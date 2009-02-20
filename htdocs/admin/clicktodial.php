<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.org>
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
 
/**	    \file       htdocs/admin/clicktodial.php
		\ingroup    clicktodial
		\brief      Page d'administration/configuration du module clicktodial
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


if ($_POST["action"] == 'setvalue' && $user->admin)
{
	$result=dolibarr_set_const($db, "CLICKTODIAL_URL",$_POST["url"]);
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
 *
 *
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("ClickToDialSetup"),$linkback,'setup');

print $langs->trans("ClickToDialDesc")."<br>\n";


if ($mesg) print '<br>'.$mesg;

print '<br>';
print '<form method="post" action="clicktodial.php">';
print '<input type="hidden" name="action" value="setvalue">';

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Value").'</td><td>'.$langs->trans("Description").'</td>';
print "</tr>\n";
$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("URL").'</td><td>';
print '<input size="48" type="text" name="url" value="'.$conf->global->CLICKTODIAL_URL.'">';
print '</td><td>';
print $langs->trans("ClickToDialUrlDesc").'<br>';
print $langs->trans("Example").': http://myphoneserver/mypage?login=%3$s&password=%4$s&caller=%2$s&called=%1$s';
print '</td></tr>';

print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table></form>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
