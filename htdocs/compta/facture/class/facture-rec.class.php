<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012       Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 *	Classe de gestion des factures recurrentes/Modeles
 */
class FactureRec extends CommonInvoice
{
	public $element='facturerec';
	public $table_element='facture_rec';
	public $table_element_line='facturedet_rec';
	public $fk_element='fk_facture';
	public $picto='bill';
	
	var $entity;
	var $number;
	var $date;
	var $amount;
	var $remise;
	var $tva;
	var $total;
	var $db_table;
	var $propalid;

	var $date_last_gen;
	var $date_when;
	var $nb_gen_done;
	var $nb_gen_max;
	
	var $rang;
	var $special_code;

	var $usenewprice=0;

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
	 *	@return		int					<0 if KO, id of invoice created if OK
	 */
	function create($user, $facid)
	{
		global $conf;

		$error=0;
		$now=dol_now();

		// Clean parameters
		$this->titre=trim($this->titre);
		$this->usenewprice=empty($this->usenewprice)?0:$this->usenewprice;
		
		// No frequency defined then no next date to execution
		if (empty($this->frequency)) 
		{
			$this->frequency=0;
			$this->date_when=NULL;
		}
		
		
		$this->frequency=abs($this->frequency);
		$this->nb_gen_done=0;
		$this->nb_gen_max=empty($this->nb_gen_max)?0:$this->nb_gen_max;
		$this->auto_validate=empty($this->auto_validate)?0:$this->auto_validate;
		
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
			$sql.= ", fk_account";
			$sql.= ", fk_cond_reglement";
			$sql.= ", fk_mode_reglement";
			$sql.= ", usenewprice";
			$sql.= ", frequency";
			$sql.= ", unit_frequency";
			$sql.= ", date_when";
			$sql.= ", date_last_gen";
			$sql.= ", nb_gen_done";
			$sql.= ", nb_gen_max";
			$sql.= ", auto_validate";
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
			$sql.= ", ".(! empty($facsrc->fk_account)?"'".$facsrc->fk_account."'":"null");
			$sql.= ", '".$facsrc->cond_reglement_id."'";
			$sql.= ", '".$facsrc->mode_reglement_id."'";
			$sql.= ", ".$this->usenewprice;
			$sql.= ", ".$this->frequency;
			$sql.= ", '".$this->db->escape($this->unit_frequency)."'";
			$sql.= ", ".(!empty($this->date_when)?"'".$this->db->idate($this->date_when)."'":'NULL');
			$sql.= ", ".(!empty($this->date_last_gen)?"'".$this->db->idate($this->date_last_gen)."'":'NULL');
			$sql.= ", ".$this->nb_gen_done;
			$sql.= ", ".$this->nb_gen_max;
			$sql.= ", ".$this->auto_validate;
			$sql.= ")";

			if ($this->db->query($sql))
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."facture_rec");

