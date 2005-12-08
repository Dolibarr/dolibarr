<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * Generation de graphiques
 *
 */
require ("../../master.inc.php");
$verbose = 0;
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie-tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/bar.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/baraccumul.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/camenbert.class.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/ca.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/gain.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/heureappel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/joursemaine.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/camoyen.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/appelsdureemoyenne.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/comm.nbmensuel.class.php");

$error = 0;

$img_root = DOL_DATA_ROOT."/graph/telephonie";


$colors = array();
$colors[10] = 'yellow';
$colors[11] = 'red';
$months = array(10,11);

print "$month\n";

$sql = "SELECT distinct ligne";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";

$resql = $db->query($sql);
$total = 0;
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  while ($row = $db->fetch_row($resql))
    {
      $ligne = new LigneTel($db);
      $ligne->fetch($row[0]);

      $data = array();
      $datas = array();
      $moydata = array();
      $moydatas = array();
      
      $sqla = "SELECT date, sum(duree)";
      $sqla .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
      $sqla .= " WHERE ligne = '".$ligne->numero."'";
      $sqla .= " GROUP BY date ASC;";
      
      $resqla = $db->query($sqla);
      $total = 0;
      if ($resqla)
	{
	  $num = $db->num_rows($resqla);
	  $i = 0;
	  
	  while ($rowa = $db->fetch_row($resqla))
	    {
	      $jour = (substr($rowa[0],0,2) * 1);
	      $data[$jour] = ($rowa[1]/60);
	      $total = $total + $data[$jour];
	      $i++;
	      $moydata[$jour] = $total / $i;
	    }
	}

      for ($i = 0 ; $i < 31 ; $i++)
	{
	  $j = $i + 1;
	  $datas[$i] = 0;
	  $moydatas[$i] = 0;
	  if ($data[$j])
	    $datas[$i] = $data[$j];
	  if ($moydata[$j])
	    $moydatas[$i] = $moydata[$j];
	  $labels[$i] = $j;
	}
      
      $img_root = DOL_DATA_ROOT."/graph/".substr($ligne->id,-1)."/telephonie/ligne/";

      $file = $img_root . $ligne->id."/conso.png";
      print $ligne->id . " ".$ligne->numero."\n";
      $graph = new Graph(800, 400,"auto");    
      $graph->SetScale("textlin");
      $graph->yaxis->scale->SetGrace(20);
      $graph->SetFrame(true);
      $graph->img->SetMargin(50,20,20,35);
      $graph->xaxis->scale->SetGrace(20);
      
      $graph->title->Set("Consommation en cours");
      $graph->xaxis->SetTickLabels($labels);    
      
      $b2plot = new LinePlot($datas);
      $b2plot->SetWeight(2);
      $b2plot->SetColor("red");
      $graph->Add($b2plot);
      
      $lineplot = new LinePlot($moydatas);    
      $lineplot->SetColor("blue");
      $graph->Add($lineplot);
      
      $graph->img->SetImgFormat("png");
      $graph->Stroke($file);
    }
}
else
{
  print $db->error();
}


?>
