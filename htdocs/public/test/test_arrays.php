<?php
define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

require '../../main.inc.php';

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
<title>Login Dolibarr 3.4.0-alpha</title>
<!-- Includes for JQuery (Ajax library) -->
<link rel="stylesheet" type="text/css" href="<?php echo DOL_URL_ROOT ?>/includes/jquery/css/smoothness/jquery-ui-latest.custom.css" />
<link rel="stylesheet" type="text/css" href="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/datatables/css/jquery.dataTables.css" />
<link rel="stylesheet" type="text/css" href="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/mobile/jquery.mobile-latest.min.css" />
<link rel="stylesheet" type="text/css" title="default" href="<?php echo DOL_URL_ROOT ?>/theme/eldy/style.css.php?dol_use_jmobile=1" />
<!-- Includes JS for JQuery -->
<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/js/jquery-latest.min.js"></script>
<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/datatables/js/jquery.dataTables.js"></script>
<!--<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/mobile/jquery.mobile-latest.min.js"></script>-->
</head>



<body style="margin: 4px;">
<div data-role="page">
<br>
This page is a sample of page using tables. To make test with<br>
- css (edit page to change)<br>
- jmobile (edit page to enable/disable)<br>
- dataTables.<br>
<br>



<br>
Example 1 : Table using tags: div.tagtable+form+div or div.tagtable+div.tagtr+div.tagtd<br>

<div class="tagtable centpercent" id="abc">
    <form class="liste_titre" method="POST" action="1.php">
        <div>snake<input type="hidden" name="cartitem" value="1"></div>
        <div><label><input type="checkbox" name="hidedetails" value="2"> A checkbox inside a cell</label></div>
        <div><input name="count" value="4"></div>
        <div><input type="submit" name="count" class="button noshadow" value="aaa"></div>
    </form>
    <form class="impair" method="POST" action="2.php">
        <div>snagfdgfd  gd fgf ke<input type="hidden" name="cartitem" value="2"></div>
        <div>dfsdf</div>
        <div><input name="count" value="4"></div>
        <div><input type="submit" value="xxx" class="button"></div>
    </form>
    <div class="pair tagtr" method="GET" action="3.php">
        <div>snagfdgfd  gd fgf ke<input type="hidden" name="cartitem" value="3"></div>
        <div>dfsdf</div>
        <div><input name="count" value="4"></div>
        <div><input type="submit" value="zzz" class="button"></div>
    </div>
</div>



<br><br>



Example 2 : Table using tags: table/thead/tbody/tr/td + dataTable<br>

<script type="text/javascript">
/*$(document).ready(function(){
    $('#abc').dataTable();
});*/
$(document).ready(function(){
    $('#def').dataTable();
});

/*
// counts total number of td in a head so that we can can use it for label extraction
var head_col_count =  $('xxxthead td').size();
// loop which replaces td
for ( i=0; i <= head_col_count; i++ )  {
	// head column label extraction
	var head_col_label = $('xxxthead td:nth-child('+ i +')').text();
	// replaces td with <div class="column" data-label="label">
	$('xxxtr td:nth-child('+ i +')').replaceWith(
		function(){
			return $('<div class="column" data-label="'+ head_col_label +'">').append($(this).contents());
		}
	);
}
// replaces table with <div class="table">
$('xxxtable').replaceWith(
	function(){
		return $('<div class="table">').append($(this).contents());
	}
);
// replaces thead with <div class="table-head">
$('xxxthead').replaceWith(
	function(){
		return $('<div class="table-head">').append($(this).contents());
	}
);
// replaces tr with <div class="row">
$('xxxtr').replaceWith(
	function(){
		return $('<div class="row">').append($(this).contents());
	}
);
// replaces th with <div class="column">
$('xxxth').replaceWith(
	function(){
		return $('<div class="column">').append($(this).contents());
	}
);
*/
</script>

<table id="def">
	<thead>
    <tr>
        <th>snake</th>
        <th><label><input type="checkbox" name="hidedetails" value="2"> A checkbox inside a cell</label></th>
		<?php print getTitleFieldOfList($langs->trans('zzz'),1,$_SERVER["PHP_SELF"],'','','','align="center" class="tagtd"',$sortfield,$sortorder); ?>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>snagfdgfd  gd fgf ke</td>
        <td>dfsdf</td>
		<td> xxx </td>
    </tr>
    <tr>
        <td>snagfdgfd  gd fgf ke</td>
        <td>dfsdf</td>
        <td> xxx </td>
    </tr>
    <tr>
        <td>snagfdgfd  gd fgf ke</td>
        <td>dfsdf</td>
        <td> xxx </td>
    </tr>
    <tr>
        <td>snagfdgfd  gd fgf ke</td>
        <td>dfsdf</td>
        <td> xxx </td>
    </tr>
    <tr>
        <td>snagfdgfd  gd fgf ke</td>
        <td>dfsdf</td>
        <td> xxx </td>
    </tr>
    <tr>
        <td>snagfdgfd  gd fgf ke</td>
        <td>dfsdf</td>
        <td> xxx </td>
    </tr>
    <tr>
        <td>snagfdgfd  gd fgf ke</td>
        <td>dfsdf</td>
        <td> xxx </td>
    </tr>
    <tr>
        <td>snagfdgfd  gd fgf ke</td>
        <td>dfsdf</td>
        <td> xxx </td>
    </tr>
    <tr>
        <td>snagfdgfd  gd fgf ke</td>
        <td>dfsdf</td>
        <td> xxx </td>
    </tr>
    <tr>
        <td>snagfdgfd  gd fgf ke</td>
        <td>dfsdf</td>
        <td> xxx </td>
    </tr>
    <tr>
        <td>snagfdgfd  gd fgf ke</td>
        <td>dfsdf</td>
        <td> xxx </td>
    </tr>
    <tr>
        <td>snagfdgfd  gd fgf ke</td>
        <td>dfsdf</td>
        <td> xxx </td>
    </tr>
    </tbody>
</table>

</div>
</body>
</html>
