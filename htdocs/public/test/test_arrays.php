<?php
//define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.


require '../../main.inc.php';

if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1')
{
	print "Page available only from remote address 127.0.0.1";
	exit;
}


$usedolheader=1;	// 1 = Test inside a dolibarr page, 0 = Use hard coded header


// HEADER
//--------

if (empty($usedolheader))
{
	header("Content-type: text/html; charset=UTF8");
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
	<?php if ($_GET["dol_use_jmobile"] == 1) { ?>
	<link rel="stylesheet" type="text/css" href="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/mobile/jquery.mobile-latest.min.css" />
	<?php } ?>
	<link rel="stylesheet" type="text/css" title="default" href="<?php echo DOL_URL_ROOT ?>/theme/eldy/style.css.php<?php echo ($_GET["dol_use_jmobile"] == 1)?'?dol_use_jmobile=1&dol_optimize_smallscreen=1':''; ?>" />
	<!-- Includes JS for JQuery -->
	<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/js/jquery-latest.min.js"></script>
	<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/tablednd/jquery.tablednd.0.6.min.js"></script>
	<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/datatables/js/jquery.dataTables.js"></script>
	<?php if ($_GET["dol_use_jmobile"] == 1) { ?>
	<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/mobile/jquery.mobile-latest.min.js"></script>
	<?php } ?>
	</head>

	<body style="padding: 10px;">

	<div data-role="page">

	<?php
}
else
{
	$arraycss=array('/includes/jquery/plugins/datatables/css/jquery.dataTables.css');
	$arrayjs=array('/includes/jquery/plugins/datatables/js/jquery.dataTables.js');
	llxHeader('','','','',0,0,$arrayjs,$arraycss);
}


// CONTENT
//---------
?>

<h1>
This page is a sample of page using tables. It is designed to make test with<br>
- css (edit page to change to test another css)<br>
- jmobile (add parameter dol_use_jmobile=1&dol_optimize_smallscreen=1 to enable view with jmobile)<br>
- dataTables<br>
- tablednd<br>
</h1>

<br><hr><br>Example 0a : Table with div+div+div containg a select that should be overflowed and truncated => Use this to align text or form<br>


<div class="tagtable centpercent">
	<div class="tagtr">
	<div class="tagtd maxwidthonsmartphone" style="overflow: hidden; white-space: nowrap;"> <!-- If you remove max-width, the jmobile overflow does not work -->
	<select name="hidedetails"><option>aaaaaaaaaaaaaaafd sf sf gfd gfds fsd  gfd fhfg hf dhfg hg fhfgdhfgdh gh gfdhdgf h gfdh dfhg dfgh dfgh fdgh gfd hfd hfd gs fgdf gaaaa</option><option>gdfs gdf g sdfg dfg fdsg dsfg dfs gdfs gds fgs  gdfdf gd</option></select>
	</div>
	<div class="tagtd maxwidthonsmartphone" style="overflow: hidden; white-space: nowrap;"> <!-- If you remove max-width, the jmobile overflow does not work -->
	<select name="hidedetails"><option>aaaaaaaaaaaaaaafd sf sf gfd gfds fsd  gfd fhfg hf dhfg hg fhfgdhfgdh gh gfdhdgf h gfdh dfhg dfgh dfgh fdgh gfd hfd hfd gs fgdf gaaaa</option><option>gdfs gdf g sdfg dfg fdsg dsfg dfs gdfs gds fgs  gdfdf gd</option></select>
	</div>
	</div>
</div>

<br><hr><br>Example 0b: Table with div+form+div containg a select that should be overflowed and truncated => Use this to align text or form<br>

<div class="tagtable centpercent">
	<form action="xxx" method="POST" class="tagtr">
	<div class="tagtd maxwidthonsmartphone" style="overflow: hidden; white-space: nowrap;"> <!-- If you remove max-width, the jmobile overflow does not work -->
	<select name="hidedetails"><option>aaaaaaaaaaaaaaafd sf sf gfd gfds fsd  gfd fhfg hf dhfg hg fhfgdhfgdh gh gfdhdgf h gfdh dfhg dfgh dfgh fdgh gfd hfd hfd gs fgdf gaaaa</option><option>gdfs gdf g sdfg dfg fdsg dsfg dfs gdfs gds fgs  gdfdf gd</option></select>
	</div>
	<div class="tagtd maxwidthonsmartphone" style="overflow: hidden; white-space: nowrap;"> <!-- If you remove max-width, the jmobile overflow does not work -->
	<select name="hidedetails"><option>aaaaaaaaaaaaaaafd sf sf gfd gfds fsd  gfd fhfg hf dhfg hg fhfgdhfgdh gh gfdhdgf h gfdh dfhg dfgh dfgh fdgh gfd hfd hfd gs fgdf gaaaa</option><option>gdfs gdf g sdfg dfg fdsg dsfg dfs gdfs gds fgs  gdfdf gd</option></select>
	</div>
	</form>
