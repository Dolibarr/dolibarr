<?php
/* Copyright (C) 2017		Alexandre Spangaro   <aspangaro@zendsi.com>
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
 * \file		htdocs/accountancy/class/accountingjournal.class.php
 * \ingroup		Advanced accountancy
 * \brief		File of class to manage accounting journals
 */

/**
 * Class to manage accounting accounts
 */
class AccountingJournal extends CommonObject
{
	public $element='accounting_journal';
	public $table_element='accounting_journal';
	public $fk_element = '';
	protected $ismultientitymanaged = 0;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	var $rowid;

	var $code;
	var $label;
	var $nature;		// 0:various operations, 1:sale, 2:purchase, 3:bank, 9: has-new
	var $active;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handle
	 */
	function __construct($db) {
		$this->db = $db;
	}
	
	/**
	* Load an object from database
	*
	* @param	int		$id		Id of record to load
	* @return	int				<0 if KO, >0 if OK
	*/
	function fetch($id)
	{
		$sql = "SELECT rowid, code, label, nature, active";
		$sql.= " FROM ".MAIN_DB_PREFIX."accounting_journal";
		$sql.= " WHERE rowid = ".$id;

		dol_syslog(get_class($this)."::fetch sql=" . $sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ( $result )
		{
			$obj = $this->db->fetch_object($result);

			$this->id			= $obj->rowid;

			$this->code			= $obj->code;
			$this->ref			= $obj->code;
			$this->label		= $obj->label;
			$this->nature	    = $obj->nature;
			$this->active		= $obj->active;

			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}
	
	/**
	 * Insert journal in database
	 *
	 * @param	User	$user		Use making action
	 * @param	int		$notrigger	Disable triggers
	 * @return 	int 				<0 if KO, >0 if OK
	 */
	function create($user, $notrigger = 0)
	{
		global $conf;
		$error = 0;
		$now = dol_now();
		
		// Clean parameters
		if (isset($this->code))
			$this->code = trim($this->code);
		if (isset($this->label))
			$this->label = trim($this->label);

		// Check parameters
		if (empty($this->nature) || $this->nature == '-1')
		{
		    $this->nature = '0';
		}

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "accounting_journal(";
		$sql .= "code";
		$sql .= ", label";
		$sql .= ", nature";
		$sql .= ", active";
		$sql .= ") VALUES (";
		$sql .= " " . (empty($this->code) ? 'NULL' : "'" . $this->db->escape($this->code) . "'");
		$sql .= ", " . (empty($this->label) ? 'NULL' : "'" . $this->db->escape($this->label) . "'");
		$sql .= ", " . (empty($this->nature) ? '0' : "'" . $this->db->escape($this->nature) . "'");
		$sql .= ", " . (! isset($this->active) ? 'NULL' : $this->db->escape($this->active));
		$sql .= ")";
		
		$this->db->begin();
		
		dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}
		
		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "accounting_journal");
			
			// if (! $notrigger) {
			// Uncomment this and change MYOBJECT to your own tag if you
			// want this action calls a trigger.
			
			// // Call triggers
			// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
			// $interface=new Interfaces($this->db);
			// $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
			// if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// // End call triggers
			// }
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}
	