				// Add lines
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
                    	$facsrc->lines[$i]->label,
	                    $facsrc->lines[$i]->fk_unit
                    );

					if ($result_insert < 0)
					{
						$error++;
					}
				}
				
			    // Add object linked
			    if (! $error && $this->id && is_array($this->linked_objects) && ! empty($this->linked_objects))
			    {
			        foreach($this->linked_objects as $origin => $origin_id)
			        {
			            $ret = $this->add_object_linked($origin, $origin_id);
			            if (! $ret)
			            {
			                $this->error=$this->db->lasterror();
			                $error++;
			            }
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
			    $this->error=$this->db->lasterror();
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
	 *	Load object and lines
	 *
	 *	@param      int		$rowid       	Id of object to load
	 * 	@param		string	$ref			Reference of recurring invoice
	 * 	@param		string	$ref_ext		External reference of invoice
	 * 	@param		int		$ref_int		Internal reference of other object
	 *	@return     int         			>0 if OK, <0 if KO, 0 if not found
	 */
	function fetch($rowid, $ref='', $ref_ext='', $ref_int='')
	{
		$sql = 'SELECT f.rowid, f.entity, f.titre, f.fk_soc, f.amount, f.tva, f.total, f.total_ttc, f.remise_percent, f.remise_absolue, f.remise';
		$sql.= ', f.date_lim_reglement as dlr';
		$sql.= ', f.note_private, f.note_public, f.fk_user_author';
		$sql.= ', f.fk_mode_reglement, f.fk_cond_reglement, f.fk_projet';
		$sql.= ', f.fk_account';
		$sql.= ', f.frequency, f.unit_frequency, f.date_when, f.date_last_gen, f.nb_gen_done, f.nb_gen_max, f.usenewprice, f.auto_validate';
		$sql.= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
		$sql.= ', c.code as cond_reglement_code, c.libelle as cond_reglement_libelle, c.libelle_facture as cond_reglement_libelle_doc';
		//$sql.= ', el.fk_source';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_rec as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as c ON f.fk_cond_reglement = c.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON f.fk_mode_reglement = p.id';
		//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_target = f.rowid AND el.targettype = 'facture'";
		if ($rowid) $sql.= ' WHERE f.rowid='.$rowid;
		elseif ($ref) $sql.= " WHERE f.titre='".$this->db->escape($ref)."'";
		/* This field are not used for template invoice
		if ($ref_ext) $sql.= " AND f.ref_ext='".$this->db->escape($ref_ext)."'";
		if ($ref_int) $sql.= " AND f.ref_int='".$this->db->escape($ref_int)."'";
		*/
		
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id                     = $obj->rowid;
				$this->entity                 = $obj->entity;
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
				$this->fk_account             = $obj->fk_account;
				$this->fk_facture_source      = $obj->fk_facture_source;
				$this->note_private           = $obj->note_private;
				$this->note_public            = $obj->note_public;
				$this->user_author            = $obj->fk_user_author;
				$this->modelpdf               = $obj->model_pdf;
				$this->rang					  = $obj->rang;
				$this->special_code			  = $obj->special_code;
				$this->frequency			  = $obj->frequency;
				$this->unit_frequency		  = $obj->unit_frequency;
				$this->date_when			  = $this->db->jdate($obj->date_when);
				$this->date_last_gen		  = $this->db->jdate($obj->date_last_gen);
				$this->nb_gen_done			  = $obj->nb_gen_done;
				$this->nb_gen_max			  = $obj->nb_gen_max;
				$this->usenewprice			  = $obj->usenewprice;
				$this->auto_validate		  = $obj->auto_validate;

				if ($this->statut == self::STATUS_DRAFT)	$this->brouillon = 1;

				/*
				 * Lines
				 */
				$result=$this->fetch_lines();
				if ($result < 0)
				{
					$this->error=$this->db->lasterror();
					return -3;
				}
				return 1;
			}
			else
			{
				$this->error='Bill with id '.$rowid.' or ref '.$ref.' not found sql='.$sql;
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
	 * 	Create an array of invoice lines
	 *
	 * 	@return int		>0 if OK, <0 if KO
	 */
	function getLinesArray()
	{
	    return $this->fetch_lines();
	}
	
	
	/**
	 *	Recupere les lignes de factures predefinies dans this->lines
	 *
	 *	@return     int         1 if OK, < 0 if KO
 	 */
	function fetch_lines()
	{
		$this->lines=array();

		$sql = 'SELECT l.rowid, l.fk_product, l.product_type, l.label as custom_label, l.description, l.product_type, l.price, l.qty, l.tva_tx, ';
		$sql.= ' l.remise, l.remise_percent, l.subprice,';
		$sql.= ' l.info_bits, l.total_ht, l.total_tva, l.total_ttc,';
		//$sql.= ' l.situation_percent, l.fk_prev_id,';
		//$sql.= ' l.localtax1_tx, l.localtax2_tx, l.localtax1_type, l.localtax2_type, l.remise_percent, l.fk_remise_except, l.subprice,';
		$sql.= ' l.rang, l.special_code,';
		//$sql.= ' l.info_bits, l.total_ht, l.total_tva, l.total_localtax1, l.total_localtax2, l.total_ttc, l.fk_code_ventilation, l.fk_product_fournisseur_price as fk_fournprice, l.buy_price_ht as pa_ht,';
		$sql.= ' l.fk_unit, l.fk_contract_line,';
		//$sql.= ' l.fk_multicurrency, l.multicurrency_code, l.multicurrency_subprice, l.multicurrency_total_ht, l.multicurrency_total_tva, l.multicurrency_total_ttc,';
		$sql.= ' p.ref as product_ref, p.fk_product_type as fk_product_type, p.label as product_label, p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet_rec as l';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
		$sql.= ' WHERE l.fk_facture = '.$this->id;
		$sql.= ' ORDER BY l.rang';
		
		dol_syslog('FactureRec::fetch_lines', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);
				$line = new FactureLigne($this->db);

				$line->id	            = $objp->rowid;
				$line->rowid	        = $objp->rowid;
				$line->label            = $objp->custom_label;		// Label line
				$line->desc             = $objp->description;		// Description line
				$line->description      = $objp->description;		// Description line
				$line->product_type     = $objp->product_type;		// Type of line
				$line->ref              = $objp->product_ref;		// Ref product
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
				$line->info_bits        = $objp->info_bits;
				$line->total_ht         = $objp->total_ht;
				$line->total_tva        = $objp->total_tva;
				$line->total_ttc        = $objp->total_ttc;
				$line->code_ventilation = $objp->fk_code_ventilation;
				$line->rang 			= $objp->rang;
				$line->special_code 	= $objp->special_code;
				$line->fk_unit          = $objp->fk_unit;
                $line->fk_contract_line = $objp->fk_contract_line;
                
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
			$this->error=$this->db-lasterror();
			return -3;
		}
	}


	/**
	 * 	Delete template invoice
	 *
	 *	@param     	User	$user          	User that delete.
	 *	@param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@param		int		$idwarehouse	Id warehouse to use for stock change.
	 *	@return		int						<0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0, $idwarehouse=-1)
	{
	    $rowid=$this->id;
	    
	    dol_syslog(get_class($this)."::delete rowid=".$rowid, LOG_DEBUG);
	    
        $error=0;
		$this->db->begin();
		
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."facturedet_rec WHERE fk_facture = ".$rowid;
		dol_syslog($sql);
		if ($this->db->query($sql))
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."facture_rec WHERE rowid = ".$rowid;
			dol_syslog($sql);
			if (! $this->db->query($sql))
			{
				$this->error=$this->db->lasterror();
				$error=-1;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$error=-2;
		}
		
		if (! $error)
		{
		    $this->db->commit();
		    return 1;
		}
		else
		{
	        $this->db->rollback();
	        return $error;
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
     *	@param		string		$fk_unit			Unit
     *	@return    	int             				<0 if KO, Id of line if OK
	 */
	function addline($desc, $pu_ht, $qty, $txtva, $fk_product=0, $remise_percent=0, $price_base_type='HT', $info_bits=0, $fk_remise_except='', $pu_ttc=0, $type=0, $rang=-1, $special_code=0, $label='', $fk_unit=null)
	{
	    global $mysoc;
	    
		$facid=$this->id;

		dol_syslog(get_class($this)."::addline facid=$facid,desc=$desc,pu_ht=$pu_ht,qty=$qty,txtva=$txtva,fk_product=$fk_product,remise_percent=$remise_percent,info_bits=$info_bits,fk_remise_except=$fk_remise_except,price_base_type=$price_base_type,pu_ttc=$pu_ttc,type=$type,fk_unit=$fk_unit", LOG_DEBUG);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		// Check parameters
		if ($type < 0) return -1;

		if ($this->brouillon)
		{
			// Clean parameters
			$remise_percent=price2num($remise_percent);
			if (empty($remise_percent)) $remise_percent=0;
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
			$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, 0, 0, $price_base_type, $info_bits, $type, $mysoc);
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
			$sql.= ", fk_unit";
			$sql.= ") VALUES (";
			$sql.= "'".$facid."'";
			$sql.= ", ".(! empty($label)?"'".$this->db->escape($label)."'":"null");
			$sql.= ", '".$this->db->escape($desc)."'";
			$sql.= ", ".price2num($pu_ht);
			$sql.= ", ".price2num($qty);
			$sql.= ", ".price2num($txtva);
			$sql.= ", ".(! empty($fk_product)?"'".$fk_product."'":"null");
			$sql.= ", ".$product_type;
			$sql.= ", ".price2num($remise_percent);
			$sql.= ", ".price2num($pu_ht);
			$sql.= ", null";
			$sql.= ", ".price2num($total_ht);
			$sql.= ", ".price2num($total_tva);
			$sql.= ", ".price2num($total_ttc);
			$sql.= ", ".$rang;
			$sql.= ", ".$special_code;
			$sql.= ", ".($fk_unit?"'".$this->db->escape($fk_unit)."'":"null").")";

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
	 * 	Update a line to invoice
	 *
	 *  @param     	int			$rowid           	Id of line to update
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
	 *	@param		string		$fk_unit			Unit
	 *	@return    	int             				<0 if KO, Id of line if OK
	 */
	function updateline($rowid, $desc, $pu_ht, $qty, $txtva, $fk_product=0, $remise_percent=0, $price_base_type='HT', $info_bits=0, $fk_remise_except='', $pu_ttc=0, $type=0, $rang=-1, $special_code=0, $label='', $fk_unit=null)
	{
	    global $mysoc;
	     
	    $facid=$this->id;
	
	    dol_syslog(get_class($this)."::updateline facid=".$facid." rowid=$rowid,desc=$desc,pu_ht=$pu_ht,qty=$qty,txtva=$txtva,fk_product=$fk_product,remise_percent=$remise_percent,info_bits=$info_bits,fk_remise_except=$fk_remise_except,price_base_type=$price_base_type,pu_ttc=$pu_ttc,type=$type,fk_unit=$fk_unit", LOG_DEBUG);
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
	        $tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, 0, 0, $price_base_type, $info_bits, $type, $mysoc);
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
	
	        $sql = "UPDATE ".MAIN_DB_PREFIX."facturedet_rec SET ";
	        $sql.= "fk_facture = '".$facid."'";
	        $sql.= ", label=".(! empty($label)?"'".$this->db->escape($label)."'":"null");
	        $sql.= ", description='".$this->db->escape($desc)."'";
	        $sql.= ", price=".price2num($pu_ht);
	        $sql.= ", qty=".price2num($qty);
	        $sql.= ", tva_tx=".price2num($txtva);
	        $sql.= ", fk_product=".(! empty($fk_product)?"'".$fk_product."'":"null");
	        $sql.= ", product_type=".$product_type;
	        $sql.= ", remise_percent='".price2num($remise_percent)."'";
	        $sql.= ", subprice='".price2num($pu_ht)."'";
	        $sql.= ", total_ht='".price2num($total_ht)."'";
	        $sql.= ", total_tva='".price2num($total_tva)."'";
	        $sql.= ", total_ttc='".price2num($total_ttc)."'";
	        $sql.= ", rang=".$rang;
	        $sql.= ", special_code=".$special_code;
	        $sql.= ", fk_unit=".($fk_unit?"'".$this->db->escape($fk_unit)."'":"null");
	        $sql.= " WHERE rowid = ".$rowid;
	        
	        dol_syslog(get_class($this)."::updateline", LOG_DEBUG);
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
	 * Return the next date of 
	 * 
	 * @return	timestamp	false if KO, timestamp if OK
	 */
	function getNextDate()
	{
		if (empty($this->date_when)) return false;
		return dol_time_plus_duree($this->date_when, $this->frequency, $this->unit_frequency);
	}
	
	/**
	 *  Create all recurrents invoices (for all entities if multicompany is used).
	 *  A result may also be provided into this->output.
	 *  
	 *  WARNING: This method change context $conf->entity to be in correct context for each recurring invoice found. 
	 * 
	 *  @return	int						0 if OK, < 0 if KO (this function is used also by cron so only 0 is OK) 
	 */
	function createRecurringInvoices()
	{
		global $conf, $langs, $db, $user;
		
		$langs->load("bills");
		
		$nb_create=0;
		
		$now = dol_now();
		$tmparray=dol_getdate($now);
		$today = dol_mktime(23,59,59,$tmparray['mon'],$tmparray['mday'],$tmparray['year']);   // Today is last second of current day
		
		dol_syslog("createRecurringInvoices");
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'facture_rec';
		$sql.= ' WHERE frequency > 0';      // A recurring invoice is an invoice with a frequency
		$sql.= " AND (date_when IS NULL OR date_when <= '".$db->idate($today)."')";
		$sql.= ' AND (nb_gen_done < nb_gen_max OR nb_gen_max = 0)';
		$sql.= $db->order('entity', 'ASC');
		//print $sql;exit;
		
		$resql = $db->query($sql);
		if ($resql)
		{
		    $i=0;
		    $num = $db->num_rows($resql);
		    
		    if ($num) $this->output.=$langs->trans("FoundXQualifiedRecurringInvoiceTemplate", $num)."\n";
		    else $this->output.=$langs->trans("NoQualifiedRecurringInvoiceTemplateFound");
		    
		    $saventity = $conf->entity;
		
		    while ($i < $num)     // Loop on each template invoice
			{
			    $line = $db->fetch_object($resql);

			    $db->begin();
			    
				$facturerec = new FactureRec($db);
				$facturerec->fetch($line->rowid);
			
				// Set entity context
				$conf->entity = $facturerec->entity;
				
				dol_syslog("createRecurringInvoices Process invoice template id=".$facturerec->id.", ref=".$facturerec->ref.", entity=".$facturerec->entity);

			    $error=0;

			    $facture = new Facture($db);
				$facture->fac_rec = $facturerec->id;    // We will create $facture from this recurring invoice
			    $facture->type = self::TYPE_STANDARD;
			    $facture->brouillon = 1;
			    $facture->date = $facturerec->date_when;	// We could also use dol_now here but we prefer date_when so invoice has real date when we would like even if we generate later.
			    $facture->socid = $facturerec->socid;
			    
			    $invoiceidgenerated = $facture->create($user);
			    if ($invoiceidgenerated <= 0)
			    {
			        $this->errors = $facture->errors;
			        $this->error = $facture->error;
			        $error++;
			    }
			    if (! $error && $facturerec->auto_validate)
			    {
			        $result = $facture->validate($user);
			        if ($result <= 0)
			        {
    			        $this->errors = $facture->errors;
    			        $this->error = $facture->error;
			            $error++;
			        }
			    }

				if (! $error && $invoiceidgenerated >= 0)
				{
					$db->commit("createRecurringInvoices Process invoice template id=".$facturerec->id.", ref=".$facturerec->ref);
					dol_syslog("createRecurringInvoices Process invoice template ".$facturerec->ref." is finished with a success generation");
					$nb_create++;
					$this->output.=$langs->trans("InvoiceGeneratedFromTemplate", $facture->ref, $facturerec->ref)."\n";
				}
				else
				{
				    $db->rollback("createRecurringInvoices Process invoice template id=".$facturerec->id.", ref=".$facturerec->ref);
				}

				$i++;
			}
			
			$conf->entity = $saventity;      // Restore entity context
		}
		else dol_print_error($db);
		
		$this->output=trim($this->output);
		
		return $error?$error:0;
	}
	
	/**
	 *	Return clicable name (with picto eventually)
	 *
	 * @param	int		$withpicto       Add picto into link
	 * @param  string	$option          Where point the link
	 * @param  int		$max             Maxlength of ref
	 * @param  int		$short           1=Return just URL
	 * @param  string   $moretitle       Add more text to title tooltip
	 * @return string 			         String with URL
	 */
	function getNomUrl($withpicto=0,$option='',$max=0,$short=0,$moretitle='')
	{
		global $langs;

		$result='';
        $label=$langs->trans("ShowInvoice").': '.$this->ref;
        
        $url = DOL_URL_ROOT.'/compta/facture/fiche-rec.php?facid='.$this->id;
        
        if ($short) return $url;
        
		$picto='bill';
        
		$link = '<a href="'.$url.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';



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

        // Load array of products prodids
		$num_prods = 0;
		$prodids = array();
		
		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."product";
		$sql.= " WHERE entity IN (".getEntity('product', 1).")";
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

		// Initialize parameters
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$this->socid = 1;
		$this->date = $nownotime;
		$this->date_lim_reglement = $nownotime + 3600 * 24 *30;
		$this->cond_reglement_id   = 1;
		$this->cond_reglement_code = 'RECEP';
		$this->date_lim_reglement=$this->calculate_date_lim_reglement();
		$this->mode_reglement_id   = 0;		// Not forced to show payment mode CHQ + VIR
		$this->mode_reglement_code = '';	// Not forced to show payment mode CHQ + VIR
		$this->note_public='This is a comment (public)';
		$this->note_private='This is a comment (private)';
		$this->note='This is a comment (private)';
		$this->fk_incoterms=0;
		$this->location_incoterms='';

		if (empty($option) || $option != 'nolines')
		{
			// Lines
			$nbp = 5;
			$xnbp = 0;
			while ($xnbp < $nbp)
			{
				$line=new FactureLigne($this->db);
				$line->desc=$langs->trans("Description")." ".$xnbp;
				$line->qty=1;
				$line->subprice=100;
				$line->tva_tx=19.6;
				$line->localtax1_tx=0;
				$line->localtax2_tx=0;
				$line->remise_percent=0;
				if ($xnbp == 1)        // Qty is negative (product line)
				{
					$prodid = mt_rand(1, $num_prods);
					$line->fk_product=$prodids[$prodid];
					$line->qty=-1;
					$line->total_ht=-100;
					$line->total_ttc=-119.6;
					$line->total_tva=-19.6;
				}
				else if ($xnbp == 2)    // UP is negative (free line)
				{
					$line->subprice=-100;
					$line->total_ht=-100;
					$line->total_ttc=-119.6;
					$line->total_tva=-19.6;
					$line->remise_percent=0;
				}
				else if ($xnbp == 3)    // Discount is 50% (product line)
				{
					$prodid = mt_rand(1, $num_prods);
					$line->fk_product=$prodids[$prodid];
					$line->total_ht=50;
					$line->total_ttc=59.8;
					$line->total_tva=9.8;
					$line->remise_percent=50;
				}
				else    // (product line)
				{
					$prodid = mt_rand(1, $num_prods);
					$line->fk_product=$prodids[$prodid];
					$line->total_ht=100;
					$line->total_ttc=119.6;
					$line->total_tva=19.6;
					$line->remise_percent=00;
				}

				$this->lines[$xnbp]=$line;
				$xnbp++;

				$this->total_ht       += $line->total_ht;
				$this->total_tva      += $line->total_tva;
				$this->total_ttc      += $line->total_ttc;
			}
			$this->revenuestamp = 0;

			// Add a line "offered"
			$line=new FactureLigne($this->db);
			$line->desc=$langs->trans("Description")." (offered line)";
			$line->qty=1;
			$line->subprice=100;
			$line->tva_tx=19.6;
			$line->localtax1_tx=0;
			$line->localtax2_tx=0;
			$line->remise_percent=100;
			$line->total_ht=0;
			$line->total_ttc=0;    // 90 * 1.196
			$line->total_tva=0;
			$prodid = mt_rand(1, $num_prods);
			$line->fk_product=$prodids[$prodid];

			$this->lines[$xnbp]=$line;
			$xnbp++;
		}
		
		$this->usenewprice = 1;
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old thirdparty id
	 * @param int $dest_id New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'facture_rec'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}
	
	/**
     *	Update frequency and unit
     *
     *	@param     	int		$frequency		value of frequency
	 *	@param     	string	$unit 			unit of frequency  (d, m, y)
     *	@return		int						<0 if KO, >0 if OK
     */
    function setFrequencyAndUnit($frequency,$unit)
    {
        if (! $this->table_element)
        {
            dol_syslog(get_class($this)."::setFrequencyAndUnit was called on objet with property table_element not defined",LOG_ERR);
            return -1;
        }

		if (!empty($frequency) && empty($unit))
        {
            dol_syslog(get_class($this)."::setFrequencyAndUnit was called on objet with params frequency defined but unit not defined",LOG_ERR);
            return -2;
        }

        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
        $sql.= ' SET frequency = '.($frequency?$this->db->escape($frequency):'null');
        if (!empty($unit)) 
        {
        	$sql.= ', unit_frequency = \''.$this->db->escape($unit).'\'';
		}
        $sql.= ' WHERE rowid = '.$this->id;
        
        dol_syslog(get_class($this)."::setFrequencyAndUnit", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            $this->frequency = $frequency;
			if (!empty($unit)) $this->unit_frequency = $unit;
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }
    
	/**
     *	Update the next date of execution
     *
     *	@param     	datetime	$date					date of execution
     *	@param     	int			$increment_nb_gen_done	0 do nothing more, >0 increment nb_gen_done
     *	@return		int									<0 if KO, >0 if OK
     */
    function setNextDate($date, $increment_nb_gen_done=0)
    {
        if (! $this->table_element)
        {
            dol_syslog(get_class($this)."::setNextDate was called on objet with property table_element not defined",LOG_ERR);
            return -1;
        }
        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
        $sql.= " SET date_when = ".($date ? "'".$this->db->idate($date)."'" : "null");
        if ($increment_nb_gen_done>0) $sql.= ', nb_gen_done = nb_gen_done + 1';
        $sql.= ' WHERE rowid = '.$this->id;

        dol_syslog(get_class($this)."::setNextDate", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            $this->date_when = $date;
            if ($increment_nb_gen_done>0) $this->nb_gen_done++;
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }
	
	/**
     *	Update the maximum period
     *
     *	@param     	int		$nb		number of maximum period
     *	@return		int				<0 if KO, >0 if OK
     */
    function setMaxPeriod($nb)
    {
        if (! $this->table_element)
        {
            dol_syslog(get_class($this)."::setMaxPeriod was called on objet with property table_element not defined",LOG_ERR);
            return -1;
        }
		
        if (empty($nb)) $nb=0;
        
        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
        $sql.= ' SET nb_gen_max = '.$nb;
        $sql.= ' WHERE rowid = '.$this->id;

        dol_syslog(get_class($this)."::setMaxPeriod", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            $this->nb_gen_max = $nb;
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }
	
	/**
     *	Update the auto validate invoice
     *
     *	@param     	int		$validate		0 to create in draft, 1 to create and validate invoice
     *	@return		int						<0 if KO, >0 if OK
     */
    function setAutoValidate($validate)
    {
        if (! $this->table_element)
        {
            dol_syslog(get_class($this)."::setAutoValidate was called on objet with property table_element not defined",LOG_ERR);
            return -1;
        }
		
        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
        $sql.= ' SET auto_validate = '.$validate;
        $sql.= ' WHERE rowid = '.$this->id;

        dol_syslog(get_class($this)."::setAutoValidate", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            $this->auto_validate = $validate;
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }
}



/**
 *	Class to manage invoice lines of templates.
 *  Saved into database table llx_facturedet_rec
 */
class FactureLigneRec extends CommonInvoiceLine
{
    
    /**
     * 	Delete line in database
     *
     *	@return		int		<0 if KO, >0 if OK
     */
    function delete()
    {
        global $conf,$langs,$user;
    
        $error=0;
    
        $this->db->begin();
    
        // Call trigger
        /*$result=$this->call_trigger('LINEBILLREC_DELETE',$user);
        if ($result < 0)
        {
            $this->db->rollback();
            return -1;
        }*/
        // End call triggers
    
    
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."facturedet_rec WHERE rowid = ".($this->rowid > 0 ? $this->rowid : $this->id);
        dol_syslog(get_class($this)."::delete", LOG_DEBUG);
        if ($this->db->query($sql) )
        {
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
    
}
