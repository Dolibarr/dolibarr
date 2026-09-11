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

	/**
	 * testGetHtmlForWithCccNewRendersFreetagSelect2
	 *
	 * @return void
	 */
	public function testGetHtmlForWithCccNewRendersFreetagSelect2()
	{
		global $db, $form, $conf;
		$db = $this->savdb;
		$conf = $this->savconf;
		$form = new Form($db);

		$fm = new FormMail($db);
		$fm->withtoccc = array(9 => 'Ada King <ada@example.com>');
		$fm->param = array();

		$out = $fm->getHtmlForWithCccNew();
		print __METHOD__." out=".$out."\n";

		$this->assertStringContainsString('name="receiverccc[]"', $out, 'testGetHtmlForWithCccNewRendersFreetagSelect21');
		$this->assertStringContainsString('tags: true', $out, 'testGetHtmlForWithCccNewRendersFreetagSelect22');
		$this->assertStringContainsString('Ada King (ada@example.com)', $out, 'testGetHtmlForWithCccNewRendersFreetagSelect23');
	}

	/**
	 * testGetFormDispatchesToFreetagRenderersWhenConstantIsOn
	 *
	 * get_form() itself contains the ternaries that pick between the old and new To/CC/CCC
	 * renderers based on MAIL_ENABLE_FREETAG_RECIPIENT_INPUT. The 4 tests above only call the
	 * individual getHtmlForTo()/getHtmlForToNew()/... methods directly and never exercise that
	 * dispatch logic. This test drives it through the real get_form() call.
	 *
	 * @return void
	 */
	public function testGetFormDispatchesToFreetagRenderersWhenConstantIsOn()
	{
		global $db, $form, $conf;
		$db = $this->savdb;
		$conf = $this->savconf;
		$form = new Form($db);

		$savedvalue = getDolGlobalString('MAIL_ENABLE_FREETAG_RECIPIENT_INPUT');

		try {
			// Off: the dispatch ternaries must pick the old renderers, which never emit createTag.
			$conf->global->MAIL_ENABLE_FREETAG_RECIPIENT_INPUT = 0;

			$fmoff = new FormMail($db);
			$fmoff->param = array('models' => 'none', 'returnurl' => '');
			$fmoff->withtoccc = 1;
			$fmoff->withtopic = 0; // Avoid an unrelated pre-existing issue: with no template ($arraydefaultmessage stays -1), getHtmlForTopic() dereferences it as an object.
			$fmoff->withbody = 0; // Same pre-existing issue as withtopic, for the body/content section.

			$outoff = $fmoff->get_form();
			print __METHOD__." outoff=".$outoff."\n";

			$this->assertStringNotContainsString('createTag', $outoff, 'testGetFormDispatchesToFreetagRenderersWhenConstantIsOn1');

			// On: the dispatch ternaries must pick the new *New() renderers, which do emit createTag.
			$conf->global->MAIL_ENABLE_FREETAG_RECIPIENT_INPUT = 1;

			$fmon = new FormMail($db);
			$fmon->param = array('models' => 'none', 'returnurl' => '');
			$fmon->withtoccc = 1;
			$fmon->withtopic = 0; // Avoid an unrelated pre-existing issue: with no template ($arraydefaultmessage stays -1), getHtmlForTopic() dereferences it as an object.
			$fmon->withbody = 0; // Same pre-existing issue as withtopic, for the body/content section.

			$outon = $fmon->get_form();
			print __METHOD__." outon=".$outon."\n";

			$this->assertStringContainsString('createTag', $outon, 'testGetFormDispatchesToFreetagRenderersWhenConstantIsOn2');
		} finally {
			$conf->global->MAIL_ENABLE_FREETAG_RECIPIENT_INPUT = $savedvalue;
		}
	}
}
