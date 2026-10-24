<?php
/* Copyright (C) 2010-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2023       Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/XCalLibTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql'); // This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/xcal.lib.php';

if (empty($user->id) && is_object($db)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class XCalLibTest extends CommonClassTest
{
	/**
	 * testBuildRssfile: the RSS file is well-formed XML whatever the texts and urls of the events
	 *
	 * @return  void
	 */
	public function testBuildRssfile()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$langs->charset_output = 'UTF-8';
		$summary = 'Title ]]> <script>alert(1)</script> & "q" \u{e9}';
		$description = 'desc ]]> more & <b>x</b>';
		$url = 'https://example.org/a?b=1&c=2&d="x"';
		$events = array(
			'r1' => array('uid' => '12', 'startdate' => 1792000000, 'summary' => $summary, 'desc' => $description, 'url' => $url,
				'author' => 'me', 'category' => 'c]]>d', 'image' => 'https://example.org/i.png?a=1&b="2"'),
		);
		$file = tempnam(sys_get_temp_dir(), 'xcal');
		$result = build_rssfile('rss', 'R&D <agenda>', 'D', $events, $file, '', 'https://example.org/feed?a=1&b=2', 'fr');
		$this->assertEquals(1, $result);

		$raw = (string) file_get_contents($file);
		unlink($file);

		$xml = new DOMDocument();
		libxml_use_internal_errors(true);
		$this->assertTrue($xml->loadXML($raw), 'The RSS file is not well-formed: '.print_r(libxml_get_errors(), true));
		libxml_clear_errors();

		$this->assertEquals(0, $xml->getElementsByTagName('script')->length);
		$this->assertEquals(1, $xml->getElementsByTagName('item')->length);
		$item = $xml->getElementsByTagName('item')->item(0);
		$this->assertInstanceOf(DOMElement::class, $item);
		$this->assertEquals('R&D <agenda>', $xml->getElementsByTagName('title')->item(0)->textContent);
		$this->assertEquals($summary, $item->getElementsByTagName('title')->item(0)->textContent);
		$this->assertEquals('c]]>d', $item->getElementsByTagName('category')->item(0)->textContent);
		$this->assertEquals($url, $item->getElementsByTagName('link')->item(0)->textContent);
		$this->assertEquals($url, $item->getElementsByTagName('source')->item(0)->getAttribute('url'));
		$this->assertStringContainsString('desc ]]> more & x', $item->getElementsByTagName('description')->item(0)->textContent);
		$this->assertStringContainsString('src="https://example.org/i.png?a=1&amp;b=&quot;2&quot;"', $item->getElementsByTagName('description')->item(0)->textContent);
	}

	/**
	 * testEscapeXml
	 *
	 * @return  void
	 */
	public function testEscapeXml()
	{
		$this->assertEquals('A &amp; B &lt;c&gt; &quot;d&quot; &apos;e&apos; \u{e9}', dol_escape_xml('A & B <c> "d" \'e\' \u{e9}'));
		$this->assertEquals('ab', dol_escape_xml("a\x0Bb"));
	}

	/**
	 * testQuotedPrintEncodeDecode
	 *
	 * @return  void
	 */
	public function testQuotedPrintEncodeDecode()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$stringtoencode = 'ABCD=1234;';
		$result = quotedPrintEncode($stringtoencode);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('ABCD=3D1234;', $result);

		$resultback = quotedPrintDecode($result);
		print __METHOD__." result=".$resultback."\n";
		$this->assertEquals($stringtoencode, $resultback);
	}
}
