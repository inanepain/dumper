Version: $Id$ ($Date$)

# History

## 1.17.0-dev @2025 May xx

-   **update**: Console output divider line now stretches across the
    console width

-   **new** : `Type::Todo` new type, when enabled addes todo entries to
    dump log

-   **new** : `Dumper::todo` alias for `Dumper::dump` that is configured
    for `Type::Todo`

-   **new** : `Dumper::isWindows` new method to check if running on
    Windows, need for column width

-   **new** : `Dumper::columns` new method to get console width

-   **update**: Tweaked the UI for dumps a little

-   fix some minor wording and spelling errors

## 1.16.0 @2025 Apr 29

-   **new**: Global Constant **INANE\_DUMPER\_HIDE\_RUNKIT7** to disable
    runkit7 support message if it is not installed

-   **new**: `Dumper::showRunkit7SupportMessage` does the same as above,
    either one can be set

## 1.15.0 @2025 Feb 18

-   new: `Dumper::setExceptionHandler` Register Dumper as the exception
    handler

## 1.14.2 @2024 Sep 20

-   fix: Infinit loop if `Runkit7` not installed

## 1.14.1 @2023 Jun 27

-   fix: Dumper console colours for divider line

## 1.14.0 @2023 Jun 09

-   new: `Dumper::setConsoleColours` Ability to set custom colours for
    console

-   new: `Dumper::getConsoleColours` Get current console colours

-   fix: error when using Dumper in interactive shell

-   Minor tweaks

## 1.13.1 @2023 Apr 25

-   fix: added check function is not null when checking for `Silence`

## 1.13.0 @2023 Apr 25

-   enhance: Dumper support extended to functions (global / classless
    \[Attribute::TARGET\_FUNCTION\])

-   enhance: improvements to alias function code

-   doc: some work on the README.md

## 1.12.0 @2023 Apr 16

-   new: aliases - you can now also register custom assert aliases

-   new: aliases - if the **pecl** **runkit7** extension is installed;
    custom aliases are registered as functions, variable versions also
    still created

## 1.11.1 @2022 Dec 11

-   fix: missing autoloader in composer.json

## 1.11.0 @2022 Dec 11

-   new: Silence checks can be logged

-   update: phpdoc

-   minor tweaks, fixes and updates

## 1.10.0 @2022 Dec 09

-   new: `Dumper::assert` method - first argument true/false expression
    followed by usual dump arguments

-   new: `da` global function shortcut for `Dumper::assert`

-   new: parameter `$limit` for `Silence` ⇒ toggles state returned when
    limit reached

-   many minor fixes & updates to code & documentation

-   major performance improvements

## 1.9.1 @2022 Nov 09

-   Internal improvements and optimisations

-   fix: dump file information showing incorrect data

## 1.9.0 @2022 Nov 05

-   update: added shortcut argument to `dumper` to register global
    variable dump function by that name

-   update: it is no longer necessary to call `dumper` to register `dd`,
    composer handles this

-   README improvements

## 1.8.0 @2022 Jul 29

-   Added static expanded option to control initial state of Dumper
    window
