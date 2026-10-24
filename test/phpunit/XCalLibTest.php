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

	/**
	 * testBuildCalfileIcal: the iCal file is valid (CRLF, escaped texts, lines of 75 octets at most, no empty property)
	 *
	 * @return  void
	 */
	public function testBuildCalfileIcal()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$conf->global->MAIN_AGENDA_EXPORT_CACHE = 3600;
		$noemail = new stdClass();
		$noemail->email = '';
		$description = "C:\\dir\\new\\table\nLine 2 with accents \u{e9}\u{e0}\u{fc} and an emoji \u{1F600} ".trim(str_repeat("word \u{e9} ", 30));
		$events = array(
			'e1' => array('uid' => 'u1', 'type' => 'event', 'startdate' => 1792000000, 'duration' => 3600, 'enddate' => 1792003600, 'summary' => "Meeting, point; budget \\ ok",
				'category' => 'Clients, VIP', 'priority' => 0, 'fulldayevent' => 0, 'location' => "Room A, floor 2", 'email' => 'a@b.fr', 'url' => "https://x.fr/a\r\nX-INJECT:yes",
				'transparency' => 'OPAQUE', 'desc' => $description, 'created' => 1790000000, 'modified' => 1790000100, 'assignedUsers' => array($noemail)),
			'j1' => array('uid' => 'u2', 'type' => 'journal', 'startdate' => 1791000000, 'duration' => 0, 'enddate' => 0, 'summary' => 'Note', 'category' => '', 'priority' => 0,
				'fulldayevent' => 0, 'location' => '', 'email' => '', 'url' => '', 'transparency' => '', 'desc' => 'x', 'created' => 0, 'modified' => 0),
		);
		$file = tempnam(sys_get_temp_dir(), 'xcal');
		$result = build_calfile('ical', 'Agenda, test', 'Desc; x', $events, $file);
		$this->assertEquals(2, $result);

		$raw = (string) file_get_contents($file);
		unlink($file);

		$this->assertSame(0, preg_match('/(?<!\r)\n/', $raw), 'A line does not end with CRLF');
		$this->assertSame("\r\n", substr($raw, -2));
		foreach (explode("\r\n", $raw) as $line) {
			$this->assertLessThanOrEqual(75, strlen($line));
			$this->assertSame(1, preg_match('//u', $line), 'A line is not valid UTF-8: '.$line);
		}
		$this->assertStringContainsString("\r\nX-PUBLISHED-TTL:PT1H0M0S\r\n", $raw);
		$this->assertStringNotContainsString("\r\nX-INJECT", $raw);
		$this->assertStringNotContainsString("ATTENDEE", $raw);
		$this->assertStringNotContainsString("\r\nCATEGORIES:\r\n", $raw);
		$this->assertStringNotContainsString("\r\nLOCATION:\r\n", $raw);

		// Read it back with a real parser
		include_once DOL_DOCUMENT_ROOT.'/includes/sabre/autoload.php';
		$calendar = \Sabre\VObject\Reader::read($raw);
		$this->assertCount(0, $calendar->validate());
		$vevent = $calendar->VEVENT;
		$this->assertEquals("Meeting, point; budget \\ ok", (string) $vevent->SUMMARY);
		$this->assertEquals($description, (string) $vevent->DESCRIPTION);
		$this->assertEquals("Room A, floor 2", (string) $vevent->LOCATION);
		$this->assertEquals(array('Clients, VIP'), $vevent->CATEGORIES->getParts());
	}
}
