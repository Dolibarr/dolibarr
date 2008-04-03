<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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

 
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/tva/tva.class.php");
require_once(DOL_DOCUMENT_ROOT."/chargesociales.class.php");


if (!$user->rights->compta->resultat->lire)
  accessforbidden();

/*
*	Views
*/
llxHeader();

$year=$_GET["year"];
$month=$_GET["month"];
if (! $year) { $year = strftime("%Y", time()); }


/* Le compte de résultat est un document officiel requis par l'administration selon le status ou activité */

print_titre("Compte de résultat".($year?" année $year":""));

print '<br>';

print "Cet état n'est pas disponible.";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
