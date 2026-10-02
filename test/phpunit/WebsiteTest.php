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

global $conf,$user,$langs,$db,$mysoc;
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

		// Force allow of PHP in website from main setup
		global $dolibarr_website_allow_custom_php;
		$dolibarr_website_allow_custom_php = 2;		// Value 2 allow PHP code, so we can check the protections. Value 1 will allow PHP code only if exec fautres are disabled in PHP.

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

		$t = '';
		$s = '<?php $pid = pcntl_fork(); ?>';
		$result = checkPHPCode($t, $s);
		print __METHOD__." result checkPHPCode=".$result."\n";
		$this->assertEquals($result, 1, 'checkPHPCode did not detect the string was dangerous');

		$t = '';
		$s = '<?php $pid = pcntl_fork(); if (!$pid) { pcntl_exec("/usr/bin/touch", array("/tmp/totouch")); } ?>';
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
	 * testPutPageApiCheckPHPCode
	 *
	 * Regression test of security fix: Websites::putPage() (REST API PUT website page) must call
	 * checkPHPCode() with the same rules than the web editor. A page whose PHP content contains
	 * a forbidden instruction (here pcntl_exec) must be refused, even if the API user has the
	 * writephp permission, while a page with a legitimate PHP code can still be updated.
	 *
	 * @return void
	 */
	public function testPutPageApiCheckPHPCode()
	{
		global $conf, $db, $user, $langs;

		if (!isModEnabled('website')) {
			$this->markTestSkipped('Website module is not enabled.');
		}
		if (empty($conf->website->dir_output) || empty($conf->website->dir_temp)) {
			$this->markTestSkipped('No output or temp directory defined for website module.');
		}

		require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';
		require_once dirname(__FILE__).'/../../htdocs/api/class/api_access.class.php';
		require_once dirname(__FILE__).'/../../htdocs/api/class/api.class.php';
		require_once dirname(__FILE__).'/../../htdocs/website/class/api_websites.class.php';

		// Save context and force the setup so the forbidden php functions list is the only rule that can block
		global $dolibarr_website_allow_custom_php;
		$savdolibarrwebsiteallowcustomphp = $dolibarr_website_allow_custom_php;
		$dolibarr_website_allow_custom_php = 2;	// Allow PHP code (value 2), so the permission and the global option can not block
		$savwebsitephpallowexec = getDolGlobalString('WEBSITE_PHP_ALLOW_EXEC');
		unset($conf->global->WEBSITE_PHP_ALLOW_EXEC);	// Make sure functions to execute commands are forbidden

		// Force permissions so this is not the permission that will affect result of checkPHPCode
		if (empty($user->rights->website)) {
			$user->rights->website = new stdClass();
		}
		$savwritephp = empty($user->rights->website->writephp) ? 0 : $user->rights->website->writephp;
		$savwrite = empty($user->rights->website->write) ? 0 : $user->rights->website->write;
		$user->rights->website->writephp = 1;
		$user->rights->website->write = 1;

		// Set the API user like the REST API authentication layer does
		DolibarrApiAccess::$user = $user;

		// The regeneration of page files done into Websites::putPage() uses the global $dolibarr_main_data_root
		// that is not defined into the PHPUnit context (main.inc.php is loaded into a non global scope),
		// so we set it like the web context does, so files are written into the documents directory of website.
		global $dolibarr_main_data_root;
		$savdolibarrmaindataroot = isset($GLOBALS['dolibarr_main_data_root']) ? $GLOBALS['dolibarr_main_data_root'] : null;
		$dolibarr_main_data_root = DOL_DATA_ROOT;

		// Create a website
		$website = new Website($db);
		$website->ref = 'testwebsiteapi'.mt_rand(10000, 99999);
		$website->description = 'Website created for unit test testPutPageApiCheckPHPCode';
		$website->lang = 'en';
		$website->status = 1;
		$result = $website->create($user);
		print __METHOD__." website created ref=".$website->ref." result=".var_export($result, true)."\n";
		$this->assertGreaterThan(0, $result, 'Failed to create website: '.$website->error);

		// dolSavePageContent() used when regenerating page files reads the temp htmlheader file of the website
		dol_mkdir($conf->website->dir_temp.'/'.$website->ref.'/containers');
		file_put_contents($conf->website->dir_temp.'/'.$website->ref.'/containers/htmlheader.html', '');

		// Create a page with a forbidden php instruction inside its content
		$pageforbidden = new WebsitePage($db);
		$pageforbidden->fk_website = $website->id;
		$pageforbidden->pageurl = 'pageforbidden'.$website->id;
		$pageforbidden->title = 'Page with forbidden php content';
		$pageforbidden->type_container = 'page';
		$pageforbidden->lang = 'en';
		$pageforbidden->aliasalt = '';
		$pageforbidden->content = '<body>Page with php <?php $pid = pcntl_fork(); if (!$pid) { pcntl_exec("/usr/bin/touch", array("/tmp/totouch")); } ?></body>';
		$pageforbidden->date_creation = dol_now();
		$result = $pageforbidden->create($user);
		print __METHOD__." page with forbidden php created id=".var_export($pageforbidden->id, true)." result=".var_export($result, true)."\n";
		$this->assertGreaterThan(0, $result, 'Failed to create page with forbidden php content: '.implode(', ', $pageforbidden->errors));

		// Create a page with a legitimate php code inside its content
		$pagelegit = new WebsitePage($db);
		$pagelegit->fk_website = $website->id;
		$pagelegit->pageurl = 'pagelegit'.$website->id;
		$pagelegit->title = 'Page with legit php content';
		$pagelegit->type_container = 'page';
		$pagelegit->lang = 'en';
		$pagelegit->aliasalt = '';
		$pagelegit->content = '<body>Page with php <?php echo "test"; ?></body>';
		$pagelegit->date_creation = dol_now();
		$result = $pagelegit->create($user);
		print __METHOD__." page with legit php created id=".var_export($pagelegit->id, true)." result=".var_export($result, true)."\n";
		$this->assertGreaterThan(0, $result, 'Failed to create page with legit php content: '.implode(', ', $pagelegit->errors));

		// Update with the API the page that contains a forbidden php instruction: must be refused
		$api = new Websites();
		$exceptionthrown = null;
		try {
			$api->putPage($website->id, $pageforbidden->id, array('title' => 'New title'));
		} catch (Luracast\Restler\RestException $e) {
			$exceptionthrown = $e;
		}
		print __METHOD__." update of page with forbidden php content: exception=".var_export($exceptionthrown ? $exceptionthrown->getCode() : 'none', true)."\n";
		$this->assertNotNull($exceptionthrown, 'putPage() accepted the update of a page with a forbidden php instruction (pcntl_exec) but checkPHPCode() should have refused it');
		$this->assertEquals(403, $exceptionthrown->getCode(), 'putPage() must refuse with a 403 error the update of a page with a forbidden php instruction');

		// Update with the API the page that contains a legitimate php code: must be accepted
		$api = new Websites();
		$result = $api->putPage($website->id, $pagelegit->id, array('title' => 'New title for legit page'));
		print __METHOD__." update of page with legit php content: result=".var_export($result ? 'ok' : 'ko', true)."\n";
		$pageafterupdate = new WebsitePage($db);
		$pageafterupdate->fetch($pagelegit->id);
		$this->assertEquals('New title for legit page', $pageafterupdate->title, 'putPage() refused or did not save the update of a page with a legitimate php code');

		// Cleanup
		$pagetodelete = new WebsitePage($db);
		$pagetodelete->fetch($pageforbidden->id);
		$pagetodelete->delete($user);
		$pagetodelete->fetch($pagelegit->id);
		$pagetodelete->delete($user);
		$result = $website->delete($user);
		print __METHOD__." cleanup website=".$website->ref." result=".var_export($result, true)."\n";
		dol_delete_dir_recursive($conf->website->dir_temp.'/'.$website->ref);

		// Restore context
		$dolibarr_website_allow_custom_php = $savdolibarrwebsiteallowcustomphp;
		if ($savwebsitephpallowexec) {
			$conf->global->WEBSITE_PHP_ALLOW_EXEC = $savwebsitephpallowexec;
		}
		if (!is_null($savdolibarrmaindataroot)) {
			$GLOBALS['dolibarr_main_data_root'] = $savdolibarrmaindataroot;
		} else {
			unset($GLOBALS['dolibarr_main_data_root']);
		}
		$user->rights->website->writephp = $savwritephp;
		$user->rights->website->write = $savwrite;
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
