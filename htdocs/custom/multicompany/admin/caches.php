<?php
/* Copyright (C) 2014-2018 Regis Houssin  <regis.houssin@inodbox.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * 		\file       /multicompany/admin/options.php
 *		\ingroup    multicompany
 *		\brief      Page to setup options for Multicompany module
 */


$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

dol_include_once('/multicompany/lib/multicompany.lib.php');
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";

$langs->loadLangs(array('admin', 'multicompany@multicompany'));

// Security check
if (empty($user->admin) || ! empty($user->entity)) {
	accessforbidden();
}

$action	= GETPOST('action','alpha');


/*
 * 	Action
 */

if ($action == 'setvalue')
{
	$result=dolibarr_set_const($db, "MULTICOMPANY_MEMCACHED_SERVER",GETPOST('MULTICOMPANY_MEMCACHED_SERVER', 'alpha'),'chaine',0,'',0);

	if ($result >= 0)
	{
		setEventMessage($langs->trans("SetupSaved"));

		// Force new value
		$conf->global->MULTICOMPANY_MEMCACHED_SERVER=GETPOST('MULTICOMPANY_MEMCACHED_SERVER', 'alpha');
	}
	else
	{
		dol_print_error($db);
	}
}


/*
 *	View
 */

$form=new Form($db);

$arrayofjs=array(
	'/multicompany/core/js/lib_head.js'
);

$help_url='EN:Module_MultiCompany|FR:Module_MultiSoci&eacute;t&eacute;';
llxHeader('', $langs->trans("MultiCompanySetup"), $help_url, '', '', '', $arrayofjs);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MultiCompanySetup"),$linkback,'multicompany@multicompany',0,'multicompany_title');

$head=multicompany_prepare_head();
dol_fiche_head($head, 'caches', $langs->trans("ModuleSetup"), -1);

print '<div>'.info_admin($langs->trans("MulticompanyCacheSystemInfo"), 0, 0, '1', 'clearboth').'</div>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

dol_include_once('/multicompany/admin/tpl/caches.tpl.php');

print '</div>';

// Boutons actions
print '<div class="tabsAction">';
print '<input type="submit" id="save" name="save" class="butAction linkobject" value="'.$langs->trans("Save").'" />';
print '</form>'."\n";
print '</div>';

llxFooter();
$db->close();
