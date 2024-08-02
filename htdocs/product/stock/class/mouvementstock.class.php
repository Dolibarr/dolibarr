<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      Jean Heimburger      <jean@tiaris.info>
 * Copyright (C) 2014	   Cedric GROSS	        <c.gross@kreiz-it.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/product/stock/class/mouvementstock.class.php
 *	\ingroup    stock
 *	\brief      File of class to manage stock movement (input or output)
 */


/**
 *	Class to manage stock movements
 */
class MouvementStock extends CommonObject
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'stockmouvement';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'stock_mouvement';


	/**
	 * @var int ID product
	 */
	public $product_id;

	/**
	 * @var int ID warehouse
	 * @deprecated
	 * @see $warehouse_id
	 */
	public $entrepot_id;

	/**
	 * @var int ID warehouse
	 */
	public $warehouse_id;

	/**
	 * @var float Quantity
	 */
	public $qty;

	/**
	 * @var int Type of movement
	 * 0=input (stock increase by a manual/direct stock transfer, correction or inventory),
	 * 1=output (stock decrease after by a manual/direct stock transfer, correction or inventory),
	 * 2=output (stock decrease after a business event like sale, shipment or manufacturing, ...),
	 * 3=input (stock increase after a business event like purchase, reception or manufacturing, ...)
	 * Note that qty should be > 0 with 0 or 3, < 0 with 1 or 2.
	 */
	public $type;

	/**
	 * @var null|int|'' datem date
	 */
	public $datem = '';
	public $price;

	/**
	 * @var int ID user author
	 */
	public $fk_user_author;

	/**
	 * @var string stock movements label
	 */
	public $label;

	/**
	 * @var int ID
	 * @deprecated
	 * @see $origin_id
	 */
	public $fk_origin;

	/**
	 * @var int		Origin id
	 */
	public $origin_id;

	/**
	 * @var	string	origintype
	 * @deprecated
	 * see $origin_type
	 */
	public $origintype;

	/**
	 * @var string Origin type ('project', ...)
	 */
	public $origin_type;
	public $line_id_oject_src;
	public $line_id_oject_origin;

	/**
	 * @var string inventory code
	 */
	public $inventorycode;

	/**
	 * @var string batch reference
	 */
	public $batch;

	public $line_id_object_src;
	public $line_id_object_origin;

	/**
	 * @var null|int|'' eatby date
	 */
	public $eatby;

	/**
	 * @var null|int|'' sellby date
	 */
	public $sellby;



	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 10, 'showoncombobox' => 1),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 15),
		'datem' => array('type' => 'datetime', 'label' => 'Datem', 'enabled' => 1, 'visible' => -1, 'position' => 20),
		'fk_product' => array('type' => 'integer:Product:product/class/product.class.php:1', 'label' => 'Product', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 25),
		'fk_entrepot' => array('type' => 'integer:Entrepot:product/stock/class/entrepot.class.php', 'label' => 'Warehouse', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 30),
		'value' => array('type' => 'double', 'label' => 'Value', 'enabled' => 1, 'visible' => -1, 'position' => 35),
		'price' => array('type' => 'double(24,8)', 'label' => 'Price', 'enabled' => 1, 'visible' => -1, 'position' => 40),
		'type_mouvement' => array('type' => 'smallint(6)', 'label' => 'Type mouvement', 'enabled' => 1, 'visible' => -1, 'position' => 45),
		'fk_user_author' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Fk user author', 'enabled' => 1, 'visible' => -1, 'position' => 50),
		'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'visible' => -1, 'position' => 55),
		'fk_origin' => array('type' => 'integer', 'label' => 'Fk origin', 'enabled' => 1, 'visible' => -1, 'position' => 60),
		'origintype' => array('type' => 'varchar(32)', 'label' => 'Origintype', 'enabled' => 1, 'visible' => -1, 'position' => 65),
		'model_pdf' => array('type' => 'varchar(255)', 'label' => 'Model pdf', 'enabled' => 1, 'visible' => 0, 'position' => 70),
		'fk_projet' => array('type' => 'integer:Project:projet/class/project.class.php:1:(fk_statut:=:1)', 'label' => 'Project', 'enabled' => '$conf->project->enabled', 'visible' => -1, 'notnull' => 1, 'position' => 75),
		'inventorycode' => array('type' => 'varchar(128)', 'label' => 'InventoryCode', 'enabled' => 1, 'visible' => -1, 'position' => 80),
		'batch' => array('type' => 'varchar(30)', 'label' => 'Batch', 'enabled' => 1, 'visible' => -1, 'position' => 85),
		'eatby' => array('type' => 'date', 'label' => 'Eatby', 'enabled' => 1, 'visible' => -1, 'position' => 90),
		'sellby' => array('type' => 'date', 'label' => 'Sellby', 'enabled' => 1, 'visible' => -1, 'position' => 95),
		'fk_project' => array('type' => 'integer:Project:projet/class/project.class.php:1:(fk_statut:=:1)', 'label' => 'Fk project', 'enabled' => 1, 'visible' => -1, 'position' => 100),
	);



	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *	Add a movement of stock (in one direction only).
	 *  This is the lowest level method to record a stock change. There is no control if warehouse is open or not.
	 *  $this->origin_type and $this->origin_id can be also be set to save the source object of movement.
	 *
	 *	@param		User			$user				User object
	 *	@param		int				$fk_product			Id of product
	 *	@param		int				$entrepot_id		Id of warehouse
	 *	@param		float			$qty				Qty of movement (can be <0 or >0 depending on parameter type)
	 *	@param		int				$type				Direction of movement:
	 *													0=input (stock increase by a stock transfer), 1=output (stock decrease by a stock transfer),
	 *													2=output (stock decrease), 3=input (stock increase)
	 *                          		            	Note that qty should be > 0 with 0 or 3, < 0 with 1 or 2.
	 *	@param		int				$price				Unit price HT of product, used to calculate average weighted price (AWP or PMP in french). If 0, average weighted price is not changed.
	 *	@param		string			$label				Label of stock movement
	 *	@param		string			$inventorycode		Inventory code
	 *	@param		integer|string	$datem				Force date of movement
	 *	@param		integer|string	$eatby				eat-by date. Will be used if lot does not exists yet and will be created.
	 *	@param		integer|string	$sellby				sell-by date. Will be used if lot does not exists yet and will be created.
	 *	@param		string			$batch				batch number
	 *	@param		boolean			$skip_batch			If set to true, stock movement is done without impacting batch record
	 * 	@param		int				$id_product_batch	Id product_batch (when skip_batch is false and we already know which record of product_batch to use)
	 *  @param		int				$disablestockchangeforsubproduct	Disable stock change for sub-products of kit (useful only if product is a subproduct)
	 *  @param		int				$donotcleanemptylines				Do not clean lines in stock table with qty=0 (because we want to have this done by the caller)
	 * 	@param		boolean			$force_update_batch	Allows to add batch stock movement even if $product doesn't use batch anymore
	 *	@return		int									Return integer <0 if KO, 0 if fk_product is null or product id does not exists, >0 if OK
	 */
	public function _create($user, $fk_product, $entrepot_id, $qty, $type, $price = 0, $label = '', $inventorycode = '', $datem = '', $eatby = '', $sellby = '', $batch = '', $skip_batch = false, $id_product_batch = 0, $disablestockchangeforsubproduct = 0, $donotcleanemptylines = 0, $force_update_batch = false)
	{
		// phpcs:enable
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';

		$error = 0;
		dol_syslog(get_class($this)."::_create start userid=$user->id, fk_product=$fk_product, warehouse_id=$entrepot_id, qty=$qty, type=$type, price=$price, label=$label, inventorycode=$inventorycode, datem=".$datem.", eatby=".$eatby.", sellby=".$sellby.", batch=".$batch.", skip_batch=".json_encode($skip_batch));

		// Call hook at beginning
		global $action, $hookmanager;
		$hookmanager->initHooks(array('mouvementstock'));

		if (is_object($hookmanager)) {
			$parameters = array(
				'currentcontext'   => 'mouvementstock',
				'user'             => &$user,
				'fk_product'       => &$fk_product,
				'entrepot_id'      => &$entrepot_id,
				'qty'              => &$qty,
				'type'             => &$type,
				'price'            => &$price,
				'label'            => &$label,
				'inventorycode'    => &$inventorycode,
				'datem'            => &$datem,
				'eatby'            => &$eatby,
				'sellby'           => &$sellby,
				'batch'            => &$batch,
				'skip_batch'       => &$skip_batch,
				'id_product_batch' => &$id_product_batch
			);
			$reshook = $hookmanager->executeHooks('stockMovementCreate', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

			if ($reshook < 0) {
				if (!empty($hookmanager->resPrint)) {
					dol_print_error(null, $hookmanager->resPrint);
				}
				return $reshook;
			} elseif ($reshook > 0) {
				return $reshook;
			}
		}
		// end hook at beginning

		// Clean parameters
		$price = price2num($price, 'MU'); // Clean value for the casse we receive a float zero value, to have it a real zero value.
		if (empty($price)) {
			$price = 0;
		}
		$now = (!empty($datem) ? $datem : dol_now());

		// Check parameters
		if (!($fk_product > 0)) {
			return 0;
		}
		if (!($entrepot_id > 0)) {
			return 0;
		}

		if (is_numeric($eatby) && $eatby < 0) {
			dol_syslog(get_class($this)."::_create start ErrorBadValueForParameterEatBy eatby = ".$eatby);
			$this->errors[] = 'ErrorBadValueForParameterEatBy';
			return -1;
		}
		if (is_numeric($sellby) && $sellby < 0) {
			dol_syslog(get_class($this)."::_create start ErrorBadValueForParameterSellBy sellby = ".$sellby);
			$this->errors[] = 'ErrorBadValueForParameterSellBy';
			return -1;
		}

		// Set properties of movement
		$this->product_id = $fk_product;
		$this->entrepot_id = $entrepot_id; // deprecated
		$this->warehouse_id = $entrepot_id;
		$this->qty = $qty;
		$this->type = $type;
		$this->price = price2num($price);
		$this->label = $label;
		$this->inventorycode = $inventorycode;
		$this->datem = $now;
		$this->batch = $batch;

		$mvid = 0;

		$product = new Product($this->db);

		$result = $product->fetch($fk_product);
		if ($result < 0) {
			$this->error = $product->error;
			$this->errors = $product->errors;
			dol_print_error(null, "Failed to fetch product");
			return -1;
		}
		if ($product->id <= 0) {	// Can happen if database is corrupted (a product id exist in stock with product that has been removed)
			return 0;
		}

		// Define if we must make the stock change (If product type is a service or if stock is used also for services)
		// Only record into stock tables will be disabled by this (the rest like writing into lot table or movement of subproucts are done)
		$movestock = 0;
		if ($product->type != Product::TYPE_SERVICE || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
			$movestock = 1;
		}

		$this->db->begin();

		// Set value $product->stock_reel and detail per warehouse into $product->stock_warehouse array
		if ($movestock) {
			$product->load_stock('novirtual');
		}

		// Test if product require batch data. If yes, and there is not or values are not correct, we throw an error.
		if (isModEnabled('productbatch') && $product->hasbatch() && !$skip_batch) {
			if (empty($batch)) {
				$langs->load("errors");
				$this->errors[] = $langs->transnoentitiesnoconv("ErrorTryToMakeMoveOnProductRequiringBatchData", $product->ref);
				dol_syslog("Try to make a movement of a product with status_batch on without any batch data", LOG_ERR);

				$this->db->rollback();
				return -2;
			}

			// Check table llx_product_lot from batchnumber for same product
			// If found and eatby/sellby defined into table and provided and differs, return error
			// If found and eatby/sellby defined into table and not provided, we take value from table
			// If found and eatby/sellby not defined into table and provided, we update table
			// If found and eatby/sellby not defined into table and not provided, we do nothing
			// If not found, we add record
			$sql = "SELECT pb.rowid, pb.batch, pb.eatby, pb.sellby FROM ".$this->db->prefix()."product_lot as pb";
			$sql .= " WHERE pb.fk_product = ".((int) $fk_product)." AND pb.batch = '".$this->db->escape($batch)."'";

			dol_syslog(get_class($this)."::_create scan serial for this product to check if eatby and sellby match", LOG_DEBUG);

			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				$i = 0;
				if ($num > 0) {
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);
						if ($obj->eatby) {
							if ($eatby) {
								$tmparray = dol_getdate($eatby, true);
								$eatbywithouthour = dol_mktime(0, 0, 0, $tmparray['mon'], $tmparray['mday'], $tmparray['year']);
								if ($this->db->jdate($obj->eatby) != $eatby && $this->db->jdate($obj->eatby) != $eatbywithouthour) {    // We test date without hours and with hours for backward compatibility
									// If found and eatby/sellby defined into table and provided and differs, return error
									$langs->load("stocks");
									$this->errors[] = $langs->transnoentitiesnoconv("ThisSerialAlreadyExistWithDifferentDate", $batch, dol_print_date($this->db->jdate($obj->eatby), 'dayhour'), dol_print_date($eatbywithouthour, 'dayhour'));
									dol_syslog("ThisSerialAlreadyExistWithDifferentDate batch=".$batch.", eatby found into product_lot = ".$obj->eatby." = ".dol_print_date($this->db->jdate($obj->eatby), 'dayhourrfc')." so eatbywithouthour = ".$eatbywithouthour." = ".dol_print_date($eatbywithouthour)." - eatby provided = ".$eatby." = ".dol_print_date($eatby, 'dayhourrfc'), LOG_ERR);
									$this->db->rollback();
									return -3;
								}
							} else {
								$eatby = $obj->eatby; // If found and eatby/sellby defined into table and not provided, we take value from table
							}
						} else {
							if ($eatby) { // If found and eatby/sellby not defined into table and provided, we update table
								$productlot = new Productlot($this->db);
								$result = $productlot->fetch($obj->rowid);
								$productlot->eatby = $eatby;
								$result = $productlot->update($user);
								if ($result <= 0) {
									$this->error = $productlot->error;
									$this->errors = $productlot->errors;
									$this->db->rollback();
									return -5;
								}
							}
						}
						if ($obj->sellby) {
							if ($sellby) {
								$tmparray = dol_getdate($sellby, true);
								$sellbywithouthour = dol_mktime(0, 0, 0, $tmparray['mon'], $tmparray['mday'], $tmparray['year']);
								if ($this->db->jdate($obj->sellby) != $sellby && $this->db->jdate($obj->sellby) != $sellbywithouthour) {    // We test date without hours and with hours for backward compatibility
									// If found and eatby/sellby defined into table and provided and differs, return error
									$this->errors[] = $langs->transnoentitiesnoconv("ThisSerialAlreadyExistWithDifferentDate", $batch, dol_print_date($this->db->jdate($obj->sellby)), dol_print_date($sellby));
									dol_syslog($langs->transnoentities("ThisSerialAlreadyExistWithDifferentDate", $batch, dol_print_date($this->db->jdate($obj->sellby)), dol_print_date($sellby)), LOG_ERR);
									$this->db->rollback();
									return -3;
								}
							} else {
								$sellby = $obj->sellby; // If found and eatby/sellby defined into table and not provided, we take value from table
							}
						} else {
							if ($sellby) { // If found and eatby/sellby not defined into table and provided, we update table
								$productlot = new Productlot($this->db);
								$result = $productlot->fetch($obj->rowid);
								$productlot->sellby = $sellby;
								$result = $productlot->update($user);
								if ($result <= 0) {
									$this->error = $productlot->error;
									$this->errors = $productlot->errors;
									$this->db->rollback();
									return -5;
								}
							}
						}

						$i++;
					}
				} else { // If not found, we add record
					$productlot = new Productlot($this->db);
					$productlot->origin = !empty($this->origin_type) ? $this->origin_type : '';
					$productlot->origin_id = !empty($this->origin_id) ? $this->origin_id : 0;
					$productlot->entity = $conf->entity;
					$productlot->fk_product = $fk_product;
					$productlot->batch = $batch;
					// If we are here = first time we manage this batch, so we used dates provided by users to create lot
					$productlot->eatby = $eatby;
					$productlot->sellby = $sellby;
					$result = $productlot->create($user);
					if ($result <= 0) {
						$this->error = $productlot->error;
						$this->errors = $productlot->errors;
						$this->db->rollback();
						return -4;
					}
				}
			} else {
				dol_print_error($this->db);
				$this->db->rollback();
				return -1;
			}
		}

		// Check if stock is enough when qty is < 0
		// Note that qty should be > 0 with type 0 or 3, < 0 with type 1 or 2.
		if ($movestock && $qty < 0 && !getDolGlobalInt('STOCK_ALLOW_NEGATIVE_TRANSFER')) {
			if (isModEnabled('productbatch') && $product->hasbatch() && !$skip_batch) {
				$foundforbatch = 0;
				$qtyisnotenough = 0;
				if (isset($product->stock_warehouse[$entrepot_id])) {
					foreach ($product->stock_warehouse[$entrepot_id]->detail_batch as $batchcursor => $prodbatch) {
						if ((string) $batch != (string) $batchcursor) {        // Lot '59' must be different than lot '59c'
							continue;
						}

						$foundforbatch = 1;
						if ($prodbatch->qty < abs($qty)) {
							$qtyisnotenough = $prodbatch->qty;
						}
						break;
					}
				}
				if (!$foundforbatch || $qtyisnotenough) {
					$langs->load("stocks");
					include_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
					$tmpwarehouse = new Entrepot($this->db);
					$tmpwarehouse->fetch($entrepot_id);

					$this->error = $langs->trans('qtyToTranferLotIsNotEnough', $product->ref, $batch, $qtyisnotenough, $tmpwarehouse->ref);
					$this->errors[] = $langs->trans('qtyToTranferLotIsNotEnough', $product->ref, $batch, $qtyisnotenough, $tmpwarehouse->ref);
					$this->db->rollback();
					return -8;
				}
			} else {
				if (isset($product->stock_warehouse[$entrepot_id]) && (empty($product->stock_warehouse[$entrepot_id]->real) || $product->stock_warehouse[$entrepot_id]->real < abs($qty)) && $product->stockable_product == Product::ENABLED_STOCK) {
					$langs->load("stocks");
					$this->error = $langs->trans('qtyToTranferIsNotEnough').' : '.$product->ref;
					$this->errors[] = $langs->trans('qtyToTranferIsNotEnough').' : '.$product->ref;
					$this->db->rollback();
					return -8;
				}
			}
		}

		if ($movestock && $product->stockable_product == Product::ENABLED_STOCK) {	// Change stock for current product, change for subproduct is done after
			// Set $origin_type, origin_id and fk_project
			$fk_project = $this->fk_project;
			if (!empty($this->origin_type)) {			// This is set by caller for tracking reason
				$origin_type = $this->origin_type;
				$origin_id = $this->origin_id;
				if (empty($fk_project) && $origin_type == 'project') {
					$fk_project = $origin_id;
					$origin_type = '';
					$origin_id = 0;
				}
			} else {
				$fk_project = 0;
				$origin_type = '';
				$origin_id = 0;
			}

			$sql = "INSERT INTO ".$this->db->prefix()."stock_mouvement(";
			$sql .= " datem, fk_product, batch, eatby, sellby,";
			$sql .= " fk_entrepot, value, type_mouvement, fk_user_author, label, inventorycode, price, fk_origin, origintype, fk_projet";
			$sql .= ")";
			$sql .= " VALUES ('".$this->db->idate($this->datem)."', ".((int) $this->product_id).", ";
			$sql .= " ".($batch ? "'".$this->db->escape($batch)."'" : "null").", ";
			$sql .= " ".($eatby ? "'".$this->db->idate($eatby)."'" : "null").", ";
			$sql .= " ".($sellby ? "'".$this->db->idate($sellby)."'" : "null").", ";
			$sql .= " ".((int) $this->entrepot_id).", ".((float) $this->qty).", ".((int) $this->type).",";
			$sql .= " ".((int) $user->id).",";
			$sql .= " '".$this->db->escape($label)."',";
			$sql .= " ".($inventorycode ? "'".$this->db->escape($inventorycode)."'" : "null").",";
			$sql .= " ".((float) price2num($price)).",";
			$sql .= " ".((int) $origin_id).",";
			$sql .= " '".$this->db->escape($origin_type)."',";
			$sql .= " ".((int) $fk_project);
			$sql .= ")";

			dol_syslog(get_class($this)."::_create insert record into stock_mouvement", LOG_DEBUG);
			$resql = $this->db->query($sql);

			if ($resql) {
				$mvid = $this->db->last_insert_id($this->db->prefix()."stock_mouvement");
				$this->id = $mvid;
			} else {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				$error = -1;
			}

			// Define current values for qty and pmp
			$oldqty = $product->stock_reel;
			$oldpmp = $product->pmp;
			$oldqtywarehouse = 0;

			// Test if there is already a record for couple (warehouse / product), so later we will make an update or create.
			$alreadyarecord = 0;
			if (!$error) {
				$sql = "SELECT rowid, reel FROM ".$this->db->prefix()."product_stock";
				$sql .= " WHERE fk_entrepot = ".((int) $entrepot_id)." AND fk_product = ".((int) $fk_product); // This is a unique key

				dol_syslog(get_class($this)."::_create check if a record already exists in product_stock", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$obj = $this->db->fetch_object($resql);
					if ($obj) {
						$alreadyarecord = 1;
						$oldqtywarehouse = $obj->reel;
						$fk_product_stock = $obj->rowid;
					}
					$this->db->free($resql);
				} else {
					$this->errors[] = $this->db->lasterror();
					$error = -2;
				}
			}

			// Calculate new AWP (PMP)
			$newpmp = 0;
			if (!$error) {
				if ($type == 0 || $type == 3) {
					// After a stock increase
					// Note: PMP is calculated on stock input only (type of movement = 0 or 3). If type == 0 or 3, qty should be > 0.
					// Note: Price should always be >0 or 0. PMP should be always >0 (calculated on input)
					if ($price > 0 || (getDolGlobalString('STOCK_UPDATE_AWP_EVEN_WHEN_ENTRY_PRICE_IS_NULL') && $price == 0 && in_array($this->origin_type, array('order_supplier', 'invoice_supplier')))) {
						$oldqtytouse = ($oldqty >= 0 ? $oldqty : 0);
						// We make a test on oldpmp>0 to avoid to use normal rule on old data with no pmp field defined
						if ($oldpmp > 0) {
							$newpmp = price2num((($oldqtytouse * $oldpmp) + ($qty * $price)) / ($oldqtytouse + $qty), 'MU');
						} else {
							$newpmp = $price; // For this product, PMP was not yet set. We set it to input price.
						}
						//print "oldqtytouse=".$oldqtytouse." oldpmp=".$oldpmp." oldqtywarehousetouse=".$oldqtywarehousetouse." ";
						//print "qty=".$qty." newpmp=".$newpmp;
						//exit;
					} else {
						$newpmp = $oldpmp;
					}
				} else {
					// ($type == 1 || $type == 2)
					//   -> After a stock decrease, we don't change value of the AWP/PMP of a product.
					// else
					//   Type of movement unknown
					$newpmp = $oldpmp;
				}
			}
			// Update stock quantity
			if (!$error) {
				if ($alreadyarecord > 0) {
					$sql = "UPDATE ".$this->db->prefix()."product_stock SET reel = " . ((float) $oldqtywarehouse + (float) $qty);
					$sql .= " WHERE fk_entrepot = ".((int) $entrepot_id)." AND fk_product = ".((int) $fk_product);
				} else {
					$sql = "INSERT INTO ".$this->db->prefix()."product_stock";
					$sql .= " (reel, fk_entrepot, fk_product) VALUES ";
					$sql .= " (".((float) $qty).", ".((int) $entrepot_id).", ".((int) $fk_product).")";
				}

				dol_syslog(get_class($this)."::_create update stock value", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->errors[] = $this->db->lasterror();
					$error = -3;
				} elseif (empty($fk_product_stock)) {
					$fk_product_stock = $this->db->last_insert_id($this->db->prefix()."product_stock");
				}
			}

			// Update detail of stock for the lot.
			if (!$error && isModEnabled('productbatch') && (($product->hasbatch() && !$skip_batch) || $force_update_batch)) {
				if ($id_product_batch > 0) {
					$result = $this->createBatch($id_product_batch, $qty);
					if ($result == -2 && $fk_product_stock > 0) {	// The entry for this product batch does not exists anymore, bu we already have a llx_product_stock, so we recreate the batch entry in product_batch
						$param_batch = array('fk_product_stock' => $fk_product_stock, 'batchnumber' => $batch);
						$result = $this->createBatch($param_batch, $qty);
					}
				} else {
					$param_batch = array('fk_product_stock' => $fk_product_stock, 'batchnumber' => $batch);
					$result = $this->createBatch($param_batch, $qty);
				}
				if ($result < 0) {
					$error++;
				}
			}

			// Update PMP and denormalized value of stock qty at product level
			if (!$error) {
				$newpmp = price2num($newpmp, 'MU');

				// $sql = "UPDATE ".$this->db->prefix()."product SET pmp = ".$newpmp.", stock = ".$this->db->ifsql("stock IS NULL", 0, "stock") . " + ".$qty;
				// $sql.= " WHERE rowid = ".((int) $fk_product);
				// Update pmp + denormalized fields because we change content of produt_stock. Warning: Do not use "SET p.stock", does not works with pgsql
				$sql = "UPDATE ".$this->db->prefix()."product as p SET pmp = ".((float) $newpmp).",";
				$sql .= " stock=(SELECT SUM(ps.reel) FROM ".$this->db->prefix()."product_stock as ps WHERE ps.fk_product = p.rowid)";
				$sql .= " WHERE rowid = ".((int) $fk_product);

				dol_syslog(get_class($this)."::_create update AWP", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->errors[] = $this->db->lasterror();
					$error = -4;
				}
			}

			if (empty($donotcleanemptylines)) {
				// If stock is now 0, we can remove entry into llx_product_stock, but only if there is no child lines into llx_product_batch (detail of batch, because we can imagine
				// having a lot1/qty=X and lot2/qty=-X, so 0 but we must not loose repartition of different lot.
				$sql = "DELETE FROM ".$this->db->prefix()."product_stock WHERE reel = 0 AND rowid NOT IN (SELECT fk_product_stock FROM ".$this->db->prefix()."product_batch as pb)";
				$resql = $this->db->query($sql);
				// We do not test error, it can fails if there is child in batch details
			}
		}

		// Add movement for sub products (recursive call)
		if (!$error && getDolGlobalString('PRODUIT_SOUSPRODUITS') && !getDolGlobalString('INDEPENDANT_SUBPRODUCT_STOCK') && empty($disablestockchangeforsubproduct)) {
			$error = $this->_createSubProduct($user, $fk_product, $entrepot_id, $qty, $type, 0, $label, $inventorycode, $datem); // we use 0 as price, because AWP must not change for subproduct
		}

		if ($movestock && !$error) {
			// Call trigger
			if ($product->stockable_product != Product::NOT_MANAGED_IN_STOCK ) {
				$result = $this->call_trigger('STOCK_MOVEMENT', $user);
				if ($result < 0) {
					$error++;
				}
			}
			// End call triggers
			// Check unicity for serial numbered equipment once all movement were done.
			if (!$error && isModEnabled('productbatch') && $product->hasbatch() && !$skip_batch) {
				if ($product->status_batch == 2 && $qty > 0) {	// We check only if we increased qty
					if ($this->getBatchCount($fk_product, $batch) > 1) {
						$error++;
						$this->errors[] = $langs->trans("TooManyQtyForSerialNumber", $product->ref, $batch);
					}
				}
			}
		}

		if (!$error) {
			$this->db->commit();
			return $mvid;
		} else {
			$this->db->rollback();
			dol_syslog(get_class($this)."::_create error code=".$error, LOG_ERR);
			return -6;
		}
	}



	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id  Id object
	 *
	 * @return int Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.tms,";
		$sql .= " t.datem,";
		$sql .= " t.fk_product,";
		$sql .= " t.fk_entrepot,";
		$sql .= " t.value,";
		$sql .= " t.price,";
		$sql .= " t.type_mouvement,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.label,";
		$sql .= " t.fk_origin as origin_id,";
		$sql .= " t.origintype as origin_type,";
		$sql .= " t.inventorycode,";
		$sql .= " t.batch,";
		$sql .= " t.eatby,";
		$sql .= " t.sellby,";
		$sql .= " t.fk_projet as fk_project";
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		$sql .= " WHERE t.rowid = ".((int) $id);

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->product_id = $obj->fk_product;
				$this->warehouse_id = $obj->fk_entrepot;
				$this->qty = $obj->value;
				$this->type = $obj->type_mouvement;

				$this->tms = $this->db->jdate($obj->tms);
				$this->datem = $this->db->jdate($obj->datem);
				$this->price = $obj->price;
				$this->fk_user_author = $obj->fk_user_author;
				$this->label = $obj->label;
				$this->fk_origin = $obj->origin_id;		// For backward compatibility
				$this->origintype = $obj->origin_type;	// For backward compatibility
				$this->origin_id = $obj->origin_id;
				$this->origin_type = $obj->origin_type;
				$this->inventorycode = $obj->inventorycode;
				$this->batch = $obj->batch;
				$this->eatby = $this->db->jdate($obj->eatby);
				$this->sellby = $this->db->jdate($obj->sellby);
				$this->fk_project = $obj->fk_project;
			}

			// Retrieve all extrafield
			$this->fetch_optionals();

			// $this->fetch_lines();

			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}




	/**
	 *  Create movement in database for all subproducts
	 *
	 * 	@param 		User			$user			Object user
	 * 	@param		int				$idProduct		Id product
	 * 	@param		int				$entrepot_id	Warehouse id
	 * 	@param		float			$qty			Quantity
	 * 	@param		int				$type			Type
	 * 	@param		int				$price			Price
	 * 	@param		string			$label			Label of movement
	 *  @param		string			$inventorycode	Inventory code
	 *  @param		integer|string	$datem			Force date of movement
	 * 	@return 	int     		Return integer <0 if KO, 0 if OK
	 */
	private function _createSubProduct($user, $idProduct, $entrepot_id, $qty, $type, $price = 0, $label = '', $inventorycode = '', $datem = '')
	{
		global $langs;

		$error = 0;
		$pids = array();
		$pqtys = array();

		$sql = "SELECT fk_product_pere, fk_product_fils, qty";
		$sql .= " FROM ".$this->db->prefix()."product_association";
		$sql .= " WHERE fk_product_pere = ".((int) $idProduct);
		$sql .= " AND incdec = 1";

		dol_syslog(get_class($this)."::_createSubProduct for parent product ".$idProduct, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$i = 0;
			while ($obj = $this->db->fetch_object($resql)) {
				$pids[$i] = $obj->fk_product_fils;
				$pqtys[$i] = $obj->qty;
				$i++;
			}
			$this->db->free($resql);
		} else {
			$error = -2;
		}

		// Create movement for each subproduct
		foreach ($pids as $key => $value) {
			if (!$error) {
				$tmpmove = dol_clone($this, 1);

				$result = $tmpmove->_create($user, $pids[$key], $entrepot_id, ($qty * $pqtys[$key]), $type, 0, $label, $inventorycode, $datem); // This will also call _createSubProduct making this recursive
				if ($result < 0) {
					$this->error = $tmpmove->error;
					$this->errors = array_merge($this->errors, $tmpmove->errors);
					if ($result == -2) {
						$this->errors[] = $langs->trans("ErrorNoteAlsoThatSubProductCantBeFollowedByLot");
					}
					$error = $result;
				}
				unset($tmpmove);
			}
		}

		return $error;
	}


	/**
	 *	Decrease stock for product and subproducts
	 *
	 * 	@param 		User			$user			    	Object user
	 * 	@param		int				$fk_product		    	Id product
	 * 	@param		int				$entrepot_id	    	Warehouse id
	 * 	@param		float			$qty			    	Quantity
	 * 	@param		int				$price			    	Price
	 * 	@param		string			$label			    	Label of stock movement
	 * 	@param		int|string		$datem			    	Force date of movement
	 *	@param		int|string		$eatby			    	eat-by date
	 *	@param		int|string		$sellby			    	sell-by date
	 *	@param		string			$batch			    	batch number
	 * 	@param		int				$id_product_batch		Id product_batch
	 *  @param      string  		$inventorycode      	Inventory code
	 *  @param		int				$donotcleanemptylines	Do not clean lines that remains in stock table with qty=0 (because we want to have this done by the caller)
	 * 	@return		int								    	Return integer <0 if KO, >0 if OK
	 */
	public function livraison($user, $fk_product, $entrepot_id, $qty, $price = 0, $label = '', $datem = '', $eatby = '', $sellby = '', $batch = '', $id_product_batch = 0, $inventorycode = '', $donotcleanemptylines = 0)
	{
		global $conf;

		$skip_batch = empty($conf->productbatch->enabled);

		return $this->_create($user, $fk_product, $entrepot_id, (0 - $qty), 2, $price, $label, $inventorycode, $datem, $eatby, $sellby, $batch, $skip_batch, $id_product_batch, 0, $donotcleanemptylines);
	}

	/**
	 *	Increase stock for product and subproducts
	 *
	 * 	@param 		User			$user			     	Object user
	 * 	@param		int				$fk_product		     	Id product
	 * 	@param		int				$entrepot_id	     	Warehouse id
	 * 	@param		float			$qty			     	Quantity
	 * 	@param		int				$price			     	Price
	 * 	@param		string			$label			     	Label of stock movement
	 *	@param		integer|string	$eatby			     	eat-by date
	 *	@param		integer|string	$sellby			     	sell-by date
	 *	@param		string			$batch			     	batch number
	 * 	@param		integer|string	$datem			     	Force date of movement
	 * 	@param		int				$id_product_batch    	Id product_batch
	 *  @param      string			$inventorycode       	Inventory code
	 *  @param		int				$donotcleanemptylines	Do not clean lines that remains in stock table with qty=0 (because we want to have this done by the caller)
	 *	@return		int								     	Return integer <0 if KO, >0 if OK
	 */
	public function reception($user, $fk_product, $entrepot_id, $qty, $price = 0, $label = '', $eatby = '', $sellby = '', $batch = '', $datem = '', $id_product_batch = 0, $inventorycode = '', $donotcleanemptylines = 0)
	{
		global $conf;

		$skip_batch = empty($conf->productbatch->enabled);

		return $this->_create($user, $fk_product, $entrepot_id, $qty, 3, $price, $label, $inventorycode, $datem, $eatby, $sellby, $batch, $skip_batch, $id_product_batch, 0, $donotcleanemptylines);
	}

	/**
	 * Count number of product in stock before a specific date
	 *
	 * @param 	int			$productidselected		Id of product to count
	 * @param 	integer 	$datebefore				Date limit
	 * @return	int			Number
	 */
	public function calculateBalanceForProductBefore($productidselected, $datebefore)
	{
		$nb = 0;

		$sql = "SELECT SUM(value) as nb from ".$this->db->prefix()."stock_mouvement";
		$sql .= " WHERE fk_product = ".((int) $productidselected);
		$sql .= " AND datem < '".$this->db->idate($datebefore)."'";

		dol_syslog(get_class($this).__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$nb = $obj->nb;
			}
			return (empty($nb) ? 0 : $nb);
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * Create or update batch record (update table llx_product_batch). No check is done here, done by parent.
	 *
	 * @param	array|int	$dluo	      Could be either
	 *                                    - int if row id of product_batch table (for update)
	 *                                    - or complete array('fk_product_stock'=>, 'batchnumber'=>)
	 * @param	float		$qty	      Quantity of product with batch number. May be a negative amount.
	 * @return 	int   				      Return integer <0 if KO, -2 if we try to update a product_batchid that does not exist, else return productbatch id
	 */
	private function createBatch($dluo, $qty)
	{
		global $user, $langs;

		$langs->load('productbatch');

		$pdluo = new Productbatch($this->db);

		$result = 0;

		// Try to find an existing record with same batch number or id
		if (is_numeric($dluo)) {
			$result = $pdluo->fetch($dluo);
			if (empty($pdluo->id)) {
				// We didn't find the line. May be it was deleted before by a previous move in same transaction.
				$this->error = $langs->trans('CantMoveNonExistantSerial');
				$this->errors[] = $this->error;
				$result = -2;
			}
		} elseif (is_array($dluo)) {
			if (isset($dluo['fk_product_stock'])) {
				$vfk_product_stock = $dluo['fk_product_stock'];
				$vbatchnumber = $dluo['batchnumber'];

				$result = $pdluo->find($vfk_product_stock, '', '', $vbatchnumber); // Search on batch number only (eatby and sellby are deprecated here)
			} else {
				dol_syslog(get_class($this)."::createBatch array param dluo must contain at least key fk_product_stock", LOG_ERR);
				$result = -1;
			}
		} else {
			dol_syslog(get_class($this)."::createBatch error invalid param dluo", LOG_ERR);
			$result = -1;
		}

		if ($result >= 0) {
			// No error
			if ($pdluo->id > 0) {	// product_batch record found
				//print "Avant ".$pdluo->qty." Apres ".($pdluo->qty + $qty)."<br>";
				$pdluo->qty += $qty;
				if ($pdluo->qty == 0) {
					$result = $pdluo->delete($user, 1);
				} else {
					$result = $pdluo->update($user, 1);
				}
			} else {					// product_batch record not found
				$pdluo->fk_product_stock = $vfk_product_stock;
				$pdluo->qty = $qty;
				$pdluo->eatby = empty($dluo['eatby']) ? '' : $dluo['eatby'];		// No more used. Now eatby date is store in table of lot, no more into prouct_batch table.
				$pdluo->sellby = empty($dluo['sellby']) ? '' : $dluo['sellby'];		// No more used. Now sellby date is store in table of lot, no more into prouct_batch table.
				$pdluo->batch = $vbatchnumber;

				$result = $pdluo->create($user, 1);
				if ($result < 0) {
					$this->error = $pdluo->error;
					$this->errors = $pdluo->errors;
				}
			}
		}

		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return Url link of origin object
	 *
	 * @param  int		$origin_id		Id origin
	 * @param  string	$origin_type	Type origin ('project', 'xxx@MODULENAME', etc)
	 * @return string
	 */
	public function get_origin($origin_id, $origin_type)
	{
		// phpcs:enable
		$origin = '';

		switch ($origin_type) {
			case 'commande':
				require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
				$origin = new Commande($this->db);
				break;
			case 'shipping':
				require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
				$origin = new Expedition($this->db);
				break;
			case 'facture':
				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				$origin = new Facture($this->db);
				break;
			case 'order_supplier':
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
				$origin = new CommandeFournisseur($this->db);
				break;
			case 'invoice_supplier':
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
				$origin = new FactureFournisseur($this->db);
				break;
			case 'project':
				require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				$origin = new Project($this->db);
				break;
			case 'mo':
				require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
				$origin = new Mo($this->db);
				break;
			case 'user':
				require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
				$origin = new User($this->db);
				break;
			case 'reception':
				require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';
				$origin = new Reception($this->db);
				break;
			case 'inventory':
				require_once DOL_DOCUMENT_ROOT.'/product/inventory/class/inventory.class.php';
				$origin = new Inventory($this->db);
				break;
			default:
				if ($origin_type) {
					// Separate origin_type with "@" : left part is class name, right part is module name
					$origin_type_array = explode('@', $origin_type);
					$classname = $origin_type_array[0];
					$modulename = empty($origin_type_array[1]) ? strtolower($classname) : $origin_type_array[1];

					$result = dol_include_once('/'.$modulename.'/class/'.$classname.'.class.php');

					if ($result) {
						$classname = ucfirst($classname);
						$origin = new $classname($this->db);
					}
				}
				break;
		}

		if (empty($origin) || !is_object($origin)) {
			return '';
		}

		if ($origin->fetch($origin_id) > 0) {
			return $origin->getNomUrl(1);
		}

		return '';
	}

	/**
	 * Set attribute origin_type and fk_origin to object
	 *
	 * @param	string	$origin_element		Type of element
	 * @param	int		$origin_id			Id of element
	 * @param	int		$line_id_object_src	Id line of element Source
	 * @param	int		$line_id_object_origin	Id line of element Origin
	 *
	 * @return	void
	 */
	public function setOrigin($origin_element, $origin_id, $line_id_object_src = 0, $line_id_object_origin = 0)
	{
		$this->origin_type = $origin_element;
		$this->origin_id = $origin_id;
		$this->line_id_object_src = $line_id_object_src;
		$this->line_id_object_origin = $line_id_object_origin;
		// For backward compatibility
		$this->origintype = $origin_element;
		$this->fk_origin = $origin_id;
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return int
	 */
	public function initAsSpecimen()
	{
		// Initialize parameters
		$this->id = 0;

		// There is no specific properties. All data into insert are provided as method parameter.

		return 1;
	}

	/**
	 *  Return html string with picto for type of movement
	 *
	 *	@param	int		$withlabel			With label
	 *	@return	string					    String with URL
	 */
	public function getTypeMovement($withlabel = 0)
	{
		global $langs;

		$s = '';
		switch ($this->type) {
			case "0":
				$s = '<span class="fa fa-level-down-alt stockmovemententry stockmovementtransfer" title="'.$langs->trans('StockIncreaseAfterCorrectTransfer').'"></span>';
				if ($withlabel) {
					$s .= $langs->trans('StockIncreaseAfterCorrectTransfer');
				}
				break;
			case "1":
				$s = '<span class="fa fa-level-up-alt stockmovementexit stockmovementtransfer" title="'.$langs->trans('StockDecreaseAfterCorrectTransfer').'"></span>';
				if ($withlabel) {
					$s .= $langs->trans('StockDecreaseAfterCorrectTransfer');
				}
				break;
			case "2":
				$s = '<span class="fa fa-long-arrow-alt-up stockmovementexit stockmovement" title="'.$langs->trans('StockDecrease').'"></span>';
				if ($withlabel) {
					$s .= $langs->trans('StockDecrease');
				}
				break;
			case "3":
				$s = '<span class="fa fa-long-arrow-alt-down stockmovemententry stockmovement" title="'.$langs->trans('StockIncrease').'"></span>';
				if ($withlabel) {
					$s .= $langs->trans('StockIncrease');
				}
				break;
		}

		return $s;
	}

	/**
	 *  Return a link (with optionally the picto)
	 * 	Use this->id,this->lastname, this->firstname
	 *
	 *	@param	int		$withpicto			Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option				On what the link point to ('' = Tab of stock movement of warehouse, 'movements' = list of movements)
	 *  @param	integer	$notooltip			1=Disable tooltip
	 *  @param	int		$maxlen				Max length of visible user name
	 *  @param  string  $morecss            Add more css on link
	 *	@return	string						String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $maxlen = 24, $morecss = '')
	{
		global $langs, $conf, $db;

		$result = '';

		$label = img_picto('', 'stock', 'class="pictofixedwidth"').'<u>'.$langs->trans("StockMovement").'</u>';
		$label .= '<div width="100%">';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->id;
		$label .= '<br><b>'.$langs->trans('Label').':</b> '.$this->label;
		$qtylabel = (($this->qty > 0) ? '<span class="stockmovemententry">+' : '<span class="stockmovementexit">') . $this->qty . '</span>';
		$label .= '<br><b>'.$langs->trans('Qty').':</b> ' . $qtylabel;
		if ($this->batch) {
			$label .= '<br><b>'.$langs->trans('Batch').':</b> '.$this->batch;
		}
		/* TODO Get also warehouse label in a property instead of id
		if ($this->warehouse_id > 0) {
			$label .= '<br><b>'.$langs->trans('Warehouse').':</b> '.$this->warehouse_id;
		}*/
		$label .= '</div>';

		// Link to page of warehouse tab
		if ($option == 'movements') {
			$url = DOL_URL_ROOT.'/product/stock/movement_list.php?search_ref='.$this->id;
		} else {
			$url = DOL_URL_ROOT.'/product/stock/movement_list.php?id='.$this->warehouse_id.'&msid='.$this->id;
		}

		$link = '<a href="'.$url.'"'.($notooltip ? '' : ' title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip'.($morecss ? ' '.$morecss : '').'"');
		$link .= '>';
		$linkend = '</a>';

		if ($withpicto) {
			$result .= ($link.img_object(($notooltip ? '' : $label), 'stock', ($notooltip ? '' : 'class="classfortooltip"')).$linkend);
			if ($withpicto != 2) {
				$result .= ' ';
			}
		}
		$result .= $link.$this->id.$linkend;
		return $result;
	}

	/**
	 *  Return label statut
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function LibStatut($mode = 0)
	{
		// phpcs:enable
		global $langs;

		if ($mode == 0 || $mode == 1) {
			return $langs->trans('StatusNotApplicable');
		} elseif ($mode == 2) {
			return img_picto($langs->trans('StatusNotApplicable'), 'statut9').' '.$langs->trans('StatusNotApplicable');
		} elseif ($mode == 3) {
			return img_picto($langs->trans('StatusNotApplicable'), 'statut9');
		} elseif ($mode == 4) {
			return img_picto($langs->trans('StatusNotApplicable'), 'statut9').' '.$langs->trans('StatusNotApplicable');
		} elseif ($mode == 5) {
			return $langs->trans('StatusNotApplicable').' '.img_picto($langs->trans('StatusNotApplicable'), 'statut9');
		}

		return 'Bad value for mode';
	}

	/**
	 *	Create object on disk
	 *
	 *	@param     string		$modele			force le modele a utiliser ('' to not force)
	 * 	@param     Translate	$outputlangs	Object langs to use for output
	 *  @param     int			$hidedetails    Hide details of lines
	 *  @param     int			$hidedesc       Hide description
	 *  @param     int			$hideref        Hide ref
	 *  @return    int             				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $conf, $user, $langs;

		$langs->load("stocks");
		$outputlangs->load("products");

		if (!dol_strlen($modele)) {
			$modele = 'stdmovement';

			if ($this->model_pdf) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('MOUVEMENT_ADDON_PDF')) {
				$modele = getDolGlobalString('MOUVEMENT_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/stock/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}

	/**
	 * Delete object in database
	 *
	 * @param User 	$user       User that deletes
	 * @param int 	$notrigger  0=launch triggers after, 1=disable triggers
	 * @return int             	Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 * Retrieve number of equipment for a product lot/serial
	 *
	 * @param 	int 		$fk_product 	Product id
	 * @param 	string 		$batch  		batch number
	 * @return 	int            				Return integer <0 if KO, number of equipment found if OK
	 */
	private function getBatchCount($fk_product, $batch)
	{
		$cpt = 0;

		$sql = "SELECT sum(pb.qty) as cpt";
		$sql .= " FROM ".$this->db->prefix()."product_batch as pb";
		$sql .= " INNER JOIN ".$this->db->prefix()."product_stock as ps ON ps.rowid = pb.fk_product_stock";
		$sql .= " WHERE ps.fk_product = " . ((int) $fk_product);
		$sql .= " AND pb.batch = '" . $this->db->escape($batch) . "'";

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$cpt = $obj->cpt;
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
			return -1;
		}

		return $cpt;
	}

	/**
	 * reverse movement for object by updating infos
	 * @return int    1 if OK,-1 if KO
	 */
	public function reverseMouvement()
	{
		$formattedDate = "REVERTMV" .dol_print_date($this->datem, '%Y%m%d%His');
		if ($this->label == 'Annulation movement ID'.$this->id) {
			return -1;
		}
		if ($this->inventorycode == $formattedDate) {
			return -1;
		}

		$sql = "UPDATE ".$this->db->prefix()."stock_mouvement SET";
		$sql .= " label = 'Annulation movement ID ".((int) $this->id)."',";
		$sql .= "inventorycode = '".($formattedDate)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);

		if ($resql) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}
}
