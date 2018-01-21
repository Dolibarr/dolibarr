# CHANGELOG

## 1.2.2

 * Issue #16: Command on different drive didn't work on windows

## 1.2.1

 * Issue #1: Command with spaces didn't work on windows

## 1.2.0

 * Add option to return untrimmed output and error

## 1.1.0

 * Issue #7: UTF-8 encoded arguments where truncated

## 1.0.7

 * Issue #6: Solve `proc_open()` pipe configuration for both, Windows / Linux

## 1.0.6

 * Undid `proc_open()` changes as it broke error capturing

## 1.0.5

 * Improve `proc_open()` pipe configuration

## 1.0.4

 * Add `$useExec` option to fix Windows issues (#3)

## 1.0.3

 * Add `getExecuted()` to find out execution status of the command

## 1.0.2

 * Add `$escape` parameter to `addArg()` to override escaping settings per call

## 1.0.1

 * Minor fixes

## 1.0.0

 * Initial release