	/**
	 * Update record
	 *
	 * @param  User $user      Use making update
	 * @return int             <0 if KO, >0 if OK
	 */
	function update($user) 
	{
	    // Check parameters
	    if (empty($this->nature) || $this->nature == '-1')
	    {
	        $this->nature = '0';
	    }

	    $this->db->begin();
		
		$sql = "UPDATE " . MAIN_DB_PREFIX . "accounting_journal ";
		$sql .= " SET code = " . ($this->code ? "'" . $this->db->escape($this->code) . "'" : "null");
		$sql .= " , label = " . ($this->label ? "'" . $this->db->escape($this->label) . "'" : "null");
		$sql .= " , nature = " . ($this->nature ? "'" . $this->db->escape($this->nature) . "'" : "0");
		$sql .= " , active = '" . $this->active . "'";
		$sql .= " WHERE rowid = " . $this->id;
		
		dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return - 1;
		}
	}
	
	/**
	 * Check usage of accounting journal
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function checkUsage() {
		global $langs;
		
		$sql = "(SELECT fk_code_ventilation FROM " . MAIN_DB_PREFIX . "facturedet";
		$sql .= " WHERE  fk_code_ventilation=" . $this->id . ")";
		$sql .= "UNION";
		$sql .= "(SELECT fk_code_ventilation FROM " . MAIN_DB_PREFIX . "facture_fourn_det";
		$sql .= " WHERE  fk_code_ventilation=" . $this->id . ")";
		
		dol_syslog(get_class($this) . "::checkUsage sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				$this->error = $langs->trans('ErrorAccountingJournalIsAlreadyUse');
				return 0;
			} else {
				return 1;
			}
		} else {
			$this->error = $this->db->lasterror();
			return - 1;
		}
	}
	
	/**
	 * Delete object in database
	 *
	 * @param User $user User that deletes
	 * @param int $notrigger 0=triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger = 0) {
		$error = 0;
		
		$result = $this->checkUsage();
		
		if ($result > 0) {
			
			$this->db->begin();
			
			// if (! $error) {
			// if (! $notrigger) {
			// Uncomment this and change MYOBJECT to your own tag if you
			// want this action calls a trigger.
			
			// // Call triggers
			// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
			// $interface=new Interfaces($this->db);
			// $result=$interface->run_triggers('ACCOUNTANCY_ACCOUNT_DELETE',$this,$user,$langs,$conf);
			// if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// // End call triggers
			// }
			// }
			
			if (! $error) {
				$sql = "DELETE FROM " . MAIN_DB_PREFIX . "accounting_journal";
				$sql .= " WHERE rowid=" . $this->id;
				
				dol_syslog(get_class($this) . "::delete sql=" . $sql);
				$resql = $this->db->query($sql);
				if (! $resql) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
			}
			
			// Commit or rollback
			if ($error) {
				foreach ( $this->errors as $errmsg ) {
					dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
				}
				$this->db->rollback();
				return - 1 * $error;
			} else {
				$this->db->commit();
				return 1;
			}
		} else {
			return - 1;
		}
	}
	
	/**
	 * Return clicable name (with picto eventually)
	 *
	 * @param	int		$withpicto	0=No picto, 1=Include picto into link, 2=Only picto
	 * @return	string				Chaine avec URL
	 */
	function getNomUrl($withpicto = 0) {
		global $langs;

		$result = '';

		$link = '<a href="' . DOL_URL_ROOT . '/accountancy/admin/journals_card.php?id=' . $this->id . '">';
		$linkend = '</a>';

		$picto = 'billr';

		$label = $langs->trans("Show") . ': ' . $this->code . ' - ' . $this->label;

		if ($withpicto)
			$result .= ($link . img_object($label, $picto) . $linkend);
		if ($withpicto && $withpicto != 2)
			$result .= ' ';
		if ($withpicto != 2)
			$result .= $link . $this->code . ' - ' . $this->label . $linkend;
		return $result;
	}
	
	/**
	 * Deactivate journal
	 *
	 * @param int $id Id
	 * @return int <0 if KO, >0 if OK
	 */
	function journal_deactivate($id) {
		$result = $this->checkUsage();
		
		if ($result > 0) {
			$this->db->begin();
			
			$sql = "UPDATE " . MAIN_DB_PREFIX . "accounting_journal ";
			$sql .= "SET active = '0'";
			$sql .= " WHERE rowid = " . $this->db->escape($id);
			
			dol_syslog(get_class($this) . "::deactivate sql=" . $sql, LOG_DEBUG);
			$result = $this->db->query($sql);
			
			if ($result) {
				$this->db->commit();
				return 1;
			} else {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return - 1;
			}
		} else {
			return - 1;
		}
	}
	
	/**
	 * Activate journal
	 *
	 * @param int $id Id
	 * @return int <0 if KO, >0 if OK
	 */
	function journal_activate($id) {
		$this->db->begin();
		
		$sql = "UPDATE " . MAIN_DB_PREFIX . "accounting_journal ";
		$sql .= "SET active = '1'";
		$sql .= " WHERE rowid = " . $this->db->escape($id);
		
		dol_syslog(get_class($this) . "::activate sql=" . $sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return - 1;
		}
	}
	
	
	/**
	 *  Retourne le libelle du statut d'un user (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court
	 *  @return	string 			       Label of type
	 */
	function getLibType($mode=0)
	{
	    return $this->LibType($this->nature,$mode);
	}
	
	/**
	 *  Return type of an accounting journal
	 *
	 *  @param	int		$nature        	Id type
	 *  @param  int		$mode          	0=libelle long, 1=libelle court
	 *  @return string 			       	Label of type
	 */
	function LibType($nature,$mode=0)
	{
	    global $langs;

		$langs->load("accountancy");
	
	    if ($mode == 0)
	    {
	        $prefix='';
			if ($nature == 9) return $langs->trans('AccountingJournalTypeHasNew');
			if ($nature == 3) return $langs->trans('AccountingJournalTypeBank');
			if ($nature == 2) return $langs->trans('AccountingJournalTypePurchase');
	        if ($nature == 1) return $langs->trans('AccountingJournalTypeSale');
	        if ($nature == 0) return $langs->trans('AccountingJournalTypeVariousOperation');
	    }
	    if ($mode == 1)
	    {
			if ($nature == 9) return $langs->trans('AccountingJournalTypeHasNew');
			if ($nature == 3) return $langs->trans('AccountingJournalTypeBank');
			if ($nature == 2) return $langs->trans('AccountingJournalTypePurchase');
	        if ($nature == 1) return $langs->trans('AccountingJournalTypeSale');
	        if ($nature == 0) return $langs->trans('AccountingJournalTypeVariousOperation');
	    }
	}
}
