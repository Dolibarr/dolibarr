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
	 * @var int Status indicating a successful operation
	 */
	const STATUS_SUCCESS = 1;

	/**
	 * @var int Status indicating a failed operation
	 */
	const STATUS_ERROR = 0;

	/**
	 * @var int HTTP status code: OK
	 */
	const HTTP_OK = 200;

	/**
	 * @var int HTTP status code: Created
	 */
	const HTTP_CREATED = 201;

	/**
	 * @var int HTTP status code: Accepted (request accepted but processing not completed yet)
	 */
	const HTTP_ACCEPTED = 202;

	/**
	 * @var int HTTP status code: Bad Request (invalid parameters or malformed request)
	 */
	const HTTP_BAD_REQUEST = 400;

	/**
	 * @var int HTTP status code: Unauthorized (authentication required or failed)
	 */
	const HTTP_UNAUTHORIZED = 401;

	/**
	 * @var int HTTP status code: Forbidden (authenticated but not enough permissions)
	 */
	const HTTP_FORBIDDEN = 403;

	/**
	 * @var int HTTP status code: Not Found
	 */
	const HTTP_NOT_FOUND = 404;

	/**
	 * @var int HTTP status code: Internal Server Error
	 */
	const HTTP_INTERNAL_ERROR = 500;

	/**
	 * @var int HTTP status code: Not Implemented
	 */
	const HTTP_NOT_IMPLEMENTED = 501;

	/**
	 * @var int HTTP status code: Service Unavailable
	 */
	const HTTP_SERVICE_UNAVAILABLE = 503;

	/**
	 * When enabled, an error response sent with a 2xx HTTP code is upgraded to self::HTTP_BAD_REQUEST.
	 *
	 * @var bool $changeHeaderForErrors
	 */
	public $changeHeaderForErrors = false;

	/**
	 * http response code, null if not explicitly set with setError(), setSuccess() or setHttpResponseCode()
	 *
	 * @var int|null
	 */
	private $httpResponseCode = null;

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
	 * debug data you can set all data you want
	 * Only returned when $dolibarr_main_prod is off and constant DEBUGJSONRESPONSE is set to 1
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
	 * JsonResponse constructor.
	 */
	public function __construct()
	{
		$this->newToken = newToken();
	}

	/**
	 * return json encoded of object and send the HTTP response code if headers are not already sent
	 *
	 * @return string JSON
	 */
	public function getResponse()
	{
		global $dolibarr_main_prod;

		if (!headers_sent()) {
			http_response_code($this->getHttpResponseCode());
		}

		$jsonResponse = new stdClass();
		$jsonResponse->result = $this->result;
		$jsonResponse->msg = $this->msg;
		$jsonResponse->newToken = $this->newToken;
		$jsonResponse->data = $this->data;

		if (empty($dolibarr_main_prod) && defined('DEBUGJSONRESPONSE') && (int) constant('DEBUGJSONRESPONSE') > 0) {
			$jsonResponse->debug = $this->debug;
		}

		return json_encode($jsonResponse, JSON_PRETTY_PRINT);
	}

	/**
	 * Get the HTTP response code that will be sent.
	 *
	 * If no code was explicitly set, keep the historical behavior: 200 on success, 400 on error.
	 *
	 * @return int
	 */
	public function getHttpResponseCode()
	{
		$httpCode = $this->httpResponseCode;
		if ($httpCode === null) {
			$httpCode = $this->result ? self::HTTP_OK : self::HTTP_BAD_REQUEST;
		}

		if ($this->changeHeaderForErrors && !$this->result && $this->isValidHttpSuccessCode($httpCode)) {
			$httpCode = self::HTTP_BAD_REQUEST;
		}

		return $httpCode;
	}

	/**
	 * Set the current response as an error response.
	 *
	 * @param string $msg 		Error message returned in the JSON response.
	 * @param int 	 $httpCode 	HTTP response code. Defaults to 200 for application errors, as the JSON response already
	 *                       	contains the error status and message through the "result" and "msg" fields.
	 *                       	Use 4xx/5xx codes when the error must also be reported at the HTTP protocol level
	 *                       	(permissions, invalid requests, server failures...).
	 * @return void
	 */
	public function setError($msg = '', $httpCode = self::HTTP_OK)
	{
		if (!$this->isValidHttpErrorCode($httpCode)) {
			$httpCode = self::HTTP_OK;
		}

		$this->result = self::STATUS_ERROR;
		$this->msg = $msg;
		$this->setHttpResponseCode($httpCode);
	}

	/**
	 * Set the response as a success.
	 *
	 * @param string $msg 		Success message to return.
	 * @param int 	 $httpCode 	HTTP status code (2xx only, default: 200).
	 * @return void
	 */
	public function setSuccess($msg = '', $httpCode = self::HTTP_OK)
	{
		if (!$this->isValidHttpSuccessCode($httpCode)) {
			$httpCode = self::HTTP_OK;
		}

		$this->result = self::STATUS_SUCCESS;
		$this->msg = $msg;
		$this->setHttpResponseCode($httpCode);
	}

	/**
	 * Define the HTTP response code used when sending the JSON response.
	 * Allowed codes are the HTTP_* constants of this class.
	 *
	 * @param int $httpCode HTTP response code.
	 * @return bool 		True if the response code is allowed and applied, false otherwise.
	 */
	public function setHttpResponseCode($httpCode)
	{
		if (!$this->isValidHttpErrorCode($httpCode) && !$this->isValidHttpSuccessCode($httpCode)) {
			return false;
		}

		$this->httpResponseCode = $httpCode;
		return true;
	}

	/**
	 * Check if HTTP code is a valid error code (4xx or 5xx).
	 *
	 * @param int $code HTTP code
	 * @return bool
	 */
	private function isValidHttpErrorCode($code)
	{
		return in_array($code, [
			self::HTTP_BAD_REQUEST,
			self::HTTP_UNAUTHORIZED,
			self::HTTP_FORBIDDEN,
			self::HTTP_NOT_FOUND,
			self::HTTP_INTERNAL_ERROR,
			self::HTTP_NOT_IMPLEMENTED,
			self::HTTP_SERVICE_UNAVAILABLE
		], true);
	}

	/**
	 * Check if HTTP code is a valid success code (2xx).
	 *
	 * @param int $code HTTP code
	 * @return bool
	 */
	private function isValidHttpSuccessCode($code)
	{
		return in_array($code, [
			self::HTTP_OK,
			self::HTTP_CREATED,
			self::HTTP_ACCEPTED
		], true);
	}

	/**
	 * Send the JSON response to the client (JSON content-type header, HTTP response code and payload),
	 * close the database handler and stop the script.
	 *
	 * @return void
	 */
	public function output()
	{
		global $db;

		if (!headers_sent()) {
			top_httphead('application/json');
		}

		print $this->getResponse();

		if (is_object($db)) {
			$db->close();
		}
		exit;
	}
}
