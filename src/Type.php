<?php

/**
 * Inane: Dumper
 *
 * A little tool to help with debugging by writing a `var_dump` like message unobtrusively into a collapsible panel at the bottom of a page.
 *
 * $Id$
 * $Date$
 *
 * PHP version 8.4
 *
 * @author Philip Michael Raab<philip@cathedral.co.za>
 * @package inanepain\dumper
 * @category dumper
 *
 * @license UNLICENSE
 * @license https://unlicense.org/UNLICENSE UNLICENSE
 *
 * _version_ $version
 */

declare(strict_types=1);

namespace Inane\Dumper;

/**
 * Dumper: Dump Type
 *
 * This is mostly for Dumper debugging or some other self amusing task.
 * Dumps are typed in Dumper with the normal dump being `Type::Dump`, which is always allowed / written to output.
 *
 * The Silence Attribute logs its progress using Dumper with dumps typed as `Type::Silence`. These are not allowed by default, not written to output.
 * To enable logging of a non-default type add it to `Dumper::$additionalTypes`. E.g.: `Dumper::$additionalTypes[] = Type::Silence;`
 * This enables the `Type::Silence` dumping which adds silence tests to the dump log.
 * To reset the allowed types simple set it to a blank array. e.g.: Dumper::$additionalTypes = [];
 *
 * @version 0.16.0
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
    /**
     * TODO dump not shown by default
     */
    case Todo;
}
