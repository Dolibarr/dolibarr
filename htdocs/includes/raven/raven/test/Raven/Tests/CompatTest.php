<?php

/*
 * This file is part of Raven.
 *
 * (c) Sentry Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Raven_Tests_CompatTest extends PHPUnit_Framework_TestCase
{
    public function test_gethostname()
    {
        $this->assertEquals(Raven_Compat::gethostname(), Raven_Compat::_gethostname());
        $this->assertTrue(strlen(Raven_Compat::_gethostname()) > 0);
    }

    public function test_hash_hmac()
    {
        $result = Raven_Compat::hash_hmac('sha1', 'foo', 'bar');
        $this->assertEquals('85d155c55ed286a300bd1cf124de08d87e914f3a', $result);

        $result = Raven_Compat::_hash_hmac('sha1', 'foo', 'bar');
        $this->assertEquals('85d155c55ed286a300bd1cf124de08d87e914f3a', $result);
    }

    public function test_json_encode()
    {
        $result = Raven_Compat::json_encode(array('foo' => array('bar' => 1)));
        $this->assertEquals('{"foo":{"bar":1}}', $result);

        $result = Raven_Compat::_json_encode(array('foo' => array('bar' => 1)));
        $this->assertEquals('{"foo":{"bar":1}}', $result);

        $result = Raven_Compat::_json_encode(array(1, 2, 3, 4, 'foo', 'bar'));
        $this->assertEquals('[1,2,3,4,"foo","bar"]', $result);

        $result = Raven_Compat::_json_encode(array(1, 'foo', 'foobar' => 'bar'));
        $this->assertEquals('{0:1,1:"foo","foobar":"bar"}', $result);

        $result = Raven_Compat::_json_encode(array(array()));
        $this->assertEquals('[[]]', $result);
    }
}
