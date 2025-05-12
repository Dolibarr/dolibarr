<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/WebsiteTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (! defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
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
if (! defined("NOSESSION")) {
	define("NOSESSION", '1');
}

require_once dirname(__FILE__).'/../../htdocs/main.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/website.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/website2.lib.php';
require_once dirname(__FILE__).'/../../htdocs/website/class/website.class.php';


if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
if (empty($user->rights->website)) {
	$user->rights->website = new stdClass();
}

$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class WebsiteTest extends CommonClassTest
{
	/**
	 * testGetPagesFromSearchCriterias
	 *
	 * @return	void
	 */
	public function testGetPagesFromSearchCriterias()
	{
		global $db, $website;	// We need the $website as global, it is used by the getPagesFromSearchCriterias()

		$website = new Website($db);	// $website must be defined globally for getPagesFromSearchCriterias()

		$s = "123') OR 1=1-- \' xxx";
		/*
		 var_dump($s);
		 var_dump($db->escapeforlike($s));
		 var_dump($db->escape($db->escapeforlike($s)));
		 */

		$res = getPagesFromSearchCriterias('page,blogpost', 'meta,content', $s, 2, 'date_creation', 'DESC', 'en');
		//var_dump($res);
		print __METHOD__." message=".$res['code']."\n";
		// We must found no line (so code should be KO). If we found somethiing, it means there is a SQL injection of the 1=1
		$this->assertEquals($res['code'], 'KO');
	}

	/**
	 * testDolStripPhpCode
	 *
	 * @return	void
	 */
	public function testDolStripPhpCode()
	{
		global $db;

		$s = "abc\n<?php echo 'def'\n// comment\n ?>ghi";
		$result = dolStripPhpCode($s);
		$this->assertEquals("abc\n<span phptag></span>ghi", $result);

		$s = "abc\n<?PHP echo 'def'\n// comment\n ?>ghi";
		$result = dolStripPhpCode($s);
		$this->assertEquals("abc\n<span phptag></span>ghi", $result);
	}

	/**
	 * testCheckPHPCode
	 *
	 * @return	void
	 */
	public function testCheckPHPCode()
	{
		global $conf, $user;

		// Force permission so this is not the permission that will affect result of checkPHPCode
		$user->rights->website->writephp = 1;

		// Legitimate

		$t = '';
		$s = '<?php execu ?>';
		$result = checkPHPCode($t, $s);
		print __METHOD__." result checkPHPCode=".$result."\n";
		$this->assertEquals($result, 0, 'checkPHPCode detect string as dangerous when it is legitimate');

		$t = '';
		$s = '<?php echo $_SESSION["eee"] ?>';
		$result = checkPHPCode($t, $s);
		print __METHOD__." result checkPHPCode=".$result."\n";
		$this->assertEquals($result, 0, 'checkPHPCode detect string as dangerous when it is legitimate');


		// Dangerous

		$t = '';
		$s = '<?php exec("eee"); ?>';
		$result = checkPHPCode($t, $s);
		print __METHOD__." result checkPHPCode=".$result."\n";
		$this->assertEquals($result, 1, 'checkPHPCode did not detect the string was dangerous');

		$t = '';
		$s = '<?php eXec  ("eee"); ?>';
		$result = checkPHPCode($t, $s);
		print __METHOD__." result checkPHPCode=".$result."\n";
		$this->assertEquals($result, 1, 'checkPHPCode did not detect the string was dangerous');

		$t = '';
		$s = '<?php $a="xec"; "e$a" ("ee"); ?>';
		$result = checkPHPCode($t, $s);
		print __METHOD__." result checkPHPCode=".$result."\n";
		$this->assertEquals($result, 1, 'checkPHPCode did not detect the string was dangerous');

		$t = '';
		$s = '<?php $a=\'exec\'("ee"); ?>';
		$result = checkPHPCode($t, $s);
		print __METHOD__." result checkPHPCode=".$result."\n";
		$this->assertEquals($result, 1, 'checkPHPCode did not detect the string was dangerous');

		$t = '';
		$s = '<?php $_="{"; $_=($_^"<").($_^">;").($_^"/"); ?><?=${\'_\'.$_}["_"](${\'_\'.$_}["__"]);?>';
		$result = checkPHPCode($t, $s);
		print __METHOD__." result checkPHPCode=".$result."\n";
		$this->assertEquals($result, 1, 'checkPHPCode did not detect the string was dangerous');

		// Dangerous but legitimate due to option WEBSITE_PHP_ALLOW_EXEC

		$conf->global->WEBSITE_PHP_ALLOW_EXEC = 1;

		$t = '';
		$s = '<?php exec("eee"); ?>';
		$result = checkPHPCode($t, $s);
		print __METHOD__." result checkPHPCode=".$result."\n";
		$this->assertEquals($result, 0, 'checkPHPCode did not accept the exec. it should when WEBSITE_PHP_ALLOW_EXEC is set.');
	}

	/**
	 * testDolKeepOnlyPhpCode
	 *
	 * @return void
	 */
	public function testDolKeepOnlyPhpCode()
	{
		$s = 'HTML content <?php exec("eee"); ?> and more HTML content';
		$result = dolKeepOnlyPhpCode($s);
		print __METHOD__." result dolKeepOnlyPhpCode=".$result."\n";
		$this->assertEquals('<?php exec("eee"); ?>', $result, 'dolKeepOnlyPhpCode did extract the correct string');

		$s = 'HTML content <? exec("eee"); ?> and more HTML content';
		$result = dolKeepOnlyPhpCode($s);
		print __METHOD__." result dolKeepOnlyPhpCode=".$result."\n";
		$this->assertEquals('<?php exec("eee"); ?>', $result, 'dolKeepOnlyPhpCode did extract the correct string');

		$s = 'HTML content <?php test() <?php test2(); ?> and more HTML content';
		$result = dolKeepOnlyPhpCode($s);
		print __METHOD__." result dolKeepOnlyPhpCode=".$result."\n";
		$this->assertEquals('<?php test() ?><?php test2(); ?>', $result, 'dolKeepOnlyPhpCode did extract the correct string');
	}

	/**
	 * testGetImageFromHtmlContent
	 *
	 * @return void
	 */
	public function testGetImageFromHtmlContent()
	{
		// Example of usage
		$htmlContent = '<p>Some text before.</p><img src="image1.jpg"><p>Some text in between.</p><img src="/mydir/image2.jpg"><p>Some text after.</p>';

		$firstImage = getImageFromHtmlContent($htmlContent, 1);
		print __METHOD__." result firstImage=".$firstImage."\n";
		$this->assertEquals('image1.jpg', $firstImage, ' failed to get firstimage');

		$secondImage = getImageFromHtmlContent($htmlContent, 2);
		print __METHOD__." result secondImage=".$secondImage."\n";
		$this->assertEquals('/mydir/image2.jpg', $secondImage, ' failed to get second image');
	}
}
