<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    test/phpunit/DeferredRevenueTest.php
 * \ingroup test
 * \brief   Deferred revenue calendar calculations without a database connection
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', dirname(__FILE__).'/../../htdocs');
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/deferredrevenue.class.php';

/**
 * Calendar calculations do not require a configured Dolibarr instance.
 */
class DeferredRevenueTest extends PHPUnit\Framework\TestCase
{

	/** @var mixed Original configuration */
	private $previousconf;

	/** @var mixed Original translations */
	private $previouslangs;

	/** @var string Original timezone */
	private $previoustimezone;

	/**
	 * Set up numeric precision without loading an application or database.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		$this->previousconf = isset($GLOBALS['conf']) ? $GLOBALS['conf'] : null;
		$this->previouslangs = isset($GLOBALS['langs']) ? $GLOBALS['langs'] : null;
		$this->previoustimezone = date_default_timezone_get();
		$GLOBALS['conf'] = (object) array('global' => (object) array('MAIN_MAX_DECIMALS_TOT' => 2));
		$GLOBALS['langs'] = null;
		date_default_timezone_set('UTC');
	}

	/**
	 * Restore the environment for other tests in the main suite.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		$GLOBALS['conf'] = $this->previousconf;
		$GLOBALS['langs'] = $this->previouslangs;
		date_default_timezone_set($this->previoustimezone);
	}

	/**
	 * Covers cutoff boundaries, signed credit notes, leap years and cent rounding.
	 *
	 * @return void
	 */
	public function testCalendarProration()
	{
		foreach (self::periodProvider() as $name => $period) {
			list($amount, $start, $end, $cutoff, $expected, $days) = $period;
			$result = DeferredRevenue::calculate($amount, dol_stringtotime($start), dol_stringtotime($end), dol_stringtotime($cutoff));
			$this->assertSame($expected, $result['amount'], $name);
			$this->assertSame($days, $result['deferred_days'], $name);
			$this->assertSame('DeferredRevenueCalculated', $result['status'], $name);
		}
	}

	/**
	 * Calendar examples independent of business documents and payments.
	 *
	 * @return array<string,array{float,string,string,string,float,int}>
	 */
	public static function periodProvider()
	{
		return array(
			'crossing cutoff' => array(200.0, '20261221', '20270109', '20261231', 90.0, 9),
			'fully future' => array(200.0, '20270101', '20270120', '20261231', 200.0, 20),
			'fully earned' => array(200.0, '20261201', '20261220', '20261231', 0.0, 0),
			'cutoff is last day' => array(200.0, '20261201', '20261220', '20261220', 0.0, 0),
			'cutoff is first day' => array(200.0, '20261221', '20270109', '20261221', 190.0, 19),
			'single future day' => array(200.0, '20270101', '20270101', '20261231', 200.0, 1),
			'single current day' => array(200.0, '20261231', '20261231', '20261231', 0.0, 0),
			'credit note' => array(-40.0, '20261221', '20270109', '20261231', -18.0, 9),
			'leap day' => array(290.0, '20240201', '20240229', '20240228', 10.0, 1),
			'round to cents' => array(100.0, '20261230', '20270101', '20261231', 33.33, 1),
			'zero amount' => array(0.0, '20261221', '20270109', '20261231', 0.0, 9)
		);
	}

	/**
	 * Missing periods must never be interpreted as zero deferred revenue.
	 *
	 * @return void
	 */
	public function testMissingAndInvalidPeriodsRequireReview()
	{
		$cutoff = dol_stringtotime('20261231');
		$missing = DeferredRevenue::calculate(200, false, dol_stringtotime('20270109'), $cutoff);
		$this->assertSame(null, $missing['amount']);
		$this->assertSame('DeferredRevenueMissingDates', $missing['status']);
		$invalid = DeferredRevenue::calculate(200, dol_stringtotime('20270109'), dol_stringtotime('20261221'), $cutoff);
		$this->assertSame(null, $invalid['amount']);
		$this->assertSame('DeferredRevenueInvalidDates', $invalid['status']);
	}

	/**
	 * A daylight saving transition still contains three calendar days.
	 *
	 * @return void
	 */
	public function testDaylightSavingDoesNotChangeCalendarProration()
	{
		date_default_timezone_set('Europe/Paris');
		$result = DeferredRevenue::calculate(300, dol_mktime(0, 0, 0, 3, 28, 2026, 'tzserver'), dol_mktime(23, 59, 59, 3, 30, 2026, 'tzserver'), dol_mktime(0, 0, 0, 3, 28, 2026, 'tzserver'));
		$this->assertSame(3, $result['total_days']);
		$this->assertSame(2, $result['deferred_days']);
		$this->assertSame(200.0, $result['amount']);
	}

	/**
	 * Use the configured total precision instead of forcing cent rounding.
	 *
	 * @return void
	 */
	public function testConfiguredPrecision()
	{
		$GLOBALS['conf']->global->MAIN_MAX_DECIMALS_TOT = 3;
		$result = DeferredRevenue::calculate(100, dol_stringtotime('20261230'), dol_stringtotime('20270101'), dol_stringtotime('20261231'));
		$this->assertSame(33.333, $result['amount']);
	}
}
