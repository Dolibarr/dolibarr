<?PHP
/* Copyright (C) 2005-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require_once(DOL_DOCUMENT_ROOT.'/telephonie/workflowtel.class.php');

class LigneAdsl {
  var $db;

  var $id;
  var $ligne;

  function LigneAdsl($DB, $id=0)
  {
    global $config;
    $this->id = $id;

    $this->db = $DB;
    $this->error_message = '';
    $this->statuts[-1] = "En attente";
    $this->statuts[1] = "A commander";
    $this->statuts[2] = "Commandée chez le fournisseur";
    $this->statuts[3] = "Activée chez le fournisseur";
    $this->statuts[4] = "Installée chez le client";
    $this->statuts[5] = "A résilier";
    $this->statuts[6] = "Résiliation demandée";
    $this->statuts[7] = "Résiliée";
    $this->statuts[8] = "Rejetée";
    $this->statuts[9] = "Backbone programmé";

    $this->statuts_order[0] = -1;
    $this->statuts_order[1] = 1;
    $this->statuts_order[2] = 2;
    $this->statuts_order[3] = 3;
    $this->statuts_order[4] = 9;     
    $this->statuts_order[5] = 4;
    $this->statuts_order[6] = 5;
    $this->statuts_order[7] = 6;
    $this->statuts_order[8] = 7;
    $this->statuts_order[9] = 8;

    return 1;
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

  /*
   *
   *
   */
  function create($user)
  {
    if (strlen(trim($this->numero)) == 10)
      {
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_adsl_ligne";
	$sql .= " (fk_client, fk_client_install, fk_client_facture, fk_contrat, numero_ligne, fk_type, fk_fournisseur, note, fk_commercial, statut, fk_user_creat)";
	$sql .= " VALUES (";
	$sql .= " $this->client,$this->client_install,$this->client_facture,'$this->contrat','$this->numero',$this->type,$this->fournisseur, '$this->note',$this->commercial, -1,$user->id)";
	
	$resql = $this->db->query($sql);

	if ( $resql )
	  {
	    $this->id = $this->db->last_insert_id($resql);

	    // Appel le workflow
	    $wkf = new WorkflowTelephonie($this->db);
	    $wkf->notify('xdsl', -1, $this->numero);
	    
	    return 0;
	  }
	else
	  {
	    $lex = new LigneAdsl($this->db);
	    if ($lex->fetch($this->numero) == 1)
	      {
		$this->error_message = "Echec de la création de la ligne, cette ligne existe déjà !";
		dolibarr_syslog("LigneAdsl::Create Error -3");
		return -3;
	      }
	    else
	      {
		$this->error_message = "Echec de la création de la ligne";
		dolibarr_syslog("LigneAdsl::Create Error -1");
		dolibarr_syslog("LigneAdsl::Create $sql");
		return -1;
	      }
	  }
      }
    else
      {
	$this->error_message = "Echec de la création de la ligne, le numéro de la ligne est incorrect !";
	dolibarr_syslog("LigneAdsl::Create Error -2");
	return -2;
      }
  }
  /*
   *
   *
   */
  function fetch_by_id($id)
  {
    return $this->fetch(0, $id);
  }

  function delete($id)
    {
      $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_adsl_ligne_statut";
      $sql .= " WHERE fk_ligne = ".$id;

      if ($this->db->query($sql))
	{

	  $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_adsl_ligne";
	  $sql .= " WHERE rowid = ".$id;
	  
	  if ($this->db->query($sql))
	    {
	      return 0;
	    }
	  else
	    {
	      dolibarr_syslog("LigneAdsl::Delete Error -1");
	      return -1;
	    }
	}
      else
	{
	  dolibarr_syslog("LigneAdsl::Delete Error -2");
	  return -2;
	}
    }

  function fetch($ligne, $id = 0)
    {
      $sql = "SELECT l.rowid, l.fk_client, l.fk_client_install, l.fk_client_facture, l.fk_fournisseur, l.numero_ligne, l.note, l.statut, l.fk_commercial";
      $sql .= ", l.ip, l.login, l.password, l.prix, l.fk_contrat";
      $sql .= " , t.intitule AS type";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_adsl_ligne as l";
      $sql .= " , ".MAIN_DB_PREFIX."telephonie_adsl_type as t";
      $sql .= " WHERE t.rowid = l.fk_type";

      if ($id > 0)
	{
	  $sql .= " AND l.rowid = ".$id;
	}
      else	
	{
	  $sql .= " AND l.numero_ligne = ".$ligne;
	}
      $resql = $this->db->query($sql);
      if ($resql)
	{
	  if ($this->db->num_rows($resql))
	    {
	      $obj = $this->db->fetch_object($resql);

	      $this->id = $obj->rowid;
	      $this->socid             = $obj->fk_soc;
	      $this->numero            = $obj->numero_ligne;
	      $this->remise            = $obj->remise;
	      $this->client_id         = $obj->fk_client;
	      $this->client_install_id = $obj->fk_client_install;
	      $this->client_facture_id = $obj->fk_client_facture;
	      $this->fournisseur_id    = $obj->fk_fournisseur;
	      $this->commercial_id     = $obj->fk_commercial;
	      $this->contrat_id        = $obj->fk_contrat;
	      $this->type              = $obj->type;
	      $this->prix              = $obj->prix;
	      $this->statut            = $obj->statut;

	      $this->ip                = $obj->ip;
	      $this->login             = $obj->login;
	      $this->password          = $obj->password;

	      $this->mode_paiement  = 'pre';

	      $result = 1;
	    }
	  else
	    {
	      $result = -2;
	    }

	  $this->db->free($resql);
	}
      else
	{
	  /* Erreur select SQL */
	  print $this->db->error();
	  $result = -1;
	}

      return $result;
  }

  /**
   *
   *
   *
   */

  function update_info($ip, $login, $password)
  {
    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_adsl_ligne";
    $sql .= " SET ip     = '".$ip ."'";
    $sql .= " , login    = '".$login."'" ;
    $sql .= " , password = '".$password."'" ;

    $sql .= " WHERE rowid =".$this->id;
    
    $this->db->query($sql);    
  }

  /**
   * Change le statut de la ligne
   *
   */
  function set_statut($user, $statut, $datea='', $commentaire='')
  {
    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_adsl_ligne";
    $sql .= " SET statut = ".$statut ;

    $sql .= " WHERE rowid =".$this->id;
    
    $this->db->query($sql);
    
    if ($datea <> '')
      {
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_adsl_ligne_statut";
	$sql .= " (tms,fk_ligne, fk_user, statut, comment) ";
	$sql .= " VALUES ($datea,$this->id, $user->id, $statut, '$commentaire' )";

	$this->db->query($sql);
      }
    else
      {
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_adsl_ligne_statut";
	$sql .= " (tms, fk_ligne, fk_user, statut, comment) ";
	$sql .= " VALUES (now(), $this->id, $user->id, $statut, '$commentaire' )";

	$this->db->query($sql);
      }
    
    // Appel le workflow
    $wkf = new WorkflowTelephonie($this->db);
    $wkf->notify('xdsl', $statut, $this->numero);

    return 0;
  }

}
?>
