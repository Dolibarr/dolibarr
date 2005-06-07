<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * Generation des graphiques
 *
 *
 *
 */
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie-tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/bar.class.php");
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

$sql = "SELECT date_format(date,'%Y%m'), sum(cout_vente), sum(cout_achat), sum(gain), count(cout_vente)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture";
$sql .= " GROUP BY date_format(date,'%Y%m') ASC ";

if ($db->query($sql))
{
  $cout_vente = array();
  $cout_vente_moyen = array();
  $nb_factures = array();
  $jour_semaine_nb = array();
  $jour_semaine_duree = array();
  $gain = array();
  $gain_moyen = array();

  $num = $db->num_rows();
  print "$num lignes de comm a traiter\n";
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row();	

      $cout_vente[$i] = $row[1];

      $gain[$i] = $row[3];
      $gain_moyen[$i] = ($row[3]/$row[4]);
      $cout_vente_moyen[$i] = ($row[1]/$row[4]);
      $nb_factures[$i] = $row[4];
      $labels[$i] = substr($row[0],4,2) . '/'.substr($row[0],2,2);
      $i++;
    }
}
$file = $img_root . "/factures/ca_mensuel.png";
$graph = new GraphBar ($db, $file);
$graph->titre = "Chiffre d'affaire par mois en euros HT";
$graph->width = 400;
print $graph->titre."\n";
$graph->GraphDraw($file, $cout_vente, $labels);

/*
$file = $img_root . "/factures/facture_moyenne.png";
$graph = new GraphBar ($db, $file, $labels);
$graph->titre = "Facture moyenne";
print $graph->titre."\n";
$graph->barcolor = "blue";
$graph->GraphDraw($file, $cout_vente_moyen, $labels);

$file = $img_root . "/factures/gain_mensuel.png";
$graph = new GraphBar ($db, $file);
$graph->titre = "Gain par mois en euros HT";
print $graph->titre."\n";
$graph->GraphDraw($file, $gain, $labels);

$file = $img_root . "/factures/gain_moyen.png";
$graph = new GraphBar ($db, $file);
$graph->titre = "Gain moyen par facture par mois";
print $graph->titre."\n";
$graph->barcolor = "blue";
$graph->GraphDraw($file, $gain_moyen, $labels);

$file = $img_root . "/factures/nb_facture.png";
$graph = new GraphBar ($db, $file);
$graph->titre = "Nb de facture mois";
print $graph->titre."\n";
$graph->barcolor = "yellow";
$graph->GraphDraw($file, $nb_factures, $labels);

*/

?>
