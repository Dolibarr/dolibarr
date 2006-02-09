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
 * Export des communications dans import-cdr
 *
 */
require ("../../../master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_workbook.inc.php");
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_worksheet.inc.php");
/*
 *
 */
$sql  = "SELECT ligne,date,heure,num,dest,dureetext,montant";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
$sql .= " WHERE ligne ='".$argv[1]."'";
$sql .= " ORDER BY date ASC, heure ASC";

$resql = $db->query($sql);
if ( $resql )
{
  $fname = "/tmp/export-comm-".$argv[1].".xls";

  $workbook = &new writeexcel_workbook($fname);	
  $page = &$workbook->addworksheet("Communications");

  $page->set_column(0,0,12);
  $page->set_column(1,1,10);
  $page->set_column(3,3,12);
  $page->set_column(4,4,40);
		
  $page->write(0, 0,  "Ligne", $format_titre_agence1);
  $page->write(0, 1,  "Date", $format_titre);
  $page->write(0, 2,  "Heure", $format_titre);
  $page->write(0, 3,  "Numéro", $format_titre);
  $page->write(0, 4,  "Destination", $format_titre);
  $page->write(0, 5,  "Durée", $format_titre);
  $page->write(0, 6,  "Coût", $format_titre);
  
  $fnb =& $workbook->addformat();
  $fnb->set_align('vcenter');
  $fnb->set_align('left');

  $i = 1;
  while ($row = $db->fetch_row($resql))
    {
      $page->write_string($i, 0,  $row[0], $fnb);
      $page->write($i, 1,  $row[1], $fnb);
      $page->write_string($i, 2,  $row[2], $fnb);
      $page->write_string($i, 3,  $row[3], $fnb);
      $page->write_string($i, 4,  $row[4], $fnb);
      $page->write_string($i, 5,  $row[5], $fnb);
      $page->write_number($i, 6,  $row[6], $fnb);

      $i++;
    }

  $workbook->close();
  print ($i -1) ." communications\n";
  print "Write $fname\n";
}
else
{
  print $db->error();
}

$db->close();
?>
