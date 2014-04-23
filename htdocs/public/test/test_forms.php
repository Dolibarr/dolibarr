<?php
define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

require '../../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

if (empty($conf->global->MAIN_FEATURES_LEVEL))
{
	print "Page available onto dev environment only";
	exit;
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
<meta name="robots" content="noindex,nofollow" />
<meta name="author" content="Dolibarr Development Team">
<link rel="shortcut icon" type="image/x-icon" href="<?php echo DOL_URL_ROOT ?>/theme/eldy/img/favicon.ico"/>
<title>Test page</title>
<!-- Includes for JQuery (Ajax library) -->
<link rel="stylesheet" type="text/css" href="<?php echo DOL_URL_ROOT ?>/includes/jquery/css/smoothness/jquery-ui-latest.custom.css" />
<link rel="stylesheet" type="text/css" href="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/datatables/css/jquery.dataTables.css" />
<!-- <link rel="stylesheet" type="text/css" href="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/mobile/jquery.mobile-latest.min.css" /> -->
<!-- <link rel="stylesheet" type="text/css" title="default" href="<?php echo DOL_URL_ROOT ?>/theme/eldy/style.css.php?dol_use_jmobile=1" /> -->
<link rel="stylesheet" type="text/css" title="default" href="<?php echo DOL_URL_ROOT ?>/theme/eldy/style.css.php?dol_use_jmobile=0" />
<!-- Includes JS for JQuery -->
<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/js/jquery-latest.min.js"></script>
<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/tablednd/jquery.tablednd.0.6.min.js"></script>
<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/datatables/js/jquery.dataTables.js"></script>
<!-- <script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/mobile/jquery.mobile-latest.min.js"></script> -->
</head>



<body style="margin: 4px;">
<div data-role="page">
<br>
This page is a sample of page using html methods.<br>
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

?>

</div>
</body>
</html>