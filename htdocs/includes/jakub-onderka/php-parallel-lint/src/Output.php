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

interface Output
{
    public function __construct(IWriter $writer);

    public function ok();

    public function skip();

    public function error();

    public function fail();

    public function setTotalFileCount($count);

    public function writeHeader($phpVersion, $parallelJobs, $hhvmVersion = null);

    public function writeResult(Result $result, ErrorFormatter $errorFormatter, $ignoreFails);
}

class JsonOutput implements Output
{
    /** @var IWriter */
    protected $writer;

    /** @var int */
    protected $phpVersion;

    /** @var int */
    protected $parallelJobs;

    /** @var string */
    protected $hhvmVersion;

    /**
     * @param IWriter $writer
     */
    public function __construct(IWriter $writer)
    {
        $this->writer = $writer;
    }

    public function ok()
    {

    }

    public function skip()
    {

    }

    public function error()
    {

    }

    public function fail()
    {

    }

    public function setTotalFileCount($count)
    {

    }

    public function writeHeader($phpVersion, $parallelJobs, $hhvmVersion = null)
    {
        $this->phpVersion = $phpVersion;
        $this->parallelJobs = $parallelJobs;
        $this->hhvmVersion = $hhvmVersion;
    }

    public function writeResult(Result $result, ErrorFormatter $errorFormatter, $ignoreFails)
    {
        echo json_encode(array(
            'phpVersion' => $this->phpVersion,
            'hhvmVersion' => $this->hhvmVersion,
            'parallelJobs' => $this->parallelJobs,
            'results' => $result,
        ));
    }
}

class TextOutput implements Output
{
    const TYPE_DEFAULT = 'default',
        TYPE_SKIP = 'skip',
        TYPE_ERROR = 'error',
        TYPE_OK = 'ok';

    /** @var int */
    public $filesPerLine = 60;

    /** @var int */
    protected $checkedFiles;

    /** @var int */
    protected $totalFileCount;

    /** @var IWriter */
    protected $writer;

    /**
     * @param IWriter $writer
     */
    public function __construct(IWriter $writer)
    {
        $this->writer = $writer;
    }

    public function ok()
    {
        $this->writer->write('.');
        $this->progress();
    }

    public function skip()
    {
        $this->write('S', self::TYPE_SKIP);
        $this->progress();
    }

    public function error()
    {
        $this->write('X', self::TYPE_ERROR);
        $this->progress();
    }

    public function fail()
    {
        $this->writer->write('-');
        $this->progress();
    }

    /**
     * @param string $string
     * @param string $type
     */
    public function write($string, $type = self::TYPE_DEFAULT)
    {
        $this->writer->write($string);
    }

    /**
     * @param string|null $line
     * @param string $type
     */
    public function writeLine($line = null, $type = self::TYPE_DEFAULT)
    {
        $this->write($line, $type);
        $this->writeNewLine();
    }

    /**
     * @param int $count
     */
    public function writeNewLine($count = 1)
    {
        $this->write(str_repeat(PHP_EOL, $count));
    }

    /**
     * @param int $count
     */
    public function setTotalFileCount($count)
    {
        $this->totalFileCount = $count;
    }

    /**
     * @param int $phpVersion
     * @param int $parallelJobs
     * @param string $hhvmVersion
     */
    public function writeHeader($phpVersion, $parallelJobs, $hhvmVersion = null)
    {
        $this->write("PHP {$this->phpVersionIdToString($phpVersion)} | ");

        if ($hhvmVersion) {
            $this->write("HHVM $hhvmVersion | ");
        }

        if ($parallelJobs === 1) {
            $this->writeLine("1 job");
        } else {
            $this->writeLine("{$parallelJobs} parallel jobs");
        }
    }

