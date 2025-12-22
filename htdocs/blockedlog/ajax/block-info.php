<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 ATM Consulting       <contact@atm-consulting.fr>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *      \file       htdocs/blockedlog/ajax/block-info.php
 *      \ingroup    blockedlog
 *      \brief      block-info
 */


// This script is called with a POST method.
// Directory to scan (full path) is inside POST['dir'].

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}


// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';


$id = GETPOSTINT('id');
$block = new BlockedLog($db);

if ((!$user->admin && !$user->hasRight('blockedlog', 'read')) || empty($conf->blockedlog->enabled)) {
	accessforbidden();
}

$langs->loadLangs(array("admin", "bills", "blockedlog", "cashdesk", "companies", "members", "products"));


/*
 * View
 */

top_httphead();

print '<div id="pop-info"><table height="80%" class="border centpercent"><thead>';
print '<th width="30%" class="left">'.$langs->trans('Field').'</th><th class="left">'.$langs->trans('Label').'</th><th class="left">'.$langs->trans('Value').'</th></thead>';
print '<tbody>';

if ($block->fetch($id) > 0) {
	$objtoshow = $block->object_data;
	print formatObject($objtoshow, '');
} else {
	print 'Error, failed to get unalterable log with id '.$id;
}

print '</tbody>';
print '</table></div>';


$db->close();


/**
 * formatObject
 *
 * @param 	Object|array<string,mixed>	$objtoshow		Object to show
 * @param	string	$prefix			Prefix of key
 * @return	string					String formatted
 */
