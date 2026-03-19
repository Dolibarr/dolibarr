<?php
/* Copyright (C) 2026		Pierre Ardoin		<developpeur@lesmetiersdubatiment.fr>
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
 * \file		product/stock/stocktransfer/class/actions_stocktransfer.class.php
 * \ingroup	stocktransfer
 * \brief		Hooks for stock transfer module.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';

/**
 * Class ActionsStockTransfer
 */
class ActionsStockTransfer extends CommonHookActions
{
	/** @var DoliDB Database handler */
	public $db;

	/** @var array<int,array<string,mixed>> Hook result array */
	public $resArray = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Add stock transfer entry to quick add menu.
	 *
	 * @param array<string,mixed> $parameters Hook parameters
	 * @param CommonObject|null $object       Object
	 * @param string $action                  Current action
	 * @param HookManager $hookmanager        Hook manager propagated by reference
	 * @return int                            0 if OK
	 */
	public function menuDropdownQuickaddItems($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user;

		if (!isModEnabled('stocktransfer') || !$user->hasRight('stocktransfer', 'stocktransfer', 'write')) {
			return 0;
		}

		$this->resArray[] = array(
			'url' => '/product/stock/stocktransfer/stocktransfer_card.php?action=create&amp;mainmenu=products',
			'title' => 'StockTransferNew@stocks',
			'name' => 'StockTransfer@stocks',
			'picto' => 'stock',
			'activation' => !empty($conf->stocktransfer->enabled),
			'position' => 420,
		);

		return 0;
	}
}
