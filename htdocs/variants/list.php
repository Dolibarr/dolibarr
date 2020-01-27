<?php
/* Copyright (C) 2016	Marcos García	<marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

require '../main.inc.php';
require DOL_DOCUMENT_ROOT.'/variants/class/ProductAttribute.class.php';

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$object = new ProductAttribute($db);



/*
 * Actions
 */

if ($action == 'up') {
	$object->fetch($rowid);
	$object->moveUp();

	header('Location: '.$_SERVER['PHP_SELF']);
	exit();
} elseif ($action == 'down') {
	$object->fetch($rowid);
	$object->moveDown();

	header('Location: '.$_SERVER['PHP_SELF']);
	exit();
}



/*
 * View
 */

$langs->load('products');

$title = $langs->trans($langs->trans('ProductAttributes'));

$variants = $object->fetchAll();

llxHeader('', $title);

$newcardbutton='';
if ($user->rights->produit->creer)
{
    $newcardbutton.= dolGetButtonTitle($langs->trans('Create'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/variants/create.php');
}

print load_fiche_titre($title, $newcardbutton, 'products');

$forcereloadpage=empty($conf->global->MAIN_FORCE_RELOAD_PAGE)?0:1;
?>
	<script type="text/javascript">
		$(document).ready(function(){
			$(".imgupforline, .imgdownforline").hide();
			$(".lineupdown").removeAttr('href');
			$(".tdlineupdown")
				.css("background-image", 'url(<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/grip.png'; ?>)')
				.css("background-repeat", "no-repeat")
				.css("background-position", "center center")
				.hover(
					function () {
						$(this).addClass('showDragHandle');
					}, function () {
						$(this).removeClass('showDragHandle');
					}
				);

			$("#tablelines").tableDnD({
				onDrop: function(table, row) {
					console.log('drop');
					var reloadpage = "<?php echo $forcereloadpage; ?>";
					var roworder = cleanSerialize(decodeURI($("#tablelines").tableDnDSerialize()));
					$.post("<?php echo DOL_URL_ROOT; ?>/variants/ajax/orderAttribute.php",
						{
							roworder: roworder
						},
						function() {
							if (reloadpage == 1) {
								location.href = '<?php echo dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.dol_escape_htmltag($_SERVER['QUERY_STRING']); ?>';
							} else {
								$("#tablelines .drag").each(
									function( intIndex ) {
										$(this).removeClass("pair impair");
										if (intIndex % 2 == 0) $(this).addClass('impair');
										if (intIndex % 2 == 1) $(this).addClass('pair');
									});
							}
						});
				},
				onDragClass: "dragClass",
				dragHandle: "td.tdlineupdown"
			});
		});
	</script>

	<table class="liste nobottom" id="tablelines">
		<tr class="liste_titre nodrag nodrop">
			<th class="liste_titre"><?php print $langs->trans('Ref') ?></th>
			<th class="liste_titre"><?php print $langs->trans('Label') ?></th>
			<th class="liste_titre right"><?php print $langs->trans('NbOfDifferentValues') ?></th>
			<th class="liste_titre right"><?php print $langs->trans('NbProducts') ?></th>
			<th class="liste_titre" colspan="2"></th>
		</tr>
<?php
foreach ($variants as $key => $attribute) {
	print '<tr id="row-'.$attribute->id.'" class="drag drop oddeven">';
	print '<td><a href="card.php?id='.$attribute->id.'">'.dol_htmlentities($attribute->ref).'</a></td>';
	print '<td><a href="card.php?id='.$attribute->id.'">'.dol_htmlentities($attribute->label).'</a></td>';
	print '<td class="right">'.$attribute->countChildValues().'</td>';
	print '<td class="right">'.$attribute->countChildProducts().'</td>';
	print '<td class="right">';
	print '<a href="card.php?id='.$attribute->id.'&action=edit">'.img_edit().'</a>';
	print '<a href="card.php?id='.$attribute->id.'&action=delete">'.img_delete().'</a>';
	print '</td>';
	print '<td class="center linecolmove tdlineupdown">';
	if ($key > 0) {
		print '<a class="lineupdown" href="'.$_SERVER['PHP_SELF'].'?action=up&amp;rowid='.$attribute->id.'">'.img_up('default', 0, 'imgupforline').'</a>';
	}
	if ($key < count($variants)-1) {
		print '<a class="lineupdown" href="'.$_SERVER['PHP_SELF'].'?action=down&amp;rowid='.$attribute->id.'">'.img_down('default', 0, 'imgdownforline').'</a>';
	}
	print '</td>';
	print "</tr>\n";
}

print '</table>';

// End of page
llxFooter();
$db->close();
