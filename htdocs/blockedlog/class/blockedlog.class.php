<?php
/* Copyright (C) 2017 ATM Consulting <contact@atm-consulting.fr>
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
 *	Class to manage Blocked Log
 */

class BlockedLog
{
	/**
	 * Id of the log
	 * @var int
	 */
	public $id;

	public $error = '';
	public $errors = array();

	/**
	 * Unique fingerprint of the log
	 * @var string
	 */
	public $signature = '';

	/**
	 * Unique fingerprint of the line log content
	 * @var string
	 */
	public $signature_line = '';

	public $amounts = null;

	/**
	 * trigger action
	 * @var string
	 */
	public $action = '';

	/**
	 * Object element
	 * @var string
	 */
	public $element = '';

	/**
	 * Object id
	 * @var int
	 */
	public $fk_object = 0;

	/**
	 * Log certified by remote authority or not
	 * @var boolean
	 */
	public $certified = false;

	/**
	 * Author
	 * @var int
	 */
	public $fk_user = 0;

	public $date_object = 0;

	public $ref_object = '';

	public $object_data = null;



	/**
	 *      Constructor
	 *
	 *      @param		DoliDB		$db      Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;

	}

	/**
	 *      try to retrieve logged object link
	 */
	public function getObjectLink() {
		global $langs;

		if($this->element === 'facture') {
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

			$object = new Facture($this->db);
			if($object->fetch($this->fk_object)>0) {
				return $object->getNomUrl(1);
			}
			else{
				$this->error++;
			}
		}
		else if($this->element === 'payment') {
			require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

			$object = new Paiement($this->db);
			if($object->fetch($this->fk_object)>0) {
				return $object->getNomUrl(1);
			}
			else{
				$this->error++;
			}
		}

		return $langs->trans('ImpossibleToReloadObject', $this->element, $this->fk_object);

	}

	/**
	 *      try to retrieve user author
	 */
	public function getUser() {
		global $langs, $cachedUser;

		if(empty($cachedUser))$cachedUser=array();

		if(empty($cachedUser[$this->fk_user])) {
			$u=new User($this->db);
			if($u->fetch($this->fk_user)>0) {
				$cachedUser[$this->fk_user] = $u;
			}
		}

		if(!empty($cachedUser[$this->fk_user])) {
			return $cachedUser[$this->fk_user]->getNomUrl(1);
		}

		return $langs->trans('ImpossibleToRetrieveUser', $this->fk_user);
	}

	/**
	 *      populate log by object
	 *
	 *      @param		payment|facture		$object      object to store
	 */
	public function setObjectData(&$object) {

		if($object->element=='payment') {
			$this->date_object = $object->datepaye;
		}
		else{
			$this->date_object = $object->date;
		}

		$this->ref_object = $object->ref;
		$this->element = $object->element;
		$this->fk_object = $object->id;

		$this->object_data=new stdClass();

		if($this->element === 'facture') {
			if(empty($object->thirdparty))$object->fetch_thirdparty();
			$this->object_data->thirdparty = new stdClass();

			foreach($object->thirdparty as $key=>$value) {
				if(!is_object($value)) $this->object_data->thirdparty->{$key} = $value;
			}

			$this->object_data->total_ht 	= (double) $object->total_ht;
			$this->object_data->total_tva	= (double) $object->total_tva;
			$this->object_data->total_ttc	= (double) $object->total_ttc;
			$this->object_data->total_localtax1= (double) $object->total_localtax1;
			$this->object_data->total_localtax2= (double) $object->total_localtax2;
			$this->object_data->note_public	= (double) $object->note_public;
			$this->object_data->note_private= (double) $object->note_private;

		}
		elseif($this->element==='payment'){

			$this->object_data->amounts = $object->amounts;

		}
	}

