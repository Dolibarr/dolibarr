<?php
/* Copyright (C) 2026		iProspective			<contact@iprospective.fr>
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
 */

/**
 *      \file       test/phpunit/EasterEggLibTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the DoliBear easter egg
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/easteregg.lib.php';


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class EasterEggLibTest extends CommonClassTest
{
	/**
	 * Data provider for testDolibearIsTriggered
	 *
	 * @return array<string,array{0:array<mixed>,1:bool}>
	 */
	public static function searchesProvider()
	{
		return array(
			'exact' => array(array('dolibear'), true),
			'case and spaces' => array(array('  DoliBear '), true),
			'upper case' => array(array('DOLIBEAR'), true),
			'second field' => array(array('', 'dolibear'), true),
			'empty' => array(array('', ''), false),
			'no search' => array(array(), false),
			'other search' => array(array('dolibarr'), false),
			'contained only' => array(array('dolibear inc'), false),
			'null and array' => array(array(null, array('dolibear')), false),
		);
	}

	/**
	 * The DoliBear comes only for the exact search "dolibear"
	 *
	 * @param	array<mixed>	$searches	Searched values
	 * @param	bool			$expected	Expected result
	 * @return	void
	 *
	 * @dataProvider searchesProvider
	 */
	public function testDolibearIsTriggered($searches, $expected)
	{
		global $conf;
		$conf = $this->savconf;
		unset($conf->global->MAIN_DISABLE_EASTER_EGGS);

		$this->assertSame($expected, dolibearIsTriggered($searches));
	}

	/**
	 * The constant MAIN_DISABLE_EASTER_EGGS disables the DoliBear
	 *
	 * @return	void
	 */
	public function testDolibearCanBeDisabled()
	{
		global $conf;
		$conf = $this->savconf;

		$conf->global->MAIN_DISABLE_EASTER_EGGS = 1;
		$result = dolibearIsTriggered(array('dolibear'));
		unset($conf->global->MAIN_DISABLE_EASTER_EGGS);

		$this->assertFalse($result);
	}

	/**
	 * The template loads the script only when the DoliBear is searched
	 *
	 * @return	void
	 */
	public function testTemplateLoadsScriptOnlyWhenSearched()
	{
		global $conf;
		$conf = $this->savconf;
		unset($conf->global->MAIN_DISABLE_EASTER_EGGS);

		$search_all = 'Dolibear';
		$search_nom = '';
		ob_start();
		include DOL_DOCUMENT_ROOT.'/core/tpl/easteregg_dolibear.tpl.php';
		$output = ob_get_clean();
		$this->assertStringContainsString('/core/js/dolibear.js', $output);
		$this->assertStringContainsString('/theme/common/dolibear/dolibear.css', $output);

		$search_all = 'dolibarr';
		$search_nom = 'acme';
		ob_start();
		include DOL_DOCUMENT_ROOT.'/core/tpl/easteregg_dolibear.tpl.php';
		$output = ob_get_clean();
		$this->assertSame('', $output);
	}
}
