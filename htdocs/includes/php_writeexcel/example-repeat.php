<?php

set_time_limit(10);

require_once "class.writeexcel_workbook.inc.php";
require_once "class.writeexcel_worksheet.inc.php";

$fname = tempnam("/tmp", "repeat.xls");
$workbook = &new writeexcel_workbook($fname);
$worksheet = &$workbook->addworksheet();

$worksheet->repeat_rows(0, 1);

$worksheet->write(0, 0, "Header line (will be repeated when printed)");
$worksheet->write(1, 0, "Header line number 2");

for ($i=1;$i<=100;$i++) {
  $worksheet->write($i+1, 0, "Line $i");
}

$workbook->close();

header("Content-Type: application/x-msexcel; name=\"example-repeat.xls\"");
header("Content-Disposition: inline; filename=\"example-repeat.xls\"");
$fh=fopen($fname, "rb");
fpassthru($fh);
unlink($fname);

?>
