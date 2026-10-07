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
 *      \file       test/phpunit/ModuleBuilderDocGenerationTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the ModuleBuilder document generation opt-out
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/modulebuilder.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class ModuleBuilderDocGenerationTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredMethod
 */
class ModuleBuilderDocGenerationTest extends CommonClassTest
{
	const TEMPLATE_DIR = __DIR__.'/../../htdocs/modulebuilder/template';

	const DOC_TEMPLATES = array(
		'class/myobject.class.php',
		'myobject_card.php',
		'myobject_list.php',
	);

	const DESCRIPTOR_TPL = __DIR__.'/../../htdocs/modulebuilder/template/core/modules/modMyModule.class.php';

	/**
	 * @var string Scratch directory for template copies
	 */
	private $tmpDir = '';

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		if ($this->tmpDir !== '' && is_dir($this->tmpDir)) {
			dol_delete_dir_recursive($this->tmpDir);
		}
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testModeFromFlag(): void
	{
		$this->assertSame(DocumentGenerationMode::ENABLED, DocumentGenerationMode::fromFlag(true));
		$this->assertSame(DocumentGenerationMode::DISABLED, DocumentGenerationMode::fromFlag(false));
		$this->assertTrue(DocumentGenerationMode::isEnabled(DocumentGenerationMode::ENABLED));
		$this->assertFalse(DocumentGenerationMode::isEnabled(DocumentGenerationMode::DISABLED));
	}

