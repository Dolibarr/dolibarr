<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!	\file       htdocs/societe.class.php
		\ingroup    societe
		\brief      Fichier de la classe des societes
		\version    $Revision$
*/


/*! \class Societe
		\brief Classe permettant la gestion des societes
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
  var $tel;
  var $fax;
  var $url;
  var $siren;
  var $forme_juridique_code;
  var $forme_juridique;
  var $client;
  var $note;
  var $fournisseur;
 

  /**
   *    \brief  Constructeur de la classe
   *    \param  DB     handler accès base de données
   *    \param  id     id societe (0 par defaut)
   */
	 
  function Societe($DB, $id=0)
  {
    global $config;

    $this->db = $DB;
    $this->id = $id;
    $this->client = 0;
    $this->fournisseur = 0;
    $this->effectif_id  = 0;
    $this->forme_juridique_code  = 0;

    return 1;
  }

  /**
   *    \brief  Crée la societe en base
   *    \param  user    Utilisateur qui demande la création
   */
	 
  function create($user='')
  {
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."societe (nom, datec, datea, fk_user_creat) ";
    $sql .= " VALUES ('".trim($this->nom)."', now(), now(), '".$user->id."');";

    if ($this->db->query($sql) ) {
      $id = $this->db->last_insert_id();

      $result=$this->update($id);
      if ($result < 0) { return $result; }

      return $id;
    } else {
    	dolibarr_print_error($this->db);
    }        
        
  }

  /**
   *    \brief  Mise a jour des paramètres de la société
   *    \param  id      id societe
   *    \param  user    Utilisateur qui demande la mise à jour
   */
	 
  function update($id,$user='')
  {
    if (strlen(trim($this->nom)) == 0)
      {
	$this->nom = "VALEUR MANQUANTE";
      }

    if (strlen(trim($this->capital)) == 0)
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
	if (strlen(trim($this->cp)) == 5)
	  {
	    $depid = departement_rowid($this->db, 
				       substr(trim($this->cp),0,2), 
				       $this->pays_id);
	    if ($depid > 0)
	      {
		$this->departement_id = $depid;
	      }
	  }
      }
    

    $sql = "UPDATE ".MAIN_DB_PREFIX."societe ";
    $sql .= " SET nom = '" . trim($this->nom) ."'"; // Champ obligatoire

    if (trim($this->adresse))  
      { $sql .= ",address = '" . trim($this->adresse) ."'"; }

    if (trim($this->cp))
      { $sql .= ",cp = '" . trim($this->cp) ."'"; }

    if (trim($this->ville))
      { $sql .= ",ville = '" . trim($this->ville) ."'"; }

    if (trim($this->departement_id)) 
      { $sql .= ",fk_departement = '" . $this->departement_id ."'"; }

    if (trim($this->pays_id))
      { $sql .= ",fk_pays = '" . $this->pays_id ."'"; }

    if (trim($this->tel))             { $sql .= ",tel = '" . trim($this->tel) ."'"; }
    if (trim($this->fax))             { $sql .= ",fax = '" . trim($this->fax) ."'"; }
    if (trim($this->url))             { $sql .= ",url = '" . trim($this->url) ."'"; }
    if (trim($this->siren))           { $sql .= ",siren = '" . trim($this->siren) ."'"; }
    if (trim($this->siret))           { $sql .= ",siret = '" . trim($this->siret) ."'"; }
    if (trim($this->ape))             { $sql .= ",ape = '" . trim($this->ape) ."'"; }
    if (trim($this->prefix_comm))     { $sql .= ",prefix_comm = '" . trim($this->prefix_comm) ."'"; }
    if (trim($this->tva_intra))       { $sql .= ",tva_intra = '" . trim($this->tva_intra) ."'"; }
    if (trim($this->capital))         { $sql .= ",capital = '" . trim($this->capital) ."'"; }
    if (trim($this->effectif_id))     { $sql .= ",fk_effectif = '" . trim($this->effectif_id) ."'"; }

    if (trim($this->forme_juridique_code))
      { 
	$sql .= ",fk_forme_juridique = '" . trim($this->forme_juridique_code) ."'";
      }
    if (trim($this->client))          { $sql .= ",client = '" . $this->client ."'"; }
    if (trim($this->fournisseur))     { $sql .= ",fournisseur = '" . $this->fournisseur ."'"; }
    if ($user)                        { $sql .= ",fk_user_modif = '".$user->id."'"; }


    $sql .= " , code_client = '". strtoupper(ereg_replace("[^[:alnum:]]", "", $this->code_client)) ."'";

    $sql .= " WHERE idp = '" . $id ."'";

    if ($this->db->query($sql)) 
      {
    	return 0;
      }
    else
      {
    	if ($this->db->errno() == $this->db->ERROR_DUPLICATE)
    	  {
    	    // Doublon
    	    return -1;
    	  }
    
    	dolibarr_print_error($this->db);
      }
  }

  /**
   *    \brief      Recupére l'objet societe
   *    \param      socid       id de la société à charger en mémoire
   */
	 
  function fetch($socid)
    {
      $this->id = $socid;

      $sql = "SELECT s.idp, s.nom, s.address,".$this->db->pdate("s.datec")." as dc, prefix_comm";
      $sql .= ",". $this->db->pdate("s.tms")." as date_update";
      $sql .= ", s.tel, s.fax, s.url,s.cp,s.ville, s.note, s.siren, client, fournisseur";
      $sql .= ", s.siret, s.capital, s.ape, s.tva_intra, s.rubrique, s.fk_effectif";
      $sql .= ", e.libelle as effectif, e.id as effectif_id";
      $sql .= ", s.fk_forme_juridique as forme_juridique_code, fj.libelle as forme_juridique, s.code_client";
      $sql .= ", s.fk_departement, s.fk_pays, s.fk_stcomm, s.remise_client";
      $sql .= ", p.libelle as pays";
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

      if ($this->db->query($sql))
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object();

	      $this->date_update = $obj->date_update;

	      $this->nom = stripslashes($obj->nom);
	      $this->adresse =  stripslashes($obj->address);
	      $this->cp = $obj->cp;
	      $this->ville =  stripslashes($obj->ville);

	      $this->adresse_full =  stripslashes($obj->address) . "\n". $obj->cp . " ". stripslashes($obj->ville);

	      $this->departement_id = $obj->fk_departement;
	      $this->pays_id = $obj->fk_pays;
	      $this->pays = $obj->fk_pays?$obj->pays:'';

	      $this->stcomm_id = $obj->fk_stcomm; // statut commercial
	      $this->statut_commercial = $obj->stcomm; // statut commercial

	      $this->url = $obj->url;

	      $this->tel = $obj->tel;
	      $this->fax = $obj->fax;
	      
	      $this->siren     = $obj->siren;
	      $this->siret     = $obj->siret;
	      $this->ape       = $obj->ape;
	      $this->capital   = $obj->capital;

	      $this->code_client = $obj->code_client;

	      $this->tva_intra      = $obj->tva_intra;
	      $this->tva_intra_code = substr($obj->tva_intra,0,2);
	      $this->tva_intra_num  = substr($obj->tva_intra,2);

	      $this->effectif       = $obj->effectif;
	      $this->effectif_id    = $obj->effectif_id;

	      $this->forme_juridique_code= $obj->forme_juridique_code;
	      $this->forme_juridique     = $obj->forme_juridique;

	      $this->prefix_comm = $obj->prefix_comm;

	      $this->remise_client = $obj->remise_client;
	      
	      $this->client = $obj->client;
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
	      print "Aucun enregistrement trouvé<br>$sql";
	      $result = -2;
	    }

	  $this->db->free();
	}
      else
	{
	  dolibarr_syslog("Erreur Societe::Fetch");
	  $result = -3;
	}

      return $result;
  }

  /**
   * \brief     Suppression d'une societe de la base 
   * \todo      Cette fonction n'est pas utilisée. Attente des contraintes d'intégrité dans MySql
   */
	 
  function delete($id)
    {
      $sql = "DELETE from ".MAIN_DB_PREFIX."societe ";
      $sql .= " WHERE idp = " . $id .";";

      if (! $this->db->query($sql))
	{
	  dolibarr_print_error($this->db);
	}

      // Suppression du répertoire document
      $docdir = SOCIETE_OUTPUTDIR . "/$id";

      // Cette fonction permet de supprimer le répertoire de la societe
      // Meme s'il contient des documents.
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
      
      if (file_exists ($docdir))
	{
	  deldir($docdir);
	}
    }

  /**
   * \brief     Retournes les factures impayées de la société
   * \return    array   tableau des id de factures impayées
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
      $prefix = substr($tab[$mot],0,$taille);
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
   *    \brief      Renvoie la liste des types d'effectifs possibles
   *    \return     array      tableau des types d'effectifs
   */
	 
  function effectif_array()
    {
      $effs = array();
      /*
       * Lignes
       */      
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
	      $effs[$objp->id] = $objp->libelle;
	      $i++;
	    }
	  $this->db->free();
	} 
      return $effs;
    }

  /**
   *    \brief      Renvoie la liste des formes juridiques existantes
   *    \return     array      tableau des formes juridiques
   */
	 
  function forme_juridique_array()
  {
    $fj = array();
    /*
     * Lignes
     */      
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
	    $fj[$objp->code] = $objp->libelle;
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
    require_once DOL_DOCUMENT_ROOT . "/companybankaccount.class.php";
    
    $bac = new CompanyBankAccount($this->db, $this->id);
    $bac->fetch();

    $rib = $bac->code_banque." ".$bac->code_guichet." ".$bac->number." ".$bac->cle_rib;

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
}

?>
