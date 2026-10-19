<?php
/* Copyright (C) 2022		Alice Adminson			<aadminson@example.com>
 * Copyright (C) 2024-2025  Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026		Anthony Damhet			<a.damhet@progiseize.fr>
 * Copyright (C) 2026		Nick Fragoulis
 * Copyright (C) 2026		Jose Martinez			<jose.martinez@pichinov.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/ai/lib/ai.lib.php
 * \ingroup ai
 * \brief   Library files with common functions for Ai
 */

include_once DOL_DOCUMENT_ROOT.'/ai/class/ai.class.php';


/**
 * Prepare admin pages header
 *
 * @return array<string,array<string,string>>
 */
function getListOfAIFeatures()
{
	global $langs;

	$arrayofaifeatures = array(
		'textgenerationemail' => array('label' => $langs->trans('TextGeneration').' ('.$langs->trans("EmailContent").')', 'picto' => '', 'status' => 'dolibarr', 'function' => 'TEXT', 'placeholder' => Ai::AI_DEFAULT_PROMPT_FOR_EMAIL),
		'textgenerationwebpage' => array('label' => $langs->trans('TextGeneration').' ('.$langs->trans("WebsitePage").')', 'picto' => '', 'status' => 'dolibarr', 'function' => 'TEXT', 'placeholder' => Ai::AI_DEFAULT_PROMPT_FOR_WEBPAGE),
		'textgeneration' => array('label' => $langs->trans('TextGeneration').' ('.$langs->trans("Other").')', 'picto' => '', 'status' => 'notused', 'function' => 'TEXT'),

		'texttranslation' => array('label' => $langs->trans('TextTranslation'), 'picto' => '', 'status'=>'dolibarr', 'function' => 'TEXT', 'placeholder' => Ai::AI_DEFAULT_PROMPT_FOR_TEXT_TRANSLATION),
		'textsummarize' => array('label' => $langs->trans('TextSummarize'), 'picto' => '', 'status'=>'dolibarr', 'function' => 'TEXT', 'placeholder' => Ai::AI_DEFAULT_PROMPT_FOR_TEXT_SUMMARIZE),
		'textspellchecker' => array('label' => $langs->trans('TextSpellChecker'), 'picto' => '', 'status'=>'dolibarr', 'function' => 'TEXT', 'placeholder' => Ai::AI_DEFAULT_PROMPT_FOR_TEXT_SPELLCHECKER),
		'textrephraser' => array('label' => $langs->trans('TextRephraser'), 'picto' => '', 'status'=>'dolibarr', 'function' => 'TEXT', 'placeholder' => Ai::AI_DEFAULT_PROMPT_FOR_TEXT_REPHRASER),

		'textgenerationextrafield' => array('label' => $langs->trans('TextGeneration').' ('.$langs->trans("ExtrafieldFiller").')', 'picto' => '', 'status'=>'dolibarr', 'function' => 'TEXT', 'placeholder' => Ai::AI_DEFAULT_PROMPT_FOR_EXTRAFIELD_FILLER),

		'imagegeneration' => array('label' => 'ImageGeneration', 'picto' => '', 'status' => 'notused', 'function' => 'IMAGE'),
		'videogeneration' => array('label' => 'VideoGeneration', 'picto' => '', 'status' => 'notused', 'function' => 'VIDEO'),
		'audiogeneration' => array('label' => 'AudioGeneration', 'picto' => '', 'status' => 'notused', 'function' => 'AUDIO'),
		'transcription' => array('label' => 'AudioTranscription', 'picto' => '', 'status' => 'notused', 'function' => 'TRANSCRIPT'),
		'translation' => array('label' => 'AudioTranslation', 'picto' => '', 'status' => 'notused', 'function' => 'TRANSLATE'),
		'docparsing' => array('label' => 'DocumentParsing', 'picto' => '', 'status' => 'experimental', 'function' => 'DOCPARSING')
	);

	return $arrayofaifeatures;
}

/**
 * Get list of available ai services
 *
 * @return array<int|string,mixed>
 */
function getListOfAIServices()
{
	global $langs;

	$arrayofai = array(
		'-1' => array('label' => $langs->trans('SelectAService')),
		'chatgpt' => array(
			'label'           => 'ChatGPT (OpenAI)',
			'url'             => 'https://api.openai.com/v1/',
			'setup'           => 'https://platform.openai.com/account/api-keys',
			'textgeneration'  => array('default' => 'gpt-5.6'),             //  updated Oct 2026
			'imagegeneration' => array('default' => 'gpt-image-2'),       // Replaced DALL-E 3; 4x faster and native to GPT-5
			'audiogeneration' => array('default' => 'gpt-audio-1.5'),       // New Feb 23, 2026 release for high-fidelity audio out
			'videogeneration' => array('default' => 'sora-2'),              // OpenAI's standard API video model
			'transcription'   => array('default' => 'gpt-transcribe'), 		// Dedicated model
			'translation'     => array('default' => 'gpt-5.6'),				 // Still the best for multi-language audio translation
			'docparsing'      => array('default' => 'gpt-5.6'),             // Uses the new Responses API / Vision capabilities
			'adapter_type'    => 'openai'
		),
		'groq' => array(
			'label'           => 'Groq (LPU Inference)',
			'url'             => 'https://api.groq.com/openai/v1/',
			'setup'           => 'https://console.groq.com/keys',
			'textgeneration'  => array('default' => 'llama-4-8b-instant'),    // February 2026 flagship for extreme speed (1,000+ t/s)
			'imagegeneration' => array('default' => 'na'),
			'audiogeneration' => array('default' => 'na'),
			'videogeneration' => array('default' => 'na'),
			'transcription'   => array('default' => 'whisper-large-v3-turbo'), // Groq's specialized high-speed Whisper implementation
			'translation'     => array('default' => 'whisper-large-v3-turbo'), // High-speed audio translation to English
			'docparsing'      => array('default' => 'llama-4-70b-versatile'),  // Best for structured data extraction from text
			'adapter_type'    => 'openai'
		),
		'mistral' => array(
			'label' => 'Mistral AI',
			'url' => 'https://api.mistral.ai/v1/',
			'setup' => 'https://console.mistral.ai/api-keys/',
			'textgeneration' => array('default' => 'mistral-small-latest', 'examples' => 'mistral-tiny-latest, mistral-small-latest, mistral-medium-latest, mistral-large-latest'),    // Points to Mistral Small 3 (updated Feb 2026)
			'imagegeneration' => array('default' => 'na'),
			'audiogeneration' => array('default' => 'na'),
			'videogeneration' => array('default' => 'na'),
			'transcription' => array('default' => 'na'),
			'translation' => array('default' => 'na'),
			'docparsing' => array('default' => 'pixtral-12b-latest'),         // Mistral's native vision/doc model
			'adapter_type' => 'openai'
		),
		'deepseek' => array(
			'label' => 'DeepSeek',
			'url' => 'https://api.deepseek.com',
			'setup' => 'https://platform.deepseek.com/api_keys',
			'textgeneration' => array('default' => 'deepseek-v4'),             // Released Feb 2026, flagship MoE model
			'imagegeneration' => array('default' => 'deepseek-janus-2'),       // DeepSeek's latest multimodal vision/gen model
			'audiogeneration' => array('default' => 'na'),
			'videogeneration' => array('default' => 'na'),
			'transcription' => array('default' => 'na'),
			'translation' => array('default' => 'na'),
			'docparsing' => array('default' => 'deepseek-v4'),                 // Massive 1M context support for parsing
			'adapter_type' => 'openai'
		),
		'perplexity' => array(
			'label' => 'Perplexity (Sonar)',
			'url' => 'https://api.perplexity.ai',
			'setup' => 'https://www.perplexity.ai/settings/api',
			'textgeneration' => array('default' => 'sonar-pro'),               // Flagship search model as of Feb 2026
			'imagegeneration' => array('default' => 'na'),
			'audiogeneration' => array('default' => 'na'),
			'videogeneration' => array('default' => 'na'),
			'transcription' => array('default' => 'na'),
			'translation' => array('default' => 'na'),
			'docparsing' => array('default' => 'sonar-reasoning'),             // Best for analyzing search-grounded docs
			'adapter_type' => 'openai'
		),
		'zai' => array(
			'label' => 'Zhipu AI (GLM)',
			'url' => 'https://api.z.ai/api/paas/v4',
			'setup' => 'https://docs.z.ai/guides/overview/quick-start',
			'textgeneration' => array('default' => 'glm-5'),                  // Flagship released February 11, 2026
			'imagegeneration' => array('default' => 'cogview-4'),              // Zhipu's latest SOTA image generator
			'audiogeneration' => array('default' => 'cogvlm2-audio'),          // High-fidelity conversational audio
			'videogeneration' => array('default' => 'cogvideox-2'),            // Flagship API video model
			'transcription' => array('default' => 'na'),
			'translation' => array('default' => 'na'),
			'docparsing' => array('default' => 'glm-5'),                      // Top-tier agentic document processing
			'adapter_type' => 'openai'
		),
		'custom' => array(
			'label' => 'Custom',
			'url' => 'https://domainofapi.com/v1/',
			'setup' => 'Ask your AI provider how to get your API key',
			'textgeneration' => array('default' => 'tinyllama-1.1b'),
			'imagegeneration' => array('default' => 'mixtral-8x7b-32768'),
			'audiogeneration' => array('default' => 'mixtral-8x7b-32768'),
			'videogeneration' => array('default' => 'na'),
			'transcription' => array('default' => 'mixtral-8x7b-32768'),
			'translation' => array('default' => 'mixtral-8x7b-32768'),
			'docparsing' => array('default' => 'na'),
			'adapter_type' => 'openai'
		),
		// --- SPECIALIZED ADAPTERS ---
		'anthropic' => array(
			'label' => 'Anthropic (Claude)',
			'url' => 'https://api.anthropic.com/v1/',
			'setup' => 'https://console.anthropic.com/',
			'textgeneration' => array('default' => 'claude-opus-5'),    // Current Anthropic flagship; 1M context window
			'imagegeneration' => array('default' => 'na'),              // Anthropic remains focused on text/code logic
			'audiogeneration' => array('default' => 'na'),
			'videogeneration' => array('default' => 'na'),
			'transcription' => array('default' => 'na'),
			'translation' => array('default' => 'na'),
			'docparsing' => array('default' => 'claude-opus-5'),      // Leading model for "Computer Use" and PDF analysis
			'adapter_type' => 'anthropic'
		),
		'google' => array(
			'label' => 'Google Gemini',
			'url' => 'https://generativelanguage.googleapis.com/v1beta/',
			'setup' => 'https://aistudio.google.com/',
			'textgeneration' => array('default' => 'gemini-3.1-pro-preview'), // Flagship reasoning model released Feb 19, 2026
			'imagegeneration' => array('default' => 'nano-banana-pro'),       // Latest SOTA image model (Gemini 3 Pro Image)
			'audiogeneration' => array('default' => 'gemini-2.5-pro-tts'),    // High-fidelity native speech synthesis
			'videogeneration' => array('default' => 'veo-3.1'),              // Google's flagship cinematic video API
			'transcription' => array('default' => 'gemini-3.1-pro-preview'),  // Native multi-modal audio reasoning
			'translation' => array('default' => 'gemini-3.1-pro-preview'),    // Native audio-to-text translation
			'docparsing' => array('default' => 'gemini-3.1-pro-preview'),     // Massive 2M+ context window for full repo parsing
			'adapter_type' => 'google'
		)
	);

	return $arrayofai;
}

