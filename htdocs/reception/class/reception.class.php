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
if (! empty($conf->productbatch->enabled)) require_once DOL_DOCUMENT_ROOT.'/reception/class/receptionbatch.class.php';


/**
 *	Class to manage receptions
 */
class Reception extends CommonObject
{
	public $element="reception";
	public $fk_element="fk_reception";
	public $table_element="reception";
	public $table_element_line="receptiondet";
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
	 * @deprecated
	 * @see date_reception
	 */
	var $date;

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
   * 	@param		int		$notrigger	1=Does not execute triggers, 0= execute triggers
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
		$sql.= ", fk_address";
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
		$sql.= ", ".($this->fk_delivery_address>0?$this->fk_delivery_address:"null");
		$sql.= ", ".($this->reception_method_id>0?$this->reception_method_id:"null");
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
					if (! isset($this->lines[$i]->detail_batch))
					{	// no batch management
						if (! $this->create_line($this->lines[$i]->entrepot_id, $this->lines[$i]->origin_line_id, $this->lines[$i]->qty, $this->lines[$i]->array_options) > 0)
						{
							$error++;
						}
					}
					else
					{	// with batch management
						if (! $this->create_line_batch($this->lines[$i],$this->lines[$i]->array_options) > 0)
						{
							$error++;
						}
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
	 * Create a reception line
	 *
	 * @param 	int		$entrepot_id		Id of warehouse
	 * @param 	int		$origin_line_id		Id of source line
	 * @param 	int		$qty				Quantity
	 * @param	array	$array_options		extrafields array
	 * @return	int							<0 if KO, line_id if OK
	 */
	function create_line($entrepot_id, $origin_line_id, $qty,$array_options=0)
	{
		global $conf;
		$error = 0;
		$line_id = 0;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."receptiondet (";
		$sql.= "fk_reception";
		$sql.= ", fk_entrepot";
		$sql.= ", fk_origin_line";
		$sql.= ", qty";
		$sql.= ") VALUES (";
		$sql.= $this->id;
		$sql.= ", ".($entrepot_id?$entrepot_id:'null');
		$sql.= ", ".$origin_line_id;
		$sql.= ", ".$qty;
		$sql.= ")";

		dol_syslog(get_class($this)."::create_line", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
		    $line_id = $this->db->last_insert_id(MAIN_DB_PREFIX."receptiondet");
		}
		else
		{
			$error++;
		}

		if (! $error && empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($array_options) && count($array_options)>0) // For avoid conflicts if trigger used
		{
			$receptionline = new ReceptionLigne($this->db);
			$receptionline->array_options=$array_options;
			$receptionline->id= $this->db->last_insert_id(MAIN_DB_PREFIX.$receptionline->table_element);
			$result=$receptionline->insertExtraFields();
			if ($result < 0)
			{
				$this->error[]=$receptionline->error;
				$error++;
			}
		}

		if (! $error) return $line_id;
		else return -1;
	}


	/**
	 * Create the detail (eat-by date) of the reception line
	 *
	 * @param 	object		$line_ext		full line informations
	 * @param	array		$array_options		extrafields array
	 * @return	int							<0 if KO, >0 if OK
	 */
	function create_line_batch($line_ext,$array_options=0)
	{
		$error = 0;
		$stockLocationQty = array(); // associated array with batch qty in stock location

		$tab=$line_ext->detail_batch;
		// create stockLocation Qty array
		foreach ($tab as $detbatch)
		{
			if ($detbatch->entrepot_id)
			{
				$stockLocationQty[$detbatch->entrepot_id] += $detbatch->dluo_qty;
			}
		}
		// create reception lines
		foreach ($stockLocationQty as $stockLocation => $qty)
		{
			if (($line_id = $this->create_line($stockLocation,$line_ext->origin_line_id,$qty,$array_options)) < 0)
			{
				$error++;
			}
			else
			{
				// create reception batch lines for stockLocation
				foreach ($tab as $detbatch)
				{
					if ($detbatch->entrepot_id == $stockLocation){
						if (! ($detbatch->create($line_id) >0))		// Create an receptionlinebatch
						{
							$error++;
						}
					}
				}
			}
		}

		if (! $error) return 1;
		else return -1;
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
		$sql.= ", e.date_reception as date_reception, e.model_pdf, e.fk_address, e.date_delivery";
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
				$this->GetUrlTrackingStatus($obj->tracking_number);

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

		// Class of company linked to order
		$result=$soc->set_as_client();

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
		$sql.= " ref='".$numref."'";
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
			$sql.= " edb.rowid as edbrowid, edb.eatby, edb.sellby, edb.batch, edb.qty as edbqty, edb.fk_origin_stock";
			$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
			$sql.= " ".MAIN_DB_PREFIX."receptiondet as ed";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."receptiondet_batch as edb on edb.fk_receptiondet = ed.rowid";
			$sql.= " WHERE ed.fk_reception = ".$this->id;
			$sql.= " AND cd.rowid = ed.fk_origin_line";

			dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$cpt = $this->db->num_rows($resql);
				for ($i = 0; $i < $cpt; $i++)
				{
					$obj = $this->db->fetch_object($resql);
					if (empty($obj->edbrowid))
					{
						$qty = $obj->qty;
					}
					else
					{
						$qty = $obj->edbqty;
					}
					if ($qty <= 0) continue;
					dol_syslog(get_class($this)."::valid movement index ".$i." ed.rowid=".$obj->rowid." edb.rowid=".$obj->edbrowid);

					//var_dump($this->lines[$i]);
					$mouvS = new MouvementStock($this->db);
					$mouvS->origin = &$this;

					if (empty($obj->edbrowid))
					{
						// line without batch detail

						// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record.
						$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->subprice, $langs->trans("ReceptionValidatedInDolibarr",$numref));
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
						$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->subprice, $langs->trans("ReceptionValidatedInDolibarr",$numref), '', $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch, $obj->fk_origin_stock);
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
		$ret = $this->setStatut(Commande::STATUS_RECEPTIONONPROCESS, $this->origin_id, $this->origin);

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
				$dirsource = $conf->reception->dir_output.'/reception/'.$oldref;
				$dirdest = $conf->reception->dir_output.'/reception/'.$newref;
				if (file_exists($dirsource))
				{
					dol_syslog(get_class($this)."::valid rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest))
					{
					    dol_syslog("Rename ok");
                        // Rename docs starting with $oldref with $newref
                        $listoffiles=dol_dir_list($conf->reception->dir_output.'/reception/'.$newref, 'files', 1, '^'.preg_quote($oldref,'/'));
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
	 *	Create a delivery receipt from a reception
	 *
	 *	@param	User	$user       User
	 *  @return int  				<0 if KO, >=0 if OK
	 */
	function create_delivery($user)
	{
		global $conf;

		if ($conf->livraison_bon->enabled)
		{
			if ($this->statut == 1 || $this->statut == 2)
			{
				// Reception validee
				include_once DOL_DOCUMENT_ROOT.'/livraison/class/livraison.class.php';
				$delivery = new Livraison($this->db);
				$result=$delivery->create_from_reception($user, $this->id);
				if ($result > 0)
				{
					return $result;
				}
				else
				{
					$this->error=$delivery->error;
					return $result;
				}
			}
			else return 0;
		}
		else return 0;
	}

	/**
	 * Add an reception line.
	 * If STOCK_WAREHOUSE_NOT_REQUIRED_FOR_RECEPTIONS is set, you can add a reception line, with no stock source defined
	 * If STOCK_MUST_BE_ENOUGH_FOR_RECEPTION is not set, you can add a reception line, even if not enough into stock
	 *
	 * @param 	int		$entrepot_id		Id of warehouse
	 * @param 	int		$id					Id of source line (order line)
	 * @param 	int		$qty				Quantity
	 * @param	array	$array_options		extrafields array
	 * @return	int							<0 if KO, >0 if OK
	 */
	function addline($entrepot_id, $id, $qty,$array_options=0)
	{
		global $conf, $langs;

		$num = count($this->lines);
		$line = new ReceptionLigne($this->db);

		$line->entrepot_id = $entrepot_id;
		$line->origin_line_id = $id;
		$line->qty = $qty;

		$orderline = new OrderLine($this->db);
		$orderline->fetch($id);

		if (! empty($conf->stock->enabled) && ! empty($orderline->fk_product))
		{
			$fk_product = $orderline->fk_product;

			if (! ($entrepot_id > 0) && empty($conf->global->STOCK_WAREHOUSE_NOT_REQUIRED_FOR_RECEPTIONS))
			{
			    $langs->load("errors");
				$this->error=$langs->trans("ErrorWarehouseRequiredIntoReceptionLine");
				return -1;
			}

			if ($conf->global->STOCK_MUST_BE_ENOUGH_FOR_RECEPTION)
			{
			    // Check must be done for stock of product into warehouse if $entrepot_id defined
				$product=new Product($this->db);
				$result=$product->fetch($fk_product);

				if ($entrepot_id > 0) {
					$product->load_stock('warehouseopen');
					$product_stock = $product->stock_warehouse[$entrepot_id]->real;
				}
				else
					$product_stock = $product->stock_reel;

				$product_type=$product->type;
				if ($product_type == 0 && $product_stock < $qty)
				{
                    $langs->load("errors");
				    $this->error=$langs->trans('ErrorStockIsNotEnoughToAddProductOnReception', $product->ref);
					$this->db->rollback();
					return -3;
				}
			}
		}

		// If product need a batch number, we should not have called this function but addline_batch instead.
		if (! empty($conf->productbatch->enabled) && ! empty($orderline->fk_product) && ! empty($orderline->product_tobatch))
		{
		    $this->error='ADDLINE_WAS_CALLED_INSTEAD_OF_ADDLINEBATCH';
		    return -4;
		}

		// extrafields
		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($array_options) && count($array_options)>0) // For avoid conflicts if trigger used
			$line->array_options = $array_options;

		$this->lines[$num] = $line;
	}

    /**
	 * Add a reception line with batch record
	 *
	 * @param 	array		$dbatch		Array of value (key 'detail' -> Array, key 'qty' total quantity for line, key ix_l : original line index)
	 * @param	array		$array_options		extrafields array
	 * @return	int						<0 if KO, >0 if OK
	 */
	function addline_batch($dbatch,$array_options=0)
	{
		global $conf,$langs;

		$num = count($this->lines);
		if ($dbatch['qty']>0)
		{
			$line = new ReceptionLigne($this->db);
			$tab=array();
			foreach ($dbatch['detail'] as $key=>$value)
			{
				if ($value['q']>0)
				{
					// $value['q']=qty to move
					// $value['id_batch']=id into llx_product_batch of record to move
					//var_dump($value);

				    $linebatch = new ReceptionLineBatch($this->db);
					$ret=$linebatch->fetchFromStock($value['id_batch']);	// load serial, sellby, eatby
					if ($ret<0)
					{
						$this->error=$linebatch->error;
						return -1;
					}
					$linebatch->dluo_qty=$value['q'];
					$tab[]=$linebatch;

					if ($conf->global->STOCK_MUST_BE_ENOUGH_FOR_RECEPTION)
					{
						require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
						$prod_batch = new Productbatch($this->db);
						$prod_batch->fetch($value['id_batch']);

						if ($prod_batch->qty < $linebatch->dluo_qty)
						{
                            $langs->load("errors");
        				    $this->errors[]=$langs->trans('ErrorStockIsNotEnoughToAddProductOnReception', $prod_batch->fk_product);
							dol_syslog(get_class($this)."::addline_batch error=Product ".$prod_batch->batch.": ".$this->errorsToString(), LOG_ERR);
							$this->db->rollback();
							return -1;
						}
					}

					//var_dump($linebatch);
				}
			}
			$line->entrepot_id = $linebatch->entrepot_id;
			$line->origin_line_id = $dbatch['ix_l'];
			$line->qty = $dbatch['qty'];
			$line->detail_batch=$tab;

			// extrafields
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($array_options) && count($array_options)>0) // For avoid conflicts if trigger used
				$line->array_options = $array_options;

			//var_dump($line);
			$this->lines[$num] = $line;
			return 1;
		}
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
		if (isset($this->reception_method_id)) $this->reception_method_id=trim($this->reception_method_id);
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
		$sql.= " fk_address=".(isset($this->fk_delivery_address)?$this->fk_delivery_address:"null").",";
		$sql.= " fk_shipping_method=".((isset($this->reception_method_id) && $this->reception_method_id > 0)?$this->reception_method_id:"null").",";
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
	 *  Warning, do not delete a reception if a delivery is linked to (with table llx_element_element)
	 *
	 * 	@return	int		>0 if OK, 0 if deletion done but failed to delete files, <0 if KO
	 */
	function delete()
	{
		global $conf, $langs, $user;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		if ($conf->productbatch->enabled)
		{
		require_once DOL_DOCUMENT_ROOT.'/reception/class/receptionbatch.class.php';
		}
		$error=0;
		$this->error='';

		// Add a protection to refuse deleting if reception has at least one delivery
		$this->fetchObjectLinked($this->id, 'reception', 0, 'delivery');	// Get deliveries linked to this reception
		if (count($this->linkedObjectsIds) > 0)
		{
			$this->error='ErrorThereIsSomeDeliveries';
			return -1;
		}

		$this->db->begin();
		// Stock control
		if ($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_RECEPTION && $this->statut > 0)
		{
			require_once(DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php");

			$langs->load("agenda");

			// Loop on each product line to add a stock movement
			$sql = "SELECT cd.fk_product, cd.subprice, ed.qty, ed.fk_entrepot, ed.rowid as receptiondet_id";
			$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
			$sql.= " ".MAIN_DB_PREFIX."receptiondet as ed";
			$sql.= " WHERE ed.fk_reception = ".$this->id;
			$sql.= " AND cd.rowid = ed.fk_origin_line";

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
					// get lot/serial
					$lotArray = null;
					if ($conf->productbatch->enabled)
					{
						$lotArray = ReceptionLineBatch::fetchAll($this->db,$obj->receptiondet_id);
						if (! is_array($lotArray))
						{
							$error++;$this->errors[]="Error ".$this->db->lasterror();
						}
					}
					if (empty($lotArray)) {
						// no lot/serial
						// We increment stock of product (and sub-products)
						// We use warehouse selected for each line
						$result=$mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $obj->qty, 0, $langs->trans("ReceptionDeletedInDolibarr", $this->ref));  // Price is set to 0, because we don't want to see WAP changed
						if ($result < 0)
						{
							$error++;$this->errors=$this->errors + $mouvS->errors;
							break;
						}
					}
					else
					{
						// We increment stock of batches
						// We use warehouse selected for each line
						foreach($lotArray as $lot)
						{
							$result=$mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $lot->dluo_qty, 0, $langs->trans("ReceptionDeletedInDolibarr", $this->ref), $lot->eatby, $lot->sellby, $lot->batch);  // Price is set to 0, because we don't want to see WAP changed
							if ($result < 0)
							{
								$error++;$this->errors=$this->errors + $mouvS->errors;
								break;
							}
						}
						if ($error) break; // break for loop incase of error
					}
				}
			}
			else
			{
				$error++;$this->errors[]="Error ".$this->db->lasterror();
			}
		}

		// delete batch reception line
		if (! $error && $conf->productbatch->enabled)
		{
			if (ReceptionLineBatch::deletefromexp($this->db,$this->id) < 0)
			{
				$error++;$this->errors[]="Error ".$this->db->lasterror();
			}
		}

		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."receptiondet";
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
						    if ($this->$origin->statut == Commande::STATUS_RECEPTIONONPROCESS)     // If order source of reception is "reception in progress"
						    {
                                // Check if there is no more reception. If not, we can move back status of order to "validated" instead of "reception in progress"
						        $this->$origin->loadReceptions();
						        //var_dump($this->$origin->receptions);exit;
						        if (count($this->$origin->receptions) <= 0)
						        {
                                    $this->$origin->setStatut(Commande::STATUS_VALIDATED);
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
								$dir = $conf->reception->dir_output . '/reception/' . $ref ;
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

	/**
	 *	Load lines
	 *
	 *	@return	int		>0 if OK, Otherwise if KO
	 */
	function fetch_lines()
	{
		global $conf, $mysoc;
		// TODO: recuperer les champs du document associe a part

		$sql = "SELECT cd.rowid, cd.fk_product, cd.label as custom_label, cd.description, cd.qty as qty_asked, cd.product_type";
		$sql.= ", cd.total_ht, cd.total_localtax1, cd.total_localtax2, cd.total_ttc, cd.total_tva";
		$sql.= ", cd.vat_src_code, cd.tva_tx, cd.localtax1_tx, cd.localtax2_tx, cd.localtax1_type, cd.localtax2_type, cd.price, cd.subprice, cd.remise_percent,cd.buy_price_ht as pa_ht";
		$sql.= ", cd.fk_multicurrency, cd.multicurrency_code, cd.multicurrency_subprice, cd.multicurrency_total_ht, cd.multicurrency_total_tva, cd.multicurrency_total_ttc";
		$sql.= ", ed.rowid as line_id, ed.qty as qty_shipped, ed.fk_origin_line, ed.fk_entrepot";
		$sql.= ", p.ref as product_ref, p.label as product_label, p.fk_product_type";
		$sql.= ", p.weight, p.weight_units, p.length, p.length_units, p.surface, p.surface_units, p.volume, p.volume_units, p.tobatch as product_tobatch";
		$sql.= " FROM (".MAIN_DB_PREFIX."receptiondet as ed,";
		$sql.= " ".MAIN_DB_PREFIX."commandedet as cd)";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = cd.fk_product";
		$sql.= " WHERE ed.fk_reception = ".$this->id;
		$sql.= " AND ed.fk_origin_line = cd.rowid";
		$sql.= " ORDER BY cd.rang, ed.fk_origin_line";

		dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

			$num = $this->db->num_rows($resql);
			$i = 0;
			$lineindex = 0;
			$originline = 0;

			$this->total_ht = 0;
			$this->total_tva = 0;
			$this->total_ttc = 0;
			$this->total_localtax1 = 0;
			$this->total_localtax2 = 0;

			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

			    if ($originline == $obj->fk_origin_line) {
			        $line->entrepot_id       = 0; // entrepod_id in details_entrepot
				    $line->qty_shipped    	+= $obj->qty_shipped;
				} else {
				    $line = new ReceptionLigne($this->db);
				    $line->entrepot_id    	= $obj->fk_entrepot;
				    $line->qty_shipped    	= $obj->qty_shipped;
				}

				$detail_entrepot              = new stdClass;
				$detail_entrepot->entrepot_id = $obj->fk_entrepot;
				$detail_entrepot->qty_shipped = $obj->qty_shipped;
				$line->details_entrepot[]     = $detail_entrepot;

                $line->line_id          = $obj->line_id;
                $line->rowid            = $obj->line_id;    // TODO deprecated
                $line->id               = $obj->line_id;

                $line->fk_origin     	= 'orderline';
                $line->fk_origin_line 	= $obj->fk_origin_line;
                $line->origin_line_id 	= $obj->fk_origin_line;	    // TODO deprecated

				$line->fk_reception    = $this->id;                // id of parent

				$line->product_type     = $obj->product_type;
				$line->fk_product     	= $obj->fk_product;
				$line->fk_product_type	= $obj->fk_product_type;
				$line->ref				= $obj->product_ref;		// TODO deprecated
                $line->product_ref		= $obj->product_ref;
                $line->product_label	= $obj->product_label;
				$line->libelle        	= $obj->product_label;		// TODO deprecated
				$line->product_tobatch  = $obj->product_tobatch;
				$line->label			= $obj->custom_label;
				$line->description    	= $obj->description;
				$line->qty_asked      	= $obj->qty_asked;
				$line->weight         	= $obj->weight;
				$line->weight_units   	= $obj->weight_units;
				$line->length         	= $obj->length;
				$line->length_units   	= $obj->length_units;
				$line->surface        	= $obj->surface;
				$line->surface_units   	= $obj->surface_units;
				$line->volume         	= $obj->volume;
				$line->volume_units   	= $obj->volume_units;

				$line->pa_ht 			= $obj->pa_ht;

				// Local taxes
				$localtax_array=array(0=>$obj->localtax1_type, 1=>$obj->localtax1_tx, 2=>$obj->localtax2_type, 3=>$obj->localtax2_tx);
				$localtax1_tx = get_localtax($obj->tva_tx, 1, $this->thirdparty);
				$localtax2_tx = get_localtax($obj->tva_tx, 2, $this->thirdparty);

				// For invoicing
				$tabprice = calcul_price_total($obj->qty_shipped, $obj->subprice, $obj->remise_percent, $obj->tva_tx, $localtax1_tx, $localtax2_tx, 0, 'HT', $obj->info_bits, $obj->fk_product_type, $mysoc, $localtax_array);	// We force type to 0
				$line->desc	         	= $obj->description;		// We need ->desc because some code into CommonObject use desc (property defined for other elements)
				$line->qty 				= $line->qty_shipped;
				$line->total_ht			= $tabprice[0];
				$line->total_localtax1 	= $tabprice[9];
				$line->total_localtax2 	= $tabprice[10];
				$line->total_ttc	 	= $tabprice[2];
				$line->total_tva	 	= $tabprice[1];
				$line->vat_src_code	 	= $obj->vat_src_code;
				$line->tva_tx 		 	= $obj->tva_tx;
				$line->localtax1_tx 	= $obj->localtax1_tx;
				$line->localtax2_tx 	= $obj->localtax2_tx;
				$line->price			= $obj->price;
				$line->subprice			= $obj->subprice;
				$line->remise_percent	= $obj->remise_percent;

				$this->total_ht+= $tabprice[0];
				$this->total_tva+= $tabprice[1];
				$this->total_ttc+= $tabprice[2];
				$this->total_localtax1+= $tabprice[9];
				$this->total_localtax2+= $tabprice[10];

				// Multicurrency
				$this->fk_multicurrency 		= $obj->fk_multicurrency;
				$this->multicurrency_code 		= $obj->multicurrency_code;
				$this->multicurrency_subprice 	= $obj->multicurrency_subprice;
				$this->multicurrency_total_ht 	= $obj->multicurrency_total_ht;
				$this->multicurrency_total_tva 	= $obj->multicurrency_total_tva;
				$this->multicurrency_total_ttc 	= $obj->multicurrency_total_ttc;

				if ($originline != $obj->fk_origin_line)
				{
					$line->detail_batch = array();
				}
				// Eat-by date
				if (! empty($conf->productbatch->enabled) && $obj->line_id > 0)
				{
                    require_once DOL_DOCUMENT_ROOT.'/reception/class/receptionbatch.class.php';

                    $newdetailbatch = ReceptionLineBatch::fetchAll($this->db,$obj->line_id);
                    if (is_array($newdetailbatch))
                    {
	                    if ($originline != $obj->fk_origin_line)
	                    {
	                        $line->detail_batch = $newdetailbatch;
	                    }
	                    else
						{
	                        $line->detail_batch = array_merge($line->detail_batch, $newdetailbatch);
	                    }
                    }
				}

				if ($originline != $obj->fk_origin_line)
				{
				    $this->lines[$lineindex] = $line;
				    $lineindex++;
				}
				else
				{
				    $line->total_ht			+= $tabprice[0];
				    $line->total_localtax1 	+= $tabprice[9];
				    $line->total_localtax2 	+= $tabprice[10];
				    $line->total_ttc	 	+= $tabprice[2];
				    $line->total_tva	 	+= $tabprice[1];
				}

				$i++;
				$originline = $obj->fk_origin_line;
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -3;
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
        $label .= '<br><b>'.$langs->trans('RefCustomer').':</b> '.($this->ref_supplier ? $this->ref_supplier : $this->ref_client);

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

		$picto='reception';

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

	/**
	 * Return label of a status
	 *
	 * @param      int		$statut		Id statut
	 * @param      int		$mode       0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto
	 * @return     string				Label of status
	 */
	function LibStatut($statut,$mode)
	{
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
			$line=new ReceptionLigne($this->db);
			$line->desc=$langs->trans("Description")." ".$xnbp;
			$line->libelle=$langs->trans("Description")." ".$xnbp;
			$line->qty=10;
			$line->qty_asked=5;
			$line->qty_shipped=4;
			$line->fk_product=$this->commande->lines[$xnbp]->fk_product;

			$this->lines[]=$line;
			$xnbp++;
		}

	}

	/**
	 *	Set the planned delivery date
	 *
	 *	@param      User			$user        		Objet utilisateur qui modifie
	 *	@param      timestamp		$date_livraison     Date de livraison
	 *	@return     int         						<0 if KO, >0 if OK
	 */
	function set_date_livraison($user, $date_livraison)
	{
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

	/**
	 *	Fetch deliveries method and return an array. Load array this->meths(rowid=>label).
	 *
	 * 	@return	void
	 */
	function fetch_delivery_methods()
	{
		global $langs;
		$this->meths = array();

		$sql = "SELECT em.rowid, em.code, em.libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_reception_mode as em";
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

    /**
     *  Fetch all deliveries method and return an array. Load array this->listmeths.
     *
     *  @param  id      $id     only this carrier, all if none
     *  @return void
     */
    function list_delivery_methods($id='')
    {
        global $langs;

        $this->listmeths = array();
        $i=0;

        $sql = "SELECT em.rowid, em.code, em.libelle, em.description, em.tracking, em.active";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_reception_mode as em";
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

    /**
     *  Update/create delivery method.
     *
     *  @param	string      $id     id method to activate
     *
     *  @return void
     */
    function update_delivery_method($id='')
    {
        if ($id=='')
        {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_reception_mode (code, libelle, description, tracking)";
            $sql.=" VALUES ('".$this->update['code']."','".$this->update['libelle']."','".$this->update['description']."','".$this->update['tracking']."')";
            $resql = $this->db->query($sql);
        }
        else
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."c_reception_mode SET";
            $sql.= " code='".$this->db->escape($this->update['code'])."'";
            $sql.= ",libelle='".$this->db->escape($this->update['libelle'])."'";
            $sql.= ",description='".$this->db->escape($this->update['description'])."'";
            $sql.= ",tracking='".$this->db->escape($this->update['tracking'])."'";
            $sql.= " WHERE rowid=".$id;
            $resql = $this->db->query($sql);
        }
        if ($resql < 0) dol_print_error($this->db,'');
    }

    /**
     *  Activate delivery method.
     *
     *  @param      id      $id     id method to activate
     *
     *  @return void
     */
    function activ_delivery_method($id)
    {
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'c_reception_mode SET active=1';
        $sql.= ' WHERE rowid='.$id;

        $resql = $this->db->query($sql);

    }

    /**
     *  DesActivate delivery method.
     *
     *  @param      id      $id     id method to desactivate
     *
     *  @return void
     */
    function disable_delivery_method($id)
    {
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'c_reception_mode SET active=0';
        $sql.= ' WHERE rowid='.$id;

        $resql = $this->db->query($sql);

    }


	/**
	 * Forge an set tracking url
	 *
	 * @param	string	$value		Value
	 * @return	void
	 */
	function GetUrlTrackingStatus($value='')
	{
		if (! empty($this->reception_method_id))
		{
			$sql = "SELECT em.code, em.tracking";
			$sql.= " FROM ".MAIN_DB_PREFIX."c_reception_mode as em";
			$sql.= " WHERE em.rowid = ".$this->reception_method_id;

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
			// Set order billed if 100% of order is shipped (qty in reception lines match qty in order lines)
			if ($this->origin == 'commande' && $this->origin_id > 0)
			{
				$order = new Commande($this->db);
				$order->fetch($this->origin_id);

				$order->loadReceptions(self::STATUS_CLOSED);		// Fill $order->receptions = array(orderlineid => qty)

				$receptions_match_order = 1;
				foreach($order->lines as $line)
				{
					$lineid = $line->id;
					$qty = $line->qty;
					if (($line->product_type == 0 || ! empty($conf->global->STOCK_SUPPORTS_SERVICES)) && $order->receptions[$lineid] != $qty)
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
					$order->cloture($user);
				}
			}

			$this->statut=self::STATUS_CLOSED;


			// If stock increment is done on closing
			if (! $error && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_RECEPTION_CLOSE))
			{
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

				$langs->load("agenda");

				// Loop on each product line to add a stock movement
				// TODO possibilite d'expedier a partir d'une propale ou autre origine ?
				$sql = "SELECT cd.fk_product, cd.subprice,";
				$sql.= " ed.rowid, ed.qty, ed.fk_entrepot,";
				$sql.= " edb.rowid as edbrowid, edb.eatby, edb.sellby, edb.batch, edb.qty as edbqty, edb.fk_origin_stock";
				$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
				$sql.= " ".MAIN_DB_PREFIX."receptiondet as ed";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."receptiondet_batch as edb on edb.fk_receptiondet = ed.rowid";
				$sql.= " WHERE ed.fk_reception = ".$this->id;
				$sql.= " AND cd.rowid = ed.fk_origin_line";

				dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
				$resql=$this->db->query($sql);
				if ($resql)
				{
					$cpt = $this->db->num_rows($resql);
					for ($i = 0; $i < $cpt; $i++)
					{
						$obj = $this->db->fetch_object($resql);
						if (empty($obj->edbrowid))
						{
							$qty = $obj->qty;
						}
						else
						{
							$qty = $obj->edbqty;
						}
						if ($qty <= 0) continue;
						dol_syslog(get_class($this)."::valid movement index ".$i." ed.rowid=".$obj->rowid." edb.rowid=".$obj->edbrowid);

						$mouvS = new MouvementStock($this->db);
						$mouvS->origin = &$this;

						if (empty($obj->edbrowid))
						{
							// line without batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->subprice, $langs->trans("ReceptionClassifyClosedInDolibarr",$numref));
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
							$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->subprice, $langs->trans("ReceptionClassifyClosedInDolibarr",$numref), '', $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch, $obj->fk_origin_stock);
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

	/**
	 *	Classify the reception as invoiced (used when WORKFLOW_BILL_ON_RECEPTION is on)
	 *
	 *	@return     int     <0 if ko, >0 if ok
	 */
	function set_billed()
	{
	    global $user;
		$error=0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'reception SET fk_statut=2, billed=1';    // TODO Update only billed
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

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'reception SET fk_statut=1';
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

				$langs->load("agenda");

				// Loop on each product line to add a stock movement
				// TODO possibilite d'expedier a partir d'une propale ou autre origine
				$sql = "SELECT cd.fk_product, cd.subprice,";
				$sql.= " ed.rowid, ed.qty, ed.fk_entrepot,";
				$sql.= " edb.rowid as edbrowid, edb.eatby, edb.sellby, edb.batch, edb.qty as edbqty, edb.fk_origin_stock";
				$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
				$sql.= " ".MAIN_DB_PREFIX."receptiondet as ed";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."receptiondet_batch as edb on edb.fk_receptiondet = ed.rowid";
				$sql.= " WHERE ed.fk_reception = ".$this->id;
				$sql.= " AND cd.rowid = ed.fk_origin_line";

				dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
				$resql=$this->db->query($sql);
				if ($resql)
				{
					$cpt = $this->db->num_rows($resql);
					for ($i = 0; $i < $cpt; $i++)
					{
						$obj = $this->db->fetch_object($resql);
						if (empty($obj->edbrowid))
						{
							$qty = $obj->qty;
						}
						else
						{
							$qty = $obj->edbqty;
						}
						if ($qty <= 0) continue;
						dol_syslog(get_class($this)."::reopen reception movement index ".$i." ed.rowid=".$obj->rowid." edb.rowid=".$obj->edbrowid);

						//var_dump($this->lines[$i]);
						$mouvS = new MouvementStock($this->db);
						$mouvS->origin = &$this;

						if (empty($obj->edbrowid))
						{
							// line without batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, -$qty, $obj->subprice, $langs->trans("ReceptionUnClassifyCloseddInDolibarr",$numref));
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
							$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, -$qty, $obj->subprice, $langs->trans("ReceptionUnClassifyCloseddInDolibarr",$numref), '', $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch, $obj->fk_origin_stock);
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
	
	
	 /**
     *	Set draft status
     *
     *	@param	User	$user			Object user that modify
     *	@return	int						<0 if KO, >0 if OK
     */
    function set_draft($user)
    {
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
				// TODO possibilite d'expedier a partir d'une propale ou autre origine
				$sql = "SELECT cd.fk_product, cd.subprice,";
				$sql.= " ed.rowid, ed.qty, ed.fk_entrepot,";
				$sql.= " edb.rowid as edbrowid, edb.eatby, edb.sellby, edb.batch, edb.qty as edbqty, edb.fk_origin_stock";
				$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
				$sql.= " ".MAIN_DB_PREFIX."receptiondet as ed";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."receptiondet_batch as edb on edb.fk_receptiondet = ed.rowid";
				$sql.= " WHERE ed.fk_reception = ".$this->id;
				$sql.= " AND cd.rowid = ed.fk_origin_line";

				dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
				$resql=$this->db->query($sql);
				if ($resql)
				{
					$cpt = $this->db->num_rows($resql);
					for ($i = 0; $i < $cpt; $i++)
					{
						$obj = $this->db->fetch_object($resql);
						if (empty($obj->edbrowid))
						{
							$qty = $obj->qty;
						}
						else
						{
							$qty = $obj->edbqty;
						}
						if ($qty <= 0) continue;
						dol_syslog(get_class($this)."::reopen reception movement index ".$i." ed.rowid=".$obj->rowid." edb.rowid=".$obj->edbrowid);

						//var_dump($this->lines[$i]);
						$mouvS = new MouvementStock($this->db);
						$mouvS->origin = &$this;

						if (empty($obj->edbrowid))
						{
							// line without batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, -$qty, $obj->subprice, $langs->trans("ReceptionBackToDraftInDolibarr",$this->ref));
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
							$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, -$qty, $obj->subprice, $langs->trans("ReceptionBackToDraftInDolibarr",$this->ref), '', $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch, $obj->fk_origin_stock);
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
			if($this->origin == 'commande'){
				$commande = new Commande($this->db);
				$commande->fetch($this->origin_id);
				$commande->statut = Commande::STATUS_VALIDATED;
				$commande->update();
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

		if (! dol_strlen($modele)) {

			$modele = 'rouget';

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
		$tables = array(
			'reception'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}
}


/**
 * Classe de gestion des lignes de bons d'reception
 */
class ReceptionLigne extends CommonObjectLine
{
	public $element='receptiondet';
	public $table_element='receptiondet';

	public $fk_origin_line;

	/**
	 * Id of reception
	 * @var int
	 */
	public $fk_reception;

	var $db;

	// From llx_receptiondet
	var $qty;
	var $qty_shipped;
	var $fk_product;
	var $detail_batch;
	/**
	 * Id of warehouse
	 * @var int
	 */
	public $entrepot_id;


	// From llx_commandedet or llx_propaldet
	var $qty_asked;
	public $product_ref;
	public $product_label;
	public $product_desc;


	// Invoicing
	var $remise_percent;
	var $total_ht;			// Total net of tax
	var $total_ttc;			// Total with tax
	var $total_tva;			// Total VAT
	var $total_localtax1;   // Total Local tax 1
	var $total_localtax2;   // Total Local tax 2



	// Deprecated
	/**
	 * @deprecated
	 * @see fk_origin_line
	 */
	var $origin_line_id;
	/**
	 * @deprecated
	 * @see product_ref
	 */
	var $ref;
	/**
	 * @deprecated
	 * @see product_label
	 */
	var $libelle;

    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
	function __construct($db)
	{
		$this->db=$db;
	}

	/**
	 *  Load line reception
	 *
	 *  @param  int		$rowid          Id line order
	 *  @return	int						<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT ed.rowid, ed.fk_reception, ed.fk_entrepot, ed.fk_origin_line, ed.qty, ed.rang';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as ed';
		$sql.= ' WHERE ed.rowid = '.$rowid;
		$result = $this->db->query($sql);
		if ($result)
		{
			$objp = $this->db->fetch_object($result);
			$this->id				= $objp->rowid;
			$this->fk_reception	= $objp->fk_reception;
			$this->entrepot_id		= $objp->fk_entrepot;
			$this->fk_origin_line	= $objp->fk_origin_line;
			$this->qty				= $objp->qty;
			$this->rang				= $objp->rang;

			$this->db->free($result);

			return 1;
		}
		else
		{
			$this->errors[] = $this->db->lasterror();
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Insert line into database
	 *
	 *	@param      User	$user			User that modify
	 *	@param      int		$notrigger		1 = disable triggers
	 *	@return		int						<0 if KO, line id >0 if OK
	 */
	function insert($user=null, $notrigger=0)
	{
		global $langs, $conf;

		$error=0;

		// Check parameters
		if (empty($this->fk_reception) || empty($this->fk_origin_line) || ! is_numeric($this->qty))
		{
			$this->error = 'ErrorMandatoryParametersNotProvided';
			return -1;
		}
		// Clean parameters
		if (empty($this->entrepot_id)) $this->entrepot_id='null';

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."receptiondet (";
		$sql.= "fk_reception";
		$sql.= ", fk_entrepot";
		$sql.= ", fk_origin_line";
		$sql.= ", qty";
		$sql.= ") VALUES (";
		$sql.= $this->fk_reception;
		$sql.= ", ".$this->entrepot_id;
		$sql.= ", ".$this->fk_origin_line;
		$sql.= ", ".$this->qty;
		$sql.= ")";

		dol_syslog(get_class($this)."::insert", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."receptiondet");
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
				$result=$this->call_trigger('LINERECEPTION_INSERT',$user);
				if ($result < 0)
				{
					$error++;
				}
				// End call triggers
			}

			if (! $error) {
				$this->db->commit();
				return $this->id;
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
			$error++;
		}
	}

	/**
	 * 	Delete reception line.
	 *
	 *	@param		User	$user			User that modify
	 *	@param		int		$notrigger		0=launch triggers after, 1=disable triggers
	 * 	@return		int		>0 if OK, <0 if KO
	 */
	function delete($user = null, $notrigger = 0)
	{
		global $conf;

		$error=0;

		$this->db->begin();

		// delete batch reception line
		if ($conf->productbatch->enabled)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."receptiondet_batch";
			$sql.= " WHERE fk_receptiondet = ".$this->id;

			if (!$this->db->query($sql))
			{
				$this->errors[]=$this->db->lasterror()." - sql=$sql";
				$error++;
			}
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."receptiondet";
		$sql.= " WHERE rowid = ".$this->id;

		if (! $error && $this->db->query($sql))
		{
			// Remove extrafields
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
			{
				$result=$this->deleteExtraFields();
				if ($result < 0)
				{
					$this->errors[]=$this->error;
					$error++;
				}
			}
			if (! $error && ! $notrigger)
			{
				// Call trigger
				$result=$this->call_trigger('LINERECEPTION_DELETE',$user);
				if ($result < 0)
				{
					$this->errors[]=$this->error;
					$error++;
				}
				// End call triggers
			}
		}
		else
		{
			$this->errors[]=$this->db->lasterror()." - sql=$sql";
			$error++;
		}

		if (! $error) {
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
	 *  Update a line in database
	 *
	 *	@param		User	$user			User that modify
	 *	@param		int		$notrigger		1 = disable triggers
	 *  @return		int					< 0 if KO, > 0 if OK
	 */
	function update($user = null, $notrigger = 0)
	{
		global $conf;

		$error=0;

		dol_syslog(get_class($this)."::update id=$this->id, entrepot_id=$this->entrepot_id, product_id=$this->fk_product, qty=$this->qty");

		$this->db->begin();

		// Clean parameters
		if (empty($this->qty)) $this->qty=0;
		$qty=price2num($this->qty);
		$remainingQty = 0;
		$batch = null;
		$batch_id = null;
		$reception_batch_id = null;
		if (is_array($this->detail_batch)) 	// array of ReceptionLineBatch
		{
			if (count($this->detail_batch) > 1)
			{
				dol_syslog(get_class($this).'::update only possible for one batch', LOG_ERR);
				$this->errors[]='ErrorBadParameters';
				$error++;
			}
			else
			{
				$batch = $this->detail_batch[0]->batch;
				$batch_id = $this->detail_batch[0]->fk_origin_stock;
				$reception_batch_id = $this->detail_batch[0]->id;
				if ($this->entrepot_id != $this->detail_batch[0]->entrepot_id)
				{
					dol_syslog(get_class($this).'::update only possible for batch of same warehouse', LOG_ERR);
					$this->errors[]='ErrorBadParameters';
					$error++;
				}
				$qty = price2num($this->detail_batch[0]->dluo_qty);
			}
		}
		else if (! empty($this->detail_batch))
		{
			$batch = $this->detail_batch->batch;
			$batch_id = $this->detail_batch->fk_origin_stock;
			$reception_batch_id = $this->detail_batch->id;
			if ($this->entrepot_id != $this->detail_batch->entrepot_id)
			{
				dol_syslog(get_class($this).'::update only possible for batch of same warehouse', LOG_ERR);
				$this->errors[]='ErrorBadParameters';
				$error++;
			}
			$qty = price2num($this->detail_batch->dluo_qty);
		}

		// check parameters
		if (! isset($this->id) || ! isset($this->entrepot_id))
		{
			dol_syslog(get_class($this).'::update missing line id and/or warehouse id', LOG_ERR);
			$this->errors[]='ErrorMandatoryParametersNotProvided';
			$error++;
			return -1;
		}

		// update lot

		if (! empty($batch) && $conf->productbatch->enabled)
		{
			dol_syslog(get_class($this)."::update reception batch id=$reception_batch_id, batch_id=$batch_id, batch=$batch");

			if (empty($batch_id) || empty($this->fk_product)) {
				dol_syslog(get_class($this).'::update missing fk_origin_stock (batch_id) and/or fk_product', LOG_ERR);
				$this->errors[]='ErrorMandatoryParametersNotProvided';
				$error++;
			}

			// fetch remaining lot qty
			require_once DOL_DOCUMENT_ROOT.'/reception/class/receptionbatch.class.php';
			if (! $error && ($lotArray = ReceptionLineBatch::fetchAll($this->db, $this->id)) < 0)
			{
				$this->errors[]=$this->db->lasterror()." - ReceptionLineBatch::fetchAll";
				$error++;
			}
			else
			{
				// caculate new total line qty
				foreach ($lotArray as $lot)
				{
					if ($reception_batch_id != $lot->id)
					{
						$remainingQty += $lot->dluo_qty;
					}
				}
				$qty += $remainingQty;

				//fetch lot details

				// fetch from product_lot
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
				$lot = new Productlot($this->db);
				if ($lot->fetch(0,$this->fk_product,$batch) < 0)
				{
					$this->errors[] = $lot->errors;
					$error++;
				}
				if (! $error && ! empty($reception_batch_id))
				{
					// delete lot reception line
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."receptiondet_batch";
					$sql.= " WHERE fk_receptiondet = ".$this->id;
					$sql.= " AND rowid = ".$reception_batch_id;

					if (!$this->db->query($sql))
					{
						$this->errors[]=$this->db->lasterror()." - sql=$sql";
						$error++;
					}
				}
				if (! $error && $this->detail_batch->dluo_qty > 0)
				{
					// create lot reception line
					if (isset($lot->id))
					{
						$receptionLot = new ReceptionLineBatch($this->db);
						$receptionLot->batch = $lot->batch;
						$receptionLot->eatby = $lot->eatby;
						$receptionLot->sellby = $lot->sellby;
						$receptionLot->entrepot_id = $this->detail_batch->entrepot_id;
						$receptionLot->dluo_qty = $this->detail_batch->dluo_qty;
						$receptionLot->fk_origin_stock = $batch_id;
						if ($receptionLot->create($this->id) < 0)
						{
							$this->errors[]=$receptionLot->errors;
							$error++;
						}
					}
				}
			}
		}
		if (! $error)
		{
			// update line
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
			$sql.= " fk_entrepot = ".($this->entrepot_id > 0 ? $this->entrepot_id : 'null');
			$sql.= " , qty = ".$qty;
			$sql.= " WHERE rowid = ".$this->id;

			if (!$this->db->query($sql))
			{
				$this->errors[]=$this->db->lasterror()." - sql=$sql";
				$error++;
			}
			else
			{
				if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
				{
					$result=$this->insertExtraFields();
					if ($result < 0)
					{
						$this->errors[]=$this->error;
						$error++;
					}
				}
			}
		}
		if (! $error && ! $notrigger)
		{
			// Call trigger
			$result=$this->call_trigger('LINERECEPTION_UPDATE',$user);
			if ($result < 0)
			{
				$this->errors[]=$this->error;
				$error++;
			}
			// End call triggers
		}
		if (!$error) {
			$this->db->commit();
			return 1;
		}
		else
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
	}
}

