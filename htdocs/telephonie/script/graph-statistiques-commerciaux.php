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
 * Generation des graphiques relatifs aux commerciaux
 *
 */
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie-tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/bar.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/camenbert.class.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/commerciaux/commercial.ca.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/commerciaux/commercial.gain.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/commerciaux/groupes/groupe.gain.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/commerciaux/groupes/groupe.ca.class.php");
$year = strftime("%Y",time());
$error = 0;

/*
 * Création des répertoires
 *
 */
$dirs[0] = DOL_DATA_ROOT."/graph/";
$dirs[1] = DOL_DATA_ROOT."/graph/telephonie/";
$dirs[2] = DOL_DATA_ROOT."/graph/telephonie/commercials/";
$dirs[2] = DOL_DATA_ROOT."/graph/telephonie/distributeurs/";

$img_root = DOL_DATA_ROOT."/graph/telephonie/";

if (is_array($dirs))
{
  foreach ($dirs as $key => $value)
    {
      $dir = $value;
      
      if (! file_exists($dir))
	{
	  umask(0);
	  if (! @mkdir($dir, 0755))
	    {
	      print  "Erreur: Le répertoire '$dir' n'existe pas et Dolibarr n'a pu le créer.";
	    }
	  else
	    {
	      print $dir ." créé\n";
	    }
	}	
    }
}

$sql = "SELECT distinct fk_commercial_sign";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  while ($i < $num)
    {
      $row = $db->fetch_row($resql);	
      
      /* Chiffre d'affaire mensuel */
            
      $file = $img_root . "commercials/".$row[0]."/ca.mensuel.png";
      if ($verbose) print "Graph : Lignes commandes$file\n";
      $graph = new GraphCommercialChiffreAffaire($db, $file);
      $graph->width = 400;
      $graph->GraphMakeGraph($row[0]);

      /* Gain */
            
      $file = $img_root . "commercials/".$row[0]."/gain.mensuel.png";
      if ($verbose) print "Graph : Lignes commandes$file\n";
      $graph = new GraphCommercialGain($db, $file);
      $graph->width = 400;
      $graph->GraphMakeGraph($row[0]);

      /*
       * Statut des lignes
       *
       */
      require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/lignes/statut.class.php");
      
      $file = $img_root . "commercials/".$row[0]."/lignes.statut.png";
      if ($verbose) print "Graph : Lignes statut $file\n";
      $graph = new GraphLignesStatut($db, $file);
      $graph->GraphMakeGraph($row[0]);
      
      $i++;
    }
}

/*
 * Groupes
 *
 */
$sql = "SELECT rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."usergroup as u";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  while ($i < $num)
    {
      $row = $db->fetch_row($resql);	
      
      /* Gain */
            
      $file = $img_root . "commerciaux/groupes/".$row[0]."/gain.mensuel.png";
      if ($verbose) print "Graph : Lignes commandes$file\n";
      $graph = new GraphGroupeGain($db, $file);
      $graph->width = 400;
      $graph->GraphMakeGraph($row[0]);

      /* Chiffre d'affaire */
            
      $file = $img_root . "commerciaux/groupes/".$row[0]."/ca.mensuel.png";
      if ($verbose) print "Graph : Lignes commandes$file\n";
      $graph = new GraphGroupeChiffreAffaire($db, $file);
      $graph->width = 400;
      $graph->GraphMakeGraph($row[0]);

      $i++;
    }
}

/*
 * Contrats
 *
 */
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/commerciaux/contrats.class.php");
      
$file = $img_root . "commercials/contrats-suivis.png";
if ($verbose) print "Graph : Commerciaux contrats $file\n";
$graph = new GraphCommerciauxContrats($db, $file);
$graph->GraphMakeGraph("suivi");

$file = $img_root . "commercials/contrats-signes.png";
if ($verbose) print "Graph : Commerciaux contrats $file\n";
$graph = new GraphCommerciauxContrats($db, $file);
$graph->GraphMakeGraph("signe");

/*
 * Prises d'ordres
 *
 */
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/commerciaux/commercial.po.class.php");

$sql = "SELECT date_format(datepo,'%Y%m'), sum(montant), fk_commercial, fk_distributeur";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre";
$sql .= " WHERE year(now()) = year(datepo)";
$sql .= " GROUP BY date_format(datepo,'%Y%m'), fk_commercial, fk_distributeur";
$sql .= " ORDER BY date_format(datepo,'%Y%m') ASC";

$resql = $db->query($sql);
if ($resql)
{
  $datas_comm = array();
  $labels_comm = array();
  
  $datas_dist = array();
  $labels_dist = array();

  while ( $row = $db->fetch_row($resql) )
    {
      $datas_comm[$row[2]][$row[0]] += $row[1];
      $datas_dist[$row[3]][$row[0]] += $row[1];
    }

  foreach($datas_comm as $comm => $value)
    {
      //print $comm."\n";

      $file = $img_root . "commercials/".$comm."/po.$year.mensuel.png";
      if ($verbose) print "Graph : Lignes commandes$file\n";
      $graph = new GraphCommercialPO($db, $file);
      $graph->width = 400;
      $graph->GraphMakeGraph($value);
    }
}

function _cdir($dir)
{
  if (! file_exists($dir))
    {
      umask(0);
      if (! @mkdir($dir, 0755))
	{
	  print  "Erreur: Le répertoire '$dir' n'existe pas et Dolibarr n'a pu le créer.";
	}
      else
	{
	  //print $dir ." créé\n";
	}
    }	
}

?>
