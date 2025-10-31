<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

// 缺少 MethodTag 和 MethodDoc 属性
#[MethodExpose(method: 'test.missingMultiple')]
class MissingMultipleAttributesProcedure extends BaseProcedure
{
    public function execute(): array
    {
        return ['result' => 'test'];
    }
}
