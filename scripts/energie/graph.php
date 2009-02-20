<?PHP
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
 */

require_once("../../htdocs/master.inc.php");

require_once(DOL_DOCUMENT_ROOT."/energie/EnergieCompteur.class.php");
require_once(DOL_DOCUMENT_ROOT."/energie/EnergieGroupe.class.php");

require_once (DOL_DOCUMENT_ROOT."/../external-libs/Artichow/BarPlot.class.php");
require_once (DOL_DOCUMENT_ROOT."/../external-libs/Artichow/LinePlot.class.php");

$error = 0;

$sql_c = "SELECT rowid FROM ".MAIN_DB_PREFIX."energie_compteur";
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

	  $labels = array();
	  $datas = array();
	  
	  $ydatas = array();
	  $mdatas = array();
	  $wdatas = array();


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
 
	      for ($i = 1 ; $i < sizeof($datas) ; $i++)
		{
		  $xa = ($datas[$i][0] - $datas[($i-1)][0]);
		  
		  $maxa = max($maxa, $xa);
		  
		  $gdatas[$i-1] = $xa;
		  $glabels[$i-1] = '';//strftime("%d%m",$datas[$i][1]);
		  
		  $month = strftime("%m%y",$datas[$i][1]);
		  
		  $mdatas[$compteur_id][$month] = $mdatas[$compteur_id][$month] + $xa;

		  $week = strftime("%W%y",$datas[$i][1]);

		  $wdatas[$compteur_id][$week] = $wdatas[$compteur_id][$week] + $xa;

		  $year = strftime("%Y",$datas[$i][1]);

		  $ydatas[$compteur_id][$year] = $ydatas[$compteur_id][$year] + $xa;
		}
	      
	      $width = 750;
	      $height = 300;
	      if (sizeof($gdatas) > 2)
		{
		  $file= DOL_DATA_ROOT."/energie/graph/day.".$obj_c->rowid.".png";

		  $group = new PlotGroup;
		  $group->setPadding(30, 10, NULL, NULL);
    
		  $graph = new Graph($width, $height);
		  $graph->border->hide();
		  $graph->setAntiAliasing(true);

		  $graph->title->set("Consommation par jour");
		  $graph->title->setFont(new Tuffy(10));

		  $bgcolor= new Color(222,231,236);
		  $graph->setBackgroundColor($bgcolor);
    
		  $plot = new LinePlot($gdatas);
    
		  $plot->xAxis->setLabelText($glabels);
		  $plot->xAxis->label->setFont(new Tuffy(7));
		  
		  $graph->add($plot);
		  $graph->draw($file);
		}
	      $width = 450;
	      $height = 300;
	      
	      // Mensuel
	      $i=0;
	      foreach ($mdatas[$compteur_id] as $key => $value)
		{
		  $gmdatas[$i] = $value;
		  $gmlabels[$i] = $key;
		  $i++;
		}
	      if (sizeof($gmdatas))
		{
		  $file= DOL_DATA_ROOT."/energie/graph/month.".$obj_c->rowid.".png";

		  $group = new PlotGroup;
		  $group->setPadding(30, 10, NULL, NULL);
    
		  $graph = new Graph($width, $height);
		  $graph->border->hide();
		  $graph->setAntiAliasing(true);

		  $graph->title->set("Consommation par mois");
		  $graph->title->setFont(new Tuffy(10));

		  $bgcolor= new Color(222,231,236);
		  $graph->setBackgroundColor($bgcolor);
    
		  $plot = new BarPlot($gmdatas);
		  $col = "blue";
		  $color = new $col ;
		  $plot->setBarColor($color);
		  $plot->xAxis->setLabelText($gmlabels);
		  $plot->xAxis->label->setFont(new Tuffy(7));
		  
		  $graph->add($plot);
		  $graph->draw($file);
		}
	      // Hebdomadaire
	      $width = 750;
	      $height = 300;
	      $i=0;
	      foreach ($wdatas[$compteur_id] as $key => $value)
		{
		  $gwdatas[$i] = $value;
		  $gwlabels[$i] = substr($key,0,2);
		  $i++;
		}
	      if (sizeof($gwdatas))
		{
		  $file= DOL_DATA_ROOT."/energie/graph/week.".$obj_c->rowid.".png";

		  $group = new PlotGroup;
		  $group->setPadding(30, 10, NULL, NULL);
    
		  $graph = new Graph($width, $height);
		  $graph->border->hide();
		  $graph->setAntiAliasing(true);

		  $graph->title->set("Consommation par semaine");
		  $graph->title->setFont(new Tuffy(10));

		  $bgcolor= new Color(222,231,236);
		  $graph->setBackgroundColor($bgcolor);
    
		  $plot = new BarPlot($gwdatas);
		  $col = "blue";
		  $color = new $col ;
		  $plot->setBarColor($color);
		  $plot->xAxis->setLabelText($gwlabels);
		  $plot->xAxis->label->setFont(new Tuffy(7));
		  
		  $graph->add($plot);
		  $graph->draw($file);
		}

	      // Annuel
	      $width = 450;
	      $height = 300;
	      $i=0;
	      foreach ($ydatas[$compteur_id] as $key => $value)
		{
		  $gydatas[$i] = $value;
		  $gylabels[$i] = $key;
		  $i++;
		}
	     
	      if (sizeof($gydatas))
		{
		  $file= DOL_DATA_ROOT."/energie/graph/year.".$obj_c->rowid.".png";

		  $group = new PlotGroup;
		  $group->setPadding(30, 10, NULL, NULL);
    
		  $graph = new Graph($width, $height);
		  $graph->border->hide();
		  $graph->setAntiAliasing(true);

		  $graph->title->set("Consommation annuelle");
		  $graph->title->setFont(new Tuffy(10));

		  $bgcolor= new Color(222,231,236);
		  $graph->setBackgroundColor($bgcolor);
    
		  $plot = new BarPlot($gydatas);
		  $col = "blue";
		  $color = new $col ;
		  $plot->setBarColor($color);
		  $plot->xAxis->setLabelText($gylabels);
		  $plot->xAxis->label->setFont(new Tuffy(7));
		  
		  $graph->add($plot);
		  $graph->draw($file);
		}	      
	    }
	  else
	    {
	      dol_syslog("Erreur SQL");
	      dol_syslog("$sql");
	    }
	  $i_c++;
	}
    }
}
else
{
  dol_syslog("Erreur SQL");
  dol_syslog($db->error($resql_c));
}
/*************************************************************
 * Groupes
 * 
 */

