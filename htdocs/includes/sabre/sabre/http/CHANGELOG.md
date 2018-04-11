ChangeLog
=========

4.2.2 (2017-01-02)
------------------

* #72: Handling clients that send invalid `Content-Length` headers.


4.2.1 (2016-01-06)
------------------

* #56: `getBodyAsString` now returns at most as many bytes as the contents of
  the `Content-Length` header. This allows users to pass much larger strings
  without having to copy and truncate them.
* The client now sets a default `User-Agent` header identifying this library.


4.2.0 (2016-01-04)
------------------

* This package now supports sabre/event 3.0.


4.1.0 (2015-09-04)
------------------

* The async client wouldn't `wait()` for new http requests being started
  after the (previous) last request in the queue was resolved.
* Added `Sabre\HTTP\Auth\Bearer`, to easily extract a OAuth2 bearer token.


4.0.0 (2015-05-20)
------------------

* Deprecated: All static functions from `Sabre\HTTP\URLUtil` and
  `Sabre\HTTP\Util` moved to a separate `functions.php`, which is also
  autoloaded. The old functions are still there, but will be removed in a
  future version. (#49)


4.0.0-alpha3 (2015-05-19)
-------------------------

* Added a parser for the HTTP `Prefer` header, as defined in [RFC7240][rfc7240].
* Deprecated `Sabre\HTTP\Util::parseHTTPDate`, use `Sabre\HTTP\parseDate()`.
* Deprecated `Sabre\HTTP\Util::toHTTPDate` use `Sabre\HTTP\toDate()`.


4.0.0-alpha2 (2015-05-18)
-------------------------

* #45: Don't send more data than what is promised in the HTTP content-length.
  (@dratini0).
* #43: `getCredentials` returns null if incomplete. (@Hywan)
* #48: Now using php-cs-fixer to make our CS consistent (yay!)
* This includes fixes released in version 3.0.5.


4.0.0-alpha1 (2015-02-25)
-------------------------

* #41: Fixing bugs related to comparing URLs in `Request::getPath()`.
* #41: This library now uses the `sabre/uri` package for uri handling.
* Added `421 Misdirected Request` from the HTTP/2.0 spec.


3.0.5 (2015-05-11)
------------------

* #47 #35: When re-using the client and doing any request after a `HEAD`
  request, the client discards the body.


3.0.4 (2014-12-10)
------------------

* #38: The Authentication helpers no longer overwrite any existing
  `WWW-Authenticate` headers, but instead append new headers. This ensures
  that multiple authentication systems can exist in the same environment.


3.0.3 (2014-12-03)
------------------

* Hiding `Authorization` header value from `Request::__toString`.


3.0.2 (2014-10-09)
------------------

* When parsing `Accept:` headers, we're ignoring invalid parts. Before we
  would throw a PHP E_NOTICE.


3.0.1 (2014-09-29)
------------------

* Minor change in unittests.


3.0.0 (2014-09-23)
------------------

* `getHeaders()` now returns header values as an array, just like psr/http.
* Added `hasHeader()`.


2.1.0-alpha1 (2014-09-15)
-------------------------

* Changed: Copied most of the header-semantics for the PSR draft for
  representing HTTP messages. [Reference here][psr-http].
* This means that `setHeaders()` does not wipe out every existing header
  anymore.
* We also support multiple headers with the same name.
* Use `Request::getHeaderAsArray()` and `Response::getHeaderAsArray()` to
  get a hold off multiple headers with the same name.
* If you use `getHeader()`, and there's more than 1 header with that name, we
  concatenate all these with a comma.
* `addHeader()` will now preserve an existing header with that name, and add a
  second header with the same name.
* The message class should be a lot faster now for looking up headers. No more
  array traversal, because we maintain a tiny index.
* Added: `URLUtil::resolve()` to make resolving relative urls super easy.
* Switched to PSR-4.
* #12: Circumventing CURL's FOLLOW_LOCATION and doing it in PHP instead. This
  fixes compatibility issues with people that have open_basedir turned on.
* Added: Content negotiation now correctly support mime-type parameters such as
  charset.
* Changed: `Util::negotiate()` is now deprecated. Use
  `Util::negotiateContentType()` instead.
* #14: The client now only follows http and https urls.


2.0.4 (2014-07-14)
------------------

* Changed: No longer escaping @ in urls when it's not needed.
* Fixed: #7: Client now correctly deals with responses without a body.


2.0.3 (2014-04-17)
------------------

* Now works on hhvm!
* Fixed: Now throwing an error when a Request object is being created with
  arguments that were valid for sabre/http 1.0. Hopefully this will aid with
  debugging for upgraders.


2.0.2 (2014-02-09)
------------------

* Fixed: Potential security problem in the client.


2.0.1 (2014-01-09)
------------------

* Fixed: getBodyAsString on an empty body now works.
* Fixed: Version string


2.0.0 (2014-01-08)
------------------

* Removed: Request::createFromPHPRequest. This is now handled by
  Sapi::getRequest.


2.0.0alpha6 (2014-01-03)
------------------------

* Added: Asynchronous HTTP client. See examples/asyncclient.php.
* Fixed: Issue #4: Don't escape colon (:) when it's not needed.
* Fixed: Fixed a bug in the content negotation script.
* Fixed: Fallback for when CURLOPT_POSTREDIR is not defined (mainly for hhvm).
* Added: The Request and Response object now have a `__toString()` method that
  serializes the objects into a standard HTTP message. This is mainly for
  debugging purposes.
* Changed: Added Response::getStatusText(). This method returns the
  human-readable HTTP status message. This part has been removed from
  Response::getStatus(), which now always returns just the status code as an
  int.
* Changed: Response::send() is now Sapi::sendResponse($response).
* Changed: Request::createFromPHPRequest is now Sapi::getRequest().
* Changed: Message::getBodyAsStream and Message::getBodyAsString were added. The
  existing Message::getBody changed it's behavior, so be careful.


2.0.0alpha5 (2013-11-07)
------------------------

* Added: HTTP Status 451 Unavailable For Legal Reasons. Fight government
  censorship!
* Added: Ability to catch and retry http requests in the client when a curl
  error occurs.
* Changed: Request::getPath does not return the query part of the url, so
  everything after the ? is stripped.
* Added: a reverse proxy example.


2.0.0alpha4 (2013-08-07)
------------------------

* Fixed: Doing a GET request with the client uses the last used HTTP method
  instead.
* Added: HttpException
* Added: The Client class can now automatically emit exceptions when HTTP errors
  occurred.


2.0.0alpha3 (2013-07-24)
------------------------

* Changed: Now depends on sabre/event package.
* Changed: setHeaders() now overwrites any existing http headers.
* Added: getQueryParameters to RequestInterface.
* Added: Util::negotiate.
* Added: RequestDecorator, ResponseDecorator.
* Added: A very simple HTTP client.
* Added: addHeaders() to append a list of new headers.
* Fixed: Not erroring on unknown HTTP status codes.
* Fixed: Throwing exceptions on invalid HTTP status codes (not 3 digits).
* Fixed: Much better README.md
* Changed: getBody() now uses a bitfield to specify what type to return.


2.0.0alpha2 (2013-07-02)
------------------------

* Added: Digest & AWS Authentication.
* Added: Message::getHttpVersion and Message::setHttpVersion.
* Added: Request::setRawServerArray, getRawServerValue.
* Added: Request::createFromPHPRequest
* Added: Response::send
* Added: Request::getQueryParameters
* Added: Utility for dealing with HTTP dates.
* Added: Request::setPostData and Request::getPostData.
* Added: Request::setAbsoluteUrl and Request::getAbsoluteUrl.
* Added: URLUtil, methods for calculation relative and base urls.
* Removed: Response::sendBody


2.0.0alpha1 (2012-10-07)
------------------------

* Fixed: Lots of small naming improvements
* Added: Introduction of Message, MessageInterface, Response, ResponseInterface.

Before 2.0.0, this package was built-into SabreDAV, where it first appeared in
January 2009.

[psr-http]: https://github.com/php-fig/fig-standards/blob/master/proposed/http-message.md
[rfc-7240]: http://tools.ietf.org/html/rfc7240
