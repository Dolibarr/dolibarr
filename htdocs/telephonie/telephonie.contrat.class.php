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
  function create($user, $isfacturable='oui', $mode_paiement='pre')
  {
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_contrat";
    $sql .= " (ref, fk_soc, fk_client_comm, fk_soc_facture, note";
    $sql .= " , fk_commercial_sign, fk_commercial_suiv, fk_user_creat, date_creat)";

    $sql .= " VALUES ('PROV".time()."'";

    $sql .= ", $this->client,$this->client_comm,$this->client_facture,'$this->note'";
    $sql .= ",$this->commercial_sign, $this->commercial_sign, $user->id, now())";
    
    if ( $this->db->query($sql) )
      {
	$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."telephonie_contrat");

	$sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_contrat";
	$sql .= " SET ref='".substr("00000000".$this->id,-8)."'";
	$sql .= " , isfacturable = '".$isfacturable."'";
	$sql .= " , mode_paiement = '".$mode_paiement."'";
	$sql .= " WHERE rowid=".$this->id;
	$this->db->query($sql);

	return 0;
      }
    
    else
      {
	$this->error_message = "Echec de la création du contrat";
	dolibarr_syslog("TelephonieContrat::Create Error -1");
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
    $error = 0 ;

    if (!$this->db->begin())
      {
	$error++;
	dolibarr_syslog("TelephonieContrat::Update Error -1");
      }

    if (!$error)
      {

	$sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_contrat";
	$sql .= " SET ";
	$sql .= " fk_soc = ".$this->client ;
	$sql .= ", fk_soc_facture = ".$this->client_facture;
	$sql .= ", fk_commercial_suiv = ".$this->commercial_suiv_id;
	$sql .= ", mode_paiement = '".$this->mode_paiement."'";
	$sql .= ", note =  '$this->note'";
	
	$sql .= " WHERE rowid = ".$this->id;
	
	if (! $this->db->query($sql) )
	  {
	    $error++;
	    dolibarr_syslog("TelephonieContrat::Update Error -2");
	  }
      }


    if (!$error)
      {
	$sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_societe_ligne";
	$sql .= " SET ";
	$sql .= " fk_soc = ".$this->client ;
	$sql .= ", fk_soc_facture = ".$this->client_facture;
	$sql .= ", fk_commercial_suiv = ".$this->commercial_suiv_id;
	$sql .= ", mode_paiement = '".$this->mode_paiement."'";
	$sql .= " WHERE fk_contrat = ".$this->id;
	
	
	if (! $this->db->query($sql) )
	  {
	    $error++;
	    dolibarr_syslog("TelephonieContrat::Update Error -3");
	  }
      }


    if (!$error)
      {
	$this->db->commit();
	return 0;
      }
    else
      {
	$this->db->rollback();
	return -1;
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
      $sql .= ", c.fk_user_creat, c.date_creat";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat as c";
      $sql .= " WHERE c.rowid = ".$id;


      if ($this->db->query($sql))
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);

	      $this->id                 = $obj->rowid;
	      $this->socid              = $obj->fk_soc;
	      $this->ref                = $obj->ref;
	      $this->remise             = $obj->remise;
	      $this->client_comm_id     = $obj->fk_client_comm;
	      $this->client_id          = $obj->fk_soc;
	      $this->client_facture_id  = $obj->fk_soc_facture;

	      $this->commercial_sign_id = $obj->fk_commercial_sign;
	      $this->commercial_suiv_id = $obj->fk_commercial_suiv;

	      $this->statut             = $obj->statut;
	      $this->mode_paiement      = $obj->mode_paiement;
	      $this->code_analytique    = $obj->code_analytique;

	      $this->user_creat         = $obj->fk_user_creat;

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
  /*
   *
   *
   *
   */
  function add_contact_facture($cid)
  {

    $this->del_contact_facture($cid);
        
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_contrat_contact_facture";
    $sql .= " (fk_contrat, fk_contact) ";
    $sql .= " VALUES ($this->id, $cid )";
    
    $this->db->query($sql);
  }
  /*
   *
   *
   */
  function del_contact_facture($cid)
  {
        
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_contrat_contact_facture";
    $sql .= " WHERE fk_contrat=".$this->id." AND fk_contact=".$cid;
    
    return $this->db->query($sql);   
  }
  /*
   *
   *
   */
  function count_associated_services()
  {
    $num = 0;
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."telephonie_contrat_service";
    $sql .= " WHERE fk_contrat=".$this->id;

    if ( $this->db->query( $sql) )
      {
	$num = $this->db->num_rows();
      }

    return $num;
  }
  /*
   *
   *
   */
  function add_service($user, $sid)
  {
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_contrat_service";
    $sql .= " (fk_contrat, fk_service, fk_user_creat, date_creat) ";
    $sql .= " VALUES ($this->id, $sid, $user->id, now() )";
    
    if ($this->db->query($sql) )
      {
	return 0 ;
      }
  }
  /*
   *
   *
   */
  function remove_service($user, $sid)
  {

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_contrat_service";
    $sql .= " (fk_contrat, fk_service, fk_user_creat, date_creat) ";
    $sql .= " VALUES ($this->id, $sid, $user->id, now() )";
    
    if ($this->db->query($sql) )
      {
	return 0 ;
      }
  }

}

?>
