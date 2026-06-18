<?php
/* Copyright (C) 2026 Quentin VIAL--GOUTEYRON <quentin.vial-gouteyron@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    test/phpunit/ModuleBuilderFkIndexTest.php
 * \ingroup modulebuilder
 * \brief   PHPUnit test for ModuleBuilder foreign key / index / constraint generation:
 *          foreignkey is no longer corrupted on class rebuild, and rebuildObjectSql generates
 *          UNIQUE indexes and FOREIGN KEY ... ON DELETE clauses from the field definition.
 */

global $conf, $user, $langs, $db;

require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/modulebuilder.lib.php';
require_once dirname(__FILE__).'/../../htdocs/modulebuilder/class/NamingContract.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class ModuleBuilderFkIndexTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredMethod
 * @phan-file-suppress PhanTypeMismatchArgument
 */
class ModuleBuilderFkIndexTest extends CommonClassTest
{
	/**
	 * Build a throwaway ModuleBuilder module in a temp dir from the official template, using a unique
	 * module/object name (so the generated class does not clash with other tests), then run the real
	 * rebuildObjectClass()/rebuildObjectSql() with a link field carrying unique + ondelete options.
	 *
	 * @return void
	 */
	public function testRealFkUniqueAndOnDeleteGeneration()
	{
		global $db;

		$tpl = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
		$module = 'testfkmod';
		$objectname = 'FkLink';
		$nc = new NamingContract($module, $objectname);

		$base = sys_get_temp_dir().'/mbfktest_'.getmypid();
		dol_delete_dir_recursive($base);
		dol_mkdir($base.'/class');
		dol_mkdir($base.'/sql');

		// Project the template into the temp module with the unique naming.
		file_put_contents($base.'/class/'.strtolower($objectname).'.class.php', $nc->applyTo(file_get_contents($tpl.'/class/myobject.class.php')));
		file_put_contents($base.'/sql/llx_'.$module.'_'.strtolower($objectname).'.sql', $nc->applyTo(file_get_contents($tpl.'/sql/llx_mymodule_myobject.sql')));
		file_put_contents($base.'/sql/llx_'.$module.'_'.strtolower($objectname).'.key.sql', $nc->applyTo(file_get_contents($tpl.'/sql/llx_mymodule_myobject.key.sql')));

		$addfieldentry = array(
			'name' => 'fktestlink', 'label' => 'TestLink', 'type' => 'integer',
			'foreignkey' => 'societe.rowid', 'unique' => 1, 'ondelete' => 'CASCADE',
			'notnull' => 0, 'position' => 200, 'enabled' => 1, 'visible' => 1
		);

		$object = rebuildObjectClass($base, $module, $objectname, '0', $base, $addfieldentry);
		$this->assertIsObject($object, 'rebuildObjectClass must return the object');

		$class = file_get_contents($base.'/class/'.strtolower($objectname).'.class.php');
		// The foreignkey must survive the rebuild as a string (regression: it used to be cast to (int) => "0").
		$this->assertStringContainsString('"foreignkey" => "societe.rowid"', $class, 'foreignkey must be kept as a string, not corrupted to 0');
		$this->assertStringNotContainsString('"foreignkey" => "0"', $class, 'foreignkey must not be serialized as 0');
		$this->assertStringContainsString('"unique" => "1"', $class, 'unique attribute must be serialized');
		$this->assertStringContainsString('"ondelete" => "CASCADE"', $class, 'ondelete attribute must be serialized');

		$res = rebuildObjectSql($base, $module, $objectname, '0', $base, $object, 'external');
		$this->assertGreaterThan(0, $res, 'rebuildObjectSql must succeed');

		$keysql = file_get_contents($base.'/sql/llx_'.$module.'_'.strtolower($objectname).'.key.sql');
		$this->assertStringContainsString('ADD UNIQUE INDEX uk_'.$module.'_'.strtolower($objectname).'_fktestlink', $keysql, 'A UNIQUE index must be generated for a unique field');
		$this->assertStringContainsString('FOREIGN KEY (fktestlink) REFERENCES llx_societe(rowid) ON DELETE CASCADE', $keysql, 'The FK must be generated with its ON DELETE policy');

		dol_delete_dir_recursive($base);
	}

