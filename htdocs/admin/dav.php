<?php
/* Copyright (C) 2008-2019 	Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
$langs->loadLangs(array("admin", "other", "agenda"));

if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');



$arrayofparameters = array(
	'DAV_RESTICT_ON_IP'=>array('css'=>'minwidth200', 'enabled'=>1),
	'DAV_ALLOW_PRIVATE_DIR'=>array('css'=>'minwidth200', 'enabled'=>2),
	'DAV_ALLOW_PUBLIC_DIR'=>array('css'=>'minwidth200', 'enabled'=>1),
	'DAV_ALLOW_ECM_DIR'=>array('css'=>'minwidth200', 'enabled'=>$conf->ecm->enabled)
);

// To fix when dire does not exists
dol_mkdir($conf->dav->dir_output.'/temp');
dol_mkdir($conf->dav->dir_output.'/public');
dol_mkdir($conf->dav->dir_output.'/private');


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';



/*
 * View
 */

$help_url = 'EN:Module_DAV';

llxHeader('', $langs->trans("DAVSetup"), $help_url);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("DAVSetup"), $linkback, 'title_setup');


print '<form name="agendasetupform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';

$head = dav_admin_prepare_head();

print dol_get_fiche_head($head, 'webdav', '', -1, 'action');

if ($action == 'edit') {
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	foreach ($arrayofparameters as $key => $val) {
		if (isset($val['enabled']) && empty($val['enabled'])) {
			continue;
		}

		print '<tr class="oddeven"><td>';
		$tooltiphelp = (($langs->trans($key.'Tooltip') != $key.'Tooltip') ? $langs->trans($key.'Tooltip') : '');
		$label = $langs->trans($key);
		if ($key == 'DAV_RESTICT_ON_IP') {
			$label = $langs->trans("RESTRICT_ON_IP");
			$label .= ' '.$langs->trans("Example").': '.$langs->trans("IPListExample");
		}
		print $form->textwithpicto($label, $tooltiphelp);
		print '</td><td>';
		if ($key == 'DAV_ALLOW_PRIVATE_DIR') {
			print $langs->trans("AlwaysActive");
		} elseif ($key == 'DAV_ALLOW_PUBLIC_DIR' || $key == 'DAV_ALLOW_ECM_DIR') {
			print $form->selectyesno($key, $conf->global->$key, 1);
		} else {
			print '<input name="'.$key.'"  class="flat '.(empty($val['css']) ? 'minwidth200' : $val['css']).'" value="'.getDolGlobalString($key).'">';
		}
		print '</td></tr>';
	}

	print '</table>';

	print '<br><div class="center">';
	print '<input class="button button-save" type="submit" value="'.$langs->trans("Save").'">';
	print '</div>';

	print '</form>';
	print '<br>';
} else {
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	foreach ($arrayofparameters as $key => $val) {
		print '<tr class="oddeven"><td class="titlefieldmiddle">';
		$tooltiphelp = (($langs->trans($key.'Tooltip') != $key.'Tooltip') ? $langs->trans($key.'Tooltip') : '');
		$label = $langs->trans($key);
		if ($key == 'DAV_RESTICT_ON_IP') {
			$label = $langs->trans("RESTRICT_ON_IP");
			$label .= ' <span class="opacitymedium">'.$langs->trans("Example").': '.$langs->trans("IPListExample").'</span>';
		}
		print $form->textwithpicto($label, $tooltiphelp);
		print '</td><td>';
		if ($key == 'DAV_ALLOW_PRIVATE_DIR') {
			print $langs->trans("AlwaysActive");
		} elseif ($key == 'DAV_ALLOW_PUBLIC_DIR' || $key == 'DAV_ALLOW_ECM_DIR') {
			print yn($conf->global->$key);
		} else {
			print $conf->global->$key;
		}
		print '</td></tr>';
	}

	print '</table>';

	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
	print '</div>';
}


print dol_get_fiche_end();

/*print '<div class="center">';
print '<input type="submit" name="save" class="button button-save" value="'.$langs->trans("Save").'">';
print "</div>";
*/
print "</form>\n";


clearstatcache();

print '<span class="opacitymedium">'.$langs->trans("WebDAVSetupDesc")."</span><br>\n";
print "<br>";


// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current


// Show message
$message = '';
$url = '<a href="'.$urlwithroot.'/dav/fileserver.php" target="_blank" rel="noopener noreferrer">'.$urlwithroot.'/dav/fileserver.php</a>';

$message .= img_picto('', 'globe').' '.str_replace('{url}', $url, $langs->trans("WebDavServer", 'WebDAV', ''));
$message .= '<div class="urllink"><input type="text" id="webdavpublicurl" class="quatrevingtpercent" value="'.$urlwithroot.'/dav/fileserver.php">';
$message .= '<a href="'.$urlwithroot.'/dav/fileserver.php" target="_blank" rel="noopener noreferrer">';
$message .= ' '.img_picto('', 'globe');
$message .= '</a>';
$message .= '</div>';
$message .= ajax_autoselect('webdavpublicurl');

$message .= '<br>';
if (!empty($conf->global->DAV_ALLOW_PUBLIC_DIR)) {
	$urlEntity = (!empty($conf->multicompany->enabled) ? '?entity='.$conf->entity : '');
	$url = '<a href="'.$urlwithroot.'/dav/fileserver.php/public/'.$urlEntity.'" target="_blank" rel="noopener noreferrer">'.$urlwithroot.'/dav/fileserver.php/public/'.$urlEntity.'</a>';

	$message .= img_picto('', 'globe').' '.str_replace('{url}', $url, $langs->trans("WebDavServer", 'WebDAV public', ''));
	$message .= '<div class="urllink"><input type="text" id="webdavurl" class="quatrevingtpercent" value="'.$urlwithroot.'/dav/fileserver.php/public/'.$urlEntity.'">';
	$message .= '<a href="'.$urlwithroot.'/dav/fileserver.php/public/'.$urlEntity.'" target="_blank" rel="noopener noreferrer">';
	$message .= ' '.img_picto('', 'globe');
	$message .= '</a>';
	$message .= '</div>';
	$message .= ajax_autoselect('webdavurl');
	$message .= '<br>';
}
print $message;

print '<br>';

require_once DOL_DOCUMENT_ROOT.'/includes/sabre/autoload.php';
$version = Sabre\DAV\Version::VERSION;
print '<span class="opacitymedium">'.$langs->trans("BaseOnSabeDavVersion").' : '.$version.'</span>';


// End of page
llxFooter();
$db->close();
