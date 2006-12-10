<?PHP
/* Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * Crée les graphiques pour les fournisseurs
 *
 */
require ("../../htdocs/master.inc.php");

$verbose = 0;

for ($i = 1 ; $i < sizeof($argv) ; $i++)
{
  if ($argv[$i] == "-v")
    {
      $verbose = 1;
    }
  if ($argv[$i] == "-vv")
    {
      $verbose = 2;
    }
  if ($argv[$i] == "-vvv")
    {
      $verbose = 3;
    }
}
/*
 *
 */
$dir = DOL_DATA_ROOT."/graph/fournisseur";
if (!is_dir($dir) )
{
  if (! @mkdir($dir,0755))
    {
      die ("Can't create $dir\n");
    }
}
/*
 *
 */
$sql  = "SELECT distinct(fk_societe)";
$sql .= " FROM ".MAIN_DB_PREFIX."fournisseur_ca";

$resql = $db->query($sql) ;
$fournisseurs = array();
if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      $fdir = $dir.'/'.get_exdir($row[0],3);
      if ($verbose)
	print $fdir."\n";
      create_exdir($fdir);
      $fournisseurs[$row[0]] = $fdir;
    }
  $db->free($resql);
}
else
{
  print $sql;
}
/*
 *
 */

foreach ($fournisseurs as $id => $fdir)
{
  $values = array();
  $legends = array(); 
  $sql  = "SELECT year, ca_genere";
  $sql .= " FROM ".MAIN_DB_PREFIX."fournisseur_ca";
  $sql .= " WHERE fk_societe = $id";
  $sql .= " ORDER BY year ASC";

  $resql = $db->query($sql) ;
  
  if ($resql)
    {
      $i = 0;
      while ($row = $db->fetch_row($resql))
	{
	  $values[$i]  = $row[1];
	  $legends[$i] = $row[0];
	  
	  $i++;
	}
      $db->free($resql);
    }
  else
    {
      print $sql;
    }

  require_once DOL_DOCUMENT_ROOT."/../external-libs/Artichow/BarPlot.class.php";
  
  $file = $fdir ."ca_genere-".$id.".png";
  $title = "CA généré en euros HT";
  
  graph_datas($file, $title, $values, $legends);
  if ($verbose)
    print "$file\n";
}

function graph_datas($file, $title, $values, $legends)
{
  $graph = new Graph(500, 200);
  $graph->title->set($title);
  $graph->title->setFont(new Tuffy(10));

  $graph->border->hide();
    
  $color = new Color(222,231,236);

  $graph->setAntiAliasing(TRUE);
  $graph->setBackgroundColor( $color );

  $plot = new BarPlot($values);

  $plot->setBarGradient(
			new LinearGradient(
					   new Color(244,244,244),
					   new Color(222,231,236),
					   90
					   )
			);

 $plot->setSpace(5, 5, NULL, NULL);

 $plot->barShadow->setSize(4);
 $plot->barShadow->setPosition(SHADOW_RIGHT_TOP);
 $plot->barShadow->setColor(new Color(180, 180, 180, 10));
 $plot->barShadow->smooth(TRUE);

 $plot->xAxis->setLabelText($legends);
 $plot->xAxis->label->setFont(new Tuffy(7));
  
  $graph->add($plot);
  $graph->draw($file);
}
?>
