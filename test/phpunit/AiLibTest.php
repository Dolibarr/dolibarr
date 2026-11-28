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
	 * testClassifyQueryObjectsSocid
	 *
	 * @return void
	 */
	public function testClassifyQueryObjectsSocid()
	{
		global $conf, $db, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);
		$tools = $bridge->getDefinitions();
		$langs = new Translate('', $conf);
		$langs->setDefaultLang('fr_FR');

		$this->assertSame(array('thirdparties'), classifyQueryObjects('combien me doit socid:12 ?', $langs, $tools), 'A third party replaced by the privacy guard is still a third party');
		$this->assertSame(array(), classifyQueryObjects('combien me doit socid:12 ?', $langs, array()), 'No thirdparty tool given, no object');
	}

	/**
	 * testAiHistoryObjects
	 *
	 * @return void
	 */
	public function testAiHistoryObjects()
	{
		global $conf, $db, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);
		$tools = $bridge->getDefinitions();
		$langs = new Translate('', $conf);
		$langs->setDefaultLang('fr_FR');

		$this->assertSame(array('contacts'), aiHistoryObjects(array(array('role' => 'assistant', 'text' => '[api_contacts_create result] {"id":5}')), $langs, $tools));
		$this->assertSame(array(), aiHistoryObjects(array(array('role' => 'assistant', 'text' => '[api_unknown_tool result] {}')), $langs, $tools), 'An unknown tool name gives nothing');
		$this->assertSame(array(), aiHistoryObjects(array(array('role' => 'assistant', 'text' => '[api_objects_contacts_list result] []')), $langs, $tools), 'A tool of several objects gives nothing');
		$this->assertSame(array(), aiHistoryObjects(array(array('role' => 'assistant', 'text' => 'Voici les contacts du tiers.')), $langs, $tools), 'Only the tool name of an answer counts');
		$this->assertSame(array(), aiHistoryObjects(array(array('role' => 'assistant', 'text' => '[api_contacts_create result] {}')), $langs, array()), 'A tool the user may not use gives nothing');
		$this->assertSame(array('thirdparties', 'contacts'), aiHistoryObjects(array(array('role' => 'assistant', 'text' => "[api_thirdparties_list result] []\n[api_contacts_create result] {\"id\":5}")), $langs, $tools), 'Each result of a followup answer counts');

		$turns = array(
			array('role' => 'user', 'text' => 'ajoute le contacts Jean Martin au tier devcamp'),
			array('role' => 'assistant', 'text' => '[api_contacts_create result] {"id":5}'),
		);
		$objects = aiHistoryObjects($turns, $langs, $tools);
		sort($objects);
		$this->assertSame(array('contacts', 'thirdparties'), $objects, 'The question of the previous turn is classified too');
	}

	/**
	 * testAiPageContextObjects
	 *
	 * @return void
	 */
	public function testAiPageContextObjects()
	{
		global $conf, $db, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);
		$tools = $bridge->getDefinitions();

		$this->assertSame(array('thirdparties'), aiPageContextObjects('societe', $tools));
		$this->assertSame(array('contacts'), aiPageContextObjects('contact', $tools));
		$this->assertSame(array('invoices'), aiPageContextObjects('facture', $tools));
		$this->assertSame(array('supplier_orders'), aiPageContextObjects('order_supplier', $tools));
		$this->assertSame(array(), aiPageContextObjects('user', $tools), 'An element of no object gives nothing');
		$this->assertSame(array(), aiPageContextObjects('', $tools));

		$moduleTool = array('name' => 'api_trainings_list', 'objects' => array('trainings'), 'vocabulary' => array('trainings' => array('keys' => array('Training'), 'element' => 'training')));
		$this->assertSame(array('trainings'), aiPageContextObjects('training', array($moduleTool)), 'A module maps its card to its object with the element of its tools');
	}

	/**
	 * testAiCatalogFirstObjectsLine
	 *
	 * @return void
	 */
	public function testAiCatalogFirstObjectsLine()
	{
		global $conf, $db, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);
		$tools = $bridge->getDefinitions();
		$langs = new Translate('', $conf);
		$langs->setDefaultLang('fr_FR');

		$question = 'ajoute à Jean Martin du tiers acme son numéro de téléphone 01 02 03 04 05 et son mail jean@example.com';
		$line = aiCatalogFirstObjectsLine($question, '', array(), $langs, $tools);
		$this->assertSame('Objects found in the question: contacts, thirdparties.', $line);
		$this->assertSame($line, aiCatalogFirstObjectsLine($question, '', array(), $langs, $tools), 'Every round of a question sends the same bytes');
		$this->assertSame('', aiCatalogFirstObjectsLine('bonjour', '', array(), $langs, $tools));
		$this->assertSame('', aiCatalogFirstObjectsLine('les tiers, les factures, les commandes, les contacts, les produits', '', array(), $langs, $tools), 'More objects than a load_tools may ask: no line');

		$replayed = array(
			array('role' => 'user', 'text' => "quel est son numéro de téléphone ?\n\n## Date\nCurrent date.\nObjects found in the question: contacts, thirdparties.\n\n## Rules\n* the user asked for a search", 'sent' => true),
			array('role' => 'assistant', 'text' => '(no answer)'),
		);
		$this->assertSame('', aiCatalogFirstObjectsLine('bonjour', '', $replayed, $langs, $tools), 'The tail of a replayed question is not the words of the user');
		$this->assertSame('Objects found in the question: invoices.', aiCatalogFirstObjectsLine('crée une facture', '', $replayed, $langs, $tools));

		$conf->global->AI_DISABLE_TOOLS_BY_OBJECT = 1;
		$this->assertSame('', aiCatalogFirstObjectsLine($question, '', array(), $langs, $tools));
		unset($conf->global->AI_DISABLE_TOOLS_BY_OBJECT);
	}

	/**
	 * testAiQueryFieldObjects
	 *
	 * @return void
	 */
	public function testAiQueryFieldObjects()
	{
		global $conf, $db, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);
		$tools = $bridge->getDefinitions();
		$both = array('contacts', 'thirdparties');
		$byLang = array();
		foreach (array('fr_FR', 'es_ES', 'de_DE', 'it_IT', 'nl_NL') as $lang) {
			$byLang[$lang] = new Translate('', $conf);
			$byLang[$lang]->setDefaultLang($lang);
		}
		$sorted = static function (array $objects): array {
			sort($objects);
			return $objects;
		};

		$this->assertSame($both, $sorted(aiQueryFieldObjects('ajoute le numero de telephone 06 12 34 56 78 et le mail jean@example.com a Jean Martin de devcamp', $byLang['fr_FR'], $tools)));
		$this->assertSame($both, $sorted(aiQueryFieldObjects('ajoute son numéro de téléphone', $byLang['fr_FR'], $tools)), 'A field word alone is enough');
		$this->assertSame($both, $sorted(aiQueryFieldObjects('ajoute son mail', $byLang['fr_FR'], $tools)));
		$this->assertSame($both, $sorted(aiQueryFieldObjects('set the phone of Jean Martin', $byLang['fr_FR'], $tools)), 'English words work on any install');
		$this->assertSame($both, $sorted(aiQueryFieldObjects('cambia el teléfono de Jean', $byLang['es_ES'], $tools)));
		$this->assertSame($both, $sorted(aiQueryFieldObjects('ändere das Telefon von Jean', $byLang['de_DE'], $tools)));
		$this->assertSame($both, $sorted(aiQueryFieldObjects('aggiorna il telefono di Jean', $byLang['it_IT'], $tools)));
		$this->assertSame($both, $sorted(aiQueryFieldObjects('wijzig het telefoonnummer van Jean', $byLang['nl_NL'], $tools)));
		$this->assertSame($both, $sorted(aiQueryFieldObjects('jean@example.com', $byLang['fr_FR'], $tools)), 'An email address names the fields without a word');
		$this->assertSame($both, $sorted(aiQueryFieldObjects('ajoute +33 6 12 34 56 78 a Jean', $byLang['fr_FR'], $tools)));
		$this->assertSame($both, $sorted(aiQueryFieldObjects('ajoute [[PHONE_11c8e]] a Jean', $byLang['fr_FR'], $tools)), 'A value masked by the privacy guard keeps its shape');
		$this->assertSame($both, $sorted(aiQueryFieldObjects('ajoute [[EMAIL_2a1b3]] a Jean', $byLang['fr_FR'], $tools)));
		$this->assertSame($both, $sorted(aiQueryFieldObjects('remplace par [[ADDR_37f0d]] pour Jean', $byLang['fr_FR'], $tools)));
		$this->assertSame($both, $sorted(aiQueryFieldObjects('combien pour 2101234567 ?', $byLang['fr_FR'], $tools)), 'Accepted false positive: 10 digits starting with 2x are a Greek phone for the guard');
		$this->assertSame(array(), aiQueryFieldObjects('bonjour', $byLang['fr_FR'], $tools));
		$this->assertSame(array(), aiQueryFieldObjects('quel est le total du 2026-11-27 pour FA2611-0042 ?', $byLang['fr_FR'], $tools), 'A date or a reference is not a phone');
		$this->assertSame(array(), aiQueryFieldObjects('jean@example.com', $byLang['fr_FR'], array()), 'No tool with fields, no object');

		$moduleTool = array('name' => 'api_members_list', 'objects' => array('members'), 'vocabulary' => array('members' => array('keys' => array('Member'), 'fields' => array('keys' => array('Phone')))));
		$langsFresh = new Translate('', $conf);
		$langsFresh->setDefaultLang('fr_FR');
		$this->assertSame(array('members'), aiQueryFieldObjects('change le téléphone de Jean', $langsFresh, array($moduleTool)), 'Field keys resolve without companies in the langs of the object');

		$this->assertSame(array('contacts'), aiQueryObjects('ajoute le téléphone du contact Jean Martin', '', array(), $byLang['fr_FR'], $tools), 'A named object wins over the field words');
		$this->assertSame(array('thirdparties'), aiQueryObjects('ajoute son téléphone', 'societe', array(), $byLang['fr_FR'], $tools), 'The open card wins over the field words');
		$this->assertSame($both, $sorted(aiQueryObjects('ajoute son téléphone', '', array(), $byLang['fr_FR'], $tools)));
	}

	/**
	 * testAiChatHistoryTurns
	 *
	 * @return void
	 */
	public function testAiChatHistoryTurns()
	{
		global $db, $user;

		$idOwn = $this->createAiChatMessage((int) $user->id, 'assistant', '[api_contacts_create result] {"id":5}');
		$idOther = $this->createAiChatMessage((int) $user->id + 1000, 'user', 'question of another user');
		$idSystem = $this->createAiChatMessage((int) $user->id, 'system', 'stored with another role');

		$turns = aiChatHistoryTurns($db, $user, array(
			array('id' => $idOwn, 'role' => 'user', 'text' => 'sent by the client'),
			array('id' => $idOther),
			array('role' => 'user', 'text' => 'not yet saved'),
			'garbage',
		));
		$this->assertSame(array(
			array('role' => 'assistant', 'text' => '[api_contacts_create result] {"id":5}'),
			array('role' => 'user', 'text' => 'not yet saved'),
		), $turns, 'A saved turn is read from the database, only from a conversation of the user');

		$this->assertSame(array(array('role' => 'user', 'text' => 'stored with another role')), aiChatHistoryTurns($db, $user, array(array('id' => $idSystem))), 'A role other than assistant comes back as user');
	}

	/**
	 * Create a message of a chat conversation
	 *
	 * @param	int		$fkUser		Owner of the conversation
	 * @param	string	$role		user or assistant
	 * @param	string	$text		Content
	 * @return	int					Id of the message
	 */
	private function createAiChatMessage(int $fkUser, string $role, string $text): int
	{
		global $db;

		$now = $db->idate(dol_now());
		$db->query("INSERT INTO ".$db->prefix()."ai_chat_conversation (entity, fk_user, title, date_creation) VALUES (".((int) getEntity('ai')).", ".$fkUser.", 'test', '".$now."')");
		$fkConversation = $db->last_insert_id($db->prefix().'ai_chat_conversation');
		$db->query("INSERT INTO ".$db->prefix()."ai_chat_message (fk_conversation, role, content_raw, datec) VALUES (".((int) $fkConversation).", '".$db->escape($role)."', '".$db->escape($text)."', '".$now."')");
		$id = $db->last_insert_id($db->prefix().'ai_chat_message');
		$this->assertGreaterThan(0, $id);

		return (int) $id;
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

		$kept = array_column(filterToolsByObjects($tools, array(), array(), array('api_contacts_list')), 'name');
		$this->assertSame(array('api_contacts_list', 'navigate_to_page', 'api_thirdparties_list'), $kept, 'A tool asked by name brings the tool that finds its rowids, not the other tools of its object');
	}

	/**
	 * testFilterToolsByObjectsAddsListToolForId
	 *
	 * @return void
	 */
	public function testFilterToolsByObjectsAddsListToolForId()
	{
		$contacts = array('contacts');
		$tools = array(
			array('name' => 'api_contacts_update', 'objects' => $contacts, 'categories' => array('thirdparty'), 'inputSchema' => array('properties' => array('id' => array('type' => 'integer'), 'phone' => array('type' => 'string')))),
			array('name' => 'api_contacts_create', 'objects' => $contacts, 'categories' => array('thirdparty'), 'inputSchema' => array('properties' => array('lastname' => array('type' => 'string')))),
			array('name' => 'api_contacts_list', 'objects' => $contacts, 'categories' => array('thirdparty'), 'inputSchema' => array('properties' => array('thirdparty_ids' => array('type' => 'string')))),
			array('name' => 'api_thirdparties_list', 'objects' => array('thirdparties'), 'categories' => array('thirdparty')),
			array('name' => 'navigate_to_page', 'categories' => array('global')),
		);

		$kept = array_column(filterToolsByObjects($tools, array(), array(), array('api_contacts_update')), 'name');
		$this->assertSame(array('api_contacts_update', 'navigate_to_page', 'api_contacts_list'), $kept, 'A tool asked by name with an id property brings the list tool of its object, not the tools that list tool names');
		$this->assertSame(json_encode($kept), json_encode(array_column(filterToolsByObjects($tools, array(), array(), array('api_contacts_update')), 'name')), 'Same request, same set');

		$kept = array_column(filterToolsByObjects($tools, array(), array(), array('api_contacts_create')), 'name');
		$this->assertNotContains('api_contacts_list', $kept, 'No id taken: no list tool added');

		$kept = array_column(filterToolsByObjects($tools, array(), array(), array('api_contacts_update', 'api_contacts_list')), 'name');
		$this->assertSame(array('api_contacts_update', 'api_contacts_list', 'navigate_to_page', 'api_thirdparties_list'), $kept, 'List tool already asked: nothing added twice');

		$withoutList = array_values(array_filter($tools, static function (array $tool): bool {
			return $tool['name'] !== 'api_contacts_list';
		}));
		$kept = array_column(filterToolsByObjects($withoutList, array(), array(), array('api_contacts_update')), 'name');
		$this->assertSame(array('api_contacts_update', 'navigate_to_page'), $kept, 'A user without the list tool gets none');
	}

	/**
	 * testSortAiToolsByObjectBlock
	 *
	 * @return void
	 */
	public function testSortAiToolsByObjectBlock()
	{
		global $conf, $db, $user;

		$tools = array(
			array('name' => 'navigate_to_page'),
			array('name' => 'api_objects_categories_list', 'objects' => array('contacts', 'thirdparties')),
			array('name' => 'api_invoices_list', 'objects' => array('invoices')),
			array('name' => 'api_agendaevents_list', 'objects' => array('agendaevents')),
			array('name' => 'api_users_list', 'objects' => array('users')),
			array('name' => 'api_contacts_list', 'objects' => array('contacts')),
			array('name' => 'api_thirdparties_list', 'objects' => array('thirdparties')),
		);
		$this->assertSame(array_reverse(array_column($tools, 'name')), array_column(sortAiToolsByObjectBlock($tools), 'name'), 'Hub objects, other objects by key, several objects, no object');

		$bridge = new ToolApiBridge($db, $user, $conf);
		$tools = $bridge->getDefinitions();

		$thirdparties = cleanToolSchemaForLLM(sortAiToolsByObjectBlock(filterToolsByObjects($tools, array('thirdparties'), array())));
		$this->assertSame(json_encode($thirdparties), json_encode(cleanToolSchemaForLLM(sortAiToolsByObjectBlock(filterToolsByObjects(array_reverse($tools), array('thirdparties'), array())))), 'The same set gives the same bytes');

		$nbThirdpartiesBlock = 0;
		foreach (sortAiToolsByObjectBlock(filterToolsByObjects($tools, array('thirdparties'), array())) as $tool) {
			if ((array) ($tool['objects'] ?? array()) !== array('thirdparties')) {
				break;
			}
			$nbThirdpartiesBlock++;
		}
		$this->assertGreaterThan(0, $nbThirdpartiesBlock);
		$thirdpartiesBlockJson = rtrim((string) json_encode(array_slice($thirdparties, 0, $nbThirdpartiesBlock)), ']');
		$withContacts = cleanToolSchemaForLLM(sortAiToolsByObjectBlock(filterToolsByObjects($tools, array('contacts', 'thirdparties'), array())));
		$this->assertStringStartsWith($thirdpartiesBlockJson.',', (string) json_encode($withContacts), 'Adding an object keeps the thirdparties block first, so the prompt cache still holds it');
	}
	/**
	 * testAiSelectTools
	 *
	 * @return void
	 */
	public function testAiSelectTools()
	{
		global $conf, $db, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);
		$tools = array_merge($bridge->getDefinitions(), array(array('name' => 'navigate_to_page', 'description' => 'Open a page', 'categories' => array('global'))));
		$langs = new Translate('', $conf);
		$langs->setDefaultLang('fr_FR');
		$knownObjects = array_keys(getAiToolsVocabulary($tools));
		$objectToolsOf = static function (array $selected) use ($knownObjects): array {
			return array_values(array_filter(array_column($selected, 'objects'), static function ($objects) use ($knownObjects): bool {
				return count(array_intersect((array) $objects, $knownObjects)) > 0;
			}));
		};

		$selection = aiSelectTools($tools, array('query' => 'bonjour'), array(), 32000, $langs);
		$this->assertSame('catalog', $selection['mode'], 'A question of no object never sends the whole catalog');
		$this->assertSame(array(), $selection['objects']);
		$this->assertSame(array(), $objectToolsOf($selection['tools']));
		$this->assertSame(array('navigate_to_page'), array_column($selection['tools'], 'name'), 'Tools of no object stay');

		$selection = aiSelectTools($tools, array('query' => 'bonjour'), array(), 0, $langs);
		$this->assertSame('categories', $selection['mode'], 'Size 0 keeps the behaviour without a budget');
		$this->assertSame(array_column(filterToolsProfessional($tools, array()), 'name'), array_column($selection['tools'], 'name'));

		$selection = aiSelectTools($tools, array('query' => 'crée le tiers devcamp'), array('thirdparty'), 32000, $langs);
		$this->assertSame('objects', $selection['mode']);
		$this->assertSame(array('thirdparties'), $selection['objects']);
		$this->assertSame(array_column(filterToolsByObjects($tools, array('thirdparties'), array('thirdparty')), 'name'), array_column($selection['tools'], 'name'));

		$selection = aiSelectTools($tools, array('query' => 'les commandes du client devcamp'), array(), 32000, $langs);
		$this->assertGreaterThan(32000, aiToolsSize($selection['tools']));
		$this->assertSame('objects', $selection['mode'], 'At most aiLoadToolsMaxObjects() objects found by the code are sent whatever their size');
		$this->assertSame(array('thirdparties', 'orders'), $selection['objects']);

		$oldTurn = array('role' => 'assistant', 'text' => "[api_invoices_list result] []\n[api_orders_list result] []\n[api_proposals_list result] []\n[api_products_list result] []");
		$newTurn = array('role' => 'assistant', 'text' => '[api_contacts_list result] []');
		$contactsSize = aiToolsSize(filterToolsByObjects($tools, array('contacts'), array()));
		$selection = aiSelectTools($tools, array('query' => '', 'turns' => array($oldTurn, $newTurn)), array(), $contactsSize, $langs);
		$this->assertSame('objects', $selection['mode']);
		$this->assertSame(array('contacts'), $selection['objects'], 'Over budget the objects of the past turns go first, the oldest first, even when the question names none');
		$selection = aiSelectTools($tools, array('query' => '', 'turns' => array($oldTurn, $newTurn)), array(), 0, $langs);
		$this->assertCount(5, $selection['objects'], 'Without budget the past turns all count');

		$selection = aiSelectTools($tools, array('query' => 'factures, commandes, propositions commerciales, produits et contacts'), array(), 1000, $langs);
		$this->assertGreaterThan(aiLoadToolsMaxObjects(), count(classifyQueryObjects('factures, commandes, propositions commerciales, produits et contacts', $langs, $tools)));
		$this->assertSame('catalog', $selection['mode'], 'More objects than load_tools may ask, over budget, give the catalog');
		$this->assertSame(array('navigate_to_page'), array_column($selection['tools'], 'name'));

		$selection = aiSelectTools($tools, array('query' => 'factures, commandes, propositions commerciales, produits et contacts', 'turns' => array(array('role' => 'assistant', 'text' => '[api_invoices_list result] []'))), array(), 1000, $langs);
		$this->assertSame('catalog', $selection['mode'], 'Past turns that add no object are not dropped one by one');

		$noVocabularyTools = array(array('name' => 'navigate_to_page', 'description' => 'Open a page', 'categories' => array('global')), array('name' => 'make_coffee', 'description' => 'Coffee', 'categories' => array('office')));
		$selection = aiSelectTools($noVocabularyTools, array('query' => 'bonjour'), array(), 10, $langs);
		$this->assertSame('categories', $selection['mode'], 'Without any object known, no catalog can be offered: the budget is skipped');
		$this->assertSame(array_column($noVocabularyTools, 'name'), array_column($selection['tools'], 'name'));

		$selection = aiSelectTools($tools, array('query' => 'bonjour'), array(), 10, $langs);
		$this->assertSame(array('tools' => array(), 'objects' => array(), 'mode' => 'catalog'), $selection, 'Tools of no object over budget are not sent either');

		$selection = aiSelectTools($tools, array('query' => 'bonjour', 'requested' => array('contacts')), array(), 1000, $langs);
		$this->assertSame('objects', $selection['mode'], 'Objects asked with load_tools are sent whatever their size');
		$this->assertSame(array('contacts'), $selection['objects']);

		$selection = aiSelectTools($tools, array('query' => 'Γεια σου', 'latin' => false), array(), 32000, $langs);
		$this->assertSame('catalog', $selection['mode']);
		$selection = aiSelectTools($tools, array('query' => 'Γεια σου jean@example.com', 'latin' => false), array(), 32000, $langs);
		$objects = $selection['objects'];
		sort($objects);
		$this->assertSame(array('contacts', 'thirdparties'), $objects, 'A value shape gives its objects in any script');
		$selection = aiSelectTools($tools, array('query' => 'téléphone', 'latin' => false), array(), 32000, $langs);
		$this->assertSame('catalog', $selection['mode'], 'Field words are matched in a Latin script only');
		$selection = aiSelectTools($tools, array('query' => 'Γεια σου', 'latin' => false, 'element' => 'societe'), array(), 32000, $langs);
		$this->assertSame(array('thirdparties'), $selection['objects'], 'The open card gives its object in any script');

		$smallTools = array(
			array('name' => 'bill_a', 'description' => 'A', 'categories' => array('billing')),
			array('name' => 'bill_b', 'description' => 'B', 'categories' => array('billing')),
			array('name' => 'bill_c', 'description' => 'C', 'categories' => array('billing')),
			array('name' => 'stock_a', 'description' => 'D', 'categories' => array('stock')),
		);
		$selection = aiSelectTools($smallTools, array('query' => 'facture'), array('billing'), 32000, $langs);
		$this->assertSame('categories', $selection['mode'], 'A category under budget still passes');
		$this->assertSame(array('bill_a', 'bill_b', 'bill_c'), array_column($selection['tools'], 'name'));

		$question = 'crée moi un contact jonathan lescaut sur devcamp';
		$selection = aiSelectTools($tools, array('query' => $question, 'catalog_first' => true), array('thirdparty'), 32000, $langs);
		$this->assertSame('catalog', $selection['mode'], 'Catalog first: no tool of an object before the model chose');
		$this->assertSame(array('navigate_to_page'), array_column($selection['tools'], 'name'));
		$selection = aiSelectTools($tools, array('query' => $question, 'catalog_first' => true), array('thirdparty'), 0, $langs);
		$this->assertSame('catalog', $selection['mode'], 'Catalog first does not depend on the budget');
		$this->assertSame(array('navigate_to_page'), array_column(aiCatalogFirstBaseTools($tools, 0), 'name'), 'The tools of every round, whatever the question');
		$selection = aiSelectTools($tools, array('query' => $question, 'catalog_first' => true, 'requested_tools' => array('api_contacts_create')), array('thirdparty'), 32000, $langs);
		$this->assertSame('tools', $selection['mode']);
		$names = array_column($selection['tools'], 'name');
		$this->assertContains('api_contacts_create', $names);
		$this->assertContains('api_thirdparties_list', $names, 'The tool that finds the socid of the contact comes with it');
		$this->assertNotContains('api_contacts_list', $names, 'Only the tools asked, not all the tools of their object');
		$this->assertSame(array_column(filterToolsByObjects($tools, array(), array(), array('api_contacts_create')), 'name'), $names);

		$conf->global->AI_DISABLE_TOOLS_BY_OBJECT = 1;
		$selectionNoBudget = aiSelectTools($tools, array('query' => 'crée le tiers devcamp'), array('thirdparty'), 0, $langs);
		$selection = aiSelectTools($tools, array('query' => 'crée le tiers devcamp'), array('thirdparty'), 32000, $langs);
		unset($conf->global->AI_DISABLE_TOOLS_BY_OBJECT);
		$this->assertSame('categories', $selectionNoBudget['mode'], 'AI_DISABLE_TOOLS_BY_OBJECT still sends the tools by category');
		$this->assertSame('catalog', $selection['mode'], 'AI_DISABLE_TOOLS_BY_OBJECT keeps the budget: a category over it gives the catalog');
	}

	/**
	 * testAiToolsCatalog
	 *
	 * @return void
	 */
	public function testAiToolsCatalog()
	{
		global $conf, $db, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);
		$thirdpartyTools = filterToolsByObjects($bridge->getDefinitions(), array('thirdparties'), array());
		$catalog = aiToolsCatalog($thirdpartyTools);
		$lines = explode("\n", $catalog);
		$this->assertCount(count(getAiToolsVocabulary($thirdpartyTools)), $lines, 'One line per object');
		$this->assertContains('thirdparties: third parties (customers, prospects, suppliers)', $lines);
		$this->assertStringNotContainsString('invoices', $catalog, 'Only the objects of the tools given');

		$moduleTool = array('name' => 'api_trainings_list', 'objects' => array('trainings'), 'vocabulary' => array('trainings' => array('keys' => array('Training'))));
		$this->assertSame('trainings', aiToolsCatalog(array($moduleTool)), 'An object without label is listed by its key');
		$this->assertSame('', aiToolsCatalog(array(array('name' => 'navigate_to_page'))));

		$prompt = aiToolsCatalogPrompt($thirdpartyTools);
		$this->assertStringContainsString('load_tools', $prompt);
		$this->assertStringContainsString('ask_for_clarification', $prompt, 'The model may ask the user which object a name is');
		$this->assertStringContainsString('comes before any rule above about a missing tool', $prompt, 'A small model must ask for tools before saying one is missing');
		$this->assertStringContainsString($catalog, $prompt);
	}

	/**
	 * testAiToolsCatalogWithActions
	 *
	 * @return void
	 */
	public function testAiToolsCatalogWithActions()
	{
		global $conf, $db, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);
		$tools = array_merge($bridge->getDefinitions(), array(array('name' => 'api_objects_contacts_link', 'objects' => array('tickets'))));
		$catalog = aiToolsCatalog($tools, true);
		$this->assertSame($catalog, aiToolsCatalog(array_reverse($tools), true), 'The same tools give the same bytes, whatever their order');
		$this->assertMatchesRegularExpression('/^contacts: [^\n]*\bactions: [^\n]*\bcreate\b/m', $catalog, 'An action is the end of the name of the tool');
		$this->assertMatchesRegularExpression('/^tickets: [^\n]*\bapi_objects_contacts_link\b/m', $catalog, 'A tool not named after its object is given by its full name');
		$this->assertCount(count(getAiToolsVocabulary($tools)), explode("\n", $catalog), 'One line per object');
		$this->assertStringNotContainsString('actions:', aiToolsCatalog($tools), 'Without actions, the catalog of task 4 is unchanged');

		$withOther = array_merge($tools, array(array('name' => 'make_coffee', 'categories' => array('office')), array('name' => 'brew_tea', 'categories' => array('office')), array('name' => 'navigate_to_page', 'categories' => array('global'))));
		$catalogWithOther = aiToolsCatalog($withOther, true);
		$this->assertStringEndsWith("\nother - actions: brew_tea, make_coffee, navigate_to_page", $catalogWithOther, 'A tool of no object can be asked by its name, even when the budget sends none of them');
		$this->assertStringEndsWith("\nother - actions: brew_tea, make_coffee", aiToolsCatalog($withOther, true, array('navigate_to_page')), 'A tool sent in every round is not offered again');
		$this->assertSame($catalogWithOther, aiToolsCatalog(array_reverse($withOther), true));
		$this->assertStringNotContainsString('other - actions', aiToolsCatalog($withOther), 'Only the catalog with actions lists tools');

		$prompt = aiToolsCatalogPrompt($tools, true);
		$this->assertStringContainsString($catalog, $prompt);
		$this->assertStringContainsString('"tools"', $prompt, 'The model asks tools by name');
		$this->assertStringContainsString('at most '.aiLoadToolsMaxTools().' ', $prompt);
		$this->assertSame($prompt, aiToolsCatalogPrompt(array_reverse($tools), true), 'Round 1 and round 2 share this prefix');
		$this->assertStringContainsString('Objects found in the question', $prompt);
	}

	/**
	 * testAiLoadToolsNames
	 *
	 * @return void
	 */
	public function testAiLoadToolsNames()
	{
		global $conf, $db, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);
		$tools = $bridge->getDefinitions();

		$this->assertSame(array('api_contacts_create', 'api_thirdparties_list'), aiLoadToolsNames(array('api_contacts_create', 'api_unknown_list', 'api_thirdparties_list', 'api_contacts_create', 12, array('x')), $tools, false), 'Unknown names are dropped');
		$many = array('api_contacts_create', 'api_contacts_list', 'api_contacts_get', 'api_thirdparties_list', 'api_thirdparties_get', 'api_invoices_list', 'api_orders_list');
		$this->assertSame(array_slice($many, 0, aiLoadToolsMaxTools()), aiLoadToolsNames($many, $tools, false), 'At most aiLoadToolsMaxTools() tools');
		$this->assertSame(6, aiLoadToolsMaxTools());
		$this->assertSame(array(), aiLoadToolsNames(array('api_contacts_create'), $tools, true), 'One load_tools per question');
		$this->assertSame(array(), aiLoadToolsNames('api_contacts_create', $tools, false));
		$this->assertSame(array('api_contacts_update'), aiLoadToolsNames(array('contacts_update'), $tools, false), 'A name built from the catalog without its api_ prefix');
		$withoutContactCreate = array_values(array_filter($tools, static function (array $tool): bool {
			return $tool['name'] !== 'api_contacts_create';
		}));
		$this->assertSame(array('api_contacts_list'), aiLoadToolsNames(array('api_contacts_create', 'api_contacts_list'), $withoutContactCreate, false), 'A tool the user may not use is refused');
	}

	/**
	 * testAiToolsCatalogFirst
	 *
	 * @return void
	 */
	public function testAiToolsCatalogFirst()
	{
		global $conf;

		unset($conf->global->AI_ASSISTANT_TOOLS_CATALOG_FIRST);
		$this->assertTrue(aiToolsCatalogFirst('custom'), 'On by default for a local model');
		$this->assertFalse(aiToolsCatalogFirst('chatgpt'), 'Off by default for an online service');
		$this->assertFalse(aiToolsCatalogFirst('anthropic'));

		$conf->global->AI_ASSISTANT_TOOLS_CATALOG_FIRST = '1';
		$this->assertTrue(aiToolsCatalogFirst('chatgpt'));
		$conf->global->AI_ASSISTANT_TOOLS_CATALOG_FIRST = '0';
		$this->assertFalse(aiToolsCatalogFirst('custom'));

		unset($conf->global->AI_ASSISTANT_TOOLS_CATALOG_FIRST);
	}

	/**
	 * testAiLoadToolsObjects
	 *
	 * @return void
	 */
	public function testAiLoadToolsObjects()
	{
		global $conf, $db, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);
		$tools = $bridge->getDefinitions();

		$this->assertSame(array('contacts', 'thirdparties'), aiLoadToolsObjects(array('contacts', 'unknown', 'thirdparties', 'contacts', 12, array('x')), $tools, false), 'Unknown keys are dropped');
		$this->assertSame(array('contacts', 'thirdparties', 'invoices', 'products'), aiLoadToolsObjects(array('contacts', 'thirdparties', 'invoices', 'products', 'users', 'proposals'), $tools, false), 'At most 4 objects');
		$this->assertSame(array(), aiLoadToolsObjects(array('contacts'), $tools, true), 'One load_tools per question');
		$this->assertSame(array(), aiLoadToolsObjects('contacts', $tools, false));
		$this->assertSame(array(), aiLoadToolsObjects(array('contacts'), array(), false), 'An object of no tool the user may use is refused');
	}

	/**
	 * testAiCatalogFirstMessages
	 *
	 * @return void
	 */
	public function testAiCatalogFirstMessages()
	{
		$this->assertSame('Current date and time (Europe/Paris): 2026-11-29 00:00.', aiCatalogFirstDateLine('Europe/Paris', (int) strtotime('2026-11-28T23:30:00Z')), 'The local date of the user, to the hour: right "today" around midnight');
		$this->assertSame('Current date and time (UTC): 2026-11-28 23:00.', aiCatalogFirstDateLine('Not/AZone', (int) strtotime('2026-11-28T23:30:00Z')));

		$flatten = static function (array $messages): array {
			return array_merge($messages['history'], array(array('role' => 'user', 'text' => $messages['user'])));
		};
		$past = array(array('role' => 'user', 'text' => 'liste les tiers'), array('role' => 'assistant', 'text' => 'Done: api_thirdparties_list returned []'));
		$question = "crée moi un contact jonathan lescaut sur devcamp\n\n## Date\nCurrent date: 2026-11-28";
		$loadToolsAnswer = aiLoadToolsAnswer(array(), array('api_contacts_create'));
		$this->assertSame('{"tool":"load_tools","arguments":{"objects":[],"tools":["api_contacts_create"]}}', $loadToolsAnswer);
		$toolsMessage = aiCatalogFirstToolsMessage(array(array('name' => 'api_contacts_create', 'description' => 'Create a contact', 'parameters' => array())));
		$this->assertStringStartsWith("## Tools:\n[{\"name\":\"api_contacts_create\"", $toolsMessage);

		$opening = array(
			array('role' => 'user', 'text' => 'Start of the conversation.'),
			array('role' => 'assistant', 'text' => '{"tool":"respond_to_user","arguments":{"message":"Ready."}}'),
		);
		$this->assertSame($opening, aiConversationOpening());
		$round1 = aiCatalogFirstMessages($past, $question, '', '', array());
		$this->assertSame(array('history' => array_merge($opening, $past), 'user' => $question, 'attachments' => array()), $round1, 'Every conversation starts with the same opening turns');
		$this->assertSame(aiCatalogFirstMessages($past, $question, '', '', array()), $round1);
		$round2 = aiCatalogFirstMessages($past, $question, $loadToolsAnswer, $toolsMessage, array());
		$this->assertSame($flatten($round1), array_slice($flatten($round2), 0, count($flatten($round1))), 'Round 2 only appends to round 1: a model with sliding window attention reuses its cache only then');
		$this->assertSame($toolsMessage, $round2['user']);
		$step2 = aiCatalogFirstMessages($past, $question, $loadToolsAnswer, $toolsMessage, array('Done: api_thirdparties_list returned [{"id":3}]'));
		$this->assertSame($flatten($round2), array_slice($flatten($step2), 0, count($flatten($round2))), 'A followup round only appends too');
		$this->assertSame(aiFollowupStepText(1), $step2['user']);
		$step3 = aiCatalogFirstMessages($past, $question, $loadToolsAnswer, $toolsMessage, array('Done: api_thirdparties_list returned [{"id":3}]', 'Done: api_contacts_list returned []'));
		$this->assertSame($flatten($step2), array_slice($flatten($step3), 0, count($flatten($step2))));
		$this->assertSame(aiFollowupStepText(2), $step3['user']);
		$this->assertStringContainsString('LAST STEP', aiFollowupStepText(2));
		$this->assertStringContainsString('the LAST question of the user', aiFollowupStepText(1), 'The step instruction is the last message, it must point at the question');

		$this->assertStringNotContainsString('## Tools:', aiCatalogFirstToolsMessage(array()), 'No empty tool list when every tool asked was already sent');
		$attachments = array(array('mime' => 'image/png', 'data' => 'iVBORw0KGgo='));
		$round1WithFile = aiCatalogFirstMessages($past, $question, '', '', array(), $attachments);
		$this->assertSame($attachments, $round1WithFile['attachments'], 'Round 1: the files go with the question, the last message');
		$round2WithFile = aiCatalogFirstMessages($past, $question, $loadToolsAnswer, $toolsMessage, array(), $attachments);
		$this->assertSame(array(), $round2WithFile['attachments']);
		$this->assertSame($attachments, $round2WithFile['history'][count($opening) + count($past)]['attachments'], 'Round 2: the files stay on the question turn, so round 2 extends round 1');

		$nextQuestion = aiCatalogFirstMessages(array_merge(array_slice($flatten($step3), count($opening)), array(array('role' => 'assistant', 'text' => 'Done: api_contacts_create returned {"id":9}'))), 'et son téléphone ?', '', '', array());
		$this->assertSame($flatten($step3), array_slice($flatten($nextQuestion), 0, count($flatten($step3))), 'With the whole conversation as past turns, the next question appends too');
	}

	/**
	 * testAiCatalogFirstWarmupMessages
	 *
	 * @return void
	 */
	public function testAiCatalogFirstWarmupMessages()
	{
		$flatten = static function (array $messages): array {
			return array_merge($messages['history'], array(array('role' => 'user', 'text' => $messages['user'])));
		};
		$warmUpMessages = aiCatalogFirstWarmupMessages();
		$this->assertSame(array('history' => array(), 'user' => 'Start of the conversation.', 'attachments' => array()), $warmUpMessages);
		$this->assertSame($warmUpMessages, aiCatalogFirstWarmupMessages());

		// The prompt of the warm-up must be an exact prefix of the prompt of the first question of any conversation.
		$firstQuestion = aiCatalogFirstMessages(array(), "liste les tiers\n\n## Date\nCurrent date: 2026-11-28", '', '', array(), array(array('mime' => 'image/png', 'data' => 'iVBORw0KGgo=')));
		$this->assertSame($flatten($warmUpMessages), array_slice($firstQuestion['history'], 0, count($flatten($warmUpMessages))));
		$withPastTurns = aiCatalogFirstMessages(array(array('role' => 'user', 'text' => 'bonjour'), array('role' => 'assistant', 'text' => 'Bonjour !')), 'et ensuite ?', '', '', array());
		$this->assertSame($flatten($warmUpMessages), array_slice($withPastTurns['history'], 0, count($flatten($warmUpMessages))));
	}

	/**
	 * testAiAppendOnlyHistoryStart
	 *
	 * @return void
	 */
	public function testAiAppendOnlyHistoryStart()
	{
		$this->assertSame(0, aiAppendOnlyHistoryStart(0, 20));
		$this->assertSame(0, aiAppendOnlyHistoryStart(19, 20), 'The window starts at the first turn while it holds less than the max');
		$this->assertSame(10, aiAppendOnlyHistoryStart(20, 20), 'Then it moves by half the max at once');
		$this->assertSame(10, aiAppendOnlyHistoryStart(29, 20), 'And stays fixed until the next move, so the prompt keeps its prefix');
		$this->assertSame(20, aiAppendOnlyHistoryStart(30, 20));
	}

	/**
	 * testAiCatalogFirstNextQuestion
	 *
	 * @return void
	 */
	public function testAiCatalogFirstNextQuestion()
	{
		$flatten = static function (array $messages): array {
			return array_merge($messages['history'], array(array('role' => 'user', 'text' => $messages['user'])));
		};
		$typed = 'crée moi un contact jonathan lescaut sur devcamp';
		$sent = $typed."\n\n## Date\nCurrent UTC date: 2026-11-28\n\n## Context\nPage context: thirdparty devcamp\n";
		$past = aiCatalogFirstPastTurns(array(array('role' => 'user', 'text' => 'bonjour'), array('role' => 'assistant', 'text' => 'Bonjour !')), array(), 7);
		$questionN = aiCatalogFirstMessages($past, $sent, '', '', array());

		$this->assertSame(array(array('role' => 'user', 'text' => 'bonjour'), array('role' => 'assistant', 'text' => 'Bonjour !')), $past, 'A turn without its sent message stays as stored');
		$this->assertSame(array(
			array('role' => 'user', 'text' => 'a'),
			array('role' => 'assistant', 'text' => aiNoAnswerTurn()),
			array('role' => 'user', 'text' => 'b'),
			array('role' => 'assistant', 'text' => aiNoAnswerTurn()),
		), aiCatalogFirstPastTurns(array(array('role' => 'user', 'text' => 'a'), array('role' => 'user', 'text' => 'b')), array(), 7), 'A question left without answer gets a fixed assistant turn: two user turns in a row do not extend the previous prompt');

		$unanswered = array(array('role' => 'user', 'text' => 'a'));
		$round1 = aiCatalogFirstMessages(aiCatalogFirstPastTurns($unanswered, array(), 7), $sent, '', '', array());
		$withResult = aiCatalogFirstPastTurns(array_merge($unanswered, array(array('role' => 'assistant', 'text' => 'Done: api_thirdparties_list returned [{"id":3}]'))), array(), 7, true);
		$result = array_pop($withResult);
		$step2 = aiCatalogFirstMessages($withResult, $sent, '', '', array($result['text']));
		$this->assertSame($flatten($round1), array_slice($flatten($step2), 0, count($flatten($round1))), 'The result of a search is not the answer of the previous question: the search round still extends round 1');
		$sentQuestions = array(aiSentQuestionKey(7, ' '.$typed.' ') => $sent);
		$stored = array_merge($past, array(array('role' => 'user', 'text' => $typed), array('role' => 'assistant', 'text' => 'Done: api_contacts_create returned {"id":9}')));
		$this->assertArrayNotHasKey('sent', aiCatalogFirstPastTurns($stored, $sentQuestions, 8)[2], 'The same question in another conversation was sent with another context');
		$pastNext = aiCatalogFirstPastTurns($stored, $sentQuestions, 7);
		$this->assertTrue($pastNext[2]['sent']);
		$questionNext = aiCatalogFirstMessages(array_map(static function (array $turn): array {
			return array('role' => $turn['role'], 'text' => $turn['text']);
		}, $pastNext), "et son téléphone ?\n\n## Date\nCurrent UTC date: 2026-11-28", '', '', array());
		$this->assertSame($flatten($questionN), array_slice($flatten($questionNext), 0, count($flatten($questionN))), 'Question N+1 starts with the round-1 messages of question N, byte for byte');
	}

	/**
	 * testAiRememberSentQuestion
	 *
	 * @return void
	 */
	public function testAiRememberSentQuestion()
	{
		unset($_SESSION['ai_sent_questions']);
		for ($i = 0; $i < 55; $i++) {
			aiRememberSentQuestion(aiSentQuestionKey(1, 'question '.$i), 'sent '.$i, false, false);
		}
		$sent = aiSentQuestions(false);
		$this->assertCount(50, $sent, 'The session keeps the 50 last questions');
		$this->assertArrayNotHasKey(aiSentQuestionKey(1, 'question 4'), $sent);
		$this->assertSame('sent 5', reset($sent));

		aiRememberSentQuestion(aiSentQuestionKey(1, 'question 5'), 'sent again 5', false, false);
		$sent = aiSentQuestions(false);
		$this->assertSame(aiSentQuestionKey(1, 'question 5'), array_key_last($sent), 'A question asked again moves to the end, it is not the next one dropped');
		$this->assertSame('sent again 5', end($sent));

		aiRememberSentQuestion(aiSentQuestionKey(1, 'masked'), 'sent', true, false);
		aiRememberSentQuestion(aiSentQuestionKey(1, 'with a document'), 'sent', false, true);
		aiRememberSentQuestion(aiSentQuestionKey(1, 'long'), str_repeat('x', 8193), false, false);
		$sent = aiSentQuestions(false);
		$this->assertArrayNotHasKey(aiSentQuestionKey(1, 'masked'), $sent, 'Masked values of an older request cannot be restored: never replayed');
		$this->assertArrayNotHasKey(aiSentQuestionKey(1, 'with a document'), $sent, 'A document would fill the session');
		$this->assertArrayNotHasKey(aiSentQuestionKey(1, 'long'), $sent);
		$this->assertSame(array(), aiSentQuestions(true), 'Under the privacy guard no question is replayed');

		unset($_SESSION['ai_sent_questions']);
	}

	/**
	 * testAiAppendOnlyContext
	 *
	 * @return void
	 */
	public function testAiAppendOnlyContext()
	{
		global $conf;

		$service = getDolGlobalString('AI_API_SERVICE');
		$autoMessages = getDolGlobalString('AI_CHAT_CONTEXT_AUTO_MESSAGES');
		unset($conf->global->AI_ASSISTANT_TOOLS_CATALOG_FIRST, $conf->global->AI_ASSISTANT_DISABLE_TOOLS);
		$conf->global->AI_API_SERVICE = 'custom';
		$conf->global->AI_CHAT_CONTEXT_AUTO_MESSAGES = 4;
		$this->assertTrue(aiAppendOnlyContext());
		$conf->global->AI_CHAT_CONTEXT_AUTO_MESSAGES = 0;
		$this->assertFalse(aiAppendOnlyContext(), 'No automatic context is a privacy choice of the admin: catalog first must not send the conversation');
		$conf->global->AI_CHAT_CONTEXT_AUTO_MESSAGES = 4;
		$conf->global->AI_API_SERVICE = 'chatgpt';
		$this->assertFalse(aiAppendOnlyContext());

		$conf->global->AI_API_SERVICE = $service;
		if ($autoMessages === '') {
			unset($conf->global->AI_CHAT_CONTEXT_AUTO_MESSAGES);
		} else {
			$conf->global->AI_CHAT_CONTEXT_AUTO_MESSAGES = $autoMessages;
		}
	}

	/**
	 * testAiImplicitLoadTools
	 *
	 * @return void
	 */
	public function testAiImplicitLoadTools()
	{
		global $conf, $db, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);
		$tools = $bridge->getDefinitions();
		$none = array('objects' => array(), 'tools' => array());
		$sent = array('navigate_to_page');

		$this->assertSame(array('objects' => array(), 'tools' => array('api_contacts_create')), aiImplicitLoadTools(array('tool' => 'api_contacts_create', 'arguments' => array()), $tools, $sent, false), 'A tool of the catalog called without its schema is loaded first');
		$this->assertSame($none, aiImplicitLoadTools(array('tool' => 'api_contacts_create'), $tools, array('api_contacts_create'), false), 'A tool sent in this round runs');
		$this->assertSame($none, aiImplicitLoadTools(array('tool' => 'api_unknown_create'), $tools, $sent, false), 'An unknown tool is refused');
		$withoutCreate = array_values(array_filter($tools, static function (array $tool): bool {
			return $tool['name'] !== 'api_contacts_create';
		}));
		$this->assertSame($none, aiImplicitLoadTools(array('tool' => 'api_contacts_create'), $withoutCreate, $sent, false), 'A tool the user may not use is refused');
		$this->assertSame($none, aiImplicitLoadTools(array('tool' => 'api_contacts_create'), $tools, $sent, true), 'One load per question');
		$this->assertSame(array('objects' => array('contacts'), 'tools' => array()), aiImplicitLoadTools(array('tool' => 'api_objects_contacts_list', 'arguments' => array('object' => 'contacts')), $tools, array('api_objects_contacts_list'), false), 'A tool of several objects called for an object it does not take loads the tools of that object');
		$this->assertSame(array('objects' => array(), 'tools' => array('api_objects_contacts_list')), aiImplicitLoadTools(array('tool' => 'api_objects_contacts_list', 'arguments' => array('object' => 'nothing')), $tools, $sent, false), 'An unknown object: the tool itself is loaded');
		$this->assertSame($none, aiImplicitLoadTools(array('tool' => 'api_objects_contacts_list', 'arguments' => array('object' => 'invoices')), $tools, array('api_objects_contacts_list'), false), 'An object the tool takes: it runs');

		$allTools = array_merge($tools, array(array('name' => 'respond_to_user', 'is_system' => true)));
		$sentRound2 = array('api_contacts_create', 'api_thirdparties_list', 'navigate_to_page');
		$valid = aiValidToolNames($allTools, $tools, $sentRound2, true);
		$this->assertNotContains('api_contacts_list', $valid, 'Round 2: a tool not sent, whose load is refused, does not run with guessed arguments');
		$this->assertContains('api_contacts_create', $valid);
		$this->assertContains('respond_to_user', $valid, 'System tools are never sent and always valid');
		$this->assertContains('api_contacts_list', aiValidToolNames($allTools, $tools, $sentRound2, false), 'Without the catalog first, every tool is valid as before');

		$confirmation = array('tool' => 'ask_for_confirmation', 'arguments' => array('original_intent' => array('tool' => 'api_contacts_create', 'arguments' => array('lastname' => 'X'))));
		$this->assertSame($confirmation['arguments']['original_intent'], aiCalledIntent($confirmation), 'A confirmation asked by the model is checked as the call it wraps');
		$this->assertSame(array('objects' => array(), 'tools' => array('api_contacts_create')), aiImplicitLoadTools(aiCalledIntent($confirmation), $tools, $sent, false), 'Round 1: the wrapped tool is loaded');
		$this->assertSame($none, aiImplicitLoadTools(aiCalledIntent($confirmation), $tools, $sent, true));
		$this->assertNotContains('api_contacts_create', aiValidToolNames($allTools, $tools, $sent, true), 'Round 2: the wrapped tool not sent is refused, not confirmed with guessed arguments');
		$this->assertSame(array('tool' => 'api_contacts_list'), aiCalledIntent(array('tool' => 'api_contacts_list')));
		$this->assertSame(array('tool' => 'ask_for_confirmation', 'arguments' => array('original_intent' => 'x')), aiCalledIntent(array('tool' => 'ask_for_confirmation', 'arguments' => array('original_intent' => 'x'))));
	}

	/**
	 * testAiIntentWithFullToolNames
	 *
	 * @return void
	 */
	public function testAiIntentWithFullToolNames()
	{
		$names = array('api_thirdparties_update', 'navigate_to_page', 'ask_for_confirmation');

		$this->assertSame(array('tool' => 'api_thirdparties_update', 'arguments' => array()), aiIntentWithFullToolNames(array('tool' => 'thirdparties_update', 'arguments' => array()), $names));
		$wrapped = aiIntentWithFullToolNames(array('tool' => 'ask_for_confirmation', 'arguments' => array('original_intent' => array('tool' => 'thirdparties_update'))), $names);
		$this->assertSame('ask_for_confirmation', $wrapped['tool']);
		$this->assertSame('api_thirdparties_update', $wrapped['arguments']['original_intent']['tool']);
		$this->assertSame('navigate_to_page', aiFullToolName('navigate_to_page', array('navigate_to_page', 'api_navigate_to_page')), 'A known name is kept, even when an api_ tool of that name exists');
		$this->assertSame('list_products', aiFullToolName('list_products', $names), 'No api_ name: kept, refused later');
	}
}
