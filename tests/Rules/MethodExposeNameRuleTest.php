<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Tests\Rules;

use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\PHPStanJsonRPC\Rules\MethodExposeNameRule;

/**
 * 测试 MethodExposeNameRule 规则
 *
 * @extends RuleTestCase<MethodExposeNameRule>
 * @internal
 */
#[CoversClass(MethodExposeNameRule::class)]
final class MethodExposeNameRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new MethodExposeNameRule(
            $this->createReflectionProvider()
        );
    }

    public function testGetNodeTypeShouldReturnInClassNode(): void
    {
        $reflectionProvider = $this->createReflectionProvider();
        $rule = new MethodExposeNameRule($reflectionProvider);
        $this->assertSame(InClassNode::class, $rule->getNodeType());
    }

    /**
     * 数据提供者，测试各种场景
     *
     * @return list<array{code: string, expectedErrors: list<array{message: string, line: int, identifier: string}>}>
     */
    public static function ruleTestProvider(): array
    {
        return array_values([
            // 有效的 MethodExpose 属性（不包含点号）
            'valid_method_expose_without_dot' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: "user_create")]
class UserCreateProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [],
            ],

            // 有效的 MethodExpose 属性（多个方法，都不包含点号）
            'valid_multiple_method_expose_without_dot' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: "user_create")]
#[MethodExpose(method: "user_update")]
class UserProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [],
            ],

            // 无效的 MethodExpose 属性（包含点号）
            'invalid_method_expose_with_dot' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: "user.create")]
class UserCreateProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [
                    [
                        'message' => 'In class Test\UserCreateProcedure, the method parameter of MethodExpose attribute cannot contain ".". Invalid value: "user.create".',
                        'line' => 7,
                        'identifier' => 'procedure.invalidMethodExposeName',
                    ],
                ],
            ],

            // 多个 MethodExpose 属性，其中一个包含点号
            'multiple_method_expose_one_with_dot' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: "user.update")]
#[MethodExpose(method: "user_create")]
class UserProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [
                    [
                        'message' => 'In class Test\UserProcedure, the method parameter of MethodExpose attribute cannot contain ".". Invalid value: "user.update".',
                        'line' => 7,
                        'identifier' => 'procedure.invalidMethodExposeName',
                    ],
                ],
            ],

            // 不继承 BaseProcedure 的类（应该被忽略）
            'class_not_inheriting_base_procedure' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;

#[MethodExpose(method: "user.create")]
class RegularClass {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [],
            ],

            // 抽象类（应该被忽略）
            'abstract_class_with_method_expose' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: "user.create")]
abstract class AbstractUserProcedure extends BaseProcedure {
    abstract public function execute(): array;
}',
                'expectedErrors' => [],
            ],

            // 没有 MethodExpose 属性的类（应该被忽略）
            'class_without_method_expose' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

class UserCreateProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [],
            ],

            // 接口（应该被忽略）
            'interface_with_method_expose' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;

#[MethodExpose(method: "user.create")]
interface UserInterface {
    public function execute(): array;
}',
                'expectedErrors' => [],
            ],

            // trait（应该被忽略）
            'trait_with_method_expose' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;

#[MethodExpose(method: "user.create")]
trait UserTrait {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [],
            ],

            // 枚举（应该被忽略）
            'enum_with_method_expose' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;

#[MethodExpose(method: "user.create")]
enum UserEnum: string {
    case ADMIN = "admin";
    case USER = "user";
}',
                'expectedErrors' => [],
            ],

            // MethodExpose 属性使用命名参数
            'method_expose_with_named_argument' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: "user_update")]
class UserUpdateProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [],
            ],

            // MethodExpose 属性使用位置参数
            'method_expose_with_positional_argument' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose("user.delete")]
class UserDeleteProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [],
            ],

            // 包含多个点号的方法名
            'method_expose_with_multiple_dots' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: "module.submodule.action")]
class ComplexProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [
                    [
                        'message' => 'In class Test\ComplexProcedure, the method parameter of MethodExpose attribute cannot contain ".". Invalid value: "module.submodule.action".',
                        'line' => 7,
                        'identifier' => 'procedure.invalidMethodExposeName',
                    ],
                ],
            ],

            // 方法名以点号开头
            'method_expose_starting_with_dot' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: ".startsWithDot")]
class DotStartProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [
                    [
                        'message' => 'In class Test\DotStartProcedure, the method parameter of MethodExpose attribute cannot contain ".". Invalid value: ".startsWithDot".',
                        'line' => 7,
                        'identifier' => 'procedure.invalidMethodExposeName',
                    ],
                ],
            ],

            // 方法名以点号结尾
            'method_expose_ending_with_dot' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: "endsWithDot.")]
class DotEndProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [
                    [
                        'message' => 'In class Test\DotEndProcedure, the method parameter of MethodExpose attribute cannot contain ".". Invalid value: "endsWithDot.".',
                        'line' => 7,
                        'identifier' => 'procedure.invalidMethodExposeName',
                    ],
                ],
            ],

            // 复杂命名空间的类
            'complex_namespace_class' => [
                'code' => '<?php
namespace Test\SubNamespace\Deep;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: "order.create")]
class OrderCreateProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [
                    [
                        'message' => 'In class Test\SubNamespace\Deep\OrderCreateProcedure, the method parameter of MethodExpose attribute cannot contain ".". Invalid value: "order.create".',
                        'line' => 7,
                        'identifier' => 'procedure.invalidMethodExposeName',
                    ],
                ],
            ],

            // 多个类在同一个文件中
            'multiple_classes_in_same_file' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: "valid_method")]
class ValidProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}

