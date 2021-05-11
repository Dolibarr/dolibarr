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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttribute.class.php';

$ref = GETPOST('ref', 'alpha');
$label = GETPOST('label', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');


/*
 * Actions
 */

if ($_POST) {
	if (empty($ref) || empty($label)) {
		setEventMessage($langs->trans('ErrorFieldsRequired'), 'errors');
	} else {

		$prodattr = new ProductAttribute($db);
		$prodattr->label = $label;
		$prodattr->ref = $ref;

		$resid = $prodattr->create($user);
		if ($resid > 0) {
			setEventMessage($langs->trans('RecordSaved'));
			if ($backtopage)
			{
				header('Location: '.$backtopage);
			}
			else
			{
				header('Location: '.DOL_URL_ROOT.'/variants/card.php?id='.$resid.'&backtopage='.urlencode($backtopage));
			}
			exit;
		} else {
			setEventMessage($langs->trans('ErrorRecordAlreadyExists'), 'errors');
		}
	}
}

$langs->load('products');


/*
 * View
 */

$title = $langs->trans('NewProductAttribute');

llxHeader('', $title);

print_fiche_titre($title);

dol_fiche_head();

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="add">';
print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

?>

	<table class="border" style="width: 100%">
		<tr>
			<td class="titlefield fieldrequired"><label for="ref"><?php echo $langs->trans('Ref') ?></label></td>
			<td><input type="text" id="ref" name="ref" value="<?php echo $ref ?>"></td>
			<td><?php echo $langs->trans("VariantRefExample"); ?>
		</tr>
		<tr>
			<td class="fieldrequired"><label for="label"><?php echo $langs->trans('Label') ?></label></td>
			<td><input type="text" id="label" name="label" value="<?php echo $label ?>"></td>
			<td><?php echo $langs->trans("VariantLabelExample"); ?>
		</tr>

	</table>

<?php
dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Create").'"></div>';

print '</form>';

llxFooter();
$db->close();
