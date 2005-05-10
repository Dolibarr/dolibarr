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

/***********************************************************************/
/*
/* Chiffre d'affaire mensuel
/*
/***********************************************************************/

$file = $img_root . "ca/ca.mensuel.png";
print "Graph : Chiffre d'affaire mensuel $file\n";
$graphca = new GraphCa($db, $file);
$graphca->GraphDraw();

/************************************************************************/
/*
/* Chiffre d'affaire moyen
/*
/*
/************************************************************************/

print "\nGraph ca moyen\n";

$file = $img_root . "ca/gain_moyen_par_client.png";
$graphgain = new GraphCaMoyen ($db, $file);

$graphgain->show_console = 0 ;
$graphgain->GraphDraw();

/*************************************************************************/
/*
/* Stats sur les communications
/*
/*
/*************************************************************************/

$sql = "SELECT ".$db->pdate("date")." as date, duree";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";

if ($db->query($sql))
{
  $heure_appel = array();
  $jour_semaine_nb = array();
  $jour_semaine_duree = array();

  $num = $db->num_rows();
  print "$num lignes de comm a traiter\n";
  $i = 0;

  while ($i < $num)
    {
      $obj = $db->fetch_object();	

      $h = strftime("%H",$obj->date) * 1; // suppression du 0

      $heure_appel_nb[$h]++;
      $heure_appel_duree[$h] += $obj->duree;

      $u = strftime("%u",$obj->date) - 1; // 1 pour Lundi

      $jour_semaine_nb[$u]++;
      $jour_semaine_duree[$u] += $obj->duree;

      $i++;
    }
}

$file = $img_root . "communications/heure_appel_nb.png";
$graphha = new GraphHeureAppel ($db, $file);
$graphha->GraphDraw($heure_appel_nb);


$file = $img_root . "communications/joursemaine_nb.png";
$graphha = new GraphJourSemaine ($db, $file);
$graphha->GraphDraw($jour_semaine_nb);

repart_comm($db);

$year = strftime("%Y", $datetime);
$month = strftime("%m", $datetime);

for ($i = 1 ; $i < 4 ; $i++)
{
  $month = $month - 1;

  if ($month == 0)
    {
      $year = $year - 1;
      $month = 12;
    }

  repart($db,$year, $month);
  repart_comm($db,$year, $month);

}


