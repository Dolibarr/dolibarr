<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011      Jean Heimburger      <jean@tiaris.info>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU  *General Public License as published by
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
 *  \file       htdocs/commande/class/commande.class.php
 *  \ingroup    commande
 *  \brief      Fichier des classes de commandes
 */
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");


/**
 *  \class      Commande
 *  \brief      Class to manage customers orders
 */
class Commande extends CommonObject
{
    public $element='commande';
    public $table_element='commande';
    public $table_element_line = 'commandedet';
    public $class_element_line = 'OrderLine';
    public $fk_element = 'fk_commande';
    protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    var $id;

    var $socid;		// Id client
    var $client;		// Objet societe client (a charger par fetch_client)

    var $ref;
    var $ref_client;
    var $ref_ext;
    var $ref_int;
    var $contactid;
    var $fk_project;
    var $statut;		// -1=Canceled, 0=Draft, 1=Validated, (2=Accepted/On process not managed for customer orders), 3=Closed (Sent/Received, billed or not)

    var $facturee;		// Facturee ou non
    var $brouillon;
    var $cond_reglement_id;
    var $cond_reglement_code;
    var $mode_reglement_id;
    var $mode_reglement_code;
    var $availability_id;
    var $availability_code;
    var $demand_reason_id;
    var $demand_reason_code;
    var $fk_delivery_address;
    var $adresse;
    var $date;				// Date commande
    var $date_commande;		// Date commande (deprecated)
    var $date_livraison;	// Date livraison souhaitee
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

    var $origin;
    var $origin_id;

    var $user_author_id;

    var $lines = array();

