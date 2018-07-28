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
 * @author     Jonathan Lipps <jlipps@gmail.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 1.2.0
 */

/**
 * Gets or posts an attribute from/to the session (title, alert text, etc.)
 *
 * @package    PHPUnit_Selenium
 * @author     Jonathan Lipps <jlipps@gmail.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.9
 */

if (defined('SAUCE_USERNAME') && defined('SAUCE_ACCESS_KEY')) {
    define('SAUCE_HOST', constant('SAUCE_USERNAME') . ':' . constant('SAUCE_ACCESS_KEY') . '@ondemand.saucelabs.com');
} else {
    define('SAUCE_HOST', '');
}

class Tests_Selenium2TestCase_MobileFeaturesTest extends PHPUnit_Extensions_Selenium2TestCase
{
    public static $browsers = array(
        array(
            'host' => SAUCE_HOST,
            'port' => 80,
            'sessionStrategy' => 'isolated',
            'browserName' => 'Android',
            'desiredCapabilities' => array(
                'version' => '4',
                'platform' => 'Linux'
            )
        ),
        array(
            'host' => SAUCE_HOST,
            'port' => 80,
            'sessionStrategy' => 'isolated',
            'browserName' => 'iPhone',
            'desiredCapabilities' => array(
                'version' => '5',
                'platform' => 'Mac 10.6'
            )
        )
    );

    public function setUp()
    {
        if (!defined('SAUCE_ACCESS_KEY') || !defined('SAUCE_USERNAME')) {
            $this->markTestSkipped("SAUCE_USERNAME and SAUCE_ACCESS_KEY must be set to run tests on Sauce");
        } elseif ($this->getBrowser() == 'iPhone') {
            $this->markTestSkipped('iPhone does not yet support touch interactions');
        } elseif ($this->getName() == "testLocation") {
            $this->markTestSkipped('Mobile drivers don\'t yet reliably support location');
        } else {
            $caps = $this->getDesiredCapabilities();
            $caps['name'] = get_called_class() . '::' . $this->getName();
            $this->setDesiredCapabilities($caps);
            $this->setBrowserUrl('http://saucelabs.com/test/guinea-pig');
        }
    }

    public function testMove()
    {
        $this->url('/');
        $this->touchMove(array('x' => 100, 'y' => 100));
    }

    public function testGeneralScroll()
    {
        $this->url('/');
        $this->touchScroll(array('xoffset' => 0, 'yoffset' => 100));
    }

    public function testTouchDownUp()
    {
        $this->url('/');
        $this->touchDown(array('x' => 100, 'y' => 100));
        $this->touchUp(array('x' => 100, 'y' => 100));
    }

    public function testGeneralFlick()
    {
        $this->url('/');
        $this->flick(array('ySpeed' => -20));
    }

    public function testTap()
    {
        $this->url('/');
        $this->byId('i am a link')->tap();
        $this->assertContains("I am another page title", $this->title());
    }

    public function testElementScroll()
    {
        $this->url('/');
        $this->byId('i_am_a_textbox')->scroll(array('yoffset' => 50, 'xoffset' => 0));
    }

    public function testElementFlick()
    {
        $this->url('/');
        $this->byId('i_am_a_textbox')->flick(array('yoffset' => 50, 'speed' => 10, 'xoffset' => 0));
    }

    public function testDoubleTap()
    {
        $this->url('/');
        $this->byId('i_am_an_id')->doubletap();
    }

    public function testLongTap()
    {
        $this->url('/');
        $this->byId('i am a link')->longtap();
    }

    public function testLocation()
    {
        $this->url('/');
        $this->location(array('latitude' => 35.5, 'longitude' => 17.6, 'altitude' => 50));
        $location = $this->location();
        $this->assertEquals($location['latitude'], 35.5);
    }

    public function testOrientation()
    {
        $this->url('/');
        $this->landscape();
        $this->assertEquals($this->orientation(), 'LANDSCAPE');
        $this->portrait();
        $this->assertEquals($this->orientation(), 'PORTRAIT');
    }
}