function repart_comm($db, $year = 0, $month = 0)
{
  print "Répartition des communications\n";

  $sql = "SELECT duree, numero";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";

  if ($year && $month)
    {
      print "Répartition des communications pour $month/$year\n";
      $month = substr("00".$month, -2);
      $sql .= " WHERE date_format(date,'%Y%m') = '$year$month'";
    }
  
  if ($db->query($sql))
    {
      $labels_duree = array();
      $repart_duree = array(0,0,0,0,0,0);
      $repart_dureelong = array(0,0);

      $labels_dest= array();
      $repart_dest = array(0,0,0);
      $repart_dest_temps = array(0,0,0);

      $num = $db->num_rows();
      
      $i = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row();	
	  
	  if ($row[0] < 10)
	    {
	      $repart_duree[0]++;
	    }
	  elseif ($row[0] >= 10 && $row[0] < 30)
	    {
	      $repart_duree[1]++;
	    }
	  elseif ($row[0] >= 30 && $row[0] < 60)
	    {
	      $repart_duree[2]++;
	    }
	  elseif ($row[0] >= 60 && $row[0] < 120)
	    {
	      $repart_duree[3]++;
	    }
	  elseif ($row[0] >= 120 && $row[0] < 300)
	    {
	      $repart_duree[4]++;
	    }
	  else
	    {
	      $repart_duree[5]++;
	    }
	  
	  if ($row[0] < 600)
	    {
	      $repart_dureelong[0]++;
	    }
	  else
	    {
	      $repart_dureelong[1]++;
	    }

	  if (substr($row[1],0,2) == '00')
	    {
	      $repart_dest[0]++;
	      $repart_dest_temps[0] += $row[0];
	    }
	  elseif (substr($row[1],0,2) == '06')
	    {
	      $repart_dest[1]++;
	      $repart_dest_temps[1] += $row[0];
	    }
	  else
	    {
	      $repart_dest[2]++;
	      $repart_dest_temps[2] += $row[0];
	    }
	  $i++;
	}
    }
  else
    {
      print $sql ;
    }

  if ($num > 0)
    {  
      $labels_duree[0] = "< 10 sec";
      $labels_duree[1] = "10-30 sec";
      $labels_duree[2] = "30-60 sec";
      $labels_duree[3] = "60-120 sec";
      $labels_duree[4] = "120-300 sec";
      $labels_duree[5] = "> 300 sec";
      
      $labels_dureelong[0] = "< 600 sec";
      $labels_dureelong[1] = "> 600 sec";

      $labels_dest[0] = 'International';
      $labels_dest[1] = 'Mobile';
      $labels_dest[2] = 'Local/National';

      $filem  = DOL_DOCUMENT_ROOT."/telephonie/stats/communications/duree_repart.png";
      $filec  = DOL_DOCUMENT_ROOT."/telephonie/stats/communications/dureelong_repart.png";
      $filed  = DOL_DOCUMENT_ROOT."/telephonie/stats/communications/dest_repart.png";
      $filedt = DOL_DOCUMENT_ROOT."/telephonie/stats/communications/dest_temps_repart.png";
      
      if ($year && $month)
	{
	  $filem = DOL_DOCUMENT_ROOT."/telephonie/stats/communications/duree_repart-$year$month.png";
	  $filec = DOL_DOCUMENT_ROOT."/telephonie/stats/communications/dureelong_repart-$year$month.png";
	  $filed = DOL_DOCUMENT_ROOT."/telephonie/stats/communications/dest_repart-$year$month.png";
	  $filedt = DOL_DOCUMENT_ROOT."/telephonie/stats/communications/dest_temps_repart-$year$month.png";
	}
      
      $graphm  = new GraphCamenbert ($db, $filem);
      $graphc  = new GraphCamenbert ($db, $filec);
      $graphd  = new GraphCamenbert ($db, $filed);
      $graphdt = new GraphCamenbert ($db, $filedt);
      
      $graphm->titre = "Répartition du nombre de communications par duree";
      $graphc->titre = "Répartition du nombre de communications par duree";
      $graphd->titre = "Répartition du nombre de communications par destination";
      $graphdt->titre = "Répartition du nombre de communications par destination";
      
      if ($year && $month)
	{
	  $graphm->titre = "Répart. du nbre de communications par duree $month/$year";
	  $graphc->titre = "Répart. du nbre de communications par duree $month/$year";
	  $graphd->titre = "Répart. du nbre de communications par destination $month/$year";
	  $graphdt->titre = "Répart. du temps de communications par destination $month/$year";
	}

      $graphm->colors= array('#993333','#66cc99','#6633ff','#33ff33','#336699','#00ffff');     
      $graphd->colors= array('#FFC0FF','#FF00FF','#C000C0');
      $graphdt->colors= array('#FFFFC0','#FFFF0F','#C0C000');

      $graphm->GraphDraw($repart_duree, $labels_duree);  
      $graphc->GraphDraw($repart_dureelong, $labels_dureelong);  
      $graphd->GraphDraw($repart_dest, $labels_dest);  
      $graphdt->GraphDraw($repart_dest_temps, $labels_dest);  
    }
}

/***************************************************************************/
$sql = "SELECT date_format(date, '%Y%m'), count(distinct(ligne))";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
$sql .= " GROUP BY date_format(date, '%Y%m') ASC";

if ($db->query($sql))
{
  $nblignes = array();

  $num = $db->num_rows();

  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row();
      $nblignes[$i] = $row[1];
      $i++;
    }
}

/*
 *
 *
 */

$sql = "SELECT date_format(date, '%Y%m'), sum(duree), count(duree)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
$sql .= " GROUP BY date_format(date, '%Y%m') ASC";

$resql = $db->query($sql);

if ($resql)
{
  $durees = array();
  $kilomindurees = array();
  $durees_moyenne = array();
  $nombres = array();
  $labels = array();

  $num = $db->num_rows($resql);

  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);
      $labels[$i] = substr($row[0],4,2) . '/'.substr($row[0],2,2);
      $durees[$i] = $row[1];
      $kilomindurees[$i] = ($row[1]/60000);
      $durees_moyenne[$i] = ($row[1] / $row[2]);
      $nombres[$i] = $row[2];

      $nbappels_ligne[$i] = ($nombres[$i] / $nblignes[$i]);

      $i++;
    }
}

