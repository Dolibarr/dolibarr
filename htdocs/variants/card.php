<?php
/* Copyright (C) 2016   Marcos García   <marcosgdf@gmail.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

require '../main.inc.php';
require 'class/ProductAttribute.class.php';
require 'class/ProductAttributeValue.class.php';

$id = GETPOST('id', 'int');
$valueid = GETPOST('valueid', 'alpha');
$action = GETPOST('action', 'aZ09');
$label = GETPOST('label', 'alpha');
$ref = GETPOST('ref', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

$object = new ProductAttribute($db);
$objectval = new ProductAttributeValue($db);

if ($object->fetch($id) < 1) {
	dol_print_error($db, $langs->trans('ErrorRecordNotFound'));
	exit();
}


/*
 * Actions
 */

if ($cancel) $action = '';

if ($action) {
	if ($action == 'update') {
		$object->ref = $ref;
		$object->label = $label;

		if ($object->update($user) < 1) {
			setEventMessages($langs->trans('CoreErrorMessage'), $object->errors, 'errors');
		} else {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header('Location: '.dol_buildpath('/variants/card.php?id='.$id, 2));
			exit();
		}
	} elseif ($action == 'update_value') {
		if ($objectval->fetch($valueid) > 0) {
			$objectval->ref = $ref;
			$objectval->value = GETPOST('value', 'alpha');

			if (empty($objectval->ref))
			{
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Ref")), null, 'errors');
			}
			if (empty($objectval->value))
			{
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
			}

			if (!$error)
			{
				if ($objectval->update($user) > 0) {
					setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
				} else {
					setEventMessage($langs->trans('CoreErrorMessage'), $objectval->errors, 'errors');
				}
			}
		}

		header('Location: '.dol_buildpath('/variants/card.php?id='.$object->id, 2));
		exit();
	}
}

if ($confirm == 'yes') {
	if ($action == 'confirm_delete') {
		$db->begin();

		$res = $objectval->deleteByFkAttribute($object->id, $user);

		if ($res < 1 || ($object->delete($user) < 1)) {
			$db->rollback();
			setEventMessages($langs->trans('CoreErrorMessage'), $object->errors, 'errors');
			header('Location: '.dol_buildpath('/variants/card.php?id='.$object->id, 2));
		} else {
			$db->commit();
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header('Location: '.dol_buildpath('/variants/list.php', 2));
		}
		exit();
	} elseif ($action == 'confirm_deletevalue')
	{
		if ($objectval->fetch($valueid) > 0) {
			if ($objectval->delete($user) < 1) {
				setEventMessages($langs->trans('CoreErrorMessage'), $objectval->errors, 'errors');
			} else {
				setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			}

			header('Location: '.dol_buildpath('/variants/card.php?id='.$object->id, 2));
			exit();
		}
	}
}


/*
 * View
 */

$langs->load('products');

$title = $langs->trans('ProductAttributeName', dol_htmlentities($object->label));

llxHeader('', $title);

//print load_fiche_titre($title);

$h = 0;
$head[$h][0] = DOL_URL_ROOT.'/variants/card.php?id='.$object->id;
$head[$h][1] = $langs->trans("ProductAttributeName");
$head[$h][2] = 'variant';
$h++;

print dol_get_fiche_head($head, 'variant', $langs->trans('ProductAttributeName'), -1, 'generic');

if ($action == 'edit') {
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.$id.'">';
		print '<input type="hidden" name="valueid" value="'.$valueid.'">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
}


if ($action != 'edit') {
	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
}
print '<table class="border centpercent tableforfield">';
print '<tr>';
print '<td class="titlefield'.($action == 'edit' ? ' fieldrequired' : '').'">'.$langs->trans('Ref').'</td>';
print '<td>';
if ($action == 'edit') {
	print '<input type="text" name="ref" value="'.$object->ref.'">';
} else {
	print dol_htmlentities($object->ref);
}
print '</td>';
print '</tr>';
print '<tr>';
print '<td'.($action == 'edit' ? ' class="fieldrequired"' : '').'>'.$langs->trans('Label').'</td>';
print '<td>';
if ($action == 'edit') {
	print '<input type="text" name="label" value="'.$object->label.'">';
} else {
	print dol_htmlentities($object->label);
}
print '</td>';
print '</tr>';

