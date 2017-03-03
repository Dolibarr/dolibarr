<?php
/* Copyright (C) 2013-2016 Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2016 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015      Ari Elbaz (elarifr)  <github@accedinfo.com>
 * Copyright (C) 2016      Marcos García        <marcosgdf@gmail.com>
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
 * \file		htdocs/accountancy/class/html.formventilation.class.php
 * \ingroup		Advanced accountancy
 * \brief		File of class with all html predefined components
 */

/**
 * Class to manage generation of HTML components for bank module
 */
class FormVentilation extends Form
{

    private $options_cache = array();


	/**
	 * Return select filter with date of transaction
	 *
	 * @param string $htmlname Name of select field
	 * @param string $selectedkey Value
	 * @return string HTML edit field
	 */
	function select_bookkeeping_importkey($htmlname = 'importkey', $selectedkey = '') {
		$options = array();

		$sql = 'SELECT DISTINCT import_key from ' . MAIN_DB_PREFIX . 'accounting_bookkeeping';
	    $sql .= " WHERE entity IN (" . getEntity("accountancy", 1) . ")";
		$sql .= ' ORDER BY import_key DESC';

		dol_syslog(get_class($this) . "::select_bookkeeping_importkey", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::select_bookkeeping_importkey " . $this->error, LOG_ERR);
			return - 1;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$options[$obj->import_key] = dol_print_date($obj->import_key, 'dayhourtext');
		}

		return Form::selectarray($htmlname, $options, $selectedkey);
	}

	/**
	 * Return list of accounts with label by chart of accounts
	 *
	 * @param string   $selectid           Preselected id or code of accounting accounts (depends on $select_in)
	 * @param string   $htmlname           Name of field in html form
	 * @param int      $showempty          Add an empty field
	 * @param array    $event              Event options
	 * @param int      $select_in          0=selectid value is a aa.rowid (default) or 1=selectid is aa.account_number
	 * @param int      $select_out         Set value returned by select. 0=rowid (default), 1=account_number
	 * @param string   $morecss            More css non HTML object
	 * @param string   $usecache           Key to use to store result into a cache. Next call with same key will reuse the cache.
	 * @return string                      String with HTML select
	 */
	function select_account($selectid, $htmlname = 'account', $showempty = 0, $event = array(), $select_in = 0, $select_out = 0, $morecss='maxwidth300 maxwidthonsmartphone', $usecache='')
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';

    	$options = array();
		if ($usecache && ! empty($this->options_cache[$usecache]))
		{
		    $options = $this->options_cache[$usecache];
		    $selected=$selectid;
		}
		else
		{
    		$trunclength = defined('ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT') ? $conf->global->ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT : 50;

    		$sql = "SELECT DISTINCT aa.account_number, aa.label, aa.rowid, aa.fk_pcg_version";
    		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as aa";
    		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
    		$sql .= " AND asy.rowid = " . $conf->global->CHARTOFACCOUNTS;
    		$sql .= " AND aa.active = 1";
    		$sql .= " ORDER BY aa.account_number";

    		dol_syslog(get_class($this) . "::select_account", LOG_DEBUG);
    		$resql = $this->db->query($sql);

    		if (!$resql) {
    			$this->error = "Error " . $this->db->lasterror();
    			dol_syslog(get_class($this) . "::select_account " . $this->error, LOG_ERR);
    			return -1;
    		}

    		$out = ajax_combobox($htmlname, $event);

    		$selected = 0;
    		while ($obj = $this->db->fetch_object($resql))
    		{
    			$label = length_accountg($obj->account_number) . ' - ' . $obj->label;
    			$label = dol_trunc($label, $trunclength);

    			$select_value_in = $obj->rowid;
    			$select_value_out = $obj->rowid;

    			// Try to guess if we have found default value
    			if ($select_in == 1) {
    				$select_value_in = $obj->account_number;
    			}
    			if ($select_out == 1) {
    				$select_value_out = $obj->account_number;
    			}
    			// Remember guy's we store in database llx_facturedet the rowid of accounting_account and not the account_number
    			// Because same account_number can be share between different accounting_system and do have the same meaning
    			if ($selectid != '' && $selectid == $select_value_in) {
    			    //var_dump("Found ".$selectid." ".$select_value_in);
    				$selected = $select_value_out;
    			}

    			$options[$select_value_out] = $label;
    		}
    		$this->db->free($resql);

    		if ($usecache)
    		{
                $this->options_cache[$usecache] = $options;
    		}
		}

