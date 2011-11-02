<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/user/info.php
 *      \ingroup    core
 *		\brief      Page des informations d'un utilisateur
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php');
require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");

$langs->load("users");

// Security check
$id = GETPOST('id','int');
$fuser = new User($db);
$fuser->fetch($id);

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2 = (($socid && $user->rights->user->self->creer)?'':'user');
if ($user->id == $id)	// A user can always read its own card
{
	$feature2='';
}
$result = restrictedArea($user, 'user', $id, '', $feature2);

// If user is not user read and no permission to read other users, we stop
if (($fuser->id != $user->id) && (! $user->rights->user->user->lire))
  accessforbidden();



/*
 * View
 */

llxHeader();

$fuser->info($id);

$head = user_prepare_head($fuser);

$title = $langs->trans("User");
dol_fiche_head($head, 'info', $title, 0, 'user');


print '<table width="100%"><tr><td>';
dol_print_object_info($fuser);
print '</td></tr></table>';

print '</div>';

$db->close();

llxFooter();
?>
