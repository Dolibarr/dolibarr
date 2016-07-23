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
$valueid = GETPOST('valueid');
$action = GETPOST('action');
$label = GETPOST('label');
$ref = GETPOST('ref');
$confirm = GETPOST('confirm');

$prodattr = new ProductAttribute($db);
$prodattrval = new ProductAttributeValue($db);

if ($prodattr->fetch($id) < 1) {
	dol_print_error($db, $langs->trans('ErrorRecordNotFound'));
	die;
}

if ($_POST) {

	if ($action == 'edit') {

		$prodattr->label = $label;
		$prodattr->ref = $ref;

		if ($prodattr->update() < 1) {
			setEventMessage($langs->trans('CoreErrorMessage'), 'errors');
		} else {
			setEventMessage($langs->trans('RecordSaved'));
			header('Location: '.dol_buildpath('/attributes/card.php?id='.$id, 2));
			die;
		}
	} elseif ($action == 'edit_value') {

		if ($prodattrval->fetch($valueid) > 0) {

			$prodattrval->ref = $ref;
			$prodattrval->value = GETPOST('value');

			if ($prodattrval->update() > 0) {
				setEventMessage($langs->trans('RecordSaved'));
			} else {
				setEventMessage($langs->trans('CoreErrorMessage'), 'errors');
			}
		}

		header('Location: '.dol_buildpath('/attributes/card.php?id='.$prodattr->id, 2));
		die;
	}

}

if ($confirm == 'yes') {
	if ($action == 'confirm_delete') {

		$db->begin();

		$res = $prodattrval->deleteByFkAttribute($prodattr->id);

		if ($res < 1 || ($prodattr->delete() < 1)) {
			$db->rollback();
			setEventMessage($langs->trans('CoreErrorMessage'), 'errors');
			header('Location: '.dol_buildpath('/attributes/card.php?id='.$prodattr->id, 2));
		} else {
			$db->commit();
			setEventMessage($langs->trans('RecordSaved'));
			header('Location: '.dol_buildpath('/attributes/list.php', 2));
		}

		die;
	} elseif ($action == 'confirm_deletevalue') {

		if ($prodattrval->fetch($valueid) > 0) {

			if ($prodattrval->delete() < 1) {
				setEventMessage($langs->trans('CoreErrorMessage'), 'errors');
			} else {
				setEventMessage($langs->trans('RecordSaved'));
			}

			header('Location: '.dol_buildpath('/attributes/card.php?id='.$prodattr->id, 2));
			die;
		}
	}
}

$langs->load('products');

$title = $langs->trans('ProductAttributeName', dol_htmlentities($prodattr->label));
$var = false;

llxHeader('', $title);

print_fiche_titre($title);

dol_fiche_head();

if ($action == 'edit') {
	print '<form method="post">';
}

?>
	<table class="border" style="width: 100%">
		<tr>
			<td style="width: 15%" class="fieldrequired"><?php echo $langs->trans('Ref') ?></td>
			<td>
				<?php if ($action == 'edit') {
					print '<input type="text" name="ref" value="'.$prodattr->ref.'">';
				} else {
					print dol_htmlentities($prodattr->ref);
				} ?>
			</td>
		</tr>
		<tr>
			<td style="width: 15%" class="fieldrequired"><?php echo $langs->trans('Label') ?></td>
			<td>
				<?php if ($action == 'edit') {
					print '<input type="text" name="label" value="'.$prodattr->label.'">';
				} else {
					print dol_htmlentities($prodattr->label);
				} ?>
			</td>
		</tr>

	</table>

<?php
dol_fiche_end();

if ($action == 'edit') { ?>
	<div style="text-align: center;">
		<div class="inline-block divButAction">
			<input type="submit" class="button" value="<?php echo $langs->trans('Save') ?>">
			<a href="card.php?id=<?php echo $prodattr->id ?>" class="butAction"><?php echo $langs->trans('Cancel') ?></a>
		</div>
	</div></form>
<?php } else {

	if ($action == 'delete') {
		$form = new Form($db);

		print $form->formconfirm(
			"card.php?id=".$prodattr->id,
			$langs->trans('Delete'),
			$langs->trans('ProductAttributeDeleteDialog'),
			"confirm_delete",
			'',
			0,
			1
		);
	} elseif ($action == 'delete_value') {

		if ($prodattrval->fetch($valueid) > 0) {

			$form = new Form($db);

			print $form->formconfirm(
				"card.php?id=".$prodattr->id."&valueid=".$prodattrval->id,
				$langs->trans('Delete'),
				$langs->trans('ProductAttributeValueDeleteDialog', dol_htmlentities($prodattrval->value), dol_htmlentities($prodattrval->ref)),
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
			<a href="card.php?id=<?php echo $prodattr->id ?>&action=edit" class="butAction"><?php echo $langs->trans('Modify') ?></a>
			<a href="card.php?id=<?php echo $prodattr->id ?>&action=delete" class="butAction"><?php echo $langs->trans('Delete') ?></a>
		</div>
	</div>

	<?php if ($action == 'edit_value'): ?>
	<form method="post">
	<?php endif ?>

	<table class="liste">
		<tr class="liste_titre">
			<th class="liste_titre"><?php echo $langs->trans('Ref') ?></th>
			<th class="liste_titre"><?php echo $langs->trans('Value') ?></th>
			<th class="liste_titre"></th>
		</tr>

		<?php foreach ($prodattrval->fetchAllByProductAttribute($prodattr->id) as $attrval): ?>
		<tr <?php echo $bc[!$var] ?>>
			<?php if ($action == 'edit_value' && ($valueid == $attrval->id)): ?>
				<td><input type="text" name="ref" value="<?php echo $attrval->ref ?>"></td>
				<td><input type="text" name="value" value="<?php echo $attrval->value ?>"></td>
				<td style="text-align: right">
					<input type="submit" value="<?php echo $langs->trans('Save') ?>" class="button">
					<a href="card.php?id=<?php echo $prodattr->id ?>" class="butAction"><?php echo $langs->trans('Cancel') ?></a>
				</td>
			<?php else: ?>
				<td><?php echo dol_htmlentities($attrval->ref) ?></td>
				<td><?php echo dol_htmlentities($attrval->value) ?></td>
				<td style="text-align: right">
					<a href="card.php?id=<?php echo $prodattr->id ?>&action=edit_value&valueid=<?php echo $attrval->id ?>"><?php echo img_edit() ?></a>
					<a href="card.php?id=<?php echo $prodattr->id ?>&action=delete_value&valueid=<?php echo $attrval->id ?>"><?php echo img_delete() ?></a>
				</td>
			<?php endif; ?>
		</tr>
		<?php
			$var = !$var;
			endforeach
		?>
	</table>

	<?php if ($action == 'edit_value'): ?>
	</form>
	<?php endif ?>

	<div class="tabsAction">
		<div class="inline-block divButAction">
			<a href="create_val.php?id=<?php echo $prodattr->id ?>" class="butAction"><?php echo $langs->trans('Create') ?></a>
		</div>
	</div>

	<?php
}

llxFooter();