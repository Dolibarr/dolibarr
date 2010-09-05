<?PHP
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010 	   Juanjo Menent        <jmenent@2byte.es>
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
 */

/**
 *	\file       htdocs/admin/prelevement.php
 *	\ingroup    prelevement
 *	\brief      Page configuration des prelevements
 *	\version    $Id$
 */

require('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/class/bon-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");
$langs->load("withdrawals");
$langs->load("bills");

// Security check
if (!$user->admin)
accessforbidden();

/*if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'prelevement', '', '', 'bons');*/


if ($_GET["action"] == "set")
{
	for ($i = 6 ; $i < 7 ; $i++)
	{
		dolibarr_set_const($db, $_POST["nom$i"], $_POST["value$i"],'chaine',0,'',$conf->entity);
	}

	Header("Location: prelevement.php");
	exit;
}

if ($_GET["action"] == "addnotif")
{
	$bon = new BonPrelevement($db);
	$bon->AddNotification($_POST["user"],$_POST["action"]);

	Header("Location: prelevement.php");
	exit;
}

if ($_GET["action"] == "deletenotif")
{
	$bon = new BonPrelevement($db);
	$bon->DeleteNotificationById($_GET["notif"]);

	Header("Location: prelevement.php");
	exit;
}

/*
 *	View
 */

$html=new Form($db);

llxHeader('',$langs->trans("WithdrawalsSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("WithdrawalsSetup"),$linkback,'setup');

print '<form method="post" action="prelevement.php?action=set">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="nobordernopadding" width="100%">';
print '<tr class="liste_titre">';
print '<td width="30%">'.$langs->trans("Parameter").'</td>';
print '<td width="40%">'.$langs->trans("Value").'</td>';
print '<td width="30%">'.$langs->trans("CurrentValue").'</td>';
print "</tr>\n";
print '<tr class="impair"><td>'.$langs->trans("ResponsibleUser").'</td>';
print '<td align="left">';
print '<input type="hidden" name="nom6" value="PRELEVEMENT_USER">';
print $html->select_users($conf->global->PRELEVEMENT_USER,'value6',1);
print '</td>';
print '<td>';
if ($conf->global->PRELEVEMENT_USER > 0)
{
	$cuser = new User($db);
	$cuser->fetch($conf->global->PRELEVEMENT_USER);
	print $cuser->getFullName($langs);
}
else
{
	print '&nbsp;';
}
print '</td></tr>';
print '<tr><td align="center" colspan="3"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';
print '</table>';
print '</form>';
print '<br>';


/*
 * Notifications
 */

if ($conf->global->MAIN_MODULE_NOTIFICATION)
{
	$langs->load("mails");
	print_titre($langs->trans("Notifications"));

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=addnotif">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("User").'</td>';
	print '<td>'.$langs->trans("Value").'</td>';
	print '<td align="right">'.$langs->trans("Action").'</td>';
	print "</tr>\n";
	print '<tr class="impair"><td align="left">';
    print $html->select_users(0,'user',1);
    print '</td>';
	print '<td>';
	print '<select name="action">';
    $sql = "SELECT ad.rowid, ad.code, ad.titre";
    $sql.= " FROM action_def as ad";
    $sql.= " WHERE ad.objet_type = 'withdraw'";
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        $var = false;
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);
            print '<option value="'.$obj->code.'">'.$obj->titre.'</option>';
        }
    }
	print '</select></td>';
	print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td></tr>';
}
// List of current notifications for objet_type='withdraw'
$sql = "SELECT u.name, u.firstname";
$sql.= ", ad.rowid, ad.code, ad.titre";
$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
$sql.= ", ".MAIN_DB_PREFIX."notify_def as nd";
$sql.= ", ".MAIN_DB_PREFIX."action_def as ad";
$sql.= " WHERE u.rowid = nd.fk_user AND nd.fk_action = ad.rowid";
$sql.= " ad.objet_type = 'withdraw'";
$sql.= " AND u.entity IN (0,".$conf->entity.")";
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$var = false;
	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;

		print "<tr $bc[$var]>";
		print '<td>'.$obj->firstname." ".$obj->name.'</td>';
		print '<td>'.$obj->titre.'</td>';

		if ($user->rights->prelevement->bons->configurer)
		{
			print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=deletenotif&amp;notif='.$obj->code.'">'.img_delete().'</a></td></tr>';
		}
		else
		{
			print '</tr>';
		}
		$i++;
	}
	$db->free($resql);
}

print '</table>';
print '</form>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
