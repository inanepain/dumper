# Readme: Dumper

> $Id$ ($Date$)

A little tool to help with debugging by writing a `var_dump` like message unobtrusively to a collapsible panel at the bottom of a page.

## Install

`composer require inanepain/dumper`

## Usage

```php
\Inane\Dumper\Dumper::dump($variable);
```

### Ease of use

Dumper registers a shortcut function `dd` that works like calling `\Inane\Dumper\Dumper::dump()` if `dd` is available.

```php
$var = ['Some', 'variable'];

// calling:
dd($var);

// is the same as calling:
\Inane\Dumper\Dumper::dump($var);
```

You can also register your own global variable to use as a shortcut for Dumper by passing the desired variable name to `\Inane\Dumper\Dumper::dumper('showMe')`.

```php
// register global alias
\Inane\Dumper\Dumper::dumper('showMe');

// now you can use $showMe()
$showMe($var);
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
