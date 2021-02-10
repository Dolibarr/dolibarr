<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2014 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 *	    \file       htdocs/societe/notify/card.php
 *      \ingroup    societe notification
 *		\brief      Tab for notifications of third party
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/triggers/interface_50_modNotification_Notification.class.php';

$langs->loadLangs(array("companies", "mails", "admin", "other"));

$socid     = GETPOST("socid", 'int');
$action    = GETPOST('action', 'aZ09');
$contactid = GETPOST('contactid'); // May be an int or 'thirdparty'
$actionid  = GETPOST('actionid');
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'societe', '', '');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (!$sortorder) $sortorder = "DESC";
if (!$sortfield) $sortfield = "n.daten";
if (empty($page) || $page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$now = dol_now();

$object = new Societe($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartynotification', 'globalcard'));



/*
 * Actions
 */

$parameters = array('id'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	$error = 0;

	// Add a notification
	if ($action == 'add')
	{
		if (empty($contactid))
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Contact")), null, 'errors');
			$error++;
		}
		if ($actionid <= 0)
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Action")), null, 'errors');
			$error++;
		}

		if (!$error)
		{
			$db->begin();

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def";
			$sql .= " WHERE fk_soc=".$socid." AND fk_contact=".$contactid." AND fk_action=".$actionid;
			if ($db->query($sql))
			{
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."notify_def (datec,fk_soc, fk_contact, fk_action)";
				$sql .= " VALUES ('".$db->idate($now)."',".$socid.",".$contactid.",".$actionid.")";

				if (!$db->query($sql))
				{
					$error++;
					dol_print_error($db);
				}
			} else {
				dol_print_error($db);
			}

			if (!$error)
			{
				$db->commit();
			} else {
				$db->rollback();
			}
		}
	}

	// Remove a notification
	if ($action == 'delete')
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def where rowid=".GETPOST('actid', 'int');
		$db->query($sql);
	}
}



/*
 *	View
 */

$form = new Form($db);

$object = new Societe($db);
$result = $object->fetch($socid);

$title = $langs->trans("ThirdParty").' - '.$langs->trans("Notification");
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) $title = $object->name.' - '.$langs->trans("Notification");
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);


