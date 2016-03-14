<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015-*2016 Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 * \file accountancy/bookkeeping.class.php
 * \ingroup accountancy
 * \brief This file is an example for a CRUD class file (Create/Read/Update/Delete)
 * Put some comments here
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
// require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
// require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class Accountingbookkeeping
 *
 * Put here description of your class
 */
class BookKeeping extends CommonObject
{
	/**
	 *
	 * @var string Error code (or message)
	 * @deprecated
	 *
	 * @see Accountingbookkeeping::errors
	 */
	public $error;
	/**
	 *
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array ();
	/**
	 *
	 * @var string Id to identify managed objects
	 */
	public $element = 'accountingbookkeeping';
	/**
	 *
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'accounting_bookkeeping';
	
	/**
	 *
	 * @var BookKeepingLine[] Lines
	 */
	public $lines = array ();
	
	/**
	 *
	 * @var int ID
	 */
	public $id;
	/**
	 */
	public $doc_date = '';
	public $doc_type;
	public $doc_ref;
	public $fk_doc;
	public $fk_docdet;
	public $code_tiers;
	public $numero_compte;
	public $label_compte;
	public $debit;
	public $credit;
	public $montant;
	public $sens;
	public $fk_user_author;
	public $import_key;
	public $code_journal;
	public $piece_num;
	
