<?php
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
 * Generation des graphiques clients
 *
 *
 */
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/ca.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/gain.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/appelsdureemoyenne.class.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/comm.nbmensuel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/comm.nbminutes.class.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/heureappel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/joursemaine.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/camoyen.class.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/camenbert.class.php");

/*
 * Process
 *
 */

class ProcessGraphLignes
{
  var $ident;
  
  function ProcessGraphLignes( $ident , $cpc)
  {
    global $conf;

    $this->ident = $ident;
    $this->cpc = $cpc;
    $this->db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,1);
  }
  
  function go()
  {
    $min = $this->ident * $this->cpc;
    $max = ($this->ident + 1 ) * $this->cpc;

    dolibarr_syslog("Deb ligne ".$this->ident . " ($min - $max)");
    $error = 0;

    /*
     * Lecture des lignes
     *
     */
    $sql = "SELECT l.rowid";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
    $sql .= " WHERE l.rowid >= ".$min;
    $sql .= " AND l.rowid < ".$max;
    $sql .= " ORDER BY l.rowid ASC";
    
    if ($this->db->query($sql))
      {
	$lignes = array();
	
	$num = $this->db->num_rows();
	$i = 0;
	
	while ($i < $num)
	  {
	    $obj = $this->db->fetch_object();	
	    
	    $lignes[$i] = $obj->rowid;
	    
	    $i++;
	  }
      }

    if (sizeof($lignes))
      {
	foreach ($lignes as $ligne)
	  {
	    /* Chiffre d'affaire */
	    
	    $img_root = DOL_DATA_ROOT."/graph/".substr($ligne,-1)."/telephonie/ligne/";

	    $file = $img_root . $ligne."/graphca.png";
	    $graphca = new GraphCa($this->db, $file);
	    $graphca->ligne = $ligne;
	    $graphca->GraphDraw();

	    /* Gain */

	    $file = $img_root . $ligne."/graphgain.png";

	    $graphgain = new GraphGain ($this->db, $file);
	    $graphgain->ligne = $ligne;
	    $graphgain->show_console = 0 ;
	    $graphgain->GraphDraw();

	    /* Duree moyenne des appels */

	    $file = $img_root . $ligne."/graphappelsdureemoyenne.png";
	    
	    $graphduree = new GraphAppelsDureeMoyenne ($this->db, $file);
	    $graphduree->ligne = $ligne;
	    $graphduree->show_console = 0 ;
	    $graphduree->Graph();
	    
	    /* Nb de communication */

	    $file = $img_root . $ligne."/nb-comm-mensuel.png";
	    
	    $graphx = new GraphCommNbMensuel ($this->db, $file);
	    $graphx->ligne = $ligne;
	    $graphx->show_console = 0 ;
	    $graphx->Graph();

	    /* Nb de minutes */

	    $file = $img_root . $ligne."/nb-minutes-mensuel.png";
	    
	    $graphx = new GraphCommNbMinutes ($this->db, $file);
	    $graphx->ligne = $ligne;
	    $graphx->show_console = 0 ;
	    $graphx->Graph();
	  }       
      }

    dolibarr_syslog("Fin ligne ".$this->ident);
  }
}
?>
