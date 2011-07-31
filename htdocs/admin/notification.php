<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.org>
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
 */

/**
 *	    \file       htdocs/admin/notification.php
 *		\ingroup    notification
 *		\brief      Page to setup notification module
 *		\version    $Id: notification.php,v 1.13 2011/07/31 22:23:25 eldy Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/includes/triggers/interface_modNotification_Notification.class.php");

$langs->load("admin");

// Security check
if (!$user->admin)
  accessforbidden();


/*
 * Actions
 */

if ($_POST["action"] == 'setvalue' && $user->admin)
{
	$result=dolibarr_set_const($db, "NOTIFICATION_EMAIL_FROM",$_POST["email_from"],'chaine',0,'',$conf->entity);
  	if ($result >= 0)
  	{
  		$mesg='<div class="ok">'.$langs->trans("Success").'</div>';
  	}
  	else
  	{
		dol_print_error($db);
    }
}



/*
 *	View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("NotificationSetup"),$linkback,'setup');

print $langs->trans("NotificationsDesc").'<br><br>';

if ($mesg) print $mesg.'<br>';

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
print '<input size="32" type="text" name="email_from" value="'.$conf->global->NOTIFICATION_EMAIL_FROM.'">';
print '</td></tr>';
print '</table>';

print '<br>';

print '<center><input type="submit" class="button" value="'.$langs->trans("Modify").'"></center>';

print '</form>';
print '<br>';


print_fiche_titre($langs->trans("ListOfAvailableNotifications"),'','');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Module").'</td>';
print '<td>'.$langs->trans("Code").'</td>';
print '<td>'.$langs->trans("Label").'</td>';
print "</tr>\n";

// Load array of available notifications
$notificationtrigger=new InterfaceNotification($db);
$listofnotifiedevents=$notificationtrigger->getListOfManagedEvents();

foreach($listofnotifiedevents as $notifiedevent)
{
    $var=!$var;
    $label=$langs->trans("Notify_".$notifiedevent['code'])!=$langs->trans("Notify_".$notifiedevent['code'])?$langs->trans("Notify_".$notifiedevent['code']):$notifiedevent['label'];
    print '<tr '.$bc[$var].'>';
    print '<td>'.$notifiedevent['elementtype'].'</td>';
    print '<td>'.$notifiedevent['code'].'</td>';
    print '<td>'.$label.'</td>';
    print '</tr>';
}
print '</table>';


$db->close();


llxFooter('$Date: 2011/07/31 22:23:25 $ - $Revision: 1.13 $');

?>
