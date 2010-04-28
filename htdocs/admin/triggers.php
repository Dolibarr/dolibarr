<?php
/* Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/admin/triggers.php
 *       \brief      Page de configuration et activation des triggers
 *       \version    $Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/interfaces.class.php");

if (!$user->admin)
    accessforbidden();

/*
 * Action
 */



/*
 * View
 */

llxHeader("","");

$html = new Form($db);

print_fiche_titre($langs->trans("TriggersAvailable"),'','setup');

print $langs->trans("TriggersDesc")."<br>";
print "<br>\n";

$template_dir = DOL_DOCUMENT_ROOT.'/core/tpl/';

$interfaces = new Interfaces($db);
$triggers = $interfaces->getTriggersList(0);

include($template_dir.'triggers.tpl.php');

llxFooter('$Date$ - $Revision$');
?>