$sql_g = "SELECT distinct fk_energie_groupe";
$sql_g .= " FROM ".MAIN_DB_PREFIX."energie_compteur_groupe";

$resql_g = $db->query($sql_g);

if ($resql_g)
{
  $num_g = $db->num_rows($resql_g);
  $i_g = 0;

  while ($i_g < $num_g)
    {
      $row_g = $db->fetch_row($resql_g);
            
      $sql_c = "SELECT fk_energie_compteur";
      $sql_c .= " FROM ".MAIN_DB_PREFIX."energie_compteur_groupe";
      $sql_c .= " WHERE fk_energie_groupe = ".$row_g[0];	  
      
      $resql_c = $db->query($sql_c);
      
      if ($resql_c)
	{
	  $num_c = $db->num_rows($resql_c);
	  $i_c = 0;
	  
	  $compteurs = array();
	  while ($i_c < $num_c)
	    {
	      $obj_c = $db->fetch_object($resql_c);
	      
	      array_push($compteurs,$obj_c->fk_energie_compteur);
	      $i_c++;
	    }
	  	  	 	  
	  $width = 450;
	  $height = 300;

	  // Hebdo
	  $file= DOL_DATA_ROOT."/energie/graph/groupe.week.".$row_g[0].".png";
	  
	  $group = new PlotGroup;
	  $group->setPadding(30, 10, NULL, NULL);
	  
	  $graph = new Graph($width, $height);
	  $graph->border->hide();
	  $graph->setAntiAliasing(true);
	  
	  $graph->title->set("Consommation hebdomadaire");
	  $graph->title->setFont(new Tuffy(10));
	  
	  $bgcolor= new Color(222,231,236);
	  $graph->setBackgroundColor($bgcolor);
	  
	  $gbspl = array();
	  foreach ($compteurs as $cx)
	    {
	      $gydatas = array();
	      $gylabels = array();
	      
	      $i=0;
	      foreach ($wdatas[$cx] as $key => $value)
		{
		  $gydatas[$i] = $value;
		  $gylabels[$i] = $key;
		  $i++;
		}
	      
	      array_push($gbspl, $bplot);

	      $plot = new BarPlot($gydatas);
	      $col = "green";
	      $color = new $col ;
	      $plot->setBarColor($color);
	      $plot->xAxis->setLabelText($gylabels);
	      $plot->xAxis->label->setFont(new Tuffy(7));
	      $graph->add($plot);	      	      
	    }
	  
	  $graph->draw($file);
	  //
	  //
	  //
	  $file= DOL_DATA_ROOT."/energie/graph/groupe.month.".$row_g[0].".png";	  
	  $group = new PlotGroup;
	  $group->setPadding(30, 10, NULL, NULL);
	  
	  $graph = new Graph($width, $height);
	  $graph->border->hide();
	  $graph->setAntiAliasing(true);
	  
	  $graph->title->set("Consommation mensuelle");
	  $graph->title->setFont(new Tuffy(10));
	  
	  $bgcolor= new Color(222,231,236);
	  $graph->setBackgroundColor($bgcolor);
	  
	  $gbspl = array();
	  foreach ($compteurs as $cx)
	    {
	      $gydatas = array();
	      $gylabels = array();
	      
	      $i=0;
	      foreach ($mdatas[$cx] as $key => $value)
		{
		  $gydatas[$i] = $value;
		  $gylabels[$i] = $key;
		  $i++;
		}
	      
	      array_push($gbspl, $bplot);

	      $plot = new BarPlot($gydatas);
	      $col = "blue";
	      $color = new $col ;
	      $plot->setBarColor($color);
	      $plot->xAxis->setLabelText($gylabels);
	      $plot->xAxis->label->setFont(new Tuffy(7));
	      $graph->add($plot);
	      	      
	    }
	  
	  $graph->draw($file);

	  //
	  //
	  //
	  $file= DOL_DATA_ROOT."/energie/graph/groupe.year.".$row_g[0].".png";
	  $group = new PlotGroup;
	  $group->setPadding(30, 10, NULL, NULL);
	  
	  $graph = new Graph($width, $height);
	  $graph->border->hide();
	  $graph->setAntiAliasing(true);
	  
	  $graph->title->set("Consommation annuelle");
	  $graph->title->setFont(new Tuffy(10));
	  
	  $bgcolor= new Color(222,231,236);
	  $graph->setBackgroundColor($bgcolor);
	  
	  $gbspl = array();
	  foreach ($compteurs as $cx)
	    {
	      $gydatas = array();
	      $gylabels = array();
	      
	      $i=0;
	      foreach ($ydatas[$cx] as $key => $value)
		{
		  $gydatas[$i] = $value;
		  $gylabels[$i] = $key;
		  $i++;
		}
	      
	      array_push($gbspl, $bplot);

	      $plot = new BarPlot($gydatas);
	      $col = "yellow";
	      $color = new $col ;
	      $plot->setBarColor($color);
	      $plot->xAxis->setLabelText($gylabels);
	      $plot->xAxis->label->setFont(new Tuffy(7));
	      $graph->add($plot);
	      	      
	    }
	  
	  $graph->draw($file);
	  //
	}
      else
	{
	  dol_syslog("Erreur SQL");
	  print $sql_c;
	}
      $i_g++;
    }
}
else
{
  dol_syslog("Erreur SQL");
  print $sql;
}
?>
