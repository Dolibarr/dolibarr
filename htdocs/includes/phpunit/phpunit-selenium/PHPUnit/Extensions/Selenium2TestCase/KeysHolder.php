<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2010-2011, Sebastian Bergmann <sb@sebastian-bergmann.de>.
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
 * @author     Ivan Kurnosov <zerkms@zerkms.com>
 * @copyright  2010-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 1.2.12
 */

/**
 * Class-mapper, that converts requested special key into correspondent Unicode character
 *
 * @package    PHPUnit_Selenium
 * @author     Ivan Kurnosov <zerkms@zerkms.com>
 * @copyright  2010-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.12
 * @see        http://code.google.com/p/selenium/wiki/JsonWireProtocol#/session/:sessionId/element/:id/value
 */
class PHPUnit_Extensions_Selenium2TestCase_KeysHolder
{
    private $_keys = array(
        'null'      => "\xEE\x80\x80",
        'cancel'    => "\xEE\x80\x81",
        'help'      => "\xEE\x80\x82",
        'backspace' => "\xEE\x80\x83",
        'tab'       => "\xEE\x80\x84",
        'clear'     => "\xEE\x80\x85",
        'return'    => "\xEE\x80\x86",
        'enter'     => "\xEE\x80\x87",
        'shift'     => "\xEE\x80\x88",
        'control'   => "\xEE\x80\x89",
        'alt'       => "\xEE\x80\x8A",
        'pause'     => "\xEE\x80\x8B",
        'escape'    => "\xEE\x80\x8C",
        'space'     => "\xEE\x80\x8D",
        'pageup'    => "\xEE\x80\x8E",
        'pagedown'  => "\xEE\x80\x8F",
        'end'       => "\xEE\x80\x90",
        'home'      => "\xEE\x80\x91",
        'left'      => "\xEE\x80\x92",
        'up'        => "\xEE\x80\x93",
        'right'     => "\xEE\x80\x94",
        'down'      => "\xEE\x80\x95",
        'insert'    => "\xEE\x80\x96",
        'delete'    => "\xEE\x80\x97",
        'semicolon' => "\xEE\x80\x98",
        'equals'    => "\xEE\x80\x99",
        'numpad0'   => "\xEE\x80\x9A",
        'numpad1'   => "\xEE\x80\x9B",
        'numpad2'   => "\xEE\x80\x9C",
        'numpad3'   => "\xEE\x80\x9D",
        'numpad4'   => "\xEE\x80\x9E",
        'numpad5'   => "\xEE\x80\x9F",
        'numpad6'   => "\xEE\x80\xA0",
        'numpad7'   => "\xEE\x80\xA1",
        'numpad8'   => "\xEE\x80\xA2",
        'numpad9'   => "\xEE\x80\xA3",
        'multiply'  => "\xEE\x80\xA4",
        'add'       => "\xEE\x80\xA5",
        'separator' => "\xEE\x80\xA6",
        'subtract'  => "\xEE\x80\xA7",
        'decimal'   => "\xEE\x80\xA8",
        'divide'    => "\xEE\x80\xA9",
        'f1'        => "\xEE\x80\xB1",
        'f2'        => "\xEE\x80\xB2",
        'f3'        => "\xEE\x80\xB3",
        'f4'        => "\xEE\x80\xB4",
        'f5'        => "\xEE\x80\xB5",
        'f6'        => "\xEE\x80\xB6",
        'f7'        => "\xEE\x80\xB7",
        'f8'        => "\xEE\x80\xB8",
        'f9'        => "\xEE\x80\xB9",
        'f10'       => "\xEE\x80\xBA",
        'f11'       => "\xEE\x80\xBB",
        'f12'       => "\xEE\x80\xBC",
        'command'   => "\xEE\x80\xBD",
    );

    public function specialKey($name)
    {
        $normalizedName = strtolower($name);

        if (!isset($this->_keys[$normalizedName])) {
            throw new PHPUnit_Extensions_Selenium2TestCase_Exception("There is no special key '$name' defined");
        }

        return $this->_keys[$normalizedName];
    }
}
