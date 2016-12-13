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
 *	\file       htdocs/inventory/class/product.inventory.php
 *	\ingroup    product
 *	\brief      File of class to manage predefined products stock
 */
 
require_once DOL_DOCUMENT_ROOT.'/core/class/coreobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

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
	
	protected $__fields=array(
		'fk_warehouse'=>array('type'=>'integer','index'=>true)
		,'entity'=>array('type'=>'integer','index'=>true)
		,'status'=>array('type'=>'integer','index'=>true)
		,'date_inventory'=>array('type'=>'date')
		,'title'=>array('type'=>'string')
	);
	
	public $db;
	
	function __construct(DoliDB &$db) 
	{
		
		$this->db = &$db;
		
		parent::init();
		
       	$this->status = 0;
		$this->entity = $conf->entity;
		$this->errors = array();
		$this->amount = 0;
		
	}
	
	function sort_det() 
	{

		if(!empty($this->Inventorydet))	usort($this->Inventorydet, array('Inventory', 'customSort'));
	}
	
	function fetch($id,$annexe = true) 
	{
	    
        if(!$annexe) $this->withChild = false;
        
		$res = parent::fetch($id);
		$this->sort_det();
		
		$this->amount = 0;
		
		if(!empty($this->Inventorydet ))  {
			foreach($this->Inventorydet as &$det){
				$this->amount+=$det->qty_view * $det->pmp;
			}
			
		}
		
		return $res;
	}
	
	
	function customSort(&$objA, &$objB)
	{
		global $db;
		
		$r = strcmp(strtoupper(trim($objA->product->ref)), strtoupper(trim($objB->product->ref)));
		
		if ($r < 0) $r = -1;
		elseif ($r > 0) $r = 1;
		else $r = 0;
		
		return $r;
	}
	
	function changePMP() {
		
		foreach ($this->Inventorydet as $k => &$Inventorydet)
		{
			
			if($Inventorydet->new_pmp>0) {
				$Inventorydet->pmp = $Inventorydet->new_pmp; 
				$Inventorydet->new_pmp = 0;
			
				$db->query("UPDATE ".MAIN_DB_PREFIX."product as p SET pmp = ".$Inventorydet->pmp."
				WHERE rowid = ".$Inventorydet->fk_product );
				
			}
		}
		
		parent::save($PDOdb);
		
	}
	
	function update()
	{
		//si on valide l'inventaire on sauvegarde le stock à cette instant
		if ($this->status)
		{
			 $this->regulate();
		}
		
		parent::update();
	}
	
	function set_values($Tab)
	{
		global $db,$langs;
		
		if (isset($Tab['qty_to_add']))
		{
			foreach ($Tab['qty_to_add'] as $k => $qty)
			{
				$qty = (float) price2num($qty);
				
				if ($qty < 0) 
				{
					$this->errors[] = $langs->trans('inventoryErrorQtyAdd');
					return 0;
				} 
				
				$product = new Product($db);
				$product->fetch($this->Inventorydet[$k]->fk_product);
				
				$this->Inventorydet[$k]->pmp = $product->pmp;
				$this->Inventorydet[$k]->qty_view += $qty;
			}	
		}
		
		return parent::set_values($Tab);
	}
	
    function deleteAllLine() {
        
        foreach($this->Inventorydet as &$det) {
            $det->to_delete = true;
        }
        
        $this->update();
      
        $this->Inventorydet=array();
        
    }
    
    function add_product($fk_product, $fk_entrepot='') {
        
        $k = $this->addChild('Inventorydet');
        $det =  &$this->Inventorydet[$k];
        
        $det->fk_inventory = $this->id;
        $det->fk_product = $fk_product;
		$det->fk_warehouse = empty($fk_entrepot) ? $this->fk_warehouse : $fk_entrepot;
        
        $det->load_product();
                
        $date = $this->get_date('date_inventory', 'Y-m-d');
        if(empty($date))$date = $this->get_date('date_cre', 'Y-m-d'); 
        $det->setStockDate( $date , $fk_entrepot);
        
    }
    
    function correct_stock($fk_product, $fk_warehouse, $nbpiece, $movement, $label='', $price=0, $inventorycode='')
	{
		global $conf, $db, $langs, $user;
		
		/* duplication method product to add datem */
		if ($fk_warehouse)
		{
			$db->begin();

			require_once DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php';

			$op[0] = "+".trim($nbpiece);
			$op[1] = "-".trim($nbpiece);

			$datem = empty($conf->global->INVENTORY_USE_INVENTORY_DATE_FROM_DATEMVT) ? dol_now() : $this->date_inventory;

			$movementstock=new MouvementStock($db);
			$result=$movementstock->_create($user,$fk_product,$fk_warehouse,$op[$movement],$movement,$price,$label,$inventorycode, $datem);
			
			if ($result >= 0)
			{
				$db->commit();
				return 1;
			}
			else
			{
			    $this->error=$movementstock->error;
			    $this->errors=$movementstock->errors;

				$db->rollback();
				return -1;
			}
		}
	}
	
	function regulate()
	{
		global $db,$user,$langs,$conf;
		
		if($conf->global->INVENTORY_DISABLE_VIRTUAL){
			$pdt_virtuel = false;
			// Test si pdt virtuel est activé
			if($conf->global->PRODUIT_SOUSPRODUITS)
			{
				$pdt_virtuel = true;
				$conf->global->PRODUIT_SOUSPRODUITS = 0;
			}
		}
		
		foreach ($this->Inventorydet as $k => $Inventorydet)
		{
			$product = new Product($db);
			$product->fetch($Inventorydet->fk_product);
			
			/*
			 * Ancien code qui était pourri et qui modifié la valeur du stock théorique si le parent était déstocké le même jour que l'enfant
			 * 
			 * $product->load_stock();
			$Inventorydet->qty_stock = $product->stock_warehouse[$this->fk_warehouse]->real;
			
			if(date('Y-m-d', $this->date_inventory) < date('Y-m-d')) {
				$TRes = $Inventorydet->getPmpStockFromDate($PDOdb, date('Y-m-d', $this->date_inventory), $this->fk_warehouse);
				$Inventorydet->qty_stock = $TRes[1];
			}
			*/
			if ($Inventorydet->qty_view != $Inventorydet->qty_stock)
			{
				$Inventorydet->qty_regulated = $Inventorydet->qty_view - $Inventorydet->qty_stock;
				$nbpiece = abs($Inventorydet->qty_regulated);
				$movement = (int) ($Inventorydet->qty_view < $Inventorydet->qty_stock); // 0 = add ; 1 = remove
						
				$href = dol_buildpath('/inventory/inventory.php?id='.$this->id.'&action=view', 1);
				
				if(empty($this->title))
					$this->correct_stock($product->id, $Inventorydet->fk_warehouse, $nbpiece, $movement, $langs->trans('inventoryMvtStock', $href, $this->id));
				else
					$this->correct_stock($product->id, $Inventorydet->fk_warehouse, $nbpiece, $movement, $langs->trans('inventoryMvtStockWithNomInventaire', $href, $this->title));
			}
		}

		if($conf->global->INVENTORY_DISABLE_VIRTUAL){
			// Test si pdt virtuel était activé avant la régule
			if($pdt_virtuel) $conf->global->PRODUIT_SOUSPRODUITS = 1;
		}
		
		return 1;
	}
    
	public function getNomUrl($picto = 1) {
		global $langs;
		
		$title = !empty($this->title) ? $this->title : $langs->trans('inventoryTitle').' '.$this->id;
        
        return '<a href="'.dol_buildpath('/inventory/inventory.php?id='.$this->id, 1).'">'.($picto ? img_picto('','object_list.png','',0).' ' : '').$title.'</a>';
        
	} 
	
    static function getLink($id) {
        global $langs,$db;
        
        $i = new Inventory($db);
        $i->fetch($id, false);
        
        return $i->getNomUrl();
        
    }
	
	static function getSQL($type) {
		global $conf;
		
		if($type=='All') {
			
			$sql="SELECT i.rowid, e.label, i.date_inventory, i.fk_warehouse, i.datec, i.tms, i.status
				  FROM ".MAIN_DB_PREFIX."inventory i
				  LEFT JOIN ".MAIN_DB_PREFIX."entrepot e ON (e.rowid = i.fk_warehouse)
				  WHERE i.entity=".(int) $conf->entity;
			
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
	
	protected $__fields=array(
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
	
	
	function __construct(&$db)
	{
		global $conf;
		
		$this->db = &$db;
		
		parent::init();
				
		$this->entity = $conf->entity;
		$this->errors = array();
		
		$this->product = null;
		
		$this->current_pa = 0;
		
	}
	
	function fetch($id) 
	{
		global $conf;
		
		$res = parent::fetch($id);
		$this->load_product();
        $this->fetch_current_pa();
			
		return $res;
	}
	
	function fetch_current_pa() {
		global $db,$conf;
		
		if(empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)) return false;
		
		if($this->pa>0){ 
			$this->current_pa = $this->pa;
		}
		else {
			
			dol_include_once('/fourn/class/fournisseur.product.class.php');
			$p= new ProductFournisseur($db);
			$p->find_min_price_product_fournisseur($this->fk_product);
			
			if($p->fourn_qty>0)	$this->current_pa = $p->fourn_price / $p->fourn_qty;
		}
		return true;
	}
	
    function setStockDate(&$PDOdb, $date, $fk_warehouse) {
        
		list($pmp,$stock) = $this->getPmpStockFromDate($PDOdb, $date, $fk_warehouse);
		
        $this->qty_stock = $stock;
        $this->pmp = $pmp;
        
        $last_pa = 0;
        $sql = "SELECT price FROM ".MAIN_DB_PREFIX."stock_mouvement 
                WHERE fk_entrepot=".$fk_warehouse." 
                AND fk_product=".$this->fk_product." 
                AND (origintype='order_supplier' || origintype='invoice_supplier')
                AND price>0 
                AND datem<='".$date." 23:59:59'
                ORDER BY datem DESC LIMIT 1";
               
        $res = $db->query($sql);
       
        if($obj = $db->fetch_object($res)) {
            $last_pa = $obj->price;
        }
        
        $this->pa = $last_pa;
      /*  var_dump($fk_warehouse,$this->product->stock_warehouse,$this->pmp, $this->pa, $this->qty_stock);
        exit;*/
    }

	function getPmpStockFromDate(&$PDOdb, $date, $fk_warehouse){
		
		$res = $this->product->load_stock();
		
		if($res>0) {
			$stock = isset($this->product->stock_warehouse[$fk_warehouse]->real) ? $this->product->stock_warehouse[$fk_warehouse]->real : 0;
			
			if((float)DOL_VERSION<4.0) {
				$pmp = isset($this->product->stock_warehouse[$fk_warehouse]->pmp) ? $this->product->stock_warehouse[$fk_warehouse]->pmp : 0; 
			}
			else{
				$pmp = $this->product->pmp;
			}
			
		}
		
		//On récupère tous les mouvements de stocks du produit entre aujourd'hui et la date de l'inventaire
		$sql = "SELECT value, price
				FROM ".MAIN_DB_PREFIX."stock_mouvement
				WHERE fk_product = ".$this->product->id."
					AND fk_entrepot = ".$fk_warehouse."
					AND datem > '".date('Y-m-d 23:59:59',strtotime($date))."'
				ORDER BY datem DESC";

		//echo $sql.'<br>';
		$db->query($sql);
		$TMouvementStock = $PDOdb->Get_All();
		$laststock = $stock;
		$lastpmp = $pmp;
		//Pour chacun des mouvements on recalcule le PMP et le stock physique
		foreach($TMouvementStock as $mouvement){
			
			//150
			//if($this->product->id==394) echo 'laststock = '.$stock.'<br>';
			
			//9.33
			//if($this->product->id==394) echo 'lastpmp = '.$pmp.'<br>';
			$price = ($mouvement->price>0 && $mouvement->value>0) ? $mouvement->price : $lastpmp;  
				
			$stock_value = $laststock * $lastpmp;
			
			$laststock -= $mouvement->value;
			
			$last_stock_value = $stock_value - ($mouvement->value * $price);	
			
			$lastpmp = ($laststock != 0) ? $last_stock_value / $laststock : $lastpmp;
			 
			

		}
		
		return array($lastpmp,$laststock);
	}
    
	function load_product() 
	{
		global $db;
		
		if($this->fk_product>0) {
			$this->product = new Product($db);
			$this->product->fetch($this->fk_product);
		}
		
	}
	
}
