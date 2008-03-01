<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles  <ccomb@free.fr>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2007 Regis Houssin         <regis@dolibarr.fr>
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
	var $socid;
	var $element='facture_fourn';
	var $table_element='facture_fourn';
	
	//! 0=brouillon,
	//! 1=validée,
	//! TODO Ce statut doit etre 2 et non 1 classée payée partiellement (close_code='discount_vat','badcustomer') ou complètement (close_code=null),
	//! TODO Ce statut doit etre 2 et non 1 classée abandonnée et aucun paiement n'a eu lieu (close_code='badcustomer','abandon' ou 'replaced')
	var $statut;
	//! 1 si facture payée COMPLETEMENT, 0 sinon (ce champ ne devrait plus servir car insuffisant)
	var $paye;
	
	var $author;
	var $libelle;
	var $date;
	var $date_echeance;
	var $ref;
	var $amount;
	var $remise;
	var $tva;
	var $total_ht;
	var $total_tva;
	var $total_ttc;
	var $note;
	var $propalid;
	var $lignes;
	var $fournisseur;

	/**
	 *    \brief  Constructeur de la classe
	 *    \param  DB          	Handler accès base de données
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
	 *    \brief      Création de la facture en base
	 *    \param      user        object utilisateur qui crée
	 *    \return     int         id facture si ok, < 0 si erreur
	 */
	function create($user)
	{
		global $langs;

		$socid = $this->socid;
		$number = $this->ref;
		$amount = $this->amount;
		$remise = $this->remise;

		$this->db->begin();

		if (! $remise) $remise = 0 ;
		$totalht = ($amount - $remise);

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture_fourn (facnumber, libelle, fk_soc, datec, datef, note, fk_user_author, date_lim_reglement) ';
		$sql .= " VALUES ('".addslashes($number)."','".addslashes($this->libelle)."',";
		$sql .= $this->socid.", now(),'".$this->db->idate($this->date)."','".addslashes($this->note)."', ".$user->id.",'".$this->db->idate($this->date_echeance)."');";
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn');
			for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
			{
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture_fourn_det (fk_facture_fourn)';
				$sql .= ' VALUES ('.$this->id.');';
				$resql_insert=$this->db->query($sql);
				if ($resql_insert)
				{
					$idligne = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn_det');
					$this->updateline($idligne,
					$this->lignes[$i]->description,
					$this->lignes[$i]->pu_ht,
					$this->lignes[$i]->tva_taux,
					$this->lignes[$i]->qty);
				}
			}
			// Mise à jour prix
			if ($this->update_price($this->id) > 0)
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
	 *    	\brief      Recupére l'objet facture et ses lignes de factures
	 *    	\param      rowid       id de la facture a récupérer
	 *		\return     int         >0 si ok, <0 si ko
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT libelle, facnumber, amount, remise, '.$this->db->pdate(datef).'as df,';
		$sql.= ' total_ht, total_tva, total_ttc, fk_user_author,';
		$sql.= ' fk_statut, paye, f.note, f.note_public,';
		$sql.= ' '.$this->db->pdate('date_lim_reglement').'as de,';
		$sql.= ' s.nom as socnom, s.rowid as socid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f,'.MAIN_DB_PREFIX.'societe as s';
		$sql.= ' WHERE f.rowid='.$rowid.' AND f.fk_soc = s.rowid';

		dolibarr_syslog("FactureFourn::Fetch sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			if ($num)
			{
				$obj = $this->db->fetch_object($resql);

				$this->id            = $rowid;
				$this->datep         = $obj->df;
				$this->date_echeance = $obj->de;
				$this->ref           = $obj->facnumber;
				$this->libelle       = $obj->libelle;

				$this->remise        = $obj->remise;
				$this->socid        = $obj->socid;

				$this->total_ht  = $obj->total_ht;
				$this->total_tva = $obj->total_tva;
				$this->total_ttc = $obj->total_ttc;

				$this->author    = $obj->fk_user_author;

				$this->statut = $obj->fk_statut;
				$this->paye   = $obj->paye;

				$this->socnom = $obj->socnom;
				$this->note = $obj->note;
				$this->note_public = $obj->note_public;

				$this->db->free($resql);

				/*
				* Lignes
				*/
				$result=$this->fetch_lines();
				if ($result < 0)
				{
					$this->error=$this->db->error();
					dolibarr_syslog('Facture::Fetch Error '.$this->error);
					return -3;
				}
				return 1;
			}
			else
			{
				dolibarr_syslog('FactureFournisseur::Fetch rowid='.$rowid.' numrows=0 sql='.$sql);
				$this->error='Bill with id '.$rowid.' not found sql='.$sql;
				dolibarr_print_error($this->db);
				return -2;
			}
		}
		else
		{
			dolibarr_syslog('FactureFournisseur::Fetch rowid='.$rowid.' Erreur dans fetch de la facture fournisseur');
			$this->error=$this->db->error();
			dolibarr_print_error($this->db);
			return -1;
		}
	}

	
	/**
		\brief      Recupére les lignes de factures dans this->lignes
		\return     int         1 si ok, < 0 si erreur
	*/
	function fetch_lines()
	{
		$sql = 'SELECT f.rowid, f.description, f.pu_ht, f.pu_ttc, f.qty, f.tva_taux, f.tva';
		$sql.= ', f.total_ht, f.tva as total_tva, f.total_ttc, f.fk_product, f.product_type';
		$sql.= ', p.ref, p.label as label, p.description as product_desc';
		//$sql.= ', pf.ref_fourn';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn_det as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON f.fk_product = p.rowid';
		//$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_fournisseur as pf ON f.fk_product = pf.fk_product';
		$sql.= ' WHERE fk_facture_fourn='.$this->id;

		dolibarr_syslog("FactureFourn::fetch_lines sql=".$sql, LOG_DEBUG);
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
			dolibarr_syslog('FactureFournisseur::fetch_lines: Error '.$this->error,LOG_ERR);
			return -3;
		}
	}
	
	
	/**
	 * \brief     Recupére l'objet fournisseur lié à la facture
	 *
	 */
	function fetch_fournisseur()
	{
		$fournisseur = new Fournisseur($this->db);
		$fournisseur->fetch($this->socid);
		$this->fournisseur = $fournisseur;
	}

	/**
	 * \brief     Supprime la facture
	 * \param     rowid      id de la facture à supprimer
	 */
	function delete($rowid)
	{
		$this->db->begin();
		
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn_det WHERE fk_facture_fourn = '.$rowid.';';
		dolibarr_syslog("FactureFournisseur sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn WHERE rowid = '.$rowid.' AND fk_statut = 0';
				dolibarr_syslog("FactureFournisseur sql=".$sql);
				$resql2 = $this->db->query($sql);
				if ($resql2)
				{
					$this->db->commit();
					return 1;
				}
				else
				{
					$this->db->rollback();
					$this->error=$this->db->lasterror();
					dolibarr_syslog("FactureFournisseur::delete ".$this->error);
				}
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->lasterror();
			dolibarr_syslog("FactureFournisseur::delete ".$this->error);
		}
	}


	/**
	 *      \brief      Tag la facture comme payée complètement
	 *      \param      user        Objet utilisateur qui modifie l'état
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
			dolibarr_print_error($this->db);
            return -1;
		}
        return 1;
	}


	/**
	 *      \brief      Tag la facture comme validée
	 *      \param      user        Objet utilisateur qui valide la facture
     *      \return     int         <0 si ko, >0 si ok
	 */
	function set_valid($user)
	{
        $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn";
        $sql.= " SET fk_statut = 1, fk_user_valid = ".$user->id;
        $sql.= " WHERE rowid = ".$this->id;
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error=$this->db->error();
			dolibarr_print_error($this->db);
            return -1;
		}
        return 1;
	}


	/**
	* 		\brief     	Ajoute une ligne de facture (associé à aucun produit/service prédéfini)
	* 		\param    	desc            Description de la ligne
	* 		\param    	pu              Prix unitaire (HT ou TTC selon price_base_type)
	* 		\param    	txtva           Taux de tva forcé, sinon -1
	* 		\param    	qty             Quantité
	*		\param    	fk_product      Id du produit/service predéfini
	* 		\param    	remise_percent  Pourcentage de remise de la ligne
	* 		\param    	date_start      Date de debut de validité du service
	* 		\param    	date_end        Date de fin de validité du service
	* 		\param    	ventil          Code de ventilation comptable
	* 		\param    	info_bits		Bits de type de lignes
	* 		\param    	price_base_type HT ou TTC
	* 		\remarks	Les parametres sont deja censé etre juste et avec valeurs finales a l'appel
	*					de cette methode. Aussi, pour le taux tva, il doit deja avoir ete défini
	*					par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,taux_produit)
	*					et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
	*/
	function addline($desc, $pu, $txtva, $qty, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits='', $price_base_type='HT')
	{
		dolibarr_syslog("FactureFourn::Addline $desc,$pu,$qty,$txtva,$fk_product,$remise_percent,$date_start,$date_end,$ventil,$info_bits", LOG_DEBUG);
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

		$this->db->begin();

		// Nettoyage paramètres
		if ($txtva == '') $txtva=0;
		$txtva=price2num($txtva);
	

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture_fourn_det (fk_facture_fourn)';
		$sql .= ' VALUES ('.$this->id.');';
		dolibarr_syslog("Fournisseur.facture::addline sql=".$sql);

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$idligne = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn_det');

			$result=$this->updateline($idligne, $desc, $pu, $txtva, $qty, $fk_product, $price_base_type);
			if ($result > 0)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dolibarr_syslog("Error sql=$sql, error=".$this->error);
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
	 * \brief     Mets à jour une ligne de facture
	 * \param     id            	Id de la ligne de facture
	 * \param     label         	Description de la ligne
	 * \param     pu          		Prix unitaire (HT ou TTC selon price_base_type)
	 * \param     tauxtva       	Taux tva
	 * \param     qty           	Quantité
	 * \param     idproduct			Id produit
	 * \param	  price_base_type	HT ou TTC
	 * \param	  info_bits			Miscellanous informations of line
	 * \return    int           	<0 si ko, >0 si ok
	 */
	function updateline($id, $label, $pu, $tauxtva, $qty=1, $idproduct=0, $price_base_type='HT', $info_bits=0)
	{
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

		$pu = price2num($pu);
		$qty  = price2num($qty);

		// Validation
		if (! is_numeric($pu) || ! is_numeric($qty)) return -1;

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
		$product_type = 0;
		if ($idproduct)
		{
			$product=new Product($this->db);
			$result=$product->fetch($idproduct);
			$product_type=$product->type;
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

		dolibarr_syslog("Fournisseur.facture::updateline sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			// Mise a jour prix total facture
			return $this->update_price($this->id);
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("Fournisseur.facture::updateline error=".$this->error);
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
			dolibarr_print_error($this->db);
		}
		// Mise a jour prix facture
		$this->update_price($this->id);
		return 1;
	}

	/**
	 *    \brief      Mise à jour des sommes de la facture
	 *    \param      facid       id de la facture a modifier
	 *    \return     int         <0 si ko, >0 si ok
	 */
	function update_price($facid)
	{
		global $conf;
		
		$total_ht  = 0;
		$total_tva = 0;
		$total_ttc = 0;

		$sql = 'SELECT sum(total_ht), sum(tva), sum(total_ttc) FROM '.MAIN_DB_PREFIX.'facture_fourn_det';
		$sql .= ' WHERE fk_facture_fourn = '.$facid.';';
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				$row = $this->db->fetch_row();
				$total_ht  = $row[0];
				$total_tva = $row[1];
				$total_ttc = $row[2];
			}
			$this->db->free($resql);

			$total_ht  = $total_ht  != '' ? $total_ht  : 0;
			$total_tva = $total_tva != '' ? $total_tva : 0;
			$total_ttc = $total_ttc != '' ? $total_ttc : 0;

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn SET';
			$sql .= ' total_ht = '. price2num($total_ht,'MT');
			$sql .= ',total_tva = '.price2num($total_tva,'MT');
			$sql .= ',total_ttc = '.price2num($total_ttc,'MT');
			$sql .= ' WHERE rowid = '.$facid.';';
			dolibarr_syslog("Fournisseur.facture::update_price sql=".$sql);
			$resql2 = $this->db->query($sql);
			if ($resql2)
			{
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				return -2;
			}
		}
		else
		{
			dolibarr_print_error($this->db);
			return -1;
		}
	}


	/**
	 *      \brief     Charge les informations d'ordre info dans l'objet facture
	 *      \param     id       	Id de la facture a charger
	 */
	function info($id)
	{
		$sql = 'SELECT c.rowid, '.$this->db->pdate('datec').' as datec';
		$sql .= ', fk_user_author, fk_user_valid';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as c';
		$sql .= ' WHERE c.rowid = '.$id;

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
				//$this->date_validation   = $obj->datev; \todo La date de validation n'est pas encore gérée
			}
			$this->db->free($result);
		}
		else
		{
			dolibarr_print_error($this->db);
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

		$this->nbtodo=$this->nbtodolate=0;
		$sql = 'SELECT ff.rowid,'.$this->db->pdate('ff.date_lim_reglement').' as datefin';
		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as ff';
		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= ' WHERE ff.paye=0 AND ff.fk_statut > 0';
		if ($user->societe_id) $sql.=' AND ff.fk_soc = '.$user->societe_id;
		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= " AND ff.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nbtodo++;
				if ($obj->datefin < (time() - $conf->facture->fournisseur->warning_delay)) $this->nbtodolate++;
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			dolibarr_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
     *		\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *		\param		option			Sur quoi pointe le lien
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;
		
		$result='';
		
		$lien = '<a href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?facid='.$this->id.'">';
		$lienfin='</a>';

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowInvoice"),'bill').$lienfin.' ');
		$result.=$lien.$this->ref.$lienfin;
		return $result;
	}

	
	/**
	*		\brief		Initialise la facture avec valeurs fictives aléatoire
	*					Sert à générer une facture pour l'aperu des modèles ou demo
	*/
	function initAsSpecimen()
	{
		global $user,$langs;

		// Charge tableau des id de société socids
		$socids = array();
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE fournisseur=1 LIMIT 10";
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
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE envente=1";
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

		// Initialise paramètres
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
			$this->lignes[$xnbp]=$ligne;
			$xnbp++;
		}

		$this->amount_ht      = $xnbp*100;
		$this->total_ht       = $xnbp*100;
		$this->total_tva      = $xnbp*19.6;
		$this->total_ttc      = $xnbp*119.6;
	}	
}
?>
