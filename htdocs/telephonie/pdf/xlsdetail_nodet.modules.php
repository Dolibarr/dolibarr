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
 *
 */
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_workbook.inc.php");
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_worksheet.inc.php");

class xlsdetail_nodet {

  Function xlsdetail_nodet($DB)
  {
    $this->db = $DB;
  }

  Function GenerateFile($ligne, $fac, $factel)
  {
    $this->ligne = $ligne;
    $error = 0;

    $dir = FAC_OUTPUTDIR . "/" . $fac->ref . "/" ;
    $fname = $dir . $fac->ref . "-".$ligne->numero."-".$ligne->code_analytique."-detail.xls";

    //dol_syslog("Open file : $fname");
    
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

    $page->set_column(0,0,12);  // A
    $page->set_column(1,1,20);  // B
    $page->set_column(2,2,15);  // C
    $page->set_column(3,3,30);  // D
    $page->set_column(6,6,7);   // G
    $page->set_column(9,9,7);   // J
    $page->set_column(12,12,7); // M
              
    $page->write(0, 0, "Ligne", $format_titre_agence1);
    $page->write(0, 1, "Date", $format_titre);
    $page->write(0, 2, "Numero", $format_titre);
    $page->write(0, 3, "Destination", $format_titre);
    $page->write(0, 4, "Durée", $format_titre);
    $page->write(0, 5, "Cout HT", $format_titre);

    $sql = "SELECT ligne, date, numero, dest, dureetext, duree, cout_vente";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
    $sql .= " WHERE fk_telephonie_facture  = '".$factel->id."'";
    $sql .= " ORDER BY date ASC";

    $resql = $this->db->query($sql);
    
    if ($resql)
      {
	$i = 0;
	$numsql = $this->db->num_rows($resql);

	//dol_syslog($this->ligne->numero . " : ".$numsql);

	while ($i < $numsql)
	  {
	    $obj = $this->db->fetch_object($resql);

	    $xx = $i + 1;
	    
	    $page->write_string($xx, 0,  $obj->ligne, $fdest);
	    $page->write_string($xx, 1,  $obj->date, $fdest);
	    $page->write_string($xx, 2,  $obj->numero, $fdest);
	    $page->write_string($xx, 3,  $obj->dest, $fdest);
	    $page->write($xx, 4,  $obj->duree, $fnb);
	    $page->write($xx, 5,  $obj->cout_vente, $fp);
	    
	    $i++;
	  }
	$this->db->free($resql);
      }
    else
      {
	dol_syslog($this->db->error());
      }
    
    $workbook->close();
    //dol_syslog("Close $fname");

    return $error;
  }
}
?>
