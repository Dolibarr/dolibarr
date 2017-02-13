<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013	   Juanjo Menent		<jmenent@2byte.es>
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
 *	    \file       htdocs/admin/events.php
 *      \ingroup    core
 *      \brief      Log event setup page
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/events.class.php';


if (!$user->admin)
accessforbidden();

$langs->load("users");
$langs->load("admin");
$langs->load("other");

$action=GETPOST("action");


$securityevent=new Events($db);
$eventstolog=$securityevent->eventstolog;



/*
 *	Actions
 */
if ($action == "save")
{
	$i=0;

	$db->begin();

	foreach ($eventstolog as $key => $arr)
	{
		$param='MAIN_LOGEVENTS_'.$arr['id'];
		//print "param=".$param." - ".$_POST[$param];
		if (! empty($_POST[$param])) dolibarr_set_const($db,$param,$_POST[$param],'chaine',0,'',$conf->entity);
		else dolibarr_del_const($db,$param,$conf->entity);
	}

	$db->commit();
	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
}



/*
 * View
 */

$wikihelp='EN:Setup_Security|FR:Paramétrage_Sécurité|ES:Configuración_Seguridad';
llxHeader('',$langs->trans("Audit"),$wikihelp);

//$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SecuritySetup"),'','title_setup');

print $langs->trans("LogEventDesc")."<br>\n";
print "<br>\n";


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="save">';

$head=security_prepare_head();

dol_fiche_head($head, 'audit', $langs->trans("Security"));


$var=true;
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td colspan=\"2\">".$langs->trans("LogEvents")."</td>";
print "</tr>\n";
// Loop on each event type
foreach ($eventstolog as $key => $arr)
{
	if ($arr['id'])
	{
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td>'.$arr['id'].'</td>';
		print '<td>';
		$key='MAIN_LOGEVENTS_'.$arr['id'];
		$value=$conf->global->$key;
		print '<input '.$bc[$var].' type="checkbox" name="'.$key.'" value="1"'.($value?' checked':'').'>';
		print '</td></tr>'."\n";
	}
}
print '</table>';

dol_fiche_end();

print '<div class="center">';
print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</div>";

print "</form>\n";


llxFooter();
$db->close();
