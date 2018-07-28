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

use JakubOnderka\PhpParallelLint\Process\GitBlameProcess;
use JakubOnderka\PhpParallelLint\Process\PhpExecutable;

class Manager
{
    /** @var Output */
    protected $output;

    /**
     * @param null|Settings $settings
     * @return Result
     * @throws \Exception
     */
    public function run(Settings $settings = null)
    {
        $settings = $settings ?: new Settings;
        $output = $this->output ?: $this->getDefaultOutput($settings);

        $phpExecutable = PhpExecutable::getPhpExecutable($settings->phpExecutable);
        $translateTokens = $phpExecutable->isIsHhvmType() || $phpExecutable->getVersionId() < 50400; // From PHP version 5.4 are tokens translated by default

        $output->writeHeader($phpExecutable->getVersionId(), $settings->parallelJobs, $phpExecutable->getHhvmVersion());

        $files = $this->getFilesFromPaths($settings->paths, $settings->extensions, $settings->excluded);

        if (empty($files)) {
            throw new Exception('No file found to check.');
        }

        $output->setTotalFileCount(count($files));

        $parallelLint = new ParallelLint($phpExecutable, $settings->parallelJobs);
        $parallelLint->setAspTagsEnabled($settings->aspTags);
        $parallelLint->setShortTagEnabled($settings->shortTag);

        $parallelLint->setProcessCallback(function ($status, $file) use ($output) {
            if ($status === ParallelLint::STATUS_OK) {
                $output->ok();
            } elseif ($status === ParallelLint::STATUS_SKIP) {
                $output->skip();
            } elseif ($status === ParallelLint::STATUS_ERROR) {
                $output->error();
            } else {
                $output->fail();
            }
        });

        $result = $parallelLint->lint($files);

        if ($settings->blame) {
            $this->gitBlame($result, $settings);
        }

        $output->writeResult($result, new ErrorFormatter($settings->colors, $translateTokens), $settings->ignoreFails);

        return $result;
    }

    /**
     * @param Output $output
     */
    public function setOutput(Output $output)
    {
        $this->output = $output;
    }

    /**
     * @param Settings $settings
     * @return Output
     */
    protected function getDefaultOutput(Settings $settings)
    {
        $writer = new ConsoleWriter;
        if ($settings->json) {
            return new JsonOutput($writer);
        } else {
            return ($settings->colors ? new TextOutputColored($writer) : new TextOutput($writer));
        }
    }

    /**
     * @param Result $result
     * @param Settings $settings
     * @throws Exception
     */
    protected function gitBlame(Result $result, Settings $settings)
    {
        if (!GitBlameProcess::gitExists($settings->gitExecutable)) {
            return;
        }

        foreach ($result->getErrors() as $error) {
            if ($error instanceof SyntaxError) {
                $process = new GitBlameProcess($settings->gitExecutable, $error->getFilePath(), $error->getLine());
                $process->waitForFinish();

                if ($process->isSuccess()) {
                    $blame = new Blame;
                    $blame->name = $process->getAuthor();
                    $blame->email = $process->getAuthorEmail();
                    $blame->datetime = $process->getAuthorTime();
                    $blame->commitHash = $process->getCommitHash();
                    $blame->summary = $process->getSummary();

                    $error->setBlame($blame);
                }
            }
        }
    }

    /**
     * @param array $paths
     * @param array $extensions
     * @param array $excluded
     * @return array
     * @throws NotExistsPathException
     */
    protected function getFilesFromPaths(array $paths, array $extensions, array $excluded = array())
    {
        $extensions = array_flip($extensions);
        $files = array();

        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;
            } elseif (is_dir($path)) {
                $iterator = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
                if (!empty($excluded)) {
                    $iterator = new RecursiveDirectoryFilterIterator($iterator, $excluded);
                }
                $iterator = new \RecursiveIteratorIterator(
                    $iterator,
                    \RecursiveIteratorIterator::LEAVES_ONLY,
                    \RecursiveIteratorIterator::CATCH_GET_CHILD
                );

                /** @var \SplFileInfo[] $iterator */
                foreach ($iterator as $directoryFile) {
                    if (isset($extensions[pathinfo($directoryFile->getFilename(), PATHINFO_EXTENSION)])) {
                        $files[] = (string) $directoryFile;
                    }
                }
            } else {
                throw new NotExistsPathException($path);
            }
        }

        $files = array_unique($files);

        return $files;
    }
}

class RecursiveDirectoryFilterIterator extends \RecursiveFilterIterator
{
    /** @var \RecursiveDirectoryIterator */
    private $iterator;

    /** @var array */
    private $excluded = array();

    /**
     * @param \RecursiveDirectoryIterator $iterator
     * @param array $excluded
     */
    public function __construct(\RecursiveDirectoryIterator $iterator, array $excluded)
    {
        parent::__construct($iterator);
        $this->iterator = $iterator;
        $this->excluded = array_map(array($this, 'getPathname'), $excluded);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Check whether the current element of the iterator is acceptable
     *
     * @link http://php.net/manual/en/filteriterator.accept.php
     * @return bool true if the current element is acceptable, otherwise false.
     */
    public function accept()
    {
        $current = $this->current()->getPathname();
        $current = $this->normalizeDirectorySeparator($current);

        if ('.' . DIRECTORY_SEPARATOR !== $current[0] . $current[1]) {
            $current = '.' . DIRECTORY_SEPARATOR . $current;
        }

        return !in_array($current, $this->excluded);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Check whether the inner iterator's current element has children
     *
     * @link http://php.net/manual/en/recursivefilteriterator.haschildren.php
     * @return bool true if the inner iterator has children, otherwise false
     */
    public function hasChildren()
    {
        return $this->iterator->hasChildren();
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Return the inner iterator's children contained in a RecursiveFilterIterator
     *
     * @link http://php.net/manual/en/recursivefilteriterator.getchildren.php
     * @return \RecursiveFilterIterator containing the inner iterator's children.
     */
    public function getChildren()
    {
        return new self($this->iterator->getChildren(), $this->excluded);
    }

    /**
     * @param string $file
     * @return string
     */
    private function getPathname($file)
    {
        $file = $this->normalizeDirectorySeparator($file);

        if ('.' . DIRECTORY_SEPARATOR !== $file[0] . $file[1]) {
            $file = '.' . DIRECTORY_SEPARATOR . $file;
        }

        $directoryFile = new \SplFileInfo($file);
        return $directoryFile->getPathname();
    }

    /**
     * @param string $file
     * @return string
     */
    private function normalizeDirectorySeparator($file)
    {
        return str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $file);
    }
}
