<?php
/* Copyright (C) 2013-2016  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2015       Ari Elbaz (elarifr)     <github@accedinfo.com>
 * Copyright (C) 2016       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2016-2024  Alexandre Spangaro      <aspangaro@easya.solutions>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
	/**
	 * @var array<string,array<string,string>>
	 */
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
	 * @var int Nb of accounts found
	 */
	public $nbaccounts;
	/**
	 * @var int Nb of accounts category found
	 */
	public $nbaccounts_category;


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
	 * @param	string		$selectid	Preselected journal code
	 * @param	string		$htmlname	Name of field in html form
	 * @param	int<0,9>	$nature		Limit the list to a particular type of journals (1:various operations / 2:sale / 3:purchase / 4:bank / 9: has-new)
	 * @param	int<0,1>	$showempty	Add an empty field
	 * @param	int<0,1>	$select_in	0=selectid value is the journal rowid (default) or 1=selectid is journal code
	 * @param	int<0,1>	$select_out	Set value returned by select. 0=rowid (default), 1=code
	 * @param	string		$morecss	More css non HTML object
	 * @param	string		$usecache	Key to use to store result into a cache. Next call with same key will reuse the cache.
	 * @param   int<0,1>	$disabledajaxcombo Disable ajax combo box.
	 * @return	string|int				String with HTML select, or -1 if error
	 */
	public function select_journal($selectid, $htmlname = 'journal', $nature = 0, $showempty = 0, $select_in = 0, $select_out = 0, $morecss = 'maxwidth300 maxwidthonsmartphone', $usecache = '', $disabledajaxcombo = 0)
	{
		// phpcs:enable
		global $conf, $langs;

		$out = '';

		$options = array();
		if ($usecache && !empty($this->options_cache[$usecache])) {
			$options = $this->options_cache[$usecache];
			$selected = $selectid;
		} else {
			$sql = "SELECT rowid, code, label, nature, entity, active";
			$sql .= " FROM ".$this->db->prefix()."accounting_journal";
			$sql .= " WHERE active = 1";
			$sql .= " AND entity = ".((int) $conf->entity);
			if ($nature && is_numeric($nature)) {
				$sql .= " AND nature = ".((int) $nature);
			}
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
			while ($obj = $this->db->fetch_object($resql)) {
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

			if ($usecache) {
				$this->options_cache[$usecache] = $options;
			}
		}

		$out .= Form::selectarray($htmlname, $options, $selected, $showempty, 0, 0, '', 0, 0, 0, '', $morecss, ($disabledajaxcombo ? 0 : 1));

		return $out;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return list of journals with label by nature
	 *
	 * @param	string[]	$selectedIds		Preselected journal code array
	 * @param	string		$htmlname			Name of field in html form
	 * @param	int			$nature				Limit the list to a particular type of journals (1:various operations / 2:sale / 3:purchase / 4:bank / 9: has-new)
	 * @param	int			$showempty			Add an empty field
	 * @param	int			$select_in			0=selectid value is the journal rowid (default) or 1=selectid is journal code
	 * @param	int			$select_out			Set value returned by select. 0=rowid (default), 1=code
	 * @param	string		$morecss			More css non HTML object
	 * @param	string		$usecache			Key to use to store result into a cache. Next call with same key will reuse the cache.
	 * @param   int    		$disabledajaxcombo Disable ajax combo box.
	 * @return	string|int<-1,-1>				String with HTML select, or -1 if error
	 */
	public function multi_select_journal($selectedIds = array(), $htmlname = 'journal', $nature = 0, $showempty = 0, $select_in = 0, $select_out = 0, $morecss = '', $usecache = '', $disabledajaxcombo = 0)
	{
		// phpcs:enable
		global $conf, $langs;

		$out = '';

		$options = array();
		if ($usecache && !empty($this->options_cache[$usecache])) {
			$options = $this->options_cache[$usecache];
			$selected = $selectedIds;
		} else {
			$sql = "SELECT rowid, code, label, nature, entity, active";
			$sql .= " FROM ".$this->db->prefix()."accounting_journal";
			$sql .= " WHERE active = 1";
			$sql .= " AND entity = ".$conf->entity;
			if ($nature && is_numeric($nature)) {
				$sql .= " AND nature = ".((int) $nature);
			}
			$sql .= " ORDER BY code";

			dol_syslog(get_class($this)."::multi_select_journal", LOG_DEBUG);
			$resql = $this->db->query($sql);

			if (!$resql) {
				$this->error = "Error ".$this->db->lasterror();
				dol_syslog(get_class($this)."::multi_select_journal ".$this->error, LOG_ERR);
				return -1;
			}

			$selected = array();
			$langs->load('accountancy');
			while ($obj = $this->db->fetch_object($resql)) {
				$label = $langs->trans($obj->label);

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
				if (!empty($selectedIds) && in_array($select_value_in, $selectedIds)) {
					//var_dump("Found ".$selectid." ".$select_value_in);
					$selected[] = $select_value_out;
				}
				$options[$select_value_out] = $label;
			}
			$this->db->free($resql);

			if ($usecache) {
				$this->options_cache[$usecache] = $options;
			}
		}

		$out .= Form::multiselectarray($htmlname, $options, $selected, $showempty, 0, $morecss, 0, 0, '', 'code_journal', '', ($disabledajaxcombo ? 0 : 1));

		return $out;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return list of accounting category.
	 * 	Use mysoc->country_id or mysoc->country_code so they must be defined.
	 *
	 *	@param	int		$selected       Preselected type
	 *	@param  string	$htmlname       Name of field in form
	 * 	@param	int		$useempty		Set to 1 if we want an empty value
	 * 	@param	int		$maxlen			Max length of text in combo box
	 * 	@param	int		$help			Add or not the admin help picto
	 *  @param  int     $allcountries   All countries
	 * 	@return	void|string				HTML component with the select
	 */
	public function select_accounting_category($selected = 0, $htmlname = 'account_category', $useempty = 0, $maxlen = 0, $help = 1, $allcountries = 0)
	{
		// phpcs:enable
		global $langs, $mysoc;

		if (empty($mysoc->country_id) && empty($mysoc->country_code) && empty($allcountries)) {
			dol_print_error(null, 'Call to select_accounting_account with mysoc country not yet defined');
			exit;
		}

		$out = '';

		if (!empty($mysoc->country_id)) {
			$sql = "SELECT c.rowid, c.label as type, c.range_account";
			$sql .= " FROM ".$this->db->prefix()."c_accounting_category as c";
			$sql .= " WHERE c.active = 1";
			$sql .= " AND c.category_type = 0";
			if (empty($allcountries)) {
				$sql .= " AND c.fk_country = ".((int) $mysoc->country_id);
			}
			$sql .= " ORDER BY c.label ASC";
		} else {
			$sql = "SELECT c.rowid, c.label as type, c.range_account";
			$sql .= " FROM ".$this->db->prefix()."c_accounting_category as c, ".$this->db->prefix()."c_country as co";
			$sql .= " WHERE c.active = 1";
			$sql .= " AND c.category_type = 0";
			$sql .= " AND c.fk_country = co.rowid";
			if (empty($allcountries)) {
				$sql .= " AND co.code = '".$this->db->escape($mysoc->country_code)."'";
			}
			$sql .= " ORDER BY c.label ASC";
		}

		$this->nbaccounts_category = 0;

		dol_syslog(get_class($this).'::'.__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				$this->nbaccounts_category = $num;

				$out .= '<select class="flat minwidth200" id="'.$htmlname.'" name="'.$htmlname.'">';
				$i = 0;

				if ($useempty) {
					$out .= '<option value="0">&nbsp;</option>';
				}
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);

					$titletoshowhtml = ($maxlen ? dol_trunc($obj->type, $maxlen) : $obj->type).($obj->range_account ? ' <span class="opacitymedium">('.$obj->range_account.')</span>' : '');
					$titletoshow = ($maxlen ? dol_trunc($obj->type, $maxlen) : $obj->type).($obj->range_account ? ' ('.$obj->range_account.')' : '');

					$out .= '<option value="'.$obj->rowid.'"';
					if ($obj->rowid == $selected) {
						$out .= ' selected';
					}
					//$out .= ' data-html="'.dol_escape_htmltag(dol_string_onlythesehtmltags($titletoshowhtml, 1, 0, 0, 0, array('span'))).'"';
					$out .= ' data-html="'.dolPrintHTMLForAttribute($titletoshowhtml).'"';
					$out .= '>';
					$out .= dol_escape_htmltag($titletoshow);
					$out .= '</option>';
					$i++;
				}
				$out .= '</select>';
				//if ($user->admin && $help) $out .= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);

				$out .= ajax_combobox($htmlname, array());
			} else {
				$out .= '<span class="opacitymedium">'.$langs->trans("ErrorNoAccountingCategoryForThisCountry", $mysoc->country_code, $langs->transnoentitiesnoconv("Accounting"), $langs->transnoentitiesnoconv("Setup"), $langs->transnoentitiesnoconv("AccountingCategories")).'</span>';
			}
		} else {
			dol_print_error($this->db);
		}

		return $out;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return select filter with date of transaction
	 *
	 * @param 	string 				$htmlname       Name of select field
	 * @param 	string 				$selectedkey    Value
	 * @return 	string|int<-1,-1>					HTML edit field, or -1 if error
	 */
	public function select_bookkeeping_importkey($htmlname = 'importkey', $selectedkey = '')
	{
		// phpcs:enable
		$options = array();

		$sql = "SELECT DISTINCT import_key FROM ".$this->db->prefix()."accounting_bookkeeping";
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
	 * @param string   		$selectid          	Preselected id of accounting accounts (depends on $select_in)
	 * @param string   		$htmlname          	Name of HTML field id. If name start with '.', it is name of HTML css class, so several component with same name in different forms can be used.
	 * @param int|string    $showempty         	1=Add an empty field, 2=Add an empty field+'None' field
	 * @param array<array<string,mixed>> $event Event options
	 * @param int      		$select_in         	0=selectid value is a aa.rowid (default) or 1=selectid is aa.account_number
	 * @param int      		$select_out        	Set value returned by select. 0=rowid (default), 1=account_number
	 * @param string   		$morecss           	More css non HTML object
	 * @param string   		$usecache          	Key to use to store result into a cache. Next call with same key will reuse the cache.
	 * @param string		$active				Filter on status active or not: '0', '1' or '' for no filter
	 * @return string|int<-1,-1>               	String with HTML select, or -1 if error
	 */
	public function select_account($selectid, $htmlname = 'account', $showempty = 0, $event = array(), $select_in = 0, $select_out = 0, $morecss = 'minwidth100 maxwidth300 maxwidthonsmartphone', $usecache = '', $active = '1')
	{
		// phpcs:enable
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';

		$out = '';
		$selected = '';

		$options = array();

		if ($showempty == 2) {
			$options['0'] = '--- '.$langs->trans("None").' ---';
		}

		if ($usecache && !empty($this->options_cache[$usecache])) {
			$options += $this->options_cache[$usecache]; // We use + instead of array_merge because we don't want to reindex key from 0
			$selected = $selectid;
		} else {
			$trunclength = getDolGlobalInt('ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT', 50);

			$sql = "SELECT DISTINCT aa.account_number, aa.label, aa.labelshort, aa.rowid, aa.fk_pcg_version";
			$sql .= " FROM ".$this->db->prefix()."accounting_account as aa";
			$sql .= " INNER JOIN ".$this->db->prefix()."accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
			$sql .= " AND asy.rowid = ".((int) getDolGlobalInt('CHARTOFACCOUNTS'));
			if ($active === '1') {
				$sql .= " AND aa.active = 1";
			} elseif ($active === '0') {
				$sql .= " AND aa.active = 0";
			}
			$sql .= " AND aa.entity=".((int) $conf->entity);
			$sql .= " ORDER BY aa.account_number";

			dol_syslog(get_class($this)."::select_account", LOG_DEBUG);
			$resql = $this->db->query($sql);

			if (!$resql) {
				$this->error = "Error ".$this->db->lasterror();
				dol_syslog(get_class($this)."::select_account ".$this->error, LOG_ERR);
				return -1;
			}

			$num_rows = $this->db->num_rows($resql);

			if ($num_rows == 0 && getDolGlobalInt('CHARTOFACCOUNTS') <= 0) {
				$langs->load("errors");
				$showempty = $langs->trans("ErrorYouMustFirstSetupYourChartOfAccount");
			} else {
				$selected = $selectid; // selectid can be -1, 0, 123
				while ($obj = $this->db->fetch_object($resql)) {
					if (empty($obj->labelshort)) {
						$labeltoshow = $obj->label;
					} else {
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
			}

			$this->db->free($resql);

			if ($usecache) {
				$this->options_cache[$usecache] = $options;
				unset($this->options_cache[$usecache]['0']);
			}
		}


		$out .= Form::selectarray($htmlname, $options, $selected, ($showempty ? (is_numeric($showempty) ? 1 : $showempty) : 0), 0, 0, '', 0, 0, 0, '', $morecss, 1);

		$this->nbaccounts = count($options) - ($showempty == 2 ? 1 : 0);

		return $out;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return list of auxiliary accounts. Cumulate list from customers, suppliers and users.
	 *
	 * @param string   		$selectid       Preselected pcg_type
	 * @param string   		$htmlname       Name of field in html form
	 * @param int|string    $showempty      Add an empty field
	 * @param string   		$morecss        More css
	 * @param string   		$usecache       Key to use to store result into a cache. Next call with same key will reuse the cache.
	 * @param string		$labelhtmlname	HTML name of label for autofill of account from name.
	 * @return string|int<-1,-1>			String with HTML select, or -1 if error
	 */
	public function select_auxaccount($selectid, $htmlname = 'account_num_aux', $showempty = 0, $morecss = 'minwidth100 maxwidth300 maxwidthonsmartphone', $usecache = '', $labelhtmlname = '')
	{
		// phpcs:enable
		global $conf;

		$aux_account = array();

		if ($usecache && !empty($this->options_cache[$usecache])) {
			$aux_account += $this->options_cache[$usecache]; // We use + instead of array_merge because we don't want to reindex key from 0
		} else {
			dol_syslog(get_class($this)."::select_auxaccount", LOG_DEBUG);

			// Auxiliary thirdparties account
			$sql = "SELECT code_compta as code_compta_client, code_compta_fournisseur, nom as name";
			$sql .= " FROM ".$this->db->prefix()."societe";
			$sql .= " WHERE entity IN (".getEntity('societe').")";
			$sql .= " AND (client IN (1,3) OR fournisseur = 1)";

			$resql = $this->db->query($sql);
			if ($resql) {
				while ($obj = $this->db->fetch_object($resql)) {
					if (!empty($obj->code_compta_client)) {
						$aux_account[$obj->code_compta_client] = $obj->code_compta_client.' <span class="opacitymedium">('.$obj->name.')</span>';
					}
					if (!empty($obj->code_compta_fournisseur)) {
						$aux_account[$obj->code_compta_fournisseur] = $obj->code_compta_fournisseur.' <span class="opacitymedium">('.$obj->name.')</span>';
					}
				}
			} else {
				$this->error = "Error ".$this->db->lasterror();
				dol_syslog(get_class($this)."::select_auxaccount ".$this->error, LOG_ERR);
				return -1;
			}

			ksort($aux_account);

			$this->db->free($resql);

			// Auxiliary user account
			$sql = "SELECT DISTINCT accountancy_code, lastname, firstname ";
			$sql .= " FROM ".$this->db->prefix()."user";
			$sql .= " WHERE entity IN (".getEntity('user').")";
			$sql .= " ORDER BY accountancy_code";

			$resql = $this->db->query($sql);
			if ($resql) {
				while ($obj = $this->db->fetch_object($resql)) {
					if (!empty($obj->accountancy_code)) {
						$aux_account[$obj->accountancy_code] = $obj->accountancy_code.' <span class="opacitymedium">('.dolGetFirstLastname($obj->firstname, $obj->lastname).')</span>';
					}
				}
			} else {
				$this->error = "Error ".$this->db->lasterror();
				dol_syslog(get_class($this)."::select_auxaccount ".$this->error, LOG_ERR);
				return -1;
			}
			$this->db->free($resql);

			if ($usecache) {
				$this->options_cache[$usecache] = $aux_account;
			}
		}

		// Build select
		$out = '';
		$out .= Form::selectarray($htmlname, $aux_account, $selectid, ($showempty ? (is_numeric($showempty) ? 1 : $showempty) : 0), 0, 0, '', 0, 0, 0, '', $morecss, 1);
		//automatic filling if we give the name of the subledger_label input
		if (!empty($conf->use_javascript_ajax) && !empty($labelhtmlname)) {
			$out .= '<script nonce="'.getNonce().'">
				jQuery(document).ready(() => {
					$("#'.$htmlname.'").on("select2:select", function(e) {
						var regExp = /\(([^)]+)\)/;
						const match = regExp.exec(e.params.data.text);
						$(\'input[name="'.dol_escape_js($labelhtmlname).'"]\').val(match[1]);
					});
				});

			</script>';
		}

		return $out;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return HTML combo list of years existing into book keepping
	 *
	 * @param string 	$selected 		Preselected value
	 * @param string 	$htmlname 		Name of HTML select object
	 * @param int 		$useempty 		Affiche valeur vide dans liste
	 * @param string 	$output_format 	(html/option (for option html only)/array (to return options arrays
	 * @return string|array<string,string>|int<-1,-1>	HTML select component || array of select options || - 1 if error
	 */
	public function selectyear_accountancy_bookkepping($selected = '', $htmlname = 'yearid', $useempty = 0, $output_format = 'html')
	{
		// phpcs:enable
		global $conf;

		$out_array = array();

		$sql = "SELECT DISTINCT date_format(doc_date, '%Y') as dtyear";
		$sql .= " FROM ".$this->db->prefix()."accounting_bookkeeping";
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

	/**
	 *  Output html select to select accounting account
	 *
	 *  @param	string	$page       			Page
	 *  @param  string	$selected   			Id preselected
	 * 	@param	string	$htmlname				Name of HTML select object
	 *  @param  int		$option					option (0: aggregate by general account or 1: aggregate by subaccount)
	 *  @param  int		$useempty				Show empty value in list
	 *  @param  string	$filter         		optional filters criteria
	 *  @param  int		$nooutput       		No print output. Return it only.
	 *  @return	void|string
	 */
	public function formAccountingAccount($page, $selected = '', $htmlname = 'none', $option = 0, $useempty = 1, $filter = '', $nooutput = 0)
	{
		global $langs;

		$out = '';
		if ($htmlname != "none") {
			$out .= '<form method="post" action="' . $page . '">';
			$out .= '<input type="hidden" name="action" value="set'.$htmlname.'">';
			$out .= '<input type="hidden" name="token" value="' . newToken() . '">';
			if ($option == 0) {
				$out .= $this->select_account($selected, $htmlname, $useempty, array(), 1, 1, 'minwidth100 maxwidth300 maxwidthonsmartphone', 'accounts', $filter);
			} else {
				$out .= $this->select_auxaccount($selected, $htmlname, $useempty, 'minwidth100 maxwidth300 maxwidthonsmartphone', 'subaccounts');
			}
			$out .= '<input type="submit" class="button smallpaddingimp valignmiddle" name="modify" value="' . $langs->trans("Modify") . '">';
			//$out .= '<input type="submit" class="button smallpaddingimp valignmiddle button-cancel" name="cancel" value="' . $langs->trans("Cancel") . '">';
			$out .= '</form>';
		} else {
			$out .= "&nbsp;";
		}

		if ($nooutput) {
			return $out;
		} else {
			print $out;
		}
	}
}
