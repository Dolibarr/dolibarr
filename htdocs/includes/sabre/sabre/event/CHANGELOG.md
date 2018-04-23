ChangeLog
=========

3.0.0 (2015-11-05)
------------------

* Now requires PHP 5.5!
* `Promise::all()` is moved to `Promise\all()`.
* Aside from the `Promise\all()` function, there's now also `Promise\race()`.
* `Promise\reject()` and `Promise\resolve()` have also been added.
* Now 100% compatible with the Ecmascript 6 Promise.


3.0.0-alpha1 (2015-10-23)
-------------------------

* This package now requires PHP 5.5.
* #26: Added an event loop implementation. Also knows as the Reactor Pattern.
* Renamed `Promise::error` to `Promise::otherwise` to be consistent with
  ReactPHP and Guzzle. The `error` method is kept for BC but will be removed
  in a future version.
* #27: Support for Promise-based coroutines via the `Sabre\Event\coroutine`
  function.
* BC Break: Promises now use the EventLoop to run "then"-events in a separate
  execution context. In practise that means you need to run the event loop to
  wait for any `then`/`otherwise` callbacks to trigger.
* Promises now have a `wait()` method. Allowing you to make a promise
  synchronous and simply wait for a result (or exception) to happen.


2.0.2 (2015-05-19)
------------------

* This release has no functional changes. It's just been brought up to date
  with the latest coding standards.


2.0.1 (2014-10-06)
------------------

* Fixed: `$priority` was ignored in `EventEmitter::once` method.
* Fixed: Breaking the event chain was not possible in `EventEmitter::once`.


2.0.0 (2014-06-21)
------------------

* Added: When calling emit, it's now possible to specify a callback that will be
  triggered after each method handled. This is dubbed the 'continueCallback' and
  can be used to implement strategy patterns.
* Added: Promise object!
* Changed: EventEmitter::listeners now returns just the callbacks for an event,
  and no longer returns the list by reference. The list is now automatically
  sorted by priority.
* Update: Speed improvements.
* Updated: It's now possible to remove all listeners for every event.
* Changed: Now uses psr-4 autoloading.


1.0.1 (2014-06-12)
------------------

* hhvm compatible!
* Fixed: Issue #4. Compatiblitiy for PHP < 5.4.14.


1.0.0 (2013-07-19)
------------------

* Added: removeListener, removeAllListeners
* Added: once, to only listen to an event emitting once.
* Added README.md.


0.0.1-alpha (2013-06-29)
------------------------

* First version!
