<?php
/* Copyright (C) 2003-2008	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2007		Franky Van Liedekerke	<franky.van.liedekerke@telenet.be>
 * Copyright (C) 2006-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2012	Juanjo Menent			<jmenent@2byte.es>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/expedition/class/expedition.class.php
 *  \ingroup    expedition
 *  \brief      Fichier de la classe de gestion des expeditions
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
if (! empty($conf->propal->enabled)) require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';


/**
 *	Class to manage shipments
 */
class Expedition extends CommonObject
{
	public $element="shipping";
	public $fk_element="fk_expedition";
	public $table_element="expedition";
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	var $id;
	var $socid;
	var $ref_customer;
	var $ref_ext;
	var $ref_int;
	var $brouillon;
	var $entrepot_id;
	var $modelpdf;
	var $origin;
	var $origin_id;
	var $lines=array();
	var $shipping_method_id;
	var $tracking_number;
	var $tracking_url;
	var $statut;
	var $billed;
	var $note_public;
	var $note_private;
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
	var $date_expedition;	// Date delivery real
	var $date_creation;
	var $date_valid;

	// For Invoicing
	var $total_ht;			// Total net of tax
	var $total_ttc;			// Total with tax
	var $total_tva;			// Total VAT
	var $total_localtax1;   // Total Local tax 1
	var $total_localtax2;   // Total Local tax 2

