<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures;

use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Contracts\RpcResultInterface;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

// 抽象类应该被规则忽略
#[MethodTag(name: 'test')]
#[MethodDoc(summary: 'Abstract Procedure', description: 'This abstract class should be ignored')]
#[MethodExpose(method: 'test.abstract')]
abstract class AbstractProcedure extends BaseProcedure
{
    abstract public function execute(RpcParamInterface $param): RpcResultInterface;
}
 