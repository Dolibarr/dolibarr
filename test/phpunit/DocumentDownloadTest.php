<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Regression tests for document.php, run in isolated processes without a database.
 * The fixture replaces the bootstrap and services; the endpoint itself is unmodified.
 */
class DocumentDownloadTest extends \PHPUnit\Framework\TestCase
{
	/** @var string Temporary fixture root */
	private $directory;

	/** @return void */
	protected function setUp(): void
	{
		parent::setUp();
		if (!extension_loaded('zip')) {
			$this->markTestSkipped('The ZIP extension is required.');
		}
		$this->directory = sys_get_temp_dir().'/dolibarr-document-test-'.bin2hex(random_bytes(8));
		mkdir($this->directory.'/htdocs/core/lib', 0700, true);
		mkdir($this->directory.'/documents/A', 0700, true);
		mkdir($this->directory.'/documents/B', 0700, true);
		copy(__DIR__.'/../../htdocs/document.php', $this->directory.'/htdocs/document.php');
		foreach (array('main.inc.php', 'core/lib/files.lib.php', 'core/lib/images.lib.php') as $file) {
			file_put_contents($this->directory.'/htdocs/'.$file, '<?php');
		}
		file_put_contents($this->directory.'/documents/A/report.pdf', 'First document');
		file_put_contents($this->directory.'/documents/B/report.pdf', 'Second document');
	}

	/** @return void */
	protected function tearDown(): void
	{
		if ($this->directory && is_dir($this->directory)) {
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
			foreach ($iterator as $file) {
				if ($file->isDir()) {
					rmdir($file->getPathname());
				} else {
					unlink($file->getPathname());
				}
			}
			rmdir($this->directory);
		}
		parent::tearDown();
	}

	/**
	 * @param array<string,mixed> $options Fixture scenario
	 * @return array<string,mixed> Observed endpoint state and response body
	 */
	private function runDownload($options = array())
	{
		$request = array_replace(array(
			'selection' => array(array('modulepart' => 'propal', 'file' => 'A/report.pdf', 'entity' => 2)),
			'post' => array('action' => 'downloadselected', 'modulepart' => 'propal'),
			'get' => array(),
		), $options);
		file_put_contents($this->directory.'/request.json', json_encode($request));
		$command = escapeshellarg(PHP_BINARY);
		if (php_ini_loaded_file()) {
			$command .= ' -c '.escapeshellarg(php_ini_loaded_file());
		}
		$command .= ' -d display_errors=stderr '.escapeshellarg(__DIR__.'/fixtures/documentdownload.php').' '.escapeshellarg($this->directory);
		$process = proc_open($command, array(0 => array('pipe', 'r'), 1 => array('file', $this->directory.'/response', 'w'), 2 => array('pipe', 'w')), $pipes);
		$this->assertIsResource($process);
		fclose($pipes[0]);
		$errors = stream_get_contents($pipes[2]);
		fclose($pipes[2]);
		$this->assertSame(0, proc_close($process), $errors);
		$this->assertSame('', $errors);
		$state = json_decode(file_get_contents($this->directory.'/state.json'), true);
		$state['body'] = file_get_contents($this->directory.'/response');
		$this->assertSame(array(), glob($this->directory.'/documents/admin/temp/documentzip_*'));
		return $state;
	}

	/** @return void */
	public function testBatchKeepsEntitiesAndDeduplicatesFilesBeforeHooks()
	{
		$first = array('modulepart' => 'propal', 'file' => 'A/report.pdf', 'entity' => 2);
		$second = array('modulepart' => 'item@external', 'file' => 'B/report.pdf', 'entity' => 2);
		$state = $this->runDownload(array('selection' => array($first, $second, $first)));
		$this->assertSame(200, $state['status']);
		$this->assertSame(array('A/report.pdf', 'B/report.pdf'), array_column($state['hooks'], 'original_file'));
		$this->assertSame(array(2, 2), array_column($state['hooks'], 'entity'));
		$zip = new ZipArchive();
		$this->assertTrue($zip->open($this->directory.'/response'));
		$this->assertSame(2, $zip->numFiles);
		$this->assertSame('First document', $zip->getFromName('report.pdf'));
		$this->assertSame('Second document', $zip->getFromName('report-2.pdf'));
		$zip->close();
	}

