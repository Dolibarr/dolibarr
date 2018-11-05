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

// Load translation files required by the page
$langs->loadLangs(array("admin","other","agenda"));

if (!$user->admin)
    accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$arrayofparameters=array(
	'DAV_ALLOW_PUBLIC_DIR'=>array('css'=>'minwidth200', 'enabled'=>1),
	'DAV_ALLOW_ECM_DIR'=>array('css'=>'minwidth200', 'enabled'=>$conf->ecm->enabled)
);


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';



/*
 * View
 */


llxHeader('', $langs->trans("DAVSetup"), $wikihelp);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("DAVSetup"),$linkback,'title_setup');


print '<form name="agendasetupform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

$head=dav_admin_prepare_head();

dol_fiche_head($head, 'webdav', '', -1, 'action');


if ($action == 'edit')
{
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	foreach($arrayofparameters as $key => $val)
	{
		if (isset($val['enabled']) && empty($val['enabled'])) continue;

		print '<tr class="oddeven"><td>';
		print $form->textwithpicto($langs->trans($key), $langs->trans($key.'Tooltip'));
		print '</td><td><input name="'.$key.'"  class="flat '.(empty($val['css'])?'minwidth200':$val['css']).'" value="' . $conf->global->$key . '"></td></tr>';
	}

	print '</table>';

	print '<br><div class="center">';
	print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
	print '</div>';

	print '</form>';
	print '<br>';
}
else
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	foreach($arrayofparameters as $key => $val)
	{
		print '<tr class="oddeven"><td>';
		print $form->textwithpicto($langs->trans($key),$langs->trans($key.'Tooltip'));
		print '</td><td>' . $conf->global->$key . '</td></tr>';
	}

	print '</table>';

	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
	print '</div>';
}


dol_fiche_end();

/*print '<div class="center">';
print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</div>";
*/
print "</form>\n";


clearstatcache();

print $langs->trans("WebDAVSetupDesc")."<br>\n";
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

// End of page
llxFooter();
$db->close();
