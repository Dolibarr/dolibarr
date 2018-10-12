<?php
/* Copyright (C) 2016	Marcos García	<marcosgdf@gmail.com>
 * Copyright (C) 2018   Frédéric France <frederic.france@netlogic.fr>
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

$id = GETPOST('id','int');
$ref = GETPOST('ref','alpha');
$value = GETPOST('value','alpha');

$action=GETPOST('action','alpha');
$cancel=GETPOST('cancel','alpha');
$backtopage=GETPOST('backtopage','alpha');

$object = new ProductAttribute($db);
$objectval = new ProductAttributeValue($db);

if ($object->fetch($id) < 1) {
	dol_print_error($db, $langs->trans('ErrorRecordNotFound'));
	exit();
}


/*
 * Actions
 */

if ($cancel)
{
    $action='';
    header('Location: '.DOL_URL_ROOT.'/variants/card.php?id='.$object->id);
    exit();
}

// None



/*
 * View
 */

if ($action == 'add')
{
	if (empty($ref) || empty($value)) {
		setEventMessages($langs->trans('ErrorFieldsRequired'), null, 'errors');
	} else {

		$objectval->fk_product_attribute = $object->id;
		$objectval->ref = $ref;
		$objectval->value = $value;

		if ($objectval->create($user) > 0) {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header('Location: '.DOL_URL_ROOT.'/variants/card.php?id='.$object->id);
			exit();
		} else {
			setEventMessages($langs->trans('ErrorCreatingProductAttributeValue'), $objectval->errors, 'errors');
		}
	}
}

$langs->load('products');

$title = $langs->trans('ProductAttributeName', dol_htmlentities($object->label));

llxHeader('', $title);

$h=0;
$head[$h][0] = DOL_URL_ROOT.'/variants/card.php?id='.$object->id;
$head[$h][1] = $langs->trans("Card");
$head[$h][2] = 'variant';
$h++;

dol_fiche_head($head, 'variant', $langs->trans('ProductAttributeName'), -1, 'generic');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
?>
<table class="border" style="width: 100%">
	<tr>
		<td class="titlefield fieldrequired"><?php echo $langs->trans('Ref') ?></td>
		<td><?php echo dol_htmlentities($object->ref) ?>
	</tr>
	<tr>
		<td class="fieldrequired"><?php echo $langs->trans('Label') ?></td>
		<td><?php echo dol_htmlentities($object->label) ?></td>
	</tr>
</table>

<?php
print '</div>';

dol_fiche_end();

print '<br>';


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="add">';
print '<input type="hidden" name="id" value="'.$object->id.'">';
print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

print load_fiche_titre($langs->trans('NewProductAttributeValue'));

dol_fiche_head();

?>
	<table class="border" style="width: 100%">
		<tr>
			<td class="titlefield fieldrequired"><label for="ref"><?php echo $langs->trans('Ref') ?></label></td>
			<td><input id="ref" type="text" name="ref" value="<?php echo $ref ?>"></td>
		</tr>
		<tr>
			<td class="fieldrequired"><label for="value"><?php echo $langs->trans('Label') ?></label></td>
			<td><input id="value" type="text" name="value" value="<?php echo $value ?>"></td>
		</tr>
	</table>
<?php

dol_fiche_end();

print '<div class="center">';
print '<input type="submit" class="button" name="create" value="'.$langs->trans("Create").'">';
print ' &nbsp; ';
print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
