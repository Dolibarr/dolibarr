<?php
/* Copyright (C) 2017		Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
 * \file		htdocs/accountancy/class/accountingjournal.class.php
 * \ingroup		Accountancy (Double entries)
 * \brief		File of class to manage accounting journals
 */

/**
 * Class to manage accounting accounts
 */
class AccountingJournal extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'accounting_journal';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'accounting_journal';

	/**
	 * @var string Fieldname with ID of parent key if this field has a parent
	 */
	public $fk_element = '';

	/**
	 * @var int 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'generic';

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var string Accounting journal code
	 */
	public $code;

	/**
	 * @var string Accounting Journal label
	 */
	public $label;

	/**
	 * @var int 1:various operations, 2:sale, 3:purchase, 4:bank, 5:expense-report, 8:inventory, 9: has-new
	 */
	public $nature;

	/**
	 * @var int is active or not
	 */
	public $active;

	/**
	 * @var array array of lines
	 */
	public $lines;

	/**
	 * @var array 		Accounting account cached
	 */
	static public $accounting_account_cached = array();

	/**
	 * @var array 		Nature mapping
	 */
	static public $nature_maps = array(
		1 => 'variousoperations',
		2 => 'sells',
		3 => 'purchases',
		4 => 'bank',
		5 => 'expensereports',
		8 => 'inventories',
		9 => 'hasnew',
	);

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handle
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Load an object from database
	 *
	 * @param	int		$rowid				Id of record to load
	 * @param 	string 	$journal_code		Journal code
	 * @return	int							<0 if KO, Id of record if OK and found
	 */
	public function fetch($rowid = null, $journal_code = null)
	{
		global $conf;

		if ($rowid || $journal_code) {
			$sql = "SELECT rowid, code, label, nature, active";
			$sql .= " FROM ".MAIN_DB_PREFIX."accounting_journal";
			$sql .= " WHERE";
			if ($rowid) {
				$sql .= " rowid = ".((int) $rowid);
			} elseif ($journal_code) {
				$sql .= " code = '".$this->db->escape($journal_code)."'";
				$sql .= " AND entity  = ".$conf->entity;
			}

			dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result) {
				$obj = $this->db->fetch_object($result);

				if ($obj) {
					$this->id = $obj->rowid;
					$this->rowid		= $obj->rowid;

					$this->code			= $obj->code;
					$this->ref			= $obj->code;
					$this->label		= $obj->label;
					$this->nature		= $obj->nature;
					$this->active		= $obj->active;

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
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		$sql = "SELECT rowid, code, label, nature, active";
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.code' || $key == 't.label' || $key == 't.nature') {
					$sqlwhere[] = $key.'\''.$this->db->escape($value).'\'';
				} elseif ($key == 't.rowid' || $key == 't.active') {
					$sqlwhere[] = $key.'='.$value;
				}
			}
		}
		$sql .= ' WHERE 1 = 1';
		$sql .= " AND entity IN (".getEntity('accountancy').")";
		if (count($sqlwhere) > 0) {
			$sql .= " AND ".implode(" ".$filtermode." ", $sqlwhere);
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit + 1, $offset);
		}
		$this->lines = array();

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new self($this->db);

				$line->id = $obj->rowid;
				$line->code = $obj->code;
				$line->label = $obj->label;
				$line->nature = $obj->nature;
				$line->active = $obj->active;

				$this->lines[] = $line;
			}

			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Return clicable name (with picto eventually)
	 *
	 * @param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 * @param	int		$withlabel		0=No label, 1=Include label of journal
	 * @param	int  	$nourl			1=Disable url
	 * @param	string  $moretitle		Add more text to title tooltip
	 * @param	int  	$notooltip		1=Disable tooltip
	 * @return	string	String with URL
	 */
	public function getNomUrl($withpicto = 0, $withlabel = 0, $nourl = 0, $moretitle = '', $notooltip = 0)
	{
		global $langs, $conf, $user;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$url = DOL_URL_ROOT.'/accountancy/admin/journals_list.php?id=35';

		$label = '<u>'.$langs->trans("ShowAccountingJournal").'</u>';
		if (!empty($this->code)) {
			$label .= '<br><b>'.$langs->trans('Code').':</b> '.$this->code;
		}
		if (!empty($this->label)) {
			$label .= '<br><b>'.$langs->trans('Label').':</b> '.$langs->transnoentities($this->label);
		}
		if ($moretitle) {
			$label .= ' - '.$moretitle;
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowAccountingJournal");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip"';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		if ($nourl) {
			$linkstart = '';
			$linkclose = '';
			$linkend = '';
		}

		$label_link = $this->code;
		if ($withlabel) {
			$label_link .= ' - '.($nourl ? '<span class="opacitymedium">' : '').$langs->transnoentities($this->label).($nourl ? '</span>' : '');
		}

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $label_link;
		}
		$result .= $linkend;

		return $result;
	}

	/**
	 *  Retourne le libelle du statut d'un user (actif, inactif)
	 *
	 *  @param	int		$mode		  0=libelle long, 1=libelle court
	 *  @return	string 				   Label of type
	 */
	public function getLibType($mode = 0)
	{
		return $this->LibType($this->nature, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return type of an accounting journal
	 *
	 *  @param	int		$nature			Id type
	 *  @param  int		$mode		  	0=libelle long, 1=libelle court
	 *  @return string 				   	Label of type
	 */
	public function LibType($nature, $mode = 0)
	{
		// phpcs:enable
		global $langs;

		$langs->loadLangs(array("accountancy"));

		if ($mode == 0) {
			$prefix = '';
			if ($nature == 9) {
				return $langs->trans('AccountingJournalType9');
			} elseif ($nature == 5) {
				return $langs->trans('AccountingJournalType5');
			} elseif ($nature == 4) {
				return $langs->trans('AccountingJournalType4');
			} elseif ($nature == 3) {
				return $langs->trans('AccountingJournalType3');
			} elseif ($nature == 2) {
				return $langs->trans('AccountingJournalType2');
			} elseif ($nature == 1) {
				return $langs->trans('AccountingJournalType1');
			}
		} elseif ($mode == 1) {
			if ($nature == 9) {
				return $langs->trans('AccountingJournalType9');
			} elseif ($nature == 5) {
				return $langs->trans('AccountingJournalType5');
			} elseif ($nature == 4) {
				return $langs->trans('AccountingJournalType4');
			} elseif ($nature == 3) {
				return $langs->trans('AccountingJournalType3');
			} elseif ($nature == 2) {
				return $langs->trans('AccountingJournalType2');
			} elseif ($nature == 1) {
				return $langs->trans('AccountingJournalType1');
			}
		}
	}


	/**
	 *  Get journal data
	 *
	 * @param 	User			$user				User who get infos
	 * @param 	string			$type				Type data returned ('view', 'bookkeeping', 'csv')
	 * @param 	int				$date_start			Filter 'start date'
	 * @param 	int				$date_end			Filter 'end date'
	 * @param 	string			$in_bookkeeping		Filter 'in bookkeeping' ('already', 'notyet')
	 * @return 	array|int							<0 if KO, >0 if OK
	 */
	public function getData(User $user, $type = 'view', $date_start = null, $date_end = null, $in_bookkeeping = 'notyet')
	{
		global $hookmanager;

		// Clean parameters
		if (empty($type)) $type = 'view';
		if (empty($in_bookkeeping)) $in_bookkeeping = 'notyet';

		// Hook
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}

		$data = array();

		$hookmanager->initHooks(array('accountingjournaldao'));
		$parameters = array('data' => &$data, 'user' => $user, 'type' => $type, 'date_start' => $date_start, 'date_end' => $date_end, 'in_bookkeeping' => $in_bookkeeping);
		$reshook = $hookmanager->executeHooks('getData', $parameters, $this); // Note that $action and $object may have been
		if ($reshook < 0) {
			$this->error = $hookmanager->error;
			$this->errors = $hookmanager->errors;
			return -1;
		} elseif (empty($reshook)) {
			switch ($this->nature) {
				case 1: // Various Journal
					$data = $this->getVariousData($user, $type, $date_start, $date_end, $in_bookkeeping);
					break;
//				case 2: // Sells Journal
//				case 3: // Purchases Journal
//				case 4: // Bank Journal
//				case 5: // Expense reports Journal
//				case 8: // Inventory Journal
//				case 9: // hasnew Journal
			}
		}

		return $data;
	}

	/**
	 *  Get various journal data
	 *
	 * @param 	User			$user				User who get infos
	 * @param 	string			$type				Type data returned ('view', 'bookkeeping', 'csv')
	 * @param 	int				$date_start			Filter 'start date'
	 * @param 	int				$date_end			Filter 'end date'
	 * @param 	string			$in_bookkeeping		Filter 'in bookkeeping' ('already', 'notyet')
	 * @return 	array|int							<0 if KO, >0 if OK
	 */
	public function getVariousData(User $user, $type = 'view', $date_start = null, $date_end = null, $in_bookkeeping = 'notyet')
	{
		global $conf, $langs, $mysoc;

		return array();

		require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
		require_once DOL_DOCUMENT_ROOT . '/asset/class/asset.class.php';
		require_once DOL_DOCUMENT_ROOT . '/asset/class/assetaccountancycodes.class.php';
		require_once DOL_DOCUMENT_ROOT . '/asset/class/assetdepreciationoptions.class.php';

		$langs->loadLangs(array("companies", "assets"));

		// Clean parameters
		if (empty($type)) $type = 'view';
		if (empty($in_bookkeeping)) $in_bookkeeping = 'notyet';

		$sql = "SELECT a.rowid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "asset as a";
		$sql .= " WHERE a.entity IN (" . getEntity('asset', 0) . ')'; // We don't share object for accountancy, we use source object sharing
		$sql .= " AND a.status > 0 AND a.status != 2";
//		if ($date_start && $date_end) {
//			$sql .= " AND a.date_start >= '" . $this->db->idate($date_start) . "' AND f.datef <= '" . $this->db->idate($date_end) . "'";
//		}
//		// Define begin binding date
//		if (!empty($conf->global->ACCOUNTING_DATE_START_BINDING)) {
//			$sql .= " AND f.date_start >= '" . $this->db->idate($conf->global->ACCOUNTING_DATE_START_BINDING) . "'";
//		}
//		// Already in bookkeeping or not
//		if ($in_bookkeeping == 'already') {
//			$sql .= " AND f.rowid IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab WHERE ab.doc_type='customer_invoice')";
//		} elseif ($in_bookkeeping == 'notyet') {
//			$sql .= " AND f.rowid NOT IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab WHERE ab.doc_type='customer_invoice')";
//		}
		$sql .= " ORDER BY a.date_start";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $this->db->lasterror();
			return -1;
		}

		$pre_data = array(
			'elements' => array(),
		);
		while ($obj = $this->db->fetch_object($resql)) {
			$asset = new Asset($this->db);
			$result = $asset->fetch($obj->rowid);
			if ($result < 0) {
				$this->error = $asset->error;
				$this->errors = $asset->errors;
				return -1;
			}

			$result = $asset->fetchDepreciationLines();
			if ($result < 0) {
				$this->error = $assetaccountancycodes->error;
				$this->errors = $assetaccountancycodes->errors;
				return -1;
			}

			$assetaccountancycodes = new AssetAccountancyCodes($this->db);
			$result = $assetaccountancycodes->fetchAccountancyCodes($obj->rowid);
			if ($result < 0) {
				$this->error = $assetaccountancycodes->error;
				$this->errors = $assetaccountancycodes->errors;
				return -1;
			}

			$pre_data['elements'][$obj->rowid] = array(
				'error' => '',
				'date' => $this->db->jdate($obj->df),
				'ref' => $obj->ref,
				'ht_lines' => array(),
			);

			$compta_prod = $obj->compte;
			if (empty($compta_prod)) {
				if ($obj->product_type == 0) {
					$compta_prod = (!empty($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT)) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT : 'NotDefined';
				} else {
					$compta_prod = (!empty($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT)) ? $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT : 'NotDefined';
				}
			}

			$vatdata = getTaxesFromId($obj->tva_tx . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : ''), $mysoc, $mysoc, 0);
			$compta_tva = (!empty($vatdata['accountancy_code_sell']) ? $vatdata['accountancy_code_sell'] : $cpttva);
			$compta_localtax1 = (!empty($vatdata['accountancy_code_sell']) ? $vatdata['accountancy_code_sell'] : $cpttva);
			$compta_localtax2 = (!empty($vatdata['accountancy_code_sell']) ? $vatdata['accountancy_code_sell'] : $cpttva);

			// Define array to display all VAT rates that use this accounting account $compta_tva
			if (price2num($obj->tva_tx) || !empty($obj->vat_src_code)) {
				if (!isset($pre_data['elements'][$obj->rowid]['vat_info'][$compta_tva])) $pre_data['elements'][$obj->rowid]['vat_info'][$compta_tva] = array();
				$pre_data['elements'][$obj->rowid]['vat_info'][$compta_tva][vatrate($obj->tva_tx) . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : '')] = (vatrate($obj->tva_tx) . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : ''));
			}

			// Situation invoices handling
			$line = new FactureLigne($this->db);
			$line->fetch($obj->fdid);
			$prev_progress = $line->get_prev_progress($obj->rowid);
			if ($obj->type == Facture::TYPE_SITUATION) {
				// Avoid divide by 0
				if ($obj->situation_percent == 0) {
					$situation_ratio = 0;
				} else {
					$situation_ratio = ($obj->situation_percent - $prev_progress) / $obj->situation_percent;
				}
			} else {
				$situation_ratio = 1;
			}

			if (!isset($pre_data['elements'][$obj->rowid]['ttc_lines'][$compta_soc])) $pre_data['elements'][$obj->rowid]['ttc_lines'][$compta_soc] = 0;
			$pre_data['elements'][$obj->rowid]['ttc_lines'][$compta_soc] += $obj->total_ttc * $situation_ratio;

			if (!isset($pre_data['elements'][$obj->rowid]['ht_lines'][$compta_prod])) $pre_data['elements'][$obj->rowid]['ht_lines'][$compta_prod] = 0;
			$pre_data['elements'][$obj->rowid]['ht_lines'][$compta_prod] += $obj->total_ht * $situation_ratio;

			if (!isset($pre_data['elements'][$obj->rowid]['vat_lines'][0][$compta_tva])) $pre_data['elements'][$obj->rowid]['vat_lines'][0][$compta_tva] = 0;
			if (empty($line->tva_npr)) $pre_data['elements'][$obj->rowid]['vat_lines'][0][$compta_tva] += $obj->total_tva * $situation_ratio; // We ignore line if VAT is a NPR

			if (!isset($pre_data['elements'][$obj->rowid]['vat_lines'][1][$compta_localtax1])) $pre_data['elements'][$obj->rowid]['vat_lines'][1][$compta_localtax1] = 0;
			$pre_data['elements'][$obj->rowid]['vat_lines'][1][$compta_localtax1] += $obj->total_localtax1 * $situation_ratio;

			if (!isset($pre_data['elements'][$obj->rowid]['vat_lines'][2][$compta_localtax2])) $pre_data['elements'][$obj->rowid]['vat_lines'][2][$compta_localtax2] = 0;
			$pre_data['elements'][$obj->rowid]['vat_lines'][2][$compta_localtax2] += $obj->total_localtax2 * $situation_ratio;
		}

		$journal = $this->code;
		$journal_label = $this->label;
		$journal_label_formatted = $langs->transnoentities($journal_label);
		$now = dol_now();

		$company_static = new Client($this->db);
		$element_static = new Facture($this->db);

		$journal_data = array();
		foreach ($pre_data['elements'] as $pre_data_id => $pre_data_info) {
			$element_static->id = $pre_data_id;
			$element_static->ref = (string)$pre_data_info["ref"];
			$element_static->type = $pre_data_info["type"];
			$element_static->close_code = $pre_data_info["close_code"];
			$element_static->date = $pre_data_info["date"];
			$element_static->date_lim_reglement = $pre_data_info["date_limit_payment"];
			$element_link = $element_static->getNomUrl(1);
			$element_date = dol_print_date($element_static->date, 'day');

			$company_static->id = $pre_data_info['company_id'];
			if (!empty($pre_data['companies'][$company_static->id])) {
				$company_infos = $pre_data['companies'][$company_static->id];
				$company_static->name = $company_infos['name'];
				$company_static->code_client = $company_infos['code_client'];
				$company_static->code_compta_client = $company_infos['code_compta'];
				$company_static->client = 3;
				$company_name_formatted_0 = dol_trunc($company_static->name, 16);
				$company_name_formatted_1 = utf8_decode(dol_trunc($company_static->name, 32));
				$company_name_formatted_2 = utf8_decode(dol_trunc($company_static->name, 16));
			} else {
				$company_static->name = '';
				$company_static->code_client = '';
				$company_static->code_compta_client = '';
				$company_static->client = '';
				$company_name_formatted_0 = '';
				$company_name_formatted_1 = '';
				$company_name_formatted_2 = '';
			}

			$label_operation = $company_static->getNomUrl(0, 'customer', 16) . ' - ' . $element_static->ref;

			// Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
			$replacedinvoice = 0;
			if ($element_static->close_code == Facture::CLOSECODE_REPLACED) {
				$replacedinvoice = 1;
				$alreadydispatched = $element_static->getVentilExportCompta(); // Test if replaced invoice already into bookkeeping.
				if ($alreadydispatched) {
					$replacedinvoice = 2;
				}
			}

			$element = array(
				'ref' => $element_static->ref,
				'error' => $pre_data_info['error'],
				'blocks' => array(),
			);
			$blocks = array();

			// If not already into bookkeeping, we won't add it, if yes, add the counterpart ???.
			if ($replacedinvoice == 1) {
				if ($type == 'view') {
					$blocks[] = array(
						'date' => $element_date,
						'piece' => '<strike>' . $element_link . '</strike>',
						'account_accounting' => $langs->trans("Replaced"),
						'subledger_account' => '',
						'label_operation' => '',
						'debit' => '',
						'credit' => '',
					);
				} else {
					continue;
				}
			} else {
				if ($pre_data_info['error'] == 'somelinesarenotbound') {
					if ($type == 'view') {
						$blocks[] = array(
							'date' => $element_date,
							'piece' => $element_link,
							'account_accounting' => '<span class="error">' . $langs->trans('ErrorInvoiceContainsLinesNotYetBoundedShort', $element_static->ref) . '</span>',
							'subledger_account' => '',
							'label_operation' => '',
							'debit' => '',
							'credit' => '',
						);
					} else {
						$journal_data[$pre_data_id] = $element;
						continue;
					}
				}

				// Third parties
				//--------------------
				$account_to_show = length_accounta($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER);
				if (($account_to_show == "") || $account_to_show == 'NotDefined') {
					$account_to_show = '<span class="error">' . $langs->trans("MainAccountForCustomersNotDefined") . '</span>';
				}
				foreach ($pre_data_info['ttc_lines'] as $account => $mt) {
					if ($type == 'view') {
						$subledger_account_to_show = length_accounta($account);
						if (($subledger_account_to_show == "") || $subledger_account_to_show == 'NotDefined') {
							$subledger_account_to_show = '<span class="error">' . $langs->trans("ThirdpartyAccountNotDefined") . '</span>';
						}

						$blocks[] = array(
							'date' => $element_date,
							'piece' => $element_link,
							'account_accounting' => $account_to_show,
							'subledger_account' => $subledger_account_to_show,
							'label_operation' => $label_operation . ' - ' . $langs->trans("SubledgerAccount"),
							'debit' => $mt >= 0 ? price($mt) : '',
							'credit' => $mt < 0 ? price(-$mt) : '',
						);
					} elseif ($type == 'bookkeeping') {
						$account_infos = $this->getAccountingAccountInfos($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER);

						$blocks[] = array(
							'doc_date' => $element_static->date,
							'date_lim_reglement' => $element_static->date_lim_reglement,
							'doc_ref' => $element_static->ref,
							'date_creation' => $now,
							'doc_type' => 'customer_invoice',
							'fk_doc' => $element_static->id,
							'fk_docdet' => 0, // Useless, can be several lines that are source of this record to add
							'thirdparty_code' => $company_static->code_client,
							'subledger_account' => $company_static->code_compta_client,
							'subledger_label' => $company_static->name,
							'numero_compte' => $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER,
							'label_compte' => $account_infos['label'],
							'label_operation' => $company_name_formatted_0 . ' - ' . $element_static->ref . ' - ' . $langs->trans("SubledgerAccount"),
							'montant' => $mt,
							'sens' => $mt >= 0 ? 'D' : 'C',
							'debit' => $mt >= 0 ? $mt : 0,
							'credit' => $mt < 0 ? -$mt : 0,
							'code_journal' => $journal,
							'journal_label' => $journal_label_formatted,
							'piece_num' => '',
							'import_key' => '',
							'fk_user_author' => $user->id,
							'entity' => $conf->entity,
						);
					} else { // $type == 'csv'
						$account_infos = $this->getAccountingAccountInfos($account);

						$blocks[] = array(
							$element_static->id,
							$element_date,
							$element_static->ref,
							$company_name_formatted_1,
							$account_infos['code_formatted_1'],
							$conf->global->ACCOUNTING_ACCOUNT_CUSTOMER,
							$account_infos['code_formatted_1'],
							$langs->trans("Thirdparty"),
							$company_name_formatted_2 . ' - ' . $element_static->ref . ' - ' . $langs->trans("Thirdparty"),
							$mt >= 0 ? price($mt) : '',
							$mt < 0 ? price(-$mt) : '',
							$journal,
						);
					}
				}
				$element['blocks'][] = $blocks;

				// Product / Service
				//--------------------
				$blocks = array();
				foreach ($pre_data_info['ht_lines'] as $account => $mt) {
					$account_infos = $this->getAccountingAccountInfos($account);

					if ($type == 'view') {
						$account_to_show = length_accounta($account);
						if (($account_to_show == "") || $account_to_show == 'NotDefined') {
							$account_to_show = '<span class="error">' . $langs->trans("ProductNotDefined") . '</span>';
						}

						$blocks[] = array(
							'date' => $element_date,
							'piece' => $element_link,
							'account_accounting' => $account_to_show,
							'subledger_account' => '',
							'label_operation' => $label_operation . ' - ' . $account_infos['label'],
							'debit' => $mt < 0 ? price(-$mt) : '',
							'credit' => $mt >= 0 ? price($mt) : '',
						);
					} elseif ($type == 'bookkeeping') {
						if ($account_infos['found']) {
							$blocks[] = array(
								'doc_date' => $element_static->date,
								'date_lim_reglement' => $element_static->date_lim_reglement,
								'doc_ref' => $element_static->ref,
								'date_creation' => $now,
								'doc_type' => 'customer_invoice',
								'fk_doc' => $element_static->id,
								'fk_docdet' => 0, // Useless, can be several lines that are source of this record to add
								'thirdparty_code' => $company_static->code_client,
								'subledger_account' => '',
								'subledger_label' => '',
								'numero_compte' => $account,
								'label_compte' => $account_infos['label'],
								'label_operation' => $company_name_formatted_0 . ' - ' . $element_static->ref . ' - ' . $account_infos['label'],
								'montant' => $mt,
								'sens' => $mt < 0 ? 'D' : 'C',
								'debit' => $mt < 0 ? -$mt : 0,
								'credit' => $mt >= 0 ? $mt : 0,
								'code_journal' => $journal,
								'journal_label' => $journal_label_formatted,
								'piece_num' => '',
								'import_key' => '',
								'fk_user_author' => $user->id,
								'entity' => $conf->entity,
							);
						}
					} else { // $type == 'csv'
						$blocks[] = array(
							$element_static->id,
							$element_date,
							$element_static->ref,
							$company_name_formatted_1,
							$account_infos['code_formatted_1'],
							$account_infos['code_formatted_1'],
							'',
							$account_infos['label_formatted_1'],
							$company_name_formatted_2 . ' - ' . $account_infos['label_formatted_2'],
							$mt < 0 ? price(-$mt) : '',
							$mt >= 0 ? price($mt) : '',
							$journal,
						);
					}
				}
				$element['blocks'][] = $blocks;

				// VAT
				//--------------------
				$blocks = array();
				$list_of_tax = array(0, 1, 2);
				foreach ($list_of_tax as $num_tax) {
					foreach ($pre_data_info['vat_lines'][$num_tax] as $account => $mt) {
						if ($mt) {
							if ($type == 'view') {
								$account_to_show = length_accounta($account);
								if (($account_to_show == "") || $account_to_show == 'NotDefined') {
									$account_to_show = '<span class="error">' . $langs->trans("VATAccountNotDefined") . ' (' . $langs->trans("Sale") . ')</span>';
								}

								$blocks[] = array(
									'date' => $element_date,
									'piece' => $element_link,
									'account_accounting' => $account_to_show,
									'subledger_account' => '',
									'label_operation' => $label_operation . ' - ' . $langs->trans("VAT") . ' ' . join(', ', $pre_data_info['vat_info'][$account]) . ' %' . ($num_tax ? ' - Localtax ' . $num_tax : ''),
									'debit' => $mt < 0 ? price(-$mt) : '',
									'credit' => $mt >= 0 ? price($mt) : '',
								);
							} elseif ($type == 'bookkeeping') {
								$account_infos = $this->getAccountingAccountInfos($account);

								$blocks[] = array(
									'doc_date' => $element_static->date,
									'date_lim_reglement' => $element_static->date_lim_reglement,
									'doc_ref' => $element_static->ref,
									'date_creation' => $now,
									'doc_type' => 'customer_invoice',
									'fk_doc' => $element_static->id,
									'fk_docdet' => 0, // Useless, can be several lines that are source of this record to add
									'thirdparty_code' => $company_static->code_client,
									'subledger_account' => '',
									'subledger_label' => '',
									'numero_compte' => $account,
									'label_compte' => $account_infos['label'],
									'label_operation' => $company_name_formatted_0 . ' - ' . $element_static->ref . ' - ' . $langs->trans("VAT") . ' ' . join(', ', $pre_data_info['vat_info'][$account]) . ' %' . ($num_tax ? ' - Localtax ' . $num_tax : ''),
									'montant' => $mt,
									'sens' => $mt < 0 ? 'D' : 'C',
									'debit' => $mt < 0 ? -$mt : 0,
									'credit' => $mt >= 0 ? $mt : 0,
									'code_journal' => $journal,
									'journal_label' => $journal_label_formatted,
									'piece_num' => '',
									'import_key' => '',
									'fk_user_author' => $user->id,
									'entity' => $conf->entity,
								);
							} else { // $type == 'csv'
								$account_infos = $this->getAccountingAccountInfos($account);

								$blocks[] = array(
									$element_static->id,
									$element_date,
									$element_static->ref,
									$company_name_formatted_1,
									$account_infos['code_formatted_1'],
									$account_infos['code_formatted_1'],
									'',
									$langs->trans("VAT") . ' - ' . join(', ', $pre_data_info['vat_info'][$account]) . ' %',
									$company_name_formatted_2 . ' - ' . $element_static->ref . ' - ' . $langs->trans("VAT") . join(', ', $pre_data_info['vat_info'][$account]) . ' %' . ($num_tax ? ' - Localtax ' . $num_tax : ''),
									$mt < 0 ? price(-$mt) : '',
									$mt >= 0 ? price($mt) : '',
									$journal,
								);
							}
						}
					}
				}
			}

			$element['blocks'][] = $blocks;
			$journal_data[$pre_data_id] = $element;
		}
		unset($pre_data);

		return $journal_data;
	}

	/**
	 *  Write bookkeeping
	 *
	 * @param	User		$user				User who write in the bookkeeping
	 * @param	array		$journal_data		Journal data to write in the bookkeeping
	 * 											$journal_data = array(
	 * 												id_element => array(
	 * 													'ref' => 'ref',
	 * 													'error' => '',
	 * 													'blocks' => array(
	 * 														pos_block => array(
	 * 															num_line => array(
	 * 																'doc_date' => '',
	 * 																'date_lim_reglement' => '',
	 * 																'doc_ref' => '',
	 * 																'date_creation' => '',
	 * 																'doc_type' => '',
	 * 																'fk_doc' => '',
	 * 																'fk_docdet' => '',
	 * 																'thirdparty_code' => '',
	 * 																'subledger_account' => '',
	 * 																'subledger_label' => '',
	 * 																'numero_compte' => '',
	 * 																'label_compte' => '',
	 * 																'label_operation' => '',
	 * 																'montant' => '',
	 * 																'sens' => '',
	 * 																'debit' => '',
	 * 																'credit' => '',
	 * 																'code_journal' => '',
	 * 																'journal_label' => '',
	 * 																'piece_num' => '',
	 * 																'import_key' => '',
	 * 																'fk_user_author' => '',
	 * 																'entity' => '',
	 * 															),
	 * 														),
	 * 													),
	 * 												),
	 * 											);
	 * @param	int		$max_nb_errors			Nb error authorized before stop the process
	 * @return 	int								<0 if KO, >0 if OK
	 */
	public function writeIntoBookkeeping(User $user, &$journal_data = array(), $max_nb_errors = 10)
	{
		global $conf, $langs, $hookmanager;
		require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';

		// Hook
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}

		$error = 0;

		$hookmanager->initHooks(array('accountingjournaldao'));
		$parameters = array('journal_data' => &$journal_data);
		$reshook = $hookmanager->executeHooks('writeBookkeeping', $parameters, $this); // Note that $action and $object may have been
		if ($reshook < 0) {
			$this->error = $hookmanager->error;
			$this->errors = $hookmanager->errors;
			return -1;
		} elseif (empty($reshook)) {
			// Clean parameters
			$journal_data = is_array($journal_data) ? $journal_data : array();

			foreach ($journal_data as $element_id => $element) {
				$error_for_line = 0;
				$total_credit = 0;
				$total_debit = 0;

				$this->db->begin();

				if ($element['error'] == 'somelinesarenotbound') {
					$error++;
					$error_for_line++;
					$this->errors[] = $langs->trans('ErrorInvoiceContainsLinesNotYetBounded', $element['ref']);
				}

				if (!$error_for_line) {
					foreach ($element['blocks'] as $lines) {
						foreach ($lines as $line) {
							$bookkeeping = new BookKeeping($this->db);
							$bookkeeping->doc_date = $line['doc_date'];
							$bookkeeping->date_lim_reglement = $line['date_lim_reglement'];
							$bookkeeping->doc_ref = $line['doc_ref'];
							$bookkeeping->date_creation = $line['date_creation']; // not used
							$bookkeeping->doc_type = $line['doc_type'];
							$bookkeeping->fk_doc = $line['fk_doc'];
							$bookkeeping->fk_docdet = $line['fk_docdet'];
							$bookkeeping->thirdparty_code = $line['thirdparty_code'];
							$bookkeeping->subledger_account = $line['subledger_account'];
							$bookkeeping->subledger_label = $line['subledger_label'];
							$bookkeeping->numero_compte = $line['numero_compte'];
							$bookkeeping->label_compte = $line['label_compte'];
							$bookkeeping->label_operation = $line['label_operation'];
							$bookkeeping->montant = $line['montant'];
							$bookkeeping->sens = $line['sens'];
							$bookkeeping->debit = $line['debit'];
							$bookkeeping->credit = $line['credit'];
							$bookkeeping->code_journal = $line['code_journal'];
							$bookkeeping->journal_label = $line['journal_label'];
							$bookkeeping->piece_num = $line['piece_num'];
							$bookkeeping->import_key = $line['import_key'];
							$bookkeeping->fk_user_author = $user->id;
							$bookkeeping->entity = $conf->entity;

							$total_debit += $bookkeeping->debit;
							$total_credit += $bookkeeping->credit;

							$result = $bookkeeping->create($user);
							if ($result < 0) {
								if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {   // Already exists
									$error++;
									$error_for_line++;
									$journal_data[$element_id]['error'] = 'alreadyjournalized';
								} else {
									$error++;
									$error_for_line++;
									$journal_data[$element_id]['error'] = 'other';
									$this->errors[] = $bookkeeping->errorsToString();
								}
							}
						}

						if ($error_for_line) {
							break;
						}
					}
				}

				// Protection against a bug on lines before
				if (!$error_for_line && (price2num($total_debit, 'MT') != price2num($total_credit, 'MT'))) {
					$error++;
					$error_for_line++;
					$journal_data[$element_id]['error'] = 'amountsnotbalanced';
					$this->errors[] = 'Try to insert a non balanced transaction in book for ' . $element['blocks'] . '. Canceled. Surely a bug.';
				}

				if (!$error_for_line) {
					$this->db->commit();
				} else {
					$this->db->rollback();

					if ($error >= $max_nb_errors) {
						$this->errors[] = $langs->trans("ErrorTooManyErrorsProcessStopped");
						break; // Break in the foreach
					}
				}
			}
		}

		return $error ? -$error : 1;
	}

	/**
	 *	Export journal CSV
	 * 	ISO and not UTF8 !
	 *
	 * @param	array			$journal_data			Journal data to write in the bookkeeping
	 * 													$journal_data = array(
	 * 														id_element => array(
	 * 															'continue' => false,
	 * 															'blocks' => array(
	 * 																pos_block => array(
	 * 																	num_line => array(
	 * 																		data to write in the CSV line
	 * 																	),
	 * 																),
	 * 															),
	 * 														),
	 * 													);
	 * @param	int				$search_date_end		Search date end
	 * @param	string			$sep					CSV separator
	 * @return 	int|string								<0 if KO, >0 if OK
	 */
	public function exportCsv(&$journal_data = array(), $search_date_end = 0, $sep = '')
	{
		global $conf, $langs, $hookmanager;

		if (empty($sep)) $sep = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;
		$out = '';

		// Hook
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}

		$hookmanager->initHooks(array('accountingjournaldao'));
		$parameters = array('journal_data' => &$journal_data, 'search_date_end' => &$search_date_end, 'sep' => &$sep, 'out' => &$out);
		$reshook = $hookmanager->executeHooks('exportCsv', $parameters, $this); // Note that $action and $object may have been
		if ($reshook < 0) {
			$this->error = $hookmanager->error;
			$this->errors = $hookmanager->errors;
			return -1;
		} elseif (empty($reshook)) {
			// Clean parameters
			$journal_data = is_array($journal_data) ? $journal_data : array();

			// CSV header line
			$header = array();
			if ($this->nature == 4) {
				$header = array(
					$langs->transnoentitiesnoconv("BankId"),
					$langs->transnoentitiesnoconv("Date"),
					$langs->transnoentitiesnoconv("PaymentMode"),
					$langs->transnoentitiesnoconv("AccountAccounting"),
					$langs->transnoentitiesnoconv("LedgerAccount"),
					$langs->transnoentitiesnoconv("SubledgerAccount"),
					$langs->transnoentitiesnoconv("Label"),
					$langs->transnoentitiesnoconv("Debit"),
					$langs->transnoentitiesnoconv("Credit"),
					$langs->transnoentitiesnoconv("Journal"),
					$langs->transnoentitiesnoconv("Note"),
				);
			} elseif ($this->nature == 5) {
				$header = array(
					$langs->transnoentitiesnoconv("Date"),
					$langs->transnoentitiesnoconv("Piece"),
					$langs->transnoentitiesnoconv("AccountAccounting"),
					$langs->transnoentitiesnoconv("LabelOperation"),
					$langs->transnoentitiesnoconv("Debit"),
					$langs->transnoentitiesnoconv("Credit"),
				);
			}

			if (!empty($header)) $out .= '"' . implode('"' . $sep . '"', $header) . '"' . "\n";
			foreach ($journal_data as $element_id => $element) {
				foreach ($element['blocks'] as $lines) {
					foreach ($lines as $line) {
						$out .= '"' . implode('"' . $sep . '"', $line) . '"' . "\n";
					}
				}
			}
		}

		return $out;
	}

	/**
	 *  Get accounting account infos
	 *
	 * @param string	$account	Accounting account number
	 * @return array				Accounting account infos
	 */
	function getAccountingAccountInfos($account) {
		if (!isset(self::$accounting_account_cached[$account])) {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
			require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
			$accountingaccount = new AccountingAccount($this->db);
			$result = $accountingaccount->fetch(null, $account, true);
			if ($result > 0) {
				self::$accounting_account_cached[$account] = array(
					'found' => true,
					'label' => $accountingaccount->label,
					'code_formatted_1' => length_accounta(html_entity_decode($account)),
					'label_formatted_1' => utf8_decode(dol_trunc($accountingaccount->label, 32)),
					'label_formatted_2' => dol_trunc($accountingaccount->label, 32),
				);
			} else {
				self::$accounting_account_cached[$account] = array(
					'found' => false,
					'label' => '',
					'code_formatted_1' => length_accounta(html_entity_decode($account)),
					'label_formatted_1' => '',
					'label_formatted_2' => '',
				);
			}
		}

		return self::$accounting_account_cached[$account];
	}
}
