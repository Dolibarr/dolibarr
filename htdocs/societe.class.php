<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2003      Brian Fraval         <brian@fraval.org>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008	   Patrick Raguin       <patrick.raguin@auguria.net>
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
 */

/**
 *	\file       htdocs/societe.class.php
 *	\ingroup    societe
 *	\brief      Fichier de la classe des societes
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/commonobject.class.php");


/**
 *	\class 		Societe
 *	\brief 		Classe permettant la gestion des societes
 */
class Societe extends CommonObject
{
	var $db;
	var $error;
	var $errors=array();
	var $table_element = 'societe';

	var $id;
	var $nom;
	var $nom_particulier;
	var $prenom;
	var $particulier;
	var $adresse;
	var $cp;
	var $ville;
	var $departement_id;
	var $pays_id;
	var $pays_code;
	var $tel;
	var $fax;
	var $email;
	var $url;
	var $gencod;

	// 4 identifiants professionnels (leur utilisation depend du pays)
	var $siren;		// IdProf1
	var $siret;		// IdProf2
	var $ape;		// IdProf3
	var $idprof4;	// IdProf4

	var $prefix_comm;

	var $tva_assuj;
	var $tva_intra;

	var $capital;
	var $typent_id;
	var $typent_code;
	var $effectif_id;
	var $forme_juridique_code;
	var $forme_juridique;

	var $remise_client;
	var $mode_reglement;
	var $cond_reglement;

	var $client;					// 0=no customer, 1=customer, 2=prospect
	var $prospect;					// 0=no prospect, 1=prospect
	var $fournisseur;				// =0no supplier, 1=supplier

	var $prefixCustomerIsRequired;
	var $prefixSupplierIsRequired;
	var $code_client;
	var $code_fournisseur;
	var $code_compta;
	var $code_compta_fournisseur;

	var $note;
	//! code statut prospect
	var $stcomm_id;
	var $statut_commercial;

	var $price_level;

	var $commercial_id; //Id du commercial affecte


	/**
	 *    \brief  Constructeur de la classe
	 *    \param  DB     handler acc�s base de donn�es
	 *    \param  id     id societe (0 par defaut)
	 */
	function Societe($DB, $id=0)
	{
		global $conf;

		$this->db = $DB;

		$this->id = $id;
		$this->client = 0;
		$this->prospect = 0;
		$this->fournisseur = 0;
		$this->typent_id  = 0;
		$this->effectif_id  = 0;
		$this->forme_juridique_code  = 0;
		$this->prefixCustomerIsRequired = 0;
		$this->prefixSupplierIsRequired = 0;
		$this->tva_assuj = 1;

		return 1;
	}


	/**
	 *    \brief      Create third party in database
	 *    \param      user        Object of user that ask creation
	 *    \return     int         >= 0 if OK, < 0 if KO
	 */
	function create($user='')
	{
		global $langs,$conf;

		// Clean parameters
		$this->nom=trim($this->nom);

		dol_syslog("Societe::create ".$this->nom);

		$this->db->begin();

		// For automatic creation during create action (not used by Dolibarr)
		if ($this->code_client == -1)      $this->get_codeclient($this->prefix_comm,0);
		if ($this->code_fournisseur == -1) $this->get_codefournisseur($this->prefix_comm,1);

		$result = $this->verify();

		if ($result >= 0)
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe (nom, datec, datea, fk_user_creat)";
			$sql.= " VALUES ('".addslashes($this->nom)."', ".$this->db->idate(gmmktime()).", ".$this->db->idate(gmmktime()).",";
			$sql.= " ".($user->id > 0 ? "'".$user->id."'":"null");
			$sql.= ")";

			dol_syslog("Societe::create sql=".$sql);
			$result=$this->db->query($sql);
			if ($result)
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."societe");

				$ret = $this->update($this->id,$user,0,1,1);

				// si un commercial cree un client il lui est affecte automatiquement
				if (!$user->rights->societe->client->voir)
				{
					$this->add_commercial($user, $user->id);
				}
				// Ajout du commercial affecte
				else if ($this->commercial_id != '' && $this->commercial_id != -1)
				{
					$this->add_commercial($user, $this->commercial_id);
				}

				// si le fournisseur est classe on l'ajoute
				$this->AddFournisseurInCategory($this->fournisseur_categorie);

