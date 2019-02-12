<?php
/* Copyright (C) 2012      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015-2016 Charlie BENKE 	<charlie@patas-monkey.com>
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
 *
 * This template needs:
 * $object
 * $withproject (if we are on task contact)
 */

// Protection to avoid direct call of template
if (empty($object) || ! is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}


require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$module = $object->element;

// Special cases
if ($module == 'propal')				{ $permission=$user->rights->propale->creer; }
elseif ($module == 'fichinter')			{ $permission=$user->rights->ficheinter->creer; }
elseif ($module == 'order_supplier')	{ $permission=$user->rights->fournisseur->commande->creer; }
elseif ($module == 'invoice_supplier')	{ $permission=$user->rights->fournisseur->facture->creer; }
elseif ($module == 'project')		{ $permission=$user->rights->projet->creer; }
elseif ($module == 'action')		{ $permission=$user->rights->agenda->myactions->create; }
elseif ($module == 'shipping')		{ $permission=$user->rights->expedition->creer; }
elseif ($module == 'project_task')	{ $permission=$user->rights->projet->creer; }
elseif (! isset($permission) && isset($user->rights->$module->creer))
{
	$permission=$user->rights->$module->creer;
}
elseif (! isset($permission)  && isset($user->rights->$module->write))
{
	$permission=$user->rights->$module->write;
}

$formcompany= new FormCompany($db);
$companystatic=new Societe($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);

?>

<!-- BEGIN PHP TEMPLATE CONTACTS -->
<div class="underbanner clearboth"></div>
<div class="div-table-responsive">
<div class="tagtable centpercent noborder nobordertop allwidth">

