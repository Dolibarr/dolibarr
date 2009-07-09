<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles  <ccomb@free.fr>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
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
 \file       htdocs/fourn/fournisseur.facture.class.php
 \ingroup    fournisseur,facture
 \brief      Fichier de la classe des factures fournisseurs
 \version    $Id$
 */

include_once(DOL_DOCUMENT_ROOT."/facture.class.php");


/**
 \class      FactureFournisseur
 \brief      Classe permettant la gestion des factures fournisseurs
 */

class FactureFournisseur extends Facture
{
	var $id;
	var $db;

	var $element='facture_fourn';
	var $table_element='facture_fourn';
	var $table_element_line='facture_fourn_det';
	var $fk_element='fk_facture_fourn';

	var $ref;
	var $ref_supplier;
	var $socid;
	//! 0=draft,
	//! 1=validated,
	//! TODO Ce statut doit etre 2 et non 1 classee payee partiellement (close_code='discount_vat','badcustomer') ou completement (close_code=null),
	//! TODO Ce statut doit etre 2 et non 1 classee abandonnee et aucun paiement n'a eu lieu (close_code='badcustomer','abandon' ou 'replaced')
	var $statut;
	//! 1 si facture payee COMPLETEMENT, 0 sinon (ce champ ne devrait plus servir car insuffisant)
	var $paye;

	var $author;
	var $libelle;
	var $date;
	var $date_echeance;
	var $amount;
	var $remise;
	var $tva;
	var $total_ht;
	var $total_tva;
	var $total_ttc;
	var $note;
	var $note_public;
	var $propalid;

	var $lignes;
	var $fournisseur;

	/**
	 *    \brief  Constructeur de la classe
	 *    \param  DB          	Database access handler
	 *    \param  socid			Id societe ('' par defaut)
	 *    \param  facid       	Id facture ('' par defaut)
	 */
	function FactureFournisseur($DB, $socid='', $facid='')
	{
		$this->db = $DB ;
		$this->table = 'facture_fourn';
		$this->tabledetail = 'facture_fourn_det';

		$this->id = $facid;
		$this->socid = $socid;

		$this->amount = 0;
		$this->remise = 0;
		$this->tva = 0;
		$this->total_ht = 0;
		$this->total_tva = 0;
		$this->total_ttc = 0;
		$this->propalid = 0;

		$this->products = array();
		$this->lignes = array();
	}

