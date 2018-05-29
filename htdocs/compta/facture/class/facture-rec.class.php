<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012       Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2017       Frédéric France         <frederic.france@netlogic.fr>
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
 *	Class to manage invoice templates
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

	var $frequency;
	var $unit_frequency;

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
		$this->generate_pdf = empty($this->generate_pdf)?0:$this->generate_pdf;

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
			$sql.= ", modelpdf";
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
			$sql.= ", generate_pdf";
			$sql.= ", fk_multicurrency";
			$sql.= ", multicurrency_code";
			$sql.= ", multicurrency_tx";
			$sql.= ") VALUES (";
			$sql.= "'".$this->db->escape($this->titre)."'";
			$sql.= ", ".$facsrc->socid;
			$sql.= ", ".$conf->entity;
			$sql.= ", '".$this->db->idate($now)."'";
			$sql.= ", ".(!empty($facsrc->amount)?$facsrc->amount:'0');
			$sql.= ", ".(!empty($facsrc->remise)?$this->remise:'0');
			$sql.= ", ".(!empty($this->note_private)?("'".$this->db->escape($this->note_private)."'"):"NULL");
			$sql.= ", ".(!empty($this->note_public)?("'".$this->db->escape($this->note_public)."'"):"NULL");
			$sql.= ", ".(!empty($this->modelpdf)?("'".$this->db->escape($this->modelpdf)."'"):"NULL");
			$sql.= ", '".$this->db->escape($user->id)."'";
			$sql.= ", ".(! empty($facsrc->fk_project)?"'".$facsrc->fk_project."'":"null");
			$sql.= ", ".(! empty($facsrc->fk_account)?"'".$facsrc->fk_account."'":"null");
			$sql.= ", ".($facsrc->cond_reglement_id > 0 ? $this->db->escape($facsrc->cond_reglement_id) : "null");
			$sql.= ", ".($facsrc->mode_reglement_id > 0 ? $this->db->escape($facsrc->mode_reglement_id) : "null");
			$sql.= ", ".$this->usenewprice;
			$sql.= ", ".$this->frequency;
			$sql.= ", '".$this->db->escape($this->unit_frequency)."'";
			$sql.= ", ".(!empty($this->date_when)?"'".$this->db->idate($this->date_when)."'":'NULL');
			$sql.= ", ".(!empty($this->date_last_gen)?"'".$this->db->idate($this->date_last_gen)."'":'NULL');
			$sql.= ", ".$this->nb_gen_done;
			$sql.= ", ".$this->nb_gen_max;
			$sql.= ", ".$this->auto_validate;
			$sql.= ", ".$this->generate_pdf;
			$sql.= ", ".$facsrc->fk_multicurrency;
			$sql.= ", '".$facsrc->multicurrency_code."'";
			$sql.= ", ".$facsrc->multicurrency_tx;
			$sql.= ")";

			if ($this->db->query($sql))
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."facture_rec");

				// Fields used into addline later
				$this->fk_multicurrency = $facsrc->fk_multicurrency;
				$this->multicurrency_code = $facsrc->multicurrency_code;
				$this->multicurrency_tx = $facsrc->multicurrency_tx;

				// Add lines
				$num=count($facsrc->lines);
				for ($i = 0; $i < $num; $i++)
				{
					$tva_tx = $facsrc->lines[$i]->tva_tx;
					if (! empty($facsrc->lines[$i]->vat_src_code) && ! preg_match('/\(/', $tva_tx)) $tva_tx .= ' ('.$facsrc->lines[$i]->vat_src_code.')';

					$result_insert = $this->addline(
                        $facsrc->lines[$i]->desc,
                        $facsrc->lines[$i]->subprice,
                        $facsrc->lines[$i]->qty,
						$tva_tx,
                        $facsrc->lines[$i]->localtax1_tx,
                        $facsrc->lines[$i]->localtax2_tx,
                        $facsrc->lines[$i]->fk_product,
                        $facsrc->lines[$i]->remise_percent,
                        'HT',
						$facsrc->lines[$i]->info_bits,
                        '',
                        0,
                        $facsrc->lines[$i]->product_type,
                        $facsrc->lines[$i]->rang,
                        $facsrc->lines[$i]->special_code,
                    	$facsrc->lines[$i]->label,
						$facsrc->lines[$i]->fk_unit,
						$facsrc->lines[$i]->multicurrency_subprice
                    );

					if ($result_insert < 0)
					{
						$error++;
					}
				}

				if (! empty($this->linkedObjectsIds) && empty($this->linked_objects))	// To use new linkedObjectsIds instead of old linked_objects
				{
					$this->linked_objects = $this->linkedObjectsIds;	// TODO Replace linked_objects with linkedObjectsIds
				}

				// Add object linked
				if (! $error && $this->id && is_array($this->linked_objects) && ! empty($this->linked_objects))
				{
					foreach($this->linked_objects as $origin => $tmp_origin_id)
					{
					    if (is_array($tmp_origin_id))       // New behaviour, if linked_object can have several links per type, so is something like array('contract'=>array(id1, id2, ...))
					    {
					        foreach($tmp_origin_id as $origin_id)
					        {
					            $ret = $this->add_object_linked($origin, $origin_id);
					            if (! $ret)
					            {
					                $this->error=$this->db->lasterror();
					                $error++;
					            }
					        }
					    }
					    else                                // Old behaviour, if linked_object has only one link per type, so is something like array('contract'=>id1))
					    {
					        $origin_id = $tmp_origin_id;
	    					$ret = $this->add_object_linked($origin, $origin_id);
	    					if (! $ret)
	    					{
	    						$this->error=$this->db->lasterror();
	    						$error++;
	    					}
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
		$sql = 'SELECT f.rowid, f.entity, f.titre, f.suspended, f.fk_soc, f.amount, f.tva, f.localtax1, f.localtax2, f.total, f.total_ttc';
		$sql.= ', f.remise_percent, f.remise_absolue, f.remise';
		$sql.= ', f.date_lim_reglement as dlr';
		$sql.= ', f.note_private, f.note_public, f.fk_user_author';
        $sql.= ', f.modelpdf';
		$sql.= ', f.fk_mode_reglement, f.fk_cond_reglement, f.fk_projet';
		$sql.= ', f.fk_account';
		$sql.= ', f.frequency, f.unit_frequency, f.date_when, f.date_last_gen, f.nb_gen_done, f.nb_gen_max, f.usenewprice, f.auto_validate';
        $sql.= ', f.generate_pdf';
        $sql.= ", f.fk_multicurrency, f.multicurrency_code, f.multicurrency_tx, f.multicurrency_total_ht, f.multicurrency_total_tva, f.multicurrency_total_ttc";
        $sql.= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
		$sql.= ', c.code as cond_reglement_code, c.libelle as cond_reglement_libelle, c.libelle_facture as cond_reglement_libelle_doc';
		//$sql.= ', el.fk_source';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_rec as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as c ON f.fk_cond_reglement = c.rowid AND c.entity IN ('.getEntity('c_payment_term').')';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON f.fk_mode_reglement = p.id AND p.entity IN ('.getEntity('c_paiement').')';
		//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_target = f.rowid AND el.targettype = 'facture'";
		$sql.= ' WHERE f.entity IN ('.getEntity('facture').')';
		if ($rowid) $sql.= ' AND f.rowid='.$rowid;
		elseif ($ref) $sql.= " AND f.titre='".$this->db->escape($ref)."'";
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
				$this->suspended              = $obj->suspended;
				$this->type                   = $obj->type;
				$this->datep                  = $obj->dp;
				$this->date                   = $obj->df;
				$this->amount                 = $obj->amount;
				$this->remise_percent         = $obj->remise_percent;
				$this->remise_absolue         = $obj->remise_absolue;
				$this->remise                 = $obj->remise;
				$this->total_ht               = $obj->total;
				$this->total_tva              = $obj->tva;
				$this->total_localtax1        = $obj->localtax1;
				$this->total_localtax2        = $obj->localtax2;
				$this->total_ttc              = $obj->total_ttc;
				$this->paye                   = $obj->paye;
				$this->close_code             = $obj->close_code;
				$this->close_note             = $obj->close_note;
				$this->socid                  = $obj->fk_soc;
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
				$this->modelpdf               = $obj->modelpdf;
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
				$this->generate_pdf           = $obj->generate_pdf;

				// Multicurrency
				$this->fk_multicurrency 		= $obj->fk_multicurrency;
				$this->multicurrency_code 		= $obj->multicurrency_code;
				$this->multicurrency_tx 		= $obj->multicurrency_tx;
				$this->multicurrency_total_ht 	= $obj->multicurrency_total_ht;
				$this->multicurrency_total_tva 	= $obj->multicurrency_total_tva;
				$this->multicurrency_total_ttc 	= $obj->multicurrency_total_ttc;

				if ($this->statut == self::STATUS_DRAFT)	$this->brouillon = 1;

				// Retreive all extrafield for thirdparty
				// fetch optionals attributes and labels
				require_once(DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php');
				$extrafields=new ExtraFields($this->db);
				$extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
				$this->fetch_optionals($this->id,$extralabels);

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

		// Retreive all extrafield for line
		// fetch optionals attributes and labels
		require_once(DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php');
		$extrafieldsline=new ExtraFields($this->db);
		$extrafieldsline=$extrafieldsline->fetch_name_optionals_label('facturedet_rec',true);

		$sql = 'SELECT l.rowid, l.fk_product, l.product_type, l.label as custom_label, l.description, l.product_type, l.price, l.qty, l.vat_src_code, l.tva_tx, ';
		$sql.= ' l.localtax1_tx, l.localtax2_tx, l.localtax1_type, l.localtax2_type, l.remise, l.remise_percent, l.subprice,';
		$sql.= ' l.info_bits, l.total_ht, l.total_tva, l.total_ttc,';
		//$sql.= ' l.situation_percent, l.fk_prev_id,';
		//$sql.= ' l.localtax1_tx, l.localtax2_tx, l.localtax1_type, l.localtax2_type, l.remise_percent, l.fk_remise_except, l.subprice,';
		$sql.= ' l.rang, l.special_code,';
		//$sql.= ' l.info_bits, l.total_ht, l.total_tva, l.total_localtax1, l.total_localtax2, l.total_ttc, l.fk_code_ventilation, l.fk_product_fournisseur_price as fk_fournprice, l.buy_price_ht as pa_ht,';
		$sql.= ' l.fk_unit, l.fk_contract_line,';
		$sql.= ' l.fk_multicurrency, l.multicurrency_code, l.multicurrency_subprice, l.multicurrency_total_ht, l.multicurrency_total_tva, l.multicurrency_total_ttc,';
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
				$line = new FactureLigneRec($this->db);

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

				$line->vat_src_code     = $objp->vat_src_code;
				$line->tva_tx           = $objp->tva_tx;
				$line->localtax1_tx     = $objp->localtax1_tx;
				$line->localtax2_tx     = $objp->localtax2_tx;
				$line->localtax1_type   = $objp->localtax1_type;
				$line->localtax2_type   = $objp->localtax2_type;
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

				$extralabelsline = $line->fetch_optionals($line->id,$extrafieldsline);

				// Multicurrency
				$line->fk_multicurrency 		= $objp->fk_multicurrency;
				$line->multicurrency_code 		= $objp->multicurrency_code;
				$line->multicurrency_subprice 	= $objp->multicurrency_subprice;
				$line->multicurrency_total_ht 	= $objp->multicurrency_total_ht;
				$line->multicurrency_total_tva 	= $objp->multicurrency_total_tva;
				$line->multicurrency_total_ttc 	= $objp->multicurrency_total_ttc;

				$this->lines[$i] = $line;

				$i++;
			}

			$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
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
	function delete(User $user, $notrigger=0, $idwarehouse=-1)
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
			if ($this->db->query($sql))
			{
				// Delete linked object
				$res = $this->deleteObjectLinked();
				if ($res < 0) $error=-3;
			}
			else
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
	 * 	@param		double		$txlocaltax1		Local tax 1 rate (deprecated)
	 *  @param		double		$txlocaltax2		Local tax 2 rate (deprecated)
     *	@param    	int			$fk_product      	Id du produit/service predefini
     *	@param    	double		$remise_percent  	Pourcentage de remise de la ligne
     *	@param		string		$price_base_type	HT or TTC
     *	@param    	int			$info_bits			VAT npr or not ?
     *	@param    	int			$fk_remise_except	Id remise
     *	@param    	double		$pu_ttc             Prix unitaire TTC (> 0 even for credit note)
     *	@param		int			$type				Type of line (0=product, 1=service)
     *	@param      int			$rang               Position of line
     *	@param		int			$special_code		Special code
     *	@param		string		$label				Label of the line
     *	@param		string		$fk_unit			Unit
	 * 	@param		double		$pu_ht_devise		Unit price in currency
     *	@return    	int             				<0 if KO, Id of line if OK
	 */
	function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $price_base_type='HT', $info_bits=0, $fk_remise_except='', $pu_ttc=0, $type=0, $rang=-1, $special_code=0, $label='', $fk_unit=null, $pu_ht_devise=0)
	{
	    global $mysoc;

		$facid=$this->id;

		dol_syslog(get_class($this)."::addline facid=$facid,desc=$desc,pu_ht=$pu_ht,qty=$qty,txtva=$txtva,txlocaltax1=$txlocaltax1,txlocaltax2=$txlocaltax2,fk_product=$fk_product,remise_percent=$remise_percent,info_bits=$info_bits,fk_remise_except=$fk_remise_except,price_base_type=$price_base_type,pu_ttc=$pu_ttc,type=$type,fk_unit=$fk_unit,pu_ht_devise=$pu_ht_devise", LOG_DEBUG);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		// Check parameters
		if ($type < 0) return -1;

		$localtaxes_type=getLocalTaxesFromRate($txtva, 0, $this->thirdparty, $mysoc);

		// Clean vat code
		$vat_src_code='';
		if (preg_match('/\((.*)\)/', $txtva, $reg))
		{
			$vat_src_code = $reg[1];
			$txtva = preg_replace('/\s*\(.*\)/', '', $txtva);    // Remove code into vatrate.
		}

		if ($this->brouillon)
		{
			// Clean parameters
			$remise_percent=price2num($remise_percent);
			if (empty($remise_percent)) $remise_percent=0;
			$qty=price2num($qty);
			$pu_ht = price2num($pu_ht);
			$pu_ttc = price2num($pu_ttc);
			$txtva = price2num($txtva);
			$txlocaltax1 = price2num($txlocaltax1);
			$txlocaltax2 = price2num($txlocaltax2);
			if (empty($txtva)) $txtva=0;
			if (empty($txlocaltax1)) $txlocaltax1=0;
			if (empty($txlocaltax2)) $txlocaltax2=0;
			if (empty($info_bits)) $info_bits=0;

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

			$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $mysoc, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1=$tabprice[9];
			$total_localtax2=$tabprice[10];
			$pu_ht = $tabprice[3];

			// MultiCurrency
			$multicurrency_total_ht  = $tabprice[16];
			$multicurrency_total_tva = $tabprice[17];
			$multicurrency_total_ttc = $tabprice[18];
			$pu_ht_devise = $tabprice[19];

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
			$sql.= ", vat_src_code";
			$sql.= ", localtax1_tx";
			$sql.= ", localtax1_type";
			$sql.= ", localtax2_tx";
			$sql.= ", localtax2_type";
			$sql.= ", fk_product";
			$sql.= ", product_type";
			$sql.= ", remise_percent";
			$sql.= ", subprice";
			$sql.= ", remise";
			$sql.= ", total_ht";
			$sql.= ", total_tva";
			$sql.= ", total_localtax1";
			$sql.= ", total_localtax2";
			$sql.= ", total_ttc";
			$sql.= ", info_bits";
			$sql.= ", rang";
			$sql.= ", special_code";
			$sql.= ", fk_unit";
			$sql.= ', fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc';
			$sql.= ") VALUES (";
			$sql.= "'".$facid."'";
			$sql.= ", ".(! empty($label)?"'".$this->db->escape($label)."'":"null");
			$sql.= ", '".$this->db->escape($desc)."'";
			$sql.= ", ".price2num($pu_ht);
			$sql.= ", ".price2num($qty);
			$sql.= ", ".price2num($txtva);
			$sql.= ", '".$this->db->escape($vat_src_code)."'";
			$sql.= ", ".price2num($txlocaltax1);
			$sql.= ", '".$this->db->escape($localtaxes_type[0])."'";
			$sql.= ", ".price2num($txlocaltax2);
			$sql.= ", '".$this->db->escape($localtaxes_type[2])."'";
			$sql.= ", ".(! empty($fk_product)?"'".$fk_product."'":"null");
			$sql.= ", ".$product_type;
			$sql.= ", ".price2num($remise_percent);
			$sql.= ", ".price2num($pu_ht);
			$sql.= ", null";
			$sql.= ", ".price2num($total_ht);
			$sql.= ", ".price2num($total_tva);
			$sql.= ", ".price2num($total_localtax1);
			$sql.= ", ".price2num($total_localtax2);
			$sql.= ", ".price2num($total_ttc);
			$sql.= ", ".$info_bits;
			$sql.= ", ".$rang;
			$sql.= ", ".$special_code;
			$sql.= ", ".($fk_unit?"'".$this->db->escape($fk_unit)."'":"null");
			$sql.= ", ".(int) $this->fk_multicurrency;
			$sql.= ", '".$this->db->escape($this->multicurrency_code)."'";
			$sql.= ", ".price2num($pu_ht_devise);
			$sql.= ", ".price2num($multicurrency_total_ht);
			$sql.= ", ".price2num($multicurrency_total_tva);
			$sql.= ", ".price2num($multicurrency_total_ttc);
			$sql.= ")";

			dol_syslog(get_class($this)."::addline", LOG_DEBUG);
			if ($this->db->query($sql))
			{
				$lineId = $this->db->last_insert_id(MAIN_DB_PREFIX."facturedet_rec");
				$this->id=$facid;
				$this->update_price();
				return $lineId;
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
	 * 	@param		double		$txlocaltax1		Local tax 1 rate (deprecated)
	 *  @param		double		$txlocaltax2		Local tax 2 rate (deprecated)
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
	 * 	@param		double		$pu_ht_devise		Unit price in currency
	 * 	@param		int			$notrigger			disable line update trigger
	 *	@return    	int             				<0 if KO, Id of line if OK
	 */
	function updateline($rowid, $desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $price_base_type='HT', $info_bits=0, $fk_remise_except='', $pu_ttc=0, $type=0, $rang=-1, $special_code=0, $label='', $fk_unit=null, $pu_ht_devise = 0, $notrigger=0)
	{
	    global $mysoc;

	    $facid=$this->id;

	    dol_syslog(get_class($this)."::updateline facid=".$facid." rowid=$rowid, desc=$desc, pu_ht=$pu_ht, qty=$qty, txtva=$txtva, txlocaltax1=$txlocaltax1, txlocaltax2=$txlocaltax2, fk_product=$fk_product, remise_percent=$remise_percent, info_bits=$info_bits, fk_remise_except=$fk_remise_except, price_base_type=$price_base_type, pu_ttc=$pu_ttc, type=$type, fk_unit=$fk_unit, pu_ht_devise=$pu_ht_devise", LOG_DEBUG);
	    include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

	    // Clean parameters
	    if (empty($remise_percent)) $remise_percent = 0;

	    // Check parameters
	    if ($type < 0) return -1;

		$localtaxes_type=getLocalTaxesFromRate($txtva, 0, $this->thirdparty, $mysoc);

		// Clean vat code
		$vat_src_code='';
		if (preg_match('/\((.*)\)/', $txtva, $reg))
		{
			$vat_src_code = $reg[1];
			$txtva = preg_replace('/\s*\(.*\)/', '', $txtva);    // Remove code into vatrate.
		}

	    if ($this->brouillon)
	    {
	        // Clean parameters
	        $remise_percent=price2num($remise_percent);
	        $qty=price2num($qty);
	        if (empty($info_bits)) $info_bits=0;
	        $pu_ht=price2num($pu_ht);
	        $pu_ttc=price2num($pu_ttc);
	        $txtva=price2num($txtva);
		    $txlocaltax1	= price2num($txlocaltax1);
		    $txlocaltax2	= price2num($txlocaltax2);
		    if (empty($txlocaltax1)) $txlocaltax1=0;
		    if (empty($txlocaltax2)) $txlocaltax2=0;

		    if (empty($this->multicurrency_subprice)) $this->multicurrency_subprice=0;
		    if (empty($this->multicurrency_total_ht)) $this->multicurrency_total_ht=0;
		    if (empty($this->multicurrency_total_tva)) $this->multicurrency_total_tva=0;
		    if (empty($this->multicurrency_total_ttc)) $this->multicurrency_total_ttc=0;

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
	        $tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $mysoc, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);

	        $total_ht  = $tabprice[0];
	        $total_tva = $tabprice[1];
	        $total_ttc = $tabprice[2];
		    $total_localtax1=$tabprice[9];
		    $total_localtax2=$tabprice[10];
		    $pu_ht  = $tabprice[3];
		    $pu_tva = $tabprice[4];
		    $pu_ttc = $tabprice[5];

		    // MultiCurrency
		    $multicurrency_total_ht  = $tabprice[16];
		    $multicurrency_total_tva = $tabprice[17];
		    $multicurrency_total_ttc = $tabprice[18];
		    $pu_ht_devise = $tabprice[19];

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
	        $sql.= ", vat_src_code='".$this->db->escape($vat_src_code)."'";
		    $sql.= ", localtax1_tx=".$txlocaltax1;
		    $sql.= ", localtax1_type='".$this->db->escape($localtaxes_type[0])."'";
		    $sql.= ", localtax2_tx=".$txlocaltax2;
		    $sql.= ", localtax2_type='".$this->db->escape($localtaxes_type[2])."'";
	        $sql.= ", fk_product=".(! empty($fk_product)?"'".$fk_product."'":"null");
	        $sql.= ", product_type=".$product_type;
	        $sql.= ", remise_percent='".price2num($remise_percent)."'";
	        $sql.= ", subprice='".price2num($pu_ht)."'";
	        $sql.= ", total_ht='".price2num($total_ht)."'";
	        $sql.= ", total_tva='".price2num($total_tva)."'";
	        $sql.= ", total_localtax1='".price2num($total_localtax1)."'";
	        $sql.= ", total_localtax2='".price2num($total_localtax2)."'";
	        $sql.= ", total_ttc='".price2num($total_ttc)."'";
	        $sql.= ", info_bits=".$info_bits;
	        $sql.= ", rang=".$rang;
	        $sql.= ", special_code=".$special_code;
	        $sql.= ", fk_unit=".($fk_unit?"'".$this->db->escape($fk_unit)."'":"null");
	        $sql.= ', multicurrency_subprice = '.$pu_ht_devise;
	        $sql.= ', multicurrency_total_ht = '.$multicurrency_total_ht;
	        $sql.= ', multicurrency_total_tva = '.$multicurrency_total_tva;
	        $sql.= ', multicurrency_total_ttc = '.$multicurrency_total_ttc;
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
	 * Return if maximum number of generation is reached
	 *
	 * @return	boolean			False by default, True if maximum number of generation is reached
	 */
	function isMaxNbGenReached()
	{
		$ret = false;
		if ($this->nb_gen_max > 0 && ($this->nb_gen_done >= $this->nb_gen_max)) $ret = true;
		return $ret;
	}

	/**
	 * Format string to output with by striking the string if max number of generation was reached
	 *
	 * @param	string		$ret	Default value to output
	 * @return	boolean				False by default, True if maximum number of generation is reached
	 */
	function strikeIfMaxNbGenReached($ret)
	{
		// Special case to strike the date
		return ($this->isMaxNbGenReached()?'<strike>':'').$ret.($this->isMaxNbGenReached()?'</strike>':'');
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
		$sql.= ' AND suspended = 0';
		$sql.= ' AND entity = '.$conf->entity;	// MUST STAY = $conf->entity here
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

		    while ($i < $num)     // Loop on each template invoice. If $num = 0, test is false at first pass.
			{
			    $line = $db->fetch_object($resql);

			    $db->begin();

				$facturerec = new FactureRec($db);
				$facturerec->fetch($line->rowid);

				if ($facturerec->id > 0)
				{
					// Set entity context
					$conf->entity = $facturerec->entity;

					dol_syslog("createRecurringInvoices Process invoice template id=".$facturerec->id.", ref=".$facturerec->ref.", entity=".$facturerec->entity);

				    $error=0;

				    $facture = new Facture($db);
					$facture->fac_rec = $facturerec->id;    // We will create $facture from this recurring invoice
					$facture->fk_fac_rec_source = $facturerec->id;    // We will create $facture from this recurring invoice

				    $facture->type = self::TYPE_STANDARD;
				    $facture->brouillon = 1;
				    $facture->date = (empty($facturerec->date_when)?$now:$facturerec->date_when);	// We could also use dol_now here but we prefer date_when so invoice has real date when we would like even if we generate later.
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
	                if (! $error && $facturerec->generate_pdf)
	                {
	                    $result = $facture->generateDocument($facturerec->modelpdf, $langs);
	                    if ($result <= 0)
	                    {
	                        $this->errors = $facture->errors;
	                        $this->error = $facture->error;
	                        $error++;
	                    }
	                }
				}
				else
				{
					$error++;
					$this->error="Failed to load invoice template with id=".$line->rowid.", entity=".$conf->entity."\n";
					$this->errors[]="Failed to load invoice template with id=".$line->rowid.", entity=".$conf->entity;
					dol_syslog("createRecurringInvoices Failed to load invoice template with id=".$line->rowid.", entity=".$conf->entity);
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
	 * @param	int		$withpicto       			Add picto into link
	 * @param  string	$option          			Where point the link
	 * @param  int		$max             			Maxlength of ref
	 * @param  int		$short           			1=Return just URL
	 * @param  string   $moretitle       			Add more text to title tooltip
     * @param	int  	$notooltip		 			1=Disable tooltip
     * @param  int		$save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @return string 			         			String with URL
	 */
	function getNomUrl($withpicto=0,$option='',$max=0,$short=0,$moretitle='',$notooltip='',$save_lastsearch_value=-1)
	{
		global $langs;

		$result='';
        $label=$langs->trans("ShowInvoice").': '.$this->ref;

        $url = DOL_URL_ROOT.'/compta/facture/fiche-rec.php?facid='.$this->id;

        if ($short) return $url;

        if ($option != 'nolink')
        {
        	// Add param to save lastsearch_values or not
        	$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
        	if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
        	if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
        }

		$linkstart = '<a href="'.$url.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.= $this->ref;
		$result .= $linkend;

		return $result;
	}

	/**
	 *  Return label of object status
	 *
	 *  @param      int		$mode			0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=Long label + picto
	 *  @param      integer	$alreadypaid    Not used on recurring invoices
	 *  @return     string			        Label of status
	 */
	function getLibStatut($mode=0, $alreadypaid=-1)
	{

		return $this->LibStatut($this->frequency?1:0, $this->suspended, $mode, $alreadypaid, empty($this->type)?0:$this->type);
	}

	/**
	 *	Return label of a status
	 *
	 *	@param    	int  	$recur         	Is it a recurring invoice ?
	 *	@param      int		$status        	Id status (suspended or not)
	 *	@param      int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=long label + picto
	 *	@param		integer	$alreadypaid	Not used for recurring invoices
	 *	@param		int		$type			Type invoice
	 *	@return     string        			Label of status
	 */
	function LibStatut($recur, $status, $mode=0, $alreadypaid=-1, $type=0)
	{
		global $langs;
		$langs->load('bills');

		//print "$recur,$status,$mode,$alreadypaid,$type";
		if ($mode == 0)
		{
			$prefix='';
			if ($recur)
			{
				if ($status == 1) return $langs->trans('Disabled');       // credit note
				else return $langs->trans('Active');
			}
			else return $langs->trans("Draft");
		}
		if ($mode == 1)
		{
			$prefix='Short';
			if ($recur)
			{
				if ($status == 1) return $langs->trans('Disabled');
				else return $langs->trans('Active');
			}
			else return $langs->trans("Draft");
		}
		if ($mode == 2)
		{
			if ($recur)
			{
				if ($status == 1) return img_picto($langs->trans('Disabled'),'statut6').' '.$langs->trans('Disabled');
				else return img_picto($langs->trans('Active'),'statut4').' '.$langs->trans('Active');
			}
			else return img_picto($langs->trans('Draft'),'statut0').' '.$langs->trans('Draft');
		}
		if ($mode == 3)
		{
			if ($recur)
			{
				$prefix='Short';
				if ($status == 1) return img_picto($langs->trans('Disabled'),'statut6');
				else return img_picto($langs->trans('Active'),'statut4');
			}
			else return img_picto($langs->trans('Draft'),'statut0');
		}
		if ($mode == 4)
		{
			$prefix='';
			if ($recur)
			{
				if ($status == 1) return img_picto($langs->trans('Disabled'),'statut6').' '.$langs->trans('Disabled');
				else return img_picto($langs->trans('Active'),'statut4').' '.$langs->trans('Active');
			}
			else return img_picto($langs->trans('Draft'),'statut0').' '.$langs->trans('Draft');
		}
		if ($mode == 5 || $mode == 6)
		{
			$prefix='';
			if ($mode == 5) $prefix='Short';
			if ($recur)
			{
				if ($status == 1) return '<span class="xhideonsmartphone">'.$langs->trans('Disabled').' </span>'.img_picto($langs->trans('Disabled'),'statut6');
				else return '<span class="xhideonsmartphone">'.$langs->trans('Active').' </span>'.img_picto($langs->trans('Active'),'statut4');
			}
			else return $langs->trans('Draft').' '.img_picto($langs->trans('Active'),'statut0');
		}
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
		$sql.= " WHERE entity IN (".getEntity('product').")";
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

    /**
     *	Update the auto generate documents
     *
     *	@param     	int		$validate		0 no document, 1 to generate document
     *	@return		int						<0 if KO, >0 if OK
     */
    function setGeneratePdf($validate)
    {
        if (! $this->table_element)
        {
            dol_syslog(get_class($this)."::setGeneratePdf was called on objet with property table_element not defined",LOG_ERR);
            return -1;
        }

        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
        $sql.= ' SET generate_pdf = '.$validate;
        $sql.= ' WHERE rowid = '.$this->id;

        dol_syslog(get_class($this)."::setGeneratePdf", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            $this->generate_pdf = $validate;
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *	Update the model for documents
     *
     *	@param     	string		$model		model of document generator
     *	@return		int						<0 if KO, >0 if OK
     */
    function setModelPdf($model)
    {
        if (! $this->table_element)
        {
            dol_syslog(get_class($this)."::setModelPdf was called on objet with property table_element not defined",LOG_ERR);
            return -1;
        }

        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
        $sql.= ' SET modelpdf = "' . $model . '"';
        $sql.= ' WHERE rowid = '.$this->id;

        dol_syslog(get_class($this)."::setModelPdf", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            $this->modelpdf = $model;
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
	public $element='facturedetrec';
	public $table_element='facturedet_rec';

    /**
     * 	Delete line in database
     *
     *  @param		User	$user		Object user
     *  @param		int		$notrigger	Disable triggers
     *	@return		int					<0 if KO, >0 if OK
     */
    function delete(User $user, $notrigger = false)
    {
    	$error=0;

	    $this->db->begin();

	    if (! $error) {
	        if (! $notrigger) {
	            // Call triggers
	            $result=$this->call_trigger('LINEBILLREC_DELETE', $user);
	            if ($result < 0) { $error++; } // Do also here what you must do to rollback action if trigger fail
	            // End call triggers
	        }
	    }

	    if (! $error)
	    {
    		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element.' WHERE rowid='.$this->id;

    		$res = $this->db->query($sql);
    		if($res===false) {
    		    $error++;
    		    $this->errors[] = $this->db->lasterror();
    		}
	    }

    	// Commit or rollback
		if ($error) {
		    $this->db->rollback();
		    return -1;
		} else {
		    $this->db->commit();
		    return 1;
		}
    }


    /**
     *	Recupere les lignes de factures predefinies dans this->lines
     *
     *	@param		int 	$rowid		Id of invoice
     *	@return     int         		1 if OK, < 0 if KO
     */
    function fetch($rowid)
    {
    	$sql = 'SELECT l.rowid, l.fk_facture ,l.fk_product, l.product_type, l.label as custom_label, l.description, l.product_type, l.price, l.qty, l.vat_src_code, l.tva_tx,';
    	$sql.= ' l.localtax1_tx, l.localtax2_tx, l.localtax1_type, l.localtax2_type, l.remise, l.remise_percent, l.subprice,';
    	$sql.= ' l.info_bits, l.total_ht, l.total_tva, l.total_ttc,';
    	$sql.= ' l.rang, l.special_code,';
    	$sql.= ' l.fk_unit, l.fk_contract_line,';
    	$sql.= ' p.ref as product_ref, p.fk_product_type as fk_product_type, p.label as product_label, p.description as product_desc';
    	$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet_rec as l';
    	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
    	$sql.= ' WHERE l.rowid = '.$rowid;
    	$sql.= ' ORDER BY l.rang';

    	dol_syslog('FactureRec::fetch', LOG_DEBUG);
    	$result = $this->db->query($sql);
    	if ($result)
    	{

    		$objp = $this->db->fetch_object($result);

    		$this->id	            = $objp->rowid;
    		$this->label            = $objp->custom_label;		// Label line
    		$this->desc             = $objp->description;		// Description line
    		$this->description      = $objp->description;		// Description line
    		$this->product_type     = $objp->product_type;		// Type of line
    		$this->ref              = $objp->product_ref;		// Ref product
    		$this->product_ref      = $objp->product_ref;		// Ref product
    		$this->libelle          = $objp->product_label;		// deprecated
    		$this->product_label	= $objp->product_label;		// Label product
    		$this->product_desc     = $objp->product_desc;		// Description product
    		$this->fk_product_type  = $objp->fk_product_type;	// Type of product
    		$this->qty              = $objp->qty;
    		$this->price			= $objp->price;
    		$this->subprice         = $objp->subprice;
    		$this->fk_facture		= $objp->fk_facture;
    		$this->vat_src_code     = $objp->vat_src_code;
    		$this->tva_tx           = $objp->tva_tx;
    		$this->localtax1_tx     = $objp->localtax1_tx;
    		$this->localtax2_tx     = $objp->localtax2_tx;
    		$this->localtax1_type   = $objp->localtax1_type;
    		$this->localtax2_type   = $objp->localtax2_type;
    		$this->remise_percent   = $objp->remise_percent;
    		$this->fk_remise_except = $objp->fk_remise_except;
    		$this->fk_product       = $objp->fk_product;
    		$this->info_bits        = $objp->info_bits;
    		$this->total_ht         = $objp->total_ht;
    		$this->total_tva        = $objp->total_tva;
    		$this->total_ttc        = $objp->total_ttc;
    		$this->code_ventilation = $objp->fk_code_ventilation;
    		$this->rang 			= $objp->rang;
    		$this->special_code 	= $objp->special_code;
    		$this->fk_unit          = $objp->fk_unit;
    		$this->fk_contract_line = $objp->fk_contract_line;


    		$this->db->free($result);
    		return 1;
    	}
    	else
    	{
    		$this->error=$this->db->lasterror();
    		return -3;
    	}
    }


    /**
     * 	Update a line to invoice_rec.
     *
     *	@return    	int             				<0 if KO, Id of line if OK
     */
    function update()
    {
    	global $user;

    	include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

    	if ($fk_product)
    	{
    		$product=new Product($this->db);
    		$result=$product->fetch($fk_product);
    		$product_type=$product->type;
    	}

    	$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet_rec SET ";
    	$sql.= " fk_facture = ".$this->fk_facture;
    	$sql.= ", label=".(! empty($this->label)?"'".$this->db->escape($this->label)."'":"null");
    	$sql.= ", description='".$this->db->escape($this->desc)."'";
    	$sql.= ", price=".price2num($this->price);
    	$sql.= ", qty=".price2num($this->qty);
    	$sql.= ", tva_tx=".price2num($this->tva_tx);
    	$sql.= ", vat_src_code='".$this->db->escape($this->vat_src_code)."'";
    	$sql.= ", localtax1_tx=".price2num($this->localtax1_tx);
    	$sql.= ", localtax1_type='".$this->db->escape($this->localtax1_type)."'";
    	$sql.= ", localtax2_tx=".price2num($this->localtax2_tx);
    	$sql.= ", localtax2_type='".$this->db->escape($this->localtax2_type)."'";
    	$sql.= ", fk_product=".($this->fk_product > 0 ? $this->fk_product :"null");
    	$sql.= ", product_type=".$this->product_type;
    	$sql.= ", remise_percent='".price2num($this->remise_percent)."'";
    	$sql.= ", subprice='".price2num($this->subprice)."'";
    	$sql.= ", total_ht='".price2num($this->total_ht)."'";
    	$sql.= ", total_tva='".price2num($this->total_tva)."'";
    	$sql.= ", total_localtax1='".price2num($this->total_localtax1)."'";
    	$sql.= ", total_localtax2='".price2num($this->total_localtax2)."'";
    	$sql.= ", total_ttc='".price2num($this->total_ttc)."'";
    	$sql.= ", rang=".$this->rang;
    	$sql.= ", special_code=".$this->special_code;
    	$sql.= ", fk_unit=".($this->fk_unit ?"'".$this->db->escape($this->fk_unit )."'":"null");
    	$sql.= ", fk_contract_line=".($this->fk_contract_line?$this->fk_contract_line:"null");

    	$sql.= " WHERE rowid = ".$this->id;

    	dol_syslog(get_class($this)."::updateline", LOG_DEBUG);
    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
    		{
    			$result=$this->insertExtraFields();
    			if ($result < 0)
    			{
    				$error++;
    			}
    		}

    		if (! $notrigger)
    		{
    			// Call trigger
    			$result=$this->call_trigger('LINEBILL_REC_UPDATE',$user);
    			if ($result < 0)
    			{
    				$this->db->rollback();
    				return -2;
    			}
    			// End call triggers
    		}
    		$this->db->commit();
    		return 1;
    	}
    	else
    	{
    		$this->error=$this->db->error();
    		$this->db->rollback();
    		return -2;
    	}

    }

}
