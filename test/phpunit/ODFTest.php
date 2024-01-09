<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 - Thomas Negre - contact@open-dsi.fr
 * Copyright (C) 2023      Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    test/unit/ODFTest.php
 * \ingroup odf
 * \brief   PHPUnit test for odf class.
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/includes/odtphp/odf.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS=1;

$langs->load("main");


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class ODFTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @param 	string	$name		Name
	 * @return ODFTest
	 */
	public function __construct($name = '')
	{
		parent::__construct($name);

		//$this->sharedFixture
		global $conf,$user,$langs,$db;
		$this->savconf=$conf;
		$this->savuser=$user;
		$this->savlangs=$langs;
		$this->savdb=$db;

		print __METHOD__." db->type=".$db->type." user->id=".$user->id;
		//print " - db ".$db->db;
		print "\n";
	}

	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf,$user,$langs,$db;
		$db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

		print __METHOD__."\n";
	}

	/**
	 * tearDownAfterClass
	 *
	 * @return	void
	 */
	public static function tearDownAfterClass(): void
	{
		global $conf,$user,$langs,$db;
		$db->rollback();

		print __METHOD__."\n";
	}

	/**
	 * Init phpunit tests
	 *
	 * @return  void
	 */
	protected function setUp(): void
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		print __METHOD__."\n";
	}

	/**
	 * End phpunit tests
	 *
	 * @return  void
	 */
	protected function tearDown(): void
	{
		print __METHOD__."\n";
	}

	/**
	 * test ODF convertVarToOdf
	 *
	 * @return int
	 */
	public function testODFconvertVarToOdf()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		// we test using template_invoice, it does not matter, we just need a valid odt.
		$filename = '../../htdocs/install/doctemplates/invoices/template_invoice.odt';
		$config = [
			'PATH_TO_TMP'  => "/tmp",
			'ZIP_PROXY' => "PclZipProxy",
			'DELIMITER_LEFT' => "{",
			'DELIMITER_RIGHT' => "}",
		];

		$to_test = [
			/** No HTML **/
			// Simple strings
			1 => [
				'to_convert' => 'Simple string',
				'encode' => true,
				'charset' => null,
				'expected' => 'Simple string',
			],
			2 => [
				'to_convert' => 'Simple string',
				'encode' => false,
				'charset' => null,
				'expected' => 'Simple string',
			],
			3 => [
				'to_convert' => "Simple string\nwith line break",
				'encode' => true,
				'charset' => null,
				'expected' => "Simple string<text:line-break/>with line break",
			],
			4 => [
				'to_convert' => "Simple string\nwith line break",
				'encode' => false,
				'charset' => null,
				'expected' => "Simple string<text:line-break/>with line break",
			],
			// Special chars
			5 => [
				'to_convert' => 'One&two',
				'encode' => true,
				'charset' => null,
				'expected' => 'One&amp;two',
			],
			6 => [
				'to_convert' => 'One&two',
				'encode' => false,
				'charset' => null,
				'expected' => 'One&two',
			],
			7 => [
				'to_convert' => "/a&él'èàüöç€Ğ~<>",
				'encode' => true,
				'charset' => null,
				'expected' => mb_convert_encoding("/a&amp;él&apos;èàüöç€Ğ~&lt;&gt;", 'UTF-8', 'ISO-8859-1'),
			],
			8 => [
				'to_convert' => "/a&él'èàüöç€Ğ~<>",
				'encode' => false,
				'charset' => null,
				'expected' => mb_convert_encoding("/a&él'èàüöç€Ğ~<>", 'UTF-8', 'ISO-8859-1'),
			],
			// special chars with non-default charset
			9 => [
				'to_convert' => "/a&él'èàüöç€Ğ~<>",
				'encode' => true,
				'charset' => 'UTF-16',
				'expected' => "/a&amp;él&apos;èàüöç€Ğ~&lt;&gt;",
			],
			10 => [
				'to_convert' => "/a&él'èàüöç€Ğ~<>",
				'encode' => false,
				'charset' => 'UTF-16', // When the charset differs from ISO-8859 string is not converted.
				'expected' => "/a&él'èàüöç€Ğ~<>",
			],
			11 => [
				'to_convert' => "Greater > than",
				'encode' => true,
				'charset' => null,
				'expected' => mb_convert_encoding("Greater &gt; than", 'UTF-8', 'ISO-8859-1'),
			],
			12 => [
				'to_convert' => "Greater > than",
				'encode' => false,
				'charset' => null,
				'expected' => mb_convert_encoding("Greater > than", 'UTF-8', 'ISO-8859-1'),
			],
			13 => [
				'to_convert' => "Smaller < than",
				'encode' => true,
				'charset' => null,
				'expected' => mb_convert_encoding("Smaller &lt; than", 'UTF-8', 'ISO-8859-1'),
			],
			14 => [
				'to_convert' => "Smaller < than",
				'encode' => false,
				'charset' => null,
				'expected' => mb_convert_encoding("Smaller < than", 'UTF-8', 'ISO-8859-1'),
			],
			/** HTML **/
			// break lines
			15 => [
				'to_convert' => "Break<br>line",
				'encode' => true,
				'charset' => null,
				'expected' => mb_convert_encoding("Break<text:line-break/>line", 'UTF-8', 'ISO-8859-1'),
			],
			16 => [
				'to_convert' => "Break<br>line",
				'encode' => false,
				'charset' => null,
				'expected' => mb_convert_encoding("Break<text:line-break/>line", 'UTF-8', 'ISO-8859-1'),
			],
			17 => [
				'to_convert' => "Break<br />line",
				'encode' => true,
				'charset' => null,
				'expected' => mb_convert_encoding("Break<text:line-break/>line", 'UTF-8', 'ISO-8859-1'),
			],
			18 => [
				'to_convert' => "Break<br />line",
				'encode' => false,
				'charset' => null,
				'expected' => mb_convert_encoding("Break<text:line-break/>line", 'UTF-8', 'ISO-8859-1'),
			],
			// HTML tags
			19 => [
				'to_convert' => "text with <strong>strong, </strong><em>emphasis</em> and <u>underlined</u> words with <i>it@lic sp&ciàlchärs éè l'</i>",
				'encode' => false,
				'charset' => 'UTF-8',
				'expected' => 'text with <text:span text:style-name="boldText">strong, </text:span><text:span text:style-name="italicText">emphasis</text:span> and <text:span text:style-name="underlineText">underlined</text:span> words with <text:span text:style-name="italicText">it@lic sp&ciàlchärs éè l\'</text:span>',
			],
			20 => [
				'to_convert' => "text with <strong>strong, </strong><em>emphasis</em> and <u>underlined</u> words with <i>it@lic sp&ciàlchärs éè l'</i>",
				'encode' => true,
				'charset' => 'UTF-8',
				'expected' => 'text with <text:span text:style-name="boldText">strong, </text:span><text:span text:style-name="italicText">emphasis</text:span> and <text:span text:style-name="underlineText">underlined</text:span> words with <text:span text:style-name="italicText">it@lic sp&amp;ciàlchärs éè l&apos;</text:span>',
			],
			21 => [
				'to_convert' => "text with <strong>strong, </strong><em>emphasis</em> and <u>underlined</u> words with <i>it@lic sp&ciàlchärs éè l'</i>",
				'encode' => false,
				'charset' => null,
				'expected' => mb_convert_encoding('text with <text:span text:style-name="boldText">strong, </text:span><text:span text:style-name="italicText">emphasis</text:span> and <text:span text:style-name="underlineText">underlined</text:span> words with <text:span text:style-name="italicText">it@lic sp&ciàlchärs éè l\'</text:span>', 'UTF-8', 'ISO-8859-1'),
			],
			22 => [
				'to_convert' => "text with <strong>strong, </strong><em>emphasis</em> and <u>underlined</u> words with <i>it@lic sp&ciàlchärs éè l'</i>",
				'encode' => true,
				'charset' => null,
				'expected' => mb_convert_encoding('text with <text:span text:style-name="boldText">strong, </text:span><text:span text:style-name="italicText">emphasis</text:span> and <text:span text:style-name="underlineText">underlined</text:span> words with <text:span text:style-name="italicText">it@lic sp&amp;ciàlchärs éè l&apos;</text:span>', 'UTF-8', 'ISO-8859-1'),
			],
			23 => [
				'to_convert' => "text with <strong>intricated<u>tags</u></strong>",
				'encode' => true,
				'charset' => null,
				'expected' => mb_convert_encoding('text with <text:span text:style-name="boldText">intricated<text:span text:style-name="underlineText">tags</text:span></text:span>', 'UTF-8', 'ISO-8859-1'),
			],

			// One can also pass html-encoded string to the method
			24 => [
				'to_convert' => 'One&amp;two',
				'encode' => true,
				'charset' => null,
				'expected' => 'One&amp;two',
			],
			25 => [
				'to_convert' => "text with &lt;strong&gt;strong, &lt;/strong&gt;&lt;em&gt;emphasis&lt;/em&gt; and &lt;u&gt;underlined&lt;/u&gt; words with &lt;i&gt;it@lic sp&amp;ciàlchärs éè l'&lt;/i&gt;",
				'encode' => false,
				'charset' => 'UTF-8',
				'expected' => 'text with <text:span text:style-name="boldText">strong, </text:span><text:span text:style-name="italicText">emphasis</text:span> and <text:span text:style-name="underlineText">underlined</text:span> words with <text:span text:style-name="italicText">it@lic sp&ciàlchärs éè l\'</text:span>',
			],
			26 => [
				'to_convert' => "text with &lt;strong&gt;strong, &lt;/strong&gt;&lt;em&gt;emphasis&lt;/em&gt; and &lt;u&gt;underlined&lt;/u&gt; words with &lt;i&gt;it@lic sp&amp;ciàlchärs éè l'&lt;/i&gt;",
				'encode' => true,
				'charset' => 'UTF-8',
				'expected' => 'text with <text:span text:style-name="boldText">strong, </text:span><text:span text:style-name="italicText">emphasis</text:span> and <text:span text:style-name="underlineText">underlined</text:span> words with <text:span text:style-name="italicText">it@lic sp&amp;ciàlchärs éè l&apos;</text:span>',
			],
			27 => [
				'to_convert' => "text with &lt;strong&gt;strong, &lt;/strong&gt;&lt;em&gt;emphasis&lt;/em&gt; and &lt;u&gt;underlined&lt;/u&gt; words with &lt;i&gt;it@lic sp&amp;ciàlchärs éè l'&lt;/i&gt;",
				'encode' => false,
				'charset' => null,
				'expected' => mb_convert_encoding('text with <text:span text:style-name="boldText">strong, </text:span><text:span text:style-name="italicText">emphasis</text:span> and <text:span text:style-name="underlineText">underlined</text:span> words with <text:span text:style-name="italicText">it@lic sp&ciàlchärs éè l\'</text:span>', 'UTF-8', 'ISO-8859-1'),
			],
			28 => [
				'to_convert' => "text with &lt;strong&gt;strong, &lt;/strong&gt;&lt;em&gt;emphasis&lt;/em&gt; and &lt;u&gt;underlined&lt;/u&gt; words with &lt;i&gt;it@lic sp&amp;ciàlchärs éè l'&lt;/i&gt;",
				'encode' => true,
				'charset' => null,
				'expected' => mb_convert_encoding('text with <text:span text:style-name="boldText">strong, </text:span><text:span text:style-name="italicText">emphasis</text:span> and <text:span text:style-name="underlineText">underlined</text:span> words with <text:span text:style-name="italicText">it@lic sp&amp;ciàlchärs éè l&apos;</text:span>', 'UTF-8', 'ISO-8859-1'),
			],

			// // TODO custom styles are not tested for now : the custom style have a custom ID based on time. Not random, but hard to mock or predict. generated in _replaceHtmlWithOdtTag() case 'span'.
			// [
			// 	'to_convert' => '123 <span style="color:#e74c3c">trucmachin > truc < troc > trac</span>bla bla',
			// 	'encode' => true,
			// 	'charset' => 'UTF-8',
			// 	'expected' => "123 <text:span text:style-name="customStyle1668592427018">trucmachin &gt; truc &lt; troc &gt; trac</text:span>bla bla'",
			// ],

			/* Tests that can evolve */
			// Following tests reflect the current behavior. They may evolve if the method behavior changes.

			// The method removes hyperlinks and tags that are not dealt with.
			29 => [
				'to_convert' => '123 <a href="/test.php">trucmachin > truc < troc > trac</a>bla bla',
				'encode' => true,
				'charset' => null,
				'expected' => "123 trucmachin &gt; truc &lt; troc &gt; tracbla bla",
			],
			30 => [
				'to_convert' => '123 <h3>Title</h3> bla',
				'encode' => true,
				'charset' => null,
				'expected' => "123 Title bla",
			],
			// HTML should not take \n into account, but only <br />.
			31 => [
				'to_convert' => "text with <strong>strong text </strong>, a line\nbreak and <u>underlined</u> words with <i>it@lic sp&ciàlchärs éè l'</i>",
				'encode' => false,
				'charset' => 'UTF-8',
				'expected' => 'text with <text:span text:style-name="boldText">strong text </text:span>, a line'."\n".'break and <text:span text:style-name="underlineText">underlined</text:span> words with <text:span text:style-name="italicText">it@lic sp&ciàlchärs éè l\'</text:span>',
			],
		];

		$odf=new Odf($filename, array());
		if (is_object($odf)) {
			$result = 1;
		} // Just to test

		foreach ($to_test as $case) {
			if ($case['charset'] !== null) {
				$res = $odf->convertVarToOdf($case['to_convert'], $case['encode'], $case['charset']);
			} else {
				$res = $odf->convertVarToOdf($case['to_convert'], $case['encode']);
			}
			$this->assertEquals($res, $case['expected']);
		}

		print __METHOD__." result=".$result."\n";

		return $result;
	}
}