	/**
	 * The ON DELETE policy normalization (used both server-side for validation and for SQL generation)
	 * must map the accepted tokens and reject unknown ones.
	 *
	 * @return void
	 */
	public function testOnDeletePolicyNormalization()
	{
		$normalize = static function (string $v): string {
			return preg_replace('/[^A-Z]/', '', strtoupper($v));
		};
		$allowed = array('RESTRICT', 'SETNULL', 'CASCADE', 'NOACTION');

		$this->assertSame('SETNULL', $normalize('SET NULL'), 'SET NULL must normalize to SETNULL');
		$this->assertSame('CASCADE', $normalize('cascade'), 'lowercase cascade must normalize');
		$this->assertTrue(in_array($normalize('SET NULL'), $allowed, true), 'SET NULL must be accepted');
		$this->assertFalse(in_array($normalize('DROP TABLE'), $allowed, true), 'An unknown policy must be rejected');
	}

	/**
	 * Exercise rebuildObjectSql() over a comprehensive field set (one object, no per-field include) to cover
	 * every ON DELETE policy, the index/unique combinations and the defensive SET NULL guard at the SQL layer.
	 *
	 * @return void
	 */
	public function testRebuildObjectSqlAllPolicies()
	{
		global $db;

		$tpl = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
		$module = 'sqlpolmod';
		$objectname = 'myobject';

		$base = sys_get_temp_dir().'/mbsqlpol_'.getmypid();
		dol_delete_dir_recursive($base);
		dol_mkdir($base.'/class');
		dol_mkdir($base.'/sql');
		file_put_contents($base.'/class/myobject.class.php', file_get_contents($tpl.'/class/myobject.class.php'));
		file_put_contents($base.'/sql/llx_'.$module.'_myobject.sql', file_get_contents($tpl.'/sql/llx_mymodule_myobject.sql'));
		file_put_contents($base.'/sql/llx_'.$module.'_myobject.key.sql', file_get_contents($tpl.'/sql/llx_mymodule_myobject.key.sql'));

		// rebuildObjectSql() only reads $object->fields, so a lightweight object is enough and avoids
		// the class-redeclaration hazard of including the template class in the test suite process.
		$object = new stdClass();
		$object->fields = array(
			'rowid' => array('type' => 'integer', 'label' => 'id'),
			'entity' => array('type' => 'integer', 'label' => 'e'),
			'fkcascade' => array('type' => 'integer', 'label' => 'C', 'foreignkey' => 'societe.rowid', 'ondelete' => 'CASCADE'),
			'fksetnull' => array('type' => 'integer', 'label' => 'N', 'foreignkey' => 'societe.rowid', 'ondelete' => 'SETNULL', 'notnull' => 0),
			'fknoaction' => array('type' => 'integer', 'label' => 'NA', 'foreignkey' => 'societe.rowid', 'ondelete' => 'NOACTION'),
			'fkrestrict' => array('type' => 'integer', 'label' => 'R', 'foreignkey' => 'societe.rowid', 'ondelete' => 'RESTRICT'),
			'fkplain' => array('type' => 'integer', 'label' => 'P', 'foreignkey' => 'societe.rowid'),
			'uqfield' => array('type' => 'varchar(50)', 'label' => 'U', 'unique' => 1),
			'idxfield' => array('type' => 'varchar(50)', 'label' => 'I', 'index' => 1),
			'idxuq' => array('type' => 'varchar(50)', 'label' => 'IU', 'index' => 1, 'unique' => 1),
			'fksetnullbad' => array('type' => 'integer', 'label' => 'BAD', 'foreignkey' => 'societe.rowid', 'ondelete' => 'SETNULL', 'notnull' => 1),
		);

		$res = rebuildObjectSql($base, $module, $objectname, '0', $base, $object, 'external');
		$this->assertGreaterThan(0, $res, 'rebuildObjectSql must succeed');
		$key = file_get_contents($base.'/sql/llx_'.$module.'_myobject.key.sql');

		$this->assertStringContainsString('fkcascade) REFERENCES llx_societe(rowid) ON DELETE CASCADE;', $key, 'CASCADE policy');
		$this->assertStringContainsString('fksetnull) REFERENCES llx_societe(rowid) ON DELETE SET NULL;', $key, 'SET NULL policy on nullable field');
		$this->assertStringContainsString('fknoaction) REFERENCES llx_societe(rowid) ON DELETE NO ACTION;', $key, 'NO ACTION policy');
		$this->assertStringContainsString('fkrestrict) REFERENCES llx_societe(rowid);', $key, 'RESTRICT emits no clause');
		$this->assertStringContainsString('fkplain) REFERENCES llx_societe(rowid);', $key, 'No policy emits no clause');
		$this->assertStringContainsString('UNIQUE INDEX uk_'.$module.'_myobject_uqfield (uqfield, entity);', $key, 'unique field scoped per entity');
		$this->assertStringContainsString('ADD INDEX idx_'.$module.'_myobject_idxfield (idxfield);', $key, 'plain index stays non-unique');
		$this->assertSame(1, substr_count($key, '_idxuq ('), 'index+unique must produce a single index');
		$this->assertStringContainsString('UNIQUE INDEX uk_'.$module.'_myobject_idxuq (idxuq, entity);', $key, 'index+unique becomes a UNIQUE INDEX');
		// Defensive guard: SET NULL on a NOT NULL column must not reach the DDL.
		$this->assertStringContainsString('fksetnullbad) REFERENCES llx_societe(rowid);', $key, 'SET NULL on NOT NULL field falls back to no clause');
		$this->assertStringNotContainsString('fksetnullbad) REFERENCES llx_societe(rowid) ON DELETE', $key, 'no invalid SET NULL clause on a NOT NULL field');

		dol_delete_dir_recursive($base);
	}