/**
 * Tests the connection to an AI service using its API key and URL by sending message "Hello"
 *
 * This function supports multiple AI providers (Google Gemini, Anthropic Claude, and OpenAI-compatible APIs like
 * Mistral, Groq, and DeepSeek). It constructs a minimal, provider-specific request payload and sends it
 * to the given endpoint to verify that the API key is valid and the service is reachable.
 *
 * @param string $service The identifier of the AI service (e.g., 'google', 'anthropic', 'openai', 'mistral').
 * @param string $key The API key for the service.
 * @param string $url The base URL of the AI service's API endpoint.
 *
 * @return array{success: bool, message: string} An associative array indicating the result of the test.
 *               - 'success' is true on a successful connection (HTTP 2xx), false otherwise.
 *               - 'message' provides details, such as "OK (HTTP 200)" or an error description.
 */
function testAIConnection(string $service, string $key, string $url): array
{
	if (empty($key)) {
		return ['success' => false, 'message' => 'API Key is empty'];
	}

	// Load Defaults (Ensure this function exists or handle the error)
	if (!function_exists('getListOfAIServices')) {
		return ['success' => false, 'message' => 'Configuration helper function missing.'];
	}

	$list = getListOfAIServices();
	$defUrl = $list[$service]['url'] ?? '';
	// Use model from config, fallback to hardcoded if necessary
	$defaultModel = $list[$service]['model'] ?? 'unknown';

	// Normalize URL
	if (empty($url)) {
		$url = $defUrl;
	}
	$url = rtrim($url, '/');

	$data = [];
	$headers = ["Content-Type: application/json"];

	$model = '';
	if (empty($model)) {
		$model = getDolGlobalString('AI_API_' . strtoupper($service) . '_MODEL_TEXT');
	}

	// GOOGLE
	if ($service == 'google' || strpos($url, 'googleapis') !== false) {
		if (strpos($url, ':generateContent') === false) {
			if (strpos($url, 'models') === false) {
				$url .= "/models/$model:generateContent";
			} else {
				$url .= "/$model:generateContent";
			}
		}
		$url .= "?key=" . $key;
		$data = ["contents" => [ ["parts" => [ ["text" => "Hello"] ] ] ], "generationConfig" => ["maxOutputTokens" => 5]];
	} elseif ($service == 'anthropic' || strpos($url, 'anthropic') !== false) {  // ANTHROPIC
		if (strpos($url, 'messages') === false) $url .= '/messages';
		$headers[] = "x-api-key: $key";
		$headers[] = "anthropic-version: 2023-06-01";
		$data = [
			"model" => $model, // Uses Configured Model
			"messages" => [["role" => "user", "content" => "Hello"]],
			"max_tokens" => 5
		];
	} else {
		if (strpos($url, '/chat/completions') === false) $url .= '/chat/completions';
		$headers[] = "Authorization: Bearer $key";

		$data = [
			"model" => $model, // Uses Configured Model (from Priority Chain)
			"messages" => [["role" => "user", "content" => "Hello"]],
			"max_tokens" => 5
		];
	}

	// Execute request with the Dolibarr HTTP wrapper (handles proxy, SSL and logging)
	include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

	// By default, we accept only external endpoints ($dolibarr_ai_allow_local_endpoints is not set).
	// To allow local endpoints, we must set $dolibarr_ai_allow_local_endpoints to 1 or 2 in conf.php.
	global $dolibarr_ai_allow_local_endpoints;
	$localurl = empty($dolibarr_ai_allow_local_endpoints) ? 0 : 2;

	$result = getURLContent($url, 'POST', json_encode($data), 1, $headers, array('http', 'https'), $localurl, -1, 0, 10);
	$httpCode = (int) ($result['http_code'] ?? 0);
	$responseContent = (string) ($result['content'] ?? '');

	if (!empty($result['curl_error_no'])) {
		return ['success' => false, 'message' => "Curl Error: ".($result['curl_error_msg'] ?? '')];
	}

	if ($httpCode >= 200 && $httpCode < 300) {
		return ['success' => true, 'message' => "OK (HTTP $httpCode)."];
	} else {
		$json = json_decode($responseContent, true);
		// Attempt to find the error message in various common structures
		$msg = $json['error']['message'] ?? $json['message'] ?? substr($responseContent, 0, 150);
		return ['success' => false, 'message' => "HTTP $httpCode. Error: $msg"];
	}
}


/**
 * Validate chat attachments before they reach any LLM provider.
 *
 * The MIME type comes from the browser's File.type (client-controlled), so it
 * is checked server-side against a strict allowlist of what every wired
 * provider can natively consume (images and PDF). Size is bounded per
 * attachment and in total: base64 travels inside the JSON POST body and is
 * re-sent to the provider, so an unbounded payload is both a memory and a
 * billing hazard. When the privacy redaction policy is enforced, attachments
 * are refused entirely: text is masked by PrivacyGuard before a cloud call,
 * but a document's content cannot be, so sending it would bypass the policy.
 *
 * @param array<int,array{mime:string,data:string}> $attachments Parsed attachments
 * @param string $error Set to a client-safe message when validation fails
 * @return bool True when all attachments may be sent
 */
