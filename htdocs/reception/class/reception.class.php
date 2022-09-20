<?php
/* Copyright (C) 2003-2008	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2007		Franky Van Liedekerke	<franky.van.liedekerke@telenet.be>
 * Copyright (C) 2006-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2017	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2014		Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2014-2020  Francis Appels          <francis.appels@yahoo.com>
 * Copyright (C) 2015       Claudio Aschieri        <c.aschieri@19.coop>
 * Copyright (C) 2016-2022	Ferran Marcet			<fmarcet@2byte.es>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/reception/class/reception.class.php
 *  \ingroup    reception
 *  \brief      Fichier de la classe de gestion des receptions
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


/**
 *	Class to manage receptions
 */
class Reception extends CommonObject
{
	use CommonIncoterm;

	/**
	 * @var string element name
	 */
	public $element = "reception";

	/**
	 * @var string Fieldname with ID of parent key if this field has a parent
	 */
	public $fk_element = "fk_reception";
	public $table_element = "reception";
	public $table_element_line = "commande_fournisseur_dispatch";
	public $ismultientitymanaged = 1; // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'dollyrevert';

	public $socid;
	public $ref_supplier;

	/**
	 * @var int		Ref int
	 * @deprecated
	 */
	public $ref_int;

	public $brouillon;
	public $entrepot_id;
	public $tracking_number;
	public $tracking_url;
	public $billed;
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

	public $date_delivery; // Date delivery planed


	/**
	 * @var integer|string Effective delivery date
	 */
	public $date_reception;

	/**
	 * @var integer|string date_creation
	 */
	public $date_creation;

	/**
	 * @var integer|string date_validation
	 */
	public $date_valid;

	public $meths;
	public $listmeths; // List of carriers

	public $lines = array();


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CLOSED = 2;



	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		// List of long language codes for status
		$this->statuts = array();
		$this->statuts[-1] = 'StatusReceptionCanceled';
		$this->statuts[0]  = 'StatusReceptionDraft';
		// product to receive if stock increase is on close or already received if stock increase is on validation
		$this->statuts[1]  = 'StatusReceptionValidated';
		if (getDolGlobalInt("STOCK_CALCULATE_ON_RECEPTION")) {
			$this->statuts[1]  = 'StatusReceptionValidatedReceived';
		}
		if (getDolGlobalInt("STOCK_CALCULATE_ON_RECEPTION_CLOSE")) {
			$this->statuts[1]  = 'StatusReceptionValidatedToReceive';
		}
		$this->statuts[2]  = 'StatusReceptionProcessed';

