<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2010-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011      Jean Heimburger      <jean@tiaris.info>
 * Copyright (C) 2012-2014 Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2015 Marcos García        <marcosgdf@gmail.com>
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
 *  \file       htdocs/commande/class/commande.class.php
 *  \ingroup    commande
 *  \brief      Fichier des classes de commandes
 */
include_once DOL_DOCUMENT_ROOT.'/core/class/commonorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT .'/margin/lib/margins.lib.php';

/**
 *  Class to manage customers orders
 */
class Commande extends CommonOrder
{
    public $element='commande';
    public $table_element='commande';
    public $table_element_line = 'commandedet';
    public $class_element_line = 'OrderLine';
    public $fk_element = 'fk_commande';
    protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    /**
     * {@inheritdoc}
     */
    protected $table_ref_field = 'ref';

    var $id;

    var $socid;		// Id client
    var $client;		// Objet societe client (a charger par fetch_client)

    var $ref;
    var $ref_client;
    var $ref_ext;
    var $ref_int;
    var $contactid;
    var $fk_project;
	/**
	 * Status of the order. Check the following constants:
	 * - STATUS_CANCELED
	 * - STATUS_DRAFT
	 * - STATUS_ACCEPTED
	 * - STATUS_CLOSED
	 * @var int
	 */
    var $statut;
    var $facturee;		// deprecated
    var $billed;		// billed or not

	/**
	 * Canceled status
	 */
	const STATUS_CANCELED = -1;
	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;
	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;
	/**
	 * Accepted/On process not managed for customer orders
	 */
	const STATUS_ACCEPTED = 2;
	/**
	 * Closed (Sent/Received, billed or not)
	 */
	const STATUS_CLOSED = 3;

    var $brouillon;
    var $cond_reglement_id;
    var $cond_reglement_code;
    var $fk_account;
    var $mode_reglement_id;
    var $mode_reglement_code;
    var $availability_id;
    var $availability_code;
    var $demand_reason_id;
    var $demand_reason_code;
    var $fk_delivery_address;
    var $address;
    var $date;				// Date commande
    var $date_commande;		// Date commande (deprecated)
    var $date_livraison;	// Date livraison souhaitee
    var $shipping_method_id;
    var $fk_remise_except;
    var $remise_percent;
    var $total_ht;			// Total net of tax
    var $total_ttc;			// Total with tax
    var $total_tva;			// Total VAT
    var $total_localtax1;   // Total Local tax 1
    var $total_localtax2;   // Total Local tax 2
    var $remise_absolue;
    var $modelpdf;
    var $info_bits;
    var $rang;
    var $special_code;
    var $source;			// Origin of order
    var $note;				// deprecated
    var $note_private;
    var $note_public;
    var $extraparams=array();

    var $origin;
    var $origin_id;
    var $linked_objects=array();

    var $user_author_id;

	/**
	 * @var OrderLine[]
	 */
	var $lines = array();

	//Incorterms
	var $fk_incoterms;
	var $location_incoterms;
	var $libelle_incoterms;  //Used into tooltip

    // Pour board
    var $nbtodo;
    var $nbtodolate;


     /**
     * ERR Not engouch stock
     */
    const STOCK_NOT_ENOUGH_FOR_ORDER = -3;


    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;

        $this->remise = 0;
        $this->remise_percent = 0;

