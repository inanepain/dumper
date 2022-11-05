<?php

/**
 * This file is part of the Inane suite of tools.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * PHP version 8.1
 *
 * @author Philip Michael Raab <peep@inane.co.za>
 * @package Inane\Dumper
 *
 * @license MIT
 * @license https://inane.co.za/license/MIT
 *
 * @version $Id$
 * $Date$
 */

declare(strict_types=1);

/**
 * Global namespace
 */

namespace {
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

        if (!isset($GLOBALS['dd'])) $GLOBALS['dd'] = \Inane\Dumper\Dumper::dump(...);
    } else {
        \Inane\Dumper\Dumper::dump('Dumper creates the global `dd` function for you. Just call `Dumper::dumper()` in you entry point. If this is taken try $dd().', 'Dumper: NOTICE: function: dd');
        if (!isset($GLOBALS['dd'])) $GLOBALS['dd'] = \Inane\Dumper\Dumper::dump(...);
    }
}

/**
 * Inane\Dumper namespace
 */

namespace Inane\Dumper {

    use Inane\Stdlib\Highlight;
    use Inane\Stdlib\Parser\ObjectParser;
    use ReflectionClass;

    use const PHP_EOL;
    use const true;
    use const false;

    use function array_combine;
    use function array_shift;
    use function basename;
    use function count;
    use function debug_backtrace;
    use function file_get_contents;
    use function file;
    use function gettype;
    use function highlight_string;
    use function implode;
    use function in_array;
    use function ob_start;
    use function php_sapi_name;
    use function preg_match;
    use function str_replace;
    use function str_starts_with;
    use function trim;
    use function var_export;

    /**
     * Dumper
     *
     * A simple dump tool that neatly stacks its collapsed dumps on the bottom of the page.
     *
     * @version 1.8.0
     *
     * @todo: move the two rendering methods into their own classes. allow for custom renderers.
     *
     * @package Inane\Dumper
     */
    final class Dumper {
        /**
         * Dumper version
         */
        public const VERSION = '1.8.0';

        /**
         * Single instance of Dumper
         */
        private static Dumper $instance;

        /**
         * Colour codes for console
         */
        private static array $colour = [
            'B' => "\033[30m",
            'r' => "\033[31m",
            'g' => "\033[32m",
            'y' => "\033[33m",
            'b' => "\033[34m",
            'm' => "\033[35m",
            'c' => "\033[36m",
            'LG' => "\033[37m",
            'dg' => "\033[90m",
            'lr' => "\033[91m",
            'lg' => "\033[92m",
            'ly' => "\033[93m",
            'lb' => "\033[94m",
            'lm' => "\033[95m",
            'lc' => "\033[96m",
            'w' => "\033[97m",
            'e' => "\033[0m",
        ];

        /**
         * Set to false to stop dumper writing to page. instant quiet.
         * PS: this effect manual calls to write dumps as well.
         */
        public static bool $enabled = true;

        /**
         * Render with dumper expander
         *
         * @since 1.8.0
         */
        public static bool $expanded = false;

        /**
         * Use php's var_export to generate dump
         *
         * By default Dumper parses variables itself,
         *  you can however have it use `var_export` instead
         *  by setting this to `true`
         *
         */
        public static bool $useVarExport = false;

        /**
         * Buffer dumps until end of process
         */
        public static bool $bufferOutput = true;

        /**
         * Colours used for display
         */
        public static Highlight $highlight = Highlight::CURRENT;

        /**
         * The collected dumps
         */
        protected static array $dumps = [];

        /**
         * Running in console
         *
         * @return bool
         */
        protected static function isCli(): bool {
            return (php_sapi_name() === 'cli');
        }

        /**
         * Private Dumper constructor
         */
        private function __construct() {
        }

        /**
         * Get Dumper's instance
         *
         * @return static
         */
        public static function dumper(): static {
            if (!isset(static::$instance)) static::$instance = new static();
            return static::$instance;
        }

        /**
         * When destroyed the dumps get written to page
         */
        public function __destruct() {
            if (static::$enabled && count(static::$dumps) > 0) {
                ob_start();
                // echo static::$instance->render();
                echo $this->render();
            }
        }

        /**
         * With Args: Add a dump to the collection
         * OR
         * Without Args: Write current dumps to page
         *
         * @param mixed $data item to dump
         * @param null|string $label
         * @param array $options
         *
         * @return Dumper
         */
        public function __invoke(mixed $data = null, ?string $label = null, array $options = []): static {
            return static::dump($data, $label, $options);
        }

        /**
         * Prepare the string for writing to page
         *
         * @return string
         */
        protected function render(array $options = []): string {
            // Check for command line
            if (static::isCli()) {
                $c = (object) static::$colour;

                $code = implode("{$c->y}==========================================================================={$c->e} \n", static::$dumps);
                static::$dumps = [];
                return "{$c->m}DUMPER{$c->e}\n{$code}";
            }

            $code = implode("\n", static::$dumps);
            static::$dumps = [];

            $style = file_get_contents(__DIR__ . '/../css/dumper.css');

            $open = static::$expanded ? ' open' : '';

            return <<<DUMPER_HTML
<style id="inane-dumper-style">{$style}</style>
<div class="dumper">
    <details class="dumper-window"{$open}>
        <summary class="dumper-title">dumper</summary>
        <div class="dumper-body">{$code}</div>
    </details>
</div>
DUMPER_HTML;
        }