	/**
	 *    \brief      Creation de la facture en base
	 *    \param      user        object utilisateur qui cree
	 *    \return     int         id facture si ok, < 0 si erreur
	 */
	function create($user)
	{
		global $langs,$conf;

		// Clear parameters
		if (empty($this->date)) $this->date=gmmktime();

		$socid = $this->socid;
		$number = $this->ref_supplier?$this->ref_supplier:$this->ref;
		$amount = $this->amount;
		$remise = $this->remise;

		$this->db->begin();

		if (! $remise) $remise = 0 ;
		$totalht = ($amount - $remise);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_fourn (";
		$sql.= "facnumber";
		$sql.= ", entity";
		$sql.= ", libelle";
		$sql.= ", fk_soc";
		$sql.= ", datec";
		$sql.= ", datef";
		$sql.= ", note";
		$sql.= ", note_public";
		$sql.= ", fk_user_author";
		$sql.= ", date_lim_reglement";
		$sql.= ")";
		$sql.= " VALUES (";
		$sql.= "'".addslashes($number)."'";
		$sql.= ", ".$conf->entity;
		$sql.= ", '".addslashes($this->libelle)."'";
		$sql.= ", ".$this->socid;
		$sql.= ", ".$this->db->idate(gmmktime());
		$sql.= ", '".$this->db->idate($this->date)."'";
		$sql.= ", '".addslashes($this->note)."'";
		$sql.= ", '".addslashes($this->note_public)."'";
		$sql.= ", ".$user->id.",'".$this->db->idate($this->date_echeance)."'";
		$sql.= ")";

		dol_syslog("FactureFournisseur::create sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn');
			for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
			{
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture_fourn_det (fk_facture_fourn)';
				$sql .= ' VALUES ('.$this->id.');';

				dol_syslog("FactureFournisseur::create sql=".$sql, LOG_DEBUG);
				$resql_insert=$this->db->query($sql);
				if ($resql_insert)
				{
					$idligne = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn_det');

					$this->updateline($idligne,
					$this->lignes[$i]->description,
					$this->lignes[$i]->pu_ht,
					$this->lignes[$i]->tva_taux,
					$this->lignes[$i]->qty,
					$this->lignes[$i]->fk_product,
					'HT',
					$this->lignes[$i]->info_bits,
					$this->lignes[$i]->product_type
					);
				}
			}
			// Update total price
			if ($this->update_price() > 0)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				$this->error=$langs->trans('FailedToUpdatePrice');
				$this->db->rollback();
				return -3;
			}
		}
		else
		{
			if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$this->error=$langs->trans('ErrorBillRefAlreadyExists');
				$this->db->rollback();
				return -1;
			}
			else
			{
				$this->error=$this->db->error();
				$this->db->rollback();
				return -2;
			}
		}
	}

	/**
	 *    	\brief      Load object from database
	 *    	\param      rowid       id of object to get
	 *		\return     int         >0 if ok, <0 if ko
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT libelle, facnumber, amount, remise, '.$this->db->pdate(datef).'as df,';
		$sql.= ' total_ht, total_tva, total_ttc, fk_user_author,';
		$sql.= ' fk_statut, fk_projet as fk_project, paye, f.note, f.note_public,';
		$sql.= ' '.$this->db->pdate('date_lim_reglement').'as de,';
		$sql.= ' s.nom as socnom, s.rowid as socid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f,'.MAIN_DB_PREFIX.'societe as s';
		$sql.= ' WHERE f.rowid='.$rowid.' AND f.fk_soc = s.rowid';

		dol_syslog("FactureFournisseur::Fetch sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			if ($num)
			{
				$obj = $this->db->fetch_object($resql);

				$this->id            = $rowid;
				$this->ref           = $this->id;
				$this->ref_supplier  = $obj->facnumber;

				$this->datep         = $obj->df;
				$this->date          = $obj->df;
				$this->date_echeance = $obj->de;
				$this->libelle       = $obj->libelle;

				$this->remise        = $obj->remise;
				$this->socid        = $obj->socid;

				$this->total_ht  = $obj->total_ht;
				$this->total_tva = $obj->total_tva;
				$this->total_ttc = $obj->total_ttc;

				$this->author    = $obj->fk_user_author;

				$this->statut = $obj->fk_statut;
				$this->paye   = $obj->paye;

				$this->fk_project = $obj->fk_project;

				$this->socnom = $obj->socnom;
				$this->note = $obj->note;
				$this->note_public = $obj->note_public;

				$this->db->free($resql);

				// Lines
				$result=$this->fetch_lines();
				if ($result < 0)
				{
					$this->error=$this->db->error();
					dol_syslog('FactureFournisseur::Fetch Error '.$this->error, LOG_ERR);
					return -3;
				}
				return 1;
			}
			else
			{
				dol_syslog('FactureFournisseur::Fetch rowid='.$rowid.' numrows=0 sql='.$sql);
				$this->error='Bill with id '.$rowid.' not found sql='.$sql;
				dol_print_error($this->db);
				return -2;
			}
		}
		else
		{
			dol_syslog('FactureFournisseur::Fetch rowid='.$rowid.' Erreur dans fetch de la facture fournisseur');
			$this->error=$this->db->error();
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *	\brief      Load this->lignes
	 *	\return     int         1 si ok, < 0 si erreur
	 */
	function fetch_lines()
	{
		$sql = 'SELECT f.rowid, f.description, f.pu_ht, f.pu_ttc, f.qty, f.tva_taux, f.tva';
		$sql.= ', f.total_ht, f.tva as total_tva, f.total_ttc, f.fk_product, f.product_type';
		$sql.= ', p.rowid as product_id, p.ref, p.label as label, p.description as product_desc';
		//$sql.= ', pf.ref_fourn';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn_det as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON f.fk_product = p.rowid';
		//$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_fournisseur as pf ON f.fk_product = pf.fk_product';
		$sql.= ' WHERE fk_facture_fourn='.$this->id;

		dol_syslog("FactureFournisseur::fetch_lines sql=".$sql, LOG_DEBUG);
		$resql_rows = $this->db->query($sql);
		if ($resql_rows)
		{
			$num_rows = $this->db->num_rows($resql_rows);
			if ($num_rows)
			{
				$i = 0;
				while ($i < $num_rows)
				{
					$obj = $this->db->fetch_object($resql_rows);
					$this->lignes[$i]->rowid            = $obj->rowid;
					$this->lignes[$i]->description      = $obj->description;
					$this->lignes[$i]->ref              = $obj->ref;             // Reference interne du produit
					//$this->lignes[$i]->ref_fourn        = $obj->ref_fourn;       // Reference fournisseur du produit
					$this->lignes[$i]->libelle          = $obj->label;           // Label du produit
					$this->lignes[$i]->product_desc     = $obj->product_desc;    // Description du produit
					$this->lignes[$i]->pu_ht            = $obj->pu_ht;
					$this->lignes[$i]->pu_ttc           = $obj->pu_ttc;
					$this->lignes[$i]->tva_taux         = $obj->tva_taux;
					$this->lignes[$i]->qty              = $obj->qty;
					$this->lignes[$i]->tva              = $obj->tva;
					$this->lignes[$i]->total_ht         = $obj->total_ht;
					$this->lignes[$i]->total_tva        = $obj->total_tva;
					$this->lignes[$i]->total_ttc        = $obj->total_ttc;
					$this->lignes[$i]->fk_product       = $obj->fk_product;
					$this->lignes[$i]->product_type     = $obj->product_type;
					$i++;
				}
			}
			$this->db->free($resql_rows);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog('FactureFournisseur::fetch_lines: Error '.$this->error,LOG_ERR);
			return -3;
		}
	}


	/**
	 * \brief     Load supplier
	 */
	function fetch_fournisseur()
	{
		$fournisseur = new Fournisseur($this->db);
		$fournisseur->fetch($this->socid);
		$this->fournisseur = $fournisseur;
	}

	/**
	 * \brief     	Delete invoice in database
	 * \param     	rowid      	Id of invoice to delete
	 * \return		int			<0 if KO, >0 if OK
	 */
	function delete($rowid)
	{
		global $user,$langs,$conf;

		if (! $rowid) $rowid=$this->id;

		dol_syslog("FactureFournisseur::delete rowid=".$rowid, LOG_DEBUG);

		// TODO Test if there is at least on payment. If yes, refuse to delete.

		$error=0;
		$this->db->begin();

		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn_det WHERE fk_facture_fourn = '.$rowid.';';
		dol_syslog("FactureFournisseur sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn WHERE rowid = '.$rowid;
			dol_syslog("FactureFournisseur sql=".$sql, LOG_DEBUG);
			$resql2 = $this->db->query($sql);
			if (! $resql2) $error++;

			if (! $error)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				$this->error=$this->db->lasterror();
				dol_syslog("FactureFournisseur::delete ".$this->error, LOG_ERR);
				return -1;
			}
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->lasterror();
			dol_syslog("FactureFournisseur::delete ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *      \brief      Tag la facture comme payee completement
	 *      \param      user        Objet utilisateur qui modifie l'etat
	 *      \return     int         <0 si ko, >0 si ok
	 */
	function set_payed($user)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn';
		$sql.= ' SET paye = 1';
		$sql.= ' WHERE rowid = '.$this->id;

		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error=$this->db->error();
			dol_print_error($this->db);
			return -1;
		}
		return 1;
	}


	/**
	 *      \brief      Set invoice status as validate
	 *      \param      user        Objet utilisateur qui valide la facture
	 *      \return     int         <0 si ko, >0 si ok
	 */
	function set_valid($user)
	{
		global $conf,$langs;

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn";
		$sql.= " SET fk_statut = 1, fk_user_valid = ".$user->id;
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog("FactureFournisseur::set_valid sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$error=0;

			// Si activé on décrémente le produit principal et ses composants à la validation de facture
			if ($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL)
			{
				require_once(DOL_DOCUMENT_ROOT."/product/stock/mouvementstock.class.php");

				for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
				{
					if ($this->lignes[$i]->fk_product && $this->lignes[$i]->product_type == 0)
					{
						$mouvP = new MouvementStock($this->db);
						// We increase stock for product
						$entrepot_id = "1"; // TODO ajouter possibilite de choisir l'entrepot
						$result=$mouvP->reception($user, $this->lignes[$i]->fk_product, $entrepot_id, $this->lignes[$i]->qty, $this->lignes[$i]->pu_ht);
						if ($result < 0) { $error++; }
					}
				}
			}

			if ($error == 0)
			{
				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('BILL_SUPPLIER_VALIDATE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers
			}

			if ($error == 0)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 * 		\brief     	Ajoute une ligne de facture (associ� � aucun produit/service pr�d�fini)
	 * 		\param    	desc            Description de la ligne
	 * 		\param    	pu              Prix unitaire (HT ou TTC selon price_base_type)
	 * 		\param    	txtva           Taux de tva forc�, sinon -1
	 * 		\param    	qty             Quantit�
	 *		\param    	fk_product      Id du produit/service pred�fini
	 * 		\param    	remise_percent  Pourcentage de remise de la ligne
	 * 		\param    	date_start      Date de debut de validit� du service
	 * 		\param    	date_end        Date de fin de validit� du service
	 * 		\param    	ventil          Code de ventilation comptable
	 * 		\param    	info_bits		Bits de type de lignes
	 * 		\param    	price_base_type HT ou TTC
	 * 		\param		type			Type of line (0=product, 1=service)
	 * 		\remarks	Les parametres sont deja cens� etre juste et avec valeurs finales a l'appel
	 *					de cette methode. Aussi, pour le taux tva, il doit deja avoir ete d�fini
	 *					par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,taux_produit)
	 *					et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
	 */
	function addline($desc, $pu, $txtva, $qty, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits='', $price_base_type='HT', $type=0)
	{
		dol_syslog("FactureFourn::Addline $desc,$pu,$qty,$txtva,$fk_product,$remise_percent,$date_start,$date_end,$ventil,$info_bits,$price_base_type,$type", LOG_DEBUG);
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

		// Clean parameters
		if ($txtva == '') $txtva=0;
		$txtva=price2num($txtva);

		// Check parameters
		if ($type < 0) return -1;


		$this->db->begin();

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture_fourn_det (fk_facture_fourn)';
		$sql .= ' VALUES ('.$this->id.');';
		dol_syslog("Fournisseur.facture::addline sql=".$sql);

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$idligne = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn_det');

			$result=$this->updateline($idligne, $desc, $pu, $txtva, $qty, $fk_product, $price_base_type, $info_bits, $type);
			if ($result > 0)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Error sql=$sql, error=".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->error=$ligne->error;
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 * \brief     	Update line
	 * \param     	id            	Id of line invoice
	 * \param     	label         	Description of line
	 * \param     	pu          	Prix unitaire (HT ou TTC selon price_base_type)
	 * \param     	tauxtva       	VAT Rate
	 * \param     	qty           	Quantity
	 * \param     	idproduct		Id produit
	 * \param	  	price_base_type	HT or TTC
	 * \param	  	info_bits		Miscellanous informations of line
	 * \param		type			Type of line (0=product, 1=service)
	 * \return    	int           	<0 if KO, >0 if OK
	 */
	function updateline($id, $label, $pu, $tauxtva, $qty=1, $idproduct=0, $price_base_type='HT', $info_bits=0, $type=0)
	{
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

		$pu = price2num($pu);
		$qty  = price2num($qty);

		// Check parameters
		if (! is_numeric($pu) || ! is_numeric($qty)) return -1;
		if ($type < 0) return -1;

		// Calcul du total TTC et de la TVA pour la ligne a partir de
		// qty, pu, remise_percent et txtva
		// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
		// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
		$tabprice = calcul_price_total($qty, $pu, 0, $tauxtva, 0, $price_base_type, $info_bits);
		$total_ht  = $tabprice[0];
		$total_tva = $tabprice[1];
		$total_ttc = $tabprice[2];
		$pu_ht  = $tabprice[3];
		$pu_tva = $tabprice[4];
		$pu_ttc = $tabprice[5];

		if ($idproduct)
		{
			$product=new Product($this->db);
			$result=$product->fetch($idproduct);
			$product_type = $product->type;
		}
		else
		{
			$product_type = $type;
		}

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn_det ';
		$sql.= 'SET ';
		$sql.= 'description =\''.addslashes($label).'\'';
		$sql.= ', pu_ht = '  .price2num($pu_ht);
		$sql.= ', pu_ttc= '  .price2num($pu_ttc);
		$sql.= ', qty ='     .price2num($qty);
		$sql.= ', tva_taux=' .price2num($tauxtva);
		$sql.= ', total_ht=' .price2num($total_ht);
		$sql.= ', tva='      .price2num($total_tva);
		$sql.= ', total_ttc='.price2num($total_ttc);
		if ($idproduct) $sql.= ', fk_product='.$idproduct;
		else $sql.= ', fk_product=null';
		$sql.= ', product_type='.$product_type;
		$sql.= ' WHERE rowid = '.$id;

		dol_syslog("Fournisseur.facture::updateline sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			// Mise a jour prix total facture
			return $this->update_price();
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("Fournisseur.facture::updateline error=".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * \brief     Supprime une ligne facture de la base
	 * \param     rowid      id de la ligne de facture a supprimer
	 */
	function deleteline($rowid)
	{
		// Supprime ligne
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn_det ';
		$sql .= ' WHERE rowid = '.$rowid.';';
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			dol_print_error($this->db);
		}
		// Mise a jour prix facture
		$this->update_price();
		return 1;
	}


	/**
	 *      \brief     Charge les informations d'ordre info dans l'objet facture
	 *      \param     id       	Id de la facture a charger
	 */
	function info($id)
	{
		$sql = 'SELECT c.rowid, datec, tms as datem,';
		$sql.= ' fk_user_author, fk_user_valid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as c';
		$sql.= ' WHERE c.rowid = '.$id;

		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db, $obj->fk_user_author);
					$cuser->fetch();
					$this->user_creation     = $cuser;
				}
				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db, $obj->fk_user_valid);
					$vuser->fetch();
					$this->user_validation = $vuser;
				}
				$this->date_creation     = $obj->datec;
				$this->date_modification = $obj->datem;
				//$this->date_validation   = $obj->datev; Should be stored in log table
			}
			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}


	/**
	 *      \brief      Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
	 *      \param      user        Objet user
	 *      \return     int         <0 si ko, >0 si ok
	 */
	function load_board($user)
	{
		global $conf, $user;

		$now=gmmktime();

		$this->nbtodo=$this->nbtodolate=0;
		$sql = 'SELECT ff.rowid, ff.date_lim_reglement as datefin';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as ff';
		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= ' WHERE ff.paye=0';
		$sql.= ' AND ff.fk_statut > 0';
		$sql.= " AND ff.entity = ".$conf->entity;
		if ($user->societe_id) $sql.=' AND ff.fk_soc = '.$user->societe_id;
		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND ff.fk_soc = sc.fk_soc AND sc.fk_user = ".$user->id;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nbtodo++;
				if ($this->db->jdate($obj->datefin) < ($now - $conf->facture->fournisseur->warning_delay)) $this->nbtodolate++;
			}
			$this->db->free($resql);
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
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *		\param		option			Sur quoi pointe le lien
	 * 		\param		max				Max length of shown ref
	 * 		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='',$max=0)
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?facid='.$this->id.'">';
		$lienfin='</a>';

		$label=$langs->trans("ShowInvoice").': '.$this->ref;

		if ($withpicto) $result.=($lien.img_object($label,'bill').$lienfin.' ');
		$result.=$lien.($max?dol_trunc($this->ref,$max):$this->ref).$lienfin;
		return $result;
	}


	/**
	 *		\brief		Initialise la facture avec valeurs fictives aleatoire
	 *					Sert a generer une facture pour l'aperu des modeles ou demo
	 */
	function initAsSpecimen()
	{
		global $user,$langs,$conf;

		// Charge tableau des id de societe socids
		$socids = array();

		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe";
		$sql.= " WHERE fournisseur = 1";
		$sql.= " AND entity = ".$conf->entity;
		$sql.= " LIMIT 10";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num_socs = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_socs)
			{
				$i++;

				$row = $this->db->fetch_row($resql);
				$socids[$i] = $row[0];
			}
		}

		// Charge tableau des produits prodids
		$prodids = array();

		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."product";
		$sql.= " WHERE envente = 1";
		$sql.= " AND entity = ".$conf->entity;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num_prods = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_prods)
			{
				$i++;
				$row = $this->db->fetch_row($resql);
				$prodids[$i] = $row[0];
			}
		}

		// Initialise parametres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$socid = rand(1, $num_socs);
		$this->socid = $socids[$socid];
		$this->date = time();
		$this->date_lim_reglement=$this->date+3600*24*30;
		$this->cond_reglement_code = 'RECEP';
		$this->mode_reglement_code = 'CHQ';
		$this->note_public='SPECIMEN';
		$nbp = 5;
		$xnbp = 0;
		while ($xnbp < $nbp)
		{
			$ligne=new FactureLigne($this->db);
			$ligne->desc=$langs->trans("Description")." ".$xnbp;
			$ligne->qty=1;
			$ligne->subprice=100;
			$ligne->price=100;
			$ligne->tva_tx=19.6;
			$prodid = rand(1, $num_prods);
			$ligne->produit_id=$prodids[$prodid];
			$ligne->product_type=0;

			$this->lignes[$xnbp]=$ligne;
			$xnbp++;
		}

		$this->amount_ht      = $xnbp*100;
		$this->total_ht       = $xnbp*100;
		$this->total_tva      = $xnbp*19.6;
		$this->total_ttc      = $xnbp*119.6;
	}

	/**
	 *		\brief      Load an object from its id and create a new one in database
	 *		\param      fromid     		Id of object to clone
	 *		\param		invertdetail	Reverse sign of amounts for lines
	 * 	 	\return		int				New id of clone
	 */
	function createFromClone($fromid,$invertdetail=0)
	{
		global $user,$langs;

		$error=0;

		$object=new FactureFournisseur($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		$object->ref_supplier=$langs->trans("CopyOf").' '.$object->ref_supplier;
		$object->author             = $user->id;
		$object->user_valid         = '';
		$object->fk_facture_source  = 0;
		$object->date_creation      = '';
		$object->date_validation    = '';
		$object->date               = '';
		$object->ref_client         = '';
		$object->close_code         = '';
		$object->close_note         = '';

		// Create clone
		$result=$object->create($user);

		// Other options
		if ($result < 0)
		{
			$this->error=$object->error;
			$error++;
		}

		if (! $error)
		{



		}

		// End
		if (! $error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}

}
?>
