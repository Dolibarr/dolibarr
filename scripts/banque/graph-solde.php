#!/usr/bin/php
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
 */

/**
        \file       scripts/banque/graph-solde.php
        \ingroup    banque
        \brief      Script de génération des images des soldes des comptes
*/


// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer mailing-send.php en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}


// Recupere root dolibarr
$path=eregi_replace('graph-solde.php','',$_SERVER["PHP_SELF"]);

require_once($path."../../htdocs/master.inc.php");

// Vérifie que chemin vers JPGRAHP est connu et defini $jpgraph
if (! defined('JPGRAPH_DIR') && ! defined('JPGRAPH_PATH'))
{
    print 'Erreur: Définissez la constante JPGRAPH_PATH sur la valeur du répertoire contenant JPGraph';
    exit;
}    
if (! defined('JPGRAPH_DIR')) define('JPGRAPH_DIR', JPGRAPH_PATH);
$jpgraphdir=JPGRAPH_DIR;
if (! eregi('[\\\/]$',$jpgraphdir)) $jpgraphdir.='/';


include_once($jpgraphdir."jpgraph.php");
include_once($jpgraphdir."jpgraph_line.php");
include_once($jpgraphdir."jpgraph_bar.php");
include_once($jpgraphdir."jpgraph_pie.php");
include_once($jpgraphdir."jpgraph_error.php");
include_once($jpgraphdir."jpgraph_canvas.php");

$error = 0;

// Initialise opt, tableau des parametres
if (function_exists("getopt"))
{
    // getopt existe sur ce PHP
    $opt = getopt("m:y:");
}
else
{
    // getopt n'existe sur ce PHP
    $opt=array('m'=>$argv[1]);
}    


// Crée répertoire accueil
create_exdir($conf->banque->dir_images);


$datetime = time();

if ($opt['m'] > 0)
{
  $month = $opt['m'];
}
else
{
  $month = strftime("%m", $datetime);
}
$year = strftime("%Y", $datetime);

if ($month == 1)
{
  $monthprev = "12";
  $yearprev = $year - 1;
}
else
{
  $monthprev = substr("00".($month - 1), -2) ;
  $yearprev = $year ;
}

if ($month == 12)
{
  $monthnext = "01";
  $yearnext = $year + 1;
}
else
{
  $monthnext = substr("00".($month + 1), -2) ;
}

$sql = "SELECT distinct(fk_account)";
$sql .= " FROM ".MAIN_DB_PREFIX."bank";
$sql .= " WHERE fk_account IS NOT NULL";

$resql = $db->query($sql);

$accounts = array();

if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);
      array_push($accounts, $row[0]);
      $i++;
    }

}
  
$account = 1; 

foreach ($accounts as $account)
{
  $labels = array();
  $datas = array();
  $amounts = array();
  
  $sql = "SELECT sum(amount)";
  $sql .= " FROM ".MAIN_DB_PREFIX."bank";
  $sql .= " WHERE fk_account = ".$account;
  $sql .= " AND datev < '".$year."-".$month."-01';";
  
  $resql = $db->query($sql);
  if ($resql)
    {
      $row = $db->fetch_row($resql);
      $solde = $row[0];
    }
  else
    {
      print $sql ;
    }


  $sql = "SELECT date_format(datev,'%Y%m%d'), sum(amount)";
  $sql .= " FROM ".MAIN_DB_PREFIX."bank";
  $sql .= " WHERE fk_account = ".$account;
  $sql .= " AND date_format(datev,'%Y%m') = '".$year.$month."'";
  $sql .= " GROUP BY date_format(datev,'%Y%m%d');";

  $resql = $db->query($sql);
  
  $amounts = array();
  
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($resql);
	  $amounts[$row[0]] = $row[1];
	  $i++;
	}      
    }
  else
    {
      print $sql ;
    }
  
  $subtotal = 0;
  
  $day = mktime(1,1,1,$month,1,$year);
  
  $xmonth = substr("00".strftime("%m",$day), -2);
  $i = 0;
  while ($xmonth == $month)
    {
      //print strftime ("%e %d %m %y",$day)."\n";
      
      $subtotal = $subtotal + $amounts[strftime("%Y%m%d",$day)];

      if ($day > time())
	{      
	  $datas[$i] = 0;
	}
      else
	{
	  $datas[$i] = $solde + $subtotal;
	}

      $labels[$i] = strftime("%d",$day);
      
      $day += 86400;
      $xmonth = substr("00".strftime("%m",$day), -2);
      $i++;
    }
    
  $width = 750;
  $height = 350;
  
  $graph = new Graph($width, $height,"auto");    
  $graph->SetScale("textlin");
  
  $graph->yaxis->scale->SetGrace(2);
  $graph->SetFrame(1);
  $graph->img->SetMargin(60,20,20,35);
  
  $b2plot = new BarPlot($datas);
  
  $b2plot->SetColor("blue");
  //$b2plot->SetWeight(2);
  
  $graph->title->Set("Solde $month $year");
  
  $graph->xaxis->SetTickLabels($labels);   
  //$graph->xaxis->title->Set(strftime("%d/%m/%y %H:%M:%S", time()));
  
  $graph->Add($b2plot);
  $graph->img->SetImgFormat("png");
  
  $file= $conf->banque->dir_images."/solde.$account.$year.$month.png";
  
  $graph->Stroke($file);
}
/*
 * Graph annuels
 *
 */