	var $listmeths;			// List of carriers


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
		$this->statuts[-1] = 'StatusSendingCanceled';
		$this->statuts[0]  = 'StatusSendingDraft';
		$this->statuts[1]  = 'StatusSendingValidated';
		$this->statuts[2]  = 'StatusSendingProcessed';
	}

	/**
	 *	Return next contract ref
	 *
	 *	@param	Societe		$soc	Objet society
	 *	@return string				Free reference for contract
	 */
	function getNextNumRef($soc)
	{
		global $db, $langs, $conf;
		$langs->load("sendings");

		$dir = DOL_DOCUMENT_ROOT . "/core/modules/expedition";

	    if (empty($conf->global->EXPEDITION_ADDON_NUMBER))
        {
            $conf->global->EXPEDITION_ADDON_NUMBER='mod_expedition_safor';
        }

		$file = $conf->global->EXPEDITION_ADDON_NUMBER.".php";

		// Chargement de la classe de numerotation
		$classname = $conf->global->EXPEDITION_ADDON_NUMBER;

		$result=include_once $dir.'/'.$file;
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
				dol_print_error($db,get_class($this)."::getNextNumRef ".$obj->error);
				return "";
			}
		}
		else
		{
			print $langs->trans("Error")." ".$langs->trans("Error_EXPEDITION_ADDON_NUMBER_NotDefined");
			return "";
		}
	}

	/**
	 *  Create expedition en base
	 *
	 *  @param	User	$user       Objet du user qui cree
	 *  @return int 				<0 si erreur, id expedition creee si ok
	 */
	function create($user)
	{
		global $conf, $langs;

		$now=dol_now();
		
		if (empty($this->model_pdf)) $this->model_pdf=$conf->global->EXPEDITION_ADDON_PDF;

		require_once DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php';
		$error = 0;

		// Clean parameters
		$this->brouillon = 1;
		$this->tracking_number = dol_sanitizeFileName($this->tracking_number);

		$this->user = $user;


		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."expedition (";
		$sql.= "ref";
		$sql.= ", entity";
		$sql.= ", ref_customer";
		$sql.= ", ref_int";
		$sql.= ", date_creation";
		$sql.= ", fk_user_author";
		$sql.= ", date_expedition";
		$sql.= ", date_delivery";
		$sql.= ", fk_soc";
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
		$sql.= ") VALUES (";
		$sql.= "'(PROV)'";
		$sql.= ", ".$conf->entity;
		$sql.= ", ".($this->ref_customer?"'".$this->ref_customer."'":"null");
		$sql.= ", ".($this->ref_int?"'".$this->ref_int."'":"null");
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", ".$user->id;
		$sql.= ", ".($this->date_expedition>0?"'".$this->db->idate($this->date_expedition)."'":"null");
		$sql.= ", ".($this->date_delivery>0?"'".$this->db->idate($this->date_delivery)."'":"null");
		$sql.= ", ".$this->socid;
		$sql.= ", ".($this->fk_delivery_address>0?$this->fk_delivery_address:"null");
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
		$sql.= ")";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."expedition");

			$sql = "UPDATE ".MAIN_DB_PREFIX."expedition";
			$sql.= " SET ref = '(PROV".$this->id.")'";
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
			if ($this->db->query($sql))
			{
				// Insertion des lignes
				$num=count($this->lines);
				for ($i = 0; $i < $num; $i++)
				{
					if (! $this->create_line($this->lines[$i]->entrepot_id, $this->lines[$i]->origin_line_id, $this->lines[$i]->qty) > 0)
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

					// TODO uniformiser les statuts
					$ret = $this->setStatut(2,$this->origin_id,$this->origin);
					if (! $ret)
					{
						$error++;
					}
				}

				if (! $error)
				{
					// Appel des triggers
					include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
					$interface=new Interfaces($this->db);
					$result=$interface->run_triggers('SHIPPING_CREATE',$this,$user,$langs,$conf);
					if ($result < 0) { $error++; $this->errors=$interface->errors; }
					// Fin appel triggers

					$this->db->commit();
					return $this->id;
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
	 * Create a expedition line
	 *
	 * @param 	int		$entrepot_id		Id of warehouse
	 * @param 	int		$origin_line_id		Id of source line
	 * @param 	int		$qty				Quantity
	 * @return	int							<0 if KO, >0 if OK
	 */
	function create_line($entrepot_id, $origin_line_id, $qty)
	{
		$error = 0;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."expeditiondet (";
		$sql.= "fk_expedition";
		$sql.= ", fk_entrepot";
		$sql.= ", fk_origin_line";
		$sql.= ", qty";
		$sql.= ") VALUES (";
		$sql.= $this->id;
		$sql.= ", ".($entrepot_id?$entrepot_id:'null');
		$sql.= ", ".$origin_line_id;
		$sql.= ", ".$qty;
		$sql.= ")";

		if (! $this->db->query($sql))
		{
			$error++;
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
	 *	@return int			        >0 if OK, <0 if KO
	 */
	function fetch($id, $ref='', $ref_ext='', $ref_int='')
	{
		global $conf;

		// Check parameters
		if (empty($id) && empty($ref) && empty($ref_ext) && empty($ref_int)) return -1;

		$sql = "SELECT e.rowid, e.ref, e.fk_soc as socid, e.date_creation, e.ref_customer, e.ref_ext, e.ref_int, e.fk_user_author, e.fk_statut";
		$sql.= ", e.weight, e.weight_units, e.size, e.size_units, e.width, e.height";
		$sql.= ", e.date_expedition as date_expedition, e.model_pdf, e.fk_address, e.date_delivery";
		$sql.= ", e.fk_shipping_method, e.tracking_number";
		$sql.= ", el.fk_source as origin_id, el.sourcetype as origin";
		$sql.= ", e.note_private, e.note_public";
		$sql.= " FROM ".MAIN_DB_PREFIX."expedition as e";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_target = e.rowid AND el.targettype = '".$this->element."'";
		$sql.= " WHERE e.entity = ".$conf->entity;
		if ($id)   	  $sql.= " AND e.rowid=".$id;
        if ($ref)     $sql.= " AND e.ref='".$this->db->escape($ref)."'";
        if ($ref_ext) $sql.= " AND e.ref_ext='".$this->db->escape($ref_ext)."'";
        if ($ref_int) $sql.= " AND e.ref_int='".$this->db->escape($ref_int)."'";

		dol_syslog(get_class($this)."::fetch sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id                   = $obj->rowid;
				$this->ref                  = $obj->ref;
				$this->socid                = $obj->socid;
				$this->ref_customer			= $obj->ref_customer;
				$this->ref_ext				= $obj->ref_ext;
				$this->ref_int				= $obj->ref_int;
				$this->statut               = $obj->fk_statut;
				$this->user_author_id       = $obj->fk_user_author;
				$this->date_creation        = $this->db->jdate($obj->date_creation);
				$this->date                 = $this->db->jdate($obj->date_expedition);	// TODO obsolete
				$this->date_expedition      = $this->db->jdate($obj->date_expedition);	// TODO obsolete
				$this->date_shipping        = $this->db->jdate($obj->date_expedition);	// Date real
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

				$this->db->free($result);

				if ($this->statut == 0) $this->brouillon = 1;

				$file = $conf->expedition->dir_output . "/" .get_exdir($this->id, 2) . "/" . $this->id.".pdf";
				$this->pdf_filename = $file;

				// Tracking url
				$this->GetUrlTrackingStatus($obj->tracking_number);

				/*
				 * Thirparty
				 */
				$result=$this->fetch_thirdparty();

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
				dol_syslog(get_class($this).'::Fetch Error -2');
				$this->error='Delivery with id '.$id.' not found sql='.$sql;
				return -2;
			}
		}
		else
		{
			dol_syslog(get_class($this).'::Fetch Error -1');
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *  Validate object and update stock if option enabled
	 *
	 *  @param      User		$user       Object user that validate
	 *  @return     int						<0 if OK, >0 if KO
	 */
	function valid($user)
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

		if (! $user->rights->expedition->valider)
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
		if (! $error && (preg_match('/^[\(]?PROV/i', $this->ref)))
		{
			$numref = $this->getNextNumRef($soc);
		}
		else
		{
			$numref = "EXP".$this->id;
		}

		$now=dol_now();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX."expedition SET";
		$sql.= " ref='".$numref."'";
		$sql.= ", fk_statut = 1";
		$sql.= ", date_valid = '".$this->db->idate($now)."'";
		$sql.= ", fk_user_valid = ".$user->id;
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::valid update expedition sql=".$sql);
		$resql=$this->db->query($sql);
		if (! $resql)
		{
			dol_syslog(get_class($this)."::valid Echec update - 10 - sql=".$sql, LOG_ERR);
			$this->error=$this->db->lasterror();
			$error++;
		}

		// If stock increment is done on sending (recommanded choice)
		if (! $error && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT))
		{
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

			$langs->load("agenda");

			// Loop on each product line to add a stock movement
			// TODO possibilite d'expedier a partir d'une propale ou autre origine
			$sql = "SELECT cd.fk_product, cd.subprice, ed.qty, ed.fk_entrepot";
			$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
			$sql.= " ".MAIN_DB_PREFIX."expeditiondet as ed";
			$sql.= " WHERE ed.fk_expedition = ".$this->id;
			$sql.= " AND cd.rowid = ed.fk_origin_line";

			dol_syslog(get_class($this)."::valid select details sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
			    $cpt = $this->db->num_rows($resql);
                for ($i = 0; $i < $cpt; $i++)
				{
					dol_syslog(get_class($this)."::valid movement index ".$i);
					$obj = $this->db->fetch_object($resql);

					//var_dump($this->lines[$i]);
					$mouvS = new MouvementStock($this->db);
					// We decrement stock of product (and sub-products)
					// We use warehouse selected for each line
					$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $obj->qty, $obj->subprice, $langs->trans("ShipmentValidatedInDolibarr",$numref));
					if ($result < 0) { $error++; break; }
				}
			}
			else
			{
				$this->db->rollback();
				$this->error=$this->db->error();
				dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
				return -2;
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
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($numref);
				$dirsource = $conf->expedition->dir_output.'/sending/'.$oldref;
				$dirdest = $conf->expedition->dir_output.'/sending/'.$newref;
				if (file_exists($dirsource))
				{
					dol_syslog(get_class($this)."::valid rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest))
					{
						$this->oldref = $oldref;

						dol_syslog("Rename ok");
						// Suppression ancien fichier PDF dans nouveau rep
						dol_delete_file($dirdest.'/'.$oldref.'*.*');
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
			// Appel des triggers
			include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('SHIPPING_VALIDATE',$this,$user,$langs,$conf);
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
	 *	Cree un bon de livraison a partir de l'expedition
	 *
	 *	@param	User	$user       Utilisateur
	 *  @return int  				<0 if KO, >=0 if OK
	 */
	function create_delivery($user)
	{
		global $conf;

		if ($conf->livraison_bon->enabled)
		{
			if ($this->statut == 1 || $this->statut == 2)
			{
				// Expedition validee
				include_once DOL_DOCUMENT_ROOT.'/livraison/class/livraison.class.php';
				$delivery = new Livraison($this->db);
				$result=$delivery->create_from_sending($user, $this->id);
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
	 * Add a expedition line
	 *
	 * @param 	int		$entrepot_id		Id of warehouse
	 * @param 	int		$id					Id of source line
	 * @param 	int		$qty				Quantity
	 * @return	int							<0 if KO, >0 if OK
	 */
	function addline($entrepot_id, $id, $qty)
	{
		$num = count($this->lines);
		$line = new ExpeditionLigne($this->db);

		$line->entrepot_id = $entrepot_id;
		$line->origin_line_id = $id;
		$line->qty = $qty;

		$this->lines[$num] = $line;
	}

    /**
     *  Update database
     *
     *  @param	User	$user        	User that modify
     *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
     *  @return int 			       	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->ref)) $this->ref=trim($this->ref);
		if (isset($this->entity)) $this->entity=trim($this->entity);
		if (isset($this->ref_customer)) $this->ref_customer=trim($this->ref_customer);
		if (isset($this->socid)) $this->socid=trim($this->socid);
		if (isset($this->fk_user_author)) $this->fk_user_author=trim($this->fk_user_author);
		if (isset($this->fk_user_valid)) $this->fk_user_valid=trim($this->fk_user_valid);
		if (isset($this->fk_delivery_address)) $this->fk_delivery_address=trim($this->fk_delivery_address);
		if (isset($this->shipping_method_id)) $this->shipping_method_id=trim($this->shipping_method_id);
		if (isset($this->tracking_number)) $this->tracking_number=trim($this->tracking_number);
		if (isset($this->statut)) $this->statut=trim($this->statut);
		if (isset($this->trueDepth)) $this->trueDepth=trim($this->trueDepth);
		if (isset($this->trueWidth)) $this->trueWidth=trim($this->trueWidth);
		if (isset($this->trueHeight)) $this->trueHeight=trim($this->trueHeight);
		if (isset($this->size_units)) $this->size_units=trim($this->size_units);
		if (isset($this->weight_units)) $this->weight_units=trim($this->weight_units);
		if (isset($this->trueWeight)) $this->weight=trim($this->trueWeight);
		if (isset($this->note_private)) $this->note=trim($this->note_private);
		if (isset($this->note_public)) $this->note=trim($this->note_public);
		if (isset($this->model_pdf)) $this->model_pdf=trim($this->model_pdf);



		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."expedition SET";

		$sql.= " tms=".(dol_strlen($this->tms)!=0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
		$sql.= " ref=".(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"null").",";
		$sql.= " ref_customer=".(isset($this->ref_customer)?"'".$this->db->escape($this->ref_customer)."'":"null").",";
		$sql.= " fk_soc=".(isset($this->socid)?$this->socid:"null").",";
		$sql.= " date_creation=".(dol_strlen($this->date_creation)!=0 ? "'".$this->db->idate($this->date_creation)."'" : 'null').",";
		$sql.= " fk_user_author=".(isset($this->fk_user_author)?$this->fk_user_author:"null").",";
		$sql.= " date_valid=".(dol_strlen($this->date_valid)!=0 ? "'".$this->db->idate($this->date_valid)."'" : 'null').",";
		$sql.= " fk_user_valid=".(isset($this->fk_user_valid)?$this->fk_user_valid:"null").",";
		$sql.= " date_expedition=".(dol_strlen($this->date_expedition)!=0 ? "'".$this->db->idate($this->date_expedition)."'" : 'null').",";
		$sql.= " date_delivery=".(dol_strlen($this->date_delivery)!=0 ? "'".$this->db->idate($this->date_delivery)."'" : 'null').",";
		$sql.= " fk_address=".(isset($this->fk_delivery_address)?$this->fk_delivery_address:"null").",";
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
		$sql.= " model_pdf=".(isset($this->model_pdf)?"'".$this->db->escape($this->model_pdf)."'":"null").",";
		$sql.= " entity=".$conf->entity;

        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Call triggers
	            include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('SHIPPING_MODIFY',$this,$user,$langs,$conf);
	            if ($result < 0) { $error++; $this->errors=$interface->errors; }
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
	 * 	Delete shipment
	 *
	 * 	@return	int		>0 if OK otherwise if KO
	 */
	function delete()
	{
		global $conf, $langs, $user;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error=0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."expeditiondet";
		$sql.= " WHERE fk_expedition = ".$this->id;

		if ( $this->db->query($sql) )
		{
			// Delete linked object
			$res = $this->deleteObjectLinked();
			if ($res < 0) $error++;

			if (! $error)
			{
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."expedition";
				$sql.= " WHERE rowid = ".$this->id;

				if ($this->db->query($sql))
				{
					$this->db->commit();

					// On efface le repertoire de pdf provisoire
					$ref = dol_sanitizeFileName($this->ref);
					if (! empty($conf->expedition->dir_output))
					{
						$dir = $conf->expedition->dir_output . '/sending/' . $ref ;
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
							if (!dol_delete_dir($dir))
							{
								$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
								return 0;
							}
						}
					}

					// Call triggers
		            include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
		            $interface=new Interfaces($this->db);
		            $result=$interface->run_triggers('SHIPPING_DELETE',$this,$user,$langs,$conf);
		            if ($result < 0) { $error++; $this->errors=$interface->errors; }
		            // End call triggers

					// TODO il faut incrementer le stock si on supprime une expedition validee
					return 1;
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

	/**
	 *	Load lines
	 *
	 *	@return	int		>0 if OK, Otherwise if KO
	 */
	function fetch_lines()
	{
		// TODO: recuperer les champs du document associe a part

		$sql = "SELECT cd.rowid, cd.fk_product, cd.label as custom_label, cd.description, cd.qty as qty_asked";
		$sql.= ", cd.total_ht, cd.total_localtax1, cd.total_localtax2, cd.total_ttc, cd.total_tva";
		$sql.= ", cd.tva_tx, cd.localtax1_tx, cd.localtax2_tx, cd.price, cd.subprice, cd.remise_percent";
		$sql.= ", ed.qty as qty_shipped, ed.fk_origin_line, ed.fk_entrepot";
		$sql.= ", p.ref as product_ref, p.label as product_label, p.fk_product_type";
		$sql.= ", p.weight, p.weight_units, p.length, p.length_units, p.surface, p.surface_units, p.volume, p.volume_units";
		$sql.= " FROM (".MAIN_DB_PREFIX."expeditiondet as ed,";
		$sql.= " ".MAIN_DB_PREFIX."commandedet as cd)";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = cd.fk_product";
		$sql.= " WHERE ed.fk_expedition = ".$this->id;
		$sql.= " AND ed.fk_origin_line = cd.rowid";

		dol_syslog(get_class($this)."::fetch_lines sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

			$num = $this->db->num_rows($resql);
			$i = 0;

			$this->total_ht = 0;
			$this->total_tva = 0;
			$this->total_ttc = 0;
			$this->total_localtax1 = 0;
			$this->total_localtax2 = 0;

			while ($i < $num)
			{
				$line = new ExpeditionLigne($this->db);
				$obj = $this->db->fetch_object($resql);

				$line->fk_origin_line 	= $obj->fk_origin_line;
				$line->origin_line_id 	= $obj->fk_origin_line;	    // TODO deprecated
				$line->entrepot_id    	= $obj->fk_entrepot;
				$line->fk_product     	= $obj->fk_product;
				$line->fk_product_type	= $obj->fk_product_type;
				$line->ref				= $obj->product_ref;		// TODO deprecated
                $line->product_ref		= $obj->product_ref;
                $line->product_label	= $obj->product_label;
				$line->libelle        	= $obj->product_label;		// TODO deprecated
				$line->label			= $obj->custom_label;
				$line->description    	= $obj->description;
				$line->qty_asked      	= $obj->qty_asked;
				$line->qty_shipped    	= $obj->qty_shipped;
				$line->weight         	= $obj->weight;
				$line->weight_units   	= $obj->weight_units;
				$line->length         	= $obj->length;
				$line->length_units   	= $obj->length_units;
				$line->surface        	= $obj->surface;
				$line->surface_units   	= $obj->surface_units;
				$line->volume         	= $obj->volume;
				$line->volume_units   	= $obj->volume_units;

				// For invoicing
				$line->desc	         	= $obj->description;		// We need ->desc because some code into CommonObject use desc (property defined for other elements)
				$line->qty 				= $obj->qty_shipped;
				$line->total_ht			= $obj->total_ht;
				$line->total_localtax1 	= $obj->total_localtax1;
				$line->total_localtax2 	= $obj->total_localtax2;
				$line->total_ttc	 	= $obj->total_ttc;
				$line->total_tva	 	= $obj->total_tva;
				$line->tva_tx 		 	= $obj->tva_tx;
				$line->localtax1_tx 	= $obj->localtax1_tx;
				$line->localtax2_tx 	= $obj->localtax2_tx;
				$line->price			= $obj->price;
				$line->subprice			= $obj->subprice;
				$line->remise_percent	= $obj->remise_percent;

				$tabprice = calcul_price_total($obj->qty_shipped, $obj->subprice, $obj->remise_percent, $obj->tva_tx, $obj->localtax1_tx, $obj->localtax2_tx, 0, 'HT', $info_bits, $obj->fk_product_type);	// We force type to 0
				$this->total_ht+= $tabprice[0];
				$this->total_tva+= $tabprice[1];
				$this->total_ttc+= $tabprice[2];
				$this->total_localtax1+= $tabprice[9];
				$this->total_localtax2+= $tabprice[10];

				$this->lines[$i] = $line;

				$i++;
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::fetch_lines: Error '.$this->error, LOG_ERR);
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
     *	@return     string          			String with URL
     */
	function getNomUrl($withpicto=0,$option=0,$max=0,$short=0)
	{
		global $langs;

		$result='';

		$url = DOL_URL_ROOT.'/expedition/fiche.php?id='.$this->id;

		if ($short) return $url;

		$linkstart = '<a href="'.$url.'">';
		$linkend='</a>';

		$picto='sending';
		$label=$langs->trans("ShowSending").': '.$this->ref;

		if ($withpicto) $result.=($linkstart.img_object($label,$picto).$linkend);
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
			if ($statut==0) return $langs->trans('StatusSendingDraftShort');
			if ($statut==1) return $langs->trans('StatusSendingValidatedShort');
			if ($statut==2) return $langs->trans('StatusSendingProcessedShort');
		}
		if ($mode == 3)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts[$statut]),'statut0');
			if ($statut==1) return img_picto($langs->trans($this->statuts[$statut]),'statut4');
			if ($statut==2) return img_picto($langs->trans('StatusSendingProcessed'),'statut6');
		}
		if ($mode == 4)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts[$statut]),'statut0').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==1) return img_picto($langs->trans($this->statuts[$statut]),'statut4').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==2) return img_picto($langs->trans('StatusSendingProcessed'),'statut6').' '.$langs->trans('StatusSendingProcessed');
		}
		if ($mode == 5)
		{
			if ($statut==0) return $langs->trans('StatusSendingDraftShort').' '.img_picto($langs->trans($this->statuts[$statut]),'statut0');
			if ($statut==1) return $langs->trans('StatusSendingValidatedShort').' '.img_picto($langs->trans($this->statuts[$statut]),'statut4');
			if ($statut==2) return $langs->trans('StatusSendingProcessedShort').' '.img_picto($langs->trans('StatusSendingProcessedShort'),'statut6');
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

		$now=dol_now();

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
		$this->date_expedition      = $now + 24*3600;

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
			$line=new ExpeditionLigne($this->db);
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
		if ($user->rights->expedition->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."expedition";
			$sql.= " SET date_delivery = ".($date_livraison ? "'".$this->db->idate($date_livraison)."'" : 'null');
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::set_date_livraison sql=".$sql,LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->date_delivery = $date_livraison;
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
	 *	Fetch deliveries method and return an array. Load array this->meths(rowid=>label).
	 *
	 * 	@return	void
	 */
	function fetch_delivery_methods()
	{
		global $langs;
		$meths = array();

		$sql = "SELECT em.rowid, em.code, em.libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
		$sql.= " WHERE em.active = 1";
		$sql.= " ORDER BY em.libelle ASC";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
				$label=$langs->trans('SendingMethod'.$obj->code);
				$this->meths[$obj->rowid] = ($label != 'SendingMethod'.$obj->code?$label:$obj->libelle);
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
        $sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
        if ($id!='') $sql.= " WHERE em.rowid=".$id;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            while ($obj = $this->db->fetch_object($resql))
            {
                $this->listmeths[$i]['rowid'] = $obj->rowid;
                $this->listmeths[$i]['code'] = $obj->code;
                $label=$langs->trans('SendingMethod'.$obj->code);
                $this->listmeths[$i]['libelle'] = ($label != 'SendingMethod'.$obj->code?$label:$obj->libelle);
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
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_shipment_mode (code, libelle, description, tracking)";
            $sql.=" VALUES ('".$this->update['code']."','".$this->update['libelle']."','".$this->update['description']."','".$this->update['tracking']."')";
            $resql = $this->db->query($sql);
        }
        else
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."c_shipment_mode SET";
            $sql.= " code='".$this->update['code']."'";
            $sql.= ",libelle='".$this->update['libelle']."'";
            $sql.= ",description='".$this->update['description']."'";
            $sql.= ",tracking='".$this->update['tracking']."'";
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
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'c_shipment_mode SET active=1';
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
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'c_shipment_mode SET active=0';
        $sql.= ' WHERE rowid='.$id;

        $resql = $this->db->query($sql);

    }

	/**
	 * Get tracking url status
	 *
	 * @param	string	$value		Value
	 * @return	void
	 */
	function GetUrlTrackingStatus($value='')
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
	 *	Classify the shipping as invoiced
	 *
	 *	@return     int     <0 if ko, >0 if ok
	 */
	function set_billed()
	{
		global $conf;

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'expedition SET fk_statut=2';
		$sql .= ' WHERE rowid = '.$this->id.' AND fk_statut > 0 ;';
		if ($this->db->query($sql) )
		{
			//TODO: Option to set order billed if 100% of order is shipped
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

}


/**
 * Classe de gestion des lignes de bons d'expedition
 */
class ExpeditionLigne
{
	var $db;

	// From llx_expeditiondet
	var $qty;
	var $qty_shipped;
	var $fk_product;

	// From llx_commandedet or llx_propaldet
	var $qty_asked;
	var $libelle;       // Label produit
	var $product_desc;  // Description produit
	var $ref;

	// Invoicing
	var $remise_percent;
	var $total_ht;			// Total net of tax
	var $total_ttc;			// Total with tax
	var $total_tva;			// Total VAT
	var $total_localtax1;   // Total Local tax 1
	var $total_localtax2;   // Total Local tax 2


    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
	function __construct($db)
	{
		$this->db=$db;
	}

}

?>
