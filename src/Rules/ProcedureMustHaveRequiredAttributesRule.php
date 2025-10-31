<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

/**
 * 检查继承 BaseProcedure 的非抽象类，必须包含 MethodTag, MethodDoc, MethodExpose 注解
 *
 * @implements Rule<InClassNode>
 */
readonly class ProcedureMustHaveRequiredAttributesRule implements Rule
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        if (null === $classReflection || !$classReflection->isClass() || $classReflection->isAbstract()) {
            return [];
        }

        $baseProcedureReflection = $this->reflectionProvider->getClass(BaseProcedure::class);
        if (!$classReflection->isSubclassOfClass($baseProcedureReflection)) {
            return [];
        }

        $originalNode = $node->getOriginalNode();
        if (!$originalNode instanceof Class_) {
            return [];
        }

        $attributeNames = $this->getAttributeNames($originalNode);

        $requiredAttributes = [
            MethodTag::class,
            MethodDoc::class,
            MethodExpose::class,
        ];

        $missingAttributes = [];
        foreach ($requiredAttributes as $requiredAttribute) {
            if (!in_array($requiredAttribute, $attributeNames, true)) {
                $missingAttributes[] = $requiredAttribute;
            }
        }

        if ([] !== $missingAttributes) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Class %s must have the following attributes: %s.',
                    $classReflection->getName(),
                    implode(', ', $missingAttributes)
                ))
                    ->identifier('procedure.missingRequiredAttributes')
                    ->build(),
            ];
        }

        return [];
    }

    /**
     * @return string[]
     */
    private function getAttributeNames(Class_ $class): array
    {
        $names = [];
        foreach ($class->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $names[] = $attr->name->toString();
            }
        }

        return $names;
    }
}
