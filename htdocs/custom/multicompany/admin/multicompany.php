<?php
/* Copyright (C) 2009-2018	Regis Houssin	<regis.houssin@inodbox.com>
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
 *	\file       multicompany/admin/multicompany.php
 *	\ingroup    multicompany
 *	\brief      Page d'administration/configuration du module Multi-societe
 */

$res=@include("../../main.inc.php");						// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");			// For "custom" directory

dol_include_once('/multicompany/class/actions_multicompany.class.php', 'ActionsMulticompany');
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';

$langs->loadLangs(array('admin', 'languages', 'multicompany@multicompany'));

if (! $user->admin || $user->entity) {
	accessforbidden();
}

$action=GETPOST('action', 'alpha');

$object = new ActionsMulticompany($db);

$form=new Form($db);
$formadmin=new FormAdmin($db);
$formcompany=new FormCompany($db);

/*
 * Actions
 */

$object->doAdminActions($action);

//$test = new DaoMulticompany($db);
//$test->deleteEntityRecords(4);

/*
 * View
 */

$extrajs='';
$extracss='';

if (empty($action) || $action == "update" || $action == "add") {
	$extrajs = array(
		'/multicompany/inc/datatables/js/jquery.dataTables.min.js',
		//'/multicompany/inc/datatables/responsive/js/dataTables.responsive.min.js',
		'/multicompany/inc/datatables/buttons/js/dataTables.buttons.min.js',
		'/multicompany/inc/datatables/buttons/js/buttons.colVis.min.js',
		'/multicompany/inc/datatables/buttons/js/buttons.html5.min.js'
	);
	$extracss = array(
		'/multicompany/inc/datatables/css/jquery.dataTables.min.css',
		//'/multicompany/inc/datatables/responsive/css/responsive.dataTables.min.css',
		'/multicompany/inc/datatables/buttons/css/buttons.dataTables.min.css'
	);
} else if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
	$extrajs = array(
		'/multicompany/inc/multiselect/js/multiselect.min.js',
		//'/multicompany/inc/multiselect/js/multiselect.js'
	);
	$extracss = array(
		'/multicompany/inc/multiselect/css/bootstrap-iso.min.css'
	);
}

$help_url='EN:Module_MultiCompany|FR:Module_MultiSoci&eacute;t&eacute;';
llxHeader('', $langs->trans("MultiCompanySetup"), $help_url, '', '', '', $extrajs, $extracss);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MultiCompanySetup"), $linkback, 'multicompany@multicompany', 0, 'multicompany_title');

$head = multicompany_prepare_head();
dol_fiche_head($head, 'entities', $object->getTitle($action), -1);

$level = checkMultiCompanyVersion();
if ($level === 1 || $level === -1)
{
	$text = $langs->trans("MultiCompanyIsOlderThanDolibarr");
	if ($level === -1) $text = $langs->trans("DolibarrIsOlderThanMulticompany");

	print '<div class="multicompany_checker">';
	dol_htmloutput_mesg($text, '', 'warning', 1);
	print '</div>';

}

// Assign template values
$object->assign_values($action);

// Isolate Boostrap for avoid conflicts
if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) && ! empty($action) && $action != "update" && $action != "add") {
	print '<div class="bootstrap-iso">';
}

// Show the template
$object->display();

// Isolate Boostrap for avoid conflicts
if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) && ! empty($action) && $action != "update" && $action != "add") {
	print '</div>';
}

// Card end
dol_fiche_end();
// Footer
llxFooter();
// Close database handler
$db->close();
