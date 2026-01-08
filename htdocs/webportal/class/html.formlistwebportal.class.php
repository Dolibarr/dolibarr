<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
 * Copyright (C) 2023-2024	Patrice Andreani		<pandreani@easya.solutions>
 * Copyright (C) 2024-2025  Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
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
 * \file       htdocs/webportal/class/html.formlistwebportal.class.php
 * \ingroup    webportal
 * \brief      File of class with all html predefined components for WebPortal
 */

require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT . '/webportal/class/html.formwebportal.class.php';

/**
 *    Class to manage generation of HTML components
 *    Only common components for WebPortal must be here.
 */
class FormListWebPortal
{
	/**
	 * @var DoliDB Database
	 */
	public $db;

	/**
	 * @var	AbstractListController Controller handler
	 */
	public $controller;
	/**
	 * @var string Element (english) : "propal", "order", "invoice"
	 */
	public $element = '';
	/**
	 * @var string Page context
	 */
	public $contextpage = '';
	/**
	 * @var string Action
	 */
	public $action = '';

	/**
	 * @var FormWebPortal  Instance of the Form
	 */
	public $form;
	/**
	 * @var CommonObject Object
	 */
	public $object;

	/**
	 * @var string Title key to translate
	 */
	public $titleKey = '';
	/**
	 * @var string Title desc key to translate
	 */
	public $titleDescKey = '';

	/**
	 * @var int Limit (-1 to get limit from conf, 0 no limit, or Nb to show)
	 */
	public $limit = -1;
	/**
	 * @var int Page (1 by default)
	 */
	public $page = 1;

	/**
	 * @var string		Request SQL for SELECT part
	 */
	public $sql_select = '';
	/**
	 * @var string		Request SQL for body part (FROM, LEFT JOIN, WHERE, ...)
	 */
	public $sql_body = '';
	/**
	 * @var string		Request SQL for ORDER BY part (and LIMIT, ...)
	 */
	public $sql_order = '';
	/**
	 * @var string		Empty value for select filters
	 */
	public $emptyValueKey = '';

	/**
	 * @var int Offset (0 by default)
	 */
	public $offset = 0;
	/**
	 * @var string Sort field
	 */
	public $sortfield = '';
	/**
	 * @var string Sort order
	 */
	public $sortorder = '';

	/**
	 * @var array<string,array{type?:string,label:string,checked:int<0,1>,visible:int<0,1>,enabled:bool|int<0,1>,position:int,help:string}>	Array of fields
	 */
	public $arrayfields = array();
	/**
	 * @var array<string,mixed> Search filters
	 */
	public $search = array();
	/**
	 * @var string Search all
	 */
	public $search_all = '';
	/**
	 * @var array<string,string> Fields for search all
	 */
	public $fields_to_search_all = array();
	/**
	 * @var string Params for links
	 */
	public $params = '';

	/**
	 * @var int Nb total results (0 by default)
	 */
	public $nbtotalofrecords = 0;
	/**
	 * @var array<int,stdClass> Object records from the SQL request
	 */
	public $records = [];
	/**
	 * @var int Nb column in the table
	 */
	public $nbColumn = 0;

