<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026	Nick Fragoulis
 * Copyright (C) 2026	Jose Martinez			<jose.martinez@pichinov.com>
 * Copyright (C) 2026	Anthony Damhet			<a.damhet@progiseize.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 * \file    htdocs/ai/assistant/parse_intent.php
 * \ingroup ai
 * \brief   File to handle MCP (Model Context Protocol) Intent Parsing
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
// The payload is read from the raw php://input body, so the CSRF token cannot be checked by
// main.inc.php. It is checked explicitly below by aiCheckCsrfToken().
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/ai/class/mcp.class.php';
require_once DOL_DOCUMENT_ROOT . '/ai/lib/ai.lib.php';
require_once DOL_DOCUMENT_ROOT . '/ai/class/llmadapter.class.php';
require_once DOL_DOCUMENT_ROOT . '/ai/class/privacy_guard.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';

// Security check
if (!isModEnabled('ai') || !getDolGlobalString('AI_ASSISTANT_ENABLED')) {
	http_response_code(403);
	accessforbidden('Module or feature not allowed');
}

global $db, $user, $conf, $langs;

// Same per-user gate as the Assistant page that calls this endpoint, so a
// user without 'ai/assistant/use' cannot reach the LLM through direct AJAX.
if (!$user->hasRight('ai', 'assistant', 'use')) {
	accessforbidden();
}

// This endpoint sends data to the LLM provider on behalf of the user and can chain tool
// executions, so it must not be reachable from another site.
aiCheckCsrfToken('ai/assistant/parse_intent.php');

ob_start();
top_httphead('application/json');

// Confirmation level: 0=no confirmation, 1=only create/update/delete, 2=all actions
$askForConfirmation = getDolGlobalInt('AI_ASK_FOR_CONFIRMATION');

// Confidence thresholds
define('HIGH_CONFIDENCE', 0.8);
define('MEDIUM_CONFIDENCE', 0.5);
define('LOW_CONFIDENCE', 0.3);

// Logging variables
$startTime = microtime(true);
$rawRequestLog = "";
$rawResponseLog = "";
$providerUsed = "offline";
$errorDetails = "";

$assistantEnabled = getDolGlobalInt('AI_ASSISTANT_ENABLED', 0);
$serviceKey = getDolGlobalString('AI_API_SERVICE');
$doRedact = getDolGlobalInt('AI_PRIVACY_REDACTION', 0);
$timeout = getDolGlobalInt('AI_REQUEST_TIMEOUT', 120);

// Kill switch
if (!$assistantEnabled) {
	$response = [
		"tool" => "respond_to_user",
		"arguments" => [
			"message" => "AI assistant service is currently disabled. Please contact your administrator to enable it."
		]
	];
	ob_end_clean();
	echo json_encode($response);
	exit;
}

set_time_limit($timeout + 5);

