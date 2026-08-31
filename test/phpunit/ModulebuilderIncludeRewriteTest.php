<?php
/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
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
 *      \file       test/phpunit/ModulebuilderIncludeRewriteTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for ModuleBuilder include rewrite (dol_include_once -> __DIR__)
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/modulebuilder/class/TemplateMutationReport.class.php';
require_once dirname(__FILE__).'/../../htdocs/modulebuilder/class/IncludeRewritePolicy.class.php';
require_once dirname(__FILE__).'/../../htdocs/modulebuilder/class/ModuleRootIncludePathResolver.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/modulebuilder.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class ModulebuilderIncludeRewriteTest extends CommonClassTest
{
	/**
	 * testTemplateMutationReport
	 *
	 * @return void
	 */
	public function testTemplateMutationReport()
	{
		$report = new TemplateMutationReport('/x/mymodule/myobject_card.php');
		$this->assertFalse($report->hasChanges());
		$report->addReplacement(96, "dol_include_once('/mymodule/class/myobject.class.php');", "include_once __DIR__.'/class/myobject.class.php';");
		$report->addSkipped(97, 'cross-module');
		$report->addWarning('example warning');
		$this->assertTrue($report->hasChanges());
		$this->assertCount(1, $report->replacements);
		$this->assertSame('cross-module', $report->skipped[0]['reason']);
		$this->assertCount(1, $report->warnings);
	}

	/**
	 * testIncludeRewritePolicy
	 *
	 * @return void
	 */
	public function testIncludeRewritePolicy()
	{
		$p = new IncludeRewritePolicy();

		$this->assertTrue($p->isExcluded('core/lib/modulebuilder.lib.php'));
		$this->assertTrue($p->isExcluded('modulebuilder/index.php'));
		$this->assertTrue($p->isApiFile('class/api_mymodule.class.php', 'mymodule'));
		$this->assertFalse($p->isApiFile('class/myobject.class.php', 'mymodule'));

		$this->assertTrue($p->shouldProcess('myobject_card.php', 'mymodule'));
		$this->assertTrue($p->shouldProcess('stats/myobject_index.php', 'mymodule'));
		$this->assertFalse($p->shouldProcess('class/api_mymodule.class.php', 'mymodule'));
		$this->assertFalse($p->shouldProcess('core/lib/modulebuilder.lib.php', 'mymodule'));
		$this->assertFalse($p->shouldProcess('sql/data.sql', 'mymodule'));
	}

	/**
	 * testResolveFromFileDirDepths
	 *
	 * @return void
	 */
	public function testResolveFromFileDirDepths()
	{
		$r = new ModuleRootIncludePathResolver('/x/mymodule');

		// racine -> class/ (depth 0)
		$this->assertSame('class/myobject.class.php', $r->resolveFromFileDir('/x/mymodule/myobject_card.php', 'class/myobject.class.php'));
		// stats/ -> class/ (depth 1)
		$this->assertSame('../class/myobject.class.php', $r->resolveFromFileDir('/x/mymodule/stats/myobject_index.php', 'class/myobject.class.php'));
		// core/modules/mailings/ -> class/ (depth 3)
		$this->assertSame('../../../class/myobject.class.php', $r->resolveFromFileDir('/x/mymodule/core/modules/mailings/mailing_mymodule_selector1.modules.php', 'class/myobject.class.php'));
		// core/modules/mymodule/doc/ -> core/modules/mymodule/ (direct parent, must stay ../)
		$this->assertSame('../modules_myobject.php', $r->resolveFromFileDir('/x/mymodule/core/modules/mymodule/doc/pdf_standard_myobject.modules.php', 'core/modules/mymodule/modules_myobject.php'));
		// same directory
		$this->assertSame('modules_myobject.php', $r->resolveFromFileDir('/x/mymodule/core/modules/mymodule/mod_myobject_standard.php', 'core/modules/mymodule/modules_myobject.php'));
		$this->assertSame('myobject.class.php', $r->resolveFromFileDir('/x/mymodule/class/myobjectstats.class.php', 'class/myobject.class.php'));
		// abs from module root
		$this->assertSame('/x/mymodule/class/myobject.class.php', $r->resolveAbsoluteFromModuleRoot('class/myobject.class.php'));
	}

	/**
	 * testRewriteIncludeStatements
	 *
	 * @return void
	 */
	public function testRewriteIncludeStatements()
	{
		$policy = new IncludeRewritePolicy();
		$resolver = new ModuleRootIncludePathResolver('/x/mymodule');
		$file = '/x/mymodule/myobject_card.php';

		// Fake generated file: single/double-quote own-module, core, commented cross-module, dynamic, active cross-module.
		$content = implode("\n", array(
			'<?php',
			"dol_include_once('/mymodule/class/myobject.class.php');",
			'dol_include_once("/mymodule/class/myobject.class.php");',
			"include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';",
			"//dol_include_once('/othermodule/class/otherobject.class.php');",
			'dol_include_once(\'/\'.$moduledir.\'/class/\'.strtolower($k).\'.class.php\');',
			"dol_include_once('/othermodule/class/otherobject.class.php');",
		));

		$report = rewriteIncludeStatements($content, $file, 'mymodule', $policy, $resolver);

		// 2 rewrites (the 2 static own-module includes, single + double quote)
		$this->assertCount(2, $report->replacements);
		$this->assertSame("include_once __DIR__.'/class/myobject.class.php';", $report->replacements[0]['to']);

		$reasons = array_column($report->skipped, 'reason');
		$this->assertContains('commented', $reasons);
		$this->assertContains('dynamic-or-non-literal', $reasons);
		$this->assertContains('cross-module', $reasons);
	}

	/**
	 * testRewriteIsIdempotent
	 *
	 * @return void
	 */
	public function testRewriteIsIdempotent()
	{
		$policy = new IncludeRewritePolicy();
		$resolver = new ModuleRootIncludePathResolver('/x/mymodule');
		$file = '/x/mymodule/stats/myobject_index.php';

		$content = "<?php\ndol_include_once('/mymodule/class/myobject.class.php');\n";
		$report = rewriteIncludeStatements($content, $file, 'mymodule', $policy, $resolver);
		$this->assertSame("include_once __DIR__.'/../class/myobject.class.php';", $report->replacements[0]['to']);

		$lines = explode("\n", $content);
		foreach ($report->replacements as $rp) {
			$lines[$rp['line'] - 1] = str_replace($rp['from'], $rp['to'], $lines[$rp['line'] - 1]);
		}
		$newContent = implode("\n", $lines);

		$report2 = rewriteIncludeStatements($newContent, $file, 'mymodule', $policy, $resolver);
		$this->assertCount(0, $report2->replacements);
	}

	/**
	 * testRewriteGeneratedIncludesOnTempModule
	 *
	 * @return void
	 */
	public function testRewriteGeneratedIncludesOnTempModule()
	{
		$root = sys_get_temp_dir().'/mbil_'.getmypid().'/mymodule';
		dol_mkdir($root.'/class');
		dol_mkdir($root.'/stats');

		file_put_contents($root.'/myobject_card.php', "<?php\ndol_include_once('/mymodule/class/myobject.class.php');\n");
		file_put_contents($root.'/stats/myobject_index.php', "<?php\ndol_include_once('/mymodule/class/myobject.class.php');\n");
		file_put_contents($root.'/class/myobject.class.php', "<?php\nclass MyObject\n{\n}\n");
		file_put_contents($root.'/class/api_mymodule.class.php', "<?php\ndol_include_once('/mymodule/class/myobject.class.php');\n");

		$reports = rewriteGeneratedIncludes($root, 'mymodule');
		$this->assertNotEmpty($reports);

		$card = file_get_contents($root.'/myobject_card.php');
		$this->assertStringContainsString("include_once __DIR__.'/class/myobject.class.php';", $card);
		$this->assertStringNotContainsString("dol_include_once('/mymodule/", $card);

		$stats = file_get_contents($root.'/stats/myobject_index.php');
		$this->assertStringContainsString("include_once __DIR__.'/../class/myobject.class.php';", $stats);

		// API file preserved (A2)
		$api = file_get_contents($root.'/class/api_mymodule.class.php');
		$this->assertStringContainsString("dol_include_once('/mymodule/class/myobject.class.php');", $api);

		dol_delete_dir_recursive(dirname($root));
	}

	/**
	 * testRewriteGeneratedIncludesFeatureFlag
	 *
	 * @return void
	 */
	public function testRewriteGeneratedIncludesFeatureFlag()
	{
		global $conf;

		$root = sys_get_temp_dir().'/mbilflag_'.getmypid().'/mymodule';
		dol_mkdir($root);
		file_put_contents($root.'/myobject_card.php', "<?php\ndol_include_once('/mymodule/class/myobject.class.php');\n");

		$conf->global->MODULEBUILDER_DISABLE_INCLUDE_REWRITE = '1';
		$reports = rewriteGeneratedIncludes($root, 'mymodule');
		unset($conf->global->MODULEBUILDER_DISABLE_INCLUDE_REWRITE);

		$this->assertSame(array(), $reports);
		$this->assertStringContainsString("dol_include_once('/mymodule/", file_get_contents($root.'/myobject_card.php'));

		dol_delete_dir_recursive(dirname($root));
	}

	/**
	 * testTemplatesMigratedToDir
	 *
	 * @return void
	 */
	public function testTemplatesMigratedToDir()
	{
		$dir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
		$files = array(
			'myobject_card.php', 'myobject_contact.php', 'myobject_note.php',
			'myobject_document.php', 'myobject_agenda.php',
			'ajax/myobject.php', 'stats/myobject_index.php', 'admin/about.php',
			'class/myobjectstats.class.php',
			'core/modules/mymodule/mod_myobject_standard.php',
			'core/modules/mymodule/mod_myobject_advanced.php',
			'core/modules/mymodule/doc/doc_generic_myobject_odt.modules.php',
			'core/modules/mymodule/doc/pdf_standard_myobject.modules.php',
			'core/modules/mailings/mailing_mymodule_selector1.modules.php',
		);
		foreach ($files as $rel) {
			$content = file_get_contents($dir.'/'.$rel);
			$this->assertNotFalse($content, "Missing template ".$rel);
			foreach (explode("\n", $content) as $line) {
				if (preg_match('/^\s*(\/\/|\*|\/\*)/', $line)) {
					continue; // commented line allowed
				}
				$this->assertSame(0, preg_match('#dol_include_once\(\s*[\'"]/mymodule/#', $line), "Active own-module dol_include_once remains in ".$rel.": ".$line);
			}
		}
		// API file: dol_include_once kept by design (A2)
		$api = file_get_contents($dir.'/class/api_mymodule.class.php');
		$this->assertSame(1, preg_match('#dol_include_once\(\s*[\'"]/mymodule/#', $api));
	}
}
