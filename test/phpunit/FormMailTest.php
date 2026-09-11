<?php
/* Copyright (C) 2026       Frédéric France             <frederic.france@free.fr>
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
 *      \file       test/phpunit/FormMailTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db, $form;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/html.form.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/html.formmail.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals          disabled
 * @backupStaticAttributes enabled
 * @remarks                backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class FormMailTest extends CommonClassTest
{
	/**
	 * testGetHtmlForToNewRendersFreetagSelect2
	 *
	 * @return void
	 */
	public function testGetHtmlForToNewRendersFreetagSelect2()
	{
		global $db, $form;
		$db = $this->savdb;
		$form = new Form($db);

		$fm = new FormMail($db);
		$fm->withto = array(5 => 'Jane Doe <jane@example.com>');
		$fm->withtofree = 1;

		$out = $fm->getHtmlForToNew();
		print __METHOD__." out=".$out."\n";

		$this->assertStringContainsString('name="receiver[]"', $out, 'testGetHtmlForToNewRendersFreetagSelect21');
		$this->assertStringContainsString('tags: true', $out, 'testGetHtmlForToNewRendersFreetagSelect22');
		$this->assertStringContainsString('createTag: function', $out, 'testGetHtmlForToNewRendersFreetagSelect23');
		$this->assertStringContainsString('Jane Doe (jane@example.com)', $out, 'testGetHtmlForToNewRendersFreetagSelect24');
	}

	/**
	 * testGetHtmlForToOldHasNoFreetagScript
	 *
	 * @return void
	 */
	public function testGetHtmlForToOldHasNoFreetagScript()
	{
		global $db, $form;
		$db = $this->savdb;
		$form = new Form($db);

		$fm = new FormMail($db);
		$fm->withto = array(5 => 'Jane Doe <jane@example.com>');
		$fm->withtofree = 1;

		$out = $fm->getHtmlForTo();
		print __METHOD__." out=".$out."\n";

		$this->assertStringNotContainsString('createTag', $out, 'testGetHtmlForToOldHasNoFreetagScript1');
	}

	/**
	 * testGetHtmlForCcNewRendersFreetagSelect2
	 *
	 * @return void
	 */
	public function testGetHtmlForCcNewRendersFreetagSelect2()
	{
		global $db, $form;
		$db = $this->savdb;
		$form = new Form($db);

		$fm = new FormMail($db);
		$fm->withtocc = array(7 => 'Bob Roe <bob@example.com>');

		$out = $fm->getHtmlForCcNew();
		print __METHOD__." out=".$out."\n";

		$this->assertStringContainsString('name="receivercc[]"', $out, 'testGetHtmlForCcNewRendersFreetagSelect21');
		$this->assertStringContainsString('tags: true', $out, 'testGetHtmlForCcNewRendersFreetagSelect22');
		$this->assertStringContainsString('Bob Roe (bob@example.com)', $out, 'testGetHtmlForCcNewRendersFreetagSelect23');
	}
}
