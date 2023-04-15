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

### Silence

### CSS Variables

| Variable              | Default   | Description                       |
|:----------------------|:----------|:----------------------------------|
| `--dumper-font-size`  | `smaller` | Font Size                         |
| `--dumper-max-height` | `80vh`    | Max amount Dumper window can grow |

## Configuration

There are some static properties you can set to control parts of dumper.

### expanded

> @since 1.8.0

Controls the initial state of the Dumper window.

default: `false`
