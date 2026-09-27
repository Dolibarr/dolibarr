<?php
/* Copyright (C) 2026		Frédéric France			<frederic.france@free.fr>
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
 *      \file       test/phpunit/NotifyTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test of the Notify class
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/notify.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/commonhookactions.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/../../htdocs/contrat/class/contrat.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests of Notify.
 *
 * The mails are disabled, so the message is not sent, but Notify::send() calls the hook 'formatNotificationMessage'
 * just before, with the list of the files to attach: the tests read it there.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class NotifyTest extends CommonClassTest
{
	/**
	 * Send the notification CONTRACT_MODIFY for a contract that has a PDF file, and return the files that the
	 * notification attaches and the PHP warnings raised by the class Notify.
	 *
	 * @param	string	$target		'user' = notification defined for a user (table llx_notify_def), 'fixedemail' = notification on a fixed email (constant NOTIFICATION_FIXEDEMAIL_...)
	 * @return	array{files: array<int,string[]>, warnings: string[]}
	 */
	private function sendContractModify($target)
	{
		global $db, $conf, $user, $langs, $mysoc, $hookmanager;
		$db = $this->savdb;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$savmysoc = $mysoc;
		$mysoc = new Societe($db);	// Notify::send() needs the name of the company
		$mysoc->name = 'PHPUnit company';

		if (empty($conf->contract->multidir_output[$conf->entity])) {
			$this->markTestSkipped('Module contract is not enabled');
		}

		$ref = 'PHPUNITNOTIFYCONTRACT';
		$dir = $conf->contract->multidir_output[$conf->entity].'/'.$ref;
		dol_mkdir($dir);
		file_put_contents($dir.'/'.$ref.'.pdf', "%PDF-1.4\n");

		$capture = new class extends CommonHookActions {
			/** @var array<int,string[]> */
			public $files = array();

			/**
			 * Hook called by Notify::send() just before sending the message
			 *
			 * @param	array<string,mixed>	$parameters		Parameters of the hook
			 * @param	CommonObject		$object			Object
			 * @param	string				$action			Action
			 * @param	HookManager			$hookmanager	Hook manager
			 * @return	int
			 */
			public function formatNotificationMessage($parameters, &$object, &$action, $hookmanager)
			{
				$this->files[] = $parameters['file'];
				return 0;
			}
		};

		$warnings = array();
		$savhookmanager = $hookmanager;
		$fixedemailconst = 'NOTIFICATION_FIXEDEMAIL_CONTRACT_MODIFY_THRESHOLD_HIGHER_0';

		$db->begin();
		set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use (&$warnings) {
			if (basename($errfile) == 'notify.class.php') {
				$warnings[] = $errstr.' at line '.$errline;
			}
			return true;
		});
		try {
			if ($target == 'user') {
				$db->query("UPDATE ".MAIN_DB_PREFIX."user SET email = 'phpunit-notify@example.invalid' WHERE rowid = ".((int) $user->id));
				$fkaction = dol_getIdFromCode($db, 'CONTRACT_MODIFY', 'c_action_trigger', 'code', 'rowid');
				$db->query("INSERT INTO ".MAIN_DB_PREFIX."notify_def (datec, fk_action, fk_user, type, entity) VALUES ('".$db->idate(dol_now())."', ".((int) $fkaction).", ".((int) $user->id).", 'email', ".((int) $conf->entity).")");
			} else {
				$conf->global->$fixedemailconst = 'phpunit-notify@example.invalid';
			}

			$hookmanager = new HookManager($db);
			$hookmanager->initHooks(array('notification'));
			$hookmanager->hooks['notification']['phpunitcapture'] = $capture;
			$hookmanager->hooksSorted['notification']['0:phpunitcapture'] = $capture;

			$contract = new Contrat($db);
			$contract->id = 999999;
			$contract->ref = $ref;
			$contract->entity = $conf->entity;
			$contract->socid = 0;
			$contract->context = array();

			$notify = new Notify($db);
			$notify->send('CONTRACT_MODIFY', $contract);
		} finally {
			restore_error_handler();
			$db->rollback();
			unset($conf->global->$fixedemailconst);
			$hookmanager = $savhookmanager;
			$mysoc = $savmysoc;
			dol_delete_dir_recursive($dir);
		}

		return array('files' => $capture->files, 'warnings' => $warnings);
	}

	/**
	 * testSendContractModifyToUser
	 *
	 * The directory of the contract files is a directory per entity (an array), it must be completed with the ref of the
	 * contract to find the PDF, and using the array as a string raises a warning.
	 *
	 * @return void
	 */
	public function testSendContractModifyToUser()
	{
		global $conf;

		$result = $this->sendContractModify('user');

		$this->assertSame(array(), $result['warnings'], 'No PHP warning must be raised by Notify (notification of a user)');
		$this->assertCount(1, $result['files'], 'The hook must be called once (notification of a user)');
		$this->assertSame(array($conf->contract->multidir_output[$conf->entity].'/PHPUNITNOTIFYCONTRACT/PHPUNITNOTIFYCONTRACT.pdf'), $result['files'][0], 'The PDF of the contract must be attached (notification of a user)');
	}

	/**
	 * testSendContractModifyToFixedEmail
	 *
	 * @return void
	 */
	public function testSendContractModifyToFixedEmail()
	{
		global $conf;

		$result = $this->sendContractModify('fixedemail');

		$this->assertSame(array(), $result['warnings'], 'No PHP warning must be raised by Notify (notification on a fixed email)');
		$this->assertCount(1, $result['files'], 'The hook must be called once (notification on a fixed email)');
		$this->assertSame(array($conf->contract->multidir_output[$conf->entity].'/PHPUNITNOTIFYCONTRACT/PHPUNITNOTIFYCONTRACT.pdf'), $result['files'][0], 'The PDF of the contract must be attached (notification on a fixed email)');
	}
}