	/**
	 *	Get object from database
	 *
	 *	@param      int		$id       	Id of object to load
	 *	@return     int         			>0 if OK, <0 if KO, 0 if not found
	 */
	public function fetch($id) {

		global $langs;

		dol_syslog(get_class($this)."::fetch id=".$id, LOG_DEBUG);

		if (empty($id))
		{
			$this->error='BadParameter';
			return -1;
		}

		$langs->load("blockedlog");

		$sql = "SELECT b.rowid, b.signature, b.amounts, b.action, b.element, b.fk_object, b.certified, b.tms, b.fk_user, b.date_object, b.ref_object, b.object_data";
		$sql.= " FROM ".MAIN_DB_PREFIX."blockedlog as b";
		if ($id) $sql.= " WHERE b.rowid = ". $id;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id				= $obj->rowid;
				$this->ref				= $obj->rowid;

				$this->signature		= $obj->signature;
				$this->amounts			= (double) $obj->amounts;
				$this->action			= $obj->action;
				$this->element			= $obj->element;

				$this->fk_object		= $obj->fk_object;
				$this->date_object		= $this->db->jdate($obj->date_object);
				$this->ref_object		= $obj->ref_object;

				$this->certified		= ($obj->certified == 1);

				$this->fk_user 			= $obj->fk_user;

				$this->tms				= $this->db->jdate($obj->tms);

				$this->object_data		= unserialize($obj->object_data);

				return 1;
			}
			else
			{
				$this->error=$langs->trans("RecordNotFound");
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
	 *	Set block certified by authority
	 *
	 *	@return	boolean
	 */
	public function setCertified() {

		$res = $this->db->query("UPDATE ".MAIN_DB_PREFIX."blockedlog SET certified=1 WHERE rowid=".$this->id);
		if($res===false) return false;

		return true;


	}

	/**
	 *	Create blocked log in database.
	 *
	 *	@param	User	$user      		Object user that create
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function create($user) {

		global $conf,$langs,$hookmanager;

		$langs->load('blockedlog');

		$error=0;

		dol_syslog(get_class($this).'::create', LOG_DEBUG);

		$this->getSignatureRecursive();


		if (is_null($this->amounts))
		{
			$this->error=$langs->trans("BlockLogNeedAmountsValue");
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

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."blockedlog (";
		$sql.= "action,";
		$sql.= " amounts,";
		$sql.= " signature,";
		$sql.= " signature_line,";
		$sql.= " element,";
		$sql.= " fk_object,";
		$sql.= " date_object,";
		$sql.= " ref_object,";
		$sql.= " object_data,";
		$sql.= " certified,";
		$sql.= " fk_user,";
		$sql.= " entity";
		$sql.= ") VALUES (";
		$sql.= "'".$this->db->escape($this->action)."',";
		$sql.= "".$this->amounts.",";
		$sql.= "'".$this->db->escape($this->signature)."',";
		$sql.= "'".$this->db->escape($this->signature_line)."',";
		$sql.= "'".$this->db->escape($this->element)."',";
		$sql.= "".$this->fk_object.",";
		$sql.= "'".$this->db->idate($this->date_object)."',";
		$sql.= "'".$this->db->escape($this->ref_object)."',";
		$sql.= "'".$this->db->escape(serialize($this->object_data))."',";
		$sql.= "0,";
		$sql.= "".$user->id.",";
		$sql.= $conf->entity;
		$sql.= ")";

		$res = $this->db->query($sql);
		if ($res)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."blockedlog");

			if ($id > 0)
			{
				$this->id = $id;

				$this->db->commit();

				return $this->id;
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

	/**
	 *	return crypted value.
	 *
	 *	@param	string $value      		string to crypt
	 *	@return	string					crypted string
	 */
	private function crypt($value) {

		return hash('sha256',$value);

	}

	/**
	 *	check if current signature still correct compare to the chain
	 *
	 *	@return	boolean
	 */
	public function checkSignature() {

		$signature_to_test = $this->signature;

		$this->getSignatureRecursive();

		$res = ($signature_to_test === $this->signature);

		if(!$res) {
			$this->error++;
		}

		return $res;
	}

	/**
	 *	set current signatures
	 */
	 private function getSignatureRecursive(){

		$this->signature_line = $this->crypt( $this->action . $this->getSignature() . $this->amounts . print_r($this->object_data, true) );
		/*if($this->signature=='d6320580a02c1ab67fcc0a6d49d453c7d96dda0148901736f7f55725bfe1b900' || $this->signature=='ea65d435ff12ca929936a406aa9d707d99fb334c127878d256b602a5541bbbc9') {
			var_dump($this->signature_line,$this->action ,$this->getSignature() , $this->amounts , $this->object_data);
		}*/
		$this->signature = $this->signature_line;

		$logs = $this->getLog('all', 0, 0, 1) ;
		if($logs!==false) {
			foreach($logs as &$b) {

				if($this->id>0 && $b->id == $this->id) break; // on arrête sur un enregistrement précis pour recalculer une signature

				$b->getCurrentValue(); // on récupère la valeur actuelle en base de l'élément enregistré

				$this->signature = $this->crypt($this->signature. $this->action . $b->signature . $b->amounts);
			}
		}

	}

	/**
	 *	return log object for a element.
	 *
	 *	@param	string 	$element      	element to search
	 *	@param	int 	$fk_object		id of object to search
	 *	@param	int 	$limit      	max number of element, 0 for all
	 *	@param	string 	$order      	sort of query
	 *	@return	array					array of object log
	 */
	public function getLog($element, $fk_object, $limit = 0, $order = -1) {
		global $conf,$cachedlogs ;

		/* $cachedlogs allow fastest search */
		if(empty($cachedlogs)) $cachedlogs=array();


		if($element=='all') {

	 		$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog
	         WHERE entity=".$conf->entity;

		}
		else if($element=='not_certified') {
			$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog
	         WHERE entity=".$conf->entity." AND certified = 0";

		}
		else if($element=='just_certified') {
			$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog
	         WHERE entity=".$conf->entity." AND certified = 1";

		}
		else{
			$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog
	         WHERE element='".$element."' AND fk_object=".(int) $fk_object;

		}

		$sql.=($order<0 ? ' ORDER BY rowid DESC ' : ' ORDER BY rowid ASC ');

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

	/**
	 *	set amounts of log from current element value in order to compare signature.
	 */
	private function getCurrentValue() {

		if($this->element === 'payment') {
			$sql="SELECT amount FROM ".MAIN_DB_PREFIX."paiement WHERE rowid=".$this->fk_object;

			$res = $this->db->query($sql);

			if($res && $obj = $this->db->fetch_object($res)) {
				$this->amounts = (double) $obj->amount;
			}
		}
		elseif($this->element === 'facture') {
			$sql="SELECT total_ttc FROM ".MAIN_DB_PREFIX."facture WHERE rowid=".$this->fk_object;

			$res = $this->db->query($sql);
			if($res && $obj = $this->db->fetch_object($res)) {
				$this->amounts = (double) $obj->total_ttc;
			}
		}

	}

	/**
	 *	Return and set the entity signature included into line signature
	 *
	 *	@return	string					current entity signature
	 */
	public function getSignature() {
		global $db,$conf,$mysoc;

		if (empty($conf->global->BLOCKEDLOG_ENTITY_FINGERPRINT)) { // creation of a unique fingerprint

			require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

			$fingerprint = $this->crypt(print_r($mysoc,true).time().rand(0,1000));

			dolibarr_set_const($db, 'BLOCKEDLOG_ENTITY_FINGERPRINT', $fingerprint, 'chaine',0,'Numeric Unique Fingerprint', $conf->entity);

			$conf->global->BLOCKEDLOG_ENTITY_FINGERPRINT=$fingerprint;
		}

		return $conf->global->BLOCKEDLOG_ENTITY_FINGERPRINT;
	}

}

