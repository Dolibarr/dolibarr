raven-php
=========

.. image:: https://secure.travis-ci.org/getsentry/raven-php.png?branch=master
   :target: http://travis-ci.org/getsentry/raven-php


raven-php is a PHP client for `Sentry <http://aboutsentry.com/>`_.

.. code-block:: php

    // Instantiate a new client with a compatible DSN
    $client = new Raven_Client('http://public:secret@example.com/1');

    // Capture a message
    $event_id = $client->getIdent($client->captureMessage('my log message'));
    if ($client->getLastError() !== null) {
        printf('There was an error sending the event to Sentry: %s', $client->getLastError());
    }

    // Capture an exception
    $event_id = $client->getIdent($client->captureException($ex));

    // Provide some additional data with an exception
    $event_id = $client->getIdent($client->captureException($ex, array(
        'extra' => array(
            'php_version' => phpversion()
        ),
    )));

    // Give the user feedback
    echo "Sorry, there was an error!";
    echo "Your reference ID is " . $event_id;

    // Install error handlers and shutdown function to catch fatal errors
    $error_handler = new Raven_ErrorHandler($client);
    $error_handler->registerExceptionHandler();
    $error_handler->registerErrorHandler();
    $error_handler->registerShutdownFunction();

Installation
------------

Install with Composer
~~~~~~~~~~~~~~~~~~~~~

If you're using `Composer <https://getcomposer.org/>`_ to manage
dependencies, you can add Raven with it.

::

    $ composer require raven/raven:$VERSION

(replace ``$VERSION`` with one of the available versions on `Packagist <https://packagist.org/packages/raven/raven>`_)
or to get the latest version off the master branch:

::

    $ composer require raven/raven:dev-master

Note that using unstable versions is not recommended and should be avoided. Also
you should define a maximum version, e.g. by doing ``>=0.6,<1.0`` or ``~0.6``.

Alternatively, use the ``^`` operator for specifying a version, e.g.,

::

    $ composer require raven/raven:^0.11.0

Composer will take care of the autoloading for you, so if you require the
``vendor/autoload.php``, you're good to go.


Install source from GitHub
~~~~~~~~~~~~~~~~~~~~~~~~~~

To install the source code:

::

    $ git clone git://github.com/getsentry/raven-php.git

And including it using the autoloader:

.. code-block:: php

    require_once '/path/to/Raven/library/Raven/Autoloader.php';
    Raven_Autoloader::register();

Testing Your Connection
-----------------------

The PHP client includes a simple helper script to test your connection and credentials with
the Sentry master server:

.. code-block:: bash

    $ bin/raven test https://public:secret@app.getsentry.com/1
    Client configuration:
    -> servers: [https://sentry.example.com/api/store/]
    -> project: 1
    -> public_key: public
    -> secret_key: secret

    Sending a test event:
    -> event ID: f1765c9aed4f4ceebe5a93df9eb2d34f

    Done!

.. note:: The CLI enforces the synchronous option on HTTP requests whereas the default configuration is asyncrhonous.

Configuration
-------------

Several options exist that allow you to configure the behavior of the ``Raven_Client``. These are passed as the
second parameter of the constructor, and is expected to be an array of key value pairs:

.. code-block:: php

    $client = new Raven_Client($dsn, array(
        'option_name' => 'value',
    ));

``name``
~~~~~~~~

A string to override the default value for the server's hostname.

Defaults to ``Raven_Compat::gethostname()``.

``tags``
~~~~~~~~

An array of tags to apply to events in this context.

.. code-block:: php

    'tags' => array(
        'php_version' => phpversion(),
    )


``curl_method``
~~~~~~~~~~~~~~~

Defaults to 'sync'.

Available methods:

- sync (default): send requests immediately when they're made
- async: uses a curl_multi handler for best-effort asynchronous submissions
- exec: asynchronously send events by forking a curl process for each item

``curl_path``
~~~~~~~~~~~~~

Defaults to 'curl'.

Specify the path to the curl binary to be used with the 'exec' curl method.


``trace``
~~~~~~~~~

Set this to ``false`` to disable reflection tracing (function calling arguments) in stacktraces.


``logger``
~~~~~~~~~~

Adjust the default logger name for messages.

Defaults to ``php``.

``ca_cert``
~~~~~~~~~~~

The path to the CA certificate bundle.

Defaults to the common bundle which includes getsentry.com: ./data/cacert.pem

Caveats:

- The CA bundle is ignored unless curl throws an error suggesting it needs a cert.
- The option is only currently used within the synchronous curl transport.

``curl_ssl_version``
~~~~~~~~~~~~~~~~~~~~

The SSL version (2 or 3) to use.
By default PHP will try to determine this itself, although in some cases this must be set manually.

``message_limit``
~~~~~~~~~~~~~~~~~

Defaults to 1024 characters.

This value is used to truncate message and frame variables. However it is not guarantee that length of whole message will be restricted by this value.

``processors``
~~~~~~~~~~~~~~~~~

An array of classes to use to process data before it is sent to Sentry. By default, Raven_SanitizeDataProcessor is used

``processorOptions``
~~~~~~~~~~~~~~~~~
Options that will be passed on to a setProcessorOptions() function in a Raven_Processor sub-class before that Processor is added to the list of processors used by Raven_Client

An example of overriding the regular expressions in Raven_SanitizeDataProcessor is below:

.. code-block:: php

    'processorOptions' => array(
        'Raven_SanitizeDataProcessor' => array(
                    'fields_re' => '/(user_password|user_token|user_secret)/i',
                    'values_re' => '/^(?:\d[ -]*?){15,16}$/'
                )
    )

Providing Request Context
-------------------------

Most of the time you're not actually calling out to Raven directly, but you still want to provide some additional context. This lifecycle generally constists of something like the following:

- Set some context via a middleware (e.g. the logged in user)
- Send all given context with any events during the request lifecycle
- Cleanup context

There are three primary methods for providing request context:

.. code-block:: php

    // bind the logged in user
    $client->user_context(array('email' => 'foo@example.com'));

    // tag the request with something interesting
    $client->tags_context(array('interesting' => 'yes'));

    // provide a bit of additional context
    $client->extra_context(array('happiness' => 'very'));


If you're performing additional requests during the lifecycle, you'll also need to ensure you cleanup the context (to reset its state):

.. code-block:: php

    $client->context->clear();


Contributing
------------

First, make sure you can run the test suite. Install development dependencies :

::

    $ composer install

You may now use phpunit :

::

    $ vendor/bin/phpunit



Resources
---------

* `Bug Tracker <http://github.com/getsentry/raven-php/issues>`_
* `Code <http://github.com/getsentry/raven-php>`_
* `Mailing List <https://groups.google.com/group/getsentry>`_
* `IRC <irc://irc.freenode.net/sentry>`_  (irc.freenode.net, #sentry)
