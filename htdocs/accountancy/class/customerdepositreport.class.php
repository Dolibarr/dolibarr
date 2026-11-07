<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    htdocs/accountancy/class/customerdepositreport.class.php
 * \ingroup Accountancy
 * \brief   Read-only customer deposit control at a cutoff date
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

/**
 * Reconstruct dated receipts and applications from existing documents.
 * This report neither determines deferred revenue nor writes accounting entries.
 */
class CustomerDepositReport
{

	/** @var DoliDB Database handler */
	public $db;

	/** @var string Last error */
	public $error = '';

	/** @var stdClass[] Deposits on the current page */
	public $lines = array();

	/** @var int Number of matching deposits */
	public $num_total = 0;

	/** @var array<string,float> Amounts including tax on the current page */
	public $totals = array('received' => 0, 'applied' => 0, 'remaining' => 0);

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
	 * Control a deposit without allocating its receipts to VAT rates or order lines.
	 * Credit notes cap the deposit; cash refunds reduce net receipts separately.
	 *
	 * @param float $invoiced Deposit including tax
	 * @param float $received Positive cash receipts including tax
	 * @param float $refunded Cash refunds including tax, expressed positively
	 * @param float $credited Credit notes on the deposit including tax, expressed positively
	 * @param float $applied Applications on dated, validated invoices including tax
	 * @return array{received:float,remaining:float,status:string}
	 */
	public static function calculate($invoiced, $received, $refunded, $credited, $applied)
	{
		$net = (float) price2num($received - $refunded, 'MT');
		$capacity = (float) price2num(max(0, $invoiced - $credited), 'MT');
		$available = min(max(0, $net), $capacity);
		$remaining = (float) price2num(max(0, $available - max(0, $applied)), 'MT');
		$status = $remaining > 0 ? 'DepositControlOutstanding' : 'DepositControlCleared';
		if ($net < 0 || $credited > $invoiced || $net > $capacity || $applied > $available || $applied < 0) {
			$status = 'DepositControlInconsistent';
		}
		return array('received' => $net, 'remaining' => $remaining, 'status' => $status);
	}

