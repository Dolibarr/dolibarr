<?PHP
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
 *
 * $Id$
 * $Source$
 *
 */

/**
* Gestion d'une proposition commerciale
* @package propale
*/

require("./pre.inc.php");

$user->getrights('propale');
if (!$user->rights->propale->lire)
  accessforbidden();

require("../../propal.class.php");
/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/



llxHeader();

/******************************************************************************/
/*                   Fin des  Actions                                         */
/******************************************************************************/
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
  $societe->fetch($propal->soc_id);

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
  print_titre("Simulation des remises sur le prix HT");
  print '<table class="border" cellspacing="0" cellpadding="2" width="100%">';
  $j=1;
  for ($i = 1 ; $i < 100 ; $i= $i+3)
    {

      $ht1 = $propal->total_ht - ($propal->total_ht * $j / 100 );
      $ttc1 = $propal->total_ttc - ($propal->total_ttc * $j / 100 );
      $ht2 = $propal->total_ht - ($propal->total_ht * ($j+33) / 100 );
      $ttc2 = $propal->total_ttc - ($propal->total_ttc * ($j+33) / 100 );
      $ht3 = $propal->total_ht - ($propal->total_ht * ($j+66) / 100 );
      $ttc3 = $propal->total_ttc - ($propal->total_ttc * ($j+66) / 100 );

      print "<tr><td>$j %</td><td>".price($ht1)." <small>(".price($ttc1).")</small></td>";
      print "<td>".($j+33)." %</td><td>".price($ht2)." <small>(".price($ttc2).")</small></td>";
      print "<td>".($j+66)." %</td><td>".price($ht3)." <small>(".price($ttc3).")</small></td></tr>";

      $j++;


    }
  print "</table>";

}
$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
