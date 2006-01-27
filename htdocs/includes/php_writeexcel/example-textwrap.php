<?php

require_once "class.writeexcel_workbook.inc.php";
require_once "class.writeexcel_worksheet.inc.php";

$fname = tempnam("/tmp", "textwrap.xls");
$workbook = &new writeexcel_workbook($fname);
$worksheet = &$workbook->addworksheet();

# Set the column width for columns 1, 2 and 3
$worksheet->set_column(1, 1, 24);
$worksheet->set_column(2, 2, 34);
$worksheet->set_column(3, 3, 34);

# Set the row height for rows 1, 4, and 6. The heigt of row 2 will adjust
# automatically to fit the text.
#
$worksheet->set_row(0, 30);
$worksheet->set_row(3, 40);
$worksheet->set_row(5, 80);

# No newlines
$str1  = "For whatever we lose (like a you or a me) ";
$str1 .= "it's always ourselves we find in the sea";

# Embedded newlines
$str2  = "For whatever we lose\n(like a you or a me)\n";
$str2 .= "it's always ourselves\nwe find in the sea";


# Create a format for the column headings
$header =& $workbook->addformat();
$header->set_bold();
$header->set_font("Courier New");
$header->set_align('center');
$header->set_align('vcenter');

# Create a "vertical justification" format
$format1 =& $workbook->addformat();
$format1->set_align('vjustify');

# Create a "text wrap" format
$format2 =& $workbook->addformat();
$format2->set_text_wrap();

# Write the headers
$worksheet->write(0, 1, "set_align('vjustify')", $header);
$worksheet->write(0, 2, "set_align('vjustify')", $header);
$worksheet->write(0, 3, "set_text_wrap()", $header);

# Write some examples
$worksheet->write(1, 1, $str1, $format1);
$worksheet->write(1, 2, $str1, $format1);
$worksheet->write(1, 3, $str2, $format2);

$worksheet->write(3, 1, $str1, $format1);
$worksheet->write(3, 2, $str1, $format1);
$worksheet->write(3, 3, $str2, $format2);

$worksheet->write(5, 1, $str1, $format1);
$worksheet->write(5, 2, $str1, $format1);
$worksheet->write(5, 3, $str2, $format2);

$workbook->close();

header("Content-Type: application/x-msexcel; name=\"example-textwrap.xls\"");
header("Content-Disposition: inline; filename=\"example-textwrap.xls\"");
$fh=fopen($fname, "rb");
fpassthru($fh);
unlink($fname);

?>