function ai_validate_attachments(array $attachments, &$error)
{
	global $langs;

	$error = '';
	if (empty($attachments)) {
		return true;
	}
	$langs->load("other");	// owns the AIAttachment* keys; callers load it later or not at all

	if (getDolGlobalInt('AI_PRIVACY_REDACTION', 0)) {
		$error = $langs->trans("AIAttachmentBlockedByPrivacy");

		return false;
	}

	// Cap the number of attachments server-side too: the chat enforces it
	// client-side only, and other callers may not. 0 means unlimited.
	$maxfiles = getDolGlobalInt('AI_ATTACHMENT_MAX_FILES', 5);
	if ($maxfiles > 0 && count($attachments) > $maxfiles) {
		$error = $langs->trans("AIAttachmentTooMany", (string) $maxfiles);

		return false;
	}

	$allowedmimes = array('application/pdf', 'image/png', 'image/jpeg', 'image/gif', 'image/webp');
	// HEIC/HEIF reach this point only through the native-send fallback of the
	// chat (browser unable to transcode): acceptable solely when the active
	// provider consumes them (Gemini); other providers 400 on the MIME.
	if ((getListOfAIServices()[getDolGlobalString('AI_API_SERVICE')]['adapter_type'] ?? '') === 'google') {
		$allowedmimes[] = 'image/heic';
		$allowedmimes[] = 'image/heif';
	}
	$maxbytes = getDolGlobalInt('AI_ATTACHMENT_MAX_MB', 10) * 1024 * 1024;
	$totalbytes = 0;
	foreach ($attachments as $att) {
		if (!in_array($att['mime'], $allowedmimes, true)) {
			$error = $langs->trans("AIAttachmentTypeNotAllowed", $att['mime']);

			return false;
		}
		// 3/4 ratio: decoded size of a base64 payload without decoding it.
		$bytes = (int) (strlen($att['data']) * 3 / 4);
		$totalbytes += $bytes;
		if ($bytes > $maxbytes || $totalbytes > $maxbytes) {
			$error = $langs->trans("AIAttachmentTooLarge", (string) getDolGlobalInt('AI_ATTACHMENT_MAX_MB', 10));

			return false;
		}
	}

	return true;
}

/**
 * Trim a payload for the request log, keeping the beginning and the end.
 *
 * Request payloads start with the tool schemas and end with what a human
 * actually looks for: the system rules, the user query and the page context.
 * A plain head cut removes the interesting half, so keep both sides and state
 * how much was dropped in between.
 *
 * @param string $text Payload to trim.
 * @param int    $max  Maximum number of characters to keep.
 * @return string Trimmed payload, unchanged when short enough.
 */
function aiTruncateForLog($text, $max = 60000)
{
	$len = dol_strlen($text);
	if ($len <= $max) {
		return $text;
	}
	$head = (int) floor($max / 2);
	$tail = $max - $head;

	return dol_substr($text, 0, $head)
		."\n... [Truncated ".($len - $max)." chars] ...\n"
		.dol_substr($text, $len - $tail, $tail);
}

/**
 * Log AI Request with Raw Payloads
 *
 * @param   DoliDB                  $db         Database object
 * @param   User                    $user       User object
 * @param   string                  $query      The query sent to the AI
 * @param   array<string, mixed>    $response   The full response from the AI
 * @param   string                  $provider   The AI provider (e.g., 'OpenAI', 'Anthropic')
 * @param   float                   $time       Execution time in seconds
 * @param   float                   $confidence Confidence score from the AI (if any)
 * @param   string                  $status     Status of the request (e.g., 'success', 'error')
 * @param   string                  $error      Error message, if any
 * @param   string                  $rawReq     Raw request payload
 * @param   string                  $rawRes     Raw response payload
 * @param   array{fk_actioncomm?:int,input_hash?:string,output_hash?:string,security_hash?:string,preserve_payloads?:bool,tokens_input?:int,tokens_output?:int,model?:string} $context Optional event link, audit metadata and provider token usage
 * @param   int|null                $logId      Output: inserted row id, or 0 when logging is disabled or fails
 * @param-out int                   $logId
 * @return  int									Return 0
 */
function ai_log_request($db, $user, $query, array $response, $provider, float $time, float $confidence, $status, $error = '', $rawReq = '', $rawRes = '', array $context = array(), &$logId = null)
{
	global $conf;

	$logId = 0;

	if (!getDolGlobalInt('AI_LOG_REQUESTS')) {
		return 0;
	}

	$tool = isset($response['tool']) ? (string) $response['tool'] : '';

	// Keep both ends when trimming: a request payload starts with the tool
	// schemas (tens of kB, identical on every call) and ends with the system
	// rules, the user query and the page context - the part anyone reads a
	// log for. Cutting only the tail threw exactly that away.
	// Structured tool output must remain valid JSON for subsequent reads.
	$rawResStr = (string) $rawRes;
	if (empty($context['preserve_payloads'])) {
		$rawReq = aiTruncateForLog($rawReq, 60000);
		$rawResStr = aiTruncateForLog($rawResStr, 60000);
	}

	$sql = "INSERT INTO " . $db->prefix() . "ai_request_log (";
	$sql .= "entity, date_request, fk_user, query_text, tool_name, provider, ";
	$sql .= "execution_time, confidence, status, error_msg, raw_request_payload, raw_response_payload";
	// Each optional group keys on ITS OWN entries, so a caller passing only
	// token usage does not drag empty audit hashes along, and vice versa.
	$hasAudit = isset($context['fk_actioncomm']) || isset($context['input_hash']) || isset($context['output_hash']) || isset($context['security_hash']);
	$hasUsage = isset($context['tokens_input']) || isset($context['tokens_output']) || isset($context['model']);
	if ($hasAudit) {
		$sql .= ", fk_actioncomm, input_hash, output_hash, security_hash";
	}
	if ($hasUsage) {
		$sql .= ", tokens_input, tokens_output, model";
	}
	$sql .= ") VALUES (";
	$sql .= ((int) $conf->entity) . ", ";
	$sql .= "'" . $db->idate(dol_now()) . "', ";
	$sql .= ((int) $user->id) . ", ";
	$sql .= "'" . $db->escape($query) . "', ";
	$sql .= "'" . $db->escape($tool) . "', ";
	$sql .= "'" . $db->escape($provider) . "', ";
	$sql .= ((float) $time) . ", ";
	$sql .= ((float) $confidence) . ", ";
	$sql .= "'" . $db->escape($status) . "', ";
	$sql .= "'" . $db->escape($error) . "', ";
	$sql .= "'" . $db->escape($rawReq) . "', ";
	$sql .= "'" . $db->escape($rawResStr) . "'";
	if ($hasAudit) {
		$sql .= ", ".(!empty($context['fk_actioncomm']) && $context['fk_actioncomm'] > 0 ? (int) $context['fk_actioncomm'] : 'NULL');
		$sql .= ", '".$db->escape($context['input_hash'] ?? '')."'";
		$sql .= ", '".$db->escape($context['output_hash'] ?? '')."'";
		$sql .= ", '".$db->escape($context['security_hash'] ?? '')."'";
	}
	if ($hasUsage) {
		$sql .= ", ".(isset($context['tokens_input']) ? (int) $context['tokens_input'] : 'NULL');
		$sql .= ", ".(isset($context['tokens_output']) ? (int) $context['tokens_output'] : 'NULL');
		$sql .= ", '".$db->escape((string) ($context['model'] ?? ''))."'";
	}
	$sql .= ")";

	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog(__FUNCTION__.": ".$db->lasterror(), LOG_ERR);
	} else {
		$logId = (int) $db->last_insert_id($db->prefix()."ai_request_log");
	}

	return 0;
}

/**
 * Get list for AI summarize
 *
 * @return array<int|string,mixed>
 */
function getListForAISummarize()
{
	$arrayforaisummarize = array(
		//'20_w' => 'SummarizeTwentyWords',
		'50_w' => 'SummarizeFiftyWords',
		'100_w' => 'SummarizeHundredWords',
		'200_w' => 'SummarizeTwoHundredWords',
		'1_p' => 'SummarizeOneParagraphs',
		'2_p' => 'SummarizeTwoParagraphs',
		'25_pc' => 'SummarizeTwentyFivePercent',
		'50_pc' => 'SummarizeFiftyPercent',
		'75_pc' => 'SummarizeSeventyFivePercent'
	);

	return $arrayforaisummarize;
}

/**
 * Get list for AI style of writing
 *
 * @return array<int|string,mixed>
 */
function getListForAIRephraseStyle()
{
	$arrayforaierephrasestyle = array(
		'spellchecker' => 'RephraseSpellChecker',
		'professional' => 'RephraseStyleProfessional',
		'humouristic' => 'RephraseStyleHumouristic',
	);

	return $arrayforaierephrasestyle;
}

/**
 * Prepare admin pages header
 *
 * @return array<array{0:string,1:string,2:string}>
 */
function aiAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("agenda");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/ai/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/ai/admin/custom_prompt.php", 1);
	$head[$h][1] = $langs->trans("CustomPrompt");
	$head[$h][2] = 'custom';
	$h++;

	if (getDolGlobalString("MAIN_FEATURES_LEVEL") >= 1) {
		$head[$h][0] = dol_buildpath("/ai/admin/assistant.php", 1);
		$head[$h][1] = $langs->trans("Assistant");
		$head[$h][2] = 'assistant';
		$h++;
	}

	if (getDolGlobalString("MAIN_FEATURES_LEVEL") >= 1) {
		$head[$h][0] = dol_buildpath("/ai/admin/server_mcp.php", 1);
		$head[$h][1] = $langs->trans("MCPServer");
		$head[$h][2] = 'servermcp';
		$h++;
	}

	if (getDolGlobalString("MAIN_FEATURES_LEVEL") >= 1) {
		$head[$h][0] = dol_buildpath("/ai/admin/configure_tools.php", 1);
		$head[$h][1] = $langs->trans("ToolAccessControl");
		$head[$h][2] = 'tools';
		$h++;
	}

	/*
	$head[$h][0] = dol_buildpath("/ai/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@ai:/ai/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@ai:/ai/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'ai@ai');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'ai@ai', 'remove');

	return $head;
}

/**
 * Resolve the AI provider/service currently configured for the AI Assistant
 * (e.g. "ChatGPT (OpenAI)", "Google Gemini", "Anthropic (Claude)"), so it can be
 * displayed in the chat header. The precise model name is intentionally not
 * shown here, only which AI is in use.
 *
 * @return string	The provider label, or '' if no service is configured
 */
function getAiAssistantProviderLabel()
{
	$serviceKey = getDolGlobalString('AI_API_SERVICE');
	if (empty($serviceKey) || $serviceKey === '-1') {
		return '';
	}

	$services = getListOfAIServices();

	return isset($services[$serviceKey]['label']) ? (string) $services[$serviceKey]['label'] : (string) $serviceKey;
}

/**
 * Resolve the text model used by the AI Assistant when the picker is on "Auto"
 * (same resolution order as assistant/parse_intent.php).
 *
 * @return string	The model id, or '' if no service is configured
 */
function getAiAssistantDefaultModel()
{
	$serviceKey = getDolGlobalString('AI_API_SERVICE');
	if (empty($serviceKey) || $serviceKey === '-1') {
		return '';
	}

	$services = getListOfAIServices();
	$prefix = 'AI_API_'.strtoupper($serviceKey);
	$model = getDolGlobalString($prefix.'_MODEL_TEXT')
		?: getDolGlobalString($prefix.'_MODEL')
		?: ($services[$serviceKey]['textgeneration']['default'] ?? '');

	return is_string($model) ? $model : '';
}

/**
 * Resolve the API URL used by the AI Assistant for the configured provider
 * (same resolution order as assistant/parse_intent.php).
 *
 * @return string	The API base URL, or '' if no service is configured
 */
function getAiAssistantProviderUrl()
{
	$serviceKey = getDolGlobalString('AI_API_SERVICE');
	if (empty($serviceKey) || $serviceKey === '-1') {
		return '';
	}

	$services = getListOfAIServices();

	return (string) (getDolGlobalString('AI_API_'.strtoupper($serviceKey).'_URL') ?: ($services[$serviceKey]['url'] ?? ''));
}

/**
 * Return the list of model ids offered by the configured AI provider, with a
 * 1-hour cache in the constant AI_MODELS_LIST_CACHE (Anthropic GET /models,
 * Google GET /models, OpenAI-compatible GET /models). Shared by the AJAX
 * endpoint ai/ajax/list_models.php (datalists, chat picker) and by the
 * model-availability warning banner of the admin models page.
 *
 * @param DoliDB $db           Database handler (to store the cache constant)
 * @param bool   $forcerefresh True to bypass the cache and query the live list
 * @return array{service:string,models:string[]} Active service key and its sorted model ids (empty list when the provider is not configured, offers no listing API, or the call fails)
 */
function getAiProviderModelList($db, $forcerefresh = false)
{
	global $conf;

	$serviceKey = getDolGlobalString('AI_API_SERVICE');
	if (empty($serviceKey) || $serviceKey == '-1') {
		return array('service' => '', 'models' => array());
	}

	if (!$forcerefresh) {
		$cacheraw = getDolGlobalString('AI_MODELS_LIST_CACHE');
		if ($cacheraw) {
			$cache = json_decode($cacheraw, true);
			if (is_array($cache) && !empty($cache['service']) && $cache['service'] === $serviceKey
				&& !empty($cache['ts']) && (dol_now() - (int) $cache['ts']) < 3600
				&& !empty($cache['models']) && is_array($cache['models'])) {
				return array('service' => $serviceKey, 'models' => $cache['models']);
			}
		}
	}

	include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

	$servicesList = getListOfAIServices();
	$adapterType = $servicesList[$serviceKey]['adapter_type'] ?? 'openai';
	$defUrl = $servicesList[$serviceKey]['url'] ?? '';
	$baseUrl = rtrim(getDolGlobalString('AI_API_'.strtoupper($serviceKey).'_URL') ?: $defUrl, '/');

	$apiKey = getDolGlobalString('AI_API_'.strtoupper($serviceKey).'_KEY');
	if (preg_match('/^crypt:/', $apiKey)) {
		$apiKey = dolDecrypt($apiKey, $conf->file->instance_unique_id);
	}
	if (empty($apiKey) || empty($baseUrl)) {
		return array('service' => $serviceKey, 'models' => array());
	}

	$models = array();
	if ($adapterType === 'anthropic') {
		$headers = array('x-api-key: '.$apiKey, 'anthropic-version: 2023-06-01');
		$res = getURLContent($baseUrl.'/models?limit=100', 'GET', '', 1, $headers, array('http', 'https'), 2);
		$json = json_decode($res['content'] ?? '', true);
		foreach ((array) ($json['data'] ?? array()) as $m) {
			if (!empty($m['id'])) {
				$models[] = (string) $m['id'];
			}
		}
	} elseif ($adapterType === 'google') {
		$res = getURLContent($baseUrl.'/models?pageSize=200&key='.urlencode($apiKey), 'GET', '', 1, array(), array('http', 'https'), 2);
		$json = json_decode($res['content'] ?? '', true);
		foreach ((array) ($json['models'] ?? array()) as $m) {
			if (!empty($m['name'])) {
				$models[] = preg_replace('/^models\//', '', (string) $m['name']);
			}
		}
	} else {
		// OpenAI-compatible providers (OpenAI, Mistral, Groq, DeepSeek, custom...)
		$headers = array('Authorization: Bearer '.$apiKey);
		$res = getURLContent($baseUrl.'/models', 'GET', '', 1, $headers, array('http', 'https'), 2);
		$json = json_decode($res['content'] ?? '', true);
		foreach ((array) ($json['data'] ?? array()) as $m) {
			if (!empty($m['id'])) {
				$models[] = (string) $m['id'];
			}
		}
	}

	$models = array_values(array_unique($models));
	sort($models);

	if (count($models)) {
		dolibarr_set_const($db, 'AI_MODELS_LIST_CACHE', json_encode(array('service' => $serviceKey, 'ts' => dol_now(), 'models' => $models)), 'chaine', 0, '', $conf->entity);
	}

	return array('service' => $serviceKey, 'models' => $models);
}

/**
 * Suggest the closest available model id for a model that disappeared from the
 * provider's list: same family first (shared leading token, e.g. 'gemini',
 * 'gpt', 'claude'), then overall string similarity. Used by the warning banner
 * of the admin models page to propose a replacement.
 *
 * @param string   $missing Configured model id that is no longer offered
 * @param string[] $models  Model ids currently offered by the provider
 * @return string Closest model id, or '' when nothing is similar enough to be a useful suggestion
 */
function aiSuggestClosestModel($missing, array $models)
{
	$best = '';
	$bestScore = -1.0;
	foreach ($models as $cand) {
		$pct = 0.0;
		similar_text(strtolower($missing), strtolower($cand), $pct);
		$score = $pct;
		if (strtok(strtolower($missing), '-') === strtok(strtolower($cand), '-')) {
			$score += 15.0;	// same family beats a slightly closer string of another family
		}
		if ($score > $bestScore) {
			$bestScore = $score;
			$best = $cand;
		}
	}
	return ($bestScore >= 50.0) ? $best : '';
}

/**
 * Build the configuration array consumed by the AI Assistant chat frontend (ai/js/ai_assistant.js).
 * It is serialized as JSON into the data-ai-config attribute of the chat container.
 *
 * @return array{mode:string,labels:array<string,string>,baseUrl:string,token:string,userInitial:string}
 */
