<?php
/* Copyright (C) 2008-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2016  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2023       Eric Seigne      		<eric.seigne@cap-rel.fr>
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
	 * @param   string  	$instructions   instruction for generate content
	 * @param   string  	$model          model name ('gpt-3.5-turbo')
	 * @param   string  	$function     	code of the feature we want to use ('emailing', 'transcription', 'audiotext', 'imagegeneration', 'translation')
	 * @return  mixed   	$response
	 */
	public function generateContent($instructions, $model = 'auto', $function = 'textgeneration')
	{
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

			// TODO Replace this with a simple call of getDolURLContent();
			// $ch = curl_init($this->apiEndpoint);
			// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			//curl_setopt($ch, CURLOPT_HTTPHEADER, [
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
			$response = getURLContent($this->apiEndpoint, 'POST', $payload, $headers);

			if ($response['http_code']  != 200) {
				throw new Exception('API request failed with status code ' . $response['http_code']);
			}
			// Decode JSON response
			$decodedResponse = json_decode($response['content'], true);

			// Extraction content
			$generatedEmailContent = $decodedResponse['choices'][0]['message']['content'];

			return $generatedEmailContent;
		} catch (Exception $e) {
			return array('error' => true, 'message' => $e->getMessage());
		}
	}
}
