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
 * Génération du plan de facturation
 *
 */
print "Mem : ".memory_get_usage() ."\n";
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/numero.class.php");
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_workbook.inc.php");
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_worksheet.inc.php");

$error = 0;

$datetime = time();

$date = strftime("%d%h%Y%Hh%Mm%S",$datetime);

$sql = "SELECT c.rowid, sl.ligne, sl.code_analytique";

$sql .= ", s.nom, c.ref, s.code_client, s.address, s.cp, s.ville";

$sql .=" FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as sl";
$sql .=" , ".MAIN_DB_PREFIX."telephonie_contrat as c";
$sql .=" , ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE sl.fk_contrat  = c.rowid";
$sql .= " AND c.fk_client_comm = 52";
$sql .= " AND sl.statut <> 7";
$sql .= " AND c.fk_soc_facture = s.rowid";
$sql .= " ORDER BY c.rowid ASC, sl.code_analytique ASC";

$resql = $db->query($sql);

if ($resql)
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

  $fname = "/tmp/plan-facturation-".strftime("%d-%m-%Y",$datetime).".xls";

  print "Open $fname\n";

  $workbook = &new writeexcel_workbook($fname);

  $page2 = &$workbook->addworksheet("Plan");

  $page2->set_column(0,0,8);
  $page2->set_column(1,1,20);
  $page2->set_column(2,2,48);
  $page2->set_column(4,4,20);
  $page2->set_column(5,5,50);

  $formatcc =& $workbook->addformat();
  $formatcc->set_align('center');
  $formatcc->set_align('vcenter');  

  $fclient =& $workbook->addformat();
  $fclient->set_align('left');
  $fclient->set_align('vcenter');  

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

  $fb =& $workbook->addformat();
  $fb->set_align('vcenter');  
  $fb->set_bold();
  $fb->set_top(2);

  $fc =& $workbook->addformat();
  $fc->set_align('vcenter');  
  $fc->set_top(2);

  $fa =& $workbook->addformat();
  $fa->set_align('left');
  $fa->set_align('vcenter');  
  $fa->set_top(2);

  $fd =& $workbook->addformat();
  $fd->set_align('center');
  $fd->set_align('vcenter');  
  $fd->set_top(2);

  $num = $db->num_rows($resql);
  $i = 0;
  $b = 0;
  $oc = '';

  while($i < $num)
    {
      $obj = $db->fetch_object($resql);

      if ($oc <> $obj->ref)
	{
	  $b++;

	  $z = $obj->nom . "(".$obj->code_client.")". "\n";
	  $z.= $obj->address."\n";
	  $z.= $obj->cp . " ".$obj->ville;
	  
	  $page2->write_string($i, 0,  $b, $fc);
	  $page2->write_string($i, 1,  "Contrat : ".$obj->ref, $fb);
	  $page2->write_string($i, 2,  $z, $fa);

	  $oc = $obj->ref;
	  $a = 0;

	  $page2->write_string($i, 3,  $obj->code_analytique, $fd);
	  $page2->write_string($i, 4,  $obj->ligne, $fd);
	  $page2->write_string($i, 5,  $obj->nom, $fa);

	}

      else
	{
	  $page2->write_string($i, 3,  $obj->code_analytique, $formatcc);
	  $page2->write_string($i, 4,  $obj->ligne, $formatcc);
	  $page2->write_string($i, 5,  $obj->nom, $fclient);
	}

      $i++;
      $a++;
    }

  $workbook->close();
  dol_syslog("Close $fname");
  
}

?>
