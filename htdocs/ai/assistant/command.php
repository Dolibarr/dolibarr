<?php
/* Copyright (C) 2026	Nick Fragoulis
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
 * \file    htdocs/ai/assistant/command.php
 * \ingroup ai
 * \brief   Commands of the chat's "/" palette: tasks that take several pages in
 *          Dolibarr, done from one line. No AI provider is involved.
 *          - /invoice, /order, /proposal, /supplier-invoice, /supplier-order:
 *            a draft from "customer qty [unit] product [price]; ...", through
 *            the API bridge, so the write still stops at the confirmation.
 *          - /total: totals of the rows ticked on a list (or of the rows shown).
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', 1);
}
// The payload is read from the raw php://input body: the token is checked by aiCheckCsrfToken() below.
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ai/class/mcp.class.php';
require_once DOL_DOCUMENT_ROOT.'/ai/lib/ai.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

// Same gates as execute_tool.php.
if (!isModEnabled('ai') || !getDolGlobalString('AI_ASSISTANT_ENABLED')) {
	accessforbidden('Module or feature not allowed');
}
if (!$user->hasRight('ai', 'assistant', 'use')) {
	accessforbidden();
}
aiCheckCsrfToken('ai/assistant/command.php');

top_httphead('application/json');
$langs->loadLangs(array('main', 'other', 'bills'));

// Document commands: the bridge tool that writes, and the word the syntax uses
// for the third party.
$documentCommands = array(
	'invoice' => array('tool' => 'api_invoices_create', 'label' => 'AICmdInvoice', 'who' => 'Customer'),
	'order' => array('tool' => 'api_orders_create', 'label' => 'AICmdOrder', 'who' => 'Customer'),
	'proposal' => array('tool' => 'api_proposals_create', 'label' => 'AICmdProposal', 'who' => 'Customer'),
	'supplier-invoice' => array('tool' => 'api_supplier_invoices_create', 'label' => 'AICmdSupplierInvoice', 'who' => 'Supplier', 'ref' => true),
	'supplier-order' => array('tool' => 'api_supplier_orders_create', 'label' => 'AICmdSupplierOrder', 'who' => 'Supplier'),
);
// Lists /total can add up: table, read right, and the payments table for invoices.
$totalElements = array(
	'facture' => array('table' => 'facture', 'right' => array('facture', 'lire'), 'paid' => array('paiement_facture', 'fk_facture')),
	'invoice_supplier' => array('table' => 'facture_fourn', 'right' => array('fournisseur', 'facture', 'lire'), 'paid' => array('paiementfourn_facturefourn', 'fk_facturefourn')),
	'commande' => array('table' => 'commande', 'right' => array('commande', 'lire')),
	'propal' => array('table' => 'propal', 'right' => array('propal', 'lire')),
	'order_supplier' => array('table' => 'commande_fournisseur', 'right' => array('fournisseur', 'commande', 'lire')),
	'supplier_proposal' => array('table' => 'supplier_proposal', 'right' => array('supplier_proposal', 'lire')),
);

$mcp = new McpHandler($db, $user, $conf, McpHandler::CTX_ASSISTANT);
$mcp->loadTools();
// What this user may run: the same filter as the model gets (rights and allow-list).
$allowedTools = array_flip(array_column($mcp->getToolsSchemaForLLM(), 'name'));

// --- The list of commands, for the palette ---------------------------------
if (GETPOST('action', 'aZ09') === 'list') {
	$list = array();
	foreach ($documentCommands as $cmd => $def) {
		if (!isset($allowedTools[$def['tool']])) {
			continue;
		}
		$list[] = array(
			'name' => $cmd,
			'label' => $langs->transnoentitiesnoconv($def['label']),
			'syntax' => '/'.$cmd.' '.$langs->transnoentitiesnoconv('AICmdSyntaxDocument', dol_strtolower($langs->transnoentitiesnoconv($def['who']))).(!empty($def['ref']) ? ' ref:...' : '')
		);
	}
	$list[] = array('name' => 'total', 'label' => $langs->transnoentitiesnoconv('AICmdTotal'), 'syntax' => '/total');
	print json_encode(array('commands' => $list));
	$db->close();
	exit;
}

// --- Suggestions while typing: third parties, then products ----------------
if (GETPOST('action', 'aZ09') === 'suggest') {
	$kind = GETPOST('kind', 'aZ09');
	$q = trim(GETPOST('q', 'restricthtml'));
	$cmdName = GETPOST('cmd', 'alphanohtml');
	$side = (strpos($cmdName, 'supplier') === 0) ? 'buy' : 'sell';
	$items = array();
	$consumed = $q;
	if ($kind === 'thirdparty' && dol_strlen($q) >= 2 && $user->hasRight('societe', 'lire')) {
		// Every word somewhere in the name or the trade name; only third parties the user sees.
		$sql = "SELECT s.rowid, s.nom, s.name_alias, s.town, s.client, s.fournisseur FROM ".$db->prefix()."societe as s";
		$sql .= " WHERE s.entity IN (".getEntity('societe').") AND s.status = 1";
		foreach (array_slice(preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY) ?: array(), 0, 6) as $w) {
			$like = $db->escape($db->escapeforlike($w));
			$sql .= " AND (s.nom LIKE '%".$like."%' OR s.name_alias LIKE '%".$like."%')";
		}
		// As the lists of Dolibarr: an external user sees their own company, an
		// internal one without "see all customers" the ones they follow.
		if ($user->socid > 0) {
			$sql .= " AND s.rowid = ".((int) $user->socid);
		} elseif (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " AND s.rowid IN (SELECT sc.fk_soc FROM ".$db->prefix()."societe_commerciaux as sc WHERE sc.fk_user = ".((int) $user->id).")";
		}
		$sql .= " ORDER BY s.nom LIMIT 10";
		$resql = $db->query($sql);
		$langs->load('companies');
		while ($resql && ($obj = $db->fetch_object($resql))) {
			// Customer, prospect, supplier: what the third party is, then where.
			$roles = array();
			if (in_array((int) $obj->client, array(1, 3), true)) {
				$roles[] = $langs->transnoentitiesnoconv('Customer');
			}
			if (in_array((int) $obj->client, array(2, 3), true)) {
				$roles[] = $langs->transnoentitiesnoconv('Prospect');
			}
			if ((int) $obj->fournisseur === 1) {
				$roles[] = $langs->transnoentitiesnoconv('Supplier');
			}
			$detail = implode(', ', array_filter(array(implode(' / ', $roles), (string) $obj->town)));
			$items[] = array('value' => (string) $obj->nom, 'ref' => (string) $obj->nom, 'label' => (string) $obj->nom.((string) $obj->name_alias !== '' ? ' ('.$obj->name_alias.')' : ''), 'detail' => $detail);
		}
	} elseif ($kind === 'product') {
		// A unit said before the product ("4 pieces pop") is not part of the search.
		$words = preg_split('/\s+/', trim(str_replace('"', ' ', ltrim($q, '#'))), -1, PREG_SPLIT_NO_EMPTY) ?: array();
		if (count($words) >= 1 && aiUnitIdFromName($db, $words[0]) > 0 && (count($words) > 1 || substr($q, -1) === ' ')) {
			array_shift($words);
		}
		$consumed = implode(' ', $words);
		if ($consumed !== '') {
			$found = aiFindProducts($db, $user, $consumed, $side, 10);
		} else {
			// Nothing typed yet: what this third party had last.
			$socid = GETPOSTINT('socid');
			$name = trim(GETPOST('name', 'restricthtml'));
			if ($socid <= 0 && $name !== '') {
				$best = array('words' => 0, 'candidates' => array());
				foreach (aiFindThirdpartiesInText($db, $user, $name) as $mt) {
					if ($mt['words'] > $best['words']) {
						$best = $mt;
					}
				}
				$visible = array();
				foreach ($best['candidates'] as $cid => $c) {
					if (!empty($c['visible'])) {
						$visible[$cid] = $c;
					}
				}
				$socid = (count($visible) === 1) ? (int) key($visible) : 0;
			}
			$found = aiRecentProducts($db, $user, $socid, $side, 8);
		}
		$langs->load('products');
		foreach ($found as $pr) {
			// The product is written by its name, the word users know (a ref is
			// rarely remembered). In a command a name with spaces, or that is a
			// number, goes in quotes, so it is never read as a quantity, a price or
			// two products.
			$name = trim(str_replace('"', '', (string) ($pr['label'] !== '' ? $pr['label'] : $pr['ref'])));
			$plain = (preg_match('/^\S+$/', $name) && !preg_match('/^\d+(?:[.,]\d+)?$/', $name));
			$value = $plain ? $name : '"'.$name.'"';
			$sentence = $name;
			$detail = price($pr['price'], 0, $langs, 1, -1, -1, $conf->currency);
			if (isModEnabled('stock')) {
				$detail .= ' - '.$langs->transnoentitiesnoconv('Stock').' '.price2num($pr['stock'], 'MS');
			}
			// 'value' for a command line, 'ref' for a sentence to the AI.
			$items[] = array('value' => $value, 'ref' => $sentence, 'label' => $pr['ref'].' - '.$pr['label'], 'detail' => $detail);
		}
	}
	print json_encode(array('items' => $items, 'consumed' => $consumed));
	$db->close();
	exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
$command = is_array($input) ? (string) ($input['command'] ?? '') : '';
$text = is_array($input) ? trim((string) ($input['text'] ?? '')) : '';
$context = (is_array($input) && isset($input['context']) && is_array($input['context'])) ? $input['context'] : array();
$choices = (is_array($input) && isset($input['thirdparty_choices']) && is_array($input['thirdparty_choices'])) ? $input['thirdparty_choices'] : array();
$productChoices = (is_array($input) && isset($input['product_choices']) && is_array($input['product_choices'])) ? $input['product_choices'] : array();
$startTime = microtime(true);

/**
 * Answer the browser, log the command like the other assistant requests, and stop.
 *
 * @param	DoliDB				$db			Database handler
 * @param	User				$user		User
 * @param	string				$query		Command as typed
 * @param	float				$startTime	Start of the request
 * @param	array<string,mixed>	$response	Answer
 * @param	string				$status		Log status
 * @return	never
 */
