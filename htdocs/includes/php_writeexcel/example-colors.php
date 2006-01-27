<?php

# Demonstrates Spreadsheet::WriteExcel's named colors and the Excel
# color palette.
#
# reverse('©'), March 2002, John McNamara, jmcnamara@cpan.org

# PHP port by Johann Hanne, 2005-11-01

set_time_limit(10);

require_once "class.writeexcel_workbook.inc.php";
require_once "class.writeexcel_worksheet.inc.php";

$fname = tempnam("/tmp", "colors.xls");
$workbook = &new writeexcel_workbook($fname);

# Some common formats
$center  =& $workbook->addformat(array('align' => 'center'));
$heading =& $workbook->addformat(array('align' => 'center', 'bold' => 1));

# Try this to see the default Excel 5 palette
# $workbook->set_palette_xl5();

######################################################################
#
# Demonstrate the named colors.
#

$colors = array(
                'black'=>0x08,
                'blue'=>0x0C,
                'brown'=>0x10,
                'cyan'=>0x0F,
                'gray'=>0x17,
                'green'=>0x11,
                'lime'=>0x0B,
                'magenta'=>0x0E,
                'navy'=>0x12,
                'orange'=>0x35,
                'purple'=>0x14,
                'red'=>0x0A,
                'silver'=>0x16,
                'white'=>0x09,
                'yellow'=>0x0D
               );

$worksheet1 =& $workbook->addworksheet('Named colors');

$worksheet1->set_column(0, 3, 15);

$worksheet1->write(0, 0, "Index", $heading);
$worksheet1->write(0, 1, "Index", $heading);
$worksheet1->write(0, 2, "Name",  $heading);
$worksheet1->write(0, 3, "Color", $heading);

$i = 1;

foreach ($colors as $color=>$index) {
   $format =& $workbook->addformat(array(
                                        'fg_color' => $color,
                                        'pattern'  => 1,
                                        'border'   => 1
                                     ));

    $worksheet1->write($i+1, 0, $index,                    $center);
    $worksheet1->write($i+1, 1, sprintf("0x%02X", $index), $center);
    $worksheet1->write($i+1, 2, $color,                    $center);
    $worksheet1->write($i+1, 3, '',                        $format);
    $i++;
}


######################################################################
#
# Demonstrate the standard Excel colors in the range 8..63.
#

$worksheet2 =& $workbook->addworksheet('Standard colors');

$worksheet2->set_column(0, 3, 15);

$worksheet2->write(0, 0, "Index", $heading);
$worksheet2->write(0, 1, "Index", $heading);
$worksheet2->write(0, 2, "Color", $heading);
$worksheet2->write(0, 3, "Name",  $heading);

for ($i=8;$i<=63;$i++) {
    $format =& $workbook->addformat(array(
                                        'fg_color' => $i,
                                        'pattern'  => 1,
                                        'border'   => 1
                                     ));

    $worksheet2->write(($i -7), 0, $i,                    $center);
    $worksheet2->write(($i -7), 1, sprintf("0x%02X", $i), $center);
    $worksheet2->write(($i -7), 2, '',                    $format);

    # Add the  color names
    foreach ($colors as $color=>$index) {
      if ($i==$index) {
        $worksheet2->write(($i -7), 3, $color, $center);
      }
    }
}

$workbook->close();

header("Content-Type: application/x-msexcel; name=\"example-colors.xls\"");
header("Content-Disposition: inline; filename=\"example-colors.xls\"");
$fh=fopen($fname, "rb");
fpassthru($fh);
unlink($fname);

?>
