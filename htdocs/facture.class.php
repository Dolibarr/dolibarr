<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier        <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2007 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
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
 */

/**
   \file       htdocs/facture.class.php
   \ingroup    facture
   \brief      Fichier de la classe des factures clients
   \version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT ."/product.class.php");
require_once(DOL_DOCUMENT_ROOT ."/client.class.php");

/**
   \class      Facture
   \brief      Classe permettant la gestion des factures clients
*/

class Facture extends CommonObject
{
	var $db;
	var $error;
	var $element='facture';
    var $table_element='facture';

  var $table;
  var $tabledetail;	
  var $id;
  //! Id client
  var $socid;
  //! Objet societe client (à charger par fetch_client)
  var $client;	
  var $number;
  var $author;
  var $date;
  var $ref;
  var $ref_client;
  //! 0=Facture normale, 1=Facture remplacement, 2=Facture avoir, 3=Facture récurrente
  var $type;
  var $amount;
  var $remise;
  var $tva;
  var $total;
  var $note;
  var $note_public;
  //! 0=brouillon,
  //! 1=validée,
  //! 2=classée payée partiellement (close_code='discount_vat','badcustomer') ou complètement (close_code=null),
  //! 3=classée abandonnée et aucun paiement n'a eu lieu (close_code='badcustomer','abandon' ou 'replaced')
  var $statut;
  //! 1 si facture payée COMPLETEMENT, 0 sinon (ce champ ne devrait plus servir car insuffisant)
  var $paye;
  //! id facture source si facture de remplacement ou avoir
  var $fk_facture_source;
  //! Fermeture apres paiement partiel: discount_vat, bad_customer, abandon
  //! Fermeture alors que aucun paiement: replaced (si remplacé), abandon
  var $close_code;	
  //! Commentaire si mis a paye sans paiement complet
  var $close_note;
  var $propalid;
  var $projetid;
  var $date_lim_reglement;
  var $cond_reglement_id;
  var $cond_reglement_code;
  var $mode_reglement_id;
  var $mode_reglement_code;
  var $modelpdf;
  var $products=array();
  var $lignes=array();	
  //! Pour board
  var $nbtodo;
  var $nbtodolate;
  var $specimen;
  //! Numero d'erreur de 512 à 1023
  var $errno = 0;
  /**
     \brief  Constructeur de la classe
     \param  DB         handler accès base de données
     \param  socid		id societe ('' par defaut)
     \param  facid      id facture ('' par defaut)
   */
  function Facture($DB, $socid='', $facid='')
  {
    $this->db = $DB;
    $this->table = 'facture';
    $this->tabledetail = 'facturedet';

    $this->id = $facid;
    $this->socid = $socid;

    $this->amount = 0;
    $this->remise = 0;
    $this->remise_percent = 0;
    $this->tva = 0;
    $this->total = 0;
    $this->propalid = 0;
    $this->projetid = 0;
    $this->remise_exceptionnelle = 0;
  }

