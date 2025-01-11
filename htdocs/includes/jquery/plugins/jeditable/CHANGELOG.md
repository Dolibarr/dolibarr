# Changelog

## 2.0.21

* Fix tooltip getting overwritten (#241)
* Update dev dependencies

## 2.0.20

* Update dev dependencies

## 2.0.19

* Fix warnings from jquery-migrate (#230) by Shaun Case
* Upgrade dependencies

## 2.0.18

* Upgrade dependencies
* Formatting fix (remove tabs)
* Add FUNDING.yml
* Fix changelog (#218)

## 2.0.17

* Upgrade jquery to 3.5.0 because of vulnerability in previous versions

## 2.0.16

* Get text instead of html in callback result (#214)
* Enable shift+enter to submit form (#216)

## 2.0.15

* Remove deprecated .blur() calls and replace them with .on() (#211)

## 2.0.14

* Remove deprecated jQuery functions and replace them by modern counterparts (#211)

## 2.0.13

* Accept list of tuples as items for select element (#208 by @emjayko)

## 2.0.12

* Wrap the plugins in functions for non-conflict mode (#205)

## 2.0.11

* Use text() instead of html() for revert (#203)

## 2.0.10

* Regenerate the minified files

## 2.0.9

* Allow resubmiting if onsubmit or submit returns false (#201 by @RobdeT)

## 2.0.8

* Update dependencies to fix security vulnerabilities (CVE-2019-11358, and in js-yaml)

## 2.0.7

* Change isSubmitting back to false onComplete (#192)
* Width/height now correctly taken into account for masked inputs (#189)
* Pass event object to the before callback (#188)

## 2.0.6

* Fix cancel when ESC is pressed (#177) by @thezoggy
* Fix "main" entry of package.json (#181)

## 2.0.5

* Sorting of select items is now an option (default is not sorted). by @nathanvda (#178)

## 2.0.3

* Add ability to apply css to input element (#173)

## 2.0.2

* Fix a bug where selected value will get added to the select options. by @eman1986 (#166)

## 2.0.1

* Fix width setting to number, url and email (#163)

## 2.0.0

* BREAKING: remove ajaxupload and old datepicker plugins (new datepicker added)
* BREAKING: change the autogrow plugin for another one
* Add datepicker plugin for jQuery-UI's datepicker
* Add form param to onblur apply (#37)
* Allow onblur function to cancel form if it returns false (#14)
* Properly cleanup after destroy. by  @Scottmitch (#125)
* Return submitdata if it's a function. by @mkdgs (#109)
* Add onedit example + doc. by @chriskeeble (#122)
* Stop event propagation for charcounter. by @twashing (#86)
* Add multiselect support. by @nicholasryan (#75)
* Add html5 text attributes, email, number and url types. by @nfreear (#87)
* Add maxlength to textarea too. by @estebistec (#68)
* Add option to style buttons and add id to form. by @quocvu (#71)
* Add intercept option useful for preprocessing data. by @randell (#66)
* Pass the source event to the onedit hook. by @gfouquet (#104)
* Return element to target function (#67)
* Add 'before' option. by @bp323 (#113)
* Fix issue with html encodable characters (#110)
* Add checkbox type. by @oneslash and @pushpinderbagga (#52)
* Correctly check if checkbox is checked
* Select options are now sorted by value. by @jjwdesign (#97)
* Add submitdata to callback. by @mikemeier (#64)
* Adjust the width of the element to account for border/margin/padding
* Fix loadtext not showing (#15)
* Pass a response callback function to custom target function. by bshelton229. (#65)
* Add showfn for jquery animations before displaying form (#46)
* Remove extra loop. by @dgm (#45)
* Better demo page showing more example
* Add proper API documentation
* Add usage documentation in README.md
* Add Dockerfile to serve the demos
* Add LICENSE file

## 1.8.0

* A lot of cleanup in the repo after years of abandon
* The demos folder now contains a page (index.html) with all the demo code
* Removed Textile stuff
* Removed SQLite from the demo
* Add support for configuring size and maxlength. by @bonkowski (#32)
* Set "cache" to false on loadurl. Fix issue with IE8. by @spikex (#33)
* Add package.json for npm hosting
* Remove eval to allow compilation with Closure & Fix for newer jQuery. by @flavour (#158)
* Fix issue with width/height (#137)
* Fix issue with selected in select element (#106)
* Add label settings. by @tomasm- (#40)

## 1.7.3

* Add support for "jQuery plugin repository": http://plugins.jquery.com/

## 1.7.2

* Submit on change if input type select and no submit button defined ("gregpyp":http://github.com/gregpyp)

## 1.7.1

* Namespace default event as click.editable ("Zangetsu":http://github.com/Zangetsu)
* Trim whitespace when determining the selected value of pulldown ("binarylogic":http://github.com/binarylogic)
* Make default settings publicly available ("lawrencepit":http://github.com/lawrencepit)
* Allow ajax calls other than 'html', e.g. json and script calls. ("lawrencepit":http://github.com/lawrencepit)
* Do not follow links if they are editable ("Darwin":http://github.com/darwin)
* make JSLInt happy ("olleolleolle":http://github.com/olleolleolle)

## 1.7.0

* Full control over jQuery AJAX options for those who want to tinker.
* Fix problem with IE and placeholder with HTML tags.
* Add $.editable('disable'), $.editable('enable') and $.editable('destroy')
* Add onedit, onsubmit, onreset and onerror hooks. 
* Allow passing select options as JavaScript object.
* Fix IE throwing error with textareas when width or height was set to 'none'.

## 1.6.2

* Fix problems when xhtml is served application/xhtml+xml.

## 1.6.1

* Submit method can now be POST (default) or PUT.
* Fix form being submitted twice in some cases.

## 1.6.0

* Onblur parameter can now be a function.
* Support for any arbitrary event for triggering Jeditable
* Submitting of form will be canceled if submit() method of custom input returns false. 
* Custom inputs now have access to reset() method. 
