<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

class Paiement 
{
  var $id;
  var $db;
  var $facid;
  var $datepaye;
  var $amount;
  var $author;
  var $paiementid; // numero du paiement dans le cas ou une facture paye +ieur fois
  var $num_paiement;
  var $note;
  /*
   *
   *
   *
   */
  Function Paiement($DB, $soc_idp="") 
  {
    $this->db = $DB ;
  }
  /*
   *
   *
   *
   */
  Function create() 
  {
    /*
     *  Insertion dans la base
     */
    
    $this->amount = ereg_replace(",",".",$this->amount);

    $sql = "INSERT INTO llx_paiement (fk_facture, datec, datep, amount, author, fk_paiement, num_paiement, note)";
    $sql .= " VALUES ($this->facid, now(), $this->datepaye,$this->amount,'$this->author', $this->paiementid, '$this->num_paiement', '$this->note')";
    
    $result = $this->db->query($sql);
    
    if ($result) 
      {
	$this->id = $this->db->last_insert_id();
	return $this->id;
      }
    else
      {
		print $this->db->error() ."<br>".$sql;
      }  
  }
  /*
   *
   *
   *
   */
  Function select($name, $filtre='', $id='')
  {
    $form = new Form($this->db);

    if ($filtre == 'crédit')
      {
	$sql = "SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_paiement WHERE type IN (0,2) ORDER BY libelle";
      }
    elseif ($filtre == 'débit')
      {
	$sql = "SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_paiement WHERE type IN (1,2) ORDER BY libelle";
      }
    else
      {
	$sql = "SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_paiement ORDER BY libelle";
      }
    $form->select($name, $sql, $id);
  }

  /*
   *
   *
   *
   */
  Function delete()
  {
    $sql = "SELECT llx_paiement.rowid FROM llx_facture, llx_paiement WHERE  llx_paiement.rowid = ".$this->id;
    $sql .= " AND llx_paiement.fk_facture = llx_facture.rowid AND llx_facture.paye = 0";

    $result = $this->db->query($sql);
    
    if ($result) 
      {
	if ($this->db->num_rows() == 1)
	  {
	    $sql = "DELETE FROM llx_paiement WHERE llx_paiement.rowid = ".$this->id;

	    $result = $this->db->query($sql);
	    
	    if ($result) 
	      {
		return 1;
	      }
	    else
	      {
		print $this->db->error() ."<br>".$sql;
		return 0;
	      }
	  }
      }
    else
      {
	print $this->db->error() ."<br>".$sql;
	return 0;
      }
  }
}
?>