    // Pour board
    var $nbtodo;
    var $nbtodolate;


    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    function Commande($db)
    {
        global $langs;
        $langs->load('orders');
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

        $dir = DOL_DOCUMENT_ROOT . "/core/modules/commande";

        if (! empty($conf->global->COMMANDE_ADDON))
        {
            $file = $conf->global->COMMANDE_ADDON.".php";

            // Chargement de la classe de numerotation
            $classname = $conf->global->COMMANDE_ADDON;

            $result=include_once($dir.'/'.$file);
            if ($result)
            {
                $obj = new $classname();
                $numref = "";
                $numref = $obj->getNextValue($soc,$this);

                if ( $numref != "")
                {
                    return $numref;
                }
                else
                {
                    dol_print_error($db,"Commande::getNextNumRef ".$obj->error);
                    return "";
                }
            }
            else
            {
                print $langs->trans("Error")." ".$langs->trans("Error_COMMANDE_ADDON_NotDefined");
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
     *	@return  	int						<=0 if OK, >0 if KO
     */
    function valid($user, $idwarehouse=0)
    {
        global $conf,$langs;
        require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

        $error=0;

        // Protection
        if ($this->statut == 1)
        {
            dol_syslog(get_class($this)."::valid no draft status", LOG_WARNING);
            return 0;
        }

        if (! $user->rights->commande->valider)
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

        // Validate
        $sql = "UPDATE ".MAIN_DB_PREFIX."commande";
        $sql.= " SET ref = '".$num."',";
        $sql.= " fk_statut = 1,";
        $sql.= " date_valid='".$this->db->idate($now)."',";
        $sql.= " fk_user_valid = ".$user->id;
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::valid() sql=".$sql);
        $resql=$this->db->query($sql);
        if (! $resql)
        {
            dol_syslog(get_class($this)."::valid Echec update - 10 - sql=".$sql, LOG_ERR);
            dol_print_error($this->db);
            $error++;
        }

        if (! $error)
        {
            // If stock is incremented on validate order, we must increment it
            if ($result >= 0 && $conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER == 1)
            {
                require_once(DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php");
                $langs->load("agenda");

                // Loop on each line
                $cpt=count($this->lines);
                for ($i = 0; $i < $cpt; $i++)
                {
                    if ($this->lines[$i]->fk_product > 0)
                    {
                        $mouvP = new MouvementStock($this->db);
                        // We decrement stock of product (and sub-products)
                        $result=$mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("OrderValidatedInDolibarr",$num));
                        if ($result < 0) { $error++; }
                    }
                }
            }
        }

        if (! $error)
        {
            $this->oldref='';

            // Rename directory if dir was a temporary ref
            if (preg_match('/^[\(]?PROV/i', $this->ref))
            {
                // On renomme repertoire ($this->ref = ancienne ref, $numfa = nouvelle ref)
                // afin de ne pas perdre les fichiers attaches
                $comref = dol_sanitizeFileName($this->ref);
                $snum = dol_sanitizeFileName($num);
                $dirsource = $conf->commande->dir_output.'/'.$comref;
                $dirdest = $conf->commande->dir_output.'/'.$snum;
                if (file_exists($dirsource))
                {
                    dol_syslog(get_class($this)."::valid() rename dir ".$dirsource." into ".$dirdest);

                    if (@rename($dirsource, $dirdest))
                    {
                        $this->oldref = $comref;

                        dol_syslog("Rename ok");
                        // Suppression ancien fichier PDF dans nouveau rep
                        dol_delete_file($conf->commande->dir_output.'/'.$snum.'/'.$comref.'.*');
                    }
                }
            }
        }

        // Set new ref and current status
        if (! $error)
        {
            $this->ref = $num;
            $this->statut = 1;
        }

        if (! $error)
        {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('ORDER_VALIDATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers
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
    function set_draft($user, $idwarehouse=-1)
    {
        global $conf,$langs;

        $error=0;

        // Protection
        if ($this->statut <= 0)
        {
            return 0;
        }

        if (! $user->rights->commande->valider)
        {
            $this->error='Permission denied';
            return -1;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."commande";
        $sql.= " SET fk_statut = 0";
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::set_draft sql=".$sql, LOG_DEBUG);
        if ($this->db->query($sql))
        {
            // If stock is decremented on validate order, we must reincrement it
            if ($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER == 1)
            {
                require_once(DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php");
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
                    $this->statut=0;
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

            $this->statut=0;
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            $this->db->rollback();
            dol_syslog($this->error, LOG_ERR);
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

        if ($this->statut != 3)
        {
            return 0;
        }

        $this->db->begin();

        $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
        $sql.= ' SET fk_statut=1, facture=0';
        $sql.= ' WHERE rowid = '.$this->id;

        dol_syslog("Commande::set_reopen sql=".$sql);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('BILL_REOPEN',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
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
     *  Close order
     *
     * 	@param      User	$user       Objet user that close
     *	@return		int					<0 if KO, >0 if OK
     */
    function cloture($user)
    {
        global $conf, $langs;

        $error=0;

        if ($user->rights->commande->valider)
        {
            $this->db->begin();

            $now=dol_now();

            $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
            $sql.= ' SET fk_statut = 3,';
            $sql.= ' fk_user_cloture = '.$user->id.',';
            $sql.= ' date_cloture = '.$this->db->idate($now);
            $sql.= ' WHERE rowid = '.$this->id.' AND fk_statut > 0';

            if ($this->db->query($sql))
            {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('ORDER_CLOSE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers

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
                $this->error=$this->db->lasterror();
                dol_syslog($this->error, LOG_ERR);

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
    function cancel($user, $idwarehouse=-1)
    {
        global $conf,$langs;

        $error=0;

        if ($user->rights->commande->valider)
        {
            $this->db->begin();

            $sql = "UPDATE ".MAIN_DB_PREFIX."commande";
            $sql.= " SET fk_statut = -1";
            $sql.= " WHERE rowid = ".$this->id;
            $sql.= " AND fk_statut = 1";

            dol_syslog("Commande::cancel sql=".$sql, LOG_DEBUG);
            if ($this->db->query($sql))
            {
                // If stock is decremented on validate order, we must reincrement it
                if ($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER == 1)
                {
	                require_once(DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php");
	                $langs->load("agenda");

	                $num=count($this->lines);
	                for ($i = 0; $i < $num; $i++)
	                {
	                    if ($this->lines[$i]->fk_product > 0)
	                    {
	                        $mouvP = new MouvementStock($this->db);
	                        // We increment stock of product (and sub-products)
	                        $result=$mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("OrderCanceledInDolibarr",$this->ref));
	                        if ($result < 0) { $error++; }
	                    }
	                }
                }

                if (! $error)
                {
                    // Appel des triggers
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface=new Interfaces($this->db);
                    $result=$interface->run_triggers('ORDER_CANCEL',$this,$user,$langs,$conf);
                    if ($result < 0) { $error++; $this->errors=$interface->errors; }
                    // Fin appel triggers
                }

                if (! $error)
                {
                    $this->statut=-1;
                    $this->db->commit();
                    return 1;
                }
                else
                {
                    $this->error=$mouvP->error;
                    $this->db->rollback();
                    return -1;
                }
            }
            else
            {
                $this->error=$this->db->error();
                $this->db->rollback();
                dol_syslog($this->error, LOG_ERR);
                return -1;
            }
        }
    }

    /**
     *	Create order
     *	Note that this->ref can be set or empty. If empty, we will use "(PROV)"
     *
     *	@param		User	$user 		Objet user that make creation
     *	@param		int		notrigger	Disable all triggers
     *	@return 	int					<0 if KO, >0 if OK
     */
    function create($user, $notrigger=0)
    {
        global $conf,$langs,$mysoc;
        $error=0;

        // Clean parameters
        $this->brouillon = 1;		// On positionne en mode brouillon la commande

        dol_syslog("Commande::create user=".$user->id);

        // Check parameters
        $soc = new Societe($this->db);
        $result=$soc->fetch($this->socid);
        if ($result < 0)
        {
            $this->error="Failed to fetch company";
            dol_syslog("Commande::create ".$this->error, LOG_ERR);
            return -2;
        }
        if (! empty($conf->global->COMMANDE_REQUIRE_SOURCE) && $this->source < 0)
        {
            $this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Source"));
            dol_syslog("Commande::create ".$this->error, LOG_ERR);
            return -1;
        }
        if (! $this->fk_project) $this->fk_project = 0;

        // $date_commande is deprecated
        $date = ($this->date_commande ? $this->date_commande : $this->date);


        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande (";
        $sql.= " ref, fk_soc, date_creation, fk_user_author, fk_projet, date_commande, source, note, note_public, ref_client, ref_int";
        $sql.= ", model_pdf, fk_cond_reglement, fk_mode_reglement, fk_availability, fk_demand_reason, date_livraison, fk_adresse_livraison";
        $sql.= ", remise_absolue, remise_percent";
        $sql.= ", entity";
        $sql.= ")";
        $sql.= " VALUES ('(PROV)',".$this->socid.", ".$this->db->idate(gmmktime()).", ".$user->id.", ".$this->fk_project;
        $sql.= ", ".$this->db->idate($date);
        $sql.= ", ".($this->source>=0 && $this->source != '' ?$this->source:'null');
        $sql.= ", '".$this->db->escape($this->note)."'";
        $sql.= ", '".$this->db->escape($this->note_public)."'";
        $sql.= ", '".$this->db->escape($this->ref_client)."'";
        $sql.= ", ".($this->ref_int?"'".$this->db->escape($this->ref_int)."'":"null");
        $sql.= ", '".$this->modelpdf."'";
        $sql.= ", ".($this->cond_reglement_id>0?"'".$this->cond_reglement_id."'":"null");
        $sql.= ", ".($this->mode_reglement_id>0?"'".$this->mode_reglement_id."'":"null");
        $sql.= ", ".($this->availability_id>0?"'".$this->availability_id."'":"null");
        $sql.= ", ".($this->demand_reason_id>0?"'".$this->demand_reason_id."'":"null");
        $sql.= ", ".($this->date_livraison?"'".$this->db->idate($this->date_livraison)."'":"null");
        $sql.= ", ".($this->fk_delivery_address>0?$this->fk_delivery_address:'NULL');
        $sql.= ", ".($this->remise_absolue>0?$this->remise_absolue:'NULL');
        $sql.= ", '".$this->remise_percent."'";
        $sql.= ", ".$conf->entity;
        $sql.= ")";

        dol_syslog("Commande::create sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'commande');

            if ($this->id)
            {
                $fk_parent_line=0;
                $num=count($this->lines);

                /*
                 *  Insertion du detail des produits dans la base
                 */
                for ($i=0;$i<$num;$i++)
                {
                    // Reset fk_parent_line for no child products and special product
                    if (($this->lines[$i]->product_type != 9 && empty($this->lines[$i]->fk_parent_line)) || $this->lines[$i]->product_type == 9) {
                        $fk_parent_line = 0;
                    }

                    $result = $this->addline(
                    $this->id,
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
                    $fk_parent_line
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

                // Mise a jour ref
                $sql = 'UPDATE '.MAIN_DB_PREFIX."commande SET ref='(PROV".$this->id.")' WHERE rowid=".$this->id;
                if ($this->db->query($sql))
                {
                    if ($this->id)
                    {
                        $this->ref="(PROV".$this->id.")";

                        // Add linked object
                        if ($this->origin && $this->origin_id)
                        {
                            $ret = $this->add_object_linked();
                            if (! $ret)	dol_print_error($this->db);
                        }

                        // TODO mutualiser
                        if ($this->origin == 'propal' && $this->origin_id)
                        {
                            // On recupere les differents contact interne et externe
                            $prop = new Propal($this->db, $this->socid, $this->origin_id);

                            // On recupere le commercial suivi propale
                            $this->userid = $prop->getIdcontact('internal', 'SALESREPFOLL');

                            if ($this->userid)
                            {
                                //On passe le commercial suivi propale en commercial suivi commande
                                $this->add_contact($this->userid[0], 'SALESREPFOLL', 'internal');
                            }

                            // On recupere le contact client suivi propale
                            $this->contactid = $prop->getIdcontact('external', 'CUSTOMER');

                            if ($this->contactid)
                            {
                                //On passe le contact client suivi propale en contact client suivi commande
                                $this->add_contact($this->contactid[0], 'CUSTOMER', 'external');
                            }
                        }
                    }

                    if (! $notrigger)
                    {
                        // Appel des triggers
                        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                        $interface=new Interfaces($this->db);
                        $result=$interface->run_triggers('ORDER_CREATE',$this,$user,$langs,$conf);
                        if ($result < 0) { $error++; $this->errors=$interface->errors; }
                        // Fin appel triggers
                    }

                    $this->db->commit();
                    return $this->id;
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
     *	@param		HookManager	$hookmanager	Hook manager instance
     *	@return		int							New id of clone
     */
    function createFromClone($socid=0,$hookmanager=false)
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
        $this->user_author_id     = $user->id;
        $this->user_valid         = '';
        $this->date_creation      = '';
        $this->date_validation    = '';
        $this->ref_client         = '';

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
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('ORDER_CLONE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
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
     *  Load an object from a proposal and create a new order into database
     *
     *  @param      Object			$object 	        Object source
     *  @return     int             					<0 if KO, 0 if nothing done, 1 if OK
     */
    function createFromProposal($object)
    {
        global $conf,$user,$langs;

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
            $this->note                 = $object->note;
            $this->note_public          = $object->note_public;

            $this->origin      = $object->element;
            $this->origin_id   = $object->id;

            $ret = $this->create($user);

            if ($ret > 0)
            {
                // Actions hooked (by external module)
                include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');
                $hookmanager=new HookManager($this->db);
                $hookmanager->callHooks(array('orderdao'));

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
                    return 1;
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
     *	@param      int				$commandeid      	Id of line
     *	@param      string			$desc            	Description of line
     *	@param      double			$pu_ht    	        Unit price (without tax)
     *	@param      double			$qty             	Quantite
     *	@param      double			$txtva           	Taux de tva force, sinon -1
     *	@param      double			$txlocaltax1		Local tax 1 rate
     *	@param      double			$txlocaltax2		Local tax 2 rate
     *	@param      int				$fk_product      	Id du produit/service predefini
     *	@param      double			$remise_percent  	Pourcentage de remise de la ligne
     *	@param      int				$info_bits			Bits de type de lignes
     *	@param      int				$fk_remise_except	Id remise
     *	@param      string			$price_base_type	HT or TTC
     *	@param      double			$pu_ttc    		    Prix unitaire TTC
     *	@param      timestamp		$date_start       	Start date of the line - Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
     *	@param      timestamp		$date_end         	End date of the line - Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
     *	@param      int				$type				Type of line (0=product, 1=service)
     *	@param      int				$rang             	Position of line
     *	@return     int             					>0 if OK, <0 if KO
     *
     *	@see        add_product
     *
     *	Les parametres sont deja cense etre juste et avec valeurs finales a l'appel
     *	de cette methode. Aussi, pour le taux tva, il doit deja avoir ete defini
     *	par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,produit)
     *	et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
     */
    function addline($commandeid, $desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $info_bits=0, $fk_remise_except=0, $price_base_type='HT', $pu_ttc=0, $date_start='', $date_end='', $type=0, $rang=-1, $special_code=0, $fk_parent_line=0)
    {
        dol_syslog("Commande::addline commandeid=$commandeid, desc=$desc, pu_ht=$pu_ht, qty=$qty, txtva=$txtva, fk_product=$fk_product, remise_percent=$remise_percent, info_bits=$info_bits, fk_remise_except=$fk_remise_except, price_base_type=$price_base_type, pu_ttc=$pu_ttc, date_start=$date_start, date_end=$date_end, type=$type", LOG_DEBUG);

        include_once(DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php');

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
        $desc=trim($desc);

        // Check parameters
        if ($type < 0) return -1;

        if ($this->statut == 0)
        {
            $this->db->begin();

            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
            $tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits);
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

            $this->line->fk_commande=$commandeid;
            $this->line->desc=$desc;
            $this->line->qty=$qty;
            $this->line->tva_tx=$txtva;
            $this->line->localtax1_tx=$txlocaltax1;
            $this->line->localtax2_tx=$txlocaltax2;
            $this->line->fk_product=$fk_product;
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

            // TODO Ne plus utiliser
            $this->line->price=$price;
            $this->line->remise=$remise;

            $result=$this->line->insert();
            if ($result > 0)
            {
                // Reorder if child line
                if (! empty($fk_parent_line)) $this->line_order(true,'DESC');

                // Mise a jour informations denormalisees au niveau de la commande meme
                $this->id=$commandeid;	// TODO A virer
                $result=$this->update_price(1);
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
                dol_syslog("Commande::addline error=".$this->error, LOG_ERR);
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
     *	@param		double			$qty				Quantity
     *	@param		double			$remise_percent		Product discount relative
     * 	@param    	timestamp		$date_start         Start date of the line - Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
     * 	@param    	timestamp		$date_end           End date of the line - Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
     * 	@return    	void
     *
     *	TODO	Remplacer les appels a cette fonction par generation objet Ligne
     *			insere dans tableau $this->products
     */
    function add_product($idproduct, $qty, $remise_percent=0, $date_start='', $date_end='')
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
        $sql.= ', c.amount_ht, c.total_ht, c.total_ttc, c.tva as total_tva, c.localtax1 as total_localtax1, c.localtax2 as total_localtax2, c.fk_cond_reglement, c.fk_mode_reglement, c.fk_availability, c.fk_demand_reason';
        $sql.= ', c.date_commande';
        $sql.= ', c.date_livraison';
        $sql.= ', c.fk_projet, c.remise_percent, c.remise, c.remise_absolue, c.source, c.facture as facturee';
        $sql.= ', c.note, c.note_public, c.ref_client, c.ref_ext, c.ref_int, c.model_pdf, c.fk_adresse_livraison';
        $sql.= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
        $sql.= ', cr.code as cond_reglement_code, cr.libelle as cond_reglement_libelle, cr.libelle_facture as cond_reglement_libelle_doc';
        $sql.= ', ca.code as availability_code';
        $sql.= ', dr.code as demand_reason_code';
        $sql.= ', el.fk_source';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'commande as c';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as cr ON (c.fk_cond_reglement = cr.rowid)';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON (c.fk_mode_reglement = p.id)';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_availability as ca ON (c.fk_availability = ca.rowid)';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_input_reason as dr ON (c.fk_demand_reason = ca.rowid)';
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_target = c.rowid AND el.targettype = '".$this->element."'";
        $sql.= " WHERE c.entity = ".$conf->entity;
        if ($id)   	  $sql.= " AND c.rowid=".$id;
        if ($ref)     $sql.= " AND c.ref='".$this->db->escape($ref)."'";
        if ($ref_ext) $sql.= " AND c.ref_ext='".$this->db->escape($ref_ext)."'";
        if ($ref_int) $sql.= " AND c.ref_int='".$this->db->escape($ref_int)."'";

        dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            $obj = $this->db->fetch_object($result);
            if ($obj)
            {
                $this->id                     = $obj->rowid;
                $this->ref                    = $obj->ref;
                $this->ref_client             = $obj->ref_client;
                $this->ref_ext				  = $obj->ref_ext;
                $this->ref_int				  = $obj->ref_int;
                $this->socid                  = $obj->fk_soc;
                $this->statut                 = $obj->fk_statut;
                $this->user_author_id         = $obj->fk_user_author;
                $this->total_ht               = $obj->total_ht;
                $this->total_tva              = $obj->total_tva;
                $this->total_localtax1		  = $obj->total_localtax1;
                $this->total_localtax2		  = $obj->total_localtax2;
                $this->total_ttc              = $obj->total_ttc;
                $this->date                   = $this->db->jdate($obj->date_commande);
                $this->date_commande          = $this->db->jdate($obj->date_commande);
                $this->remise                 = $obj->remise;
                $this->remise_percent         = $obj->remise_percent;
                $this->remise_absolue         = $obj->remise_absolue;
                $this->source                 = $obj->source;
                $this->facturee               = $obj->facturee;
                $this->note                   = $obj->note;
                $this->note_public            = $obj->note_public;
                $this->fk_project             = $obj->fk_projet;
                $this->modelpdf               = $obj->model_pdf;
                $this->mode_reglement_id      = $obj->fk_mode_reglement;
                $this->mode_reglement_code    = $obj->mode_reglement_code;
                $this->mode_reglement         = $obj->mode_reglement_libelle;
                $this->cond_reglement_id      = $obj->fk_cond_reglement;
                $this->cond_reglement_code    = $obj->cond_reglement_code;
                $this->cond_reglement         = $obj->cond_reglement_libelle;
                $this->cond_reglement_doc     = $obj->cond_reglement_libelle_doc;
                $this->availability_id		  = $obj->fk_availability;
                $this->availability_code      = $obj->availability_code;
                $this->demand_reason_id		  = $obj->fk_demand_reason;
                $this->demand_reason_code     = $obj->demand_reason_code;
                $this->date_livraison         = $this->db->jdate($obj->date_livraison);
                $this->fk_delivery_address    = $obj->fk_adresse_livraison;
                $this->propale_id             = $obj->fk_source;

                $this->lines                 = array();

                if ($this->statut == 0) $this->brouillon = 1;

                $this->db->free();

                if ($this->propale_id)
                {
                    $sqlp = "SELECT ref";
                    $sqlp.= " FROM ".MAIN_DB_PREFIX."propal";
                    $sqlp.= " WHERE rowid = ".$this->propale_id;

                    $resqlprop = $this->db->query($sqlp);

                    if ($resqlprop)
                    {
                        $objp = $this->db->fetch_object($resqlprop);
                        $this->propale_ref = $objp->ref;
                        $this->db->free($resqlprop);
                    }
                }

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
                dol_syslog(get_class($this).'::fetch '.$this->error);
                return 0;
            }
        }
        else
        {
            dol_syslog(get_class($this).'::fetch Error rowid='.$id, LOG_ERR);
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

        include_once(DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php');
        include_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');

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

        $sql = 'SELECT l.rowid, l.fk_product, l.fk_parent_line, l.product_type, l.fk_commande, l.description, l.price, l.qty, l.tva_tx,';
        $sql.= ' l.localtax1_tx, l.localtax2_tx, l.fk_remise_except, l.remise_percent, l.subprice, l.marge_tx, l.marque_tx, l.rang, l.info_bits, l.special_code,';
        $sql.= ' l.total_ht, l.total_ttc, l.total_tva, l.total_localtax1, l.total_localtax2, l.date_start, l.date_end,';
        $sql.= ' p.ref as product_ref, p.description as product_desc, p.fk_product_type, p.label as product_label';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'commandedet as l';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON (p.rowid = l.fk_product)';
        $sql.= ' WHERE l.fk_commande = '.$this->id;
        if ($only_product) $sql .= ' AND p.fk_product_type = 0';
        $sql .= ' ORDER BY l.rang';

        dol_syslog("Commande::fetch_lines sql=".$sql,LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);

            $i = 0;
            while ($i < $num)
            {
                $objp = $this->db->fetch_object($result);

                $line = new OrderLine($this->db);

                $line->rowid            = $objp->rowid;				// \deprecated
                $line->id               = $objp->rowid;
                $line->fk_commande      = $objp->fk_commande;
                $line->commande_id      = $objp->fk_commande;			// \deprecated
                $line->desc             = $objp->description;  		// Description ligne
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
                $line->marge_tx         = $objp->marge_tx;
                $line->marque_tx        = $objp->marque_tx;
                $line->rang             = $objp->rang;
                $line->info_bits        = $objp->info_bits;
                $line->special_code		= $objp->special_code;
                $line->fk_parent_line	= $objp->fk_parent_line;

                $line->ref				= $objp->product_ref;		// TODO deprecated
                $line->product_ref		= $objp->product_ref;
                $line->libelle			= $objp->product_label;		// TODO deprecated
                $line->product_label	= $objp->product_label;
                $line->product_desc     = $objp->product_desc; 		// Description produit
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
            dol_syslog('Commande::fetch_lines: Error '.$this->error, LOG_ERR);
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
            if ($line->fk_product_type == 0) $nb++;
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
        $num=0;
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
        if ($filtre_statut >= 0) $sql.=' AND e.fk_statut = '.$filtre_statut;
        $sql.= ' GROUP BY cd.rowid, cd.fk_product';
        //print $sql;

        dol_syslog("Commande::loadExpeditions sql=".$sql,LOG_DEBUG);
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
            dol_syslog("Commande::loadExpeditions ".$this->error,LOG_ERR);
            return -1;
        }

    }

    /**
     * Returns a array with expeditions lines number
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
    function livraison_array($filtre_statut=-1)
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
    function stock_array($filtre_statut=-1)
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

        if ($this->statut == 0)
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
                        $this->error=$this->db->lasterror();
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
            $sql.= ' WHERE rowid = '.$this->id.' AND fk_statut = 0 ;';

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
            $sql.= ' WHERE rowid = '.$this->id.' AND fk_statut = 0 ;';

            dol_syslog("Commande::set_remise_absolue sql=$sql");

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
     *	@param      timestamp	$date		Date
     *	@return     int         			<0 if KO, >0 if OK
     */
    function set_date($user, $date)
    {
        if ($user->rights->commande->creer)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande";
            $sql.= " SET date_commande = ".($date ? $this->db->idate($date) : 'null');
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";

            dol_syslog("Commande::set_date sql=$sql",LOG_DEBUG);
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $this->date = $date;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dol_syslog("Commande::set_date ".$this->error,LOG_ERR);
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
     *	@param      timestamp		$date_livraison     Date de livraison
     *	@return     int         						<0 si ko, >0 si ok
     */
    function set_date_livraison($user, $date_livraison)
    {
        if ($user->rights->commande->creer)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande";
            $sql.= " SET date_livraison = ".($date_livraison ? "'".$this->db->idate($date_livraison)."'" : 'null');
            $sql.= " WHERE rowid = ".$this->id;

            dol_syslog("Commande::set_date_livraison sql=".$sql,LOG_DEBUG);
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $this->date_livraison = $date_livraison;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dol_syslog("Commande::set_date_livraison ".$this->error,LOG_ERR);
                return -1;
            }
        }
        else
        {
            return -2;
        }
    }

    /**
     *	Set address
     *
     *	@param      User		$user        	Object user making change
     *	@param      int			$fk_address	    Adress of delivery
     *	@return     int         				<0 ig KO, >0 if Ok
     */
    function set_adresse_livraison($user, $fk_address)
    {
        if ($user->rights->commande->creer)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_adresse_livraison = '".$fk_address."'";
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";

            if ($this->db->query($sql) )
            {
                $this->fk_delivery_address = $fk_address;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dol_syslog("Commande::set_adresse_livraison Erreur SQL");
                return -1;
            }
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
                dol_syslog("Commande::set_availability Erreur SQL");
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
            $sql.= " SET fk_demand_reason = '".$id."'";
            $sql.= " WHERE rowid = ".$this->id;

            if ($this->db->query($sql))
            {
                $this->fk_demand_reason = $id;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dol_syslog("Commande::set_demand_reason Erreur SQL");
                return -1;
            }
        }
    }

    /**
     *  Return list of orders (eventuelly filtered on a user) into an array
     *
     *  @param      int		$brouillon      0=non brouillon, 1=brouillon
     *  @param      User	$user           Objet user de filtre
     *  @return     int             		-1 if KO, array with result if OK
     */
    function liste_array($brouillon=0, $user='')
    {
        global $conf;

        $ga = array();

        $sql = "SELECT s.nom, s.rowid, c.rowid, c.ref";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
        $sql.= " WHERE c.entity = ".$conf->entity;
        $sql.= " AND c.fk_soc = s.rowid";
        if ($brouillon) $sql.= " AND c.fk_statut = 0";
        if ($user) $sql.= " AND c.fk_user_author <> ".$user->id;
        $sql .= " ORDER BY c.date_commande DESC";

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

                    $ga[$obj->rowid] = $obj->ref;
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
     *	Change les conditions de reglement de la commande
     *
     *	@param      int	$cond_reglement_id      Id de la nouvelle condition de reglement
     *	@return     int                    		>0 if OK, <0 if KO
     */
    function cond_reglement($cond_reglement_id)
    {
        dol_syslog('Commande::cond_reglement('.$cond_reglement_id.')');
        if ($this->statut >= 0)
        {
            $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
            $sql .= ' SET fk_cond_reglement = '.$cond_reglement_id;
            $sql .= ' WHERE rowid='.$this->id;
            if ( $this->db->query($sql) )
            {
                $this->cond_reglement_id = $cond_reglement_id;
                return 1;
            }
            else
            {
                dol_syslog('Commande::cond_reglement Erreur '.$sql.' - '.$this->db->error(), LOG_ERR);
                $this->error=$this->db->lasterror();
                return -1;
            }
        }
        else
        {
            dol_syslog('Commande::cond_reglement, etat commande incompatible', LOG_ERR);
            $this->error='Etat commande incompatible '.$this->statut;
            return -2;
        }
    }


    /**
     *  Change le mode de reglement
     *
     *  @param      int		$mode       Id du nouveau mode
     *  @return     int         		>0 si ok, <0 si ko
     */
    function mode_reglement($mode_reglement_id)
    {
        dol_syslog('Commande::mode_reglement('.$mode_reglement_id.')');
        if ($this->statut >= 0)
        {
            $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
            $sql .= ' SET fk_mode_reglement = '.$mode_reglement_id;
            $sql .= ' WHERE rowid='.$this->id;
            if ( $this->db->query($sql) )
            {
                $this->mode_reglement_id = $mode_reglement_id;
                return 1;
            }
            else
            {
                dol_syslog('Commande::mode_reglement Erreur '.$sql.' - '.$this->db->error(), LOG_ERR);
                $this->error=$this->db->lasterror();
                return -1;
            }
        }
        else
        {
            dol_syslog('Commande::mode_reglement, etat facture incompatible', LOG_ERR);
            $this->error='Etat commande incompatible '.$this->statut;
            return -2;
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
        if ($this->statut >= 0)
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
        if ($this->statut >= 0)
        {
            $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
            $sql .= ' SET fk_demand_reason = '.$demand_reason_id;
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
     *	@param      User	$user           User that make change
     *	@param      string	$ref_client     Customer ref
     *	@return     int             		<0 if KO, >0 if OK
     */
    function set_ref_client($user, $ref_client)
    {
        if ($user->rights->commande->creer)
        {
            dol_syslog('Commande::set_ref_client this->id='.$this->id.', ref_client='.$ref_client);

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
                dol_syslog('Commande::set_ref_client Erreur '.$this->error.' - '.$sql, LOG_ERR);
                return -2;
            }
        }
        else
        {
            return -1;
        }
    }


    /**
     *	Classify the order as invoiced
     *
     *	@return     int     <0 if ko, >0 if ok
     */
    function classer_facturee()
    {
        global $conf;

        $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande SET facture = 1';
        $sql .= ' WHERE rowid = '.$this->id.' AND fk_statut > 0 ;';
        if ($this->db->query($sql) )
        {
            if (($conf->global->PROPALE_CLASSIFIED_INVOICED_WITH_ORDER == 1) && $this->propale_id)
            {
                $propal = new Propal($this->db);
                $propal->fetch($this->propale_id);
                $propal->classer_facturee();
            }
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     *  Update a line in database
     *
     *  @param    	int				$rowid            	Id of line to update
     *  @param    	string			$desc             	Description de la ligne
     *  @param    	double			$pu               	Prix unitaire
     *  @param    	double			$qty              	Quantity
     *  @param    	double			$remise_percent   	Pourcentage de remise de la ligne
     *  @param    	double			$tva_tx           	Taux TVA
     * 	@param		double			$txlocaltax1		Local tax 1 rate
     *  @param		double			$txlocaltax2		Local tax 2 rate
     *  @param    	string			$price_base_type	HT or TTC
     *  @param    	int				$info_bits        	Miscellaneous informations on line
     *  @param    	timestamp		$date_start        	Start date of the line
     *  @param    	timestamp		$date_end          	End date of the line
     * 	@param		int				$type				Type of line (0=product, 1=service)
     *  @return   	int              					< 0 if KO, > 0 if OK
     */
    function updateline($rowid, $desc, $pu, $qty, $remise_percent=0, $txtva, $txlocaltax1=0,$txlocaltax2=0, $price_base_type='HT', $info_bits=0, $date_start='', $date_end='', $type=0, $fk_parent_line=0, $skip_update_total=0)
    {
        global $conf;

        dol_syslog("CustomerOrder::UpdateLine $rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, $price_base_type, $info_bits, $date_start, $date_end, $type");
        include_once(DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php');

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
            $pu = price2num($pu);
            $txtva=price2num($txtva);
            $txlocaltax1=price2num($txlocaltax1);
            $txlocaltax2=price2num($txlocaltax2);

            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
            $tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits);
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
            $this->line->desc=$desc;
            $this->line->qty=$qty;
            $this->line->tva_tx=$txtva;
            $this->line->localtax1_tx=$txlocaltax1;
            $this->line->localtax2_tx=$txlocaltax2;
            $this->line->remise_percent=$remise_percent;
            $this->line->subprice=$subprice;
            $this->line->info_bits=$info_bits;
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

            // TODO deprecated
            $this->line->price=$price;
            $this->line->remise=$remise;

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
                $this->error=$this->db->error();
                $this->db->rollback();
                dol_syslog("CustomerOrder::UpdateLine Error=".$this->error, LOG_ERR);
                return -1;
            }
        }
        else
        {
            $this->error="CustomerOrder::Updateline Order status makes operation forbidden";
            return -2;
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
        require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

        $error = 0;

        $this->db->begin();
        
        if (! $error && ! $notrigger)
        {
        	// Appel des triggers
        	include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        	$interface=new Interfaces($this->db);
        	$result=$interface->run_triggers('ORDER_DELETE',$this,$user,$langs,$conf);
        	if ($result < 0) {
        		$error++; $this->errors=$interface->errors;
        	}
        	// Fin appel triggers
        }
        
        if (! $error)
        {
        	// Delete order details
        	$sql = 'DELETE FROM '.MAIN_DB_PREFIX."commandedet WHERE fk_commande = ".$this->id;
        	dol_syslog("Commande::delete sql=".$sql);
        	if (! $this->db->query($sql) )
        	{
        		dol_syslog(get_class($this)."::delete error", LOG_ERR);
        		$error++;
        	}
        	
        	// Delete order
        	$sql = 'DELETE FROM '.MAIN_DB_PREFIX."commande WHERE rowid = ".$this->id;
        	dol_syslog(get_class($this)."::delete sql=".$sql, LOG_DEBUG);
        	if (! $this->db->query($sql) )
        	{
        		dol_syslog(get_class($this)."::delete error", LOG_ERR);
        		$error++;
        	}
        	
        	// Delete linked object
        	$res = $this->deleteObjectLinked();
        	if ($res < 0) $error++;
        	
        	// Delete linked contacts
        	$res = $this->delete_linked_contact();
        	if ($res < 0) $error++;
        	
        	// On efface le repertoire de pdf provisoire
        	$comref = dol_sanitizeFileName($this->ref);
        	if ($conf->commande->dir_output)
        	{
        		$dir = $conf->commande->dir_output . "/" . $comref ;
        		$file = $conf->commande->dir_output . "/" . $comref . "/" . $comref . ".pdf";
        		if (file_exists($file))	// We must delete all files before deleting directory
        		{
        			dol_delete_preview($this);
        	
        			if (!dol_delete_file($file))
        			{
        				$this->error=$langs->trans("ErrorCanNotDeleteFile",$file);
        				$this->db->rollback();
        				return 0;
        			}
        		}
        		if (file_exists($dir))
        		{
        			if (!dol_delete_dir($dir))
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
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *	Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *
     *	@param		User	$user   Object user
     *	@return     int     		<0 if KO, >0 if OK
     */
    function load_board($user)
    {
        global $conf, $user;

        $now=gmmktime();

        $this->nbtodo=$this->nbtodolate=0;
        $clause = " WHERE";

        $sql = "SELECT c.rowid, c.date_creation as datec, c.fk_statut";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
        if (!$user->rights->societe->client->voir && !$user->societe_id)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON c.fk_soc = sc.fk_soc";
            $sql.= " WHERE sc.fk_user = " .$user->id;
            $clause = " AND";
        }
        $sql.= $clause." c.entity = ".$conf->entity;
        $sql.= " AND c.fk_statut IN (1,2,3) AND c.facture = 0";
        if ($user->societe_id) $sql.=" AND c.fk_soc = ".$user->societe_id;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if ($obj->fk_statut != 3 && $this->db->jdate($obj->datec) < ($now - $conf->commande->client->warning_delay)) $this->nbtodolate++;
            }
            return 1;
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
     *  @param      int		$facturee    	if invoiced
     *	@param      int		$mode        	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *  @return     string					Label of status
     */
    function LibStatut($statut,$facturee,$mode)
    {
        global $langs;
        //print 'x'.$statut.'-'.$facturee;
        if ($mode == 0)
        {
            if ($statut==-1) return $langs->trans('StatusOrderCanceled');
            if ($statut==0) return $langs->trans('StatusOrderDraft');
            if ($statut==1) return $langs->trans('StatusOrderValidated');
            if ($statut==2) return $langs->trans('StatusOrderSentShort');
            if ($statut==3 && ! $facturee) return $langs->trans('StatusOrderToBill');
            if ($statut==3 && $facturee) return $langs->trans('StatusOrderProcessed');
        }
        if ($mode == 1)
        {
            if ($statut==-1) return $langs->trans('StatusOrderCanceledShort');
            if ($statut==0) return $langs->trans('StatusOrderDraftShort');
            if ($statut==1) return $langs->trans('StatusOrderValidatedShort');
            if ($statut==2) return $langs->trans('StatusOrderSentShort');
            if ($statut==3 && ! $facturee) return $langs->trans('StatusOrderToBillShort');
            if ($statut==3 && $facturee) return $langs->trans('StatusOrderProcessed');
        }
        if ($mode == 2)
        {
            if ($statut==-1) return img_picto($langs->trans('StatusOrderCanceled'),'statut5').' '.$langs->trans('StatusOrderCanceledShort');
            if ($statut==0) return img_picto($langs->trans('StatusOrderDraft'),'statut0').' '.$langs->trans('StatusOrderDraftShort');
            if ($statut==1) return img_picto($langs->trans('StatusOrderValidated'),'statut1').' '.$langs->trans('StatusOrderValidatedShort');
            if ($statut==2) return img_picto($langs->trans('StatusOrderOnProcess'),'statut3').' '.$langs->trans('StatusOrderSentShort');
            if ($statut==3 && ! $facturee) return img_picto($langs->trans('StatusOrderToBill'),'statut7').' '.$langs->trans('StatusOrderToBillShort');
            if ($statut==3 && $facturee) return img_picto($langs->trans('StatusOrderProcessed'),'statut6').' '.$langs->trans('StatusOrderProcessedShort');
        }
        if ($mode == 3)
        {
            if ($statut==-1) return img_picto($langs->trans('StatusOrderCanceled'),'statut5');
            if ($statut==0) return img_picto($langs->trans('StatusOrderDraft'),'statut0');
            if ($statut==1) return img_picto($langs->trans('StatusOrderValidated'),'statut1');
            if ($statut==2) return img_picto($langs->trans('StatusOrderSentShort'),'statut3');
            if ($statut==3 && ! $facturee) return img_picto($langs->trans('StatusOrderToBill'),'statut7');
            if ($statut==3 && $facturee) return img_picto($langs->trans('StatusOrderProcessed'),'statut6');
        }
        if ($mode == 4)
        {
            if ($statut==-1) return img_picto($langs->trans('StatusOrderCanceled'),'statut5').' '.$langs->trans('StatusOrderCanceled');
            if ($statut==0) return img_picto($langs->trans('StatusOrderDraft'),'statut0').' '.$langs->trans('StatusOrderDraft');
            if ($statut==1) return img_picto($langs->trans('StatusOrderValidated'),'statut1').' '.$langs->trans('StatusOrderValidated');
            if ($statut==2) return img_picto($langs->trans('StatusOrderSentShort'),'statut3').' '.$langs->trans('StatusOrderOnProcess');
            if ($statut==3 && ! $facturee) return img_picto($langs->trans('StatusOrderToBill'),'statut7').' '.$langs->trans('StatusOrderToBill');
            if ($statut==3 && $facturee) return img_picto($langs->trans('StatusOrderProcessed'),'statut6').' '.$langs->trans('StatusOrderProcessed');
        }
        if ($mode == 5)
        {
            if ($statut==-1) return $langs->trans('StatusOrderCanceledShort').' '.img_picto($langs->trans('StatusOrderCanceled'),'statut5');
            if ($statut==0) return $langs->trans('StatusOrderDraftShort').' '.img_picto($langs->trans('StatusOrderDraft'),'statut0');
            if ($statut==1) return $langs->trans('StatusOrderValidatedShort').' '.img_picto($langs->trans('StatusOrderValidated'),'statut1');
            if ($statut==2) return $langs->trans('StatusOrderSentShort').' '.img_picto($langs->trans('StatusOrderOnProcess'),'statut3');
            if ($statut==3 && ! $facturee) return $langs->trans('StatusOrderToBillShort').' '.img_picto($langs->trans('StatusOrderToBill'),'statut7');
            if ($statut==3 && $facturee) return $langs->trans('StatusOrderProcessedShort').' '.img_picto($langs->trans('StatusOrderProcessed'),'statut6');
        }
    }


    /**
     *	Return clicable link of object (with eventually picto)
     *
     *	@param      int			$withpicto      Add picto into link
     *	@param      int			$option         Where point the link
     *	@param      int			$max          	Max length to show
     *	@param      int			$short			Use short labels
     *	@return     string          		String with URL
     */
    function getNomUrl($withpicto=0,$option=0,$max=0,$short=0)
    {
        global $conf, $langs;

        $result='';

        if ($conf->expedition->enabled && ($option == 1 || $option == 2)) $url = DOL_URL_ROOT.'/expedition/shipment.php?id='.$this->id;
        else $url = DOL_URL_ROOT.'/commande/fiche.php?id='.$this->id;

        if ($short) return $url;

        $linkstart = '<a href="'.$url.'">';
        $linkend='</a>';

        $picto='order';
        $label=$langs->trans("ShowOrder").': '.$this->ref;

        if ($withpicto) $result.=($linkstart.img_object($label,$picto).$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$linkstart.$this->ref.$linkend;
        return $result;
    }


    /**
     *	Charge les informations d'ordre info dans l'objet commande
     *
     *	@param     int		$id       Id of order
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
        $this->note='This is a comment (private)';
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
                $line->remise_percent=00;
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

        $sql = 'SELECT l.rowid, l.fk_product, l.product_type, l.description, l.price, l.qty, l.tva_tx, ';
        $sql.= ' l.fk_remise_except, l.remise_percent, l.subprice, l.info_bits,l.rang,l.special_code,';
        $sql.= ' l.total_ht, l.total_tva, l.total_ttc,';
        $sql.= ' l.date_start,';
        $sql.= ' l.date_end,';
        $sql.= ' p.label as product_label, p.ref, p.fk_product_type, p.rowid as prodid, ';
        $sql.= ' p.description as product_desc';
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

                $this->lines[$i]->id				= $obj->rowid;
                $this->lines[$i]->description 		= $obj->description;
                $this->lines[$i]->fk_product		= $obj->fk_product;
                $this->lines[$i]->ref				= $obj->ref;
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
                $this->lines[$i]->special_code		= $obj->special_code;
                $this->lines[$i]->rang				= $obj->rang;
                $this->lines[$i]->date_start		= $this->db->jdate($obj->date_start);
                $this->lines[$i]->date_end			= $this->db->jdate($obj->date_end);

                $i++;
            }

            $this->db->free($resql);

            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog("Error sql=$sql, error=".$this->error,LOG_ERR);
            return -1;
        }
    }

}


/**
 *  \class      OrderLine
 *  \brief      Classe de gestion des lignes de commande
 */
class OrderLine
{
    var $db;
    var $error;

    var $oldline;

    // From llx_commandedet
    var $rowid;
    var $fk_parent_line;
    var $fk_facture;
    var $desc;          	// Description ligne
    var $fk_product;		// Id produit predefini
    var $product_type = 0;	// Type 0 = product, 1 = Service

    var $qty;				// Quantity (example 2)
    var $tva_tx;			// VAT Rate for product/service (example 19.6)
    var $localtax1_tx; 		// Local tax 1
    var $localtax2_tx; 		// Local tax 2
    var $subprice;      	// U.P. HT (example 100)
    var $remise_percent;	// % for line discount (example 20%)
    var $rang = 0;
    var $marge_tx;
    var $marque_tx;
    var $info_bits = 0;		// Bit 0: 	0 si TVA normal - 1 si TVA NPR
    // Bit 1:	0 ligne normale - 1 si ligne de remise fixe
    var $total_ht;			// Total HT  de la ligne toute quantite et incluant la remise ligne
    var $total_tva;			// Total TVA  de la ligne toute quantite et incluant la remise ligne
    var $total_localtax1;   // Total local tax 1 for the line
    var $total_localtax2;   // Total local tax 2 for the line
    var $total_ttc;			// Total TTC de la ligne toute quantite et incluant la remise ligne

    // Ne plus utiliser
    var $remise;
    var $price;

    // From llx_product
    var $ref;				// Reference produit
    var $product_libelle; 	// Label produit
    var $product_desc;  	// Description produit

    // Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
    // Start and end date of the line
    var $date_start;
    var $date_end;

    var $skip_update_total; // Skip update price total for special lines


    /**
     *      Constructor
     *
     *      @param     DoliDB	$DB      handler d'acces base de donnee
     */
    function OrderLine($DB)
    {
        $this->db= $DB;
    }

    /**
     *  Load line order
     *
     *  @param     rowid           id line order
     */
    function fetch($rowid)
    {
        $sql = 'SELECT cd.rowid, cd.fk_commande, cd.fk_parent_line, cd.fk_product, cd.product_type, cd.description, cd.price, cd.qty, cd.tva_tx, cd.localtax1_tx, cd.localtax2_tx,';
        $sql.= ' cd.remise, cd.remise_percent, cd.fk_remise_except, cd.subprice,';
        $sql.= ' cd.info_bits, cd.total_ht, cd.total_tva, cd.total_localtax1, cd.total_localtax2, cd.total_ttc, cd.marge_tx, cd.marque_tx, cd.rang, cd.special_code,';
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
            $this->total_ht         = $objp->total_ht;
            $this->total_tva        = $objp->total_tva;
            $this->total_localtax1  = $objp->total_localtax1;
            $this->total_localtax2  = $objp->total_localtax2;
            $this->total_ttc        = $objp->total_ttc;
            $this->marge_tx         = $objp->marge_tx;
            $this->marque_tx        = $objp->marque_tx;
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

        $sql = 'DELETE FROM '.MAIN_DB_PREFIX."commandedet WHERE rowid='".$this->rowid."';";

        dol_syslog("OrderLine::delete sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('LINEORDER_DELETE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog("OrderLine::delete ".$this->error, LOG_ERR);
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

        dol_syslog("OrderLine::insert rang=".$this->rang);

        // Clean parameters
        if (empty($this->tva_tx)) $this->tva_tx=0;
        if (empty($this->localtax1_tx)) $this->localtax1_tx=0;
        if (empty($this->localtax2_tx)) $this->localtax2_tx=0;
        if (empty($this->total_localtax1)) $this->total_localtax1=0;
        if (empty($this->total_localtax2)) $this->total_localtax2=0;
        if (empty($this->rang)) $this->rang=0;
        if (empty($this->remise)) $this->remise=0;
        if (empty($this->remise_percent)) $this->remise_percent=0;
        if (empty($this->info_bits)) $this->info_bits=0;
        if (empty($this->special_code)) $this->special_code=0;
        if (empty($this->fk_parent_line)) $this->fk_parent_line=0;

        // Check parameters
        if ($this->product_type < 0) return -1;

        $this->db->begin();

        // Insertion dans base de la ligne
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'commandedet';
        $sql.= ' (fk_commande, fk_parent_line, description, qty, tva_tx, localtax1_tx, localtax2_tx,';
        $sql.= ' fk_product, product_type, remise_percent, subprice, price, remise, fk_remise_except,';
        $sql.= ' special_code, rang, marge_tx, marque_tx,';
        $sql.= ' info_bits, total_ht, total_tva, total_localtax1, total_localtax2, total_ttc, date_start, date_end)';
        $sql.= " VALUES (".$this->fk_commande.",";
        $sql.= " ".($this->fk_parent_line>0?"'".$this->fk_parent_line."'":"null").",";
        $sql.= " '".$this->db->escape($this->desc)."',";
        $sql.= " '".price2num($this->qty)."',";
        $sql.= " '".price2num($this->tva_tx)."',";
        $sql.= " '".price2num($this->localtax1_tx)."',";
        $sql.= " '".price2num($this->localtax2_tx)."',";
        if ($this->fk_product) { $sql.= "'".$this->fk_product."',"; }
        else { $sql.='null,'; }
        $sql.= " '".$this->product_type."',";
        $sql.= " '".price2num($this->remise_percent)."',";
        $sql.= " ".($this->subprice!=''?"'".price2num($this->subprice)."'":"null").",";
        $sql.= " ".($this->price!=''?"'".price2num($this->price)."'":"null").",";
        $sql.= " '".price2num($this->remise)."',";
        if ($this->fk_remise_except) $sql.= $this->fk_remise_except.",";
        else $sql.= 'null,';
        $sql.= ' '.$this->special_code.',';
        $sql.= ' '.$this->rang.',';
        if (isset($this->marge_tx)) $sql.= ' '.$this->marge_tx.',';
        else $sql.= ' null,';
        if (isset($this->marque_tx)) $sql.= ' '.$this->marque_tx.',';
        else $sql.= ' null,';
        $sql.= " '".$this->info_bits."',";
        $sql.= " '".price2num($this->total_ht)."',";
        $sql.= " '".price2num($this->total_tva)."',";
        $sql.= " '".price2num($this->total_localtax1)."',";
        $sql.= " '".price2num($this->total_localtax2)."',";
        $sql.= " '".price2num($this->total_ttc)."',";
        if ($this->date_start) { $sql.= "'".$this->db->idate($this->date_start)."',"; }
        else { $sql.='null,'; }
        if ($this->date_end)   { $sql.= "'".$this->db->idate($this->date_end)."'"; }
        else { $sql.='null'; }
        $sql.= ')';

        dol_syslog("OrderLine::insert sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->rowid=$this->db->last_insert_id(MAIN_DB_PREFIX.'commandedet');

            if (! $notrigger)
            {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('LINEORDER_INSERT',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers
            }

            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog("OrderLine::insert Error ".$this->error, LOG_ERR);
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
        if (empty($this->qty)) $this->qty=0;
        if (empty($this->total_localtax1)) $this->total_localtax1=0;
        if (empty($this->total_localtax2)) $this->total_localtax2=0;
        if (empty($this->marque_tx)) $this->marque_tx=0;
        if (empty($this->marge_tx)) $this->marge_tx=0;
        if (empty($this->remise)) $this->remise=0;
        if (empty($this->remise_percent)) $this->remise_percent=0;
        if (empty($this->info_bits)) $this->info_bits=0;
        if (empty($this->product_type)) $this->product_type=0;
        if (empty($this->fk_parent_line)) $this->fk_parent_line=0;

        $this->db->begin();

        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."commandedet SET";
        $sql.= " description='".$this->db->escape($this->desc)."'";
        $sql.= " , tva_tx=".price2num($this->tva_tx);
        $sql.= " , localtax1_tx=".price2num($this->localtax1_tx);
        $sql.= " , localtax2_tx=".price2num($this->localtax2_tx);
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
        }
        $sql.= " , total_localtax1=".price2num($this->total_localtax1);
        $sql.= " , total_localtax2=".price2num($this->total_localtax2);
        $sql.= " , info_bits=".$this->info_bits;
        if ($this->date_start) { $sql.= " , date_start='".$this->db->idate($this->date_start)."'"; }
        else { $sql.=' , date_start=null'; }
        if ($this->date_end) { $sql.= " , date_end='".$this->db->idate($this->date_end)."'"; }
        $sql.= " , product_type=".$this->product_type;
        $sql.= " , fk_parent_line=".($this->fk_parent_line>0?$this->fk_parent_line:"null");
        if (! empty($this->rang)) $sql.= ", rang=".$this->rang;
        $sql.= " WHERE rowid = ".$this->rowid;

        dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if (! $notrigger)
            {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result = $interface->run_triggers('LINEORDER_UPDATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
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

        dol_syslog("OrderLine::update_total sql=$sql");

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog("OrderLine::update_total Error ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -2;
        }
    }
}

?>
