<?php
/* Copyright (C) 2024  Laurent Destailleur     <eldy@users.sourceforge.net>
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
 * or see https://www.gnu.org/
 */

/**
 * \file    htdocs/ai/class/ai.class.php
 * \ingroup ai
 * \brief   Class files with common methods for Ai
 */

require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT."/ai/lib/ai.lib.php";


/**
 * Class for AI
 */
class Ai
{
	/**
	 * @var DoliDB $db Database object
	 */
	protected $db;

	/**
	 * @var string $apiService
	 */
	private $apiService;

	/**
	 * @var string $apiKey
	 */
	private $apiKey;

	/**
	 * @var string $apiEndpoint
	 */
	private $apiEndpoint;

	/**
	 * Avoid writing repeated quota alerts during the same request.
	 *
	 * @var bool
	 */
	private static $quotaAlertRaised = false;

	const AI_DEFAULT_PROMPT_FOR_EMAIL = 'You are an email editor. Return all HTML content inside a section tag. Do not add explanation.';
	const AI_DEFAULT_PROMPT_FOR_WEBPAGE = 'You are a website editor. Return all HTML content inside a section tag. Do not add explanation.';
	const AI_DEFAULT_PROMPT_FOR_TEXT_TRANSLATION = 'You are a translator, answer with one and only one translation with no comment and explanation.';
	const AI_DEFAULT_PROMPT_FOR_TEXT_SUMMARIZE = 'You are a writer, make the answer in the same language than the original text to summarize.';
	const AI_DEFAULT_PROMPT_FOR_TEXT_REPHRASER = 'You are a writer, give only one answer with no comment and explanation and give the answer in the same language than the original text to rephrase.';
	const AI_DEFAULT_PROMPT_FOR_EXTRAFIELD_FILLER = 'Give only one answer with no comment and explanation, I want the text to be ready to copy and paste.';
	const AI_DEFAULT_PROMPT_FOR_DOC_PARSING = 'You are an assistant to anayze documents. Return your answer with a JSON string and only a JSON string, do not add any other comment.';

	/**
	 * Detect AI quota / billing hard-limit errors to trigger a persistent warning in Dolibarr.
	 *
	 * @param int $httpCode
	 * @param mixed $decodedResponse
	 * @param string $errorMessage
	 * @return bool
	 */
	private function isQuotaExhaustedError($httpCode, $decodedResponse, $errorMessage)
	{
		$httpCode = (int) $httpCode;
		$errorMessage = strtolower(trim((string) $errorMessage));

		$payload = '';
		if (is_array($decodedResponse)) {
			$tmp = json_encode($decodedResponse);
			if ($tmp !== false) $payload = strtolower($tmp);
		} elseif (is_string($decodedResponse)) {
			$payload = strtolower($decodedResponse);
		}

		$hay = trim($errorMessage.' '.$payload);
		if ($hay === '') return false;

		// OpenAI: error.code/type = "insufficient_quota" or similar.
		if (strpos($hay, 'insufficient_quota') !== false) return true;
		if (strpos($hay, 'billing_hard_limit_reached') !== false) return true;
		if (strpos($hay, 'hard_limit') !== false && strpos($hay, 'billing') !== false) return true;

		// Generic quota wording.
		if (strpos($hay, 'quota') !== false && (strpos($hay, 'exceed') !== false || strpos($hay, 'exceeded') !== false)) return true;
		if (strpos($hay, 'quota') !== false && strpos($hay, 'billing') !== false) return true;

		// HTTP hints (429 can also be rate-limit; we only treat it as quota when "quota" is present).
		if (in_array($httpCode, array(402, 403, 429), true) && strpos($hay, 'quota') !== false) return true;

		return false;
	}

