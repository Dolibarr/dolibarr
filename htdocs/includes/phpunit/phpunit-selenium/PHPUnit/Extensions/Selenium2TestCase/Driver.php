<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2010-2013, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <info@giorgiosironi.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 1.2.0
 */

/**
 * Driver for creating browser session with Selenium 2 (WebDriver API).
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <info@giorgiosironi.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.0
 */
class PHPUnit_Extensions_Selenium2TestCase_Driver
{
    private $seleniumServerUrl;
    private $seleniumServerRequestsTimeout;

    public function __construct(PHPUnit_Extensions_Selenium2TestCase_URL $seleniumServerUrl, $timeout = 60)
    {
        $this->seleniumServerUrl = $seleniumServerUrl;
        $this->seleniumServerRequestsTimeout = $timeout;
    }

    public function startSession(array $desiredCapabilities, PHPUnit_Extensions_Selenium2TestCase_URL $browserUrl)
    {
        $sessionCreation = $this->seleniumServerUrl->descend("/wd/hub/session");
        $response = $this->curl('POST', $sessionCreation, array(
            'desiredCapabilities' => $desiredCapabilities
        ));
        $sessionPrefix = $response->getURL();

        $timeouts = new PHPUnit_Extensions_Selenium2TestCase_Session_Timeouts(
            $this,
            $sessionPrefix->descend('timeouts'),
            $this->seleniumServerRequestsTimeout * 1000
        );
        return new PHPUnit_Extensions_Selenium2TestCase_Session(
            $this,
            $sessionPrefix,
            $browserUrl,
            $timeouts
        );
    }

    /**
     * Performs an HTTP request to the Selenium 2 server.
     *
     * @param string $method      'GET'|'POST'|'DELETE'|...
     * @param string $url
     * @param array $params       JSON parameters for POST requests
     */
    public function curl($http_method,
                         PHPUnit_Extensions_Selenium2TestCase_URL $url,
                         $params = NULL)
    {
        $curl = curl_init($url->getValue());
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->seleniumServerRequestsTimeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl,
                    CURLOPT_HTTPHEADER,
                    array(
                        'Content-type: application/json;charset=UTF-8',
                        'Accept: application/json;charset=UTF-8'
                     ));

        if ($http_method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, TRUE);
            if ($params && is_array($params)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, '');
            }
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        } else if ($http_method == 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $rawResponse = trim(curl_exec($curl));
        if (curl_errno($curl)) {
            throw new PHPUnit_Extensions_Selenium2TestCase_NoSeleniumException(
                'Error connection[' . curl_errno($curl) . '] to ' .
                $url->getValue()  . ': ' . curl_error($curl)
            );
        }
        $info = curl_getinfo($curl);
        if ($info['http_code'] == 0) {
            throw new PHPUnit_Extensions_Selenium2TestCase_NoSeleniumException();
        }
        if ($info['http_code'] == 404) {
            throw new BadMethodCallException("The command $url is not recognized by the server.");
        }
        if (($info['http_code'] >= 400) && ($info['http_code'] < 500)) {
            throw new BadMethodCallException("Something unexpected happened: '$rawResponse'");
        }
        curl_close($curl);
        $content = json_decode($rawResponse, TRUE);

        if ($content === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new PHPUnit_Extensions_Selenium2TestCase_Exception(
                sprintf(
                    "JSON decoding of remote response failed.\n".
                    "Error code: %d\n".
                    "The response: '%s'\n",
                    json_last_error(),
                    $rawResponse
                )
            );
        }

        $value = null;
        if (is_array($content) && array_key_exists('value', $content)) {
            $value = $content['value'];
        }

        $message = null;
        if (is_array($value) && array_key_exists('message', $value)) {
            $message = $value['message'];
        }

        $status = isset($content['status']) ? $content['status'] : 0;
        if ($status !== PHPUnit_Extensions_Selenium2TestCase_WebDriverException::Success) {
            throw new PHPUnit_Extensions_Selenium2TestCase_WebDriverException($message, $status);
        }

        return new PHPUnit_Extensions_Selenium2TestCase_Response($content, $info);
    }

    public function execute(PHPUnit_Extensions_Selenium2TestCase_Command $command)
    {
        return $this->curl($command->httpMethod(),
                           $command->url(),
                           $command->jsonParameters());
    }
}
