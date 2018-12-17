<?php
/* Copyright (C) 2003-2008	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2007		Franky Van Liedekerke	<franky.van.liedekerke@telenet.be>
 * Copyright (C) 2006-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2017	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2014		Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2014-2015  Francis Appels          <francis.appels@yahoo.com>
 * Copyright (C) 2015       Claudio Aschieri        <c.aschieri@19.coop>
 * Copyright (C) 2016		Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2018		Quentin Vial-Gouteyron  <quentin.vial-gouteyron@atm-consulting.fr>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/reception/class/reception.class.php
 *  \ingroup    reception
 *  \brief      Fichier de la classe de gestion des receptions
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/commonobjectline.class.php";
if (! empty($conf->propal->enabled)) require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';


/**
 *	Class to manage receptions
 */
class Reception extends CommonObject
{
	public $element="reception";
	public $fk_element="fk_reception";
	public $table_element="reception";
	public $table_element_line="commande_fournisseur_dispatch";
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
    public $picto = 'reception';

	var $socid;
	var $ref_supplier;
	var $ref_int;
	var $brouillon;
	var $entrepot_id;
	var $lines=array();
	var $tracking_number;
	var $tracking_url;
	var $billed;
	var $model_pdf;

	var $trueWeight;
	var $weight_units;
	var $trueWidth;
	var $width_units;
	var $trueHeight;
	var $height_units;
	var $trueDepth;
	var $depth_units;
	// A denormalized value
	var $trueSize;

	var $date_delivery;		// Date delivery planed


	/**
	 * Effective delivery date
	 * @var int
	 */
	public $date_reception;
	var $date_creation;
	var $date_valid;

	var $meths;
	var $listmeths;			// List of carriers


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CLOSED = 2;



	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->lines = array();
		$this->products = array();

