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
require ("../../../master.inc.php");

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

$data = array();
$xdata = array();
$colors = array();
$colors[10] = 'yellow';
$colors[11] = 'red';
$months = array(10,11);
foreach ($months as $month)
{
  print "$month\n";
  $sql = "SELECT date_format(date,'%d'), sum(duree)";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
  $sql .= " WHERE date_format(date,'%Y%m') ='2005".$month."'";
  $sql .= " GROUP BY date_format(date,'%Y%m%d') ASC ;";
  
  $resql = $db->query($sql);
  
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      while ($row = $db->fetch_row($resql))
	{
	  $xdata[($row[0]*1)] = ($row[1]/60000);
	  $labels[$i] = $row[0];
	  $i++;
	}
    }
  else
    {
      print $db->error();
    }

  for ($i = 1 ; $i < 32 ; $i++)
    {
      $data[$month][$i] = $xdata[$i];
      $labels[$i] = $i;
    }

}




$file = "/tmp/conso-jour.png";

$graph = new Graph(800, 400,"auto");    
$graph->SetScale("textlin");
$graph->yaxis->scale->SetGrace(20);
$graph->SetFrame(true);
$graph->img->SetMargin(50,20,20,35);
$graph->xaxis->scale->SetGrace(20);

$graph->title->Set("Nb minutes en kilos");
$graph->xaxis->SetTickLabels($labels);    

foreach ($months as $month)
{
  $b2plot = new LinePlot($data[$month]);    
  $b2plot->SetColor($colors[$month]);
  $lineplot->SetWeight(2);
  $graph->Add($b2plot);
}
$graph->img->SetImgFormat("png");
$graph->Stroke($file);


?>