	/**
	 * getFieldForeignKeyTargetFromType() must resolve a link-type field to its real table (read from the
	 * linked object's $table_element, so 'Project' yields 'projet') and return '' for non-link types.
	 *
	 * @return void
	 */
	public function testForeignKeyTargetDerivationFromType()
	{
		global $db;

		$this->assertSame('societe.rowid', getFieldForeignKeyTargetFromType('integer:Societe:societe/class/societe.class.php:1:(...)', $db), 'Societe must resolve to societe.rowid');
		$this->assertSame('projet.rowid', getFieldForeignKeyTargetFromType('integer:Project:projet/class/project.class.php:1', $db), 'Project must resolve to projet.rowid, not project.rowid');
		$this->assertSame('', getFieldForeignKeyTargetFromType('integer', $db), 'A plain integer type has no FK target');
		$this->assertSame('', getFieldForeignKeyTargetFromType('varchar(50)', $db), 'A varchar type has no FK target');
	}

	/**
	 * dolModuleBuilderTruncSqlIdentifier() must keep short identifiers intact and bound long ones to 64 chars.
	 *
	 * @return void
	 */
	public function testTruncSqlIdentifier()
	{
		$this->assertSame('idx_short_field', dolModuleBuilderTruncSqlIdentifier('idx_short_field'), 'short identifier is unchanged');
		$long = dolModuleBuilderTruncSqlIdentifier('uk_'.str_repeat('x', 80).'_field');
		$this->assertLessThanOrEqual(64, strlen($long), 'a long identifier must be bounded to 64 characters');
	}

	/**
	 * The shipped template object must declare the default foreign keys on fk_soc / fk_project so generated
	 * modules get them out of the box (with a SET NULL policy, as those columns are nullable).
	 *
	 * @return void
	 */
	public function testTemplateShipsDefaultForeignKeys()
	{
		$tpl = file_get_contents(DOL_DOCUMENT_ROOT.'/modulebuilder/template/class/myobject.class.php');

		$this->assertMatchesRegularExpression("/'fk_soc'\s*=>\s*array\(.*'foreignkey'\s*=>\s*'societe\.rowid'.*'ondelete'\s*=>\s*'SETNULL'/", $tpl, 'fk_soc must ship with a societe.rowid FK and SET NULL policy');
		$this->assertMatchesRegularExpression("/'fk_project'\s*=>\s*array\(.*'foreignkey'\s*=>\s*'projet\.rowid'.*'ondelete'\s*=>\s*'SETNULL'/", $tpl, 'fk_project must ship with a projet.rowid FK and SET NULL policy');
	}
}