foreach ($accounts as $account)
{
  $labels = array();
  $datas = array();
  $amounts = array();
  
  $sql = "SELECT sum(amount)";
  $sql .= " FROM ".MAIN_DB_PREFIX."bank";
  $sql .= " WHERE fk_account = ".$account;
  $sql .= " AND datev < '".$year."-01-01';";
  
  $resql = $db->query($sql);
  if ($resql)
    {
      $row = $db->fetch_row($resql);
      $solde = $row[0];
    }
  else
    {
      print $sql ;
    }

  $sql = "SELECT date_format(datev,'%Y%m%d'), sum(amount)";
  $sql .= " FROM ".MAIN_DB_PREFIX."bank";
  $sql .= " WHERE fk_account = ".$account;
  $sql .= " AND date_format(datev,'%Y') = '".$year."'";
  $sql .= " GROUP BY date_format(datev,'%Y%m%d');";

  $resql = $db->query($sql);
  
  $amounts = array();
  
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($resql);
	  $amounts[$row[0]] = $row[1];
	  $i++;
	}      
    }
  else
    {
      dolibarr_syslog("graph-solde.php Error");
    }
  
  $subtotal = 0;
  
  $day = mktime(1,1,1,1,1,$year);
  
  $xyear = strftime("%Y",$day);
  $i = 0;
  while ($xyear == $year)
    {
      //print strftime ("%e %d %m %y",$day)."\n";
      
      $subtotal = $subtotal + $amounts[strftime("%Y%m%d",$day)];

      if ($day > time())
	{      
	  $datas[$i] = 'x'; // Valeur spéciale permettant de ne pas tracer le graph
	}
      else
	{
	  $datas[$i] = $solde + $subtotal;
	}

      if (strftime("%d",$day) == 1)
	{
	  $labels[$i] = strftime("%d",$day);
	}
      else
	{

	}
      
      $day += 86400;
      $xyear = strftime("%Y",$day);
      $i++;
    }
    
  $width = 750;
  $height = 350;
  
  $graph = new Graph($width, $height,"auto");    
  $graph->SetScale("textlin");
  
  $graph->yaxis->scale->SetGrace(2);
  $graph->SetFrame(1);
  $graph->img->SetMargin(60,20,20,35);
  
  $b2plot = new LinePlot($datas);
  
  $b2plot->SetColor("blue");
  //$b2plot->SetWeight(2);
  
  $graph->title->Set("Solde $year");
  
  $graph->xaxis->SetTickLabels($labels);

  $graph->xaxis->Hide();
  //$graph->xaxis->HideTicks(); 


  //$graph->xaxis->title->Set(strftime("%d/%m/%y %H:%M:%S", time()));
  
  $graph->Add($b2plot);
  $graph->img->SetImgFormat("png");
  
  $file= $conf->banque->dir_images."/solde.$account.$year.png";
  
  $graph->Stroke($file);
}

/*
 * Graph annuels
 *
 */
