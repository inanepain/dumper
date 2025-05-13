<?php

/**
 * Inane: Dumper
 *
 * A little tool to help with debugging by writing a `var_dump` like message unobtrusively to a collapsible panel at the bottom of a page.
 *
 * $Id$<br/>
 * $Date$
 *
 * PHP version 8.1
 *
 * @author Philip Michael Raab <peep@inane.co.za>
 * @package Inane\Dumper
 * @category debug
 *
 * @license UNLICENSE
 * @license https://unlicense.org/UNLICENSE UNLICENSE UNLICENSE
 *
 * @version 0.16.0
 */

declare(strict_types=1);

namespace Inane\Dumper;

use ReflectionClass;
use ReflectionFunction;

use function array_combine;
use function array_key_exists;
use function array_shift;
use function basename;
use function count;
use function debug_backtrace;
use function dirname;
use function exec;
use function file_get_contents;
use function file;
use function function_exists;
use function getenv;
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
use function strtoupper;
use function substr;
use function var_export;

use const false;
use const PHP_EOL;
use const PHP_OS;
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
 * @version 0.16.0
 *
 * @todo: move the two rendering methods into their own classes. allow for custom renderers.
 *
 * @package Inane\Dumper
 */
final class Dumper {
	/**
	 * Dumper version
	 */
	public const string VERSION = '1.16.0';

	/**
	 * Single instance of Dumper
	 */
	private static Dumper $instance;

