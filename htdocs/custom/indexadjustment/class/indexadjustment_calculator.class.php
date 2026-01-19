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
 * \file       class/indexadjustment_calculator.class.php
 * \ingroup    indexadjustment
 * \brief      Calculator for index adjustments (VPI-based price calculations)
 */

/**
 * Class IndexAdjustmentCalculator
 *
 * Handles all price calculations for index adjustments.
 * Stateless utility class for percentage calculations, VPI adjustments,
 * threshold checks, and batch processing.
 */
class IndexAdjustmentCalculator
{
	/**
	 * @var int Number of decimal places for price rounding
	 */
	public $decimals = 2;

	/**
	 * @var string Default rounding mode (standard, up, down)
	 */
	public $roundingMode = 'standard';

	/**
	 * Calculate adjusted price after applying percentage
	 *
	 * @param float $price      Original price
	 * @param float $percent    Percentage adjustment (positive or negative)
	 * @return float            Adjusted price rounded to decimals
	 */
	public function calculateAdjustedPrice($price, $percent)
	{
		$multiplier = 1 + ($percent / 100);
		$newPrice = $price * $multiplier;

		return $this->roundPrice($newPrice, $this->roundingMode);
	}

	/**
	 * Calculate VPI adjustment percentage
	 *
	 * Formula: ((current - base) / base) * 100
	 *
	 * @param float $baseValue      VPI base value (e.g., from 2020)
	 * @param float $currentValue   VPI current value
	 * @return float                Percentage change
	 * @throws InvalidArgumentException If base value is zero or negative
	 */
	public function calculateVpiAdjustment($baseValue, $currentValue)
	{
		if ($baseValue <= 0) {
			throw new InvalidArgumentException('Base VPI value cannot be zero or negative');
		}

		return (($currentValue - $baseValue) / $baseValue) * 100;
	}

	/**
	 * Check if adjustment meets threshold
	 *
	 * Uses absolute value to handle both increases and decreases.
	 *
	 * @param float $percent    Adjustment percentage
	 * @param float $threshold  Minimum threshold (e.g., 3.0 for 3%)
	 * @return bool             True if |percent| >= threshold
	 */
	public function meetsThreshold($percent, $threshold)
	{
		return abs($percent) >= $threshold;
	}

	/**
	 * Calculate price difference
	 *
	 * @param float $priceBefore    Original price
	 * @param float $priceAfter     Adjusted price
	 * @return float                Difference (positive = increase)
	 */
	public function calculatePriceDifference($priceBefore, $priceAfter)
	{
		return $this->roundPrice($priceAfter - $priceBefore, $this->roundingMode);
	}

	/**
	 * Calculate total HT (excluding tax) for a line
	 *
	 * @param float $subprice   Unit price
	 * @param float $qty        Quantity
	 * @return float            Total HT
	 */
	public function calculateTotalHT($subprice, $qty)
	{
		return $this->roundPrice($subprice * $qty, $this->roundingMode);
	}

	/**
	 * Round price according to rounding mode
	 *
	 * @param float  $price         Price to round
	 * @param string $mode          Rounding mode: standard, up, down
	 * @return float                Rounded price
	 */
	public function roundPrice($price, $mode = 'standard')
	{
		switch ($mode) {
			case 'up':
				$factor = pow(10, $this->decimals);
				return ceil($price * $factor) / $factor;

			case 'down':
				$factor = pow(10, $this->decimals);
				return floor($price * $factor) / $factor;

			case 'standard':
			default:
				return round($price, $this->decimals);
		}
	}

	/**
	 * Calculate batch of line adjustments
	 *
	 * @param array $lines      Array of lines with ['subprice' => float, 'qty' => int]
	 * @param float $percent    Adjustment percentage
	 * @return array            Array of results with before/after prices
	 */
	public function calculateBatch($lines, $percent)
	{
		$results = [];

		foreach ($lines as $line) {
			$subpriceBefore = (float)$line['subprice'];
			$qty = isset($line['qty']) ? (float)$line['qty'] : 1;

			$subpriceAfter = $this->calculateAdjustedPrice($subpriceBefore, $percent);
			$totalHtBefore = $this->calculateTotalHT($subpriceBefore, $qty);
			$totalHtAfter = $this->calculateTotalHT($subpriceAfter, $qty);

			$results[] = [
				'subprice_before' => $subpriceBefore,
				'subprice_after' => $subpriceAfter,
				'qty' => $qty,
				'total_ht_before' => $totalHtBefore,
				'total_ht_after' => $totalHtAfter,
				'price_diff' => $this->calculatePriceDifference($subpriceBefore, $subpriceAfter),
				'total_diff' => $this->calculatePriceDifference($totalHtBefore, $totalHtAfter),
			];
		}

		return $results;
	}

	/**
	 * Calculate batch totals
	 *
	 * @param array $lines      Array of lines with ['subprice' => float, 'qty' => int]
	 * @param float $percent    Adjustment percentage
	 * @return array            Totals array with total_ht_before, total_ht_after, total_diff
	 */
	public function calculateBatchTotals($lines, $percent)
	{
		$results = $this->calculateBatch($lines, $percent);

		$totalHtBefore = 0;
		$totalHtAfter = 0;

		foreach ($results as $result) {
			$totalHtBefore += $result['total_ht_before'];
			$totalHtAfter += $result['total_ht_after'];
		}

		return [
			'total_ht_before' => $this->roundPrice($totalHtBefore, $this->roundingMode),
			'total_ht_after' => $this->roundPrice($totalHtAfter, $this->roundingMode),
			'total_diff' => $this->roundPrice($totalHtAfter - $totalHtBefore, $this->roundingMode),
			'line_count' => count($results),
		];
	}

	/**
	 * Set number of decimal places
	 *
	 * @param int $decimals Number of decimal places (0-8)
	 * @return self
	 */
	public function setDecimals($decimals)
	{
		$this->decimals = max(0, min(8, (int)$decimals));
		return $this;
	}

	/**
	 * Set rounding mode
	 *
	 * @param string $mode Rounding mode: standard, up, down
	 * @return self
	 */
	public function setRoundingMode($mode)
	{
		if (in_array($mode, ['standard', 'up', 'down'])) {
			$this->roundingMode = $mode;
		}
		return $this;
	}
}
