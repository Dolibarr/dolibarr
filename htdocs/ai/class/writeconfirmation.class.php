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
 * \file       htdocs/ai/class/writeconfirmation.class.php
 * \ingroup    ai
 * \brief      Confirmation gate for AI writes, on the MCP multi-round-trip pattern.
 */

/**
 * Class AiWriteConfirmation
 *
 * A write tool does not execute on the first call: it answers with a preview and
 * an opaque requestState, and executes only when the caller comes back with that
 * state. The state is signed with an instance secret, expires, is single use and
 * is bound to the arguments it was issued for, so a caller can neither forge a
 * confirmation nor confirm one set of arguments and send another.
 */
class AiWriteConfirmation
{
	/**
	 * Minutes a state stays valid, long enough to read a preview.
	 */
	const DEFAULT_TTL_MINUTES = 10;

	/**
	 * @var DoliDB Database handler.
	 */
	private $db;

	/**
	 * @var string Last error, for the caller to report.
	 */
	public $error = '';

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Issue a state for a pending write.
	 *
	 * @param User   $user     User asking for the write.
	 * @param string $toolName Tool that would run.
	 * @param array<string,mixed> $args Arguments it would run with.
	 * @param string $preview  Human readable description of what would be written.
	 * @return string Opaque state to hand to the caller, '' on failure.
	 */
	public function issue($user, $toolName, array $args, $preview)
	{
		global $conf;

		$secret = $this->signingSecret();
		if ($secret === '') {
			$this->error = 'No instance secret available to sign the confirmation';

			return '';
		}

		$state = dol_hash($secret.'|'.$toolName.'|'.$user->id.'|'.dol_now().'|'.microtime(true).'|'.mt_rand(), 'sha256');
		$ttl = getDolGlobalInt('AI_WRITE_CONFIRMATION_TTL_MINUTES', self::DEFAULT_TTL_MINUTES);
		if ($ttl <= 0) {
			$ttl = self::DEFAULT_TTL_MINUTES;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."ai_write_confirmation";
		$sql .= " (entity, state_hash, fk_user, tool_name, args_hash, preview, date_creation, date_expiration, ip)";
		$sql .= " VALUES (".((int) $conf->entity);
		$sql .= ", '".$this->db->escape($this->stateHash($state, $secret))."'";
		$sql .= ", ".((int) $user->id);
		$sql .= ", '".$this->db->escape($toolName)."'";
		$sql .= ", '".$this->db->escape($this->argsHash($args))."'";
		$sql .= ", '".$this->db->escape(dol_trunc((string) $preview, 60000, 'right', 'UTF-8', 1))."'";
		$sql .= ", '".$this->db->idate(dol_now())."'";
		$sql .= ", '".$this->db->idate(dol_now() + ($ttl * 60))."'";
		$sql .= ", '".$this->db->escape(getUserRemoteIP())."'";
		$sql .= ")";

		if (!$this->db->query($sql)) {
			$this->error = 'Cannot store the pending confirmation';
			dol_syslog('[AiWriteConfirmation] '.$this->error.': '.$this->db->lasterror(), LOG_ERR);

			return '';
		}

		// Keep the table bounded without depending on a scheduled job being
		// enabled: one call in a hundred clears rows older than the retention.
		if (mt_rand(1, 100) === 1) {
			$this->purge(getDolGlobalInt('AI_WRITE_CONFIRMATION_KEEP_DAYS', 30));
		}

		return $state;
	}

	/**
	 * Validate a state presented on the confirming call, and consume it.
	 *
	 * Everything is checked before the write runs: the signature, that the state
	 * belongs to this user, this tool and these arguments, that it has not expired
	 * and that it was never used before.
	 *
	 * @param User   $user     User confirming.
	 * @param string $toolName Tool being confirmed.
	 * @param array<string,mixed> $args Arguments presented now.
	 * @param string $state    State the caller echoed back.
	 * @return bool True when the write may proceed.
	 */
	public function consume($user, $toolName, array $args, $state)
	{
		global $conf;

		$this->error = '';
		$secret = $this->signingSecret();
		if ($secret === '' || !is_string($state) || $state === '') {
			$this->error = 'Invalid confirmation';

			return false;
		}

		$sql = "SELECT rowid, fk_user, tool_name, args_hash, date_expiration, date_consumed";
		$sql .= " FROM ".MAIN_DB_PREFIX."ai_write_confirmation";
		$sql .= " WHERE state_hash = '".$this->db->escape($this->stateHash($state, $secret))."'";
		$sql .= " AND entity = ".((int) $conf->entity);

		$resql = $this->db->query($sql);
		if (!$resql || !($obj = $this->db->fetch_object($resql))) {
			$this->error = 'Unknown or forged confirmation';
			dol_syslog('[AiWriteConfirmation] rejected: no state matches', LOG_NOTICE);

			return false;
		}

		if ((int) $obj->fk_user !== (int) $user->id) {
			$this->error = 'This confirmation was issued to another user';
		} elseif ((string) $obj->tool_name !== (string) $toolName) {
			$this->error = 'This confirmation was issued for another action';
		} elseif ((string) $obj->args_hash !== $this->argsHash($args)) {
			$this->error = 'The action changed since it was confirmed';
		} elseif (!empty($obj->date_consumed)) {
			$this->error = 'This confirmation was already used';
		} elseif ($this->db->jdate($obj->date_expiration) < dol_now()) {
			$this->error = 'This confirmation expired, ask again';
		}

		if ($this->error !== '') {
			dol_syslog('[AiWriteConfirmation] rejected for '.$toolName.': '.$this->error, LOG_NOTICE);

			return false;
		}

		// Mark consumed before the write runs: a crash mid-write must not leave a
		// state that can be replayed.
		$sql = "UPDATE ".MAIN_DB_PREFIX."ai_write_confirmation";
		$sql .= " SET date_consumed = '".$this->db->idate(dol_now())."'";
		$sql .= " WHERE rowid = ".((int) $obj->rowid)." AND date_consumed IS NULL";

		$resupdate = $this->db->query($sql);
		// The WHERE includes date_consumed IS NULL, so two concurrent confirmations
		// of the same state cannot both update the row: the loser gets 0 rows.
		if (!$resupdate || $this->db->affected_rows($resupdate) === 0) {
			$this->error = 'This confirmation was already used';
			dol_syslog('[AiWriteConfirmation] rejected for '.$toolName.': concurrent use', LOG_NOTICE);

			return false;
		}

		return true;
	}

	/**
	 * Remove expired states that were never confirmed.
	 *
	 * @param int $keepdays Days of history to keep for audit.
	 * @return int Rows deleted, -1 on error.
	 */
	public function purge($keepdays = 30)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."ai_write_confirmation";
		$sql .= " WHERE date_expiration < '".$this->db->idate(dol_now() - ((int) $keepdays * 86400))."'";

		$resql = $this->db->query($sql);
		if (!$resql) {
			return -1;
		}

		return (int) $this->db->affected_rows($resql);
	}

