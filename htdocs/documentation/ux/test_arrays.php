<?php
//define("NOLOGIN",1);        // This means this output page does not require to be logged.
//if (!defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
//if (!defined('NOREQUIREDB'))    define('NOREQUIREDB', '1');
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
//if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN', '1');
if (!defined('NOSTYLECHECK')) {
	define('NOSTYLECHECK', '1'); // Do not check style html tag into posted data
}
//if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1'); // If there is no need to load and show top and left menu
//if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
//if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1'); // Do not load ajax.lib.php library
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1'); // If this page is public (can be called outside logged session)
}

// Load Dolibarr environment
require '../../main.inc.php';

// Security
if ($dolibarr_main_prod) {
	accessforbidden('Access forbidden when $dolibarr_main_prod is set to 1');
}



/*
 * View
 */

$form = new Form($db);

$usedolheader = 1; // 1 = Test inside a dolibarr page, 0 = Use hard coded header

// HEADER
//--------

if (empty($usedolheader)) {
	header("Content-type: text/html; charset=UTF8");

	// Security options
	header("X-Content-Type-Options: nosniff"); // With the nosniff option, if the server says the content is text/html, the browser will render it as text/html (note that most browsers now force this option to on)
	header("X-Frame-Options: SAMEORIGIN"); // Frames allowed only if on same domain (stop some XSS attacks)
	?>
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
	<html>
	<head>
	<meta name="robots" content="noindex,nofollow" />
	<meta name="author" content="Dolibarr Development Team">
	<title>Test page</title>
	<!-- Includes for JQuery (Ajax library) -->
	<link rel="stylesheet" type="text/css" href="<?php echo DOL_URL_ROOT ?>/includes/jquery/css/base/jquery-ui.css" />
	<!-- <link rel="stylesheet" type="text/css" href="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/datatables/media/css/jquery.dataTables.css" /> -->
	<link rel="stylesheet" type="text/css" title="default" href="<?php echo DOL_URL_ROOT ?>/theme/eldy/style.css.php<?php echo (GETPOST("dol_use_jmobile") == 1) ? '?dol_use_jmobile=1&dol_optimize_smallscreen=1' : ''; ?>" />
	<!-- Includes JS for JQuery -->
	<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/js/jquery.min.js"></script>
	<!-- migration fixes for removed Jquery functions -->
	<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/js/jquery-migrate.min.js"></script>
	<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/tablednd/jquery.tablednd.0.6.min.js"></script>
	<!-- <script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/datatables/media/js/jquery.dataTables.js"></script> -->
	<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/plugins/select2/select2.min.js?version=4.0.0-beta"></script>
	</head>

	<body style="padding: 10px;">


	<?php
} else {
	$arraycss = array();
	$arrayjs = array();
	/*
	$arraycss=array('/includes/jquery/plugins/datatables/media/css/jquery.dataTables.css',
	'/includes/jquery/plugins/datatables/extensions/Buttons/css/buttons.dataTables.min.css',
	'/includes/jquery/plugins/datatables/extensions/ColReorder/css/colReorder.dataTables.min.css'
	);
	$arrayjs=array('/includes/jquery/plugins/datatables/media/js/jquery.dataTables.js',
	'/includes/jquery/plugins/datatables/extensions/Buttons/js/dataTables.buttons.js',
	'/includes/jquery/plugins/datatables/extensions/Buttons/js/buttons.colVis.min.js',
	'/includes/jquery/plugins/datatables/extensions/Buttons/js/buttons.html5.min.js',
	'/includes/jquery/plugins/datatables/extensions/Buttons/js/buttons.flash.min.js',
	'/includes/jquery/plugins/datatables/extensions/Buttons/js/buttons.print.min.js',
	'/includes/jquery/plugins/datatables/extensions/ColReorder/js/dataTables.colReorder.min.js',
	'/includes/jszip/jszip.min.js',
	'/includes/pdfmake/pdfmake.min.js',
	'/includes/pdfmake/vfs_fonts.js'
	);
	*/

	llxHeader('', '', '', '', 0, 0, $arrayjs, $arraycss);
}


