<?php
/* Copyright (C) 2002-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Christophe Combelles	<ccomb@free.fr>
 * Copyright (C) 2005		Marc Barilley			<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2017	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2016	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2015		Bahfir Abbes			<bafbes@gmail.com>
 * Copyright (C) 2015		Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2016		Alexandre Spangaro		<aspangaro@zendsi.com>
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
 *	\file       htdocs/fourn/class/fournisseur.facture.class.php
 *	\ingroup    fournisseur,facture
 *	\brief      File of class to manage suppliers invoices
 */

include_once DOL_DOCUMENT_ROOT.'/core/class/commoninvoice.class.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';

/**
 *	Class to manage suppliers invoices
 */
class FactureFournisseur extends CommonInvoice
{
    public $element='invoice_supplier';
    public $table_element='facture_fourn';
    public $table_element_line='facture_fourn_det';
    public $fk_element='fk_facture_fourn';
    public $picto='bill';
    /**
     * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
     * @var int
     */
    public $ismultientitymanaged = 1;
    /**
     * 0=Default, 1=View may be restricted to sales representative only if no permission to see all or to company of external user if external user
     * @var integer
     */
    public $restrictiononfksoc = 1;

    /**
     * {@inheritdoc}
     */
    protected $table_ref_field = 'ref';

    public $rowid;
    public $ref;
    public $product_ref;
    public $ref_supplier;
    public $socid;
    //Check constants for types
    public $type = self::TYPE_STANDARD;

	/**
	 * Supplier invoice status
	 * @var int
	 * @see FactureFournisseur::STATUS_DRAFT, FactureFournisseur::STATUS_VALIDATED, FactureFournisseur::STATUS_PAID, FactureFournisseur::STATUS_ABANDONED
	 */
    public $statut;

    /**
     * Set to 1 if the invoice is completely paid, otherwise is 0
     * @var int
     * @deprecated Use statuses stored in self::statut
     */
    public $paye;

    public $author;
    public $libelle;
    public $datec;            // Creation date
    public $tms;              // Last update date
    public $date;             // Invoice date
    public $date_echeance;    // Max payment date
    public $amount;
    public $remise;
    public $tva;
    public $localtax1;
    public $localtax2;
    public $total_ht;
    public $total_tva;
    public $total_localtax1;
    public $total_localtax2;
    public $total_ttc;
	/**
	 * @deprecated
	 * @see note_private, note_public
	 */
    public $note;
    public $note_private;
    public $note_public;
    public $propalid;
    public $cond_reglement_id;
    public $cond_reglement_code;
    public $fk_account;
    public $mode_reglement_id;
    public $mode_reglement_code;

	/**
	 * Invoice lines
	 * @var SupplierInvoiceLine[]
	 */
    public $lines = array();
	/**
	 * @deprecated
	 */
    public $fournisseur;

	//Incorterms
    public $fk_incoterms;
    public $location_incoterms;
    public $libelle_incoterms;  //Used into tooltip

    public $extraparams=array();

	// Multicurrency
    public $fk_multicurrency;
    public $multicurrency_code;
    public $multicurrency_tx;
    public $multicurrency_total_ht;
    public $multicurrency_total_tva;
    public $multicurrency_total_ttc;
    //! id of source invoice if replacement invoice or credit note
    public $fk_facture_source;

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
	const CLOSECODE_BADCREDIT = 'badsupplier';
	const CLOSECODE_ABANDONED = 'abandon';
	const CLOSECODE_REPLACED = 'replaced';

    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->amount = 0;
        $this->remise = 0;
        $this->tva = 0;
        $this->total_localtax1 = 0;
        $this->total_localtax2 = 0;
        $this->total_ht = 0;
        $this->total_tva = 0;
        $this->total_ttc = 0;
        $this->propalid = 0;

