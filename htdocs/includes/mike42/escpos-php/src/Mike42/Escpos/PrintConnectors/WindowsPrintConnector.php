<?php
/**
 * This file is part of escpos-php: PHP receipt printer library for use with
 * ESC/POS-compatible thermal and impact printers.
 *
 * Copyright (c) 2014-18 Michael Billington < michael.billington@gmail.com >,
 * incorporating modifications by others. See CONTRIBUTORS.md for a full list.
 *
 * This software is distributed under the terms of the MIT license. See LICENSE.md
 * for details.
 */

namespace Mike42\Escpos\PrintConnectors;

use Exception;
use BadMethodCallException;

/**
 * Connector for sending print jobs to
 * - local ports on windows (COM1, LPT1, etc)
 * - shared (SMB) printers from any platform (smb://server/foo)
 * For USB printers or other ports, the trick is to share the printer with a
 * generic text driver, then connect to the shared printer locally.
 */
class WindowsPrintConnector implements PrintConnector
{
    /**
     * @var array $buffer
     *  Accumulated lines of output for later use.
     */
    private $buffer;

    /**
     * @var string $hostname
     *  The hostname of the target machine, or null if this is a local connection.
     */
    private $hostname;

    /**
     * @var boolean $isLocal
     *  True if a port is being used directly (must be Windows), false if network shares will be used.
     */
    private $isLocal;

    /**
     * @var int $platform
     *  Platform we're running on, for selecting different commands. See PLATFORM_* constants.
     */
    private $platform;

    /**
     * @var string $printerName
     *  The name of the target printer (eg "Foo Printer") or port ("COM1", "LPT1").
     */
    private $printerName;

    /**
     * @var string $userName
     *  Login name for network printer, or null if not using authentication.
     */
    private $userName;

    /**
     * @var string $userPassword
     *  Password for network printer, or null if no password is required.
     */
    private $userPassword;

    /**
     * @var string $workgroup
     *  Workgroup that the printer is located on
     */
    private $workgroup;

    /**
     * Represents Linux
     */
    const PLATFORM_LINUX = 0;

    /**
     * Represents Mac
     */
    const PLATFORM_MAC = 1;

    /**
     * Represents Windows
     */
    const PLATFORM_WIN = 2;

    /**
     * Valid local ports.
     */
    const REGEX_LOCAL = "/^(LPT\d|COM\d)$/";

    /**
     * Valid printer name.
     */
    const REGEX_PRINTERNAME = "/^[\d\w-]+(\s[\d\w-]+)*$/";

    /**
     * Valid smb:// URI containing hostname & printer with optional user & optional password only.
     */
    const REGEX_SMB = "/^smb:\/\/([\s\d\w-]+(:[\s\d\w+-]+)?@)?([\d\w-]+\.)*[\d\w-]+\/([\d\w-]+\/)?[\d\w-]+(\s[\d\w-]+)*$/";

