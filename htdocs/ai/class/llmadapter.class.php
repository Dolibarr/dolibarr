<?php
/* Copyright (C) 2026		Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026		Nick Fragoulis
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
 * \file    htdocs/ai/class/llmadapter.class.php
 * \ingroup ai
 * \brief   Universal adapter for multiple LLM providers
 */

class UniversalLLMAdapter
{
	/** @var string Stores the raw request for debugging */
	public $lastRequest = "";

	/** @var string Stores the raw response for debugging */
	public $lastResponse = "";

	/** @var string The type of LLM (e.g., 'openai', 'ollama') */
	private $type;

	/** @var string The API Key */
	private $key;

	/** @var string The Base URL for the API */
	private $baseUrl;

	/** @var string The model name to use */
	private $model;

	/** @var int Timeout in seconds */
	private $timeout;

	/**
	 * Constructor
	 *
	 * @param string $type    The LLM type
	 * @param string $key     API Key
	 * @param string $baseUrl API Base URL
	 * @param string $model   Model name
	 * @param int    $timeout Timeout in seconds
	 */
	public function __construct(string $type, string $key, string $baseUrl, string $model, int $timeout)
	{
		$this->type = strtolower($type);
		$this->key = $key;
		$this->baseUrl = rtrim($baseUrl, '/');
		$this->model = $model;
		$this->timeout = $timeout;
	}

	/**
	 * Generate a response using the configured LLM provider
	 *
	 * @param string $system   The system prompt/instruction
	 * @param string $userMsg  The specific user query
	 * @param string $mode     'json' for strict JSON (MCP), 'text' for legacy (default)
	 * @return string|null     The text response from the AI or null on failure
	 */
	public function generate(string $system, string $userMsg, string $mode = 'text'): ?string
	{
		switch ($this->type) {
			case 'anthropic':
				return $this->callAnthropic($system, $userMsg, $mode);
			case 'google':
				return $this->callGoogle($system, $userMsg, $mode);
			default:
				return $this->callOpenAI($system, $userMsg, $mode);
		}
	}

	/**
	 * Call OpenAI-compatible API
	 *
	 * @param string $sys System prompt
	 * @param string $msg User message
	 * @param string $mode 'json' or 'text'
	 * @return string|null Response content or null on failure
	 */
	private function callOpenAI(string $sys, string $msg, string $mode = 'text'): ?string
	{
		$url = $this->baseUrl;
		if (strpos($url, '/chat/completions') === false && strpos($url, '/generate') === false) {
			$url .= '/chat/completions';
		}

		$data = array(
			"model" => $this->model,
			"messages" => array(
				array("role" => "system", "content" => $sys),
				array("role" => "user", "content" => $msg)
			),
			"temperature" => 0.1
		);

		// Only force JSON mode if explicitly requested
		// This allows Email/Webpage generation to return raw HTML
		if ($mode === 'json') {
			// Apply to specific providers known to support this parameter safely
			if (strpos($url, 'openai') !== false || strpos($url, 'deepseek') !== false || strpos($url, 'perplexity') !== false || strpos($url, 'mistral') !== false || strpos($url, 'zai') !== false) {
				$data["response_format"] = array("type" => "json_object");
			}
		}

		$this->lastRequest = json_encode($data, JSON_PRETTY_PRINT);

		return $this->curl($url, $data, array("Content-Type: application/json", "Authorization: Bearer " . $this->key));
	}

	/**
	 * Call Anthropic API (Claude)
	 *
	 * @param string $sys System prompt
	 * @param string $msg User message
	 * @param string $mode Response mode (default: text)
	 *
	 * @return string|null Response content or null on failure
	 */
	private function callAnthropic(string $sys, string $msg, string $mode = 'text')
	{

		$url = $this->baseUrl . (strpos($this->baseUrl, '/messages') === false ? '/messages' : '');

		$data = array(
			"model" => $this->model,
			"system" => $sys,
			"messages" => array(array("role" => "user", "content" => $msg)),
			"max_tokens" => 1024
		);

		$this->lastRequest = json_encode($data, JSON_PRETTY_PRINT);

		return $this->curl($url, $data, array("content-type: application/json", "x-api-key: " . $this->key, "anthropic-version: 2023-06-01"), true);
	}