function getAiChatAssistantConfig()
{
	global $conf, $langs, $user;

	$langs->loadLangs(array('main', 'bills', 'companies', 'products', 'other'));

	$keys = array(
		// Table header labels for common API fields (see FIELD_LABELS in ai_assistant.js)
		'AIAttachmentBlockedByPrivacy', 'AIAttachmentHeicUnsupported', 'AIAttachmentTooMany', 'MissingInformation', 'CouldYouClarify',
		'Ref', 'Label', 'ThirdParty', 'Customer', 'Paid', 'Status', 'Type', 'Email', 'Town', 'Date',
		'DateInvoice', 'DateMaxPayment', 'AmountHT', 'AmountTTC', 'AmountVAT', 'RemainderToPay',
		'Price', 'PriceTTC', 'VATRate', 'CustomerCode', 'SupplierCode', 'Supplier', 'TotalHT', 'TotalTTC',
		// General UI
		'NoDataAvailable',
		'Error',
		'NoRecordFound',
		'Download',
		'Show',
		'Confirm',
		'ConfirmAiAction', 'ConfirmAiWrite',
		'ClearChatHistoryTitle',
		'HistoryCleared',
		'Send',
		'TypeYourQuestion',

		// Placeholders & Status
		'TypeOrSpeak',
		'DocLoaded',
		'Listening',
		'Transcribed',
		'NoSpeech',
		'ProcessingAudio',
		'Timeout',
		'Cancelled',

		// Engine Specific
		'CloudSpeechReady',
		'WhisperReady',
		'DownloadingModel',
		'ModelLoading',

		// Document Processing
		'ProcessingFile',
		'ReadingPdf',
		'PdfError',
		'UnsupportedFileType',
		'TryingOCR',
		'OcrProgress',
		'SwitchingAIModel',
		'OcrFailed',
		'ReadingWord',
		'ReadingExcel',
		'ReadingOdf',

		// Errors
		'MicError',
		'MicTooQuiet',
		'ConnectionBlocked',
		'ConnectionBlockedHelp',
		'WorkerInitFailed',
		'NetworkError',
		'AIError',
		'EmptyAIResponse',
		'BrowserNotSupported',
		'AISessionExpiredReload',

		// Context pins
		'AIContextPinOn',
		'AIContextPinOff',
		'AIContextCounter',
		'AIContextAuto',
		'AIContextAutoTitle',
		'AIContextClear',
		'AIContextClearTitle',
		'AIContextAll',
		'AIContextAllTitle',
		'AIContextAttachmentOnly',

		// Actions & Dialogs
		'YesProceed',
		'Cancel',
		'Submit',
		'ActionCancelled',
		'ExecutingTool',
		'FetchingData',
		'AISearchDoneNowPreparingAction',
		'GeneratingLink',
		'Found',
		'File',
		'Preview',
		'TypeResponse',
		'AINoneOfThese',
		'OpenVerb',
		'AIPdfReport',

		// Voice Confirmation
		'VoiceYesNo',
		'VoiceQuiet',
		'PleaseRepeat',
		'HeardText',

		// Context
		'DocContextIntro',
		'DocContextOutro',

		// Model picker
		'AIModelAuto',
		'AIModelFast',
		'AIModelBalanced',
		'AIModelDeep',
		'AIModelSavedGone'
	);

	$ai_translations = array();
	foreach ($keys as $key) {
		$ai_translations[$key] = $langs->transnoentitiesnoconv($key);
	}
	// Keys whose %s placeholders are consumed CLIENT-side: trans() always
	// sprintf()s the string (empty defaults eat the %s - same trap as the
	// TakePOS split-amount labels), so re-feed literal '%s' as parameters to
	// keep the placeholders intact for the JS .replace() calls.
	$ai_translations['AIContextCounter'] = $langs->transnoentitiesnoconv('AIContextCounter', '%s', '%s', '%s');
	$ai_translations['AIContextAuto'] = $langs->transnoentitiesnoconv('AIContextAuto', '%s');
	$ai_translations['AIContextAutoTitle'] = $langs->transnoentitiesnoconv('AIContextAutoTitle', '%s');
	$ai_translations['AIAttachmentTooMany'] = $langs->transnoentitiesnoconv('AIAttachmentTooMany', '%s');
	$ai_translations['DownloadPdf'] = $langs->transnoentitiesnoconv("Download").' PDF';
	$ai_translations['CloudVoiceRequiresSecureContext'] = $langs->trans(
		"CloudVoiceRequiresSecureContext",
		"HTTPS",
		"localhost",
		"Whisper"
	);

	// First letter of the current user name, shown in the "user" message avatar
	$userinitial = '';
	if (is_object($user)) {
		$namesource = $user->firstname ? $user->firstname : ($user->login ? $user->login : '');
		$userinitial = dol_strtoupper(dol_substr($namesource, 0, 1));
	}

	return array(
		'mode' => getDolGlobalString('AI_DEFAULT_INPUT_MODE'),
		'labels' => $ai_translations,
		// Presentation context for tool results: money, date and label
		// formatting happen client-side on raw API data.
		'privacyRedaction' => getDolGlobalInt('AI_PRIVACY_REDACTION', 0),
		// Attachment count cap, so the client mirrors the server-side guard
		// of ai_validate_attachments() instead of hardcoding its own.
		'maxAttachments' => getDolGlobalInt('AI_ATTACHMENT_MAX_FILES', 5),
		// Recent exchanges that follow the model by default (sliding window).
		// Off (0) until an administrator decides otherwise: past answers carry
		// business content the privacy masking does not cover, so sending them
		// back on every request is not a default the module takes by itself.
		'autoContext' => getDolGlobalInt('AI_CHAT_CONTEXT_AUTO_EXCHANGES', 0),
		// Fast/Balanced/Deep presets of the model picker: mapped onto the provider
		// model list by a name heuristic that is not reliable, so hidden by default.
		'modelPresets' => getDolGlobalInt('AI_SUGGESTS_PRESETS_TO_TRY_TO_CHANGE_MODEL_DYNAMICALLY', 0),
		// Gemini is the only wired provider taking HEIC natively; the chat JS
		// falls back to it when the browser cannot transcode HEIC to JPEG.
		'providerAcceptsHeic' => ((getListOfAIServices()[getDolGlobalString('AI_API_SERVICE')]['adapter_type'] ?? '') === 'google' ? 1 : 0),
		'currency' => $conf->currency,
		'locale' => str_replace('_', '-', $langs->getDefaultLang()),
		'urlRoot' => DOL_URL_ROOT,
		// Endpoints are called with absolute URLs so the chat also works when
		// injected into another page (topbar popover) and not only when served
		// from /ai/assistant/index.php.
		'baseUrl' => dol_buildpath('/ai/assistant/', 1),
		'token' => newToken(),
		'userInitial' => $userinitial,
	);
}

/**
 * Build the HTML of the AI Assistant chat interface.
 * Shared by the standalone page (ai/assistant/index.php) and the topbar popover
 * fragment (ai/assistant/popover.php) so both render the exact same chat.
 *
 * @param	string	$mode	'page' for the standalone full page, 'popover' for the topbar popover fragment
 * @return	string			HTML content
 */
