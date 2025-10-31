<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures;

use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;

#[MethodTag(name: 'test')]
#[MethodDoc(summary: 'Test Procedure', description: 'Missing MethodExpose attribute')]
class MissingMethodExposeProcedure extends BaseProcedure
{
    public function execute(): array
    {
        return ['result' => 'test'];
    }
}
