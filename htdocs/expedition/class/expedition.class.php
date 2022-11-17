<?php
/* Copyright (C) 2003-2008	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2007		Franky Van Liedekerke	<franky.van.liedekerke@telenet.be>
 * Copyright (C) 2006-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2020	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2014		Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2014-2017  Francis Appels          <francis.appels@yahoo.com>
 * Copyright (C) 2015       Claudio Aschieri        <c.aschieri@19.coop>
 * Copyright (C) 2016-2022	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2018       Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2018-2022  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020       Lenin Rivas         	<lenin@leninrivas.com>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/expedition/class/expedition.class.php
 *  \ingroup    expedition
 *  \brief      Fichier de la classe de gestion des expeditions
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/commonobjectline.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/commonincoterm.class.php';
if (!empty($conf->propal->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
}
if (!empty($conf->commande->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expeditionlinebatch.class.php';


/**
 *	Class to manage shipments
 */
class Expedition extends CommonObject
{
	use CommonIncoterm;

	/**
	 * @var string ID to identify managed object
	 */
	public $element = "shipping";

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = "fk_expedition";

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = "expedition";

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = "expeditiondet";

	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'dolly';

	public $socid;

	/**
	 * @var string Customer ref
	 * @deprecated
	 * @see $ref_customer
	 */
	public $ref_client;

	/**
	 * @var string Customer ref
	 */
	public $ref_customer;

	/**
	 * @var string internal ref
	 * @deprecated
	 */
	public $ref_int;

	public $brouillon;

	/**
	 * @var int warehouse id
	 */
	public $entrepot_id;

	/**
	 * @var string Tracking number
	 */
	public $tracking_number;

	/**
	 * @var string Tracking url
	 */
	public $tracking_url;
	public $billed;

	/**
	 * @var string name of pdf model
	 */
	public $model_pdf;

	public $trueWeight;
	public $weight_units;
	public $trueWidth;
	public $width_units;
	public $trueHeight;
	public $height_units;
	public $trueDepth;
	public $depth_units;
	// A denormalized value
	public $trueSize;

	/**
	 * @var integer|string Date delivery planed
	 */
	public $date_delivery;

	/**
	 * @deprecated
	 * @see $date_shipping
	 */
	public $date;

	/**
	 * @deprecated
	 * @see $date_shipping
	 */
	public $date_expedition;

	/**
	 * Effective delivery date
	 * @var integer|string
	 */
	public $date_shipping;

	/**
	 * @var integer|string date_creation
	 */
	public $date_creation;

	/**
	 * @var integer|string date_valid
	 */
	public $date_valid;

	public $meths;
	public $listmeths; // List of carriers

	public $lines = array();


	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;

	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;

	/**
	 * Closed status
	 */
	const STATUS_CLOSED = 2;

	/**
	 * Canceled status
	 */
	const STATUS_CANCELED = -1;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		// List of long language codes for status
		$this->statuts = array();
		$this->statuts[-1] = 'StatusSendingCanceled';
		$this->statuts[0]  = 'StatusSendingDraft';
		$this->statuts[1]  = 'StatusSendingValidated';
		$this->statuts[2]  = 'StatusSendingProcessed';

