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

$datetime = time();
$date = strftime("%d%h%Y%Hh%Mm%S",$datetime);
$year = strftime("%Y", $datetime);

$img_root = DOL_DATA_ROOT."/graph/telephonie";

$month = array();
$month[1] = 'J';
$month[2] = 'F';
$month[3] = 'M';
$month[4] = 'A';
$month[5] = 'M';
$month[6] = 'J';
$month[7] = 'J';
$month[8] = 'A';
$month[9] = 'S';
$month[10] = 'O';
$month[11] = 'N';
$month[12] = 'D';

/**********************************************************************/
/*
/* Stats sur les factures
/*
/*
/**********************************************************************/

$sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_stats";
$sql .= " WHERE graph IN ('factures.facture_moyenne','factures.ca_mensuel','factures.nb_mensuel')";
$sql .= " AND legend like '".$this->year."%';";
$resql = $db->query($sql);

$sql = "SELECT date_format(tf.date,'%Y%m'), sum(tf.cout_vente)";
$sql .= ", sum(tf.cout_achat), sum(tf.gain), count(tf.cout_vente)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as tf";
$sql .= " WHERE date_format(tf.date,'%Y') ='".$year."'";
$sql .= " GROUP BY date_format(tf.date,'%Y%m') ASC ;";

$resql = $db->query($sql);

if ($resql)
{
  $cout_vente = array_pad(array(),12,0);
  $cout_vente_prev = array();
  $cout_vente_autr = array();
  $cout_vente_moyen = array();
  $nb_factures = array_pad(array(),12,0);
  $jour_semaine_nb = array();
  $jour_semaine_duree = array();
  $gain = array_pad(array(),12,0);
  $gain_moyen = array_pad(array(),12,0);
  $labels = array_pad(array(),12,0);
  $short_labels = array_pad(array(),12,0);

  $num = $db->num_rows($resql);
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);	

      $cout_vente[$i] = $row[1];

      if ($row[5] == 3)
	{
	  $cout_vente_prev[$i] = $row[1];
	}
      else
	{
	  $cout_vente_autr[$i] = $row[1];
	}

      $gain[$i] = $row[3];
      $gain_moyen[$i] = ($row[3]/$row[4]);
      $cout_vente_moyen[$i] = ($row[1]/$row[4]);
      $nb_factures[$i] = $row[4];
      $labels[$i] = $row[0];
      $short_labels[$i] = $month[(substr($row[0],-2)*1)];

      $sqli = " INSERT INTO ".MAIN_DB_PREFIX."telephonie_stats";
      $sqli .= " (graph, ord, legend, valeur) VALUES (";
      $sqli .= "'factures.ca_mensuel','".$i."','".$labels[$i]."','".$cout_vente[$i]."')";     
      if (!$resqli = $db->query($sqli)) print $db->error();
      
      $sqli = " INSERT INTO ".MAIN_DB_PREFIX."telephonie_stats";
      $sqli .= " (graph, ord, legend, valeur) VALUES (";
      $sqli .= "'factures.nb_mensuel','".$i."','".$labels[$i]."','".$nb_factures[$i]."')";      
      if (!$resqli = $db->query($sqli)) print $db->error();

      $sqli = " INSERT INTO ".MAIN_DB_PREFIX."telephonie_stats";
      $sqli .= " (graph, ord, legend, valeur) VALUES (";
      $sqli .= "'factures.facture_moyenne','".$i."','".$labels[$i]."','".$cout_vente_moyen[$i]."')";
      if (!$resqli = $db->query($sqli)) print $db->error();

      $i++;
    }
}
else
{
  print $db->error();
}
$file = $img_root . "/factures/ca_mensuel.png";
if ($verbose) print "Graph $file\n";
$graph = new GraphBar ($db, $file);
$graph->titre = "Chiffre d'affaire par mois en euros HT";
$graph->width = 440;
$graph->GraphDraw($file, $cout_vente, $short_labels);

$file = $img_root . "/factures/facture_moyenne.png";
if ($verbose) print "Graph $file\n";
$graph = new GraphBar ($db, $file, $labels);
$graph->titre = "Facture moyenne";
$graph->barcolor = "blue";
$graph->width = 440;
$graph->GraphDraw($file, $cout_vente_moyen, $short_labels);

