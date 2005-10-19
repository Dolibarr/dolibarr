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
  
  function ProcessGraphLignes( $db)
  {
    global $conf;

    $this->ident = $ident;
    $this->cpc = $cpc;

    $this->db = $db;
    $this->labels = array();
    $this->nbminutes = array();
    $this->nbcomm = array();
    $this->duree_moyenne = array();
    $this->vente = array();
    $this->gain = array();
  }
  
  function go($ligne)
  {
    dolibarr_syslog("Deb ligne ".$ligne);

    $this->ligne = $ligne;
    $this->GetDatas();

    $error = 0;

    /* Chiffre d'affaire */
    
    $img_root = DOL_DATA_ROOT."/graph/".substr($ligne,-1)."/telephonie/ligne/";

    $file = $img_root . $ligne."/graphca.png";

    $graphx = new GraphBar ($this->db, $file);
    $graphx->ligne = $ligne;
    $graphx->width = 360;
    $graphx->titre = "Chiffre d'affaire (euros HT)";
    $graphx->barcolor = "blue";
    $graphx->show_console = 0 ;
    $graphx->GraphDraw($file, $this->vente, $this->labels);


    /* Gain */

    $file = $img_root . $ligne."/graphgain.png";
    
    $graphx = new GraphBar ($this->db, $file);
    $graphx->ligne = $ligne;
    $graphx->width = 360;
    $graphx->titre = "Gain (euros HT)";
    $graphx->barcolor = "green";
    $graphx->show_console = 0 ;
    $graphx->GraphDraw($file, $this->gain, $this->labels);

    /* Duree moyenne des appels */
    
    $file = $img_root . $ligne."/graphappelsdureemoyenne.png";
    
    $graphx = new GraphBar ($this->db, $file);
    $graphx->ligne = $ligne;
    $graphx->width = 360;
    $graphx->titre = "Durée moyenne";
    $graphx->barcolor = "orange";
    $graphx->show_console = 0 ;
    $graphx->GraphDraw($file, $this->duree_moyenne, $this->labels);

    /* Nb de communication */
    
    $file = $img_root . $ligne."/nb-comm-mensuel.png";

    $graphx = new GraphBar ($this->db, $file);
    $graphx->ligne = $ligne;
    $graphx->width = 360;
    $graphx->titre = "Nombre de communications";
    $graphx->show_console = 0 ;
    $graphx->GraphDraw($file, $this->nbcomm, $this->labels);

    /* Nb de minutes */
    
    $file = $img_root . $ligne."/nb-minutes-mensuel.png";
    
    $graphx = new GraphBar ($this->db, $file);
    $graphx->ligne = $ligne;
    $graphx->width = 360;
    $graphx->show_console = 0 ;
    $graphx->titre = "Nombre de minutes";
    $graphx->barcolor = "bisque2";
    $graphx->GraphDraw($file, $this->nbminutes, $this->labels);    
  }

  Function GetDatas()
  {
    $sql = "SELECT date_format(td.date,'%m'), sum(duree), count(*), sum(cout_vente), sum(fourn_montant)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details as td";
    $sql .= " WHERE td.fk_ligne = ".$this->ligne;
    $sql .= " GROUP BY date_format(td.date,'%Y%m') ASC ";
    
    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
		
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row();	
	    
	    $this->labels[$i] = $row[0];

	    $this->nbminutes[$i] = ceil($row[1] / 60);
	    $this->nbcomm[$i] = $row[2];
	    $this->duree_moyenne[$i] = ($this->nbminutes[$i] / $this->nbcomm[$i]);
	    $this->vente[$i] = $row[3];
	    $this->gain[$i] = ($row[3] - $row[4]);
	    
	    $i++;
	  }	
	$this->db->free();
      }
    else 
      {
	dolibarr_syslog("Error");
      }
  }
}
?>
