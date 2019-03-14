<?php
/* Copyright (C) 2003-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2007		Franky Van Liedekerke	<franky.van.liedekerke@telenet.be>
 * Copyright (C) 2010-2014	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2010-2018	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2018       Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018       Ferran Marcet         	<fmarcet@2byte.es>
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
 *	\file       htdocs/fourn/class/fournisseur.commande.class.php
 *	\ingroup    fournisseur,commande
 *	\brief      File of class to manage suppliers orders
 */

include_once DOL_DOCUMENT_ROOT.'/core/class/commonorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
if (! empty($conf->productbatch->enabled)) require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';

/**
 *	Class to manage predefined suppliers products
 */
class CommandeFournisseur extends CommonOrder
{
    /**
	 * @var string ID to identify managed object
	 */
	public $element='order_supplier';

    /**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='commande_fournisseur';

    /**
	 * @var int    Name of subtable line
	 */
	public $table_element_line = 'commande_fournisseurdet';

    /**
	 * @var int Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_commande';

    public $picto='order';

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

    /**
	 * @var int ID
	 */
	public $id;

	/**
	 * Supplier order reference
	 * @var string
	 */
    public $ref;

    public $ref_supplier;
    public $brouillon;
    public $statut;			// 0=Draft -> 1=Validated -> 2=Approved -> 3=Ordered/Process runing -> 4=Received partially -> 5=Received totally -> (reopen) 4=Received partially
    //                                                                                          -> 7=Canceled/Never received -> (reopen) 3=Process runing
    //									                            -> 6=Canceled -> (reopen) 2=Approved
    //  		                                      -> 9=Refused  -> (reopen) 1=Validated
    //  Note: billed or not is on another field "billed"
    public $statuts;           // List of status

    public $socid;
    public $fourn_id;
    public $date;
    public $date_valid;
    public $date_approve;
    public $date_approve2;		// Used when SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED is set
    public $date_commande;

    /**
     * Delivery date
     */
    public $date_livraison;

    public $total_ht;
    public $total_tva;
    public $total_localtax1;   // Total Local tax 1
    public $total_localtax2;   // Total Local tax 2
    public $total_ttc;
    public $source;

	/**
	 * @deprecated
	 * @see note_private, note_public
	 */
    public $note;

	public $note_private;
    public $note_public;
    public $model_pdf;

    /**
     * @var int ID
     */
    public $fk_project;

    public $cond_reglement_id;
    public $cond_reglement_code;

    /**
     * @var int ID
     */
    public $fk_account;

    public $mode_reglement_id;
    public $mode_reglement_code;
    public $user_author_id;
    public $user_valid_id;
    public $user_approve_id;
    public $user_approve_id2;	// Used when SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED is set

	//Incoterms
    public $fk_incoterms;
    public $location_incoterms;
    public $libelle_incoterms;  //Used into tooltip

    public $extraparams=array();

	/**
	 * @var CommandeFournisseurLigne[]
	 */
	public $lines = array();

	//Add for supplier_proposal
    public $origin;
    public $origin_id;
    public $linked_objects=array();

	// Multicurrency
	/**
     * @var int ID
     */
    public $fk_multicurrency;

    public $multicurrency_code;
    public $multicurrency_tx;
    public $multicurrency_total_ht;
    public $multicurrency_total_tva;
    public $multicurrency_total_ttc;

	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;

	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;

	/**
	 * Accepted
	 */
	const STATUS_ACCEPTED = 2;

	/**
	 * Order sent, shipment on process
	 */
	const STATUS_ORDERSENT = 3;

	/**
	 * Received partially
	 */
	const STATUS_RECEIVED_PARTIALLY = 4;

	/**
	 * Received completely
	 */
	const STATUS_RECEIVED_COMPLETELY = 5;

	/**
	 * Order canceled
	 */
	const STATUS_CANCELED = 6;

	/**
	 * Order canceled/never received
	 */
	const STATUS_CANCELED_AFTER_ORDER = 7;

	/**
	 * Refused
	 */
	const STATUS_REFUSED = 9;




