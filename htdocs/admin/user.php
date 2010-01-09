<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
 *   	\file       htdocs/admin/user.php
 *		\ingroup    core
 *		\brief      Page to setup user module
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");
$langs->load("members");
$langs->load("users");

if (!$user->admin)
accessforbidden();


$typeconst=array('yesno','texte','chaine');


// Action mise a jour ou ajout d'une constante
if ($_POST["action"] == 'update' || $_POST["action"] == 'add')
{
	$result=dolibarr_set_const($db, $_POST["constname"],$_POST["constvalue"],$typeconst[$_POST["consttype"]],0,isset($_POST["constnote"])?$_POST["constnote"]:'',$conf->entity);
	if ($result < 0)
	{
		print $db->error();
	}
}

// Action activation d'un sous module du module adherent
if ($_GET["action"] == 'set')
{
	$result=dolibarr_set_const($db, $_GET["name"],$_GET["value"],'',0,'',$conf->entity);
	if ($result < 0)
	{
		print $db->error();
	}
}

// Action desactivation d'un sous module du module adherent
if ($_GET["action"] == 'unset')
{
	$result=dolibarr_del_const($db,$_GET["name"],$conf->entity);
	if ($result < 0)
	{
		print $db->error();
	}
}


/*
 * View
 */

llxHeader();


$var=True;

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("UsersSetup"),$linkback,'setup');
print "<br>";


print_fiche_titre($langs->trans("MemberMainOptions"),'','');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
print "</tr>\n";
$var=true;
$form = new Form($db);

// Mail required for members
$var=!$var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="rowid" value="'.$rowid.'">';
print '<input type="hidden" name="constname" value="USER_MAIL_REQUIRED">';
print "<tr $bc[$var] class=value><td>".$langs->trans("UserMailRequired").'</td><td>';
print $form->selectyesno('constvalue',$conf->global->USER_MAIL_REQUIRED,1);
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

print '</table>';
print '<br>';

$db->close();

print '<br>';

llxFooter('$Date$ - $Revision$');
?>