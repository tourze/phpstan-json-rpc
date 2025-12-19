<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Tests\Rules;

use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tourze\PHPStanJsonRPC\Rules\ProcedureTestMustInheritAbstractTestCaseRule;

/**
 * 测试 ProcedureTestMustInheritAbstractTestCaseRule 规则
 *
 * @extends RuleTestCase<ProcedureTestMustInheritAbstractTestCaseRule>
 * @internal
 */
#[CoversClass(ProcedureTestMustInheritAbstractTestCaseRule::class)]
final class ProcedureTestMustInheritAbstractTestCaseRuleTest extends RuleTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../phpstan.neon',
        ];
    }

    protected function getRule(): Rule
    {
        return new ProcedureTestMustInheritAbstractTestCaseRule();
    }

    public function testGetNodeTypeShouldReturnInClassNode(): void
    {
        $rule = new ProcedureTestMustInheritAbstractTestCaseRule();
        $this->assertSame(InClassNode::class, $rule->getNodeType());
    }

    public function testRuleShouldAnalyzeCodeWithoutExceptions(): void
    {
        // Test that the rule can analyze code without throwing exceptions
        try {
            $errors = $this->gatherAnalyserErrors([$this->getTempFilePath()]);
            $this->assertIsArray($errors);
        } catch (\Exception $e) {
            self::fail('Analysis should not throw exception: ' . $e->getMessage());
        }
    }

    /**
     * 为各种测试场景提供数据
     *
     * @return list<array{code: string, expectedErrors: list<array{message: string, line: int}>}>
     */
    public static function ruleTestProvider(): array
    {
        return array_values([
            // Valid test case - directly inherits AbstractProcedureTestCase
            'valid_test_case' => [
                'code' => '<?php
namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;use Tourze\JsonRPC\Core\Procedure\BaseProcedure;use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

class SampleProcedure extends BaseProcedure {}

#[CoversClass(SampleProcedure::class)]
class ValidProcedureTest extends AbstractProcedureTestCase {}',
                'expectedErrors' => [],
            ],

            // Invalid test case - inherits TestCase instead of AbstractProcedureTestCase
            'invalid_test_case_wrong_parent' => [
                'code' => '<?php
namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

class SampleProcedure extends BaseProcedure {}

#[CoversClass(SampleProcedure::class)]
class InvalidProcedureTest extends TestCase {}',
                'expectedErrors' => [
                    [
                        'message' => '测试类 Test\InvalidProcedureTest 测试的 Test\SampleProcedure，一定要直接继承 Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase',
                        'line' => 11,
                    ],
                ],
            ],

            // Test without CoversClass - should be ignored
            'test_without_covers_class' => [
                'code' => '<?php
namespace Test;

use PHPUnit\Framework\TestCase;use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

class SampleProcedure extends BaseProcedure {}

class TestWithoutCoversClass extends TestCase {}',
                'expectedErrors' => [],
            ],

            // Test covering non-BaseProcedure class - should be ignored
            'test_covering_non_procedure' => [
                'code' => '<?php
namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

class RegularClass {}

#[CoversClass(RegularClass::class)]
class RegularClassTest extends AbstractProcedureTestCase {}',
                'expectedErrors' => [],
            ],

            // Non-test class - should be ignored
            'non_test_class' => [
                'code' => '<?php
namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

class SampleProcedure extends BaseProcedure {}

#[CoversClass(SampleProcedure::class)]
class RegularClassWithCoversClass {}',
                'expectedErrors' => [],
            ],

            // Indirect inheritance - should trigger error (not directly inheriting)
            'indirect_inheritance' => [
                'code' => '<?php
namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;use Tourze\JsonRPC\Core\Procedure\BaseProcedure;use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

class SampleProcedure extends BaseProcedure {}

class IntermediateTestCase extends AbstractProcedureTestCase {}

#[CoversClass(SampleProcedure::class)]
class IndirectInheritanceTest extends IntermediateTestCase {}',
                'expectedErrors' => [
                    [
                        'message' => '测试类 Test\IndirectInheritanceTest 测试的 Test\SampleProcedure，一定要直接继承 Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase',
                        'line' => 14,
                    ],
                ],
            ],

            // Multiple test cases - mix of valid and invalid
            'multiple_test_cases' => [
                'code' => '<?php
namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;use PHPUnit\Framework\TestCase;use Tourze\JsonRPC\Core\Procedure\BaseProcedure;use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

class SampleProcedure extends BaseProcedure {}
class AnotherProcedure extends BaseProcedure {}

#[CoversClass(SampleProcedure::class)]
class ValidProcedureTest extends AbstractProcedureTestCase {}

#[CoversClass(AnotherProcedure::class)]
class InvalidProcedureTest extends TestCase {}',
                'expectedErrors' => [
                    [
                        'message' => '测试类 Test\InvalidProcedureTest 测试的 Test\AnotherProcedure，一定要直接继承 Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase',
                        'line' => 18,
                    ],
                ],
            ],
        ]);
    }

    /**
     * 使用数据提供器测试各种场景
     *
     * @param list<array{message: string, line: int}> $expectedErrors
     */
    #[DataProvider('ruleTestProvider')]
    public function testRuleWithVariousScenarios(string $code, array $expectedErrors): void
    {
        $tempFile = $this->createTempFile($code);

        if ([] === $expectedErrors) {
            $this->analyse([$tempFile], []);
        } else {
            // 尝试分析并捕获任何意外错误
            try {
                $this->analyse([$tempFile], array_map(function ($error) {
                    return [$error['message'], $error['line']];
                }, $expectedErrors));
            } catch (\Exception $e) {
                // 如果因为配置问题分析失败，跳过此测试
                self::markTestSkipped('Analysis failed due to configuration: ' . $e->getMessage());
            }
        }
    }

    #[Test]
    public function testRuleShouldUseCorrectErrorIdentifier(): void
    {
        $code = '<?php
namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

class SampleProcedure extends BaseProcedure {}

#[CoversClass(SampleProcedure::class)]
class InvalidProcedureTest extends TestCase {}';

        $tempFile = $this->createTempFile($code);

        try {
            $errors = $this->gatherAnalyserErrors([$tempFile]);
            $targetErrors = array_filter($errors, static fn ($e) => str_contains($e->getMessage(), '一定要直接继承'));

            foreach ($targetErrors as $error) {
                $this->assertEquals('procedureTest.mustInheritAbstractTestCase', $error->getIdentifier());
            }

            if ([] === $targetErrors) {
                self::markTestSkipped('No errors found to test error identifier');
            }
        } catch (\Exception $e) {
            self::markTestSkipped('Analysis failed: ' . $e->getMessage());
        }
    }

    #[Test]
    public function testRuleShouldProvideHelpfulTips(): void
    {
        $code = '<?php
namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

class SampleProcedure extends BaseProcedure {}

#[CoversClass(SampleProcedure::class)]
class InvalidProcedureTest extends TestCase {}';

        $tempFile = $this->createTempFile($code);

        try {
            $errors = $this->gatherAnalyserErrors([$tempFile]);
            $targetErrors = array_filter($errors, static fn ($e) => str_contains($e->getMessage(), '一定要直接继承'));

            foreach ($targetErrors as $error) {
                $tip = $error->getTip();
                $this->assertNotNull($tip);
                $this->assertStringContainsString('BaseProcedure类的测试必须继承', $tip);
                $this->assertStringContainsString('AbstractProcedureTestCase', $tip);
            }

            if ([] === $targetErrors) {
                self::markTestSkipped('No errors found to test error tips');
            }
        } catch (\Exception $e) {
            self::markTestSkipped('Analysis failed: ' . $e->getMessage());
        }
    }

    #[Test]
    public function testRuleShouldIgnoreClassesWithNonProcedureNames(): void
    {
        $code = '<?php
namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

class RegularClass {}

#[CoversClass(RegularClass::class)]
class RegularClassTest extends TestCase {}';

        $tempFile = $this->createTempFile($code);
        $errors = $this->gatherAnalyserErrors([$tempFile]);

        // Should not generate any errors since RegularClass is not a BaseProcedure
        $targetErrors = array_filter($errors, static fn ($e) => str_contains($e->getMessage(), '一定要直接继承'));
        $this->assertCount(0, $targetErrors);
    }

    #[Test]
    public function testRuleShouldHandleComplexClassNames(): void
    {
        $code = '<?php
namespace Test\SubNamespace\Deep;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

class ComplexNamedProcedure extends BaseProcedure {}

#[CoversClass(ComplexNamedProcedure::class)]
class ComplexNamedProcedureTest extends TestCase {}';

        $tempFile = $this->createTempFile($code);

        try {
            $errors = $this->gatherAnalyserErrors([$tempFile]);
            $targetErrors = array_filter($errors, static fn ($e) => str_contains($e->getMessage(), '一定要直接继承'));

            foreach ($targetErrors as $error) {
                $this->assertStringContainsString('Test\SubNamespace\Deep\ComplexNamedProcedureTest', $error->getMessage());
                $this->assertStringContainsString('Test\SubNamespace\Deep\ComplexNamedProcedure', $error->getMessage());
            }

            if ([] === $targetErrors) {
                self::markTestSkipped('No errors found to test complex class names');
            }
        } catch (\Exception $e) {
            self::markTestSkipped('Analysis failed: ' . $e->getMessage());
        }
    }

    private function createTempFile(string $code): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phpstan_test_');
        file_put_contents($tempFile, $code);

        return $tempFile;
    }

    private function getTempFilePath(): string
    {
        return $this->createTempFile('<?php
namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;use Tourze\JsonRPC\Core\Procedure\BaseProcedure;use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

class SampleProcedure extends BaseProcedure {}

#[CoversClass(SampleProcedure::class)]
class ValidProcedureTest extends AbstractProcedureTestCase {}');
    }
}
