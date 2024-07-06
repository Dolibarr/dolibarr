<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
 * Copyright (C) 2023-2024	Patrice Andreani		<pandreani@easya.solutions>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *
 */
class FormListWebPortal
{
	/**
	 * @var string Action
	 */
	public $action = '';

	/**
	 * @var DoliDB Database
	 */
	public $db;

	/**
	 * @var Form  Instance of the Form
	 */
	public $form;

	/**
	 * @var CommonObject Object
	 */
	public $object;

	/**
	 * @var int Limit (-1 to get limit from conf, 0 no limit, or Nb to show)
	 */
	public $limit = -1;

	/**
	 * @var int Page (1 by default)
	 */
	public $page = 1;

	/**
	 * @var string Sort field
	 */
	public $sortfield = '';

	/**
	 * @var string Sort order
	 */
	public $sortorder = '';

	/**
	 * @var string Title key to translate
	 */
	public $titleKey = '';

	/**
	 * @var string Title desc key to translate
	 */
	public $titleDescKey = '';

	/**
	 * @var string Page context
	 */
	public $contextpage = '';

	/**
	 * @var array Search filters
	 */
	public $search = array();

	/**
	 * @var array Array of fields
	 */
	public $arrayfields = array();

	/**
	 * @var array Company static list (cache)
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
	 * @param	string		$elementEn		Element (english) : "propal", "order", "invoice"
	 * @return	void
	 */
	public function init($elementEn)
	{
		// keep compatibility
		if ($elementEn == 'commande') {
			$elementEn = 'order';
		} elseif ($elementEn == 'facture') {
			$elementEn = 'invoice';
		}

		// load module libraries
		dol_include_once('/webportal/class/webportal' . $elementEn . '.class.php');

		// Initialize a technical objects
		$objectclass = 'WebPortal' . ucfirst($elementEn);
		$object = new $objectclass($this->db);

		// set form list
		$this->action = GETPOST('action', 'aZ09');
		$this->object = $object;
		$this->limit = GETPOSTISSET('limit') ? GETPOSTINT('limit') : -1;
		$this->sortfield = GETPOST('sortfield', 'aZ09comma');
		$this->sortorder = GETPOST('sortorder', 'aZ09comma');
		$this->page = GETPOSTISSET('page') ? GETPOSTINT('page') : 1;
		$this->titleKey = $objectclass . 'ListTitle';

		// Initialize array of search criteria
		//$search_all = GETPOST('search_all', 'alphanohtml');
		$search = array();
		foreach ($object->fields as $key => $val) {
			if (GETPOST('search_' . $key, 'alpha') !== '') {
				$search[$key] = GETPOST('search_' . $key, 'alpha');
			}
			if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
				$postDateStart = GETPOST('search_' . $key . '_dtstart', 'alphanohtml');
				$postDateEnd = GETPOST('search_' . $key . '_dtend', 'alphanohtml');
				// extract date YYYY-MM-DD for year, month and day
				$dateStartArr = explode('-', $postDateStart);
				$dateEndArr = explode('-', $postDateEnd);
				if (count($dateStartArr) == 3) {
					$dateStartYear = (int) $dateStartArr[0];
					$dateStartMonth = (int) $dateStartArr[1];
					$dateStartDay = (int) $dateStartArr[2];
					$search[$key . '_dtstart'] = dol_mktime(0, 0, 0, $dateStartMonth, $dateStartDay, $dateStartYear);
				}
				if (count($dateEndArr) == 3) {
					$dateEndYear = (int) $dateEndArr[0];
					$dateEndMonth = (int) $dateEndArr[1];
					$dateEndDay = (int) $dateEndArr[2];
					$search[$key . '_dtend'] = dol_mktime(23, 59, 59, $dateEndMonth, $dateEndDay, $dateEndYear);
				}
			}
		}
		$this->search = $search;

		// List of fields to search into when doing a "search in all"
		//$fieldstosearchall = array();