	/**
	 * @var array<int,Societe> Company static list (cache)
	 */
	public $companyStaticList = array();


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->form = new FormWebPortal($this->db);
	}

	/**
	 * Init
	 *
	 * @param	AbstractListController	$controller		Controller handler
	 * @param	string					$elementEn		Element (english) : "propal", "order", "invoice"
	 * @return	void
	 */
	public function init(&$controller, $elementEn)
	{
		global $conf;

		$this->controller = &$controller;

		// keep compatibility
		if ($elementEn == 'commande') {
			$elementEn = 'order';
		} elseif ($elementEn == 'facture') {
			$elementEn = 'invoice';
		}

		$objectclass = 'WebPortal' . ucfirst($elementEn);
		if (!is_object($this->object) && dol_include_once('/webportal/class/webportal' . $elementEn . '.class.php') > 0) {
			// Initialize a technical objects
			$this->object = new $objectclass($this->db);
		}

		// Set form list
		$this->element = $elementEn;
		$this->action = GETPOST('action', 'aZ09');
		$this->limit = GETPOSTISSET('limit') ? GETPOSTINT('limit') : -1;
		$this->sortfield = GETPOST('sortfield', 'aZ09comma');
		$this->sortorder = GETPOST('sortorder', 'aZ09comma');
		$this->page = GETPOSTISSET('page') ? GETPOSTINT('page') : 1;
		if (empty($this->titleKey)) {
			$this->titleKey = $objectclass . 'ListTitle';
		}
		if (empty($this->titleDescKey)) {
			$this->titleDescKey = $objectclass . 'ListDesc';
		}
		$this->fields_to_search_all = array();
		if ($this->limit < 0) {
			$this->limit = $conf->liste_limit;
		}
		if ($this->page <= 0) {
			$this->limit = 1;
		}
		if (!$this->sortfield && is_object($this->object)) {
			reset($this->object->fields); // Reset is required to avoid key() to return null.
			$key = key($this->object->fields);
			$alias = $this->object->fields[$key]['alias'] ?? 't.';
			$this->sortfield = $alias . $key; // Set here default search field. By default 1st field in definition.
		}
		if (!$this->sortorder) {
			$this->sortorder = 'DESC';
		}
		$this->emptyValueKey = ($elementEn == 'order' ? "-5" : "-1");

		// Sort object fields
		if (is_object($this->object)) {
			$this->object->fields = dol_sort_array($this->object->fields, 'position');
		}

		// Initialize array of search criteria
		$this->setSearchValues();

		// Definition of array of fields for columns
		$this->setArrayFields();

		// Sort array of fields for columns
		$this->arrayfields = dol_sort_array($this->arrayfields, 'position');
	}

	/**
	 * Do actions
	 *
	 * @return	void
	 */
	public function doActions()
	{
		// Purge search criteria
		if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
			$this->setSearchValues(true);
		}
	}

	/**
	 * Set array fields
	 *
	 * @return	void
	 */
	public function setArrayFields()
	{
		// Definition of array of fields for columns
		$this->arrayfields = array();
		if (is_object($this->object)) {
			foreach ($this->object->fields as $key => $val) {
				// If $val['visible']==0, then we never show the field
				if (!empty($val['visible'])) {
					$visible = (int) dol_eval((string) $val['visible'], 1);
					$alias = $val['alias'] ?? 't.';
					$this->arrayfields[$alias . $key] = array(
						'label' => $val['label'],
						'checked' => (($visible < 0) ? 0 : 1),
						'enabled' => (int) (abs($visible) != 3 && (bool) dol_eval($val['enabled'], 1)),
						'position' => $val['position'],
						'help' => isset($val['help']) ? $val['help'] : ''
					);
				}
			}
		}
		$this->arrayfields['remain_to_pay'] = array('type' => 'price', 'label' => 'RemainderToPay', 'checked' => 1, 'enabled' => $this->element == 'invoice' && isModEnabled('invoice'), 'visible' => 1, 'position' => 10000, 'help' => '',);
		$this->arrayfields['download_link'] = array('type' => '', 'label' => 'File', 'checked' => 1, 'enabled' => ($this->element == 'propal' && isModEnabled('propal')) || ($this->element == 'order' && isModEnabled('order')) || ($this->element == 'invoice' && isModEnabled('invoice')), 'visible' => 1, 'position' => 10001, 'help' => '',);
		$this->arrayfields['signature_link'] = array('type' => '', 'label' => 'Signature', 'checked' => 1, 'enabled' => $this->element == 'propal' && isModEnabled('propal') && getDolGlobalString("PROPOSAL_ALLOW_ONLINESIGN") != 0, 'visible' => 1, 'position' => 10002, 'help' => '',);

		$this->controller->listSetArrayFields();
	}

	/**
	 * Set columns visibility
	 *
	 * @return	void
	 */
	public function setColumnsVisibility()
	{
		// Overwrite checked property for columns visibility
		if (!empty($this->arrayfields)) {
			foreach ($this->arrayfields as $key => $val) {
				$this->arrayfields[$key]['checked'] &= $val['enabled'];
			}
		}
	}

	/**
	 * Set search values
	 *
	 * @param	bool		$clear		Clear search values
	 * @return	void
	 */
	public function setSearchValues($clear = false)
	{
		// Initialize array of search criteria
		$this->search = array();
		if (is_object($this->object)) {
			foreach ($this->object->fields as $key => $val) {
				if ($clear) {
					$this->search[$key] = '';
					if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
						$this->search[$key . '_dtstart'] = '';
						$this->search[$key . '_dtend'] = '';
						$this->search[$key . '_dtstartmonth'] = '';
						$this->search[$key . '_dtendmonth'] = '';
						$this->search[$key . '_dtstartday'] = '';
						$this->search[$key . '_dtendday'] = '';
						$this->search[$key . '_dtstartyear'] = '';
						$this->search[$key . '_dtendyear'] = '';
					}
				} else {
					if (GETPOST('search_' . $key, 'alpha') !== '') {
						$this->search[$key] = GETPOST('search_' . $key, 'alpha');
					}
					if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
						/* Fix: this is not compatible with multilangage date format, replaced with dolibarr method
						$postDateStart = GETPOST('search_' . $key . '_dtstart', 'alphanohtml');
						$postDateEnd = GETPOST('search_' . $key . '_dtend', 'alphanohtml');
						// extract date YYYY-MM-DD for year, month and day
						$dateStartArr = explode('-', $postDateStart);
						$dateEndArr = explode('-', $postDateEnd);
						if (count($dateStartArr) == 3) {
							$dateStartYear = (int) $dateStartArr[0];
							$dateStartMonth = (int) $dateStartArr[1];
							$dateStartDay = (int) $dateStartArr[2];
							$this->search[$key . '_dtstart'] = dol_mktime(0, 0, 0, $dateStartMonth, $dateStartDay, $dateStartYear);
						}
						if (count($dateEndArr) == 3) {
							$dateEndYear = (int) $dateEndArr[0];
							$dateEndMonth = (int) $dateEndArr[1];
							$dateEndDay = (int) $dateEndArr[2];
							$this->search[$key . '_dtend'] = dol_mktime(23, 59, 59, $dateEndMonth, $dateEndDay, $dateEndYear);
						}
						*/
						$this->search[$key . '_dtstart'] = dol_mktime(0, 0, 0, GETPOSTINT('search_'.$key.'_dtstartmonth'), GETPOSTINT('search_'.$key.'_dtstartday'), GETPOSTINT('search_'.$key.'_dtstartyear'));
						$this->search[$key . '_dtend'] = dol_mktime(23, 59, 59, GETPOSTINT('search_'.$key.'_dtendmonth'), GETPOSTINT('search_'.$key.'_dtendday'), GETPOSTINT('search_'.$key.'_dtendyear'));
						$this->search[$key . '_dtstartmonth'] = GETPOSTINT('search_' . $key . '_dtstartmonth');
						$this->search[$key . '_dtstartday'] = GETPOSTINT('search_' . $key . '_dtstartday');
						$this->search[$key . '_dtstartyear'] = GETPOSTINT('search_' . $key . '_dtstartyear');
						$this->search[$key . '_dtendmonth'] = GETPOSTINT('search_' . $key . '_dtendmonth');
						$this->search[$key . '_dtendday'] = GETPOSTINT('search_' . $key . '_dtendday');
						$this->search[$key . '_dtendyear'] = GETPOSTINT('search_' . $key . '_dtendyear');
					}
				}
			}
		}

		// List of fields to search into when doing a "search in all"
		$this->search_all = GETPOST('search_all', 'alphanohtml');

		$this->controller->listSetSearchValues($clear);
	}

	/**
	 * set SQL request
	 *
	 * @return	void
	 */
	public function setSqlRequest()
	{
		global $hookmanager;

		if (is_object($this->object)) {
			$context = Context::getInstance();

			// Build and execute select
			// --------------------------------------------------------------------
			$this->sql_select = "SELECT ";
			$this->sql_select .= $this->object->getFieldList('t');
			if ($this->object->ismultientitymanaged == 1) {
				$this->sql_select .= ", t.entity as element_entity, t.entity";
			}
			// Add fields from hooks
			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $context);
			$this->sql_select .= $hookmanager->resPrint;
			$this->sql_select = preg_replace('/,\s*$/', '', $this->sql_select);

			$this->sql_body = " FROM " . $this->db->prefix() . $this->object->table_element . " as t";
			// Add table from hooks
			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $context);
			$this->sql_body .= $hookmanager->resPrint;
			if ($this->object->ismultientitymanaged == 1) {
				$this->sql_body .= " WHERE t.entity IN (" . getEntity($this->object->element, (GETPOSTINT('search_current_entity') ? 0 : 1)) . ")";
			} else {
				$this->sql_body .= " WHERE 1 = 1";
			}

			foreach ($this->search as $key => $val) {
				if (array_key_exists($key, $this->object->fields)) {
					if (($key == 'status' || $key == 'fk_statut') && $val == $this->emptyValueKey) {
						continue;
					}
					if (!isset($this->object->fields[$key])) {
						continue;
					}
					$field_spec = $this->object->fields[$key];
					//  @phpstan-ignore-next-line
					$alias = $field_spec['alias'] ?? 't.';
					$mode_search = (($this->object->isInt($field_spec) || $this->object->isFloat($field_spec)) ? 1 : 0);
					if ((strpos($field_spec['type'], 'integer:') === 0) || (strpos($field_spec['type'], 'sellist:') === 0) || !empty($field_spec['arrayofkeyval'])) {
						if ($val == "$this->emptyValueKey" || ($val === '0' && (empty($field_spec['arrayofkeyval']) || !array_key_exists('0', $field_spec['arrayofkeyval'])))) {
							$val = '';
						}
						$mode_search = 2;
					}
					if ($field_spec['type'] === 'boolean') {
						$mode_search = 1;
						if ($val == "$this->emptyValueKey") {
							$val = '';
						}
					}
					//  @phpstan-ignore-next-line
					if (empty($field_spec['searchmulti'])) {
						if (!is_array($val) && $val != '') {
							$this->sql_body .= natural_search($alias . $this->db->escape($key), $val, (($key == 'status') ? 2 : $mode_search));
						}
					} else {
						if (is_array($val) && !empty($val)) {
							$this->sql_body .= natural_search($alias . $this->db->escape($key), implode(',', $val), (($key == 'status') ? 2 : $mode_search));
						}
					}
				} elseif (preg_match('/(_dtstart|_dtend)$/', $key) && $val != '') {
					$columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
					if (array_key_exists($columnName, $this->object->fields)) {
						$field_spec = $this->object->fields[$columnName];
						//  @phpstan-ignore-next-line
						$alias = $field_spec['alias'] ?? 't.';
						if (preg_match('/^(date|timestamp|datetime)/', $field_spec['type'])) {
							if (preg_match('/_dtstart$/', $key)) {
								$this->sql_body .= " AND " . $alias . $this->db->sanitize($columnName) . " >= '" . $this->db->idate((int) $val) . "'";
							}
							if (preg_match('/_dtend$/', $key)) {
								$this->sql_body .= " AND " . $alias . $this->db->sanitize($columnName) . " <= '" . $this->db->idate((int) $val) . "'";
							}
						}
					}
				}
			}
			if (!empty($this->search_all) && !empty($this->fields_to_search_all)) {
				$this->sql_body .= natural_search(array_keys($this->fields_to_search_all), $this->search_all);
			}
			// Add where from hooks
			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $context);
			$this->sql_body .= $hookmanager->resPrint;

			if ($this->page <= 0) {
				$this->page = 1;
			}
			$this->offset = $this->limit * ($this->page - 1);

			$this->sql_order = $this->db->order($this->sortfield, $this->sortorder);
			if ($this->limit) {
				$this->sql_order .= $this->db->plimit($this->limit, $this->offset);
			}
		}
	}

	/**
	 * Load record from SQL request
	 *
	 * @return	void
	 */
	public function loadRecords()
	{
		$this->records = [];
		$this->nbtotalofrecords = 0;

		if (!empty($this->sql_select) && !empty($this->sql_body)) {
			// Count total nb of records
			if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
				/* The fast and low memory method to get and count full list converts the sql into a sql count */
				$resql = $this->db->query('SELECT COUNT(*) as nbtotalofrecords' . $this->sql_body);
				if ($resql) {
					$obj = $this->db->fetch_object($resql);
					$this->nbtotalofrecords = (int) $obj->nbtotalofrecords;
					$this->db->free($resql);
				} else {
					dol_print_error($this->db);
				}

				if ($this->offset > $this->nbtotalofrecords) {    // if total resultset is smaller than the paging size (filtering), goto and load page 1
					$this->page = 1;
					$this->offset = 0;
				}
			}

			$resql = $this->db->query($this->sql_select . $this->sql_body . $this->sql_order);
			if (!$resql) {
				dol_print_error($this->db);
			} else {
				// Load records
				while ($obj = $this->db->fetch_object($resql)) {
					$this->records[] = $obj;
				}
				$this->db->free($resql);
			}
		}
	}

	/**
	 * Set params
	 *
	 * @return	void
	 */
	public function setParams()
	{
		global $hookmanager;

		$context = Context::getInstance();

		$this->params = '&amp;contextpage=' . urlencode($this->contextpage);
		$this->params .= '&amp;limit=' . $this->limit;
		foreach ($this->search as $key => $val) {
			if (is_array($val)) {
				foreach ($val as $skey) {
					if ($skey != '') {
						$this->params .= '&amp;search_' . $key . '[]=' . urlencode($skey);
					}
				}
			} elseif (preg_match('/(_dtstart|_dtend)$/', $key) && !empty($val)) {
				$this->params .= '&amp;search_' . $key . 'month=' . urlencode($this->search['search_' . $key . 'month']);
				$this->params .= '&amp;search_' . $key . 'day=' . urlencode($this->search['search_' . $key . 'day']);
				$this->params .= '&amp;search_' . $key . 'year=' . urlencode($this->search['search_' . $key . 'year']);
			} elseif ($val != '') {
				$this->params .= '&amp;search_' . $key . '=' . urlencode($val);
			}
		}

		// Add $param from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $context);
		$this->params .= $hookmanager->resPrint;
	}

	/**
	 * Print input field for search list
	 *
	 * @param	string					$field_key		Field key
	 * @param	array<string,mixed>		$field_spec		Field specification
	 * @return	string									HTML input
	 */
	public function printSearchInput($field_key, $field_spec)
	{
		global $langs;

		if (!empty($field_spec['arrayofkeyval']) && is_array($field_spec['arrayofkeyval'])) {
			$out = $this->form->selectarray('search_' . $field_key, $field_spec['arrayofkeyval'], (isset($this->search[$field_key]) ? $this->search[$field_key] : ''), $field_spec['notnull'], 0, 0, '', 1, 0, 0, '', '');
		} elseif (preg_match('/^(date|timestamp|datetime)/', $field_spec['type'])) {
			$postDateStart = dol_mktime(0, 0, 0, (int) $this->search[$field_key . '_dtstartmonth'], (int) $this->search[$field_key . '_dtstartday'], (int) $this->search[$field_key . '_dtstartyear']);
			$postDateEnd = dol_mktime(0, 0, 0, (int) $this->search[$field_key . '_dtendmonth'], (int) $this->search[$field_key . '_dtendday'], (int) $this->search[$field_key . '_dtendyear']);

			$out = '<div class="grid width150">';
			$out .= $this->form->inputDate('search_' . $field_key . '_dtstart', $postDateStart ? $postDateStart : '', $langs->trans('From'));
			$out .= '</div>';
			$out .= '<div class="grid width150">';
			$out .= $this->form->inputDate('search_' . $field_key . '_dtend', $postDateEnd ? $postDateEnd : '', $langs->trans('to'));
			$out .= '</div>';
		} else {
			$out = '<input type="text" name="search_' . $field_key . '" value="' . dol_escape_htmltag(isset($this->search[$field_key]) ? $this->search[$field_key] : '') . '">';
		}

		return $out;
	}

	/**
	 * Function to load data from a SQL pointer into properties of current object $this
	 *
	 * @param   stdClass    $record    Contain data of object from database
	 * @return	void
	 */
	public function setVarsFromFetchObj(&$record)
	{
		if (is_object($this->object)) {
			$this->object->setVarsFromFetchObj($record);

			// specific to get invoice status (depends on payment)
			if ($this->element == 'invoice') {
				$discount = new DiscountAbsolute($this->db);

				// Store company
				$idCompany = (int) $this->object->socid;
				if (!isset($this->companyStaticList[$idCompany])) {
					$companyStatic = new Societe($this->db);
					$companyStatic->fetch($idCompany);
					$this->companyStaticList[$idCompany] = $companyStatic;
				}
				$companyStatic = $this->companyStaticList[$idCompany];

				// paid sum
				$payment = $this->object->getSommePaiement();
				$totalcreditnotes = $this->object->getSumCreditNotesUsed();
				$totaldeposits = $this->object->getSumDepositsUsed();

				// remain to pay
				$totalpay = $payment + $totalcreditnotes + $totaldeposits;
				$remaintopay = price2num($this->object->total_ttc - $totalpay);
				if ($this->object->status == Facture::STATUS_CLOSED && $this->object->close_code == 'discount_vat') {        // If invoice closed with discount for anticipated payment
					$remaintopay = 0;
				}
				if ($this->object->type == Facture::TYPE_CREDIT_NOTE && $this->object->paye == 1) {
					// @phan-suppress-next-line PhanTypeMismatchArgumentProbablyReal
					$remaincreditnote = $discount->getAvailableDiscounts($companyStatic, '', 'rc.fk_facture_source=' . $this->object->id);
					$remaintopay = -$remaincreditnote;
				}

				$record->invoice_payment = $payment;
				$record->invoice_remaintopay = $remaintopay;
			}
		}
	}

	/**
	 * Print value for list
	 *
	 * @param	string					$field_key		Field key
	 * @param	array<string,mixed>		$field_spec		Field specification
	 * @param	stdClass				$record			Contain data of object from database
	 * @param	int						$i				Index line (0, 1, 2, ...)
	 * @param	array<string,mixed>		$totalarray		Array for total line
	 * @return	string									HTML input
	 */
	public function printValue($field_key, $field_spec, &$record, $i, &$totalarray)
	{
		global $conf;

		$out = '';
		if (is_object($this->object)) {
			$out = $this->controller->listPrintValueBefore($field_key, $field_spec, $record);
			if (empty($out)) {
				if ($field_key == 'status' || $field_key == 'fk_statut') {
					if ($this->element == 'invoice') {
						// specific to get invoice status (depends on payment)
						$out = $this->object->getLibStatut(5, $record->invoice_payment);
					} else {
						$out = $this->object->getLibStatut(5);
					}
				} elseif ($field_key == 'rowid') {
					$out = $this->form->showOutputFieldForObject($this->object, $field_spec, $field_key, (string) $this->object->id, '');
				} elseif ($field_key == 'remain_to_pay') {
					if ($this->element == 'invoice') {
						$out = $this->form->showOutputFieldForObject($this->object, $this->arrayfields['remain_to_pay'], 'remain_to_pay', $record->invoice_remaintopay, '');
					}
				} elseif ($field_key == 'download_link') {
					$element = $this->element;
					$filename = dol_sanitizeFileName($this->object->ref);
					$filedir = $conf->{$element}->multidir_output[$this->object->entity] . '/' . dol_sanitizeFileName($this->object->ref);
					$out = $this->form->getDocumentsLink($element, $filename, $filedir);
				} elseif ($field_key == 'signature_link') {
					if ($this->object->fk_statut == Propal::STATUS_VALIDATED) {
						$out = $this->form->getSignatureLink('proposal', $this->object);
					}
				} else {
					$out = $this->form->showOutputFieldForObject($this->object, $field_spec, $field_key, $this->object->$field_key, '');
				}
			}

			$out = $this->controller->listPrintValueAfter($field_key, $field_spec, $record, $out);
		}

		return $out;
	}

	/**
	 * Set total value for list
	 *
	 * @param	string					$field_key		Field key
	 * @param	array<string,mixed>		$field_spec		Field specification
	 * @param	stdClass				$record			Contain data of object from database
	 * @param	int						$i				Index line (0, 1, 2, ...)
	 * @param	array<string,mixed>		$totalarray		Array for total line
	 * @return	void
	 */
	public function setTotalValue($field_key, $field_spec, &$record, $i, &$totalarray)
	{
		if (is_object($this->object)) {
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!empty($field_spec['isameasure']) && $field_spec['isameasure'] == 1) {
				$alias = $field_spec['alias'] ?? 't.';
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = $alias . $field_key;
				}
				if (!isset($totalarray['val'])) {
					$totalarray['val'] = array();
				}
				if (!isset($totalarray['val'][$alias . $field_key])) {
					$totalarray['val'][$alias . $field_key] = 0;
				}
				$totalarray['val'][$alias . $field_key] += $this->object->$field_key;
			}
		}
	}

	/**
	 * Get class css list
	 *
	 * @param	string					$field_key		Field key
	 * @param	array<string,mixed>		$field_spec		Field specification
	 * @param	bool					$for_value		For td of value
	 * @return	string									Class used for list <td class="xxxx">
	 */
	public function getClasseCssList($field_key, $field_spec, $for_value = false)
	{
		$cssforfield = (empty($field_spec['csslist']) ? (empty($field_spec['css']) ? '' : $field_spec['css']) : $field_spec['csslist']);
		if ($field_key == 'status') {
			$cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		} elseif (in_array($field_spec['type'], array('date', 'datetime', 'timestamp'))) {
			$cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		} elseif (in_array($field_spec['type'], array('timestamp'))) {
			$cssforfield .= ($cssforfield ? ' ' : '') . ($for_value ? 'nowraponall' : 'nowrap');
		} elseif ($for_value && $field_key == 'ref') {
			$cssforfield .= ($cssforfield ? ' ' : '') . 'nowraponall';
		} elseif (in_array($field_spec['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && !in_array($field_key, array('id', 'rowid', 'ref', 'status')) && empty($field_spec['arrayofkeyval'])) {
			$cssforfield .= ($cssforfield ? ' ' : '') . 'right';
		}

		return $cssforfield;
	}
}
