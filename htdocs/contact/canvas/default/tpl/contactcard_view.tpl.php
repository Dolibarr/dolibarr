<?php
/* Copyright (C) 2010-2012 Regis Houssin <regis.houssin@inodbox.com>
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

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}


$contact = $GLOBALS['objcanvas']->control->object;

print "<!-- BEGIN PHP TEMPLATE CONTACTCARD_VIEW.TPL.PHP DEFAULT -->\n";
echo $this->control->tpl['showhead'];

dol_htmloutput_errors($this->control->tpl['error'], $this->control->tpl['errors']);

if (!empty($this->control->tpl['action_create_user'])) {
	echo $this->control->tpl['action_create_user'];
}
if (!empty($this->control->tpl['action_delete'])) {
	echo $this->control->tpl['action_delete'];
} ?>

<table class="border allwidth">

<tr>
	<td width="20%"><?php echo $langs->trans("Ref"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['showrefnav']; ?></td>
</tr>

<tr>
	<td width="20%"><?php echo $langs->trans("Lastname"); ?></td>
	<td width="30%"><?php echo $this->control->tpl['name']; ?></td>
	<td width="25%"><?php echo $langs->trans("Firstname"); ?></td>
	<td width="25%"><?php echo $this->control->tpl['firstname']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("ThirdParty"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['company']; ?></td>
</tr>

<tr>
	<td width="15%"><?php echo $langs->trans("UserTitle"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['civility']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("PostOrFunction"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['poste']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("Address"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['address']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("Zip").' / '.$langs->trans("Town"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['zip'].$this->control->tpl['town']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("Country"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['country']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('State'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['departement']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("PhonePro"); ?></td>
	<td><?php echo $this->control->tpl['phone_pro']; ?></td>
	<td><?php echo $langs->trans("PhonePerso"); ?></td>
	<td><?php echo $this->control->tpl['phone_perso']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("PhoneMobile"); ?></td>
	<td><?php echo $this->control->tpl['phone_mobile']; ?></td>
	<td><?php echo $langs->trans("Fax"); ?></td>
	<td><?php echo $this->control->tpl['fax']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("EMail"); ?></td>
	<td><?php echo $this->control->tpl['email']; ?></td>
	<?php if ($this->control->tpl['nb_emailing']) { ?>
	<td class="nowrap"><?php echo $langs->trans("NbOfEMailingsReceived"); ?></td>
	<td><?php echo $this->control->tpl['nb_emailing']; ?></td>
	<?php } else { ?>
	<td colspan="2">&nbsp;</td>
	<?php } ?>
</tr>

<tr>
	<td><?php echo $langs->trans("ContactVisibility"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['visibility']; ?></td>
</tr>

<tr>
	<td class="tdtop"><?php echo $langs->trans("Note"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['note']; ?></td>
</tr>

<?php foreach ($this->control->tpl['contact_element'] as $element) { ?>
<tr>
	<td><?php echo $element['linked_element_label']; ?></td>
	<td colspan="3"><?php echo $element['linked_element_value']; ?></td>
</tr>
<?php } ?>

<tr>
	<td><?php echo $langs->trans("DolibarrLogin"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['dolibarr_user']; ?></td>
</tr>

</table>

<?php echo $this->control->tpl['showend'];

if (empty($user->socid)) {
	print '<div class="tabsAction">';
	if ($user->rights->societe->contact->creer) {
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$this->control->tpl['id'].'&action=edit&token='.newToken().'&canvas='.$canvas.'">'.$langs->trans('Modify').'</a>';
	}

	if (!$this->control->tpl['user_id'] && $user->rights->user->user->creer) {
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$this->control->tpl['id'].'&action=create_user&token='.newToken().'&canvas='.$canvas.'">'.$langs->trans("CreateDolibarrLogin").'</a>';
	}

	if ($user->rights->societe->contact->supprimer) {
		print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$this->control->tpl['id'].'&action=delete&token='.newToken().'&canvas='.$canvas.'">'.$langs->trans('Delete').'</a>';
	}

	print '</div><br>';
}

echo $this->control->tpl['actionstodo'];

echo $this->control->tpl['actionsdone'];

print "<!-- END PHP TEMPLATE -->\n";
