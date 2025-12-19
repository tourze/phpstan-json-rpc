<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Contracts\RpcResultInterface;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPC\Core\Result\ArrayResult;

// 缺少 MethodTag 和 MethodDoc 属性
#[MethodExpose(method: 'test.missingMultiple')]
class MissingMultipleAttributesProcedure extends BaseProcedure
{
    public function execute(RpcParamInterface $param): RpcResultInterface
    {
        return new ArrayResult(['result' => 'test']);
    }
}
