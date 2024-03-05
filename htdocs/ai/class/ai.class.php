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
		$this->apiEndpoint = dolibarr_get_const($this->db, 'AI_API_ENDPOINT');
		$this->apiKey = dolibarr_get_const($this->db, 'AI_KEY_API_CHATGPT');
	}

	/**
	 * Generate response of instructions
	 * @param   string  $instructions   instruction for generate content
	 * @param   string  $model          model name (chat,text,image...)
	 * @param   string  $moduleName     Name of module
	 * @return   mixed   $response
	 */
	public function generateContent($instructions, $model = 'gpt-3.5-turbo', $moduleName = 'MAILING')
	{
		global $conf;
		try {
			$configurationsJson = dolibarr_get_const($this->db, 'AI_CONFIGURATIONS_PROMPT', $conf->entity);
			$configurations = json_decode($configurationsJson, true);

			$prePrompt = '';
			$postPrompt = '';

			if (isset($configurations[$moduleName])) {
				if (isset($configurations[$moduleName]['prePrompt'])) {
					$prePrompt = $configurations[$moduleName]['prePrompt'];
				}

				if (isset($configurations[$moduleName]['postPrompt'])) {
					$postPrompt = $configurations[$moduleName]['postPrompt'];
				}
			}
			$fullInstructions = $prePrompt.' '.$instructions.' .'.$postPrompt;

			$ch = curl_init($this->apiEndpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
				'messages' => [
					['role' => 'user', 'content' => $fullInstructions]
				],
				'model' => $model
			]));
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Authorization: Bearer ' . $this->apiKey,
				'Content-Type: application/json'
			]);

			$response = curl_exec($ch);
			if (curl_errno($ch)) {
				throw new Exception('cURL error: ' . curl_error($ch));
			}

			$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($statusCode != 200) {
				throw new Exception('API request failed with status code ' . $statusCode);
			}
			// Decode JSON response
			$decodedResponse = json_decode($response, true);

			// Extraction content
			$generatedEmailContent = $decodedResponse['choices'][0]['message']['content'];

			return $generatedEmailContent;
		} catch (Exception $e) {
			return array('error' => true, 'message' => $e->getMessage());
		} finally {
			curl_close($ch);
		}
	}
}
