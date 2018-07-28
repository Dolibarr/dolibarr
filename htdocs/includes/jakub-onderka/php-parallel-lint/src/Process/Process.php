<?php

namespace JakubOnderka\PhpParallelLint\Process;

use JakubOnderka\PhpParallelLint\RunTimeException;

class Process
{
    const STDIN = 0,
        STDOUT = 1,
        STDERR = 2;

    const READ = 'r',
        WRITE = 'w';

    /** @var resource */
    protected $process;

    /** @var resource */
    protected $stdout;

    /** @var resource */
    protected $stderr;

    /** @var string */
    private $output;

    /** @var string */
    private $errorOutput;

    /** @var int */
    private $statusCode;

    /**
     * @param string $cmdLine
     * @param string $stdInInput
     * @throws RunTimeException
     */
    public function __construct($cmdLine, $stdInInput = null)
    {
        $descriptors = array(
            self::STDIN  => array('pipe', self::READ),
            self::STDOUT => array('pipe', self::WRITE),
            self::STDERR => array('pipe', self::WRITE),
        );

        $this->process = proc_open($cmdLine, $descriptors, $pipes, null, null, array('bypass_shell' => true));

        if ($this->process === false) {
            throw new RunTimeException("Cannot create new process $cmdLine");
        }

        list($stdin, $this->stdout, $this->stderr) = $pipes;

        if ($stdInInput) {
            fwrite($stdin, $stdInInput);
        }

        fclose($stdin);
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        if ($this->statusCode !== NULL) {
            return true;
        }

        $status = proc_get_status($this->process);

        if ($status['running']) {
            return false;
        } elseif ($this->statusCode === null) {
            $this->statusCode = (int) $status['exitcode'];
        }

        // Process outputs
        $this->output = stream_get_contents($this->stdout);
        fclose($this->stdout);

        $this->errorOutput = stream_get_contents($this->stderr);
        fclose($this->stderr);

        $statusCode = proc_close($this->process);

        if ($this->statusCode === null) {
            $this->statusCode = $statusCode;
        }

        $this->process = null;

        return true;
    }

    public function waitForFinish()
    {
        while (!$this->isFinished()) {
            usleep(100);
        }
    }

    /**
     * @return string
     * @throws RunTimeException
     */
    public function getOutput()
    {
        if (!$this->isFinished()) {
            throw new RunTimeException("Cannot get output for running process");
        }

        return $this->output;
    }

    /**
     * @return string
     * @throws RunTimeException
     */
    public function getErrorOutput()
    {
        if (!$this->isFinished()) {
            throw new RunTimeException("Cannot get error output for running process");
        }

        return $this->errorOutput;
    }

    /**
     * @return int
     * @throws RunTimeException
     */
    public function getStatusCode()
    {
        if (!$this->isFinished()) {
            throw new RunTimeException("Cannot get status code for running process");
        }

        return $this->statusCode;
    }

    /**
     * @return bool
     */
    public function isFail()
    {
        return $this->getStatusCode() === 1;
    }
}
