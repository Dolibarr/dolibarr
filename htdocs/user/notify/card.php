<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2014 Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2016      Abbes Bahfir         <contact@dolibarrpar.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	    \file       htdocs/user/notify/card.php
 *      \ingroup    user notification
 *		\brief      Tab for notifications of third party
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/triggers/interface_50_modNotification_Notification.class.php';

// Load translation files required by page
$langs->loadLangs(array('companies', 'mails', 'admin', 'other', 'errors'));

$id = GETPOSTINT("id");
$ref = GETPOST('ref', 'alpha');

if (!isset($id) || empty($id)) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$actionid = GETPOSTINT('actionid');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "n.daten";
}
if (empty($page) || $page == -1) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$now = dol_now();

// Security check
$object = new User($db);
if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref, '', 1);
	$object->getrights();
}

$permissiontoadd = (($object->id == $user->id) || ($user->hasRight('user', 'user', 'lire')));

// Security check
if ($user->socid) {
	$id = $user->socid;
}
$result = restrictedArea($user, 'user', '', '', 'user');


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
}

// Add a notification
if ($action == 'add') {
	$error = 0;

	if ($actionid <= 0) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Action")), null, 'errors');
		$error++;
		$action = 'create';
	}

	if (!$error) {
		$db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def";
		$sql .= " WHERE fk_user=".((int) $id)." AND fk_action=".((int) $actionid);
		if ($db->query($sql)) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."notify_def (datec, fk_user, fk_action)";
			$sql .= " VALUES ('".$db->idate($now)."', ".((int) $id).", ".((int) $actionid).")";

			if (!$db->query($sql)) {
				$error++;
				dol_print_error($db);
			}
		} else {
			dol_print_error($db);
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
			$action = 'create';
		}
	}
}

// Remove a notification
if ($action == 'delete') {
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def where rowid=".GETPOSTINT("actid");
	$db->query($sql);
}



/*
 *	View
 */

$form = new Form($db);

$object = new User($db);
$result = $object->fetch($id, '', '', 1);
$object->getrights();

$title = $langs->trans("ThirdParty").' - '.$langs->trans("Notification");
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
	$title = $object->name.' - '.$langs->trans("Notification");
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-user page-notify_card');