    /**
     * @param string $dest
     * @throws BadMethodCallException
     */
    public function __construct($dest)
    {
        $this -> platform = $this -> getCurrentPlatform();
        $this -> isLocal = false;
        $this -> buffer = null;
        $this -> userName = null;
        $this -> userPassword = null;
        $this -> workgroup = null;
        if (preg_match(self::REGEX_LOCAL, $dest) == 1) {
            // Straight to LPT1, COM1 or other local port. Allowed only if we are actually on windows.
            if ($this -> platform !== self::PLATFORM_WIN) {
                throw new BadMethodCallException("WindowsPrintConnector can only be " .
                    "used to print to a local printer ('".$dest."') on a Windows computer.");
            }
            $this -> isLocal = true;
            $this -> hostname = null;
            $this -> printerName = $dest;
        } elseif (preg_match(self::REGEX_SMB, $dest) == 1) {
            // Connect to samba share, eg smb://host/printer
            $part = parse_url($dest);
            $this -> hostname = $part['host'];
            /* Printer name and optional workgroup */
            $path = ltrim($part['path'], '/');
            if (strpos($path, "/") !== false) {
                $pathPart = explode("/", $path);
                $this -> workgroup = $pathPart[0];
                $this -> printerName = $pathPart[1];
            } else {
                $this -> printerName = $path;
            }
            /* Username and password if set */
            if (isset($part['user'])) {
                $this -> userName = $part['user'];
                if (isset($part['pass'])) {
                    $this -> userPassword = $part['pass'];
                }
            }
        } elseif (preg_match(self::REGEX_PRINTERNAME, $dest) == 1) {
            // Just got a printer name. Assume it's on the current computer.
            $hostname = gethostname();
            if (!$hostname) {
                $hostname = "localhost";
            }
            $this -> hostname = $hostname;
            $this -> printerName = $dest;
        } else {
            throw new BadMethodCallException("Printer '" . $dest . "' is not a valid " .
                "printer name. Use local port (LPT1, COM1, etc) or smb://computer/printer notation.");
        }
        $this -> buffer = [];
    }

    public function __destruct()
    {
        if ($this -> buffer !== null) {
            trigger_error("Print connector was not finalized. Did you forget to close the printer?", E_USER_NOTICE);
        }
    }

    public function finalize()
    {
        $data = implode($this -> buffer);
        $this -> buffer = null;
        if ($this -> platform == self::PLATFORM_WIN) {
            $this -> finalizeWin($data);
        } elseif ($this -> platform == self::PLATFORM_LINUX) {
            $this -> finalizeLinux($data);
        } else {
            $this -> finalizeMac($data);
        }
    }

    /**
     * Send job to printer -- platform-specific Linux code.
     *
     * @param string $data Print data
     * @throws Exception
     */
    protected function finalizeLinux($data)
    {
        /* Non-Windows samba printing */
        $device = "//" . $this -> hostname . "/" . $this -> printerName;
        if ($this -> userName !== null) {
            $user = ($this -> workgroup != null ? ($this -> workgroup . "\\") : "") . $this -> userName;
            if ($this -> userPassword == null) {
                // No password
                $command = sprintf(
                    "smbclient %s -U %s -c %s -N -m SMB2",
                    escapeshellarg($device),
                    escapeshellarg($user),
                    escapeshellarg("print -")
                );
                $redactedCommand = $command;
            } else {
                // With password
                $command = sprintf(
                    "smbclient %s %s -U %s -c %s -m SMB2",
                    escapeshellarg($device),
                    escapeshellarg($this -> userPassword),
                    escapeshellarg($user),
                    escapeshellarg("print -")
                );
                $redactedCommand = sprintf(
                    "smbclient %s %s -U %s -c %s -m SMB2",
                    escapeshellarg($device),
                    escapeshellarg("*****"),
                    escapeshellarg($user),
                    escapeshellarg("print -")
                );
            }
        } else {
            // No authentication information at all
            $command = sprintf(
                "smbclient %s -c %s -N -m SMB2",
                escapeshellarg($device),
                escapeshellarg("print -")
            );
            $redactedCommand = $command;
        }
        $retval = $this -> runCommand($command, $outputStr, $errorStr, $data);
        if ($retval != 0) {
            throw new Exception("Failed to print. Command \"$redactedCommand\" " .
                "failed with exit code $retval: " . trim($errorStr) . trim($outputStr));
        }
    }

    /**
     * Send job to printer -- platform-specific Mac code.
     *
     * @param string $data Print data
     * @throws Exception
     */
    protected function finalizeMac($data)
    {
        throw new Exception("Mac printing not implemented.");
    }

