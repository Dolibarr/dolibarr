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
 * Régénère les factures détaillées pour un mois précis
 *
 */
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once DOL_DOCUMENT_ROOT."/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php";
require_once DOL_DOCUMENT_ROOT."/telephonie/pdfdetail_ibreizh.modules.php";

/*
 * Regénération de la facture détaillée
 */

$year = "2005";
$month = "01";

$sql = "SELECT rowid, fk_facture ";
$sql .= " FROM llx_telephonie_facture";
$sql .= " WHERE date_format(date, '%Y%m') = '".$year.$month."'";

$result = $db->query($sql);

if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  $message = "";  
  while ($i < $num)
    {
      $row = $db->fetch_row();

      $facid = $row[1];
      $factel_id = $row[0];

      $factel = new FactureTel($db);
      if ($factel->fetch($factel_id) == 0)
	{
	  $ligne = new LigneTel($db);
	  if ($ligne->fetch($factel->ligne) == 1)		      
	    {
	      $facdet = new pdfdetail_ibreizh($db, $ligne->numero, $year, $month, $factel);
	      
	      if (! $facdet->write_pdf_file($facid, $ligne->numero))
		{
		  print "- ERREUR lors de Génération du pdf détaillé\n";
		  $error = 19;
		}            	      
	      else
		{
		  print "Génération du pdf détaillé ligne ".$ligne->numero."\n";
		}
	    }	  
	}
      $i++;
    }
}

$db->close();

?>
