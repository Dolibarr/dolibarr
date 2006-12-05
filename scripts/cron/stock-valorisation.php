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
 *
 */
$sql  = "SELECT e.rowid as ref, sum(ps.reel * p.price) as valo";
$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e,".MAIN_DB_PREFIX."product_stock as ps,".MAIN_DB_PREFIX."product as p";
$sql .= " WHERE ps.fk_entrepot = e.rowid AND ps.fk_product = p.rowid";
$sql .= " GROUP BY e.rowid";

$resql = $db->query($sql) ;

if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      $sqli = "INSERT INTO ".MAIN_DB_PREFIX."entrepot_valorisation";
      $sqli .= " VALUES (now(),$row[0],$row[1])";
     
      $resqli = $db->query($sqli);
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
$sql  = "SELECT sum(value)";
$sql .= " FROM ".MAIN_DB_PREFIX."entrepot_valorisation as e";
$sql .= " GROUP BY date_calcul";
$sql .= " ORDER BY date_calcul ASC";

$resql = $db->query($sql) ;

if ($resql)
{
  $i = 0;
  while ($row = $db->fetch_row($resql))
    {
      $values[$i] = $row[0];
      if ($verbose)
	print $values[$i]."\n";
      $i++;
    }
  $db->free($resql);
}
else
{
  print $sql;
}


require_once DOL_DOCUMENT_ROOT."/../external-libs/Artichow/LinePlot.class.php";

$graph = new Graph(800, 600);
if (isset($this->title)) $graph->title->set($this->title);

$graph->border->hide();
    
$color = new Color(244,244,244);

$graph->setAntiAliasing(TRUE);

$graph->setBackgroundColor( $color );



$plot = new LinePlot($values);
$plot->setSize(1, 0.96);
$plot->setCenter(0.5, 0.52);



$plot->xAxis->setLabelText($legends);
$plot->xAxis->label->setFont(new Tuffy(7));

$graph->add($plot);

$file = DOL_DATA_ROOT."/graph/entrepot/entrepot.png";

$graph->draw($file);

?>