#[MethodExpose(method: "invalid.method")]
class InvalidProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [
                    [
                        'message' => 'In class Test\InvalidProcedure, the method parameter of MethodExpose attribute cannot contain ".". Invalid value: "invalid.method".',
                        'line' => 14,
                        'identifier' => 'procedure.invalidMethodExposeName',
                    ],
                ],
            ],

            // 空字符串方法名（不包含点号，应该通过）
            'empty_method_name' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: "")]
class EmptyMethodProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [],
            ],

            // 其他属性混合使用
            'mixed_attributes' => [
                'code' => '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

/**
 * @some-annotation
 */
#[MethodExpose(method: "user_action")]
class MixedAttributesProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}',
                'expectedErrors' => [],
            ],
        ]);
    }

    /**
     * 使用数据提供器测试各种场景
     *
     * @param list<array{message: string, line: int, identifier: string}> $expectedErrors
     */
    #[DataProvider('ruleTestProvider')]
    public function testRuleWithVariousScenarios(string $code, array $expectedErrors): void
    {
        $tempFile = $this->createTempFile($code);

        if ([] === $expectedErrors) {
            $this->analyse([$tempFile], []);
        } else {
            $errors = $this->gatherAnalyserErrors([$tempFile]);

            // 过滤出我们关心的错误（包含点号的错误）
            $targetErrors = [];
            foreach ($expectedErrors as $expectedError) {
                $matchingErrors = array_filter($errors, static fn ($e) => str_contains($e->getMessage(), $expectedError['message'])
                    && $e->getLine() === $expectedError['line']
                );
                $targetErrors = array_merge($targetErrors, $matchingErrors);
            }

            $this->assertNotEmpty($targetErrors, 'Expected errors were not found');

            // 检查每个期望的错误是否找到
            foreach ($expectedErrors as $expectedError) {
                $found = false;
                foreach ($targetErrors as $error) {
                    if (str_contains($error->getMessage(), $expectedError['message'])
                        && $error->getLine() === $expectedError['line']) {
                        $found = true;
                        // 检查错误标识符
                        $this->assertEquals($expectedError['identifier'], $error->getIdentifier());
                        break;
                    }
                }
                $this->assertTrue($found, sprintf(
                    'Expected error "%s" at line %d not found',
                    $expectedError['message'],
                    $expectedError['line']
                ));
            }
        }
    }

    #[Test]
    public function testRuleShouldUseCorrectErrorIdentifier(): void
    {
        $code = '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: "user.create")]
class UserCreateProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}';

        $tempFile = $this->createTempFile($code);
        $errors = $this->gatherAnalyserErrors([$tempFile]);

        $targetErrors = array_filter($errors, static fn ($e) => str_contains($e->getMessage(), 'cannot contain "."'));

        foreach ($targetErrors as $error) {
            $this->assertEquals('procedure.invalidMethodExposeName', $error->getIdentifier());
        }
    }

    #[Test]
    public function testRuleShouldProvideHelpfulTips(): void
    {
        $code = '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: "user.create")]
class UserCreateProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}';

        $tempFile = $this->createTempFile($code);
        $errors = $this->gatherAnalyserErrors([$tempFile]);

        $targetErrors = array_filter($errors, static fn ($e) => str_contains($e->getMessage(), 'cannot contain "."'));

        foreach ($targetErrors as $error) {
            $tip = $error->getTip();
            $this->assertNotNull($tip);
            $this->assertStringContainsString('参考`AddToCart`', $tip);
            $this->assertStringContainsString('使用动词+模块+名词来', $tip);
        }
    }

    #[Test]
    public function testRuleShouldIgnoreAbstractProcedures(): void
    {
        $code = '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: "user.create")]
abstract class AbstractUserProcedure extends BaseProcedure {
    abstract public function execute(): array;
}';

        $tempFile = $this->createTempFile($code);
        $errors = $this->gatherAnalyserErrors([$tempFile]);

        // 不应该生成任何错误，因为该类是抽象类
        $targetErrors = array_filter($errors, static fn ($e) => str_contains($e->getMessage(), 'cannot contain "."'));
        $this->assertCount(0, $targetErrors);
    }

    #[Test]
    public function testRuleShouldIgnoreNonBaseProcedureClasses(): void
    {
        $code = '<?php
namespace Test;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;

#[MethodExpose(method: "user.create")]
class RegularClass {
    public function execute(): array {
        return [];
    }
}';

        $tempFile = $this->createTempFile($code);
        $errors = $this->gatherAnalyserErrors([$tempFile]);

        // 不应该生成任何错误，因为 RegularClass 不是 BaseProcedure
        $targetErrors = array_filter($errors, static fn ($e) => str_contains($e->getMessage(), 'cannot contain "."'));
        $this->assertCount(0, $targetErrors);
    }

    #[Test]
    public function testRuleShouldHandleComplexClassNames(): void
    {
        $code = '<?php
namespace Test\SubNamespace\Deep;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodExpose(method: "complex.action")]
class ComplexNamedProcedure extends BaseProcedure {
    public function execute(): array {
        return [];
    }
}';

        $tempFile = $this->createTempFile($code);
        $errors = $this->gatherAnalyserErrors([$tempFile]);

        $targetErrors = array_filter($errors, static fn ($e) => str_contains($e->getMessage(), 'cannot contain "."'));

        foreach ($targetErrors as $error) {
            $this->assertStringContainsString('Test\SubNamespace\Deep\ComplexNamedProcedure', $error->getMessage());
            $this->assertStringContainsString('complex.action', $error->getMessage());
        }
    }

    private function createTempFile(string $code): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phpstan_test_');
        file_put_contents($tempFile, $code);

        return $tempFile;
    }
}
