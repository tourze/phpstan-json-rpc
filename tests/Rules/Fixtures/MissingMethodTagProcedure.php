<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures;

use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Contracts\RpcResultInterface;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPC\Core\Result\ArrayResult;

#[MethodDoc(summary: 'Test Procedure', description: 'Missing MethodTag attribute')]
#[MethodExpose(method: 'test.missingTag')]
class MissingMethodTagProcedure extends BaseProcedure
{
    public function execute(RpcParamInterface $param): RpcResultInterface
    {
        return new ArrayResult(['result' => 'test']);
    }
}
