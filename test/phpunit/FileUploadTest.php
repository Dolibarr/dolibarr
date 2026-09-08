<?php
/* Copyright (C) 2026 ATM Consulting
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
 *      \file       test/phpunit/FileUploadTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test of the class FileUpload, used by the drag and drop of a file on a card.
 *		\remarks	To run this script as CLI:  phpunit FileUploadTest.php < /dev/null
 *					The redirection of stdin is only needed on an interactive terminal, because the tested
 *					code reads php://input when the file is not a real http upload.
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/fileupload.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/../../htdocs/contact/class/contact.class.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/project.class.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/task.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1');
}

/**
 * Class for PHPUnit tests of FileUpload
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class FileUploadTest extends CommonClassTest
{
	/**
	 * Directories created by the tests, removed at the end
	 * @var string[]
	 */
	protected static $dirstoclean = array();

	/**
	 * Temporary files created by the tests, removed at the end
	 * @var string[]
	 */
	protected static $filestoclean = array();

	/**
	 * Objects of the fixture, removed by the rollback of the transaction
	 * @var array<string,CommonObject>
	 */
	protected static $objects = array();


	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf, $db, $user;

		parent::setUpBeforeClass();

		$conf->global->MAIN_DISABLE_SUGGEST_REF_AS_PREFIX = 0;

		// A thirdparty
		$thirdparty = new Societe($db);
		$thirdparty->initAsSpecimen();
		$thirdparty->name = 'Test FileUpload';
		$thirdparty->country_id = 1;
		$id = $thirdparty->create($user);
		if ($id <= 0) {
			die("Failed to create the thirdparty: ".$thirdparty->errorsToString()."\n");
		}
		$thirdparty->fetch($id);
		self::$objects['societe'] = $thirdparty;

		// A contact of this thirdparty
		$contact = new Contact($db);
		$contact->lastname = 'Doe';
		$contact->firstname = 'John';
		$contact->socid = $thirdparty->id;
		$contact->country_id = 1;
		$id = $contact->create($user);
		if ($id <= 0) {
			die("Failed to create the contact: ".$contact->errorsToString()."\n");
		}
		$contact->fetch($id);
		self::$objects['contact'] = $contact;

		// A product
		$product = new Product($db);
		$product->ref = 'PRODUCT-FILEUPLOAD-TEST';
		$product->label = 'Test FileUpload';
		$product->type = Product::TYPE_PRODUCT;
		$id = $product->create($user);
		if ($id <= 0) {
			die("Failed to create the product: ".$product->errorsToString()."\n");
		}
		$product->fetch($id);
		self::$objects['product'] = $product;

		// A project whose ref holds the chars a path must not keep, and a task of this project
		$project = new Project($db);
		$project->ref = 'PJ/UP:é 2026';
		$project->title = 'Test FileUpload';
		$project->socid = $thirdparty->id;
		$id = $project->create($user);
		if ($id <= 0) {
			die("Failed to create the project: ".$project->errorsToString()."\n");
		}
		$project->fetch($id);
		self::$objects['project'] = $project;

		$task = new Task($db);
		$task->ref = 'TASK-FILEUPLOAD-TEST';
		$task->label = 'Test FileUpload';
		$task->fk_project = $project->id;
		$id = $task->create($user);
		if ($id <= 0) {
			die("Failed to create the task: ".$task->errorsToString()."\n");
		}
		$task->fetch($id);
		self::$objects['project_task'] = $task;
	}

	/**
	 * tearDownAfterClass
	 *
	 * @return void
	 */
	public static function tearDownAfterClass(): void
	{
		foreach (self::$dirstoclean as $dir) {
			if (is_dir($dir)) {
				dol_delete_dir_recursive($dir);
			}
		}
		foreach (self::$filestoclean as $file) {
			@unlink($file);
		}

		// The objects created by setUpBeforeClass() are removed by the rollback of the parent, which
		// closes the transaction opened by its own setUpBeforeClass().
		parent::tearDownAfterClass();
	}

	/**
	 * Build a FileUpload for an object and return its upload directory.
	 *
	 * @param	string	$element	Element code
	 * @param	int		$id			Id of the object
	 * @return	string				Upload directory
	 */
	protected function uploadDirOf($element, $id)
	{
		$upload = new FileUpload(null, $id, $element);
		$this->assertIsArray($upload->options);
		$this->assertArrayHasKey('upload_dir', $upload->options);
		return $upload->options['upload_dir'];
	}


	//
	// Resolution of the directory where the file is stored
	//

	/**
	 * The directory of a product must be the one read by product/document.php, otherwise the file is
	 * stored but never shown into the "Attached files" tab.
	 *
	 * @return void
	 */
	public function testUploadDirOfAProduct()
	{
		global $conf;

		$object = self::$objects['product'];

		// The formula of product/document.php, written here on purpose so the test does not use the
		// code it checks to build its expectation.
		$expected = $conf->product->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref).'/';

		$this->assertSame($expected, $this->uploadDirOf('product', $object->id));
	}

	/**
	 * A thirdparty stores its documents into a directory named after its id, not its ref.
	 *
	 * @return void
	 */
	public function testUploadDirOfAThirdpartyUsesTheId()
	{
		global $conf;

		$object = self::$objects['societe'];

		// The formula of societe/document.php
		$expected = $conf->societe->multidir_output[$object->entity].'/'.$object->id.'/';

		$this->assertSame($expected, $this->uploadDirOf('societe', $object->id));
		$this->assertStringNotContainsString(dol_sanitizeFileName($object->name), $this->uploadDirOf('societe', $object->id), 'The name of a thirdparty must never appear into its directory');
	}

	/**
	 * A contact is stored into a sub directory of the thirdparty module. getMultidirOutput() does not know
	 * this element and answers its sentinel, so this asserts the fallback on getElementProperties().
	 *
	 * @return void
	 */
	public function testUploadDirOfAContactFallsBackOnGetElementProperties()
	{
		global $conf;

		$object = self::$objects['contact'];

		$this->assertStringStartsWith('error-diroutput-not-defined-for-this-object=', (string) getMultidirOutput($object, 'contact'), 'The fixture requires getMultidirOutput to fail on a contact');

		// The formula of contact/document.php
		$expected = $conf->societe->multidir_output[$object->entity].'/contact/'.dol_sanitizeFileName($object->ref).'/';

		$this->assertSame($expected, $this->uploadDirOf('contact', $object->id));
	}

	/**
	 * A task is stored into a sub directory named after the ref of its project. That ref is a user input,
	 * so it must be sanitized the same way projet/tasks/document.php does it.
	 *
	 * @return void
	 */
	public function testUploadDirOfATaskSanitizesTheProjectRef()
	{
		global $conf;

		$task = self::$objects['project_task'];
		$project = self::$objects['project'];

		$this->assertStringContainsString('/', $project->ref, 'The fixture requires a project ref holding a slash');

		// The formula of projet/tasks/document.php
		$expected = $conf->project->multidir_output[$project->entity].'/'.dol_sanitizeFileName($project->ref).'/'.dol_sanitizeFileName($task->ref).'/';

		$dir = $this->uploadDirOf('project_task', $task->id);

		$this->assertSame($expected, $dir);
		$this->assertStringNotContainsString('PJ/UP', $dir, 'The slash of the project ref must not create a sub directory');
		$this->assertStringNotContainsString(':', $dir, 'A colon of the project ref must not be kept');
	}

	/**
	 * Whatever the element, the directory is absolute, inside DOL_DATA_ROOT, and never holds the sentinel
	 * string returned by getMultidirOutput() when it fails.
	 *
	 * @return void
	 */
	public function testUploadDirIsNeverTheSentinelOfGetMultidirOutput()
	{
		foreach (array('product', 'societe', 'contact', 'project_task') as $element) {
			$dir = $this->uploadDirOf($element, self::$objects[$element]->id);

			$this->assertStringNotContainsString('error-diroutput-not-defined-for-this-object', $dir, 'The sentinel leaked into the directory of the element '.$element);
			$this->assertStringStartsWith(DOL_DATA_ROOT, $dir, 'The directory of the element '.$element.' must be inside DOL_DATA_ROOT');
			$this->assertStringNotContainsString('//', $dir, 'The directory of the element '.$element.' must not hold a double slash');
			$this->assertStringEndsWith('/', $dir, 'The directory must end with a slash, the file name is concatenated to it');
		}
	}

	/**
	 * The file name is prefixed by the ref of the object, and that ref is sanitized because it is a user
	 * input that may hold a slash.
	 *
	 * @return void
	 */
	public function testSavingDocMaskIsSanitized()
	{
		$project = self::$objects['project'];

		$upload = new FileUpload(null, $project->id, 'project');

		$this->assertSame(dol_sanitizeFileName($project->ref).'-__file__', $upload->options['saving_doc_mask']);
		$this->assertStringNotContainsString('/', $upload->options['saving_doc_mask'], 'A file name mask must never hold a slash');
	}


	//
	// Refusals
	//

	/**
	 * An object that does not exist must be refused, otherwise the file is stored at the root of the
	 * directory of the module, out of any object and out of any permission check.
	 *
	 * @return void
	 */
	public function testConstructRefusesAnObjectThatDoesNotExist()
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('objectnotfound');

		new FileUpload(null, 99999999, 'product');
	}

	/**
	 * An id of 0 must be refused too, it would answer the directory of every unsaved object.
	 *
	 * @return void
	 */
	public function testConstructRefusesAnEmptyId()
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('objectnotfound');

		new FileUpload(null, 0, 'product');
	}

	/**
	 * An element with no directory at all must be refused instead of writing anywhere.
	 *
	 * @return void
	 */
	public function testConstructRefusesAnUnknownElement()
	{
		$this->expectException(Exception::class);

		new FileUpload(null, 1, 'anelementthatdoesnotexist');
	}


	//
	// Deduplication of the name of the uploaded file
	//


	/**
	 * Build a FileUpload for an object of the fixture and empty its upload directory, so the tests below
	 * start from a known set of already used names.
	 *
	 * @param	string	$element	Element code of the fixture
	 * @return	FileUpload			Instance ready to use
	 */
	protected function prepareUploadDir($element)
	{
		$object = self::$objects[$element];
		$upload = new FileUpload(null, $object->id, $element);
		$dir = $upload->options['upload_dir'];

		if (is_dir($dir)) {
			dol_delete_dir_recursive($dir);
		}
		dol_mkdir($dir);
		self::$dirstoclean[$dir] = $dir;

		return $upload;
	}

	/**
	 * Create the temporary file playing the role of the file uploaded by the browser.
	 *
	 * @return	string	Path of the file
	 */
	protected function makeTmpFile()
	{
		$tmpfile = DOL_DATA_ROOT.'/admin/temp/fileuploadtest-'.getmypid().'.txt';
		dol_mkdir(dirname($tmpfile));
		file_put_contents($tmpfile, 'content');
		self::$filestoclean[$tmpfile] = $tmpfile;

		return $tmpfile;
	}

	/**
	 * Call the protected handleFileUpload() of FileUpload.
	 *
	 * @param	FileUpload	$upload		Instance
	 * @param	string		$tmpfile	Path of the file to upload
	 * @param	string		$name		Name sent by the browser
	 * @return	stdClass				The file object answered by handleFileUpload()
	 */
	protected function callHandleFileUpload($upload, $tmpfile, $name)
	{
		$method = new ReflectionMethod('FileUpload', 'handleFileUpload');
		$method->setAccessible(true);

		// validate() reads CONTENT_LENGTH when the file is not a real http upload
		$_SERVER['CONTENT_LENGTH'] = 10;

		return $method->invoke($upload, $tmpfile, $name, 0, 'text/plain', 0, 0);
	}

	/**
	 * A name that is free must be kept as it is, only prefixed by the ref of the object.
	 *
	 * @return void
	 */
	public function testUploadOfAFreeNameIsKept()
	{
		$upload = $this->prepareUploadDir('product');
		$prefix = dol_sanitizeFileName(self::$objects['product']->ref).'-';

		$file = $this->callHandleFileUpload($upload, $this->makeTmpFile(), 'afreename.txt');

		$this->assertSame($prefix.'afreename.txt', $file->name, 'A free name must be kept, only the prefix of the ref is added');
	}

	/**
	 * Uploading twice the same file must not overwrite the first one: dol_move_uploaded_file() is called
	 * with $allowoverwrite = 1, so the name must be made unique before.
	 * trimFileName() already does that check, but on the name before the prefix of the ref is added, so
	 * it compares a name that is not the one stored.
	 *
	 * @return void
	 */
	public function testUploadOfAnAlreadyExistingNameIsRenamed()
	{
		$upload = $this->prepareUploadDir('product');
		$dir = $upload->options['upload_dir'];
		$prefix = dol_sanitizeFileName(self::$objects['product']->ref).'-';

		// The name is already used, with the prefix that trimFileName() does not know about
		file_put_contents($dir.$prefix.'mydoc.txt', 'first');

		$file = $this->callHandleFileUpload($upload, $this->makeTmpFile(), 'mydoc.txt');

		$this->assertSame($prefix.'mydoc (1).txt', $file->name, 'The name must be made unique, the first file must not be overwritten');
		$this->assertSame('first', file_get_contents($dir.$prefix.'mydoc.txt'), 'The first file must be untouched');
		$this->assertFileExists($dir.$file->name, 'The new file must be stored under its new name');
	}

	/**
	 * The renaming must be repeated as long as the name is used, so a third upload gets a third name.
	 *
	 * @return void
	 */
	public function testUploadOfAnAlreadyExistingNameIsRenamedAgain()
	{
		$upload = $this->prepareUploadDir('product');
		$dir = $upload->options['upload_dir'];
		$prefix = dol_sanitizeFileName(self::$objects['product']->ref).'-';

		file_put_contents($dir.$prefix.'mydoc.txt', 'first');
		file_put_contents($dir.$prefix.'mydoc (1).txt', 'second');

		$file = $this->callHandleFileUpload($upload, $this->makeTmpFile(), 'mydoc.txt');

		$this->assertSame($prefix.'mydoc (2).txt', $file->name);
		$this->assertSame('first', file_get_contents($dir.$prefix.'mydoc.txt'));
		$this->assertSame('second', file_get_contents($dir.$prefix.'mydoc (1).txt'));
	}

	/**
	 * A file whose name is executable is stored by dol_move_uploaded_file() with a '.noexe' suffix added.
	 * The check on an already used name must look for that suffixed name too, otherwise such a file is
	 * silently overwritten at each upload.
	 *
	 * @return void
	 */
	public function testUploadOfAnAlreadyExistingNoexeNameIsRenamed()
	{
		$upload = $this->prepareUploadDir('product');
		$dir = $upload->options['upload_dir'];
		$prefix = dol_sanitizeFileName(self::$objects['product']->ref).'-';

		// A previous upload of the same file was renamed with the .noexe suffix
		file_put_contents($dir.$prefix.'myscript.php.noexe', 'first');

		$file = $this->callHandleFileUpload($upload, $this->makeTmpFile(), 'myscript.php');

		$this->assertStringStartsWith($prefix.'myscript (1).php', $file->name, 'A name already used with the .noexe suffix must be made unique too');
		$this->assertSame('first', file_get_contents($dir.$prefix.'myscript.php.noexe'), 'The first file must be untouched');
	}
}
