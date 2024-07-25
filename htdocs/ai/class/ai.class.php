<?php
/* Copyright (C) 2024  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
	 * Generate response of instructions
	 *
	 * @param   string  		$instructions   Instruction to generate content
	 * @param   string  		$model          Model name ('gpt-3.5-turbo', 'gpt-4-turbo', 'dall-e-3', ...)
	 * @param   string  		$function     	Code of the feature we want to use ('textgeneration', 'transcription', 'audiogeneration', 'imagegeneration', 'translation')
	 * @param	string			$format			Format for output ('', 'html', ...)
	 * @return  string|array   	$response		Text or array if error
	 */
	public function generateContent($instructions, $model = 'auto', $function = 'textgeneration', $format = '')
	{
		if (empty($this->apiKey)) {
			return array('error' => true, 'message' => 'API key is not defined for the AI enabled service ('.$this->apiService.')');
		}

		// $this->apiEndpoint is set here only if forced.
		// In most cases, it is empty and we must get it from $function and $this->apiService
		if (empty($this->apiEndpoint)) {
			if ($function == 'imagegeneration') {
				if ($this->apiService == 'chatgpt') {
					$this->apiEndpoint = getDolGlobalString('AI_API_CHATGPT_URL', 'https://api.openai.com/v1').'/images/generations';
				} elseif ($this->apiService == 'groq') {
					$this->apiEndpoint = getDolGlobalString('AI_API_GROK_URL', 'https://api.groq.com/openai/v1').'/images/generations';
				} elseif ($this->apiService == 'custom') {
					$this->apiEndpoint = getDolGlobalString('AI_API_CUSTOM_URL', '').'/images/generations';
				}
			} elseif ($function == 'audiogeneration') {
				if ($this->apiService == 'chatgpt') {
					$this->apiEndpoint = getDolGlobalString('AI_API_CHATGPT_URL', 'https://api.openai.com/v1').'/audio/speech';
				} elseif ($this->apiService == 'groq') {
					$this->apiEndpoint = getDolGlobalString('AI_API_GROK_URL', 'https://api.groq.com/openai/v1').'/audio/speech';
				} elseif ($this->apiService == 'custom') {
					$this->apiEndpoint = getDolGlobalString('AI_API_CUSTOM_URL', '').'/audio/speech';
				}
			} elseif ($function == 'transcription') {
				if ($this->apiService == 'chatgpt') {
					$this->apiEndpoint = getDolGlobalString('AI_API_CHATGPT_URL', 'https://api.openai.com/v1').'/transcriptions';
				} elseif ($this->apiService == 'groq') {
					$this->apiEndpoint = getDolGlobalString('AI_API_GROK_URL', 'https://api.groq.com/openai/v1').'/transcriptions';
				} elseif ($this->apiService == 'custom') {
					$this->apiEndpoint = getDolGlobalString('AI_API_CUSTOM_URL', '').'/transcriptions';
				}
			} elseif ($function == 'translation') {
				if ($this->apiService == 'chatgpt') {
					$this->apiEndpoint = getDolGlobalString('AI_API_CHATGPT_URL', 'https://api.openai.com/v1').'/translations';
				} elseif ($this->apiService == 'groq') {
					$this->apiEndpoint = getDolGlobalString('AI_API_GROK_URL', 'https://api.groq.com/openai/v1').'/translations';
				} elseif ($this->apiService == 'custom') {
					$this->apiEndpoint = getDolGlobalString('AI_API_CUSTOM_URL', '').'/translations';
				}
			} else {	// else textgeneration...
				if ($this->apiService == 'chatgpt') {
					$this->apiEndpoint = getDolGlobalString('AI_API_CHATGPT_URL', 'https://api.openai.com/v1').'/chat/completions';
				} elseif ($this->apiService == 'groq') {
					$this->apiEndpoint = getDolGlobalString('AI_API_GROK_URL', 'https://api.groq.com/openai/v1').'/chat/completions';
				} elseif ($this->apiService == 'custom') {
					$this->apiEndpoint = getDolGlobalString('AI_API_CUSTOM_URL', '').'/chat/completions';
				}
			}
		}

		// $model may be undefined or 'auto'.
		// If this is the case, we must get it from $function and $this->apiService
		if (empty($model) || $model == 'auto') {
			// Return the endpoint and the model from $this->apiService.
			if ($function == 'imagegeneration') {
				if ($this->apiService == 'chatgpt') {
					$model = getDolGlobalString('AI_API_CHATGPT_MODEL_IMAGE', 'dall-e-3');
				} elseif ($this->apiService == 'groq') {
					$model = getDolGlobalString('AI_API_GROK_MODEL_IMAGE', 'mixtral-8x7b-32768');	// 'llama3-8b-8192', 'gemma-7b-it'
				} elseif ($this->apiService == 'custom') {
					$model = getDolGlobalString('AI_API_CUSTOM_MODEL_IMAGE', 'dall-e-3');
				}
			} elseif ($function == 'audiogeneration') {
				if ($this->apiService == 'chatgpt') {
					$model = getDolGlobalString('AI_API_CHATGPT_MODEL_AUDIO', 'tts-1');
				} elseif ($this->apiService == 'groq') {
					$model = getDolGlobalString('AI_API_GROK_MODEL_AUDIO', 'mixtral-8x7b-32768');	// 'llama3-8b-8192', 'gemma-7b-it'
				} elseif ($this->apiService == 'custom') {
					$model = getDolGlobalString('AI_API_CUSTOM_MODEL_AUDIO', 'tts-1');
				}
			} elseif ($function == 'transcription') {
				if ($this->apiService == 'chatgpt') {
					$model = getDolGlobalString('AI_API_CHATGPT_MODEL_TRANSCRIPT', 'whisper-1');
				} elseif ($this->apiService == 'groq') {
					$model = getDolGlobalString('AI_API_GROK_MODEL_TRANSCRIPT', 'mixtral-8x7b-32768');	// 'llama3-8b-8192', 'gemma-7b-it'
				} elseif ($this->apiService == 'custom') {
					$model = getDolGlobalString('AI_API_CUSTOM_TRANSCRIPT', 'whisper-1');
				}
			} elseif ($function == 'translation') {
				if ($this->apiService == 'chatgpt') {
					$model = getDolGlobalString('AI_API_CHATGPT_MODEL_TRANSLATE', 'whisper-1');
				} elseif ($this->apiService == 'groq') {
					$model = getDolGlobalString('AI_API_GROK_MODEL_TRANSLATE', 'mixtral-8x7b-32768');	// 'llama3-8b-8192', 'gemma-7b-it'
				} elseif ($this->apiService == 'custom') {
					$model = getDolGlobalString('AI_API_CUSTOM_TRANSLATE', 'whisper-1');
				}
			} else {	// else textgeneration...
				if ($this->apiService == 'chatgpt') {
					$model = getDolGlobalString('AI_API_CHATGPT_MODEL_TEXT', 'gpt-3.5-turbo');
				} elseif ($this->apiService == 'groq') {
					$model = getDolGlobalString('AI_API_GROK_MODEL_TEXT', 'mixtral-8x7b-32768');	// 'llama3-8b-8192', 'gemma-7b-it'
				} elseif ($this->apiService == 'custom') {
					$model = getDolGlobalString('AI_API_CUSTOM_MODEL_TEXT', 'tinyllama-1.1b');		// with JAN: 'tinyllama-1.1b', 'mistral-ins-7b-q4'
				}
			}
		}

		dol_syslog("Call API for apiKey=".substr($this->apiKey, 0, 3).'***********, apiEndpoint='.$this->apiEndpoint.", model=".$model);

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
					$prePrompt = $configurations[$function]['prePrompt'];	// TODO We can send prePrompt into a separated message with role system.
				}

				if (isset($configurations[$function]['postPrompt'])) {
					$postPrompt = $configurations[$function]['postPrompt'];
				}
			}
			$fullInstructions = ($prePrompt ? $prePrompt.' ' : '').$instructions.($postPrompt ? '. '.$postPrompt : '');

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
			$payload = json_encode([
				'messages' => [
					['role' => 'user', 'content' => $fullInstructions]
				],
				'model' => $model,
				//'stream' => false
			]);

			$headers = ([
				'Authorization: Bearer ' . $this->apiKey,
				'Content-Type: application/json'
			]);

			$localurl = 2;	// Accept both local and external endpoints
			$response = getURLContent($this->apiEndpoint, 'POST', $payload, 1, $headers, array('http', 'https'), $localurl);

			if (empty($response['http_code'])) {
				throw new Exception('API request failed. No http received');
			}
			if (!empty($response['http_code']) && $response['http_code'] != 200) {
				throw new Exception('API request on AI endpoint '.$this->apiEndpoint.' failed with status code '.$response['http_code'].(empty($response['content']) ? '' : ' - '.$response['content']));
			}

			if (getDolGlobalString("AI_DEBUG")) {
				dol_syslog("response content = ".var_export($response['content'], true));
			}

			// Decode JSON response
			$decodedResponse = json_decode($response['content'], true);

			// Extraction content
			$generatedContent = $decodedResponse['choices'][0]['message']['content'];

			dol_syslog("generatedContent=".dol_trunc($generatedContent, 50));

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
			if (!empty($response['content'])) {
				$decodedResponse = json_decode($response['content'], true);

				// With OpenAI, error is into an object error into the content
				if (!empty($decodedResponse['error']['message'])) {
					$errormessage .= ' - '.$decodedResponse['error']['message'];
				}
			}

			return array(
				'error' => true,
				'message' => $errormessage,
				'code' => (empty($response['http_code']) ? 0 : $response['http_code']),
				'curl_error_no' => (empty($response['curl_error_no']) ? $response['curl_error_no'] : ''),
				'format' => $format,
				'service' => $this->apiService,
				'function'=>$function
			);
		}
	}
}