				if ($ret >= 0)
				{
					$this->use_webcal=($conf->global->PHPWEBCALENDAR_COMPANYCREATE=='always'?1:0);

					// Appel des triggers
					include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
					$interface=new Interfaces($this->db);
					$result=$interface->run_triggers('COMPANY_CREATE',$this,$user,$langs,$conf);
					if ($result < 0) { $error++; $this->errors=$interface->errors; }
					// Fin appel triggers

					dol_syslog("Societe::Create success id=".$this->id);
					$this->db->commit();
					return 0;
				}
				else
				{
					dol_syslog("Societe::Create echec update");
					$this->db->rollback();
					return -3;
				}
			}
			else
			{
				if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
				{

					$this->error=$langs->trans("ErrorCompanyNameAlreadyExists",$this->nom);
				}
				else
				{
					dol_syslog("Societe::Create echec insert sql=".$sql);
				}
				$this->db->rollback();
				return -2;
			}

		}
		else
		{
			$this->db->rollback();
			dol_syslog("Societe::Create echec verify sql=".$sql);
			return -1;
		}
	}

	/**
	 *    \brief      Verification lors de la modification
	 *    \return     int		0 si OK, <0 si KO
	 */
	function verify()
	{
		$this->errors=array();

		$result = 0;
		$this->nom=trim($this->nom);

		if (! $this->nom)
		{
			$this->errors[] = 'ErrorBadThirdPartyName';
			$result = -2;
		}
		if ($this->client && $this->codeclient_modifiable())
		{
			// On ne verifie le code client que si la societe est un client / prospect et que le code est modifiable
			// Si il n'est pas modifiable il n'est pas mis a jour lors de l'update
			$rescode = $this->check_codeclient();
			if ($rescode <> 0)
			{
				if ($rescode == -1)
				{
					$this->errors[] = 'ErrorBadCustomerCodeSyntax';
				}
				if ($rescode == -2)
				{
					$this->errors[] = 'ErrorCustomerCodeRequired';
				}
				if ($rescode == -3)
				{
					$this->errors[] = 'ErrorCustomerCodeAlreadyUsed';
				}
				if ($rescode == -4)
				{
					$this->errors[] = 'ErrorPrefixRequired';
				}
				$result = -3;
			}
		}
		if ($this->fournisseur && $this->codefournisseur_modifiable())
		{
			// On ne verifie le code fournisseur que si la societe est un fournisseur et que le code est modifiable
			// Si il n'est pas modifiable il n'est pas mis a jour lors de l'update
			$rescode = $this->check_codefournisseur();
			if ($rescode <> 0)
			{
				if ($rescode == -1)
				{
					$this->errors[] = 'ErrorBadSupplierCodeSyntax';
				}
				if ($rescode == -2)
				{
					$this->errors[] = 'ErrorSupplierCodeRequired';
				}
				if ($rescode == -3)
				{
					$this->errors[] = 'ErrorSupplierCodeAlreadyUsed';
				}
				if ($rescode == -5)
				{
					$this->errors[] = 'ErrorprefixRequired';
				}
				$result = -3;
			}
		}
		if (valid_url($this->url) == 0)
		{
			$this->errors[] = 'ErrorUrlNotValid';
			$result = -4;
		}

		return $result;
	}

	/**
	 *      \brief      Update parameters of third party
	 *      \param      id              			id societe
	 *      \param      user            			Utilisateur qui demande la mise a jour
	 *      \param      call_trigger    			0=non, 1=oui
	 *		\param		allowmodcodeclient			Autorise modif code client
	 *		\param		allowmodcodefournisseur		Autorise modif code fournisseur
	 *      \return     int             			<0 si ko, >=0 si ok
	 */
	function update($id, $user='', $call_trigger=1, $allowmodcodeclient=0, $allowmodcodefournisseur=0)
	{
		global $langs,$conf;

		dol_syslog("Societe::Update id=".$id." call_trigger=".$call_triger." allowmodcodeclient=".$allowmodcodeclient." allowmodcodefournisseur=".$allowmodcodefournisseur);

		// Clean parameters
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
		$this->tel = ereg_replace(" ","",$this->tel);
		$this->tel = ereg_replace("\.","",$this->tel);
		$this->fax = ereg_replace(" ","",$this->fax);
		$this->fax = ereg_replace("\.","",$this->fax);
		$this->email=trim($this->email);
		$this->url=$this->url?clean_url($this->url,0):'';
		$this->siren=trim($this->siren);
		$this->siret=trim($this->siret);
		$this->ape=trim($this->ape);
		$this->idprof4=trim($this->idprof4);
		$this->prefix_comm=trim($this->prefix_comm);

		$this->tva_assuj=trim($this->tva_assuj);
		$this->tva_intra=sanitizeFileName($this->tva_intra,'');

		$this->capital=trim($this->capital);
		if (strlen($this->capital) == 0) $this->capital = 0;

		$this->effectif_id=trim($this->effectif_id);
		$this->forme_juridique_code=trim($this->forme_juridique_code);

		//Gencod
        $this->gencod=trim($this->gencod);

		// For automatic creation (not used by Dolibarr)
		if ($this->code_client == -1) $this->get_codeclient($this->prefix_comm,0);
		if ($this->code_fournisseur == -1) $this->get_codefournisseur($this->prefix_comm,1);

		// Check name is required and codes are ok or unique.
		// If error, this->errors[] is filled
		$result = $this->verify();

		if ($result >= 0)
		{
			dol_syslog("Societe::Update verify ok");

			$sql = "UPDATE ".MAIN_DB_PREFIX."societe";
			$sql.= " SET nom = '" . addslashes($this->nom) ."'"; // Champ obligatoire
			$sql.= ",datea = ".$this->db->idate(mktime());
			$sql.= ",address = '" . addslashes($this->adresse) ."'";

			$sql.= ",cp = ".($this->cp?"'".$this->cp."'":"null");
			$sql.= ",ville = ".($this->ville?"'".addslashes($this->ville)."'":"null");

			$sql .= ",fk_departement = '" . ($this->departement_id?$this->departement_id:'0') ."'";
			$sql .= ",fk_pays = '" . ($this->pays_id?$this->pays_id:'0') ."'";

			$sql .= ",tel = ".($this->tel?"'".addslashes($this->tel)."'":"null");
			$sql .= ",fax = ".($this->fax?"'".addslashes($this->fax)."'":"null");
			$sql .= ",email = ".($this->email?"'".addslashes($this->email)."'":"null");
			$sql .= ",url = ".($this->url?"'".addslashes($this->url)."'":"null");

			$sql .= ",siren   = '". addslashes($this->siren)   ."'";
			$sql .= ",siret   = '". addslashes($this->siret)   ."'";
			$sql .= ",ape     = '". addslashes($this->ape)     ."'";
			$sql .= ",idprof4 = '". addslashes($this->idprof4) ."'";

			$sql .= ",tva_assuj = ".($this->tva_assuj>=0?"'".$this->tva_assuj."'":"null");
			$sql .= ",tva_intra = '" . addslashes($this->tva_intra) ."'";

			$sql .= ",capital = '" .   addslashes($this->capital) ."'";

			$sql .= ",prefix_comm = ".($this->prefix_comm?"'".addslashes($this->prefix_comm)."'":"null");

			$sql .= ",fk_effectif = ".($this->effectif_id?"'".$this->effectif_id."'":"null");

			$sql .= ",fk_typent = ".($this->typent_id?"'".$this->typent_id."'":"0");

			$sql .= ",fk_forme_juridique = ".($this->forme_juridique_code?"'".$this->forme_juridique_code."'":"null");

			$sql .= ",client = " . $this->client;
			$sql .= ",fournisseur = " . $this->fournisseur;
            $sql .= ",gencod = ".($this->gencod?"'".$this->gencod."'":"null");


			if ($allowmodcodeclient)
			{
				//$this->check_codeclient();

				$sql .= ", code_client = ".($this->code_client?"'".addslashes($this->code_client)."'":"null");

				// Attention get_codecompta peut modifier le code suivant le module utilise
				$this->get_codecompta('customer');

				$sql .= ", code_compta = ".($this->code_compta?"'".addslashes($this->code_compta)."'":"null");
			}

			if ($allowmodcodefournisseur)
			{
				//$this->check_codefournisseur();

				$sql .= ", code_fournisseur = ".($this->code_fournisseur?"'".addslashes($this->code_fournisseur)."'":"null");

				// Attention get_codecompta peut modifier le code suivant le module utilise
				$this->get_codecompta('supplier');

				$sql .= ", code_compta_fournisseur = ".($this->code_compta_fournisseur?"'".addslashes($this->code_compta_fournisseur)."'":"null");
			}
			$sql .= ", fk_user_modif = ".($user->id > 0 ? "'".$user->id."'":"null");
			$sql .= " WHERE rowid = '" . $id ."'";


			dol_syslog("Societe::update sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				// Si le fournisseur est classe on l'ajoute
				$this->AddFournisseurInCategory($this->fournisseur_categorie);

				if ($call_trigger)
				{
					// Appel des triggers
					include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
					$interface=new Interfaces($this->db);
					$result=$interface->run_triggers('COMPANY_MODIFY',$this,$user,$langs,$conf);
					if ($result < 0) { $error++; $this->errors=$interface->errors; }
					// Fin appel triggers
				}

				$result = 1;
			}
			else
			{
				if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
				{
					// Doublon
					$this->error = $langs->trans("ErrorDuplicateField");
					$result =  -1;
				}
				else
				{

					$this->error = $langs->trans("Error sql=".$sql);
					dol_syslog("Societe::Update echec sql=".$sql);
					$result =  -2;
				}
			}
		}

		return $result;

	}

	/**
	 *    \brief      Load a third party from database into memory
	 *    \param      socid       Id third party to load
	 *    \param      user        User object
	 *    \return     int         >0 si ok, <0 si ko
	 */
	function fetch($socid, $user=0)
	{
		global $langs;
		global $conf;

		// Init data for telephonie module
		if ($conf->telephonie->enabled && $user && $user->id)
		{
			/* Lecture des permissions */
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

		$sql = 'SELECT s.rowid, s.nom, s.address,'.$this->db->pdate('s.datec').' as dc, s.prefix_comm';
		$sql .= ', s.price_level';
		$sql .= ','. $this->db->pdate('s.tms').' as date_update';
		$sql .= ', s.tel, s.fax, s.email, s.url, s.cp, s.ville, s.note, s.client, s.fournisseur';
		$sql .= ', s.siren, s.siret, s.ape, s.idprof4';
		$sql .= ', s.capital, s.tva_intra';
		$sql .= ', s.fk_typent as typent_id';
		$sql .= ', s.fk_effectif as effectif_id';
		$sql .= ', s.fk_forme_juridique as forme_juridique_code';
		$sql .= ', s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur, s.parent, s.gencod';
		$sql .= ', s.fk_departement, s.fk_pays, s.fk_stcomm, s.remise_client, s.mode_reglement, s.cond_reglement, s.tva_assuj';
		$sql .= ', s.fk_prospectlevel';
		$sql .= ', fj.libelle as forme_juridique';
		$sql .= ', e.libelle as effectif';
		$sql .= ', p.code as pays_code, p.libelle as pays';
		$sql .= ', d.code_departement as departement_code, d.nom as departement';
		$sql .= ', st.libelle as stcomm';
		$sql .= ', te.code as typent_code';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_effectif as e ON s.fk_effectif = e.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_pays as p ON s.fk_pays = p.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_stcomm as st ON s.fk_stcomm = st.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_forme_juridique as fj ON s.fk_forme_juridique = fj.code';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as d ON s.fk_departement = d.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_typent as te ON s.fk_typent = te.id';
		$sql .= ' WHERE s.rowid = '.$socid;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->ref = $obj->rowid;

				$this->date_update = $obj->date_update;

				$this->nom = $obj->nom;
				$this->adresse = $obj->address;
				$this->cp = $obj->cp;
				$this->ville = $obj->ville;
				$this->adresse_full = $obj->address . "\n". $obj->cp . ' '. $obj->ville;

				$this->pays_id = $obj->fk_pays;
				$this->pays_code = $obj->fk_pays?$obj->pays_code:'';
				$this->pays = $obj->fk_pays?($langs->trans('Country'.$obj->pays_code)!='Country'.$obj->pays_code?$langs->trans('Country'.$obj->pays_code):$obj->pays):'';

				$this->departement_id = $obj->fk_departement;
				$this->departement= $obj->fk_departement?$obj->departement:'';

				$transcode=$langs->trans('StatusProspect'.$obj->fk_stcomm);
				$libelle=($transcode!='StatusProspect'.$obj->fk_stcomm?$transcode:$obj->stcomm);
				$this->stcomm_id = $obj->fk_stcomm;     // id statut commercial
				$this->statut_commercial = $libelle;    // libelle statut commercial

				$this->email = $obj->email;
				$this->url = $obj->url;
				$this->tel = $obj->tel;
				$this->fax = $obj->fax;

				$this->parent    = $obj->parent;

				$this->siren     = $obj->siren;
				$this->siret     = $obj->siret;
				$this->ape       = $obj->ape;
				$this->idprof4   = $obj->idprof4;

				$this->capital   = $obj->capital;

				$this->code_client = $obj->code_client;
				$this->code_fournisseur = $obj->code_fournisseur;

				$this->code_compta = $obj->code_compta;
				$this->code_compta_fournisseur = $obj->code_compta_fournisseur;

				$this->gencod = $obj->gencod;

				$this->tva_assuj      = $obj->tva_assuj;
				$this->tva_intra      = $obj->tva_intra;
				$this->tva_intra_code = substr($obj->tva_intra,0,2);
				$this->tva_intra_num  = substr($obj->tva_intra,2);

				$this->typent_id      = $obj->typent_id;
				$this->typent_code    = $obj->typent_code;

				$this->effectif_id    = $obj->effectif_id;
				$this->effectif       = $obj->effectif_id?$obj->effectif:'';

				$this->forme_juridique_code= $obj->forme_juridique_code;
				$this->forme_juridique     = $obj->forme_juridique_code?$obj->forme_juridique:'';

				$this->fk_prospectlevel = $obj->fk_prospectlevel;

				$this->prefix_comm = $obj->prefix_comm;

				$this->remise_client = $obj->remise_client;
				$this->mode_reglement = $obj->mode_reglement;
				$this->cond_reglement = $obj->cond_reglement;
				$this->client      = $obj->client;
				$this->fournisseur = $obj->fournisseur;

				$this->note = $obj->note;
				// multiprix
				$this->price_level = $obj->price_level;

				$result = 1;
			}
			else
			{
				dol_syslog('Erreur Societe::Fetch aucune societe avec id='.$this->id.' - '.$sql);
				$this->error='Erreur Societe::Fetch aucune societe avec id='.$this->id.' - '.$sql;
				$result = -2;
			}

			$this->db->free($resql);
		}
		else
		{
			dol_syslog('Erreur Societe::Fetch echec sql='.$sql);
			dol_syslog('Erreur Societe::Fetch '.$this->db->error());
			$this->error=$this->db->error();
			$result = -3;
		}

		return $result;
	}

	/**
	 *
	 * Lit une adresse de livraison
	 *
	 */
	function fetch_adresse_livraison($id)
	{
		global $conf,$langs;

		$sql = "SELECT l.rowid, l.label, l.fk_societe, l.nom, l.address, l.cp";
		$sql .= ", ".$this->db->pdate("l.tms")."as dm, ".$this->db->pdate("l.datec")."as dc";
		$sql .= ", l.ville, l.fk_pays, l.note, l.tel, l.fax";
		$sql .= ", p.libelle as pays, p.code as pays_code, s.nom as socname";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_adresse_livraison as l";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p ON l.fk_pays = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON l.fk_societe = s.rowid";
		$sql .= " WHERE l.rowid = ".$id;

		$result = $this->db->query($sql) ;

		if ( $result )
		{
			$obj = $this->db->fetch_object($result);

			$this->id             = $obj->rowid;
			$this->datec          = $obj->dc;
			$this->datem          = $obj->dm;
			$this->label          = $obj->label;
			$this->socid          = $obj->fk_societe;
			$this->societe        = $obj->socname;
			$this->nom            = $obj->nom;
			$this->address        = $obj->address;
			$this->adresse        = $obj->address; //Todo: uniformiser le nom des champs
			$this->cp             = $obj->cp;
			$this->ville          = $obj->ville;
			$this->pays_id        = $obj->fk_pays;
			$this->pays_code      = $obj->fk_pays?$obj->pays_code:'';
			$this->pays           = $obj->fk_pays?($langs->trans('Country'.$obj->pays_code)!='Country'.$obj->pays_code?$langs->trans('Country'.$obj->pays_code):$obj->pays):'';
			$this->tel            = $obj->tel;
			$this->fax            = $obj->fax;
			$this->note           = $obj->note;


			$this->db->free($result);

			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *    \brief      Suppression d'une societe de la base avec ses d�pendances (contacts, rib...)
	 *    \param      id      id de la societe � supprimer
	 */
	function delete($id)
	{
		global $user,$langs,$conf;

		dol_syslog("Societe::Delete", LOG_DEBUG);
		$sqr = 0;

		// Check if third party can be deleted
		$nbpropal=0;
		$sql = "SELECT COUNT(*) as nb from ".MAIN_DB_PREFIX."propal";
		$sql.= " WHERE fk_soc = " . $id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj=$this->db->fetch_object($resql);
			$nbpropal=$obj->nb;
			if ($nbpropal > 0)
			{
				$this->error="ErrorRecordHasChildren";
				return -1;
			}
		}
		else
		{
			$this->error .= $this->db->lasterror();
			dol_syslog("Societe::Delete erreur -1 ".$this->error, LOG_ERR);
			return -1;
		}



		if ($this->db->begin())
		{
			// Added by Matelli (see http://matelli.fr/showcases/patchs-dolibarr/fix-third-party-deleting.html)
			// Removing every "categorie" link with this company
			require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");

			$static_cat = new Categorie($this->db);
			$toute_categs = array();

			// Fill $toute_categs array with an array of (type => array of ("Categorie" instance))
			if ($this->client || $this->prospect)
			{
				$toute_categs ['societe'] = $static_cat->containing($this->id,'societe',2);
			}
			if ($this->fournisseur)
			{
				$toute_categs ['fournisseur'] = $static_cat->containing($this->id,'fournisseur',1);
			}

			// Remove each "Categorie"
			foreach ($toute_categs as $type => $categs_type)
			{
				foreach ($categs_type as $cat)
				{
					$cat->del_type($this, $type);
				}
			}

			// Remove contacts
			$sql = "DELETE from ".MAIN_DB_PREFIX."socpeople";
			$sql.= " WHERE fk_soc = " . $id;
			dol_syslog("Societe::Delete sql=".$sql, LOG_DEBUG);
			if ($this->db->query($sql))
			{
				$sqr++;
			}
			else
			{
				$this->error .= $this->db->lasterror();
				dol_syslog("Societe::Delete erreur -1 ".$this->error, LOG_ERR);
			}

			// Update link in member table
			$sql = "UPDATE ".MAIN_DB_PREFIX."adherent";
			$sql.= " SET fk_soc = NULL where fk_soc = " . $id;
			dol_syslog("Societe::Delete sql=".$sql, LOG_DEBUG);
			if ($this->db->query($sql))
			{
				$sqr++;
			}
			else
			{
				$this->error .= $this->db->lasterror();
				dol_syslog("Societe::Delete erreur -1 ".$this->error, LOG_ERR);
			}

			// Remove ban
			$sql = "DELETE from ".MAIN_DB_PREFIX."societe_rib";
			$sql.= " WHERE fk_soc = " . $id;
			dol_syslog("Societe::Delete sql=".$sql, LOG_DEBUG);
			if ($this->db->query($sql))
			{
				$sqr++;
			}
			else
			{
				$this->error = $this->db->lasterror();
				dol_syslog("Societe::Delete erreur -2 ".$this->error, LOG_ERR);
			}

			// Remove third party
			$sql = "DELETE from ".MAIN_DB_PREFIX."societe";
			$sql.= " WHERE rowid = " . $id;
			dol_syslog("Societe::Delete sql=".$sql, LOG_DEBUG);
			if ($this->db->query($sql))
			{
				$sqr++;
			}
			else
			{
				$this->error = $this->db->lasterror();
				dol_syslog("Societe::Delete erreur -3 ".$this->error, LOG_ERR);
			}

			if ($sqr == 4)
			{
				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('COMPANY_DELETE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers

				$this->db->commit();

				// Suppression du repertoire document
				$docdir = $conf->societe->dir_output . "/" . $id;
				if (file_exists ($docdir))
				{
					dol_delete_dir_recursive($docdir);
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
	 *    \brief     Retournes les factures impay�es de la soci�t�
	 *    \return    array   tableau des id de factures impay�es
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
	 *    \brief      Attribut le prefix de la soci�t� en base
	 *
	 */
	function attribute_prefix()
	{
		$sql = "SELECT nom FROM ".MAIN_DB_PREFIX."societe WHERE rowid = '".$this->id."'";
		$resql=$this->db->query( $sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj=$this->db->fetch_object($resql);
				$nom = preg_replace("/[[:punct:]]/","",$obj->nom);
				$this->db->free();

				$prefix = $this->genprefix($nom,4);

				$sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."societe WHERE prefix_comm = '$prefix'";
				$resql=$this->db->query($sql);
				if ($resql)
				{
					$obj=$this->db->fetch_object($resql);
					$this->db->free($resql);
					if (! $obj->nb)
					{
						$sql = "UPDATE ".MAIN_DB_PREFIX."societe set prefix_comm='".$prefix."' WHERE rowid='".$this->id."'";

						if ( $this->db->query( $sql) )
						{

						}
						else
						{
							dol_print_error($this->db);
						}
					}
				}
				else
				{
					dol_print_error($this->db);
				}
			}
		}
		else
		{
			dol_print_error($this->db);
		}
		return $prefix;
	}

	/**
	 *    \brief      G�n�re le pr�fix de la soci�t�
	 *    \param      nom         nom de la soci�t�
	 *    \param      taille      taille du prefix � retourner
	 *    \param      mot         l'indice du mot � utiliser
	 */
	function genprefix($nom, $taille=4, $mot=0)
	{
		$retour = "";
		$tab = explode(" ",$nom);

		if ($mot < count($tab))
		{
			$prefix = strtoupper(substr($tab[$mot],0,$taille));
			// On v�rifie que ce prefix n'a pas d�j� �t� pris ...
			$sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."societe WHERE prefix_comm = '$prefix'";
			$resql=$this->db->query( $sql);
			if ($resql)
			{
				$obj=$this->db->fetch_object($resql);
				if ($obj->nb)
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
	 *    	\brief     	Define third party as a customer
	 *		\return		int		<0 if KO, >0 if OK
	 */
	function set_as_client()
	{
		if ($this->id)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe";
			$sql.= " SET client = 1";
			$sql.= " WHERE rowid = " . $this->id;

			$resql=$this->db->query($sql);
			if ($resql) return 1;
			else return -1;
		}
		return 0;
	}

	/**
	 *    	\brief      D�finit la soci�t� comme un client
	 *    	\param      remise		Valeur en % de la remise
	 *    	\param      note		Note/Motif de modification de la remise
	 *    	\param      user		Utilisateur qui d�finie la remise
	 *		\return		int			<0 si ko, >0 si ok
	 */
	function set_remise_client($remise, $note, $user)
	{
		global $langs;

		// Nettoyage parametres
		$note=trim($note);
		if (! $note)
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Note"));
			return -2;
		}

		dol_syslog("Societe::set_remise_client ".$remise.", ".$note.", ".$user->id);

		if ($this->id)
		{
			$this->db->begin();

			// Positionne remise courante
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe ";
			$sql.= " SET remise_client = '".$remise."'";
			$sql.= " WHERE rowid = " . $this->id .";";
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->db->rollback();
				$this->error=$this->db->error();
				return -1;
			}

			// Ecrit trace dans historique des remises
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_remise ";
			$sql.= " (datec, fk_soc, remise_client, note, fk_user_author)";
			$sql.= " VALUES (".$this->db->idate(mktime()).", ".$this->id.", '".$remise."',";
			$sql.= " '".addslashes($note)."',";
			$sql.= " ".$user->id;
			$sql.= ")";

			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->db->rollback();
				$this->error=$this->db->error();
				return -1;
			}

			$this->db->commit();
			return 1;
		}
	}

	/**
	 *    	\brief      Ajoute une remise fixe pour la societe
	 *    	\param      remise      Montant de la remise
	 *    	\param      user        Utilisateur qui accorde la remise
	 *    	\param      desc		Motif de l'avoir
	 *		\return		int			<0 si ko, id de la ligne de remise si ok
	 */
	function set_remise_except($remise, $user, $desc, $tva_tx=0)
	{
		global $langs;

		// Nettoyage des parametres
		$remise = price2num($remise);
		$desc = trim($desc);

		// Check parameters
		if (! $remise > 0)
		{
			$this->error=$langs->trans("ErrorWrongValueForParameter","1");
			return -1;
		}
		if (! $desc)
		{
			$this->error=$langs->trans("ErrorWrongValueForParameter","3");
			return -2;
		}

		if ($this->id)
		{
			require_once(DOL_DOCUMENT_ROOT.'/discount.class.php');

			$discount = new DiscountAbsolute($this->db);
			$discount->fk_soc=$this->id;
			$discount->amount_ht=price2num($remise,'MT');
			$discount->amount_tva=price2num($remise*$tva_tx/100,'MT');
			$discount->amount_ttc=price2num($discount->amount_ht+$discount->amount_tva,'MT');
			$discount->tva_tx=price2num($tva_tx,'MT');
			$discount->desc=$desc;
			$result=$discount->create($user);
			if ($result > 0)
			{
				return $result;
			}
			else
			{
				$this->error=$discount->error;
				return -3;
			}
		}
		else return 0;
	}

	/**
	 *    	\brief      Supprime un avoir (a condition que non affecte a une facture)
	 *    	\param      id			Id de l'avoir a supprimer
	 *		\return		int			<0 si ko, id de l'avoir si ok
	 */
	function del_remise_except($id)
	{
		if ($this->id)
		{
			require_once(DOL_DOCUMENT_ROOT.'/discount.class.php');

			$discount = new DiscountAbsolute($this->db);
			$result=$discount->fetch($id);
			$result=$discount->delete();
			return $result;
		}
		else return 0;
	}


	/**
	 *    	\brief      Renvoie montant TTC des reductions/avoirs en cours disponibles de la societe
	 *		\param		user		Filtre sur un user auteur des remises
	 * 		\param		filter		Filtre autre
	 * 		\param		maxvalue	Filter on max value for discount
	 *		\return		int			<0 if KO, Credit note amount otherwise
	 */
	function getAvailableDiscounts($user='',$filter='',$maxvalue=0)
	{
		require_once(DOL_DOCUMENT_ROOT.'/discount.class.php');

		$discountstatic=new DiscountAbsolute($this->db);
		$result=$discountstatic->getAvailableDiscounts($this,$user,$filter,$maxvalue);
		if ($result >= 0)
		{
			return $result;
		}
		else
		{
			$this->error=$discountstatic->error;
			return -1;
		}
	}


	/**
	 * Enter description here...
	 *
	 * @param unknown_type $price_level
	 * @param unknown_type $user
	 */
	function set_price_level($price_level, $user)
	{
		if ($this->id)
		{
			$sql  = "UPDATE ".MAIN_DB_PREFIX."societe ";
			$sql .= " SET price_level = '".$price_level."'";
			$sql .= " WHERE rowid = " . $this->id .";";

			$this->db->query($sql);

			$sql  = "INSERT INTO ".MAIN_DB_PREFIX."societe_prices ";
			$sql .= " ( datec, fk_soc, price_level, fk_user_author )";
			$sql .= " VALUES (".$this->db->idate(mktime()).",".$this->id.",'".$price_level."',".$user->id.")";

			if (! $this->db->query($sql) )
	  {
	  	dol_print_error($this->db);
	  }

		}
	}

	/**
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
	  	dol_syslog("Societe::add_commercial Erreur");
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
	  	dol_syslog("Societe::del_commercial Erreur");
	  }

		}
	}


	/**
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		Inclut le picto dans le lien
	 *		\param		option			Sur quoi pointe le lien
	 *		\param		maxlen			Longueur max libelle
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='customer',$maxlen=0)
	{
		global $langs;

		$result='';
		$lien=$lienfin='';

		if ($option == 'customer')
		{
			if ($this->client == 1)
			{
				$lien = '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$this->id.'">';
				$lienfin='</a>';
			}
			elseif($this->client == 2)
			{
				$lien= '<a href="'.DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$this->id.'">';
				$lienfin='</a>';
			}
		}
		if ($option == 'supplier')
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$this->id.'">';
			$lienfin='</a>';
		}
		if ($option == 'compta')
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$this->id.'">';
			$lienfin='</a>';
		}

		if (empty($lien))
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$this->id.'">';
			$lienfin='</a>';
		}
		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowCompany").': '.$this->nom,'company').$lienfin.' ');
		$result.=$lien.($maxlen?dol_trunc($this->nom,$maxlen):$this->nom).$lienfin;

		return $result;
	}


	/**
	 *    \brief      Renvoie le nom d'une societe a partir d'un id
	 *    \param      id      	id company we search for name
	 *	\return		string		Third party name
	 */
	function get_nom($id)
	{

		$sql = "SELECT nom FROM ".MAIN_DB_PREFIX."societe WHERE rowid='".$id."';";

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
			dol_print_error($this->db);
		}

	}


	/**
	 *    \brief      Renvoie la liste des contacts emails existant pour la soci�t�
	 *    \return     array       tableau des contacts emails
	 */
	function thirdparty_and_contact_email_array()
	{
		global $langs;

		$contact_email = $this->contact_email_array();
		if ($this->email)
		{
			// TODO: Tester si email non deja present dans tableau contact
			$contact_email[-1]=$langs->trans("ThirdParty").': '.dol_trunc($this->nom,16)." &lt;".$this->email."&gt;";
		}
		return $contact_email;
	}

	/**
	 *    \brief      Renvoie la liste des contacts emails existant pour la soci�t�
	 *    \return     array       tableau des contacts emails
	 */
	function contact_email_array()
	{
		$contact_email = array();

		$sql = "SELECT rowid, email, name, firstname";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople";
		$sql.= " WHERE fk_soc = '".$this->id."'";
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$nump = $this->db->num_rows($resql);
			if ($nump)
			{
				$i = 0;
				while ($i < $nump)
				{
					$obj = $this->db->fetch_object($resql);
					$contact_email[$obj->rowid] = trim($obj->firstname." ".$obj->name)." &lt;".$obj->email."&gt;";
					$i++;
				}
			}
		}
		else
		{
			dol_print_error($this->db);
		}
		return $contact_email;
	}


	/**
	 *    \brief      Renvoie la liste des contacts de cette soci�t�
	 *    \return     array      tableau des contacts
	 */
	function contact_array()
	{
		$contacts = array();

		$sql = "SELECT rowid, name, firstname FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = '".$this->id."'";
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$nump = $this->db->num_rows($resql);
			if ($nump)
			{
				$i = 0;
				while ($i < $nump)
				{
					$obj = $this->db->fetch_object($resql);
					$contacts[$obj->rowid] = $obj->firstname." ".$obj->name;
					$i++;
				}
			}
		}
		else
		{
			dol_print_error($this->db);
		}
		return $contacts;
	}

	/**
	 *    \brief      Renvoie l'email d'un contact depuis son id
	 *    \param      rowid       id du contact
	 *    \return     string      email du contact
	 */
	function contact_get_email($rowid)
	{

		$sql = "SELECT rowid, email, name, firstname FROM ".MAIN_DB_PREFIX."socpeople WHERE rowid = '".$rowid."'";

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
			dol_print_error($this->db);
		}

	}


	/**
	 *    \brief      Affiche le rib
	 */
	function display_rib()
	{
		global $langs;

		require_once DOL_DOCUMENT_ROOT . "/companybankaccount.class.php";

		$bac = new CompanyBankAccount($this->db);
		$bac->socid = $this->id;
		$bac->fetch($this->id);

		if ($bac->code_banque || $bac->code_guichet || $bac->number || $bac->cle_rib)
		{
			$rib = $bac->code_banque." ".$bac->code_guichet." ".$bac->number;
			$rib.=($bac->cle_rib?" (".$bac->cle_rib.")":"");
		}
		else
		{
			$rib=$langs->trans("NoRIB");
		}
		return $rib;
	}

	/**
	 * Load this->bank_account attribut
	 */
	function load_ban()
	{
		require_once DOL_DOCUMENT_ROOT . "/companybankaccount.class.php";

		$bac = new CompanyBankAccount($this->db, $this->id);
		$bac->fetch($this->id);

		$this->bank_account = $bac;
		return 1;
	}


	function verif_rib()
	{
		$this->load_ban();
		return $this->bank_account->verif();
	}

	/**
	 *    \brief      Attribut un code client � partir du module de controle des codes.
	 *    \return     code_client		Code client automatique
	 */
	function get_codeclient($objsoc=0,$type=0)
	{
		global $conf;
		if ($conf->global->SOCIETE_CODECLIENT_ADDON)
		{
			require_once DOL_DOCUMENT_ROOT.'/includes/modules/societe/'.$conf->global->SOCIETE_CODECLIENT_ADDON.'.php';
			$var = $conf->global->SOCIETE_CODECLIENT_ADDON;
			$mod = new $var;

			$this->code_client = $mod->getNextValue($objsoc,$type);
			$this->prefixCustomerIsRequired = $mod->prefixIsRequired;

			dol_syslog("Societe::get_codeclient code_client=".$this->code_client." module=".$var);
		}
	}

	/**
	 *    \brief      Attribut un code fournisseur � partir du module de controle des codes.
	 *    \return     code_fournisseur		Code fournisseur automatique
	 */
	function get_codefournisseur($objsoc=0,$type=1)
	{
		global $conf;
		if ($conf->global->SOCIETE_CODEFOURNISSEUR_ADDON)
		{
			require_once DOL_DOCUMENT_ROOT.'/includes/modules/societe/'.$conf->global->SOCIETE_CODEFOURNISSEUR_ADDON.'.php';
			$var = $conf->global->SOCIETE_CODEFOURNISSEUR_ADDON;
			$mod = new $var;

			$this->code_fournisseur = $mod->getNextValue($objsoc,$type);
			$this->prefixSupplierIsRequired = $mod->prefixIsRequired;

			dol_syslog("Societe::get_codefournisseur code_fournisseur=".$this->code_fournisseur." module=".$var);
		}
	}

	/**
	 *    \brief      Verifie si un code client est modifiable en fonction des parametres
	 *                du module de controle des codes.
	 *    \return     int		0=Non, 1=Oui
	 */
	function codeclient_modifiable()
	{
		global $conf;
		if ($conf->global->SOCIETE_CODECLIENT_ADDON)
		{
			require_once DOL_DOCUMENT_ROOT.'/includes/modules/societe/'.$conf->global->SOCIETE_CODECLIENT_ADDON.'.php';

			$var = $conf->global->SOCIETE_CODECLIENT_ADDON;

			$mod = new $var;

			dol_syslog("Societe::codeclient_modifiable code_client=".$this->code_client." module=".$var);
			if ($mod->code_modifiable_null && ! $this->code_client) return 1;
			if ($mod->code_modifiable_invalide && $this->check_codeclient() < 0) return 1;
			if ($mod->code_modifiable) return 1;	// A mettre en dernier
			return 0;
		}
		else
		{
			return 0;
		}
	}


	/**
	 *    \brief      Verifie si un code fournisseur est modifiable dans configuration du module de controle des codes
	 *    \return     int		0=Non, 1=Oui
	 */
	function codefournisseur_modifiable()
	{
		global $conf;
		if ($conf->global->SOCIETE_CODEFOURNISSEUR_ADDON)
		{
			require_once DOL_DOCUMENT_ROOT.'/includes/modules/societe/'.$conf->global->SOCIETE_CODEFOURNISSEUR_ADDON.'.php';

			$var = $conf->global->SOCIETE_CODEFOURNISSEUR_ADDON;

			$mod = new $var;

			dol_syslog("Societe::codefournisseur_modifiable code_founisseur=".$this->code_fournisseur." module=".$var);
			if ($mod->code_modifiable_null && ! $this->code_fournisseur) return 1;
			if ($mod->code_modifiable_invalide && $this->check_codefournisseur() < 0) return 1;
			if ($mod->code_modifiable) return 1;	// A mettre en dernier
			return 0;
		}
		else
		{
			return 0;
		}
	}


	/**
	 *    \brief      Verifie code client
	 *    \return     int		<0 si KO, 0 si OK, peut modifier le code client suivant le module utilise
	 */
	function check_codeclient()
	{
		global $conf;
		if ($conf->global->SOCIETE_CODECLIENT_ADDON)
		{
			require_once DOL_DOCUMENT_ROOT.'/includes/modules/societe/'.$conf->global->SOCIETE_CODECLIENT_ADDON.'.php';

			$var = $conf->global->SOCIETE_CODECLIENT_ADDON;

			$mod = new $var;

			dol_syslog("Societe::check_codeclient code_client=".$this->code_client." module=".$var);
			$result = $mod->verif($this->db, $this->code_client, $this, 0);
			return $result;
		}
		else
		{
			return 0;
		}
	}

	/**
	 *    \brief      Verifie code fournisseur
	 *    \return     int		<0 si KO, 0 si OK, peut modifier le code client suivant le module utilise
	 */
	function check_codefournisseur()
	{
		global $conf;
		if ($conf->global->SOCIETE_CODEFOURNISSEUR_ADDON)
		{
			require_once DOL_DOCUMENT_ROOT.'/includes/modules/societe/'.$conf->global->SOCIETE_CODEFOURNISSEUR_ADDON.'.php';

			$var = $conf->global->SOCIETE_CODEFOURNISSEUR_ADDON;

			$mod = new $var;

			dol_syslog("Societe::check_codefournisseur code_fournisseur=".$this->code_fournisseur." module=".$var);
			$result = $mod->verif($this->db, $this->code_fournisseur, $this ,1);
			return $result;
		}
		else
		{
			return 0;
		}
	}

	/**
	 *    	\brief  	Renvoie un code compta, suivant le module de code compta.
	 *            		Peut �tre identique � celui saisit ou g�n�r� automatiquement.
	 *            		A ce jour seule la g�n�ration automatique est impl�ment�e
	 *    	\param      type			Type de tiers ('customer' ou 'supplier')
	 *		\return		string			Code compta si ok, 0 si aucun, <0 si ko
	 */
	function get_codecompta($type)
	{
		global $conf;

		if ($conf->global->SOCIETE_CODECOMPTA_ADDON)
		{
			require_once DOL_DOCUMENT_ROOT.'/includes/modules/societe/'.$conf->global->SOCIETE_CODECOMPTA_ADDON.'.php';

			$var = $conf->global->SOCIETE_CODECOMPTA_ADDON;

			$mod = new $var;

			// Defini code compta dans $mod->code
			$result = $mod->get_code($this->db, $this, $type);

			if ($type == 'customer') $this->code_compta = $mod->code;
			if ($type == 'supplier') $this->code_compta_fournisseur = $mod->code;

			return $result;
		}
		else
		{
			if ($type == 'customer') $this->code_compta = '';
			if ($type == 'supplier') $this->code_compta_fournisseur = '';

			return 0;
		}
	}

	/**
	 *    \brief      D�fini la soci�t� m�re pour les filiales
	 *    \param      id      id compagnie m�re � positionner
	 *    \return     int     <0 si ko, >0 si ok
	 */
	function set_parent($id)
	{
		if ($this->id)
		{
			$sql  = "UPDATE ".MAIN_DB_PREFIX."societe ";
			$sql .= " SET parent = ".$id;
			$sql .= " WHERE rowid = " . $this->id .";";

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
	 *    \brief      Supprime la soci�t� m�re
	 *    \param      id      id compagnie m�re � effacer
	 *    \return     int     <0 si ko, >0 si ok
	 */
	function remove_parent($id)
	{
		if ($this->id)
		{
			$sql  = "UPDATE ".MAIN_DB_PREFIX."societe ";
			$sql .= " SET parent = null";
			$sql .= " WHERE rowid = " . $this->id .";";

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
	 *      \brief      Verifie la validite d'un identifiant professionnel en
	 *                  fonction du pays de la societe (siren, siret, ...)
	 *      \param      idprof          1,2,3,4 (Exemple: 1=siren,2=siret,3=naf,4=rcs/rm)
	 *      \param      soc             Objet societe
	 *      \return     int             <0 si ko, >0 si ok
	 */
	function id_prof_check($idprof,$soc)
	{
		$ok=1;

		// Verifie SIREN si pays FR
		if ($idprof == 1 && $soc->pays_code == 'FR')
		{
			$chaine=trim($this->siren);
			$chaine=eregi_replace(' ','',$chaine);

			if (strlen($chaine) != 9) return -1;

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

			if (substr($sum, -1) != 0) return -1;
		}

		// Verifie SIRET si pays FR
		if ($idprof == 2 && $soc->pays_code == 'FR')
		{
			$chaine=trim($this->siret);
			$chaine=eregi_replace(' ','',$chaine);

			if (strlen($chaine) != 14) return -1;
		}

		return $ok;
	}

	/**
	 *      \brief      Renvoi url de v�rification d'un identifiant professionnal
	 *      \param      idprof          1,2,3,4 (Exemple: 1=siren,2=siret,3=naf,4=rcs/rm)
	 *      \param      soc             Objet societe
	 *      \return     string          url ou chaine vide si aucune url connue
	 */
	function id_prof_url($idprof,$soc)
	{
		global $langs;

		$url='';
		if ($idprof == 1 && $soc->pays_code == 'FR') $url='http://www.societe.com/cgi-bin/recherche?rncs='.$soc->siren;
		if ($idprof == 1 && $soc->pays_code == 'GB') $url='http://www.companieshouse.gov.uk/WebCHeck/findinfolink/';

		if ($url) return '<a target="_blank" href="'.$url.'">['.$langs->trans("Check").']</a>';
		return '';
	}

	/**
	 *      \brief      Indique si la soci�t� a des projets
	 *      \return     bool	   true si la soci�t� a des projets, false sinon
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


	function AddPerms($user_id, $read, $write, $perms)
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_perms";
		$sql .= " (fk_soc, fk_user, pread, pwrite, pperms) ";
		$sql .= " VALUES (".$this->id.",".$user_id.",".$read.",".$write.",".$perms.");";

		$resql=$this->db->query($sql);
		if ($resql)
		{

		}
	}

	/**
	 *       \brief     Charge les informations d'ordre info dans l'objet societe
	 *       \param     id     id de la societe a charger
	 */
	function info($id)
	{
		$sql = "SELECT s.rowid, s.nom, ".$this->db->pdate("datec")." as datec, ".$this->db->pdate("datea")." as datea,";
		$sql.= " fk_user_creat, fk_user_modif";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql.= " WHERE s.rowid = ".$id;

		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				if ($obj->fk_user_creat) {
					$cuser = new User($this->db, $obj->fk_user_creat);
					$cuser->fetch();
					$this->user_creation     = $cuser;
				}

				if ($obj->fk_user_modif) {
					$muser = new User($this->db, $obj->fk_user_modif);
					$muser->fetch();
					$this->user_modification = $muser;
				}
				$this->ref			     = $obj->nom;
				$this->date_creation     = $obj->datec;
				$this->date_modification = $obj->datea;
			}

			$this->db->free($result);

		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *       \brief     Renvoi si pays appartient � CEE
	 *       \param     boolean		true = pays dans CEE, false= pays hors CEE
	 */
	function isInEEC()
	{
		// List of all country codes that are in europe for european vat rules
		$country_code_in_EEC=array(
			'AT',	// Austria
			'BE',	// Belgium
			'BG',	// Bulgaria
			'CY',	// Cyprus
			'CZ',	// Czech republic
			'DK',	// Danemark
			'EE',	// Estonia
			'FI',	// Finland
			'FR',	// France
			'DE',	// Germany
			'GB',	// Royaume-uni
			'GR',	// Greece
			'NL',	// Holland
			'HU',	// Hungary
			'IE',	// Ireland
			'IT',	// Italy
			'LV',	// Latvia
			'LT',	// Lithuania
			'LU',	// Luxembourg
			'MO',	// Monaco
			'MT',	// Malta
			'NO',	// Norway
			'PL',	// Poland
			'PT',	// Portugal
			'RO',	// Romania
			'SK',	// Slovakia
			'SI',	// Slovenia
			'ES',	// Spain
			'SE',	// Sweden
			'CH',	// Switzerland
		);
		//print "dd".$this->pays_code;
		return in_array($this->pays_code,$country_code_in_EEC);
	}

	/**
	 *  	\brief     Charge la liste des categories fournisseurs
	 *   \return    0 in success, <> 0 in error
	 */
	function LoadSupplierCateg()
	{
		$this->SupplierCategories = array();
		$sql = "SELECT rowid, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE type = 1";

		$resql=$this->db->query($sql);
		if ($resql)
		{
	  while ($obj = $this->db->fetch_object($resql) )
	  {
	  	$this->SupplierCategories[$obj->rowid] = $obj->label;
	  }
	  return 0;
		}
		else
		{
	  return -1;
		}
	}

	/*
	 *  \brief     Charge la liste des categories fournisseurs
	 *   \return    0 in success, <> 0 in error
	 */
	function AddFournisseurInCategory($categorie_id)
	{
		if ($categorie_id > 0)
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."categorie_fournisseur (fk_categorie, fk_societe) ";
			$sql.= " VALUES ('".$categorie_id."','".$this->id."');";

			if ($resql=$this->db->query($sql))
	  return 0;
		}
		else
		{
			return 0;
		}
		return -1;
	}



	function set_status($id_status)
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_log (datel, fk_soc, fk_statut, fk_user, author, label)";
		$sql.= " VALUES ('$dateaction', $socid, $id_status,";
		$sql.= "'".$user->id."',";
		$sql.= "'".addslashes($user->login)."',";
		$sql.= "'Change statut from $oldstcomm to $stcommid'";
		$sql.= ")";
		$result = $db->query($sql);
		if ($result)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=$stcommid WHERE rowid=".$socid;
			$result = $db->query($sql);
		}
		else
		{
			$errmesg = $db->error;
		}
	}

	/**
	 *      \brief      Retourne le formulaire de saisie d'un identifiant professionnel (siren, siret, etc...)
	 *      \param      idprof          1,2,3,4 (Exemple: 1=siren,2=siret,3=naf,4=rcs/rm)
	 *      \param      htmlname        Nom de la zone input
	 * 		\param		preselected		Default value to show
	 */
	function show_input_id_prof($idprof,$htmlname,$preselected)
	{
		global $langs;

		$formlength=16;
		if ($this->pays_code == 'FR')
		{
			if ($idprof==1) $formlength=9;
			if ($idprof==2) $formlength=14;
			if ($idprof==3) $formlength=5;		// 4 chiffres et 1 lettre depuis janvier
			if ($idprof==4) $formlength=12;
		}
		$selected=$preselected;
		if (! $selected && $idprof==1) $selected=$this->siren;
		if (! $selected && $idprof==2) $selected=$this->siret;
		if (! $selected && $idprof==3) $selected=$this->ape;
		if (! $selected && $idprof==4) $selected=$this->idprof4;

		print '<input type="text" name="'.$htmlname.'" size="'.($formlength+1).'" maxlength="'.$formlength.'" value="'.$selected.'">';
	}

	/**
	 *      \brief      Cree en base un utilisateur depuis l'objet adherent
	 *      \param      member	Objet adherent source
	 * 		\param		login	Login to force
	 *      \return     int		Si erreur <0, si ok renvoie id compte cree
	 */
	function create_from_member($member,$login='')
	{
		global $conf,$user,$langs;

		$name=$member->societe;
		if (empty($name)) $name=strtolower($member->nom.' '.$member->prenom);

		// Positionne parametres
		$this->email = $member->email;
		$this->nom = $name;
		$this->code_client = -1;
		$this->code_fournisseur = -1;
		$this->adresse=$member->adresse;
		$this->cp=$member->cp;
		$this->ville=$member->ville;
		$this->pays_code=$member->pays_code;
		$this->pays_id=$member->pays_id;
		$this->tel=$member->phone;				// Prof phone

		$this->db->begin();

		// Cree et positionne $this->id
		$result=$this->create($user);
		if ($result >= 0)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."adherent";
			$sql.= " SET fk_soc=".$this->id;
			$sql.= " WHERE rowid=".$member->id;

			dol_syslog("Societe::create_from_member sql=".$sql, LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Societe::create_from_member - 1 - ".$this->error, LOG_ERR);

				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			// $this->error deja positionne
			dol_syslog("Societe::create_from_member - 2 - ".$this->error, LOG_ERR);

			$this->db->rollback();
			return $result;
		}
	}

}

?>
