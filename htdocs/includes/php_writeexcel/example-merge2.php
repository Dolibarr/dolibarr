<?php

set_time_limit(10);

require_once "class.writeexcel_workbook.inc.php";
require_once "class.writeexcel_worksheet.inc.php";

$fname = tempnam("/tmp", "merge2.xls");
$workbook = &new writeexcel_workbook($fname);
$worksheet = &$workbook->addworksheet();

# Set the column width for columns 2 and 3
$worksheet->set_column(1, 2, 20);

# Set the row height for row 2
$worksheet->set_row(2, 30);

# Create a border format
$border1 =& $workbook->addformat();
$border1->set_color('white');
$border1->set_bold();
$border1->set_size(15);
$border1->set_pattern(0x1);
$border1->set_fg_color('green');
$border1->set_border_color('yellow');
$border1->set_top(6);
$border1->set_bottom(6);
$border1->set_left(6);
$border1->set_align('center');
$border1->set_align('vcenter');
$border1->set_merge(); # This is the key feature

# Create another border format. Note you could use copy() here.
$border2 =& $workbook->addformat();
$border2->set_color('white');
$border2->set_bold();
$border2->set_size(15);
$border2->set_pattern(0x1);
$border2->set_fg_color('green');
$border2->set_border_color('yellow');
$border2->set_top(6);
$border2->set_bottom(6);
$border2->set_right(6);
$border2->set_align('center');
$border2->set_align('vcenter');
$border2->set_merge(); # This is the key feature

# Only one cell should contain text, the others should be blank.
$worksheet->write      (2, 1, "Merged Cells", $border1);
$worksheet->write_blank(2, 2,                 $border2);

$workbook->close();

header("Content-Type: application/x-msexcel; name=\"example-merge2.xls\"");
header("Content-Disposition: inline; filename=\"example-merge2.xls\"");
$fh=fopen($fname, "rb");
fpassthru($fh);
unlink($fname);

?>
