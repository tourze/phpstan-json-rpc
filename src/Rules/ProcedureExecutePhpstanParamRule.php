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
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

/**
 * 检查继承 BaseProcedure 的非抽象类的 execute() 方法必须包含 @phpstan-param 注解
 *
 * 背景：由于 Param 类实现了 RpcParamInterface，execute 的联合类型（ConcreteParam|RpcParamInterface）
 * 在静态分析时容易被收敛为 RpcParamInterface，导致访问 $param->xxx 被误判为不存在。
 * 使用 @phpstan-param 可以让 PHPStan 识别具体类型。
 *
 * @implements Rule<InClassNode>
 */
readonly class ProcedureExecutePhpstanParamRule implements Rule
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

        if (!$this->isBaseProcedureSubclass($classReflection)) {
            return [];
        }

        $originalNode = $node->getOriginalNode();
        if (!$originalNode instanceof Class_) {
            return [];
        }

        return $this->validateExecuteMethod($classReflection, $scope);
    }

    /**
     * @param \PHPStan\Reflection\ClassReflection $classReflection
     */
    private function isBaseProcedureSubclass($classReflection): bool
    {
        $baseProcedureReflection = $this->reflectionProvider->getClass(BaseProcedure::class);

        return $classReflection->isSubclassOfClass($baseProcedureReflection);
    }

    /**
     * @param \PHPStan\Reflection\ClassReflection $classReflection
     *
     * @return array<\PHPStan\Rules\RuleError>
     */
    private function validateExecuteMethod($classReflection, Scope $scope): array
    {
        if (!$classReflection->hasMethod('execute')) {
            return [];
        }

        $nativeReflection = $classReflection->getNativeReflection();

        try {
            $methodReflection = $nativeReflection->getMethod('execute');
        } catch (\ReflectionException) {
            return [];
        }

        $parameters = $methodReflection->getParameters();
        if (0 === count($parameters)) {
            return [];
        }

        $concreteParamClass = $this->findConcreteParamClass($parameters[0]->getType());
        if (null === $concreteParamClass) {
            return [];
        }

        return $this->validatePhpstanParamAnnotation(
            $classReflection->getName(),
            $methodReflection,
            $parameters[0]->getName(),
            $concreteParamClass
        );
    }

    /**
     * @param class-string $className
     * @param class-string<RpcParamInterface> $concreteParamClass
     *
     * @return array<\PHPStan\Rules\RuleError>
     */
    private function validatePhpstanParamAnnotation(
        string $className,
        \ReflectionMethod $methodReflection,
        string $paramName,
        string $concreteParamClass,
    ): array {
        $docComment = $methodReflection->getDocComment();
        $expectedShort = $this->getShortClassName($concreteParamClass);

        if (false === $docComment) {
            return $this->buildMissingPhpstanParamError($className, $expectedShort, $paramName);
        }

        return $this->checkDocCommentAnnotation($docComment, $className, $paramName, $expectedShort, $concreteParamClass);
    }

    /**
     * @param class-string $className
     * @param class-string<RpcParamInterface> $concreteParamClass
     *
     * @return array<\PHPStan\Rules\RuleError>
     */
    private function checkDocCommentAnnotation(
        string $docComment,
        string $className,
        string $paramName,
        string $expectedShort,
        string $concreteParamClass,
    ): array {
        $pattern = '/@phpstan-param\s+([^\s]+)\s+\$' . preg_quote($paramName, '/') . '\b/';

        if (1 !== preg_match($pattern, $docComment, $matches)) {
            return $this->handleMissingPhpstanParam($docComment, $className, $paramName, $expectedShort);
        }

        $docType = $matches[1];
        if (!$this->matchesParamClass($docType, $concreteParamClass)) {
            $tip = sprintf(
                "请修改 @phpstan-param 的类型：\n- @phpstan-param %s \$%s\n+ @phpstan-param %s \$%s",
                $docType,
                $paramName,
                $expectedShort,
                $paramName
            );

            return [
                RuleErrorBuilder::message(sprintf(
                    'Procedure %s execute() @phpstan-param type must be "%s", "%s" given.',
                    $className,
                    $expectedShort,
                    $docType
                ))
                    ->identifier('procedure.wrongPhpstanParamType')
                    ->tip($tip)
                    ->build(),
            ];
        }

        return [];
    }

    /**
     * @param class-string $className
     *
     * @return array<\PHPStan\Rules\RuleError>
     */
    private function handleMissingPhpstanParam(string $docComment, string $className, string $paramName, string $expectedShort): array
    {
        $paramPattern = '/@param\s+([^\s]+)\s+\$' . preg_quote($paramName, '/') . '\b/';
        if (1 === preg_match($paramPattern, $docComment, $matches)) {
            $currentType = $matches[1];
            $tip = sprintf(
                "将 @param 改为 @phpstan-param：\n- @param %s \$%s\n+ @phpstan-param %s \$%s",
                $currentType,
                $paramName,
                $expectedShort,
                $paramName
            );

            return [
                RuleErrorBuilder::message(sprintf(
                    'Procedure %s execute() must use "@phpstan-param" instead of "@param" for parameter $%s.',
                    $className,
                    $paramName
                ))
                    ->identifier('procedure.wrongParamAnnotation')
                    ->tip($tip)
                    ->build(),
            ];
        }

        return $this->buildMissingPhpstanParamError($className, $expectedShort, $paramName);
    }

    /**
     * @param class-string $className
     *
     * @return array<\PHPStan\Rules\RuleError>
     */
    private function buildMissingPhpstanParamError(string $className, string $expectedShort, string $paramName): array
    {
        $tip = sprintf(
            "请在 execute() 方法上方添加文档块：\n/**\n * @phpstan-param %s \$%s\n */",
            $expectedShort,
            $paramName
        );

        return [
            RuleErrorBuilder::message(sprintf(
                'Procedure %s execute() must declare "@phpstan-param %s $%s" in docblock.',
                $className,
                $expectedShort,
                $paramName
            ))
                ->identifier('procedure.missingPhpstanParam')
                ->tip($tip)
                ->build(),
        ];
    }

    /**
     * @return class-string<RpcParamInterface>|null
     */
    private function findConcreteParamClass(?\ReflectionType $type): ?string
    {
        if (null === $type) {
            return null;
        }

        $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [$type];

        foreach ($types as $t) {
            if (!$t instanceof \ReflectionNamedType) {
                continue;
            }

            $paramClass = $this->checkIfRpcParamImplementation($t->getName());
            if (null !== $paramClass) {
                return $paramClass;
            }
        }

        return null;
    }

    /**
     * @return class-string<RpcParamInterface>|null
     */
    private function checkIfRpcParamImplementation(string $className): ?string
    {
        if (RpcParamInterface::class === $className) {
            return null;
        }

        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if ($classReflection->implementsInterface(RpcParamInterface::class)) {
            return $className;
        }

        return null;
    }

    private function getShortClassName(string $className): string
    {
        $pos = strrpos($className, '\\');
        if (false === $pos) {
            return $className;
        }

        return substr($className, $pos + 1);
    }

    /**
     * @param class-string<RpcParamInterface> $paramClass
     */
    private function matchesParamClass(string $docType, string $paramClass): bool
    {
        $expectedFqcn = ltrim($paramClass, '\\');
        $expectedShort = $this->getShortClassName($paramClass);
        $normalizedDocType = ltrim($docType, '\\');

        return 0 === strcasecmp($normalizedDocType, $expectedFqcn)
            || 0 === strcasecmp($normalizedDocType, $expectedShort);
    }
}
