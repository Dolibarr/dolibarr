<?php
/* Copyright (C) 2013-2014  Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2021  Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2013-2021  Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2014       Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015       Ari Elbaz (elarifr)  <github@accedinfo.com>
 * Copyright (C) 2018       Frédéric France      <frederic.france@netlogic.fr>
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
 *  \file       htdocs/accountancy/class/accountingaccount.class.php
 *  \ingroup    Accountancy (Double entries)
 *  \brief      File of class to manage accounting accounts
 */

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

/**
 * Class to manage accounting accounts
 */
class AccountingAccount extends CommonObject
{
	/**
	 * @var string Name of element
	 */
	public $element = 'accounting_account';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'accounting_account';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'billr';

	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;

	/**
	 * 0=Default, 1=View may be restricted to sales representative only if no permission to see all or to company of external user if external user
	 * @var integer
	 */
	public $restrictiononfksoc = 1;

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * Date creation record (datec)
	 *
	 * @var integer
	 */
	public $datec;

	/**
	 * @var string pcg version
	 */
	public $fk_pcg_version;

	/**
	 * @var string pcg type
	 */
	public $pcg_type;

	/**
	 * @var string account number
	 */
	public $account_number;

	/**
	 * @var int ID parent account
	 */
	public $account_parent;

	/**
	 * @var int ID category account
	 */
	public $account_category;

	/**
	 * @var int Label category account
	 */
	public $account_category_label;

	/**
	 * @var int Status
	 */
	public $status;

	/**
	 * @var string Label of account
	 */
	public $label;

	/**
	 * @var string Label short of account
	 */
	public $labelshort;

	/**
	 * @var int ID
	 */
	public $fk_user_author;

	/**
	 * @var int ID
	 */
	public $fk_user_modif;

	/**
	 * @var int active (duplicate with status)
	 */
	public $active;

	/**
	 * @var int reconcilable
	 */
	public $reconcilable;

	/**
	 * @var array cache array
	 */
	private $accountingaccount_codetotid_cache = array();


