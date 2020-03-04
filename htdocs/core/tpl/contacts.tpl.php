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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * This template needs:
 * $object
 * $withproject (if we are on task contact)
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$module = $object->element;

// Special cases
if ($module == 'propal') { $permission = $user->rights->propale->creer; }
elseif ($module == 'fichinter') { $permission = $user->rights->ficheinter->creer; }
elseif ($module == 'order_supplier') { $permission = $user->rights->fournisseur->commande->creer; }
elseif ($module == 'invoice_supplier') { $permission = $user->rights->fournisseur->facture->creer; }
elseif ($module == 'project') { $permission = $user->rights->projet->creer; }
elseif ($module == 'action') { $permission = $user->rights->agenda->myactions->create; }
elseif ($module == 'shipping') { $permission = $user->rights->expedition->creer; }
elseif ($module == 'reception') { $permission = $user->rights->reception->creer; }
elseif ($module == 'project_task') { $permission = $user->rights->projet->creer; }
elseif (!isset($permission) && isset($user->rights->$module->creer))
{
	$permission = $user->rights->$module->creer;
}
elseif (!isset($permission) && isset($user->rights->$module->write))
{
	$permission = $user->rights->$module->write;
}

$formcompany = new FormCompany($db);
$companystatic = new Societe($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);

?>

<!-- BEGIN PHP TEMPLATE CONTACTS -->
<div class="underbanner clearboth"></div>
<div class="div-table-responsive">
<div class="tagtable tableforcontact centpercent noborder nobordertop allwidth">

<?php
if ($permission)
{
	?>
	<form class="tagtr liste_titre">
		<div class="tagtd liste_titre"><?php echo $langs->trans("NatureOfContact"); ?></div>
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
		<div class="nowrap tagtd"><?php echo img_object('', 'user').' '.$langs->trans("Users"); ?></div>
		<div class="tagtd"><?php echo $conf->global->MAIN_INFO_SOCIETE_NOM; ?></div>
		<div class="tagtd maxwidthonsmartphone"><?php echo $form->select_dolusers($user->id, 'userid', 0, (!empty($userAlreadySelected) ? $userAlreadySelected : null), 0, null, null, 0, 56, '', 0, '', 'minwidth200imp'); ?></div>
		<div class="tagtd maxwidthonsmartphone">
		<?php
		$tmpobject = $object;
		if (($object->element == 'shipping' || $object->element == 'reception') && is_object($objectsrc)) $tmpobject = $objectsrc;
		echo $formcompany->selectTypeContact($tmpobject, '', 'type', 'internal');
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
		<div class="tagtd nowrap noborderbottom"><?php echo img_object('', 'contact').' '.$langs->trans("ThirdPartyContacts"); ?></div>
		<div class="tagtd nowrap maxwidthonsmartphone noborderbottom">
			<?php $selectedCompany = isset($_GET["newcompany"]) ? $_GET["newcompany"] : $object->socid; ?>
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
			<?php
			$nbofcontacts=$form->select_contacts(($selectedCompany > 0 ? $selectedCompany : -1), '', 'contactid', 3, '', '', 1, 'minwidth100imp');

			$newcardbutton = '';
			if (! empty($object->socid) && $object->socid > 1 && $user->rights->societe->creer)
			{
				$newcardbutton .= '<a href="'.DOL_URL_ROOT.'/contact/card.php?socid='.$object->socid.'&action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id).'" title="'.$langs->trans('NewContact').'"><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
			}
			print $newcardbutton;
			?>
		</div>
		<div class="tagtd maxwidthonsmartphone noborderbottom">
			<?php
			$tmpobject=$object;
			if (($object->element == 'shipping'|| $object->element == 'reception') && is_object($objectsrc)) $tmpobject=$objectsrc;
			$formcompany->selectTypeContact($tmpobject, '', 'type', 'external', 'position', 0, 'minwidth100imp'); ?>
		</div>
		<div class="tagtd noborderbottom">&nbsp;</div>
		<div class="tagtd center noborderbottom">
			<input type="submit" id="add-customer-contact" class="button" value="<?php echo $langs->trans("Add"); ?>"<?php if (! $nbofcontacts) echo ' disabled'; ?>>
		</div>
	</form>

        <?php
	}
}

print "</div>";

/**
* Prepare list
*/

// TODO: replace this with direct SQL string to use $db->sort($sortfield, $sortorder)
$list = array();
foreach(array('internal', 'external') as $source)
{
	$tmpobject = $object;

	if (($object->element == 'shipping'|| $object->element == 'reception') && is_object($objectsrc))
	{
		$tmpobject = $objectsrc;
	}

	$tab = $tmpobject->liste_contact(-1, $source);
	$num = count($tab);

	$i = 0;
	while ($i < $num)
	{
		$entry = '';
		$entry->id   = $tab[$i]['rowid'];
		$entry->type = $tab[$i]['libelle'];

		if ($tab[$i]['source'] == 'internal')
		{
			$entry->nature = $langs->trans("User");
		}
		elseif ($tab[$i]['source'] == 'external')
		{
			$entry->nature = $langs->trans("ThirdPartyContact");
		}

		if ($tab[$i]['socid'] > 0)
		{
			$companystatic->fetch($tab[$i]['socid']);
			$entry->thirdparty = $companystatic->getNomUrl(1);
		}
		elseif ($tab[$i]['socid'] < 0)
		{
			$entry->thirdparty = $conf->global->MAIN_INFO_SOCIETE_NOM;
		}
		elseif (! $tab[$i]['socid'])
		{
			$entry->thirdparty = "";
		}

		if ($tab[$i]['source']=='internal')
		{
			$userstatic->fetch($tab[$i]['id']);
			$entry->contact = $userstatic->getNomUrl(-1, '', 0, 0, 0, 0, '', 'valignmiddle');
		}
		elseif ($tab[$i]['source']=='external')
		{
			$contactstatic->fetch($tab[$i]['id']);
			$entry->contact =$contactstatic->getNomUrl(1, '', 0, '', 0, 0);
		}

		if ($tab[$i]['source']=='internal')
		{
			$entry->status = $userstatic->LibStatut($tab[$i]['statuscontact'], 3);
		}
		elseif ($tab[$i]['source']=='external')
		{
			$entry->status = $contactstatic->LibStatut($tab[$i]['statuscontact'], 3);
		}

		$i++;
		$list[] = $entry;
    }
}


