<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
* Copyright (C) 2004      Sebastien Di Cintio   <sdicintio@ressource-toi.org>
* Copyright (C) 2004      Benoit Mortier        <benoit.mortier@opensides.be>
* Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
* Copyright (C) 2005-2013 Regis Houssin         <regis.houssin@capnetworks.com>
* Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
* Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
* Copyright (C) 2010-2013 Juanjo Menent         <jmenent@2byte.es>
* Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
* Copyright (C) 2012      Marcos García         <marcosgdf@gmail.com>
* Copyright (C) 2013      Cedric Gross          <c.gross@kreiz-it.fr>
* Copyright (C) 2013      Florian Henry		  	<florian.henry@open-concept.pro>
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
 *	\file       htdocs/compta/facture/class/facture.class.php
*	\ingroup    facture
*	\brief      File of class to manage invoices
*/

include_once DOL_DOCUMENT_ROOT.'/core/class/commoninvoice.class.php';
require_once DOL_DOCUMENT_ROOT .'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT .'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT .'/margin/lib/margins.lib.php';


/**
 *	Class to manage invoices
 */
class Facture extends CommonInvoice
{
	public $element='facture';
	public $table_element='facture';
	public $table_element_line = 'facturedet';
	public $fk_element = 'fk_facture';
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	var $id;
	//! Id client
	var $socid;
	//! Objet societe client (to load with fetch_client method)
	var $client;
	var $author;
	var $fk_user_author;
	var $fk_user_valid;
	//! Invoice date
	var $date;				// Invoice date
	var $date_creation;		// Creation date
	var $date_validation;	// Validation date
	var $datem;
	var $ref;
	var $ref_client;
	var $ref_ext;
	var $ref_int;
	//! 0=Standard invoice, 1=Replacement invoice, 2=Credit note invoice, 3=Deposit invoice, 4=Proforma invoice
	var $type=0;

	//var $amount;
	var $remise_absolue;
	var $remise_percent;
	var $total_ht=0;
	var $total_tva=0;
	var $total_ttc=0;
	var $revenuestamp;
	var $note;			// deprecated
	var $note_private;
	var $note_public;
	//! 0=draft,
	//! 1=validated (need to be paid),
	//! 2=classified paid partially (close_code='discount_vat','badcustomer') or completely (close_code=null),
	//! 3=classified abandoned and no payment done (close_code='badcustomer','abandon' or 'replaced')
	var $statut;
	//! Fermeture apres paiement partiel: discount_vat, badcustomer, abandon
	//! Fermeture alors que aucun paiement: replaced (si remplace), abandon
	var $close_code;
	//! Commentaire si mis a paye sans paiement complet
	var $close_note;
	//! 1 if invoice paid COMPLETELY, 0 otherwise (do not use it anymore, use statut and close_code
	var $paye;
	//! id of source invoice if replacement invoice or credit note
	var $fk_facture_source;
	var $origin;
	var $origin_id;
	var $linked_objects=array();
	var $fk_project;
	var $date_lim_reglement;
	var $cond_reglement_id;			// Id in llx_c_paiement
	var $cond_reglement_code;		// Code in llx_c_paiement
	var $mode_reglement_id;			// Id in llx_c_paiement
	var $mode_reglement_code;		// Code in llx_c_paiement
	var $fk_bank;					// Field to store bank id to use when payment mode is withdraw
	var $modelpdf;
	var $products=array();	// deprecated
	var $lines=array();
	var $line;
	var $extraparams=array();
	//! Pour board
	var $nbtodo;
	var $nbtodolate;
	var $specimen;

