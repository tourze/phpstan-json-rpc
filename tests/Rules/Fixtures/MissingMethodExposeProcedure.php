<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures;

use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Contracts\RpcResultInterface;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPC\Core\Result\ArrayResult;

#[MethodTag(name: 'test')]
#[MethodDoc(summary: 'Test Procedure', description: 'Missing MethodExpose attribute')]
class MissingMethodExposeProcedure extends BaseProcedure
{
    public function execute(RpcParamInterface $param): RpcResultInterface
    {
        return new ArrayResult(['result' => 'test']);
    }
}
