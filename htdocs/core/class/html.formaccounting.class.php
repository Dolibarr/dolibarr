<?php
/* Copyright (C) 2013-2016 Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2015      Ari Elbaz (elarifr)  <github@accedinfo.com>
 * Copyright (C) 2016      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2016-2020 Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
 *	\file       htdocs/core/class/html.formaccounting.class.php
 *  \ingroup    Accountancy (Double entries)
 *	\brief      File of class with all html predefined components
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';


/**
 *	Class to manage generation of HTML components for accounting management
 */
class FormAccounting extends Form
{

	private $options_cache = array();

	/**
     * @var DoliDB Database handler.
     */
    public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

    /**
	 * Constructor
	 *
	 * @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
	    $this->db = $db;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return list of journals with label by nature
	 *
	 * @param	string	$selectid	Preselected pcg_type
	 * @param	string	$htmlname	Name of field in html form
	 * @param	int		$nature		Limit the list to a particular type of journals (1:various operations / 2:sale / 3:purchase / 4:bank / 9: has-new)
	 * @param	int		$showempty	Add an empty field
	 * @param	int		$select_in	0=selectid value is the journal rowid (default) or 1=selectid is journal code
	 * @param	int		$select_out	Set value returned by select. 0=rowid (default), 1=code
	 * @param	string	$morecss	More css non HTML object
	 * @param	string	$usecache	Key to use to store result into a cache. Next call with same key will reuse the cache.
	 * @param   int     $disabledajaxcombo Disable ajax combo box.
	 * @return	string				String with HTML select
	 */
	public function select_journal($selectid, $htmlname = 'journal', $nature = 0, $showempty = 0, $select_in = 0, $select_out = 0, $morecss = 'maxwidth300 maxwidthonsmartphone', $usecache = '', $disabledajaxcombo = 0)
	{
        // phpcs:enable
		global $conf, $langs;

		$out = '';

    	$options = array();
		if ($usecache && !empty($this->options_cache[$usecache]))
		{
		    $options = $this->options_cache[$usecache];
		    $selected = $selectid;
		}
		else
		{
			$sql = "SELECT rowid, code, label, nature, entity, active";
			$sql .= " FROM ".MAIN_DB_PREFIX."accounting_journal";
			$sql .= " WHERE active = 1";
			$sql .= " AND entity = ".$conf->entity;
			if ($nature && is_numeric($nature))   $sql .= " AND nature = ".$nature;
			$sql .= " ORDER BY code";

			dol_syslog(get_class($this)."::select_journal", LOG_DEBUG);
			$resql = $this->db->query($sql);

			if (!$resql) {
				$this->error = "Error ".$this->db->lasterror();
				dol_syslog(get_class($this)."::select_journal ".$this->error, LOG_ERR);
				return -1;
			}

    		$selected = 0;
			$langs->load('accountancy');
			while ($obj = $this->db->fetch_object($resql))
			{
				$label = $obj->code.' - '.$langs->trans($obj->label);

    			$select_value_in = $obj->rowid;
				$select_value_out = $obj->rowid;

				// Try to guess if we have found default value
    			if ($select_in == 1) {
    				$select_value_in = $obj->code;
    			}
    			if ($select_out == 1) {
    				$select_value_out = $obj->code;
    			}
    			// Remember guy's we store in database llx_accounting_bookkeeping the code of accounting_journal and not the rowid
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

		$out .= Form::selectarray($htmlname, $options, $selected, $showempty, 0, 0, '', 0, 0, 0, '', $morecss, ($disabledajaxcombo ? 0 : 1));

		return $out;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *	Return list of accounting category.
     * 	Use mysoc->country_id or mysoc->country_code so they must be defined.
     *
     *	@param	string	$selected       Preselected type
     *	@param  string	$htmlname       Name of field in form
     * 	@param	int		$useempty		Set to 1 if we want an empty value
     * 	@param	int		$maxlen			Max length of text in combo box
     * 	@param	int		$help			Add or not the admin help picto
     *  @param  int     $allcountries   All countries
     * 	@return	void
     */
    public function select_accounting_category($selected = '', $htmlname = 'account_category', $useempty = 0, $maxlen = 0, $help = 1, $allcountries = 0)
    {
        // phpcs:enable
        global $db, $langs, $user, $mysoc;

        if (empty($mysoc->country_id) && empty($mysoc->country_code) && empty($allcountries))
        {
            dol_print_error('', 'Call to select_accounting_account with mysoc country not yet defined');
            exit;
        }

        if (!empty($mysoc->country_id))
        {
            $sql = "SELECT c.rowid, c.label as type, c.range_account";
            $sql .= " FROM ".MAIN_DB_PREFIX."c_accounting_category as c";
            $sql .= " WHERE c.active = 1";
			$sql .= " AND c.category_type = 0";
            if (empty($allcountries)) $sql .= " AND c.fk_country = ".$mysoc->country_id;
            $sql .= " ORDER BY c.label ASC";
        }
        else
        {
            $sql = "SELECT c.rowid, c.label as type, c.range_account";
            $sql .= " FROM ".MAIN_DB_PREFIX."c_accounting_category as c, ".MAIN_DB_PREFIX."c_country as co";
            $sql .= " WHERE c.active = 1";
			$sql .= " AND c.category_type = 0";
			$sql .= " AND c.fk_country = co.rowid";
            if (empty($allcountries)) $sql .= " AND co.code = '".$mysoc->country_code."'";
            $sql .= " ORDER BY c.label ASC";
        }

        dol_syslog(get_class($this).'::'.__METHOD__, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            if ($num)
            {
                $out = '<select class="flat minwidth200" id="'.$htmlname.'" name="'.$htmlname.'">';
                $i = 0;

                if ($useempty) $out .= '<option value="0">&nbsp;</option>';
                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);
                    $out .= '<option value="'.$obj->rowid.'"';
                    if ($obj->rowid == $selected) $out .= ' selected';
                    $out .= '>'.($maxlen ? dol_trunc($obj->type, $maxlen) : $obj->type);
					$out .= ' ('.$obj->range_account.')';
                    $i++;
                }
                $out .= '</select>';
                //if ($user->admin && $help) $out .= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
            }
            else
            {
                $out .= $langs->trans("ErrorNoAccountingCategoryForThisCountry", $mysoc->country_code);
            }
        }
        else
        {
            dol_print_error($db, $db->lasterror());
        }

        $out .= ajax_combobox($htmlname, array());

        print $out;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return select filter with date of transaction
	 *
	 * @param string $htmlname         Name of select field
	 * @param string $selectedkey      Value
	 * @return string                  HTML edit field
	 */
    public function select_bookkeeping_importkey($htmlname = 'importkey', $selectedkey = '')
    {
        // phpcs:enable
		$options = array();

		$sql = 'SELECT DISTINCT import_key from '.MAIN_DB_PREFIX.'accounting_bookkeeping';
	    $sql .= " WHERE entity IN (".getEntity('accountancy').")";
		$sql .= ' ORDER BY import_key DESC';

		dol_syslog(get_class($this)."::select_bookkeeping_importkey", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::select_bookkeeping_importkey ".$this->error, LOG_ERR);
			return -1;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$options[$obj->import_key] = $obj->import_key;
		}

		return Form::selectarray($htmlname, $options, $selectedkey);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return list of accounts with label by chart of accounts
	 *
	 * @param string   $selectid           Preselected id of accounting accounts (depends on $select_in)
	 * @param string   $htmlname           Name of HTML field id. If name start with '.', it is name of HTML css class, so several component with same name in different forms can be used.
	 * @param int      $showempty          1=Add an empty field, 2=Add an empty field+'None' field
	 * @param array    $event              Event options
	 * @param int      $select_in          0=selectid value is a aa.rowid (default) or 1=selectid is aa.account_number
	 * @param int      $select_out         Set value returned by select. 0=rowid (default), 1=account_number
	 * @param string   $morecss            More css non HTML object
	 * @param string   $usecache           Key to use to store result into a cache. Next call with same key will reuse the cache.
	 * @return string                      String with HTML select
	 */
	public function select_account($selectid, $htmlname = 'account', $showempty = 0, $event = array(), $select_in = 0, $select_out = 0, $morecss = 'maxwidth300 maxwidthonsmartphone', $usecache = '')
	{
        // phpcs:enable
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';

		$out = '';

    	$options = array();

    	if ($showempty == 2)
    	{
    		$options['0'] = '--- '.$langs->trans("None").' ---';
    	}

		if ($usecache && !empty($this->options_cache[$usecache]))
		{
		    $options = $options + $this->options_cache[$usecache]; // We use + instead of array_merge because we don't want to reindex key from 0
		    $selected = $selectid;
		}
		else
		{
    		$trunclength = empty($conf->global->ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT) ? 50 : $conf->global->ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT;

    		$sql = "SELECT DISTINCT aa.account_number, aa.label, aa.labelshort, aa.rowid, aa.fk_pcg_version";
    		$sql .= " FROM ".MAIN_DB_PREFIX."accounting_account as aa";
    		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
    		$sql .= " AND asy.rowid = ".$conf->global->CHARTOFACCOUNTS;
    		$sql .= " AND aa.active = 1";
    		$sql .= " AND aa.entity=".$conf->entity;
    		$sql .= " ORDER BY aa.account_number";

    		dol_syslog(get_class($this)."::select_account", LOG_DEBUG);
    		$resql = $this->db->query($sql);

    		if (!$resql) {
    			$this->error = "Error ".$this->db->lasterror();
    			dol_syslog(get_class($this)."::select_account ".$this->error, LOG_ERR);
    			return -1;
    		}

    		$selected = $selectid; // selectid can be -1, 0, 123
    		while ($obj = $this->db->fetch_object($resql))
    		{
				if (empty($obj->labelshort))
				{
					$labeltoshow = $obj->label;
				}
				else
				{
					$labeltoshow = $obj->labelshort;
				}

				$label = length_accountg($obj->account_number).' - '.$labeltoshow;
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
                unset($this->options_cache[$usecache]['0']);
    		}
		}

		$out .= Form::selectarray($htmlname, $options, $selected, ($showempty > 0 ? 1 : 0), 0, 0, '', 0, 0, 0, '', $morecss, 1);

		return $out;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return list of auxilary thirdparty accounts
	 *
	 * @param string   $selectid       Preselected pcg_type
	 * @param string   $htmlname       Name of field in html form
	 * @param int      $showempty      Add an empty field
	 * @param string   $morecss        More css
	 * @return string                  String with HTML select
	 */
    public function select_auxaccount($selectid, $htmlname = 'account_num_aux', $showempty = 0, $morecss = 'maxwidth200')
    {
        // phpcs:enable

		$aux_account = array();

		// Auxiliary customer account
		$sql = "SELECT DISTINCT code_compta, nom ";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe";
	    $sql .= " WHERE entity IN (".getEntity('societe').")";
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
			dol_syslog(get_class($this)."::select_auxaccount ".$this->error, LOG_ERR);
			return -1;
		}
		$this->db->free($resql);

		// Auxiliary supplier account
		$sql = "SELECT DISTINCT code_compta_fournisseur, nom ";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe";
		$sql .= " WHERE entity IN (".getEntity('societe').")";
		$sql .= " ORDER BY code_compta_fournisseur";
		dol_syslog(get_class($this)."::select_auxaccount", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if ($obj->code_compta_fournisseur != "") {
					$aux_account[$obj->code_compta_fournisseur] = $obj->code_compta_fournisseur.' ('.$obj->nom.')';
				}
			}
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::select_auxaccount ".$this->error, LOG_ERR);
			return -1;
		}
		$this->db->free($resql);

        // Auxiliary user account
        $sql = "SELECT DISTINCT accountancy_code, lastname, firstname ";
        $sql .= " FROM ".MAIN_DB_PREFIX."user";
        $sql .= " WHERE entity IN (".getEntity('user').")";
        $sql .= " ORDER BY accountancy_code";
        dol_syslog(get_class($this)."::select_auxaccount", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                if (!empty($obj->accountancy_code)) {
                    $aux_account[$obj->accountancy_code] = $obj->accountancy_code.' ('.dolGetFirstLastname($obj->firstname, $obj->lastname).')';
                }
            }
        } else {
            $this->error = "Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::select_auxaccount ".$this->error, LOG_ERR);
            return -1;
        }
        $this->db->free($resql);

		// Build select
		$out .= Form::selectarray($htmlname, $aux_account, $selectid, $showempty, 0, 0, '', 0, 0, 0, '', $morecss, 1);

		return $out;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return HTML combo list of years existing into book keepping
	 *
	 * @param string 	$selected 		Preselected value
	 * @param string 	$htmlname 		Name of HTML select object
	 * @param int 		$useempty 		Affiche valeur vide dans liste
	 * @param string 	$output_format 	(html/opton (for option html only)/array (to return options arrays
	 * @return string|array				HTML select component or array of select options
	 */
	public function selectyear_accountancy_bookkepping($selected = '', $htmlname = 'yearid', $useempty = 0, $output_format = 'html')
	{
        // phpcs:enable
	    global $conf;

		$out_array = array();

		$sql = "SELECT DISTINCT date_format(doc_date, '%Y') as dtyear";
		$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping";
	    $sql .= " WHERE entity IN (".getEntity('accountancy').")";
		$sql .= " ORDER BY date_format(doc_date, '%Y')";
		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(__METHOD__.$this->error, LOG_ERR);
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
}
