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
 * Generation des graphiques
 *
 *
 *
 */
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie-tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/bar.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/camenbert.class.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/lignes/actives.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/lignes/commandes.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/lignes/commandes.week.class.php");

$error = 0;

/*
 * Création des répertoires
 *
 */
$dirs[0] = DOL_DATA_ROOT."/graph/";
$dirs[1] = DOL_DATA_ROOT."/graph/telephonie/";
$dirs[2] = DOL_DATA_ROOT."/graph/telephonie/communications/";
$dirs[3] = DOL_DATA_ROOT."/graph/telephonie/factures/";
$dirs[4] = DOL_DATA_ROOT."/graph/telephonie/ca/";
$dirs[5] = DOL_DATA_ROOT."/graph/telephonie/client/";
$dirs[6] = DOL_DATA_ROOT."/graph/telephonie/lignes/";
$dirs[7] = DOL_DATA_ROOT."/graph/telephonie/commercials/";

$img_root = DOL_DATA_ROOT."/graph/telephonie/";

$numdir = sizeof($dirs);

$sql = "SELECT distinct fk_commercial";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  
  while ($i < $num)
    {
      $row = $db->fetch_row();	
      
      $dirs[($numdir + $i)] = DOL_DATA_ROOT."/graph/telephonie/commercials/".$row[0];
      
      $i++;
    }
}


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
/***********************************************************************/
/*
/* Lignes actives
/*
/***********************************************************************/

$file = $img_root . "lignes/lignes.actives.png";
print "Graph : Lignes actives$file\n";
$graph = new GraphLignesActives($db, $file);
$graph->GraphMakeGraph();

/***********************************************************************/
/*
/* Lignes commandes
/*
/***********************************************************************/

$file = $img_root . "lignes/commandes.mensuels.png";
print "Graph : Lignes commandes$file\n";
$graph = new GraphLignesCommandes($db, $file);
$graph->width = 400;
$graph->GraphMakeGraph();

$file = $img_root . "lignes/commandes.hebdomadaire.png";
print "Graph : Lignes commandes$file\n";
$graph = new GraphLignesCommandesWeek($db, $file);
$graph->width = 400;
$graph->GraphMakeGraph();


$sql = "SELECT distinct fk_commercial";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  while ($i < $num)
    {
      $row = $db->fetch_row();	
      
      $file = $img_root . "commercials/".$row[0]."/lignes.commandes.mensuels.png";
      print "Graph : Lignes commandes$file\n";
      $graph = new GraphLignesCommandes($db, $file);
      $graph->width = 400;
      $graph->GraphMakeGraph($row[0]);
      
      $file = $img_root . "commercials/".$row[0]."/lignes.commandes.hebdomadaire.png";
      print "Graph : Lignes commandes$file\n";
      $graph = new GraphLignesCommandesWeek($db, $file);
      $graph->width = 400;
      $graph->GraphMakeGraph($row[0]);


      $i++;
    }
}
?>