function getAiChatAssistantHtml($mode = 'page')
{
	global $langs, $user, $conf;

	$out = '';

	// Config travels as a data attribute: <script> tags injected via innerHTML
	// are never executed by the browser, so a window.AI_CONFIG inline script
	// would not work for the AJAX-loaded popover.
	$out .= '<div class="ai-chat-container'.($mode === 'popover' ? ' ai-in-popover' : '').'"';
	$out .= ' data-ai-config="'.dol_escape_htmltag(json_encode(getAiChatAssistantConfig())).'"';
	if ($mode === 'page') {
		$out .= ' data-ai-autoinit="1"';
	}
	$out .= '>';

	// Header
	$out .= '<div class="chat-header">';
	if ($mode === 'popover') {
		// In the popover the title links to the full standalone page
		$title = img_picto('', 'fa-robot', '', 0, 0, 0, '', 'paddingright').$langs->trans("AIAssistant");
		$title = '<a href="'.dol_buildpath('/ai/assistant/index.php', 1).'" class="ai-header-link" title="'.dol_escape_htmltag($langs->trans("AIOpenFullPage")).'">'.$title.'</a>';
		$out .= '<h2>'.$title.'</h2>';
	} else {
		// Full page: assistant identity (avatar + title + current LLM model)
		$out .= '<div class="chat-header-id">';
		$out .= '<span class="chat-header-avatar">'.img_picto('', 'fa-robot').'</span>';
		$out .= '<span class="chat-header-text">';
		$out .= '<span class="chat-header-title">'.$langs->trans("AIAssistant").'</span>';
		$aiprovider = getAiAssistantProviderLabel();
		if ($aiprovider !== '') {
			$out .= '<span class="chat-header-status" title="'.dol_escape_htmltag($langs->trans("AIProviderInUse")).'">'.dol_escape_htmltag($aiprovider).'</span>';
		}
		$out .= '</span>';
		$out .= '</div>';
	}
	$out .= '<div class="header-controls">';
	// Model picker pill: 'Auto' (provider default) + presets + the dynamic model
	// list fetched from ajax/list_models.php by the JS. Choice kept in localStorage.
	$modelSelectTitle = $langs->transnoentitiesnoconv("AIModelToUse");
	$aiproviderurl = getAiAssistantProviderUrl();
	if ($aiproviderurl !== '') {
		$modelSelectTitle .= "\n".$langs->transnoentitiesnoconv("URL").': '.$aiproviderurl;
	}
	$out .= '<select id="model-select" class="engine-select model-select" title="'.dol_escape_htmltag($modelSelectTitle, 0, 1).'">';
	$autoLabel = $langs->transnoentitiesnoconv("AIModelAuto");
	$defaultModel = getAiAssistantDefaultModel();
	if ($defaultModel !== '') {
		$autoLabel .= ' ('.$defaultModel.')';
	}
	$out .= '<option value="">'.dol_escape_htmltag($autoLabel).'</option>';
	$out .= '</select>';
	//$out .= ajax_combobox("model-select");

	// Engine Switcher (restyled as a pill with a sparkle icon)
	$out .= '<select id="engine-select" class="engine-select">';
	$out .= '<option value="text">'.$langs->transnoentitiesnoconv("OptionTextOnly").'</option>';
	$out .= '<option value="cloud">'.$langs->transnoentitiesnoconv("OptionCloudFast").'</option>';
	$out .= '<option value="whisper">'.$langs->transnoentitiesnoconv("OptionWhisperLocal").'</option>';
	// Note: the legacy 'local_docs'/'cloud_docs' selector modes are gone — documents
	// are now attached with the always-visible paperclip button and routed
	// automatically (local extraction first, cloud parsing as fallback).
	$out .= '</select>';
	// Clear Button
	$out .= '<button type="button" id="clear-btn" class="icon-btn" title="'.dol_escape_htmltag($langs->trans("ClearChatHistoryTitle")).'">';
	$out .= img_picto('', 'fa-trash').' <span class="ai-btn-label">'.$langs->trans("Clear").'</span>';
	$out .= '</button>';
	if ($mode === 'popover') {
		// Window controls of the popover (handled by the bootstrap JS in main.inc.php).
		// The expand button opens the standalone full page (/ai/assistant/index.php)
		// in the current tab; the popover always stays in its large ("expanded") state.
		$out .= '<button type="button" id="ai-expand-btn" class="icon-btn ai-window-btn" title="'.dol_escape_htmltag($langs->trans("AIOpenFullPage")).'" data-fullscreen-url="'.dol_buildpath('/ai/assistant/index.php', 1).'"><i class="fa fa-expand"></i></button>';
		$out .= '<button type="button" id="ai-close-btn" class="icon-btn ai-window-btn" title="'.dol_escape_htmltag($langs->trans("Close")).'"><i class="fa fa-times"></i></button>';
	}
	$out .= '</div>';
	$out .= '</div>';

	// Chat History
	$out .= '<div id="chat-history" class="chat-history">';
	if ($mode === 'popover') {
		// Compact greeting line for the narrow popover
		$out .= '<div class="msg system">'.$langs->trans("AIWelcomeMessage").'</div>';
	} else {
		// Full page: rich empty-state welcome screen (hidden by JS as soon as the
		// conversation starts, restored on Clear). Quick cards send a localized
		// ready-made prompt on click (data-prompt).
		$welcomename = $user->firstname ? $user->firstname : (is_object($user) ? $user->login : '');
		$quickcards = array(
			array('icon' => 'fa-file-invoice-dollar', 'key' => 'Invoices'),
			array('icon' => 'fa-chart-line', 'key' => 'Revenue'),
			array('icon' => 'fa-coins', 'key' => 'Finance'),
			array('icon' => 'fa-warehouse', 'key' => 'Inventory'),
		);
		$out .= '<div class="chat-welcome">';
		$out .= '<div class="chat-welcome-avatar">'.img_picto('', 'fa-robot').'</div>';
		$out .= '<h2 class="chat-welcome-title">'.dol_escape_htmltag($langs->trans("AIGreeting", $welcomename)).'</h2>';
		$out .= '<p class="chat-welcome-subtitle">'.dol_escape_htmltag($langs->trans("AIGreetingSubtitle")).'</p>';
		$out .= '<div class="chat-welcome-actions">';
		foreach ($quickcards as $card) {
			$out .= '<button type="button" class="ai-quick-card" data-prompt="'.dol_escape_htmltag($langs->transnoentitiesnoconv("AIQuick".$card['key']."Prompt")).'">';
			$out .= '<span class="ai-quick-icon">'.img_picto('', $card['icon']).'</span>';
			$out .= '<span class="ai-quick-text">';
			$out .= '<span class="ai-quick-title">'.dol_escape_htmltag($langs->trans("AIQuick".$card['key']."Title")).'</span>';
			$out .= '<span class="ai-quick-desc">'.dol_escape_htmltag($langs->trans("AIQuick".$card['key']."Desc")).'</span>';
			$out .= '</span>';
			$out .= '</button>';
		}
		$out .= '</div>';
		$out .= '</div>';
	}
	$out .= '</div>';

	// Controls: a single rounded "pill" holding the attach/mic buttons, the
	// textarea and the send button.
	$out .= '<div class="chat-controls">';
	// Attached-file chip (icon + name + remove cross), shown above the input pill
	// once a document has been attached with the paperclip. The document content
	// itself NEVER appears in the input nor in the conversation.
	$out .= '<div id="file-chip-area" class="file-chip-area ai-hidden"></div>';
	$out .= '<div class="chat-input-pill">';
	// Upload Wrapper (always visible: documents can be attached in any mode)
	$out .= '<div id="upload-wrapper" class="upload-wrapper">';
	$out .= '<input type="file" id="file-upload" multiple accept=".pdf,.txt,.xml,.png,.jpg,.jpeg,.heic,.heif,.doc,.docx,.xls,.xlsx,.odt,.ods" style="display: none;">';
	$out .= '<button type="button" id="upload-btn" class="round-btn" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("AttachFile")).'">'.img_picto('', 'fa-paperclip').'</button>';
	$out .= '</div>';
	// Microphone Wrapper (Visible only in Voice modes)
	$out .= '<div id="mic-wrapper" class="mic-wrapper ai-hidden">';
	$out .= '<button type="button" id="mic-btn" class="round-btn mic-btn" title="'.dol_escape_htmltag($langs->trans("ToggleMicrophone")).'">'.img_picto('', 'fa-microphone').'</button>';
	$out .= '</div>';
	// Text Input
	$out .= '<textarea id="user-input" class="ia-input" rows="1"';
	if (empty($conf->dol_optimize_smallscreen)) {
		$out .= ' placeholder="'.dol_escape_htmltag($langs->trans("TypeYourQuestion")).'"';
	}
	$out .= ' autocomplete="off" spellcheck="false"></textarea>';
	// Send Button
	$out .= '<button type="button" id="send-btn" class="chat-send-btn" title="'.dol_escape_htmltag($langs->trans("SendPrompt")).'">'.img_picto('', 'fa-paper-plane').'</button>';
	$out .= '</div>';
	$out .= '</div>';

	$out .= '<div id="status-bar"></div>';

	$out .= '</div>';

	return $out;
}

/**
 * Check the anti-CSRF token of a request sent to one of the AI Assistant endpoints.
 *
 * The check cannot be delegated to main.inc.php for those endpoints:
 *  - it only runs when MAIN_SECURITY_CSRF_WITH_TOKEN is enabled (it is optional),
 *  - it reads the token from $_GET/$_POST only,
 *  - and when the token is present but invalid it merely clears $_POST, which does not
 *    protect an endpoint reading its payload from the raw php://input body.
 *
 * The token is read from the X-CSRF-Token header, then from the 'token' parameter (the chat
 * frontend appends it to the endpoint URL, see getAiChatAssistantConfig() and epUrl() in
 * ai/js/ai_assistant.js). It is compared to both session tokens because the endpoints define
 * NOTOKENRENEWAL and therefore never rotate them: 'newtoken' is the value handed to the
 * frontend by newToken(), 'token' is the value it has been promoted to by a page that does
 * rotate. Both are legitimate for a call issued from a page of the current session.
 * (Port of #39393 to develop.)
 *
 * @param	string	$context	Endpoint name, used for logging only
 * @return	void				Emits a 403 JSON response and exits when the token is invalid
 */
function aiCheckCsrfToken($context = '')
{
	$token = '';
	if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
		$token = (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
	} else {
		$token = GETPOST('token', 'alpha');
	}

	$sessiontokens = array();
	if (!empty($_SESSION['token'])) {
		$sessiontokens[] = (string) $_SESSION['token'];
	}
	if (!empty($_SESSION['newtoken'])) {
		$sessiontokens[] = (string) $_SESSION['newtoken'];
	}

	$valid = false;
	foreach ($sessiontokens as $sessiontoken) {
		if (!empty($token) && hash_equals($sessiontoken, $token)) {
			$valid = true;
			break;
		}
	}

	if (!$valid) {
		dol_syslog(
			'[AI] Request to '.($context !== '' ? $context : $_SERVER['PHP_SELF'])
			.' refused by CSRF protection (invalid or missing token).',
			LOG_WARNING
		);

		http_response_code(403);
		header('Content-Type: application/json');
		echo json_encode(array('error' => 'Invalid CSRF token'));
		exit;
	}
}