	/**
	 * Call Google Gemini API
	 *
	 * @param string $sys System prompt
	 * @param string $msg User message
	 * @param string $mode Response mode (default: text)
	 *
	 * @return string|null Response content or null on failure
	 */
	private function callGoogle(string $sys, string $msg, string $mode = 'text')
	{
		$url = $this->baseUrl;

		// Strict type check for string position
		if (strpos($url, ':generateContent') === false) {
			if (strpos($url, '/models/') === false) {
				$url .= "/models/" . $this->model;
			}
			$url .= ":generateContent";
		}

		$url .= "?key=" . $this->key;

		$data = array(
			"contents" => array(
				array("parts" => array(array("text" => $sys . "\nUser: " . $msg)))
			),
			"generationConfig" => array("temperature" => 0.1)
		);

		$this->lastRequest = json_encode($data, JSON_PRETTY_PRINT);

		return $this->curl($url, $data, array("Content-Type: application/json"), false, true);
	}

	/**
	 * Execute HTTP Request via cURL
	 *
	 * @param string 				$url       	Target API URL
	 * @param array<string, mixed> 	$data      	Request payload (keys are strings, values vary)
	 * @param array<int, string>   	$headers   	List of HTTP headers (indexed array of strings)
	 * @param bool   				$isClaude  	Flag to handle Anthropic response format
	 * @param bool   				$isGemini  	Flag to handle Gemini response format
	 * @return string|null      				Returns the extracted text, an error message, or null
	 */
	private function curl(string $url, array $data, array $headers, bool $isClaude = false, bool $isGemini = false): ?string
	{
		include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

		// By default, we accept only external endpoints ($dolibarr_ai_allow_local_endpoints is not set).
		// To allow local endpoints, we must set $dolibarr_ai_allow_local_endpoints to 1 or 2 in conf.php.
		global $dolibarr_ai_allow_local_endpoints;
		$localurl = $dolibarr_ai_allow_local_endpoints ?? 0;

		// Pass $this->timeout as the response timeout so the LLM-specific value configured
		// at construction time is honored (getURLContent's $timeoutresponse is the 10th arg;
		// preceding args $ssl_verifypeer=-1 and $timeoutconnect=0 keep their defaults).
		$result = getURLContent($url, 'POST', json_encode($data), 1, $headers, array('http', 'https'), $localurl, -1, 0, $this->timeout);

		$body         = (string) ($result['content'] ?? '');
		$httpCode     = (int) ($result['http_code'] ?? 0);
		$effectiveUrl = (string) ($result['url'] ?? $url);
		// Store an enriched payload so the admin Log Viewer ("VIEW LOGS" in the AI Server
		// MCP setup page) shows something actionable when something goes wrong, not just
		// a bare "Invalid JSON response from API." with an empty body.
		$this->lastResponse = "HTTP " . $httpCode . " from " . $effectiveUrl . "\n--- body (" . strlen($body) . " bytes) ---\n"	. $body;

		if (!empty($result['curl_error_no'])) {
			return "Error: cURL #" . $result['curl_error_no'] . " " . $result['curl_error_msg'] . " (url=" . $effectiveUrl . ")";
		}

		$json = json_decode($body, true);

		if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
			// Common real-world causes: HTTP 4xx/5xx with empty body, HTML error page
			// from a proxy, gateway timeout, etc. Surface the HTTP code and a short
			// body snippet so the admin can diagnose without re-running with curl.
			$snippet = substr($body, 0, 500);
			return "Error: Invalid JSON response from API (HTTP " . $httpCode . ", " . strlen($body) . " bytes). Body snippet: " . ($snippet !== '' ? $snippet : '<empty>');
		}

		if (isset($json['error'])) {
			$msg = $json['error']['message'] ?? json_encode($json['error']);
			return "Error: API " . $msg;
		}

		// Extraction Logic
		if ($isClaude) {
			return $json['content'][0]['text'] ?? null;
		}
		if ($isGemini) {
			return $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
		}

		// Default (OpenAI compatible)
		return $json['choices'][0]['message']['content'] ?? null;
	}
}