        $this->products = array();
    }

    /**
	 *  Returns the reference to the following non used Order depending on the active numbering module
	 *  defined into COMMANDE_ADDON
	 *
	 *  @param	Societe		$soc  	Object thirdparty
	 *  @return string      		Order free reference
	 */
    function getNextNumRef($soc)
    {
        global $db, $langs, $conf;
        $langs->load("order");

        if (! empty($conf->global->COMMANDE_ADDON))
        {
        	$mybool=false;

        	$file = $conf->global->COMMANDE_ADDON.".php";
			$classname = $conf->global->COMMANDE_ADDON;

			// Include file with class
			$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir)
			{
                $dir = dol_buildpath($reldir."core/modules/commande/");

                // Load file with numbering class (if found)
                $mybool|=@include_once $dir.$file;
            }

            if (! $mybool)
            {
                dol_print_error('',"Failed to include file ".$file);
                return '';
            }

            $obj = new $classname();
            $numref = $obj->getNextValue($soc,$this);

            if ($numref != "")
            {
            	return $numref;
            }
            else
			{
            	dol_print_error($db,get_class($this)."::getNextNumRef ".$obj->error);
            	return "";
            }
        }
        else
        {
            print $langs->trans("Error")." ".$langs->trans("Error_COMMANDE_ADDON_NotDefined");
            return "";
        }
    }


    /**
     *	Validate order
     *
     *	@param		User	$user     		User making status change
     *	@param		int		$idwarehouse	Id of warehouse to use for stock decrease
     *  @param		int		$notrigger		1=Does not execute triggers, 0= execuete triggers
     *	@return  	int						<=0 if OK, >0 if KO
     */
    function valid($user, $idwarehouse=0, $notrigger=0)
    {
        global $conf,$langs;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $error=0;

        // Protection
        if ($this->statut == self::STATUS_VALIDATED)
        {
            dol_syslog(get_class($this)."::valid no draft status", LOG_WARNING);
            return 0;
        }

        if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->creer))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->order_advance->validate))))
        {
            $this->error='Permission denied';
            dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
            return -1;
        }

        $now=dol_now();

        $this->db->begin();

        // Definition du nom de module de numerotation de commande
        $soc = new Societe($this->db);
        $soc->fetch($this->socid);

        // Class of company linked to order
        $result=$soc->set_as_client();

        // Define new ref
        if (! $error && (preg_match('/^[\(]?PROV/i', $this->ref)))
        {
            $num = $this->getNextNumRef($soc);
        }
        else
		{
            $num = $this->ref;
        }
        $this->newref = $num;

        // Validate
        $sql = "UPDATE ".MAIN_DB_PREFIX."commande";
        $sql.= " SET ref = '".$num."',";
        $sql.= " fk_statut = ".self::STATUS_VALIDATED.",";
        $sql.= " date_valid='".$this->db->idate($now)."',";
        $sql.= " fk_user_valid = ".$user->id;
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::valid()", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if (! $resql)
        {
            dol_print_error($this->db);
            $this->error=$this->db->lasterror();
            $error++;
        }

        if (! $error)
        {
            // If stock is incremented on validate order, we must increment it
            if ($result >= 0 && ! empty($conf->stock->enabled) && $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER == 1)
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
                        // We decrement stock of product (and sub-products)
                        $result=$mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("OrderValidatedInDolibarr",$num));
                        if ($result < 0)
                        {
                        	$error++;
                        	$this->error=$mouvP->error;
                        }
                    }
                    if ($error) break;
                }
            }
        }

        if (! $error && ! $notrigger)
        {
            // Call trigger
            $result=$this->call_trigger('ORDER_VALIDATE',$user);
            if ($result < 0) $error++;
            // End call triggers
        }

        if (! $error)
        {
            $this->oldref = $this->ref;

            // Rename directory if dir was a temporary ref
            if (preg_match('/^[\(]?PROV/i', $this->ref))
            {
            	// On renomme repertoire ($this->ref = ancienne ref, $numfa = nouvelle ref)
                // in order not to lose the attachments
                $oldref = dol_sanitizeFileName($this->ref);
                $newref = dol_sanitizeFileName($num);
                $dirsource = $conf->commande->dir_output.'/'.$oldref;
                $dirdest = $conf->commande->dir_output.'/'.$newref;
                if (file_exists($dirsource))
                {
                    dol_syslog(get_class($this)."::valid() rename dir ".$dirsource." into ".$dirdest);

                    if (@rename($dirsource, $dirdest))
                    {
                        dol_syslog("Rename ok");
                        // Rename docs starting with $oldref with $newref
                        $listoffiles=dol_dir_list($conf->commande->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref,'/'));
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

        // Set new ref and current status
        if (! $error)
        {
            $this->ref = $num;
            $this->statut = self::STATUS_VALIDATED;
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

        // Protection
        if ($this->statut <= self::STATUS_DRAFT)
        {
            return 0;
        }

        if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->creer))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->order_advance->validate))))
        {
            $this->error='Permission denied';
            return -1;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."commande";
        $sql.= " SET fk_statut = ".self::STATUS_DRAFT;
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::set_draft", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            // If stock is decremented on validate order, we must reincrement it
            if (! empty($conf->stock->enabled) && $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER == 1)
            {
                require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
                $langs->load("agenda");

                $num=count($this->lines);
                for ($i = 0; $i < $num; $i++)
                {
                    if ($this->lines[$i]->fk_product > 0)
                    {
                        $mouvP = new MouvementStock($this->db);
                        // We increment stock of product (and sub-products)
                        $result=$mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("OrderBackToDraftInDolibarr",$this->ref));
                        if ($result < 0) { $error++; }
                    }
                }

                if (!$error)
                {
                    $this->statut=self::STATUS_DRAFT;
                    $this->db->commit();
                    return $result;
                }
                else
                {
                    $this->error=$mouvP->error;
                    $this->db->rollback();
                    return $result;
                }
            }

            $this->statut=self::STATUS_DRAFT;
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *	Tag the order as validated (opened)
     *	Function used when order is reopend after being closed.
     *
     *	@param      User	$user       Object user that change status
     *	@return     int         		<0 if KO, 0 if nothing is done, >0 if OK
     */
    function set_reopen($user)
    {
        global $conf,$langs;
        $error=0;

        if ($this->statut != self::STATUS_CLOSED)
        {
        	dol_syslog(get_class($this)."::set_reopen order has not status closed", LOG_WARNING);
            return 0;
        }

        $this->db->begin();

        $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
        $sql.= ' SET fk_statut='.self::STATUS_VALIDATED.', facture=0';
        $sql.= ' WHERE rowid = '.$this->id;

        dol_syslog(get_class($this)."::set_reopen", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            // Call trigger
            $result=$this->call_trigger('ORDER_REOPEN',$user);
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
        	$this->statut = self::STATUS_VALIDATED;
        	$this->billed = 0;
        	$this->facturee = 0; // deprecated

            $this->db->commit();
            return 1;
        }
        else
        {
	        foreach($this->errors as $errmsg)
	        {
		        dol_syslog(get_class($this)."::set_reopen ".$errmsg, LOG_ERR);
		        $this->error.=($this->error?', '.$errmsg:$errmsg);
	        }
	        $this->db->rollback();
	        return -1*$error;
        }
    }

    /**
     *  Close order
     *
     * 	@param      User	$user       Objet user that close
     *	@return		int					<0 if KO, >0 if OK
     */
    function cloture($user)
    {
        global $conf, $langs;

        $error=0;

        if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->creer))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->order_advance->validate)))
        {
            $this->db->begin();

            $now=dol_now();

            $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
            $sql.= ' SET fk_statut = '.self::STATUS_CLOSED.',';
            $sql.= ' fk_user_cloture = '.$user->id.',';
            $sql.= " date_cloture = '".$this->db->idate($now)."'";
            $sql.= ' WHERE rowid = '.$this->id.' AND fk_statut > '.self::STATUS_DRAFT;

            if ($this->db->query($sql))
            {
	            // Call trigger
	            $result=$this->call_trigger('ORDER_CLOSE',$user);
	            if ($result < 0) $error++;
	            // End call triggers

                if (! $error)
                {
                	$this->statut=self::STATUS_CLOSED;

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
                $this->error=$this->db->lasterror();

                $this->db->rollback();
                return -1;
            }
        }
    }

    /**
     * 	Cancel an order
     * 	If stock is decremented on order validation, we must reincrement it
     *
     *	@param	int		$idwarehouse	Id warehouse to use for stock change.
     *	@return	int						<0 if KO, >0 if OK
     */
	function cancel($idwarehouse=-1)
	{
		global $conf,$user,$langs;

		$error=0;

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."commande";
		$sql.= " SET fk_statut = ".self::STATUS_CANCELED;
		$sql.= " WHERE rowid = ".$this->id;
		$sql.= " AND fk_statut = ".self::STATUS_VALIDATED;

		dol_syslog(get_class($this)."::cancel", LOG_DEBUG);
		if ($this->db->query($sql))
		{
			// If stock is decremented on validate order, we must reincrement it
			if (! empty($conf->stock->enabled) && $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER == 1)
			{
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$langs->load("agenda");

				$num=count($this->lines);
				for ($i = 0; $i < $num; $i++)
				{
					if ($this->lines[$i]->fk_product > 0)
					{
						$mouvP = new MouvementStock($this->db);
						// We increment stock of product (and sub-products)
						$result=$mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("OrderCanceledInDolibarr",$this->ref));
						if ($result < 0)
						{
							$error++;
							$this->error=$mouvP->error;
							break;
						}
					}
				}
			}

			if (! $error)
			{
	            // Call trigger
	            $result=$this->call_trigger('ORDER_CANCEL',$user);
	            if ($result < 0) $error++;
	            // End call triggers
			}

			if (! $error)
			{
				$this->statut=self::STATUS_CANCELED;
				$this->db->commit();
				return 1;
			}
			else
			{
				foreach($this->errors as $errmsg)
				{
					dol_syslog(get_class($this)."::cancel ".$errmsg, LOG_ERR);
					$this->error.=($this->error?', '.$errmsg:$errmsg);
				}
				$this->db->rollback();
				return -1*$error;
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
     *	Create order
     *	Note that this->ref can be set or empty. If empty, we will use "(PROV)"
     *
     *	@param		User	$user 		Objet user that make creation
     *	@param		int	$notrigger	Disable all triggers
     *	@return 	int			<0 if KO, >0 if OK
     */
    function create($user, $notrigger=0)
    {
        global $conf,$langs,$mysoc,$hookmanager;
        $error=0;

        // Clean parameters
        $this->brouillon = 1;		// set command as draft

        dol_syslog(get_class($this)."::create user=".$user->id);

        // Check parameters
    	if (! empty($this->ref))	// We check that ref is not already used
    	{
    		$result=self::isExistingObject($this->element, 0, $this->ref);	// Check ref is not yet used
    		if ($result > 0)
    		{
    			$this->error='ErrorRefAlreadyExists';
    			dol_syslog(get_class($this)."::create ".$this->error,LOG_WARNING);
    			$this->db->rollback();
    			return -1;
    		}
    	}

        $soc = new Societe($this->db);
        $result=$soc->fetch($this->socid);
        if ($result < 0)
        {
            $this->error="Failed to fetch company";
            dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
            return -2;
        }
        if (! empty($conf->global->COMMANDE_REQUIRE_SOURCE) && $this->source < 0)
        {
            $this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Source"));
            dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
            return -1;
        }

        // $date_commande is deprecated
        $date = ($this->date_commande ? $this->date_commande : $this->date);

        $now=dol_now();

        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande (";
        $sql.= " ref, fk_soc, date_creation, fk_user_author, fk_projet, date_commande, source, note_private, note_public, ref_ext, ref_client, ref_int";
        $sql.= ", model_pdf, fk_cond_reglement, fk_mode_reglement, fk_account, fk_availability, fk_input_reason, date_livraison, fk_delivery_address";
        $sql.= ", fk_shipping_method";
        $sql.= ", remise_absolue, remise_percent";
        $sql.= ", fk_incoterms, location_incoterms";
        $sql.= ", entity";
        $sql.= ")";
        $sql.= " VALUES ('(PROV)',".$this->socid.", '".$this->db->idate($now)."', ".$user->id;
        $sql.= ", ".($this->fk_project>0?$this->fk_project:"null");
        $sql.= ", '".$this->db->idate($date)."'";
        $sql.= ", ".($this->source>=0 && $this->source != '' ?$this->source:'null');
        $sql.= ", '".$this->db->escape($this->note_private)."'";
        $sql.= ", '".$this->db->escape($this->note_public)."'";
        $sql.= ", ".($this->ref_ext?"'".$this->db->escape($this->ref_ext)."'":"null");
        $sql.= ", ".($this->ref_client?"'".$this->db->escape($this->ref_client)."'":"null");
        $sql.= ", ".($this->ref_int?"'".$this->db->escape($this->ref_int)."'":"null");
        $sql.= ", '".$this->modelpdf."'";
        $sql.= ", ".($this->cond_reglement_id>0?"'".$this->cond_reglement_id."'":"null");
        $sql.= ", ".($this->mode_reglement_id>0?"'".$this->mode_reglement_id."'":"null");
        $sql.= ", ".($this->fk_account>0?$this->fk_account:'NULL');
        $sql.= ", ".($this->availability_id>0?"'".$this->availability_id."'":"null");
        $sql.= ", ".($this->demand_reason_id>0?"'".$this->demand_reason_id."'":"null");
        $sql.= ", ".($this->date_livraison?"'".$this->db->idate($this->date_livraison)."'":"null");
        $sql.= ", ".($this->fk_delivery_address>0?$this->fk_delivery_address:'NULL');
        $sql.= ", ".($this->shipping_method_id>0?$this->shipping_method_id:'NULL');
        $sql.= ", ".($this->remise_absolue>0?$this->remise_absolue:'NULL');
        $sql.= ", ".($this->remise_percent>0?$this->remise_percent:0);
        $sql.= ", ".(int) $this->fk_incoterms;
        $sql.= ", '".$this->db->escape($this->location_incoterms)."'";
        $sql.= ", ".$conf->entity;
        $sql.= ")";

        dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'commande');

            if ($this->id)
            {
                $fk_parent_line=0;
                $num=count($this->lines);

                /*
                 *  Insert products details into db
                 */
                for ($i=0;$i<$num;$i++)
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
                        $this->lines[$i]->info_bits,
                        $this->lines[$i]->fk_remise_except,
                        'HT',
                        0,
                        $this->lines[$i]->date_start,
                        $this->lines[$i]->date_end,
                        $this->lines[$i]->product_type,
                        $this->lines[$i]->rang,
                        $this->lines[$i]->special_code,
                        $fk_parent_line,
                        $this->lines[$i]->fk_fournprice,
                        $this->lines[$i]->pa_ht,
                    	$this->lines[$i]->label,
		    	$this->lines[$i]->array_options
                    );
                    if ($result < 0)
                    {
                    	if ($result != self::STOCK_NOT_ENOUGH_FOR_ORDER)
                    	{
                        	$this->error=$this->db->lasterror();
                        	dol_print_error($this->db);
                    	}
                        $this->db->rollback();
                        return -1;
                    }
                    // Defined the new fk_parent_line
                    if ($result > 0 && $this->lines[$i]->product_type == 9) {
                        $fk_parent_line = $result;
                    }
                }

                // update ref
                $initialref='(PROV'.$this->id.')';
                if (! empty($this->ref)) $initialref=$this->ref;

                $sql = 'UPDATE '.MAIN_DB_PREFIX."commande SET ref='".$this->db->escape($initialref)."' WHERE rowid=".$this->id;
                if ($this->db->query($sql))
                {
                    if ($this->id)
                    {
                    	$this->ref = $initialref;

                        // Add object linked
                        if (is_array($this->linked_objects) && ! empty($this->linked_objects))
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
                        		if ($origin == 'propal' && $origin_id)
                        		{
                        			// On recupere les differents contact interne et externe
                        			$prop = new Propal($this->db);
									$prop->fetch($origin_id);

                        			// We get ids of sales representatives of proposal
                        			$this->userid = $prop->getIdcontact('internal', 'SALESREPFOLL');

                        			if ($this->userid)
                        			{
                        				//On passe le commercial suivi propale en commercial suivi commande
                        				$this->add_contact($this->userid[0], 'SALESREPFOLL', 'internal');
                        			}

                        			// We get ids of customer follower of proposal
                        			$this->contactid = $prop->getIdcontact('external', 'CUSTOMER');

                        			if ($this->contactid)
                        			{
                        				//On passe le contact client suivi propale en contact client suivi commande
                        				$this->add_contact($this->contactid[0], 'CUSTOMER', 'external');
                        			}
                        		}
                        	}
                        }
                    }

                    if (! $error)
                    {
                    	$action='create';

	                    // Actions on extra fields (by external module or standard code)
	                    // FIXME le hook fait double emploi avec le trigger !!
	                    $hookmanager->initHooks(array('orderdao'));
	                    $parameters=array('socid'=>$this->id);
	                    $reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
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
                    }

                    if (! $notrigger)
                    {
			            // Call trigger
			            $result=$this->call_trigger('ORDER_CREATE',$user);
			            if ($result < 0) $error++;
			            // End call triggers
                    }

	                if (!$error) {
		                $this->db->commit();
		                return $this->id;
	                }

	                foreach($this->errors as $errmsg)
	                {
		                dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
		                $this->error.=($this->error?', '.$errmsg:$errmsg);
	                }
	                $this->db->rollback();
	                return -1*$error;

                }
                else
                {
                    $this->db->rollback();
                    return -1;
                }
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
     *	Load an object from its id and create a new one in database
     *
     *	@param		int			$socid			Id of thirdparty
     *	@return		int							New id of clone
     */
    function createFromClone($socid=0)
    {
        global $conf,$user,$langs,$hookmanager;

        $error=0;

        $this->context['createfromclone'] = 'createfromclone';

        $this->db->begin();

		// get extrafields so they will be clone
		foreach($this->lines as $line)
			$line->fetch_optionals($line->rowid);

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
        $this->statut=self::STATUS_DRAFT;

        // Clear fields
        $this->user_author_id     = $user->id;
        $this->user_valid         = '';
        $this->date_creation      = '';
        $this->date_validation    = '';
        $this->ref_client         = '';

        // Set ref
        require_once DOL_DOCUMENT_ROOT ."/core/modules/commande/".$conf->global->COMMANDE_ADDON.'.php';
        $obj = $conf->global->COMMANDE_ADDON;
        $modCommande = new $obj;
        $this->ref = $modCommande->getNextValue($objsoc,$this);


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

            // Call trigger
            $result=$this->call_trigger('ORDER_CLONE',$user);
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
     *  Load an object from a proposal and create a new order into database
     *
     *  @param      Object			$object 	        Object source
     *  @return     int             					<0 if KO, 0 if nothing done, 1 if OK
     */
    function createFromProposal($object)
    {
        global $conf,$user,$langs,$hookmanager;

        $error=0;

        // Signed proposal
        if ($object->statut == 2)
        {
            $this->date_commande = dol_now();
            $this->source = 0;

            $num=count($object->lines);
            for ($i = 0; $i < $num; $i++)
            {
                $line = new OrderLine($this->db);

                $line->libelle           = $object->lines[$i]->libelle;
                $line->label             = $object->lines[$i]->label;
                $line->desc              = $object->lines[$i]->desc;
                $line->price             = $object->lines[$i]->price;
                $line->subprice          = $object->lines[$i]->subprice;
                $line->tva_tx            = $object->lines[$i]->tva_tx;
                $line->localtax1_tx      = $object->lines[$i]->localtax1_tx;
                $line->localtax2_tx      = $object->lines[$i]->localtax2_tx;
                $line->qty               = $object->lines[$i]->qty;
                $line->fk_remise_except  = $object->lines[$i]->fk_remise_except;
                $line->remise_percent    = $object->lines[$i]->remise_percent;
                $line->fk_product        = $object->lines[$i]->fk_product;
                $line->info_bits         = $object->lines[$i]->info_bits;
                $line->product_type      = $object->lines[$i]->product_type;
                $line->rang              = $object->lines[$i]->rang;
                $line->special_code      = $object->lines[$i]->special_code;
                $line->fk_parent_line    = $object->lines[$i]->fk_parent_line;

                $line->date_start      	= $object->lines[$i]->date_start;
                $line->date_end    		= $object->lines[$i]->date_end;

				$line->fk_fournprice	= $object->lines[$i]->fk_fournprice;
				$marginInfos			= getMarginInfos($object->lines[$i]->subprice, $object->lines[$i]->remise_percent, $object->lines[$i]->tva_tx, $object->lines[$i]->localtax1_tx, $object->lines[$i]->localtax2_tx, $object->lines[$i]->fk_fournprice, $object->lines[$i]->pa_ht);
				$line->pa_ht			= $marginInfos[0];
				$line->marge_tx			= $marginInfos[1];
				$line->marque_tx		= $marginInfos[2];

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
            $this->fk_account           = $object->fk_account;
            $this->availability_id      = $object->availability_id;
            $this->demand_reason_id     = $object->demand_reason_id;
            $this->date_livraison       = $object->date_livraison;
            $this->shipping_method_id   = $object->shipping_method_id;
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
            if (is_array($object->other_linked_objects) && ! empty($object->other_linked_objects))
            {
            	$this->linked_objects = array_merge($this->linked_objects, $object->other_linked_objects);
            }

            $ret = $this->create($user);

            if ($ret > 0)
            {
                // Actions hooked (by external module)
                $hookmanager->initHooks(array('orderdao'));

                $parameters=array('objFrom'=>$object);
                $action='';
                $reshook=$hookmanager->executeHooks('createFrom',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
                if ($reshook < 0) $error++;

                if (! $error)
                {
                    // Ne pas passer par la commande provisoire
                    if ($conf->global->COMMANDE_VALID_AFTER_CLOSE_PROPAL == 1)
                    {
                        $this->fetch($ret);
                        $this->valid($user);
                    }
                    return $ret;
                }
                else return -1;
            }
            else return -1;
        }
        else return 0;
    }


    /**
     *	Add an order line into database (linked to product/service or not)
     *
     *	@param      string			$desc            	Description of line
     *	@param      float			$pu_ht    	        Unit price (without tax)
     *	@param      float			$qty             	Quantite
     *	@param      float			$txtva           	Taux de tva force, sinon -1
     *	@param      float			$txlocaltax1		Local tax 1 rate
     *	@param      float			$txlocaltax2		Local tax 2 rate
     *	@param      int				$fk_product      	Id du produit/service predefini
     *	@param      float			$remise_percent  	Pourcentage de remise de la ligne
     *	@param      int				$info_bits			Bits de type de lignes
     *	@param      int				$fk_remise_except	Id remise
     *	@param      string			$price_base_type	HT or TTC
     *	@param      float			$pu_ttc    		    Prix unitaire TTC
     *	@param      int				$date_start       	Start date of the line - Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
     *	@param      int				$date_end         	End date of the line - Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
     *	@param      int				$type				Type of line (0=product, 1=service)
     *	@param      int				$rang             	Position of line
     *	@param		int				$special_code		Special code (also used by externals modules!)
     *	@param		int				$fk_parent_line		Parent line
     *  @param		int				$fk_fournprice		Id supplier price
     *  @param		int				$pa_ht				Buying price (without tax)
     *  @param		string			$label				Label
	 *  @param		array			$array_options		extrafields array
     *	@return     int             					>0 if OK, <0 if KO
     *
     *	@see        add_product
     *
     *	Les parametres sont deja cense etre juste et avec valeurs finales a l'appel
     *	de cette methode. Aussi, pour le taux tva, il doit deja avoir ete defini
     *	par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,produit)
     *	et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
     */
	function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $info_bits=0, $fk_remise_except=0, $price_base_type='HT', $pu_ttc=0, $date_start='', $date_end='', $type=0, $rang=-1, $special_code=0, $fk_parent_line=0, $fk_fournprice=null, $pa_ht=0, $label='',$array_options=0)
    {
    	global $mysoc, $conf, $langs;

        dol_syslog(get_class($this)."::addline commandeid=$this->id, desc=$desc, pu_ht=$pu_ht, qty=$qty, txtva=$txtva, fk_product=$fk_product, remise_percent=$remise_percent, info_bits=$info_bits, fk_remise_except=$fk_remise_except, price_base_type=$price_base_type, pu_ttc=$pu_ttc, date_start=$date_start, date_end=$date_end, type=$type", LOG_DEBUG);

        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

        // Clean parameters
        if (empty($remise_percent)) $remise_percent=0;
        if (empty($qty)) $qty=0;
        if (empty($info_bits)) $info_bits=0;
        if (empty($rang)) $rang=0;
        if (empty($txtva)) $txtva=0;
        if (empty($txlocaltax1)) $txlocaltax1=0;
        if (empty($txlocaltax2)) $txlocaltax2=0;
        if (empty($fk_parent_line) || $fk_parent_line < 0) $fk_parent_line=0;

        $remise_percent=price2num($remise_percent);
        $qty=price2num($qty);
        $pu_ht=price2num($pu_ht);
        $pu_ttc=price2num($pu_ttc);
    	$pa_ht=price2num($pa_ht);
        $txtva = price2num($txtva);
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
        $label=trim($label);
        $desc=trim($desc);

        // Check parameters
        if ($type < 0) return -1;

        if ($this->statut == self::STATUS_DRAFT)
        {
            $this->db->begin();

            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

            $localtaxes_type=getLocalTaxesFromRate($txtva,0,$this->thirdparty,$mysoc);

            $tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type,'', $localtaxes_type);
            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];
            $total_localtax1 = $tabprice[9];
            $total_localtax2 = $tabprice[10];

            // Rang to use
            $rangtouse = $rang;
            if ($rangtouse == -1)
            {
                $rangmax = $this->line_max($fk_parent_line);
                $rangtouse = $rangmax + 1;
            }

			$product_type=$type;
			if (!empty($fk_product))
			{
				$product=new Product($this->db);
				$result=$product->fetch($fk_product);
				$product_type=$product->type;

				if($conf->global->STOCK_MUST_BE_ENOUGH_FOR_ORDER && $product_type == 0 && $product->stock_reel < $qty)
				{
					$this->error=$langs->trans('ErrorStockIsNotEnough');
					dol_syslog(get_class($this)."::addline error=Product ".$product->ref.": ".$this->error, LOG_ERR);
					$this->db->rollback();
					return self::STOCK_NOT_ENOUGH_FOR_ORDER;
				}
			}

            // TODO A virer
            // Anciens indicateurs: $price, $remise (a ne plus utiliser)
            $price = $pu;
            $remise = 0;
            if ($remise_percent > 0)
            {
                $remise = round(($pu * $remise_percent / 100), 2);
                $price = $pu - $remise;
            }

            // Insert line
            $this->line=new OrderLine($this->db);

            $this->line->context = $this->context;

            $this->line->fk_commande=$this->id;
            $this->line->label=$label;
            $this->line->desc=$desc;
            $this->line->qty=$qty;
            $this->line->tva_tx=$txtva;
            $this->line->localtax1_tx=$txlocaltax1;
            $this->line->localtax2_tx=$txlocaltax2;
			$this->line->localtax1_type = $localtaxes_type[0];
			$this->line->localtax2_type = $localtaxes_type[2];
            $this->line->fk_product=$fk_product;
			$this->line->product_type=$product_type;
            $this->line->fk_remise_except=$fk_remise_except;
            $this->line->remise_percent=$remise_percent;
            $this->line->subprice=$pu_ht;
            $this->line->rang=$rangtouse;
            $this->line->info_bits=$info_bits;
            $this->line->total_ht=$total_ht;
            $this->line->total_tva=$total_tva;
            $this->line->total_localtax1=$total_localtax1;
            $this->line->total_localtax2=$total_localtax2;
            $this->line->total_ttc=$total_ttc;
            $this->line->product_type=$type;
            $this->line->special_code=$special_code;
            $this->line->fk_parent_line=$fk_parent_line;

            $this->line->date_start=$date_start;
            $this->line->date_end=$date_end;

			// infos marge
			if (!empty($fk_product) && empty($fk_fournprice) && empty($pa_ht)) {
			    // by external module, take lowest buying price
			    include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
			    $productFournisseur = new ProductFournisseur($this->db);
			    $productFournisseur->find_min_price_product_fournisseur($fk_product);
			    $this->line->fk_fournprice = $productFournisseur->product_fourn_price_id;
			} else {
			    $this->line->fk_fournprice = $fk_fournprice;
			}
			$this->line->pa_ht = $pa_ht;

            // TODO Ne plus utiliser
            $this->line->price=$price;
            $this->line->remise=$remise;

			if (is_array($array_options) && count($array_options)>0) {
				$this->line->array_options=$array_options;
			}

            $result=$this->line->insert();
            if ($result > 0)
            {
                // Reorder if child line
                if (! empty($fk_parent_line)) $this->line_order(true,'DESC');

                // Mise a jour informations denormalisees au niveau de la commande meme
                $result=$this->update_price(1,'auto');	// This method is designed to add line from user input so total calculation must be done using 'auto' mode.
                if ($result > 0)
                {
                    $this->db->commit();
                    return $this->line->rowid;
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
                dol_syslog(get_class($this)."::addline error=".$this->error, LOG_ERR);
                $this->db->rollback();
                return -2;
            }
        }
    }


    /**
     *	Add line into array
     *	$this->client must be loaded
     *
     *	@param		int				$idproduct			Product Id
     *	@param		float			$qty				Quantity
     *	@param		float			$remise_percent		Product discount relative
     * 	@param    	int		$date_start         Start date of the line - Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
     * 	@param    	int		$date_end           End date of the line - Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
     * 	@return    	void
     *
     *	TODO	Remplacer les appels a cette fonction par generation objet Ligne
     *			insere dans tableau $this->products
     */
    function add_product($idproduct, $qty, $remise_percent=0.0, $date_start='', $date_end='')
    {
        global $conf, $mysoc;

        if (! $qty) $qty = 1;

        if ($idproduct > 0)
        {
            $prod=new Product($this->db);
            $prod->fetch($idproduct);

            $tva_tx = get_default_tva($mysoc,$this->client,$prod->id);
            $localtax1_tx=get_localtax($tva_tx,1,$this->client);
            $localtax2_tx=get_localtax($tva_tx,2,$this->client);
            // multiprix
            if($conf->global->PRODUIT_MULTIPRICES && $this->client->price_level)
            $price = $prod->multiprices[$this->client->price_level];
            else
            $price = $prod->price;

            $line=new OrderLine($this->db);

            $line->context = $this->context;

            $line->fk_product=$idproduct;
            $line->desc=$prod->description;
            $line->qty=$qty;
            $line->subprice=$price;
            $line->remise_percent=$remise_percent;
            $line->tva_tx=$tva_tx;
            $line->localtax1_tx=$localtax1_tx;
            $line->localtax2_tx=$localtax2_tx;
            $line->ref=$prod->ref;
            $line->libelle=$prod->libelle;
            $line->product_desc=$prod->description;

            // Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
            // Save the start and end date of the line in the object
            if ($date_start) { $line->date_start = $date_start; }
            if ($date_end)   { $line->date_end = $date_end; }

            $this->lines[] = $line;

            /** POUR AJOUTER AUTOMATIQUEMENT LES SOUSPRODUITS a LA COMMANDE
             if (! empty($conf->global->PRODUIT_SOUSPRODUITS))
             {
             $prod = new Product($this->db);
             $prod->fetch($idproduct);
             $prod -> get_sousproduits_arbo ();
             $prods_arbo = $prod->get_each_prod();
             if(count($prods_arbo) > 0)
             {
             foreach($prods_arbo as $key => $value)
             {
             // print "id : ".$value[1].' :qty: '.$value[0].'<br>';
             if(! in_array($value[1],$this->products))
             $this->add_product($value[1], $value[0]);

             }
             }

             }
             **/
        }
    }


    /**
     *	Get object and lines from database
     *
     *	@param      int			$id       		Id of object to load
     * 	@param		string		$ref			Ref of object
     * 	@param		string		$ref_ext		External reference of object
     * 	@param		string		$ref_int		Internal reference of other object
     *	@return     int         				>0 if OK, <0 if KO, 0 if not found
     */
    function fetch($id, $ref='', $ref_ext='', $ref_int='')
    {
        global $conf;

        // Check parameters
        if (empty($id) && empty($ref) && empty($ref_ext) && empty($ref_int)) return -1;

        $sql = 'SELECT c.rowid, c.date_creation, c.ref, c.fk_soc, c.fk_user_author, c.fk_statut';
        $sql.= ', c.amount_ht, c.total_ht, c.total_ttc, c.tva as total_tva, c.localtax1 as total_localtax1, c.localtax2 as total_localtax2, c.fk_cond_reglement, c.fk_mode_reglement, c.fk_availability, c.fk_input_reason';
        $sql.= ', c.fk_account';
        $sql.= ', c.date_commande';
        $sql.= ', c.date_livraison';
        $sql.= ', c.fk_shipping_method';
        $sql.= ', c.fk_projet, c.remise_percent, c.remise, c.remise_absolue, c.source, c.facture as billed';
        $sql.= ', c.note_private, c.note_public, c.ref_client, c.ref_ext, c.ref_int, c.model_pdf, c.fk_delivery_address, c.extraparams';
        $sql.= ', c.fk_incoterms, c.location_incoterms';
        $sql.= ", i.libelle as libelle_incoterms";
        $sql.= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
        $sql.= ', cr.code as cond_reglement_code, cr.libelle as cond_reglement_libelle, cr.libelle_facture as cond_reglement_libelle_doc';
        $sql.= ', ca.code as availability_code';
        $sql.= ', dr.code as demand_reason_code';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'commande as c';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as cr ON (c.fk_cond_reglement = cr.rowid)';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON (c.fk_mode_reglement = p.id)';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_availability as ca ON (c.fk_availability = ca.rowid)';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_input_reason as dr ON (c.fk_input_reason = ca.rowid)';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON c.fk_incoterms = i.rowid';
        $sql.= " WHERE c.entity = ".$conf->entity;
        if ($id)   	  $sql.= " AND c.rowid=".$id;
        if ($ref)     $sql.= " AND c.ref='".$this->db->escape($ref)."'";
        if ($ref_ext) $sql.= " AND c.ref_ext='".$this->db->escape($ref_ext)."'";
        if ($ref_int) $sql.= " AND c.ref_int='".$this->db->escape($ref_int)."'";

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            $obj = $this->db->fetch_object($result);
            if ($obj)
            {
                $this->id					= $obj->rowid;
                $this->ref					= $obj->ref;
                $this->ref_client			= $obj->ref_client;
                $this->ref_ext				= $obj->ref_ext;
                $this->ref_int				= $obj->ref_int;
                $this->socid				= $obj->fk_soc;
                $this->statut				= $obj->fk_statut;
                $this->user_author_id		= $obj->fk_user_author;
                $this->total_ht				= $obj->total_ht;
                $this->total_tva			= $obj->total_tva;
                $this->total_localtax1		= $obj->total_localtax1;
                $this->total_localtax2		= $obj->total_localtax2;
                $this->total_ttc			= $obj->total_ttc;
                $this->date					= $this->db->jdate($obj->date_commande);
                $this->date_commande		= $this->db->jdate($obj->date_commande);
                $this->remise				= $obj->remise;
                $this->remise_percent		= $obj->remise_percent;
                $this->remise_absolue		= $obj->remise_absolue;
                $this->source				= $obj->source;
                $this->facturee				= $obj->billed;			// deprecated
                $this->billed				= $obj->billed;
                $this->note					= $obj->note_private;	// deprecated
                $this->note_private			= $obj->note_private;
                $this->note_public			= $obj->note_public;
                $this->fk_project			= $obj->fk_projet;
                $this->modelpdf				= $obj->model_pdf;
                $this->mode_reglement_id	= $obj->fk_mode_reglement;
                $this->mode_reglement_code	= $obj->mode_reglement_code;
                $this->mode_reglement		= $obj->mode_reglement_libelle;
                $this->cond_reglement_id	= $obj->fk_cond_reglement;
                $this->cond_reglement_code	= $obj->cond_reglement_code;
                $this->cond_reglement		= $obj->cond_reglement_libelle;
                $this->cond_reglement_doc	= $obj->cond_reglement_libelle_doc;
                $this->fk_account           = $obj->fk_account;
                $this->availability_id		= $obj->fk_availability;
                $this->availability_code	= $obj->availability_code;
                $this->demand_reason_id		= $obj->fk_input_reason;
                $this->demand_reason_code	= $obj->demand_reason_code;
                $this->date_livraison		= $this->db->jdate($obj->date_livraison);
                $this->shipping_method_id   = ($obj->fk_shipping_method>0)?$obj->fk_shipping_method:null;
                $this->fk_delivery_address	= $obj->fk_delivery_address;

				//Incoterms
				$this->fk_incoterms = $obj->fk_incoterms;
				$this->location_incoterms = $obj->location_incoterms;
				$this->libelle_incoterms = $obj->libelle_incoterms;

                $this->extraparams			= (array) json_decode($obj->extraparams, true);

                $this->lines				= array();

                if ($this->statut == self::STATUS_DRAFT) $this->brouillon = 1;

                // Retreive all extrafield for invoice
                // fetch optionals attributes and labels
                require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
                $extrafields=new ExtraFields($this->db);
                $extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
               	$this->fetch_optionals($this->id,$extralabels);

                $this->db->free($result);

                /*
                 * Lines
                 */
                $result=$this->fetch_lines();
                if ($result < 0)
                {
                    return -3;
                }
                return 1;
            }
            else
            {
                $this->error='Order with id '.$id.' not found sql='.$sql;
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
     *	Adding line of fixed discount in the order in DB
     *
     *	@param     int	$idremise			Id de la remise fixe
     *	@return    int          			>0 si ok, <0 si ko
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

            $line = new OrderLine($this->db);

            $line->fk_commande=$this->id;
            $line->fk_remise_except=$remise->id;
            $line->desc=$remise->description;   	// Description ligne
            $line->tva_tx=$remise->tva_tx;
            $line->subprice=-$remise->amount_ht;
            $line->price=-$remise->amount_ht;
            $line->fk_product=0;					// Id produit predefini
            $line->qty=1;
            $line->remise=0;
            $line->remise_percent=0;
            $line->rang=-1;
            $line->info_bits=2;

            $line->total_ht  = -$remise->amount_ht;
            $line->total_tva = -$remise->amount_tva;
            $line->total_ttc = -$remise->amount_ttc;

            $result=$line->insert();
            if ($result > 0)
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
                    return -1;
                }
            }
            else
            {
                $this->error=$line->error;
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $this->db->rollback();
            return -2;
        }
    }


    /**
     *	Load array lines
     *
     *	@param		int		$only_product	Return only physical products
     *	@return		int						<0 if KO, >0 if OK
     */
    function fetch_lines($only_product=0)
    {
        $this->lines=array();

        $sql = 'SELECT l.rowid, l.fk_product, l.fk_parent_line, l.product_type, l.fk_commande, l.label as custom_label, l.description, l.price, l.qty, l.tva_tx,';
        $sql.= ' l.localtax1_tx, l.localtax2_tx, l.fk_remise_except, l.remise_percent, l.subprice, l.fk_product_fournisseur_price as fk_fournprice, l.buy_price_ht as pa_ht, l.rang, l.info_bits, l.special_code,';
        $sql.= ' l.total_ht, l.total_ttc, l.total_tva, l.total_localtax1, l.total_localtax2, l.date_start, l.date_end,';
        $sql.= ' p.ref as product_ref, p.description as product_desc, p.fk_product_type, p.label as product_label';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'commandedet as l';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON (p.rowid = l.fk_product)';
        $sql.= ' WHERE l.fk_commande = '.$this->id;
        if ($only_product) $sql .= ' AND p.fk_product_type = 0';
        $sql .= ' ORDER BY l.rang';

        dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);

            $i = 0;
            while ($i < $num)
            {
                $objp = $this->db->fetch_object($result);

                $line = new OrderLine($this->db);

                $line->rowid            = $objp->rowid;
                $line->id               = $objp->rowid;
                $line->fk_commande      = $objp->fk_commande;
                $line->commande_id      = $objp->fk_commande;
                $line->label            = $objp->custom_label;
                $line->desc             = $objp->description;
                $line->product_type     = $objp->product_type;
                $line->qty              = $objp->qty;
                $line->tva_tx           = $objp->tva_tx;
                $line->localtax1_tx     = $objp->localtax1_tx;
                $line->localtax2_tx     = $objp->localtax2_tx;
                $line->total_ht         = $objp->total_ht;
                $line->total_ttc        = $objp->total_ttc;
                $line->total_tva        = $objp->total_tva;
                $line->total_localtax1  = $objp->total_localtax1;
                $line->total_localtax2  = $objp->total_localtax2;
                $line->subprice         = $objp->subprice;
                $line->fk_remise_except = $objp->fk_remise_except;
                $line->remise_percent   = $objp->remise_percent;
                $line->price            = $objp->price;
                $line->fk_product       = $objp->fk_product;
				$line->fk_fournprice 	= $objp->fk_fournprice;
		      	$marginInfos			= getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $line->fk_fournprice, $objp->pa_ht);
		   		$line->pa_ht 			= $marginInfos[0];
		    	$line->marge_tx			= $marginInfos[1];
		     	$line->marque_tx		= $marginInfos[2];
                $line->rang             = $objp->rang;
                $line->info_bits        = $objp->info_bits;
                $line->special_code		= $objp->special_code;
                $line->fk_parent_line	= $objp->fk_parent_line;

                $line->ref				= $objp->product_ref;
                $line->product_ref		= $objp->product_ref;
                $line->libelle			= $objp->product_label;
                $line->product_label	= $objp->product_label;
                $line->product_desc     = $objp->product_desc;
                $line->fk_product_type  = $objp->fk_product_type;	// Produit ou service

                $line->date_start       = $this->db->jdate($objp->date_start);
                $line->date_end         = $this->db->jdate($objp->date_end);

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
     *	Return number of line with type product.
     *
     *	@return		int		<0 if KO, Nbr of product lines if OK
     */
    function getNbOfProductsLines()
    {
        $nb=0;
        foreach($this->lines as $line)
        {
            if ($line->product_type == 0) $nb++;
        }
        return $nb;
    }

    /**
     *	Return number of line with type service.
     *
     *	@return		int		<0 if KO, Nbr of service lines if OK
     */
    function getNbOfServicesLines()
    {
        $nb=0;
        foreach($this->lines as $line)
        {
            if ($line->product_type == 1) $nb++;
        }
        return $nb;
    }

    /**
     *	Load array this->expeditions of nb of products sent by line in order
     *
     *	@param      int		$filtre_statut      Filter on status
     * 	@return     int                			<0 if KO, Nb of lines found if OK
     *
     *	TODO deprecated, move to Shipping class
     */
    function loadExpeditions($filtre_statut=-1)
    {
        $this->expeditions = array();

        $sql = 'SELECT cd.rowid, cd.fk_product,';
        $sql.= ' sum(ed.qty) as qty';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'expeditiondet as ed,';
        if ($filtre_statut >= 0) $sql.= ' '.MAIN_DB_PREFIX.'expedition as e,';
        $sql.= ' '.MAIN_DB_PREFIX.'commandedet as cd';
        $sql.= ' WHERE';
        if ($filtre_statut >= 0) $sql.= ' ed.fk_expedition = e.rowid AND';
        $sql.= ' ed.fk_origin_line = cd.rowid';
        $sql.= ' AND cd.fk_commande =' .$this->id;
        if ($filtre_statut >= 0) $sql.=' AND e.fk_statut >= '.$filtre_statut;
        $sql.= ' GROUP BY cd.rowid, cd.fk_product';
        //print $sql;

        dol_syslog(get_class($this)."::loadExpeditions", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                $this->expeditions[$obj->rowid] = $obj->qty;
                $i++;
            }
            $this->db->free();
            return $num;
        }
        else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }

    }

    /**
     * Returns a array with expeditions lines number
     *
     * @return	int		Nb of shipments
     *
     * TODO deprecated, move to Shipping class
     */
    function nb_expedition()
    {
        $sql = 'SELECT count(*)';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'expedition as e';
        $sql.= ', '.MAIN_DB_PREFIX.'element_element as el';
        $sql.= ' WHERE el.fk_source = '.$this->id;
        $sql.= " AND el.fk_target = e.rowid";
        $sql.= " AND el.targettype = 'shipping'";

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $row = $this->db->fetch_row($resql);
            return $row[0];
        }
        else dol_print_error($this->db);
    }

    /**
     *	Return a array with sendings by line
     *
     *	@param      int		$filtre_statut      Filtre sur statut
     *	@return     int                 		0 si OK, <0 si KO
     *
     *	TODO  deprecated, move to Shipping class
     */
    function livraison_array($filtre_statut=self::STATUS_CANCELED)
    {
        $delivery = new Livraison($this->db);
        $deliveryArray = $delivery->livraison_array($filtre_statut);
        return $deliveryArray;
    }

    /**
     *	Return a array with the pending stock by product
     *
     *	@param      int		$filtre_statut      Filtre sur statut
     *	@return     int                 		0 si OK, <0 si KO
     *
     *	TODO		FONCTION NON FINIE A FINIR
     */
    function stock_array($filtre_statut=self::STATUS_CANCELED)
    {
        $this->stocks = array();

        // Tableau des id de produit de la commande
		$array_of_product=array();

        // Recherche total en stock pour chaque produit
        // TODO $array_of_product est défini vide juste au dessus !!
        if (count($array_of_product))
        {
            $sql = "SELECT fk_product, sum(ps.reel) as total";
            $sql.= " FROM ".MAIN_DB_PREFIX."product_stock as ps";
            $sql.= " WHERE ps.fk_product IN (".join(',',$array_of_product).")";
            $sql.= ' GROUP BY fk_product ';
            $result = $this->db->query($sql);
            if ($result)
            {
                $num = $this->db->num_rows($result);
                $i = 0;
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($result);
                    $this->stocks[$obj->fk_product] = $obj->total;
                    $i++;
                }
                $this->db->free();
            }
        }
        return 0;
    }

    /**
     *  Delete an order line
     *
     *  @param      int		$lineid		Id of line to delete
     *  @return     int        		 	>0 if OK, 0 if nothing to do, <0 if KO
     */
    function deleteline($lineid)
    {
        global $user;

        if ($this->statut == self::STATUS_DRAFT)
        {
            $this->db->begin();

            $sql = "SELECT fk_product, qty";
            $sql.= " FROM ".MAIN_DB_PREFIX."commandedet";
            $sql.= " WHERE rowid = ".$lineid;

            $result = $this->db->query($sql);
            if ($result)
            {
                $obj = $this->db->fetch_object($result);

                if ($obj)
                {
                    $product = new Product($this->db);
                    $product->id = $obj->fk_product;

                    // Delete line
                    $line = new OrderLine($this->db);

                    // For triggers
                    $line->fetch($lineid);

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
                        $this->error=$line->error;
                        return -1;
                    }
                }
                else
                {
                    $this->db->rollback();
                    return 0;
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
            return -1;
        }
    }

    /**
     * 	Applique une remise relative
     *
     * 	@param     	User		$user		User qui positionne la remise
     * 	@param     	float		$remise		Discount (percent)
     *	@return		int 					<0 if KO, >0 if OK
     */
    function set_remise($user, $remise)
    {
        $remise=trim($remise)?trim($remise):0;

        if ($user->rights->commande->creer)
        {
            $remise=price2num($remise);

            $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
            $sql.= ' SET remise_percent = '.$remise;
            $sql.= ' WHERE rowid = '.$this->id.' AND fk_statut = '.self::STATUS_DRAFT.' ;';

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
     * 		Applique une remise absolue
     *
     * 		@param     	User		$user 		User qui positionne la remise
     * 		@param     	float		$remise		Discount
     *		@return		int 					<0 if KO, >0 if OK
     */
    function set_remise_absolue($user, $remise)
    {
        $remise=trim($remise)?trim($remise):0;

        if ($user->rights->commande->creer)
        {
            $remise=price2num($remise);

            $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
            $sql.= ' SET remise_absolue = '.$remise;
            $sql.= ' WHERE rowid = '.$this->id.' AND fk_statut = '.self::STATUS_DRAFT.' ;';

            dol_syslog(get_class($this)."::set_remise_absolue", LOG_DEBUG);

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
     *	Set the order date
     *
     *	@param      User		$user       Object user making change
     *	@param      int	$date		Date
     *	@return     int         			<0 if KO, >0 if OK
     */
    function set_date($user, $date)
    {
        if ($user->rights->commande->creer)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande";
            $sql.= " SET date_commande = ".($date ? $this->db->idate($date) : 'null');
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = ".self::STATUS_DRAFT;

            dol_syslog(get_class($this)."::set_date",LOG_DEBUG);
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $this->date = $date;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                return -1;
            }
        }
        else
        {
            return -2;
        }
    }

    /**
     *	Set the planned delivery date
     *
     *	@param      User			$user        		Objet utilisateur qui modifie
     *	@param      int		$date_livraison     Date de livraison
     *	@return     int         						<0 si ko, >0 si ok
     */
    function set_date_livraison($user, $date_livraison)
    {
        if ($user->rights->commande->creer)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande";
            $sql.= " SET date_livraison = ".($date_livraison ? "'".$this->db->idate($date_livraison)."'" : 'null');
            $sql.= " WHERE rowid = ".$this->id;

            dol_syslog(get_class($this)."::set_date_livraison", LOG_DEBUG);
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $this->date_livraison = $date_livraison;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                return -1;
            }
        }
        else
        {
            return -2;
        }
    }

    /**
     *	Set availability
     *
     *	@param      User	$user		Object user making change
     *	@param      int		$id			If of availability delay
     *	@return     int           		<0 if KO, >0 if OK
     */
    function set_availability($user, $id)
    {
        if ($user->rights->commande->creer)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande ";
            $sql.= " SET fk_availability = '".$id."'";
            $sql.= " WHERE rowid = ".$this->id;

            if ($this->db->query($sql))
            {
                $this->fk_availability = $id;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dol_syslog(get_class($this)."::set_availability Erreur SQL");
                return -1;
            }
        }
    }

    /**
     *	Set source of demand
     *
     *	@param      User	$user		  	Object user making change
     *	@param      int		$id				Id of source
     *	@return     int           			<0 if KO, >0 if OK
     */
    function set_demand_reason($user, $id)
    {
        if ($user->rights->commande->creer)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande ";
            $sql.= " SET fk_input_reason = '".$id."'";
            $sql.= " WHERE rowid = ".$this->id;

            if ($this->db->query($sql))
            {
                $this->fk_input_reason = $id;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dol_syslog(get_class($this)."::set_demand_reason Erreur SQL");
                return -1;
            }
        }
    }

    /**
     *  Return list of orders (eventuelly filtered on a user) into an array
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
    function liste_array($shortlist=0, $draft=0, $excluser='', $socid=0, $limit=0, $offset=0, $sortfield='c.date_commande', $sortorder='DESC')
    {
        global $conf,$user;

        $ga = array();

        $sql = "SELECT s.rowid, s.nom as name, s.client,";
        $sql.= " c.rowid as cid, c.ref";
        if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", sc.fk_soc, sc.fk_user";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
		if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE c.entity = ".$conf->entity;
        $sql.= " AND c.fk_soc = s.rowid";
        if (! $user->rights->societe->client->voir && ! $socid) //restriction
        {
        	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        }
        if ($socid) $sql.= " AND s.rowid = ".$socid;
        if ($draft) $sql.= " AND c.fk_statut = ".self::STATUS_DRAFT;
        if (is_object($excluser)) $sql.= " AND c.fk_user_author <> ".$excluser->id;
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
                    	$ga[$obj->cid] = $obj->ref;
                    }
                    else if ($shortlist == 2)
                    {
                    	$ga[$obj->cid] = $obj->ref.' ('.$obj->name.')';
                    }
                    else
					{
                    	$ga[$i]['id']	= $obj->cid;
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
     *	Change le delai de livraison
     *
     *	@param      int		$availability_id	Id du nouveau mode
     *	@return     int         				>0 if OK, <0 if KO
     */
    function availability($availability_id)
    {
        dol_syslog('Commande::availability('.$availability_id.')');
        if ($this->statut >= self::STATUS_DRAFT)
        {
            $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
            $sql .= ' SET fk_availability = '.$availability_id;
            $sql .= ' WHERE rowid='.$this->id;
            if ( $this->db->query($sql) )
            {
                $this->availability_id = $availability_id;
                return 1;
            }
            else
            {
                dol_syslog('Commande::availability Erreur '.$sql.' - '.$this->db->error(), LOG_ERR);
                $this->error=$this->db->lasterror();
                return -1;
            }
        }
        else
        {
            dol_syslog('Commande::availability, etat facture incompatible', LOG_ERR);
            $this->error='Etat commande incompatible '.$this->statut;
            return -2;
        }
    }

    /**
     *	Change la source de la demande
     *
     *  @param      int		$demand_reason_id	Id of new demand
     *  @return     int        			 		>0 if ok, <0 if ko
     */
    function demand_reason($demand_reason_id)
    {
        dol_syslog('Commande::demand_reason('.$demand_reason_id.')');
        if ($this->statut >= self::STATUS_DRAFT)
        {
            $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
            $sql .= ' SET fk_input_reason = '.$demand_reason_id;
            $sql .= ' WHERE rowid='.$this->id;
            if ( $this->db->query($sql) )
            {
                $this->demand_reason_id = $demand_reason_id;
                return 1;
            }
            else
            {
                dol_syslog('Commande::demand_reason Erreur '.$sql.' - '.$this->db->error(), LOG_ERR);
                $this->error=$this->db->lasterror();
                return -1;
            }
        }
        else
        {
            dol_syslog('Commande::demand_reason, etat facture incompatible', LOG_ERR);
            $this->error='Etat commande incompatible '.$this->statut;
            return -2;
        }
    }

    /**
     *	Set customer ref
     *
     *	@param      User	$user           User that make change
     *	@param      string	$ref_client     Customer ref
     *	@return     int             		<0 if KO, >0 if OK
     */
    function set_ref_client($user, $ref_client)
    {
        if ($user->rights->commande->creer)
        {
            dol_syslog(get_class($this).'::set_ref_client this->id='.$this->id.', ref_client='.$ref_client);

            $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande SET';
            $sql.= ' ref_client = '.(empty($ref_client) ? 'NULL' : '\''.$this->db->escape($ref_client).'\'');
            $sql.= ' WHERE rowid = '.$this->id;

            if ($this->db->query($sql) )
            {
                $this->ref_client = $ref_client;
                return 1;
            }
            else
            {
                $this->error=$this->db->lasterror();
                return -2;
            }
        }
        else
        {
            return -1;
        }
    }

	/**
	 * Classify the order as invoiced
	 *
	 * @return     int     <0 if ko, >0 if ok
	 */
	function classifyBilled()
	{
		global $conf, $user, $langs;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande SET facture = 1';
		$sql.= ' WHERE rowid = '.$this->id.' AND fk_statut > '.self::STATUS_DRAFT;

		dol_syslog(get_class($this)."::classifyBilled", LOG_DEBUG);
		if ($this->db->query($sql))
		{
            // Call trigger
            $result=$this->call_trigger('ORDER_CLASSIFY_BILLED',$user);
            if ($result < 0) $error++;
            // End call triggers

			if (! $error)
			{
				$this->facturee=1; // deprecated
				$this->billed=1;

				$this->db->commit();
				return 1;
			}
			else
			{
				foreach($this->errors as $errmsg)
				{
					dol_syslog(get_class($this)."::classifyBilled ".$errmsg, LOG_ERR);
					$this->error.=($this->error?', '.$errmsg:$errmsg);
				}
				$this->db->rollback();
				return -1*$error;
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
	 * Classify the order as invoiced
	 *
	 * @return     int     <0 if ko, >0 if ok
	 * @deprecated
	 */
	function classer_facturee()
	{
		return $this->classifyBilled();
	}


    /**
     *  Update a line in database
     *
     *  @param    	int				$rowid            	Id of line to update
     *  @param    	string			$desc             	Description de la ligne
     *  @param    	float			$pu               	Prix unitaire
     *  @param    	float			$qty              	Quantity
     *  @param    	float			$remise_percent   	Pourcentage de remise de la ligne
     *  @param    	float			$txtva           	Taux TVA
     * 	@param		float			$txlocaltax1		Local tax 1 rate
     *  @param		float			$txlocaltax2		Local tax 2 rate
     *  @param    	string			$price_base_type	HT or TTC
     *  @param    	int				$info_bits        	Miscellaneous informations on line
     *  @param    	int		$date_start        	Start date of the line
     *  @param    	int		$date_end          	End date of the line
     * 	@param		int				$type				Type of line (0=product, 1=service)
     * 	@param		int				$fk_parent_line		Id of parent line (0 in most cases, used by modules adding sublevels into lines).
     * 	@param		int				$skip_update_total	Keep fields total_xxx to 0 (used for special lines by some modules)
     *  @param		int				$fk_fournprice		Id of origin supplier price
     *  @param		int				$pa_ht				Price (without tax) of product when it was bought
     *  @param		string			$label				Label
     *  @param		int				$special_code		Special code (also used by externals modules!)
	 *  @param		array			$array_options		extrafields array
     *  @return   	int              					< 0 if KO, > 0 if OK
     */
	function updateline($rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1=0.0,$txlocaltax2=0.0, $price_base_type='HT', $info_bits=0, $date_start='', $date_end='', $type=0, $fk_parent_line=0, $skip_update_total=0, $fk_fournprice=null, $pa_ht=0, $label='', $special_code=0, $array_options=0)
    {
        global $conf, $mysoc;

        dol_syslog(get_class($this)."::updateline $rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, $price_base_type, $info_bits, $date_start, $date_end, $type");
        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

        if (! empty($this->brouillon))
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
            if (empty($special_code) || $special_code == 3) $special_code=0;
            $remise_percent=price2num($remise_percent);
            $qty=price2num($qty);
            $pu = price2num($pu);
      		$pa_ht=price2num($pa_ht);
            $txtva=price2num($txtva);
            $txlocaltax1=price2num($txlocaltax1);
            $txlocaltax2=price2num($txlocaltax2);

            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

            $localtaxes_type=getLocalTaxesFromRate($txtva,0,$this->thirdparty, $mysoc);

            $tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, '', $localtaxes_type);
            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];
            $total_localtax1 = $tabprice[9];
            $total_localtax2 = $tabprice[10];

            // Anciens indicateurs: $price, $subprice, $remise (a ne plus utiliser)
            $price = $pu;
            $subprice = $pu;
            $remise = 0;
            if ($remise_percent > 0)
            {
                $remise = round(($pu * $remise_percent / 100),2);
                $price = ($pu - $remise);
            }

            // Update line
            $this->line=new OrderLine($this->db);

            $this->line->context = $this->context;

            // Stock previous line records
            $staticline=new OrderLine($this->db);
            $staticline->fetch($rowid);
            $this->line->oldline = $staticline;

            // Reorder if fk_parent_line change
            if (! empty($fk_parent_line) && ! empty($staticline->fk_parent_line) && $fk_parent_line != $staticline->fk_parent_line)
            {
            	$rangmax = $this->line_max($fk_parent_line);
            	$this->line->rang = $rangmax + 1;
            }

            $this->line->rowid=$rowid;
            $this->line->label=$label;
            $this->line->desc=$desc;
            $this->line->qty=$qty;
            $this->line->tva_tx=$txtva;
            $this->line->localtax1_tx=$txlocaltax1;
            $this->line->localtax2_tx=$txlocaltax2;
			$this->line->localtax1_type = $localtaxes_type[0];
			$this->line->localtax2_type = $localtaxes_type[2];
            $this->line->remise_percent=$remise_percent;
            $this->line->subprice=$subprice;
            $this->line->info_bits=$info_bits;
            $this->line->special_code=$special_code;
            $this->line->total_ht=$total_ht;
            $this->line->total_tva=$total_tva;
            $this->line->total_localtax1=$total_localtax1;
            $this->line->total_localtax2=$total_localtax2;
            $this->line->total_ttc=$total_ttc;
            $this->line->date_start=$date_start;
            $this->line->date_end=$date_end;
            $this->line->product_type=$type;
            $this->line->fk_parent_line=$fk_parent_line;
            $this->line->skip_update_total=$skip_update_total;

			// infos marge
			if (!empty($fk_product) && empty($fk_fournprice) && empty($pa_ht)) {
			    //by external module, take lowest buying price
			    include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
			    $productFournisseur = new ProductFournisseur($this->db);
			    $productFournisseur->find_min_price_product_fournisseur($fk_product);
			    $this->line->fk_fournprice = $productFournisseur->product_fourn_price_id;
			} else {
			    $this->line->fk_fournprice = $fk_fournprice;
			}
			$this->line->pa_ht = $pa_ht;

            // TODO deprecated
            $this->line->price=$price;
            $this->line->remise=$remise;

			if (is_array($array_options) && count($array_options)>0) {
				$this->line->array_options=$array_options;
			}

            $result=$this->line->update();
            if ($result > 0)
            {
            	// Reorder if child line
            	if (! empty($fk_parent_line)) $this->line_order(true,'DESC');

                // Mise a jour info denormalisees
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
            $this->error=get_class($this)."::updateline Order status makes operation forbidden";
        	$this->errors=array('OrderStatusMakeOperationForbidden');
            return -2;
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
		global $conf, $langs;
		$error=0;

		// Clean parameters
		if (isset($this->ref)) $this->ref=trim($this->ref);
		if (isset($this->ref_client)) $this->ref_client=trim($this->ref_client);
		if (isset($this->note) || isset($this->note_private)) $this->note_private=(isset($this->note_private) ? trim($this->note_private) : trim($this->note));
		if (isset($this->note_public)) $this->note_public=trim($this->note_public);
		if (isset($this->modelpdf)) $this->modelpdf=trim($this->modelpdf);
		if (isset($this->import_key)) $this->import_key=trim($this->import_key);

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."commande SET";

		$sql.= " ref=".(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"null").",";
		$sql.= " ref_client=".(isset($this->ref_client)?"'".$this->db->escape($this->ref_client)."'":"null").",";
		$sql.= " ref_ext=".(isset($this->ref_ext)?"'".$this->db->escape($this->ref_ext)."'":"null").",";
		$sql.= " fk_soc=".(isset($this->socid)?$this->socid:"null").",";
		$sql.= " date_commande=".(strval($this->date_commande)!='' ? "'".$this->db->idate($this->date_commande)."'" : 'null').",";
		$sql.= " date_valid=".(strval($this->date_validation)!='' ? "'".$this->db->idate($this->date_validation)."'" : 'null').",";
		$sql.= " tva=".(isset($this->total_tva)?$this->total_tva:"null").",";
		$sql.= " localtax1=".(isset($this->total_localtax1)?$this->total_localtax1:"null").",";
		$sql.= " localtax2=".(isset($this->total_localtax2)?$this->total_localtax2:"null").",";
		$sql.= " total_ht=".(isset($this->total_ht)?$this->total_ht:"null").",";
		$sql.= " total_ttc=".(isset($this->total_ttc)?$this->total_ttc:"null").",";
		$sql.= " fk_statut=".(isset($this->statut)?$this->statut:"null").",";
		$sql.= " fk_user_author=".(isset($this->user_author)?$this->user_author:"null").",";
		$sql.= " fk_user_valid=".(isset($this->fk_user_valid)?$this->fk_user_valid:"null").",";
		$sql.= " fk_projet=".(isset($this->fk_project)?$this->fk_project:"null").",";
		$sql.= " fk_cond_reglement=".(isset($this->cond_reglement_id)?$this->cond_reglement_id:"null").",";
		$sql.= " fk_mode_reglement=".(isset($this->mode_reglement_id)?$this->mode_reglement_id:"null").",";
		$sql.= " note_private=".(isset($this->note_private)?"'".$this->db->escape($this->note_private)."'":"null").",";
		$sql.= " note_public=".(isset($this->note_public)?"'".$this->db->escape($this->note_public)."'":"null").",";
		$sql.= " model_pdf=".(isset($this->modelpdf)?"'".$this->db->escape($this->modelpdf)."'":"null").",";
		$sql.= " import_key=".(isset($this->import_key)?"'".$this->db->escape($this->import_key)."'":"null")."";

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
	            $result=$this->call_trigger('ORDER_MODIFY',$user);
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
     *	Update value of extrafields on order
     *
     *	@param      User	$user       Object user that modify
     *	@return     int         		<0 if ko, >0 if ok
     */
    function update_extrafields($user)
    {
        global $hookmanager, $conf;

    	$action='create';

    	// Actions on extra fields (by external module or standard code)
    	// FIXME le hook fait double emploi avec le trigger !!
    	$hookmanager->initHooks(array('orderdao'));
    	$parameters=array('id'=>$this->id);
    	$reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
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

    	if (!$error)
    	{
    		return 1;
    	}
    	else
    	{
    		return -1;
    	}

    }

    /**
     *	Delete the customer order
     *
     *	@param	User	$user		User object
     *	@param	int		$notrigger	1=Does not execute triggers, 0= execuete triggers
     * 	@return	int					<=0 if KO, >0 if OK
     */
    function delete($user, $notrigger=0)
    {
        global $conf, $langs;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $error = 0;

        $this->db->begin();

        if (! $error && ! $notrigger)
        {
            // Call trigger
            $result=$this->call_trigger('ORDER_DELETE',$user);
            if ($result < 0) $error++;
            // End call triggers
        }

        //TODO: Check for error after each action. If one failed we rollback, don't waste time to do action if previous fail
        if (! $error)
        {
        	// Delete order details
        	$sql = 'DELETE FROM '.MAIN_DB_PREFIX."commandedet WHERE fk_commande = ".$this->id;
        	dol_syslog(get_class($this)."::delete", LOG_DEBUG);
        	if (! $this->db->query($sql) )
        	{
        		$error++;
        		$this->errors[]=$this->db->lasterror();
        	}

        	// Delete order
        	$sql = 'DELETE FROM '.MAIN_DB_PREFIX."commande WHERE rowid = ".$this->id;
        	dol_syslog(get_class($this)."::delete", LOG_DEBUG);
        	if (! $this->db->query($sql) )
        	{
        		$error++;
        		$this->errors[]=$this->db->lasterror();
        	}

        	// Delete linked object
        	$res = $this->deleteObjectLinked();
        	if ($res < 0) $error++;

        	// Delete linked contacts
        	$res = $this->delete_linked_contact();
        	if ($res < 0) $error++;

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

        	// On efface le repertoire de pdf provisoire
        	$comref = dol_sanitizeFileName($this->ref);
        	if ($conf->commande->dir_output && !empty($this->ref))
        	{
        		$dir = $conf->commande->dir_output . "/" . $comref ;
        		$file = $conf->commande->dir_output . "/" . $comref . "/" . $comref . ".pdf";
        		if (file_exists($file))	// We must delete all files before deleting directory
        		{
        			dol_delete_preview($this);

        			if (! dol_delete_file($file,0,0,0,$this)) // For triggers
        			{
        				$this->db->rollback();
        				return 0;
        			}
        		}
        		if (file_exists($dir))
        		{
        			if (! dol_delete_dir_recursive($dir))
        			{
        				$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
        				$this->db->rollback();
        				return 0;
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
	        foreach($this->errors as $errmsg)
	        {
		        dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
		        $this->error.=($this->error?', '.$errmsg:$errmsg);
	        }
	        $this->db->rollback();
	        return -1*$error;
        }
    }


    /**
     *	Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *
     *	@param		User	$user   Object user
     *	@return WorkboardResponse|int <0 if KO, WorkboardResponse if OK
     */
    function load_board($user)
    {
        global $conf, $user, $langs;

        $clause = " WHERE";

        $sql = "SELECT c.rowid, c.date_creation as datec, c.date_livraison as delivery_date, c.fk_statut";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
        if (!$user->rights->societe->client->voir && !$user->societe_id)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON c.fk_soc = sc.fk_soc";
            $sql.= " WHERE sc.fk_user = " .$user->id;
            $clause = " AND";
        }
        $sql.= $clause." c.entity = ".$conf->entity;
        //$sql.= " AND c.fk_statut IN (1,2,3) AND c.facture = 0";
        $sql.= " AND ((c.fk_statut IN (".self::STATUS_VALIDATED.",".self::STATUS_ACCEPTED.")) OR (c.fk_statut = ".self::STATUS_CLOSED." AND c.facture = 0))";    // If status is 2 and facture=1, it must be selected
        if ($user->societe_id) $sql.=" AND c.fk_soc = ".$user->societe_id;

        $resql=$this->db->query($sql);
        if ($resql)
        {
	        $now=dol_now();

	        $response = new WorkboardResponse();
	        $response->warning_delay=$conf->commande->client->warning_delay/60/60/24;
	        $response->label=$langs->trans("OrdersToProcess");
	        $response->url=DOL_URL_ROOT.'/commande/list.php?viewstatut=-3';
	        $response->img=img_object($langs->trans("Orders"),"order");

            while ($obj=$this->db->fetch_object($resql))
            {
	            $response->nbtodo++;

				$date_to_test = empty($obj->delivery_date) ? $obj->datec : $obj->delivery_date;

	            if ($obj->fk_statut != 3 && $this->db->jdate($date_to_test) < ($now - $conf->commande->client->warning_delay)) {
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
     *	Return source label of order
     *
     *	@return     string      Label
     */
    function getLabelSource()
    {
        global $langs;

        $label=$langs->trans('OrderSource'.$this->source);

        if ($label == 'OrderSource') return '';
        return $label;
    }

    /**
     *	Return status label of Order
     *
     *	@param      int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *	@return     string      		Libelle
     */
    function getLibStatut($mode)
    {
        return $this->LibStatut($this->statut,$this->facturee,$mode);
    }

    /**
     *	Return label of status
     *
     *	@param		int		$statut      	Id statut
     *  @param      int		$billed    		If invoiced
     *	@param      int		$mode        	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *  @return     string					Label of status
     */
    function LibStatut($statut,$billed,$mode)
    {
        global $langs;
        //print 'x'.$statut.'-'.$billed;
        if ($mode == 0)
        {
            if ($statut==self::STATUS_CANCELED) return $langs->trans('StatusOrderCanceled');
            if ($statut==self::STATUS_DRAFT) return $langs->trans('StatusOrderDraft');
            if ($statut==self::STATUS_VALIDATED) return $langs->trans('StatusOrderValidated');
            if ($statut==self::STATUS_ACCEPTED) return $langs->trans('StatusOrderSentShort');
            if ($statut==self::STATUS_CLOSED && (! $billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))) return $langs->trans('StatusOrderToBill');
            if ($statut==self::STATUS_CLOSED && ($billed || ! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))) return $langs->trans('StatusOrderProcessed');
        }
        elseif ($mode == 1)
        {
            if ($statut==self::STATUS_CANCELED) return $langs->trans('StatusOrderCanceledShort');
            if ($statut==self::STATUS_DRAFT) return $langs->trans('StatusOrderDraftShort');
            if ($statut==self::STATUS_VALIDATED) return $langs->trans('StatusOrderValidatedShort');
            if ($statut==self::STATUS_ACCEPTED) return $langs->trans('StatusOrderSentShort');
            if ($statut==self::STATUS_CLOSED && (! $billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))) return $langs->trans('StatusOrderToBillShort');
            if ($statut==self::STATUS_CLOSED && ($billed || ! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))) return $langs->trans('StatusOrderProcessed');
        }
        elseif ($mode == 2)
        {
            if ($statut==self::STATUS_CANCELED) return img_picto($langs->trans('StatusOrderCanceled'),'statut5').' '.$langs->trans('StatusOrderCanceledShort');
            if ($statut==self::STATUS_DRAFT) return img_picto($langs->trans('StatusOrderDraft'),'statut0').' '.$langs->trans('StatusOrderDraftShort');
            if ($statut==self::STATUS_VALIDATED) return img_picto($langs->trans('StatusOrderValidated'),'statut1').' '.$langs->trans('StatusOrderValidatedShort');
            if ($statut==self::STATUS_ACCEPTED) return img_picto($langs->trans('StatusOrderSent'),'statut3').' '.$langs->trans('StatusOrderSentShort');
            if ($statut==self::STATUS_CLOSED && (! $billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))) return img_picto($langs->trans('StatusOrderToBill'),'statut7').' '.$langs->trans('StatusOrderToBillShort');
            if ($statut==self::STATUS_CLOSED && ($billed || ! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))) return img_picto($langs->trans('StatusOrderProcessed'),'statut6').' '.$langs->trans('StatusOrderProcessedShort');
        }
        elseif ($mode == 3)
        {
            if ($statut==self::STATUS_CANCELED) return img_picto($langs->trans('StatusOrderCanceled'),'statut5');
            if ($statut==self::STATUS_DRAFT) return img_picto($langs->trans('StatusOrderDraft'),'statut0');
            if ($statut==self::STATUS_VALIDATED) return img_picto($langs->trans('StatusOrderValidated'),'statut1');
            if ($statut==self::STATUS_ACCEPTED) return img_picto($langs->trans('StatusOrderSentShort'),'statut3');
            if ($statut==self::STATUS_CLOSED && (! $billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))) return img_picto($langs->trans('StatusOrderToBill'),'statut7');
            if ($statut==self::STATUS_CLOSED && ($billed || ! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))) return img_picto($langs->trans('StatusOrderProcessed'),'statut6');
        }
        elseif ($mode == 4)
        {
            if ($statut==self::STATUS_CANCELED) return img_picto($langs->trans('StatusOrderCanceled'),'statut5').' '.$langs->trans('StatusOrderCanceled');
            if ($statut==self::STATUS_DRAFT) return img_picto($langs->trans('StatusOrderDraft'),'statut0').' '.$langs->trans('StatusOrderDraft');
            if ($statut==self::STATUS_VALIDATED) return img_picto($langs->trans('StatusOrderValidated'),'statut1').' '.$langs->trans('StatusOrderValidated');
            if ($statut==self::STATUS_ACCEPTED) return img_picto($langs->trans('StatusOrderSentShort'),'statut3').' '.$langs->trans('StatusOrderSent');
            if ($statut==self::STATUS_CLOSED && (! $billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))) return img_picto($langs->trans('StatusOrderToBill'),'statut7').' '.$langs->trans('StatusOrderToBill');
            if ($statut==self::STATUS_CLOSED && ($billed || ! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))) return img_picto($langs->trans('StatusOrderProcessed'),'statut6').' '.$langs->trans('StatusOrderProcessed');
        }
        elseif ($mode == 5)
        {
            if ($statut==self::STATUS_CANCELED) return '<span class="hideonsmartphone">'.$langs->trans('StatusOrderCanceledShort').' </span>'.img_picto($langs->trans('StatusOrderCanceled'),'statut5');
            if ($statut==self::STATUS_DRAFT) return '<span class="hideonsmartphone">'.$langs->trans('StatusOrderDraftShort').' </span>'.img_picto($langs->trans('StatusOrderDraft'),'statut0');
            if ($statut==self::STATUS_VALIDATED) return '<span class="hideonsmartphone">'.$langs->trans('StatusOrderValidatedShort').' </span>'.img_picto($langs->trans('StatusOrderValidated'),'statut1');
            if ($statut==self::STATUS_ACCEPTED) return '<span class="hideonsmartphone">'.$langs->trans('StatusOrderSentShort').' </span>'.img_picto($langs->trans('StatusOrderSent'),'statut3');
            if ($statut==self::STATUS_CLOSED && (! $billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))) return '<span class="hideonsmartphone">'.$langs->trans('StatusOrderToBillShort').' </span>'.img_picto($langs->trans('StatusOrderToBill'),'statut7');
            if ($statut==self::STATUS_CLOSED && ($billed || ! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))) return '<span class="hideonsmartphone">'.$langs->trans('StatusOrderProcessedShort').' </span>'.img_picto($langs->trans('StatusOrderProcessed'),'statut6');
        }
    }


    /**
     *	Return clicable link of object (with eventually picto)
     *
     *	@param      int			$withpicto      Add picto into link
     *	@param      int			$option         Where point the link (0=> main card, 1,2 => shipment)
     *	@param      int			$max          	Max length to show
     *	@param      int			$short			Use short labels
     *	@return     string          			String with URL
     */
    function getNomUrl($withpicto=0,$option=0,$max=0,$short=0)
    {
        global $conf, $langs;

        $result='';

        if (! empty($conf->expedition->enabled) && ($option == 1 || $option == 2)) $url = DOL_URL_ROOT.'/expedition/shipment.php?id='.$this->id;
        else $url = DOL_URL_ROOT.'/commande/card.php?id='.$this->id;

        if ($short) return $url;

        $picto = 'order';
        $label = '<u>' . $langs->trans("ShowOrder") . '</u>';
        if (! empty($this->ref))
            $label .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
        if (! empty($this->ref_client))
            $label.= '<br><b>' . $langs->trans('RefCustomer') . ':</b> ' . $this->ref_client;
        if (! empty($this->total_ht))
            $label.= '<br><b>' . $langs->trans('AmountHT') . ':</b> ' . price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
        if (! empty($this->total_tva))
            $label.= '<br><b>' . $langs->trans('TVA') . ':</b> ' . price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
        if (! empty($this->total_ttc))
            $label.= '<br><b>' . $langs->trans('AmountTTC') . ':</b> ' . price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);

        $linkstart = '<a href="'.$url.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
        $linkend='</a>';

        if ($withpicto) $result.=($linkstart.img_object($label, $picto, 'class="classfortooltip"').$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$linkstart.$this->ref.$linkend;
        return $result;
    }


    /**
     *	Charge les informations d'ordre info dans l'objet commande
     *
     *	@param  int		$id       Id of order
     *	@return	void
     */
    function info($id)
    {
        $sql = 'SELECT c.rowid, date_creation as datec, tms as datem,';
        $sql.= ' date_valid as datev,';
        $sql.= ' date_cloture as datecloture,';
        $sql.= ' fk_user_author, fk_user_valid, fk_user_cloture';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'commande as c';
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
                    $this->user_creation   = $cuser;
                }

                if ($obj->fk_user_valid)
                {
                    $vuser = new User($this->db);
                    $vuser->fetch($obj->fk_user_valid);
                    $this->user_validation = $vuser;
                }

                if ($obj->fk_user_cloture)
                {
                    $cluser = new User($this->db);
                    $cluser->fetch($obj->fk_user_cloture);
                    $this->user_cloture   = $cluser;
                }

                $this->date_creation     = $this->db->jdate($obj->datec);
                $this->date_modification = $this->db->jdate($obj->datem);
                $this->date_validation   = $this->db->jdate($obj->datev);
                $this->date_cloture      = $this->db->jdate($obj->datecloture);
            }

            $this->db->free($result);

        }
        else
        {
            dol_print_error($this->db);
        }
    }


    /**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    function initAsSpecimen()
    {
        global $user,$langs,$conf;

        dol_syslog(get_class($this)."::initAsSpecimen");

        // Charge tableau des produits prodids
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

        // Initialise parametres
        $this->id=0;
        $this->ref = 'SPECIMEN';
        $this->specimen=1;
        $this->socid = 1;
        $this->date = time();
        $this->date_lim_reglement=$this->date+3600*24*30;
        $this->cond_reglement_code = 'RECEP';
        $this->mode_reglement_code = 'CHQ';
        $this->availability_code   = 'DSP';
        $this->demand_reason_code  = 'SRC_00';
        $this->note_public='This is a comment (public)';
        $this->note_private='This is a comment (private)';
        // Lines
        $nbp = 5;
        $xnbp = 0;
        while ($xnbp < $nbp)
        {
            $line=new OrderLine($this->db);

            $line->desc=$langs->trans("Description")." ".$xnbp;
            $line->qty=1;
            $line->subprice=100;
            $line->price=100;
            $line->tva_tx=19.6;
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
            $prodid = rand(1, $num_prods);
            $line->fk_product=$prodids[$prodid];

            $this->lines[$xnbp]=$line;

            $this->total_ht       += $line->total_ht;
            $this->total_tva      += $line->total_tva;
            $this->total_ttc      += $line->total_ttc;

            $xnbp++;
        }
    }


    /**
     *	Charge indicateurs this->nb de tableau de bord
     *
     *	@return     int         <0 si ko, >0 si ok
     */
    function load_state_board()
    {
        global $conf, $user;

        $this->nb=array();
        $clause = "WHERE";

        $sql = "SELECT count(co.rowid) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande as co";
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
                $this->nb["orders"]=$obj->nb;
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
     * 	Return an array of order lines
     *
     * @return	array		Lines of order
     */
    function getLinesArray()
    {
        $lines = array();

        $sql = 'SELECT l.rowid, l.fk_product, l.product_type, l.label as custom_label, l.description, l.price, l.qty, l.tva_tx, ';
        $sql.= ' l.fk_remise_except, l.remise_percent, l.subprice, l.info_bits, l.rang, l.special_code, l.fk_parent_line,';
        $sql.= ' l.total_ht, l.total_tva, l.total_ttc, l.fk_product_fournisseur_price as fk_fournprice, l.buy_price_ht as pa_ht, l.localtax1_tx, l.localtax2_tx,';
        $sql.= ' l.date_start, l.date_end,';
        $sql.= ' p.label as product_label, p.ref, p.fk_product_type, p.rowid as prodid, ';
        $sql.= ' p.description as product_desc, p.stock as stock_reel,';
        $sql.= ' p.entity';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'commandedet as l';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product=p.rowid';
        $sql.= ' WHERE l.fk_commande = '.$this->id;
        $sql.= ' ORDER BY l.rang ASC, l.rowid';

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;

            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

				$this->lines[$i]					= new OrderLine($this->db);
                $this->lines[$i]->id				= $obj->rowid;
                $this->lines[$i]->label 			= $obj->custom_label;
                $this->lines[$i]->description 		= $obj->description;
                $this->lines[$i]->fk_product		= $obj->fk_product;
                $this->lines[$i]->ref				= $obj->ref;
                $this->lines[$i]->entity            = $obj->entity;         // Product entity
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
				$this->lines[$i]->stock				= $obj->stock_reel;
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
            return -1;
        }
    }

	/**
	 *  Create a document onto disk accordign to template module.
	 *
	 *  @param	    string		$modele			Force le mnodele a utiliser ('' to not force)
	 *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
	{
		global $conf,$langs;

		$langs->load("orders");

		// Positionne le modele sur le nom du modele a utiliser
		if (! dol_strlen($modele))
		{
			if (! empty($conf->global->COMMANDE_ADDON_PDF))
			{
				$modele = $conf->global->COMMANDE_ADDON_PDF;
			}
			else
			{
				$modele = 'einstein';
			}
		}

		$modelpath = "core/modules/commande/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}

}


/**
 *  Class to mange order lines
 */
