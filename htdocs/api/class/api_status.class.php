<?php
/*
 * Copyright (C) 2015      Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2023      Christian Foellmann     <christian@foellmann.de>
 * Copyright (C) 2025		MDW						<mdeweerd@users.noreply.github.com>
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
	 * Get status (Dolibarr version).
	 * Exact version and environment details are disclosed to admin users only,
	 * to avoid version/environment fingerprinting by non-admin API users.
	 *
	 * @return array
	 * @phan-return array{success:array{code:int,access_locked:string,dolibarr_version?:string,environment?:string,timestamp_now_utc?:int,timestamp_php_tz?:string,date_tz?:string}}
	 * @phpstan-return array{success:array{code:int,access_locked:string,dolibarr_version?:string,environment?:string,timestamp_now_utc?:int,timestamp_php_tz?:string,date_tz?:string}}
	 */
	public function index()
	{
		global $dolibarr_main_prod;

		$response = array(
			'success' => array(
				'code' => 200,
				'access_locked' => getDolGlobalString('MAIN_ONLY_LOGIN_ALLOWED', '0'),
			),
		);

		// Show technical details only to admin users (avoid information disclosure to non-admin users).
		if (DolibarrApiAccess::$user->admin) {
			$response['success']['dolibarr_version'] = DOL_VERSION;				// Full version
		} else {
			$response['success']['dolibarr_version'] = (float) DOL_VERSION;		// Major version
		}

		if (empty($dolibarr_main_prod) && DolibarrApiAccess::$user->admin) {
			$response['success']['environment']       = 'non-production';
			$response['success']['timestamp_now_utc'] = dol_now();
			$response['success']['timestamp_php_tz']  = date_default_timezone_get();
			$response['success']['date_tz']           = dol_print_date(dol_now('gmt'), 'standard');
		}

		return $response;
	}
}