try {
	// Input
	$raw_input = file_get_contents('php://input');
	$data = json_decode($raw_input, true);
	$query = isset($data['query']) ? trim($data['query']) : '';

	// --- Page context (optional): posted by the chat JS from the value the
	// printCommonFooter hook emitted on the page being viewed. The POST is
	// client-controlled, so nothing here is trusted: the element must be on
	// the whitelist, the object must fetch, and the user must hold the read
	// permission - otherwise the context is silently dropped. On success a
	// one-line description is added to the system prompt so the model can
	// resolve "this invoice" into real tool arguments.
	$aiPageContextLine = '';
	if (!empty($data['context']) && is_array($data['context'])) {
		$ctxElement = isset($data['context']['element']) ? (string) $data['context']['element'] : '';
		$ctxId = isset($data['context']['id']) ? (int) $data['context']['id'] : 0;
		// element => [classfile, classname, label, rights module, rights perm(, rights subperm)]
		// External modules with their own objects opt in through the
		// AI_ASSISTANT_CONTEXT_ELEMENTS const: a JSON array of entries
		// {"element":..,"classfile":"/mymodule/class/x.class.php","classname":..,
		//  "label":..,"rights":["mymodule","myobject","read"]} - same shape,
		// same validation path (whitelist, fetch, hasRight, entity) as core
		// elements. Their card pages already emit context automatically via
		// the global hook; this const is the server-side acceptance half.
		$ctxMap = array(
			'facture' => array('/compta/facture/class/facture.class.php', 'Facture', 'customer invoice', 'facture', 'lire'),
			'invoice_supplier' => array('/fourn/class/fournisseur.facture.class.php', 'FactureFournisseur', 'supplier invoice', 'fournisseur', 'facture', 'lire'),
			'commande' => array('/commande/class/commande.class.php', 'Commande', 'sales order', 'commande', 'lire'),
			'order_supplier' => array('/fourn/class/fournisseur.commande.class.php', 'CommandeFournisseur', 'supplier order', 'fournisseur', 'commande', 'lire'),
			'propal' => array('/comm/propal/class/propal.class.php', 'Propal', 'commercial proposal', 'propal', 'lire'),
			'supplier_proposal' => array('/supplier_proposal/class/supplier_proposal.class.php', 'SupplierProposal', 'supplier proposal', 'supplier_proposal', 'lire'),
			'societe' => array('/societe/class/societe.class.php', 'Societe', 'thirdparty', 'societe', 'lire'),
			'product' => array('/product/class/product.class.php', 'Product', 'product or service', 'produit', 'lire'),
			'shipping' => array('/expedition/class/expedition.class.php', 'Expedition', 'shipment', 'expedition', 'lire'),
			'reception' => array('/reception/class/reception.class.php', 'Reception', 'reception', 'reception', 'lire'),
			'project' => array('/projet/class/project.class.php', 'Project', 'project', 'projet', 'lire'),
			'project_task' => array('/projet/class/task.class.php', 'Task', 'project task', 'projet', 'lire'),
			'contrat' => array('/contrat/class/contrat.class.php', 'Contrat', 'contract', 'contrat', 'lire'),
			'fichinter' => array('/fichinter/class/fichinter.class.php', 'Fichinter', 'intervention', 'ficheinter', 'lire'),
			'ticket' => array('/ticket/class/ticket.class.php', 'Ticket', 'support ticket', 'ticket', 'read'),
			'member' => array('/adherents/class/adherent.class.php', 'Adherent', 'member', 'adherent', 'lire'),
			'expensereport' => array('/expensereport/class/expensereport.class.php', 'ExpenseReport', 'expense report', 'expensereport', 'lire'),
			'holiday' => array('/holiday/class/holiday.class.php', 'Holiday', 'leave request', 'holiday', 'read'),
			'don' => array('/don/class/don.class.php', 'Don', 'donation', 'don', 'lire'),
			'action' => array('/comm/action/class/actioncomm.class.php', 'ActionComm', 'agenda event', 'agenda', 'myactions', 'read'),
			'bom' => array('/bom/class/bom.class.php', 'BOM', 'bill of materials', 'bom', 'read'),
			'mo' => array('/mrp/class/mo.class.php', 'Mo', 'manufacturing order', 'mrp', 'read'),
			'stock' => array('/product/stock/class/entrepot.class.php', 'Entrepot', 'warehouse', 'stock', 'lire'),
			'contact' => array('/contact/class/contact.class.php', 'Contact', 'contact', 'societe', 'contact', 'lire'),
			'bank_account' => array('/compta/bank/class/account.class.php', 'Account', 'bank account', 'banque', 'lire'),
			'category' => array('/categories/class/categorie.class.php', 'Categorie', 'category (tag)', 'categorie', 'lire'),
			'knowledgerecord' => array('/knowledgemanagement/class/knowledgerecord.class.php', 'KnowledgeRecord', 'knowledge article', 'knowledgemanagement', 'knowledgerecord', 'read'),
			'recruitmentjobposition' => array('/recruitment/class/recruitmentjobposition.class.php', 'RecruitmentJobPosition', 'job position', 'recruitment', 'recruitmentjobposition', 'read'),
			// Deliberately absent: user and salary (privacy/SEC precedent
			// #40313) - personal data cards never feed the prompt.
		);
		$ctxExtra = getDolGlobalString('AI_ASSISTANT_CONTEXT_ELEMENTS');
		if ($ctxExtra) {
			$extraArr = json_decode($ctxExtra, true);
			if (is_array($extraArr)) {
				foreach ($extraArr as $extra) {
					if (!empty($extra['element']) && !empty($extra['classfile']) && !empty($extra['classname']) && !empty($extra['rights'][0]) && !isset($ctxMap[$extra['element']])) {
						$ctxMap[(string) $extra['element']] = array(
							(string) $extra['classfile'],
							(string) $extra['classname'],
							(string) ($extra['label'] ?? $extra['element']),
							(string) $extra['rights'][0],
							(string) ($extra['rights'][1] ?? 'read'),
							(string) ($extra['rights'][2] ?? '')
						);
					}
				}
			}
		}
		if ($ctxId > 0 && isset($ctxMap[$ctxElement]) && $user->hasRight($ctxMap[$ctxElement][3], $ctxMap[$ctxElement][4], $ctxMap[$ctxElement][5] ?? '')) {
			require_once DOL_DOCUMENT_ROOT.$ctxMap[$ctxElement][0];
			$ctxObj = new $ctxMap[$ctxElement][1]($db);
			if ($ctxObj->fetch($ctxId) > 0 && (empty($ctxObj->entity) || in_array((int) $ctxObj->entity, explode(',', getEntity($ctxElement))))) {
				$ctxThirdpartyName = '';
				if (empty($doRedact) && !empty($ctxObj->socid)) {
					// The counterparty NAME lets the model use name-based search
					// tools too; under redaction it is omitted - the ids suffice
					// and names must not travel to the provider.
					require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
					$ctxSoc = new Societe($db);
					if ($ctxSoc->fetch((int) $ctxObj->socid) > 0) {
						$ctxThirdpartyName = " (".dol_string_nohtmltag($ctxSoc->name).")";
					}
				}
				if (empty($doRedact) && $ctxElement === 'societe' && !empty($ctxObj->name)) {
					$ctxThirdpartyName = " (".dol_string_nohtmltag($ctxObj->name).")";
				}
				// Under redaction, elements whose ref IS a personal/company name
				// (societe: ref = company name) must not leak it - the privacy
				// guard is pattern-based and cannot recognize arbitrary names.
				$ctxRefPart = " with ref \"".$ctxObj->ref."\"";
				if (!empty($doRedact) && in_array($ctxElement, array('societe', 'contact'), true)) {
					$ctxRefPart = "";
				}
				$aiPageContextLine = "The user is currently viewing the ".$ctxMap[$ctxElement][2].$ctxRefPart." (id ".(int) $ctxObj->id.(!empty($ctxObj->socid) ? ", thirdparty id ".(int) $ctxObj->socid.$ctxThirdpartyName : $ctxThirdpartyName).").";
				$aiPageContextLine .= " When the user says \"this\"/\"it\" or refers to the current document, use these identifiers as tool arguments";
				if ($ctxElement === 'societe') {
					$aiPageContextLine .= " - in particular, this thirdparty id is the socid/customer id for any create or search tool";
				}
				$aiPageContextLine .= ". NEVER ask the user for ids already given here; pass names/refs the user wrote (products, etc.) directly in the matching ref arguments - tools resolve them.";
				dol_syslog("AI Pro: page context accepted: ".$ctxElement." #".$ctxId);
			} else {
				dol_syslog("AI Pro: page context rejected (fetch/entity): ".$ctxElement." #".$ctxId, LOG_WARNING);
			}
		} elseif ($ctxId > 0) {
			dol_syslog("AI Pro: page context rejected (whitelist/rights): ".$ctxElement." #".$ctxId, LOG_WARNING);
		} elseif (!empty($data['context']['dashboard']) && is_string($data['context']['dashboard'])) {
			$dash = dol_string_nohtmltag(dol_substr($data['context']['dashboard'], 0, 60));
			if (preg_match('/^[a-z0-9 _-]+$/i', $dash)) {
				$aiPageContextLine = "The user is currently on the ".$dash." dashboard page. Questions about \"here\"/\"this page\" concern that module's data.";
				dol_syslog("AI Pro: dashboard context accepted: ".$dash);
			}
		} elseif (!empty($data['context']['list']) && $ctxElement !== '' && (!empty($data['context']['filters']) || !empty($data['context']['ids']) || !empty($data['context']['selected']))) {
			// List context: the user's own search inputs on their own list
			// page, echoed back uninterpreted (sanitized + capped). Nothing is
			// fetched, so no rights question arises; the model maps these onto
			// tool arguments and the tools validate as always.
			$parts = array();
			$n = 0;
			foreach ((is_array($data['context']['filters'] ?? null) ? $data['context']['filters'] : array()) as $fk => $fv) {
				if (!is_string($fv) || !preg_match('/^(search_[a-z0-9_]+|sall|search_all|sortfield|sortorder)$/', (string) $fk)) {
					continue;
				}
				$parts[] = $fk."='".dol_string_nohtmltag(dol_substr($fv, 0, 120))."'";
				if (++$n >= 12) {
					break;
				}
			}
			$idsPart = '';
			foreach (array('ids' => 100, 'selected' => 25) as $idkey => $cap) {
				if (!empty($data['context'][$idkey]) && is_array($data['context'][$idkey])) {
					$clean = array();
					foreach ($data['context'][$idkey] as $v) {
						if ((int) $v > 0) {
							$clean[] = (int) $v;
						}
						if (count($clean) >= $cap) {
							break;
						}
					}
					if (!empty($clean)) {
						$idsPart .= ($idkey === 'ids' ? " Visible row ids: " : " Checked/selected row ids (act on these when the user says the selected ones): ").implode(',', $clean).".";
					}
				}
			}
			if (!empty($parts) || $idsPart !== '') {
				$aiPageContextLine = "The user is currently viewing the \"".preg_replace('/[^a-z0-9_]/', '', $ctxElement)."\" list".(!empty($parts) ? " filtered by: ".implode(', ', $parts) : "").".".$idsPart;
				$aiPageContextLine .= " To act on \"this list\"/\"these records\"/\"the selected ones\", use these ids or translate the filters into the matching arguments of the list/report tools.";
				dol_syslog("AI Pro: list context accepted: ".$ctxElement." (".count($parts)." filters".($idsPart !== '' ? ", ids" : "").")");
			}
		}
	}

	// This is to allow easy test of the parse_intent.php by calling the URL with param query=test
	if (empty($query) && GETPOST('query', 'alphanohtml') == 'testdebug') {
		$query = 'testdebug';
	}

	if (empty($query)) {
		ob_end_clean();
		echo json_encode(["status" => "ok"]);
		exit;
	}

	// Extract file attachments sent by the chat (paperclip flow). The JS embeds
	// cloud-parsed documents as "__FILE_ATTACHMENT__[mime]::<base64>" markers in
	// the query. They MUST be stripped here, before the privacy/thirdparty
	// candidate pipeline (which would run regexes over megabytes of base64), and
	// are handed to the LLM adapter as NATIVE multimodal parts — inlining base64
	// into the text prompt makes every provider fail or hallucinate.
	$attachments = array();
	if (strpos($query, '__FILE_ATTACHMENT__') !== false) {
		$query = preg_replace_callback(
			'/__FILE_ATTACHMENT__\[([^\]]*)\]::([A-Za-z0-9+\/=\r\n]+)/',
			/**
			 * @param string[] $m Regex matches: [1] = mime type, [2] = base64 payload
			 * @return string
			 */
			static function (array $m) use (&$attachments) {
				$attachments[] = array(
					'mime' => ($m[1] !== '' ? $m[1] : 'application/octet-stream'),
					'data' => preg_replace('/\s+/', '', $m[2])
				);
				return '[attached document]';
			},
			$query
		);
		$query = trim((string) $query);
		if ($query === '' || $query === '[attached document]') {
			$query = 'Analyze the attached document and describe its content.';
		}
	}

	// Server-side gate on what the browser sent: MIME allowlist, size caps,
	// and the privacy-redaction policy (documents cannot be masked, so under
	// enforced redaction they must not go to a cloud provider at all).
	$attachmenterror = '';
	if (!ai_validate_attachments($attachments, $attachmenterror)) {
		ob_end_clean();
		echo json_encode(array(
			"tool" => "respond_to_user",
			"arguments" => array("message" => $attachmenterror)
		));
		exit;
	}

	// Privacy (Name Resolution & Masking)
	$langs->loadLangs(array("main", "bills", "orders", "propal", "supplier_invoice", "supplier_order", "projects", "other"));

	// Translation key of Words we want to block in any language.
	$blockKeys = [
		// Objects (Nouns)
		'Bill',
		'Invoice',
		'Order',
		'Proposal',
		'Shipment',
		'Reception',
		'Contract',
		'SupplierInvoice',
		'SupplierOrder',
		'Project',
		'Task',
		'Product',
		'Service',
		'Ticket',
		'Event',
		'Agenda',
		'Member',
		'User',
		'ThirdParty',
		'Company',
		'Contact',
		// Actions (Verbs/Commands)
		'Search',
		'Find',
		'List',
		'Show',
		'Create',
		'Add',
		'Modify',
		'Delete',
		'Validate',
		'Send',
		// Other
		'Hello',
		'Test'
	];

	// Resolve keys to the actual current language
	$dynamicStopWords = [];
	foreach ($blockKeys as $key) {
		$word = $langs->transnoentities($key);
		if (!empty($word)) {
			$dynamicStopWords[] = dol_strtolower($word);
		}
	}

	// Add common short English/French/Spanish commands that users often type
	// regardless of the UI language.
	$commonCommands = ['show', 'find', 'search', 'list', 'get', 'voir', 'chercher', 'affiche', 'lista', 'buscar'];
	$dynamicStopWords = array_unique(array_merge($dynamicStopWords, $commonCommands));		// $dynamicStopWords is an array of words


	$cleanQuery = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $query);							// Remove special chars from the prompt query
	$words = preg_split('/\s+/', $cleanQuery, -1, PREG_SPLIT_NO_EMPTY);
	$count = count($words);
	$candidates = array();

	// Helper function to validate a phrase without a dictionary
	$isValidPhrase = function (string $phrase) use ($dynamicStopWords): bool {
		$phrase = trim($phrase);

		// RULE 1: Minimum Length
		// Filter out extremely short words (1-2 chars).
		// This catches "a", "le", "la", "de", "y", "to", "in", "von", "zu" in almost all languages.
		if (mb_strlen($phrase) < 3) {
			return false;
		}

		// RULE 2: First Word Check
		// If the phrase starts with a translated keyword (e.g. "Invoice Acme"), skip it.
		$parts = explode(' ', $phrase);
		$firstWord = dol_strtolower($parts[0]);

		if (in_array($firstWord, $dynamicStopWords)) {
			return false;
		}

		return true;
	};

	// Fill array $candidates of thirdparty name we may want to work with
	for ($i = 0; $i < $count; $i++) {
		// Single Word
		if ($isValidPhrase($words[$i])) {
			$candidates[] = $words[$i];
		}

		if ($i + 1 < $count) {
			$phrase = $words[$i] . ' ' . $words[$i + 1];
			if ($isValidPhrase($phrase)) {
				$candidates[] = $phrase;
			}
		}

		if ($i + 2 < $count) {
			$phrase = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
			if ($isValidPhrase($phrase)) {
				$candidates[] = $phrase;
			}
		}
	}

	usort($candidates, function (string $a, string $b): int {
		return mb_strlen($b) - mb_strlen($a);
	});

	dol_syslog("parse_intent.php We have candidates into text that may be a thirdparty. List is ".implode(',', $candidates), LOG_DEBUG);

	if (!empty($candidates)) {
		foreach ($candidates as $phrase) {
			// We use LIKE '...' to match the start of the company name.
			$sql = "SELECT rowid, nom FROM " . MAIN_DB_PREFIX . "societe WHERE nom LIKE '" . $db->escape($phrase) . "%' LIMIT 1";

			$res = $db->query($sql);

			if ($res && $obj = $db->fetch_object($res)) {
				// Match found. Replace in the original query.
				$query = preg_replace('/\b' . preg_quote($phrase, '/') . '\b/iu', "socid:" . $obj->rowid, $query);

				break;
			}
		}
	}

	// Apply privacy guard if enabled
	$guard = null;
	if ($doRedact && class_exists('PrivacyGuard')) {
		$guard = new PrivacyGuard();
		$query = $guard->mask($query);
		// In-context reinforcement, adjacent to the placeholders themselves:
		// weak models weigh nearby text far more than distant system rules, and
		// the system-rule variant alone proved insufficient in the field.
		if (strpos($query, '[[') !== false) {
			$query .= "\n\n(Note: tokens like [[REF_1]] or [[ADDR_2]] above are privacy-masked real values. Use them verbatim as tool argument values - they are replaced with the real data before execution. Do not refuse the task because of them and do not ask the user to re-provide masked details.)";
		}
	}

	// AI Execution
	$intentJSON = null;
	$confidence = 0.0;
	$allToolsSchema = [];

	if ($serviceKey && $serviceKey !== '-1') {
		$providerUsed = $serviceKey;
		$mcp = new McpHandler($db, $user, $conf, McpHandler::CTX_ASSISTANT);
		$mcp->loadTools();		// This fill array ->loadedTools and ->toolsByName from tools found into ai/tools/

		// Two schemas are maintained:
		//   $allToolsSchema  — full list including system tools; used ONLY for post-LLM validation.
		//   $llmToolsBase   — system tools excluded (is_system=>true filtered out in McpHandler);

		// Special case we ask debug info
		if ($query == 'testdebug') {
			print '----- loadedTools'."\n";
			print '<pre>' . json_encode($mcp->loadedTools, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
			print "\n";
			print "\n";
			print '----- toolsByName'."\n";
			print '<pre>' . json_encode($mcp->loadedTools, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
			print "\n";
			print "\n";
			print '----- allToolsSchema (non system + system)'."\n";
			print '<pre>' . json_encode($allToolsSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
			exit;
		}

		//                     used for category filtering and as the LLM tool list.
		// This separation guarantees ask_for_confirmation, respond_to_user, etc. are
		// never visible to the model, preventing the LLM from calling them directly.
		$allToolsSchema = $mcp->getToolsSchema();
		$llmToolsBase   = $mcp->getToolsSchemaForLLM();

		// Detect if query is in a Non-Latin language (Russian, Greek, Chinese, Arabic, etc.)
		$isComplex = isComplexScript($query);

		$toolsSchema = [];

		if ($isComplex) {
			// Non-Latin: the classifier matches translated keys (user language
			// + en_US reference), so try to narrow the schema here too - a Greek
			// query otherwise always ships all tools, which is the largest
			// prompt this module can build (documents + full schema overflow
			// small-context models). No categories detected = full schema, as
			// before.
			$detectedCategories = classifyIntentUniversal($query, $langs);
			if (!empty($detectedCategories)) {
				dol_syslog("AI Pro: Non-Latin query classified into ".implode(',', $detectedCategories).". Filtering schema.");
				$toolsSchema = filterToolsProfessional($llmToolsBase, $detectedCategories);
			} else {
				dol_syslog("AI Pro: Non-Latin language detected, no category match. Sending full (cleaned) schema.");
				$toolsSchema = $llmToolsBase;
			}
		} else {
			// Detect in which business family the query is using Hybrid (Translations + Synonyms)
			$detectedCategories = classifyIntentUniversal($query, $langs);

			// Category filter applied to $llmToolsBase — system tools already excluded
			$toolsSchema = filterToolsProfessional($llmToolsBase, $detectedCategories);

			dol_syslog("AI Pro: Latin script. Detected: " . json_encode($detectedCategories) . ". Filtered to " . count($toolsSchema) . " tools.");
		}

		// If we are sending a lot of tools (Non-Latin or Fallback), we strip descriptions.
		// The 20-tool threshold was likely chosen for GPT-3.5 (4K context window). Modern
		// LLMs handle the full schema trivially: Gemini 2.5 Flash has a 1M token context,
		// GPT-4o has 128K, Claude Sonnet has 1M. Compression hurts more than it helps
		// today because it also truncates tool *descriptions* (down to 3 words), which
		// breaks tool selection (e.g. "create_other_document" becomes "Create documents
		// other" -- the LLM then thinks supplier_invoice creation is not available).
		// We raise the threshold to 100 to effectively disable compression for the
		// default install (~30 tools), while still leaving a safety net for very large
		// custom installs that register dozens of additional addMcpTools hooks.
		$isLargeSchema = count($toolsSchema) > 100;
		$toolsForLLM = cleanToolSchemaForLLM($toolsSchema, $isLargeSchema);

		// Build System Prompt
		$basePrompt = getDolGlobalString('AI_INTENT_PROMPT') ?: "You are a professional Dolibarr assistant.";

		$systemRules = "\n\nRules: Respond ONLY JSON and ensure any json string does not contains special chars and are correctly json encoded. Format: {\"tool\":..., \"arguments\":{...}}. ";
		$systemRules .= "ALWAYS write user-facing text (the message/question/answer argument values) in the SAME LANGUAGE as the user's message. English context notes, tool names, or schemas never change the response language. ";
		$systemRules .= "When a tool matches the user request, CALL it - never explain limitations instead of acting, and never claim a capability is missing while a matching tool is listed. Only when genuinely NO tool can fulfill the request, use respond_to_user to say the feature is not available. ";

		// If MCP is disabled, we disable all tools
		if (getDolGlobalString('AI_ASSISTANT_DISABLE_TOOLS')) {
			$toolsForLLM = array();
		}

		$systemPrompt = $basePrompt . "\n\n";
		$systemPrompt .= "Tools:\n" . json_encode($toolsForLLM, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		// When redaction is active, the model sees [[TYPE_N]] placeholders where
		// PII was. Without this rule it refuses tasks needing those values
		// with it, placeholders travel verbatim through tool arguments and are restored server-side
		// (unmaskAiResponse on the raw intent JSON) before execution, so the
		// cloud never sees the data and the task still completes.
		if ($doRedact) {
			$systemRules .= " Privacy masking is active: values like [[REF_1]], [[ADDR_2]], [[EMAIL_3]], [[PHONE_4]], [[ZIP_5]] are masked real data. Treat them as valid values: when a tool argument needs such a datum, pass the placeholder exactly as written — it is replaced by the real value before execution. Never refuse a task because values look masked, and never invent replacements for them.";
		}

		// A bare date is not enough for weaker models: state explicitly that
		// relative periods are the assistant's job to resolve, not the user's.
		$systemPrompt .= $systemRules . " Current date: " . date('Y-m-d') . " (" . date('l') . ").";
		if (!empty($aiPageContextLine)) {
			// Masked like the query itself: under enforced redaction the ref
			// becomes a placeholder that is restored server-side in tool
			// arguments; the numeric ids the tools need stay usable.
			$systemPrompt .= "\n\nPage context: ".(!empty($doRedact) && !empty($guard) ? $guard->mask($aiPageContextLine) : $aiPageContextLine);
		}
		$systemPrompt .= " Resolve relative periods yourself from the current date — today, yesterday, this week, this month, last month, this quarter, this year — into explicit YYYY-MM-DD values for date parameters (e.g. this month = first day of the current month to the current date). Never ask the user for dates you can compute.";

		// Get API configuration
		$servicesList = getListOfAIServices();
		$apiKey = getDolGlobalString('AI_API_' . strtoupper($serviceKey) . '_KEY');

		if (preg_match('/^crypt:/', $apiKey)) {
			$apiKey = dolDecrypt($apiKey, $conf->file->instance_unique_id);
		}

		$defUrl = $servicesList[$serviceKey]['url'] ?? '';
		$url = getDolGlobalString('AI_API_' . strtoupper($serviceKey) . '_URL') ?: $defUrl;
		// The model defaults declared in getListOfAIServices() are nested:
		//   $servicesList[$key]['textgeneration'] = ['default' => 'model-name']
		// Reading 'textgeneration' without ['default'] returns the inner array, which
		// then fails the (string) type-hint of UniversalLLMAdapter's 4th argument with:
		//   "Argument #4 ($model) must be of type string, array given"
		//
		// The admin UI (htdocs/ai/admin/setup.php "Prompt and custom AI models" tab) also
		// stores the per-function model under AI_API_<SERVICE>_MODEL_TEXT (matching the
		// convention already used by Ai::generateContent() for the same data). The
		// previous lookup used AI_API_<SERVICE>_MODEL which is never written by that
		// form, so the user-configured model was silently ignored.
		$rawDefault = $servicesList[$serviceKey]['textgeneration'] ?? null;
		if (is_array($rawDefault)) {
			$defModel = $rawDefault['default'] ?? 'gpt-4o-mini';
		} else {
			$defModel = $rawDefault ?: 'gpt-4o-mini';
		}
		$prefix = 'AI_API_' . strtoupper($serviceKey);
		$model = getDolGlobalString($prefix . '_MODEL_TEXT')
			?: getDolGlobalString($prefix . '_MODEL')
			?: $defModel;
		// Defensive: coerce to string if anyone stored an array in this constant
		if (is_array($model)) {
			$model = $model['default'] ?? $defModel;
		}
		if (!is_string($model) || $model === '') {
			$model = (string) $defModel;
		}
		// Optional per-request model override sent by the chat model picker.
		// Sanitized to the provider model-id charset; empty/invalid = keep default.
		if (!empty($data['model']) && is_string($data['model'])) {
			$reqModel = preg_replace('/[^a-zA-Z0-9._:\/-]/', '', $data['model']);
			if ($reqModel !== '' && strlen($reqModel) <= 100) {
				$model = $reqModel;
			}
		}
		$adapterType = $servicesList[$serviceKey]['adapter_type'] ?? 'openai';


		// The request.
		// var_dump($query);

		if (!empty($apiKey)) {
			$adapter = new UniversalLLMAdapter($adapterType, $apiKey, $url, $model, $timeout);

			dol_syslog("parse_intent.php Call AI API", LOG_DEBUG);

			// In-context page-context reinforcement: weak models ignore context
			// buried in the system prompt (same lesson as the privacy
			// placeholders) - a short line adjacent to the query is what
			// actually works. Masked like everything else under redaction.
			if (!empty($aiPageContextLine)) {
				// Keep the user-turn anchor MINIMAL: verbose instructions in
				// the user message destabilize weaker models (field-observed:
				// hallucinated tool names appeared with the long form). The
				// full coaching stays in the system Page-context line above.
				$aiPageContextShort = strtok($aiPageContextLine, ".").".";
				$query .= "\n\n(Context: ".(!empty($doRedact) && !empty($guard) ? $guard->mask($aiPageContextShort) : $aiPageContextShort).")";
			}

			$rawResponse = $adapter->generate($systemPrompt, $query, 'text', $attachments);

			// $rawResponse should be a json string with format '{"tool":..., "arguments":{text answer}}' but sometimes it is just 'text answer'
			dol_syslog('rawResponse='.$rawResponse, LOG_DEBUG);

			//var_dump($rawResponse);exit;

			// Capture logs
			$rawRequestLog = $adapter->lastRequest;
			$rawResponseLog = $adapter->lastResponse;

			// Process response
			if (is_string($rawResponse) && strpos($rawResponse, 'Error:') === 0) {
				$errorDetails = $rawResponse;
			} elseif ($rawResponse) {
				// Clean JSON response
				$clean = preg_replace('/```json\s*|\s*```/s', '', $rawResponse);
				$clean = trim($clean);

				$matches = array();
				if (preg_match('/^\{.*\}$/s', $clean, $matches)) {
					$clean = $matches[0];
				}

				// Unmask the JSON string
				if ($guard) {
					$clean = $guard->unmaskAiResponse($clean);
				}

				// Removed carriage returns and newlines
				$clean = preg_replace('/[\r\n]/', ' ', $clean);

				// If answer is a json string or not
				if (strpos($clean, '{') === 0) {
					// This may be a json string
					$intentJSON = json_decode($clean, true);
					// Weak models improvise clarification fields (missing_argument,
					// reason...) instead of the schema's 'question'; the UI then
					// renders "undefined". Normalize here so every consumer gets
					// a question.
					if (is_array($intentJSON) && ($intentJSON['tool'] ?? '') === 'respond_to_user' && empty($intentJSON['arguments']['message'])) {
						// Weak models sometimes answer with an empty argument set,
						// which renders as a blank bubble. Give the user something
						// actionable instead.
						$intentJSON['arguments']['message'] = 'I could not produce an answer for this request. Please rephrase or add details.';
					}
					if (is_array($intentJSON) && ($intentJSON['tool'] ?? '') === 'ask_for_clarification' && empty($intentJSON['arguments']['question'])) {
						$a = isset($intentJSON['arguments']) && is_array($intentJSON['arguments']) ? $intentJSON['arguments'] : array();
						$qparts = array();
						if (!empty($a['reason'])) {
							$qparts[] = (string) $a['reason'];
						}
						if (!empty($a['missing_argument']) && stripos(implode(' ', $qparts), (string) $a['missing_argument']) === false) {
							$qparts[] = "Missing: ".(string) $a['missing_argument'];
						}
						if (empty($qparts) && !empty($a['message'])) {
							$qparts[] = (string) $a['message'];
						}
						$intentJSON['arguments']['question'] = !empty($qparts) ? implode(' ', $qparts) : 'Could you provide the missing information?';
					}
				} else {
					$intentJSON = [
						"tool" => "respond_to_user",
						'arguments' => [
							"message" => $clean
						]
					];
				}

				// Ensure no placeholders remain in the data structure.
				if ($guard && isset($intentJSON['arguments'])) {
					$intentJSON['arguments'] = recursiveUnmaskValues($intentJSON['arguments'], $guard);
				}

				// Validation check: Check if the AI selected a tool that actually exists in our filtered schema.
				if ($intentJSON && isset($intentJSON['tool'])) {
					$validToolNames = array_column($allToolsSchema, 'name');
					if (!in_array($intentJSON['tool'], $validToolNames)) {
						dol_syslog("AI Validation: Tool '" . $intentJSON['tool'] . "' not found in filtered schema. Send error message via respond_to_user.", LOG_WARNING);

						// Force the standard response for non-existent functionality
						$intentJSON = [
							"tool" => "respond_to_user",
							"arguments" => [
								"message" => "I apologize, but the requested functionality is not currently available in the system."
							]
						];
						$confidence = 1.0;
					}
				}

				// Calculate confidence (only if not manually set to 1.0 above)
				if ($intentJSON && $confidence === 0.0) {
					$mappedToolsSchema = array_column($toolsSchema, null, 'name');
					$confidence = calculateConfidence($intentJSON, $mappedToolsSchema, $rawResponse);

					dol_syslog("parse_intent.php AI Intent: " . json_encode(['query' => $query, 'intent' => $intentJSON, 'confidence' => $confidence]), LOG_DEBUG);
				}
			}
		}
	}


	// Handle no AI Intent
	if (!$intentJSON || !isset($intentJSON['tool'])) {
		$finalResponse = [
			"tool" => "respond_to_user",
			"arguments" => [
				"message" => "I'm having trouble understanding your request. Please try rephrasing it differently. If the problem persists, please contact your administrator to check the AI connection status."
			]
		];

		// Log the failure
		ai_log_request($db, $user, $query, $finalResponse, $providerUsed, microtime(true) - $startTime, 0.0, $langs->transnoentitiesnoconv('Error'), $errorDetails, $rawRequestLog, $rawResponseLog);

		ob_end_clean();
		echo json_encode($finalResponse);
		exit;
	}

	// Check if confirmation needed
	$needsConfirmation = false;
	$toolName = $intentJSON['tool'] ?? '';

	// Normalize the text answer key: some models (e.g. GPT-4o) fill
	// respond_to_user / reject_general_question under 'response', 'text',
	// 'answer'... instead of the 'message' key the frontend reads, which
	// otherwise surfaces as "Empty AI Response".
	if (in_array($toolName, array('respond_to_user', 'reject_general_question'), true) && isset($intentJSON['arguments']) && is_array($intentJSON['arguments'])) {
		if (empty($intentJSON['arguments']['message'])) {
			foreach (array('response', 'text', 'answer', 'content', 'reply', 'output') as $altkey) {
				if (!empty($intentJSON['arguments'][$altkey])) {
					$intentJSON['arguments']['message'] = $intentJSON['arguments'][$altkey];
					break;
				}
			}
		}
	}

	if ($askForConfirmation > 0) {
		$isModifyOperation = preg_match('/(create|update|delete|add|remove|modify|edit)/i', $toolName);

		if ($askForConfirmation == 1 && $isModifyOperation) {
			$needsConfirmation = true;
		} elseif ($askForConfirmation == 2) {
			$needsConfirmation = true;
		}
	}

	// Handle confirmation
	if ($needsConfirmation) {
		$allToolsMap = !empty($allToolsSchema)
			? array_column($allToolsSchema, null, 'name')
			: [];
		$toolDescription = $allToolsMap[$toolName]['description'] ?? 'No description available';
		$arguments = $intentJSON['arguments'] ?? [];

		$details = formatArgumentsForDisplay($arguments);
		$action = extractActionFromTool($toolName);

		$confirmationResponse = [
			"tool" => "ask_for_confirmation",
			"arguments" => [
				"action" => $action,
				"details" => $details,
				"original_intent" => $intentJSON
			]
		];

		// Log the confirmation request
		ai_log_request($db, $user, $query, $confirmationResponse, $providerUsed, microtime(true) - $startTime, $confidence, $langs->transnoentitiesnoconv("Confirm"), $errorDetails, $rawRequestLog, $rawResponseLog);

		ob_end_clean();
		echo json_encode($confirmationResponse);
		exit;
	}

	// Handle low confidence
	if ($confidence < LOW_CONFIDENCE) {
		$finalResponse = [
			"tool" => "respond_to_user",
			"arguments" => [
				"message" => "I'm not confident about understanding your request. Please try rephrasing it with more specific details."
			]
		];

		// Log the low confidence response
		ai_log_request($db, $user, $query, $finalResponse, $providerUsed, microtime(true) - $startTime, $confidence, 'low_confidence', $errorDetails, $rawRequestLog, $rawResponseLog);

		ob_end_clean();
		echo json_encode($finalResponse);
		exit;
	}

	// Add confidence note
	if ($confidence < MEDIUM_CONFIDENCE && isset($intentJSON['arguments'])) {
		$intentJSON['arguments']['_confidence_note'] = "I'm moderately confident about this interpretation. Please verify the results.";
	}

	// Success!
	$finalResponse = $intentJSON;
	$execTime = microtime(true) - $startTime;
	ai_log_request($db, $user, $query, $finalResponse, $providerUsed, $execTime, $confidence, $langs->transnoentitiesnoconv("Success"), $errorDetails, $rawRequestLog, $rawResponseLog);

	ob_end_clean();
	echo json_encode($finalResponse);
} catch (Throwable $e) {
	$friendlyResponse = [
		"tool" => "respond_to_user",
		"arguments" => [
			"message" => "I'm experiencing technical difficulties. Please try again later or contact your administrator."
		]
	];

	$realErrorForLog = "PHP Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();

	dol_syslog("AI Critical Error: " . $realErrorForLog, LOG_ERR);

	if (function_exists('ai_log_request') && is_object($db)) {
		ai_log_request(
			$db,
			$user,
			$query ?? 'unknown',
			$friendlyResponse,
			$providerUsed,
			microtime(true) - $startTime,
			0.0,
			'error',
			$realErrorForLog,
			$rawRequestLog ?? '',
			$rawResponseLog ?? ''
		);
	}

	ob_end_clean();
	echo json_encode($friendlyResponse);
}


/**
 * Recursively unmask values in a dataset.
 *
 * This helper walks through an array structure and applies the appropriate
 * unmasking method on all string values. It ensures that any masked or
 * placeholder data is restored before being used in actual tool execution.
 *
 * Supported guard methods:
 * - unmask(string $value): string
 * - unmaskAiResponse(string $value): string
 *
 * If both methods exist, `unmask()` takes precedence.
 *
 * @param mixed $data  The input data (array, string, or scalar) to process.
 * @param PrivacyGuard|null $guard An object providing unmasking methods.
 *
 * @return mixed The data with all string values unmasked.
 */
function recursiveUnmaskValues($data, ?PrivacyGuard $guard)
{
	if ($guard === null) {
		return $data;
	}

	if (is_array($data)) {
		return array_map(
			/**
			* @param mixed $item
			* @return mixed
			*/
			function ($item) use ($guard) {
				return recursiveUnmaskValues($item, $guard);
			},
			$data
		);
	}

	if (is_string($data)) {
		return $guard->unmask($data);
	}

	return $data;
}

/**
 * Detects if the query uses Non-Latin Scripts.
 *
 * Supports all Dolibarr Core Non-Latin languages:
 * - CJK (Chinese, Japanese, Korean)
 * - Cyrillic (Russian, Ukrainian, Serbian, Bulgarian)
 * - Greek, Arabic, Hebrew, Thai
 *
 *
 * @param string $text The input text to be checked.
 * @return bool True if the text contains complex scripts, false otherwise.
 */
function isComplexScript(string $text)
{
	// CJK (Chinese, Japanese, Korean)
	if (preg_match('/\p{Han}|\p{Hiragana}|\p{Katakana}|\p{Hangul}/u', $text)) {
		return true;
	}

	// Cyrillic (Russian, Ukrainian, Bulgarian, Serbian)
	if (preg_match('/\p{Cyrillic}/u', $text)) {
		return true;
	}

	// Greek
	if (preg_match('/\p{Greek}/u', $text)) {
		return true;
	}

	// Arabic
	if (preg_match('/\p{Arabic}/u', $text)) {
		return true;
	}

	// Hebrew
	if (preg_match('/\p{Hebrew}/u', $text)) {
		return true;
	}

	// Thai
	if (preg_match('/\p{Thai}/u', $text)) {
		return true;
	}

	return false;
}

/**
 * Normalize a keyword or query for inflection-tolerant non-Latin matching.
 *
 * Translators translate UI keys as natural dictionary
 * words - as they should. Inflected languages then break exact substring
 * matching ("ÏÎ¹Î¼Î¿Î»ÏÎ³Î¹Î¿" never appears inside "ÏÎ¹Î¼Î¿Î»Î¿Î³Î¯ÏÎ½"),
 * so the code derives a match-friendly form instead of asking humans for
 * stems: lowercase, strip combining accents (Unicode NFD marks, when the
 * intl Normalizer is available), and for keywords drop the trailing
 * inflection-bearing characters. No language is special-cased.
 *
 * @param string $word    Word to normalize
 * @param bool   $asStem  True to also truncate the inflected tail (keywords); false for the query
 * @return string Normalized form ('' when too short to stem safely)
 */
function aiNormalizeForMatch($word, $asStem = false)
{
	$w = dol_strtolower(trim($word), 'UTF-8');
	if (class_exists('Normalizer')) {
		$decomposed = Normalizer::normalize($w, Normalizer::FORM_D);
		if ($decomposed !== false) {
			$w = (string) preg_replace('/\p{Mn}+/u', '', $decomposed);
		}
	}
	if ($asStem) {
		$len = dol_strlen($w);
		if ($len >= 6) {
			$w = dol_substr($w, 0, $len - 2);	// drop the inflected tail
		} elseif ($len == 5) {
			$w = dol_substr($w, 0, 4);
		} elseif ($len < 3) {
			return '';	// too short to stem: matching it would be noise
		}
	}
	return $w;
}

/**
 * Detect intent categories from a user query.
 *
 * This function analyzes a natural language query and attempts to classify it
 * into one or more predefined intent categories (e.g., billing, commercial,
 * thirdparty, stock, project, reporting).
 *
 * It leverages Dolibarr translations (`$langs->trans()`) to match localized
 * keywords in the user's language and in the en_US reference.
 * For non-Latin scripts, it performs a simpler substring search.
 *
 * Matching strategy:
 * - Latin queries: normalized (lowercase + unaccent) and matched using regex word boundaries.
 * - Non-Latin queries: matched using case-insensitive substring search.
 *
 * Each category is detected if at least one keyword matches.
 *
 * @param string    $query The user input query to analyze.
 * @param Translate $langs The Dolibarr translation object used to resolve localized keywords.
 *
 * @return string[] Array of detected intent categories (e.g., ['billing', 'stock']).
 */
function classifyIntentUniversal(string $query, Translate $langs)
{
	$isLatin = !isComplexScript($query);
	$searchQuery = $isLatin ? strtolower(dol_string_unaccent($query)) : $query;

	$langs->loadLangs(array("main", "bills", "orders", "propal", "companies", "products", "projects", "dict"));

	$intentMap = [
		'billing' => [
			'keys'     => ['Bill', 'Invoice', 'Payment', 'Cheque', 'VAT', 'BillStatusUnpaid', 'BillStatusPaid', 'BillStatusDraft'],
			'synonyms' => ['paid', 'unpaid', 'pay', 'money', 'cost', 'amount', 'overdue']
		],
		'commercial' => [
			'keys'     => ['Order', 'Proposal', 'Quote', 'SupplierOrder', 'OrderStatusDraft'],
			'synonyms' => ['sale', 'buy', 'purchase', 'contract', 'shipping', 'quote']
		],
		'thirdparty' => [
			'keys'     => ['ThirdParty', 'Customer', 'Supplier', 'Contact', 'Company'],
			'synonyms' => ['client', 'partner', 'address', 'phone', 'vendor']
		],
		'stock' => [
			'keys'     => ['Product', 'Service', 'Stock', 'Warehouse'],
			'synonyms' => ['item', 'inventory', 'sku', 'location', 'qty', 'warehouse']
		],
		'project' => [
			'keys'     => ['Project', 'Task'],
			'synonyms' => ['task', 'team', 'deadline', 'planning', 'milestone']
		],
		'reporting' => [
			'keys'     => ['Report', 'Statistics', 'Turnover', 'Revenue', 'Income'],
			'synonyms' => ['report', 'statistics', 'analytics', 'chart', 'total', 'turnover']
		]
	];

	// en_US reference translator (loaded once; Translate caches files)
	static $langsEnUs = null;
	if ($langsEnUs === null) {
		global $conf;
		$langsEnUs = new Translate('', $conf);
		$langsEnUs->setDefaultLang('en_US');
		$langsEnUs->loadLangs(array('main', 'bills', 'companies', 'products', 'projects', 'orders', 'propal', 'stocks', 'other'));
	}

	$detectedCategories = [];
	foreach ($intentMap as $category => $data) {
		$keywords = [];
		foreach ($data['keys'] as $key) {
			// Matched in the user's language AND in the en_US reference: keys
			// translate to the UI language only, but users routinely type
			// English terms on non-English installs. One mechanism, no
			// separate synonym lists to translate.
			foreach (array($langs->transnoentities($key), $langsEnUs->transnoentities($key)) as $trans) {
				if ($trans === '' || ($trans === $key && preg_match('/[A-Z]/', dol_substr($key, 1, 200)))) {
					// Untranslated composite key (e.g. 'BillStatusUnpaid'):
					// matching it would be noise. A plain word equal to its
					// key ('Customer' in en_US) is a real keyword - keep it.
					continue;
				}
				if ($isLatin) {
					$trans = strtolower(dol_string_unaccent($trans));
				}
				$keywords[] = $trans;
			}
		}
		$keywords = array_unique($keywords);

		// Hardcoded English colloquialisms (words no UI key carries) - words no
		// UI key carries; translated vocabulary already arrives through the
		// dual-language keys above.
		foreach ((array) ($data['synonyms'] ?? array()) as $syn) {
			$keywords[] = $isLatin ? strtolower(dol_string_unaccent($syn)) : $syn;
		}
		$keywords = array_unique($keywords);

		$normalizedQuery = aiNormalizeForMatch($searchQuery, false);
		foreach ($keywords as $word) {
			if (empty($word)) {
				continue;
			}
			if ($isLatin) {
				if (preg_match('/\b' . preg_quote($word, '/') . 's?\b/u', $searchQuery)) {
					$detectedCategories[] = $category;
					break;
				}
			} else {
				// Inflection-tolerant: stem the keyword, normalize the query,
				// then substring-match. Natural-word translations work as-is.
				$stem = aiNormalizeForMatch($word, true);
				if ($stem !== '' && mb_strpos($normalizedQuery, $stem) !== false) {
					$detectedCategories[] = $category;
					break;
				}
			}
		}
	}
	return $detectedCategories;
}

/**
 * Filter a list of tools based on active intent categories.
 *
 * This function narrows down the available tools by matching their assigned
 * categories against the detected intent categories. Tools tagged as "global"
 * are always considered, but may be excluded when more specific categories
 * are active to avoid overly generic matches.
 *
 * Behavior:
 * - If no categories are provided, or only "global" is present, all tools are returned.
 * - Tools are included if they share at least one category with the target categories.
 * - Tools with only the "global" category are excluded when specific categories are active.
 * - If filtering results in fewer than 3 tools, the full tool list is returned as a fallback.
 *
 *
 * @param array<int,array<string,mixed>> $allTools          List of all available tools.
 * @param string[] $activeCategories  Detected intent categories (e.g., ['billing', 'stock']).
 *
 * @return array<int,array<string,mixed>> Filtered list of tools matching the active categories.
 */
function filterToolsProfessional(array $allTools, array $activeCategories)
{
	if (empty($activeCategories) || (count($activeCategories) === 1 && $activeCategories[0] === 'global')) {
		return $allTools;
	}

	$targetCategories = array_merge(['global'], $activeCategories);
	$filtered = [];

	foreach ($allTools as $tool) {
		$toolCats = $tool['categories'] ?? ['global'];
		if (count(array_intersect($toolCats, $targetCategories)) > 0) {
			if (count($activeCategories) > 0 && $toolCats === ['global']) {
				continue;
			}
			$filtered[] = $tool;
		}
	}

	if (count($filtered) < 3) {
		dol_syslog("AI Filter: Too few tools (" . count($filtered) . "). Reverting to full schema.", LOG_WARNING);
		return $allTools;
	}

	return $filtered;
}

/**
 * Compresses tool schema by removing optional parameters with defaults
 * and stripping descriptions, relying on LLM inference of variable names.
 *
 * @param array<int, array<string, mixed>> $tools Array of tool definitions.
 * @param bool $isLargeSchema True if compression is needed.
 * @return array<int, array<string, mixed>>
 */
function cleanToolSchemaForLLM(array $tools, bool $isLargeSchema = false)
{
	$cleaned = [];

	foreach ($tools as $tool) {
		// Tool descriptions are how the LLM selects the right tool -- never truncate
		// them, even when the schema is large. Truncating to 3 words ("Create documents
		// other", "Add a single") breaks tool selection. If the schema really is too
		// big for the chosen model, the right answer is to filter the toolset before
		// it reaches the LLM (which is what filterToolsProfessional() already does
		// upstream of this function), not to mutilate each tool's description.
		// Parameter-level compression (stripping defaults, descriptions of optional
		// fields, etc.) remains gated on $isLargeSchema below.
		$desc = $tool['description'];

		// Get parameters
		$toolParams = $tool['parameters'] ?? $tool['inputSchema'] ?? [];

		if ($isLargeSchema && isset($toolParams['properties']) && is_array($toolParams['properties'])) {
			$requiredList = $toolParams['required'] ?? [];
			$newProperties = [];

			foreach ($toolParams['properties'] as $propKey => $propData) {
				$isRequired = in_array($propKey, $requiredList);

				// -----------------------------------------------------------
				// Remove Optional Parameters with Defaults
				// -----------------------------------------------------------
				// If a parameter is optional and has a default value defined in
				// the schema, we assume the backend will handle it. We remove it
				// from the prompt entirely. This saves massive amounts of tokens
				// on list/search functions (limit, sortorder, sqlfilters, etc).
				// -----------------------------------------------------------
				if (!$isRequired && isset($propData['default'])) {
					continue;
				}

				// Remove 'type' for string (LLM default), Keep others (int/bool/arr)
				if (isset($propData['type']) && $propData['type'] === 'string') {
					unset($propData['type']);
				}

				// Handle Descriptions
				// Remove descriptions entirely. Rely on the key name (e.g. 'email', 'qty').
				// Exception: Keep 1 word if it's a required parameter with a confusing name.
				if (isset($propData['description'])) {
					unset($propData['description']);
					// If we want to keep a tiny hint for required params, uncomment below:
					// if ($isRequired) {
					//     $propData['description'] = explode(' ', trim($propData['description']))[0];
					// }
				}

				// Collapse Complex Objects
				// If a parameter is a deep object (like a complex filter), replace the
				// recursive properties definition with a generic string to save tokens.
				if (isset($propData['type']) && $propData['type'] === 'object' && isset($propData['properties'])) {
					unset($propData['properties']);
					unset($propData['required']);
					$propData['description'] = "JSON object"; // Minimal hint
				}

				$newProperties[$propKey] = $propData;
			}

			$toolParams['properties'] = $newProperties;

			// Clean up root metadata
			unset($toolParams['type']);
			unset($toolParams['additionalProperties']);
		}

		$cleaned[] = [
			'name' => $tool['name'],
			'description' => $desc,
			'parameters' => $toolParams
		];
	}

	return $cleaned;
}

/**
 * Calculate confidence score based on multiple factors.
 *
 * This function analyzes the AI's response to determine if the intent was
 * parsed correctly and if all required arguments were provided according to
 * the tool's schema.
 *
 * @param array<string, mixed> $intentJSON  The parsed intent (Keys: 'tool', 'arguments').
 * @param array<string, array<string, mixed>> $toolsSchema Available tools schema (Key=ToolName, Value=ToolDefinition).
 * @param string               $rawResponse Raw response string from the AI provider.
 * @return float Confidence score between 0.0 and 1.0.
 */
function calculateConfidence($intentJSON, $toolsSchema, $rawResponse)
{
	$confidence = 0.0;
	$factors = [];

	// Factor 1: JSON parsing success (Weight: 40%)
	// If we are here, the JSON generally parsed, but we check if the structure is valid.
	$factors['parse_success'] = 0.4;

	// Factor 2: Response completeness (Weight: 30%)
	// Check if we have a tool name and some arguments.
	$hasRequiredFields = !empty($intentJSON['tool']) && !empty($intentJSON['arguments']);
	$factors['completeness'] = $hasRequiredFields ? 0.3 : 0.0;

	// Factor 3: Schema validation (Weight: 20%)
	$isValidSchema = false;

	// Ensure the tool exists in our known schema
	if (isset($intentJSON['tool']) && isset($toolsSchema[$intentJSON['tool']])) {
		// Support both 'parameters' and 'inputSchema'
		$schema = $toolsSchema[$intentJSON['tool']]['parameters']
			?? $toolsSchema[$intentJSON['tool']]['inputSchema']
			?? [];

		// Extract parameters provided by the AI
		$providedParams = array_keys($intentJSON['arguments'] ?? []);

		// Standard JSON Schema structure uses 'properties' to list params and 'required' to list mandatory ones.
		$properties = $schema['properties'] ?? [];
		$requiredList = $schema['required'] ?? [];

		$missingParams = [];

		// Iterate through the schema properties to check required fields
		foreach ($properties as $paramKey => $paramDetails) {
			// Check if this specific parameter is marked as required in the schema
			if (in_array($paramKey, $requiredList)) {
				// If it is required but not in the AI's provided arguments, it's missing.
				if (!in_array($paramKey, $providedParams)) {
					$missingParams[] = $paramKey;
				}
			}
		}

		// If no required parameters are missing, schema validation passes.
		$isValidSchema = empty($missingParams);
	}

	$factors['schema_validation'] = $isValidSchema ? 0.2 : 0.0;

	// Factor 4: Response quality (Weight: 10%)
	$qualityScore = 0.0;
	if (is_string($rawResponse)) {
		// Check for error indicators in the raw text (e.g., "I'm sorry", "Error")
		if (!preg_match('/error|fail|unable|cannot|sorry/i', $rawResponse)) {
			$qualityScore += 0.05;
		}

		// Verify the tool actually exists in our registry (double check)
		if (isset($intentJSON['tool']) && isset($toolsSchema[$intentJSON['tool']])) {
			$qualityScore += 0.05;
		}
	}
	$factors['response_quality'] = $qualityScore;

	// Calculate Total Confidence
	$confidence = array_sum($factors);

	// Ensure confidence stays within bounds [0, 1]
	return max(0.0, min(1.0, $confidence));
}

/**
 * Format arguments for display in confirmation
 *
 * @param array<string, mixed> $arguments The arguments to format (Key=ParamName, Value=Value)
 * @return string Formatted arguments string
 */
function formatArgumentsForDisplay($arguments)
{
	$formattedArgs = [];
	foreach ($arguments as $key => $value) {
		if (is_array($value)) {
			$formattedArgs[] = "- {$key}: " . (empty($value) ? "(empty)" : json_encode($value, JSON_PRETTY_PRINT));
		} else {
			$formattedArgs[] = "- {$key}: {$value}";
		}
	}
	return implode("\n", $formattedArgs);
}

/**
 * Extract action from tool name
 *
 * @param string $toolName The tool name
 * @return string The extracted action
 */
function extractActionFromTool($toolName)
{
	if (preg_match('/^(create|update|delete|list|show|find|search|get|view|validate|send)/i', $toolName, $matches)) {
		return strtolower($matches[1]);
	}
	return 'perform this action';
}
