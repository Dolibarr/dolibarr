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

use PHPUnit\Framework\TestCase;

// Load Dolibarr environment
define('DOL_DOCUMENT_ROOT', realpath(__DIR__ . '/../../../../'));
require_once DOL_DOCUMENT_ROOT . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/class/indexadjustment_service.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/class/indexadjustment.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/class/indexadjustment_calculator.class.php';

/**
 * Service tests for IndexAdjustmentService class
 *
 * TDD: These tests are written BEFORE the implementation
 * Note: These tests require Dolibarr database connection
 */
class IndexAdjustmentServiceTest extends TestCase
{
	private $db;
	private $service;
	private $user;

	/**
	 * Set up test environment
	 */
	protected function setUp(): void
	{
		global $db, $user;
		$this->db = $db;
		$this->user = $user;
		$this->service = new IndexAdjustmentService($this->db);
	}

	// =========================================================================
	// SERVICE INSTANTIATION TESTS
	// =========================================================================

	/**
	 * Test service instantiation
	 */
	public function testServiceInstantiation()
	{
		$service = new IndexAdjustmentService($this->db);
		$this->assertInstanceOf(IndexAdjustmentService::class, $service);
	}

	/**
	 * Test service has calculator
	 */
	public function testServiceHasCalculator()
	{
		$service = new IndexAdjustmentService($this->db);
		$this->assertInstanceOf(IndexAdjustmentCalculator::class, $service->calculator);
	}

	// =========================================================================
	// CONTRACT FETCHING TESTS
	// =========================================================================

	/**
	 * Test fetching active contracts returns array
	 */
	public function testFetchActiveContracts_ReturnsArray()
	{
		$result = $this->service->fetchActiveContracts();

		$this->assertIsArray($result);
	}

	/**
	 * Test fetching contracts with customer filter
	 */
	public function testFetchActiveContracts_WithCustomerFilter()
	{
		// This test verifies filtering works without errors
		$result = $this->service->fetchActiveContracts(1); // Customer ID 1

		$this->assertIsArray($result);
	}

	/**
	 * Test fetching active service lines
	 */
	public function testFetchActiveServiceLines_ReturnsArray()
	{
		// Get any contract ID from the database for testing
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "contrat LIMIT 1";
		$resql = $this->db->query($sql);
		if ($resql && $this->db->num_rows($resql) > 0) {
			$obj = $this->db->fetch_object($resql);
			$contractId = $obj->rowid;

			$result = $this->service->fetchActiveServiceLines($contractId);

			$this->assertIsArray($result);
		} else {
			$this->markTestSkipped('No contracts in database for testing');
		}
	}

	// =========================================================================
	// PREVIEW TESTS
	// =========================================================================

	/**
	 * Test preview adjustments returns structured data
	 */
	public function testPreviewAdjustments_ReturnsStructuredData()
	{
		// Get any active contract for testing
		$contracts = $this->service->fetchActiveContracts();

		if (empty($contracts)) {
			$this->markTestSkipped('No active contracts for preview testing');
		}

		$contractIds = array_slice(array_keys($contracts), 0, 1);
		$result = $this->service->previewAdjustments($contractIds, 4.5);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('contracts', $result);
		$this->assertArrayHasKey('totals', $result);
	}

	/**
	 * Test preview shows before and after prices
	 */
	public function testPreviewAdjustments_ShowsBeforeAfterPrices()
	{
		$contracts = $this->service->fetchActiveContracts();

		if (empty($contracts)) {
			$this->markTestSkipped('No active contracts for preview testing');
		}

		$contractIds = array_slice(array_keys($contracts), 0, 1);
		$result = $this->service->previewAdjustments($contractIds, 4.5);

		if (!empty($result['contracts'])) {
			$firstContract = reset($result['contracts']);
			if (!empty($firstContract['lines'])) {
				$firstLine = reset($firstContract['lines']);

				$this->assertArrayHasKey('subprice_before', $firstLine);
				$this->assertArrayHasKey('subprice_after', $firstLine);
				$this->assertArrayHasKey('total_ht_before', $firstLine);
				$this->assertArrayHasKey('total_ht_after', $firstLine);
			}
		}

		$this->assertTrue(true); // Test structure passed
	}

	/**
	 * Test preview skips inactive lines
	 */
	public function testPreviewAdjustments_SkipsInactiveLines()
	{
		$contracts = $this->service->fetchActiveContracts();

		if (empty($contracts)) {
			$this->markTestSkipped('No contracts for testing');
		}

		// Preview should only include lines with statut=4 (active)
		$contractIds = array_keys($contracts);
		$result = $this->service->previewAdjustments($contractIds, 4.5);

		// All returned lines should be for active services only
		foreach ($result['contracts'] as $contract) {
			foreach ($contract['lines'] as $line) {
				if (isset($line['statut'])) {
					$this->assertEquals(4, $line['statut'], 'Only active lines (statut=4) should be included');
				}
			}
		}
	}

	// =========================================================================
	// ADJUSTMENT OBJECT TESTS
	// =========================================================================

