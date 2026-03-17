<?php
/* Copyright (C) 2022		Alice Adminson			<aadminson@example.com>
 * Copyright (C) 2024-2025  Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026		Nick Fragoulis
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
		'textrephrase' => array('label' => $langs->trans('TextRephraser'), 'picto' => '', 'status'=>'dolibarr', 'function' => 'TEXT', 'placeholder' => Ai::AI_DEFAULT_PROMPT_FOR_TEXT_REPHRASER),

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
			'textgeneration'  => array('default' => 'gpt-5.2'),             // Flagship model released late 2025, updated Feb 2026
			'imagegeneration' => array('default' => 'gpt-image-1.5'),       // Replaced DALL-E 3; 4x faster and native to GPT-5
			'audiogeneration' => array('default' => 'gpt-audio-1.5'),       // New Feb 23, 2026 release for high-fidelity audio out
			'videogeneration' => array('default' => 'sora-2'),              // OpenAI's standard API video model
			'transcription'   => array('default' => 'whisper-large-v3-turbo'), // The current speed/accuracy benchmark for ASR
			'translation'     => array('default' => 'whisper-large-v3-turbo'), // Still the best for multi-language audio translation
			'docparsing'      => array('default' => 'gpt-5.2'),             // Uses the new Responses API / Vision capabilities
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
			'textgeneration' => array('default' => 'claude-opus-4-6'),    // Released Feb 2026; features a 1M context window
			'imagegeneration' => array('default' => 'na'),              // Anthropic remains focused on text/code logic
			'audiogeneration' => array('default' => 'na'),
			'videogeneration' => array('default' => 'na'),
			'transcription' => array('default' => 'na'),
			'translation' => array('default' => 'na'),
			'docparsing' => array('default' => 'claude-opus-4-6'),      // Leading model for "Computer Use" and PDF analysis
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
 * Tests the connection to an AI service using its API key and URL.
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
		$model = getDolGlobalString('AI_API_' . strtoupper($service) . '_MODEL');
	}

	$data = [];
	$headers = ["Content-Type: application/json"];

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

	// Execute cURL
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	// Optional: Add SSL verification if behind a proxy with self-signed certs
	// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	$result = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$err = curl_error($ch);
	curl_close($ch);

	if ($err) {
		return ['success' => false, 'message' => "Curl Error: $err"];
	}

	if ($httpCode >= 200 && $httpCode < 300) {
		return ['success' => true, 'message' => "OK (HTTP $httpCode)."];
	} else {
		$json = json_decode($result, true);
		// Attempt to find the error message in various common structures
		$msg = $json['error']['message'] ?? $json['message'] ?? substr($result, 0, 150);
		return ['success' => false, 'message' => "HTTP $httpCode. Error: $msg"];
	}
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
 * @return  void
 */
function ai_log_request($db, $user, $query, array $response, $provider, float $time, float $confidence, $status, $error = '', $rawReq = '', $rawRes = ''): void
{
	global $conf;

	if (!getDolGlobalInt('AI_LOG_REQUESTS')) {
		return;
	}

	$tool = isset($response['tool']) ? (string) $response['tool'] : '';

	if (dol_strlen($rawReq) > 60000) {
		$rawReq = dol_substr($rawReq, 0, 60000) . '... [Truncated]';
	}

	$rawResStr = (string) $rawRes;
	if (dol_strlen($rawResStr) > 60000) {
		$rawResStr = dol_substr($rawResStr, 0, 60000) . '... [Truncated]';
	}

	$sql = "INSERT INTO " . MAIN_DB_PREFIX . "ai_request_log (";
	$sql .= "entity, date_request, fk_user, query_text, tool_name, provider, ";
	$sql .= "execution_time, confidence, status, error_msg, raw_request_payload, raw_response_payload";
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
	$sql .= ")";

	$resql = $db->query($sql);

	if (!$resql) {
		dol_print_error($db);
	}
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
		'professional' => 'RephraseStyleProfessional',
		'humouristic' => 'RephraseStyleHumouristic'
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

	if (getDolGlobalString("MAIN_FEATURES_LEVEL") >= 2) {
		$head[$h][0] = dol_buildpath("/ai/admin/server_mcp.php", 1);
		$head[$h][1] = $langs->trans("MCPServer");
		$head[$h][2] = 'servermcp';
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
