<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/comm/mailing/info.php
 *      \ingroup    mailing
 *		\brief      Page with log information for emailing
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT .'/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/emailing.lib.php';

$langs->load("mails");

// Security check
if (! $user->rights->mailing->lire || $user->societe_id > 0)
accessforbidden();



/*
 * View
 */

llxHeader('',$langs->trans("Mailing"),'EN:Module_EMailing|FR:Module_Mailing|ES:M&oacute;dulo_Mailing');

$form = new Form($db);

$mil = new Mailing($db);

if ($mil->fetch($_REQUEST["id"]) >= 0)
{
	$head = emailing_prepare_head($mil);

	dol_fiche_head($head, 'info', $langs->trans("Mailing"), 0, 'email');


	print '<table width="100%"><tr><td>';
	$mil->user_creation=$mil->user_creat;
	$mil->date_creation=$mil->date_creat;
	$mil->user_validation=$mil->user_valid;
	$mil->date_validation=$mil->date_valid;
	dol_print_object_info($mil);
	print '</td></tr></table>';

	print '</div>';
}

$db->close();

llxFooter();
?>