		// List of short language codes for status
		$this->statutshorts = array();
		$this->statutshorts[-1] = 'StatusReceptionCanceledShort';
		$this->statutshorts[0]  = 'StatusReceptionDraftShort';
		$this->statutshorts[1]  = 'StatusReceptionValidatedShort';
		$this->statutshorts[2]  = 'StatusReceptionProcessedShort';
	}

	/**
	 *	Return next contract ref
	 *
	 *	@param	Societe		$soc	Thirdparty object
	 *	@return string				Free reference for contract
	 */
	public function getNextNumRef($soc)
	{
		global $langs, $conf;
		$langs->load("receptions");

		if (!empty($conf->global->RECEPTION_ADDON_NUMBER)) {
			$mybool = false;

			$file = $conf->global->RECEPTION_ADDON_NUMBER.".php";
			$classname = $conf->global->RECEPTION_ADDON_NUMBER;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/reception/");

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
		if (empty($this->weight_units)) {
			$this->weight_units = 0;
		}
		if (empty($this->size_units)) {
			$this->size_units = 0;
		}

		$this->user = $user;

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."reception (";
		$sql .= "ref";
		$sql .= ", entity";
		$sql .= ", ref_supplier";
		$sql .= ", date_creation";
		$sql .= ", fk_user_author";
		$sql .= ", date_reception";
		$sql .= ", date_delivery";
		$sql .= ", fk_soc";
		$sql .= ", fk_projet";
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
		$sql .= ", ".($this->ref_supplier ? "'".$this->db->escape($this->ref_supplier)."'" : "null");
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", ".((int) $user->id);
		$sql .= ", ".($this->date_reception > 0 ? "'".$this->db->idate($this->date_reception)."'" : "null");
		$sql .= ", ".($this->date_delivery > 0 ? "'".$this->db->idate($this->date_delivery)."'" : "null");
		$sql .= ", ".((int) $this->socid);
		$sql .= ", ".((int) $this->fk_project);
		$sql .= ", ".($this->shipping_method_id > 0 ? ((int) $this->shipping_method_id) : "null");
		$sql .= ", '".$this->db->escape($this->tracking_number)."'";
		$sql .= ", ".(is_null($this->weight) ? "NULL" : ((double) $this->weight));
		$sql .= ", ".(is_null($this->trueDepth) ? "NULL" : ((double) $this->trueDepth));
		$sql .= ", ".(is_null($this->trueWidth) ? "NULL" : ((double) $this->trueWidth));
		$sql .= ", ".(is_null($this->trueHeight) ? "NULL" : ((double) $this->trueHeight));
		$sql .= ", ".(is_null($this->weight_units) ? "NULL" : ((double) $this->weight_units));
		$sql .= ", ".(is_null($this->size_units) ? "NULL" : ((double) $this->size_units));
		$sql .= ", ".(!empty($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null");
		$sql .= ", ".(!empty($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null");
		$sql .= ", ".(!empty($this->model_pdf) ? "'".$this->db->escape($this->model_pdf)."'" : "null");
		$sql .= ", ".(int) $this->fk_incoterms;
		$sql .= ", '".$this->db->escape($this->location_incoterms)."'";
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);

		$resql = $this->db->query($sql);

		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."reception");

			$sql = "UPDATE ".MAIN_DB_PREFIX."reception";
			$sql .= " SET ref = '(PROV".$this->id.")'";
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			if ($this->db->query($sql)) {
				// Insert of lines
				$num = count($this->lines);
				for ($i = 0; $i < $num; $i++) {
					$this->lines[$i]->fk_reception = $this->id;

					if (!$this->lines[$i]->create($user) > 0) {
						$error++;
					}
				}

				if (!$error && $this->id && $this->origin_id) {
					$ret = $this->add_object_linked();
					if (!$ret) {
						$error++;
					}
				}

				// Create extrafields
				if (!$error) {
					$result = $this->insertExtraFields();
					if ($result < 0) {
						$error++;
					}
				}

				if (!$error && !$notrigger) {
					// Call trigger
					$result = $this->call_trigger('RECEPTION_CREATE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

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

		$sql = "SELECT e.rowid, e.ref, e.fk_soc as socid, e.date_creation, e.ref_supplier, e.ref_ext, e.fk_user_author, e.fk_statut";
		$sql .= ", e.weight, e.weight_units, e.size, e.size_units, e.width, e.height";
		$sql .= ", e.date_reception as date_reception, e.model_pdf,  e.date_delivery";
		$sql .= ", e.fk_shipping_method, e.tracking_number";
		$sql .= ", el.fk_source as origin_id, el.sourcetype as origin";
		$sql .= ", e.note_private, e.note_public";
		$sql .= ', e.fk_incoterms, e.location_incoterms';
		$sql .= ', i.libelle as label_incoterms';
		$sql .= " FROM ".MAIN_DB_PREFIX."reception as e";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_target = e.rowid AND el.targettype = '".$this->db->escape($this->element)."'";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON e.fk_incoterms = i.rowid';
		$sql .= " WHERE e.entity IN (".getEntity('reception').")";
		if ($id) {
			$sql .= " AND e.rowid=".((int) $id);
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
				$this->ref                  = $obj->ref;
				$this->socid                = $obj->socid;
				$this->ref_supplier = $obj->ref_supplier;
				$this->ref_ext = $obj->ref_ext;
				$this->statut               = $obj->fk_statut;
				$this->user_author_id       = $obj->fk_user_author;
				$this->date_creation        = $this->db->jdate($obj->date_creation);
				$this->date                 = $this->db->jdate($obj->date_reception); // TODO deprecated
				$this->date_reception = $this->db->jdate($obj->date_reception); // TODO deprecated
				$this->date_reception = $this->db->jdate($obj->date_reception); // Date real
				$this->date_delivery        = $this->db->jdate($obj->date_delivery); // Date planed
				$this->model_pdf            = $obj->model_pdf;
				$this->modelpdf             = $obj->model_pdf; // deprecated
				$this->shipping_method_id = $obj->fk_shipping_method;
				$this->tracking_number      = $obj->tracking_number;
				$this->origin               = ($obj->origin ? $obj->origin : 'commande'); // For compatibility
				$this->origin_id            = $obj->origin_id;
				$this->billed = ($obj->fk_statut == 2 ? 1 : 0);

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
				$this->trueSize = $obj->size."x".$obj->width."x".$obj->height;
				$this->size_units = $obj->size_units;

				//Incoterms
				$this->fk_incoterms = $obj->fk_incoterms;
				$this->location_incoterms = $obj->location_incoterms;
				$this->label_incoterms = $obj->label_incoterms;

				$this->db->free($result);

				if ($this->statut == 0) {
					$this->brouillon = 1;
				}

				//$file = $conf->reception->dir_output."/".get_exdir(0, 0, 0, 1, $this, 'reception')."/".$this->id.".pdf";
				//$this->pdf_filename = $file;

				// Tracking url
				$this->getUrlTrackingStatus($obj->tracking_number);

				/*
				 * Thirdparty
				 */
				$result = $this->fetch_thirdparty();


				// Retrieve all extrafields for reception
				// fetch optionals attributes and labels
				require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
				$extrafields = new ExtraFields($this->db);
				$extrafields->fetch_name_optionals_label($this->table_element, true);
				$this->fetch_optionals();

				/*
				 * Lines
				 */
				$result = $this->fetch_lines();
				if ($result < 0) {
					return -3;
				}

				return 1;
			} else {
				dol_syslog(get_class($this).'::Fetch no reception found', LOG_ERR);
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
			dol_syslog(get_class($this)."::valid no draft status", LOG_WARNING);
			return 0;
		}

		if (!((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->reception->creer))
		|| (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->reception->reception_advance->validate)))) {
			$this->error = 'Permission denied';
			dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
			return -1;
		}

		$this->db->begin();

		$error = 0;

		// Define new ref
		$soc = new Societe($this->db);
		$soc->fetch($this->socid);


		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
			$numref = $this->getNextNumRef($soc);
		} else {
			$numref = $this->ref;
		}

		$this->newref = dol_sanitizeFileName($numref);

		$now = dol_now();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX."reception SET";
		$sql .= " ref='".$this->db->escape($numref)."'";
		$sql .= ", fk_statut = 1";
		$sql .= ", date_valid = '".$this->db->idate($now)."'";
		$sql .= ", fk_user_valid = ".$user->id;
		$sql .= " WHERE rowid = ".((int) $this->id);
		dol_syslog(get_class($this)."::valid update reception", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$error++;
		}

		// If stock increment is done on reception (recommanded choice)
		if (!$error && !empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION)) {
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

			$langs->load("agenda");

			// Loop on each product line to add a stock movement
			// TODO in future, reception lines may not be linked to order line
			$sql = "SELECT cd.fk_product, cd.subprice, cd.remise_percent,";
			$sql .= " ed.rowid, ed.qty, ed.fk_entrepot,";
			$sql .= " ed.eatby, ed.sellby, ed.batch,";
			$sql .= " ed.cost_price";
			$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd,";
			$sql .= " ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as ed";
			$sql .= " WHERE ed.fk_reception = ".((int) $this->id);
			$sql .= " AND cd.rowid = ed.fk_commandefourndet";

			dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$cpt = $this->db->num_rows($resql);
				for ($i = 0; $i < $cpt; $i++) {
					$obj = $this->db->fetch_object($resql);

					$qty = $obj->qty;

					if ($qty <= 0) {
						continue;
					}
					dol_syslog(get_class($this)."::valid movement index ".$i." ed.rowid=".$obj->rowid." edb.rowid=".$obj->edbrowid);

					//var_dump($this->lines[$i]);
					$mouvS = new MouvementStock($this->db);
					$mouvS->origin = &$this;
					$mouvS->setOrigin($this->element, $this->id);

					if (empty($obj->batch)) {
						// line without batch detail

						// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record.
						$inventorycode = '';
						$result = $mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->cost_price, $langs->trans("ReceptionValidatedInDolibarr", $numref), '', '', '', '', 0, $inventorycode);

						if (intval($result) < 0) {
							$error++;
							$this->errors[] = $mouvS->error;
							$this->errors = array_merge($this->errors, $mouvS->errors);
							break;
						}
					} else {
						// line with batch detail

						// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record.
						// Note: ->fk_origin_stock = id into table llx_product_batch (may be rename into llx_product_stock_batch in another version)
						$inventorycode = '';
						$result = $mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->cost_price, $langs->trans("ReceptionValidatedInDolibarr", $numref), $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch, '', 0, $inventorycode);

						if (intval($result) < 0) {
							$error++;
							$this->errors[] = $mouvS->error;
							$this->errors = array_merge($this->errors, $mouvS->errors);
							break;
						}
					}
				}
			} else {
				$this->db->rollback();
				$this->error = $this->db->error();
				return -2;
			}
		}

		// Change status of order to "reception in process" or "totally received"
		$status = $this->getStatusDispatch();
		if ($status < 0) {
			$error++;
		} else {
			$trigger_key = '';
			if ($status == CommandeFournisseur::STATUS_RECEIVED_COMPLETELY) {
				$ret = $this->commandeFournisseur->Livraison($user, dol_now(), 'tot', '');
				if ($ret < 0) {
					$error++;
					$this->errors = array_merge($this->errors, $this->commandeFournisseur->errors);
				}
			} else {
				$ret = $this->setStatut($status, $this->origin_id, 'commande_fournisseur', $trigger_key);
				if ($ret < 0) {
					$error++;
				}
			}
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('RECEPTION_VALIDATE', $user);
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'reception/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'reception/".$this->db->escape($this->ref)."' AND entity = ".((int) $conf->entity);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($numref);
				$dirsource = $conf->reception->dir_output.'/'.$oldref;
				$dirdest = $conf->reception->dir_output.'/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::valid rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->reception->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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
			$this->statut = 1;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::valid ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
	}

	/**
	 * Get status from all dispatched lines
	 *
	 * @return		int		                        <0 if KO, Status of reception if OK
	 */
	public function getStatusDispatch()
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';

		$status = CommandeFournisseur::STATUS_RECEIVED_PARTIALLY;

		if (!empty($this->origin) && $this->origin_id > 0 && ($this->origin == 'order_supplier' || $this->origin == 'commandeFournisseur')) {
			if (empty($this->commandeFournisseur)) {
				$this->fetch_origin();
				if (empty($this->commandeFournisseur->lines)) {
					$res = $this->commandeFournisseur->fetch_lines();
					if ($res < 0)	return $res;
				}
			}

			$qty_received = array();
			$qty_wished = array();

			$supplierorderdispatch = new CommandeFournisseurDispatch($this->db);
			$filter = array('t.fk_commande'=>$this->origin_id);
			if (!empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS)) {
				$filter['t.status'] = 1; // Restrict to lines with status validated
			}

			$ret = $supplierorderdispatch->fetchAll('', '', 0, 0, $filter);
			if ($ret < 0) {
				$this->error = $supplierorderdispatch->error;
				$this->errors = $supplierorderdispatch->errors;
				return $ret;
			} else {
				// build array with quantity received by product in all supplier orders (origin)
				foreach ($supplierorderdispatch->lines as $dispatch_line) {
					$qty_received[$dispatch_line->fk_product] += $dispatch_line->qty;
				}

				// qty wished in order supplier (origin)
				foreach ($this->commandeFournisseur->lines as $origin_line) {
					// exclude lines not qualified for reception
					if (empty($conf->global->STOCK_SUPPORTS_SERVICES) && $origin_line->product_type > 0) {
						continue;
					}

					$qty_wished[$origin_line->fk_product] += $origin_line->qty;
				}

				// compare array
				$diff_array = array_diff_assoc($qty_received, $qty_wished); // Warning: $diff_array is done only on common keys.
				$keys_in_wished_not_in_received = array_diff(array_keys($qty_wished), array_keys($qty_received));
				$keys_in_received_not_in_wished = array_diff(array_keys($qty_received), array_keys($qty_wished));

				if (count($diff_array) == 0 && count($keys_in_wished_not_in_received) == 0 && count($keys_in_received_not_in_wished) == 0) { // no diff => mean everything is received
					$status = CommandeFournisseur::STATUS_RECEIVED_COMPLETELY;
				} elseif (!empty($conf->global->SUPPLIER_ORDER_MORE_THAN_WISHED)) {
					// set totally received if more products received than ordered
					$close = 0;

					if (count($diff_array) > 0) {
						// there are some difference between the two arrays
						// scan the array of results
						foreach ($diff_array as $key => $value) {
							// if the quantity delivered is greater or equal to ordered quantity
							if ($qty_received[$key] >= $qty_wished[$key]) {
								$close++;
							}
						}
					}

					if ($close == count($diff_array)) {
						// all the products are received equal or more than the ordered quantity
						$status = CommandeFournisseur::STATUS_RECEIVED_COMPLETELY;
					}
				}
			}
		}

		return $status;
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
	 * @param	integer		$eatby					eat-by date
	 * @param	integer		$sellby					sell-by date
	 * @param	string		$batch					Lot number
	 * @param	double		$cost_price			Line cost
	 * @return	int							<0 if KO, index of line if OK
	 */
	public function addline($entrepot_id, $id, $qty, $array_options = 0, $comment = '', $eatby = '', $sellby = '', $batch = '', $cost_price = 0)
	{
		global $conf, $langs, $user;

		$num = count($this->lines);
		$line = new CommandeFournisseurDispatch($this->db);

		$line->fk_entrepot = $entrepot_id;
		$line->fk_commandefourndet = $id;
		$line->qty = $qty;

		$supplierorderline = new CommandeFournisseurLigne($this->db);
		$result = $supplierorderline->fetch($id);
		if ($result <= 0) {
			$this->error = $supplierorderline->error;
			$this->errors = $supplierorderline->errors;
			return -1;
		}

		$fk_product = 0;
		if (!empty($conf->stock->enabled) && !empty($supplierorderline->fk_product)) {
			$fk_product = $supplierorderline->fk_product;

			if (!($entrepot_id > 0) && empty($conf->global->STOCK_WAREHOUSE_NOT_REQUIRED_FOR_RECEPTIONS)) {
				$langs->load("errors");
				$this->error = $langs->trans("ErrorWarehouseRequiredIntoReceptionLine");
				return -1;
			}
		}

		// Check batch is set
		$product = new Product($this->db);
		$product->fetch($fk_product);
		if (!empty($conf->productbatch->enabled)) {
			$langs->load("errors");
			if (!empty($product->status_batch) && empty($batch)) {
				$this->error = $langs->trans('ErrorProductNeedBatchNumber', $product->ref);
				return -1;
			} elseif (empty($product->status_batch) && !empty($batch)) {
				$this->error = $langs->trans('ErrorProductDoesNotNeedBatchNumber', $product->ref);
				return -1;
			}
		}
		unset($product);

		// extrafields
		$line->array_options = $supplierorderline->array_options;
		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($array_options) && count($array_options) > 0) {
			foreach ($array_options as $key => $value) {
				$line->array_options[$key] = $value;
			}
		}

		$line->fk_product = $fk_product;
		$line->fk_commande = $supplierorderline->fk_commande;
		$line->fk_user = $user->id;
		$line->comment = $comment;
		$line->batch = $batch;
		$line->eatby = $eatby;
		$line->sellby = $sellby;
		$line->status = 1;
		$line->cost_price = $cost_price;
		$line->fk_reception = $this->id;

		$this->lines[$num] = $line;

		return $num;
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
			$this->entity = trim($this->entity);
		}
		if (isset($this->ref_supplier)) {
			$this->ref_supplier = trim($this->ref_supplier);
		}
		if (isset($this->socid)) {
			$this->socid = trim($this->socid);
		}
		if (isset($this->fk_user_author)) {
			$this->fk_user_author = trim($this->fk_user_author);
		}
		if (isset($this->fk_user_valid)) {
			$this->fk_user_valid = trim($this->fk_user_valid);
		}
		if (isset($this->shipping_method_id)) {
			$this->shipping_method_id = trim($this->shipping_method_id);
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
		$sql = "UPDATE ".MAIN_DB_PREFIX."reception SET";

		$sql .= " ref=".(isset($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null").",";
		$sql .= " ref_supplier=".(isset($this->ref_supplier) ? "'".$this->db->escape($this->ref_supplier)."'" : "null").",";
		$sql .= " fk_soc=".(isset($this->socid) ? $this->socid : "null").",";
		$sql .= " date_creation=".(dol_strlen($this->date_creation) != 0 ? "'".$this->db->idate($this->date_creation)."'" : 'null').",";
		$sql .= " fk_user_author=".(isset($this->fk_user_author) ? $this->fk_user_author : "null").",";
		$sql .= " date_valid=".(dol_strlen($this->date_valid) != 0 ? "'".$this->db->idate($this->date_valid)."'" : 'null').",";
		$sql .= " fk_user_valid=".(isset($this->fk_user_valid) ? $this->fk_user_valid : "null").",";
		$sql .= " date_reception=".(dol_strlen($this->date_reception) != 0 ? "'".$this->db->idate($this->date_reception)."'" : 'null').",";
		$sql .= " date_delivery=".(dol_strlen($this->date_delivery) != 0 ? "'".$this->db->idate($this->date_delivery)."'" : 'null').",";
		$sql .= " fk_shipping_method=".((isset($this->shipping_method_id) && $this->shipping_method_id > 0) ? $this->shipping_method_id : "null").",";
		$sql .= " tracking_number=".(isset($this->tracking_number) ? "'".$this->db->escape($this->tracking_number)."'" : "null").",";
		$sql .= " fk_statut=".(isset($this->statut) ? $this->statut : "null").",";
		$sql .= " height=".(($this->trueHeight != '') ? $this->trueHeight : "null").",";
		$sql .= " width=".(($this->trueWidth != '') ? $this->trueWidth : "null").",";
		$sql .= " size_units=".(isset($this->size_units) ? $this->size_units : "null").",";
		$sql .= " size=".(($this->trueDepth != '') ? $this->trueDepth : "null").",";
		$sql .= " weight_units=".(isset($this->weight_units) ? $this->weight_units : "null").",";
		$sql .= " weight=".(($this->trueWeight != '') ? $this->trueWeight : "null").",";
		$sql .= " note_private=".(isset($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null").",";
		$sql .= " note_public=".(isset($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null").",";
		$sql .= " model_pdf=".(isset($this->model_pdf) ? "'".$this->db->escape($this->model_pdf)."'" : "null").",";
		$sql .= " entity = ".((int) $conf->entity);
		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++; $this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('RECEPTION_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
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
	 * 	Delete reception.
	 *
	 *	@param	User	$user	Object user
	 * 	@return	int				>0 if OK, 0 if deletion done but failed to delete files, <0 if KO
	 */
	public function delete(User $user)
	{
		global $conf, $langs, $user;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;
		$this->error = '';


		$this->db->begin();

		// Stock control
		if ($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_RECEPTION && $this->statut > 0) {
			require_once DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php";

			$langs->load("agenda");

			// Loop on each product line to add a stock movement
			$sql = "SELECT cd.fk_product, cd.subprice, ed.qty, ed.fk_entrepot, ed.eatby, ed.sellby, ed.batch, ed.rowid as commande_fournisseur_dispatch_id";
			$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd,";
			$sql .= " ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as ed";
			$sql .= " WHERE ed.fk_reception = ".((int) $this->id);
			$sql .= " AND cd.rowid = ed.fk_commandefourndet";

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

					$result = $mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $obj->qty, 0, $langs->trans("ReceptionDeletedInDolibarr", $this->ref), '', $obj->eatby, $obj->sellby, $obj->batch); // Price is set to 0, because we don't want to see WAP changed
				}
			} else {
				$error++; $this->errors[] = "Error ".$this->db->lasterror();
			}
		}

		if (!$error) {
			$main = MAIN_DB_PREFIX.'commande_fournisseur_dispatch';
			$ef = $main."_extrafields";

			$sqlef = "DELETE FROM ".$ef." WHERE fk_object IN (SELECT rowid FROM ".$main." WHERE fk_reception = ".((int) $this->id).")";

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch";
			$sql .= " WHERE fk_reception = ".((int) $this->id);

			if ($this->db->query($sqlef) && $this->db->query($sql)) {
				// Delete linked object
				$res = $this->deleteObjectLinked();
				if ($res < 0) {
					$error++;
				}

				if (!$error) {
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."reception";
					$sql .= " WHERE rowid = ".((int) $this->id);

					if ($this->db->query($sql)) {
						// Call trigger
						$result = $this->call_trigger('RECEPTION_DELETE', $user);
						if ($result < 0) {
							$error++;
						}
						// End call triggers

						if (!empty($this->origin) && $this->origin_id > 0) {
							$this->fetch_origin();
							$origin = $this->origin;
							if ($this->$origin->statut == 4) {     // If order source of reception is "partially received"
								// Check if there is no more reception. If not, we can move back status of order to "validated" instead of "reception in progress"
								$this->$origin->loadReceptions();
								//var_dump($this->$origin->receptions);exit;
								if (count($this->$origin->receptions) <= 0) {
									$this->$origin->setStatut(3); // ordered
								}
							}
						}

						if (!$error) {
							$this->db->commit();

							// We delete PDFs
							$ref = dol_sanitizeFileName($this->ref);
							if (!empty($conf->reception->dir_output)) {
								$dir = $conf->reception->dir_output.'/'.$ref;
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
		$this->lines = array();

		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch WHERE fk_reception = ".((int) $this->id);
		$resql = $this->db->query($sql);

		if (!empty($resql)) {
			while ($obj = $this->db->fetch_object($resql)) {
				$line = new CommandeFournisseurDispatch($this->db);

				$line->fetch($obj->rowid);

				// TODO Remove or keep this ?
				$line->fetch_product();

				$sql_commfourndet = 'SELECT qty, ref,  label, description, tva_tx, vat_src_code, subprice, multicurrency_subprice, remise_percent';
				$sql_commfourndet .= ' FROM '.MAIN_DB_PREFIX.'commande_fournisseurdet';
				$sql_commfourndet .= ' WHERE rowid = '.((int) $line->fk_commandefourndet);
				$sql_commfourndet .= ' ORDER BY rang';

				$resql_commfourndet = $this->db->query($sql_commfourndet);
				if (!empty($resql_commfourndet)) {
					$obj = $this->db->fetch_object($resql_commfourndet);
					$line->qty_asked = $obj->qty;
					$line->description = $obj->description;
					$line->desc = $obj->description;
					$line->tva_tx = $obj->tva_tx;
					$line->vat_src_code = $obj->vat_src_code;
					$line->subprice = $obj->subprice;
					$line->multicurrency_subprice = $obj->multicurrency_subprice;
					$line->remise_percent = $obj->remise_percent;
					$line->label = !empty($obj->label) ? $obj->label : $line->product->label;
					$line->ref_supplier = $obj->ref;
				} else {
					$line->qty_asked = 0;
					$line->description = '';
					$line->desc = '';
					$line->label = $obj->label;
				}

				$pu_ht = ($line->subprice * $line->qty) * (100 - $line->remise_percent) / 100;
				$tva = $pu_ht * $line->tva_tx / 100;
				$this->total_ht += $pu_ht;
				$this->total_tva += $pu_ht * $line->tva_tx / 100;

				$this->total_ttc += $pu_ht + $tva;


				$this->lines[] = $line;
			}

			return 1;
		} else {
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
	public function getNomUrl($withpicto = 0, $option = 0, $max = 0, $short = 0, $notooltip = 0)
	{
		global $conf, $langs, $hookmanager;
		$result = '';
		$label = img_picto('', $this->picto).' <u>'.$langs->trans("Reception").'</u>';
		$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		$label .= '<br><b>'.$langs->trans('RefSupplier').':</b> '.($this->ref_supplier ? $this->ref_supplier : '');

		$url = DOL_URL_ROOT.'/reception/card.php?id='.$this->id;

		if ($short) {
			return $url;
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("Reception");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip"';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		if ($withpicto) {
			$result .= ($linkstart.img_object(($notooltip ? '' : $label), $this->picto, ($notooltip ? '' : 'class="classfortooltip"'), 0, 0, $notooltip ? 0 : 1).$linkend);
		}
		if ($withpicto && $withpicto != 2) {
			$result .= ' ';
		}
		$result .= $linkstart.$this->ref.$linkend;

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
	 * @param      int		$status		Id status
	 * @param      int		$mode       0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto
	 * @return     string				Label of status
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

		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';
		$now = dol_now();

		dol_syslog(get_class($this)."::initAsSpecimen");

		// Load array of products prodids
		$num_prods = 0;
		$prodids = array();
		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."product";
		$sql .= " WHERE entity IN (".getEntity('product').")";
		$sql .= $this->db->plimit(100);

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

		$order = new CommandeFournisseur($this->db);
		$order->initAsSpecimen();

		// Initialise parametres
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;
		$this->statut               = 1;
		$this->livraison_id         = 0;
		$this->date                 = $now;
		$this->date_creation        = $now;
		$this->date_valid           = $now;
		$this->date_delivery        = $now;
		$this->date_reception = $now + 24 * 3600;

		$this->entrepot_id          = 0;
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
			$line = new CommandeFournisseurDispatch($this->db);
			$line->desc = $langs->trans("Description")." ".$xnbp;
			$line->libelle = $langs->trans("Description")." ".$xnbp;
			$line->qty = 10;

			$line->fk_product = $this->commande->lines[$xnbp]->fk_product;

			$this->lines[] = $line;
			$xnbp++;
		}
	}

	/**
	 *	Set the planned delivery date
	 *
	 *	@param      User			$user        		Objet utilisateur qui modifie
	 *	@param      integer 		$delivery_date     Delivery date
	 *	@return     int         						<0 if KO, >0 if OK
	 */
	public function setDeliveryDate($user, $delivery_date)
	{
		// phpcs:enable
		if ($user->rights->reception->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."reception";
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

		$sql = "SELECT em.rowid, em.code, em.libelle";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
		$sql .= " WHERE em.active = 1";
		$sql .= " ORDER BY em.libelle ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$label = $langs->trans('ReceptionMethod'.$obj->code);
				$this->meths[$obj->rowid] = ($label != 'ReceptionMethod'.$obj->code ? $label : $obj->libelle);
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

		$sql = "SELECT em.rowid, em.code, em.libelle, em.description, em.tracking, em.active";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
		if ($id != '') {
			$sql .= " WHERE em.rowid = ".((int) $id);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->listmeths[$i]['rowid'] = $obj->rowid;
				$this->listmeths[$i]['code'] = $obj->code;
				$label = $langs->trans('ReceptionMethod'.$obj->code);
				$this->listmeths[$i]['libelle'] = ($label != 'ReceptionMethod'.$obj->code ? $label : $obj->libelle);
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
	 *	Classify the reception as closed (this record also the stock movement)
	 *
	 *	@return     int     <0 if KO, >0 if OK
	 */
	public function setClosed()
	{
		global $conf, $langs, $user;

		$error = 0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'reception SET fk_statut='.self::STATUS_CLOSED;
		$sql .= " WHERE rowid = ".((int) $this->id).' AND fk_statut > 0';

		$resql = $this->db->query($sql);
		if ($resql) {
			// Set order billed if 100% of order is received (qty in reception lines match qty in order lines)
			if ($this->origin == 'order_supplier' && $this->origin_id > 0) {
				$order = new CommandeFournisseur($this->db);
				$order->fetch($this->origin_id);

				$order->loadReceptions(self::STATUS_CLOSED); // Fill $order->receptions = array(orderlineid => qty)

				$receptions_match_order = 1;
				foreach ($order->lines as $line) {
					$lineid = $line->id;
					$qty = $line->qty;
					if (($line->product_type == 0 || !empty($conf->global->STOCK_SUPPORTS_SERVICES)) && $order->receptions[$lineid] < $qty) {
						$receptions_match_order = 0;
						$text = 'Qty for order line id '.$lineid.' is '.$qty.'. However in the receptions with status Reception::STATUS_CLOSED='.self::STATUS_CLOSED.' we have qty = '.$order->receptions[$lineid].', so we can t close order';
						dol_syslog($text);
						break;
					}
				}
				if ($receptions_match_order) {
					dol_syslog("Qty for the ".count($order->lines)." lines of order have same value for receptions with status Reception::STATUS_CLOSED=".self::STATUS_CLOSED.', so we close order');
					$order->Livraison($user, dol_now(), 'tot', 'Reception '.$this->ref);
				}
			}

			$this->statut = self::STATUS_CLOSED;


			// If stock increment is done on closing
			if (!$error && !empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION_CLOSE)) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

				$langs->load("agenda");

				// Loop on each product line to add a stock movement
				// TODO possibilite de receptionner a partir d'une propale ou autre origine ?
				$sql = "SELECT cd.fk_product, cd.subprice,";
				$sql .= " ed.rowid, ed.qty, ed.fk_entrepot,";
				$sql .= " ed.eatby, ed.sellby, ed.batch,";
				$sql .= " ed.cost_price";
				$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd,";
				$sql .= " ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as ed";
				$sql .= " WHERE ed.fk_reception = ".((int) $this->id);
				$sql .= " AND cd.rowid = ed.fk_commandefourndet";

				dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
				$resql = $this->db->query($sql);

				if ($resql) {
					$cpt = $this->db->num_rows($resql);
					for ($i = 0; $i < $cpt; $i++) {
						$obj = $this->db->fetch_object($resql);

						$qty = $obj->qty;

						if ($qty <= 0) {
							continue;
						}
						dol_syslog(get_class($this)."::valid movement index ".$i." ed.rowid=".$obj->rowid." edb.rowid=".$obj->edbrowid);

						$mouvS = new MouvementStock($this->db);
						$mouvS->origin = &$this;
						$mouvS->setOrigin($this->element, $this->id);

						if (empty($obj->batch)) {
							// line without batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$inventorycode = '';
							$result = $mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->cost_price, $langs->trans("ReceptionClassifyClosedInDolibarr", $this->ref), '', '', '', '', 0, $inventorycode);
							if ($result < 0) {
								$this->error = $mouvS->error;
								$this->errors = $mouvS->errors;
								$error++; break;
							}
						} else {
							// line with batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$inventorycode = '';
							$result = $mouvS->reception($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->cost_price, $langs->trans("ReceptionClassifyClosedInDolibarr", $this->ref), $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch, '', 0, $inventorycode);

							if ($result < 0) {
								$this->error = $mouvS->error;
								$this->errors = $mouvS->errors;
								$error++; break;
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
				$result = $this->call_trigger('RECEPTION_CLOSED', $user);
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
			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Classify the reception as invoiced (used when WORKFLOW_BILL_ON_RECEPTION is on)
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
	 *	Classify the reception as invoiced (used when WORKFLOW_BILL_ON_RECEPTION is on)
	 *
	 *	@return     int     <0 if ko, >0 if ok
	 */
	public function setBilled()
	{
		global $user;
		$error = 0;

		$this->db->begin();

		$this->setClosed();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'reception SET  billed=1';
		$sql .= " WHERE rowid = ".((int) $this->id).' AND fk_statut > 0';

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->statut = 2;
			$this->billed = 1;

			// Call trigger
			$result = $this->call_trigger('RECEPTION_BILLED', $user);
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
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Classify the reception as validated/opened
	 *
	 *	@return     int     <0 if ko, >0 if ok
	 */
	public function reOpen()
	{
		global $conf, $langs, $user;

		$error = 0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'reception SET fk_statut=1, billed=0';
		$sql .= " WHERE rowid = ".((int) $this->id).' AND fk_statut > 0';

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->statut = 1;
			$this->billed = 0;

			// If stock increment is done on closing
			if (!$error && !empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION_CLOSE)) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$numref = $this->ref;
				$langs->load("agenda");

				// Loop on each product line to add a stock movement
				// TODO possibilite de receptionner a partir d'une propale ou autre origine
				$sql = "SELECT ed.fk_product, cd.subprice,";
				$sql .= " ed.rowid, ed.qty, ed.fk_entrepot,";
				$sql .= " ed.eatby, ed.sellby, ed.batch,";
				$sql .= " ed.cost_price";
				$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd,";
				$sql .= " ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as ed";
				$sql .= " WHERE ed.fk_reception = ".((int) $this->id);
				$sql .= " AND cd.rowid = ed.fk_commandefourndet";

				dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$cpt = $this->db->num_rows($resql);
					for ($i = 0; $i < $cpt; $i++) {
						$obj = $this->db->fetch_object($resql);

						$qty = $obj->qty;

						if ($qty <= 0) {
							continue;
						}

						dol_syslog(get_class($this)."::reopen reception movement index ".$i." ed.rowid=".$obj->rowid);

						//var_dump($this->lines[$i]);
						$mouvS = new MouvementStock($this->db);
						$mouvS->origin = &$this;
						$mouvS->setOrigin($this->element, $this->id);

						if (empty($obj->batch)) {
							// line without batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$inventorycode = '';
							$result = $mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->cost_price, $langs->trans("ReceptionUnClassifyCloseddInDolibarr", $numref), '', '', '', '', 0, $inventorycode);

							if ($result < 0) {
								$this->error = $mouvS->error;
								$this->errors = $mouvS->errors;
								$error++; break;
							}
						} else {
							// line with batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$inventorycode = '';
							$result = $mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->cost_price, $langs->trans("ReceptionUnClassifyCloseddInDolibarr", $numref), $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch, '', $obj->fk_origin_stock, $inventorycode);
							if ($result < 0) {
								$this->error = $mouvS->error;
								$this->errors = $mouvS->errors;
								$error++; break;
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
				$result = $this->call_trigger('RECEPTION_REOPEN', $user);
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && $this->origin == 'order_supplier') {
				$commande = new CommandeFournisseur($this->db);
				$commande->fetch($this->origin_id);
				$result = $commande->setStatus($user, 4);
				if ($result < 0) {
					$error++;
					$this->error = $commande->error;
					$this->errors = $commande->errors;
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
	public function setDraft($user)
	{
		// phpcs:enable
		global $conf, $langs;

		$error = 0;

		// Protection
		if ($this->statut <= self::STATUS_DRAFT) {
			return 0;
		}

		if (!((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->reception->creer))
		|| (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->reception->reception_advance->validate)))) {
			$this->error = 'Permission denied';
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."reception";
		$sql .= " SET fk_statut = ".self::STATUS_DRAFT;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(__METHOD__, LOG_DEBUG);
		if ($this->db->query($sql)) {
			// If stock increment is done on closing
			if (!$error && !empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION)) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

				$langs->load("agenda");

				// Loop on each product line to add a stock movement
				// TODO possibilite de receptionner a partir d'une propale ou autre origine
				$sql = "SELECT cd.fk_product, cd.subprice,";
				$sql .= " ed.rowid, ed.qty, ed.fk_entrepot,";
				$sql .= " ed.eatby, ed.sellby, ed.batch,";
				$sql .= " ed.cost_price";
				$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd,";
				$sql .= " ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as ed";
				$sql .= " WHERE ed.fk_reception = ".((int) $this->id);
				$sql .= " AND cd.rowid = ed.fk_commandefourndet";

				dol_syslog(get_class($this)."::valid select details", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$cpt = $this->db->num_rows($resql);
					for ($i = 0; $i < $cpt; $i++) {
						$obj = $this->db->fetch_object($resql);

						$qty = $obj->qty;


						if ($qty <= 0) {
							continue;
						}
						dol_syslog(get_class($this)."::reopen reception movement index ".$i." ed.rowid=".$obj->rowid." edb.rowid=".$obj->edbrowid);

						//var_dump($this->lines[$i]);
						$mouvS = new MouvementStock($this->db);
						$mouvS->origin = &$this;
						$mouvS->setOrigin($this->element, $this->id);

						if (empty($obj->batch)) {
							// line without batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$inventorycode = '';
							$result = $mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->cost_price, $langs->trans("ReceptionBackToDraftInDolibarr", $this->ref), '', '', '', '', 0, $inventorycode);
							if ($result < 0) {
								$this->error = $mouvS->error;
								$this->errors = $mouvS->errors;
								$error++;
								break;
							}
						} else {
							// line with batch detail

							// We decrement stock of product (and sub-products) -> update table llx_product_stock (key of this table is fk_product+fk_entrepot) and add a movement record
							$inventorycode = '';
							$result = $mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $qty, $obj->cost_price, $langs->trans("ReceptionBackToDraftInDolibarr", $this->ref), $this->db->jdate($obj->eatby), $this->db->jdate($obj->sellby), $obj->batch, '', 0, $inventorycode);
							if ($result < 0) {
								$this->error = $mouvS->error;
								$this->errors = $mouvS->errors;
								$error++; break;
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
				$result = $this->call_trigger('RECEPTION_UNVALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
			}
			if ($this->origin == 'order_supplier') {
				if (!empty($this->origin) && $this->origin_id > 0) {
					$this->fetch_origin();
					$origin = $this->origin;
					if ($this->$origin->statut == 4) {  // If order source of reception is "partially received"
						// Check if there is no more reception validated.
						$this->$origin->fetchObjectLinked();
						$setStatut = 1;
						if (!empty($this->$origin->linkedObjects['reception'])) {
							foreach ($this->$origin->linkedObjects['reception'] as $rcption) {
								if ($rcption->statut > 0) {
									$setStatut = 0;
									break;
								}
							}
							//var_dump($this->$origin->receptions);exit;
							if ($setStatut) {
								$this->$origin->setStatut(3); // ordered
							}
						}
					}
				}
			}

			if (!$error) {
				$this->statut = self::STATUS_DRAFT;
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->error();
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
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $conf, $langs;

		$langs->load("receptions");

		if (!dol_strlen($modele)) {
			$modele = 'squille';

			if ($this->model_pdf) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->RECEPTION_ADDON_PDF)) {
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

	/**
	 * Function used to replace a product id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old product id
	 * @param int $dest_id New product id
	 * @return bool
	 */
	public static function replaceProduct(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'commande_fournisseur_dispatch'
		);

		return CommonObject::commonReplaceProduct($db, $origin_id, $dest_id, $tables);
	}
}
