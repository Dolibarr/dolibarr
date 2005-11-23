<?PHP
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
 *
 * $Id$
 * $Source$
 *
 *
 * Generation des graphiques des données hébdomadaire
 * Ce script doit-être exécuté au minimum une fois par semaine
 *
 *
 */
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/distributeurs/distributeur.po.month.class.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/lignes/commandes.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/lignes/commandes.week.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/lignes/resiliation.week.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/lignes/rejet.week.class.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/clients/clientsmoyenne.week.class.php");

$error = 0;

$img_root = DOL_DATA_ROOT."/graph/telephonie/";

/*****
 *
 *
 *
 */
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/commerciaux/commerciaux.po.class.php");
$file = $img_root . "commerciaux/po.mensuel.png";

$graph = new GraphCommerciauxPO($db, $file);
$graph->width = 400;
$graph->GraphMakeGraph();

/***********************************************************************/
/*
/* Contrats
/*
/***********************************************************************/

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/contrats/modereglement.class.php");

$file = $img_root . "contrats/modereglement.png";
if ($verbose) print "Graph : Contrats Reglement $file\n";
$graph = new GraphContratModeReglement($db, $file);
$graph->GraphMakeGraph();

/***********************************************************************/
/*
/* Lignes actives
/*
/***********************************************************************/

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/lignes/actives.class.php");

$file = $img_root . "lignes/lignes.actives.png";
if ($verbose) print "Graph : Lignes actives$file\n";
$graph = new GraphLignesActives($db, $file);
$graph->GraphMakeGraph();

$file = $img_root . "lignes/lignes.commandees.png";
if ($verbose) print "Graph : Lignes actives$file\n";
$graph = new GraphLignesCommandees($db, $file);
$graph->GraphMakeGraph();

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/lignes/statut.class.php");

$file = $img_root . "lignes/lignes.statut.png";
if ($verbose) print "Graph : Lignes statut $file\n";
$graph = new GraphLignesStatut($db, $file);
$graph->GraphMakeGraph();

/***********************************************************************/
/*
/* Lignes commandes
/*
/***********************************************************************/

require_once DOL_DOCUMENT_ROOT."/telephonie/stats/clients/clients.week.class.php";
require_once DOL_DOCUMENT_ROOT."/telephonie/stats/clients/clients.month.class.php";

$file = $img_root . "lignes/commandes.mensuels.png";
if ($verbose) print "Graph : Lignes commandes$file\n";
$graph = new GraphLignesCommandes($db, $file);
$graph->width = 400;
$graph->GraphMakeGraph();

$file = $img_root . "lignes/commandes.hebdomadaire.png";
if ($verbose) print "Graph : Lignes commandes$file\n";
$graph = new GraphLignesCommandesWeek($db, $file);
$graph->width = 400;
$graph->GraphMakeGraph();

$file = $img_root . "commercials/clientsmoyenne.hebdomadaire.png";
if ($verbose) print "Graph : Clients Moyenne $file\n";
$graph = new GraphClientsMoyenneWeek($db, $file);
$graph->width = 400;
$graph->GraphMakeGraph();

$file = $img_root . "commercials/clients.hebdomadaire.png";
$graph = new GraphClientsWeek($db, $file);
$graph->width = 400;
$graph->GraphMakeGraph();

$file = $img_root . "commercials/clients.mensuel.png";
$graph = new GraphClientsMonth($db, $file);
$graph->width = 400;
$graph->GraphMakeGraph();

$sql = "SELECT distinct fk_commercial";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  
  while ($i < $num)
    {
      $row = $db->fetch_row();	
      
      $file = $img_root . "commercials/".$row[0]."/lignes.commandes.mensuels.png";
      if ($verbose) print "Graph : Lignes commandes$file\n";
      $graph = new GraphLignesCommandes($db, $file);
      $graph->width = 400;
      $graph->GraphMakeGraph($row[0]);
      
      $file = $img_root . "commercials/".$row[0]."/lignes.commandes.hebdomadaire.png";
      if ($verbose) print "Graph : Lignes commandes$file\n";
      $graph = new GraphLignesCommandesWeek($db, $file);
      $graph->width = 400;
      $graph->GraphMakeGraph($row[0]);

      $file = $img_root . "commercials/".$row[0]."/clients.hebdomadaire.png";
      if ($verbose) print "Graph : Lignes commandes$file\n";
      $graph = new GraphClientsWeek($db, $file);
      $graph->width = 400;
      $graph->commercial = $row[0];
       $graph->GraphMakeGraph();

       $file = $img_root . "commercials/".$row[0]."/clientsmoyenne.hebdomadaire.png";
       if ($verbose) print "Graph : Moyenne nouveaux clients $file\n";
       $graph = new GraphClientsMoyenneWeek($db, $file);
       $graph->width = 400;
       $graph->GraphMakeGraph($row[0]);

       $i++;
     }
 }

/***********************************************************************/
/*
/* Prise ordre des distributeur
/*
/***********************************************************************/
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/contrats/modereglement.class.php");

$sql = "SELECT distinct p.fk_distributeur, d.nom";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre as p";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_distributeur as d";
$sql .= " WHERE d.rowid = p.fk_distributeur";

$resql = $db->query($sql);

if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  while ($i < $num)
    {
      $row = $db->fetch_row($resql);

      $file = $img_root . "distributeurs/".$row[0]."/po.month.png";

      $graph = new GraphDistributeurPoMensuel($db, $file);
      $graph->width = 500;
      $graph->GraphMakeGraph($row[0], $row[1]);
      $i++;
    }
}



/*
 *
 */

$sql = "SELECT rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_distributeur";
$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  while ($i < $num)
    {
      $row = $db->fetch_row($resql);	
      $file = $img_root . "distributeurs/".$row[0]."/clients.hebdomadaire.png";
      if ($verbose) print "Graph : Lignes commandes$file\n";
      $graph = new GraphClientsWeek($db, $file);
      $graph->width = 500;
      $graph->distributeur = $row[0];
      $graph->GraphMakeGraph();
      $i++;
    }
}

/*****
 *
 *
 *
 */
$file = $img_root . "lignes/resiliations.hebdomadaire.png";

$graph = new GraphLignesResiliationWeek($db, $file);
$graph->width = 400;
$graph->GraphMakeGraph();
/*****
 *
 *
 *
 */
$file = $img_root . "lignes/rejets.hebdomadaire.png";

$graph = new GraphLignesRejetWeek($db, $file);
$graph->width = 400;
$graph->GraphMakeGraph();

?>
