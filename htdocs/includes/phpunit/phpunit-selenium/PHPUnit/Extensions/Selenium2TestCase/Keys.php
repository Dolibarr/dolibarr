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
 * Class to hold the special keys Unicode entities
 *
 * @package    PHPUnit_Selenium
 * @author     Ivan Kurnosov <zerkms@zerkms.com>
 * @copyright  2010-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.3.0
 * @see        http://code.google.com/p/selenium/wiki/JsonWireProtocol#/session/:sessionId/element/:id/value
 */
class PHPUnit_Extensions_Selenium2TestCase_Keys
{
    const NULL      = "\xEE\x80\x80";
    const CANCEL    = "\xEE\x80\x81";
    const HELP      = "\xEE\x80\x82";
    const BACKSPACE = "\xEE\x80\x83";
    const TAB       = "\xEE\x80\x84";
    const CLEAR     = "\xEE\x80\x85";
    const RETURN_   = "\xEE\x80\x86";
    const ENTER     = "\xEE\x80\x87";
    const SHIFT     = "\xEE\x80\x88";
    const CONTROL   = "\xEE\x80\x89";
    const ALT       = "\xEE\x80\x8A";
    const PAUSE     = "\xEE\x80\x8B";
    const ESCAPE    = "\xEE\x80\x8C";
    const SPACE     = "\xEE\x80\x8D";
    const PAGEUP    = "\xEE\x80\x8E";
    const PAGEDOWN  = "\xEE\x80\x8F";
    const END       = "\xEE\x80\x90";
    const HOME      = "\xEE\x80\x91";
    const LEFT      = "\xEE\x80\x92";
    const UP        = "\xEE\x80\x93";
    const RIGHT     = "\xEE\x80\x94";
    const DOWN      = "\xEE\x80\x95";
    const INSERT    = "\xEE\x80\x96";
    const DELETE    = "\xEE\x80\x97";
    const SEMICOLON = "\xEE\x80\x98";
    const EQUALS    = "\xEE\x80\x99";
    const NUMPAD0   = "\xEE\x80\x9A";
    const NUMPAD1   = "\xEE\x80\x9B";
    const NUMPAD2   = "\xEE\x80\x9C";
    const NUMPAD3   = "\xEE\x80\x9D";
    const NUMPAD4   = "\xEE\x80\x9E";
    const NUMPAD5   = "\xEE\x80\x9F";
    const NUMPAD6   = "\xEE\x80\xA0";
    const NUMPAD7   = "\xEE\x80\xA1";
    const NUMPAD8   = "\xEE\x80\xA2";
    const NUMPAD9   = "\xEE\x80\xA3";
    const MULTIPLY  = "\xEE\x80\xA4";
    const ADD       = "\xEE\x80\xA5";
    const SEPARATOR = "\xEE\x80\xA6";
    const SUBTRACT  = "\xEE\x80\xA7";
    const DECIMAL   = "\xEE\x80\xA8";
    const DIVIDE    = "\xEE\x80\xA9";
    const F1        = "\xEE\x80\xB1";
    const F2        = "\xEE\x80\xB2";
    const F3        = "\xEE\x80\xB3";
    const F4        = "\xEE\x80\xB4";
    const F5        = "\xEE\x80\xB5";
    const F6        = "\xEE\x80\xB6";
    const F7        = "\xEE\x80\xB7";
    const F8        = "\xEE\x80\xB8";
    const F9        = "\xEE\x80\xB9";
    const F10       = "\xEE\x80\xBA";
    const F11       = "\xEE\x80\xBB";
    const F12       = "\xEE\x80\xBC";
    const COMMAND   = "\xEE\x80\xBD";
}
