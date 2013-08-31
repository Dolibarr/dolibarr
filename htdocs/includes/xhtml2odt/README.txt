XHTML to ODT XML transformation
===============================

These stylesheets convert namespaced XHTML to ODT_.

You can download_ them from the project's website_.

The HTML must be well-formed and valid, so I recommand running it through Tidy_
before sending it to the stylesheets.

Those stylesheets do not build a whole ODT file, they only convert the XHTML to
the equivalent OpenDocument XML. The result can then be inserted in a template
ODT file. The HTML may be included in an ODT document, the ODT will be left
untouched.

.. _website: http://xhtml2odt.org/
.. _download: http://xhtml2odt.org/dl/
.. _ODT: http://en.wikipedia.org/wiki/OpenDocument
.. _Tidy: http://tidy.sourceforge.net/


Caveats
-------

Styles:
    Some default styles will be added to the document, but not on the first
    pass. After converting to ODT, you must run the ``content.xml`` and the
    ``styles.xml`` files (in the ODT file) through the ``xsl/styles.xsl``
    stylesheet to add the styles. When the default styles are added, the
    stylesheet checks that the style is not already present, so customizations
    to the template ODT file will be preserved. It will however add styles that
    are not used in the text, but that's harmless.

Images:
    Images are not added, you must manually go trough the ``draw:image``
    elements in the converted ODT text and use the ``xlink:href`` attribute to
    download or copy the image. While you're at it, you should update the image
    dimensions if the were not provided in the ``img`` tag.


Command-line scripts
--------------------

Three command-line scripts to run the stylesheets are provided, one is
Python-based, the other is PHP-based, the last one is shell-based. The first
two do import the styles and the images, so they can also be used as a code
example for these two steps in other languages and actual export plugins. The
shell script is more of a minimalist approach to demonstrate the simplest
possible use of the stylesheets.

Documentation for the PHP and Python scripts can be generated using the ``make
doc`` command. This will require Sphinx_ for Python and phpDocumentor_ for PHP.

.. _sphinx: http://sphinx.pocoo.org/
.. _phpDocumentor: http://www.phpdoc.org/

The python script
^^^^^^^^^^^^^^^^^

The python script is the preferred command-line script, because it currently is
a little more complete than the PHP script. It depends on the following Python
modules:

* uTidylib_
* lxml_
* PIL_

To get information on the script's options, run it with ``--help``::

    ./xhtml2odt.py --help

The script can be installed on the system with the ``make install`` command.

.. _uTidylib: http://pypi.python.org/pypi/uTidylib
.. _lxml: http://pypi.python.org/pypi/lxml
.. _PIL: http://pypi.python.org/pypi/PIL

The PHP script
^^^^^^^^^^^^^^

The PHP script can be used as an example to create an ODT export plugin for a
PHP-based application. It contains comments on what you should do differently
in a web-based application. If you want a real PHP-based export plugin, you can
look at the code of the `Dotclear ODT export plugin`_.

The PHP script requires the zip_ module, and will work better with the `tidy
extension`_.

To get information on the script's options, run it with ``--help``::

    ./xhtml2odt.php --help

.. _Dotclear ODT export plugin: http://lab.dotclear.org/wiki/plugin/odt
.. _zip: http://php.net/manual/en/zip.installation.php
.. _tidy extension: http://php.net/manual/en/book.tidy.php


Tests
-----

The unit tests are python-based, you need to install the nose_ python module
availble from PyPI (or your distribution).

Then, just run ``nosetests tests``.

.. _nose: http://pypi.python.org/pypi/nose/


References
----------

* `ODT export for Dotclear <http://lab.dotclear.org/wiki/plugin/odt>`_
* `ODT export for Trac <http://trac-hacks.org/wiki/OdtExportPlugin>`_
* `ODT export for Dokuwiki <http://www.dokuwiki.org/plugin:odt>`_
  (not using this project, but similar and by the same author)


License
-------

Copyright (C) 2009-2010 `Aurelien Bompard`_.

Inspired by the work on docbook2odt_, by Roman Fordinal. Many thanks to him.

.. _Aurelien Bompard: http://aurelien.bompard.org/
.. _docbook2odt: http://open.comsultia.com/docbook2odf/

License is LGPL v2.1 or later: http://www.gnu.org/licenses/lgpl-2.1.html

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA

.. vim:syntax=rst
