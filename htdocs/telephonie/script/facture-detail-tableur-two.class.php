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
 * Créé un ou plusieurs fichiers xls avec les communications
 * d'un contrat
 *
 */
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_workbook.inc.php");
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_worksheet.inc.php");

class FactureDetailTableurTwo {

  Function FactureDetailTableurTwo($DB)
  {
    $this->db = $DB;
  }

  Function GenerateFile($contrat_id, $year, $month)
  {
    $error = 0;


    $contrat_id = substr("0000".$contrat_id, -4);


    $dir = DOL_DATA_ROOT.'/telephonie/contrat/';
    
    $dir .= substr($contrat_id,0,1)."/";
    $dir .= substr($contrat_id,1,1)."/";
    $dir .= substr($contrat_id,2,1)."/";
    $dir .= substr($contrat_id,3,1)."/";
    
    create_exdir($dir);
    
    $fname = $dir . $contrat_id . "-$month-$year-detail.xls";
    
    dol_syslog("Open ".$fname);
    
    $workbook = &new writeexcel_workbook($fname);
    
    $page = &$workbook->addworksheet($year."/".substr("00".$month,-2));
    
    $fnb =& $workbook->addformat();
    $fnb->set_align('vcenter');
    $fnb->set_align('right');
    
    $fp =& $workbook->addformat();
    $fp->set_align('vcenter');
    $fp->set_align('right');
    $fp->set_num_format('0.000');
    
    $fdest =& $workbook->addformat();
    $fdest->set_align('vcenter');
    
    $page->set_column(0,0,12); // A
    $page->set_column(1,1,20); // B
    $page->set_column(2,2,15); // C
	
    $page->set_column(3,3,30);  // D
    $page->set_column(6,6,7); // G
    $page->set_column(9,9,7); // J
    $page->set_column(12,12,7); // M
    
    $page->write(0, 0,  "Ligne", $format_titre_agence1);
    $page->write(0, 1,  "Date", $format_titre);
    $page->write(0, 2,  "Numero", $format_titre);
    $page->write(0, 3,  "Destination", $format_titre);
    $page->write(0, 4,  "Duree", $format_titre);
    $page->write(0, 5,  "Cout", $format_titre);

    $sql = "SELECT f.rowid, l.rowid";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
    $sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
    $sql .= " WHERE f.fk_ligne = l.rowid";
    $sql .= " AND l.fk_contrat = ".$contrat_id;
    $sql .= " AND f.date = '".$year."-".$month."-01';";

    $resql = $this->db->query($sql);
	
    if ($resql)
      {    
	$num = $this->db->num_rows($resql);
	$total = 0;
	dol_syslog($num." lignes trouvées");
	$xx = 1;
	while ($row = $this->db->fetch_row($resql))
	  {
	    $sq = "SELECT ligne, date, numero, dest, dureetext, duree, cout_vente";
	    $sq .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
	    $sq .= " WHERE fk_ligne = '".$row[1]."'";
	    $sq .= " AND fk_telephonie_facture = ".$row[0];
	    $sq .= " ORDER BY date ASC";
	       	
	    $resq = $this->db->query($sq);
	
	    if ($resq)
	      {
		$i = 0;
		$numsq = $this->db->num_rows($resq);
		$total = $total + $numsq;
		dol_syslog("Ligne : ".$row[1] . " : ".$numsq . " Total : ".$total);
		
		while ($i < $numsq)
		  {
		    $obj = $this->db->fetch_object($resq);
		    
		    $page->write_string($xx, 0,  $obj->ligne, $fdest);
		    $page->write_string($xx, 1,  $obj->date, $fdest);
		    $page->write_string($xx, 2,  $obj->numero, $fdest);
		    $page->write_string($xx, 3,  $obj->dest, $fdest);
		    $page->write($xx, 4,  $obj->duree, $fnb);
		    $page->write($xx, 5,  $obj->cout_vente, $fp);
		    
		    $i++;
		    $xx++;
		  }
		$this->db->free($resq);
	      }
	    else
	      {
		dol_syslog($this->db->error());
	      }
	  }
	
	$workbook->close();
	//dol_syslog("Close $fname");
      }
    
    return $error;
  }
}
?>
