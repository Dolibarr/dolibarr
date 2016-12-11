<?php
/* Copyright (C) 2016	Alexandre Spangaro	<aspangaro@zendsi.com>
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
 *      \ingroup    thirdparty
 *      \brief      Information page for thirdparty
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$langs->load("companies");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '&societe');

$object = new Societe($db);


/*
 *	Actions
 */

/*
 *	View
 */

$title = $langs->trans('Infos');
$helpurl = '';
llxHeader('', $title, $helpurl);

$form=new Form($b);

if ($socid)
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

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php">'.$langs->trans("BackToList").'</a>';

	dol_fiche_head($head, 'info', $langs->trans("ThirdParty"),0,'company');

	dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

    $object->info($socid);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';

	print '<br>';

	print dol_print_object_info($object, 1);

	print '</div>';

	dol_fiche_end();
}


llxFooter();

$db->close();
