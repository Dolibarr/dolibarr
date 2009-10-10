<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Destailleur Laurent  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
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
 *	\file       htdocs/contrat/contrat.class.php
 *	\ingroup    contrat
 *	\brief      Fichier de la classe des contrats
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/price.lib.php");


/**
 *	\class      Contrat
 *	\brief      Classe permettant la gestion des contrats
 */
class Contrat extends CommonObject
{
	var $db;
	var $error;
	var $element='contrat';
	var $table_element='contrat';
	var $table_element_line='contratdet';
	var $fk_element='fk_contrat';

	var $id;
	var $ref;
	var $socid;
	var $societe;		// Objet societe
	var $statut=0;		// 0=Draft,
	var $product;

	var $user_author;
	var $user_service;
	var $user_cloture;
	var $date_creation;
	var $date_validation;

	var $date_contrat;
	var $date_cloture;

	var $commercial_signature_id;
	var $commercial_suivi_id;

	var $note;
	var $note_public;

	var $fk_projet;

	var $lignes=array();


	/**
	 *    \brief      Constructeur de la classe
	 *    \param      DB          Databse handler
	 */
	function Contrat($DB)
	{
		global $langs;

		$this->db = $DB ;
		$this->product = new Product($DB);
		$this->societe = new Societe($DB);
		$this->user_service = new User($DB);
		$this->user_cloture = new User($DB);
	}