	/**
		\brief     	Création de la facture en base
		\param     	user       		Object utilisateur qui crée
	    \param      notrigger		1 ne declenche pas les triggers, 0 sinon
		\return		int				<0 si ko, >0 si ok
	*/
	function create($user,$notrigger=0)
	{
		global $langs,$conf,$mysoc;

		// Nettoyage paramètres
		if (! $this->type) $this->type = 0;
		$this->ref_client=trim($this->ref_client);
		$this->note=trim($this->note);
		$this->note_public=trim($this->note_public);
		if (! $this->remise) $this->remise = 0;
		if (! $this->mode_reglement_id) $this->mode_reglement_id = 0;
		$this->brouillon = 1;

		dolibarr_syslog("Facture::Create user=".$user->id);

		$soc = new Societe($this->db);
		$soc->fetch($this->socid);

		$error=0;
		
		$this->db->begin();

		// Facture récurrente
		if ($this->fac_rec > 0)
		{
			require_once(DOL_DOCUMENT_ROOT.'/compta/facture/facture-rec.class.php');
			$_facrec = new FactureRec($this->db, $this->fac_rec);
			$result=$_facrec->fetch($this->fac_rec);

			$this->projetid          = $_facrec->projetid;
			$this->cond_reglement    = $_facrec->cond_reglement_id;
			$this->cond_reglement_id = $_facrec->cond_reglement_id;
			$this->mode_reglement    = $_facrec->mode_reglement_id;
			$this->mode_reglement_id = $_facrec->mode_reglement_id;
			$this->amount            = $_facrec->amount;
			$this->remise_absolue    = $_facrec->remise_absolue;
			$this->remise_percent    = $_facrec->remise_percent;
			$this->remise		     = $_facrec->remise;
			
			// Nettoyage parametres
			if (! $this->type) $this->type = 0;
			$this->ref_client=trim($this->ref_client);
			$this->note=trim($this->note);
			$this->note_public=trim($this->note_public);
			if (! $this->remise) $this->remise = 0;
			if (! $this->mode_reglement_id) $this->mode_reglement_id = 0;
			$this->brouillon = 1;
		}

		// Definition de la date limite
		$datelim=$this->calculate_date_lim_reglement();

		// Insertion dans la base
		$socid  = $this->socid;
		$amount = $this->amount;
		$remise = $this->remise;

		$totalht = ($amount - $remise);

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture (';
		$sql.= ' facnumber, type, fk_soc, datec, amount, remise_absolue, remise_percent,';
		$sql.= ' datef,';
		$sql.= ' note,';
		$sql.= ' note_public,';
		$sql.= ' ref_client,';
		$sql.= ' fk_facture_source, fk_user_author, fk_projet,';
		$sql.= ' fk_cond_reglement, fk_mode_reglement, date_lim_reglement, model_pdf)';
		$sql.= ' VALUES (';
		$sql.= "'(PROV)', '".$this->type."', '$socid', now(), '$totalht', '".$this->remise_absolue."'";
		$sql.= ",'".$this->remise_percent."', ".$this->db->idate($this->date);
		$sql.= ",".($this->note?"'".addslashes($this->note)."'":"null");
		$sql.= ",".($this->note_public?"'".addslashes($this->note_public)."'":"null");
		$sql.= ",".($this->ref_client?"'".addslashes($this->ref_client)."'":"null");
		$sql.= ",".($this->fk_facture_source?"'".addslashes($this->fk_facture_source)."'":"null");
		$sql.= ",".$user->id;
		$sql.= ",".($this->projetid?$this->projetid:"null");
		$sql.= ','.$this->cond_reglement_id;
		$sql.= ",".$this->mode_reglement_id;
		$sql.= ",".$this->db->idate($datelim).", '".$this->modelpdf."')";

		dolibarr_syslog("Facture::Create sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture');

			$this->ref='(PROV'.$this->id.')';
			$sql = 'UPDATE '.MAIN_DB_PREFIX."facture SET facnumber='".$this->ref."' WHERE rowid=".$this->id;

			dolibarr_syslog("Facture::create sql=".$sql);
			$resql=$this->db->query($sql);
			if (! $resql) $error++;
			
			// Mise a jour lien avec propal ou commande
			if (! $error && $this->id && $this->propalid)
			{
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'fa_pr (fk_facture, fk_propal) VALUES ('.$this->id.','.$this->propalid.')';
				dolibarr_syslog("Facture::Create sql=".$sql);
				$resql=$this->db->query($sql);
				if (! $resql) $error++;
			}
			if (! $error && $this->id && $this->commandeid)
			{
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'co_fa (fk_facture, fk_commande) VALUES ('.$this->id.','.$this->commandeid.')';
				dolibarr_syslog("Facture::Create sql=".$sql);
				$resql=$this->db->query($sql);
				if (! $resql) $error++;
			}

			/*
			*  Insertion du detail des produits dans la base,
			*  si tableau products défini.
			*/
			for ($i = 0 ; $i < sizeof($this->products) ; $i++)
			{
				$result = $this->addline(
					$this->id,
					$this->products[$i]->desc,
					$this->products[$i]->subprice,
					$this->products[$i]->qty,
					$this->products[$i]->tva_tx,
					$this->products[$i]->fk_product,
					$this->products[$i]->remise_percent,
					$this->products[$i]->date_start,
					$this->products[$i]->date_end
					);

				if ($result < 0)
				{
					$error++;
					break;
				}
			}

			/*
			* Produits de la facture récurrente
			*/
			if (! $error && $this->fac_rec > 0)
			{
				for ($i = 0 ; $i < sizeof($_facrec->lignes) ; $i++)
				{
					if ($_facrec->lignes[$i]->produit_id)
					{
						$prod = new Product($this->db, $_facrec->lignes[$i]->produit_id);
						$res=$prod->fetch($_facrec->lignes[$i]->produit_id);
					}
					$tva_tx = get_default_tva($mysoc,$soc,($prod->tva_tx?$prod->tva_tx:0));

					$result_insert = $this->addline(
					$this->id,
					$_facrec->lignes[$i]->desc,
					$_facrec->lignes[$i]->subprice,
					$_facrec->lignes[$i]->qty,
					$tva_tx,
					$_facrec->lignes[$i]->produit_id,
					$_facrec->lignes[$i]->remise_percent);

					if ( $result_insert < 0)
					{
						$error++;
						$this->error=$this->db->error();
						break;
					}
				}
			}

			if (! $error)
			{
				$resql=$this->update_price($this->id);
				if ($resql)
				{
					// Appel des triggers
					include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
					$interface=new Interfaces($this->db);
					$result=$interface->run_triggers('BILL_CREATE',$this,$user,$langs,$conf);
					// Fin appel triggers

					$this->db->commit();
					return $this->id;
				}
				else
				{
					$this->db->rollback();
					return -3;
				}
			}
			else
			{
				dolibarr_syslog("Facture::create error ".$this->error);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("Facture::create error ".$this->error." sql=".$sql);
			$this->db->rollback();
			return -1;
		}
	}


  /**
     \brief      Création de la facture en base depuis une autre
     \param      user    Object utilisateur qui crée
     \return	 int				<0 si ko, >0 si ok
  */
  function create_clone($user,$invertdetail=0)
  {
    // Charge facture source
    $facture=new Facture($this->db);

    $facture->fk_facture_source = $this->fk_facture_source;
    $facture->type 			    = $this->type;
    $facture->socid 		    = $this->socid;
    $facture->date              = $this->date;
    $facture->note_public       = $this->note_public;
    $facture->note              = $this->note;
    $facture->ref_client        = $this->ref_client;
    $facture->modelpdf          = $this->modelpdf;
    $facture->projetid          = $this->projetid;
    $facture->cond_reglement_id = $this->cond_reglement_id;
    $facture->mode_reglement_id = $this->mode_reglement_id;
    $facture->amount            = $this->amount;
    $facture->remise_absolue    = $this->remise_absolue;
    $facture->remise_percent    = $this->remise_percent;
    $facture->lignes		    = $this->lignes;	// Tableau des lignes de factures
    $facture->products		    = $this->lignes;	// Tant que products encore utilisé

    if ($invertdetail)
      {
	foreach($facture->lignes as $i => $line)
	  {
	    $facture->lignes[$i]->subprice  = -$facture->lignes[$i]->subprice;
	    $facture->lignes[$i]->price     = -$facture->lignes[$i]->price;
	    $facture->lignes[$i]->total_ht  = -$facture->lignes[$i]->total_ht;
	    $facture->lignes[$i]->total_tva = -$facture->lignes[$i]->total_tva;
	    $facture->lignes[$i]->total_ttc = -$facture->lignes[$i]->total_ttc;
	  }
      }
				
    dolibarr_syslog("Facture::create_clone invertdetail=".$invertdetail." socid=".$this->socid." nboflines=".sizeof($facture->lignes));
		

    $facid = $facture->create($user);

    return $facid;
  }		
			
			
  /**
     \brief      Renvoie nom clicable (avec eventuellement le picto)
     \param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
     \param		option			Sur quoi pointe le lien
     \return		string			Chaine avec URL
   */
  function getNomUrl($withpicto=0,$option='')
  {
    global $langs;
		
    $result='';
		
    $lien = '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$this->id.'">';
    $lienfin='</a>';
		
    $picto='bill';
    if ($this->type == 1) $picto.='r';
    if ($this->type == 2) $picto.='a';

    $label=$langs->trans("ShowInvoice").': '.$this->ref;
    if ($this->type == 1) $label=$langs->trans("ShowInvoiceReplace").': '.$this->ref;
    if ($this->type == 2) $label=$langs->trans("ShowInvoiceAvoir").': '.$this->ref;
		
    if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
    if ($withpicto && $withpicto != 2) $result.=' ';
    $result.=$lien.$this->ref.$lienfin;
    return $result;
  }
	
	
	/**
		\brief      Recupére l'objet facture et ses lignes de factures
		\param      rowid       id de la facture a récupérer
		\param      societe_id  id de societe
		\return     int         >0 si ok, <0 si ko
	*/
	function fetch($rowid, $societe_id=0)
	{
		dolibarr_syslog("Facture::Fetch rowid=".$rowid.", societe_id=".$societe_id, LOG_DEBUG);

		$sql = 'SELECT f.facnumber,f.ref_client,f.type,f.fk_soc,f.amount,f.tva,f.total,f.total_ttc,f.remise_percent,f.remise_absolue,f.remise';
		$sql.= ','.$this->db->pdate('f.datef').' as df, f.fk_projet';
		$sql.= ','.$this->db->pdate('f.date_lim_reglement').' as dlr';
		$sql.= ', f.note, f.note_public, f.fk_statut, f.paye, f.close_code, f.close_note, f.fk_user_author, f.model_pdf';
		$sql.= ', f.fk_facture_source';
		$sql.= ', f.fk_mode_reglement, f.fk_cond_reglement';
		$sql.= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
		$sql.= ', c.code as cond_reglement_code, c.libelle as cond_reglement_libelle, c.libelle_facture as cond_reglement_libelle_facture';
		$sql.= ', cf.fk_commande';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'cond_reglement as c ON f.fk_cond_reglement = c.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON f.fk_mode_reglement = p.id';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'co_fa as cf ON cf.fk_facture = f.rowid';
		$sql.= ' WHERE f.rowid='.$rowid;
		if ($societe_id > 0)
		{
			$sql.= ' AND f.fk_soc = '.$societe_id;
		}
		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id                     = $rowid;
				$this->ref                    = $obj->facnumber;
				$this->ref_client             = $obj->ref_client;
				$this->type                   = $obj->type;
				$this->date                   = $obj->df;
				$this->amount                 = $obj->amount;
				$this->remise_percent         = $obj->remise_percent;
				$this->remise_absolue         = $obj->remise_absolue;
				$this->remise                 = $obj->remise;
				$this->total_ht               = $obj->total;
				$this->total_tva              = $obj->tva;
				$this->total_ttc              = $obj->total_ttc;
				$this->paye                   = $obj->paye;
				$this->close_code             = $obj->close_code;
				$this->close_note             = $obj->close_note;
				$this->socid                  = $obj->fk_soc;
				$this->statut                 = $obj->fk_statut;
				$this->date_lim_reglement     = $obj->dlr;
				$this->mode_reglement_id      = $obj->fk_mode_reglement;
				$this->mode_reglement_code    = $obj->mode_reglement_code;
				$this->mode_reglement         = $obj->mode_reglement_libelle;
				$this->cond_reglement_id      = $obj->fk_cond_reglement;
				$this->cond_reglement_code    = $obj->cond_reglement_code;
				$this->cond_reglement         = $obj->cond_reglement_libelle;
				$this->cond_reglement_facture = $obj->cond_reglement_libelle_facture;
				$this->projetid               = $obj->fk_projet;
				$this->fk_facture_source      = $obj->fk_facture_source;
				$this->note                   = $obj->note;
				$this->note_public            = $obj->note_public;
				$this->user_author            = $obj->fk_user_author;
				$this->modelpdf               = $obj->model_pdf;
				$this->commande_id            = $obj->fk_commande;
				$this->lignes                 = array();

				if ($this->commande_id)
				{
					$sql = "SELECT ref";
					$sql.= " FROM ".MAIN_DB_PREFIX."commande";
					$sql.= " WHERE rowid = ".$this->commande_id;

					$resqlcomm = $this->db->query($sql);

					if ($resqlcomm)
					{
						$objc = $this->db->fetch_object($resqlcomm);
						$this->commande_ref = $objc->ref;
						$this->db->free($resqlcomm);
					}
				}

				if ($this->statut == 0)	$this->brouillon = 1;

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
				$this->error='Bill with id '.$rowid.' not found sql='.$sql;
				dolibarr_syslog('Facture::Fetch Error '.$this->error);
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog('Facture::Fetch Error '.$this->error);
			return -1;
		}
	}


	/**
		\brief      Recupére les lignes de factures dans this->lignes
		\return     int         1 si ok, < 0 si erreur
	*/
	function fetch_lines()
	{
		$sql = 'SELECT l.rowid, l.fk_product, l.description, l.price, l.qty, l.tva_taux, ';
		$sql.= ' l.remise, l.remise_percent, l.fk_remise_except, l.subprice,';
		$sql.= ' '.$this->db->pdate('l.date_start').' as date_start,'.$this->db->pdate('l.date_end').' as date_end,';
		$sql.= ' l.info_bits, l.total_ht, l.total_tva, l.total_ttc, l.fk_code_ventilation, l.fk_export_compta,';
		$sql.= ' p.label as label, p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet as l';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
		$sql.= ' WHERE l.fk_facture = '.$this->id;
		$sql.= ' ORDER BY l.rang';

		dolibarr_syslog('Facture::fetch_lines', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);
				$faclig = new FactureLigne($this->db);
				$faclig->rowid	          = $objp->rowid;
				$faclig->desc             = $objp->description;     // Description ligne
				$faclig->libelle          = $objp->label;           // Label produit
				$faclig->product_desc     = $objp->product_desc;    // Description produit
				$faclig->qty              = $objp->qty;
				$faclig->subprice         = $objp->subprice;
				$faclig->tva_tx           = $objp->tva_taux;
				$faclig->remise_percent   = $objp->remise_percent;
				$faclig->fk_remise_except = $objp->fk_remise_except;
				$faclig->produit_id       = $objp->fk_product;
				$faclig->fk_product       = $objp->fk_product;
				$faclig->date_start       = $objp->date_start;
				$faclig->date_end         = $objp->date_end;
				$faclig->date_start       = $objp->date_start;
				$faclig->date_end         = $objp->date_end;
				$faclig->info_bits        = $objp->info_bits;
				$faclig->total_ht         = $objp->total_ht;
				$faclig->total_tva        = $objp->total_tva;
				$faclig->total_ttc        = $objp->total_ttc;
				$faclig->export_compta    = $objp->fk_export_compta;
				$faclig->code_ventilation = $objp->fk_code_ventilation;

				// Ne plus utiliser
				$faclig->price            = $objp->price;
				$faclig->remise           = $objp->remise;

				$this->lignes[$i] = $faclig;
				$i++;
			}
			$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog('Facture::fetch_lines: Error '.$this->error);
			return -3;
		}
	}

	/**
	*    \brief     Ajout en base d'une ligne remise fixe en ligne de facture
	*    \param     idremise			Id de la remise fixe
	*    \return    int          		>0 si ok, <0 si ko
	*/
	function insert_discount($idremise)
	{
		global $langs;

		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');
		include_once(DOL_DOCUMENT_ROOT.'/discount.class.php');

		$this->db->begin();

		$remise=new DiscountAbsolute($this->db);
		$result=$remise->fetch($idremise);
		
		if ($result > 0)
		{
			if ($remise->fk_facture)	// Protection against multiple submission
			{
				$this->error=$langs->trans("ErrorDiscountAlreadyUsed");
				$this->db->rollback();
				return -5;
			}
			
			$facligne=new FactureLigne($this->db);
			$facligne->fk_facture=$this->id;
			$facligne->fk_remise_except=$remise->id;
			$facligne->desc=$remise->description;   	// Description ligne
			$facligne->tva_tx=$remise->tva_tx;
			$facligne->subprice=-$remise->amount_ht;
			$facligne->fk_product=0;					// Id produit prédéfini
			$facligne->qty=1;
			$facligne->remise_percent=0;
			$facligne->rang=-1;
			$facligne->info_bits=2;

			// Ne plus utiliser
			$facligne->price=-$remise->amount_ht;
			$facligne->remise=0;

			$facligne->total_ht  = -$remise->amount_ht;
			$facligne->total_tva = -$remise->amount_tva;
			$facligne->total_ttc = -$remise->amount_ttc;

			$lineid=$facligne->insert();
			if ($lineid > 0)
			{
				$result=$this->update_price($this->id);
				if ($result > 0)
				{
					// Crée lien entre remise et ligne de facture
					$result=$remise->link_to_invoice($lineid,0);
					if ($result < 0)
					{
						$this->error=$remise->error;
						$this->db->rollback();
						return -4;
					}

					$this->db->commit();
					return 1;
				}
				else
				{
					$this->error=$facligne->error;
					$this->db->rollback();
					return -1;
				}
			}
			else
			{
				$this->error=$facligne->error;
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->db->rollback();
			return -3;
		}
	}


  /**
   *      \brief     Classe la facture dans un projet
   *      \param     projid       Id du projet dans lequel classer la facture
   */
  function classin($projid)
  {
    $sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
    if ($projid) $sql.= ' SET fk_projet = '.$projid;
    else $sql.= ' SET fk_projet = NULL';
    $sql.= ' WHERE rowid = '.$this->id;
    if ($this->db->query($sql))
      {
	return 1;
      }
    else
      {
	dolibarr_print_error($this->db);
	return -1;
      }
  }

  function set_ref_client($ref_client)
  {
    $sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
    if (empty($ref_client))
      $sql .= ' SET ref_client = NULL';
    else
      $sql .= ' SET ref_client = \''.addslashes($ref_client).'\'';
    $sql .= ' WHERE rowid = '.$this->id;
    if ($this->db->query($sql))
      {
	$this->ref_client = $ref_client;
	return 1;
      }
    else
      {
	dolibarr_print_error($this->db);
	return -1;
      }
  }

	/**
		\brief     	Supprime la facture
		\param     	rowid      	Id de la facture à supprimer
		\return		int			<0 si ko, >0 si ok
	*/
	function delete($rowid=0)
	{
		global $user,$langs,$conf;

		if (! $rowid) $rowid=$this->id;

		dolibarr_syslog("Facture::delete rowid=".$rowid, LOG_DEBUG);
		
		$this->db->begin();

		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_tva_sum WHERE fk_facture = '.$rowid;
		if ($this->db->query($sql))
		{
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'fa_pr WHERE fk_facture = '.$rowid;
			if ($this->db->query($sql))
			{
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'co_fa WHERE fk_facture = '.$rowid;
				if ($this->db->query($sql))
				{
					// On met a jour le lien des remises
					$list_rowid_det=array();
					$sql = 'SELECT fd.rowid FROM '.MAIN_DB_PREFIX.'facturedet as fd WHERE fk_facture = '.$rowid;
					$resql=$this->db->query($sql);
					while ($obj = $this->db->fetch_object($resql))
					{
						$list_rowid_det[]=$obj->rowid;
					}
					
					// On désaffecte de la facture les remises liées
					if (sizeof($list_rowid_det))
					{
						$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except as re';
						$sql.= ' SET re.fk_facture = NULL';
						$sql.= ' WHERE re.fk_facture in ('.join(',',$list_rowid_det).')';

						dolibarr_syslog("Facture.class::delete sql=".$sql);
						if (! $this->db->query($sql))
						{
							$this->error=$this->db->error()." sql=".$sql;
							dolibarr_syslog("Facture.class::delete ".$this->error);
							$this->db->rollback();
							return -5;
						}
					}
					
					$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facturedet WHERE fk_facture = '.$rowid;
					if ($this->db->query($sql))
					{
						$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture WHERE rowid = '.$rowid;
						$resql=$this->db->query($sql);
						if ($resql)
						{
							// Appel des triggers
							include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
							$interface=new Interfaces($this->db);
							$result=$interface->run_triggers('BILL_DELETE',$this,$user,$langs,$conf);
							// Fin appel triggers
							
							$this->db->commit();
							return 1;
						}
						else
						{
							$this->error=$this->db->error()." sql=".$sql;
							dolibarr_syslog("Facture.class::delete ".$this->error);
							$this->db->rollback();
							return -6;
						}
					}
					else
					{
						$this->error=$this->db->error()." sql=".$sql;
						dolibarr_syslog("Facture.class::delete ".$this->error);
						$this->db->rollback();
						return -4;
					}
				}
				else
				{
					$this->error=$this->db->error()." sql=".$sql;
					dolibarr_syslog("Facture.class::delete ".$this->error);
					$this->db->rollback();
					return -3;
				}
			}
			else
			{
				$this->error=$this->db->error()." sql=".$sql;
				dolibarr_syslog("Facture.class::delete ".$this->error);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			dolibarr_syslog("Facture.class::delete ".$this->error);
			$this->db->rollback();
			return -1;
		}
	}


  /**
     \brief      Renvoi une date limite de reglement de facture en fonction des
     conditions de reglements de la facture et date de facturation
     \param      cond_reglement_id   Condition de reglement à utiliser, 0=Condition actuelle de la facture
     \return     date                Date limite de réglement si ok, <0 si ko
   */
  function calculate_date_lim_reglement($cond_reglement_id=0)
  {
    if (! $cond_reglement_id)
      $cond_reglement_id=$this->cond_reglement_id;
    $sqltemp = 'SELECT c.fdm,c.nbjour,c.decalage';
    $sqltemp.= ' FROM '.MAIN_DB_PREFIX.'cond_reglement as c';
    $sqltemp.= ' WHERE c.rowid='.$cond_reglement_id;
    $resqltemp=$this->db->query($sqltemp);
    if ($resqltemp)
      {
	if ($this->db->num_rows($resqltemp))
	  {
	    $obj = $this->db->fetch_object($resqltemp);
	    $cdr_nbjour = $obj->nbjour;
	    $cdr_fdm = $obj->fdm;
	    $cdr_decalage = $obj->decalage;
	  }
      }
    else
      {
	$this->error=$this->db->error();
	return -1;
      }
    $this->db->free($resqltemp);

    /* Definition de la date limite */

    // 1 : ajout du nombre de jours
    $datelim = $this->date + ( $cdr_nbjour * 3600 * 24 );

    // 2 : application de la règle "fin de mois"
    if ($cdr_fdm)
      {
	$mois=date('m', $datelim);
	$annee=date('Y', $datelim);
	if ($mois == 12)
	  {
	    $mois = 1;
	    $annee += 1;
	  }
	else
	  {
	    $mois += 1;
	  }
	// On se déplace au début du mois suivant, et on retire un jour
	$datelim=mktime(12,0,0,$mois,1,$annee);
	$datelim -= (3600 * 24);
      }

    // 3 : application du décalage
    $datelim += ( $cdr_decalage * 3600 * 24);

    return $datelim;
  }

	/**
	*      \brief      Tag la facture comme payée complètement (close_code non renseigné) ou partiellement (close_code renseigné) + appel trigger BILL_PAYED
	*      \param      user      	Objet utilisateur qui modifie
	*	   \param      close_code	Code renseigné si on classe à payée complètement alors que paiement incomplet (cas ecompte par exemple)
	*	   \param      close_note	Commentaire renseigné si on classe à payée alors que paiement incomplet (cas ecompte par exemple)
	*      \return     int         	<0 si ok, >0 si ok
	*/
	function set_payed($user,$close_code='',$close_note='')
	{
		global $conf,$langs;

		dolibarr_syslog("Facture::set_payed rowid=".$this->id, LOG_DEBUG);
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture SET';
		$sql.= ' fk_statut=2';
		if (! $close_code) $sql.= ', paye=1';
		if ($close_code) $sql.= ", close_code='".addslashes($close_code)."'";
		if ($close_note) $sql.= ", close_note='".addslashes($close_note)."'";
		$sql.= ' WHERE rowid = '.$this->id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->use_webcal=($conf->global->PHPWEBCALENDAR_BILLSTATUS=='always'?1:0);

			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('BILL_PAYED',$this,$user,$langs,$conf);
			// Fin appel triggers
		}

		return 1;
	}


	/**
	*      \brief      Tag la facture comme non payée complètement + appel trigger BILL_UNPAYED
	*				   Fonction utilisée quand un paiement prélevement est refusé.
	*      \param      user        Objet utilisateur qui modifie
	*      \return     int         <0 si ok, >0 si ok
	*/
	function set_unpayed($user)
	{
		global $conf,$langs;

		dolibarr_syslog("Facture::set_unpayed rowid=".$this->id, LOG_DEBUG);
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
		$sql.= ' SET paye=0, fk_statut=1';
		$sql.= ' WHERE rowid = '.$this->id;
		$resql = $this->db->query($sql);

		if ($resql)
		{
			$this->use_webcal=($conf->global->PHPWEBCALENDAR_BILLSTATUS=='always'?1:0);

			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('BILL_UNPAYED',$this,$user,$langs,$conf);
			// Fin appel triggers
		}

		return 1;
	}


	/**
		\brief      Tag la facture comme abandonnée, sans paiement dessus (exemple car facture de remplacement) + appel trigger BILL_CANCEL
		\param      user        Objet utilisateur qui modifie
		\param		close_code	Code de fermeture
		\param		close_note	Commentaire de fermeture
		\return     int         <0 si ok, >0 si ok
	*/
	function set_canceled($user,$close_code='',$close_note='')
	{
		global $conf,$langs;

		dolibarr_syslog("Facture::set_canceled rowid=".$this->id, LOG_DEBUG);

		$this->db->begin();
		
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture SET';
		$sql.= ' fk_statut=3';
		if ($close_code) $sql.= ", close_code='".addslashes($close_code)."'";
		if ($close_note) $sql.= ", close_note='".addslashes($close_note)."'";
		$sql.= ' WHERE rowid = '.$this->id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			// On désaffecte de la facture les remises liées
			// car elles n'ont pas été utilisées vu que la facture est abandonnée.
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except';
			$sql.= ' SET fk_facture = NULL WHERE fk_facture = '.$this->id;
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->use_webcal=($conf->global->PHPWEBCALENDAR_BILLSTATUS=='always'?1:0);

				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('BILL_CANCEL',$this,$user,$langs,$conf);
				// Fin appel triggers
				
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->error=$this->db->error()." sql=".$sql;
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -2;
		}
	}

	/**
	*      	\brief     	Tag la facture comme validée + appel trigger BILL_VALIDATE
	*      	\param     	rowid           Id de la facture à valider
	*      	\param     	user            Utilisateur qui valide la facture
	*      	\param     	soc             Ne sert plus \\TODO A virer
	*      	\param     	force_number	Référence à forcer de la facture
	*		\return		int				<0 si ko, >0 si ok
	*/
	function set_valid($rowid, $user, $soc='', $force_number='')
	{
		global $conf,$langs;

		$error = 0;
		if ($this->brouillon)
		{
			$this->db->begin();

			$this->fetch_client();

			// Verification paramètres
			if ($this->type == 1)		// si facture de remplacement
			{
				// Controle que facture source connue
				if ($this->fk_facture_source <= 0)
				{
					$this->error=$langs->trans("ErrorFieldRequired",$langs->trans("InvoiceReplacement"));
					$this->db->rollback();
					return -10;
				}
				
				// Charge la facture source a remplacer
				$facreplaced=new Facture($this->db);
				$result=$facreplaced->fetch($this->fk_facture_source);
				if ($result <= 0)
				{
					$this->error=$langs->trans("ErrorBadInvoice");
					$this->db->rollback();
					return -11;
				}
				
				// Controle que facture source non deja remplacee par une autre
				$idreplacement=$facreplaced->getIdReplacingInvoice('validated');
				if ($idreplacement && $idreplacement != $rowid)
				{
					$facreplacement=new Facture($this->db);
					$facreplacement->fetch($idreplacement);
					$this->error=$langs->trans("ErrorInvoiceAlreadyReplaced",$facreplaced->ref,$facreplacement->ref);
					$this->db->rollback();
					return -12;
				}
				
				$result=$facreplaced->set_canceled($user,'replaced','');
				if ($result < 0)
				{
					$this->error=$facreplaced->error." sql=".$sql;
					$this->db->rollback();
					return -13;
				}
			}


			// on vérifie si la facture est en numérotation provisoire
			$facref = substr($this->ref, 1, 4);

			if ($force_number)
			{
				$numfa = $force_number;
			}
			else if ($facref == 'PROV')
			{	
				$numfa = $this->getNextNumRef($this->client);
			}
			else
			{
				$numfa = $this->ref;
			}

			$this->update_price($this->id);

			// Validation de la facture
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture ';
			$sql.= " SET facnumber='".$numfa."', fk_statut = 1, fk_user_valid = ".$user->id;
			if ($conf->global->FAC_FORCE_DATE_VALIDATION)
			{
				// Si l'option est activée, on force la date de facture
				$this->date=time();
				$datelim=$this->calculate_date_lim_reglement();
				$sql.= ', datef='.$this->db->idate($this->date);
				$sql.= ', date_lim_reglement='.$this->db->idate($datelim);
			}
			$sql.= ' WHERE rowid = '.$rowid;
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->facnumber=$numfa;
				dolibarr_syslog("Facture::set_valid() sql=$sql");
			}
			else
			{
				dolibarr_syslog("Facture::set_valid() Echec update - 10 - sql=$sql");
				dolibarr_print_error($this->db);
				$error++;
			}


			// On vérifie si la facture était une provisoire
			if ($facref == 'PROV')
			{
				// On renomme repertoire facture ($this->ref = ancienne ref, $numfa = nouvelle ref)
				// afin de ne pas perdre les fichiers attachés
				$facref = sanitize_string($this->ref);
				$snumfa = sanitize_string($numfa);
				$dirsource = $conf->facture->dir_output.'/'.$facref;
				$dirdest = $conf->facture->dir_output.'/'.$snumfa;
				if (file_exists($dirsource))
				{
					dolibarr_syslog("Facture::set_valid() renommage rep ".$dirsource." en ".$dirdest);
					
					if (@rename($dirsource, $dirdest))
					{
						dolibarr_syslog("Renommage ok");
						// Suppression ancien fichier PDF dans nouveau rep
						dol_delete_file($conf->facture->dir_output.'/'.$snumfa.'/'.$facref.'.*');
					}
				}
			}

			// On vérifie si la facture était une provisoire
			if (! $error && $facref == 'PROV')
			{
				$this->fetch_lines();

				// La vérif qu'une remise n'est pas utilisée 2 fois est faite au moment de l'insertion de ligne

				// On met a jour table des ventes
				// On crée ici une denormalisation pas forcement utilisé !!!
				// TODO Virer utilisation du champ nbvente si utilisation non justifié
				foreach($this->lignes as $i => $line)
				{
					if ($line->fk_product)
					{
						$sql = 'UPDATE '.MAIN_DB_PREFIX.'product SET nbvente=nbvente+1 WHERE rowid = '.$line->rowid;
						$resql2 = $this->db->query($sql);
						$i++;
					}
				}
			}

			if (! $error)
			{
				// Classe la société rattachée comme client
				$result=$this->client->set_as_client();
				
				// Si activé on décrémente le produit principal et ses composants à la validation de facture
				if($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_BILL)
				{
					require_once(DOL_DOCUMENT_ROOT."/product/stock/mouvementstock.class.php");
					
					for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
					{
						if ($conf->global->PRODUIT_SOUSPRODUITS)
						{
							$prod = new Product($this->db, $this->lignes[$i]->fk_product);
							$prod -> get_sousproduits_arbo();
							$prods_arbo = $prod->get_each_prod();
							if(sizeof($prods_arbo) > 0)
							{
								foreach($prods_arbo as $key => $value)
								{
									// on décompte le stock de tous les sousproduits
									$mouvS = new MouvementStock($this->db);
									$entrepot_id = "1"; //Todo: ajouter possibilité de choisir l'entrepot
									$result=$mouvS->livraison($user, $value[1], $entrepot_id, $value[0]*$this->lignes[$i]->qty);
								}
		    	    }
		        }
		        $mouvP = new MouvementStock($this->db);
		        // on décompte le stock du produit principal
		        $entrepot_id = "1"; // TODO ajouter possibilité de choisir l'entrepot
		        $result=$mouvP->livraison($user, $this->lignes[$i]->fk_product, $entrepot_id, $this->lignes[$i]->qty);
		      }
		    }

				$this->ref = $numfa;

				$this->use_webcal=($conf->global->PHPWEBCALENDAR_BILLSTATUS=='always'?1:0);

				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('BILL_VALIDATE',$this,$user,$langs,$conf);
				// Fin appel triggers

				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				return -1;
			}
		}
	}

	/**
	*		\brief		Set draft status
	*		\param		userid		Id user setting
	*/
	function set_draft($userid)
	{
		dolibarr_syslog("Facture::set_draft rowid=".$this->id, LOG_DEBUG);

		$sql = "UPDATE ".MAIN_DB_PREFIX."facture SET fk_statut = 0";
		$sql.= " WHERE rowid = ".$this->id;

		if ($this->db->query($sql))
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}


  /**
   *		\brief		Positionne modele derniere generation
   *		\param		user		Objet use qui modifie
   *		\param		modelpdf	Nom du modele
   */
  function set_pdf_model($user, $modelpdf)
  {
    if ($user->rights->facture->creer)
      {
	$sql = "UPDATE ".MAIN_DB_PREFIX."facture SET model_pdf = '$modelpdf'";
	$sql .= " WHERE rowid = $this->id AND fk_statut < 2 ;";

	if ($this->db->query($sql) )
	  {
	    $this->modelpdf=$modelpdf;
	    return 1;
	  }
	else
	  {
	    dolibarr_print_error($this->db);
	    return 0;
	  }
      }
  }

	/**
		\brief   	Ajoute une ligne dans le tableau products
		\param    	idproduct		Id du produit a ajouter
		\param    	qty			Quantit
		\param    	remise_percent		Remise relative effectuée sur le produit
		\param    	date_start
		\param    	date_end
		\return   	void
		\remarks	$this->client doit etre charg
		\TODO		Remplacer les appels a cette fonction par generation objet Ligne
					inséré dans tableau $this->products
	*/
	function add_product($idproduct, $qty, $remise_percent, $date_start='', $date_end='')
	{
		global $conf, $mysoc;
	
		// Nettoyage parametres
		if (! $qty) $qty = 1;
	
		dolibarr_syslog("Facture.class::add_product $idproduct, $qty, $remise_percent, $date_start, $date_end");

		if ($idproduct > 0)
		{
			$prod=new Product($this->db);
			$prod->fetch($idproduct);
	
			$tva_tx = get_default_tva($mysoc,$this->client,$prod->tva_tx);
			// multiprix
			if($conf->global->PRODUIT_MULTIPRICES == 1)
			$price = $prod->multiprices[$this->client->price_level];
			else
			$price = $prod->price;
	
			$line=new FactureLigne($this->db);
			$line->rowid = $idproduct;
			$line->fk_product = $idproduct;
			$line->desc = $prod->description;
			$line->qty = $qty;
			$line->subprice = $price;
			$line->remise_percent = $remise_percent;
			$line->tva_tx = $tva_tx;
			if ($date_start) { $line->date_start = $date_start; }
			if ($date_end)   { $line->date_end = $date_end; }
	
			$this->products[]=$line;
		}
	}

  /**
   * 		\brief    	Ajoute une ligne de facture (associé à un produit/service prédéfini ou non)
   * 		\param    	facid           	Id de la facture
   * 		\param    	desc            	Description de la ligne
   * 		\param    	pu_ht              	Prix unitaire HT
   * 		\param    	qty             	Quantité
   * 		\param    	txtva           	Taux de tva forcé, sinon -1
   *		\param    	fk_product      	Id du produit/service predéfini
   * 		\param    	remise_percent  	Pourcentage de remise de la ligne
   * 		\param    	date_start      	Date de debut de validité du service
   * 		\param    	date_end        	Date de fin de validité du service
   * 		\param    	ventil          	Code de ventilation comptable
   * 		\param    	info_bits			Bits de type de lignes
   *		\param    	fk_remise_except	Id remise
   *		\param		price_base_type		HT or TTC
   * 		\param    	pu_ttc             	Prix unitaire TTC
   *    	\return    	int             	>0 si ok, <0 si ko
   * 		\remarks	Les parametres sont deja censé etre juste et avec valeurs finales a l'appel
   *					de cette methode. Aussi, pour le taux tva, il doit deja avoir ete défini
   *					par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,taux_produit)
   *					et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
   */
	function addline($facid, $desc, $pu_ht, $qty, $txtva, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits='', $fk_remise_except='', $price_base_type='HT', $pu_ttc=0)
	{
		dolibarr_syslog("Facture::Addline facid=$facid,desc=$desc,pu_ht=$pu_ht,qty=$qty,txtva=$txtva,fk_product=$fk_product,remise_percent=$remise_percent,date_start=$date_start,date_end=$date_end,ventil=$ventil,info_bits=$info_bits,fk_remise_except=$fk_remise_except,price_base_type=$price_base_type,pu_ttc=$pu_ttc", LOG_DEBUG);
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

		if ($this->brouillon)
		{
			$this->db->begin();

			// Nettoyage paramètres
			$remise_percent=price2num($remise_percent);
			$qty=price2num($qty);
			if (! $qty) $qty=1;
			if (! $ventil) $ventil=0;
			if (! $info_bits) $info_bits=0;
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
			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, $price_base_type);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];

			// \TODO A virer
			// Anciens indicateurs: $price, $remise (a ne plus utiliser)
			$price = $pu;
			$remise = 0;
			if ($remise_percent > 0)
			{
				$remise = round(($pu * $remise_percent / 100),2);
				$price = ($pu - $remise);
			}

			// Insertion ligne
			$ligne=new FactureLigne($this->db);

			$ligne->fk_facture=$facid;
			$ligne->desc=$desc;
			$ligne->qty=$qty;
			$ligne->tva_tx=$txtva;
			$ligne->fk_product=$fk_product;
			$ligne->remise_percent=$remise_percent;
			$ligne->subprice=$pu_ht;
			$ligne->date_start=$date_start;
			$ligne->date_end=$date_end;
			$ligne->ventil=$ventil;
			$ligne->rang=-1;
			$ligne->info_bits=$info_bits;
			$ligne->fk_remise_except=$fk_remise_except;
			$ligne->total_ht=$total_ht;
			$ligne->total_tva=$total_tva;
			$ligne->total_ttc=$total_ttc;

			// \TODO Ne plus utiliser
			$ligne->price=$price;
			$ligne->remise=$remise;

			$result=$ligne->insert();
			if ($result > 0)
			{
				// Mise a jour informations denormalisees au niveau de la facture meme
				$result=$this->update_price($facid);
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
	}

  /**
   *      \brief     Mets à jour une ligne de facture
   *      \param     rowid            Id de la ligne de facture
   *      \param     desc             Description de la ligne
   *      \param     pu               Prix unitaire (HT ou TTC selon price_base_type)
   *      \param     qty              Quantité
   *      \param     remise_percent   Pourcentage de remise de la ligne
   *      \param     date_start       Date de debut de validité du service
   *      \param     date_end         Date de fin de validité du service
   *      \param     tva_tx           Taux TVA
   * 	  \param	 price_base_type  HT ou TTC
   *      \return    int              < 0 si erreur, > 0 si ok
   */
  function updateline($rowid, $desc, $pu, $qty, $remise_percent=0, $date_start, $date_end, $txtva, $price_base_type='HT')
  {
    dolibarr_syslog("Facture::UpdateLine $rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva", LOG_DEBUG);
    include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

    if ($this->brouillon)
      {
	$this->db->begin();

	// Nettoyage paramètres
	$remise_percent=price2num($remise_percent);
	$qty=price2num($qty);
	if (! $qty) $qty=1;
	$pu = price2num($pu);
	$txtva=price2num($txtva);

	// Calcul du total TTC et de la TVA pour la ligne a partir de
	// qty, pu, remise_percent et txtva
	// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
	// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
	$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, $price_base_type);
		$total_ht  = $tabprice[0];
		$total_tva = $tabprice[1];
		$total_ttc = $tabprice[2];
		$pu_ht  = $tabprice[3];
		$pu_tva = $tabprice[4];
		$pu_ttc = $tabprice[5];

	// Anciens indicateurs: $price, $remise (a ne plus utiliser)
	$price = $pu;
	$remise = 0;
	if ($remise_percent > 0)
	  {
	    $remise = round(($pu * $remise_percent / 100),2);
	    $price = ($pu - $remise);
	  }
	$price    = price2num($price);

	// Mise a jour ligne en base
	$ligne=new FactureLigne($this->db);
	$ligne->rowid=$rowid;
	$ligne->fetch($rowid);

	$ligne->desc=$desc;
	$ligne->qty=$qty;
	$ligne->tva_tx=$txtva;
	$ligne->remise_percent=$remise_percent;
	$ligne->subprice=$pu;
	$ligne->date_start=$date_start;
	$ligne->date_end=$date_end;
	$ligne->total_ht=$total_ht;
	$ligne->total_tva=$total_tva;
	$ligne->total_ttc=$total_ttc;

	// A ne plus utiliser
	$ligne->price=$price;
	$ligne->remise=$remise;

	$result=$ligne->update();
	if ($result > 0)
	  {
	    // Mise a jour info denormalisees au niveau facture
	    $this->update_price($this->id);
	    $this->db->commit();
	    return $result;
	  }
	else
	  {
	    $this->db->rollback();
	    return -1;
	  }
      }
    else
      {
	$this->error="Facture::UpdateLine Invoice statut makes operation forbidden";
	return -2;
      }
  }

	/**
	*	\brief		Supprime une ligne facture de la base
	*	\param		rowid		Id de la ligne de facture a supprimer
	*	\return		int			<0 if KO, >0 if OK
	*/
	function deleteline($rowid, $user='')
	{
	    global $langs, $conf;

	    dolibarr_syslog("Facture::Deleteline rowid=".$rowid, LOG_DEBUG);

	    if (! $this->brouillon)
	    {
			$this->error='ErrorBadStatus';
			return -1;
		}

		$this->db->begin();
	    	
    	// Libere remise liee a ligne de facture
    	$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except';
    	$sql.= ' SET fk_facture_line = NULL where fk_facture_line = '.$rowid;
   		dolibarr_syslog("Facture::Deleteline sql=".$sql);
    	$result = $this->db->query($sql);
    	if (! $result)
    	{
    		$this->error=$this->db->error();
    		dolibarr_syslog("Facture::Deleteline Error ".$this->error);
    		$this->db->rollback();
    		return -1;
    	}
    	
    	// Efface ligne de facture
    	$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facturedet WHERE rowid = '.$rowid;
   		dolibarr_syslog("Facture::Deleteline sql=".$sql);
    	$result = $this->db->query($sql);
    	if (! $result)
    	{
    		$this->error=$this->db->error();
    		dolibarr_syslog("Facture::Deleteline  Error ".$this->error);
    		$this->db->rollback();
    		return -1;
    	}
    	
    	$result=$this->update_price($this->id);
    	
    	// Appel des triggers
    	include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
    	$interface=new Interfaces($this->db);
    	$result = $interface->run_triggers('LINEBILL_DELETE',$this,$user,$langs,$conf);
    	// Fin appel triggers
    	
    	$this->db->commit();
    	
    	return 1;
	}

	/**
     \brief     	Mise à jour des sommes de la facture et calculs denormalises
     \param     	facid      	id de la facture a modifier
     \return		int			<0 si ko, >0 si ok
	*/
	function update_price($facid)
	{
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');
		
		$tvas=array();
		$err=0;

		// Liste des lignes a sommer
		$sql = 'SELECT qty, tva_taux, subprice, remise_percent,';
		$sql.= ' total_ht, total_tva, total_ttc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet';
		$sql.= ' WHERE fk_facture = '.$facid;

		dolibarr_syslog("Facture::update_price sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->total_ht  = 0;
			$this->total_tva = 0;
			$this->total_ttc = 0;
			
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$this->total_ht       += $obj->total_ht;
				$this->total_tva      += ($obj->total_ttc - $obj->total_ht);
				$this->total_ttc      += $obj->total_ttc;

				$tvas[$obj->tva_taux] += ($obj->total_ttc - $obj->total_ht);
				$i++;
			}

			$this->db->free($resql);

			// Met a jour indicateurs
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture SET';
			$sql .= " total='".    price2num($this->total_ht)."',";
			$sql .= " tva='".      price2num($this->total_tva)."',";
			$sql .= " total_ttc='".price2num($this->total_ttc)."'";
			$sql .= ' WHERE rowid = '.$facid;

			dolibarr_syslog("Facture::update_price sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				// \TODO A supprimer car l'utilisation de facture_tva_sum non utilisable
				// dans un context compta propre. On utilisera plutot les lignes.
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_tva_sum WHERE fk_facture='.$this->id;
				if ( $this->db->query($sql) )
				{
					foreach ($tvas as $key => $value)
					{
						$sql_del = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_tva_sum where fk_facture ='.$this->id;
						$this->db->query($sql_del);
						$sql = 'INSERT INTO '.MAIN_DB_PREFIX."facture_tva_sum (fk_facture,amount,tva_tx) values ($this->id,'".price2num($tvas[$key])."','".price2num($key)."');";
						if (! $this->db->query($sql) )
						{
							dolibarr_print_error($this->db);
							$err++;
						}
					}
				}
				else
				{
					$err++;
				}

				if ($err == 0)
				{
					return 1;
				}
				else
				{
					return -3;
				}
			}
			else
			{
				$this->error=$this->db->error();
				dolibarr_syslog("Facture::update_price error=".$this->error,LOG_ERR);
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("Facture::update_price error=".$this->error,LOG_ERR);
			return -1;
		}
	}

  /**
   * 		\brief     	Applique une remise relative
   * 		\param     	user		User qui positionne la remise
   * 		\param     	remise
   *		\return		int 		<0 si ko, >0 si ok
   */
  function set_remise($user, $remise)
  {
    $remise=trim($remise)?trim($remise):0;

    if ($user->rights->facture->creer)
      {
	$remise=price2num($remise);

	$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
	$sql.= ' SET remise_percent = '.$remise;
	$sql.= ' WHERE rowid = '.$this->id.' AND fk_statut = 0 ;';

	if ($this->db->query($sql))
	  {
	    $this->remise_percent = $remise;
	    $this->update_price($this->id);
	    return 1;
	  }
	else
	  {
	    $this->error=$this->db->error();
	    return -1;
	  }
      }
  }


  /**
   * 		\brief     	Applique une remise absolue
   * 		\param     	user 		User qui positionne la remise
   * 		\param     	remise
   *		\return		int 		<0 si ko, >0 si ok
   */
  function set_remise_absolue($user, $remise)
  {
    $remise=trim($remise)?trim($remise):0;

    if ($user->rights->facture->creer)
      {
	$remise=price2num($remise);

	$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
	$sql.= ' SET remise_absolue = '.$remise;
	$sql.= ' WHERE rowid = '.$this->id.' AND fk_statut = 0 ;';

	dolibarr_syslog("Facture::set_remise_absolue sql=$sql");

	if ($this->db->query($sql))
	  {
	    $this->remise_absolue = $remise;
	    $this->update_price($this->id);
	    return 1;
	  }
	else
	  {
	    $this->error=$this->db->error();
	    return -1;
	  }
      }
  }


  /**
   * 		\brief     Renvoie la liste des sommes de tva
   */
  function getSumTva()
  {
    $tvs=array();

    $sql = 'SELECT amount, tva_tx FROM '.MAIN_DB_PREFIX.'facture_tva_sum WHERE fk_facture = '.$this->id;
    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($i);
	    $tvs[$row[1]] = $row[0];
	    $i++;
	  }
	return $tvs;
      }
    else
      {
	dolibarr_print_error($this->db);
	return -1;
      }
  }

	/**
	* 	\brief     	Renvoie la sommes des paiements deja effectués
	*	\return		Montant deja versé, <0 si ko
	*/
	function getSommePaiement()
	{
		$sql = 'SELECT sum(amount) as amount';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture';
		$sql.= ' WHERE fk_facture = '.$this->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			return $obj->amount;
		}
		else
		{
			return -1;
		}
	}

	/**
	 *    	\brief      Renvoie montant TTC des avoirs utilises par la facture
	 *		\return		int			<0 if KO, Credit note amount otherwise
	 */
	function getSommeCreditNote()
	{
		require_once(DOL_DOCUMENT_ROOT.'/discount.class.php');

        $discountstatic=new DiscountAbsolute($this->db);
		$result=$discountstatic->getSommeCreditNote($this);
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
	* 	\brief     	Renvoie tableau des ids de facture avoir issus de la facture
	*	\return		array		Tableau d'id de factures avoirs
	*/
	function getListIdAvoirFromInvoice()
	{
		$idarray=array();

		$sql = 'SELECT rowid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture';
		$sql.= ' WHERE fk_facture_source = '.$this->id;
		$sql.= ' AND type = 2';
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $this->db->fetch_row($resql);
				$idarray[]=$row[0];
				$i++;
			}
		}
		else
		{
			dolibarr_print_error($this->db);	
		}
		return $idarray;
	}
	
  /**
   * 	\brief     	Renvoie l'id de la facture qui la remplace
   *	\param		option		filtre sur statut ('', 'validated', ...)
   *	\return		int			<0 si ko, 0 si aucune facture ne remplace, id facture sinon
   */
  function getIdReplacingInvoice($option='')
  {
    $sql = 'SELECT rowid';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'facture';
    $sql.= ' WHERE fk_facture_source = '.$this->id;
    $sql.= ' AND type < 2';
    if ($option == 'validated') $sql.= ' AND fk_statut = 1';
    // PROTECTION BAD DATA
    // Au cas ou base corrompue et qu'il y a une facture de remplacement validée
    // et une autre non, on donne priorité à la validée.
    // Ne devrait pas arriver (sauf si accès concurrentiel et que 2 personnes
    // ont créé en meme temps une facture de remplacement pour la meme facture)
    $sql.= ' ORDER BY fk_statut DESC';	
	
    $resql=$this->db->query($sql);
    if ($resql)
      {
	$obj = $this->db->fetch_object($resql);
	if ($obj) 
	  {
	    // Si il y en a
	    return $obj->rowid;
	  }
	else
	  {
	    // Si aucune facture ne remplace	
	    return 0;
	  }
      }
    else
      {
	return -1;
      }
  }
	
  /**
   *    \brief      Retourne le libellé du type de facture
   *    \return     string        Libelle
   */
  function getLibType()
  {
    global $langs;
    if ($this->type == 0) return $langs->trans("InvoiceStandard");
    if ($this->type == 1) return $langs->trans("InvoiceReplacement");
    if ($this->type == 2) return $langs->trans("InvoiceAvoir");
    return $langs->trans("Unknown");
  }


	/**
	*    \brief      Retourne le libellé du statut d'une facture (brouillon, validée, abandonnée, payée)
	*    \param      mode          0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long
	*    \return     string        Libelle
	*/
	function getLibStatut($mode=0,$alreadypayed=-1)
	{
		return $this->LibStatut($this->paye,$this->statut,$mode,$alreadypayed,$this->type);
	}

	/**
	*    	\brief      Renvoi le libellé d'un statut donné
	*    	\param      paye          	Etat paye
	*    	\param      statut        	Id statut
	*    	\param      mode          	0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
	*		\param		alreadypayed	Montant deja payé
	*		\param		type			Type facture
	*    	\return     string        	Libellé du statut
	*/
	function LibStatut($paye,$statut,$mode=0,$alreadypayed=-1,$type=0)
	{
		global $langs;
		$langs->load('bills');

		if ($mode == 0)
		{
			$prefix='';
			if (! $paye)
			{
				if ($statut == 0) return $langs->trans('Bill'.$prefix.'StatusDraft');
				if (($statut == 3 || $statut == 2) && $alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusClosedUnpayed');
				if (($statut == 3 || $statut == 2) && $alreadypayed > 0) return $langs->trans('Bill'.$prefix.'StatusClosedPayedPartially');
				if ($alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusNotPayed');
				return $langs->trans('Bill'.$prefix.'StatusStarted');
			}
			else
			{
				if ($type == 2) return $langs->trans('Bill'.$prefix.'StatusPayedBackOrConverted');
				else return $langs->trans('Bill'.$prefix.'StatusPayed');
			}
		}
		if ($mode == 1)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($statut == 0) return $langs->trans('Bill'.$prefix.'StatusDraft');
				if (($statut == 3 || $statut == 2) && $alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusCanceled');
				if (($statut == 3 || $statut == 2) && $alreadypayed > 0) return $langs->trans('Bill'.$prefix.'StatusClosedPayedPartially');
				if ($alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusNotPayed');
				return $langs->trans('Bill'.$prefix.'StatusStarted');
			}
			else
			{
				if ($type == 2) return $langs->trans('Bill'.$prefix.'StatusPayedBackOrConverted');
				else return $langs->trans('Bill'.$prefix.'StatusPayed');
			}
		}
		if ($mode == 2)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($statut == 0) return img_picto($langs->trans('BillStatusDraft'),'statut0').' '.$langs->trans('Bill'.$prefix.'StatusDraft');
				if (($statut == 3 || $statut == 2) && $alreadypayed <= 0) return img_picto($langs->trans('StatusCanceled'),'statut5').' '.$langs->trans('Bill'.$prefix.'StatusCanceled');
				if (($statut == 3 || $statut == 2) && $alreadypayed > 0) return img_picto($langs->trans('BillStatusClosedPayedPartially'),'statut7').' '.$langs->trans('Bill'.$prefix.'StatusClosedPayedPartially');
				if ($alreadypayed <= 0) return img_picto($langs->trans('BillStatusNotPayed'),'statut1').' '.$langs->trans('Bill'.$prefix.'StatusNotPayed');
				return img_picto($langs->trans('BillStatusStarted'),'statut3').' '.$langs->trans('Bill'.$prefix.'StatusStarted');
			}
			else
			{
				if ($type == 2) return img_picto($langs->trans('BillStatusPayedBackOrConverted'),'statut6').' '.$langs->trans('Bill'.$prefix.'StatusPayedBackOrConverted');
				else return img_picto($langs->trans('BillStatusPayed'),'statut6').' '.$langs->trans('Bill'.$prefix.'StatusPayed');
			}
		}
		if ($mode == 3)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($statut == 0) return img_picto($langs->trans('BillStatusDraft'),'statut0');
				if (($statut == 3 || $statut == 2) && $alreadypayed <= 0) return img_picto($langs->trans('BillStatusCanceled'),'statut5');
				if (($statut == 3 || $statut == 2) && $alreadypayed > 0) return img_picto($langs->trans('BillStatusClosedPayedPartially'),'statut7');
				if ($alreadypayed <= 0) return img_picto($langs->trans('BillStatusNotPayed'),'statut1');
				return img_picto($langs->trans('BillStatusStarted'),'statut3');
			}
			else
			{
				if ($type == 2) return img_picto($langs->trans('BillStatusPayedBackOrConverted'),'statut6');
				else return img_picto($langs->trans('BillStatusPayed'),'statut6');
			}
		}
		if ($mode == 4)
		{
			if (! $paye)
			{
				if ($statut == 0) return img_picto($langs->trans('BillStatusDraft'),'statut0').' '.$langs->trans('BillStatusDraft');
				if (($statut == 3 || $statut == 2) && $alreadypayed <= 0) return img_picto($langs->trans('BillStatusCanceled'),'statut5').' '.$langs->trans('Bill'.$prefix.'StatusCanceled');
				if (($statut == 3 || $statut == 2) && $alreadypayed > 0) return img_picto($langs->trans('BillStatusClosedPayedPartially'),'statut7').' '.$langs->trans('Bill'.$prefix.'StatusClosedPayedPartially');
				if ($alreadypayed <= 0) return img_picto($langs->trans('BillStatusNotPayed'),'statut1').' '.$langs->trans('BillStatusNotPayed');
				return img_picto($langs->trans('BillStatusStarted'),'statut3').' '.$langs->trans('BillStatusStarted');
			}
			else
			{
				if ($type == 2) return img_picto($langs->trans('BillStatusPayedBackOrConverted'),'statut6').' '.$langs->trans('BillStatusPayedBackOrConverted');
				else return img_picto($langs->trans('BillStatusPayed'),'statut6').' '.$langs->trans('BillStatusPayed');
			}
		}
		if ($mode == 5)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($statut == 0) return $langs->trans('Bill'.$prefix.'StatusDraft').' '.img_picto($langs->trans('BillStatusDraft'),'statut0');
				if (($statut == 3 || $statut == 2) && $alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusCanceled').' '.img_picto($langs->trans('BillStatusCanceled'),'statut5');
				if (($statut == 3 || $statut == 2) && $alreadypayed > 0) return $langs->trans('Bill'.$prefix.'StatusClosedPayedPartially').' '.img_picto($langs->trans('BillStatusClosedPayedPartially'),'statut7');
				if ($alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusNotPayed').' '.img_picto($langs->trans('BillStatusNotPayed'),'statut1');
				return $langs->trans('Bill'.$prefix.'StatusStarted').' '.img_picto($langs->trans('BillStatusStarted'),'statut3');
			}
			else
			{
				if ($type == 2) return $langs->trans('Bill'.$prefix.'StatusPayedBackOrConverted').' '.img_picto($langs->trans('BillStatusPayedBackOrConverted'),'statut6');
				else return $langs->trans('Bill'.$prefix.'StatusPayed').' '.img_picto($langs->trans('BillStatusPayed'),'statut6');
			}
		}
	}

  /**
   *      \brief      Renvoie la référence de facture suivante non utilisée en fonction du module
   *                  de numérotation actif défini dans FACTURE_ADDON
   *      \param	    soc  		            objet societe
   *      \return     string                  reference libre pour la facture
   */
  function getNextNumRef($soc)
  {
    global $db, $langs;
    $langs->load("bills");

    $dir = DOL_DOCUMENT_ROOT . "/includes/modules/facture/";

    if (defined("FACTURE_ADDON") && FACTURE_ADDON)
      {
	$file = FACTURE_ADDON."/".FACTURE_ADDON.".modules.php";

	// Chargement de la classe de numérotation
	$classname = "mod_facture_".FACTURE_ADDON;
	require_once($dir.$file);

	$obj = new $classname();

	$numref = "";
	$numref = $obj->getNumRef($soc,$this);

	if ( $numref != "")
	  {
	    return $numref;
	  }
	else
	  {
	    dolibarr_print_error($db,"Facture::getNextNumRef ".$obj->error);
	    return "";
	  }
      }
    else
      {
	print $langs->trans("Error")." ".$langs->trans("Error_FACTURE_ADDON_NotDefined");
	return "";
      }
  }

  /**
   *    \brief      Mets à jour les commentaires privés
   *    \param      note        	Commentaire
   *    \return     int         	<0 si ko, >0 si ok
   */
  function update_note($note)
  {
    $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table;
    $sql.= " SET note = '".addslashes($note)."'";
    $sql.= " WHERE rowid =". $this->id;

    dolibarr_syslog("Facture.class::update_note sql=$sql");
    if ($this->db->query($sql))
      {
	$this->note = $note;
	return 1;
      }
    else
      {
	$this->error=$this->db->error();
	return -1;
      }
  }

  /**
   *    \brief      Mets à jour les commentaires publiques
   *    \param      note_public	Commentaire
   *    \return     int         	<0 si ko, >0 si ok
   */
  function update_note_public($note_public)
  {
    $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table;
    $sql.= " SET note_public = '".addslashes($note_public)."'";
    $sql.= " WHERE rowid =". $this->id;

    dolibarr_syslog("Facture.class::update_note_public sql=$sql");
    if ($this->db->query($sql))
      {
	$this->note_public = $note_public;
	return 1;
      }
    else
      {
	$this->error=$this->db->error();
	return -1;
      }
  }

	/**
	*      \brief     Charge les informations de l'onglet info dans l'objet facture
	*      \param     id       	Id de la facture a charger
	*/
	function info($id)
	{
		$sql = 'SELECT c.rowid, '.$this->db->pdate('datec').' as datec,';
		$sql.= ' '.$this->db->pdate('date_valid').' as datev,';
		$sql.= ' fk_user_author, fk_user_valid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as c';
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
				$this->date_validation   = $obj->datev;
			}
			$this->db->free($result);
		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}

  /**
   *   \brief      Change les conditions de réglement de la facture
   *   \param      cond_reglement_id      Id de la nouvelle condition de réglement
   *   \return     int                    >0 si ok, <0 si ko
   */
  function cond_reglement($cond_reglement_id)
  {
    dolibarr_syslog('Facture::cond_reglement '.$cond_reglement_id, LOG_DEBUG);
    if ($this->statut >= 0 && $this->paye == 0)
      {
	$datelim=$this->calculate_date_lim_reglement($cond_reglement_id);
	$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
	$sql .= ' SET fk_cond_reglement = '.$cond_reglement_id;
	$sql .= ', date_lim_reglement='.$this->db->idate($datelim);
	$sql .= ' WHERE rowid='.$this->id;
	if ( $this->db->query($sql) )
	  {
	    $this->cond_reglement_id = $cond_reglement_id;
	    return 1;
	  }
	else
	  {
	    dolibarr_syslog('Facture::cond_reglement Erreur '.$sql.' - '.$this->db->error());
	    $this->error=$this->db->error();
	    return -1;
	  }
      }
    else
      {
	dolibarr_syslog('Facture::cond_reglement, etat facture incompatible');
	$this->error='Etat facture incompatible '.$this->statut.' '.$this->paye;
	return -2;
      }
  }


  /**
   *   \brief      Change le mode de réglement
   *   \param      mode        Id du nouveau mode
   *   \return     int         >0 si ok, <0 si ko
   */
  function mode_reglement($mode_reglement_id)
  {
    dolibarr_syslog('Facture::mode_reglement('.$mode_reglement_id.')', LOG_DEBUG);
    if ($this->statut >= 0 && $this->paye == 0)
      {
	$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
	$sql .= ' SET fk_mode_reglement = '.$mode_reglement_id;
	$sql .= ' WHERE rowid='.$this->id;
	if ( $this->db->query($sql) )
	  {
	    $this->mode_reglement_id = $mode_reglement_id;
	    return 1;
	  }
	else
	  {
	    dolibarr_syslog('Facture::mode_reglement Erreur '.$sql.' - '.$this->db->error());
	    $this->error=$this->db->error();
	    return -1;
	  }
      }
    else
      {
	dolibarr_syslog('Facture::mode_reglement, etat facture incompatible');
	$this->error='Etat facture incompatible '.$this->statut.' '.$this->paye;
	return -2;
      }
  }


  /**
   *   \brief      Renvoi si les lignes de facture sont ventilées et/ou exportées en compta
   *   \param      user        Utilisateur créant la demande
   *   \return     int         <0 si ko, 0=non, 1=oui
   */
  function getVentilExportCompta()
  {
    // On vérifie si les lignes de factures ont été exportées en compta et/ou ventilées
    $ventilExportCompta = 0 ;
    for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
      {
	if ($this->lignes[$i]->export_compta <> 0 && $this->lignes[$i]->code_ventilation <> 0)
	  {
	    $ventilExportCompta++;
	  }
      }

    if ($ventilExportCompta <> 0)
      {
	return 1;
      }
    else
      {
	return 0;
      }
  }


  /**
   *   \brief     Renvoi si une facture peut etre supprimée complètement.
   *				La règle est la suivante:
   *				Si facture dernière, non provisoire, sans paiement et non exporté en compta -> oui fin de règle
   *       		Si facture brouillon et provisoire -> oui
   *   \return    int         <0 si ko, 0=non, 1=oui
   */
  function is_erasable()
  {
    global $conf;

    // on vérifie si la facture est en numérotation provisoire
    $facref = substr($this->ref, 1, 4);

    // Si facture non brouillon et non provisoire
    if ($facref != 'PROV' && ! $conf->comptaexpert->enabled && $conf->global->FACTURE_ENABLE_EDITDELETE)
      {
	// On ne peut supprimer que la dernière facture validée
	// pour ne pas avoir de trou dans la numérotation
	$sql = "SELECT MAX(facnumber)";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture";

	$resql=$this->db->query($sql);
	if ($resql)
	  {
	    $maxfacnumber = $this->db->fetch_row($resql);
	  }

	$ventilExportCompta = $this->getVentilExportCompta();

	// Si derniere facture et si non ventilée, on peut supprimer
	if ($maxfacnumber[0] == $this->ref && $ventilExportCompta == 0)
	  {
	    return 1;
	  }
      }
    else if ($this->statut == 0 && $facref == 'PROV') // Si facture brouillon et provisoire
      {
	return 1;
      }

    return 0;
  }


  /**
     \brief     	Renvoi liste des factures remplacables
					Statut validée ou abandonnée pour raison autre + non payée + aucun paiement + pas deja remplacée
     \param			socid		Id societe
     \return    	array		Tableau des factures ('id'=>id, 'ref'=>ref, 'statut'=>status)
   */
  function list_replacable_invoices($socid=0)
  {
    global $conf;

    $return = array();

    $sql = "SELECT f.rowid as rowid, f.facnumber, f.fk_statut,";
    $sql.= " ff.rowid as rowidnext";
    $sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid = pf.fk_facture";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as ff ON f.rowid = ff.fk_facture_source";
    $sql.= " WHERE (f.fk_statut = 1 OR (f.fk_statut = 3 AND f.close_code = 'abandon'))";
	$sql.= " AND f.paye = 0";					// Pas classée payée complètement
	$sql.= " AND pf.fk_paiement IS NULL";		// Aucun paiement deja fait
    $sql.= " AND ff.fk_statut IS NULL";			// Renvoi vrai si pas facture de remplacement
    if ($socid > 0) $sql.=" AND f.fk_soc = ".$socid;
    $sql.= " ORDER BY f.facnumber";

    dolibarr_syslog("Facture.class::list_replacable_invoices sql=$sql");
    $resql=$this->db->query($sql);
    if ($resql)
      {
	while ($obj=$this->db->fetch_object($resql))
	  {
	    $return[$obj->rowid]=array(	'id' => $obj->rowid, 
					'ref' => $obj->facnumber,
					'status' => $obj->fk_status);
	  }
	//print_r($return);
	return $return;
      }
    else
      {
	$this->error=$this->db->error();
	dolibarr_syslog("Facture.class::list_replacable_invoices ".$this->error);
	return -1;
      }
  }


  /**
   *  	\brief     	Renvoi liste des factures qualifiables pour correction par avoir
   *				Les factures qui respectent les regles suivantes sont retournees:
   * 				(validée + paiement en cours) ou classée (payée completement ou payée partiellement) + pas deja remplacée + pas deja avoir
   *	\param		socid		Id societe
   *   	\return    	array		Tableau des factures ($id => $ref)
   */
  function list_qualified_avoir_invoices($socid=0)
  {
    $return = array();

    $sql = "SELECT f.rowid as rowid, f.facnumber, f.fk_statut, pf.fk_paiement";
    $sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid = pf.fk_facture";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as ff ON (f.rowid = ff.fk_facture_source AND ff.type=1)";
	$sql.= " WHERE ";
	$sql.= " f.fk_statut in (1,2)";
//  $sql.= " WHERE f.fk_statut >= 1";
//	$sql.= " AND (f.paye = 1";				// Classée payée complètement
//	$sql.= " OR f.close_code IS NOT NULL)";	// Classée payée partiellement
    $sql.= " AND ff.type IS NULL";			// Renvoi vrai si pas facture de remplacement
    $sql.= " AND f.type != 2";				// Type non 2 si facture non avoir
	if ($socid > 0) $sql.=" AND f.fk_soc = ".$socid;
    $sql.= " ORDER BY f.facnumber";

    dolibarr_syslog("Facture.class::list_qualified_avoir_invoices sql=$sql");
    $resql=$this->db->query($sql);
    if ($resql)
      {
	while ($obj=$this->db->fetch_object($resql))
	  {
	  	$qualified=0;
	  	// if statut is 1, record is qualified only if some paiement
		// has already been made.
		// If not, we must not do credit note but a replacement invoice.
	    if ($obj->fk_statut == 1 && $obj->fk_paiement) $qualified=1;
	    if ($obj->fk_statut == 2) $qualified=1;
	    if ($qualified)
	    {
	    	//$ref=$obj->facnumber;
	    	$paymentornot=($obj->fk_paiement?1:0);
	    	$return[$obj->rowid]=$paymentornot;
	    }
	  }

	return $return;
      }
    else
      {
	$this->error=$this->db->error();
	dolibarr_syslog("Facture.class::list_avoir_invoices ".$this->error);
	return -1;
      }
  }


  /**
   *   \brief      Créé une demande de prélèvement
   *   \param      user        Utilisateur créant la demande
   *   \return     int         <0 si ko, >0 si ok
   */
  function demande_prelevement($user)
  {
    dolibarr_syslog("Facture::demande_prelevement", LOG_DEBUG);

    $soc = new Societe($this->db);
    $soc->id = $this->socid;
    $soc->rib();
    if ($this->statut > 0 && $this->paye == 0 && $this->mode_reglement_id == 3)
      {
	$sql = 'SELECT count(*) FROM '.MAIN_DB_PREFIX.'prelevement_facture_demande';
	$sql .= ' WHERE fk_facture='.$this->id;
	$sql .= ' AND traite = 0';
	if ( $this->db->query( $sql) )
	  {
	    $row = $this->db->fetch_row();
	    if ($row[0] == 0)
	      {
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'prelevement_facture_demande';
		$sql .= ' (fk_facture, amount, date_demande, fk_user_demande, code_banque, code_guichet, number, cle_rib)';
		$sql .= ' VALUES ('.$this->id;
		$sql .= ",'".price2num($this->total_ttc)."'";
		$sql .= ',now(),'.$user->id;
		$sql .= ",'".$soc->bank_account->code_banque."'";
		$sql .= ",'".$soc->bank_account->code_guichet."'";
		$sql .= ",'".$soc->bank_account->number."'";
		$sql .= ",'".$soc->bank_account->cle_rib."')";
		if ( $this->db->query( $sql) )
		  {
		    return 1;
		  }
		else
		  {
		    $this->error=$this->db->error();
		    dolibarr_syslog('Facture::DemandePrelevement Erreur');
		    return -1;
		  }
	      }
	    else
	      {
		$this->error="Une demande existe déjà";
		dolibarr_syslog('Facture::DemandePrelevement Impossible de créer une demande, demande déja en cours');
	      }
	  }
	else
	  {
	    $this->error=$this->db->error();
	    dolibarr_syslog('Facture::DemandePrelevement Erreur -2');
	    return -2;
	  }
      }
    else
      {
	$this->error="Etat facture incompatible avec l'action";
	dolibarr_syslog("Facture::DemandePrelevement Etat facture incompatible $this->statut, $this->paye, $this->mode_reglement_id");
	return -3;
      }
  }

  /**
   * \brief     Supprime une demande de prélèvement
   * \param     user         utilisateur créant la demande
   * \param     did          id de la demande a supprimer
   */
  function demande_prelevement_delete($user, $did)
  {
    $sql = 'DELETE FROM '.MAIN_DB_PREFIX.'prelevement_facture_demande';
    $sql .= ' WHERE rowid = '.$did;
    $sql .= ' AND traite = 0';
    if ( $this->db->query( $sql) )
      {
	return 0;
      }
    else
      {
	dolibarr_syslog('Facture::DemandePrelevement Erreur');
	return -1;
      }
  }

  /**
   *      \brief      Stocke un numéro de rang pour toutes les lignes de
   *                  detail d'une facture qui n'en ont pas.
   */
  function line_order()
  {
    $sql = 'SELECT count(rowid) FROM '.MAIN_DB_PREFIX.'facturedet';
    $sql.= ' WHERE fk_facture='.$this->id;
    $sql.= ' AND rang = 0';
    $resql = $this->db->query($sql);
    if ($resql)
      {
	$row = $this->db->fetch_row($resql);
	$nl = $row[0];
      }
    if ($nl > 0)
      {
	$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'facturedet';
	$sql.= ' WHERE fk_facture='.$this->id;
	$sql.= ' ORDER BY rang ASC, rowid ASC';
	$resql = $this->db->query($sql);
	if ($resql)
	  {
	    $num = $this->db->num_rows($resql);
	    $i = 0;
	    while ($i < $num)
	      {
		$row = $this->db->fetch_row($resql);
		$li[$i] = $row[0];
		$i++;
	      }
	  }
	for ($i = 0 ; $i < sizeof($li) ; $i++)
	  {
	    $sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet SET rang = '.($i+1);
	    $sql.= ' WHERE rowid = '.$li[$i];
	    if (!$this->db->query($sql) )
	      {
		dolibarr_syslog($this->db->error());
	      }
	  }
      }
  }

  function line_up($rowid)
  {
    $this->line_order();

    /* Lecture du rang de la ligne */
    $sql = 'SELECT rang FROM '.MAIN_DB_PREFIX.'facturedet';
    $sql.= ' WHERE rowid ='.$rowid;
    $resql = $this->db->query($sql);
    if ($resql)
      {
	$row = $this->db->fetch_row($resql);
	$rang = $row[0];
      }

    if ($rang > 1 )
      {
	$sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet SET rang = '.$rang ;
	$sql.= ' WHERE fk_facture  = '.$this->id;
	$sql.= ' AND rang = '.($rang - 1);
	if ($this->db->query($sql) )
	  {
	    $sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet SET rang  = '.($rang - 1);
	    $sql.= ' WHERE rowid = '.$rowid;
	    if (! $this->db->query($sql) )
	      {
		dolibarr_print_error($this->db);
	      }
	  }
	else
	  {
	    dolibarr_print_error($this->db);
	  }
      }
  }

  function line_down($rowid)
  {
    $this->line_order();

    /* Lecture du rang de la ligne */
    $sql = 'SELECT rang FROM '.MAIN_DB_PREFIX.'facturedet';
    $sql.= ' WHERE rowid ='.$rowid;
    $resql = $this->db->query($sql);
    if ($resql)
      {
	$row = $this->db->fetch_row($resql);
	$rang = $row[0];
      }

    /* Lecture du rang max de la facture */
    $sql = 'SELECT max(rang) FROM '.MAIN_DB_PREFIX.'facturedet';
    $sql.= ' WHERE fk_facture ='.$this->id;
    $resql = $this->db->query($sql);
    if ($resql)
      {
	$row = $this->db->fetch_row($resql);
	$max = $row[0];
      }

    if ($rang < $max )
      {
	$sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet SET rang = '.$rang;
	$sql.= ' WHERE fk_facture  = '.$this->id;
	$sql.= ' AND rang = '.($rang+1);
	if ($this->db->query($sql) )
	  {
	    $sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet SET rang = '.($rang+1);
	    $sql.= ' WHERE rowid = '.$rowid;
	    if (! $this->db->query($sql) )
	      {
		dolibarr_print_error($this->db);
	      }
	  }
	else
	  {
	    dolibarr_print_error($this->db);
	  }
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
    $clause = "WHERE";
    
    $sql = 'SELECT f.rowid,'.$this->db->pdate('f.date_lim_reglement').' as datefin';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
    if (!$user->rights->commercial->client->voir && !$user->societe_id)
    {
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON f.fk_soc = sc.fk_soc";
    	$sql.= " WHERE sc.fk_user = " .$user->id;
    	$clause = "AND";
    }
    $sql.= ' '.$clause.' f.paye=0 AND f.fk_statut = 1';
    if ($user->societe_id) $sql.=' AND f.fk_soc = '.$user->societe_id;
    $resql=$this->db->query($sql);
    if ($resql)
    {
    	while ($obj=$this->db->fetch_object($resql))
    	{
    		$this->nbtodo++;
    		if ($obj->datefin < (time() - $conf->facture->client->warning_delay)) $this->nbtodolate++;
    	}
    	return 1;
    }
    else
    {
    	dolibarr_print_error($this->db);
    	$this->error=$this->db->error();
    	return -1;
    }
  }


  /* gestion des contacts d'une facture */

  /**
   *      \brief      Retourne id des contacts clients de facturation
   *      \return     array       Liste des id contacts facturation
   */
  function getIdBillingContact()
  {
    return $this->getIdContact('external','BILLING');
  }

  /**
   *      \brief      Retourne id des contacts clients de livraison
   *      \return     array       Liste des id contacts livraison
   */
  function getIdShippingContact()
  {
    return $this->getIdContact('external','SHIPPING');
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
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE client=1 LIMIT 10";
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
	$ligne->total_ht=100;
	$ligne->total_ttc=119.6;
	$ligne->total_tva=19.6;
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

  	/**
	*      \brief      Charge indicateurs this->nb de tableau de bord
	*      \return     int         <0 si ko, >0 si ok
	*/
	function load_state_board()
	{
		global $conf, $user;

		$this->nb=array();

		$sql = "SELECT count(f.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
		if (!$user->rights->commercial->client->voir && !$user->societe_id)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql.= " WHERE sc.fk_user = " .$user->id;
		}
		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["invoices"]=$obj->nb;
			}
			return 1;
		}
		else
		{
			dolibarr_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
	}
	
}



