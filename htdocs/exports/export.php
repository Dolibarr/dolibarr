<?php
/* Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Source$
 */

/**
        \file       htdocs/exports/export.php
        \ingroup    core
        \brief      Page d'edition d'un export
        \version    $Revision$
*/
 
require("./pre.inc.php");

$langs->load("commercial");
$langs->load("orders");

$user->getrights();

if (! $user->societe_id == 0)
  accessforbidden();





 
llxHeader('',$langs->trans("NewExport"));

print_fiche_titre($langs->trans("NewExport"));

print '<table class="notopnoleftnoright" width="100%">';



print '</table>';

$db->close();


llxFooter('$Date$ - $Revision$');

?>
