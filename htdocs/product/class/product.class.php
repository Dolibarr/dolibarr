<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2007-2011	Jean Heimburger			<jean@tiaris.info>
 * Copyright (C) 2010-2013	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013-2014	Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2013-2014	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2011-2014	Alexandre Spangaro		<alexandre.spangaro@gmail.com>
 * Copyright (C) 2014		Henry Florian			<florian.henry@open-concept.pro>
 * Copyright (C) 2014		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2014		Ion agorria			<ion@agorria.com>
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
 *	\file       htdocs/product/class/product.class.php
 *	\ingroup    produit
 *	\brief      File of class to manage predefined products or services
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';


/**
 * Class to manage products or services
 */
class Product extends CommonObject
{
	public $element='product';
	public $table_element='product';
	public $fk_element='fk_product';
	protected $childtables=array('askpricesupplierdet', 'propaldet','commandedet','facturedet','contratdet','facture_fourn_det','commande_fournisseurdet');    // To test if we can delete object
	protected $isnolinkedbythird = 1;     // No field fk_soc
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'ref';

	var $regeximgext='\.jpg|\.jpeg|\.bmp|\.gif|\.png|\.tiff';

	//! Identifiant unique
	var $id ;
	//! Ref
	var $ref;
	var $libelle;            // TODO deprecated
	var $label;
	var $description;

	/**
	 * Check TYPE constants
	 * @var int
	 */
	var $type = self::TYPE_PRODUCT;
	//! Selling price
	var $price;				// Price net
	var $price_ttc;			// Price with tax
	var $price_min;         // Minimum price net
	var $price_min_ttc;     // Minimum price with tax
	//! Base price ('TTC' for price including tax or 'HT' for net price)
	var $price_base_type;
	//! Arrays for multiprices
	var $multiprices=array();
	var $multiprices_ttc=array();
	var $multiprices_base_type=array();
	var $multiprices_tva_tx=array();
	var $multiprices_recuperableonly=array();
	//! Price by quantity arrays
	var $price_by_qty;
	var $prices_by_qty=array();
	var $prices_by_qty_id=array();
	var $prices_by_qty_list=array();
	//! Default VAT rate of product
	var $tva_tx;
	//! French VAT NPR (0 or 1)
    var $tva_npr=0;
	//! Spanish local taxes
	var $localtax1_tx;
	var $localtax2_tx;

	//! Stock
	var $stock_reel;
	//! Average price value for product entry into stock (PMP)
	var $pmp;
    //! Stock alert
	var $seuil_stock_alerte;
	//! Ask for replenishment when $desiredstock < $stock_reel
	public $desiredstock;
	//! Duree de validite du service
	var $duration_value;
	//! Unite de duree
	var $duration_unit;
	// Statut indique si le produit est en vente '1' ou non '0'
	var $status;
	// Status indicate whether the product is available for purchase '1' or not '0'
	var $status_buy;
	// Statut indique si le produit est un produit fini '1' ou une matiere premiere '0'
	var $finished;
	// We must manage batch number, sell-by date and so on : '1':yes '0':no
	var $status_batch;

	var $customcode;       // Customs code
    var $country_id;       // Country origin id
	var $country_code;     // Country origin code (US, FR, ...)

	//! Unites de mesure
	var $weight;
	var $weight_units;
	var $length;
	var $length_units;
	var $surface;
	var $surface_units;
	var $volume;
	var $volume_units;

	var $accountancy_code_buy;
	var $accountancy_code_sell;

	//! barcode
	var $barcode;               // value
	var $barcode_type;          // id
	var $barcode_type_code;     // code  (loaded by fetch_barcode). Example 'ean', 'isbn', ...
	var $barcode_type_label;    // label (loaded by fetch_barcode)
	var $barcode_type_coder;    // coder (loaded by fetch_barcode). Engine.

	var $stats_propale=array();
	var $stats_commande=array();
	var $stats_contrat=array();
	var $stats_facture=array();
    var $stats_commande_fournisseur=array();

	var $multilangs=array();

	//! Taille de l'image
	var $imgWidth;
	var $imgHeight;

	//! Canevas a utiliser si le produit n'est pas un produit generique
	var $canvas;

	var $import_key;
	var $date_creation;
	var $date_modification;

	//! Id du fournisseur
	var $product_fourn_id;

	//! Product ID already linked to a reference supplier
	var $product_id_already_linked;

	var $nbphoto;

	//! Contains detail of stock of product into each warehouse
	var $stock_warehouse=array();

	var $oldcopy;

	//note not visible on orders and invoices
	var $note;

    var $fk_price_expression;

	/**
	 * Regular product
	 */
	const TYPE_PRODUCT = 0;
	/**
	 * Service
	 */
	const TYPE_SERVICE = 1;
	/**
	 * Advanced feature: assembly kit
	 */
	const TYPE_ASSEMBLYKIT = 2;
	/**
	 * Advanced feature: stock kit
	 */
	const TYPE_STOCKKIT = 3;

	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $langs;

