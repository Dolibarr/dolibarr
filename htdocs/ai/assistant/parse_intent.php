<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026	Nick Fragoulis
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
if (!defined('NOCSRFCHECK')) {		// TODO Enable the CSRF check
	define('NOCSRFCHECK', 1);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/ai/class/mcp.class.php';
require_once DOL_DOCUMENT_ROOT . '/ai/lib/ai.lib.php';
require_once DOL_DOCUMENT_ROOT . '/ai/class/llmadapter.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/ai/class/privacy_guard.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';

// Security check
if (!isModEnabled('ai') || !getDolGlobalString('AI_ASSISTANT_ENABLED')) {
	http_response_code(403);
	accessforbidden('Module or feature not allowed');
}

global $db, $user, $conf, $langs;

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

	if (empty($query)) {
		ob_end_clean();
		echo json_encode(["status" => "ok"]);
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
			$dynamicStopWords[] = mb_strtolower($word);
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
		$firstWord = mb_strtolower($parts[0]);

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
	}

	// AI Execution
	$intentJSON = null;
	$confidence = 0.0;
	$allToolsSchema = [];

	if ($serviceKey && $serviceKey !== '-1') {
		$providerUsed = $serviceKey;
		$mcp = new McpHandler($db, $user);

		// Fetch all tools
		$allToolsSchema = $mcp->getToolsSchema();

		// Detect if query is in a Non-Latin language (Russian, Greek, Chinese, Arabic, etc.)
		$isComplex = isComplexScript($query);

		$toolsSchema = [];

		if ($isComplex) {
			// We send ALL tools to ensure accuracy.
			dol_syslog("AI Pro: Non-Latin language detected. Sending full (cleaned) schema.");
			$toolsSchema = $allToolsSchema;
		} else {
			// Detect in which business family the query is using Hybrid (Translations + Synonyms)
			$detectedCategories = classifyIntentUniversal($query, $langs);

			// Filter Logic
			$toolsSchema = filterToolsProfessional($allToolsSchema, $detectedCategories);

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
		$systemRules .= "IMPORTANT: If the user asks for functionality that is NOT available in the list of Tools above, you MUST use the tool 'respond_to_user' to inform them that the specific feature is not available.";

		// If MCP is disabled, we disable all tools
		if (getDolGlobalString('AI_ASSISTANT_DISABLE_TOOLS')) {
			$toolsForLLM = array();
		}

		$systemPrompt = $basePrompt . "\n\n";
		$systemPrompt .= "Tools:\n" . json_encode($toolsForLLM, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$systemPrompt .= $systemRules . " Date: " . date('Y-m-d');

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
		$adapterType = $servicesList[$serviceKey]['adapter_type'] ?? 'openai';


		// The request.
		// var_dump($query);

		if (!empty($apiKey)) {
			$adapter = new UniversalLLMAdapter($adapterType, $apiKey, $url, $model, $timeout);

			dol_syslog("parse_intent.php Call AI API", LOG_DEBUG);

			$rawResponse = $adapter->generate($systemPrompt, $query);

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
					$validToolNames = array_column($toolsSchema, 'name');
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
 * Detect intent categories from a user query.
 *
 * This function analyzes a natural language query and attempts to classify it
 * into one or more predefined intent categories (e.g., billing, commercial,
 * thirdparty, stock, project, reporting).
 *
 * It leverages Dolibarr translations (`$langs->trans()`) to match localized
 * keywords, and applies additional synonym matching for Latin-based queries.
 * For non-Latin scripts, it performs a simpler substring search.
 *
 * Matching strategy:
 * - Latin queries: normalized (lowercase + unaccent) and matched using regex word boundaries.
 * - Non-Latin queries: matched using case-insensitive substring search.
 *
 * Each category is detected if at least one keyword or synonym matches.
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
			'synonyms' => ['paid', 'unpaid', 'pay', 'money', 'cost', 'amount']
		],
		'commercial' => [
			'keys'     => ['Order', 'Proposal', 'Quote', 'SupplierOrder', 'OrderStatusDraft'],
			'synonyms' => ['sale', 'buy', 'purchase', 'contract', 'shipping']
		],
		'thirdparty' => [
			'keys'     => ['ThirdParty', 'Customer', 'Supplier', 'Contact', 'Company'],
			'synonyms' => ['client', 'partner', 'address', 'phone']
		],
		'stock' => [
			'keys'     => ['Product', 'Service', 'Stock', 'Warehouse'],
			'synonyms' => ['item', 'inventory', 'sku', 'location', 'qty']
		],
		'project' => [
			'keys'     => ['Project', 'Task'],
			'synonyms' => ['milestone', 'gantt', 'team']
		],
		'reporting' => [
			'keys'     => ['Report', 'Statistics', 'Turnover', 'Revenue', 'Income'],
			'synonyms' => ['graph', 'chart', 'analytics', 'dashboard', 'kpi']
		]
	];

	$detectedCategories = [];
	foreach ($intentMap as $category => $data) {
		$keywords = [];
		foreach ($data['keys'] as $key) {
			$trans = $langs->trans($key);
			if ($isLatin) {
				$trans = strtolower(dol_string_unaccent($trans));
			}
			$keywords[] = $trans;
			if (!$isLatin && $key !== $trans) {
				$keywords[] = strtolower($key);
			}
		}
		if ($isLatin) {
			foreach ($data['synonyms'] as $syn) {
				$keywords[] = dol_string_unaccent($syn);
			}
		}
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
				if (mb_stripos($searchQuery, $word) !== false) {
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
