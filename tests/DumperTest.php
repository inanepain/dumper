<?php

/**
 * Inane: Dumper
 *
 * A little tool to help with debugging by writing a `var_dump` like message unobtrusively into a collapsible panel at the bottom of a page.
 *
 * $Id$
 * $Date$
 *
 * PHP version 8.5
 *
 * @author   Philip Michael Raab<philip@cathedral.co.za>
 * @package  inanepain\dumper
 * @category dumper
 *
 * @license  UNLICENSE
 * @license  https://unlicense.org/UNLICENSE UNLICENSE
 *
 * _version_ $version
 */

declare(strict_types = 1);

namespace Inane\Dumper\Tests;

use Inane\Dumper\Dumper;
use Inane\Stdlib\Exception\ReflectionException;
use Inane\Stdlib\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

/**
 * Dumper test
 */
final class DumperTest extends TestCase {
    /**
     * Tests that console colours can be partially overridden and switched off entirely
     *
     * @return void
     *
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function testConsoleColoursCanBeCustomisedAndDisabled(): void {
        // Dumper state is static, so capture it for restoration.
        $originalColours = Dumper::getConsoleColours();
        $originalShowRunkit7SupportMessage = Dumper::$showRunkit7SupportMessage;
        Dumper::$showRunkit7SupportMessage = false;

        try {
            // A partial colour set is merged, leaving untouched keys as they were.
            $this->assertInstanceOf(Dumper::class, Dumper::setConsoleColours(['label' => 'custom']));
            $this->assertSame('custom', Dumper::getConsoleColours()['label']);
            $this->assertSame($originalColours['reset'], Dumper::getConsoleColours()['reset']);

            // Disabling colours blanks every entry.
            Dumper::setConsoleColors(false);

            $this->assertSame([
                'reset'   => '',
                'dumper'  => '',
                'label'   => '',
                'file'    => '',
                'line'    => '',
                'divider' => '',
            ], Dumper::getConsoleColours());
        } finally {
            // Always hand the shared static state back untouched.
            Dumper::setConsoleColours($originalColours);
            Dumper::$showRunkit7SupportMessage = $originalShowRunkit7SupportMessage;
        }
    }
}
