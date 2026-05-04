<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Test schema and migration contract for contact address mode.
 */
class ContactAddressMigrationContractTest extends PHPUnit\Framework\TestCase
{
	/**
	 * Fresh install schema must expose the persisted flag.
	 *
	 * @return void
	 */
	public function testFreshInstallSchemaContainsUseThirdpartyAddress(): void
	{
		$content = file_get_contents(DOL_DOCUMENT_ROOT.'/install/mysql/tables/llx_socpeople.sql');

		$this->assertStringContainsString('use_thirdparty_address', $content);
		$this->assertStringContainsString('smallint DEFAULT NULL', $content);
	}

	/**
	 * Upgrade path must carry the column addition in 23->24 and not in 22->23.
	 *
	 * @return void
	 */
	public function testMigrationPlacementMatchesCurrentContract(): void
	{
		$content22023 = file_get_contents(DOL_DOCUMENT_ROOT.'/install/mysql/migration/22.0.0-23.0.0.sql');
		$content23024 = file_get_contents(DOL_DOCUMENT_ROOT.'/install/mysql/migration/23.0.0-24.0.0.sql');

		$this->assertStringNotContainsString('ALTER TABLE llx_socpeople ADD COLUMN use_thirdparty_address', $content22023);
		$this->assertStringContainsString('ALTER TABLE llx_socpeople ADD COLUMN use_thirdparty_address smallint DEFAULT NULL AFTER fk_soc;', $content23024);
		$this->assertStringContainsString('ALTER TABLE llx_socpeople ADD COLUMN use_thirdparty_address smallint DEFAULT NULL;', $content23024);
	}
}
