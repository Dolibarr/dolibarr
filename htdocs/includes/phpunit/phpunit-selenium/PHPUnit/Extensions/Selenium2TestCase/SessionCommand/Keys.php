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
 * @author     Christian Soronellas <csoronellas@emagister.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 1.2.0
 */

/**
 * Gets or sets the current URL of the window.
 *
 * @package    PHPUnit_Selenium
 * @author     Christian Soronellas <csoronellas@emagister.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.0
 */
class PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Keys
    extends PHPUnit_Extensions_Selenium2TestCase_Command
{
    public function __construct($jsonParameters,
                                PHPUnit_Extensions_Selenium2TestCase_URL $url)
    {
        if ($jsonParameters === NULL) {
            parent::__construct(NULL, $url);
        } else {
            $jsonParameters = $this->keysForText($jsonParameters);
            parent::__construct($jsonParameters, $url);
        }
    }

    /**
     * @return string
     */
    public function httpMethod()
    {
        return 'POST';
    }

    /**
     * Given a string returns an array of the characters that compose the string
     *
     * @param string $text
     * @throws InvalidArgumentException
     * @return array
     */
    public function keysForText($text)
    {
        if (is_scalar($text)) {
            return array('value' => preg_split('//u', (string) $text, -1, PREG_SPLIT_NO_EMPTY));
        }
        if (is_array($text)) {
            return $text;
        }
        throw new InvalidArgumentException('The "text" argument should be a string or an array of special characters!');
    }
}
