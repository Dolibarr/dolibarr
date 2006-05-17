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
 * Prépare les factures à imprimer
 */

/**
   \file       htdocs/telephonie/script/facturation-consolidation.php
   \ingroup    telephonie
   \brief      Consolidation des données de facturation
   \version    $Revision$
*/

require ("../../master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");

$ligne = new LigneTel($db);

if ($opt['m'] > 0)
{
  $datetime = mktime(10,10,10,$opt['m'],10,2006);
}
else
{
  $datetime = time();
}

$month = strftime("%m", $datetime);
$year = strftime("%y", $datetime);

if ($month == 1)
{
  $month = "12";
  $year = $year - 1;
}
else
{
  $month = substr("00".($month - 1), -2) ;
}

$sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_facture_consol";
$resql = $db->query($sql);
  
if (! $resql )
{
  print $db->error();
  die();
}

$paye[0] = 'non';
$paye[1] = 'oui';

$sql = "SELECT groupe.nom, agence.nom, l.ligne, l.statut, u.firstname,u.name,u.rowid ";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat as c";
$sql .= " , ".MAIN_DB_PREFIX."societe as groupe";
$sql .= " , ".MAIN_DB_PREFIX."societe as agence";
$sql .= " , ".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE l.fk_contrat = c.rowid";
$sql .= " AND c.fk_client_comm = groupe.idp";
$sql .= " AND c.fk_soc = agence.idp";
$sql .= " AND c.fk_commercial_sign = u.rowid";
//$sql .= " LIMIT 20";
$resql = $db->query($sql);
  
if ( $resql )
{
  $num = $db->num_rows();

  while ($row = $db->fetch_row($resql))
    {
      //print $row[0]."\t".$row[1]."\t".$row[2]."\t".$ligne->statuts[$row[3]]."\t".$row[4]."\n";

      $sqli = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_facture_consol";
      $sqli .= " (groupe,agence,ligne,statut,repre_ib) VALUES ";
      $sqli .= " ('".addslashes($row[0])."','".addslashes($row[1])."','$row[2]',";
      $sqli .= "'".$ligne->statuts[$row[3]]."',";
      $sqli .= "'".$row[4]." ".$row[5]."')";

      $resqli = $db->query($sqli);

      if ($resqli)
	{

	  /* Distributeur */
	  $sqls = "SELECT d.nom ";
	  $sqls .= " FROM ".MAIN_DB_PREFIX."telephonie_distributeur as d";
	  $sqls .= " , ".MAIN_DB_PREFIX."telephonie_distributeur_commerciaux as dc";
	  $sqls .= " WHERE dc.fk_user = '$row[6]'";
	  $sqls .= " AND dc.fk_distributeur = d.rowid";
	  $resqls = $db->query($sqls);
      
	  if ( $resqls )
	    {
	      while ($rows = $db->fetch_row($resqls))
		{
		  $sqlu = "UPDATE ".MAIN_DB_PREFIX."telephonie_facture_consol";
		  $sqlu .= " SET distri='".$rows[0]."'";
		  $sqlu .= " WHERE ligne = '$row[2]'";
		  if (!  $resqlu = $db->query($sqlu))
		    {
		      die($db->error());
		    }
		}
	    }
	  else
	    {
	      die($db->error());
	    }
      
	  $m = 0;
	  $mc = $month + 1;
	  $yc = $year;

	  while ($m < 7)
	    {
	      $mc = $mc - 1;

	      if ($mc == 0)
		{
		  $mc = 12;
		  $yc = $yc - 1;
		}

	      $msc = substr("00".$mc, -2) ;
	      $ysc = substr("00".$yc, -2) ;

	      $sqls = "SELECT round(sum(cout_vente),2) ";
	      $sqls .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";	  
	      $sqls .= " WHERE ligne = '$row[2]'";	  
	      $sqls .= " AND ym = '".$ysc.$msc."'";
	      $sqls .= " AND num_prefix = '06'";
	      $resqls = $db->query($sqls);
	      //print "$sqls\n";
	      if ( $resqls )
		{
		  while ($rows = $db->fetch_row($resqls))
		    {
		      $sqlu = "UPDATE ".MAIN_DB_PREFIX."telephonie_facture_consol";
		      $sqlu .= " SET mobi_m".$m."='".$rows[0]."'";
		      $sqlu .= " WHERE ligne = '$row[2]'";
		      if (!  $resqlu = $db->query($sqlu))
			{
			  die($db->error());
			}

		    }
		}
	      else
		{
		  die($db->error());
		}

	      /* Fixes */
	      $sqls = "SELECT round(sum(cout_vente),2) ";
	      $sqls .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";	  
	      $sqls .= " WHERE ligne = '$row[2]'";	  
	      $sqls .= " AND ym = '".$ysc.$msc."'";
	      $sqls .= " AND num_prefix in ('01','02','03','04','05')";
	      $resqls = $db->query($sqls);

	      if ( $resqls )
		{
		  while ($rows = $db->fetch_row($resqls))
		    {
		      $sqlu = "UPDATE ".MAIN_DB_PREFIX."telephonie_facture_consol";
		      $sqlu .= " SET fixe_m".$m."='".$rows[0]."'";
		      $sqlu .= " WHERE ligne = '$row[2]'";
		      if (!  $resqlu = $db->query($sqlu))
			{
			  die($db->error());
			}
		    }
		}
	      else
		{
		  die($db->error());
		}

	      /* Facture Payé */
	      $sqls = "SELECT paye ";
	      $sqls .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as tf";
	      $sqls .= " , ".MAIN_DB_PREFIX."facture as f";
	      $sqls .= " WHERE ligne = '$row[2]'";	  
	      $sqls .= " AND date_format(date,'%y%m') = '".$ysc.$msc."'";
	      $sqls .= " AND tf.fk_facture = f.rowid";
	      $resqls = $db->query($sqls);

	      if ( $resqls )
		{
		  while ($rows = $db->fetch_row($resqls))
		    {
		      $sqlu = "UPDATE ".MAIN_DB_PREFIX."telephonie_facture_consol";
		      $sqlu .= " SET paye_m".$m."='".$paye[$rows[0]]."'";
		      $sqlu .= " WHERE ligne = '$row[2]'";
		      if (!  $resqlu = $db->query($sqlu))
			{
			  die($db->error());
			}
		    }
		}
	      else
		{
		  die($db->error());
		}

	      $m++;
	    }
	}
      else
	{
	  print $db->error();
	  die();
	}
    }
}
else
{
  print $db->error();
}

$db->close();
?>