$sortfield = GETPOST("sortfield", "alpha");
$sortorder = GETPOST("sortorder", 'alpha');

if (!$sortfield) $sortfield = "nature";
if (!$sortorder) $sortorder = "asc";

/**
 * Re-sort list
 */

// TODO: switch to $db->sort($sortfield, $sortorder);
if($sortorder == "asc")
{
	if($sortfield == "nature")		usort($list, "nature_asc");
	if($sortfield == "thirdparty")	usort($list, "thirdparty_asc");
	if($sortfield == "contact")		usort($list, "contact_asc");
	if($sortfield == "type")		usort($list, "type_asc");
	if($sortfield == "status")		usort($list, "status_asc");
}
else
{
	if($sortfield == "nature")		usort($list, "nature_desc");
	if($sortfield == "thirdparty")	usort($list, "thirdparty_desc");
	if($sortfield == "contact")		usort($list, "contact_desc");
	if($sortfield == "type")		usort($list, "type_desc");
	if($sortfield == "status")		usort($list, "status_desc");
}

$arrayfields = array(
	'rowid' 		=> array('label'=>$langs->trans("Id"), 'checked'=>1),
	'nature' 		=> array('label'=>$langs->trans("NatureOfContact"), 'checked'=>1),
	'thirdparty' 	=> array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
	'contact' 		=> array('label'=>$langs->trans("Users").'/'.$langs->trans("Contacts"), 'checked'=>1),
	'type' 			=> array('label'=>$langs->trans("ContactType"), 'checked'=>1),
	'status' 		=> array('label'=>$langs->trans("Status"), 'checked'=>1),
	'link' 			=> array('label'=>$langs->trans("Link"), 'checked'=>1),
);

$param = 'id='.$object->id.'&mainmenu=home';

/**
 * Show list
 */

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

print '<table class="tagtable nobottomiftotal liste">';

print '<tr class="liste_titre_filter">';
print '</tr>';

print '<tr class="liste_titre">';
print_liste_field_titre($arrayfields['nature']['label'], $_SERVER["PHP_SELF"], "nature", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre($arrayfields['thirdparty']['label'], $_SERVER["PHP_SELF"], "thirdparty", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre($arrayfields['contact']['label'], $_SERVER["PHP_SELF"], "contact", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre($arrayfields['type']['label'], $_SERVER["PHP_SELF"], "type", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre($arrayfields['status']['label'], $_SERVER["PHP_SELF"], "statut", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre($arrayfields['link']['label'], $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder, 'center maxwidthsearch ');
print "</tr>";

foreach($list as $entry)
{
	print '<tr class="oddeven">';

	print '<td class="nowrap">'.$entry->nature.'</td>';
	print '<td class="tdoverflowmax200">'.$entry->thirdparty.'</td>';
	print '<td class="tdoverflowmax200">'.$entry->contact.'</td>';
	print '<td class="tdoverflowmax200">'.$entry->type.'</td>';
	print '<td class="tdoverflowmax200">'.$entry->status.'</td>';

	if ($permission)
	{
		$href = $_SERVER["PHP_SELF"];
		$href .= "?id=".$object->id;
		$href .= "&action=deletecontact";
		$href .= "&lineid=".$entry->id;

		print "<td class='center'>";
		print "<a href='$href'>";
		print img_picto($langs->trans("Unlink"), "unlink");
		print "</a>";
		print "</td>";
	}

	print "</tr>";
}

print "</table>";
print "</div>";
print "</form>";
print "</div>";

print "<!-- TEMPLATE CONTACTS HOOK BEGIN HERE -->\n";
if (is_object($hookmanager)) {
    $hookmanager->initHooks(array('contacttpl'));
    $parameters=array();
    $reshook=$hookmanager->executeHooks('formContactTpl', $parameters, $object, $action);
}
print "<!-- END PHP TEMPLATE CONTACTS -->\n";


// TODO: Remove this functions after switch to $db->sort($sortfield, $sortorder);
function nature_asc($left, $right)
{
	return $left->nature > $right->nature; }
function thirdparty_asc($left, $right)
{
	return $left->thirdparty > $right->thirdparty; }
function contact_asc($left, $right)
{
	return $left->contact > $right->contact; }
function type_asc($left, $right)
{
	return $left->type > $right->type; }
function status_asc($left, $right)
{
	return $left->status > $right->status; }

function nature_desc($left, $right)
{
	return $left->nature < $right->nature; }
function thirdparty_desc($left, $right)
{
	return $left->thirdparty < $right->thirdparty; }
function contact_desc($left, $right)
{
	return $left->contact < $right->contact; }
function type_desc($left, $right)
{
	return $left->type < $right->type; }
function status_desc($left, $right)
{
	return $left->status < $right->status; }
