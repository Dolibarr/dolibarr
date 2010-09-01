<?php
/* Copyright (C) 2010 Regis Houssin <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */
?>

<!-- BEGIN PHP TEMPLATE -->

<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST" name="formsoc">

<input type="hidden" name="action" value="add">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="cleartype" value="0">
<input type="hidden" name="private" value="<?php echo $this->object->tpl['particulier']; ?>">
<?php if ($modCodeClient->code_auto || $modCodeFournisseur->code_auto) { ?>
<input type="hidden" name="code_auto" value="1">
<?php } ?>

<table class="border" width="100%">

<tr>
	<td><span class="fieldrequired"><?php echo $langs->trans('LastName'); ?></span></td>
	<td><input type="text" size="30" maxlength="60" name="nom" value="<?php echo $this->object->tpl['nom']; ?>"></td>
	<td><?php echo $langs->trans('Prefix'); ?></td>
	<td><input type="text" size="5" maxlength="5" name="prefix_comm" value="<?php echo $this->object->tpl['prefix_comm']; ?>"></td>
</tr>

<tr>
	<td><?php echo $langs->trans('FirstName'); ?></td>
	<td><input type="text" size="30" name="prenom" value="<?php echo $this->object->tpl['firstname']; ?>"></td>
	<td colspan="2">&nbsp;</td>
</tr>

<tr>
	<td><?php echo $langs->trans("UserTitle"); ?></td>
	<td><?php echo $this->object->tpl['select_civility']; ?></td>
	<td colspan="2">&nbsp;</td>
</tr>

<tr>
	<td width="25%"><span class="fieldrequired"><?php echo $langs->trans('ProspectCustomer'); ?></span></td>
	<td width="25%"><?php echo $this->object->tpl['select_customertype']; ?></td>

    <td width="25%"><?php echo $langs->trans('CustomerCode'); ?></td>
    <td width="25%">
		<table class="nobordernopadding">
			<tr>
				<td><input type="text" name="code_client" size="16" value="<?php echo $this->object->tpl['customercode']; ?>" maxlength="15"></td>
				<td><?php echo $this->object->tpl['help_customercode']; ?></td>
			</tr>
		</table>
	</td>
</tr>

<tr>
	<td><span class="fieldrequired"><?php echo $langs->trans('Supplier'); ?></span></td>
	<td><?php echo $this->object->tpl['yn_supplier']; ?></td>
    <td><?php echo $langs->trans('SupplierCode'); ?></td>
    <td>
    	<table class="nobordernopadding">
    		<tr>
    			<td><input type="text" name="code_fournisseur" size="16" value="<?php echo $this->object->tpl['suppliercode']; ?>" maxlength="15"></td>
    			<td><?php echo $this->object->tpl['help_suppliercode']; ?></td>
    		</tr>
    	</table>
	</td>
</tr>

<?php
if ($this->object->tpl['fournisseur']) {
	if (sizeof($this->object->tpl['suppliercategory']) > 0) { ?>
<tr>
	<td><?php echo $langs->trans('SupplierCategory'); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['select_suppliercategory']; ?></td>
</tr>
<?php } }?>

<?php if ($conf->global->MAIN_MODULE_BARCODE) { ?>
<tr>
	<td><?php echo $langs->trans('Gencod'); ?></td>
	<td colspan="3"><input type="text" name="gencod" value="<?php echo $this->object->tpl['gencod']; ?>"></td>
</tr>
<?php } ?>

<tr>
	<td valign="top"><?php echo $langs->trans('Address'); ?></td>
	<td colspan="3"><textarea name="adresse" cols="40" rows="3" wrap="soft"><?php echo $this->object->tpl['address']; ?></textarea></td>
</tr>

<tr>
	<td><?php echo $langs->trans('Zip'); ?></td>
	<td><input size="6" type="text" name="cp" value="<?php echo $this->object->tpl['cp']; ?>"><?php echo $this->object->tpl['autofilltownfromzip']; ?></td>
	<td><?php echo $langs->trans('Town'); ?></td>
	<td><input type="text" name="ville" value="<?php echo $this->object->tpl['ville']; ?>"></td>
</tr>

<tr>
	<td width="25%"><?php echo $langs->trans('Country'); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['select_country']; echo $this->object->tpl['info_admin']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('State'); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['select_state']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('Phone'); ?></td>
	<td><input type="text" name="tel" value="<?php echo $this->object->tpl['tel']; ?>"></td>
	<td><?php echo $langs->trans('Fax'); ?></td>
	<td><input type="text" name="fax" value="<?php echo $this->object->tpl['fax']; ?>"></td>
</tr>

<tr>
	<td><?php echo $langs->trans('EMail').($conf->global->SOCIETE_MAIL_REQUIRED?'*':''); ?></td>
	<td><input type="text" name="email" size="32" value="<?php echo $this->object->tpl['email']; ?>"></td>
	<td><?php echo $langs->trans('Web'); ?></td>
	<td><input type="text" name="url" size="32" value="<?php echo $this->object->tpl['url']; ?>"></td>
</tr>

<?php if ($conf->global->MAIN_MULTILANGS) { ?>
<tr>
	<td><?php echo $langs->trans("DefaultLang"); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['select_lang']; ?></td>
</tr>
<?php } ?>

<tr>
	<td><?php echo $langs->trans('VATIsUsed'); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['yn_assujtva']; ?></td>
</tr>

<?php if(!empty($this->object->tpl['localtax'])) echo $this->object->tpl['localtax']; ?>

<?php if ($user->rights->societe->client->voir) { ?>
<tr>
	<td><?php echo $langs->trans("AllocateCommercial"); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['select_users']; ?></td>
</tr>
<?php } ?>

<tr>
	<td colspan="4" align="center"><input type="submit" class="button" value="<?php echo $langs->trans('AddThirdParty'); ?>"></td>
</tr>

</table>
</form>

<!-- END PHP TEMPLATE -->