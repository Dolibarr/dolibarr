<?php
/* Copyright (C) 2026	Nicolas Vidal	<nicolas.vidal@atm-consulting.fr>
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
 *      \file       test/phpunit/AiLibTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for ai/lib/ai.lib.php
 */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/ai/lib/ai.lib.php';
require_once dirname(__FILE__).'/../../htdocs/ai/class/mcptool.class.php';
require_once dirname(__FILE__).'/../../htdocs/ai/tools/api_bridge.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class for PHPUnit tests
 */
class AiLibTest extends CommonClassTest
{
	/**
	 * testGetAiReasoningEffort
	 *
	 * @return void
	 */
	public function testGetAiReasoningEffort()
	{
		global $conf;

		unset($conf->global->AI_API_CUSTOM_REASONING_EFFORT);
		$this->assertSame('', getAiReasoningEffort('custom'));

		$conf->global->AI_API_CUSTOM_REASONING_EFFORT = 'None';
		$this->assertSame('none', getAiReasoningEffort('custom'));
		$this->assertSame('', getAiReasoningEffort('chatgpt'), 'The value of a service must not apply to another one');

		$conf->global->AI_API_CUSTOM_REASONING_EFFORT = 'off';
		$this->assertSame('', getAiReasoningEffort('custom'));

		unset($conf->global->AI_API_CUSTOM_REASONING_EFFORT);
	}

	/**
	 * testAiHistoryTurnForModel
	 *
	 * @return void
	 */
	public function testAiHistoryTurnForModel()
	{
		$this->assertSame('Done: api_thirdparties_create returned {"id":15}', aiHistoryTurnForModel('[api_thirdparties_create result] {"id":15}'), 'The model must not see a tool result in a format it can copy');
		$this->assertSame("Done: api_a_list returned {}\nDone: api_b_list returned []", aiHistoryTurnForModel("[api_a_list result] {}\n[api_b_list result] []"), 'Each result of a followup is rewritten');
		$this->assertSame('[earlier conversation] hello', aiHistoryTurnForModel('[earlier conversation] hello'));
		$this->assertSame('Create the third party [api_thirdparties_create result]', aiHistoryTurnForModel('Create the third party [api_thirdparties_create result]'));
	}

	/**
	 * testClassifyQueryObjects
	 *
	 * @return void
	 */
	public function testClassifyQueryObjects()
	{
		global $conf, $db, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);
		$tools = $bridge->getDefinitions();
		$langs = new Translate('', $conf);
		$langs->setDefaultLang('fr_FR');

		$this->assertSame(array('thirdparties'), classifyQueryObjects('peux tu me créer un tiers devcamp de type prospect', $langs, $tools));
		$this->assertContains('supplier_orders', classifyQueryObjects('liste les commandes fournisseurs en attente', $langs, $tools), 'Each word of a name may be plural');
		$this->assertContains('supplier_invoices', classifyQueryObjects('show the vendor invoices', $langs, $tools), 'English translations work on a French install');
		$this->assertSame(array('agendaevents'), classifyQueryObjects('mes rendez-vous de demain', $langs, $tools));
		$this->assertSame(array('thirdparties'), classifyQueryObjects('list the third parties created this week', $langs, $tools), 'A word in y may be plural in ies');
		$this->assertSame(array(), classifyQueryObjects("I don't know, I don’t see", $langs, $tools), 'A contraction is another word');
		$this->assertContains('thirdparties', classifyQueryObjects("show the customer's invoices", $langs, $tools), 'A possessive still names the object');
		$this->assertSame(array(), classifyQueryObjects('bonjour', $langs, $tools));

		$langsNl = new Translate('', $conf);
		$langsNl->setDefaultLang('nl_NL');
		$this->assertSame(array('invoices'), classifyQueryObjects('toon de onbetaalde facturen', $langsNl, $tools), 'The plural translated by Dolibarr names the object');

		$langsEs = new Translate('', $conf);
		$langsEs->setDefaultLang('es_ES');
		$this->assertContains('supplier_orders', classifyQueryObjects('lista los pedidos de proveedores', $langsEs, $tools), 'An object plus a word of its combines names the combined object');
		$this->assertNotContains('supplier_orders', classifyQueryObjects('combien de commandes clients', $langs, $tools));
		$this->assertNotContains('supplier_orders', classifyQueryObjects('liste des fournisseurs', $langs, $tools), 'The words of combines alone name nothing');

		$moduleTool = array('name' => 'api_trainings_list', 'objects' => array('trainings'), 'vocabulary' => array('trainings' => array('keys' => array('Training'))));
		$this->assertSame(array('trainings'), classifyQueryObjects('list the trainings', $langs, array($moduleTool)), 'A module names its objects with the words of its tools');

		$leaveTool = array('name' => 'api_holidays_list', 'objects' => array('holidays'), 'vocabulary' => array('holidays' => array('langs' => array('holiday'), 'keys' => array('Holidays'))));
		$this->assertSame(array('holidays'), classifyQueryObjects('qui est en congé demain', $langs, array($leaveTool)), 'A word translated in the plural matches its singular');
	}

	/**
	 * testFilterToolsByObjects
	 *
	 * @return void
	 */
	public function testFilterToolsByObjects()
	{
		$tools = array(
			array('name' => 'api_thirdparties_create', 'objects' => array('thirdparties'), 'vocabulary' => array('thirdparties' => array('keys' => array('ThirdParty'))), 'categories' => array('thirdparty')),
			array('name' => 'api_invoices_list', 'objects' => array('invoices'), 'vocabulary' => array('invoices' => array('keys' => array('Invoice'))), 'categories' => array('billing', 'thirdparty'), 'description' => 'Invoices. Look the rowid up with api_thirdparties_create first.'),
			array('name' => 'api_external_list', 'objects' => array('externalmodule'), 'categories' => array('thirdparty')),
			array('name' => 'api_contacts_list', 'objects' => array('contacts'), 'vocabulary' => array('contacts' => array('keys' => array('Contact'))), 'categories' => array('thirdparty'), 'inputSchema' => array('properties' => array('thirdparty_ids' => array('type' => 'string')))),
			array('name' => 'api_thirdparties_list', 'objects' => array('thirdparties'), 'categories' => array('thirdparty')),
			array('name' => 'navigate_to_page', 'categories' => array('global')),
			array('name' => 'report', 'categories' => array('reporting')),
		);

		$kept = array_column(filterToolsByObjects($tools, array('thirdparties'), array('thirdparty')), 'name');
		$this->assertSame(array('api_thirdparties_create', 'api_external_list', 'api_thirdparties_list', 'navigate_to_page'), $kept);

		$kept = array_column(filterToolsByObjects($tools, array('contacts'), array()), 'name');
		$this->assertSame(array('api_contacts_list', 'navigate_to_page', 'api_thirdparties_list'), $kept, 'A rowid argument brings the tool that finds the rowid');

		$kept = array_column(filterToolsByObjects($tools, array('invoices'), array('billing')), 'name');
		$this->assertSame(array('api_invoices_list', 'navigate_to_page', 'api_thirdparties_create'), $kept, 'A tool named by a kept tool is kept too');

		$this->assertSame(array(), filterToolsByObjects($tools, array('tickets'), array('thirdparty')), 'No tool for the objects named: the caller falls back to the categories');
	}
}
