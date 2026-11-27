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
}
