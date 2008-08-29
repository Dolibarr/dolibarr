<?php
/* Copyright (C) 2005-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie-tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/ca.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/gain.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/heureappel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/joursemaine.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/camoyen.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/appelsdureemoyenne.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/comm.nbmensuel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/comm.nbminutes.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/camenbert.class.php");

/*
 * Process
 *
 */

class ProcessGraphClients
{
  var $ident;
  
  function ProcessGraphClients( $ident , $cpc)
  {
    global $db;

    $this->ident = $ident;
    $this->cpc = $cpc;
    $this->db = $db;
  }
  
  function go($id=0)
  {
    $error = 0;

    $img_root = DOL_DATA_ROOT."/graph/telephonie/";
 
    $min = $this->ident * $this->cpc;
    $max = ($this->ident + 1 ) * $this->cpc;

    /*
     * Lecture des clients
     *
     */
    $sql = "SELECT s.rowid as socid, s.nom, count(l.ligne) as ligne";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql .= ",".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
    $sql .= " WHERE l.fk_client_comm = s.rowid ";
    if ($id == 0)
      {
	$sql .= " AND s.rowid >= ".$min;
	$sql .= " AND s.rowid < ".$max;
      }
    else
      {
	$sql .= " AND l.fk_client_comm = ".$id;
      }

    $sql .= " GROUP BY s.rowid";

    $resql = $this->db->query($sql);
    
    if ($resql)
      {
	$clients = array();
	
	$num = $this->db->num_rows($resql);

	$i = 0;
	
	while ($i < $num)
	  {
	    $obj = $this->db->fetch_object($resql);	   
	    $clients[$i] = $obj->socid;	    
	    $i++;
	  }
	$this->db->free($resql);
      }
    else
      {
	print $sql;
	exit("Erreur recuperation des clients");
      }

    if (sizeof($clients))
      {
	foreach ($clients as $client)
	  {
	    $img_root = DOL_DATA_ROOT."/graph/".substr($client,-1)."/telephonie/client/";
	    
	    $file = $img_root . $client."/graphca.png";
	    $graphca = new GraphCa($this->db, $file);
	    $graphca->client = $client;
	    $graphca->GraphDraw();
	    
	    $file = $img_root . $client."/graphgain.png";

	    $graphgain = new GraphGain ($this->db, $file);
	    $graphgain->client = $client;
	    $graphgain->show_console = 0 ;
	    $graphgain->GraphDraw();

	    $marge = 0;

	    if ($graphgain->total_ca > 0)
	      {
		$marge = ( $graphgain->total_gain / $graphgain->total_ca * 100);
	      }
	    
	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_client_stats (fk_client_comm, gain, ca, cout, marge)";
	    $sql .= " VALUES (".$client.",'".ereg_replace(",",".",$graphgain->total_gain)."'";
	    $sql .= ",'".ereg_replace(",",".",$graphgain->total_ca)."'";
	    $sql .= ",'".ereg_replace(",",".",$graphgain->total_cout)."'";
	    $sql .= ",'".ereg_replace(",",".",$marge)."')";

	    if (($graphgain->total_ca * $graphgain->total_gain * $graphgain->total_cout) <> 0)
	      {
		$this->db->query($sql);
	      }


	    $file = $img_root . $client."/graphappelsdureemoyenne.png";
	    
	    $graphduree = new GraphAppelsDureeMoyenne ($this->db, $file);
	    $graphduree->client = $client;
	    $graphduree->show_console = 0 ;
	    $graphduree->Graph();
	    
	    $file = $img_root . $client."/nb-comm-mensuel.png";
	    
	    $graphx = new GraphCommNbMensuel ($this->db, $file);
	    $graphx->client = $client;
	    $graphx->show_console = 0 ;
	    $graphx->Graph();
	    
	    
	    $file = $img_root . $client."/nb-minutes-mensuel.png";
	    
	    $graphx = new GraphCommNbMinutes ($this->db, $file);
	    $graphx->client = $client;
	    $graphx->show_console = 0 ;
	    $graphx->Graph();
	    
	  }       
      }
  }
}
?>
