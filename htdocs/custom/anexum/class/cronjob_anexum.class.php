<?php
/* Copyright (C) 2026  Florian Hödl  <florian@hoedl.co>
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
 * \file       htdocs/custom/anexum/class/cronjob_anexum.class.php
 * \ingroup    anexum
 * \brief      Cron job methods for Anexum module
 */

/**
 * Class CronjobAnexum
 *
 * Contains methods that can be called by Dolibarr's internal cron scheduler
 */
class CronjobAnexum
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var array Error messages
	 */
	public $errors = array();

	/**
	 * @var string Output message
	 */
	public $output = '';

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Reset stuck cron jobs
	 *
	 * Finds cron jobs that have been processing for longer than the threshold
	 * and resets them. This fixes the issue where EmailCollector or other cron
	 * jobs get stuck in an infinite loop.
	 *
	 * @param int $threshold_minutes Minutes before a job is considered stuck (default: 30)
	 * @return int 0 if OK, < 0 if KO
	 */
	public function resetStuckCronJobs($threshold_minutes = 30)
	{
		global $conf, $langs;

		$threshold_minutes = (int) $threshold_minutes;
		if ($threshold_minutes < 1) {
			$threshold_minutes = 30;
		}

		$this->output = '';
		$error = 0;
		$reset_count = 0;

		// Find stuck cron jobs:
		// - processing = 1 (currently running)
		// - datelastrun < NOW() - threshold (running for too long)
		// - Exclude ourselves (this job) to avoid self-reset
		$sql = "SELECT rowid, label, datelastrun, pid, module_name, methodename";
		$sql .= " FROM ".$this->db->prefix()."cronjob";
		$sql .= " WHERE processing = 1";
		$sql .= " AND datelastrun < DATE_SUB(NOW(), INTERVAL ".((int) $threshold_minutes)." MINUTE)";
		// Exclude this job itself
		$sql .= " AND NOT (module_name = 'anexum' AND methodename = 'resetStuckCronJobs')";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = "SQL error: ".$this->db->lasterror();
			dol_syslog(__METHOD__.": ".$this->error, LOG_ERR);
			return -1;
		}

		$num = $this->db->num_rows($resql);
		$this->output .= "Found ".$num." potentially stuck job(s)\n";

		while ($obj = $this->db->fetch_object($resql)) {
			$should_reset = true;

			// If we have a PID, check if the process is still alive (Linux-specific)
			if (!empty($obj->pid) && $obj->pid > 0) {
				if (file_exists('/proc/'.$obj->pid)) {
					// Process still exists - check if it's been running too long
					$proc_stat = @file_get_contents('/proc/'.$obj->pid.'/stat');
					if ($proc_stat !== false) {
						// Process is alive - don't reset unless it's really old (> 60 min)
						$running_minutes = round((time() - strtotime($obj->datelastrun)) / 60);
						if ($running_minutes < 60) {
							$should_reset = false;
							$this->output .= "  - Job #".$obj->rowid." '".$obj->label."': PID ".$obj->pid." still alive, skipping\n";
						}
					}
				}
			}

			if ($should_reset) {
				$running_minutes = round((time() - strtotime($obj->datelastrun)) / 60);

				// Reset the stuck job
				$sql_update = "UPDATE ".$this->db->prefix()."cronjob SET";
				$sql_update .= " processing = 0,";
				$sql_update .= " pid = NULL";
				$sql_update .= " WHERE rowid = ".((int) $obj->rowid);

				$res_update = $this->db->query($sql_update);
				if ($res_update) {
					$reset_count++;

					$log_msg = "Reset stuck cron job #".$obj->rowid." '".$obj->label."' ";
					$log_msg .= "(was processing for ".$running_minutes." minutes";
					if (!empty($obj->pid)) {
						$log_msg .= ", PID: ".$obj->pid;
					}
					$log_msg .= ")";

					dol_syslog(__METHOD__.": ".$log_msg, LOG_WARNING);
					$this->output .= "  - ".$log_msg."\n";
				} else {
					$error++;
					$err_msg = "Failed to reset job #".$obj->rowid.": ".$this->db->lasterror();
					dol_syslog(__METHOD__.": ".$err_msg, LOG_ERR);
					$this->errors[] = $err_msg;
					$this->output .= "  - ERROR: ".$err_msg."\n";
				}
			}
		}

		$this->db->free($resql);

		$this->output .= "Done. Reset ".$reset_count." job(s).\n";

		if ($reset_count > 0) {
			dol_syslog(__METHOD__.": Reset ".$reset_count." stuck cron job(s)", LOG_NOTICE);
		}

		return $error ? -1 : 0;
	}
}
