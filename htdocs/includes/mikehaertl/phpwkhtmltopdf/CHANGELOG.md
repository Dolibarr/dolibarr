# CHANGELOG

## 2.2.1

 * Issue #219: Make `header-html` and `footer-html` also work for Toc and cover page

## 2.2.0

 * Add a `$type` parameter to `addPage()` and `addToc()` (#169)

## 2.1.1

 * Add `-a` to default `xvfb-run` options

## 2.1.0

 * We now follow [semver](http://semver.org/)!
 * Issue #112: Add toString() to get raw PDF content as string

## 2.0.4

 * Issue #100: Fix issue with header-/footer-html

## 2.0.3

 * Make dependencies in `composer.json` less restrictive
 * Remove `composer.lock` to allow for independent updates of dependencies

## 2.0.2

 * Issue #56: Fix issue with `proc_open()` on windows
 * Add detection for XML strings

## 2.0.1

 * Issue #61: Fix ignored `tmpDir`

## 2.0.0

In this major release we have added a wrapper for wkhtmltoimage and cleaned
up the code and interface. We also introduced namespaces and Travis testing.
Please have a look at the README.md for the new API.

## 1.2.6

 * Issue #54: Add binary autodetection for windows
 * Issue #47: Add escaping to temp files to improve behavior on windows

## 1.2.5

 * Issue #46: Add 'ignoreWarnings' option
 * Issue #45: Fix for missing sys_get_temp_dir() on older PHP 5 versions
 * Issue #41: Fix escaping of some parameters

## 1.2.4

 * Add support for HTML strings in html-footer and html-header

## 1.2.3

 * Issue #36: Bugfix in send()

## 1.2.2

 * Issue #34: Allow to set filename even when PDF is streamed inline
 * Issue #35: Support repeatable wkhtmltopdf options

## 1.2.1

 * Issue #29: Add Xvfb support

## 1.2.0

A minor change in the options was introduced in this release. If you used the `bin`
option before you have to rename it to `binPath` now. Please check the docs for
full documentation.

 * Issue #27: Add autodetection of wkhtmltopdf binary on Unix based systems (thanks eusonlito)
 * Issue #28: Implement optional passing of environment variables to proc_open (thanks eusonlito)
 * Issue #30: Bug with options without an argument

## 1.1.6

 * Issue #21: Add support for wkhtmltopdf 0.9 versions

## 1.1.5

 * Add composer autoloading (thanks igorw)
 * Issue #10: Improve error reporting

## 1.1.4

 * Add composer.jsone

## 1.1.3

 * Made getCommand() public to ease debugging
 * Issue #6: Fix typo that prevented shell escaping on windows
 * Issue #5: Updated docs: wkhtmltopdf can not process PDF files

## 1.1.2

 * Issue #4: Fix issue with longer PDFs

## 1.1.1

 * Issue #2: Fix escaping of arguments
 * Issue #3: Fix HTML detection regex


## 1.1.0

 * Issue #1: Allow to add HTML as string


## 1.0.0

 * Initial release
