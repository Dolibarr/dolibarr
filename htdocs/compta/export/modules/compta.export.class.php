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
 * $Id$
 * $Source$
 *
 */


class ComptaExport
{
  function ComptaExport ($DB, $USER, $classe)
  {
    $this->db = $DB;
    $this->user = $USER;
    $this->classe_export = $classe;
  }

  function ReadLines()
  {
    $sql = "SELECT f.rowid as facid, f.facnumber, ".$this->db->pdate("f.datef")." as datef";
    $sql .= " , f.total_ttc, f.tva ";
    $sql .= " ,s.nom, s.code_client";
    $sql .= " , l.price, l.tva_taux";
    $sql .= " , c.numero";

    $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as l";
    $sql .= " , ".MAIN_DB_PREFIX."facture as f";
    $sql .= " , ".MAIN_DB_PREFIX."societe as s";
    $sql .= " , ".MAIN_DB_PREFIX."compta_compte_generaux as c";
    
    $sql .= " WHERE f.rowid = l.fk_facture ";
    $sql .= " AND s.idp = f.fk_soc";
    $sql .= " AND f.fk_statut = 1 ";
    $sql .= " AND l.fk_code_ventilation <> 0 ";
    $sql .= " AND c.rowid = l.fk_code_ventilation";

    $sql .= " ORDER BY f.rowid ASC, l.rowid ASC";

    print $sql; 

    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
	$this->linec = array();

	while ($i < $num)
	  {
	    $obj = $this->db->fetch_object();

	    $this->linec[$i][0] = $obj->datef;
	    $this->linec[$i][1] = $obj->facid;
	    $this->linec[$i][2] = $obj->code_client;
	    $this->linec[$i][3] = $obj->nom;
	    $this->linec[$i][4] = $obj->numero;
	    $this->linec[$i][5] = $obj->facnumber;
	    $this->linec[$i][6] = $obj->tva;
	    $this->linec[$i][7] = $obj->total_ttc;
	    $this->linec[$i][8] = $obj->price;

	    $i++;
	  }
      }    
  }

  function Export()
  {
    $this->ReadLines();


    if (sizeof($this->linec) > 0 )
      {

	include_once DOL_DOCUMENT_ROOT.'/compta/export/modules/compta.export.'.strtolower($this->classe_export).'.class.php';  



	$objexport_name = "ComptaExport".$this->classe_export;

	$objexport = new $objexport_name();

	$objexport->Export($this->linec);

      }

  }

}
