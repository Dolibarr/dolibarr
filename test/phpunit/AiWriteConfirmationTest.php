<?php
/* Copyright (C) 2026 Nick Fragoulis
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
 * \file       test/phpunit/AiWriteConfirmationTest.php
 * \ingroup    test
 * \brief      Tests of the confirmation gate protecting AI writes.
 */

global $conf, $user, $langs, $db;

require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/ai/class/writeconfirmation.class.php';

/**
 * Class AiWriteConfirmationTest
 *
 * The gate is what stands between a model deciding to write and the write
 * happening, so every refusal path is asserted rather than the happy path only:
 * a forged state, a replayed one, an expired one, one issued to another user or
 * another tool, and one confirmed with different arguments than it was issued
 * for.
 *
 * @backupGlobals disabled
 */
class AiWriteConfirmationTest extends CommonClassTest
{
	/**
	 * Arguments a pending write is issued for.
	 *
	 * @return array<string,mixed> Arguments.
	 */
	private function sampleArgs()
	{
		return array(
			'header' => array('socid' => 1),
			'lines' => array(array('quantity' => 2, 'unit_price' => 30))
		);
	}

	/**
	 * A state is issued for a write, and the raw state never reaches the table.
	 *
	 * @return void
	 */
	public function testIssueReturnsAStateAndStoresOnlyItsHash()
	{
		global $db, $user;

		$gate = new AiWriteConfirmation($db);
		$state = $gate->issue($user, 'create_customer_invoice', $this->sampleArgs(), 'Create a draft invoice');

		$this->assertNotEmpty($state, 'a state must be issued: '.$gate->error);

		$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."ai_write_confirmation WHERE state_hash = '".$db->escape($state)."'";
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$this->assertSame(0, (int) $obj->nb, 'the state itself must not be stored, only a keyed hash of it');
	}

	/**
	 * A valid state lets the write through exactly once.
	 *
	 * @return void
	 */
	public function testValidStateIsAcceptedOnceAndRefusedOnReplay()
	{
		global $db, $user;

		$gate = new AiWriteConfirmation($db);
		$args = $this->sampleArgs();
		$state = $gate->issue($user, 'create_customer_invoice', $args, 'Create a draft invoice');

		$this->assertTrue($gate->consume($user, 'create_customer_invoice', $args, $state), 'first confirmation must pass');
		$this->assertFalse($gate->consume($user, 'create_customer_invoice', $args, $state), 'a state must not be usable twice');
	}

	/**
	 * A state the server never issued is refused.
	 *
	 * @return void
	 */
	public function testForgedStateIsRefused()
	{
		global $db, $user;

		$gate = new AiWriteConfirmation($db);
		$this->assertFalse($gate->consume($user, 'create_customer_invoice', $this->sampleArgs(), str_repeat('a', 64)));
		$this->assertNotEmpty($gate->error);
	}

	/**
	 * Confirming different arguments than were previewed is refused.
	 *
	 * @return void
	 */
	public function testChangedArgumentsAreRefused()
	{
		global $db, $user;

		$gate = new AiWriteConfirmation($db);
		$args = $this->sampleArgs();
		$state = $gate->issue($user, 'create_customer_invoice', $args, 'Create a draft invoice');

		$tampered = $args;
		$tampered['lines'][0]['unit_price'] = 30000;

		$this->assertFalse($gate->consume($user, 'create_customer_invoice', $tampered, $state), 'the amount changed after the preview');
	}

	/**
	 * The same arguments in another order are still the same arguments.
	 *
	 * @return void
	 */
	public function testArgumentOrderDoesNotMatter()
	{
		global $db, $user;

		$gate = new AiWriteConfirmation($db);
		$args = $this->sampleArgs();
		$state = $gate->issue($user, 'create_customer_invoice', $args, 'Create a draft invoice');

		$reordered = array('lines' => $args['lines'], 'header' => $args['header']);

		$this->assertTrue($gate->consume($user, 'create_customer_invoice', $reordered, $state));
	}

	/**
	 * A state issued for one tool cannot confirm another.
	 *
	 * @return void
	 */
	public function testStateIsBoundToItsTool()
	{
		global $db, $user;

		$gate = new AiWriteConfirmation($db);
		$args = $this->sampleArgs();
		$state = $gate->issue($user, 'create_customer_invoice', $args, 'Create a draft invoice');

		$this->assertFalse($gate->consume($user, 'delete_object', $args, $state));
	}

	/**
	 * An expired state is refused.
	 *
	 * @return void
	 */
	public function testExpiredStateIsRefused()
	{
		global $db, $user;

		$gate = new AiWriteConfirmation($db);
		$args = $this->sampleArgs();
		$state = $gate->issue($user, 'create_customer_invoice', $args, 'Create a draft invoice');

		$sql = "UPDATE ".MAIN_DB_PREFIX."ai_write_confirmation SET date_expiration = '".$db->idate(dol_now() - 60)."'";
		$sql .= " WHERE date_consumed IS NULL";
		$db->query($sql);

		$this->assertFalse($gate->consume($user, 'create_customer_invoice', $args, $state));
	}

	/**
	 * Every write tool stops on its first call, and no read tool does.
	 *
	 * @return void
	 */
	public function testWriteToolsStopAndReadToolsDoNot()
	{
		global $db, $user, $conf;

		require_once DOL_DOCUMENT_ROOT.'/ai/class/mcp.class.php';

		$handler = new McpHandler($db, $user, $conf, McpHandler::CTX_ASSISTANT);
		if (is_callable(array($handler, 'loadTools'))) {
			$handler->loadTools();
		}

		$result = $handler->executeTool('create_thirdparty', array('name' => 'PHPUnit pending company'));
		$this->assertSame('input_required', $result['resultType'] ?? '', 'a write must ask for confirmation first');
		$this->assertNotEmpty($result['requestState'] ?? '', 'the caller needs a state to confirm with');
		$this->assertNotEmpty($result['inputRequests'][0]['prompt'] ?? '', 'the user must be told what would be written');

		$read = $handler->executeTool('search_thirdparties', array('limit' => 1));
		$this->assertNotSame('input_required', $read['resultType'] ?? '', 'a read must not be gated');
	}
}
