<?php
/* Copyright (C) 2022 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/RepositoryTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';
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

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class RepositoryTest extends CommonClassTest
{
	/**
	 * testJqueryOnce
	 *
	 * @return	void
	 *
	 * The depends says test is run only if previous is ok
	 */
	public function testJqueryOnce()
	{
		global $conf,$user,$langs,$db;

		// Scan dir to guarantee we don't have library jquery twice
		$foundfiles = dol_dir_list(DOL_DOCUMENT_ROOT.'/includes/', 'files', 1, '^jquery\.js', array('ckeditor'));
		print __METHOD__." count(founddirs)=".count($foundfiles)."\n";
		$this->assertEquals(1, count($foundfiles), 'We found jquery lib (jquery.js) twice');

		// Scan dir to guarantee we don't have library jquery twice
		$foundfiles = dol_dir_list(DOL_DOCUMENT_ROOT.'/includes/', 'files', 1, '^jquery\.min\.js', array('ckeditor'));
		print __METHOD__." count(founddirs)=".count($foundfiles)."\n";
		$this->assertEquals(1, count($foundfiles), 'We found jquery lib (jquery.min.js) twice '.(empty($foundfiles[0]) ? '' : $foundfiles[0]['fullname']).' '.(empty($foundfiles[1]) ? '' : $foundfiles[1]['fullname']));
	}


	/**
	 * testRepository
	 *
	 * @return string
	 */
	public function testRepository()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$filesarray = dol_dir_list(DOL_DOCUMENT_ROOT, 'directories', 1, '', array('\/custom\/'), 'fullname', SORT_ASC, 0, 1, '', 1);
		//$filesarray = dol_dir_list(DOL_DOCUMENT_ROOT, 'files', 1, '\.php', null, 'fullname');

		$ok = true;
		foreach ($filesarray as $key => $file) {
			if (preg_match('/\/vendor\//', $file['fullname'])) {
				if (!preg_match('/php-imap/', $file['fullname'])) {		// we accept 'vendor' dir for php-imap (not find how to do it easily without)
					print "Found a vendor dir into ".$file['fullname']."\n";
					$ok = false;
				}
			}
		}

		$this->assertTrue($ok, 'Pb in list of files of repository');

		return '';
	}
}
