<?php
/* Copyright (C) 2024	Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024	Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024	MDW						<mdeweerd@users.noreply.github.com>
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
 * Class for AI feature
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

	const AI_DEFAULT_PROMPT_FOR_EMAIL = 'You are an email editor. Return only the content of the message. Do not add explanation.';	// Note: This instruction will also be completed by generateContent() to manage text versus HTML content.
	const AI_DEFAULT_PROMPT_FOR_WEBPAGE = 'You are a website editor. Return all HTML content inside a section tag. Do not add explanation.';
	const AI_DEFAULT_PROMPT_FOR_TEXT_TRANSLATION = 'You are a translator, answer with one and only one translation with no comment and explanation.';
	const AI_DEFAULT_PROMPT_FOR_TEXT_SUMMARIZE = 'You are a writer, make the answer in the same language than the original text to summarize.';
	const AI_DEFAULT_PROMPT_FOR_TEXT_SPELLCHECKER = 'You are a proofreader, write your response in the same language as the original text in order to correct spelling and grammar errors. If there is carriage return or line feed in original message, keep them. Keep also any HTML or markdown formatting without adding one, just fix spelling and grammar errors. Answer with the corrected text and only the corrected text with no comment and explanation.';
	const AI_DEFAULT_PROMPT_FOR_TEXT_REPHRASER = 'You are a writer, write your response in the same language as the original text to rephrase. Give only one answer with no comment and explanation. If there is carriage return or line feed in original message, keep them. Keep also any HTML or markdown formatting without adding one.';
	const AI_DEFAULT_PROMPT_FOR_EXTRAFIELD_FILLER = 'Give only one answer with no comment and explanation, I want the text to be ready to copy and paste.';
	const AI_DEFAULT_PROMPT_FOR_DOC_PARSING = 'You are an assistant to analyze documents. Return your answer with a JSON string and only a JSON string, do not add any other comment.';


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
	 * Generate the response of an AI prompt.
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
			} else {	// if $function == 'docparsing', 'text...', ...
				$this->apiEndpoint = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_URL', $arrayofai[$this->apiService]['url']);
				if ($this->apiService == 'google') {
					// Google Gemini native API: the /models/<model>:generateContent suffix is
					// appended later (once $model has been resolved). The OpenAI-style
					// /chat/completions does not exist on the native Gemini endpoint.
					$this->apiEndpoint = rtrim($this->apiEndpoint, '/');
				} else {
					$this->apiEndpoint .= (preg_match('/\/$/', $this->apiEndpoint) ? '' : '/').'chat/completions';
				}
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
				$model = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_MODEL_IMAGE', $arrayofai[$this->apiService][$function]['default']);
			} elseif ($function == 'audiogeneration') {
				$model = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_MODEL_AUDIO', $arrayofai[$this->apiService][$function]['default']);
			} elseif ($function == 'transcription') {
				$model = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_MODEL_TRANSCRIPT', $arrayofai[$this->apiService][$function]['default']);
			} elseif ($function == 'translation') {
				$model = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_MODEL_TRANSLATE', $arrayofai[$this->apiService][$function]['default']);
			} elseif ($function == 'docparsing') {
				$model = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_MODEL_DOCPARSING', $arrayofai[$this->apiService][$function]['default']);
			} else {
				// else 'textgenerationemail', 'textgenerationwebpage', 'textgeneration', 'texttranslation', 'textsummarize', 'textrephraser', 'textspellchecker', ...
				$model = getDolGlobalString('AI_API_'.strtoupper($this->apiService).'_MODEL_TEXT', $arrayofai[$this->apiService]['textgeneration']['default']);
			}
		}

		// Google Gemini: append /models/<model>:generateContent now that $model is resolved.
		if ($this->apiService == 'google' && !in_array($function, array('file', 'assistant', 'thread'))
			&& strpos($this->apiEndpoint, ':generateContent') === false) {
			$this->apiEndpoint .= '/models/'.rawurlencode($model).':generateContent';
		}

		dol_syslog("Call API for apiKey=".substr($this->apiKey, 0, 5).'***********, apiEndpoint='.$this->apiEndpoint.", model=".$model.", format=".$format);
		if (getDolGlobalString("AI_DEBUG")) {
			if (@is_writable($dolibarr_main_data_root)) {	// Avoid fatal error on fopen with open_basedir
				$outputfile = $dolibarr_main_data_root."/dolibarr_ai.log";
				$fp = fopen($outputfile, "w");	// overwrite

				if ($fp) {
					fwrite($fp, "Call API for apiKey=".substr($this->apiKey, 0, 5).'***********, apiEndpoint='.$this->apiEndpoint.", model=".$model.", format=".$format."\n");
					fclose($fp);
					dolChmod($outputfile);
				}
			}
		}

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
			//var_dump($prePrompt);

			// Get the default value of prePrompt if not defined
			if (empty($prePrompt) && $function == 'textgenerationemail') {
				$prePrompt = self::AI_DEFAULT_PROMPT_FOR_EMAIL;
				if ($format === 'html') {
					$prePrompt .= ' Return all HTML content inside a section tag';
				} else {
					$prePrompt .= ' Return content in UTF8 text. Use Linux carriage return if you need to split a line. Do not include any HTML tag neither HTML entities.';
				}
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
			if (empty($prePrompt) && $function == 'textspellchecker') {
				$prePrompt = self::AI_DEFAULT_PROMPT_FOR_TEXT_SPELLCHECKER;
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

				// Add a system message
				$addDateTimeContext = false;
				if ($addDateTimeContext) {		// @phpstan-ignore-line
					$prePrompt = ($prePrompt ? $prePrompt.(preg_match('/[\.\!\?]$/', $prePrompt) ? '' : '.').' ' : '').'Today we are '.dol_print_date(dol_now(), 'dayhourtext');
				}

				if ($this->apiService == 'google') {
					// Google Gemini native payload format (different from OpenAI's "messages").
					$arrayforpayload = array(
						'contents' => array(
							array('role' => 'user', 'parts' => array(array('text' => $fullInstructions)))
						)
					);
					if ($prePrompt) {
						$arrayforpayload['system_instruction'] = array(
							'parts' => array(array('text' => $prePrompt))
						);
					}
				} else {
					// OpenAI-compatible payload format (chatgpt, mistral, groq, anthropic-compat, custom, ...)
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

			if ($this->apiService == 'google') {
				// Google Gemini uses the x-goog-api-key header (Bearer is not accepted by the native API).
				$headers = array(
					'x-goog-api-key: ' . $this->apiKey,
				);
			} else {
				$headers = array(
					'Authorization: Bearer ' . $this->apiKey,
				);
			}
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
					$fp = fopen($outputfile, "a");

					if ($fp) {
						if ($function == 'docparsing') {
							fwrite($fp, "Call endpoint ".$this->apiEndpoint." with POST and the following file to upload:\n");
							fwrite($fp, $instructions."\n");
						} else {
							fwrite($fp, "Call endpoint ".$this->apiEndpoint." with POST and the following message:\n");
							fwrite($fp, $fullInstructions."\n");
							fwrite($fp, "And prepompt:\n");
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

			// By default, we accept only external endpoints ($dolibarr_ai_allow_local_endpoints is not set).
			// To allow local endpoints, we must set $dolibarr_ai_allow_local_endpoints to 1 or 2 in conf.php.
			global $dolibarr_ai_allow_local_endpoints;
			$localurl = $dolibarr_ai_allow_local_endpoints ?? 0;

			$response = getURLContent($this->apiEndpoint, 'POST', $payload, 1, $headers, array('http', 'https'), $localurl);

			if (empty($response['http_code'])) {
				throw new Exception('API request failed. No http received');
			}
			if (!empty($response['http_code']) && $response['http_code'] != 200) {
				if (in_array($response['http_code'], array(400, 401, 403, 429)) && !empty($response['content'])) {
					$tmp = json_decode($response['content'], true);
					if (!empty($tmp['message'])) {
						return array(
							'error' => true,
							'message' => $tmp['message'],
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

			// Extraction content
			if (!empty($decodedResponse['error'])) {
				if (is_scalar($decodedResponse['error'])) {
					$generatedContent = $decodedResponse['error'];
				} else {
					$generatedContent = var_export($decodedResponse['error'], true);
				}
			} elseif ($this->apiService == 'google') {
				// Google Gemini response shape: candidates[0].content.parts[*].text
				// (parts is an array because Gemini can return mixed-modality output;
				// we concatenate the textual parts.)
				$generatedContent = '';
				if (!empty($decodedResponse['candidates'][0]['content']['parts'])) {
					foreach ($decodedResponse['candidates'][0]['content']['parts'] as $part) {
						if (isset($part['text'])) {
							$generatedContent .= $part['text'];
						}
					}
				}
			} else {
				$generatedContent = $decodedResponse['choices'][0]['message']['content'];
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