	const STATUS_ENABLED = 1;
	const STATUS_DISABLED = 0;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handle
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->next_prev_filter = "fk_pcg_version IN (SELECT pcg_version FROM ".MAIN_DB_PREFIX."accounting_system WHERE rowid = ".((int) $conf->global->CHARTOFACCOUNTS).")"; // Used to add a filter in Form::showrefnav method
	}

	/**
	 * Load record in memory
	 *
	 * @param 	int 	       $rowid 				    Id
	 * @param 	string 	       $account_number 	        Account number
	 * @param 	int|boolean    $limittocurrentchart     1 or true=Load record only if it is into current active chart of account
	 * @param   string         $limittoachartaccount    'ABC'=Load record only if it is into chart account with code 'ABC' (better and faster than previous parameter if you have chart of account code).
	 * @return 	int                                     <0 if KO, 0 if not found, Id of record if OK and found
	 */
	public function fetch($rowid = null, $account_number = null, $limittocurrentchart = 0, $limittoachartaccount = '')
	{
		global $conf;

		if ($rowid || $account_number) {
			$sql  = "SELECT a.rowid as rowid, a.datec, a.tms, a.fk_pcg_version, a.pcg_type, a.account_number, a.account_parent, a.label, a.labelshort, a.fk_accounting_category, a.fk_user_author, a.fk_user_modif, a.active, a.reconcilable";
			$sql .= ", ca.label as category_label";
			$sql .= " FROM ".MAIN_DB_PREFIX."accounting_account as a";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_accounting_category as ca ON a.fk_accounting_category = ca.rowid";
			$sql .= " WHERE";
			if ($rowid) {
				$sql .= " a.rowid = ".(int) $rowid;
			} elseif ($account_number) {
				$sql .= " a.account_number = '".$this->db->escape($account_number)."'";
				$sql .= " AND a.entity = ".$conf->entity;
			}
			if (!empty($limittocurrentchart)) {
				$sql .= ' AND a.fk_pcg_version IN (SELECT pcg_version FROM '.MAIN_DB_PREFIX.'accounting_system WHERE rowid = '.((int) $conf->global->CHARTOFACCOUNTS).')';
			}
			if (!empty($limittoachartaccount)) {
				$sql .= " AND a.fk_pcg_version = '".$this->db->escape($limittoachartaccount)."'";
			}

			dol_syslog(get_class($this)."::fetch rowid=".$rowid." account_number=".$account_number, LOG_DEBUG);

			$result = $this->db->query($sql);
			if ($result) {
				$obj = $this->db->fetch_object($result);

				if ($obj) {
					$this->id = $obj->rowid;
					$this->rowid = $obj->rowid;
					$this->ref = $obj->account_number;
					$this->datec = $obj->datec;
					$this->tms = $obj->tms;
					$this->fk_pcg_version = $obj->fk_pcg_version;
					$this->pcg_type = $obj->pcg_type;
					$this->account_number = $obj->account_number;
					$this->account_parent = $obj->account_parent;
					$this->label = $obj->label;
					$this->labelshort = $obj->labelshort;
					$this->account_category = $obj->fk_accounting_category;
					$this->account_category_label = $obj->category_label;
					$this->fk_user_author = $obj->fk_user_author;
					$this->fk_user_modif = $obj->fk_user_modif;
					$this->active = $obj->active;
					$this->status = $obj->active;
					$this->reconcilable = $obj->reconcilable;

					return $this->id;
				} else {
					return 0;
				}
			} else {
				$this->error = "Error ".$this->db->lasterror();
				$this->errors[] = "Error ".$this->db->lasterror();
			}
		}
		return -1;
	}

	/**
	 * Insert new accounting account in chart of accounts
	 *
	 * @param User $user User making action
	 * @param int $notrigger Disable triggers
	 * @return int                 <0 if KO, >0 if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf;
		$error = 0;
		$now = dol_now();

		// Clean parameters
		if (isset($this->fk_pcg_version)) {
			$this->fk_pcg_version = trim($this->fk_pcg_version);
		}
		if (isset($this->pcg_type)) {
			$this->pcg_type = trim($this->pcg_type);
		}
		if (isset($this->account_number)) {
			$this->account_number = trim($this->account_number);
		}
		if (isset($this->label)) {
			$this->label = trim($this->label);
		}
		if (isset($this->labelshort)) {
			$this->labelshort = trim($this->labelshort);
		}

		if (empty($this->pcg_type) || $this->pcg_type == '-1') {
			$this->pcg_type = 'XXXXXX';
		}
		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "accounting_account(";
		$sql .= "datec";
		$sql .= ", entity";
		$sql .= ", fk_pcg_version";
		$sql .= ", pcg_type";
		$sql .= ", account_number";
		$sql .= ", account_parent";
		$sql .= ", label";
		$sql .= ", labelshort";
		$sql .= ", fk_accounting_category";
		$sql .= ", fk_user_author";
		$sql .= ", active";
		$sql .= ", reconcilable";
		$sql .= ") VALUES (";
		$sql .= " '".$this->db->idate($now)."'";
		$sql .= ", ".((int) $conf->entity);
		$sql .= ", ".(empty($this->fk_pcg_version) ? 'NULL' : "'".$this->db->escape($this->fk_pcg_version)."'");
		$sql .= ", ".(empty($this->pcg_type) ? 'NULL' : "'".$this->db->escape($this->pcg_type)."'");
		$sql .= ", ".(empty($this->account_number) ? 'NULL' : "'".$this->db->escape($this->account_number)."'");
		$sql .= ", ".(empty($this->account_parent) ? 0 : (int) $this->account_parent);
		$sql .= ", ".(empty($this->label) ? "''" : "'".$this->db->escape($this->label)."'");
		$sql .= ", ".(empty($this->labelshort) ? "''" : "'".$this->db->escape($this->labelshort)."'");
		$sql .= ", ".(empty($this->account_category) ? 0 : (int) $this->account_category);
		$sql .= ", ".((int) $user->id);
		$sql .= ", ".(int) $this->active;
		$sql .= ", ".(int) $this->reconcilable;
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "accounting_account");

			// Uncomment this and change MYOBJECT to your own tag if you
			// want this action to call a trigger.
			//if (! $error && ! $notrigger) {

			// // Call triggers
			// $result=$this->call_trigger('MYOBJECT_CREATE',$user);
			// if ($result < 0) $error++;
			// // End call triggers
			//}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * Update record
	 *
	 * @param User $user Use making update
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update($user)
	{
		// Check parameters
		if (empty($this->pcg_type) || $this->pcg_type == '-1') {
			$this->pcg_type = 'XXXXXX';
		}

		$this->db->begin();

		$sql = "UPDATE " . MAIN_DB_PREFIX . "accounting_account ";
		$sql .= " SET fk_pcg_version = " . ($this->fk_pcg_version ? "'" . $this->db->escape($this->fk_pcg_version) . "'" : "null");
		$sql .= " , pcg_type = " . ($this->pcg_type ? "'" . $this->db->escape($this->pcg_type) . "'" : "null");
		$sql .= " , account_number = '" . $this->db->escape($this->account_number) . "'";
		$sql .= " , account_parent = " . (int) $this->account_parent;
		$sql .= " , label = " . ($this->label ? "'" . $this->db->escape($this->label) . "'" : "''");
		$sql .= " , labelshort = " . ($this->labelshort ? "'" . $this->db->escape($this->labelshort) . "'" : "''");
		$sql .= " , fk_accounting_category = " . (empty($this->account_category) ? 0 : (int) $this->account_category);
		$sql .= " , fk_user_modif = " . ((int) $user->id);
		$sql .= " , active = " . (int) $this->active;
		$sql .= " , reconcilable = " . (int) $this->reconcilable;
		$sql .= " WHERE rowid = " . ((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Check usage of accounting code
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function checkUsage()
	{
		global $langs;

		$sql = "(SELECT fk_code_ventilation FROM ".MAIN_DB_PREFIX."facturedet";
		$sql .= " WHERE fk_code_ventilation=".((int) $this->id).")";
		$sql .= "UNION";
		$sql .= " (SELECT fk_code_ventilation FROM ".MAIN_DB_PREFIX."facture_fourn_det";
		$sql .= " WHERE fk_code_ventilation=".((int) $this->id).")";

		dol_syslog(get_class($this)."::checkUsage", LOG_DEBUG);
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
			return -1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user User that deletes
	 * @param int $notrigger 0=triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		$error = 0;

		$result = $this->checkUsage();

		if ($result > 0) {
			$this->db->begin();

			if (!$error) {
				$sql = "DELETE FROM " . MAIN_DB_PREFIX . "accounting_account";
				$sql .= " WHERE rowid=" . ((int) $this->id);

				dol_syslog(get_class($this) . "::delete sql=" . $sql);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
			}

			// Commit or rollback
			if ($error) {
				foreach ($this->errors as $errmsg) {
					dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			} else {
				$this->db->commit();
				return 1;
			}
		} else {
			return -1;
		}
	}

	/**
	 * Return clicable name (with picto eventually)
	 *
	 * @param int $withpicto 0=No picto, 1=Include picto into link, 2=Only picto
	 * @param int $withlabel 0=No label, 1=Include label of account
	 * @param int $nourl 1=Disable url
	 * @param string $moretitle Add more text to title tooltip
	 * @param int $notooltip 1=Disable tooltip
	 * @param int $save_lastsearch_value -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @param int $withcompletelabel 0=Short label (field short label), 1=Complete label (field label)
	 * @param string $option 'ledger', 'journals', 'accountcard'
	 * @return  string    String with URL
	 */
	public function getNomUrl($withpicto = 0, $withlabel = 0, $nourl = 0, $moretitle = '', $notooltip = 0, $save_lastsearch_value = -1, $withcompletelabel = 0, $option = '')
	{
		global $langs, $conf;
		require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$url = '';
		$labelurl = '';
		if (empty($option) || $option == 'ledger') {
			$url = DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?search_accountancy_code_start=' . urlencode($this->account_number) . '&search_accountancy_code_end=' . urlencode($this->account_number);
			$labelurl = $langs->trans("ShowAccountingAccountInLedger");
		} elseif ($option == 'journals') {
			$url = DOL_URL_ROOT . '/accountancy/bookkeeping/list.php?search_accountancy_code_start=' . urlencode($this->account_number) . '&search_accountancy_code_end=' . urlencode($this->account_number);
			$labelurl = $langs->trans("ShowAccountingAccountInJournals");
		} elseif ($option == 'accountcard') {
			$url = DOL_URL_ROOT . '/accountancy/admin/card.php?id=' . urlencode($this->id);
			$labelurl = $langs->trans("ShowAccountingAccount");
		}

		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
			$add_save_lastsearch_values = 1;
		}
		if ($add_save_lastsearch_values) {
			$url .= '&save_lastsearch_values=1';
		}

		$picto = 'accounting_account';
		$label = '';

		if (empty($this->labelshort) || $withcompletelabel == 1) {
			$labeltoshow = $this->label;
		} else {
			$labeltoshow = $this->labelshort;
		}

		$label = '<u>' . $labelurl . '</u>';
		if (!empty($this->account_number)) {
			$label .= '<br><b>' . $langs->trans('AccountAccounting') . ':</b> ' . length_accountg($this->account_number);
		}
		if (!empty($labeltoshow)) {
			$label .= '<br><b>' . $langs->trans('Label') . ':</b> ' . $labeltoshow;
		}
		if ($moretitle) {
			$label .= ' - ' . $moretitle;
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $labelurl;
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip"';
		}

		$linkstart = '<a href="' . $url . '"';
		$linkstart .= $linkclose . '>';
		$linkend = '</a>';

		if ($nourl) {
			$linkstart = '';
			$linkclose = '';
			$linkend = '';
		}

		$label_link = length_accountg($this->account_number);
		if ($withlabel) {
			$label_link .= ' - ' . ($nourl ? '<span class="opacitymedium">' : '') . $labeltoshow . ($nourl ? '</span>' : '');
		}

		if ($withpicto) {
			$result .= ($linkstart . img_object(($notooltip ? '' : $label), $picto, ($notooltip ? '' : 'class="classfortooltip"'), 0, 0, $notooltip ? 0 : 1) . $linkend);
		}
		if ($withpicto && $withpicto != 2) {
			$result .= ' ';
		}
		if ($withpicto != 2) {
			$result .= $linkstart . $label_link . $linkend;
		}
		return $result;
	}

	/**
	 * Information on record
	 *
	 * @param int 	$id 	ID of record
	 * @return void
	 */
	public function info($id)
	{
		$sql = 'SELECT a.rowid, a.datec, a.fk_user_author, a.fk_user_modif, a.tms';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'accounting_account as a';
		$sql .= ' WHERE a.rowid = ' . ((int) $id);

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
	 * Deactivate an account (for status active or status reconcilable)
	 *
	 * @param int $id Id
	 * @param int $mode 0=field active, 1=field reconcilable
	 * @return int              <0 if KO, >0 if OK
	 */
	public function accountDeactivate($id, $mode = 0)
	{
		$result = $this->checkUsage();

		$fieldtouse = 'active';
		if ($mode == 1) {
			$fieldtouse = 'reconcilable';
		}

		if ($result > 0) {
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."accounting_account ";
			$sql .= "SET ".$fieldtouse." = '0'";
			$sql .= " WHERE rowid = ".((int) $id);

			dol_syslog(get_class($this)."::accountDeactivate ".$fieldtouse, LOG_DEBUG);
			$result = $this->db->query($sql);

			if ($result) {
				$this->db->commit();
				return 1;
			} else {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return -1;
			}
		} else {
			return -1;
		}
	}


	/**
	 * Account activated
	 *
	 * @param int $id Id
	 * @param int $mode 0=field active, 1=field reconcilable
	 * @return int              <0 if KO, >0 if OK
	 */
	public function accountActivate($id, $mode = 0)
	{
		// phpcs:enable
		$this->db->begin();

		$fieldtouse = 'active';
		if ($mode == 1) {
			$fieldtouse = 'reconcilable';
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."accounting_account";
		$sql .= " SET ".$fieldtouse." = '1'";
		$sql .= " WHERE rowid = ".((int) $id);

		dol_syslog(get_class($this)."::account_activate ".$fieldtouse, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Retourne le libelle du statut d'un user (actif, inactif)
	 *
	 * @param int $mode 0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * @return string              Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 * @param int $status Id status
	 * @param int $mode 0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * @return string              Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$langs->load("users");
			$this->labelStatus[self::STATUS_ENABLED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatus[self::STATUS_DISABLED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatusShort[self::STATUS_ENABLED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_DISABLED] = $langs->transnoentitiesnoconv('Disabled');
		}

		$statusType = 'status4';
		if ($status == self::STATUS_DISABLED) {
			$statusType = 'status5';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 * Return Suggest accounting accounts to bind
	 *
	 * @param 	Societe 							$buyer 				Object buyer
	 * @param 	Societe 							$seller 			Object seller
	 * @param 	Product 							$product 			Product object sell or buy
	 * @param 	Facture|FactureFournisseur 			$facture 			Facture
	 * @param 	FactureLigne|SupplierInvoiceLine	$factureDet 		Facture Det
	 * @param 	array 								$accountingAccount 	Array of Account account
	 * @param 	string 								$type 				Customer / Supplier
	 * @return	array       											Accounting accounts suggested
	 */
	public function getAccountingCodeToBind(Societe $buyer, Societe $seller, Product $product, $facture, $factureDet, $accountingAccount = array(), $type = '')
	{
		global $conf;
		global $hookmanager;

		// Instantiate hooks for external modules
		$hookmanager->initHooks(array('accoutancyBindingCalculation'));

		// Execute hook accoutancyBindingCalculation
		$parameters = array('buyer' => $buyer, 'seller' => $seller, 'product' => $product, 'facture' => $facture, 'factureDet' => $factureDet ,'accountingAccount'=>$accountingAccount, $type);
		$reshook = $hookmanager->executeHooks('accoutancyBindingCalculation', $parameters); // Note that $action and $object may have been modified by some hooks

		if (empty($reshook)) {
			if ($type == 'customer') {
				$const_name = "SOLD";
			} elseif ($type == 'supplier') {
				$const_name = "BUY";
			}

			require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
			$isBuyerInEEC = isInEEC($buyer);
			$isSellerInEEC = isInEEC($seller);
			$code_l = '';	// Default value for generic product/service
			$code_p = '';	// Value for the product/service in parameter ($product)
			$code_t = '';	// Default value of product account for the thirdparty
			$suggestedid = '';

			// Level 1 (define $code_l): Search suggested default account for product/service
			$suggestedaccountingaccountbydefaultfor = '';
			if ($factureDet->product_type == 1) {
				if ($buyer->country_code == $seller->country_code || empty($buyer->country_code)) {  // If buyer in same country than seller (if not defined, we assume it is same country)
					$code_l = (!empty($conf->global->{'ACCOUNTING_SERVICE_' . $const_name . '_ACCOUNT'}) ? $conf->global->{'ACCOUNTING_SERVICE_' . $const_name . '_ACCOUNT'} : '');
					$suggestedaccountingaccountbydefaultfor = '';
				} else {
					if ($isSellerInEEC && $isBuyerInEEC && $factureDet->tva_tx != 0) {    // European intravat sale, but with a VAT
						$code_l = (!empty($conf->global->{'ACCOUNTING_SERVICE_' . $const_name . '_ACCOUNT'}) ? $conf->global->{'ACCOUNTING_SERVICE_' . $const_name . '_ACCOUNT'} : '');
						$suggestedaccountingaccountbydefaultfor = 'eecwithvat';
					} elseif ($isSellerInEEC && $isBuyerInEEC && empty($buyer->tva_intra)) {    // European intravat sale, without VAT intra community number
						$code_l = (!empty($conf->global->{'ACCOUNTING_SERVICE_' . $const_name . '_ACCOUNT'}) ? $conf->global->{'ACCOUNTING_SERVICE_' . $const_name . '_ACCOUNT'} : '');
						$suggestedaccountingaccountbydefaultfor = 'eecwithoutvatnumber';
					} elseif ($isSellerInEEC && $isBuyerInEEC) {    // European intravat sale
						$code_l = (!empty($conf->global->{'ACCOUNTING_SERVICE_' . $const_name . '_INTRA_ACCOUNT'}) ? $conf->global->{'ACCOUNTING_SERVICE_' . $const_name . '_INTRA_ACCOUNT'} : '');
						$suggestedaccountingaccountbydefaultfor = 'eec';
					} else {                                        // Foreign sale
						$code_l = (!empty($conf->global->{'ACCOUNTING_SERVICE_' . $const_name . '_EXPORT_ACCOUNT'}) ? $conf->global->{'ACCOUNTING_SERVICE_' . $const_name . '_EXPORT_ACCOUNT'} : '');
						$suggestedaccountingaccountbydefaultfor = 'export';
					}
				}
			} elseif ($factureDet->product_type == 0) {
				if ($buyer->country_code == $seller->country_code || empty($buyer->country_code)) {  // If buyer in same country than seller (if not defined, we assume it is same country)
					$code_l = (!empty($conf->global->{'ACCOUNTING_PRODUCT_' . $const_name . '_ACCOUNT'}) ? $conf->global->{'ACCOUNTING_PRODUCT_' . $const_name . '_ACCOUNT'} : '');
					$suggestedaccountingaccountbydefaultfor = '';
				} else {
					if ($isSellerInEEC && $isBuyerInEEC && $factureDet->tva_tx != 0) {    // European intravat sale, but with a VAT
						$code_l = (!empty($conf->global->{'ACCOUNTING_PRODUCT_' . $const_name . '_ACCOUNT'}) ? $conf->global->{'ACCOUNTING_PRODUCT_' . $const_name . '_ACCOUNT'} : '');
						$suggestedaccountingaccountbydefaultfor = 'eecwithvat';
					} elseif ($isSellerInEEC && $isBuyerInEEC && empty($buyer->tva_intra)) {    // European intravat sale, without VAT intra community number
						$code_l = (!empty($conf->global->{'ACCOUNTING_PRODUCT_' . $const_name . '_ACCOUNT'}) ? $conf->global->{'ACCOUNTING_PRODUCT_' . $const_name . '_ACCOUNT'} : '');
						$suggestedaccountingaccountbydefaultfor = 'eecwithoutvatnumber';
					} elseif ($isSellerInEEC && $isBuyerInEEC) {    // European intravat sale
						$code_l = (!empty($conf->global->{'ACCOUNTING_PRODUCT_' . $const_name . '_INTRA_ACCOUNT'}) ? $conf->global->{'ACCOUNTING_PRODUCT_' . $const_name . '_INTRA_ACCOUNT'} : '');
						$suggestedaccountingaccountbydefaultfor = 'eec';
					} else {
						$code_l = (!empty($conf->global->{'ACCOUNTING_PRODUCT_' . $const_name . '_EXPORT_ACCOUNT'}) ? $conf->global->{'ACCOUNTING_PRODUCT_' . $const_name . '_EXPORT_ACCOUNT'} : '');
						$suggestedaccountingaccountbydefaultfor = 'export';
					}
				}
			}
			if ($code_l == -1) {
				$code_l = '';
			}

			// Level 2 (define $code_p): Search suggested account for product/service (similar code exists in page index.php to make automatic binding)
			$suggestedaccountingaccountfor = '';
			if ((($buyer->country_code == $seller->country_code) || empty($buyer->country_code))) {
				// If buyer in same country than seller (if not defined, we assume it is same country)
				if ($type=='customer' && !empty($product->accountancy_code_sell)) {
					$code_p = $product->accountancy_code_sell;
				} elseif ($type=='supplier' && !empty($product->accountancy_code_buy)) {
					$code_p = $product->accountancy_code_buy;
				}
				$suggestedid = $accountingAccount['dom'];
				$suggestedaccountingaccountfor = 'prodserv';
			} else {
				if ($isSellerInEEC && $isBuyerInEEC && $factureDet->tva_tx != 0) {
					// European intravat sale, but with VAT
					if ($type=='customer' && !empty($product->accountancy_code_sell)) {
						$code_p = $product->accountancy_code_sell;
					} elseif ($type=='supplier' && !empty($product->accountancy_code_buy)) {
						$code_p = $product->accountancy_code_buy;
					}
					$suggestedid = $accountingAccount['dom'];
					$suggestedaccountingaccountfor = 'eecwithvat';
				} elseif ($isSellerInEEC && $isBuyerInEEC && empty($buyer->tva_intra)) {
					// European intravat sale, without VAT intra community number
					if ($type=='customer' && !empty($product->accountancy_code_sell)) {
						$code_p = $product->accountancy_code_sell;
					} elseif ($type=='supplier' && !empty($product->accountancy_code_buy)) {
						$code_p = $product->accountancy_code_buy;
					}
					$suggestedid = $accountingAccount['dom']; // There is a doubt for this case. Is it an error on vat or we just forgot to fill vat number ?
					$suggestedaccountingaccountfor = 'eecwithoutvatnumber';
				} elseif ($isSellerInEEC && $isBuyerInEEC && !empty($product->accountancy_code_sell_intra)) {
					// European intravat sale
					if ($type=='customer' && !empty($product->accountancy_code_sell_intra)) {
						$code_p = $product->accountancy_code_sell_intra;
					} elseif ($type=='supplier' && !empty($product->accountancy_code_buy_intra)) {
						$code_p = $product->accountancy_code_buy_intra;
					}
					$suggestedid = $accountingAccount['intra'];
					$suggestedaccountingaccountfor = 'eec';
				} else {
					// Foreign sale
					if ($type=='customer' && !empty($product->accountancy_code_sell_export)) {
						$code_p = $product->accountancy_code_sell_export;
					} elseif ($type=='supplier' && !empty($product->accountancy_code_buy_export)) {
						$code_p = $product->accountancy_code_buy_export;
					}
					$suggestedid = $accountingAccount['export'];
					$suggestedaccountingaccountfor = 'export';
				}
			}

			// Level 3 (define $code_t): Search suggested account for this thirdparty (similar code exists in page index.php to make automatic binding)
			if (!empty($conf->global->ACCOUNTANCY_USE_PRODUCT_ACCOUNT_ON_THIRDPARTY)) {
				if (!empty($buyer->code_compta_product)) {
					$code_t = $buyer->code_compta_product;
					$suggestedid = $accountingAccount['thirdparty'];
					$suggestedaccountingaccountfor = 'thridparty';
				}
			}

			// Manage Deposit
			if ($factureDet->desc == "(DEPOSIT)" || $facture->type == $facture::TYPE_DEPOSIT) {
				$accountdeposittoventilated = new self($this->db);
				$result = $accountdeposittoventilated->fetch('', $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT, 1);
				if ($result < 0) {
					return -1;
				}

				$code_l = $accountdeposittoventilated->ref;
				$suggestedid = $accountdeposittoventilated->rowid;
				$suggestedaccountingaccountfor = 'deposit';
			}

			// If $suggestedid could not be guessed yet, we set it from the generic default accounting code $code_l
			if (empty($suggestedid) && empty($code_p) && !empty($code_l) && empty($conf->global->ACCOUNTANCY_DO_NOT_AUTOFILL_ACCOUNT_WITH_GENERIC)) {
				if (empty($this->accountingaccount_codetotid_cache[$code_l])) {
					$tmpaccount = new self($this->db);
					$result = $tmpaccount->fetch(0, $code_l, 1);
					if ($result < 0) {
						return -1;
					}
					if ($tmpaccount->id > 0) {
						$suggestedid = $tmpaccount->id;
					}
					$this->accountingaccount_codetotid_cache[$code_l] = $tmpaccount->id;
				} else {
					$suggestedid = $this->accountingaccount_codetotid_cache[$code_l];
				}
			}
			return array(
				'suggestedaccountingaccountbydefaultfor' => $suggestedaccountingaccountbydefaultfor,
				'suggestedaccountingaccountfor' => $suggestedaccountingaccountfor,
				'suggestedid' => $suggestedid,
				'code_l' => $code_l,
				'code_p' => $code_p,
				'code_t' => $code_t,
			);
		} else {
			if (is_array($hookmanager->resArray) && !empty($hookmanager->resArray)) {
				return $hookmanager->resArray;
			}
		}
	}
}
