<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/compta/facture/class/facture-rec.class.php
 *	\ingroup    facture
 *	\brief      Fichier de la classe des factures recurentes
 *	\version    $Id: facture-rec.class.php,v 1.16 2011/08/03 00:46:25 eldy Exp $
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/notify.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");


/**
 *	\class      FactureRec
 *	\brief      Classe de gestion des factures recurrentes/Modeles
 */
class FactureRec extends Facture
{
	var $db ;
	var $element='facturerec';
	var $table_element='facture_rec';
	var $table_element_line='facturedet_rec';
	var $fk_element='fk_facture';

	var $id ;

	//! Id customer
	var $socid;
	//! Customer object (charging by fetch_client)
	var $client;

	var $number;
	var $author;
	var $date;
	var $ref;
	var $amount;
	var $remise;
	var $tva;
	var $total;
	var $note;
	var $db_table;
	var $propalid;
	var $fk_project;

	var $rang;
	var $special_code;

	var $lines=array();


	/**
	 * 		\brief		Initialisation de la classe
	 */
	function FactureRec($DB)
	{
		$this->db = $DB ;
	}

	/**
	 * 		Create a predefined invoice
	 * 		@param		user
	 * 		@param		facid		Id of source invoice
	 *		@return		int			<0 if KO, id of invoice if OK
	 */
	function create($user,$facid)
	{
		global $conf, $langs;

		$error=0;

		// Clean parameters
		$this->titre=trim($this->titre);

		// Validate parameters
		if (empty($this->titre))
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Title"));
			return -3;
		}

		$this->db->begin();