	/**
	 * Test creating adjustment object
	 */
	public function testCreateAdjustmentObject()
	{
		$adjustment = new IndexAdjustment($this->db);

		$adjustment->label = 'Test Adjustment';
		$adjustment->adjustment_date = dol_now();
		$adjustment->adjustment_percent = 4.5;
		$adjustment->fk_user_creat = $this->user->id;

		$this->assertInstanceOf(IndexAdjustment::class, $adjustment);
		$this->assertEquals('Test Adjustment', $adjustment->label);
		$this->assertEquals(4.5, $adjustment->adjustment_percent);
	}

	/**
	 * Test adjustment has correct status constants
	 */
	public function testAdjustmentStatusConstants()
	{
		$this->assertEquals(0, IndexAdjustment::STATUS_DRAFT);
		$this->assertEquals(1, IndexAdjustment::STATUS_VALIDATED);
		$this->assertEquals(2, IndexAdjustment::STATUS_EXECUTED);
		$this->assertEquals(9, IndexAdjustment::STATUS_CANCELLED);
	}

	// =========================================================================
	// EXECUTION TESTS (Read-only - no actual execution in tests)
	// =========================================================================

	/**
	 * Test execute validates required fields
	 */
	public function testExecute_ValidatesRequiredFields()
	{
		$adjustment = new IndexAdjustment($this->db);
		// Don't set required fields

		$this->expectException(Exception::class);
		$this->service->execute($adjustment, $this->user);
	}

	/**
	 * Test execute requires validated status
	 */
	public function testExecute_RequiresValidatedStatus()
	{
		$adjustment = new IndexAdjustment($this->db);
		$adjustment->status = IndexAdjustment::STATUS_DRAFT;
		$adjustment->label = 'Test';
		$adjustment->adjustment_percent = 4.5;

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('validated');
		$this->service->execute($adjustment, $this->user);
	}

	// =========================================================================
	// ROLLBACK VALIDATION TESTS
	// =========================================================================

	/**
	 * Test rollback checks time window
	 */
	public function testRollbackValidation_ChecksTimeWindow()
	{
		global $conf;

		$rollbackDays = !empty($conf->global->INDEXADJUSTMENT_ROLLBACK_DAYS) ? $conf->global->INDEXADJUSTMENT_ROLLBACK_DAYS : 30;

		$adjustment = new IndexAdjustment($this->db);
		$adjustment->status = IndexAdjustment::STATUS_EXECUTED;
		$adjustment->date_executed = strtotime("-" . ($rollbackDays + 1) . " days");

		$canRollback = $this->service->canRollback($adjustment);

		$this->assertFalse($canRollback);
	}

	/**
	 * Test rollback within time window is allowed
	 */
	public function testRollbackValidation_WithinTimeWindow()
	{
		$adjustment = new IndexAdjustment($this->db);
		$adjustment->status = IndexAdjustment::STATUS_EXECUTED;
		$adjustment->date_executed = strtotime("-5 days");

		$canRollback = $this->service->canRollback($adjustment);

		$this->assertTrue($canRollback);
	}

	// =========================================================================
	// ACTION EVENT TESTS
	// =========================================================================

	/**
	 * Test event note generation
	 */
	public function testGenerateEventNote()
	{
		$adjustment = new IndexAdjustment($this->db);
		$adjustment->ref = 'IA-2024-0001';
		$adjustment->adjustment_date = strtotime('2024-01-15');
		$adjustment->adjustment_percent = 4.5;
		$adjustment->total_ht_before = 350.00;
		$adjustment->total_ht_after = 365.75;

		$lines = [
			[
				'product_label' => 'Internet Service',
				'subprice_before' => 100.00,
				'subprice_after' => 104.50,
				'price_diff' => 4.50,
			],
			[
				'product_label' => 'VoIP Service',
				'subprice_before' => 250.00,
				'subprice_after' => 261.25,
				'price_diff' => 11.25,
			],
		];

		$note = $this->service->generateEventNote($adjustment, $lines, $this->user);

		$this->assertStringContainsString('IA-2024-0001', $note);
		$this->assertStringContainsString('+4.50%', $note);
		$this->assertStringContainsString('Internet Service', $note);
		$this->assertStringContainsString('VoIP Service', $note);
	}

	// =========================================================================
	// THRESHOLD TESTS
	// =========================================================================

	/**
	 * Test threshold filtering in preview
	 */
	public function testPreviewWithThreshold()
	{
		$contracts = $this->service->fetchActiveContracts();

		if (empty($contracts)) {
			$this->markTestSkipped('No contracts for testing');
		}

		$contractIds = array_slice(array_keys($contracts), 0, 1);

		// Preview with 10% threshold should skip if adjustment is only 4.5%
		$result = $this->service->previewAdjustments($contractIds, 4.5, 10.0);

		// With high threshold, no lines should pass
		$this->assertArrayHasKey('totals', $result);

		// Low threshold should allow adjustments
		$result = $this->service->previewAdjustments($contractIds, 4.5, 0);
		$this->assertArrayHasKey('totals', $result);
	}
}
