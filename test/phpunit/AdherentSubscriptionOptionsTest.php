<?php
/* Copyright (C) 2026 Marcel
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', dirname(__DIR__, 2).'/htdocs');
}
if (!defined('DOL_URL_ROOT')) {
	define('DOL_URL_ROOT', '');
}
require_once __DIR__.'/fixtures/subscriptionoptions.php';

/** Subscription options without bootstrapping the application database. */
class AdherentSubscriptionOptionsTest extends PHPUnit\Framework\TestCase
{
	/** @return void */
	public function testIsolatedScenarios()
	{
		global $conf, $langs, $hookmanager, $mc;
		$savedConf = $conf;
		$savedLangs = $langs;
		$savedHooks = $hookmanager;
		$savedMulticompany = $mc;
		try {
			subscriptionOptionsTestEnvironment();
			foreach (subscriptionOptionsScenarios() as $name => $scenario) {
				subscriptionOptionsTestEnvironment();
				$this->assertTrue($scenario(), $name);
			}
		} finally {
			$conf = $savedConf;
			$langs = $savedLangs;
			$hookmanager = $savedHooks;
			$mc = $savedMulticompany;
		}
	}
}
