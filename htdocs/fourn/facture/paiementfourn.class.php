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
  var $facnumber;
  var $datepaye;
  var $amount;
  var $accountid;
  var $author;
  var $paiementid;		// Cette variable contient le type de paiement, 7 pour CHQ, etc... (nom pas tres bien choisi)
  var $num_paiement;
  var $note;
  var $societe;
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
    $sql .= " VALUES ('$this->facid', now(), '$this->datepaye', '$this->amount', '$user->id', '$this->paiementid', '$this->num_paiement', '$this->note')";
    
    $result = $this->db->query($sql);

    if (isset($result))
      {
		$this->id = $this->db->last_insert_id();

		$label = "Règlement facture $this->facnumber - $this->societe";

	// Portion de code qui mériterait de se baser sur la table des types 
	// de paiement, mais comme cette portion est aussi en dur dans l'ajout
	// des factures clients, je fais pareil pour les factures fournisseurs
    switch ($this->paiementid)
      {
      case 1:
        $this->paiementid = 'TIP';
        break;
      case 2:
        $this->paiementid = 'VIR';
        break;
      case 3:
        $this->paiementid = 'PRE';
        break;
      case 4:
        $this->paiementid = 'LIQ';
        break;
      case 5:
        $this->paiementid = 'WWW';
        break;
      case 6:
        $this->paiementid = 'CB';
        break;
      case 7:
        $this->paiementid = 'CHQ';
        break;
      }

	$sql = "INSERT INTO llx_bank (datec, dateo, amount, author, label, fk_type, fk_account, num_chq)";
	$sql .= " VALUES (now(), '$this->datepaye', -$this->amount, '$this->author', '$label', '$this->paiementid', '$this->accountid', '$this->num_paiement')";
	$result = $this->db->query($sql);

	// Pour l'instant ce code n'est pas actif et n'est pas nécessaire.
	// Je l'activerais (Eldy) si besoin de retrouver le lien entre une transaction bancaire
	// et la facture générée se fait sentir (fonction futures ?):
	// Mise a jour fk_bank dans llx_paiement_fourn
   	//if ($result) {   
 	//	$this->bankid = $this->db->last_insert_id();
	//
	//	$sql = "UPDATE llx_paiementfourn SET fk_bank=$this->bankid WHERE rowid=$this->id";
	//	$result = $this->db->query($sql);
	//}

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
