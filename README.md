# Readme: Dumper

> $Id$ ($Date$)

A little tool to help with debugging by writing a `var_dump` like message unobtrusively to a collapsible panel at the bottom of a page.

## Install

`composer require inanepain/dumper`

## Basics

Dump anything by calling the `dump` or `assert` method on the `\Inane\Dumper\Dumper` object.

### Ease of use

Dumper registers the shortcut functions `dd` and `da` that work like calling `\Inane\Dumper\Dumper::dump()` or `\Inane\Dumper\Dumper::assert()`.

**NOTE:** Alias will not overwrite any existing functions, rather no alias is then created. See [Custom Alias] for setting your own alias for `dump`.

```php
$var = ['Some', 'variable'];

// calling:
dd($var);
# or
da(false, $var);

// is the same as calling:
\Inane\Dumper\Dumper::dump($var);
# or
\Inane\Dumper\Dumper::assert(false, $var);
```

#### Custom Aliases

You can also register your own global variable to use as a shortcut for Dumper by passing the desired variable name to `\Inane\Dumper\Dumper::dumper('showMe')`.

**NOTE:** Custom alias are created as variables thus require the dollar sign `$`.  
**NOTE:** UNLESS: **pecl** ext **runkit7** is installed. Then global functions are also created.

```php
// register global alias
\Inane\Dumper\Dumper::dumper('showMe', 'showError');

// now you can use $showMe()
$showMe($var);
$showError(false, $var);

// with runkit7
showMe($var);
showError(false, $var);
```

## Usage by example

Common code to all usage examples:

```php
$variable = [
	'title' => 'Dumper Examples',
	'data' => 'some stuff.',
];

$data = [
	'id' => 'dumper-assert',
	'one' => 1,
	'aye' => 'a',
	'error' => false,
];
```

### Full command usage:

```php
\Inane\Dumper\Dumper::dump($variable);
```

### Alias usage:

Dumper sets an global alias that is usable anywhere in your code.

```php
dd($variable);
```

### Assert usage:

Only want to dump a variable if something goes wrong. Use `assert` for that.

```php
\Inane\Dumper\Dumper::assert(isset($variable), $variable, 'isset returns `true` this is not written to output.');

da(isset($another), '$another', 'isset returns `false` line written. Note: 2nd param a string since the variable is not set.');
```

#### Assert: Slightly more realistic example

Here we check for an error value in an array and only dump the error if we have one. You can use anything that returns a `true/false` result, like a validation method.

```php
$data = [
	'error' => [
		'code' => 101,
		'message' => 'Example error',
	],
] + $data;

# if we have an error the test returns `false` and the $data is dumped.
da($data['error'] == false, $data['error'], 'Error found in data');
```

### Silence Attribute

You can use the `\Inane\Dumper\Silence` attribute to silence dumps, silence a specified number of dumps, only show a specified number of dumps then go silent, per **class**, **method** or **function**.

#### Using Silence

Simply add the attribute to your desired target.

```php
use Inane\Dumper\Silence as DumperSilence;

#[DumperSilence(false, 1)]
function doSomething(): void {
	echo 'hello', PHP_EOL;

	dd('doSomething', 'function');
	dd('func', 'doSomething');
}

class Person {
	public function __construct(?array $properties = null) {
	}
	
	/**
	 * getStrength
	 *
	 * @return string
	 */
	#[DumperSilence(true)]
	public function getName(): string {
		// This dump will never write, thanks to silence attribute
		dd('name', 'Person Properties name');
	    return 'Bob';
	}

	/**
	 * getStrength
	 *
	 * @return int
	 */
	public function getStrength(): int {
	    return 10;
	}

	#[DumperSilence(true, 1)]
	public function speak(string $message): string {
		// this is skipped by silence the first time method is called
		dd($message, 'message');

		$line = $this->getName() . ' ' . match($this->getStrength()) {
			6, 7 => 'wheezes',
			8, 9, 10 => 'rasps',
			15, 16, 17 => 'booms',
			18 => 'bellows',
			default => 'says',
		} . ': ' . $message;

		// this is always dumped
		dd($line, 'Spoken message');

		return $line;
	}
}

doSomething();

$p = new Person();
$p->speak('Hello');
$p->speak('bye');
```

### CSS Variables

| Variable              | Default   | Description                       |
|:----------------------|:----------|:----------------------------------|
| `--dumper-font-size`  | `smaller` | Font Size                         |
| `--dumper-max-height` | `80vh`    | Max amount Dumper window can grow |

## Configuration

There are some static properties you can set to control parts of dumper.

### enabled

Completely turn Dumper output off.

default: `true`

```php
\Inane\Dumper\Dumper::$enabled = false;
```

### bufferOutput

Write dumps last. Just before php terminates. Set to `false` to have dumps inserted as the occur at runtime.
This is mostly useful when running console code.

default: `true`

```php
// Somewhere before using Dumper, or even after for a section of code and then turn buffer on again.
\Inane\Dumper\Dumper::$bufferOutput = false;
// some code loop probably
\Inane\Dumper\Dumper::$bufferOutput = true;
```

### expanded

> @since 1.8.0

Controls the initial state of the Dumper window.

default: `false`

```php
// Create the Dumper panel expanded
\Inane\Dumper\Dumper::$expanded = true;
```