	/**
	 * @return void
	 */
	public function testUnknownModeIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		DocumentGenerationMode::isEnabled('maybe');
	}

	/**
	 * @return void
	 */
	public function testFileMapCreatesOrDeletesExclusively(): void
	{
		$map = new DocumentFileMap(new NamingContract('TestMod', 'Thing'));

		$expected = array(
			'core/modules/mymodule/doc/doc_generic_myobject_odt.modules.php' => 'core/modules/testmod/doc/doc_generic_thing_odt.modules.php',
			'core/modules/mymodule/doc/pdf_standard_myobject.modules.php' => 'core/modules/testmod/doc/pdf_standard_thing.modules.php',
		);
		$this->assertSame($expected, $map->getAll());
		$this->assertSame($expected, $map->getFilesToCreate(DocumentGenerationMode::ENABLED));
		$this->assertSame(array(), $map->getFilesToDelete(DocumentGenerationMode::ENABLED));
		$this->assertSame(array(), $map->getFilesToCreate(DocumentGenerationMode::DISABLED));
		$this->assertSame($expected, $map->getFilesToDelete(DocumentGenerationMode::DISABLED));
	}

	/**
	 * @return void
	 */
	public function testFileMapTemplatesExist(): void
	{
		foreach (DocumentFileMap::TEMPLATE_FILES as $templateFile) {
			$this->assertFileExists(self::TEMPLATE_DIR.'/'.$templateFile);
		}
	}

	/**
	 * Non greedy block patterns need balanced, non nested markers.
	 *
	 * @return void
	 */
	public function testDocGenerationMarkersAreBalancedAndNotNested(): void
	{
		foreach (self::DOC_TEMPLATES as $templateFile) {
			$content = file_get_contents(self::TEMPLATE_DIR.'/'.$templateFile);
			$nbBegin = preg_match_all('/\/\/ BEGIN MODULEBUILDER DOCGENERATION/', $content);
			$nbEnd = preg_match_all('/\/\/ END MODULEBUILDER DOCGENERATION/', $content);
			$this->assertGreaterThan(0, $nbBegin, $templateFile.' has no DOCGENERATION block');
			$this->assertSame($nbBegin, $nbEnd, $templateFile.' has unbalanced DOCGENERATION markers');

			$matches = array();
			preg_match_all('/\/\/ BEGIN MODULEBUILDER DOCGENERATION.*?\/\/ END MODULEBUILDER DOCGENERATION/s', $content, $matches);
			foreach ($matches[0] as $block) {
				$this->assertStringNotContainsString('BEGIN MODULEBUILDER DOCGENERATION', substr($block, strlen('// BEGIN MODULEBUILDER DOCGENERATION')), $templateFile.' has nested DOCGENERATION markers');
				$this->assertDoesNotMatchRegularExpression('/MODULEBUILDER (LINES|ACTION )/', $block, $templateFile.' mixes DOCGENERATION with another block');
			}
		}
	}

	/**
	 * @return void
	 */
	public function testDisabledModeStripsEveryDocReference(): void
	{
		foreach ($this->copyTemplates() as $templateFile => $copy) {
			$this->assertTrue(removePatternFromFile($copy, getModuleBuilderDocGenerationBlockPattern()));
			$content = file_get_contents($copy);

			foreach (array('model_pdf', 'actions_builddoc', 'showdocuments', 'ADDON_PDF', 'DOCGENERATION', 'includedocgeneration', 'FormFile') as $forbidden) {
				$this->assertStringNotContainsString($forbidden, $content, $templateFile.' still contains '.$forbidden);
			}
			$this->assertPhpLintOk($copy);
		}

		$this->assertStringNotContainsString('$upload_dir', file_get_contents($this->tmpDir.'/myobject_card.php'));

		$classContent = file_get_contents($this->tmpDir.'/class/myobject.class.php');
		$this->assertStringContainsString('public function generateDocument(', $classContent, 'generateDocument() is called by mass actions without method_exists()');
	}

	/**
	 * @return void
	 */
	public function testEnabledModeKeepsCodeAndDropsMarkers(): void
	{
		foreach ($this->copyTemplates() as $templateFile => $copy) {
			$this->assertTrue(removePatternFromFile($copy, getModuleBuilderDocGenerationMarkerPattern()));
			$content = file_get_contents($copy);

			$this->assertStringNotContainsString('DOCGENERATION', $content, $templateFile);
			$this->assertStringNotContainsString('includedocgeneration', $content, $templateFile);
			$this->assertPhpLintOk($copy);
		}

		$this->assertStringContainsString("'model_pdf' =>", file_get_contents($this->tmpDir.'/class/myobject.class.php'));
		$this->assertStringContainsString('commonGenerateDocument(', file_get_contents($this->tmpDir.'/class/myobject.class.php'));
		$cardContent = file_get_contents($this->tmpDir.'/myobject_card.php');
		$this->assertStringContainsString('actions_builddoc.inc.php', $cardContent);
		$this->assertStringContainsString('showdocuments(', $cardContent);
	}

	/**
	 * FormFile::showdocuments() resolves the ModelePDF<Object> class from a "module:Object" modulepart.
	 *
	 * @return void
	 */
	public function testCardShowsDocumentsWithModuleColonObjectModulepart(): void
	{
		$content = file_get_contents(self::TEMPLATE_DIR.'/myobject_card.php');
		$this->assertStringContainsString("\$formfile->showdocuments('mymodule:MyObject', \$object->element.'/'.\$objref,", $content);
		$this->assertStringContainsString("\$upload_dir = \$conf->mymodule->multidir_output[isset(\$object->entity) ? \$object->entity : 1];", $content, 'remove_file resolves $upload_dir/<element>/<ref>/<file>');
	}

	/**
	 * @return void
	 */
	public function testDescriptorEntryIsInsertedThenUpdatedIdempotently(): void
	{
		$content = $this->getDescriptorZone();

		$content = setModuleBuilderDescriptorDocModelEntry($content, 'Thing', 0, 1);
		$content = setModuleBuilderDescriptorDocModelEntry($content, 'Thing', 0, 1);
		$this->assertSame(1, substr_count($content, "\$myTmpObjects['Thing']"));
		$this->assertStringContainsString("\$myTmpObjects['Thing'] = array('includerefgeneration' => 0, 'includedocgeneration' => 1);", $content);

		$content = setModuleBuilderDescriptorDocModelEntry($content, 'Thing', 1, 0);
		$this->assertSame(1, substr_count($content, "\$myTmpObjects['Thing']"));
		$this->assertStringContainsString("\$myTmpObjects['Thing'] = array('includerefgeneration' => 1, 'includedocgeneration' => 0);", $content);
		$this->assertMatchesRegularExpression('/BEGIN MODULEBUILDER DOCUMENT MODELS \*\/.*myTmpObjects\[\'Thing\'\].*\/\* END MODULEBUILDER DOCUMENT MODELS/s', $content);
	}

	/**
	 * @return void
	 */
	public function testDescriptorEntriesOfSeveralObjectsCoexistAndAreRemovedOneByOne(): void
	{
		$content = $this->getDescriptorZone();
		$content = setModuleBuilderDescriptorDocModelEntry($content, 'Thing', 0, 1);
		$content = setModuleBuilderDescriptorDocModelEntry($content, 'Other', 1, 1);
		$this->assertStringContainsString("\$myTmpObjects['Thing']", $content);
		$this->assertStringContainsString("\$myTmpObjects['Other']", $content);

		$content = removeModuleBuilderDescriptorDocModelEntry($content, 'thing');
		$this->assertStringNotContainsString("\$myTmpObjects['Thing']", $content);
		$this->assertStringContainsString("\$myTmpObjects['Other'] = array('includerefgeneration' => 1, 'includedocgeneration' => 1);", $content);
		$this->assertSame($this->getDescriptorZone(), removeModuleBuilderDescriptorDocModelEntry($content, 'Other'));
	}

	/**
	 * @return void
	 */
	public function testDescriptorWithoutZoneIsLeftUntouched(): void
	{
		$content = "<?php\n\$myTmpObjects = array();\n";
		$this->assertSame($content, setModuleBuilderDescriptorDocModelEntry($content, 'Thing', 0, 1));
	}

	/**
	 * Document models must only be registered for objects that generate documents.
	 *
	 * @return void
	 */
	public function testDescriptorTemplateRegistersModelsOnDocGenerationOnly(): void
	{
		$content = file_get_contents(self::DESCRIPTOR_TPL);
		$this->assertStringContainsString('/* BEGIN MODULEBUILDER DOCUMENT MODELS */', $content);
		$this->assertStringContainsString('/* END MODULEBUILDER DOCUMENT MODELS */', $content);
		$this->assertStringNotContainsString("\$myTmpObjects['MyObject'] =", $content);
		$this->assertStringContainsString("if (\$myTmpObjectArray['includedocgeneration']) {", $content);
		$this->assertStringNotContainsString("if (\$myTmpObjectArray['includerefgeneration']) {", $content);
	}

	/**
	 * @return string Descriptor excerpt holding an empty DOCUMENT MODELS zone
	 */
	private function getDescriptorZone(): string
	{
		return "\t\t\$myTmpObjects = array();\n\t\t/* BEGIN MODULEBUILDER DOCUMENT MODELS */\n\t\t/* END MODULEBUILDER DOCUMENT MODELS */\n";
	}

	/**
	 * @return array<string,string> Template relative path => scratch copy path
	 */
	private function copyTemplates(): array
	{
		$this->tmpDir = sys_get_temp_dir().'/modulebuilderdocgenerationtest_'.getmypid();
		$copies = array();
		foreach (self::DOC_TEMPLATES as $templateFile) {
			$copy = $this->tmpDir.'/'.$templateFile;
			if (!is_dir(dirname($copy))) {
				$this->assertTrue(mkdir(dirname($copy), 0700, true));
			}
			$this->assertTrue(copy(self::TEMPLATE_DIR.'/'.$templateFile, $copy));
			$copies[$templateFile] = $copy;
		}
		return $copies;
	}

	/**
	 * @param string $file PHP file to lint
	 * @return void
	 */
	private function assertPhpLintOk(string $file): void
	{
		$output = array();
		$returnCode = 0;
		exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1', $output, $returnCode);
		$this->assertSame(0, $returnCode, implode("\n", $output));
	}
}
