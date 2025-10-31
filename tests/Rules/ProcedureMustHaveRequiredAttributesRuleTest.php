<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\PHPStanJsonRPC\Rules\ProcedureMustHaveRequiredAttributesRule;

/**
 * @extends RuleTestCase<ProcedureMustHaveRequiredAttributesRule>
 * @internal
 */
#[CoversNothing]
final class ProcedureMustHaveRequiredAttributesRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ProcedureMustHaveRequiredAttributesRule($this->createReflectionProvider());
    }

    protected function setUp(): void
    {
        parent::setUp();

        // 加载测试夹具类
        require_once __DIR__ . '/Fixtures/ValidProcedure.php';
        require_once __DIR__ . '/Fixtures/MissingMethodTagProcedure.php';
        require_once __DIR__ . '/Fixtures/MissingMethodDocProcedure.php';
        require_once __DIR__ . '/Fixtures/MissingMethodExposeProcedure.php';
        require_once __DIR__ . '/Fixtures/MissingMultipleAttributesProcedure.php';
        require_once __DIR__ . '/Fixtures/NonProcedureClass.php';
        require_once __DIR__ . '/Fixtures/AbstractProcedure.php';
    }

    public function testValidProcedureWithAllRequiredAttributes(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ValidProcedure.php'], []);
    }

    public function testMissingMethodTagAttribute(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/MissingMethodTagProcedure.php'], [
            [
                'Class Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures\MissingMethodTagProcedure must have the following attributes: Tourze\JsonRPC\Core\Attribute\MethodTag.',
                11,
            ],
        ]);
    }

    public function testMissingMethodDocAttribute(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/MissingMethodDocProcedure.php'], [
            [
                'Class Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures\MissingMethodDocProcedure must have the following attributes: Tourze\JsonRPC\Core\Attribute\MethodDoc.',
                11,
            ],
        ]);
    }

    public function testMissingMethodExposeAttribute(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/MissingMethodExposeProcedure.php'], [
            [
                'Class Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures\MissingMethodExposeProcedure must have the following attributes: Tourze\JsonRPC\Core\Attribute\MethodExpose.',
                11,
            ],
        ]);
    }

    public function testMissingMultipleAttributes(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/MissingMultipleAttributesProcedure.php'], [
            [
                'Class Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures\MissingMultipleAttributesProcedure must have the following attributes: Tourze\JsonRPC\Core\Attribute\MethodTag, Tourze\JsonRPC\Core\Attribute\MethodDoc.',
                11,
            ],
        ]);
    }

    public function testNonProcedureClassIsIgnored(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/NonProcedureClass.php'], []);
    }

    public function testAbstractClassIsIgnored(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/AbstractProcedure.php'], []);
    }

    #[DataProvider('provideMissingAttributesCases')]
    public function testMissingAttributesCases(string $fixtureFile, array $expectedErrors): void
    {
        $this->analyse([$fixtureFile], $expectedErrors);
    }

    /**
     * @return iterable<string, array{0: string, 1: array<array{0: string, 1: int}>}>
     */
    public static function provideMissingAttributesCases(): iterable
    {
        yield 'missing MethodTag' => [
            __DIR__ . '/Fixtures/MissingMethodTagProcedure.php',
            [
                [
                    'Class Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures\MissingMethodTagProcedure must have the following attributes: Tourze\JsonRPC\Core\Attribute\MethodTag.',
                    11,
                ],
            ],
        ];

        yield 'missing MethodDoc' => [
            __DIR__ . '/Fixtures/MissingMethodDocProcedure.php',
            [
                [
                    'Class Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures\MissingMethodDocProcedure must have the following attributes: Tourze\JsonRPC\Core\Attribute\MethodDoc.',
                    11,
                ],
            ],
        ];

        yield 'missing MethodExpose' => [
            __DIR__ . '/Fixtures/MissingMethodExposeProcedure.php',
            [
                [
                    'Class Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures\MissingMethodExposeProcedure must have the following attributes: Tourze\JsonRPC\Core\Attribute\MethodExpose.',
                    11,
                ],
            ],
        ];

        yield 'missing multiple attributes' => [
            __DIR__ . '/Fixtures/MissingMultipleAttributesProcedure.php',
            [
                [
                    'Class Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures\MissingMultipleAttributesProcedure must have the following attributes: Tourze\JsonRPC\Core\Attribute\MethodTag, Tourze\JsonRPC\Core\Attribute\MethodDoc.',
                    11,
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
        yield 'valid procedure with all attributes' => [
            __DIR__ . '/Fixtures/ValidProcedure.php',
        ];

        yield 'non procedure class' => [
            __DIR__ . '/Fixtures/NonProcedureClass.php',
        ];

        yield 'abstract procedure' => [
            __DIR__ . '/Fixtures/AbstractProcedure.php',
        ];
    }

    public function testRuleErrorIdentifier(): void
    {
        $rule = new ProcedureMustHaveRequiredAttributesRule($this->createReflectionProvider());
        $this->assertSame(ProcedureMustHaveRequiredAttributesRule::class, get_class($rule));

        // 验证规则支持正确的节点类型
        $this->assertSame('PHPStan\Node\InClassNode', $rule->getNodeType());
    }
}
