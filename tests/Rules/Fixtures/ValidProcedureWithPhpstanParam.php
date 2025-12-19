<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures;

use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Contracts\RpcResultInterface;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPC\Core\Result\ArrayResult;

#[MethodTag(name: 'test')]
#[MethodDoc(summary: 'Test Procedure')]
#[MethodExpose(method: 'test.validPhpstanParam')]
class ValidProcedureWithPhpstanParam extends BaseProcedure
{
    /**
     * @phpstan-param TestParam $param
     */
    public function execute(TestParam|RpcParamInterface $param): RpcResultInterface
    {
        return new ArrayResult(['name' => $param->name]);
    }
}