	/**
	 * Fetch deposits received by the cutoff, with independent aggregates to avoid multiplication.
	 * Accounting reports stay in the source entity, like the native sales journal.
	 *
	 * @param int $date_cutoff Cutoff timestamp (end of the selected server calendar day)
	 * @param array{ref?:string,thirdparty?:string,status?:string,sale?:int,orders?:bool} $filter Search filters and caller access scope
	 * @param int $limit Maximum number of deposits
	 * @param int $offset Offset
	 * @param string $sortfield Sort column
	 * @param string $sortorder Sort direction
	 * @return int Number of fetched deposits, or -1 on error
	 */
	public function fetchAll($date_cutoff, $filter = array(), $limit = 100, $offset = 0, $sortfield = 'f.datef', $sortorder = 'ASC')
	{
		$this->lines = array();
		$this->num_total = 0;
		$this->totals = array('received' => 0, 'applied' => 0, 'remaining' => 0);
		$this->error = '';
		$cutoff = $this->db->idate(dol_get_last_hour($date_cutoff));
		$entities = getEntity('invoice', 0);
		$sqlpayments = 'SELECT pf.fk_facture, SUM(CASE WHEN pf.amount > 0 THEN pf.amount ELSE 0 END) AS receipts,';
		$sqlpayments .= ' SUM(CASE WHEN pf.amount < 0 THEN -pf.amount ELSE 0 END) AS refunds, MAX(p.datep) AS last_payment';
		$sqlpayments .= ' FROM '.$this->db->prefix().'paiement_facture AS pf';
		$sqlpayments .= ' INNER JOIN '.$this->db->prefix().'paiement AS p ON p.rowid = pf.fk_paiement';
		$sqlpayments .= " WHERE p.datep <= '".$this->db->escape($cutoff)."' AND p.entity IN (".$this->db->sanitize($entities).') GROUP BY pf.fk_facture';

		$sqlapplications = 'SELECT re.fk_facture_source, SUM(re.amount_ttc) AS applied';
		$sqlapplications .= ' FROM '.$this->db->prefix().'societe_remise_except AS re';
		$sqlapplications .= ' LEFT JOIN '.$this->db->prefix().'facturedet AS usedline ON usedline.rowid = re.fk_facture_line';
		$sqlapplications .= ' INNER JOIN '.$this->db->prefix().'facture AS usedinvoice ON usedinvoice.rowid = COALESCE(usedline.fk_facture, re.fk_facture)';
		$sqlapplications .= ' INNER JOIN '.$this->db->prefix().'facture AS source ON source.rowid = re.fk_facture_source';
		$sqlapplications .= ' WHERE re.discount_type = 0 AND re.entity = source.entity AND usedinvoice.entity = source.entity';
		$sqlapplications .= ' AND usedinvoice.fk_soc = source.fk_soc AND usedinvoice.entity IN ('.$this->db->sanitize($entities).')';
		$sqlapplications .= ' AND usedinvoice.fk_statut IN ('.Facture::STATUS_VALIDATED.','.Facture::STATUS_CLOSED.')';
		$sqlapplications .= " AND usedinvoice.datef <= '".$this->db->escape($cutoff)."'";
		$sqlapplications .= " AND (usedinvoice.date_valid IS NULL OR usedinvoice.date_valid <= '".$this->db->escape($cutoff)."')";
		$sqlapplications .= ' GROUP BY re.fk_facture_source';

		$sqlcredits = 'SELECT cn.fk_facture_source, SUM(ABS(cn.total_ttc)) AS credited';
		$sqlcredits .= ' FROM '.$this->db->prefix().'facture AS cn';
		$sqlcredits .= ' INNER JOIN '.$this->db->prefix().'facture AS source ON source.rowid = cn.fk_facture_source';
		$sqlcredits .= ' WHERE cn.type = '.Facture::TYPE_CREDIT_NOTE.' AND cn.entity IN ('.$this->db->sanitize($entities).')';
		$sqlcredits .= ' AND cn.entity = source.entity AND cn.fk_soc = source.fk_soc';
		$sqlcredits .= ' AND cn.fk_statut IN ('.Facture::STATUS_VALIDATED.','.Facture::STATUS_CLOSED.')';
		$sqlcredits .= " AND cn.datef <= '".$this->db->escape($cutoff)."' AND (cn.date_valid IS NULL OR cn.date_valid <= '".$this->db->escape($cutoff)."')";
		$sqlcredits .= ' GROUP BY cn.fk_facture_source';

		$sqlremaining = 'GREATEST(0, LEAST(GREATEST(0, pay.receipts - pay.refunds), GREATEST(0, f.total_ttc - COALESCE(credit.credited, 0))) - GREATEST(0, COALESCE(used.applied, 0)))';
		$sqlfrom = ' FROM '.$this->db->prefix().'facture AS f';
		$sqlfrom .= ' INNER JOIN '.$this->db->prefix().'societe AS s ON s.rowid = f.fk_soc';
		$sqlfrom .= ' INNER JOIN ('.$sqlpayments.') AS pay ON pay.fk_facture = f.rowid';
		$sqlfrom .= ' LEFT JOIN ('.$sqlapplications.') AS used ON used.fk_facture_source = f.rowid';
		$sqlfrom .= ' LEFT JOIN ('.$sqlcredits.') AS credit ON credit.fk_facture_source = f.rowid';
		$sqlfrom .= ' WHERE f.entity IN ('.$this->db->sanitize($entities).') AND f.type = '.Facture::TYPE_DEPOSIT;
		// Keep abandoned deposits for review instead of silently losing an outstanding receipt.
		$sqlfrom .= " AND f.fk_statut > 0 AND f.datef <= '".$this->db->escape($cutoff)."' AND pay.receipts > 0";
		$sqlfrom .= " AND (f.date_valid IS NULL OR f.date_valid <= '".$this->db->escape($cutoff)."')";
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
		if (isset($filter['status']) && $filter['status'] == 'outstanding') {
			$sqlfrom .= ' AND '.$sqlremaining.' > 0';
		} elseif (isset($filter['status']) && $filter['status'] == 'cleared') {
			$sqlfrom .= ' AND '.$sqlremaining.' = 0';
		}
		$resql = $this->db->query('SELECT COUNT(f.rowid) AS nb'.$sqlfrom);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		$count = $this->db->fetch_object($resql);
		if (is_object($count)) {
			$this->num_total = (int) $count->nb;
		}
		$this->db->free($resql);
		$sql = 'SELECT f.rowid, f.ref, f.type, f.datef, f.date_valid, f.fk_statut, f.total_ttc, f.fk_soc, s.nom AS thirdparty_name,';
		$sql .= ' pay.receipts, pay.refunds, pay.last_payment, COALESCE(used.applied, 0) AS applied, COALESCE(credit.credited, 0) AS credited';
		$sql .= $sqlfrom;
		if (!in_array($sortfield, array('f.ref', 'f.datef', 's.nom', 'f.total_ttc', 'pay.last_payment'), true)) {
			$sortfield = 'f.datef';
		}
		$sql .= $this->db->order($sortfield, $sortorder === 'DESC' ? 'DESC' : 'ASC').', f.rowid ASC';
		$sql .= $this->db->plimit(max(1, $limit), max(0, $offset));
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		while (is_object($line = $this->db->fetch_object($resql))) {
			$line->datef = $this->db->jdate($line->datef);
			$line->last_payment = $this->db->jdate($line->last_payment);
			$line->calculation = self::calculate((float) $line->total_ttc, (float) $line->receipts, (float) $line->refunds, (float) $line->credited, (float) $line->applied);
			if ((int) $line->fk_statut > Facture::STATUS_CLOSED || empty($line->date_valid)) {
				$line->calculation['status'] = 'DepositControlInconsistent';
			}
			$line->applications = array();
			$line->orders = array();
			$this->totals['received'] += $line->calculation['received'];
			$this->totals['applied'] += (float) $line->applied;
			$this->totals['remaining'] += $line->calculation['remaining'];
			$this->lines[(int) $line->rowid] = $line;
		}
		$this->db->free($resql);
		if ($this->lines && $this->fetchEvidence($date_cutoff, !empty($filter['orders'])) < 0) {
			// Incomplete evidence must never be presented as a complete control.
			$this->lines = array();
			$this->totals = array('received' => 0, 'applied' => 0, 'remaining' => 0);
			return -1;
		}
		foreach ($this->totals as $key => $amount) {
			$this->totals[$key] = (float) price2num($amount, 'MT');
		}
		return count($this->lines);
	}