</div>

<br><hr><br>Example 0c: Table with table+tr+td containg a select that should be overflowed and truncated => Use this to align text or form<br>

<table class="centpercent">
    <tr>
    <td class="tagtd maxwidthonsmartphone" style="overflow: hidden; white-space: nowrap;"> <!-- If you remove max-width, the jmobile overflow does not work -->
	<select name="hidedetails"><option>aaaaaaaaaaaaaaafd sf sf gfd gfds fsd  gfd fhfg hf dhfg hg fhfgdhfgdh gh gfdhdgf h gfdh dfhg dfgh dfgh fdgh gfd hfd hfd gs fgdf gaaaa</option><option>gdfs gdf g sdfg dfg fdsg dsfg dfs gdfs gds fgs  gdfdf gd</option></select>
    </td>
    <td class="tagtd maxwidthonsmartphone" style="overflow: hidden; white-space: nowrap;"> <!-- If you remove max-width, the jmobile overflow does not work -->
	<select name="hidedetails"><option>aaaaaaaaaaaaaaafd sf sf gfd gfds fsd  gfd fhfg hf dhfg hg fhfgdhfgdh gh gfdhdgf h gfdh dfhg dfgh dfgh fdgh gfd hfd hfd gs fgdf gaaaa</option><option>gdfs gdf g sdfg dfg fdsg dsfg dfs gdfs gds fgs  gdfdf gd</option></select>
    </td>
    </tr>
</table>





<br><hr><br>Example 1 : Standard table => Use this if you need the drag and drop for lines<br>

<?php
$sortfield='aaa';
$sortorder='ASC';
$tasksarray=array(1,2,3);	// To force having several lines
$tagidfortablednd='tablelines3';
if (! empty($conf->use_javascript_ajax)) include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
?>
<table class="liste noborder tagtable centpercent" id="tablelines3">
<tr class="liste_titre">
<?php print getTitleFieldOfList($langs->trans('title1'),0,$_SERVER["PHP_SELF"],'aaa','','','align="left"',$sortfield,$sortorder); ?>
<?php print getTitleFieldOfList($langs->trans('title2'),0,$_SERVER["PHP_SELF"],'bbb','','','align="right"',$sortfield,$sortorder); ?>
<?php print getTitleFieldOfList($langs->trans('title3'),0,$_SERVER["PHP_SELF"],'ccc','','','align="center"',$sortfield,$sortorder); ?>
</tr>
<tr class="pair"><td class="pair">a1</td><td class="pair" align="right">b1</td><td class="tdlineupdown pair" align="left">c1</td></tr>
<tr class="impair"><td class="impair">a2</td><td class="impair" align="right">b2</td><td class="tdlineupdown impair" align="left">c2</td></tr>
</table>
<br>



<br><hr><br>Example 2 : Table using tags: table/thead/tbody/tr/td + dataTable => Use this for long result tables<br>



<script type="text/javascript">
$(document).ready(function(){
    $('#idtableexample2').dataTable( {
		"sPaginationType": "full_numbers",
		"aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tous"]],
		"oLanguage": {
			"sLengthMenu": "Voir _MENU_ lignes",
			"sSearch": "Recherche:",
			"sZeroRecords": "Aucune ligne &agrave; afficher",
			"sInfoEmpty": "Aucune ligne &agrave; afficher",
			"sInfoFiltered": "(Filtrer sur _MAX_ Total de lignes)",
			"sInfo": "Afficher _START_ &agrave; _END_ sur les _TOTAL_ lignes &agrave; afficher",
			"oPaginate": {
				"sFirst": "Début",
				"sLast": "Fin",
				"sPrevious": "Précédent",
				"sNext": "Suivant"
			}
		},
		"aaSorting": [[0,'desc']],
		"sDom": 'T<"clear">lfrtip',
/* To get flash tools
 		"oTableTools": {
			"sSwfPath": "<?php echo DOL_URL_ROOT.'/includes/jquery/plugins/datatables/extras/TableTools/swf/copy_csv_xls_pdf.swf'; ?>"
		}
*/
/* To use in ajax mode
		"bProcessing": true,	// Show	"processing message"
		"bServerSide": true,
		"bJQueryUI": true,
		"sAjaxSource": "../ajaxlist.php"
*/
    })
});