function aiCommandAnswer($db, $user, $query, $startTime, array $response, $status = 'Success')
{
	ai_log_request($db, $user, $query, $response, 'palette', microtime(true) - $startTime, 1.0, $status, (string) ($response['error'] ?? ''));
	print json_encode($response);
	$db->close();
	exit;
}
$typedQuery = '/'.$command.' '.$text;

// --- /total -----------------------------------------------------------------
if ($command === 'total') {
	$element = (string) ($context['element'] ?? '');
	if (empty($context['list']) || !isset($totalElements[$element])) {
		aiCommandAnswer($db, $user, $typedQuery, $startTime, array('error' => $langs->transnoentitiesnoconv('AICmdTotalNoList')), 'Error');
	}
	$def = $totalElements[$element];
	if (!$user->hasRight($def['right'][0], $def['right'][1], $def['right'][2] ?? '')) {
		aiCommandAnswer($db, $user, $typedQuery, $startTime, array('error' => $langs->transnoentitiesnoconv('NotEnoughPermissions')), 'Error');
	}
	$scope = 'ticked';
	$ids = array_values(array_filter(array_map('intval', (array) ($context['selected'] ?? array()))));
	if (empty($ids)) {
		$scope = 'shown';
		$ids = array_values(array_filter(array_map('intval', (array) ($context['ids'] ?? array()))));
	}
	$ids = array_slice(array_unique($ids), 0, 500);
	if (empty($ids)) {
		aiCommandAnswer($db, $user, $typedQuery, $startTime, array('error' => $langs->transnoentitiesnoconv('AICmdTotalNoList')), 'Error');
	}
	// One query for all the rows (no per-row fetch); the same entity and
	// sales-representative restrictions as the list itself.
	$sql = "SELECT COUNT(t.rowid) as nb, SUM(t.total_ht) as ht, SUM(t.total_tva) as tva, SUM(t.total_ttc) as ttc";
	if (!empty($def['paid'])) {
		$sql .= ", SUM((SELECT COALESCE(SUM(p.amount), 0) FROM ".$db->prefix().$db->sanitize($def['paid'][0])." as p WHERE p.".$db->sanitize($def['paid'][1])." = t.rowid)) as paid";
	}
	$sql .= " FROM ".$db->prefix().$db->sanitize($def['table'])." as t";
	$sql .= " WHERE t.rowid IN (".$db->sanitize(implode(',', $ids)).")";
	$sql .= " AND t.entity IN (".getEntity($element).")";
	if ($user->socid > 0) {
		$sql .= " AND t.fk_soc = ".((int) $user->socid);	// an external user: their own company only
	} elseif (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND t.fk_soc IN (SELECT sc.fk_soc FROM ".$db->prefix()."societe_commerciaux as sc WHERE sc.fk_user = ".((int) $user->id).")";
	}
	$resql = $db->query($sql);
	$obj = $resql ? $db->fetch_object($resql) : null;
	if (!$obj) {
		dol_syslog('command.php total '.$db->lasterror(), LOG_ERR);
		aiCommandAnswer($db, $user, $typedQuery, $startTime, array('error' => $langs->transnoentitiesnoconv('Error')), 'Error');
	}
	$rows = array(
		array($langs->transnoentitiesnoconv('AmountHT'), price((float) $obj->ht, 0, $langs, 1, -1, -1, $conf->currency)),
		array($langs->transnoentitiesnoconv('AmountVAT'), price((float) $obj->tva, 0, $langs, 1, -1, -1, $conf->currency)),
		array($langs->transnoentitiesnoconv('AmountTTC'), price((float) $obj->ttc, 0, $langs, 1, -1, -1, $conf->currency)),
	);
	if (!empty($def['paid'])) {
		$rows[] = array($langs->transnoentitiesnoconv('AlreadyPaid'), price((float) $obj->paid, 0, $langs, 1, -1, -1, $conf->currency));
		$rows[] = array($langs->transnoentitiesnoconv('RemainderToPay'), price((float) $obj->ttc - (float) $obj->paid, 0, $langs, 1, -1, -1, $conf->currency));
	}
	aiCommandAnswer($db, $user, $typedQuery, $startTime, array(
		'totals' => array(
			'title' => $langs->transnoentitiesnoconv($scope === 'ticked' ? 'AICmdTotalTicked' : 'AICmdTotalShown', (string) ((int) $obj->nb)),
			'rows' => $rows
		)
	));
}

