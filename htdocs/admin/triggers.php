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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/admin/triggers.php
 *       \brief      Page de configuration et activation des triggers
 *       \version    $Id: triggers.php,v 1.27 2011/07/31 22:23:21 eldy Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/interfaces.class.php");

$langs->load("admin");

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

llxFooter('$Date: 2011/07/31 22:23:21 $ - $Revision: 1.27 $');
?>
