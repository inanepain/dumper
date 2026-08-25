<?php

declare(strict_types=1);

namespace Inane\Dumper\Tests;

use Inane\Dumper\Type;
use PHPUnit\Framework\TestCase;

final class TypeTest extends TestCase {
    public function testDefinesAllSupportedDumpTypes(): void {
        $this->assertSame([Type::Dump, Type::Silence, Type::Todo], Type::cases());
    }
}