print '</table>';


if ($action != 'edit') {
	print '</div>';
}

print dol_get_fiche_end();

if ($action == 'edit') {
	print '<div style="text-align: center;">';
	print '<div class="inline-block divButAction">';
	print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
	print '&nbsp; &nbsp;';
	print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';
	print '</div></form>';
} else {
	if ($action == 'delete') {
		$form = new Form($db);

		print $form->formconfirm(
			"card.php?id=".$object->id,
			$langs->trans('Delete'),
			$langs->trans('ProductAttributeDeleteDialog'),
			"confirm_delete",
			'',
			0,
			1
		);
	} elseif ($action == 'delete_value') {
		if ($objectval->fetch($valueid) > 0) {
			$form = new Form($db);

			print $form->formconfirm(
				"card.php?id=".$object->id."&valueid=".$objectval->id,
				$langs->trans('Delete'),
				$langs->trans('ProductAttributeValueDeleteDialog', dol_htmlentities($objectval->value), dol_htmlentities($objectval->ref)),
				"confirm_deletevalue",
				'',
				0,
				1
			);
		}
	}

	?>

	<div class="tabsAction">
		<div class="inline-block divButAction">
			<a href="card.php?id=<?php echo $object->id ?>&action=edit&token=<?php echo newToken(); ?>" class="butAction"><?php echo $langs->trans('Modify') ?></a>
			<a href="card.php?id=<?php echo $object->id ?>&action=delete&token=<?php echo newToken(); ?>" class="butAction"><?php echo $langs->trans('Delete') ?></a>
		</div>
	</div>


	<?php

	print load_fiche_titre($langs->trans("PossibleValues"));

	if ($action == 'edit_value') {
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update_value">';
		print '<input type="hidden" name="id" value="'.$id.'">';
		print '<input type="hidden" name="valueid" value="'.$valueid.'">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}

	print '<table class="liste">';
	print '<tr class="liste_titre">';
	print '<th class="liste_titre titlefield">'.$langs->trans('Ref').'</th>';
	print '<th class="liste_titre">'.$langs->trans('Value').'</th>';
	print '<th class="liste_titre"></th>';
	print '</tr>';

	foreach ($objectval->fetchAllByProductAttribute($object->id) as $attrval) {
		print '<tr class="oddeven">';
		if ($action == 'edit_value' && ($valueid == $attrval->id)) {
			?>
				<td><input type="text" name="ref" value="<?php echo $attrval->ref ?>"></td>
				<td><input type="text" name="value" value="<?php echo $attrval->value ?>"></td>
				<td class="right">
					<input type="submit" value="<?php echo $langs->trans("Save") ?>" class="button button-save">
					&nbsp; &nbsp;
					<input type="submit" name="cancel" value="<?php echo $langs->trans("Cancel") ?>" class="button button-cancel">
				</td>
			<?php
		} else {
			?>
				<td><?php echo dol_htmlentities($attrval->ref) ?></td>
				<td><?php echo dol_htmlentities($attrval->value) ?></td>
				<td class="right">
					<a class="editfielda marginrightonly" href="card.php?id=<?php echo $object->id ?>&action=edit_value&valueid=<?php echo $attrval->id ?>"><?php echo img_edit() ?></a>
					<a href="card.php?id=<?php echo $object->id ?>&action=delete_value&token=<?php echo newToken(); ?>&valueid=<?php echo $attrval->id ?>"><?php echo img_delete() ?></a>
				</td>
			<?php
		}
		print '</tr>';
	}
	print '</table>';

	if ($action == 'edit_value') {
		print '</form>';
	}

	print '<div class="tabsAction">';
	print '<div class="inline-block divButAction">';
	print '<a href="create_val.php?id='.$object->id.'" class="butAction">'.$langs->trans('Create').'</a>';
	print '</div>';
	print '</div>';
}

// End of page
llxFooter();
$db->close();
