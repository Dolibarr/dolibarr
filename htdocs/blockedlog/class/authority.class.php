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
 *	Class to manage certif authority
 */
class BlockedLogAuthority
{

	/**
	 * Id of the log
	 * @var int
	 */
	public $id;

	/**
	 * Unique fingerprint of the blockchain to store
	 * @var string
	 */
	public $signature = '';

	/**
	 * Entire fingerprints blockchain
	 * @var string
	 */
	public $blockchain = '';

	/**
	 * timestamp
	 * @var int
	 */
	public $tms = 0;

	/**
	 *      Constructor
	 *
	 *      @param		DoliDB		$db      Database handler
	 */
    public function __construct($db) {

    	$this->db = $db;

	}

	/**
	 *	Get the blockchain
	 *
	 *	@return     string         			blockchain
	 */
	public function getLocalBlockChain() {

		$block_static = new BlockedLog($this->db);

		$this->signature = $block_static->getSignature();

		$blocks = $block_static->getLog('all', 0, 0, 'rowid', 'ASC') ;

		$this->blockchain = '';

		foreach($blocks as &$b) {
			$this->blockchain.=$b->signature;

		}

		return $this->blockchain;
	}

	/**
	 *	Get hash of the block chain to check
	 *
	 *	@return     string         			hash md5 of blockchain
	 */
	public function getBlockchainHash() {

		return md5($this->signature.$this->blockchain);

	}

	/**
	 *	Get hash of the block chain to check
	 *
	 *	@param      string		$hash		hash md5 of blockchain to test
	 *	@return     boolean
	 */
	public function checkBlockchain($hash) {

		return ($hash === $this->getBlockchainHash() );

	}

	/**
	 *	Add a new block to the chain
	 *
	 *	@param      string		$block		new block to chain
	 */
	public function addBlock($block) {

		$this->blockchain.=$block;

	}

	/**
	 *	hash already exist into chain ?
	 *
	 *	@param      string		$block		new block to chain
	 *	@return     boolean
	 */
	public function checkBlock($block) {

		if(strlen($block)!=64) return false;

		$blocks = str_split($this->blockchain,64);

		if(!in_array($block,$blocks)) {
			return true;
		}
		else{
			return false;
		}
	}


	/**
	 *	Get object from database
	 *
	 *	@param      int			$id		       	Id of object to load
	 *	@param      string		$signature		Signature of object to load
	 *	@return     int         				>0 if OK, <0 if KO, 0 if not found
	 */
	public function fetch($id, $signature='') {

		global $langs;

		dol_syslog(get_class($this)."::fetch id=".$id, LOG_DEBUG);

		if (empty($id) && empty($signature))
		{
			$this->error='BadParameter';
			return -1;
		}

		$langs->load("blockedlog");

		$sql = "SELECT b.rowid, b.signature, b.blockchain, b.tms";
		$sql.= " FROM ".MAIN_DB_PREFIX."blockedlog_authority as b";

		if ($id) $sql.= " WHERE b.rowid = ". $id;
		else if($signature)$sql.= " WHERE b.signature = '". $this->db->escape( $signature ) ."'" ;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id				= $obj->rowid;
				$this->ref				= $obj->rowid;

				$this->signature		= $obj->signature;
				$this->blockchain		= $obj->blockchain;

				$this->tms				= $this->db->jdate($obj->tms);

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
	 *	Create authority in database.
	 *
	 *	@param	User	$user      		Object user that create
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function create($user) {

		global $conf,$langs,$hookmanager;

		$langs->load('blockedlog');

		$error=0;

		dol_syslog(get_class($this).'::create', LOG_DEBUG);

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."blockedlog_authority (";
		$sql.= " signature,";
		$sql.= " blockchain";
		$sql.= ") VALUES (";
		$sql.= "'".$this->db->escape($this->signature)."',";
		$sql.= "'".$this->db->escape($this->blockchain)."'";
		$sql.= ")";

		$res = $this->db->query($sql);
		if ($res)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."blockedlog_authority");

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
	 *	Create authority in database.
	 *
	 *	@param	User	$user      		Object user that create
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function update($user) {

		global $conf,$langs,$hookmanager;

		$langs->load('blockedlog');

		$error=0;

		dol_syslog(get_class($this).'::create', LOG_DEBUG);

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."blockedlog_authority SET ";
		$sql.= " blockchain='".$this->db->escape($this->blockchain)."'";
		$sql.= " WHERE rowid=".$this->id;

		$res = $this->db->query($sql);
		if ($res)
		{
			$this->db->commit();

			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}

	}

	/**
	 *	For cron to sync to authority.
	 *
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function syncSignatureWithAuthority() {
		global $conf, $langs;

		//TODO create cron task on activation

		if(empty($conf->global->BLOCKEDLOG_AUTHORITY_URL) || empty($conf->global->BLOCKEDLOG_USE_REMOTE_AUTHORITY)) {
			$this->error = $langs->trans('NoAuthorityURLDefined');
			return -2;
		}

		require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';

		$block_static = new BlockedLog($this->db);

		$blocks = $block_static->getLog('not_certified', 0, 0, 'rowid', 'ASC');

		$signature=$block_static->getSignature();

		foreach($blocks as &$block) {

			$url = $conf->global->BLOCKEDLOG_AUTHORITY_URL.'/blockedlog/ajax/authority.php?s='.$signature.'&b='.$block->signature;

			$res = file_get_contents($url);
			echo $block->signature.' '.$url. ' '.$res.'<br>';
			if($res === 'blockalreadyadded' || $res === 'blockadded') {

				$block->setCertified();

			}
			else {

				$this->error = $langs->trans('ImpossibleToContactAuthority ',$url);
				return -1;
			}


		}

		return 1;
	}

}