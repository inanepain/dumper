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

use Attribute;

use function in_array;
use function is_null;
use const null;
use const true;

/**
 * Silence
 *
 * Attribute to silence Dumper for a class or/then method<br/>
 *
 * Silence priority, higher level only filter down if not silent:
 * - Dumper enabled
 * - Class Silence => false / No Silence Attribute
 * - Method Silence
 *
 * @property-read bool $on      true if dump skipped
 * @property-read bool $silent  true if dump skipped
 * @property-read bool $quiet   true if dump skipped
 * @property-read bool $off     true if dump written
 * @property-read bool $verbose true if dump written
 *
 * @version 1.1.0
 *
 * @package Inane\Dumper
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Silence {
    /**
     * Counter increments every time instance is checked
     *
     * @since 1.1.0
     */
    private int $counter = 0;

    /**
     * Silence
     *
     * @param bool $on enable silence
     *
     * @return void
     */
    public function __construct(
        /**
         * When `on` Silence prevents Dumper from logging
         */
        public readonly bool $on = true,
        /**
         * Toggle state after $limit is reached
         *
         * @since 1.1.0
         */
        public readonly ?int $limit = null,
        /**
         * Friendly name
         */
        // public readonly string $label = '',
        /**
         * Colour for debug text
         */
        // public readonly string $colour = 'grey',
    ) {
        // echo __METHOD__;
    }

    /**
     * get property
     *
     * valid (silent):
     *  - on
     *  - silence
     *  - quiet
     * valid (verbose):
     *  - off
     *  - verbose
     *
     * @since 1.1.0
     *
     * @param string $name property
     *
     * @return null|bool state or null for invalid property
     */
    public function __get(string $name): ?bool {
        if (in_array($name, ['on', 'silence', 'quiet']))
            return $this->on;
        else if (in_array($name, ['off', 'verbose']))
            return !$this->on;
        return null;
    }

    /**
     * Silence State
     *
     * @return bool true is silent, false dump
     */
    public function __invoke(): bool {
        $this->counter++;
        // echo '<div style="border-bottom: 1px dotted black; width: fit-content; color: ' . $this->colour . '; font-size: small; margin: 3px;"><span style="min-width: 130px; display: inline-block; padding: 3px">', $this->label, '</span>: ', $this->counter, '</div>';

        if (!is_null($this->limit) && $this->limit > 0 && $this->counter > $this->limit)
            return !$this->on;

        return $this->on;
    }
}
