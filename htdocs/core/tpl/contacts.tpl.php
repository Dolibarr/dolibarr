<?php
/* Copyright (C) 2012 Regis Houssin <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

$module = $object->element;
$permission=(isset($permission)?$permission:$user->rights->$module->creer);    // If already defined by caller page

// Special cases
if ($module == 'propal')				{ $permission=$user->rights->propale->creer; }
elseif ($module == 'fichinter')			{ $permission=$user->rights->ficheinter->creer; }
elseif ($module == 'invoice_supplier')	{ $permission=$user->rights->fournisseur->facture->creer; }
elseif ($module == 'order_supplier')	{ $permission=$user->rights->fournisseur->commande->creer; }

$companystatic=new Societe($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);

?>

<!-- BEGIN PHP TEMPLATE CONTACTS -->
<table class="noborder allwidth">

<?php if ($permission) { ?>
	<tr class="liste_titre">
		<td><?php echo $langs->trans("Source"); ?></td>
		<td><?php echo $langs->trans("Company"); ?></td>
		<td><?php echo $langs->trans("Contacts"); ?></td>
		<td><?php echo $langs->trans("ContactType"); ?></td>
		<td colspan="3">&nbsp;</td>
	</tr>

	<?php $var=false; ?>

	<form action="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id; ?>" method="POST">
	<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
	<input type="hidden" name="id" value="<?php echo $object->id; ?>" />
	<input type="hidden" name="action" value="addcontact" />
	<input type="hidden" name="source" value="internal" />

	<tr <?php echo $bc[$var]; ?>>
		<td nowrap="nowrap"><?php echo img_object('','user').' '.$langs->trans("Users"); ?></td>
		<td><?php echo $conf->global->MAIN_INFO_SOCIETE_NOM; ?></td>
		<td><?php echo $form->select_users($user->id,'userid',0,$userAlreadySelected); ?></td>
		<td><?php echo $formcompany->selectTypeContact($object, '', 'type','internal'); ?></td>
		<td align="right" colspan="3" ><input type="submit" class="button" value="<?php echo $langs->trans("Add"); ?>"></td>
	</tr>
	</form>

	<form action="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id; ?>" method="POST">
	<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
	<input type="hidden" name="id" value="<?php echo $object->id; ?>" />
	<input type="hidden" name="action" value="addcontact" />
	<input type="hidden" name="source" value="internal" />

	<?php $var=!$var; ?>

	<tr <?php echo $bc[$var]; ?>>
		<td nowrap="nowrap"><?php echo img_object('','contact').' '.$langs->trans("ThirdPartyContacts"); ?></td>
		<?php if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT) { ?>
		<td>
			<?php
			$events=array();
			$events[]=array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php',1), 'htmlname' => 'contactid', 'params' => array('add-customer-contact' => 'disabled'));
			print $form->select_company($object->socid,'socid','',1,0,0,$events);
			?>
		</td>
		<td>
			<?php $nbofcontacts=$form->select_contacts($object->socid, '', 'contactid'); ?>
		</td>
		<?php } else { ?>
		<td>
			<?php $selectedCompany = isset($_GET["newcompany"])?$_GET["newcompany"]:$object->socid; ?>
			<?php $selectedCompany = $formcompany->selectCompaniesForNewContact($object, 'id', $selectedCompany, 'newcompany'); ?>
		</td>
		<td>
			<?php $nbofcontacts=$form->select_contacts($selectedCompany, '', 'contactid'); ?>
		</td>
		<?php } ?>
		<td>
			<?php $formcompany->selectTypeContact($object, '', 'type','external'); ?>
		</td>
		<td align="right" colspan="3" >
			<input type="submit" id="add-customer-contact" class="button" value="<?php echo $langs->trans("Add"); ?>"<?php if (! $nbofcontacts) echo ' disabled="disabled"'; ?>>
		</td>
	</tr>
	</form>

<?php } ?>

	<tr class="liste_titre">
		<td><?php echo $langs->trans("Source"); ?></td>
		<td><?php echo $langs->trans("Company"); ?></td>
		<td><?php echo $langs->trans("Contacts"); ?></td>
		<td><?php echo $langs->trans("ContactType"); ?></td>
		<td align="center"><?php echo $langs->trans("Status"); ?></td>
		<td colspan="2">&nbsp;</td>
	</tr>

	<?php $var=true; ?>

	<?php
	foreach(array('internal','external') as $source) {
		$tab = $object->liste_contact(-1,$source);
		$num=count($tab);

		$i = 0;
		while ($i < $num) {
			$var = !$var;
	?>

	<tr <?php echo $bc[$var]; ?> valign="top">
		<td align="left">
			<?php if ($tab[$i]['source']=='internal') echo $langs->trans("User"); ?>
			<?php if ($tab[$i]['source']=='external') echo $langs->trans("ThirdPartyContact"); ?>
		</td>
		<td align="left">
			<?php
			if ($tab[$i]['socid'] > 0)
			{
				$companystatic->fetch($tab[$i]['socid']);
				echo $companystatic->getNomUrl(1);
			}
			if ($tab[$i]['socid'] < 0)
			{
				echo $conf->global->MAIN_INFO_SOCIETE_NOM;
			}
			if (! $tab[$i]['socid'])
			{
				echo '&nbsp;';
			}
			?>
		</td>
		<td>
			<?php
			if ($tab[$i]['source']=='internal')
			{
				$userstatic->id=$tab[$i]['id'];
				$userstatic->lastname=$tab[$i]['lastname'];
				$userstatic->firstname=$tab[$i]['firstname'];
				echo $userstatic->getNomUrl(1);
			}
			if ($tab[$i]['source']=='external')
			{
				$contactstatic->id=$tab[$i]['id'];
				$contactstatic->lastname=$tab[$i]['lastname'];
				$contactstatic->firstname=$tab[$i]['firstname'];
				echo $contactstatic->getNomUrl(1);
			}
			?>
		</td>
		<td><?php echo $tab[$i]['libelle']; ?></td>
		<td align="center">
			<?php if ($object->statut >= 0) echo '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'">'; ?>
			<?php echo $contactstatic->LibStatut($tab[$i]['status'],3); ?>
			<?php if ($object->statut >= 0) echo '</a>'; ?>
		</td>
		<td align="center" nowrap="nowrap" colspan="2">
			<?php if ($permission) { ?>
				&nbsp;<a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=deletecontact&amp;lineid='.$tab[$i]['rowid']; ?>"><?php echo img_delete(); ?></a>
			<?php } ?>
		</td>
	</tr>

<?php $i++; ?>
<?php } } ?>

</table>
<!-- END PHP TEMPLATE CONTACTS -->