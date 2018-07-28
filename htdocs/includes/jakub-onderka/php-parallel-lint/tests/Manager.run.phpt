<?php

/**
 * @testCase
 */

require __DIR__ . '/../vendor/autoload.php';

use JakubOnderka\PhpParallelLint\Manager;
use JakubOnderka\PhpParallelLint\NullWriter;
use JakubOnderka\PhpParallelLint\Settings;
use JakubOnderka\PhpParallelLint\TextOutput;
use Tester\Assert;

class ManagerRunTest extends Tester\TestCase
{
    public function testBadPath()
    {
        $settings = $this->prepareSettings();
        $settings->paths = array('path/for-not-found/');
        $manager = $this->getManager($settings);
        Assert::exception(function() use ($manager, $settings) {
            $manager->run($settings);
        }, 'JakubOnderka\PhpParallelLint\NotExistsPathException');
    }

    public function testFilesNotFound()
    {
        $settings = $this->prepareSettings();
        $settings->paths = array('examples/example-01/');
        $manager = $this->getManager($settings);
        Assert::exception(function() use ($manager, $settings) {
            $manager->run($settings);
        }, 'JakubOnderka\PhpParallelLint\Exception', 'No file found to check.');
    }

    public function testSuccess()
    {
        $settings = $this->prepareSettings();
        $settings->paths = array('examples/example-02/');

        $manager = $this->getManager($settings);
        $result = $manager->run($settings);
        Assert::false($result->hasError());
    }

    public function testError()
    {
        $settings = $this->prepareSettings();
        $settings->paths = array('examples/example-03/');

        $manager = $this->getManager($settings);
        $result = $manager->run($settings);
        Assert::true($result->hasError());
    }

    public function testExcludeRelativeSubdirectory()
    {
        $settings = $this->prepareSettings();
        $settings->paths = array('examples/example-04/');

        $manager = $this->getManager($settings);
        $result = $manager->run($settings);
        Assert::true($result->hasError());

        $settings->excluded = array('examples/example-04/dir1/dir2');

        $manager = $this->getManager($settings);
        $result = $manager->run($settings);
        Assert::false($result->hasError());
    }

    public function testExcludeAbsoluteSubdirectory()
    {
        $settings = $this->prepareSettings();
        $cwd = getcwd();
        $settings->paths = array($cwd . '/examples/example-04/');
        $settings->excluded = array();

        $manager = $this->getManager($settings);
        $result = $manager->run($settings);
        Assert::true($result->hasError());

        $settings->excluded = array($cwd . '/examples/example-04/dir1/dir2');

        $manager = $this->getManager($settings);
        $result = $manager->run($settings);
        Assert::false($result->hasError());
    }

    /**
     * @param Settings $settings
     * @return Manager
     */
    private function getManager(Settings $settings)
    {
        $manager = new Manager($settings);
        $manager->setOutput(new TextOutput(new NullWriter()));
        return $manager;
    }

    /**
     * @return JakubOnderka\PhpParallelLint\Settings
     */
    private function prepareSettings()
    {
        $settings = new Settings();
        $settings->phpExecutable = 'php';
        $settings->shortTag = false;
        $settings->aspTags = false;
        $settings->parallelJobs = 10;
        $settings->extensions = array('php', 'phtml', 'php3', 'php4', 'php5');
        $settings->paths = array('FOR-SET');
        $settings->excluded = array();
        $settings->colors = false;

        return $settings;
    }
}

$testCase = new ManagerRunTest;
$testCase->run();
