<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier        <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2014 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2010-2016 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2012-2014 Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2012-2015 Marcos García         <marcosgdf@gmail.com>
 * Copyright (C) 2012      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014 Raphaël Doursenaud    <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013      Cedric Gross          <c.gross@kreiz-it.fr>
 * Copyright (C) 2013      Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2016      Ferran Marcet        <fmarcet@2byte.es>
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
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';

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
	public $picto='bill';
	
	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'facnumber';

	public $socid;

	public $author;
	public $fk_user_author;
	public $fk_user_valid;
	public $date;              // Date invoice
	public $date_creation;		// Creation date
	public $date_validation;	// Validation date
	public $datem;
	public $ref_client;
	public $ref_int;
	//Check constants for types
	public $type = self::TYPE_STANDARD;

	//var $amount;
	public $remise_absolue;
	public $remise_percent;
	public $total_ht=0;
	public $total_tva=0;
	public $total_ttc=0;
	public $revenuestamp;

	//! Fermeture apres paiement partiel: discount_vat, badcustomer, abandon
	//! Fermeture alors que aucun paiement: replaced (si remplace), abandon
	public $close_code;
	//! Commentaire si mis a paye sans paiement complet
	public $close_note;
	//! 1 if invoice paid COMPLETELY, 0 otherwise (do not use it anymore, use statut and close_code)
	public $paye;
	//! id of source invoice if replacement invoice or credit note
	public $fk_facture_source;
	public $linked_objects=array();
	public $date_lim_reglement;
	public $cond_reglement_code;		// Code in llx_c_paiement
	public $mode_reglement_code;		// Code in llx_c_paiement
	public $fk_bank;					// Field to store bank id to use when payment mode is withdraw
	/**
	 * @deprecated
	 */
	public $products=array();
	/**
	 * @var FactureLigne[]
	 */
	public $lines=array();
	public $line;
	public $extraparams=array();
	public $specimen;

	public $fac_rec;

	// Multicurrency
	public $fk_multicurrency;
	public $multicurrency_code;
	public $multicurrency_tx;
	public $multicurrency_total_ht;
	public $multicurrency_total_tva;
	public $multicurrency_total_ttc;

	/**
	 * @var int Situation cycle reference number
	 */
	public $situation_cycle_ref;

	/**
	 * @var int Situation counter inside the cycle
	 */
	public $situation_counter;

	/**
	 * @var bool Final situation flag
	 */
	public $situation_final;

	/**
	 * @var array Table of previous situations
	 */
	public $tab_previous_situation_invoice=array();

	/**
	 * @var array Table of next situations
	 */
	public $tab_next_situation_invoice=array();

	public $oldcopy;

    /**
     * Standard invoice
     */
    const TYPE_STANDARD = 0;

    /**
     * Replacement invoice
     */
    const TYPE_REPLACEMENT = 1;

    /**
     * Credit note invoice
     */
    const TYPE_CREDIT_NOTE = 2;

    /**
     * Deposit invoice
     */
    const TYPE_DEPOSIT = 3;

    /**
     * Proforma invoice (should not be used. a proforma is an order)
     */
    const TYPE_PROFORMA = 4;

	/**
	 * Situation invoice
	 */
	const TYPE_SITUATION = 5;

	/**
	 * Draft
	 */
	const STATUS_DRAFT = 0;

	/**
	 * Validated (need to be paid)
	 */
	const STATUS_VALIDATED = 1;

	/**
	 * Classified paid.
	 * If paid partially, $this->close_code can be:
	 * - CLOSECODE_DISCOUNTVAT
	 * - CLOSECODE_BADDEBT
	 * If paid completelly, this->close_code will be null
	 */
	const STATUS_CLOSED = 2;

	/**
	 * Classified abandoned and no payment done.
	 * $this->close_code can be:
	 * - CLOSECODE_BADDEBT
	 * - CLOSECODE_ABANDONED
	 * - CLOSECODE_REPLACED
	 */
	const STATUS_ABANDONED = 3;

	const CLOSECODE_DISCOUNTVAT = 'discount_vat';
	const CLOSECODE_BADDEBT = 'badcustomer';
	const CLOSECODE_ABANDONED = 'abandon';
	const CLOSECODE_REPLACED = 'replaced';

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
	 *	Create invoice in database.
	 *  Note: this->ref can be set or empty. If empty, we will use "(PROV999)"
	 *  Note: this->fac_rec must be set to create invoice from a recurring invoice
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
		if (empty($this->type)) $this->type = self::TYPE_STANDARD;
		$this->ref_client=trim($this->ref_client);
		$this->note=(isset($this->note) ? trim($this->note) : trim($this->note_private)); // deprecated
		$this->note_private=(isset($this->note_private) ? trim($this->note_private) : trim($this->note_private));
		$this->note_public=trim($this->note_public);
		if (! $this->cond_reglement_id) $this->cond_reglement_id = 0;
		if (! $this->mode_reglement_id) $this->mode_reglement_id = 0;
		$this->brouillon = 1;
        if (empty($this->entity)) $this->entity = $conf->entity;
        
		// Multicurrency (test on $this->multicurrency_tx because we sould take the default rate only if not using origin rate)
		if (!empty($this->multicurrency_code) && empty($this->multicurrency_tx)) list($this->fk_multicurrency,$this->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($this->db, $this->multicurrency_code);
		else $this->fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $this->multicurrency_code);
		if (empty($this->fk_multicurrency))
		{
			$this->multicurrency_code = $conf->currency;
			$this->fk_multicurrency = 0;
			$this->multicurrency_tx = 1;
		}

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
			$this->error="Failed to fetch company: ".$soc->error;
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -2;
		}

		$now=dol_now();

		$this->db->begin();

		// Create invoice from a template invoice
		if ($this->fac_rec > 0)
		{
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
			$_facrec = new FactureRec($this->db);
			$result=$_facrec->fetch($this->fac_rec);
			$result=$_facrec->fetchObjectLinked();       // This load $_facrec->linkedObjectsIds

			$this->socid 		     = $_facrec->socid;  // Invoice created on same thirdparty than template
			$this->entity            = $_facrec->entity; // Invoice created in same entity than template
			
			// Fields coming from GUI (priority on template). TODO Value of template should be used as default value on GUI so we can use here always value from GUI
			$this->fk_project        = GETPOST('projectid','int') > 0 ? GETPOST('projectid','int') : $_facrec->fk_project;
			$this->note_public       = GETPOST('note_public') ? GETPOST('note_public') : $_facrec->note_public;
			$this->note_private      = GETPOST('note_private') ? GETPOST('note_private') : $_facrec->note_private;
			$this->modelpdf          = GETPOST('model') ? GETPOST('model') : $_facrec->modelpdf;
			$this->cond_reglement_id = GETPOST('cond_reglement_id') > 0 ? GETPOST('cond_reglement_id') : $_facrec->cond_reglement_id;
			$this->mode_reglement_id = GETPOST('mode_reglement_id') > 0 ? GETPOST('mode_reglement_id') : $_facrec->mode_reglement_id;
			$this->fk_account        = GETPOST('fk_account') > 0 ? GETPOST('fk_account') : $_facrec->fk_account;

			// Set here to have this defined for substitution into notes, should be recalculated after adding lines to get same result
			$this->total_ht          = $_facrec->total_ht;
			$this->total_ttc         = $_facrec->total_ttc;
				
			// Fields always coming from template
			$this->remise_absolue    = $_facrec->remise_absolue;
			$this->remise_percent    = $_facrec->remise_percent;
			$this->fk_incoterms		 = $_facrec->fk_incoterms;
			$this->location_incoterms= $_facrec->location_incoterms;

			// Clean parameters
			if (! $this->type) $this->type = self::TYPE_STANDARD;
			$this->ref_client=trim($this->ref_client);
			$this->note_public=trim($this->note_public);
			$this->note_private=trim($this->note_private);
		    $this->note_private=dol_concatdesc($this->note_private, $langs->trans("GeneratedFromRecurringInvoice", $_facrec->ref));

			//if (! $this->remise) $this->remise = 0;
			if (! $this->mode_reglement_id) $this->mode_reglement_id = 0;
			$this->brouillon = 1;

			$this->linked_objects = $_facrec->linkedObjectsIds;

			$forceduedate = $this->calculate_date_lim_reglement();

			// For recurring invoices, update date and number of last generation of recurring template invoice, before inserting new invoice
			if ($_facrec->frequency > 0)
			{
			    dol_syslog("This is a recurring invoice so we set date_last_gen and next date_when");
			    if (empty($_facrec->date_when)) $_facrec->date_when = $now;
                $next_date = $_facrec->getNextDate();   // Calculate next date
                $result = $_facrec->setValueFrom('date_last_gen', $now, '', null, 'date', '', $user, '');
                //$_facrec->setValueFrom('nb_gen_done', $_facrec->nb_gen_done + 1);		// Not required, +1 already included into setNextDate when second param is 1.
                $result = $_facrec->setNextDate($next_date,1);
			}

			// Define lang of customer
			$outputlangs = $langs;
			$newlang='';

			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && isset($this->thirdparty->default_lang)) $newlang=$this->thirdparty->default_lang;  // for proposal, order, invoice, ...
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && isset($this->default_lang)) $newlang=$this->default_lang;                  // for thirdparty
			if (! empty($newlang))
			{
			    $outputlangs = new Translate("",$conf);
			    $outputlangs->setDefaultLang($newlang);
			}

			// Array of possible substitutions (See also file mailing-send.php that should manage same substitutions)
			$substitutionarray=array(
			    '__TOTAL_HT__' => price($this->total_ht, 0, $outputlangs, 0, 0, -1, $conf->currency_code),
			    '__TOTAL_TTC__' => price($this->total_ttc, 0, $outputlangs, 0, 0, -1, $conf->currency_code),
			    '__INVOICE_PREVIOUS_MONTH__' => dol_print_date(dol_time_plus_duree($this->date, -1, 'm'), '%m'),
			    '__INVOICE_MONTH__' => dol_print_date($this->date, '%m'),
			    '__INVOICE_NEXT_MONTH__' => dol_print_date(dol_time_plus_duree($this->date, 1, 'm'), '%m'),
			    '__INVOICE_PREVIOUS_MONTH_TEXT__' => dol_print_date(dol_time_plus_duree($this->date, -1, 'm'), '%B'),
			    '__INVOICE_MONTH_TEXT__' => dol_print_date($this->date, '%B'),
			    '__INVOICE_NEXT_MONTH_TEXT__' => dol_print_date(dol_time_plus_duree($this->date, 1, 'm'), '%B'),
			    '__INVOICE_PREVIOUS_YEAR__' => dol_print_date(dol_time_plus_duree($this->date, -1, 'y'), '%Y'),
			    '__INVOICE_YEAR__' => dol_print_date($this->date, '%Y'),
			    '__INVOICE_NEXT_YEAR__' => dol_print_date(dol_time_plus_duree($this->date, 1, 'y'), '%Y'),
			);
			
			$substitutionisok=true;
			complete_substitutions_array($substitutionarray, $outputlangs);
			
			$this->note_public=make_substitutions($this->note_public,$substitutionarray);
			$this->note_private=make_substitutions($this->note_private,$substitutionarray);
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
		$sql.= ", date_pointoftax";
		$sql.= ", note_private";
		$sql.= ", note_public";
		$sql.= ", ref_client, ref_int";
        $sql.= ", fk_account";
		$sql.= ", fk_facture_source, fk_user_author, fk_projet";
		$sql.= ", fk_cond_reglement, fk_mode_reglement, date_lim_reglement, model_pdf";
		$sql.= ", situation_cycle_ref, situation_counter, situation_final";
		$sql.= ", fk_incoterms, location_incoterms";
        $sql.= ", fk_multicurrency";
        $sql.= ", multicurrency_code";
        $sql.= ", multicurrency_tx";
		$sql.= ")";
		$sql.= " VALUES (";
		$sql.= "'(PROV)'";
		$sql.= ", ".$this->entity;
		$sql.= ", ".($this->ref_ext?"'".$this->db->escape($this->ref_ext)."'":"null");
		$sql.= ", '".$this->db->escape($this->type)."'";
		$sql.= ", '".$socid."'";
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", ".($this->remise_absolue>0?$this->remise_absolue:'NULL');
		$sql.= ", ".($this->remise_percent>0?$this->remise_percent:'NULL');
		$sql.= ", '".$this->db->idate($this->date)."'";
		$sql.= ", ".(strval($this->date_pointoftax)!='' ? "'".$this->db->idate($this->date_pointoftax)."'" : 'null');
		$sql.= ", ".($this->note_private?"'".$this->db->escape($this->note_private)."'":"null");
		$sql.= ", ".($this->note_public?"'".$this->db->escape($this->note_public)."'":"null");
		$sql.= ", ".($this->ref_client?"'".$this->db->escape($this->ref_client)."'":"null");
		$sql.= ", ".($this->ref_int?"'".$this->db->escape($this->ref_int)."'":"null");
		$sql.= ", ".($this->fk_account>0?$this->fk_account:'NULL');
		$sql.= ", ".($this->fk_facture_source?"'".$this->db->escape($this->fk_facture_source)."'":"null");
		$sql.= ", ".($user->id > 0 ? "'".$user->id."'":"null");
		$sql.= ", ".($this->fk_project?$this->fk_project:"null");
		$sql.= ", ".$this->cond_reglement_id;
		$sql.= ", ".$this->mode_reglement_id;
		$sql.= ", '".$this->db->idate($datelim)."', '".$this->db->escape($this->modelpdf)."'";
		$sql.= ", ".($this->situation_cycle_ref?"'".$this->db->escape($this->situation_cycle_ref)."'":"null");
		$sql.= ", ".($this->situation_counter?"'".$this->db->escape($this->situation_counter)."'":"null");
		$sql.= ", ".($this->situation_final?$this->situation_final:0);
		$sql.= ", ".(int) $this->fk_incoterms;
        $sql.= ", '".$this->db->escape($this->location_incoterms)."'";
		$sql.= ", ".(int) $this->fk_multicurrency;
		$sql.= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql.= ", ".(double) $this->multicurrency_tx;
		$sql.=")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture');

			// Update ref with new one
			$this->ref='(PROV'.$this->id.')';
			$sql = 'UPDATE '.MAIN_DB_PREFIX."facture SET facnumber='".$this->ref."' WHERE rowid=".$this->id;

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql) $error++;

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
				                dol_print_error($this->db);
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
    						dol_print_error($this->db);
    						$error++;
    					}
				    }
				}
			}
			
			if (! $error && $this->id && ! empty($conf->global->MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN) && ! empty($this->origin) && ! empty($this->origin_id))   // Get contact from origin object
			{
				$originforcontact = $this->origin;
				$originidforcontact = $this->origin_id;
				if ($originforcontact == 'shipping')     // shipment and order share the same contacts. If creating from shipment we take data of order
				{
				    require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
				    $exp = new Expedition($this->db);
				    $exp->fetch($this->origin_id);
				    $exp->fetchObjectLinked();
				    if (count($exp->linkedObjectsIds['commande']) > 0) 
				    {
				        foreach ($exp->linkedObjectsIds['commande'] as $key => $value)
				        {
				            $originforcontact = 'commande';
				            $originidforcontact = $value->id;
				            break; // We take first one
				        }
				    }
				}
				
				$sqlcontact = "SELECT ctc.code, ctc.source, ec.fk_socpeople FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as ctc";
				$sqlcontact.= " WHERE element_id = ".$originidforcontact." AND ec.fk_c_type_contact = ctc.rowid AND ctc.element = '".$originforcontact."'";
	
				$resqlcontact = $this->db->query($sqlcontact);
				if ($resqlcontact)
				{
				    while($objcontact = $this->db->fetch_object($resqlcontact))
				    {
				        //print $objcontact->code.'-'.$objcontact->source.'-'.$objcontact->fk_socpeople."\n";
				        $this->add_contact($objcontact->fk_socpeople, $objcontact->code, $objcontact->source);    // May failed because of duplicate key or because code of contact type does not exists for new object
				    }
				}
				else dol_print_error($resqlcontact);
			}

			/*
			 *  Insert lines of invoices into database
			 */
			if (count($this->lines) && is_object($this->lines[0]))	// If this->lines is array of InvoiceLines (preferred mode)
			{
				$fk_parent_line = 0;

				dol_syslog("There is ".count($this->lines)." lines that are invoice lines objects");
				foreach ($this->lines as $i => $val)
				{
					$newinvoiceline=$this->lines[$i];
					$newinvoiceline->fk_facture=$this->id;
                    $newinvoiceline->origin = $this->element;           // TODO This seems not used. Here we but origin 'facture' but after
                    $newinvoiceline->origin_id = $this->lines[$i]->id;  // we put an id of object !
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
			else	// If this->lines is an array of invoice line arrays
			{
				$fk_parent_line = 0;

				dol_syslog("There is ".count($this->lines)." lines that are array lines");

				foreach ($this->lines as $i => $val)
				{
                	$line = $this->lines[$i];
                	
                	// Test and convert into object this->lines[$i]. When coming from REST API, we may still have an array
				    //if (! is_object($line)) $line=json_decode(json_encode($line), FALSE);  // convert recursively array into object.
                	if (! is_object($line)) $line = (object) $line;
				    
				    if (($line->info_bits & 0x01) == 0)	// We keep only lines with first bit = 0
					{
						// Reset fk_parent_line for no child products and special product
						if (($line->product_type != 9 && empty($line->fk_parent_line)) || $line->product_type == 9) {
							$fk_parent_line = 0;
						}

						$result = $this->addline(
							$line->desc,
							$line->subprice,
							$line->qty,
							$line->tva_tx,
							$line->localtax1_tx,
							$line->localtax2_tx,
							$line->fk_product,
							$line->remise_percent,
							$line->date_start,
							$line->date_end,
							$line->fk_code_ventilation,
							$line->info_bits,
							$line->fk_remise_except,
							'HT',
							0,
							$line->product_type,
							$line->rang,
							$line->special_code,
                            $this->element,
                            $line->id,
							$fk_parent_line,
							$line->fk_fournprice,
							$line->pa_ht,
							$line->label,
							$line->array_options,
							$line->situation_percent,
							$line->fk_prev_id,
							$line->fk_unit
						);
						if ($result < 0)
						{
							$this->error=$this->db->lasterror();
							dol_print_error($this->db);
							$this->db->rollback();
							return -1;
						}

						// Defined the new fk_parent_line
						if ($result > 0 && $line->product_type == 9) {
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
					$tva_npr = get_default_npr($mysoc,$soc,$prod->id);
					if (empty($tva_tx)) $tva_npr=0;
					$localtax1_tx=get_localtax($tva_tx,1,$soc,$mysoc,$tva_npr);
					$localtax2_tx=get_localtax($tva_tx,2,$soc,$mysoc,$tva_npr);

					$result_insert = $this->addline(
						$_facrec->lines[$i]->desc,
						$_facrec->lines[$i]->subprice,
						$_facrec->lines[$i]->qty,
						$tva_tx,
						$localtax1_tx,
						$localtax2_tx,
						$_facrec->lines[$i]->fk_product,
						$_facrec->lines[$i]->remise_percent,
						'','',0,$tva_npr,'','HT',0,
						$_facrec->lines[$i]->product_type,
						$_facrec->lines[$i]->rang,
						$_facrec->lines[$i]->special_code,
						'',
						0,
						0,
						null,
						0,
						$_facrec->lines[$i]->label,
						null,
						$_facrec->lines[$i]->situation_percent,
						'',
						$_facrec->lines[$i]->fk_unit
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
					$action='create';

					// Actions on extra fields (by external module or standard code)
					// TODO le hook fait double emploi avec le trigger !!
					/*
					$hookmanager->initHooks(array('invoicedao'));
					$parameters=array('invoiceid'=>$this->id);
					$reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action); // Note that $action and $object may have been modified by some hooks
					if (empty($reshook))
					{
						if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
						{*/
					if (! $error)
					{
					    $result=$this->insertExtraFields();
					    if ($result < 0) $error++;
					}
						/*}
					}
					else if ($reshook < 0) $error++;*/

                    // Call trigger
                    $result=$this->call_trigger('BILL_CREATE',$user);
                    if ($result < 0) $error++;
                    // End call triggers

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
		global $conf;

		// Charge facture source
		$facture=new Facture($this->db);

		$facture->fk_facture_source = $this->fk_facture_source;
		$facture->type 			    = $this->type;
		$facture->socid 		    = $this->socid;
		$facture->date              = $this->date;
		$facture->date_pointoftax   = $this->date_pointoftax;
		$facture->note_public       = $this->note_public;
		$facture->note_private      = $this->note_private;
		$facture->ref_client        = $this->ref_client;
		$facture->modelpdf          = $this->modelpdf;
		$facture->fk_project        = $this->fk_project;
		$facture->cond_reglement_id = $this->cond_reglement_id;
		$facture->mode_reglement_id = $this->mode_reglement_id;
		$facture->remise_absolue    = $this->remise_absolue;
		$facture->remise_percent    = $this->remise_percent;

		$facture->origin                        = $this->origin;
		$facture->origin_id                     = $this->origin_id;

		$facture->lines		    	= $this->lines;	// Tableau des lignes de factures
		$facture->products		    = $this->lines;	// Tant que products encore utilise
		$facture->situation_counter = $this->situation_counter;
		$facture->situation_cycle_ref=$this->situation_cycle_ref;
		$facture->situation_final  = $this->situation_final;

		// Loop on each line of new invoice
		foreach($facture->lines as $i => $line)
		{
			$facture->lines[$i]->fk_prev_id = $this->lines[$i]->rowid;
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
		elseif ($this->type == self::TYPE_SITUATION && !empty($conf->global->INVOICE_USE_SITUATION))
		{
			$this->fetchObjectLinked('', '', $object->id, 'facture');

			foreach ($this->linkedObjectsIds as $typeObject => $Tfk_object)
			{
				foreach ($Tfk_object as $fk_object)
				{
					$facture->add_object_linked($typeObject, $fk_object);
				}
			}

			$facture->add_object_linked('facture', $this->fk_facture_source);
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
		global $user,$hookmanager;

		$error=0;

		$this->context['createfromclone'] = 'createfromclone';

		$this->db->begin();

		// get extrafields so they will be clone
		foreach($this->lines as $line)
			$line->fetch_optionals($line->rowid);

		// Load source object
		$objFrom = clone $this;



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
		$this->statut= self::STATUS_DRAFT;

		// Clear fields
		$this->date               = dol_now();	// Date of invoice is set to current date when cloning. // TODO Best is to ask date into confirm box
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
		else {
			// copy internal contacts
			if ($this->copy_linked_contact($objFrom, 'internal') < 0)
				$error++;

			// copy external contacts if same company
			elseif ($objFrom->socid == $this->socid)
			{
				if ($this->copy_linked_contact($objFrom, 'external') < 0)
					$error++;
			}
		}

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

            // Call trigger
            $result=$this->call_trigger('BILL_CLONE',$user);
            if ($result < 0) $error++;
            // End call triggers
		}

		unset($this->context['createfromclone']);

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
		global $user,$hookmanager;

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
			$line->vat_src_code  	= $object->lines[$i]->vat_src_code;
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
			$line->fk_unit			= $object->lines[$i]->fk_unit;

			$line->fk_fournprice	= $object->lines[$i]->fk_fournprice;
			$marginInfos			= getMarginInfos($object->lines[$i]->subprice, $object->lines[$i]->remise_percent, $object->lines[$i]->tva_tx, $object->lines[$i]->localtax1_tx, $object->lines[$i]->localtax2_tx, $object->lines[$i]->fk_fournprice, $object->lines[$i]->pa_ht);
			$line->pa_ht			= $marginInfos[0];

            // get extrafields from original line
			$object->lines[$i]->fetch_optionals($object->lines[$i]->rowid);
			foreach($object->lines[$i]->array_options as $options_key => $value)
				$line->array_options[$options_key] = $value;

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

        // get extrafields from original line
		$object->fetch_optionals($object->id);
		foreach($object->array_options as $options_key => $value)
			$this->array_options[$options_key] = $value;

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
     *      @param	int  	$notooltip		 1=Disable tooltip
     *      @param  int     $addlinktonotes  1=Add link to notes
	 *      @return string 			         String with URL
	 */
	function getNomUrl($withpicto=0,$option='',$max=0,$short=0,$moretitle='',$notooltip=0,$addlinktonotes=0)
	{
		global $langs, $conf, $user, $form;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

		$result='';

		if ($option == 'withdraw') $url = DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$this->id;
		else $url = DOL_URL_ROOT.'/compta/facture.php?facid='.$this->id;

		if ($short) return $url;

		$picto='bill';
		if ($this->type == self::TYPE_REPLACEMENT) $picto.='r';	// Replacement invoice
		if ($this->type == self::TYPE_CREDIT_NOTE) $picto.='a';	// Credit note
		if ($this->type == self::TYPE_DEPOSIT) $picto.='d';	// Deposit invoice
        $label='';
        
        if ($user->rights->facture->lire) {
            $label = '<u>' . $langs->trans("ShowInvoice") . '</u>';
            if (! empty($this->ref))
                $label .= '<br><b>'.$langs->trans('Ref') . ':</b> ' . $this->ref;
            if (! empty($this->ref_client))
                $label .= '<br><b>' . $langs->trans('RefCustomer') . ':</b> ' . $this->ref_client;
            if (! empty($this->total_ht))
                $label.= '<br><b>' . $langs->trans('AmountHT') . ':</b> ' . price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
            if (! empty($this->total_tva))
                $label.= '<br><b>' . $langs->trans('VAT') . ':</b> ' . price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
            if (! empty($this->total_ttc))
                $label.= '<br><b>' . $langs->trans('AmountTTC') . ':</b> ' . price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
    		if ($this->type == self::TYPE_REPLACEMENT) $label=$langs->transnoentitiesnoconv("ShowInvoiceReplace").': '.$this->ref;
    		if ($this->type == self::TYPE_CREDIT_NOTE) $label=$langs->transnoentitiesnoconv("ShowInvoiceAvoir").': '.$this->ref;
    		if ($this->type == self::TYPE_DEPOSIT) $label=$langs->transnoentitiesnoconv("ShowInvoiceDeposit").': '.$this->ref;
    		if ($this->type == self::TYPE_SITUATION) $label=$langs->transnoentitiesnoconv("ShowInvoiceSituation").': '.$this->ref;
    		if ($moretitle) $label.=' - '.$moretitle;
        }
        
		$linkclose='';
		if (empty($notooltip) && $user->rights->facture->lire)
		{
		    if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
		    {
		        $label=$langs->trans("ShowInvoice");
		        $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
		    }
		    $linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
		    $linkclose.=' class="classfortooltip"';
		}

        $linkstart='<a href="'.$url.'"';
        $linkstart.=$linkclose.'>';
		$linkend='</a>';

        if ($withpicto) $result.=($linkstart.img_object(($notooltip?'':$label), $picto, ($notooltip?'':'class="classfortooltip"'), 0, 0, $notooltip?0:1).$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$linkstart.($max?dol_trunc($this->ref,$max):$this->ref).$linkend;

		if ($addlinktonotes)
		{
		    $txttoshow=($user->societe_id>0?$this->note_public:$this->note_private);
		    if ($txttoshow)
		    {
                $notetoshow=$langs->trans("ViewPrivateNote").':<br>'.dol_string_nohtmltag($txttoshow,1);
    		    $result.=' <span class="note inline-block">';
    		    $result.='<a href="'.DOL_URL_ROOT.'/compta/facture/note.php?id='.$this->id.'" class="classfortooltip" title="'.dol_escape_htmltag($notetoshow).'">'.img_picto('','object_generic').'</a>';
    		    //$result.=img_picto($langs->trans("ViewNote"),'object_generic');
    		    //$result.='</a>';
    		    $result.='</span>';
		    }
		}
		
		return $result;
	}

	/**
	 *	Get object and lines from database
	 *
	 *	@param      int		$rowid       	Id of object to load
	 * 	@param		string	$ref			Reference of invoice
	 * 	@param		string	$ref_ext		External reference of invoice
	 * 	@param		int		$ref_int		Internal reference of other object
	 *  @param		bool	$fetch_situation	Fetch the previous and next situation in $tab_previous_situation_invoice and $tab_next_situation_invoice
	 *	@return     int         			>0 if OK, <0 if KO, 0 if not found
	 */
	function fetch($rowid, $ref='', $ref_ext='', $ref_int='', $fetch_situation=false)
	{
		global $conf;

		if (empty($rowid) && empty($ref) && empty($ref_ext) && empty($ref_int)) return -1;

		$sql = 'SELECT f.rowid,f.facnumber,f.ref_client,f.ref_ext,f.ref_int,f.type,f.fk_soc,f.amount';
		$sql.= ', f.tva, f.localtax1, f.localtax2, f.total, f.total_ttc, f.revenuestamp';
		$sql.= ', f.remise_percent, f.remise_absolue, f.remise';
		$sql.= ', f.datef as df, f.date_pointoftax';
		$sql.= ', f.date_lim_reglement as dlr';
		$sql.= ', f.datec as datec';
		$sql.= ', f.date_valid as datev';
		$sql.= ', f.tms as datem';
		$sql.= ', f.note_private, f.note_public, f.fk_statut, f.paye, f.close_code, f.close_note, f.fk_user_author, f.fk_user_valid, f.model_pdf';
		$sql.= ', f.fk_facture_source';
		$sql.= ', f.fk_mode_reglement, f.fk_cond_reglement, f.fk_projet, f.extraparams';
		$sql.= ', f.situation_cycle_ref, f.situation_counter, f.situation_final';
		$sql.= ', f.fk_account';
		$sql.= ", f.fk_multicurrency, f.multicurrency_code, f.multicurrency_tx, f.multicurrency_total_ht, f.multicurrency_total_tva, f.multicurrency_total_ttc";
		$sql.= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
		$sql.= ', c.code as cond_reglement_code, c.libelle as cond_reglement_libelle, c.libelle_facture as cond_reglement_libelle_doc';
        $sql.= ', f.fk_incoterms, f.location_incoterms';
        $sql.= ", i.libelle as libelle_incoterms";
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as c ON f.fk_cond_reglement = c.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON f.fk_mode_reglement = p.id';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON f.fk_incoterms = i.rowid';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		if ($rowid)   $sql.= " AND f.rowid=".$rowid;
		if ($ref)     $sql.= " AND f.facnumber='".$this->db->escape($ref)."'";
		if ($ref_ext) $sql.= " AND f.ref_ext='".$this->db->escape($ref_ext)."'";
		if ($ref_int) $sql.= " AND f.ref_int='".$this->db->escape($ref_int)."'";

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
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
				$this->date_pointoftax		= $this->db->jdate($obj->date_pointoftax);
				$this->date_creation		= $this->db->jdate($obj->datec);
				$this->date_validation		= $this->db->jdate($obj->datev);
				$this->datem				= $this->db->jdate($obj->datem);
				$this->remise_percent		= $obj->remise_percent;
				$this->remise_absolue		= $obj->remise_absolue;
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
				$this->fk_account           = ($obj->fk_account>0)?$obj->fk_account:null;
				$this->fk_project			= $obj->fk_projet;
				$this->fk_facture_source	= $obj->fk_facture_source;
				$this->note					= $obj->note_private;	// deprecated
				$this->note_private			= $obj->note_private;
				$this->note_public			= $obj->note_public;
				$this->user_author			= $obj->fk_user_author;
				$this->user_valid			= $obj->fk_user_valid;
				$this->modelpdf				= $obj->model_pdf;
				$this->situation_cycle_ref  = $obj->situation_cycle_ref;
				$this->situation_counter    = $obj->situation_counter;
				$this->situation_final      = $obj->situation_final;
				$this->extraparams			= (array) json_decode($obj->extraparams, true);

				//Incoterms
				$this->fk_incoterms = $obj->fk_incoterms;
				$this->location_incoterms = $obj->location_incoterms;
				$this->libelle_incoterms = $obj->libelle_incoterms;

				// Multicurrency
				$this->fk_multicurrency 		= $obj->fk_multicurrency;
				$this->multicurrency_code 		= $obj->multicurrency_code;
				$this->multicurrency_tx 		= $obj->multicurrency_tx;
				$this->multicurrency_total_ht 	= $obj->multicurrency_total_ht;
				$this->multicurrency_total_tva 	= $obj->multicurrency_total_tva;
				$this->multicurrency_total_ttc 	= $obj->multicurrency_total_ttc;

				if ($this->type == self::TYPE_SITUATION && $fetch_situation)
				{
					$this->fetchPreviousNextSituationInvoice();
				}

				if ($this->statut == self::STATUS_DRAFT)	$this->brouillon = 1;

				// Retrieve all extrafield for invoice
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
					return -3;
				}
				return 1;
			}
			else
			{
				$this->error='Bill with id '.$rowid.' or ref '.$ref.' not found';
				dol_syslog(get_class($this)."::fetch Error ".$this->error, LOG_ERR);
				return 0;
			}
		}
		else
		{
			$this->error=$this->db->error();
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

		$sql = 'SELECT l.rowid, l.fk_facture, l.fk_product, l.fk_parent_line, l.label as custom_label, l.description, l.product_type, l.price, l.qty, l.vat_src_code, l.tva_tx,';
		$sql.= ' l.situation_percent, l.fk_prev_id,';
		$sql.= ' l.localtax1_tx, l.localtax2_tx, l.localtax1_type, l.localtax2_type, l.remise_percent, l.fk_remise_except, l.subprice,';
		$sql.= ' l.rang, l.special_code,';
		$sql.= ' l.date_start as date_start, l.date_end as date_end,';
		$sql.= ' l.info_bits, l.total_ht, l.total_tva, l.total_localtax1, l.total_localtax2, l.total_ttc, l.fk_code_ventilation, l.fk_product_fournisseur_price as fk_fournprice, l.buy_price_ht as pa_ht,';
		$sql.= ' l.fk_unit,';
		$sql.= ' l.fk_multicurrency, l.multicurrency_code, l.multicurrency_subprice, l.multicurrency_total_ht, l.multicurrency_total_tva, l.multicurrency_total_ttc,';
		$sql.= ' p.ref as product_ref, p.fk_product_type as fk_product_type, p.label as product_label, p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet as l';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
		$sql.= ' WHERE l.fk_facture = '.$this->id;
		$sql.= ' ORDER BY l.rang, l.rowid';

		dol_syslog(get_class($this).'::fetch_lines', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);
				$line = new FactureLigne($this->db);

				$line->id               = $objp->rowid;
				$line->rowid	        = $objp->rowid;             // deprecated
				$line->fk_facture       = $objp->fk_facture;
				$line->label            = $objp->custom_label;		// deprecated
				$line->desc             = $objp->description;		// Description line
				$line->description      = $objp->description;		// Description line
				$line->product_type     = $objp->product_type;		// Type of line
				$line->ref              = $objp->product_ref;		// Ref product
				$line->product_ref      = $objp->product_ref;		// Ref product
				$line->libelle          = $objp->product_label;		// TODO deprecated
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
				$line->situation_percent= $objp->situation_percent;
				$line->fk_prev_id       = $objp->fk_prev_id;
				$line->fk_unit	        = $objp->fk_unit;

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
			$this->error=$this->db->error();
			return -3;
		}
	}

	/**
	 * Fetch previous and next situations invoices
	 *
	 * @return	void
	 */
	function fetchPreviousNextSituationInvoice()
	{
		global $conf;

		$this->tab_previous_situation_invoice = array();
		$this->tab_next_situation_invoice = array();

		$sql = 'SELECT rowid, situation_counter FROM '.MAIN_DB_PREFIX.'facture WHERE rowid <> '.$this->id.' AND entity = '.$conf->entity.' AND situation_cycle_ref = '.(int) $this->situation_cycle_ref.' ORDER BY situation_counter ASC';

		dol_syslog(get_class($this).'::fetchPreviousNextSituationInvoice ', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result && $this->db->num_rows($result) > 0)
		{
			while ($objp = $this->db->fetch_object($result))
			{
				$invoice = new Facture($this->db);
				if ($invoice->fetch($objp->rowid) > 0)
				{
					if ($objp->situation_counter < $this->situation_counter) $this->tab_previous_situation_invoice[] = $invoice;
					else $this->tab_next_situation_invoice[] = $invoice;
				}
			}
		}

	}

	/**
	 *      Update database
	 *
	 *      @param      User	$user        	User that modify
	 *      @param      int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *      @return     int      			   	<0 if KO, >0 if OK
	 */
	function update($user=null, $notrigger=0)
	{
		$error=0;

		// Clean parameters
		if (empty($this->type)) $this->type= self::TYPE_STANDARD;
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
		if (empty($this->situation_cycle_ref)) {
			$this->situation_cycle_ref = 'null';
		}

		if (empty($this->situation_counter)) {
			$this->situation_counter = 'null';
		}

		if (empty($this->situation_final)) {
			$this->situation_final = '0';
		}

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
		$sql.= " date_pointoftax=".(strval($this->date_pointoftax)!='' ? "'".$this->db->idate($this->date_pointoftax)."'" : 'null').",";
		$sql.= " date_valid=".(strval($this->date_validation)!='' ? "'".$this->db->idate($this->date_validation)."'" : 'null').",";
		$sql.= " paye=".(isset($this->paye)?$this->paye:"null").",";
		$sql.= " remise_percent=".(isset($this->remise_percent)?$this->remise_percent:"null").",";
		$sql.= " remise_absolue=".(isset($this->remise_absolue)?$this->remise_absolue:"null").",";
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
		$sql.= " import_key=".(isset($this->import_key)?"'".$this->db->escape($this->import_key)."'":"null");
		$sql.= ", situation_cycle_ref=".$this->situation_cycle_ref;
		$sql.= ", situation_counter=".$this->situation_counter;
		$sql.= ", situation_final=".$this->situation_final;

		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error++; $this->errors[]="Error ".$this->db->lasterror();
		}

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Call trigger
	            $result=$this->call_trigger('BILL_MODIFY',$user);
	            if ($result < 0) $error++;
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
			$facligne->vat_src_code=$remise->vat_src_code;
			$facligne->tva_tx=$remise->tva_tx;
			$facligne->subprice=-$remise->amount_ht;
			$facligne->fk_product=0;					// Id produit predefini
			$facligne->qty=1;
			$facligne->remise_percent=0;
			$facligne->rang=-1;
			$facligne->info_bits=2;

			// Get buy/cost price of invoice that is source of discount
			if ($remise->fk_facture_source > 0)
			{
    			$srcinvoice=new Facture($this->db);
    			$srcinvoice->fetch($remise->fk_facture_source);
    			$totalcostpriceofinvoice=0;
    			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';  // TODO Move this into commonobject
    			$formmargin=new FormMargin($this->db);
    			$arraytmp=$formmargin->getMarginInfosArray($srcinvoice, false);
        		$facligne->pa_ht = $arraytmp['pa_total'];
			}
			
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
	 *  @param     	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return		int						<0 if KO, >0 if OK
	 */
	function set_ref_client($ref_client, $notrigger=0)
	{
	    global $user;
	    
		$error=0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
		if (empty($ref_client))
			$sql .= ' SET ref_client = NULL';
		else
			$sql .= ' SET ref_client = \''.$this->db->escape($ref_client).'\'';
		$sql .= ' WHERE rowid = '.$this->id;

		dol_syslog(__METHOD__.' this->id='.$this->id.', ref_client='.$ref_client, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (!$resql)
		{
			$this->errors[]=$this->db->error();
			$error++;
		}

		if (! $error)
		{
			$this->ref_client = $ref_client;
		}

		if (! $notrigger && empty($error))
		{
			// Call trigger
			$result=$this->call_trigger('BILL_MODIFY',$user);
			if ($result < 0) $error++;
			// End call triggers
		}

		if (! $error)
		{

			$this->ref_client = $ref_client;

			$this->db->commit();
			return 1;
		}
		else
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
	}

	/**
	 *	Delete invoice
	 *
	 *	@param     	User	$user      	    User making the deletion.
	 *	@param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@param		int		$idwarehouse	Id warehouse to use for stock change.
	 *	@return		int						<0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0, $idwarehouse=-1)
	{
		global $langs,$conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		if (empty($rowid)) $rowid=$this->id;

		dol_syslog(get_class($this)."::delete rowid=".$rowid, LOG_DEBUG);

		// TODO Test if there is at least one payment. If yes, refuse to delete.

		$error=0;
		$this->db->begin();

		if (! $error && ! $notrigger)
		{
            // Call trigger
            $result=$this->call_trigger('BILL_DELETE',$user);
            if ($result < 0) $error++;
            // End call triggers
		}

		// Removed extrafields
		if (! $error) {
			$result=$this->deleteExtraFields();
			if ($result < 0)
			{
				$error++;
				dol_syslog(get_class($this)."::delete error deleteExtraFields ".$this->error, LOG_ERR);
			}
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

				dol_syslog(get_class($this)."::delete", LOG_DEBUG);
				if (! $this->db->query($sql))
				{
					$this->error=$this->db->error()." sql=".$sql;
					$this->db->rollback();
					return -5;
				}
			}

			// If we decrement stock on invoice validation, we increment
			if ($this->type != self::TYPE_DEPOSIT && $result >= 0 && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $idwarehouse!=-1)
			{
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$langs->load("agenda");

				$num=count($this->lines);
				for ($i = 0; $i < $num; $i++)
				{
					if ($this->lines[$i]->fk_product > 0)
					{
						$mouvP = new MouvementStock($this->db);
						$mouvP->origin = &$this;
						// We decrease stock for product
						if ($this->type == self::TYPE_CREDIT_NOTE) $result=$mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("InvoiceDeleteDolibarr",$this->ref));
						else $result=$mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, 0, $langs->trans("InvoiceDeleteDolibarr",$this->ref));	// we use 0 for price, to not change the weighted average value
					}
				}
			}


			// Delete invoice line
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facturedet WHERE fk_facture = '.$rowid;

			dol_syslog(get_class($this)."::delete", LOG_DEBUG);

			if ($this->db->query($sql) && $this->delete_linked_contact())
			{
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture WHERE rowid = '.$rowid;

				dol_syslog(get_class($this)."::delete", LOG_DEBUG);

				$resql=$this->db->query($sql);
				if ($resql)
				{
					// On efface le repertoire de pdf provisoire
					$ref = dol_sanitizeFileName($this->ref);
					if ($conf->facture->dir_output && !empty($this->ref))
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
					$this->db->rollback();
					return -6;
				}
			}
			else
			{
				$this->error=$this->db->lasterror()." sql=".$sql;
				$this->db->rollback();
				return -4;
			}
		}
		else
		{
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 *  Tag la facture comme paye completement (si close_code non renseigne) => this->fk_statut=2, this->paye=1
	 *  ou partiellement (si close_code renseigne) + appel trigger BILL_PAYED => this->fk_statut=2, this->paye stay 0
	 *
	 *  @param	User	$user      	Objet utilisateur qui modifie
	 *	@param  string	$close_code	Code renseigne si on classe a payee completement alors que paiement incomplet (cas escompte par exemple)
	 *	@param  string	$close_note	Commentaire renseigne si on classe a payee alors que paiement incomplet (cas escompte par exemple)
	 *  @return int         		<0 if KO, >0 if OK
	 */
	function set_paid($user, $close_code='', $close_note='')
	{
		$error=0;

		if ($this->paye != 1)
		{
			$this->db->begin();

			dol_syslog(get_class($this)."::set_paid rowid=".$this->id, LOG_DEBUG);
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture SET';
			$sql.= ' fk_statut='.self::STATUS_CLOSED;
			if (! $close_code) $sql.= ', paye=1';
			if ($close_code) $sql.= ", close_code='".$this->db->escape($close_code)."'";
			if ($close_note) $sql.= ", close_note='".$this->db->escape($close_note)."'";
			$sql.= ' WHERE rowid = '.$this->id;

			dol_syslog(get_class($this)."::set_paid", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql)
			{
	            // Call trigger
	            $result=$this->call_trigger('BILL_PAYED',$user);
	            if ($result < 0) $error++;
	            // End call triggers
			}
			else
			{
				$error++;
				$this->error=$this->db->lasterror();
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
		$error=0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
		$sql.= ' SET paye=0, fk_statut='.self::STATUS_VALIDATED.', close_code=null, close_note=null';
		$sql.= ' WHERE rowid = '.$this->id;

		dol_syslog(get_class($this)."::set_unpaid", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
            // Call trigger
            $result=$this->call_trigger('BILL_UNPAYED',$user);
            if ($result < 0) $error++;
            // End call triggers
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

		dol_syslog(get_class($this)."::set_canceled rowid=".$this->id, LOG_DEBUG);

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture SET';
		$sql.= ' fk_statut='.self::STATUS_ABANDONED;
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
	            // Call trigger
	            $result=$this->call_trigger('BILL_CANCEL',$user);
	            if ($result < 0)
	            {
					$this->db->rollback();
					return -1;
				}
	            // End call triggers

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
	 * @param	int		$idwarehouse	Id of warehouse to use for stock decrease if option to decreasenon stock is on (0=no decrease)
	 * @param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
     * @return	int						<0 if KO, >0 if OK
	 */
	function validate($user, $force_number='', $idwarehouse=0, $notrigger=0)
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

		if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->facture->creer))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->facture->invoice_advance->validate)))
		{
			$this->error='Permission denied';
			dol_syslog(get_class($this)."::validate ".$this->error.' MAIN_USE_ADVANCED_PERMS='.$conf->global->MAIN_USE_ADVANCED_PERMS, LOG_ERR);
			return -1;
		}

		$this->db->begin();

		$this->fetch_thirdparty();
		$this->fetch_lines();

		// Check parameters
		if ($this->type == self::TYPE_REPLACEMENT)		// si facture de remplacement
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
		else if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref)) // empty should not happened, but when it occurs, the test save life
		{
			if (! empty($conf->global->FAC_FORCE_DATE_VALIDATION))	// If option enabled, we force invoice date
			{
				$this->date=dol_now();
				$this->date_lim_reglement=$this->calculate_date_lim_reglement();
			}
			$num = $this->getNextNumRef($this->thirdparty);
		}
		else
		{
			$num = $this->ref;
		}
		$this->newref = $num;

		if ($num)
		{
			$this->update_price(1);

			// Validate
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
			$sql.= " SET facnumber='".$num."', fk_statut = ".self::STATUS_VALIDATED.", fk_user_valid = ".$user->id.", date_valid = '".$this->db->idate($now)."'";
			if (! empty($conf->global->FAC_FORCE_DATE_VALIDATION))	// If option enabled, we force invoice date
			{
				$sql.= ", datef='".$this->db->idate($this->date)."'";
				$sql.= ", date_lim_reglement='".$this->db->idate($this->date_lim_reglement)."'";
			}
			$sql.= ' WHERE rowid = '.$this->id;

			dol_syslog(get_class($this)."::validate", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
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
				$result=$this->thirdparty->set_as_client();

				// Si active on decremente le produit principal et ses composants a la validation de facture
				if ($this->type != self::TYPE_DEPOSIT && $result >= 0 && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $idwarehouse > 0)
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
							$mouvP->origin = &$this;
							// We decrease stock for product
							if ($this->type == self::TYPE_CREDIT_NOTE) $result=$mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, 0, $langs->trans("InvoiceValidatedInDolibarr",$num));
							else $result=$mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("InvoiceValidatedInDolibarr",$num));
							if ($result < 0) {
								$error++;
							}
						}
					}
				}
			}

			// Trigger calls
			if (! $error && ! $notrigger)
			{
	            // Call trigger
	            $result=$this->call_trigger('BILL_VALIDATE',$user);
	            if ($result < 0) $error++;
	            // End call triggers
			}

			if (! $error)
			{
				$this->oldref = $this->ref;

				// Rename directory if dir was a temporary ref
				if (preg_match('/^[\(]?PROV/i', $this->ref))
				{
					// Rename of object directory ($this->ref = old ref, $num = new ref)
					// to  not lose the linked files
					$oldref = dol_sanitizeFileName($this->ref);
					$newref = dol_sanitizeFileName($num);
					$dirsource = $conf->facture->dir_output.'/'.$oldref;
					$dirdest = $conf->facture->dir_output.'/'.$newref;
					if (file_exists($dirsource))
					{
						dol_syslog(get_class($this)."::validate rename dir ".$dirsource." into ".$dirdest);

						if (@rename($dirsource, $dirdest))
						{
							dol_syslog("Rename ok");
	                        // Rename docs starting with $oldref with $newref
	                        $listoffiles=dol_dir_list($conf->facture->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref,'/'));
	                        foreach($listoffiles as $fileentry)
	                        {
	                        	$dirsource=$fileentry['name'];
	                        	$dirdest=preg_replace('/^'.preg_quote($oldref,'/').'/',$newref, $dirsource);
	                        	$dirsource=$fileentry['path'].'/'.$dirsource;
	                        	$dirdest=$fileentry['path'].'/'.$dirdest;
	                        	@rename($dirsource, $dirdest);
	                        }
						}
					}
				}
			}

			if (! $error && !$this->is_last_in_cycle())
			{
				if (! $this->updatePriceNextInvoice($langs))
				{
					$error++;
				}
			}

			// Set new ref and define current statut
			if (! $error)
			{
				$this->ref = $num;
				$this->facnumber=$num;
				$this->statut= self::STATUS_VALIDATED;
				$this->brouillon=0;
				$this->date_validation=$now;
				$i = 0;

                if (!empty($conf->global->INVOICE_USE_SITUATION))
                {
    				$final = True;
    				$nboflines = count($this->lines);
    				while (($i < $nboflines) && $final) {
    					$final = ($this->lines[$i]->situation_percent == 100);
    					$i++;
    				}
    				if ($final) {
    					$this->setFinal($user);
    				}
                }
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
			return -1;
		}
	}

	/**
	 * Update price of next invoice
	 *
	 * @param	Translate	$langs	Translate object
	 * @return bool		false if KO, true if OK
	 */
	function updatePriceNextInvoice(&$langs)
	{
		foreach ($this->tab_next_situation_invoice as $next_invoice)
		{
			$is_last = $next_invoice->is_last_in_cycle();

			if ($next_invoice->brouillon && $is_last != 1)
			{
				$this->error = $langs->trans('updatePriceNextInvoiceErrorUpdateline', $next_invoice->ref);
				return false;
			}

			$next_invoice->brouillon = 1;
			foreach ($next_invoice->lines as $line)
			{
				$result = $next_invoice->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent,
														$line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type,
														$line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent,
														$line->fk_unit);

				if ($result < 0)
				{
					$this->error = $langs->trans('updatePriceNextInvoiceErrorUpdateline', $next_invoice->ref);
					return false;
				}
			}

			break; // Only the next invoice and not each next invoice
		}

		return true;
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

		if ($this->statut == self::STATUS_DRAFT)
		{
			dol_syslog(get_class($this)."::set_draft already draft status", LOG_WARNING);
			return 0;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."facture";
		$sql.= " SET fk_statut = ".self::STATUS_DRAFT;
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::set_draft", LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{
			// Si on decremente le produit principal et ses composants a la validation de facture, on réincrement
			if ($this->type != self::TYPE_DEPOSIT && $result >= 0 && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_BILL))
			{
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$langs->load("agenda");

				$num=count($this->lines);
				for ($i = 0; $i < $num; $i++)
				{
					if ($this->lines[$i]->fk_product > 0)
					{
						$mouvP = new MouvementStock($this->db);
						$mouvP->origin = &$this;
						// We decrease stock for product
						if ($this->type == self::TYPE_CREDIT_NOTE) $result=$mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("InvoiceBackToDraftInDolibarr",$this->ref));
						else $result=$mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, 0, $langs->trans("InvoiceBackToDraftInDolibarr",$this->ref));	// we use 0 for price, to not change the weighted average value
					}
				}
			}

			if ($error == 0)
			{
				$old_statut=$this->statut;
				$this->brouillon = 1;
				$this->statut = self::STATUS_DRAFT;
	            // Call trigger
	            $result=$this->call_trigger('BILL_UNVALIDATE',$user);
	            if ($result < 0)
				{
					$error++;
					$this->statut=$old_statut;
					$this->brouillon=0;
				}
	            // End call triggers
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
	 * 		@param    	string		$desc            	Description of line
	 * 		@param    	double		$pu_ht              Unit price without tax (> 0 even for credit note)
	 * 		@param    	double		$qty             	Quantity
	 * 		@param    	double		$txtva           	Force Vat rate, -1 for auto (Can contain the vat_src_code too with syntax '9.9 (CODE)')
	 * 		@param		double		$txlocaltax1		Local tax 1 rate (deprecated)
	 *  	@param		double		$txlocaltax2		Local tax 2 rate (deprecated)
	 *		@param    	int			$fk_product      	Id of predefined product/service
	 * 		@param    	double		$remise_percent  	Percent of discount on line
	 * 		@param    	int	$date_start      	Date start of service
	 * 		@param    	int	$date_end        	Date end of service
	 * 		@param    	int			$ventil          	Code of dispatching into accountancy
	 * 		@param    	int			$info_bits			Bits de type de lignes
	 *		@param    	int			$fk_remise_except	Id discount used
	 *		@param		string		$price_base_type	'HT' or 'TTC'
	 * 		@param    	double		$pu_ttc             Unit price with tax (> 0 even for credit note)
	 * 		@param		int			$type				Type of line (0=product, 1=service). Not used if fk_product is defined, the type of product is used.
	 *      @param      int			$rang               Position of line
	 *      @param		int			$special_code		Special code (also used by externals modules!)
	 *      @param		string		$origin				'order', ...
	 *      @param		int			$origin_id			Id of origin object
	 *      @param		int			$fk_parent_line		Id of parent line
	 * 		@param		int			$fk_fournprice		Supplier price id (to calculate margin) or ''
	 * 		@param		int			$pa_ht				Buying price of line (to calculate margin) or ''
	 * 		@param		string		$label				Label of the line (deprecated, do not use)
	 *		@param		array		$array_options		extrafields array
	 *      @param      int         $situation_percent  Situation advance percentage
	 *      @param      int         $fk_prev_id         Previous situation line id reference
	 * 		@param 		string		$fk_unit 			Code of the unit to use. Null to use the default one
	 * 		@param		double		$pu_ht_devise		Unit price in currency
	 *    	@return    	int             				<0 if KO, Id of line if OK
	 */
	function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits=0, $fk_remise_except='', $price_base_type='HT', $pu_ttc=0, $type=self::TYPE_STANDARD, $rang=-1, $special_code=0, $origin='', $origin_id=0, $fk_parent_line=0, $fk_fournprice=null, $pa_ht=0, $label='', $array_options=0, $situation_percent=100, $fk_prev_id='', $fk_unit = null, $pu_ht_devise = 0)
	{
		// Deprecation warning
		if ($label) {
			dol_syslog(__METHOD__ . ": using line label is deprecated", LOG_WARNING);
		}

		global $mysoc, $conf, $langs;

		dol_syslog(get_class($this)."::addline facid=$this->id,desc=$desc,pu_ht=$pu_ht,qty=$qty,txtva=$txtva, txlocaltax1=$txlocaltax1, txlocaltax2=$txlocaltax2, fk_product=$fk_product,remise_percent=$remise_percent,date_start=$date_start,date_end=$date_end,ventil=$ventil,info_bits=$info_bits,fk_remise_except=$fk_remise_except,price_base_type=$price_base_type,pu_ttc=$pu_ttc,type=$type, fk_unit=$fk_unit", LOG_DEBUG);
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
		if (empty($fk_prev_id)) $fk_prev_id = 'null';
		if (! isset($situation_percent) || $situation_percent > 100 || (string) $situation_percent == '') $situation_percent = 100;

		$localtaxes_type=getLocalTaxesFromRate($txtva, 0, $this->thirdparty, $mysoc);
			
		// Clean vat code
		$vat_src_code='';
		if (preg_match('/\((.*)\)/', $txtva, $reg))
		{
		    $vat_src_code = $reg[1];
		    $txtva = preg_replace('/\s*\(.*\)/', '', $txtva);    // Remove code into vatrate.
		}
		
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

			$product_type=$type;
			if (!empty($fk_product))
			{
				$product=new Product($this->db);
				$result=$product->fetch($fk_product);
				$product_type=$product->type;

				if (! empty($conf->global->STOCK_MUST_BE_ENOUGH_FOR_INVOICE) && $product_type == 0 && $product->stock_reel < $qty) {
                    $langs->load("errors");
				    $this->error=$langs->trans('ErrorStockIsNotEnoughToAddProductOnInvoice', $product->ref);
					$this->db->rollback();
					return -3;
				}
			}

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $product_type, $mysoc, $localtaxes_type, $situation_percent, $this->multicurrency_tx, $pu_ht_devise);

			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1 = $tabprice[9];
			$total_localtax2 = $tabprice[10];
			$pu_ht = $tabprice[3];

			// MultiCurrency
			$multicurrency_total_ht  = $tabprice[16];
            $multicurrency_total_tva = $tabprice[17];
            $multicurrency_total_ttc = $tabprice[18];
			$pu_ht_devise = $tabprice[19];

			// Rank to use
			$rangtouse = $rang;
			if ($rangtouse == -1)
			{
				$rangmax = $this->line_max($fk_parent_line);
				$rangtouse = $rangmax + 1;
			}

			// Insert line
			$this->line=new FactureLigne($this->db);

			$this->line->context = $this->context;

			$this->line->fk_facture=$this->id;
			$this->line->label=$label;	// deprecated
			$this->line->desc=$desc;

			$this->line->qty=            ($this->type==self::TYPE_CREDIT_NOTE?abs($qty):$qty);	    // For credit note, quantity is always positive and unit price negative
			$this->line->subprice=       ($this->type==self::TYPE_CREDIT_NOTE?-abs($pu_ht):$pu_ht); // For credit note, unit price always negative, always positive otherwise

			$this->line->vat_src_code=$vat_src_code;
			$this->line->tva_tx=$txtva;
			$this->line->localtax1_tx=$txlocaltax1;
			$this->line->localtax2_tx=$txlocaltax2;
			$this->line->localtax1_type = $localtaxes_type[0];
			$this->line->localtax2_type = $localtaxes_type[2];

			$this->line->total_ht=       (($this->type==self::TYPE_CREDIT_NOTE||$qty<0)?-abs($total_ht):$total_ht);    // For credit note and if qty is negative, total is negative
			$this->line->total_ttc=      (($this->type==self::TYPE_CREDIT_NOTE||$qty<0)?-abs($total_ttc):$total_ttc);  // For credit note and if qty is negative, total is negative
			$this->line->total_tva=      (($this->type==self::TYPE_CREDIT_NOTE||$qty<0)?-abs($total_tva):$total_tva);  // For credit note and if qty is negative, total is negative
			$this->line->total_localtax1=(($this->type==self::TYPE_CREDIT_NOTE||$qty<0)?-abs($total_localtax1):$total_localtax1);  // For credit note and if qty is negative, total is negative
			$this->line->total_localtax2=(($this->type==self::TYPE_CREDIT_NOTE||$qty<0)?-abs($total_localtax2):$total_localtax2);  // For credit note and if qty is negative, total is negative

			$this->line->fk_product=$fk_product;
			$this->line->product_type=$product_type;
			$this->line->remise_percent=$remise_percent;
			$this->line->date_start=$date_start;
			$this->line->date_end=$date_end;
			$this->line->ventil=$ventil;
			$this->line->rang=$rangtouse;
			$this->line->info_bits=$info_bits;
			$this->line->fk_remise_except=$fk_remise_except;

			$this->line->special_code=$special_code;
			$this->line->fk_parent_line=$fk_parent_line;
			$this->line->origin=$origin;
			$this->line->origin_id=$origin_id;
			$this->line->situation_percent = $situation_percent;
			$this->line->fk_prev_id = $fk_prev_id;
			$this->line->fk_unit=$fk_unit;

			// infos marge
			$this->line->fk_fournprice = $fk_fournprice;
			$this->line->pa_ht = $pa_ht;

			// Multicurrency
			$this->line->fk_multicurrency			= $this->fk_multicurrency;
			$this->line->multicurrency_code			= $this->multicurrency_code;
			$this->line->multicurrency_subprice		= $pu_ht_devise;
			$this->line->multicurrency_total_ht 	= $multicurrency_total_ht;
            $this->line->multicurrency_total_tva 	= $multicurrency_total_tva;
            $this->line->multicurrency_total_ttc 	= $multicurrency_total_ttc;

			if (is_array($array_options) && count($array_options)>0) {
				$this->line->array_options=$array_options;
			}

			$result=$this->line->insert();
			if ($result > 0)
			{
				// Reorder if child line
				if (! empty($fk_parent_line)) $this->line_order(true,'DESC');

				// Mise a jour informations denormalisees au niveau de la facture meme
				$result=$this->update_price(1,'auto',0,$mysoc);	// The addline method is designed to add line from user input so total calculation with update_price must be done using 'auto' mode.
				if ($result > 0)
				{
					$this->db->commit();
					return $this->line->rowid;
				}
				else
				{
					$this->error=$this->db->error();
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
	 *  @param     	int		$date_start      	Date de debut de validite du service
	 *  @param     	int		$date_end        	Date de fin de validite du service
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
	 * 	@param		string		$label				Label of the line (deprecated, do not use)
	 * 	@param		int			$special_code		Special code (also used by externals modules!)
     *  @param		array		$array_options		extrafields array
	 * 	@param      int         $situation_percent  Situation advance percentage
	 * 	@param 		string		$fk_unit 			Code of the unit to use. Null to use the default one
	 * 	@param		double		$pu_ht_devise		Unit price in currency
	 *  @return    	int             				< 0 if KO, > 0 if OK
	 */
	function updateline($rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva, $txlocaltax1=0, $txlocaltax2=0, $price_base_type='HT', $info_bits=0, $type= self::TYPE_STANDARD, $fk_parent_line=0, $skip_update_total=0, $fk_fournprice=null, $pa_ht=0, $label='', $special_code=0, $array_options=0, $situation_percent=0, $fk_unit = null, $pu_ht_devise = 0)
	{
		global $conf,$user;
		// Deprecation warning
		if ($label) {
			dol_syslog(__METHOD__ . ": using line label is deprecated", LOG_WARNING);
		}

		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		global $mysoc,$langs;

		dol_syslog(get_class($this)."::updateline rowid=$rowid, desc=$desc, pu=$pu, qty=$qty, remise_percent=$remise_percent, date_start=$date_start, date_end=$date_end, txtva=$txtva, txlocaltax1=$txlocaltax1, txlocaltax2=$txlocaltax2, price_base_type=$price_base_type, info_bits=$info_bits, type=$type, fk_parent_line=$fk_parent_line pa_ht=$pa_ht, special_code=$special_code fk_unit=$fk_unit", LOG_DEBUG);

		if ($this->brouillon)
		{
			if (!$this->is_last_in_cycle() && empty($this->error))
			{
				if (!$this->checkProgressLine($rowid, $situation_percent))
				{
					if (!$this->error) $this->error=$langs->trans('invoiceLineProgressError');
					return -3;
				}
			}

			$this->db->begin();

			// Clean parameters
			if (empty($qty)) $qty=0;
			if (empty($fk_parent_line) || $fk_parent_line < 0) $fk_parent_line=0;
			if (empty($special_code) || $special_code == 3) $special_code=0;
			if (! isset($situation_percent) || $situation_percent > 100 || (string) $situation_percent == '') $situation_percent = 100;

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

			$localtaxes_type=getLocalTaxesFromRate($txtva,0,$this->thirdparty, $mysoc);

			// Clean vat code
    		$vat_src_code='';
    		if (preg_match('/\((.*)\)/', $txtva, $reg))
    		{
    		    $vat_src_code = $reg[1];
    		    $txtva = preg_replace('/\s*\(.*\)/', '', $txtva);    // Remove code into vatrate.
    		}

			$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $mysoc, $localtaxes_type, $situation_percent, $this->multicurrency_tx, $pu_ht_devise);

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

			// Old properties: $price, $remise (deprecated)
			$price = $pu;
			$remise = 0;
			if ($remise_percent > 0)
			{
				$remise = round(($pu * $remise_percent / 100),2);
				$price = ($pu - $remise);
			}
			$price    = price2num($price);

			//Fetch current line from the database and then clone the object and set it in $oldline property
			$line = new FactureLigne($this->db);
			$line->fetch($rowid);

			if (!empty($line->fk_product))
			{
				$product=new Product($this->db);
				$result=$product->fetch($line->fk_product);
				$product_type=$product->type;

				if (! empty($conf->global->STOCK_MUST_BE_ENOUGH_FOR_INVOICE) && $product_type == 0 && $product->stock_reel < $qty) {
                    $langs->load("errors");
				    $this->error=$langs->trans('ErrorStockIsNotEnoughToAddProductOnInvoice', $product->ref);
					$this->db->rollback();
					return -3;
				}
			}

			$staticline = clone $line;

			$line->oldline = $staticline;
			$this->line = $line;
            $this->line->context = $this->context;

			// Reorder if fk_parent_line change
			if (! empty($fk_parent_line) && ! empty($staticline->fk_parent_line) && $fk_parent_line != $staticline->fk_parent_line)
			{
				$rangmax = $this->line_max($fk_parent_line);
				$this->line->rang = $rangmax + 1;
			}

			$this->line->rowid				= $rowid;
			$this->line->label				= $label;
			$this->line->desc				= $desc;
			$this->line->qty				= ($this->type==self::TYPE_CREDIT_NOTE?abs($qty):$qty);	// For credit note, quantity is always positive and unit price negative
            
			$this->line->vat_src_code       = $vat_src_code;
			$this->line->tva_tx				= $txtva;
			$this->line->localtax1_tx		= $txlocaltax1;
			$this->line->localtax2_tx		= $txlocaltax2;
			$this->line->localtax1_type		= $localtaxes_type[0];
			$this->line->localtax2_type		= $localtaxes_type[2];
			
			$this->line->remise_percent		= $remise_percent;
			$this->line->subprice			= ($this->type==2?-abs($pu_ht):$pu_ht); // For credit note, unit price always negative, always positive otherwise
			$this->line->date_start			= $date_start;
			$this->line->date_end			= $date_end;
			$this->line->total_ht			= (($this->type==self::TYPE_CREDIT_NOTE||$qty<0)?-abs($total_ht):$total_ht);  // For credit note and if qty is negative, total is negative
			$this->line->total_tva			= (($this->type==self::TYPE_CREDIT_NOTE||$qty<0)?-abs($total_tva):$total_tva);
			$this->line->total_localtax1	= $total_localtax1;
			$this->line->total_localtax2	= $total_localtax2;
			$this->line->total_ttc			= (($this->type==self::TYPE_CREDIT_NOTE||$qty<0)?-abs($total_ttc):$total_ttc);
			$this->line->info_bits			= $info_bits;
			$this->line->special_code		= $special_code;
			$this->line->product_type		= $type;
			$this->line->fk_parent_line		= $fk_parent_line;
			$this->line->skip_update_total	= $skip_update_total;
			$this->line->situation_percent  = $situation_percent;
			$this->line->fk_unit				= $fk_unit;

			$this->line->fk_fournprice = $fk_fournprice;
			$this->line->pa_ht = $pa_ht;

			// Multicurrency
			$this->line->multicurrency_subprice		= $pu_ht_devise;
			$this->line->multicurrency_total_ht 	= $multicurrency_total_ht;
            $this->line->multicurrency_total_tva 	= $multicurrency_total_tva;
            $this->line->multicurrency_total_ttc 	= $multicurrency_total_ttc;

			if (is_array($array_options) && count($array_options)>0) {
				$this->line->array_options=$array_options;
			}

			$result=$this->line->update($user);
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
			    $this->error=$this->line->error;
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
	 * Check if the percent edited is lower of next invoice line
	 *
	 * @param	int		$idline				id of line to check
	 * @param	float	$situation_percent	progress percentage need to be test
	 * @return false if KO, true if OK
	 */
	function checkProgressLine($idline, $situation_percent)
	{
		$sql = 'SELECT fd.situation_percent FROM '.MAIN_DB_PREFIX.'facturedet fd
				INNER JOIN '.MAIN_DB_PREFIX.'facture f ON (fd.fk_facture = f.rowid)
				WHERE fd.fk_prev_id = '.$idline.'
				AND f.fk_statut <> 0';

		$result = $this->db->query($sql);
		if (! $result)
		{
			$this->error=$this->db->error();
			return false;
		}

		$obj = $this->db->fetch_object($result);

		if ($obj === null) return true;
		else return $situation_percent < $obj->situation_percent;
	}

	/**
	 * Update invoice line with percentage
	 *
	 * @param  FactureLigne $line       Invoice line
	 * @param  int          $percent    Percentage
	 * @return void
	 */
	function update_percent($line, $percent)
	{
	    global $mysoc,$user;

		include_once(DOL_DOCUMENT_ROOT . '/core/lib/price.lib.php');

		// Cap percentages to 100
		if ($percent > 100) $percent = 100;
		$line->situation_percent = $percent;
		$tabprice = calcul_price_total($line->qty, $line->subprice, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->product_type, 'HT', 0, 0, $mysoc, '', $percent);
		$line->total_ht = $tabprice[0];
		$line->total_tva = $tabprice[1];
		$line->total_ttc = $tabprice[2];
		$line->total_localtax1 = $tabprice[9];
		$line->total_localtax2 = $tabprice[10];
		$line->update($user);
		$this->update_price(1);
		$this->db->commit();
	}

	/**
	 *	Delete line in database
	 *
	 *	@param		int		$rowid		Id of line to delete
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function deleteline($rowid)
	{
        global $user;
        
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

		dol_syslog(get_class($this)."::deleteline", LOG_DEBUG);
		$result = $this->db->query($sql);
		if (! $result)
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}

		$line=new FactureLigne($this->db);
		
        $line->context = $this->context;

		// For triggers
		$result = $line->fetch($rowid);
		if (! ($result > 0)) dol_print_error($db, $line->error, $line->errors);
		
		if ($line->delete($user) > 0)
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
			$this->error=$line->error;
			return -1;
		}
	}

	/**
	 *	Set percent discount
	 *
	 *	@param     	User	$user		User that set discount
	 *	@param     	double	$remise		Discount
	 *  @param     	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return		int 		<0 if ko, >0 if ok
	 */
	function set_remise($user, $remise, $notrigger=0)
	{
		// Clean parameters
		if (empty($remise)) $remise=0;

		if ($user->rights->facture->creer)
		{
			$remise=price2num($remise);

			$error=0;

			$this->db->begin();

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
			$sql.= ' SET remise_percent = '.$remise;
			$sql.= ' WHERE rowid = '.$this->id;
			$sql.= ' AND fk_statut = '.self::STATUS_DRAFT;

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (!$resql)
			{
				$this->errors[]=$this->db->error();
				$error++;
			}

			if (! $notrigger && empty($error))
			{
				// Call trigger
				$result=$this->call_trigger('BILL_MODIFY',$user);
				if ($result < 0) $error++;
				// End call triggers
			}

			if (! $error)
			{
				$this->remise_percent = $remise;
				$this->update_price(1);

				$this->db->commit();
				return 1;
			}
			else
			{
				foreach($this->errors as $errmsg)
				{
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error.=($this->error?', '.$errmsg:$errmsg);
				}
				$this->db->rollback();
				return -1*$error;
			}
		}
	}


	/**
	 *	Set absolute discount
	 *
	 *	@param     	User	$user 		User that set discount
	 *	@param     	double	$remise		Discount
	 *  @param     	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return		int 				<0 if KO, >0 if OK
	 */
	function set_remise_absolue($user, $remise, $notrigger=0)
	{
		if (empty($remise)) $remise=0;

		if ($user->rights->facture->creer)
		{
			$error=0;

			$this->db->begin();

			$remise=price2num($remise);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
			$sql.= ' SET remise_absolue = '.$remise;
			$sql.= ' WHERE rowid = '.$this->id;
			$sql.= ' AND fk_statut = '.self::STATUS_DRAFT;

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (!$resql)
			{
				$this->errors[]=$this->db->error();
				$error++;
			}

			if (! $error)
			{
				$this->oldcopy= clone $this;
				$this->remise_absolue = $remise;
				$this->update_price(1);
			}

			if (! $notrigger && empty($error))
			{
				// Call trigger
				$result=$this->call_trigger('BILL_MODIFY',$user);
				if ($result < 0) $error++;
				// End call triggers
			}

			if (! $error)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				foreach($this->errors as $errmsg)
				{
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error.=($this->error?', '.$errmsg:$errmsg);
				}
				$this->db->rollback();
				return -1*$error;
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

		$sql = 'SELECT pf.amount, pf.multicurrency_amount, p.fk_paiement, p.datep, p.num_paiement as num, t.code';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$table.' as pf, '.MAIN_DB_PREFIX.$table2.' as p, '.MAIN_DB_PREFIX.'c_paiement as t';
		$sql.= ' WHERE pf.'.$field.' = '.$this->id;
		//$sql.= ' WHERE pf.'.$field.' = 1';
		$sql.= ' AND pf.'.$field2.' = p.rowid';
		$sql.= ' AND p.fk_paiement = t.id';
		if ($filtertype) $sql.=" AND t.code='PRE'";

		dol_syslog(get_class($this)."::getListOfPayments", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$retarray[]=array('amount'=>$obj->amount,'type'=>$obj->code, 'date'=>$obj->datep, 'num'=>$obj->num);
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
	 * 		@param 		int 	$multicurrency 	Return multicurrency_amount instead of amount
	 *		@return		int						<0 if KO, Sum of credit notes and deposits amount otherwise
	 */
	function getSumCreditNotesUsed($multicurrency=0)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		$discountstatic=new DiscountAbsolute($this->db);
		$result=$discountstatic->getSumCreditNotesUsed($this, $multicurrency);
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
	 * 		@param 		int 	$multicurrency 	Return multicurrency_amount instead of amount
	 *		@return		int						<0 if KO, Sum of deposits amount otherwise
	 */
	function getSumDepositsUsed($multicurrency=0)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		$discountstatic=new DiscountAbsolute($this->db);
		$result=$discountstatic->getSumDepositsUsed($this, $multicurrency);
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
	 *      @param	   Societe		$soc		object company
	 *      @param     string		$mode		'next' for next value or 'last' for last value
	 *      @return    string					free ref or last ref
	 */
	function getNextNumRef($soc,$mode='next')
	{
		global $conf, $langs;
		$langs->load("bills");

		// Clean parameters (if not defined or using deprecated value)
		if (empty($conf->global->FACTURE_ADDON)) $conf->global->FACTURE_ADDON='mod_facture_terre';
		else if ($conf->global->FACTURE_ADDON=='terre') $conf->global->FACTURE_ADDON='mod_facture_terre';
		else if ($conf->global->FACTURE_ADDON=='mercure') $conf->global->FACTURE_ADDON='mod_facture_mercure';

		if (! empty($conf->global->FACTURE_ADDON))
		{
			$mybool=false;

			$file = $conf->global->FACTURE_ADDON.".php";
			$classname = $conf->global->FACTURE_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

			foreach ($dirmodels as $reldir) {

				$dir = dol_buildpath($reldir."core/modules/facture/");

				// Load file with numbering class (if found)
				if (is_file($dir.$file) && is_readable($dir.$file))
				{
                    $mybool |= include_once $dir . $file;
                }
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
					if (is_file($dir.$file) && is_readable($dir.$file)) {
                        $mybool |= include_once $dir . $file;
                    }
				}
			}

			if (! $mybool)
			{
				dol_print_error('',"Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = "";
			$numref = $obj->getNextValue($soc,$this,$mode);

			/**
			 * $numref can be empty in case we ask for the last value because if there is no invoice created with the
			 * set up mask.
			 */
			if ($mode != 'last' && !$numref) {
				$this->error=$obj->error;
				//dol_print_error($this->db,"Facture::getNextNumRef ".$obj->error);
				return "";
			}

			return $numref;
		}
		else
		{
			$langs->load("errors");
			print $langs->trans("Error")." ".$langs->trans("ErrorModuleSetupNotComplete");
			return "";
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
			if (! empty($this->lines[$i]->export_compta) && ! empty($this->lines[$i]->code_ventilation))
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
			$maxfacnumber = $this->getNextNumRef($this->thirdparty,'last');
			$ventilExportCompta = $this->getVentilExportCompta();
			// If there is no invoice into the reset range and not already dispatched, we can delete
			if ($maxfacnumber == '' && $ventilExportCompta == 0) return 1;
			// If invoice to delete is last one and not already dispatched, we can delete
			if ($maxfacnumber == $this->ref && $ventilExportCompta == 0) return 1;
			if ($this->situation_cycle_ref) {
				$last = $this->is_last_in_cycle();
				return $last;
			}
		}
		else if ($this->statut == self::STATUS_DRAFT && $facref == 'PROV') // Si facture brouillon et provisoire
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
		if ($draft) $sql.= " AND f.fk_statut = ".self::STATUS_DRAFT;
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
		$sql.= " WHERE (f.fk_statut = ".self::STATUS_VALIDATED." OR (f.fk_statut = ".self::STATUS_ABANDONED." AND f.close_code = '".self::CLOSECODE_ABANDONED."'))";
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND f.paye = 0";					// Pas classee payee completement
		$sql.= " AND pf.fk_paiement IS NULL";		// Aucun paiement deja fait
		$sql.= " AND ff.fk_statut IS NULL";			// Renvoi vrai si pas facture de remplacement
		if ($socid > 0) $sql.=" AND f.fk_soc = ".$socid;
		$sql.= " ORDER BY f.facnumber";

		dol_syslog(get_class($this)."::list_replacable_invoices", LOG_DEBUG);
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
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as ff ON (f.rowid = ff.fk_facture_source AND ff.type=".self::TYPE_REPLACEMENT.")";
		$sql.= " WHERE f.entity = ".$conf->entity;
		$sql.= " AND f.fk_statut in (".self::STATUS_VALIDATED.",".self::STATUS_CLOSED.")";
		//  $sql.= " WHERE f.fk_statut >= 1";
		//	$sql.= " AND (f.paye = 1";				// Classee payee completement
		//	$sql.= " OR f.close_code IS NOT NULL)";	// Classee payee partiellement
		$sql.= " AND ff.type IS NULL";			// Renvoi vrai si pas facture de remplacement
		$sql.= " AND f.type != ".self::TYPE_CREDIT_NOTE;				// Type non 2 si facture non avoir
		if ($socid > 0) $sql.=" AND f.fk_soc = ".$socid;
		$sql.= " ORDER BY f.facnumber";

		dol_syslog(get_class($this)."::list_qualified_avoir_invoices", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$qualified=0;
				if ($obj->fk_statut == self::STATUS_VALIDATED) $qualified=1;
				if ($obj->fk_statut == self::STATUS_CLOSED) $qualified=1;
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
			return -1;
		}
	}


	/**
	 *	Create a withdrawal request for a standing order.
	 *  Use the remain to pay excluding all existing open direct debit requests.
	 *
	 *	@param      User	$fuser      User asking the direct debit transfer
	 *  @param		float	$amount		Amount we request direct debit for
	 *	@return     int         		<0 if KO, >0 if OK
	 */
	function demande_prelevement($fuser, $amount=0)
	{

		$error=0;

		dol_syslog(get_class($this)."::demande_prelevement", LOG_DEBUG);

		if ($this->statut > self::STATUS_DRAFT && $this->paye == 0)
		{
	        require_once DOL_DOCUMENT_ROOT . '/societe/class/companybankaccount.class.php';
	        $bac = new CompanyBankAccount($this->db);
	        $bac->fetch(0,$this->socid);

        	$sql = 'SELECT count(*)';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'prelevement_facture_demande';
			$sql.= ' WHERE fk_facture = '.$this->id;
			$sql.= ' AND traite = 0';

			dol_syslog(get_class($this)."::demande_prelevement", LOG_DEBUG);
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
					if (empty($amount)) $amount = price2num($this->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits,'MT');

					if (is_numeric($amount) && $amount != 0)
					{
						$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'prelevement_facture_demande';
						$sql .= ' (fk_facture, amount, date_demande, fk_user_demande, code_banque, code_guichet, number, cle_rib)';
						$sql .= ' VALUES ('.$this->id;
						$sql .= ",'".price2num($amount)."'";
						$sql .= ",'".$this->db->idate($now)."'";
						$sql .= ",".$fuser->id;
						$sql .= ",'".$bac->code_banque."'";
						$sql .= ",'".$bac->code_guichet."'";
						$sql .= ",'".$bac->number."'";
						$sql .= ",'".$bac->cle_rib."')";

						dol_syslog(get_class($this)."::demande_prelevement", LOG_DEBUG);
						$resql=$this->db->query($sql);
						if (! $resql)
						{
						    $this->error=$this->db->lasterror();
						    dol_syslog(get_class($this).'::demandeprelevement Erreur');
						    $error++;
						}
					}
					else
					{
						$this->error='WithdrawRequestErrorNilAmount';
	                    dol_syslog(get_class($this).'::demandeprelevement WithdrawRequestErrorNilAmount');
	                    $error++;
					}

        			if (! $error)
        			{
        				// Force payment mode of invoice to withdraw
        				$payment_mode_id = dol_getIdFromCode($this->db, 'PRE', 'c_paiement');
        				if ($payment_mode_id > 0)
        				{
        					$result=$this->setPaymentMethods($payment_mode_id);
        				}
        			}

                    if ($error) return -1;
                    return 1;
                }
                else
                {
                    $this->error="A request already exists";
                    dol_syslog(get_class($this).'::demandeprelevement Impossible de creer une demande, demande deja en cours');
                    return 0;
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
	 *  @param  User	$fuser      User making delete
	 *  @param  int		$did        id de la demande a supprimer
	 *  @return	int					<0 if OK, >0 if KO
	 */
	function demande_prelevement_delete($fuser, $did)
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
	 *	@param  User		$user    	Object user
	 *	@return WorkboardResponse|int 	<0 if KO, WorkboardResponse if OK
	 */
	function load_board($user)
	{
		global $conf, $langs;

		$clause = " WHERE";

		$sql = "SELECT f.rowid, f.date_lim_reglement as datefin,f.fk_statut";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
		if (!$user->rights->societe->client->voir && !$user->societe_id)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON f.fk_soc = sc.fk_soc";
			$sql.= " WHERE sc.fk_user = " .$user->id;
			$clause = " AND";
		}
		$sql.= $clause." f.paye=0";
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND f.fk_statut = ".self::STATUS_VALIDATED;
		if ($user->societe_id) $sql.= " AND f.fk_soc = ".$user->societe_id;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$langs->load("bills");
			$now=dol_now();

			$response = new WorkboardResponse();
			$response->warning_delay=$conf->facture->client->warning_delay/60/60/24;
			$response->label=$langs->trans("CustomerBillsUnpaid");
			$response->url=DOL_URL_ROOT.'/compta/facture/list.php?search_status=1&mainmenu=accountancy&leftmenu=customers_bills';
			$response->img=img_object($langs->trans("Bills"),"bill");

			$generic_facture = new Facture($this->db);

			while ($obj=$this->db->fetch_object($resql))
			{
				$generic_facture->date_lim_reglement = $this->db->jdate($obj->datefin);
				$generic_facture->statut = $obj->fk_statut;

				$response->nbtodo++;

				if ($generic_facture->hasDelay()) {
					$response->nbtodolate++;
				}
			}

			return $response;
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
		global $langs;

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
		//Avoid php warning Warning: mt_rand(): max(0) is smaller than min(1) when no product exists
		if (empty($num_prods)) {
			$num_prods=1;
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
	    return $this->fetch_lines();
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *	@param	string		$modele			Generator to use. Caller must set it to obj->modelpdf or GETPOST('modelpdf') for example.
	 *	@param	Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param  int			$hidedetails    Hide details of lines
	 *  @param  int			$hidedesc       Hide description
	 *  @param  int			$hideref        Hide ref
	 *	@return int        					<0 if KO, >0 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
	{
		global $conf,$langs;

		$langs->load("bills");

		// Positionne le modele sur le nom du modele a utiliser
		if (! dol_strlen($modele))
		{
			if (! empty($conf->global->FACTURE_ADDON_PDF))
			{
				$modele = $conf->global->FACTURE_ADDON_PDF;
			}
			else
			{
				$modele = 'crabe';
			}
		}

		$modelpath = "core/modules/facture/doc/";

		$result=$this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);

		return $result;
	}

	/**
	 * Gets the smallest reference available for a new cycle
	 *
	 * @return int >= 1 if OK, -1 if error
	 */
	function newCycle()
	{
		$sql = 'SELECT max(situation_cycle_ref) FROM ' . MAIN_DB_PREFIX . 'facture as f';
		$sql.= " WHERE f.entity in (".getEntity('facture').")";
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($resql->num_rows > 0)
			{
				$res = $this->db->fetch_array($resql);
				$ref = $res['max(situation_cycle_ref)'];
				$ref++;
			} else {
				$ref = 1;
			}
			$this->db->free($resql);
			return $ref;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog("Error sql=" . $sql . ", error=" . $this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * Checks if the invoice is the first of a cycle
	 *
	 * @return boolean
	 */
	function is_first()
	{
		return ($this->situation_counter == 1);
	}

	/**
	 * Returns an array containing the previous situations as Facture objects
	 *
	 * @return mixed -1 if error, array of previous situations
	 */
	function get_prev_sits()
	{
		global $conf;

		$sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'facture';
		$sql .= ' where situation_cycle_ref = ' . $this->situation_cycle_ref;
		$sql .= ' and situation_counter < ' . $this->situation_counter;
		$sql .= ' AND entity = '. ($this->entity > 0 ? $this->entity : $conf->entity);
		$resql = $this->db->query($sql);
		$res = array();
		if ($resql && $resql->num_rows > 0) {
			while ($row = $this->db->fetch_object($resql)) {
				$id = $row->rowid;
				$situation = new Facture($this->db);
				$situation->fetch($id);
				$res[] = $situation;
			}
		} else {
			$this->error = $this->db->error();
			dol_syslog("Error sql=" . $sql . ", error=" . $this->error, LOG_ERR);
			return -1;
		}

		return $res;
	}

	/**
	 * Sets the invoice as a final situation
	 *
	 *  @param  	User	$user    	Object user
	 *  @param     	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return		int 				<0 if KO, >0 if OK
	 */
	function setFinal(User $user, $notrigger=0)
	{
		$error=0;

		$this->db->begin();

		$this->situation_final = 1;
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'facture SET situation_final = ' . $this->situation_final . ' where rowid = ' . $this->id;

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (!$resql)
		{
			$this->errors[]=$this->db->error();
			$error++;
		}

		if (! $notrigger && empty($error))
		{
			// Call trigger
			$result=$this->call_trigger('BILL_MODIFY',$user);
			if ($result < 0) $error++;
			// End call triggers
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
	}

	/**
	 * Checks if the invoice is the last in its cycle
	 *
	 * @return bool Last of the cycle status
	 *
	 */
	function is_last_in_cycle()
	{
		global $conf;

		if (!empty($this->situation_cycle_ref)) {
			// No point in testing anything if we're not inside a cycle
			$sql = 'SELECT max(situation_counter) FROM ' . MAIN_DB_PREFIX . 'facture WHERE situation_cycle_ref = ' . $this->situation_cycle_ref . ' AND entity = ' . ($this->entity > 0 ? $this->entity : $conf->entity);
			$resql = $this->db->query($sql);

			if ($resql && $resql->num_rows > 0) {
				$res = $this->db->fetch_array($resql);
				$last = $res['max(situation_counter)'];
				return ($last == $this->situation_counter);
			} else {
				$this->error = $this->db->lasterror();
				dol_syslog(get_class($this) . "::select Error " . $this->error, LOG_ERR);
				return false;
			}
		} else {
			return true;
		}
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
			'facture'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}

	/**
	 * Is the customer invoice delayed?
	 *
	 * @return bool
	 */
	public function hasDelay()
	{
		global $conf;

		$now = dol_now();

		// Paid invoices have status STATUS_CLOSED
		if ($this->statut != Facture::STATUS_VALIDATED) return false;

		return $this->date_lim_reglement < ($now - $conf->facture->client->warning_delay);
	}
}

/**
 *	Class to manage invoice lines.
 *  Saved into database table llx_facturedet
 */
class FactureLigne extends CommonInvoiceLine
{
    public $element='facturedet';
    public $table_element='facturedet';

	var $oldline;

	//! From llx_facturedet
	//! Id facture
	var $fk_facture;
	//! Id parent line
	var $fk_parent_line;
	/**
	 * @deprecated
	 */
	var $label;
	//! Description ligne
	var $desc;

	var $localtax1_type;	// Local tax 1 type
	var $localtax2_type;	// Local tax 2 type
	var $fk_remise_except;	// Link to line into llx_remise_except
	var $rang = 0;

	var $fk_fournprice;
	var $pa_ht;
	var $marge_tx;
	var $marque_tx;

	var $special_code;	// Liste d'options non cumulabels:
	// 1: frais de port
	// 2: ecotaxe
	// 3: ??

	var $origin;
	var $origin_id;

	var $fk_code_ventilation = 0;

	var $date_start;
	var $date_end;

	// Ne plus utiliser
	//var $price;         	// P.U. HT apres remise % de ligne (exemple 80)
	//var $remise;			// Montant calcule de la remise % sur PU HT (exemple 20)

	// From llx_product
	/**
	 * @deprecated
	 * @see product_ref
	 */
	var $ref;				// Product ref (deprecated)
	var $product_ref;       // Product ref
	/**
	 * @deprecated
	 * @see product_label
	 */
	var $libelle;      		// Product label (deprecated)
	var $product_label;     // Product label
	var $product_desc;  	// Description produit

	var $skip_update_total; // Skip update price total for special lines

	/**
	 * @var int Situation advance percentage
	 */
	public $situation_percent;

	/**
	 * @var int Previous situation line id reference
	 */
	public $fk_prev_id;

	// Multicurrency
	var $fk_multicurrency;
	var $multicurrency_code;
	var $multicurrency_subprice;
	var $multicurrency_total_ht;
	var $multicurrency_total_tva;
	var $multicurrency_total_ttc;

	/**
	 *	Load invoice line from database
	 *
	 *	@param	int		$rowid      id of invoice line to get
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT fd.rowid, fd.fk_facture, fd.fk_parent_line, fd.fk_product, fd.product_type, fd.label as custom_label, fd.description, fd.price, fd.qty, fd.vat_src_code, fd.tva_tx,';
		$sql.= ' fd.localtax1_tx, fd. localtax2_tx, fd.remise, fd.remise_percent, fd.fk_remise_except, fd.subprice,';
		$sql.= ' fd.date_start as date_start, fd.date_end as date_end, fd.fk_product_fournisseur_price as fk_fournprice, fd.buy_price_ht as pa_ht,';
		$sql.= ' fd.info_bits, fd.special_code, fd.total_ht, fd.total_tva, fd.total_ttc, fd.total_localtax1, fd.total_localtax2, fd.rang,';
		$sql.= ' fd.fk_code_ventilation,';
		$sql.= ' fd.fk_unit, fd.fk_user_author, fd.fk_user_modif,';
		$sql.= ' fd.situation_percent, fd.fk_prev_id,';
		$sql.= ' fd.multicurrency_subprice,';
		$sql.= ' fd.multicurrency_total_ht,';
		$sql.= ' fd.multicurrency_total_tva,';
		$sql.= ' fd.multicurrency_total_ttc,';
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
			$this->vat_src_code  		= $objp->vat_src_code;
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
			$this->special_code			= $objp->special_code;
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
			$this->fk_unit				= $objp->fk_unit;
			$this->fk_user_modif		= $objp->fk_user_modif;
			$this->fk_user_author		= $objp->fk_user_author;
			
			$this->situation_percent    = $objp->situation_percent;
			$this->fk_prev_id           = $objp->fk_prev_id;

			$this->multicurrency_subprice = $objp->multicurrency_subprice;
			$this->multicurrency_total_ht = $objp->multicurrency_total_ht;
			$this->multicurrency_total_tva= $objp->multicurrency_total_tva;
			$this->multicurrency_total_ttc= $objp->multicurrency_total_ttc;

			$this->db->free($result);

			return 1;
		}
		else
		{
		    $this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Insert line into database
	 *
	 *	@param      int		$notrigger		                 1 no triggers
	 *  @param      int     $noerrorifdiscountalreadylinked  1=Do not make error if lines is linked to a discount and discount already linked to another
	 *	@return		int						                 <0 if KO, >0 if OK
	 */
	function insert($notrigger=0, $noerrorifdiscountalreadylinked=0)
	{
		global $langs,$user,$conf;

		$error=0;

        $pa_ht_isemptystring = (empty($this->pa_ht) && $this->pa_ht == ''); // If true, we can use a default value. If this->pa_ht = '0', we must use '0'.

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
		if (empty($this->fk_prev_id)) $this->fk_prev_id = 'null';
		if (! isset($this->situation_percent) || $this->situation_percent > 100 || (string) $this->situation_percent == '') $this->situation_percent = 100;

		if (empty($this->pa_ht)) $this->pa_ht=0;
		if (empty($this->multicurrency_subprice)) $this->multicurrency_subprice=0;
		if (empty($this->multicurrency_total_ht)) $this->multicurrency_total_ht=0;
		if (empty($this->multicurrency_total_tva)) $this->multicurrency_total_tva=0;
		if (empty($this->multicurrency_total_ttc)) $this->multicurrency_total_ttc=0;

		// if buy price not defined, define buyprice as configured in margin admin
		if ($this->pa_ht == 0 && $pa_ht_isemptystring)
		{
			if (($result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product)) < 0)
			{
				return $result;
			}
			else
			{
				$this->pa_ht = $result;
			}
		}

		// Check parameters
		if ($this->product_type < 0)
		{
			$this->error='ErrorProductTypeMustBe0orMore';
			return -1;
		}
		if (! empty($this->fk_product))
		{
			// Check product exists
			$result=Product::isExistingObject('product', $this->fk_product);
			if ($result <= 0)
			{
				$this->error='ErrorProductIdDoesNotExists';
				return -1;
			}
		}

		$this->db->begin();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facturedet';
		$sql.= ' (fk_facture, fk_parent_line, label, description, qty,';
		$sql.= ' vat_src_code, tva_tx, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type,';
		$sql.= ' fk_product, product_type, remise_percent, subprice, fk_remise_except,';
		$sql.= ' date_start, date_end, fk_code_ventilation, ';
		$sql.= ' rang, special_code, fk_product_fournisseur_price, buy_price_ht,';
		$sql.= ' info_bits, total_ht, total_tva, total_ttc, total_localtax1, total_localtax2,';
		$sql.= ' situation_percent, fk_prev_id,';
		$sql.= ' fk_unit, fk_user_author, fk_user_modif,';
		$sql.= ' fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc';
		$sql.= ')';
		$sql.= " VALUES (".$this->fk_facture.",";
		$sql.= " ".($this->fk_parent_line>0?"'".$this->fk_parent_line."'":"null").",";
		$sql.= " ".(! empty($this->label)?"'".$this->db->escape($this->label)."'":"null").",";
		$sql.= " '".$this->db->escape($this->desc)."',";
		$sql.= " ".price2num($this->qty).",";
        $sql.= " ".(empty($this->vat_src_code)?"''":"'".$this->vat_src_code."'").",";
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
		$sql.= ", " . $this->situation_percent;
		$sql.= ", " . $this->fk_prev_id;
		$sql.= ", ".(!$this->fk_unit ? 'NULL' : $this->fk_unit);
		$sql.= ", ".$user->id;
		$sql.= ", ".$user->id;
		$sql.= ", ".(int) $this->fk_multicurrency;
		$sql.= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql.= ", ".price2num($this->multicurrency_subprice);
		$sql.= ", ".price2num($this->multicurrency_total_ht);
		$sql.= ", ".price2num($this->multicurrency_total_tva);
		$sql.= ", ".price2num($this->multicurrency_total_ttc);
		$sql.= ')';

		dol_syslog(get_class($this)."::insert", LOG_DEBUG);
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
						if ($discount->fk_facture_line > 0)
						{
						    if (empty($noerrorifdiscountalreadylinked))
						    {
    							$this->error=$langs->trans("ErrorDiscountAlreadyUsed",$discount->id);
    							dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
    							$this->db->rollback();
    							return -3;
						    }
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
                // Call trigger
                $result=$this->call_trigger('LINEBILL_INSERT',$user);
                if ($result < 0)
                {
					$this->db->rollback();
					return -2;
				}
                // End call triggers
			}

			$this->db->commit();
			return $this->rowid;

		}
		else
		{
			$this->error=$this->db->error();
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
		global $user,$conf;

		$error=0;

		$pa_ht_isemptystring = (empty($this->pa_ht) && $this->pa_ht == ''); // If true, we can use a default value. If this->pa_ht = '0', we must use '0'.

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
		if (! isset($this->situation_percent) || $this->situation_percent > 100 || (string) $this->situation_percent == '') $this->situation_percent = 100;
		if (empty($this->pa_ht)) $this->pa_ht=0;

		if (empty($this->multicurrency_subprice)) $this->multicurrency_subprice=0;
		if (empty($this->multicurrency_total_ht)) $this->multicurrency_total_ht=0;
		if (empty($this->multicurrency_total_tva)) $this->multicurrency_total_tva=0;
		if (empty($this->multicurrency_total_ttc)) $this->multicurrency_total_ttc=0;

		// Check parameters
		if ($this->product_type < 0) return -1;

		// if buy price not defined, define buyprice as configured in margin admin
		if ($this->pa_ht == 0 && $pa_ht_isemptystring)
		{
			if (($result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product)) < 0)
			{
				return $result;
			}
			else
			{
				$this->pa_ht = $result;
			}
		}

		$this->db->begin();

        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."facturedet SET";
        $sql.= " description='".$this->db->escape($this->desc)."'";
        $sql.= ", label=".(! empty($this->label)?"'".$this->db->escape($this->label)."'":"null");
        $sql.= ", subprice=".price2num($this->subprice)."";
        $sql.= ", remise_percent=".price2num($this->remise_percent)."";
        if ($this->fk_remise_except) $sql.= ", fk_remise_except=".$this->fk_remise_except;
        else $sql.= ", fk_remise_except=null";
		$sql.= ", vat_src_code = '".(empty($this->vat_src_code)?'':$this->vat_src_code)."'";
        $sql.= ", tva_tx=".price2num($this->tva_tx)."";
        $sql.= ", localtax1_tx=".price2num($this->localtax1_tx)."";
        $sql.= ", localtax2_tx=".price2num($this->localtax2_tx)."";
		$sql.= ", localtax1_type='".$this->localtax1_type."'";
		$sql.= ", localtax2_type='".$this->localtax2_type."'";
        $sql.= ", qty=".price2num($this->qty)."";
        $sql.= ", date_start=".(! empty($this->date_start)?"'".$this->db->idate($this->date_start)."'":"null");
        $sql.= ", date_end=".(! empty($this->date_end)?"'".$this->db->idate($this->date_end)."'":"null");
        $sql.= ", product_type=".$this->product_type;
        $sql.= ", info_bits='".$this->info_bits."'";
        $sql.= ", special_code='".$this->special_code."'";
        if (empty($this->skip_update_total))
        {
        	$sql.= ", total_ht=".price2num($this->total_ht)."";
        	$sql.= ", total_tva=".price2num($this->total_tva)."";
        	$sql.= ", total_ttc=".price2num($this->total_ttc)."";
        	$sql.= ", total_localtax1=".price2num($this->total_localtax1)."";
        	$sql.= ", total_localtax2=".price2num($this->total_localtax2)."";
        }
		$sql.= ", fk_product_fournisseur_price=".(! empty($this->fk_fournprice)?"'".$this->db->escape($this->fk_fournprice)."'":"null");
		$sql.= ", buy_price_ht='".price2num($this->pa_ht)."'";
		$sql.= ", fk_parent_line=".($this->fk_parent_line>0?$this->fk_parent_line:"null");
		if (! empty($this->rang)) $sql.= ", rang=".$this->rang;
		$sql.= ", situation_percent=" . $this->situation_percent;
		$sql.= ", fk_unit=".(!$this->fk_unit ? 'NULL' : $this->fk_unit);
		$sql.= ", fk_user_modif =".$user->id;

		// Multicurrency
		$sql.= ", multicurrency_subprice=".price2num($this->multicurrency_subprice)."";
        $sql.= ", multicurrency_total_ht=".price2num($this->multicurrency_total_ht)."";
        $sql.= ", multicurrency_total_tva=".price2num($this->multicurrency_total_tva)."";
        $sql.= ", multicurrency_total_ttc=".price2num($this->multicurrency_total_ttc)."";

		$sql.= " WHERE rowid = ".$this->rowid;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
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
                // Call trigger
                $result=$this->call_trigger('LINEBILL_UPDATE',$user);
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

	/**
	 * 	Delete line in database
	 *  TODO Add param User $user and notrigger (see skeleton)
     *
	 *	@return	    int		           <0 if KO, >0 if OK
	 */
	function delete()
	{
		global $user;

		$this->db->begin();

		// Call trigger
		$result=$this->call_trigger('LINEBILL_DELETE',$user);
		if ($result < 0)
		{
			$this->db->rollback();
			return -1;
		}
		// End call triggers


		$sql = "DELETE FROM ".MAIN_DB_PREFIX."facturedet WHERE rowid = ".$this->rowid;
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

		dol_syslog(get_class($this)."::update_total", LOG_DEBUG);

		$resql=$this->db->query($sql);
		if ($resql)
		{
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

	/**
	 * Returns situation_percent of the previous line.
	 * Warning: If invoice is a replacement invoice, this->fk_prev_id is id of the replaced line.
	 *
	 * @param  int     $invoiceid      Invoice id
	 * @return int                     >= 0
	 */
	function get_prev_progress($invoiceid)
	{
		if (is_null($this->fk_prev_id) || empty($this->fk_prev_id) || $this->fk_prev_id == "") {
			return 0;
		} else {
		    // If invoice is a not a situation invoice, this->fk_prev_id is used for something else
            $tmpinvoice=new Facture($this->db);
            $tmpinvoice->fetch($invoiceid);
            if ($tmpinvoice->type != Facture::TYPE_SITUATION) return 0;

			$sql = 'SELECT situation_percent FROM ' . MAIN_DB_PREFIX . 'facturedet WHERE rowid=' . $this->fk_prev_id;
			$resql = $this->db->query($sql);
			if ($resql && $resql->num_rows > 0) {
				$res = $this->db->fetch_array($resql);
				return $res['situation_percent'];
			} else {
				$this->error = $this->db->error();
				dol_syslog(get_class($this) . "::select Error " . $this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		}
	}
}