/**
   \class      FactureLigne
   \brief      Classe permettant la gestion des lignes de factures
   \remarks	Gere des lignes de la table llx_facturedet
*/
class FactureLigne
{
  var $db;
  var $error;

  //! From llx_facturedet
  var $rowid;
  //! Id facture
  var $fk_facture;
  //! Description ligne
  var $desc;           
  var $fk_product;	// Id produit prédéfini

  var $qty;		// Quantité (exemple 2)
  var $tva_tx;		// Taux tva produit/service (exemple 19.6)
  var $subprice;      	// P.U. HT (exemple 100)
  var $remise_percent;	// % de la remise ligne (exemple 20%)
  var $rang = 0;
  var $info_bits = 0;		// Bit 0:	0 si TVA normal - 1 si TVA NPR
							// Bit 1:	0 si ligne normal - 1 si bit discount

  //! Total HT  de la ligne toute quantité et incluant la remise ligne
  var $total_ht;
  //! Total TVA  de la ligne toute quantité et incluant la remise ligne
  var $total_tva;
  //! Total TTC de la ligne toute quantité et incluant la remise ligne
  var $total_ttc;

  var $fk_code_ventilation = 0;
  var $fk_export_compta = 0;

  var $date_start;
  var $date_end;

  // Ne plus utiliser
  var $price;         	// P.U. HT apres remise % de ligne (exemple 80)
  var $remise;			// Montant calculé de la remise % sur PU HT (exemple 20)

