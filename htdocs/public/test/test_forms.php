<?php
//define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.
define('REQUIRE_JQUERY_MULTISELECT','select2');

require '../../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1')
{
	print "Page available only frome remote address 127.0.0.1";
	exit;
}


llxHeader();

?>

<h1>
This page is a sample of page using Dolibarr HTML widget methods. It is designed to make test with<br>
- css (edit page to change to test another css)<br>
- jmobile (add parameter dol_use_jmobile=1 to enable view with jmobile)<br>
</h1>
<br>

<!--  Output to test html.form.class.php -->
<?php
$form=new Form($db);

// Test1: form->select_date using tzuser date
print "Test 1: We must have here current hour for user (must match hour on browser). Note: Check your are logged so user TZ and DST are known.";
$offsettz=(empty($_SESSION['dol_tz'])?0:$_SESSION['dol_tz'])*60*60;
$offsetdst=(empty($_SESSION['dol_dst'])?0:$_SESSION['dol_dst'])*60*60;
print " (dol_tz=".$offsettz." dol_dst=".$dol_dst.")<br>\n";
$form->select_date('', 'test1', 1, 1, 0);

print '<br><br>'."\n";

// Test2: form->select_date using tzuser date
print "Test 2: We must have here 1970-01-01 00:00:00 selected (fields can be empty)<br>\n";
$form->select_date(dol_get_first_day(1970,1,false), 'test2', 1, 1, 1);

print '<br><br>'."\n";

// Test3: form->select_date for 1970-01-01 00:00:00
print "Test 3: We must have here 1970-01-01 00:00:00 selected (fields are mandatory)<br>\n";
$form->select_date(dol_get_first_day(1970,1,false), 'test3', 1, 1, 0);

print '<br><br>'."\n";

// Test4a: form->select_product
print "Test 4: Select product - ";
$form->select_produits(0,'producttest');

print '<br><br>'."\n";

// Test4b: form->selectarray
print "Test 4: Select array - ";
$array=array(1=>'Value 1',2=>'Value 2',3=>'Value 3');
$arrayselected=array(1,3);
print $form->selectarray('selectarray',$array);

print '<br><br>'."\n";

// Test5: a multiselect
print "Test 5: a multiselect<br>\n";
$array=array(1=>'Value 1',2=>'Value 2',3=>'Value 3');
$arrayselected=array(1,3);
print $form->multiselectarray('testmulti', $array, $arrayselected, '', 0, '', 0, 250);

print '<br><br>'."\n";

// Test6: a select
print "Test 6a: a select<br>\n";
$array=array(1=>'Value 1',2=>'Value 2',3=>'Value 3 ith a very long text. aze eazeae e ae aeae a e a ea ea ea e a e aea e ae aeaeaeaze.');
$selected=3;
print $form->selectarray('testselecta', $array, $selected, 1, 0, 0, 'style="min-width: 250px;"', 0, 0, 0, '', '', 1);
print '<br><br>';
print "Test 6b: a select<br>\n";
$array=array(1=>'Value 1',2=>'Value 2',3=>'Value 3');
$selected=3;
print $form->selectarray('testselectb', $array, $selected, 1, 0, 0, 'style="min-width: 250px;"', 0, 0, 0, '', '', 1);


llxFooter();
$db->close();
