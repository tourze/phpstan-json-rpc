<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodTag(name: 'test')]
#[MethodExpose(method: 'test.missingDoc')]
class MissingMethodDocProcedure extends BaseProcedure
{
    public function execute(): array
    {
        return ['result' => 'test'];
    }
}