	/**
	 * Colour codes for console
	 *
	 * @since 1.14.0
	 *
	 * @var array<string, string>
	 */
	private static array $consoleColours = [
		'reset' => "\033[0m",		# console default
		'dumper' => "\033[35m",		# magenta
		'label' => "\033[34m",		# blue
		'file' => "\033[97m",		# while
		'line' => "\033[31m",		# red
		'divider' => "\033[33m",	# yellow
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
	 * Show a message to install runkit7 if not found.
	 */
	public static bool $showRunkit7SupportMessage = true;

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
	 * Determines if the current operating system is Windows.
	 *
	 * @since 1.17.0
	 *
	 * @return bool Returns true if the operating system is Windows, false otherwise.
	 */
	private static function isWindows(): bool {
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
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
	 * Retrieves the number of columns available in the current environment.
	 *
	 * This method is typically used to determine the width of the output
	 * for formatting purposes.
	 *
	 * @since 1.17.0
	 *
	 * @return int The number of columns available.
	 */
	protected static function columns(): int {
		static $columns;

		if (getenv('PHP_CLI_TOOLS_TEST_SHELL_COLUMNS_RESET'))
			$columns = null;

		if (null === $columns) {
			if (function_exists('exec')) {
				if (self::isWindows()) {
					// Cater for shells such as Cygwin and Git bash where `mode CON` returns an incorrect value for columns.
					if (($shell = getenv('SHELL')) && preg_match('/(?:bash|zsh)(?:\.exe)?$/', $shell) && getenv('TERM'))
						$columns = (int) exec('tput cols');

					if (!$columns) {
						$return_var = -1;
						$output = [];
						exec('mode CON', $output, $return_var);
						if (0 === $return_var && $output) {
							// Look for second line ending in ": <number>" (searching for "Columns:" will fail on non-English locales).
							if (preg_match('/:\s*[0-9]+\n[^:]+:\s*([0-9]+)\n/', implode("\n", $output), $matches))
								$columns = (int) $matches[1];
						}
					}
				} else {
					if (!($columns = (int) getenv('COLUMNS'))) {
						$size = exec('/usr/bin/env stty size 2>/dev/null');
						if ('' !== $size && preg_match('/[0-9]+ ([0-9]+)/', $size, $matches))
							$columns = (int) $matches[1];
						if (!$columns) {
							if (getenv('TERM'))
								$columns = (int) exec('/usr/bin/env tput cols 2>/dev/null');
						}
					}
				}
			}

			if (!$columns)
				$columns = 80; // default width of cmd window on Windows OS
		}

		return $columns;
	}

	/**
	 * Register Dumper as the exception handler
	 *
	 * @since 1.15.0
	 *
	 * @return void
	 */
	public static function setExceptionHandler(): void {
		@set_exception_handler([__CLASS__, 'dump']);
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

		static $checked = false;

		if (!$checked) {
			$checked = true;
			if (static::canRegisterFunctions()) {
				if (!is_null($dumpAlias) && !isset($GLOBALS[$dumpAlias]) && preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $dumpAlias))
					runkit7_function_add($dumpAlias, '$data = null,$label = null,$options = []', 'return \Inane\Dumper\Dumper::dump($data, $label, $options);');

				if (!is_null($assertAlias) && !isset($GLOBALS[$assertAlias]) && preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $assertAlias))
					runkit7_function_add($assertAlias, '$expression,$data = null,$label = null,$options = []', 'return \Inane\Dumper\Dumper::assert($expression, $data, $label, $options);');
			} else {
				$hide = !static::$showRunkit7SupportMessage;

				if (!$hide) {
					$hide = \defined('INANE_DUMPER_HIDE_RUNKIT7');
					if ($hide) $hide = \INANE_DUMPER_HIDE_RUNKIT7;
				}

				if (!$hide) Dumper::dump('pecl install runkit7-alpha', 'Enable creating global functions at runtime.');
			}
		}

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
	 * Gets the currently set console colours
	 *
	 * options (and defaults with description):
	 *  - line		=> "\033[0m",	# console default
	 *  - divider	=> "\033[35m",	# magenta
	 *  - label		=> "\033[34m",	# blue
	 *  - dumper	=> "\033[97m",	# while
	 *  - file		=> "\033[31m",	# red
	 *  - reset		=> "\033[33m",	# yellow
	 *
	 * @since 1.14.0
	 *
	 * @return array console colours
	 */
	public static function getConsoleColours(): array {
		return static::$consoleColours;
	}

	/**
	 * Set or disable custom colours for console output
	 *
	 * There are a few elements that can be set using console colour escape codes.
	 *
	 * options (and defaults with description):
	 *  - line		=> "\033[0m",	# console default
	 *  - divider	=> "\033[35m",	# magenta
	 *  - label		=> "\033[34m",	# blue
	 *  - dumper	=> "\033[97m",	# while
	 *  - file		=> "\033[31m",	# red
	 *  - reset		=> "\033[33m",	# yellow
	 *
	 * @since 1.14.0
	 *
	 * @param false|array $colours `false` to disable, `[]` (empty array) for default, or set the colours using array keys provided.
	 *
	 * @return \Inane\Dumper\Dumper Dumper instance
	 *
	 * @throws \Inane\Stdlib\Exception\RuntimeException
	 * @throws \ReflectionException
	 */
	public static function setConsoleColours(false|array $colours = []): Dumper {
		if ($colours === false) static::$consoleColours = [
			'reset'		=> '',
			'dumper'	=> '',
			'label'		=> '',
			'file'		=> '',
			'line'		=> '',
			'divider'	=> '',
		];
		else if (count($colours) == 0) static::$consoleColours = [
			'reset'		=> "\033[0m",	# console default
			'dumper'	=> "\033[35m",	# magenta
			'label'		=> "\033[34m",	# blue
			'file'		=> "\033[97m",	# while
			'line'		=> "\033[31m",	# red
			'divider'	=> "\033[33m",	# yellow
		];
		else static::$consoleColours = \array_filter($colours, fn($k) => in_array($k, \array_keys(static::$consoleColours)), \ARRAY_FILTER_USE_KEY) + static::$consoleColours;

		return static::dumper();

		static::$consoleColours = $colours === false ? [
			'reset'		=> '',
			'dumper'	=> '',
			'label'		=> '',
			'file'		=> '',
			'line'		=> '',
			'divider'	=> '',
		] : $colours + [
			'reset'		=> "\033[0m",	# console default
			'dumper'	=> "\033[35m",	# magenta
			'label'		=> "\033[34m",	# blue
			'file'		=> "\033[97m",	# while
			'line'		=> "\033[31m",	# red
			'divider'	=> "\033[33m",	# yellow
		];
		return static::dumper();
	}

	/**
	 * Set Colors
	 *
	 * @since 1.14.0
	 *
	 * @see Dumper::setConsoleColours()	alias
	 *
	 * @param false|array{
	 * 		'line'		: "\033[0m",
	 * 		'divider'	: "\033[35m",
	 * 		'label'		: "\033[34m",
	 * 		'dumper'	: "\033[97m",
	 * 		'file'		: "\033[31m",
	 * 		'reset'		: "\033[33m",
	 * } $colours
	 *
	 * @return static
	 */
	public static function setConsoleColors(false|array $colors = []): Dumper {
		return static::setConsoleColours($colors);
	}

	/**
	 * Prepare the string for writing to page
	 *
	 * @since 1.17.0 console divider line streches to the end of the page
	 *
	 * @return string
	 */
	protected function render(): string {
		// Check for command line
		if (Dumper::isCli()) {
			$c = (object) Dumper::$consoleColours;

			$divider = str_repeat('=', static::columns());
			$code = implode("$c->divider$divider$c->reset " . PHP_EOL, Dumper::$dumps);
			Dumper::$dumps = [];
			return "{$c->dumper}DUMPER$c->reset" . PHP_EOL . "$code";
		}

		$code = implode(PHP_EOL, Dumper::$dumps);
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
			$file = array_key_exists('file', $t) ? str_replace('\\', '/', $t['file']) : 'none';
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
	 * @param string|null $label    label text
	 * @param string|null $type     variable type
	 * @param Type		  $dumpType dump type, default: `Type::Dump`
	 *
	 * @return string|null If Attribute Silence true return null
	 *
	 * @throws \Inane\Stdlib\Exception\RuntimeException
	 * @throws \ReflectionException
	 */
	protected static function formatLabel(?string $label = null, ?string $type = null, Type $dumpType = Type::Dump): ?string {
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
			if ($dumpType == Type::Todo) $label = "TODO: $label";
			$c = (object) Dumper::$consoleColours;

			$title = isset($label) ? "$c->label $label:$c->reset " : '';
			$file = "$c->file$data->file$c->reset::$c->line$data->line$c->reset";
			$class = $data->class ? " => $c->divider$data->class::$data->function$c->reset" : '';
		} else {
			// HTML
			if ($dumpType == Type::Todo) $label = "<span class=\"type-todo\">TODO:</span> $label";
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

		if (array_key_exists('file', $trace) && $file = @file($trace['file'])) {
			$id = $trace['line'] - 1;
			$line = $file[$id];
			preg_match('/\((\\$(\w+))/', $line, $match);
		} else $match = [];

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
    $code
</details>
</div>
DUMPER_HTML;
		}

		if (Dumper::$bufferOutput) Dumper::$dumps[] = $output;
		else {
			$c = (object) Dumper::$consoleColours;
			echo "\t\t{$c->dumper}DUMPER$c->reset:$output" . PHP_EOL;
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
	 *  - (Type=Dump) type         : Dump - set a custom type for the dump
	 *
	 * Chaining: You only need bracket your arguments for repeated dumps.
	 * Dumper::dump('one')('two', 'Label')
	 *
	 * @param mixed                       $data    item to dump
	 * @param null|string                 $label   text table for the dump
	 * @param array|\Inane\Stdlib\Options $options customised options for the dump
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

		if (in_array($params->type, [Type::Dump, Type::Todo]) || in_array($params->type, Dumper::$additionalTypes)) {
			$info = Dumper::analyseVariable($data);
			if (is_null($label) && $info['variable'] != '') $label = $info['variable'];
			if (in_array($params->type, [Type::Dump, Type::Todo])) $label = Dumper::formatLabel($label, $info['type'], $params->type);
			if (!is_null($label)) Dumper::dumper()->addDump($data, $label, $params);
		}

		return Dumper::dumper();
	}


	/**
	 * Adds a `Type::Todo` dump to the collection
	 *
	 * Alias for dump set to add a todo dump.
	 *
	 * @see \Inane\Dumper\Dumper::dump
	 *
	 * @param mixed         $data    The data to be dumped. Defaults to null.
	 * @param string|null   $label   An optional label to describe the data. Defaults to null.
	 * @param array|Options $options Additional options or configuration for the dumper. Defaults to an empty array.
	 *
	 * @return Dumper Returns an instance of the Dumper class.
	 */
	public static function todo(mixed $data = null, ?string $label = null, array|Options $options = []): Dumper {
		$options = new Options(['type' => Type::Todo])->complete($options);
		return static::dump($data, $label, $options);
	}
}
