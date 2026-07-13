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
	 * When enabled, send HTTP status code self::HTTP_BAD_REQUEST if the response indicates an error.
	 *
	 * @var bool $changeHeaderForErrors
	 */
	public $changeHeaderForErrors = false;


	/**
	 * Status indicating a successful operation.
	 *
	 * @var int
	 */
	const STATUS_SUCCESS = 1;

	/**
	 * Status indicating a failed operation.
	 *
	 * @var int
	 */
	const STATUS_ERROR = 0;

	/**
	 * HTTP status code: OK
	 *
	 * Standard response for successful HTTP requests.
	 *
	 * @var int
	 */
	const HTTP_OK = 200;

	/**
	 * HTTP status code: Created
	 *
	 * Resource has been successfully created.
	 *
	 * @var int
	 */
	const HTTP_CREATED = 201;

	/**
	 * HTTP status code: Accepted
	 *
	 * Request has been accepted but not yet processed.
	 *
	 * @var int
	 */
	const HTTP_ACCEPTED = 202;

	/**
	 * HTTP status code: Bad Request
	 *
	 * The request is invalid or malformed.
	 *
	 * @var int
	 */
	const HTTP_BAD_REQUEST = 400;

	/**
	 * HTTP status code: Unauthorized
	 *
	 * Authentication is required or has failed.
	 *
	 * @var int
	 */
	const HTTP_UNAUTHORIZED = 401;

	/**
	 * HTTP status code: Forbidden
	 *
	 * The client does not have permission to access the resource.
	 *
	 * @var int
	 */
	const HTTP_FORBIDDEN = 403;

	/**
	 * HTTP status code: Not Found
	 *
	 * The requested resource could not be found.
	 *
	 * @var int
	 */
	const HTTP_NOT_FOUND = 404;

	/**
	 * HTTP status code: Internal Server Error
	 *
	 * A generic server error occurred.
	 *
	 * @var int
	 */
	const HTTP_INTERNAL_ERROR = 500;

	/**
	 * HTTP status code: Not Implemented
	 *
	 * The requested functionality is not implemented.
	 *
	 * @var int
	 */
	const HTTP_NOT_IMPLEMENTED = 501;

	/**
	 * HTTP status code: Service Unavailable
	 *
	 * The server is temporarily unavailable or under maintenance.
	 *
	 * @var int
	 */
	const HTTP_SERVICE_UNAVAILABLE = 503;

	/**
	 * http response code
	 * @var int
	 */
	private $httpResponseCode = 200;

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
	 * return json encoded of object
	 *
	 * @return string JSON
	 */
	public function getResponse()
	{
		global $dolibarr_main_prod;

		if ($this->changeHeaderForErrors && !$this->result && $this->httpResponseCode === self::HTTP_OK) {
			$this->httpResponseCode = self::HTTP_BAD_REQUEST;
		}

		$jsonResponse = new stdClass();
		$jsonResponse->result = $this->result;
		$jsonResponse->msg = $this->msg;
		$jsonResponse->newToken = $this->newToken;
		$jsonResponse->data = $this->data;

		if ((empty($dolibarr_main_prod) || (int) $dolibarr_main_prod === 0) && defined('DEBUGJSONRESPONSE') && (int) DEBUGJSONRESPONSE > 0) {
			$jsonResponse->debug = $this->debug;
		}

		return json_encode($jsonResponse, JSON_PRETTY_PRINT);
	}

	/**
	 * Set the current response as an error response.
	 *
	 * This method automatically:
	 * - sets the response status to STATUS_ERROR
	 * - sets the error message
	 * - sets the HTTP response code
	 *
	 * @param string $msg Error message returned in the JSON response.
	 * @param int $httpCode HTTP response code. Defaults to 200 for application errors,
	 *                      as the JSON response already contains the error status
	 *                      and message through the "result" and "msg" fields.
	 *                      HTTP 4xx/5xx codes can be used when the error must also
	 *                      be reported at the HTTP protocol level (for example REST
	 *                      clients, authentication errors, invalid requests or
	 *                      server failures).
	 *
	 * @return void
	 */
	public function setError($msg = '', $httpCode = 200)
	{

		if (!$this->isValidHttpErrorCode($httpCode)) {
			$httpCode = 200;
		}

		$this->result = self::STATUS_ERROR;
		$this->msg = $msg;
		$this->setHttpResponseCode($httpCode);
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
		]);
	}

	/**
	 * Set the response as a success.
	 *
	 * This method:
	 * - sets result to STATUS_SUCCESS
	 * - sets the response message
	 * - sets the HTTP response code
	 *
	 * @param string $msg Success message to return.
	 * @param int $httpCode HTTP status code (default: 200).
	 *
	 * @return void
	 */
	public function setSuccess($msg = '', $httpCode = 200)
	{
		if (!$this->isValidHttpSuccessCode($httpCode)) {
			$httpCode = 200;
		}

		$this->result = self::STATUS_SUCCESS;
		$this->msg = $msg;
		$this->setHttpResponseCode($httpCode);
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
		]);
	}

	/**
	 * Define the HTTP response code used when sending the JSON response.
	 *
	 * Allowed HTTP response codes:
	 *
	 * - 200 : OK
	 *   Standard successful request.
	 *
	 * - 201 : Created
	 *   Resource successfully created.
	 *
	 * - 202 : Accepted
	 *   Request accepted but processing is not completed yet.
	 *
	 * - 400 : Bad Request
	 *   Invalid request parameters or malformed request.
	 *
	 * - 401 : Unauthorized
	 *   Authentication is required or failed.
	 *
	 * - 403 : Forbidden
	 *   Access denied to the requested resource.
	 *
	 * - 404 : Not Found
	 *   Requested resource does not exist.
	 *
	 * - 405 : Method Not Allowed
	 *   HTTP method is not allowed for this endpoint.
	 *
	 * - 500 : Internal Server Error
	 *   Unexpected server-side error.
	 *
	 * - 501 : Not Implemented
	 *   Requested functionality is not implemented.
	 *
	 * - 503 : Service Unavailable
	 *   Service temporarily unavailable or under maintenance.
	 *
	 * @param int $httpCode HTTP response code.
	 *
	 * @return bool
	 *   Returns true if the response code is allowed and applied,
	 *   otherwise returns false.
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
	 * Send the JSON response to the client and stop script
	 *
	 * This method:
	 * - sends the JSON content-type header
	 * - applies the configured HTTP response code when the result is an error
	 * - outputs the JSON payload
	 * - terminates the current script execution
	 *
	 * @return void
	 */
	public function output()
	{
		if (!headers_sent()) {
			top_httphead('application/json');
			http_response_code($this->httpResponseCode);
		}

		print $this->getResponse();
		exit;
	}
}