	/**
     * 	Constructor
     *
     *  @param      DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->products = array();
    }


    /**
     *	Get object and lines from database
     *
     * 	@param	int		$id			Id of order to load
     * 	@param	string	$ref		Ref of object
     *	@return int 		        >0 if OK, <0 if KO, 0 if not found
     */
    public function fetch($id, $ref='')
    {
        global $conf;

        // Check parameters
        if (empty($id) && empty($ref)) return -1;

        $sql = "SELECT c.rowid, c.entity, c.ref, ref_supplier, c.fk_soc, c.fk_statut, c.amount_ht, c.total_ht, c.total_ttc, c.tva as total_vat,";
        $sql.= " c.localtax1, c.localtax2, ";
        $sql.= " c.date_creation, c.date_valid, c.date_approve, c.date_approve2,";
        $sql.= " c.fk_user_author, c.fk_user_valid, c.fk_user_approve, c.fk_user_approve2,";
        $sql.= " c.date_commande as date_commande, c.date_livraison as date_livraison, c.fk_cond_reglement, c.fk_mode_reglement, c.fk_projet as fk_project, c.remise_percent, c.source, c.fk_input_method,";
        $sql.= " c.fk_account,";
        $sql.= " c.note_private, c.note_public, c.model_pdf, c.extraparams, c.billed,";
		$sql.= " c.fk_multicurrency, c.multicurrency_code, c.multicurrency_tx, c.multicurrency_total_ht, c.multicurrency_total_tva, c.multicurrency_total_ttc,";
        $sql.= " cm.libelle as methode_commande,";
        $sql.= " cr.code as cond_reglement_code, cr.libelle as cond_reglement_libelle,";
        $sql.= " p.code as mode_reglement_code, p.libelle as mode_reglement_libelle";
        $sql.= ', c.fk_incoterms, c.location_incoterms';
        $sql.= ', i.libelle as libelle_incoterms';
        $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_payment_term as cr ON c.fk_cond_reglement = cr.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as p ON c.fk_mode_reglement = p.id";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_input_method as cm ON cm.rowid = c.fk_input_method";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON c.fk_incoterms = i.rowid';
        $sql.= " WHERE c.entity = ".$conf->entity;
        if ($ref) $sql.= " AND c.ref='".$this->db->escape($ref)."'";
        else $sql.= " AND c.rowid=".$id;

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if (! $obj)
            {
                $this->error='Bill with id '.$id.' not found';
                dol_syslog(get_class($this).'::fetch '.$this->error);
                return 0;
            }

            $this->id					= $obj->rowid;
            $this->entity				= $obj->entity;

            $this->ref					= $obj->ref;
            $this->ref_supplier			= $obj->ref_supplier;
            $this->socid				= $obj->fk_soc;
            $this->fourn_id				= $obj->fk_soc;
            $this->statut				= $obj->fk_statut;
            $this->billed				= $obj->billed;
            $this->user_author_id		= $obj->fk_user_author;
            $this->user_valid_id		= $obj->fk_user_valid;
            $this->user_approve_id		= $obj->fk_user_approve;
            $this->user_approve_id2		= $obj->fk_user_approve2;
            $this->total_ht				= $obj->total_ht;
            $this->total_tva			= $obj->total_vat;
            $this->total_localtax1		= $obj->localtax1;
            $this->total_localtax2		= $obj->localtax2;
            $this->total_ttc			= $obj->total_ttc;
            $this->date					= $this->db->jdate($obj->date_creation);
            $this->date_valid			= $this->db->jdate($obj->date_valid);
            $this->date_approve			= $this->db->jdate($obj->date_approve);
            $this->date_approve2		= $this->db->jdate($obj->date_approve2);
            $this->date_commande		= $this->db->jdate($obj->date_commande); // date we make the order to supplier
			$this->date_livraison       = $this->db->jdate($obj->date_livraison);
            $this->remise_percent		= $obj->remise_percent;
            $this->methode_commande_id	= $obj->fk_input_method;
            $this->methode_commande		= $obj->methode_commande;

            $this->source				= $obj->source;
            $this->fk_project			= $obj->fk_project;
            $this->cond_reglement_id	= $obj->fk_cond_reglement;
            $this->cond_reglement_code	= $obj->cond_reglement_code;
            $this->cond_reglement		= $obj->cond_reglement_libelle;
            $this->cond_reglement_doc	= $obj->cond_reglement_libelle;
            $this->fk_account           = $obj->fk_account;
            $this->mode_reglement_id	= $obj->fk_mode_reglement;
            $this->mode_reglement_code	= $obj->mode_reglement_code;
            $this->mode_reglement		= $obj->mode_reglement_libelle;
            $this->note					= $obj->note_private;    // deprecated
            $this->note_private			= $obj->note_private;
            $this->note_public			= $obj->note_public;
            $this->modelpdf				= $obj->model_pdf;

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

            $this->db->free($resql);

            // Retreive all extrafield
            // fetch optionals attributes and labels
            $this->fetch_optionals();

            if ($this->statut == 0) $this->brouillon = 1;

            /*
             * Lines
             */
            $result=$this->fetch_lines();
            if ($result < 0)
            {
            	return -1;
            }
            else
            {
            	return 1;
            }
        }
        else
        {
            $this->error=$this->db->error()." sql=".$sql;
            return -1;
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     * Load array lines
     *
     * @param		int		$only_product	Return only physical products
     * @return		int						<0 if KO, >0 if OK
     */
    function fetch_lines($only_product=0)
    {
        // phpcs:enable
    	//$result=$this->fetch_lines();
    	$this->lines=array();

    	$sql = "SELECT l.rowid, l.ref as ref_supplier, l.fk_product, l.product_type, l.label, l.description, l.qty,";
    	$sql.= " l.vat_src_code, l.tva_tx, l.remise_percent, l.subprice,";
    	$sql.= " l.localtax1_tx, l. localtax2_tx, l.localtax1_type, l. localtax2_type, l.total_localtax1, l.total_localtax2,";
    	$sql.= " l.total_ht, l.total_tva, l.total_ttc, l.special_code, l.fk_parent_line, l.rang,";
    	$sql.= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.description as product_desc,";
    	$sql.= " l.fk_unit,";
    	$sql.= " l.date_start, l.date_end,";
    	$sql.= ' l.fk_multicurrency, l.multicurrency_code, l.multicurrency_subprice, l.multicurrency_total_ht, l.multicurrency_total_tva, l.multicurrency_total_ttc';
    	$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet	as l";
    	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
    	$sql.= " WHERE l.fk_commande = ".$this->id;
    	if ($only_product) $sql .= ' AND p.fk_product_type = 0';
    	$sql.= " ORDER BY l.rang, l.rowid";
    	//print $sql;

    	dol_syslog(get_class($this)."::fetch get lines", LOG_DEBUG);
    	$result = $this->db->query($sql);
    	if ($result)
    	{
    		$num = $this->db->num_rows($result);
    		$i = 0;

    		while ($i < $num)
    		{
    			$objp                  = $this->db->fetch_object($result);

    			$line                 = new CommandeFournisseurLigne($this->db);

    			$line->id                  = $objp->rowid;
    			$line->desc                = $objp->description;
    			$line->description         = $objp->description;
    			$line->qty                 = $objp->qty;
    			$line->tva_tx              = $objp->tva_tx;
    			$line->localtax1_tx		   = $objp->localtax1_tx;
    			$line->localtax2_tx		   = $objp->localtax2_tx;
    			$line->localtax1_type	   = $objp->localtax1_type;
    			$line->localtax2_type	   = $objp->localtax2_type;
    			$line->subprice            = $objp->subprice;
    			$line->pu_ht	           = $objp->subprice;
    			$line->remise_percent      = $objp->remise_percent;

    			$line->vat_src_code        = $objp->vat_src_code;
    			$line->total_ht            = $objp->total_ht;
    			$line->total_tva           = $objp->total_tva;
    			$line->total_localtax1	   = $objp->total_localtax1;
    			$line->total_localtax2	   = $objp->total_localtax2;
    			$line->total_ttc           = $objp->total_ttc;
    			$line->product_type        = $objp->product_type;

    			$line->fk_product          = $objp->fk_product;

    			$line->libelle             = $objp->product_label;
    			$line->product_label       = $objp->product_label;
    			$line->product_desc        = $objp->product_desc;

    			$line->ref                 = $objp->product_ref;    // Ref of product
    			$line->product_ref         = $objp->product_ref;    // Ref of product
    			$line->ref_fourn           = $objp->ref_supplier;   // The supplier ref of price when product was added. May have change since
    			$line->ref_supplier        = $objp->ref_supplier;   // The supplier ref of price when product was added. May have change since

    			$line->date_start          = $this->db->jdate($objp->date_start);
    			$line->date_end            = $this->db->jdate($objp->date_end);
    			$line->fk_unit             = $objp->fk_unit;

    			// Multicurrency
    			$line->fk_multicurrency 		= $objp->fk_multicurrency;
    			$line->multicurrency_code 		= $objp->multicurrency_code;
    			$line->multicurrency_subprice 	= $objp->multicurrency_subprice;
    			$line->multicurrency_total_ht 	= $objp->multicurrency_total_ht;
    			$line->multicurrency_total_tva 	= $objp->multicurrency_total_tva;
    			$line->multicurrency_total_ttc 	= $objp->multicurrency_total_ttc;

    			$line->special_code        = $objp->special_code;
    			$line->fk_parent_line      = $objp->fk_parent_line;

    			$line->rang                = $objp->rang;

    			// Retreive all extrafield
    			// fetch optionals attributes and labels
    			$line->fetch_optionals();

    			$this->lines[$i]      = $line;

    			$i++;
    		}
    		$this->db->free($result);

    		return $num;
    	}
    	else
    	{
    		$this->error=$this->db->error()." sql=".$sql;
    		return -1;
    	}
    }

    /**
     *	Validate an order
     *
     *	@param	User	$user			Validator User
     *	@param	int		$idwarehouse	Id of warehouse to use for stock decrease
     *  @param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
     *	@return	int						<0 if KO, >0 if OK
     */
    public function valid($user,$idwarehouse=0,$notrigger=0)
    {
        global $langs,$conf;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $error=0;

        dol_syslog(get_class($this)."::valid");
        $result = 0;
        if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fournisseur->commande->creer))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fournisseur->supplier_order_advance->validate)))
        {
            $this->db->begin();

            // Definition of supplier order numbering model name
            $soc = new Societe($this->db);
            $soc->fetch($this->fourn_id);

            // Check if object has a temporary ref
            if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref)) // empty should not happened, but when it occurs, the test save life
            {
                $num = $this->getNextNumRef($soc);
            }
            else
			{
                $num = $this->ref;
            }
            $this->newref = $num;

            $sql = 'UPDATE '.MAIN_DB_PREFIX."commande_fournisseur";
            $sql.= " SET ref='".$this->db->escape($num)."',";
            $sql.= " fk_statut = ".self::STATUS_VALIDATED.",";
            $sql.= " date_valid='".$this->db->idate(dol_now())."',";
            $sql.= " fk_user_valid = ".$user->id;
            $sql.= " WHERE rowid = ".$this->id;
            $sql.= " AND fk_statut = ".self::STATUS_DRAFT;

            $resql=$this->db->query($sql);
            if (! $resql)
            {
                dol_print_error($this->db);
                $error++;
            }

            if (! $error && ! $notrigger)
            {
				// Call trigger
				$result=$this->call_trigger('ORDER_SUPPLIER_VALIDATE',$user);
				if ($result < 0) $error++;
				// End call triggers
            }

            if (! $error)
            {
	            $this->oldref = $this->ref;

                // Rename directory if dir was a temporary ref
                if (preg_match('/^[\(]?PROV/i', $this->ref))
                {
                    // We rename directory ($this->ref = ancienne ref, $num = nouvelle ref)
                    // in order not to lose the attached files
                    $oldref = dol_sanitizeFileName($this->ref);
                    $newref = dol_sanitizeFileName($num);
                    $dirsource = $conf->fournisseur->commande->dir_output.'/'.$oldref;
                    $dirdest = $conf->fournisseur->commande->dir_output.'/'.$newref;
                    if (file_exists($dirsource))
                    {
                        dol_syslog(get_class($this)."::valid rename dir ".$dirsource." into ".$dirdest);

                        if (@rename($dirsource, $dirdest))
                        {
                            dol_syslog("Rename ok");
                            // Rename docs starting with $oldref with $newref
	                        $listoffiles=dol_dir_list($conf->fournisseur->commande->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref,'/'));
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

            if (! $error)
            {
                $result = 1;
                $this->statut = self::STATUS_VALIDATED;
                $this->ref = $num;
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
            $this->error='NotAuthorized';
            dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Return label of the status of object
     *
	 *  @param      int		$mode			0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto
     *  @return 	string        			Label
     */
    public function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut,$mode,$this->billed);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Return label of a status
     *
     * 	@param  int		$statut		Id statut
     *  @param  int		$mode       0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto
     *  @param  int     $billed     1=Billed
     *  @return string				Label of status
     */
    function LibStatut($statut,$mode=0,$billed=0)
    {
        // phpcs:enable
    	global $conf, $langs;

    	if (empty($this->statuts) || empty($this->statutshort))
    	{
	        $langs->load('orders');

	        $this->statuts[0] = 'StatusOrderDraft';
	        $this->statuts[1] = 'StatusOrderValidated';
	        $this->statuts[2] = 'StatusOrderApproved';
	        if (empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS)) $this->statuts[3] = 'StatusOrderOnProcess';
	        else $this->statuts[3] = 'StatusOrderOnProcessWithValidation';
	        $this->statuts[4] = 'StatusOrderReceivedPartially';
	        $this->statuts[5] = 'StatusOrderReceivedAll';
	        $this->statuts[6] = 'StatusOrderCanceled';	// Approved->Canceled
	        $this->statuts[7] = 'StatusOrderCanceled';	// Process running->canceled
	        //$this->statuts[8] = 'StatusOrderBilled';	// Everything is finished, order received totally and bill received
	        $this->statuts[9] = 'StatusOrderRefused';

	        // List of language codes for status
	        $this->statutshort[0] = 'StatusOrderDraftShort';
	        $this->statutshort[1] = 'StatusOrderValidatedShort';
	        $this->statutshort[2] = 'StatusOrderApprovedShort';
	        $this->statutshort[3] = 'StatusOrderOnProcessShort';
	        $this->statutshort[4] = 'StatusOrderReceivedPartiallyShort';
	        $this->statutshort[5] = 'StatusOrderReceivedAllShort';
	        $this->statutshort[6] = 'StatusOrderCanceledShort';
	        $this->statutshort[7] = 'StatusOrderCanceledShort';
	        $this->statutshort[9] = 'StatusOrderRefusedShort';
    	}

        $billedtext='';
		//if ($statut==5 && $this->billed == 1) $statut = 8;
        if ($billed == 1) $billedtext=$langs->trans("Billed");

        if ($mode == 0)
        {
            return $langs->trans($this->statuts[$statut]);
        }
        elseif ($mode == 1)
        {
        	return $langs->trans($this->statutshort[$statut]);
        }
        elseif ($mode == 2)
        {
            return $langs->trans($this->statuts[$statut]);
        }
        elseif ($mode == 3)
        {
            if ($statut==0) return img_picto($langs->trans($this->statuts[$statut]),'statut0');
            elseif ($statut==1) return img_picto($langs->trans($this->statuts[$statut]),'statut1');
            elseif ($statut==2) return img_picto($langs->trans($this->statuts[$statut]),'statut3');
            elseif ($statut==3) return img_picto($langs->trans($this->statuts[$statut]),'statut3');
            elseif ($statut==4) return img_picto($langs->trans($this->statuts[$statut]),'statut3');
            elseif ($statut==5) return img_picto($langs->trans($this->statuts[$statut]),'statut6');
            elseif ($statut==6 || $statut==7) return img_picto($langs->trans($this->statuts[$statut]),'statut5');
            elseif ($statut==9) return img_picto($langs->trans($this->statuts[$statut]),'statut5');
        }
        elseif ($mode == 4)
        {
            if ($statut==0) return img_picto($langs->trans($this->statuts[$statut]),'statut0').' '.$langs->trans($this->statuts[$statut]).($billedtext?' - '.$billedtext:'');
            elseif ($statut==1) return img_picto($langs->trans($this->statuts[$statut]),'statut1').' '.$langs->trans($this->statuts[$statut]).($billedtext?' - '.$billedtext:'');
            elseif ($statut==2) return img_picto($langs->trans($this->statuts[$statut]),'statut3').' '.$langs->trans($this->statuts[$statut]).($billedtext?' - '.$billedtext:'');
            elseif ($statut==3) return img_picto($langs->trans($this->statuts[$statut]),'statut3').' '.$langs->trans($this->statuts[$statut]).($billedtext?' - '.$billedtext:'');
            elseif ($statut==4) return img_picto($langs->trans($this->statuts[$statut]),'statut3').' '.$langs->trans($this->statuts[$statut]).($billedtext?' - '.$billedtext:'');
            elseif ($statut==5) return img_picto($langs->trans($this->statuts[$statut]),'statut6').' '.$langs->trans($this->statuts[$statut]).($billedtext?' - '.$billedtext:'');
            elseif ($statut==6 || $statut==7) return img_picto($langs->trans($this->statuts[$statut]),'statut5').' '.$langs->trans($this->statuts[$statut]).($billedtext?' - '.$billedtext:'');
            elseif ($statut==9) return img_picto($langs->trans($this->statuts[$statut]),'statut5').' '.$langs->trans($this->statuts[$statut]).($billedtext?' - '.$billedtext:'');
        }
        elseif ($mode == 5)
        {
        	if ($statut==0) return '<span class="hideonsmartphone">'.$langs->trans($this->statutshort[$statut]).' </span>'.img_picto($langs->trans($this->statuts[$statut]),'statut0');
        	elseif ($statut==1) return '<span class="hideonsmartphone">'.$langs->trans($this->statutshort[$statut]).' </span>'.img_picto($langs->trans($this->statuts[$statut]),'statut1');
        	elseif ($statut==2) return '<span class="hideonsmartphone">'.$langs->trans($this->statutshort[$statut]).' </span>'.img_picto($langs->trans($this->statuts[$statut]),'statut3');
        	elseif ($statut==3) return '<span class="hideonsmartphone">'.$langs->trans($this->statutshort[$statut]).' </span>'.img_picto($langs->trans($this->statuts[$statut]),'statut3');
        	elseif ($statut==4) return '<span class="hideonsmartphone">'.$langs->trans($this->statutshort[$statut]).' </span>'.img_picto($langs->trans($this->statuts[$statut]),'statut3');
        	elseif ($statut==5) return '<span class="hideonsmartphone">'.$langs->trans($this->statutshort[$statut]).' </span>'.img_picto($langs->trans($this->statuts[$statut]),'statut6');
        	elseif ($statut==6 || $statut==7) return '<span class="hideonsmartphone">'.$langs->trans($this->statutshort[$statut]).' </span>'.img_picto($langs->trans($this->statuts[$statut]),'statut5');
        	elseif ($statut==9) return '<span class="hideonsmartphone">'.$langs->trans($this->statutshort[$statut]).' </span>'.img_picto($langs->trans($this->statuts[$statut]),'statut5');
        }
    }


    /**
     *	Return clicable name (with picto eventually)
     *
     *	@param		int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
     *	@param		string	$option						On what the link points
     *  @param	    int   	$notooltip					1=Disable tooltip
     *  @param      int     $save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *	@return		string								Chain with URL
     */
    public function getNomUrl($withpicto=0, $option='', $notooltip=0, $save_lastsearch_value=-1)
    {
        global $langs, $conf;

        $result='';
        $label = '<u>' . $langs->trans("ShowOrder") . '</u>';
        if (! empty($this->ref))
            $label .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
        if (! empty($this->ref_supplier))
            $label.= '<br><b>' . $langs->trans('RefSupplier') . ':</b> ' . $this->ref_supplier;
        if (! empty($this->total_ht))
            $label.= '<br><b>' . $langs->trans('AmountHT') . ':</b> ' . price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
        if (! empty($this->total_tva))
            $label.= '<br><b>' . $langs->trans('VAT') . ':</b> ' . price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
        if (! empty($this->total_ttc))
            $label.= '<br><b>' . $langs->trans('AmountTTC') . ':</b> ' . price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);

        $picto='order';
        $url = DOL_URL_ROOT.'/fourn/commande/card.php?id='.$this->id;

        if ($option !== 'nolink')
        {
        	// Add param to save lastsearch_values or not
        	$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
        	if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
        	if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
        }

        $linkclose='';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("ShowOrder");
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
        if ($withpicto != 2) $result.= $this->ref;
        $result .= $linkend;

        return $result;
    }


    /**
     *  Returns the following order reference not used depending on the numbering model activated
     *                  defined within COMMANDE_SUPPLIER_ADDON_NUMBER
     *
     *  @param	    Company		$soc  		company object
     *  @return     string                  free reference for the invoice
     */
    public function getNextNumRef($soc)
    {
        global $db, $langs, $conf;
        $langs->load("orders");

        if (! empty($conf->global->COMMANDE_SUPPLIER_ADDON_NUMBER))
        {
            $mybool = false;

            $file = $conf->global->COMMANDE_SUPPLIER_ADDON_NUMBER.'.php';
            $classname=$conf->global->COMMANDE_SUPPLIER_ADDON_NUMBER;

            // Include file with class
            $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

            foreach ($dirmodels as $reldir) {

                $dir = dol_buildpath($reldir."core/modules/supplier_order/");

                // Load file with numbering class (if found)
                $mybool|=@include_once $dir.$file;
            }

            if ($mybool === false) {
                dol_print_error('',"Failed to include file ".$file);
                return '';
            }

            $obj = new $classname();
            $numref = $obj->getNextValue($soc,$this);

            if ( $numref != "")
            {
                return $numref;
            }
            else
			{
                $this->error = $obj->error;
                return -1;
            }
        }
        else
		{
            $this->error = "Error_COMMANDE_SUPPLIER_ADDON_NotDefined";
            return -2;
        }
    }
	/**
     *	Class invoiced the supplier order
     *
     *  @param      User        $user       Object user making the change
     *	@return     int     	            <0 if KO, >0 if KO
     */
    public function classifyBilled(User $user)
    {
        $error=0;
        $this->db->begin();

        $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande_fournisseur SET billed = 1';
        $sql .= ' WHERE rowid = '.$this->id.' AND fk_statut > '.self::STATUS_DRAFT;
        if ($this->db->query($sql))
        {
        	if (! $error)
        	{
        	    // Call trigger
        	    $result=$this->call_trigger('ORDER_SUPPLIER_CLASSIFY_BILLED',$user);
        	    if ($result < 0) $error++;
        	    // End call triggers
        	}

        	if (! $error)
        	{
        	    $this->billed=1;

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
        	dol_print_error($this->db);

        	$this->db->rollback();
			return -1;
        }
    }

    /**
     * 	Approve a supplier order
     *
     *	@param	User	$user			Object user
     *	@param	int		$idwarehouse	Id of warhouse for stock change
     *  @param	int		$secondlevel	0=Standard approval, 1=Second level approval (used when option SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED is set)
     *	@return	int						<0 if KO, >0 if OK
     */
    public function approve($user, $idwarehouse=0, $secondlevel=0)
    {
        global $langs,$conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $error=0;

        dol_syslog(get_class($this)."::approve");

        if ($user->rights->fournisseur->commande->approuver)
        {
        	$now = dol_now();

            $this->db->begin();

			// Definition of order numbering model name
            $soc = new Societe($this->db);
            $soc->fetch($this->fourn_id);

            // Check if object has a temporary ref
            if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref)) // empty should not happened, but when it occurs, the test save life
            {
                $num = $this->getNextNumRef($soc);
            }
            else
			{
                $num = $this->ref;
            }
            $this->newref = $num;

            // Do we have to change status now ? (If double approval is required and first approval, we keep status to 1 = validated)
			$movetoapprovestatus=true;
			$comment='';

            $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur";
			$sql.= " SET ref='".$this->db->escape($num)."',";
			if (empty($secondlevel))	// standard or first level approval
			{
	            $sql.= " date_approve='".$this->db->idate($now)."',";
    	        $sql.= " fk_user_approve = ".$user->id;
    	        if (! empty($conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED) && $conf->global->MAIN_FEATURES_LEVEL > 0 && $this->total_ht >= $conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED)
    	        {
    	        	if (empty($this->user_approve_id2))
    	        	{
    	        	    $movetoapprovestatus=false;		// second level approval not done
    	        	    $comment=' (first level)';
    	        	}
    	        }
			}
			else	// request a second level approval
			{
            	$sql.= " date_approve2='".$this->db->idate($now)."',";
            	$sql.= " fk_user_approve2 = ".$user->id;
    	        if (empty($this->user_approve_id)) $movetoapprovestatus=false;		// first level approval not done
    	        $comment=' (second level)';
			}
			// If double approval is required and first approval, we keep status to 1 = validated
			if ($movetoapprovestatus) $sql.= ", fk_statut = ".self::STATUS_ACCEPTED;
			else $sql.= ", fk_statut = ".self::STATUS_VALIDATED;
            $sql.= " WHERE rowid = ".$this->id;
            $sql.= " AND fk_statut = ".self::STATUS_VALIDATED;

            if ($this->db->query($sql))
            {
            	if (! empty($conf->global->SUPPLIER_ORDER_AUTOADD_USER_CONTACT))
	            {
					$result=$this->add_contact($user->id, 'SALESREPFOLL', 'internal', 1);
					if ($result < 0 && $result != -2)	// -2 means already exists
					{
						$error++;
					}
	            }

                // If stock is incremented on validate order, we must increment it
                if (! $error && $movetoapprovestatus && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER))
                {
                    require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
                    $langs->load("agenda");

                    $cpt=count($this->lines);
                    for ($i = 0; $i < $cpt; $i++)
                    {
                        // Product with reference
                        if ($this->lines[$i]->fk_product > 0)
                        {
                            $this->line = $this->lines[$i];
                            $mouvP = new MouvementStock($this->db);
                            $mouvP->origin = &$this;
                            // We decrement stock of product (and sub-products)
	                        $up_ht_disc=$this->lines[$i]->subprice;
    	                    if (! empty($this->lines[$i]->remise_percent) && empty($conf->global->STOCK_EXCLUDE_DISCOUNT_FOR_PMP)) $up_ht_disc=price2num($up_ht_disc * (100 - $this->lines[$i]->remise_percent) / 100, 'MU');
                            $result=$mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $up_ht_disc, $langs->trans("OrderApprovedInDolibarr",$this->ref));
                            if ($result < 0) { $error++; }
                            unset($this->line);
                        }
                    }
                }

                if (! $error)
                {
					// Call trigger
					$result=$this->call_trigger('ORDER_SUPPLIER_APPROVE',$user);
					if ($result < 0) $error++;
					// End call triggers
                }

                if (! $error)
                {
                	$this->ref = $this->newref;

                	if ($movetoapprovestatus) $this->statut = self::STATUS_ACCEPTED;
					else $this->statut = self::STATUS_VALIDATED;
           			if (empty($secondlevel))	// standard or first level approval
					{
			            $this->date_approve = $now;
		    	        $this->user_approve_id = $user->id;
					}
					else	// request a second level approval
					{
			            $this->date_approve2 = $now;
		    	        $this->user_approve_id2 = $user->id;
					}

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
                $this->db->rollback();
                $this->error=$this->db->lasterror();
                return -1;
            }
        }
        else
        {
            dol_syslog(get_class($this)."::approve Not Authorized", LOG_ERR);
        }
        return -1;
    }

    /**
     * 	Refuse an order
     *
     * 	@param		User	$user		User making action
     *	@return		int					0 if Ok, <0 if Ko
     */
    public function refuse($user)
    {
        global $conf, $langs;

		$error=0;

        dol_syslog(get_class($this)."::refuse");
        $result = 0;
        if ($user->rights->fournisseur->commande->approuver)
        {
            $this->db->begin();

            $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = ".self::STATUS_REFUSED;
            $sql .= " WHERE rowid = ".$this->id;

            if ($this->db->query($sql))
            {
                $result = 0;

                if ($error == 0)
                {
					// Call trigger
					$result=$this->call_trigger('ORDER_SUPPLIER_REFUSE',$user);
					if ($result < 0)
                    {
                        $error++;
                        $this->db->rollback();
                    }
                    else
                    	$this->db->commit();
					// End call triggers
                }
            }
            else
            {
                $this->db->rollback();
                $this->error=$this->db->lasterror();
                dol_syslog(get_class($this)."::refuse Error -1");
                $result = -1;
            }
        }
        else
        {
            dol_syslog(get_class($this)."::refuse Not Authorized");
        }
        return $result ;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     * 	Cancel an approved order.
     *	The cancellation is done after approval
     *
     * 	@param	User	$user			User making action
     *	@param	int		$idwarehouse	Id warehouse to use for stock change (not used for supplier orders).
     * 	@return	int						>0 if Ok, <0 if Ko
     */
    function Cancel($user, $idwarehouse=-1)
    {
        // phpcs:enable
        global $langs,$conf;

		$error=0;

        //dol_syslog("CommandeFournisseur::Cancel");
        $result = 0;
        if ($user->rights->fournisseur->commande->commander)
        {
            $statut = self::STATUS_CANCELED;

            $this->db->begin();

            $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = ".$statut;
            $sql .= " WHERE rowid = ".$this->id;
            dol_syslog(get_class($this)."::cancel", LOG_DEBUG);
            if ($this->db->query($sql))
            {
                $result = 0;

				// Call trigger
				$result=$this->call_trigger('ORDER_SUPPLIER_CANCEL',$user);
				if ($result < 0) $error++;
				// End call triggers

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
                $this->db->rollback();
                $this->error=$this->db->lasterror();
                dol_syslog(get_class($this)."::cancel ".$this->error);
                return -1;
            }
        }
        else
        {
            dol_syslog(get_class($this)."::cancel Not Authorized");
            return -1;
        }
    }

    /**
     * 	Submit a supplier order to supplier
     *
     * 	@param		User	$user		User making change
     * 	@param		date	$date		Date
     * 	@param		int		$methode	Method
     * 	@param		string	$comment	Comment
     * 	@return		int			        <0 if KO, >0 if OK
     */
    public function commande($user, $date, $methode, $comment='')
    {
        global $langs;
        dol_syslog(get_class($this)."::commande");
        $error = 0;
        if ($user->rights->fournisseur->commande->commander)
        {
            $this->db->begin();

            $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = ".self::STATUS_ORDERSENT.", fk_input_method=".$methode.", date_commande='".$this->db->idate($date)."'";
            $sql .= " WHERE rowid = ".$this->id;

            dol_syslog(get_class($this)."::commande", LOG_DEBUG);
            if ($this->db->query($sql))
            {
                $this->statut = self::STATUS_ORDERSENT;
                $this->methode_commande_id = $methode;
                $this->date_commande = $date;

                // Call trigger
                $result=$this->call_trigger('ORDER_SUPPLIER_SUBMIT',$user);
                if ($result < 0) $error++;
                // End call triggers
            }
            else
            {
                $error++;
                $this->error = $this->db->lasterror();
                $this->errors[] = $this->db->lasterror();
            }

            if (! $error)
            {
                $this->db->commit();
            }
            else
            {
                $this->db->rollback();
            }
        }
        else
        {
            $error++;
            $this->error = $langs->trans('NotAuthorized');
            $this->errors[] = $langs->trans('NotAuthorized');
            dol_syslog(get_class($this)."::commande User not Authorized", LOG_WARNING);
        }

        return ($error ? -1 : 1);
    }

    /**
     *  Create order with draft status
     *
     *  @param      User	$user       User making creation
     *	@param		int		$notrigger	Disable all triggers
     *  @return     int         		<0 if KO, Id of supplier order if OK
     */
    public function create($user, $notrigger=0)
    {
        global $langs,$conf,$hookmanager;

        $this->db->begin();

		$error=0;
        $now=dol_now();

        // Clean parameters
        if (empty($this->source)) $this->source = 0;

		// Multicurrency (test on $this->multicurrency_tx because we should take the default rate only if not using origin rate)
		if (!empty($this->multicurrency_code) && empty($this->multicurrency_tx)) list($this->fk_multicurrency,$this->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($this->db, $this->multicurrency_code);
		else $this->fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $this->multicurrency_code);
		if (empty($this->fk_multicurrency))
		{
			$this->multicurrency_code = $conf->currency;
			$this->fk_multicurrency = 0;
			$this->multicurrency_tx = 1;
		}

        // We set order into draft status
        $this->brouillon = 1;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur (";
        $sql.= "ref";
        $sql.= ", ref_supplier";
        $sql.= ", note_private";
        $sql.= ", note_public";
        $sql.= ", entity";
        $sql.= ", fk_soc";
        $sql.= ", fk_projet";
        $sql.= ", date_creation";
		$sql.= ", date_livraison";
        $sql.= ", fk_user_author";
        $sql.= ", fk_statut";
        $sql.= ", source";
        $sql.= ", model_pdf";
        $sql.= ", fk_mode_reglement";
		$sql.= ", fk_cond_reglement";
        $sql.= ", fk_account";
		$sql.= ", fk_incoterms, location_incoterms";
        $sql.= ", fk_multicurrency";
        $sql.= ", multicurrency_code";
        $sql.= ", multicurrency_tx";
        $sql.= ") ";
        $sql.= " VALUES (";
        $sql.= "''";
        $sql.= ", '".$this->db->escape($this->ref_supplier)."'";
        $sql.= ", '".$this->db->escape($this->note_private)."'";
        $sql.= ", '".$this->db->escape($this->note_public)."'";
        $sql.= ", ".$conf->entity;
        $sql.= ", ".$this->socid;
        $sql.= ", ".($this->fk_project > 0 ? $this->fk_project : "null");
        $sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", ".($this->date_livraison?"'".$this->db->idate($this->date_livraison)."'":"null");
        $sql.= ", ".$user->id;
        $sql.= ", ".self::STATUS_DRAFT;
        $sql.= ", ".$this->db->escape($this->source);
        $sql.= ", '".$conf->global->COMMANDE_SUPPLIER_ADDON_PDF."'";
        $sql.= ", ".($this->mode_reglement_id > 0 ? $this->mode_reglement_id : 'null');
        $sql.= ", ".($this->cond_reglement_id > 0 ? $this->cond_reglement_id : 'null');
        $sql.= ", ".($this->fk_account>0?$this->fk_account:'NULL');
        $sql.= ", ".(int) $this->fk_incoterms;
        $sql.= ", '".$this->db->escape($this->location_incoterms)."'";
		$sql.= ", ".(int) $this->fk_multicurrency;
		$sql.= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql.= ", ".(double) $this->multicurrency_tx;
        $sql.= ")";

        dol_syslog(get_class($this)."::create", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."commande_fournisseur");

			if ($this->id) {
				$num=count($this->lines);

	            // insert products details into database
	            for ($i=0;$i<$num;$i++)
	            {

	                $this->special_code = $this->lines[$i]->special_code; // TODO : remove this in 9.0 and add special_code param to addline()

	                $result = $this->addline(              // This include test on qty if option SUPPLIER_ORDER_WITH_NOPRICEDEFINED is not set
	                    $this->lines[$i]->desc,
	                    $this->lines[$i]->subprice,
	                    $this->lines[$i]->qty,
	                    $this->lines[$i]->tva_tx,
	                    $this->lines[$i]->localtax1_tx,
	                    $this->lines[$i]->localtax2_tx,
	                    $this->lines[$i]->fk_product,
	                    0,
	                    $this->lines[$i]->ref_fourn,   // $this->lines[$i]->ref_fourn comes from field ref into table of lines. Value may ba a ref that does not exists anymore, so we first try with value of product
	                    $this->lines[$i]->remise_percent,
	                    'HT',
	                    0,
	                    $this->lines[$i]->product_type,
	                    $this->lines[$i]->info_bits,
                        false,
	                    $this->lines[$i]->date_start,
                        $this->lines[$i]->date_end,
                        0,
                        $this->lines[$i]->fk_unit
	                );
	                if ($result < 0)
	                {
	                    dol_syslog(get_class($this)."::create ".$this->error, LOG_WARNING);	// do not use dol_print_error here as it may be a functionnal error
	                    $this->db->rollback();
	                    return -1;
	                }
	            }

	            $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur";
	            $sql.= " SET ref='(PROV".$this->id.")'";
	            $sql.= " WHERE rowid=".$this->id;
	            dol_syslog(get_class($this)."::create", LOG_DEBUG);
	            if ($this->db->query($sql))
	            {
					// Add link with price request and supplier order
					if ($this->id)
					{
						$this->ref="(PROV".$this->id.")";

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
					}

	                if (! $error)
                    {
                    	$result=$this->insertExtraFields();
	                    if ($result < 0) $error++;
                    }

					if (! $error && ! $notrigger)
	                {
						// Call trigger
						$result=$this->call_trigger('ORDER_SUPPLIER_CREATE',$user);
						if ($result < 0)
	                    {
	                        $this->db->rollback();
	                        return -1;
	                    }
						// End call triggers
	                }

	                $this->db->commit();
	                return $this->id;
	            }
	            else
	            {
	                $this->error=$this->db->lasterror();
	                $this->db->rollback();
	                return -2;
	            }
            }
        }
        else
        {
            $this->error=$this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *	Load an object from its id and create a new one in database
     *
     *	@return		int							New id of clone
     */
    public function createFromClone()
    {
        global $conf,$user,$langs,$hookmanager;

        $error=0;

		$this->db->begin();

		// Load source object
		$objFrom = clone $this;

        $this->id=0;
        $this->statut=self::STATUS_DRAFT;

        // Clear fields
        $this->user_author_id     = $user->id;
        $this->user_valid         = '';
        $this->date_creation      = '';
        $this->date_validation    = '';
        $this->ref_supplier       = '';
        $this->user_approve_id    = '';
        $this->user_approve_id2   = '';
        $this->date_approve       = '';
        $this->date_approve2      = '';

        // Create clone
        $this->context['createfromclone'] = 'createfromclone';
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
     *	Add order line
     *
     *	@param      string	$desc            		Description
     *	@param      float	$pu_ht              	Unit price
     *	@param      float	$qty             		Quantity
     *	@param      float	$txtva           		Taux tva
     *	@param      float	$txlocaltax1        	Localtax1 tax
     *  @param      float	$txlocaltax2        	Localtax2 tax
     *	@param      int		$fk_product      		Id product
     *  @param      int		$fk_prod_fourn_price	Id supplier price
     *  @param      string	$ref_supplier			Supplier reference price
     *	@param      float	$remise_percent  		Remise
     *	@param      string	$price_base_type		HT or TTC
     *	@param		float	$pu_ttc					Unit price TTC
     *	@param		int		$type					Type of line (0=product, 1=service)
     *	@param		int		$info_bits				More information
     *  @param		bool	$notrigger				Disable triggers
     *  @param		int		$date_start				Date start of service
     *  @param		int		$date_end				Date end of service
	 *  @param		array	$array_options			extrafields array
     *  @param 		string	$fk_unit 				Code of the unit to use. Null to use the default one
	 *  @param 		string	$pu_ht_devise			Amount in currency
	 *  @param		string	$origin					'order', ...
	 *  @param		int		$origin_id				Id of origin object
     *	@return     int             				<=0 if KO, >0 if OK
     */
	public function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0.0, $txlocaltax2=0.0, $fk_product=0, $fk_prod_fourn_price=0, $ref_supplier='', $remise_percent=0.0, $price_base_type='HT', $pu_ttc=0.0, $type=0, $info_bits=0, $notrigger=false, $date_start=null, $date_end=null, $array_options=0, $fk_unit=null, $pu_ht_devise=0, $origin='', $origin_id=0)
    {
        global $langs,$mysoc,$conf;

        $error = 0;

        dol_syslog(get_class($this)."::addline $desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $fk_prod_fourn_price, $ref_supplier, $remise_percent, $price_base_type, $pu_ttc, $type, $info_bits, $notrigger, $date_start, $date_end, $fk_unit, $pu_ht_devise, $origin, $origin_id");
        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		if ($this->statut == self::STATUS_DRAFT)
		{
			include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

			// Clean parameters
			if (! $qty) $qty=1;
			if (! $info_bits) $info_bits=0;
			if (empty($txtva)) $txtva=0;
			if (empty($txlocaltax1)) $txlocaltax1=0;
			if (empty($txlocaltax2)) $txlocaltax2=0;
			if (empty($remise_percent)) $remise_percent=0;

			$remise_percent=price2num($remise_percent);
			$qty=price2num($qty);
			$pu_ht=price2num($pu_ht);
			$pu_ht_devise=price2num($pu_ht_devise);
			$pu_ttc=price2num($pu_ttc);
			if (!preg_match('/\((.*)\)/', $txtva)) {
				$txtva = price2num($txtva);               // $txtva can have format '5.0(XXX)' or '5'
			}
			$txlocaltax1 = price2num($txlocaltax1);
			$txlocaltax2 = price2num($txlocaltax2);
			if ($price_base_type=='HT')
			{
				$pu=$pu_ht;
			}
			else
			{
				$pu=$pu_ttc;
			}
			$desc=trim($desc);

			// Check parameters
			if ($qty < 1 && ! $fk_product)
			{
				$this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Product"));
				return -1;
			}
			if ($type < 0) return -1;


            $this->db->begin();

            if ($fk_product > 0)
            {
            	if (! empty($conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY))
                {
                    // Check quantity is enough
                    dol_syslog(get_class($this)."::addline we check supplier prices fk_product=".$fk_product." fk_prod_fourn_price=".$fk_prod_fourn_price." qty=".$qty." ref_supplier=".$ref_supplier);
                    $prod = new Product($this->db, $fk_product);
                    if ($prod->fetch($fk_product) > 0)
                    {
                        $product_type = $prod->type;
                        $label = $prod->label;

                        // We use 'none' instead of $ref_supplier, because fourn_ref may not exists anymore. So we will take the first supplier price ok.
                        // If we want a dedicated supplier price, we must provide $fk_prod_fourn_price.
                        $result=$prod->get_buyprice($fk_prod_fourn_price, $qty, $fk_product, 'none', ($this->fk_soc?$this->fk_soc:$this->socid));   // Search on couple $fk_prod_fourn_price/$qty first, then on triplet $qty/$fk_product/$ref_supplier/$this->fk_soc
                        // If supplier order created from customer order, we take best supplier price
                        // If $pu (defined previously from pu_ht or pu_ttc) is not defined at all, we also take the best supplier price
                        if ($result > 0 && ($origin == 'commande' || $pu === ''))
                        {
                        	$pu = $prod->fourn_pu;       // Unit price supplier price set by get_buyprice
                        	$ref_supplier = $prod->ref_supplier;   // Ref supplier price set by get_buyprice
                        	// is remise percent not keyed but present for the product we add it
                        	if ($remise_percent == 0 && $prod->remise_percent !=0) $remise_percent =$prod->remise_percent;
                        }
                        if ($result == 0)                   // If result == 0, we failed to found the supplier reference price
                        {
                            $langs->load("errors");
                            $this->error = "Ref " . $prod->ref . " " . $langs->trans("ErrorQtyTooLowForThisSupplier");
                            $this->db->rollback();
                            dol_syslog(get_class($this)."::addline we did not found supplier price, so we can't guess unit price");
                            //$pu    = $prod->fourn_pu;     // We do not overwrite unit price
                            //$ref   = $prod->ref_fourn;    // We do not overwrite ref supplier price
                            return -1;
                        }
                        if ($result == -1)
                        {
                            $langs->load("errors");
                            $this->error = "Ref " . $prod->ref . " " . $langs->trans("ErrorQtyTooLowForThisSupplier");
                            $this->db->rollback();
                            dol_syslog(get_class($this)."::addline result=".$result." - ".$this->error, LOG_DEBUG);
                            return -1;
                        }
                        if ($result < -1)
                        {
                            $this->error=$prod->error;
                            $this->db->rollback();
                            dol_syslog(get_class($this)."::addline result=".$result." - ".$this->error, LOG_ERR);
                            return -1;
                        }
                    }
                    else
    				{
                        $this->error=$prod->error;
                        $this->db->rollback();
                        return -1;
                    }
                }
            }
            else
            {
                $product_type = $type;
            }

            if ($conf->multicurrency->enabled && $pu_ht_devise > 0) {
            	$pu = 0;
            }

            $localtaxes_type=getLocalTaxesFromRate($txtva,0,$mysoc,$this->thirdparty);

            // Clean vat code
            $vat_src_code='';
            if (preg_match('/\((.*)\)/', $txtva, $reg))
            {
                $vat_src_code = $reg[1];
                $txtva = preg_replace('/\s*\(.*\)/', '', $txtva);    // Remove code into vatrate.
            }

            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

            $tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $product_type, $this->thirdparty, $localtaxes_type, 100, $this->multicurrency_tx,$pu_ht_devise);

            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];
            $total_localtax1 = $tabprice[9];
            $total_localtax2 = $tabprice[10];
            $pu = $pu_ht = $tabprice[3];

			// MultiCurrency
			$multicurrency_total_ht  = $tabprice[16];
            $multicurrency_total_tva = $tabprice[17];
            $multicurrency_total_ttc = $tabprice[18];
			$pu_ht_devise = $tabprice[19];

            $localtax1_type=$localtaxes_type[0];
			$localtax2_type=$localtaxes_type[2];

            $subprice = price2num($pu,'MU');

            $rangmax = $this->line_max();
            $rang = $rangmax + 1;

            // Insert line
            $this->line=new CommandeFournisseurLigne($this->db);

            $this->line->context = $this->context;

            $this->line->fk_commande=$this->id;
            $this->line->label=$label;
            $this->line->ref_fourn = $ref_supplier;
            $this->line->ref_supplier = $ref_supplier;
            $this->line->desc=$desc;
            $this->line->qty=$qty;
            $this->line->tva_tx=$txtva;
            $this->line->localtax1_tx=($total_localtax1?$localtaxes_type[1]:0);
            $this->line->localtax2_tx=($total_localtax2?$localtaxes_type[3]:0);
            $this->line->localtax1_type = $localtaxes_type[0];
            $this->line->localtax2_type = $localtaxes_type[2];
            $this->line->fk_product=$fk_product;
            $this->line->product_type=$product_type;
            $this->line->remise_percent=$remise_percent;
            $this->line->subprice=$pu_ht;
            $this->line->rang=$rang;
            $this->line->info_bits=$info_bits;

            $this->line->vat_src_code=$vat_src_code;
            $this->line->total_ht=$total_ht;
            $this->line->total_tva=$total_tva;
            $this->line->total_localtax1=$total_localtax1;
            $this->line->total_localtax2=$total_localtax2;
            $this->line->total_ttc=$total_ttc;
            $this->line->product_type=$type;
            $this->line->special_code=$this->special_code;
            $this->line->origin=$origin;
            $this->line->origin_id=$origin_id;
            $this->line->fk_unit=$fk_unit;

            $this->line->date_start=$date_start;
            $this->line->date_end=$date_end;

            // Multicurrency
            $this->line->fk_multicurrency			= $this->fk_multicurrency;
            $this->line->multicurrency_code			= $this->multicurrency_code;
            $this->line->multicurrency_subprice		= $pu_ht_devise;
            $this->line->multicurrency_total_ht 	= $multicurrency_total_ht;
            $this->line->multicurrency_total_tva 	= $multicurrency_total_tva;
            $this->line->multicurrency_total_ttc 	= $multicurrency_total_ttc;

            $this->line->subprice=$pu_ht;
            $this->line->price=$this->line->subprice;

            $this->line->remise_percent=$remise_percent;

            if (is_array($array_options) && count($array_options)>0) {
                $this->line->array_options=$array_options;
            }

            $result=$this->line->insert($notrigger);
            if ($result > 0)
            {
                // Reorder if child line
                if (! empty($fk_parent_line)) $this->line_order(true,'DESC');

                // Mise a jour informations denormalisees au niveau de la commande meme
                $result=$this->update_price(1,'auto',0,$this->thirdparty);	// This method is designed to add line from user input so total calculation must be done using 'auto' mode.
                if ($result > 0)
                {
                    $this->db->commit();
                    return $this->line->id;
                }
                else
                {
                    $this->db->rollback();
                    return -1;
                }
            }
            else
            {
                $this->error=$this->line->error;
                $this->errors=$this->line->errors;
                dol_syslog(get_class($this)."::addline error=".$this->error, LOG_ERR);
                $this->db->rollback();
                return -1;
            }
        }
    }


    /**
     * Save a receiving into the tracking table of receiving (commande_fournisseur_dispatch) and add product into stock warehouse.
     *
     * @param 	User		$user					User object making change
     * @param 	int			$product				Id of product to dispatch
     * @param 	double		$qty					Qty to dispatch
     * @param 	int			$entrepot				Id of warehouse to add product
     * @param 	double		$price					Unit Price for PMP value calculation (Unit price without Tax and taking into account discount)
     * @param	string		$comment				Comment for stock movement
	 * @param	date		$eatby					eat-by date
	 * @param	date		$sellby					sell-by date
	 * @param	string		$batch					Lot number
	 * @param	int			$fk_commandefourndet	Id of supplier order line
     * @param	int			$notrigger          	1 = notrigger
     * @return 	int						<0 if KO, >0 if OK
     */
    public function dispatchProduct($user, $product, $qty, $entrepot, $price=0, $comment='', $eatby='', $sellby='', $batch='', $fk_commandefourndet=0, $notrigger=0)
    {
        global $conf, $langs;

        $error = 0;
        require_once DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php';

        // Check parameters (if test are wrong here, there is bug into caller)
        if ($entrepot <= 0)
        {
            $this->error='ErrorBadValueForParameterWarehouse';
            return -1;
        }
        if ($qty == 0)
        {
            $this->error='ErrorBadValueForParameterQty';
            return -1;
        }

        $dispatchstatus = 1;
        if (! empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS)) $dispatchstatus = 0;	// Setting dispatch status (a validation step after receiving products) will be done manually to 1 or 2 if this option is on

        $now=dol_now();

        if (($this->statut == self::STATUS_ORDERSENT || $this->statut == self::STATUS_RECEIVED_PARTIALLY || $this->statut == self::STATUS_RECEIVED_COMPLETELY))
        {
            $this->db->begin();

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur_dispatch";
            $sql.= " (fk_commande, fk_product, qty, fk_entrepot, fk_user, datec, fk_commandefourndet, status, comment, eatby, sellby, batch) VALUES";
            $sql.= " ('".$this->id."','".$product."','".$qty."',".($entrepot>0?"'".$entrepot."'":"null").",'".$user->id."','".$this->db->idate($now)."','".$fk_commandefourndet."', ".$dispatchstatus.", '".$this->db->escape($comment)."', ";
            $sql.= ($eatby?"'".$this->db->idate($eatby)."'":"null").", ".($sellby?"'".$this->db->idate($sellby)."'":"null").", ".($batch?"'".$batch."'":"null");
            $sql.= ")";

            dol_syslog(get_class($this)."::dispatchProduct", LOG_DEBUG);
            $resql = $this->db->query($sql);
            if ($resql)
            {
                if (! $notrigger)
                {
                    global $conf, $langs, $user;
					// Call trigger
					$result=$this->call_trigger('LINEORDER_SUPPLIER_DISPATCH',$user);
					if ($result < 0)
                    {
                        $error++;
                        return -1;
                    }
					// End call triggers
                }
            }
            else
			{
                $this->error=$this->db->lasterror();
                $error++;
            }

            // Si module stock gere et que incrementation faite depuis un dispatching en stock
            if (! $error && $entrepot > 0 && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER))
            {

                $mouv = new MouvementStock($this->db);
                if ($product > 0)
                {
                	// $price should take into account discount (except if option STOCK_EXCLUDE_DISCOUNT_FOR_PMP is on)
                	$mouv->origin = &$this;
					$result=$mouv->reception($user, $product, $entrepot, $qty, $price, $comment, $eatby, $sellby, $batch);
                    if ($result < 0)
                    {
                        $this->error=$mouv->error;
                        $this->errors=$mouv->errors;
                        dol_syslog(get_class($this)."::dispatchProduct ".$this->error." ".join(',',$this->errors), LOG_ERR);
                        $error++;
                    }
                }
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
            $this->error='BadStatusForObject';
            return -2;
        }
    }

    /**
     * 	Delete line
     *
     *	@param	int		$idline		Id of line to delete
     *	@param	int		$notrigger	1=Disable call to triggers
     *	@return	int					<0 if KO, >0 if OK
     */
    public function deleteline($idline, $notrigger=0)
    {
        if ($this->statut == 0)
        {
            $line = new CommandeFournisseurLigne($this->db);

            if ($line->fetch($idline) <= 0)
            {
                return 0;
            }

            if ($line->delete($notrigger) > 0)
            {
                $this->update_price();
                return 1;
            }
            else
            {
                $this->error = $line->error;
                $this->errors = $line->errors;
                return -1;
            }
        }
        else
        {
            return -2;
        }
    }

    /**
     *  Delete an order
     *
     *	@param	User	$user		Object user
     *	@param	int		$notrigger	1=Does not execute triggers, 0= execute triggers
     *	@return	int					<0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger=0)
    {
        global $langs,$conf;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $error = 0;

        $this->db->begin();

        if (empty($notrigger))
        {
            // Call trigger
            $result=$this->call_trigger('ORDER_SUPPLIER_DELETE',$user);
            if ($result < 0)
            {
            	$this->errors[]='ErrorWhenRunningTrigger';
            	dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
            	return -1;
            }
            // End call triggers
        }

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseurdet WHERE fk_commande =". $this->id ;
        dol_syslog(get_class($this)."::delete", LOG_DEBUG);
        if (! $this->db->query($sql) )
        {
            $this->error=$this->db->lasterror();
            $this->errors[]=$this->db->lasterror();
            $error++;
        }

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE rowid =".$this->id;
        dol_syslog(get_class($this)."::delete", LOG_DEBUG);
        if ($resql = $this->db->query($sql) )
        {
            if ($this->db->affected_rows($resql) < 1)
            {
                $this->error=$this->db->lasterror();
                $this->errors[]=$this->db->lasterror();
                $error++;
            }
        }
        else
        {
            $this->error=$this->db->lasterror();
            $this->errors[]=$this->db->lasterror();
            $error++;
        }

        // Remove extrafields
        if ((! $error) && (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED))) // For avoid conflicts if trigger used
        {
        	$result=$this->deleteExtraFields();
        	if ($result < 0)
        	{
        		$this->error='FailToDeleteExtraFields';
        		$this->errors[]='FailToDeleteExtraFields';
        		$error++;
        		dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
        	}
        }

		// Delete linked object
    	$res = $this->deleteObjectLinked();
    	if ($res < 0) {
    		$this->error='FailToDeleteObjectLinked';
    		$this->errors[]='FailToDeleteObjectLinked';
    		$error++;
    	}

        if (! $error)
        {
        	// We remove directory
        	$ref = dol_sanitizeFileName($this->ref);
        	if ($conf->fournisseur->commande->dir_output)
        	{
        		$dir = $conf->fournisseur->commande->dir_output . "/" . $ref ;
        		$file = $dir . "/" . $ref . ".pdf";
        		if (file_exists($file))
        		{
        			if (! dol_delete_file($file,0,0,0,$this)) // For triggers
        			{
        				$this->error='ErrorFailToDeleteFile';
        				$this->errors[]='ErrorFailToDeleteFile';
        				$error++;
        			}
        		}
        		if (file_exists($dir))
        		{
        			$res=@dol_delete_dir_recursive($dir);
        			if (! $res)
        			{
        				$this->error='ErrorFailToDeleteDir';
        				$this->errors[]='ErrorFailToDeleteDir';
        				$error++;
        			}
        		}
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
			dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -$error;
		}
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *	Get list of order methods
     *
     *	@return int 0 if OK, <0 if KO
     */
    function get_methodes_commande()
    {
        // phpcs:enable
        $sql = "SELECT rowid, libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_input_method";
        $sql.= " WHERE active = 1";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $i = 0;
            $num = $this->db->num_rows($resql);
            $this->methodes_commande = array();
            while ($i < $num)
            {
                $row = $this->db->fetch_row($resql);

                $this->methodes_commande[$row[0]] = $row[1];

                $i++;
            }
            return 0;
        }
        else
        {
            return -1;
        }
    }

    /**
	 * Return array of dispatched lines waiting to be approved for this order
     *
     * @since 8.0 Return dispatched quantity (qty).
	 *
	 * @param	int		$status		Filter on stats (-1 = no filter, 0 = lines draft to be approved, 1 = approved lines)
	 * @return	array				Array of lines
     */
    public function getDispachedLines($status=-1)
    {
    	$ret = array();

    	// List of already dispatched lines
		$sql = "SELECT p.ref, p.label,";
		$sql.= " e.rowid as warehouse_id, e.ref as entrepot,";
		$sql.= " cfd.rowid as dispatchedlineid, cfd.fk_product, cfd.qty, cfd.eatby, cfd.sellby, cfd.batch, cfd.comment, cfd.status";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p,";
		$sql.= " ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as cfd";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as e ON cfd.fk_entrepot = e.rowid";
		$sql.= " WHERE cfd.fk_commande = ".$this->id;
		$sql.= " AND cfd.fk_product = p.rowid";
		if ($status >= 0) $sql.=" AND cfd.status = ".$status;
		$sql.= " ORDER BY cfd.rowid ASC";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num)
			{
				$objp = $this->db->fetch_object($resql);
				if ($objp)
				{
					$ret[] = array(
						'id' => $objp->dispatchedlineid,
						'productid' => $objp->fk_product,
						'warehouseid' => $objp->warehouse_id,
						'qty' => $objp->qty,
					);
				}

				$i++;
			}
		}
		else dol_print_error($this->db, 'Failed to execute request to get dispatched lines');

		return $ret;
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     * 	Set a delivery in database for this supplier order
     *
     *	@param	User	$user		User that input data
     *	@param	date	$date		Date of reception
     *	@param	string	$type		Type of receipt ('tot' = total/done, 'par' = partial, 'nev' = never, 'can' = cancel)
     *	@param	string	$comment	Comment
     *	@return	int					<0 if KO, >0 if OK
     */
    function Livraison($user, $date, $type, $comment)
    {
        // phpcs:enable
    	global $conf, $langs;

        $result = 0;
		$error = 0;

        dol_syslog(get_class($this)."::Livraison");

        if ($user->rights->fournisseur->commande->receptionner)
        {
        	// Define the new status
            if ($type == 'par') $statut = self::STATUS_RECEIVED_PARTIALLY;
            elseif ($type == 'tot')	$statut = self::STATUS_RECEIVED_COMPLETELY;
            elseif ($type == 'nev') $statut = self::STATUS_CANCELED_AFTER_ORDER;
            elseif ($type == 'can') $statut = self::STATUS_CANCELED_AFTER_ORDER;
			else {
            	$error++;
                dol_syslog(get_class($this)."::Livraison Error -2", LOG_ERR);
                return -2;
			}

            // Some checks to accept the record
            if (! empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS))
            {
				// If option SUPPLIER_ORDER_USE_DISPATCH_STATUS is on, we check all reception are approved to allow status "total/done"
	        	if (! $error && ($type == 'tot'))
		    	{
		    		$dispatchedlinearray=$this->getDispachedLines(0);
		    		if (count($dispatchedlinearray) > 0)
		    		{
		    			$result=-1;
		    			$error++;
		    			$this->errors[]='ErrorCantSetReceptionToTotalDoneWithReceptionToApprove';
		    			dol_syslog('ErrorCantSetReceptionToTotalDoneWithReceptionToApprove', LOG_DEBUG);
		    		}
		    	}
	    		if (! $error && ! empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS_NEED_APPROVE) && ($type == 'tot'))	// Accept to move to reception done, only if status of all line are ok (refuse denied)
	    		{
	    			$dispatcheddenied=$this->getDispachedLines(2);
	    			if (count($dispatchedlinearray) > 0)
	    			{
		    			$result=-1;
		    			$error++;
		    			$this->errors[]='ErrorCantSetReceptionToTotalDoneWithReceptionDenied';
		    			dol_syslog('ErrorCantSetReceptionToTotalDoneWithReceptionDenied', LOG_DEBUG);
	    			}
	    		}
            }

            // TODO LDR01 Add a control test to accept only if ALL predefined products are received (same qty).


            if (! $error)
            {
                $this->db->begin();

                $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur";
                $sql.= " SET fk_statut = ".$statut;
                $sql.= " WHERE rowid = ".$this->id;
                $sql.= " AND fk_statut IN (".self::STATUS_ORDERSENT.",".self::STATUS_RECEIVED_PARTIALLY.")";	// Process running or Partially received

                dol_syslog(get_class($this)."::Livraison", LOG_DEBUG);
                $resql=$this->db->query($sql);
                if ($resql)
                {
                    $result = 0;
                    $old_statut = $this->statut;
                    $this->statut = $statut;
					$this->actionmsg2 = $comment;

                    // Call trigger
                    $result=$this->call_trigger('ORDER_SUPPLIER_RECEIVE',$user);
                    if ($result < 0) $error++;
                    // End call triggers

                    if (! $error)
                    {
                        $this->db->commit();
                    }
                    else
                    {
                        $this->statut = $old_statut;
                        $this->db->rollback();
                        $this->error=$this->db->lasterror();
                        $result = -1;
                    }
                }
                else
                {
                    $this->db->rollback();
                    $this->error=$this->db->lasterror();
                    $result = -1;
                }
            }
        }
        else
        {
            $this->error = $langs->trans('NotAuthorized');
            $this->errors[] = $langs->trans('NotAuthorized');
            dol_syslog(get_class($this)."::Livraison Not Authorized");
            $result = -3;
        }
        return $result ;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *	Set the planned delivery date
     *
     *	@param      User			$user        		Objet user making change
     *	@param      timestamp		$date_livraison     Planned delivery date
     *  @param     	int				$notrigger			1=Does not execute triggers, 0= execute triggers
     *	@return     int         						<0 if KO, >0 if OK
     */
    function set_date_livraison($user, $date_livraison, $notrigger=0)
    {
        // phpcs:enable
        if ($user->rights->fournisseur->commande->creer)
        {
        	$error=0;

        	$this->db->begin();

        	$sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur";
            $sql.= " SET date_livraison = ".($date_livraison ? "'".$this->db->idate($date_livraison)."'" : 'null');
            $sql.= " WHERE rowid = ".$this->id;

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
        		$this->date_livraison = $date_livraison;
        	}

        	if (! $notrigger && empty($error))
        	{
        		// Call trigger
        		$result=$this->call_trigger('ORDER_SUPPLIER_MODIFY',$user);
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
        else
        {
            return -2;
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
     *	Set the id projet
     *
     *	@param      User			$user        		Objet utilisateur qui modifie
     *	@param      int				$id_projet    	 	Date de livraison
     *  @param     	int				$notrigger			1=Does not execute triggers, 0= execute triggers
     *	@return     int         						<0 si ko, >0 si ok
     */
    function set_id_projet($user, $id_projet, $notrigger=0)
    {
        // phpcs:enable
        if ($user->rights->fournisseur->commande->creer)
        {
        	$error=0;

        	$this->db->begin();

            $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur";
            $sql.= " SET fk_projet = ".($id_projet > 0 ? (int) $id_projet : 'null');
            $sql.= " WHERE rowid = ".$this->id;

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
            	$this->fk_projet = $id_projet;
            }

            if (! $notrigger && empty($error))
            {
            	// Call trigger
            	$result=$this->call_trigger('ORDER_SUPPLIER_MODIFY',$user);
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
        else
        {
            return -2;
        }
    }

    /**
     *  Update a supplier order from a customer order
     *
     *  @param  User	$user           User that create
     *  @param  int		$idc			Id of supplier order to update
     *  @param	int		$comclientid	Id of customer order to use as template
     *	@return	int						<0 if KO, >0 if OK
     */
    public function updateFromCommandeClient($user, $idc, $comclientid)
    {
        $comclient = new Commande($this->db);
        $comclient->fetch($comclientid);

        $this->id = $idc;

        $this->lines = array();

        $num=count($comclient->lines);
        for ($i = 0; $i < $num; $i++)
        {
            $prod = new Product($this->db);
            $libelle = '';
            $ref = '';
            if ($prod->fetch($comclient->lines[$i]->fk_product) > 0)
            {
                $libelle  = $prod->libelle;
                $ref      = $prod->ref;
            }

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseurdet";
            $sql .= " (fk_commande,label,description,fk_product, price, qty, tva_tx, localtax1_tx, localtax2_tx, remise_percent, subprice, remise, ref)";
            $sql .= " VALUES (".$idc.", '" . $this->db->escape($libelle) . "','" . $this->db->escape($comclient->lines[$i]->desc) . "'";
            $sql .= ",".$comclient->lines[$i]->fk_product.",'".price2num($comclient->lines[$i]->price)."'";
            $sql .= ", '".$comclient->lines[$i]->qty."', ".$comclient->lines[$i]->tva_tx.", ".$comclient->lines[$i]->localtax1_tx.", ".$comclient->lines[$i]->localtax2_tx.", ".$comclient->lines[$i]->remise_percent;
            $sql .= ", '".price2num($comclient->lines[$i]->subprice)."','0','".$ref."');";
            if ($this->db->query($sql))
            {
                $this->update_price();
            }
        }

        return 1;
    }

    /**
     *  Tag order with a particular status
     *
     *  @param      User	$user       Object user that change status
     *  @param      int		$status		New status
     *  @return     int         		<0 if KO, >0 if OK
     */
    public function setStatus($user, $status)
    {
        global $conf,$langs;
        $error=0;

        $this->db->begin();

        $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande_fournisseur';
        $sql.= ' SET fk_statut='.$status;
        $sql.= ' WHERE rowid = '.$this->id;

        dol_syslog(get_class($this)."::setStatus", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            // Trigger names for each status
            $trigger_name[0] = 'DRAFT';
            $trigger_name[1] = 'VALIDATED';
            $trigger_name[2] = 'APPROVED';
            $trigger_name[3] = 'ORDERED';				// Ordered
            $trigger_name[4] = 'RECEIVED_PARTIALLY';
            $trigger_name[5] = 'RECEIVED_COMPLETELY';
            $trigger_name[6] = 'CANCELED';
            $trigger_name[7] = 'CANCELED';
            $trigger_name[9] = 'REFUSED';

            // Call trigger
            $result=$this->call_trigger("ORDER_SUPPLIER_STATUS_".$trigger_name[$status],$user);
            if ($result < 0) { $error++; }
            // End call triggers
        }
        else
        {
            $error++;
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::setStatus ".$this->error);
        }

        if (! $error)
        {
            $this->statut = $status;
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
     *	Update line
     *
     *	@param     	int			$rowid           	Id de la ligne de facture
     *	@param     	string		$desc            	Description de la ligne
     *	@param     	double		$pu              	Prix unitaire
     *	@param     	double		$qty             	Quantity
     *	@param     	double		$remise_percent  	Percent discount on line
     *	@param     	double		$txtva          	VAT rate
     *  @param     	double		$txlocaltax1	    Localtax1 tax
     *  @param     	double		$txlocaltax2   		Localtax2 tax
     *  @param     	double		$price_base_type 	Type of price base
     *	@param		int			$info_bits			Miscellaneous informations
     *	@param		int			$type				Type of line (0=product, 1=service)
     *  @param		int			$notrigger			Disable triggers
     *  @param      timestamp   $date_start     	Date start of service
     *  @param      timestamp   $date_end       	Date end of service
	 *  @param		array		$array_options		Extrafields array
     * 	@param 		string		$fk_unit 			Code of the unit to use. Null to use the default one
	 * 	@param		double		$pu_ht_devise		Unit price in currency
	 *  @param		string		$ref_supplier		Supplier ref
     *	@return    	int         	    			< 0 if error, > 0 if ok
     */
    public function updateline($rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1=0, $txlocaltax2=0, $price_base_type='HT', $info_bits=0, $type=0, $notrigger=0, $date_start='', $date_end='', $array_options=0, $fk_unit=null, $pu_ht_devise=0, $ref_supplier='')
    {
    	global $mysoc, $conf;
        dol_syslog(get_class($this)."::updateline $rowid, $desc, $pu, $qty, $remise_percent, $txtva, $price_base_type, $info_bits, $type, $fk_unit");
        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

        $error = 0;

        if ($this->brouillon)
        {
            $this->db->begin();

            // Clean parameters
            if (empty($qty)) $qty=0;
            if (empty($info_bits)) $info_bits=0;
            if (empty($txtva)) $txtva=0;
            if (empty($txlocaltax1)) $txlocaltax1=0;
            if (empty($txlocaltax2)) $txlocaltax2=0;
            if (empty($remise)) $remise=0;
            if (empty($remise_percent)) $remise_percent=0;

            $remise_percent=price2num($remise_percent);
            $qty=price2num($qty);
            if (! $qty) $qty=1;
            $pu = price2num($pu);
        	$pu_ht_devise=price2num($pu_ht_devise);
            $txtva=price2num($txtva);
            $txlocaltax1=price2num($txlocaltax1);
            $txlocaltax2=price2num($txlocaltax2);

            // Check parameters
            if ($type < 0) return -1;

            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

            $localtaxes_type=getLocalTaxesFromRate($txtva,0,$mysoc, $this->thirdparty);

            // Clean vat code
            $vat_src_code='';
            if (preg_match('/\((.*)\)/', $txtva, $reg))
            {
                $vat_src_code = $reg[1];
                $txtva = preg_replace('/\s*\(.*\)/', '', $txtva);    // Remove code into vatrate.
            }

            $tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $this->thirdparty, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);
            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];
            $total_localtax1 = $tabprice[9];
            $total_localtax2 = $tabprice[10];
			$pu_ht  = $tabprice[3];
			$pu_tva = $tabprice[4];
			$pu_ttc = $tabprice[5];

			// MultiCurrency
			$multicurrency_total_ht  = $tabprice[16];
            $multicurrency_total_tva = $tabprice[17];
            $multicurrency_total_ttc = $tabprice[18];
			$pu_ht_devise = $tabprice[19];

            $localtax1_type=$localtaxes_type[0];
			$localtax2_type=$localtaxes_type[2];

            $subprice = price2num($pu_ht,'MU');

            //Fetch current line from the database and then clone the object and set it in $oldline property
            $this->line=new CommandeFournisseurLigne($this->db);
            $this->line->fetch($rowid);
            $oldline = clone $this->line;
            $this->line->oldline = $oldline;

            $this->line->context = $this->context;

            $this->line->fk_commande=$this->id;
            //$this->line->label=$label;
            $this->line->desc=$desc;
            $this->line->qty=$qty;
			$this->line->ref_supplier=$ref_supplier;

	        $this->line->vat_src_code   = $vat_src_code;
            $this->line->tva_tx         = $txtva;
            $this->line->localtax1_tx   = $txlocaltax1;
            $this->line->localtax2_tx   = $txlocaltax2;
            $this->line->localtax1_type = $localtaxes_type[0];
            $this->line->localtax2_type = $localtaxes_type[2];
            $this->line->remise_percent = $remise_percent;
            $this->line->subprice       = $pu_ht;
            $this->line->rang           = $this->rang;
            $this->line->info_bits      = $info_bits;
            $this->line->total_ht       = $total_ht;
            $this->line->total_tva      = $total_tva;
            $this->line->total_localtax1= $total_localtax1;
            $this->line->total_localtax2= $total_localtax2;
            $this->line->total_ttc      = $total_ttc;
            $this->line->product_type   = $type;
            $this->line->special_code   = $this->special_code;
            $this->line->origin         = $this->origin;
            $this->line->fk_unit        = $fk_unit;

            $this->line->date_start     = $date_start;
            $this->line->date_end       = $date_end;

            // Multicurrency
            $this->line->fk_multicurrency			= $this->fk_multicurrency;
            $this->line->multicurrency_code			= $this->multicurrency_code;
            $this->line->multicurrency_subprice		= $pu_ht_devise;
            $this->line->multicurrency_total_ht 	= $multicurrency_total_ht;
            $this->line->multicurrency_total_tva 	= $multicurrency_total_tva;
            $this->line->multicurrency_total_ttc 	= $multicurrency_total_ttc;

            $this->line->subprice=$pu_ht;
            $this->line->price=$this->line->subprice;

            $this->line->remise_percent=$remise_percent;

            if (is_array($array_options) && count($array_options)>0) {
                $this->line->array_options=$array_options;
            }

            $result=$this->line->update($notrigger);


            // Mise a jour info denormalisees au niveau facture
            if ($result >= 0)
            {
                $this->update_price('','auto');
				$this->db->commit();
				return $result;
            }
            else
            {
                $this->error=$this->db->lasterror();
                $this->db->rollback();
                return -1;
            }
        }
        else
        {
            $this->error="Order status makes operation forbidden";
            dol_syslog(get_class($this)."::updateline ".$this->error, LOG_ERR);
            return -2;
        }
    }


    /**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    public function initAsSpecimen()
    {
        global $user,$langs,$conf;

        dol_syslog(get_class($this)."::initAsSpecimen");

        $now=dol_now();

        // Find first product
        $prodid=0;
        $product=new ProductFournisseur($this->db);
        $sql = "SELECT rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."product";
        $sql.= " WHERE entity IN (".getEntity('product').")";
        $sql.=$this->db->order("rowid","ASC");
        $sql.=$this->db->plimit(1);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            $prodid = $obj->rowid;
        }

        // Initialise parametres
        $this->id=0;
        $this->ref = 'SPECIMEN';
        $this->specimen=1;
        $this->socid = 1;
        $this->date = $now;
        $this->date_commande = $now;
        $this->date_lim_reglement=$this->date+3600*24*30;
        $this->cond_reglement_code = 'RECEP';
        $this->mode_reglement_code = 'CHQ';
        $this->note_public='This is a comment (public)';
        $this->note_private='This is a comment (private)';
        $this->statut=0;

        // Lines
        $nbp = 5;
        $xnbp = 0;
        while ($xnbp < $nbp)
        {
            $line=new CommandeFournisseurLigne($this->db);
            $line->desc=$langs->trans("Description")." ".$xnbp;
            $line->qty=1;
            $line->subprice=100;
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
                $line->remise_percent=00;
            }
            $line->fk_product=$prodid;

            $this->lines[$xnbp]=$line;

            $this->total_ht       += $line->total_ht;
            $this->total_tva      += $line->total_tva;
            $this->total_ttc      += $line->total_ttc;

            $xnbp++;
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
        $sql = 'SELECT c.rowid, date_creation as datec, tms as datem, date_valid as date_validation, date_approve as datea, date_approve2 as datea2,';
        $sql.= ' fk_user_author, fk_user_modif, fk_user_valid, fk_user_approve, fk_user_approve2';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'commande_fournisseur as c';
        $sql.= ' WHERE c.rowid = '.$id;

        $result=$this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);
                $this->id = $obj->rowid;
                if ($obj->fk_user_author)   $this->user_creation_id = $obj->fk_user_author;
                if ($obj->fk_user_valid)    $this->user_validation_id = $obj->fk_user_valid;
                if ($obj->fk_user_modif)    $this->user_modification_id =$obj->fk_user_modif;
                if ($obj->fk_user_approve)  $this->user_approve_id = $obj->fk_user_approve;
                if ($obj->fk_user_approve2) $this->user_approve_id2 = $obj->fk_user_approve2;

                $this->date_creation     = $this->db->idate($obj->datec);
                $this->date_modification = $this->db->idate($obj->datem);
                $this->date_approve      = $this->db->idate($obj->datea);
                $this->date_approve2     = $this->db->idate($obj->datea2);
                $this->date_validation   = $this->db->idate($obj->date_validation);
            }
            $this->db->free($result);
        }
        else
        {
            dol_print_error($this->db);
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *	Charge indicateurs this->nb de tableau de bord
     *
     *	@return     int         <0 si ko, >0 si ok
     */
    function load_state_board()
    {
        // phpcs:enable
        global $conf, $user;

        $this->nb=array();
        $clause = "WHERE";

        $sql = "SELECT count(co.rowid) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as co";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON co.fk_soc = s.rowid";
        if (!$user->rights->societe->client->voir && !$user->societe_id)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
            $sql.= " WHERE sc.fk_user = " .$user->id;
            $clause = "AND";
        }
        $sql.= " ".$clause." co.entity = ".$conf->entity;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nb["supplier_orders"]=$obj->nb;
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *	Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *
     *	@param          User	$user   Objet user
     *	@return WorkboardResponse|int 	<0 if KO, WorkboardResponse if OK
     */
    function load_board($user)
    {
        // phpcs:enable
        global $conf, $langs;

        $clause = " WHERE";

        $sql = "SELECT c.rowid, c.date_creation as datec, c.date_commande, c.fk_statut, c.date_livraison as delivery_date";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
        if (!$user->rights->societe->client->voir && !$user->societe_id)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON c.fk_soc = sc.fk_soc";
            $sql.= " WHERE sc.fk_user = " .$user->id;
            $clause = " AND";
        }
        $sql.= $clause." c.entity = ".$conf->entity;
        $sql.= " AND c.fk_statut IN (".self::STATUS_VALIDATED.", ".self::STATUS_ACCEPTED.")";
        if ($user->societe_id) $sql.=" AND c.fk_soc = ".$user->societe_id;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $commandestatic = new CommandeFournisseur($this->db);

	        $response = new WorkboardResponse();
	        $response->warning_delay=$conf->commande->fournisseur->warning_delay/60/60/24;
	        $response->label=$langs->trans("SuppliersOrdersToProcess");
	        $response->url=DOL_URL_ROOT.'/fourn/commande/list.php?statut=1,2,3&mainmenu=commercial&leftmenu=orders_suppliers';
	        $response->img=img_object('',"order");

            while ($obj=$this->db->fetch_object($resql))
            {
                $response->nbtodo++;

                $commandestatic->date_livraison = $this->db->jdate($obj->delivery_date);
                $commandestatic->date_commande = $this->db->jdate($obj->date_commande);
                $commandestatic->statut = $obj->fk_statut;

                if ($commandestatic->hasDelay()) {
	                $response->nbtodolate++;
                }
            }

            return $response;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     * Returns the translated input method of object (defined if $this->methode_commande_id > 0).
     * This function make a sql request to get translation. No cache yet, try to not use it inside a loop.
     *
     * @return string
     */
    public function getInputMethod()
    {
        global $db, $langs;

        if ($this->methode_commande_id > 0)
        {
            $sql = "SELECT rowid, code, libelle as label";
            $sql.= " FROM ".MAIN_DB_PREFIX.'c_input_method';
            $sql.= " WHERE active=1 AND rowid = ".$db->escape($this->methode_commande_id);

            $resql = $db->query($sql);
            if ($resql)
            {
                if ($db->num_rows($query))
                {
                    $obj = $db->fetch_object($query);

                    $string = $langs->trans($obj->code);
                    if ($string == $obj->code)
                    {
                        $string = $obj->label != '-' ? $obj->label : '';
                    }
                    return $string;
                }
            }
            else dol_print_error($db);
        }

        return '';
    }

	/**
	 *  Create a document onto disk according to template model.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	Object lang to use for traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
     *  @param      null|array  $moreparams     Array to provide more information
	 *  @return     int          				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0, $moreparams=null)
	{
		global $conf, $langs;

		$langs->load("suppliers");

		if (! dol_strlen($modele)) {

			$modele = 'muscadet';

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (! empty($conf->global->COMMANDE_SUPPLIER_ADDON_PDF)) {
				$modele = $conf->global->COMMANDE_SUPPLIER_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/supplier_order/pdf/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}

	/**
     * Return the max number delivery delay in day
     *
     * @param	Translate	$langs		Language object
     * @return 							Translated string
     */
    public function getMaxDeliveryTimeDay($langs)
	{
		if (empty($this->lines)) return '';

		$obj = new ProductFournisseur($this->db);

		$nb = 0;
		foreach ($this->lines as $line)
		{
			if ($line->fk_product > 0)
			{
				$idp = $obj->find_min_price_product_fournisseur($line->fk_product, $line->qty);
				if ($idp)
				{
					$obj->fetch($idp);
					if ($obj->delivery_time_days > $nb) $nb = $obj->delivery_time_days;
				}
			}
		}

		if ($nb === 0) return '';
		else return $nb.' '.$langs->trans('Days');
	}

	/**
	 * Returns the rights used for this class
	 * @return stdClass
	 */
	public function getRights()
	{
		global $user;

		return $user->rights->fournisseur->commande;
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
			'commande_fournisseur'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}

    /**
     * Is the supplier order delayed?
     *
     * @return bool
     */
    public function hasDelay()
    {
        global $conf;

        if (empty($this->date_delivery) && ! empty($this->date_livraison)) $this->date_delivery = $this->date_livraison;    // For backward compatibility

        $now = dol_now();
        $date_to_test = empty($this->date_delivery) ? $this->date_commande : $this->date_delivery;

        return ($this->statut > 0 && $this->statut < 4) && $date_to_test && $date_to_test < ($now - $conf->commande->fournisseur->warning_delay);
    }

    /**
     * Show the customer delayed info
     *
     * @return string       Show delayed information
     */
    public function showDelay()
    {
        global $conf, $langs;

        if (empty($this->date_delivery) && ! empty($this->date_livraison)) $this->date_delivery = $this->date_livraison;    // For backward compatibility

        if (empty($this->date_delivery)) $text=$langs->trans("OrderDate").' '.dol_print_date($this->date_commande, 'day');
        else $text=$text=$langs->trans("DeliveryDate").' '.dol_print_date($this->date_delivery, 'day');
        $text.=' '.($conf->commande->fournisseur->warning_delay>0?'+':'-').' '.round(abs($conf->commande->fournisseur->warning_delay)/3600/24,1).' '.$langs->trans("days").' < '.$langs->trans("Today");

        return $text;
    }


    /**
     * Calc status regarding to dispatched stock
     *
     * @param 		User 	$user                   User action
     * @param       int     $closeopenorder         Close if received
     * @param		string	$comment				Comment
     * @return		int		                        <0 if KO, 0 if not applicable, >0 if OK
     */
    public function calcAndSetStatusDispatch(User $user, $closeopenorder=1, $comment='')
    {
    	global $conf, $langs;

    	if (! empty($conf->fournisseur->enabled))
    	{
    		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';

    		$qtydelivered=array();
    		$qtywished=array();

    		$supplierorderdispatch = new CommandeFournisseurDispatch($this->db);
    		$filter=array('t.fk_commande'=>$this->id);
    		if (! empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS)) {
    			$filter['t.status']=1;	// Restrict to lines with status validated
    		}

    		$ret=$supplierorderdispatch->fetchAll('','',0,0,$filter);
    		if ($ret<0)
    		{
    			$this->error=$supplierorderdispatch->error; $this->errors=$supplierorderdispatch->errors;
    			return $ret;
    		}
    		else
    		{
    			if (is_array($supplierorderdispatch->lines) && count($supplierorderdispatch->lines)>0)
    			{
    				$date_liv = dol_now();

    				// Build array with quantity deliverd by product
    				foreach($supplierorderdispatch->lines as $line) {
    					$qtydelivered[$line->fk_product]+=$line->qty;
    				}
    				foreach($this->lines as $line) {
    					$qtywished[$line->fk_product]+=$line->qty;
    				}
    				//Compare array
    				$diff_array=array_diff_assoc($qtydelivered,$qtywished);		// Warning: $diff_array is done only on common keys.
    				$keysinwishednotindelivered=array_diff(array_keys($qtywished),array_keys($qtydelivered));		// To check we also have same number of keys
    				$keysindeliverednotinwished=array_diff(array_keys($qtydelivered),array_keys($qtywished));		// To check we also have same number of keys
    				/*var_dump(array_keys($qtydelivered));
    				var_dump(array_keys($qtywished));
    				var_dump($diff_array);
    				var_dump($keysinwishednotindelivered);
    				var_dump($keysindeliverednotinwished);
    				exit;*/

    				if (count($diff_array)==0 && count($keysinwishednotindelivered)==0 && count($keysindeliverednotinwished)==0) //No diff => mean everythings is received
    				{
    					if ($closeopenorder)
    					{
        					//$ret=$this->setStatus($user,5);
    						$ret = $this->Livraison($user, $date_liv, 'tot', $comment);   // GETPOST("type") is 'tot', 'par', 'nev', 'can'
        					if ($ret<0) {
        						return -1;
        					}
    					    return 5;
    					}
    					else
    					{
    					    //Diff => received partially
    					    //$ret=$this->setStatus($user,4);
    						$ret = $this->Livraison($user, $date_liv, 'par', $comment);   // GETPOST("type") is 'tot', 'par', 'nev', 'can'
    					    if ($ret<0) {
    					        return -1;
    					    }
    					    return 4;
    					}
    				}elseif(! empty($conf->global->SUPPLIER_ORDER_MORE_THAN_WISHED) )
				{//set livraison to 'tot' if more products received than wished. (and if $closeopenorder is set to 1 of course...)

					$close=0;

					if( count($diff_array) > 0 )
					{//there are some difference between  the two arrays

						//scan the array of results
						foreach($diff_array as $key => $value)
						{//if the quantity delivered is greater or equal to wish quantity
							if($qtydelivered[$key] >= $qtywished[$key] )
							{
								$close++;
							}
						}
					}


					if($close == count($diff_array))
					{//all the products are received equal or more than the wished quantity
						if ($closeopenorder)
    						{
    							$ret = $this->Livraison($user, $date_liv, 'tot', $comment);   // GETPOST("type") is 'tot', 'par', 'nev', 'can'
        						if ($ret<0) {
        							return -1;
        						}
    					    		return 5;
    						}
    						else
    						{
    					   	 	//Diff => received partially
    					  		$ret = $this->Livraison($user, $date_liv, 'par', $comment);   // GETPOST("type") is 'tot', 'par', 'nev', 'can'
							if ($ret<0) {
								return -1;
							}
							return 4;
						}
					}
					else
					{//all the products are not received
						$ret = $this->Livraison($user, $date_liv, 'par', $comment);   // GETPOST("type") is 'tot', 'par', 'nev', 'can'
						if ($ret<0) {
							return -1;
						}
						return 4;
					}
				}
    				else
    				{
    					//Diff => received partially
    					$ret = $this->Livraison($user, $date_liv, 'par', $comment);   // GETPOST("type") is 'tot', 'par', 'nev', 'can'
    					if ($ret<0) {
    						return -1;
    					}
    					return 4;
    				}
    			}
    			return 1;
    		}
    	}
    	return 0;
    }
}



/**
 *  Class to manage line orders
 */
class CommandeFournisseurLigne extends CommonOrderLine
{
    /**
	 * @var string ID to identify managed object
	 */
	public $element='commande_fournisseurdet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='commande_fournisseurdet';

    public $oldline;

    /**
     * Id of parent order
     * @var int
     */
    public $fk_commande;

    // From llx_commande_fournisseurdet
    /**
     * @var int ID
     */
    public $fk_parent_line;

    /**
     * @var int ID
     */
    public $fk_facture;

    /**
     * @var string supplier order line label
     */
    public $label;

    public $rang = 0;
    public $special_code = 0;

	/**
	 * Unit price without taxes
	 * @var float
	 */
	public $pu_ht;

    public $date_start;
    public $date_end;

    // From llx_product_fournisseur_price

	/**
	 * Supplier reference of price when we added the line. May have been changed after line was added.
	 * @var string
	 */
    public $ref_supplier;
    public $remise;
    public $product_libelle;


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
     *  Load line order
     *
     *  @param  int		$rowid      Id line order
     *	@return	int					<0 if KO, >0 if OK
     */
    public function fetch($rowid)
    {
        $sql = 'SELECT cd.rowid, cd.fk_commande, cd.fk_product, cd.product_type, cd.description, cd.qty, cd.tva_tx, cd.special_code,';
        $sql.= ' cd.localtax1_tx, cd.localtax2_tx, cd.localtax1_type, cd.localtax2_type, cd.ref,';
        $sql.= ' cd.remise, cd.remise_percent, cd.subprice,';
        $sql.= ' cd.info_bits, cd.total_ht, cd.total_tva, cd.total_ttc,';
        $sql.= ' cd.total_localtax1, cd.total_localtax2,';
        $sql.= ' p.ref as product_ref, p.label as product_libelle, p.description as product_desc,';
        $sql.= ' cd.date_start, cd.date_end, cd.fk_unit,';
		$sql.= ' cd.multicurrency_subprice, cd.multicurrency_total_ht, cd.multicurrency_total_tva, cd.multicurrency_total_ttc';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'commande_fournisseurdet as cd';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON cd.fk_product = p.rowid';
        $sql.= ' WHERE cd.rowid = '.$rowid;
        $result = $this->db->query($sql);
        if ($result)
        {
            $objp = $this->db->fetch_object($result);

            if (!empty($objp))
            {
                $this->rowid            = $objp->rowid;
	            $this->id               = $objp->rowid;
	            $this->fk_commande      = $objp->fk_commande;
	            $this->desc             = $objp->description;
	            $this->qty              = $objp->qty;
	            $this->ref_fourn        = $objp->ref;
	            $this->ref_supplier     = $objp->ref;
	            $this->subprice         = $objp->subprice;
	            $this->tva_tx           = $objp->tva_tx;
	            $this->localtax1_tx		= $objp->localtax1_tx;
	            $this->localtax2_tx		= $objp->localtax2_tx;
	            $this->localtax1_type	= $objp->localtax1_type;
	            $this->localtax2_type	= $objp->localtax2_type;
	            $this->remise           = $objp->remise;
	            $this->remise_percent   = $objp->remise_percent;
	            $this->fk_product       = $objp->fk_product;
	            $this->info_bits        = $objp->info_bits;
	            $this->total_ht         = $objp->total_ht;
	            $this->total_tva        = $objp->total_tva;
    	        $this->total_localtax1	= $objp->total_localtax1;
	            $this->total_localtax2	= $objp->total_localtax2;
	            $this->total_ttc        = $objp->total_ttc;
	            $this->product_type     = $objp->product_type;
	            $this->special_code     = $objp->special_code;
	
    	        $this->ref	            = $objp->product_ref;
	            $this->product_ref      = $objp->product_ref;
	            $this->product_libelle  = $objp->product_libelle;
	            $this->product_desc     = $objp->product_desc;

	            $this->date_start       		= $this->db->jdate($objp->date_start);
	            $this->date_end         		= $this->db->jdate($objp->date_end);
		        $this->fk_unit          		= $objp->fk_unit;

				$this->multicurrency_subprice	= $objp->multicurrency_subprice;
				$this->multicurrency_total_ht	= $objp->multicurrency_total_ht;
				$this->multicurrency_total_tva	= $objp->multicurrency_total_tva;
				$this->multicurrency_total_ttc	= $objp->multicurrency_total_ttc;

				$this->fetch_optionals();

	            $this->db->free($result);
    	        return 1;
            }
    	    else
    	    {
    	        $this->error='Supplier order line  with id='.$rowid.' not found';
    	        dol_syslog(get_class($this)."::fetch Error ".$this->error, LOG_ERR);
    	        return 0;
    	    }
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *	Insert line into database
     *
     *	@param      int		$notrigger		1 = disable triggers
     *	@return		int						<0 if KO, >0 if OK
     */
    public function insert($notrigger=0)
    {
        global $conf, $user;

        $error=0;

        dol_syslog(get_class($this)."::insert rang=".$this->rang);

        // Clean parameters
        if (empty($this->tva_tx)) $this->tva_tx=0;
        if (empty($this->localtax1_tx)) $this->localtax1_tx=0;
        if (empty($this->localtax2_tx)) $this->localtax2_tx=0;
        if (empty($this->localtax1_type)) $this->localtax1_type='0';
        if (empty($this->localtax2_type)) $this->localtax2_type='0';
        if (empty($this->total_localtax1)) $this->total_localtax1=0;
        if (empty($this->total_localtax2)) $this->total_localtax2=0;
        if (empty($this->rang)) $this->rang=0;
        if (empty($this->remise)) $this->remise=0;
        if (empty($this->remise_percent)) $this->remise_percent=0;
        if (empty($this->info_bits)) $this->info_bits=0;
        if (empty($this->special_code)) $this->special_code=0;
        if (empty($this->fk_parent_line)) $this->fk_parent_line=0;
        if (empty($this->pa_ht)) $this->pa_ht=0;

        // Multicurrency
        if (!empty($this->multicurrency_code)) list($this->fk_multicurrency,$this->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($this->db, $this->multicurrency_code);
        if (empty($this->fk_multicurrency))
        {
            $this->multicurrency_code = $conf->currency;
            $this->fk_multicurrency = 0;
            $this->multicurrency_tx = 1;
        }

        // Check parameters
        if ($this->product_type < 0) return -1;

        $this->db->begin();

        // Insertion dans base de la ligne
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element;
        $sql.= " (fk_commande, label, description, date_start, date_end,";
        $sql.= " fk_product, product_type, special_code, rang,";
        $sql.= " qty, vat_src_code, tva_tx, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, remise_percent, subprice, ref,";
        $sql.= " total_ht, total_tva, total_localtax1, total_localtax2, total_ttc, fk_unit,";
        $sql.= " fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc";
        $sql.= ")";
        $sql.= " VALUES (".$this->fk_commande.", '" . $this->db->escape($this->label) . "','" . $this->db->escape($this->desc) . "',";
        $sql.= " ".($this->date_start?"'".$this->db->idate($this->date_start)."'":"null").",";
        $sql.= " ".($this->date_end?"'".$this->db->idate($this->date_end)."'":"null").",";
        if ($this->fk_product) { $sql.= $this->fk_product.","; }
        else { $sql.= "null,"; }
        $sql.= "'".$this->db->escape($this->product_type)."',";
        $sql.= "'".$this->db->escape($this->special_code)."',";
        $sql.= "'".$this->db->escape($this->rang)."',";
        $sql.= "'".$this->db->escape($this->qty)."', ";
        $sql.= " ".(empty($this->vat_src_code)?"''":"'".$this->db->escape($this->vat_src_code)."'").",";
        $sql.= " ".$this->tva_tx.", ";
        $sql.= " ".$this->localtax1_tx.",";
        $sql.= " ".$this->localtax2_tx.",";
        $sql.= " '".$this->db->escape($this->localtax1_type)."',";
        $sql.= " '".$this->db->escape($this->localtax2_type)."',";
        $sql.= " ".$this->remise_percent.", ".price2num($this->subprice,'MU').", '".$this->db->escape($this->ref_supplier)."',";
        $sql.= " ".price2num($this->total_ht).",";
        $sql.= " ".price2num($this->total_tva).",";
        $sql.= " ".price2num($this->total_localtax1).",";
        $sql.= " ".price2num($this->total_localtax2).",";
        $sql.= " ".price2num($this->total_ttc).",";
        $sql.= ($this->fk_unit ? "'".$this->db->escape($this->fk_unit)."'":"null");
        $sql.= ", ".($this->fk_multicurrency ? $this->fk_multicurrency : "null");
        $sql.= ", '".$this->db->escape($this->multicurrency_code)."'";
        $sql.= ", ".($this->multicurrency_subprice ? price2num($this->multicurrency_subprice) : '0');
        $sql.= ", ".($this->multicurrency_total_ht ? price2num($this->multicurrency_total_ht) : '0');
        $sql.= ", ".($this->multicurrency_total_tva ? price2num($this->multicurrency_total_tva) : '0');
        $sql.= ", ".($this->multicurrency_total_ttc ? price2num($this->multicurrency_total_ttc) : '0');
        $sql.= ")";

        dol_syslog(get_class($this)."::insert", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id=$this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
            $this->rowid =$this->id;

            if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
            {
                $result=$this->insertExtraFields();
                if ($result < 0)
                {
                    $error++;
                }
            }

            if (! $error && ! $notrigger)
            {
                // Call trigger
                $result=$this->call_trigger('LINEORDER_SUPPLIER_CREATE',$user);
                if ($result < 0) $error++;
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return 1;
            }

            foreach($this->errors as $errmsg)
            {
                dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
                $this->errors[]=($this->errors?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        }
        else
        {
            $this->errors[]=$this->db->error();
            $this->db->rollback();
            return -2;
        }
    }
    /**
     *	Update the line object into db
     *
     *	@param      int		$notrigger		1 = disable triggers
     *	@return		int		<0 si ko, >0 si ok
     */
    public function update($notrigger=0)
    {
        global $conf,$user;

        $error=0;

        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.= "  description='".$this->db->escape($this->desc)."'";
        $sql.= ", ref='".$this->db->escape($this->ref_supplier)."'";
        $sql.= ", subprice='".price2num($this->subprice)."'";
        //$sql.= ",remise='".price2num($remise)."'";
        $sql.= ", remise_percent='".price2num($this->remise_percent)."'";

		$sql.= ", vat_src_code = '".(empty($this->vat_src_code)?'':$this->vat_src_code)."'";
        $sql.= ", tva_tx='".price2num($this->tva_tx)."'";
        $sql.= ", localtax1_tx='".price2num($this->total_localtax1)."'";
        $sql.= ", localtax2_tx='".price2num($this->total_localtax2)."'";
        $sql.= ", localtax1_type='".$this->db->escape($this->localtax1_type)."'";
        $sql.= ", localtax2_type='".$this->db->escape($this->localtax2_type)."'";
        $sql.= ", qty='".price2num($this->qty)."'";
        $sql.= ", date_start=".(! empty($this->date_start)?"'".$this->db->idate($this->date_start)."'":"null");
        $sql.= ", date_end=".(! empty($this->date_end)?"'".$this->db->idate($this->date_end)."'":"null");
        $sql.= ", info_bits='".$this->db->escape($this->info_bits)."'";
        $sql.= ", total_ht='".price2num($this->total_ht)."'";
        $sql.= ", total_tva='".price2num($this->total_tva)."'";
        $sql.= ", total_localtax1='".price2num($this->total_localtax1)."'";
        $sql.= ", total_localtax2='".price2num($this->total_localtax2)."'";
        $sql.= ", total_ttc='".price2num($this->total_ttc)."'";
        $sql.= ", product_type=".$this->product_type;
        $sql.= ", special_code=".(!empty($this->special_code) ? $this->special_code : 0);
        $sql.= ($this->fk_unit ? ", fk_unit='".$this->db->escape($this->fk_unit)."'":", fk_unit=null");

        // Multicurrency
        $sql.= ", multicurrency_subprice=".price2num($this->multicurrency_subprice)."";
        $sql.= ", multicurrency_total_ht=".price2num($this->multicurrency_total_ht)."";
        $sql.= ", multicurrency_total_tva=".price2num($this->multicurrency_total_tva)."";
        $sql.= ", multicurrency_total_ttc=".price2num($this->multicurrency_total_ttc)."";

        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::updateline", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result > 0)
        {
            if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
            {
                $result=$this->insertExtraFields();
                if ($result < 0)
                {
                    $error++;
                }
            }

            if (! $error && ! $notrigger)
            {
                global $user;
                // Call trigger
                $result=$this->call_trigger('LINEORDER_SUPPLIER_UPDATE',$user);
                if ($result < 0)
                {
                    $this->db->rollback();
                    return -1;
                }
                // End call triggers
            }

            if (! $error)
            {
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
            $this->error=$this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * 	Delete line in database
     *
     *	@param      int     $notrigger  1=Disable call to triggers
     *	@return     int                 <0 if KO, >0 if OK
     */
    function delete($notrigger)
    {
        global $user;

        $error=0;

        $this->db->begin();

        $sql = 'DELETE FROM '.MAIN_DB_PREFIX."commande_fournisseurdet WHERE rowid=".$this->rowid;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {

            if (!$notrigger)
            {
                // Call trigger
                $result=$this->call_trigger('LINEORDER_SUPPLIER_DELETE',$user);
                if ($result < 0) $error++;
                // End call triggers
            }

            if (!$error)
            {
                $this->db->commit();
                return 1;
            }

            foreach($this->errors as $errmsg)
            {
                dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
                $this->error.=($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        }
        else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
    }
}
