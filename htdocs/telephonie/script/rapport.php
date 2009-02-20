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
 *
 * Génération des rapports
 *
 */
print "Mem : ".memory_get_usage() ."\n";
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie-tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/numero.class.php");
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_workbook.inc.php");
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_worksheet.inc.php");


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

//$month = "02";
//$year = "2005";

/*
 * Lecture des groupes de lignes
 *
 */
$groupes = array();
$numdatas = array();
$lignes = array();

$tarif_vente = new TelephonieTarif($db, 1, "vente");

$sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."telephonie_groupeligne";
  
$resql = $db->query($sql);

if ($resql)
{
  $nums = $db->num_rows($resql);
  $i = 0;
  while($i < $nums)
    {
      $row = $db->fetch_row($resql);
      $groupes[$row[0]] = $row[1];
      print "Mem : ".memory_get_usage() ."\n";
      $i++;
    }
  $db->free($resql);
}


foreach ($groupes as $keygroupe => $groupe)
{

  $dir = DOL_DATA_ROOT . "/telephonie/rapports/".$keygroupe;
  
  if (! file_exists($dir))
    {
      umask(0);
      if (! @mkdir($dir, 0755))
	{
	  print "Erreur: Le répertoire '$dir' n'existe pas et Dolibarr n'a pu le créer.";
	}
    }	


  $fname = $dir."/".substr('00'.$month, -2)."-".$year.".xls";

  print "Open $fname\n";

  $workbook = &new writeexcel_workbook($fname);

  $formatcc =& $workbook->addformat();
  $formatcc->set_align('center');
  $formatcc->set_align('vcenter');  

  $fclient =& $workbook->addformat();
  $fclient->set_align('left');
  $fclient->set_align('vcenter');  
  $fclient->set_border(1);

  $fcode =& $workbook->addformat();
  $fcode->set_align('center');
  $fcode->set_align('vcenter');  
  $fcode->set_border(1);

  $fligne =& $workbook->addformat();
  $fligne->set_align('center');
  $fligne->set_align('vcenter');  
  $fligne->set_right(6);
  $fligne->set_bottom(1);

  $fnb =& $workbook->addformat();
  $fnb->set_align('vcenter');
  $fnb->set_align('center');
  $fnb->set_top(1);
  $fnb->set_right(1);
  $fnb->set_bottom(1);
  $fnb->set_left(6);

  $fduree =& $workbook->addformat();
  $fduree->set_align('center');
  $fduree->set_align('vcenter');  
  $fduree->set_border(1);

  $fcout =& $workbook->addformat();
  $fcout->set_align('center');
  $fcout->set_align('vcenter');
  $fcout->set_num_format('0.00');
  $fcout->set_border(1);

  $fmoy =& $workbook->addformat();
  $fmoy->set_align('center');
  $fmoy->set_align('vcenter');  
  $fmoy->set_right(6);
  $fmoy->set_bottom(1);
  $fmoy->set_num_format('0.0000');

  $format_titre =& $workbook->addformat();
  $format_titre->set_align('center');
  $format_titre->set_align('vcenter');  
  $format_titre->set_bold();
  $format_titre->set_border(1);

  $format_titre_nb =& $workbook->addformat();
  $format_titre_nb->set_align('center');
  $format_titre_nb->set_align('vcenter');  
  $format_titre_nb->set_bold();
  $format_titre_nb->set_right(1);
  $format_titre_nb->set_top(1);
  $format_titre_nb->set_bottom(1);
  $format_titre_nb->set_left(6);

  $format_titre_moy =& $workbook->addformat();
  $format_titre_moy->set_align('center');
  $format_titre_moy->set_align('vcenter');  
  $format_titre_moy->set_bold();
  $format_titre_moy->set_right(1);
  $format_titre_moy->set_top(1);
  $format_titre_moy->set_bottom(1);
  $format_titre_moy->set_right(6);

  $format_titre_agence1 =& $workbook->addformat();
  $format_titre_agence1->set_align('center');
  $format_titre_agence1->set_align('vcenter');  
  $format_titre_agence1->set_bold();
  $format_titre_agence1->set_right(1);
  $format_titre_agence1->set_left(1);
  $format_titre_agence1->set_top(1);
  $format_titre_agence1->set_merge();

  $format_titre_agence2 =& $workbook->addformat();
  $format_titre_agence2->set_align('center');
  $format_titre_agence2->set_align('vcenter');  
  $format_titre_agence2->set_bold();
  $format_titre_agence2->set_right(1);
  $format_titre_agence2->set_left(1);
  $format_titre_agence2->set_bottom(1);
  $format_titre_agence2->set_merge();


  $format_titre_nat1 =& $workbook->addformat();
  $format_titre_nat1->set_align('center');
  $format_titre_nat1->set_align('vcenter');  
  $format_titre_nat1->set_bold();
  $format_titre_nat1->set_left(6);
  $format_titre_nat1->set_merge();

  $format_titre_nat2 =& $workbook->addformat();
  $format_titre_nat2->set_align('center');
  $format_titre_nat2->set_align('vcenter');  
  $format_titre_nat2->set_bold();
  $format_titre_nat2->set_merge();

  $format_titre_nat3 =& $workbook->addformat();
  $format_titre_nat3->set_align('center');
  $format_titre_nat3->set_align('vcenter');  
  $format_titre_nat3->set_bold();
  $format_titre_nat3->set_right(6);
  $format_titre_nat3->set_merge();

  $format_titre_total1 =& $workbook->addformat();
  $format_titre_total1->set_align('center');
  $format_titre_total1->set_align('vcenter');  
  $format_titre_total1->set_bold();
  $format_titre_total1->set_left(1);
  $format_titre_total1->set_top(1);
  $format_titre_total1->set_bottom(1);
  $format_titre_total1->set_merge();

  $format_titre_total2 =& $workbook->addformat();
  $format_titre_total2->set_align('center');
  $format_titre_total2->set_align('vcenter');  
  $format_titre_total2->set_bold();
  $format_titre_total2->set_top(1);
  $format_titre_total2->set_bottom(1);
  $format_titre_total2->set_merge();

  $format_titre_total3 =& $workbook->addformat();
  $format_titre_total3->set_align('center');
  $format_titre_total3->set_align('vcenter');  
  $format_titre_total3->set_bold();
  $format_titre_total3->set_right(6);
  $format_titre_total3->set_top(1);
  $format_titre_total3->set_bottom(1);
  $format_titre_total3->set_merge();


  $formatc =& $workbook->addformat();
  $formatc->set_align('vcenter');
 

  $fnbBold =& $workbook->addformat();
  $fnbBold->set_align('vcenter');
  $fnbBold->set_bold();

  $formatr =& $workbook->addformat();
  $formatr->set_align('vcenter');
  $formatr->set_align('right');

  $fcoutBold =& $workbook->addformat();
  $fcoutBold->set_align('right');
  $fcoutBold->set_num_format('0.00');
  $fcoutBold->set_bold();

  $fgrey =& $workbook->addformat();
  $fgrey->set_fg_color('yellow');
  $fgrey->set_bold();
  $fgrey->set_align('left');
  $fgrey->set_pattern(0x1);

  $ftotal =& $workbook->addformat();
  $ftotal->set_bold();
  $ftotal->set_align('right');
  $ftotal->set_align('vcenter');
  $ftotal->set_pattern(0x1);

  /*
   * Chargement des numéros de datas
   *
   */
  
  $sql = "SELECT n.numero ";
  $sql .=" FROM ".MAIN_DB_PREFIX."telephonie_numdata as n";  
  $sql .= " WHERE n.fk_groupe = ".$keygroupe;
  
  $resql = $db->query($sql);
  
  if ($resql)
    {
      $nums = $db->num_rows($resql);
      $si = 0;
      while($si < $nums)
	{
	  $row = $db->fetch_row($resql);
	  $numdatas[$row[0]] = $row[0];
	  $si++;
	}
      $db->free($resql);
    }
  else
    {
      print $db->error();
    }
  
  /*
   * Boucle sur les mois
   */
  for ($imonth = 1 ; $imonth <= ($month + 1) ; $imonth++)
    {

      if ($imonth > $month)
	{
	  $page2 = &$workbook->addworksheet("Année $year");
	}
      else
	{
	  $page2 = &$workbook->addworksheet($year."-.".substr("00".$imonth,-2));
	}

      for ($a = 0 ; $a < 200 ; $a++)
	{
	  $page2->set_row($a,25); // A
	}

      $page2->set_column(0,0,40); // A
      $page2->set_column(1,1,10); // B
      $page2->set_column(2,2,13); // C
      
      $page2->set_column(3,3,7);  // D
      $page2->set_column(6,6,7); // G
      $page2->set_column(9,9,7); // J
      $page2->set_column(12,12,7); // M

      $page2->set_column(4,5,10); // E-F
      $page2->set_column(7,8,10);  // H-I
      $page2->set_column(10,11,10);  // K-L 
      $page2->set_column(13,14,10);  // N-O 

      $page2->write(0, 0,  "Agence/Filiale", $format_titre_agence1);
      $page2->write_blank(1, 0,  $format_titre_agence2);

      $page2->write(0, 1,  "Site", $format_titre);
      $page2->write(0, 2,  "Ligne", $format_titre);

      $page2->write(0, 3,  "Local/National", $format_titre_nat1);
      $page2->write_blank(0, 4, $format_titre_nat2);
      $page2->write_blank(0, 5, $format_titre_nat3);


      $page2->write(1, 3,  "Nb", $format_titre_nb);
      $page2->write(1, 4,  "Durée", $format_titre);
      $page2->write(1, 5,  "Coût", $format_titre);


      $page2->write(0, 6,  "Mobile SFR/Orange", $format_titre_nat1);
      $page2->write_blank(0, 7, $format_titre_nat2);
      $page2->write_blank(0, 8, $format_titre_nat3);


      $page2->write(1, 6,  "Nb", $format_titre_nb);
      $page2->write(1, 7,  "Durée", $format_titre);
      $page2->write(1, 8,  "Coût", $format_titre);


      $page2->write(0, 9,  "Mobile Bouygues", $format_titre_nat1);
      $page2->write_blank(0, 10, $format_titre_nat2);
      $page2->write_blank(0, 11, $format_titre_nat3);

      $page2->write(1, 9,  "Nb", $format_titre_nb);
      $page2->write(1, 10,  "Durée", $format_titre);
      $page2->write(1, 11,  "Coût", $format_titre);


      $page2->write(0, 12,  "Données", $format_titre_nat1);
      $page2->write_blank(0, 13, $format_titre_nat2);
      $page2->write_blank(0, 14, $format_titre_nat3);

      $page2->write(1, 12,  "Nb", $format_titre_nb);
      $page2->write(1, 13,  "Durée", $format_titre);
      $page2->write(1, 14,  "Coût", $format_titre);


      $page2->write(0, 15,  "Total", $format_titre_nat1);
      $page2->write_blank(0, 16, $format_titre_nat2);
      $page2->write_blank(0, 17, $format_titre_nat2);
      $page2->write_blank(0, 18, $format_titre_nat3);

      $page2->write(1, 15,  "Nb", $format_titre_nb);
      $page2->write(1, 16,  "Durée", $format_titre);
      $page2->write(1, 17,  "Coût", $format_titre);
      $page2->write(1, 18,  "Moyenne\ncoût/sec", $format_titre_moy);

      unset ($lignes);

      /*
       *
       *
       */
      $sql = "SELECT sl.rowid, sl.ligne";
      $sql .=" FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as sl";
      $sql .=" , ".MAIN_DB_PREFIX."telephonie_groupe_ligne as gl";
  
      $sql .= " WHERE gl.fk_ligne = sl.rowid";
      $sql .= " AND sl.statut <> 7";
      $sql .= " AND gl.fk_groupe = ".$keygroupe;

      $sql .= " ORDER BY sl.fk_soc_facture ASC";

      if ( $db->query($sql) )
	{
	  $nums = $db->num_rows();
	  $si = 0;
	  while($si < $nums)
	    {
	      $row = $db->fetch_row();

	      $lignes[$row[0]] = $row[1];

	      //print "Lecture $row[1]\n";
	      $si++;
	    }
	  $db->free();
	}
      else
	{
	  print $db->error();
	}

      /*
       *
       *
       */

      $xx = 2;
      $oldxx = $xx+1;
      $oldana = '';
      $oldfk_soc = '';
      $lines = array();

      $fksoc = 0;
      $tg = 0; // permet de gérer l'affichage du total groupe.

      $total_global_nb = 0;
      $total_global_duree = 0;
      $total_global_cout = 0;

      $total_global_mobile_nb = 0;
      $total_global_mobile_duree = 0;
      $total_global_mobile_cout = 0;

      $total_global_data_nb = 0;
      $total_global_data_duree = 0;
      $total_global_data_cout = 0;

      $total_global_inter_nb = 0;
      $total_global_inter_duree = 0;
      $total_global_inter_cout = 0;

      $total_global_national_nb = 0;
      $total_global_national_duree = 0;
      $total_global_national_cout = 0;
   
      $total_groupe_national_nb = 0;
      $total_groupe_national_duree = 0;
      $total_groupe_national_cout = 0;
  
      $total_groupe_mobile_sfr_nb = 0;
      $total_groupe_mobile_sfr_duree = 0;
      $total_groupe_mobile_sfr_cout = 0;
  
      $total_groupe_mobile_orange_nb = 0;
      $total_groupe_mobile_orange_duree = 0;
      $total_groupe_mobile_orange_cout = 0;
  
      $total_groupe_mobile_bouygues_nb = 0;
      $total_groupe_mobile_bouygues_duree = 0;
      $total_groupe_mobile_bouygues_cout = 0;
  
      $total_groupe_data_nb = 0;
      $total_groupe_data_duree = 0;
      $total_groupe_data_cout = 0;
  
      $total_groupe_duree = 0;
      $total_groupe_nb = 0;
      $total_groupe_cout = 0;

      foreach ($lignes as $keyligne => $ligne)
	{
	  $lignetel = new LigneTel($db);
	  $lignetel->fetch_by_id($keyligne);

	  $client = new Societe($db);
	  $client->fetch($lignetel->client_id);

	  //print "Traitement ligne $keyligne ". $lignetel->numero."\n";

	  $national_nb = 0;
	  $national_duree = 0;
	  $national_cout = 0;

	  $mobile_sfr_nb = 0;
	  $mobile_sfr_duree = 0;
	  $mobile_sfr_cout = 0;

	  $mobile_orange_nb = 0;
	  $mobile_orange_duree = 0;
	  $mobile_orange_cout = 0;

	  $mobile_bouygues_nb = 0;
	  $mobile_bouygues_duree = 0;
	  $mobile_bouygues_cout = 0;

	  $inter_nb = 0;
	  $inter_duree = 0;
	  $inter_cout = 0;

	  $data_nb = 0;
	  $data_duree = 0;
	  $data_cout = 0;

	  if (($oldfk_soc <> $lignetel->client_facture_id) && ($fksoc > 0))
	    {
	      $page2->write_string($xx, 0,  'Total', $format_titre_total1);
	      $page2->write_blank($xx, 1, $format_titre_total2);
	      $page2->write_blank($xx, 2, $format_titre_total3);

	      $str = '=SUM(D'.$oldxx.':D'.($xx).')';
	      $page2->write_formula($xx, 3,  $str, $fnb);

	      //$page2->write($xx, 3,  $total_groupe_national_nb, $fnb);
	      $page2->write_string($xx, 4, duree_text($total_groupe_national_duree), $fduree);

	      $str = '=SUM(F'.$oldxx.':F'.($xx).')';
	      $page2->write_formula($xx, 5,  $str, $fcout);

	      $str = '=SUM(G'.$oldxx.':G'.($xx).')';
	      $page2->write_formula($xx, 6,  $str, $fnb);

	      $page2->write_string($xx, 7, duree_text(($total_groupe_mobile_sfr_duree + $total_groupe_mobile_orange_duree)), $fduree);


	      $str = '=SUM(I'.$oldxx.':I'.($xx).')';
	      $page2->write_formula($xx, 8,  $str, $fcout);

	      $str = '=SUM(J'.$oldxx.':J'.($xx).')';
	      $page2->write_formula($xx, 9,  $str, $fnb);

	      $page2->write_string($xx, 10, duree_text($total_groupe_mobile_bouygues_duree), $fduree);

	      $str = '=SUM(L'.$oldxx.':L'.($xx).')';
	      $page2->write_formula($xx, 11,  $str, $fcout);

	      $str = '=SUM(M'.$oldxx.':M'.($xx).')';
	      $page2->write_formula($xx, 12,  $str, $fnb);

	      $page2->write_string($xx, 13, duree_text($total_groupe_data_duree), $fduree);

	      $str = '=SUM(O'.$oldxx.':O'.($xx).')';
	      $page2->write_formula($xx, 14,  $str, $fcout);

	      $tlg_nb = $total_groupe_national_nb + $total_groupe_mobile_sfr_nb + $total_groupe_mobile_orange_nb + $total_groupe_mobile_bouygues_nb + $total_groupe_data_nb;

	      $tlg_duree = $total_groupe_national_duree + $total_groupe_mobile_sfr_duree + $total_groupe_mobile_orange_duree + $total_groupe_mobile_bouygues_duree + $total_groupe_data_duree;

	      $tlg_cout = $total_groupe_national_cout + $total_groupe_mobile_sfr_cout + $total_groupe_mobile_orange_cout + $total_groupe_mobile_bouygues_cout + $total_groupe_data_cout;

	      $str ="=D".($xx+1)."+G".($xx+1)."+J".($xx+1)."+M".($xx+1);

	      $page2->write_formula($xx, 15,  $str, $fnb);
	      $page2->write_string($xx, 16, duree_text($tlg_duree), $fduree);
	      $str ="=F".($xx+1)."+I".($xx+1)."+L".($xx+1)."+O".($xx+1);
	      $page2->write_formula($xx, 17,  $str, $fcout);

	      
	      if ($tlg_duree > 0)
		{
		  $page2->write($xx, 18,  round(($tlg_cout/$tlg_duree),4), $fmoy);
		}
	      else
		{
		  $page2->write($xx, 18,  0, $fmoy);
		}

	      $tg = 0;
	      $total_groupe_duree = 0;
	      $total_groupe_nb = 0;
	      $total_groupe_cout = 0;

	      $total_groupe_national_nb = 0;
	      $total_groupe_national_duree = 0;
	      $total_groupe_national_cout = 0;

	      $total_groupe_mobile_sfr_nb = 0;
	      $total_groupe_mobile_sfr_duree = 0;
	      $total_groupe_mobile_sfr_cout = 0;

	      $total_groupe_mobile_orange_nb = 0;
	      $total_groupe_mobile_orange_duree = 0;
	      $total_groupe_mobile_orange_cout = 0;

	      $total_groupe_mobile_bouygues_nb = 0;
	      $total_groupe_mobile_bouygues_duree = 0;
	      $total_groupe_mobile_bouygues_cout = 0;

	      $total_groupe_data_nb = 0;
	      $total_groupe_data_duree = 0;
	      $total_groupe_data_cout = 0;

	      $lines = array();

	      $oldfk_soc = $lignetel->client_facture_id;
	      $xx++;
	      $oldxx = $xx+1;
	    }
	  else
	    {
	      if ($tg == 1)
		{
		  // Ecrase le total groupe si la ligne suivante fait partie du même groupe
		  $tg = 0;
		}
	    }

	  $page2->write_string($xx, 1,  $lignetel->code_analytique, $fcode);
	  $page2->write_string($xx, 0,  $client->nom, $fclient);

	  $total_duree = 0;
	  $total_cout = 0;
	  $total_nb=0;

	  $page2->write_string($xx, 2,  $lignetel->numero, $fligne);

	  /* 
	   * Communications
	   */

	  $sql = "SELECT ligne, numero, date, fourn_cout, fourn_montant, duree, tarif_achat_temp, tarif_achat_fixe, tarif_vente_temp, tarif_vente_fixe, cout_achat, cout_vente, remise";      
	  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details as l";
	  $sql .= " WHERE ligne = '".$lignetel->numero."'";	              

	  if ($imonth > $month)
	    {
	      $sql .= " AND date_format(date,'%Y') = ".$year;
	    }
	  else
	    {
	      $sql .= " AND date_format(date,'%Y%m') = ".$year.substr("00".$imonth, -2);
	    }
	  $sql .= " ORDER BY date ASC ";

	  $result = $db->query($sql);
	  if ($result)
	    {
	      $num = $db->num_rows();
	      $i = 0;
	  
	      while ($i < $num)
		{
		  $obj = $db->fetch_object();

		  $NumTel = new TelephonieNumero($obj->numero);
		  $type = $NumTel->NumeroType($lignetel->numero);

		  /*
		   * Type appel
		   *
		   */
	      
		  if ($type == 'mobile')
		    {
		      $z = '';
		      $nnum = '0033'.substr($obj->numero, 1);
		      // print $nnum;
		      $tarif_vente->cout($nnum, $x, $y, $z);
		      // print " ".$z."\n";

		      if ($z == 'FRANCE MOBILE SFR')
			{
			  $type = 'mobile_sfr';
			}
		      elseif ($z == 'FRANCE MOBILE ORANGE')
			{
			  $type = 'mobile_orange';
			}
		      elseif ($z == 'FRANCE MOBILE BOUYGUES')
			{
			  $type = 'mobile_bouygues';
			}
		      else
			{
			  print "ERROR ERROR ERROR ERROR\n";
			  exit (1);
			}
		    }
	      
		  if (array_key_exists($obj->numero, $numdatas) )
		    {
		      $data_nb++;
		      $data_duree += $obj->duree;
		      $data_cout += $obj->cout_vente;

		      $total_groupe_data_nb++;
		      $total_groupe_data_duree += $obj->duree;
		      $total_groupe_data_cout += $obj->cout_vente;

		      $total_global_data_nb++;
		      $total_global_data_duree += $obj->duree;
		      $total_global_data_cout += $obj->cout_vente;
		    }
		  else
		    {
		      if ($type == 'mobile_sfr')
			{
			  $mobile_sfr_nb++;
			  $mobile_sfr_duree += $obj->duree;
			  $mobile_sfr_cout += $obj->cout_vente;

			  $total_groupe_mobile_sfr_nb++;
			  $total_groupe_mobile_sfr_duree += $obj->duree;
			  $total_groupe_mobile_sfr_cout += $obj->cout_vente;

			  $total_global_mobile_sfr_nb++;
			  $total_global_mobile_sfr_duree += $obj->duree;
			  $total_global_mobile_sfr_cout += $obj->cout_vente;
			}
		      elseif ($type == 'mobile_orange')
			{
			  $mobile_orange_nb++;
			  $mobile_orange_duree += $obj->duree;
			  $mobile_orange_cout += $obj->cout_vente;

			  $total_groupe_mobile_orange_nb++;
			  $total_groupe_mobile_orange_duree += $obj->duree;
			  $total_groupe_mobile_orange_cout += $obj->cout_vente;

			  $total_global_mobile_orange_nb++;
			  $total_global_mobile_orange_duree += $obj->duree;
			  $total_global_mobile_orange_cout += $obj->cout_vente;
			}
		      elseif ($type == 'mobile_bouygues')
			{
			  $mobile_bouygues_nb++;
			  $mobile_bouygues_duree += $obj->duree;
			  $mobile_bouygues_cout += $obj->cout_vente;

			  $total_groupe_mobile_bouygues_nb++;
			  $total_groupe_mobile_bouygues_duree += $obj->duree;
			  $total_groupe_mobile_bouygues_cout += $obj->cout_vente;

			  $total_global_mobile_bouygues_nb++;
			  $total_global_mobile_bouygues_duree += $obj->duree;
			  $total_global_mobile_bouygues_cout += $obj->cout_vente;
			}
		      elseif ($type == 'inter')
			{
			  $inter_nb++;
			  $inter_duree += $obj->duree;
			  $inter_cout += $obj->cout_vente;

			  $total_global_inter_nb++;
			  $total_global_inter_duree += $obj->duree;
			  $total_global_inter_cout += $obj->cout_vente;
			}
		      elseif ($type == 'national')
			{
			  $national_nb++;
			  $national_duree += $obj->duree;
			  $national_cout += $obj->cout_vente;

			  $total_groupe_national_nb++;
			  $total_groupe_national_duree += $obj->duree;
			  $total_groupe_national_cout += $obj->cout_vente;

			  $total_global_national_nb++;
			  $total_global_national_duree += $obj->duree;
			  $total_global_national_cout += $obj->cout_vente;
			}
		      else
			{
			  print "ERROR ERROR ERROR ERROR\n";
			  exit (1);
			}
		    }

		  $total_nb++;
		  $total_duree += $obj->duree;
		  $total_cout += $obj->cout_vente;

		  $total_groupe_nb++;
		  $total_groupe_duree += $obj->duree;
		  $total_groupe_cout += $obj->cout_vente;

		  $total_global_nb++;
		  $total_global_duree += $obj->duree;
		  $total_global_cout += $obj->cout_vente;

		  $i++;
		}
	    }
	  else
	    {
	      print $db->error();
	    }

	  /*
	    $page2->write($xx, 3,  $data_nb, $formatc);
	    $page2->write_string($xx, 4,  duree_text($data_duree), $fduree);
	    $page2->write($xx, 5,  $data_cout, $fcout);
	  */

	  /* Local / National */ 

	  $page2->write($xx, 3,  $national_nb, $fnb);
	  $page2->write_string($xx, 4, duree_text($national_duree), $fduree);
	  $page2->write($xx, 5,  $national_cout, $fcout);

	  /* Mobile SFR + Mobile Orange */

	  $page2->write($xx, 6,  ($mobile_sfr_nb + $mobile_orange_nb), $fnb);
	  $page2->write_string($xx, 7, duree_text($mobile_sfr_duree + $mobile_orange_duree), $fduree);
	  $page2->write($xx, 8,  ($mobile_sfr_cout+$mobile_orange_cout), $fcout);

	  /* Mobile Bouygues */

	  $page2->write($xx, 9,  $mobile_bouygues_nb, $fnb);
	  $page2->write_string($xx, 10, duree_text($mobile_bouygues_duree), $fduree);
	  $page2->write($xx, 11,  $mobile_bouygues_cout, $fcout);

	  /* Data */

	  $page2->write($xx, 12,  $data_nb, $fnb);
	  $page2->write_string($xx, 13, duree_text($data_duree), $fduree);
	  $page2->write($xx, 14,  $data_cout, $fcout);

	  /* Totaux */
	  $tl_nb    = $national_nb    + $mobile_sfr_nb    + $mobile_orange_nb    + $mobile_bouygues_nb    + $data_nb;
	  $tl_cout  = $national_cout  + $mobile_sfr_cout  + $mobile_orange_cout  + $mobile_bouygues_cout  + $data_cout;
	  $tl_duree = $national_duree + $mobile_sfr_duree + $mobile_orange_duree + $mobile_bouygues_duree + $data_duree;

	  $str ="=D".($xx+1)."+G".($xx+1)."+J".($xx+1)."+M".($xx+1);

	  $page2->write_formula($xx, 15,  $str , $fnb);
	  $page2->write_string($xx, 16, duree_text($tl_duree), $fduree);

	  $str ="=F".($xx+1)."+I".($xx+1)."+L".($xx+1)."+O".($xx+1);

	  $page2->write($xx, 17,  $str, $fcout);

	  if ($tl_duree > 0)
	    {
	      $page2->write($xx, 18,  round(($tl_cout/$tl_duree),4), $fmoy);
	    }
	  else
	    {
	      $page2->write($xx, 18,  0, $fmoy);
	    }

	  /*
	    $page2->write($xx, 3,  $inter_nb, $formatc);
	    $page2->write_string($xx, 4,  duree_text($inter_duree), $fduree);
	    $page2->write($xx, 5,  $inter_cout, $fcout);
	  */


	  /*
	    $page2->write($xx, 3,  $total_nb, $ftotal);
	    $page2->write_string($xx, 4, duree_text($total_duree), $fdureeBold);
	    $page2->write($xx, 5,  $total_cout, $fcoutBold);
	  */

	  $xx++;
	  $fksoc++;
	} // Fin de la boucle des lignes
      /*
      $page2->write_string($xx, 0,  'Total', $format_titre_total1);
      $page2->write_blank($xx, 1, $format_titre_total2);
      $page2->write_blank($xx, 2, $format_titre_total3);
  
      $page2->write($xx, 3,  $total_groupe_national_nb, $fnb);
      $page2->write_string($xx, 4, duree_text($total_groupe_national_duree), $fduree);
      $page2->write($xx, 5,  $total_groupe_national_cout, $fcout);  
  
  
      $page2->write($xx, 6,  ($total_groupe_mobile_sfr_nb + $total_groupe_mobile_orange_nb), $fnb);
      $page2->write_string($xx, 7, duree_text(($total_groupe_mobile_sfr_duree + $total_groupe_mobile_orange_duree)), $fduree);

      $page2->write($xx, 8,  ($total_groupe_mobile_sfr_cout + $total_groupe_mobile_orange_cout), $fcout);

  
      $page2->write($xx, 9,  $total_groupe_mobile_bouygues_nb, $fnb);
      $page2->write_string($xx, 10, duree_text($total_groupe_mobile_bouygues_duree ), $fduree);

      $page2->write($xx, 11,  $total_groupe_mobile_bouygues_cout, $fcout);

  
      $page2->write($xx, 12,  $total_groupe_data_nb, $fnb);
      $page2->write_string($xx, 13, duree_text($total_groupe_data_duree), $fduree);
      $page2->write($xx, 14,  $total_groupe_data_cout, $fcout);


      $tlg_nb = $total_groupe_national_nb + $total_groupe_mobile_sfr_nb + $total_groupe_mobile_orange_nb + $total_groupe_mobile_bouygues_nb + $total_groupe_data_nb;

      $tlg_duree = $total_groupe_national_duree + $total_groupe_mobile_sfr_duree + $total_groupe_mobile_orange_duree + $total_groupe_mobile_bouygues_duree + $total_groupe_data_duree;
  
      $tlg_cout = $total_groupe_national_cout + $total_groupe_mobile_sfr_cout + $total_groupe_mobile_orange_cout + $total_groupe_mobile_bouygues_cout + $total_groupe_data_cout;
  
      $page2->write($xx, 19,  $tlg_nb, $fnb);
      $page2->write_string($xx, 20, duree_text($tlg_duree), $fduree);
      $page2->write($xx, 21,  $tlg_cout, $fcout);
      $page2->write($xx, 22,  ($tlg_cout/$tlg_duree), $fmoy);
  
      */

      /******************************/

      $page2->write_string($xx, 0,  'Total', $format_titre_total1);
      $page2->write_blank($xx, 1, $format_titre_total2);
      $page2->write_blank($xx, 2, $format_titre_total3);
      
      $str = '=SUM(D'.$oldxx.':D'.($xx).')';
      $page2->write_formula($xx, 3,  $str, $fnb);
      
      //$page2->write($xx, 3,  $total_groupe_national_nb, $fnb);
      $page2->write_string($xx, 4, duree_text($total_groupe_national_duree), $fduree);

      $str = '=SUM(F'.$oldxx.':F'.($xx).')';
      $page2->write_formula($xx, 5,  $str, $fcout);
      
      $str = '=SUM(G'.$oldxx.':G'.($xx).')';
      $page2->write_formula($xx, 6,  $str, $fnb);
      
      $page2->write_string($xx, 7, duree_text(($total_groupe_mobile_sfr_duree + $total_groupe_mobile_orange_duree)), $fduree);
      
      
      $str = '=SUM(I'.$oldxx.':I'.($xx).')';
      $page2->write_formula($xx, 8,  $str, $fcout);
      
      $str = '=SUM(J'.$oldxx.':J'.($xx).')';
      $page2->write_formula($xx, 9,  $str, $fnb);
      
      $page2->write_string($xx, 10, duree_text($total_groupe_mobile_bouygues_duree), $fduree);
      
      $str = '=SUM(L'.$oldxx.':L'.($xx).')';
      $page2->write_formula($xx, 11,  $str, $fcout);
      
      $str = '=SUM(M'.$oldxx.':M'.($xx).')';
      $page2->write_formula($xx, 12,  $str, $fnb);
      
      $page2->write_string($xx, 13, duree_text($total_groupe_data_duree), $fduree);
      
      $str = '=SUM(O'.$oldxx.':O'.($xx).')';
      $page2->write_formula($xx, 14,  $str, $fcout);
      
      $tlg_nb = $total_groupe_national_nb + $total_groupe_mobile_sfr_nb + $total_groupe_mobile_orange_nb + $total_groupe_mobile_bouygues_nb + $total_groupe_data_nb;
      
      $tlg_duree = $total_groupe_national_duree + $total_groupe_mobile_sfr_duree + $total_groupe_mobile_orange_duree + $total_groupe_mobile_bouygues_duree + $total_groupe_data_duree;
      
      $tlg_cout = $total_groupe_national_cout + $total_groupe_mobile_sfr_cout + $total_groupe_mobile_orange_cout + $total_groupe_mobile_bouygues_cout + $total_groupe_data_cout;
      
      $str ="=D".($xx+1)."+G".($xx+1)."+J".($xx+1)."+M".($xx+1);
      
      $page2->write_formula($xx, 15,  $str, $fnb);
      $page2->write_string($xx, 16, duree_text($tlg_duree), $fduree);
      $str ="=F".($xx+1)."+I".($xx+1)."+L".($xx+1)."+O".($xx+1);
      $page2->write_formula($xx, 17,  $str, $fcout);
      
      if ($tlg_duree > 0)
	{
	  $page2->write($xx, 18,  round(($tlg_cout/$tlg_duree),4), $fmoy);
	}
      else
	{
	  $page2->write($xx, 18,  0, $fmoy);
	}
      
      
      
      /********************************/
      
      
      $tg = 0;
      $total_groupe_duree = 0;
      $total_groupe_nb = 0;
      $total_groupe_cout = 0;
  
      $total_groupe_national_nb = 0;
      $total_groupe_national_duree = 0;
      $total_groupe_national_cout = 0;
  
      $total_groupe_mobile_sfr_nb = 0;
      $total_groupe_mobile_sfr_duree = 0;
      $total_groupe_mobile_sfr_cout = 0;
  
      $total_groupe_mobile_orange_nb = 0;
      $total_groupe_mobile_orange_duree = 0;
      $total_groupe_mobile_orange_cout = 0;
  
      $total_groupe_mobile_bouygues_nb = 0;
      $total_groupe_mobile_bouygues_duree = 0;
      $total_groupe_mobile_bouygues_cout = 0;
  
      $total_groupe_data_nb = 0;
      $total_groupe_data_duree = 0;
      $total_groupe_data_cout = 0;
  
      $oldfk_soc = $lignetel->client_facture_id;
      $xx++;

    }

  $workbook->close();
  dol_syslog("Close $fname");

}

function duree_text($duree)
{
  $h = floor($duree / 3600);
  $m = floor(($duree - ($h * 3600)) / 60);
  $s = ($duree - ( ($h * 3600 ) + ($m * 60) ) );
  
  if ($h > 0)
    {
      $dt = $h . " h " . $m ." min " . $s ." sec" ; 
    }
  else
    {
      if ($m > 0)
	{
	  $dt = $m ." min " . $s ." sec" ; 
	}
      else
	{
	  $dt =  $s ." sec" ; 
	}
    }


  return $h.":".substr("00".$m, -2).":".substr("00".$s,-2);
}

?>