		$out .= Form::selectarray($htmlname, $options, $selected, $showempty, 0, 0, '', 0, 0, 0, '', $morecss, 1);

		return $out;
	}

	/**
	 * Return list of accounts with label by class of accounts
	 *
	 * @param string $selectid Preselected pcg_type
	 * @param string $htmlname Name of field in html form
	 * @param int $showempty Add an empty field
	 * @param array $event Event options
	 *
	 * @return string String with HTML select
	 */
	function select_pcgtype($selectid, $htmlname = 'pcg_type', $showempty = 0, $event = array()) {
		global $conf;

		$sql = "SELECT DISTINCT pcg_type ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as aa";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
		$sql .= " AND asy.rowid = " . $conf->global->CHARTOFACCOUNTS;
		$sql .= " ORDER BY pcg_type";

		dol_syslog(get_class($this) . "::select_pcgtype", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::select_pcgtype ".$this->error, LOG_ERR);
			return -1;
		}

		$options = array();
		$out = ajax_combobox($htmlname, $event);

		while ($obj = $this->db->fetch_object($resql)) 
		{
		    if ($obj->pcg_type != '-1')
		    {
                $options[$obj->pcg_type] = $obj->pcg_type;
		    }
		}

		$out .= Form::selectarray($htmlname, $options, $selectid, $showempty, 0, 0, '', 0, 0, 0, '', 'minwidth200');

		$this->db->free($resql);
		return $out;
	}

	/**
	 * Return list of accounts with label by sub_class of accounts
	 *
	 * @param string $selectid Preselected pcg_type
	 * @param string $htmlname Name of field in html form
	 * @param int $showempty Add an empty field
	 * @param array $event Event options
	 *
	 * @return string String with HTML select
	 */
	function select_pcgsubtype($selectid, $htmlname = 'pcg_subtype', $showempty = 0, $event = array()) 
	{
		global $conf;

		$sql = "SELECT DISTINCT pcg_subtype ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as aa";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
		$sql .= " AND asy.rowid = " . $conf->global->CHARTOFACCOUNTS;
		$sql .= " ORDER BY pcg_subtype";

		dol_syslog(get_class($this) . "::select_pcgsubtype", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::select_pcgsubtype ".$this->error, LOG_ERR);
			return -1;
		}

		$options = array();
		$out = ajax_combobox($htmlname, $event);

		while ($obj = $this->db->fetch_object($resql)) 
		{
		    if ($obj->pcg_type != '-1')
		    {
                $options[$obj->pcg_subtype] = $obj->pcg_subtype;
		    }
		}

		$out .= Form::selectarray($htmlname, $options, $selectid, $showempty, 0, 0, '', 0, 0, 0, '', 'minwidth200');

		$this->db->free($resql);
		return $out;
	}

	/**
	 * Return list of auxilary thirdparty accounts
	 *
	 * @param string $selectid Preselected pcg_type
	 * @param string $htmlname Name of field in html form
	 * @param int $showempty Add an empty field
	 * @param array $event Event options
	 *
	 * @return string String with HTML select
	 */
	function select_auxaccount($selectid, $htmlname = 'account_num_aux', $showempty = 0, $event = array()) {

		$aux_account = array();

		// Auxiliary customer account
		$sql = "SELECT DISTINCT code_compta, nom ";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe";
	    $sql .= " WHERE entity IN (" . getEntity("societe", 1) . ")";
		$sql .= " ORDER BY code_compta";
		dol_syslog(get_class($this)."::select_auxaccount", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if (!empty($obj->code_compta)) {
					$aux_account[$obj->code_compta] = $obj->code_compta.' ('.$obj->nom.')';
				}
			}
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::select_pcgsubtype ".$this->error, LOG_ERR);
			return -1;
		}
		$this->db->free($resql);

		// Auxiliary supplier account
		$sql = "SELECT DISTINCT code_compta_fournisseur, nom ";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe";
	    $sql .= " WHERE entity IN (" . getEntity("societe", 1) . ")";
		$sql .= " ORDER BY code_compta_fournisseur";
		dol_syslog(get_class($this)."::select_auxaccount", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if (!empty($obj->code_compta_fournisseur)) {
					$aux_account[$obj->code_compta_fournisseur] = $obj->code_compta_fournisseur.' ('.$obj->nom.')';
				}
			}
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::select_pcgsubtype ".$this->error, LOG_ERR);
			return -1;
		}
		$this->db->free($resql);

		// Build select
		$out = ajax_combobox($htmlname, $event);
		$out .= Form::selectarray($htmlname, $aux_account, $selectid, $showempty, 0, 0, '', 0, 0, 0, '', 'maxwidth300');

		return $out;
	}

	/**
	 * Return HTML combo list of years existing into book keepping
	 *
	 * @param string $selected Preselected value
	 * @param string $htmlname Name of HTML select object
	 * @param int $useempty Affiche valeur vide dans liste
	 * @param string $output_format (html/opton (for option html only)/array (to return options arrays
	 * @return string/array
	 */
	function selectyear_accountancy_bookkepping($selected = '', $htmlname = 'yearid', $useempty = 0, $output_format = 'html')
	{
	    global $conf;

		$out_array = array();

		$sql = "SELECT DISTINCT date_format(doc_date,'%Y') as dtyear";
		$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping";
	    $sql .= " WHERE entity IN (" . getEntity("accountancy", 1) . ")";
		$sql .= " ORDER BY date_format(doc_date,'%Y')";
		dol_syslog(get_class($this)."::".__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::".__METHOD__.$this->error, LOG_ERR);
			return -1;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$out_array[$obj->dtyear] = $obj->dtyear;
		}
		$this->db->free($resql);

		if ($output_format == 'html') {
			return Form::selectarray($htmlname, $out_array, $selected, $useempty, 0, 0, 'placeholder="aa"');
		} else {
			return $out_array;
		}
	}

	/**
	 * Return HTML combo list of years existing into book keepping
	 *
	 * @param  string          $selected       Preselected value
	 * @param  string          $htmlname       Name of HTML select object
	 * @param  int             $useempty       Affiche valeur vide dans liste
	 * @param  string          $output_format  Html/option (for option html only)/array (to return options arrays
	 * @return string/array
	 */
	function selectjournal_accountancy_bookkepping($selected = '', $htmlname = 'journalid', $useempty = 0, $output_format = 'html')
	{
	    global $conf,$langs;

		$out_array = array();

		$sql = "SELECT DISTINCT code_journal";
		$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping";
	    $sql .= " WHERE entity IN (" . getEntity("accountancy", 1) . ")";
		$sql .= " ORDER BY code_journal";
		dol_syslog(get_class($this)."::".__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::".__METHOD__.$this->error, LOG_ERR);
			return -1;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$out_array[$obj->code_journal] = $obj->code_journal?$obj->code_journal:$langs->trans("NotDefined");  // TODO Not defined is accepted ? We should avoid this, shouldn't we ?
		}
		$this->db->free($resql);

		if ($output_format == 'html') {
			return Form::selectarray($htmlname, $out_array, $selected, $useempty, 0, 0, 'placeholder="aa"');
		} else {
			return $out_array;
		}
	}
}