$file = $img_root . "communications/duree.png";
$graphgain = new GraphBar ($db, $file);
$graphgain->show_console = 0 ;
$graphgain->width = 480 ;
$graphgain->titre = "Nb minutes (milliers)";
print $graphgain->titre."\n";
$graphgain->GraphDraw($file, $kilomindurees, $labels);

$file = $img_root . "communications/nbappelsparligne.png";
$graphgain = new GraphBar ($db, $file);
$graphgain->show_console = 0 ;
$graphgain->titre = "Nb appels moyen par ligne";
print $graphgain->titre."\n";
$graphgain->barcolor = "pink";
$graphgain->GraphDraw($file, $nbappels_ligne, $labels);

$file = $img_root . "communications/dureemoyenne.png";
$graphgain = new GraphBar ($db, $file);
$graphgain->show_console = 0 ;
$graphgain->titre = "Durée moyenne d'un appel";
print $graphgain->titre."\n";
$graphgain->barcolor = "yellow";
$graphgain->GraphDraw($file, $durees_moyenne, $labels);

$file = $img_root . "communications/nombre.png";
$graphgain = new GraphBar ($db, $file);
$graphgain->show_console = 0 ;
$graphgain->titre = "Nombres d'appel mensuels";
print $graphgain->titre."\n";
$graphgain->GraphDraw($file, $nombres, $labels);

/* ---------------------------------------------- */

$sql = "SELECT date_format(date, '%Y%m'), sum(duree), count(duree)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
$sql .= " WHERE numero like '06%'";
$sql .= " GROUP BY date_format(date, '%Y%m') ASC";

if ($db->query($sql))
{
  $durees = array();
  $kilomindurees = array();
  $durees_moyenne = array();
  $nombres = array();
  $labels = array();

  $num = $db->num_rows();

  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row();
      $labels[$i] = substr($row[0],4,2) . '/'.substr($row[0],2,2);
      $durees[$i] = $row[1];
      $kilomindurees_mob[$i] = ($row[1]/60000);

      $i++;
    }
}

$file = $img_root . "communications/duree_mob.png";
$graphgain = new GraphBar ($db, $file);
$graphgain->show_console = 0 ;
$graphgain->titre = "Nb minutes -> portables (milliers)";
print $graphgain->titre."\n";
$graphgain->GraphDraw($file, $kilomindurees_mob, $labels);

/* ---------------------------------------------- */

$sql = "SELECT date_format(date, '%Y%m'), sum(duree), count(duree)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
$sql .= " WHERE numero like '00%'";
$sql .= " GROUP BY date_format(date, '%Y%m') ASC";

if ($db->query($sql))
{
  $durees = array();
  $kilomindurees_inter = array();
  $durees_moyenne = array();
  $nombres = array();
  $labels = array();

  $num = $db->num_rows();

  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row();
      $labels[$i] = substr($row[0],4,2) . '/'.substr($row[0],2,2);
      $durees[$i] = $row[1];
      $kilomindurees_inter[$i] = ($row[1]/60000);

      $i++;
    }
}

$file = $img_root . "communications/duree_inter.png";
$graphgain = new GraphBar ($db, $file);
$graphgain->show_console = 0 ;
$graphgain->titre = "Nb minutes -> inter (milliers)";
print $graphgain->titre."\n";
$graphgain->GraphDraw($file, $kilomindurees_inter, $labels);

/* ---------------------------------------------- */

$sql = "SELECT date_format(date, '%Y%m'), sum(duree)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
$sql .= " WHERE substring(numero, 1, 2) <> '00'";
$sql .= " AND   substring(numero, 1, 2) <> '06'";
$sql .= " GROUP BY date_format(date, '%Y%m') ASC";

if ($db->query($sql))
{
  $kilomindurees_loc = array();
  $labels = array();
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row();
      $labels[$i] = substr($row[0],4,2) . '/'.substr($row[0],2,2);
      $kilomindurees_loc[$i] = ($row[1]/60000);

      $i++;
    }
}

