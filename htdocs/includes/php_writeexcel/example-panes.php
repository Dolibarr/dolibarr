<?php

# Example of using the WriteExcel module to create worksheet panes.
#
# reverse('©'), May 2001, John McNamara, jmcnamara@cpan.org

# PHP port by Johann Hanne, 2005-11-01

set_time_limit(10);

require_once "class.writeexcel_workbook.inc.php";
require_once "class.writeexcel_worksheet.inc.php";

$fname = tempnam("/tmp", "panes.xls");
$workbook = &new writeexcel_workbook($fname);

$worksheet1 =& $workbook->addworksheet('Panes 1');
$worksheet2 =& $workbook->addworksheet('Panes 2');
$worksheet3 =& $workbook->addworksheet('Panes 3');
$worksheet4 =& $workbook->addworksheet('Panes 4');

# Frozen panes
$worksheet1->freeze_panes(1, 0); # 1 row
$worksheet2->freeze_panes(0, 1); # 1 column
$worksheet3->freeze_panes(1, 1); # 1 row and column

# Un-frozen panes. The divisions must be specified in terms of row and column
# dimensions. The default row height is 12.75 and the default column width
# is 8.43
#
$worksheet4->thaw_panes(12.75, 8.43, 1, 1); # 1 row and column




#######################################################################
#
# Set up some formatting and text to highlight the panes
#

$header =& $workbook->addformat();
$header->set_color('white');
$header->set_align('center');
$header->set_align('vcenter');
$header->set_pattern();
$header->set_fg_color('green');

$center =& $workbook->addformat();
$center->set_align('center');


#######################################################################
#
# Sheet 1
#

$worksheet1->set_column('A:I', 16);
$worksheet1->set_row(0, 20);
$worksheet1->set_selection('C3');

for ($i=0;$i<=8;$i++) {
    $worksheet1->write(0, $i, 'Scroll down', $header);
}

for ($i=1;$i<=100;$i++) {
    for ($j=0;$j<=8;$j++) {
        $worksheet1->write($i, $j, $i+1, $center);
    }
}


#######################################################################
#
# Sheet 2
#

$worksheet2->set_column('A:A', 16);
$worksheet2->set_selection('C3');

for ($i=0;$i<=49;$i++) {
    $worksheet2->set_row($i, 15);
    $worksheet2->write($i, 0, 'Scroll right', $header);
}

for ($i=0;$i<=49;$i++) {
    for ($j=1;$j<=25;$j++) {
        $worksheet2->write($i, $j, $j, $center);
    }
}


#######################################################################
#
# Sheet 3
#

$worksheet3->set_column('A:Z', 16);
$worksheet3->set_selection('C3');

for ($i=1;$i<=25;$i++) {
    $worksheet3->write(0, $i, 'Scroll down',  $header);
}

for ($i=1;$i<=49;$i++) {
    $worksheet3->write($i, 0, 'Scroll right', $header);
}

for ($i=1;$i<=49;$i++) {
    for ($j=1;$j<=25;$j++) {
        $worksheet3->write($i, $j, $j, $center);
    }
}


#######################################################################
#
# Sheet 4
#

$worksheet4->set_selection('C3');

for ($i=1;$i<=25;$i++) {
    $worksheet4->write(0, $i, 'Scroll', $center);
}

for ($i=1;$i<=49;$i++) {
    $worksheet4->write($i, 0, 'Scroll', $center);
}

for ($i=1;$i<=49;$i++) {
    for ($j=1;$j<=25;$j++) {
        $worksheet4->write($i, $j, $j, $center);
    }
}

$workbook->close();

header("Content-Type: application/x-msexcel; name=\"example-panes.xls\"");
header("Content-Disposition: inline; filename=\"example-panes.xls\"");
$fh=fopen($fname, "rb");
fpassthru($fh);
unlink($fname);

?>