/**
 * Remove extrafields flagged as personal data from an API-shaped payload.
 *
 * Dolibarr lets an administrator mark an extrafield as personal data
 * (GDPR). Such values must not travel to an AI provider, but the
 * REST objects the bridge returns carry every extrafield in array_options,
 * and the assistant tools that read array_options directly do the same.
 * This walks an already-serialized payload (single object or list) and drops
 * those keys, leaving everything else untouched.
 *
 * The per-element list is cached for the life of the process: a change to the
 * personal_data flag is honored from the next request on.
 *
 * @param DoliDB              $db          Database handler.
 * @param array<mixed>|mixed  $payload     Serialized API output (object or list of objects).
 * @param string              $elementtype Element type as used by ExtraFields (e.g. 'facture').
 * @return array<mixed>|mixed Payload without personal-data extrafields.
 */
function aiStripPersonalExtrafields($db, $payload, $elementtype)
{
	if (!is_array($payload) || $elementtype === '') {
		return $payload;
	}

	static $cache = array();
	if (!isset($cache[$elementtype])) {
		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($db);
		$extrafields->fetch_name_optionals_label($elementtype);
		$attrs = $extrafields->attributes[$elementtype] ?? array();
		$personal = array();
		foreach (($attrs['personal_data'] ?? array()) as $code => $flag) {
			if (!empty($flag)) {
				$personal[] = 'options_'.$code;
			}
		}
		$cache[$elementtype] = $personal;
	}
	if (empty($cache[$elementtype])) {
		return $payload;
	}

	foreach ($payload as $key => $value) {
		if ($key === 'array_options' && is_array($value)) {
			foreach ($cache[$elementtype] as $personalKey) {
				unset($payload[$key][$personalKey]);
			}
		} elseif (is_array($value)) {
			// List responses: each row carries its own array_options.
			$payload[$key] = aiStripPersonalExtrafields($db, $value, $elementtype);
		}
	}

	return $payload;
}

/**
 * Words of a text as the third party matcher compares them: runs of letters or
 * digits, lower-cased and without accents, with their byte offsets in the text.
 * "Anthropic Ireland, Limited" and "anthropic ireland limited" give the same words.
 *
 * @param	string	$text	Text to split
 * @return	array<int, array{0:string, 1:int, 2:int}>	Normalised word, start offset, end offset
 */
function aiNameWords($text)
{
	$out = array();
	$m = array();
	if (preg_match_all('/[\p{L}\p{N}]+/u', (string) $text, $m, PREG_OFFSET_CAPTURE)) {
		foreach ($m[0] as $w) {
			$norm = dol_strtolower(dol_string_unaccent((string) $w[0]));
			if (class_exists('Normalizer')) {
				// Accents dol_string_unaccent() does not know (Greek tonos, ...).
				$norm = (string) preg_replace('/\p{Mn}+/u', '', (string) Normalizer::normalize($norm, Normalizer::FORM_D));
			}
			$out[] = array($norm, (int) $w[1], (int) $w[1] + strlen((string) $w[0]));
		}
	}

	return $out;
}

/**
 * Nature of third party a text points at, from its own words: "supplier" when it
 * speaks of a supplier (or vendor), "customer" when it speaks of a customer, ''
 * when it says neither or both. Used only to narrow a list of candidates, never
 * to choose one.
 *
 * @param	string		$text	Text of the request
 * @param	Translate	$langs	Language of the user
 * @return	string				'supplier', 'customer' or ''
 */
function aiThirdpartyNatureHint($text, $langs)
{
	$langs->loadLangs(array('companies', 'bills'));
	$stems = array(
		'supplier' => array('supplier', 'vendor', $langs->transnoentitiesnoconv('Supplier')),
		'customer' => array('customer', $langs->transnoentitiesnoconv('Customer'))
	);
	$words = array_column(aiNameWords($text), 0);
	$found = array();
	foreach ($stems as $nature => $list) {
		foreach ($list as $kw) {
			$kww = aiNameWords((string) $kw);
			$kw = isset($kww[0]) ? $kww[0][0] : '';
			// Inflected forms ("προμηθευτή", "suppliers") share the start of the word.
			$stem = dol_substr($kw, 0, max(5, dol_strlen($kw) - 2));
			foreach ($words as $w) {
				if (dol_strlen($stem) >= 5 && strpos($w, $stem) === 0) {
					$found[$nature] = true;
					break 2;
				}
			}
		}
	}

	return count($found) === 1 ? (string) key($found) : '';
}

/**
 * Whether two words of a name are the same word: equal, or the same word with
 * a different ending ("solution" / "solutions")
 * one starts with the other, both have at least 4 letters, and they differ by
 * at most 2 letters.
 *
 * @param	string	$a	Normalised word (see aiNameWords())
 * @param	string	$b	Normalised word
 * @return	bool
 */
function aiNameWordsEqual($a, $b)
{
	if ($a === $b) {
		return true;
	}
	$la = dol_strlen($a);
	$lb = dol_strlen($b);
	if (min($la, $lb) < 4 || abs($la - $lb) > 2) {
		return false;
	}

	return ($la < $lb) ? (strpos($b, $a) === 0) : (strpos($a, $b) === 0);
}

/**
 * Whether the words of a phrase appear in a name one after the other, in the
 * same order each word compared with aiNameWordsEqual().
 *
 * @param	string[]	$phrase	Normalised words of the phrase
 * @param	string[]	$name	Normalised words of the name
 * @return	bool
 */
function aiNameWordsInSequence($phrase, $name)
{
	$lp = count($phrase);
	$ln = count($name);
	for ($j = 0; $j + $lp <= $ln; $j++) {
		$all = true;
		for ($k = 0; $k < $lp; $k++) {
			if (!aiNameWordsEqual($phrase[$k], $name[$j + $k])) {
				$all = false;
				break;
			}
		}
		if ($all) {
			return true;
		}
	}

	return false;
}

/**
 * Third parties named in a text, by the words of their name or trade name
 * (name_alias), in any order and not necessarily all of them: "Anthropic" names
 * "Anthropic Ireland, Limited", "Jensen LLC" and "Jensen Jon" name "Jon Jensen
 * LLC". Words may differ by their ending ("solution" / "solutions").
 *
 * Nothing is guessed here: a phrase is reported only when every word of it is
 * a word of the name, and every third party it fits is returned so the caller
 * can ask when there are several. A phrase that is a whole name keeps only the
 * third parties carrying that whole name. Keywords and legal forms (LLC, ΑΕ...)
 * never identify a third party on their own.
 *
 * @param	DoliDB			$db			Database handler
 * @param	User			$user		User the text comes from (visibility of candidates)
 * @param	string			$text		Text to scan
 * @param	string			$nature		'supplier' or 'customer' to narrow candidates when that leaves at least one, '' for all
 * @param	array<string>	$stopwords	Words never accepted alone as a partial name (translated keywords)
 * @param	int				$maxwords	Distinct words looked up, in order of appearance
 * @return	array<int, array{phrase:string, raw:string, start:int, end:int, exact:bool, first:bool, words:int, candidates:array<int, array{id:int, name:string, name_alias:string, client:int, fournisseur:int, town:string, visible:bool}>}>
 */