// --- Document commands -----------------------------------------------------
if (!isset($documentCommands[$command]) || !isset($allowedTools[$documentCommands[$command]['tool']])) {
	aiCommandAnswer($db, $user, $typedQuery, $startTime, array('error' => $langs->transnoentitiesnoconv('AICmdUnknown', $command)), 'Error');
}
$def = $documentCommands[$command];
$side = (strpos($command, 'supplier') === 0) ? 'buy' : 'sell';
$parsed = aiParseDocumentCommand($db, $text, $user, $side);
if ($parsed['error'] === 'nolines') {
	aiCommandAnswer($db, $user, $typedQuery, $startTime, array('error' => $langs->transnoentitiesnoconv('AICmdNoLines', '/'.$command.' '.$langs->transnoentitiesnoconv('AICmdSyntaxDocument', dol_strtolower($langs->transnoentitiesnoconv($def['who']))))), 'Error');
}
if (!empty($def['ref']) && $parsed['ref'] === '') {
	aiCommandAnswer($db, $user, $typedQuery, $startTime, array('error' => $langs->transnoentitiesnoconv('AICmdNeedRefSupplier')), 'Error');
}

// The third party: named in the line, or the one of the card being viewed.
// Never guessed: several fit -> the user chooses, as in the chat.
$socid = 0;
if ($parsed['name'] !== '') {
	// The longest phrase of the name that matched (one name, one phrase).
	$best = array('phrase' => '', 'raw' => $parsed['name'], 'words' => 0, 'candidates' => array());
	foreach (aiFindThirdpartiesInText($db, $user, $parsed['name']) as $mt) {
		if ($mt['words'] > $best['words']) {
			$best = $mt;
		}
	}
	$visible = array();
	foreach ($best['candidates'] as $cid => $c) {
		if ($c['visible']) {
			$visible[(int) $cid] = $c;
		}
	}
	if (empty($visible)) {
		aiCommandAnswer($db, $user, $typedQuery, $startTime, array('error' => $langs->transnoentitiesnoconv('AIWriteThirdpartyNotFound', $parsed['name'])), 'Error');
	}
	$chosen = isset($choices[$best['phrase']]) ? (int) $choices[$best['phrase']] : 0;
	if ($chosen > 0 && isset($visible[$chosen])) {
		$socid = $chosen;
	} elseif (count($visible) === 1) {
		$socid = (int) key($visible);
	} else {
		$langs->load('companies');
		$options = array();
		foreach ($visible as $cid => $c) {
			$nat = array();
			if (in_array($c['client'], array(1, 3))) {
				$nat[] = $langs->transnoentitiesnoconv('Customer');
			}
			if (in_array($c['client'], array(2, 3))) {
				$nat[] = $langs->transnoentitiesnoconv('Prospect');
			}
			if ($c['fournisseur'] == 1) {
				$nat[] = $langs->transnoentitiesnoconv('Supplier');
			}
			$options[] = array('id' => $cid, 'label' => $c['name'].($c['name_alias'] !== '' ? ' ('.$c['name_alias'].')' : '').(!empty($nat) ? ' - '.implode(', ', $nat) : '').($c['town'] !== '' ? ' - '.$c['town'] : ''));
		}
		aiCommandAnswer($db, $user, $typedQuery, $startTime, array('tool' => 'ask_for_clarification', 'arguments' => array(
			'question' => $langs->transnoentitiesnoconv('AIWhichThirdparty', $best['raw']),
			'thirdparty_choice' => array('phrase' => $best['phrase'], 'options' => $options)
		)), 'Confirm');
	}
} else {
	$element = (string) ($context['element'] ?? '');
	$socid = ($element === 'societe') ? (int) ($context['id'] ?? 0) : (int) ($context['socid'] ?? 0);
	if ($socid <= 0) {
		aiCommandAnswer($db, $user, $typedQuery, $startTime, array('error' => $langs->transnoentitiesnoconv('AICmdNeedThirdparty', dol_strtolower($langs->transnoentitiesnoconv($def['who'])))), 'Error');
	}
	// The page's third party comes from the browser: the user must be allowed to see it.
	if (!checkUserAccessToObject($user, array('societe'), $socid, '', '', 'fk_soc', 'rowid')) {
		aiCommandAnswer($db, $user, $typedQuery, $startTime, array('error' => $langs->transnoentitiesnoconv('AICmdNeedThirdparty', dol_strtolower($langs->transnoentitiesnoconv($def['who'])))), 'Error');
	}
}

