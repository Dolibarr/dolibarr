<?php
//define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

require '../../main.inc.php';

if ($dolibarr_main_prod) {
	accessforbidden();
}

$usedolheader=1;	// 1 = Test inside a dolibarr page, 0 = Use hard coded header

$form=new Form($db);




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
	<link rel="stylesheet" type="text/css" href="<?php echo DOL_URL_ROOT ?>/includes/jquery/css/smoothness/jquery-ui.custom.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/datatables/media/css/jquery.dataTables.css" />
	<?php if ($_GET["dol_use_jmobile"] == 1) { ?>
	<link rel="stylesheet" type="text/css" href="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/mobile/jquery.mobile-latest.min.css" />
	<?php } ?>
	<link rel="stylesheet" type="text/css" title="default" href="<?php echo DOL_URL_ROOT ?>/theme/eldy/style.css.php<?php echo ($_GET["dol_use_jmobile"] == 1)?'?dol_use_jmobile=1&dol_optimize_smallscreen=1':''; ?>" />
	<!-- Includes JS for JQuery -->
	<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/js/jquery.min.js"></script>
	<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/tablednd/jquery.tablednd.0.6.min.js"></script>
	<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/datatables/media/js/jquery.dataTables.js"></script>
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
	$arraycss=array('/includes/jquery/plugins/datatables/media/css/jquery.dataTables.css');
	$arrayjs=array('/includes/jquery/plugins/datatables/media/js/jquery.dataTables.js');
	llxHeader('','','','',0,0,$arrayjs,$arraycss);
}


// CONTENT
//---------
?>

<h1>
This page is a sample of page using tables. It is designed to make test with<br>
- css (add parameter &amp;theme=newtheme to test another theme or edit css of current theme)<br>
- jmobile (add parameter <a href="<?php echo $_SERVER["PHP_SELF"].'?dol_use_jmobile=1&dol_optimize_smallscreen=1'; ?>">dol_use_jmobile=1&amp;dol_optimize_smallscreen=1</a> to enable view with jmobile)<br>
- no javascript / usage for bind people (add parameter <a href="<?php echo $_SERVER["PHP_SELF"].'?nojs=1'; ?>">nojs=1</a> to force disable javascript)<br>
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





<br><hr><br>Example 1 : Standard table/thead/tbody/tr/th-td (no class pair/impair on td) => Use this if you need the drag and drop for lines or for long result tables<br>

<?php
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
$productspecimen=new Product($db);
$productspecimen->initAsSpecimen();

$sortfield='aaa';
$sortorder='ASC';
$tasksarray=array(1,2,3);	// To force having several lines
$tagidfortablednd='tablelines3';
if (! empty($conf->use_javascript_ajax)) include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';

$nav='';
$nav.='<form name="dateselect" action="'.$_SERVER["PHP_SELF"].'?action=show_peruser'.$param.'">';
if ($actioncode || isset($_GET['actioncode']) || isset($_POST['actioncode'])) $nav.='<input type="hidden" name="actioncode" value="'.$actioncode.'">';
if ($status || isset($_GET['status']) || isset($_POST['status']))  $nav.='<input type="hidden" name="status" value="'.$status.'">';
if ($filter)  $nav.='<input type="hidden" name="filter" value="'.$filter.'">';
if ($filtert) $nav.='<input type="hidden" name="filtert" value="'.$filtert.'">';
if ($socid)   $nav.='<input type="hidden" name="socid" value="'.$socid.'">';
if ($showbirthday)  $nav.='<input type="hidden" name="showbirthday" value="1">';
if ($pid)    $nav.='<input type="hidden" name="projectid" value="'.$pid.'">';
if ($type)   $nav.='<input type="hidden" name="type" value="'.$type.'">';
if ($usergroup) $nav.='<input type="hidden" name="usergroup" value="'.$usergroup.'">';
$nav.=$form->select_date($dateselect, 'dateselect', 0, 0, 1, '', 1, 0, 1);
$nav.=' <input type="submit" name="submitdateselect" class="button" value="'.$langs->trans("Refresh").'">';
$nav.='</form>';

print_barre_liste('Title of my list', 3, $_SERVER["PHP_SELF"], '', '', '', 'Text in middle', 20, 5000, '', 0, $nav);


$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('This is a select list for a filter A'). ': ';
$cate_arbo = array('field1'=>'value1a into the select list A','field2'=>'value2a');
$moreforfilter.=$form->selectarray('search_aaa', $cate_arbo, '', 1);		// List without js combo
$moreforfilter.='</div>';

