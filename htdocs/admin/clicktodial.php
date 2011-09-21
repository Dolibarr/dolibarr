<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.org>
 * Copyright (C) 2011 	    Juanjo Menent		<jmenent@2byte.es>
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
 *   \file       htdocs/admin/clicktodial.php
 *   \ingroup    clicktodial
 *   \brief      Page to setup module clicktodial
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");

if (!$user->admin) accessforbidden();

$action = GETPOST("action");


/*
 *	Actions
 */
if ($action == 'setvalue' && $user->admin)
{
    $result=dolibarr_set_const($db, "CLICKTODIAL_URL",GETPOST("url"),'chaine',0,'',$conf->entity);
    if ($result >= 0)
    {
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}


/*
 * View
 */

$wikihelp='EN:Module_ClickToDial_En|FR:Module_ClickToDial|ES:Módulo_ClickTodial_Es';
llxHeader('',$langs->trans("ClickToDialSetup"),$wikihelp);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("ClickToDialSetup"),$linkback,'setup');

print $langs->trans("ClickToDialDesc")."<br>\n";

print '<br>';
print '<form method="post" action="clicktodial.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

$var=true;

print '<table class="nobordernopadding" width="100%">';
print '<tr class="liste_titre">';
print '<td width="120">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";
$var=!$var;
print '<tr '.$bc[$var].'><td valign="top">';
print $langs->trans("URL").'</td><td>';
print '<input size="92" type="text" name="url" value="'.$conf->global->CLICKTODIAL_URL.'"><br>';
print '<br>';
print $langs->trans("ClickToDialUrlDesc").'<br>';
print $langs->trans("Example").':<br>http://myphoneserver/mypage?login=__LOGIN__&password=__PASS__&caller=__PHONEFROM__&called=__PHONETO__';
print '</td></tr>';

print '<tr><td colspan="3" align="center"><br><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table></form>';

/*if (! empty($conf->global->CLICKTODIAL_URL))
 {
 print $langs->trans("Test");
 // Add a phone number to test
 }
 */

dol_htmloutput_mesg($mesg);

$db->close();

llxFooter();
?>