	/**
	 *      \brief      Activate a contract line
	 *      \param      user        Objet User qui avtice le contrat
	 *      \param      line_id     Id de la ligne de detail � activer
	 *      \param      date        Date d'ouverture
	 *      \param      date_end    Date fin prevue
	 * 		\param		comment		A comment typed by user
	 *      \return     int         <0 if KO, >0 if OK
	 */
	function active_line($user, $line_id, $date, $date_end='', $comment='')
	{
		global $langs,$conf;

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = 4,";
		$sql.= " date_ouverture = ".(strlen($date)!=0?"'".$this->db->idate($date)."'":"null").",";
		$sql.= " date_fin_validite = ".(strlen($date_end)!=0?"'".$this->db->idate($date_end)."'":"null").",";
		$sql.= " fk_user_ouverture = ".$user->id.",";
		$sql.= " date_cloture = null,";
		$sql.= " commentaire = '".addslashes($comment)."'";
		$sql.= " WHERE rowid = ".$line_id . " AND (statut = 0 OR statut = 3 OR statut = 5)";

		dol_syslog("Contrat::active_line sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('CONTRACT_SERVICE_ACTIVATE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Contrat::active_line error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *      \brief      Close a contract line
	 *      \param      user        Objet User qui avtice le contrat
	 *      \param      line_id     Id de la ligne de detail a activer
	 *      \param      date_end	Date fin
	 * 		\param		comment		A comment typed by user
	 *      \return     int         <0 if KO, >0 if OK
	 */
	function close_line($user, $line_id, $date_end, $comment='')
	{
		global $langs,$conf;

		// statut actif : 4

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = 5,";
		$sql.= " date_cloture = '".$this->db->idate($date_end)."',";
		$sql.= " fk_user_cloture = ".$user->id.",";
		$sql.= " commentaire = '".addslashes($comment)."'";
		$sql.= " WHERE rowid = ".$line_id . " AND statut = 4";

		$resql = $this->db->query($sql) ;
		if ($resql)
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('CONTRACT_SERVICE_CLOSE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Contrat::close_line error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *    \brief      Close all lines of a contract
	 *    \param      user      Object User making action
	 *    \param      langs     Object Lang
	 *    \param      conf      Object Conf
	 *
	 */
	function cloture($user,$langs='',$conf='')
	{
		$this->db->begin();

		// Load lines
		$this->fetch_lignes();

		$ok=true;
		foreach($this->lignes as $contratline)
		{
			// Close line not already closed
	        if ($contratline->statut != 5)
	        {
				$contratline->date_cloture=mktime();
				$contratline->fk_user_cloture=$user->id;
				$contratline->statut='5';
				$result=$contratline->update($user);
				if ($result < 0)
				{
					$ok=false;
					break;
				}
	        }
		}

		if ($this->statut == 0)
		{
			$result=$this->validate($user,$langs,$conf);
			if ($result < 0) $ok=false;
		}

        if ($ok)
        {
            $this->db->commit();
        }
        else
        {
            dol_print_error($db,'Error in cloture function');
            $this->db->rollback();
        }
	}

	/**
	 *    	\brief      Validate a contract
	 *    	\param      user      	Objet User
	 *    	\param      langs     	Environnement langue de l'utilisateur
	 *    	\param      conf      	Environnement de configuration lors de l'operation
	 * 		\return		int			<0 if KO, >0 if OK
	 */
	function validate($user,$langs,$conf)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET statut = 1";
		$sql .= " WHERE rowid = ".$this->id . " AND statut = 0";

		$resql = $this->db->query($sql) ;
		if ($resql)
		{
			$this->use_webcal=($conf->global->PHPWEBCALENDAR_CONTRACTSTATUS=='always'?1:0);

			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('CONTRACT_VALIDATE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 *    \brief      Load a contract from database
	 *    \param      id      Id of contract to load
	 *    \return     int     <0 if KO, id of contract if OK
	 */
	function fetch($id,$ref='')
	{
		$sql = "SELECT rowid, statut, ref, fk_soc, mise_en_service as datemise,";
		$sql.= " fk_user_mise_en_service, date_contrat as datecontrat,";
		$sql.= " fk_user_author,";
		$sql.= " fk_projet,";
		$sql.= " fk_commercial_signature, fk_commercial_suivi,";
		$sql.= " note, note_public";
		$sql.= " FROM ".MAIN_DB_PREFIX."contrat";
		if ($ref) $sql.= " WHERE ref='".$ref."'";
		else $sql.= " WHERE rowid=".$id;

		dol_syslog("Contrat::fetch sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql) ;
		if ($resql)
		{
			$result = $this->db->fetch_array($resql);

			if ($result)
			{
				$this->id                = $result["rowid"];
				$this->ref               = (!isset($result["ref"]) || !$result["ref"]) ? $result["rowid"] : $result["ref"];
				$this->statut            = $result["statut"];
				$this->factureid         = $result["fk_facture"];
				$this->facturedetid      = $result["fk_facturedet"];
				$this->mise_en_service   = $this->db->jdate($result["datemise"]);
				$this->date_fin_validite = $result["datefin"];
				$this->date_contrat      = $this->db->jdate($result["datecontrat"]);

				$this->user_author_id    = $result["fk_user_author"];

				$this->commercial_signature_id = $result["fk_commercial_signature"];
				$this->commercial_suivi_id = $result["fk_commercial_suivi"];

				$this->user_service->id  = $result["fk_user_mise_en_service"];
				$this->user_cloture->id  = $result["fk_user_cloture"];

				$this->note              = $result["note"];
				$this->note_public       = $result["note_public"];

				$this->fk_projet         = $result["fk_projet"];

				$this->socid             = $result["fk_soc"];
				$this->fk_soc            = $result["fk_soc"];
				$this->societe->fetch($result["fk_soc"]);	// TODO A virer car la societe doit etre charge par appel de fetch_client()

				$this->db->free($resql);

				return $this->id;
			}
			else
			{
				dol_syslog("Contrat::Fetch Erreur contrat non trouve");
				$this->error="Contrat non trouve";
				return -2;
			}
		}
		else
		{
			dol_syslog("Contrat::Fetch Erreur lecture contrat");
			$this->error=$this->db->error();
			return -1;
		}

	}

	/**
	 *      \brief      Load lignes array
	 */
	function fetch_lignes()
	{
		$this->nbofserviceswait=0;
		$this->nbofservicesopened=0;
		$this->nbofservicesexpired=0;
		$this->nbofservicesclosed=0;

		$now=gmmktime();

		// Selectionne les lignes contrats liees a un produit
		$sql = "SELECT p.label, p.description as product_desc, p.ref,";
		$sql.= " d.rowid, d.fk_contrat, d.statut, d.description, d.price_ht, d.tva_tx, d.qty, d.remise_percent, d.subprice,";
		$sql.= " d.total_ht,";
		$sql.= " d.total_tva,";
		$sql.= " d.total_ttc,";
		$sql.= " d.info_bits, d.fk_product,";
		$sql.= " d.date_ouverture_prevue, d.date_ouverture,";
		$sql.= " d.date_fin_validite, d.date_cloture,";
		$sql.= " d.fk_user_author,";
		$sql.= " d.fk_user_ouverture,";
		$sql.= " d.fk_user_cloture";
		$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as d, ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE d.fk_contrat = ".$this->id ." AND d.fk_product = p.rowid";
		$sql.= " ORDER by d.rowid ASC";

		dol_syslog("Contrat::fetch_lignes sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;

			while ($i < $num)
			{
				$objp                  = $this->db->fetch_object($result);

				$ligne                 = new ContratLigne($this->db);
				$ligne->id             = $objp->rowid;
				$ligne->ref            = $objp->rowid;
				$ligne->fk_contrat     = $objp->fk_contrat;
				$ligne->desc           = $objp->description;  // Description ligne
				$ligne->qty            = $objp->qty;
				$ligne->tva_tx         = $objp->tva_tx;
				$ligne->subprice       = $objp->subprice;
				$ligne->statut 		   = $objp->statut;
				$ligne->remise_percent = $objp->remise_percent;
				$ligne->price_ht       = $objp->price_ht;
				$ligne->price          = $objp->price;	// For backward compatibility
				$ligne->total_ht       = $objp->total_ht;
				$ligne->total_tva      = $objp->total_tva;
				$ligne->total_ttc      = $objp->total_ttc;
				$ligne->fk_product     = $objp->fk_product;
				$ligne->info_bits      = $objp->info_bits;

				$ligne->fk_user_author   = $objp->fk_user_author;
				$ligne->fk_user_ouverture= $objp->fk_user_ouverture;
				$ligne->fk_user_cloture  = $objp->fk_user_cloture;

				$ligne->ref            = $objp->ref;
				$ligne->libelle        = $objp->label;        // Label produit
				$ligne->label          = $objp->label;        // For backward compatibility
				$ligne->product_desc   = $objp->product_desc; // Description produit

				$ligne->description    = $objp->description;

				$ligne->date_ouverture_prevue = $this->db->jdate($objp->date_ouverture_prevue);
				$ligne->date_ouverture        = $this->db->jdate($objp->date_ouverture);
				$ligne->date_fin_validite     = $this->db->jdate($objp->date_fin_validite);
				$ligne->date_cloture          = $this->db->jdate($objp->date_cloture);
				// For backward compatibility
				$ligne->date_debut_prevue = $this->db->jdate($objp->date_ouverture_prevue);
				$ligne->date_debut_reel   = $this->db->jdate($objp->date_ouverture);
				$ligne->date_fin_prevue   = $this->db->jdate($objp->date_fin_validite);
				$ligne->date_fin_reel     = $this->db->jdate($objp->date_cloture);
				$this->lignes[]        = $ligne;
				//dol_syslog("1 ".$ligne->desc);
				//dol_syslog("2 ".$ligne->product_desc);

				if ($ligne->statut == 0) $this->nbofserviceswait++;
				if ($ligne->statut == 4 && (empty($ligne->date_fin_prevue) || $ligne->date_fin_prevue >= $now)) $this->nbofservicesopened++;
				if ($ligne->statut == 4 && $ligne->date_fin_prevue < $now) $this->nbofservicesexpired++;
				if ($ligne->statut == 5) $this->nbofservicesclosed++;

				$i++;
			}
			$this->db->free($result);
		}
		else
		{
			dol_syslog("Contrat::Fetch Erreur lecture des lignes de contrats liees aux produits");
			return -3;
		}

		// Selectionne les lignes contrat liees a aucun produit
		$sql = "SELECT d.rowid, d.fk_contrat, d.statut, d.qty, d.description, d.price_ht, d.tva_tx, d.rowid, d.remise_percent, d.subprice,";
		$sql.= " d.total_ht,";
		$sql.= " d.total_tva,";
		$sql.= " d.total_ttc,";
		$sql.= " d.info_bits, d.fk_product,";
		$sql.= " d.date_ouverture_prevue, d.date_ouverture,";
		$sql.= " d.date_fin_validite, d.date_cloture,";
		$sql.= " d.fk_user_author,";
		$sql.= " d.fk_user_ouverture,";
		$sql.= " d.fk_user_cloture";
		$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as d";
		$sql.= " WHERE d.fk_contrat = ".$this->id;
		$sql.= " AND (d.fk_product IS NULL OR d.fk_product = 0)";   // fk_product = 0 gardee pour compatibilitee

		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;

			while ($i < $num)
			{
				$objp                  = $this->db->fetch_object($result);

				$ligne                 = new ContratLigne($this->db);
				$ligne->id 			   = $objp->rowid;
				$ligne->fk_contrat     = $objp->fk_contrat;
				$ligne->libelle        = $objp->description;
				$ligne->desc           = $objp->description;
				$ligne->qty            = $objp->qty;
				$ligne->statut 		   = $objp->statut;
				$ligne->ref            = $objp->ref;
				$ligne->tva_tx         = $objp->tva_tx;
				$ligne->subprice       = $objp->subprice;
				$ligne->remise_percent = $objp->remise_percent;
				$ligne->price_ht       = $objp->price_ht;
				$ligne->price          = $objp->price;	// For backward compatibility
				$ligne->total_ht       = $objp->total_ht;
				$ligne->total_tva      = $objp->total_tva;
				$ligne->total_ttc      = $objp->total_ttc;
				$ligne->fk_product     = 0;
				$ligne->info_bits      = $objp->info_bits;

				$ligne->fk_user_author   = $objp->fk_user_author;
				$ligne->fk_user_ouverture= $objp->fk_user_ouverture;
				$ligne->fk_user_cloture  = $objp->fk_user_cloture;

				$ligne->description    = $objp->description;

				$ligne->date_ouverture_prevue = $this->db->jdate($objp->date_ouverture_prevue);
				$ligne->date_ouverture        = $this->db->jdate($objp->date_ouverture);
				$ligne->date_fin_validite     = $this->db->jdate($objp->date_fin_validite);
				$ligne->date_cloture          = $this->db->jdate($objp->date_cloture);
				// For backward compatibility
				$ligne->date_debut_prevue = $this->db->jdate($objp->date_ouverture_prevue);
				$ligne->date_debut_reel   = $this->db->jdate($objp->date_ouverture);
				$ligne->date_fin_prevue   = $this->db->jdate($objp->date_fin_validite);
				$ligne->date_fin_reel     = $this->db->jdate($objp->date_cloture);

				if ($ligne->statut == 0) $this->nbofserviceswait++;
				if ($ligne->statut == 4 && (empty($ligne->date_fin_prevue) || $ligne->date_fin_prevue >= $now)) $this->nbofservicesopened++;
				if ($ligne->statut == 4 && $ligne->date_fin_prevue < $now) $this->nbofservicesexpired++;
				if ($ligne->statut == 5) $this->nbofservicesclosed++;

				$this->lignes[]        = $ligne;
				$i++;
			}

			$this->db->free($result);
		}
		else
		{
			dol_syslog("Contrat::Fetch Erreur lecture des lignes de contrat non li�es aux produits");
			$this->error=$this->db->error();
			return -2;
		}

		$this->nbofservices=sizeof($this->lignes);

		return $this->lignes;
	}

	/**
	 *      \brief      Cree un contrat vierge en base
	 *      \param      user        Utilisateur qui cree
	 *      \param      langs       Environnement langue de l'utilisateur
	 *      \param      conf        Environnement de configuration lors de l'operation
	 *      \return     int         <0 si erreur, id contrat cre sinon
	 */
	function create($user,$langs='',$conf='')
	{
		// Check parameters
		$paramsok=1;
		if ($this->commercial_signature_id <= 0)
		{
			$langs->load("commercial");
			$this->error.=$langs->trans("ErrorFieldRequired",$langs->trans("SalesRepresentativeSignature"));
			$paramsok=0;
		}
		if ($this->commercial_suivi_id <= 0)
		{
			$langs->load("commercial");
			$this->error.=($this->error?"<br>":'');
			$this->error.=$langs->trans("ErrorFieldRequired",$langs->trans("SalesRepresentativeFollowUp"));
			$paramsok=0;
		}
		if (! $paramsok) return -1;

		$this->db->begin();

		// Insert contract
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."contrat (datec, fk_soc, fk_user_author, date_contrat";
		$sql.= ", fk_commercial_signature, fk_commercial_suivi";
		$sql.= " , ref)";
		$sql.= " VALUES (".$this->db->idate(mktime()).",".$this->socid.",".$user->id;
		$sql.= ",".$this->db->idate($this->date_contrat);
		$sql.= ",".($this->commercial_signature_id>0?$this->commercial_signature_id:"NULL");
		$sql.= ",".($this->commercial_suivi_id>0?$this->commercial_suivi_id:"NULL");
		$sql .= ", " . (strlen($this->ref)<=0 ? "null" : "'".$this->ref."'");
		$sql.= ")";
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$error=0;

			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."contrat");

			// Ins�re contacts commerciaux ('SALESREPSIGN','contrat')
			$result=$this->add_contact($this->commercial_signature_id,'SALESREPSIGN','internal');
			if ($result < 0) $error++;

			// Ins�re contacts commerciaux ('SALESREPFOLL','contrat')
			$result=$this->add_contact($this->commercial_suivi_id,'SALESREPFOLL','internal');
			if ($result < 0) $error++;

			if (! $error)
			{
				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('CONTRACT_CREATE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers

				if (! $error)
				{
					$this->db->commit();
					return $this->id;
				}
				else
				{
					$this->error=$interface->error;
					dol_syslog("Contrat::create - 30 - ".$this->error, LOG_ERR);

					$this->db->rollback();
					return -3;
				}
			}
			else
			{
				$this->error="Failed to add contact";
				dol_syslog("Contrat::create - 20 - ".$this->error, LOG_ERR);

				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$langs->trans("UnknownError: ".$this->db->error()." - sql=".$sql);
			dol_syslog("Contrat::create - 10 - ".$this->error, LOG_ERR);

			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *      \brief      Supprime l'objet de la base
	 *      \param      user        Utilisateur qui supprime
	 *      \param      langs       Environnement langue de l'utilisateur
	 *      \param      conf        Environnement de configuration lors de l'operation
	 *      \return     int         < 0 si erreur, > 0 si ok
	 */
	function delete($user,$langs='',$conf='')
	{
		$error=0;

		$this->db->begin();

		if (! $error)
		{
			// Delete element_contact
			/*
			$sql = "DELETE ec";
			$sql.= " FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc";
			$sql.= " WHERE ec.fk_c_type_contact = tc.rowid";
			$sql.= " AND tc.element='".$this->element."'";
			$sql.= " AND ec.element_id=".$this->id;
			*/

			$sql = "SELECT ec.rowid as ecrowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc";
			$sql.= " WHERE ec.fk_c_type_contact = tc.rowid";
			$sql.= " AND tc.element='".$this->element."'";
			$sql.= " AND ec.element_id=".$this->id;

			dol_syslog("Contrat::delete element_contact sql=".$sql,LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->error=$this->db->error();
				$error++;
			}
			$numressql=$this->db->num_rows($resql);
			if (! $error && $numressql )
			{
				$tab_resql=array();
				for($i=0;$i<$numressql;$i++)
				{
					$objresql=$this->db->fetch_object($resql);
					$tab_resql[]= $objresql->ecrowid;
				}
				$this->db->free($resql);

				$sql= "DELETE FROM ".MAIN_DB_PREFIX."element_contact ";
				$sql.= " WHERE ".MAIN_DB_PREFIX."element_contact.rowid IN (".implode(",",$tab_resql).")";

				dol_syslog("Contrat::delete element_contact sql=".$sql,LOG_DEBUG);
				$resql=$this->db->query($sql);
				if (! $resql)
				{
					$this->error=$this->db->error();
					$error++;
				}
			}
		}

		if (! $error)
		{
			// Delete contratdet_log
			/*
			$sql = "DELETE cdl";
			$sql.= " FROM ".MAIN_DB_PREFIX."contratdet_log as cdl, ".MAIN_DB_PREFIX."contratdet as cd";
			$sql.= " WHERE cdl.fk_contratdet=cd.rowid AND cd.fk_contrat=".$this->id;
			*/
			$sql = "SELECT cdl.rowid as cdlrowid ";
			$sql.= " FROM ".MAIN_DB_PREFIX."contratdet_log as cdl, ".MAIN_DB_PREFIX."contratdet as cd";
			$sql.= " WHERE cdl.fk_contratdet=cd.rowid AND cd.fk_contrat=".$this->id;

			dol_syslog("Contrat::delete contratdet_log sql=".$sql, LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->error=$this->db->error();
				$error++;
			}
			$numressql=$this->db->num_rows($resql);
			if (! $error && $numressql )
			{
				$tab_resql=array();
				for($i=0;$i<$numressql;$i++)
				{
					$objresql=$this->db->fetch_object($resql);
					$tab_resql[]= $objresql->cdlrowid;
				}
				$this->db->free($resql);

				$sql= "DELETE FROM ".MAIN_DB_PREFIX."contratdet_log ";
				$sql.= " WHERE ".MAIN_DB_PREFIX."contratdet_log.rowid IN (".implode(",",$tab_resql).")";

				dol_syslog("Contrat::delete contratdet_log sql=".$sql, LOG_DEBUG);
				$resql=$this->db->query($sql);
				if (! $resql)
				{
					$this->error=$this->db->error();
					$error++;
				}
			}
		}

		if (! $error)
		{
			// Delete contratdet
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."contratdet";
			$sql.= " WHERE fk_contrat=".$this->id;

			dol_syslog("Contrat::delete contratdet sql=".$sql, LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->error=$this->db->error();
				$error++;
			}
		}

		if (! $error)
		{
			// Delete contrat
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."contrat";
			$sql.= " WHERE rowid=".$this->id;

			dol_syslog("Contrat::delete contrat sql=".$sql);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->error=$this->db->error();
				$error++;
			}
		}

		if (! $error)
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('CONTRACT_DELETE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("Contrat::delete ERROR ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *      \brief      Ajoute une ligne de contrat en base
	 *      \param      desc            	Description de la ligne
	 *      \param      pu_ht              	Prix unitaire HT
	 *      \param      qty             	Quantit�
	 *      \param      txtva           	Taux tva
	 *      \param      fk_product      	Id produit
	 *      \param      remise_percent  	Pourcentage de remise de la ligne
	 *      \param      date_start      	Date de debut pr�vue
	 *      \param      date_end        	Date de fin pr�vue
	 *		\param		price_base_type		HT ou TTC
	 * 	    \param    	pu_ttc             	Prix unitaire TTC
	 * 		\param    	info_bits			Bits de type de lignes
	 *      \return     int             	<0 si erreur, >0 si ok
	 */
	function addline($desc, $pu_ht, $qty, $txtva, $fk_product=0, $remise_percent=0, $date_start, $date_end, $price_base_type='HT', $pu_ttc=0, $info_bits=0)
	{
		global $langs, $conf;

		dol_syslog("Contrat::addline $desc, $pu_ht, $qty, $txtva, $fk_product, $remise_percent, $date_start, $date_end, $price_base_type, $pu_ttc, $info_bits");

		if ($this->statut == 0 || ($this->statut >= 1 && empty($conf->global->CONTRAT_NOEDITWHENVALIDATED)))
		{
			$this->db->begin();

			// Clean parameters
			$remise_percent=price2num($remise_percent);
			$qty=price2num($qty);
			if (! $qty) $qty=1;
			if (! $ventil) $ventil=0;
			if (! $info_bits) $info_bits=0;
			if (! $pu_ht)  $pu_ht=0;
			if (! $pu_ttc) $pu_ttc=0;
			$pu_ht=price2num($pu_ht);
			$pu_ttc=price2num($pu_ttc);
			$txtva=price2num($txtva);

			if ($price_base_type=='HT')
			{
				$pu=$pu_ht;
			}
			else
			{
				$pu=$pu_ttc;
			}

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
			$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, $price_base_type, $info_bits);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];

			// \TODO A virer
			// Anciens indicateurs: $price, $remise (a ne plus utiliser)
			$remise = 0;
			$price = price2num(round($pu_ht, 2));
			if (strlen($remise_percent) > 0)
			{
				$remise = round(($pu_ht * $remise_percent / 100), 2);
				$price = $pu_ht - $remise;
			}

			// Insertion dans la base
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."contratdet";
			$sql.= " (fk_contrat, label, description, fk_product, qty, tva_tx,";
			$sql.= " remise_percent, subprice,";
			$sql.= " total_ht, total_tva, total_ttc,";
			$sql.= " info_bits,";
			$sql.= " price_ht, remise";								// \TODO A virer
			if ($date_start > 0) { $sql.= ",date_ouverture_prevue"; }
			if ($date_end > 0)  { $sql.= ",date_fin_validite"; }
			$sql.= ") VALUES ($this->id, '" . addslashes($label) . "','" . addslashes($desc) . "',";
			$sql.= ($fk_product>0 ? $fk_product : "null").",";
			$sql.= " '".$qty."',";
			$sql.= " '".$txtva."',";
			$sql.= " ".price2num($remise_percent).",".price2num($pu_ht).",";
			$sql.= " ".price2num($total_ht).",".price2num($total_tva).",".price2num($total_ttc).",";
			$sql.= " '".$info_bits."',";
			$sql.= " ".price2num($price).",".price2num( $remise);	// \TODO A virer
			if ($date_start > 0) { $sql.= ",".$this->db->idate($date_start); }
			if ($date_end > 0) { $sql.= ",".$this->db->idate($date_end); }
			$sql.= ")";

			dol_syslog("Contrat::addline sql=".$sql);

			$resql=$this->db->query($sql);
			if ($resql)
			{
				$result=$this->update_statut();
				if ($result > 0)
				{
					$this->db->commit();
					return 1;
				}
				else
				{
					dol_syslog("Error sql=$sql, error=".$this->error,LOG_ERR);
					$this->db->rollback();
					return -1;
				}
			}
			else
			{
				$this->db->rollback();
				$this->error=$this->db->error()." sql=".$sql;
				dol_syslog("Contrat::addline ".$this->error,LOG_ERR);
				return -1;
			}
		}
		else
		{
			dol_syslog("Contrat::addline ErrorTryToAddLineOnValidatedContract", LOG_ERR);
			return -2;
		}
	}

	/**
	 *      \brief     Mets a jour une ligne de contrat
	 *      \param     rowid            Id de la ligne de facture
	 *      \param     desc             Description de la ligne
	 *      \param     pu               Prix unitaire
	 *      \param     qty              Quantite
	 *      \param     remise_percent   Pourcentage de remise de la ligne
	 *      \param     date_start       Date de debut prevue
	 *      \param     date_end         Date de fin prevue
	 *      \param     tvatx            Taux TVA
	 *      \param     date_debut_reel  Date de debut reelle
	 *      \param     date_fin_reel    Date de fin reelle
	 *      \return    int              < 0 si erreur, > 0 si ok
	 */
	function updateline($rowid, $desc, $pu, $qty, $remise_percent=0,
	$date_start='', $date_end='', $tvatx,
	$date_debut_reel='', $date_fin_reel='')
	{
		// Nettoyage parametres
		$qty=trim($qty);
		$desc=trim($desc);
		$desc=trim($desc);
		$price = price2num($pu);
		$tvatx = price2num($tvatx);
		$subprice = $price;
		$remise = 0;
		if (strlen($remise_percent) > 0)
		{
			$remise = round(($pu * $remise_percent / 100), 2);
			$price = $pu - $remise;
		}
		else
		{
			$remise_percent=0;
		}

		dol_syslog("Contrat::UpdateLine $rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $date_debut_reel, $date_fin_reel, $tvatx");

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet set description='".addslashes($desc)."'";
		$sql .= ",price_ht='" .     price2num($price)."'";
		$sql .= ",subprice='" .     price2num($subprice)."'";
		$sql .= ",remise='" .       price2num($remise)."'";
		$sql .= ",remise_percent='".price2num($remise_percent)."'";
		$sql .= ",qty='$qty'";
		$sql .= ",tva_tx='".        price2num($tvatx)."'";

		if ($date_start > 0) { $sql.= ",date_ouverture_prevue=".$this->db->idate($date_start); }
		else { $sql.=",date_ouverture_prevue=null"; }
		if ($date_end > 0) { $sql.= ",date_fin_validite=".$this->db->idate($date_end); }
		else { $sql.=",date_fin_validite=null"; }
		if ($date_debut_reel > 0) { $sql.= ",date_ouverture=".$this->db->idate($date_debut_reel); }
		else { $sql.=",date_ouverture=null"; }
		if ($date_fin_reel > 0) { $sql.= ",date_cloture=".$this->db->idate($date_fin_reel); }
		else { $sql.=",date_cloture=null"; }
		$sql .= " WHERE rowid = ".$rowid;

		dol_syslog("Contrat::UpdateLine sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$result=$this->update_statut();
			if ($result >= 0)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				dol_syslog("Contrat::UpdateLigne Erreur -2");
				return -2;
			}
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->error();
			dol_syslog("Contrat::UpdateLigne Erreur -1");
			return -1;
		}
	}

	/**
	 *      \brief      Delete a contract line
	 *      \param      idline		Id of line to delete
	 *		\param      user        User that delete
	 *      \return     int         >0 if OK, <0 if KO
	 */
	function delete_line($idline,$user)
	{
		global $conf, $langs;

		if ($this->statut == 0 || ($this->statut >= 1 && empty($conf->global->CONTRAT_NOEDITWHENVALIDATED)) )
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."contratdet";
			$sql.= " WHERE rowid=".$idline;

			dol_syslog("Contratdet::delete sql=".$sql);
			$resql = $this->db->query($sql);
			if (! $resql)
			{
				$this->error="Error ".$this->db->lasterror();
				dol_syslog("Contratdet::delete ".$this->error, LOG_ERR);
				return -1;
			}

			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('CONTRACTLINE_DELETE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			return 1;
		}
		else
		{
			return -2;
		}
	}


	/**
	 *      \brief      Update statut of contract according to services
	 *		\return     int     <0 si ko, >0 si ok
	 */
	function update_statut()
	{
		// If draft, we keep it (should not happen)
		if ($this->statut == 0) return 1;

		// Load $this->lignes array
		//		$this->fetch_lignes();

		$newstatut=1;
		foreach($this->lignes as $key => $contractline)
		{
			//			if ($contractline)         // Loop on each service
		}

		return 1;
	}


	/**
	 *    	\brief      Retourne le libelle du statut du contrat
	 *    	\param      mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *    	\return     string      	Label
	 */
	function getLibStatut($mode)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *    	\brief      Renvoi le libelle d'un statut donne
	 *    	\param      statut      	id statut
	 *    	\param      mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *		\return     string      	Libelle
	 */
	function LibStatut($statut,$mode)
	{
		global $langs;
		$langs->load("contracts");
		if ($mode == 0)
		{
			if ($statut == 0) { return $langs->trans("ContractStatusDraft").$text; }
			if ($statut == 1) { return $langs->trans("ContractStatusValidated").$text; }
			if ($statut == 2) { return $langs->trans("ContractStatusClosed").$text; }
		}
		if ($mode == 1)
		{
			if ($statut == 0) { return $langs->trans("ContractStatusDraft"); }
			if ($statut == 1) { return $langs->trans("ContractStatusValidated"); }
			if ($statut == 2) { return $langs->trans("ContractStatusClosed"); }
		}
		if ($mode == 2)
		{
			if ($statut == 0) { return img_picto($langs->trans('ContractStatusDraft'),'statut0').' '.$langs->trans("ContractStatusDraft"); }
			if ($statut == 1) { return img_picto($langs->trans('ContractStatusValidated'),'statut4').' '.$langs->trans("ContractStatusValidated"); }
			if ($statut == 2) { return img_picto($langs->trans('ContractStatusClosed'),'statut6').' '.$langs->trans("ContractStatusClosed"); }
		}
		if ($mode == 3)
		{
			if ($statut == 0) { return img_picto($langs->trans('ContractStatusDraft'),'statut0'); }
			if ($statut == 1) { return img_picto($langs->trans('ContractStatusValidated'),'statut4'); }
			if ($statut == 2) { return img_picto($langs->trans('ContractStatusClosed'),'statut6'); }
		}
		if ($mode == 4)
		{
			$line=new ContratLigne($this->db);
			$text=($this->nbofserviceswait+$this->nbofservicesopened+$this->nbofservicesexpired+$this->nbofservicesclosed);
			$text.=' '.$langs->trans("Services");
			$text.=': &nbsp; &nbsp; ';
			$text.=$this->nbofserviceswait.' '.$line->LibStatut(0,3).' &nbsp; ';
			$text.=$this->nbofservicesopened.' '.$line->LibStatut(4,3,0).' &nbsp; ';
			$text.=$this->nbofservicesexpired.' '.$line->LibStatut(4,3,1).' &nbsp; ';
			$text.=$this->nbofservicesclosed.' '.$line->LibStatut(5,3);
			return $text;
		}
		if ($mode == 5)
		{
			if ($statut == 0) { return $langs->trans("ContractStatusDraft").' '.img_picto($langs->trans('ContractStatusDraft'),'statut0'); }
			if ($statut == 1) { return $langs->trans("ContractStatusValidated").' '.img_picto($langs->trans('ContractStatusValidated'),'statut4'); }
			if ($statut == 2) { return $langs->trans("ContractStatusClosed").' '.img_picto($langs->trans('ContractStatusClosed'),'statut6'); }
		}
	}


	/**
	 *	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *	\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *	\param		maxlength		Max length of ref
	 *	\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$maxlength=0)
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		$picto='contract';

		$label=$langs->trans("ShowContract").': '.$this->ref;

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.($maxlength?dol_trunc($this->ref,$maxlength):$this->ref).$lienfin;
		return $result;
	}

	/*
	 *       \brief     Charge les informations d'ordre info dans l'objet contrat
	 *       \param     id     id du contrat a charger
	 */
	function info($id)
	{
		$sql = "SELECT c.rowid, c.ref, ".$this->db->pdate("datec")." as datec, ".$this->db->pdate("date_cloture")." as date_cloture,";
		$sql.= $this->db->pdate("c.tms")." as date_modification,";
		$sql.= " fk_user_author, fk_user_cloture";
		$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c";
		$sql.= " WHERE c.rowid = ".$id;

		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				if ($obj->fk_user_author) {
					$cuser = new User($this->db, $obj->fk_user_author);
					$cuser->fetch();
					$this->user_creation     = $cuser;
				}

				if ($obj->fk_user_cloture) {
					$cuser = new User($this->db, $obj->fk_user_cloture);
					$cuser->fetch();
					$this->user_cloture = $cuser;
				}
				$this->ref			     = (! $obj->ref) ? $obj->rowid : $obj->ref;
				$this->date_creation     = $obj->datec;
				$this->date_modification = $obj->date_modification;
				$this->date_cloture      = $obj->date_cloture;
			}

			$this->db->free($result);

		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *    \brief      Return list of line rowid
	 *    \param      statut      Status of lines to get
	 *    \return     array       Array of line's rowid
	 */
	function array_detail($statut=-1)
	{
		$tab=array();

		$sql = "SELECT cd.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
		$sql.= " WHERE fk_contrat =".$this->id;
		if ($statut >= 0) $sql.= " AND statut = '$statut'";

		dol_syslog("Contrat::array_detail() sql=".$sql,LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$tab[$i]=$obj->rowid;
				$i++;
			}
			return $tab;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *  	\brief      Return list of other contracts for same company than current contract
	 *		\param		option		'all' or 'others'
	 *  	\return     array   	Array of contracts id
	 */
	function getListOfContracts($option='all')
	{
		$tab=array();

		$sql = "SELECT c.rowid, c.ref";
		$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c";
		$sql.= " WHERE fk_soc =".$this->socid;
		if ($option == 'others') $sql.= " AND c.rowid != ".$this->id;

		dol_syslog("Contrat::getOtherContracts() sql=".$sql,LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$contrat=new Contrat($this->db);
				$contrat->fetch($obj->rowid);
				$tab[]=$contrat;
				$i++;
			}
			return $tab;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 *      \brief      Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
	 *      \param      user        Objet user
	 *      \param      mode        "inactive" pour services a activer, "expired" pour services expires
	 *      \return     int         <0 si ko, >0 si ok
	 */
	function load_board($user,$mode)
	{
		global $conf, $user;

		$now=gmmktime();

		$this->nbtodo=$this->nbtodolate=0;

		$this->from = " FROM ".MAIN_DB_PREFIX."contrat as c";
		$this->from.= ", ".MAIN_DB_PREFIX."contratdet as cd";
		$this->from.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$user->societe_id) $this->from.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";

		if ($mode == 'inactives')
		{
			$sql = "SELECT cd.rowid, cd.date_ouverture_prevue as datefin";
			$sql.= $this->from;
			$sql.= " WHERE c.statut = 1";
			$sql.= " AND c.rowid = cd.fk_contrat";
			$sql.= " AND cd.statut = 0";
		}
		if ($mode == 'expired')
		{
			$sql = "SELECT cd.rowid, cd.date_fin_validite as datefin";
			$sql.= $this->from;
			$sql.= " WHERE c.statut = 1";
			$sql.= " AND c.rowid = cd.fk_contrat";
			$sql.= " AND cd.statut = 4";
			$sql.= " AND cd.date_fin_validite < '".$this->db->idate(time())."'";
		}
		$sql.= " AND c.fk_soc = s.rowid";
		$sql.= " AND s.entity = ".$conf->entity;
		if ($user->societe_id) $sql.=" AND c.fk_soc = ".$user->societe_id;
		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nbtodo++;
				if ($mode == 'inactives')
				if ($obj->datefin && $this->db->jdate($obj->datefin) < ($now - $conf->contrat->services->inactifs->warning_delay)) $this->nbtodolate++;
				if ($mode == 'expired')
				if ($obj->datefin && $this->db->jdate($obj->datefin) < ($now - $conf->contrat->services->expires->warning_delay)) $this->nbtodolate++;
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

	/**
	 *      \brief      Charge indicateurs this->nb de tableau de bord
	 *      \return     int         <0 si ko, >0 si ok
	 */
	function load_state_board()
	{
		global $conf, $user;

		$this->nb=array();
		$clause = "WHERE";

		$sql = "SELECT count(c.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON c.fk_soc = s.rowid";
		if (!$user->rights->contrat->activer && !$user->societe_id)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql.= " WHERE sc.fk_user = " .$user->id;
			$clause = "AND";
		}
		$sql.= " ".$clause." s.entity = ".$conf->entity;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["Contracts"]=$obj->nb;
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


	/* gestion des contacts d'un contrat */

	/**
	 *      \brief      Retourne id des contacts clients de facturation
	 *      \return     array       Liste des id contacts facturation
	 */
	function getIdBillingContact()
	{
		return $this->getIdContact('external','BILLING');
	}

	/**
	 *      \brief      Retourne id des contacts clients de prestation
	 *      \return     array       Liste des id contacts prestation
	 */
	function getIdServiceContact()
	{
		return $this->getIdContact('external','SERVICE');
	}

}


/**
 *	\class      ContratLigne
 *	\brief      Classe permettant la gestion des lignes de contrats
 */
class ContratLigne
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='contratdet';			//!< Id that identify managed objects
	//var $table_element='contratdet';	//!< Name of table without prefix where object is stored

	var $id;
	var $ref;
	var $tms;
	var $fk_contrat;
	var $fk_product;
	var $statut;					// 0 inactive, 4 active, 5 closed
	var $label;
	var $description;
	var $date_commande;
	var $date_ouverture_prevue;		// date start planned
	var $date_ouverture;			// date start real
	var $date_fin_validite;			// date end planned
	var $date_cloture;				// date end real
	var $tva_tx;
	var $qty;
	var $remise_percent;
	var $remise;
	var $fk_remise_except;

	var $subprice;					// Unit price HT
	var $price_ht;

	var $total_ht;
	var $total_tva;
	var $total_ttc;

	var $info_bits;
	var $fk_user_author;
	var $fk_user_ouverture;
	var $fk_user_cloture;
	var $commentaire;


	/**
	 *      \brief     Constructeur d'objets ligne de contrat
	 *      \param     DB      Database access handler
	 */
	function ContratLigne($DB)
	{
		$this->db = $DB;
	}


	/**
	 *    	\brief      Return label of this contract line status
	 *		\param      mode        	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *    	\return     string      	Libelle
	 */
	function getLibStatut($mode)
	{
		return $this->LibStatut($this->statut,$mode,(isset($this->date_fin_validite)?($this->date_fin_validite < dol_now('tzref')?1:0):-1));
	}

	/**
	 *    	\brief      Return label of a contract line status
	 *    	\param      statut      id statut
	 *		\param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *		\param		expired		0=Not expired, 1=Expired, -1=Both or unknown
	 *    	\return     string      Libelle
	 */
	function LibStatut($statut,$mode,$expired=-1)
	{
		global $langs;
		$langs->load("contracts");
		if ($mode == 0)
		{
			if ($statut == 0) { return $langs->trans("ServiceStatusInitial"); }
			if ($statut == 4 && $expired == -1) { return $langs->trans("ServiceStatusRunning"); }
			if ($statut == 4 && $expired == 0)  { return $langs->trans("ServiceStatusNotLate"); }
			if ($statut == 4 && $expired == 1)  { return $langs->trans("ServiceStatusLate"); }
			if ($statut == 5) { return $langs->trans("ServiceStatusClosed");  }
		}
		if ($mode == 1)
		{
			if ($statut == 0) { return $langs->trans("ServiceStatusInitial"); }
			if ($statut == 4 && $expired == -1) { return $langs->trans("ServiceStatusRunning"); }
			if ($statut == 4 && $expired == 0)  { return $langs->trans("ServiceStatusNotLateShort"); }
			if ($statut == 4 && $expired == 1)  { return $langs->trans("ServiceStatusLateShort"); }
			if ($statut == 5) { return $langs->trans("ServiceStatusClosed");  }
		}
		if ($mode == 2)
		{
			if ($statut == 0) { return img_picto($langs->trans('ServiceStatusInitial'),'statut0').' '.$langs->trans("ServiceStatusInitial"); }
			if ($statut == 4 && $expired == -1) { return img_picto($langs->trans('ServiceStatusRunning'),'statut4').' '.$langs->trans("ServiceStatusRunning"); }
			if ($statut == 4 && $expired == 0)  { return img_picto($langs->trans('ServiceStatusNotLate'),'statut4').' '.$langs->trans("ServiceStatusNotLateShort"); }
			if ($statut == 4 && $expired == 1)  { return img_picto($langs->trans('ServiceStatusLate'),'statut3').' '.$langs->trans("ServiceStatusLateShort"); }
			if ($statut == 5) { return img_picto($langs->trans('ServiceStatusClosed'),'statut6') .' '.$langs->trans("ServiceStatusClosed"); }
		}
		if ($mode == 3)
		{
			if ($statut == 0) { return img_picto($langs->trans('ServiceStatusInitial'),'statut0'); }
			if ($statut == 4 && $expired == -1) { return img_picto($langs->trans('ServiceStatusRunning'),'statut4'); }
			if ($statut == 4 && $expired == 0)  { return img_picto($langs->trans('ServiceStatusNotLate'),'statut4'); }
			if ($statut == 4 && $expired == 1)  { return img_picto($langs->trans('ServiceStatusLate'),'statut3'); }
			if ($statut == 5) { return img_picto($langs->trans('ServiceStatusClosed'),'statut6'); }
		}
		if ($mode == 4)
		{
			if ($statut == 0) { return img_picto($langs->trans('ServiceStatusInitial'),'statut0').' '.$langs->trans("ServiceStatusInitial"); }
			if ($statut == 4 && $expired == -1) { return img_picto($langs->trans('ServiceStatusRunning'),'statut4').' '.$langs->trans("ServiceStatusRunning"); }
			if ($statut == 4 && $expired == 0)  { return img_picto($langs->trans('ServiceStatusNotLate'),'statut4').' '.$langs->trans("ServiceStatusNotLate"); }
			if ($statut == 4 && $expired == 1)  { return img_picto($langs->trans('ServiceStatusLate'),'statut3').' '.$langs->trans("ServiceStatusLate"); }
			if ($statut == 5) { return img_picto($langs->trans('ServiceStatusClosed'),'statut6') .' '.$langs->trans("ServiceStatusClosed"); }
		}
		if ($mode == 5)
		{
			if ($statut == 0) { return $langs->trans("ServiceStatusInitial").' '.img_picto($langs->trans('ServiceStatusInitial'),'statut0'); }
			if ($statut == 4 && $expired == -1) { return $langs->trans("ServiceStatusRunning").' '.img_picto($langs->trans('ServiceStatusRunning'),'statut4'); }
			if ($statut == 4 && $expired == 0)  { return $langs->trans("ServiceStatusNotLateShort").' '.img_picto($langs->trans('ServiceStatusNotLateShort'),'statut4'); }
			if ($statut == 4 && $expired == 1)  { return $langs->trans("ServiceStatusLateShort").' '.img_picto($langs->trans('ServiceStatusLate'),'statut3'); }
			if ($statut == 5) { return $langs->trans("ServiceStatusClosed").' '.img_picto($langs->trans('ServiceStatusClosed'),'statut6'); }
		}
	}

	/**
		\brief      Renvoie nom clicable (avec eventuellement le picto)
		\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
		\return		string			Chaine avec URL
		*/
	function getNomUrl($withpicto=0,$maxlength=0)
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$this->fk_contrat.'">';
		$lienfin='</a>';

		$picto='contract';

		$label=$langs->trans("ShowContractOfService").': '.$this->label;

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->label.$lienfin;
		return $result;
	}

	/**
	 *    	\brief      Load object in memory from database
	 *    	\param      id          id object
	 * 		\param		ref			Ref of contract
	 *    	\return     int         <0 if KO, >0 if OK
	 */
	function fetch($id, $ref='')
	{
		global $langs,$user;

		// Check parameters
		if (empty($id) && empty($ref)) return -1;

		$sql = "SELECT";
		$sql.= " t.rowid,";

		$sql.= " ".$this->db->pdate('t.tms')." as tms,";
		$sql.= " t.fk_contrat,";
		$sql.= " t.fk_product,";
		$sql.= " t.statut,";
		$sql.= " t.label,";
		$sql.= " t.description,";
		$sql.= " ".$this->db->pdate('t.date_commande')." as date_commande,";
		$sql.= " ".$this->db->pdate('t.date_ouverture_prevue')." as date_ouverture_prevue,";
		$sql.= " ".$this->db->pdate('t.date_ouverture')." as date_ouverture,";
		$sql.= " ".$this->db->pdate('t.date_fin_validite')." as date_fin_validite,";
		$sql.= " ".$this->db->pdate('t.date_cloture')." as date_cloture,";
		$sql.= " t.tva_tx,";
		$sql.= " t.qty,";
		$sql.= " t.remise_percent,";
		$sql.= " t.remise,";
		$sql.= " t.fk_remise_except,";
		$sql.= " t.subprice,";
		$sql.= " t.price_ht,";
		$sql.= " t.total_ht,";
		$sql.= " t.total_tva,";
		$sql.= " t.total_ttc,";
		$sql.= " t.info_bits,";
		$sql.= " t.fk_user_author,";
		$sql.= " t.fk_user_ouverture,";
		$sql.= " t.fk_user_cloture,";
		$sql.= " t.commentaire";
		$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as t";
		if ($id)  $sql.= " WHERE t.rowid = ".$id;
		if ($ref) $sql.= " WHERE t.rowid = '".$ref."'";

		dol_syslog("Contratdet::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = $obj->rowid;

				$this->tms = $obj->tms;
				$this->fk_contrat = $obj->fk_contrat;
				$this->fk_product = $obj->fk_product;
				$this->statut = $obj->statut;
				$this->label = $obj->label;
				$this->description = $obj->description;
				$this->date_commande = $obj->date_commande;
				$this->date_ouverture_prevue = $obj->date_ouverture_prevue;
				$this->date_ouverture = $obj->date_ouverture;
				$this->date_fin_validite = $obj->date_fin_validite;
				$this->date_cloture = $obj->date_cloture;
				$this->tva_tx = $obj->tva_tx;
				$this->qty = $obj->qty;
				$this->remise_percent = $obj->remise_percent;
				$this->remise = $obj->remise;
				$this->fk_remise_except = $obj->fk_remise_except;
				$this->subprice = $obj->subprice;
				$this->price_ht = $obj->price_ht;
				$this->total_ht = $obj->total_ht;
				$this->total_tva = $obj->total_tva;
				$this->total_ttc = $obj->total_ttc;
				$this->info_bits = $obj->info_bits;
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_ouverture = $obj->fk_user_ouverture;
				$this->fk_user_cloture = $obj->fk_user_cloture;
				$this->commentaire = $obj->commentaire;

			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog("ContratLigne::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/*
	 *      \brief      Update database for contract line
	 *      \param      user        	User that modify
	 *      \param      notrigger	    0=no, 1=yes (no update trigger)
	 *      \return     int         	<0 if KO, >0 if OK
	 */
	function update($user, $notrigger=0)
	{
		global $conf, $langs;

		// Clean parameters
		$this->fk_contrat=trim($this->fk_contrat);
		$this->fk_product=trim($this->fk_product);
		$this->statut=trim($this->statut);
		$this->label=trim($this->label);
		$this->description=trim($this->description);
		$this->tva_tx=trim($this->tva_tx);
		$this->qty=trim($this->qty);
		$this->remise_percent=trim($this->remise_percent);
		$this->remise=trim($this->remise);
		$this->fk_remise_except=trim($this->fk_remise_except);
		$this->subprice=price2num($this->subprice);
		$this->price_ht=price2num($this->price_ht);
		$this->total_ht=trim($this->total_ht);
		$this->total_tva=trim($this->total_tva);
		$this->total_ttc=trim($this->total_ttc);
		$this->info_bits=trim($this->info_bits);
		$this->fk_user_author=trim($this->fk_user_author);
		$this->fk_user_ouverture=trim($this->fk_user_ouverture);
		$this->fk_user_cloture=trim($this->fk_user_cloture);
		$this->commentaire=trim($this->commentaire);

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET";
		$sql.= " fk_contrat='".$this->fk_contrat."',";
		$sql.= " fk_product=".($this->fk_product?"'".$this->fk_product."'":'null').",";
		$sql.= " statut='".$this->statut."',";
		$sql.= " label='".addslashes($this->label)."',";
		$sql.= " description='".addslashes($this->description)."',";
		$sql.= " date_commande=".($this->date_commande!=''?$this->db->idate($this->date_commande):"null").",";
		$sql.= " date_ouverture_prevue=".($this->date_ouverture_prevue!=''?$this->db->idate($this->date_ouverture_prevue):"null").",";
		$sql.= " date_ouverture=".($this->date_ouverture!=''?$this->db->idate($this->date_ouverture):"null").",";
		$sql.= " date_fin_validite=".($this->date_fin_validite!=''?$this->db->idate($this->date_fin_validite):"null").",";
		$sql.= " date_cloture=".($this->date_cloture!=''?$this->db->idate($this->date_cloture):"null").",";
		$sql.= " tva_tx='".$this->tva_tx."',";
		$sql.= " qty='".$this->qty."',";
		$sql.= " remise_percent='".$this->remise_percent."',";
		$sql.= " remise=".($this->remise?"'".$this->remise."'":"null").",";
		$sql.= " fk_remise_except=".($this->fk_remise_except?"'".$this->fk_remise_except."'":"null").",";
		$sql.= " subprice='".$this->subprice."',";
		$sql.= " price_ht='".$this->price_ht."',";
		$sql.= " total_ht='".$this->total_ht."',";
		$sql.= " total_tva='".$this->total_tva."',";
		$sql.= " total_ttc='".$this->total_ttc."',";
		$sql.= " info_bits='".$this->info_bits."',";
		$sql.= " fk_user_author=".($this->fk_user_author >= 0?$this->fk_user_author:"NULL").",";
		$sql.= " fk_user_ouverture=".($this->fk_user_ouverture > 0?$this->fk_user_ouverture:"NULL").",";
		$sql.= " fk_user_cloture=".($this->fk_user_cloture > 0?$this->fk_user_cloture:"NULL").",";
		$sql.= " commentaire='".addslashes($this->commentaire)."'";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog("ContratLigne::update sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$contrat=new Contrat($this->db);
			$contrat->fetch($this->fk_contrat);
			$result=$contrat->update_statut();
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog("ContratLigne::update ".$this->error, LOG_ERR);
			return -1;
		}

		if (! $notrigger)
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers
		}

		return 1;
	}


	/**
	 *      \brief     	Mise a jour en base des champs total_xxx de ligne
	 *		\remarks	Utilise par migration
	 *		\return		int		<0 si ko, >0 si ok
	 */
	function update_total()
	{
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET";
		$sql.= " total_ht=".price2num($this->total_ht,'MT')."";
		$sql.= ",total_tva=".price2num($this->total_tva,'MT')."";
		$sql.= ",total_ttc=".price2num($this->total_ttc,'MT')."";
		$sql.= " WHERE rowid = ".$this->rowid;

		dol_syslog("ContratLigne::update_total sql=".$sql);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("ContratLigne::update_total Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}
}


?>