		// Definition of array of fields for columns
		$arrayfields = array();
		foreach ($object->fields as $key => $val) {
			// If $val['visible']==0, then we never show the field
			if (!empty($val['visible'])) {
				$visible = (int) dol_eval($val['visible'], 1);
				$arrayfields['t.' . $key] = array(
					'label' => $val['label'],
					'checked' => (($visible < 0) ? 0 : 1),
					'enabled' => (abs($visible) != 3 && (int) dol_eval($val['enabled'], 1)),
					'position' => $val['position'],
					'help' => isset($val['help']) ? $val['help'] : ''
				);
			}
		}
		if ($elementEn == 'invoice') {
			$arrayfields['remain_to_pay'] = array('type' => 'price', 'label' => 'RemainderToPay', 'checked' => 1, 'enabled' => 1, 'visible' => 1, 'position' => 10000, 'help' => '',);
		}
		$arrayfields['download_link'] = array('label' => 'File', 'checked' => 1, 'enabled' => 1, 'visible' => 1, 'position' => 10001, 'help' => '',);
		if ($elementEn == "propal" && getDolGlobalString("PROPOSAL_ALLOW_ONLINESIGN") != 0) {
			$arrayfields['signature_link'] = array('label' => 'Signature', 'checked' => 1, 'enabled' => 1, 'visible' => 1, 'position' => 10002, 'help' => '',);
		}

		$object->fields = dol_sort_array($object->fields, 'position');
		//$arrayfields['anotherfield'] = array('type'=>'integer', 'label'=>'AnotherField', 'checked'=>1, 'enabled'=>1, 'position'=>90, 'csslist'=>'right');
		$arrayfields = dol_sort_array($arrayfields, 'position');

