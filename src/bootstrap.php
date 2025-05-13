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
 * @version 0.16.0
 *
 * $Id$
 * $Date$
 */

declare(strict_types=1);

use Inane\Dumper\Dumper;
use Inane\Stdlib\Options;

if (!function_exists('dd')) {
    /**
     * Add a dump to the collection
     *
     * options:
     *  - (bool=false) open        : true - creates dumps open (main panel not effect)
     *  - (bool=false) useVarExport: true - uses `var_export` instead of dumper to generate dump string
     *
     * Chaining: You only need bracket your arguments for repeated dumps.
     * Dumper::dump('one')('two', 'Label')
     *
     * @param mixed                       $data    item to dump
     * @param null|string                 $label
     * @param array|\Inane\Stdlib\Options $options
     *
     * @return \Inane\Dumper\Dumper
     *
     * @throws \Inane\Stdlib\Exception\RuntimeException
     * @throws \ReflectionException
     */
    function dd(mixed $data = null, ?string $label = null, array|Options $options = []): Dumper {
        return Dumper::dump($data, $label, $options);
    }
}

if (!function_exists('da')) {
    /**
     * Conditionally adds a dump to the collection
     *
     * options:
     *  - (bool=false) open        : true - creates dumps open (main panel not effect)
     *  - (bool=false) useVarExport: true - uses `var_export` instead of dumper to generate dump string
     *
     * Chaining: You only need bracket your arguments for repeated dumps.
     * Dumper::dump('one')('two', 'Label')
     *
     * @since 1.10.0
     *
     * @param bool                        $expression true suppress dump, false dump $data
     * @param mixed                       $data       item to dump
     * @param null|string                 $label
     * @param array|\Inane\Stdlib\Options $options
     *
     * @return \Inane\Dumper\Dumper
     *
     * @throws \Inane\Stdlib\Exception\RuntimeException
     * @throws \ReflectionException
     */
    function da(bool $expression, mixed $data = null, ?string $label = null, array|Options $options = []): Dumper {
        return Dumper::assert($expression, $data, $label, $options);
    }
}
