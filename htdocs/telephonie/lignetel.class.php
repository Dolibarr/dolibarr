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

class LigneTel {
  var $db;

  var $id;
  var $ligne;

  function LigneTel($DB, $id=0)
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
    $sql .= " note =  '$this->note'";
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
   * Infos complémentaires
   *
   */
  function update_infoc($user)
  {

    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_societe_ligne";
    $sql .= " SET ";
    $sql .= " code_analytique = '".$this->code_analytique."' ";

    $sql .= " WHERE rowid = ".$this->id;

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
  function SetRemise($user, $remise, $comment)
  {
    $remise = ereg_replace(",",".", $remise);

    if (strlen(trim($remise)) <> 0 && is_numeric($remise))
      {

	if (!$this->db->begin())
	  {
	    dolibarr_syslog("LigneTel::SetRemise Error -5");
	    $error++;
	  }

	if (!$error)
	  {
	    
	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_societe_ligne_remise";
	    $sql .= " (tms, fk_ligne, remise,  fk_user, comment)";
	    $sql .= " VALUES (now(),";
	    $sql .= " $this->id,'$remise',$user->id, '$comment')";
	    
	    if (! $this->db->query($sql) )
	      {
		dolibarr_syslog("LigneTel::SetRemise Error -3");
		$error++;
	      }
	  }
	
	if (!$error)
	  {
	    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_societe_ligne";
	    $sql .= " SET remise = '$remise'";
	    $sql .= " WHERE rowid = $this->id";
	    
	    if (! $this->db->query($sql) )
	      {
		dolibarr_syslog("LigneTel::SetRemise Error -4");
		$error++;
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
    else
      {
	dolibarr_syslog("LigneTel::SetRemise Error -2");
	return -2;
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
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_societe_ligne";
	$sql .= " (datec,fk_soc, fk_client_comm, ligne, fk_soc_facture, fk_fournisseur, note, remise, fk_commercial, statut, fk_user_creat, fk_concurrent, fk_contrat)";
	$sql .= " VALUES (";
	$sql .= "now(),$this->client,$this->client_comm,'$this->numero',$this->client_facture,$this->fournisseur, '$this->note','$this->remise',$this->commercial, -1,$user->id, $this->concurrent, $this->contrat)";
	
	if ( $this->db->query($sql) )
	  {
	    $this->id = $this->db->last_insert_id();

	    $this->SetRemise($user, $this->remise, 'Remise initiale');

	    return 0;
	  }
	else
	  {
	    $lex = new LigneTel($this->db);
	    if ($lex->fetch($this->numero) == 1)
	      {
		$this->error_message = "Echec de la création de la ligne, cette ligne existe déjà !";
		dolibarr_syslog("LigneTel::Create Error -3");
		return -3;
	      }
	    else
	      {
		$this->error_message = "Echec de la création de la ligne";
		dolibarr_syslog("LigneTel::Create Error -1");
		return -1;
	      }
	  }
      }
    else
      {
	$this->error_message = "Echec de la création de la ligne, le numéro de la ligne est incorrect !";
	dolibarr_syslog("LigneTel::Create Error -2");
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


  function fetch($ligne, $id = 0)
    {

      $sql = "SELECT rowid, fk_client_comm, fk_soc, fk_soc_facture, fk_fournisseur";
      $sql .= " , ligne, remise, note, statut, fk_commercial, isfacturable";
      $sql .= " , mode_paiement, fk_concurrent, code_analytique";
      $sql .= " , fk_user_creat, fk_user_commande";
      $sql .= " , fk_contrat ";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as tl";

      if ($id > 0)
	{
	  $sql .= " WHERE tl.rowid = ".$id;
	}
      else	
	{
	  $sql .= " WHERE tl.ligne = ".$ligne;
	}

      if ($this->db->query($sql))
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);

	      $this->id = $obj->rowid;
	      $this->socid             = $obj->fk_soc;
	      $this->numero            = $obj->ligne;
	      $this->contrat           = $obj->fk_contrat;
	      $this->remise            = $obj->remise;
	      $this->client_comm_id    = $obj->fk_client_comm;
	      $this->client_id         = $obj->fk_soc;
	      $this->client_facture_id = $obj->fk_soc_facture;
	      $this->fournisseur_id    = $obj->fk_fournisseur;
	      $this->commercial_id     = $obj->fk_commercial;
	      $this->concurrent_id     = $obj->fk_concurrent;
	      $this->statut            = $obj->statut;
	      $this->mode_paiement     = $obj->mode_paiement;
	      $this->code_analytique   = $obj->code_analytique;

	      $this->user_creat        = $obj->fk_user_creat;
	      $this->user_commande     = $obj->fk_user_commande;

	      if ($obj->isfacturable == 'oui')
		{
		  $this->facturable        = 1;
		}
	      else
		{
		  $this->facturable        = 0;
		}

	      $result = 1;
	    }
	  else
	    {
	      $result = -2;
	    }

	  $this->db->free();
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
  function print_concurrent_nom()
    {
      $sql = "SELECT nom";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_concurrents";
      $sql .= " WHERE rowid=".$this->concurrent_id;


      if ($this->db->query($sql))
	{
	  if ($this->db->num_rows())
	    {
	      $row = $this->db->fetch_row(0);

	      return $row[0];	      
	    }
	}
    }


  /**
   * Change le statut de la ligne
   *
   */
  function set_statut($user, $statut, $datea='', $commentaire='')
  {
    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_societe_ligne";
    $sql .= " SET statut = ".$statut ;

    if ($statut == 2)
      {
	$sql .= ", date_commande = now()";
	$sql .= ",fk_user_commande=".$user->id; 
      }

    $sql .= " WHERE rowid =".$this->id;
    
    $this->db->query($sql);
    
    if ($datea)
      {
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_societe_ligne_statut";
	$sql .= " (tms,fk_ligne, fk_user, statut, comment) ";
	$sql .= " VALUES ($datea,$this->id, $user->id, $statut, '$commentaire' )";

	$this->db->query($sql);
	/* 
	 * Mise à jour des logs
	 *
	 */
	$sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_ligne_statistique";
	$sql .= " SET nb = nb - 1";
	$sql .= " WHERE statut = ".$this->statut;
	$sql .= " AND dates >= '".$datea ."'";

	$this->db->query($sql);

	$sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_ligne_statistique";
	$sql .= " SET nb = nb + 1";
	$sql .= " WHERE statut = ".$statut;
	$sql .= " AND dates >= '".$datea ."'";

	$this->db->query($sql);

      }
    else
      {
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_societe_ligne_statut";
	$sql .= " (tms, fk_ligne, fk_user, statut, comment) ";
	$sql .= " VALUES (now(), $this->id, $user->id, $statut, '$commentaire' )";

	$this->db->query($sql);
      }


    
    $sql = "SELECT distinct statut, count(*) FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne GROUP BY statut";
    
    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
	$sl = array();    
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($i);
	    $sl[$i] = $row;
	    $i++;
	  }

	$i = 0;

	/* Nettoyage des logs */

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_ligne_statistique";
	$sql .= " WHERE dates = now()";
	if (!$this->db->query($sql))
	  {
	    print $sql;
	  }

	/* Insertion des nouveaux logs */

	while ($i < $num)
	  {
	    $row = $sl[$i];

	    $sql = "REPLACE INTO ".MAIN_DB_PREFIX."telephonie_ligne_statistique";
	    $sql .= " VALUES (now(),".$row[0].",".$row[1].")";
	    if (!$this->db->query($sql))
	      {
		print $sql;
	      }
	    $i++;
	  }	
      }
    else
      {
	print $sql;
      }

    $this->log_clients();

    return 0;
  }
  /*
   *
   *
   */
  function log_clients()
  {
    $sql = "SELECT distinct s.idp ";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
    $sql .= " , ".MAIN_DB_PREFIX."societe as s";
    $sql .= " WHERE l.statut = 3 AND s.idp = l.fk_soc ";

    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$nbclients = $num;
	$i = 0;

	/* Insertion des nouveaux logs */

	$sql = "REPLACE INTO ".MAIN_DB_PREFIX."telephonie_client_statistique";
	$sql .= " VALUES (now(),'nbclient',".$num.")";
	if (!$this->db->query($sql))
	  {
	    print $sql;
	  }
      }
    else
      {
	print $sql;
      }
    /*
     * nbligne active / client
     *
     */

    $sql = "SELECT count(*) ";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
    $sql .= " WHERE l.statut = 3";

    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();

	$row = $this->db->fetch_row(0);

	/* Insertion des nouveaux logs */

	if ($nbclients > 0)
	  {
	    $nblapc = ereg_replace(",",".",round($row[0]/$nbclients,3));
	  }

	$sql = "REPLACE INTO ".MAIN_DB_PREFIX."telephonie_client_statistique";
	$sql .= " VALUES (now(),'nblapc','".$nblapc."')";
	if (!$this->db->query($sql))
	  {
	    print $sql;
	  }
      }
    else
      {
	print $sql;
      }


    return 0;

  }
  /*
   *
   *
   */
  function add_contact($cid)
  {
        
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_contact_facture";
    $sql .= " (fk_ligne, fk_contact) ";
    $sql .= " VALUES ($this->id, $cid )";
    
    $this->db->query($sql);
  }
  /*
   *
   *
   */
  function del_contact($cid)
  {
        
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_contact_facture";
    $sql .= " WHERE fk_ligne=$this->id AND fk_contact=$cid ;";
    
    return $this->db->query($sql);   
  }
  /*
   *
   */
  function get_contact_facture()
  {
    $this->contact_facture_id = array();        
    $res   = array();
    $resid = array();


    $sql = "SELECT c.idp, c.name, c.firstname, c.email ";
    $sql .= "FROM ".MAIN_DB_PREFIX."socpeople as c";
    $sql .= ",".MAIN_DB_PREFIX."telephonie_contact_facture as cf";
    $sql .= " WHERE c.idp = cf.fk_contact AND cf.fk_ligne = ".$this->id." ORDER BY name ";

    if ( $this->db->query( $sql) )
      {
	$num = $this->db->num_rows();
	if ( $num > 0 )
	  {
	    $i = 0;
	    while ($i < $num)
	      {
		$row = $this->db->fetch_row($i);
		
		array_push($res, $row[1] . " " . $row[2] . " &lt;".$row[3]."&gt;");
		array_push($resid, $row[0]);
		$i++;
	      }
	    
	    $this->db->free();     
	  }
	
      }
    $this->contact_facture_id = $resid;
    return $res;
  }
  /**
   * Recupére le numéro d'une ligne depuis une facture comptable
   *
   */

  function fetch_by_facture_number($facnumber)
    {
      $sql = "SELECT fk_ligne, fk_facture ";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture";
      $sql .= " WHERE fk_facture = ".$facnumber;


      if ($this->db->query($sql))
	{
	  if ($this->db->num_rows())
	    {
	      $row = $this->db->fetch_row(0);
	      
	      $this->id = $row[0];

	      return 0;
	    }
	  else
	    {
	      return -1;
	    }
	}
      else
	{
	  return -2;
	}
    }
  /*
   *
   *
   */

}

?>
