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
 * @author     Andrew Krasichkov <krasichkovandrew@gmail.com>
 * @copyright  2010-2013 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 */

/**
 * Tests for session::log() and session::logTypes() commands.
 *
 * @package    PHPUnit_Selenium
 * @author     Andrew Krasichkov <krasichkovandrew@gmail.com>
 * @copyright  2010-2013 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 */
class Tests_Selenium2TestCase_LogTest extends Tests_Selenium2TestCase_BaseTestCase
{
    private static $expectedLogTypes = array(
        'default' => array(
            'browser'
        ),
        'firefox' => array(
            'browser',
            'driver',
            'client',
            'server'
        ),
        'chrome' => array(
            'browser',
            'client',
            'server'
        )
    );

    private static $jsInlineErrors = array(
        'default' => '',
        'chrome' => 'html/test_log.html 4 Uncaught TypeError: Cannot read property \'inlineError\' of null',
        'firefox' => 'TypeError: null has no properties'
    );

    public function testLogType()
    {
        $this->markTestSkipped('Unsupported command');

        $actual = $this->logTypes();
        $expected = $this->getDataArray(self::$expectedLogTypes, $this->getBrowser());
        $diff = array_diff($expected, $actual);
        $this->assertEmpty($diff, 'Some log types not presented by browser: ' . var_export($diff, TRUE));
    }

    public function testLog()
    {
        $this->markTestSkipped('Unsupported command');

        $this->url('html/test_log.html');
        $actual = $this->log('browser');
        $actual = $this->getLogsMessages($actual);
        if($this->getBrowser() == 'chrome') {
            $expected = $this->getBrowserUrl();

        } else {
            $expected = '';
        }
        $expected .= $this->getDataArray(self::$jsInlineErrors, $this->getBrowser());
        $this->assertContains($expected, $actual);
    }

    private function getDataArray(array $array, $key)
    {
        if(isset($array[$key])) {
            return $array[$key];
        }
        else {
            return $array['default'];
        }
    }

    private function getLogsMessages($logs, $level = 'SEVERE')
    {
        $result = array();
        foreach($logs as $log) {
            if(isset($log['message']) && isset($log['level']) && $log['level'] == $level) {
                $result[] = $log['message'];
            }
        }
        return $result;
    }
}
