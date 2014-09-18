<?php
/* Copyright (C) 2013-2014 Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
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
 *	\file		htdocs/accountancy/class/html.formventilation.class.php
 *	\ingroup	Accounting Expert
 *	\brief		File of class with all html predefined components
 */

/**
 *	Class to manage generation of HTML components for bank module
 */
class FormVentilation extends Form
{
	var $db;
	var $error;

	/**
     * Constructor
     *
     * @param		DoliDB		$db      Database handler
     */
	public function __construct($db)
    {
        $this->db = $db;
    }

	/**
	 *	Return select filter with date of transaction
	 *
	 *	@param	string	$htmlname		Name of select field
	 *	@param	string	$selectedkey	Value
	 *	@return	string					HTML edit field
	 */
	function select_bookkeeping_importkey($htmlname = 'importkey', $selectedkey='')
	{
		global $langs;

		$date_array = array ();

		$sql  = 'SELECT DISTINCT import_key from ' . MAIN_DB_PREFIX . 'accounting_bookkeeping';
		$sql .= ' ORDER BY import_key DESC';

		$out = '<SELECT name="' . $htmlname . '">';

		dol_syslog(get_class($this) . "::select_bookkeeping_importkey sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $this->db->num_rows($resql);

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$selected = '';
				if ($selectedkey == $obj->import_key) {
					$selected = ' selected="selected" ';
				}

				$out .= '<OPTION value="' . $obj->import_key . '"' . $selected . '>' . $obj->import_key . '</OPTION>';

				$i ++;
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::select_bookkeeping_importkey " . $this->error, LOG_ERR);
			return - 1;
		}

		$out .= '</SELECT>';

		return $out;
	}

	/**
	 *	Return list of accounts with label by chart of accounts
	 *
	 *	@param	string	$selectid		Preselected chart of accounts
	 *	@param	string	$htmlname		Name of field in html form
	 *	@param	int		$showempty		Add an empty field
	 *  @param	array	$event			Event options
     *
	 *	@return	string					String with HTML select
	 */
	function select_account($selectid, $htmlname = 'account', $showempty = 0, $event = array())
	{
		global $conf, $user, $langs;

		$out = '';

		$sql = "SELECT DISTINCT aa.account_number, aa.label, aa.rowid, aa.fk_pcg_version";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accountingaccount as aa";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
		$sql .= " AND asy.rowid = " . $conf->global->CHARTOFACCOUNTS;
		$sql .= " AND aa.active = 1";
		$sql .= " ORDER BY aa.account_number";

		dol_syslog(get_class($this) . "::select_account sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {

			$out .= ajax_combobox($htmlname, $event);
			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty)
				$out .= '<option value="-1"></option>';
			$num = $this->db->num_rows($resql);

			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);
					$label = $obj->account_number . ' - ' . $obj->label;

					// Remember guy's we store in database llx_facturedet the rowid of accountingaccount and not the account_number
					// Bacause same account_number can be share between different accounting_system and do have the same meaning
					if (($selectid != '') && $selectid == $obj->rowid) {
						// $out .= '<option value="' . $obj->account_number . '" selected="selected">' . $label . '</option>';
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						// $out .= '<option value="' . $obj->account_number . '">' . $label . '</option>';
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::select_account " . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);
		return $out;
	}

	/**
	 *	Return list of accounts with label by class of accounts
	 *
	 *	@param	string	$selectid		Preselected pcg_type
	 *	@param	string	$htmlname		Name of field in html form
	 *	@param	int		$showempty		Add an empty field
	 *  @param	array	$event			Event options
     *
	 *	@return	string					String with HTML select
	 */
	function select_pcgtype($selectid, $htmlname = 'pcg_type', $showempty = 0, $event = array())
	{
		global $conf, $user, $langs;

		$out = '';

		$sql = "SELECT DISTINCT pcg_type ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accountingaccount as aa";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
		$sql .= " AND asy.rowid = " . $conf->global->CHARTOFACCOUNTS;
		$sql .= " ORDER BY pcg_type";

		dol_syslog(get_class($this) . "::select_pcgtype sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {

			$out .= ajax_combobox($htmlname, $event);

			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty)
				$out .= '<option value="-1"></option>';
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);
					$label = $obj->pcg_type;

					if (($selectid != '') && $selectid == $obj->pcg_type) {
						$out .= '<option value="' . $obj->pcg_type . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->pcg_type . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::select_pcgtype " . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);
		return $out;
	}

	/**
	 *	Return list of accounts with label by sub_class of accounts
	 *
	 *	@param	string	$selectid		Preselected pcg_type
	 *	@param	string	$htmlname		Name of field in html form
	 *	@param	int		$showempty		Add an empty field
	 *  @param	array	$event			Event options
     *
	 *	@return	string					String with HTML select
	 */
	function select_pcgsubtype($selectid, $htmlname = 'pcg_subtype', $showempty = 0, $event = array())
	{
		global $conf, $user, $langs;

		$out = '';

		$sql = "SELECT DISTINCT pcg_subtype ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accountingaccount as aa";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
		$sql .= " AND asy.rowid = " . $conf->global->CHARTOFACCOUNTS;
		$sql .= " ORDER BY pcg_subtype";

		dol_syslog(get_class($this) . "::select_pcgsubtype sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {

			$out .= ajax_combobox($htmlname, $event);

			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty)
				$out .= '<option value="-1"></option>';
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);
					$label = $obj->pcg_subtype;

					if (($selectid != '') && $selectid == $obj->pcg_subtype) {
						$out .= '<option value="' . $obj->pcg_subtype . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->pcg_subtype . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::select_pcgsubtype " . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);
		return $out;
	}
}
