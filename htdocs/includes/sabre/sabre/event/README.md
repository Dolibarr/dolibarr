sabre/event
===========

A lightweight library for event-based development in PHP.

This library provides the following event-based concepts:

1. EventEmitter.
2. Promises.
3. An event loop.
4. Co-routines.

Full documentation can be found on [the website][1].

Installation
------------

Make sure you have [composer][3] installed, and then run:

    composer require sabre/event "~3.0.0"

This package requires PHP 5.5. The 2.0 branch is still maintained as well, and
supports PHP 5.4.

Build status
------------

| branch | status |
| ------ | ------ |
| master | [![Build Status](https://travis-ci.org/fruux/sabre-event.svg?branch=master)](https://travis-ci.org/fruux/sabre-event) |
| 2.0    | [![Build Status](https://travis-ci.org/fruux/sabre-event.svg?branch=2.0)](https://travis-ci.org/fruux/sabre-event) |
| 1.0    | [![Build Status](https://travis-ci.org/fruux/sabre-event.svg?branch=1.0)](https://travis-ci.org/fruux/sabre-event) |
| php53  | [![Build Status](https://travis-ci.org/fruux/sabre-event.svg?branch=php53)](https://travis-ci.org/fruux/sabre-event) |


Questions?
----------

Head over to the [sabre/dav mailinglist][4], or you can also just open a ticket
on [GitHub][5].

Made at fruux
-------------

This library is being developed by [fruux](https://fruux.com/). Drop us a line for commercial services or enterprise support.

[1]: http://sabre.io/event/
[3]: http://getcomposer.org/
[4]: http://groups.google.com/group/sabredav-discuss
[5]: https://github.com/fruux/sabre-event/issues/
