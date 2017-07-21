<?php
/* Copyright (C) 2016		ATM Consulting			<support@atm-consulting.fr>
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
 *	\file       htdocs/inventory/class/product.class.php
 *	\ingroup    product
 *	\brief      File of class to manage predefined products stock
 */
 
require_once DOL_DOCUMENT_ROOT.'/core/class/coreobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

/**
 *	Class to manage inventories
 */
class Inventory extends CoreObject
{
	public $element='inventory';
	public $table_element='inventory';
	public $fk_element='fk_inventory';
	protected $childtables=array('inventorydet');    // To test if we can delete object
	protected $isnolinkedbythird = 1;     // No field fk_soc
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	
	/**
	 * Warehouse Id
	 * @var int
	 */
	public $fk_warehouse;
	/**
	 * Entity Id
	 * @var int
	 */
	public $entity;
	
	/**
	 * Status
	 * @var int
	 */
	public $status;
	/**
	 * Inventory Date
	 * @var date
	 */
	public $date_inventory;
	/**
	 * Inventory Title
	 * @var string
	 */
	public $title;

    /**
     * Attribute object linked with database
     * @var array
     */
	protected $fields=array(
		'fk_warehouse'=>array('type'=>'integer','index'=>true)
	    ,'ref'=>array('type'=>'string','index'=>true)
		,'entity'=>array('type'=>'integer','index'=>true)
		,'status'=>array('type'=>'integer','index'=>true)
		,'date_inventory'=>array('type'=>'date')
		,'title'=>array('type'=>'string')
	);

    /**
     *  Constructor
     *
     *  @param      DoliDB		$db      Database handler
     */
	public function __construct(DoliDB &$db) 
	{
		global $conf;

        parent::__construct($db);
		parent::init();
		
       	$this->status = 0;
		$this->entity = $conf->entity;
		$this->errors = array();
		$this->amount = 0;
	}

    /**
     * Function to sort children object
     */
	public function sortDet()
	{
		if(!empty($this->Inventorydet))	usort($this->Inventorydet, array('Inventory', 'customSort'));
	}

    /**
     *	Get object and children from database
     *
     *	@param      int			$id       		Id of object to load
     * 	@param		bool		$loadChild		used to load children from database
     *	@return     int         				>0 if OK, <0 if KO, 0 if not found
     */
	public function fetch($id, $loadChild = true)
	{
        if(!$loadChild) $this->withChild = false;
        
		$res = parent::fetch($id, $loadChild);

		if ($res > 0)
		{
			$this->sortDet();
			$this->amount = 0;
			if(!empty($this->Inventorydet ))
			{
				foreach($this->Inventorydet as &$det)
				{
					$this->amount += $det->qty_view * $det->pmp;
				}
			}
		}
				
		return $res;
	}

    /**
     * Custom function call by usort
     *
     * @param   Inventorydet    $objA   first Inventorydet object
     * @param   Inventorydet    $objB   second Inventorydet object
     * @return                          int
     */
	private function customSort(&$objA, &$objB)
	{
		$r = strcmp(strtoupper(trim($objA->product->ref)), strtoupper(trim($objB->product->ref)));
		
		if ($r < 0) $r = -1;
		elseif ($r > 0) $r = 1;
		else $r = 0;
		
		return $r;
	}

