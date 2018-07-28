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

class Error implements \JsonSerializable
{
    /** @var string */
    protected $filePath;

    /** @var string */
    protected $message;

    /**
     * @param string $filePath
     * @param string $message
     */
    public function __construct($filePath, $message)
    {
        $this->filePath = $filePath;
        $this->message = rtrim($message);
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @return string
     */
    public function getShortFilePath()
    {
        return str_replace(getcwd(), '', $this->filePath);
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return array(
            'type' => 'error',
            'file' => $this->getFilePath(),
            'message' => $this->getMessage(),
        );
    }
}

class Blame implements \JsonSerializable
{
    public $name;

    public $email;

    /** @var \DateTime */
    public $datetime;

    public $commitHash;

    public $summary;

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
            'name' => $this->name,
            'email' => $this->email,
            'datetime' => $this->datetime,
            'commitHash' => $this->commitHash,
            'summary' => $this->summary,
        );
    }


}

class SyntaxError extends Error
{
    /** @var Blame */
    private $blame;

    /**
     * @return int|null
     */
    public function getLine()
    {
        preg_match('~on line ([0-9]*)~', $this->message, $matches);

        if ($matches && isset($matches[1])) {
            $onLine = (int) $matches[1];
            return $onLine;
        }

        return null;
    }

    /**
     * @param bool $translateTokens
     * @return mixed|string
     */
    public function getNormalizedMessage($translateTokens = false)
    {
        $message = preg_replace('~(Parse|Fatal) error: syntax error, ~', '', $this->message);
        $message = ucfirst($message);
        $message = preg_replace('~ in (.*) on line [0-9]*~', '', $message);

        if ($translateTokens) {
            $message = $this->translateTokens($message);
        }

        return $message;
    }

    /**
     * @param Blame $blame
     */
    public function setBlame(Blame $blame)
    {
        $this->blame = $blame;
    }

    /**
     * @return Blame
     */
    public function getBlame()
    {
        return $this->blame;
    }

    /**
     * @param string $message
     * @return string
     */
    protected function translateTokens($message)
    {
        static $translateTokens = array(
            'T_FILE' => '__FILE__',
            'T_FUNC_C' => '__FUNCTION__',
            'T_HALT_COMPILER' => '__halt_compiler()',
            'T_INC' => '++',
            'T_IS_EQUAL' => '==',
            'T_IS_GREATER_OR_EQUAL' => '>=',
            'T_IS_IDENTICAL' => '===',
            'T_IS_NOT_IDENTICAL' => '!==',
            'T_IS_SMALLER_OR_EQUAL' => '<=',
            'T_LINE' => '__LINE__',
            'T_METHOD_C' => '__METHOD__',
            'T_MINUS_EQUAL' => '-=',
            'T_MOD_EQUAL' => '%=',
            'T_MUL_EQUAL' => '*=',
            'T_NS_C' => '__NAMESPACE__',
            'T_NS_SEPARATOR' => '\\',
            'T_OBJECT_OPERATOR' => '->',
            'T_OR_EQUAL' => '|=',
            'T_PAAMAYIM_NEKUDOTAYIM' => '::',
            'T_PLUS_EQUAL' => '+=',
            'T_SL' => '<<',
            'T_SL_EQUAL' => '<<=',
            'T_SR' => '>>',
            'T_SR_EQUAL' => '>>=',
            'T_START_HEREDOC' => '<<<',
            'T_XOR_EQUAL' => '^=',
            'T_ECHO' => 'echo'
        );

        return preg_replace_callback('~T_([A-Z_]*)~', function ($matches) use ($translateTokens) {
            list($tokenName) = $matches;
            if (isset($translateTokens[$tokenName])) {
                $operator = $translateTokens[$tokenName];
                return "$operator ($tokenName)";
            }

            return $tokenName;
        }, $message);
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return array(
            'type' => 'syntaxError',
            'file' => $this->getFilePath(),
            'line' => $this->getLine(),
            'message' => $this->getMessage(),
            'normalizeMessage' => $this->getNormalizedMessage(),
            'blame' => $this->blame,
        );
    }
}