// Each product: the one typed when it is unique, otherwise the user picks it
// (or keeps the words as a free-text line). A line is never written with a
// product guessed from a few letters.
foreach ($parsed['lines'] as $li => $line) {
	$typed = (string) ($line['product_ref'] ?? '');
	$key = $li.':'.$typed;
	$pick = 0;
	if (isset($productChoices[$key]) && is_numeric($productChoices[$key])) {
		$pick = (int) $productChoices[$key];
		if ($pick === 0) {
			$parsed['lines'][$li]['desc'] = $typed;	// kept as free text, as the user chose
			unset($parsed['lines'][$li]['product_ref']);
			continue;
		}
	}
	// "#" only says "a product": what follows is its name or its ref, never a
	// rowid. Quotes, as a picked suggestion writes a name, only group the words.
	$search = trim(str_replace('"', ' ', ltrim($typed, '#')));
	if ($pick > 0) {
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		$prod = new Product($db);
		if ($prod->fetch($pick) > 0 && ($side === 'buy' ? $prod->status_buy : $prod->status) && $user->hasRight($prod->type == 1 ? 'service' : 'produit', 'lire')) {
			$parsed['lines'][$li]['product_ref'] = $prod->ref;
			continue;
		}
	}
	$found = aiFindProducts($db, $user, $search, $side, 10);
	$exact = array();
	foreach ($found as $pr) {
		if (!empty($pr['exact'])) {
			$exact[] = $pr;
		}
	}
	if (count($exact) === 1 || count($found) === 1) {
		$parsed['lines'][$li]['product_ref'] = (count($exact) === 1 ? $exact[0]['ref'] : $found[0]['ref']);
		continue;
	}
	$options = array();
	foreach ($found as $pr) {
		$options[] = array('id' => $pr['id'], 'label' => $pr['ref'].' - '.$pr['label'].' - '.price($pr['price'], 0, $langs, 1, -1, -1, $conf->currency));
	}
	aiCommandAnswer($db, $user, $typedQuery, $startTime, array('tool' => 'ask_for_clarification', 'arguments' => array(
		'question' => $langs->transnoentitiesnoconv(empty($options) ? 'AICmdProductNotFound' : 'AICmdWhichProduct', $search),
		'product_choice' => array('key' => $key, 'options' => $options)
	)), 'Confirm');
}

$args = array('socid' => $socid, 'lines' => $parsed['lines']);
if ($parsed['ref'] !== '') {
	$args['ref_supplier'] = $parsed['ref'];
}
// Through the tool, as the chat would call it: rights, refusal and the
// confirmation all apply. The first call only prepares the confirmation.
$result = $mcp->executeTool($def['tool'], $args);
if (isset($result['error'])) {
	aiCommandAnswer($db, $user, $typedQuery, $startTime, array('error' => (string) $result['error']), 'Error');
}
aiCommandAnswer($db, $user, $typedQuery, $startTime, array('result' => $result, 'tool' => $def['tool'], 'arguments' => $args), (($result['resultType'] ?? '') === 'input_required') ? 'Confirm' : 'Success');
