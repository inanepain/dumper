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
 * @version $Id$
 * $Date$
 */

declare(strict_types=1);

namespace Inane\Dumper;

use ReflectionClass;

use function array_combine;
use function array_shift;
use function basename;
use function count;
use function debug_backtrace;
use function dirname;
use function file;
use function file_get_contents;
use function gettype;
use function implode;
use function in_array;
use function is_null;
use function ob_start;
use function php_sapi_name;
use function preg_match;
use function str_ends_with;
use function str_replace;
use function var_export;
use const false;
use const PHP_EOL;
use const true;

use Inane\Stdlib\{
    Parser\ObjectParser,
    Highlight,
    Options
};

/**
 * Dumper
 *
 * A simple dump tool that neatly stacks its collapsed dumps on the bottom of the page.
 *
 * @version 1.10.0
 *
 * @todo: move the two rendering methods into their own classes. allow for custom renderers.
 *
 * @package Inane\Dumper
 */
final class Dumper {
    /**
     * Dumper version
     */
    public const VERSION = '1.10.0';

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
     * Enable or Disable Dumper
     *
     * true: normal functionality of Dumper
     * false: stops dumper writing to page. instant quiet.
     *
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
     * default: Dumper parses variables itself,
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
     * Instantiated Silences
     *
     * @since 1.10.0
     */
    protected static Options $silences;

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
        // echo __METHOD__;
        if (!isset(Dumper::$silences)) Dumper::$silences = new Options();
    }

    /**
     * Get Dumper's instance
     *
     * register shortcut `what`:
     *  ::dumper('what');
     *
     * using `what`:
     *  $what($something, 'Dumped using shortcut `what`');
     *
     * @param null|string $shortcut [optional] register a global variable shortcut function with name $shortcut. Shortcut must also be a valid variable/function name.
     *
     * @return \Inane\Dumper\Dumper
     */
    public static function dumper(?string $shortcut = null): Dumper {
        if (!isset(Dumper::$instance)) Dumper::$instance = new Dumper();

        if (!is_null($shortcut) && !isset($GLOBALS[$shortcut]) && preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $shortcut))
            $GLOBALS[$shortcut] = Dumper::dump(...);

        return Dumper::$instance;
    }

    /**
     * When destroyed the dumps get written to page
     */
    public function __destruct() {
        if (Dumper::$enabled && count(Dumper::$dumps) > 0) {
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
     * @param mixed       $data item to dump
     * @param null|string $label
     * @param array       $options
     *
     * @return \Inane\Dumper\Dumper
     *
     * @throws \Inane\Stdlib\Exception\RuntimeException
     * @throws \ReflectionException
     */
    public function __invoke(mixed $data = null, ?string $label = null, array $options = []): Dumper {
        return Dumper::dump($data, $label, $options);
    }

    /**
     * Prepare the string for writing to page
     *
     * @return string
     */
    protected function render(): string {
        // Check for command line
        if (Dumper::isCli()) {
            $c = (object) Dumper::$colour;

            $code = implode("$c->y===========================================================================$c->e \n", Dumper::$dumps);
            Dumper::$dumps = [];
            return "{$c->m}DUMPER$c->e\n$code";
        }

        $code = implode("\n", Dumper::$dumps);
        Dumper::$dumps = [];

        $style = file_get_contents(__DIR__ . '/../css/dumper.css');

        $open = Dumper::$expanded ? ' open' : '';

        return <<<DUMPER_HTML
<style id="inane-dumper-style">$style</style>
<div class="dumper">
<details class="dumper-window"$open>
    <summary class="dumper-title">dumper</summary>
    <div class="dumper-body">$code</div>
</details>
</div>
DUMPER_HTML;
    }

    /**
     * Filters `debug_backtrace` for the information relevant to Dumper
     *
     * @since 1.9.1
     *
     * @return array Dumper required trace data
     */
    private static function getTrace(): array {
        $i = -1;
        $trace = debug_backtrace();

        foreach ($trace as $t) {
            $i++;
            $file = str_replace('\\', '/', $t['file']);
            $dir = dirname($file);
            if (!str_ends_with($dir, 'dumper/src') && !in_array(basename($file), ['Dumper.php', 'bootstrap.php'])) break;
        }

        return [
            $trace[$i],
            $trace[$i + 1] ?? null
        ];
    }

    /**
     * Get method silence state
     *
     * @since 1.10.0
     *
     * @param \Inane\Stdlib\Options $backtrace
     *
     * @return bool silence state
     *
     * @throws \Inane\Stdlib\Exception\RuntimeException
     */
    private static function getMethodSilence(Options $backtrace): bool {
        $class = Dumper::$silences->get($backtrace->class)->object;
        $instance = null;

        if (Dumper::$silences->get($backtrace->class)->methods->has($backtrace->function))
            $instance = Dumper::$silences->get($backtrace->class)->methods->get($backtrace->function)->instance;
        else if ($backtrace->function) {
            $attributes = $class->getMethod($backtrace->function)->getAttributes(Silence::class);
            $instance = count($attributes) > 0 ? $attributes[0]->newInstance() : null;

            Dumper::$silences->get($backtrace->class)->methods->set($backtrace->function, [
                'instance' => $instance,
            ]);
        }

        return $instance && $instance();
    }

    /**
     * Create a label for the dump with relevant information
     *
     * @param string|null $label
     * @param string|null $type
     *
     * @return string|null If Attribute Silence true return null
     *
     * @throws \Inane\Stdlib\Exception\RuntimeException
     * @throws \ReflectionException
     */
    protected static function formatLabel(?string $label = null, string $type = null): ?string {
        [$src, $obj] = Dumper::getTrace();

        $data = new Options();
        $data->file = $src['file'] ?? '';
        $data->line = $src['line'] ?? '';

        if (!is_null($obj)) {
            $data->class = $obj['class'] ?? false;
            $data->function = $obj['function'] ?? '';
        } else $data->class = false;

        // checking classes/functions for Silence attribute
        if ($data->class) {
            if (Dumper::$silences->has($data->class)) {
                $classInstance = Dumper::$silences->get($data->class)->instance;
            } else {
                $class = new ReflectionClass($data->class);
                $attributes = $class->getAttributes(Silence::class);
                $classInstance = count($attributes) > 0 ? $attributes[0]->newInstance() : null;

                Dumper::$silences->set($data->class, [
                    'object' => $class,
                    'instance' => $classInstance,
                    'methods' => [],
                ]);
            }
            if (($classInstance && $classInstance()) || Dumper::getMethodSilence($data)) return null;
        }

        $label = isset($label) ? "$label [$type]" : $type;

        // CHECK CONSOLE
        if (Dumper::isCli()) {
            $c = (object) Dumper::$colour;

            $title = isset($label) ? "$c->b $label:$c->e " : '';
            $file = "$c->w$data->file$c->e::$c->r$data->line$c->e";
            $class = $data->class ? " => $c->y$data->class::$data->function$c->e" : '';
        } else {
            // HTML
            $title = isset($label) ? "<strong class=\"dump-label\">$label</strong> " : '';
            $file = "$data->file::<strong>$data->line</strong>";
            $class = $data->class ? " => $data->class::<strong>$data->function</strong>" : '';
        }

        return "$title$file$class" . PHP_EOL;
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
    protected static function analyseVariable(mixed $v): array {
        $trace = Dumper::getTrace()[0];
        $file = file($trace['file']);
        $id = $trace['line'] - 1;
        $line = $file[$id];
        preg_match('/\((\\$(\w+))/', $line, $match);

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
        $code = ($options['useVarExport'] ?? Dumper::$useVarExport) ? var_export($data, true) : ObjectParser::parse($data);

        $bufferMessage = Dumper::$bufferOutput;

        // CHECK CONSOLE
        if (Dumper::isCli()) $output = "$label$code" . PHP_EOL;
        else {
            // HTML
            $highlight = $options['highlight'] ?? Dumper::$highlight;
            $code = $highlight->render($code);

            $open = ($options['open'] ?? false) ? 'open' : '';

            $output = <<<DUMPER_HTML
<div class="dump">
<details class="dump-window"$open>
    <summary>$label</summary>
    <code>$code</code>
</details>
</div>
DUMPER_HTML;
        }

        if ($bufferMessage) Dumper::$dumps[] = $output;
        else {
            $c = (object) Dumper::$colour;
            echo "\t\t{$c->m}DUMPER$c->e:$output" . PHP_EOL;
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
     * @param mixed       $data item to dump
     * @param null|string $label
     * @param array       $options
     *
     * @return \Inane\Dumper\Dumper
     *
     * @throws \Inane\Stdlib\Exception\RuntimeException
     * @throws \ReflectionException
     */
    public static function dump(mixed $data = null, ?string $label = null, array $options = []): Dumper {
        $info = Dumper::analyseVariable($data);
        if (is_null($label) && $info['variable'] != '') $label = $info['variable'];

        $label = Dumper::formatLabel($label, $info['type']);
        if (!is_null($label)) Dumper::dumper()->addDump($data, $label, $options);

        return Dumper::dumper();
    }

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
     * @param bool        $expression true suppress dump, false dump $data
     * @param mixed       $data       item to dump
     * @param null|string $label
     * @param array       $options
     *
     * @return \Inane\Dumper\Dumper
     *
     * @throws \Inane\Stdlib\Exception\RuntimeException
     * @throws \ReflectionException
     */
    public static function assert(bool $expression, mixed $data = null, ?string $label = null, array $options = []): Dumper {
        if (!$expression) return Dumper::dump($data, $label, $options);

        return Dumper::dumper();
    }
}
