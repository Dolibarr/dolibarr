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

// Pure unit test - no Dolibarr environment needed
require_once __DIR__ . '/../../class/indexadjustment_calculator.class.php';

/**
 * Unit tests for IndexAdjustmentCalculator class
 *
 * TDD: These tests are written BEFORE the implementation
 */
class IndexAdjustmentCalculatorTest extends TestCase
{
	private $calculator;

	/**
	 * Set up test environment
	 */
	protected function setUp(): void
	{
		$this->calculator = new IndexAdjustmentCalculator();
	}

	// =========================================================================
	// PERCENTAGE CALCULATION TESTS
	// =========================================================================

	/**
	 * Test basic price increase calculation
	 * 100 + 4.5% = 104.50
	 */
	public function testCalculateAdjustedPrice_BasicIncrease()
	{
		$result = $this->calculator->calculateAdjustedPrice(100.00, 4.5);

		$this->assertEquals(104.50, $result);
	}

	/**
	 * Test price decrease calculation
	 * 100 - 3% = 97.00
	 */
	public function testCalculateAdjustedPrice_Decrease()
	{
		$result = $this->calculator->calculateAdjustedPrice(100.00, -3.0);

		$this->assertEquals(97.00, $result);
	}

	/**
	 * Test rounding behavior
	 * 33.33 + 4.5% = 34.83 (rounded to 2 decimals)
	 */
	public function testCalculateAdjustedPrice_Rounding()
	{
		$result = $this->calculator->calculateAdjustedPrice(33.33, 4.5);

		// 33.33 * 1.045 = 34.82985, rounded to 34.83
		$this->assertEquals(34.83, $result);
	}

	/**
	 * Test zero percent adjustment returns same price
	 * 100 + 0% = 100.00
	 */
	public function testCalculateAdjustedPrice_ZeroPercent()
	{
		$result = $this->calculator->calculateAdjustedPrice(100.00, 0);

		$this->assertEquals(100.00, $result);
	}

	/**
	 * Test small percentage adjustment
	 * 100 + 0.1% = 100.10
	 */
	public function testCalculateAdjustedPrice_SmallPercent()
	{
		$result = $this->calculator->calculateAdjustedPrice(100.00, 0.1);

		$this->assertEquals(100.10, $result);
	}

	/**
	 * Test large percentage adjustment
	 * 100 + 50% = 150.00
	 */
	public function testCalculateAdjustedPrice_LargePercent()
	{
		$result = $this->calculator->calculateAdjustedPrice(100.00, 50.0);

		$this->assertEquals(150.00, $result);
	}

	/**
	 * Test with realistic contract price
	 * 249.99 + 4.2% = 260.49 (rounded)
	 */
	public function testCalculateAdjustedPrice_RealisticPrice()
	{
		$result = $this->calculator->calculateAdjustedPrice(249.99, 4.2);

		// 249.99 * 1.042 = 260.48958, rounded to 260.49
		$this->assertEquals(260.49, $result);
	}

	/**
	 * Test zero price returns zero
	 */
	public function testCalculateAdjustedPrice_ZeroPrice()
	{
		$result = $this->calculator->calculateAdjustedPrice(0, 4.5);

		$this->assertEquals(0, $result);
	}

	// =========================================================================
	// VPI CALCULATION TESTS
	// =========================================================================

	/**
	 * Test standard VPI adjustment calculation
	 * Base: 100, Current: 104.5 = 4.5%
	 */
	public function testCalculateVpiAdjustment_Standard()
	{
		$result = $this->calculator->calculateVpiAdjustment(100.0, 104.5);

		$this->assertEquals(4.5, $result);
	}

	/**
	 * Test VPI with decline (deflation)
	 * Base: 100, Current: 98 = -2%
	 */
	public function testCalculateVpiAdjustment_Decline()
	{
		$result = $this->calculator->calculateVpiAdjustment(100.0, 98.0);

		$this->assertEquals(-2.0, $result);
	}

	/**
	 * Test VPI calculation with non-100 base
	 * Base: 115.2, Current: 120.4 = 4.51%
	 */
	public function testCalculateVpiAdjustment_NonStandardBase()
	{
		$result = $this->calculator->calculateVpiAdjustment(115.2, 120.4);

		// (120.4 - 115.2) / 115.2 * 100 = 4.5138...
		$this->assertEquals(4.51, round($result, 2));
	}

	/**
	 * Test VPI with same values returns 0%
	 */
	public function testCalculateVpiAdjustment_NoChange()
	{
		$result = $this->calculator->calculateVpiAdjustment(100.0, 100.0);

		$this->assertEquals(0.0, $result);
	}

	/**
	 * Test VPI throws exception on zero base value
	 */
	public function testCalculateVpiAdjustment_InvalidBase()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Base VPI value cannot be zero');

