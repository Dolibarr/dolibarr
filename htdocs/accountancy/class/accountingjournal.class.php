<?php
/* Copyright (C) 2017-2022  OpenDSI     <support@open-dsi.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * Class to manage accounting journals
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
	 * @var array 		Accounting account cached
	 */
	public static $accounting_account_cached = array();

	/**
	 * @var array 		Nature mapping
	 */
	public static $nature_maps = array(
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

		$this->ismultientitymanaged = 0;
	}

	/**
	 * Load an object from database
	 *
	 * @param	int			$rowid			Id of record to load
	 * @param 	string|null $journal_code	Journal code
	 * @return	int							Return integer <0 if KO, Id of record if OK and found
	 */
	public function fetch($rowid = 0, $journal_code = null)
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
	 * Return clickable name (with picto eventually)
	 *
	 * @param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 * @param	int		$withlabel		0=No label, 1=Include label of journal, 2=Include nature of journal
	 * @param	int  	$nourl			1=Disable url
	 * @param	string  $moretitle		Add more text to title tooltip
	 * @param	int  	$notooltip		1=Disable tooltip
	 * @return	string	String with URL
	 */
	public function getNomUrl($withpicto = 0, $withlabel = 0, $nourl = 0, $moretitle = '', $notooltip = 0)
	{
		global $langs, $conf, $hookmanager;

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
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
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
		if ($withlabel == 1 && !empty($this->label)) {
			$label_link .= ' - '.($nourl ? '<span class="opacitymedium">' : '').$langs->transnoentities($this->label).($nourl ? '</span>' : '');
		}
		if ($withlabel == 2 && !empty($this->nature)) {
			$key = $langs->trans("AccountingJournalType".$this->nature);
			$transferlabel = ($this->nature && $key != "AccountingJournalType".strtoupper($langs->trans($this->nature)) ? $key : $this->label);
			$label_link .= ' - '.($nourl ? '<span class="opacitymedium">' : '').$transferlabel.($nourl ? '</span>' : '');
		}

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $label_link;
		}
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array('accountingjournaldao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}
		return $result;
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
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
	 *  @param  int		$mode		  	0=label long, 1=label short
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
		return "";
	}


	/**
	 *  Get journal data
	 *
	 * @param 	User			$user				User who get infos
	 * @param 	string			$type				Type data returned ('view', 'bookkeeping', 'csv')
	 * @param 	int				$date_start			Filter 'start date'
	 * @param 	int				$date_end			Filter 'end date'
	 * @param 	string			$in_bookkeeping		Filter 'in bookkeeping' ('already', 'notyet')
	 * @return 	array|int							Return integer <0 if KO, >0 if OK
	 */
	public function getData(User $user, $type = 'view', $date_start = null, $date_end = null, $in_bookkeeping = 'notyet')
	{
		global $hookmanager;

		// Clean parameters
		if (empty($type)) {
			$type = 'view';
		}
		if (empty($in_bookkeeping)) {
			$in_bookkeeping = 'notyet';
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
					$data = $this->getAssetData($user, $type, $date_start, $date_end, $in_bookkeeping);
					break;
					//              case 2: // Sells Journal
					//              case 3: // Purchases Journal
					//              case 4: // Bank Journal
					//              case 5: // Expense reports Journal
					//              case 8: // Inventory Journal
					//              case 9: // hasnew Journal
			}
		}

		return $data;
	}

	/**
	 *  Get asset data for various journal
	 *
	 * @param 	User			$user				User who get infos
	 * @param 	string			$type				Type data returned ('view', 'bookkeeping', 'csv')
	 * @param 	int				$date_start			Filter 'start date'
	 * @param 	int				$date_end			Filter 'end date'
	 * @param 	string			$in_bookkeeping		Filter 'in bookkeeping' ('already', 'notyet')
	 * @return 	array|int							Return integer <0 if KO, >0 if OK
	 */
	public function getAssetData(User $user, $type = 'view', $date_start = null, $date_end = null, $in_bookkeeping = 'notyet')
	{
		global $conf, $langs;

		if (!isModEnabled('asset')) {
			return array();
		}

		require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
		require_once DOL_DOCUMENT_ROOT . '/asset/class/asset.class.php';
		require_once DOL_DOCUMENT_ROOT . '/asset/class/assetaccountancycodes.class.php';
		require_once DOL_DOCUMENT_ROOT . '/asset/class/assetdepreciationoptions.class.php';

		$langs->loadLangs(array("assets"));

		// Clean parameters
		if (empty($type)) {
			$type = 'view';
		}
		if (empty($in_bookkeeping)) {
			$in_bookkeeping = 'notyet';
		}

		$sql = "";
		$sql .= "SELECT ad.fk_asset AS rowid, a.ref AS asset_ref, a.label AS asset_label, a.acquisition_value_ht AS asset_acquisition_value_ht";
		$sql .= ", a.disposal_date AS asset_disposal_date, a.disposal_amount_ht AS asset_disposal_amount_ht, a.disposal_subject_to_vat AS asset_disposal_subject_to_vat";
		$sql .= ", ad.rowid AS depreciation_id, ad.depreciation_mode, ad.ref AS depreciation_ref, ad.depreciation_date, ad.depreciation_ht, ad.accountancy_code_debit, ad.accountancy_code_credit";
		$sql .= " FROM " . MAIN_DB_PREFIX . "asset_depreciation as ad";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "asset as a ON a.rowid = ad.fk_asset";
		$sql .= " WHERE a.entity IN (" . getEntity('asset', 0) . ')'; // We don't share object for accountancy, we use source object sharing
		if ($in_bookkeeping == 'already') {
			$sql .= " AND EXISTS (SELECT iab.fk_docdet FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping AS iab WHERE iab.fk_docdet = ad.rowid AND doc_type = 'asset')";
		} elseif ($in_bookkeeping == 'notyet') {
			$sql .= " AND NOT EXISTS (SELECT iab.fk_docdet FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping AS iab WHERE iab.fk_docdet = ad.rowid AND doc_type = 'asset')";
		}
		$sql .= " AND ad.ref != ''"; // not reversal lines
		if ($date_start && $date_end) {
			$sql .= " AND ad.depreciation_date >= '" . $this->db->idate($date_start) . "' AND ad.depreciation_date <= '" . $this->db->idate($date_end) . "'";
		}
		// Define begin binding date
		if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
			$sql .= " AND ad.depreciation_date >= '" . $this->db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) . "'";
		}
		$sql .= " ORDER BY ad.depreciation_date";

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
			if (!isset($pre_data['elements'][$obj->rowid])) {
				$pre_data['elements'][$obj->rowid] = array(
					'ref' => $obj->asset_ref,
					'label' => $obj->asset_label,
					'acquisition_value_ht' => $obj->asset_acquisition_value_ht,
					'depreciation' => array(),
				);

				// Disposal infos
				if (isset($obj->asset_disposal_date)) {
					$pre_data['elements'][$obj->rowid]['disposal'] = array(
						'date' => $this->db->jdate($obj->asset_disposal_date),
						'amount' => $obj->asset_disposal_amount_ht,
						'subject_to_vat' => !empty($obj->asset_disposal_subject_to_vat),
					);
				}
			}

			$compta_debit = empty($obj->accountancy_code_debit) ? 'NotDefined' : $obj->accountancy_code_debit;
			$compta_credit = empty($obj->accountancy_code_credit) ? 'NotDefined' : $obj->accountancy_code_credit;

			$pre_data['elements'][$obj->rowid]['depreciation'][$obj->depreciation_id] = array(
				'date' => $this->db->jdate($obj->depreciation_date),
				'ref' => $obj->depreciation_ref,
				'lines' => array(
					$compta_debit => -$obj->depreciation_ht,
					$compta_credit => $obj->depreciation_ht,
				),
			);
		}

		$disposal_ref = $langs->transnoentitiesnoconv('AssetDisposal');
		$journal = $this->code;
		$journal_label = $this->label;
		$journal_label_formatted = $langs->transnoentities($journal_label);
		$now = dol_now();

		$element_static = new Asset($this->db);

		$journal_data = array();
		foreach ($pre_data['elements'] as $pre_data_id => $pre_data_info) {
			$element_static->id = $pre_data_id;
			$element_static->ref = (string) $pre_data_info["ref"];
			$element_static->label = (string) $pre_data_info["label"];
			$element_static->acquisition_value_ht = $pre_data_info["acquisition_value_ht"];
			$element_link = $element_static->getNomUrl(1, 'with_label');

			$element_name_formatted_0 = dol_trunc($element_static->label, 16);
			$label_operation = $element_static->getNomUrl(0, 'label', 16);

			$element = array(
				'ref' => dol_trunc($element_static->ref, 16, 'right', 'UTF-8', 1),
				'error' => $pre_data_info['error'],
				'blocks' => array(),
			);

			// Depreciation lines
			//--------------------
			foreach ($pre_data_info['depreciation'] as $depreciation_id => $line) {
				$depreciation_ref = $line["ref"];
				$depreciation_date = $line["date"];
				$depreciation_date_formatted = dol_print_date($depreciation_date, 'day');

				// lines
				$blocks = array();
				foreach ($line['lines'] as $account => $mt) {
					$account_infos = $this->getAccountingAccountInfos($account);

					if ($type == 'view') {
						$account_to_show = length_accounta($account);
						if (($account_to_show == "") || $account_to_show == 'NotDefined') {
							$account_to_show = '<span class="error">' . $langs->trans("AssetInAccountNotDefined") . '</span>';
						}

						$blocks[] = array(
							'date' => $depreciation_date_formatted,
							'piece' => $element_link,
							'account_accounting' => $account_to_show,
							'subledger_account' => '',
							'label_operation' => $label_operation . ' - ' . $depreciation_ref,
							'debit' => $mt < 0 ? price(-$mt) : '',
							'credit' => $mt >= 0 ? price($mt) : '',
						);
					} elseif ($type == 'bookkeeping') {
						if ($account_infos['found']) {
							$blocks[] = array(
								'doc_date' => $depreciation_date,
								'date_lim_reglement' => '',
								'doc_ref' => $element_static->ref,
								'date_creation' => $now,
								'doc_type' => 'asset',
								'fk_doc' => $element_static->id,
								'fk_docdet' => $depreciation_id, // Useless, can be several lines that are source of this record to add
								'thirdparty_code' => '',
								'subledger_account' => '',
								'subledger_label' => '',
								'numero_compte' => $account,
								'label_compte' => $account_infos['label'],
								'label_operation' => $element_name_formatted_0 . ' - ' . $depreciation_ref,
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
							$depreciation_date,                                   	// Date
							$element_static->ref,                                	// Piece
							$account_infos['code_formatted_1'],                		// AccountAccounting
							$element_name_formatted_0 . ' - ' . $depreciation_ref,  // LabelOperation
							$mt < 0 ? price(-$mt) : '',                        		// Debit
							$mt >= 0 ? price($mt) : '',                        		// Credit
						);
					}
				}
				$element['blocks'][] = $blocks;
			}

			// Disposal line
			//--------------------
			if (!empty($pre_data_info['disposal'])) {
				$disposal_date = $pre_data_info['disposal']['date'];

				if ((!($date_start && $date_end) || ($date_start <= $disposal_date && $disposal_date <= $date_end)) &&
					(!getDolGlobalString('ACCOUNTING_DATE_START_BINDING') || getDolGlobalInt('ACCOUNTING_DATE_START_BINDING') <= $disposal_date)
				) {
					$disposal_amount = $pre_data_info['disposal']['amount'];
					$disposal_subject_to_vat = $pre_data_info['disposal']['subject_to_vat'];
					$disposal_date_formatted = dol_print_date($disposal_date, 'day');
					$disposal_vat = getDolGlobalInt('ASSET_DISPOSAL_VAT') > 0 ? getDolGlobalInt('ASSET_DISPOSAL_VAT') : 20;

					// Get accountancy codes
					//---------------------------
					require_once DOL_DOCUMENT_ROOT . '/asset/class/assetaccountancycodes.class.php';
					$accountancy_codes = new AssetAccountancyCodes($this->db);
					$result = $accountancy_codes->fetchAccountancyCodes($element_static->id);
					if ($result < 0) {
						$element['error'] = $accountancy_codes->errorsToString();
					} else {
						// Get last depreciation cumulative amount
						$element_static->fetchDepreciationLines();
						foreach ($element_static->depreciation_lines as $mode_key => $depreciation_lines) {
							$accountancy_codes_list = $accountancy_codes->accountancy_codes[$mode_key];

							if (!isset($accountancy_codes_list['value_asset_sold'])) {
								continue;
							}

							$accountancy_code_value_asset_sold = empty($accountancy_codes_list['value_asset_sold']) ? 'NotDefined' : $accountancy_codes_list['value_asset_sold'];
							$accountancy_code_depreciation_asset = empty($accountancy_codes_list['depreciation_asset']) ? 'NotDefined' : $accountancy_codes_list['depreciation_asset'];
							$accountancy_code_asset = empty($accountancy_codes_list['asset']) ? 'NotDefined' : $accountancy_codes_list['asset'];
							$accountancy_code_receivable_on_assignment = empty($accountancy_codes_list['receivable_on_assignment']) ? 'NotDefined' : $accountancy_codes_list['receivable_on_assignment'];
							$accountancy_code_vat_collected = empty($accountancy_codes_list['vat_collected']) ? 'NotDefined' : $accountancy_codes_list['vat_collected'];
							$accountancy_code_proceeds_from_sales = empty($accountancy_codes_list['proceeds_from_sales']) ? 'NotDefined' : $accountancy_codes_list['proceeds_from_sales'];

							$last_cumulative_amount_ht = 0;
							$depreciated_ids = array_keys($pre_data_info['depreciation']);
							foreach ($depreciation_lines as $line) {
								$last_cumulative_amount_ht = $line['cumulative_depreciation_ht'];
								if (!in_array($line['id'], $depreciated_ids) && empty($line['bookkeeping']) && !empty($line['ref'])) {
									break;
								}
							}

							$lines = array();
							$lines[0][$accountancy_code_value_asset_sold] = -((float) $element_static->acquisition_value_ht - $last_cumulative_amount_ht);
							$lines[0][$accountancy_code_depreciation_asset] = -$last_cumulative_amount_ht;
							$lines[0][$accountancy_code_asset] = $element_static->acquisition_value_ht;

							$disposal_amount_vat = $disposal_subject_to_vat ? (float) price2num($disposal_amount * $disposal_vat / 100, 'MT') : 0;
							$lines[1][$accountancy_code_receivable_on_assignment] = -($disposal_amount + $disposal_amount_vat);
							if ($disposal_subject_to_vat) {
								$lines[1][$accountancy_code_vat_collected] = $disposal_amount_vat;
							}
							$lines[1][$accountancy_code_proceeds_from_sales] = $disposal_amount;

							foreach ($lines as $lines_block) {
								$blocks = array();
								foreach ($lines_block as $account => $mt) {
									$account_infos = $this->getAccountingAccountInfos($account);

									if ($type == 'view') {
										$account_to_show = length_accounta($account);
										if (($account_to_show == "") || $account_to_show == 'NotDefined') {
											$account_to_show = '<span class="error">' . $langs->trans("AssetInAccountNotDefined") . '</span>';
										}

										$blocks[] = array(
											'date' => $disposal_date_formatted,
											'piece' => $element_link,
											'account_accounting' => $account_to_show,
											'subledger_account' => '',
											'label_operation' => $label_operation . ' - ' . $disposal_ref,
											'debit' => $mt < 0 ? price(-$mt) : '',
											'credit' => $mt >= 0 ? price($mt) : '',
										);
									} elseif ($type == 'bookkeeping') {
										if ($account_infos['found']) {
											$blocks[] = array(
												'doc_date' => $disposal_date,
												'date_lim_reglement' => '',
												'doc_ref' => $element_static->ref,
												'date_creation' => $now,
												'doc_type' => 'asset',
												'fk_doc' => $element_static->id,
												'fk_docdet' => 0, // Useless, can be several lines that are source of this record to add
												'thirdparty_code' => '',
												'subledger_account' => '',
												'subledger_label' => '',
												'numero_compte' => $account,
												'label_compte' => $account_infos['label'],
												'label_operation' => $element_name_formatted_0 . ' - ' . $disposal_ref,
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
											$disposal_date,                                    // Date
											$element_static->ref,                              // Piece
											$account_infos['code_formatted_1'],                // AccountAccounting
											$element_name_formatted_0 . ' - ' . $disposal_ref, // LabelOperation
											$mt < 0 ? price(-$mt) : '',                        // Debit
											$mt >= 0 ? price($mt) : '',                        // Credit
										);
									}
								}
								$element['blocks'][] = $blocks;
							}
						}
					}
				}
			}

			$journal_data[(int) $pre_data_id] = $element;
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
	 *                                          id_element => array(
	 *                                          'ref' => 'ref',
	 *                                          'error' => '',
	 *                                          'blocks' => array(
	 *                                          pos_block => array(
	 *                                          num_line => array(
	 *                                          'doc_date' => '',
	 *                                          'date_lim_reglement' => '',
	 *                                          'doc_ref' => '',
	 *                                          'date_creation' => '',
	 *                                          'doc_type' => '',
	 *                                          'fk_doc' => '',
	 *                                          'fk_docdet' => '',
	 *                                          'thirdparty_code' => '',
	 *                                          'subledger_account' => '',
	 *                                          'subledger_label' => '',
	 *                                          'numero_compte' => '',
	 *                                          'label_compte' => '',
	 *                                          'label_operation' => '',
	 *                                          'montant' => '',
	 *                                          'sens' => '',
	 *                                          'debit' => '',
	 *                                          'credit' => '',
	 *                                          'code_journal' => '',
	 *                                          'journal_label' => '',
	 *                                          'piece_num' => '',
	 *                                          'import_key' => '',
	 *                                          'fk_user_author' => '',
	 *                                          'entity' => '',
	 *                                          ),
	 *                                          ),
	 *                                          ),
	 *                                          ),
	 * 											);
	 * @param	int		$max_nb_errors			Nb error authorized before stop the process
	 * @return 	int								Return integer <0 if KO, >0 if OK
	 */
	public function writeIntoBookkeeping(User $user, &$journal_data = array(), $max_nb_errors = 10)
	{
		global $conf, $langs, $hookmanager;
		require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';

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
							//
							//                          if (!$error_for_line && isModEnabled('asset') && $this->nature == 1 && $bookkeeping->fk_doc > 0) {
							//                              // Set last cumulative depreciation
							//                              require_once DOL_DOCUMENT_ROOT . '/asset/class/asset.class.php';
							//                              $asset = new Asset($this->db);
							//                              $result = $asset->setLastCumulativeDepreciation($bookkeeping->fk_doc);
							//                              if ($result < 0) {
							//                                  $error++;
							//                                  $error_for_line++;
							//                                  $journal_data[$element_id]['error'] = 'other';
							//                                  $this->errors[] = $asset->errorsToString();
							//                              }
							//                          }
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
	 *                                                  id_element => array(
	 *                                                  'continue' => false,
	 *                                                  'blocks' => array(
	 *                                                  pos_block => array(
	 *                                                  num_line => array(
	 *                                                  data to write in the CSV line
	 *                                                  ),
	 *                                                  ),
	 *                                                  ),
	 *                                                  ),
	 * 													);
	 * @param	int				$search_date_end		Search date end
	 * @param	string			$sep					CSV separator
	 * @return 	int|string								Return integer <0 if KO, >0 if OK
	 */
	public function exportCsv(&$journal_data = array(), $search_date_end = 0, $sep = '')
	{
		global $conf, $langs, $hookmanager;

		if (empty($sep)) {
			$sep = getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV');
		}
		$out = '';

		// Hook
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
					$langs->transnoentitiesnoconv("AccountingDebit"),
					$langs->transnoentitiesnoconv("AccountingCredit"),
					$langs->transnoentitiesnoconv("Journal"),
					$langs->transnoentitiesnoconv("Note"),
				);
			} elseif ($this->nature == 5) {
				$header = array(
					$langs->transnoentitiesnoconv("Date"),
					$langs->transnoentitiesnoconv("Piece"),
					$langs->transnoentitiesnoconv("AccountAccounting"),
					$langs->transnoentitiesnoconv("LabelOperation"),
					$langs->transnoentitiesnoconv("AccountingDebit"),
					$langs->transnoentitiesnoconv("AccountingCredit"),
				);
			} elseif ($this->nature == 1) {
				$header = array(
					$langs->transnoentitiesnoconv("Date"),
					$langs->transnoentitiesnoconv("Piece"),
					$langs->transnoentitiesnoconv("AccountAccounting"),
					$langs->transnoentitiesnoconv("LabelOperation"),
					$langs->transnoentitiesnoconv("AccountingDebit"),
					$langs->transnoentitiesnoconv("AccountingCredit"),
				);
			}

			if (!empty($header)) {
				$out .= '"' . implode('"' . $sep . '"', $header) . '"' . "\n";
			}
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
	public function getAccountingAccountInfos($account)
	{
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
					'label_formatted_1' => mb_convert_encoding(dol_trunc($accountingaccount->label, 32), 'ISO-8859-1'),
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
