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
 *      \brief      Class to manage the tombstone log of deleted objects.
 */

/**
 *  Class to read and write llx_deletion_log.
 *
 *  This table keeps a short-lived trace of deleted objects (element type + id +
 *  date). A page holding a partial view of a collection (typically a calendar
 *  refreshed in ajax) can then ask "which objects of type X disappeared since
 *  timestamp T?" and drop them from its display without reloading everything.
 *
 *  Each row also stores the entity of the deleted object, so a consumer only
 *  gets the deletions of the company(ies) it is allowed to read on a
 *  multi-company install. getDeletionsSince() and purge() both filter on it.
 *
 *  Rows are kept for MAIN_DELETION_LOG_RETENTION_DAYS days (default 30) and
 *  purged probabilistically at write time by the trigger that fills the table
 *  (roughly one insert in a hundred), so no scheduled job is needed.
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
	 * Record the deletion of an object.
	 *
	 * @param  string      $element_type  Object element type (usually $object->element)
	 * @param  int         $fk_object     Id of the deleted object
	 * @param  ?User       $user          User doing the deletion (may be null)
	 * @param  int         $entity        Entity of the deleted object
	 * @return int                        Id of the new row if OK, < 0 if KO
	 */
	public function add($element_type, $fk_object, $user = null, $entity = 1)
	{
		$element_type = trim((string) $element_type);
		$fk_object = (int) $fk_object;
		if ($element_type === '' || $fk_object <= 0) {
			dol_syslog("DeletionLog::add called with an empty element_type or a non positive fk_object", LOG_WARNING);
			return -1;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."deletion_log(entity, element_type, fk_object, date_deletion, fk_user)";
		$sql .= " VALUES (";
		$sql .= ((int) $entity);
		$sql .= ", '".$this->db->escape($element_type)."'";
		$sql .= ", ".((int) $fk_object);
		$sql .= ", '".$this->db->idate(dol_now())."'";
		$sql .= ", ".((is_object($user) && $user->id > 0) ? ((int) $user->id) : "null");
		$sql .= ")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog("DeletionLog::add ".$this->db->lasterror(), LOG_ERR);
			return -2;
		}

		return (int) $this->db->last_insert_id(MAIN_DB_PREFIX."deletion_log");
	}

	/**
	 * Return the ids of the objects of a given type deleted at or after a given time.
	 *
	 * @param  string $element_type  Object element type
	 * @param  int    $since         Lower bound of the deletion date, as a unix timestamp
	 * @param  int    $entity        Entity to look into
	 * @return int[]                 List of deleted object ids (empty on error or no match)
	 */
	public function getDeletionsSince($element_type, $since, $entity = 1)
	{
		$ret = array();

		$element_type = trim((string) $element_type);
		if ($element_type === '') {
			return $ret;
		}

		$sql = "SELECT fk_object";
		$sql .= " FROM ".MAIN_DB_PREFIX."deletion_log";
		$sql .= " WHERE element_type = '".$this->db->escape($element_type)."'";
		$sql .= " AND entity = ".((int) $entity);
		$sql .= " AND date_deletion >= '".$this->db->idate($since)."'";

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog("DeletionLog::getDeletionsSince ".$this->db->lasterror(), LOG_ERR);
			return $ret;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$ret[] = (int) $obj->fk_object;
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