class OrderLine extends CommonOrderLine
{
	public $element='commandedet';
	public $table_element='commandedet';

    var $oldline;

	/**
	 * Id of parent order
	 * @var int
	 */
	public $fk_commande;

	/**
	 * Id of parent order
	 * @var int
	 * @deprecated Use fk_commande
	 */
	public $commande_id;

    // From llx_commandedet
    var $fk_parent_line;
    var $fk_facture;
    var $label;
    var $fk_remise_except;
    var $rang = 0;
	var $fk_fournprice;

	/**
	 * Buy price without taxes
	 * @var float
	 */
	var $pa_ht;
    var $marge_tx;
    var $marque_tx;

	/**
	 * @deprecated
	 */
	var $remise;

    // Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
    // Start and end date of the line
    var $date_start;
    var $date_end;

    var $skip_update_total; // Skip update price total for special lines


    /**
     *      Constructor
     *
     *      @param     DoliDB	$db      handler d'acces base de donnee
     */
    function __construct($db)
    {
        $this->db= $db;
    }

    /**
     *  Load line order
     *
     *  @param  int		$rowid          Id line order
     *  @return	int						<0 if KO, >0 if OK
     */
    function fetch($rowid)
    {
        $sql = 'SELECT cd.rowid, cd.fk_commande, cd.fk_parent_line, cd.fk_product, cd.product_type, cd.label as custom_label, cd.description, cd.price, cd.qty, cd.tva_tx, cd.localtax1_tx, cd.localtax2_tx,';
        $sql.= ' cd.remise, cd.remise_percent, cd.fk_remise_except, cd.subprice,';
        $sql.= ' cd.info_bits, cd.total_ht, cd.total_tva, cd.total_localtax1, cd.total_localtax2, cd.total_ttc, cd.fk_product_fournisseur_price as fk_fournprice, cd.buy_price_ht as pa_ht, cd.rang, cd.special_code,';
        $sql.= ' p.ref as product_ref, p.label as product_libelle, p.description as product_desc,';
        $sql.= ' cd.date_start, cd.date_end';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'commandedet as cd';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON cd.fk_product = p.rowid';
        $sql.= ' WHERE cd.rowid = '.$rowid;
        $result = $this->db->query($sql);
        if ($result)
        {
            $objp = $this->db->fetch_object($result);
            $this->rowid            = $objp->rowid;
            $this->fk_commande      = $objp->fk_commande;
            $this->fk_parent_line   = $objp->fk_parent_line;
            $this->label            = $objp->custom_label;
            $this->desc             = $objp->description;
            $this->qty              = $objp->qty;
            $this->price            = $objp->price;
            $this->subprice         = $objp->subprice;
            $this->tva_tx           = $objp->tva_tx;
            $this->localtax1_tx		= $objp->localtax1_tx;
            $this->localtax2_tx		= $objp->localtax2_tx;
            $this->remise           = $objp->remise;
            $this->remise_percent   = $objp->remise_percent;
            $this->fk_remise_except = $objp->fk_remise_except;
            $this->fk_product       = $objp->fk_product;
            $this->product_type     = $objp->product_type;
            $this->info_bits        = $objp->info_bits;
			$this->special_code		= $objp->special_code;
            $this->total_ht         = $objp->total_ht;
            $this->total_tva        = $objp->total_tva;
            $this->total_localtax1  = $objp->total_localtax1;
            $this->total_localtax2  = $objp->total_localtax2;
            $this->total_ttc        = $objp->total_ttc;
			$this->fk_fournprice	= $objp->fk_fournprice;
			$marginInfos			= getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $this->fk_fournprice, $objp->pa_ht);
			$this->pa_ht			= $marginInfos[0];
			$this->marge_tx			= $marginInfos[1];
			$this->marque_tx		= $marginInfos[2];
            $this->special_code		= $objp->special_code;
            $this->rang             = $objp->rang;

            $this->ref				= $objp->product_ref;      // deprecated
            $this->product_ref		= $objp->product_ref;
            $this->libelle			= $objp->product_libelle;  // deprecated
            $this->product_label	= $objp->product_libelle;
            $this->product_desc     = $objp->product_desc;

            $this->date_start       = $this->db->jdate($objp->date_start);
            $this->date_end         = $this->db->jdate($objp->date_end);

            $this->db->free($result);
        }
        else
        {
            dol_print_error($this->db);
        }
    }

    /**
     * 	Delete line in database
     *
     *	@return	 int  <0 si ko, >0 si ok
     */
    function delete()
    {
        global $conf, $user, $langs;

		$error=0;

	    $this->db->begin();

        $sql = 'DELETE FROM '.MAIN_DB_PREFIX."commandedet WHERE rowid='".$this->rowid."';";

        dol_syslog("OrderLine::delete", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
			// Remove extrafields
			if ((! $error) && (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED))) // For avoid conflicts if trigger used
			{
				$this->id=$this->rowid;
				$result=$this->deleteExtraFields();
				if ($result < 0)
				{
					$error++;
					dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
				}
			}

            // Call trigger
            $result=$this->call_trigger('LINEORDER_DELETE',$user);
            if ($result < 0) $error++;
            // End call triggers

	        if (!$error) {
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

    /**
     *	Insert line into database
     *
     *	@param      int		$notrigger		1 = disable triggers
     *	@return		int						<0 if KO, >0 if OK
     */
    function insert($notrigger=0)
    {
        global $langs, $conf, $user;

		$error=0;

        dol_syslog(get_class($this)."::insert rang=".$this->rang);

        // Clean parameters
        if (empty($this->tva_tx)) $this->tva_tx=0;
        if (empty($this->localtax1_tx)) $this->localtax1_tx=0;
        if (empty($this->localtax2_tx)) $this->localtax2_tx=0;
		if (empty($this->localtax1_type)) $this->localtax1_type=0;
		if (empty($this->localtax2_type)) $this->localtax2_type=0;
        if (empty($this->total_localtax1)) $this->total_localtax1=0;
        if (empty($this->total_localtax2)) $this->total_localtax2=0;
        if (empty($this->rang)) $this->rang=0;
        if (empty($this->remise)) $this->remise=0;
        if (empty($this->remise_percent)) $this->remise_percent=0;
        if (empty($this->info_bits)) $this->info_bits=0;
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
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'commandedet';
        $sql.= ' (fk_commande, fk_parent_line, label, description, qty, ';
        $sql.= ' tva_tx, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type,';
        $sql.= ' fk_product, product_type, remise_percent, subprice, price, remise, fk_remise_except,';
        $sql.= ' special_code, rang, fk_product_fournisseur_price, buy_price_ht,';
        $sql.= ' info_bits, total_ht, total_tva, total_localtax1, total_localtax2, total_ttc, date_start, date_end)';
        $sql.= " VALUES (".$this->fk_commande.",";
        $sql.= " ".($this->fk_parent_line>0?"'".$this->fk_parent_line."'":"null").",";
        $sql.= " ".(! empty($this->label)?"'".$this->db->escape($this->label)."'":"null").",";
        $sql.= " '".$this->db->escape($this->desc)."',";
        $sql.= " '".price2num($this->qty)."',";
        $sql.= " '".price2num($this->tva_tx)."',";
        $sql.= " '".price2num($this->localtax1_tx)."',";
        $sql.= " '".price2num($this->localtax2_tx)."',";
		$sql.= " '".$this->localtax1_type."',";
		$sql.= " '".$this->localtax2_type."',";
        $sql.= ' '.(! empty($this->fk_product)?$this->fk_product:"null").',';
        $sql.= " '".$this->product_type."',";
        $sql.= " '".price2num($this->remise_percent)."',";
        $sql.= " ".($this->subprice!=''?"'".price2num($this->subprice)."'":"null").",";
        $sql.= " ".($this->price!=''?"'".price2num($this->price)."'":"null").",";
        $sql.= " '".price2num($this->remise)."',";
        $sql.= ' '.(! empty($this->fk_remise_except)?$this->fk_remise_except:"null").',';
        $sql.= ' '.$this->special_code.',';
        $sql.= ' '.$this->rang.',';
		$sql.= ' '.(! empty($this->fk_fournprice)?$this->fk_fournprice:"null").',';
		$sql.= ' '.price2num($this->pa_ht).',';
        $sql.= " '".$this->info_bits."',";
        $sql.= " '".price2num($this->total_ht)."',";
        $sql.= " '".price2num($this->total_tva)."',";
        $sql.= " '".price2num($this->total_localtax1)."',";
        $sql.= " '".price2num($this->total_localtax2)."',";
        $sql.= " '".price2num($this->total_ttc)."',";
        $sql.= " ".(! empty($this->date_start)?"'".$this->db->idate($this->date_start)."'":"null").',';
        $sql.= " ".(! empty($this->date_end)?"'".$this->db->idate($this->date_end)."'":"null");
        $sql.= ')';

        dol_syslog(get_class($this)."::insert", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->rowid=$this->db->last_insert_id(MAIN_DB_PREFIX.'commandedet');

			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
			{
				$this->id=$this->rowid;
				$result=$this->insertExtraFields();
				if ($result < 0)
				{
					$error++;
				}
			}

            if (! $error && ! $notrigger)
            {
	            // Call trigger
	            $result=$this->call_trigger('LINEORDER_INSERT',$user);
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
		        $this->error.=($this->error?', '.$errmsg:$errmsg);
	        }
	        $this->db->rollback();
	        return -1*$error;
        }
        else
        {
            $this->error=$this->db->error();
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
	function update($notrigger=0)
	{
		global $conf,$langs,$user;

		$error=0;

		// Clean parameters
		if (empty($this->tva_tx)) $this->tva_tx=0;
		if (empty($this->localtax1_tx)) $this->localtax1_tx=0;
		if (empty($this->localtax2_tx)) $this->localtax2_tx=0;
		if (empty($this->localtax1_type)) $this->localtax1_type=0;
		if (empty($this->localtax2_type)) $this->localtax2_type=0;
		if (empty($this->qty)) $this->qty=0;
		if (empty($this->total_localtax1)) $this->total_localtax1=0;
		if (empty($this->total_localtax2)) $this->total_localtax2=0;
		if (empty($this->marque_tx)) $this->marque_tx=0;
		if (empty($this->marge_tx)) $this->marge_tx=0;
		if (empty($this->remise)) $this->remise=0;
		if (empty($this->remise_percent)) $this->remise_percent=0;
		if (empty($this->info_bits)) $this->info_bits=0;
        if (empty($this->special_code)) $this->special_code=0;
		if (empty($this->product_type)) $this->product_type=0;
		if (empty($this->fk_parent_line)) $this->fk_parent_line=0;
		if (empty($this->pa_ht)) $this->pa_ht=0;

		// si prix d'achat non renseigné et utilisé pour calcul des marges alors prix achat = prix vente
		if ($this->pa_ht == 0) {
			if ($this->subprice > 0 && (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1))
				$this->pa_ht = $this->subprice * (1 - $this->remise_percent / 100);
		}

		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet SET";
		$sql.= " description='".$this->db->escape($this->desc)."'";
		$sql.= " , label=".(! empty($this->label)?"'".$this->db->escape($this->label)."'":"null");
		$sql.= " , tva_tx=".price2num($this->tva_tx);
		$sql.= " , localtax1_tx=".price2num($this->localtax1_tx);
		$sql.= " , localtax2_tx=".price2num($this->localtax2_tx);
		$sql.= " , localtax1_type='".$this->localtax1_type."'";
		$sql.= " , localtax2_type='".$this->localtax2_type."'";
		$sql.= " , qty=".price2num($this->qty);
		$sql.= " , subprice=".price2num($this->subprice)."";
		$sql.= " , remise_percent=".price2num($this->remise_percent)."";
		$sql.= " , price=".price2num($this->price)."";					// TODO A virer
		$sql.= " , remise=".price2num($this->remise)."";				// TODO A virer
		if (empty($this->skip_update_total))
		{
			$sql.= " , total_ht=".price2num($this->total_ht)."";
			$sql.= " , total_tva=".price2num($this->total_tva)."";
			$sql.= " , total_ttc=".price2num($this->total_ttc)."";
			$sql.= " , total_localtax1=".price2num($this->total_localtax1);
			$sql.= " , total_localtax2=".price2num($this->total_localtax2);
		}
		$sql.= " , fk_product_fournisseur_price=".(! empty($this->fk_fournprice)?$this->fk_fournprice:"null");
		$sql.= " , buy_price_ht='".price2num($this->pa_ht)."'";
		$sql.= " , info_bits=".$this->info_bits;
        $sql.= " , special_code=".$this->special_code;
		$sql.= " , date_start=".(! empty($this->date_start)?"'".$this->db->idate($this->date_start)."'":"null");
		$sql.= " , date_end=".(! empty($this->date_end)?"'".$this->db->idate($this->date_end)."'":"null");
		$sql.= " , product_type=".$this->product_type;
		$sql.= " , fk_parent_line=".(! empty($this->fk_parent_line)?$this->fk_parent_line:"null");
		if (! empty($this->rang)) $sql.= ", rang=".$this->rang;
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
	            $result=$this->call_trigger('LINEORDER_UPDATE',$user);
	            if ($result < 0) $error++;
	            // End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			}

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
			$this->error=$this->db->error();
			$this->db->rollback();
			return -2;
		}
	}

    /**
     *	Update totals of order into database
     *
     *	@return		int		<0 if ko, >0 if ok
     */
    function update_total()
    {
        $this->db->begin();

        // Clean parameters
        if (empty($this->total_localtax1)) $this->total_localtax1=0;
        if (empty($this->total_localtax2)) $this->total_localtax2=0;

        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."commandedet SET";
        $sql.= " total_ht='".price2num($this->total_ht)."'";
        $sql.= ",total_tva='".price2num($this->total_tva)."'";
        $sql.= ",total_localtax1='".price2num($this->total_localtax1)."'";
        $sql.= ",total_localtax2='".price2num($this->total_localtax2)."'";
        $sql.= ",total_ttc='".price2num($this->total_ttc)."'";
        $sql.= " WHERE rowid = ".$this->rowid;

        dol_syslog("OrderLine::update_total", LOG_DEBUG);

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

