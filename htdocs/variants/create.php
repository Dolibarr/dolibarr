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

$ref = GETPOST('ref');
$label = GETPOST('label');

if ($_POST) {

	if (empty($ref) || empty($label)) {
		setEventMessage($langs->trans('ErrorFieldsRequired'), 'errors');
	} else {

		$prodattr = new ProductAttribute($db);
		$prodattr->label = $label;
		$prodattr->ref = $ref;

		if ($prodattr->create()) {
			setEventMessage($langs->trans('RecordSaved'));
			header('Location: '.dol_buildpath('/variants/list.php', 2));
		} else {
			setEventMessage($langs->trans('ErrorRecordAlreadyExists'), 'errors');
		}
	}
}

$langs->load('products');

$title = $langs->trans('NewProductAttribute');

llxHeader('', $title);

print_fiche_titre($title);

dol_fiche_head();

?>
<form method="post">
	<table class="border" style="width: 100%">
		<tr>
			<td class="fieldrequired"><label for="ref"><?php echo $langs->trans('Ref') ?></label></td>
			<td><input type="text" id="ref" name="ref" value="<?php echo $ref ?>"></td>
		</tr>
		<tr>
			<td class="fieldrequired"><label for="label"><?php echo $langs->trans('Label') ?></label></td>
			<td><input type="text" id="label" name="label" value="<?php echo $label ?>"></td>
		</tr>

	</table>

<?php
dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Create").'"></div></form>';

llxFooter();