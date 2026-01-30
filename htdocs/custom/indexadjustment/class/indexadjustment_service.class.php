<?php
/* Copyright (C) 2025 Florian Hödl <florian@hoedl.co>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       class/indexadjustment_service.class.php
 * \ingroup    indexadjustment
 * \brief      Service class for IndexAdjustment business logic
 */

require_once __DIR__ . '/indexadjustment.class.php';
require_once __DIR__ . '/indexadjustmentline.class.php';
require_once __DIR__ . '/indexadjustment_calculator.class.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

/**
 * Class IndexAdjustmentService
 *
 * Handles business logic for index adjustments:
 * - Fetching eligible contracts
 * - Preview calculations
 * - Executing adjustments
 * - Rollback operations
 * - Event documentation
 */
class IndexAdjustmentService
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var IndexAdjustmentCalculator Calculator instance
	 */
	public $calculator;

	/**
	 * @var string Error message
	 */
	public $error;

	/**
	 * @var array Error messages
	 */
	public $errors = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->calculator = new IndexAdjustmentCalculator();
	}

	/**
	 * Fetch active contracts eligible for adjustment
	 *
	 * @param int|null $customerId Optional customer ID filter
	 * @return array               Array of contracts indexed by ID
	 */
	public function fetchActiveContracts($customerId = null)
	{
		$contracts = array();

		$sql = "SELECT c.rowid, c.ref, c.ref_customer, c.ref_supplier,";
		$sql .= " s.rowid as socid, s.nom as socname,";
		$sql .= " COUNT(cd.rowid) as active_lines";
		$sql .= " FROM " . MAIN_DB_PREFIX . "contrat as c";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON c.fk_soc = s.rowid";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "contratdet as cd ON cd.fk_contrat = c.rowid AND cd.statut = 4";
		$sql .= " WHERE c.statut = 1"; // Contract validated
		$sql .= " AND c.entity IN (" . getEntity('contrat') . ")";

		if ($customerId) {
			$sql .= " AND c.fk_soc = " . (int)$customerId;
		}

		$sql .= " GROUP BY c.rowid, c.ref, c.ref_customer, c.ref_supplier, s.rowid, s.nom";
		$sql .= " ORDER BY s.nom ASC, c.ref ASC";

		$result = $this->db->query($sql);
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				$contracts[$obj->rowid] = array(
					'id' => $obj->rowid,
					'ref' => $obj->ref,
					'ref_customer' => $obj->ref_customer,
					'ref_supplier' => $obj->ref_supplier,
					'socid' => $obj->socid,
					'socname' => $obj->socname,
					'active_lines' => $obj->active_lines,
				);
			}
		}

		return $contracts;
	}

	/**
	 * Fetch active service lines for a contract
	 *
	 * Only returns lines with statut=4 (active running service)
	 *
	 * @param int $contractId Contract ID
	 * @return array          Array of active service lines
	 */
	public function fetchActiveServiceLines($contractId)
	{
		$lines = array();

		$sql = "SELECT cd.rowid, cd.fk_contrat, cd.fk_product,";
		$sql .= " cd.description, cd.subprice, cd.qty, cd.tva_tx,";
		$sql .= " cd.total_ht, cd.total_tva, cd.total_ttc,";
		$sql .= " cd.statut, cd.date_ouverture, cd.date_fin_validite,";
		$sql .= " p.ref as product_ref, p.label as product_label";
		$sql .= " FROM " . MAIN_DB_PREFIX . "contratdet as cd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON cd.fk_product = p.rowid";
		$sql .= " WHERE cd.fk_contrat = " . (int)$contractId;
		$sql .= " AND cd.statut = 4"; // Active running service

		$result = $this->db->query($sql);
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				$lines[$obj->rowid] = array(
					'id' => $obj->rowid,
					'fk_contrat' => $obj->fk_contrat,
					'fk_product' => $obj->fk_product,
					'description' => $obj->description,
					'subprice' => (float)$obj->subprice,
					'qty' => (float)$obj->qty,
					'tva_tx' => (float)$obj->tva_tx,
					'total_ht' => (float)$obj->total_ht,
					'total_tva' => (float)$obj->total_tva,
					'total_ttc' => (float)$obj->total_ttc,
					'statut' => $obj->statut,
					'date_ouverture' => $obj->date_ouverture,
					'date_fin_validite' => $obj->date_fin_validite,
					'product_ref' => $obj->product_ref,
					'product_label' => $obj->product_label ?: $obj->description,
				);
			}
		}

		return $lines;
	}

	/**
	 * Preview adjustments for selected contracts
	 *
	 * @param array $contractIds Array of contract IDs
	 * @param float $percent     Adjustment percentage
	 * @param float $threshold   Minimum threshold (default 0)
	 * @return array             Preview data with contracts and totals
	 */
	public function previewAdjustments($contractIds, $percent, $threshold = 0)
	{
		$preview = array(
			'contracts' => array(),
			'totals' => array(
				'total_contracts' => 0,
				'total_lines' => 0,
				'total_ht_before' => 0,
				'total_ht_after' => 0,
				'total_diff' => 0,
			),
		);

		// Check threshold
		if ($threshold > 0 && !$this->calculator->meetsThreshold($percent, $threshold)) {
			return $preview;
		}

		foreach ($contractIds as $contractId) {
			$lines = $this->fetchActiveServiceLines($contractId);

			if (empty($lines)) {
				continue;
			}

			$contractData = array(
				'id' => $contractId,
				'lines' => array(),
				'totals' => array(
					'total_ht_before' => 0,
					'total_ht_after' => 0,
					'total_diff' => 0,
				),
			);

			foreach ($lines as $lineId => $line) {
				$subpriceAfter = $this->calculator->calculateAdjustedPrice($line['subprice'], $percent);
				$totalHtAfter = $this->calculator->calculateTotalHT($subpriceAfter, $line['qty']);
				$priceDiff = $this->calculator->calculatePriceDifference($line['subprice'], $subpriceAfter);

				$lineData = array(
					'id' => $lineId,
					'fk_contrat' => $contractId,
					'product_ref' => $line['product_ref'],
					'product_label' => $line['product_label'],
					'qty' => $line['qty'],
					'tva_tx' => $line['tva_tx'],
					'statut' => $line['statut'],
					'subprice_before' => $line['subprice'],
					'subprice_after' => $subpriceAfter,
					'total_ht_before' => $line['total_ht'],
					'total_ht_after' => $totalHtAfter,
					'price_diff' => $priceDiff,
					'total_diff' => $totalHtAfter - $line['total_ht'],
				);

				$contractData['lines'][$lineId] = $lineData;
				$contractData['totals']['total_ht_before'] += $line['total_ht'];
				$contractData['totals']['total_ht_after'] += $totalHtAfter;
				$contractData['totals']['total_diff'] += ($totalHtAfter - $line['total_ht']);
			}

			if (!empty($contractData['lines'])) {
				$preview['contracts'][$contractId] = $contractData;
				$preview['totals']['total_contracts']++;
				$preview['totals']['total_lines'] += count($contractData['lines']);
				$preview['totals']['total_ht_before'] += $contractData['totals']['total_ht_before'];
				$preview['totals']['total_ht_after'] += $contractData['totals']['total_ht_after'];
				$preview['totals']['total_diff'] += $contractData['totals']['total_diff'];
			}
		}

		return $preview;
	}

	/**
	 * Execute index adjustment
	 *
	 * @param IndexAdjustment $adjustment Adjustment object (must be validated)
	 * @param User            $user       User executing
	 * @param array           $contractIds Contract IDs to adjust
	 * @return int                        <0 if KO, >0 if OK
	 * @throws Exception                  If validation fails
	 */
	public function execute($adjustment, $user, $contractIds = array())
	{
		global $langs;

		// Validate adjustment status
		if ($adjustment->status != IndexAdjustment::STATUS_VALIDATED) {
			throw new Exception($langs->trans('AdjustmentMustBeValidatedBeforeExecution') ?: 'Adjustment must be validated before execution');
		}

		// Validate required fields
		if (empty($adjustment->label) || $adjustment->adjustment_percent === null) {
			throw new Exception($langs->trans('RequiredFieldsMissing') ?: 'Required fields missing');
		}

		$this->db->begin();

		try {
			$percent = $adjustment->adjustment_percent;
			$preview = $this->previewAdjustments($contractIds, $percent);

			if ($preview['totals']['total_lines'] == 0) {
				throw new Exception($langs->trans('NoActiveServicesFound') ?: 'No active services found');
			}

			$contract = new Contrat($this->db);

			// Process each contract
			foreach ($preview['contracts'] as $contractId => $contractData) {
				$contract->fetch($contractId);

				// Process each line
				foreach ($contractData['lines'] as $lineId => $lineData) {
					// Create audit record (including tva_tx for rollback)
					$line = new IndexAdjustmentLine($this->db);
					$line->fk_indexadjustment = $adjustment->id;
					$line->fk_contrat = $contractId;
					$line->fk_contratdet = $lineId;
					$line->product_ref = $lineData['product_ref'];
					$line->product_label = $lineData['product_label'];
					$line->subprice_before = $lineData['subprice_before'];
					$line->qty = $lineData['qty'];
					$line->total_ht_before = $lineData['total_ht_before'];
					$line->tva_tx = $lineData['tva_tx'];
					$line->subprice_after = $lineData['subprice_after'];
					$line->total_ht_after = $lineData['total_ht_after'];
					$line->price_diff_ht = $lineData['price_diff'];

					$resultLine = $line->create($user);
					if ($resultLine < 0) {
						throw new Exception('Failed to create adjustment line: ' . $line->error);
					}

					// Update contract line price using Dolibarr API
					// Signature: updateline($rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $tvatx, $localtax1tx, $localtax2tx, $date_start_real, $date_end_real, $price_base_type)
					$resultUpdate = $contract->updateline(
						$lineId,                        // rowid
						$lineData['product_label'],     // desc
						$lineData['subprice_after'],    // pu (new subprice)
						$lineData['qty'],               // qty
						0,                              // remise_percent
						'',                             // date_start
						'',                             // date_end
						$lineData['tva_tx'],            // tvatx
						0,                              // localtax1tx
						0,                              // localtax2tx
						'',                             // date_start_real
						'',                             // date_end_real
						'HT'                            // price_base_type
					);

					if ($resultUpdate < 0) {
						throw new Exception('Failed to update contract line: ' . $contract->error);
					}
				}

				// Create ActionComm event for this contract
				$this->createContractEvent($adjustment, $contract, $contractData, $user);
			}

			// Update adjustment statistics
			$adjustment->total_contracts = $preview['totals']['total_contracts'];
			$adjustment->total_lines = $preview['totals']['total_lines'];
			$adjustment->total_ht_before = $preview['totals']['total_ht_before'];
			$adjustment->total_ht_after = $preview['totals']['total_ht_after'];
			$adjustment->setExecuted($user);

			$this->db->commit();
			return 1;

		} catch (Exception $e) {
			$this->db->rollback();
			$this->error = $e->getMessage();
			$this->errors[] = $e->getMessage();
			return -1;
		}
	}

	/**
	 * Create ActionComm event for a contract adjustment
	 *
	 * @param IndexAdjustment $adjustment Adjustment object
	 * @param Contrat         $contract   Contract object
	 * @param array           $data       Contract adjustment data
	 * @param User            $user       User
	 * @return int                        <0 if KO, >0 if OK
	 */
	protected function createContractEvent($adjustment, $contract, $data, $user)
	{
		global $langs, $conf;

		$langs->load("indexadjustment@indexadjustment");

		$actioncomm = new ActionComm($this->db);

		// Use custom action type for index adjustments
		$actioncomm->type_code = 'AC_INDEXADJUST';
		$actioncomm->code = 'AC_INDEXADJUST';

		// Label with adjustment reference and percentage
		$percentSign = $adjustment->adjustment_percent >= 0 ? '+' : '';
		$actioncomm->label = 'Indexanpassung ' . $adjustment->ref . ': ' . $percentSign . number_format($adjustment->adjustment_percent, 2) . '%';

		// Build detailed note
		$note = "INDEXANPASSUNG DURCHGEFÜHRT\n\n";

		$note .= "Referenz:        " . $adjustment->ref . "\n";
		$note .= "Bezeichnung:     " . $adjustment->label . "\n";
		$note .= "Anpassung:       " . $percentSign . number_format($adjustment->adjustment_percent, 2) . "%\n";
		$note .= "Anpassungsdatum: " . dol_print_date($adjustment->adjustment_date, 'day') . "\n";
		$note .= "Ausgeführt am:   " . dol_print_date(dol_now(), 'dayhour') . "\n";
		$note .= "\n";

		$note .= "ANGEPASSTE POSITIONEN\n\n";

		$lineNum = 1;
		foreach ($data['lines'] as $lineData) {
			$lineDiff = $lineData['price_diff'];
			$lineDiffSign = $lineDiff >= 0 ? '+' : '';

			$note .= "Position " . $lineNum . ": " . ($lineData['product_ref'] ? $lineData['product_ref'] . ' - ' : '') . $lineData['product_label'] . "\n";
			$note .= "   Menge:         " . $lineData['qty'] . "\n";
			$note .= "   Preis vorher:  " . price($lineData['subprice_before'], 0, $langs, 1, -1, 2) . " " . $conf->currency . "\n";
			$note .= "   Preis nachher: " . price($lineData['subprice_after'], 0, $langs, 1, -1, 2) . " " . $conf->currency . "\n";
			$note .= "   Differenz:     " . $lineDiffSign . price($lineDiff, 0, $langs, 1, -1, 2) . " " . $conf->currency . "\n";
			$note .= "\n";
			$lineNum++;
		}

		$note .= "ZUSAMMENFASSUNG VERTRAG\n\n";

		$totalDiff = $data['totals']['total_diff'];
		$totalDiffSign = $totalDiff >= 0 ? '+' : '';

		$note .= "Positionen:      " . count($data['lines']) . "\n";
		$note .= "Gesamt vorher:   " . price($data['totals']['total_ht_before'], 0, $langs, 1, -1, 2) . " " . $conf->currency . "\n";
		$note .= "Gesamt nachher:  " . price($data['totals']['total_ht_after'], 0, $langs, 1, -1, 2) . " " . $conf->currency . "\n";
		$note .= "Differenz:       " . $totalDiffSign . price($totalDiff, 0, $langs, 1, -1, 2) . " " . $conf->currency . "\n";

		$actioncomm->note_private = $note;

		$actioncomm->datep = dol_now();
		$actioncomm->datef = dol_now();
		$actioncomm->durationp = 0;
		$actioncomm->punctual = 1;
		$actioncomm->percentage = -1; // Not applicable
		$actioncomm->socid = $contract->fk_soc;
		$actioncomm->fk_element = $contract->id;
		$actioncomm->elementtype = 'contrat';
		$actioncomm->userownerid = $user->id;
		$actioncomm->userassigned = array($user->id => array('id' => $user->id));

		return $actioncomm->create($user);
	}

	/**
	 * Check if rollback is allowed for adjustment
	 *
	 * @param IndexAdjustment $adjustment Adjustment object
	 * @return bool                       True if rollback is allowed
	 */
	public function canRollback($adjustment)
	{
		global $conf;

		// Must be executed
		if ($adjustment->status != IndexAdjustment::STATUS_EXECUTED) {
			return false;
		}

		// Check time window
		$rollbackDays = !empty($conf->global->INDEXADJUSTMENT_ROLLBACK_DAYS) ? $conf->global->INDEXADJUSTMENT_ROLLBACK_DAYS : 30;
		$maxDate = strtotime("+{$rollbackDays} days", $adjustment->date_executed);

		return dol_now() <= $maxDate;
	}

	/**
	 * Rollback index adjustment
	 *
	 * @param IndexAdjustment $adjustment Adjustment object
	 * @param User            $user       User performing rollback
	 * @return int                        <0 if KO, >0 if OK
	 */
	public function rollback($adjustment, $user)
	{
		global $langs;

		if (!$this->canRollback($adjustment)) {
			$this->error = $langs->trans('RollbackNotAllowed');
			return -1;
		}

		$this->db->begin();

		try {
			$contract = new Contrat($this->db);

			// Process each line
			foreach ($adjustment->lines as $line) {
				// Skip if already rolled back
				if ($line->rollback_executed) {
					continue;
				}

				// Load contract
				$contract->fetch($line->fk_contrat);

				// Restore original price and VAT rate
				// Signature: updateline($rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $tvatx, $localtax1tx, $localtax2tx, $date_start_real, $date_end_real, $price_base_type)
				$resultUpdate = $contract->updateline(
					$line->fk_contratdet,    // rowid
					$line->product_label,    // desc
					$line->subprice_before,  // pu (restore original price)
					$line->qty,              // qty
					0,                       // remise_percent
					'',                      // date_start
					'',                      // date_end
					$line->tva_tx,           // tvatx (restore original VAT rate)
					0,                       // localtax1tx
					0,                       // localtax2tx
					'',                      // date_start_real
					'',                      // date_end_real
					'HT'                     // price_base_type
				);

				if ($resultUpdate < 0) {
					throw new Exception('Failed to restore contract line: ' . $contract->error);
				}

				// Mark line as rolled back
				$line->setRolledBack($user);
			}

			// Cancel adjustment
			$adjustment->cancel($user);

			$this->db->commit();
			return 1;

		} catch (Exception $e) {
			$this->db->rollback();
			$this->error = $e->getMessage();
			return -1;
		}
	}

	/**
	 * Generate event note content
	 *
	 * @param IndexAdjustment $adjustment Adjustment object
	 * @param array           $lines      Lines data
	 * @param User            $user       User
	 * @return string                     Note content
	 */
	public function generateEventNote($adjustment, $lines, $user)
	{
		global $langs, $conf;

		$langs->load("indexadjustment@indexadjustment");

		$percentSign = $adjustment->adjustment_percent >= 0 ? '+' : '';

		$linesText = '';
		foreach ($lines as $line) {
			$linesText .= sprintf(
				"- %s: €%.2f → €%.2f (%+.2f€)\n",
				$line['product_label'],
				$line['subprice_before'],
				$line['subprice_after'],
				$line['price_diff']
			);
		}

		$note = "Indexanpassung " . $adjustment->ref . "\n";
		$note .= "Datum: " . dol_print_date($adjustment->adjustment_date, 'day') . "\n";
		$note .= "Anpassung: " . $percentSign . number_format($adjustment->adjustment_percent, 2) . "%\n\n";
		$note .= $linesText;
		$note .= "\nGesamt vorher: " . price($adjustment->total_ht_before, 0, $langs, 0, -1, 2) . "\n";
		$note .= "Gesamt nachher: " . price($adjustment->total_ht_after, 0, $langs, 0, -1, 2) . "\n";
		$note .= "Differenz: " . price($adjustment->total_ht_after - $adjustment->total_ht_before, 0, $langs, 0, -1, 2) . "\n";
		$note .= "Ausgeführt von: " . $user->getFullName($langs) . "\n";

		return $note;
	}

	/**
	 * Preview adjustments for selected lines (grouped by contract)
	 *
	 * @param array $selectedLines Array of [contractId => [lineId, lineId, ...]]
	 * @param float $percent       Adjustment percentage
	 * @return array               Preview data with contracts and totals
	 */
	public function previewAdjustmentsWithLines($selectedLines, $percent)
	{
		$preview = array(
			'contracts' => array(),
			'totals' => array(
				'total_contracts' => 0,
				'total_lines' => 0,
				'total_ht_before' => 0,
				'total_ht_after' => 0,
				'total_diff' => 0,
			),
		);

		foreach ($selectedLines as $contractId => $lineIds) {
			if (empty($lineIds)) {
				continue;
			}

			// Fetch all active lines for this contract
			$allLines = $this->fetchActiveServiceLines($contractId);

			$contractData = array(
				'id' => $contractId,
				'lines' => array(),
				'totals' => array(
					'total_ht_before' => 0,
					'total_ht_after' => 0,
					'total_diff' => 0,
				),
			);

			foreach ($lineIds as $lineId) {
				// Only process lines that exist and are in our selected list
				if (!isset($allLines[$lineId])) {
					continue;
				}

				$line = $allLines[$lineId];
				$subpriceAfter = $this->calculator->calculateAdjustedPrice($line['subprice'], $percent);
				$totalHtAfter = $this->calculator->calculateTotalHT($subpriceAfter, $line['qty']);
				$priceDiff = $this->calculator->calculatePriceDifference($line['subprice'], $subpriceAfter);

				$lineData = array(
					'id' => $lineId,
					'fk_contrat' => $contractId,
					'fk_product' => $line['fk_product'],
					'product_ref' => $line['product_ref'],
					'product_label' => $line['product_label'],
					'description' => $line['description'],
					'qty' => $line['qty'],
					'tva_tx' => $line['tva_tx'],
					'statut' => $line['statut'],
					'subprice_before' => $line['subprice'],
					'subprice_after' => $subpriceAfter,
					'total_ht_before' => $line['total_ht'],
					'total_ht_after' => $totalHtAfter,
					'price_diff' => $priceDiff,
					'total_diff' => $totalHtAfter - $line['total_ht'],
				);

				$contractData['lines'][$lineId] = $lineData;
				$contractData['totals']['total_ht_before'] += $line['total_ht'];
				$contractData['totals']['total_ht_after'] += $totalHtAfter;
				$contractData['totals']['total_diff'] += ($totalHtAfter - $line['total_ht']);
			}

			if (!empty($contractData['lines'])) {
				$preview['contracts'][$contractId] = $contractData;
				$preview['totals']['total_contracts']++;
				$preview['totals']['total_lines'] += count($contractData['lines']);
				$preview['totals']['total_ht_before'] += $contractData['totals']['total_ht_before'];
				$preview['totals']['total_ht_after'] += $contractData['totals']['total_ht_after'];
				$preview['totals']['total_diff'] += $contractData['totals']['total_diff'];
			}
		}

		return $preview;
	}

	/**
	 * Execute index adjustment with selected lines
	 *
	 * @param IndexAdjustment $adjustment    Adjustment object (must be validated)
	 * @param User            $user          User executing
	 * @param array           $selectedLines Array of [contractId => [lineId, lineId, ...]]
	 * @return int                           <0 if KO, >0 if OK
	 * @throws Exception                     If validation fails
	 */
	public function executeWithLines($adjustment, $user, $selectedLines = array())
	{
		global $langs;

		// Validate adjustment status
		if ($adjustment->status != IndexAdjustment::STATUS_VALIDATED) {
			throw new Exception($langs->trans('AdjustmentMustBeValidatedBeforeExecution') ?: 'Adjustment must be validated before execution');
		}

		// Validate required fields
		if (empty($adjustment->label) || $adjustment->adjustment_percent === null) {
			throw new Exception($langs->trans('RequiredFieldsMissing') ?: 'Required fields missing');
		}

		$this->db->begin();

		try {
			$percent = $adjustment->adjustment_percent;
			$preview = $this->previewAdjustmentsWithLines($selectedLines, $percent);

			if ($preview['totals']['total_lines'] == 0) {
				throw new Exception($langs->trans('NoActiveServicesFound') ?: 'No active services found');
			}

			$contract = new Contrat($this->db);

			// Process each contract
			foreach ($preview['contracts'] as $contractId => $contractData) {
				$contract->fetch($contractId);

				// Process each line
				foreach ($contractData['lines'] as $lineId => $lineData) {
					// Create audit record (including tva_tx for rollback)
					$line = new IndexAdjustmentLine($this->db);
					$line->fk_indexadjustment = $adjustment->id;
					$line->fk_contrat = $contractId;
					$line->fk_contratdet = $lineId;
					$line->product_ref = $lineData['product_ref'];
					$line->product_label = $lineData['product_label'] ?: $lineData['description'];
					$line->subprice_before = $lineData['subprice_before'];
					$line->qty = $lineData['qty'];
					$line->total_ht_before = $lineData['total_ht_before'];
					$line->tva_tx = $lineData['tva_tx'];
					$line->subprice_after = $lineData['subprice_after'];
					$line->total_ht_after = $lineData['total_ht_after'];
					$line->price_diff_ht = $lineData['price_diff'];

					$resultLine = $line->create($user);
					if ($resultLine < 0) {
						throw new Exception('Failed to create adjustment line: ' . $line->error);
					}

					// Update contract line price using Dolibarr API
					// Signature: updateline($rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $tvatx, $localtax1tx, $localtax2tx, $date_start_real, $date_end_real, $price_base_type)
					$resultUpdate = $contract->updateline(
						$lineId,                                                 // rowid
						$lineData['product_label'] ?: $lineData['description'],  // desc
						$lineData['subprice_after'],                             // pu (new subprice)
						$lineData['qty'],                                        // qty
						0,                                                       // remise_percent
						'',                                                      // date_start
						'',                                                      // date_end
						$lineData['tva_tx'],                                     // tvatx
						0,                                                       // localtax1tx
						0,                                                       // localtax2tx
						'',                                                      // date_start_real
						'',                                                      // date_end_real
						'HT'                                                     // price_base_type
					);

					if ($resultUpdate < 0) {
						throw new Exception('Failed to update contract line: ' . $contract->error);
					}
				}

				// Create ActionComm event for this contract
				$this->createContractEvent($adjustment, $contract, $contractData, $user);
			}

			// Update adjustment statistics
			$adjustment->total_contracts = $preview['totals']['total_contracts'];
			$adjustment->total_lines = $preview['totals']['total_lines'];
			$adjustment->total_ht_before = $preview['totals']['total_ht_before'];
			$adjustment->total_ht_after = $preview['totals']['total_ht_after'];
			$adjustment->setExecuted($user);

			$this->db->commit();
			return 1;

		} catch (Exception $e) {
			$this->db->rollback();
			$this->error = $e->getMessage();
			$this->errors[] = $e->getMessage();
			return -1;
		}
	}
}
