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
 * Calcul la valorisation du stock
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
$sql  = "SELECT distinct(fk_entrepot)";
$sql .= " FROM ".MAIN_DB_PREFIX."entrepot_valorisation";

$resql = $db->query($sql) ;
$entrepots = array();
if ($resql)
{
  $i = 0;
  while ($row = $db->fetch_row($resql))
    {
      $entrepots[$row[0]] = $row[0];
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
for ($i = 0 ; $i < 366 ; $i++)
{
  foreach ($entrepots as $key => $ent)
    {
      $values[$key][$i] = 0;
    }
  $legends[$i] = strftime('%b',mktime(12,12,12,1,1,2006) + ($i * 3600 * 24));
}

/*
 *
 */
$sql  = "SELECT date_format(date_calcul,'%j'), value, fk_entrepot";
$sql .= " FROM ".MAIN_DB_PREFIX."entrepot_valorisation as e";
$sql .= " ORDER BY date_calcul ASC";

$resql = $db->query($sql) ;

if ($resql)
{
  $i = 0;
  while ($row = $db->fetch_row($resql))
    {
      $values[$row[2]][$row[0]] = $row[1];
      if ($verbose)
	print $values[$i]."\n";

      $values[0][$row[0]] += $row[1];

      $i++;
    }
  $db->free($resql);
}
else
{
  print $sql;
}

require_once DOL_DOCUMENT_ROOT."/../external-libs/Artichow/LinePlot.class.php";

  foreach ($entrepots as $key => $ent)
    {

      $file = DOL_DATA_ROOT."/graph/entrepot/entrepot-".$key.".png";
      $title = 'Valorisation du stock (euros HT)';

      graph_datas($file, $title, $values[$key], $legends);
    }



function graph_datas($file, $title, $values, $legends)
{

  $graph = new Graph(800, 250);
  $graph->title->set($title);
  $graph->title->setFont(new Tuffy(10));

  $graph->border->hide();
    
  $color = new Color(244,244,244);

  $graph->setAntiAliasing(TRUE);
  $graph->setBackgroundColor( $color );

  //$plot->yAxis->title->set("euros");

  $plot = new LinePlot($values);
  $plot->setSize(1, 0.96);
  $plot->setCenter(0.5, 0.52);

  // Change line color
  $plot->setColor(new Color(0, 0, 150, 20));

  // Set line background gradient
  $plot->setFillGradient(
			 new LinearGradient(
					    new Color(150, 150, 210),
					    new Color(230, 230, 255),
					    90
					    )
			 );
  
  $plot->xAxis->setLabelText($legends);
  $plot->xAxis->label->setFont(new Tuffy(7));
  
  $plot->grid->hideVertical(TRUE);
  $plot->xAxis->setLabelInterval(31);

  $graph->add($plot);

  $graph->draw($file);
}
?>
