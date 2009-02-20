<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
    $this->statuts[2] = "Commandée";
    $this->statuts[3] = "Activée";
    $this->statuts[4] = "A résilier";
    $this->statuts[5] = "Résiliation demandée";
    $this->statuts[6] = "Résiliée";
    $this->statuts[7] = "Rejetée";
    $this->statuts[8] = "En transfert";
    $this->statuts[9] = "Commande en cours";

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
    $sql .= " ligne = '$this->numero', ";
    $sql .= " fk_fournisseur = $this->fournisseur, ";
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
  function num_comments()
  {
    $num_comments = 0;

    /* Commentaires */
    $sql = "SELECT fk_ligne ";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne_comments";
    $sql .= " WHERE fk_ligne = ".$this->id;
    $resql = $this->db->query($sql);
    
    if ($resql)
      {
	$num_comments = $this->db->num_rows($resql);
	$this->db->free($resql);
      }
    return $num_comments;
  }
  /*
   *
   */

  function send_mail($user, $commentaire, $statut)
  {
  /* 
   * Envoi mail au commercial responsable
   *
   */

    $comm = new User($this->db,$this->commercial_id);
    $comm->fetch();

    $soc = new Societe($this->db);
    $soc->fetch($this->socid);

    $subject = "Evénement sur la ligne ".$this->numero;
    $sendto = $comm->prenom . " " .$comm->nom . "<".$comm->email.">";
    $from = "Dolibarr <dolibarr@ibreizh.net>";
    
    $message = "Bonjour,\n\n";
    $message .= "Nous vous informons de l'événement suivant :\n\n";

    $message .= "Ligne numéro : ".$this->numero."\n";
    $message .= "Société      : ".$soc->nom."\n";

    if ($statut == 6)
      {
	$message .= "Evénement    : Désactivation\n";
      }

    if ($statut == 3)
      {
	$message .= "Evénement    : Activation\n";

      }

    if (strlen($commentaire))
      {
	$message .= "Commentaire : ".$commentaire;
      }

    $message .= "\n\n--\n";
    $message .= "Ceci est un message automatique envoyé par Dolibarr\n";
    $message .= "Vous ne pouvez pas y répondre.";

    $mailfile = new DolibarrMail($subject,
				 $sendto,
				 $from,
				 $message);
    $mailfile->sendfile();
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
	    dol_syslog("LigneTel::SetRemise Error -5");
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
		dol_syslog("LigneTel::SetRemise Error -3");
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
		dol_syslog("LigneTel::SetRemise Error -4");
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
	dol_syslog("LigneTel::SetRemise Error -2");
	return -2;
      }
  }
	
  /*
   *
   *
   */
  function create($user, $mode_paiement='pre')
  {
    if (strlen(trim($this->numero)) == 10)
      {
	/*
	 * fk_commercial est encore définit pour supporter la migration en douceur 
	 * à terme cette colonne sera supprimé
	 *
	 */

	$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_societe_ligne";
	$sql .= " (datec,fk_soc, fk_client_comm, ligne, fk_soc_facture, fk_fournisseur, note, remise, fk_commercial, fk_commercial_sign, fk_commercial_suiv, statut, fk_user_creat, fk_concurrent, fk_contrat, mode_paiement)";
	$sql .= " VALUES (";
	$sql .= "now(),$this->client,$this->client_comm,'$this->numero',$this->client_facture,$this->fournisseur, '$this->note','$this->remise',$this->commercial_sign, $this->commercial_sign, $this->commercial_suiv, -1,$user->id, $this->concurrent, $this->contrat,'$mode_paiement')";
	
	if ( $this->db->query($sql) )
	  {
	    $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."telephonie_societe_ligne");

	    $this->SetRemise($user, $this->remise, 'Remise initiale');

	    $this->DefineClientOption();

	    if ($this->techno == 'voip' && $this->id)
	      {
		$sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_societe_ligne";
		$sql .= " SET statut = 3, techno='voip'";
		$sql .= " WHERE rowid=".$this->id;
		$this->db->query($sql);
	      }


	    return 0;
	  }
	else
	  {
	    $lex = new LigneTel($this->db);
	    if ($lex->fetch($this->numero) == 1)
	      {
		$this->error_message = "Echec de la création de la ligne, cette ligne existe déjà !";
		dol_syslog("LigneTel::Create Error -3");
		return -3;
	      }
	    else
	      {
		$this->error_message = "Echec de la création de la ligne";
		dol_syslog("LigneTel::Create Error -1");
		dol_syslog("LigneTel::Create ".$this->db->error());
		dol_syslog("LigneTel::Create $sql");
		return -1;
	      }
	  }
      }
    else
      {
	$this->error_message = "Echec de la création de la ligne, le numéro de la ligne est incorrect !";
	dol_syslog("LigneTel::Create Error -2 ($this->numero)");
	return -2;
      }
  }
  /*
   *
   *
   */
  function DefineClientOption()
  {

    $sql = "SELECT propriete, valeur";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_options";
    $sql .= " WHERE type= 'ligne'";
    $sql .= " AND fk_client_comm = '".$this->client_comm."'";

    $resql = $this->db->query($sql);
    
    if ($resql)
      {
	$num = $this->db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	  {
	    $obj = $this->db->fetch_object($resql);

	    $sqlu = "UPDATE ".MAIN_DB_PREFIX."telephonie_societe_ligne";
	    $sqlu .= " SET ".$obj->propriete." = '".$obj->valeur."'";
	    $sqlu .= " WHERE rowid = '".$this->id."'";

	    $resqlu = $this->db->query($sqlu);
	    
	    if (!$resqlu)
	      {
		dol_syslog("LigneTel::DefineClientOption Error");
		dol_syslog("LigneTel::DefineClientOption $sqlu");
	      }
	

	    $i++;
	  }
      }
    else
      {
	dol_syslog("LigneTel::DefineClientOption Error");
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
  /*
   *
   *
   */
  function fetch($ligne, $id = 0)
    {

      $sql = "SELECT rowid, fk_client_comm, fk_soc, fk_soc_facture, fk_fournisseur";
      $sql .= " , ligne, remise, note, statut, isfacturable";
      $sql .= " , mode_paiement, fk_concurrent, code_analytique";
      $sql .= " , fk_user_creat, fk_user_commande";
      $sql .= " , fk_contrat ";
      $sql .= " , fk_commercial_suiv, fk_commercial_sign";
      $sql .= " , pdfdetail, techno, support, last_comm_date";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as tl";

      if ($id > 0)
	{
	  $sql .= " WHERE tl.rowid = ".$id;
	}
      else	
	{
	  $sql .= " WHERE tl.ligne = ".$ligne;
	}

      $resql = $this->db->query($sql);

      if ($resql)
	{
	  if ($this->db->num_rows($resql))
	    {
	      $obj = $this->db->fetch_object($resql);

	      $this->id = $obj->rowid;
	      $this->socid              = $obj->fk_soc;
	      $this->numero             = $obj->ligne;
	      $this->contrat            = $obj->fk_contrat;
	      $this->remise             = $obj->remise;
	      $this->client_comm_id     = $obj->fk_client_comm;
	      $this->client_id          = $obj->fk_soc;
	      $this->client_facture_id  = $obj->fk_soc_facture;
	      $this->fournisseur_id     = $obj->fk_fournisseur;
	      $this->commercial_id      = $obj->fk_commercial_suiv;
	      $this->commercial_sign_id = $obj->fk_commercial_sign;
	      $this->commercial_suiv_id = $obj->fk_commercial_suiv;
	      $this->concurrent_id      = $obj->fk_concurrent;
	      $this->statut             = $obj->statut;
	      $this->mode_paiement      = $obj->mode_paiement;
	      $this->code_analytique    = $obj->code_analytique;
	      $this->techno             = $obj->techno;
	      $this->support            = $obj->support;
	      $this->user_creat         = $obj->fk_user_creat;
	      $this->user_commande      = $obj->fk_user_commande;
	      $this->last_comm_date     = $obj->last_comm_date;

	      if ($obj->isfacturable == 'oui')
		{
		  $this->facturable     = 1;
		}
	      else
		{
		  $this->facturable     = 0;
		}

	      $this->note               = stripslashes($obj->note);
	      $this->pdfdetail          = $obj->pdfdetail;
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
   * Change le statut de la ligne en a commander
   *
   */
  function set_a_commander($user)
  {
    if ($this->statut == -1)
      {
	$this->set_statut( $user, 1, $datea, $commentaire);
      }
  }

  /**
   * Transfer la ligne
   *
   */
  function transfer($user, $fourn_id)
  {
    if ($this->statut == 3)
      {
	$this->change_fournisseur($user,$fourn_id);
	$this->set_statut($user, 8);
      }
  }

  function change_fournisseur($user, $fourn_id)
  {
    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_societe_ligne";
    $sql .= " SET fk_fournisseur = ".$fourn_id ;
    $sql .= " WHERE rowid = ".$this->id;

    $resql = $this->db->query($sql);

    $this->fournisseur_id = $fourn_id;
  }


  /**
   * Change le statut de la ligne en En attente
   *
   */
  function set_en_attente($user)
  {
    if ($this->statut == 1)
      {
	$this->set_statut($user, -1, $datea, $commentaire);
      }
  }

  /**
   * Change le statut de la ligne
   *
   */
  function set_statut($user, $statut, $datea='', $commentaire='', $fourn=0)
  {
    if ($statut == 6 || $statut == 3)
      {
	$this->send_mail($user, $commentaire, $statut);
      }


    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_societe_ligne";
    $sql .= " SET statut = ".$statut ;
    $sql .= " WHERE rowid =".$this->id;
    
    if ($fourn > 0)
      {
	$sql .= " AND fk_fournisseur =".$fourn;
	$this->fournisseur_id = $fourn;
      }

    $this->db->query($sql);
    
    if ($statut == 2)
      {
	$sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_societe_ligne";
	$sql .= " SET date_commande = now()";
	$sql .= ", date_commande_last = now()";
	$sql .= ", fk_user_commande=".$user->id; 
	$sql .= " WHERE rowid =".$this->id;
	$sql .= " AND date_commande IS NULL";    

	if ($fourn > 0)
	  {
	    $sql .= " AND fk_fournisseur =".$fourn;
	  }

	$this->db->query($sql);

	$sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_societe_ligne";
	$sql .= " SET date_commande_last = now()";
	$sql .= ", fk_user_commande=".$user->id; 
	$sql .= " WHERE rowid =".$this->id;

	if ($fourn > 0)
	  {
	    $sql .= " AND fk_fournisseur =".$fourn;
	  }

	$this->db->query($sql);
      }

    if ($datea)
      {
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_societe_ligne_statut";
	$sql .= " (tms,fk_ligne, fk_user, statut, comment,fk_fournisseur) ";
	$sql .= " VALUES ($datea,$this->id, $user->id, $statut, '$commentaire',$this->fournisseur_id)";

	if (!$this->db->query($sql))
	  {
	    dol_syslog("LigneTel::set_statut Error -5");
	    dol_syslog($this->db->error());
	    dol_syslog($sql);
	  }
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
	$sql .= " (tms, fk_ligne, fk_user, statut, comment,fk_fournisseur) ";
	$sql .= " VALUES (now(), $this->id, $user->id, $statut, '$commentaire',$this->fournisseur_id)";

	if (!$this->db->query($sql))
	  {
	    dol_syslog("LigneTel::set_statut Error -6");
	    dol_syslog($this->db->error());
	    dol_syslog($sql);
	  }
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


    /* Mise à jour du contrat associé */

    if ($this->contrat > 0)
      {
	require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie.contrat.class.php");
	$contrat = new TelephonieContrat($this->db);
	$contrat->id = $this->contrat;
	$contrat->update_statut();
      }

    return 0;
  }
  /*
   *
   *
   */
  function log_clients()
  {
    $sql = "SELECT distinct s.rowid ";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
    $sql .= " , ".MAIN_DB_PREFIX."societe as s";
    $sql .= " WHERE l.statut = 3 AND s.rowid = l.fk_soc ";

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
   *
   */
  function delete($user)
  {
    $erro = 0;
    if ($this->statut == -1)
      {

	if ($this->db->begin())
	  {
	    $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne_remise";
	    $sql .= " WHERE fk_ligne=".$this->id;
	
	    if (!$this->db->query($sql))
	      {
		dol_syslog("LigneTel::Delete Error -5");
		$error++;
	      }

	    $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne_statut";
	    $sql .= " WHERE fk_ligne=".$this->id;
	
	    if (!$this->db->query($sql))
	      {
		dol_syslog("LigneTel::Delete Error -4");
		$error++;
	      }

	    $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_contact_facture";
	    $sql .= " WHERE fk_ligne=".$this->id;
	
	    if (!$this->db->query($sql))
	      {
		dol_syslog("LigneTel::Delete Error -3");
		$error++;
	      }

	    $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
	    $sql .= " WHERE rowid =".$this->id;
	    
	    if (!$this->db->query($sql))
	      {
		dol_syslog("LigneTel::Delete Error -2");
		$error++;
	      }
	    
	    /*****/

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
	
      }
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


    $sql = "SELECT c.rowid, c.name, c.firstname, c.email ";
    $sql .= "FROM ".MAIN_DB_PREFIX."socpeople as c";
    $sql .= ",".MAIN_DB_PREFIX."telephonie_contact_facture as cf";
    $sql .= " WHERE c.rowid = cf.fk_contact AND cf.fk_ligne = ".$this->id." ORDER BY name ";

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

      $resql = $this->db->query($sql);

      if ($resql)
	{
	  if ($this->db->num_rows($resql))
	    {
	      $row = $this->db->fetch_row($resql);
	      
	      $this->id = $row[0];

	      return 0;
	    }
	  else
	    {
	      return -1;
	    }
	  $this->db->free($resql);
	}
      else
	{
	  return -2;
	}
    }

  function ChangeContrat($user, $contrat_id)
  {
    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_societe_ligne";
    $sql .= " SET fk_contrat = ".$contrat_id ;
    $sql .= " WHERE rowid = ".$this->id;

    $resql = $this->db->query($sql);

    return 0;
  }

  /**
   *      \brief      Charge indicateurs this->nb de tableau de bord
   *      \return     int         <0 si ko, >0 si ok
   */
  function load_state_board($user)
  {
    $this->nb=array();
    
    $sql = "SELECT count(rowid) as nb";
    $sql.= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne ";
    $sql.= " WHERE fk_commercial_sign = ".$user->id;
    $resql=$this->db->query($sql);
    if ($resql)
      {
	while ($obj=$this->db->fetch_object($resql))
	  {
	    $this->nb["sign"] = $obj->nb;
	  }
	return 1;
      }
    else 
      {
	dol_print_error($this->db);
	$this->error=$this->db->error();
	return -1;
      }    
  }
  /*
   *
   *
   *
   */
  function load_previous_next_id($filtre='')
  {
    $sql = "SELECT rowid";
    $sql.= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
    $sql.= " WHERE rowid > ".$this->id."";
    $sql .= " ORDER BY rowid ASC LIMIT 1";

    $resql = $this->db->query($sql) ;
    if ($resql)
      {
	while ($row = $this->db->fetch_row($resql))
	  {
	    $this->ref_next = $row[0];
	  }
      }

    $sql = "SELECT rowid";
    $sql.= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
    $sql.= " WHERE rowid < ".$this->id."";
    $sql .= " ORDER BY rowid DESC LIMIT 1";

    $resql = $this->db->query($sql) ;
    if ($resql)
      {
	while ($row = $this->db->fetch_row($resql))
	  {
	    $this->ref_previous = $row[0];
	  }
      }

    return 1;
  }

}
?>
