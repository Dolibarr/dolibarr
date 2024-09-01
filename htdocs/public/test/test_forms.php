<?php
define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
//define("NOLOGIN",1);		// This means this output page does not require to be logged.
/*if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}*/

// Load Dolibarr environment
require '../../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Security
if ($dolibarr_main_prod) {
	accessforbidden('Access forbidden when $dolibarr_main_prod is set to 1');
}


/*
 * View
 */

llxHeader();

?>

This page is a sample of page using Dolibarr HTML widget methods. It is designed to make test with<br>
- css (add parameter &amp;theme=newtheme to test another theme or edit css of current theme)<br>
- jmobile (add parameter <a class="wordbreak" href="<?php echo $_SERVER["PHP_SELF"].'?dol_use_jmobile=1&dol_optimize_smallscreen=1'; ?>">dol_use_jmobile=1&amp;dol_optimize_smallscreen=1</a> and switch to small screen < 570 to enable with emulated jmobile)<br>
- no javascript / usage for bind people (add parameter <a class="wordbreak" href="<?php echo $_SERVER["PHP_SELF"].'?nojs=1'; ?>">nojs=1</a> to force disable javascript)<br>
- use with a text browser (add parameter <a class="wordbreak" href="<?php echo $_SERVER["PHP_SELF"].'?textbrowser=1'; ?>">textbrowser=1</a> to force detection of a text browser)<br>
<br><br>

<!--  Output to test html.form.class.php -->
<?php
$form = new Form($db);

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table

// Test1: form->selectDate using tzuser date
print "Test 1a: We must have here current date and hour for user (must match hour on browser). Note: Check your are logged so user TZ and DST are known.";
$offsettz = (empty($_SESSION['dol_tz']) ? 0 : $_SESSION['dol_tz']) * 60 * 60;
$offsetdst = (empty($_SESSION['dol_dst']) ? 0 : $_SESSION['dol_dst']) * 60 * 60;
print " (dol_tz=".$offsettz." dol_dst=".$offsetdst.")<br>\n";
print $form->selectDate(dol_now(), 'test1a', 1, 1, 0);

print '<br><br>'."\n";

print "Test 1b: We must have here current date with hours to 00:00.<br>";
print $form->selectDate('', 'test1b', 1, 1, 0);

print '<br><br>'."\n";

// Test2: form->selectDate using tzuser date
print "Test 2: We must have here 1970-01-01 selected (fields can be empty)<br>\n";
print $form->selectDate(dol_get_first_day(1970, 1, false), 'test2', 1, 1, 1);

print '<br><br>'."\n";

// Test3: form->selectDate for 1970-01-01 00:00:00
print "Test 3: We must have here 1970-01-01 00:00:00 selected (fields are mandatory)<br>\n";
print $form->selectDate(dol_get_first_day(1970, 1, false), 'test3', 1, 1, 0);

print '<br><br>'."\n";

// Test4a: a select
print "Test 4a: a select<br>\n";
$array = array(1=>'Value 1', 2=>'Value 2', 3=>'Value 3 with a very long text. aze eazeae e ae aeae a e a ea ea ea e a e aea e ae aeaeaeaze.');
$selected = 3;
print $form->selectarray('testselecta', $array, $selected, 1, 0, 0, '', 0, 0, 0, '', 'minwidth100', 1);
print '<br><br>';
print "Test 4b: a select<br>\n";
$array = array(1=>'Value 1', 2=>'Value 2', 3=>'Value 3');
$selected = 3;
print $form->selectarray('testselectb', $array, $selected, 1, 0, 0, '', 0, 0, 0, '', 'minwidth100', 1);
print '<br><br>'."\n";
print "Test 4c: Select array with addjscombo not forced<br>\n";
$array = array(1=>'Value 1', 2=>'Value 2', 3=>'Value 3');
print $form->selectarray('selectarray', $array, $selected, 1, 0, 0, '', 0, 0, 0, '', 'minwidth100', 0);

print '<br><br>'."\n";

print "Test 4d: a select with ajax refresh and with onchange call of url<br>\n";
$selected = -1;
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
$array = array(1=>'Value 1', 2=>'Value 2', 3=>'Value 3');
$arrayselected = array(1, 3);
print $form->multiselectarray('testmulti', $array, $arrayselected, '', 0, 'minwidth100');

print '<br><br>'."\n";

// Test6a: Upload of big files
print "Test 6a: Upload of big files<br>\n";
print "The file will be uploaded in the directory: documents/test/temp/<br>\n";

if (is_file(DOL_DOCUMENT_ROOT.'/includes/flowjs/flow.js')) {
	print '<button id="buttonbigupload" type="button">Browse...</button>';
	print '&nbsp;<span id="filespan">No file selected.</span>';
	print '<br><div class="progress-bar filepgbar taligncenter" role="progressbar" style="width:1%;display:none"><span class="small valigntop">0%</span></div>';
	print '<br><button type="button" style="display:none;" data-fileidentifier="" class="btn green-haze btn-circle cancelfileinput" id="filecancel">Cancel</button>';
	print '<script src="'.DOL_URL_ROOT.'/includes/flowjs/flow.js"></script>';
	print '<script>
	jQuery(document).ready(function() {
		var flow = new Flow({
			target:"'.DOL_URL_ROOT.'/core/ajax/flowjs-server.php",
			query:{module:"test", token:"'.newToken().'"},
			testChunks:false
		});
		';
	print 'if(flow.support){
			flow.assignBrowse(document.getElementById("buttonbigupload"));
			flow.on("fileAdded", function(file, event){
				console.log("Trigger event file added", file, event);
				$("#filespan").text(file.name);
				$("#filecancel").data("fileidentifier", file.uniqueIdentifier)
				$("#filecancel").show()
				$(".filepgbar").show();
				$(".filepgbar").attr("id",file.uniqueIdentifier+"pgbar")
			});
			flow.on("filesSubmitted", function(array,message){
				console.log("Trigger event file submitted");
				flow.upload()
			});
			flow.on("progress", function(){
				console.log("progress",flow.files);
				flow.files.forEach(function(element){
					console.log(element.progress());
					width = Math.round(element.progress()*100)
					width = width.toString()
					$("#"+element.uniqueIdentifier+"pgbar").width(width+"%")
					$("#"+element.uniqueIdentifier+"pgbar").children("span").text(width+"%")
				});
			});
			flow.on("fileSuccess", function(file,message){
				console.log("The file has been uploaded successfully",file,message);
			});
			$(".cancelfileinput").on("click", function(){
				filename = $(this).data("fileidentifier");
				file = flow.getFromUniqueIdentifier(filename);
				file.cancel();
				$("#"+file.uniqueIdentifier+"pgbar").hide();
				console.log("We remove file "+filename);
				$("#filespan").text("No file selected.");
				$(this).hide();
			})
			flow.on("fileError", function(file, message){
				console.log("Error on file upload",file, message);
				$("#"+file.uniqueIdentifier+"pgbar").width(20+"%");
				$("#"+file.uniqueIdentifier+"pgbar").children("span").text("ERROR UPLOAD");
			});
		}
	})
	';
	print '</script>';
} else {
	print "If this message displays, please add flow.js and flow.min.js files which can be found here: https://github.com/flowjs/flow.js and place the js lib in htdocs/includes/flowjs/<br>\n";
}

print '</div>';

// End of page
llxFooter();
$db->close();
