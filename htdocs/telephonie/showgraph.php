<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 *
 * Affichage des graph dédiés téléphonie
 *
 */

require_once "../main.inc.php";

$original_graph = DOL_DATA_ROOT.'/graph/telephonie/'.urldecode($_GET["graph"]);

$graphname = basename ($original_graph);

header('Content-type: image/png');

if ($fh = @fopen($original_graph, "rb"))
{  
  fpassthru($fh);
  fclose($fh);
}

?>
