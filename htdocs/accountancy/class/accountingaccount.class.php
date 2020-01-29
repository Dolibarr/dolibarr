<?php
/* Copyright (C) 2013-2014  Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2016  Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2013-2014  Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2014       Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015       Ari Elbaz (elarifr)  <github@accedinfo.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *  \file       htdocs/accountancy/class/accountingaccount.class.php
 *  \ingroup    Accountancy (Double entries)
 *  \brief      File of class to manage accounting accounts
 */

/**
 * Class to manage accounting accounts
 */
class AccountingAccount extends CommonObject
{
	/**
	 * @var string Name of element
	 */
	public $element='accounting_account';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='accounting_account';

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
     * @var string pcg subtype
     */
	public $pcg_subtype;

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
	 * @var int Status
	 */
	public $status;

    /**
     * @var string Label of account
     */
    public $label;

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
	 * Constructor
	 *
	 * @param DoliDB $db Database handle
	 */
    public function __construct($db)
    {
		global $conf;

		$this->db = $db;
		$this->next_prev_filter='fk_pcg_version IN (SELECT pcg_version FROM ' . MAIN_DB_PREFIX . 'accounting_system WHERE rowid=' . $conf->global->CHARTOFACCOUNTS . ')';		// Used to add a filter in Form::showrefnav method
    }

