<?php

declare(strict_types=1);

namespace Tourze\PHPStanJsonRPC\Tests\Rules\Fixtures;

use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;

// 这个类不继承 BaseProcedure，应该被规则忽略
#[MethodTag(name: 'test')]
#[MethodDoc(summary: 'Non Procedure', description: 'This class should be ignored')]
#[MethodExpose(method: 'test.nonProcedure')]
class NonProcedureClass
{
    public function execute(): array
    {
        return ['result' => 'test'];
    }
}
