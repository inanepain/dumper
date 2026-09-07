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

use Inane\Dumper\Type;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Type test
 */
final class TypeTest extends TestCase {
    /**
     * Tests that the enum declares the supported dump types in order
     *
     * @return void
     *
     * @throws ExpectationFailedException
     */
    public function testDefinesAllSupportedDumpTypes(): void {
        // Order matters as cases are relied on for presentation.
        $this->assertSame([
            Type::Dump,
            Type::Silence,
            Type::Todo,
        ], Type::cases());
    }
}