if ($result > 0) {
	$langs->load("other");

	$head = user_prepare_head($object);

	print dol_get_fiche_head($head, 'notify', $langs->trans("User"), -1, 'user');

	$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'&output=file&file='.urlencode(dol_sanitizeFileName($object->getFullName($langs).'.vcf')).'" class="refid" rel="noopener">';
	$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
	$morehtmlref .= '</a>';

	$urltovirtualcard = '/user/virtualcard.php?id='.((int) $object->id);
	$morehtmlref .= dolButtonToOpenUrlInDialogPopup('publicvirtualcard', $langs->transnoentitiesnoconv("PublicVirtualCardUrl").' - '.$object->getFullName($langs), img_picto($langs->trans("PublicVirtualCardUrl"), 'card', 'class="valignmiddle marginleftonly paddingrightonly"'), $urltovirtualcard, '', 'nohover');

	dol_banner_tab($object, 'id', $linkback, $user->hasRight('user', 'user', 'lire') || $user->admin, 'rowid', 'ref', $morehtmlref, '', 0, '', '', 0, '');

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Login
	print '<tr><td class="titlefield">'.$langs->trans("Login").'</td>';
	if (!empty($object->ldap_sid) && $object->statut == 0) {
		print '<td class="error">';
		print $langs->trans("LoginAccountDisableInDolibarr");
		print '</td>';
	} else {
		print '<td>';
		$addadmin = '';
		if (property_exists($object, 'admin')) {
			if (isModEnabled('multicompany') && !empty($object->admin) && empty($object->entity)) {
				$addadmin .= img_picto($langs->trans("SuperAdministratorDesc"), "redstar", 'class="paddingleft"');
			} elseif (!empty($object->admin)) {
				$addadmin .= img_picto($langs->trans("AdministratorDesc"), "star", 'class="paddingleft"');
			}
		}
		print showValueWithClipboardCPButton($object->login).$addadmin;
		print '</td>';
	}
	print '</tr>'."\n";

	/*print '<tr><td class="titlefield">'.$langs->trans("NbOfActiveNotifications").'</td>';   // Notification for this thirdparty
	print '<td colspan="3">';
	$nbofrecipientemails=0;
	$notify=new Notify($db);
	$tmparray = $notify->getNotificationsArray('', 0, null, $object->id, array('user'));
	foreach($tmparray as $tmpkey => $tmpval)
	{
		$nbofrecipientemails++;
	}
	print $nbofrecipientemails;
	print '</td></tr>';*/

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();

	print "\n";

	// Help
	print '<span class="opacitymedium">';
	print '<br>'.$langs->trans("NotificationsDesc");
	print '<br>'.$langs->trans("NotificationsDescUser").' - '.$langs->trans("YouAreHere");
	if (isModEnabled('societe')) {
		print '<br>'.$langs->trans("NotificationsDescContact");
	}
	print '<br>'.$langs->trans("NotificationsDescGlobal");
	print '</span>';

	print '<br><br><br>'."\n";


	// Add notification form
	//  print load_fiche_titre($langs->trans("AddNewNotification"), '', '');

	print '<form action="'.$_SERVER["PHP_SELF"].'?id='.urlencode((string) ($id)).'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	$param = "&id=".urlencode((string) ($id));

	// Line with titles
	/*  print '<table width="100%" class="noborder">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("Target", $_SERVER["PHP_SELF"], "c.lastname,c.firstname", '', $param, 'width="45%"', $sortfield, $sortorder);
	print_liste_field_titre("Action", $_SERVER["PHP_SELF"], "", '', $param, 'width="35%"', $sortfield, $sortorder);
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "n.type", '', $param, 'width="10%"', $sortfield, $sortorder);
	print_liste_field_titre('');
	print "</tr>\n";

	print '</table>';

	print '<br>';
	*/
	// List of notifications enabled for contacts
	$sql = "SELECT n.rowid, n.type,";
	$sql .= " a.code, a.label,";
	$sql .= " c.rowid as userid, c.entity, c.login, c.lastname, c.firstname, c.email, c.statut as status";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_action_trigger as a,";
	$sql .= " ".MAIN_DB_PREFIX."notify_def as n,";
	$sql .= " ".MAIN_DB_PREFIX."user c";
	$sql .= " WHERE a.rowid = n.fk_action";
	$sql .= " AND c.rowid = n.fk_user";
	$sql .= " AND c.rowid = ".((int) $object->id);
	$sql .= " AND c.entity IN (".getEntity('user').')';

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
	} else {
		dol_print_error($db);
	}

	$newcardbutton = '';
	$newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=create&backtopage='.urlencode($_SERVER['PHP_SELF']), '', $permissiontoadd);

	$titlelist = $langs->trans("ListOfActiveNotifications");

	// List of active notifications
	// @phan-suppress-next-line PhanPluginSuspiciousParamPosition, PhanPluginSuspiciousParamOrder
	print_barre_liste($titlelist, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $num, 'email', 0, $newcardbutton, '', $limit, 0, 0, 1);

	// Line with titles
	print '<table width="100%" class="noborder">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("Target", $_SERVER["PHP_SELF"], "c.lastname,c.firstname", '', $param, 'width="45%"', $sortfield, $sortorder);
	print_liste_field_titre("Action", $_SERVER["PHP_SELF"], "", '', $param, 'width="35%"', $sortfield, $sortorder);
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "n.type", '', $param, 'width="10%"', $sortfield, $sortorder);
	print_liste_field_titre('', '', '');
	print '</tr>';


	if ($action == 'create') {
		// $listofemails=$object->thirdparty_and_contact_email_array();
		if ($object->email) {
			$actions = array();

			// Load array of available notifications
			$notificationtrigger = new InterfaceNotification($db);
			$listofnotifiedevents = $notificationtrigger->getListOfManagedEvents();

			foreach ($listofnotifiedevents as $notifiedevent) {
				$label = ($langs->trans("Notify_".$notifiedevent['code']) != "Notify_".$notifiedevent['code'] ? $langs->trans("Notify_".$notifiedevent['code']) : $notifiedevent['label']);
				$actions[$notifiedevent['rowid']] = $label;
			}
			print '<tr class="oddeven nohover"><td>';
			print $object->getNomUrl(1);
			if (isValidEmail($object->email)) {
				print ' &lt;'.$object->email.'&gt;';
			} else {
				$langs->load("errors");
				print ' &nbsp; '.img_warning().' '.$langs->trans("ErrorBadEMail", $object->email);
			}
			print '</td>';
			print '<td>';
			print img_picto('', 'object_action', '', false, 0, 0, '', 'paddingright').$form->selectarray("actionid", $actions, '', 1);
			print '</td>';
			print '<td>';
			$type = array('email' => $langs->trans("EMail"));
			print $form->selectarray("typeid", $type);
			print '</td>';
			print '<td class="nowraponall">';
			print '<input type="submit" class="button button-add" value="'.$langs->trans("Add").'">';
			print '&nbsp;';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</td>';
			print '</tr>';
		} else {
			print '<tr class="oddeven"><td colspan="4">';
			print $langs->trans("YouMustAssignUserMailFirst");
			print '</td></tr>';
		}
	} else {
		if ($num) {
			$i = 0;

			$userstatic = new User($db);

			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$userstatic->id = $obj->userid;
				$userstatic->lastname = $obj->lastname;
				$userstatic->firstname = $obj->firstname;
				$userstatic->email = $obj->email;
				$userstatic->status = $obj->status;

				print '<tr class="oddeven">';
				print '<td>'.$userstatic->getNomUrl(1);
				if ($obj->type == 'email') {
					if (isValidEmail($obj->email)) {
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
				if ($obj->type == 'email') {
					print $langs->trans("Email");
				}
				if ($obj->type == 'sms') {
					print $langs->trans("SMS");
				}
				print '</td>';
				print '<td class="right"><a href="card.php?id='.$id.'&action=delete&token='.newToken().'&actid='.$obj->rowid.'">'.img_delete().'</a></td>';
				print '</tr>';
				$i++;
			}
			$db->free($resql);
		} else {
			print '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}
		// List of notifications enabled for fixed email
		/*
		foreach($conf->global as $key => $val) {
			if (! preg_match('/^NOTIFICATION_FIXEDEMAIL_(.*)/', $key, $reg)) continue;
			print '<tr class="oddeven"><td>';
			$listtmp=explode(',', $val);
			$first=1;
			foreach($listtmp as $keyemail => $valemail) {
				if (! $first) print ', ';
				$first=0;
				$valemail=trim($valemail);
				//print $keyemail.' - '.$valemail.' - '.$reg[1].'<br>';
				if (isValidEmail($valemail, 1)) {
					if ($valemail == '__SUPERVISOREMAIL__') print $valemail;
					else print ' &lt;'.$valemail.'&gt;';
				} else {
					print ' '.img_warning().' '.$langs->trans("ErrorBadEMail",$valemail);
				}
			}
			print '</td>';
			print '<td>';
			$notifcode=preg_replace('/_THRESHOLD_.*$/','',$reg[1]);
			$notifcodecond=preg_replace('/^.*_(THRESHOLD_)/','$1',$reg[1]);
			$label=($langs->trans("Notify_".$notifcode)!="Notify_".$notifcode?$langs->trans("Notify_".$notifcode):$notifcode);
			print $label;
			if (preg_match('/^THRESHOLD_HIGHER_(.*)$/',$notifcodecond,$regcond) && ($regcond[1] > 0)) {
				print ' - '.$langs->trans("IfAmountHigherThan",$regcond[1]);
			}
			print '</td>';
			print '<td>';
			print $langs->trans("Email");
			print '</td>';
			print '<td class="right">'.$langs->trans("SeeModuleSetup", $langs->transnoentitiesnoconv("Module600Name")).'</td>';
			print '</tr>';
		}*/
		/*if ($user->admin) {
			print '<tr class="oddeven"><td colspan="4">';
			print '+ <a href="'.DOL_URL_ROOT.'/admin/notification.php">'.$langs->trans("SeeModuleSetup", $langs->transnoentitiesnoconv("Module600Name")).'</a>';
			print '</td></tr>';
		}*/
	}

	print '</table>';

	print '</form>';


	print '<br><br>'."\n";


	// List
	$sql = "SELECT n.rowid, n.daten, n.email, n.objet_type as object_type, n.objet_id as object_id, n.type,";
	$sql .= " c.rowid as id, c.lastname, c.firstname, c.email as contactemail, c.statut as status,";
	$sql .= " a.code, a.label";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_action_trigger as a,";
	$sql .= " ".MAIN_DB_PREFIX."notify as n";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as c ON n.fk_user = c.rowid";
	$sql .= " WHERE a.rowid = n.fk_action";
	$sql .= " AND n.fk_user = ".((int) $object->id);
	$sql .= $db->order($sortfield, $sortorder);

	// Count total nb of records
	$nbtotalofrecords = '';
	if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
		$result = $db->query($sql);
		$nbtotalofrecords = $db->num_rows($result);
		if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
			$page = 0;
			$offset = 0;
		}
	}

	$sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
	} else {
		dol_print_error($db);
	}

	$param = '&id='.$object->id;
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.$contextpage;
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.$limit;
	}

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
	if (isset($optioncss) && $optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	// List of notifications done  @phan-suppress-next-line PhanPluginSuspiciousParamOrder
	print_barre_liste($langs->trans("ListOfNotificationsDone"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'email', 0, '', '', $limit);

	// Line with titles
	print '<table width="100%" class="noborder">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("Target", $_SERVER["PHP_SELF"], "c.lastname,c.firstname", '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Action", $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "n.type", '', $param, '', $sortfield, $sortorder);
	//print_liste_field_titre("Object",$_SERVER["PHP_SELF"],"",'',$param,'"',$sortfield,$sortorder);
	print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "n.daten", '', $param, '', $sortfield, $sortorder, 'right ');
	print '</tr>';

	if ($num) {
		$i = 0;

		$userstatic = new User($db);

		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven"><td>';
			if ($obj->id > 0) {
				$userstatic->id = $obj->id;
				$userstatic->lastname = $obj->lastname;
				$userstatic->firstname = $obj->firstname;
				$userstatic->status = $obj->status;
				$userstatic->email = $obj->email;
				print $userstatic->getNomUrl(1);
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
			if ($obj->type == 'email') {
				print $langs->trans("Email");
			}
			if ($obj->type == 'sms') {
				print $langs->trans("Sms");
			}
			print '</td>';
			// TODO Add link to object here for other types
			/*print '<td>';
			if ($obj->object_type == 'order') {
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
} else {
	dol_print_error(null, 'RecordNotFound');
}

// End of page
llxFooter();
$db->close();
