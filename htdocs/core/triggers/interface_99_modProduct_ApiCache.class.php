<?php
/* Copyright (C) 2026		Frédéric France			<frederic.france@free.fr>
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
 */

/**
 *	\file       htdocs/core/triggers/interface_99_modProduct_ApiCache.class.php
 *  \ingroup    product
 *  \brief      Trigger that invalidates the product REST API read cache.
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 *  Class of triggered functions for the product REST API read cache
 */
class InterfaceApiCache extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "core";
		$this->description = "Invalidates the product REST API read cache (constant PRODUCT_API_CACHE_ENABLE) on any product write.";
		$this->version = self::VERSIONS['dev'];
		$this->picto = 'product';
	}

	/**
	 * Function called when a Dolibarr business event is done.
	 * All functions "runTrigger" are triggered if file of function is inside directory core/triggers.
	 *
	 * @param string		$action		Event action code
	 * @param CommonObject	$object		Object
	 * @param User			$user		Object user
	 * @param Translate		$langs		Object langs
	 * @param Conf			$conf		Object conf
	 * @return int						Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('product') && !isModEnabled('service')) {
			return 0; // Product/Service module not active, nothing to do
		}
		if (!getDolGlobalString('PRODUCT_API_CACHE_ENABLE')) {
			return 0; // Read cache disabled, nothing to invalidate
		}

		$watchedactions = array(
			'PRODUCT_CREATE',
			'PRODUCT_MODIFY',
			'PRODUCT_DELETE',
			'PRODUCT_PRICE_MODIFY',
		);
		if (!in_array($action, $watchedactions)) {
			return 0;
		}

		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';

		// Bump the generation stamp: this invalidates every entry of the product
		// API read cache at once (shared caches have no delete-by-pattern).
		// The key string must stay in sync with Products::CACHE_GENERATION_KEY
		// in product/class/api_products.class.php.
		dol_setcache('productapicache_generation', (string) dol_now(), 0, 1, 1);
		dol_syslog("Trigger '".$this->name."' for action '".$action."' invalidated the product API read cache", LOG_DEBUG);

		return 1;
	}
}
