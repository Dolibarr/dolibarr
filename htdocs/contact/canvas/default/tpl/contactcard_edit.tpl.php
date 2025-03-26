<?php
/* Copyright (C) 2010       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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

/**
 * @var Canvas $this
 * @var Conf $conf
 * @var Contact $object
 * @var Translate $langs
 *
 * @var string $canvas
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}


$contact = $GLOBALS['objcanvas']->control->object;

?>

<!-- BEGIN PHP TEMPLATE CONTACTCARD_EDIT.TPL.PHP DEFAULT -->

<?php
print load_fiche_titre($this->control->tpl['title']);

dol_htmloutput_errors($this->control->tpl['error'], $this->control->tpl['errors']);

echo $this->control->tpl['ajax_selectcountry'];
?>

<br>

<form method="post" name="formsoc" action="<?php echo $_SERVER["PHP_SELF"].'?id='.GETPOST('id', 'int'); ?>">
<input type="hidden" name="token" value="<?php echo newToken(); ?>">
<input type="hidden" name="canvas" value="<?php echo $canvas ?>">
<input type="hidden" name="id" value="<?php echo GETPOST('id', 'int'); ?>">
<input type="hidden" name="action" value="update">
<input type="hidden" name="contactid" value="<?php echo $this->control->tpl['id']; ?>">
<input type="hidden" name="old_name" value="<?php echo $this->control->tpl['name']; ?>">
<input type="hidden" name="old_firstname" value="<?php echo $this->control->tpl['firstname']; ?>">
<?php if (!empty($this->control->tpl['company_id'])) { ?>
<input type="hidden" name="socid" value="<?php echo $this->control->tpl['company_id']; ?>">
<?php } ?>

<table class="border allwidth">

<tr>
	<td><?php echo $langs->trans("Ref"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['ref']; ?></td>
</tr>

<tr>
	<td width="15%" class="fieldrequired"><?php echo $langs->trans("Lastname").' / '.$langs->trans("Label"); ?></td>
	<td><input name="lastname" type="text" size="30" maxlength="80" value="<?php echo $this->control->tpl['name']; ?>"></td>
	<td width="20%"><?php echo $langs->trans("Firstname"); ?></td>
	<td width="25%"><input name="firstname" type="text" size="30" maxlength="80" value="<?php echo $this->control->tpl['firstname']; ?>"></td>
</tr>

<tr>
	<td><?php echo $langs->trans("ThirdParty"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['company']; ?></td>
</tr>

<tr>
	<td width="15%"><?php echo $langs->trans("UserTitle"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['select_civility']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("PostOrFunction"); ?></td>
	<td colspan="3"><input name="poste" type="text" class="minwidth200" maxlength="80" value="<?php echo $this->control->tpl['poste']; ?>"></td>
</tr>

<tr>
	<td><?php echo $langs->trans("Address"); ?></td>
	<td colspan="3"><textarea class="flat" name="address" cols="70"><?php echo $this->control->tpl['address']; ?></textarea></td>
</tr>

<tr>
	<td><?php echo $langs->trans("Zip").' / '.$langs->trans("Town"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['select_zip'].'&nbsp;'.$this->control->tpl['select_town']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("Country"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['select_country'].$this->control->tpl['info_admin']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('State'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['select_state']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("PhonePro"); ?></td>
	<td><input name="phone_pro" type="text" size="18" maxlength="80" value="<?php echo $this->control->tpl['phone_pro']; ?>"></td>
	<td><?php echo $langs->trans("PhonePerso"); ?></td>
	<td><input name="phone_perso" type="text" size="18" maxlength="80" value="<?php echo $this->control->tpl['phone_perso']; ?>"></td>
</tr>

<tr>
	<td><?php echo $langs->trans("PhoneMobile"); ?></td>
	<td><input name="phone_mobile" type="text" size="18" maxlength="80" value="<?php echo $this->control->tpl['phone_mobile']; ?>"></td>
	<td><?php echo $langs->trans("Fax"); ?></td>
	<td><input name="fax" type="text" size="18" maxlength="80" value="<?php echo $this->control->tpl['fax']; ?>"></td>
</tr>

<tr>
	<td><?php echo $langs->trans("Email"); ?></td>
	<td><input name="email" type="text" class="minwidth200" maxlength="80" value="<?php echo $this->control->tpl['email']; ?>"></td>
	<?php if ($this->control->tpl['nb_emailing']) { ?>
	<td class="nowrap"><?php echo $langs->trans("NbOfEMailingsReceived"); ?></td>
	<td><?php echo $this->control->tpl['nb_emailing']; ?></td>
	<?php } else { ?>
	<td colspan="2">&nbsp;</td>
	<?php } ?>
</tr>

<tr>
	<td><?php echo $langs->trans("ContactVisibility"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['select_visibility']; ?></td>
</tr>

<tr>
	<td class="tdtop"><?php echo $langs->trans("Note"); ?></td>
	<td colspan="3" valign="top"><textarea name="note" cols="70" rows="<?php echo ROWS_3; ?>"><?php echo $this->control->tpl['note']; ?></textarea></td>
</tr>

<?php
if (!empty($this->control->tpl['contact_element'])) {
	foreach ($this->control->tpl['contact_element'] as $element) {
		print '<tr>';
		print '<td>'.$element['linked_element_label'].'</td>';
		print '<td colspan="3">'.$element['linked_element_value'].'</td>';
		print '</tr>';
	}
} ?>

<tr>
	<td><?php echo $langs->trans("DolibarrLogin"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['dolibarr_user']; ?></td>
</tr>

<tr>
	<td colspan="4" class="center">
	<input type="submit" class="button button-save" name="save" value="<?php echo $langs->trans("Save"); ?>">&nbsp;
	<input type="submit" class="button button-cancel" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
	</td>
</tr>

</table><br>

</form>

<!-- END PHP TEMPLATE -->
