<?php
/* Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2015 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2013-2014 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014 	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Ari Elbaz (elarifr)  <github@accedinfo.com>
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
 * \file		htdocs/accountancy/class/accountingaccount.class.php
 * \ingroup		Accounting Expert
 * \brief		Fichier de la classe des comptes comptable
 */

/**
 * Class to manage accounting accounts
 */
class AccountingAccount extends CommonObject
{
	var $rowid;

	var $datec; // Creation date
	var $fk_pcg_version;
	var $pcg_type;
	var $pcg_subtype;
	var $account_number;
	var $account_parent;
	var $label;
	var $fk_user_author;
	var $fk_user_modif;
	var $active;

	/**
	 * Constructor
	 *
	 * @param 	DoliDB	$db		Database handle
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Load record in memory
	 *
	 * @param	int		$rowid					Id
	 * @param	string	$account_number			Account number
	 * @param	int		$limittocurentchart		1=Do not load record if it is into another accounting system
	 * @return 	int								<0 if KO, >0 if OK
	 */
	function fetch($rowid = null, $account_number = null, $limittocurentchart=0)
	{
		global $conf;

		if ($rowid || $account_number) {
			$sql = "SELECT rowid, datec, tms, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, fk_user_author, fk_user_modif, active";
			$sql.= " FROM " . MAIN_DB_PREFIX . "accounting_account WHERE";
			if ($rowid) {
				$sql .= " rowid = '" . $rowid . "'";
			} elseif ($account_number) {
				$sql .= " account_number = '" . $account_number . "'";
			}
			if (!empty($limittocurentchart)) {
				$sql .=' AND fk_pcg_version IN (SELECT pcg_version FROM '.MAIN_DB_PREFIX.'accounting_system WHERE rowid='.$conf->global->CHARTOFACCOUNTS.')';
			}
			dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result) {
				$obj = $this->db->fetch_object($result);

				if ($obj) {
					$this->id = $obj->rowid;
					$this->rowid = $obj->rowid;
					$this->datec = $obj->datec;
					$this->tms = $obj->tms;
					$this->fk_pcg_version = $obj->fk_pcg_version;
					$this->pcg_type = $obj->pcg_type;
					$this->pcg_subtype = $obj->pcg_subtype;
					$this->account_number = $obj->account_number;
					$this->account_parent = $obj->account_parent;
					$this->label = $obj->label;
					$this->fk_user_author = $obj->fk_user_author;
					$this->fk_user_modif = $obj->fk_user_modif;
					$this->active = $obj->active;

					return $this->id;
				} else {
					return 0;
				}
			} else {
				$this->error="Error " . $this->db->lasterror();
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}
		return -1;
	}

