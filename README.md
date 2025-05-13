# Overview

A little tool to help with debugging by writing a `var_dump` like
message unobtrusively to a collapsible panel at the bottom of a page.

# Install

    $ composer require inanepain/dumper

# Basic Usage

Dumper works right out the box. Once installed via **composer** you can
use it straight away to dump your objects using either method; `dump` or
`assert` on the `\Inane\Dumper\Dumper` object.

The `dump` method is the default method and logs what it is given. Where
as the `assert` has a test for its first parameter and only logs if the
test **fails** (*falsy*).

basic usage

    \Inane\Dumper\Dumper::dump($data, 'After marge process');
    \Inane\Dumper\Dumper::assert(!$data->error, $data, 'After marge process'); 

- Logs if error is **true**

## Ease of use

Dumper registers the shortcut functions `dd` and `da` that work just
like calling `\Inane\Dumper\Dumper::dump()` or
`\Inane\Dumper\Dumper::assert()`.

Dumper does not overwrite existing functions. So if either `dd` or `da`
are already taken, Dumper will skip them. See **Dumper: Aliases**
[Dumper: Aliases](#_dumper_aliases) on creating your own custom alias
functions.

basic shortcut usage

    dd($data, 'After marge process');
    da(!$data->error, $data, 'After marge process'); 

- Logs if error is **true**

# Getting more out of Dumper

Some more or less helpful hints and tips regarding to usage of `Dumper`.

- [custom aliases](doc/aliases.adoc)

- [configuration](doc/configuration.adoc)

- [ui](doc/ui.adoc)

- [silence](doc/silence.adoc)

- [other](doc/other.adoc)

## Dumper: Aliases

Creating a custom global function as an alias to
`\Inane\Dumper\Dumper::dump` method.

**ext-runkit7** required to register global functions. Without it the
function is stored in a variable instead.

Creating a custom alias for Dumper

    \Inane\Dumper\Dumper::dumper('kickIt', 'shErr');

    // you can now use your `kickIt` function the same as the `dump` method.
    kickIt($data, 'Data after...'); 
    // what about `shErr`?
    shErr(!$data->error, $data, 'Data after...'); 

    // without *ext-runkit7*. Note the $kickIt is a variable.
    $kickIt($data, 'Data after...');
    $shErr(!$data->error, $data, 'Data after...'); 

- The first parameter of the dumper method creates `dump` aliases akin
  to the `dd` function.

- The second parameter sets the alias for `assert` akin to `da`.

That’s how easy it is to create a custom global shortcut function for
Dumper.

## Dumper: Configuration

Dumper has a few static public properties you can use to change some of
the default behaviours.

### enabled

Dumper starts enabled but should you wish all Dumpers related content
gone. Disable it here.

default: `true`

config: turn off Dumper’s output

    \Inane\Dumper\Dumper::$enabled = false;

### bufferOutput

Write dumps last. Just before php terminates. Set to `false` to have
dumps inserted as the occur at runtime.

This is mostly useful when running console code.

default: `true`

config: turn off buffered output to print dumps inline

    // Somewhere before using Dumper, or even after for a section of code and then turn buffer on again.
    \Inane\Dumper\Dumper::$bufferOutput = false;
    // some code loop probably
    \Inane\Dumper\Dumper::$bufferOutput = true;

### useVarExport

By default Dumper uses its own variable parser to generate the output.
Here you can tell Dumper to use `var_export` instead.

default: `false`

config: set dumper to use var\_export

    // set value to true
    \Inane\Dumper\Dumper::$useVarExport = true;

### highlight

Set the colour theme dumper uses. The default is to use the colours
already set in your php.ini file.

default: `\Inane\Stdlib\Highlight::CURRENT`

1.  Available colours in `\Inane\Stdlib\Highlight`

    - CURRENT

    - DEFAULT

    - PHP2

    - HTML

config: set dumper colours

    // set colour theme
    \Inane\Dumper\Dumper::$highlight = \Inane\Stdlib\Highlight::PHP2;

### expanded

**Since**: 1.8.0

Controls the initial expanded state of the Dumper panel.

default: `false`

config: dumper log panel initial state

    // Create the Dumper panel expanded
    \Inane\Dumper\Dumper::$expanded = true;

### setColours

**Since**: 1.14.0

Allows setting custom cli colours or disabling cli colours completely.

default:

    [
        'reset' => "\033[0m",        # console default
        'dumper' => "\033[35m",      # magenta
        'label' => "\033[34m",       # blue
        'file' => "\033[97m",        # while
        'line' => "\033[31m",        # red
        'divider' => "\033[33m", # yellow
    ];

config: setting cli colours

    // Remove cli colouring
    \Inane\Dumper\Dumper::setConsoleColours(false);

    // Setting default colours
    \Inane\Dumper\Dumper::setConsoleColours([]);

    // Remove cli colouring
    \Inane\Dumper\Dumper::setConsoleColours(false);
    // creating a colour using Pencil from `inanepain/cli`
    $label = new \Inane\Cli\Pencil(colour: \Inane\Cli\Pencil\Colour::Green, background: \Inane\Cli\Pencil\Colour::Red, style: \Inane\Cli\Pencil\Style::SlowBlink);
    // Then set colours for **file**, **label** and **reset**
    \Inane\Dumper\Dumper::setConsoleColours([
        'file' => "\033[36m",
        'label' => "$label",
        'reset' => "\033[0m",
    ]);

### Hide runkit7 support message

**Since**: 1.16.0

Option to hide the support message to install **runkit7** if not
found.  
There are two methods to disable this message: via class static property
or via a global constant.

class property

    \Inane\Dumper\Dumper::$showRunkit7SupportMessage = false;

global constant

    define('INANE_DUMPER_HIDE_RUNKIT7', true);

## Dumper: UI

Customising Dumpers look and feel.

### Panel

This is done by setting the values of the following **css variables**
and a few php **class properties**.

#### font size

Adjust the font size used by the Dumper panel.

- variable: `--dumper-font-size`

- default: `smaller`

#### max height

Adjust the maximum height allowed of the Dumper panel when opened.

- variable: `--dumper-max-height`

- default: `80vh`

#### expanded

**Since**: 1.8.0

Controls the initial expanded state of the Dumper panel.

default: `false`

config: dumper log panel initial state

    // Create the Dumper panel expanded
    \Inane\Dumper\Dumper::$expanded = true;

### Theme

Switching Dumpers theme is done in the php by changing a static property
on the Dumper object.

#### highlight

Set the colour theme dumper uses. The default is to use the colours
already set in your php.ini file.

default: `\Inane\Stdlib\Highlight::CURRENT`

1.  Available colours in `\Inane\Stdlib\Highlight`

    - CURRENT

    - DEFAULT

    - PHP2

    - HTML

config: set dumper colours

    // set colour theme
    \Inane\Dumper\Dumper::$highlight = \Inane\Stdlib\Highlight::PHP2;

## Dumper: Silence

You can use the `\Inane\Dumper\Silence` attribute to silence dumps,
silence a specified number of dumps, only show a specified number of
dumps then go silent, per **class**, **method** or **function**. The
Silence attribute also allows you to set Silence’s initial state and
then set a counter after which the state will toggle.

If a class is silenced all functions are silenced regardless of their
individual settings.

Basic Silence Usage

    use Inane\Dumper\Silence as DumperSilence;

    #[DumperSilence()]
    function doFirst(): void {
        echo 'hello', PHP_EOL;

        dd(__FUNCTION__, 'one');
        dd(__FUNCTION__, 'two');
    }


    #[DumperSilence(false)]
    function doSecond(): void {
        echo 'hello', PHP_EOL;

        dd(__FUNCTION__, 'one');
        dd(__FUNCTION__, 'two');
    }

    doFirst(); 
    // hello

    doSecond(); 
    // hello
    // doSecond, one
    // doSecond, two

- This only outputs the `echo`. The \`dd’s are ignored.

- Here the `echo` and `dd` output is displayed.

### Toggling State

This feature of Silence lets you either enable or disable dumping after
a specified number of dump requests have been made. This lets you log
only a few items when iterating over a large collection.

If you specify a limit, Silence’s second parameter, the Silence instance
will toggle its value after it has received that many check requests.
i.e. Silent becomes verbose and vice versa.

The toggle only happens once. **NOT** every time the limit is reached.

The is an issue logged to pass an array in place of an limit that sets
when to toggle and how long the toggle should remain active.

Toggle Silence Usage

    use Inane\Dumper\Silence as DumperSilence;

    #[DumperSilence(false, 1)]
    function doFirst(): void {
        echo 'hello', PHP_EOL;

        dd(__FUNCTION__, 'one');
        dd(__FUNCTION__, 'two');
    }


    #[DumperSilence(true, 1)]
    function doSecond(): void {
        echo 'hello', PHP_EOL;

        dd(__FUNCTION__, 'one');
        dd(__FUNCTION__, 'two');
    }

    doFirst(); 
    // hello
    // doFirst, two

    doSecond(); 
    // hello
    // doSecond, one

- Now we have the `echo` and the value from the first `dd` request.
  Silence toggled **false** to **true** after **1** request so the
  second `dd` request was ignored.

- This is the reverse of the first. Here only the first `dd` request is
  shown.

### Advanced: Logging Silence checks

Actually geeky stuff would be a better way to describe this section. By
default Silence checks are not shown in the Dumper panel but this can be
enabled if you want to figure out why your toggles are not doing what
you expect them to do.

To enable this this is one simple step, add `Type::Silence` to the
`Dumper::$additionalTypes` array.

Logging Silence Requests

    Dumper::$additionalTypes[] = Type::Silence; 
    // code
    Dumper::$additionalTypes = []; 

- future Silence checks will be shown in the Dumper panel.

- and Silence checks after this will no longer show in the Dumper panel.

#### Customising Silence checks

You can customise the Silence check logs per Silence instance to make
them stand out from the rest by giving it a custom **label** and
**colour**.

Customising Silence Logs

    #[Silence(on: true, config: [
        'label' => 'Do Test This', 
        'colour' => 'purple', 
    ])]
    function doThis(): void {
        dd(null, 'Dump nothing important'); 
    }

    doThis(); 
    doThis(); 
    doThis(); 

- set custom label to appear in Dumper panel.

- set custom colour for log entry in Dumper panel.

- this will not be show due to Silence

- a purple entry labelled **Do Test This** will be added every time this
  function is called

## Other Useful Information

Dumper has a few more tricks up its sleve. Here are some of the more
useful ones.

### Exception Handling

You can set Dumper as the Exception Handler. This will catch any
uncaught exceptions and dump them. This is useful for debugging in
production environments. The method provided is a simple ease of use
function since the same effect can be achived quiet simple in php.

setting Dumper as the exception handler

    \Inane\Dumper\Dumper::setExceptionHandler();

    // The same thing can be done usding
    set_exception_handler(['Inane\Dumper\Dumper', 'dump']);
