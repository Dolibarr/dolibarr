<?php
//define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK", 1);	// We accept to go on this page from external web site.
define('REQUIRE_JQUERY_MULTISELECT', 'select2');

require '../../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

if ($dolibarr_main_prod) {
	accessforbidden();
}

llxHeader();

?>

<h2>
This page is a sample of page using Dolibarr HTML widget methods. It is designed to make test with<br>
- css (add parameter &amp;theme=newtheme to test another theme or edit css of current theme)<br>
- jmobile (add parameter <a class="wordbreak" href="<?php echo $_SERVER["PHP_SELF"].'?dol_use_jmobile=1&dol_optimize_smallscreen=1'; ?>">dol_use_jmobile=1&amp;dol_optimize_smallscreen=1</a> and switch to small screen < 570 to enable with emulated jmobile)<br>
- no javascript / usage for bind people (add parameter <a class="wordbreak" href="<?php echo $_SERVER["PHP_SELF"].'?nojs=1'; ?>">nojs=1</a> to force disable javascript)<br>
</h2>
<br>

<!--  Output to test html.form.class.php -->
<?php
$form=new Form($db);

print '<div class="div-table-responsive">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table

// Test1: form->selectDate using tzuser date
print "Test 1a: We must have here current date and hour for user (must match hour on browser). Note: Check your are logged so user TZ and DST are known.";
$offsettz=(empty($_SESSION['dol_tz'])?0:$_SESSION['dol_tz'])*60*60;
$offsetdst=(empty($_SESSION['dol_dst'])?0:$_SESSION['dol_dst'])*60*60;
print " (dol_tz=".$offsettz." dol_dst=".$dol_dst.")<br>\n";
print $form->selectDate(dol_now(), 'test1a', 1, 1, 0);

print '<br><br>'."\n";

print "Test 1b: We must have here current date with hours to 00:00.<br>";
print $form->selectDate('', 'test1b', 1, 1, 0);

print '<br><br>'."\n";

// Test2: form->selectDate using tzuser date
print "Test 2: We must have here 1970-01-01 00:00:00 selected (fields can be empty)<br>\n";
print $form->selectDate(dol_get_first_day(1970, 1, false), 'test2', 1, 1, 1);

print '<br><br>'."\n";

// Test3: form->selectDate for 1970-01-01 00:00:00
print "Test 3: We must have here 1970-01-01 00:00:00 selected (fields are mandatory)<br>\n";
print $form->selectDate(dol_get_first_day(1970, 1, false), 'test3', 1, 1, 0);

print '<br><br>'."\n";

// Test4a: a select
print "Test 4a: a select<br>\n";
$array=array(1=>'Value 1',2=>'Value 2',3=>'Value 3 ith a very long text. aze eazeae e ae aeae a e a ea ea ea e a e aea e ae aeaeaeaze.');
$selected=3;
print $form->selectarray('testselecta', $array, $selected, 1, 0, 0, '', 0, 0, 0, '', 'minwidth100', 1);
print '<br><br>';
print "Test 4b: a select<br>\n";
$array=array(1=>'Value 1',2=>'Value 2',3=>'Value 3');
$selected=3;
print $form->selectarray('testselectb', $array, $selected, 1, 0, 0, '', 0, 0, 0, '', 'minwidth100', 1);
print '<br><br>'."\n";
print "Test 4c: Select array with addjscombo not forced<br>\n";
$array=array(1=>'Value 1',2=>'Value 2',3=>'Value 3');
print $form->selectarray('selectarray', $array, $selected, 1, 0, 0, '', 0, 0, 0, '', 'minwidth100', 0);

print '<br><br>'."\n";

print "Test 4d: a select with ajax refresh and with onchange call of url<br>\n";
$selected=-1;
print $form->selectArrayAjax('testselectc', DOL_URL_ROOT.'/core/ajax/selectsearchbox.php', $selected, '', '', 0, 1, 'minwidth100', 1);

print '<br><br>'."\n";

// Test5a: form->select_thirdparty
print "Test 5a: Select thirdparty<br>\n";
print $form->select_company(0, 'thirdpartytest', '', '', 0, 0, null, 0, 'minwidth100');

print '<br><br>'."\n";

// Test5b: form->select_product
print "Test 5b: Select product (using ajax)<br>\n";
$form->select_produits(0, 'producttest', '', 20, 0, 1, 2, '', 0, null, 0, '1', 0, 'minwidth100');

print '<br><br>'."\n";

// Test5c: a multiselect
print "Test 5c: a multiselect<br>\n";
$array=array(1=>'Value 1',2=>'Value 2',3=>'Value 3');
$arrayselected=array(1,3);
print $form->multiselectarray('testmulti', $array, $arrayselected, '', 0, 'minwidth100');

print '</div>';

// End of page
llxFooter();
$db->close();