foreach ($accounts as $account)
{
  $labels = array();
  $datas = array();
  $amounts = array();
  
  $sql = "SELECT min(".$db->pdate("datev")."),max(".$db->pdate("datev").")";
  $sql .= " FROM ".MAIN_DB_PREFIX."bank";
  $sql .= " WHERE fk_account = ".$account;

  $resql = $db->query($sql);

  if ($resql)
    {
      $num = $db->num_rows($resql);
      $row = $db->fetch_row($resql);
      $min = $row[0];
      $max = $row[1];
    }
  else
    {
      dolibarr_syslog("graph-solde.php Error");
    }


  $sql = "SELECT date_format(datev,'%Y%m%d'), sum(amount)";
  $sql .= " FROM ".MAIN_DB_PREFIX."bank";
  $sql .= " WHERE fk_account = ".$account;
  $sql .= " GROUP BY date_format(datev,'%Y%m%d');";

  $resql = $db->query($sql);
  
  $amounts = array();
  
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($resql);
	  $amounts[$row[0]] = $row[1];
	  $i++;
	}      
    }
  else
    {
      dolibarr_syslog("graph-solde.php Error");
    }
  
  $subtotal = 0;
  
  $day = $min;
  
  $i = 0;
  while ($day <= $max)
    {
      //print strftime ("%e %d %m %y",$day)."\n";
      
      $subtotal = $subtotal + $amounts[strftime("%Y%m%d",$day)];

      $datas[$i] = $solde + $subtotal;

      $labels[$i] = strftime("%d",$day);

      $day += 86400;
      $i++;
    }

  if (sizeof($amounts) > 3)
    {    
      $width = 750;
      $height = 350;

      $graph = new Graph($width, $height,"auto");    
      $graph->SetScale("textlin");
  
      $graph->yaxis->scale->SetGrace(2);
      $graph->SetFrame(1);
      $graph->img->SetMargin(60,20,20,35);
      
      $b2plot = new LinePlot($datas);
      
      $b2plot->SetColor("blue");
      
      $graph->title->Set("Solde");
      
      $graph->xaxis->SetTickLabels($labels);
      
      $graph->xaxis->Hide();
      
      $graph->Add($b2plot);
      $graph->img->SetImgFormat("png");
      
      $file= $conf->banque->dir_images."/solde.$account.png";
      
      $graph->Stroke($file);
    }
}

foreach ($accounts as $account)
{
  $labels = array();
  $datas = array();
  $amounts = array();
  $credits = array();
  $debits = array();

  $sql = "SELECT date_format(datev,'%m'), sum(amount)";
  $sql .= " FROM ".MAIN_DB_PREFIX."bank";
  $sql .= " WHERE fk_account = ".$account;
  $sql .= " AND date_format(datev,'%Y') = '".$year."'";
  $sql .= " AND amount > 0";
  $sql .= " GROUP BY date_format(datev,'%m');";

  $resql = $db->query($sql);
  
  $amounts = array();
  
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($resql);
	  $credits[$row[0]] = $row[1];
	  $i++;
	}      
    }
  else
    {
      print $sql ;
    }

  $sql = "SELECT date_format(datev,'%m'), sum(amount)";
  $sql .= " FROM ".MAIN_DB_PREFIX."bank";
  $sql .= " WHERE fk_account = ".$account;
  $sql .= " AND date_format(datev,'%Y') = '".$year."'";
  $sql .= " AND amount < 0";
  $sql .= " GROUP BY date_format(datev,'%m');";

  $resql = $db->query($sql);  
  if ($resql)
    {
      while ($row = $db->fetch_row($resql))
	{
	  $debits[$row[0]] = abs($row[1]);
	}      
    }
  else
    {
      print $sql ;
    }

  for ($i = 0 ; $i < 12 ; $i++)
    {
      $data_credit[$i] = $credits[substr("0".($i+1),-2)];
      $data_debit[$i] = $debits[substr("0".($i+1),-2)];
      $labels[$i] = $i+1;	  
    }

  $width = 750;
  $height = 350;
  
  $graph = new Graph($width, $height,"auto");    
  $graph->SetScale("textlin");
  
  $graph->yaxis->scale->SetGrace(2);
  //$graph->SetFrame(1);
  $graph->img->SetMargin(60,20,20,35);
  
  $bsplot = new BarPlot($data_debit);  
  $bsplot->SetColor("red");
  
  $beplot = new BarPlot($data_credit);  
  $beplot->SetColor("green");

  $bg = new GroupBarPlot(array($beplot, $bsplot));

  $graph->title->Set("Mouvements $year");
  
  $graph->xaxis->SetTickLabels($labels);   
  
  $graph->Add($bg);
  $graph->img->SetImgFormat("png");
  
  $file= DOL_DATA_ROOT."/graph/banque/mouvement.$account.$year.png";
  
  $graph->Stroke($file);
}

?>
