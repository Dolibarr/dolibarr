<?php
/* Copyright (C) 2010-2011 Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012 Laurent Destailleur <eldy@users.sourceforge.net>
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

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

?>

<!-- BEGIN PHP TEMPLATE CARD_CREATE.TPL.PHP INDIVIDUAL -->

<?php echo $this->control->tpl['title']; ?>

<?php echo $this->control->tpl['error']; ?>

<?php if ($conf->use_javascript_ajax) { ?>
<?php echo $this->control->tpl['ajax_selecttype']; ?>
<br>
<?php echo $langs->trans("ThirdPartyType") ?>: &nbsp;
<input type="radio" id="radiocompany" class="flat" name="private" value="0">
<?php echo $langs->trans("CompanyFoundation"); ?> &nbsp; &nbsp;
<input type="radio" id="radioprivate" class="flat" name="private" value="1" checked> <?php echo $langs->trans("Individual"); ?> (<?php echo $langs->trans("ToCreateContactWithSameName") ?>)
<br>
<br>
<?php echo $this->control->tpl['ajax_selectcountry']; ?>
<?php } ?>

<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST" name="formsoc">

<input type="hidden" name="action" value="add">
<input type="hidden" name="canvas" value="<?php echo $canvas ?>">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="private" value="<?php echo $this->control->tpl['particulier']; ?>">
<?php if ($this->control->tpl['auto_customercode'] || $this->control->tpl['auto_suppliercode']) { ?>
<input type="hidden" name="code_auto" value="1">
<?php } ?>

<table class="border allwidth">

<tr>
	<td><span class="fieldrequired"><?php echo $langs->trans('LastName'); ?></span></td>
	<td><input type="text" size="30" maxlength="60" name="nom" value="<?php echo $this->control->tpl['nom']; ?>"></td>
	<?php if (! empty($conf->global->SOCIETE_USEPREFIX)) { ?>
	<td><?php echo $langs->trans('Prefix'); ?></td>
	<td><input type="text" size="5" maxlength="5" name="prefix_comm" value="<?php echo $this->control->tpl['prefix_comm']; ?>"></td>
	<?php } ?>
</tr>

<tr>
	<td><?php echo $langs->trans('FirstName'); ?></td>
	<td><input type="text" size="30" name="firstname" value="<?php echo $this->control->tpl['firstname']; ?>"></td>
	<td colspan="2">&nbsp;</td>
</tr>

<tr>
	<td><?php echo $langs->trans("UserTitle"); ?></td>
	<td><?php echo $this->control->tpl['select_civility']; ?></td>
	<td colspan="2">&nbsp;</td>
</tr>

<tr>
	<td width="25%"><span class="fieldrequired"><?php echo $langs->trans('ProspectCustomer'); ?></span></td>
	<td width="25%"><?php echo $this->control->tpl['select_customertype']; ?></td>

    <td width="25%"><?php echo $langs->trans('CustomerCode'); ?></td>
    <td width="25%">
		<table class="nobordernopadding">
			<tr>
				<td><input type="text" name="code_client" size="16" value="<?php echo $this->control->tpl['customercode']; ?>" maxlength="15"></td>
				<td><?php echo $this->control->tpl['help_customercode']; ?></td>
			</tr>
		</table>
	</td>
</tr>

<?php if ($this->control->tpl['supplier_enabled']) { ?>
<tr>
	<td><span class="fieldrequired"><?php echo $langs->trans('Supplier'); ?></span></td>
	<td><?php echo $this->control->tpl['yn_supplier']; ?></td>
    <td><?php echo $langs->trans('SupplierCode'); ?></td>
    <td>
    	<table class="nobordernopadding">
    		<tr>
    			<td><input type="text" name="code_fournisseur" size="16" value="<?php echo $this->control->tpl['suppliercode']; ?>" maxlength="15"></td>
    			<td><?php echo $this->control->tpl['help_suppliercode']; ?></td>
    		</tr>
    	</table>
	</td>
</tr>

<?php if (count($this->control->tpl['suppliercategory']) > 0) { ?>
<tr>
	<td><?php echo $langs->trans('SupplierCategory'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['select_suppliercategory']; ?></td>
</tr>
<?php } }?>

<?php if (! empty($conf->barcode->enabled)) { ?>
<tr>
	<td><?php echo $langs->trans('Gencod'); ?></td>
	<td colspan="3"><input type="text" name="barcode" value="<?php echo $this->control->tpl['barcode']; ?>"></td>
</tr>
<?php } ?>

<tr>
	<td class="tdtop"><?php echo $langs->trans('Address'); ?></td>
	<td colspan="3"><textarea name="adresse" cols="40" rows="3"><?php echo $this->control->tpl['address']; ?></textarea></td>
</tr>

<tr>
	<td><?php echo $langs->trans('Zip'); ?></td>
	<td><input size="6" type="text" name="zip" value="<?php echo $this->control->tpl['zip']; ?>"><?php echo $this->control->tpl['autofilltownfromzip']; ?></td>
	<td><?php echo $langs->trans('Town'); ?></td>
	<td><input type="text" name="town" value="<?php echo $this->control->tpl['town']; ?>"></td>
</tr>

<tr>
	<td width="25%"><?php echo $langs->trans('Country'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['select_country']; echo $this->control->tpl['info_admin']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('State'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['select_state']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('Phone'); ?></td>
	<td><input type="text" name="tel" value="<?php echo $this->control->tpl['tel']; ?>"></td>
	<td><?php echo $langs->trans('Fax'); ?></td>
	<td><input type="text" name="fax" value="<?php echo $this->control->tpl['fax']; ?>"></td>
</tr>

<tr>
	<td><?php echo $langs->trans('EMail').($conf->global->SOCIETE_EMAIL_MANDATORY?'*':''); ?></td>
	<td><input type="text" name="email" size="32" value="<?php echo $this->control->tpl['email']; ?>"></td>
	<td><?php echo $langs->trans('Web'); ?></td>
	<td><input type="text" name="url" size="32" value="<?php echo $this->control->tpl['url']; ?>"></td>
</tr>

<?php if (! empty($conf->global->MAIN_MULTILANGS)) { ?>
<tr>
	<td><?php echo $langs->trans("DefaultLang"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['select_lang']; ?></td>
</tr>
<?php } ?>

<tr>
	<td><?php echo $langs->trans('VATIsUsed'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['yn_assujtva']; ?></td>
</tr>

<?php if(!empty($this->control->tpl['localtax'])) echo $this->control->tpl['localtax']; ?>

<?php if ($user->rights->societe->client->voir) { ?>
<tr>
	<td><?php echo $langs->trans("AllocateCommercial"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['select_users']; ?></td>
</tr>
<?php } ?>

<tr>
	<td colspan="4" align="center"><input type="submit" class="button" value="<?php echo $langs->trans('AddThirdParty'); ?>"></td>
</tr>

</table>
</form>

<!-- END PHP TEMPLATE -->
