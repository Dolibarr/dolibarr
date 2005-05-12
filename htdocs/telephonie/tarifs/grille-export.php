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
  $socidp = $user->societe_id;
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
$worksheet->write(2, 6,  "ID", $formatcc);

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
	  $worksheet->write($j, 6,  $obj->rowid, $formatcc);

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

/*
 * Comparatif
 *
 */


$sheetcomp = &$workbook->addworksheet("Comparatif");

$sheetcomp->set_column('A:A', 40);
$sheetcomp->set_column('B:F', 10);


$num1_format =& $workbook->addformat(array(num_format => '#0.0000'));
$num1_format->set_align('center');
$num1_format->set_align('vcenter');

$num2_format =& $workbook->addformat(array(num_format => '#0.0000'));
$num2_format->set_right(1);
$num2_format->set_align('center');
$num2_format->set_align('vcenter');

$num3_format =& $workbook->addformat(array(num_format => '#0.0000'));
$num3_format->set_left(1);
$num3_format->set_align('center');
$num3_format->set_align('vcenter');


$formatcc =& $workbook->addformat();
$formatcc->set_align('center');
$formatcc->set_align('vcenter');  
$formatcc->set_border(1);

$sql = "SELECT d.libelle as grille";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
$sql .= " WHERE d.rowid IN (1,".$_GET["id"].")";
$sql .= " ORDER BY d.rowid ASC";
$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  $a = 1;  

  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);
      
      $sheetcomp->write(1, $a, $obj->grille);

      $a = $a + 2;
      $i++;
    }
}
else
{
  print $db->error();
}


$sheetcomp->write(2, 0,  "Tarif");
$sheetcomp->write(2, 1,  "/min", $formatcc);
$sheetcomp->write(2, 2,  "Fixe", $formatcc);
$sheetcomp->write(2, 3,  "/min", $formatcc);
$sheetcomp->write(2, 4,  "Fixe", $formatcc);

$types = array('NAT','MOB','INT');

$j = 3;

foreach ($types as $type)
{
  $tarifs = array();


  $sql = "SELECT t.libelle, d.libelle as grille, t.rowid, t.type";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
  $sql .= "," . MAIN_DB_PREFIX."telephonie_tarif as t";
  
  $sql .= " WHERE d.rowid = '".$_GET["id"]."'";
  $sql .= " AND t.type = '".$type."'";
  $sql .= " ORDER BY d.libelle ASC";
  
  $resql = $db->query($sql);
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      while ($i < $num)
	{
	  $obj = $db->fetch_object($i);	
	
	  $tid = $obj->rowid;

	  $tarifs[$tid][0] = $obj->libelle;
	  $i++;
	}
    }

  $sql = "SELECT t.libelle, d.libelle as grille, m.temporel, m.fixe, t.rowid, t.type";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
  $sql .= "," . MAIN_DB_PREFIX."telephonie_tarif_montant as m";
  $sql .= "," . MAIN_DB_PREFIX."telephonie_tarif as t";
  $sql .= " WHERE d.rowid = m.fk_tarif_desc";
  $sql .= " AND m.fk_tarif = t.rowid"; 
  $sql .= " AND d.rowid = 1";
  $sql .= " AND t.type = '".$type."'";
  $sql .= " ORDER BY d.libelle ASC";
  
  $resql = $db->query($sql);
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      while ($i < $num)
	{
	  $obj = $db->fetch_object($i);	

	  $tid = $obj->rowid;

	  $tarifs[$tid][1] = $obj->temporel;
	  $tarifs[$tid][2] = $obj->fixe;
	  $i++;
	}
    }


  $sql = "SELECT t.rowid, m.temporel, m.fixe";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
  $sql .= "," . MAIN_DB_PREFIX."telephonie_tarif_montant as m";
  $sql .= "," . MAIN_DB_PREFIX."telephonie_tarif as t";
  $sql .= " WHERE d.rowid = m.fk_tarif_desc";
  $sql .= " AND m.fk_tarif = t.rowid"; 
  $sql .= " AND d.rowid = ".$_GET["id"];
  $sql .= " AND t.type = '".$type."'";
  $sql .= " ORDER BY d.libelle ASC";
  
  $resql = $db->query($sql);
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      while ($i < $num)
	{
	  $obj = $db->fetch_object($i);	

	  $tid = $obj->rowid;

	  $tarifs[$tid][3] = $obj->temporel;
	  $tarifs[$tid][4] = $obj->fixe;
	  $i++;
	}
    }


  foreach($tarifs as $tarif)
    {
      $sheetcomp->write($j, 0,  $tarif[0]);
      $sheetcomp->write($j, 1,  $tarif[1], $num3_format);
      $sheetcomp->write($j, 2,  $tarif[2], $num2_format);
      $sheetcomp->write($j, 3,  $tarif[3], $num1_format);
      $sheetcomp->write($j, 4,  $tarif[4], $num2_format);
      
      $j++;      
    } 

  $sheetcomp->write_blank($j, 1, $num3_format);
  $sheetcomp->write_blank($j, 2, $num2_format);
  $sheetcomp->write_blank($j, 4, $num2_format);

  $j++;
}


$sheetcomp->write(0, 0,  "Tarifs comparés au ".strftime("%d %m %Y",$date) );
$worksheet->write(0, 0,  "Tarifs au ".strftime("%d %m %Y",$date) );


$workbook->close();
$db->close();

Header("Content-Disposition: attachment; filename=$fname");
header("Content-Type: application/x-msexcel");
$fh=fopen($fname, "rb");
fpassthru($fh);
//unlink($fname);
?>
