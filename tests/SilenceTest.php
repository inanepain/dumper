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

use Inane\Dumper\Silence;
use Inane\Stdlib\Exception\RuntimeException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * Silence test
 */
final class SilenceTest extends TestCase {
    /**
     * Tests that state aliases and config options are exposed as magic properties
     *
     * @return void
     *
     * @throws ExpectationFailedException
     */
    public function testAliasesAndOptionsReflectTheConfiguredState(): void {
        $silence = new Silence(on: false, config: ['label'  => 'request',
                                                   'colour' => 'blue',
        ]);

        // `silence` and `quiet` mirror `on`, while `off` and `verbose` invert it.
        $this->assertFalse($silence->on);
        $this->assertFalse($silence->silence);
        $this->assertFalse($silence->quiet);
        $this->assertTrue($silence->off);
        $this->assertTrue($silence->verbose);

        // Unknown names fall through to the config, defaulting to null.
        $this->assertSame('request', $silence->label);
        $this->assertSame('blue', $silence->colour);
        $this->assertNull($silence->unknown);
    }

    /**
     * Tests that a positive limit inverts the state once it is exhausted
     *
     * @return void
     *
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function testLimitInvertsTheConfiguredStateAfterTheSpecifiedCalls(): void {
        $silence = new Silence(on: true, limit: 2);

        // The first two invocations consume the limit.
        $this->assertTrue($silence());
        $this->assertTrue($silence());
        // Once spent, the state flips.
        $this->assertFalse($silence());
    }

    /**
     * Tests that a non-positive limit leaves the state unchanged
     *
     * @return void
     *
     * @throws RuntimeException
     * @throws ReflectionException
     */
    public function testNonPositiveLimitDoesNotInvertTheConfiguredState(): void {
        $silence = new Silence(on: false, limit: 0);

        $this->assertFalse($silence());
        $this->assertFalse($silence());
    }
}