// CONTENT
//---------
?>

<h2>
This page is a sample of page using tables. It is designed to make test with<br>
</h2>
<div class="wordbreak">
- css (add parameter &amp;theme=newtheme to test another theme or edit css of current theme)<br>
- jmobile (add parameter <a href="<?php echo $_SERVER["PHP_SELF"].'?dol_use_jmobile=1&dol_optimize_smallscreen=1'; ?>">dol_use_jmobile=1&amp;dol_optimize_smallscreen=1</a> and switch to small screen < 570 to enable with emulated jmobile)<br>
- no javascript / usage for bind people (add parameter <a href="<?php echo $_SERVER["PHP_SELF"].'?nojs=1'; ?>">nojs=1</a> to force disable javascript)<br>
- tablednd<br>
</div>


<br><hr><br>Example 0a : Table with div+div+div containing a select that should be overflowed and truncated => Use this to align text or form<br>

<div class="tagtable centpercent">
	<div class="tagtr">
	<div class="tagtd maxwidthonsmartphone" style="overflow: hidden; white-space: nowrap;"> <!-- If you remove max-width, the jmobile overflow does not work -->
	<select name="hidedetails" class="centpercentonsmartphone"><option>aaaaaaaaaaaaaaafd sf sf gfd gfds fsd  gfd fhfg hf dhfg hg fhfgdhfgdh gh gfdhdgf h gfdh dfhg dfgh dfgh fdgh gfd hfd hfd gs fgdf gaaaa</option><option>gdfs gdf g sdfg dfg fdsg dsfg dfs gdfs gds fgs  gdfdf gd</option></select>
	</div>
	<div class="tagtd maxwidthonsmartphone" style="overflow: hidden; white-space: nowrap;"> <!-- If you remove max-width, the jmobile overflow does not work -->
	<select name="hidedetails" class="centpercentonsmartphone"><option>aaaaaaaaaaaaaaafd sf sf gfd gfds fsd  gfd fhfg hf dhfg hg fhfgdhfgdh gh gfdhdgf h gfdh dfhg dfgh dfgh fdgh gfd hfd hfd gs fgdf gaaaa</option><option>gdfs gdf g sdfg dfg fdsg dsfg dfs gdfs gds fgs  gdfdf gd</option></select>
	</div>
	</div>
</div>

<br><hr><br>Example 0b: Table with div+form+div containing a select that should be overflowed and truncated => Use this to align text or form<br>

<div class="tagtable centpercent">
	<form action="xxx" method="POST" class="tagtr">
	<div class="tagtd maxwidthonsmartphone" style="overflow: hidden; white-space: nowrap;"> <!-- If you remove max-width, the jmobile overflow does not work -->
	<select name="hidedetails" class="centpercentonsmartphone"><option>aaaaaaaaaaaaaaafd sf sf gfd gfds fsd  gfd fhfg hf dhfg hg fhfgdhfgdh gh gfdhdgf h gfdh dfhg dfgh dfgh fdgh gfd hfd hfd gs fgdf gaaaa</option><option>gdfs gdf g sdfg dfg fdsg dsfg dfs gdfs gds fgs  gdfdf gd</option></select>
	</div>
	<div class="tagtd maxwidthonsmartphone" style="overflow: hidden; white-space: nowrap;"> <!-- If you remove max-width, the jmobile overflow does not work -->
	<select name="hidedetails" class="centpercentonsmartphone"><option>aaaaaaaaaaaaaaafd sf sf gfd gfds fsd  gfd fhfg hf dhfg hg fhfgdhfgdh gh gfdhdgf h gfdh dfhg dfgh dfgh fdgh gfd hfd hfd gs fgdf gaaaa</option><option>gdfs gdf g sdfg dfg fdsg dsfg dfs gdfs gds fgs  gdfdf gd</option></select>
	</div>
	</form>
