<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * $Id: index.php,v 1.10 2011/08/03 00:46:34 eldy Exp $
 */

require("../../main.inc.php");

// Security check
if (!$user->admin && $user->societe_id > 0)
  accessforbidden();


/*
 * View
 */

llxHeader();

print_fiche_titre("Payments");



$db->close();

llxFooter('$Date: 2011/08/03 00:46:34 $ - $Revision: 1.10 $');
?>