if ($result > 0)
{
	$langs->load("other");

	$head = societe_prepare_head($object);

	print dol_get_fiche_head($head, 'notify', $langs->trans("ThirdParty"), -1, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Prefix
	if (!empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
	{
		print '<tr><td class="titlefield">'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
	}

    if ($object->client) {
        print '<tr><td class="titlefield">';
        print $langs->trans('CustomerCode').'</td><td colspan="3">';
        print $object->code_client;
        $tmpcheck = $object->check_codeclient();
        if ($tmpcheck != 0 && $tmpcheck != -5) {
        	print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
        }
        print '</td></tr>';
    }

    if (!empty($conf->fournisseur->enabled) && $object->fournisseur && !empty($user->rights->fournisseur->lire)) {
        print '<tr><td class="titlefield">';
        print $langs->trans('SupplierCode').'</td><td colspan="3">';
        print $object->code_fournisseur;
        $tmpcheck = $object->check_codefournisseur();
        if ($tmpcheck != 0 && $tmpcheck != -5) {
        	print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
        }
        print '</td></tr>';
    }

	/*print '<tr><td class="titlefield">'.$langs->trans("NbOfActiveNotifications").'</td>';   // Notification for this thirdparty
    print '<td colspan="3">';
    $nbofrecipientemails=0;
    $notify=new Notify($db);
    $tmparray = $notify->getNotificationsArray('', $object->id, null, 0, array('thirdparty'));
    foreach($tmparray as $tmpkey => $tmpval)
    {
        if (! empty($tmpkey)) $nbofrecipientemails++;
    }
    print $nbofrecipientemails;
    print '</td></tr>';*/

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();

	print "\n";

	// Help
	print '<div class="opacitymedium">';
	print $langs->trans("NotificationsDesc");
	print '<br>'.$langs->trans("NotificationsDescUser");
	print '<br>'.$langs->trans("NotificationsDescContact");
	print '<br>'.$langs->trans("NotificationsDescGlobal");
	print '</div>';

	print '<br><br>'."\n";


	// Add notification form
	print load_fiche_titre($langs->trans("AddNewNotification"), '', '');

	print '<form action="'.$_SERVER["PHP_SELF"].'?socid='.$socid.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	$param = "&socid=".$socid;

	// Line with titles
	print '<table width="100%" class="noborder">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("Target", $_SERVER["PHP_SELF"], "c.lastname,c.firstname", '', $param, 'width="45%"', $sortfield, $sortorder);
	print_liste_field_titre("Action", $_SERVER["PHP_SELF"], "", '', $param, 'width="35%"', $sortfield, $sortorder);
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "n.type", '', $param, 'width="10%"', $sortfield, $sortorder);
	print_liste_field_titre('');
	print "</tr>\n";

	$var = false;
	$listofemails = $object->thirdparty_and_contact_email_array();
	if (count($listofemails) > 0)
	{
		$actions = array();

		// Load array of available notifications
		$notificationtrigger = new InterfaceNotification($db);
		$listofmanagedeventfornotification = $notificationtrigger->getListOfManagedEvents();

		foreach ($listofmanagedeventfornotification as $managedeventfornotification)
		{
 			$label = ($langs->trans("Notify_".$managedeventfornotification['code']) != "Notify_".$managedeventfornotification['code'] ? $langs->trans("Notify_".$managedeventfornotification['code']) : $managedeventfornotification['label']);
			$actions[$managedeventfornotification['rowid']] = $label;
		}
		print '<tr class="oddeven nohover"><td class="maxwidthonsmartphone">';
		print img_picto('', 'contact', '', false, 0, 0, '', 'paddingright').$form->selectarray("contactid", $listofemails, '', 0, 0, 0, '', 0, 0, 0, '', 'maxwidthonsmartphone');
		print '</td>';
		print '<td class="maxwidthonsmartphone">';
		print img_picto('', 'object_action', '', false, 0, 0, '', 'paddingright').$form->selectarray("actionid", $actions, '', 1, 0, 0, '', 0, 0, 0, '', 'maxwidthonsmartphone');
		print '</td>';
		print '<td>';
		$type = array('email'=>$langs->trans("EMail"));
		print $form->selectarray("typeid", $type);
		print '</td>';
		print '<td class="right"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
		print '</tr>';
	} else {
		print '<tr class="oddeven"><td colspan="4" class="opacitymedium">';
		print $langs->trans("YouMustCreateContactFirst");
		print '</td></tr>';
	}

	print '</table>';


	print '</form>';
	print '<br>';


	// List of notifications enabled for contacts
	$sql = "SELECT n.rowid, n.type,";
	$sql .= " a.code, a.label,";
	$sql .= " c.rowid as contactid, c.lastname, c.firstname, c.email";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_action_trigger as a,";
	$sql .= " ".MAIN_DB_PREFIX."notify_def as n,";
	$sql .= " ".MAIN_DB_PREFIX."socpeople c";
	$sql .= " WHERE a.rowid = n.fk_action";
	$sql .= " AND c.rowid = n.fk_contact";
	$sql .= " AND c.fk_soc = ".$object->id;

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
	} else {
		dol_print_error($db);
	}

	// List of active notifications
	print load_fiche_titre($langs->trans("ListOfActiveNotifications").' <span class="opacitymedium colorblack paddingleft">('.$num.')</span>', '', '');

	// Line with titles
	print '<table width="100%" class="noborder">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("Target", $_SERVER["PHP_SELF"], "c.lastname,c.firstname", '', $param, 'width="45%"', $sortfield, $sortorder);
	print_liste_field_titre("Action", $_SERVER["PHP_SELF"], "", '', $param, 'width="35%"', $sortfield, $sortorder);
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "n.type", '', $param, 'width="10%"', $sortfield, $sortorder);
	print_liste_field_titre('', '', '');
	print '</tr>';

	$langs->load("errors");
	$langs->load("other");

	if ($num)
	{
		$i = 0;

		$contactstatic = new Contact($db);

		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

			$contactstatic->id = $obj->contact_id;
			$contactstatic->lastname = $obj->lastname;
			$contactstatic->firstname = $obj->firstname;
			print '<tr class="oddeven"><td>'.$contactstatic->getNomUrl(1);
			if ($obj->type == 'email')
			{
				if (isValidEmail($obj->email))
				{
					print ' &lt;'.$obj->email.'&gt;';
				} else {
					$langs->load("errors");
					print ' &nbsp; '.img_warning().' '.$langs->trans("ErrorBadEMail", $obj->email);
				}
			}
			print '</td>';
			print '<td>';
			$label = ($langs->trans("Notify_".$obj->code) != "Notify_".$obj->code ? $langs->trans("Notify_".$obj->code) : $obj->label);
			print img_picto('', 'object_action', '', false, 0, 0, '', 'paddingright').$label;
			print '</td>';
			print '<td>';
			if ($obj->type == 'email') print $langs->trans("Email");
			if ($obj->type == 'sms') print $langs->trans("SMS");
			print '</td>';
			print '<td class="right"><a href="card.php?socid='.$socid.'&action=delete&token='.newToken().'&actid='.$obj->rowid.'">'.img_delete().'</a></td>';
			print '</tr>';
			$i++;
		}
		$db->free($resql);
	}

	// List of notifications enabled for fixed email
	/*
    foreach($conf->global as $key => $val)
    {
    	if (! preg_match('/^NOTIFICATION_FIXEDEMAIL_(.*)/', $key, $reg)) continue;
    	$var = ! $var;
		print '<tr class="oddeven"><td>';
		$listtmp=explode(',',$val);
		$first=1;
		foreach($listtmp as $keyemail => $valemail)
		{
			if (! $first) print ', ';
			$first=0;
			$valemail=trim($valemail);
    		//print $keyemail.' - '.$valemail.' - '.$reg[1].'<br>';
			if (isValidEmail($valemail, 1))
			{
				if ($valemail == '__SUPERVISOREMAIL__') print $valemail;
				else print ' &lt;'.$valemail.'&gt;';
			}
			else
			{
				print ' '.img_warning().' '.$langs->trans("ErrorBadEMail",$valemail);
			}
		}
		print '</td>';
		print '<td>';
		$notifcode=preg_replace('/_THRESHOLD_.*$/','',$reg[1]);
		$notifcodecond=preg_replace('/^.*_(THRESHOLD_)/','$1',$reg[1]);
		$label=($langs->trans("Notify_".$notifcode)!="Notify_".$notifcode?$langs->trans("Notify_".$notifcode):$notifcode);
		print $label;
		if (preg_match('/^THRESHOLD_HIGHER_(.*)$/',$notifcodecond,$regcond) && ($regcond[1] > 0))
		{
			print ' - '.$langs->trans("IfAmountHigherThan",$regcond[1]);
		}
		print '</td>';
		print '<td>';
		print $langs->trans("Email");
		print '</td>';
		print '<td class="right">'.$langs->trans("SeeModuleSetup", $langs->transnoentitiesnoconv("Module600Name")).'</td>';
		print '</tr>';
    }*/

	/*if ($user->admin)
    {
	    $var = ! $var;
		print '<tr class="oddeven"><td colspan="4">';
		print '+ <a href="'.DOL_URL_ROOT.'/admin/notification.php">'.$langs->trans("SeeModuleSetup", $langs->transnoentitiesnoconv("Module600Name")).'</a>';
		print '</td></tr>';
    }*/

	print '</table>';


	print '<br><br>'."\n";


	// List
	$sql = "SELECT n.rowid, n.daten, n.email, n.objet_type as object_type, n.objet_id as object_id, n.type,";
	$sql .= " c.rowid as id, c.lastname, c.firstname, c.email as contactemail,";
	$sql .= " a.code, a.label";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_action_trigger as a,";
	$sql .= " ".MAIN_DB_PREFIX."notify as n ";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as c ON n.fk_contact = c.rowid";
	$sql .= " WHERE a.rowid = n.fk_action";
	$sql .= " AND n.fk_soc = ".$object->id;
	$sql .= $db->order($sortfield, $sortorder);

	// Count total nb of records
	$nbtotalofrecords = '';
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
	{
		$result = $db->query($sql);
		$nbtotalofrecords = $db->num_rows($result);
		if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
		{
			$page = 0;
			$offset = 0;
		}
	}

	$sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
	} else {
		dol_print_error($db);
	}

	$param = '&socid='.$object->id;
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.$limit;

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="socid" value="'.$object->id.'">';

	// List of active notifications
	print_barre_liste($langs->trans("ListOfNotificationsDone"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, '', 0, '', '', $limit);

	// Line with titles
	print '<table width="100%" class="noborder">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("Target", $_SERVER["PHP_SELF"], "c.lastname,c.firstname", '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Action", $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "n.type", '', $param, '', $sortfield, $sortorder);
	//print_liste_field_titre("Object",$_SERVER["PHP_SELF"],"",'',$param,'"',$sortfield,$sortorder);
	print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "n.daten", '', $param, '', $sortfield, $sortorder, 'right ');
	print '</tr>';

	if ($num > 0)
	{
		$i = 0;

		$contactstatic = new Contact($db);

		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven"><td>';
			if ($obj->id > 0)
			{
				$contactstatic->id = $obj->id;
				$contactstatic->lastname = $obj->lastname;
				$contactstatic->firstname = $obj->firstname;
				print $contactstatic->getNomUrl(1);
				print $obj->email ? ' &lt;'.$obj->email.'&gt;' : $langs->trans("NoMail");
			} else {
				print $obj->email;
			}
			print '</td>';
			print '<td>';
			$label = ($langs->trans("Notify_".$obj->code) != "Notify_".$obj->code ? $langs->trans("Notify_".$obj->code) : $obj->label);
			print $label;
			print '</td>';
			print '<td>';
			if ($obj->type == 'email') print $langs->trans("Email");
			if ($obj->type == 'sms') print $langs->trans("Sms");
			print '</td>';
			// TODO Add link to object here for other types
			/*print '<td>';
            if ($obj->object_type == 'order')
            {
				$orderstatic->id=$obj->object_id;
				$orderstatic->ref=...
				print $orderstatic->getNomUrl(1);
            }
           	print '</td>';*/
			// print
			print'<td class="right">'.dol_print_date($db->jdate($obj->daten), 'dayhour').'</td>';
			print '</tr>';
			$i++;
		}
		$db->free($resql);
	} else {
		print '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}

	print '</table>';

	print '</form>';
} else dol_print_error('', 'RecordNotFound');

// End of page
llxFooter();
$db->close();