        /**
         * Create a label for the dump with relevant information
         *
         * @param string|null $label
         *
         * @return string|null If Attribute Silence true return null
         */
        protected static function formatLabel(?string $label = null, string $type = null): ?string {
            $backtrace = debug_backtrace();
            // backtracking dump point of origin
            $i = -1;
            foreach ($backtrace as $t) {
                $i++;
                if (!in_array(basename($t['file']), ['Dumper.php', 'index.php'])) break;
            }

            $_data = [];
            // backtrace to file
            $bt_file = $backtrace[$i];
            $_data['file'] = $bt_file['file'] ?? '';
            $_data['line'] = $bt_file['line'] ?? '';

            // backtrace to object
            if (($i) < @count($backtrace)) {
                $bt_object = @$backtrace[++$i];
                $_data['class'] = $bt_object['class'] ?? false;
                $_data['function'] = $bt_object['function'] ?? '';
            } else $_data['class'] = false;

            // checking classes/functions for Silence attribute
            if ($_data['class'] != false) {
                $r_class = new ReflectionClass($_data['class']);
                $attributes = $r_class->getAttributes(Silence::class);
                if (count($attributes) > 0 && ($attributes[0]->newInstance())()) return null;

                if ($_data['function'] != false) {
                    $r_method = $r_class->getMethod($_data['function']);
                    $attribs = $r_method->getAttributes(Silence::class);
                    if (count($attribs) > 0 && ($attribs[0]->newInstance())()) return null;
                }
            }

            $label = isset($label) ? "{$label} [{$type}]" : $type;

            // CHECK CONSOLE
            if (static::isCli()) {
                $c = (object) static::$colour;

                $title = isset($label) ? "{$c->b} {$label}:{$c->e} " : '';
                $file = "{$c->w}{$_data['file']}{$c->e}::{$c->r}{$_data['line']}{$c->e}";
                $class = $_data['class'] ? " => {$c->y}{$_data['class']}::{$_data['function']}{$c->e}" : '';
            } else {
                // HTML
                $title = isset($label) ? "<strong class=\"dump-label\">{$label}</strong> " : '';
                $file = "{$_data['file']}::<strong>{$_data['line']}</strong>";
                $class = $_data['class'] ? " => {$_data['class']}::<strong>{$_data['function']}</strong>" : '';
            }

            return "{$title}{$file}{$class}" . PHP_EOL;
        }

        /**
         * Return information on variable
         *
         * @since 1.5.0
         *
         * @param mixed $v variable to query
         *
         * @return array info
         */
        protected static function analyseVariable($v): array {
            $i = -1;
            $trace = debug_backtrace();
            foreach ($trace as $t) {
                $i++;
                if (!in_array(basename($t['file']), ['Dumper.php'])) break;
            }

            $file = file($trace[$i]['file']);
            $id = $trace[$i]['line'] - 1;
            $line = $file[$id];
            preg_match('/(?:\()(\\$(\w+))/', $line, $match);

            if (count($match) == 0) $match = ['', ''];
            else if (count($match) == 3) array_shift($match);

            $result = array_combine(['variable', 'name'], $match);
            $result['type'] = gettype($v);

            return $result;
        }

        /**
         * Add a dump to the collection
         *
         * @param mixed $data item to dump
         * @param null|string $label
         * @param array $options
         *
         * @return void
         */
        protected function addDump(mixed $data, ?string $label = null, array $options = []): void {
            // Parse the variable to string
            $code = ($options['useVarExport'] ?? static::$useVarExport) ? var_export($data, true) : ObjectParser::parse($data);

            $bufferMessage = static::$bufferOutput;

            // CHECK CONSOLE
            if (static::isCli()) $output = "{$label}{$code}" . PHP_EOL;
            else {
                // HTML
                $highlight = $options['highlight'] ?? static::$highlight;
                $code = $highlight->render($code);

                $open = ($options['open'] ?? false) ? 'open' : '';

                $output = <<<DUMPER_HTML
<div class="dump">
    <details class="dump-window"{$open}>
        <summary>{$label}</summary>
        <code>{$code}</code>
    </details>
</div>
DUMPER_HTML;
            }

            if ($bufferMessage) static::$dumps[] = $output;
            else {
                $c = (object) static::$colour;
                echo "\t\t{$c->m}DUMPER{$c->e}:{$output}" . PHP_EOL;
            }
        }

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
         * @param mixed $data item to dump
         * @param null|string $label
         * @param array $options
         *
         * @return Dumper
         */
        public static function dump(mixed $data = null, ?string $label = null, array $options = []): static {
            $info = static::analyseVariable($data);
            if (is_null($label) && $info['variable'] != '') $label = $info['variable'];

            $label = static::formatLabel($label, $info['type']);
            if (!is_null($label)) static::dumper()->addDump($data, $label, $options);

            return static::dumper();
        }
    }
}
