<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010 Regis Houssin        <regis.houssin@inodbox.com>
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
 *      \file       htdocs/comm/mailing/info.php
 *      \ingroup    mailing
 *		\brief      Page with log information for emailing
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/emailing.lib.php';

$id = GETPOST('id', 'int');

// Load translation files required by the page
$langs->load("mails");

// Security check
if (!$user->rights->mailing->lire || $user->socid > 0)
accessforbidden();



/*
 * View
 */

llxHeader('', $langs->trans("Mailing"), 'EN:Module_EMailing|FR:Module_Mailing|ES:M&oacute;dulo_Mailing');

$form = new Form($db);

$object = new Mailing($db);

if ($object->fetch($id) >= 0)
{
	$head = emailing_prepare_head($object);

	print dol_get_fiche_head($head, 'info', $langs->trans("Mailing"), -1, 'email');

	$linkback = '<a href="'.DOL_URL_ROOT.'/comm/mailing/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlright = '';
	$nbtry = $nbok = 0;
	if ($object->statut == 2 || $object->statut == 3)
	{
		$nbtry = $object->countNbOfTargets('alreadysent');
		$nbko  = $object->countNbOfTargets('alreadysentko');

		$morehtmlright .= ' ('.$nbtry.'/'.$object->nbemail;
		if ($nbko) $morehtmlright .= ' - '.$nbko.' '.$langs->trans("Error");
		$morehtmlright .= ') &nbsp; ';
	}

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', '', '', 0, '', $morehtmlright);

	print '<div class="underbanner clearboth"></div><br>';

	//print '<table width="100%"><tr><td>';
	$object->user_creation = $object->user_creat;
	$object->date_creation = $object->date_creat;
	$object->user_validation = $object->user_valid;
	$object->date_validation = $object->date_valid;
	dol_print_object_info($object, 0);
	//print '</td></tr></table>';


	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
