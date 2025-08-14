Contributor guide
=================

This project is open source, and is built on a voluntary basis by developers like you. You can help to improve it by contributing code & documentation to the repository, or by interacting with other developers and users on the issue tracker.

Where to ask a question
^^^^^^^^^^^^^^^^^^^^^^^

If you need to seek clarification, then you are welcome to post questions about the library to the `gfx-php issue tracker`_. They will be tagged ``question``, and closed once there is an answer.

If your question is incomplete or not specific to this project, then it will be tagged ``invalid`` and closed with a short explanation. We do this to keep the issue tracker focussed, on-topic and actionable by project contributors.

The StackOverflow help page `How do I ask a good question?`_ contains advice about writing complete questions.

.. _`How do I ask a good question?`: https://stackoverflow.com/help/how-to-ask

How to report a bug
^^^^^^^^^^^^^^^^^^^

We track bugs as GitHub issues. If something does not work as documented, then you are welcome to make a bug report on the `gfx-php issue tracker`_

When posting a bug:

- Check for open issues first. If the same issue has already been reported, then you should post any additional information to the existing thread.
- Describe what you are trying to do, and how the actual behaviour of the library differs from what you expected.
- Include a self-contained code snippet that demonstrates the issue, as PHP code formatted in a `code block`_
- Try to use images from the repository to demonstrate the problem. If you need to use a specific example image, then attach it to the issue in a ``.zip`` file.

Bug reports will stay open as long as they are actionable. Generally, this means that they can be replicated on the current stable release, and there is some expectation that the issue is solvable.

The title and tags on your bug may be edited so that it can be distinguished from other bugs.

.. _`code block`: https://help.github.com/articles/creating-and-highlighting-code-blocks/
.. _`gfx-php issue tracker`: https://github.com/mike42/gfx-php/issues

Feature requests
^^^^^^^^^^^^^^^^

You can also make suggestions for new features on the `gfx-php issue tracker`_. These are tagged ``enhancement``.

Please keep the scope and resources of the project in mind when making suggestions.

Development process
^^^^^^^^^^^^^^^^^^^

The project is hosted online on the services below:

:Code:
  https://github.com/mike42/gfx-php
:Continuous integration:
  https://travis-ci.org/mike42/gfx-php
:Code coverage reporting:
  https://coveralls.io/github/mike42/gfx-php
:Documentation:
  https://gfx-php.readthedocs.io/
:Package manager:
  https://packagist.org/packages/mike42/gfx-php

For a change to be accepted, it will first need to meet some basic technical criteria, such as passing existing unit tests, a style check, and not breaking any of the examples.

Secondly, it will need to pass a human review, to confirm that it improves the overall product. You are encouraged to submit changes which address one open issue, so that this review can be as constructive as possible.

Commands to use locally
-----------------------

To make code changes, fork the repository on GitHub, and set up
your local copy with composer.

.. code-block:: bash
     
   composer install

To run unit tests, execute:

.. code-block:: bash

   php vendor/bin/phpunit --coverage-text

To test all examples:

.. code-block:: bash

   mkdir -p tmp && (cd tmp && find ../example -name '*.php' -print0 | xargs -n 1 -0 sh -c 'echo $0; php $0 || exit 255')

To run a style check and fix formatting issues:

.. code-block:: bash

   php vendor/bin/phpcs --standard=psr2 src/ -n
   php vendor/bin/phpcbf --standard=psr2 src/ -n

Submitting changes
------------------

Changes should be submitted as a GitHub pull request to the ``master`` branch.

Licensing considerations
------------------------

You are not required to assign copyright for contributions to this project, but
you do need ensure that your changes are suitable for release under the project's
`copyleft` license.

If you hold the copyright to the submitted code, then indicate this in your pull request.

If you are thinking of including code which you do not hold the copyright to,
please post relevant details to the issue tracker first. Only works which are
licensed under an LGPL-2.1-compatible license may be combined with `gfx-php` code.

Release process
---------------

Git tags are automatically reflected as releases in packagist.

Release numbers approximately follow semantic versioning, and do not follow a particular schedule.

Updates are not typically made to old releases.
