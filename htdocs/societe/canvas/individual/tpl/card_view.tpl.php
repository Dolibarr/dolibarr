<?php
/* Copyright (C) 2010-2011 Regis Houssin <regis.houssin@inodbox.com>
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
 * @var CommonObject $this
 * @var DoliDB $db
 * @var FormFile $formfile
 * @var Translate $langs
 * @var User $user
 *
 * @var string $canvas
 */
// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}


$object = $GLOBALS['objcanvas']->control->object;


print "<!-- BEGIN PHP TEMPLATE CARD_VIEW.TPL.PHP INDIVIDUAL -->\n";

$head = societe_prepare_head($object);

print dol_get_fiche_head($head, 'card', $langs->trans("ThirdParty"), 0, 'company');

if ($this->control->tpl['error']) {
	echo $this->control->tpl['error'];
}
if ($this->control->tpl['action_delete']) {
	echo $this->control->tpl['action_delete'];
} ?>

<table class="border allwidth">

<tr>
	<td width="20%"><?php echo $langs->trans('Name'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['showrefnav']; ?></td>
</tr>

<?php if (getDolGlobalString('SOCIETE_USEPREFIX')) { ?>
<tr>
	<td><?php echo $langs->trans('Prefix'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['prefix_comm']; ?></td>
</tr>
<?php } ?>

<?php if ($this->control->tpl['client']) { ?>
<tr>
	<td><?php echo $langs->trans('CustomerCode'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['code_client']; ?>
	<?php if ($this->control->tpl['checkcustomercode'] != 0) { ?>
	<span class="error">(<?php echo $langs->trans("WrongCustomerCode"); ?>)</span>
	<?php } ?>
	</td>
</tr>
<?php } ?>

<?php if ($this->control->tpl['fournisseur']) { ?>
<tr>
	<td><?php echo $langs->trans('SupplierCode'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['code_fournisseur']; ?>
	<?php if ($this->control->tpl['checksuppliercode'] != 0) { ?>
	<span class="error">(<?php echo $langs->trans("WrongSupplierCode"); ?>)</span>
	<?php } ?>
	</td>
</tr>
<?php } ?>

<?php if (isModEnabled('barcode')) { ?>
<tr>
	<td><?php echo $langs->trans('Gencod'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['barcode']; ?></td>
</tr>
<?php } ?>

<tr>
	<td class="tdtop"><?php echo $langs->trans('Address'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['address']; ?></td>
</tr>

<tr>
	<td width="25%"><?php echo $langs->trans('Zip'); ?></td>
	<td width="25%"><?php echo $this->control->tpl['zip']; ?></td>
	<td width="25%"><?php echo $langs->trans('Town'); ?></td>
	<td width="25%"><?php echo $this->control->tpl['town']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("Country"); ?></td>
	<td colspan="3" class="nowrap"><?php echo $this->control->tpl['country']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('State'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['departement']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('Phone'); ?></td>
	<td><?php echo $this->control->tpl['phone']; ?></td>
	<td><?php echo $langs->trans('PhoneMobile'); ?></td>
	<td><?php echo $this->control->tpl['phone_mobile']; ?></td>
	<td><?php echo $langs->trans('Fax'); ?></td>
	<td><?php echo $this->control->tpl['fax']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('EMail'); ?></td>
	<td><?php echo $this->control->tpl['email']; ?></td>
	<td><?php echo $langs->trans('Web'); ?></td>
	<td><?php echo $this->control->tpl['url']; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('VATIsUsed'); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['tva_assuj']; ?></td>
</tr>

<?php if (!empty($this->control->tpl['localtax'])) {
	echo $this->control->tpl['localtax'];
} ?>

<tr>
	<td><?php echo $langs->trans("Type"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['typent']; ?></td>
</tr>

<?php if (getDolGlobalInt('MAIN_MULTILANGS')) { ?>
<tr>
	<td><?php echo $langs->trans("DefaultLang"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['default_lang']; ?></td>
</tr>
<?php } ?>

<tr>
	<td>
	<table class="nobordernopadding allwidth">
		<tr>
			<td><?php echo $langs->trans('RIB'); ?></td>
			<td class="right">
			<?php if ($user->hasRight('societe', 'creer')) { ?>
			<a href="<?php echo DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.$this->control->tpl['id']; ?>"><?php echo $this->control->tpl['image_edit']; ?></a>
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
	<table class="nobordernopadding allwidth">
		<tr>
			<td><?php echo $langs->trans('SalesRepresentatives'); ?></td>
			<td class="right">
			<?php if ($user->hasRight('societe', 'creer')) { ?>
			<a href="<?php echo DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$this->control->tpl['id']; ?>"><?php echo $this->control->tpl['image_edit']; ?></a>
			<?php } else { ?>
			&nbsp;
			<?php } ?>
			</td>
		</tr>
	</table>
	</td>
	<td colspan="3"><?php echo $this->control->tpl['sales_representatives']; ?></td>
</tr>

<?php if (isModEnabled('member')) { ?>
<tr>
	<td width="25%" valign="top"><?php echo $langs->trans("LinkedToDolibarrMember"); ?></td>
	<td colspan="3"><?php echo $this->control->tpl['linked_member']; ?></td>
</tr>
<?php } ?>

</table>

<?php print dol_get_fiche_end(); ?>

<div class="tabsAction">
<?php if ($user->hasRight('societe', 'creer')) { ?>
<a class="butAction" href="<?php echo $_SERVER["PHP_SELF"].'?socid='.$this->control->tpl['id'].'&action=edit&token='.newToken().'&canvas='.urlencode($canvas); ?>"><?php echo $langs->trans("Modify"); ?></a>
<?php } ?>

<?php if ($user->hasRight('societe', 'supprimer')) { ?>
	<?php if ($conf->use_javascript_ajax) { ?>
		<span id="action-delete" class="butActionDelete"><?php echo $langs->trans('Delete'); ?></span>
	<?php } else { ?>
		<a class="butActionDelete" href="<?php echo $_SERVER["PHP_SELF"].'?socid='.$this->control->tpl['id'].'&action=delete&token='.newToken().'&canvas='.urlencode($canvas); ?>"><?php echo $langs->trans('Delete'); ?></a>
	<?php } ?>
<?php } ?>
</div>

<br>

<table class="allwidth"><tr><td valign="top" width="50%">
<div id="builddoc"></div>
<?php
/*
 * Generated documents
 */
$filedir = $conf->societe->multidir_output[$this->control->tpl['entity']].'/'.$socid;
$urlsource = $_SERVER["PHP_SELF"]."?socid=".$socid;
$genallowed = $user->hasRight('societe', 'lire');
$delallowed = $user->hasRight('societe', 'creer');

print $formfile->showdocuments('company', $socid, $filedir, $urlsource, $genallowed, $delallowed, '', 0, 0, 0, 28, 0, '', 0, '', $objcanvas->control->object->default_lang);
?>

</td>
<td></td>
</tr>
</table>

<br>

<?php
// Subsidiaries list
$result = show_subsidiaries($conf, $langs, $db, $object);

// Contacts list
$result = show_contacts($conf, $langs, $db, $object);

// Projects list
$result = show_projects($conf, $langs, $db, $object);

print "<!-- END PHP TEMPLATE -->\n";
