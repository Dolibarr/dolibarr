<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2019   Cedric Ancelin          <icedo.anc@gmail.com>
 * Copyright (C) 2023   Lionel Vessiller		<lvessiller@open-dsi.fr>
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

use Luracast\Restler\RestException;

/**
 * API class for accountancy
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 *
 */
class Accountancy extends DolibarrApi
{
	/**
	 *
	 * @var array $FIELDS Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array();

	/**
	 * @var BookKeeping $bookkeeping {@type BookKeeping}
	 */
	public $bookkeeping;

	/**
	 * @var AccountancyExport $accountancy_export {@type AccountancyExport}
	 */
	public $accountancyexport;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $langs;
		$this->db = $db;

		require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
		require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancyexport.class.php';

		$langs->load('accountancy');

		$this->bookkeeping = new BookKeeping($this->db);
		$this->accountancyexport = new AccountancyExport($this->db);
	}

	/**
	 * Accountancy export data
	 *
	 * @param       string		$period					Period : 'lastmonth', 'currentmonth', 'last3months', 'last6months', 'currentyear', 'lastyear', 'fiscalyear', 'lastfiscalyear', 'actualandlastfiscalyear' or 'custom' (see above)
	 * @param		string		$date_min				[=''] Start date of period if 'custom' is set in period parameter
	 *													Date format is 'YYYY-MM-DD'
	 * @param		string		$date_max				[=''] End date of period if 'custom' is set in period parameter
	 *													Date format is 'YYYY-MM-DD'
	 * @param		string		$format					[=''] by default uses '1' for 'Configurable (CSV)' for format number
	 *													or '1000' for FEC
	 *													or '1010' for FEC2
	 *													(see AccountancyExport class)
	 * @param		int			$lettering				[=0] by default don't export or 1 to export lettering data (columns 'letterring_code' and 'date_lettering' returns empty or not)
	 * @param		int			$alreadyexport			[=0] by default export data only if it's not yet exported or 1 already exported (always export data even if 'date_export" is set)
	 * @param		int			$notnotifiedasexport	[=0] by default notified as exported or 1 not notified as exported (when the export is done, notified or not the column 'date_export')
	 *
	 * @return	string
	 *
	 * @url		GET exportdata
	 *
	 * @throws	RestException	401		Insufficient rights
	 * @throws	RestException	404		Accountancy export period not found
	 * @throws	RestException	404		Accountancy export start or end date not defined
	 * @throws	RestException	404		Accountancy export format not found
	 * @throws	RestException	500		Error on accountancy export
	 */
	public function exportData($period, $date_min = '', $date_max = '', $format = '', $lettering = 0, $alreadyexport = 0, $notnotifiedasexport = 0)
	{
		global $conf, $langs;

		// check rights
		if (!DolibarrApiAccess::$user->hasRight('accounting', 'mouvements', 'export')) {
			throw new RestException(403, 'No permission to export accounting');
		}

		// check parameters
		$period_available_list = array('lastmonth', 'currentmonth', 'last3months', 'last6months', 'currentyear', 'lastyear', 'fiscalyear', 'lastfiscalyear', 'actualandlastfiscalyear', 'custom');
		if (!in_array($period, $period_available_list)) {
			throw new RestException(404, 'Accountancy export period not found');
		}
		if ($period == 'custom') {
			if ($date_min == '' && $date_max == '') {
				throw new RestException(404, 'Accountancy export start and end date for custom period not defined');
			}
		}
		if ($format == '') {
			$format = AccountancyExport::$EXPORT_TYPE_CONFIGURABLE; // uses default
		}

		// get objects
		$bookkeeping = $this->bookkeeping;
		$accountancyexport = $this->accountancyexport;

		// find export format code from format number
		$format_number_available_list = $accountancyexport->getType();
		if (is_numeric($format)) {
			$format_number = (int) $format;
		} else {
			$format_number = 0;
			$format_label_available_list = array_flip($format_number_available_list);
			if (isset($format_label_available_list[$format])) {
				$format_number = $format_label_available_list[$format];
			}
		}

		// get all format available and check if exists
		if (!array_key_exists($format_number, $format_number_available_list)) {
			throw new RestException(404, 'Accountancy export format not found');
		}

		$sortorder = 'ASC'; // by default
		$sortfield = 't.piece_num, t.rowid'; // by default

		// set filter for each period available
		$filter = array();
		$doc_date_start = null;
		$doc_date_end= null;
		$now = dol_now();
		$now_arr = dol_getdate($now);
		$now_month = $now_arr['mon'];
		$now_year = $now_arr['year'];
		if ($period == 'custom') {
			if ($date_min != '') {
				$time_min = strtotime($date_min);
				if ($time_min !== false) {
					$doc_date_start = $time_min;
				}
			}
			if ($date_max != '') {
				$time_max = strtotime($date_max);
				if ($time_max !== false) {
					$doc_date_end = $time_max;
				}
			}
		} elseif ($period == 'lastmonth') {
			$prev_date_arr = dol_get_prev_month($now_month, $now_year); // get previous month and year if month is january
			$doc_date_start = dol_mktime(0, 0, 0, $prev_date_arr['month'], 1, $prev_date_arr['year']); // first day of previous month
			$doc_date_end = dol_get_last_day($prev_date_arr['year'], $prev_date_arr['month']); // last day of previous month
		} elseif ($period == 'currentmonth') {
			$doc_date_start = dol_mktime(0, 0, 0, $now_month, 1, $now_year); // first day of current month
			$doc_date_end = dol_get_last_day($now_year, $now_month); // last day of current month
		} elseif ($period == 'last3months' || $period == 'last6months') {
			if ($period == 'last3months') {
				// last 3 months
				$nb_prev_month = 3;
			} else {
				// last 6 months
				$nb_prev_month = 6;
			}
			$prev_month_date_list = array();
			$prev_month_date_list[] = dol_get_prev_month($now_month, $now_year); // get previous month for index = 0
			for ($i = 1; $i < $nb_prev_month; $i++) {
				$prev_month_date_list[] = dol_get_prev_month($prev_month_date_list[$i-1]['month'], $prev_month_date_list[$i-1]['year']); // get i+1 previous month for index=i
			}
			$doc_date_start = dol_mktime(0, 0, 0, $prev_month_date_list[$nb_prev_month-1]['month'], 1, $prev_month_date_list[$nb_prev_month-1]['year']); // first day of n previous month for index=n-1
			$doc_date_end = dol_get_last_day($prev_month_date_list[0]['year'], $prev_month_date_list[0]['month']); // last day of previous month for index = 0
		} elseif ($period == 'currentyear' || $period == 'lastyear') {
			$period_year = $now_year;
			if ($period == 'lastyear') {
				$period_year--;
			}
			$doc_date_start = dol_mktime(0, 0, 0, 1, 1, $period_year); // first day of year
			$doc_date_end = dol_mktime(23, 59, 59, 12, 31, $period_year); // last day of year
		} elseif ($period == 'fiscalyear' || $period == 'lastfiscalyear' || $period == 'actualandlastfiscalyear') {
			// find actual fiscal year
			$cur_fiscal_period = getCurrentPeriodOfFiscalYear($this->db, $conf);
			$cur_fiscal_date_start = $cur_fiscal_period['date_start'];
			$cur_fiscal_date_end = $cur_fiscal_period['date_end'];

			if ($period == 'fiscalyear') {
				$doc_date_start = $cur_fiscal_date_start;
				$doc_date_end = $cur_fiscal_date_end;
			} else {
				// get one day before current fiscal date start (to find previous fiscal period)
				$prev_fiscal_date_search = dol_time_plus_duree($cur_fiscal_date_start, -1, 'd');

				// find previous fiscal year from current fiscal year
				$prev_fiscal_period = getCurrentPeriodOfFiscalYear($this->db, $conf, $prev_fiscal_date_search);
				$prev_fiscal_date_start = $prev_fiscal_period['date_start'];
				$prev_fiscal_date_end = $prev_fiscal_period['date_end'];

				if ($period == 'lastfiscalyear') {
					$doc_date_start = $prev_fiscal_date_start;
					$doc_date_end = $prev_fiscal_date_end;
				} else {
					// period == 'actualandlastfiscalyear'
					$doc_date_start = $prev_fiscal_date_start;
					$doc_date_end = $cur_fiscal_date_end;
				}
			}
		}
		if (is_numeric($doc_date_start)) {
			$filter['t.doc_date>='] = $doc_date_start;
		}
		if (is_numeric($doc_date_end)) {
			$filter['t.doc_date<='] = $doc_date_end;
		}

		// @FIXME Critical bugged. Never use fetchAll without limit !
		$result = $bookkeeping->fetchAll($sortorder, $sortfield, 0, 0, $filter, 'AND', $alreadyexport);

		if ($result < 0) {
			throw new RestException(500, 'Error bookkeeping fetch all : '.$bookkeeping->errorsToString());
		} else {
			// export files then exit
			if (empty($lettering)) {
				if (is_array($bookkeeping->lines)) {
					foreach ($bookkeeping->lines as $k => $movement) {
						unset($bookkeeping->lines[$k]->lettering_code);
						unset($bookkeeping->lines[$k]->date_lettering);
					}
				}
			}

			$error = 0;
			$this->db->begin();

			if (empty($notnotifiedasexport)) {
				if (is_array($bookkeeping->lines)) {
					foreach ($bookkeeping->lines as $movement) {
						$now = dol_now();

						$sql = " UPDATE " . MAIN_DB_PREFIX . "accounting_bookkeeping";
						$sql .= " SET date_export = '" . $this->db->idate($now) . "'";
						$sql .= " WHERE rowid = " . ((int) $movement->id);

						$result = $this->db->query($sql);
						if (!$result) {
							$accountancyexport->errors[] = $langs->trans('NotAllExportedMovementsCouldBeRecordedAsExportedOrValidated');
							$error++;
							break;
						}
					}
				}
			}

			// export and only write file without downloading
			if (!$error) {
				$result = $accountancyexport->export($bookkeeping->lines, $format_number, 0, 1, 2);
				if ($result < 0) {
					$error++;
				}
			}

			if ($error) {
				$this->db->rollback();
				throw new RestException(500, 'Error accountancy export : '.implode(',', $accountancyexport->errors));
			} else {
				$this->db->commit();
				exit();
			}
		}
	}
}
