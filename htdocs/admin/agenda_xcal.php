<?php
/* Copyright (C) 2008-2010 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012		Juanjo Menent		<jmenent@2byte.es>
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
 *	    \file       htdocs/admin/agenda_xcal.php
 *      \ingroup    agenda
 *      \brief      Page to setup miscellaneous options of agenda module
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php');


if (!$user->admin)
    accessforbidden();

$langs->load("admin");
$langs->load("other");
$langs->load("agenda");

$def = array();
$actionsave=GETPOST('save','alpha');

// Sauvegardes parametres
if ($actionsave)
{
    $i=0;

    $db->begin();

    $i+=dolibarr_set_const($db,'MAIN_AGENDA_XCAL_EXPORTKEY',trim(GETPOST('MAIN_AGENDA_XCAL_EXPORTKEY','alpha')),'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db,'MAIN_AGENDA_EXPORT_PAST_DELAY',trim(GETPOST('MAIN_AGENDA_EXPORT_PAST_DELAY','alpha')),'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db,'MAIN_AGENDA_EXPORT_CACHE',trim(GETPOST('MAIN_AGENDA_EXPORT_CACHE','alpha')),'chaine',0,'',$conf->entity);

    if ($i >= 3)
    {
        $db->commit();
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $db->rollback();
        $mesg = "<font class=\"error\">".$langs->trans("SaveFailed")."</font>";
    }
}



/**
 * View
 */

if (! isset($conf->global->MAIN_AGENDA_EXPORT_PAST_DELAY)) $conf->global->MAIN_AGENDA_EXPORT_PAST_DELAY=100;

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("AgendaSetup"),$linkback,'setup');
print '<br>';

print $langs->trans("AgendaSetupOtherDesc")."<br>\n";
print "<br>\n";

$head=agenda_prepare_head();

dol_fiche_head($head, 'xcal', $langs->trans("Agenda"));


print '<form name="agendasetupform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<table class=\"noborder\" width=\"100%\">";

print "<tr class=\"liste_titre\">";
print "<td>".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
//print "<td>".$langs->trans("Examples")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print "<tr class=\"impair\">";
print '<td class="fieldrequired">'.$langs->trans("PasswordTogetVCalExport")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"MAIN_AGENDA_XCAL_EXPORTKEY\" value=\"". (GETPOST('MAIN_AGENDA_XCAL_EXPORTKEY','alpha')?GETPOST('MAIN_AGENDA_XCAL_EXPORTKEY','alpha'):$conf->global->MAIN_AGENDA_XCAL_EXPORTKEY) . "\" size=\"40\"></td>";
print "<td>&nbsp;</td>";
print "</tr>";

print "<tr class=\"pair\">";
print "<td>".$langs->trans("PastDelayVCalExport")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"MAIN_AGENDA_EXPORT_PAST_DELAY\" value=\"". (GETPOST('MAIN_AGENDA_EXPORT_PAST_DELAY','alpha')?GETPOST('MAIN_AGENDA_EXPORT_PAST_DELAY','alpha'):$conf->global->MAIN_AGENDA_EXPORT_PAST_DELAY) . "\" size=\"10\"> ".$langs->trans("days")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print "<tr class=\"impair\">";
print "<td>".$langs->trans("UseACacheDelay")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"MAIN_AGENDA_EXPORT_CACHE\" value=\"". (GETPOST('MAIN_AGENDA_EXPORT_CACHE','alpha')?GETPOST('MAIN_AGENDA_EXPORT_CACHE','alpha'):$conf->global->MAIN_AGENDA_EXPORT_CACHE) . "\" size=\"10\"></td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '</table>';

print '<br><center>';
print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</center>";

print "</form>\n";

print '</div>';

clearstatcache();

//if ($mesg) print "<br>$mesg<br>";
print "<br>";

// Show message
$message='';
$urlvcal='<a href="'.DOL_MAIN_URL_ROOT.'/public/agenda/agendaexport.php?format=vcal&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'...').'" target="_blank">'.DOL_MAIN_URL_ROOT.'/public/agenda/agendaexport.php?format=vcal&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'KEYNOTDEFINED').'</a>';
$message.=img_picto('','object_globe.png').' '.$langs->trans("WebCalUrlForVCalExport",'vcal',$urlvcal);
$message.='<br>';
$urlical='<a href="'.DOL_MAIN_URL_ROOT.'/public/agenda/agendaexport.php?format=ical&type=event&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'...').'" target="_blank">'.DOL_MAIN_URL_ROOT.'/public/agenda/agendaexport.php?format=ical&type=event&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'KEYNOTDEFINED').'</a>';
$message.=img_picto('','object_globe.png').' '.$langs->trans("WebCalUrlForVCalExport",'ical/ics',$urlical);
$message.='<br>';
$urlrss='<a href="'.DOL_MAIN_URL_ROOT.'/public/agenda/agendaexport.php?format=rss&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'...').'" target="_blank">'.DOL_MAIN_URL_ROOT.'/public/agenda/agendaexport.php?format=rss&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'KEYNOTDEFINED').'</a>';
$message.=img_picto('','object_globe.png').' '.$langs->trans("WebCalUrlForVCalExport",'rss',$urlrss);
$message.='<br>';
$message.='<br>';
print $message;

$message=$langs->trans("AgendaUrlOptions1",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptions2",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptions3",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptions4",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptions5",$user->login,$user->login);
print info_admin($message);

dol_htmloutput_mesg($mesg);

$db->close();

llxFooter();
?>
