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

<?php if ($this->object->tpl['action_delete']) echo $this->object->tpl['action_delete']; ?>

<?php if ($mesg) { ?>
<div class="error"><?php echo $mesg; ?></div>
<?php } ?>

<form name="formsoc" method="POST">
<input type="hidden" name="canvas" value="<?php echo $canvas ?>">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<table class="border" width="100%">

<tr>
	<td width="20%"><?php echo $langs->trans('Name'); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['showrefnav']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('Prefix'); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['prefix_comm']; ?></td>
</tr>

<?php if ($this->object->tpl['client']) { ?>
<tr>
	<td><?php echo $langs->trans('CustomerCode'); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['code_client']; ?>
	<?php if ($this->object->tpl['checkcustomercode'] <> 0) { ?>
	<font class="error">(<?php echo $langs->trans("WrongCustomerCode"); ?>)</font>
	<?php } ?>
	</td>
</tr>
<?php } ?>

<?php if ($this->object->tpl['fournisseur']) { ?>
<tr>
	<td><?php echo $langs->trans('SupplierCode'); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['code_fournisseur']; ?>
	<?php if ($this->object->tpl['checksuppliercode'] <> 0) { ?>
	<font class="error">(<?php echo $langs->trans("WrongSupplierCode"); ?>)</font>
	<?php } ?>
	</td>
</tr>
<?php } ?>

<?php if ($conf->global->MAIN_MODULE_BARCODE) { ?>
<tr>
	<td><?php echo $langs->trans('Gencod'); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['gencod']; ?></td>
</tr>
<?php } ?>

<tr>
	<td valign="top"><?php echo $langs->trans('Address'); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['address']; ?></td>
</tr>

<tr>
	<td width="25%"><?php echo $langs->trans('Zip'); ?></td>
	<td width="25%"><?php echo $this->object->tpl['cp']; ?></td>
	<td width="25%"><?php echo $langs->trans('Town'); ?></td>
	<td width="25%"><?php echo $this->object->tpl['ville']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("Country"); ?></td>
	<td colspan="3" nowrap="nowrap"><?php echo $this->object->tpl['country']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('State'); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['departement']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('Phone'); ?></td>
	<td><?php echo $this->object->tpl['phone']; ?></td>
	<td><?php echo $langs->trans('Fax'); ?></td>
	<td><?php echo $this->object->tpl['fax']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('EMail'); ?></td>
	<td><?php echo $this->object->tpl['email'];; ?></td>
	<td><?php echo $langs->trans('Web'); ?></td>
	<td><?php echo $this->object->tpl['url']; ?></td>
</tr>

<?php
for ($i=1; $i<=4; $i++) {
	if ($this->object->tpl['langprofid'.$i]!='-')	{
		if ($i==1 || $i==3) echo '<tr>';
		echo '<td>'.$this->object->tpl['langprofid'.$i].'</td>';
		echo '<td>'.$this->object->tpl['profid'.$i];
		if ($this->object->tpl['profid'.$i]) {
			if ($this->object->tpl['checkprofid'.$i] > 0) echo ' &nbsp; '.$this->object->tpl['urlprofid'.$i];
			else echo ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
		}
		echo '</td>';
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
	<td><?php echo $this->object->tpl['tva_assuj']; ?></td>

<?php if ($conf->use_javascript_ajax) { ?>
<script language="JavaScript" type="text/javascript">
function CheckVAT(a) {
	newpopup('<?php echo DOL_URL_ROOT; ?>/societe/checkvat/checkVatPopup.php?vatNumber='+a,'<?php echo dol_escape_js($langs->trans("VATIntraCheckableOnEUSite")); ?>',500,260);
}
</script>
<?php } ?>

	<td nowrap="nowrap"><?php echo $langs->trans('VATIntra'); ?></td>
	<td><?php echo $this->object->tpl['tva_intra']; ?></td>
</tr>

<?php if(!empty($this->object->tpl['localtax'])) echo $this->object->tpl['localtax']; ?>

<tr>
	<td><?php echo $langs->trans('Capital'); ?></td>
	<td colspan="3">
	<?php
	if ($this->object->tpl['capital']) echo $this->object->tpl['capital'].' '.$langs->trans("Currency".$conf->monnaie);
	else echo '&nbsp;';
	?>
	</td>
</tr>

<tr>
	<td><?php echo $langs->trans('JuridicalStatus'); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['forme_juridique']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("Type"); ?></td>
	<td><?php echo $this->object->tpl['typent']; ?></td>
	<td><?php echo $langs->trans("Staff"); ?></td>
	<td><?php echo $this->object->tpl['effectif']; ?></td>
</tr>

<?php if ($conf->global->MAIN_MULTILANGS) { ?>
<tr>
	<td><?php echo $langs->trans("DefaultLang"); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['default_lang']; ?></td>
</tr>
<?php } ?>

<tr>
	<td>
	<table width="100%" class="nobordernopadding">
		<tr>
			<td><?php echo $langs->trans('RIB'); ?></td>
			<td align="right">
			<?php if ($user->rights->societe->creer) { ?>
			<a href="<?php echo DOL_URL_ROOT.'/societe/rib.php?socid='.$this->object->tpl['id']; ?>"><?php echo $this->object->tpl['image_edit']; ?></a>
			<?php } else { ?>
			&nbsp;
			<?php } ?>
			</td>
		</tr>
	</table>
	</td>
	<td colspan="3"><?php echo $this->object->tpl['display_rib']; ?></td>
</tr>

<tr>
	<td>
	<table width="100%" class="nobordernopadding">
		<tr>
			<td><?php echo $langs->trans('ParentCompany'); ?></td>
			<td align="right">
			<?php if ($user->rights->societe->creer) { ?>
			<a href="<?php echo DOL_URL_ROOT.'/societe/lien.php?socid='.$this->object->tpl['id']; ?>"><?php echo $this->object->tpl['image_edit']; ?></a>
			<?php } else { ?>
			&nbsp;
			<?php } ?>
			</td>
		</tr>
	</table>
	</td>
	<td colspan="3"><?php echo $this->object->tpl['parent_company']; ?></td>
</tr>

<tr>
	<td>
	<table width="100%" class="nobordernopadding">
		<tr>
			<td><?php echo $langs->trans('SalesRepresentatives'); ?></td>
			<td align="right">
			<?php if ($user->rights->societe->creer) { ?>
			<a href="<?php echo DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$this->object->tpl['id']; ?>"><?php echo $this->object->tpl['image_edit']; ?></a>
			<?php } else { ?>
			&nbsp;
			<?php } ?>
			</td>
		</tr>
	</table>
	</td>
	<td colspan="3"><?php echo $this->object->tpl['sales_representatives'];	?></td>
</tr>

<?php if ($conf->adherent->enabled) { ?>
<tr>
	<td width="25%" valign="top"><?php echo $langs->trans("LinkedToDolibarrMember"); ?></td>
	<td colspan="3"><?php echo $this->object->tpl['linked_member']; ?></td>
</tr>
<?php } ?>

</table>
</form>

</div>

<!-- END PHP TEMPLATE -->