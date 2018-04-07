<?php
/* Copyright (C) 2008-2018 	Laurent Destailleur <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/admin/dav.php
 *      \ingroup    dav
 *      \brief      Page to setup DAV server
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/dav/dav.lib.php';


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

    $i+=dolibarr_set_const($db,'XXX',trim(GETPOST('XXX','alpha')),'chaine',0,'',$conf->entity);

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


llxHeader('', $langs->trans("DAVSetup"), $wikihelp);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("DAVSetup"),$linkback,'title_setup');


print '<form name="agendasetupform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

$head=dav_admin_prepare_head();

dol_fiche_head($head, 'webdav', '', -1, 'action');

print $langs->trans("WebDAVSetupDesc")."<br>\n";
print "<br>\n";

/*
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

print '</table>';
*/

dol_fiche_end();

/*print '<div class="center">';
print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</div>";
*/
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
$url='<a href="'.$urlwithroot.'/dav/fileserver.php" target="_blank">'.$urlwithroot.'/dav/fileserver.php</a>';
$message.=img_picto('','object_globe.png').' '.$langs->trans("WebDavServer",'WebDAV',$url);
$message.='<br>';
print $message;

/*$message =$langs->trans("AgendaUrlOptions1",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptions3",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptionsNotAdmin",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptions4",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptionsProject",$user->login,$user->login).'<br>';
$message.=$langs->trans("AgendaUrlOptionsNotAutoEvent",'systemauto','systemauto').'<br>';

print info_admin($message);
*/

/*
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
*/

llxFooter();
$db->close();