    /**
     * @param   User    $user   user object
     * @return                  int
     */
    public function changePMP(User &$user)
    {
        $error = 0;
        $this->db->begin();

		if(!empty($this->Inventorydet))
		{
			foreach ($this->Inventorydet as $k => &$Inventorydet)
			{
				if($Inventorydet->new_pmp>0)
				{
					$Inventorydet->pmp = $Inventorydet->new_pmp; 
					$Inventorydet->new_pmp = 0;
				
					$res = $this->db->query('UPDATE '.MAIN_DB_PREFIX.'product as p SET pmp = '.$Inventorydet->pmp.' WHERE rowid = '.$Inventorydet->fk_product );
					if (!$res)
                    {
                        $error++;
                        $this->error = $this->db->lasterror();
                        $this->errors[] = $this->db->lasterror();
                    }
				}
			}
		}
		
		$res = parent::update($user);
        if (!$res)
        {
            $error++;
            $this->error = $this->db->lasterror();
            $this->errors[] = $this->db->lasterror();
        }


        if (!$error)
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
     * Function to update object or create or delete if needed
     *
     * @param   User    $user   user object
     * @return                  < 0 if ko, > 0 if ok
     */
	public function update(User &$user)
	{
		$error = 0;
		$this->db->begin();

        // if we valid the inventory we save the stock at the same time
		if ($this->status)
		{
		    $res = $this->regulate();
            if ($res < 0)
            {
                $error++;
                $this->error = $this->db->lasterror();
                $this->errors[] = $this->db->lasterror();
            }
		}

        $res = parent::update($user);
        if (!$res)
        {
            $error++;
            $this->error = $this->db->lasterror();
            $this->errors[] = $this->db->lasterror();
        }

		if (!$error)
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
     * Function to update current object
     *
     * @param   array   $Tab    Array of values
     * @return                  int
     */
	public function setValues(&$Tab)
	{
		global $langs;
		
		if (isset($Tab['qty_to_add']))
		{
			foreach ($Tab['qty_to_add'] as $k => $qty)
			{
				$qty = (float) price2num($qty);
				
				if ($qty < 0) 
				{
					$this->errors[] = $langs->trans('inventoryErrorQtyAdd');
					return -1;
				} 
				
				$product = new Product($this->db);
				$product->fetch($this->Inventorydet[$k]->fk_product);
				
				$this->Inventorydet[$k]->pmp = $product->pmp;
				$this->Inventorydet[$k]->qty_view += $qty;
			}	
		}
		
		return parent::setValues($Tab);
	}

    /**
     * Function to delete all Inventorydet
     *
     * @param   User    $user   user object
     * @return                  < 0 if ko, > 0 if ok
     */
    public function deleteAllLine(User &$user)
    {
        foreach($this->Inventorydet as &$det)
        {
            $det->to_delete = true;
        }
        
        $res = $this->update($user);

        if ($res > 0) $this->Inventorydet = array();
        else return -1;
    }

    /**
     * Function to add Inventorydet
     *
     * @param   int     $fk_product     fk_product of Inventorydet
     * @param   int     $fk_warehouse   fk_warehouse target
     * @return                          bool
     */
    public function addProduct($fk_product, $fk_warehouse=0)
    {
        $k = $this->addChild('Inventorydet');
        $det =  &$this->Inventorydet[$k];
        
        $det->fk_inventory = $this->id;
        $det->fk_product = $fk_product;
		$det->fk_warehouse = empty($fk_warehouse) ? $this->fk_warehouse : $fk_warehouse;
        
        $det->load_product();
                
        $date = $this->getDate('date_inventory', 'Y-m-d');
        if(empty($date)) $date = $this->getDate('datec', 'Y-m-d');
        $det->setStockDate($date, $fk_warehouse);
        
        return true;
    }

    /**
     *  Duplication method product to add datem
     *  Adjust stock in a warehouse for product
     *
     *  @param  	int     $fk_product     id of product
     *  @param  	int		$fk_warehouse   id of warehouse
     *  @param  	double	$nbpiece        nb of units
     *  @param  	int		$movement       0 = add, 1 = remove
     * 	@param		string	$label			Label of stock movement
     * 	@param		double	$price			Unit price HT of product, used to calculate average weighted price (PMP in french). If 0, average weighted price is not changed.
     *  @param		string	$inventorycode	Inventory code
     * 	@return     int     				<0 if KO, >0 if OK
     */
    public function correctStock($fk_product, $fk_warehouse, $nbpiece, $movement, $label='', $price=0, $inventorycode='')
	{
		global $conf, $user;

		if ($fk_warehouse)
		{
			$this->db->begin();

			require_once DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php';

			$op[0] = "+".trim($nbpiece);
			$op[1] = "-".trim($nbpiece);

			$datem = empty($conf->global->INVENTORY_USE_INVENTORY_DATE_FROM_DATEMVT) ? dol_now() : $this->date_inventory;

			$movementstock=new MouvementStock($this->db);
			$movementstock->origin = new stdClass();
			$movementstock->origin->element = 'inventory';
			$movementstock->origin->id = $this->id;
			$result=$movementstock->_create($user,$fk_product,$fk_warehouse,$op[$movement],$movement,$price,$label,$inventorycode, $datem);
			
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
     * Function to regulate stock
     *
     * @return      int
     */
	public function regulate()
	{
		global $langs,$conf;
		
		if($conf->global->INVENTORY_DISABLE_VIRTUAL)
		{
			$pdt_virtuel = false;
			// Test if virtual product is enabled
			if($conf->global->PRODUIT_SOUSPRODUITS)
			{
				$pdt_virtuel = true;
				$conf->global->PRODUIT_SOUSPRODUITS = 0;
			}
		}
		
		foreach ($this->Inventorydet as $k => $Inventorydet)
		{
			$product = new Product($this->db);
			$product->fetch($Inventorydet->fk_product);

			if ($Inventorydet->qty_view != $Inventorydet->qty_stock)
			{
				$Inventorydet->qty_regulated = $Inventorydet->qty_view - $Inventorydet->qty_stock;
				$nbpiece = abs($Inventorydet->qty_regulated);
				$movement = (int) ($Inventorydet->qty_view < $Inventorydet->qty_stock); // 0 = add ; 1 = remove
						
				//$href = dol_buildpath('/inventory/inventory.php?id='.$this->id.'&action=view', 1);
				
				$res = $this->correctStock($product->id, $Inventorydet->fk_warehouse, $nbpiece, $movement, $langs->trans('inventoryMvtStock'));
				if ($res < 0) return -1;
			}
		}

		if($conf->global->INVENTORY_DISABLE_VIRTUAL)
		{
            // Test if virtual product was enabled before regulate
			if($pdt_virtuel) $conf->global->PRODUIT_SOUSPRODUITS = 1;
		}
		
		return 1;
	}

    /**
     * Get the title
     * @return  string
     */
	public function getTitle()
    {
		global $langs;
		
		return !empty($this->title) ? $this->title : $langs->trans('inventoryTitle').' '.$this->id;
	}


    /**
     * Return clicable link of object (with eventually picto)
     *
     * @param   int     $withpicto  Add picto into link
     * @return                      string
     */
	public function getNomUrl($withpicto = 1)
    {
        return '<a href="'.DOL_URL_ROOT.'/product/inventory/card.php?id='.$this->id.'">'.($withpicto ? img_picto('','object_list.png','',0).' ' : '').$this->getTitle().'</a>';
	}

    /**
     * Function to add products by default from warehouse and children
     *
     * @param int $fk_warehouse         id of warehouse
     * @param int $fk_category          id of category
     * @param int $fk_supplier          id of supplier
     * @param int $only_prods_in_stock  only product with stock
     *
     * @return int
     */
	public function addProductsFor($fk_warehouse,$fk_category=0,$fk_supplier=0,$only_prods_in_stock=0)
    {
        $warehouse = new Entrepot($this->db);
        $warehouse->fetch($fk_warehouse);
		$TChildWarehouses = array($fk_warehouse);
        $warehouse->get_children_warehouses($fk_warehouse, $TChildWarehouses);
			
		$sql = 'SELECT ps.fk_product, ps.fk_entrepot';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'product_stock ps';
        $sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'product p ON (p.rowid = ps.fk_product)';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product cp ON (cp.fk_product = p.rowid)';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_fournisseur_price pfp ON (pfp.fk_product = p.rowid)';
        $sql.= ' WHERE ps.fk_entrepot IN ('.implode(', ', $TChildWarehouses).')';
			
		if ($fk_category>0) $sql.= ' AND cp.fk_categorie='.$fk_category;
		if ($fk_supplier>0) $sql.= ' AND pfp.fk_soc = '.$fk_supplier;
		if (!empty($only_prods_in_stock)) $sql.= ' AND ps.reel > 0';
			
		$sql.=' GROUP BY ps.fk_product, ps.fk_entrepot ORDER BY p.ref ASC,p.label ASC';
		 
		$res = $this->db->query($sql);
		if($res)
		{
			while($obj = $this->db->fetch_object($res))
            {
				$this->addProduct($obj->fk_product, $obj->fk_entrepot);
			}

			return 1;
		}
		else
        {
            $this->error = $this->db->lasterror();
            $this->errors[] = $this->db->lasterror();
            return -1;
        }
	}

    /**
     * Return clicable link of inventory object
     *
     * @param   int     $id         id of inventory
     * @param   int     $withpicto  Add picto into link
     * @return  string
     */
    static function getLink($id, $withpicto=1)
    {
        global $langs,$db;
        
        $inventory = new Inventory($db);
        if($inventory->fetch($id, false) > 0) return $inventory->getNomUrl($withpicto);
        else return $langs->trans('InventoryUnableToFetchObject');
    }

    /**
     * Function to get the sql select of inventory
     * 
     * @param   string  $type   'All' to get all data
     * @return  string
     */
	static function getSQL($type)
    {
		global $conf;

        $sql = '';
		if($type == 'All')
		{
			$sql = 'SELECT i.rowid,i.title, e.label, i.date_inventory, i.fk_warehouse, i.datec, i.tms, i.status';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'inventory i';
            $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'entrepot e ON (e.rowid = i.fk_warehouse)';
            $sql.= ' WHERE i.entity IN ('.getEntity('inventory').')';
		}
	
		return $sql;	
	}
}

class Inventorydet extends CoreObject
{
	public $element='inventorydet';
	public $table_element='inventorydet';
	protected $isnolinkedbythird = 1;     // No field fk_soc
	protected $ismultientitymanaged = 0;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	
	public $fk_inventory;
	public $fk_warehouse;
	public $fk_product;
	public $entity;
	public $qty_view;
	public $qty_stock;
	public $qty_regulated;
	public $pmp;
	public $pa;
	public $new_pmp;
	
	protected $fields=array(
		'fk_inventory'=>array('type'=>'int')
		,'fk_warehouse'=>array('type'=>'int')
		,'fk_product'=>array('type'=>'int')
		,'entity'=>array('type'=>'int')
		,'qty_view'=>array('type'=>'float')
		,'qty_stock'=>array('type'=>'float')
		,'qty_regulated'=>array('type'=>'float')
		,'pmp'=>array('type'=>'float')
		,'pa'=>array('type'=>'float')
		,'new_pmp'=>array('type'=>'float')
	);

    /**
     *  Constructor
     *
     *  @param      DoliDB		$db      Database handler
     */
	function __construct(DoliDB &$db)
	{
		global $conf;

		parent::__construct($db);
		parent::init();
				
		$this->entity = $conf->entity;
		$this->errors = array();
		
		$this->product = null;
		$this->current_pa = 0;
	}

    /**
     * Get object and children from database
     *
     * @param   int   $id           id of inventorydet object
     * @param   bool  $loadChild    load children
     * @return  int
     */
	function fetch($id, $loadChild = true)
	{
		$res = parent::fetch($id);
		$this->load_product();
        $this->fetch_current_pa();
			
		return $res;
	}

    /**
     * Function to get the unit buy price
     *
     * @return bool
     */
    function fetch_current_pa()
    {
		global $db,$conf;
		
		if(empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)) return false;
		
		if($this->pa > 0)
		{
			$this->current_pa = $this->pa;
		}
		else
        {
			dol_include_once('/fourn/class/fournisseur.product.class.php');
			$p= new ProductFournisseur($db);
			$p->find_min_price_product_fournisseur($this->fk_product);
			
			if($p->fourn_qty>0)	$this->current_pa = $p->fourn_price / $p->fourn_qty;
		}

		return true;
	}

    /**
     * Function to set pa attribute from date en fk_warehouse
     *
     * @param   date    $date           date value
     * @param   int     $fk_warehouse   fk_warehouse target
     */
    function setStockDate($date, $fk_warehouse)
    {
		list($pmp, $stock) = $this->getPmpStockFromDate($date, $fk_warehouse);

        $this->qty_stock = $stock;
        $this->pmp = $pmp;

        $last_pa = 0;
        $sql = 'SELECT price FROM '.MAIN_DB_PREFIX.'stock_mouvement';
        $sql.= ' WHERE fk_entrepot = '.$fk_warehouse;
        $sql.= ' AND fk_product = '.$this->fk_product;
        $sql.= ' AND (origintype=\'order_supplier\' || origintype=\'invoice_supplier\')';
        $sql.= ' AND price > 0';
        $sql.= ' AND datem <= \''.$date.' 23:59:59\'';
        $sql.= ' ORDER BY datem DESC LIMIT 1';

        $res = $this->db->query($sql);
        if($res && $obj = $this->db->fetch_object($res))
        {
            $last_pa = $obj->price;
        }

        $this->pa = $last_pa;
    }


    /**
     * Get the last pmp and last stock from date and warehouse
     *
     * @param   date    $date           date to check
     * @param   int     $fk_warehouse   id of warehouse
     * @return array
     */
    function getPmpStockFromDate($date, $fk_warehouse)
    {
		$res = $this->product->load_stock();
		
		if($res>0)
		{
			$stock = isset($this->product->stock_warehouse[$fk_warehouse]->real) ? $this->product->stock_warehouse[$fk_warehouse]->real : 0;
            $pmp = $this->product->pmp;
		}
		
		//All Stock mouvement between now and inventory date
		$sql = 'SELECT value, price';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'stock_mouvement';
        $sql.= ' WHERE fk_product = '.$this->product->id;
        $sql.= ' AND fk_entrepot = '.$fk_warehouse;
        $sql.= ' AND datem > \''.date('Y-m-d 23:59:59', strtotime($date)).'\'';
        $sql.= ' ORDER BY datem DESC';

		$res = $this->db->query($sql);
		
		$laststock = $stock;
		$lastpmp = $pmp;
		
		if($res)
		{
			while($mouvement = $this->db->fetch_object($res))
            {
				$price = ($mouvement->price > 0 && $mouvement->value > 0) ? $mouvement->price : $lastpmp;
				$stock_value = $laststock * $lastpmp;
				$laststock -= $mouvement->value;
				$last_stock_value = $stock_value - ($mouvement->value * $price);
				$lastpmp = ($laststock != 0) ? $last_stock_value / $laststock : $lastpmp;
			}
		}
		
		return array($lastpmp, $laststock);
	}

    /**
     * Fetch the product linked with the line
     * @return  void
     */
	function load_product() 
	{
		global $db;
		
		if($this->fk_product>0)
		{
			$this->product = new Product($db);
			$this->product->fetch($this->fk_product);
		}
	}
}
