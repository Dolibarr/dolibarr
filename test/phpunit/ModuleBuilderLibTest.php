<?php
/* Copyright (C) 2026 ATM Consulting <contact@atm-consulting.fr>
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
 *      \file       test/phpunit/ModuleBuilderLibTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for modulebuilder.lib.php tab and card action selection helpers
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/modulebuilder.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredMethod
 * @phan-file-suppress PhanTypeMismatchArgumentProbablyReal
 */
class ModuleBuilderLibTest extends CommonClassTest
{
	/** @var string[] Generated lib fixtures to remove */
	private $libFixtures = array();

	/**
	 * testGetModuleBuilderObjectTabs
	 *
	 * @return void
	 */
	public function testGetModuleBuilderObjectTabs()
	{
		$map = getModuleBuilderObjectTabs();
		$this->assertSame(array('contact', 'note', 'document', 'agenda'), array_keys($map));
		$this->assertSame('myobject_contact.php', $map['contact']['file']);
		$this->assertSame('showtabofpageagenda', $map['agenda']['var']);
		$this->assertSame('DOCUMENT', $map['document']['marker']);
	}

	/**
	 * testFilterEnabledTabs
	 *
	 * @return void
	 */
	public function testFilterEnabledTabs()
	{
		$map = getModuleBuilderObjectTabs();

		// Nominal: returns requested keys in map order
		$this->assertSame(array('contact', 'agenda'), filterEnabledTabs(array('agenda', 'contact'), $map));

		// Unknown key is rejected
		$this->assertSame(array('contact'), filterEnabledTabs(array('contact', 'evil'), $map));

		// Empty / non-array returns empty
		$this->assertSame(array(), filterEnabledTabs(array(), $map));
		$this->assertSame(array(), filterEnabledTabs('', $map));

		// Duplicates collapsed
		$this->assertSame(array('note'), filterEnabledTabs(array('note', 'note'), $map));

		// Wrapper must stay strictly equivalent to the generic filter it delegates to
		$this->assertSame(filterEnabledKeys(array('agenda', 'contact'), $map), filterEnabledTabs(array('agenda', 'contact'), $map));
	}

	/**
	 * testGetModuleBuilderObjectCardActions
	 *
	 * @return void
	 */
	public function testGetModuleBuilderObjectCardActions()
	{
		$map = getModuleBuilderObjectCardActions();
		$this->assertSame(array('sendmail', 'clone', 'validate', 'setdraft', 'statuschange', 'delete'), array_keys($map));

		$markers = array();
		foreach ($map as $actionkey => $meta) {
			foreach (array('marker', 'label', 'default', 'mode') as $index) {
				$this->assertArrayHasKey($index, $meta, 'Missing index '.$index.' for card action '.$actionkey);
			}
			// The marker is injected into a regular expression built in modulebuilder/index.php
			$this->assertMatchesRegularExpression('/^[A-Z]+$/', $meta['marker'], 'Invalid marker for card action '.$actionkey);
			$this->assertContains($meta['mode'], array('remove', 'activate'), 'Invalid mode for card action '.$actionkey);
			$markers[] = $meta['marker'];
		}

		// Two actions sharing a marker would purge each other blocks
		$this->assertSame($markers, array_unique($markers));

		// Cancel / Re-Open ships commented out in the template: it must stay off by default
		$this->assertSame(0, $map['statuschange']['default']);
		$this->assertSame('activate', $map['statuschange']['mode']);
	}

	/**
	 * testGetModuleBuilderDefaultEnabledKeys
	 *
	 * @return void
	 */
	public function testGetModuleBuilderDefaultEnabledKeys()
	{
		// Default selection must reproduce the card page generated before this option existed
		$this->assertSame(array('sendmail', 'clone', 'validate', 'setdraft', 'delete'), getModuleBuilderDefaultEnabledKeys(getModuleBuilderObjectCardActions()));

		// All tabs are enabled by default
		$this->assertSame(array_keys(getModuleBuilderObjectTabs()), getModuleBuilderDefaultEnabledKeys(getModuleBuilderObjectTabs()));
	}

	/**
	 * testFilterEnabledKeys
	 *
	 * @return void
	 */
	public function testFilterEnabledKeys()
	{
		$map = getModuleBuilderObjectCardActions();

		// Nominal: returns requested keys in map order
		$this->assertSame(array('clone', 'delete'), filterEnabledKeys(array('delete', 'clone'), $map));

		// Unknown key is rejected
		$this->assertSame(array('delete'), filterEnabledKeys(array('delete', 'evil'), $map));

		// Empty / non-array returns empty
		$this->assertSame(array(), filterEnabledKeys(array(), $map));
		$this->assertSame(array(), filterEnabledKeys('', $map));

		// Duplicates collapsed
		$this->assertSame(array('validate'), filterEnabledKeys(array('validate', 'validate'), $map));
	}

