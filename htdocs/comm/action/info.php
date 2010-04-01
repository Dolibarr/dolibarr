<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *      \file       htdocs/comm/action/info.php
 *      \ingroup    agenda
 *		\brief      Page des informations d'une action
 *		\version    $Id$
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/agenda.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/cactioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/actioncomm.class.php");

$langs->load("commercial");

// Security check
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}



/*
 * View
 */

llxHeader();

$act = new ActionComm($db);
$act->fetch($_GET["id"]);
$act->info($_GET["id"]);

$head=actions_prepare_head();
dol_fiche_head($head, 'info', $langs->trans("Action"),0,'task');


print '<table width="100%"><tr><td>';
dol_print_object_info($act);
print '</td></tr></table>';

print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
