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
 * Generation des graphiques relatifs aux distributeurs
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

$error = 0;
$year = strftime("%Y",time());
/*
 * Création des répertoires
 *
 */
$dirs[0] = DOL_DATA_ROOT."/graph/";
$dirs[1] = DOL_DATA_ROOT."/graph/telephonie/";
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
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/distributeurs/distributeur.gain.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/distributeurs/distributeur.commission.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/distributeurs/distributeur.resultat.class.php");
/*
 * Distributeurs
 *
 */
$sql = "SELECT distinct fk_distributeur";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_distributeur_commerciaux";

$resql = $db->query($sql);
if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      /* Gain */
      $dir = $img_root . "distributeurs/".$row[0]."/";
      _cdir($dir);
      $file = $dir."gain.mensuel.png";
      if ($verbose) print "Graph : gain distributeur $file\n";
      $graph = new GraphDistributeurGain($db, $file);
      $graph->width = 500;
      $graph->height = 260;
      $graph->GraphMakeGraph($row[0]);

      /* Commission */
      $dir = $img_root . "distributeurs/".$row[0]."/";
      _cdir($dir);
      $file = $dir."commission.mensuel.png";
      if ($verbose) print "Graph : commission distributeur $file\n";
      $graph = new GraphDistributeurCommission($db, $file);
      $graph->width = 500;
      $graph->height = 260;
      $graph->GraphMakeGraph($row[0]);

      /* Resultat */
      $dir = $img_root . "distributeurs/".$row[0]."/";
      _cdir($dir);
      $file = $dir."resultat.mensuel.png";
      if ($verbose) print "Graph : resultat distributeur $file\n";
      $graph = new GraphDistributeurResultat($db, $file);
      $graph->width = 500;
      $graph->height = 260;
      $graph->GraphMakeGraph($row[0]);
    }
}

/*
 * Globaux
 *
 */
/* Gain */
$file = $img_root . "distributeurs/gain.mensuel.$year.png";
if ($verbose) print "Graph : gain distributeur $file\n";
$graph = new GraphDistributeurGain($db, $file);
$graph->width = 500;
$graph->height = 260;
$graph->GraphMakeGraph(0);

$file = $img_root . "distributeurs/commission.mensuel.$year.png";
if ($verbose) print "Graph : commission distributeur $file\n";
$graph = new GraphDistributeurCommission($db, $file);
$graph->width = 500;
$graph->height = 260;
$graph->GraphMakeGraph(0);

$file = $img_root . "distributeurs/resultat.mensuel.$year.png";
if ($verbose) print "Graph : resultat distributeur $file\n";
$graph = new GraphDistributeurResultat($db, $file);
$graph->width = 500;
$graph->height = 260;
$graph->GraphMakeGraph(0);


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
