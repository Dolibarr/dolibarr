<?php
/* Copyright (C) 2010-2012   Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010        Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *    \file       htdocs/core/modules/ticket/mod_ticket_simple.php
 *    \ingroup    ticket
 *    \brief      File with class to manage the numbering module Simple for ticket references
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/ticket/modules_ticket.php';

/**
 *     Class to manage the numbering module Simple for ticket references
 */
class mod_ticket_simple extends ModeleNumRefTicket
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	public $prefix = 'TS';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see $name
	 */
	public $nom = 'Simple';

	/**
	 * @var string model name
	 */
	public $name = 'Simple';

	/**
	 *  Return description of numbering module
	 *
	 *  @return string      Text with description
	 */
	public function info()
	{
		global $langs;
		return $langs->trans("SimpleNumRefModelDesc", $this->prefix);
	}

	/**
	 *  Return an example of numbering module values
	 *
	 *     @return string      Example
	 */
	public function getExample()
	{
		return $this->prefix."0501-0001";
	}

	/**
	 *  Checks if the numbers already in the database do not
	 *  cause conflicts that would prevent this numbering working.
	 *
	 *   @return boolean     false if conflict, true if ok
	 */
	public function canBeActivated()
	{
		global $conf, $langs, $db;

		$coyymm = '';
		$max = '';

		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."ticket";
		$search = $this->prefix."____-%";
		$sql .= " WHERE ref LIKE '".$db->escape($search)."'";
		$sql .= " AND entity = ".$conf->entity;
		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			if ($row) {
				$coyymm = substr($row[0], 0, 6);
				$max = $row[0];
			}
		}
		if (!$coyymm || preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $coyymm)) {
			return true;
		} else {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}
	}

	/**
	 *  Return next value
	 *
	 *  @param  Societe $objsoc    	Object third party
	 *  @param  Ticket 	$ticket 	Object ticket
	 *  @return string              Value if OK, 0 if KO
	 */
	public function getNextValue($objsoc, $ticket)
	{
		global $db, $conf;

		// First, we get the max value
		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."ticket";
		$search = $this->prefix."____-%";
		$sql .= " WHERE ref LIKE '".$db->escape($search)."'";
		$sql .= " AND entity IN (".getEntity('ticketnumber', 1, $ticket).")";

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$max = intval($obj->max);
			} else {
				$max = 0;
			}
		} else {
			dol_syslog("mod_ticket_simple::getNextValue", LOG_DEBUG);
			return -1;
		}

		$date = empty($ticket->datec) ? dol_now() : $ticket->datec;

		//$yymm = strftime("%y%m",time());
		$yymm = strftime("%y%m", $date);

		if ($max >= (pow(10, 4) - 1)) {
			$num = $max + 1;
		} else {
			// If counter > 9999, we do not format on 4 chars, we take number as it is
			$num = sprintf("%04s", $max + 1);
		}

		dol_syslog("mod_ticket_simple::getNextValue return ".$this->prefix.$yymm."-".$num);
		return $this->prefix.$yymm."-".$num;
	}
}