		$this->calculator->calculateVpiAdjustment(0, 104.5);
	}

	/**
	 * Test VPI throws exception on negative base value
	 */
	public function testCalculateVpiAdjustment_NegativeBase()
	{
		$this->expectException(InvalidArgumentException::class);

		$this->calculator->calculateVpiAdjustment(-100, 104.5);
	}

	// =========================================================================
	// THRESHOLD TESTS
	// =========================================================================

	/**
	 * Test that adjustment above threshold passes
	 * 4.5% >= 3% = true
	 */
	public function testMeetsThreshold_Above()
	{
		$result = $this->calculator->meetsThreshold(4.5, 3.0);

		$this->assertTrue($result);
	}

	/**
	 * Test that adjustment below threshold fails
	 * 2.5% >= 3% = false
	 */
	public function testMeetsThreshold_Below()
	{
		$result = $this->calculator->meetsThreshold(2.5, 3.0);

		$this->assertFalse($result);
	}

	/**
	 * Test that adjustment exactly at threshold passes
	 * 3.0% >= 3% = true
	 */
	public function testMeetsThreshold_Exact()
	{
		$result = $this->calculator->meetsThreshold(3.0, 3.0);

		$this->assertTrue($result);
	}

	/**
	 * Test zero threshold always passes
	 */
	public function testMeetsThreshold_ZeroThreshold()
	{
		$result = $this->calculator->meetsThreshold(0.1, 0);

		$this->assertTrue($result);
	}

	/**
	 * Test negative adjustment with threshold
	 * Uses absolute value: |-4.5%| >= 3% = true
	 */
	public function testMeetsThreshold_NegativeAdjustment()
	{
		$result = $this->calculator->meetsThreshold(-4.5, 3.0);

		$this->assertTrue($result);
	}

	// =========================================================================
	// PRICE DIFFERENCE TESTS
	// =========================================================================

	/**
	 * Test price difference calculation
	 */
	public function testCalculatePriceDifference_Increase()
	{
		$result = $this->calculator->calculatePriceDifference(100.00, 104.50);

		$this->assertEquals(4.50, $result);
	}

	/**
	 * Test price difference for decrease
	 */
	public function testCalculatePriceDifference_Decrease()
	{
		$result = $this->calculator->calculatePriceDifference(100.00, 97.00);

		$this->assertEquals(-3.00, $result);
	}

	// =========================================================================
	// TOTAL CALCULATION TESTS
	// =========================================================================

	/**
	 * Test calculating total HT for a contract line
	 * subprice * qty = total_ht
	 */
	public function testCalculateTotalHT()
	{
		$result = $this->calculator->calculateTotalHT(104.50, 1);

		$this->assertEquals(104.50, $result);
	}

	/**
	 * Test calculating total HT with quantity
	 * 104.50 * 3 = 313.50
	 */
	public function testCalculateTotalHT_WithQuantity()
	{
		$result = $this->calculator->calculateTotalHT(104.50, 3);

		$this->assertEquals(313.50, $result);
	}

	// =========================================================================
	// ROUNDING MODE TESTS
	// =========================================================================

	/**
	 * Test standard rounding mode (half-up)
	 * 34.825 -> 34.83
	 */
	public function testRounding_Standard()
	{
		$result = $this->calculator->roundPrice(34.825, 'standard');

		$this->assertEquals(34.83, $result);
	}

	/**
	 * Test rounding up mode
	 * 34.821 -> 34.83
	 */
	public function testRounding_Up()
	{
		$result = $this->calculator->roundPrice(34.821, 'up');

		$this->assertEquals(34.83, $result);
	}

	/**
	 * Test rounding down mode
	 * 34.829 -> 34.82
	 */
	public function testRounding_Down()
	{
		$result = $this->calculator->roundPrice(34.829, 'down');

		$this->assertEquals(34.82, $result);
	}

	// =========================================================================
	// BATCH CALCULATION TESTS
	// =========================================================================

	/**
	 * Test batch calculation for multiple lines
	 */
	public function testCalculateBatch()
	{
		$lines = [
			['subprice' => 100.00, 'qty' => 1],
			['subprice' => 250.00, 'qty' => 2],
			['subprice' => 75.50, 'qty' => 1],
		];

		$results = $this->calculator->calculateBatch($lines, 4.5);

		$this->assertCount(3, $results);

		// Line 1: 100 + 4.5% = 104.50
		$this->assertEquals(104.50, $results[0]['subprice_after']);
		$this->assertEquals(104.50, $results[0]['total_ht_after']);

		// Line 2: 250 + 4.5% = 261.25, * 2 = 522.50
		$this->assertEquals(261.25, $results[1]['subprice_after']);
		$this->assertEquals(522.50, $results[1]['total_ht_after']);

		// Line 3: 75.50 + 4.5% = 78.90 (rounded)
		$this->assertEquals(78.90, $results[2]['subprice_after']);
		$this->assertEquals(78.90, $results[2]['total_ht_after']);
	}

	/**
	 * Test batch calculation totals
	 */
	public function testCalculateBatchTotals()
	{
		$lines = [
			['subprice' => 100.00, 'qty' => 1],
			['subprice' => 250.00, 'qty' => 1],
		];

		$totals = $this->calculator->calculateBatchTotals($lines, 4.5);

		// Before: 100 + 250 = 350
		$this->assertEquals(350.00, $totals['total_ht_before']);

		// After: 104.50 + 261.25 = 365.75
		$this->assertEquals(365.75, $totals['total_ht_after']);

		// Difference: +15.75
		$this->assertEquals(15.75, $totals['total_diff']);
	}

	// =========================================================================
	// EDGE CASES
	// =========================================================================

	/**
	 * Test very small prices
	 */
	public function testCalculateAdjustedPrice_VerySmallPrice()
	{
		$result = $this->calculator->calculateAdjustedPrice(0.01, 4.5);

		// 0.01 * 1.045 = 0.01045, rounded to 0.01
		$this->assertEquals(0.01, $result);
	}

	/**
	 * Test very large prices
	 */
	public function testCalculateAdjustedPrice_VeryLargePrice()
	{
		$result = $this->calculator->calculateAdjustedPrice(999999.99, 4.5);

		// 999999.99 * 1.045 = 1044999.989...
		$this->assertEquals(1044999.99, $result);
	}

	/**
	 * Test empty batch returns empty results
	 */
	public function testCalculateBatch_Empty()
	{
		$results = $this->calculator->calculateBatch([], 4.5);

		$this->assertCount(0, $results);
	}
}
