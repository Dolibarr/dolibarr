<?php
/* Copyright (C) 2005-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * Generation des graphiques de statistiques des lignes
 *
 *
 */
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/SimpleBar.class.php");

/*
 * Process
 *
 */

class ProcessGraphLignes
{
  var $ident;
  
  function ProcessGraphLignes($db)
  {
    global $conf;
    $this->messages = array();
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

  function GenerateAll()
  {
    $graph_all = 1;
    $datetime = time();
    $month = strftime("%m", $datetime);
    $year = strftime("%Y", $datetime);
    
    if ($month == 1)
      {
	$month = "12";
	$year = $year - 1;
      }
    else
      {
	$month = substr("00".($month - 1), -2) ;
      }
    
    $sql = "SELECT distinct(fk_ligne)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
    if ($graph_all == 0)
      {
	$sql .= " WHERE date_format(date,'%m%Y') = '".$month.$year."'";
      }
    
    $resql = $this->db->query($sql);
    
    if ($resql)
      {
	$num = $this->db->num_rows($resql);
	$i = 0;
	array_push($this->messages,array('info',"$num lignes trouvees"));
	
	while ($row = $this->db->fetch_row($resql))
	  {
	    //if ($verbose)
	      //print substr("0000".($i+1), -4) . "/".substr("0000".$num, -4)."\n";
	      
	      $this->go($row[0]);
	    
	    $i++;
	  }
      }
    else
      {
	array_push($this->messages,array('error','SQL Erreur'));
      }
  }
  
  
  function go($ligne)
  {
    dol_syslog("go $ligne");
    $this->ligne = $ligne;

    /* Lecture des donnees */
    $this->GetDatas();

    $error = 0;

    /* Chiffre d'affaire */
    
    $img_root = DOL_DATA_ROOT."/graph/".substr($ligne,-1)."/telephonie/ligne/";

    @mkdir(DOL_DATA_ROOT."/graph/");
    @mkdir(DOL_DATA_ROOT."/graph/".substr($ligne,-1));
    @mkdir(DOL_DATA_ROOT."/graph/".substr($ligne,-1)."/telephonie/");
    @mkdir(DOL_DATA_ROOT."/graph/".substr($ligne,-1)."/telephonie/ligne/");

    $file = $img_root . $ligne."/graphca.png";

    @mkdir(DOL_DATA_ROOT."/graph/".substr($ligne,-1)."/telephonie/ligne/".$ligne);

    $graphx = new DolibarrSimpleBar ($this->db, $file);
    $graphx->ligne = $ligne;
    $graphx->width = 400;
    $graphx->titre = "Chiffre d'affaire (euros HT)";
    $graphx->barcolor = "blue";
    $graphx->show_console = 0 ;
    $graphx->GraphDraw($file, $this->vente, $this->labels);

    /* Gain */

    $file = $img_root . $ligne."/graphgain.png";
    
    $graphx = new DolibarrSimpleBar ($this->db, $file);
    $graphx->ligne = $ligne;
    $graphx->width = 400;
    $graphx->titre = "Gain (euros HT)";
    $graphx->barcolor = "green";
    $graphx->show_console = 0 ;
    $graphx->GraphDraw($file, $this->gain, $this->labels);

    /* Duree moyenne des appels */
    
    $file = $img_root . $ligne."/graphappelsdureemoyenne.png";
    
    $graphx = new DolibarrSimpleBar ($this->db, $file);
    $graphx->ligne = $ligne;
    $graphx->width = 400;
    $graphx->titre = "Durée moyenne";
    $graphx->barcolor = "orange";
    $graphx->show_console = 0 ;
    $graphx->GraphDraw($file, $this->duree_moyenne, $this->labels);

    /* Nb de communication */
    
    $file = $img_root . $ligne."/nb-comm-mensuel.png";

    $graphx = new DolibarrSimpleBar ($this->db, $file);
    $graphx->ligne = $ligne;
    $graphx->width = 400;
    $graphx->titre = "Nombre de communications";
    $graphx->barcolor = "yellow";
    $graphx->show_console = 0 ;
    $graphx->GraphDraw($file, $this->nbcomm, $this->labels);

    /* Nb de minutes */
    
    $file = $img_root . $ligne."/nb-minutes-mensuel.png";
    
    $graphx = new DolibarrSimpleBar ($this->db, $file);
    $graphx->ligne = $ligne;
    $graphx->width = 400;
    $graphx->show_console = 0 ;
    $graphx->titre = "Nombre de minutes";
    $graphx->barcolor = "pink";
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
	dol_syslog("Error");
      }
  }
}
?>
