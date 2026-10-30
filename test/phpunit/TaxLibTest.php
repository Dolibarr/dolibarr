<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * @file test/phpunit/TaxLibTest.php
 * @ingroup test
 * @brief Integration tests for invoice tax reports.
 */

global $db, $conf;
if (!defined('DOL_DOCUMENT_ROOT')) {
	require_once __DIR__.'/../../htdocs/master.inc.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';

/**
 * @backupGlobals disabled
 */
class TaxLibTest extends PHPUnit\Framework\TestCase
{
	/**
	 * Compare both report groupings, tax modes and situation representations.
	 *
	 * @return void
	 */
	public function testSituationInvoiceAmounts(): void
	{
		global $db, $conf;
		if (isModEnabled('multicompany')) {
			$this->markTestSkipped('This fixture uses an isolated entity without Multicompany sharing.');
		}
		$savedconf = $conf;
		$conf = clone $conf;
		$conf->global = clone $conf->global;
		$conf->entity = 90040373;
		try {
			foreach (array(0, 1) as $productType) {
				foreach (array(0, 1, 2) as $situationMode) {
					foreach (array('invoice', 'payment') as $taxMode) {
						$conf->global->INVOICE_USE_SITUATION = $situationMode;
						$conf->global->TAX_MODE_SELL_PRODUCT = $taxMode;
						$conf->global->TAX_MODE_SELL_SERVICE = $taxMode;
						$conf->global->MAIN_MAX_DECIMALS_TOT = 2;
						$db->begin();
						try {
							$this->createSituationFixture($productType, $situationMode);
							foreach (array('vat', 'localtax1', 'localtax2') as $tax) {
								// The stock service/invoice path in modes 0/2 has no datep column.
								// Assert those existing warnings rather than fix another mode here.
								$warnings = array();
								set_error_handler(function ($severity, $message) use (&$warnings) {
									if (($severity == E_WARNING && $message === 'Undefined array key "datep"')
										|| ($severity == E_NOTICE && $message === 'Undefined index: datep')) {
										$warnings[] = $message;
										return true;
									}
									return false;
								});
								try {
									$reports = array(
										tax_by_rate($tax, $db, 0, 0, 0, 0, 0, 'sell'),
										tax_by_thirdparty($tax, $db, 0, 0, 0, 0, 'sell'),
									);
								} finally {
									restore_error_handler();
								}
								$this->assertCount($situationMode != 1 && $productType == 1 && $taxMode == 'invoice' ? 6 : 0, $warnings);
								foreach ($reports as $report) {
									$this->assertIsArray($report);
									$this->assertCount(1, $report);
									$amounts = reset($report);
									$this->assertEquals(100000, $amounts['totalht']);
									$this->assertEquals(20000, $amounts['vat']);
									$this->assertEquals(1000, $amounts['localtax1']);
									$this->assertEquals(2000, $amounts['localtax2']);
									$collected = 0;
									foreach ($amounts['vat_list'] as $key => $vat) {
										$collected += $taxMode == 'invoice' ? $vat : $vat * $amounts['payment_amount'][$key] / $amounts['ftotal_ttc'][$key];
									}
									$this->assertEquals($taxMode == 'invoice' ? 20000 : 10000, $collected);
								}
							}
						} finally {
							$db->rollback();
						}
					}
				}
			}
		} finally {
			$conf = $savedconf;
		}
	}

	/**
	 * Credit notes affect previous progress; another entity must not affect the report.
	 *
	 * @return void
	 */
	public function testCreditNotesAndEntityIsolation(): void
	{
		global $db, $conf;
		if (isModEnabled('multicompany')) {
			$this->markTestSkipped('This fixture uses an isolated entity without Multicompany sharing.');
		}
		$savedconf = $conf;
		$conf = clone $conf;
		$conf->global = clone $conf->global;
		$conf->entity = 90040373;
		$conf->global->INVOICE_USE_SITUATION = 1;
		$conf->global->TAX_MODE_SELL_PRODUCT = 'invoice';
		$conf->global->TAX_MODE_SELL_SERVICE = 'invoice';
		$conf->global->MAIN_MAX_DECIMALS_TOT = 2;
		$db->begin();
		try {
			$this->createSituationFixture(0, 1);
			$result = $db->query('SELECT f.rowid, f.fk_soc, fd.rowid as lineid FROM '.MAIN_DB_PREFIX.'facture as f INNER JOIN '.MAIN_DB_PREFIX.'facturedet as fd ON fd.fk_facture = f.rowid WHERE f.entity = '.((int) $conf->entity).' AND f.situation_counter = 1');
			$this->assertNotFalse($result);
			$first = $db->fetch_object($result);
			$this->assertIsObject($first);
			foreach (array($conf->entity, $conf->entity + 1) as $entity) {
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture (ref, entity, type, fk_soc, fk_statut, datef, total_ht, total_tva, total_ttc, situation_cycle_ref)';
				$sql .= " VALUES ('TEST40373-CREDIT', ".((int) $entity).', 2, '.((int) $first->fk_soc).", 1, '2100-01-01', -10000, -2000, -12300, ".((int) $first->fk_soc).')';
				$this->assertNotFalse($db->query($sql), (string) $db->lasterror());
				$creditid = $db->last_insert_id(MAIN_DB_PREFIX.'facture');
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facturedet (fk_facture, product_type, tva_tx, total_ht, total_tva, total_ttc, total_localtax1, total_localtax2, situation_percent, fk_prev_id)';
				$sql .= ' VALUES ('.((int) $creditid).', 0, 20, -10000, -2000, -12300, -100, -200, -10, '.((int) $first->lineid).')';
				$this->assertNotFalse($db->query($sql), (string) $db->lasterror());
				foreach (array(tax_by_rate('vat', $db, 0, 0, 0, 0, 0, 'sell'), tax_by_thirdparty('vat', $db, 0, 0, 0, 0, 'sell')) as $report) {
					$this->assertIsArray($report);
					$this->assertCount(1, $report);
					$amounts = reset($report);
					$this->assertEquals(100000, $amounts['totalht']);
					$this->assertEquals(20000, $amounts['vat']);
				}
			}
			// A zero-progress line must not cause a division by zero.
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facturedet (fk_facture, product_type, tva_tx, total_ht, total_tva, total_ttc, situation_percent)';
			$sql .= ' VALUES ('.((int) $first->rowid).', 0, 20, 0, 0, 0, 0)';
			$this->assertNotFalse($db->query($sql), (string) $db->lasterror());
			$report = tax_by_rate('vat', $db, 0, 0, 0, 0, 0, 'sell');
			$this->assertIsArray($report);
			$this->assertEquals(20000, reset($report)['vat']);
			// Ordinary invoices use their stored incremental amounts even in legacy mode.
			$this->assertNotFalse($db->query('UPDATE '.MAIN_DB_PREFIX.'facture SET type = 0 WHERE entity = '.((int) $conf->entity)));
			$report = tax_by_rate('vat', $db, 0, 0, 0, 0, 0, 'sell');
			$this->assertIsArray($report);
			$this->assertEquals(38000, reset($report)['vat']);
		} finally {
			$db->rollback();
			$conf = $savedconf;
		}
	}

	/**
	 * Create cumulative or incremental lines, with two partial payments per invoice.
	 *
	 * @param int $productType Product or service
	 * @param int $situationMode Situation representation
	 * @return void
	 */
	private function createSituationFixture($productType, $situationMode): void
	{
		global $db, $conf;
		$this->assertNotFalse($db->query('INSERT INTO '.MAIN_DB_PREFIX."societe (nom, entity) VALUES ('Tax report fixture', ".((int) $conf->entity).')'), (string) $db->lasterror());
		$socid = $db->last_insert_id(MAIN_DB_PREFIX.'societe');
		$previousLine = 0;
		foreach (array(30, 70, 100) as $rank => $progress) {
			$delta = $rank == 1 ? 40 : 30;
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture (ref, entity, type, fk_soc, fk_statut, datef, total_ht, total_tva, total_ttc, situation_cycle_ref, situation_counter)';
			$sql .= " VALUES ('TEST40373-".$rank."', ".((int) $conf->entity).', 5, '.((int) $socid).", 1, '2100-01-01', ".($delta * 1000).', '.($delta * 200).', '.($delta * 1230).', '.((int) $socid).', '.($rank + 1).')';
			$this->assertNotFalse($db->query($sql), (string) $db->lasterror());
			$invoiceid = $db->last_insert_id(MAIN_DB_PREFIX.'facture');
			$lineProgress = $situationMode == 1 ? $progress : $delta;
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facturedet (fk_facture, product_type, tva_tx, localtax1_tx, localtax2_tx, total_ht, total_tva, total_ttc, total_localtax1, total_localtax2, situation_percent, fk_prev_id)';
			$sql .= ' VALUES ('.((int) $invoiceid).', '.((int) $productType).', 20, 1, 2, '.($lineProgress * 1000).', '.($lineProgress * 200).', '.($lineProgress * 1230).', '.($lineProgress * 10).', '.($lineProgress * 20).', '.$lineProgress.', '.($previousLine > 0 ? (int) $previousLine : 'NULL').')';
			$this->assertNotFalse($db->query($sql), (string) $db->lasterror());
			$previousLine = $db->last_insert_id(MAIN_DB_PREFIX.'facturedet');
			foreach (array(1, 2) as $part) {
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiement (entity, ref, datep, amount, fk_paiement)';
				$sql .= ' VALUES ('.((int) $conf->entity).", 'TEST40373-".$rank.'-'.$part."', '2100-01-02', ".($delta * 1230 / 4).', 1)';
				$this->assertNotFalse($db->query($sql), (string) $db->lasterror());
				$paymentid = $db->last_insert_id(MAIN_DB_PREFIX.'paiement');
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiement_facture (fk_paiement, fk_facture, amount)';
				$sql .= ' VALUES ('.((int) $paymentid).', '.((int) $invoiceid).', '.($delta * 1230 / 4).')';
				$this->assertNotFalse($db->query($sql), (string) $db->lasterror());
			}
		}
	}
}
