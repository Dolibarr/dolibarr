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
 * @since      File available since Release 1.2.6
 */

/**
 * Adds and remove cookies.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <info@giorgiosironi.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.6
 */
class PHPUnit_Extensions_Selenium2TestCase_Session_Cookie
{
    private $driver;
    private $url;

    public function __construct(PHPUnit_Extensions_Selenium2TestCase_Driver $driver,
                                PHPUnit_Extensions_Selenium2TestCase_URL $url)
    {
        $this->driver = $driver;
        $this->url = $url;
    }

    /**
     * @param string $name
     * @param string $value
     * @return PHPUnit_Extensions_Selenium2TestCase_Session_Cookie_Builder
     */
    public function add($name, $value)
    {
        return new PHPUnit_Extensions_Selenium2TestCase_Session_Cookie_Builder($this, $name, $value);
    }

    /**
     * @param string $name
     * @return string
     */
    public function get($name)
    {
        $cookies = $this->driver->curl('GET', $this->url)->getValue();
        foreach ($cookies as $cookie) {
            if ($cookie['name'] == $name) {
                return $cookie['value'];
            }
        }
        throw new PHPUnit_Extensions_Selenium2TestCase_Exception("There is no '$name' cookie available on this page.");
    }

    /**
     * @param string $name
     * @return void
     */
    public function remove($name)
    {
        $url = $this->url->descend($name);
        $this->driver->curl('DELETE', $url);
    }

    /**
     * @return void
     */
    public function clear()
    {
        $this->driver->curl('DELETE', $this->url);
    }

    /**
     * @internal
     * @param array $data
     * @return void
     */
    public function postCookie(array $data)
    {
        $this->driver->curl('POST',
                            $this->url,
                            array(
                                'cookie' => $data
                            ));
    }
}
