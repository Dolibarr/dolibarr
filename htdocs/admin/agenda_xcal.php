<?php
/* Copyright (C) 2008-2015 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2015		Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	    \file       htdocs/admin/agenda_xcal.php
 *      \ingroup    agenda
 *      \brief      Page to setup miscellaneous options of agenda module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';


if (!$user->admin)
    accessforbidden();

// Load translation files required by the page
$langs->loadLangs(array("admin","other","agenda"));

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
    $i+=dolibarr_set_const($db,'AGENDA_EXPORT_FIX_TZ',trim(GETPOST('AGENDA_EXPORT_FIX_TZ','alpha')),'chaine',0,'',$conf->entity);

    if ($i >= 4)
    {
        $db->commit();
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        $db->rollback();
        setEventMessages($langs->trans("SaveFailed"), null, 'errors');
    }
}



/**
 * View
 */

if (! isset($conf->global->MAIN_AGENDA_EXPORT_PAST_DELAY)) $conf->global->MAIN_AGENDA_EXPORT_PAST_DELAY=100;

$wikihelp='EN:Module_Agenda_En|FR:Module_Agenda|ES:Módulo_Agenda';
llxHeader('', $langs->trans("AgendaSetup"), $wikihelp);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("AgendaSetup"),$linkback,'title_setup');


print '<form name="agendasetupform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

$head=agenda_prepare_head();

dol_fiche_head($head, 'xcal', $langs->trans("Agenda"), -1, 'action');

print $langs->trans("AgendaSetupOtherDesc")."<br>\n";
print "<br>\n";

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
//print "<td>".$langs->trans("Examples")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '<tr class="oddeven">';
print '<td class="fieldrequired">'.$langs->trans("PasswordTogetVCalExport")."</td>";
print '<td><input required="required" type="text" class="flat" id="MAIN_AGENDA_XCAL_EXPORTKEY" name="MAIN_AGENDA_XCAL_EXPORTKEY" value="' . (GETPOST('MAIN_AGENDA_XCAL_EXPORTKEY','alpha')?GETPOST('MAIN_AGENDA_XCAL_EXPORTKEY','alpha'):$conf->global->MAIN_AGENDA_XCAL_EXPORTKEY) . '" size="40">';
if (! empty($conf->use_javascript_ajax))
	print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
print '</td>';
print "<td>&nbsp;</td>";
print "</tr>";

print '<tr class="oddeven">';
print "<td>".$langs->trans("PastDelayVCalExport")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"MAIN_AGENDA_EXPORT_PAST_DELAY\" value=\"". (GETPOST('MAIN_AGENDA_EXPORT_PAST_DELAY','alpha')?GETPOST('MAIN_AGENDA_EXPORT_PAST_DELAY','alpha'):$conf->global->MAIN_AGENDA_EXPORT_PAST_DELAY) . "\" size=\"10\"> ".$langs->trans("days")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '<tr class="oddeven">';
print "<td>".$langs->trans("UseACacheDelay")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"MAIN_AGENDA_EXPORT_CACHE\" value=\"". (GETPOST('MAIN_AGENDA_EXPORT_CACHE','alpha')?GETPOST('MAIN_AGENDA_EXPORT_CACHE','alpha'):$conf->global->MAIN_AGENDA_EXPORT_CACHE) . "\" size=\"10\"></td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '</table>';

print '<br>';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td width="25%">'.$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "</tr>";
print '<tr class="oddeven">';
print '<td>'.$langs->trans("FixTZ")."</td>";
print "<td>";
print '<input class="flat" type="text" size="4" name="AGENDA_EXPORT_FIX_TZ" value="'.$conf->global->AGENDA_EXPORT_FIX_TZ.'">';
print ' &nbsp; '.$langs->trans("FillThisOnlyIfRequired");
print "</td>";
print "</tr>";

print '</table>';

dol_fiche_end();

print '<div class="center">';
print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</div>";

print "</form>\n";


clearstatcache();

//if ($mesg) print "<br>$mesg<br>";
print "<br>";


// Define $urlwithroot
$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current


// Show message
$message='';
$urlvcal='<a href="'.$urlwithroot.'/public/agenda/agendaexport.php?format=vcal&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'...').'" target="_blank">'.$urlwithroot.'/public/agenda/agendaexport.php?format=vcal&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'KEYNOTDEFINED').'</a>';
$message.=img_picto('','object_globe.png').' '.$langs->trans("WebCalUrlForVCalExport",'vcal',$urlvcal);
$message.='<br>';
$urlical='<a href="'.$urlwithroot.'/public/agenda/agendaexport.php?format=ical&type=event&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'...').'" target="_blank">'.$urlwithroot.'/public/agenda/agendaexport.php?format=ical&type=event&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'KEYNOTDEFINED').'</a>';
$message.=img_picto('','object_globe.png').' '.$langs->trans("WebCalUrlForVCalExport",'ical/ics',$urlical);
$message.='<br>';
$urlrss='<a href="'.$urlwithroot.'/public/agenda/agendaexport.php?format=rss&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'...').'" target="_blank">'.$urlwithroot.'/public/agenda/agendaexport.php?format=rss&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY?urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY):'KEYNOTDEFINED').'</a>';
$message.=img_picto('','object_globe.png').' '.$langs->trans("WebCalUrlForVCalExport",'rss',$urlrss);
$message.='<br>';
$message.='<br>';
print $message;

$message =$langs->trans("AgendaUrlOptions1",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptions3",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptionsNotAdmin",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptions4",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptionsProject",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptionsNotAutoEvent",'systemauto','systemauto').'<br>';

print info_admin($message);

if (! empty($conf->use_javascript_ajax))
{
	print "\n".'<script type="text/javascript">';
	print '$(document).ready(function () {
            $("#generate_token").click(function() {
            	$.get( "'.DOL_URL_ROOT.'/core/ajax/security.php", {
            		action: \'getrandompassword\',
            		generic: true
				},
				function(token) {
					$("#MAIN_AGENDA_XCAL_EXPORTKEY").val(token);
				});
            });
    });';
	print '</script>';
}


llxFooter();
$db->close();