</div>

<br><hr><br>Example 0c: Table with table+tr+td containing a select that should be overflowed and truncated => Use this to align text or form<br>

<table class="centpercent">
	<tr>
	<td class="tagtd maxwidthonsmartphone" style="overflow: hidden; white-space: nowrap;"> <!-- If you remove max-width, the jmobile overflow does not work -->
	<select name="hidedetails" class="centpercentonsmartphone"><option>aaaaaaaaaaaaaaafd sf sf gfd gfds fsd  gfd fhfg hf dhfg hg fhfgdhfgdh gh gfdhdgf h gfdh dfhg dfgh dfgh fdgh gfd hfd hfd gs fgdf gaaaa</option><option>gdfs gdf g sdfg dfg fdsg dsfg dfs gdfs gds fgs  gdfdf gd</option></select>
	</td>
	<td class="tagtd maxwidthonsmartphone" style="overflow: hidden; white-space: nowrap;"> <!-- If you remove max-width, the jmobile overflow does not work -->
	<select name="hidedetails" class="centpercentonsmartphone"><option>aaaaaaaaaaaaaaafd sf sf gfd gfds fsd  gfd fhfg hf dhfg hg fhfgdhfgdh gh gfdhdgf h gfdh dfhg dfgh dfgh fdgh gfd hfd hfd gs fgdf gaaaa</option><option>gdfs gdf g sdfg dfg fdsg dsfg dfs gdfs gds fgs  gdfdf gd</option></select>
	</td>
	</tr>
</table>




<br><hr><br>Example 1 : Standard table/thead/tbody/tr/th-td (no class pair/impair on td) => Use this if you need the drag and drop for lines or for long result tables<br>


<script type="text/javascript">
/*jQuery(document).ready(function() {
$(document).ready(function() {
	var table = $('#tablelines3').DataTable( {
		scrollY:        "300px",
		scrollX:        true,
		scrollCollapse: true,
		paging:         false,
		fixedColumns:   {
			leftColumns: 1,
			rightColumns: 1
		}
	} );
} );
});*/
</script>


<?php
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
$productspecimen = new Product($db);
$productspecimen->initAsSpecimen();
$object = $productspecimen;

$sortfield = 'aaa';
$sortorder = 'ASC';
$tasksarray = array(1, 2, 3); // To force having several lines
$tagidfortablednd = 'tablelines3';
if (!isset($moreforfilter)) {
	$moreforfilter = '';
}
if (!empty($conf->use_javascript_ajax)) {
	include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
}

$nav = '';
$nav .= '<form name="dateselect" action="'.$_SERVER["PHP_SELF"].'?mode=show_peruser'.$param.'">';
if ($actioncode || GETPOSTISSET('actioncode')) {
	$nav .= '<input type="hidden" name="actioncode" value="'.$actioncode.'">';
}
if ($status || GETPOSTISSET('status')) {
	$nav .= '<input type="hidden" name="status" value="'.$status.'">';
}
if ($filter) {
	$nav .= '<input type="hidden" name="filter" value="'.$filter.'">';
}
if ($filtert) {
	$nav .= '<input type="hidden" name="filtert" value="'.$filtert.'">';
}
if ($socid) {
	$nav .= '<input type="hidden" name="socid" value="'.$socid.'">';
}
if (isset($showbirthday) && $showbirthday) {
	$nav .= '<input type="hidden" name="showbirthday" value="1">';
}
if (isset($pid) && $pid) {
	$nav .= '<input type="hidden" name="projectid" value="'.$pid.'">';
}
if ($type) {
	$nav .= '<input type="hidden" name="type" value="'.$type.'">';
}
if ($usergroup) {
	$nav .= '<input type="hidden" name="usergroup" value="'.$usergroup.'">';
}
$nav .= $form->selectDate($dateselect ?? '', 'dateselect', 0, 0, 1, '', 1, 0);
$nav .= ' <input type="submit" name="submitdateselect" class="button" value="'.$langs->trans("Refresh").'">';
$nav .= '</form>';

