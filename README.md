# Readme: Dumper

> $Id$ ($Date$)

A little tool to help with debugging by writing a `var_dump` like message unobtrusively to a collapsable panel at the bottom of a page.

## Install

`composer require inanepain/dumper`

## Usage

```php
\Inane\Dumper\Dumper::dump($variable);
```

### Ease of use

```php
// register global alias
\Inane\Dumper\Dumper::dumper();

// now you can use dd()
dd($variable);
```

### Silence

## Configuration

There are some static properties you can set to control parts of dumper.

### expanded

> @since 1.8.0

Controls the initial state of the Dumper window.

default: `false`
