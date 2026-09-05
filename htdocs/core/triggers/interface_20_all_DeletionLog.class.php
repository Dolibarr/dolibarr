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
 */

/**
 *  \file       htdocs/core/triggers/interface_20_all_DeletionLog.class.php
 *  \ingroup    core
 *  \brief      Trigger file that records deleted objects into llx_deletion_log.
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/deletionlog.class.php';

/**
 *  Class of triggers for the deletion log (tombstones).
 */
class InterfaceDeletionLog extends DolibarrTriggers
{
	/**
	 * Trigger action codes handled here. When one of these fires, a row is
	 * added to llx_deletion_log with the object element type and id, so a page
	 * showing a partial list (e.g. an ajax calendar) can learn about removals.
	 *
	 * @var string[]
	 */
	const HANDLED_ACTIONS = array(
		'ACTION_DELETE',
	);

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		parent::__construct($db);

		$this->family = "core";
		$this->description = "Triggers of this module record deleted objects into a short-lived log.";
		$this->version = self::VERSIONS['prod'];
		$this->picto = 'technic';
	}

	/**
	 * Function called when a Dolibarr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string    $action  Event action code
	 * @param CommonObject $object Object
	 * @param User      $user    Object user
	 * @param Translate $langs   Object langs
	 * @param Conf      $conf    Object conf
	 * @return int               If KO: <0, if no trigger ran: 0, if OK: >0
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (getDolGlobalString('MAIN_DISABLE_DELETION_LOG')) {
			return 0;
		}
		if (!in_array($action, self::HANDLED_ACTIONS, true)) {
			return 0;
		}
		if (empty($object->id) || empty($object->element)) {
			return 0;
		}

		$entity = (!empty($object->entity) ? (int) $object->entity : (int) $conf->entity);

		$res = DeletionLog::add($this->db, $object->element, $object->id, $user, $entity);
		if ($res < 0) {
			// A best-effort tombstone must never roll back a legitimate deletion.
			dol_syslog("InterfaceDeletionLog::runTrigger failed to log deletion of ".$object->element." ".$object->id, LOG_ERR);
			return 0;
		}

		// Probabilistic purge, so the table stays bounded even if the scheduled job is disabled.
		if (mt_rand(1, 100) === 1) {
			$retentiondays = getDolGlobalInt('MAIN_DELETION_LOG_RETENTION_DAYS', DeletionLog::DEFAULT_RETENTION_DAYS);
			DeletionLog::purge($this->db, $retentiondays, 0);
		}

		return 1;
	}
}
