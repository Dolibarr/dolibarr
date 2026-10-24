<?php
/* Copyright (C) 2026 Joachim Kueter        <git-jk@bloxera.com>
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
 *      \file       test/phpunit/triggers/interface_99_all_PhpunitBookKeeping.class.php
 *      \ingroup    test
 *      \brief      Trigger used by BookKeepingTest to see the bookkeeping triggers and to refuse a change
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 * Records the BOOKKEEPING_* triggers, like a module that locks closed periods would get them
 */
class InterfacePhpunitBookKeeping extends DolibarrTriggers
{
	/**
	 * @var array<int,array{action:string,piece_num:int,doc_date:mixed,nblines:int,old_doc_date:mixed,nboldlines:int}>	Calls received
	 */
	public static $calls = array();

	/**
	 * @var string	Trigger code to refuse, '' to accept all
	 */
	public static $refuse = '';

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = 'other';
		$this->description = 'Records bookkeeping triggers for BookKeepingTest.';
		$this->version = self::VERSIONS['dev'];
	}

	/**
	 * Record the bookkeeping triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int         				Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (strpos($action, 'BOOKKEEPING_') !== 0) {
			return 0;
		}

		self::$calls[] = array(
			'action' => $action,
			'piece_num' => (int) $object->piece_num,
			'doc_date' => $object->doc_date,
			'nblines' => (isset($object->linesmvt) && is_array($object->linesmvt) ? count($object->linesmvt) : 0),
			'old_doc_date' => (is_object($object->oldcopy) ? $object->oldcopy->doc_date : null),
			'nboldlines' => (isset($object->context['oldlines']) && is_array($object->context['oldlines']) ? count($object->context['oldlines']) : 0),
		);

		if ($action === self::$refuse) {
			$this->errors[] = 'Refused by test trigger';
			return -1;
		}

		return 1;
	}
}
