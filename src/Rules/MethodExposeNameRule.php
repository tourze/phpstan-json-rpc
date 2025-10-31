<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

/**
 * 检查继承 BaseProcedure 的类，MethodExpose 的 method 参数不允许带“.”
 *
 * @implements Rule<InClassNode>
 */
readonly class MethodExposeNameRule implements Rule
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        if (!$this->isValidProcedureClass($classReflection)) {
            return [];
        }

        $originalNode = $node->getOriginalNode();
        if (!$originalNode instanceof Class_) {
            return [];
        }

        if (null === $classReflection) {
            return [];
        }

        return $this->validateMethodExposeAttributes($originalNode, $classReflection);
    }

    private function isValidProcedureClass(?ClassReflection $classReflection): bool
    {
        if (null === $classReflection) {
            return false;
        }

        if (!$classReflection->isClass() || $classReflection->isAbstract()) {
            return false;
        }

        if (!$this->reflectionProvider->hasClass(BaseProcedure::class)) {
            return false;
        }

        $baseProcedureReflection = $this->reflectionProvider->getClass(BaseProcedure::class);

        return $classReflection->isSubclassOfClass($baseProcedureReflection);
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateMethodExposeAttributes(Class_ $classNode, ClassReflection $classReflection): array
    {
        foreach ($classNode->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($this->isMethodExposeAttribute($attr)) {
                    $error = $this->validateMethodAttribute($attr, $classReflection);
                    if (null !== $error) {
                        return [$error];
                    }
                }
            }
        }

        return [];
    }

    private function isMethodExposeAttribute(Node\Attribute $attr): bool
    {
        return MethodExpose::class === $attr->name->toString();
    }

    private function validateMethodAttribute(Node\Attribute $attr, ClassReflection $classReflection): ?IdentifierRuleError
    {
        foreach ($attr->args as $arg) {
            if ($this->isMethodArgument($arg)) {
                return $this->validateMethodValue($arg->value, $classReflection);
            }
        }

        return null;
    }

    private function isMethodArgument(Node\Arg $arg): bool
    {
        return $arg->name instanceof Node\Identifier && 'method' === $arg->name->toString();
    }

    private function validateMethodValue(Node $value, ClassReflection $classReflection): ?IdentifierRuleError
    {
        if ($value instanceof Node\Scalar\String_) {
            if (str_contains($value->value, '.')) {
                return RuleErrorBuilder::message(sprintf(
                    'In class %s, the method parameter of MethodExpose attribute cannot contain ".". Invalid value: "%s".',
                    $classReflection->getName(),
                    $value->value
                ))
                    ->identifier('procedure.invalidMethodExposeName')
                    ->addTip('参考`AddToCart`，使用动词+模块+名词来')
                    ->build()
                ;
            }
        }

        return null;
    }
}