	/** @return void */
	public function testObjectAndEntityRestrictionsApplyBeforeAnyHook()
	{
		foreach (array(array('denyref' => 'B'), array('missingref' => 'B'), array('denymodule' => true), array('allowedentity' => 1)) as $restriction) {
			$state = $this->runDownload(array_merge($restriction, array('selection' => array(
				array('modulepart' => 'propal', 'file' => 'A/report.pdf', 'entity' => 2),
				array('modulepart' => 'propal', 'file' => 'B/report.pdf', 'entity' => 2),
			))));
			$this->assertSame(403, $state['status']);
			$this->assertSame(array(), $state['hooks']);
			$this->assertSame('Forbidden', $state['body']);
		}
	}

	/** @return void */
	public function testHookCanRefuseBatchDownload()
	{
		$state = $this->runDownload(array('denyhook' => true));
		$this->assertStringContainsString('ErrorDownloadDocumentHooks', $state['body']);
		$this->assertCount(1, $state['hooks']);
	}

	/** @return void */
	public function testInvoiceDownloadRegeneratesBeforeArchivingAndTriggersOnce()
	{
		$file = array('modulepart' => 'facture', 'file' => 'A/report.pdf', 'entity' => 2);
		$state = $this->runDownload(array('selection' => array($file, $file)));
		$this->assertSame(array('DOC_DOWNLOAD'), $state['triggers']);
		$this->assertSame(1, $state['regenerations']);
		$this->assertSame(1, $state['counterupdates']);
		$zip = new ZipArchive();
		$this->assertTrue($zip->open($this->directory.'/response'));
		$this->assertSame('DUPLICATA', $zip->getFromName('report.pdf'));
		$zip->close();
	}

	/** @return void */
	public function testSingleDownloadPreservesHookAndInvoiceHandling()
	{
		$state = $this->runDownload(array('post' => array('modulepart' => 'facture', 'file' => 'A/report.pdf', 'forcedownload' => 1)));
		$this->assertSame('DUPLICATA', $state['body']);
		$this->assertSame(array('DOC_DOWNLOAD'), $state['triggers']);
		$this->assertCount(1, $state['hooks']);
	}

	/** @return void */
	public function testMalformedSelectionIsRejectedWithoutWarnings()
	{
		foreach (array(array('file' => array()), array('file' => 'A/report.pdf', 'modulepart' => array()), array('file' => 'A/report.pdf', 'entity' => '2')) as $selection) {
			$state = $this->runDownload(array('selection' => array($selection)));
			$this->assertSame(400, $state['status']);
			$this->assertSame(array(), $state['hooks']);
		}
	}

	/** @return void */
	public function testBatchCannotUsePublicLoginOrCsrfExemptions()
	{
		foreach (array(array('hashp' => 'publickey'), array('modulepart' => 'medias')) as $get) {
			$state = $this->runDownload(array('get' => $get, 'anonymous' => true));
			$this->assertFalse($state['nologin']);
			$this->assertFalse($state['nocsrfcheck']);
			$this->assertSame(403, $state['status']);
			$this->assertSame(array(), $state['hooks']);
		}
		$state = $this->runDownload(array(
			'get' => array('modulepart' => 'medias', 'action' => ' downloadselected '),
			'anonymous' => true,
		));
		$this->assertFalse($state['nologin']);
		$this->assertFalse($state['nocsrfcheck']);
		$this->assertSame(403, $state['status']);
	}

	/** @return void */
	public function testBatchRequiresPost()
	{
		$state = $this->runDownload(array('method' => 'GET'));
		$this->assertSame(405, $state['status']);
		$this->assertSame(array(), $state['hooks']);
	}
}
