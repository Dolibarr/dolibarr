<?php
namespace JakubOnderka\PhpParallelLint;

/*
Copyright (c) 2012, Jakub Onderka
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those
of the authors and should not be interpreted as representing official policies,
either expressed or implied, of the FreeBSD Project.
 */

use JakubOnderka\PhpParallelLint\Process\LintProcess;
use JakubOnderka\PhpParallelLint\Process\PhpExecutable;
use JakubOnderka\PhpParallelLint\Process\SkipLintProcess;

class ParallelLint
{
    const STATUS_OK = 'ok',
        STATUS_SKIP = 'skip',
        STATUS_FAIL = 'fail',
        STATUS_ERROR = 'error';

    /** @var int */
    private $parallelJobs;

    /** @var PhpExecutable */
    private $phpExecutable;

    /** @var bool */
    private $aspTagsEnabled = false;

    /** @var bool */
    private $shortTagEnabled = false;

    /** @var callable */
    private $processCallback;

    public function __construct(PhpExecutable $phpExecutable, $parallelJobs = 10)
    {
        $this->phpExecutable = $phpExecutable;
        $this->parallelJobs = $parallelJobs;
    }

    /**
     * @param array $files
     * @return Result
     * @throws \Exception
     */
    public function lint(array $files)
    {
        $startTime = microtime(true);

        $skipLintProcess = new SkipLintProcess($this->phpExecutable, $files);

        $processCallback = is_callable($this->processCallback) ? $this->processCallback : function() {};

        /**
         * @var LintProcess[] $running
         * @var LintProcess[] $waiting
         */
        $errors = $running = $waiting = array();
        $skippedFiles = $checkedFiles = array();

        while ($files || $running) {
            for ($i = count($running); $files && $i < $this->parallelJobs; $i++) {
                $file = array_shift($files);

                if ($skipLintProcess->isSkipped($file) === true) {
                    $skippedFiles[] = $file;
                    $processCallback(self::STATUS_SKIP, $file);
                } else {
                    $running[$file] = new LintProcess(
                        $this->phpExecutable,
                        $file,
                        $this->aspTagsEnabled,
                        $this->shortTagEnabled
                    );
                }
            }

            $skipLintProcess->getChunk();
            usleep(100);

            foreach ($running as $file => $process) {
                if ($process->isFinished()) {
                    unset($running[$file]);

                    $skipStatus = $skipLintProcess->isSkipped($file);
                    if ($skipStatus === null) {
                        $waiting[$file] = $process;

                    } elseif ($skipStatus === true) {
                        $skippedFiles[] = $file;
                        $processCallback(self::STATUS_SKIP, $file);

                    } elseif ($process->isSuccess()) {
                        $checkedFiles[] = $file;
                        $processCallback(self::STATUS_OK, $file);

                    } elseif ($process->hasSyntaxError()) {
                        $checkedFiles[] = $file;
                        $errors[] = new SyntaxError($file, $process->getSyntaxError());
                        $processCallback(self::STATUS_ERROR, $file);

                    } else {
                        $errors[] = new Error($file, $process->getOutput());
                        $processCallback(self::STATUS_FAIL, $file);
                    }
                }
            }
        }

        if (!empty($waiting)) {
            $skipLintProcess->waitForFinish();

            foreach ($waiting as $file => $process) {
                $skipStatus = $skipLintProcess->isSkipped($file);
                if ($skipStatus === null) {
                    throw new \Exception("File $file has empty skip status. Please contact PHP Parallel Lint author.");

                } elseif ($skipStatus === true) {
                    $skippedFiles[] = $file;
                    $processCallback(self::STATUS_SKIP, $file);

                } elseif ($process->isSuccess()) {
                    $checkedFiles[] = $file;
                    $processCallback(self::STATUS_OK, $file);

                } elseif ($process->hasSyntaxError()) {
                    $checkedFiles[] = $file;
                    $errors[] = new SyntaxError($file, $process->getSyntaxError());
                    $processCallback(self::STATUS_ERROR, $file);

                } else {
                    $errors[] = new Error($file, $process->getOutput());
                    $processCallback(self::STATUS_FAIL, $file);
                }
            }
        }

        $testTime = microtime(true) - $startTime;

        return new Result($errors, $checkedFiles, $skippedFiles, $testTime);
    }

    /**
     * @return int
     */
    public function getParallelJobs()
    {
        return $this->parallelJobs;
    }

    /**
     * @param int $parallelJobs
     * @return ParallelLint
     */
    public function setParallelJobs($parallelJobs)
    {
        $this->parallelJobs = $parallelJobs;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhpExecutable()
    {
        return $this->phpExecutable;
    }

    /**
     * @param string $phpExecutable
     * @return ParallelLint
     */
    public function setPhpExecutable($phpExecutable)
    {
        $this->phpExecutable = $phpExecutable;

        return $this;
    }

    /**
     * @return callable
     */
    public function getProcessCallback()
    {
        return $this->processCallback;
    }

    /**
     * @param callable $processCallback
     * @return ParallelLint
     */
    public function setProcessCallback($processCallback)
    {
        $this->processCallback = $processCallback;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAspTagsEnabled()
    {
        return $this->aspTagsEnabled;
    }

    /**
     * @param boolean $aspTagsEnabled
     * @return ParallelLint
     */
    public function setAspTagsEnabled($aspTagsEnabled)
    {
        $this->aspTagsEnabled = $aspTagsEnabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShortTagEnabled()
    {
        return $this->shortTagEnabled;
    }

    /**
     * @param boolean $shortTagEnabled
     * @return ParallelLint
     */
    public function setShortTagEnabled($shortTagEnabled)
    {
        $this->shortTagEnabled = $shortTagEnabled;

        return $this;
    }
}
