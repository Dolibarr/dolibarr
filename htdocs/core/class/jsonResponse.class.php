<?php
/* Copyright (C) 2024 John BOTELLA
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
 */


/**
 * Class JsonResponse
 * used for ajax responses in Dolibarr
 */
class JsonResponse
{

	/**
	 * the call status to determine if success or fail
	 *
	 * @var int $result 0|1
	 */
	public $result = 0;

	/**
	 * data to return to call can be all type you want
	 *
	 * @var mixed
	 */
	public $data;

	/**
	 * debug data
	 *
	 * @var mixed
	 */
	public $debug;

	/**
	 * returned message used usually as set event message
	 *
	 * @var string $msg
	 */
	public $msg = '';

	/**
	 * the current newToken
	 *
	 * @var mixed|string
	 */
	public $newToken = '';

	/**
	 * Event messages to display by the caller, by style
	 *
	 * @var array{mesgs?:string[],warnings?:string[],errors?:string[]}
	 */
	public $eventMessages = array();

	/**
	 * JsonResponse constructor.
	 */
	public function __construct()
	{
		$this->newToken = newToken();
	}

	/**
	 * Move the event messages set with setEventMessages() from the session into the response.
	 * They are then displayed by the ajax caller (Dolibarr.tools.fetch) instead of on the next loaded page.
	 *
	 * @return void
	 */
	public function addSessionEventMessages()
	{
		global $langs;

		if (empty($_SESSION['dol_events']) || !is_array($_SESSION['dol_events'])) {
			return;
		}

		if (is_object($langs)) {
			$langs->load('errors');
		}

		foreach (array('mesgs', 'warnings', 'errors') as $style) {
			if (!empty($_SESSION['dol_events'][$style]) && is_array($_SESSION['dol_events'][$style])) {
				foreach ($_SESSION['dol_events'][$style] as $message) {
					// Same translation as dol_htmloutput_mesg(), messages can be translation keys
					$this->eventMessages[$style][] = is_object($langs) ? $langs->trans((string) $message) : (string) $message;
				}
			}
			unset($_SESSION['dol_events'][$style]);
		}
	}

	/**
	 * return json encoded of object
	 *
	 * @return string JSON
	 */
	public function getResponse()
	{

		if (!headers_sent()) {
			header('Content-Type: application/json; charset=utf-8');
			if (!$this->result) {
				http_response_code(400);
			}
		}

		$jsonResponse = new stdClass();
		$jsonResponse->result = $this->result;
		$jsonResponse->msg = $this->msg;
		$jsonResponse->newToken = $this->newToken;
		$jsonResponse->data = $this->data;
		$jsonResponse->debug = $this->debug;
		$jsonResponse->eventMessages = (object) $this->eventMessages;

		return json_encode($jsonResponse, JSON_PRETTY_PRINT);
	}
}