	/**
	 * Persist a quota exhausted warning into global constants so admins see it in the UI.
	 *
	 * @param int $httpCode
	 * @param string $function
	 * @param string $errorMessage
	 * @param mixed $decodedResponse
	 * @return void
	 */
	private function recordQuotaExhaustedWarning($httpCode, $function, $errorMessage, $decodedResponse = null)
	{
		global $conf;

		if (self::$quotaAlertRaised) return;
		self::$quotaAlertRaised = true;

		if (empty($conf) || empty($conf->entity) || !is_object($this->db)) return;

		$httpCode = (int) $httpCode;
		$function = trim((string) $function);
		$errorMessage = trim((string) $errorMessage);

		if ($errorMessage === '') {
			$errorMessage = 'AI quota exhausted';
		}

		// Avoid writing the same alert too often.
		$now = dol_now('gmt');
		$prevAt = (int) getDolGlobalString('AI_LAST_QUOTA_EXHAUSTED_AT', '0');
		$prevMsg = (string) getDolGlobalString('AI_LAST_QUOTA_EXHAUSTED_MSG', '');

		$shortMsg = dol_trunc($errorMessage, 255);

		// Suspend further AI calls for some time (to avoid burning retry attempts in background jobs).
		// Can be tuned with AI_QUOTA_SUSPEND_SECONDS (0 disables suspension).
		$suspendSeconds = (int) getDolGlobalInt('AI_QUOTA_SUSPEND_SECONDS', 3600);
		if ($suspendSeconds < 0) $suspendSeconds = 0;
		if ($suspendSeconds > 0) {
			$suspendUntil = $now + $suspendSeconds;
			$prevSuspendUntil = (int) getDolGlobalString('AI_QUOTA_SUSPEND_UNTIL', '0');
			if ($prevSuspendUntil < $suspendUntil) {
				dolibarr_set_const($this->db, 'AI_QUOTA_SUSPEND_UNTIL', (string) $suspendUntil, 'chaine', 0, '', $conf->entity);
			}
		}

		if ($prevAt > 0 && ($now - $prevAt) < 600 && $prevMsg === $shortMsg) {
			return;
		}

		dolibarr_set_const($this->db, 'AI_LAST_QUOTA_EXHAUSTED_AT', (string) $now, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($this->db, 'AI_LAST_QUOTA_EXHAUSTED_MSG', $shortMsg, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($this->db, 'AI_LAST_QUOTA_EXHAUSTED_SERVICE', (string) $this->apiService, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($this->db, 'AI_LAST_QUOTA_EXHAUSTED_HTTP', (string) $httpCode, 'chaine', 0, '', $conf->entity);
		if ($function !== '') {
			dolibarr_set_const($this->db, 'AI_LAST_QUOTA_EXHAUSTED_FUNCTION', $function, 'chaine', 0, '', $conf->entity);
		}
		if (is_array($decodedResponse)) {
			$tmp = json_encode($decodedResponse);
			if ($tmp !== false && $tmp !== '') {
				dolibarr_set_const($this->db, 'AI_LAST_QUOTA_EXHAUSTED_RAW', dol_trunc($tmp, 2048), 'chaine', 0, '', $conf->entity);
			}
		}
	}


	/**
	 * Constructor
	 *
	 * @param	DoliDB	$db		 Database handler
	 *
	 */
	public function __construct($db)
	{
		$this->db = $db;

		// Get API key according to enabled AI
		$this->apiService = getDolGlobalString('AI_API_SERVICE', 'chatgpt');
		$this->apiKey = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_KEY');
	}

	/**
	 * get API Service
	 *
	 * @return	string		API service
	 */
	public function getApiService()
	{
		return $this->apiService;
	}

	/**
	 * Generate response of instructions
	 *
	 * @param   string|array<mixed,mixed>	$instructions   String instruction to generate content (or file path) or array of payload or ID of file with function threads
	 * @param   string  					$model          Model name ('gpt-4.1-turbo', 'gpt-4.1', 'dall-e-3', ...)
	 * @param   string  					$function     	Code of the feature we want to use ('textgeneration', 'transcription', 'audiogeneration', 'imagegeneration', 'translation', 'docparsing')
	 * @param	string						$format			Format for output ('', 'html', ...)
	 * @param	array<string,string>		$moreheaders	More headers
	 * @param	string						$moreendpoint	Add a part to endpoint url
	 * @return  string|array{error:bool,message:string,code?:int,curl_error_no?:int,format?:string,service?:string,function?:string}	$response		Text or array if error
	 */
	public function generateContent($instructions, $model = 'auto', $function = 'textgeneration', $format = '', $moreheaders = array(), $moreendpoint = '')
	{
		global $dolibarr_main_data_root;

		$arrayofai = getListOfAIServices();

		// TODO Can store the need for a key into array returned by getListOfAIServices()
		if (empty($this->apiKey) && in_array($this->apiService, array('chatgpt', 'groq', 'mistral'))) {
			return array('error' => true, 'message' => 'API key is not defined for the AI enabled service ('.$this->apiService.')');
		}

		// Fast-fail when AI calls are suspended due to a recent "insufficient quota" error.
		$suspendUntil = (int) getDolGlobalString('AI_QUOTA_SUSPEND_UNTIL', '0');
		if ($suspendUntil > 0 && $suspendUntil > dol_now('gmt')) {
			return array(
				'error' => true,
				'message' => 'AI quota suspended until '.$suspendUntil,
				'code' => 429,
				'format' => $format,
				'service' => $this->apiService,
				'function' => $function
			);
		}

		// $this->apiEndpoint is already set here only if it was previously forced.

		if (empty($this->apiEndpoint) && $this->apiService == 'custom' && !getDolGlobalString('AI_API_CUSTOM_URL')) {
			return array('error' => true, 'message' => 'API URL is not defined for the AI enabled service ('.$this->apiService.')');
		}

		// In most cases, it is empty and we must get it from $function and $this->apiService
		if (empty($this->apiEndpoint)) {
			// Return the endpoint from $this->apiService.
			if ($function == 'imagegeneration') {
				$this->apiEndpoint = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_URL', $arrayofai[$this->apiService]['url']);
				$this->apiEndpoint .= (preg_match('/\/$/', $this->apiEndpoint) ? '' : '/').'images/generations';
			} elseif ($function == 'audiogeneration') {
				$this->apiEndpoint = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_URL', $arrayofai[$this->apiService]['url']);
				$this->apiEndpoint .= (preg_match('/\/$/', $this->apiEndpoint) ? '' : '/').'audio/speech';
			} elseif ($function == 'transcription') {
				$this->apiEndpoint = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_URL', $arrayofai[$this->apiService]['url']);
				$this->apiEndpoint .= (preg_match('/\/$/', $this->apiEndpoint) ? '' : '/').'transcriptions';
			} elseif ($function == 'file') {
				$this->apiEndpoint = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_URL', $arrayofai[$this->apiService]['url']);
				$this->apiEndpoint .= (preg_match('/\/$/', $this->apiEndpoint) ? '' : '/').'files';
			} elseif ($function == 'assistant') {
				$this->apiEndpoint = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_URL', $arrayofai[$this->apiService]['url']);
				$this->apiEndpoint .= (preg_match('/\/$/', $this->apiEndpoint) ? '' : '/').'assistans';
			} elseif ($function == 'thread') {
				$this->apiEndpoint = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_URL', $arrayofai[$this->apiService]['url']);
				$this->apiEndpoint .= (preg_match('/\/$/', $this->apiEndpoint) ? '' : '/').'threads';
			} else {	// if $function == 'docparsing', ...
				$this->apiEndpoint = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_URL', $arrayofai[$this->apiService]['url']);
				$this->apiEndpoint .= (preg_match('/\/$/', $this->apiEndpoint) ? '' : '/').'chat/completions';
			}
		}
		if ($moreendpoint) {
			$this->apiEndpoint .= '/'.$moreendpoint;
		}

		// $model may be undefined or 'auto'.
		// If this is the case, we must get it from $function and $this->apiService
		if (empty($model) || $model == 'auto') {
			// Return the model from $this->apiService.
			if (in_array($function, array('file', 'assistant', 'thread'))) {
				$model = '';
			} elseif ($function == 'imagegeneration') {
				$model = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_MODEL_IMAGE', $arrayofai[$this->apiService][$function]);
			} elseif ($function == 'audiogeneration') {
				$model = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_MODEL_AUDIO', $arrayofai[$this->apiService][$function]);
			} elseif ($function == 'transcription') {
				$model = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_MODEL_TRANSCRIPT', $arrayofai[$this->apiService][$function]);
			} elseif ($function == 'translation') {
				$model = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_MODEL_TRANSLATE', $arrayofai[$this->apiService][$function]);
			} elseif ($function == 'docparsing') {
				$model = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_MODEL_DOCPARSING', $arrayofai[$this->apiService][$function]);
			} else {
				// else 'textgenerationemail', 'textgenerationwebpage', 'textgeneration', 'texttranslation', 'textsummarize'
				$model = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_MODEL_TEXT', $arrayofai[$this->apiService]['textgeneration']);
			}
		}

		// OpenAI GPT-5 models use the Responses API (Chat Completions may return "invalid model ID").
		$useResponsesApi = false;
		if (
			!is_array($instructions)
			&& $this->apiService === 'chatgpt'
			&& is_string($model)
			&& preg_match('/^gpt-5/i', $model)
		) {
			// Only for text-like functions.
			if (!in_array($function, array('imagegeneration', 'audiogeneration', 'transcription', 'translation', 'file', 'assistant', 'thread'), true)) {
				$useResponsesApi = true;
				if (!empty($this->apiEndpoint) && strpos($this->apiEndpoint, 'chat/completions') !== false) {
					$tmpEndpoint = preg_replace('~/chat/completions~', '/responses', $this->apiEndpoint, 1);
					if (!empty($tmpEndpoint)) {
						$this->apiEndpoint = $tmpEndpoint;
					}
				}
			}
		}

		dol_syslog("Call API for apiKey=".substr($this->apiKey, 0, 5).'***********, apiEndpoint='.$this->apiEndpoint.", model=".$model);

		$response = null;

		try {
			if (empty($this->apiEndpoint)) {
				throw new Exception('The AI service '.$this->apiService.' is not yet supported for the type of request '.$function);
			}

			$configurationsJson = getDolGlobalString('AI_CONFIGURATIONS_PROMPT');
			$configurations = json_decode($configurationsJson, true);

			$prePrompt = '';
			$postPrompt = '';

			if (isset($configurations[$function])) {
				if (isset($configurations[$function]['prePrompt'])) {
					$prePrompt = $configurations[$function]['prePrompt'];
				}

				if (isset($configurations[$function]['postPrompt'])) {
					$postPrompt = $configurations[$function]['postPrompt'];
				}
			}

			// Get the default value of prePrompt if not defined
			if (empty($prePrompt) && $function == 'textgenerationemail') {
				$prePrompt = self::AI_DEFAULT_PROMPT_FOR_EMAIL;
			}
			if (empty($prePrompt) && $function == 'textgenerationwebpage') {
				$prePrompt = self::AI_DEFAULT_PROMPT_FOR_WEBPAGE;
			}
			if (empty($prePrompt) && $function == 'textgenerationextrafield') {
				$prePrompt = self::AI_DEFAULT_PROMPT_FOR_EXTRAFIELD_FILLER;
			}
			if (empty($prePrompt) && $function == 'texttranslation') {
				$prePrompt = self::AI_DEFAULT_PROMPT_FOR_TEXT_TRANSLATION;
			}
			if (empty($prePrompt) && $function == 'textsummarize') {
				$prePrompt = self::AI_DEFAULT_PROMPT_FOR_TEXT_SUMMARIZE;
			}
			if (empty($prePrompt) && $function == 'textrephraser') {
				$prePrompt = self::AI_DEFAULT_PROMPT_FOR_TEXT_REPHRASER;
			}
			if (empty($prePrompt) && $function == 'docparsing') {
				$prePrompt = self::AI_DEFAULT_PROMPT_FOR_DOC_PARSING;
			}

			if (is_array($instructions)) {
				$arrayforpayload = $instructions;
				$fullInstructions = '';
			} else {
				$fullInstructions = $instructions.($postPrompt ? (preg_match('/[\.\!\?]$/', $instructions) ? '' : '.').' '.$postPrompt : '');

				// Set payload string
				/*{
					"messages": [
					{
						"content": "You are a helpful assistant.",
						"role": "system"
					},
					{
						"content": "Hello!",
						"role": "user"
					}
					],
					"model": "tinyllama-1.1b",
					"stream": true,
					"max_tokens": 2048,
					"stop": [
						"hello"
					],
					"frequency_penalty": 0,
					"presence_penalty": 0,
					"temperature": 0.7,
					"top_p": 0.95
				}*/

				// Add a system message (Chat Completions) / instructions (Responses API)
				$addDateTimeContext = false;
				if ($addDateTimeContext) {		// @phpstan-ignore-line
					$prePrompt = ($prePrompt ? $prePrompt.(preg_match('/[\.\!\?]$/', $prePrompt) ? '' : '.').' ' : '').'Today we are '.dol_print_date(dol_now(), 'dayhourtext');
				}

				if ($useResponsesApi) {
					$arrayforpayload = array(
						'model' => $model,
						'input' => array(
							array(
								'role' => 'user',
								'content' => array(
									array('type' => 'input_text', 'text' => $fullInstructions),
								),
							),
						),
					);
					if ($prePrompt) {
						$arrayforpayload['instructions'] = $prePrompt;
					}
				} else {
					$arrayforpayload = array(
						'messages' => array(array('role' => 'user', 'content' => $fullInstructions)),
						'model' => $model,
					);
					if ($prePrompt) {
						$arrayforpayload['messages'][] = array('role' => 'system', 'content' => $prePrompt);
					}
				}
			}

			/*
			$arrayforpayload['temperature'] = 0.7;
			$arrayforpayload['max_tokens'] = -1;
			$arrayforpayload['stream'] = false;
			*/

			if ($function == 'thread') {
				$payload = $instructions;
			} else {
				$payload = json_encode($arrayforpayload);
			}

			$headers = array(
				'Authorization: Bearer ' . $this->apiKey,
			);
			if ($function != 'file') {
				$headers[] = 'Content-Type: application/json';
			}
			if (!empty($moreheaders)) {
				foreach ($moreheaders as $morekey => $moreval) {
					$headers[] = $morekey.': '.$moreval;
				}
			}

			if (getDolGlobalString("AI_DEBUG")) {
				if (@is_writable($dolibarr_main_data_root)) {	// Avoid fatal error on fopen with open_basedir
					$outputfile = $dolibarr_main_data_root."/dolibarr_ai.log";
					$fp = fopen($outputfile, "w");	// overwrite

					if ($fp) {
						if ($function == 'docparsing') {
							fwrite($fp, "Call endpoint ".$this->apiEndpoint." with POST and the following file to upload:\n");
							fwrite($fp, $instructions."\n");
						} else {
							fwrite($fp, "Call endpoint ".$this->apiEndpoint." with POST and the following message:\n");
							fwrite($fp, $fullInstructions."\n");
							fwrite($fp, "Prepompt\n");
							fwrite($fp, $prePrompt."\n");
						}
						fwrite($fp, "HTTP Header\n");
						fwrite($fp, var_export($headers, true)."\n");
						fwrite($fp, "Payload\n");
						fwrite($fp, var_export($payload, true)."\n");

						fclose($fp);
						dolChmod($outputfile);
					}
				}
			}

			$localurl = 2;	// Accept both local and external endpoints
			$response = getURLContent($this->apiEndpoint, 'POST', $payload, 1, $headers, array('http', 'https'), $localurl);

			if (empty($response['http_code'])) {
				throw new Exception('API request failed. No http received');
			}
			if (!empty($response['http_code']) && $response['http_code'] != 200) {
				if (in_array($response['http_code'], array(400, 401, 403, 429)) && !empty($response['content'])) {
					$tmp = json_decode($response['content'], true);
					$tmpMsg = '';
					if (!empty($tmp['message'])) {
						$tmpMsg = (string) $tmp['message'];
					} elseif (!empty($tmp['error']['message'])) {
						$tmpMsg = (string) $tmp['error']['message'];
					}

					if ($tmpMsg !== '') {
						if ($this->isQuotaExhaustedError((int) $response['http_code'], $tmp, $tmpMsg)) {
							$this->recordQuotaExhaustedWarning((int) $response['http_code'], (string) $function, $tmpMsg, $tmp);
						}
						return array(
							'error' => true,
							'message' => $tmpMsg,
							'code' => (empty($response['http_code']) ? 0 : $response['http_code']),
							'curl_error_no' => (empty($response['curl_error_no']) ? 0 : $response['curl_error_no']),
							'format' => $format,
							'service' => $this->apiService,
							'function' => $function
						);
					}
				}
				throw new Exception('API request on AI endpoint '.$this->apiEndpoint.' failed with status code '.$response['http_code']);
			}

			if (getDolGlobalString("AI_DEBUG")) {
				if (@is_writable($dolibarr_main_data_root)) {	// Avoid fatal error on fopen with open_basedir
					$outputfile = $dolibarr_main_data_root."/dolibarr_ai.log";
					$fp = fopen($outputfile, "a");

					if ($fp) {
						fwrite($fp, "Answer\n");
						fwrite($fp, var_export((empty($response['content']) ? 'No content result' : $response['content']), true)."\n");

						fclose($fp);
						dolChmod($outputfile);
					}
				}
			}

			// Decode JSON response
			$decodedResponse = json_decode($response['content'], true);

			// Clear quota suspension after a successful request.
			if (!empty($decodedResponse) && is_array($decodedResponse)) {
				global $conf;
				if (!empty($conf) && !empty($conf->entity) && is_object($this->db)) {
					if ((int) getDolGlobalString('AI_QUOTA_SUSPEND_UNTIL', '0') > 0) {
						dolibarr_del_const($this->db, 'AI_QUOTA_SUSPEND_UNTIL', $conf->entity);
					}
				}
			}

			// Extraction content
			$generatedContent = '';
			if ($useResponsesApi) {
				if (!empty($decodedResponse['output_text']) && is_string($decodedResponse['output_text'])) {
					$generatedContent = (string) $decodedResponse['output_text'];
				} elseif (!empty($decodedResponse['output']) && is_array($decodedResponse['output'])) {
					$texts = array();
					foreach ($decodedResponse['output'] as $outItem) {
						if (!is_array($outItem)) continue;
						if (!empty($outItem['content']) && is_array($outItem['content'])) {
							foreach ($outItem['content'] as $cont) {
								if (!is_array($cont)) continue;
								if (!empty($cont['text']) && is_string($cont['text'])) {
									$texts[] = $cont['text'];
								} elseif (!empty($cont['output_text']) && is_string($cont['output_text'])) {
									$texts[] = $cont['output_text'];
								}
							}
						} elseif (!empty($outItem['text']) && is_string($outItem['text'])) {
							$texts[] = $outItem['text'];
						}
					}
					$generatedContent = trim(implode("\n", $texts));
				}

				if ($generatedContent === '' && !empty($decodedResponse['error'])) {
					if (is_scalar($decodedResponse['error'])) {
						$generatedContent = (string) $decodedResponse['error'];
					} else {
						$generatedContent = var_export($decodedResponse['error'], true);
					}
				}
			} else {
				if (!empty($decodedResponse['error'])) {
					if (is_scalar($decodedResponse['error'])) {
						$generatedContent = $decodedResponse['error'];
					} else {
						$generatedContent = var_export($decodedResponse['error'], true);
					}
				} else {
					$generatedContent = $decodedResponse['choices'][0]['message']['content'];
				}
			}
			dol_syslog("ai->generatedContent returned: ".dol_trunc($generatedContent, 50));

			// If content is not HTML, we convert it into HTML
			if ($format == 'html') {
				if (!dol_textishtml($generatedContent)) {
					dol_syslog("Result was detected as not HTML so we convert it into HTML.");
					$generatedContent = dol_nl2br($generatedContent);
				} else {
					dol_syslog("Result was detected as already HTML. Do nothing.");
				}

				// TODO If content is for website module, we must
				// - clan html header, keep body only and remove ``` ticks added by AI
				// - add tags <section contenEditable="true"> </section>
			}

			return $generatedContent;
		} catch (Exception $e) {
			$errormessage = $e->getMessage();
			$errormessagelog = $e->getMessage();
			$decodedResponse = null;
			if (!empty($response['content'])) {
				$decodedResponse = json_decode($response['content'], true);
				$errormessagelog .= ' - '.$response['content'];

				if (!empty($decodedResponse['error']['message'])) {
					// With OpenAI, error is into an object error into the content
					$errormessage .= ' - '.$decodedResponse['error']['message'];
				} else {
					$errormessage .= ' - '.$response['content'];
				}
			}

			if ($this->isQuotaExhaustedError((empty($response['http_code']) ? 0 : (int) $response['http_code']), $decodedResponse, $errormessage)) {
				$this->recordQuotaExhaustedWarning((empty($response['http_code']) ? 0 : (int) $response['http_code']), (string) $function, $errormessage, $decodedResponse);
			}

			if (getDolGlobalString("AI_DEBUG")) {
				if (@is_writable($dolibarr_main_data_root)) {	// Avoid fatal error on fopen with open_basedir
					$outputfile = $dolibarr_main_data_root."/dolibarr_ai.log";
					$fp = fopen($outputfile, "a");

					if ($fp) {
						fwrite($fp, "Error: ".$errormessagelog."\n");

						fclose($fp);
						dolChmod($outputfile);
					}
				}
			}

			return array(
				'error' => true,
				'message' => $errormessage,
				'code' => (empty($response['http_code']) ? 0 : $response['http_code']),
				'curl_error_no' => (empty($response['curl_error_no']) ? 0 : $response['curl_error_no']),
				'format' => $format,
				'service' => $this->apiService,
				'function' => $function
			);
		}
	}
}
