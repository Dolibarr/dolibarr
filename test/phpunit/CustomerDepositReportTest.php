<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    test/phpunit/CustomerDepositReportTest.php
 * \ingroup test
 * \brief   Customer deposit receipt and application controls
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', dirname(__FILE__).'/../../htdocs');
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/customerdepositreport.class.php';

/**
 * Monetary controls independent of a database or a configured application.
 */
class CustomerDepositReportTest extends PHPUnit\Framework\TestCase
{

	/** @var mixed Original configuration */
	private $previousconf;

	/** @var mixed Original translations */
	private $previouslangs;

	/**
	 * Use configured native total precision.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		$this->previousconf = isset($GLOBALS['conf']) ? $GLOBALS['conf'] : null;
		$this->previouslangs = isset($GLOBALS['langs']) ? $GLOBALS['langs'] : null;
		$GLOBALS['conf'] = (object) array('global' => (object) array('MAIN_MAX_DECIMALS_TOT' => 2));
		$GLOBALS['langs'] = null;
	}

	/**
	 * Restore other tests' environment.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		$GLOBALS['conf'] = $this->previousconf;
		$GLOBALS['langs'] = $this->previouslangs;
	}

	/**
	 * Receipts, partial applications and refunds must not be confused with PCA.
	 *
	 * @return void
	 */
	public function testReceiptAndApplicationBalances()
	{
		$cases = array(
			'unpaid' => array(120, 0, 0, 0, 0, 0.0, 0.0, 'DepositControlCleared'),
			'fully received' => array(120, 120, 0, 0, 0, 120.0, 120.0, 'DepositControlOutstanding'),
			'partly received' => array(120, 50, 0, 0, 0, 50.0, 50.0, 'DepositControlOutstanding'),
			'partly applied' => array(120, 120, 0, 0, 40, 120.0, 80.0, 'DepositControlOutstanding'),
			'fully applied' => array(120, 120, 0, 0, 120, 120.0, 0.0, 'DepositControlCleared'),
			'refunded credit note' => array(120, 120, 30, 30, 0, 90.0, 90.0, 'DepositControlOutstanding'),
			'credit note pending refund' => array(120, 120, 0, 30, 0, 120.0, 90.0, 'DepositControlInconsistent'),
			'fully refunded' => array(120, 120, 120, 120, 0, 0.0, 0.0, 'DepositControlCleared'),
			'overpayment' => array(120, 150, 0, 0, 0, 150.0, 120.0, 'DepositControlInconsistent'),
			'overapplication' => array(120, 120, 0, 0, 150, 120.0, 0.0, 'DepositControlInconsistent'),
			'application exceeds receipts' => array(120, 40, 0, 0, 60, 40.0, 0.0, 'DepositControlInconsistent'),
			'negative receipts' => array(120, 20, 30, 0, 0, -10.0, 0.0, 'DepositControlInconsistent'),
			'credit exceeds invoice' => array(120, 120, 0, 150, 0, 120.0, 0.0, 'DepositControlInconsistent'),
			'negative application' => array(120, 120, 0, 0, -10, 120.0, 120.0, 'DepositControlInconsistent')
		);
		foreach ($cases as $name => $case) {
			$result = CustomerDepositReport::calculate($case[0], $case[1], $case[2], $case[3], $case[4]);
			$this->assertSame($case[5], $result['received'], $name);
			$this->assertSame($case[6], $result['remaining'], $name);
			$this->assertSame($case[7], $result['status'], $name);
		}
	}

	/**
	 * A native precision change must apply without hardcoded financial decimals.
	 *
	 * @return void
	 */
	public function testConfiguredPrecision()
	{
		$GLOBALS['conf']->global->MAIN_MAX_DECIMALS_TOT = 3;
		$result = CustomerDepositReport::calculate(100, 100, 0, 0, 33.333);
		$this->assertSame(66.667, $result['remaining']);
		$GLOBALS['conf']->global->MAIN_MAX_DECIMALS_TOT = 2;
		$result = CustomerDepositReport::calculate(100, 100, 0, 0, 33.333);
		$this->assertSame(66.67, $result['remaining']);
	}
}
