<?php
/* Copyright (C) 2004-2009	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010		Juanjo Menent		<jmenent@2byte.es>
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
 *      \file       htdocs/societe/info.php
 *      \ingroup    societe
 *      \brief      Page des informations d'une societe
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$langs->load("companies");
$langs->load("other");
if (! empty($conf->notification->enabled)) $langs->load("mails");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '&societe');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('infothirdparty'));

$object = new Societe($db);


/*
 *	Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');



/*
 *	View
 */

$form=new Form($b);

$title=$langs->trans("ThirdParty");
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name.' - '.$langs->trans("Info");
$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$title,$help_url);

if ($socid > 0)
{
	$result = $object->fetch($socid);
	if (! $result)
	{
		$langs->load("errors");
		print $langs->trans("ErrorRecordNotFound");

		llxFooter();
		$db->close();

		exit;
	}

	$head = societe_prepare_head($object);

	dol_fiche_head($head, 'info', $langs->trans("ThirdParty"), 0, 'company');

	dol_banner_tab($object, 'socid', '', ($user->societe_id?0:1), 'rowid', 'nom');
	
	$object->info($socid);


	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';

	print '<br>';
	
	dol_print_object_info($object);

	print '</div>';
	
	dol_fiche_end();
}


llxFooter();

$db->close();