		$this->db = $db;
		$this->status = 0;
		$this->status_buy = 0;
		$this->nbphoto = 0;
		$this->stock_reel = 0;
		$this->seuil_stock_alerte = 0;
		$this->desiredstock = 0;
		$this->canvas = '';
		$this->status_batch=0;
	}

	/**
	 *    Check that ref and label are ok
	 *
	 *    @return     int         >1 if OK, <=0 if KO
	 */
	function check()
	{
		$this->ref = dol_sanitizeFileName(stripslashes($this->ref));

		$err = 0;
		if (dol_strlen(trim($this->ref)) == 0)
		$err++;

		if (dol_strlen(trim($this->libelle)) == 0)
		$err++;

		if ($err > 0)
		{
			return 0;
		}
		else
		{
			return 1;
		}
	}

	/**
	 *	Insert product into database
	 *
	 *	@param	User	$user     		User making insert
	 *  @param	int		$notrigger		Disable triggers
	 *	@return int			     		Id of product/service if OK, < 0 if KO
	 */
	function create($user,$notrigger=0)
	{
		global $conf, $langs;

        $error=0;

		// Clean parameters
		$this->ref = dol_string_nospecial(trim($this->ref));
		$this->libelle = trim($this->libelle);
		$this->price_ttc=price2num($this->price_ttc);
		$this->price=price2num($this->price);
		$this->price_min_ttc=price2num($this->price_min_ttc);
		$this->price_min=price2num($this->price_min);
		if (empty($this->tva_tx))    	$this->tva_tx = 0;
		if (empty($this->tva_npr))    	$this->tva_npr = 0;
		//Local taxes
		if (empty($this->localtax1_tx)) $this->localtax1_tx = 0;
		if (empty($this->localtax2_tx)) $this->localtax2_tx = 0;

		if (empty($this->price))     	$this->price = 0;
		if (empty($this->price_min)) 	$this->price_min = 0;

		// Price by quantity
		if (empty($this->price_by_qty)) 	$this->price_by_qty = 0;

		if (empty($this->status))    	$this->status = 0;
		if (empty($this->status_buy))   $this->status_buy = 0;

		$price_ht=0;
		$price_ttc=0;
		$price_min_ht=0;
		$price_min_ttc=0;
		if ($this->price_base_type == 'TTC' && $this->price_ttc > 0)
		{
			$price_ttc = price2num($this->price_ttc,'MU');
			$price_ht = price2num($this->price_ttc / (1 + ($this->tva_tx / 100)),'MU');
		}
		if ($this->price_base_type != 'TTC' && $this->price > 0)
		{
			$price_ht = price2num($this->price,'MU');
			$price_ttc = price2num($this->price * (1 + ($this->tva_tx / 100)),'MU');
		}
		if (($this->price_min_ttc > 0) && ($this->price_base_type == 'TTC'))
		{
			$price_min_ttc = price2num($this->price_min_ttc,'MU');
			$price_min_ht = price2num($this->price_min_ttc / (1 + ($this->tva_tx / 100)),'MU');
		}
		if (($this->price_min > 0) && ($this->price_base_type != 'TTC'))
		{
			$price_min_ht = price2num($this->price_min,'MU');
			$price_min_ttc = price2num($this->price_min * (1 + ($this->tva_tx / 100)),'MU');
		}

    	$this->accountancy_code_buy = trim($this->accountancy_code_buy);
		$this->accountancy_code_sell= trim($this->accountancy_code_sell);

        // Barcode value
        $this->barcode=trim($this->barcode);

        // Check parameters
		if (empty($this->libelle))
		{
			$this->error='ErrorMandatoryParametersNotProvided';
			return -1;
		}
		if (empty($this->ref))
		{
			// Load object modCodeProduct
			$module=(! empty($conf->global->PRODUCT_CODEPRODUCT_ADDON)?$conf->global->PRODUCT_CODEPRODUCT_ADDON:'mod_codeproduct_leopard');
			if ($module != 'mod_codeproduct_leopard')	// Do not load module file for leopard
			{
				if (substr($module, 0, 16) == 'mod_codeproduct_' && substr($module, -3) == 'php')
				{
					$module = substr($module, 0, dol_strlen($module)-4);
				}
				dol_include_once('/core/modules/product/'.$module.'.php');
				$modCodeProduct = new $module;
				if (! empty($modCodeProduct->code_auto))
				{
					$this->ref = $modCodeProduct->getNextValue($this,$this->type);
				}
				unset($modCodeProduct);
			}
			if (empty($this->ref))
			{
				$this->error='ProductModuleNotSetupForAutoRef';
				return -2;
			}
		}

		dol_syslog(get_class($this)."::create ref=".$this->ref." price=".$this->price." price_ttc=".$this->price_ttc." tva_tx=".$this->tva_tx." price_base_type=".$this->price_base_type, LOG_DEBUG);

        $now=dol_now();

		$this->db->begin();

        // For automatic creation during create action (not used by Dolibarr GUI, can be used by scripts)
		if ($this->barcode == -1) $this->barcode = $this->get_barcode($this,$this->barcode_type_code);

		// Check more parameters
        // If error, this->errors[] is filled
        $result = $this->verify();

        if ($result >= 0)
        {
			$sql = "SELECT count(*) as nb";
			$sql.= " FROM ".MAIN_DB_PREFIX."product";
			$sql.= " WHERE entity IN (".getEntity('product', 1).")";
			$sql.= " AND ref = '" .$this->ref."'";

			$result = $this->db->query($sql);
			if ($result)
			{
				$obj = $this->db->fetch_object($result);
				if ($obj->nb == 0)
				{
					// Produit non deja existant
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."product (";
					$sql.= "datec";
					$sql.= ", entity";
					$sql.= ", ref";
					$sql.= ", ref_ext";
					$sql.= ", price_min";
					$sql.= ", price_min_ttc";
					$sql.= ", label";
					$sql.= ", fk_user_author";
					$sql.= ", fk_product_type";
					$sql.= ", price";
					$sql.= ", price_ttc";
					$sql.= ", price_base_type";
					$sql.= ", tobuy";
					$sql.= ", tosell";
					$sql.= ", accountancy_code_buy";
					$sql.= ", accountancy_code_sell";
					$sql.= ", canvas";
					$sql.= ", finished";
					$sql.= ", tobatch";
					$sql.= ") VALUES (";
					$sql.= "'".$this->db->idate($now)."'";
					$sql.= ", ".$conf->entity;
					$sql.= ", '".$this->db->escape($this->ref)."'";
					$sql.= ", ".(! empty($this->ref_ext)?"'".$this->db->escape($this->ref_ext)."'":"null");
					$sql.= ", ".price2num($price_min_ht);
					$sql.= ", ".price2num($price_min_ttc);
					$sql.= ", ".(! empty($this->libelle)?"'".$this->db->escape($this->libelle)."'":"null");
					$sql.= ", ".$user->id;
					$sql.= ", ".$this->type;
					$sql.= ", ".price2num($price_ht);
					$sql.= ", ".price2num($price_ttc);
					$sql.= ", '".$this->price_base_type."'";
					$sql.= ", ".$this->status;
					$sql.= ", ".$this->status_buy;
					$sql.= ", '".$this->accountancy_code_buy."'";
					$sql.= ", '".$this->accountancy_code_sell."'";
					$sql.= ", '".$this->canvas."'";
					$sql.= ", ".((! isset($this->finished) || $this->finished < 0 || $this->finished == '') ? 'null' : (int) $this->finished);
					$sql.= ", ".((empty($this->status_batch) || $this->status_batch < 0)? '0':$this->status_batch);
					$sql.= ")";

					dol_syslog(get_class($this)."::Create", LOG_DEBUG);
					$result = $this->db->query($sql);
					if ( $result )
					{
						$id = $this->db->last_insert_id(MAIN_DB_PREFIX."product");

						if ($id > 0)
						{
							$this->id				= $id;
							$this->price			= $price_ht;
							$this->price_ttc		= $price_ttc;
							$this->price_min		= $price_min_ht;
							$this->price_min_ttc	= $price_min_ttc;

							$result = $this->_log_price($user);
							if ($result > 0)
							{
								if ($this->update($id, $user, true, 'add') <= 0)
								{
								    $error++;
								}
							}
							else
							{
								$error++;
							    $this->error=$this->db->lasterror();
							}
						}
						else
						{
							$error++;
						    $this->error='ErrorFailedToGetInsertedId';
						}
					}
					else
					{
						$error++;
					    $this->error=$this->db->lasterror();
					}
				}
				else
				{
					// Product already exists with this ref
					$langs->load("products");
					$error++;
					$this->error = "ErrorProductAlreadyExists";
				}
			}
			else
			{
				$error++;
			    $this->error=$this->db->lasterror();
			}

			if (! $error && ! $notrigger)
			{
                // Call trigger
                $result=$this->call_trigger('PRODUCT_CREATE',$user);
                if ($result < 0) { $error++; }
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
				return -$error;
			}
        }
        else
       {
            $this->db->rollback();
            dol_syslog(get_class($this)."::Create fails verify ".join(',',$this->errors), LOG_WARNING);
            return -3;
        }

	}


    /**
     *    Check properties of product are ok (like name, barcode, ...).
     *    All properties must be already loaded on object (this->barcode, this->barcode_type_code, ...).
     *
     *    @return     int		0 if OK, <0 if KO
     */
    function verify()
    {
        $this->errors=array();

        $result = 0;
        $this->ref = trim($this->ref);

        if (! $this->ref)
        {
            $this->errors[] = 'ErrorBadRef';
            $result = -2;
        }

        $rescode = $this->check_barcode($this->barcode,$this->barcode_type_code);
        if ($rescode <> 0)
        {
        	if ($rescode == -1)
        	{
        		$this->errors[] = 'ErrorBadBarCodeSyntax';
        	}
        	if ($rescode == -2)
        	{
        		$this->errors[] = 'ErrorBarCodeRequired';
        	}
        	if ($rescode == -3)
        	{
        		$this->errors[] = 'ErrorBarCodeAlreadyUsed';
        	}
        	$result = -3;
        }

        return $result;
    }

    /**
     *  Check barcode
     *
     *	@param	string	$valuetotest	Value to test
     *  @param	string	$typefortest	Type of barcode (ISBN, EAN, ...)
     *  @return int						0 if OK
     * 									-1 ErrorBadBarCodeSyntax
     * 									-2 ErrorBarCodeRequired
     * 									-3 ErrorBarCodeAlreadyUsed
     */
    function check_barcode($valuetotest,$typefortest)
    {
        global $conf;
        if (! empty($conf->barcode->enabled) && ! empty($conf->global->BARCODE_PRODUCT_ADDON_NUM))
        {
        	$module=strtolower($conf->global->BARCODE_PRODUCT_ADDON_NUM);

            $dirsociete=array_merge(array('/core/modules/barcode/'),$conf->modules_parts['barcode']);
            foreach ($dirsociete as $dirroot)
            {
                $res=dol_include_once($dirroot.$module.'.php');
                if ($res) break;
            }

            $mod = new $module();

            dol_syslog(get_class($this)."::check_barcode value=".$valuetotest." type=".$typefortest." module=".$module);
            $result = $mod->verif($this->db, $valuetotest, $this, 0, $typefortest);
            return $result;
        }
        else
		{
            return 0;
        }
    }

	/**
	 *	Update a record into database
	 *
	 *	@param	int		$id         Id of product
	 *	@param  User	$user       Object user making update
	 *	@param	int		$notrigger	Disable triggers
	 *	@param	string	$action		Current action for hookmanager ('add' or 'update')
	 *	@return int         		1 if OK, -1 if ref already exists, -2 if other error
	 */
	function update($id, $user, $notrigger=false, $action='update')
	{
		global $langs, $conf, $hookmanager;

		$error=0;

		// Check parameters
		if (! $this->libelle) $this->libelle = 'MISSING LABEL';

		// Clean parameters
		$this->ref = dol_string_nospecial(trim($this->ref));
		$this->libelle = trim($this->libelle);
		$this->description = trim($this->description);
		$this->note = (isset($this->note) ? trim($this->note) : null);
		$this->weight = price2num($this->weight);
		$this->weight_units = trim($this->weight_units);
		$this->length = price2num($this->length);
		$this->length_units = trim($this->length_units);
		$this->surface = price2num($this->surface);
		$this->surface_units = trim($this->surface_units);
		$this->volume = price2num($this->volume);
		$this->volume_units = trim($this->volume_units);
		if (empty($this->tva_tx))    			$this->tva_tx = 0;
		if (empty($this->tva_npr))    			$this->tva_npr = 0;
		if (empty($this->localtax1_tx))			$this->localtax1_tx = 0;
		if (empty($this->localtax2_tx))			$this->localtax2_tx = 0;
		if (empty($this->status))				$this->status = 0;
		if (empty($this->status_buy))			$this->status_buy = 0;

        if (empty($this->country_id))           $this->country_id = 0;

        // Barcode value
        $this->barcode=trim($this->barcode);

		$this->accountancy_code_buy = trim($this->accountancy_code_buy);
		$this->accountancy_code_sell= trim($this->accountancy_code_sell);


        $this->db->begin();

        // Check name is required and codes are ok or unique.
        // If error, this->errors[] is filled
        if ($action != 'add')
        {
        	$result = $this->verify();	// We don't check when update called during a create because verify was already done
        }

        if ($result >= 0)
        {
            if (empty($this->oldcopy))
            {
                $org=new self($this->db);
                $org->fetch($this->id);
                $this->oldcopy=$org;
            }
            // test if batch management is activated on existing product
            if ($this->hasbatch() && !$this->oldcopy->hasbatch())
            {
                $this->load_stock();
                foreach ($this->stock_warehouse as $idW => $ObjW)
                {
                    $qty_batch = 0;
                    foreach ($ObjW->detail_batch as $detail)
                    {
                        $qty_batch += $detail->qty;
                    }
                    // Quantities in batch details are not same same as stock quantity
                    // So we add a default batch record
                    if ($ObjW->real <> $qty_batch)
                    {
                        $ObjBatch = new Productbatch($this->db);
                        $ObjBatch->batch = $langs->trans('BatchDefaultNumber');
                        $ObjBatch->qty = $ObjW->real - $qty_batch;
                        $ObjBatch->fk_product_stock = $ObjW->id;
                        if ($ObjBatch->create($user,1) < 0)
                        {
                            $error++;
                            $this->errors=$ObjBatch->errors;
                        }
                    }
                }
            }
	        // For automatic creation
	        if ($this->barcode == -1) $this->barcode = $this->get_barcode($this,$this->barcode_type_code);

			$sql = "UPDATE ".MAIN_DB_PREFIX."product";
			$sql.= " SET label = '" . $this->db->escape($this->libelle) ."'";
			$sql.= ", ref = '" . $this->ref ."'";
			$sql.= ", ref_ext = ".(! empty($this->ref_ext)?"'".$this->db->escape($this->ref_ext)."'":"null");
			$sql.= ", tva_tx = " . $this->tva_tx;
			$sql.= ", recuperableonly = " . $this->tva_npr;
			$sql.= ", localtax1_tx = " . $this->localtax1_tx;
			$sql.= ", localtax2_tx = " . $this->localtax2_tx;

			$sql.= ", barcode = ". (empty($this->barcode)?"null":"'".$this->db->escape($this->barcode)."'");
			$sql.= ", fk_barcode_type = ". (empty($this->barcode_type)?"null":$this->db->escape($this->barcode_type));

			$sql.= ", tosell = " . $this->status;
			$sql.= ", tobuy = " . $this->status_buy;
			$sql.= ", tobatch = " . ((empty($this->status_batch) || $this->status_batch < 0) ? '0' : $this->status_batch);
			$sql.= ", finished = " . ((! isset($this->finished) || $this->finished < 0) ? "null" : (int) $this->finished);
			$sql.= ", weight = " . ($this->weight!='' ? "'".$this->weight."'" : 'null');
			$sql.= ", weight_units = " . ($this->weight_units!='' ? "'".$this->weight_units."'": 'null');
			$sql.= ", length = " . ($this->length!='' ? "'".$this->length."'" : 'null');
			$sql.= ", length_units = " . ($this->length_units!='' ? "'".$this->length_units."'" : 'null');
			$sql.= ", surface = " . ($this->surface!='' ? "'".$this->surface."'" : 'null');
			$sql.= ", surface_units = " . ($this->surface_units!='' ? "'".$this->surface_units."'" : 'null');
			$sql.= ", volume = " . ($this->volume!='' ? "'".$this->volume."'" : 'null');
			$sql.= ", volume_units = " . ($this->volume_units!='' ? "'".$this->volume_units."'" : 'null');
			$sql.= ", seuil_stock_alerte = " . ((isset($this->seuil_stock_alerte) && $this->seuil_stock_alerte != '') ? "'".$this->seuil_stock_alerte."'" : "null");
			$sql.= ", description = '" . $this->db->escape($this->description) ."'";
			$sql.= ", url = " . ($this->url?"'".$this->db->escape($this->url)."'":'null');
			$sql.= ", customcode = '" .        $this->db->escape($this->customcode) ."'";
	        $sql.= ", fk_country = " . ($this->country_id > 0 ? $this->country_id : 'null');
	        $sql.= ", note = ".(isset($this->note) ? "'" .$this->db->escape($this->note)."'" : 'null');
			$sql.= ", duration = '" . $this->duration_value . $this->duration_unit ."'";
			$sql.= ", accountancy_code_buy = '" . $this->accountancy_code_buy."'";
			$sql.= ", accountancy_code_sell= '" . $this->accountancy_code_sell."'";
			$sql.= ", desiredstock = " . ((isset($this->desiredstock) && $this->desiredstock != '') ? $this->desiredstock : "null");
			$sql.= " WHERE rowid = " . $id;

			dol_syslog(get_class($this)."::update", LOG_DEBUG);

			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->id = $id;

				// Multilangs
				if (! empty($conf->global->MAIN_MULTILANGS))
				{
					if ( $this->setMultiLangs() < 0)
					{
						$this->error=$langs->trans("Error")." : ".$this->db->error()." - ".$sql;
						return -2;
					}
				}

				$action='update';

				// Actions on extra fields (by external module or standard code)
				$hookmanager->initHooks(array('productdao'));
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

				if (! $error && ! $notrigger)
				{
                    // Call trigger
                    $result=$this->call_trigger('PRODUCT_MODIFY',$user);
                    if ($result < 0) { $error++; }
                    // End call triggers
				}

				if (! $error && (is_object($this->oldcopy) && $this->oldcopy->ref != $this->ref))
				{
					// We remove directory
					if ($conf->product->dir_output)
					{
						$olddir = $conf->product->dir_output . "/" . dol_sanitizeFileName($this->oldcopy->ref);
						$newdir = $conf->product->dir_output . "/" . dol_sanitizeFileName($this->ref);
						if (file_exists($olddir))
						{
							include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
							$res=dol_move($olddir, $newdir);
							if (! $res)
							{
								$this->error='ErrorFailToMoveDir';
								$error++;
							}
						}
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
					return -$error;
				}
			}
			else
			{
				if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
				{
					$this->error=$langs->trans("Error")." : ".$langs->trans("ErrorProductAlreadyExists",$this->ref);
					$this->db->rollback();
					return -1;
				}
				else
				{
					$this->error=$langs->trans("Error")." : ".$this->db->error()." - ".$sql;
					$this->db->rollback();
					return -2;
				}
			}
        }
        else
       {
            $this->db->rollback();
            dol_syslog(get_class($this)."::Update fails verify ".join(',',$this->errors), LOG_WARNING);
            return -3;
        }
	}

	/**
	 *  Delete a product from database (if not used)
	 *
	 *	@param      int		$id         Product id (usage of this is deprecated, delete should be called without parameters on a fetched object)
	 * 	@return		int					< 0 if KO, 0 = Not possible, > 0 if OK
	 */
	function delete($id=0)
	{
		global $conf,$user,$langs;
		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$error=0;

		// Clean parameters
		if (empty($id)) $id=$this->id;
		else $this->fetch($id);

		// Check parameters
		if (empty($id))
		{
			$this->error = "Object must be fetched before calling delete";
			return -1;
		}
		if (($this->type == Product::TYPE_PRODUCT && empty($user->rights->produit->supprimer)) || ($this->type == Product::TYPE_SERVICE && empty($user->rights->service->supprimer)))
		{
			$this->error = "ErrorForbidden";
			return 0;
		}

		$objectisused = $this->isObjectUsed($id);
		if (empty($objectisused))
		{
			$this->db->begin();

			if (! $error)
			{
                // Call trigger
                $result=$this->call_trigger('PRODUCT_DELETE',$user);
                if ($result < 0) { $error++; }
                // End call triggers
			}

   			// Delete all child tables
			if (! $error)
			{
    			$elements = array('product_fournisseur_price','product_price','product_lang','categorie_product','product_stock');
    			foreach($elements as $table)
    			{
    				if (! $error)
    				{
    					$sql = "DELETE FROM ".MAIN_DB_PREFIX.$table;
    					$sql.= " WHERE fk_product = ".$id;
    					dol_syslog(get_class($this).'::delete', LOG_DEBUG);
    					$result = $this->db->query($sql);
    					if (! $result)
    					{
    						$error++;
    						$this->errors[] = $this->db->lasterror();
    					}
    				}
    			}
			}

			// Delete product
			if (! $error)
			{
				$sqlz = "DELETE FROM ".MAIN_DB_PREFIX."product";
				$sqlz.= " WHERE rowid = ".$id;
				dol_syslog(get_class($this).'::delete', LOG_DEBUG);
				$resultz = $this->db->query($sqlz);
				if ( ! $resultz )
				{
					$error++;
					$this->errors[] = $this->db->lasterror();
				}
			}

			if (! $error)
			{
				// We remove directory
				$ref = dol_sanitizeFileName($this->ref);
				if ($conf->product->dir_output)
				{
					$dir = $conf->product->dir_output . "/" . $ref;
					if (file_exists($dir))
					{
						$res=@dol_delete_dir_recursive($dir);
						if (! $res)
						{
							$this->errors[] = 'ErrorFailToDeleteDir';
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
				return -$error;
			}
		}
		else
		{
			$this->error = "ErrorRecordIsUsedCantDelete";
			return 0;
		}
	}

	/**
	 *	Update or add a translation for a product
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function setMultiLangs()
	{
		global $langs;

		$langs_available = $langs->get_available_languages();
		$current_lang = $langs->getDefaultLang();

		foreach ($langs_available as $key => $value)
		{
			if ($key == $current_lang)
			{
				$sql = "SELECT rowid";
				$sql.= " FROM ".MAIN_DB_PREFIX."product_lang";
				$sql.= " WHERE fk_product=".$this->id;
				$sql.= " AND lang='".$key."'";

				$result = $this->db->query($sql);

				if ($this->db->num_rows($result)) // if there is already a description line for this language
				{
					$sql2 = "UPDATE ".MAIN_DB_PREFIX."product_lang";
					$sql2.= " SET label='".$this->db->escape($this->libelle)."',";
					$sql2.= " description='".$this->db->escape($this->description)."',";
					$sql2.= " note='".$this->db->escape($this->note)."'";
					$sql2.= " WHERE fk_product=".$this->id." AND lang='".$key."'";
				}
				else
				{
					$sql2 = "INSERT INTO ".MAIN_DB_PREFIX."product_lang (fk_product, lang, label, description, note)";
					$sql2.= " VALUES(".$this->id.",'".$key."','". $this->db->escape($this->libelle);
					$sql2.= "','".$this->db->escape($this->description);
					$sql2.= "','".$this->db->escape($this->note)."')";
				}
				dol_syslog(get_class($this).'::setMultiLangs');
				if (! $this->db->query($sql2))
				{
					$this->error=$this->db->lasterror();
					return -1;
				}
			}
			else if (isset($this->multilangs[$key]))
			{
				$sql = "SELECT rowid";
				$sql.= " FROM ".MAIN_DB_PREFIX."product_lang";
				$sql.= " WHERE fk_product=".$this->id;
				$sql.= " AND lang='".$key."'";

				$result = $this->db->query($sql);

				if ($this->db->num_rows($result)) // if there is already a description line for this language
				{
					$sql2 = "UPDATE ".MAIN_DB_PREFIX."product_lang";
					$sql2.= " SET label='".$this->db->escape($this->multilangs["$key"]["label"])."',";
					$sql2.= " description='".$this->db->escape($this->multilangs["$key"]["description"])."',";
					$sql2.= " note='".$this->db->escape($this->multilangs["$key"]["note"])."'";
					$sql2.= " WHERE fk_product=".$this->id." AND lang='".$key."'";
				}
				else
				{
					$sql2 = "INSERT INTO ".MAIN_DB_PREFIX."product_lang (fk_product, lang, label, description, note)";
					$sql2.= " VALUES(".$this->id.",'".$key."','". $this->db->escape($this->multilangs["$key"]["label"]);
					$sql2.= "','".$this->db->escape($this->multilangs["$key"]["description"]);
					$sql2.= "','".$this->db->escape($this->multilangs["$key"]["note"])."')";
				}

				// on ne sauvegarde pas des champs vides
				if ( $this->multilangs["$key"]["label"] || $this->multilangs["$key"]["description"] || $this->multilangs["$key"]["note"] )
				dol_syslog(get_class($this).'::setMultiLangs');
				if (! $this->db->query($sql2))
				{
					$this->error=$this->db->lasterror();
					return -1;
				}
			}
			else
			{
				// language is not current language and we didn't provide a multilang description for this language
			}
		}
		return 1;
	}

	/**
	 *	Delete a language for this product
	 *
	 *  @param		string	$langtodelete		Language code to delete
	 *	@return		int							<0 if KO, >0 if OK
	 */
	function delMultiLangs($langtodelete)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_lang";
		$sql.= " WHERE fk_product=".$this->id." AND lang='".$this->db->escape($langtodelete)."'";

		dol_syslog(get_class($this).'::delMultiLangs', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this).'::delMultiLangs error='.$this->error, LOG_ERR);
			return -1;
		}
	}

	/*
	 * Sets an accountancy code for a product.
	 * Also calls PRODUCT_MODIFY trigger when modified
	 *
	 * @param string $type It can be 'buy' or 'sell'
	 * @param string $value Accountancy code
	 * @return int <0 KO >0 OK
	 */
	public function setAccountancyCode($type, $value)
	{
		global $user, $langs, $conf;

		$this->db->begin();

		if ($type == 'buy') {
			$field = 'accountancy_code_buy';
		} elseif ($type == 'sell') {
			$field = 'accountancy_code_sell';
		} else {
			return -1;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET ";
		$sql.= "$field = '".$this->db->escape($value)."'";
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::".__FUNCTION__." sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql)
		{
			// Call triggers
			include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('PRODUCT_MODIFY',$this,$user,$langs,$conf);
			if ($result < 0)
			{
				$this->errors=$interface->errors;
				$this->db->rollback();
				return -1;
			}
			// End call triggers

			$this->$field = $value;

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Load array this->multilangs
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function getMultiLangs()
	{
		global $langs;

		$current_lang = $langs->getDefaultLang();

		$sql = "SELECT lang, label, description, note";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_lang";
		$sql.= " WHERE fk_product=".$this->id;

		$result = $this->db->query($sql);
		if ($result)
		{
			while ($obj = $this->db->fetch_object($result))
			{
				//print 'lang='.$obj->lang.' current='.$current_lang.'<br>';
				if ($obj->lang == $current_lang)  // si on a les traduct. dans la langue courante on les charge en infos principales.
				{
					$this->label		= $obj->label;
					$this->description	= $obj->description;
					$this->note			= $obj->note;

				}
				$this->multilangs["$obj->lang"]["label"]		= $obj->label;
				$this->multilangs["$obj->lang"]["description"]	= $obj->description;
				$this->multilangs["$obj->lang"]["note"]			= $obj->note;
			}
			return 1;
		}
		else
		{
			$this->error="Error: ".$this->db->lasterror()." - ".$sql;
			return -1;
		}
	}



	/**
	 *  Insert a track that we changed a customer price
	 *
	 *	@param  	User	$user       User making change
	 *	@param		int		$level		price level to change
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function _log_price($user,$level=0)
	{
		global $conf;

		$now=dol_now();

		// Add new price
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_price(price_level,date_price,fk_product,fk_user_author,price,price_ttc,price_base_type,tosell,tva_tx,recuperableonly,";
		$sql.= " localtax1_tx, localtax2_tx, price_min,price_min_ttc,price_by_qty,entity,fk_price_expression) ";
		$sql.= " VALUES(".($level?$level:1).", '".$this->db->idate($now)."',".$this->id.",".$user->id.",".$this->price.",".$this->price_ttc.",'".$this->price_base_type."',".$this->status.",".$this->tva_tx.",".$this->tva_npr.",";
		$sql.= " ".$this->localtax1_tx.",".$this->localtax2_tx.",".$this->price_min.",".$this->price_min_ttc.",".$this->price_by_qty.",".$conf->entity.",".($this->fk_price_expression > 0?$this->fk_price_expression:'null');
		$sql.= ")";

		dol_syslog(get_class($this)."_log_price", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if(! $resql)
		{
			$this->error=$this->db->lasterror();
			dol_print_error($this->db);
			return -1;
		}
		else
		{
			return 1;
		}
	}


	/**
	 *  Delete a price line
	 *
	 * 	@param		User	$user	Object user
	 * 	@param		int		$rowid	Line id to delete
	 * 	@return		int				<0 if KO, >0 if OK
	 */
	function log_price_delete($user,$rowid)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_price";
		$sql.= " WHERE rowid=".$rowid;

		dol_syslog(get_class($this)."log_price_delete", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}

	}


	/**
	 *	Read price used by a provider
	 *	We enter as input couple prodfournprice/qty or triplet qty/product_id/fourn_ref
	 *
	 *  @param     	int		$prodfournprice     Id du tarif = rowid table product_fournisseur_price
	 *  @param     	double	$qty                Quantity asked
	 *	@param		int		$product_id			Filter on a particular product id
	 * 	@param		string	$fourn_ref			Filter on a supplier ref
	 *  @return    	int 						<-1 if KO, -1 if qty not enough, 0 si ok mais rien trouve, id_product si ok et trouve. May also initialize some properties like (->ref_supplier, buyprice, fourn_pu, vatrate_supplier...)
	 */
	function get_buyprice($prodfournprice,$qty,$product_id=0,$fourn_ref=0)
	{
		$result = 0;

		// We do select by searching with qty and prodfournprice
		$sql = "SELECT pfp.rowid, pfp.price as price, pfp.quantity as quantity,";
		$sql.= " pfp.fk_product, pfp.ref_fourn, pfp.fk_soc, pfp.tva_tx, pfp.fk_supplier_price_expression";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
		$sql.= " WHERE pfp.rowid = ".$prodfournprice;
		if ($qty) $sql.= " AND pfp.quantity <= ".$qty;

		dol_syslog(get_class($this)."::get_buyprice", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj && $obj->quantity > 0)		// If found
			{
                if (!empty($obj->fk_supplier_price_expression))
                {
					require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
                	$priceparser = new PriceParser($this->db);
                    $price_result = $priceparser->parseProductSupplier($obj->fk_product, $obj->fk_supplier_price_expression, $obj->quantity, $obj->tva_tx);
                    if ($price_result >= 0) {
                    	$obj->price = $price_result;
                    }
                }
				$this->buyprice = $obj->price;                      // \deprecated
				$this->fourn_pu = $obj->price / $obj->quantity;     // Prix unitaire du produit pour le fournisseur $fourn_id
				$this->ref_fourn = $obj->ref_fourn;                 // Ref supplier
				$this->vatrate_supplier = $obj->tva_tx;             // Vat ref supplier
				$result=$obj->fk_product;
				return $result;
			}
			else
			{
				// We do same select again but searching with qty, ref and id product
				$sql = "SELECT pfp.rowid, pfp.price as price, pfp.quantity as quantity, pfp.fk_soc,";
				$sql.= " pfp.fk_product, pfp.ref_fourn as ref_supplier, pfp.tva_tx, pfp.fk_supplier_price_expression";
				$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
				$sql.= " WHERE pfp.ref_fourn = '".$fourn_ref."'";
				$sql.= " AND pfp.fk_product = ".$product_id;
				$sql.= " AND pfp.quantity <= ".$qty;
				$sql.= " ORDER BY pfp.quantity DESC";
				$sql.= " LIMIT 1";

				dol_syslog(get_class($this)."::get_buyprice", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql)
				{
					$obj = $this->db->fetch_object($resql);
					if ($obj && $obj->quantity > 0)		// If found
					{
		                if (!empty($obj->fk_supplier_price_expression))
		                {
							require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
		                	$priceparser = new PriceParser($this->db);
		                    $price_result = $priceparser->parseProductSupplier($obj->fk_product, $obj->fk_supplier_price_expression, $obj->quantity, $obj->tva_tx);
		                    if ($result >= 0) {
		                    	$obj->price = $price_result;
		                    }
		                }
						$this->buyprice = $obj->price;                      // deprecated
						$this->fourn_qty = $obj->quantity;					// min quantity for price
						$this->fourn_pu = $obj->price / $obj->quantity;     // Prix unitaire du produit pour le fournisseur $fourn_id
						$this->ref_fourn = $obj->ref_supplier;              // deprecated
						$this->ref_supplier = $obj->ref_supplier;           // Ref supplier
						$this->vatrate_supplier = $obj->tva_tx;             // Vat ref supplier
						$result=$obj->fk_product;
						return $result;
					}
					else
					{
						return -1;	// Ce produit n'existe pas avec cette ref fournisseur ou existe mais qte insuffisante
					}
				}
				else
				{
					$this->error=$this->db->lasterror();
					return -3;
				}
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -2;
		}
	}


	/**
	 *	Modify price of a product/Service
	 *
	 *	@param  	double	$newprice		New price
	 *	@param  	string	$newpricebase	HT or TTC
	 *	@param  	User	$user        	Object user that make change
	 *	@param  	double	$newvat			New VAT Rate
	 *  @param		double	$newminprice	New price min
	 *  @param		int		$level			0=standard, >0 = level if multilevel prices
	 *  @param     	int		$newnpr         0=Standard vat rate, 1=Special vat rate for French NPR VAT
	 *  @param     	int		$newpsq         1 if it has price by quantity
	 * 	@return		int						<0 if KO, >0 if OK
	 */
	function updatePrice($newprice, $newpricebase, $user, $newvat='',$newminprice='', $level=0, $newnpr=0, $newpsq=0)
	{
		global $conf,$langs;

		$id=$this->id;

		dol_syslog(get_class($this)."update_price id=".$id." newprice=".$newprice." newpricebase=".$newpricebase." newminprice=".$newminprice." level=".$level." npr=".$newnpr);

		// Clean parameters
		if (empty($this->tva_tx))  $this->tva_tx=0;
        if (empty($newnpr)) $newnpr=0;

		// Check parameters
		if ($newvat == '') $newvat=$this->tva_tx;
		if (! empty($newminprice) && ($newminprice > $newprice))
		{
			$this->error='ErrorPriceCantBeLowerThanMinPrice';
			return -1;
		}

		if ($newprice!='' || $newprice==0)
		{
			if ($newpricebase == 'TTC')
			{
				$price_ttc = price2num($newprice,'MU');
				$price = price2num($newprice) / (1 + ($newvat / 100));
				$price = price2num($price,'MU');

				if ($newminprice!='' || $newminprice==0)
				{
					$price_min_ttc = price2num($newminprice,'MU');
					$price_min = price2num($newminprice) / (1 + ($newvat / 100));
					$price_min = price2num($price_min,'MU');
				}
				else
				{
					$price_min=0;
					$price_min_ttc=0;
				}
			}
			else
			{
				$price = price2num($newprice,'MU');
				$price_ttc = ( $newnpr != 1 ) ? price2num($newprice) * (1 + ($newvat / 100)) : $price;
				$price_ttc = price2num($price_ttc,'MU');

				if ($newminprice!='' || $newminprice==0)
				{
					$price_min = price2num($newminprice,'MU');
					$price_min_ttc = price2num($newminprice) * (1 + ($newvat / 100));
					$price_min_ttc = price2num($price_min_ttc,'MU');
					//print 'X'.$newminprice.'-'.$price_min;
				}
				else
				{
					$price_min=0;
					$price_min_ttc=0;
				}
			}
			//print 'x'.$id.'-'.$newprice.'-'.$newpricebase.'-'.$price.'-'.$price_ttc.'-'.$price_min.'-'.$price_min_ttc;


			//Local taxes
			$localtax1=get_localtax($newvat,1);
			$localtax2=get_localtax($newvat,2);
			if (empty($localtax1)) $localtax1=0;	// If = '' then = 0
			if (empty($localtax2)) $localtax2=0;	// If = '' then = 0

			$this->db->begin();

			// Ne pas mettre de quote sur les numeriques decimaux.
			// Ceci provoque des stockages avec arrondis en base au lieu des valeurs exactes.
			$sql = "UPDATE ".MAIN_DB_PREFIX."product SET";
			$sql.= " price_base_type='".$newpricebase."',";
			$sql.= " price=".$price.",";
			$sql.= " price_ttc=".$price_ttc.",";
			$sql.= " price_min=".$price_min.",";
			$sql.= " price_min_ttc=".$price_min_ttc.",";
			$sql.= " localtax1_tx=".($localtax1>=0?$localtax1:'NULL').",";
			$sql.= " localtax2_tx=".($localtax2>=0?$localtax2:'NULL').",";
			$sql.= " tva_tx='".price2num($newvat)."',";
            $sql.= " recuperableonly='".$newnpr."'";
			$sql.= " WHERE rowid = ".$id;

			dol_syslog(get_class($this)."update_price", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->price = $price;
				$this->price_ttc = $price_ttc;
				$this->price_min = $price_min;
				$this->price_min_ttc = $price_min_ttc;
				$this->price_base_type = $newpricebase;
				$this->tva_tx = $newvat;
				$this->tva_npr = $newnpr;
				//Local taxes
				$this->localtax1_tx = $localtax1;
				$this->localtax2_tx = $localtax2;

				// Price by quantity
				$this->price_by_qty = $newpsq;

				$this->_log_price($user,$level);	// Save price for level into table product_price

				$this->level = $level;				// Store level of price edited for trigger

                // Call trigger
                $result=$this->call_trigger('PRODUCT_PRICE_MODIFY',$user);
                if ($result < 0)
                {
                	$this->db->rollback();
                	return -1;
                }
                // End call triggers

                $this->db->commit();
			}
			else
			{
				$this->db->rollback();
			    dol_print_error($this->db);
			}
		}

		return 1;
	}

    /**
     *  Sets the supplier price expression
     *
     *  @param  int     $expression_id	Expression
     *  @return int                 	<0 if KO, >0 if OK
     */
    function setPriceExpression($expression_id)
    {
        global $conf;

        // Clean parameters
        $this->db->begin();
        $expression_id = $expression_id != 0 ? $expression_id : 'NULL';

        $sql = "UPDATE ".MAIN_DB_PREFIX."product";
        $sql.= " SET fk_price_expression = ".$expression_id;
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::setPriceExpression", LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql)
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
	 *  Load a product in memory from database
	 *
	 *  @param	int		$id      			Id of product/service to load
	 *  @param  string	$ref     			Ref of product/service to load
	 *  @param	string	$ref_ext			Ref ext of product/service to load
     *  @param	int		$ignore_expression  Ignores the math expression for calculating price and uses the db value instead
	 *  @return int     					<0 if KO, 0 if not found, >0 if OK
	 */
	function fetch($id='', $ref='', $ref_ext='', $ignore_expression = 0)
	{
	    include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

		global $langs, $conf;

		dol_syslog(get_class($this)."::fetch id=".$id." ref=".$ref." ref_ext=".$ref_ext);

		// Check parameters
		if (! $id && ! $ref && ! $ref_ext)
		{
			$this->error='ErrorWrongParameters';
			dol_print_error(get_class($this)."::fetch ".$this->error);
			return -1;
		}

		$sql = "SELECT rowid, ref, ref_ext, label, description, url, note, customcode, fk_country, price, price_ttc,";
		$sql.= " price_min, price_min_ttc, price_base_type, tva_tx, recuperableonly as tva_npr, localtax1_tx, localtax2_tx, tosell,";
		$sql.= " tobuy, fk_product_type, duration, seuil_stock_alerte, canvas,";
		$sql.= " weight, weight_units, length, length_units, surface, surface_units, volume, volume_units, barcode, fk_barcode_type, finished,";
		$sql.= " accountancy_code_buy, accountancy_code_sell, stock, pmp,";
		$sql.= " datec, tms, import_key, entity, desiredstock, tobatch";
		$sql.= " ,ref_ext, fk_price_expression";
		$sql.= " FROM ".MAIN_DB_PREFIX."product";
		if ($id) $sql.= " WHERE rowid = ".$this->db->escape($id);
		else
		{
			$sql.= " WHERE entity IN (".getEntity($this->element, 1).")";
			if ($ref) $sql.= " AND ref = '".$this->db->escape($ref)."'";
			else if ($ref_ext) $sql.= " AND ref_ext = '".$this->db->escape($ref_ext)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ( $resql )
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$obj = $this->db->fetch_object($resql);

				$this->id						= $obj->rowid;
				$this->ref						= $obj->ref;
				$this->ref_ext					= $obj->ref_ext;
				$this->libelle					= $obj->label;		// TODO deprecated
				$this->label					= $obj->label;
				$this->description				= $obj->description;
				$this->url						= $obj->url;
				$this->note						= $obj->note;

				$this->type						= $obj->fk_product_type;
				$this->status					= $obj->tosell;
				$this->status_buy				= $obj->tobuy;
				$this->status_batch				= $obj->tobatch;

	            $this->customcode				= $obj->customcode;
	            $this->country_id				= $obj->fk_country;
	            $this->country_code				= getCountry($this->country_id,2,$this->db);
	            $this->price					= $obj->price;
				$this->price_ttc				= $obj->price_ttc;
				$this->price_min				= $obj->price_min;
				$this->price_min_ttc			= $obj->price_min_ttc;
				$this->price_base_type			= $obj->price_base_type;
				$this->tva_tx					= $obj->tva_tx;
				//! French VAT NPR
				$this->tva_npr					= $obj->tva_npr;
				//! Spanish local taxes
				$this->localtax1_tx				= $obj->localtax1_tx;
				$this->localtax2_tx				= $obj->localtax2_tx;

				$this->finished					= $obj->finished;
				$this->duration					= $obj->duration;
				$this->duration_value			= substr($obj->duration,0,dol_strlen($obj->duration)-1);
				$this->duration_unit			= substr($obj->duration,-1);
				$this->canvas					= $obj->canvas;
				$this->weight					= $obj->weight;
				$this->weight_units				= $obj->weight_units;
				$this->length					= $obj->length;
				$this->length_units				= $obj->length_units;
				$this->surface					= $obj->surface;
				$this->surface_units			= $obj->surface_units;
				$this->volume					= $obj->volume;
				$this->volume_units				= $obj->volume_units;
				$this->barcode					= $obj->barcode;
				$this->barcode_type				= $obj->fk_barcode_type;

				$this->accountancy_code_buy		= $obj->accountancy_code_buy;
				$this->accountancy_code_sell	= $obj->accountancy_code_sell;

				$this->seuil_stock_alerte		= $obj->seuil_stock_alerte;
				$this->desiredstock             = $obj->desiredstock;
				$this->stock_reel				= $obj->stock;
				$this->pmp						= $obj->pmp;

				$this->date_creation			= $obj->datec;
				$this->date_modification		= $obj->tms;
				$this->import_key				= $obj->import_key;
				$this->entity					= $obj->entity;

				$this->ref_ext					= $obj->ref_ext;
				$this->fk_price_expression		= $obj->fk_price_expression;

				$this->db->free($resql);


				// Retrieve all extrafield for thirdparty
				// fetch optionals attributes and labels
				require_once(DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php');
				$extrafields=new ExtraFields($this->db);
				$extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
				$this->fetch_optionals($this->id,$extralabels);


				// multilangs
				if (! empty($conf->global->MAIN_MULTILANGS)) $this->getMultiLangs();

				// Load multiprices array
				if (! empty($conf->global->PRODUIT_MULTIPRICES))
				{
					for ($i=1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++)
					{
						$sql = "SELECT price, price_ttc, price_min, price_min_ttc,";
						$sql.= " price_base_type, tva_tx, tosell, price_by_qty, rowid, recuperableonly";
						$sql.= " FROM ".MAIN_DB_PREFIX."product_price";
						$sql.= " WHERE entity IN (".getEntity('productprice', 1).")";
						$sql.= " AND price_level=".$i;
						$sql.= " AND fk_product = '".$this->id."'";
						$sql.= " ORDER BY date_price DESC";
						$sql.= " LIMIT 1";
						$resql = $this->db->query($sql);
						if ($resql)
						{
							$result = $this->db->fetch_array($resql);

							$this->multiprices[$i]=$result["price"];
							$this->multiprices_ttc[$i]=$result["price_ttc"];
							$this->multiprices_min[$i]=$result["price_min"];
							$this->multiprices_min_ttc[$i]=$result["price_min_ttc"];
							$this->multiprices_base_type[$i]=$result["price_base_type"];
							$this->multiprices_tva_tx[$i]=$result["tva_tx"];
							$this->multiprices_recuperableonly[$i]=$result["recuperableonly"];

							// Price by quantity
							$this->prices_by_qty[$i]=$result["price_by_qty"];
							$this->prices_by_qty_id[$i]=$result["rowid"];
							// Récuperation de la liste des prix selon qty si flag positionné
							if ($this->prices_by_qty[$i] == 1)
							{
								$sql = "SELECT rowid,price, unitprice, quantity, remise_percent, remise";
								$sql.= " FROM ".MAIN_DB_PREFIX."product_price_by_qty";
								$sql.= " WHERE fk_product_price = '".$this->prices_by_qty_id[$i]."'";
								$sql.= " ORDER BY quantity ASC";
								$resultat=array();
								$resql = $this->db->query($sql);
								if ($resql)
								{
									$ii=0;
									while ($result= $this->db->fetch_array($resql)) {
										$resultat[$ii]=array();
										$resultat[$ii]["rowid"]=$result["rowid"];
										$resultat[$ii]["price"]= $result["price"];
										$resultat[$ii]["unitprice"]= $result["unitprice"];
										$resultat[$ii]["quantity"]= $result["quantity"];
										$resultat[$ii]["remise_percent"]= $result["remise_percent"];
										$resultat[$ii]["remise"]= $result["remise"];
										$ii++;
									}
									$this->prices_by_qty_list[$i]=$resultat;
								}
								else
								{
									dol_print_error($this->db);
									return -1;
								}
							}
						}
						else
						{
							dol_print_error($this->db);
							return -1;
						}
					}
				} else if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))
				{
					$sql = "SELECT price, price_ttc, price_min, price_min_ttc,";
					$sql.= " price_base_type, tva_tx, tosell, price_by_qty, rowid";
					$sql.= " FROM ".MAIN_DB_PREFIX."product_price";
					$sql.= " WHERE fk_product = '".$this->id."'";
					$sql.= " ORDER BY date_price DESC";
					$sql.= " LIMIT 1";
					$resql = $this->db->query($sql);
					if ($resql)
					{
						$result = $this->db->fetch_array($resql);

						// Price by quantity
						$this->prices_by_qty[0]=$result["price_by_qty"];
						$this->prices_by_qty_id[0]=$result["rowid"];
						// Récuperation de la liste des prix selon qty si flag positionné
						if ($this->prices_by_qty[0] == 1)
						{
							$sql = "SELECT rowid,price, unitprice, quantity, remise_percent, remise";
							$sql.= " FROM ".MAIN_DB_PREFIX."product_price_by_qty";
							$sql.= " WHERE fk_product_price = '".$this->prices_by_qty_id[0]."'";
							$sql.= " ORDER BY quantity ASC";
							$resultat=array();
							$resql = $this->db->query($sql);
							if ($resql)
							{
								$ii=0;
								while ($result= $this->db->fetch_array($resql)) {
									$resultat[$ii]=array();
									$resultat[$ii]["rowid"]=$result["rowid"];
									$resultat[$ii]["price"]= $result["price"];
									$resultat[$ii]["unitprice"]= $result["unitprice"];
									$resultat[$ii]["quantity"]= $result["quantity"];
									$resultat[$ii]["remise_percent"]= $result["remise_percent"];
									$resultat[$ii]["remise"]= $result["remise"];
									$ii++;
								}
								$this->prices_by_qty_list[0]=$resultat;
							}
							else
							{
								dol_print_error($this->db);
								return -1;
							}
						}
					}
					else
					{
						dol_print_error($this->db);
						return -1;
					}
				}

                if (!empty($this->fk_price_expression) && empty($ignore_expression))
                {
					require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
                	$priceparser = new PriceParser($this->db);
                    $price_result = $priceparser->parseProduct($this);
                    if ($price_result >= 0)
                    {
                        $this->price = $price_result;
                        //Calculate the VAT
						$this->price_ttc = price2num($this->price) * (1 + ($this->tva_tx / 100));
						$this->price_ttc = price2num($this->price_ttc,'MU');
                    }
                }

				// We should not load stock at each fetch. If someone need stock, he must call load_stock after fetch.
				//$res=$this->load_stock();
				// instead we just init the stock_warehouse array
				$this->stock_warehouse = array();

				return 1;
			}
			else
			{
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
	 *  Charge tableau des stats propale pour le produit/service
	 *
	 *  @param    int	$socid      Id societe
	 *  @return   array       		Tableau des stats
	 */
	function load_stats_propale($socid=0)
	{
		global $conf;
		global $user;

		$sql = "SELECT COUNT(DISTINCT p.fk_soc) as nb_customers, COUNT(DISTINCT p.rowid) as nb,";
		$sql.= " COUNT(pd.rowid) as nb_rows, SUM(pd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."propaldet as pd";
		$sql.= ", ".MAIN_DB_PREFIX."propal as p";
		$sql.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE p.rowid = pd.fk_propal";
		$sql.= " AND p.fk_soc = s.rowid";
		$sql.= " AND p.entity = ".$conf->entity;
		$sql.= " AND pd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		//$sql.= " AND pr.fk_statut != 0";
		if ($socid > 0)	$sql.= " AND p.fk_soc = ".$socid;

		$result = $this->db->query($sql);
		if ( $result )
		{
			$obj=$this->db->fetch_object($result);
			$this->stats_propale['customers']=$obj->nb_customers;
			$this->stats_propale['nb']=$obj->nb;
			$this->stats_propale['rows']=$obj->nb_rows;
			$this->stats_propale['qty']=$obj->qty?$obj->qty:0;
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 *  Charge tableau des stats commande client pour le produit/service
	 *
	 *  @param    int	$socid       	Id societe pour filtrer sur une societe
	 *  @param    int	$filtrestatut   Id statut pour filtrer sur un statut
	 *  @return   array       			Array of stats (nb=nb of order, qty=qty ordered)
	 */
	function load_stats_commande($socid=0,$filtrestatut='')
	{
		global $conf,$user;

		$sql = "SELECT COUNT(DISTINCT c.fk_soc) as nb_customers, COUNT(DISTINCT c.rowid) as nb,";
		$sql.= " COUNT(cd.rowid) as nb_rows, SUM(cd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd";
		$sql.= ", ".MAIN_DB_PREFIX."commande as c";
		$sql.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.rowid = cd.fk_commande";
		$sql.= " AND c.fk_soc = s.rowid";
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " AND cd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)	$sql.= " AND c.fk_soc = ".$socid;
		if ($filtrestatut <> '') $sql.= " AND c.fk_statut in (".$filtrestatut.")";

		$result = $this->db->query($sql);
		if ( $result )
		{
			$obj=$this->db->fetch_object($result);
			$this->stats_commande['customers']=$obj->nb_customers;
			$this->stats_commande['nb']=$obj->nb;
			$this->stats_commande['rows']=$obj->nb_rows;
			$this->stats_commande['qty']=$obj->qty?$obj->qty:0;
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *  Charge tableau des stats commande fournisseur pour le produit/service
	 *
	 *  @param    int		$socid       	Id societe pour filtrer sur une societe
	 *  @param    string	$filtrestatut  	Id des statuts pour filtrer sur des statuts
	 *  @return   array       				Tableau des stats
	 */
	function load_stats_commande_fournisseur($socid=0,$filtrestatut='')
	{
		global $conf,$user;

		$sql = "SELECT COUNT(DISTINCT c.fk_soc) as nb_suppliers, COUNT(DISTINCT c.rowid) as nb,";
		$sql.= " COUNT(cd.rowid) as nb_rows, SUM(cd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd";
		$sql.= ", ".MAIN_DB_PREFIX."commande_fournisseur as c";
		$sql.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.rowid = cd.fk_commande";
		$sql.= " AND c.fk_soc = s.rowid";
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " AND cd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0) $sql.= " AND c.fk_soc = ".$socid;
		if ($filtrestatut != '') $sql.= " AND c.fk_statut in (".$filtrestatut.")"; // Peut valoir 0

		$result = $this->db->query($sql);
		if ( $result )
		{
			$obj=$this->db->fetch_object($result);
			$this->stats_commande_fournisseur['suppliers']=$obj->nb_suppliers;
			$this->stats_commande_fournisseur['nb']=$obj->nb;
			$this->stats_commande_fournisseur['rows']=$obj->nb_rows;
			$this->stats_commande_fournisseur['qty']=$obj->qty?$obj->qty:0;
			return 1;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
		}
	}

	/**
	 *  Charge tableau des stats expedition client pour le produit/service
	 *
	 *  @param    int	$socid       	Id societe pour filtrer sur une societe
	 *  @param    int	$filtrestatut  	Id statut pour filtrer sur un statut
	 *  @return   array       			Tableau des stats
	 */
	function load_stats_sending($socid=0,$filtrestatut='')
	{
		global $conf,$user;

		$sql = "SELECT COUNT(DISTINCT e.fk_soc) as nb_customers, COUNT(DISTINCT e.rowid) as nb,";
		$sql.= " COUNT(ed.rowid) as nb_rows, SUM(ed.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."expeditiondet as ed";
		$sql.= ", ".MAIN_DB_PREFIX."commandedet as cd";
		$sql.= ", ".MAIN_DB_PREFIX."commande as c";
		$sql.= ", ".MAIN_DB_PREFIX."expedition as e";
		$sql.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE e.rowid = ed.fk_expedition";
		$sql.= " AND c.rowid = cd.fk_commande";
		$sql.= " AND e.fk_soc = s.rowid";
		$sql.= " AND e.entity = ".$conf->entity;
		$sql.= " AND ed.fk_origin_line = cd.rowid";
		$sql.= " AND cd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND e.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)	$sql.= " AND e.fk_soc = ".$socid;
		if ($filtrestatut <> '') $sql.= " AND c.fk_statut in (".$filtrestatut.")";

		$result = $this->db->query($sql);
		if ( $result )
		{
			$obj=$this->db->fetch_object($result);
			$this->stats_expedition['customers']=$obj->nb_customers;
			$this->stats_expedition['nb']=$obj->nb;
			$this->stats_expedition['rows']=$obj->nb_rows;
			$this->stats_expedition['qty']=$obj->qty?$obj->qty:0;
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *  Charge tableau des stats réception fournisseur pour le produit/service
	 *
	 *  @param    int	$socid       	Id societe pour filtrer sur une societe
	 *  @param    int	$filtrestatut  	Id statut pour filtrer sur un statut
	 *  @return   array       			Tableau des stats
	 */
	function load_stats_reception($socid=0,$filtrestatut='')
	{
		global $conf,$user;

		$sql = "SELECT COUNT(DISTINCT cf.fk_soc) as nb_customers, COUNT(DISTINCT cf.rowid) as nb,";
		$sql.= " COUNT(fd.rowid) as nb_rows, SUM(fd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as fd";
		$sql.= ", ".MAIN_DB_PREFIX."commande_fournisseur as cf";
		$sql.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE cf.rowid = fd.fk_commande";
		$sql.= " AND cf.fk_soc = s.rowid";
		$sql.= " AND cf.entity = ".$conf->entity;
		$sql.= " AND fd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND cf.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)	$sql.= " AND cf.fk_soc = ".$socid;
		if ($filtrestatut <> '') $sql.= " AND cf.fk_statut in (".$filtrestatut.")";

		$result = $this->db->query($sql);
		if ( $result )
		{
			$obj=$this->db->fetch_object($result);
			$this->stats_reception['suppliers']=$obj->nb_customers;
			$this->stats_reception['nb']=$obj->nb;
			$this->stats_reception['rows']=$obj->nb_rows;
			$this->stats_reception['qty']=$obj->qty?$obj->qty:0;
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *  Charge tableau des stats contrat pour le produit/service
	 *
	 *  @param    int	$socid      Id societe
	 *  @return   array       		Tableau des stats
	 */
	function load_stats_contrat($socid=0)
	{
		global $conf;
		global $user;

		$sql = "SELECT COUNT(DISTINCT c.fk_soc) as nb_customers, COUNT(DISTINCT c.rowid) as nb,";
		$sql.= " COUNT(cd.rowid) as nb_rows, SUM(cd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
		$sql.= ", ".MAIN_DB_PREFIX."contrat as c";
		$sql.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.rowid = cd.fk_contrat";
		$sql.= " AND c.fk_soc = s.rowid";
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " AND cd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		//$sql.= " AND c.statut != 0";
		if ($socid > 0)	$sql.= " AND c.fk_soc = ".$socid;

		$result = $this->db->query($sql);
		if ( $result )
		{
			$obj=$this->db->fetch_object($result);
			$this->stats_contrat['customers']=$obj->nb_customers;
			$this->stats_contrat['nb']=$obj->nb;
			$this->stats_contrat['rows']=$obj->nb_rows;
			$this->stats_contrat['qty']=$obj->qty?$obj->qty:0;
			return 1;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
		}
	}

	/**
	 *  Charge tableau des stats facture pour le produit/service
	 *
	 *  @param    int		$socid      Id societe
	 *  @return   array       			Tableau des stats
	 */
	function load_stats_facture($socid=0)
	{
		global $conf;
		global $user;

		$sql = "SELECT COUNT(DISTINCT f.fk_soc) as nb_customers, COUNT(DISTINCT f.rowid) as nb,";
		$sql.= " COUNT(fd.rowid) as nb_rows, SUM(fd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."facturedet as fd";
		$sql.= ", ".MAIN_DB_PREFIX."facture as f";
		$sql.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE f.rowid = fd.fk_facture";
		$sql.= " AND f.fk_soc = s.rowid";
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND fd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		//$sql.= " AND f.fk_statut != 0";
		if ($socid > 0)	$sql .= " AND f.fk_soc = ".$socid;

		$result = $this->db->query($sql);
		if ( $result )
		{
			$obj=$this->db->fetch_object($result);
			$this->stats_facture['customers']=$obj->nb_customers;
			$this->stats_facture['nb']=$obj->nb;
			$this->stats_facture['rows']=$obj->nb_rows;
			$this->stats_facture['qty']=$obj->qty?$obj->qty:0;
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *  Charge tableau des stats facture pour le produit/service
	 *
	 *  @param    int		$socid      Id societe
	 *  @return   array       			Tableau des stats
	 */
	function load_stats_facture_fournisseur($socid=0)
	{
		global $conf;
		global $user;

		$sql = "SELECT COUNT(DISTINCT f.fk_soc) as nb_suppliers, COUNT(DISTINCT f.rowid) as nb,";
		$sql.= " COUNT(fd.rowid) as nb_rows, SUM(fd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn_det as fd";
		$sql.= ", ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE f.rowid = fd.fk_facture_fourn";
		$sql.= " AND f.fk_soc = s.rowid";
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND fd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		//$sql.= " AND f.fk_statut != 0";
		if ($socid > 0)	$sql .= " AND f.fk_soc = ".$socid;

		$result = $this->db->query($sql);
		if ( $result )
		{
			$obj=$this->db->fetch_object($result);
			$this->stats_facture_fournisseur['suppliers']=$obj->nb_suppliers;
			$this->stats_facture_fournisseur['nb']=$obj->nb;
			$this->stats_facture_fournisseur['rows']=$obj->nb_rows;
			$this->stats_facture_fournisseur['qty']=$obj->qty?$obj->qty:0;
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *  Return an array formated for showing graphs
	 *
	 *  @param		string	$sql        Request to execute
	 *  @param		string	$mode		'byunit'=number of unit, 'bynumber'=nb of entities
	 *  @return   	array       		<0 if KO, result[month]=array(valuex,valuey) where month is 0 to 11
	 */
	function _get_stats($sql,$mode)
	{
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$arr = $this->db->fetch_array($resql);
				if ($mode == 'byunit')   $tab[$arr[1]] = $arr[0];	// 1st field
				if ($mode == 'bynumber') $tab[$arr[1]] = $arr[2];	// 3rd field
				$i++;
			}
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
		}

		$year = strftime('%Y',time());
		$month = strftime('%m',time());
		$result = array();

		for ($j = 0 ; $j < 12 ; $j++)
		{
			$idx=ucfirst(dol_trunc(dol_print_date(dol_mktime(12,0,0,$month,1,$year),"%b"),3,'right','UTF-8',1));
			$monthnum=sprintf("%02s",$month);

			$result[$j] = array($idx,isset($tab[$year.$month])?$tab[$year.$month]:0);
			//            $result[$j] = array($monthnum,isset($tab[$year.$month])?$tab[$year.$month]:0);

			$month = "0".($month - 1);
			if (dol_strlen($month) == 3)
			{
				$month = substr($month,1);
			}
			if ($month == 0)
			{
				$month = 12;
				$year = $year - 1;
			}
		}

		return array_reverse($result);
	}


	/**
	 *  Return nb of units or customers invoices in which product is included
	 *
	 *  @param  	int		$socid      Limit count on a particular third party id
	 *  @param		string	$mode		'byunit'=number of unit, 'bynumber'=nb of entities
	 * 	@return   	array       		<0 if KO, result[month]=array(valuex,valuey) where month is 0 to 11
	 */
	function get_nb_vente($socid,$mode)
	{
		global $conf;
		global $user;

		$sql = "SELECT sum(d.qty), date_format(f.datef, '%Y%m')";
		if ($mode == 'bynumber') $sql.= ", count(DISTINCT f.rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."facturedet as d, ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE f.rowid = d.fk_facture";
		$sql.= " AND d.fk_product =".$this->id;
		$sql.= " AND f.fk_soc = s.rowid";
		$sql.= " AND f.entity = ".$conf->entity;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)	$sql.= " AND f.fk_soc = $socid";
		$sql.= " GROUP BY date_format(f.datef,'%Y%m')";
		$sql.= " ORDER BY date_format(f.datef,'%Y%m') DESC";

		return $this->_get_stats($sql,$mode);
	}


	/**
	 *  Return nb of units or supplier invoices in which product is included
	 *
	 *  @param  	int		$socid      Limit count on a particular third party id
	 * 	@param		string	$mode		'byunit'=number of unit, 'bynumber'=nb of entities
	 * 	@return   	array       		<0 if KO, result[month]=array(valuex,valuey) where month is 0 to 11
	 */
	function get_nb_achat($socid,$mode)
	{
		global $conf;
		global $user;

		$sql = "SELECT sum(d.qty), date_format(f.datef, '%Y%m')";
		if ($mode == 'bynumber') $sql.= ", count(DISTINCT f.rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn_det as d, ".MAIN_DB_PREFIX."facture_fourn as f, ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE f.rowid = d.fk_facture_fourn";
		$sql.= " AND d.fk_product =".$this->id;
		$sql.= " AND f.fk_soc = s.rowid";
		$sql.= " AND f.entity = ".$conf->entity;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)	$sql.= " AND f.fk_soc = $socid";
		$sql.= " GROUP BY date_format(f.datef,'%Y%m')";
		$sql.= " ORDER BY date_format(f.datef,'%Y%m') DESC";

		$resarray=$this->_get_stats($sql,$mode);
		return $resarray;
	}

	/**
	 *  Return nb of units or proposals in which product is included
	 *
	 *  @param  	int		$socid      Limit count on a particular third party id
	 * 	@param		string	$mode		'byunit'=number of unit, 'bynumber'=nb of entities
	 * 	@return   	array       		<0 if KO, result[month]=array(valuex,valuey) where month is 0 to 11
	 */
	function get_nb_propal($socid,$mode)
	{
		global $conf;
		global $user;

		$sql = "SELECT sum(d.qty), date_format(p.datep, '%Y%m')";
		if ($mode == 'bynumber') $sql.= ", count(DISTINCT p.rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."propaldet as d, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE p.rowid = d.fk_propal";
		$sql.= " AND d.fk_product =".$this->id;
		$sql.= " AND p.fk_soc = s.rowid";
		$sql.= " AND p.entity = ".$conf->entity;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)	$sql.= " AND p.fk_soc = ".$socid;
		$sql.= " GROUP BY date_format(p.datep,'%Y%m')";
		$sql.= " ORDER BY date_format(p.datep,'%Y%m') DESC";

		return $this->_get_stats($sql,$mode);
	}

	/**
	 *  Return nb of units or orders in which product is included
	 *
	 *  @param  	int		$socid      Limit count on a particular third party id
	 *  @param		string	$mode		'byunit'=number of unit, 'bynumber'=nb of entities
	 * 	@return   	array       		<0 if KO, result[month]=array(valuex,valuey) where month is 0 to 11
	 */
	function get_nb_order($socid,$mode)
	{
		global $conf, $user;

		$sql = "SELECT sum(d.qty), date_format(c.date_commande, '%Y%m')";
		if ($mode == 'bynumber') $sql.= ", count(DISTINCT c.rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as d, ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.rowid = d.fk_commande";
		$sql.= " AND d.fk_product =".$this->id;
		$sql.= " AND c.fk_soc = s.rowid";
		$sql.= " AND c.entity = ".$conf->entity;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)	$sql.= " AND c.fk_soc = ".$socid;
		$sql.= " GROUP BY date_format(c.date_commande,'%Y%m')";
		$sql.= " ORDER BY date_format(c.date_commande,'%Y%m') DESC";

		return $this->_get_stats($sql,$mode);
	}

	/**
	 *  Return nb of units or orders in which product is included
	 *
	 *  @param  	int		$socid      Limit count on a particular third party id
	 *  @param		string	$mode		'byunit'=number of unit, 'bynumber'=nb of entities
	 * 	@return   	array       		<0 if KO, result[month]=array(valuex,valuey) where month is 0 to 11
	 */
	function get_nb_ordersupplier($socid,$mode)
	{
		global $conf, $user;

		$sql = "SELECT sum(d.qty), date_format(c.date_commande, '%Y%m')";
		if ($mode == 'bynumber') $sql.= ", count(DISTINCT c.rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as d, ".MAIN_DB_PREFIX."commande_fournisseur as c, ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->fournisseur->lire && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.rowid = d.fk_commande";
		$sql.= " AND d.fk_product =".$this->id;
		$sql.= " AND c.fk_soc = s.rowid";
		$sql.= " AND c.entity = ".$conf->entity;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)	$sql.= " AND c.fk_soc = ".$socid;
		$sql.= " GROUP BY date_format(c.date_commande,'%Y%m')";
		$sql.= " ORDER BY date_format(c.date_commande,'%Y%m') DESC";

		return $this->_get_stats($sql,$mode);
	}

	/**
	 *  Link a product/service to a parent product/service
	 *
	 *  @param      int	$id_pere    Id of parent product/service
	 *  @param      int	$id_fils    Id of child product/service
	 *  @param		int	$qty		Quantity
	 *  @param		int	$incdec		1=Increase/decrease stock of child when parent stock increase/decrease
	 *  @return     int        		< 0 if KO, > 0 if OK
	 */
	function add_sousproduit($id_pere, $id_fils, $qty, $incdec=1)
	{
		// Clean parameters
		if (! is_numeric($id_pere)) $id_pere=0;
		if (! is_numeric($id_fils)) $id_fils=0;
		if (! is_numeric($incdec)) $incdec=0;

		$result=$this->del_sousproduit($id_pere, $id_fils);
		if ($result < 0) return $result;

		// Check not already father of id_pere (to avoid father -> child -> father links)
		$sql = 'SELECT fk_product_pere from '.MAIN_DB_PREFIX.'product_association';
		$sql .= ' WHERE fk_product_pere  = '.$id_fils.' AND fk_product_fils = '.$id_pere;
		if (! $this->db->query($sql))
		{
			dol_print_error($this->db);
			return -1;
		}
		else
		{
			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				if($num > 0)
				{
					$this->error="isFatherOfThis";
					return -1;
				}
				else
				{
					$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'product_association(fk_product_pere,fk_product_fils,qty,incdec)';
					$sql .= ' VALUES ('.$id_pere.', '.$id_fils.', '.$qty.', '.$incdec.')';
					if (! $this->db->query($sql))
					{
						dol_print_error($this->db);
						return -1;
					}
					else
					{
						return 1;
					}
				}
			}
		}
	}

	/**
	 *  Modify composed product
	 *
	 *  @param      int	$id_pere    Id of parent product/service
	 *  @param      int	$id_fils    Id of child product/service
	 *  @param		int	$qty		Quantity
	 *  @param		int	$incdec		1=Increase/decrease stock of child when parent stock increase/decrease
	 * 	@return     int        		< 0 if KO, > 0 if OK
	 */
	function update_sousproduit($id_pere, $id_fils, $qty, $incdec=1)
	{
		// Clean parameters
		if (! is_numeric($id_pere)) $id_pere=0;
		if (! is_numeric($id_fils)) $id_fils=0;
		if (! is_numeric($incdec)) $incdec=1;
		if (! is_numeric($qty)) $qty=1;

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'product_association SET ';
		$sql.= 'qty='.$qty;
		$sql.= ',incdec='.$incdec;
		$sql .= ' WHERE fk_product_pere='.$id_pere.' AND fk_product_fils='.$id_fils;

		if (!$this->db->query($sql))
		{
			dol_print_error($this->db);
			return -1;
		}
		else
		{
			return 1;
		}

	}

	/**
	 *  Retire le lien entre un sousproduit et un produit/service
	 *
	 *  @param      int	$fk_parent		Id du produit auquel ne sera plus lie le produit lie
	 *  @param      int	$fk_child		Id du produit a ne plus lie
	 *  @return     int			    	< 0 if KO, > 0 if OK
	 */
	function del_sousproduit($fk_parent, $fk_child)
	{
		if (! is_numeric($fk_parent)) $fk_parent=0;
		if (! is_numeric($fk_child)) $fk_child=0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_association";
		$sql.= " WHERE fk_product_pere  = ".$fk_parent;
		$sql.= " AND fk_product_fils = ".$fk_child;

		dol_syslog(get_class($this).'::del_sousproduit', LOG_DEBUG);
		if (! $this->db->query($sql))
		{
			dol_print_error($this->db);
			return -1;
		}

		return 1;
	}

	/**
	 *  Verifie si c'est un sous-produit
	 *
	 *  @param      int	$fk_parent		Id du produit auquel le produit est lie
	 *  @param      int	$fk_child		Id du produit lie
	 *  @return     int			    	< 0 si erreur, > 0 si ok
	 */
	function is_sousproduit($fk_parent, $fk_child)
	{
		$sql = "SELECT fk_product_pere, qty, incdec";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_association";
		$sql.= " WHERE fk_product_pere  = '".$fk_parent."'";
		$sql.= " AND fk_product_fils = '".$fk_child."'";

		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);

			if($num > 0)
			{
				$obj = $this->db->fetch_object($result);
				$this->is_sousproduit_qty = $obj->qty;
				$this->is_sousproduit_incdec = $obj->incdec;

				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *  Add a supplier price for the product.
	 *  Note: Duplicate ref is accepted for different quantity only, or for different companies.
	 *
	 *  @param      User	$user       User that make link
	 *  @param      int		$id_fourn   Supplier id
	 *  @param      string	$ref_fourn  Supplier ref
	 *  @param		float	$quantity	Quantity minimum for price
	 *  @return     int         		< 0 if KO, 0 if link already exists for this product, > 0 if OK
	 */
	function add_fournisseur($user, $id_fourn, $ref_fourn, $quantity)
	{
		global $conf;

		$now=dol_now();

		if ($ref_fourn)
		{
    		$sql = "SELECT rowid, fk_product";
    		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
    		$sql.= " WHERE fk_soc = ".$id_fourn;
    		$sql.= " AND ref_fourn = '".$ref_fourn."'";
    		$sql.= " AND fk_product != ".$this->id;
    		$sql.= " AND entity = ".$conf->entity;

    		dol_syslog(get_class($this)."::add_fournisseur", LOG_DEBUG);
    		$resql=$this->db->query($sql);
    		if ($resql)
    		{
    			$obj = $this->db->fetch_object($resql);
                if ($obj)
                {
        			// If the supplier ref already exists but for another product (duplicate ref is accepted for different quantity only or different companies)
                    $this->product_id_already_linked = $obj->fk_product;
    				return -3;
    			}
                $this->db->free($resql);
    		}
		}

		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
		$sql.= " WHERE fk_soc = ".$id_fourn;
		if ($ref_fourn) $sql.= " AND ref_fourn = '".$ref_fourn."'";
		else $sql.= " AND (ref_fourn = '' OR ref_fourn IS NULL)";
		$sql.= " AND quantity = '".$quantity."'";
		$sql.= " AND fk_product = ".$this->id;
		$sql.= " AND entity = ".$conf->entity;

		dol_syslog(get_class($this)."::add_fournisseur", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
    		$obj = $this->db->fetch_object($resql);

		    // The reference supplier does not exist, we create it for this product.
			if (! $obj)
			{
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price(";
				$sql.= "datec";
				$sql.= ", entity";
				$sql.= ", fk_product";
				$sql.= ", fk_soc";
				$sql.= ", ref_fourn";
				$sql.= ", quantity";
				$sql.= ", fk_user";
				$sql.= ", tva_tx";
				$sql.= ") VALUES (";
				$sql.= "'".$this->db->idate($now)."'";
				$sql.= ", ".$conf->entity;
				$sql.= ", ".$this->id;
				$sql.= ", ".$id_fourn;
				$sql.= ", '".$ref_fourn."'";
				$sql.= ", ".$quantity;
				$sql.= ", ".$user->id;
				$sql.= ", 0";
				$sql.= ")";

				dol_syslog(get_class($this)."::add_fournisseur", LOG_DEBUG);
				if ($this->db->query($sql))
				{
					$this->product_fourn_price_id = $this->db->last_insert_id(MAIN_DB_PREFIX."product_fournisseur_price");
					return 1;
				}
				else
				{
					$this->error=$this->db->lasterror();
					return -1;
				}
			}
			// If the supplier price already exists for this product and quantity
			else
			{
				$this->product_fourn_price_id = $obj->rowid;
				return 0;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -2;
		}
	}


	/**
	 *  Renvoie la liste des fournisseurs du produit/service
	 *
	 *  @return 	array		Tableau des id de fournisseur
	 */
	function list_suppliers()
	{
		global $conf;

		$list = array();

		$sql = "SELECT p.fk_soc";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as p";
		$sql.= " WHERE p.fk_product = ".$this->id;
		$sql.= " AND p.entity = ".$conf->entity;

		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				$list[$i] = $obj->fk_soc;
				$i++;
			}
		}

		return $list;
	}

	/**
	 *  Recopie les prix d'un produit/service sur un autre
	 *
	 *  @param	int		$fromId     Id product source
	 *  @param  int		$toId       Id product target
	 *  @return nt         			< 0 if KO, > 0 if OK
	 */
	function clone_price($fromId, $toId)
	{
		$this->db->begin();

		// les prix
		$sql = "INSERT ".MAIN_DB_PREFIX."product_price (";
		$sql.= " fk_product, date_price, price, tva_tx, localtax1_tx, localtax2_tx, fk_user_author, tosell)";
		$sql.= " SELECT ".$toId . ", date_price, price, tva_tx, localtax1_tx, localtax2_tx, fk_user_author, tosell";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_price ";
		$sql.= " WHERE fk_product = ". $fromId;

		dol_syslog(get_class($this).'::clone_price', LOG_DEBUG);
		if (! $this->db->query($sql))
		{
			$this->db->rollback();
			return -1;
		}
		$this->db->commit();
		return 1;
	}

	/**
	 * Clone links between products
	 *
	 * @param 	int		$fromId		Product id
	 * @param 	int		$toId		Product id
	 * @return number
	 */
	function clone_associations($fromId, $toId)
	{
		$this->db->begin();

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'product_association (rowid, fk_product_pere, fk_product_fils, qty)';
		$sql.= " SELECT null, $toId, fk_product_fils, qty FROM ".MAIN_DB_PREFIX."product_association";
		$sql.= " WHERE fk_product_pere = '".$fromId."'";

		dol_syslog(get_class($this).'::clone_association', LOG_DEBUG);
		if (! $this->db->query($sql))
		{
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return 1;
	}

	/**
	 *  Recopie les fournisseurs et prix fournisseurs d'un produit/service sur un autre
	 *
	 *  @param    int	$fromId      Id produit source
	 *  @param    int	$toId        Id produit cible
	 *  @return   int    		     < 0 si erreur, > 0 si ok
	 */
	function clone_fournisseurs($fromId, $toId)
	{
		$this->db->begin();

		$now=dol_now();

		// les fournisseurs
		/*$sql = "INSERT ".MAIN_DB_PREFIX."product_fournisseur ("
		. " datec, fk_product, fk_soc, ref_fourn, fk_user_author )"
		. " SELECT '".$this->db->idate($now)."', ".$toId.", fk_soc, ref_fourn, fk_user_author"
		. " FROM ".MAIN_DB_PREFIX."product_fournisseur"
		. " WHERE fk_product = ".$fromId;

		if ( ! $this->db->query($sql ) )
		{
			$this->db->rollback();
			return -1;
		}*/

		// les prix de fournisseurs.
		$sql = "INSERT ".MAIN_DB_PREFIX."product_fournisseur_price (";
		$sql.= " datec, fk_product, fk_soc, price, quantity, fk_user)";
		$sql.= " SELECT '".$this->db->idate($now)."', ".$toId. ", fk_soc, price, quantity, fk_user";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
		$sql.= " WHERE fk_product = ".$fromId;

		dol_syslog(get_class($this).'::clone_fournisseurs', LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (! $resql)
		{
			$this->db->rollback();
			return -1;
		}
		else
		{
		    $this->db->commit();
		    return 1;
		}
	}

	/**
	 *  Fonction recursive uniquement utilisee par get_arbo_each_prod, recompose l'arborescence des sousproduits
	 * 	Define value of this->res
	 *
	 *	@param		array		$prod			Products array
	 *	@param		string		$compl_path		Directory path of parents to add before
	 *	@param		int			$multiply		Because each sublevel must be multiplicated by parent nb
	 *	@param		int			$level			Init level
	 *  @param		int			$id_parent		Id parent
	 *  @return 	void
	 */
	function fetch_prod_arbo($prod, $compl_path="", $multiply=1, $level=1, $id_parent=0)
	{
		global $conf,$langs;

		$product = new Product($this->db);
		//var_dump($prod);
		foreach($prod as $id_product => $desc_pere)	// $id_product is 0 (first call starting with root top) or an id of a sub_product
		{
			if (is_array($desc_pere))	// If desc_pere is an array, this means it's a child
			{
				$id=(! empty($desc_pere[0]) ? $desc_pere[0] :'');
				$nb=(! empty($desc_pere[1]) ? $desc_pere[1] :'');
				$type=(! empty($desc_pere[2]) ? $desc_pere[2] :'');
				$label=(! empty($desc_pere[3]) ? $desc_pere[3] :'');
				$incdec=!empty($desc_pere[4]) ? $desc_pere[4] : 0;

				if ($multiply < 1) $multiply=1;

				//print "XXX We add id=".$id." - label=".$label." - nb=".$nb." - multiply=".$multiply." fullpath=".$compl_path.$label."\n";
				$this->fetch($id);		// Load product
				$this->load_stock();	// Load stock
				$this->res[]= array(
					'id'=>$id,					// Id product
					'id_parent'=>$id_parent,
					'ref'=>$this->ref,			// Ref product
					'nb'=>$nb,					// Nb of units that compose parent product
					'nb_total'=>$nb*$multiply,	// Nb of units for all nb of product
					'stock'=>$this->stock_reel,	// Stock
					'stock_alert'=>$this->seuil_stock_alerte,	// Stock alert
					'label'=>$label,
					'fullpath'=>$compl_path.$label,			// Label
					'type'=>$type,				// Nb of units that compose parent product
					'desiredstock'=>$this->desiredstock,
					'level'=>$level,
					'incdec'=>$incdec
				);

				// Recursive call if there is childs to child
				if (is_array($desc_pere['childs']))
				{
					//print 'YYY We go down for '.$desc_pere[3]." -> \n";
					$this->fetch_prod_arbo($desc_pere['childs'], $compl_path.$desc_pere[3]." -> ", $desc_pere[1]*$multiply, $level+1, $id);
				}
			}
		}
	}

	/**
	 *  fonction recursive uniquement utilisee par get_each_prod, ajoute chaque sousproduits dans le tableau res
	 *
	 *	@param	array	$prod	Products array
	 *  @return void
	 */
	function fetch_prods($prod)
	{
		$this->res;
		foreach($prod as $nom_pere => $desc_pere)
		{
			// on est dans une sous-categorie
			if(is_array($desc_pere))
			$this->res[]= array($desc_pere[1],$desc_pere[0]);
			if(count($desc_pere) >1)
			{
				$this->fetch_prods($desc_pere);
			}
		}
	}

	/**
	 *  reconstruit l'arborescence des categories sous la forme d'un tableau
	 *
	 *	@param		int		$multiply		Because each sublevel must be multiplicated by parent nb
	 *  @return 	array 					$this->res
	 */
	function get_arbo_each_prod($multiply=1)
	{
		$this->res = array();
		if (isset($this->sousprods) && is_array($this->sousprods))
		{
			foreach($this->sousprods as $prod_name => $desc_product)
			{
				if (is_array($desc_product)) $this->fetch_prod_arbo($desc_product,"",$multiply,1,$this->id);
			}
		}
		//var_dump($this->res);
		return $this->res;
	}

	/**
	 *  Renvoie tous les sousproduits dans le tableau res, chaque ligne de res contient : id -> qty
	 *
	 *  @return array $this->res
	 */
	function get_each_prod()
	{
		$this->res = array();
		if (is_array($this->sousprods))
		{
			foreach($this->sousprods as $nom_pere => $desc_pere)
			{
				if (count($desc_pere) >1) $this->fetch_prods($desc_pere);
			}
			sort($this->res);
		}
		return $this->res;
	}


	/**
	 *  Return all Father products fo current product
	 *
	 *  @return 	array prod
	 *  @see		getParent
	 */
	function getFather()
	{
		$sql = "SELECT p.label as label,p.rowid,pa.fk_product_pere as id,p.fk_product_type";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_association as pa,";
		$sql.= " ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE p.rowid = pa.fk_product_pere";
		$sql.= " AND pa.fk_product_fils=".$this->id;

		$res = $this->db->query($sql);
		if ($res)
		{
			$prods = array ();
			while ($record = $this->db->fetch_array($res))
			{
				$prods[$record['id']]['id'] =  $record['rowid'];
				$prods[$record['id']]['label'] =  $this->db->escape($record['label']);
				$prods[$record['id']]['fk_product_type'] =  $record['fk_product_type'];
			}
			return $prods;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *  Return all direct parent products fo current product
	 *
	 *  @return 	array prod
	 *  @see		getFather
	 */
	function getParent()
	{
		$sql = "SELECT p.label as label,p.rowid,pa.fk_product_pere as id,p.fk_product_type";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_association as pa,";
		$sql.= " ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE p.rowid = pa.fk_product_pere";
		$sql.= " AND p.rowid = ".$this->id;

		$res = $this->db->query($sql);
		if ($res)
		{
			$prods = array ();
			while ($record = $this->db->fetch_array($res))
			{
				$prods[$this->db->escape($record['label'])] = array(0=>$record['id']);
			}
			return $prods;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Return childs of product $id
	 *
	 * 	@param		int		$id					Id of product to search childs of
	 *  @param		int		$firstlevelonly		Return only direct child
	 *  @return     array       				Prod
	 */
	function getChildsArbo($id, $firstlevelonly=0)
	{
		$sql = "SELECT p.rowid, p.label as label, pa.qty as qty, pa.fk_product_fils as id, p.fk_product_type, pa.incdec";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql.= ", ".MAIN_DB_PREFIX."product_association as pa";
		$sql.= " WHERE p.rowid = pa.fk_product_fils";
		$sql.= " AND pa.fk_product_pere = ".$id;
		$sql.= " AND pa.fk_product_fils != ".$id;	// This should not happens, it is to avoid infinite loop if it happens

		dol_syslog(get_class($this).'::getChildsArbo', LOG_DEBUG);
		$res  = $this->db->query($sql);
		if ($res)
		{
			$prods = array();
			while ($rec = $this->db->fetch_array($res))
			{
				$prods[$rec['rowid']]= array(
					0=>$rec['id'],
					1=>$rec['qty'],
					2=>$rec['fk_product_type'],
					3=>$this->db->escape($rec['label']),
					4=>$rec['incdec']
				);
				//$prods[$this->db->escape($rec['label'])]= array(0=>$rec['id'],1=>$rec['qty'],2=>$rec['fk_product_type']);
				//$prods[$this->db->escape($rec['label'])]= array(0=>$rec['id'],1=>$rec['qty']);
				if (empty($firstlevelonly))
				{
					$listofchilds=$this->getChildsArbo($rec['id']);
					foreach($listofchilds as $keyChild => $valueChild)
					{
						$prods[$rec['rowid']]['childs'][$keyChild] = $valueChild;
					}
				}
			}

			return $prods;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Return tree of all subproducts for product. Tree contains id, name and quantity.
	 * 	Set this->sousprods
	 *
	 *  @return    	void
	 */
	function get_sousproduits_arbo()
	{
		$parent = $this->getParent();
		foreach($parent as $key => $value)		// key=label, value[0]=id
		{
			foreach($this->getChildsArbo($value[0]) as $keyChild => $valueChild)
			{
				$parent[$key][$keyChild] = $valueChild;
			}
		}
		foreach($parent as $key => $value)		// key=label, value is array of childs
		{
			$this->sousprods[$key] = $value;
		}
	}

	/**
	 *	Return clicable link of object (with eventually picto)
	 *
	 *	@param		int		$withpicto		Add picto into link
	 *	@param		string	$option			Where point the link
	 *	@param		int		$maxlength		Maxlength of ref
	 *	@return		string					String with URL
	 */
	function getNomUrl($withpicto=0,$option='',$maxlength=0)
	{
		global $conf, $langs;
		include_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

		$result='';
        $newref=$this->ref;
        if ($maxlength) $newref=dol_trunc($newref,$maxlength,'middle');
        if ($this->type == Product::TYPE_PRODUCT) $label = '<u>' . $langs->trans("ShowProduct") . '</u>';
        if ($this->type == Product::TYPE_SERVICE) $label = '<u>' . $langs->trans("ShowService") . '</u>';
        if (! empty($this->ref))
            $label .= '<br><b>' . $langs->trans('ProductRef') . ':</b> ' . $this->ref;
        if (! empty($this->label))
            $label .= '<br><b>' . $langs->trans('ProductLabel') . ':</b> ' . $this->label;

        $tmptext='';
		if ($this->weight) $tmptext.="<br>".$langs->trans("Weight").': '.$this->weight.' '.measuring_units_string($this->weight_units,"weight");
		if ($this->length) $tmptext.="<br>".$langs->trans("Length").': '.$this->length.' '.measuring_units_string($this->length_units,'length');
		if ($this->surface) $tmptext.="<br>".$langs->trans("Surface").': '.$this->surface.' '.measuring_units_string($this->surface_units,'surface');
		if ($this->volume) $tmptext.="<br>".$langs->trans("Volume").': '.$this->volume.' '.measuring_units_string($this->volume_units,'volume');
        if ($tmptext) $label .= $tmptext;

        if (! empty($this->entity))
            $label .= '<br>' . $this->show_photos($conf->product->multidir_output[$this->entity],1,1,0,0,0,80);

        $linkclose = '" title="'.str_replace('\n', '', dol_escape_htmltag($label, 1)).'" class="classfortooltip">';

        if ($option == 'supplier') {
            $link = '<a href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$this->id.$linkclose;
            $linkend='</a>';
        } else if ($option == 'stock') {
            $link = '<a href="'.DOL_URL_ROOT.'/product/stock/product.php?id='.$this->id.$linkclose;
            $linkend='</a>';
        } else if ($option == 'composition') {
            $link = '<a href="'.DOL_URL_ROOT.'/product/composition/card.php?id='.$this->id.$linkclose;
            $linkend='</a>';
        } else if ($option == 'category') {
            $link = '<a href="'.DOL_URL_ROOT.'/categories/categorie.php?id='.$this->id.'&amp;type=0'.$linkclose;
        } else {
            $link = '<a href="'.DOL_URL_ROOT.'/product/card.php?id='.$this->id.$linkclose;
            $linkend='</a>';
        }

		if ($withpicto) {
			if ($this->type == Product::TYPE_PRODUCT) $result.=($link.img_object($langs->trans("ShowProduct").' '.$this->label, 'product', 'class="classfortooltip"').$linkend.' ');
			if ($this->type == Product::TYPE_SERVICE) $result.=($link.img_object($langs->trans("ShowService").' '.$this->label, 'service', 'class="classfortooltip"').$linkend.' ');
		}
		$result.=$link.$newref.$linkend;
		return $result;
	}

	/**
	 *	Return label of status of object
	 *
	 *	@param      int	$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@param      int	$type       0=Sell, 1=Buy, 2=Batch Number management
	 *	@return     string      	Label of status
	 */
	function getLibStatut($mode=0, $type=0)
	{
		switch ($type)
		{
		case 0:
			return $this->LibStatut($this->status,$mode,$type);
		case 1:
			return $this->LibStatut($this->status_buy,$mode,$type);
		case 2:
			return $this->LibStatut($this->status_batch,$mode,$type);
		default:
			//Simulate previous behavior but should return an error string
			return $this->LibStatut($this->status_buy,$mode,$type);
		}
	}

	/**
	 *	Return label of a given status
	 *
	 *	@param      int		$status     Statut
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@param      int		$type       0=Status "to sell", 1=Status "to buy", 2=Status "to Batch"
	 *	@return     string      		Label of status
	 */
	function LibStatut($status,$mode=0,$type=0)
	{
		global $langs;
		$langs->load('products');
		if (!empty($conf->productbatch->enabled)) $langs->load("productbatch");

		if ($type == 2)
		{
			switch ($mode)
			{
				case 0:
					return ($status == 0 ? $langs->trans('ProductStatusNotOnBatch') : $langs->trans('ProductStatusOnBatch'));
				case 1:
					return ($status == 0 ? $langs->trans('ProductStatusNotOnBatchShort') : $langs->trans('ProductStatusOnBatchShort'));
				case 2:
					return $this->LibStatut($status,3,2).' '.$this->LibStatut($status,1,2);
				case 3:
					if ($status == 0 )
					{
						return img_picto($langs->trans('ProductStatusNotOnBatch'),'statut5');
					}
					else
					{
						return img_picto($langs->trans('ProductStatusOnBatch'),'statut4');
					}
				case 4:
					return $this->LibStatut($status,3,2).' '.$this->LibStatut($status,0,2);
				case 5:
					return $this->LibStatut($status,1,2).' '.$this->LibStatut($status,3,2);
				default:
					return $langs->trans('Unknown');
			}
		}
		if ($mode == 0)
		{
			if ($status == 0) return ($type==0 ? $langs->trans('ProductStatusNotOnSellShort'):$langs->trans('ProductStatusNotOnBuyShort'));
			if ($status == 1) return ($type==0 ? $langs->trans('ProductStatusOnSellShort'):$langs->trans('ProductStatusOnBuyShort'));
		}
		if ($mode == 1)
		{
			if ($status == 0) return ($type==0 ? $langs->trans('ProductStatusNotOnSell'):$langs->trans('ProductStatusNotOnBuy'));
			if ($status == 1) return ($type==0 ? $langs->trans('ProductStatusOnSell'):$langs->trans('ProductStatusOnBuy'));
		}
		if ($mode == 2)
		{
			if ($status == 0) return img_picto($langs->trans('ProductStatusNotOnSell'),'statut5').' '.($type==0 ? $langs->trans('ProductStatusNotOnSellShort'):$langs->trans('ProductStatusNotOnBuyShort'));
			if ($status == 1) return img_picto($langs->trans('ProductStatusOnSell'),'statut4').' '.($type==0 ? $langs->trans('ProductStatusOnSellShort'):$langs->trans('ProductStatusOnBuyShort'));
		}
		if ($mode == 3)
		{
			if ($status == 0) return img_picto(($type==0 ? $langs->trans('ProductStatusNotOnSell') : $langs->trans('ProductStatusNotOnBuy')),'statut5');
			if ($status == 1) return img_picto(($type==0 ? $langs->trans('ProductStatusOnSell') : $langs->trans('ProductStatusOnBuy')),'statut4');
		}
		if ($mode == 4)
		{
			if ($status == 0) return img_picto($langs->trans('ProductStatusNotOnSell'),'statut5').' '.($type==0 ? $langs->trans('ProductStatusNotOnSell'):$langs->trans('ProductStatusNotOnBuy'));
			if ($status == 1) return img_picto($langs->trans('ProductStatusOnSell'),'statut4').' '.($type==0 ? $langs->trans('ProductStatusOnSell'):$langs->trans('ProductStatusOnBuy'));
		}
		if ($mode == 5)
		{
			if ($status == 0) return ($type==0 ? $langs->trans('ProductStatusNotOnSellShort'):$langs->trans('ProductStatusNotOnBuyShort')).' '.img_picto(($type==0 ? $langs->trans('ProductStatusNotOnSell'):$langs->trans('ProductStatusNotOnBuy')),'statut5');
			if ($status == 1) return ($type==0 ? $langs->trans('ProductStatusOnSellShort'):$langs->trans('ProductStatusOnBuyShort')).' '.img_picto(($type==0 ? $langs->trans('ProductStatusOnSell'):$langs->trans('ProductStatusOnBuy')),'statut4');
		}
		return $langs->trans('Unknown');
	}


	/**
	 *  Retourne le libelle du finished du produit
	 *
	 *  @return     string		Libelle
	 */
	function getLibFinished()
	{
		global $langs;
		$langs->load('products');

		if ($this->finished == '0') return $langs->trans("RowMaterial");
		if ($this->finished == '1') return $langs->trans("Finished");
		return '';
	}


	/**
	 *  Adjust stock in a warehouse for product
	 *
	 *  @param  	User	$user           user asking change
	 *  @param  	int		$id_entrepot    id of warehouse
	 *  @param  	double	$nbpiece        nb of units
	 *  @param  	int		$movement       0 = add, 1 = remove
	 * 	@param		string	$label			Label of stock movement
	 * 	@param		double	$price			Price to use for stock eval
	 *  @param		string	$inventorycode	Inventory code
	 * 	@return     int     				<0 if KO, >0 if OK
	 */
	function correct_stock($user, $id_entrepot, $nbpiece, $movement, $label='', $price=0, $inventorycode='')
	{
		if ($id_entrepot)
		{
			$this->db->begin();

			require_once DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php';

			$op[0] = "+".trim($nbpiece);
			$op[1] = "-".trim($nbpiece);

			$movementstock=new MouvementStock($this->db);
			$result=$movementstock->_create($user,$this->id,$id_entrepot,$op[$movement],$movement,$price,$label,$inventorycode);

			if ($result >= 0)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
			    $this->error=$movementstock->error;
				$this->db->rollback();
				return -1;
			}
		}
	}

	/**
	 *  Adjust stock in a warehouse for product with batch number
	 *
	 *  @param  	User	$user           user asking change
	 *  @param  	int		$id_entrepot    id of warehouse
	 *  @param  	double	$nbpiece        nb of units
	 *  @param  	int		$movement       0 = add, 1 = remove
	 * 	@param		string	$label			Label of stock movement
	 * 	@param		double	$price			Price to use for stock eval
	 * 	@param		date	$dlc			eat-by date
	 * 	@param		date	$dluo			sell-by date
	 * 	@param		string	$lot			Lot number
	 *  @param		string	$inventorycode	Inventory code
	 * 	@return     int     				<0 if KO, >0 if OK
	 */
	function correct_stock_batch($user, $id_entrepot, $nbpiece, $movement, $label='', $price=0, $dlc='', $dluo='',$lot='', $inventorycode='')
	{
		if ($id_entrepot)
		{
			$this->db->begin();

			require_once DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php';

			$op[0] = "+".trim($nbpiece);
			$op[1] = "-".trim($nbpiece);

			$movementstock=new MouvementStock($this->db);
			$result=$movementstock->_create($user,$this->id,$id_entrepot,$op[$movement],$movement,$price,$label,$inventorycode,'',$dlc,$dluo,$lot);

			if ($result >= 0)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
			    $this->error=$movementstock->error;
			    $this->errors=$movementstock->errors;

				$this->db->rollback();
				return -1;
			}
		}
	}

	/**
	 *    Load information about stock of a product into stock_warehouse[] and stock_reel
	 *
	 *    @return     int             < 0 if KO, > 0 if OK
	 */
	function load_stock()
	{
		$this->stock_reel = 0;
		$this->stock_warehouse = array();

		$sql = "SELECT ps.reel, ps.fk_entrepot, ps.pmp, ps.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_stock as ps";
		$sql.= ", ".MAIN_DB_PREFIX."entrepot as w";
		$sql.= " WHERE w.entity IN (".getEntity('warehouse', 1).")";
		$sql.= " AND w.rowid = ps.fk_entrepot";
		$sql.= " AND ps.fk_product = ".$this->id;

		dol_syslog(get_class($this)."::load_stock", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i=0;
			if ($num > 0)
			{
				while ($i < $num)
				{
					$row = $this->db->fetch_object($result);
					$this->stock_warehouse[$row->fk_entrepot] = new stdClass();
					$this->stock_warehouse[$row->fk_entrepot]->real = $row->reel;
					$this->stock_warehouse[$row->fk_entrepot]->pmp = $row->pmp;
					$this->stock_warehouse[$row->fk_entrepot]->id = $row->rowid;
					if ($this->hasbatch()) $this->stock_warehouse[$row->fk_entrepot]->detail_batch=Productbatch::findAll($this->db,$row->rowid,1);
					$this->stock_reel+=$row->reel;
					$i++;
				}
			}
			$this->db->free($result);

			$this->load_virtual_stock();		// This also load stats_commande_fournisseur, ...

			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *    Load information about virtual stock of a product
	 *
	 *    @return     int             < 0 if KO, > 0 if OK
	 */
    function load_virtual_stock()
    {
        global $conf;

        $stock_commande_client=0;
        $stock_commande_fournisseur=0;
        $stock_sending_client=0;
        $stock_reception_fournisseur=0;

        if (! empty($conf->commande->enabled))
        {
            $result=$this->load_stats_commande(0,'1,2');
            if ($result < 0) dol_print_error($db,$this->error);
            $stock_commande_client=$this->stats_commande['qty'];
        }
        if (! empty($conf->expedition->enabled))
        {
            $result=$this->load_stats_sending(0,'1,2');
            if ($result < 0) dol_print_error($db,$this->error);
            $stock_sending_client=$this->stats_expedition['qty'];
        }
        if (! empty($conf->fournisseur->enabled))
        {
            $result=$this->load_stats_commande_fournisseur(0,'1,2,3,4');
            if ($result < 0) dol_print_error($db,$this->error);
            $stock_commande_fournisseur=$this->stats_commande_fournisseur['qty'];

            $result=$this->load_stats_reception(0,'4');
            if ($result < 0) dol_print_error($db,$this->error);
            $stock_reception_fournisseur=$this->stats_reception['qty'];
        }

        // Stock decrease mode
        if (! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT)) {
            $this->stock_theorique=$this->stock_reel-$stock_commande_client+$stock_sending_client;
        }
        if (! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER)) {
            $this->stock_theorique=$this->stock_reel;
        }
        if (! empty($conf->global->STOCK_CALCULATE_ON_BILL)) {
            $this->stock_theorique=$this->stock_reel-$stock_commande_client;
        }
        // Stock Increase mode
        if (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)) {
            $this->stock_theorique+=$stock_commande_fournisseur-$stock_reception_fournisseur;
        }
        if (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER)) {
            $this->stock_theorique-=$stock_reception_fournisseur;
        }
        if (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL)) {
            $this->stock_theorique+=$stock_commande_fournisseur-$stock_reception_fournisseur;
        }
    }

	/**
	 *  Move an uploaded file described into $file array into target directory $sdir.
	 *
	 *  @param  string	$sdir       Target directory
	 *  @param  string	$file       Array of file info of file to upload: array('name'=>..., 'tmp_name'=>...)
	 *  @return	int					<0 if KO, >0 if OK
	 */
	function add_photo($sdir, $file)
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$result = 0;

		$dir = $sdir;
		if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) $dir .= '/'. get_exdir($this->id,2) . $this->id ."/photos";
		else $dir .= '/'.dol_sanitizeFileName($this->ref);

		dol_mkdir($dir);

		$dir_osencoded=$dir;

		if (is_dir($dir_osencoded))
		{
			$originImage = $dir . '/' . $file['name'];

			// Cree fichier en taille origine
			$result=dol_move_uploaded_file($file['tmp_name'], $originImage, 1);

			if (file_exists(dol_osencode($originImage)))
			{
				// Cree fichier en taille vignette
				$this->add_thumb($originImage);
			}
		}

		if (is_numeric($result) && $result > 0) return 1;
		else return -1;
	}

	/**
	 *  Affiche la premiere photo du produit
	 *
	 *  @param      string		$sdir       Repertoire a scanner
	 *  @return     boolean     			true si photo dispo, false sinon
	 */
	function is_photo_available($sdir)
	{
		include_once DOL_DOCUMENT_ROOT .'/core/lib/files.lib.php';

		$dir = $sdir;
		if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) $dir .= '/'. get_exdir($this->id,2) . $this->id ."/photos";

		$nbphoto=0;

		$dir_osencoded=dol_osencode($dir);
		if (file_exists($dir_osencoded))
		{
			$handle=opendir($dir_osencoded);
			if (is_resource($handle))
			{
			    while (($file = readdir($handle)) != false)
    			{
    				if (! utf8_check($file)) $file=utf8_encode($file);	// To be sure data is stored in UTF8 in memory
    				if (dol_is_file($dir.$file)) return true;
    			}
			}
		}
		return false;
	}


	/**
	 *  Show photos of a product (nbmax maximum), into several columns
	 *	TODO Move this into html.formproduct.class.php
	 *
	 *  @param      string	$sdir        	Directory to scan
	 *  @param      int		$size        	0=original size, 1 use thumbnail if possible
	 *  @param      int		$nbmax       	Nombre maximum de photos (0=pas de max)
	 *  @param      int		$nbbyrow     	Number of image per line or -1 to use div. Used only if size=1.
	 * 	@param		int		$showfilename	1=Show filename
	 * 	@param		int		$showaction		1=Show icon with action links (resize, delete)
	 * 	@param		int		$maxHeight		Max height of image when size=1
	 * 	@param		int		$maxWidth		Max width of image when size=1
	 *  @return     string					Html code to show photo. Number of photos shown is saved in this->nbphoto
	 */
	function show_photos($sdir,$size=0,$nbmax=0,$nbbyrow=5,$showfilename=0,$showaction=0,$maxHeight=120,$maxWidth=160)
	{
		global $conf,$user,$langs;

		include_once DOL_DOCUMENT_ROOT .'/core/lib/files.lib.php';
		include_once DOL_DOCUMENT_ROOT .'/core/lib/images.lib.php';

		$dir = $sdir . '/';
		$pdir = '/';
		if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO))
		{
			$dir .= get_exdir($this->id,2) . $this->id ."/photos/";
			$pdir .= get_exdir($this->id,2) . $this->id ."/photos/";
		}
		else
		{
			$dir .= $this->ref.'/';
			$pdir .= $this->ref.'/';
		}

		$dirthumb = $dir.'thumbs/';
		$pdirthumb = $pdir.'thumbs/';

		$return ='<!-- Photo -->'."\n";
		$nbphoto=0;

		$dir_osencoded=dol_osencode($dir);
		if (file_exists($dir_osencoded))
		{
			$handle=opendir($dir_osencoded);
            if (is_resource($handle))
            {
    			while (($file = readdir($handle)) != false)
    			{
    				$photo='';

    				if (! utf8_check($file)) $file=utf8_encode($file);	// To be sure file is stored in UTF8 in memory

    				if (dol_is_file($dir.$file) && preg_match('/('.$this->regeximgext.')$/i', $dir.$file))
    				{
    					$nbphoto++;
    					$photo = $file;
    					$viewfilename = $file;

    					if ($size == 1) {   // Format vignette
    						// On determine nom du fichier vignette
    						$photo_vignette='';
    						if (preg_match('/(\.jpg|\.bmp|\.gif|\.png|\.tiff)$/i', $photo, $regs)) {
    							$photo_vignette=preg_replace('/'.$regs[0].'/i', '', $photo)."_small".$regs[0];
    							if (! dol_is_file($dirthumb.$photo_vignette)) $photo_vignette='';
    						}

    						// Get filesize of original file
    						$imgarray=dol_getImageSize($dir.$photo);

    						if ($nbbyrow > 0)
    						{
    							if ($nbphoto == 1) $return.= '<table width="100%" valign="top" align="center" border="0" cellpadding="2" cellspacing="2">';

    							if ($nbphoto % $nbbyrow == 1) $return.= '<tr align=center valign=middle border=1>';
    							$return.= '<td width="'.ceil(100/$nbbyrow).'%" class="photo">';
    						}
    						else if ($nbbyrow < 0) $return .= '<div class="inline-block">';

    						$return.= "\n";
    						$return.= '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&entity='.$this->entity.'&file='.urlencode($pdir.$photo).'" class="aphoto" target="_blank">';

    						// Show image (width height=$maxHeight)
    						// Si fichier vignette disponible et image source trop grande, on utilise la vignette, sinon on utilise photo origine
    						$alt=$langs->transnoentitiesnoconv('File').': '.$pdir.$photo;
    						$alt.=' - '.$langs->transnoentitiesnoconv('Size').': '.$imgarray['width'].'x'.$imgarray['height'];
    						if ($photo_vignette && $imgarray['height'] > $maxHeight) {
    							$return.= '<!-- Show thumb -->';
    							$return.= '<img class="photo photowithmargin" border="0" '.($conf->dol_use_jmobile?'max-height':'height').'="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&entity='.$this->entity.'&file='.urlencode($pdirthumb.$photo_vignette).'" title="'.dol_escape_htmltag($alt).'">';
    						}
    						else {
    							$return.= '<!-- Show original file -->';
    							$return.= '<img class="photo photowithmargin" border="0" '.($conf->dol_use_jmobile?'max-height':'height').'="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&entity='.$this->entity.'&file='.urlencode($pdir.$photo).'" title="'.dol_escape_htmltag($alt).'">';
    						}

    						$return.= '</a>'."\n";

    						if ($showfilename) $return.= '<br>'.$viewfilename;
    						if ($showaction)
    						{
    							$return.= '<br>';
    							// On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
    							if ($photo_vignette && preg_match('/('.$this->regeximgext.')$/i', $photo) && ($product->imgWidth > $maxWidth || $product->imgHeight > $maxHeight))
    							{
    								$return.= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=addthumb&amp;file='.urlencode($pdir.$viewfilename).'">'.img_picto($langs->trans('GenerateThumb'),'refresh').'&nbsp;&nbsp;</a>';
    							}
    							if ($user->rights->produit->creer || $user->rights->service->creer)
    							{
    								// Link to resize
    			               		$return.= '<a href="'.DOL_URL_ROOT.'/core/photos_resize.php?modulepart='.urlencode('produit|service').'&id='.$this->id.'&amp;file='.urlencode($pdir.$viewfilename).'" title="'.dol_escape_htmltag($langs->trans("Resize")).'">'.img_picto($langs->trans("Resize"),DOL_URL_ROOT.'/theme/common/transform-crop-and-resize','',1).'</a> &nbsp; ';

    			               		// Link to delete
    								$return.= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=delete&amp;file='.urlencode($pdir.$viewfilename).'">';
    								$return.= img_delete().'</a>';
    							}
    						}
    						$return.= "\n";

    						if ($nbbyrow > 0)
    						{
    							$return.= '</td>';
    							if (($nbphoto % $nbbyrow) == 0) $return.= '</tr>';
    						}
    						else if ($nbbyrow < 0) $return.='</div>';
    					}

    					if ($size == 0) {     // Format origine
    						$return.= '<img class="photo photowithmargin" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&entity='.$this->entity.'&file='.urlencode($pdir.$photo).'">';

    						if ($showfilename) $return.= '<br>'.$viewfilename;
    						if ($showaction)
    						{
    							if ($user->rights->produit->creer || $user->rights->service->creer)
    							{
    								// Link to resize
    			               		$return.= '<a href="'.DOL_URL_ROOT.'/core/photos_resize.php?modulepart='.urlencode('produit|service').'&id='.$this->id.'&amp;file='.urlencode($pdir.$viewfilename).'" title="'.dol_escape_htmltag($langs->trans("Resize")).'">'.img_picto($langs->trans("Resize"),DOL_URL_ROOT.'/theme/common/transform-crop-and-resize','',1).'</a> &nbsp; ';

    			               		// Link to delete
    			               		$return.= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=delete&amp;file='.urlencode($pdir.$viewfilename).'">';
    								$return.= img_delete().'</a>';
    							}
    						}
    					}

    					// On continue ou on arrete de boucler ?
    					if ($nbmax && $nbphoto >= $nbmax) break;
    				}
    			}
            }

			if ($size==1)
			{
				if ($nbbyrow > 0)
				{
					// Ferme tableau
					while ($nbphoto % $nbbyrow)
					{
						$return.= '<td width="'.ceil(100/$nbbyrow).'%">&nbsp;</td>';
						$nbphoto++;
					}

					if ($nbphoto) $return.= '</table>';
				}
			}

			closedir($handle);
		}

		$this->nbphoto = $nbphoto;

		return $return;
	}


	/**
	 *  Retourne tableau de toutes les photos du produit
	 *
	 *  @param      string		$dir        Repertoire a scanner
	 *  @param      int			$nbmax      Nombre maximum de photos (0=pas de max)
	 *  @return     array       			Tableau de photos
	 */
	function liste_photos($dir,$nbmax=0)
	{
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$nbphoto=0;
		$tabobj=array();

		$dir_osencoded=dol_osencode($dir);
		$handle=@opendir($dir_osencoded);
		if (is_resource($handle))
		{
			while (($file = readdir($handle)) != false)
			{
				if (! utf8_check($file)) $file=utf8_encode($file);	// readdir returns ISO
				if (dol_is_file($dir.$file) && preg_match('/('.$this->regeximgext.')$/i', $dir.$file))
				{
					$nbphoto++;

					// On determine nom du fichier vignette
					$photo=$file;
					$photo_vignette='';
					if (preg_match('/('.$this->regeximgext.')$/i', $photo, $regs))
					{
						$photo_vignette=preg_replace('/'.$regs[0].'/i', '', $photo).'_small'.$regs[0];
					}

					$dirthumb = $dir.'thumbs/';

					// Objet
					$obj=array();
					$obj['photo']=$photo;
					if ($photo_vignette && dol_is_file($dirthumb.$photo_vignette)) $obj['photo_vignette']='thumbs/' . $photo_vignette;
					else $obj['photo_vignette']="";

					$tabobj[$nbphoto-1]=$obj;

					// On continue ou on arrete de boucler ?
					if ($nbmax && $nbphoto >= $nbmax) break;
				}
			}

			closedir($handle);
		}

		return $tabobj;
	}

	/**
	 *  Efface la photo du produit et sa vignette
	 *
	 *  @param  string		$file        Chemin de l'image
	 *  @return	void
	 */
	function delete_photo($file)
	{
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $dir = dirname($file).'/'; // Chemin du dossier contenant l'image d'origine
		$dirthumb = $dir.'/thumbs/'; // Chemin du dossier contenant la vignette
		$filename = preg_replace('/'.preg_quote($dir,'/').'/i','',$file); // Nom du fichier

		// On efface l'image d'origine
		dol_delete_file($file);

		// Si elle existe, on efface la vignette
		if (preg_match('/('.$this->regeximgext.')$/i',$filename,$regs))
		{
			$photo_vignette=preg_replace('/'.$regs[0].'/i','',$filename).'_small'.$regs[0];
			if (file_exists(dol_osencode($dirthumb.$photo_vignette)))
			{
				dol_delete_file($dirthumb.$photo_vignette);
			}
		}
	}

	/**
	 *  Load size of image file
	 *
	 *  @param  string	$file        Path to file
	 *  @return	void
	 */
	function get_image_size($file)
	{
		$file_osencoded=dol_osencode($file);
		$infoImg = getimagesize($file_osencoded); // Get information on image
		$this->imgWidth = $infoImg[0]; // Largeur de l'image
		$this->imgHeight = $infoImg[1]; // Hauteur de l'image
	}

	/**
	 *  Charge indicateurs this->nb de tableau de bord
	 *
	 *  @return     int         <0 si ko, >0 si ok
	 */
	function load_state_board()
	{
		global $conf, $user;

		$this->nb=array();

		$sql = "SELECT count(p.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql.= ' WHERE p.entity IN ('.getEntity($this->element, 1).')';
		$sql.= " AND p.fk_product_type <> 1";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["products"]=$obj->nb;
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
     * Return if object is a product
     *
     * @return  boolean     True if it's a product
     */
	function isproduct()
	{
		return ($this->type != Product::TYPE_PRODUCT ? true : false);
	}

    /**
     * Return if object is a product
     *
     * @return  boolean     True if it's a service
     */
	function isservice()
	{
		return ($this->type == Product::TYPE_SERVICE ? true : false);
	}

    /**
     *  Get a barcode from the module to generate barcode values.
     *  Return value is stored into this->barcode
     *
     *	@param	Product		$object		Object product or service
     *	@param	string		$type		Barcode type (ean, isbn, ...)
     *  @return void
     */
    function get_barcode($object,$type='')
    {
        global $conf;

        $result='';
        if (! empty($conf->global->BARCODE_PRODUCT_ADDON_NUM))
        {
            $dirsociete=array_merge(array('/core/modules/barcode/'),$conf->modules_parts['barcode']);
            foreach ($dirsociete as $dirroot)
            {
                $res=dol_include_once($dirroot.$conf->global->BARCODE_PRODUCT_ADDON_NUM.'.php');
                if ($res) break;
            }
            $var = $conf->global->BARCODE_PRODUCT_ADDON_NUM;
            $mod = new $var;

            $result=$mod->getNextValue($object,$type);

            dol_syslog(get_class($this)."::get_barcode barcode=".$result." module=".$var);
        }
        return $result;
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
        global $user,$langs,$conf,$mysoc;

        $now=dol_now();

        // Initialize parameters
        $this->id=0;
        $this->ref = 'PRODUCT_SPEC';
        $this->libelle = 'PRODUCT SPECIMEN';
        $this->description = 'PRODUCT SPECIMEN '.dol_print_date($now,'dayhourlog');
        $this->specimen=1;
        $this->country_id=1;
        $this->tosell=1;
        $this->tobuy=1;
		$this->tobatch=0;
        $this->note='This is a comment (private)';

        $this->barcode=-1;	// Create barcode automatically
    }

    /**
     * Return if object has a sell-by date or eat-by date
     *
     * @return  boolean     True if it's has
     */
	function hasbatch()
	{
		return ($this->status_batch == 1 ? true : false);
	}


	/**
     * Return minimum product recommended price
     *
	 * @return	int			Minimum recommanded price that is higher price among all suppliers * PRODUCT_MINIMUM_RECOMMENDED_PRICE
     */
	function min_recommended_price()
	{
		global $conf;

		$maxpricesupplier=0;

		if (! empty($conf->global->PRODUCT_MINIMUM_RECOMMENDED_PRICE))
		{
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
			$product_fourn = new ProductFournisseur($this->db);
			$product_fourn_list = $product_fourn->list_product_fournisseur_price($this->id, '', '');

			if (is_array($product_fourn_list) && count($product_fourn_list)>0)
			{
				foreach($product_fourn_list as $productfourn)
				{
					if ($productfourn->fourn_unitprice > $maxpricesupplier)
					{
						$maxpricesupplier = $productfourn->fourn_unitprice;
					}
				}

				$maxpricesupplier *= $conf->global->PRODUCT_MINIMUM_RECOMMENDED_PRICE;
			}
		}

		return $maxpricesupplier;
	}
}
