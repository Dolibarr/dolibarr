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
 */

require ("../../htdocs/master.inc.php");

require_once(DOL_DOCUMENT_ROOT."/energie/EnergieCompteur.class.php");
require_once(DOL_DOCUMENT_ROOT."/energie/EnergieGroupe.class.php");

include_once (JPGRAPH_DIR."jpgraph.php");
include_once (JPGRAPH_DIR."jpgraph_line.php");

$error = 0;

$labels = array();
$datas = array();

$sql_c = "SELECT rowid";
$sql_c .= " FROM ".MAIN_DB_PREFIX."energie_compteur";

$resql_c = $db->query($sql_c);

if ($resql_c)
{
  $num_c = $db->num_rows($resql_c);
  $i_c = 0;

  if ($num_c > 0)
    {
      while ($i_c < $num_c)
	{
	  $obj_c = $db->fetch_object($resql_c);

	  $compteur_id = $obj_c->rowid;

	  $sql = "SELECT ".$db->pdate("date_releve")." as date_releve, valeur";
	  $sql .= " FROM ".MAIN_DB_PREFIX."energie_compteur_releve";
	  $sql .= " WHERE fk_compteur = ".$obj_c->rowid;
	  $sql .= " ORDER BY date_releve ASC";

	  $resql = $db->query($sql);

	  if ($resql)
	    {
	      $num = $db->num_rows($resql);
	      $i = 0;
	      
	      if ($num > 0)
		{
		  $obj = $db->fetch_object($resql);
		  
		  //print strftime("%Y-%m-%d", $obj->date_releve) . "\t\t".$obj->valeur."\n";
		  
		  $previous_date  = $obj->date_releve;
		  $previous_value = $obj->valeur;
		  
		  $i++;
		}
	      
	      $datas = array();
	      $k = 0;
	      
	      while ($i < $num)
		{
		  $obj = $db->fetch_object($resql);
		  
		  $delta = (($obj->date_releve - $previous_date) / 86400 );
		  
		  if ($delta > 1)
		    {
		      for ($j = 1 ; $j < $delta ; $j++)
			{
			  $value = $previous_value + ((($obj->valeur - $previous_value) / $delta) * $j);
			  
			  $datas[$k][0] = $value;
			  $datas[$k][1] = ($previous_date + (86400 * $j));
			  $k++;
			  
			  //print strftime("%Y-%m-%d", ($previous_date + (86400 * $j))) . "\t$j\t".$value."\n";
			}
		    }
		  
		  //print strftime("%Y-%m-%d", $obj->date_releve) . "\t\t".$obj->valeur."\n";
		  
		  $datas[$k][0] = $obj->valeur;
		  $datas[$k][1] = $obj->date_releve;
		  $k++;
		  
		  $previous_date = $obj->date_releve;
		  $previous_value = $obj->valeur;
		  $i++;      
		}
	      
	      // Graph
	      $maxa = 0;
 
	      $xdatas = array();
	      $xlabels = array();

	      for ($i = 1 ; $i < sizeof($datas) ; $i++)
		{
		  $xa = ($datas[$i][0] - $datas[($i-1)][0]);
		  
		  $maxa = max($maxa, $xa);
		  
		  $gdatas[$i-1] = $xa;
		  $glabels[$i-1] = strftime("%d%m",$datas[$i][1]);
		  
		  $xdatas[$glabels[$i-1]] = $gdatas[$i-1];
		}

	      $year = strftime("%Y", time());
	      $xyear = $year;
	      $day = mktime(0,0,0,1,1,$year);

	      $xydatas = array();
	      $xylabels = array();
	      $i=0;
	      while ($xyear == $year)
		{
		  $xydatas[$i] = $xdatas[strftime("%d%m",$day)];
		  $xylabels[$i] = '';

		  $i++;
		  $day += 86400;
		  $xyear = strftime("%Y",$day);
		}
	      
	      $width = 750;
	      $height = 300;
	      if (sizeof($xydatas) > 2)
		{
		  $graph = new Graph($width, $height,"auto");    
		  $graph->SetScale("textlin",0,$maxa);
	      
		  $graph->yaxis->scale->SetGrace(2);
		  $graph->SetFrame(1);
		  $graph->img->SetMargin(40,20,20,35);
	      
		  $b2plot = new LinePlot($xydatas);	      

		  $b2plot->SetColor("blue");

		  $graph->title->Set("Consommation journalière");
	      
		  $graph->xaxis->Hide();
	      
		  $graph->Add($b2plot);
		  $graph->img->SetImgFormat("png");
	      
		  $file= DOL_DATA_ROOT."/energie/graph/all.".$obj_c->rowid.".png";
		  
		  $graph->Stroke($file);
		}
	      else
		{
		  //print "No graph ".sizeof($xydatas)."\n";
		}
	    }
	  else
	    {
	      dolibarr_syslog("Erreur SQL");
	      dolibarr_syslog("$sql");
	    }
	  $i_c++;
	}
    }
}
else
{
  dolibarr_syslog("Erreur SQL");
  dolibarr_syslog($db->error($resql_c));
}
?>
