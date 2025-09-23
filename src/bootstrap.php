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

use Inane\Dumper\Dumper;
use Inane\Stdlib\Array\OptionsInterface;
use Inane\Stdlib\Options;

if (!function_exists('dd')) {
    /**
     * Add a dump to the collection
     *
     * options:
	 *  - (bool=false) open        : true - creates dumps open (main panel not effect)
	 *  - (bool=false) useVarExport: true - uses `var_export` instead of dumper to generate dump string
	 *  - (Type=Dump) type         : Dump - set a custom type for the dump
	 *  - (int=ref) parseDepth     : set the depth to which an object is parsed
     *
     * Chaining: You only need bracket your arguments for repeated dumps.
     * Dumper::dump('one')('two', 'Label')
     *
     * @param mixed                                        $data    item to dump
     * @param null|string                                  $label
     * @param array|\Inane\Stdlib\Options|OptionsInterface $options
     *
     * @return \Inane\Dumper\Dumper
     *
     * @throws \Inane\Stdlib\Exception\RuntimeException
     * @throws \ReflectionException
     */
    function dd(mixed $data = null, ?string $label = null, array|Options|OptionsInterface $options = []): Dumper {
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
	 *  - (Type=Dump) type         : Dump - set a custom type for the dump
	 *  - (int=ref) parseDepth     : set the depth to which an object is parsed
     *
     * Chaining: You only need bracket your arguments for repeated dumps.
     * Dumper::dump('one')('two', 'Label')
     *
     * @since 1.10.0
     *
     * @param bool                           $expression true suppress dump, false dump $data
     * @param mixed                          $data       item to dump
     * @param null|string                    $label
     * @param array|Options|OptionsInterface $options
     *
     * @return \Inane\Dumper\Dumper
     *
     * @throws \Inane\Stdlib\Exception\RuntimeException
     * @throws \ReflectionException
     */
    function da(bool $expression, mixed $data = null, ?string $label = null, array|Options|OptionsInterface $options = []): Dumper {
        return Dumper::assert($expression, $data, $label, $options);
    }
}
