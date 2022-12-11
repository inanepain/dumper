<?php

/**
 * Inane: Dumper
 *
 * A little tool to help with debugging by writing a `var_dump` like message unobtrusively to a collapsible panel at the bottom of a page.
 *
 * PHP version 8.1
 *
 * @author Philip Michael Raab <peep@inane.co.za>
 * @package Inane\Dumper
 * @category debug
 *
 * @license UNLICENSE
 * @license https://github.com/inanepain/dumper/raw/develop/UNLICENSE UNLICENSE
 *
 * @copyright 2015-2021 Philip Michael Raab <philip@inane.co.za>
 */

declare(strict_types=1);

namespace Inane\Dumper;

/**
 * Dumper Dump Type
 *
 * @version 0.1.0
 *
 * @package Inane\Dumper
 */
enum Type {
    /**
     * Default: Standard dump type
     */
    case Dump;
    /**
     * Triggered by a Silence Attribute check
     */
    case Silence;
}
