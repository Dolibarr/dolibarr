php-shellcommand
===========

[![Build Status](https://secure.travis-ci.org/mikehaertl/php-shellcommand.png)](http://travis-ci.org/mikehaertl/php-shellcommand)
[![Latest Stable Version](https://poser.pugx.org/mikehaertl/php-shellcommand/v/stable.svg)](https://packagist.org/packages/mikehaertl/php-shellcommand)
[![Total Downloads](https://poser.pugx.org/mikehaertl/php-shellcommand/downloads)](https://packagist.org/packages/mikehaertl/php-shellcommand)
[![Latest Unstable Version](https://poser.pugx.org/mikehaertl/php-shellcommand/v/unstable.svg)](https://packagist.org/packages/mikehaertl/php-shellcommand)
[![License](https://poser.pugx.org/mikehaertl/php-shellcommand/license.svg)](https://packagist.org/packages/mikehaertl/php-shellcommand)

php-shellcommand provides a simple object oriented interface to execute shell commands.

## Features

 * Catches `stdOut`, `stdErr` and `exitCode`
 * Handle argument escaping
 * Pass environment vars and other options to `proc_open()`

## Examples

### Basic Example

```php
<?php
use mikehaertl\shellcommand\Command;

// Basic example
$command = new Command('/usr/local/bin/mycommand -a -b');
if ($command->execute()) {
    echo $command->getOutput();
} else {
    echo $command->getError();
    $exitCode = $command->getExitCode();
}
```

### Advanced Features

```php
// Create command with options array
$command = new Command(array(
    'command' => '/usr/local/bin/mycommand',

    // Will be passed as environment variables to the command
    'procEnv' => array(
        'DEMOVAR' => 'demovalue'
    ),

    // Will be passed as options to proc_open()
    'procOptions' => array(
        'bypass_shell' => true,
    ),
));

// Add arguments with correct escaping:
// results in --name='d'\''Artagnan'
$command->addArg('--name=', "d'Artagnan");

// Add argument with several values
// results in --keys key1 key2
$command->addArg('--keys', array('key1','key2'));
```

## API

### Properties

 * `$escapeArgs`: Whether to escape any argument passed through `addArg()`. Default is `true`.
 * `$escapeCommand`: Whether to escape the command passed to `setCommand()` or the constructor.
    This is only useful if `$escapeArgs` is `false`. Default is `false`.
 * `$useExec`: Whether to use `exec()` instead of `proc_open()`. This is a workaround for OS which
   have problems with `proc_open()`. Default is `false`.
 * `$captureStdErr`: Whether to capture stderr when `useExec` is set. This will try to redirect
   the otherwhise unavailable `stderr` to `stdout`, so that both have the same content on error.
   Default is `true`.
 * `$procCwd`: The initial working dir passed to `proc_open()`. Default is `null` for current
    PHP working dir.
 * `$procEnv`: An array with environment variables to pass to `proc_open()`. Default is `null` for none.
 * `$procOptions`: An array of `other_options` for `proc_open()`. Default is `null` for none.
 * `$locale`: The locale to (temporarily) set with `setlocale()` before running the command.
   This can be set to e.g. `en_US.UTF-8` if you have issues with UTF-8 encoded arguments.

You can configure all these properties via an array that you pass in the constructor. You can also
pass `command`, `execCommand` and `args` as options. This will call the respective setter (`setCommand()`,
`setExecCommand()`, etc.).

### Methods

 * `__construct($options = null)`
    * `$options`: either a command string or an options array (see `setOptions()`)
 * `setOptions($options)`: Set command options
    * `$options`: array of name => value options that should be applied to the object.
       You can also pass options that use a setter, e.g. you can pass a `command` option which
       will be passed to `setCommand().`
 * `setCommand($command)`: Set command
    * `$command`: The command or full command string to execute, like `gzip` or `gzip -d`.
       You can still call `addArg()` to add more arguments to the command. If `$escapeCommand` was
       set to `true`, the command gets escaped through `escapeshellcmd()`.
 * `getCommand()`: The command that was set through `setCommand()` or passed to the constructor.
 * `getExecCommand()`: The full command string to execute.
 * `setArgs($args)`: Set argument as string
    * `$args`: The command arguments as string. Note, that these will not get escaped!
 * `getArgs()`: The command arguments that where set through `setArgs()` or `addArg()`, as string
 * `addArg($key, $value=null, $escape=null)`: Add argument with correct escaping
    * `$key`: The argument key to add e.g. `--feature` or `--name=`. If the key does not end with
       and `=`, the `$value` will be separated by a space, if any. Keys are not escaped unless
       `$value` is null and `$escape` is `true`.
    * `$value`: The optional argument value which will get escaped if `$escapeArgs` is true.
       An array can be passed to add more than one value for a key, e.g. `addArg('--exclude', array('val1','val2'))`
       which will create the option "--exclude 'val1' 'val2'".
    * `$escape`: If set, this overrides the `$escapeArgs` setting and enforces escaping/no escaping
 * `getOutput()`: The command output as string. Empty if none.
 * `getError()`: The error message, either stderr or internal message. Empty if no error.
 * `getStdErr()`: The stderr output. Empty if none.
 * `getExitCode()`: The exit code.
 * `getExecuted()`: Whether the command was successfully executed.
 * `execute()`: Executes the command and returns `true` on success, `false` otherwhise.

> **Note:** `getError()`, `getStdErr()` and `getOutput()` return the trimmed output.
> You can pass `false` to these methods if you need any possible line breaks at the end.
