<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\PHPUnitBase\TestCaseHelper;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

/**
 * 检查继承 BaseProcedure 的类的测试用例必须直接继承 AbstractProcedureTestCase
 *
 * @implements Rule<InClassNode>
 */
class ProcedureTestMustInheritAbstractTestCaseRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        if (null === $classReflection) {
            return [];
        }

        // 检查是否是测试类
        if (!TestCaseHelper::isTestClass($classReflection->getName())) {
            return [];
        }

        $originalNode = $node->getOriginalNode();
        if (!$originalNode instanceof Class_) {
            return [];
        }

        // 获取CoversClass注解
        $coversClass = $this->getCoversClassFromAnnotations($originalNode);
        if (null === $coversClass) {
            return [];
        }

        // 检查被覆盖的类是否是BaseProcedure类
        if (!$this->isBaseProcedureClass($coversClass, $scope)) {
            return [];
        }

        // 检查测试类是否直接继承AbstractProcedureTestCase
        if (!$this->inheritsFromAbstractTestCase($classReflection)) {
            return [
                RuleErrorBuilder::message(sprintf(
                    '测试类 %s 测试的 %s，一定要直接继承 ' . AbstractProcedureTestCase::class,
                    $classReflection->getName(),
                    $coversClass
                ))
                    ->identifier('procedureTest.mustInheritAbstractTestCase')
                    ->addTip('BaseProcedure类的测试必须直接继承 ' . AbstractProcedureTestCase::class)
                    ->addTip('参考示例：final class YourProcedureTest extends AbstractProcedureTestCase')
                    ->addTip('这个规则不允许被忽略')
                    ->build(),
            ];
        }

        return [];
    }

    private function getCoversClassFromAnnotations(Class_ $class): ?string
    {
        foreach ($class->attrGroups as $attrGroup) {
            $result = $this->findCoversClassInGroup($attrGroup);
            if (null !== $result) {
                return $result;
            }
        }

        return null;
    }

    private function findCoversClassInGroup(Node\AttributeGroup $attrGroup): ?string
    {
        foreach ($attrGroup->attrs as $attr) {
            if ('PHPUnit\Framework\Attributes\CoversClass' === $attr->name->toString()) {
                return $this->extractClassFromCoversAttribute($attr);
            }
        }

        return null;
    }

    private function extractClassFromCoversAttribute(Node\Attribute $attr): ?string
    {
        if (!isset($attr->args[0]) || !$attr->args[0]->value instanceof Node\Expr\ClassConstFetch) {
            return null;
        }

        $classConstFetch = $attr->args[0]->value;
        if ($classConstFetch->name instanceof Node\Identifier
            && 'class' === $classConstFetch->name->toString()
            && $classConstFetch->class instanceof Node\Name) {
            return $classConstFetch->class->toString();
        }

        return null;
    }

    private function isBaseProcedureClass(string $className, Scope $scope): bool
    {
        try {
            $classReflection = $scope->getClassReflection();
            if (null === $classReflection) {
                return false;
            }

            $coveredClassType = new ObjectType($className);
            $baseProcedureType = new ObjectType(BaseProcedure::class);

            return $baseProcedureType->isSuperTypeOf($coveredClassType)->yes();
        } catch (\Throwable) {
            // 如果无法解析类型，尝试通过类名推断
            return str_contains($className, 'Procedure');
        }
    }

    private function inheritsFromAbstractTestCase(ClassReflection $classReflection): bool
    {
        // 检查直接继承
        foreach ($classReflection->getParents() as $parent) {
            if (AbstractProcedureTestCase::class === $parent->getName()) {
                return true;
            }
        }

        return false;
    }
}
