<?php

/**
 * @testCase
 */

require __DIR__ . '/../vendor/autoload.php';

use Tester\Assert;

class SkipLintProcessTest extends Tester\TestCase
{
    public function testLargeInput()
    {
        $filesToCheck = array(
            __DIR__ . '/skip-on-5.3/class.php',
            __DIR__ . '/skip-on-5.3/trait.php',
        );

        for ($i = 0; $i < 15; $i++) {
            $filesToCheck = array_merge($filesToCheck, $filesToCheck);
        }

        $phpExecutable = \JakubOnderka\PhpParallelLint\Process\PhpExecutable::getPhpExecutable('php');
        $process = new \JakubOnderka\PhpParallelLint\Process\SkipLintProcess($phpExecutable, $filesToCheck);

        while (!$process->isFinished()) {
            usleep(100);
            $process->getChunk();
        }

        foreach ($filesToCheck as $fileToCheck) {
            $status = $process->isSkipped($fileToCheck);
            Assert::notEqual(null, $status);
        }
    }
}

$skipLintProcessTest = new SkipLintProcessTest;
$skipLintProcessTest->run();
