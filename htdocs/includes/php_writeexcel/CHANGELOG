0.3.0 (2005-11-01):
- Fixed cell merging (fix contributed by Nicolas Kahn - thanks!)
- Corrected the README: formulas are supported since version 0.2.2
- Added a method named set_codepage() to the workbook object which
  allows to set the codepage (fixes German Umlauts on Macs for
  example, contributed by Stefan Grünig - thanks!)
- Changed all calls of call_user_method_array() to call_user_func_array()
  as recent PHP versions complain about call_user_method_array() being
  deprecated (reported by Julien Detante and Luc Cessieux)
- Added $_debug to the class variable declaration of 
  class.writeexcel_biffwriter.inc.php and 
  class.writeexcel_worksheet.inc.php so that PHP doesn't complain about it
- The demos now produce proper headers ("Content-Type" and 
  "Content-Disposition") so that the correct application gets loaded
  when an XLS file is received more reliably (thanks to
  C. Mourad Jaber)
- Temporary files are now deleted (reported by Siggi Oskarsson)
- Added example-colors.php
- Fixed frozen and thawed panes (reported by Roger Jochem and Flavio 
  Ciotola)
- Added example-panes.php to prove that it's really fixed ;-)
- Added example-repeat.php because somebody reported that it doesn't
  work - it does work for me, please report again if it fails for you
  and send me your code
- Fixed cell range formulas like '$A$1:$B$2' which should now work
  (thanks Cedric Chandon)
- Fixed the PHP 5 incompatibility (reported by several people, patch
  by Jean Pasdeloup, thanks!)

0.2.2 (2003-10-08):
- Cleaned up class.writeexcel_biffwriter.inc.php
- Cleaned up _append() and _prepend() in class.writeexcel_biffwriter.inc.php
  and class.writeexcel_worksheet.inc.php to no longer use func_get_args()
  but require exactly one argument instead (internal api change)
- Changed class to support tmpfile. Because of this it is possible now to
  create an excel sheet on the fly that can be send by web server and is
  afterwards deleted. To provoke this behaviour you just pass an empty file
  name and later on use the writexcel_workbook->send(filename) function.
  (contributed by Andreas Brodowski)
- Imported and changed the PEAR::Spreadsheet Parser.php file to allow
  formulas in worksheets (contributed by Andreas Brodowski)

0.2.1 (2002-11-11):
- Corrected two typos in class.writeexcel_format.inc.php
  (thanks to Peter van Aarle)
- Fixed cell bg_color (thanks to Jason McCarver for the patch)
- Added support for Big/Little Endian (thanks to Marc Dilasser)
- Added FAQ
- Random cleanups

0.2.0 (2002-09-04):
- Added missing methods set_bg_color(), set_rotation(), set_text_justlast(),
  set_locked(), set_hidden(), set_font_script(), set_font_shadow(),
  set_font_outline() and set_font_strikeout() to
  class.writeexcel_format.inc.php
- Cleaned up class.writeexcel_format.inc.php
- Moved $monthdays() in functions.writeexcel_utility.inc.php into
  xl_date_list()
- Fixed all calls to call_user_func_array() and call_user_method_array()
  where the first parameter was an undefined constant (thanks Arnaud Limbourg)
- Cleaned up class.writeexcel_olewriter.inc.php
- Temporarily moved class.writeexcel_formula.inc.php out of the archive
  because formulas are not yet supported
- Added support for files bigger than 7 MB
  (class.writeexcel_workbookbig.inc.php), requires php_ole
- Added example which creates a file bigger than 7 MB (example-bigfile.php)
- Changed dates in CHANGELOG to international notation
- Updated the README file

0.1.2 (2002-08-10):
- Fixed xl_date_list() in class.writeexcel_utility.inc.php
- Cleaned up class.writeexcel_utility.inc.php
- Renamed class.writeexcel_utility.inc.php to
  functions.writeexcel_utility.inc.php (it isn't actually a class)
- Changed "pack("NN", 0xD0CF11E0, 0xA1B11AE1)" to
  "pack("C8", 0xD0, 0xCF, 0x11, 0xE0, 0xA1, 0xB1, 0x1A, 0xE1)"
  in class.writeexcel_olewriter.inc.php which seems to work around a bug in
  some PHP versions (thanks to Maître Reizz for this one!)

0.1.1 (2002-07-30):
- Fixed several PHP warnings regarding call-time pass-by-reference and
  undefined constants
- Changed "Spreadsheet::WriteExcel" to "php_writeexcel" in example-demo.php
- Changed fopen() calls to explicitely open files in binary mode

0.1.0 (2002-05-20):
- Added support for inserting bitmaps (insert_bitmap in writeexcel_worksheet)
- Some fixes for improved compatibility with the Spreadsheet::WriteExcel
  examples (see example-*.php)
- Some random bugfixes
