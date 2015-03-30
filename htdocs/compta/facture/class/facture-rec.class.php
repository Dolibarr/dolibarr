<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';


/**
 *	Classe de gestion des factures recurrentes/Modeles
 */
class FactureRec extends Facture
{
	public $element='facturerec';
	public $table_element='facture_rec';
	public $table_element_line='facturedet_rec';
	public $fk_element='fk_facture';

	var $id;

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
	var $note_private;
	var $note_public;
	var $db_table;
	var $propalid;
	var $fk_project;

	var $rang;
	var $special_code;

	var $usenewprice=0;

	var $lines=array();


	/**
	 *	Constructor
	 *
	 * 	@param		DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * 	Create a predefined invoice
	 *
	 * 	@param		User	$user		User object
	 * 	@param		int		$facid		Id of source invoice
	 *	@return		int					<0 if KO, id of invoice if OK
	 */
	function create($user, $facid)
	{
		global $conf;

		$error=0;
		$now=dol_now();

		// Clean parameters
		$this->titre=trim($this->titre);
		$this->usenewprice=empty($this->usenewprice)?0:$this->usenewprice;
		
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
			$sql.= ", note_private";
			$sql.= ", note_public";
			$sql.= ", fk_user_author";
			$sql.= ", fk_projet";
			$sql.= ", fk_cond_reglement";
			$sql.= ", fk_mode_reglement";
			$sql.= ", usenewprice";
			$sql.= ") VALUES (";
			$sql.= "'".$this->titre."'";
			$sql.= ", ".$facsrc->socid;
			$sql.= ", ".$conf->entity;
			$sql.= ", '".$this->db->idate($now)."'";
			$sql.= ", ".(!empty($facsrc->amount)?$facsrc->amount:'0');
			$sql.= ", ".(!empty($facsrc->remise)?$this->remise:'0');
			$sql.= ", ".(!empty($this->note_private)?("'".$this->db->escape($this->note_private)."'"):"NULL");
			$sql.= ", ".(!empty($this->note_public)?("'".$this->db->escape($this->note_public)."'"):"NULL");
			$sql.= ", '".$user->id."'";
			$sql.= ", ".(! empty($facsrc->fk_project)?"'".$facsrc->fk_project."'":"null");
			$sql.= ", '".$facsrc->cond_reglement_id."'";
			$sql.= ", '".$facsrc->mode_reglement_id."'";
			$sql.= ", ".$this->usenewprice;
			$sql.= ")";

			if ($this->db->query($sql))
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."facture_rec");

				/*
				 * Lines
				 */
				$num=count($facsrc->lines);
				for ($i = 0; $i < $num; $i++)
				{
                    $result_insert = $this->addline(
                        $facsrc->lines[$i]->desc,
                        $facsrc->lines[$i]->subprice,
                        $facsrc->lines[$i]->qty,
                        $facsrc->lines[$i]->tva_tx,
                        $facsrc->lines[$i]->fk_product,
                        $facsrc->lines[$i]->remise_percent,
                        'HT',
                        0,
                        '',
                        0,
                        $facsrc->lines[$i]->product_type,
                        $facsrc->lines[$i]->rang,
                        $facsrc->lines[$i]->special_code,
                    	$facsrc->lines[$i]->label
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
	 *	Recupere l'objet facture et ses lignes de factures
	 *
	 *	@param	int		$rowid      Id de la facture a recuperer
	 *	@return int         		>0 si ok, <0 si ko
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT f.titre,f.fk_soc,f.amount,f.tva,f.total,f.total_ttc,f.remise_percent,f.remise_absolue,f.remise';
		$sql.= ', f.date_lim_reglement as dlr';
		$sql.= ', f.note_private, f.note_public, f.fk_user_author';
		$sql.= ', f.fk_mode_reglement, f.fk_cond_reglement';
		$sql.= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
		$sql.= ', c.code as cond_reglement_code, c.libelle as cond_reglement_libelle, c.libelle_facture as cond_reglement_libelle_doc';
		$sql.= ', el.fk_source';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_rec as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as c ON f.fk_cond_reglement = c.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON f.fk_mode_reglement = p.id';
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_target = f.rowid AND el.targettype = 'facture'";
		$sql.= ' WHERE f.rowid='.$rowid;

        dol_syslog("FactureRec::Fetch rowid=".$rowid."", LOG_DEBUG);
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
				$this->note_private           = $obj->note_private;
				$this->note_public            = $obj->note_public;
				$this->user_author            = $obj->fk_user_author;
				$this->modelpdf               = $obj->model_pdf;
				$this->rang					  = $obj->rang;
				$this->special_code			  = $obj->special_code;

				if ($this->statut == self::STATUS_DRAFT)	$this->brouillon = 1;

				/*
				 * Lines
				 */
				$result=$this->fetch_lines();
				if ($result < 0)
				{
					$this->error=$this->db->error();
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
			return -1;
		}
	}


	/**
	 *	Recupere les lignes de factures predefinies dans this->lines
	 *
	 *	@return     int         1 if OK, < 0 if KO
 	 */
	function fetch_lines()
	{
		$sql = 'SELECT l.rowid, l.fk_product, l.product_type, l.label as custom_label, l.description, l.price, l.qty, l.tva_tx, ';
		$sql.= ' l.remise, l.remise_percent, l.subprice,';
		$sql.= ' l.total_ht, l.total_tva, l.total_ttc,';
		$sql.= ' l.rang, l.special_code,';
		$sql.= ' p.ref as product_ref, p.fk_product_type as fk_product_type, p.label as product_label, p.description as product_desc';
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
				$line->label            = $objp->custom_label;		// Label line
				$line->desc             = $objp->description;		// Description line
				$line->product_type     = $objp->product_type;		// Type of line
				$line->product_ref      = $objp->product_ref;		// Ref product
				$line->libelle          = $objp->product_label;		// deprecated
				$line->product_label	= $objp->product_label;		// Label product
				$line->product_desc     = $objp->product_desc;		// Description product
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
			return -3;
		}
	}


