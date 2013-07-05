<?php
/* Copyright (C) 2012 Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2013 Laurent Destailleur <eldy@users.sourceforge.net>
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

if (! class_exists('Contact')) {
	require DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
}
if (! class_exists('FormCompany')) {
	require DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
}

$module = $object->element;

// Special cases
if ($module == 'propal')				{ $permission=$user->rights->propale->creer; }
elseif ($module == 'fichinter')			{ $permission=$user->rights->ficheinter->creer; }
elseif ($module == 'invoice_supplier')	{ $permission=$user->rights->fournisseur->facture->creer; }
elseif ($module == 'order_supplier')	{ $permission=$user->rights->fournisseur->commande->creer; }
elseif (! isset($permission))			{ $permission=$user->rights->$module->creer; } // If already defined by caller page

$formcompany= new FormCompany($db);
$companystatic=new Societe($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);

?>

<!-- BEGIN PHP TEMPLATE CONTACTS -->
<div class="tagtable centpercent noborder allwidth">

<?php if ($permission) { ?>
	<form class="liste_titre">
		<div><?php echo $langs->trans("Source"); ?></div>
		<div><?php echo $langs->trans("Company"); ?></div>
		<div><?php echo $langs->trans("Contacts"); ?></div>
		<div><?php echo $langs->trans("ContactType"); ?></div>
		<div>&nbsp;</div>
		<div>&nbsp;</div>
	</form>

	<?php $var=false; ?>


	<form <?php echo $bc[$var]; ?> action="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id; ?>" method="POST">
	<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
	<input type="hidden" name="id" value="<?php echo $object->id; ?>" />
	<input type="hidden" name="action" value="addcontact" />
	<input type="hidden" name="source" value="internal" />
		<div class="nowrap"><?php echo img_object('','user').' '.$langs->trans("Users"); ?></div>
		<div><?php echo $conf->global->MAIN_INFO_SOCIETE_NOM; ?></div>
		<div><?php echo $form->select_dolusers($user->id, 'userid', 0, (! empty($userAlreadySelected)?$userAlreadySelected:null), 0, null, null, 0, 56); ?></div>
		<div><?php echo $formcompany->selectTypeContact($object, '', 'type','internal'); ?></div>
		<div>&nbsp;</div>
		<div align="right"><input type="submit" class="button" value="<?php echo $langs->trans("Add"); ?>"></div>
	</form>

	<?php $var=!$var; ?>

	<form <?php echo $bc[$var]; ?> action="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id; ?>" method="POST">
	<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
	<input type="hidden" name="id" value="<?php echo $object->id; ?>" />
	<input type="hidden" name="action" value="addcontact" />
	<input type="hidden" name="source" value="external" />
		<div class="nowrap"><?php echo img_object('','contact').' '.$langs->trans("ThirdPartyContacts"); ?></div>
		<?php if ($conf->use_javascript_ajax && ! empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT)) { ?>
		<div class="nowrap">
			<?php
			$events=array();
			$events[]=array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php',1), 'htmlname' => 'contactid', 'params' => array('add-customer-contact' => 'disabled'));
			print $form->select_company($object->socid,'socid','',1,0,0,$events);
			?>
		</div>
		<div>
			<?php $nbofcontacts=$form->select_contacts($object->socid, '', 'contactid'); ?>
		</div>
		<?php } else { ?>
		<div>
			<?php $selectedCompany = isset($_GET["newcompany"])?$_GET["newcompany"]:$object->socid; ?>
			<?php $selectedCompany = $formcompany->selectCompaniesForNewContact($object, 'id', $selectedCompany, 'newcompany'); ?>
		</div>
		<div>
			<?php $nbofcontacts=$form->select_contacts($selectedCompany, '', 'contactid'); ?>
		</div>
		<?php } ?>
		<div>
			<?php $formcompany->selectTypeContact($object, '', 'type','external'); ?>
		</div>
		<div>&nbsp;</div>
		<div align="right">
			<input type="submit" id="add-customer-contact" class="button" value="<?php echo $langs->trans("Add"); ?>"<?php if (! $nbofcontacts) echo ' disabled="disabled"'; ?>>
		</div>
	</form>

<?php } ?>

	<form class="liste_titre">
		<div><?php echo $langs->trans("Source"); ?></div>
		<div><?php echo $langs->trans("Company"); ?></div>
		<div><?php echo $langs->trans("Contacts"); ?></div>
		<div><?php echo $langs->trans("ContactType"); ?></div>
		<div align="center"><?php echo $langs->trans("Status"); ?></div>
		<div>&nbsp;</div>
	</form>

	<?php $var=true; ?>

	<?php
	foreach(array('internal','external') as $source) {
		$tab = $object->liste_contact(-1,$source);
		$num=count($tab);

		$i = 0;
		while ($i < $num) {
			$var = !$var;
	?>

	<form <?php echo $bc[$var]; ?>>
		<div align="left">
			<?php if ($tab[$i]['source']=='internal') echo $langs->trans("User"); ?>
			<?php if ($tab[$i]['source']=='external') echo $langs->trans("ThirdPartyContact"); ?>
		</div>
		<div align="left">
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
		</div>
		<div>
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
		</div>
		<div><?php echo $tab[$i]['libelle']; ?></div>
		<div align="center">
			<?php if ($object->statut >= 0) echo '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'">'; ?>
			<?php echo $contactstatic->LibStatut($tab[$i]['status'],3); ?>
			<?php if ($object->statut >= 0) echo '</a>'; ?>
		</div>
		<div align="center" class="nowrap">
			<?php if ($permission) { ?>
				&nbsp;<a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=deletecontact&amp;lineid='.$tab[$i]['rowid']; ?>"><?php echo img_delete(); ?></a>
			<?php } ?>
		</div>
	</form>

<?php $i++; ?>
<?php } } ?>

</div>
<!-- END PHP TEMPLATE CONTACTS -->
