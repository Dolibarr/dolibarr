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
 *      \file       htdocs/core/class/deletionlog.class.php
 *      \ingroup    core
 *      \brief      Class to manage the tombstone log of deleted agenda events.
 */

require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

/**
 *  Class to read and write llx_deletion_log.
 *
 *  This table keeps a short-lived trace of deleted agenda events (llx_actioncomm), so a
 *  page holding a partial view of the agenda (typically a calendar refreshed in ajax) can
 *  ask "which events were deleted since timestamp T?" and drop them from its display
 *  without reloading everything. Consumers are given the event's uid, never its internal
 *  id, so the endpoint cannot be used to enumerate/guess ids of other agenda events.
 *
 *  Each row also stores the entity of the deleted event and a snapshot of its owner and
 *  assigned users, since that access-right info is no longer queryable once the row is
 *  gone: getDeletionsSince() only returns events a given user was allowed to read, the
 *  same way agenda/myactions vs agenda/allactions already works on comm/action/list.php.
 *
 *  Rows are kept for MAIN_DELETION_LOG_RETENTION_DAYS days (default 30) and purged
 *  probabilistically at write time (roughly one insert in a hundred), so no scheduled job
 *  is needed.
 */
class DeletionLog
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * Default retention delay, in days, when the constant is not set.
	 */
	const DEFAULT_RETENTION_DAYS = 30;

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Record the deletion of an agenda event. Meant to be called from
	 * ActionComm::delete() once the row itself is gone, so every property read here comes
	 * from the in-memory PHP object, not from a fresh SQL fetch.
	 *
	 * @param  ActionComm  $object  The event being deleted
	 * @param  ?User       $user    User doing the deletion (may be null)
	 * @return int                  Id of the new row if OK, < 0 if KO
	 */
	public function add($object, $user = null)
	{
		$fk_actioncomm = (int) $object->id;
		if ($fk_actioncomm <= 0) {
			dol_syslog("DeletionLog::add called with a non positive object id", LOG_WARNING);
			return -1;
		}

		$entity = !empty($object->entity) ? (int) $object->entity : 1;
		$uid = !empty($object->uid) ? $object->uid : null;
		$fk_user_action = !empty($object->userownerid) ? (int) $object->userownerid : null;
		$assigned_users = is_array($object->userassigned) ? implode(',', array_map('intval', array_keys($object->userassigned))) : '';

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."deletion_log(entity, fk_actioncomm, uid, fk_user_action, assigned_users, date_deletion, fk_user)";
		$sql .= " VALUES (";
		$sql .= ((int) $entity);
		$sql .= ", ".((int) $fk_actioncomm);
		$sql .= ", ".($uid !== null ? "'".$this->db->escape($uid)."'" : "null");
		$sql .= ", ".($fk_user_action !== null ? ((int) $fk_user_action) : "null");
		$sql .= ", ".($assigned_users !== '' ? "'".$this->db->escape($assigned_users)."'" : "null");
		$sql .= ", '".$this->db->idate(dol_now())."'";
		$sql .= ", ".((is_object($user) && $user->id > 0) ? ((int) $user->id) : "null");
		$sql .= ")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog("DeletionLog::add ".$this->db->lasterror(), LOG_ERR);
			return -2;
		}

		$newid = (int) $this->db->last_insert_id(MAIN_DB_PREFIX."deletion_log");

		// Keep the table bounded without a scheduled job: one insert in a hundred also
		// drops the rows past the retention delay.
		if ($newid > 0 && ($newid % 100) === 0) {
			$retentiondays = getDolGlobalInt('MAIN_DELETION_LOG_RETENTION_DAYS', self::DEFAULT_RETENTION_DAYS);
			$this->purge($retentiondays, 0);
		}

		return $newid;
	}

	/**
	 * Return the uid of the agenda events deleted at or after a given time that $user is
	 * allowed to know about: all of them if $user has the 'allactions' read permission,
	 * otherwise only the ones $user owned or was assigned to (same rule agenda/myactions
	 * already applies to live events on comm/action/list.php).
	 *
	 * @param  int    $since   Lower bound of the deletion date, as a unix timestamp
	 * @param  User   $user    User asking for the list
	 * @param  int    $entity  Entity to look into
	 * @return string[]        List of deleted event uid (empty on error, no match, or no permission)
	 */
	public function getDeletionsSince($since, User $user, $entity = 1)
	{
		$ret = array();

		$allactions = $user->hasRight('agenda', 'allactions', 'read');
		$myactions = $user->hasRight('agenda', 'myactions', 'read');
		if (!$allactions && !$myactions) {
			return $ret;
		}

		$sql = "SELECT uid";
		$sql .= " FROM ".MAIN_DB_PREFIX."deletion_log";
		$sql .= " WHERE entity = ".((int) $entity);
		$sql .= " AND date_deletion >= '".$this->db->idate($since)."'";
		$sql .= " AND uid IS NOT NULL"; // Nothing usable to report for rows that predate uid support
		if (!$allactions) {
			// Not the owner and not assigned: user is not allowed to read this event.
			// assigned_users is a plain comma-separated list of ids (e.g. "42,43"), not a join
			// table, so membership is checked with plain LIKE (portable across all DB drivers)
			// rather than a vendor-specific function like MySQL's FIND_IN_SET().
			$sql .= " AND (fk_user_action = ".((int) $user->id);
			$sql .= " OR assigned_users = '".((int) $user->id)."'";
			$sql .= " OR assigned_users LIKE '".((int) $user->id).",%'";
			$sql .= " OR assigned_users LIKE '%,".((int) $user->id)."'";
			$sql .= " OR assigned_users LIKE '%,".((int) $user->id).",%')";
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog("DeletionLog::getDeletionsSince ".$this->db->lasterror(), LOG_ERR);
			return $ret;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$ret[] = $obj->uid;
		}
		$this->db->free($resql);

		return $ret;
	}

	/**
	 * Delete rows older than a retention delay.
	 *
	 * @param  int    $retentiondays  Number of days to keep (<= 0 falls back to the default)
	 * @param  int    $entity         Entity to purge, or 0 for every entity
	 * @return int                    Number of deleted rows if OK, < 0 if KO
	 */
	public function purge($retentiondays, $entity = 0)
	{
		$retentiondays = (int) $retentiondays;
		if ($retentiondays <= 0) {
			$retentiondays = self::DEFAULT_RETENTION_DAYS;
		}

		$limit = dol_now() - ($retentiondays * 24 * 3600);

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."deletion_log";
		$sql .= " WHERE date_deletion < '".$this->db->idate($limit)."'";
		if ($entity > 0) {
			$sql .= " AND entity = ".((int) $entity);
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog("DeletionLog::purge ".$this->db->lasterror(), LOG_ERR);
			return -1;
		}

		return $this->db->affected_rows($resql);
	}
}
