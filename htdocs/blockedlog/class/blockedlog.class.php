<?php
/* Copyright (C) 2017 ATM Consulting <contact@atm-consulting.fr>
 * Copyright (C) 2017 Laurent Destailleur <eldy@destailleur.fr>
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
 *
 * See https://medium.com/@lhartikk/a-blockchain-in-200-lines-of-code-963cc1cc0e54
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
	public function getObjectLink()
	{
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
		if($this->element === 'invoice_supplier') {
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

			$object = new FactureFournisseur($this->db);
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
		else if($this->element === 'payment_supplier') {
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';

			$object = new PaiementFourn($this->db);
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
	public function getUser()
	{
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
	 *      Populate properties of log from object data
	 *
	 *      @param		Object		$object      object to store
	 *      @param		string		$action      action
	 *      @param		string		$amounts     amounts
	 */
	public function setObjectData(&$object, $action, $amounts)
	{
		global $user, $mysoc;

		// Generic fields

		// action
		$this->action = $action;
		// amount
		$this->amounts= $amounts;
		// date
		if ($object->element == 'payment' || $object->element == 'payment_supplier')
		{
			$this->date_object = $object->datepaye;
		}
		elseif ($object->element=='payment_salary')
		{
			$this->date_object = $object->datev;
		}
		else {
			$this->date_object = $object->date;
		}
		// ref
		$this->ref_object = $object->ref;
		// type of object
		$this->element = $object->element;
		// id of object
		$this->fk_object = $object->id;

		$this->object_data=new stdClass();

		// Add thirdparty info

		if (empty($object->thirdparty) && method_exists('fetch_thirdparty')) $object->fetch_thirdparty();

		if (! empty($object->thirdparty))
		{
			$this->object_data->thirdparty = new stdClass();

			foreach($object->thirdparty as $key=>$value)
			{
				if (in_array($key, array('fields'))) continue;	// Discard some properties
				if (! in_array($key, array(
				'name','name_alias','ref_ext','address','zip','town','state_code','country_code','idprof1','idprof2','idprof3','idprof4','idprof5','idprof6','phone','fax','email','barcode',
				'tva_intra', 'localtax1_assuj', 'localtax1_value', 'localtax2_assuj', 'localtax2_value', 'managers', 'capital', 'typent_code', 'forme_juridique_code', 'code_client', 'code_fournisseur'
				))) continue;								// Discard if not into a dedicated list
				if (!is_object($value)) $this->object_data->thirdparty->{$key} = $value;
			}
		}

		// Add company info
		if (! empty($mysoc))
		{
			$this->object_data->mycompany = new stdClass();

			foreach($mysoc as $key=>$value)
			{
				if (in_array($key, array('fields'))) continue;	// Discard some properties
				if (! in_array($key, array(
				'name','name_alias','ref_ext','address','zip','town','state_code','country_code','idprof1','idprof2','idprof3','idprof4','idprof5','idprof6','phone','fax','email','barcode',
				'tva_intra', 'localtax1_assuj', 'localtax1_value', 'localtax2_assuj', 'localtax2_value', 'managers', 'capital', 'typent_code', 'forme_juridique_code', 'code_client', 'code_fournisseur'
				))) continue;									// Discard if not into a dedicated list
				if (!is_object($value)) $this->object_data->mycompany->{$key} = $value;
			}
		}

		// Add user info

		$this->fk_user = $user->id;
		$this->user_fullname = $user->getFullName($langs);

		// Field specific to object

		if ($this->element == 'facture')
		{
			$this->object_data->total_ht 	= (double) $object->total_ht;
			$this->object_data->total_tva	= (double) $object->total_tva;
			$this->object_data->total_ttc	= (double) $object->total_ttc;
			$this->object_data->total_localtax1 = (double) $object->total_localtax1;
			$this->object_data->total_localtax2 = (double) $object->total_localtax2;

			$this->object_data->revenue_stamp = (double) $object->revenue_stamp;
			$this->object_data->date_pointoftax = (double) $object->date_pointoftax;
			$this->object_data->note_public	= (double) $object->note_public;
		}
		if($this->element == 'invoice_supplier') {
			if(empty($object->thirdparty))$object->fetch_thirdparty();
			$this->object_data->thirdparty = new stdClass();

			foreach($object->thirdparty as $key=>$value) {
				if(!is_object($value)) $this->object_data->thirdparty->{$key} = $value;
			}

			$this->object_data->total_ht 	= (double) $object->total_ht;
			$this->object_data->total_tva	= (double) $object->total_tva;
			$this->object_data->total_ttc	= (double) $object->total_ttc;
			$this->object_data->total_localtax1 = (double) $object->total_localtax1;
			$this->object_data->total_localtax2 = (double) $object->total_localtax2;

			$this->object_data->revenue_stamp = (double) $object->revenue_stamp;
			$this->object_data->date_pointoftax = (double) $object->date_pointoftax;
			$this->object_data->note_public	= (double) $object->note_public;
		}
		elseif ($this->element == 'payment'|| $object->element == 'payment_supplier')
		{
			$this->object_data->amounts = $object->amounts;
		}
		elseif($this->element == 'payment_salary')
		{
			$this->object_data->amounts = array($object->amount);
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

		$sql = "SELECT b.rowid, b.date_creation, b.signature, b.signature_line, b.amounts, b.action, b.element, b.fk_object, b.certified, b.tms, b.fk_user, b.user_fullname, b.date_object, b.ref_object, b.object_data";
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

				$this->date_creation    = $this->db->jdate($obj->date_creation);
				$this->tms				= $this->db->jdate($obj->tms);

				$this->amounts			= (double) $obj->amounts;
				$this->action			= $obj->action;
				$this->element			= $obj->element;

				$this->fk_object		= $obj->fk_object;
				$this->date_object		= $this->db->jdate($obj->date_object);
				$this->ref_object		= $obj->ref_object;

				$this->fk_user 			= $obj->fk_user;
				$this->user_fullname	= $obj->user_fullname;

				$this->object_data		= unserialize($obj->object_data);

				$this->signature		= $obj->signature;
				$this->signature_line	= $obj->signature_line;
				$this->certified		= ($obj->certified == 1);

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

		// Clean data
		$this->amounts=(double) $this->amounts;

		dol_syslog(get_class($this).'::create action='.$this->action.' fk_user='.$this->fk_user.' user_fullname='.$this->user_fullname, LOG_DEBUG);

		// Check parameters/properties
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

		if (empty($this->action) || empty($this->fk_user) || empty($this->user_fullname)) {
			$this->error=$langs->trans("BadParameterWhenCallingCreateOfBlockedLog");
			dol_syslog($this->error, LOG_WARNING);
			return -3;
		}

		$this->date_creation = dol_now();

		$this->db->begin();

		$previoushash = $this->getPreviousHash(1);	// This get last record and lock database until insert is done

		$keyforsignature = $this->buildKeyForSignature();

		$this->signature_line = dol_hash($keyforsignature, '5');		// Not really usefull
		$this->signature = dol_hash($previoushash . $keyforsignature, '5');
		//var_dump($keyforsignature);var_dump($previoushash);var_dump($this->signature_line);var_dump($this->signature);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."blockedlog (";
		$sql.= " date_creation,";
		$sql.= " action,";
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
		$sql.= " user_fullname,";
		$sql.= " entity";
		$sql.= ") VALUES (";
		$sql.= "'".$this->db->idate($this->date_creation)."',";
		$sql.= "'".$this->db->escape($this->action)."',";
		$sql.= $this->amounts.",";
		$sql.= "'".$this->db->escape($this->signature)."',";
		$sql.= "'".$this->db->escape($this->signature_line)."',";
		$sql.= "'".$this->db->escape($this->element)."',";
		$sql.= $this->fk_object.",";
		$sql.= "'".$this->db->idate($this->date_object)."',";
		$sql.= "'".$this->db->escape($this->ref_object)."',";
		$sql.= "'".$this->db->escape(serialize($this->object_data))."',";
		$sql.= "0,";
		$sql.= $this->fk_user.",";
		$sql.= "'".$this->db->escape($this->user_fullname)."',";
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

		// The commit will release the lock so we can insert nex record
	}

	/**
	 *	Check if current signature still correct compare to the chain
	 *
	 *	@return	boolean
	 */
	public function checkSignature()
	{

		//$oldblockedlog = new BlockedLog($this->db);
		//$previousrecord = $oldblockedlog->fetch($this->id - 1);
		$previoushash = $this->getPreviousHash(0, $this->id);

		// Recalculate hash
		$keyforsignature = $this->buildKeyForSignature();
		$signature_line = dol_hash($keyforsignature, '5');		// Not really usefull
		$signature = dol_hash($previoushash . $keyforsignature, '5');
		//var_dump($previoushash); var_dump($keyforsignature); var_dump($signature_line); var_dump($signature);

		$res = ($signature === $this->signature);

		if(!$res) {
			$this->error++;
		}

		return $res;
	}

	/**
	 * Return a string for signature
	 *
	 * @return string		Key for signature
	 */
	private function buildKeyForSignature()
	{
		//print_r($this->object_data);
		return $this->date_creation.'|'.$this->action.'|'.$this->amounts.'|'.$this->ref_object.'|'.$this->date_object.'|'.$this->user_fullname.'|'.print_r($this->object_data, true);
	}


	/**
	 *	Get previous signature/hash in chain
	 *
	 *	@param int	$withlock		1=With a lock
	 *	@param int	$beforeid		Before id
	 *  @return	string				Hash of last record
	 */
	 private function getPreviousHash($withlock=0, $beforeid=0)
	 {
		global $conf;

		$previoussignature='';

	 	$sql="SELECT rowid, signature FROM ".MAIN_DB_PREFIX."blockedlog WHERE entity=".$conf->entity;
	 	if ($beforeid) $sql.= " AND rowid < ".(int) $beforeid;
	 	$sql.=" ORDER BY rowid DESC LIMIT 1";
	 	$sql.=($withlock ? " FOR UPDATE ": "");

	 	$resql = $this->db->query($sql);
	 	if ($resql) {
	 		$obj = $this->db->fetch_object($resql);
	 		if ($obj)
	 		{
	 			$previoussignature = $obj->signature;
	 		}
	 	}
	 	else
	 	{
	 		dol_print_error($this->db);
	 		exit;
	 	}

	 	if (empty($previoussignature))
	 	{
			// First signature line (line 0)
	 		$previoussignature = $this->getSignature();
	 	}

	 	return $previoussignature;
	}

	/**
	 *	Return array of log objects (with criterias)
	 *
	 *	@param	string 	$element      	element to search
	 *	@param	int 	$fk_object		id of object to search
	 *	@param	int 	$limit      	max number of element, 0 for all
	 *	@param	string 	$order      	sort of query
	 *	@return	array					array of object log
	 */
	public function getLog($element, $fk_object, $limit = 0, $order = -1)
	{
		global $conf, $cachedlogs;

		/* $cachedlogs allow fastest search */
		if (empty($cachedlogs)) $cachedlogs=array();

		if ($element=='all') {

	 		$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog
	         WHERE entity=".$conf->entity;

		}
		else if ($element=='not_certified') {
			$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog
	         WHERE entity=".$conf->entity." AND certified = 0";

		}
		else if ($element=='just_certified') {
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

			while ($obj = $this->db->fetch_object($res)) {

				if (!isset($cachedlogs[$obj->rowid])) {
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
	 *	Return the signature (hash) of the "genesis-block" (Block 0)
	 *
	 *	@return	string					Signature of genesis-block for current conf->entity
	 */
	public function getSignature()
	{
		global $db,$conf,$mysoc;

		if (empty($conf->global->BLOCKEDLOG_ENTITY_FINGERPRINT)) { // creation of a unique fingerprint

			require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

			$fingerprint = dol_hash(print_r($mysoc,true).getRandomPassword(1), '5');

			dolibarr_set_const($db, 'BLOCKEDLOG_ENTITY_FINGERPRINT', $fingerprint, 'chaine',0,'Numeric Unique Fingerprint', $conf->entity);

			$conf->global->BLOCKEDLOG_ENTITY_FINGERPRINT=$fingerprint;
		}

		return $conf->global->BLOCKEDLOG_ENTITY_FINGERPRINT;
	}

}

