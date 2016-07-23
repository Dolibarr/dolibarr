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
require 'class/ProductAttribute.class.php';
require 'class/ProductAttributeValue.class.php';

$id = GETPOST('id');
$ref = GETPOST('ref');
$value = GETPOST('value');

$prodattr = new ProductAttribute($db);
$prodattrval = new ProductAttributeValue($db);

if ($prodattr->fetch($id) < 1) {
	dol_print_error($db, $langs->trans('ErrorRecordNotFound'));
	die;
}

if ($_POST) {

	if (empty($ref) || empty($value)) {
		setEventMessage($langs->trans('ErrorFieldsRequired'), 'errors');
	} else {

		$prodattrval->fk_product_attribute = $prodattr->id;
		$prodattrval->ref = $ref;
		$prodattrval->value = $value;

		if ($prodattrval->create() > 0) {
			setEventMessage($langs->trans('RecordSaved'));
			header('Location: '.dol_buildpath('/attributes/card.php?id='.$prodattr->id, 2));
			die;
		} else {
			setEventMessage($langs->trans('ErrorCreatingProductAttributeValue'), 'errors');
		}
	}

}

$langs->load('products');

$title = $langs->trans('ProductAttributeName', dol_htmlentities($prodattr->label));

llxHeader('', $title);

print_fiche_titre($title);

dol_fiche_head();

?>
<table class="border" style="width: 100%">
	<tr>
		<td style="width: 15%" class="fieldrequired"><?php echo $langs->trans('Ref') ?></td>
		<td><?php echo dol_htmlentities($prodattr->ref) ?>
	</tr>
	<tr>
		<td style="width: 15%" class="fieldrequired"><?php echo $langs->trans('Label') ?></td>
		<td><?php echo dol_htmlentities($prodattr->label) ?></td>
	</tr>
</table>

<?php

dol_fiche_end();

print_fiche_titre($langs->trans('NewProductAttributeValue'));

dol_fiche_head();
?>
<form method="post">
	<table class="border" style="width: 100%">
		<tr>
			<td style="width: 15%" class="fieldrequired"><label for="ref"><?php echo $langs->trans('Ref') ?></label></td>
			<td><input id="ref" type="text" name="ref" value="<?php echo $ref ?>"></td>
		</tr>
		<tr>
			<td style="width: 15%" class="fieldrequired"><label for="value"><?php echo $langs->trans('Value') ?></label></td>
			<td><input id="value" type="text" name="value" value="<?php echo $value ?>"></td>
		</tr>
	</table>
<?php
dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Create").'"></div></form>';

llxFooter();