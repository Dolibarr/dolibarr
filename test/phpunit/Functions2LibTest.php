<?php
/* Copyright (C) 2010-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023      Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024-2026	MDW                  <mdeweerd@users.noreply.github.com>
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
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/Functions2LibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/functions2.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (! defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (! defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (! defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (! defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (! defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (! defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (! defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no menu to show
}
if (! defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
}
if (! defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (! defined("NOLOGIN")) {
	define("NOLOGIN", '1');       // If this page is public (can be called outside logged session)
}


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class Functions2LibTest extends CommonClassTest
{
	/**
	 * Data provider for testJsUnEscape.
	 *
	 * Note: Tests the behavior of jsUnEscape() which mimics JavaScript's deprecated unescape() function.
	 * JavaScript unescape() reads exactly 4 hex digits after %u and 2 hex digits after %.
	 *
	 * @return array<int, array{0: string, 1: string}>
	 */
	public static function unescapeProvider(): array
	{
		return [
			// Original Mixed %uXXXX only test
			['%u03BD%u03B5%u03BF','νεο'],

			// Mixed %uXXXX and raw UTF-8
			['Hello %u0041 ❤ %u2764', 'Hello A ❤ ❤'],

			// %u1F600 is decoded as %u1F60 (ὠ) + 0 (js unescape only reads 4 hex digits after %u)
			['Café %u1F600 😊 %u2764', 'Café ὠ0 😊 ❤'],

			// %F0%9F%8C%88 is decoded as individual bytes that form 🌈 in UTF-8
			// %u1F308 is decoded as %u1F30 (ι with tonos) + 8 (js unescape only reads 4 hex digits after %u)
			['%F0%9F%8C%88 %u1F308', "\xF0\x9F\x8C\x88 \xE1\xBC\xB0\x38"],

			// %u20AC is correctly decoded to €, but %E2%82%AC is also decoded to € (bytes form UTF-8)
			['Price: %u20AC100 or %E2%82%AC100', 'Price: €100 or ' . chr(0xE2) . chr(0x82) . chr(0xAC) . '100'],

			// %u26A1 is correct, but %F0%9F%8C%88 is decoded as individual bytes
			['%u26A1 %F0%9F%8C%88 %u2194', '⚡ ' . chr(0xF0) . chr(0x9F) . chr(0x8C) . chr(0x88) . ' ↔'],

			// %u0024 is correctly decoded to $
			['%u0024100', '$100'],

			// %20 is correctly decoded to space
			['%20', ' '],

			// %u0020 is correctly decoded to space
			['%u0020', ' ']
		];
	}

	/**
	 * Test jsUnEscape function.
	 * @dataProvider unescapeProvider
	 * @param string $input The input string to test.
	 * @param string $expected The expected result.
	 * @return void
	 */
	public function testJsUnEscape($input, $expected)
	{
		$result = jsUnEscape($input);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, $expected);
	}

	/**
	 * testIsValidMailDomain
	 *
	 * @return void
	 */
	public function testIsValidMailDomain()
	{
		print __METHOD__."\n";

		$mail = 'bidon@invalid.invalid';
		$result = isValidMailDomain($mail);
		$this->assertEquals(0, $result, 'Email isValidMailDomain('.$mail.') should return 0 (not valid) but returned '.$result);

		$mail = 'bidon@dolibarr.org';
		$result = isValidMailDomain($mail);
		$this->assertEquals(1, $result, 'Email isValidMailDomain('.$mail.') should return 1 (valid) but returned '.$result);
	}

	/**
	 * testIsValidURL
	 *
	 * @return	void
	 */
	public function testIsValidUrl()
	{
		print __METHOD__."\n";

		//Simple check
		$result = isValidUrl('http://google.com');
		$this->assertEquals(1, $result);

		$result = isValidUrl('goo=gle');	// This is good, it might be an alias of hostname
		$this->assertEquals(1, $result);

		//With scheme check
		$result = isValidUrl('http://www.google.com', 1);
		$this->assertEquals(1, $result);

		$result = isValidUrl('ftp://www.google.com', 1);
		$this->assertEquals(0, $result);

		//With password check invalid. This test should be ko but currently it is not
		//$result = isValidUrl('http://user:password@http://www.google.com', 1, 1);
		//$this->assertEquals(0, $result);

		//With password check valid
		$result = isValidUrl('http://user:password@www.google.com', 1, 1);
		$this->assertEquals(1, $result);

		$result = isValidUrl('http://www.google.com', 1, 1);
		$this->assertEquals(0, $result);

		//With port check
		$result = isValidUrl('http://google.com:8080', 0, 0, 1);
		$this->assertEquals(1, $result);

		$result = isValidUrl('http://google.com', 0, 0, 1);
		$this->assertEquals(0, $result);

		//With path check
		$result = isValidUrl('http://google.com/search', 0, 0, 0, 1);
		$this->assertEquals(1, $result);

		$result = isValidUrl('http://google.com', 0, 0, 0, 0);
		$this->assertEquals(1, $result);

		//With query check
		$result = isValidUrl('http://google.com/search?test=test', 0, 0, 0, 0, 1);
		$this->assertEquals(1, $result);

		//With query check
		$result = isValidUrl('http://google.com?test=test', 0, 0, 0, 0, 1);
		$this->assertEquals(1, $result);

		$result = isValidUrl('http://google.com', 0, 0, 0, 0, 1);
		$this->assertEquals(0, $result);

		//With anchor check
		$result = isValidUrl('http://google.com/search#done', 0, 0, 0, 0, 0, 1);
		$this->assertEquals(1, $result);

		$result = isValidUrl('http://google.com/search', 0, 0, 0, 0, 0, 1);
		$this->assertEquals(0, $result);
	}

	/**
	 * testIsIP
	 *
	 * @return	void
	 */
	public function testIsIP()
	{
		// Not valid
		$ip = 'a299.299.299.299';
		$result = is_ip($ip);
		print __METHOD__." for ".$ip." result=".$result."\n";
		$this->assertEquals(0, $result, $ip);

		// Reserved IP range (not checked by is_ip function)
		$ip = '169.254.0.0';
		$result = is_ip($ip);
		print __METHOD__." for ".$ip." result=".$result."\n";
		//$this->assertEquals(2,$result,$ip);      // Assertion disabled because returned value differs between PHP patch version

		$ip = '1.2.3.4';
		$result = is_ip($ip);
		print __METHOD__." for ".$ip." result=".$result."\n";
		$this->assertEquals(1, $result, $ip);

		// Private IP ranges
		$ip = '10.0.0.0';
		$result = is_ip($ip);
		print __METHOD__." for ".$ip." result=".$result."\n";
		$this->assertEquals(2, $result, $ip);

		$ip = '172.16.0.0';
		$result = is_ip($ip);
		print __METHOD__." for ".$ip." result=".$result."\n";
		$this->assertEquals(2, $result, $ip);

		$ip = '192.168.0.0';
		$result = is_ip($ip);
		print __METHOD__." for ".$ip." result=".$result."\n";
		$this->assertEquals(2, $result, $ip);
	}


	/**
	 * Dataprovider for testGetStringBetween
	 *
	 * @return array<string,string[]}
	 */
	public function stringBetweenDataProvider()
	{
		return [
			// string, start, end, expected
			'matches' => [ "STARTcontentEND", "START", "END", "content"],
			'start does not match' => [ "ScontentEND", "START", "END", ""],
			'end does not match' => [ "STARTcontentN", "START", "END", ""],
			'no match' => [ "content", "START", "END", ""],
			'end before start' => [ "ENDcontentSTART", "START", "END", ""],
			'end inside start' => [ "BAB", "BA", "AB", ""],
			'multiple matches' => [ "BAcontentABBAdoneAB", "BA", "AB", "content"],
		];
	}


	/**
	 * Test get_string_between()
	 *
	 * @param string $string String to search in.
	 * @param string $start String indicating start
	 * @param string $end String indicating end
	 * @param string $expected Expected result
	 *
	 * @return void
	 *
	 * @dataProvider stringBetweenDataProvider
	 */
	public function testGetStringBetween($string, $start, $end, $expected)
	{
		$this->assertEquals($expected, get_string_between($string, $start, $end));
	}


	/**
	 * Dataprovider for numero_semaine
	 *
	 * @return array<string,array{0:string,1:string}
	 */
	public function numeroSemaineDataProvider()
	{
		return [
			// time_str, expected week
			'day 1 - 1977' => [ "1977/1/1 10:10:10", '53'],
			'day 2 - 1977' => [ "1977/1/1 10:10:10", '53'],
			'last day - 1977' => [ "1977/12/31 10:10:10", '52'],
			'day 1 - 1978' => [ "1978/1/1 10:10:10", '52'],
			'day 2 - 1978' => [ "1978/1/2 10:10:10", '01'],
			'day 1 - 1981' => [ "1981/1/1 10:10:10", '01'],
			'last day - 1981' => [ "1981/12/31 10:10:10", '53'],
			'day 1 - 1982' => [ "1982/1/1 10:10:10", '53'],
			'day 3 - 1982' => [ "1982/1/3 10:10:10", '53'],
			'day 4 - 1982' => [ "1982/1/4 10:10:10", '01'],
		];
	}


	/**
	 * Test numero_semaine()
	 *
	 * @param string $time_str Time (string) to test
	 * @param int    $expected_week Week expected
	 *
	 * @return void
	 *
	 * @dataProvider numeroSemaineDataProvider
	 */
	public function testNumeroSemaine($time_str, $expected_week)
	{
		$time = strtotime($time_str);
		$str = date(DATE_ATOM, $time).PHP_EOL;
		print __METHOD__." time=".$time."\n";
		$this->assertEquals($expected_week, numero_semaine($time), "Computed week incorrect for $str");
	}


	/**
	 * Test testRemoveEmoji
	 *
	 * @return void
	 */
	public function testRemoveEmoji()
	{
		print __METHOD__."\n";

		$text = 'abc ✅ def';
		$result = removeEmoji($text, 0);
		$this->assertEquals('abc  def', $result, 'testRemoveEmoji 0');

		$text = 'abc ✅ def';
		$result = removeEmoji($text, 1);
		$this->assertEquals('abc  def', $result, 'testRemoveEmoji 1');

		$text = 'abc ✅ def';
		$result = removeEmoji($text, 2);
		$this->assertEquals($text, $result, 'testRemoveEmoji 2');
	}
}
