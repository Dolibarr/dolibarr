<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/LessTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security2.lib.php';

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

if (empty($user->id)) {
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
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class LesscTest extends CommonClassTest
{
	/**
	 * testLess
	 *
	 * @return string
	 */
	public function testLessc()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		include_once DOL_DOCUMENT_ROOT.'/core/class/lessc.class.php';

		$lesscobj = new Lessc();

		$cssfile = "
        a { font-size: 10px; }
        b .test {
        	font-size: 10px;
        }
        ";

		try {
			$contentforlessc = ".bodywebsite {\n".$cssfile."\n}\n";
			//print '<pre>'.$contentforlessc.'</pre>';
			$result = $lesscobj->compile($contentforlessc);
			//var_dump($contentforlessc); exit;
		} catch (exception $e) {
			//echo "failed to compile lessc";
			$result = 'Error';
			dol_syslog("Failed to compile the CSS with lessc: ".$e->getMessage(), LOG_WARNING);
		}

		$cssexpected = "
.bodywebsite a {
  font-size: 10px;
}
.bodywebsite b .test {
  font-size: 10px;
}
";

		print __METHOD__." Result=".$result."\n";
		$this->assertEquals(trim($result), trim($cssexpected));

		return;
	}

	/**
	 * Data provider for testLesscCompileFeatures
	 *
	 * @return array<string,array{0:string,1:string}>	Array of (LESS source, CSS expected with spaces reduced)
	 */
	public static function lesscFeaturesProvider()
	{
		return array(
			'operation in parenthesis' => array(".a { height: (2px + 3px) * 2; }", ".a { height: 10px; }"),
			'addition' => array(".a { width: 2px + 3px; }", ".a { width: 5px; }"),
			'mixin with argument' => array(".m(@w: 5px) { width: @w; }\n.a { .m(10px); }", ".a { width: 10px; }"),
			'mixin without argument' => array(".m { width: 1px; }\n.a { .m; }", ".m { width: 1px; } .a { width: 1px; }"),
			'mixin with important' => array(".m() { width: 1px; }\n.a { .m() !important; }", ".a { width: 1px !important; }"),
			'mixin with guards' => array(".m(@a) when (@a > 10) { width: @a; }\n.m(@a) when (@a =< 10) { height: @a; }\n.a { .m(20); }\n.b { .m(5); }", ".a { width: 20; } .b { height: 5; }"),
			'media only' => array("@media only screen and (max-width: 600px) { .a { color: red; } }", "@media only screen and (max-width: 600px) { .a { color: red; } }"),
			'media without value' => array("@media (color) { .a { color: red; } }", "@media (color) { .a { color: red; } }"),
			'unknown function alpha' => array(".a { filter: alpha(opacity=50); }", ".a { filter: alpha(opacity=50); }"),
			'operation not supported' => array(".a { width: 2 - #fff; }", ".a { width: 2 - #ffffff; }"),
		);
	}

	/**
	 * Test compilation of several LESS features (non regression of the parser and of the compiler)
	 *
	 * @param	string	$source		LESS source
	 * @param	string	$expected	CSS expected (with spaces reduced)
	 * @return	void
	 *
	 * @dataProvider lesscFeaturesProvider
	 */
	public function testLesscCompileFeatures($source, $expected)
	{
		include_once DOL_DOCUMENT_ROOT.'/core/class/lessc.class.php';

		$lesscobj = new Lessc();
		try {
			$result = $lesscobj->compile($source);
		} catch (Exception $e) {
			$result = 'Error: '.$e->getMessage();
		}

		$this->assertEquals($expected, preg_replace('/\s+/', ' ', trim($result)));
	}
}
