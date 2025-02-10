<?php
/*
 * Copyright (C) 2015      Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2023      Christian Foellmann     <christian@foellmann.de>
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

require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';


/**
 * API that gives the status of the Dolibarr instance.
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Status extends DolibarrApi
{
	/**
	 * Constructor of the class
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}

	/**
	 * Get status (Dolibarr version)
	 *
	 * @return array
	 */
	public function index()
	{
		global $dolibarr_main_prod;

		$response = array(
			'success' => array(
				'code' => 200,
				'dolibarr_version' => DOL_VERSION,
				'access_locked' => getDolGlobalString('MAIN_ONLY_LOGIN_ALLOWED', '0'),
			),
		);

		if (empty($dolibarr_main_prod)) {
			$response['success']['environment']       = 'non-production';
			$response['success']['timestamp_now_utc'] = dol_now();
			$response['success']['timestamp_php_tz']  = date_default_timezone_get();
			$response['success']['date_tz']           = dol_print_date(dol_now('gmt'), 'standard');
		}

		return $response;
	}
}