	/**
	 * Generated lib of an object created with some tabs unselected, as initobject leaves it.
	 *
	 * @param string[] $unselected Tab keys unselected at the object creation
	 * @return string Path to the generated lib
	 */
	private function makeObjectLib(array $unselected): string
	{
		$content = (string) file_get_contents(DOL_DOCUMENT_ROOT.'/modulebuilder/template/lib/mymodule_myobject.lib.php');
		foreach (getModuleBuilderObjectTabs() as $key => $tab) {
			if (in_array($key, $unselected, true)) {
				$content = (string) preg_replace('/\h*\/\/ BEGIN MODULEBUILDER TABFLAG '.$tab['marker'].'.*?\/\/ END MODULEBUILDER TABFLAG '.$tab['marker'].'\s*/s', '', $content);
				$content = (string) preg_replace('/\h*\/\/ BEGIN MODULEBUILDER TAB '.$tab['marker'].'.*?\/\/ END MODULEBUILDER TAB '.$tab['marker'].'\s*/s', '', $content);
			} else {
				$content = (string) preg_replace('/\$'.$tab['var'].' = getDolGlobalInt\([^;]*\);/', '$'.$tab['var'].' = 1;', $content);
			}
		}
		$path = sys_get_temp_dir().'/mblib'.uniqid().'.lib.php';
		file_put_contents($path, (new NamingContract('Rtest', 'Roauto'))->applyTo($content));
		$this->libFixtures[] = $path;

		return $path;
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		foreach ($this->libFixtures as $path) {
			if (file_exists($path)) {
				unlink($path);
			}
		}
		$this->libFixtures = array();
		parent::tearDown();
	}

	/**
	 * @param string $content PHP content
	 * @return void
	 */
	private function assertParses(string $content): void
	{
		try {
			token_get_all($content, TOKEN_PARSE);
		} catch (ParseError $e) {
			$this->fail('Lib does not parse: '.$e->getMessage());
		}
		$this->addToAssertionCount(1);
	}

	/**
	 * A page generated later with the Generate icon gets back the tab removed at the object creation, at its place.
	 *
	 * @return void
	 */
	public function testRestoreObjectTabPutsBackFlagAndTab()
	{
		$lib = $this->makeObjectLib(array('note'));

		$this->assertSame(1, modulebuilderRestoreObjectTab($lib, 'note', new NamingContract('Rtest', 'Roauto')));

		$content = (string) file_get_contents($lib);
		$this->assertStringContainsString('$showtabofpagenote = 1;', $content);
		$this->assertStringContainsString('/rtest/roauto_note.php', $content);
		$this->assertStringNotContainsString('mymodule', $content);
		$flag = strpos($content, '$showtabofpagenote = 1;');
		$this->assertGreaterThan(strpos($content, '$showtabofpagecontact = 1;'), $flag);
		$this->assertLessThan(strpos($content, '$showtabofpagedocument = 1;'), $flag);
		$tab = strpos($content, 'if ($showtabofpagenote) {');
		$this->assertGreaterThan(strpos($content, 'if ($showtabofpagecontact) {'), $tab);
		$this->assertLessThan(strpos($content, 'if ($showtabofpagedocument) {'), $tab);
		$this->assertParses($content);

		// Already declared: nothing changes
		$this->assertSame(1, modulebuilderRestoreObjectTab($lib, 'note', new NamingContract('Rtest', 'Roauto')));
		$this->assertSame($content, (string) file_get_contents($lib));
	}

	/**
	 * Without the previous tab, the restored tab still goes before the next one.
	 *
	 * @return void
	 */
	public function testRestoreObjectTabBeforeTheNextTab()
	{
		$lib = $this->makeObjectLib(array('contact', 'note'));

		$this->assertSame(1, modulebuilderRestoreObjectTab($lib, 'note', new NamingContract('Rtest', 'Roauto')));

		$content = (string) file_get_contents($lib);
		$this->assertLessThan(strpos($content, '$showtabofpagedocument = 1;'), strpos($content, '$showtabofpagenote = 1;'));
		$this->assertLessThan(strpos($content, 'if ($showtabofpagedocument) {'), strpos($content, 'if ($showtabofpagenote) {'));
		$this->assertParses($content);
	}

	/**
	 * @return void
	 */
	public function testRestoreObjectTabWhenEveryTabWasRemoved()
	{
		$lib = $this->makeObjectLib(array('contact', 'note', 'document', 'agenda'));

		$this->assertSame(1, modulebuilderRestoreObjectTab($lib, 'agenda', new NamingContract('Rtest', 'Roauto')));

		$content = (string) file_get_contents($lib);
		$this->assertLessThan(strpos($content, '$h = 0;'), strpos($content, '$showtabofpageagenda = 1;'));
		$this->assertGreaterThan(strpos($content, '$h = 0;'), strpos($content, 'if ($showtabofpageagenda) {'));
		$this->assertLessThan(strpos($content, '// Show more tabs from modules'), strpos($content, 'if ($showtabofpageagenda) {'));
		$this->assertParses($content);
	}

	/**
	 * @return void
	 */
	public function testRestoreObjectTabLeavesAnUnknownLibUntouched()
	{
		$lib = sys_get_temp_dir().'/mblib'.uniqid().'.lib.php';
		file_put_contents($lib, "<?php\nfunction roautoPrepareHead(\$object)\n{\n\treturn array();\n}\n");
		$this->libFixtures[] = $lib;
		$before = (string) file_get_contents($lib);

		$this->assertSame(0, modulebuilderRestoreObjectTab($lib, 'note', new NamingContract('Rtest', 'Roauto')));
		$this->assertSame($before, (string) file_get_contents($lib));
		$this->assertSame(-1, modulebuilderRestoreObjectTab($lib, 'unknown', new NamingContract('Rtest', 'Roauto')));
		$this->assertSame(-1, modulebuilderRestoreObjectTab($lib.'.missing', 'note', new NamingContract('Rtest', 'Roauto')));
	}
}