$limit = 10;
print_barre_liste('Title of my list', 12, $_SERVER["PHP_SELF"], '', '', '', 'Text in middle', 20, 500, '', 0, $nav, '', $limit, 0, 0, 1);

$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= $langs->trans('This is a select list for a filter A (no combo forced)').': ';
$cate_arbo = array('field1' => 'value1a into the select list A', 'field2' => 'value2a');
$moreforfilter .= $form->selectarray('search_aaa', $cate_arbo, '', 1, 0, 0, '', 0, 0, 0, '', 'maxwidth300', 0); // List with no js combo
$moreforfilter .= '</div>';

$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= $langs->trans('This is a select list for a filter B (auto combo)').': ';
$cate_arbo = array('field1' => 'value1b into the select list B', 'field2' => 'value2b');
$moreforfilter .= $form->selectarray('search_bbb', $cate_arbo, '', 1, 0, 0, '', 0, 0, 0, '', 'maxwidth300', -1); // List with js combo auto
$moreforfilter .= '</div>';

$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= $langs->trans('This is a select list for a filter C (combo forced)').': ';
$cate_arbo = array('field1' => 'value1c into the select list C', 'field2' => 'value2c');
$moreforfilter .= $form->selectarray('search_ccc', $cate_arbo, '', 1, 0, 0, '', 0, 0, 0, '', 'maxwidth300', 1); // List with js combo forced
$moreforfilter .= '</div>';

$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= $langs->trans('This is a select list for a filter D (combo forced)').': ';
$cate_arbo = array('field1' => 'value1d into the select list D', 'field2' => 'value2d');
$moreforfilter .= $form->selectarray('search_ddd', $cate_arbo, '', 1, 0, 0, '', 0, 0, 0, '', 'maxwidth300', 1); // List with js combo forced
$moreforfilter .= '</div>';

if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '</div>';
}

?>

<table class="stripe row-border order-column centpercent tagtable liste<?php echo $moreforfilter ? " listwithfilterbefore" : ""; ?>" id="tablelines3">
<thead>
<tr class="liste_titre">
<?php print getTitleFieldOfList($langs->trans('title1'), 0, $_SERVER["PHP_SELF"], 'aaa', '', '', 'align="left"', $sortfield, $sortorder); ?>
<?php print getTitleFieldOfList($langs->trans('title2'), 0, $_SERVER["PHP_SELF"], 'bbb', '', '', 'align="center"', $sortfield, $sortorder); ?>
<?php print getTitleFieldOfList($langs->trans('title3'), 0, $_SERVER["PHP_SELF"], 'ccc', '', '', 'align="right"', $sortfield, $sortorder); ?>
</tr>
</thead>
<tbody>
<tr class="pair"><td><?php echo $productspecimen->getNomUrl(1); ?></td><td class="center">b1</td><td class="tdlineupdown right">c1</td></tr>
<tr class="impair nowrap"><td>a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2 a2</td><td align="center">b2</td><td class="tdlineupdown right">c2</td></tr>
<tr class="pair"><td>a3</td><td class="center">b3</td><td class="tdlineupdown right">c3</td></tr>
</tbody>
</table>


<br>


<!--
<br><hr><br>Example 1b : Table using tags: table/thead/tbody/tr/th-td + dataTable => Use this for short result tables<br>

