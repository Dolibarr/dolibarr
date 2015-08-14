<?php
//define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.
define('REQUIRE_JQUERY_MULTISELECT','select2');

require '../../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

if ($dolibarr_main_prod) {
	accessforbidden();
}

llxHeader();

?>

<h1>
This page is a sample of page using Dolibarr HTML widget methods. It is designed to make test with<br>
- css (add parameter &theme=newtheme to test another theme or edit css of current theme)<br>
- jmobile (add parameter <a href="<?php echo $_SERVER["PHP_SELF"].'?dol_use_jmobile=1&dol_optimize_smallscreen=1'; ?>">dol_use_jmobile=1&dol_optimize_smallscreen=1</a> to enable view with jmobile)<br>
- no javascript / usage for bind people (add parameter <a href="<?php echo $_SERVER["PHP_SELF"].'?nojs=1'; ?>">nojs=1</a> to force disable javascript)<br>
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

/*print '<br><br>'."\n";

print "Test 4c: a select with ajax refresh<br>\n";
//$array=array(0=>'',1=>'Search into xxx',2=>'Search into yyy',3=>'Search into zzz');
$array=array();
$selected=-1;
print $form->selectArrayAjax('testselectc', DOL_URL_ROOT.'/core/ajax/selecsearchbox.php', $selected, 1, 0, 0, 'style="min-width: 250px;"', 0, 0, 0, '', '', 1);
*/

print '<br><br>'."\n";

// Test4: a select
print "Test 4a: a select<br>\n";
$array=array(1=>'Value 1',2=>'Value 2',3=>'Value 3 ith a very long text. aze eazeae e ae aeae a e a ea ea ea e a e aea e ae aeaeaeaze.');
$selected=3;
print $form->selectarray('testselecta', $array, $selected, 1, 0, 0, 'style="min-width: 250px;"', 0, 0, 0, '', '', 1);
print '<br><br>';
print "Test 4b: a select<br>\n";
$array=array(1=>'Value 1',2=>'Value 2',3=>'Value 3');
$selected=3;
print $form->selectarray('testselectb', $array, $selected, 1, 0, 0, 'style="min-width: 250px;"', 0, 0, 0, '', '', 1);
print '<br><br>'."\n";
print "Test 4c: Select array with no js forced<br>\n";
$array=array(1=>'Value 1',2=>'Value 2',3=>'Value 3');
print $form->selectarray('selectarray',$array);

print '<br><br>'."\n";

// Test4d: form->select_thirdparty
print "Test 4d: Select thirdparty<br>\n";
print $form->select_company(0,'thirdpartytest');

print '<br><br>'."\n";

// Test4e: form->select_product
print "Test 4e: Select product (using ajax)<br>\n";
$form->select_produits(0,'producttest');

print '<br><br>'."\n";

// Test5: a multiselect
print "Test 5: a multiselect<br>\n";
$array=array(1=>'Value 1',2=>'Value 2',3=>'Value 3');
$arrayselected=array(1,3);
print $form->multiselectarray('testmulti', $array, $arrayselected, '', 0, '', 0, 250);


llxFooter();
$db->close();
