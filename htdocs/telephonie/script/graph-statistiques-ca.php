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


$img_root = DOL_DATA_ROOT."/graph/telephonie/";

/**********************************************************************/
/*
/* Stats sur les factures
/*
/*
/**********************************************************************/

$sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_stats";
$sql .= " WHERE graph IN ('factures.facture_moyenne','factures.ca_mensuel','factures.nb_mensuel')";
$resql = $db->query($sql);


$sql = "SELECT date_format(tf.date,'%m'), sum(tf.cout_vente), sum(tf.cout_achat), sum(tf.gain), count(tf.cout_vente)";

$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as tf";
$sql .= " GROUP BY date_format(tf.date,'%Y%m') ASC ";

$resql = $db->query($sql);

if ($resql)
{
  $cout_vente_type = array();
  $cout_vente = array();
  $cout_vente_prev = array();
  $cout_vente_autr = array();
  $cout_vente_moyen = array();
  $nb_factures = array();
  $jour_semaine_nb = array();
  $jour_semaine_duree = array();
  $gain = array();
  $gain_moyen = array();

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
$graph = new GraphBar ($db, $file);
$graph->titre = "Chiffre d'affaire par mois en euros HT";
$graph->width = 440;
$graph->GraphDraw($file, $cout_vente, $labels);

$file = $img_root . "/factures/facture_moyenne.png";
$graph = new GraphBar ($db, $file, $labels);
$graph->titre = "Facture moyenne";
$graph->barcolor = "blue";
$graph->width = 440;
$graph->GraphDraw($file, $cout_vente_moyen, $labels);

$file = $img_root . "/factures/gain_mensuel.png";
$graph = new GraphBar ($db, $file);
$graph->titre = "Gain par mois en euros HT";
$graph->width = 440;
$graph->GraphDraw($file, $gain, $labels);

$file = $img_root . "/factures/gain_moyen.png";
$graph = new GraphBar ($db, $file);
$graph->titre = "Gain moyen par facture par mois";
$graph->width = 440;
$graph->barcolor = "blue";
$graph->GraphDraw($file, $gain_moyen, $labels);

$file = $img_root . "/factures/nb_facture.png";
$graph = new GraphBar ($db, $file);
$graph->titre = "Nb de facture mois";
$graph->width = 440;
$graph->barcolor = "yellow";
$graph->GraphDraw($file, $nb_factures, $labels);



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
  $cout_vente_autre[$i] = $cva[$labl];
  $cout_achat[$i] = $cvc[$labl];
  $labels[$i] = substr($labl, -2);
  $i++;
}


$file = $img_root . "/factures/ca_mensuel_preleve.png";
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