/*
// counts total number of td in a head so that we can use it for label extraction
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

<table id="idtableexample2" class="centpercent">
	<thead>
    <tr class="liste_titre">
        <th>snake</th>
        <th><label><input type="checkbox" name="hidedetails" value="2"> A checkbox inside a cell</label></th>
		<?php print getTitleFieldOfList($langs->trans('zzz'),1,$_SERVER["PHP_SELF"],'','','','align="center" class="tagtd"',$sortfield,$sortorder); ?>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>line1</td>
        <td>dfsdf</td>
		<td align="center"> xxx </td>
    </tr>
    <tr>
        <td>line2</td>
        <td>dfsdf</td>
        <td align="center"> xxx </td>
    </tr>
    <tr>
        <td>line3</td>
        <td>dfsdf</td>
        <td align="center"> xxx </td>
    </tr>
    <tr>
        <td>line4</td>
        <td>dfsdf</td>
        <td align="center"> xxx </td>
    </tr>
    <tr>
        <td>line5</td>
        <td>dfsdf</td>
        <td align="center"> xxx </td>
    </tr>
    <tr>
        <td>line6</td>
        <td>dfsdf</td>
        <td align="center"> xxx </td>
    </tr>
    <tr>
        <td>line7</td>
        <td>dfsdf</td>
        <td align="center"> xxx </td>
    </tr>
    <tr>
        <td>line8</td>
        <td>dfsdf</td>
        <td align="center"> xxx </td>
    </tr>
    <tr>
        <td>line9</td>
        <td>dfsdf</td>
        <td align="center"> xxx </td>
    </tr>
    <tr>
        <td>line10</td>
        <td>dfsdf</td>
        <td align="center"> xxx </td>
    </tr>
    <tr>
        <td>line11</td>
        <td>dfsdf</td>
        <td align="center"> xxx </td>
    </tr>
    <tr>
        <td>line12</td>
        <td>dfsdf</td>
        <td align="center"> xxx </td>
    </tr>
    </tbody>
</table>
<br>


<br><hr><br>Example 3 : Table using tags: div.tagtable+div.tagtr+div or div.tagtable+div.tagtr+div.tagtd => Use this, but AVOID IT if possible, for tables that need to have a different form for each line (drag and drop of lines does not work for this case, also height of title can't be forced to a minimum)<br><br>


<?php
	$tasksarray=array(1,2,3);	// To force having several lines
	$tagidfortablednd='tablelines';
	if (! empty($conf->use_javascript_ajax)) include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
?>
<div class="tagtable centpercent" id="tablelines">
    <div class="tagtr liste_titre">
        <div class="tagtd">line3<input type="hidden" name="cartitem" value="3"></div>
        <div class="tagtd">dfsdf</div>
        <div class="tagtd">ffdsfsd</div>
        <div class="tagtd tdlineupdown">aaaa</div>
    </div>
    <div class="impair tagtr">
        <div class="tagtd">line4<input type="hidden" name="cartitem" value="3"></div>
        <div class="tagtd">dfsdf</div>
        <div class="tagtd"><input name="count" value="4"></div>
        <div class="tagtd tdlineupdown">bbbb</div>
    </div>
    <div class="pair tagtr">
        <div class="tagtd">line5<input type="hidden" name="cartitemb" value="3"></div>
        <div class="tagtd">dfsdf</div>
        <div class="tagtd"><input name="countb" value="4"></div>
        <div class="tagtd tdlineupdown">bbbb</div>
    </div>
<!-- Using form into div make Firefox crazy (page loading does not end) -->
<!--	<form class="liste_titre" method="POST" action="1.php">
        <div>line1<input type="hidden" name="cartitem" value="1"></div>
        <div><label><input type="checkbox" name="hidedetails" value="2"> A checkbox inside a cell</label></div>
        <div><input name="count" value="4"></div>
        <div><input type="submit" name="count2" class="button noshadow" value="aaa"></div>
    </form>
    <form class="impair" method="POST" action="2.php">
        <div>line2<input type="hidden" name="cartitem" value="2"></div>
        <div><select name="hidedetails"><option>aaaaaaaaaaaaaaafd sf sf gfd gfd gs fgdf gaaaa</option><option>gdfs gdf g sdfg dfg fdsg dsfg dfs gdfs gds fgs  gdfdf gd</option></select></div>
        <div><input name="countb" value="4"></div>
        <div class="tdlineupdown"><input type="submit" value="xxx" class="button"></div>
    </form>-->
</div>




<?php
if (! empty($usedolheader))
{
	llxFooter();
} else { ?>
</div>
</body>
<?php } ?>

</html>