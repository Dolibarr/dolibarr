<?php
/* Copyright (C) 2026		John BOTELLA
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
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/HiddenConfListTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test to check the list of hidden conf of the UX documentation is in line with the code
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/admin/tools/ui/resources/hidden-conf.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1".PHP_EOL;
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredMethod
 * @phan-file-suppress PhanUndeclaredProperty
 */
class HiddenConfListTest extends CommonClassTest
{
	/**
	 * Prefixes of constants whose name is built dynamically in the code
	 *
	 * @var string[]
	 */
	private const DYNAMIC_PREFIXES = array('MAIN_DOCUMENTS_HIDE_DESCRIPTION_FOR_');

	/**
	 * Return the list of upper case words found in the code (php and js files)
	 *
	 * @return array<string, true>
	 */
	private function getCodeTokens(): array
	{
		$tokens = array();
		$excluded = array(DOL_DOCUMENT_ROOT.'/includes/', DOL_DOCUMENT_ROOT.'/custom/', DOL_DOCUMENT_ROOT.'/admin/tools/ui/resources/');

		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(DOL_DOCUMENT_ROOT, FilesystemIterator::SKIP_DOTS));
		foreach ($iterator as $file) {
			/** @var SplFileInfo $file */
			$path = $file->getPathname();
			if (!preg_match('/\.(php|js)$/', $path)) {
				continue;
			}
			foreach ($excluded as $dir) {
				if (strpos($path, $dir) === 0) {
					continue 2;
				}
			}
			$content = (string) file_get_contents($path);
			if (preg_match_all('/[A-Z][A-Z0-9_]{3,}/', $content, $matches)) {
				$tokens += array_fill_keys($matches[0], true);
			}
		}

		return $tokens;
	}

	/**
	 * Check that each constant has a valid definition and a status in line with the code
	 *
	 * @return void
	 */
	public function testHiddenConfList(): void
	{
		global $langs;
		$langs->loadLangs(array('hiddenconf'));

		$tokens = $this->getCodeTokens();
		$this->assertNotEmpty($tokens, 'No token found in the code');

		$errors = array();
		$seen = array();
		foreach (getListOfHiddenConf() as $sectionKey => $section) {
			if (empty($section['label']) || empty($section['wiki_anchor'])) {
				$errors[] = 'Section '.$sectionKey.' has no label or wiki_anchor';
			}
			foreach ($section['confs'] as $name => $item) {
				$item = hiddenConfNormalize($name, $item);

				if (isset($seen[$name])) {
					$errors[] = $name.' is defined in sections '.$seen[$name].' and '.$sectionKey;
				}
				$seen[$name] = $sectionKey;

				$found = isset($tokens[$name]);
				foreach (self::DYNAMIC_PREFIXES as $prefix) {
					if (strpos($name, $prefix) === 0 && isset($tokens[$prefix])) {
						$found = true;
					}
				}

				if ($item['status'] == 'removed' && $found) {
					$errors[] = $name.' is flagged as removed but is still used in the code';
				}
				if ($item['status'] != 'removed' && !$found) {
					$errors[] = $name.' is not used anymore in the code: set its status to removed with removed_in';
				}
				if ($item['title'] === '') {
					$errors[] = $name.' has no title: add HiddenConfTitle_'.$name.' in langs/en_US/hiddenconf.lang';
				}
				if ($item['status'] == 'in_setup' && ($item['setup_url'] === '' || !is_file(DOL_DOCUMENT_ROOT.preg_replace('/\?.*$/', '', $item['setup_url'])))) {
					$errors[] = $name.' has an invalid setup_url';
				}
			}
		}

		$this->assertEmpty($errors, implode("\n", $errors));
	}
}