	/**
	 */
	
	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db) {
		$this->db = $db;
		return 1;
	}
	
	/**
	 * Create object into database
	 *
	 * @param User $user User that creates
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 *       
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false) {
		dol_syslog(__METHOD__, LOG_DEBUG);
		
		$error = 0;
		
		// Clean parameters
		
		if (isset($this->doc_type)) {
			$this->doc_type = trim($this->doc_type);
		}
		if (isset($this->doc_ref)) {
			$this->doc_ref = trim($this->doc_ref);
		}
		if (isset($this->fk_doc)) {
			$this->fk_doc = trim($this->fk_doc);
		}
		if (isset($this->fk_docdet)) {
			$this->fk_docdet = trim($this->fk_docdet);
		}
		if (isset($this->code_tiers)) {
			$this->code_tiers = trim($this->code_tiers);
		}
		if (isset($this->numero_compte)) {
			$this->numero_compte = trim($this->numero_compte);
		}
		if (isset($this->label_compte)) {
			$this->label_compte = trim($this->label_compte);
		}
		if (isset($this->debit)) {
			$this->debit = trim($this->debit);
		}
		if (isset($this->credit)) {
			$this->credit = trim($this->credit);
		}
		if (isset($this->montant)) {
			$this->montant = trim($this->montant);
		}
		if (isset($this->sens)) {
			$this->sens = trim($this->sens);
		}
		if (isset($this->fk_user_author)) {
			$this->fk_user_author = trim($this->fk_user_author);
		}
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}
		if (isset($this->code_journal)) {
			$this->code_journal = trim($this->code_journal);
		}
		if (isset($this->piece_num)) {
			$this->piece_num = trim($this->piece_num);
		}
		
		$this->db->begin();
		
		$this->piece_num = 0;
		
		// first check if line not yet in bookkeeping
		$sql = "SELECT count(*)";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE doc_type = '" . $this->doc_type . "'";
		$sql .= " AND fk_docdet = " . $this->fk_docdet;
		$sql .= " AND numero_compte = '" . $this->numero_compte . "'";
		
		dol_syslog(get_class($this) . ":: create sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		
		if ($resql) {
			$row = $this->db->fetch_array($resql);
			if ($row[0] == 0) {
				
				// Determine piece_num
				$sqlnum = "SELECT piece_num";
				$sqlnum .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
				$sqlnum .= " WHERE doc_type = '" . $this->doc_type . "'";
				$sqlnum .= " AND fk_docdet = '" . $this->fk_docdet . "'";
				$sqlnum .= " AND doc_ref = '" . $this->doc_ref . "'";
				
				dol_syslog(get_class($this) . ":: create sqlnum=" . $sqlnum, LOG_DEBUG);
				$resqlnum = $this->db->query($sqlnum);
				if ($resqlnum) {
					$objnum = $this->db->fetch_object($resqlnum);
					$this->piece_num = $objnum->piece_num;
				}
				dol_syslog(get_class($this) . ":: create this->piece_num=" . $this->piece_num, LOG_DEBUG);
				if (empty($this->piece_num)) {
					$sqlnum = "SELECT MAX(piece_num)+1 as maxpiecenum";
					$sqlnum .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
					
					dol_syslog(get_class($this) . ":: create sqlnum=" . $sqlnum, LOG_DEBUG);
					$resqlnum = $this->db->query($sqlnum);
					if ($resqlnum) {
						$objnum = $this->db->fetch_object($resqlnum);
						$this->piece_num = $objnum->maxpiecenum;
					}
				}
				dol_syslog(get_class($this) . ":: create this->piece_num=" . $this->piece_num, LOG_DEBUG);
				if (empty($this->piece_num)) {
					$this->piece_num = 1;
				}
				
				$now = dol_now();
				if (empty($this->date_create)) {
					$this->date_create = $now;
				}
				
				$sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
				
				$sql .= "doc_date";
				$sql .= ", doc_type";
				$sql .= ", doc_ref";
				$sql .= ", fk_doc";
				$sql .= ", fk_docdet";
				$sql .= ", code_tiers";
				$sql .= ", numero_compte";
				$sql .= ", label_compte";
				$sql .= ", debit";
				$sql .= ", credit";
				$sql .= ", montant";
				$sql .= ", sens";
				$sql .= ", fk_user_author";
				$sql .= ", import_key";
				$sql .= ", code_journal";
				$sql .= ", piece_num";
				
				$sql .= ") VALUES (";
				
				$sql .= "'" . $this->doc_date . "'";
				$sql .= ",'" . $this->doc_type . "'";
				$sql .= ",'" . $this->doc_ref . "'";
				$sql .= "," . $this->fk_doc;
				$sql .= "," . $this->fk_docdet;
				$sql .= ",'" . $this->code_tiers . "'";
				$sql .= ",'" . $this->numero_compte . "'";
				$sql .= ",'" . $this->db->escape($this->label_compte) . "'";
				$sql .= "," . $this->debit;
				$sql .= "," . $this->credit;
				$sql .= "," . $this->montant;
				$sql .= ",'" . $this->sens . "'";
				$sql .= ",'" . $this->fk_user_author . "'";
				$sql .= ",'" . $this->date_create . "'";
				$sql .= ",'" . $this->code_journal . "'";
				$sql .= "," . $this->piece_num;
				
				$sql .= ")";
				
				dol_syslog(get_class($this) . ":: create sql=" . $sql, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
					
					if ($id > 0) {
						$this->id = $id;
						$result = 0;
					} else {
						$result = - 2;
						$error ++;
						$this->errors[] = 'Error Create Error ' . $result . ' lecture ID';
						dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
					}
				} else {
					$result = - 1;
					$error ++;
					$this->errors[] = 'Error ' . $this->db->lasterror();
					dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
				}
			} else {
				$result = - 3;
				$error ++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
			}
		} else {
			$result = - 5;
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}
		
		if (! $error) {
			
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action to call a trigger.
				
				// // Call triggers
				// $result=$this->call_trigger('MYOBJECT_CREATE',$user);
				// if ($result < 0) $error++;
				// // End call triggers
			}
		}
		
		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			
			return - 1 * $error;
		} else {
			$this->db->commit();
			
			return $result;
		}
	}
	
	/**
	 * Create object into database
	 *
	 * @param User $user User that creates
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 *       
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function createStd(User $user, $notrigger = false) {
		dol_syslog(__METHOD__, LOG_DEBUG);
		
		$error = 0;
		
		// Clean parameters
		
		if (isset($this->doc_type)) {
			$this->doc_type = trim($this->doc_type);
		}
		if (isset($this->doc_ref)) {
			$this->doc_ref = trim($this->doc_ref);
		}
		if (isset($this->fk_doc)) {
			$this->fk_doc = trim($this->fk_doc);
		}
		if (isset($this->fk_docdet)) {
			$this->fk_docdet = trim($this->fk_docdet);
		}
		if (isset($this->code_tiers)) {
			$this->code_tiers = trim($this->code_tiers);
		}
		if (isset($this->numero_compte)) {
			$this->numero_compte = trim($this->numero_compte);
		}
		if (isset($this->label_compte)) {
			$this->label_compte = trim($this->label_compte);
		}
		if (isset($this->debit)) {
			$this->debit = trim($this->debit);
		}
		if (isset($this->credit)) {
			$this->credit = trim($this->credit);
		}
		if (isset($this->montant)) {
			$this->montant = trim($this->montant);
		}
		if (isset($this->sens)) {
			$this->sens = trim($this->sens);
		}
		if (isset($this->fk_user_author)) {
			$this->fk_user_author = trim($this->fk_user_author);
		}
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}
		if (isset($this->code_journal)) {
			$this->code_journal = trim($this->code_journal);
		}
		if (isset($this->piece_num)) {
			$this->piece_num = trim($this->piece_num);
		}
		
		// Check parameters
		// Put here code to add control on parameters values
		
		// Insert request
		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $this->table_element . '(';
		
		$sql .= 'doc_date,';
		$sql .= 'doc_type,';
		$sql .= 'doc_ref,';
		$sql .= 'fk_doc,';
		$sql .= 'fk_docdet,';
		$sql .= 'code_tiers,';
		$sql .= 'numero_compte,';
		$sql .= 'label_compte,';
		$sql .= 'debit,';
		$sql .= 'credit,';
		$sql .= 'montant,';
		$sql .= 'sens,';
		$sql .= 'fk_user_author,';
		$sql .= 'import_key,';
		$sql .= 'code_journal,';
		$sql .= 'piece_num';
		
		$sql .= ') VALUES (';
		
		$sql .= ' ' . (! isset($this->doc_date) || dol_strlen($this->doc_date) == 0 ? 'NULL' : "'" . $this->db->idate($this->doc_date) . "'") . ',';
		$sql .= ' ' . (! isset($this->doc_type) ? 'NULL' : "'" . $this->db->escape($this->doc_type) . "'") . ',';
		$sql .= ' ' . (! isset($this->doc_ref) ? 'NULL' : "'" . $this->db->escape($this->doc_ref) . "'") . ',';
		$sql .= ' ' . (empty($this->fk_doc) ? '0' : $this->fk_doc) . ',';
		$sql .= ' ' . (empty($this->fk_docdet) ? '0' : $this->fk_docdet) . ',';
		$sql .= ' ' . (! isset($this->code_tiers) ? 'NULL' : "'" . $this->db->escape($this->code_tiers) . "'") . ',';
		$sql .= ' ' . (! isset($this->numero_compte) ? 'NULL' : "'" . $this->db->escape($this->numero_compte) . "'") . ',';
		$sql .= ' ' . (! isset($this->label_compte) ? 'NULL' : "'" . $this->db->escape($this->label_compte) . "'") . ',';
		$sql .= ' ' . (! isset($this->debit) ? 'NULL' : "'" . $this->debit . "'") . ',';
		$sql .= ' ' . (! isset($this->credit) ? 'NULL' : "'" . $this->credit . "'") . ',';
		$sql .= ' ' . (! isset($this->montant) ? 'NULL' : "'" . $this->montant . "'") . ',';
		$sql .= ' ' . (! isset($this->sens) ? 'NULL' : "'" . $this->db->escape($this->sens) . "'") . ',';
		$sql .= ' ' . $user->id . ',';
		$sql .= ' ' . (! isset($this->import_key) ? 'NULL' : "'" . $this->db->escape($this->import_key) . "'") . ',';
		$sql .= ' ' . (! isset($this->code_journal) ? 'NULL' : "'" . $this->db->escape($this->code_journal) . "'") . ',';
		$sql .= ' ' . (! isset($this->piece_num) ? 'NULL' : $this->piece_num);
		
		$sql .= ')';
		
		$this->db->begin();
		
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}
		
		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
			
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action to call a trigger.
				
				// // Call triggers
				// $result=$this->call_trigger('MYOBJECT_CREATE',$user);
				// if ($result < 0) $error++;
				// // End call triggers
			}
		}
		
		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			
			return - 1 * $error;
		} else {
			$this->db->commit();
			
			return $this->id;
		}
	}
	
	/**
	 * Load object in memory from the database
	 *
	 * @param int $id Id object
	 * @param string $ref Ref
	 *       
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null) {
		dol_syslog(__METHOD__, LOG_DEBUG);
		
		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		
		$sql .= " t.doc_date,";
		$sql .= " t.doc_type,";
		$sql .= " t.doc_ref,";
		$sql .= " t.fk_doc,";
		$sql .= " t.fk_docdet,";
		$sql .= " t.code_tiers,";
		$sql .= " t.numero_compte,";
		$sql .= " t.label_compte,";
		$sql .= " t.debit,";
		$sql .= " t.credit,";
		$sql .= " t.montant,";
		$sql .= " t.sens,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.import_key,";
		$sql .= " t.code_journal,";
		$sql .= " t.piece_num";
		
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if (null !== $ref) {
			$sql .= ' WHERE t.ref = ' . '\'' . $ref . '\'';
		} else {
			$sql .= ' WHERE t.rowid = ' . $id;
		}
		
		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);
				
				$this->id = $obj->rowid;
				
				$this->doc_date = $this->db->jdate($obj->doc_date);
				$this->doc_type = $obj->doc_type;
				$this->doc_ref = $obj->doc_ref;
				$this->fk_doc = $obj->fk_doc;
				$this->fk_docdet = $obj->fk_docdet;
				$this->code_tiers = $obj->code_tiers;
				$this->numero_compte = $obj->numero_compte;
				$this->label_compte = $obj->label_compte;
				$this->debit = $obj->debit;
				$this->credit = $obj->credit;
				$this->montant = $obj->montant;
				$this->sens = $obj->sens;
				$this->fk_user_author = $obj->fk_user_author;
				$this->import_key = $obj->import_key;
				$this->code_journal = $obj->code_journal;
				$this->piece_num = $obj->piece_num;
			}
			$this->db->free($resql);
			
			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
			
			return - 1;
		}
	}
	
	/**
	 * Load object in memory from the database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit offset limit
	 * @param int $offset offset limit
	 * @param array $filter filter array
	 * @param string $filtermode filter mode (AND or OR)
	 *       
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND') {
		dol_syslog(__METHOD__, LOG_DEBUG);
		
		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		
		$sql .= " t.doc_date,";
		$sql .= " t.doc_type,";
		$sql .= " t.doc_ref,";
		$sql .= " t.fk_doc,";
		$sql .= " t.fk_docdet,";
		$sql .= " t.code_tiers,";
		$sql .= " t.numero_compte,";
		$sql .= " t.label_compte,";
		$sql .= " t.debit,";
		$sql .= " t.credit,";
		$sql .= " t.montant,";
		$sql .= " t.sens,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.import_key,";
		$sql .= " t.code_journal,";
		$sql .= " t.piece_num";
		
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		
		// Manage filter
		$sqlwhere = array ();
		if (count($filter) > 0) {
			foreach ( $filter as $key => $value ) {
				if ($key == 't.doc_date') {
					$sqlwhere[] = $key . '=\'' . $this->db->idate($value) . '\'';
				} elseif ($key == 't.doc_date>=' || $key == 't.doc_date<=') {
					$sqlwhere[] = $key . '\'' . $this->db->idate($value) . '\'';
				} elseif ($key == 't.numero_compte>=' || $key == 't.numero_compte<=' || $key == 't.code_tiers>=' || $key == 't.code_tiers<=') {
					$sqlwhere[] = $key . '\'' . $this->db->escape($value) . '\'';
				} elseif ($key == 't.fk_doc' || $key == 't.fk_docdet' || $key == 't.piece_num') {
					$sqlwhere[] = $key . '=' . $value;
				} elseif ($key == 't.code_tiers' || $key == 't.numero_compte') {
					$sqlwhere[] = $key . ' LIKE \'' . $this->db->escape($value) . '%\'';
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		
		if (count($sqlwhere) > 0) {
			$sql .= ' WHERE ' . implode(' ' . $filtermode . ' ', $sqlwhere);
		}
		
		if (! empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}
		$this->lines = array ();
		
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			
			while ( $obj = $this->db->fetch_object($resql) ) {
				$line = new BookKeepingLine();
				
				$line->id = $obj->rowid;
				
				$line->doc_date = $this->db->jdate($obj->doc_date);
				$line->doc_type = $obj->doc_type;
				$line->doc_ref = $obj->doc_ref;
				$line->fk_doc = $obj->fk_doc;
				$line->fk_docdet = $obj->fk_docdet;
				$line->code_tiers = $obj->code_tiers;
				$line->numero_compte = $obj->numero_compte;
				$line->label_compte = $obj->label_compte;
				$line->debit = $obj->debit;
				$line->credit = $obj->credit;
				$line->montant = $obj->montant;
				$line->sens = $obj->sens;
				$line->fk_user_author = $obj->fk_user_author;
				$line->import_key = $obj->import_key;
				$line->code_journal = $obj->code_journal;
				$line->piece_num = $obj->piece_num;
				
				$this->lines[] = $line;
			}
			$this->db->free($resql);
			
			return $num;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
			
			return - 1;
		}
	}
	
	/**
	 * Load object in memory from the database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit offset limit
	 * @param int $offset offset limit
	 * @param array $filter filter array
	 * @param string $filtermode filter mode (AND or OR)
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchAllBalance($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND') {
		dol_syslog(__METHOD__, LOG_DEBUG);
	
		$sql = 'SELECT';
		$sql .= " t.numero_compte,";
		$sql .= " SUM(t.debit) as debit,";
		$sql .= " SUM(t.credit) as credit";
		
	
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
	
		// Manage filter
		$sqlwhere = array ();
		if (count($filter) > 0) {
			foreach ( $filter as $key => $value ) {
				if ($key == 't.doc_date') {
					$sqlwhere[] = $key . '=\'' . $this->db->idate($value) . '\'';
				} elseif ($key == 't.doc_date>=' || $key == 't.doc_date<=') {
					$sqlwhere[] = $key . '\'' . $this->db->idate($value) . '\'';
				} elseif ($key == 't.numero_compte>=' || $key == 't.numero_compte<=' || $key == 't.code_tiers>=' || $key == 't.code_tiers<=') {
					$sqlwhere[] = $key . '\'' . $this->db->escape($value) . '\'';
				} elseif ($key == 't.fk_doc' || $key == 't.fk_docdet' || $key == 't.piece_num') {
					$sqlwhere[] = $key . '=' . $value;
				} elseif ($key == 't.code_tiers' || $key == 't.numero_compte') {
					$sqlwhere[] = $key . ' LIKE \'' . $this->db->escape($value) . '%\'';
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
	
		if (count($sqlwhere) > 0) {
			$sql .= ' WHERE ' . implode(' ' . $filtermode . ' ', $sqlwhere);
		}
	
		$sql .= ' GROUP BY t.numero_compte';
	
		if (! empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}
		$this->lines = array ();
	
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
	
			while ( $obj = $this->db->fetch_object($resql) ) {
				$line = new BookKeepingLine();
				
				$line->numero_compte = $obj->numero_compte;
				$line->debit = $obj->debit;
				$line->credit = $obj->credit;
				$this->lines[] = $line;
			}
			$this->db->free($resql);
	
			return $num;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
	
			return - 1;
		}
	}
	
	/**
	 * Update object into database
	 *
	 * @param User $user User that modifies
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 *       
	 * @return int <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false) {
		$error = 0;
		
		dol_syslog(__METHOD__, LOG_DEBUG);
		
		// Clean parameters
		
		if (isset($this->doc_type)) {
			$this->doc_type = trim($this->doc_type);
		}
		if (isset($this->doc_ref)) {
			$this->doc_ref = trim($this->doc_ref);
		}
		if (isset($this->fk_doc)) {
			$this->fk_doc = trim($this->fk_doc);
		}
		if (isset($this->fk_docdet)) {
			$this->fk_docdet = trim($this->fk_docdet);
		}
		if (isset($this->code_tiers)) {
			$this->code_tiers = trim($this->code_tiers);
		}
		if (isset($this->numero_compte)) {
			$this->numero_compte = trim($this->numero_compte);
		}
		if (isset($this->label_compte)) {
			$this->label_compte = trim($this->label_compte);
		}
		if (isset($this->debit)) {
			$this->debit = trim($this->debit);
		}
		if (isset($this->credit)) {
			$this->credit = trim($this->credit);
		}
		if (isset($this->montant)) {
			$this->montant = trim($this->montant);
		}
		if (isset($this->sens)) {
			$this->sens = trim($this->sens);
		}
		if (isset($this->fk_user_author)) {
			$this->fk_user_author = trim($this->fk_user_author);
		}
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}
		if (isset($this->code_journal)) {
			$this->code_journal = trim($this->code_journal);
		}
		if (isset($this->piece_num)) {
			$this->piece_num = trim($this->piece_num);
		}
		
		// Check parameters
		// Put here code to add a control on parameters values
		
		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET';
		
		$sql .= ' doc_date = ' . (! isset($this->doc_date) || dol_strlen($this->doc_date) != 0 ? "'" . $this->db->idate($this->doc_date) . "'" : 'null') . ',';
		$sql .= ' doc_type = ' . (isset($this->doc_type) ? "'" . $this->db->escape($this->doc_type) . "'" : "null") . ',';
		$sql .= ' doc_ref = ' . (isset($this->doc_ref) ? "'" . $this->db->escape($this->doc_ref) . "'" : "null") . ',';
		$sql .= ' fk_doc = ' . (isset($this->fk_doc) ? $this->fk_doc : "null") . ',';
		$sql .= ' fk_docdet = ' . (isset($this->fk_docdet) ? $this->fk_docdet : "null") . ',';
		$sql .= ' code_tiers = ' . (isset($this->code_tiers) ? "'" . $this->db->escape($this->code_tiers) . "'" : "null") . ',';
		$sql .= ' numero_compte = ' . (isset($this->numero_compte) ? "'" . $this->db->escape($this->numero_compte) . "'" : "null") . ',';
		$sql .= ' label_compte = ' . (isset($this->label_compte) ? "'" . $this->db->escape($this->label_compte) . "'" : "null") . ',';
		$sql .= ' debit = ' . (isset($this->debit) ? $this->debit : "null") . ',';
		$sql .= ' credit = ' . (isset($this->credit) ? $this->credit : "null") . ',';
		$sql .= ' montant = ' . (isset($this->montant) ? $this->montant : "null") . ',';
		$sql .= ' sens = ' . (isset($this->sens) ? "'" . $this->db->escape($this->sens) . "'" : "null") . ',';
		$sql .= ' fk_user_author = ' . (isset($this->fk_user_author) ? $this->fk_user_author : "null") . ',';
		$sql .= ' import_key = ' . (isset($this->import_key) ? "'" . $this->db->escape($this->import_key) . "'" : "null") . ',';
		$sql .= ' code_journal = ' . (isset($this->code_journal) ? "'" . $this->db->escape($this->code_journal) . "'" : "null") . ',';
		$sql .= ' piece_num = ' . (isset($this->piece_num) ? $this->piece_num : "null");
		
		$sql .= ' WHERE rowid=' . $this->id;
		
		$this->db->begin();
		
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}
		
		if (! $error && ! $notrigger) {
			// Uncomment this and change MYOBJECT to your own tag if you
			// want this action calls a trigger.
			
			// // Call triggers
			// $result=$this->call_trigger('MYOBJECT_MODIFY',$user);
			// if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
			// // End call triggers
		}
		
		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			
			return - 1 * $error;
		} else {
			$this->db->commit();
			
			return 1;
		}
	}
	
	/**
	 * Delete object in database
	 *
	 * @param User $user User that deletes
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 *       
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false) {
		dol_syslog(__METHOD__, LOG_DEBUG);
		
		$error = 0;
		
		$this->db->begin();
		
		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.
				
				// // Call triggers
				// $result=$this->call_trigger('MYOBJECT_DELETE',$user);
				// if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
				// // End call triggers
			}
		}
		
		if (! $error) {
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $this->table_element;
			$sql .= ' WHERE rowid=' . $this->id;
			
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
			}
		}
		
		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			
			return - 1 * $error;
		} else {
			$this->db->commit();
			
			return 1;
		}
	}
	
	/**
	 * Delete bookkepping by importkey
	 *
	 * @param string $importkey Import key
	 * @return int Result
	 */
	function deleteByImportkey($importkey) {
		$this->db->begin();
		
		// first check if line not yet in bookkeeping
		$sql = "DELETE";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE import_key = '" . $importkey . "'";
		
		$resql = $this->db->query($sql);
		
		if (! $resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1;
		}
		
		$this->db->commit();
		return 1;
	}
	
	/**
	 * Delete bookkepping by year
	 *
	 * @param string $delyear year to delete
	 * @return int Result
	 */
	function deleteByYear($delyear) {
		$this->db->begin();
		
		// first check if line not yet in bookkeeping
		$sql = "DELETE";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE YEAR(doc_date) = " . $delyear;
		
		$resql = $this->db->query($sql);
		
		if (! $resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1;
		}
		
		$this->db->commit();
		return 1;
	}
	
	/**
	 * Delete bookkepping by piece number
	 *
	 * @param int $piecenum peicenum to delete
	 * @return int Result
	 */
	function deleteMvtNum($piecenum) {
		$this->db->begin();
		
		// first check if line not yet in bookkeeping
		$sql = "DELETE";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE piece_num = " . $piecenum;
		
		$resql = $this->db->query($sql);
		
		if (! $resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1;
		}
		
		$this->db->commit();
		return 1;
	}
	
	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param int $fromid Id of object to clone
	 *       
	 * @return int New id of clone
	 */
	public function createFromClone($fromid) {
		dol_syslog(__METHOD__, LOG_DEBUG);
		
		global $user;
		$error = 0;
		$object = new Accountingbookkeeping($this->db);
		
		$this->db->begin();
		
		// Load source object
		$object->fetch($fromid);
		// Reset object
		$object->id = 0;
		
		// Clear fields
		// ...
		
		// Create clone
		$result = $object->create($user);
		
		// Other options
		if ($result < 0) {
			$error ++;
			$this->errors = $object->errors;
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}
		
		// End
		if (! $error) {
			$this->db->commit();
			
			return $object->id;
		} else {
			$this->db->rollback();
			
			return - 1;
		}
	}
	
	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen() {
		$this->id = 0;
		
		$this->doc_date = '';
		$this->doc_type = '';
		$this->doc_ref = '';
		$this->fk_doc = '';
		$this->fk_docdet = '';
		$this->code_tiers = '';
		$this->numero_compte = '';
		$this->label_compte = '';
		$this->debit = '';
		$this->credit = '';
		$this->montant = '';
		$this->sens = '';
		$this->fk_user_author = '';
		$this->import_key = '';
		$this->code_journal = '';
		$this->piece_num = '';
	}
	
	/**
	 * Load an accounting document into memory from database
	 *
	 * @param int $piecenum Accounting document to get
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchPerMvt($piecenum) {
		$sql = "SELECT piece_num,doc_date,code_journal,doc_ref,doc_type";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE piece_num = " . $piecenum;
		
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			
			$this->piece_num = $obj->piece_num;
			$this->code_journal = $obj->code_journal;
			$this->doc_date = $this->db->jdate($obj->doc_date);
			$this->doc_ref = $obj->doc_ref;
			$this->doc_type = $obj->doc_type;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
		
		return 1;
	}
	
	/**
	 * Return next number movement
	 *
	 * @return string Last number
	 */
	public function getNextNumMvt() {
		$sql = "SELECT MAX(piece_num)+1 as max FROM " . MAIN_DB_PREFIX . $this->table_element;
		
		dol_syslog(get_class($this) . "getNextNumMvt sql=" . $sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		
		if ($result) {
			$obj = $this->db->fetch_object($result);
			
			return $obj->max;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::getNextNumMvt " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Load all informations of accountancy document
	 *
	 * @param int $piecenum id of line to get
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_all_per_mvt($piecenum) {
		$sql = "SELECT rowid, doc_date, doc_type,";
		$sql .= " doc_ref, fk_doc, fk_docdet, code_tiers,";
		$sql .= " numero_compte, label_compte, debit, credit,";
		$sql .= " montant, sens, fk_user_author, import_key, code_journal, piece_num";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE piece_num = " . $piecenum;
		
		dol_syslog(get_class($this) . "fetch_all_per_mvt sql=" . $sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			
			while ( $obj = $this->db->fetch_object($result) ) {
				
				$line = new BookKeepingLine();
				
				$line->id = $obj->rowid;
				
				$line->doc_date = $this->db->jdate($obj->doc_date);
				$line->doc_type = $obj->doc_type;
				$line->doc_ref = $obj->doc_ref;
				$line->fk_doc = $obj->fk_doc;
				$line->fk_docdet = $obj->fk_docdet;
				$line->code_tiers = $obj->code_tiers;
				$line->numero_compte = $obj->numero_compte;
				$line->label_compte = $obj->label_compte;
				$line->debit = $obj->debit;
				$line->credit = $obj->credit;
				$line->montant = $obj->montant;
				$line->sens = $obj->sens;
				$line->code_journal = $obj->code_journal;
				$line->piece_num = $obj->piece_num;
				
				$this->linesmvt[] = $line;
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_all_per_mvt " . $this->error, LOG_ERR);
			return - 1;
		}
		
		return 1;
	}
	
	/**
	 * Export bookkeping
	 *
	 * @param string $model Model
	 * @return int Result
	 */
	function export_bookkeping($model = 'ebp') {
		$sql = "SELECT rowid, doc_date, doc_type,";
		$sql .= " doc_ref, fk_doc, fk_docdet, code_tiers,";
		$sql .= " numero_compte, label_compte, debit, credit,";
		$sql .= " montant, sens, fk_user_author, import_key, code_journal, piece_num";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		
		dol_syslog(get_class($this) . "::export_bookkeping", LOG_DEBUG);
		
		$resql = $this->db->query($sql);
		
		if ($resql) {
			$this->linesexport = array ();
			
			$num = $this->db->num_rows($resql);
			while ( $obj = $this->db->fetch_object($resql) ) {
				$line = new BookKeepingLine();
				
				$line->id = $obj->rowid;
				
				$line->doc_date = $this->db->jdate($obj->doc_date);
				$line->doc_type = $obj->doc_type;
				$line->doc_ref = $obj->doc_ref;
				$line->fk_doc = $obj->fk_doc;
				$line->fk_docdet = $obj->fk_docdet;
				$line->code_tiers = $obj->code_tiers;
				$line->numero_compte = $obj->numero_compte;
				$line->label_compte = $obj->label_compte;
				$line->debit = $obj->debit;
				$line->credit = $obj->credit;
				$line->montant = $obj->montant;
				$line->sens = $obj->sens;
				$line->code_journal = $obj->code_journal;
				$line->piece_num = $obj->piece_num;
				
				$this->linesexport[] = $line;
			}
			$this->db->free($resql);
			
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::export_bookkeping " . $this->error, LOG_ERR);
			return - 1;
		}
	}
}

/**
 * Class BookKeepingLine
 */
class BookKeepingLine
{
	public $id;
	public $doc_date = '';
	public $doc_type;
	public $doc_ref;
	public $fk_doc;
	public $fk_docdet;
	public $code_tiers;
	public $numero_compte;
	public $label_compte;
	public $debit;
	public $credit;
	public $montant;
	public $sens;
	public $fk_user_author;
	public $import_key;
	public $code_journal;
	public $piece_num;
}
