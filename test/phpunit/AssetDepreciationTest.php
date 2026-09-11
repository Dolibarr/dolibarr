<?php
/* Copyright (C) 2026 Association P'tite Tete <asso@ptitetete.org>
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
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/AssetDepreciationTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test for the day count conventions of the depreciations
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/asset.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class AssetDepreciationTest extends CommonClassTest
{
	/**
	 * Reference asset used by every prorata temporis test below.
	 * Van of 13000 EUR excl. tax, straight line depreciation over 5 years (annuity of 2600 EUR),
	 * put in service on 2022-03-10, sold on 2025-02-23, fiscal year from August to July.
	 */
	const PERIOD_AMOUNT = 2600;

	/**
	 * testNumDaysInYear
	 *
	 * @return	void
	 */
	public function testNumDaysInYear()
	{
		$this->assertEquals(365, num_days_in_year(2023));
		$this->assertEquals(366, num_days_in_year(2024));
		$this->assertEquals(365, num_days_in_year(1900));		// Divisible by 100 but not by 400
		$this->assertEquals(366, num_days_in_year(2000));		// Divisible by 400
	}

	/**
	 * testNumBetweenDay30360
	 *
	 * @return	void
	 */
	public function testNumBetweenDay30360()
	{
		// A month of 30 or 31 days always counts 30 days: the 31st is brought back to the 30th
		$this->assertEquals(30, num_between_day_30_360(dol_mktime(0, 0, 0, 1, 1, 2023, 'gmt'), dol_mktime(0, 0, 0, 1, 31, 2023, 'gmt'), 1, 'gmt'));
		$this->assertEquals(30, num_between_day_30_360(dol_mktime(0, 0, 0, 4, 1, 2023, 'gmt'), dol_mktime(0, 0, 0, 4, 30, 2023, 'gmt'), 1, 'gmt'));

		// February is the exception: this is the plain 30/360 convention, which does NOT bring the end
		// of February up to the 30th (that is the 30E/360 ISDA variant). A period ending on Feb 28th or
		// 29th is therefore counted 28 or 29 days, which is why Asset::calculationDepreciation() does
		// not compute any prorata at all when the asset is depreciated over the whole fiscal year.
		$this->assertEquals(28, num_between_day_30_360(dol_mktime(0, 0, 0, 2, 1, 2023, 'gmt'), dol_mktime(0, 0, 0, 2, 28, 2023, 'gmt'), 1, 'gmt'));
		$this->assertEquals(29, num_between_day_30_360(dol_mktime(0, 0, 0, 2, 1, 2024, 'gmt'), dol_mktime(0, 0, 0, 2, 29, 2024, 'gmt'), 1, 'gmt'));

		// A whole year always counts 360 days, leap year included
		$this->assertEquals(360, num_between_day_30_360(dol_mktime(0, 0, 0, 1, 1, 2023, 'gmt'), dol_mktime(0, 0, 0, 12, 31, 2023, 'gmt'), 1, 'gmt'));
		$this->assertEquals(360, num_between_day_30_360(dol_mktime(0, 0, 0, 1, 1, 2024, 'gmt'), dol_mktime(0, 0, 0, 12, 31, 2024, 'gmt'), 1, 'gmt'));

		// Periods of the reference asset
		$this->assertEquals(501, num_between_day_30_360(dol_mktime(0, 0, 0, 3, 10, 2022, 'gmt'), dol_mktime(0, 0, 0, 7, 31, 2023, 'gmt'), 1, 'gmt'));
		$this->assertEquals(360, num_between_day_30_360(dol_mktime(0, 0, 0, 8, 1, 2023, 'gmt'), dol_mktime(0, 0, 0, 7, 31, 2024, 'gmt'), 1, 'gmt'));
		$this->assertEquals(203, num_between_day_30_360(dol_mktime(0, 0, 0, 8, 1, 2024, 'gmt'), dol_mktime(0, 0, 0, 2, 23, 2025, 'gmt'), 1, 'gmt'));

		// Without the last day, and with an end date before the start date
		$this->assertEquals(500, num_between_day_30_360(dol_mktime(0, 0, 0, 3, 10, 2022, 'gmt'), dol_mktime(0, 0, 0, 7, 31, 2023, 'gmt'), 0, 'gmt'));
		$this->assertEquals(0, num_between_day_30_360(dol_mktime(0, 0, 0, 7, 31, 2023, 'gmt'), dol_mktime(0, 0, 0, 3, 10, 2022, 'gmt'), 1, 'gmt'));
	}

	/**
	 * A full fiscal year must always give a full annuity, whatever the convention.
	 *
	 * @return	void
	 */
	public function testFullYearIsNotImpactedByTheConvention()
	{
		$fyStart = dol_mktime(0, 0, 0, 8, 1, 2023, 'gmt');
		$fyEnd = dol_mktime(0, 0, 0, 7, 31, 2024, 'gmt');

		// 30/360 gives exactly 1
		$this->assertEquals(1.0, getAssetDepreciationPeriodFraction($fyStart, $fyEnd, 'THIRTY_360', 'gmt'));

		// Counting real days gives a value slightly above 1 (the fiscal year covers the leap day of
		// 2024), that the depreciation plan caps to a full annuity.
		$this->assertEquals(366 / 365, getAssetDepreciationPeriodFraction($fyStart, $fyEnd, 'ACT_365', 'gmt'));
		$this->assertGreaterThan(1, getAssetDepreciationPeriodFraction($fyStart, $fyEnd, 'ACT_ACT', 'gmt'));

		// A standard non leap year gives exactly 1 when each year is divided by its own length
		$this->assertEquals(1.0, getAssetDepreciationPeriodFraction(dol_mktime(0, 0, 0, 1, 1, 2023, 'gmt'), dol_mktime(0, 0, 0, 12, 31, 2023, 'gmt'), 'ACT_ACT', 'gmt'));
		$this->assertEquals(1.0, getAssetDepreciationPeriodFraction(dol_mktime(0, 0, 0, 1, 1, 2024, 'gmt'), dol_mktime(0, 0, 0, 12, 31, 2024, 'gmt'), 'ACT_ACT', 'gmt'));

		// A fiscal year ending in February is the reason why Asset::calculationDepreciation() does not
		// compute any prorata when the asset is depreciated over the whole fiscal year: a 30/360 count
		// ending on Feb 28th or 29th gives 358 or 359 days instead of 360, so slightly less than a full
		// annuity.
		$this->assertEquals(359 / 360, getAssetDepreciationPeriodFraction(dol_mktime(0, 0, 0, 3, 1, 2023, 'gmt'), dol_mktime(0, 0, 0, 2, 29, 2024, 'gmt'), 'THIRTY_360', 'gmt'));
		$this->assertEquals(358 / 360, getAssetDepreciationPeriodFraction(dol_mktime(0, 0, 0, 3, 1, 2022, 'gmt'), dol_mktime(0, 0, 0, 2, 28, 2023, 'gmt'), 'THIRTY_360', 'gmt'));
	}

	/**
	 * Acceptance criteria of the reference asset: the first period and the period of the disposal are
	 * the only ones computed prorata temporis, and each convention must give its own coherent result.
	 *
	 * @return	void
	 */
	public function testProrataTemporisOfTheReferenceAsset()
	{
		$firstPeriodStart = dol_mktime(0, 0, 0, 3, 10, 2022, 'gmt');	// Put in service
		$firstPeriodEnd = dol_mktime(0, 0, 0, 7, 31, 2023, 'gmt');
		$lastPeriodStart = dol_mktime(0, 0, 0, 8, 1, 2024, 'gmt');
		$lastPeriodEnd = dol_mktime(0, 0, 0, 2, 23, 2025, 'gmt');		// Disposal

		// 30/360, the convention applied by the accounting firm of the reference file
		$first = round(self::PERIOD_AMOUNT * getAssetDepreciationPeriodFraction($firstPeriodStart, $firstPeriodEnd, 'THIRTY_360', 'gmt'), 2);
		$last = round(self::PERIOD_AMOUNT * getAssetDepreciationPeriodFraction($lastPeriodStart, $lastPeriodEnd, 'THIRTY_360', 'gmt'), 2);
		$this->assertEquals(3618.33, $first);
		$this->assertEquals(1466.11, $last);
		$this->assertEquals(7684.44, round($first + self::PERIOD_AMOUNT + $last, 2));

		// Real days / 365
		$first = round(self::PERIOD_AMOUNT * getAssetDepreciationPeriodFraction($firstPeriodStart, $firstPeriodEnd, 'ACT_365', 'gmt'), 2);
		$last = round(self::PERIOD_AMOUNT * getAssetDepreciationPeriodFraction($lastPeriodStart, $lastPeriodEnd, 'ACT_365', 'gmt'), 2);
		$this->assertEquals(3625.75, $first);
		$this->assertEquals(1474.52, $last);
		$this->assertEquals(7700.27, round($first + self::PERIOD_AMOUNT + $last, 2));

		// Real days / real year: the disposal period covers the leap day of 2024
		$first = round(self::PERIOD_AMOUNT * getAssetDepreciationPeriodFraction($firstPeriodStart, $firstPeriodEnd, 'ACT_ACT', 'gmt'), 2);
		$last = round(self::PERIOD_AMOUNT * getAssetDepreciationPeriodFraction($lastPeriodStart, $lastPeriodEnd, 'ACT_ACT', 'gmt'), 2);
		$this->assertEquals(3625.75, $first);
		$this->assertEquals(1471.54, $last);

		// The incoherent real days / 360 calculation, that is not a convention anymore, would have
		// given 3676.11 and 1495.00, so 86.67 EUR too much on the cumulated depreciation.
		$this->assertNotEquals(3676.11, $first);
		$this->assertNotEquals(1495.00, $last);
	}

	/**
	 * The whole depreciation plan of the reference asset, computed by the production code path
	 * Asset::calculationDepreciation() and read back from llx_asset_depreciation.
	 *
	 * Reference asset: van of 13000 EUR excl. tax, straight line over 5 years, put in service on
	 * 2022-03-10, sold on 2025-02-23, fiscal years running from August to July. A second asset with
	 * no disposal checks that a plan reaching its natural term sums up to the acquisition value.
	 *
	 * @return	void
	 */
	public function testCalculationDepreciationPlan()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once DOL_DOCUMENT_ROOT.'/asset/class/asset.class.php';
		require_once DOL_DOCUMENT_ROOT.'/asset/class/assetdepreciationoptions.class.php';

		// The plan is stored in the tables of the asset module
		$resql = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."asset_depreciation LIMIT 1");
		if (!$resql) {
			$this->markTestSkipped('Tables of the asset module not available on this database.');
		}

		// A disposal needs an active record of the disposal type dictionary
		$disposaltypeid = 0;
		$resql = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."c_asset_disposal_type WHERE active = 1 ORDER BY rowid ASC");
		if ($resql && ($obj = $db->fetch_object($resql))) {
			$disposaltypeid = (int) $obj->rowid;
		}
		if (empty($disposaltypeid)) {
			$this->markTestSkipped('No active record into the dictionary of the disposal types.');
		}

		$savconvention = getDolGlobalString('ASSET_DEPRECIATION_DAY_COUNT_CONVENTION');
		$fiscalyearids = array();
		$assetids = array();

		try {
			// Fiscal years from August to July, so that the asset is put in service in the middle of one
			for ($year = 2021; $year <= 2026; $year++) {
				$datestart = sprintf('%d-08-01', $year);
				$dateend = sprintf('%d-07-31', $year + 1);
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."accounting_fiscalyear";
				$sql .= " (label, date_start, date_end, statut, entity, datec, fk_user_author)";
				$sql .= " VALUES ('PHPUNIT ".$year."', '".$db->escape($datestart)."', '".$db->escape($dateend)."'";
				$sql .= ", 0, ".((int) $conf->entity).", '".$db->idate(dol_now())."', ".((int) $user->id).")";
				$this->assertNotFalse($db->query($sql), $db->lasterror());
				$fiscalyearids[] = $db->last_insert_id(MAIN_DB_PREFIX."accounting_fiscalyear");
			}

			// The reference asset, sold before the end of its plan, and the same one left to its term
			foreach (array('disposed', 'until_term') as $kind) {
				$asset = new Asset($db);
				$asset->label = 'PHPUNIT depreciation '.$kind;
				$asset->date_acquisition = dol_mktime(0, 0, 0, 3, 10, 2022, 'gmt');
				$asset->date_start = $asset->date_acquisition;
				$asset->acquisition_value_ht = 13000;
				$asset->fk_asset_model = 0;

				$assetid = $asset->create($user);
				$this->assertGreaterThan(0, $assetid, $asset->errorsToString());
				$assetids[$kind] = $assetid;

				$options = new AssetDepreciationOptions($db);
				$options->deprecation_options['economic'] = array(
					'depreciation_type' => 0,			// Straight line
					'degressive_coefficient' => 0,
					'duration' => 5,
					'duration_type' => 0,				// Annually
					'accelerated_depreciation_option' => 0,
					'amount_base_depreciation_ht' => 13000,
					'amount_base_deductible_ht' => 0,
					'total_amount_last_depreciation_ht' => 0,
				);
				$this->assertGreaterThan(0, $options->updateDeprecationOptions($user, $assetid), $options->errorsToString());

				if ($kind == 'disposed') {
					$asset->fetch($assetid);
					$asset->disposal_date = dol_mktime(0, 0, 0, 2, 23, 2025, 'gmt');
					$asset->disposal_amount_ht = 4000;
					$asset->fk_disposal_type = $disposaltypeid;
					$asset->disposal_depreciated = 1;
					$asset->disposal_subject_to_vat = 0;
					$this->assertGreaterThan(0, $asset->dispose($user, 0), $asset->errorsToString());
				}
			}

			// Only the first and the last period are prorated, the ones in between are full annuities.
			// The first fiscal year (2022-03-10 to 2022-07-31) plus the next full one give the 3618.33 of
			// the accounting firm: 1018.33 + 2600.00.
			$expectedplans = array(
				'THIRTY_360' => array(1018.33, 2600.00, 2600.00, 1466.11),
				// These amounts assume the bounds of the fiscal periods are anchored on GMT midnight. That
				// is the case on a server running on UTC, and on every server once the companion fix
				// "Asset depreciation plan depends on the timezone of the server" is applied. Without
				// that fix, on a server whose timezone has a non zero UTC offset, num_between_day()
				// loses one day on a partial period and the last line becomes 1467.40 instead.
				'ACT_365' => array(1025.75, 2600.00, 2600.00, 1474.52),
			);
			foreach ($expectedplans as $convention => $expectedplan) {
				$conf->global->ASSET_DEPRECIATION_DAY_COUNT_CONVENTION = $convention;

				$asset = new Asset($db);
				$this->assertGreaterThan(0, $asset->fetch($assetids['disposed']));
				$this->assertGreaterThan(0, $asset->calculationDepreciation(), $asset->errorsToString());

				$plan = $this->getDepreciationPlan($assetids['disposed']);
				$this->assertEquals($expectedplan, $plan, 'Depreciation plan with the convention '.$convention);
				$this->assertEquals(array_sum($expectedplan), round(array_sum($plan), 2), 'Cumulated depreciation with the convention '.$convention);
			}

			// Whatever the convention, a plan reaching its natural term depreciates the acquisition value
			// and nothing more: the last period absorbs the rounding of all the previous ones.
			foreach (array_keys(getAssetDepreciationDayCountConventions()) as $convention) {
				$conf->global->ASSET_DEPRECIATION_DAY_COUNT_CONVENTION = $convention;

				$asset = new Asset($db);
				$this->assertGreaterThan(0, $asset->fetch($assetids['until_term']));
				$this->assertGreaterThan(0, $asset->calculationDepreciation(), $asset->errorsToString());

				$plan = $this->getDepreciationPlan($assetids['until_term']);
				$this->assertEquals(13000, round(array_sum($plan), 2), 'Sum of the plan with the convention '.$convention);
			}
		} finally {
			foreach ($assetids as $assetid) {
				$asset = new Asset($db);
				if ($asset->fetch($assetid) > 0) {
					$asset->delete($user);
				}
			}
			if (!empty($fiscalyearids)) {
				$db->query("DELETE FROM ".MAIN_DB_PREFIX."accounting_fiscalyear WHERE rowid IN (".implode(',', array_map('intval', $fiscalyearids)).")");
			}
			$conf->global->ASSET_DEPRECIATION_DAY_COUNT_CONVENTION = $savconvention;
		}
	}

	/**
	 * Return the depreciation amounts of the economic plan of an asset, ordered by date
	 *
	 * @param	int				$assetid	Id of the asset
	 * @return	array<int,float>			Depreciation amounts excl. tax
	 */
	private function getDepreciationPlan($assetid)
	{
		global $db;

		$sql = "SELECT depreciation_ht FROM ".MAIN_DB_PREFIX."asset_depreciation";
		$sql .= " WHERE fk_asset = ".((int) $assetid)." AND depreciation_mode = 'economic'";
		$sql .= " ORDER BY depreciation_date ASC, rowid ASC";
		$resql = $db->query($sql);
		$this->assertNotFalse($resql, $db->lasterror());

		$plan = array();
		while ($obj = $db->fetch_object($resql)) {
			$plan[] = round((float) $obj->depreciation_ht, 2);
		}

		return $plan;
	}

	/**
	 * testGetAssetDepreciationDayCountConvention
	 *
	 * @return	void
	 */
	public function testGetAssetDepreciationDayCountConvention()
	{
		global $conf, $mysoc;
		$conf = $this->savconf;

		$savconvention = getDolGlobalString('ASSET_DEPRECIATION_DAY_COUNT_CONVENTION');
		$savduration = getDolGlobalString('ASSET_DEPRECIATION_DURATION_PER_YEAR');
		$savcountrycode = $mysoc->country_code;

		try {
		// The new setup always wins
		$conf->global->ASSET_DEPRECIATION_DAY_COUNT_CONVENTION = 'ACT_ACT';
		$conf->global->ASSET_DEPRECIATION_DURATION_PER_YEAR = '360';
		$this->assertEquals('ACT_ACT', getAssetDepreciationDayCountConvention());

		// An unknown value is ignored
		$conf->global->ASSET_DEPRECIATION_DAY_COUNT_CONVENTION = 'ACT_360';
		$this->assertEquals('THIRTY_360', getAssetDepreciationDayCountConvention());

		// Backward compatibility with the deprecated setup
		$conf->global->ASSET_DEPRECIATION_DAY_COUNT_CONVENTION = '';
		$conf->global->ASSET_DEPRECIATION_DURATION_PER_YEAR = '360';
		$this->assertEquals('THIRTY_360', getAssetDepreciationDayCountConvention());
		$conf->global->ASSET_DEPRECIATION_DURATION_PER_YEAR = '365';
		$this->assertEquals('ACT_365', getAssetDepreciationDayCountConvention());

		// Nothing set up: the country of the company decides
		$conf->global->ASSET_DEPRECIATION_DURATION_PER_YEAR = '';
		$mysoc->country_code = 'FR';
		$this->assertEquals('THIRTY_360', getAssetDepreciationDayCountConvention());
		$mysoc->country_code = 'US';
		$this->assertEquals('ACT_365', getAssetDepreciationDayCountConvention());

		} finally {
			// Restore the global state even when an assertion above fails, so that the rest of the
			// suite does not inherit a modified country or a stale setup
			$mysoc->country_code = $savcountrycode;
			$conf->global->ASSET_DEPRECIATION_DAY_COUNT_CONVENTION = $savconvention;
			$conf->global->ASSET_DEPRECIATION_DURATION_PER_YEAR = $savduration;
		}
	}
}
