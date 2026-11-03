<?php
/* Copyright (C) 2026 Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/VCardTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/vcard.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class VCardTest extends CommonClassTest
{
	/**
	 * testLineBreaks: a line break of an address or a note is one \n, the text is not quoted-printable
	 *
	 * @return  void
	 */
	public function testLineBreaks()
	{
		$v = new vCard();
		$v->setAddress("", "", "1 rue A\r\nBatiment B\nEntree 2", "Paris", "", "75001", "France", "TYPE=WORK");
		$v->setNote("Line1\r\nLine2");
		$text = $v->getVCard();

		$this->assertStringContainsString('1 rue A\nBatiment B\nEntree 2', $text);
		$this->assertStringContainsString("NOTE:Line1\\nLine2\r\n", $text);
		$this->assertStringNotContainsString('=0D', $text);
		$this->assertStringNotContainsString('ENCODING', $text);
		$this->assertStringNotContainsString('CHARSET', $text);
	}

	/**
	 * testTextEscaping: backslash, semicolon and comma of a text are escaped, accents stay UTF-8, the separator of the parts of N and ADR is not
	 *
	 * @return  void
	 */
	public function testTextEscaping()
	{
		$v = new vCard();
		$v->setName("Dupont, Jr.", "José", "", "", "");
		$v->setAddress("", "", "12, rue A; porte \\ 2", "Saint-Étienne", "", "42000", "France", "TYPE=WORK;POSTAL");
		$text = $v->getVCard();

		$this->assertStringContainsString("N:Dupont\\, Jr.;José;;;\r\n", $text);
		$this->assertStringContainsString("ADR;TYPE=WORK,POSTAL:;;12\\, rue A\\; porte \\\\ 2;Saint-Étienne;;42000;France\r\n", $text);
	}

	/**
	 * testFolding: no line is longer than 75 octets, a character is not cut, and unfolding gives back the value
	 *
	 * @return  void
	 */
	public function testFolding()
	{
		$note = str_repeat('Ça va très bien ', 20);
		$v = new vCard();
		$v->setNote($note);
		$text = $v->getVCard();

		$lines = explode("\r\n", $text);
		foreach ($lines as $line) {
			$this->assertLessThanOrEqual(75, strlen($line));
			$this->assertSame(1, preg_match('//u', $line), 'A line is not valid UTF-8: '.$line);
		}
		$this->assertStringContainsString("NOTE:".$note, str_replace("\r\n ", '', $text));
	}

	/**
	 * testNoPropertyInjection: a value written as is can not add a property to the card
	 *
	 * @return  void
	 */
	public function testNoPropertyInjection()
	{
		$v = new vCard();
		$v->setEmail("a@b.fr\r\nX-INJECT:yes", "TYPE=WORK");
		$v->setURL("https://example.org/\nX-INJECT2:yes");
		$v->setPhoneNumber("0102\r\nX-INJECT3:yes", "TYPE=WORK");
		$text = $v->getVCard();

		$this->assertStringNotContainsString("\r\nX-INJECT", $text);
		$this->assertStringContainsString("EMAIL;TYPE=WORK:a@b.fr X-INJECT:yes\r\n", $text);
	}

	/**
	 * testEmptyValues: an empty value does not write an empty property
	 *
	 * @return  void
	 */
	public function testEmptyValues()
	{
		$v = new vCard();
		$v->setName("Doe", "Jane");
		$v->setPhoneNumber("", "TYPE=WORK");
		$v->setEmail("");
		$v->setNote("");
		$v->setTitle("");
		$v->setOrg("");
		$v->setURL("");
		$v->setAddress("", "", "", "", "", "", "", "TYPE=WORK");
		$text = $v->getVCard();

		$this->assertStringContainsString("FN:Jane Doe\r\n", $text);
		foreach (array('TEL', 'EMAIL', 'NOTE', 'TITLE', 'ORG', 'URL', 'ADR') as $property) {
			$this->assertStringNotContainsString("\r\n".$property, $text);
		}
	}

	/**
	 * testRevIsUtc: the revision date is written in UTC
	 *
	 * @return  void
	 */
	public function testRevIsUtc()
	{
		$v = new vCard();
		$text = $v->getVCard();

		$this->assertEquals(1, preg_match('/REV:(\d{8}T\d{6})Z/', $text, $reg));
		$this->assertLessThanOrEqual(5, abs(strtotime($reg[1].' UTC') - time()));
	}
}