		// List of short language codes for status
		$this->statutshorts = array();
		$this->statutshorts[-1] = 'StatusSendingCanceledShort';
		$this->statutshorts[0]  = 'StatusSendingDraftShort';
		$this->statutshorts[1]  = 'StatusSendingValidatedShort';
		$this->statutshorts[2]  = 'StatusSendingProcessedShort';
	}

	/**
	 *	Return next expedition ref
	 *
	 *	@param	Societe		$soc	Thirdparty object
	 *	@return string				Free reference for expedition
	 */
	public function getNextNumRef($soc)
	{
		global $langs, $conf;
		$langs->load("sendings");

		if (!empty($conf->global->EXPEDITION_ADDON_NUMBER)) {
			$mybool = false;

			$file = $conf->global->EXPEDITION_ADDON_NUMBER.".php";
			$classname = $conf->global->EXPEDITION_ADDON_NUMBER;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/expedition/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir.$file;
			}

			if (!$mybool) {
				dol_print_error('', "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = "";
			$numref = $obj->getNextValue($soc, $this);

			if ($numref != "") {
				return $numref;
			} else {
				dol_print_error($this->db, get_class($this)."::getNextNumRef ".$obj->error);
				return "";
			}
		} else {
			print $langs->trans("Error")." ".$langs->trans("Error_EXPEDITION_ADDON_NUMBER_NotDefined");
			return "";
		}
	}

	/**
	 *  Create expedition en base
	 *
	 *  @param	User	$user       Objet du user qui cree
	 * 	@param		int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return int 				<0 si erreur, id expedition creee si ok
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $hookmanager;

		$now = dol_now();

		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
		$error = 0;

		// Clean parameters
		$this->brouillon = 1;
		$this->tracking_number = dol_sanitizeFileName($this->tracking_number);
		if (empty($this->fk_project)) {
			$this->fk_project = 0;
		}

		$this->user = $user;


		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."expedition (";
		$sql .= "ref";
		$sql .= ", entity";
		$sql .= ", ref_customer";
		$sql .= ", ref_int";
		$sql .= ", ref_ext";
		$sql .= ", date_creation";
		$sql .= ", fk_user_author";
		$sql .= ", date_expedition";
		$sql .= ", date_delivery";
		$sql .= ", fk_soc";
		$sql .= ", fk_projet";
		$sql .= ", fk_address";
		$sql .= ", fk_shipping_method";
		$sql .= ", tracking_number";
		$sql .= ", weight";
		$sql .= ", size";
		$sql .= ", width";
		$sql .= ", height";
		$sql .= ", weight_units";
		$sql .= ", size_units";
		$sql .= ", note_private";
		$sql .= ", note_public";
		$sql .= ", model_pdf";
		$sql .= ", fk_incoterms, location_incoterms";
		$sql .= ") VALUES (";
		$sql .= "'(PROV)'";
		$sql .= ", ".((int) $conf->entity);
		$sql .= ", ".($this->ref_customer ? "'".$this->db->escape($this->ref_customer)."'" : "null");
		$sql .= ", ".($this->ref_int ? "'".$this->db->escape($this->ref_int)."'" : "null");
		$sql .= ", ".($this->ref_ext ? "'".$this->db->escape($this->ref_ext)."'" : "null");
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", ".((int) $user->id);
		$sql .= ", ".($this->date_expedition > 0 ? "'".$this->db->idate($this->date_expedition)."'" : "null");
		$sql .= ", ".($this->date_delivery > 0 ? "'".$this->db->idate($this->date_delivery)."'" : "null");
		$sql .= ", ".($this->socid > 0 ? ((int) $this->socid) : "null");
		$sql .= ", ".($this->fk_project > 0 ? ((int) $this->fk_project) : "null");
		$sql .= ", ".($this->fk_delivery_address > 0 ? $this->fk_delivery_address : "null");
		$sql .= ", ".($this->shipping_method_id > 0 ? ((int) $this->shipping_method_id) : "null");
		$sql .= ", '".$this->db->escape($this->tracking_number)."'";
		$sql .= ", ".(is_numeric($this->weight) ? $this->weight : 'NULL');
		$sql .= ", ".(is_numeric($this->sizeS) ? $this->sizeS : 'NULL'); // TODO Should use this->trueDepth
		$sql .= ", ".(is_numeric($this->sizeW) ? $this->sizeW : 'NULL'); // TODO Should use this->trueWidth
		$sql .= ", ".(is_numeric($this->sizeH) ? $this->sizeH : 'NULL'); // TODO Should use this->trueHeight
		$sql .= ", ".($this->weight_units != '' ? (int) $this->weight_units : 'NULL');
		$sql .= ", ".($this->size_units != '' ? (int) $this->size_units : 'NULL');
		$sql .= ", ".(!empty($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null");
		$sql .= ", ".(!empty($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null");
		$sql .= ", ".(!empty($this->model_pdf) ? "'".$this->db->escape($this->model_pdf)."'" : "null");
		$sql .= ", ".(int) $this->fk_incoterms;
		$sql .= ", '".$this->db->escape($this->location_incoterms)."'";
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."expedition");

			$sql = "UPDATE ".MAIN_DB_PREFIX."expedition";
			$sql .= " SET ref = '(PROV".$this->id.")'";
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			if ($this->db->query($sql)) {
				// Insert of lines
				$num = count($this->lines);
				for ($i = 0; $i < $num; $i++) {
					if (empty($this->lines[$i]->product_type) || !empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
						if (!isset($this->lines[$i]->detail_batch)) {	// no batch management
							if ($this->create_line($this->lines[$i]->entrepot_id, $this->lines[$i]->origin_line_id, $this->lines[$i]->qty, $this->lines[$i]->rang, $this->lines[$i]->array_options) <= 0) {
								$error++;
							}
						} else {	// with batch management
							if ($this->create_line_batch($this->lines[$i], $this->lines[$i]->array_options) <= 0) {
								$error++;
							}
						}
					}
				}

				if (!$error && $this->id && $this->origin_id) {
					$ret = $this->add_object_linked();
					if (!$ret) {
						$error++;
					}
				}

				// Actions on extra fields
				if (!$error) {
					$result = $this->insertExtraFields();
					if ($result < 0) {
						$error++;
					}
				}

				if (!$error && !$notrigger) {
					// Call trigger
					$result = $this->call_trigger('SHIPPING_CREATE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers

					if (!$error) {
						$this->db->commit();
						return $this->id;
					} else {
						foreach ($this->errors as $errmsg) {
							dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
							$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
						}
						$this->db->rollback();
						return -1 * $error;
					}
				} else {
					$error++;
					$this->db->rollback();
					return -3;
				}
			} else {
				$error++;
				$this->error = $this->db->lasterror()." - sql=$sql";
				$this->db->rollback();
				return -2;
			}
		} else {
			$error++;
			$this->error = $this->db->error()." - sql=$sql";
			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Create a expedition line
	 *
	 * @param 	int		$entrepot_id		Id of warehouse
	 * @param 	int		$origin_line_id		Id of source line
	 * @param 	int		$qty				Quantity
	 * @param 	int		$rang				Rang
	 * @param	array	$array_options		extrafields array
	 * @return	int							<0 if KO, line_id if OK
	 */
	public function create_line($entrepot_id, $origin_line_id, $qty, $rang = 0, $array_options = null)
	{
		//phpcs:enable
		global $user;

		$expeditionline = new ExpeditionLigne($this->db);
		$expeditionline->fk_expedition = $this->id;
		$expeditionline->entrepot_id = $entrepot_id;
		$expeditionline->fk_origin_line = $origin_line_id;
		$expeditionline->qty = $qty;
		$expeditionline->rang = $rang;
		$expeditionline->array_options = $array_options;

		if (($lineId = $expeditionline->insert($user)) < 0) {
			$this->errors[] = $expeditionline->error;
		}
		return $lineId;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Create the detail of the expedition line. Create 1 record into expeditiondet for each warehouse and n record for each lot in this warehouse into expeditiondet_batch.
	 *
	 * @param 	object		$line_ext			Objet with full information of line. $line_ext->detail_batch must be an array of ExpeditionLineBatch
	 * @param	array		$array_options		extrafields array
	 * @return	int								<0 if KO, >0 if OK
	 */
	public function create_line_batch($line_ext, $array_options = 0)
	{
		// phpcs:enable
		$error = 0;
		$stockLocationQty = array(); // associated array with batch qty in stock location

		$tab = $line_ext->detail_batch;
		// create stockLocation Qty array
		foreach ($tab as $detbatch) {
			if ($detbatch->entrepot_id) {
				$stockLocationQty[$detbatch->entrepot_id] += $detbatch->qty;
			}
		}
		// create shipment lines
		foreach ($stockLocationQty as $stockLocation => $qty) {
			$line_id = $this->create_line($stockLocation, $line_ext->origin_line_id, $qty, $line_ext->rang, $array_options);
			if ($line_id < 0) {
				$error++;
			} else {
				// create shipment batch lines for stockLocation
				foreach ($tab as $detbatch) {
					if ($detbatch->entrepot_id == $stockLocation) {
						if (!($detbatch->create($line_id) > 0)) {		// Create an ExpeditionLineBatch
							$error++;
						}
					}
				}
			}
		}

		if (!$error) {
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 *	Get object and lines from database
	 *
	 *	@param	int		$id       	Id of object to load
	 * 	@param	string	$ref		Ref of object
	 * 	@param	string	$ref_ext	External reference of object
	 * 	@param	string	$notused	Internal reference of other object
	 *	@return int			        >0 if OK, 0 if not found, <0 if KO
	 */
	public function fetch($id, $ref = '', $ref_ext = '', $notused = '')
	{
		global $conf;

		// Check parameters
		if (empty($id) && empty($ref) && empty($ref_ext)) {
			return -1;
		}

		$sql = "SELECT e.rowid, e.entity, e.ref, e.fk_soc as socid, e.date_creation, e.ref_customer, e.ref_ext, e.ref_int, e.fk_user_author, e.fk_statut, e.fk_projet as fk_project, e.billed";
		$sql .= ", e.date_valid";
		$sql .= ", e.weight, e.weight_units, e.size, e.size_units, e.width, e.height";
		$sql .= ", e.date_expedition as date_expedition, e.model_pdf, e.fk_address, e.date_delivery";
		$sql .= ", e.fk_shipping_method, e.tracking_number";
		$sql .= ", e.note_private, e.note_public";
		$sql .= ', e.fk_incoterms, e.location_incoterms';
		$sql .= ', i.libelle as label_incoterms';
		$sql .= ', s.libelle as shipping_method';
		$sql .= ", el.fk_source as origin_id, el.sourcetype as origin";
		$sql .= " FROM ".MAIN_DB_PREFIX."expedition as e";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_target = e.rowid AND el.targettype = '".$this->db->escape($this->element)."'";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON e.fk_incoterms = i.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_shipment_mode as s ON e.fk_shipping_method = s.rowid';
		$sql .= " WHERE e.entity IN (".getEntity('expedition').")";
		if ($id) {
			$sql .= " AND e.rowid = ".((int) $id);
		}
		if ($ref) {
			$sql .= " AND e.ref='".$this->db->escape($ref)."'";
		}
		if ($ref_ext) {
			$sql .= " AND e.ref_ext='".$this->db->escape($ref_ext)."'";
		}
		if ($notused) {
			$sql .= " AND e.ref_int='".$this->db->escape($notused)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id                   = $obj->rowid;
				$this->entity               = $obj->entity;
				$this->ref                  = $obj->ref;
				$this->socid                = $obj->socid;
				$this->ref_customer = $obj->ref_customer;
				$this->ref_ext		    = $obj->ref_ext;
				$this->ref_int		    = $obj->ref_int;
				$this->statut               = $obj->fk_statut;
				$this->user_author_id       = $obj->fk_user_author;
				$this->date_creation        = $this->db->jdate($obj->date_creation);
				$this->date_valid = $this->db->jdate($obj->date_valid);
				$this->date                 = $this->db->jdate($obj->date_expedition); // TODO deprecated
				$this->date_expedition      = $this->db->jdate($obj->date_expedition); // TODO deprecated
				$this->date_shipping        = $this->db->jdate($obj->date_expedition); // Date real
				$this->date_delivery        = $this->db->jdate($obj->date_delivery); // Date planed
				$this->fk_delivery_address  = $obj->fk_address;
				$this->model_pdf            = $obj->model_pdf;
				$this->modelpdf             = $obj->model_pdf; // deprecated
				$this->shipping_method_id   = $obj->fk_shipping_method;
				$this->shipping_method = $obj->shipping_method;
				$this->tracking_number      = $obj->tracking_number;
				$this->origin               = ($obj->origin ? $obj->origin : 'commande'); // For compatibility
				$this->origin_id            = $obj->origin_id;
				$this->billed               = $obj->billed;
				$this->fk_project = $obj->fk_project;

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
				$this->trueSize             = $obj->size."x".$obj->width."x".$obj->height;
				$this->size_units           = $obj->size_units;

				//Incoterms
				$this->fk_incoterms         = $obj->fk_incoterms;
				$this->location_incoterms   = $obj->location_incoterms;
				$this->label_incoterms      = $obj->label_incoterms;

				$this->db->free($result);

				if ($this->statut == self::STATUS_DRAFT) {
					$this->brouillon = 1;
				}

				// Tracking url
				$this->getUrlTrackingStatus($obj->tracking_number);

				// Thirdparty
				$result = $this->fetch_thirdparty(); // TODO Remove this

				// Retrieve extrafields
				$this->fetch_optionals();

				// Fix Get multicurrency param for transmited
				if (!empty($conf->multicurrency->enabled)) {
					if (!empty($this->multicurrency_code)) {
						$this->multicurrency_code = $this->thirdparty->multicurrency_code;
					}
					if (!empty($conf->global->MULTICURRENCY_USE_ORIGIN_TX) && !empty($this->thirdparty->multicurrency_tx)) {
						$this->multicurrency_tx = $this->thirdparty->multicurrency_tx;
					}
				}

				/*
				 * Lines
				 */
				$result = $this->fetch_lines();
				if ($result < 0) {
					return -3;
				}

				return 1;
			} else {
				dol_syslog(get_class($this).'::Fetch no expedition found', LOG_ERR);
				$this->error = 'Delivery with id '.$id.' not found';
				return 0;
			}
		} else {
			$this->error = $this->db->error();
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
	public function valid($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		dol_syslog(get_class($this)."::valid");

		// Protection
		if ($this->statut) {
			dol_syslog(get_class($this)."::valid not in draft status", LOG_WARNING);
			return 0;
		}

		if (!((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->expedition->creer))
		|| (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->expedition->shipping_advance->validate)))) {
			$this->error = 'Permission denied';
			dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
			return -1;
		}

		$this->db->begin();

		$error = 0;

		// Define new ref
		$soc = new Societe($this->db);
		$soc->fetch($this->socid);

		// Class of company linked to order
		$result = $soc->set_as_client();

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
			$numref = $this->getNextNumRef($soc);
		} else {
			$numref = "EXP".$this->id;
		}
		$this->newref = dol_sanitizeFileName($numref);

		$now = dol_now();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX."expedition SET";
		$sql .= " ref='".$this->db->escape($numref)."'";
		$sql .= ", fk_statut = 1";
		$sql .= ", date_valid = '".$this->db->idate($now)."'";
		$sql .= ", fk_user_valid = ".$user->id;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::valid update expedition", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$error++;
		}

		// If stock increment is done on sending (recommanded choice)
		if (!$error && !empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT)) {
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

			$langs->load("agenda");

			// Loop on each product line to add a stock movement
			$sql = "SELECT cd.fk_product, cd.subprice,";
			$sql .= " ed.rowid, ed.qty, ed.fk_entrepot,";
			$sql .= " edb.rowid as edbrowid, edb.eatby, edb.sellby, edb.batch, edb.qty as edbqty, edb.fk_origin_stock";
			$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
			$sql .= " ".MAIN_DB_PREFIX."expeditiondet as ed";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expeditiondet_batch as edb on edb.fk_expeditiondet = ed.rowid";
			$sql .= " WHERE ed.fk_expedition = ".((int) $this->id);
			$sql .= " AND cd.rowid = ed.fk_origin_line";

			dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$cpt = $this->db->num_rows($resql);
				for ($i = 0; $i < $cpt; $i++) {
					$obj = $this->db->fetch_object($resql);
					if (empty($obj->edbrowid)) {
						$qty = $obj->qty;
					} else {
						$qty = $obj->edbqty;
					}
					if ($qty <= 0) {
						continue;
					}
					dol_syslog(get_class($this)."::valid movement index ".$i." ed.rowid=".$obj->rowid." edb.rowid=".$obj->edbrowid);

					//var_dump($this->lines[$i]);
					$mouvS = new MouvementStock($this->db);
					//$mouvS->origin = dol_clone($this, 1);
					$mouvS->setOrigin($this->element, $this->id);

					if (empty($obj->edbrowid)) {
						// line without batch detail

						// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record.
						$result = $mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->subprice, $langs->trans("ShipmentValidatedInDolibarr", $numref), '', '', '', '', 0, '', 1);

						if ($result < 0) {
							$error++;
							$this->error = $mouvS->error;
							$this->errors = array_merge($this->errors, $mouvS->errors);
							break;
						}
					} else {
						// line with batch detail

						// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record.
						// Note: ->fk_origin_stock = id into table llx_product_batch (may be rename into llx_product_stock_batch in another version)
						$result = $mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->subprice, $langs->trans("ShipmentValidatedInDolibarr", $numref), '', $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch, $obj->fk_origin_stock, '', 1);
						if ($result < 0) {
							$error++;
							$this->error = $mouvS->error;
							$this->errors = array_merge($this->errors, $mouvS->errors);
							break;
						}
					}
				}

				// If some stock lines are now 0, we can remove entry into llx_product_stock, but only if there is no child lines into llx_product_batch (detail of batch, because we can imagine
				// having a lot1/qty=X and lot2/qty=-X, so 0 but we must not loose repartition of different lot.
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_stock WHERE reel = 0 AND rowid NOT IN (SELECT fk_product_stock FROM ".MAIN_DB_PREFIX."product_batch as pb)";
				$resql = $this->db->query($sql);
				// We do not test error, it can fails if there is child in batch details
			} else {
				$this->db->rollback();
				$this->error = $this->db->error();
				return -2;
			}
		}

		// Change status of order to "shipment in process"
		$ret = $this->setStatut(Commande::STATUS_SHIPMENTONPROCESS, $this->origin_id, $this->origin);
		if (!$ret) {
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('SHIPPING_VALIDATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'expedition/sending/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'expedition/sending/".$this->db->escape($this->ref)."' and entity = ".((int) $conf->entity);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($numref);
				$dirsource = $conf->expedition->dir_output.'/sending/'.$oldref;
				$dirdest = $conf->expedition->dir_output.'/sending/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::valid rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->expedition->dir_output.'/sending/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry) {
							$dirsource = $fileentry['name'];
							$dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $newref, $dirsource);
							$dirsource = $fileentry['path'].'/'.$dirsource;
							$dirdest = $fileentry['path'].'/'.$dirdest;
							@rename($dirsource, $dirdest);
						}
					}
				}
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->ref = $numref;
			$this->statut = self::STATUS_VALIDATED;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1 * $error;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Create a delivery receipt from a shipment
	 *
	 *	@param	User	$user       User
	 *  @return int  				<0 if KO, >=0 if OK
	 */
	public function create_delivery($user)
	{
		// phpcs:enable
		global $conf;

		if ($conf->delivery_note->enabled) {
			if ($this->statut == self::STATUS_VALIDATED || $this->statut == self::STATUS_CLOSED) {
				// Expedition validee
				include_once DOL_DOCUMENT_ROOT.'/delivery/class/delivery.class.php';
				$delivery = new Delivery($this->db);
				$result = $delivery->create_from_sending($user, $this->id);
				if ($result > 0) {
					return $result;
				} else {
					$this->error = $delivery->error;
					return $result;
				}
			} else {
				return 0;
			}
		} else {
			return 0;
		}
	}

	/**
	 * Add an expedition line.
	 * If STOCK_WAREHOUSE_NOT_REQUIRED_FOR_SHIPMENTS is set, you can add a shipment line, with no stock source defined
	 * If STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT is not set, you can add a shipment line, even if not enough into stock
	 * Note: For product that need a batch number, you must use addline_batch()
	 *
	 * @param 	int		$entrepot_id		Id of warehouse
	 * @param 	int		$id					Id of source line (order line)
	 * @param 	int		$qty				Quantity
	 * @param	array	$array_options		extrafields array
	 * @return	int							<0 if KO, >0 if OK
	 */
	public function addline($entrepot_id, $id, $qty, $array_options = 0)
	{
		global $conf, $langs;

		$num = count($this->lines);
		$line = new ExpeditionLigne($this->db);

		$line->entrepot_id = $entrepot_id;
		$line->origin_line_id = $id;
		$line->fk_origin_line = $id;
		$line->qty = $qty;

		$orderline = new OrderLine($this->db);
		$orderline->fetch($id);

		// Copy the rang of the order line to the expedition line
		$line->rang = $orderline->rang;
		$line->product_type = $orderline->product_type;

		if (!empty($conf->stock->enabled) && !empty($orderline->fk_product)) {
			$fk_product = $orderline->fk_product;

			if (!($entrepot_id > 0) && empty($conf->global->STOCK_WAREHOUSE_NOT_REQUIRED_FOR_SHIPMENTS)) {
				$langs->load("errors");
				$this->error = $langs->trans("ErrorWarehouseRequiredIntoShipmentLine");
				return -1;
			}

			if (!empty($conf->global->STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT)) {
				$product = new Product($this->db);
				$product->fetch($fk_product);

				// Check must be done for stock of product into warehouse if $entrepot_id defined
				if ($entrepot_id > 0) {
					$product->load_stock('warehouseopen');
					$product_stock = $product->stock_warehouse[$entrepot_id]->real;
				} else {
					$product_stock = $product->stock_reel;
				}

				$product_type = $product->type;
				if ($product_type == 0 || !empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
					$isavirtualproduct = ($product->hasFatherOrChild(1) > 0);
					// The product is qualified for a check of quantity (must be enough in stock to be added into shipment).
					if (!$isavirtualproduct || empty($conf->global->PRODUIT_SOUSPRODUITS) || ($isavirtualproduct && empty($conf->global->STOCK_EXCLUDE_VIRTUAL_PRODUCTS))) {  // If STOCK_EXCLUDE_VIRTUAL_PRODUCTS is set, we do not manage stock for kits/virtual products.
						if ($product_stock < $qty) {
							$langs->load("errors");
							$this->error = $langs->trans('ErrorStockIsNotEnoughToAddProductOnShipment', $product->ref);
							$this->errorhidden = 'ErrorStockIsNotEnoughToAddProductOnShipment';

							$this->db->rollback();
							return -3;
						}
					}
				}
			}
		}

		// If product need a batch number, we should not have called this function but addline_batch instead.
		if (!empty($conf->productbatch->enabled) && !empty($orderline->fk_product) && !empty($orderline->product_tobatch)) {
			$this->error = 'ADDLINE_WAS_CALLED_INSTEAD_OF_ADDLINEBATCH';
			return -4;
		}

		// extrafields
		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($array_options) && count($array_options) > 0) { // For avoid conflicts if trigger used
			$line->array_options = $array_options;
		}

		$this->lines[$num] = $line;

		return 1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Add a shipment line with batch record
	 *
	 * @param 	array		$dbatch		Array of value (key 'detail' -> Array, key 'qty' total quantity for line, key ix_l : original line index)
	 * @param	array		$array_options		extrafields array
	 * @return	int						<0 if KO, >0 if OK
	 */
	public function addline_batch($dbatch, $array_options = 0)
	{
		// phpcs:enable
		global $conf, $langs;

		$num = count($this->lines);
		if ($dbatch['qty'] > 0) {
			$line = new ExpeditionLigne($this->db);
			$tab = array();
			foreach ($dbatch['detail'] as $key => $value) {
				if ($value['q'] > 0) {
					// $value['q']=qty to move
					// $value['id_batch']=id into llx_product_batch of record to move
					//var_dump($value);

					$linebatch = new ExpeditionLineBatch($this->db);
					$ret = $linebatch->fetchFromStock($value['id_batch']); // load serial, sellby, eatby
					if ($ret < 0) {
						$this->error = $linebatch->error;
						return -1;
					}
					$linebatch->qty = $value['q'];
					$tab[] = $linebatch;

					if ($conf->global->STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT) {
						require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
						$prod_batch = new Productbatch($this->db);
						$prod_batch->fetch($value['id_batch']);

						if ($prod_batch->qty < $linebatch->qty) {
							$langs->load("errors");
							$this->errors[] = $langs->trans('ErrorStockIsNotEnoughToAddProductOnShipment', $prod_batch->fk_product);
							dol_syslog(get_class($this)."::addline_batch error=Product ".$prod_batch->batch.": ".$this->errorsToString(), LOG_ERR);
							$this->db->rollback();
							return -1;
						}
					}

					//var_dump($linebatch);
				}
			}
			$line->entrepot_id = $linebatch->entrepot_id;
			$line->origin_line_id = $dbatch['ix_l']; // deprecated
			$line->fk_origin_line = $dbatch['ix_l'];
			$line->qty = $dbatch['qty'];
			$line->detail_batch = $tab;

			// extrafields
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($array_options) && count($array_options) > 0) { // For avoid conflicts if trigger used
				$line->array_options = $array_options;
			}

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
	public function update($user = null, $notrigger = 0)
	{
		global $conf;
		$error = 0;

		// Clean parameters

		if (isset($this->ref)) {
			$this->ref = trim($this->ref);
		}
		if (isset($this->entity)) {
			$this->entity = (int) $this->entity;
		}
		if (isset($this->ref_customer)) {
			$this->ref_customer = trim($this->ref_customer);
		}
		if (isset($this->socid)) {
			$this->socid = (int) $this->socid;
		}
		if (isset($this->fk_user_author)) {
			$this->fk_user_author = (int) $this->fk_user_author;
		}
		if (isset($this->fk_user_valid)) {
			$this->fk_user_valid = (int) $this->fk_user_valid;
		}
		if (isset($this->fk_delivery_address)) {
			$this->fk_delivery_address = (int) $this->fk_delivery_address;
		}
		if (isset($this->shipping_method_id)) {
			$this->shipping_method_id = (int) $this->shipping_method_id;
		}
		if (isset($this->tracking_number)) {
			$this->tracking_number = trim($this->tracking_number);
		}
		if (isset($this->statut)) {
			$this->statut = (int) $this->statut;
		}
		if (isset($this->trueDepth)) {
			$this->trueDepth = trim($this->trueDepth);
		}
		if (isset($this->trueWidth)) {
			$this->trueWidth = trim($this->trueWidth);
		}
		if (isset($this->trueHeight)) {
			$this->trueHeight = trim($this->trueHeight);
		}
		if (isset($this->size_units)) {
			$this->size_units = trim($this->size_units);
		}
		if (isset($this->weight_units)) {
			$this->weight_units = trim($this->weight_units);
		}
		if (isset($this->trueWeight)) {
			$this->weight = trim($this->trueWeight);
		}
		if (isset($this->note_private)) {
			$this->note_private = trim($this->note_private);
		}
		if (isset($this->note_public)) {
			$this->note_public = trim($this->note_public);
		}
		if (isset($this->model_pdf)) {
			$this->model_pdf = trim($this->model_pdf);
		}



		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."expedition SET";

		$sql .= " ref=".(isset($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null").",";
		$sql .= " ref_ext=".(isset($this->ref_ext) ? "'".$this->db->escape($this->ref_ext)."'" : "null").",";
		$sql .= " ref_customer=".(isset($this->ref_customer) ? "'".$this->db->escape($this->ref_customer)."'" : "null").",";
		$sql .= " fk_soc=".(isset($this->socid) ? $this->socid : "null").",";
		$sql .= " date_creation=".(dol_strlen($this->date_creation) != 0 ? "'".$this->db->idate($this->date_creation)."'" : 'null').",";
		$sql .= " fk_user_author=".(isset($this->fk_user_author) ? $this->fk_user_author : "null").",";
		$sql .= " date_valid=".(dol_strlen($this->date_valid) != 0 ? "'".$this->db->idate($this->date_valid)."'" : 'null').",";
		$sql .= " fk_user_valid=".(isset($this->fk_user_valid) ? $this->fk_user_valid : "null").",";
		$sql .= " date_expedition=".(dol_strlen($this->date_expedition) != 0 ? "'".$this->db->idate($this->date_expedition)."'" : 'null').",";
		$sql .= " date_delivery=".(dol_strlen($this->date_delivery) != 0 ? "'".$this->db->idate($this->date_delivery)."'" : 'null').",";
		$sql .= " fk_address=".(isset($this->fk_delivery_address) ? $this->fk_delivery_address : "null").",";
		$sql .= " fk_shipping_method=".((isset($this->shipping_method_id) && $this->shipping_method_id > 0) ? $this->shipping_method_id : "null").",";
		$sql .= " tracking_number=".(isset($this->tracking_number) ? "'".$this->db->escape($this->tracking_number)."'" : "null").",";
		$sql .= " fk_statut=".(isset($this->statut) ? $this->statut : "null").",";
		$sql .= " fk_projet=".(isset($this->fk_project) ? $this->fk_project : "null").",";
		$sql .= " height=".(($this->trueHeight != '') ? $this->trueHeight : "null").",";
		$sql .= " width=".(($this->trueWidth != '') ? $this->trueWidth : "null").",";
		$sql .= " size_units=".(isset($this->size_units) ? $this->size_units : "null").",";
		$sql .= " size=".(($this->trueDepth != '') ? $this->trueDepth : "null").",";
		$sql .= " weight_units=".(isset($this->weight_units) ? $this->weight_units : "null").",";
		$sql .= " weight=".(($this->trueWeight != '') ? $this->trueWeight : "null").",";
		$sql .= " note_private=".(isset($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null").",";
		$sql .= " note_public=".(isset($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null").",";
		$sql .= " model_pdf=".(isset($this->model_pdf) ? "'".$this->db->escape($this->model_pdf)."'" : "null").",";
		$sql .= " entity=".$conf->entity;

		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++; $this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('SHIPPING_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 * 	Cancel shipment.
	 *
	 *  @param  int  $notrigger 			Disable triggers
	 *  @param  bool $also_update_stock  	true if the stock should be increased back (false by default)
	 * 	@return	int							>0 if OK, 0 if deletion done but failed to delete files, <0 if KO
	 */
	public function cancel($notrigger = 0, $also_update_stock = false)
	{
		global $conf, $langs, $user;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;
		$this->error = '';

		$this->db->begin();

		// Add a protection to refuse deleting if shipment has at least one delivery
		$this->fetchObjectLinked($this->id, 'shipping', 0, 'delivery'); // Get deliveries linked to this shipment
		if (count($this->linkedObjectsIds) > 0) {
			$this->error = 'ErrorThereIsSomeDeliveries';
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('SHIPPING_CANCEL', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Stock control
		if (!$error && $conf->stock->enabled &&
			(($conf->global->STOCK_CALCULATE_ON_SHIPMENT && $this->statut > self::STATUS_DRAFT) ||
			 ($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE && $this->statut == self::STATUS_CLOSED && $also_update_stock))) {
			require_once DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php";

			$langs->load("agenda");

			// Loop on each product line to add a stock movement and delete features
			$sql = "SELECT cd.fk_product, cd.subprice, ed.qty, ed.fk_entrepot, ed.rowid as expeditiondet_id";
			$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
			$sql .= " ".MAIN_DB_PREFIX."expeditiondet as ed";
			$sql .= " WHERE ed.fk_expedition = ".((int) $this->id);
			$sql .= " AND cd.rowid = ed.fk_origin_line";

			dol_syslog(get_class($this)."::delete select details", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$cpt = $this->db->num_rows($resql);

				$shipmentlinebatch = new ExpeditionLineBatch($this->db);

				for ($i = 0; $i < $cpt; $i++) {
					dol_syslog(get_class($this)."::delete movement index ".$i);
					$obj = $this->db->fetch_object($resql);

					$mouvS = new MouvementStock($this->db);
					// we do not log origin because it will be deleted
					$mouvS->origin = null;
					// get lot/serial
					$lotArray = null;
					if (isModEnabled('productbatch')) {
						$lotArray = $shipmentlinebatch->fetchAll($obj->expeditiondet_id);
						if (!is_array($lotArray)) {
							$error++;
							$this->errors[] = "Error ".$this->db->lasterror();
						}
					}

					if (empty($lotArray)) {
						// no lot/serial
						// We increment stock of product (and sub-products)
						// We use warehouse selected for each line
						$result = $mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $obj->qty, 0, $langs->trans("ShipmentCanceledInDolibarr", $this->ref)); // Price is set to 0, because we don't want to see WAP changed
						if ($result < 0) {
							$error++;
							$this->errors = array_merge($this->errors, $mouvS->errors);
							break;
						}
					} else {
						// We increment stock of batches
						// We use warehouse selected for each line
						foreach ($lotArray as $lot) {
							$result = $mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $lot->qty, 0, $langs->trans("ShipmentCanceledInDolibarr", $this->ref), $lot->eatby, $lot->sellby, $lot->batch); // Price is set to 0, because we don't want to see WAP changed
							if ($result < 0) {
								$error++;
								$this->errors = array_merge($this->errors, $mouvS->errors);
								break;
							}
						}
						if ($error) {
							break; // break for loop incase of error
						}
					}
				}
			} else {
				$error++; $this->errors[] = "Error ".$this->db->lasterror();
			}
		}

		// delete batch expedition line
		if (!$error && $conf->productbatch->enabled) {
			$shipmentlinebatch = new ExpeditionLineBatch($this->db);
			if ($shipmentlinebatch->deleteFromShipment($this->id) < 0) {
				$error++; $this->errors[] = "Error ".$this->db->lasterror();
			}
		}


		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."expeditiondet";
			$sql .= " WHERE fk_expedition = ".((int) $this->id);

			if ($this->db->query($sql)) {
				// Delete linked object
				$res = $this->deleteObjectLinked();
				if ($res < 0) {
					$error++;
				}

				// No delete expedition
				if (!$error) {
					$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."expedition";
					$sql .= " WHERE rowid = ".((int) $this->id);

					if ($this->db->query($sql)) {
						if (!empty($this->origin) && $this->origin_id > 0) {
							$this->fetch_origin();
							$origin = $this->origin;
							if ($this->$origin->statut == Commande::STATUS_SHIPMENTONPROCESS) {     // If order source of shipment is "shipment in progress"
								// Check if there is no more shipment. If not, we can move back status of order to "validated" instead of "shipment in progress"
								$this->$origin->loadExpeditions();
								//var_dump($this->$origin->expeditions);exit;
								if (count($this->$origin->expeditions) <= 0) {
									$this->$origin->setStatut(Commande::STATUS_VALIDATED);
								}
							}
						}

						if (!$error) {
							$this->db->commit();

							// We delete PDFs
							$ref = dol_sanitizeFileName($this->ref);
							if (!empty($conf->expedition->dir_output)) {
								$dir = $conf->expedition->dir_output.'/sending/'.$ref;
								$file = $dir.'/'.$ref.'.pdf';
								if (file_exists($file)) {
									if (!dol_delete_file($file)) {
										return 0;
									}
								}
								if (file_exists($dir)) {
									if (!dol_delete_dir_recursive($dir)) {
										$this->error = $langs->trans("ErrorCanNotDeleteDir", $dir);
										return 0;
									}
								}
							}

							return 1;
						} else {
							$this->db->rollback();
							return -1;
						}
					} else {
						$this->error = $this->db->lasterror()." - sql=$sql";
						$this->db->rollback();
						return -3;
					}
				} else {
					$this->error = $this->db->lasterror()." - sql=$sql";
					$this->db->rollback();
					return -2;
				}//*/
			} else {
				$this->error = $this->db->lasterror()." - sql=$sql";
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * 	Delete shipment.
	 * 	Warning, do not delete a shipment if a delivery is linked to (with table llx_element_element)
	 *
	 *  @param  int  $notrigger 			Disable triggers
	 *  @param  bool $also_update_stock  	true if the stock should be increased back (false by default)
	 * 	@return	int							>0 if OK, 0 if deletion done but failed to delete files, <0 if KO
	 */
	public function delete($notrigger = 0, $also_update_stock = false)
	{
		global $conf, $langs, $user;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;
		$this->error = '';

		$this->db->begin();

		// Add a protection to refuse deleting if shipment has at least one delivery
		$this->fetchObjectLinked($this->id, 'shipping', 0, 'delivery'); // Get deliveries linked to this shipment
		if (count($this->linkedObjectsIds) > 0) {
			$this->error = 'ErrorThereIsSomeDeliveries';
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('SHIPPING_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Stock control
		if (!$error && $conf->stock->enabled &&
			(($conf->global->STOCK_CALCULATE_ON_SHIPMENT && $this->statut > self::STATUS_DRAFT) ||
			 ($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE && $this->statut == self::STATUS_CLOSED && $also_update_stock))) {
			require_once DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php";

			$langs->load("agenda");

			// we try deletion of batch line even if module batch not enabled in case of the module were enabled and disabled previously
			$shipmentlinebatch = new ExpeditionLineBatch($this->db);

			// Loop on each product line to add a stock movement
			$sql = "SELECT cd.fk_product, cd.subprice, ed.qty, ed.fk_entrepot, ed.rowid as expeditiondet_id";
			$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
			$sql .= " ".MAIN_DB_PREFIX."expeditiondet as ed";
			$sql .= " WHERE ed.fk_expedition = ".((int) $this->id);
			$sql .= " AND cd.rowid = ed.fk_origin_line";

			dol_syslog(get_class($this)."::delete select details", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$cpt = $this->db->num_rows($resql);
				for ($i = 0; $i < $cpt; $i++) {
					dol_syslog(get_class($this)."::delete movement index ".$i);
					$obj = $this->db->fetch_object($resql);

					$mouvS = new MouvementStock($this->db);
					// we do not log origin because it will be deleted
					$mouvS->origin = null;
					// get lot/serial
					$lotArray = $shipmentlinebatch->fetchAll($obj->expeditiondet_id);
					if (!is_array($lotArray)) {
						$error++; $this->errors[] = "Error ".$this->db->lasterror();
					}
					if (empty($lotArray)) {
						// no lot/serial
						// We increment stock of product (and sub-products)
						// We use warehouse selected for each line
						$result = $mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $obj->qty, 0, $langs->trans("ShipmentDeletedInDolibarr", $this->ref)); // Price is set to 0, because we don't want to see WAP changed
						if ($result < 0) {
							$error++;
							$this->errors = array_merge($this->errors, $mouvS->errors);
							break;
						}
					} else {
						// We increment stock of batches
						// We use warehouse selected for each line
						foreach ($lotArray as $lot) {
							$result = $mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $lot->qty, 0, $langs->trans("ShipmentDeletedInDolibarr", $this->ref), $lot->eatby, $lot->sellby, $lot->batch); // Price is set to 0, because we don't want to see WAP changed
							if ($result < 0) {
								$error++;
								$this->errors = array_merge($this->errors, $mouvS->errors);
								break;
							}
						}
						if ($error) {
							break; // break for loop incase of error
						}
					}
				}
			} else {
				$error++; $this->errors[] = "Error ".$this->db->lasterror();
			}
		}

		// delete batch expedition line
		if (!$error) {
			$shipmentlinebatch = new ExpeditionLineBatch($this->db);
			if ($shipmentlinebatch->deleteFromShipment($this->id) < 0) {
				$error++; $this->errors[] = "Error ".$this->db->lasterror();
			}
		}

		if (!$error) {
			$main = MAIN_DB_PREFIX.'expeditiondet';
			$ef = $main."_extrafields";
			$sqlef = "DELETE FROM $ef WHERE fk_object IN (SELECT rowid FROM $main WHERE fk_expedition = ".((int) $this->id).")";

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."expeditiondet";
			$sql .= " WHERE fk_expedition = ".((int) $this->id);

			if ($this->db->query($sqlef) && $this->db->query($sql)) {
				// Delete linked object
				$res = $this->deleteObjectLinked();
				if ($res < 0) {
					$error++;
				}

				// delete extrafields
				$res = $this->deleteExtraFields();
				if ($res < 0) {
					$error++;
				}

				if (!$error) {
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."expedition";
					$sql .= " WHERE rowid = ".((int) $this->id);

					if ($this->db->query($sql)) {
						if (!empty($this->origin) && $this->origin_id > 0) {
							$this->fetch_origin();
							$origin = $this->origin;
							if ($this->$origin->statut == Commande::STATUS_SHIPMENTONPROCESS) {     // If order source of shipment is "shipment in progress"
								// Check if there is no more shipment. If not, we can move back status of order to "validated" instead of "shipment in progress"
								$this->$origin->loadExpeditions();
								//var_dump($this->$origin->expeditions);exit;
								if (count($this->$origin->expeditions) <= 0) {
									$this->$origin->setStatut(Commande::STATUS_VALIDATED);
								}
							}
						}

						if (!$error) {
							$this->db->commit();

							// Delete record into ECM index (Note that delete is also done when deleting files with the dol_delete_dir_recursive
							$this->deleteEcmFiles();

							// We delete PDFs
							$ref = dol_sanitizeFileName($this->ref);
							if (!empty($conf->expedition->dir_output)) {
								$dir = $conf->expedition->dir_output.'/sending/'.$ref;
								$file = $dir.'/'.$ref.'.pdf';
								if (file_exists($file)) {
									if (!dol_delete_file($file)) {
										return 0;
									}
								}
								if (file_exists($dir)) {
									if (!dol_delete_dir_recursive($dir)) {
										$this->error = $langs->trans("ErrorCanNotDeleteDir", $dir);
										return 0;
									}
								}
							}

							return 1;
						} else {
							$this->db->rollback();
							return -1;
						}
					} else {
						$this->error = $this->db->lasterror()." - sql=$sql";
						$this->db->rollback();
						return -3;
					}
				} else {
					$this->error = $this->db->lasterror()." - sql=$sql";
					$this->db->rollback();
					return -2;
				}
			} else {
				$this->error = $this->db->lasterror()." - sql=$sql";
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Load lines
	 *
	 *	@return	int		>0 if OK, Otherwise if KO
	 */
	public function fetch_lines()
	{
		// phpcs:enable
		global $conf, $mysoc;

		$this->lines = array();

		// NOTE: This fetch_lines is special because it groups all lines with the same origin_line_id into one line.
		// TODO: See if we can restore a common fetch_lines (one line = one record)

		$sql = "SELECT cd.rowid, cd.fk_product, cd.label as custom_label, cd.description, cd.qty as qty_asked, cd.product_type, cd.fk_unit";
		$sql .= ", cd.total_ht, cd.total_localtax1, cd.total_localtax2, cd.total_ttc, cd.total_tva";
		$sql .= ", cd.vat_src_code, cd.tva_tx, cd.localtax1_tx, cd.localtax2_tx, cd.localtax1_type, cd.localtax2_type, cd.info_bits, cd.price, cd.subprice, cd.remise_percent,cd.buy_price_ht as pa_ht";
		$sql .= ", cd.fk_multicurrency, cd.multicurrency_code, cd.multicurrency_subprice, cd.multicurrency_total_ht, cd.multicurrency_total_tva, cd.multicurrency_total_ttc, cd.rang";
		$sql .= ", ed.rowid as line_id, ed.qty as qty_shipped, ed.fk_origin_line, ed.fk_entrepot";
		$sql .= ", p.ref as product_ref, p.label as product_label, p.fk_product_type";
		$sql .= ", p.weight, p.weight_units, p.length, p.length_units, p.surface, p.surface_units, p.volume, p.volume_units, p.tosell as product_tosell, p.tobuy as product_tobuy, p.tobatch as product_tobatch";
		$sql .= " FROM ".MAIN_DB_PREFIX."expeditiondet as ed, ".MAIN_DB_PREFIX."commandedet as cd";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = cd.fk_product";
		$sql .= " WHERE ed.fk_expedition = ".((int) $this->id);
		$sql .= " AND ed.fk_origin_line = cd.rowid";
		$sql .= " ORDER BY cd.rang, ed.fk_origin_line";		// We need after a break on fk_origin_line but when there is no break on fk_origin_line, cd.rang is same so we can add it as first order criteria.

		dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
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

			$shipmentlinebatch = new ExpeditionLineBatch($this->db);

			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				if ($originline > 0 && $originline == $obj->fk_origin_line) {
					$line->entrepot_id = 0; // entrepod_id in details_entrepot
					$line->qty_shipped += $obj->qty_shipped;
				} else {
					$line = new ExpeditionLigne($this->db);
					$line->entrepot_id    	= $obj->fk_entrepot;	// this is a property of a shipment line
					$line->qty_shipped    	= $obj->qty_shipped;	// this is a property of a shipment line
				}

				$detail_entrepot              = new stdClass();
				$detail_entrepot->entrepot_id = $obj->fk_entrepot;
				$detail_entrepot->qty_shipped = $obj->qty_shipped;
				$detail_entrepot->line_id     = $obj->line_id;
				$line->details_entrepot[]     = $detail_entrepot;

				$line->line_id          = $obj->line_id;
				$line->rowid            = $obj->line_id; // TODO deprecated
				$line->id               = $obj->line_id;

				$line->fk_origin = 'orderline';
				$line->fk_origin_line 	= $obj->fk_origin_line;
				$line->origin_line_id 	= $obj->fk_origin_line; // TODO deprecated

				$line->fk_expedition    = $this->id; // id of parent

				$line->product_type     = $obj->product_type;
				$line->fk_product     	= $obj->fk_product;
				$line->fk_product_type	= $obj->fk_product_type;
				$line->ref = $obj->product_ref; // TODO deprecated
				$line->product_ref = $obj->product_ref;
				$line->product_label = $obj->product_label;
				$line->libelle        	= $obj->product_label; // TODO deprecated
				$line->product_tosell = $obj->product_tosell;
				$line->product_tobuy = $obj->product_tobuy;
				$line->product_tobatch = $obj->product_tobatch;
				$line->label = $obj->custom_label;
				$line->description    	= $obj->description;
				$line->qty_asked      	= $obj->qty_asked;
				$line->rang = $obj->rang;
				$line->weight         	= $obj->weight;
				$line->weight_units   	= $obj->weight_units;
				$line->length         	= $obj->length;
				$line->length_units   	= $obj->length_units;
				$line->surface        	= $obj->surface;
				$line->surface_units = $obj->surface_units;
				$line->volume         	= $obj->volume;
				$line->volume_units   	= $obj->volume_units;
				$line->fk_unit = $obj->fk_unit;

				$line->pa_ht = $obj->pa_ht;

				// Local taxes
				$localtax_array = array(0=>$obj->localtax1_type, 1=>$obj->localtax1_tx, 2=>$obj->localtax2_type, 3=>$obj->localtax2_tx);
				$localtax1_tx = get_localtax($obj->tva_tx, 1, $this->thirdparty);
				$localtax2_tx = get_localtax($obj->tva_tx, 2, $this->thirdparty);

				// For invoicing
				$tabprice = calcul_price_total($obj->qty_shipped, $obj->subprice, $obj->remise_percent, $obj->tva_tx, $localtax1_tx, $localtax2_tx, 0, 'HT', $obj->info_bits, $obj->fk_product_type, $mysoc, $localtax_array); // We force type to 0
				$line->desc = $obj->description; // We need ->desc because some code into CommonObject use desc (property defined for other elements)
				$line->qty = $line->qty_shipped;
				$line->total_ht = $tabprice[0];
				$line->total_localtax1 	= $tabprice[9];
				$line->total_localtax2 	= $tabprice[10];
				$line->total_ttc	 	= $tabprice[2];
				$line->total_tva	 	= $tabprice[1];
				$line->vat_src_code = $obj->vat_src_code;
				$line->tva_tx = $obj->tva_tx;
				$line->localtax1_tx 	= $obj->localtax1_tx;
				$line->localtax2_tx 	= $obj->localtax2_tx;
				$line->info_bits = $obj->info_bits;
				$line->price = $obj->price;
				$line->subprice = $obj->subprice;
				$line->remise_percent = $obj->remise_percent;

				$this->total_ht += $tabprice[0];
				$this->total_tva += $tabprice[1];
				$this->total_ttc += $tabprice[2];
				$this->total_localtax1 += $tabprice[9];
				$this->total_localtax2 += $tabprice[10];

				// Multicurrency
				$this->fk_multicurrency = $obj->fk_multicurrency;
				$this->multicurrency_code = $obj->multicurrency_code;
				$this->multicurrency_subprice 	= $obj->multicurrency_subprice;
				$this->multicurrency_total_ht 	= $obj->multicurrency_total_ht;
				$this->multicurrency_total_tva 	= $obj->multicurrency_total_tva;
				$this->multicurrency_total_ttc 	= $obj->multicurrency_total_ttc;

				if ($originline != $obj->fk_origin_line) {
					$line->detail_batch = array();
				}

				// Detail of batch
				if (!empty($conf->productbatch->enabled) && $obj->line_id > 0 && $obj->product_tobatch > 0) {
					$newdetailbatch = $shipmentlinebatch->fetchAll($obj->line_id, $obj->fk_product);

					if (is_array($newdetailbatch)) {
						if ($originline != $obj->fk_origin_line) {
							$line->detail_batch = $newdetailbatch;
						} else {
							$line->detail_batch = array_merge($line->detail_batch, $newdetailbatch);
						}
					}
				}

				$line->fetch_optionals();

				if ($originline != $obj->fk_origin_line) {
					$this->lines[$lineindex] = $line;
					$lineindex++;
				} else {
					$line->total_ht += $tabprice[0];
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
		} else {
			$this->error = $this->db->error();
			return -3;
		}
	}

	/**
	 *  Delete detail line
	 *
	 *  @param		User	$user			User making deletion
	 *  @param		int		$lineid			Id of line to delete
	 *  @return     int         			>0 if OK, <0 if KO
	 */
	public function deleteline($user, $lineid)
	{
		global $user;

		if ($this->statut == self::STATUS_DRAFT) {
			$this->db->begin();

			$line = new ExpeditionLigne($this->db);

			// For triggers
			$line->fetch($lineid);

			if ($line->delete($user) > 0) {
				//$this->update_price(1);

				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}
	}


	/**
	 *	Return clicable link of object (with eventually picto)
	 *
	 *	@param      int			$withpicto      			Add picto into link
	 *	@param      string		$option         			Where the link point to
	 *	@param      int			$max          				Max length to show
	 *	@param      int			$short						Use short labels
	 *  @param      int         $notooltip      			1=No tooltip
	 *  @param      int     	$save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return     string          						String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $max = 0, $short = 0, $notooltip = 0, $save_lastsearch_value = -1)
	{
		global $langs, $conf, $hookmanager;

		$result = '';
		$label = '<u>'.$langs->trans("Shipment").'</u>';
		$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		$label .= '<br><b>'.$langs->trans('RefCustomer').':</b> '.($this->ref_customer ? $this->ref_customer : $this->ref_client);

		$url = DOL_URL_ROOT.'/expedition/card.php?id='.$this->id;

		if ($short) {
			return $url;
		}

		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("Shipment");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip"';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), $this->picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;
		global $action;
		$hookmanager->initHooks(array($this->element . 'dao'));
		$parameters = array('id'=>$this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}
		return $result;
	}

	/**
	 *	Return status label
	 *
	 *	@param      int		$mode      	0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto
	 *	@return     string      		Libelle
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->statut, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return label of a status
	 *
	 * @param   int		$status		Id statut
	 * @param  	int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return  string				Label of status
	 */
	public function LibStatut($status, $mode)
	{
		// phpcs:enable
		global $langs;

		$labelStatus = $langs->transnoentitiesnoconv($this->statuts[$status]);
		$labelStatusShort = $langs->transnoentitiesnoconv($this->statutshorts[$status]);

		$statusType = 'status'.$status;
		if ($status == self::STATUS_VALIDATED) {
			$statusType = 'status4';
		}
		if ($status == self::STATUS_CLOSED) {
			$statusType = 'status6';
		}
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status9';
		}

		return dolGetStatus($labelStatus, $labelStatusShort, '', $statusType, $mode);
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
		global $langs;

		$now = dol_now();

		dol_syslog(get_class($this)."::initAsSpecimen");

		// Load array of products prodids
		$num_prods = 0;
		$prodids = array();
		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."product";
		$sql .= " WHERE entity IN (".getEntity('product').")";
		$resql = $this->db->query($sql);
		if ($resql) {
			$num_prods = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_prods) {
				$i++;
				$row = $this->db->fetch_row($resql);
				$prodids[$i] = $row[0];
			}
		}

		$order = new Commande($this->db);
		$order->initAsSpecimen();

		// Initialise parametres
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;
		$this->statut               = self::STATUS_VALIDATED;
		$this->livraison_id         = 0;
		$this->date                 = $now;
		$this->date_creation        = $now;
		$this->date_valid           = $now;
		$this->date_delivery        = $now;
		$this->date_expedition      = $now + 24 * 3600;

		$this->entrepot_id          = 0;
		$this->fk_delivery_address  = 0;
		$this->socid                = 1;

		$this->commande_id          = 0;
		$this->commande             = $order;

		$this->origin_id            = 1;
		$this->origin               = 'commande';

		$this->note_private = 'Private note';
		$this->note_public = 'Public note';

		$nbp = 5;
		$xnbp = 0;
		while ($xnbp < $nbp) {
			$line = new ExpeditionLigne($this->db);
			$line->desc = $langs->trans("Description")." ".$xnbp;
			$line->libelle = $langs->trans("Description")." ".$xnbp; // deprecated
			$line->label = $langs->trans("Description")." ".$xnbp;
			$line->qty = 10;
			$line->qty_asked = 5;
			$line->qty_shipped = 4;
			$line->fk_product = $this->commande->lines[$xnbp]->fk_product;

			$this->lines[] = $line;
			$xnbp++;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Set delivery date
	 *
	 *	@param      User 	$user        		Object user that modify
	 *	@param      int		$delivery_date		Delivery date
	 *	@return     int         				<0 if ko, >0 if ok
	 *	@deprecated Use  setDeliveryDate
	 */
	public function set_date_livraison($user, $delivery_date)
	{
		// phpcs:enable
		return $this->setDeliveryDate($user, $delivery_date);
	}

	/**
	 *	Set the planned delivery date
	 *
	 *	@param      User			$user        		Objet user that modify
	 *	@param      integer 		$delivery_date     Date of delivery
	 *	@return     int         						<0 if KO, >0 if OK
	 */
	public function setDeliveryDate($user, $delivery_date)
	{
		if ($user->rights->expedition->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."expedition";
			$sql .= " SET date_delivery = ".($delivery_date ? "'".$this->db->idate($delivery_date)."'" : 'null');
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::setDeliveryDate", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->date_delivery = $delivery_date;
				return 1;
			} else {
				$this->error = $this->db->error();
				return -1;
			}
		} else {
			return -2;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Fetch deliveries method and return an array. Load array this->meths(rowid=>label).
	 *
	 * 	@return	void
	 */
	public function fetch_delivery_methods()
	{
		// phpcs:enable
		global $langs;
		$this->meths = array();

		$sql = "SELECT em.rowid, em.code, em.libelle as label";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
		$sql .= " WHERE em.active = 1";
		$sql .= " ORDER BY em.libelle ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$label = $langs->trans('SendingMethod'.$obj->code);
				$this->meths[$obj->rowid] = ($label != 'SendingMethod'.$obj->code ? $label : $obj->label);
			}
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Fetch all deliveries method and return an array. Load array this->listmeths.
	 *
	 *  @param  int      $id     only this carrier, all if none
	 *  @return void
	 */
	public function list_delivery_methods($id = '')
	{
		// phpcs:enable
		global $langs;

		$this->listmeths = array();
		$i = 0;

		$sql = "SELECT em.rowid, em.code, em.libelle as label, em.description, em.tracking, em.active";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
		if ($id != '') {
			$sql .= " WHERE em.rowid=".((int) $id);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->listmeths[$i]['rowid'] = $obj->rowid;
				$this->listmeths[$i]['code'] = $obj->code;
				$label = $langs->trans('SendingMethod'.$obj->code);
				$this->listmeths[$i]['libelle'] = ($label != 'SendingMethod'.$obj->code ? $label : $obj->label);
				$this->listmeths[$i]['description'] = $obj->description;
				$this->listmeths[$i]['tracking'] = $obj->tracking;
				$this->listmeths[$i]['active'] = $obj->active;
				$i++;
			}
		}
	}

	/**
	 * Forge an set tracking url
	 *
	 * @param	string	$value		Value
	 * @return	void
	 */
	public function getUrlTrackingStatus($value = '')
	{
		if (!empty($this->shipping_method_id)) {
			$sql = "SELECT em.code, em.tracking";
			$sql .= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
			$sql .= " WHERE em.rowid = ".((int) $this->shipping_method_id);

			$resql = $this->db->query($sql);
			if ($resql) {
				if ($obj = $this->db->fetch_object($resql)) {
					$tracking = $obj->tracking;
				}
			}
		}

		if (!empty($tracking) && !empty($value)) {
			$url = str_replace('{TRACKID}', $value, $tracking);
			$this->tracking_url = sprintf('<a target="_blank" rel="noopener noreferrer" href="%s">'.($value ? $value : 'url').'</a>', $url, $url);
		} else {
			$this->tracking_url = $value;
		}
	}

	/**
	 *	Classify the shipping as closed.
	 *
	 *	@return     int     <0 if KO, >0 if OK
	 */
	public function setClosed()
	{
		global $conf, $langs, $user;

		$error = 0;

		// Protection. This avoid to move stock later when we should not
		if ($this->statut == self::STATUS_CLOSED) {
			return 0;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."expedition SET fk_statut = ".self::STATUS_CLOSED;
		$sql .= " WHERE rowid = ".((int) $this->id)." AND fk_statut > 0";

		$resql = $this->db->query($sql);
		if ($resql) {
			// Set order billed if 100% of order is shipped (qty in shipment lines match qty in order lines)
			if ($this->origin == 'commande' && $this->origin_id > 0) {
				$order = new Commande($this->db);
				$order->fetch($this->origin_id);

				$order->loadExpeditions(self::STATUS_CLOSED); // Fill $order->expeditions = array(orderlineid => qty)

				$shipments_match_order = 1;
				foreach ($order->lines as $line) {
					$lineid = $line->id;
					$qty = $line->qty;
					if (($line->product_type == 0 || !empty($conf->global->STOCK_SUPPORTS_SERVICES)) && $order->expeditions[$lineid] != $qty) {
						$shipments_match_order = 0;
						$text = 'Qty for order line id '.$lineid.' is '.$qty.'. However in the shipments with status Expedition::STATUS_CLOSED='.self::STATUS_CLOSED.' we have qty = '.$order->expeditions[$lineid].', so we can t close order';
						dol_syslog($text);
						break;
					}
				}
				if ($shipments_match_order) {
					dol_syslog("Qty for the ".count($order->lines)." lines of the origin order is same than qty for lines in the shipment we close (shipments_match_order is true), with new status Expedition::STATUS_CLOSED=".self::STATUS_CLOSED.', so we close order');
					// We close the order
					$order->cloture($user);		// Note this may also create an invoice if module workflow ask it
				}
			}

			$this->statut = self::STATUS_CLOSED;	// Will be revert to STATUS_VALIDATED at end if there is a rollback
			$this->status = self::STATUS_CLOSED;	// Will be revert to STATUS_VALIDATED at end if there is a rollback

			// If stock increment is done on closing
			if (!$error && !empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE)) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

				$langs->load("agenda");

				// Loop on each product line to add a stock movement
				// TODO possibilite d'expedier a partir d'une propale ou autre origine ?
				$sql = "SELECT cd.fk_product, cd.subprice,";
				$sql .= " ed.rowid, ed.qty, ed.fk_entrepot,";
				$sql .= " e.ref,";
				$sql .= " edb.rowid as edbrowid, edb.eatby, edb.sellby, edb.batch, edb.qty as edbqty, edb.fk_origin_stock";
				$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
				$sql .= " ".MAIN_DB_PREFIX."expeditiondet as ed";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expeditiondet_batch as edb on edb.fk_expeditiondet = ed.rowid";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."expedition as e ON ed.fk_expedition = e.rowid";
				$sql .= " WHERE ed.fk_expedition = ".((int) $this->id);
				$sql .= " AND cd.rowid = ed.fk_origin_line";

				dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$cpt = $this->db->num_rows($resql);
					for ($i = 0; $i < $cpt; $i++) {
						$obj = $this->db->fetch_object($resql);
						if (empty($obj->edbrowid)) {
							$qty = $obj->qty;
						} else {
							$qty = $obj->edbqty;
						}
						if ($qty <= 0) {
							continue;
						}
						dol_syslog(get_class($this)."::valid movement index ".$i." ed.rowid=".$obj->rowid." edb.rowid=".$obj->edbrowid);

						$mouvS = new MouvementStock($this->db);
						$mouvS->origin = &$this;
						$mouvS->setOrigin($this->element, $this->id);

						if (empty($obj->edbrowid)) {
							// line without batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$result = $mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->subprice, $langs->trans("ShipmentClassifyClosedInDolibarr", $obj->ref));
							if ($result < 0) {
								$this->error = $mouvS->error;
								$this->errors = $mouvS->errors;
								$error++;
								break;
							}
						} else {
							// line with batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$result = $mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->subprice, $langs->trans("ShipmentClassifyClosedInDolibarr", $obj->ref), '', $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch, $obj->fk_origin_stock);
							if ($result < 0) {
								$this->error = $mouvS->error;
								$this->errors = $mouvS->errors;
								$error++;
								break;
							}
						}
					}
				} else {
					$this->error = $this->db->lasterror();
					$error++;
				}
			}

			// Call trigger
			if (!$error) {
				$result = $this->call_trigger('SHIPPING_CLOSED', $user);
				if ($result < 0) {
					$error++;
				}
			}
		} else {
			dol_print_error($this->db);
			$error++;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->statut = self::STATUS_VALIDATED;
			$this->status = self::STATUS_VALIDATED;

			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Classify the shipping as invoiced (used when WORKFLOW_BILL_ON_SHIPMENT is on)
	 *
	 *	@deprecated
	 *  @see setBilled()
	 *	@return     int     <0 if ko, >0 if ok
	 */
	public function set_billed()
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::set_billed is deprecated, use setBilled instead", LOG_NOTICE);
		return $this->setBilled();
	}

	/**
	 *	Classify the shipping as invoiced (used when WORKFLOW_BILL_ON_SHIPMENT is on)
	 *
	 *	@return     int     <0 if ko, >0 if ok
	 */
	public function setBilled()
	{
		global $user;
		$error = 0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'expedition SET fk_statut=2, billed=1'; // TODO Update only billed
		$sql .= " WHERE rowid = ".((int) $this->id).' AND fk_statut > 0';

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->statut = self::STATUS_CLOSED;
			$this->billed = 1;

			// Call trigger
			$result = $this->call_trigger('SHIPPING_BILLED', $user);
			if ($result < 0) {
				$error++;
			}
		} else {
			$error++;
			$this->errors[] = $this->db->lasterror;
		}

		if (empty($error)) {
			$this->db->commit();
			return 1;
		} else {
			$this->statut = self::STATUS_VALIDATED;
			$this->billed = 0;
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Classify the shipping as validated/opened
	 *
	 *	@return     int     <0 if KO, 0 if already open, >0 if OK
	 */
	public function reOpen()
	{
		global $conf, $langs, $user;

		$error = 0;

		// Protection. This avoid to move stock later when we should not
		if ($this->statut == self::STATUS_VALIDATED) {
			return 0;
		}

		$this->db->begin();

		$oldbilled = $this->billed;

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'expedition SET fk_statut=1';
		$sql .= " WHERE rowid = ".((int) $this->id).' AND fk_statut > 0';

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->statut = self::STATUS_VALIDATED;
			$this->billed = 0;

			// If stock increment is done on closing
			if (!$error && !empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE)) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

				$langs->load("agenda");

				// Loop on each product line to add a stock movement
				// TODO possibilite d'expedier a partir d'une propale ou autre origine
				$sql = "SELECT cd.fk_product, cd.subprice,";
				$sql .= " ed.rowid, ed.qty, ed.fk_entrepot,";
				$sql .= " edb.rowid as edbrowid, edb.eatby, edb.sellby, edb.batch, edb.qty as edbqty, edb.fk_origin_stock";
				$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
				$sql .= " ".MAIN_DB_PREFIX."expeditiondet as ed";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expeditiondet_batch as edb on edb.fk_expeditiondet = ed.rowid";
				$sql .= " WHERE ed.fk_expedition = ".((int) $this->id);
				$sql .= " AND cd.rowid = ed.fk_origin_line";

				dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$cpt = $this->db->num_rows($resql);
					for ($i = 0; $i < $cpt; $i++) {
						$obj = $this->db->fetch_object($resql);
						if (empty($obj->edbrowid)) {
							$qty = $obj->qty;
						} else {
							$qty = $obj->edbqty;
						}
						if ($qty <= 0) {
							continue;
						}
						dol_syslog(get_class($this)."::reopen expedition movement index ".$i." ed.rowid=".$obj->rowid." edb.rowid=".$obj->edbrowid);

						//var_dump($this->lines[$i]);
						$mouvS = new MouvementStock($this->db);
						$mouvS->origin = &$this;
						$mouvS->setOrigin($this->element, $this->id);

						if (empty($obj->edbrowid)) {
							// line without batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$result = $mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, -$qty, $obj->subprice, $langs->trans("ShipmentUnClassifyCloseddInDolibarr", $numref));
							if ($result < 0) {
								$this->error = $mouvS->error;
								$this->errors = $mouvS->errors;
								$error++;
								break;
							}
						} else {
							// line with batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$result = $mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, -$qty, $obj->subprice, $langs->trans("ShipmentUnClassifyCloseddInDolibarr", $numref), '', $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch, $obj->fk_origin_stock);
							if ($result < 0) {
								$this->error = $mouvS->error;
								$this->errors = $mouvS->errors;
								$error++;
								break;
							}
						}
					}
				} else {
					$this->error = $this->db->lasterror();
					$error++;
				}
			}

			if (!$error) {
				// Call trigger
				$result = $this->call_trigger('SHIPPING_REOPEN', $user);
				if ($result < 0) {
					$error++;
				}
			}
		} else {
			$error++;
			$this->errors[] = $this->db->lasterror();
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->statut = self::STATUS_CLOSED;
			$this->billed = $oldbilled;
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
	 *  @param      null|array  $moreparams     Array to provide more information
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf;

		$outputlangs->load("products");

		if (!dol_strlen($modele)) {
			$modele = 'rouget';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($this->modelpdf)) {	// deprecated
				$modele = $this->modelpdf;
			} elseif (!empty($conf->global->EXPEDITION_ADDON_PDF)) {
				$modele = $conf->global->EXPEDITION_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/expedition/doc/";

		$this->fetch_origin();

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
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
			'expedition'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}
}


/**
 * Classe to manage lines of shipment
 */
class ExpeditionLigne extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'expeditiondet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'expeditiondet';

	/**
	 * @deprecated
	 * @see $fk_origin_line
	 */
	public $origin_line_id;

	/**
	 * @var int ID
	 */
	public $fk_origin_line;

	/**
	 * @var int Id of shipment
	 */
	public $fk_expedition;

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var float qty asked From llx_expeditiondet
	 */
	public $qty;

	/**
	 * @var float qty shipped
	 */
	public $qty_shipped;

	/**
	 * @var int Id of product
	 */
	public $fk_product;
	public $detail_batch;

	/**
	 * @var int Id of warehouse
	 */
	public $entrepot_id;


	/**
	 * @var float qty asked From llx_commandedet or llx_propaldet
	 */
	public $qty_asked;

	/**
	 * @deprecated
	 * @see $product_ref
	 */
	public $ref;

	/**
	 * @var string product ref
	 */
	public $product_ref;

	/**
	 * @deprecated
	 * @see $product_label
	 */
	public $libelle;

	/**
	 * @var string product label
	 */
	public $product_label;

	/**
	 * @var string product description
	 * @deprecated
	 * @see $product_desc
	 */
	public $desc;

	/**
	 * @var string product description
	 */
	public $product_desc;

	/**
	 * Type of the product. 0 for product, 1 for service
	 * @var int
	 */
	public $product_type = 0;

	/**
	 * @var int rang of line
	 */
	public $rang;

	/**
	 * @var float weight
	 */
	public $weight;
	public $weight_units;

	/**
	 * @var float weight
	 */
	public $length;
	public $length_units;

	/**
	 * @var float weight
	 */
	public $surface;
	public $surface_units;

	/**
	 * @var float weight
	 */
	public $volume;
	public $volume_units;

	// Invoicing
	public $remise_percent;
	public $tva_tx;

	/**
	 * @var float total without tax
	 */
	public $total_ht;

	/**
	 * @var float total with tax
	 */
	public $total_ttc;

	/**
	 * @var float total vat
	 */
	public $total_tva;

	/**
	 * @var float total localtax 1
	 */
	public $total_localtax1;

	/**
	 * @var float total localtax 2
	 */
	public $total_localtax2;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *  Load line expedition
	 *
	 *  @param  int		$rowid          Id line order
	 *  @return	int						<0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		$sql = 'SELECT ed.rowid, ed.fk_expedition, ed.fk_entrepot, ed.fk_origin_line, ed.qty, ed.rang';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as ed';
		$sql .= ' WHERE ed.rowid = '.((int) $rowid);
		$result = $this->db->query($sql);
		if ($result) {
			$objp = $this->db->fetch_object($result);
			$this->id = $objp->rowid;
			$this->fk_expedition = $objp->fk_expedition;
			$this->entrepot_id = $objp->fk_entrepot;
			$this->fk_origin_line = $objp->fk_origin_line;
			$this->qty = $objp->qty;
			$this->rang = $objp->rang;

			$this->db->free($result);

			return 1;
		} else {
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
	 *	@return     int						<0 if KO, line id >0 if OK
	 */
	public function insert($user, $notrigger = 0)
	{
		global $langs, $conf;

		$error = 0;

		// Check parameters
		if (empty($this->fk_expedition) || empty($this->fk_origin_line) || !is_numeric($this->qty)) {
			$this->error = 'ErrorMandatoryParametersNotProvided';
			return -1;
		}

		$this->db->begin();

		if (empty($this->rang)) {
			$this->rang = 0;
		}

		// Rank to use
		$ranktouse = $this->rang;
		if ($ranktouse == -1) {
			$rangmax = $this->line_max($this->fk_expedition);
			$ranktouse = $rangmax + 1;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."expeditiondet (";
		$sql .= "fk_expedition";
		$sql .= ", fk_entrepot";
		$sql .= ", fk_origin_line";
		$sql .= ", qty";
		$sql .= ", rang";
		$sql .= ") VALUES (";
		$sql .= $this->fk_expedition;
		$sql .= ", ".(empty($this->entrepot_id) ? 'NULL' : $this->entrepot_id);
		$sql .= ", ".((int) $this->fk_origin_line);
		$sql .= ", ".price2num($this->qty, 'MS');
		$sql .= ", ".((int) $ranktouse);
		$sql .= ")";

		dol_syslog(get_class($this)."::insert", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."expeditiondet");

			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINESHIPPING_INSERT', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if ($error) {
				foreach ($this->errors as $errmsg) {
					dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
			}
		} else {
			$error++;
		}

		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * 	Delete shipment line.
	 *
	 *	@param		User	$user			User that modify
	 *	@param		int		$notrigger		0=launch triggers after, 1=disable triggers
	 * 	@return		int		>0 if OK, <0 if KO
	 */
	public function delete($user = null, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		$this->db->begin();

		// delete batch expedition line
		if (isModEnabled('productbatch')) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."expeditiondet_batch";
			$sql .= " WHERE fk_expeditiondet = ".((int) $this->id);

			if (!$this->db->query($sql)) {
				$this->errors[] = $this->db->lasterror()." - sql=$sql";
				$error++;
			}
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."expeditiondet";
		$sql .= " WHERE rowid = ".((int) $this->id);

		if (!$error && $this->db->query($sql)) {
			// Remove extrafields
			if (!$error) {
				$result = $this->deleteExtraFields();
				if ($result < 0) {
					$this->errors[] = $this->error;
					$error++;
				}
			}
			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINESHIPPING_DELETE', $user);
				if ($result < 0) {
					$this->errors[] = $this->error;
					$error++;
				}
				// End call triggers
			}
		} else {
			$this->errors[] = $this->db->lasterror()." - sql=$sql";
			$error++;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
	}

	/**
	 *  Update a line in database
	 *
	 *	@param		User	$user			User that modify
	 *	@param		int		$notrigger		1 = disable triggers
	 *  @return		int					< 0 if KO, > 0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		dol_syslog(get_class($this)."::update id=$this->id, entrepot_id=$this->entrepot_id, product_id=$this->fk_product, qty=$this->qty");

		$this->db->begin();

		// Clean parameters
		if (empty($this->qty)) {
			$this->qty = 0;
		}
		$qty = price2num($this->qty);
		$remainingQty = 0;
		$batch = null;
		$batch_id = null;
		$expedition_batch_id = null;
		if (is_array($this->detail_batch)) { 	// array of ExpeditionLineBatch
			if (count($this->detail_batch) > 1) {
				dol_syslog(get_class($this).'::update only possible for one batch', LOG_ERR);
				$this->errors[] = 'ErrorBadParameters';
				$error++;
			} else {
				$batch = $this->detail_batch[0]->batch;
				$batch_id = $this->detail_batch[0]->fk_origin_stock;
				$expedition_batch_id = $this->detail_batch[0]->id;
				if ($this->entrepot_id != $this->detail_batch[0]->entrepot_id) {
					dol_syslog(get_class($this).'::update only possible for batch of same warehouse', LOG_ERR);
					$this->errors[] = 'ErrorBadParameters';
					$error++;
				}
				$qty = price2num($this->detail_batch[0]->qty);
			}
		} elseif (!empty($this->detail_batch)) {
			$batch = $this->detail_batch->batch;
			$batch_id = $this->detail_batch->fk_origin_stock;
			$expedition_batch_id = $this->detail_batch->id;
			if ($this->entrepot_id != $this->detail_batch->entrepot_id) {
				dol_syslog(get_class($this).'::update only possible for batch of same warehouse', LOG_ERR);
				$this->errors[] = 'ErrorBadParameters';
				$error++;
			}
			$qty = price2num($this->detail_batch->qty);
		}

		// check parameters
		if (!isset($this->id) || !isset($this->entrepot_id)) {
			dol_syslog(get_class($this).'::update missing line id and/or warehouse id', LOG_ERR);
			$this->errors[] = 'ErrorMandatoryParametersNotProvided';
			$error++;
			return -1;
		}

		// update lot

		if (!empty($batch) && $conf->productbatch->enabled) {
			dol_syslog(get_class($this)."::update expedition batch id=$expedition_batch_id, batch_id=$batch_id, batch=$batch");

			if (empty($batch_id) || empty($this->fk_product)) {
				dol_syslog(get_class($this).'::update missing fk_origin_stock (batch_id) and/or fk_product', LOG_ERR);
				$this->errors[] = 'ErrorMandatoryParametersNotProvided';
				$error++;
			}

			// fetch remaining lot qty
			$shipmentlinebatch = new ExpeditionLineBatch($this->db);

			if (!$error && ($lotArray = $shipmentlinebatch->fetchAll($this->id)) < 0) {
				$this->errors[] = $this->db->lasterror()." - ExpeditionLineBatch::fetchAll";
				$error++;
			} else {
				// caculate new total line qty
				foreach ($lotArray as $lot) {
					if ($expedition_batch_id != $lot->id) {
						$remainingQty += $lot->qty;
					}
				}
				$qty += $remainingQty;

				//fetch lot details

				// fetch from product_lot
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
				$lot = new Productlot($this->db);
				if ($lot->fetch(0, $this->fk_product, $batch) < 0) {
					$this->errors[] = $lot->errors;
					$error++;
				}
				if (!$error && !empty($expedition_batch_id)) {
					// delete lot expedition line
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."expeditiondet_batch";
					$sql .= " WHERE fk_expeditiondet = ".((int) $this->id);
					$sql .= " AND rowid = ".((int) $expedition_batch_id);

					if (!$this->db->query($sql)) {
						$this->errors[] = $this->db->lasterror()." - sql=$sql";
						$error++;
					}
				}
				if (!$error && $this->detail_batch->qty > 0) {
					// create lot expedition line
					if (isset($lot->id)) {
						$shipmentLot = new ExpeditionLineBatch($this->db);
						$shipmentLot->batch = $lot->batch;
						$shipmentLot->eatby = $lot->eatby;
						$shipmentLot->sellby = $lot->sellby;
						$shipmentLot->entrepot_id = $this->detail_batch->entrepot_id;
						$shipmentLot->qty = $this->detail_batch->qty;
						$shipmentLot->fk_origin_stock = $batch_id;
						if ($shipmentLot->create($this->id) < 0) {
							$this->errors[] = $shipmentLot->errors;
							$error++;
						}
					}
				}
			}
		}
		if (!$error) {
			// update line
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
			$sql .= " fk_entrepot = ".($this->entrepot_id > 0 ? $this->entrepot_id : 'null');
			$sql .= " , qty = ".((float) price2num($qty, 'MS'));
			$sql .= " WHERE rowid = ".((int) $this->id);

			if (!$this->db->query($sql)) {
				$this->errors[] = $this->db->lasterror()." - sql=$sql";
				$error++;
			}
		}

		if (!$error) {
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$this->errors[] = $this->error;
					$error++;
				}
			}
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('LINESHIPPING_MODIFY', $user);
			if ($result < 0) {
				$this->errors[] = $this->error;
				$error++;
			}
			// End call triggers
		}
		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
	}
}
