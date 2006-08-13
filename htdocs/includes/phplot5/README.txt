This is a class for creating scientific and business charts.
To start extract the files with

    tar zxvf phplot-5.0rc2.tar.gz

and then point your browser to

    doc/index.php.

There are some configuration settings that you will need to make
based on your setup.

1. File Type: Depending on the version of GD you are using,
   you may or may not be able to use GIF or PNG. That is
   set with the function.

        SetFileFormat("<filetype>") where <filetype> is png, gif, jpeg, ...

   or edit the file phplot.php and change the line

        var $file_format = "<filetype>";

2. TTF: If you have TTF installed then use (and read the docs)

        SetUseTTF(TRUE);

   otherwise use

        SetUseTTF(FALSE);

Everything else should be independent of what version you are using.
This has been tested with PHP3, PHP4, GD1.2 and GD 3.8.

To start please see doc/index.php. There you'll find examples, tests and
some introductory documents.

--------------------------

This is distributed with NO WARRANTY and under the terms of the GNU GPL
and PHP licenses. If you use it - a cookie or some credit would be nice.

You can get a copy of the GNU GPL at http://www.gnu.org/copyleft/gpl.html
You can get a copy of the PHP License at http://www.php.net/license.html

See http://www.sourceforge.net/projects/phplot/ for the latest changes.
