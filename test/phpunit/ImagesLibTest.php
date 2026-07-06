<?php
/* Copyright (C) 2010-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2023		Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/ImagesLibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/images.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

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
class ImagesLibTest extends CommonClassTest
{
	/**
	 * testDolCountNbOfLine
	 *
	 * @return	int
	 */
	public function testgetImageSize()
	{
		$file = dirname(__FILE__).'/img250x50.jpg';
		$tmp = dol_getImageSize($file);
		print __METHOD__." result=".$tmp['width'].'/'.$tmp['height']."\n";
		$this->assertEquals($tmp['width'], 250);
		$this->assertEquals($tmp['height'], 50);

		$file = dirname(__FILE__).'/img250x20.png';
		$tmp = dol_getImageSize($file);
		print __METHOD__." result=".$tmp['width'].'/'.$tmp['height']."\n";
		$this->assertEquals($tmp['width'], 250);
		$this->assertEquals($tmp['height'], 20);

		/*$file=dirname(__FILE__).'/filenotfound.png';
		$tmp=dol_getImageSize($file);
		print __METHOD__." result=".$tmp['width'].'/'.$tmp['height']."\n";
		$this->assertEquals($tmp['width'],250);
		$this->assertEquals($tmp['height'],20);*/

		return 1;
	}

	/**
	 * testDolImageResizeOrCrop
	 *
	 * @return 	int
	 */
	public function testDolImageResizeOrCrop()
	{
		global $conf;

		$file = dirname(__FILE__).'/img250x20.png';
		$filetarget = $conf->admin->dir_temp.'/img250x20.jpg';
		dol_delete_file($filetarget);
		$result = dol_imageResizeOrCrop($file, 0, 0, 0, 0, 0, $filetarget);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($filetarget, $result, 'Failed to convert PNG '.$file.' into '.$filetarget);

		/*$file=dirname(__FILE__).'/img250x20.png';
		$filetarget=$conf->admin->dir_temp.'/img250x20.webp';
		dol_delete_file($filetarget);
		$result = dol_imageResizeOrCrop($file, 0, 0, 0, 0, 0, $filetarget);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($filetarget, $result, 'Failed to convert PNG '.$file.' into WEBP '.$filetarget);*/
	}
}
