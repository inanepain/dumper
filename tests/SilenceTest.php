<?php

declare(strict_types=1);

namespace Inane\Dumper\Tests;

use Inane\Dumper\Silence;
use PHPUnit\Framework\TestCase;

final class SilenceTest extends TestCase {
    public function testAliasesAndOptionsReflectTheConfiguredState(): void {
        $silence = new Silence(on: false, config: ['label' => 'request', 'colour' => 'blue']);

        $this->assertFalse($silence->on);
        $this->assertFalse($silence->silence);
        $this->assertFalse($silence->quiet);
        $this->assertTrue($silence->off);
        $this->assertTrue($silence->verbose);
        $this->assertSame('request', $silence->label);
        $this->assertSame('blue', $silence->colour);
        $this->assertNull($silence->unknown);
    }

    public function testLimitInvertsTheConfiguredStateAfterTheSpecifiedCalls(): void {
        $silence = new Silence(on: true, limit: 2);

        $this->assertTrue($silence());
        $this->assertTrue($silence());
        $this->assertFalse($silence());
    }

    public function testNonPositiveLimitDoesNotInvertTheConfiguredState(): void {
        $silence = new Silence(on: false, limit: 0);

        $this->assertFalse($silence());
        $this->assertFalse($silence());
    }
}