$file = $img_root . "/factures/gain_mensuel.$year.png";
if ($verbose) print "Graph $file\n";
$graph = new GraphBar ($db, $file);
$graph->titre = "Marge en euros HT $year";
$graph->width = 440;
$graph->GraphDraw($file, $gain, $short_labels);

$file = $img_root . "/factures/gain_moyen.$year.png";
if ($verbose) print "Graph $file\n";
$graph = new GraphBar ($db, $file);
$graph->titre = "Marge moyenne par facture $year";
$graph->width = 440;
$graph->barcolor = "blue";
$graph->GraphDraw($file, $gain_moyen, $short_labels);

$file = $img_root . "/factures/nb_facture.$year.png";
if ($verbose) print "Graph $file\n";
$graph = new GraphBar ($db, $file);
$graph->titre = "Nb de facture mois $year";
$graph->width = 440;
$graph->barcolor = "yellow";
$graph->GraphDraw($file, $nb_factures, $short_labels);


$sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_stats";
$sql .= " WHERE graph IN ('factures.ca_mensuel_preleve','factures.ca_mensuel_autre')";
$resql = $db->query($sql);


$sql = "SELECT date_format(tf.date,'%Y%m'), sum(tf.cout_vente)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as tf";
$sql .= " , ".MAIN_DB_PREFIX."facture as f";
$sql .= " WHERE tf.fk_facture = f.rowid";
$sql .= " AND f.fk_mode_reglement = 3";
$sql .= " GROUP BY date_format(tf.date,'%Y%m') ASC ";

$resql = $db->query($sql);

if ($resql)
{
  $cvp = array();
  $num = $db->num_rows($resql);
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);	

      $cvp[$row[0]] = $row[1];

      $sqli = " INSERT INTO ".MAIN_DB_PREFIX."telephonie_stats";
      $sqli .= " (graph, ord, legend, valeur) VALUES (";
      $sqli .= "'factures.ca_mensuel_preleve','".$i."','".$row[0]."','".$row[1]."')";     
      if (!$resqli = $db->query($sqli)) print $db->error();

      $i++;
    }
}
else
{
  print $db->error();
}

$sql = "SELECT date_format(tf.date,'%Y%m'), sum(tf.cout_vente)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as tf";
$sql .= " , ".MAIN_DB_PREFIX."facture as f";
$sql .= " WHERE tf.fk_facture = f.rowid";
$sql .= " AND f.fk_mode_reglement <> 3";
$sql .= " GROUP BY date_format(tf.date,'%Y%m') ASC ";

$resql = $db->query($sql);

if ($resql)
{
  $cva = array();
  $num = $db->num_rows($resql);
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);	

      $cva[$row[0]] = $row[1];

      $sqli = " INSERT INTO ".MAIN_DB_PREFIX."telephonie_stats";
      $sqli .= " (graph, ord, legend, valeur) VALUES (";
      $sqli .= "'factures.ca_mensuel_autre','".$i."','".$row[0]."','".$row[1]."')";     
      if (!$resqli = $db->query($sqli)) print $db->error();

      $i++;
    }
}
else
{
  print $db->error();
}

$sql = "SELECT date_format(tf.date,'%Y%m'), sum(tf.fourn_montant)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details as tf";
$sql .= " GROUP BY date_format(tf.date,'%Y%m') ASC ";

$resql = $db->query($sql);

if ($resql)
{
  $cvc = array();
  $num = $db->num_rows($resql);
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);	
      $cvc[$row[0]] = $row[1];

      $i++;
    }
}
else
{
  print $db->error();
}

$i = 0;
foreach ($labels as $labl)
{
  $cout_vente_prelev[$i] = $cvp[$labl];
  $cout_vente_autre[$i]  = $cva[$labl];
  $cout_achat[$i]        = $cvc[$labl];
  $labels[$i] = substr($labl, -2);
  $i++;
}


$file = $img_root . "factures/ca_mensuel_preleve.png";

$graph = new GraphBarAccumul ($db, $file);
$graph->titre = "Chiffre d'affaire par méthode de réglement";
$graph->width = 640;
$graph->height = 480;
$graph->barcolor = "yellow";

$xdatas[0] = array($cout_vente_prelev, $cout_vente_autre);
$xdatas[1] = array($cout_achat);

$graph->legend[0][0] = "Factures prélevées";
$graph->legend[0][1] = "Factures non-prélevées";
$graph->legend[1][0] = "Coût fournisseur";

$graph->add_datas($xdatas);

$graph->GraphDraw($file, $labels, $cout_vente);

?>
