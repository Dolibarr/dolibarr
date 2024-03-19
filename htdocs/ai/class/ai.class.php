<?php
/* Copyright (C) 2024  Laurent Destailleur     <eldy@users.sourceforge.net>
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
	 * @var string $apiEndpoint
	 */
	private $apiEndpoint;

	/**
	 * @var string $apiKey
	 */
	private $apiKey;

	/**
	 * Constructor
	 *
	 * @param	DoliDB	$db		 Database handler
	 *
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->apiKey = getDolGlobalString('AI_API_CHATGPT_KEY');
	}

	/**
	 * Generate response of instructions
	 *
	 * @param   string  	$instructions   Instruction to generate content
	 * @param   string  	$model          Model name ('gpt-3.5-turbo', 'gpt-4-turbo', 'dall-e-3', ...)
	 * @param   string  	$function     	Code of the feature we want to use ('textgeneration', 'transcription', 'audiotext', 'imagegeneration', 'translation')
	 * @param	string		$format			Format for output ('', 'html', ...)
	 * @return  mixed   	$response
	 */
	public function generateContent($instructions, $model = 'auto', $function = 'textgeneration', $format = '')
	{
		if (empty($this->apiKey)) {
			return array('error' => true, 'message' => 'API key is no defined');
		}

		if (empty($this->apiEndpoint)) {
			if ($function == 'textgeneration') {
				$this->apiEndpoint = 'https://api.openai.com/v1/chat/completions';
				if ($model == 'auto') {
					$model = getDolGlobalString('AI_API_CHATGPT_MODEL_TEXT', 'gpt-3.5-turbo');
				}
			}
			if ($function == 'imagegeneration') {
				$this->apiEndpoint = 'https://api.openai.com/v1/images/generations';
				if ($model == 'auto') {
					$model = getDolGlobalString('AI_API_CHATGPT_MODEL_IMAGE', 'dall-e-3');
				}
			}
			if ($function == 'audiotext') {
				$this->apiEndpoint = 'https://api.openai.com/v1/audio/speech';
				if ($model == 'auto') {
					$model = getDolGlobalString('AI_API_CHATGPT_MODEL_AUDIO', 'tts-1');
				}
			}
			if ($function == 'transcription') {
				$this->apiEndpoint = 'https://api.openai.com/v1/audio/transcriptions';
				if ($model == 'auto') {
					$model = getDolGlobalString('AI_API_CHATGPT_MODEL_TRANSCRIPT', 'whisper-1');
				}
			}
			if ($function == 'translation') {
				$this->apiEndpoint = 'https://api.openai.com/v1/audio/translations';
				if ($model == 'auto') {
					$model = getDolGlobalString('AI_API_CHATGPT_MODEL_TRANSLATE', 'whisper-1');
				}
			}
		}

		dol_syslog("Call API for apiEndpoint=".$this->apiEndpoint." apiKey=".substr($this->apiKey, 0, 3).'***********, model='.$model);

		try {
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
			$fullInstructions = $prePrompt.' '.$instructions.' .'.$postPrompt;


			$payload = json_encode([
				'messages' => [
					['role' => 'user', 'content' => $fullInstructions]
				],
				'model' => $model
			]);

			$headers = ([
				'Authorization: Bearer ' . $this->apiKey,
				'Content-Type: application/json'
			]);
			$response = getURLContent($this->apiEndpoint, 'POST', $payload, 1, $headers);

			if (empty($response['http_code'])) {
				throw new Exception('API request failed. No http received');
			}
			if (!empty($response['http_code']) && $response['http_code'] != 200) {
				throw new Exception('API request failed with status code ' . $response['http_code']);
			}
			// Decode JSON response
			$decodedResponse = json_decode($response['content'], true);

			// Extraction content
			$generatedEmailContent = $decodedResponse['choices'][0]['message']['content'];

			// If content is not HTML, we convert it into HTML
			if (!dol_textishtml($generatedEmailContent)) {
				$generatedEmailContent = dol_nl2br($generatedEmailContent);
			}

			return $generatedEmailContent;
		} catch (Exception $e) {
			$errormessage = $e->getMessage();
			if (!empty($response['content'])) {
				$decodedResponse = json_decode($response['content'], true);

				// With OpenAI, error is into an object error into the content
				if (!empty($decodedResponse['error']['message'])) {
					$errormessage .= ' - '.$decodedResponse['error']['message'];
				}
			}

			return array('error' => true, 'message' => $errormessage, 'code' => (empty($response['http_code']) ? 0 : $response['http_code']), 'curl_error_no' => (empty($response['curl_error_no']) ? $response['curl_error_no'] : ''));
		}
	}
}
