<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2003      Brian Fraval         <brian@fraval.org>
 *
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

/**
        \file       htdocs/societe.class.php
        \ingroup    societe
        \brief      Fichier de la classe des societes
        \version    $Revision$
*/


/**
        \class 		Societe
        \brief 		Classe permettant la gestion des societes
*/

class Societe {
  var $db;

  var $id;
  var $nom;
  var $adresse;
  var $cp;
  var $ville;
  var $departement_id;
  var $pays_id;
  var $pays_code;
  var $tel;
  var $fax;
  var $url;
  var $siren;

  var $typent_id;
  var $effectif_id;
  var $forme_juridique_code;
  var $forme_juridique;

  var $client;
  var $fournisseur;

  var $code_client;
  var $code_fournisseur;
  var $code_compta;
  var $code_compta_fournisseur;

  var $note;
  
  var $stcomm_id;
  var $statut_commercial;
 

  /**
   *    \brief  Constructeur de la classe
   *    \param  DB     handler accès base de données
   *    \param  id     id societe (0 par defaut)
   */
	 
  function Societe($DB, $id=0)
  {
    $this->db = $DB;
    $this->creation_bit = 0;

    $this->id = $id;
    $this->client = 0;
    $this->fournisseur = 0;
    $this->typent_id  = 0;
    $this->effectif_id  = 0;
    $this->forme_juridique_code  = 0;

    // definit module code client
    if (defined('CODECLIENT_ADDON') && strlen(CODECLIENT_ADDON) > 0) $var = CODECLIENT_ADDON;
    else $var = "mod_codeclient_leopard";
	require_once DOL_DOCUMENT_ROOT.'/includes/modules/societe/'.$var.'.php';

    $this->mod_codeclient = new $var;
    $this->codeclient_modifiable = $this->mod_codeclient->code_modifiable;

    // definit module code fournisseur
    if (defined('CODEFOURNISSEUR_ADDON') && strlen(CODEFOURNISSEUR_ADDON) > 0) $var = CODEFOURNISSEUR_ADDON;
    else $var = "mod_codeclient_leopard";
	require_once DOL_DOCUMENT_ROOT.'/includes/modules/societe/'.$var.'.php';

    $this->mod_codefournisseur = new $var;
    $this->codeclient_modifiable = $this->mod_codeclient->code_modifiable;

    return 1;
  }

  /**
   *    \brief      Crée la societe en base
   *    \param      user        Objet utilisateur qui demande la création
   *    \return     0 si ok, < 0 si erreur
   */
	 
