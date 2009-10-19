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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */

require("./pre.inc.php");

require("./AtomeFactureValidee.class.php");
require("./AtomePropaleValidee.class.php");

llxHeader();

print_fiche_titre("Rapports");



/*
 * Initialisation d'un atome
 *
 * Parametre
 * - id de connexion � la bdd
 * - p�riode 'year' ou 'month' (Reste � faire les hebdo)
 * - une date dans la p�riode voulue
 * -> retourne un objet
 *
 */
$x = new AtomeFactureValidee($db,'year', time());

/*
 * Lecture des donn�es
 * -> retourne un tableau
 */
$arr = $x->fetch();
/*
 * Cr�ation du graph
 * -> retounre le nom du fichier
 */
$img = $x->ShowGraph();
print $img."<br>";


//

$x = new AtomeFactureValidee($db,'year', mktime(12,0,0,1,12,2003));
$arr = $x->fetch('year');

$img = $x->ShowGraph();
print $img."<br>";
for ($i = 1 ; $i < 5; $i++)
{
  $x = new AtomeFactureValidee($db,'month', mktime(12,0,0,$i,12,2003));
  $x->periode = 'month';

  $arr = $x->fetch('month');


  $img = $x->ShowGraph();
  print $img."<br>";
}

//

$x = new AtomePropaleValidee($db,'year',mktime(12,0,0,1,12,2003));

$arr = $x->fetch();

var_dump($arr);

$img = $x->ShowGraph();
print $img."<br>";

$db->close();

llxFooter('$Date$ - $Revision$');
?>










