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

class TelephonieContrat {
  var $db;

  var $id;
  var $ligne;

  function TelephonieContrat($DB, $id=0)
  {
    global $config;

    $this->db = $DB;
    $this->error_message = '';
    $this->statuts[-1] = "En attente";
    $this->statuts[1] = "A commander";
    $this->statuts[2] = "Commandée chez le fournisseur";
    $this->statuts[3] = "Activée";
    $this->statuts[4] = "A résilier";
    $this->statuts[5] = "Résiliation demandée";
    $this->statuts[6] = "Résiliée";
    $this->statuts[7] = "Rejetée";

    return 1;
  }
  /*
   * Creation du contrat
   * Le commercial qui fait le suivi est par defaut le commercial qui a signe
   */
  function create($user)
  {
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_contrat";
    $sql .= " (ref, fk_soc, fk_client_comm, fk_soc_facture, note";
    $sql .= " , fk_commercial_sign, fk_commercial_suiv, fk_user_creat, date_creat)";

    $sql .= " VALUES ('PROV".time()."'";

    $sql .= ", $this->client,$this->client_comm,$this->client_facture,'$this->note'";
    $sql .= ",$this->commercial_sign, $this->commercial_sign, $user->id, now())";
    
    if ( $this->db->query($sql) )
      {
	$this->id = $this->db->last_insert_id();

	$sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_contrat";
	$sql .= " SET ref='".substr("00000000".$this->id,-8)."'";
	$sql .= " WHERE rowid=".$this->id;
	$this->db->query($sql);

	return 0;
      }
    
    else
      {
	$this->error_message = "Echec de la création du contrat";
	dolibarr_syslog("LigneTel::Create Error -1");
	dolibarr_syslog($this->db->error());
	return -1;
      }
  }
  /*
   *
   *
   */
  function update($user)
  {

    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_societe_ligne";
    $sql .= " SET ";
    $sql .= " fk_client_comm = $this->client_comm, ";
    $sql .= " fk_soc = $this->client, ";
    $sql .= " ligne = '$this->numero', ";
    $sql .= " fk_soc_facture = $this->client_facture, ";
    $sql .= " fk_fournisseur = $this->fournisseur, ";
    $sql .= " fk_commercial = $this->commercial, ";
    $sql .= " fk_concurrent = $this->concurrent, ";
    $sql .= " note =  '$this->note',";
    $sql .= " remise = '$this->remise'";
    $sql .= " WHERE rowid = $this->id";

    if ( $this->db->query($sql) )
      {
	return 1;
      }
    else
      {
	print $this->db->error();
	print $sql ;
	return 0;
      }
  }

  /**
   *
   *
   */

  function fetch($id)
    {

      $sql = "SELECT c.rowid, c.ref, c.fk_client_comm, c.fk_soc, c.fk_soc_facture, c.note";
      $sql .= ", c.fk_commercial_sign, c.fk_commercial_suiv";
      $sql .= ", c.isfacturable, c.mode_paiement";

      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat as c";
      $sql .= " WHERE c.rowid = ".$id;


      if ($this->db->query($sql))
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);

	      $this->id                = $obj->rowid;
	      $this->socid             = $obj->fk_soc;
	      $this->ref               = $obj->ref;
	      $this->remise            = $obj->remise;
	      $this->client_comm_id    = $obj->fk_client_comm;
	      $this->client_id         = $obj->fk_soc;
	      $this->client_facture_id = $obj->fk_soc_facture;

	      $this->commercial_sign_id     = $obj->fk_commercial_sign;
	      $this->commercial_suiv_id     = $obj->fk_commercial_suiv;

	      $this->statut            = $obj->statut;
	      $this->mode_paiement     = $obj->mode_paiement;
	      $this->code_analytique   = $obj->code_analytique;

	      if ($obj->isfacturable == 'oui')
		{
		  $this->facturable        = 1;
		}
	      else
		{
		  $this->facturable        = 0;
		}

	      $this->ref_url = '<a href="'.DOL_URL_ROOT.'/telephonie/contrat/fiche.php?id='.$this->id.'">'.$this->ref.'</a>';



	      $result = 1;
	    }
	  else
	    {
	      dolibarr_syslog("TelephonieContrat::Fecth Erreur -2");
	      $result = -2;
	    }

	  $this->db->free();
	}
      else
	{
	  /* Erreur select SQL */
	  print $this->db->error();
	  $result = -1;
	  dolibarr_syslog("TelephonieContrat::Fecth Erreur -1");
	}

      return $result;
  }

  /*
   *
   *
   */
  function delete()
  {
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_contrat";
    $sql .= " WHERE rowid = ".$this->id;

    $this->db->query($sql);
  }
}

?>