		// Charge facture modele
		$facsrc=new Facture($this->db);
		$result=$facsrc->fetch($facid);
		if ($result > 0)
		{
			// On positionne en mode brouillon la facture
			$this->brouillon = 1;

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_rec (";
			$sql.= "titre";
			$sql.= ", fk_soc";
			$sql.= ", entity";
			$sql.= ", datec";
			$sql.= ", amount";
			$sql.= ", remise";
			$sql.= ", note";
			$sql.= ", fk_user_author";
			$sql.= ", fk_projet";
			$sql.= ", fk_cond_reglement";
			$sql.= ", fk_mode_reglement";
			$sql.= ") VALUES (";
			$sql.= "'".$this->titre."'";
			$sql.= ", '".$facsrc->socid."'";
			$sql.= ", ".$conf->entity;
			$sql.= ", ".$this->db->idate(mktime());
			$sql.= ", '".$facsrc->amount."'";
			$sql.= ", '".$facsrc->remise."'";
			$sql.= ", '".$this->db->escape($this->note)."'";
			$sql.= ", '".$user->id."'";
			$sql.= ", ".($facsrc->fk_project?"'".$facsrc->fk_project."'":"null");
			$sql.= ", '".$facsrc->cond_reglement_id."'";
			$sql.= ", '".$facsrc->mode_reglement_id."'";
			$sql.= ")";

			if ( $this->db->query($sql) )
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."facture_rec");

				/*
				 * Lines
				 */
				for ($i = 0 ; $i < sizeof($facsrc->lines) ; $i++)
				{
					$result_insert = $this->addline($this->id,
					$facsrc->lines[$i]->desc,
					$facsrc->lines[$i]->subprice,
					$facsrc->lines[$i]->qty,
					$facsrc->lines[$i]->tva_tx,
					$facsrc->lines[$i]->fk_product,
					$facsrc->lines[$i]->remise_percent,
		                    'HT',0,'',0,
					$facsrc->lines[$i]->product_type,
					$facsrc->lines[$i]->rang,
					$facsrc->lines[$i]->special_code
					);

					if ($result_insert < 0)
					{
						$error++;
					}
				}

				if ($error)
				{
					$this->db->rollback();
				}
				else
				{
					$this->db->commit();
					return $this->id;
				}
			}
			else
			{
				$this->error=$this->db->error().' sql='.$sql;
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	\brief      Recupere l'objet facture et ses lignes de factures
	 *	\param      rowid       id de la facture a recuperer
	 *	\return     int         >0 si ok, <0 si ko
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT f.titre,f.fk_soc,f.amount,f.tva,f.total,f.total_ttc,f.remise_percent,f.remise_absolue,f.remise';
		$sql.= ', f.date_lim_reglement as dlr';
		$sql.= ', f.note, f.note_public, f.fk_user_author';
		$sql.= ', f.fk_mode_reglement, f.fk_cond_reglement';
		$sql.= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
		$sql.= ', c.code as cond_reglement_code, c.libelle as cond_reglement_libelle, c.libelle_facture as cond_reglement_libelle_doc';
		$sql.= ', el.fk_source';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_rec as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as c ON f.fk_cond_reglement = c.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON f.fk_mode_reglement = p.id';
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_target = f.rowid AND el.targettype = 'facture'"; // TODO remplacer par une fonction
		$sql.= ' WHERE f.rowid='.$rowid;

        dol_syslog("FactureRec::Fetch rowid=".$rowid.", societe_id=".$socid." sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id                     = $rowid;
				$this->titre                  = $obj->titre;
				$this->ref                    = $obj->titre;
				$this->ref_client             = $obj->ref_client;
				$this->type                   = $obj->type;
				$this->datep                  = $obj->dp;
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
				$this->date_lim_reglement     = $this->db->jdate($obj->dlr);
				$this->mode_reglement_id      = $obj->fk_mode_reglement;
				$this->mode_reglement_code    = $obj->mode_reglement_code;
				$this->mode_reglement         = $obj->mode_reglement_libelle;
				$this->cond_reglement_id      = $obj->fk_cond_reglement;
				$this->cond_reglement_code    = $obj->cond_reglement_code;
				$this->cond_reglement         = $obj->cond_reglement_libelle;
				$this->cond_reglement_doc     = $obj->cond_reglement_libelle_doc;
				$this->fk_project             = $obj->fk_projet;
				$this->fk_facture_source      = $obj->fk_facture_source;
				$this->note                   = $obj->note;
				$this->note_public            = $obj->note_public;
				$this->user_author            = $obj->fk_user_author;
				$this->modelpdf               = $obj->model_pdf;
				$this->rang					  = $obj->rang;
				$this->special_code			  = $obj->special_code;
				$this->commande_id            = $obj->fk_commande;

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
				 * Lines
				 */
				$result=$this->fetch_lines();
				if ($result < 0)
				{
					$this->error=$this->db->error();
					dol_syslog('Facture::Fetch Error '.$this->error, LOG_ERR);
					return -3;
				}
				return 1;
			}
			else
			{
				$this->error='Bill with id '.$rowid.' not found sql='.$sql;
				dol_syslog('Facture::Fetch Error '.$this->error, LOG_ERR);
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog('Facture::Fetch Error '.$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *	\brief      Recupere les lignes de factures predefinies dans this->lines
	 *	\return     int         1 if OK, < 0 if KO
 	 */
	function fetch_lines()
	{
		$sql = 'SELECT l.rowid, l.fk_product, l.product_type, l.description, l.price, l.qty, l.tva_tx, ';
		$sql.= ' l.remise, l.remise_percent, l.subprice,';
		$sql.= ' l.total_ht, l.total_tva, l.total_ttc,';
		$sql.= ' l.rang, l.special_code,';
		$sql.= ' p.ref as product_ref, p.fk_product_type as fk_product_type, p.label as label, p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet_rec as l';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
		$sql.= ' WHERE l.fk_facture = '.$this->id;

		dol_syslog('Facture::fetch_lines', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);
				$line = new FactureLigne($this->db);

				$line->rowid	        = $objp->rowid;
				$line->desc             = $objp->description;     // Description line
				$line->product_type     = $objp->product_type;	// Type of line
				$line->product_ref      = $objp->product_ref;     // Ref product
				$line->libelle          = $objp->label;           // Label product
				$line->product_desc     = $objp->product_desc;    // Description product
				$line->fk_product_type  = $objp->fk_product_type;	// Type of product
				$line->qty              = $objp->qty;
				$line->subprice         = $objp->subprice;
				$line->tva_tx           = $objp->tva_tx;
				$line->remise_percent   = $objp->remise_percent;
				$line->fk_remise_except = $objp->fk_remise_except;
				$line->fk_product       = $objp->fk_product;
				$line->date_start       = $objp->date_start;
				$line->date_end         = $objp->date_end;
				$line->date_start       = $objp->date_start;
				$line->date_end         = $objp->date_end;
				$line->info_bits        = $objp->info_bits;
				$line->total_ht         = $objp->total_ht;
				$line->total_tva        = $objp->total_tva;
				$line->total_ttc        = $objp->total_ttc;
				$line->export_compta    = $objp->fk_export_compta;
				$line->code_ventilation = $objp->fk_code_ventilation;
				$line->rang 			= $objp->rang;
				$line->special_code 	= $objp->special_code;

				// Ne plus utiliser
				$line->price            = $objp->price;
				$line->remise           = $objp->remise;

				$this->lines[$i] = $line;

				$i++;
			}

			$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog('Facture::fetch_lines: Error '.$this->error, LOG_ERR);
			return -3;
		}
	}


	/**
	 * 		Delete current invoice
	 * 		@return		int		<0 if KO, >0 if OK
	 */
	function delete()
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."facturedet_rec WHERE fk_facture = ".$this->id;
		dol_syslog($sql);
		if ($this->db->query($sql))
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."facture_rec WHERE rowid = ".$this->id;
			dol_syslog($sql);
			if ($this->db->query($sql))
			{
				return 1;
			}
			else
			{
				$this->error=$this->db->lasterror();
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -2;
		}
	}


