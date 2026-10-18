<?php
/* Copyright (C) 2026 braito4 <braito4@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       test/phpunit/DocumentTransferTest.php
 * \ingroup    test
 * \brief      Validation tests for order attachment transfers
 */

require_once dirname(__FILE__).'/../../htdocs/ecm/class/documenttransfer.class.php';

/**
 * @backupGlobals disabled
 */
class DocumentTransferTest extends PHPUnit\Framework\TestCase
{
	/**
	 * Reject a path or hidden filename before accessing any document.
	 *
	 * @return void
	 */
	public function testInvalidFilenameIsRejected()
	{
		global $db, $user;

		$transfer = new DocumentTransfer($db);
		foreach (array('../outside.pdf', 'other/file.pdf', '.hidden.pdf', '') as $filename) {
			$this->assertFalse($transfer->inspectOrderToInvoice('supplier', 1, 1, $filename, $user));
			$this->assertSame(400, $transfer->status);
			$this->assertSame('invalid_input', $transfer->reason);
		}
	}

	/**
	 * Reject unsupported objects and missing identifiers.
	 *
	 * @return void
	 */
	public function testInvalidObjectParametersAreRejected()
	{
		global $db, $user;

		$transfer = new DocumentTransfer($db);
		$this->assertFalse($transfer->inspectOrderToInvoice('proposal', 1, 1, 'file.pdf', $user));
		$this->assertSame(400, $transfer->status);
		$this->assertFalse($transfer->inspectOrderToInvoice('customer', 1, 0, 'file.pdf', $user));
		$this->assertSame(400, $transfer->status);
	}

	/**
	 * A user without order or invoice rights cannot inspect a valid file.
	 *
	 * @return void
	 */
	public function testWriteRightsAreRequiredBeforeObjectLookup()
	{
		global $db;

		$restricted = new User($db);
		$transfer = new DocumentTransfer($db);
		foreach (array('customer', 'supplier') as $orderType) {
			$this->assertFalse($transfer->inspectOrderToInvoice($orderType, 1, 1, 'file.pdf', $restricted));
			$this->assertSame(403, $transfer->status);
			$this->assertSame('document_rights_required', $transfer->reason);
		}
	}
}
