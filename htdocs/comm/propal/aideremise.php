<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/comm/propal/aideremise.php
        \ingroup    propale
        \brief      Page de simulation des remises
		\version	$Id$
*/

require("./pre.inc.php");
include_once(DOL_DOCUMENT_ROOT."/propal.class.php");

$propalid = isset($_GET["propalid"])?$_GET["propalid"]:'';

// Security cehck
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'propale', $propalid, 'propal');


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/




/******************************************************************************/
/*	View                                                                      */
/******************************************************************************/

llxHeader();

/*
 *
 * Mode fiche
 *
 *
 */
if ($_GET["propalid"])
{
  $html = new Form($db);

  $propal = new Propal($db);
  $propal->fetch($_GET["propalid"]);

  $societe = new Societe($db);
  $societe->fetch($propal->socid);

  $head[0][0] = DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id;
  $head[0][1] = "Proposition commerciale : $propal->ref";
  $h = 1;
  $a = 0;
  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/note.php?propalid='.$propal->id;
  $head[$h][1] = "Note";
  $h++;
  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/info.php?propalid='.$propal->id;
  $head[$h][1] = "Info";

  dolibarr_fiche_head($head, $a, $societe->nom);

  $price = $propal->price + $propal->remise;

  print_titre("Simulation des remises sur le prix HT : ".price($price));
  print '<table class="border" cellspacing="0" cellpadding="2" width="100%">';
  $j=1;
  for ($i = 1 ; $i < 100 ; $i= $i+3)
    {

      $ht1 = $price - ($price * $j / 100 );

      $ht2 = $price - ($price * ($j+33) / 100 );

      $ht3 = $price - ($price * ($j+66) / 100 );


      print "<tr><td>$j %</td><td>".price($ht1)."</td>";
      print "<td>".($j+33)." %</td><td>".price($ht2)."</td>";
      print "<td>".($j+66)." %</td><td>".price($ht3)."</td></tr>";

      $j++;


    }
  print "</table>";

}
$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