  // From llx_product
  var $ref;				// Reference produit
  var $libelle;      		// Label produit
  var $product_desc;  	// Description produit


  /**
     \brief     Constructeur d'objets ligne de facture
     \param     DB      handler d'accès base de donnée
   */
  function FactureLigne($DB)
  {
    $this->db= $DB ;
  }

  /**
     \brief     Recupére l'objet ligne de facture
     \param     rowid           id de la ligne de facture
   */
  function fetch($rowid)
  {
    $sql = 'SELECT fd.rowid, fd.fk_facture, fd.fk_product, fd.description, fd.price, fd.qty, fd.tva_taux,';
    $sql.= ' fd.remise, fd.remise_percent, fd.fk_remise_except, fd.subprice,';
    $sql.= ' '.$this->db->pdate('fd.date_start').' as date_start,'.$this->db->pdate('fd.date_end').' as date_end,';
    $sql.= ' fd.info_bits, fd.total_ht, fd.total_tva, fd.total_ttc, fd.rang,';
    $sql.= ' fd.fk_code_ventilation, fd.fk_export_compta,';
    $sql.= ' p.ref as product_ref, p.label as product_libelle, p.description as product_desc';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet as fd';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON fd.fk_product = p.rowid';
    $sql.= ' WHERE fd.rowid = '.$rowid;
    $result = $this->db->query($sql);
    if ($result)
      {
	$objp = $this->db->fetch_object($result);
	$this->rowid          = $objp->rowid;
	$this->fk_facture     = $objp->fk_facture;
	$this->desc           = $objp->description;
	$this->qty            = $objp->qty;
	$this->subprice       = $objp->subprice;
	$this->tva_tx         = $objp->tva_taux;
	$this->remise_percent = $objp->remise_percent;
	$this->fk_remise_except = $objp->fk_remise_except;
	$this->produit_id     = $objp->fk_product;	// Ne plus utiliser
	$this->fk_product     = $objp->fk_product;
	$this->date_start     = $objp->date_start;
	$this->date_end       = $objp->date_end;
	$this->info_bits      = $objp->info_bits;
	$this->total_ht       = $objp->total_ht;
	$this->total_tva      = $objp->total_tva;
	$this->total_ttc      = $objp->total_ttc;
	$this->fk_code_ventilation = $objp->fk_code_ventilation;
	$this->fk_export_compta    = $objp->fk_export_compta;
	$this->rang           = $objp->rang;

	// Ne plus utiliser
	$this->price          = $objp->price;
	$this->remise         = $objp->remise;

	$this->ref			  = $objp->product_ref;
	$this->libelle		  = $objp->product_libelle;
	$this->product_desc	  = $objp->product_desc;

	$this->db->free($result);
      }
    else
      {
	dolibarr_print_error($this->db);
      }
  }


