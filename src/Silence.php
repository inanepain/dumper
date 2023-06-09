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
use Inane\Stdlib\Options;

use function in_array;
use function is_null;
use function is_string;
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
 * See Silence Tests:
 * To have dumper log each time it does a Silence test you need to enable dumping of the Silence Type.
 * Silence tests stand out from your normal dumps but they can quickly add up.
 * They are only useful in understanding limit values in more complicated situations.
 * If you do need them, add just before and remove just after code you are checking.
 * 
 * `\Inane\Dumper\Dumper::$additionalTypes[] = \Inane\Dumper\Type::Silence;`
 * 
 * Remove Silence Test Logging
 * `\Inane\Dumper\Dumper::$additionalTypes = [];`
 * 
 * @since 1.4.0 Supports `Attribute::TARGET_FUNCTION`
 *
 * @property-read bool $on      true if dump skipped
 * @property-read bool $silent  true if dump skipped
 * @property-read bool $quiet   true if dump skipped
 * 
 * @property-read bool $off     true if dump written
 * @property-read bool $verbose true if dump written
 *
 * @property-read ?string $label set to write Silence invocation data to page
 * @property-read string $colour for label
 *
 * @version 1.4.0
 *
 * @package Inane\Dumper
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
class Silence {
    /**
     * Counter increments every time instance is checked
     *
     * @since 1.1.0
     */
    private int $counter = 0;

    /**
     * Debug & other esoteric options to configure Silence in strange ways
     *
     * options:
     *  - label => debug label when Silence invoked showing counter and state
     *  - colour => of label
     */
    private Options $options;

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
		 * null|<=0 are equal, all disable limit.
         *
         * @since 1.1.0
		 * 
		 * @todo: change to null|int|int[offset, count] - offset = toggle value, count = toggle back to initial value.
         */
        public readonly ?int $limit = null,
        /**
         * Debug & other esoteric options to configure Silence in strange ways
         */
        null|Options|array $config = null,
    ) {
        $this->options = new Options([
            'label' => null,
            'colour' => 'grey',
        ]);

        if (!is_null($config)) {
            $this->options->modify($config);
            if (!is_string($this->options->colour)) $this->options->colour = 'grey';
        }
    }

    /**
     * get property
     *
     * These do NOT invoke the object and do NOT increment the counter.
     * valid (silent):
     *  - on
     *  - silence
     *  - quiet
     * valid (verbose):
     *  - off
     *  - verbose
     *
     * debug:
     *  - label
     *  - colour
     *
     * @since 1.1.0
     *
     * @param string $name property
     *
     * @return null|bool|string state, label, colour or null for invalid property
     */
    public function __get(string $name): null|bool|string {
        if (in_array($name, ['on', 'silence', 'quiet']))
            return $this->on;
        else if (in_array($name, ['off', 'verbose']))
            return !$this->on;
        else if (in_array($name, ['colour', 'label']))
            return $this->options[$name];
        return null;
    }

    /**
     * Silence State
     *
     * @return bool true is silent, false dump
     *
     * @throws \Inane\Stdlib\Exception\RuntimeException
     * @throws \ReflectionException
     */
    public function __invoke(): bool {
        $this->counter++;
        $result = $this->on;

        // If a limit has been set and reached the return state is inverted
        if (!is_null($this->limit) && $this->limit > 0 && $this->counter > $this->limit)
            $result = !$this->on;

        // If a label value has been set, the Silence check is registered in the dump list
        if (is_string($this->label)) {
            $options = new Options([
                'type' => Type::Silence,
            ]);

            Dumper::dump([
                'silence' => $result,
                'counter' => $this->counter,
                'limit' => $this->limit,
            ], "Silence: <span style=\"color: $this->colour;\">$this->label</span>", $options);
        }

        return $result;
    }
}
