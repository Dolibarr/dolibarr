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

class Settings
{
    /**
     * Path to PHP executable
     * @var string
     */
    public $phpExecutable = 'php';

    /**
     * Check code inside PHP opening short tag <? or <?= in PHP 5.3
     * @var bool
     */
    public $shortTag = false;

    /**
     * Check PHP code inside ASP-style <% %> tags.
     * @var bool
     */
    public $aspTags = false;

    /**
     * Number of jobs running in same time
     * @var int
     */
    public $parallelJobs = 10;

    /**
     * If path contains directory, only file with these extensions are checked
     * @var array
     */
    public $extensions = array('php', 'phtml', 'php3', 'php4', 'php5');

    /**
     * Array of file or directories to check
     * @var array
     */
    public $paths = array();

    /**
     * Dont't check files or directories
     * @var array
     */
    public $excluded = array();

    /**
     * Print to console with colors
     * @var bool
     */
    public $colors = true;

    /**
     * Output results as JSON string
     * @var bool
     */
    public $json = false;

    /**
     * Read files and folder to tests from standard input (blocking)
     * @var bool
     */
    public $stdin = false;

    /**
     * Try to show git blame for row with error
     * @var bool
     */
    public $blame = false;

    /**
     * Path to git executable for blame
     * @var string
     */
    public $gitExecutable = 'git';

    /**
     * @var bool
     */
    public $ignoreFails = false;

    /**
     * @param array $paths
     */
    public function addPaths(array $paths)
    {
        $this->paths = array_merge($this->paths, $paths);
    }

    /**
     * @param array $arguments
     * @return Settings
     * @throws InvalidArgumentException
     */
    public static function parseArguments(array $arguments)
    {
        $arguments = new ArrayIterator(array_slice($arguments, 1));
        $settings = new self;

        foreach ($arguments as $argument) {
            if ($argument{0} !== '-') {
                $settings->paths[] = $argument;
            } else {
                switch ($argument) {
                    case '-p':
                        $settings->phpExecutable = $arguments->getNext();
                        break;

                    case '-s':
                    case '--short':
                        $settings->shortTag = true;
                        break;

                    case '-a':
                    case '--asp':
                        $settings->aspTags = true;
                        break;

                    case '--exclude':
                        $settings->excluded[] = $arguments->getNext();
                        break;

                    case '-e':
                        $settings->extensions = array_map('trim', explode(',', $arguments->getNext()));
                        break;

                    case '-j':
                        $settings->parallelJobs = max((int) $arguments->getNext(), 1);
                        break;

                    case '--no-colors':
                        $settings->colors = false;
                        break;

                    case '--json':
                        $settings->json = true;
                        break;

                    case '--git':
                        $settings->gitExecutable = $arguments->getNext();
                        break;

                    case '--stdin':
                        $settings->stdin = true;
                        break;

                    case '--blame':
                        $settings->blame = true;
                        break;

                    case '--ignore-fails':
                        $settings->ignoreFails = true;
                        break;

                    default:
                        throw new InvalidArgumentException($argument);
                }
            }
        }

        return $settings;
    }

    /**
     * @return array
     */
    public static function getPathsFromStdIn()
    {
        $content = stream_get_contents(STDIN);

        if (empty($content)) {
            return array();
        }

        $lines = explode("\n", rtrim($content));
        return array_map('rtrim', $lines);
    }
}

class ArrayIterator extends \ArrayIterator
{
    public function getNext()
    {
        $this->next();
        return $this->current();
    }
}
