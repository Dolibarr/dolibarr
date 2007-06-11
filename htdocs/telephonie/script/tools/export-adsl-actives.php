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
 * Vérifie les lignes ayant le statut d'attente
 *
 */
require ("../../../master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_workbook.inc.php");
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_worksheet.inc.php");
/*
 *
 */

$sql = "SELECT la.numero_ligne, s.nom as nom,  la.prix, t.intitule";

$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_adsl_ligne as la";
$sql .= " ,  ".MAIN_DB_PREFIX."societe as s";
$sql .= " ,  ".MAIN_DB_PREFIX."telephonie_adsl_type as t";
$sql .= " WHERE la.fk_client_facture = s.rowid";
$sql .= " AND t.rowid = la.fk_type";



$resql = $db->query($sql);
if ( $resql )
{
  $fname = "/tmp/export-adsl-active.xls";

  $workbook = &new writeexcel_workbook($fname);
	
  $page = &$workbook->addworksheet("Lignes ADSL");
		
  $page->write(0, 0,  "Ligne", $format_titre_agence1);
  $page->write(0, 1,  "Client", $format_titre);
  $page->write(0, 2,  "Prix", $format_titre);
  $page->write(0, 3,  "Type", $format_titre);
  
  $fnb =& $workbook->addformat();
  $fnb->set_align('vcenter');
  $fnb->set_align('left');

  $i = 1;
  while ($row = $db->fetch_row($resql))
    {
      $xx = $i + 1;
      
      $page->write_string($xx, 0,  $row[0], $fnb);
      $page->write_string($xx, 1,  $row[1], $fnb);
      $page->write_number($xx, 2,  $row[2], $fnb);
      $page->write_string($xx, 3,  $row[3], $fnb);

      $i++;
    }

  $workbook->close();
}

else
{
  print $db->error();
}

$db->close();
?>
