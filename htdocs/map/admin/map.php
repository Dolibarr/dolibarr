<?php
/* Copyright (C) 2010-2011 Herve Prot        <herve.prot@symeos.com>
 * 
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
 *   	\file       htdocs/map/admin/map.php
 *		\ingroup    technic
 *		\brief      Page to setup the module Foundation
 *		\version    $Id: map.php,v 1.10.2.1 2011/05/30 13:43:59 herve Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");
$langs->load("map@map");

if (!$user->admin)
accessforbidden();


$typeconst=array('yesno','texte','chaine');


// Action mise a jour ou ajout d'une constante
if ($_POST["action"] == 'update' || $_POST["action"] == 'add')
{
	$const=$_POST["constname"];
	$value=$_POST["constvalue"];
	$type=$_POST["consttype"];
	$constnote=isset($_POST["constnote"])?$_POST["constnote"]:'';
	$result=dolibarr_set_const($db,$const,$value,$typeconst[$type],0,$constnote,$conf->entity);
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
print_fiche_titre($langs->trans("MapSetup"),$linkback,'setup');
print "<br>";


print_fiche_titre($langs->trans("MapMainOptions"),'','');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
print "</tr>\n";
$var=true;
$form = new Form($db);

$graphtheme=array(
		'openlayers',
		'google',
		'microsoft'
		);

$var=!$var;
print '<form action="map.php" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="rowid" value="'.$rowid.'">';
print '<input type="hidden" name="constname" value="MAP_SYSTEM">';
print "<tr $bc[$var] class=value><td>".$langs->trans("MapSolution").'</td><td>';
print $form->selectarray('constvalue',$graphtheme,$conf->global->MAP_SYSTEM,0,0,1);
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

// Export
$var=!$var;
print '<form action="map.php" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="rowid" value="'.$rowid.'">';
print '<input type="hidden" name="constname" value="GOOGLE_KEY">';
print "<tr $bc[$var] class=value><td>".$langs->trans("GoogleKey").'</td><td>';
print '<input type="text" size="100" name="constvalue" value="'.$conf->global->GOOGLE_KEY.'">';
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

print "</tr>\n";
print '</form>';
print '</table>';
print '<br>';


?>
