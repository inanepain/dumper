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
 * @license https://github.com/inanepain/polyfill/raw/develop/UNLICENSE UNLICENSE
 *
 * @version $Id$
 * $Date$
 */

declare(strict_types=1);

if (!function_exists('dd')) {
    /**
     * Dumper shortcut
     *
     * options:
     *  - (bool=false) open        : true - creates dumps open (main panel not effect)
     *  - (bool=false) useVarExport: true - uses `var_export` instead of dumper to generate dump string
     *
     * @param mixed $data
     * @param string|null $label
     * @param array $options
     *
     * @return \Inane\Dumper\Dumper
     */
    function dd(mixed $data = null, ?string $label = null, array $options = []): \Inane\Dumper\Dumper {
        return \Inane\Dumper\Dumper::dump($data, $label, $options);
    }
}
