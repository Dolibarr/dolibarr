<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

require_once dirname(__FILE__).'/CommonClassTest.class.php';

/**
 * Test schema and migration contract for contact address mode.
 *
 */
class ContactAddressMigrationContractTest extends CommonClassTest
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
	 * The column addition must sit in the migration script of the version under development, and nowhere else.
	 *
	 * The target script is derived from DOL_MAJOR_VERSION instead of being hardcoded: a block left in an
	 * already released script is never replayed, so the column would silently never be created on an upgrade
	 * to the current version. Deriving it means this test keeps guarding the placement across a version bump.
	 *
	 * @return void
	 */
	public function testMigrationPlacementMatchesCurrentContract(): void
	{
		$current = (int) DOL_MAJOR_VERSION;
		$dir = DOL_DOCUMENT_ROOT.'/install/mysql/migration/';
		$expectedfile = $dir.($current - 1).'.0.0-'.$current.'.0.0.sql';

		$this->assertFileExists($expectedfile, 'The migration script of the version under development must exist');
		$content = file_get_contents($expectedfile);

		$this->assertStringContainsString('ALTER TABLE llx_socpeople ADD COLUMN use_thirdparty_address smallint DEFAULT NULL AFTER fk_soc;', $content);
		$this->assertStringContainsString('ALTER TABLE llx_socpeople ADD COLUMN use_thirdparty_address smallint DEFAULT NULL;', $content);

		// And it must not be left behind in any already released script, where it would never run again
		foreach (glob($dir.'*-*.sql') as $file) {
			if ($file === $expectedfile) {
				continue;
			}
			$this->assertStringNotContainsString(
				'ALTER TABLE llx_socpeople ADD COLUMN use_thirdparty_address',
				file_get_contents($file),
				'The column addition must not stay in the released script '.basename($file)
			);
		}
	}
}