	/**
	 * Batch-load applications (including later/draft ones) and directly linked orders.
	 * Current links are evidence, not an immutable history of deletions or status changes.
	 *
	 * @param int $date_cutoff Cutoff timestamp
	 * @param bool $withorders Whether the caller may read orders
	 * @return int 1 on success, -1 on error
	 */
	private function fetchEvidence($date_cutoff, $withorders)
	{
		$ids = implode(',', array_keys($this->lines));
		$sql = 'SELECT re.fk_facture_source, target.rowid, target.ref, target.type, target.datef, target.date_valid, target.fk_statut, SUM(re.amount_ttc) AS amount';
		$sql .= ' FROM '.$this->db->prefix().'societe_remise_except AS re';
		$sql .= ' LEFT JOIN '.$this->db->prefix().'facturedet AS usedline ON usedline.rowid = re.fk_facture_line';
		$sql .= ' INNER JOIN '.$this->db->prefix().'facture AS target ON target.rowid = COALESCE(usedline.fk_facture, re.fk_facture)';
		$sql .= ' INNER JOIN '.$this->db->prefix().'facture AS source ON source.rowid = re.fk_facture_source';
		$sql .= ' WHERE re.fk_facture_source IN ('.$this->db->sanitize($ids).') AND re.discount_type = 0';
		$sql .= ' AND re.entity = source.entity AND target.entity = source.entity AND target.fk_soc = source.fk_soc';
		$sql .= ' GROUP BY re.fk_facture_source, target.rowid, target.ref, target.type, target.datef, target.date_valid, target.fk_statut';
		$sql .= $this->db->order('target.datef, target.rowid', 'ASC');
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		while (is_object($application = $this->db->fetch_object($resql))) {
			$application->datef = $this->db->jdate($application->datef);
			$validation = $this->db->jdate($application->date_valid);
			$application->at_cutoff = in_array((int) $application->fk_statut, array(Facture::STATUS_VALIDATED, Facture::STATUS_CLOSED), true)
				&& $application->datef && $application->datef <= dol_get_last_hour($date_cutoff)
				&& (!$validation || $validation <= dol_get_last_hour($date_cutoff));
			if (isset($this->lines[(int) $application->fk_facture_source])) {
				$this->lines[(int) $application->fk_facture_source]->applications[] = $application;
			}
		}
		$this->db->free($resql);
		if (!$withorders) {
			return 1;
		}
		$sql = 'SELECT DISTINCT ee.fk_target, c.rowid, c.ref, c.fk_statut, c.date_commande, c.date_livraison, c.date_cloture';
		$sql .= ' FROM '.$this->db->prefix().'element_element AS ee';
		$sql .= ' INNER JOIN '.$this->db->prefix().'commande AS c ON c.rowid = ee.fk_source';
		$sql .= ' INNER JOIN '.$this->db->prefix().'facture AS f ON f.rowid = ee.fk_target';
		$sql .= " WHERE ee.sourcetype = 'commande' AND ee.targettype = 'facture' AND ee.fk_target IN (".$this->db->sanitize($ids).')';
		$sql .= ' AND c.entity IN ('.$this->db->sanitize(getEntity('commande')).') AND c.fk_soc = f.fk_soc';
		$sql .= " AND c.date_commande <= '".$this->db->escape($this->db->idate(dol_get_last_hour($date_cutoff)))."'";
		$sql .= $this->db->order('c.ref', 'ASC');
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		while (is_object($order = $this->db->fetch_object($resql))) {
			$order->date_livraison = $this->db->jdate($order->date_livraison);
			$order->date_cloture = $this->db->jdate($order->date_cloture);
			if (isset($this->lines[(int) $order->fk_target])) {
				$this->lines[(int) $order->fk_target]->orders[] = $order;
			}
		}
		$this->db->free($resql);
		return 1;
	}
}