function aiFindThirdpartiesInText($db, $user, $text, $nature = '', $stopwords = array(), $maxwords = 80)
{
	$words = aiNameWords($text);
	if (empty($words)) {
		return array();
	}

	$stop = array();
	foreach ($stopwords as $sw) {
		foreach (aiNameWords((string) $sw) as $sww) {
			$stop[$sww[0]] = true;
		}
	}
	// Legal forms say nothing about which company is meant: never a match alone.
	$legal = array_flip(array('llc', 'ltd', 'limited', 'inc', 'corp', 'co', 'plc', 'gmbh', 'ag', 'sa', 'sas', 'sarl', 'srl', 'spa', 'bv', 'nv', 'pc', 'αε', 'ικε', 'επε', 'οε', 'εε', 'μον'));

	// Distinct words to look up: at least 3 letters, not a number. Keywords and
	// legal forms are looked up too, for a name made only of them ("Test Corp"),
	// which matches only when written in full.
	$lookup = array();
	foreach ($words as $w) {
		if (dol_strlen($w[0]) >= 3 && !ctype_digit($w[0])) {
			$lookup[$w[0]] = substr((string) $text, $w[1], $w[2] - $w[1]);
			if (count($lookup) >= $maxwords) {
				break;
			}
		}
	}
	if (empty($lookup)) {
		return array();
	}

	// A word anywhere in the name or the trade name: names are written in any
	// order ("Jon Jensen LLC", "Jensen Jon LLC") and said in any order too.
	$sql = "SELECT s.rowid, s.nom, s.name_alias, s.client, s.fournisseur, s.town";
	$sql .= " FROM ".$db->prefix()."societe as s";
	$sql .= " WHERE s.entity IN (".getEntity('societe').")";
	$sql .= " AND (1 = 0";
	foreach ($lookup as $norm => $raw) {
		// The word as written and normalised, and without its last two letters
		// for a long word, so "solutions" also finds "Solution ...".
		$forms = array($raw, $norm);
		if (dol_strlen($norm) >= 6) {
			$forms[] = dol_substr($norm, 0, dol_strlen($norm) - 2);
		}
		foreach (array_unique($forms) as $w) {
			$sql .= " OR s.nom LIKE '%".$db->escape($db->escapeforlike($w))."%' OR s.name_alias LIKE '%".$db->escape($db->escapeforlike($w))."%'";
		}
	}
	$sql .= ")";
	$sql .= " LIMIT 2000";
	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog(__FUNCTION__." ".$db->lasterror(), LOG_ERR);
		return array();
	}

	$companies = array();
	$wordlists = array();
	while ($obj = $db->fetch_object($resql)) {
		$id = (int) $obj->rowid;
		$companies[$id] = array('id' => $id, 'name' => (string) $obj->nom, 'name_alias' => (string) $obj->name_alias, 'client' => (int) $obj->client, 'fournisseur' => (int) $obj->fournisseur, 'town' => (string) $obj->town, 'visible' => false);
		foreach (array((string) $obj->nom, (string) $obj->name_alias) as $source) {
			$ws = array_column(aiNameWords($source), 0);
			if (!empty($ws)) {
				$wordlists[] = array($id, $ws);
			}
		}
	}
	$db->free($resql);
	if (empty($wordlists)) {
		return array();
	}

	// At each position, the longest run of words (at most ten) whose words all
	// belong to one name, in any order. A run without a word that identifies
	// (4 letters or more, not a keyword, not a legal form) must be a whole name.
	$matches = array();
	$count = count($words);
	$i = 0;
	while ($i < $count) {
		$hit = null;
		for ($len = min(10, $count - $i); $len >= 1; $len--) {
			$phrase = array_column(array_slice($words, $i, $len), 0);
			$key = implode(' ', $phrase);
			// A weak word (3 letters or less - "for", "the", "jon" - a keyword, a
			// legal form, a number) says little on its own: it may join a phrase
			// only in the order and place it has in the name, or in a whole name.
			// Otherwise "for SOLUTION" would pick the one name holding both
			// "solution" and "for" and hide every other company with "solution".
			$distinctive = false;
			$hasWeak = false;
			foreach ($phrase as $pw) {
				if (dol_strlen($pw) >= 4 && empty($stop[$pw]) && !isset($legal[$pw]) && !ctype_digit($pw)) {
					$distinctive = true;
				} else {
					$hasWeak = true;
				}
			}
			if (dol_strlen($phrase[0]) < 2 || dol_strlen($phrase[$len - 1]) < 2) {
				continue;
			}
			if ($len === 1 && dol_strlen($key) < 4) {
				continue;	// one short word alone would match half the names
			}
			$ids = array();
			$exactIds = array();
			$firstIds = array();
			foreach ($wordlists as $wl) {
				list($id, $ws) = $wl;
				if (count($ws) < $len) {
					continue;
				}
				// Each word of the phrase takes a different word of the name.
				$free = $ws;
				$strict = true;
				$same = true;
				foreach ($phrase as $pw) {
					$found = false;
					foreach ($free as $fk => $nw) {
						if (aiNameWordsEqual($pw, $nw)) {
							$strict = $strict && ($pw === $nw);
							unset($free[$fk]);
							$found = true;
							break;
						}
					}
					if (!$found) {
						$same = false;
						break;
					}
				}
				if (!$same) {
					continue;
				}
				$whole = ($strict && empty($free));
				if ($hasWeak && $len > 1 && !$whole && !aiNameWordsInSequence($phrase, $ws)) {
					continue;
				}
				$ids[$id] = true;
				if ($whole) {
					$exactIds[$id] = true;	// the whole name, as written (any order)
				}
				if (aiNameWordsEqual($phrase[0], $ws[0])) {
					$firstIds[$id] = true;	// starts like the name
				}
			}
			if (!$distinctive) {
				$ids = $exactIds;	// only keywords or legal forms: the whole name, or nothing
			}
			if (empty($ids)) {
				continue;
			}
			$hit = array($len, !empty($exactIds) ? array_keys($exactIds) : array_keys($ids), $key, !empty($exactIds), count($firstIds) === count($ids));
			break;
		}
		if ($hit === null) {
			$i++;
			continue;
		}
		list($len, $ids, $key, $exact, $first) = $hit;
		$start = $words[$i][1];
		$end = $words[$i + $len - 1][2];
		$cands = array();
		foreach ($ids as $id) {
			$cands[$id] = $companies[$id];
		}
		if ($nature === 'supplier' || $nature === 'customer') {
			$narrow = array_filter($cands,
				/**
				 * @param array{id:int, name:string, name_alias:string, client:int, fournisseur:int, town:string, visible:bool} $c Candidate
				 * @return bool
				 */
				function ($c) use ($nature) {
					return $nature === 'supplier' ? ($c['fournisseur'] == 1) : in_array($c['client'], array(1, 2, 3));
				}
			);
			if (!empty($narrow)) {
				$cands = $narrow;
			}
		}
		$matches[] = array('phrase' => $key, 'raw' => substr((string) $text, $start, $end - $start), 'start' => $start, 'end' => $end, 'exact' => $exact, 'first' => $first, 'words' => $len, 'candidates' => $cands);
		$i += $len;
	}

	// Visibility: a user without the right to read third parties sees none, a
	// user restricted to his own customers sees only those.
	if (!empty($matches) && $user->hasRight('societe', 'lire')) {
		$allIds = array();
		foreach ($matches as $mt) {
			$allIds = array_merge($allIds, array_keys($mt['candidates']));
		}
		$seen = array();
		if ($user->hasRight('societe', 'client', 'voir')) {
			$seen = array_flip($allIds);
		} elseif (!empty($allIds)) {
			$sql = "SELECT sc.fk_soc FROM ".$db->prefix()."societe_commerciaux as sc";
			$sql .= " WHERE sc.fk_user = ".((int) $user->id)." AND sc.fk_soc IN (".$db->sanitize(implode(',', array_map('intval', $allIds))).")";
			$resql = $db->query($sql);
			while ($resql && ($obj = $db->fetch_object($resql))) {
				$seen[(int) $obj->fk_soc] = true;
			}
		}
		foreach ($matches as $mi => $mt) {
			foreach ($mt['candidates'] as $id => $c) {
				$matches[$mi]['candidates'][$id]['visible'] = isset($seen[$id]);
			}
		}
	}

	return $matches;
}

/**
 * Every form of the third party names found in a text or carried by a tool
 * result, to mask with PrivacyGuard::maskNames(): the words as written, the
 * names and trade names of the matching third parties, and the values of the
 * name keys of a result ("name", "nom", "name_alias", "socname", ...).
 *
 * @param	DoliDB			$db			Database handler
 * @param	User			$user		User
 * @param	string			$text		Text that will be sent
 * @param	array<string>	$stopwords	Translated keywords never accepted alone
 * @param	mixed			$data		Decoded tool result the text was built from, or null
 * @return	array<string>				Names to mask
 */
function aiThirdpartyNamesToMask($db, $user, $text, $stopwords = array(), $data = null)
{
	$names = array();
	foreach (aiFindThirdpartiesInText($db, $user, $text, '', $stopwords) as $mt) {
		$names[] = $mt['raw'];
		foreach ($mt['candidates'] as $c) {
			$names[] = $c['name'];
			$names[] = $c['name_alias'];
		}
	}
	if (is_array($data)) {
		$keys = array('name' => true, 'nom' => true, 'name_alias' => true, 'socname' => true, 'thirdparty_name' => true, 'company' => true);
		array_walk_recursive($data,
			/**
			 * @param mixed      $value Value
			 * @param int|string $key   Key
			 * @return void
			 */
			function ($value, $key) use (&$names, $keys) {
				if (is_string($key) && isset($keys[$key]) && is_string($value)) {
					$names[] = $value;
				}
			}
		);
	}

	return array_values(array_unique(array_filter($names,
		/**
		 * @param string $n Name
		 * @return bool
		 */
		function ($n) {
			return $n !== '';
		}
	)));
}