	/**
	 * Secret used to sign states.
	 *
	 * Derived from the instance id Dolibarr already uses for session prefixes, so
	 * there is nothing to generate and the value stays in conf.php rather than in
	 * the database. Empty when the installation predates it, in which case no
	 * state is issued at all.
	 *
	 * @return string Secret, '' when the installation has none.
	 */
	private function signingSecret()
	{
		global $dolibarr_main_instance_unique_id, $dolibarr_main_cookie_cryptkey;

		$instanceid = empty($dolibarr_main_instance_unique_id) ? (empty($dolibarr_main_cookie_cryptkey) ? '' : $dolibarr_main_cookie_cryptkey) : $dolibarr_main_instance_unique_id;
		if (empty($instanceid)) {
			return '';
		}

		return dol_hash('ai-write-confirmation'.$instanceid, 'sha256');
	}

	/**
	 * Keyed hash of a state, so the database never holds the state itself.
	 *
	 * @param string $state  State handed to the caller.
	 * @param string $secret Instance secret.
	 * @return string Hash stored and looked up.
	 */
	private function stateHash($state, $secret)
	{
		return dol_hash($secret.'|'.$state, 'sha256');
	}

	/**
	 * Stable hash of the arguments a state was issued for.
	 *
	 * @param array<string,mixed> $args Tool arguments.
	 * @return string Hash.
	 */
	private function argsHash(array $args)
	{
		$normalized = $args;
		unset($normalized['requestState']);
		$this->ksortRecursive($normalized);

		return dol_hash((string) json_encode($normalized), 'sha256');
	}

	/**
	 * Sort an array by key at every level, so argument order cannot change the hash.
	 *
	 * @param array<string,mixed> $arr Array to sort in place.
	 * @return void
	 */
	private function ksortRecursive(array &$arr)
	{
		ksort($arr);
		foreach ($arr as &$value) {
			if (is_array($value)) {
				$this->ksortRecursive($value);
			}
		}
	}
}
