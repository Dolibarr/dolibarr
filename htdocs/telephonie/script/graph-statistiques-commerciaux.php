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

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/commerciaux/commercial.ca.class.php");

$error = 0;

/*
 * Création des répertoires
 *
 */
$dirs[0] = DOL_DATA_ROOT."/graph/";
$dirs[1] = DOL_DATA_ROOT."/graph/telephonie/";
$dirs[2] = DOL_DATA_ROOT."/graph/telephonie/commercials/";

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

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  while ($i < $num)
    {
      $row = $db->fetch_row();	
      
      /***********************************************************************
       *
       * Chiffre d'affaire mensuel
       *
       ***********************************************************************/
      
      
      $file = $img_root . "commercials/".$row[0]."/ca.mensuel.png";
      if ($verbose) print "Graph : Lignes commandes$file\n";
      $graph = new GraphCommercialChiffreAffaire($db, $file);
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

?>
