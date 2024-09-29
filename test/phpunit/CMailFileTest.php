<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/CMailFileTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__) . '/../../htdocs/master.inc.php';
require_once dirname(__FILE__) . '/../../htdocs/core/class/CMailFile.class.php';
require_once dirname(__FILE__) . '/../../htdocs/core/lib/files.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

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
 * @remarks    backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class CMailFileTest extends CommonClassTest
{
	/**
	 * testCMailFileText
	 *
	 * @return void
	 */
	public function testCMailFileText()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new CMailFile('Test', 'test@test.com', 'from@from.com', 'Message txt', array(), array(), array(), '', '', 1, 0);

		$result = $localobject->sendfile();
		print __METHOD__ . " result=" . $result . "\n";
		$this->assertFalse($result);   // False because mail send disabled

		return $result;
	}

	/**
	 * testCMailFileStatic
	 *
	 * @return string
	 */
	public function testCMailFileStatic()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new CMailFile('', '', '', '');

		$src = 'John Doe <john@doe.com>';
		$result = $localobject->getValidAddress($src, 0);
		print __METHOD__ . " result=" . $result . "\n";
		$this->assertEquals($result, 'John Doe <john@doe.com>');

		$src = 'John Doe <john@doe.com>';
		$result = $localobject->getValidAddress($src, 1);
		print __METHOD__ . " result=" . $result . "\n";
		$this->assertEquals($result, '<john@doe.com>');

		$src = 'John Doe <john@doe.com>';
		$result = $localobject->getValidAddress($src, 2);
		print __METHOD__ . " result=" . $result . "\n";
		$this->assertEquals($result, 'john@doe.com');

		$src = 'John Doe <john@doe.com>';
		$result = $localobject->getValidAddress($src, 3, 0);
		print __METHOD__ . " result=" . $result . "\n";
		$this->assertEquals($result, '"John Doe" <john@doe.com>');

		$src = 'John Doe <john@doe.com>';
		$result = $localobject->getValidAddress($src, 3, 1);
		print __METHOD__ . " result=" . $result . "\n";
		$this->assertEquals($result, '"=?UTF-8?B?Sm9obiBEb2U=?=" <john@doe.com>');

		$src = 'John Doe <john@doe.com>';
		$result = $localobject->getValidAddress($src, 4);
		print __METHOD__ . " result=" . $result . "\n";
		$this->assertEquals($result, 'John Doe');

		$src = 'John Doe <john@doe.com>, John Doe2 <john@doe3.com>, John Doe3 <john@doe2.com>';
		$result = $localobject->getValidAddress($src, 4);
		print __METHOD__ . " result=" . $result . "\n";
		$this->assertEquals($result, 'John Doe,John Doe2,John Doe3');

		$src = 'John Doe <john@doe.com>, John Doe2 <john@doe3.com>, John Doe3 <john@doe2.com>';
		$result = $localobject->getValidAddress($src, 4, 0, 2);
		print __METHOD__ . " result=" . $result . "\n";
		$this->assertEquals($result, 'John Doe,John Doe2...');

		return $result;
	}

	/**
	 * testCMailFileHTMLWithImage
	 *
	 * @return 	int
	 */
	public function testCMailFileHTMLWithImage()
	{
		global $conf, $user, $langs, $db;

		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$conf->global->MAIN_MAIL_ADD_INLINE_IMAGES_IF_IN_MEDIAS = 1;
		$conf->global->MAIN_MAIL_ADD_INLINE_IMAGES_IF_DATA = 1;

		dol_delete_dir_recursive(DOL_DATA_ROOT . '/medias/image');
		dol_mkdir(DOL_DATA_ROOT . '/medias/image');
		dol_copy(dirname(__FILE__) . '/img250x20.png', DOL_DATA_ROOT . '/medias/image/img250x20.png');

		$msg = '<html><body>';
		$msg .= '<img alt="" src="/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/img250x20.png" style="height:39px; width:150px" />';
		$msg .= ' <p>From wikipedia</p> <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==" alt="Red dot" />';
		$msg .= '</body></html>';

		$tmp_dir = $conf->admin->dir_temp.'/'.__FUNCTION__.getmypid().'_tmp';
		$localobject = new CMailFile('Test', 'test@test.com', 'from@from.com', $msg, array(), array(), array(), '', '', 0, -1, '', '', '', '', 'standard', '', $tmp_dir);

		$result = count($localobject->html_images);
		print __METHOD__ . " result count image detected in the mail=" . $result . "\n";
		$this->assertEquals($result, 2);


		foreach ($localobject->html_images as $i => $val) {
			if ($localobject->html_images[$i]) {
				if (preg_match('/img250x20\.png/i', $localobject->html_images[$i]['fullpath'])) {
					print __METHOD__ . " content type must be image png =" . $localobject->html_images[$i]['content_type'] . "\n";
					$this->assertEquals($localobject->html_images[$i]['content_type'], 'image/png');

					print __METHOD__ . " type must be cidfromurl =" . $localobject->html_images[$i]['type'] . "\n";
					$this->assertEquals($localobject->html_images[$i]['type'], 'cidfromurl');

					$fileSize = 9744;
					print __METHOD__ . " File size must be =" . $fileSize . "\n";
					$this->assertEquals(dol_filesize($localobject->html_images[$i]['fullpath']), $fileSize);
				} elseif (preg_match('/\.png/i', $localobject->html_images[$i]['fullpath'])) {
					print __METHOD__ . " content type must be image png =" . $localobject->html_images[$i]['content_type'] . "\n";
					$this->assertEquals($localobject->html_images[$i]['content_type'], 'image/png');

					print __METHOD__ . " type must be cidfromdata =" . $localobject->html_images[$i]['type'] . "\n";
					$this->assertEquals($localobject->html_images[$i]['type'], 'cidfromdata');

					$fileSize = 85;
					print __METHOD__ . " File size must be =" . $fileSize . "\n";
					$this->assertEquals(dol_filesize($localobject->html_images[$i]['fullpath']), $fileSize);
				}
			}
		}

		return $result;
	}
}
