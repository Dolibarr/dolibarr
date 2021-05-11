ChangeLog
=========

1.2.0 (2016-12-06)
------------------

* Now throwing `InvalidUriException` if a uri passed to the `parse` function
  is invalid or could not be parsed.
* #11: Fix support for URIs that start with a triple slash. PHP's `parse_uri()`
  doesn't support them, so we now have a pure-php fallback in case it fails.
* #9: Fix support for relative URI's that have a non-uri encoded colon `:` in
  them.


1.1.1 (2016-10-27)
------------------

* #10: Correctly support file:// URIs in the build() method. (@yuloh)


1.1.0 (2016-03-07)
------------------

* #6: PHP's `parse_url()` corrupts strings if they contain certain
  non ascii-characters such as Chinese or Hebrew. sabre/uri's `parse()`
  function now percent-encodes these characters beforehand.


1.0.1 (2015-04-28)
------------------

* #4: Using php-cs-fixer to automatically enforce conding standards.
* #5: Resolving to and building `mailto:` urls were not correctly handled.


1.0.0 (2015-01-27)
------------------

* Added a `normalize` function.
* Added a `buildUri` function.
* Fixed a bug in the `resolve` when only a new fragment is specified.

San José, CalConnect XXXII release!

0.0.1 (2014-11-17)
------------------

* First version!
* Source was lifted from sabre/http package.
* Provides a `resolve` and a `split` function.
* Requires PHP 5.4.8 and up.
