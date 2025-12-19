<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures;

use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

/**
 * 测试用的 Param 类
 */
readonly class TestParam implements RpcParamInterface
{
    public function __construct(
        public string $name = '',
        public int $value = 0,
    ) {
    }
}