	/**
	*   \brief     	Insère l'objet ligne de facture en base
	*	\param      notrigger		1 ne declenche pas les triggers, 0 sinon
	*	\return		int				<0 si ko, >0 si ok
	*/
	function insert($notrigger=0)
	{
		global $langs;
		
		// Nettoyage parametres
		if (! $this->subprice) $this->subprice=0;
		if (! $this->price) $this->price=0;
		
		dolibarr_syslog("FactureLigne::Insert rang=".$this->rang, LOG_DEBUG);
		$this->db->begin();

		$rangtouse=$this->rang;
		if ($rangtouse == -1)
		{
			// Récupère rang max de la facture dans $rangmax
			$sql = 'SELECT max(rang) as max FROM '.MAIN_DB_PREFIX.'facturedet';
			$sql.= ' WHERE fk_facture ='.$this->fk_facture;
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$obj = $this->db->fetch_object($resql);
				$rangtouse = $obj->max + 1;
			}
			else
			{
				dolibarr_print_error($this->db);
				$this->db->rollback();
				return -1;
			}
		}

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facturedet';
		$sql.= ' (fk_facture, description, qty, tva_taux,';
		$sql.= ' fk_product, remise_percent, subprice, price, remise, fk_remise_except,';
		$sql.= ' date_start, date_end, fk_code_ventilation, fk_export_compta, ';
		$sql.= ' rang,';
		$sql.= ' info_bits, total_ht, total_tva, total_ttc)';
		$sql.= " VALUES (".$this->fk_facture.",";
		$sql.= " '".addslashes($this->desc)."',";
		$sql.= " ".price2num($this->qty).",";
		$sql.= " ".price2num($this->tva_tx).",";
		if ($this->fk_product) { $sql.= "'".$this->fk_product."',"; }
		else { $sql.='null,'; }
		$sql.= " ".price2num($this->remise_percent).",";
		$sql.= " ".price2num($this->subprice).",";
		$sql.= " ".price2num($this->price).",";
		$sql.= " ".price2num($this->remise).",";
		if ($this->fk_remise_except) $sql.= $this->fk_remise_except.",";
		else $sql.= 'null,';
		if ($this->date_start) { $sql.= "'".$this->date_start."',"; }
		else { $sql.='null,'; }
		if ($this->date_end)   { $sql.= "'".$this->date_end."',"; }
		else { $sql.='null,'; }
		$sql.= ' '.$this->fk_code_ventilation.',';
		$sql.= ' '.$this->fk_export_compta.',';
		$sql.= ' '.$rangtouse.',';
		$sql.= " '".$this->info_bits."',";
		$sql.= " ".price2num($this->total_ht).",";
		$sql.= " ".price2num($this->total_tva).",";
		$sql.= " ".price2num($this->total_ttc);
		$sql.= ')';

