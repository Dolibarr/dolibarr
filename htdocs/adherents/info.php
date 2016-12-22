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

$form = new Form($db);

llxHeader('',$langs->trans("Member"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$object = new Adherent($db);
$object->fetch($id);
$object->info($id);

$head = member_prepare_head($object);

dol_fiche_head($head, 'info', $langs->trans("Member"), 0, 'user');


$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'rowid', $linkback);

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';

print '<br>';
dol_print_object_info($object);

print '</div>';

dol_fiche_end();


llxFooter();
$db->close();
