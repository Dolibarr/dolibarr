<?php
/* Copyright (C) 2021 NextGestion  <contact@nextgestion.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       partnership/class/partnershiputils.class.php
 *  \ingroup    partnership
 *  \brief      Class with utilities
 */

//require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
dol_include_once('partnership/lib/partnership.lib.php');


/**
 *	Class with cron tasks of Partnership module
 */
class PartnershipUtils
{
	public $db;							//!< To store db handler
	public $error;							//!< To return error code (or message)
	public $errors=array();				//!< To return several error codes (or messages)


	/**
	 *  Constructor
	 *
	 *  @param	DoliDb		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		return 1;
	}


	/**
	 * Action executed by scheduler to cancel status of partnership when subscription is expired + x days. (Max number of cancel per call = $conf->global->PARTNERSHIP_MAX_CANCEL_PER_CALL)
	 *
	 * CAN BE A CRON TASK
	 *
	 * @return  int                 0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doCancelStatusOfPartnership()
	{
		global $conf, $langs, $user;

		$langs->load("agenda");

		$MAXPERCALL = (empty($conf->global->PARTNERSHIP_MAX_CANCEL_PER_CALL) ? 100 : $conf->global->PARTNERSHIP_MAX_CANCEL_PER_CALL);       // Limit to 100 per call

		$error = 0;
		$this->output = '';
		$this->error='';


		dol_syslog(__METHOD__." we cancel status of partnership ", LOG_DEBUG);

		$now = dol_now();

		// En cours de traitement ...

		return ($error ? 1: 0);
	}
}
