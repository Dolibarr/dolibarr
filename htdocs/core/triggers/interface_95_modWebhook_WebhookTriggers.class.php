<?php
/* Copyright (C) 2022	SuperAdmin		<test@dolibarr.com>
 * Copyright (C) 2023	William Mead	<william.mead@manchenumerique.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modWebhook_WebhookTriggers.class.php
 * \ingroup webhook
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modWebhook_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/webhook/class/target.class.php';

/**
 *  Class of triggers for Webhook module
 */
class InterfaceWebhookTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		parent::__construct($db);
		$this->family = "demo";
		$this->description = "Webhook triggers.";
		$this->version = self::VERSIONS['dev'];
		$this->picto = 'webhook';
	}

	/**
	 * Function called when a Dolibarr business event is done.
	 * All functions "runTrigger" are triggered if file of function is inside directory core/triggers.
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->webhook) || empty($conf->webhook->enabled)) {
			return 0; // If module is not enabled, we do nothing
		}
		require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

		// Or you can execute some code here
		$nbPosts = 0;
		$errors = 0;
		$static_object = new Target($this->db);
		$target_url = $static_object->fetchAll();
		foreach ($target_url as $key => $tmpobject) {
			$actionarray = explode(",", $tmpobject->trigger_codes);
			if ($tmpobject->status == Target::STATUS_VALIDATED && is_array($actionarray) && in_array($action, $actionarray)) {
				// Build the answer object
				$resobject = new stdClass();
				$resobject->triggercode = $action;
				$resobject->object = dol_clone($object, 2);

				if (property_exists($resobject->object, 'fields')) {
					unset($resobject->object->fields);
				}
				if (property_exists($resobject->object, 'error')) {
					unset($resobject->object->error);
				}
				if (property_exists($resobject->object, 'errors')) {
					unset($resobject->object->errors);
				}

				$jsonstr = json_encode($resobject);

				$headers = array(
					'Content-Type: application/json'
					//'Accept: application/json'
				);

				$response = getURLContent($tmpobject->url, 'POST', $jsonstr, 1, $headers, array('http', 'https'), 2, -1);

				if (empty($response['curl_error_no']) && $response['http_code'] >= 200 && $response['http_code'] < 300) {
					$nbPosts++;
				} else {
					$errors++;
					$errormsg = "The WebHook for ".$action." failed to get URL ".$tmpobject->url." with httpcode=".(!empty($response['http_code']) ? $response['http_code'] : "")." curl_error_no=".(!empty($response['curl_error_no']) ? $response['curl_error_no'] : "");
					$this->errors[] = $errormsg;
					/*if (!empty($response['content'])) {
						$this->errors[] = dol_trunc($response['content'], 200);
					}*/
					dol_syslog($errormsg, LOG_ERR);
				}
			}
		}
		if (!empty($errors)) {
			return $errors * -1;
		}
		return $nbPosts;
	}
}
