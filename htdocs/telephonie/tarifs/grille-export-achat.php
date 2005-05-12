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

$fname = ("/tmp/achat-".strftime("%Y-%m-%d", $date).".xls");
$fna = ("achat-".strftime("%Y-%m-%d", $date).".xls");
$workbook = &new writeexcel_workbook($fname);

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

$num3_format_best =& $workbook->addformat(array(num_format => '#0.0000'));
$num3_format_best->set_left(1);
$num3_format_best->set_align('center');
$num3_format_best->set_align('vcenter');
$num3_format_best->set_color('green');


$formatcc =& $workbook->addformat();
$formatcc->set_align('center');
$formatcc->set_align('vcenter');  
$formatcc->set_border(1);

$sheetcomp->write(2, 0, "Tarif");

$sql = "SELECT d.rowid, d.libelle as grille";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
$sql .= " WHERE d.type_tarif = 'achat'";
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
      
      $grilles[$i] = $obj->rowid;

      $sheetcomp->write(1, $a, $obj->grille);

      $sheetcomp->write(2, $a,  "/min", $formatcc);
      $sheetcomp->write(2, ($a+1),  "Fixe", $formatcc);

      $a = $a + 2;
      $i++;
    }
}
else
{
  print $db->error();
}

$types = array('NAT','MOB','INT');

$j = 3;

foreach ($types as $type)
{
  $tarifs = array();

  $sql = "SELECT t.libelle, t.rowid, t.type";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif as t";
  $sql .= " WHERE t.type = '".$type."' ORDER BY t.libelle ASC";
  
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

  $ig = 0;

  foreach($grilles as $grille)
    {
      $sql = "SELECT t.libelle, d.libelle as grille, m.temporel, m.fixe, t.rowid, t.type";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
      $sql .= "," . MAIN_DB_PREFIX."telephonie_tarif_montant as m";
      $sql .= "," . MAIN_DB_PREFIX."telephonie_tarif as t";
      $sql .= " WHERE d.rowid = m.fk_tarif_desc";
      $sql .= " AND m.fk_tarif = t.rowid AND d.rowid = ".$grille;
      $sql .= " AND t.type = '".$type."' ORDER BY d.libelle ASC";

      $resql = $db->query($sql);
      if ($resql)
	{
	  $num = $db->num_rows($resql);
	  $i = 0;
	  
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object($i);	
	      
	      $tid = $obj->rowid;
	      
	      $tarifs[$tid][$ig+1] = $obj->temporel;
	      $tarifs[$tid][$ig+2] = $obj->fixe;
	      $i++;
	    }
	}
      $ig = $ig + 2;      
    }

  foreach($tarifs as $tarif)
    {
      $sheetcomp->write($j, 0,  $tarif[0]);

      $ig = 0;

      $min = $tarif[$ig+1];
      $igmin = 0;

      foreach($grilles as $grille)
	{      
	  $format = $num3_format;

	  if ($tarif[$ig+1] < $min)
	    {
	      $min = $tarif[$ig+1];
	      $igmin = $ig;
	    }

	  $ig = $ig + 2;
	}

      $ig = 0;

      foreach($grilles as $grille)
	{      
	  $format = $num3_format;

	  if ($igmin == $ig)
	    {
	      $format = $num3_format_best;
	    }

	  $sheetcomp->write($j, ($ig+1),  $tarif[$ig+1], $format);

	  if ($tarif[$ig+2] > 0)
	    {
	      $sheetcomp->write($j, ($ig+2),  $tarif[$ig+2], $num2_format);
	    }
	  else
	    {
	      $sheetcomp->write_string($j, ($ig+2),  "-", $num2_format);
	    }
	  $ig = $ig + 2;      
	}
     
      $j++;      
    } 

  $sheetcomp->write_blank($j, 1, $num3_format);
  $sheetcomp->write_blank($j, 2, $num2_format);
  $sheetcomp->write_blank($j, 4, $num2_format);

  $j++;
}


$sheetcomp->write(0, 0,  "Tarifs d'achats comparés au ".strftime("%d %m %Y",$date) );

$workbook->close();
$db->close();

Header("Content-Disposition: attachment; filename=$fna");
Header("Content-Type: application/x-msexcel");
$fh=fopen($fname, "rb");
fpassthru($fh);
//unlink($fname);
?>
