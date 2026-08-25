<?php

declare(strict_types=1);

namespace Inane\Dumper\Tests;

use Inane\Dumper\Dumper;
use PHPUnit\Framework\TestCase;

final class DumperTest extends TestCase {
    public function testConsoleColoursCanBeCustomisedAndDisabled(): void {
        $originalColours = Dumper::getConsoleColours();
        $originalShowRunkit7SupportMessage = Dumper::$showRunkit7SupportMessage;
        Dumper::$showRunkit7SupportMessage = false;

        try {
            $this->assertInstanceOf(Dumper::class, Dumper::setConsoleColours(['label' => 'custom']));
            $this->assertSame('custom', Dumper::getConsoleColours()['label']);
            $this->assertSame($originalColours['reset'], Dumper::getConsoleColours()['reset']);

            Dumper::setConsoleColors(false);

            $this->assertSame([
                'reset' => '',
                'dumper' => '',
                'label' => '',
                'file' => '',
                'line' => '',
                'divider' => '',
            ], Dumper::getConsoleColours());
        } finally {
            Dumper::setConsoleColours($originalColours);
            Dumper::$showRunkit7SupportMessage = $originalShowRunkit7SupportMessage;
        }
    }
}