function formatObject($objtoshow, $prefix)
{
	global $db, $langs;

	$s = '';

	$newobjtoshow = $objtoshow;

	$tmpobject = null;
	$arrayoffields = array();
	if ($prefix == 'mycompany' || $prefix == 'thirdparty') {
		include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		$tmpobject = new Societe($db);
		$arrayoffields = $tmpobject->fields;
	} elseif ($prefix == 'invoice') {
		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		$tmpobject = new Facture($db);
		$arrayoffields = $tmpobject->fields;
	} elseif ($prefix == 'invoiceline') {
		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/factureligne.class.php';
		$tmpobject = new FactureLigne($db);
		$arrayoffields = $tmpobject->fields;
	}

	// Convert the key stored into blocked log into the key used into ->fields
	$convertkey = array(
		'name' => 'nom',
		'country_code' => 'fk_pays',
		'typent_code' => 'fk_typent',
		'forme_juridique_code' => 'fk_forme_juridique',
	);

	$otherlabels = array(
		'module_source' => 'POSModule',
		'pos_source' => "POSTerminal",
		'posmodule' => 'POSModule',
		'posnumber' => 'POSTerminal',
		'pos_print_counter' => "NumberOfPrints",
		'email_sent_counter' => "NumberOfEmailsSent",
		'managers' => 'Managers',
		'type_code' => 'PaymentMode',
		'datec' => 'DateCreation',
		'dateh' => 'DateSubscription',
		'datef' => 'DateEndSubscription',
		'fk_adherent' => 'MemberId',
		'amount' => 'Amount',
		'id' => 'ID',
		'ref' => 'Ref',
		'element' => 'TypeOfEvent',
		'entity' => 'Entity',
		'label' => 'Label',
		'date' => 'Date',
		'total_ht' => 'TotalHT',
		'total_ttc' => 'TotalTTC',
		'total_tva' => 'TotalVAT',
		'total_localtax1' => 'TotalTax2',
		'total_localtax2' => 'TotalTax3',
		'multicurrency_total_ht' => 'TotalHTShortCurrency',
		'multicurrency_total_ttc' => 'TotalTTCShortCurrency',
		'multicurrency_total_tva' => 'TotalVATShortCurrency',
		'tva_tx' => 'VatRate',
		'localtax1_tx' => 'Localtax1Rate',
		'localtax2_tx' => 'Localtax2Rate',
		'vat_src_code' => 'VATCode',
		'multicurrency_code' => 'Currency',
		'qty' => 'Quantity',
		'nom' => 'Name',
		'name' => 'Name',
		'email' => 'Email',
		'state_code' => 'State',
		'revenuestamp' => 'RevenueStamp',
		'code_client' => 'CustomerCode',
		'capital' => 'Capital',
		'localtax1_assuj' => 'UseLocalTax1',
		'localtax2_assuj' => 'UseLocalTax2',
		'localtax1_value' => 'LocalTax1DefaultValue',
		'localtax2_value' => 'LocalTax2DefaultValue',
		'subprice' => 'UnitPrice',
		'product_type' => 'ProductType',
		'product_label' => 'ProductLabel',
		'type' => 'InvoiceType',
		'info_bits' => 'TVA NPR or NOT',
		'special_code' => 'Special line (WEEE line, option, id of module...)',
	);

	if (is_object($newobjtoshow) || is_array($newobjtoshow)) {
		//var_dump($newobjtoshow);
		foreach ($newobjtoshow as $key => $val) {
			if (!is_object($val) && !is_array($val)) {
				// TODO $val can be '__PHP_Incomplete_Class', the is_object return false
				$s .= '<tr>';

				// Field code
				$s .= '<td>';
				$s .= '<!-- '.$key.' '.$arrayoffields[$key]['type'].''.$arrayoffields[$convertkey[$key]]['label'].' -->';
				$s .= ($prefix ? $prefix.' > ' : '');
				$s .= $key;
				$s .= '</td>';

				// Label
				$s .= '<td>';
				$label = '';
				if (isset($arrayoffields[$key]['label'])) {
					$label = $langs->trans($tmpobject->fields[$key]['label']);
				} elseif (!empty($convertkey[$key]) && isset($arrayoffields[$convertkey[$key]]['label'])) {
					$label = $langs->trans($tmpobject->fields[$convertkey[$key]]['label']);
				} elseif ($prefix == 'mycompany' || $prefix == 'thirdparty') {
					$reg = array();
					if (preg_match('/^idprof(\d+)$/', $key, $reg)) {
						$countrycode = property_exists($newobjtoshow, 'country_code') ? ($newobjtoshow->country_code ?? '') : '';
						$label = $langs->trans("ProfId".$reg[1].$countrycode);
					}
				}
				if (empty($label) && !empty($otherlabels[$key])) {
					$label = $langs->trans($otherlabels[$key]);
				}
				if (empty($label) && array_key_exists($key, $convertkey) && array_key_exists((string) $convertkey[$key], $otherlabels)) {
					$label = $langs->trans((string) $otherlabels[(string) $convertkey[$key]]);
				}
				if (empty($label)) {
					$label = array_key_exists($key, $convertkey) ? $convertkey[$key] : '';
				}
				if (!empty($label)) {
					$s .= '<span class="opacitymedium">'.$label.'</span>';
				}

				// Value
				$s .= '<td>';
				if (in_array($key, array('date', 'datef'))) {
					$s .= dol_print_date($val, 'day');
				} elseif (in_array($key, array('dateh', 'datec', 'date_creation', 'datem', 'tms', 'date_valid', 'datep'))) {
					$s .= dol_print_date($val, 'dayhour');
				} elseif (in_array($key, array('tva_assuj', 'localtax1_assuj', 'localtax2_assuj'))) {
					$s .= yn($val);
				} elseif (in_array($key, array('product_type'))) {
					$s .= $val ? 'Product' : 'Service';
				} elseif (in_array($key, array(
					'qty', 'subprice',
					'tva_tx', 'localtax1_tx', 'localtax2_tx', 'total_ht', 'total_ttc', 'total_tva', 'total_localtax1', 'total_localtax2', 'localtax2', 'localtax2', 'revenuestamp',
					'multicurrency_total_ht', 'multicurrency_total_tva', 'multicurrency_total_ttc', 'multicurrency_subprice',
					'opening', 'cash', 'cheque', 'card',
					'amount'
				)) || (isset($arrayoffields[$key]['type']) && in_array($arrayoffields[$key]['type'], array('price')))) {
					$s .= '<span class="amount">'.price($val, 0, $langs, 1, 0, -2).'</span>';
				} else {
					$s .= $val;
				}
				$s .= '</td></tr>';
			} elseif (is_array($val)) {
				$s .= formatObject($val, ($prefix ? $prefix.' > ' : '').$key);
			} elseif (is_object($val)) {
				$s .= formatObject($val, ($prefix ? $prefix.' > ' : '').$key);
			}
		}
	}

	return $s;
}
