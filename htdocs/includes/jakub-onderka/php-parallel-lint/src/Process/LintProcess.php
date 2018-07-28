<?php
namespace JakubOnderka\PhpParallelLint\Process;

class LintProcess extends PhpProcess
{
    /**
     * @param PhpExecutable $phpExecutable
     * @param string $fileToCheck Path to file to check
     * @param bool $aspTags
     * @param bool $shortTag
     */
    public function __construct(PhpExecutable $phpExecutable, $fileToCheck, $aspTags = false, $shortTag = false)
    {
        if (empty($fileToCheck)) {
            throw new \InvalidArgumentException("File to check must be set.");
        }

        $parameters = array(
            '-d asp_tags=' . ($aspTags ? 'On' : 'Off'),
            '-d short_open_tag=' . ($shortTag ? 'On' : 'Off'),
            '-d error_reporting=E_ALL',
            '-n',
            '-l',
            escapeshellarg($fileToCheck),
        );

        parent::__construct($phpExecutable, $parameters);
    }

    /**
     * @return bool
     */
    public function hasSyntaxError()
    {
        return strpos($this->getOutput(), 'Fatal error') !== false ||
        strpos($this->getOutput(), 'Parse error') !== false;
    }

    /**
     * @return bool|string
     */
    public function getSyntaxError()
    {
        if ($this->hasSyntaxError()) {
            list(, $out) = explode("\n", $this->getOutput());
            return $out;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isFail()
    {
        return defined('PHP_WINDOWS_VERSION_MAJOR') ? $this->getStatusCode() === 1 : parent::isFail();
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->getStatusCode() === 0;
    }
}