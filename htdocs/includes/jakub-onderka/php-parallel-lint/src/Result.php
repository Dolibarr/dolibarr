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

class Result implements \JsonSerializable
{
    /** @var Error[] */
    private $errors;

    /** @var array */
    private $checkedFiles;

    /** @var array */
    private $skippedFiles;

    /** @var float */
    private $testTime;

    /**
     * @param Error[] $errors
     * @param array $checkedFiles
     * @param array $skippedFiles
     * @param float $testTime
     */
    public function __construct(array $errors, array $checkedFiles, array $skippedFiles, $testTime)
    {
        $this->errors = $errors;
        $this->checkedFiles = $checkedFiles;
        $this->skippedFiles = $skippedFiles;
        $this->testTime = $testTime;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return !empty($this->errors);
    }

    /**
     * @return array
     */
    public function getFilesWithFail()
    {
        $filesWithFail = array();
        foreach ($this->errors as $error) {
            if (!$error instanceof SyntaxError) {
                $filesWithFail[] = $error->getFilePath();
            }
        }

        return $filesWithFail;
    }

    /**
     * @return int
     */
    public function getFilesWithFailCount()
    {
        return count($this->getFilesWithFail());
    }

    /**
     * @return bool
     */
    public function hasFilesWithFail()
    {
        return $this->getFilesWithFailCount() !== 0;
    }

    /**
     * @return array
     */
    public function getCheckedFiles()
    {
        return $this->checkedFiles;
    }

    /**
     * @return int
     */
    public function getCheckedFilesCount()
    {
        return count($this->checkedFiles);
    }

    /**
     * @return array
     */
    public function getSkippedFiles()
    {
        return $this->skippedFiles;
    }

    /**
     * @return int
     */
    public function getSkippedFilesCount()
    {
        return count($this->skippedFiles);
    }

    /**
     * @return array
     */
    public function getFilesWithSyntaxError()
    {
        $filesWithSyntaxError = array();
        foreach ($this->errors as $error) {
            if ($error instanceof SyntaxError) {
                $filesWithSyntaxError[] = $error->getFilePath();
            }
        }

        return $filesWithSyntaxError;
    }

    /**
     * @return int
     */
    public function getFilesWithSyntaxErrorCount()
    {
        return count($this->getFilesWithSyntaxError());
    }

    /**
     * @return bool
     */
    public function hasSyntaxError()
    {
        return $this->getFilesWithSyntaxErrorCount() !== 0;
    }

    /**
     * @return float
     */
    public function getTestTime()
    {
        return $this->testTime;
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    function jsonSerialize()
    {
        return array(
            'checkedFiles' => $this->getCheckedFiles(),
            'filesWithSyntaxError' => $this->getFilesWithSyntaxError(),
            'skippedFiles' => $this->getSkippedFiles(),
            'errors' => $this->getErrors(),
        );
    }


}