<?php
if ($permission) {
?>
	<form class="tagtr liste_titre">
		<div class="tagtd liste_titre"><?php echo $langs->trans("Nature"); ?></div>
		<div class="tagtd liste_titre"><?php echo $langs->trans("ThirdParty"); ?></div>
		<div class="tagtd liste_titre"><?php echo $langs->trans("Users").'/'.$langs->trans("Contacts"); ?></div>
		<div class="tagtd liste_titre"><?php echo $langs->trans("ContactType"); ?></div>
		<div class="tagtd liste_titre">&nbsp;</div>
		<div class="tagtd liste_titre">&nbsp;</div>
	</form>

	<?php

	if (empty($hideaddcontactforuser))
	{

	?>
	<form class="tagtr impair" action="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id; ?>" method="POST">
	<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
	<input type="hidden" name="id" value="<?php echo $object->id; ?>" />
	<input type="hidden" name="action" value="addcontact" />
	<input type="hidden" name="source" value="internal" />
	<?php if ($withproject) print '<input type="hidden" name="withproject" value="'.$withproject.'">'; ?>
		<div class="nowrap tagtd"><?php echo img_object('','user').' '.$langs->trans("Users"); ?></div>
		<div class="tagtd"><?php echo $conf->global->MAIN_INFO_SOCIETE_NOM; ?></div>
		<div class="tagtd maxwidthonsmartphone"><?php echo $form->select_dolusers($user->id, 'userid', 0, (! empty($userAlreadySelected)?$userAlreadySelected:null), 0, null, null, 0, 56, '', 0, '', 'minwidth200imp'); ?></div>
		<div class="tagtd maxwidthonsmartphone">
		<?php
		$tmpobject=$object;
		if ($object->element == 'shipping' && is_object($objectsrc)) $tmpobject=$objectsrc;
		echo $formcompany->selectTypeContact($tmpobject, '', 'type','internal');
		?></div>
		<div class="tagtd">&nbsp;</div>
		<div class="tagtd center"><input type="submit" class="button" value="<?php echo $langs->trans("Add"); ?>"></div>
	</form>

	<?php
	}

	if (empty($hideaddcontactforthirdparty))
	{

	?>

	<form class="tagtr pair" action="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id; ?>" method="POST">
	<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
	<input type="hidden" name="id" value="<?php echo $object->id; ?>" />
	<input type="hidden" name="action" value="addcontact" />
	<input type="hidden" name="source" value="external" />
	<?php if ($withproject) print '<input type="hidden" name="withproject" value="'.$withproject.'">'; ?>
		<div class="tagtd nowrap noborderbottom"><?php echo img_object('','contact').' '.$langs->trans("ThirdPartyContacts"); ?></div>
		<div class="tagtd nowrap maxwidthonsmartphone noborderbottom">
			<?php $selectedCompany = isset($_GET["newcompany"])?$_GET["newcompany"]:$object->socid; ?>
			<?php
			// add company icon before select list
			if ($selectedCompany)
			{
			    echo img_object('', 'company', 'class="hideonsmartphone"');
			}
			?>
			<?php $selectedCompany = $formcompany->selectCompaniesForNewContact($object, 'id', $selectedCompany, 'newcompany', '', 0, '', 'minwidth300imp'); ?>
		</div>
		<div class="tagtd maxwidthonsmartphone noborderbottom">
			<?php $nbofcontacts=$form->select_contacts(($selectedCompany > 0 ? $selectedCompany : -1), '', 'contactid', 3, '', '', 0, 'minwidth100imp'); ?>
		</div>
		<div class="tagtd maxwidthonsmartphone noborderbottom">
			<?php
			$tmpobject=$object;
			if ($object->element == 'shipping' && is_object($objectsrc)) $tmpobject=$objectsrc;
			$formcompany->selectTypeContact($tmpobject, '', 'type','external', 'position', 0, 'minwidth100imp'); ?>
		</div>
		<div class="tagtd noborderbottom">&nbsp;</div>
		<div class="tagtd center noborderbottom">
			<input type="submit" id="add-customer-contact" class="button" value="<?php echo $langs->trans("Add"); ?>"<?php if (! $nbofcontacts) echo ' disabled'; ?>>
		</div>
	</form>

<?php
	}
}
?>

	<form class="tagtr liste_titre liste_titre_add formnoborder">
		<div class="tagtd liste_titre"><?php echo $langs->trans("Nature"); ?></div>
		<div class="tagtd liste_titre"><?php echo $langs->trans("ThirdParty"); ?></div>
		<div class="tagtd liste_titre"><?php echo $langs->trans("Users").'/'.$langs->trans("Contacts"); ?></div>
		<div class="tagtd liste_titre"><?php echo $langs->trans("ContactType"); ?></div>
		<div class="tagtd liste_titre center"><?php echo $langs->trans("Status"); ?></div>
		<div class="tagtd liste_titre">&nbsp;</div>
	</form>

	<?php
	$var = false;

	$arrayofsource=array('internal','external');	// Show both link to user and thirdparties contacts
	foreach($arrayofsource as $source) {

		$tmpobject=$object;
		if ($object->element == 'shipping' && is_object($objectsrc)) $tmpobject=$objectsrc;

		$tab = $tmpobject->liste_contact(-1,$source);
		$num=count($tab);

		$i = 0;
		while ($i < $num) {
		    $var = ! $var;
	?>

	<form class="tagtr oddeven <?php echo ($var?'impair':'pair') ?>">
		<div class="tagtd" align="left">
			<?php if ($tab[$i]['source']=='internal') echo $langs->trans("User"); ?>
			<?php if ($tab[$i]['source']=='external') echo $langs->trans("ThirdPartyContact"); ?>
		</div>
		<div class="tagtd" align="left">
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
		<div class="tagtd">
			<?php
			$statusofcontact = $tab[$i]['status'];

			if ($tab[$i]['source']=='internal')
			{
				$userstatic->fetch($tab[$i]['id']);
				echo $userstatic->getNomUrl(-1, '', 0, 0, 0, 0, '', 'valignmiddle');
			}
			if ($tab[$i]['source']=='external')
			{
				$contactstatic->fetch($tab[$i]['id']);
				echo $contactstatic->getNomUrl(1, '', 0, '', 0, 0, '', 'valignmiddle');
			}
			?>
		</div>
		<div class="tagtd"><?php echo $tab[$i]['libelle']; ?></div>
		<div class="tagtd center">
			<?php //if ($object->statut >= 0) echo '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'">'; ?>
			<?php
			if ($tab[$i]['source']=='internal')
			{
				echo $userstatic->LibStatut($tab[$i]['statuscontact'],3);
			}
			if ($tab[$i]['source']=='external')
			{
				echo $contactstatic->LibStatut($tab[$i]['statuscontact'],3);
			}
			?>
			<?php //if ($object->statut >= 0) echo '</a>'; ?>
		</div>
		<div class="tagtd nowrap right">
			<?php if ($permission) { ?>
				&nbsp;<a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=deletecontact&amp;lineid='.$tab[$i]['rowid']; ?>"><?php echo img_picto($langs->trans('Unlink'), 'unlink'); ?></a>
			<?php } ?>
		</div>
	</form>

<?php $i++; ?>
<?php } } ?>

</div>
</div>
<!-- TEMPLATE CONTACTS HOOK BEGIN HERE -->
<?php
	if (is_object($hookmanager))
	{
		$hookmanager->initHooks(array('contacttpl'));
		$parameters=array();
		$reshook=$hookmanager->executeHooks('formContactTpl',$parameters,$object,$action);
	}
?>
<!-- END PHP TEMPLATE CONTACTS -->
