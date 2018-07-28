<?php

/**
 * @testCase
 */

require __DIR__ . '/../vendor/autoload.php';

use JakubOnderka\PhpParallelLint\ParallelLint;
use Tester\Assert;

class ParallelLintLintTest extends Tester\TestCase
{
    public function testSettersAndGetters()
    {
        $phpExecutable = $this->getPhpExecutable();
        $parallelLint = new ParallelLint($phpExecutable, 10);
        Assert::equal($phpExecutable, $parallelLint->getPhpExecutable());
        Assert::equal(10, $parallelLint->getParallelJobs());

        $phpExecutable2 = $this->getPhpExecutable();
        $parallelLint->setPhpExecutable($phpExecutable2);
        Assert::equal($phpExecutable2, $parallelLint->getPhpExecutable());

        $parallelLint->setParallelJobs(33);
        Assert::equal(33, $parallelLint->getParallelJobs());

        $parallelLint->setShortTagEnabled(true);
        Assert::true($parallelLint->isShortTagEnabled());

        $parallelLint->setAspTagsEnabled(true);
        Assert::true($parallelLint->isAspTagsEnabled());

        $parallelLint->setShortTagEnabled(false);
        Assert::false($parallelLint->isShortTagEnabled());

        $parallelLint->setAspTagsEnabled(false);
        Assert::false($parallelLint->isAspTagsEnabled());
    }

    public function testEmptyArray()
    {
        $parallelLint = new ParallelLint($this->getPhpExecutable());
        $result = $parallelLint->lint(array());

        Assert::equal(0, $result->getCheckedFilesCount());
        Assert::equal(0, $result->getFilesWithSyntaxErrorCount());
        Assert::false($result->hasSyntaxError());
        Assert::equal(0, count($result->getErrors()));
    }

    public function testNotExistsFile()
    {
        $parallelLint = new ParallelLint($this->getPhpExecutable());
        $result = $parallelLint->lint(array('path/for-not-found/'));

        Assert::equal(0, $result->getCheckedFilesCount());
        Assert::equal(0, $result->getFilesWithSyntaxErrorCount());
        Assert::false($result->hasSyntaxError());
        Assert::equal(1, count($result->getErrors()));
    }

    public function testEmptyFile()
    {
        $parallelLint = new ParallelLint($this->getPhpExecutable());
        $result = $parallelLint->lint(array(__DIR__ . '/examples/example-01/empty-file'));

        Assert::equal(1, $result->getCheckedFilesCount());
        Assert::equal(0, $result->getFilesWithSyntaxErrorCount());
        Assert::false($result->hasSyntaxError());
        Assert::equal(0, count($result->getErrors()));
    }

    public function testValidFile()
    {
        $parallelLint = new ParallelLint($this->getPhpExecutable());
        $result = $parallelLint->lint(array(__DIR__ . '/examples/example-02/example.php'));

        Assert::equal(1, $result->getCheckedFilesCount());
        Assert::equal(0, $result->getFilesWithSyntaxErrorCount());
        Assert::equal(0, count($result->getErrors()));
    }

    public function testInvalidFile()
    {
        $parallelLint = new ParallelLint($this->getPhpExecutable());
        $result = $parallelLint->lint(array(__DIR__ . '/examples/example-03/example.php'));

        Assert::equal(1, $result->getCheckedFilesCount());
        Assert::equal(1, $result->getFilesWithSyntaxErrorCount());
        Assert::true($result->hasSyntaxError());
        Assert::equal(1, count($result->getErrors()));
    }

    public function testValidAndInvalidFiles()
    {
        $parallelLint = new ParallelLint($this->getPhpExecutable());
        $result = $parallelLint->lint(array(
            __DIR__ . '/examples/example-02/example.php',
            __DIR__ . '/examples/example-03/example.php',
        ));

        Assert::equal(2, $result->getCheckedFilesCount());
        Assert::equal(1, $result->getFilesWithSyntaxErrorCount());
        Assert::true($result->hasSyntaxError());
        Assert::equal(1, count($result->getErrors()));
    }

    private function getPhpExecutable()
    {
        return \JakubOnderka\PhpParallelLint\Process\PhpExecutable::getPhpExecutable('php');
    }
}

$testCase = new ParallelLintLintTest;
$testCase->run();
