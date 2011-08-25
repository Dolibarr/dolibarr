<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/comm/action/info.php
 *      \ingroup    agenda
 *		\brief      Page des informations d'une action
 *		\version    $Id: info.php,v 1.20 2011/07/31 22:23:21 eldy Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/agenda.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/cactioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");

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

$help_url='EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda';
llxHeader('',$langs->trans("Agenda"),$help_url);

$act = new ActionComm($db);
$act->fetch($_GET["id"]);
$act->info($_GET["id"]);

$head=actions_prepare_head($act);
dol_fiche_head($head, 'info', $langs->trans("Action"),0,'action');


print '<table width="100%"><tr><td>';
dol_print_object_info($act);
print '</td></tr></table>';

print '</div>';

$db->close();

llxFooter('$Date: 2011/07/31 22:23:21 $ - $Revision: 1.20 $');
?>
