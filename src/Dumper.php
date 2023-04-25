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
use ReflectionFunction;

use function array_combine;
use function array_shift;
use function basename;
use function count;
use function debug_backtrace;
use function dirname;
use function file;
use function file_get_contents;
use function function_exists;
use function gettype;
use function implode;
use function in_array;
use function is_null;
use function is_string;
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
 * @version 1.13.1
 *
 * @todo: move the two rendering methods into their own classes. allow for custom renderers.
 *
 * @package Inane\Dumper
 */
final class Dumper {
    /**
     * Dumper version
     */
    public const VERSION = '1.12.0';

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
     * Additional Dump Types allowed
     *
     * @var \Inane\Dumper\Type[]
     */
    public static array $additionalTypes = [];

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
     * Checks if Dumper can register/create global functions
     *
     * A wrapper method that returns true if any method is available.
     *  Currently, there is only one **Runkit7**, but I will look into adding more if requested.
	 * 
	 * @since 1.12.0
     *
     * @return bool
     */
    public static function canRegisterFunctions(): bool {
        return static::hasRunkit7();
    }

    /**
     * Checks for runkit7 ext
	 * 
	 * @since 1.12.0
     *
     * @return bool
     */
    protected static function hasRunkit7(): bool {
        return function_exists('runkit7_function_add');
    }

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
	 * @since 1.12.0 dump instruction on installing **runkit7** to enable creation of custom alias functions. Shown when custom alias requested and no runkit7.
     *
     * @param null|string $dumpAlias 	[optional] register a global variable shortcut function with name $dumpAlias for `dump`. Shortcut must also be a valid variable/function name.
     * @param null|string $assertAlias	[optional] register a global variable shortcut function with name $assertAlias for `assert`. Shortcut must also be a valid variable/function name.
     *
     * @return \Inane\Dumper\Dumper
     */
    public static function dumper(?string $dumpAlias = null, ?string $assertAlias = null): Dumper {
        if (!isset(Dumper::$instance)) Dumper::$instance = new Dumper();

		if (static::canRegisterFunctions()) {
			if (!is_null($dumpAlias) && !isset($GLOBALS[$dumpAlias]) && preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $dumpAlias))
				runkit7_function_add($dumpAlias, '$data = null,$label = null,$options = []', 'return \Inane\Dumper\Dumper::dump($data, $label, $options);');

			if (!is_null($assertAlias) && !isset($GLOBALS[$assertAlias]) && preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $assertAlias))
				runkit7_function_add($assertAlias, '$expression,$data = null,$label = null,$options = []', 'return \Inane\Dumper\Dumper::assert($expression, $data, $label, $options);');
		} else Dumper::dump('pecl install runkit7-alpha', 'Enable creating global functions at runtime.');

        if (!is_null($dumpAlias) && !isset($GLOBALS[$dumpAlias]) && preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $dumpAlias))
            $GLOBALS[$dumpAlias] = Dumper::dump(...);
        
		if (!is_null($assertAlias) && !isset($GLOBALS[$assertAlias]) && preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $assertAlias))
            $GLOBALS[$assertAlias] = Dumper::assert(...);

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
	 * @since 1.13.0 Supports `Attribute::TARGET_FUNCTION`
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
		$classInstance = null;
        if (($data->class && Dumper::$silences->has($data->class)) || ($data->class == false && Dumper::$silences->has($data->function)))
			$classInstance = Dumper::$silences->get($data->class === false ? $data->function : $data->class)->instance;
		else if ($data->class || ($data->class === false && is_string($data->function) && function_exists($data->function))) {
			if ($data->class === false) $class = new ReflectionFunction($data->function);
			else $class = new ReflectionClass($data->class);

			$attributes = $class->getAttributes(Silence::class);
			$classInstance = count($attributes) > 0 ? $attributes[0]->newInstance() : null;

			Dumper::$silences->set($data->class === false ? $data->function : $data->class, [
				'object' => $class,
				'instance' => $classInstance,
				'methods' => [],
			]);
		}

		// Check Silence Attribute value and return of silence is on
		if (($classInstance && $classInstance()) || ($data->class && Dumper::getMethodSilence($data))) return null;

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
     * @param mixed                       $data    item to dump
     * @param null|string                 $label
     * @param array|\Inane\Stdlib\Options $options
     *
     * @return void
     */
    protected function addDump(mixed $data, ?string $label = null, array|Options $options = []): void {
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
    public static function assert(bool $expression, mixed $data = null, ?string $label = null, array|Options $options = []): Dumper {
        if (!$expression) return Dumper::dump($data, $label, $options);

        return Dumper::dumper();
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
     * @param mixed                       $data    item to dump
     * @param null|string                 $label
     * @param array|\Inane\Stdlib\Options $options
     *
     * @return \Inane\Dumper\Dumper
     *
     * @throws \Inane\Stdlib\Exception\RuntimeException
     * @throws \ReflectionException
     */
    public static function dump(mixed $data = null, ?string $label = null, array|Options $options = []): Dumper {
        Dumper::dumper();

        $params = new Options([
            'open'         => false,
            'useVarExport' => false,
            'type'         => Type::Dump,
        ]);
        $params->modify($options);
        $params->lock();

        if ($params->type == Type::Dump || in_array($params->type, Dumper::$additionalTypes)) {
            $info = Dumper::analyseVariable($data);
            if (is_null($label) && $info['variable'] != '') $label = $info['variable'];
            if ($params->type == Type::Dump) $label = Dumper::formatLabel($label, $info['type']);
            if (!is_null($label)) Dumper::dumper()->addDump($data, $label, $params);
        }

        return Dumper::dumper();
    }
}
