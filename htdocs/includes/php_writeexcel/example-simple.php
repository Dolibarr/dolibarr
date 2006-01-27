<?php

set_time_limit(10);

require_once "class.writeexcel_workbook.inc.php";
require_once "class.writeexcel_worksheet.inc.php";

$fname = tempnam("/tmp", "simple.xls");
$workbook = &new writeexcel_workbook($fname);
$worksheet = &$workbook->addworksheet();

# The general syntax is write($row, $column, $token). Note that row and
# column are zero indexed
#

# Write some text
$worksheet->write(0, 0,  "Hi Excel!");

# Write some numbers
$worksheet->write(2, 0,  3);          # Writes 3
$worksheet->write(3, 0,  3.00000);    # Writes 3
$worksheet->write(4, 0,  3.00001);    # Writes 3.00001
$worksheet->write(5, 0,  3.14159);    # TeX revision no.?

# Write two formulas
$worksheet->write(7, 0,  '=A3 + A6');
$worksheet->write(8, 0,  '=IF(A5>3,"Yes", "No")');

# Write a hyperlink
$worksheet->write(10, 0, 'http://www.php.net/');

$workbook->close();

header("Content-Type: application/x-msexcel; name=\"example-simple.xls\"");
header("Content-Disposition: inline; filename=\"example-simple.xls\"");
$fh=fopen($fname, "rb");
fpassthru($fh);
unlink($fname);

?>
