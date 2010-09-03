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

<?php if ($this->control->tpl['action_delete']) echo $this->control->tpl['action_delete']; ?>

<?php if ($mesg) { ?>
<div class="error"><?php echo $mesg; ?></div>
<?php } ?>

<form name="formsoc" method="POST">
<input type="hidden" name="canvas" value="<?php echo $canvas ?>">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<table class="border" width="100%">

<tr>
	<td width="20%"><?php echo $langs->trans('Name'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['showrefnav']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('Prefix'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['prefix_comm']; ?></td>
</tr>

<?php if ($this->control->tpl['client']) { ?>
<tr>
	<td><?php echo $langs->trans('CustomerCode'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['code_client']; ?>
	<?php if ($this->control->tpl['checkcustomercode'] <> 0) { ?>
	<font class="error">(<?php echo $langs->trans("WrongCustomerCode"); ?>)</font>
	<?php } ?>
	</td>
</tr>
<?php } ?>

<?php if ($this->control->tpl['fournisseur']) { ?>
<tr>
	<td><?php echo $langs->trans('SupplierCode'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['code_fournisseur']; ?>
	<?php if ($this->control->tpl['checksuppliercode'] <> 0) { ?>
	<font class="error">(<?php echo $langs->trans("WrongSupplierCode"); ?>)</font>
	<?php } ?>
	</td>
</tr>
<?php } ?>

<?php if ($conf->global->MAIN_MODULE_BARCODE) { ?>
<tr>
	<td><?php echo $langs->trans('Gencod'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['gencod']; ?></td>
</tr>
<?php } ?>

<tr>
	<td valign="top"><?php echo $langs->trans('Address'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['address']; ?></td>
</tr>

<tr>
	<td width="25%"><?php echo $langs->trans('Zip'); ?></td>
	<td width="25%"><?php echo $this->control->tpl['cp']; ?></td>
	<td width="25%"><?php echo $langs->trans('Town'); ?></td>
	<td width="25%"><?php echo $this->control->tpl['ville']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("Country"); ?></td>
	<td colspan="3" nowrap="nowrap"><?php echo $this->control->tpl['country']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('State'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['departement']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('Phone'); ?></td>
	<td><?php echo $this->control->tpl['phone']; ?></td>
	<td><?php echo $langs->trans('Fax'); ?></td>
	<td><?php echo $this->control->tpl['fax']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('EMail'); ?></td>
	<td><?php echo $this->control->tpl['email'];; ?></td>
	<td><?php echo $langs->trans('Web'); ?></td>
	<td><?php echo $this->control->tpl['url']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('VATIsUsed'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['tva_assuj']; ?></td>
</tr>

<?php if(!empty($this->control->tpl['localtax'])) echo $this->control->tpl['localtax']; ?>

<tr>
	<td><?php echo $langs->trans("Type"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['typent']; ?></td>
</tr>

<?php if ($conf->global->MAIN_MULTILANGS) { ?>
<tr>
	<td><?php echo $langs->trans("DefaultLang"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['default_lang']; ?></td>
</tr>
<?php } ?>

<tr>
	<td>
	<table width="100%" class="nobordernopadding">
		<tr>
			<td><?php echo $langs->trans('RIB'); ?></td>
			<td align="right">
			<?php if ($user->rights->societe->creer) { ?>
			<a href="<?php echo DOL_URL_ROOT.'/societe/rib.php?socid='.$this->control->tpl['id']; ?>"><?php echo $this->control->tpl['image_edit']; ?></a>
			<?php } else { ?>
			&nbsp;
			<?php } ?>
			</td>
		</tr>
	</table>
	</td>
	<td colspan="3"><?php echo $this->control->tpl['display_rib']; ?></td>
</tr>

<tr>
	<td>
	<table width="100%" class="nobordernopadding">
		<tr>
			<td><?php echo $langs->trans('SalesRepresentatives'); ?></td>
			<td align="right">
			<?php if ($user->rights->societe->creer) { ?>
			<a href="<?php echo DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$this->control->tpl['id']; ?>"><?php echo $this->control->tpl['image_edit']; ?></a>
			<?php } else { ?>
			&nbsp;
			<?php } ?>
			</td>
		</tr>
	</table>
	</td>
	<td colspan="3"><?php echo $this->control->tpl['sales_representatives'];	?></td>
</tr>

<?php if ($conf->adherent->enabled) { ?>
<tr>
	<td width="25%" valign="top"><?php echo $langs->trans("LinkedToDolibarrMember"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['linked_member']; ?></td>
</tr>
<?php } ?>

</table>
</form>

</div>

<!-- END PHP TEMPLATE -->