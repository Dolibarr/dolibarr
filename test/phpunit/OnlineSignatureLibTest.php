<?php
/**
 * Copyright (C) 2013	Marcos García	<marcosgdf@gmail.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024-2026	MDW					<mdeweerd@users.noreply.github.com>
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

require_once __DIR__.'/../../htdocs/core/class/conf.class.php';
require_once __DIR__.'/../../htdocs/core/lib/functions.lib.php';
require_once __DIR__.'/../../htdocs/blockedlog/lib/securitycore.lib.php';
require_once __DIR__.'/../../htdocs/core/lib/signature.lib.php';

/**
 * Regression tests for native online signature security seeds, without a database.
 */
class OnlineSignatureLibTest extends PHPUnit\Framework\TestCase
{
	/**
	 * Preserve configured values and the native instance fallback.
	 *
	 * @return void
	 */
	public function testNativeSecuritySeeds()
	{
		global $conf, $dolibarr_main_instance_unique_id;

		$savedconf = $conf;
		$savedid = $dolibarr_main_instance_unique_id;
		$conf = new Conf();
		try {
			foreach (array('instance-a', 'instance-b') as $instanceid) {
				$dolibarr_main_instance_unique_id = $instanceid;
				foreach (array('', 'global-salt') as $salt) {
					$conf->global->MAIN_SECURITY_SALT = $salt;
					$expecteddefault = substr(dol_hash('dolibarr'.$instanceid, 'sha256'), 0, 32);
					$this->assertSame($expecteddefault, getOnlineSignatureDefaultSecuritySeed());
					foreach (array('proposal', 'contract', 'fichinter', 'societe_rib', 'order', 'expedition', 'propale', 'unknown') as $source) {
						$key = dol_strtoupper($source).'_ONLINE_SIGNATURE_SECURITY_TOKEN';
						foreach (array(null, '', '0', 'configured-token', "invalid\0token") as $value) {
							unset($conf->global->$key);
							if ($value !== null) {
								$conf->global->$key = $value;
							}
							$this->assertSame($value === null ? $expecteddefault : $value, getOnlineSignatureSecuritySeed($source));
						}
						unset($conf->global->$key);
					}
				}
			}
		} finally {
			$conf = $savedconf;
			$dolibarr_main_instance_unique_id = $savedid;
		}
	}
}

