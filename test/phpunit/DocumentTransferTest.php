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
 * \brief      Validation tests for object attachment transfers
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
			$this->assertFalse($transfer->inspect('order', 1, 'invoice', 2, $filename, $user));
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
		$this->assertFalse($transfer->inspect('../order', 1, 'invoice', 2, 'file.pdf', $user));
		$this->assertSame(400, $transfer->status);
		$this->assertFalse($transfer->inspect('order', 1, 'invoice', 0, 'file.pdf', $user));
		$this->assertSame(400, $transfer->status);
		$this->assertFalse($transfer->inspect('order', 1, 'order', 1, 'file.pdf', $user));
		$this->assertSame(400, $transfer->status);
		$this->assertFalse($transfer->inspect('order', 1, 'bad@module/../../', 2, 'file.pdf', $user));
		$this->assertSame(400, $transfer->status);
	}

	/**
	 * Reject document paths that leave the data root.
	 *
	 * @return void
	 */
	public function testStoragePathMustRemainInDataRoot()
	{
		global $db;

		$transfer = new DocumentTransfer($db);
		$method = new ReflectionMethod(DocumentTransfer::class, 'safePath');
		if (PHP_VERSION_ID < 80100) {
			$method->setAccessible(true);
		}
		$root = rtrim(DOL_DATA_ROOT, '/');
		$this->assertFalse($method->invoke($transfer, $root.'/../outside.pdf'));
		$this->assertFalse($method->invoke($transfer, '/outside.pdf'));
		$this->assertTrue($method->invoke($transfer, $root.'/invoice/INV-1/file.pdf'));
	}

	/**
	 * Public results must never include server paths or internal object state.
	 *
	 * @return void
	 */
	public function testPublicResultContainsOnlySafeFields()
	{
		global $db;

		$transfer = new DocumentTransfer($db);
		$checked = array(
			'source_type' => 'order', 'source_id' => 1, 'source_ref' => 'CO-1',
			'target_type' => 'project', 'target_id' => 2, 'target_ref' => 'PR-2',
			'filename' => 'attachment.pdf', 'sha256' => str_repeat('a', 64),
			'source' => array('path' => '/private/source.pdf'),
			'target' => array('path' => '/private/target.pdf')
		);
		$result = $transfer->publicResult($checked, 7, false);
		$this->assertSame(7, $result['ecm_file_id']);
		$this->assertSame('project', $result['target_type']);
		$this->assertFalse($result['source_removed']);
		$this->assertArrayNotHasKey('source', $result);
		$this->assertArrayNotHasKey('target', $result);
	}
}