	var $fac_rec;


	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB		$db			Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Create invoice in database
	 *  Note: this->ref can be set or empty. If empty, we will use "(PROV)"
	 *
	 *	@param	User	$user      		Object user that create
	 *	@param  int		$notrigger		1=Does not execute triggers, 0 otherwise
	 * 	@param	int		$forceduedate	1=Do not recalculate due date from payment condition but force it with value
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function create($user,$notrigger=0,$forceduedate=0)
	{
		global $langs,$conf,$mysoc,$hookmanager;
		$error=0;

		// Clean parameters
		if (empty($this->type)) $this->type = 0;
		$this->ref_client=trim($this->ref_client);
		$this->note=(isset($this->note) ? trim($this->note) : trim($this->note_private)); // deprecated
		$this->note_private=(isset($this->note_private) ? trim($this->note_private) : trim($this->note_private));
		$this->note_public=trim($this->note_public);
		if (! $this->cond_reglement_id) $this->cond_reglement_id = 0;
		if (! $this->mode_reglement_id) $this->mode_reglement_id = 0;
		$this->brouillon = 1;

		dol_syslog(get_class($this)."::create user=".$user->id);

		// Check parameters
		if (empty($this->date) || empty($user->id))
		{
			$this->error="ErrorBadParameter";
			dol_syslog(get_class($this)."::create Try to create an invoice with an empty parameter (user, date, ...)", LOG_ERR);
			return -3;
		}
		$soc = new Societe($this->db);
		$result=$soc->fetch($this->socid);
		if ($result < 0)
		{
			$this->error="Failed to fetch company";
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -2;
		}

		$now=dol_now();

		$this->db->begin();

		// Create invoice from a predefined invoice
		if ($this->fac_rec > 0)
		{
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
			$_facrec = new FactureRec($this->db);
			$result=$_facrec->fetch($this->fac_rec);

			$this->fk_project        = $_facrec->fk_project;
			$this->cond_reglement_id = $_facrec->cond_reglement_id;
			$this->mode_reglement_id = $_facrec->mode_reglement_id;
			$this->remise_absolue    = $_facrec->remise_absolue;
			$this->remise_percent    = $_facrec->remise_percent;

			// Clean parametres
			if (! $this->type) $this->type = 0;
			$this->ref_client=trim($this->ref_client);
			$this->note_private=trim($this->note_private);
			$this->note_public=trim($this->note_public);
			//if (! $this->remise) $this->remise = 0;
			if (! $this->mode_reglement_id) $this->mode_reglement_id = 0;
			$this->brouillon = 1;
		}

		// Define due date if not already defined
		$datelim=(empty($forceduedate)?$this->calculate_date_lim_reglement():$forceduedate);

		// Insert into database
		$socid  = $this->socid;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."facture (";
		$sql.= " facnumber";
		$sql.= ", entity";
		$sql.= ", ref_ext";
		$sql.= ", type";
		$sql.= ", fk_soc";
		$sql.= ", datec";
		$sql.= ", remise_absolue";
		$sql.= ", remise_percent";
		$sql.= ", datef";
		$sql.= ", note_private";
		$sql.= ", note_public";
		$sql.= ", ref_client, ref_int";
		$sql.= ", fk_facture_source, fk_user_author, fk_projet";
		$sql.= ", fk_cond_reglement, fk_mode_reglement, date_lim_reglement, model_pdf";
		$sql.= ")";
		$sql.= " VALUES (";
		$sql.= "'(PROV)'";
		$sql.= ", ".$conf->entity;
		$sql.= ", ".($this->ref_ext?"'".$this->db->escape($this->ref_ext)."'":"null");
		$sql.= ", '".$this->type."'";
		$sql.= ", '".$socid."'";
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ",".($this->remise_absolue>0?$this->remise_absolue:'NULL');
		$sql.= ",".($this->remise_percent>0?$this->remise_percent:'NULL');
		$sql.= ", '".$this->db->idate($this->date)."'";
		$sql.= ",".($this->note_private?"'".$this->db->escape($this->note_private)."'":"null");
		$sql.= ",".($this->note_public?"'".$this->db->escape($this->note_public)."'":"null");
		$sql.= ",".($this->ref_client?"'".$this->db->escape($this->ref_client)."'":"null");
		$sql.= ",".($this->ref_int?"'".$this->db->escape($this->ref_int)."'":"null");
		$sql.= ",".($this->fk_facture_source?"'".$this->db->escape($this->fk_facture_source)."'":"null");
		$sql.= ",".($user->id > 0 ? "'".$user->id."'":"null");
		$sql.= ",".($this->fk_project?$this->fk_project:"null");
		$sql.= ','.$this->cond_reglement_id;
		$sql.= ",".$this->mode_reglement_id;
		$sql.= ", '".$this->db->idate($datelim)."', '".$this->modelpdf."')";

		dol_syslog(get_class($this)."::create sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture');

			// Update ref with new one
			$this->ref='(PROV'.$this->id.')';
			$sql = 'UPDATE '.MAIN_DB_PREFIX."facture SET facnumber='".$this->ref."' WHERE rowid=".$this->id;

			dol_syslog(get_class($this)."::create sql=".$sql);
			$resql=$this->db->query($sql);
			if (! $resql) $error++;

			// Add object linked
			if (! $error && $this->id && is_array($this->linked_objects) && ! empty($this->linked_objects))
			{
				foreach($this->linked_objects as $origin => $origin_id)
				{
					$ret = $this->add_object_linked($origin, $origin_id);
					if (! $ret)
					{
						dol_print_error($this->db);
						$error++;
					}

					// TODO mutualiser
					if ($origin == 'commande')
					{
						// On recupere les differents contact interne et externe
						$order = new Commande($this->db);
						$order->id = $origin_id;

						// On recupere le commercial suivi propale
						$this->userid = $order->getIdcontact('internal', 'SALESREPFOLL');

						if ($this->userid)
						{
							//On passe le commercial suivi commande en commercial suivi paiement
							$this->add_contact($this->userid[0], 'SALESREPFOLL', 'internal');
						}

						// On recupere le contact client facturation commande
						$this->contactid = $order->getIdcontact('external', 'BILLING');

						if ($this->contactid)
						{
							//On passe le contact client facturation commande en contact client facturation
							$this->add_contact($this->contactid[0], 'BILLING', 'external');
						}
					}
				}
			}

			/*
			 *  Insert lines of invoices into database
			*/
			if (count($this->lines) && is_object($this->lines[0]))
			{
				$fk_parent_line = 0;

				dol_syslog("There is ".count($this->lines)." lines that are invoice lines objects");
				foreach ($this->lines as $i => $val)
				{
					$newinvoiceline=new FactureLigne($this->db);
					$newinvoiceline=$this->lines[$i];
					$newinvoiceline->fk_facture=$this->id;
					if ($result >= 0 && ($newinvoiceline->info_bits & 0x01) == 0)	// We keep only lines with first bit = 0
					{
						// Reset fk_parent_line for no child products and special product
						if (($newinvoiceline->product_type != 9 && empty($newinvoiceline->fk_parent_line)) || $newinvoiceline->product_type == 9) {
							$fk_parent_line = 0;
						}

						$newinvoiceline->fk_parent_line=$fk_parent_line;
						$result=$newinvoiceline->insert();

						// Defined the new fk_parent_line
						if ($result > 0 && $newinvoiceline->product_type == 9) {
							$fk_parent_line = $result;
						}
					}
					if ($result < 0)
					{
						$this->error=$newinvoiceline->error;
						$error++;
						break;
					}
				}
			}
			else
			{
				$fk_parent_line = 0;

				dol_syslog("There is ".count($this->lines)." lines that are array lines");
				foreach ($this->lines as $i => $val)
				{
					if (($this->lines[$i]->info_bits & 0x01) == 0)	// We keep only lines with first bit = 0
					{
						// Reset fk_parent_line for no child products and special product
						if (($this->lines[$i]->product_type != 9 && empty($this->lines[$i]->fk_parent_line)) || $this->lines[$i]->product_type == 9) {
							$fk_parent_line = 0;
						}

						$result = $this->addline(
							$this->lines[$i]->desc,
							$this->lines[$i]->subprice,
							$this->lines[$i]->qty,
							$this->lines[$i]->tva_tx,
							$this->lines[$i]->localtax1_tx,
							$this->lines[$i]->localtax2_tx,
							$this->lines[$i]->fk_product,
							$this->lines[$i]->remise_percent,
							$this->lines[$i]->date_start,
							$this->lines[$i]->date_end,
							$this->lines[$i]->fk_code_ventilation,
							$this->lines[$i]->info_bits,
							$this->lines[$i]->fk_remise_except,
							'HT',
							0,
							$this->lines[$i]->product_type,
							$this->lines[$i]->rang,
							$this->lines[$i]->special_code,
							'',
							0,
							$fk_parent_line,
							$this->lines[$i]->fk_fournprice,
							$this->lines[$i]->pa_ht,
							$this->lines[$i]->label
						);
						if ($result < 0)
						{
							$this->error=$this->db->lasterror();
							dol_print_error($this->db);
							$this->db->rollback();
							return -1;
						}

						// Defined the new fk_parent_line
						if ($result > 0 && $this->lines[$i]->product_type == 9) {
							$fk_parent_line = $result;
						}
					}
				}
			}

			/*
			 * Insert lines of predefined invoices
			*/
			if (! $error && $this->fac_rec > 0)
			{
				foreach ($_facrec->lines as $i => $val)
				{
					if ($_facrec->lines[$i]->fk_product)
					{
						$prod = new Product($this->db);
						$res=$prod->fetch($_facrec->lines[$i]->fk_product);
					}
					$tva_tx = get_default_tva($mysoc,$soc,$prod->id);
					$localtax1_tx=get_localtax($tva_tx,1,$soc);
					$localtax2_tx=get_localtax($tva_tx,2,$soc);

					$result_insert = $this->addline(
						$_facrec->lines[$i]->desc,
						$_facrec->lines[$i]->subprice,
						$_facrec->lines[$i]->qty,
						$tva_tx,
						$localtax1_tx,
						$localtax2_tx,
						$_facrec->lines[$i]->fk_product,
						$_facrec->lines[$i]->remise_percent,
						'','',0,0,'','HT',0,
						$_facrec->lines[$i]->product_type,
						$_facrec->lines[$i]->rang,
						$_facrec->lines[$i]->special_code,
						'',
						0,
						0,
						null,
						0,
						$_facrec->lines[$i]->label
					);

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

				$result=$this->update_price(1);
				if ($result > 0)
				{
					// Actions on extra fields (by external module or standard code)
					// FIXME le hook fait double emploi avec le trigger !!
					$hookmanager->initHooks(array('invoicedao'));
					$parameters=array('invoiceid'=>$this->id);
					$reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action); // Note that $action and $object may have been modified by some hooks
					if (empty($reshook))
					{
						if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
						{
							$result=$this->insertExtraFields();
							if ($result < 0)
							{
								$error++;
							}
						}
					}
					else if ($reshook < 0) $error++;

					// Appel des triggers
					include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
					$interface=new Interfaces($this->db);
					$result=$interface->run_triggers('BILL_CREATE',$this,$user,$langs,$conf);
					if ($result < 0) {
						$error++; $this->errors=$interface->errors;
					}
					// Fin appel triggers

					if (! $error)
					{
						$this->db->commit();
						return $this->id;
					}
					else
					{
						$this->db->rollback();
						return -4;
					}
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
				dol_syslog(get_class($this)."::create error ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::create error ".$this->error." sql=".$sql, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Create a new invoice in database from current invoice
	 *
	 *	@param      User	$user    		Object user that ask creation
	 *	@param		int		$invertdetail	Reverse sign of amounts for lines
	 *	@return		int						<0 if KO, >0 if OK
	 */
	function createFromCurrent($user,$invertdetail=0)
	{
		// Charge facture source
		$facture=new Facture($this->db);

		$facture->fk_facture_source = $this->fk_facture_source;
		$facture->type 			    = $this->type;
		$facture->socid 		    = $this->socid;
		$facture->date              = $this->date;
		$facture->note_public       = $this->note_public;
		$facture->note_private      = $this->note_private;
		$facture->ref_client        = $this->ref_client;
		$facture->modelpdf          = $this->modelpdf;
		$facture->fk_project        = $this->fk_project;
		$facture->cond_reglement_id = $this->cond_reglement_id;
		$facture->mode_reglement_id = $this->mode_reglement_id;
		$facture->remise_absolue    = $this->remise_absolue;
		$facture->remise_percent    = $this->remise_percent;

		$facture->lines		    	= $this->lines;	// Tableau des lignes de factures
		$facture->products		    = $this->lines;	// Tant que products encore utilise

		// Loop on each line of new invoice
		foreach($facture->lines as $i => $line)
		{
			if ($invertdetail)
			{
				$facture->lines[$i]->subprice  = -$facture->lines[$i]->subprice;
				$facture->lines[$i]->total_ht  = -$facture->lines[$i]->total_ht;
				$facture->lines[$i]->total_tva = -$facture->lines[$i]->total_tva;
				$facture->lines[$i]->total_localtax1 = -$facture->lines[$i]->total_localtax1;
				$facture->lines[$i]->total_localtax2 = -$facture->lines[$i]->total_localtax2;
				$facture->lines[$i]->total_ttc = -$facture->lines[$i]->total_ttc;
			}
		}

		dol_syslog(get_class($this)."::createFromCurrent invertdetail=".$invertdetail." socid=".$this->socid." nboflines=".count($facture->lines));

		$facid = $facture->create($user);
		if ($facid <= 0)
		{
			$this->error=$facture->error;
			$this->errors=$facture->errors;
		}

		return $facid;
	}


	/**
	 *		Load an object from its id and create a new one in database
	 *
	 *		@param		int				$socid			Id of thirdparty
	 * 	 	@return		int								New id of clone
	 */
	function createFromClone($socid=0)
	{
		global $conf,$user,$langs;

		$error=0;

		$this->db->begin();

		// Load source object
		$objFrom = dol_clone($this);

		// Change socid if needed
		if (! empty($socid) && $socid != $this->socid)
		{
			$objsoc = new Societe($this->db);

			if ($objsoc->fetch($socid)>0)
			{
				$this->socid 				= $objsoc->id;
				$this->cond_reglement_id	= (! empty($objsoc->cond_reglement_id) ? $objsoc->cond_reglement_id : 0);
				$this->mode_reglement_id	= (! empty($objsoc->mode_reglement_id) ? $objsoc->mode_reglement_id : 0);
				$this->fk_project			= '';
				$this->fk_delivery_address	= '';
			}

			// TODO Change product price if multi-prices
		}

		$this->id=0;
		$this->statut=0;

		// Clear fields
		$this->user_author        = $user->id;
		$this->user_valid         = '';
		$this->fk_facture_source  = 0;
		$this->date_creation      = '';
		$this->date_validation    = '';
		$this->ref_client         = '';
		$this->close_code         = '';
		$this->close_note         = '';
		$this->products = $this->lines;	// Tant que products encore utilise

		// Loop on each line of new invoice
		foreach($this->lines as $i => $line)
		{
			if (($this->lines[$i]->info_bits & 0x02) == 0x02)	// We do not clone line of discounts
			{
				unset($this->lines[$i]);
				unset($this->products[$i]);	// Tant que products encore utilise
			}
		}

		// Create clone
		$result=$this->create($user);
		if ($result < 0) $error++;

		if (! $error)
		{
			// Hook of thirdparty module
			if (is_object($hookmanager))
			{
				$parameters=array('objFrom'=>$objFrom);
				$action='';
				$reshook=$hookmanager->executeHooks('createFrom',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) $error++;
			}

			// Appel des triggers
			include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('BILL_CLONE',$this,$user,$langs,$conf);
			if ($result < 0) {
				$error++; $this->errors=$interface->errors;
			}
			// Fin appel triggers
		}

		// End
		if (! $error)
		{
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Load an object from an order and create a new invoice into database
	 *
	 *  @param      Object			$object         	Object source
	 *  @return     int             					<0 if KO, 0 if nothing done, 1 if OK
	 */
	function createFromOrder($object)
	{
		global $conf,$user,$langs,$hookmanager;

		$error=0;

		// Closed order
		$this->date = dol_now();
		$this->source = 0;

		$num=count($object->lines);
		for ($i = 0; $i < $num; $i++)
		{
			$line = new FactureLigne($this->db);

			$line->libelle			= $object->lines[$i]->libelle;
			$line->label			= $object->lines[$i]->label;
			$line->desc				= $object->lines[$i]->desc;
			$line->subprice			= $object->lines[$i]->subprice;
			$line->total_ht			= $object->lines[$i]->total_ht;
			$line->total_tva		= $object->lines[$i]->total_tva;
			$line->total_ttc		= $object->lines[$i]->total_ttc;
			$line->tva_tx			= $object->lines[$i]->tva_tx;
			$line->localtax1_tx		= $object->lines[$i]->localtax1_tx;
			$line->localtax2_tx		= $object->lines[$i]->localtax2_tx;
			$line->qty				= $object->lines[$i]->qty;
			$line->fk_remise_except	= $object->lines[$i]->fk_remise_except;
			$line->remise_percent	= $object->lines[$i]->remise_percent;
			$line->fk_product		= $object->lines[$i]->fk_product;
			$line->info_bits		= $object->lines[$i]->info_bits;
			$line->product_type		= $object->lines[$i]->product_type;
			$line->rang				= $object->lines[$i]->rang;
			$line->special_code		= $object->lines[$i]->special_code;
			$line->fk_parent_line	= $object->lines[$i]->fk_parent_line;

			$line->fk_fournprice	= $object->lines[$i]->fk_fournprice;
			$marginInfos			= getMarginInfos($object->lines[$i]->subprice, $object->lines[$i]->remise_percent, $object->lines[$i]->tva_tx, $object->lines[$i]->localtax1_tx, $object->lines[$i]->localtax2_tx, $object->lines[$i]->fk_fournprice, $object->lines[$i]->pa_ht);
			$line->pa_ht			= $marginInfos[0];

			$this->lines[$i] = $line;
		}

		$this->socid                = $object->socid;
		$this->fk_project           = $object->fk_project;
		$this->cond_reglement_id    = $object->cond_reglement_id;
		$this->mode_reglement_id    = $object->mode_reglement_id;
		$this->availability_id      = $object->availability_id;
		$this->demand_reason_id     = $object->demand_reason_id;
		$this->date_livraison       = $object->date_livraison;
		$this->fk_delivery_address  = $object->fk_delivery_address;
		$this->contact_id           = $object->contactid;
		$this->ref_client           = $object->ref_client;
		$this->note_private         = $object->note_private;
		$this->note_public          = $object->note_public;

		$this->origin				= $object->element;
		$this->origin_id			= $object->id;

		// Possibility to add external linked objects with hooks
		$this->linked_objects[$this->origin] = $this->origin_id;
		if (! empty($object->other_linked_objects) && is_array($object->other_linked_objects))
		{
			$this->linked_objects = array_merge($this->linked_objects, $object->other_linked_objects);
		}

		$ret = $this->create($user);

		if ($ret > 0)
		{
			// Actions hooked (by external module)
			$hookmanager->initHooks(array('invoicedao'));

			$parameters=array('objFrom'=>$object);
			$action='';
			$reshook=$hookmanager->executeHooks('createFrom',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
			if ($reshook < 0) $error++;

			if (! $error)
			{
				return 1;
			}
			else return -1;
		}
		else return -1;
	}

	/**
	 *      Return clicable link of object (with eventually picto)
	 *
	 *      @param	int		$withpicto       Add picto into link
	 *      @param  string	$option          Where point the link
	 *      @param  int		$max             Maxlength of ref
	 *      @param  int		$short           1=Return just URL
	 *      @param  string  $moretitle       Add more text to title tooltip
	 *      @return string 			         String with URL
	 */
	function getNomUrl($withpicto=0,$option='',$max=0,$short=0,$moretitle='')
	{
		global $langs;

		$result='';

		if ($option == 'withdraw') $url = DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$this->id;
		else $url = DOL_URL_ROOT.'/compta/facture.php?facid='.$this->id;

		if ($short) return $url;

		$picto='bill';
		if ($this->type == 1) $picto.='r';	// Replacement invoice
		if ($this->type == 2) $picto.='a';	// Credit note
		if ($this->type == 3) $picto.='d';	// Deposit invoice

		$label=$langs->trans("ShowInvoice").': '.$this->ref;
		if ($this->type == 1) $label=$langs->transnoentitiesnoconv("ShowInvoiceReplace").': '.$this->ref;
		if ($this->type == 2) $label=$langs->transnoentitiesnoconv("ShowInvoiceAvoir").': '.$this->ref;
		if ($this->type == 3) $label=$langs->transnoentitiesnoconv("ShowInvoiceDeposit").': '.$this->ref;
		if ($moretitle) $label.=' - '.$moretitle;

		//$linkstart='<a href="'.$url.'" title="'.dol_escape_htmltag($label).'">';
		$linkstart='<a href="'.$url.'">';
		$linkend='</a>';

		if ($withpicto) $result.=($linkstart.img_object(($max?dol_trunc($label,$max):$label),$picto).$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$linkstart.($max?dol_trunc($this->ref,$max):$this->ref).$linkend;
		return $result;
	}


	/**
	 *	Get object and lines from database
	 *
	 *	@param      int		$rowid       	Id of object to load
	 * 	@param		string	$ref			Reference of invoice
	 * 	@param		string	$ref_ext		External reference of invoice
	 * 	@param		int		$ref_int		Internal reference of other object
	 *	@return     int         			>0 if OK, <0 if KO, 0 if not found
	 */
	function fetch($rowid, $ref='', $ref_ext='', $ref_int='')
	{
		global $conf;

		if (empty($rowid) && empty($ref) && empty($ref_ext) && empty($ref_int)) return -1;

		$sql = 'SELECT f.rowid,f.facnumber,f.ref_client,f.ref_ext,f.ref_int,f.type,f.fk_soc,f.amount,f.tva, f.localtax1, f.localtax2, f.total, f.total_ttc, f.revenuestamp';
		$sql.= ', f.remise_percent, f.remise_absolue, f.remise';
		$sql.= ', f.datef as df';
		$sql.= ', f.date_lim_reglement as dlr';
		$sql.= ', f.datec as datec';
		$sql.= ', f.date_valid as datev';
		$sql.= ', f.tms as datem';
		$sql.= ', f.note_private, f.note_public, f.fk_statut, f.paye, f.close_code, f.close_note, f.fk_user_author, f.fk_user_valid, f.model_pdf';
		$sql.= ', f.fk_facture_source';
		$sql.= ', f.fk_mode_reglement, f.fk_cond_reglement, f.fk_projet, f.extraparams';
		$sql.= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
		$sql.= ', c.code as cond_reglement_code, c.libelle as cond_reglement_libelle, c.libelle_facture as cond_reglement_libelle_doc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as c ON f.fk_cond_reglement = c.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON f.fk_mode_reglement = p.id';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		if ($rowid)   $sql.= " AND f.rowid=".$rowid;
		if ($ref)     $sql.= " AND f.facnumber='".$this->db->escape($ref)."'";
		if ($ref_ext) $sql.= " AND f.ref_ext='".$this->db->escape($ref_ext)."'";
		if ($ref_int) $sql.= " AND f.ref_int='".$this->db->escape($ref_int)."'";

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id					= $obj->rowid;
				$this->ref					= $obj->facnumber;
				$this->ref_client			= $obj->ref_client;
				$this->ref_ext				= $obj->ref_ext;
				$this->ref_int				= $obj->ref_int;
				$this->type					= $obj->type;
				$this->date					= $this->db->jdate($obj->df);
				$this->date_creation		= $this->db->jdate($obj->datec);
				$this->date_validation		= $this->db->jdate($obj->datev);
				$this->datem				= $this->db->jdate($obj->datem);
				$this->remise_percent		= $obj->remise_percent;
				$this->remise_absolue		= $obj->remise_absolue;
				//$this->remise				= $obj->remise;
				$this->total_ht				= $obj->total;
				$this->total_tva			= $obj->tva;
				$this->total_localtax1		= $obj->localtax1;
				$this->total_localtax2		= $obj->localtax2;
				$this->total_ttc			= $obj->total_ttc;
				$this->revenuestamp         = $obj->revenuestamp;
				$this->paye					= $obj->paye;
				$this->close_code			= $obj->close_code;
				$this->close_note			= $obj->close_note;
				$this->socid				= $obj->fk_soc;
				$this->statut				= $obj->fk_statut;
				$this->date_lim_reglement	= $this->db->jdate($obj->dlr);
				$this->mode_reglement_id	= $obj->fk_mode_reglement;
				$this->mode_reglement_code	= $obj->mode_reglement_code;
				$this->mode_reglement		= $obj->mode_reglement_libelle;
				$this->cond_reglement_id	= $obj->fk_cond_reglement;
				$this->cond_reglement_code	= $obj->cond_reglement_code;
				$this->cond_reglement		= $obj->cond_reglement_libelle;
				$this->cond_reglement_doc	= $obj->cond_reglement_libelle_doc;
				$this->fk_project			= $obj->fk_projet;
				$this->fk_facture_source	= $obj->fk_facture_source;
				$this->note					= $obj->note_private;	// deprecated
				$this->note_private			= $obj->note_private;
				$this->note_public			= $obj->note_public;
				$this->user_author			= $obj->fk_user_author;
				$this->user_valid			= $obj->fk_user_valid;
				$this->modelpdf				= $obj->model_pdf;

				$this->extraparams			= (array) json_decode($obj->extraparams, true);

				if ($this->statut == 0)	$this->brouillon = 1;

				// Retreive all extrafield for invoice
				// fetch optionals attributes and labels
				require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
				$extrafields=new ExtraFields($this->db);
				$extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
				$this->fetch_optionals($this->id,$extralabels);

				/*
				 * Lines
				*/

				$this->lines  = array();

				$result=$this->fetch_lines();
				if ($result < 0)
				{
					$this->error=$this->db->error();
					dol_syslog(get_class($this)."::fetch Error ".$this->error, LOG_ERR);
					return -3;
				}
				return 1;
			}
			else
			{
				$this->error='Bill with id '.$rowid.' or ref '.$ref.' not found sql='.$sql;
				dol_syslog(get_class($this)."::fetch Error ".$this->error, LOG_ERR);
				return 0;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::fetch Error ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *	Load all detailed lines into this->lines
	 *
	 *	@return     int         1 if OK, < 0 if KO
	 */
	function fetch_lines()
	{
		$this->lines=array();

		$sql = 'SELECT l.rowid, l.fk_product, l.fk_parent_line, l.label as custom_label, l.description, l.product_type, l.price, l.qty, l.tva_tx, ';
		$sql.= ' l.localtax1_tx, l.localtax2_tx, l.localtax1_type, l.localtax2_type, l.remise, l.remise_percent, l.fk_remise_except, l.subprice,';
		$sql.= ' l.rang, l.special_code,';
		$sql.= ' l.date_start as date_start, l.date_end as date_end,';
		$sql.= ' l.info_bits, l.total_ht, l.total_tva, l.total_localtax1, l.total_localtax2, l.total_ttc, l.fk_code_ventilation, l.fk_product_fournisseur_price as fk_fournprice, l.buy_price_ht as pa_ht,';
		$sql.= ' p.ref as product_ref, p.fk_product_type as fk_product_type, p.label as product_label, p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet as l';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
		$sql.= ' WHERE l.fk_facture = '.$this->id;
		$sql.= ' ORDER BY l.rang';

		dol_syslog(get_class($this).'::fetch_lines sql='.$sql, LOG_DEBUG);
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
				$line->label            = $objp->custom_label;
				$line->desc             = $objp->description;		// Description line
				$line->product_type     = $objp->product_type;		// Type of line
				$line->product_ref      = $objp->product_ref;		// Ref product
				$line->libelle          = $objp->product_label;		// TODO deprecated
				$line->product_label	= $objp->product_label;		// Label product
				$line->product_desc     = $objp->product_desc;		// Description product
				$line->fk_product_type  = $objp->fk_product_type;	// Type of product
				$line->qty              = $objp->qty;
				$line->subprice         = $objp->subprice;
				$line->tva_tx           = $objp->tva_tx;
				$line->localtax1_tx     = $objp->localtax1_tx;
				$line->localtax2_tx     = $objp->localtax2_tx;
				$line->localtax1_type   = $objp->localtax1_type;
				$line->localtax2_type   = $objp->localtax2_type;
				$line->remise_percent   = $objp->remise_percent;
				$line->fk_remise_except = $objp->fk_remise_except;
				$line->fk_product       = $objp->fk_product;
				$line->date_start       = $this->db->jdate($objp->date_start);
				$line->date_end         = $this->db->jdate($objp->date_end);
				$line->date_start       = $this->db->jdate($objp->date_start);
				$line->date_end         = $this->db->jdate($objp->date_end);
				$line->info_bits        = $objp->info_bits;
				$line->total_ht         = $objp->total_ht;
				$line->total_tva        = $objp->total_tva;
				$line->total_localtax1  = $objp->total_localtax1;
				$line->total_localtax2  = $objp->total_localtax2;
				$line->total_ttc        = $objp->total_ttc;
				$line->code_ventilation = $objp->fk_code_ventilation;
				$line->fk_fournprice 	= $objp->fk_fournprice;
				$marginInfos			= getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $line->fk_fournprice, $objp->pa_ht);
				$line->pa_ht 			= $marginInfos[0];
				$line->marge_tx			= $marginInfos[1];
				$line->marque_tx		= $marginInfos[2];
				$line->rang				= $objp->rang;
				$line->special_code		= $objp->special_code;
				$line->fk_parent_line	= $objp->fk_parent_line;

				// Ne plus utiliser
				//$line->price            = $objp->price;
				//$line->remise           = $objp->remise;

				$this->lines[$i] = $line;

				$i++;
			}
			$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::fetch_lines '.$this->error,LOG_ERR);
			return -3;
		}
	}


	/**
	 *      Update database
	 *
	 *      @param      User	$user        	User that modify
	 *      @param      int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *      @return     int      			   	<0 if KO, >0 if OK
	 */
	function update($user=0, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		// Clean parameters
		if (empty($this->type)) $this->type=0;
		if (isset($this->facnumber)) $this->facnumber=trim($this->ref);
		if (isset($this->ref_client)) $this->ref_client=trim($this->ref_client);
		if (isset($this->increment)) $this->increment=trim($this->increment);
		if (isset($this->close_code)) $this->close_code=trim($this->close_code);
		if (isset($this->close_note)) $this->close_note=trim($this->close_note);
		if (isset($this->note) || isset($this->note_private)) $this->note=(isset($this->note) ? trim($this->note) : trim($this->note_private));		// deprecated
		if (isset($this->note) || isset($this->note_private)) $this->note_private=(isset($this->note_private) ? trim($this->note_private) : trim($this->note));
		if (isset($this->note_public)) $this->note_public=trim($this->note_public);
		if (isset($this->modelpdf)) $this->modelpdf=trim($this->modelpdf);
		if (isset($this->import_key)) $this->import_key=trim($this->import_key);

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."facture SET";

		$sql.= " facnumber=".(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"null").",";
		$sql.= " type=".(isset($this->type)?$this->type:"null").",";
		$sql.= " ref_client=".(isset($this->ref_client)?"'".$this->db->escape($this->ref_client)."'":"null").",";
		$sql.= " increment=".(isset($this->increment)?"'".$this->db->escape($this->increment)."'":"null").",";
		$sql.= " fk_soc=".(isset($this->socid)?$this->socid:"null").",";
		$sql.= " datec=".(strval($this->date_creation)!='' ? "'".$this->db->idate($this->date_creation)."'" : 'null').",";
		$sql.= " datef=".(strval($this->date)!='' ? "'".$this->db->idate($this->date)."'" : 'null').",";
		$sql.= " date_valid=".(strval($this->date_validation)!='' ? "'".$this->db->idate($this->date_validation)."'" : 'null').",";
		$sql.= " paye=".(isset($this->paye)?$this->paye:"null").",";
		$sql.= " remise_percent=".(isset($this->remise_percent)?$this->remise_percent:"null").",";
		$sql.= " remise_absolue=".(isset($this->remise_absolue)?$this->remise_absolue:"null").",";
		//$sql.= " remise=".(isset($this->remise)?$this->remise:"null").",";
		$sql.= " close_code=".(isset($this->close_code)?"'".$this->db->escape($this->close_code)."'":"null").",";
		$sql.= " close_note=".(isset($this->close_note)?"'".$this->db->escape($this->close_note)."'":"null").",";
		$sql.= " tva=".(isset($this->total_tva)?$this->total_tva:"null").",";
		$sql.= " localtax1=".(isset($this->total_localtax1)?$this->total_localtax1:"null").",";
		$sql.= " localtax2=".(isset($this->total_localtax2)?$this->total_localtax2:"null").",";
		$sql.= " total=".(isset($this->total_ht)?$this->total_ht:"null").",";
		$sql.= " total_ttc=".(isset($this->total_ttc)?$this->total_ttc:"null").",";
		$sql.= " revenuestamp=".((isset($this->revenuestamp) && $this->revenuestamp != '')?$this->revenuestamp:"null").",";
		$sql.= " fk_statut=".(isset($this->statut)?$this->statut:"null").",";
		$sql.= " fk_user_author=".(isset($this->user_author)?$this->user_author:"null").",";
		$sql.= " fk_user_valid=".(isset($this->fk_user_valid)?$this->fk_user_valid:"null").",";
		$sql.= " fk_facture_source=".(isset($this->fk_facture_source)?$this->fk_facture_source:"null").",";
		$sql.= " fk_projet=".(isset($this->fk_project)?$this->fk_project:"null").",";
		$sql.= " fk_cond_reglement=".(isset($this->cond_reglement_id)?$this->cond_reglement_id:"null").",";
		$sql.= " fk_mode_reglement=".(isset($this->mode_reglement_id)?$this->mode_reglement_id:"null").",";
		$sql.= " date_lim_reglement=".(strval($this->date_lim_reglement)!='' ? "'".$this->db->idate($this->date_lim_reglement)."'" : 'null').",";
		$sql.= " note_private=".(isset($this->note_private)?"'".$this->db->escape($this->note_private)."'":"null").",";
		$sql.= " note_public=".(isset($this->note_public)?"'".$this->db->escape($this->note_public)."'":"null").",";
		$sql.= " model_pdf=".(isset($this->modelpdf)?"'".$this->db->escape($this->modelpdf)."'":"null").",";
		$sql.= " import_key=".(isset($this->import_key)?"'".$this->db->escape($this->import_key)."'":"null")."";

		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error++; $this->errors[]="Error ".$this->db->lasterror();
		}

		if (! $error)
		{
			if (! $notrigger)
			{
				// Call triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('BILL_MODIFY',$this,$user,$langs,$conf);
				if ($result < 0) {
					$error++; $this->errors=$interface->errors;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *    Add a discount line into invoice using an existing absolute discount
	 *
	 *    @param     int	$idremise	Id of absolute discount
	 *    @return    int          		>0 if OK, <0 if KO
	 */
	function insert_discount($idremise)
	{
		global $langs;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

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
			$facligne->fk_product=0;					// Id produit predefini
			$facligne->qty=1;
			$facligne->remise_percent=0;
			$facligne->rang=-1;
			$facligne->info_bits=2;

			$facligne->total_ht  = -$remise->amount_ht;
			$facligne->total_tva = -$remise->amount_tva;
			$facligne->total_ttc = -$remise->amount_ttc;

			$lineid=$facligne->insert();
			if ($lineid > 0)
			{
				$result=$this->update_price(1);
				if ($result > 0)
				{
					// Create linke between discount and invoice line
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
	 *	Set customer ref
	 *
	 *	@param     	string	$ref_client		Customer ref
	 *	@return		int						<0 if KO, >0 if OK
	 */
	function set_ref_client($ref_client)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
		if (empty($ref_client))
			$sql .= ' SET ref_client = NULL';
		else
			$sql .= ' SET ref_client = \''.$this->db->escape($ref_client).'\'';
		$sql .= ' WHERE rowid = '.$this->id;
		if ($this->db->query($sql))
		{
			$this->ref_client = $ref_client;
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Delete invoice
	 *
	 *	@param     	int		$rowid      	Id of invoice to delete. If empty, we delete current instance of invoice
	 *	@param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@param		int		$idwarehouse	Id warehouse to use for stock change.
	 *	@return		int						<0 if KO, >0 if OK
	 */
	function delete($rowid=0, $notrigger=0, $idwarehouse=-1)
	{
		global $user,$langs,$conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		if (empty($rowid)) $rowid=$this->id;

		dol_syslog(get_class($this)."::delete rowid=".$rowid, LOG_DEBUG);

		// TODO Test if there is at least on payment. If yes, refuse to delete.

		$error=0;
		$this->db->begin();

		if (! $error && ! $notrigger)
		{
			// Appel des triggers
			include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('BILL_DELETE',$this,$user,$langs,$conf);
			if ($result < 0) {
				$error++; $this->errors=$interface->errors;
			}
			// Fin appel triggers
		}

		if (! $error)
		{
			// Delete linked object
			$res = $this->deleteObjectLinked();
			if ($res < 0) $error++;
		}

		if (! $error)
		{
			// If invoice was converted into a discount not yet consumed, we remove discount
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'societe_remise_except';
			$sql.= ' WHERE fk_facture_source = '.$rowid;
			$sql.= ' AND fk_facture_line IS NULL';
			$resql=$this->db->query($sql);

			// If invoice has consumned discounts
			$this->fetch_lines();
			$list_rowid_det=array();
			foreach($this->lines as $key => $invoiceline)
			{
				$list_rowid_det[]=$invoiceline->rowid;
			}

			// Consumned discounts are freed
			if (count($list_rowid_det))
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except';
				$sql.= ' SET fk_facture = NULL, fk_facture_line = NULL';
				$sql.= ' WHERE fk_facture_line IN ('.join(',',$list_rowid_det).')';

				dol_syslog(get_class($this)."::delete sql=".$sql);
				if (! $this->db->query($sql))
				{
					$this->error=$this->db->error()." sql=".$sql;
					dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
					$this->db->rollback();
					return -5;
				}
			}

			// If we decrament stock on invoice validation, we increment
			if ($this->type != 3 && $result >= 0 && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $idwarehouse!=-1)
			{
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$langs->load("agenda");

				$num=count($this->lines);
				for ($i = 0; $i < $num; $i++)
				{
					if ($this->lines[$i]->fk_product > 0)
					{
						$mouvP = new MouvementStock($this->db);
						// We decrease stock for product
						if ($this->type == 2) $result=$mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("InvoiceDeleteDolibarr",$this->ref));
						else $result=$mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, 0, $langs->trans("InvoiceDeleteDolibarr",$this->ref));	// we use 0 for price, to not change the weighted average value
					}
				}
			}


			// Delete invoice line
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facturedet WHERE fk_facture = '.$rowid;
			if ($this->db->query($sql) && $this->delete_linked_contact())
			{
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture WHERE rowid = '.$rowid;
				$resql=$this->db->query($sql);
				if ($resql)
				{
					// On efface le repertoire de pdf provisoire
					$ref = dol_sanitizeFileName($this->ref);
					if ($conf->facture->dir_output)
					{
						$dir = $conf->facture->dir_output . "/" . $ref;
						$file = $conf->facture->dir_output . "/" . $ref . "/" . $ref . ".pdf";
						if (file_exists($file))	// We must delete all files before deleting directory
						{
							$ret=dol_delete_preview($this);

							if (! dol_delete_file($file,0,0,0,$this)) // For triggers
							{
								$this->error=$langs->trans("ErrorCanNotDeleteFile",$file);
								$this->db->rollback();
								return 0;
							}
						}
						if (file_exists($dir))
						{
							if (! dol_delete_dir_recursive($dir)) // For remove dir and meta
							{
								$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
								$this->db->rollback();
								return 0;
							}
						}
					}

					$this->db->commit();
					return 1;
				}
				else
				{
					$this->error=$this->db->lasterror()." sql=".$sql;
					dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
					$this->db->rollback();
					return -6;
				}
			}
			else
			{
				$this->error=$this->db->lasterror()." sql=".$sql;
				dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -4;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}


	/**
	 *	Renvoi une date limite de reglement de facture en fonction des
	 *	conditions de reglements de la facture et date de facturation
	 *
	 *	@param      string	$cond_reglement   	Condition of payment (code or id) to use. If 0, we use current condition.
	 *	@return     date     			       	Date limite de reglement si ok, <0 si ko
	 */
	function calculate_date_lim_reglement($cond_reglement=0)
	{
		if (! $cond_reglement) $cond_reglement=$this->cond_reglement_code;
		if (! $cond_reglement) $cond_reglement=$this->cond_reglement_id;

		$cdr_nbjour=0; $cdr_fdm=0; $cdr_decalage=0;

		$sqltemp = 'SELECT c.fdm,c.nbjour,c.decalage';
		$sqltemp.= ' FROM '.MAIN_DB_PREFIX.'c_payment_term as c';
		if (is_numeric($cond_reglement)) $sqltemp.= " WHERE c.rowid=".$cond_reglement;
		else $sqltemp.= " WHERE c.code='".$this->db->escape($cond_reglement)."'";

		dol_syslog(get_class($this).'::calculate_date_lim_reglement sql='.$sqltemp);
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
		$datelim = $this->date + ($cdr_nbjour * 3600 * 24);

		// 2 : application de la regle "fin de mois"
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
			// On se deplace au debut du mois suivant, et on retire un jour
			$datelim=dol_mktime(12,0,0,$mois,1,$annee);
			$datelim -= (3600 * 24);
		}

		// 3 : application du decalage
		$datelim += ($cdr_decalage * 3600 * 24);

		return $datelim;
	}

	/**
	 *  Tag la facture comme paye completement (close_code non renseigne) ou partiellement (close_code renseigne) + appel trigger BILL_PAYED
	 *
	 *  @param	User	$user      	Objet utilisateur qui modifie
	 *	@param  string	$close_code	Code renseigne si on classe a payee completement alors que paiement incomplet (cas escompte par exemple)
	 *	@param  string	$close_note	Commentaire renseigne si on classe a payee alors que paiement incomplet (cas escompte par exemple)
	 *  @return int         		<0 if KO, >0 if OK
	 */
	function set_paid($user,$close_code='',$close_note='')
	{
		global $conf,$langs;
		$error=0;

		if ($this->paye != 1)
		{
			$this->db->begin();

			dol_syslog(get_class($this)."::set_paid rowid=".$this->id, LOG_DEBUG);
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture SET';
			$sql.= ' fk_statut=2';
			if (! $close_code) $sql.= ', paye=1';
			if ($close_code) $sql.= ", close_code='".$this->db->escape($close_code)."'";
			if ($close_note) $sql.= ", close_note='".$this->db->escape($close_note)."'";
			$sql.= ' WHERE rowid = '.$this->id;

			$resql = $this->db->query($sql);
			if ($resql)
			{
				// Appel des triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('BILL_PAYED',$this,$user,$langs,$conf);
				if ($result < 0) {
					$error++; $this->errors=$interface->errors;
				}
				// Fin appel triggers
			}
			else
			{
				$error++;
				$this->error=$this->db->error();
				dol_print_error($this->db);
			}

			if (! $error)
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
			return 0;
		}
	}


	/**
	 *  Tag la facture comme non payee completement + appel trigger BILL_UNPAYED
	 *	Fonction utilisee quand un paiement prelevement est refuse,
	 * 	ou quand une facture annulee et reouverte.
	 *
	 *  @param	User	$user       Object user that change status
	 *  @return int         		<0 if KO, >0 if OK
	 */
	function set_unpaid($user)
	{
		global $conf,$langs;
		$error=0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
		$sql.= ' SET paye=0, fk_statut=1, close_code=null, close_note=null';
		$sql.= ' WHERE rowid = '.$this->id;

		dol_syslog(get_class($this)."::set_unpaid sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			// Appel des triggers
			include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('BILL_UNPAYED',$this,$user,$langs,$conf);
			if ($result < 0) {
				$error++; $this->errors=$interface->errors;
			}
			// Fin appel triggers
		}
		else
		{
			$error++;
			$this->error=$this->db->error();
			dol_print_error($this->db);
		}

		if (! $error)
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


	/**
	 *	Tag invoice as canceled, with no payment on it (example for replacement invoice or payment never received) + call trigger BILL_CANCEL
	 *	Warning, if option to decrease stock on invoice was set, this function does not change stock (it might be a cancel because
	 *  of no payment even if merchandises were sent).
	 *
	 *	@param	User	$user        	Object user making change
	 *	@param	string	$close_code		Code de fermeture
	 *	@param	string	$close_note		Comment
	 *	@return int         			<0 if KO, >0 if OK
	 */
	function set_canceled($user,$close_code='',$close_note='')
	{
		global $conf,$langs;

		$error=0;

		dol_syslog(get_class($this)."::set_canceled rowid=".$this->id, LOG_DEBUG);

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture SET';
		$sql.= ' fk_statut=3';
		if ($close_code) $sql.= ", close_code='".$this->db->escape($close_code)."'";
		if ($close_note) $sql.= ", close_note='".$this->db->escape($close_note)."'";
		$sql.= ' WHERE rowid = '.$this->id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			// On desaffecte de la facture les remises liees
			// car elles n'ont pas ete utilisees vu que la facture est abandonnee.
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except';
			$sql.= ' SET fk_facture = NULL';
			$sql.= ' WHERE fk_facture = '.$this->id;

			$resql=$this->db->query($sql);
			if ($resql)
			{
				// Appel des triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('BILL_CANCEL',$this,$user,$langs,$conf);
				if ($result < 0) {
					$error++; $this->errors=$interface->errors;
				}
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
	 * Tag invoice as validated + call trigger BILL_VALIDATE
	 * Object must have lines loaded with fetch_lines
	 *
	 * @param	User	$user           Object user that validate
	 * @param   string	$force_number	Reference to force on invoice
	 * @param	int		$idwarehouse	Id of warehouse to use for stock decrease
	 * @return	int						<0 if KO, >0 if OK
	 */
	function validate($user, $force_number='', $idwarehouse=0)
	{
		global $conf,$langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$now=dol_now();

		$error=0;
		dol_syslog(get_class($this).'::validate user='.$user->id.', force_number='.$force_number.', idwarehouse='.$idwarehouse);

		// Check parameters
		if (! $this->brouillon)
		{
			dol_syslog(get_class($this)."::validate no draft status", LOG_WARNING);
			return 0;
		}

		if (! $user->rights->facture->valider)
		{
			$this->error='Permission denied';
			dol_syslog(get_class($this)."::validate ".$this->error, LOG_ERR);
			return -1;
		}

		$this->db->begin();

		$this->fetch_thirdparty();
		$this->fetch_lines();

		// Check parameters
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
			if ($idreplacement && $idreplacement != $this->id)
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
				$this->error=$facreplaced->error;
				$this->db->rollback();
				return -13;
			}
		}

		// Define new ref
		if ($force_number)
		{
			$num = $force_number;
		}
		else if (preg_match('/^[\(]?PROV/i', $this->ref))
		{
			if (! empty($conf->global->FAC_FORCE_DATE_VALIDATION))	// If option enabled, we force invoice date
			{
				$this->date=dol_now();
				$this->date_lim_reglement=$this->calculate_date_lim_reglement();
			}
			$num = $this->getNextNumRef($this->client);
		}
		else
		{
			$num = $this->ref;
		}

		if ($num)
		{
			$this->update_price(1);

			// Validate
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
			$sql.= " SET facnumber='".$num."', fk_statut = 1, fk_user_valid = ".$user->id.", date_valid = '".$this->db->idate($now)."'";
			if (! empty($conf->global->FAC_FORCE_DATE_VALIDATION))	// If option enabled, we force invoice date
			{
				$sql.= ", datef='".$this->db->idate($this->date)."'";
				$sql.= ", date_lim_reglement='".$this->db->idate($this->date_lim_reglement)."'";
			}
			$sql.= ' WHERE rowid = '.$this->id;

			dol_syslog(get_class($this)."::validate sql=".$sql);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				dol_syslog(get_class($this)."::validate Echec update - 10 - sql=".$sql, LOG_ERR);
				dol_print_error($this->db);
				$error++;
			}

			// On verifie si la facture etait une provisoire
			if (! $error && (preg_match('/^[\(]?PROV/i', $this->ref)))
			{
				// La verif qu'une remise n'est pas utilisee 2 fois est faite au moment de l'insertion de ligne
			}

			if (! $error)
			{
				// Define third party as a customer
				$result=$this->client->set_as_client();

				// Si active on decremente le produit principal et ses composants a la validation de facture
				if ($this->type != 3 && $result >= 0 && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_BILL))
				{
					require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
					$langs->load("agenda");

					// Loop on each line
					$cpt=count($this->lines);
					for ($i = 0; $i < $cpt; $i++)
					{
						if ($this->lines[$i]->fk_product > 0)
						{
							$mouvP = new MouvementStock($this->db);
							// We decrease stock for product
							if ($this->type == 2) $result=$mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("InvoiceValidatedInDolibarr",$num));
							else $result=$mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("InvoiceValidatedInDolibarr",$num));
							if ($result < 0) {
								$error++;
							}
						}
					}
				}
			}

			if (! $error)
			{
				$this->oldref = '';

				// Rename directory if dir was a temporary ref
				if (preg_match('/^[\(]?PROV/i', $this->ref))
				{
					// Rename of object directory ($this->ref = old ref, $num = new ref)
					// to  not lose the linked files
					$facref = dol_sanitizeFileName($this->ref);
					$snumfa = dol_sanitizeFileName($num);
					$dirsource = $conf->facture->dir_output.'/'.$facref;
					$dirdest = $conf->facture->dir_output.'/'.$snumfa;
					if (file_exists($dirsource))
					{
						dol_syslog(get_class($this)."::validate rename dir ".$dirsource." into ".$dirdest);

						if (@rename($dirsource, $dirdest))
						{
							$this->oldref = $facref;

							dol_syslog("Rename ok");
							// Suppression ancien fichier PDF dans nouveau rep
							dol_delete_file($conf->facture->dir_output.'/'.$snumfa.'/'.$facref.'*.*');
						}
					}
				}
			}

			// Set new ref and define current statut
			if (! $error)
			{
				$this->ref = $num;
				$this->facnumber=$num;
				$this->statut=1;
				$this->brouillon=0;
				$this->date_validation=$now;
			}

			// Trigger calls
			if (! $error)
			{
				// Appel des triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('BILL_VALIDATE',$this,$user,$langs,$conf);
				if ($result < 0) {
					$error++; $this->errors=$interface->errors;
				}
				// Fin appel triggers
			}
		}
		else
		{
			$error++;
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Set draft status
	 *
	 *	@param	User	$user			Object user that modify
	 *	@param	int		$idwarehouse	Id warehouse to use for stock change.
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function set_draft($user,$idwarehouse=-1)
	{
		global $conf,$langs;

		$error=0;

		if ($this->statut == 0)
		{
			dol_syslog(get_class($this)."::set_draft already draft status", LOG_WARNING);
			return 0;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."facture";
		$sql.= " SET fk_statut = 0";
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::set_draft sql=".$sql, LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{
			// Si on decremente le produit principal et ses composants a la validation de facture, on réincrement
			if ($this->type != 3 && $result >= 0 && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_BILL))
			{
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$langs->load("agenda");

				$num=count($this->lines);
				for ($i = 0; $i < $num; $i++)
				{
					if ($this->lines[$i]->fk_product > 0)
					{
						$mouvP = new MouvementStock($this->db);
						// We decrease stock for product
						if ($this->type == 2) $result=$mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("InvoiceBackToDraftInDolibarr",$this->ref));
						else $result=$mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, 0, $langs->trans("InvoiceBackToDraftInDolibarr",$this->ref));	// we use 0 for price, to not change the weighted average value
					}
				}
			}

			if ($error == 0)
			{
				$old_statut=$this->statut;
				$this->brouillon = 1;
				$this->statut = 0;
				// Appel des triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('BILL_UNVALIDATE',$this,$user,$langs,$conf);
				if ($result < 0) {
					$error++;
					$this->errors=$interface->errors;
					$this->statut=$old_statut;
					$this->brouillon=0;
				}
				// Fin appel triggers
			} else {
				$this->db->rollback();
				return -1;
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
	 * 		Add an invoice line into database (linked to product/service or not).
	 * 		Les parametres sont deja cense etre juste et avec valeurs finales a l'appel
	 *		de cette methode. Aussi, pour le taux tva, il doit deja avoir ete defini
	 *		par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,produit)
	 *		et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
	 *
	 * 		@param    	string		$desc            	Description de la ligne
	 * 		@param    	double		$pu_ht              Prix unitaire HT (> 0 even for credit note)
	 * 		@param    	double		$qty             	Quantite
	 * 		@param    	double		$txtva           	Taux de tva force, sinon -1
	 * 		@param		double		$txlocaltax1		Local tax 1 rate
	 *  	@param		double		$txlocaltax2		Local tax 2 rate
	 *		@param    	int			$fk_product      	Id du produit/service predefini
	 * 		@param    	double		$remise_percent  	Pourcentage de remise de la ligne
	 * 		@param    	timestamp	$date_start      	Date de debut de validite du service
	 * 		@param    	timestamp	$date_end        	Date de fin de validite du service
	 * 		@param    	int			$ventil          	Code de ventilation comptable
	 * 		@param    	int			$info_bits			Bits de type de lignes
	 *		@param    	int			$fk_remise_except	Id remise
	 *		@param		string		$price_base_type	HT or TTC
	 * 		@param    	double		$pu_ttc             Prix unitaire TTC (> 0 even for credit note)
	 * 		@param		int			$type				Type of line (0=product, 1=service)
	 *      @param      int			$rang               Position of line
	 *      @param		int			$special_code		Special code (also used by externals modules!)
	 *      @param		string		$origin				'order', ...
	 *      @param		int			$origin_id			Id of origin object
	 *      @param		int			$fk_parent_line		Id of parent line
	 * 		@param		int			$fk_fournprice		To calculate margin
	 * 		@param		int			$pa_ht				Buying price of line
	 * 		@param		string		$label				Label of the line
	 *		@param		array		$array_option		extrafields array
	 *    	@return    	int             				<0 if KO, Id of line if OK
	 */
	function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits=0, $fk_remise_except='', $price_base_type='HT', $pu_ttc=0, $type=0, $rang=-1, $special_code=0, $origin='', $origin_id=0, $fk_parent_line=0, $fk_fournprice=null, $pa_ht=0, $label='',$array_option=0)
	{
		global $mysoc;

		$facid=$this->id;

		dol_syslog(get_class($this)."::Addline facid=$facid,desc=$desc,pu_ht=$pu_ht,qty=$qty,txtva=$txtva, txlocaltax1=$txlocaltax1, txlocaltax2=$txlocaltax2, fk_product=$fk_product,remise_percent=$remise_percent,date_start=$date_start,date_end=$date_end,ventil=$ventil,info_bits=$info_bits,fk_remise_except=$fk_remise_except,price_base_type=$price_base_type,pu_ttc=$pu_ttc,type=$type", LOG_DEBUG);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		// Clean parameters
		if (empty($remise_percent)) $remise_percent=0;
		if (empty($qty)) $qty=0;
		if (empty($info_bits)) $info_bits=0;
		if (empty($rang)) $rang=0;
		if (empty($ventil)) $ventil=0;
		if (empty($txtva)) $txtva=0;
		if (empty($txlocaltax1)) $txlocaltax1=0;
		if (empty($txlocaltax2)) $txlocaltax2=0;
		if (empty($fk_parent_line) || $fk_parent_line < 0) $fk_parent_line=0;

		$remise_percent=price2num($remise_percent);
		$qty=price2num($qty);
		$pu_ht=price2num($pu_ht);
		$pu_ttc=price2num($pu_ttc);
		$pa_ht=price2num($pa_ht);
		$txtva=price2num($txtva);
		$txlocaltax1=price2num($txlocaltax1);
		$txlocaltax2=price2num($txlocaltax2);

		if ($price_base_type=='HT')
		{
			$pu=$pu_ht;
		}
		else
		{
			$pu=$pu_ttc;
		}

		// Check parameters
		if ($type < 0) return -1;

		if (! empty($this->brouillon))
		{
			$this->db->begin();

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$localtaxes_type=getLocalTaxesFromRate($txtva,0,$mysoc);

			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type,'',$localtaxes_type);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1 = $tabprice[9];
			$total_localtax2 = $tabprice[10];
			$pu_ht = $tabprice[3];

			// Rang to use
			$rangtouse = $rang;
			if ($rangtouse == -1)
			{
				$rangmax = $this->line_max($fk_parent_line);
				$rangtouse = $rangmax + 1;
			}

			$product_type=$type;
			if ($fk_product)
			{
				$product=new Product($this->db);
				$result=$product->fetch($fk_product);
				$product_type=$product->type;
			}

			// Insert line
			$this->line=new FactureLigne($this->db);
			$this->line->fk_facture=$facid;
			$this->line->label=$label;
			$this->line->desc=$desc;
			$this->line->qty=            ($this->type==2?abs($qty):$qty);	// For credit note, quantity is always positive and unit price negative
			$this->line->tva_tx=$txtva;
			$this->line->localtax1_tx=$txlocaltax1;
			$this->line->localtax2_tx=$txlocaltax2;
			$this->line->fk_product=$fk_product;
			$this->line->product_type=$product_type;
			$this->line->remise_percent=$remise_percent;
			$this->line->subprice=       ($this->type==2?-abs($pu_ht):$pu_ht); // For credit note, unit price always negative, always positive otherwise
			$this->line->date_start=$date_start;
			$this->line->date_end=$date_end;
			$this->line->ventil=$ventil;
			$this->line->rang=$rangtouse;
			$this->line->info_bits=$info_bits;
			$this->line->fk_remise_except=$fk_remise_except;
			$this->line->total_ht=       (($this->type==2||$qty<0)?-abs($total_ht):$total_ht);  // For credit note and if qty is negative, total is negative
			$this->line->total_tva=      (($this->type==2||$qty<0)?-abs($total_tva):$total_tva);
			$this->line->total_localtax1=(($this->type==2||$qty<0)?-abs($total_localtax1):$total_localtax1);
			$this->line->total_localtax2=(($this->type==2||$qty<0)?-abs($total_localtax2):$total_localtax2);
			$this->line->localtax1_type = $localtaxes_type[0];
			$this->line->localtax2_type = $localtaxes_type[2];
			$this->line->total_ttc=      (($this->type==2||$qty<0)?-abs($total_ttc):$total_ttc);
			$this->line->special_code=$special_code;
			$this->line->fk_parent_line=$fk_parent_line;
			$this->line->origin=$origin;
			$this->line->origin_id=$origin_id;

			// infos marge
			$this->line->fk_fournprice = $fk_fournprice;
			$this->line->pa_ht = $pa_ht;

			if (is_array($array_option) && count($array_option)>0) {
				$this->line->array_options=$array_option;
			}

			$result=$this->line->insert();
			if ($result > 0)
			{
				// Reorder if child line
				if (! empty($fk_parent_line)) $this->line_order(true,'DESC');

				// Mise a jour informations denormalisees au niveau de la facture meme
				$this->id=$facid;	// TODO To move this we must remove parameter facid into this function declaration
				$result=$this->update_price(1);
				if ($result > 0)
				{
					$this->db->commit();
					return $this->line->rowid;
				}
				else
				{
					$this->error=$this->db->error();
					dol_syslog("Error sql=$sql, error=".$this->error,LOG_ERR);
					$this->db->rollback();
					return -1;
				}
			}
			else
			{
				$this->error=$this->line->error;
				$this->db->rollback();
				return -2;
			}
		}
	}

	/**
	 *  Update a detail line
	 *
	 *  @param     	int			$rowid           	Id of line to update
	 *  @param     	string		$desc            	Description of line
	 *  @param     	double		$pu              	Prix unitaire (HT ou TTC selon price_base_type) (> 0 even for credit note lines)
	 *  @param     	double		$qty             	Quantity
	 *  @param     	double		$remise_percent  	Pourcentage de remise de la ligne
	 *  @param     	date		$date_start      	Date de debut de validite du service
	 *  @param     	date		$date_end        	Date de fin de validite du service
	 *  @param     	double		$txtva          	VAT Rate
	 * 	@param		double		$txlocaltax1		Local tax 1 rate
	 *  @param		double		$txlocaltax2		Local tax 2 rate
	 * 	@param     	string		$price_base_type 	HT or TTC
	 * 	@param     	int			$info_bits 		    Miscellaneous informations
	 * 	@param		int			$type				Type of line (0=product, 1=service)
	 * 	@param		int			$fk_parent_line		Id of parent line (0 in most cases, used by modules adding sublevels into lines).
	 * 	@param		int			$skip_update_total	Keep fields total_xxx to 0 (used for special lines by some modules)
	 * 	@param		int			$fk_fournprice		Id of origin supplier price
	 * 	@param		int			$pa_ht				Price (without tax) of product when it was bought
	 * 	@param		string		$label				Label of the line
	 * 	@param		int			$special_code		Special code (also used by externals modules!)
     *  @param		array		$array_option		extrafields array
	 *  @return    	int             				< 0 if KO, > 0 if OK
	 */
	function updateline($rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva, $txlocaltax1=0, $txlocaltax2=0, $price_base_type='HT', $info_bits=0, $type=0, $fk_parent_line=0, $skip_update_total=0, $fk_fournprice=null, $pa_ht=0, $label='', $special_code=0, $array_option=0)
	{
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		global $mysoc;

		dol_syslog(get_class($this)."::updateline $rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva, $txlocaltax1, $txlocaltax2, $price_base_type, $info_bits, $type, $fk_parent_line", LOG_DEBUG);

		if ($this->brouillon)
		{
			$this->db->begin();

			// Clean parameters
			if (empty($qty)) $qty=0;
			if (empty($fk_parent_line) || $fk_parent_line < 0) $fk_parent_line=0;
			if (empty($special_code) || $special_code == 3) $special_code=0;

			$remise_percent	= price2num($remise_percent);
			$qty			= price2num($qty);
			$pu 			= price2num($pu);
			$pa_ht			= price2num($pa_ht);
			$txtva			= price2num($txtva);
			$txlocaltax1	= price2num($txlocaltax1);
			$txlocaltax2	= price2num($txlocaltax2);

			// Check parameters
			if ($type < 0) return -1;

			// Calculate total with, without tax and tax from qty, pu, remise_percent and txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$localtaxes_type=getLocalTaxesFromRate($txtva,0,$mysoc);

			$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type,'',$localtaxes_type);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1=$tabprice[9];
			$total_localtax2=$tabprice[10];
			$pu_ht  = $tabprice[3];
			$pu_tva = $tabprice[4];
			$pu_ttc = $tabprice[5];

			// Old properties: $price, $remise (deprecated)
			$price = $pu;
			$remise = 0;
			if ($remise_percent > 0)
			{
				$remise = round(($pu * $remise_percent / 100),2);
				$price = ($pu - $remise);
			}
			$price    = price2num($price);

			// Update line into database
			$this->line=new FactureLigne($this->db);

			// Stock previous line records
			$staticline=new FactureLigne($this->db);
			$staticline->fetch($rowid);
			$this->line->oldline = $staticline;

			// Reorder if fk_parent_line change
			if (! empty($fk_parent_line) && ! empty($staticline->fk_parent_line) && $fk_parent_line != $staticline->fk_parent_line)
			{
				$rangmax = $this->line_max($fk_parent_line);
				$this->line->rang = $rangmax + 1;
			}

			$this->line->rowid				= $rowid;
			$this->line->label				= $label;
			$this->line->desc				= $desc;
			$this->line->qty				= ($this->type==2?abs($qty):$qty);	// For credit note, quantity is always positive and unit price negative
			$this->line->tva_tx				= $txtva;
			$this->line->localtax1_tx		= $txlocaltax1;
			$this->line->localtax2_tx		= $txlocaltax2;
			$this->line->localtax1_type		= $localtaxes_type[0];
			$this->line->localtax2_type		= $localtaxes_type[2];
			$this->line->remise_percent		= $remise_percent;
			$this->line->subprice			= ($this->type==2?-abs($pu_ht):$pu_ht); // For credit note, unit price always negative, always positive otherwise
			$this->line->date_start			= $date_start;
			$this->line->date_end			= $date_end;
			$this->line->total_ht			= (($this->type==2||$qty<0)?-abs($total_ht):$total_ht);  // For credit note and if qty is negative, total is negative
			$this->line->total_tva			= (($this->type==2||$qty<0)?-abs($total_tva):$total_tva);
			$this->line->total_localtax1	= (($this->type==2||$qty<0)?-abs($total_localtax1):$total_localtax1);
			$this->line->total_localtax2	= (($this->type==2||$qty<0)?-abs($total_localtax2):$total_localtax2);
			$this->line->total_ttc			= (($this->type==2||$qty<0)?-abs($total_ttc):$total_ttc);
			$this->line->info_bits			= $info_bits;
			$this->line->special_code		= $special_code;
			$this->line->product_type		= $type;
			$this->line->fk_parent_line		= $fk_parent_line;
			$this->line->skip_update_total	= $skip_update_total;

			// infos marge
			$this->line->fk_fournprice = $fk_fournprice;
			$this->line->pa_ht = $pa_ht;

			// A ne plus utiliser
			//$this->line->price=$price;
			//$this->line->remise=$remise;

			if (is_array($array_option) && count($array_option)>0) {
				$this->line->array_options=$array_option;
			}

			$result=$this->line->update();
			if ($result > 0)
			{
				// Reorder if child line
				if (! empty($fk_parent_line)) $this->line_order(true,'DESC');

				// Mise a jour info denormalisees au niveau facture
				$this->update_price(1);
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
			$this->error="Invoice statut makes operation forbidden";
			return -2;
		}
	}

	/**
	 *	Delete line in database
	 *
	 *	@param		int		$rowid		Id of line to delete
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function deleteline($rowid)
	{
		global $langs, $conf;

		dol_syslog(get_class($this)."::deleteline rowid=".$rowid, LOG_DEBUG);

		if (! $this->brouillon)
		{
			$this->error='ErrorBadStatus';
			return -1;
		}

		$this->db->begin();

		// Libere remise liee a ligne de facture
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except';
		$sql.= ' SET fk_facture_line = NULL';
		$sql.= ' WHERE fk_facture_line = '.$rowid;

		dol_syslog(get_class($this)."::deleteline sql=".$sql);
		$result = $this->db->query($sql);
		if (! $result)
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::deleteline Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}

		$line=new FactureLigne($this->db);

		// For triggers
		$line->fetch($rowid);

		if ($line->delete() > 0)
		{
			$result=$this->update_price(1);

			if ($result > 0)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				$this->error=$this->db->lasterror();
				return -1;
			}
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Set percent discount
	 *
	 *	@param     	User	$user		User that set discount
	 *	@param     	double	$remise		Discount
	 *	@return		int 		<0 if ko, >0 if ok
	 */
	function set_remise($user, $remise)
	{
		// Clean parameters
		if (empty($remise)) $remise=0;

		if ($user->rights->facture->creer)
		{
			$remise=price2num($remise);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
			$sql.= ' SET remise_percent = '.$remise;
			$sql.= ' WHERE rowid = '.$this->id;
			$sql.= ' AND fk_statut = 0 ;';

			if ($this->db->query($sql))
			{
				$this->remise_percent = $remise;
				$this->update_price(1);
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
	 *	Set absolute discount
	 *
	 *	@param     	User	$user 		User that set discount
	 *	@param     	double	$remise		Discount
	 *	@return		int 				<0 if KO, >0 if OK
	 */
	function set_remise_absolue($user, $remise)
	{
		if (empty($remise)) $remise=0;

		if ($user->rights->facture->creer)
		{
			$remise=price2num($remise);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
			$sql.= ' SET remise_absolue = '.$remise;
			$sql.= ' WHERE rowid = '.$this->id;
			$sql.= ' AND fk_statut = 0 ;';

			dol_syslog(get_class($this)."::set_remise_absolue sql=$sql");

			if ($this->db->query($sql))
			{
				$this->remise_absolue = $remise;
				$this->update_price(1);
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
	 *  Return list of payments
	 *
	 *	@param		string	$filtertype		1 to filter on type of payment == 'PRE'
	 *  @return     array					Array with list of payments
	 */
	function getListOfPayments($filtertype='')
	{
		$retarray=array();

		$table='paiement_facture';
		$table2='paiement';
		$field='fk_facture';
		$field2='fk_paiement';
		if ($this->element == 'facture_fourn' || $this->element == 'invoice_supplier')
		{
			$table='paiementfourn_facturefourn';
			$table2='paiementfourn';
			$field='fk_facturefourn';
			$field2='fk_paiementfourn';
		}

		$sql = 'SELECT pf.amount, p.fk_paiement, p.datep, t.code';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$table.' as pf, '.MAIN_DB_PREFIX.$table2.' as p, '.MAIN_DB_PREFIX.'c_paiement as t';
		$sql.= ' WHERE pf.'.$field.' = '.$this->id;
		$sql.= ' AND pf.'.$field2.' = p.rowid';
		$sql.= ' AND p.fk_paiement = t.id';
		if ($filtertype) $sql.=" AND t.code='PRE'";

		dol_syslog(get_class($this)."::getListOfPayments sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$retarray[]=array('amount'=>$obj->amount,'type'=>$obj->code, 'date'=>$obj->datep);
				$i++;
			}
			$this->db->free($resql);
			return $retarray;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_print_error($this->db);
			return array();
		}
	}


	/**
	 *    	Return amount (with tax) of all credit notes and deposits invoices used by invoice
	 *
	 *		@return		int			<0 if KO, Sum of credit notes and deposits amount otherwise
	 */
	function getSumCreditNotesUsed()
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		$discountstatic=new DiscountAbsolute($this->db);
		$result=$discountstatic->getSumCreditNotesUsed($this);
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
	 *    	Return amount (with tax) of all deposits invoices used by invoice
	 *
	 *		@return		int			<0 if KO, Sum of deposits amount otherwise
	 */
	function getSumDepositsUsed()
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		$discountstatic=new DiscountAbsolute($this->db);
		$result=$discountstatic->getSumDepositsUsed($this);
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
	 *      Return next reference of customer invoice not already used (or last reference)
	 *      according to numbering module defined into constant FACTURE_ADDON
	 *
	 *      @param	   Society		$soc		object company
	 *      @param     string		$mode		'next' for next value or 'last' for last value
	 *      @return    string					free ref or last ref
	 */
	function getNextNumRef($soc,$mode='next')
	{
		global $conf, $db, $langs;
		$langs->load("bills");

		// Clean parameters (if not defined or using deprecated value)
		if (empty($conf->global->FACTURE_ADDON)) $conf->global->FACTURE_ADDON='mod_facture_terre';
		else if ($conf->global->FACTURE_ADDON=='terre') $conf->global->FACTURE_ADDON='mod_facture_terre';
		else if ($conf->global->FACTURE_ADDON=='mercure') $conf->global->FACTURE_ADDON='mod_facture_mercure';

		$mybool=false;

		$file = $conf->global->FACTURE_ADDON.".php";
		$classname = $conf->global->FACTURE_ADDON;
		// Include file with class
		foreach ($conf->file->dol_document_root as $dirroot)
		{
			$dir = $dirroot."/core/modules/facture/";
			// Load file with numbering class (if found)
			$mybool|=@include_once $dir.$file;
		}

		// For compatibility
		if (! $mybool)
		{
			$file = $conf->global->FACTURE_ADDON."/".$conf->global->FACTURE_ADDON.".modules.php";
			$classname = "mod_facture_".$conf->global->FACTURE_ADDON;
			$classname = preg_replace('/\-.*$/','',$classname);
			// Include file with class
			foreach ($conf->file->dol_document_root as $dirroot)
			{
				$dir = $dirroot."/core/modules/facture/";
				// Load file with numbering class (if found)
				$mybool|=@include_once $dir.$file;
			}
		}
		//print "xx".$mybool.$dir.$file."-".$classname;

		if (! $mybool)
		{
			dol_print_error('',"Failed to include file ".$file);
			return '';
		}

		$obj = new $classname();
		$numref = "";
		$numref = $obj->getNumRef($soc,$this,$mode);

		if ($numref != "")
		{
			return $numref;
		}
		else
		{
			//dol_print_error($db,get_class($this)."::getNextNumRef ".$obj->error);
			return false;
		}
	}

	/**
	 *	Load miscellaneous information for tab "Info"
	 *
	 *	@param  int		$id		Id of object to load
	 *	@return	void
	 */
	function info($id)
	{
		$sql = 'SELECT c.rowid, datec, date_valid as datev, tms as datem,';
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
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation     = $cuser;
				}
				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);	// Should be in log table
			}
			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *	Renvoi si les lignes de facture sont ventilees et/ou exportees en compta
	 *
	 *   @return     int         <0 if KO, 0=no, 1=yes
	 */
	function getVentilExportCompta()
	{
		// On verifie si les lignes de factures ont ete exportees en compta et/ou ventilees
		$ventilExportCompta = 0 ;
		$num=count($this->lines);
		for ($i = 0; $i < $num; $i++)
		{
			if ($this->lines[$i]->export_compta <> 0 && $this->lines[$i]->code_ventilation <> 0)
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
	 *  Return if an invoice can be deleted
	 *	Rule is:
	 *	If hidden option INVOICE_CAN_ALWAYS_BE_REMOVED is on, we can
	 *  If invoice has a definitive ref, is last, without payment and not dipatched into accountancy -> yes end of rule
	 *  If invoice is draft and ha a temporary ref -> yes
	 *
	 *  @return    int         <0 if KO, 0=no, 1=yes
	 */
	function is_erasable()
	{
		global $conf;

		if (! empty($conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED)) return 1;
		if (! empty($conf->global->INVOICE_CAN_NEVER_BE_REMOVED))  return 0;

		// on verifie si la facture est en numerotation provisoire
		$facref = substr($this->ref, 1, 4);

		// If not a draft invoice and not temporary invoice
		if ($facref != 'PROV')
		{
			$maxfacnumber = $this->getNextNumRef($this->client,'last');
			$ventilExportCompta = $this->getVentilExportCompta();
			// If there is no invoice into the reset range and not already dispatched, we can delete
			if ($maxfacnumber == '' && $ventilExportCompta == 0) return 1;
			// If invoice to delete is last one and not already dispatched, we can delete
			if ($maxfacnumber == $this->ref && $ventilExportCompta == 0) return 1;
		}
		else if ($this->statut == 0 && $facref == 'PROV') // Si facture brouillon et provisoire
		{
			return 1;
		}

		return 0;
	}


	/**
	 *  Return list of invoices (eventually filtered on a user) into an array
	 *
	 *  @param		int		$shortlist		0=Return array[id]=ref, 1=Return array[](id=>id,ref=>ref,name=>name)
	 *  @param      int		$draft      	0=not draft, 1=draft
	 *  @param      User	$excluser      	Objet user to exclude
	 *  @param    	int		$socid			Id third pary
	 *  @param    	int		$limit			For pagination
	 *  @param    	int		$offset			For pagination
	 *  @param    	string	$sortfield		Sort criteria
	 *  @param    	string	$sortorder		Sort order
	 *  @return     int             		-1 if KO, array with result if OK
	 */
	function liste_array($shortlist=0, $draft=0, $excluser='', $socid=0, $limit=0, $offset=0, $sortfield='f.datef,f.rowid', $sortorder='DESC')
	{
		global $conf,$user;

		$ga = array();

		$sql = "SELECT s.rowid, s.nom as name, s.client,";
		$sql.= " f.rowid as fid, f.facnumber as ref, f.datef as df";
		if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", sc.fk_soc, sc.fk_user";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f";
		if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE f.entity = ".$conf->entity;
		$sql.= " AND f.fk_soc = s.rowid";
		if (! $user->rights->societe->client->voir && ! $socid) //restriction
		{
			$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
		}
		if ($socid) $sql.= " AND s.rowid = ".$socid;
		if ($draft) $sql.= " AND f.fk_statut = 0";
		if (is_object($excluser)) $sql.= " AND f.fk_user_author <> ".$excluser->id;
		$sql.= $this->db->order($sortfield,$sortorder);
		$sql.= $this->db->plimit($limit,$offset);

		$result=$this->db->query($sql);
		if ($result)
		{
			$numc = $this->db->num_rows($result);
			if ($numc)
			{
				$i = 0;
				while ($i < $numc)
				{
					$obj = $this->db->fetch_object($result);

					if ($shortlist == 1)
					{
						$ga[$obj->fid] = $obj->ref;
					}
					else if ($shortlist == 2)
					{
						$ga[$obj->fid] = $obj->ref.' ('.$obj->name.')';
					}
					else
					{
						$ga[$i]['id']	= $obj->fid;
						$ga[$i]['ref'] 	= $obj->ref;
						$ga[$i]['name'] = $obj->name;
					}
					$i++;
				}
			}
			return $ga;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *	Renvoi liste des factures remplacables
	 *	Statut validee ou abandonnee pour raison autre + non payee + aucun paiement + pas deja remplacee
	 *
	 *	@param		int		$socid		Id societe
	 *	@return    	array				Tableau des factures ('id'=>id, 'ref'=>ref, 'status'=>status, 'paymentornot'=>0/1)
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
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND f.paye = 0";					// Pas classee payee completement
		$sql.= " AND pf.fk_paiement IS NULL";		// Aucun paiement deja fait
		$sql.= " AND ff.fk_statut IS NULL";			// Renvoi vrai si pas facture de remplacement
		if ($socid > 0) $sql.=" AND f.fk_soc = ".$socid;
		$sql.= " ORDER BY f.facnumber";

		dol_syslog(get_class($this)."::list_replacable_invoices sql=$sql");
		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$return[$obj->rowid]=array(	'id' => $obj->rowid,
				'ref' => $obj->facnumber,
				'status' => $obj->fk_statut);
			}
			//print_r($return);
			return $return;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::list_replacable_invoices ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *	Renvoi liste des factures qualifiables pour correction par avoir
	 *	Les factures qui respectent les regles suivantes sont retournees:
	 *	(validee + paiement en cours) ou classee (payee completement ou payee partiellement) + pas deja remplacee + pas deja avoir
	 *
	 *	@param		int		$socid		Id societe
	 *	@return    	array				Tableau des factures ($id => array('ref'=>,'paymentornot'=>,'status'=>,'paye'=>)
	 */
	function list_qualified_avoir_invoices($socid=0)
	{
		global $conf;

		$return = array();

		$sql = "SELECT f.rowid as rowid, f.facnumber, f.fk_statut, f.type, f.paye, pf.fk_paiement";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid = pf.fk_facture";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as ff ON (f.rowid = ff.fk_facture_source AND ff.type=1)";
		$sql.= " WHERE f.entity = ".$conf->entity;
		$sql.= " AND f.fk_statut in (1,2)";
		//  $sql.= " WHERE f.fk_statut >= 1";
		//	$sql.= " AND (f.paye = 1";				// Classee payee completement
		//	$sql.= " OR f.close_code IS NOT NULL)";	// Classee payee partiellement
		$sql.= " AND ff.type IS NULL";			// Renvoi vrai si pas facture de remplacement
		$sql.= " AND f.type != 2";				// Type non 2 si facture non avoir
		if ($socid > 0) $sql.=" AND f.fk_soc = ".$socid;
		$sql.= " ORDER BY f.facnumber";

		dol_syslog(get_class($this)."::list_qualified_avoir_invoices sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$qualified=0;
				if ($obj->fk_statut == 1) $qualified=1;
				if ($obj->fk_statut == 2) $qualified=1;
				if ($qualified)
				{
					//$ref=$obj->facnumber;
					$paymentornot=($obj->fk_paiement?1:0);
					$return[$obj->rowid]=array('ref'=>$obj->facnumber,'status'=>$obj->fk_statut,'type'=>$obj->type,'paye'=>$obj->paye,'paymentornot'=>$paymentornot);
				}
			}

			return $return;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::list_avoir_invoices ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *	Create a withdrawal request for a standing order
	 *
	 *	@param      User	$user       User asking standing order
	 *	@return     int         		<0 if KO, >0 if OK
	 */
	function demande_prelevement($user)
	{
		dol_syslog(get_class($this)."::demande_prelevement", LOG_DEBUG);

		if ($this->statut > 0 && $this->paye == 0)
		{
	        require_once DOL_DOCUMENT_ROOT . '/societe/class/companybankaccount.class.php';
	        $bac = new CompanyBankAccount($this->db);
	        $bac->fetch(0,$this->socid);

        	$sql = 'SELECT count(*)';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'prelevement_facture_demande';
			$sql.= ' WHERE fk_facture = '.$this->id;
			$sql.= ' AND traite = 0';

			dol_syslog(get_class($this)."::demande_prelevement sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$row = $this->db->fetch_row($resql);
				if ($row[0] == 0)
				{
					$now=dol_now();

                    $totalpaye  = $this->getSommePaiement();
                    $totalcreditnotes = $this->getSumCreditNotesUsed();
                    $totaldeposits = $this->getSumDepositsUsed();
                    //print "totalpaye=".$totalpaye." totalcreditnotes=".$totalcreditnotes." totaldeposts=".$totaldeposits;

                    // We can also use bcadd to avoid pb with floating points
                    // For example print 239.2 - 229.3 - 9.9; does not return 0.
                    //$resteapayer=bcadd($this->total_ttc,$totalpaye,$conf->global->MAIN_MAX_DECIMALS_TOT);
                    //$resteapayer=bcadd($resteapayer,$totalavoir,$conf->global->MAIN_MAX_DECIMALS_TOT);
                    $resteapayer = price2num($this->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits,'MT');

                    $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'prelevement_facture_demande';
                    $sql .= ' (fk_facture, amount, date_demande, fk_user_demande, code_banque, code_guichet, number, cle_rib)';
                    $sql .= ' VALUES ('.$this->id;
                    $sql .= ",'".price2num($resteapayer)."'";
                    $sql .= ",'".$this->db->idate($now)."',";
                    $sql .= ",".$user->id;
                    $sql .= ",'".$bac->code_banque."'";
                    $sql .= ",'".$bac->code_guichet."'";
                    $sql .= ",'".$bac->number."'";
                    $sql .= ",'".$bac->cle_rib."')";
                    if ( $this->db->query($sql))
                    {
                        return 1;
                    }
                    else
                    {
                        $this->error=$this->db->error();
                        dol_syslog(get_class($this).'::demandeprelevement Erreur');
                        return -1;
                    }
                }
                else
                {
                    $this->error="A request already exists";
                    dol_syslog(get_class($this).'::demandeprelevement Impossible de creer une demande, demande deja en cours');
                }
            }
            else
            {
                $this->error=$this->db->error();
                dol_syslog(get_class($this).'::demandeprelevement Erreur -2');
                return -2;
            }
        }
        else
        {
            $this->error="Status of invoice does not allow this";
            dol_syslog(get_class($this)."::demandeprelevement ".$this->error." $this->statut, $this->paye, $this->mode_reglement_id");
            return -3;
        }
    }

	/**
	 *  Supprime une demande de prelevement
	 *
	 *  @param  Use		$user       utilisateur creant la demande
	 *  @param  int		$did        id de la demande a supprimer
	 *  @return	int					<0 if OK, >0 if KO
	 */
	function demande_prelevement_delete($user, $did)
	{
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'prelevement_facture_demande';
		$sql .= ' WHERE rowid = '.$did;
		$sql .= ' AND traite = 0';
		if ( $this->db->query($sql) )
		{
			return 0;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this).'::demande_prelevement_delete Error '.$this->error);
			return -1;
		}
	}


	/**
	 *	Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *	@param      User	$user    	Object user
	 *	@return     int                 <0 if KO, >0 if OK
	 */
	function load_board($user)
	{
		global $conf, $user;

		$now=dol_now();

		$this->nbtodo=$this->nbtodolate=0;
		$clause = " WHERE";

		$sql = "SELECT f.rowid, f.date_lim_reglement as datefin";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
		if (!$user->rights->societe->client->voir && !$user->societe_id)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON f.fk_soc = sc.fk_soc";
			$sql.= " WHERE sc.fk_user = " .$user->id;
			$clause = " AND";
		}
		$sql.= $clause." f.paye=0";
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND f.fk_statut = 1";
		if ($user->societe_id) $sql.= " AND f.fk_soc = ".$user->societe_id;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nbtodo++;
				if ($this->db->jdate($obj->datefin) < ($now - $conf->facture->client->warning_delay)) $this->nbtodolate++;
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


	/* gestion des contacts d'une facture */

	/**
	 *	Retourne id des contacts clients de facturation
	 *
	 *	@return     array       Liste des id contacts facturation
	 */
	function getIdBillingContact()
	{
		return $this->getIdContact('external','BILLING');
	}

	/**
	 *	Retourne id des contacts clients de livraison
	 *
	 *	@return     array       Liste des id contacts livraison
	 */
	function getIdShippingContact()
	{
		return $this->getIdContact('external','SHIPPING');
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
					$prodid = rand(1, $num_prods);
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
					$prodid = rand(1, $num_prods);
					$line->fk_product=$prodids[$prodid];
					$line->total_ht=50;
					$line->total_ttc=59.8;
					$line->total_tva=9.8;
					$line->remise_percent=50;
				}
				else    // (product line)
				{
					$prodid = rand(1, $num_prods);
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
			$prodid = rand(1, $num_prods);
			$line->fk_product=$prodids[$prodid];

			$this->lines[$xnbp]=$line;
			$xnbp++;
		}
	}

	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @return         int     <0 if KO, >0 if OK
	 */
	function load_state_board()
	{
		global $conf, $user;

		$this->nb=array();

		$clause = "WHERE";

		$sql = "SELECT count(f.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
		if (!$user->rights->societe->client->voir && !$user->societe_id)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql.= " WHERE sc.fk_user = " .$user->id;
			$clause = "AND";
		}
		$sql.= " ".$clause." f.entity = ".$conf->entity;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["invoices"]=$obj->nb;
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
	 * 	Create an array of invoice lines
	 *
	 * 	@return int		>0 if OK, <0 if KO
	 */
	function getLinesArray()
	{
		$sql = 'SELECT l.rowid, l.label as custom_label, l.description, l.fk_product, l.product_type, l.qty, l.tva_tx,';
		$sql.= ' l.fk_remise_except, l.localtax1_tx, l.localtax2_tx,';
		$sql.= ' l.remise_percent, l.subprice, l.info_bits, l.rang, l.special_code, l.fk_parent_line,';
		$sql.= ' l.total_ht, l.total_tva, l.total_ttc, l.fk_product_fournisseur_price as fk_fournprice, l.buy_price_ht as pa_ht,';
		$sql.= ' l.date_start, l.date_end,';
		$sql.= ' p.ref as product_ref, p.fk_product_type, p.label as product_label,';
		$sql.= ' p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet as l';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product p ON l.fk_product=p.rowid';
		$sql.= ' WHERE l.fk_facture = '.$this->id;
		$sql.= ' ORDER BY l.rang ASC, l.rowid';

		dol_syslog(get_class($this).'::getLinesArray sql='.$sql,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$this->lines[$i]					= new FactureLigne($this->db);
				$this->lines[$i]->id				= $obj->rowid;
				$this->lines[$i]->label 			= $obj->custom_label;
				$this->lines[$i]->description 		= $obj->description;
				$this->lines[$i]->fk_product		= $obj->fk_product;
				$this->lines[$i]->ref				= $obj->product_ref;
				$this->lines[$i]->product_label		= $obj->product_label;
				$this->lines[$i]->product_desc		= $obj->product_desc;
				$this->lines[$i]->fk_product_type	= $obj->fk_product_type;
				$this->lines[$i]->product_type		= $obj->product_type;
				$this->lines[$i]->qty				= $obj->qty;
				$this->lines[$i]->subprice			= $obj->subprice;
				$this->lines[$i]->fk_remise_except 	= $obj->fk_remise_except;
				$this->lines[$i]->remise_percent	= $obj->remise_percent;
				$this->lines[$i]->tva_tx			= $obj->tva_tx;
				$this->lines[$i]->info_bits			= $obj->info_bits;
				$this->lines[$i]->total_ht			= $obj->total_ht;
				$this->lines[$i]->total_tva			= $obj->total_tva;
				$this->lines[$i]->total_ttc			= $obj->total_ttc;
				$this->lines[$i]->fk_parent_line	= $obj->fk_parent_line;
				$this->lines[$i]->special_code		= $obj->special_code;
				$this->lines[$i]->rang				= $obj->rang;
				$this->lines[$i]->date_start		= $this->db->jdate($obj->date_start);
				$this->lines[$i]->date_end			= $this->db->jdate($obj->date_end);
				$this->lines[$i]->fk_fournprice		= $obj->fk_fournprice;
				$marginInfos						= getMarginInfos($obj->subprice, $obj->remise_percent, $obj->tva_tx, $obj->localtax1_tx, $obj->localtax2_tx, $this->lines[$i]->fk_fournprice, $obj->pa_ht);
				$this->lines[$i]->pa_ht				= $marginInfos[0];
				$this->lines[$i]->marge_tx			= $marginInfos[1];
				$this->lines[$i]->marque_tx			= $marginInfos[2];

				$i++;
			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("Error sql=".$sql.", error=".$this->error,LOG_ERR);
			return -1;
		}
	}

}



/**
 *	\class      	FactureLigne
 *	\brief      	Classe permettant la gestion des lignes de factures
 *					Gere des lignes de la table llx_facturedet
 */
class FactureLigne  extends CommonInvoiceLine
{
	var $db;
	var $error;

    public $element='facturedet';
    public $table_element='facturedet';

	var $oldline;

	//! From llx_facturedet
	var $rowid;
	//! Id facture
	var $fk_facture;
	//! Id parent line
	var $fk_parent_line;
	var $label;
	//! Description ligne
	var $desc;
	var $fk_product;		// Id of predefined product
	var $product_type = 0;	// Type 0 = product, 1 = Service

	var $qty;				// Quantity (example 2)
	var $tva_tx;			// Taux tva produit/service (example 19.6)
	var $localtax1_tx;		// Local tax 1
	var $localtax2_tx;		// Local tax 2
	var $localtax1_type;	// Local tax 1 type
	var $localtax2_type;	// Local tax 2 type
	var $subprice;      	// P.U. HT (example 100)
	var $remise_percent;	// % de la remise ligne (example 20%)
	var $fk_remise_except;	// Link to line into llx_remise_except
	var $rang = 0;

	var $fk_fournprice;
	var $pa_ht;
	var $marge_tx;
	var $marque_tx;

	var $info_bits = 0;		// Liste d'options cumulables:
	// Bit 0:	0 si TVA normal - 1 si TVA NPR
	// Bit 1:	0 si ligne normal - 1 si bit discount (link to line into llx_remise_except)

	var $special_code;	// Liste d'options non cumulabels:
	// 1: frais de port
	// 2: ecotaxe
	// 3: ??

	var $origin;
	var $origin_id;

	//! Total HT  de la ligne toute quantite et incluant la remise ligne
	var $total_ht;
	//! Total TVA  de la ligne toute quantite et incluant la remise ligne
	var $total_tva;
	var $total_localtax1; //Total Local tax 1 de la ligne
	var $total_localtax2; //Total Local tax 2 de la ligne
	//! Total TTC de la ligne toute quantite et incluant la remise ligne
	var $total_ttc;

	var $fk_code_ventilation = 0;

	var $date_start;
	var $date_end;

	// Ne plus utiliser
	//var $price;         	// P.U. HT apres remise % de ligne (exemple 80)
	//var $remise;			// Montant calcule de la remise % sur PU HT (exemple 20)

	// From llx_product
	var $ref;				// Product ref (deprecated)
	var $product_ref;       // Product ref
	var $libelle;      		// Product label (deprecated)
	var $product_label;     // Product label
	var $product_desc;  	// Description produit

	var $skip_update_total; // Skip update price total for special lines


	/**
	 *  Constructor
	 *
	 *  @param	DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Load invoice line from database
	 *
	 *	@param	int		$rowid      id of invoice line to get
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT fd.rowid, fd.fk_facture, fd.fk_parent_line, fd.fk_product, fd.product_type, fd.label as custom_label, fd.description, fd.price, fd.qty, fd.tva_tx,';
		$sql.= ' fd.localtax1_tx, fd. localtax2_tx, fd.remise, fd.remise_percent, fd.fk_remise_except, fd.subprice,';
		$sql.= ' fd.date_start as date_start, fd.date_end as date_end, fd.fk_product_fournisseur_price as fk_fournprice, fd.buy_price_ht as pa_ht,';
		$sql.= ' fd.info_bits, fd.total_ht, fd.total_tva, fd.total_ttc, fd.total_localtax1, fd.total_localtax2, fd.rang,';
		$sql.= ' fd.fk_code_ventilation,';
		$sql.= ' p.ref as product_ref, p.label as product_libelle, p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet as fd';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON fd.fk_product = p.rowid';
		$sql.= ' WHERE fd.rowid = '.$rowid;

		$result = $this->db->query($sql);
		if ($result)
		{
			$objp = $this->db->fetch_object($result);

			$this->rowid				= $objp->rowid;
			$this->fk_facture			= $objp->fk_facture;
			$this->fk_parent_line		= $objp->fk_parent_line;
			$this->label				= $objp->custom_label;
			$this->desc					= $objp->description;
			$this->qty					= $objp->qty;
			$this->subprice				= $objp->subprice;
			$this->tva_tx				= $objp->tva_tx;
			$this->localtax1_tx			= $objp->localtax1_tx;
			$this->localtax2_tx			= $objp->localtax2_tx;
			$this->remise_percent		= $objp->remise_percent;
			$this->fk_remise_except		= $objp->fk_remise_except;
			$this->fk_product			= $objp->fk_product;
			$this->product_type			= $objp->product_type;
			$this->date_start			= $this->db->jdate($objp->date_start);
			$this->date_end				= $this->db->jdate($objp->date_end);
			$this->info_bits			= $objp->info_bits;
			$this->total_ht				= $objp->total_ht;
			$this->total_tva			= $objp->total_tva;
			$this->total_localtax1		= $objp->total_localtax1;
			$this->total_localtax2		= $objp->total_localtax2;
			$this->total_ttc			= $objp->total_ttc;
			$this->fk_code_ventilation	= $objp->fk_code_ventilation;
			$this->rang					= $objp->rang;
			$this->fk_fournprice		= $objp->fk_fournprice;
			$marginInfos				= getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $this->fk_fournprice, $objp->pa_ht);
			$this->pa_ht				= $marginInfos[0];
			$this->marge_tx				= $marginInfos[1];
			$this->marque_tx			= $marginInfos[2];

			$this->ref					= $objp->product_ref;      // deprecated
			$this->product_ref			= $objp->product_ref;
			$this->libelle				= $objp->product_libelle;  // deprecated
			$this->product_label		= $objp->product_libelle;
			$this->product_desc			= $objp->product_desc;

			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *	Insert line in database
	 *
	 *	@param      int		$notrigger		1 no triggers
	 *	@return		int						<0 if KO, >0 if OK
	 */
	function insert($notrigger=0)
	{
		global $langs,$user,$conf;

		$error=0;

		dol_syslog(get_class($this)."::insert rang=".$this->rang, LOG_DEBUG);

		// Clean parameters
		$this->desc=trim($this->desc);
		if (empty($this->tva_tx)) $this->tva_tx=0;
		if (empty($this->localtax1_tx)) $this->localtax1_tx=0;
		if (empty($this->localtax2_tx)) $this->localtax2_tx=0;
		if (empty($this->localtax1_type)) $this->localtax1_type=0;
		if (empty($this->localtax2_type)) $this->localtax2_type=0;
		if (empty($this->total_localtax1)) $this->total_localtax1=0;
		if (empty($this->total_localtax2)) $this->total_localtax2=0;
		if (empty($this->rang)) $this->rang=0;
		if (empty($this->remise_percent)) $this->remise_percent=0;
		if (empty($this->info_bits)) $this->info_bits=0;
		if (empty($this->subprice)) $this->subprice=0;
		if (empty($this->special_code)) $this->special_code=0;
		if (empty($this->fk_parent_line)) $this->fk_parent_line=0;

		if (empty($this->pa_ht)) $this->pa_ht=0;

		// si prix d'achat non renseigne et utilise pour calcul des marges alors prix achat = prix vente
		if ($this->pa_ht == 0) {
			if ($this->subprice > 0 && (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1))
				$this->pa_ht = $this->subprice * (1 - $this->remise_percent / 100);
		}

		// Check parameters
		if ($this->product_type < 0) return -1;

		$this->db->begin();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facturedet';
		$sql.= ' (fk_facture, fk_parent_line, label, description, qty,';
		$sql.= ' tva_tx, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type,';
		$sql.= ' fk_product, product_type, remise_percent, subprice, fk_remise_except,';
		$sql.= ' date_start, date_end, fk_code_ventilation, ';
		$sql.= ' rang, special_code, fk_product_fournisseur_price, buy_price_ht,';
		$sql.= ' info_bits, total_ht, total_tva, total_ttc, total_localtax1, total_localtax2)';
		$sql.= " VALUES (".$this->fk_facture.",";
		$sql.= " ".($this->fk_parent_line>0?"'".$this->fk_parent_line."'":"null").",";
		$sql.= " ".(! empty($this->label)?"'".$this->db->escape($this->label)."'":"null").",";
		$sql.= " '".$this->db->escape($this->desc)."',";
		$sql.= " ".price2num($this->qty).",";
		$sql.= " ".price2num($this->tva_tx).",";
		$sql.= " ".price2num($this->localtax1_tx).",";
		$sql.= " ".price2num($this->localtax2_tx).",";
		$sql.= " '".$this->localtax1_type."',";
		$sql.= " '".$this->localtax2_type."',";
		$sql.= ' '.(! empty($this->fk_product)?$this->fk_product:"null").',';
		$sql.= " ".$this->product_type.",";
		$sql.= " ".price2num($this->remise_percent).",";
		$sql.= " ".price2num($this->subprice).",";
		$sql.= ' '.(! empty($this->fk_remise_except)?$this->fk_remise_except:"null").',';
		$sql.= " ".(! empty($this->date_start)?"'".$this->db->idate($this->date_start)."'":"null").",";
		$sql.= " ".(! empty($this->date_end)?"'".$this->db->idate($this->date_end)."'":"null").",";
		$sql.= ' '.$this->fk_code_ventilation.',';
		$sql.= ' '.$this->rang.',';
		$sql.= ' '.$this->special_code.',';
		$sql.= ' '.(! empty($this->fk_fournprice)?$this->fk_fournprice:"null").',';
		$sql.= ' '.price2num($this->pa_ht).',';
		$sql.= " '".$this->info_bits."',";
		$sql.= " ".price2num($this->total_ht).",";
		$sql.= " ".price2num($this->total_tva).",";
		$sql.= " ".price2num($this->total_ttc).",";
		$sql.= " ".price2num($this->total_localtax1).",";
		$sql.= " ".price2num($this->total_localtax2);
		$sql.= ')';

		dol_syslog(get_class($this)."::insert sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->rowid=$this->db->last_insert_id(MAIN_DB_PREFIX.'facturedet');

            if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
            {
            	$this->id=$this->rowid;
            	$result=$this->insertExtraFields();
            	if ($result < 0)
            	{
            		$error++;
            	}
            }

			// Si fk_remise_except defini, on lie la remise a la facture
			// ce qui la flague comme "consommee".
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
							dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
							$this->db->rollback();
							return -3;
						}
						else
						{
							$result=$discount->link_to_invoice($this->rowid,0);
							if ($result < 0)
							{
								$this->error=$discount->error;
								dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
								$this->db->rollback();
								return -3;
							}
						}
					}
					else
					{
						$this->error=$langs->trans("ErrorADiscountThatHasBeenRemovedIsIncluded");
						dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
						$this->db->rollback();
						return -3;
					}
				}
				else
				{
					$this->error=$discount->error;
					dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
					$this->db->rollback();
					return -3;
				}
			}

			if (! $notrigger)
			{
				// Appel des triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result = $interface->run_triggers('LINEBILL_INSERT',$this,$user,$langs,$conf);
				if ($result < 0) {
					$error++; $this->errors=$interface->errors;
				}
				// Fin appel triggers
			}

			$this->db->commit();
			return $this->rowid;

		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 *	Update line into database
	 *
	 *	@param		User	$user		User object
	 *	@param		int		$notrigger	Disable triggers
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function update($user='',$notrigger=0)
	{
		global $user,$langs,$conf;

		$error=0;

		// Clean parameters
		$this->desc=trim($this->desc);
		if (empty($this->tva_tx)) $this->tva_tx=0;
		if (empty($this->localtax1_tx)) $this->localtax1_tx=0;
		if (empty($this->localtax2_tx)) $this->localtax2_tx=0;
		if (empty($this->localtax1_type)) $this->localtax1_type=0;
		if (empty($this->localtax2_type)) $this->localtax2_type=0;
		if (empty($this->total_localtax1)) $this->total_localtax1=0;
		if (empty($this->total_localtax2)) $this->total_localtax2=0;
		if (empty($this->remise_percent)) $this->remise_percent=0;
		if (empty($this->info_bits)) $this->info_bits=0;
		if (empty($this->special_code)) $this->special_code=0;
		if (empty($this->product_type)) $this->product_type=0;
		if (empty($this->fk_parent_line)) $this->fk_parent_line=0;

		// Check parameters
		if ($this->product_type < 0) return -1;

		if (empty($this->pa_ht)) $this->pa_ht=0;

		// si prix d'achat non renseigne et utilise pour calcul des marges alors prix achat = prix vente
		if ($this->pa_ht == 0) {
			if ($this->subprice > 0 && (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1))
				$this->pa_ht = $this->subprice * (1 - $this->remise_percent / 100);
		}

		$this->db->begin();

        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."facturedet SET";
        $sql.= " description='".$this->db->escape($this->desc)."'";
        $sql.= ",label=".(! empty($this->label)?"'".$this->db->escape($this->label)."'":"null");
        $sql.= ",subprice=".price2num($this->subprice)."";
        $sql.= ",remise_percent=".price2num($this->remise_percent)."";
        if ($this->fk_remise_except) $sql.= ",fk_remise_except=".$this->fk_remise_except;
        else $sql.= ",fk_remise_except=null";
        $sql.= ",tva_tx=".price2num($this->tva_tx)."";
        $sql.= ",localtax1_tx=".price2num($this->localtax1_tx)."";
        $sql.= ",localtax2_tx=".price2num($this->localtax2_tx)."";
		$sql.= ",localtax1_type='".$this->localtax1_type."'";
		$sql.= ",localtax2_type='".$this->localtax2_type."'";
        $sql.= ",qty=".price2num($this->qty)."";
        $sql.= ",date_start=".(! empty($this->date_start)?"'".$this->db->idate($this->date_start)."'":"null");
        $sql.= ",date_end=".(! empty($this->date_end)?"'".$this->db->idate($this->date_end)."'":"null");
        $sql.= ",product_type=".$this->product_type;
        $sql.= ",info_bits='".$this->info_bits."'";
        $sql.= ",special_code='".$this->special_code."'";
        if (empty($this->skip_update_total))
        {
        	$sql.= ",total_ht=".price2num($this->total_ht)."";
        	$sql.= ",total_tva=".price2num($this->total_tva)."";
        	$sql.= ",total_ttc=".price2num($this->total_ttc)."";
        	$sql.= ",total_localtax1=".price2num($this->total_localtax1)."";
        	$sql.= ",total_localtax2=".price2num($this->total_localtax2)."";
        }
		$sql.= " , fk_product_fournisseur_price=".(! empty($this->fk_fournprice)?"'".$this->db->escape($this->fk_fournprice)."'":"null");
		$sql.= " , buy_price_ht='".price2num($this->pa_ht)."'";
		$sql.= ",fk_parent_line=".($this->fk_parent_line>0?$this->fk_parent_line:"null");
		if (! empty($this->rang)) $sql.= ", rang=".$this->rang;
		$sql.= " WHERE rowid = ".$this->rowid;

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
        	if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
        	{
        		$this->id=$this->rowid;
        		$result=$this->insertExtraFields();
        		if ($result < 0)
        		{
        			$error++;
        		}
        	}

			if (! $notrigger)
			{
				// Appel des triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result = $interface->run_triggers('LINEBILL_UPDATE',$this,$user,$langs,$conf);
				if ($result < 0) {
					$error++; $this->errors=$interface->errors;
				}
				// Fin appel triggers
			}
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::update Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}

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

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."facturedet WHERE rowid = ".$this->rowid;
		dol_syslog(get_class($this)."::delete sql=".$sql, LOG_DEBUG);
		if ($this->db->query($sql) )
		{
			// Appel des triggers
			include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
			$interface=new Interfaces($this->db);
			$result = $interface->run_triggers('LINEBILL_DELETE',$this,$user,$langs,$conf);
			if ($result < 0) {
				$error++; $this->errors=$interface->errors;
			}
			// Fin appel triggers

			$this->db->commit();

			return 1;
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			dol_syslog(get_class($this)."::delete Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Mise a jour en base des champs total_xxx de ligne de facture
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function update_total()
	{
		$this->db->begin();
		dol_syslog(get_class($this)."::update_total", LOG_DEBUG);

		// Clean parameters
		if (empty($this->total_localtax1)) $this->total_localtax1=0;
		if (empty($this->total_localtax2)) $this->total_localtax2=0;

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet SET";
		$sql.= " total_ht=".price2num($this->total_ht)."";
		$sql.= ",total_tva=".price2num($this->total_tva)."";
		$sql.= ",total_localtax1=".price2num($this->total_localtax1)."";
		$sql.= ",total_localtax2=".price2num($this->total_localtax2)."";
		$sql.= ",total_ttc=".price2num($this->total_ttc)."";
		$sql.= " WHERE rowid = ".$this->rowid;

		dol_syslog(get_class($this)."::update_total sql=".$sql, LOG_DEBUG);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::update_total Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}
}

?>