		dolibarr_syslog("FactureLigne::insert sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->rowid=$this->db->last_insert_id(MAIN_DB_PREFIX.'facturedet');

			// Si fk_remise_except défini, on lie la remise à la facture
			// ce qui la flague comme "consommée".
			if ($this->fk_remise_except)
			{
				$discount=new DiscountAbsolute($this->db);
				$result=$discount->fetch($this->fk_remise_except);
				if ($result >= 0)
				{
					// Check if discount was found
					if ($result > 0)
					{
						// Check if discount not already affected to another invoice
						if ($discount->fk_facture)
						{
							$this->error=$langs->trans("ErrorDiscountAlreadyUsed",$discount->id);
							dolibarr_syslog("FactureLigne::insert Error ".$this->error);
							$this->db->rollback();
							return -3;
						}
						else
						{
							$result=$discount->link_to_invoice($this->rowid,0);
							if ($result < 0)
							{
								$this->error=$discount->error;
								dolibarr_syslog("FactureLigne::insert Error ".$this->error);
								$this->db->rollback();
								return -3;
							}
						}
					}
					else
					{
						$this->error=$langs->trans("ErrorADiscountThatHasBeenRemovedIsIncluded");
						dolibarr_syslog("FactureLigne::insert Error ".$this->error);
						$this->db->rollback();
						return -3;
					}
				}
				else
				{
					$this->error=$discount->error;
					dolibarr_syslog("FactureLigne::insert Error ".$this->error);
					$this->db->rollback();
					return -3;
				}
			}
			
			if (! $notrigger)
            {
				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result = $interface->run_triggers('LINEBILL_INSERT',$this,$user,$langs,$conf);
				// Fin appel triggers
			}
			
			$this->db->commit();
			return $this->rowid;

		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("FactureLigne::insert Error ".$this->error);
			$this->db->rollback();
			return -2;
		}
	}


  /**
   *    \brief     	Mise a jour de l'objet ligne de facture en base
   *	\return		int		<0 si ko, >0 si ok
   */
  function update()
  {
    $this->db->begin();

    // Mise a jour ligne en base
    $sql = "UPDATE ".MAIN_DB_PREFIX."facturedet SET";
    $sql.= " description='".addslashes($this->desc)."'";
    $sql.= ",subprice=".price2num($this->subprice)."";
    $sql.= ",price=".price2num($this->price)."";
    $sql.= ",remise=".price2num($this->remise)."";
    $sql.= ",remise_percent=".price2num($this->remise_percent)."";
    if ($this->fk_remise_except) $sql.= ",fk_remise_except=".$this->fk_remise_except;
    else $sql.= ",fk_remise_except=null";
    $sql.= ",tva_taux=".price2num($this->tva_tx)."";
    $sql.= ",qty=".price2num($this->qty)."";
    if ($this->date_start) { $sql.= ",date_start='".$this->date_start."'"; }
    else { $sql.=',date_start=null'; }
    if ($this->date_end) { $sql.= ",date_end='".$this->date_end."'"; }
    else { $sql.=',date_end=null'; }
    $sql.= ",rang='".$this->rang."'";
    $sql.= ",info_bits='".$this->info_bits."'";
    $sql.= ",total_ht=".price2num($this->total_ht)."";
    $sql.= ",total_tva=".price2num($this->total_tva)."";
    $sql.= ",total_ttc=".price2num($this->total_ttc)."";
    $sql.= " WHERE rowid = ".$this->rowid;

    dolibarr_syslog("FactureLigne::update sql=$sql");

    $resql=$this->db->query($sql);
    if ($resql)
      {
	$this->db->commit();
	return 1;
      }
    else
      {
	$this->error=$this->db->error();
	dolibarr_syslog("FactureLigne::update Error ".$this->error);
	$this->db->rollback();
	return -2;
      }
  }

  /**
   *      \brief     	Mise a jour en base des champs total_xxx de ligne de facture
   *		\return		int		<0 si ko, >0 si ok
   */
  function update_total()
  {
    $this->db->begin();
    dolibarr_syslog("FactureLigne::update_total", LOG_DEBUG);

    // Mise a jour ligne en base
    $sql = "UPDATE ".MAIN_DB_PREFIX."facturedet SET";
    $sql.= " total_ht=".price2num($this->total_ht)."";
    $sql.= ",total_tva=".price2num($this->total_tva)."";
    $sql.= ",total_ttc=".price2num($this->total_ttc)."";
    $sql.= " WHERE rowid = ".$this->rowid;



    $resql=$this->db->query($sql);
    if ($resql)
      {
	$this->db->commit();
	return 1;
      }
    else
      {
	$this->error=$this->db->error();
	dolibarr_syslog("FactureLigne::update_total Error ".$this->error);
	$this->db->rollback();
	return -2;
      }
  }
}

?>
