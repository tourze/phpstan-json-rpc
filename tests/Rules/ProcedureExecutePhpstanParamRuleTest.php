<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\PHPStanJsonRPC\Rules\ProcedureExecutePhpstanParamRule;

/**
 * @extends RuleTestCase<ProcedureExecutePhpstanParamRule>
 *
 * @internal
 */
#[CoversClass(ProcedureExecutePhpstanParamRule::class)]
final class ProcedureExecutePhpstanParamRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ProcedureExecutePhpstanParamRule($this->createReflectionProvider());
    }

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/Fixtures/TestParam.php';
        require_once __DIR__ . '/Fixtures/ValidProcedureWithPhpstanParam.php';
        require_once __DIR__ . '/Fixtures/ProcedureWithWrongParamAnnotation.php';
        require_once __DIR__ . '/Fixtures/ProcedureWithoutDocblock.php';
        require_once __DIR__ . '/Fixtures/ProcedureWithWrongPhpstanParamType.php';
        require_once __DIR__ . '/Fixtures/ProcedureWithFullyQualifiedPhpstanParam.php';
    }

    public function testValidProcedureWithCorrectPhpstanParam(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ValidProcedureWithPhpstanParam.php'], []);
    }

    public function testValidProcedureWithFullyQualifiedPhpstanParam(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ProcedureWithFullyQualifiedPhpstanParam.php'], []);
    }

    public function testProcedureWithWrongParamAnnotation(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ProcedureWithWrongParamAnnotation.php'], [
            [
                'Procedure Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures\ProcedureWithWrongParamAnnotation execute() must use "@phpstan-param" instead of "@param" for parameter $param.',
                15,
                "将 @param 改为 @phpstan-param：\n- @param TestParam \$param\n+ @phpstan-param TestParam \$param",
            ],
        ]);
    }

    public function testProcedureWithoutDocblock(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ProcedureWithoutDocblock.php'], [
            [
                'Procedure Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures\ProcedureWithoutDocblock execute() must declare "@phpstan-param TestParam $param" in docblock.',
                15,
                "请在 execute() 方法上方添加文档块：\n/**\n * @phpstan-param TestParam \$param\n */",
            ],
        ]);
    }

    public function testProcedureWithWrongPhpstanParamType(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ProcedureWithWrongPhpstanParamType.php'], [
            [
                'Procedure Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures\ProcedureWithWrongPhpstanParamType execute() @phpstan-param type must be "TestParam", "WrongParam" given.',
                15,
                "请修改 @phpstan-param 的类型：\n- @phpstan-param WrongParam \$param\n+ @phpstan-param TestParam \$param",
            ],
        ]);
    }

    public function testNonProcedureClassIsIgnored(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/NonProcedureClass.php'], []);
    }

    public function testAbstractProcedureIsIgnored(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/AbstractProcedure.php'], []);
    }

    public function testValidProcedureWithoutParamIsIgnored(): void
    {
        // ValidProcedure 没有 RpcParamInterface 参数，应该被忽略
        $this->analyse([__DIR__ . '/Fixtures/ValidProcedure.php'], []);
    }

    #[DataProvider('provideErrorCases')]
    public function testErrorCases(string $fixtureFile, array $expectedErrors): void
    {
        $this->analyse([$fixtureFile], $expectedErrors);
    }

    /**
     * @return iterable<string, array{0: string, 1: array<array{0: string, 1: int, 2?: string}>}>
     */
    public static function provideErrorCases(): iterable
    {
        yield 'wrong @param annotation' => [
            __DIR__ . '/Fixtures/ProcedureWithWrongParamAnnotation.php',
            [
                [
                    'Procedure Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures\ProcedureWithWrongParamAnnotation execute() must use "@phpstan-param" instead of "@param" for parameter $param.',
                    15,
                    "将 @param 改为 @phpstan-param：\n- @param TestParam \$param\n+ @phpstan-param TestParam \$param",
                ],
            ],
        ];

        yield 'missing docblock' => [
            __DIR__ . '/Fixtures/ProcedureWithoutDocblock.php',
            [
                [
                    'Procedure Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures\ProcedureWithoutDocblock execute() must declare "@phpstan-param TestParam $param" in docblock.',
                    15,
                    "请在 execute() 方法上方添加文档块：\n/**\n * @phpstan-param TestParam \$param\n */",
                ],
            ],
        ];

        yield 'wrong @phpstan-param type' => [
            __DIR__ . '/Fixtures/ProcedureWithWrongPhpstanParamType.php',
            [
                [
                    'Procedure Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures\ProcedureWithWrongPhpstanParamType execute() @phpstan-param type must be "TestParam", "WrongParam" given.',
                    15,
                    "请修改 @phpstan-param 的类型：\n- @phpstan-param WrongParam \$param\n+ @phpstan-param TestParam \$param",
                ],
            ],
        ];
    }

    #[DataProvider('provideValidCases')]
    public function testValidCases(string $fixtureFile): void
    {
        $this->analyse([$fixtureFile], []);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideValidCases(): iterable
    {
        yield 'valid procedure with correct @phpstan-param' => [
            __DIR__ . '/Fixtures/ValidProcedureWithPhpstanParam.php',
        ];

        yield 'valid procedure with fully qualified @phpstan-param' => [
            __DIR__ . '/Fixtures/ProcedureWithFullyQualifiedPhpstanParam.php',
        ];

        yield 'non procedure class' => [
            __DIR__ . '/Fixtures/NonProcedureClass.php',
        ];

        yield 'abstract procedure' => [
            __DIR__ . '/Fixtures/AbstractProcedure.php',
        ];

        yield 'procedure without RpcParamInterface param' => [
            __DIR__ . '/Fixtures/ValidProcedure.php',
        ];
    }

    public function testRuleConfiguration(): void
    {
        $rule = new ProcedureExecutePhpstanParamRule($this->createReflectionProvider());
        $this->assertSame('PHPStan\Node\InClassNode', $rule->getNodeType());
    }
}
