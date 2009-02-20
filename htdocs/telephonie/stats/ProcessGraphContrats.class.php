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
 *
 *
 */
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/SimpleBar.class.php");

/*
 * Process
 *
 */

class ProcessGraphContrats
{
  var $ident;
  
  function ProcessGraphContrats( $ident=0 , $cpc=0)
  {
    global $db;
    
    $this->ident = $ident;
    $this->cpc = $cpc;
    $this->db = $db;
    $this->messages = array();
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
    
    $ym = substr($year,2,2).$month;

    $this->go($row[0], $ym);
	    

  }

  
  function go($contrat_id = 0, $ym=0, $verbose=0)
  {
    $error = 0;
    $contrats = array();
    /*
     * Lecture des contrats
     *
     */
    $sql = "SELECT c.rowid";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat as c";
    
    if ($resql = $this->db->query($sql))
      {
	while ($row = $this->db->fetch_row($resql))
	  {
	    array_push($contrats, $row[0]);
	  }
      }

    array_push($this->messages,array('info',sizeof($contrats)." contrats a generer"));
    
    if (sizeof($contrats))
      {
	foreach ($contrats as $contrat)
	  {
	    $img_root = DOL_DATA_ROOT."/graph/".substr($contrat,-1)."/telephonie/contrat/";

	    if (!is_dir($img_root))
	      {
		@mkdir(DOL_DATA_ROOT."/graph/".substr($contrat,-1));
		@mkdir(DOL_DATA_ROOT."/graph/".substr($contrat,-1)."/telephonie/");
		@mkdir(DOL_DATA_ROOT."/graph/".substr($contrat,-1)."/telephonie/contrat/");
		@mkdir(DOL_DATA_ROOT."/graph/".substr($contrat,-1)."/telephonie/contrat/".$contrat);
	      }
	    /* Lecture des donnees */

	    $this->GetDatas($contrat, $ym);	    

	    if (sizeof($this->labels) > 0)
	      {

		/* Chiffre d'affaire */	   
		$file = $img_root . $contrat."/graphca.png";

		$graph = new DolibarrSimpleBar ($this->db, $file);
		$graph->width = 400;
		$graph->titre = "Chiffre d'affaire (euros HT)";
		$graph->barcolor = "blue";
		$graph->show_console = 0 ;	    	    
		$graph->GraphDraw($file, $this->vente, $this->labels);
				
		/* Gain */	    
		$file = $img_root . $contrat."/graphgain.png";
		
		$graph = new DolibarrSimpleBar ($this->db, $file);
		$graph->width = 400;
		$graph->titre = "Gain (euros HT)";
		$graph->barcolor = "green";
		$graph->show_console = 0 ;	    	    
		$graph->GraphDraw($file, $this->gain, $this->labels);
		
		
		/* Duree moyenne des appels */
		$file = $img_root . $contrat."/graphappelsdureemoyenne.png";

		$graph = new DolibarrSimpleBar ($this->db, $file);
		$graph->width = 400;
		$graph->titre = "Durée moyenne d'un appel";
		$graph->yAxisLegend = "minutes";
		$graph->barcolor = "orange";
		$graph->show_console = 0 ;
		$graph->GraphDraw($file, $this->duree_moyenne, $this->labels);
		
		/* Nb de communication */		
		$file = $img_root . $contrat."/nb-comm-mensuel.png";
		
		$graph = new DolibarrSimpleBar ($this->db, $file);
		$graph->width = 400;
		$graph->titre = "Nombre de communications";
		$graph->barcolor = "yellow";
		$graph->show_console = 0 ;
		$graph->GraphDraw($file, $this->nbcomm, $this->labels);
		
		/* Nb de minutes */
		$file = $img_root . $contrat."/nb-minutes-mensuel.png";
		  
		$graph = new DolibarrSimpleBar ($this->db, $file);
		$graph->width = 400;
		$graph->titre = "Nombre de minutes";
		$graph->barcolor = "pink";
		$graph->show_console = 0 ;
		$graph->GraphDraw($file, $this->nbminutes, $this->labels);
	      }
	  }       
      }
  }

  Function GetDatas($id, $ym)
  {
    $sql = "SELECT date_format(td.date,'%m'), sum(duree), count(*), sum(cout_vente), sum(fourn_montant)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details as td";
    $sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
    $sql .= " WHERE l.fk_contrat='".$id."' AND l.rowid=td.fk_ligne";
    $sql .= " GROUP BY date_format(td.date,'%Y%m') ASC ";

    $this->labels = array();
    $this->vente = array();
    $this->gain = array();
    $this->nbcomm = array();
    $this->nbminutes = array();
    $this->duree_moyenne = array();

    if ($resql = $this->db->query($sql))
      {
	$num = $this->db->num_rows($resql);
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
	$this->db->free($resql);

      }
    else 
      {
	dol_syslog("Error");
      }
  }

}
?>
