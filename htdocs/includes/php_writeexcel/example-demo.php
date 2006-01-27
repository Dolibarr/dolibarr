<?php

set_time_limit(10);

require_once "class.writeexcel_workbook.inc.php";
require_once "class.writeexcel_worksheet.inc.php";

$fname = tempnam("/tmp", "demo.xls");
$workbook =& new writeexcel_workbook($fname);
$worksheet =& $workbook->addworksheet('Demo');
$worksheet2 =& $workbook->addworksheet('Another sheet');
$worksheet3 =& $workbook->addworksheet('And another');

#######################################################################
#
# Write a general heading
#
$worksheet->set_column('A:B', 32);
$heading  =& $workbook->addformat(array(
                                        bold    => 1,
                                        color   => 'blue',
                                        size    => 18,
                                        merge   => 1,
                                        ));

$headings = array('Features of php_writeexcel', '');
$worksheet->write_row('A1', $headings, $heading);

#######################################################################
#
# Some text examples
#
$text_format =& $workbook->addformat(array(
                                            bold    => 1,
                                            italic  => 1,
                                            color   => 'red',
                                            size    => 18,
                                            font    => 'Comic Sans MS'
                                        ));

$worksheet->write('A2', "Text");
$worksheet->write('B2', "Hello Excel");
$worksheet->write('A3', "Formatted text");
$worksheet->write('B3', "Hello Excel", $text_format);

#######################################################################
#
# Some numeric examples
#
$num1_format =& $workbook->addformat(array(num_format => '$#,##0.00'));
$num2_format =& $workbook->addformat(array(num_format => ' d mmmm yyy'));

$worksheet->write('A4', "Numbers");
$worksheet->write('B4', 1234.56);
$worksheet->write('A5', "Formatted numbers");
$worksheet->write('B5', 1234.56, $num1_format);
$worksheet->write('A6', "Formatted numbers");
$worksheet->write('B6', 37257, $num2_format);

#######################################################################
#
# Formulae
#
$worksheet->set_selection('B7');
$worksheet->write('A7', 'Formulas and functions, "=SIN(PI()/4)"');
$worksheet->write('B7', '=SIN(PI()/4)');

#######################################################################
#
# Hyperlinks
#
$worksheet->write('A8', "Hyperlinks");
$worksheet->write('B8',  'http://www.php.net/');

#######################################################################
#
# Images
#
$worksheet->write('A9', "Images");
$worksheet->insert_bitmap('B9', 'php.bmp', 16, 8);

#######################################################################
#
# Misc
#
$worksheet->write('A17', "Page/printer setup");
$worksheet->write('A18', "Multiple worksheets");

$workbook->close();

header("Content-Type: application/x-msexcel; name=\"example-demo.xls\"");
header("Content-Disposition: inline; filename=\"example-demo.xls\"");
$fh=fopen($fname, "rb");
fpassthru($fh);
unlink($fname);

?>