    /**
     * Send data to printer -- platform-specific Windows code.
     *
     * @param string $data
     */
    protected function finalizeWin($data)
    {
        /* Windows-friendly printing of all sorts */
        if (!$this -> isLocal) {
            /* Networked printing */
            $device = "\\\\" . $this -> hostname . "\\" . $this -> printerName;
            if ($this -> userName !== null) {
                /* Log in */
                $user = "/user:" . ($this -> workgroup != null ? ($this -> workgroup . "\\") : "") . $this -> userName;
                if ($this -> userPassword == null) {
                    $command = sprintf(
                        "net use %s %s",
                        escapeshellarg($device),
                        escapeshellarg($user)
                    );
                    $redactedCommand = $command;
                } else {
                    $command = sprintf(
                        "net use %s %s %s",
                        escapeshellarg($device),
                        escapeshellarg($user),
                        escapeshellarg($this -> userPassword)
                    );
                    $redactedCommand = sprintf(
                        "net use %s %s %s",
                        escapeshellarg($device),
                        escapeshellarg($user),
                        escapeshellarg("*****")
                    );
                }
                $retval = $this -> runCommand($command, $outputStr, $errorStr);
                if ($retval != 0) {
                    throw new Exception("Failed to print. Command \"$redactedCommand\" " .
                        "failed with exit code $retval: " . trim($errorStr));
                }
            }
            /* Final print-out */
            $filename = tempnam(sys_get_temp_dir(), "escpos");
            file_put_contents($filename, $data);
            if (!$this -> runCopy($filename, $device)) {
                throw new Exception("Failed to copy file to printer");
            }
            unlink($filename);
        } else {
            /* Drop data straight on the printer */
            if (!$this -> runWrite($data, $this -> printerName)) {
                throw new Exception("Failed to write file to printer at " . $this -> printerName);
            }
        }
    }

    /**
     * @return string Current platform. Separated out for testing purposes.
     */
    protected function getCurrentPlatform()
    {
        if (PHP_OS == "WINNT") {
            return self::PLATFORM_WIN;
        }
        if (PHP_OS == "Darwin") {
            return self::PLATFORM_MAC;
        }
        return self::PLATFORM_LINUX;
    }

    /* (non-PHPdoc)
     * @see PrintConnector::read()
     */
    public function read($len)
    {
        /* Two-way communication is not supported */
        return false;
    }

    /**
     * Run a command, pass it data, and retrieve its return value, standard output, and standard error.
     *
     * @param string $command the command to run.
     * @param string $outputStr variable to fill with standard output.
     * @param string $errorStr variable to fill with standard error.
     * @param string $inputStr text to pass to the command's standard input (optional).
     * @return number
     */
    protected function runCommand($command, &$outputStr, &$errorStr, $inputStr = null)
    {
        $descriptors = [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"],
        ];
        $process = proc_open($command, $descriptors, $fd);
        if (is_resource($process)) {
            /* Write to input */
            if ($inputStr !== null) {
                fwrite($fd[0], $inputStr);
            }
            fclose($fd[0]);
            /* Read stdout */
            $outputStr = stream_get_contents($fd[1]);
            fclose($fd[1]);
            /* Read stderr */
            $errorStr = stream_get_contents($fd[2]);
            fclose($fd[2]);
            /* Finish up */
            $retval = proc_close($process);
            return $retval;
        } else {
            /* Method calling this should notice a non-zero exit and print an error */
            return -1;
        }
    }

    /**
     * Copy a file. Separated out so that nothing is actually printed during test runs.
     *
     * @param string $from Source file
     * @param string $to Destination file
     * @return boolean True if copy was successful, false otherwise
     */
    protected function runCopy($from, $to)
    {
        return copy($from, $to);
    }

    /**
     * Write data to a file. Separated out so that nothing is actually printed during test runs.
     *
     * @param string $data Data to print
     * @param string $filename Destination file
         * @return boolean True if write was successful, false otherwise
     */
    protected function runWrite($data, $filename)
    {
        return file_put_contents($filename, $data) !== false;
    }

    public function write($data)
    {
        $this -> buffer[] = $data;
    }
}
