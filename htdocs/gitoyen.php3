<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */
require("./pre.inc.php3");

llxHeader();

$qt_r = 2.75;
$pu_r = 17.53;
$total_r = $qt_r * $pu_r;

$qt_tr = 2.75; $pu_tr = 23; $total_tr = $qt_tr * $pu_tr;
$qt_bpr = 0.33333   ; $pu_bpr = 270; $total_bpr = $qt_bpr * $pu_bpr;


$qt_m = 2.75;
$pu_m = 17.53;
$total_m = $qt_m * $pu_m;

print "<table border=1>";
print "<tr><td></td><td>Quantité</td><td>PU</td><td>Total</td></tr>";

print "<tr><td>Rodolphe Interxion</td><td>$qt_r</td><td>$pu_r</td><td align=right>".price($total_r)."</td></tr>";
print "<tr><td>Rodolphe Telehouse</td><td>$qt_tr</td><td>$pu_tr</td><td align=right>".price($total_tr)."</td></tr>";
print "<tr><td>Rodolphe BP</td><td>$qt_bpr</td><td>$pu_bpr</td><td align=right>".price($total_bpr)."</td></tr>";

print "<tr><td></td><td></td><td></td><td align=right>".price($total_r+$total_tr+$total_bpr)."</td></tr>";

print "<tr><td>Mose</td><td>$qt_m</td><td>$pu_m</td><td>$total_m</td></tr>";
print "<tr><td></td><td></td><td></td><td>".($total_r+$total_m)."</td></tr>";

print "</table>";


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>










