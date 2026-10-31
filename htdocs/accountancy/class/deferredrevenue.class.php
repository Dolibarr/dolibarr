<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    htdocs/accountancy/class/deferredrevenue.class.php
 * \ingroup Accountancy
 * \brief   Read-only calculation of deferred revenue from invoice periods
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

/**
 * Read-only report, without a persistent business object or accounting entries.
 */
class DeferredRevenue
{

	/** @var DoliDB Database handler */
	public $db;

	/** @var string Last error */
	public $error = '';

	/** @var stdClass[] Invoice lines on the current page */
	public $lines = array();

	/** @var int Number of matching invoice lines */
	public $num_total = 0;

	/** @var float Deferred amount on the current page */
	public $total = 0;

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
	 * Calculate the portion after the cutoff, including both service boundary days.
	 *
	 * @param float     $amount      Signed amount excluding tax
	 * @param int|false $date_start  Service start timestamp, false if missing
	 * @param int|false $date_end    Service end timestamp, false if missing
	 * @param int       $date_cutoff Cutoff timestamp
	 * @return array{amount:?float,total_days:int,deferred_days:int,status:string}
	 */
	public static function calculate($amount, $date_start, $date_end, $date_cutoff)
	{
		$result = array('amount' => null, 'total_days' => 0, 'deferred_days' => 0, 'status' => 'DeferredRevenueMissingDates');
		if (!$date_start || !$date_end) {
			return $result;
		}

		// Use calendar dates in UTC for day counts, independently of daylight saving.
		$dates = array();
		foreach (array($date_start, $date_end, $date_cutoff) as $timestamp) {
			$parts = dol_getdate($timestamp, false, 'tzserver');
			$dates[] = dol_mktime(0, 0, 0, $parts['mon'], $parts['mday'], $parts['year'], 'gmt');
		}
		if ($dates[1] < $dates[0]) {
			$result['status'] = 'DeferredRevenueInvalidDates';
			return $result;
		}

		$result['total_days'] = num_between_day($dates[0], $dates[1], 1);
		if ($dates[2] < $dates[0]) {
			$result['deferred_days'] = $result['total_days'];
		} elseif ($dates[2] < $dates[1]) {
			$result['deferred_days'] = num_between_day($dates[2], $dates[1]);
		}
		$result['amount'] = (float) price2num($amount * $result['deferred_days'] / $result['total_days'], 'MT');
		$result['status'] = 'DeferredRevenueCalculated';
		return $result;
	}