$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('This is a select list for a filter B'). ': ';
$cate_arbo = array('field1'=>'value1b into the select list B','field2'=>'value2b');
$moreforfilter.=$form->selectarray('search_bbb', $cate_arbo, '', 1, 0, 0, '', 0, 0, 0, 0, '', 1);		// List with js combo
$moreforfilter.='</div>';

$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('This is a select list for a filter C'). ': ';
$cate_arbo = array('field1'=>'value1c into the select list C','field2'=>'value2c');
$moreforfilter.=$form->selectarray('search_ccc', $cate_arbo, '', 1, 0, 0, '', 0, 0, 0, 0, '', 1);		// List with js combo
$moreforfilter.='</div>';

$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('This is a select list for a filter D'). ': ';
$cate_arbo = array('field1'=>'value1d into the select list D','field2'=>'value2d');
$moreforfilter.=$form->selectarray('search_ddd', $cate_arbo, '', 1, 0, 0, '', 0, 0, 0, 0, '', 1);		// List with js combo
$moreforfilter.='</div>';

if (! empty($moreforfilter))
{
    print '<div class="liste_titre liste_titre_bydiv centpercent">';
    print $moreforfilter;
    $parameters=array();
    $reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    print '</div>';
}

?>
<table class="tagtable liste<?php echo $moreforfilter?" listwithfilterbefore":""; ?>" id="tablelines3">
<tr class="liste_titre">
<?php print getTitleFieldOfList($langs->trans('title1'),0,$_SERVER["PHP_SELF"],'aaa','','','align="left"',$sortfield,$sortorder); ?>
<?php print getTitleFieldOfList($langs->trans('title2'),0,$_SERVER["PHP_SELF"],'bbb','','','align="right"',$sortfield,$sortorder); ?>
<?php print getTitleFieldOfList($langs->trans('title3'),0,$_SERVER["PHP_SELF"],'ccc','','','align="center"',$sortfield,$sortorder); ?>
</tr>
<tr class="pair"><td><?php echo $productspecimen->getNomUrl(1); ?></td><td align="right">b1</td><td class="tdlineupdown" align="left">c1</td></tr>
<tr class="impair"><td>a2</td><td align="right">b2</td><td class="tdlineupdown" align="left">c2</td></tr>
</table>
<br>



<br><hr><br>Example 2 : Table using tags: table/thead/tbody/tr/th-td + dataTable => Use this for short result tables<br>



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
		"sDom": 'CT<"clear">lfrtip',
//To get flash tools
 		"oTableTools": {
			"sSwfPath": "<?php echo dol_buildpath('/includes/jquery/plugins/datatables/extensions/TableTools/swf/copy_csv_xls_pdf.swf',1); ?>"
		},

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
        <th>Column A</th>
        <th><label><input type="checkbox" name="hidedetails" value="2"> A checkbox inside a title of Column B</label></th>
		<?php
		print getTitleFieldOfList($langs->trans('Column C'),1,$_SERVER["PHP_SELF"],'','','','align="center" class="tagtd"',$sortfield,$sortorder);
		?>
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


<br><hr><br>Example 3 : Table using tags: div.tagtable+div.tagtr+div or div.tagtable+div.tagtr+div.tagtd => Use this for tables that need to have a different form for each line, but AVOID IT if possible (drag and drop of lines does not work for this case, also height of title can't be forced to a minimum)<br><br>


<?php
	$tasksarray=array(1,2,3);	// To force having several lines
	$tagidfortablednd='tablelines';
	if (! empty($conf->use_javascript_ajax)) include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
?>
<div class="tagtable centpercent" id="tablelines">
    <div class="tagtr liste_titre">
        <div class="tagtd">Title A<input type="hidden" name="cartitem" value="3"></div>
        <div class="tagtd">title B</div>
        <div class="tagtd">title C</div>
        <div class="tagtd">title D</div>
    </div>
    <div class="pair tagtr">
        <div class="tagtd">line4<input type="hidden" name="cartitem" value="3"></div>
        <div class="tagtd">dfsdf</div>
        <div class="tagtd"><input name="count" value="4"></div>
        <div class="tagtd tdlineupdown">bbbb</div>
    </div>
    <div class="impair tagtr">
        <div class="tagtd">line5<input type="hidden" name="cartitemb" value="3"></div>
        <div class="tagtd">dfsdf</div>
        <div class="tagtd"><input name="countb" value="4"></div>
        <div class="tagtd tdlineupdown">bbbb</div>
    </div>
    <div class="pair tagtr">
        <div class="tagtd">line6<input type="hidden" name="cartitem" value="3"></div>
        <div class="tagtd">jghjgh</div>
        <div class="tagtd">5</div>
        <div class="tagtd tdlineupdown">lll</div>
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
