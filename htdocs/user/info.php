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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */

/**
        \file       htdocs/user/info.php
        \ingroup    core
		\brief      Page des informations d'un utilisateur
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/usergroups.lib.php');
require_once(DOL_DOCUMENT_ROOT."/user.class.php");

$langs->load("user");

// Sécurité accés client et commerciaux
$id = isset($_GET["id"])?$_GET["id"]:'';

$fuser = new User($db);
$fuser->id = $id;
$fuser->fetch();

// If user is not user read and no permission to read other users, we stop
if (($fuser->id != $user->id) && (! $user->rights->user->user->lire))
  accessforbidden();

  

/*
 * Visualisation de la fiche
 *
 */

llxHeader();

$fuser->info($_GET["id"]);

$head = user_prepare_head($fuser);

dolibarr_fiche_head($head, 'info', $langs->trans("User"));


print '<table width="100%"><tr><td>';
dolibarr_print_object_info($fuser);
print '</td></tr></table>';

print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
