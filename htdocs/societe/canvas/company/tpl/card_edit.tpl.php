<?php
/* Copyright (C) 2010      Regis Houssin       <regis.houssin@inodbox.com>
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


$contact = $GLOBALS['objcanvas']->control->object;

?>

<!-- BEGIN PHP TEMPLATE CARD_EDIT.TPL.PHP COMPANY -->

<?php echo $this->control->tpl['title']; ?>

<?php echo $this->control->tpl['error']; ?>

<?php echo $this->control->tpl['ajax_selectcountry']; ?>
<?php if ($this->control->tpl['js_checkVatPopup']) echo $this->control->tpl['js_checkVatPopup']; ?>

<form action="<?php echo $_SERVER["PHP_SELF"].'?socid='.$this->control->tpl['id']; ?>" method="POST" name="formsoc">
<input type="hidden" name="canvas" value="<?php echo $canvas ?>">
<input type="hidden" name="action" value="update">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="socid" value="<?php echo $this->control->tpl['id']; ?>">
<?php if ($this->control->tpl['auto_customercode'] || $this->control->tpl['auto_suppliercode']) { ?>
<input type="hidden" name="code_auto" value="1">
<?php } ?>

<table class="border allwidth">

<tr>
	<td><span class="fieldrequired"><?php echo $langs->trans('ThirdPartyName'); ?></span></td>
	<td colspan="3"><input type="text" size="40" maxlength="60" name="nom" value="<?php echo $this->control->tpl['nom']; ?>"></td>
</tr>

<?php if (! empty($conf->global->SOCIETE_USEPREFIX)) { ?>
<tr>
	<td><?php echo $langs->trans("Prefix"); ?></td>
	<td colspan="3">
	<?php if (($this->control->tpl['prefix_customercode'] || $this->control->tpl['prefix_suppliercode']) && $this->control->tpl['prefix_comm']) { ?>
	<input type="hidden" name="prefix_comm" value="<?php echo $this->control->tpl['prefix_comm']; ?>">
	<?php echo $this->control->tpl['prefix_comm']; ?>
	<?php } else { ?>
	<input type="text" size="5" maxlength="5" name="prefix_comm" value="<?php echo $this->control->tpl['prefix_comm']; ?>">
	<?php } ?>
	</td>
</tr>
<?php } ?>

<tr>
	<td width="25%"><span class="fieldrequired"><?php echo $langs->trans('ProspectCustomer'); ?></span></td>
	<td width="25%"><?php echo $this->control->tpl['select_customertype']; ?></td>
	<td width="25%"><?php echo $langs->trans('CustomerCode'); ?></td>
	<td width="25%">
		<table class="nobordernopadding">
			<tr>
				<td>
				<?php if ($this->control->tpl['ismodifiable_customercode']) { ?>
				<input type="text" name="code_client" size="16" value="<?php echo $this->control->tpl['customercode']; ?>" maxlength="15">
				<?php } else { ?>
				<?php  echo $this->control->tpl['customercode']; ?>
				<input type="hidden" name="code_client" value="<?php echo $this->control->tpl['customercode']; ?>">
				<?php } ?>
				</td>
				<td><?php echo $this->tpl['help_customercode']; ?></td>
			</tr>
		</table>
	</td>
</tr>

<tr>
	<td><span class="fieldrequired"><?php echo $langs->trans('Supplier'); ?></span></td>
	<td><?php echo $this->control->tpl['yn_supplier']; ?></td>
	<td><?php echo $langs->trans('SupplierCode'); ?></td>
	<td>
		<table class="nobordernopadding">
			<tr>
				<td>
				<?php if ($this->control->tpl['ismodifiable_suppliercode']) { ?>
				<input type="text" name="code_fournisseur" size="16" value="<?php echo $this->control->tpl['suppliercode']; ?>" maxlength="15">
				<?php } else { ?>
				<?php  echo $this->control->tpl['suppliercode']; ?>
				<input type="hidden" name="code_fournisseur" value="<?php echo $this->control->tpl['suppliercode']; ?>">
				<?php } ?>
				</td>
				<td><?php echo $this->control->tpl['help_suppliercode']; ?></td>
			</tr>
		</table>
	</td>
</tr>

<?php
if ($this->control->tpl['fournisseur']) {
	if (count($this->control->tpl['suppliercategory']) > 0) { ?>
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
	<td colspan="3"><textarea name="adress" cols="40" rows="3"><?php echo $this->control->tpl['address']; ?></textarea></td>
</tr>

<tr>
	<td><?php echo $langs->trans('Zip'); ?></td>
	<td><?php echo $this->control->tpl['select_zip']; ?></td>
	<td><?php echo $langs->trans('Town'); ?></td>
	<td><?php echo $this->control->tpl['select_town']; ?></td>
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
	<td><input type="text" name="phone" value="<?php echo $this->control->tpl['phone']; ?>"></td>
	<td><?php echo $langs->trans('Fax'); ?></td>
	<td><input type="text" name="fax" value="<?php echo $this->control->tpl['fax']; ?>"></td>
</tr>

<tr>
	<td><?php echo $langs->trans('EMail').($conf->global->SOCIETE_EMAIL_MANDATORY?'*':''); ?></td>
	<td><input type="text" name="email" size="32" value="<?php echo $this->control->tpl['email']; ?>"></td>
	<td><?php echo $langs->trans('Web'); ?></td>
	<td><input type="text" name="url" size="32" value="<?php echo $this->control->tpl['url']; ?>"></td>
</tr>

<?php
for ($i=1; $i<=4; $i++) {
	if ($this->control->tpl['langprofid'.$i]!='-') {
		if ($i==1 || $i==3) echo '<tr>';
		echo '<td>'.$this->control->tpl['langprofid'.$i].'</td>';
		echo '<td>'.$this->control->tpl['showprofid'.$i].'</td>';
		if ($i==2 || $i==4) echo '</tr>';
	} else {
		if ($i==1 || $i==3) echo '<tr>';
		echo '<td>&nbsp;</td>';
		echo '<td>&nbsp;</td>';
		if ($i==2 || $i==4) echo '</tr>';
	}
}
?>

<tr>
	<td><?php echo $langs->trans('VATIsUsed'); ?></td>
	<td><?php echo $this->control->tpl['yn_assujtva']; ?></td>
	<td class="nowrap"><?php echo $langs->trans('VATIntra'); ?></td>
	<td class="nowrap"><?php echo $this->control->tpl['tva_intra']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('Capital'); ?></td>
	<td colspan="3"><input type="text" name="capital" size="10" value="<?php echo $this->control->tpl['capital']; ?>"> <?php echo $langs->trans("Currency".$conf->currency); ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('JuridicalStatus'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['select_juridicalstatus']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("ThirdPartyType"); ?></td>
	<td><?php echo $this->control->tpl['select_companytype']; echo $this->control->tpl['info_admin']; ?></td>
	<td><?php echo $langs->trans("Staff"); ?></td>
	<td><?php echo $this->control->tpl['select_workforce']; echo $this->control->tpl['info_admin']; ?></td>
</tr>

<?php if (! empty($conf->global->MAIN_MULTILANGS)) { ?>
<tr>
	<td><?php echo $langs->trans("DefaultLang"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['select_lang']; ?></td>
</tr>
<?php } ?>

<?php if(!empty($this->control->tpl['localtax'])) echo $this->control->tpl['localtax']; ?>

</table>
<br>

<div align="center">
<input type="submit" class="button" name="save" value="<?php echo $langs->trans("Save"); ?>">
&nbsp; &nbsp;
<input type="submit" class="button" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
</div>

</form>

<!-- END PHP TEMPLATE -->