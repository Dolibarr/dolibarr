<?php
/* Copyright (C) 2025 Florian Hoedl <florian@hoedl.co>
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
 * \file    htdocs/custom/earechnungat/class/ear_calculator.class.php
 * \brief   Calculator class for E/A-Rechnung data aggregation
 * \ingroup earechnungat
 */

/**
 * Class EARCalculator
 *
 * Aggregates financial data from Dolibarr tables for the Austrian
 * Einnahmen-Ausgaben-Rechnung. Supports both Ist-Besteuerung (payment date)
 * and Soll-Besteuerung (invoice date) modes.
 */
class EARCalculator
{
	/**
	 * @var DoliDB Database handler
	 */
	private $db;

	/**
	 * @var string Tax mode: 'payment' (Ist) or 'invoice' (Soll)
	 */
	private $taxMode = 'payment';

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->taxMode = getDolGlobalString('EARECHNUNGAT_TAX_MODE', 'payment');
	}

	/**
	 * Get customer income aggregated by VAT rate, based on payment date
	 *
	 * @param int      $dateStart Start timestamp
	 * @param int      $dateEnd   End timestamp
	 * @return array{by_rate:array,total_ht:float,total_vat:float,total_ttc:float,details:array}
	 */
	public function getCustomerIncome($dateStart, $dateEnd)
	{
		$result = array(
			'by_rate' => array(),
			'total_ht' => 0,
			'total_vat' => 0,
			'total_ttc' => 0,
			'details' => array(),
		);

		if ($this->taxMode === 'payment') {
			$sql = $this->getCustomerIncomeByPayment($dateStart, $dateEnd);
		} else {
			$sql = $this->getCustomerIncomeByInvoice($dateStart, $dateEnd);
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(get_class($this) . '::getCustomerIncome SQL error: ' . $this->db->lasterror(), LOG_ERR);
			return $result;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$rate = (string) price2num($obj->tva_tx);
			if (!isset($result['by_rate'][$rate])) {
				$result['by_rate'][$rate] = array('ht' => 0, 'vat' => 0, 'ttc' => 0);
			}
			$result['by_rate'][$rate]['ht'] += (float) $obj->total_ht;
			$result['by_rate'][$rate]['vat'] += (float) $obj->total_vat;
			$result['by_rate'][$rate]['ttc'] += (float) $obj->total_ht + (float) $obj->total_vat;
			$result['total_ht'] += (float) $obj->total_ht;
			$result['total_vat'] += (float) $obj->total_vat;
			$result['total_ttc'] += (float) $obj->total_ht + (float) $obj->total_vat;
		}
		$this->db->free($resql);

		// Load details for drill-down
		$result['details'] = $this->getCustomerIncomeDetails($dateStart, $dateEnd);

		return $result;
	}

	/**
	 * Get supplier expenses aggregated by VAT rate, based on payment date
	 *
	 * @param int $dateStart Start timestamp
	 * @param int $dateEnd   End timestamp
	 * @return array{by_rate:array,total_ht:float,total_vat:float,total_ttc:float,details:array}
	 */
	public function getSupplierExpenses($dateStart, $dateEnd)
	{
		$result = array(
			'by_rate' => array(),
			'total_ht' => 0,
			'total_vat' => 0,
			'total_ttc' => 0,
			'details' => array(),
		);

		if ($this->taxMode === 'payment') {
			$sql = $this->getSupplierExpensesByPayment($dateStart, $dateEnd);
		} else {
			$sql = $this->getSupplierExpensesByInvoice($dateStart, $dateEnd);
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(get_class($this) . '::getSupplierExpenses SQL error: ' . $this->db->lasterror(), LOG_ERR);
			return $result;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$rate = (string) price2num($obj->tva_tx);
			if (!isset($result['by_rate'][$rate])) {
				$result['by_rate'][$rate] = array('ht' => 0, 'vat' => 0, 'ttc' => 0);
			}
			$result['by_rate'][$rate]['ht'] += (float) $obj->total_ht;
			$result['by_rate'][$rate]['vat'] += (float) $obj->total_vat;
			$result['by_rate'][$rate]['ttc'] += (float) $obj->total_ht + (float) $obj->total_vat;
			$result['total_ht'] += (float) $obj->total_ht;
			$result['total_vat'] += (float) $obj->total_vat;
			$result['total_ttc'] += (float) $obj->total_ht + (float) $obj->total_vat;
		}
		$this->db->free($resql);

		$result['details'] = $this->getSupplierExpenseDetails($dateStart, $dateEnd);

		return $result;
	}

	/**
	 * Get salary payments in period
	 *
	 * @param int $dateStart Start timestamp
	 * @param int $dateEnd   End timestamp
	 * @return array{total:float,count:int,details:array}
	 */
	public function getSalaries($dateStart, $dateEnd)
	{
		$result = array('total' => 0, 'count' => 0, 'details' => array());

		$sql = "SELECT ps.rowid, ps.datep, ps.amount, ps.label, ps.fk_user,";
		$sql .= " u.firstname, u.lastname";
		$sql .= " FROM " . MAIN_DB_PREFIX . "payment_salary as ps";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON u.rowid = ps.fk_user";
		$sql .= " WHERE ps.entity IN (" . getEntity('payment_salary') . ")";
		$sql .= " AND ps.datep >= '" . $this->db->idate($dateStart) . "'";
		$sql .= " AND ps.datep <= '" . $this->db->idate($dateEnd) . "'";
		$sql .= " ORDER BY ps.datep";

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(get_class($this) . '::getSalaries SQL error: ' . $this->db->lasterror(), LOG_ERR);
			return $result;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$result['total'] += (float) $obj->amount;
			$result['count']++;
			$result['details'][] = array(
				'id' => $obj->rowid,
				'date' => $this->db->jdate($obj->datep),
				'amount' => (float) $obj->amount,
				'label' => $obj->label,
				'user' => trim($obj->firstname . ' ' . $obj->lastname),
			);
		}
		$this->db->free($resql);

		return $result;
	}

	/**
	 * Get social charges payments in period, grouped by charge type
	 *
	 * @param int $dateStart Start timestamp
	 * @param int $dateEnd   End timestamp
	 * @return array{total:float,by_type:array,details:array}
	 */
	public function getSocialCharges($dateStart, $dateEnd)
	{
		$result = array('total' => 0, 'by_type' => array(), 'details' => array());

		$sql = "SELECT pc.rowid, pc.datep, pc.amount,";
		$sql .= " cs.libelle as charge_label, cs.fk_type,";
		$sql .= " ccs.libelle as type_label, ccs.code as type_code";
		$sql .= " FROM " . MAIN_DB_PREFIX . "paiementcharge as pc";
		$sql .= " JOIN " . MAIN_DB_PREFIX . "chargesociales as cs ON cs.rowid = pc.fk_charge";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_chargesociales as ccs ON ccs.id = cs.fk_type";
		$sql .= " WHERE cs.entity IN (" . getEntity('chargesociales') . ")";
		$sql .= " AND pc.datep >= '" . $this->db->idate($dateStart) . "'";
		$sql .= " AND pc.datep <= '" . $this->db->idate($dateEnd) . "'";
		$sql .= " ORDER BY ccs.id, pc.datep";

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(get_class($this) . '::getSocialCharges SQL error: ' . $this->db->lasterror(), LOG_ERR);
			return $result;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$typeLabel = $obj->type_label ?: $obj->charge_label;
			$typeCode = $obj->type_code ?: 'OTHER';

			if (!isset($result['by_type'][$typeCode])) {
				$result['by_type'][$typeCode] = array(
					'label' => $typeLabel,
					'total' => 0,
					'count' => 0,
				);
			}
			$result['by_type'][$typeCode]['total'] += (float) $obj->amount;
			$result['by_type'][$typeCode]['count']++;
			$result['total'] += (float) $obj->amount;

			$result['details'][] = array(
				'id' => $obj->rowid,
				'date' => $this->db->jdate($obj->datep),
				'amount' => (float) $obj->amount,
				'charge_label' => $obj->charge_label,
				'type_label' => $typeLabel,
				'type_code' => $typeCode,
			);
		}
		$this->db->free($resql);

		return $result;
	}

	/**
	 * Get miscellaneous payments (payment_various) in period
	 *
	 * @param int $dateStart Start timestamp
	 * @param int $dateEnd   End timestamp
	 * @return array{income:float,expense:float,details:array}
	 */
	public function getMiscPayments($dateStart, $dateEnd)
	{
		$result = array('income' => 0, 'expense' => 0, 'details' => array());

		$sql = "SELECT pv.rowid, pv.datep, pv.amount, pv.sens, pv.label";
		$sql .= " FROM " . MAIN_DB_PREFIX . "payment_various as pv";
		$sql .= " WHERE pv.entity IN (" . getEntity('payment_various') . ")";
		$sql .= " AND pv.datep >= '" . $this->db->idate($dateStart) . "'";
		$sql .= " AND pv.datep <= '" . $this->db->idate($dateEnd) . "'";
		$sql .= " ORDER BY pv.datep";

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(get_class($this) . '::getMiscPayments SQL error: ' . $this->db->lasterror(), LOG_ERR);
			return $result;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$amount = (float) $obj->amount;
			// sens: 0 = expense, 1 = income
			if ($obj->sens == 1) {
				$result['income'] += $amount;
			} else {
				$result['expense'] += $amount;
			}
			$result['details'][] = array(
				'id' => $obj->rowid,
				'date' => $this->db->jdate($obj->datep),
				'amount' => $amount,
				'sens' => (int) $obj->sens,
				'label' => $obj->label,
			);
		}
		$this->db->free($resql);

		return $result;
	}

	/**
	 * Get VAT summary (USt collected vs VSt deducted)
	 *
	 * @param int $dateStart Start timestamp
	 * @param int $dateEnd   End timestamp
	 * @return array{ust_collected:float,vst_deducted:float,zahllast:float,ust_by_rate:array,vst_by_rate:array}
	 */
	public function getVATSummary($dateStart, $dateEnd)
	{
		$income = $this->getCustomerIncome($dateStart, $dateEnd);
		$expenses = $this->getSupplierExpenses($dateStart, $dateEnd);

		$result = array(
			'ust_collected' => $income['total_vat'],
			'vst_deducted' => $expenses['total_vat'],
			'zahllast' => $income['total_vat'] - $expenses['total_vat'],
			'ust_by_rate' => $income['by_rate'],
			'vst_by_rate' => $expenses['by_rate'],
		);

		return $result;
	}

	/**
	 * Generate a complete E/A report for a period
	 *
	 * @param int $dateStart Start timestamp
	 * @param int $dateEnd   End timestamp
	 * @return array Complete report data
	 */
	public function getFullReport($dateStart, $dateEnd)
	{
		$income = $this->getCustomerIncome($dateStart, $dateEnd);
		$expenses = $this->getSupplierExpenses($dateStart, $dateEnd);
		$salaries = $this->getSalaries($dateStart, $dateEnd);
		$socialCharges = $this->getSocialCharges($dateStart, $dateEnd);
		$misc = $this->getMiscPayments($dateStart, $dateEnd);

		$totalIncome = $income['total_ht'] + $misc['income'];
		$totalExpenses = $expenses['total_ht'] + $salaries['total'] + $socialCharges['total'] + $misc['expense'];

		return array(
			'income' => $income,
			'expenses' => $expenses,
			'salaries' => $salaries,
			'social_charges' => $socialCharges,
			'misc' => $misc,
			'total_income' => $totalIncome,
			'total_expenses' => $totalExpenses,
			'profit_loss' => $totalIncome - $totalExpenses,
			'vat' => array(
				'ust_collected' => $income['total_vat'],
				'vst_deducted' => $expenses['total_vat'],
				'zahllast' => $income['total_vat'] - $expenses['total_vat'],
			),
			'tax_mode' => $this->taxMode,
		);
	}

	/**
	 * Generate CSV data from report
	 *
	 * @param array $report Report data from getFullReport()
	 * @param int   $year   Year for header
	 * @return string CSV content
	 */
	public function generateCSV($report, $year)
	{
		global $langs;

		$sep = ';';
		$lines = array();

		$lines[] = implode($sep, array('Kategorie', 'Bezeichnung', 'Betrag EUR'));
		$lines[] = '';

		// Income
		$lines[] = implode($sep, array($langs->trans('Income'), '', ''));
		foreach ($report['income']['by_rate'] as $rate => $data) {
			$label = $langs->trans('CustomerIncome20', $rate);
			if ($rate == '20') {
				$label = $langs->trans('CustomerIncome20');
			} elseif ($rate == '10') {
				$label = $langs->trans('CustomerIncome10');
			} elseif ($rate == '0') {
				$label = $langs->trans('CustomerIncome0');
			}
			$lines[] = implode($sep, array('', $label, number_format($data['ht'], 2, ',', '')));
		}
		if ($report['misc']['income'] > 0) {
			$lines[] = implode($sep, array('', $langs->trans('MiscIncome'), number_format($report['misc']['income'], 2, ',', '')));
		}
		$lines[] = implode($sep, array('', $langs->trans('TotalIncome'), number_format($report['total_income'], 2, ',', '')));
		$lines[] = '';

		// Expenses
		$lines[] = implode($sep, array($langs->trans('Expenses'), '', ''));
		$lines[] = implode($sep, array('', $langs->trans('SupplierExpenses'), number_format($report['expenses']['total_ht'], 2, ',', '')));
		$lines[] = implode($sep, array('', $langs->trans('Salaries'), number_format($report['salaries']['total'], 2, ',', '')));

		foreach ($report['social_charges']['by_type'] as $code => $data) {
			$lines[] = implode($sep, array('', $langs->trans('SocialChargesDetail', $data['label']), number_format($data['total'], 2, ',', '')));
		}

		if ($report['misc']['expense'] > 0) {
			$lines[] = implode($sep, array('', $langs->trans('MiscExpenses'), number_format($report['misc']['expense'], 2, ',', '')));
		}
		$lines[] = implode($sep, array('', $langs->trans('TotalExpenses'), number_format($report['total_expenses'], 2, ',', '')));
		$lines[] = '';

		// Profit/Loss
		$lines[] = implode($sep, array($langs->trans('ProfitLoss'), '', number_format($report['profit_loss'], 2, ',', '')));
		$lines[] = '';

		// VAT
		$lines[] = implode($sep, array($langs->trans('VATOverview'), '', ''));
		$lines[] = implode($sep, array('', $langs->trans('VATCollected', ''), number_format($report['vat']['ust_collected'], 2, ',', '')));
		$lines[] = implode($sep, array('', $langs->trans('VATDeducted'), number_format($report['vat']['vst_deducted'], 2, ',', '')));
		$lines[] = implode($sep, array('', $langs->trans('VATPayable'), number_format($report['vat']['zahllast'], 2, ',', '')));

		return implode("\n", $lines);
	}

	// ---- Private SQL builders ----

	/**
	 * SQL for customer income by payment date (Ist-Besteuerung)
	 * Proportional allocation: each payment is distributed across invoice lines
	 *
	 * @param int $dateStart Start timestamp
	 * @param int $dateEnd   End timestamp
	 * @return string SQL query
	 */
	private function getCustomerIncomeByPayment($dateStart, $dateEnd)
	{
		$sql = "SELECT d.tva_tx,";
		$sql .= " SUM(d.total_ht * ABS(pf.amount) / ABS(f.total_ttc)) as total_ht,";
		$sql .= " SUM(d.total_tva * ABS(pf.amount) / ABS(f.total_ttc)) as total_vat";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
		$sql .= " JOIN " . MAIN_DB_PREFIX . "facturedet as d ON d.fk_facture = f.rowid";
		$sql .= " JOIN " . MAIN_DB_PREFIX . "paiement_facture as pf ON pf.fk_facture = f.rowid";
		$sql .= " JOIN " . MAIN_DB_PREFIX . "paiement as pa ON pa.rowid = pf.fk_paiement";
		$sql .= " WHERE f.entity IN (" . getEntity('facture') . ")";
		$sql .= " AND f.fk_statut IN (1, 2)";
		$sql .= " AND f.type IN (0, 1, 2, 3, 5)";
		$sql .= " AND f.total_ttc <> 0";
		$sql .= " AND pa.datep >= '" . $this->db->idate($dateStart) . "'";
		$sql .= " AND pa.datep <= '" . $this->db->idate($dateEnd) . "'";
		$sql .= " GROUP BY d.tva_tx";
		$sql .= " ORDER BY d.tva_tx";

		return $sql;
	}

	/**
	 * SQL for customer income by invoice date (Soll-Besteuerung)
	 *
	 * @param int $dateStart Start timestamp
	 * @param int $dateEnd   End timestamp
	 * @return string SQL query
	 */
	private function getCustomerIncomeByInvoice($dateStart, $dateEnd)
	{
		$sql = "SELECT d.tva_tx,";
		$sql .= " SUM(d.total_ht) as total_ht,";
		$sql .= " SUM(d.total_tva) as total_vat";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
		$sql .= " JOIN " . MAIN_DB_PREFIX . "facturedet as d ON d.fk_facture = f.rowid";
		$sql .= " WHERE f.entity IN (" . getEntity('facture') . ")";
		$sql .= " AND f.fk_statut IN (1, 2)";
		$sql .= " AND f.type IN (0, 1, 2, 3, 5)";
		$sql .= " AND f.datef >= '" . $this->db->idate($dateStart) . "'";
		$sql .= " AND f.datef <= '" . $this->db->idate($dateEnd) . "'";
		$sql .= " GROUP BY d.tva_tx";
		$sql .= " ORDER BY d.tva_tx";

		return $sql;
	}

	/**
	 * SQL for supplier expenses by payment date (Ist-Besteuerung)
	 *
	 * @param int $dateStart Start timestamp
	 * @param int $dateEnd   End timestamp
	 * @return string SQL query
	 */
	private function getSupplierExpensesByPayment($dateStart, $dateEnd)
	{
		$sql = "SELECT d.tva_tx,";
		$sql .= " SUM(d.total_ht * ABS(pf.amount) / ABS(f.total_ttc)) as total_ht,";
		$sql .= " SUM(d.tva * ABS(pf.amount) / ABS(f.total_ttc)) as total_vat";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f";
		$sql .= " JOIN " . MAIN_DB_PREFIX . "facture_fourn_det as d ON d.fk_facture_fourn = f.rowid";
		$sql .= " JOIN " . MAIN_DB_PREFIX . "paiementfourn_facturefourn as pf ON pf.fk_facturefourn = f.rowid";
		$sql .= " JOIN " . MAIN_DB_PREFIX . "paiementfourn as pa ON pa.rowid = pf.fk_paiementfourn";
		$sql .= " WHERE f.entity IN (" . getEntity('facture_fourn') . ")";
		$sql .= " AND f.fk_statut IN (1, 2)";
		$sql .= " AND f.type IN (0, 1, 2, 3, 5)";
		$sql .= " AND f.total_ttc <> 0";
		$sql .= " AND pa.datep >= '" . $this->db->idate($dateStart) . "'";
		$sql .= " AND pa.datep <= '" . $this->db->idate($dateEnd) . "'";
		$sql .= " GROUP BY d.tva_tx";
		$sql .= " ORDER BY d.tva_tx";

		return $sql;
	}

	/**
	 * SQL for supplier expenses by invoice date (Soll-Besteuerung)
	 *
	 * @param int $dateStart Start timestamp
	 * @param int $dateEnd   End timestamp
	 * @return string SQL query
	 */
	private function getSupplierExpensesByInvoice($dateStart, $dateEnd)
	{
		$sql = "SELECT d.tva_tx,";
		$sql .= " SUM(d.total_ht) as total_ht,";
		$sql .= " SUM(d.tva) as total_vat";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f";
		$sql .= " JOIN " . MAIN_DB_PREFIX . "facture_fourn_det as d ON d.fk_facture_fourn = f.rowid";
		$sql .= " WHERE f.entity IN (" . getEntity('facture_fourn') . ")";
		$sql .= " AND f.fk_statut IN (1, 2)";
		$sql .= " AND f.type IN (0, 1, 2, 3, 5)";
		$sql .= " AND f.datef >= '" . $this->db->idate($dateStart) . "'";
		$sql .= " AND f.datef <= '" . $this->db->idate($dateEnd) . "'";
		$sql .= " GROUP BY d.tva_tx";
		$sql .= " ORDER BY d.tva_tx";

		return $sql;
	}

	/**
	 * Get detailed customer income lines for drill-down
	 *
	 * @param int $dateStart Start timestamp
	 * @param int $dateEnd   End timestamp
	 * @return array List of payment details
	 */
	private function getCustomerIncomeDetails($dateStart, $dateEnd)
	{
		$details = array();

		if ($this->taxMode === 'payment') {
			$sql = "SELECT f.rowid as facid, f.ref as facref, f.datef,";
			$sql .= " pa.datep, pf.amount as paid_amount,";
			$sql .= " f.total_ht, f.total_tva, f.total_ttc,";
			$sql .= " s.nom as company_name, s.rowid as socid";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
			$sql .= " JOIN " . MAIN_DB_PREFIX . "paiement_facture as pf ON pf.fk_facture = f.rowid";
			$sql .= " JOIN " . MAIN_DB_PREFIX . "paiement as pa ON pa.rowid = pf.fk_paiement";
			$sql .= " JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
			$sql .= " WHERE f.entity IN (" . getEntity('facture') . ")";
			$sql .= " AND f.fk_statut IN (1, 2)";
			$sql .= " AND f.type IN (0, 1, 2, 3, 5)";
			$sql .= " AND f.total_ttc <> 0";
			$sql .= " AND pa.datep >= '" . $this->db->idate($dateStart) . "'";
			$sql .= " AND pa.datep <= '" . $this->db->idate($dateEnd) . "'";
			$sql .= " ORDER BY pa.datep, f.ref";
		} else {
			$sql = "SELECT f.rowid as facid, f.ref as facref, f.datef,";
			$sql .= " f.datef as datep, f.total_ttc as paid_amount,";
			$sql .= " f.total_ht, f.total_tva, f.total_ttc,";
			$sql .= " s.nom as company_name, s.rowid as socid";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
			$sql .= " JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
			$sql .= " WHERE f.entity IN (" . getEntity('facture') . ")";
			$sql .= " AND f.fk_statut IN (1, 2)";
			$sql .= " AND f.type IN (0, 1, 2, 3, 5)";
			$sql .= " AND f.datef >= '" . $this->db->idate($dateStart) . "'";
			$sql .= " AND f.datef <= '" . $this->db->idate($dateEnd) . "'";
			$sql .= " ORDER BY f.datef, f.ref";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$details[] = array(
					'facid' => $obj->facid,
					'facref' => $obj->facref,
					'date_invoice' => $this->db->jdate($obj->datef),
					'date_payment' => $this->db->jdate($obj->datep),
					'paid_amount' => (float) $obj->paid_amount,
					'total_ht' => (float) $obj->total_ht,
					'total_vat' => (float) $obj->total_tva,
					'total_ttc' => (float) $obj->total_ttc,
					'company_name' => $obj->company_name,
					'socid' => $obj->socid,
				);
			}
			$this->db->free($resql);
		}

		return $details;
	}

	/**
	 * Get detailed supplier expense lines for drill-down
	 *
	 * @param int $dateStart Start timestamp
	 * @param int $dateEnd   End timestamp
	 * @return array List of payment details
	 */
	private function getSupplierExpenseDetails($dateStart, $dateEnd)
	{
		$details = array();

		if ($this->taxMode === 'payment') {
			$sql = "SELECT f.rowid as facid, f.ref as facref, f.datef,";
			$sql .= " pa.datep, pf.amount as paid_amount,";
			$sql .= " f.total_ht, f.total_tva, f.total_ttc,";
			$sql .= " s.nom as company_name, s.rowid as socid";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f";
			$sql .= " JOIN " . MAIN_DB_PREFIX . "paiementfourn_facturefourn as pf ON pf.fk_facturefourn = f.rowid";
			$sql .= " JOIN " . MAIN_DB_PREFIX . "paiementfourn as pa ON pa.rowid = pf.fk_paiementfourn";
			$sql .= " JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
			$sql .= " WHERE f.entity IN (" . getEntity('facture_fourn') . ")";
			$sql .= " AND f.fk_statut IN (1, 2)";
			$sql .= " AND f.type IN (0, 1, 2, 3, 5)";
			$sql .= " AND f.total_ttc <> 0";
			$sql .= " AND pa.datep >= '" . $this->db->idate($dateStart) . "'";
			$sql .= " AND pa.datep <= '" . $this->db->idate($dateEnd) . "'";
			$sql .= " ORDER BY pa.datep, f.ref";
		} else {
			$sql = "SELECT f.rowid as facid, f.ref as facref, f.datef,";
			$sql .= " f.datef as datep, f.total_ttc as paid_amount,";
			$sql .= " f.total_ht, f.total_tva, f.total_ttc,";
			$sql .= " s.nom as company_name, s.rowid as socid";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f";
			$sql .= " JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
			$sql .= " WHERE f.entity IN (" . getEntity('facture_fourn') . ")";
			$sql .= " AND f.fk_statut IN (1, 2)";
			$sql .= " AND f.type IN (0, 1, 2, 3, 5)";
			$sql .= " AND f.datef >= '" . $this->db->idate($dateStart) . "'";
			$sql .= " AND f.datef <= '" . $this->db->idate($dateEnd) . "'";
			$sql .= " ORDER BY f.datef, f.ref";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$details[] = array(
					'facid' => $obj->facid,
					'facref' => $obj->facref,
					'date_invoice' => $this->db->jdate($obj->datef),
					'date_payment' => $this->db->jdate($obj->datep),
					'paid_amount' => (float) $obj->paid_amount,
					'total_ht' => (float) $obj->total_ht,
					'total_vat' => (float) $obj->total_tva,
					'total_ttc' => (float) $obj->total_ttc,
					'company_name' => $obj->company_name,
					'socid' => $obj->socid,
				);
			}
			$this->db->free($resql);
		}

		return $details;
	}
}
