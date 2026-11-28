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
			array('role' => 'user', 'text' => 'ajoute le contacts Jean Exemple au tier devcamp'),
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

		$this->assertSame($both, $sorted(aiQueryFieldObjects('ajoute le numero de telephone 06 12 34 56 78 et le mail jean@example.com a Jean Exemple de devcamp', $byLang['fr_FR'], $tools)));
		$this->assertSame($both, $sorted(aiQueryFieldObjects('ajoute son numéro de téléphone', $byLang['fr_FR'], $tools)), 'A field word alone is enough');
		$this->assertSame($both, $sorted(aiQueryFieldObjects('ajoute son mail', $byLang['fr_FR'], $tools)));
		$this->assertSame($both, $sorted(aiQueryFieldObjects('set the phone of Jean Exemple', $byLang['fr_FR'], $tools)), 'English words work on any install');
		$this->assertSame($both, $sorted(aiQueryFieldObjects('cambia el teléfono de Jean', $byLang['es_ES'], $tools)));
		$this->assertSame($both, $sorted(aiQueryFieldObjects('ändere die Adresse von Jean', $byLang['de_DE'], $tools)));
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

		$this->assertSame(array('contacts'), aiQueryObjects('ajoute le téléphone du contact Jean Exemple', '', array(), $byLang['fr_FR'], $tools), 'A named object wins over the field words');
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
}