    /**
     * @param Result $result
     * @param ErrorFormatter $errorFormatter
     * @param bool $ignoreFails
     */
    public function writeResult(Result $result, ErrorFormatter $errorFormatter, $ignoreFails)
    {
        if ($this->checkedFiles % $this->filesPerLine !== 0) {
            $rest = $this->filesPerLine - ($this->checkedFiles % $this->filesPerLine);
            $this->write(str_repeat(' ', $rest));
            $this->writeProgress();
        }

        $this->writeNewLine(2);

        $testTime = round($result->getTestTime(), 1);
        $message = "Checked {$result->getCheckedFilesCount()} files in $testTime ";
        $message .= $testTime == 1 ? 'second' : 'seconds';

        if ($result->getSkippedFilesCount() > 0) {
            $message .= ", skipped {$result->getSkippedFilesCount()} ";
            $message .= ($result->getSkippedFilesCount() === 1 ? 'file' : 'files');
        }

        $this->writeLine($message);

        if (!$result->hasSyntaxError()) {
            $message = "No syntax error found";
        } else {
            $message = "Syntax error found in {$result->getFilesWithSyntaxErrorCount()} ";
            $message .= ($result->getFilesWithSyntaxErrorCount() === 1 ? 'file' : 'files');
        }

        if ($result->hasFilesWithFail()) {
            $message .= ", failed to check {$result->getFilesWithFailCount()} ";
            $message .= ($result->getFilesWithFailCount() === 1 ? 'file' : 'files');

            if ($ignoreFails) {
                $message .= ' (ignored)';
            }
        }

        $hasError = $ignoreFails ? $result->hasSyntaxError() : $result->hasError();
        $this->writeLine($message, $hasError ? self::TYPE_ERROR : self::TYPE_OK);

        if ($result->hasError()) {
            $this->writeNewLine();
            foreach ($result->getErrors() as $error) {
                $this->writeLine(str_repeat('-', 60));
                $this->writeLine($errorFormatter->format($error));
            }
        }
    }

    protected function progress()
    {
        ++$this->checkedFiles;

        if ($this->checkedFiles % $this->filesPerLine === 0) {
            $this->writeProgress();
        }
    }

    protected function writeProgress()
    {
        $percent = floor($this->checkedFiles / $this->totalFileCount * 100);
        $current = $this->stringWidth($this->checkedFiles, strlen($this->totalFileCount));
        $this->writeLine(" $current/$this->totalFileCount ($percent %)");
    }

    /**
     * @param string $input
     * @param int $width
     * @return string
     */
    protected function stringWidth($input, $width = 3)
    {
        $multiplier = $width - strlen($input);
        return str_repeat(' ', $multiplier > 0 ? $multiplier : 0) . $input;
    }

    /**
     * @param int $phpVersionId
     * @return string
     */
    protected function phpVersionIdToString($phpVersionId)
    {
        $releaseVersion = (int) substr($phpVersionId, -2, 2);
        $minorVersion = (int) substr($phpVersionId, -4, 2);
        $majorVersion = (int) substr($phpVersionId, 0, strlen($phpVersionId) - 4);

        return "$majorVersion.$minorVersion.$releaseVersion";
    }
}

class TextOutputColored extends TextOutput
{
    /** @var \JakubOnderka\PhpConsoleColor\ConsoleColor */
    private $colors;

    public function __construct(IWriter $writer)
    {
        parent::__construct($writer);

        if (class_exists('\JakubOnderka\PhpConsoleColor\ConsoleColor')) {
            $this->colors = new \JakubOnderka\PhpConsoleColor\ConsoleColor();
            $this->colors->setForceStyle(true);
        }
    }

    /**
     * @param string $string
     * @param string $type
     * @throws \JakubOnderka\PhpConsoleColor\InvalidStyleException
     */
    public function write($string, $type = self::TYPE_DEFAULT)
    {
        if (!$this->colors instanceof \JakubOnderka\PhpConsoleColor\ConsoleColor) {
            parent::write($string, $type);
        } else {
            switch ($type) {
                case self::TYPE_OK:
                    parent::write($this->colors->apply('bg_green', $string));
                    break;

                case self::TYPE_SKIP:
                    parent::write($this->colors->apply('bg_yellow', $string));
                    break;

                case self::TYPE_ERROR:
                    parent::write($this->colors->apply('bg_red', $string));
                    break;

                default:
                    parent::write($string);
            }
        }
    }
}

interface IWriter
{
    /**
     * @param string $string
     */
    public function write($string);
}

class NullWriter implements IWriter
{
    /**
     * @param string $string
     */
    public function write($string)
    {

    }
}

class ConsoleWriter implements IWriter
{
    /**
     * @param string $string
     */
    public function write($string)
    {
        echo $string;
    }
}

class FileWriter implements IWriter
{
    /** @var string */
    protected $logFile;

    /** @var string */
    protected $buffer;

    public function __construct($logFile)
    {
        $this->logFile = $logFile;
    }

    public function write($string)
    {
        $this->buffer .= $string;
    }

    public function __destruct()
    {
        file_put_contents($this->logFile, $this->buffer);
    }
}

class MultipleWriter implements IWriter
{
    /** @var IWriter[] */
    protected $writers;

    /**
     * @param IWriter[] $writers
     */
    public function __construct(array $writers)
    {
        foreach ($writers as $writer) {
            $this->addWriter($writer);
        }
    }

    /**
     * @param IWriter $writer
     */
    public function addWriter(IWriter $writer)
    {
        $this->writers[] = $writer;
    }

    /**
     * @param $string
     */
    public function write($string)
    {
        foreach ($this->writers as $writer) {
            $writer->write($string);
        }
    }
}
