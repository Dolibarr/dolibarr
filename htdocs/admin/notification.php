<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.org>
 * Copyright (C) 2013      Juanjo Menent		<jmenent@2byte.es>
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

/**
 *	    \file       htdocs/admin/notification.php
 *		\ingroup    notification
 *		\brief      Page to setup notification module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/triggers/interface_50_modNotification_Notification.class.php';

$langs->load("admin");
$langs->load("other");
$langs->load("orders");
$langs->load("propal");
$langs->load("bills");
$langs->load("errors");
$langs->load("mails");

// Security check
if (!$user->admin)
  accessforbidden();

$action = GETPOST("action");


/*
 * Actions
 */

if ($action == 'setvalue' && $user->admin)
{
	$db->begin();

	$result=dolibarr_set_const($db, "NOTIFICATION_EMAIL_FROM", $_POST["email_from"], 'chaine', 0, '', $conf->entity);
    if ($result < 0) $error++;

    if (! $error)
    {
    	//var_dump($_POST);
	    foreach($_POST as $key => $val)
	    {
	    	if (! preg_match('/^NOTIF_(.*)_key$/', $key, $reg)) continue;

	    	$newval='';
	    	$newkey='';

	    	$shortkey=preg_replace('/_key$/','',$key);
    		//print $shortkey.'<br>';

	    	if (preg_match('/^NOTIF_(.*)_old_(.*)_key/',$key,$reg))
	    	{
				dolibarr_del_const($db, 'NOTIFICATION_FIXEDEMAIL_'.$reg[1].'_THRESHOLD_HIGHER_'.$reg[2], $conf->entity);

				$newkey='NOTIFICATION_FIXEDEMAIL_'.$reg[1].'_THRESHOLD_HIGHER_'.((int) GETPOST($shortkey.'_amount'));
				$newval=GETPOST($shortkey.'_key');
				//print $newkey.' - '.$newval.'<br>';
	    	}
	    	else if (preg_match('/^NOTIF_(.*)_new_key/',$key,$reg))
	    	{
		    	// Add a new entry
	    		$newkey='NOTIFICATION_FIXEDEMAIL_'.$reg[1].'_THRESHOLD_HIGHER_'.((int) GETPOST($shortkey.'_amount'));
	    		$newval=GETPOST($shortkey.'_key');
	    	}

	    	if ($newkey && $newval)
	    	{
				$result=dolibarr_set_const($db, $newkey, $newval, 'chaine', 0, '', $conf->entity);
	    	}
	    }
    }

  	if (! $error)
    {
    	$db->commit();

        setEventMessage($langs->trans("SetupSaved"));
    }
    else
	{
		$db->rollback();

        setEventMessage($langs->trans("Error"),'errors');
    }
}



/*
 *	View
 */

$form=new Form($db);
$notify = new Notify($db);