    function create($user='')
    {
        global $langs,$conf;
    
        $this->nom=trim($this->nom);
    
        $this->db->begin();
    
        $result = $this->verify();
    
        if ($result >= 0)
        {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."societe (nom, datec, datea, fk_user_creat) ";
            $sql .= " VALUES ('".addslashes($this->nom)."', now(), now(), '".$user->id."')";
    
            $result=$this->db->query($sql);
            if ($result)
            {
                $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."societe");
    
                $this->creation_bit = 1;
    
                $ret = $this->update($this->id);
    
                if ($ret == 0)
                {
                    // Appel des triggers
                    include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                    $interface=new Interfaces($this->db);
                    $interface->run_triggers('COMPANY_CREATE',$this,$user,$lang,$conf);
                    // Fin appel triggers
                                        
                    dolibarr_syslog("Societe::Create success id=".$this->id);
                    $this->db->commit();
                }
                else
                {
                    dolibarr_syslog("Societe::Create echec update");
                    $this->db->rollback();
                    return -3;
                }
    
                return $ret;
            }
            else
    
            {
                if ($this->db->errno() == DB_ERROR_RECORD_ALREADY_EXISTS)
                {
    
                    $this->error=$langs->trans("ErrorCompanyNameAlreadyExists",$this->nom);
                }
                else
                {
                    dolibarr_syslog("Societe::Create echec insert sql=$sql");
                }
                $this->db->rollback();
                return -2;
            }
    
        }
        else
        {
            $this->db->rollback();
            dolibarr_syslog("Societe::Create echec verify sql=$sql");
            return -1;
        }
    }

  /**
   *    \brief      Verification lors de la modification
   *    \return     0 si ok, < 0 en cas d'erreur
   */
   
  function verify()
  {
    $this->nom=trim($this->nom);

    $result = 0;

    if (! $this->nom)
      {
	$this->error = "Le nom de la société ne peut être vide.\n";
	$result = -2;
      }

    if ($this->codeclient_modifiable == 1)
      {
	// On ne vérifie le code client que si celui-ci est modifiable
	// Si il n'est pas modifiable il n'est pas mis à jour lors de l'update

	$rescode = $this->verif_codeclient();

	if ($rescode <> 0)
	  {
	    if ($rescode == -1)
	      {
		$this->error .= "La syntaxe du code client est incorrecte.\n";
	      }
	    
	    if ($rescode == -2)
	      {
		$this->error .= "Vous devez saisir un code client.\n";
	      }
	    
	    if ($rescode == -3)
	      {
		$this->error .= "Ce code client est déjà utilisé.\n";
	      }
	    
	    $result = -3;
	  }
	  
    }

    return $result;
  }

  /**
   *    \brief      Mise a jour des paramètres de la société
   *    \param      id      id societe
   *    \param      user    Utilisateur qui demande la mise à jour
   *    \return     0 si ok, < 0 si erreur
   */
	 
  function update($id, $user='')
  {
    global $langs;
    
    dolibarr_syslog("Societe::Update");

    $this->id=$id;
    $this->capital=trim($this->capital);
    $this->nom=trim($this->nom);
    $this->adresse=trim($this->adresse);
    $this->cp=trim($this->cp);
    $this->ville=trim($this->ville);
    $this->departement_id=trim($this->departement_id);
    $this->pays_id=trim($this->pays_id);
    $this->tel=trim($this->tel);
    $this->fax=trim($this->fax);
    $this->url=trim($this->url);
    $this->siren=trim($this->siren);
    $this->siret=trim($this->siret);
    $this->ape=trim($this->ape);
    $this->prefix_comm=trim($this->prefix_comm);
    $this->tva_intra=trim($this->tva_intra);
    $this->capital=trim($this->capital);
    $this->effectif_id=trim($this->effectif_id);
    $this->forme_juridique_code=trim($this->forme_juridique_code);
    
    $result = $this->verify();

    if ($result == 0)
      {
	dolibarr_syslog("Societe::Update verify ok");

	if (strlen($this->capital) == 0)
	  {
	    $this->capital = 0;
	  }
	
	$this->tel = ereg_replace(" ","",$this->tel);
	$this->tel = ereg_replace("\.","",$this->tel);
	$this->fax = ereg_replace(" ","",$this->fax);
	$this->fax = ereg_replace("\.","",$this->fax);
	
	/*
	 * \todo simpliste pour l'instant mais remplit 95% des cas à améliorer
	 */
	if ($this->departement_id == -1 && $this->pays_id == 1)
	  {
	    if (strlen($this->cp) == 5)
	      {
		$depid = departement_rowid($this->db, substr($this->cp,0,2), $this->pays_id);
		if ($depid > 0)
		  {
		    $this->departement_id = $depid;
		  }
	      }
	  }
	
	/*
	 * Supression des if (trim(valeur)) pour construire la requete
	 * sinon il est impossible de vider les champs
	 */

	$sql = "UPDATE ".MAIN_DB_PREFIX."societe ";
	$sql .= " SET nom = '" . addslashes($this->nom) ."'"; // Champ obligatoire
	
	$sql .= ",address = '" . addslashes($this->adresse) ."'";
	
	if ($this->cp)
	  { $sql .= ",cp = '" . $this->cp ."'"; }
	
	if ($this->ville)
	  { $sql .= ",ville = '" . addslashes($this->ville) ."'"; }
	
	if ($this->departement_id) 
	  { $sql .= ",fk_departement = '" . $this->departement_id ."'"; }
	
	if ($this->pays_id)
	  { $sql .= ",fk_pays = '" . $this->pays_id ."'"; }
	      
	$sql .= ",tel = ".($this->tel?"'".$this->tel."'":"null"); 	
	$sql .= ",fax = ".($this->fax?"'".$this->fax."'":"null"); 	
	$sql .= ",url = ".($this->url?"'".$this->url."'":"null"); 	

	$sql .= ",siren = '". $this->siren ."'";
	$sql .= ",siret = '". $this->siret ."'";
	$sql .= ",ape   = '". $this->ape   ."'";

	$sql .= ",tva_intra = '" . $this->tva_intra ."'"; 
	$sql .= ",capital = '" . $this->capital ."'";

	if ($this->prefix_comm) $sql .= ",prefix_comm = '" . $this->prefix_comm ."'";

	if ($this->effectif_id) $sql .= ",fk_effectif = '" . $this->effectif_id ."'";
	
	if ($this->typent_id)   $sql .= ",fk_typent = '" . $this->typent_id ."'";

	if ($this->forme_juridique_code) $sql .= ",fk_forme_juridique = '".$this->forme_juridique_code."'";
	
	$sql .= ",client = " . $this->client;
	$sql .= ",fournisseur = " . $this->fournisseur;
	
	if ($this->creation_bit || $this->codeclient_modifiable)
	  {   
	    // Attention check_codeclient peut modifier le code 
	    // suivant le module utilisé
	    
	    $this->check_codeclient();
	    
	    $sql .= ", code_client = ".($this->code_client?"'".$this->code_client."'":"null");
	    
	    // Attention check_codecompta_client peut modifier le code 
	    // suivant le module utilisé
	    
	    $this->check_codecompta_client();
	    
	    $sql .= ", code_compta = ".($this->code_compta?"'".$this->code_compta."'":"null");
	  }

	if ($this->creation_bit || $this->codefournisseur_modifiable)
	  {   
	    // Attention check_codefournisseur peut modifier le code 
	    // suivant le module utilisé
	    
	    $this->check_codefournisseur();
	    
	    $sql .= ", code_fournisseur = ".($this->code_fournisseur?"'".$this->code_fournisseur."'":"null");
	    
	    // Attention check_codecompta_fournisseur peut modifier le code 
	    // suivant le module utilisé
	    
	    $this->check_codecompta_fournisseur();
	    
	    $sql .= ", code_compta_fournisseur = ".($this->code_compta_fournisseur?"'".$this->code_compta_fournisseur."'":"null");
	  }


	
	if ($user) $sql .= ",fk_user_modif = '".$user->id."'";
	
	$sql .= " WHERE idp = '" . $id ."'";
	
	if ($this->db->query($sql)) 
	  {
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
        $interface=new Interfaces($this->db);
        $interface->run_triggers('COMPANY_MODIFY',$this,$user,$lang,$conf);
        // Fin appel triggers

	    $result = 0;
	  }
	else
	  {
	    if ($this->db->errno() == DB_ERROR_RECORD_ALREADY_EXISTS)
	      {
		// Doublon
		$this->error = $langs->trans("ErrorPrefixAlreadyExists",$this->prefix_comm);
		$result =  -1;
	      }
	    else
	      {
		dolibarr_syslog("Societe::Update echec sql=$sql");
		$result =  -2;
	      }	    
	  }
      }

    return $result;

  }
  
  /**
   *    \brief      Charge depuis la base l'objet societe
   *    \param      socid       id de la société à charger en mémoire
   *    \return     int         >0 si ok, <0 si erreur
   */
	 
  function fetch($socid, $user=0)
  {
    global $langs;
    
    /* Lecture des permissions */
    if ($user <> 0)
      {
	$sql = "SELECT p.pread, p.pwrite, p.pperms";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe_perms as p";
	$sql .= " WHERE p.fk_user = '".$user->id."'";
	$sql .= " AND p.fk_soc = '".$socid."';";
	
	$resql=$this->db->query($sql);
	
	if ($resql)
	  {
	    if ($row = $this->db->fetch_row($resql))
	      {
		$this->perm_read  = $row[0];
		$this->perm_write = $row[1];
		$this->perm_perms = $row[2];
	      }
	  }
      }

    $this->id = $socid;

    $sql = "SELECT s.idp, s.nom, s.address,".$this->db->pdate("s.datec")." as dc, prefix_comm";
    $sql .= ",". $this->db->pdate("s.tms")." as date_update";
    $sql .= ", s.tel, s.fax, s.url,s.cp,s.ville, s.note, s.siren, client, fournisseur";
    $sql .= ", s.siret, s.capital, s.ape, s.tva_intra, s.rubrique";
    $sql .= ", s.fk_typent as typent_id";
    $sql .= ", s.fk_effectif as effectif_id, e.libelle as effectif";
    $sql .= ", s.fk_forme_juridique as forme_juridique_code, fj.libelle as forme_juridique";
    $sql .= ", s.code_client, s.code_compta, s.code_fournisseur, s.parent";
    $sql .= ", s.fk_departement, s.fk_pays, s.fk_stcomm, s.remise_client";
    $sql .= ", p.code as pays_code, p.libelle as pays";
    $sql .= ", st.libelle as stcomm";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql .= ", ".MAIN_DB_PREFIX."c_effectif as e";
    $sql .= ", ".MAIN_DB_PREFIX."c_pays as p";
    $sql .= ", ".MAIN_DB_PREFIX."c_stcomm as st";
    $sql .= ", ".MAIN_DB_PREFIX."c_forme_juridique as fj";
    $sql .= " WHERE s.idp = ".$this->id;
    $sql .= " AND s.fk_stcomm = st.id";
    $sql .= " AND s.fk_effectif = e.id";
    $sql .= " AND s.fk_pays = p.rowid";
    $sql .= " AND s.fk_forme_juridique = fj.code";

    $resql=$this->db->query($sql);

    if ($resql)
      {
	if ($this->db->num_rows($resql))
	  {
	    $obj = $this->db->fetch_object($resql);

	    $this->date_update = $obj->date_update;

	    $this->nom = stripslashes($obj->nom);
	    $this->adresse =  stripslashes($obj->address);
	    $this->cp = $obj->cp;
	    $this->ville =  stripslashes($obj->ville);

	    $this->adresse_full =  stripslashes($obj->address) . "\n". $obj->cp . " ". stripslashes($obj->ville);

	    $this->departement_id = $obj->fk_departement;
	    $this->pays_id = $obj->fk_pays;
	    $this->pays_code = $obj->fk_pays?$obj->pays_code:'';
	    $this->pays = $obj->fk_pays?$obj->pays:'';

	    $transcode=$langs->trans("StatusProspect".$obj->fk_stcomm);
	    $libelle=($transcode!="StatusProspect".$obj->fk_stcomm?$transcode:$obj->stcomm);
	    $this->stcomm_id = $obj->fk_stcomm;     // id statut commercial
	    $this->statut_commercial = $libelle;    // libelle statut commercial

	    $this->url = $obj->url;
	    $this->tel = $obj->tel;
	    $this->fax = $obj->fax;
	      
	    $this->parent    = $obj->parent;

	    $this->siren     = $obj->siren;
	    $this->siret     = $obj->siret;
	    $this->ape       = $obj->ape;
	    $this->capital   = $obj->capital;

	    $this->code_client = $obj->code_client;
	    $this->code_fournisseur = $obj->code_fournisseur;

	    if (! $this->code_client && $this->mod_codeclient->code_modifiable_null == 1)
	      {
		$this->codeclient_modifiable = 1;
	      }

	    if (! $this->code_fournisseur && $this->mod_codefournisseur->code_modifiable_null == 1)
	      {
		$this->codefournisseur_modifiable = 1;
	      }

	    $this->code_compta = $obj->code_compta;
	    $this->code_compta_fournisseur = $obj->code_compta_fournisseur;

	    $this->tva_intra      = $obj->tva_intra;
	    $this->tva_intra_code = substr($obj->tva_intra,0,2);
	    $this->tva_intra_num  = substr($obj->tva_intra,2);

	    $this->typent_id      = $obj->typent_id;
	    //$this->typent         = $obj->fk_typent?$obj->typeent:'';

	    $this->effectif_id    = $obj->effectif_id;
	    $this->effectif       = $obj->effectif_id?$obj->effectif:'';

	    $this->forme_juridique_code= $obj->forme_juridique_code;
	    $this->forme_juridique     = $obj->forme_juridique_code?$obj->forme_juridique:'';

	    $this->prefix_comm = $obj->prefix_comm;

	    $this->remise_client = $obj->remise_client;
	      
	    $this->client      = $obj->client;
	    $this->fournisseur = $obj->fournisseur;

	    if ($this->client == 1)
	      {
		$this->nom_url = '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$this->id.'">'.$obj->nom.'</a>';
	      }
	    elseif($this->client == 2)
	      {
		$this->nom_url = '<a href="'.DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$this->id.'">'.$obj->nom.'</a>';
	      }
	    else
	      {
		$this->nom_url = '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$this->id.'">'.$obj->nom.'</a>';
	      }

	    $this->rubrique = $obj->rubrique;	      
	    $this->note = $obj->note;

	    $result = 1;	      
	  }
	else
	  {
	    dolibarr_syslog("Erreur Societe::Fetch aucune societe avec id=".$this->id);
	    $result = -2;
	  }

	$this->db->free($resql);
      }
    else
      {
	dolibarr_syslog("Erreur Societe::Fetch echec sql=$sql");
	dolibarr_syslog("Erreur Societe::Fetch ".$this->db->error());
	$result = -3;
      }

    return $result;
  }

  /**
   *    \brief      Suppression d'une societe de la base avec ses dépendances (contacts, rib...)
   *    \param      id      id de la societe à supprimer
   */
   
  function delete($id)
  {
    dolibarr_syslog("Societe::Delete");
    $sqr = 0;

    if ( $this->db->begin())
      {	  
	$sql = "DELETE from ".MAIN_DB_PREFIX."socpeople ";
	$sql .= " WHERE fk_soc = " . $id .";";
	  
	if ($this->db->query($sql))
	  {
	    $sqr++;
	  }
	else
	  {
	    $this->error .= "Impossible de supprimer les contacts.\n";
	    dolibarr_syslog("Societe::Delete erreur -1");
	  }

	$sql = "DELETE from ".MAIN_DB_PREFIX."societe_rib ";
	$sql .= " WHERE fk_soc = " . $id .";";

	if ($this->db->query($sql))
	  {
	    $sqr++;
	  }
	else
	  {
	    $this->error .= "Impossible de supprimer le RIB.\n";
	    dolibarr_syslog("Societe::Delete erreur -2");
	  }
	  	  
	$sql = "DELETE from ".MAIN_DB_PREFIX."societe ";
	$sql .= " WHERE idp = " . $id .";";
	
	if ($this->db->query($sql))
	  {
	    $sqr++;
	  }
	else
	  {
	    $this->error .= "Impossible de supprimer la société.\n";
	    dolibarr_syslog("Societe::Delete erreur -3");
	  }


	if ($sqr == 3)
	  {
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
        $interface=new Interfaces($this->db);
        $interface->run_triggers('COMPANY_DELETE',$this,$user,$lang,$conf);
        // Fin appel triggers

	    $this->db->commit();

	    // Suppression du répertoire document
	    $docdir = $conf->societe->dir_output . "/" . $id;
	      	      
	    if (file_exists ($docdir))
	      {
		$this->deldir($docdir);
	      }

	    return 0;
	  }
	else
	  {
	    $this->db->rollback();
	    return -1;
	  }
      }	  

  }

  /**
   *    \brief      Cette fonction permet de supprimer le répertoire de la societe
   *                et sous répertoire, meme s'ils contiennent des documents.
   *    \param      dir     repertoire a supprimer
   */
   
  function deldir($dir)
  {
    $current_dir = opendir($dir);

    while($entryname = readdir($current_dir))
      {
	if(is_dir("$dir/$entryname") and ($entryname != "." and $entryname!=".."))
	  {
	    deldir("${dir}/${entryname}");
	  }
	elseif($entryname != "." and $entryname!="..")
	  {
	    unlink("${dir}/${entryname}");
	  }
      }
    closedir($current_dir);
    rmdir(${dir});
  } 
  
  /**
   *    \brief     Retournes les factures impayées de la société
   *    \return    array   tableau des id de factures impayées
   *
   */
   
  function factures_impayes()
  {
    $facimp = array();
    /*
     * Lignes
     */      
    $sql = "SELECT f.rowid";
    $sql .= " FROM ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = '".$this->id . "'";
    $sql .= " AND f.fk_statut = '1' AND f.paye = '0'";
    
    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
	
	while ($i < $num)
	  {
	    $objp = $this->db->fetch_object();
	    $array_push($facimp, $objp->rowid);
	    $i++;
	    print $i;
	  }
	
	$this->db->free();
      } 
    return $facimp;
  }

  /**
   *    \brief      Attribut le prefix de la société en base
   *
   */
  
  function attribute_prefix()
  {
    $sql = "SELECT nom FROM ".MAIN_DB_PREFIX."societe WHERE idp = '$this->id'";
    if ( $this->db->query( $sql) )
      {
	if ( $this->db->num_rows() )
	  {
	    $nom = preg_replace("/[[:punct:]]/","",$this->db->result(0,0));
	    $this->db->free();
	    
	    $prefix = $this->genprefix($nom,4);
	    
	    $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."societe WHERE prefix_comm = '$prefix'";
	    if ( $this->db->query( $sql) )
	      {
		if ( $this->db->result(0, 0) )
		  {
		    $this->db->free();
		  }
		else
		  {
		    $this->db->free();
		    
		    $sql = "UPDATE ".MAIN_DB_PREFIX."societe set prefix_comm='$prefix' WHERE idp='$this->id'";
		    
		    if ( $this->db->query( $sql) )
		      {
			
		      }
		    else
		      {
			dolibarr_print_error($this->db);
		      }
		  }
	      }
	    else
	      {
	        dolibarr_print_error($this->db);
	      }
	  }
      }
    else
      {
	dolibarr_print_error($this->db);
      }
    return $prefix;
  }
  
  /**
   *    \brief      Génère le préfix de la société
   *    \param      nom         nom de la société
   *    \param      taille      taille du prefix à retourner
   *    \param      mot         l'indice du mot à utiliser
   *
   */
	 
  function genprefix($nom, $taille=4,$mot=0)
  {
    $retour = "";
    $tab = explode(" ",$nom);
    if($mot < count($tab)) {
      $prefix = strtoupper(substr($tab[$mot],0,$taille));
      //On vérifie que ce prefix n'a pas déjà été pris ...
      $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."societe WHERE prefix_comm = '$prefix'";
      if ( $this->db->query( $sql) )
	{
	  if ( $this->db->result(0, 0) )
	    {
	      $this->db->free();
	      $retour = $this->genprefix($nom,$taille,$mot+1);
	    }
	  else
	    {
	      $retour = $prefix;
	    }
	}
    }
    return $retour;
  }

  /**
   *    \brief     Définit la société comme un client
   *
   */
	 
  function set_as_client()
  {
    if ($this->id)
      {
	$sql  = "UPDATE ".MAIN_DB_PREFIX."societe ";
	$sql .= " SET client = 1";
	$sql .= " WHERE idp = " . $this->id .";";
	  
	return $this->db->query($sql);
      }
  }

  /**
   *    \brief      Définit la société comme un client
   *    \param      remise      montant de la remise
   *    \param      user        utilisateur qui place la remise
   *
   */
	 
  function set_remise_client($remise, $user)
  {
    if ($this->id)
      {
	$sql  = "UPDATE ".MAIN_DB_PREFIX."societe ";
	$sql .= " SET remise_client = '".$remise."'";
	$sql .= " WHERE idp = " . $this->id .";";
	  
	$this->db->query($sql);

	$sql  = "INSERT INTO ".MAIN_DB_PREFIX."societe_remise ";
	$sql .= " ( datec, fk_soc, remise_client, fk_user_author )";
	$sql .= " VALUES (now(),".$this->id.",'".$remise."',".$user->id.")";
	  
	if (! $this->db->query($sql) )
	  {
	    dolibarr_print_error($this->db);
	  }

      }
  }
  /**
   *    \brief      Définit la société comme un client
   *    \param      remise      montant de la remise
   *    \param      user        utilisateur qui place la remise
   *
   */
	 
  function set_remise_except($remise, $user)
  {
    if ($this->id)
      {
	$remise = ereg_replace(",",".",$remise);

	$sql  = "DELETE FROM  ".MAIN_DB_PREFIX."societe_remise_except ";
	$sql .= " WHERE fk_soc = " . $this->id ." AND fk_facture IS NULL;";
	  
	$this->db->query($sql);

	$sql  = "INSERT INTO ".MAIN_DB_PREFIX."societe_remise_except ";
	$sql .= " ( datec, fk_soc, amount_ht, fk_user )";
	$sql .= " VALUES (now(),".$this->id.",'".$remise."',".$user->id.")";
	  
	if (! $this->db->query($sql) )
	  {
	    dolibarr_print_error($this->db);
	  }

      }
  }
  /**
   *
   *
   *
   */
  function add_commercial($user, $commid)
  {
    if ($this->id > 0 && $commid > 0)
      {
	$sql  = "DELETE FROM  ".MAIN_DB_PREFIX."societe_commerciaux ";
	$sql .= " WHERE fk_soc = " . $this->id ." AND fk_user =".$commid;
	  
	$this->db->query($sql);

	$sql  = "INSERT INTO ".MAIN_DB_PREFIX."societe_commerciaux ";
	$sql .= " ( fk_soc, fk_user )";
	$sql .= " VALUES (".$this->id.",".$commid.")";
	  
	if (! $this->db->query($sql) )
	  {
	    dolibarr_syslog("Societe::add_commercial Erreur");
	  }

      }
  }
  /**
   *
   *
   *
   */
  function del_commercial($user, $commid)
  {
    if ($this->id > 0 && $commid > 0)
      {
	$sql  = "DELETE FROM  ".MAIN_DB_PREFIX."societe_commerciaux ";
	$sql .= " WHERE fk_soc = " . $this->id ." AND fk_user =".$commid;
	  
	if (! $this->db->query($sql) )
	  {
	    dolibarr_syslog("Societe::del_commercial Erreur");
	  }

      }
  }
  /**
   *    \brief      Renvoie le nom d'une societe a partir d'un id
   *    \param      id      id de la société recherchée
   *
   */
	 
  function get_nom($id)
  {

    $sql = "SELECT nom FROM ".MAIN_DB_PREFIX."societe WHERE idp='$id';";
      
    $result = $this->db->query($sql);
      
    if ($result)
      {
    	if ($this->db->num_rows())
    	  {
    	    $obj = $this->db->fetch_object($result);
    	    return $obj->nom;
    	  }
    	$this->db->free();
      }
    else {
      dolibarr_print_error($this->db);   
    }    
       
  }

  /**
   *    \brief      Renvoie la liste des contacts emails existant pour la société
   *    \return     array       tableau des contacts emails
   */
	 
  function contact_email_array()
  {
    $contact_email = array();

    $sql = "SELECT idp, email, name, firstname FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = '$this->id'";
      
    if ($this->db->query($sql) )
      {
	$nump = $this->db->num_rows();

	if ($nump)
	  {
	    $i = 0;
	    while ($i < $nump)
	      {
		$obj = $this->db->fetch_object();
	      
		$contact_email[$obj->idp] = "$obj->firstname $obj->name &lt;$obj->email&gt;";
		$i++;
	      }
	  }
	return $contact_email;
      }
    else
      {
	dolibarr_print_error($this->db);
      }
      
  }

  /**
   *    \brief      Renvoie la liste des contacts de cette société
   *    \return     array      tableau des contacts
   */

  function contact_array()
  {
    $contacts = array();

    $sql = "SELECT idp, name, firstname FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = '$this->id'";
      
    if ($this->db->query($sql) )
      {
	$nump = $this->db->num_rows();

	if ($nump)
	  {
	    $i = 0;
	    while ($i < $nump)
	      {
		$obj = $this->db->fetch_object();
	      
		$contacts[$obj->idp] = "$obj->firstname $obj->name";
		$i++;
	      }
	  }
	return $contacts;
      }
    else
      {
	dolibarr_print_error($this->db);
      }
      
  }
  
  /**
   *    \brief      Renvoie l'email d'un contact par son id
   *    \param      rowid       id du contact
   *    \return     string      email du contact
   */
	 
  function contact_get_email($rowid)
  {

    $sql = "SELECT idp, email, name, firstname FROM ".MAIN_DB_PREFIX."socpeople WHERE idp = '$rowid'";
      
    if ($this->db->query($sql) )
      {
	$nump = $this->db->num_rows();

	if ($nump)
	  {
	      
	    $obj = $this->db->fetch_object();
	      
	    $contact_email = "$obj->firstname $obj->name <$obj->email>";

	  }
	return $contact_email;
      }
    else
      {
	dolibarr_print_error($this->db);
      }
      
  }

    /**
     *    \brief      Renvoie la liste des libellés traduits types actifs de sociétés
     *    \return     array      tableau des types
     */
    function typent_array()
    {
        global $langs;
        
        $effs = array();
    
        $sql  = "SELECT id, code, libelle";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_typent";
        $sql .= " WHERE active = 1";
        $sql .= " ORDER by id";
        $result=$this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
    
            while ($i < $num)
            {
                $objp = $this->db->fetch_object($result);
                if ($langs->trans($objp->code) != $objp->code)
                    $effs[$objp->id] = $langs->trans($objp->code);
                else
                    $effs[$objp->id] = $objp->libelle!='-'?$objp->libelle:'';
                $i++;
            }
            $this->db->free($result);
        }
    
        return $effs;
    }

    
    /**
     *    \brief      Renvoie la liste des types d'effectifs possibles (pas de traduction car nombre)
     *    \return     array      tableau des types d'effectifs
     */
    function effectif_array()
    {
        $effs = array();
    
        $sql = "SELECT id, libelle";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_effectif";
        $sql .= " ORDER BY id ASC";
        if ($this->db->query($sql))
        {
            $num = $this->db->num_rows();
            $i = 0;
    
            while ($i < $num)
            {
                $objp = $this->db->fetch_object();
                $effs[$objp->id] = $objp->libelle!='-'?$objp->libelle:'';
                $i++;
            }
            $this->db->free();
        }
        return $effs;
    }
    
    /**
     *    \brief      Renvoie la liste des formes juridiques existantes (pas de traduction car unique au pays)
     *    \return     array      tableau des formes juridiques
     */
    function forme_juridique_array()
    {
        $fj = array();
    
        $sql = "SELECT code, libelle";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_forme_juridique";
        $sql .= " ORDER BY code ASC";
        if ($this->db->query($sql))
        {
            $num = $this->db->num_rows();
            $i = 0;
    
            while ($i < $num)
            {
                $objp = $this->db->fetch_object();
                $fj[$objp->code] = $objp->libelle!='-'?$objp->libelle:'';
                $i++;
            }
            $this->db->free();
        }
        return $fj;
    }

  /**
   *    \brief      Affiche le rib
   */  
  
  function display_rib()
  {
    global $langs;
    
    require_once DOL_DOCUMENT_ROOT . "/companybankaccount.class.php";
    
    $bac = new CompanyBankAccount($this->db, $this->id);
    $bac->fetch();

    if ($bac->code_banque || $bac->code_guichet || $bac->number || $bac->cle_rib)
    {
        $rib = $bac->code_banque." ".$bac->code_guichet." ".$bac->number." (".$bac->cle_rib.")";
    }
    else
    {
        $rib=$langs->trans("NoRIB");
    }
    return $rib;
  }


  function rib()
  {
    require_once DOL_DOCUMENT_ROOT . "/companybankaccount.class.php";
    
    $bac = new CompanyBankAccount($this->db, $this->id);
    $bac->fetch();

    $this->bank_account = $bac;

    return 1;
  }


  function verif_rib()
  {
    $this->rib();
    return $this->bank_account->verif();
  }

  /**
   *    \brief      Verifie code client
   *    \return     Renvoie 0 si ok, peut modifier le code client suivant le module utilisé
   */
   
  function verif_codeclient()
  {
    if (defined('CODECLIENT_ADDON') && strlen(CODECLIENT_ADDON) > 0)
      {

	require_once DOL_DOCUMENT_ROOT.'/includes/modules/societe/'.CODECLIENT_ADDON.'.php';

	$var = CODECLIENT_ADDON;

	$mod = new $var;

	return $mod->verif($this->db, $this->code_client, $this->id);
      }
    else
      {
	return 0;
      }
  }

  function check_codeclient()
  {
    if (defined('CODECLIENT_ADDON') && strlen(CODECLIENT_ADDON) > 0)
      {

	require_once DOL_DOCUMENT_ROOT.'/includes/modules/societe/'.CODECLIENT_ADDON.'.php';

	$var = CODECLIENT_ADDON;

	$mod = new $var;

	return $mod->verif($this->db, $this->code_client);
      }
    else
      {
	return 0;
      }
  }

  function check_codefournisseur()
  {
    if (defined('CODEFOURNISSEUR_ADDON') && strlen(CODEFOURNISSEUR_ADDON) > 0)
      {

	require_once DOL_DOCUMENT_ROOT.'/includes/modules/societe/'.CODEFOURNISSEUR_ADDON.'.php';

	$var = CODEFOURNISSEUR_ADDON;

	$mod = new $var;

	return $mod->verif($this->db, $this->code_fournisseur);
      }
    else
      {
	return 0;
      }
  }
  
  /**
   *    \brief  Renvoie un code compta, suivant le module le code compta renvoyé 
   *            peut être identique à celui saisit ou généré automatiquement
   *
   *            A ce jour seul la génération automatique est implémentée
   */
  function check_codecompta_client()
  {
    if (defined('CODECOMPTA_ADDON') && strlen(CODECOMPTA_ADDON) > 0)
    {
        require_once DOL_DOCUMENT_ROOT.'/includes/modules/societe/'.CODECOMPTA_ADDON.'.php';
    
        $var = CODECOMPTA_ADDON;
    
        $mod = new $var;
    
        $result = $mod->get_code($this->db, $this);
    
        $this->code_compta = $mod->code;
    
        return $result;
    }
    else
    {
        $this->code_compta = '';
        return 0;
    }
  }


  /**
   *    \brief  Renvoie un code compta, suivant le module le code compta renvoyé 
   *            peut être identique à celui saisit ou généré automatiquement
   *
   *            A ce jour seul la génération automatique est implémentée
   */
  function check_codecompta_fournisseur()
  {
    if (defined('CODECOMPTAFOURN_ADDON') && strlen(CODECOMPTAFOURN_ADDON) > 0)
    {
        require_once DOL_DOCUMENT_ROOT.'/includes/modules/societe/'.CODECOMPTAFOURN_ADDON.'.php';
    
        $var = CODECOMPTAFOURN_ADDON;
    
        $mod = new $var;
    
        $result = $mod->get_code($this->db, $this);
    
        $this->code_compta_fournisseur = $mod->code;
    
        return $result;
    }
    else
    {
        $this->code_compta = '';
        return 0;
    }
  }
  
  
  /**
   *    \brief      Défini la société mère pour les filiales
   *    \param      id      id compagnie mère à positionner
   *    \return     int     <0 si ko, >0 si ok
   */
    function set_parent($id)
    {
        if ($this->id)
        {
            $sql  = "UPDATE ".MAIN_DB_PREFIX."societe ";
            $sql .= " SET parent = ".$id;
            $sql .= " WHERE idp = " . $this->id .";";
            
            if ( $this->db->query($sql) )
            {
                return 1;
            }
            else
            {
                return -1;
            }
        }
    }

  /**
   *    \brief      Supprime la société mère
   *    \param      id      id compagnie mère à effacer
   *    \return     int     <0 si ko, >0 si ok
   */
    function remove_parent($id)
    {
        if ($this->id)
        {
            $sql  = "UPDATE ".MAIN_DB_PREFIX."societe ";
            $sql .= " SET parent = null";
            $sql .= " WHERE idp = " . $this->id .";";
            
            if ( $this->db->query($sql) )
            {
                return 1;
            }
            else
            {
                return -1;
            }
        }
    }

  /**
   *    \brief  Verifie la validite du siren
   */
  function check_siren()
  {
    if (strlen($this->siren) == 9)
      {
	$sum = 0;

	for ($i = 0 ; $i < 10 ; $i = $i+2)
	  {
	    $sum = $sum + substr($this->siren, (8 - $i), 1);
	  }

	for ($i = 1 ; $i < 9 ; $i = $i+2)
	  {
	    $ps = 2 * substr($this->siren, (8 - $i), 1);

	    if ($ps > 9)
	      {
		$ps = substr($ps, 0,1) + substr($ps, 1 ,1);
	      }
	    $sum = $sum + $ps;
	  }

	if (substr($sum, -1) == 0)
	  {
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

	/**
	 *    \brief      Indique si la société a des projets
	 *    \return     bool	   true si la société a des projets, false sinon
	 */
	function has_projects()
	{
		$sql = 'SELECT COUNT(*) as numproj FROM '.MAIN_DB_PREFIX.'projet WHERE fk_soc = ' . $this->id;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$nump = $this->db->num_rows($resql);
			$obj = $this->db->fetch_object();
			$count = $obj->numproj;
		}
		else
		{
			$count = 0;
			print $this->db->error();
		}
		$this->db->free($resql);
		return ($count > 0);
    }

}

?>
