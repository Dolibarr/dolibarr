<?php 

class BlockedLog {
	
	public $signature = '';
	
	public $key_value1 = null;
	
	public $action = '';
	
	public $element = '';
	
	public $fk_object = 0;
	
	public $certified = false;
	
	public $fk_user = 0;
	
	function __construct(&$db) {
		
		$this->db =&$db;
		
	}
	
	public function create($user) {
		
		global $conf,$langs,$hookmanager;
		
		$langs->load('blockedlog');
		
		$error=0;
		
		dol_syslog(get_class($this).'::create', LOG_DEBUG);
		
		// Clean parameters
		$this->signature = $this->getSignatureRecursive();
		
		
		if (is_null($this->key_value1))
		{
			$this->error=$langs->trans("BlockLogNeedKey1Value");
			dol_syslog($this->error, LOG_WARNING);
			return -1;
		}
		
		if(empty($this->element)) {
			$this->error=$langs->trans("BlockLogNeedElement");
			dol_syslog($this->error, LOG_WARNING);
			return -2;
		}
		
		if(empty($this->action)) {
			$this->error=$langs->trans("BlockLogNeedAction");
			dol_syslog($this->error, LOG_WARNING);
			return -3;
		}

		$this->fk_user = $user->id;
		
		$this->db->begin();
		
		//TODO add fk_user;
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."blockedlog (";
		$sql.= "action,";
		$sql.= " key_value1,";
		$sql.= " signature,";
		$sql.= " element,";
		$sql.= " fk_object,";
		$sql.= " certified,";
		$sql.= " entity";
		$sql.= ") VALUES (";
		$sql.= "'".$this->db->escape($this->action)."',";
		$sql.= "".$this->key_value1.",";
		$sql.= "'".$this->db->escape($this->signature)."',";
		$sql.= "'".$this->db->escape($this->element)."',";
		$sql.= "".$this->fk_object.",";
		$sql.= "'".($this->certified ? 1 : 0)."',";
		$sql.= $conf->entity;
		$sql.= ")";
		
		$res = $this->db->query($sql);
		if ($res)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."blockedlog");
			
			if ($id > 0)
			{
				$this->id = $id;
			
			}
			else
			{
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
		
	}
	
	private function crypt($value) {
		
		return md5($value);
		
	}
	
	public function checkSignature() {
		
		$signature = $this->getSignatureRecursive();
		
		return ($signature === $this->signature);
		
	}
	
	private function getSignatureRecursive(){
		
		$signature = $this->crypt( $this->action . $this->getSignature() . $this->key_value1  );
		
		$logs = $this->getLog('payment', 0, 0, 'ASC') ;
		if($logs!==false) {
			foreach($logs as &$b) {
			
				if($this->id>0 && $b->id == $this->id) break; // on arrête sur un enregistrement précis pour recalculer une signature
				
				$b->updateValue(); // on récupère la valeur actuelle en base de l'élément enregistré
				
				$signature = $this->crypt($signature. $this->action . $b->signature . $b->key_value1);
			}
		}
		return $signature;
	
	}
	
	public function getLog($element, $fk_object, $limit = 0, $order = 'DESC') {
		global $conf,$cachedlogs ; 
		
		if(empty($cachedlogs)) $cachedlogs=array();
	
		
		if($element=='payment') {
		
	 		$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog
	         WHERE entity=".$conf->entity." AND  action LIKE '%PAYMENT%'
	         ORDER BY tms ".$order;
			
		}
		else if($element=='payments_not_certified') {
			$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog
	         WHERE entity=".$conf->entity." AND action LIKE '%PAYMENT%' AND certified = 0
	         ORDER BY tms ".$order;
			
		}
		else if($element=='payments_just_certified') {
			$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog
	         WHERE entity=".$conf->entity." AND action LIKE '%PAYMENT%' AND certified = 1
	         ORDER BY tms ".$order;
			
		}
		else{
			$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog
	         WHERE element='".$element."' AND fk_object=".(int)$fk_object."
	         ORDER BY tms ".$order;
			
		}
		
		
		if($limit > 0 )$sql.=' LIMIT '.$limit;
		
		$res = $this->db->query($sql);
		
		if($res) {
		
			$results=array();
			
			while($obj = $this->db->fetch_object($res)) {
				
				if(!isset($cachedlogs[$obj->rowid])) {
					$b=new BlockedLog($this->db);
					$b->fetch($obj->rowid);
					
					$cachedlogs[$obj->rowid] = $b;
				}
				
				$results[] = $cachedlogs[$obj->rowid];
				
			}
			
			return $results;
		}
		else{
			return false;
		}
	}
	
	private function updateValue() {
		
		if($this->action === 'PAYMENT_CUSTOMER_CREATE'
		|| $this->action === 'PAYMENT_ADD_TO_BANK') {
			$sql="SELECT amount FROM ".MAIN_DB_PREFIX."paiement WHERE rowid=".$this->fk_object;
					
			$res = $this->db->query($sql);
			
			if($res && $obj = $db->fetch_object($res)) {
				$this->key_value1 = (double)$obj->amount;
			}
		}
				
	}
	
	
	public function getSignature() {
		global $db,$conf,$mysoc;
		
		if(empty($conf->global->BLOCKEDLOG_ENTITY_FINGERPRINT)) { // creation of a unique fingerprint
			
			require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
			
			$fingerprint = $this->crypt(print_r($mysoc,true).time().rand(0,1000));
			
			dolibarr_set_const($db, 'BLOCKEDLOG_ENTITY_FINGERPRINT', $fingerprint, '',0,'Numeric Unique Fingerprint', $conf->entity);
			
			$conf->global->BLOCKEDLOG_ENTITY_FINGERPRINT= $fingerprint;
		}
		
		return $conf->global->BLOCKEDLOG_ENTITY_FINGERPRINT;
	}
	
}

