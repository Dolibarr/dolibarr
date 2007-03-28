<?PHP
/* Copyright (C) 2005-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */
require("./pre.inc.php");
require_once DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_workbook.inc.php";
require_once DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_worksheet.inc.php";

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

$date = time();

$fname = ("/tmp/tarifs-".strftime("%Y-%m-%d", $date).".xls");
$workbook = &new writeexcel_workbook($fname);

$worksheet = &$workbook->addworksheet("Tarif");

$worksheet->set_column('A:A', 20);
$worksheet->set_column('B:B', 40);
$worksheet->set_column('C:E', 10);

$num1_format =& $workbook->addformat(array(num_format => '#0.0000'));

$formatcc =& $workbook->addformat();
$formatcc->set_align('center');
$formatcc->set_align('vcenter');  

$worksheet->write(2, 0,  "Grille");
$worksheet->write(2, 1,  "Tarif");
$worksheet->write(2, 2,  "/min");
$worksheet->write(2, 3,  "Fixe");
$worksheet->write(2, 4,  "Type", $formatcc);

$types = array('NAT','MOB','INT');

$j = 3;

foreach ($types as $type)
{
  $sql = "SELECT t.libelle, d.libelle as grille, m.temporel, m.fixe, t.rowid, t.type";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
  $sql .= "," . MAIN_DB_PREFIX."telephonie_tarif_montant as m";
  $sql .= "," . MAIN_DB_PREFIX."telephonie_tarif as t";
  
  $sql .= " WHERE d.rowid = m.fk_tarif_desc";
  $sql .= " AND m.fk_tarif = t.rowid";
  
  $sql .= " AND d.rowid = '".$_GET["id"]."'";
  $sql .= " AND t.type = '".$type."'";
  $sql .= " ORDER BY d.libelle ASC";
  

  $result = $db->query($sql);
  if ($result)
    {
      $num = $db->num_rows();
      $i = 0;
      
      while ($i < $num)
	{
	  $obj = $db->fetch_object($i);	
	  
	  $worksheet->write($j, 0,  $obj->grille);
	  $worksheet->write($j, 1,  $obj->libelle);
	  $worksheet->write($j, 2,  $obj->temporel, $num1_format);
	  $worksheet->write($j, 3,  $obj->fixe, $num1_format);
	  $worksheet->write($j, 4,  $obj->type, $formatcc);
	  $j++;	  
	  $i++;
	}
      
      $db->free();
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }  
  $j++;
}

$worksheet->write(0, 0,  "Tarifs au ".strftime("%d %m %Y",$date) );

$workbook->close();
$db->close();

Header("Content-Disposition: attachment; filename=$fname");
header("Content-Type: application/x-msexcel");
$fh=fopen($fname, "rb");
fpassthru($fh);
@unlink($fname);
?>