	/**
	 *		\brief		Add a line to invoice
	 */
	function addline($facid, $desc, $pu_ht, $qty, $txtva, $fk_product=0, $remise_percent=0, $price_base_type='HT', $info_bits=0, $fk_remise_except='', $pu_ttc=0, $type=0, $rang=-1, $special_code=0)
	{
		dol_syslog("FactureRec::addline facid=$facid,desc=$desc,pu_ht=$pu_ht,qty=$qty,txtva=$txtva,fk_product=$fk_product,remise_percent=$remise_percent,date_start=$date_start,date_end=$date_end,ventil=$ventil,info_bits=$info_bits,fk_remise_except=$fk_remise_except,price_base_type=$price_base_type,pu_ttc=$pu_ttc,type=$type", LOG_DEBUG);
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

		// Check parameters
		if ($type < 0) return -1;

		if ($this->brouillon)
		{
			// Clean parameters
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
			$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, 0, 0, $price_base_type, $info_bits);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];

			// TODO A virer
			// Anciens indicateurs: $price, $remise (a ne plus utiliser)
			if (trim(dol_strlen($remise_percent)) > 0)
			{
				$remise = round(($pu * $remise_percent / 100), 2);
				$price = $pu - $remise;
			}

			$product_type=$type;
			if ($fk_product)
			{
				$product=new Product($this->db);
				$result=$product->fetch($fk_product);
				$product_type=$product->type;
			}

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."facturedet_rec (";
			$sql.= "fk_facture";
			$sql.= ", description";
			$sql.= ", price";
			$sql.= ", qty";
			$sql.= ", tva_tx";
			$sql.= ", fk_product";
			$sql.= ", product_type";
			$sql.= ", remise_percent";
			$sql.= ", subprice";
			$sql.= ", remise";
			$sql.= ", total_ht";
			$sql.= ", total_tva";
			$sql.= ", total_ttc";
			$sql.= ", rang";
			$sql.= ", special_code";
			$sql.= ") VALUES (";
			$sql.= "'".$facid."'";
			$sql.= ", '".$this->db->escape($desc)."'";
			$sql.= ", ".price2num($price);
			$sql.= ", ".price2num($qty);
			$sql.= ", ".price2num($txtva);
			$sql.= ", ".($fk_product?"'".$fk_product."'":"null");
			$sql.= ", ".$product_type;
			$sql.= ", '".price2num($remise_percent)."'";
			$sql.= ", '".price2num($pu_ht)."'";
			$sql.= ", '".price2num($remise)."'";
			$sql.= ", '".price2num($total_ht)."'";
			$sql.= ", '".price2num($total_tva)."'";
			$sql.= ", '".price2num($total_ttc)."'";
			$sql.= ", ".$rang;
			$sql.= ", ".$special_code.")";

			dol_syslog("FactureRec::addline sql=".$sql, LOG_DEBUG);
			if ($this->db->query( $sql))
			{
				$this->id=$facid;	// TODO A virer
				$this->update_price();
				return 1;
			}
			else
			{
				$this->error=$this->db->lasterror();
				dol_syslog("FactureRec::addline sql=".$this->error, LOG_ERR);
				return -1;
			}
		}
	}


	/**
	 *		Rend la facture automatique
	 *		@param		user
	 *		@param		freq
	 *		@param		courant
	 */
	function set_auto($user, $freq, $courant)
	{
		if ($user->rights->facture->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."facture_rec ";
			$sql .= " SET frequency = '".$freq."', last_gen='".$courant."'";
			$sql .= " WHERE rowid = ".$this->id;

			$resql = $this->db->query($sql);

			if ($resql)
			{
				$this->frequency 	= $freq;
				$this->last_gen 	= $courant;
				return 0;
			}
			else
			{
				dol_print_error($this->db);
				return -1;
			}
		}
		else
		{
			return -2;
		}
	}

	/**
	 *	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *	\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *	\param		option			Sur quoi pointe le lien ('', 'withdraw')
	 *	\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/compta/facture/fiche-rec.php?facid='.$this->id.'">';
		$lienfin='</a>';

		$picto='bill';

		$label=$langs->trans("ShowInvoice").': '.$this->ref;

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
		return $result;
	}

}
?>