        $this->products = array();
    }

    /**
     *    Create supplier invoice into database
     *
     *    @param      User		$user       object utilisateur qui cree
     *    @return     int    	     		Id invoice created if OK, < 0 if KO
     */
    public function create($user)
    {
        global $langs,$conf,$hookmanager;

		$error=0;
        $now=dol_now();

        // Clean parameters
        if (isset($this->ref_supplier)) $this->ref_supplier=trim($this->ref_supplier);
        if (empty($this->type)) $this->type = self::TYPE_STANDARD;
        if (empty($this->date)) $this->date=$now;

        $socid = $this->socid;
        $ref_supplier = $this->ref_supplier;
        $amount = $this->amount;
        $remise = $this->remise;

		// Multicurrency (test on $this->multicurrency_tx because we should take the default rate only if not using origin rate)
		if (!empty($this->multicurrency_code) && empty($this->multicurrency_tx)) list($this->fk_multicurrency,$this->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($this->db, $this->multicurrency_code);
		else $this->fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $this->multicurrency_code);
		if (empty($this->fk_multicurrency))
		{
			$this->multicurrency_code = $conf->currency;
			$this->fk_multicurrency = 0;
			$this->multicurrency_tx = 1;
		}

        $this->db->begin();

        if (! $remise) $remise = 0 ;
        $totalht = ($amount - $remise);

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_fourn (";
		$sql.= "ref";
        $sql.= ", ref_supplier";
        $sql.= ", entity";
        $sql.= ", type";
        $sql.= ", libelle";
        $sql.= ", fk_soc";
        $sql.= ", datec";
        $sql.= ", datef";
		$sql.= ", fk_projet";
		$sql.= ", fk_cond_reglement";
		$sql.= ", fk_mode_reglement";
        $sql.= ", fk_account";
        $sql.= ", note_private";
        $sql.= ", note_public";
        $sql.= ", fk_user_author";
        $sql.= ", date_lim_reglement";
		$sql.= ", fk_incoterms, location_incoterms";
        $sql.= ", fk_multicurrency";
        $sql.= ", multicurrency_code";
        $sql.= ", multicurrency_tx";
        $sql.= ", fk_facture_source";
        $sql.= ")";
        $sql.= " VALUES (";
		$sql.= "'(PROV)'";
        $sql.= ", '".$this->db->escape($this->ref_supplier)."'";
        $sql.= ", ".$conf->entity;
        $sql.= ", '".$this->db->escape($this->type)."'";
        $sql.= ", '".$this->db->escape($this->libelle)."'";
        $sql.= ", ".$this->socid;
        $sql.= ", '".$this->db->idate($now)."'";
        $sql.= ", '".$this->db->idate($this->date)."'";
		$sql.= ", ".($this->fk_project > 0 ? $this->fk_project:"null");
		$sql.= ", ".($this->cond_reglement_id > 0 ? $this->cond_reglement_id:"null");
		$sql.= ", ".($this->mode_reglement_id > 0 ? $this->mode_reglement_id:"null");
        $sql.= ", ".($this->fk_account>0?$this->fk_account:'NULL');
        $sql.= ", '".$this->db->escape($this->note_private)."'";
        $sql.= ", '".$this->db->escape($this->note_public)."'";
        $sql.= ", ".$user->id.",";
        $sql.= $this->date_echeance!=''?"'".$this->db->idate($this->date_echeance)."'":"null";
		$sql.= ", ".(int) $this->fk_incoterms;
        $sql.= ", '".$this->db->escape($this->location_incoterms)."'";
		$sql.= ", ".(int) $this->fk_multicurrency;
		$sql.= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql.= ", ".(double) $this->multicurrency_tx;
        $sql.= ", ".(isset($this->fk_facture_source)?$this->fk_facture_source:"NULL");
        $sql.= ")";

        dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn');

            // Update ref with new one
            $this->ref='(PROV'.$this->id.')';
            $sql = 'UPDATE '.MAIN_DB_PREFIX."facture_fourn SET ref='".$this->db->escape($this->ref)."' WHERE rowid=".$this->id;

            dol_syslog(get_class($this)."::create", LOG_DEBUG);
            $resql=$this->db->query($sql);
            if (! $resql) $error++;

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

			// Add linked object (deprecated, use ->linkedObjectsIds instead)
            if (! $error && $this->id && ! empty($this->origin) && ! empty($this->origin_id))
            {
                $ret = $this->add_object_linked();
                if (! $ret)
                {
                    dol_print_error($this->db);
                    $error++;
                }
            }

			if (count($this->lines) && is_object($this->lines[0]))	// If this->lines is array of InvoiceLines (preferred mode)
			{
                dol_syslog("There is ".count($this->lines)." lines that are invoice lines objects");
                foreach ($this->lines as $i => $val)
                {
                    $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture_fourn_det (fk_facture_fourn)';
                    $sql .= ' VALUES ('.$this->id.')';

                    $resql_insert=$this->db->query($sql);
                    if ($resql_insert)
                    {
                        $idligne = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn_det');

                        $this->updateline(
                            $idligne,
                            $this->lines[$i]->description,
                            $this->lines[$i]->pu_ht,
                            $this->lines[$i]->tva_tx,
                            $this->lines[$i]->localtax1_tx,
                            $this->lines[$i]->localtax2_tx,
                            $this->lines[$i]->qty,
                            $this->lines[$i]->fk_product,
                            'HT',
                            (! empty($this->lines[$i]->info_bits)?$this->lines[$i]->info_bits:''),
                            $this->lines[$i]->product_type
                        );
                    }
                    else
                    {
                        $this->error=$this->db->lasterror();
                        $this->db->rollback();
                        return -5;
                    }
                }
			}
			else	// If this->lines is an array of invoice line arrays
			{
			    dol_syslog("There is ".count($this->lines)." lines that are array lines");
			    foreach ($this->lines as $i => $val)
			    {
                	$line = $this->lines[$i];

                	// Test and convert into object this->lines[$i]. When coming from REST API, we may still have an array
				    //if (! is_object($line)) $line=json_decode(json_encode($line), FALSE);  // convert recursively array into object.
                	if (! is_object($line)) $line = (object) $line;

                	$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture_fourn_det (fk_facture_fourn)';
			        $sql .= ' VALUES ('.$this->id.')';

			        $resql_insert=$this->db->query($sql);
			        if ($resql_insert)
			        {
			            $idligne = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn_det');

			            $this->updateline(
			                $idligne,
			                $line->description,
			                $line->pu_ht,
			                $line->tva_tx,
			                $line->localtax1_tx,
			                $line->localtax2_tx,
			                $line->qty,
			                $line->fk_product,
			                'HT',
			                (! empty($line->info_bits)?$line->info_bits:''),
			                $line->product_type
			                );
			        }
			        else
			        {
			            $this->error=$this->db->lasterror();
			            $this->db->rollback();
			            return -5;
			        }
			    }
			}

            // Update total price
            $result=$this->update_price();
            if ($result > 0)
            {
            	$action='create';

				// Actions on extra fields (by external module or standard code)
				// TODO le hook fait double emploi avec le trigger !!
				$hookmanager->initHooks(array('supplierinvoicedao'));
				$parameters=array('socid'=>$this->id);
				$reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
				if (empty($reshook))
				{
	            	if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
					{
						$result=$this->insertExtraFields();               // This also set $this->error or $this->errors if errors are found
						if ($result < 0)
						{
							$error++;
						}
					}
				}
				else if ($reshook < 0) $error++;

				if (! $error)
				{
                    // Call trigger
                    $result=$this->call_trigger('BILL_SUPPLIER_CREATE',$user);
                    if ($result < 0) $error++;
                    // End call triggers
				}

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
            if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
            {
                $this->error=$langs->trans('ErrorRefAlreadyExists');
                $this->db->rollback();
                return -1;
            }
            else
            {
                $this->error=$this->db->lasterror();
                $this->db->rollback();
                return -2;
            }
        }
    }

    /**
     *    Load object in memory from database
     *
     *    @param	int		$id         Id supplier invoice
     *    @param	string	$ref		Ref supplier invoice
     *    @return   int        			<0 if KO, >0 if OK, 0 if not found
     */
    public function fetch($id='',$ref='')
    {
        global $langs;

        $sql = "SELECT";
        $sql.= " t.rowid,";
		$sql.= " t.ref,";
        $sql.= " t.ref_supplier,";
        $sql.= " t.entity,";
        $sql.= " t.type,";
        $sql.= " t.fk_soc,";
        $sql.= " t.datec,";
        $sql.= " t.datef,";
        $sql.= " t.tms,";
        $sql.= " t.libelle,";
        $sql.= " t.paye,";
        $sql.= " t.amount,";
        $sql.= " t.remise,";
        $sql.= " t.close_code,";
        $sql.= " t.close_note,";
        $sql.= " t.tva,";
        $sql.= " t.localtax1,";
        $sql.= " t.localtax2,";
        //$sql.= " t.total,";
        $sql.= " t.total_ht,";
        $sql.= " t.total_tva,";
        $sql.= " t.total_ttc,";
        $sql.= " t.fk_statut,";
        $sql.= " t.fk_user_author,";
        $sql.= " t.fk_user_valid,";
        $sql.= " t.fk_facture_source,";
        $sql.= " t.fk_projet,";
        $sql.= " t.fk_cond_reglement,";
        $sql.= " t.fk_account,";
        $sql.= " t.fk_mode_reglement,";
        $sql.= " t.date_lim_reglement,";
        $sql.= " t.note_private,";
        $sql.= " t.note_public,";
        $sql.= " t.model_pdf,";
        $sql.= " t.import_key,";
        $sql.= " t.extraparams,";
        $sql.= " cr.code as cond_reglement_code, cr.libelle as cond_reglement_libelle,";
        $sql.= " p.code as mode_reglement_code, p.libelle as mode_reglement_libelle,";
        $sql.= ' s.nom as socnom, s.rowid as socid,';
        $sql.= ' t.fk_incoterms, t.location_incoterms,';
        $sql.= " i.libelle as libelle_incoterms,";
        $sql.= ' t.fk_multicurrency, t.multicurrency_code, t.multicurrency_tx, t.multicurrency_total_ht, t.multicurrency_total_tva, t.multicurrency_total_ttc';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as t';
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON (t.fk_soc = s.rowid)";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_payment_term as cr ON t.fk_cond_reglement = cr.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as p ON t.fk_mode_reglement = p.id";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON t.fk_incoterms = i.rowid';
        if ($id)  $sql.= " WHERE t.rowid=".$id;
        if ($ref) $sql.= " WHERE t.ref='".$this->db->escape($ref)."'";

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id					= $obj->rowid;
                $this->ref					= $obj->ref?$obj->ref:$obj->rowid;	// We take rowid if ref is empty for backward compatibility

                $this->ref_supplier			= $obj->ref_supplier;
                $this->entity				= $obj->entity;
                $this->type					= empty($obj->type)? self::TYPE_STANDARD:$obj->type;
                $this->fk_soc				= $obj->fk_soc;
                $this->datec				= $this->db->jdate($obj->datec);
                $this->date					= $this->db->jdate($obj->datef);
                $this->datep				= $this->db->jdate($obj->datef);
                $this->tms					= $this->db->jdate($obj->tms);
                $this->libelle				= $obj->libelle;
                $this->label				= $obj->libelle;
                $this->paye					= $obj->paye;
                $this->amount				= $obj->amount;
                $this->remise				= $obj->remise;
                $this->close_code			= $obj->close_code;
                $this->close_note			= $obj->close_note;
                $this->tva					= $obj->tva;
                $this->total_localtax1		= $obj->localtax1;
                $this->total_localtax2		= $obj->localtax2;
                //$this->total				= $obj->total;
                $this->total_ht				= $obj->total_ht;
                $this->total_tva			= $obj->total_tva;
                $this->total_ttc			= $obj->total_ttc;
                $this->fk_statut			= $obj->fk_statut;
                $this->statut				= $obj->fk_statut;
                $this->fk_user_author		= $obj->fk_user_author;
                $this->author				= $obj->fk_user_author;
                $this->fk_user_valid		= $obj->fk_user_valid;
                $this->fk_facture_source	= $obj->fk_facture_source;
                $this->fk_project			= $obj->fk_projet;
	            $this->cond_reglement_id	= $obj->fk_cond_reglement;
	            $this->cond_reglement_code	= $obj->cond_reglement_code;
	            $this->cond_reglement		= $obj->cond_reglement_libelle;
	            $this->cond_reglement_doc	= $obj->cond_reglement_libelle;
                $this->fk_account           = $obj->fk_account;
	            $this->mode_reglement_id	= $obj->fk_mode_reglement;
	            $this->mode_reglement_code	= $obj->mode_reglement_code;
	            $this->mode_reglement		= $obj->mode_reglement_libelle;
                $this->date_echeance		= $this->db->jdate($obj->date_lim_reglement);
                $this->note					= $obj->note_private;	// deprecated
                $this->note_private			= $obj->note_private;
                $this->note_public			= $obj->note_public;
                $this->model_pdf			= $obj->model_pdf;
                $this->modelpdf			    = $obj->model_pdf;
                $this->import_key			= $obj->import_key;

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

                $this->extraparams			= (array) json_decode($obj->extraparams, true);

                $this->socid  = $obj->socid;
                $this->socnom = $obj->socnom;

                // Retreive all extrafield
                // fetch optionals attributes and labels
                $this->fetch_optionals();

                if ($this->statut == self::STATUS_DRAFT) $this->brouillon = 1;

                $result=$this->fetch_lines();
                if ($result < 0)
                {
                    $this->error=$this->db->lasterror();
                    return -3;
                }

            }
            else
            {
                $this->error='Bill with id '.$id.' not found';
                dol_syslog(get_class($this).'::fetch '.$this->error);
                return 0;
            }

            $this->db->free($resql);
            return 1;
        }
        else
        {
            $this->error="Error ".$this->db->lasterror();
            return -1;
        }
    }


    /**
     *	Load this->lines
     *
     *	@return     int         1 si ok, < 0 si erreur
     */
    function fetch_lines()
    {
        $sql = 'SELECT f.rowid, f.ref as ref_supplier, f.description, f.pu_ht, f.pu_ttc, f.qty, f.remise_percent, f.vat_src_code, f.tva_tx';
        $sql.= ', f.localtax1_tx, f.localtax2_tx, f.localtax1_type, f.localtax2_type, f.total_localtax1, f.total_localtax2, f.fk_facture_fourn ';
        $sql.= ', f.total_ht, f.tva as total_tva, f.total_ttc, f.fk_product, f.product_type, f.info_bits, f.rang, f.special_code, f.fk_parent_line, f.fk_unit';
        $sql.= ', p.rowid as product_id, p.ref as product_ref, p.label as label, p.description as product_desc';
		$sql.= ', f.fk_multicurrency, f.multicurrency_code, f.multicurrency_subprice, f.multicurrency_total_ht, f.multicurrency_total_tva, f.multicurrency_total_ttc';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn_det as f';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON f.fk_product = p.rowid';
        $sql.= ' WHERE fk_facture_fourn='.$this->id;
        $sql.= ' ORDER BY f.rang, f.rowid';


        dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
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

	                $line = new SupplierInvoiceLine($this->db);

                    $line->id				= $obj->rowid;
                    $line->rowid			= $obj->rowid;
                    $line->description		= $obj->description;
                    $line->product_ref		= $obj->product_ref;
                    $line->ref				= $obj->product_ref;
                    $line->ref_supplier		= $obj->ref_supplier;
                    $line->libelle			= $obj->label;
                    $line->label  			= $obj->label;
                    $line->product_desc		= $obj->product_desc;
                    $line->subprice			= $obj->pu_ht;
                    $line->pu_ht			= $obj->pu_ht;
                    $line->pu_ttc			= $obj->pu_ttc;

                    $line->vat_src_code     = $obj->vat_src_code;
                    $line->tva_tx			= $obj->tva_tx;
                    $line->localtax1_tx		= $obj->localtax1_tx;
                    $line->localtax2_tx		= $obj->localtax2_tx;
                    $line->localtax1_type	= $obj->localtax1_type;
                    $line->localtax2_type	= $obj->localtax2_type;
                    $line->qty				= $obj->qty;
                    $line->remise_percent   = $obj->remise_percent;
                    $line->tva				= $obj->total_tva;
                    $line->total_ht			= $obj->total_ht;
                    $line->total_tva		= $obj->total_tva;
                    $line->total_localtax1	= $obj->total_localtax1;
                    $line->total_localtax2	= $obj->total_localtax2;
                    $line->fk_facture_fourn     = $obj->fk_facture_fourn;
                    $line->total_ttc		= $obj->total_ttc;
                    $line->fk_product		= $obj->fk_product;
                    $line->product_type		= $obj->product_type;
                    $line->product_label	= $obj->label;
                    $line->info_bits		= $obj->info_bits;
                    $line->fk_parent_line   = $obj->fk_parent_line;
                    $line->special_code		= $obj->special_code;
                    $line->rang       		= $obj->rang;
                    $line->fk_unit          = $obj->fk_unit;

					// Multicurrency
					$line->fk_multicurrency 		= $obj->fk_multicurrency;
					$line->multicurrency_code 		= $obj->multicurrency_code;
					$line->multicurrency_subprice 	= $obj->multicurrency_subprice;
					$line->multicurrency_total_ht 	= $obj->multicurrency_total_ht;
					$line->multicurrency_total_tva 	= $obj->multicurrency_total_tva;
					$line->multicurrency_total_ttc 	= $obj->multicurrency_total_ttc;

	                $this->lines[$i] = $line;

                    $i++;
                }
            }
            $this->db->free($resql_rows);
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -3;
        }
    }


    /**
     *  Update database
     *
     *  @param	User	$user            User that modify
     *  @param  int		$notrigger       0=launch triggers after, 1=disable triggers
     *  @return int 			         <0 if KO, >0 if OK
     */
    public function update($user=null, $notrigger=0)
    {
        global $conf, $langs;
        $error=0;

        // Clean parameters
        if (empty($this->type)) $this->type= self::TYPE_STANDARD;
		if (isset($this->ref)) $this->ref=trim($this->ref);
        if (isset($this->ref_supplier)) $this->ref_supplier=trim($this->ref_supplier);
        if (isset($this->entity)) $this->entity=trim($this->entity);
        if (isset($this->type)) $this->type=trim($this->type);
        if (isset($this->fk_soc)) $this->fk_soc=trim($this->fk_soc);
        if (isset($this->libelle)) $this->libelle=trim($this->libelle);
        if (isset($this->paye)) $this->paye=trim($this->paye);
        if (isset($this->amount)) $this->amount=trim($this->amount);
        if (isset($this->remise)) $this->remise=trim($this->remise);
        if (isset($this->close_code)) $this->close_code=trim($this->close_code);
        if (isset($this->close_note)) $this->close_note=trim($this->close_note);
        if (isset($this->tva)) $this->tva=trim($this->tva);
        if (isset($this->localtax1)) $this->localtax1=trim($this->localtax1);
        if (isset($this->localtax2)) $this->localtax2=trim($this->localtax2);
        if (empty($this->total_ht)) $this->total_ht=0;
        if (empty($this->total_tva)) $this->total_tva=0;
        //	if (isset($this->total_localtax1)) $this->total_localtax1=trim($this->total_localtax1);
        //	if (isset($this->total_localtax2)) $this->total_localtax2=trim($this->total_localtax2);
        if (isset($this->total_ttc)) $this->total_ttc=trim($this->total_ttc);
        if (isset($this->statut)) $this->statut=(int) $this->statut;
        if (isset($this->author)) $this->author=trim($this->author);
        if (isset($this->fk_user_valid)) $this->fk_user_valid=trim($this->fk_user_valid);
        if (isset($this->fk_facture_source)) $this->fk_facture_source=trim($this->fk_facture_source);
        if (isset($this->fk_project)) $this->fk_project=trim($this->fk_project);
        if (isset($this->cond_reglement_id)) $this->cond_reglement_id=trim($this->cond_reglement_id);
        if (isset($this->note_private)) $this->note=trim($this->note_private);
        if (isset($this->note_public)) $this->note_public=trim($this->note_public);
        if (isset($this->model_pdf)) $this->model_pdf=trim($this->model_pdf);
        if (isset($this->import_key)) $this->import_key=trim($this->import_key);


        // Check parameters
        // Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn SET";
		$sql.= " ref=".(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"null").",";
        $sql.= " ref_supplier=".(isset($this->ref_supplier)?"'".$this->db->escape($this->ref_supplier)."'":"null").",";
        $sql.= " entity=".(isset($this->entity)?$this->entity:"null").",";
        $sql.= " type=".(isset($this->type)?$this->type:"null").",";
        $sql.= " fk_soc=".(isset($this->fk_soc)?$this->fk_soc:"null").",";
        $sql.= " datec=".(dol_strlen($this->datec)!=0 ? "'".$this->db->idate($this->datec)."'" : 'null').",";
        $sql.= " datef=".(dol_strlen($this->date)!=0 ? "'".$this->db->idate($this->date)."'" : 'null').",";
        if (dol_strlen($this->tms) != 0) $sql.= " tms=".(dol_strlen($this->tms)!=0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
        $sql.= " libelle=".(isset($this->label)?"'".$this->db->escape($this->label)."'":"null").",";
        $sql.= " paye=".(isset($this->paye)?$this->paye:"null").",";
        $sql.= " amount=".(isset($this->amount)?$this->amount:"null").",";
        $sql.= " remise=".(isset($this->remise)?$this->remise:"null").",";
        $sql.= " close_code=".(isset($this->close_code)?"'".$this->db->escape($this->close_code)."'":"null").",";
        $sql.= " close_note=".(isset($this->close_note)?"'".$this->db->escape($this->close_note)."'":"null").",";
        $sql.= " tva=".(isset($this->tva)?$this->tva:"null").",";
        $sql.= " localtax1=".(isset($this->localtax1)?$this->localtax1:"null").",";
        $sql.= " localtax2=".(isset($this->localtax2)?$this->localtax2:"null").",";
        //$sql.= " total=".(isset($this->total)?$this->total:"null").",";
        $sql.= " total_ht=".(isset($this->total_ht)?$this->total_ht:"null").",";
        $sql.= " total_tva=".(isset($this->total_tva)?$this->total_tva:"null").",";
        $sql.= " total_ttc=".(isset($this->total_ttc)?$this->total_ttc:"null").",";
        $sql.= " fk_statut=".(isset($this->statut)?$this->statut:"null").",";
        $sql.= " fk_user_author=".(isset($this->author)?$this->author:"null").",";
        $sql.= " fk_user_valid=".(isset($this->fk_user_valid)?$this->fk_user_valid:"null").",";
        $sql.= " fk_facture_source=".(isset($this->fk_facture_source)?$this->fk_facture_source:"null").",";
        $sql.= " fk_projet=".(isset($this->fk_project)?$this->fk_project:"null").",";
        $sql.= " fk_cond_reglement=".(isset($this->cond_reglement_id)?$this->cond_reglement_id:"null").",";
        $sql.= " date_lim_reglement=".(dol_strlen($this->date_echeance)!=0 ? "'".$this->db->idate($this->date_echeance)."'" : 'null').",";
        $sql.= " note_private=".(isset($this->note_private)?"'".$this->db->escape($this->note_private)."'":"null").",";
        $sql.= " note_public=".(isset($this->note_public)?"'".$this->db->escape($this->note_public)."'":"null").",";
        $sql.= " model_pdf=".(isset($this->model_pdf)?"'".$this->db->escape($this->model_pdf)."'":"null").",";
        $sql.= " import_key=".(isset($this->import_key)?"'".$this->db->escape($this->import_key)."'":"null")."";
        $sql.= " WHERE rowid=".$this->id;

        $this->db->begin();

        dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);

        if (!$resql) {
            $error++;

            if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                $this->errors[] = $langs->trans('ErrorRefAlreadyExists');
            } else {
                $this->errors[] = "Error ".$this->db->lasterror();
            }
        }

        if (! $error)
        {
            if (! $notrigger)
            {
                // Call trigger
                $result=$this->call_trigger('BILL_SUPPLIER_UPDATE',$user);
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
     *    Add a discount line into an invoice (as an invoice line) using an existing absolute discount (Consume the discount)
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
    		if ($remise->fk_invoice_supplier)	// Protection against multiple submission
    		{
    			$this->error=$langs->trans("ErrorDiscountAlreadyUsed");
    			$this->db->rollback();
    			return -5;
    		}

    		$facligne=new SupplierInvoiceLine($this->db);
    		$facligne->fk_facture_fourn=$this->id;
    		$facligne->fk_remise_except=$remise->id;
    		$facligne->desc=$remise->description;   	// Description ligne
    		$facligne->vat_src_code=$remise->vat_src_code;
    		$facligne->tva_tx=$remise->tva_tx;
    		$facligne->subprice = -$remise->amount_ht;
    		$facligne->fk_product=0;					// Id produit predefini
    		$facligne->product_type=0;
    		$facligne->qty=1;
    		$facligne->remise_percent=0;
    		$facligne->rang=-1;
    		$facligne->info_bits=2;

    		// Get buy/cost price of invoice that is source of discount
    		if ($remise->fk_invoice_supplier_source > 0)
    		{
    			$srcinvoice=new FactureFournisseur($this->db);
    			$srcinvoice->fetch($remise->fk_invoice_supplier_source);
    			$totalcostpriceofinvoice=0;
    			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';  // TODO Move this into commonobject
    			$formmargin=new FormMargin($this->db);
    			$arraytmp=$formmargin->getMarginInfosArray($srcinvoice, false);
    			$facligne->pa_ht = $arraytmp['pa_total'];
    		}

    		$facligne->total_ht  = -$remise->amount_ht;
    		$facligne->total_tva = -$remise->amount_tva;
    		$facligne->total_ttc = -$remise->amount_ttc;

    		$facligne->multicurrency_subprice = -$remise->multicurrency_subprice;
    		$facligne->multicurrency_total_ht = -$remise->multicurrency_total_ht;
    		$facligne->multicurrency_total_tva = -$remise->multicurrency_total_tva;
    		$facligne->multicurrency_total_ttc = -$remise->multicurrency_total_ttc;

    		$lineid=$facligne->insert();
    		if ($lineid > 0)
    		{
    			$result=$this->update_price(1);
    			if ($result > 0)
    			{
    				// Create link between discount and invoice line
    				$result=$remise->link_to_invoice($lineid,0,'supplier');
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
     *	Delete invoice from database
     *
     *  @param      User	$user		    User object
     *	@param	    int		$notrigger	    1=Does not execute triggers, 0= execute triggers
     *	@return		int						<0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger=0)
    {
        global $langs,$conf;

        $rowid=$this->id;

        dol_syslog("FactureFournisseur::delete rowid=".$rowid, LOG_DEBUG);

        // TODO Test if there is at least on payment. If yes, refuse to delete.

        $error=0;
        $this->db->begin();

        if (! $error && ! $notrigger)
        {
            // Call trigger
            $result=$this->call_trigger('BILL_SUPPLIER_DELETE',$user);
            if ($result < 0)
            {
                $this->db->rollback();
                return -1;
            }
            // Fin appel triggers
        }

		if (! $error) {
			// If invoice was converted into a discount not yet consumed, we remove discount
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'societe_remise_except';
			$sql .= ' WHERE fk_invoice_supplier_source = ' . $rowid;
			$sql .= ' AND fk_invoice_supplier_line IS NULL';
			$resql = $this->db->query($sql);

			// If invoice has consumned discounts
			$this->fetch_lines();
			$list_rowid_det = array ();
			foreach ($this->lines as $key => $invoiceline) {
				$list_rowid_det[] = $invoiceline->rowid;
			}

			// Consumned discounts are freed
			if (count($list_rowid_det)) {
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'societe_remise_except';
				$sql .= ' SET fk_invoice_supplier = NULL, fk_invoice_supplier_line = NULL';
				$sql .= ' WHERE fk_invoice_supplier_line IN (' . join(',', $list_rowid_det) . ')';

				dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
				if (! $this->db->query($sql)) {
					$error ++;
				}
			}
		}

        if (! $error)
        {
            $sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn_det WHERE fk_facture_fourn = '.$rowid.';';
            dol_syslog(get_class($this)."::delete", LOG_DEBUG);
            $resql = $this->db->query($sql);
            if ($resql)
            {
                $sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn WHERE rowid = '.$rowid;
                dol_syslog(get_class($this)."::delete", LOG_DEBUG);
                $resql2 = $this->db->query($sql);
                if (! $resql2) {
                	$error++;
                }
            }
            else {
            	$error++;
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
        	// Delete linked object
        	$res = $this->deleteObjectLinked();
        	if ($res < 0) $error++;
        }

        if (! $error)
        {
        	// We remove directory
        	if ($conf->fournisseur->facture->dir_output)
        	{
        		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        		$ref = dol_sanitizeFileName($this->ref);
        		$dir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($this->id, 2, 0, 0, $this, 'invoive_supplier').$ref;
        		$file = $dir . "/" . $ref . ".pdf";
        		if (file_exists($file))
        		{
        			if (! dol_delete_file($file,0,0,0,$this)) // For triggers
        			{
        				$this->error='ErrorFailToDeleteFile';
        				$error++;
        			}
        		}
        		if (file_exists($dir))
        		{
        			$res=@dol_delete_dir_recursive($dir);

        			if (! $res)
        			{
        				$this->error='ErrorFailToDeleteDir';
        				$error++;
        			}
        		}
        	}
        }

        // Remove extrafields
        if ((! $error) && (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED))) // For avoid conflicts if trigger used
        {
        	$result=$this->deleteExtraFields();
        	if ($result < 0)
        	{
        		$error++;
        		dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
        	}
        }

        if (! $error)
        {
        	dol_syslog(get_class($this)."::delete $this->id by $user->id", LOG_DEBUG);
        	$this->db->commit();
        	return 1;
        }
        else
        {
        	$this->error=$this->db->lasterror();
        	$this->db->rollback();
        	return -$error;
        }
    }


    /**
     *	Tag invoice as a payed invoice
     *
     *	@param  User	$user       Object user
	 *	@param  string	$close_code	Code renseigne si on classe a payee completement alors que paiement incomplet. Not implementd yet.
	 *	@param  string	$close_note	Commentaire renseigne si on classe a payee alors que paiement incomplet. Not implementd yet.
     *	@return int         		<0 si ko, >0 si ok
     */
    function set_paid($user, $close_code='', $close_note='')
    {
        global $conf,$langs;
        $error=0;

        $this->db->begin();

        $sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn';
        $sql.= ' SET paye = 1, fk_statut=2';
        $sql.= ' WHERE rowid = '.$this->id;

        dol_syslog("FactureFournisseur::set_paid", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            // Call trigger
            $result=$this->call_trigger('BILL_SUPPLIER_PAYED',$user);
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
     *	Tag la facture comme non payee completement + appel trigger BILL_UNPAYED
     *	Fonction utilisee quand un paiement prelevement est refuse,
     *	ou quand une facture annulee et reouverte.
     *
     *	@param      User	$user       Object user that change status
     *	@return     int         		<0 si ok, >0 si ok
     */
    function set_unpaid($user)
    {
        global $conf,$langs;
        $error=0;

        $this->db->begin();

        $sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn';
        $sql.= ' SET paye=0, fk_statut=1, close_code=null, close_note=null';
        $sql.= ' WHERE rowid = '.$this->id;

        dol_syslog("FactureFournisseur::set_unpaid", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            // Call trigger
            $result=$this->call_trigger('BILL_SUPPLIER_UNPAYED',$user);
            if ($result < 0) $error++;
            // End call triggers
        }
        else
        {
            $error++;
            $this->error=$this->db->lasterror();
            dol_syslog("FactureFournisseur::set_unpaid ".$this->error);
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
     *	Tag invoice as validated + call trigger BILL_VALIDATE
     *
     *	@param	User	$user           Object user that validate
     *	@param  string	$force_number   Reference to force on invoice
     *	@param	int		$idwarehouse	Id of warehouse for stock change
     *  @param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
     *	@return int 			        <0 if KO, =0 if nothing to do, >0 if OK
     */
    public function validate($user, $force_number='', $idwarehouse=0, $notrigger=0)
    {
        global $conf,$langs;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $now=dol_now();

        $error=0;
        dol_syslog(get_class($this).'::validate user='.$user->id.', force_number='.$force_number.', idwarehouse='.$idwarehouse);

        // Force to have object complete for checks
        $this->fetch_thirdparty();
        $this->fetch_lines();

        // Check parameters
        if ($this->statut > self::STATUS_DRAFT)	// This is to avoid to validate twice (avoid errors on logs and stock management)
        {
            dol_syslog(get_class($this)."::validate no draft status", LOG_WARNING);
            return 0;
        }
        if (preg_match('/^'.preg_quote($langs->trans("CopyOf").' ').'/', $this->ref_supplier))
        {
        	$langs->load("errors");
        	$this->error=$langs->trans("ErrorFieldFormat",$langs->transnoentities("RefSupplier")).'. '.$langs->trans('RemoveString',$langs->transnoentitiesnoconv("CopyOf"));
            return -1;
        }
        if (count($this->lines) <= 0)
        {
        	$langs->load("errors");
            $this->error=$langs->trans("ErrorObjectMustHaveLinesToBeValidated", $this->ref);
            return -1;
        }

        $this->db->begin();

        // Define new ref
        if ($force_number)
        {
            $num = $force_number;
        }
        else if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref)) // empty should not happened, but when it occurs, the test save life
        {
            $num = $this->getNextNumRef($this->thirdparty);
        }
        else
		{
            $num = $this->ref;
        }
        $this->newref = $num;

        $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn";
        $sql.= " SET ref='".$num."', fk_statut = 1, fk_user_valid = ".$user->id.", date_valid = '".$this->db->idate($now)."'";
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::validate", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            // Si on incrémente le produit principal et ses composants à la validation de facture fournisseur
            if (! $error && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL))
            {
                require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
                $langs->load("agenda");

                $cpt=count($this->lines);
                for ($i = 0; $i < $cpt; $i++)
                {
                    if ($this->lines[$i]->fk_product > 0)
                    {
                        $this->line = $this->lines[$i];
                        $mouvP = new MouvementStock($this->db);
						$mouvP->origin = &$this;
                        // We increase stock for product
                        $up_ht_disc=$this->lines[$i]->pu_ht;
                        if (! empty($this->lines[$i]->remise_percent) && empty($conf->global->STOCK_EXCLUDE_DISCOUNT_FOR_PMP)) $up_ht_disc=price2num($up_ht_disc * (100 - $this->lines[$i]->remise_percent) / 100, 'MU');
                        $result=$mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $up_ht_disc, $langs->trans("InvoiceValidatedInDolibarr",$num));
                        if ($result < 0) { $error++; }
                        unset($this->line);
                    }
                }
            }

            // Triggers call
            if (! $error && empty($notrigger))
            {
                // Call trigger
                $result=$this->call_trigger('BILL_SUPPLIER_VALIDATE',$user);
                if ($result < 0) $error++;
                // End call triggers
            }

            if (! $error)
            {
	            $this->oldref = $this->ref;

            	// Rename directory if dir was a temporary ref
            	if (preg_match('/^[\(]?PROV/i', $this->ref))
            	{
            		// On renomme repertoire facture ($this->ref = ancienne ref, $num = nouvelle ref)
            		// in order not to lose the attached files
            		$oldref = dol_sanitizeFileName($this->ref);
            		$newref = dol_sanitizeFileName($num);

            		$dirsource = $conf->fournisseur->facture->dir_output.'/'.get_exdir($this->id,2,0,0, $this, 'invoice_supplier').$oldref;
            		$dirdest = $conf->fournisseur->facture->dir_output.'/'.get_exdir($this->id,2,0,0, $this, 'invoice_supplier').$newref;
            		if (file_exists($dirsource))
            		{
            			dol_syslog(get_class($this)."::validate rename dir ".$dirsource." into ".$dirdest);

            			if (@rename($dirsource, $dirdest))
            			{
            				dol_syslog("Rename ok");
                            // Rename docs starting with $oldref with $newref
	                        $listoffiles=dol_dir_list($conf->fournisseur->facture->dir_output.'/'.get_exdir($this->id,2,0,0, $this, 'invoice_supplier').$newref, 'files', 1, '^'.preg_quote($oldref,'/'));
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

            // Set new ref and define current statut
            if (! $error)
            {
            	$this->ref = $num;
            	$this->statut=self::STATUS_VALIDATED;
            	//$this->date_validation=$now; this is stored into log table
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
            $this->error=$this->db->error();
            $this->db->rollback();
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
    function set_draft($user, $idwarehouse=-1)
    {
        global $conf,$langs;

        $error=0;

        if ($this->statut == self::STATUS_DRAFT)
        {
            dol_syslog(get_class($this)."::set_draft already draft status", LOG_WARNING);
            return 0;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn";
        $sql.= " SET fk_statut = 0";
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::set_draft", LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {
            // Si on incremente le produit principal et ses composants a la validation de facture fournisseur, on decremente
            if ($result >= 0 && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL))
            {
                require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
                $langs->load("agenda");

                $cpt=count($this->lines);
                for ($i = 0; $i < $cpt; $i++)
                {
                    if ($this->lines[$i]->fk_product > 0)
                    {
                        $mouvP = new MouvementStock($this->db);
                        $mouvP->origin = &$this;
						// We increase stock for product
                        $result=$mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("InvoiceBackToDraftInDolibarr", $this->ref));
                    }
                }
            }
            // Triggers call
            if (! $error && empty($notrigger))
            {
                // Call trigger
                $result=$this->call_trigger('BILL_SUPPLIER_UNVALIDATE',$user);
                if ($result < 0) $error++;
                // End call triggers
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
     *	Ajoute une ligne de facture (associe a aucun produit/service predefini)
     *	Les parametres sont deja cense etre juste et avec valeurs finales a l'appel
     *	de cette methode. Aussi, pour le taux tva, il doit deja avoir ete defini
     *	par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,idprod)
     *	et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue).
     *
     *	@param    	string	$desc            	Description de la ligne
     *	@param    	double	$pu              	Prix unitaire (HT ou TTC selon price_base_type, > 0 even for credit note)
     *	@param    	double	$txtva           	Force Vat rate to use, -1 for auto.
     *	@param		double	$txlocaltax1		LocalTax1 Rate
     *	@param		double	$txlocaltax2		LocalTax2 Rate
     *	@param    	double	$qty             	Quantite
     *	@param    	int		$fk_product      	Id du produit/service predefini
     *	@param    	double	$remise_percent  	Pourcentage de remise de la ligne
     *	@param    	date	$date_start      	Date de debut de validite du service
     * 	@param    	date	$date_end        	Date de fin de validite du service
     * 	@param    	string	$ventil          	Code de ventilation comptable
     *	@param    	int		$info_bits			Bits de type de lines
     *	@param    	string	$price_base_type 	HT ou TTC
     *	@param		int		$type				Type of line (0=product, 1=service)
     *  @param      int		$rang            	Position of line
     *  @param		int		$notrigger			Disable triggers
	 *  @param		array	$array_options		extrafields array
     * 	@param 		string	$fk_unit 			Code of the unit to use. Null to use the default one
     *  @param      int     $origin_id          id origin document
	 *  @param		double	$pu_ht_devise		Amount in currency
	 *  @param		string	$ref_supplier		Supplier ref
     *	@return    	int             			>0 if OK, <0 if KO
     *
     *  FIXME Add field ref (that should be named ref_supplier) and label into update. For example can be filled when product line created from order.
     */
    public function addline($desc, $pu, $txtva, $txlocaltax1, $txlocaltax2, $qty, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits='', $price_base_type='HT', $type=0, $rang=-1, $notrigger=false, $array_options=0, $fk_unit=null, $origin_id=0, $pu_ht_devise=0, $ref_supplier='')
    {
        dol_syslog(get_class($this)."::addline $desc,$pu,$qty,$txtva,$fk_product,$remise_percent,$date_start,$date_end,$ventil,$info_bits,$price_base_type,$type,$fk_unit", LOG_DEBUG);
        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
        global $mysoc, $conf;

        // Clean parameters
        if (empty($remise_percent)) $remise_percent=0;
        if (empty($qty)) $qty=0;
        if (empty($info_bits)) $info_bits=0;
        if (empty($rang)) $rang=0;
        if (empty($ventil)) $ventil=0;
        if (empty($txtva)) $txtva=0;
        if (empty($txlocaltax1)) $txlocaltax1=0;
        if (empty($txlocaltax2)) $txlocaltax2=0;

        $localtaxes_type=getLocalTaxesFromRate($txtva, 0, $mysoc, $this->thirdparty);

        // Clean vat code
        $vat_src_code='';
        if (preg_match('/\((.*)\)/', $txtva, $reg))
        {
            $vat_src_code = $reg[1];
            $txtva = preg_replace('/\s*\(.*\)/', '', $txtva);    // Remove code into vatrate.
        }

        $remise_percent=price2num($remise_percent);
        $qty=price2num($qty);
        $pu=price2num($pu);
        $txtva=price2num($txtva);
        $txlocaltax1=price2num($txlocaltax1);
        $txlocaltax2=price2num($txlocaltax2);

        if ($conf->multicurrency->enabled && $pu_ht_devise > 0) {
            $pu = 0;
        }

        $tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $this->thirdparty, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);
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

        // Check parameters
        if ($type < 0) return -1;

        // Insert line
        $this->line=new SupplierInvoiceLine($this->db);

        $this->line->context = $this->context;

        $this->line->fk_facture_fourn=$this->id;
        //$this->line->label=$label;	// deprecated
        $this->line->desc=$desc;
        $this->line->qty=            ($this->type==self::TYPE_CREDIT_NOTE?abs($qty):$qty);	// For credit note, quantity is always positive and unit price negative
		$this->line->ref_supplier=$ref_supplier;

        $this->line->vat_src_code=$vat_src_code;
        $this->line->tva_tx=$txtva;
        $this->line->localtax1_tx=($total_localtax1?$localtaxes_type[1]:0);
        $this->line->localtax2_tx=($total_localtax2?$localtaxes_type[3]:0);
        $this->line->localtax1_type = $localtaxes_type[0];
        $this->line->localtax2_type = $localtaxes_type[2];
        $this->line->fk_product=$fk_product;
        $this->line->product_type=$type;
        $this->line->remise_percent=$remise_percent;
        $this->line->subprice=       ($this->type==self::TYPE_CREDIT_NOTE?-abs($pu_ht):$pu_ht); // For credit note, unit price always negative, always positive otherwise
        $this->line->date_start=$date_start;
        $this->line->date_end=$date_end;
        $this->line->ventil=$ventil;
        $this->line->rang=$rang;
        $this->line->info_bits=$info_bits;
        $this->line->total_ht=       (($this->type==self::TYPE_CREDIT_NOTE||$qty<0)?-abs($total_ht):$total_ht);  // For credit note and if qty is negative, total is negative
        $this->line->total_tva=      $total_tva;
        $this->line->total_localtax1=$total_localtax1;
        $this->line->total_localtax2=$total_localtax2;
        $this->line->total_ttc=      (($this->type==self::TYPE_CREDIT_NOTE||$qty<0)?-abs($total_ttc):$total_ttc);
        $this->line->special_code=$this->special_code;
        $this->line->fk_parent_line=$this->fk_parent_line;
        $this->line->origin=$this->origin;
        $this->line->origin_id=$origin_id;
        $this->line->fk_unit=$fk_unit;

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

        $result=$this->line->insert($notrigger);
        if ($result > 0)
        {
            // Reorder if child line
            if (! empty($fk_parent_line)) $this->line_order(true,'DESC');

            // Mise a jour informations denormalisees au niveau de la facture meme
            $result=$this->update_price(1,'auto',0,$this->thirdparty);	// The addline method is designed to add line from user input so total calculation with update_price must be done using 'auto' mode.
            if ($result > 0)
            {
                $this->db->commit();
                return $this->line->id;
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
            $this->errors=$this->line->errors;
            $this->db->rollback();
            return -2;
        }
    }

    /**
     * Update a line detail into database
     *
     * @param     	int			$id            		Id of line invoice
     * @param     	string		$desc         		Description of line
     * @param     	double		$pu          		Prix unitaire (HT ou TTC selon price_base_type)
     * @param     	double		$vatrate       		VAT Rate (Can be '8.5', '8.5 (ABC)')
     * @param		double		$txlocaltax1		LocalTax1 Rate
     * @param		double		$txlocaltax2		LocalTax2 Rate
     * @param     	double		$qty           		Quantity
     * @param     	int			$idproduct			Id produit
     * @param	  	double		$price_base_type	HT or TTC
     * @param	  	int			$info_bits			Miscellaneous informations of line
     * @param		int			$type				Type of line (0=product, 1=service)
     * @param     	double		$remise_percent  	Pourcentage de remise de la ligne
     * @param		int			$notrigger			Disable triggers
     * @param      	timestamp	$date_start     	Date start of service
     * @param      	timestamp   $date_end       	Date end of service
	 * @param		array		$array_options		extrafields array
     * @param 		string		$fk_unit 			Code of the unit to use. Null to use the default one
	 * @param		double		$pu_ht_devise		Amount in currency
     * @return    	int           					<0 if KO, >0 if OK
     */
    public function updateline($id, $desc, $pu, $vatrate, $txlocaltax1=0, $txlocaltax2=0, $qty=1, $idproduct=0, $price_base_type='HT', $info_bits=0, $type=0, $remise_percent=0, $notrigger=false, $date_start='', $date_end='', $array_options=0, $fk_unit = null, $pu_ht_devise=0)
    {
    	global $mysoc;
        dol_syslog(get_class($this)."::updateline $id,$desc,$pu,$vatrate,$qty,$idproduct,$price_base_type,$info_bits,$type,$remise_percent,$fk_unit", LOG_DEBUG);
        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

        $pu = price2num($pu);
        $qty  = price2num($qty);
		$remise_percent=price2num($remise_percent);
		$pu_ht_devise = price2num($pu_ht_devise);

        // Check parameters
        //if (! is_numeric($pu) || ! is_numeric($qty)) return -1;
        if ($type < 0) return -1;

        // Clean parameters
		if (empty($vatrate)) $vatrate=0;
        if (empty($txlocaltax1)) $txlocaltax1=0;
        if (empty($txlocaltax2)) $txlocaltax2=0;

        $txlocaltax1=price2num($txlocaltax1);
        $txlocaltax2=price2num($txlocaltax2);

        $localtaxes_type = array($txlocaltax1,$txlocaltax2);

        // Calcul du total TTC et de la TVA pour la ligne a partir de
        // qty, pu, remise_percent et txtva
        // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
        // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

        $localtaxes_type=getLocalTaxesFromRate($vatrate,0,$mysoc, $this->thirdparty);

        // Clean vat code
        $vat_src_code='';
        if (preg_match('/\((.*)\)/', $vatrate, $reg))
        {
            $vat_src_code = $reg[1];
            $vatrate = preg_replace('/\s*\(.*\)/', '', $vatrate);    // Remove code into vatrate.
        }

        $tabprice = calcul_price_total($qty, $pu, $remise_percent, $vatrate, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $this->thirdparty, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);
        $total_ht  = $tabprice[0];
        $total_tva = $tabprice[1];
        $total_ttc = $tabprice[2];
        $pu_ht  = $tabprice[3];
        $pu_tva = $tabprice[4];
        $pu_ttc = $tabprice[5];
        $total_localtax1 = $tabprice[9];
        $total_localtax2 = $tabprice[10];

		// MultiCurrency
		$multicurrency_total_ht  = $tabprice[16];
        $multicurrency_total_tva = $tabprice[17];
        $multicurrency_total_ttc = $tabprice[18];
		$pu_ht_devise = $tabprice[19];

        if (empty($info_bits)) $info_bits=0;

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

	    $line = new SupplierInvoiceLine($this->db);

	    if ($line->fetch($id) < 1) {
		    return -1;
	    }

	    $line->description = $desc;
	    $line->subprice = $pu_ht;
	    $line->pu_ht = $pu_ht;
	    $line->pu_ttc = $pu_ttc;
	    $line->qty = $qty;
	    $line->remise_percent = $remise_percent;

	    $line->vat_src_code=$vat_src_code;
	    $line->tva_tx = $vatrate;
	    $line->localtax1_tx = $txlocaltax1;
	    $line->localtax2_tx = $txlocaltax2;
		$line->localtax1_type = $localtaxes_type[0];
		$line->localtax2_type = $localtaxes_type[2];
	    $line->total_ht = $total_ht;
	    $line->total_tva = $total_tva;
	    $line->total_localtax1 = $total_localtax1;
	    $line->total_localtax2 = $total_localtax2;
	    $line->total_ttc = $total_ttc;
	    $line->fk_product = $idproduct;
	    $line->product_type = $product_type;
	    $line->info_bits = $info_bits;
	    $line->fk_unit = $fk_unit;
	    $line->array_options = $array_options;

		// Multicurrency
		$line->multicurrency_subprice	= $pu_ht_devise;
		$line->multicurrency_total_ht 	= $multicurrency_total_ht;
        $line->multicurrency_total_tva 	= $multicurrency_total_tva;
        $line->multicurrency_total_ttc 	= $multicurrency_total_ttc;

	    $res = $line->update($notrigger);

	    if ($res < 1) {
		    $this->errors[] = $line->error;
	    } else {
		    // Update total price into invoice record
		    $res = $this->update_price('','auto');
	    }

	    return $res;
    }

    /**
     * 	Delete a detail line from database
     *
     * 	@param  int		$rowid      	Id of line to delete
     *	@param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
     * 	@return	int						<0 if KO, >0 if OK
     */
    public function deleteline($rowid, $notrigger=0)
    {
        if (!$rowid) {
	        $rowid = $this->id;
        }

		$this->db->begin();

		// Libere remise liee a ligne de facture
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'societe_remise_except';
		$sql .= ' SET fk_invoice_supplier_line = NULL';
		$sql .= ' WHERE fk_invoice_supplier_line = ' . $rowid;

		dol_syslog(get_class($this) . "::deleteline", LOG_DEBUG);
		$result = $this->db->query($sql);
		if (! $result)
		{
			$this->error = $this->db->error();
			$this->db->rollback();
			return - 2;
		}

	    $line = new SupplierInvoiceLine($this->db);

	    if ($line->fetch($rowid) < 1) {
		    return -1;
	    }

	    $res = $line->delete($notrigger);

	    if ($res < 1) {
			$this->errors[] = $line->error;
			$this->db->rollback();
			return - 3;
	    } else {
			$res = $this->update_price();

			if ($res > 0)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				return - 4;
			}
	    }
    }


    /**
     *	Charge les informations d'ordre info dans l'objet facture
     *
     *	@param  int		$id       	Id de la facture a charger
     *	@return	void
     */
    public function info($id)
    {
        $sql = 'SELECT c.rowid, datec, tms as datem, ';
        $sql.= ' fk_user_author, fk_user_modif, fk_user_valid';
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
                if ($obj->fk_user_modif)
                {
                    $muser = new User($this->db);
                    $muser->fetch($obj->fk_user_modif);
                    $this->user_modification = $muser;
                }
                $this->date_creation     = $this->db->idate($obj->datec);
                $this->date_modification = $this->db->idate($obj->datem);
                //$this->date_validation   = $obj->datev; // This field is not available. Should be store into log table and using this function should be replaced with showing content of log (like for supplier orders)
            }
            $this->db->free($result);
        }
        else
        {
            dol_print_error($this->db);
        }
    }

	/**
	 *	Renvoi liste des factures remplacables
	 *	Statut validee ou abandonnee pour raison autre + non payee + aucun paiement + pas deja remplacee
	 *
	 *	@param		int		$socid		Id societe
	 *	@return    	array				Tableau des factures ('id'=>id, 'ref'=>ref, 'status'=>status, 'paymentornot'=>0/1)
	 */
	function list_replacable_supplier_invoices($socid=0)
	{
		global $conf;

		$return = array();

		$sql = "SELECT f.rowid as rowid, f.ref, f.fk_statut,";
		$sql.= " ff.rowid as rowidnext";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf ON f.rowid = pf.fk_facturefourn";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as ff ON f.rowid = ff.fk_facture_source";
		$sql.= " WHERE (f.fk_statut = ".self::STATUS_VALIDATED." OR (f.fk_statut = ".self::STATUS_ABANDONED." AND f.close_code = '".self::CLOSECODE_ABANDONED."'))";
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND f.paye = 0";					// Pas classee payee completement
		$sql.= " AND pf.fk_paiementfourn IS NULL";	// Aucun paiement deja fait
		$sql.= " AND ff.fk_statut IS NULL";			// Renvoi vrai si pas facture de remplacement
		if ($socid > 0) $sql.=" AND f.fk_soc = ".$socid;
		$sql.= " ORDER BY f.ref";

		dol_syslog(get_class($this)."::list_replacable_supplier_invoices", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$return[$obj->rowid]=array(	'id' => $obj->rowid,
				'ref' => $obj->ref,
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
	function list_qualified_avoir_supplier_invoices($socid=0)
	{
		global $conf;

		$return = array();

		$sql = "SELECT f.rowid as rowid, f.ref, f.fk_statut, f.type, f.paye, pf.fk_paiementfourn";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf ON f.rowid = pf.fk_facturefourn";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as ff ON (f.rowid = ff.fk_facture_source AND ff.type=".self::TYPE_REPLACEMENT.")";
		$sql.= " WHERE f.entity = ".$conf->entity;
		$sql.= " AND f.fk_statut in (".self::STATUS_VALIDATED.",".self::STATUS_CLOSED.")";
		$sql.= " AND ff.type IS NULL";									// Renvoi vrai si pas facture de remplacement
		$sql.= " AND f.type != ".self::TYPE_CREDIT_NOTE;				// Type non 2 si facture non avoir
		if ($socid > 0) $sql.=" AND f.fk_soc = ".$socid;
		$sql.= " ORDER BY f.ref";

		dol_syslog(get_class($this)."::list_qualified_avoir_supplier_invoices", LOG_DEBUG);
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
					$paymentornot=($obj->fk_paiementfourn?1:0);
					$return[$obj->rowid]=array('ref'=>$obj->ref,'status'=>$obj->fk_statut,'type'=>$obj->type,'paye'=>$obj->paye,'paymentornot'=>$paymentornot);
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
     *	Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *
     *	@param      User	$user       Object user
     *	@return WorkboardResponse|int <0 if KO, WorkboardResponse if OK
     */
    function load_board($user)
    {
        global $conf, $langs;

        $sql = 'SELECT ff.rowid, ff.date_lim_reglement as datefin, ff.fk_statut';
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
	        $langs->load("bills");
	        $now=dol_now();

	        $response = new WorkboardResponse();
	        $response->warning_delay=$conf->facture->fournisseur->warning_delay/60/60/24;
	        $response->label=$langs->trans("SupplierBillsToPay");

	        $response->url=DOL_URL_ROOT.'/fourn/facture/list.php?search_status=1&mainmenu=accountancy&leftmenu=suppliers_bills';
	        $response->img=img_object($langs->trans("Bills"),"bill");

            $facturestatic = new FactureFournisseur($this->db);

            while ($obj=$this->db->fetch_object($resql))
            {
                $response->nbtodo++;

                $facturestatic->date_echeance = $this->db->jdate($obj->datefin);
                $facturestatic->statut = $obj->fk_statut;

                if ($facturestatic->hasDelay()) {
	                $response->nbtodolate++;
                }
            }
            $this->db->free($resql);
            return $response;
        }
        else
        {
            dol_print_error($this->db);
            $this->error=$this->db->error();
            return -1;
        }
    }


    /**
     *	Return clicable name (with picto eventually)
     *
     *	@param		int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
     *	@param		string	$option						Where point the link
     *	@param		int		$max						Max length of shown ref
     *	@param		int		$short						1=Return just URL
     *	@param		string	$moretitle					Add more text to title tooltip
     *  @param	    int   	$notooltip					1=Disable tooltip
     *  @param      int     $save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     * 	@return		string								String with URL
     */
    public function getNomUrl($withpicto=0, $option='',$max=0, $short=0, $moretitle='', $notooltip=0, $save_lastsearch_value=-1)
    {
        global $langs, $conf;

        $result='';

        if ($option == 'document')	$url = DOL_URL_ROOT.'/fourn/facture/document.php?facid='.$this->id;
        else $url = DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$this->id;

        if ($short) return $url;

        if ($option !== 'nolink')
        {
        	// Add param to save lastsearch_values or not
        	$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
        	if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
        	if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
        }

        $picto='bill';
        if ($this->type == self::TYPE_REPLACEMENT) $picto.='r'; // Replacement invoice
        if ($this->type == self::TYPE_CREDIT_NOTE) $picto.='a'; // Credit note
        if ($this->type == self::TYPE_DEPOSIT)     $picto.='d'; // Deposit invoice

        $label = '<u>' . $langs->trans("ShowSupplierInvoice") . '</u>';
        if (! empty($this->ref))
            $label .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
        if (! empty($this->ref_supplier))
            $label.= '<br><b>' . $langs->trans('RefSupplier') . ':</b> ' . $this->ref_supplier;
        if (! empty($this->libelle))
        	$label.= '<br><b>' . $langs->trans('Label') . ':</b> ' . $this->libelle;
        if (! empty($this->total_ht))
            $label.= '<br><b>' . $langs->trans('AmountHT') . ':</b> ' . price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
        if (! empty($this->total_tva))
            $label.= '<br><b>' . $langs->trans('VAT') . ':</b> ' . price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
        if (! empty($this->total_ttc))
            $label.= '<br><b>' . $langs->trans('AmountTTC') . ':</b> ' . price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
        if ($this->type == self::TYPE_REPLACEMENT) $label=$langs->transnoentitiesnoconv("ShowInvoiceReplace").': '.$this->ref;
        if ($this->type == self::TYPE_CREDIT_NOTE) $label=$langs->transnoentitiesnoconv("ShowInvoiceAvoir").': '.$this->ref;
        if ($this->type == self::TYPE_DEPOSIT)     $label=$langs->transnoentitiesnoconv("ShowInvoiceDeposit").': '.$this->ref;
        if ($moretitle) $label.=' - '.$moretitle;

        $ref=$this->ref;
        if (empty($ref)) $ref=$this->id;

        $linkclose='';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("ShowSupplierInvoice");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip"';
        }

        $linkstart = '<a href="'.$url.'"';
        $linkstart.=$linkclose.'>';
        $linkend='</a>';

        $result .= $linkstart;
        if ($withpicto) $result.=img_object(($notooltip?'':$label), $this->picto, ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
        if ($withpicto != 2) $result.= ($max?dol_trunc($ref,$max):$ref);
        $result .= $linkend;

        return $result;
    }

	 /**
      *      Return next reference of supplier invoice not already used (or last reference)
      *      according to numbering module defined into constant INVOICE_SUPPLIER_ADDON_NUMBER
      *
      *      @param	   Societe		$soc		Thirdparty object
      *      @param    string		$mode		'next' for next value or 'last' for last value
      *      @return   string					free ref or last ref
      */
    public function getNextNumRef($soc,$mode='next')
    {
        global $db, $langs, $conf;
        $langs->load("orders");

        // Clean parameters (if not defined or using deprecated value)
        if (empty($conf->global->INVOICE_SUPPLIER_ADDON_NUMBER)) $conf->global->INVOICE_SUPPLIER_ADDON_NUMBER='mod_facture_fournisseur_cactus';

        $mybool=false;

        $file = $conf->global->INVOICE_SUPPLIER_ADDON_NUMBER.".php";
        $classname = $conf->global->INVOICE_SUPPLIER_ADDON_NUMBER;

        // Include file with class
        $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

        foreach ($dirmodels as $reldir) {

            $dir = dol_buildpath($reldir."core/modules/supplier_invoice/");

            // Load file with numbering class (if found)
            $mybool|=@include_once $dir.$file;
        }

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
       		$this->error=$obj->error;
        	//dol_print_error($db,get_class($this)."::getNextNumRef ".$obj->error);
        	return false;
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
    public function initAsSpecimen($option='')
    {
        global $langs,$conf;
		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

        $now = dol_now();

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

        // Initialise parametres
        $this->id=0;
        $this->ref = 'SPECIMEN';
        $this->ref_supplier = 'SUPPLIER_REF_SPECIMEN';
        $this->specimen=1;
        $this->socid = 1;
        $this->date = $now;
        $this->date_lim_reglement=$this->date+3600*24*30;
        $this->cond_reglement_code = 'RECEP';
        $this->mode_reglement_code = 'CHQ';
        $this->note_public='This is a comment (public)';
        $this->note_private='This is a comment (private)';

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
	            $line->pu_ht=100;		// the canelle template use pu_ht and not subprice
	            $line->price=100;
	            $line->tva_tx=19.6;
	            $line->localtax1_tx=0;
	            $line->localtax2_tx=0;
				if ($xnbp == 2)
				{
				    $line->total_ht=50;
				    $line->total_ttc=59.8;
				    $line->total_tva=9.8;
	    			$line->remise_percent=50;
				}
				else
				{
				    $line->total_ht=100;
				    $line->total_ttc=119.6;
				    $line->total_tva=19.6;
	    			$line->remise_percent=0;
				}

	            if ($num_prods > 0)
	            {
					$prodid = mt_rand(1, $num_prods);
	            	$line->fk_product=$prodids[$prodid];
	            }
	            $line->product_type=0;

	            $this->lines[$xnbp]=$line;

	    		$this->total_ht       += $line->total_ht;
	    		$this->total_tva      += $line->total_tva;
	    		$this->total_ttc      += $line->total_ttc;

	    		$xnbp++;
	        }
		}

        $this->amount_ht      = $xnbp*100;
        $this->total_ht       = $xnbp*100;
        $this->total_tva      = $xnbp*19.6;
        $this->total_ttc      = $xnbp*119.6;
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
		$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
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
				$this->nb["supplier_invoices"]=$obj->nb;
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
     *	Load an object from its id and create a new one in database
     *
     *	@param      int		$fromid     	Id of object to clone
     *	@param		int		$invertdetail	Reverse sign of amounts for lines
     * 	@return		int						New id of clone
     */
    public function createFromClone($fromid,$invertdetail=0)
    {
        global $user,$langs;

        $error=0;

        $object=new FactureFournisseur($this->db);

		$object->context['createfromclone'] = 'createfromclone';

		$this->db->begin();

        // Load source object
        $object->fetch($fromid);
        $object->id=0;
        $object->statut=self::STATUS_DRAFT;

        // Clear fields
        $object->ref_supplier=$langs->trans("CopyOf").' '.$object->ref_supplier;
        $object->author             = $user->id;
        $object->user_valid         = '';
        $object->fk_facture_source  = 0;
        $object->date_creation      = '';
        $object->date_validation    = '';
        $object->date               = '';
        $object->date_echeance      = '';
        $object->ref_client         = '';
        $object->close_code         = '';
        $object->close_note         = '';

        // Loop on each line of new invoice
        foreach($object->lines as $i => $line)
        {
            if (isset($object->lines[$i]->info_bits) && ($object->lines[$i]->info_bits & 0x02) == 0x02)	// We do not clone line of discounts
            {
                unset($object->lines[$i]);
            }
        }

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

        unset($object->context['createfromclone']);

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

	/**
	 *	Create a document onto disk according to template model.
	 *
	 *	@param	    string		$modele			Force template to use ('' to not force)
	 *	@param		Translate	$outputlangs	Object lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @return     int         				<0 if KO, 0 if nothing done, >0 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
	{
		global $conf, $user, $langs;

		$langs->load("suppliers");

		// Set the model on the model name to use
		if (empty($modele))
		{
			if (! empty($conf->global->INVOICE_SUPPLIER_ADDON_PDF))
			{
				$modele = $conf->global->INVOICE_SUPPLIER_ADDON_PDF;
			}
			else
			{
				$modele = '';       // No default value. For supplier invoice, we allow to disable all PDF generation
			}
		}

		if (empty($modele))
		{
		    return 0;
		}
		else
		{
            $modelpath = "core/modules/supplier_invoice/pdf/";

            return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}
	}

	/**
	 * Returns the rights used for this class
	 * @return stdClass
	 */
	public function getRights()
	{
		global $user;

		return $user->rights->fournisseur->facture;
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
			'facture_fourn'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}

    /**
     * Is the payment of the supplier invoice having a delay?
     *
     * @return bool
     */
    public function hasDelay()
    {
        global $conf;

        $now = dol_now();

        if (!$this->date_echeance) {
            return false;
        }

        return ($this->statut == self::STATUS_VALIDATED) && ($this->date_echeance < ($now - $conf->facture->fournisseur->warning_delay));
    }
}



/**
 *  Class to manage line invoices
 */
class SupplierInvoiceLine extends CommonObjectLine
{
	public $element='facture_fourn_det';
	public $table_element='facture_fourn_det';

	public $oldline;

	/**
	 * @deprecated
	 * @see product_ref
	 */
	public $ref;
	/**
	 * Internal ref
	 * @var string
	 */
	public $product_ref;

	/**
	 * Supplier reference of price when we added the line. May have been changed after line was added.
	 * TODO Rename field ref to ref_supplier into table llx_facture_fourn_det and llx_commande_fournisseurdet and update fields into updateline
	 * @var string
	 */
	public $ref_supplier;

	/**
	 * @deprecated
	 * @see label
	 */
	public $libelle;
	/**
	 * Product description
	 * @var string
	 */
	public $product_desc;

	/**
	 * Unit price before taxes
	 * @var float
	 * @deprecated Use $subprice
	 * @see subprice
	 */
	public $pu_ht;
	public $subprice;

	/**
	 * Unit price included taxes
	 * @var float
	 */
	public $pu_ttc;

	/**
	 * Total VAT amount
	 * @var float
	 * @deprecated Use $total_tva instead
	 * @see total_tva
	 */
	public $tva;
	public $total_tva;

	/**
	 * Id of the corresponding supplier invoice
	 * @var int
	 */
	public $fk_facture_fourn;

	/**
	 * Product label
	 * This field may contains label of product (when invoice create from order)
	 * @var string
	 */
	public $label;

	/**
	 * Description of the line
	 * @var string
	 */
	public $description;

	public $skip_update_total; // Skip update price total for special lines

	/**
	 * @var int Situation advance percentage
	 */
	public $situation_percent;

	/**
	 * @var int Previous situation line id reference
	 */
	public $fk_prev_id;

	public $tva_tx;
	public $localtax1_tx;
	public $localtax2_tx;
	public $qty;
	public $remise_percent;
	public $total_ht;
	public $total_ttc;
	public $total_localtax1;
	public $total_localtax2;
	public $fk_product;
	public $product_type;
	public $product_label;
	public $info_bits;
	public $fk_parent_line;
	public $special_code;
	public $rang;
	public $localtax1_type;
	public $localtax2_type;

	// Multicurrency
	public $fk_multicurrency;
	public $multicurrency_code;
	public $multicurrency_subprice;
	public $multicurrency_total_ht;
	public $multicurrency_total_tva;
	public $multicurrency_total_ttc;

	/**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db= $db;
    }

	/**
	 * Retrieves a supplier invoice line
	 *
	 * @param    int    $rowid    Line id
	 * @return   int              <0 KO; 0 NOT FOUND; 1 OK
	 */
	public function fetch($rowid)
	{
		$sql = 'SELECT f.rowid, f.ref as ref_supplier, f.description, f.pu_ht, f.pu_ttc, f.qty, f.remise_percent, f.tva_tx';
		$sql.= ', f.localtax1_type, f.localtax2_type, f.localtax1_tx, f.localtax2_tx, f.total_localtax1, f.total_localtax2 ';
		$sql.= ', f.total_ht, f.tva as total_tva, f.total_ttc, f.fk_facture_fourn, f.fk_product, f.product_type, f.info_bits, f.rang, f.special_code, f.fk_parent_line, f.fk_unit';
		$sql.= ', p.rowid as product_id, p.ref as product_ref, p.label as label, p.description as product_desc';
		$sql.= ', f.multicurrency_subprice, f.multicurrency_total_ht, f.multicurrency_total_tva, multicurrency_total_ttc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn_det as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON f.fk_product = p.rowid';
		$sql.= ' WHERE f.rowid = '.$rowid;
		$sql.= ' ORDER BY f.rang, f.rowid';

		$query = $this->db->query($sql);

		if (!$query) {
			$this->errors[] = $this->db->error();
			return -1;
		}

		if (!$this->db->num_rows($query)) {
			return 0;
		}

		$obj = $this->db->fetch_object($query);

		$this->id				= $obj->rowid;
		$this->rowid				= $obj->rowid;
		$this->fk_facture_fourn			= $obj->fk_facture_fourn;
		$this->description		= $obj->description;
		$this->product_ref		= $obj->product_ref;
		$this->ref				= $obj->product_ref;
		$this->ref_supplier		= $obj->ref_supplier;
		$this->libelle			= $obj->label;
		$this->label  			= $obj->label;
		$this->product_desc		= $obj->product_desc;
		$this->subprice			= $obj->pu_ht;
		$this->pu_ht				= $obj->pu_ht;
		$this->pu_ttc			= $obj->pu_ttc;
		$this->tva_tx			= $obj->tva_tx;
		$this->localtax1_tx		= $obj->localtax1_tx;
		$this->localtax2_tx		= $obj->localtax2_tx;
		$this->localtax1_type		= $obj->localtax1_type;
		$this->localtax2_type		= $obj->localtax2_type;
		$this->qty				= $obj->qty;
		$this->remise_percent    = $obj->remise_percent;
		$this->tva				= $obj->total_tva;
		$this->total_ht			= $obj->total_ht;
		$this->total_tva			= $obj->total_tva;
		$this->total_localtax1	= $obj->total_localtax1;
		$this->total_localtax2	= $obj->total_localtax2;
		$this->total_ttc			= $obj->total_ttc;
		$this->fk_product		= $obj->fk_product;
		$this->product_type		= $obj->product_type;
		$this->product_label		= $obj->label;
		$this->info_bits		    = $obj->info_bits;
		$this->tva_npr              = ($obj->info_bits & 1 == 1) ? 1 : 0;
		$this->fk_parent_line    = $obj->fk_parent_line;
		$this->special_code		= $obj->special_code;
		$this->rang       		= $obj->rang;
		$this->fk_unit           = $obj->fk_unit;

		$this->multicurrency_subprice	= $obj->multicurrency_subprice;
		$this->multicurrency_total_ht	= $obj->multicurrency_total_ht;
		$this->multicurrency_total_tva	= $obj->multicurrency_total_tva;
		$this->multicurrency_total_ttc	= $obj->multicurrency_total_ttc;

		return 1;
	}

	/**
	 * Deletes a line
	 *
	 * @param     bool|int   $notrigger     1=Does not execute triggers, 0= execute triggers
	 * @return    int                       0 if KO, 1 if OK
	 */
	public function delete($notrigger = 0)
	{
		dol_syslog(get_class($this)."::deleteline rowid=".$this->id, LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		if (!$notrigger) {
			if ($this->call_trigger('LINEBILL_SUPPLIER_DELETE',$user) < 0) {
				$error++;
			}
		}

		if (!$error) {
			// Supprime ligne
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn_det ';
			$sql .= ' WHERE rowid = '.$this->id;
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->error = $this->db->lasterror();
			}
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
	 * Update a supplier invoice line
	 *
	 * @param int $notrigger Disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function update($notrigger = 0)
	{
		global $conf;

		$pu = price2num($this->pu_ht);
		$qty  = price2num($this->qty);

		// Check parameters
		if (empty($this->qty)) $this->qty=0;

		if ($this->product_type < 0) {
			return -1;
		}

		// Clean parameters
		if (empty($this->remise_percent)) $this->remise_percent = 0;
		if (empty($this->tva_tx))  		  $this->tva_tx = 0;
		if (empty($this->localtax1_tx))   $this->localtax1_tx = 0;
		if (empty($this->localtax2_tx))   $this->localtax2_tx = 0;

		$this->db->begin();

		if (empty($this->fk_product))
		{
			$fk_product = "null";
		} else {
			$fk_product = $this->fk_product;
		}

		if (empty($this->fk_unit)) {
			$fk_unit = "null";
		} else {
		    $fk_unit = "'".$this->db->escape($this->fk_unit)."'";
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn_det SET";
		$sql.= "  description ='".$this->db->escape($this->description)."'";
		$sql.= ", ref ='".$this->db->escape($this->ref)."'";
		$sql.= ", pu_ht = ".price2num($this->pu_ht);
		$sql.= ", pu_ttc = ".price2num($this->pu_ttc);
		$sql.= ", qty = ".price2num($this->qty);
		$sql.= ", remise_percent = ".price2num($this->remise_percent);
		$sql.= ", vat_src_code = '".(empty($this->vat_src_code)?'':$this->vat_src_code)."'";
		$sql.= ", tva_tx = ".price2num($this->tva_tx);
		$sql.= ", localtax1_tx = ".price2num($this->localtax1_tx);
		$sql.= ", localtax2_tx = ".price2num($this->localtax2_tx);
		$sql.= ", localtax1_type = '".$this->db->escape($this->localtax1_type)."'";
		$sql.= ", localtax2_type = '".$this->db->escape($this->localtax2_type)."'";
		$sql.= ", total_ht = ".price2num($this->total_ht);
		$sql.= ", tva= ".price2num($this->total_tva);
		$sql.= ", total_localtax1= ".price2num($this->total_localtax1);
		$sql.= ", total_localtax2= ".price2num($this->total_localtax2);
		$sql.= ", total_ttc = ".price2num($this->total_ttc);
		$sql.= ", fk_product = ".$fk_product;
		$sql.= ", product_type = ".$this->product_type;
		$sql.= ", info_bits = ".$this->info_bits;
		$sql.= ", fk_unit = ".$fk_unit;

		// Multicurrency
		$sql.= " , multicurrency_subprice=".price2num($this->multicurrency_subprice)."";
        $sql.= " , multicurrency_total_ht=".price2num($this->multicurrency_total_ht)."";
        $sql.= " , multicurrency_total_tva=".price2num($this->multicurrency_total_tva)."";
        $sql.= " , multicurrency_total_ttc=".price2num($this->multicurrency_total_ttc)."";

		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return -1;
		}

		$this->rowid = $this->id;
		$error = 0;

		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
		{
			if ($this->insertExtraFields() < 0) {
				$error++;
			}
		}

		if (! $error && ! $notrigger)
		{
			global $langs, $user;

			// Call trigger
			if ($this->call_trigger('LINEBILL_SUPPLIER_UPDATE',$user) < 0) {
				$this->db->rollback();
				return -1;
			}
			// End call triggers
		}

		if ($error) {
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return 1;
	}

    /**
     *	Insert line into database
     *
     *	@param      int		$notrigger		1 no triggers
     *	@return		int						<0 if KO, >0 if OK
     */
    public function insert($notrigger=0)
    {
        global $user,$conf;

        $error=0;

        dol_syslog(get_class($this)."::insert rang=".$this->rang, LOG_DEBUG);

        // Clean parameters
        $this->desc=trim($this->desc);
        if (empty($this->tva_tx)) $this->tva_tx=0;
        if (empty($this->localtax1_tx)) $this->localtax1_tx=0;
        if (empty($this->localtax2_tx)) $this->localtax2_tx=0;
        if (empty($this->localtax1_type)) $this->localtax1_type='0';
        if (empty($this->localtax2_type)) $this->localtax2_type='0';
        if (empty($this->total_localtax1)) $this->total_localtax1=0;
        if (empty($this->total_localtax2)) $this->total_localtax2=0;
        if (empty($this->rang)) $this->rang=0;
        if (empty($this->remise_percent)) $this->remise_percent=0;
        if (empty($this->info_bits)) $this->info_bits=0;
        if (empty($this->subprice)) $this->subprice=0;
        if (empty($this->special_code)) $this->special_code=0;
        if (empty($this->fk_parent_line)) $this->fk_parent_line=0;
        if (! isset($this->situation_percent) || $this->situation_percent > 100 || (string) $this->situation_percent == '') $this->situation_percent = 100;

        if (empty($this->pa_ht)) $this->pa_ht=0;
        if (empty($this->multicurrency_subprice)) $this->multicurrency_subprice=0;
        if (empty($this->multicurrency_total_ht)) $this->multicurrency_total_ht=0;
        if (empty($this->multicurrency_total_tva)) $this->multicurrency_total_tva=0;
        if (empty($this->multicurrency_total_ttc)) $this->multicurrency_total_ttc=0;


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
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element;
        $sql.= ' (fk_facture_fourn, fk_parent_line, label, description, ref, qty,';
        $sql.= ' vat_src_code, tva_tx, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type,';
        $sql.= ' fk_product, product_type, remise_percent, pu_ht, pu_ttc,';
        $sql.= ' date_start, date_end, fk_code_ventilation, rang, special_code,';
        $sql.= ' info_bits, total_ht, tva, total_ttc, total_localtax1, total_localtax2, fk_unit';
        $sql.= ', fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc';
        $sql.= ')';
        $sql.= " VALUES (".$this->fk_facture_fourn.",";
        $sql.= " ".($this->fk_parent_line>0?"'".$this->db->escape($this->fk_parent_line)."'":"null").",";
        $sql.= " ".(! empty($this->label)?"'".$this->db->escape($this->label)."'":"null").",";
        $sql.= " '".$this->db->escape($this->desc ? $this->desc : $this->description)."',";
        $sql.= " '".$this->db->escape($this->ref_supplier)."',";
        $sql.= " ".price2num($this->qty).",";

        $sql.= " ".(empty($this->vat_src_code)?"''":"'".$this->db->escape($this->vat_src_code)."'").",";
        $sql.= " ".price2num($this->tva_tx).",";
        $sql.= " ".price2num($this->localtax1_tx).",";
        $sql.= " ".price2num($this->localtax2_tx).",";
        $sql.= " '".$this->db->escape($this->localtax1_type)."',";
        $sql.= " '".$this->db->escape($this->localtax2_type)."',";
        $sql.= ' '.(! empty($this->fk_product)?$this->fk_product:"null").',';
        $sql.= " ".$this->product_type.",";
        $sql.= " ".price2num($this->remise_percent).",";
        $sql.= " ".price2num($this->subprice).",";
        $sql.= " ".price2num($this->total_ttc/$this->qty).",";
        $sql.= " ".(! empty($this->date_start)?"'".$this->db->idate($this->date_start)."'":"null").",";
        $sql.= " ".(! empty($this->date_end)?"'".$this->db->idate($this->date_end)."'":"null").",";
        $sql.= ' '.(!empty($this->fk_code_ventilation)?$this->fk_code_ventilation:0).',';
        $sql.= ' '.$this->rang.',';
        $sql.= ' '.$this->special_code.',';
        $sql.= " '".$this->db->escape($this->info_bits)."',";
        $sql.= " ".price2num($this->total_ht).",";
        $sql.= " ".price2num($this->total_tva).",";
        $sql.= " ".price2num($this->total_ttc).",";
        $sql.= " ".price2num($this->total_localtax1).",";
        $sql.= " ".price2num($this->total_localtax2);
        $sql .= ", ".(!$this->fk_unit ? 'NULL' : $this->fk_unit);
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
            $this->id=$this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
            $this->rowid=$this->id;

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
                $result=$this->call_trigger('LINEBILL_SUPPLIER_CREATE',$user);
                if ($result < 0)
                {
                    $this->db->rollback();
                    return -2;
                }
                // End call triggers
            }

            $this->db->commit();
            return $this->id;

        }
        else
        {
            $this->error=$this->db->error();
            $this->db->rollback();
            return -2;
        }
    }
            /**
     *  Mise a jour de l'objet ligne de commande en base
     *
     *  @return		int		<0 si ko, >0 si ok
     */
    function update_total()
    {
        $this->db->begin();

        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn_det SET";
        $sql.= "  total_ht='".price2num($this->total_ht)."'";
        $sql.= ", tva='".price2num($this->total_tva)."'";
        $sql.= ", total_localtax1='".price2num($this->total_localtax1)."'";
        $sql.= ", total_localtax2='".price2num($this->total_localtax2)."'";
        $sql.= ", total_ttc='".price2num($this->total_ttc)."'";
        $sql.= " WHERE rowid = ".$this->rowid;

        dol_syslog("FactureFournisseurLigne.class.php::update_total", LOG_DEBUG);

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
 }
