<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class PaiementFourn
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
  Function PaiementFourn($DB) 
  {
    $this->db = $DB ;
  }
  /*
   *
   *
   *
   */
  Function create($user) 
  {
    /*
     *  Insertion dans la base
     */

    $this->amount = ereg_replace(",",".",$this->amount);
    
    $sql = "INSERT INTO llx_paiementfourn (fk_facture_fourn, datec, datep, amount, fk_user_author, fk_paiement, num_paiement, note)";
    $sql .= " VALUES ($this->facid, now(), $this->datepaye,$this->amount, $user->id, $this->paiementid, '$this->num_paiement', '$this->note')";
    
    $result = $this->db->query($sql);
    
    if ($result) 
      {
	$label = "Facture $this->facnumber - $this->societe";
	$sql = "INSERT INTO llx_bank (datec, dateo, amount, author, label)";
	$sql .= " VALUES (now(), $this->datepaye, $this->amount,'$this->author', '$this->label')";
	//$result = $this->db->query($sql);
      }
    else
      {
	print "$sql";
      }  

    return 1;
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
	$sql = "SELECT id, libelle FROM c_paiement WHERE type IN (0,2) ORDER BY libelle";
      }
    elseif ($filtre == 'débit')
      {
	$sql = "SELECT id, libelle FROM c_paiement WHERE type IN (1,2) ORDER BY libelle";
      }
    else
      {
	$sql = "SELECT id, libelle FROM c_paiement ORDER BY libelle";
      }
    $form->select($name, $sql, $id);
  }

  /*
   *
   *
   *
   */

}
?>
