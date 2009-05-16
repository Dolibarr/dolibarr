<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/admin/agenda.php
 *      \ingroup    agenda
 *      \brief      Page de configuration du module agenda
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/agenda.lib.php');


if (!$user->admin)
    accessforbidden();

$langs->load("admin");
$langs->load("other");
$langs->load("agenda");

$def = array();
$actionsave=$_POST["save"];

// Sauvegardes parametres
if ($actionsave)
{
    $i=0;

    $db->begin();

    $i+=dolibarr_set_const($db,'MAIN_AGENDA_XCAL_EXPORTKEY',trim($_POST["MAIN_AGENDA_XCAL_EXPORTKEY"]),'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db,'MAIN_AGENDA_EXPORT_CACHE',trim($_POST["MAIN_AGENDA_EXPORT_CACHE"]),'chaine',0,'',$conf->entity);

    if ($i >= 2)
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
 * Vies
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("AgendaSetup"),$linkback,'setup');
print '<br>';

print $langs->trans("AgendaSetupOtherDesc")."<br>\n";
print "<br>\n";

$head=agenda_prepare_head();

dol_fiche_head($head, 'xcal', $langs->trans("Agenda"));


print '<form name="agendasetupform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
print "<table class=\"noborder\" width=\"100%\">";

print "<tr class=\"liste_titre\">";
print "<td>".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
//print "<td>".$langs->trans("Examples")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print "<tr class=\"impair\">";
print "<td>".$langs->trans("PasswordTogetVCalExport")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"MAIN_AGENDA_XCAL_EXPORTKEY\" value=\"". ($_POST["MAIN_AGENDA_XCAL_EXPORTKEY"]?$_POST["MAIN_AGENDA_XCAL_EXPORTKEY"]:$conf->global->MAIN_AGENDA_XCAL_EXPORTKEY) . "\" size=\"40\"></td>";
print "<td>&nbsp;</td>";
print "</tr>";

print "<tr class=\"pair\">";
print "<td>".$langs->trans("UseACacheDelay")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"MAIN_AGENDA_EXPORT_CACHE\" value=\"". ($_POST["MAIN_AGENDA_EXPORT_CACHE"]?$_POST["MAIN_AGENDA_EXPORT_CACHE"]:$conf->global->MAIN_AGENDA_EXPORT_CACHE) . "\" size=\"10\"></td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '</table>';

print '<br><center>';
print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</center>";

print "</form>\n";

print '</div>';

clearstatcache();

if ($mesg) print "<br>$mesg<br>";
print "<br>";

// Show message
$message='';
$urlwithouturlroot=eregi_replace(DOL_URL_ROOT.'$','',$dolibarr_main_url_root);
$urlvcal='<a href="'.DOL_URL_ROOT.'/comm/action/agendaexport.php?format=vcal&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'...').'" target="_blank">'.$urlwithouturlroot.DOL_URL_ROOT.'/comm/action/agendaexport.php?format=vcal&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'KEYNOTDEFINED').'</a>';
$message.=$langs->trans("WebCalUrlForVCalExport",'vcal',$urlvcal);
$message.='<br>';
$urlical='<a href="'.DOL_URL_ROOT.'/comm/action/agendaexport.php?format=ical&type=event&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'...').'" target="_blank">'.$urlwithouturlroot.DOL_URL_ROOT.'/comm/action/agendaexport.php?format=ical&type=event&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'KEYNOTDEFINED').'</a>';
$message.=$langs->trans("WebCalUrlForVCalExport",'ical',$urlical);
$message.='<br>';
$urlrss='<a href="'.DOL_URL_ROOT.'/comm/action/agendaexport.php?format=rss&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'...').'" target="_blank">'.$urlwithouturlroot.DOL_URL_ROOT.'/comm/action/agendaexport.php?format=rss&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'KEYNOTDEFINED').'</a>';
$message.=$langs->trans("WebCalUrlForVCalExport",'rss',$urlrss);
$message.='<br>';
$message.='<br>';
$message.=$langs->trans("AgendaUrlOptions1",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptions2",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptions3",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptions4",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptions5",$user->login,$user->login);
print info_admin($message);

$db->close();

llxFooter('$Date$ - $Revision$');
?>