	/**
	 * 		Delete current invoice
	 *
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
	 * 	Add a line to invoice
	 *
     *	@param    	string		$desc            	Description de la ligne
     *	@param    	double		$pu_ht              Prix unitaire HT (> 0 even for credit note)
     *	@param    	double		$qty             	Quantite
     *	@param    	double		$txtva           	Taux de tva force, sinon -1
     *	@param    	int			$fk_product      	Id du produit/service predefini
     *	@param    	double		$remise_percent  	Pourcentage de remise de la ligne
     *	@param		string		$price_base_type	HT or TTC
     *	@param    	int			$info_bits			Bits de type de lignes
     *	@param    	int			$fk_remise_except	Id remise
     *	@param    	double		$pu_ttc             Prix unitaire TTC (> 0 even for credit note)
     *	@param		int			$type				Type of line (0=product, 1=service)
     *	@param      int			$rang               Position of line
     *	@param		int			$special_code		Special code
     *	@param		string		$label				Label of the line
     *	@return    	int             				<0 if KO, Id of line if OK
	 */
	function addline($desc, $pu_ht, $qty, $txtva, $fk_product=0, $remise_percent=0, $price_base_type='HT', $info_bits=0, $fk_remise_except='', $pu_ttc=0, $type=0, $rang=-1, $special_code=0, $label='')
	{
		$facid=$this->id;

		dol_syslog("FactureRec::addline facid=$facid,desc=$desc,pu_ht=$pu_ht,qty=$qty,txtva=$txtva,fk_product=$fk_product,remise_percent=$remise_percent,date_start=$date_start,date_end=$date_end,ventil=$ventil,info_bits=$info_bits,fk_remise_except=$fk_remise_except,price_base_type=$price_base_type,pu_ttc=$pu_ttc,type=$type", LOG_DEBUG);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		// Check parameters
		if ($type < 0) return -1;

		if ($this->brouillon)
		{
			// Clean parameters
			$remise_percent=price2num($remise_percent);
			$qty=price2num($qty);
			if (! $qty) $qty=1;
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
			$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, 0, 0, $price_base_type, $info_bits, $type);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];

			$product_type=$type;
			if ($fk_product)
			{
				$product=new Product($this->db);
				$result=$product->fetch($fk_product);
				$product_type=$product->type;
			}

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."facturedet_rec (";
			$sql.= "fk_facture";
			$sql.= ", label";
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
			$sql.= ", ".(! empty($label)?"'".$this->db->escape($label)."'":"null");
			$sql.= ", '".$this->db->escape($desc)."'";
			$sql.= ", ".price2num($pu_ht);
			$sql.= ", ".price2num($qty);
			$sql.= ", ".price2num($txtva);
			$sql.= ", ".(! empty($fk_product)?"'".$fk_product."'":"null");
			$sql.= ", ".$product_type;
			$sql.= ", '".price2num($remise_percent)."'";
			$sql.= ", '".price2num($pu_ht)."'";
			$sql.= ", null";
			$sql.= ", '".price2num($total_ht)."'";
			$sql.= ", '".price2num($total_tva)."'";
			$sql.= ", '".price2num($total_ttc)."'";
			$sql.= ", ".$rang;
			$sql.= ", ".$special_code.")";

			dol_syslog(get_class($this)."::addline", LOG_DEBUG);
			if ($this->db->query($sql))
			{
				$this->id=$facid;
				$this->update_price();
				return 1;
			}
			else
			{
				$this->error=$this->db->lasterror();
				return -1;
			}
		}
	}


	/**
	 *	Rend la facture automatique
	 *
	 *	@param		User	$user		User object
	 *	@param		int		$freq		Freq
	 *	@param		string	$courant	Courant
	 *	@return		int					0 if OK, <0 if KO
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
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param		int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param		string	$option			Sur quoi pointe le lien ('', 'withdraw')
	 *	@return		string					Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';
        $label=$langs->trans("ShowInvoice").': '.$this->ref;

        $link = '<a href="'.DOL_URL_ROOT.'/compta/facture/fiche-rec.php?facid='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

		$picto='bill';


        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$link.$this->ref.$linkend;
		return $result;
	}

	
	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *	@param	string		$option		''=Create a specimen invoice with lines, 'nolines'=No lines
	 *  @return	void
	 */
	function initAsSpecimen($option='')
	{
		global $user,$langs,$conf;

		$now=dol_now();
		$arraynow=dol_getdate($now);
		$nownotime=dol_mktime(0, 0, 0, $arraynow['mon'], $arraynow['mday'], $arraynow['year']);

		parent::initAsSpecimen($option);

		$this->usenewprice = 1;
	}
		
}