$file = $img_root . "communications/duree_loc.png";
$graphgain = new GraphBar ($db, $file);
$graphgain->show_console = 0 ;
$graphgain->titre = "Nb minutes -> local/national (milliers)";
print $graphgain->titre."\n";
$graphgain->GraphDraw($file, $kilomindurees_loc, $labels);


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
print $graph->titre."\n";
$graph->GraphDraw($file, $cout_vente, $labels);

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

/*
 * Répartition des factures
 *
 *
 */
repart($db);

function repart($db, $year = 0, $month = 0)
{
  print "Répartition des factures\n";

  $sql = "SELECT cout_vente, gain";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture";

  if ($year && $month)
    {
      print "Répartition des factures pour $month/$year\n";
      $month = substr("00".$month, -2);
      $sql .= " WHERE date_format(date,'%Y%m') = '$year$month'";
    }
  
  if ($db->query($sql))
    {
      $labels = array();
      $repart_montant = array();
      $num = $db->num_rows();
      
      $i = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row();	
	  
	  if ($row[0] < 10)
	    {
	      $repart_montant[0]++;
	    }
	  elseif ($row[0] >= 10 && $row[0] < 20)
	    {
	      $repart_montant[1]++;
	    }
	  elseif ($row[0] >= 20 && $row[0] < 40)
	    {
	      $repart_montant[2]++;
	    }
	  elseif ($row[0] >= 40 && $row[0] < 70)
	    {
	      $repart_montant[3]++;
	    }
	  elseif ($row[0] >= 70 && $row[0] < 100)
	    {
	      $repart_montant[4]++;
	    }
	  else
	    {
	      $repart_montant[5]++;
	    }
	  
	  
	  if ($row[1] < 1)
	    {
	      $repart_gain[0]++;
	    }
	  elseif ($row[1] >= 1 && $row[1] < 5)
	    {
	      $repart_gain[1]++;
	    }
	  elseif ($row[1] >= 5 && $row[1] < 10)
	    {
	      $repart_gain[2]++;
	    }
	  elseif ($row[1] >= 10 && $row[1] < 20)
	    {
	      $repart_gain[3]++;
	    }
	  elseif ($row[1] >= 20 && $row[1] < 50)
	    {
	      $repart_gain[4]++;
	    }
	  else
	    {
	      $repart_gain[5]++;
	    }
	  $i++;
	}
    }
  else
    {
      print $sql ;
    }

  if ($num > 0)
    {  
      $labels_montant[0] = "< 10";
      $labels_montant[1] = "10-20";
      $labels_montant[2] = "20-40";
      $labels_montant[3] = "40-70";
      $labels_montant[4] = "70-100";
      $labels_montant[5] = "> 100";
      
      $labels_gain[0] = "< 1";
      $labels_gain[1] = "1-5";
      $labels_gain[2] = "5-10";
      $labels_gain[3] = "10-20";
      $labels_gain[4] = "20-50";
      $labels_gain[5] = "> 50";
      
      $filem = DOL_DOCUMENT_ROOT."/telephonie/stats/factures/montant_repart.png";
      $fileg = DOL_DOCUMENT_ROOT."/telephonie/stats/factures/gain_repart.png";
      
      if ($year && $month)
	{
	  $filem = DOL_DOCUMENT_ROOT."/telephonie/stats/factures/montant_repart-$year$month.png";
	  $fileg = DOL_DOCUMENT_ROOT."/telephonie/stats/factures/gain_repart-$year$month.png";
	}
      
      $graphm = new GraphCamenbert ($db, $filem);
      $graphg = new GraphCamenbert ($db, $fileg);
      
      
      $graphm->titre = "Répartition du nombre de factures par montant";
      $graphg->titre = "Répartition du nombre de factures par gain";
      
      if ($year && $month)
	{
	  $graphm->titre = "Répart. du nbre de factures par montant $month $year";
	  $graphg->titre = "Répart. du nbre de factures par gain $month $year";
	}

      $graphm->colors= array('#993333','#66cc99','#6633ff','#33ff33','#336699','#00ffff');
      
      //      $graphm->GraphDraw($filem, $repart_montant, $labels_montant);  
      //      $graphg->GraphDraw($fileg, $repart_gain, $labels_gain);
    }
}
?>