		$this->arrayfields = $arrayfields;
	}

	/**
	 * Do actions
	 *
	 * @return	void
	 */
	public function doActions()
	{
		$object = $this->object;
		$search = $this->search;

		// Purge search criteria
		if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
			foreach ($object->fields as $key => $val) {
				$search[$key] = '';
				if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
					$search[$key . '_dtstart'] = '';
					$search[$key . '_dtend'] = '';
				}
			}
			$this->search = $search;
		}
	}

	/**
	 * List for an element in the page context
	 *
	 * @param	Context		$context		Context object
	 * @return	string		Html output
	 */
	public function elementList($context)
	{
		global $conf, $hookmanager, $langs;

		$html = '';

		// initialize
		$action = $this->action;
		$object = $this->object;
		$limit = $this->limit;
		$page = $this->page;
		$sortfield = $this->sortfield;
		$sortorder = $this->sortorder;
		$titleKey = $this->titleKey;
		$contextpage = $this->contextpage;
		$search = $this->search;
		$arrayfields = $this->arrayfields;
		$elementEn = $object->element;
		if ($object->element == 'commande') {
			$elementEn = 'order';
		} elseif ($object->element == 'facture') {
			$elementEn = 'invoice';
		}

		// specific for invoice and remain to pay
		$discount = null;
		if ($elementEn == 'invoice') {
			$discount = new DiscountAbsolute($this->db);
		}

		// empty value for select
		$emptyValueKey = ($elementEn == 'order' ? -5 : -1);

		if ($limit < 0) {
			$limit = $conf->liste_limit;
		}
		if ($page <= 0) {
			$page = 1;
		}
		$offset = $limit * ($page - 1);
		if (!$sortfield) {
			reset($object->fields); // Reset is required to avoid key() to return null.
			$sortfield = 't.' . key($object->fields); // Set here default search field. By default 1st field in definition.
		}
		if (!$sortorder) {
			$sortorder = 'DESC';
		}

		$socid = (int) $context->logged_thirdparty->id;

		// Build and execute select
		// --------------------------------------------------------------------
		$sql = "SELECT ";
		$sql .= $object->getFieldList('t');
		$sql .= ", t.entity as element_entity";
		// Add fields from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		$sql .= $hookmanager->resPrint;
		$sql = preg_replace('/,\s*$/', '', $sql);

		$sqlfields = $sql; // $sql fields to remove for count total

		$sql .= " FROM " . $this->db->prefix() . $object->table_element . " as t";
		// Add table from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		$sql .= $hookmanager->resPrint;
		if ($object->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (" . getEntity($object->element, (GETPOSTINT('search_current_entity') ? 0 : 1)) . ")";
		} else {
			$sql .= " WHERE 1 = 1";
		}
		// filter on logged third-party
		$sql .= " AND t.fk_soc = " . ((int) $socid);
		// discard record with status draft
		$sql .= " AND t.fk_statut <> 0";

		foreach ($search as $key => $val) {
			if (array_key_exists($key, $object->fields)) {
				if (($key == 'status' || $key == 'fk_statut') && $search[$key] == $emptyValueKey) {
					continue;
				}
				$mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
				if ((strpos($object->fields[$key]['type'], 'integer:') === 0) || (strpos($object->fields[$key]['type'], 'sellist:') === 0) || !empty($object->fields[$key]['arrayofkeyval'])) {
					if ($search[$key] == "$emptyValueKey" || ($search[$key] === '0' && (empty($object->fields[$key]['arrayofkeyval']) || !array_key_exists('0', $object->fields[$key]['arrayofkeyval'])))) {
						$search[$key] = '';
					}
					$mode_search = 2;
				}
				if ($search[$key] != '') {
					$sql .= natural_search("t." . $this->db->escape($key), $search[$key], (($key == 'status' || $key == 'fk_statut') ? ($search[$key] < 0 ? 1 : 2) : $mode_search));
				}
			} else {
				if (preg_match('/(_dtstart|_dtend)$/', $key) && $search[$key] != '') {
					$columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
					if (preg_match('/^(date|timestamp|datetime)/', $object->fields[$columnName]['type'])) {
						if (preg_match('/_dtstart$/', $key)) {
							$sql .= " AND t." . $this->db->escape($columnName) . " >= '" . $this->db->idate($search[$key]) . "'";
						}
						if (preg_match('/_dtend$/', $key)) {
							$sql .= " AND t." . $this->db->escape($columnName) . " <= '" . $this->db->idate($search[$key]) . "'";
						}
					}
				}
			}
		}
		//if ($search_all) {
		//    $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
		//}
		// Add where from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		$sql .= $hookmanager->resPrint;

		// Count total nb of records
		$nbtotalofrecords = 0;
		if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
			/* The fast and low memory method to get and count full list converts the sql into a sql count */
			$sqlforcount = preg_replace('/^' . preg_quote($sqlfields, '/') . '/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
			$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
			$resql = $this->db->query($sqlforcount);
			if ($resql) {
				$objforcount = $this->db->fetch_object($resql);
				$nbtotalofrecords = (int) $objforcount->nbtotalofrecords;
			} else {
				dol_print_error($this->db);
			}

			if ($offset > $nbtotalofrecords) {    // if total resultset is smaller than the paging size (filtering), goto and load page 1
				$page = 1;
				$offset = 0;
			}

			$this->db->free($resql);
		}

		// Complete request and execute it with limit
		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			return '';
		}

		$num = $this->db->num_rows($resql);
		if ($limit > 0) {
			$nbpages = ceil($nbtotalofrecords / $limit);
		}
		if ($nbpages <= 0) {
			$nbpages = 1;
		}

		// make array[sort field => sort order] for this list
		$sortList = array();
		$sortFieldList = explode(",", $sortfield);
		$sortOrderList = explode(",", $sortorder);
		$sortFieldIndex = 0;
		if (!empty($sortFieldList)) {
			foreach ($sortFieldList as $sortField) {
				if (isset($sortOrderList[$sortFieldIndex])) {
					$sortList[$sortField] = $sortOrderList[$sortFieldIndex];
				}
				$sortFieldIndex++;
			}
		}

		$param = '';
		$param .= '&contextpage=' . urlencode($contextpage);
		$param .= '&limit=' . $limit;
		foreach ($search as $key => $val) {
			if (is_array($search[$key])) {
				foreach ($search[$key] as $skey) {
					if ($skey != '') {
						$param .= '&search_' . $key . '[]=' . urlencode($skey);
					}
				}
			} elseif (preg_match('/(_dtstart|_dtend)$/', $key) && !empty($val)) {
				$param .= '&search_' . $key . 'month=' . (GETPOSTINT('search_' . $key . 'month'));
				$param .= '&search_' . $key . 'day=' . (GETPOSTINT('search_' . $key . 'day'));
				$param .= '&search_' . $key . 'year=' . (GETPOSTINT('search_' . $key . 'year'));
			} elseif ($search[$key] != '') {
				$param .= '&search_' . $key . '=' . urlencode($search[$key]);
			}
		}
		// Add $param from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		$param .= $hookmanager->resPrint;

		$url_file = $context->getControllerUrl($context->controller);
		$html .= '<form method="POST" id="searchFormList" action="' . $url_file . '">' . "\n";
		$html .= $context->getFormToken();
		$html .= '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		$html .= '<input type="hidden" name="action" value="list">';
		$html .= '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
		$html .= '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
		$html .= '<input type="hidden" name="page" value="' . $page . '">';
		$html .= '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

		// pagination
		$pagination_param = $param . '&sortfield=' . $sortfield . '&sortorder=' . $sortorder;
		$html .= '<nav id="webportal-' . $elementEn . '-pagination">';
		$html .= '<ul>';
		$html .= '<li><strong>' . $langs->trans($titleKey) . '</strong> (' . $nbtotalofrecords . ')</li>';
		$html .= '</ul>';

		/* Generate pagination list */
		$html .= static::generatePageListNav($url_file . $pagination_param, $nbpages, $page);

		$html .= '</nav>';

		// table with search filters and column titles
		$html .= '<table id="webportal-' . $elementEn . '-list" responsive="scroll" role="grid">';
		// title and desc for table
		//if ($titleKey != '') {
		//    $html .= '<caption id="table-collapse-responsive">';
		//    $html .= $langs->trans($titleKey) . '<br/>';
		//    if ($titleDescKey != '') {
		//        $html .= '<small>' . $langs->trans($titleDescKey) . '</small>';
		//    }
		//    $html .= '</caption>';
		//}

		$html .= '<thead>';

		// Fields title search
		// --------------------------------------------------------------------
		$html .= '<tr role="search-row">';
		// Action column
		// if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		$html .= '<td data-col="row-checkbox" >';
		$html .= '	<button class="btn-filter-icon btn-search-filters-icon" type="submit" name="button_search_x" value="x" aria-label="'.dol_escape_htmltag($langs->trans('Search')).'" ></button>';
		$html .= '	<button class="btn-filter-icon btn-remove-search-filters-icon" type="submit" name="button_removefilter_x" value="x" aria-label="'.dol_escape_htmltag($langs->trans('RemoveSearchFilters')).'"></button>';
		$html .= '</td>';
		// }
		foreach ($object->fields as $key => $val) {
			if (!empty($arrayfields['t.' . $key]['checked'])) {
				$html .= '<td data-label="' . $arrayfields['t.' . $key]['label'] . '" data-col="'.dol_escape_htmltag($key).'" >';
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					$html .= $this->form->selectarray('search_' . $key, $val['arrayofkeyval'], (isset($search[$key]) ? $search[$key] : ''), $val['notnull'], 0, 0, '', 1, 0, 0, '', '');
				} elseif (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
					$postDateStart = GETPOST('search_' . $key . '_dtstart', 'alphanohtml');
					$postDateEnd = GETPOST('search_' . $key . '_dtend', 'alphanohtml');

					$html .= '<div class="grid">';
					$html .= $this->form->inputDate('search_' . $key . '_dtstart', $postDateStart ? $postDateStart : '', $langs->trans('From'));
					$html .= '</div>';
					$html .= '<div class="grid">';
					$html .= $this->form->inputDate('search_' . $key . '_dtend', $postDateEnd ? $postDateEnd : '', $langs->trans('to'));
					$html .= '</div>';
				} else {
					$html .= '<input type="text" name="search_' . $key . '" value="' . dol_escape_htmltag(isset($search[$key]) ? $search[$key] : '') . '">';
				}
				$html .= '</td>';
			}
		}
		// Fields from hook
		$parameters = array('arrayfields' => $arrayfields);
		$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		$html .= $hookmanager->resPrint;
		// Remain to pay
		if (!empty($arrayfields['remain_to_pay']['checked'])) {
			$html .= '<td data-label="' . $arrayfields['remain_to_pay']['label'] . '">';
			$html .= '</td>';
		}
		// Download link
		if (!empty($arrayfields['download_link']['checked'])) {
			$html .= '<td data-label="' . $arrayfields['download_link']['label'] . '">';
			$html .= '</td>';
		}
		$html .= '</tr>';
		// Signature link
		if ($elementEn == "propal" && getDolGlobalString("PROPOSAL_ALLOW_ONLINESIGN") != 0) {
			if (!empty($arrayfields['signature_link']['checked'])) {
				$html .= '<td data-label="' . $arrayfields['signature_link']['label'] . '">';
				$html .= '</td>';
			}
		}

		$totalarray = array();
		$totalarray['nbfield'] = 0;

		// Fields title label
		// --------------------------------------------------------------------
		$html .= '<tr>';
		// Action column
		// if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		$html .= '<th  data-col="row-checkbox"  ></th>';
		$totalarray['nbfield']++;
		// }
		foreach ($object->fields as $key => $val) {
			$tableKey = 't.' . $key;
			if (!empty($arrayfields[$tableKey]['checked'])) {
				$tableOrder = '';
				if (array_key_exists($tableKey, $sortList)) {
					$tableOrder = strtolower($sortList[$tableKey]);
				}
				$url_param = $url_file . '&sortfield=' . $tableKey . '&sortorder=' . ($tableOrder == 'desc' ? 'asc' : 'desc') . $param;
				$html .= '<th data-col="'.dol_escape_htmltag($key).'"  scope="col"' . ($tableOrder != '' ? ' table-order="' . $tableOrder . '"' : '') . '>';
				$html .= '<a href="' . $url_param . '">';
				$html .= $langs->trans($arrayfields['t.' . $key]['label']);
				$html .= '</a>';
				$html .= '</th>';
				$totalarray['nbfield']++;
			}
		}
		// Remain to pay
		if (!empty($arrayfields['remain_to_pay']['checked'])) {
			$html .= '<th scope="col">';
			$html .= $langs->trans($arrayfields['remain_to_pay']['label']);
			$html .= '</th>';
			$totalarray['nbfield']++;
		}
		// Download link
		if (!empty($arrayfields['download_link']['checked'])) {
			$html .= '<th scope="col">';
			$html .= $langs->trans($arrayfields['download_link']['label']);
			$html .= '</th>';
			$totalarray['nbfield']++;
		}
		// Signature link
		if ($elementEn == "propal" && getDolGlobalString("PROPOSAL_ALLOW_ONLINESIGN") != 0) {
			if (!empty($arrayfields['signature_link']['checked'])) {
				$html .= '<th scope="col">';
				$html .= $langs->trans($arrayfields['signature_link']['label']);
				$html .= '</th>';
				$totalarray['nbfield']++;
			}
		}

		// Hook fields
		$parameters = array('arrayfields' => $arrayfields, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		$html .= $hookmanager->resPrint;
		$html .= '</tr>';

		$html .= '</thead>';

		$html .= '<tbody>';

		// Store company
		$idCompany = (int) $socid;
		if (!isset($this->companyStaticList[$socid])) {
			$companyStatic = new Societe($this->db);
			$companyStatic->fetch($idCompany);
			$this->companyStaticList[$idCompany] = $companyStatic;
		}
		$companyStatic = $this->companyStaticList[$socid];

		// Loop on record
		// --------------------------------------------------------------------
		$i = 0;
		$totalarray = [
			'nbfield' => 0,
			'totalizable' => [],
		];
		$imaxinloop = ($limit ? min($num, $limit) : $num);
		while ($i < $imaxinloop) {
			$obj = $this->db->fetch_object($resql);
			if (empty($obj)) {
				break; // Should not happen
			}

			// Store properties in $object
			$object->setVarsFromFetchObj($obj);

			// specific to get invoice status (depends on payment)
			$payment = -1;
			if ($elementEn == 'invoice') {
				// paid sum
				$payment = $object->getSommePaiement();
				$totalcreditnotes = $object->getSumCreditNotesUsed();
				$totaldeposits = $object->getSumDepositsUsed();

				// remain to pay
				$totalpay = $payment + $totalcreditnotes + $totaldeposits;
				$remaintopay = price2num($object->total_ttc - $totalpay);
				if ($object->status == Facture::STATUS_CLOSED && $object->close_code == 'discount_vat') {        // If invoice closed with discount for anticipated payment
					$remaintopay = 0;
				}
				if ($object->type == Facture::TYPE_CREDIT_NOTE && $obj->paye == 1 && $discount) {
					$remaincreditnote = $discount->getAvailableDiscounts($companyStatic, '', 'rc.fk_facture_source=' . $object->id);
					$remaintopay = -$remaincreditnote;
				}
			}

			// Show line of result
			$html .= '<tr data-rowid="' . $object->id . '">';
			// if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$html .= '<td class="nowraponall">';
			$html .= '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
			// }
			foreach ($object->fields as $key => $val) {
				if (!empty($arrayfields['t.' . $key]['checked'])) {
					$html .= '<td class="nowraponall" data-label="' . $arrayfields['t.' . $key]['label'] . '">';
					if ($key == 'status' || $key == 'fk_statut') {
						if ($elementEn == 'invoice') {
							// specific to get invoice status (depends on payment)
							$html .= $object->getLibStatut(5, $payment);
						} else {
							$html .= $object->getLibStatut(5);
						}
					} elseif ($key == 'rowid') {
						$html .= $this->form->showOutputFieldForObject($object, $val, $key, $object->id, '');
					} else {
						$html .= $this->form->showOutputFieldForObject($object, $val, $key, $object->$key, '');
					}
					$html .= '</td>';


					if (!$i) {
						$totalarray['nbfield']++;
					}
					if (!empty($val['isameasure']) && $val['isameasure'] == 1) {
						if (!$i) {
							$totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
						}
						if (!isset($totalarray['val'])) {
							$totalarray['val'] = array();
						}
						if (!isset($totalarray['val']['t.' . $key])) {
							$totalarray['val']['t.' . $key] = 0;
						}
						$totalarray['val']['t.' . $key] += $object->$key;
					}
				}
			}
			// Remain to pay
			if (!empty($arrayfields['remain_to_pay']['checked'])) {
				$html .= '<td class="nowraponall" data-label="' . $arrayfields['remain_to_pay']['label'] . '">';
				$html .= $this->form->showOutputFieldForObject($object, $arrayfields['remain_to_pay'], 'remain_to_pay', $remaintopay, '');
				//$html .= price($remaintopay);
				$html .= '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Download link
			if (!empty($arrayfields['download_link']['checked'])) {
				$element = $object->element;
				$html .= '<td class="nowraponall" data-label="' . $arrayfields['download_link']['label'] . '">';
				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->{$element}->multidir_output[$obj->element_entity] . '/' . dol_sanitizeFileName($obj->ref);
				$html .= $this->form->getDocumentsLink($element, $filename, $filedir);
				$html .= '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Signature link
			if ($elementEn == "propal" && getDolGlobalString("PROPOSAL_ALLOW_ONLINESIGN") != 0) {
				if (!empty($arrayfields['signature_link']['checked'])) {
					$html .= '<td class="nowraponall" data-label="' . $arrayfields['signature_link']['label'] . '">';
					if ($object->fk_statut == Propal::STATUS_VALIDATED) {
						$html .= $this->form->getSignatureLink('proposal', $object);
					}
					$html .= '</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}
			}
			// Fields from hook
			$parameters = array('arrayfields' => $arrayfields, 'object' => $object, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			$html .= $hookmanager->resPrint;

			$html .= '</tr>';

			$i++;
		}

		// Move fields of totalizable into the common array pos and val
		if (!empty($totalarray['totalizable']) && is_array($totalarray['totalizable'])) {
			foreach ($totalarray['totalizable'] as $keytotalizable => $valtotalizable) {
				$totalarray['pos'][$valtotalizable['pos']] = $keytotalizable;
				$totalarray['val'][$keytotalizable] = isset($valtotalizable['total']) ? $valtotalizable['total'] : 0;
			}
		}
		// Show total line
		if (isset($totalarray['pos'])) {
			$html .= '<tr>';
			$i = 0;
			while ($i < $totalarray['nbfield']) {
				$i++;
				if (!empty($totalarray['pos'][$i])) {
					$html .= '<td class="nowraponall essai">';
					$html .= price(!empty($totalarray['val'][$totalarray['pos'][$i]]) ? $totalarray['val'][$totalarray['pos'][$i]] : 0);
					$html .= '</td>';
				} else {
					if ($i == 1) {
						$html .= '<td>' . $langs->trans("Total") . '</td>';
					} else {
						$html .= '<td></td>';
					}
				}
			}
			$html .= '</tr>';
		}

		// If no record found
		if ($num == 0) {
			$colspan = 1;
			foreach ($arrayfields as $key => $val) {
				if (!empty($val['checked'])) {
					$colspan++;
				}
			}
			$html .= '<tr><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
		}

		$html .= '</tbody>';

		$this->db->free($resql);

		$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
		$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		$html .= $hookmanager->resPrint;

		$html .= '</table>';

		$html .= '</form>';

		return $html;
	}

	/**
	 * Generate with pagination navigaion
	 *
	 * @param 	string	$url			Url of current page
	 * @param	int 	$nbPages		Total of pages results
	 * @param	int 	$currentPage	Number of current page
	 * @return	string
	 */
	public static function generatePageListNav(string $url, int $nbPages, int $currentPage)
	{
		global $langs;

		// Return nothing (no navigation bar), if there is only 1 page.
		if ($nbPages <= 1) {
			return '';
		}

		$pSep = strpos($url, '?') === false ? '?' : '&amp;';

		$html = '<ul class="pages-nav-list">';

		if ($currentPage > 1) {
			$html .= '<li><a class="pages-nav-list__icon --prev" aria-label="' . dol_escape_htmltag($langs->trans('AriaPrevPage')) . '" href="' . $url . $pSep . 'page=' . ($currentPage - 1) . '" ' . ($currentPage <= 1 ? ' disabled' : '') . '></a></li>';
		}

		$maxPaginItem = min($nbPages, 5);
		$minPageNum = max(1, $currentPage - 3);
		$maxPageNum = min($nbPages, $currentPage + 3);

		if ($minPageNum > 1) {
			$html .= '<li><a class="pages-nav-list__link ' . ($currentPage == 1 ? '--active' : '') . '" aria-label="' . dol_escape_htmltag($langs->trans('AriaPageX', 1)) . '" href="' . $url . $pSep . 'page=1" >1</a></li>';
			$html .= '<li>&hellip;</li>';
		}

		for ($p = $minPageNum; $p <= $maxPageNum; $p++) {
			$html .= '<li><a class="pages-nav-list__link ' . ($currentPage === $p ? '--active' : '') . '" aria-label="' . dol_escape_htmltag($langs->trans('AriaPageX', $p)) . '"  href="' . $url . $pSep . 'page=' . $p . '">' . $p . '</a></li>';
		}

		if ($maxPaginItem < $nbPages) {
			$html .= '<li>&hellip;</li>';
			$html .= '<li><a class="pages-nav-list__link ' . ($currentPage == $nbPages ? '--active' : '') . '" aria-label="' . dol_escape_htmltag($langs->trans('AriaPageX', $nbPages)) . '" href="' . $url . $pSep . 'page=' . $nbPages . '">' . $nbPages . '</a></li>';
		}

		if ($currentPage < $nbPages) {
			$html .= '<li><a class="pages-nav-list__icon --next" aria-label="' . dol_escape_htmltag($langs->trans('AriaNextPage')) . '" href="' . $url . $pSep . 'page=' . ($currentPage + 1) . '" ' . ($currentPage >= $nbPages ? ' disabled' : '') . '></a></li>';
		}

		$html .= '</ul>';

		return $html;
	}
}