<script type="text/javascript">
$(document).ready(function(){
	$('#idtableexample2').dataTable( {
		<?php
		if ($optioncss == 'print') {
			print '\'dom\': \'lfrtip\',';
		} else {
			print '\'dom\': \'Blfrtip\',';
		}
		?>
		"colReorder": true,
		'buttons': [
				  'colvis','copy', 'csv', 'excel', 'pdf', 'print'
			  ],
		"sPaginationType": "full_numbers",
		"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?php echo $langs->trans('All'); ?>"]],
		"oLanguage": {
			"sLengthMenu": "<?php echo $langs->trans('Show'); ?> _MENU_ <?php echo $langs->trans('Entries'); ?>",
			"sSearch": "<?php echo $langs->trans('Search'); ?>:",
			"sZeroRecords": "<?php echo $langs->trans('NoRecordsToDisplay'); ?>",
			"sInfoEmpty": "<?php echo $langs->trans('NoEntriesToShow'); ?>",
			"sInfoFiltered": "(<?php echo $langs->trans('FilteredFrom'); ?> _MAX_ <?php echo $langs->trans('TotalEntries'); ?>)",
			"sInfo": "<?php echo $langs->trans('Showing'); ?> _START_ <?php echo $langs->trans('To'); ?> _END_ <?php echo $langs->trans('WTOf'); ?> _TOTAL_ <?php echo $langs->trans('Entries'); ?>",
			"oPaginate": {
				"sFirst": "<?php echo $langs->transnoentities('First'); ?>",
				"sLast": "<?php echo $langs->transnoentities('Last'); ?>",
				"sPrevious": "<?php echo $langs->transnoentities('Previous'); ?>",
				"sNext": "<?php echo $langs->transnoentities('Next'); ?>"
			}
		},
		"aaSorting": [[0,'desc']],
/* To use in ajax mode
			"bProcessing": true,
		"stateSave": true,
		"bServerSide": true,
		"sAjaxSource": "../ajax.php",
		"aoColumnDefs": [
						 { "bSortable": false, "aTargets": [ 2,3,4 ] }
					   ],
*/
	})
});
</script>
 -->


<br><hr><br>Example 2 : Table using tags: div.tagtable+(div|form).tagtr+div[.tagtd] => Use this for tables that need to have a different form for each line, but AVOID IT if possible (drag and drop of lines does not work for this case, also height of title can't be forced to a minimum)<br><br>


<?php
	$tasksarray = array(1, 2, 3); // To force having several lines
$tagidfortablednd = 'tablelines';
if (!empty($conf->use_javascript_ajax)) {
	include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
}
?>
<div class="tagtable centpercent liste_titre_bydiv" id="tablelines">
	<div class="tagtr liste_titre">
		<div class="tagtd">Title A<input type="hidden" name="cartitem" value="3"></div>
		<div class="tagtd">title B</div>
		<div class="tagtd">title C</div>
		<div class="tagtd">title D</div>
	</div>
	<div class="pair tagtr">
		<div class="tagtd">line4<input type="hidden" name="cartitem" value="3"></div>
		<div class="tagtd">dfsdf</div>
		<div class="tagtd"><input name="count" value="4" class="maxwidth50"></div>
		<div class="tagtd tdlineupdown">bbbb</div>
	</div>
	<div class="impair tagtr">
		<div class="tagtd">line5<input type="hidden" name="cartitemb" value="3"></div>
		<div class="tagtd">dfsdf</div>
		<div class="tagtd"><input name="countb" value="4" class="maxwidth50"></div>
		<div class="tagtd tdlineupdown">bbbb</div>
	</div>
	<div class="pair tagtr">
		<div class="tagtd">line6<input type="hidden" name="cartitem" value="3"></div>
		<div class="tagtd">jghjgh</div>
		<div class="tagtd">5</div>
		<div class="tagtd tdlineupdown">lll</div>
	</div>
<!-- Using form into div make Firefox crazy (page loading does not end) -->
<!--    <form class="liste_titre" method="POST" action="1.php">
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
if (!empty($usedolheader)) {
	llxFooter();
} else { ?>
</body>
<?php } ?>

</html>
