<?php
/* Copyright (C) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *      \file       htdocs/adherents/info.php
 *      \ingroup    member
 *		\brief      Page des informations d'un adherent
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("members");
$langs->load("users");

$id=(GETPOST('id','int') ? GETPOST('id','int') : GETPOST('rowid','int'));

// Security check
$result=restrictedArea($user,'adherent',$id);


/*
 * View
 */

llxHeader('',$langs->trans("Member"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$adh = new Adherent($db);
$adh->fetch($id);
$adh->info($id);

$head = member_prepare_head($adh);

dol_fiche_head($head, 'info', $langs->trans("Member"), 0, 'user');


print '<table width="100%"><tr><td>';
dol_print_object_info($adh);
print '</td></tr></table>';

print '</div>';


llxFooter();
$db->close();
?>