llxHeader('',$langs->trans("NotificationSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("NotificationSetup"),$linkback,'title_setup');

print $langs->trans("NotificationsDesc").'<br><br>';

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";
$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("NotificationEMailFrom").'</td><td>';
print '<input size="32" type="email" name="email_from" value="'.$conf->global->NOTIFICATION_EMAIL_FROM.'">';
if (! empty($conf->global->NOTIFICATION_EMAIL_FROM) && ! isValidEmail($conf->global->NOTIFICATION_EMAIL_FROM)) print ' '.img_warning($langs->trans("ErrorBadEMail"));
print '</td></tr>';
print '</table>';

print '<br>';


if ($conf->societe->enabled)
{
	print load_fiche_titre($langs->trans("ListOfNotificationsPerContact"),'','');

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Module").'</td>';
	print '<td>'.$langs->trans("Code").'</td>';
	print '<td>'.$langs->trans("Label").'</td>';
	print '<td align="right">'.$langs->trans("NbOfTargetedContacts").'</td>';
	print '<td>'.'</td>';
	print "</tr>\n";

	// Load array of available notifications
	$notificationtrigger=new InterfaceNotification($db);
	$listofnotifiedevents=$notificationtrigger->getListOfManagedEvents();

	$var=true;
	foreach($listofnotifiedevents as $notifiedevent)
	{
	    $var=!$var;
	    $label=$langs->trans("Notify_".$notifiedevent['code']); //!=$langs->trans("Notify_".$notifiedevent['code'])?$langs->trans("Notify_".$notifiedevent['code']):$notifiedevent['label'];

	    if ($notifiedevent['elementtype'] == 'order_supplier') $elementLabel = $langs->trans('SupplierOrder');
	    elseif ($notifiedevent['elementtype'] == 'propal') $elementLabel = $langs->trans('Proposal');
	    elseif ($notifiedevent['elementtype'] == 'facture') $elementLabel = $langs->trans('Bill');
	    elseif ($notifiedevent['elementtype'] == 'commande') $elementLabel = $langs->trans('Order');

	    print '<tr '.$bc[$var].'>';
	    print '<td>'.$elementLabel.'</td>';
	    print '<td>'.$notifiedevent['code'].'</td>';
	    print '<td>'.$label.'</td>';
	    print '<td align="right">';
		$tmparray = $notify->getNotificationsArray($notifiedevent['code'], 0);
		print count($tmparray);
	    print '</td>';
	    print '</tr>';
	}

	print '</table>';
	print '* '.$langs->trans("GoOntoContactCardToAddMore").'<br>';
	print '<br>';
}


print load_fiche_titre($langs->trans("ListOfFixedNotifications"),'','');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Module").'</td>';
print '<td>'.$langs->trans("Code").'</td>';
print '<td>'.$langs->trans("Label").'</td>';
print '<td>'.$langs->trans("FixedEmailTarget").'</td>';
print '<td>'.$langs->trans("Threshold").'</td>';
print '<td>'.'</td>';
print "</tr>\n";

// Load array of available notifications
$notificationtrigger=new InterfaceNotification($db);
$listofnotifiedevents=$notificationtrigger->getListOfManagedEvents();

$var=true;
foreach($listofnotifiedevents as $notifiedevent)
{
    $var=!$var;
    $label=$langs->trans("Notify_".$notifiedevent['code']); //!=$langs->trans("Notify_".$notifiedevent['code'])?$langs->trans("Notify_".$notifiedevent['code']):$notifiedevent['label'];

    if ($notifiedevent['elementtype'] == 'order_supplier') $elementLabel = $langs->trans('SupplierOrder');
    elseif ($notifiedevent['elementtype'] == 'propal') $elementLabel = $langs->trans('Proposal');
    elseif ($notifiedevent['elementtype'] == 'facture') $elementLabel = $langs->trans('Bill');
    elseif ($notifiedevent['elementtype'] == 'commande') $elementLabel = $langs->trans('Order');

    print '<tr '.$bc[$var].'>';
    print '<td>'.$elementLabel.'</td>';
    print '<td>'.$notifiedevent['code'].'</td>';
    print '<td>'.$label.'</td>';
    print '<td>';
    // Notification with threshold
    foreach($conf->global as $key => $val)
    {
		if ($val == '' || ! preg_match('/^NOTIFICATION_FIXEDEMAIL_'.$notifiedevent['code'].'_THRESHOLD_HIGHER_(.*)/', $key, $reg)) continue;

	    $param='NOTIFICATION_FIXEDEMAIL_'.$notifiedevent['code'].'_THRESHOLD_HIGHER_'.$reg[1];
    	$value=GETPOST('NOTIF_'.$notifiedevent['code'].'_old_'.$reg[1].'_key')?GETPOST('NOTIF_'.$notifiedevent['code'].'_old_'.$reg[1].'_key','alpha'):$conf->global->$param;

    	$s='<input type="text" size="32" name="NOTIF_'.$notifiedevent['code'].'_old_'.$reg[1].'_key" value="'.dol_escape_htmltag($value).'">';		// Do not use type="email" here, we must be able to enter a list of email with , separator.
	    $arrayemail=explode(',',$value);
		$showwarning=0;
		foreach($arrayemail as $key=>$valuedet)
		{
			$valuedet=trim($valuedet);
			if (! empty($valuedet) && ! isValidEmail($valuedet,1)) $showwarning++;
		}
	    if ((! empty($conf->global->$param)) && $showwarning) $s.=' '.img_warning($langs->trans("ErrorBadEMail"));
	    print $form->textwithpicto($s,$langs->trans("YouCanUseCommaSeparatorForSeveralRecipients").'<br>'.$langs->trans("YouCanAlsoUseSupervisorKeyword"),1,'help','',0,2);
		print '<br>';
    }
    // New entry input fields
    $s='<input type="text" size="32" name="NOTIF_'.$notifiedevent['code'].'_new_key" value="">';		// Do not use type="email" here, we must be able to enter a list of email with , separator.
    print $form->textwithpicto($s,$langs->trans("YouCanUseCommaSeparatorForSeveralRecipients").'<br>'.$langs->trans("YouCanAlsoUseSupervisorKeyword"),1,'help','',0,2);
    print '</td>';

    print '<td>';
    // Notification with threshold
    foreach($conf->global as $key => $val)
    {
		if ($val == '' || ! preg_match('/^NOTIFICATION_FIXEDEMAIL_'.$notifiedevent['code'].'_THRESHOLD_HIGHER_(.*)/', $key, $reg)) continue;

    	print $langs->trans("AmountHT").' >= <input type="text" size="4" name="NOTIF_'.$notifiedevent['code'].'_old_'.$reg[1].'_amount" value="'.dol_escape_htmltag($reg[1]).'">';
		print '<br>';
    }
    // New entry input fields
	print $langs->trans("AmountHT").' >= <input type="text" size="4" name="NOTIF_'.$notifiedevent['code'].'_new_amount" value="">';
	print '</td>';

    print '<td>';
	// TODO Add link to show message content

    print '</td>';
    print '</tr>';
}
print '</table>';

print '<br>';

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';

print '</form>';


llxFooter();

$db->close();