	/**
	 * Fetch candidates in the accounting entity, without changing source documents.
	 *
	 * @param int    $date_cutoff Cutoff timestamp
	 * @param array{ref?:string,thirdparty?:string,status?:string,sale?:int} $filter Search filters, including the caller's commercial scope
	 * @param int    $limit       Maximum number of lines
	 * @param int    $offset      Offset
	 * @param string $sortfield   Sort column
	 * @param string $sortorder   Sort direction
	 * @return int               Number of fetched lines, or -1 on error
	 */
	public function fetchAll($date_cutoff, $filter = array(), $limit = 100, $offset = 0, $sortfield = 'f.datef', $sortorder = 'ASC')
	{
		$this->lines = array();
		$this->num_total = 0;
		$this->total = 0;
		$this->error = '';
		$cutoff = $this->db->idate(dol_get_last_hour($date_cutoff));

		$sqlfrom = ' FROM '.$this->db->prefix().'facturedet AS fd';
		$sqlfrom .= ' INNER JOIN '.$this->db->prefix().'facture AS f ON f.rowid = fd.fk_facture';
		$sqlfrom .= ' INNER JOIN '.$this->db->prefix().'societe AS s ON s.rowid = f.fk_soc';
		$sqlfrom .= ' LEFT JOIN '.$this->db->prefix().'accounting_account AS aa ON aa.rowid = fd.fk_code_ventilation';
		$sqlfrom .= ' AND aa.entity IN ('.$this->db->sanitize(getEntity('accounting', 0)).')';
		// Like the sales journal, accounting reports stay in the source entity.
		$sqlfrom .= ' WHERE f.entity IN ('.$this->db->sanitize(getEntity('invoice', 0)).')';
		$sqlfrom .= ' AND f.fk_statut IN ('.Facture::STATUS_VALIDATED.','.Facture::STATUS_CLOSED.')';
		$sqlfrom .= ' AND f.type IN ('.Facture::TYPE_STANDARD.','.Facture::TYPE_REPLACEMENT.','.Facture::TYPE_CREDIT_NOTE.','.Facture::TYPE_SITUATION.')';
		$sqlfrom .= " AND f.datef <= '".$this->db->escape($cutoff)."'";
		$sqlfrom .= ' AND fd.product_type IN (0, 1) AND fd.total_ht <> 0';
		$sqlfrom .= ' AND (fd.fk_remise_except IS NULL OR fd.fk_remise_except = 0)';
		$sqlfrom .= " AND (fd.date_end IS NULL OR fd.date_start IS NULL OR fd.date_start > fd.date_end OR fd.date_end > '".$this->db->escape($cutoff)."')";

		$sqlcalculable = 'f.type <> '.Facture::TYPE_SITUATION.' AND fd.date_start IS NOT NULL AND fd.date_end IS NOT NULL AND fd.date_start <= fd.date_end';
		if (empty($filter['status']) || $filter['status'] == 'calculated') {
			$sqlfrom .= ' AND ('.$sqlcalculable.')';
		} elseif ($filter['status'] == 'review') {
			$sqlfrom .= ' AND NOT ('.$sqlcalculable.')';
		}
		if (!empty($filter['ref'])) {
			$sqlfrom .= natural_search('f.ref', $filter['ref']);
		}
		if (!empty($filter['thirdparty'])) {
			$sqlfrom .= natural_search('s.nom', $filter['thirdparty']);
		}
		if (!empty($filter['sale'])) {
			$sqlfrom .= ' AND EXISTS (SELECT sc.fk_soc FROM '.$this->db->prefix().'societe_commerciaux AS sc';
			$sqlfrom .= ' WHERE sc.fk_soc = f.fk_soc AND sc.fk_user = '.((int) $filter['sale']).')';
		}

		$resql = $this->db->query('SELECT COUNT(fd.rowid) AS nb'.$sqlfrom);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		$count = $this->db->fetch_object($resql);
		if (is_object($count)) {
			$this->num_total = (int) $count->nb;
		}
		$this->db->free($resql);

		$sql = 'SELECT fd.rowid, fd.description, fd.date_start, fd.date_end, fd.total_ht,';
		$sql .= ' f.rowid AS invoice_id, f.ref, f.type, f.datef, f.fk_soc, s.nom AS thirdparty_name, aa.account_number';
		$sql .= $sqlfrom;
		if (!in_array($sortfield, array('f.ref', 'f.datef', 's.nom', 'fd.description', 'fd.date_start', 'fd.date_end', 'aa.account_number', 'fd.total_ht'), true)) {
			$sortfield = 'f.datef';
		}
		$sortorder = ($sortorder === 'DESC' ? 'DESC' : 'ASC');
		$sql .= $this->db->order($sortfield, $sortorder).', fd.rowid ASC';
		$sql .= $this->db->plimit(max(1, $limit), max(0, $offset));
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		while (is_object($line = $this->db->fetch_object($resql))) {
			$line->date_start = $this->db->jdate($line->date_start);
			$line->date_end = $this->db->jdate($line->date_end);
			$line->datef = $this->db->jdate($line->datef);
			$calculation = self::calculate((float) $line->total_ht, $line->date_start, $line->date_end, $date_cutoff);
			if ((int) $line->type == Facture::TYPE_SITUATION) {
				$calculation['amount'] = null;
				$calculation['status'] = 'DeferredRevenueProgressInvoice';
			}
			$line->calculation = $calculation;
			if ($calculation['amount'] !== null) {
				$this->total += $calculation['amount'];
			}
			$this->lines[] = $line;
		}
		$this->db->free($resql);
		$this->total = (float) price2num($this->total, 'MT');
		return count($this->lines);
	}
}
