<?php
namespace JakubOnderka\PhpParallelLint;

/*
Copyright (c) 2014, Jakub Onderka
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

use JakubOnderka\PhpConsoleColor\ConsoleColor;
use JakubOnderka\PhpConsoleHighlighter\Highlighter;

class ErrorFormatter
{
    /** @var bool */
    private $useColors;

    /** @var bool */
    private $translateTokens;

    public function __construct($useColors = false, $translateTokens = false)
    {
        $this->useColors = $useColors;
        $this->translateTokens = $translateTokens;
    }

    /**
     * @param Error $error
     * @return string
     */
    public function format(Error $error)
    {
        if ($error instanceof SyntaxError) {
            return $this->formatSyntaxErrorMessage($error);
        } else {
            if ($error->getMessage()) {
                return $error->getMessage();
            } else {
                return "Unknown error for file '{$error->getFilePath()}'.";
            }
        }
    }

    /**
     * @param SyntaxError $error
     * @param bool $withCodeSnipped
     * @return string
     */
    public function formatSyntaxErrorMessage(SyntaxError $error, $withCodeSnipped = true)
    {
        $string = "Parse error: {$error->getShortFilePath()}";

        if ($error->getLine()) {
            $onLine = $error->getLine();
            $string .= ":$onLine" . PHP_EOL;

            if ($withCodeSnipped) {
                if ($this->useColors) {
                    $string .= $this->getColoredCodeSnippet($error->getFilePath(), $onLine);
                } else {
                    $string .= $this->getCodeSnippet($error->getFilePath(), $onLine);
                }
            }
        }

        $string .= $error->getNormalizedMessage($this->translateTokens);

        if ($error->getBlame()) {
            $blame = $error->getBlame();
            $shortCommitHash = substr($blame->commitHash, 0, 8);
            $dateTime = $blame->datetime->format('c');
            $string .= PHP_EOL . "Blame {$blame->name} <{$blame->email}>, commit '$shortCommitHash' from $dateTime";
        }

        return $string;
    }

    /**
     * @param string $filePath
     * @param int $lineNumber
     * @param int $linesBefore
     * @param int $linesAfter
     * @return string
     */
    protected function getCodeSnippet($filePath, $lineNumber, $linesBefore = 2, $linesAfter = 2)
    {
        $lines = file($filePath);

        $offset = $lineNumber - $linesBefore - 1;
        $offset = max($offset, 0);
        $length = $linesAfter + $linesBefore + 1;
        $lines = array_slice($lines, $offset, $length, $preserveKeys = true);

        end($lines);
        $lineStrlen = strlen(key($lines) + 1);

        $snippet = '';
        foreach ($lines as $i => $line) {
            $snippet .= ($lineNumber === $i + 1 ? '  > ' : '    ');
            $snippet .= str_pad($i + 1, $lineStrlen, ' ', STR_PAD_LEFT) . '| ' . rtrim($line) . PHP_EOL;
        }

        return $snippet;
    }

    /**
     * @param string $filePath
     * @param int $lineNumber
     * @param int $linesBefore
     * @param int $linesAfter
     * @return string
     */
    protected function getColoredCodeSnippet($filePath, $lineNumber, $linesBefore = 2, $linesAfter = 2)
    {
        if (
            !class_exists('\JakubOnderka\PhpConsoleHighlighter\Highlighter') ||
            !class_exists('\JakubOnderka\PhpConsoleColor\ConsoleColor')
        ) {
            return $this->getCodeSnippet($filePath, $lineNumber, $linesBefore, $linesAfter);
        }

        $colors = new ConsoleColor();
        $colors->setForceStyle(true);
        $highlighter = new Highlighter($colors);

        $fileContent = file_get_contents($filePath);
        return $highlighter->getCodeSnippet($fileContent, $lineNumber, $linesBefore, $linesAfter);
    }
}