		// List of long language codes for status
		$this->statuts = array();
		$this->statuts[-1] = 'StatusReceptionCanceled';
		$this->statuts[0]  = 'StatusReceptionDraft';
		$this->statuts[1]  = 'StatusReceptionValidated';
		$this->statuts[2]  = 'StatusReceptionProcessed';
	}

	/**
	 *	Return next contract ref
	 *
	 *	@param	Societe		$soc	Thirdparty object
	 *	@return string				Free reference for contract
	 */
	function getNextNumRef($soc)
	{
		global $langs, $conf;
		$langs->load("receptions");

	    if (!empty($conf->global->RECEPTION_ADDON_NUMBER))
        {
			$mybool = false;

			$file = $conf->global->RECEPTION_ADDON_NUMBER.".php";
			$classname = $conf->global->RECEPTION_ADDON_NUMBER;

	        // Include file with class
	        $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

	        foreach ($dirmodels as $reldir) {

		        $dir = dol_buildpath($reldir."core/modules/reception/");

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
			$numref = $obj->getNextValue($soc,$this);

			if ( $numref != "")
			{
				return $numref;
			}
			else
			{
				dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
				return "";
			}
        }
	    else
	    {
		    print $langs->trans("Error")." ".$langs->trans("Error_RECEPTION_ADDON_NUMBER_NotDefined");
		    return "";
	    }
	}

	/**
	 *  Create reception en base
	 *
	 *  @param	User	$user       Objet du user qui cree
	 *  @param	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return int 				<0 si erreur, id reception creee si ok
	 */
	function create($user, $notrigger=0)
	{
		global $conf, $hookmanager;

		$now=dol_now();

		require_once DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php';
		$error = 0;

		// Clean parameters
		$this->brouillon = 1;
		$this->tracking_number = dol_sanitizeFileName($this->tracking_number);
		if (empty($this->fk_project)) $this->fk_project = 0;

		$this->user = $user;


		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."reception (";
		$sql.= "ref";
		$sql.= ", entity";
		$sql.= ", ref_supplier";
		$sql.= ", ref_int";
		$sql.= ", date_creation";
		$sql.= ", fk_user_author";
		$sql.= ", date_reception";
		$sql.= ", date_delivery";
		$sql.= ", fk_soc";
		$sql.= ", fk_projet";
		$sql.= ", fk_shipping_method";
		$sql.= ", tracking_number";
		$sql.= ", weight";
		$sql.= ", size";
		$sql.= ", width";
		$sql.= ", height";
		$sql.= ", weight_units";
		$sql.= ", size_units";
		$sql.= ", note_private";
		$sql.= ", note_public";
		$sql.= ", model_pdf";
		$sql.= ", fk_incoterms, location_incoterms";
		$sql.= ") VALUES (";
		$sql.= "'(PROV)'";
		$sql.= ", ".$conf->entity;
		$sql.= ", ".($this->ref_supplier?"'".$this->db->escape($this->ref_supplier)."'":"null");
		$sql.= ", ".($this->ref_int?"'".$this->db->escape($this->ref_int)."'":"null");
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", ".$user->id;
		$sql.= ", ".($this->date_reception>0?"'".$this->db->idate($this->date_reception)."'":"null");
		$sql.= ", ".($this->date_delivery>0?"'".$this->db->idate($this->date_delivery)."'":"null");
		$sql.= ", ".$this->socid;
		$sql.= ", ".$this->fk_project;
		$sql.= ", ".($this->shipping_method_id>0?$this->shipping_method_id:"null");
		$sql.= ", '".$this->db->escape($this->tracking_number)."'";
		$sql.= ", ".$this->weight;
		$sql.= ", ".$this->sizeS;	// TODO Should use this->trueDepth
		$sql.= ", ".$this->sizeW;	// TODO Should use this->trueWidth
		$sql.= ", ".$this->sizeH;	// TODO Should use this->trueHeight
		$sql.= ", ".$this->weight_units;
		$sql.= ", ".$this->size_units;
		$sql.= ", ".(!empty($this->note_private)?"'".$this->db->escape($this->note_private)."'":"null");
		$sql.= ", ".(!empty($this->note_public)?"'".$this->db->escape($this->note_public)."'":"null");
		$sql.= ", ".(!empty($this->model_pdf)?"'".$this->db->escape($this->model_pdf)."'":"null");
        $sql.= ", ".(int) $this->fk_incoterms;
        $sql.= ", '".$this->db->escape($this->location_incoterms)."'";
		$sql.= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);

		$resql=$this->db->query($sql);

		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."reception");

			$sql = "UPDATE ".MAIN_DB_PREFIX."reception";
			$sql.= " SET ref = '(PROV".$this->id.")'";
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			if ($this->db->query($sql))
			{
				// Insertion des lignes
				$num=count($this->lines);
				for ($i = 0; $i < $num; $i++)
				{
					$this->lines[$i]->fk_reception = $this->id;

					if (! $this->lines[$i]->create($user) > 0)
					{
						$error++;
					}
				}

				if (! $error && $this->id && $this->origin_id)
				{
					$ret = $this->add_object_linked();
					if (!$ret)
					{
						$error++;
					}
				}

				// Actions on extra fields (by external module or standard code)
				// TODO le hook fait double emploi avec le trigger !!
				$action='add';
				$hookmanager->initHooks(array('receptiondao'));
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

				if (! $error && ! $notrigger)
				{
                    // Call trigger
                    $result=$this->call_trigger('RECEPTION_CREATE',$user);
                    if ($result < 0) { $error++; }
                    // End call triggers

					if (! $error)
					{
						$this->db->commit();
						return $this->id;
					}
					else
					{
						foreach($this->errors as $errmsg)
						{
							dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
							$this->error.=($this->error?', '.$errmsg:$errmsg);
						}
						$this->db->rollback();
						return -1*$error;
					}
				}
				else
				{
					$error++;
					$this->error=$this->db->lasterror()." - sql=$sql";
					$this->db->rollback();
					return -3;
				}
			}
			else
			{
				$error++;
				$this->error=$this->db->lasterror()." - sql=$sql";
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$error++;
			$this->error=$this->db->error()." - sql=$sql";
			$this->db->rollback();
			return -1;
		}
	}



	/**
	 *	Get object and lines from database
	 *
	 *	@param	int		$id       	Id of object to load
	 * 	@param	string	$ref		Ref of object
	 * 	@param	string	$ref_ext	External reference of object
     * 	@param	string	$ref_int	Internal reference of other object
	 *	@return int			        >0 if OK, 0 if not found, <0 if KO
	 */
	function fetch($id, $ref='', $ref_ext='', $ref_int='')
	{
		global $conf;

		// Check parameters
		if (empty($id) && empty($ref) && empty($ref_ext) && empty($ref_int)) return -1;

		$sql = "SELECT e.rowid, e.ref, e.fk_soc as socid, e.date_creation, e.ref_supplier, e.ref_ext, e.ref_int, e.fk_user_author, e.fk_statut";
		$sql.= ", e.weight, e.weight_units, e.size, e.size_units, e.width, e.height";
		$sql.= ", e.date_reception as date_reception, e.model_pdf,  e.date_delivery";
		$sql.= ", e.fk_shipping_method, e.tracking_number";
		$sql.= ", el.fk_source as origin_id, el.sourcetype as origin";
		$sql.= ", e.note_private, e.note_public";
        $sql.= ', e.fk_incoterms, e.location_incoterms';
        $sql.= ', i.libelle as libelle_incoterms';
		$sql.= " FROM ".MAIN_DB_PREFIX."reception as e";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_target = e.rowid AND el.targettype = '".$this->db->escape($this->element)."'";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON e.fk_incoterms = i.rowid';
		$sql.= " WHERE e.entity IN (".getEntity('reception').")";
		if ($id)   	  $sql.= " AND e.rowid=".$id;
        if ($ref)     $sql.= " AND e.ref='".$this->db->escape($ref)."'";
        if ($ref_ext) $sql.= " AND e.ref_ext='".$this->db->escape($ref_ext)."'";
        if ($ref_int) $sql.= " AND e.ref_int='".$this->db->escape($ref_int)."'";

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id                   = $obj->rowid;
				$this->ref                  = $obj->ref;
				$this->socid                = $obj->socid;
				$this->ref_supplier			= $obj->ref_supplier;
				$this->ref_ext				= $obj->ref_ext;
				$this->ref_int				= $obj->ref_int;
				$this->statut               = $obj->fk_statut;
				$this->user_author_id       = $obj->fk_user_author;
				$this->date_creation        = $this->db->jdate($obj->date_creation);
				$this->date                 = $this->db->jdate($obj->date_reception);	// TODO deprecated
				$this->date_reception      = $this->db->jdate($obj->date_reception);	// TODO deprecated
				$this->date_reception        = $this->db->jdate($obj->date_reception);	// Date real
				$this->date_delivery        = $this->db->jdate($obj->date_delivery);	// Date planed
				$this->fk_delivery_address  = $obj->fk_address;
				$this->modelpdf             = $obj->model_pdf;
				$this->shipping_method_id	= $obj->fk_shipping_method;
				$this->tracking_number      = $obj->tracking_number;
				$this->origin               = ($obj->origin?$obj->origin:'commande'); // For compatibility
				$this->origin_id            = $obj->origin_id;
				$this->billed				= ($obj->fk_statut==2?1:0);

				$this->trueWeight           = $obj->weight;
				$this->weight_units         = $obj->weight_units;

				$this->trueWidth            = $obj->width;
				$this->width_units          = $obj->size_units;
				$this->trueHeight           = $obj->height;
				$this->height_units         = $obj->size_units;
				$this->trueDepth            = $obj->size;
				$this->depth_units          = $obj->size_units;

				$this->note_public          = $obj->note_public;
				$this->note_private         = $obj->note_private;

				// A denormalized value
				$this->trueSize           	= $obj->size."x".$obj->width."x".$obj->height;
				$this->size_units           = $obj->size_units;

				//Incoterms
				$this->fk_incoterms = $obj->fk_incoterms;
				$this->location_incoterms = $obj->location_incoterms;
				$this->libelle_incoterms = $obj->libelle_incoterms;

				$this->db->free($result);

				if ($this->statut == 0) $this->brouillon = 1;

				$file = $conf->reception->dir_output . "/" .get_exdir($this->id, 2, 0, 0, $this, 'reception') . "/" . $this->id.".pdf";
				$this->pdf_filename = $file;

				// Tracking url
				$this->getUrlTrackingStatus($obj->tracking_number);

				/*
				 * Thirparty
				 */
				$result=$this->fetch_thirdparty();


				// Retrieve all extrafields for reception
				// fetch optionals attributes and labels
				require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
				$extrafields=new ExtraFields($this->db);
				$extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
				$this->fetch_optionals($this->id,$extralabels);

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
				dol_syslog(get_class($this).'::Fetch no reception found', LOG_ERR);
				$this->error='Delivery with id '.$id.' not found';
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
	 *  Validate object and update stock if option enabled
	 *
	 *  @param      User		$user       Object user that validate
     *  @param		int			$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return     int						<0 if OK, >0 if KO
	 */
	function valid($user, $notrigger=0)
	{
		global $conf, $langs;

        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		dol_syslog(get_class($this)."::valid");

		// Protection
		if ($this->statut)
		{
			dol_syslog(get_class($this)."::valid no draft status", LOG_WARNING);
			return 0;
		}

        if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->reception->creer))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->reception->reception_advance->validate))))
		{
			$this->error='Permission denied';
			dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
			return -1;
		}

		$this->db->begin();

		$error = 0;

		// Define new ref
		$soc = new Societe($this->db);
		$soc->fetch($this->socid);


		// Define new ref
		if (! $error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) // empty should not happened, but when it occurs, the test save life
		{
			$numref = $this->getNextNumRef($soc);
		}
		else {
			$numref = $this->ref;
		}

        $this->newref = $numref;

		$now=dol_now();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX."reception SET";
		$sql.= " ref='".$this->db->escape($numref)."'";
		$sql.= ", fk_statut = 1";
		$sql.= ", date_valid = '".$this->db->idate($now)."'";
		$sql.= ", fk_user_valid = ".$user->id;
		$sql.= " WHERE rowid = ".$this->id;
		dol_syslog(get_class($this)."::valid update reception", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (! $resql)
		{
			$this->error=$this->db->lasterror();
			$error++;
		}

		// If stock increment is done on reception (recommanded choice)
		if (! $error && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_RECEPTION))
		{
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

			$langs->load("agenda");

			// Loop on each product line to add a stock movement
			// TODO in future, reception lines may not be linked to order line
			$sql = "SELECT cd.fk_product, cd.subprice,";
			$sql.= " ed.rowid, ed.qty, ed.fk_entrepot,";
			$sql.= " ed.eatby, ed.sellby, ed.batch";
			$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd,";
			$sql.= " ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as ed";
			$sql.= " WHERE ed.fk_reception = ".$this->id;
			$sql.= " AND cd.rowid = ed.fk_commandefourndet";

			dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$cpt = $this->db->num_rows($resql);
				for ($i = 0; $i < $cpt; $i++)
				{
					$obj = $this->db->fetch_object($resql);

					$qty = $obj->qty;

					if ($qty <= 0) continue;
					dol_syslog(get_class($this)."::valid movement index ".$i." ed.rowid=".$obj->rowid." edb.rowid=".$obj->edbrowid);

					//var_dump($this->lines[$i]);
					$mouvS = new MouvementStock($this->db);
					$mouvS->origin = &$this;

					if (empty($obj->batch))
					{
						// line without batch detail

						// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record.
						$result=$mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->subprice, $langs->trans("ReceptionValidatedInDolibarr",$numref));
						if ($result < 0) {
							$error++;
							$this->errors[]=$mouvS->error;
							$this->errors = array_merge($this->errors, $mouvS->errors);
							break;
						}
					}
					else
					{
						// line with batch detail

						// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record.
					    // Note: ->fk_origin_stock = id into table llx_product_batch (may be rename into llx_product_stock_batch in another version)
						$result=$mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->subprice, $langs->trans("ReceptionValidatedInDolibarr",$numref), $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch);
						if ($result < 0) {
							$error++;
							$this->errors[]=$mouvS->error;
							$this->errors = array_merge($this->errors, $mouvS->errors);
							break;
						}
					}
				}
			}
			else
			{
				$this->db->rollback();
				$this->error=$this->db->error();
				return -2;
			}
		}

		// Change status of order to "reception in process"
		$ret = $this->setStatut(4, $this->origin_id, 'commande_fournisseur');

        if (! $ret)
		{
		    $error++;
		}

		if (! $error && ! $notrigger)
		{
            // Call trigger
            $result=$this->call_trigger('RECEPTION_VALIDATE',$user);
            if ($result < 0) { $error++; }
            // End call triggers
		}

		if (! $error)
		{
            $this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref))
			{
				// On renomme repertoire ($this->ref = ancienne ref, $numfa = nouvelle ref)
				// in order not to lose the attached files
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($numref);
				$dirsource = $conf->reception->dir_output.'/'.$oldref;
				$dirdest = $conf->reception->dir_output.'/'.$newref;
				if (file_exists($dirsource))
				{
					dol_syslog(get_class($this)."::valid rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest))
					{
					    dol_syslog("Rename ok");
                        // Rename docs starting with $oldref with $newref
                        $listoffiles=dol_dir_list($conf->reception->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref,'/'));
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
			$this->ref = $numref;
			$this->statut = 1;
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
	            dol_syslog(get_class($this)."::valid ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
	}



	/**
	 * Add an reception line.
	 * If STOCK_WAREHOUSE_NOT_REQUIRED_FOR_RECEPTIONS is set, you can add a reception line, with no stock source defined
	 * If STOCK_MUST_BE_ENOUGH_FOR_RECEPTION is not set, you can add a reception line, even if not enough into stock
	 *
	 * @param 	int			$entrepot_id		Id of warehouse
	 * @param 	int			$id					Id of source line (supplier order line)
	 * @param 	int			$qty				Quantity
	 * @param	array		$array_options		extrafields array
	 * @param	string		$comment				Comment for stock movement
	 * @param	date		$eatby					eat-by date
	 * @param	date		$sellby					sell-by date
	 * @param	string		$batch					Lot number
	 * @return	int							<0 if KO, >0 if OK
	 */
	function addline($entrepot_id, $id, $qty, $array_options=0, $comment='', $eatby='', $sellby='', $batch='')
	{
		global $conf, $langs, $user;

		$num = count($this->lines);
		$line = new CommandeFournisseurDispatch($this->db);

		$line->fk_entrepot = $entrepot_id;
		$line->fk_commandefourndet = $id;
		$line->qty = $qty;

		$supplierorderline = new CommandeFournisseurLigne($this->db);
		$supplierorderline->fetch($id);

		if (! empty($conf->stock->enabled) && ! empty($supplierorderline->fk_product))
		{
			$fk_product = $supplierorderline->fk_product;

			if (! ($entrepot_id > 0) && empty($conf->global->STOCK_WAREHOUSE_NOT_REQUIRED_FOR_RECEPTIONS))
			{
			    $langs->load("errors");
				$this->error=$langs->trans("ErrorWarehouseRequiredIntoReceptionLine");
				return -1;
			}
		}

		// extrafields
		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($array_options) && count($array_options)>0) // For avoid conflicts if trigger used
			$line->array_options = $array_options;

		$line->fk_product = $fk_product;
		$line->fk_commande = $supplierorderline->fk_commande ;
		$line->fk_user = $user->id ;
		$line->comment = $comment;
		$line->batch = $batch;
		$line->eatby = $eatby;
		$line->sellby = $sellby;
		$line->status=1;
		$line->fk_reception=$this->id;

		$this->lines[$num] = $line;
	}


    /**
     *  Update database
     *
     *  @param	User	$user        	User that modify
     *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
     *  @return int 			       	<0 if KO, >0 if OK
     */
    function update($user=null, $notrigger=0)
    {
    	global $conf;
		$error=0;

		// Clean parameters

		if (isset($this->ref)) $this->ref=trim($this->ref);
		if (isset($this->entity)) $this->entity=trim($this->entity);
		if (isset($this->ref_supplier)) $this->ref_supplier=trim($this->ref_supplier);
		if (isset($this->socid)) $this->socid=trim($this->socid);
		if (isset($this->fk_user_author)) $this->fk_user_author=trim($this->fk_user_author);
		if (isset($this->fk_user_valid)) $this->fk_user_valid=trim($this->fk_user_valid);
		if (isset($this->fk_delivery_address)) $this->fk_delivery_address=trim($this->fk_delivery_address);
		if (isset($this->shipping_method_id)) $this->shipping_method_id=trim($this->shipping_method_id);
		if (isset($this->tracking_number)) $this->tracking_number=trim($this->tracking_number);
		if (isset($this->statut)) $this->statut=(int) $this->statut;
		if (isset($this->trueDepth)) $this->trueDepth=trim($this->trueDepth);
		if (isset($this->trueWidth)) $this->trueWidth=trim($this->trueWidth);
		if (isset($this->trueHeight)) $this->trueHeight=trim($this->trueHeight);
		if (isset($this->size_units)) $this->size_units=trim($this->size_units);
		if (isset($this->weight_units)) $this->weight_units=trim($this->weight_units);
		if (isset($this->trueWeight)) $this->weight=trim($this->trueWeight);
		if (isset($this->note_private)) $this->note=trim($this->note_private);
		if (isset($this->note_public)) $this->note=trim($this->note_public);
		if (isset($this->modelpdf)) $this->modelpdf=trim($this->modelpdf);


		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."reception SET";

		$sql.= " tms=".(dol_strlen($this->tms)!=0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
		$sql.= " ref=".(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"null").",";
		$sql.= " ref_supplier=".(isset($this->ref_supplier)?"'".$this->db->escape($this->ref_supplier)."'":"null").",";
		$sql.= " fk_soc=".(isset($this->socid)?$this->socid:"null").",";
		$sql.= " date_creation=".(dol_strlen($this->date_creation)!=0 ? "'".$this->db->idate($this->date_creation)."'" : 'null').",";
		$sql.= " fk_user_author=".(isset($this->fk_user_author)?$this->fk_user_author:"null").",";
		$sql.= " date_valid=".(dol_strlen($this->date_valid)!=0 ? "'".$this->db->idate($this->date_valid)."'" : 'null').",";
		$sql.= " fk_user_valid=".(isset($this->fk_user_valid)?$this->fk_user_valid:"null").",";
		$sql.= " date_reception=".(dol_strlen($this->date_reception)!=0 ? "'".$this->db->idate($this->date_reception)."'" : 'null').",";
		$sql.= " date_delivery=".(dol_strlen($this->date_delivery)!=0 ? "'".$this->db->idate($this->date_delivery)."'" : 'null').",";
		$sql.= " fk_shipping_method=".((isset($this->shipping_method_id) && $this->shipping_method_id > 0)?$this->shipping_method_id:"null").",";
		$sql.= " tracking_number=".(isset($this->tracking_number)?"'".$this->db->escape($this->tracking_number)."'":"null").",";
		$sql.= " fk_statut=".(isset($this->statut)?$this->statut:"null").",";
		$sql.= " height=".(($this->trueHeight != '')?$this->trueHeight:"null").",";
		$sql.= " width=".(($this->trueWidth != '')?$this->trueWidth:"null").",";
		$sql.= " size_units=".(isset($this->size_units)?$this->size_units:"null").",";
		$sql.= " size=".(($this->trueDepth != '')?$this->trueDepth:"null").",";
		$sql.= " weight_units=".(isset($this->weight_units)?$this->weight_units:"null").",";
		$sql.= " weight=".(($this->trueWeight != '')?$this->trueWeight:"null").",";
		$sql.= " note_private=".(isset($this->note_private)?"'".$this->db->escape($this->note_private)."'":"null").",";
		$sql.= " note_public=".(isset($this->note_public)?"'".$this->db->escape($this->note_public)."'":"null").",";
		$sql.= " model_pdf=".(isset($this->modelpdf)?"'".$this->db->escape($this->modelpdf)."'":"null").",";
		$sql.= " entity=".$conf->entity;

        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
                // Call trigger
                $result=$this->call_trigger('RECEPTION_MODIFY',$user);
                if ($result < 0) { $error++; }
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
	 * 	Delete reception.
	 *
	 *	@param	User	$user	Object user
	 * 	@return	int				>0 if OK, 0 if deletion done but failed to delete files, <0 if KO
	 */
	function delete(User $user)
	{
		global $conf, $langs, $user;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error=0;
		$this->error='';


		$this->db->begin();

		// Stock control
		if ($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_RECEPTION && $this->statut > 0)
		{
			require_once DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php";

			$langs->load("agenda");

			// Loop on each product line to add a stock movement
			$sql = "SELECT cd.fk_product, cd.subprice, ed.qty, ed.fk_entrepot, ed.eatby, ed.sellby, ed.batch, ed.rowid as commande_fournisseur_dispatch_id";
			$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd,";
			$sql.= " ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as ed";
			$sql.= " WHERE ed.fk_reception = ".$this->id;
			$sql.= " AND cd.rowid = ed.fk_commandefourndet";

			dol_syslog(get_class($this)."::delete select details", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$cpt = $this->db->num_rows($resql);
				for ($i = 0; $i < $cpt; $i++)
				{
					dol_syslog(get_class($this)."::delete movement index ".$i);
					$obj = $this->db->fetch_object($resql);

					$mouvS = new MouvementStock($this->db);
					// we do not log origin because it will be deleted
					$mouvS->origin = null;

					$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $obj->qty, 0, $langs->trans("ReceptionDeletedInDolibarr", $this->ref),'', $obj->eatby, $obj->sellby, $obj->batch);  // Price is set to 0, because we don't want to see WAP changed
				}
			}
			else
			{
				$error++;$this->errors[]="Error ".$this->db->lasterror();
			}
		}

		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch";
			$sql.= " WHERE fk_reception = ".$this->id;

			if ( $this->db->query($sql) )
			{
				// Delete linked object
				$res = $this->deleteObjectLinked();
				if ($res < 0) $error++;

				if (! $error)
				{
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."reception";
					$sql.= " WHERE rowid = ".$this->id;

					if ($this->db->query($sql))
					{
						// Call trigger
						$result=$this->call_trigger('RECEPTION_DELETE',$user);
						if ($result < 0) { $error++; }
						// End call triggers

						if (! empty($this->origin) && $this->origin_id > 0)
						{
						    $this->fetch_origin();
						    $origin=$this->origin;
						    if ($this->$origin->statut == 4)     // If order source of reception is "partially received"
						    {
                                // Check if there is no more reception. If not, we can move back status of order to "validated" instead of "reception in progress"
						        $this->$origin->loadReceptions();
						        //var_dump($this->$origin->receptions);exit;
						        if (count($this->$origin->receptions) <= 0)
						        {
                                    $this->$origin->setStatut(3); // ordered
						        }
						    }
						}

						if (! $error)
						{
							$this->db->commit();

							// We delete PDFs
							$ref = dol_sanitizeFileName($this->ref);
							if (! empty($conf->reception->dir_output))
							{
								$dir = $conf->reception->dir_output . '/' . $ref ;
								$file = $dir . '/' . $ref . '.pdf';
								if (file_exists($file))
								{
									if (! dol_delete_file($file))
									{
										return 0;
									}
								}
								if (file_exists($dir))
								{
									if (!dol_delete_dir_recursive($dir))
									{
										$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
										return 0;
									}
								}
							}

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
						$this->error=$this->db->lasterror()." - sql=$sql";
						$this->db->rollback();
						return -3;
					}
				}
				else
				{
					$this->error=$this->db->lasterror()." - sql=$sql";
					$this->db->rollback();
					return -2;
				}
			}
			else
			{
				$this->error=$this->db->lasterror()." - sql=$sql";
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Load lines
	 *
	 *	@return	int		>0 if OK, Otherwise if KO
	 */
	function fetch_lines()
	{
		// phpcs:enable
		global $db;
		dol_include_once('/fourn/class/fournisseur.commande.dispatch.class.php');
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'commande_fournisseur_dispatch WHERE fk_reception='.$this->id;
		$resql = $db->query($sql);

		if(!empty($resql)){
			$this->lines = array();
			while ($obj = $resql->fetch_object()){
				$line = new CommandeFournisseurDispatch($db);
				$line->fetch($obj->rowid);
				$line->fetch_product();
				$sql_commfourndet = 'SELECT qty, ref,  label, tva_tx, vat_src_code, subprice, multicurrency_subprice, remise_percent FROM llx_commande_fournisseurdet WHERE rowid='.$line->fk_commandefourndet;
				$resql_commfourndet = $db->query($sql_commfourndet);
				if(!empty($resql_commfourndet)){
					$obj = $db->fetch_object($resql_commfourndet);
					$line->qty_asked = $obj->qty;
					$line->description = $line->comment;
					$line->desc =  $line->comment;
					$line->tva_tx = $obj->tva_tx;
					$line->vat_src_code = $obj->vat_src_code;
					$line->subprice = $obj->subprice;
					$line->multicurrency_subprice = $obj->multicurrency_subprice;
					$line->remise_percent = $obj->remise_percent;
					$line->label = !empty($obj->label)?$obj->label:$line->product->label;
					$line->ref_supplier = $obj->ref;
				}else {
					$line->qty_asked = 0;
					$line->description = '';
					$line->label = $obj->label;
				}

				$pu_ht=($line->subprice*$line->qty)*(100-$line->remise_percent)/100;
				$tva = $pu_ht*$line->tva_tx/100;
				$this->total_ht += $pu_ht;
				$this->total_tva += $pu_ht*$line->tva_tx/100;

				$this->total_ttc += $pu_ht+$tva;


				$this->lines[]=$line;
			}

			return 1;
		}
		else {
			return -1;
		}
	}

	/**
     *	Return clicable link of object (with eventually picto)
     *
     *	@param      int			$withpicto      Add picto into link
     *	@param      int			$option         Where point the link
     *	@param      int			$max          	Max length to show
     *	@param      int			$short			Use short labels
     *  @param      int         $notooltip      1=No tooltip
     *	@return     string          			String with URL
     */
	function getNomUrl($withpicto=0,$option=0,$max=0,$short=0,$notooltip=0)
	{
		global $langs;
		$result='';
        $label = '<u>' . $langs->trans("ShowReception") . '</u>';
        $label .= '<br><b>' . $langs->trans('Ref') . ':</b> '.$this->ref;
        $label .= '<br><b>'.$langs->trans('RefSupplier').':</b> '.($this->ref_supplier ? $this->ref_supplier : $this->ref_client);

		$url = DOL_URL_ROOT.'/reception/card.php?id='.$this->id;

		if ($short) return $url;

		$linkclose='';
		if (empty($notooltip))
		{
		    if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
		    {
		        $label=$langs->trans("ShowReception");
		        $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
		    }
		    $linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
		    $linkclose.=' class="classfortooltip"';
		}

        $linkstart = '<a href="'.$url.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

		$picto='sending';

		if ($withpicto) $result.=($linkstart.img_object(($notooltip?'':$label), $picto, ($notooltip?'':'class="classfortooltip"'), 0, 0, $notooltip?0:1).$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		$result.=$linkstart.$this->ref.$linkend;
		return $result;
	}

	/**
     *	Return status label
     *
     *	@param      int		$mode      	0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto
     *	@return     string      		Libelle
     */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 * Return label of a status
	 *
	 * @param      int		$statut		Id statut
	 * @param      int		$mode       0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto
	 * @return     string				Label of status
	 */
	function LibStatut($statut,$mode)
	{
		// phpcs:enable
		global $langs;

		if ($mode==0)
		{
			if ($statut==0) return $langs->trans($this->statuts[$statut]);
			if ($statut==1)  return $langs->trans($this->statuts[$statut]);
			if ($statut==2)  return $langs->trans($this->statuts[$statut]);
		}
		if ($mode==1)
		{
			if ($statut==0) return $langs->trans('StatusReceptionDraftShort');
			if ($statut==1) return $langs->trans('StatusReceptionValidatedShort');
			if ($statut==2) return $langs->trans('StatusReceptionProcessedShort');
		}
		if ($mode == 3)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts[$statut]),'statut0');
			if ($statut==1) return img_picto($langs->trans($this->statuts[$statut]),'statut4');
			if ($statut==2) return img_picto($langs->trans('StatusReceptionProcessed'),'statut6');
		}
		if ($mode == 4)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts[$statut]),'statut0').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==1) return img_picto($langs->trans($this->statuts[$statut]),'statut4').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==2) return img_picto($langs->trans('StatusReceptionProcessed'),'statut6').' '.$langs->trans('StatusReceptionProcessed');
		}
		if ($mode == 5)
		{
			if ($statut==0) return $langs->trans('StatusReceptionDraftShort').' '.img_picto($langs->trans($this->statuts[$statut]),'statut0');
			if ($statut==1) return $langs->trans('StatusReceptionValidatedShort').' '.img_picto($langs->trans($this->statuts[$statut]),'statut4');
			if ($statut==2) return $langs->trans('StatusReceptionProcessedShort').' '.img_picto($langs->trans('StatusReceptionProcessedShort'),'statut6');
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
		global $langs;
		dol_include_once('/fourn/class/fournisseur.commande.dispatch.class.php');
		$now=dol_now();

		dol_syslog(get_class($this)."::initAsSpecimen");

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

		$order=new Commande($this->db);
		$order->initAsSpecimen();

		// Initialise parametres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$this->statut               = 1;
		$this->livraison_id         = 0;
		$this->date                 = $now;
		$this->date_creation        = $now;
		$this->date_valid           = $now;
		$this->date_delivery        = $now;
		$this->date_reception      = $now + 24*3600;

		$this->entrepot_id          = 0;
		$this->fk_delivery_address  = 0;
		$this->socid                = 1;

		$this->commande_id          = 0;
		$this->commande             = $order;

        $this->origin_id            = 1;
        $this->origin               = 'commande';

        $this->note_private			= 'Private note';
        $this->note_public			= 'Public note';

		$nbp = 5;
		$xnbp = 0;
		while ($xnbp < $nbp)
		{
			$line=new CommandeFournisseurDispatch($this->db);
			$line->desc=$langs->trans("Description")." ".$xnbp;
			$line->libelle=$langs->trans("Description")." ".$xnbp;
			$line->qty=10;

			$line->fk_product=$this->commande->lines[$xnbp]->fk_product;

			$this->lines[]=$line;
			$xnbp++;
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Set the planned delivery date
	 *
	 *	@param      User			$user        		Objet utilisateur qui modifie
	 *	@param      timestamp		$date_livraison     Date de livraison
	 *	@return     int         						<0 if KO, >0 if OK
	 */
	function set_date_livraison($user, $date_livraison)
	{
		// phpcs:enable
		if ($user->rights->reception->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."reception";
			$sql.= " SET date_delivery = ".($date_livraison ? "'".$this->db->idate($date_livraison)."'" : 'null');
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::set_date_livraison", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->date_delivery = $date_livraison;
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Fetch deliveries method and return an array. Load array this->meths(rowid=>label).
	 *
	 * 	@return	void
	 */
	function fetch_delivery_methods()
	{
		// phpcs:enable
		global $langs;
		$this->meths = array();

		$sql = "SELECT em.rowid, em.code, em.libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
		$sql.= " WHERE em.active = 1";
		$sql.= " ORDER BY em.libelle ASC";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
				$label=$langs->trans('ReceptionMethod'.$obj->code);
				$this->meths[$obj->rowid] = ($label != 'ReceptionMethod'.$obj->code?$label:$obj->libelle);
			}
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Fetch all deliveries method and return an array. Load array this->listmeths.
     *
     *  @param  int      $id     only this carrier, all if none
     *  @return void
     */
    function list_delivery_methods($id='')
    {
		// phpcs:enable
        global $langs;

        $this->listmeths = array();
        $i=0;

        $sql = "SELECT em.rowid, em.code, em.libelle, em.description, em.tracking, em.active";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
        if ($id!='') $sql.= " WHERE em.rowid=".$id;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            while ($obj = $this->db->fetch_object($resql))
            {
                $this->listmeths[$i]['rowid'] = $obj->rowid;
                $this->listmeths[$i]['code'] = $obj->code;
                $label=$langs->trans('ReceptionMethod'.$obj->code);
                $this->listmeths[$i]['libelle'] = ($label != 'ReceptionMethod'.$obj->code?$label:$obj->libelle);
                $this->listmeths[$i]['description'] = $obj->description;
                $this->listmeths[$i]['tracking'] = $obj->tracking;
                $this->listmeths[$i]['active'] = $obj->active;
                $i++;
            }
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Update/create delivery method.
     *
     *  @param	string      $id     id method to activate
     *
     *  @return void
     */
    function update_delivery_method($id='')
    {
		// phpcs:enable
        if ($id=='')
        {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_shipment_mode (code, libelle, description, tracking)";
            $sql.=" VALUES ('".$this->db->escape($this->update['code'])."','".$this->db->escape($this->update['libelle'])."','".$this->db->escape($this->update['description'])."','".$this->db->escape($this->update['tracking'])."')";
            $resql = $this->db->query($sql);
        }
        else
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."c_shipment_mode SET";
            $sql.= " code='".$this->db->escape($this->update['code'])."'";
            $sql.= ",libelle='".$this->db->escape($this->update['libelle'])."'";
            $sql.= ",description='".$this->db->escape($this->update['description'])."'";
            $sql.= ",tracking='".$this->db->escape($this->update['tracking'])."'";
            $sql.= " WHERE rowid=".$id;
            $resql = $this->db->query($sql);
        }
        if ($resql < 0) dol_print_error($this->db,'');
    }

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Activate delivery method.
     *
     *  @param      int      $id     id method to activate
     *
     *  @return void
     */
    function activ_delivery_method($id)
    {
		// phpcs:enable
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'c_shipment_mode SET active=1';
        $sql.= ' WHERE rowid='.$id;

        $resql = $this->db->query($sql);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  DesActivate delivery method.
     *
     *  @param      int      $id     id method to desactivate
     *
     *  @return void
     */
    function disable_delivery_method($id)
    {
		// phpcs:enable
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'c_shipment_mode SET active=0';
        $sql.= ' WHERE rowid='.$id;

        $resql = $this->db->query($sql);
    }


	/**
	 * Forge an set tracking url
	 *
	 * @param	string	$value		Value
	 * @return	void
	 */
	function getUrlTrackingStatus($value='')
	{
		if (! empty($this->shipping_method_id))
		{
			$sql = "SELECT em.code, em.tracking";
			$sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
			$sql.= " WHERE em.rowid = ".$this->shipping_method_id;

			$resql = $this->db->query($sql);
			if ($resql)
			{
				if ($obj = $this->db->fetch_object($resql))
				{
					$tracking = $obj->tracking;
				}
			}
		}

		if (!empty($tracking) && !empty($value))
		{
			$url = str_replace('{TRACKID}', $value, $tracking);
			$this->tracking_url = sprintf('<a target="_blank" href="%s">'.($value?$value:'url').'</a>',$url,$url);
		}
		else
		{
			$this->tracking_url = $value;
		}
	}

	/**
	 *	Classify the reception as closed.
	 *
	 *	@return     int     <0 if KO, >0 if OK
	 */
	function setClosed()
	{
		global $conf,$langs,$user;

		$error=0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'reception SET fk_statut='.self::STATUS_CLOSED;
		$sql .= ' WHERE rowid = '.$this->id.' AND fk_statut > 0';

		$resql=$this->db->query($sql);
		if ($resql)
		{
			// Set order billed if 100% of order is received (qty in reception lines match qty in order lines)
			if ($this->origin == 'order_supplier' && $this->origin_id > 0)
			{
				$order = new CommandeFournisseur($this->db);
				$order->fetch($this->origin_id);

				$order->loadReceptions(self::STATUS_CLOSED);		// Fill $order->receptions = array(orderlineid => qty)

				$receptions_match_order = 1;
				foreach($order->lines as $line)
				{
					$lineid = $line->id;
					$qty = $line->qty;
					if (($line->product_type == 0 || ! empty($conf->global->STOCK_SUPPORTS_SERVICES)) && $order->receptions[$lineid] < $qty)
					{
						$receptions_match_order = 0;
						$text='Qty for order line id '.$lineid.' is '.$qty.'. However in the receptions with status Reception::STATUS_CLOSED='.self::STATUS_CLOSED.' we have qty = '.$order->receptions[$lineid].', so we can t close order';
						dol_syslog($text);
						break;
					}
				}
				if ($receptions_match_order)
				{
					dol_syslog("Qty for the ".count($order->lines)." lines of order have same value for receptions with status Reception::STATUS_CLOSED=".self::STATUS_CLOSED.', so we close order');
					$order->Livraison($user, dol_now(), 'tot', 'Reception '.$this->ref);
				}
			}

			$this->statut=self::STATUS_CLOSED;


			// If stock increment is done on closing
			if (! $error && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_RECEPTION_CLOSE))
			{
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

				$langs->load("agenda");

				// Loop on each product line to add a stock movement
				// TODO possibilite de receptionner a partir d'une propale ou autre origine ?
				$sql = "SELECT cd.fk_product, cd.subprice,";
				$sql.= " ed.rowid, ed.qty, ed.fk_entrepot,";
				$sql.= " ed.eatby, ed.sellby, ed.batch";
				$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd,";
				$sql.= " ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as ed";
				$sql.= " WHERE ed.fk_reception = ".$this->id;
				$sql.= " AND cd.rowid = ed.fk_commandefourndet";

				dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
				$resql=$this->db->query($sql);

				if ($resql)
				{
					$cpt = $this->db->num_rows($resql);
					for ($i = 0; $i < $cpt; $i++)
					{
						$obj = $this->db->fetch_object($resql);

						$qty = $obj->qty;

						if ($qty <= 0) continue;
						dol_syslog(get_class($this)."::valid movement index ".$i." ed.rowid=".$obj->rowid." edb.rowid=".$obj->edbrowid);

						$mouvS = new MouvementStock($this->db);
						$mouvS->origin = &$this;

						if (empty($obj->batch))
						{
							// line without batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$result=$mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->subprice, $langs->trans("ReceptionClassifyClosedInDolibarr",$numref));
							if ($result < 0) {
							    $this->error = $mouvS->error;
							    $this->errors = $mouvS->errors;
								$error++; break;
							}
						}
						else
						{
							// line with batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$result=$mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->subprice, $langs->trans("ReceptionClassifyClosedInDolibarr",$numref),  $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch);

							if ($result < 0) {
							    $this->error = $mouvS->error;
							    $this->errors = $mouvS->errors;
							    $error++; break;
							}
						}
					}
				}
				else
				{
					$this->error=$this->db->lasterror();
					$error++;
				}
			}

			// Call trigger
			if (! $error)
			{
    			$result=$this->call_trigger('RECEPTION_CLOSED',$user);
    			if ($result < 0) {
    			    $error++;
    			}
			}
		}
		else
		{
			dol_print_error($this->db);
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Classify the reception as invoiced (used when WORKFLOW_BILL_ON_RECEPTION is on)
	 *
	 *	@return     int     <0 if ko, >0 if ok
	 */
	function set_billed()
	{
		// phpcs:enable
	    global $user;
		$error=0;

		$this->db->begin();

		$this->setClosed();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'reception SET  billed=1';
		$sql .= ' WHERE rowid = '.$this->id.' AND fk_statut > 0';

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->statut=2;
			$this->billed=1;

			// Call trigger
			$result=$this->call_trigger('RECEPTION_BILLED',$user);
			if ($result < 0) {
				$error++;
			}
		} else {
			$error++;
			$this->errors[]=$this->db->lasterror;
		}

		if (empty($error)) {
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
	 *	Classify the reception as validated/opened
	 *
	 *	@return     int     <0 if ko, >0 if ok
	 */
	function reOpen()
	{
		global $conf,$langs,$user;

		$error=0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'reception SET fk_statut=1, billed=0';
		$sql .= ' WHERE rowid = '.$this->id.' AND fk_statut > 0';

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->statut=1;
			$this->billed=0;

			// If stock increment is done on closing
			if (! $error && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_RECEPTION_CLOSE))
			{
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$numref = $this->ref;
				$langs->load("agenda");

				// Loop on each product line to add a stock movement
				// TODO possibilite de receptionner a partir d'une propale ou autre origine
				$sql = "SELECT ed.fk_product, cd.subprice,";
				$sql.= " ed.rowid, ed.qty, ed.fk_entrepot,";
				$sql.= " ed.eatby, ed.sellby, ed.batch";
				$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd,";
				$sql.= " ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as ed";
				$sql.= " WHERE ed.fk_reception = ".$this->id;
				$sql.= " AND cd.rowid = ed.fk_commandefourndet";

				dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
				$resql=$this->db->query($sql);
				if ($resql)
				{
					$cpt = $this->db->num_rows($resql);
					for ($i = 0; $i < $cpt; $i++)
					{
						$obj = $this->db->fetch_object($resql);

						$qty = $obj->qty;

						if ($qty <= 0) continue;

						dol_syslog(get_class($this)."::reopen reception movement index ".$i." ed.rowid=".$obj->rowid);

						//var_dump($this->lines[$i]);
						$mouvS = new MouvementStock($this->db);
						$mouvS->origin = &$this;

						if (empty($obj->batch))
						{
							// line without batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$result=$mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, -$qty, $obj->subprice, $langs->trans("ReceptionUnClassifyCloseddInDolibarr",$numref));
							if ($result < 0) {
							    $this->error = $mouvS->error;
							    $this->errors = $mouvS->errors;
								$error++; break;
							}
						}
						else
						{
							// line with batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$result=$mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, -$qty, $obj->subprice, $langs->trans("ReceptionUnClassifyCloseddInDolibarr",$numref),  $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch, $obj->fk_origin_stock);
							if ($result < 0) {
							    $this->error = $mouvS->error;
							    $this->errors = $mouvS->errors;
							    $error++; break;
							}
						}
					}
				}
				else
				{
					$this->error=$this->db->lasterror();
					$error++;
				}
			}

			if (! $error)
			{
    			// Call trigger
    			$result=$this->call_trigger('RECEPTION_REOPEN',$user);
    			if ($result < 0) {
    				$error++;
    			}
   			}

			if($this->origin == 'order_supplier'){
				$commande = new CommandeFournisseur($this->db);
				$commande->fetch($this->origin_id);
				$commande->setStatus($user,4);
			}
		} else {
			$error++;
			$this->errors[]=$this->db->lasterror();
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


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	 /**
     *	Set draft status
     *
     *	@param	User	$user			Object user that modify
     *	@return	int						<0 if KO, >0 if OK
     */
    function set_draft($user)
    {
		// phpcs:enable
        global $conf,$langs;

        $error=0;

        // Protection
        if ($this->statut <= self::STATUS_DRAFT)
        {
            return 0;
        }

        if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->reception->creer))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->reception->reception_advance->validate))))
        {
            $this->error='Permission denied';
            return -1;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."reception";
        $sql.= " SET fk_statut = ".self::STATUS_DRAFT;
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::set_draft", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            // If stock increment is done on closing
			if (! $error && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_RECEPTION))
			{
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

				$langs->load("agenda");

				// Loop on each product line to add a stock movement
				// TODO possibilite de receptionner a partir d'une propale ou autre origine
				$sql = "SELECT cd.fk_product, cd.subprice,";
				$sql.= " ed.rowid, ed.qty, ed.fk_entrepot,";
				$sql.= " ed.eatby, ed.sellby, ed.batch";
				$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd,";
				$sql.= " ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as ed";
				$sql.= " WHERE ed.fk_reception = ".$this->id;
				$sql.= " AND cd.rowid = ed.fk_commandefourndet";

				dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
				$resql=$this->db->query($sql);
				if ($resql)
				{
					$cpt = $this->db->num_rows($resql);
					for ($i = 0; $i < $cpt; $i++)
					{
						$obj = $this->db->fetch_object($resql);

						$qty = $obj->qty;


						if ($qty <= 0) continue;
						dol_syslog(get_class($this)."::reopen reception movement index ".$i." ed.rowid=".$obj->rowid." edb.rowid=".$obj->edbrowid);

						//var_dump($this->lines[$i]);
						$mouvS = new MouvementStock($this->db);
						$mouvS->origin = &$this;

						if (empty($obj->batch))
						{
							// line without batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$result=$mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, -$qty, $obj->subprice, $langs->trans("ReceptionBackToDraftInDolibarr",$this->ref));
							if ($result < 0) {
							    $this->error = $mouvS->error;
							    $this->errors = $mouvS->errors;
								$error++;
								break;
							}
						}
						else
						{
							// line with batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$result=$mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, -$qty, $obj->subprice, $langs->trans("ReceptionBackToDraftInDolibarr",$this->ref),  $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch);
							if ($result < 0) {
							    $this->error = $mouvS->error;
							    $this->errors = $mouvS->errors;
							    $error++; break;
							}
						}
					}
				}
				else
				{
					$this->error=$this->db->lasterror();
					$error++;
				}
			}

            if (!$error) {
            	// Call trigger
            	$result=$this->call_trigger('RECEPTION_UNVALIDATE',$user);
            	if ($result < 0) $error++;
            }
			if ($this->origin == 'order_supplier')
			{
				if (!empty($this->origin) && $this->origin_id > 0)
				{
					$this->fetch_origin();
					$origin = $this->origin;
					if ($this->$origin->statut == 4)  // If order source of reception is "partially received"
					{
						// Check if there is no more reception validated.
						$this->$origin->fetchObjectLinked();
						$setStatut = 1;
						if (!empty($this->$origin->linkedObjects['reception']))
						{
							foreach ($this->$origin->linkedObjects['reception'] as $rcption)
							{
								if ($rcption->statut > 0)
								{
									$setStatut = 0;
									break;
								}
							}
							//var_dump($this->$origin->receptions);exit;
							if ($setStatut)
							{
								$this->$origin->setStatut(3); // ordered
							}
						}
					}
				}
			}

			if (!$error) {
           		$this->statut=self::STATUS_DRAFT;
            	$this->db->commit();
            	return 1;
            }else {
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
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force the model to using ('' to not force)
	 *  @param		Translate	$outputlangs	object lang to use for translations
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs,$hidedetails=0, $hidedesc=0, $hideref=0)
	{
		global $conf,$langs;

		$langs->load("receptions");

		if (! dol_strlen($modele))
		{
			$modele = 'squille';

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (! empty($conf->global->RECEPTION_ADDON_PDF)) {
				$modele = $conf->global->RECEPTION_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/reception/doc/";

		$this->fetch_origin();

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
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
		$tables = array('reception');

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}
}