	/**
	 * Insert line in accounting_account
	 *
	 * @param 	User	$user 			Use making action
	 * @param	int		$notrigger		Disable triggers
	 * @return 	int						<0 if KO, >0 if OK
	 */
	function create($user, $notrigger = 0)
	{
		global $conf;
		$error = 0;
		$now = dol_now();

		// Clean parameters
		if (isset($this->fk_pcg_version))
			$this->fk_pcg_version = trim($this->fk_pcg_version);
		if (isset($this->pcg_type))
			$this->pcg_type = trim($this->pcg_type);
		if (isset($this->pcg_subtype))
			$this->pcg_subtype = trim($this->pcg_subtype);
		if (isset($this->account_number))
			$this->account_number = trim($this->account_number);
		if (isset($this->account_parent))
			$this->account_parent = trim($this->account_parent);
		if (isset($this->label))
			$this->label = trim($this->label);
		if (isset($this->fk_user_author))
			$this->fk_user_author = trim($this->fk_user_author);
		if (isset($this->active))
			$this->active = trim($this->active);

		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "accounting_account(";

		$sql .= "datec";
		$sql .= ", entity";
		$sql .= ", fk_pcg_version";
		$sql .= ", pcg_type";
		$sql .= ", pcg_subtype";
		$sql .= ", account_number";
		$sql .= ", account_parent";
		$sql .= ", label";
		$sql .= ", fk_user_author";
		$sql .= ", active";

		$sql .= ") VALUES (";

		$sql .= " '" . $this->db->idate($now) . "'";
		$sql .= ", " . $conf->entity;
		$sql .= ", " . (! isset($this->fk_pcg_version) ? 'NULL' : "'" . $this->db->escape($this->fk_pcg_version) . "'");
		$sql .= ", " . (! isset($this->pcg_type) ? 'NULL' : "'" . $this->db->escape($this->pcg_type) . "'");
		$sql .= ", " . (! isset($this->pcg_subtype) ? 'NULL' : "'" . $this->pcg_subtype . "'");
		$sql .= ", " . (! isset($this->account_number) ? 'NULL' : "'" . $this->account_number . "'");
		$sql .= ", " . (! isset($this->account_parent) ? 'NULL' : "'" . $this->db->escape($this->account_parent) . "'");
		$sql .= ", " . (! isset($this->label) ? 'NULL' : "'" . $this->db->escape($this->label) . "'");
		$sql .= ", " . $user->id;
		$sql .= ", " . (! isset($this->active) ? 'NULL' : "'" . $this->db->escape($this->active) . "'");

		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "accounting_account");

//			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
//			}
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
	 * @param 	User 	$user 	Use making update
	 * @return 	int 			<0 if KO, >0 if OK
	 */
	function update($user)
	{
		$this->db->begin();

		$sql = "UPDATE " . MAIN_DB_PREFIX . "accounting_account ";
		$sql .= " SET fk_pcg_version = " . ($this->fk_pcg_version ? "'" . $this->db->escape($this->fk_pcg_version) . "'" : "null");
		$sql .= " , pcg_type = " . ($this->pcg_type ? "'" . $this->db->escape($this->pcg_type) . "'" : "null");
		$sql .= " , pcg_subtype = " . ($this->pcg_subtype ? "'" . $this->db->escape($this->pcg_subtype) . "'" : "null");
		$sql .= " , account_number = '" . $this->account_number . "'";
		$sql .= " , account_parent = '" . $this->account_parent . "'";
		$sql .= " , label = " . ($this->label ? "'" . $this->db->escape($this->label) . "'" : "null");
		$sql .= " , fk_user_modif = " . $user->id;
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
	 * Check usage of accounting code
	 *
	 * @return 	int 			<0 if KO, >0 if OK
	 */
	function checkUsage()
	{
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
				$this->error = $langs->trans('ErrorAccountancyCodeIsAlreadyUse');
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
	 * @param 	User 	$user 			User that deletes
	 * @param 	int 	$notrigger 		0=triggers after, 1=disable triggers
	 * @return 	int 					<0 if KO, >0 if OK
	 */
	function delete($user, $notrigger = 0)
	{
		$error = 0;

		$result = $this->checkUsage();

		if ($result > 0) {

			$this->db->begin();

//			if (! $error) {
//				if (! $notrigger) {
					// Uncomment this and change MYOBJECT to your own tag if you
					// want this action calls a trigger.

					// // Call triggers
					// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
					// $interface=new Interfaces($this->db);
					// $result=$interface->run_triggers('ACCOUNTANCY_ACCOUNT_DELETE',$this,$user,$langs,$conf);
					// if ($result < 0) { $error++; $this->errors=$interface->errors; }
					// // End call triggers
//				}
//			}

			if (! $error) {
				$sql = "DELETE FROM " . MAIN_DB_PREFIX . "accounting_account";
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
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param		int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@return		string					Chaine avec URL
	 */
	function getNomUrl($withpicto=0)
	{
		global $langs;

		$result='';

		$link = '<a href="'.DOL_URL_ROOT.'/accountancy/admin/card.php?id='.$this->id.'">';
		$linkend='</a>';

		$picto='billr';

		$label=$langs->trans("Show").': '.$this->account_number.' - '.$this->label;

		if ($withpicto) $result.=($link.img_object($label,$picto).$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$link.$this->account_number.$linkend;
		return $result;
	}

	/**
	 * Information on record
	 *
	 * @param int $id of record
	 * @return void
	 */
	function info($id)
	{
		$sql = 'SELECT a.rowid, a.datec, a.fk_user_author, a.fk_user_modif, a.tms';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'accounting_account as a';
		$sql .= ' WHERE a.rowid = ' . $id;

		dol_syslog(get_class($this) . '::info sql=' . $sql);
		$result = $this->db->query($sql);

		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}
				if ($obj->fk_user_modif) {
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_modif);
					$this->user_modification = $muser;
				}
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Account desactivate
	 *
	 * @param	int		$id		Id
	 * @return 	int 			<0 if KO, >0 if OK
	 */
	function account_desactivate($id)
	{
		$result = $this->checkUsage();

		if ($result > 0) {
			$this->db->begin();

			$sql = "UPDATE " . MAIN_DB_PREFIX . "accounting_account ";
			$sql .= "SET active = '0'";
			$sql .= " WHERE rowid = ".$this->db->escape($id);

			dol_syslog(get_class($this) . "::desactivate sql=" . $sql, LOG_DEBUG);
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
	 * Account activate
	 *
	 * @param 	int		$id		Id
	 * @return 	int 			<0 if KO, >0 if OK
	 */
	function account_activate($id)
	{
		$this->db->begin();

		$sql = "UPDATE " . MAIN_DB_PREFIX . "accounting_account ";
		$sql .= "SET active = '1'";
		$sql .= " WHERE rowid = ".$this->db->escape($id);

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
}