	/**
	 * Load record in memory
	 *
	 * @param 	int 	       $rowid 				    Id
	 * @param 	string 	       $account_number 	        Account number
	 * @param 	int|boolean    $limittocurrentchart     1 or true=Load record only if it is into current active char of account
	 * @param   string         $limittoachartaccount    'ABC'=Load record only if it is into chart account with code 'ABC' (better and faster than previous parameter if you have chart of account code).
	 * @return 	int                                     <0 if KO, 0 if not found, Id of record if OK and found
	 */
    public function fetch($rowid = null, $account_number = null, $limittocurrentchart = 0, $limittoachartaccount = '')
	{
		global $conf;

		if ($rowid || $account_number) {
			$sql  = "SELECT a.rowid as rowid, a.datec, a.tms, a.fk_pcg_version, a.pcg_type, a.pcg_subtype, a.account_number, a.account_parent, a.label, a.fk_accounting_category, a.fk_user_author, a.fk_user_modif, a.active";
			$sql .= ", ca.label as category_label";
			$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as a";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_accounting_category as ca ON a.fk_accounting_category = ca.rowid";
			$sql .= " WHERE";
			if ($rowid) {
				$sql .= " a.rowid = " . (int) $rowid;
			} elseif ($account_number) {
				$sql .= " a.account_number = '" . $this->db->escape($account_number) . "'";
				$sql .= " AND a.entity = ".$conf->entity;
			}
			if (! empty($limittocurrentchart)) {
				$sql .= ' AND a.fk_pcg_version IN (SELECT pcg_version FROM ' . MAIN_DB_PREFIX . 'accounting_system WHERE rowid=' . $this->db->escape($conf->global->CHARTOFACCOUNTS) . ')';
			}
			if (! empty($limittoachartaccount)) {
			    $sql .= " AND a.fk_pcg_version = '".$this->db->escape($limittoachartaccount)."'";
			}

			dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
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
					$this->pcg_subtype = $obj->pcg_subtype;
					$this->account_number = $obj->account_number;
					$this->account_parent = $obj->account_parent;
					$this->label = $obj->label;
					$this->account_category = $obj->fk_accounting_category;
					$this->account_category_label = $obj->category_label;
					$this->fk_user_author = $obj->fk_user_author;
					$this->fk_user_modif = $obj->fk_user_modif;
					$this->active = $obj->active;
					$this->status = $obj->active;

					return $this->id;
				} else {
					return 0;
				}
			} else {
				$this->error = "Error " . $this->db->lasterror();
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}
		return -1;
	}

	/**
	 * Insert new accounting account in chart of accounts
	 *
	 * @param  User    $user       User making action
	 * @param  int     $notrigger  Disable triggers
	 * @return int                 <0 if KO, >0 if OK
	 */
    public function create($user, $notrigger = 0)
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
		if (isset($this->label))
			$this->label = trim($this->label);

		if (empty($this->pcg_type) || $this->pcg_type == '-1')
		{
			$this->pcg_type = 'XXXXXX';
		}
		if (empty($this->pcg_subtype) || $this->pcg_subtype == '-1')
		{
			$this->pcg_subtype = 'XXXXXX';
		}
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
		$sql .= ", fk_accounting_category";
		$sql .= ", fk_user_author";
		$sql .= ", active";
		$sql .= ") VALUES (";
		$sql .= " '" . $this->db->idate($now) . "'";
		$sql .= ", " . $conf->entity;
		$sql .= ", " . (empty($this->fk_pcg_version) ? 'NULL' : "'" . $this->db->escape($this->fk_pcg_version) . "'");
		$sql .= ", " . (empty($this->pcg_type) ? 'NULL' : "'" . $this->db->escape($this->pcg_type) . "'");
		$sql .= ", " . (empty($this->pcg_subtype) ? 'NULL' : "'" . $this->db->escape($this->pcg_subtype) . "'");
		$sql .= ", " . (empty($this->account_number) ? 'NULL' : "'" . $this->db->escape($this->account_number) . "'");
		$sql .= ", " . (empty($this->account_parent) ? 0 : (int) $this->account_parent);
		$sql .= ", " . (empty($this->label) ? "''" : "'" . $this->db->escape($this->label) . "'");
		$sql .= ", " . (empty($this->account_category) ? 0 : (int) $this->account_category);
		$sql .= ", " . $user->id;
		$sql .= ", " . (int) $this->active;
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "accounting_account");

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
	 * @param  User $user      Use making update
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update($user)
	{
		// Check parameters
		if (empty($this->pcg_type) || $this->pcg_type == '-1')
		{
			$this->pcg_type = 'XXXXXX';
		}
		if (empty($this->pcg_subtype) || $this->pcg_subtype == '-1')
		{
			$this->pcg_subtype = 'XXXXXX';
		}

		$this->db->begin();

		$sql = "UPDATE " . MAIN_DB_PREFIX . "accounting_account ";
		$sql .= " SET fk_pcg_version = " . ($this->fk_pcg_version ? "'" . $this->db->escape($this->fk_pcg_version) . "'" : "null");
		$sql .= " , pcg_type = " . ($this->pcg_type ? "'" . $this->db->escape($this->pcg_type) . "'" : "null");
		$sql .= " , pcg_subtype = " . ($this->pcg_subtype ? "'" . $this->db->escape($this->pcg_subtype) . "'" : "null");
		$sql .= " , account_number = '" . $this->db->escape($this->account_number) . "'";
		$sql .= " , account_parent = " . (int) $this->account_parent;
		$sql .= " , label = " . ($this->label ? "'" . $this->db->escape($this->label) . "'" : "''");
		$sql .= " , fk_accounting_category = " . (empty($this->account_category) ? 0 : (int) $this->account_category);
		$sql .= " , fk_user_modif = " . $user->id;
		$sql .= " , active = " . (int) $this->active;
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
	 * @return int <0 if KO, >0 if OK
	 */
    public function checkUsage()
    {
		global $langs;

		$sql = "(SELECT fk_code_ventilation FROM " . MAIN_DB_PREFIX . "facturedet";
		$sql.= " WHERE fk_code_ventilation=" . $this->id . ")";
		$sql.= "UNION";
		$sql.= " (SELECT fk_code_ventilation FROM " . MAIN_DB_PREFIX . "facture_fourn_det";
		$sql.= " WHERE fk_code_ventilation=" . $this->id . ")";

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
				foreach ($this->errors as $errmsg) {
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
	 * @param	int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
	 * @param	int		$withlabel					0=No label, 1=Include label of account
	 * @param	int  	$nourl						1=Disable url
	 * @param	string  $moretitle					Add more text to title tooltip
	 * @param	int  	$notooltip					1=Disable tooltip
     * @param	int     $save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @return  string	String with URL
	 */
    public function getNomUrl($withpicto = 0, $withlabel = 0, $nourl = 0, $moretitle = '', $notooltip = 0, $save_lastsearch_value = -1)
    {
		global $langs, $conf, $user;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';

		if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

		$result = '';

		$url = DOL_URL_ROOT . '/accountancy/admin/card.php?id=' . $this->id;

		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
		if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';

		$picto = 'billr';
		$label='';

		$label = '<u>' . $langs->trans("ShowAccountingAccount") . '</u>';
		if (! empty($this->account_number))
			$label .= '<br><b>'.$langs->trans('AccountAccounting') . ':</b> ' . length_accountg($this->account_number);
		if (! empty($this->label))
			$label .= '<br><b>'.$langs->trans('Label') . ':</b> ' . $this->label;
		if ($moretitle) $label.=' - '.$moretitle;

		$linkclose='';
		if (empty($notooltip))
		{
			if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label=$langs->trans("ShowAccoutingAccount");
				$linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose.=' class="classfortooltip"';
		}

		$linkstart='<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		if ($nourl)
		{
			$linkstart = '';
			$linkclose = '';
			$linkend = '';
		}

		$label_link = length_accountg($this->account_number);
		if ($withlabel) $label_link .= ' - ' . $this->label;

		if ($withpicto) $result.=($linkstart.img_object(($notooltip?'':$label), $picto, ($notooltip?'':'class="classfortooltip"'), 0, 0, $notooltip?0:1).$linkend);
		if ($withpicto && $withpicto != 2) $result .= ' ';
		if ($withpicto != 2) $result.=$linkstart . $label_link . $linkend;
		return $result;
    }

	/**
	 * Information on record
	 *
	 * @param int $id of record
	 * @return void
	 */
    public function info($id)
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Account deactivated
	 *
	 * @param  int  $id         Id
	 * @return int              <0 if KO, >0 if OK
	 */
    public function account_desactivate($id)
    {
        // phpcs:enable
		$result = $this->checkUsage();

		if ($result > 0) {
			$this->db->begin();

			$sql = "UPDATE " . MAIN_DB_PREFIX . "accounting_account ";
			$sql .= "SET active = '0'";
			$sql .= " WHERE rowid = " . $this->db->escape($id);

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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Account activated
	 *
	 * @param  int  $id         Id
	 * @return int              <0 if KO, >0 if OK
	 */
    public function account_activate($id)
    {
        // phpcs:enable
		$this->db->begin();

		$sql = "UPDATE " . MAIN_DB_PREFIX . "accounting_account ";
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
	 *  @param  int     $mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string              Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param  int     $statut     Id statut
	 *  @param  int     $mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string              Label of status
	 */
	public function LibStatut($statut, $mode = 0)
	{
        // phpcs:enable
		global $langs;
		$langs->loadLangs(array("users"));

		if ($mode == 0)
		{
			$prefix='';
			if ($statut == 1) return $langs->trans('Enabled');
			elseif ($statut == 0) return $langs->trans('Disabled');
		}
		elseif ($mode == 1)
		{
			if ($statut == 1) return $langs->trans('Enabled');
			elseif ($statut == 0) return $langs->trans('Disabled');
		}
		elseif ($mode == 2)
		{
			if ($statut == 1) return img_picto($langs->trans('Enabled'), 'statut4').' '.$langs->trans('Enabled');
			elseif ($statut == 0) return img_picto($langs->trans('Disabled'), 'statut5').' '.$langs->trans('Disabled');
		}
		elseif ($mode == 3)
		{
			if ($statut == 1) return img_picto($langs->trans('Enabled'), 'statut4');
			elseif ($statut == 0) return img_picto($langs->trans('Disabled'), 'statut5');
		}
		elseif ($mode == 4)
		{
			if ($statut == 1) return img_picto($langs->trans('Enabled'), 'statut4').' '.$langs->trans('Enabled');
			elseif ($statut == 0) return img_picto($langs->trans('Disabled'), 'statut5').' '.$langs->trans('Disabled');
		}
		elseif ($mode == 5)
		{
			if ($statut == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'), 'statut4');
			elseif ($statut == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'), 'statut5');
		}
	}
}
