<?php
/**
 * escpos-php, a Thermal receipt printer library, for use with
 * ESC/POS compatible printers.
 *
 * Copyright (c) 2014-2015 Michael Billington <michael.billington@gmail.com>,
 * 	incorporating modifications by:
 *  - Roni Saha <roni.cse@gmail.com>
 *  - Gergely Radics <gerifield@ustream.tv>
 *  - Warren Doyle <w.doyle@fuelled.co>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * 
 * Connector for sending print jobs to
 * - local ports on windows (COM1, LPT1, etc)
 * - shared (SMB) printers from any platform (\\server\foo)
 * For USB printers or other ports, the trick is to share the printer with a generic text driver, then access it locally.
 */
class WindowsPrintConnector implements PrintConnector {
	/**
	 * @var array Accumulated lines of output for later use.
	 */
	private $buffer;

	/**
	 * @var string The hostname of the target machine, or null if this is a local connection.
	 */
	private $hostname;

	/**
	 * @var boolean True if a port is being used directly (must be Windows), false if network shares will be used.
	 */
	private $isLocal;

	/**
	 * @var int Platform we're running on, for selecting different commands. See PLATFORM_* constants.
	 */
	private $platform;

	/**
	 * @var string The name of the target printer (eg "Foo Printer") or port ("COM1", "LPT1").
	 */
	private $printerName;

	/**
	 * @var string Login name for network printer, or null if not using authentication.
	 */
	private $userName;

	/**
	 * @var string Password for network printer, or null if no password is required.
	 */
	private $userPassword;

	/**
	 * @var string Workgroup that the printer is located on
	 */
	private $workgroup;

	/**
	 * @var int represents Linux
	 */
	const PLATFORM_LINUX = 0;

	/**
	 * @var int represents Mac
	 */
	const PLATFORM_MAC = 1;

	/**
	 * @var int represents Windows
	 */
	const PLATFORM_WIN = 2;

	/**
	 * @var string Valid local ports.
	 */
	const REGEX_LOCAL = "/^(LPT\d|COM\d)$/";

	/**
	 * @var string Valid printer name.
	 */
	const REGEX_PRINTERNAME = "/^[\w-]+(\s[\w-]+)*$/";

	/**
	 * @var string Valid smb:// URI containing hostname & printer with optional user & optional password only.
	 */
	const REGEX_SMB = "/^smb:\/\/([\s\w-]+(:[\s\w-]+)?@)?[\w-]+\/([\w-]+\/)?[\w-]+(\s[\w-]+)*$/";

	/**
	 * @param string $dest
	 * @throws BadMethodCallException
	 */
	public function __construct($dest) {
		$this -> platform = $this -> getCurrentPlatform();	
		$this -> isLocal = false;
		$this -> buffer = null;
		$this -> userName = null;
		$this -> userPassword = null;
		$this -> workgroup = null;
		if(preg_match(self::REGEX_LOCAL, $dest) == 1) {
			// Straight to LPT1, COM1 or other local port. Allowed only if we are actually on windows.
			if($this -> platform !== self::PLATFORM_WIN) {
				throw new BadMethodCallException("WindowsPrintConnector can only be used to print to a local printer ('".$dest."') on a Windows computer.");
			}
			$this -> isLocal = true;
			$this -> hostname = null;
			$this -> printerName = $dest;
		} else if(preg_match(self::REGEX_SMB, $dest) == 1) {
			// Connect to samba share, eg smb://host/printer
			$part = parse_url($dest);
			$this -> hostname = $part['host'];
			/* Printer name and optional workgroup */
			$path = ltrim($part['path'], '/');
			if(strpos($path, "/") !== false) {
				$pathPart = explode("/", $path);
				$this -> workgroup = $pathPart[0];
				$this -> printerName = $pathPart[1];
			} else {
				$this -> printerName = $path;
			}
			/* Username and password if set */
			if(isset($part['user'])) {
				$this -> userName = $part['user'];
				if(isset($part['pass'])) {
					$this -> userPassword = $part['pass'];
				}
			}
		} else if(preg_match(self::REGEX_PRINTERNAME, $dest) == 1) {
			// Just got a printer name. Assume it's on the current computer.
			$hostname = gethostname();
			if(!$hostname) {
				$hostname = "localhost";
			}
			$this -> hostname = $hostname;
			$this -> printerName = $dest;
		} else {
			throw new BadMethodCallException("Printer '" . $dest . "' is not a valid printer name. Use local port (LPT1, COM1, etc) or smb://computer/printer notation.");
		}
		$this -> buffer = array();
	}

	public function __destruct() {
		if($this -> buffer !== null) {
			trigger_error("Print connector was not finalized. Did you forget to close the printer?", E_USER_NOTICE);
		}
	}

	public function finalize() {
		$data = implode($this -> buffer);
		$this -> buffer = null;
		if($this -> platform == self::PLATFORM_WIN) {
			$this -> finalizeWin($data);
		} else if($this -> platform == self::PLATFORM_LINUX) {
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
	protected function finalizeLinux($data) {
		/* Non-Windows samba printing */
		$device = "//" . $this -> hostname . "/" . $this -> printerName;
		if($this -> userName !== null) {
			$user = ($this -> workgroup != null ? ($this -> workgroup . "\\") : "") . $this -> userName;
			if($this -> userPassword == null) {
				// No password
				$command = sprintf("smbclient %s -U %s -c %s -N",
						escapeshellarg($device),
						escapeshellarg($user),
						escapeshellarg("print -"));
				$redactedCommand = $command;
			} else {
				// With password
				$command = sprintf("smbclient %s %s -U %s -c %s",
						escapeshellarg($device),
						escapeshellarg($this -> userPassword),
						escapeshellarg($user),
						escapeshellarg("print -"));
				$redactedCommand = sprintf("smbclient %s %s -U %s -c %s",
						escapeshellarg($device),
						escapeshellarg("*****"),
						escapeshellarg($user),
						escapeshellarg("print -"));
			}
		} else {
			// No authentication information at all
			$command = sprintf("smbclient %s -c %s -N",
					escapeshellarg($device),
					escapeshellarg("print -"));
			$redactedCommand = $command;
		}
		$retval = $this -> runCommand($command, $outputStr, $errorStr, $data);
		if($retval != 0) {
			throw new Exception("Failed to print. Command \"$redactedCommand\" failed with exit code $retval: " . trim($outputStr));
		}
	}
	
	protected function finalizeMac($data) {
		throw new Exception("Mac printing not implemented.");
	}
	
	/**
	 * Send data to printer -- platform-specific Windows code.
	 * 
	 * @param string $data
	 */
	protected function finalizeWin($data) {
		/* Windows-friendly printing of all sorts */
		if(!$this -> isLocal) {
			/* Networked printing */
			$device = "\\\\" . $this -> hostname . "\\" . $this -> printerName;
			if($this -> userName !== null) {
				/* Log in */
				$user = "/user:" . ($this -> workgroup != null ? ($this -> workgroup . "\\") : "") . $this -> userName;
				if($this -> userPassword == null) {
					$command = sprintf("net use %s %s",
							escapeshellarg($device),
							escapeshellarg($user));
					$redactedCommand = $command;
				} else {
					$command = sprintf("net use %s %s %s",
							escapeshellarg($device),
							escapeshellarg($user),
							escapeshellarg($this -> userPassword));
					$redactedCommand = sprintf("net use %s %s %s",
							escapeshellarg($device),
							escapeshellarg($user),
							escapeshellarg("*****"));
				}
				$retval = $this -> runCommand($command, $outputStr, $errorStr);
				if($retval != 0) {
					throw new Exception("Failed to print. Command \"$redactedCommand\" failed with exit code $retval: " . trim($errorStr));
				}
			}
			/* Final print-out */
			$filename = tempnam(sys_get_temp_dir(), "escpos");
			file_put_contents($filename, $data);
			if(!$this -> runCopy($filename, $device)){
				throw new Exception("Failed to copy file to printer");
			}
			unlink($filename);
		} else {
			/* Drop data straight on the printer */
			if(!$this -> runWrite($data,  $this -> printerName)) {
				throw new Exception("Failed to write file to printer at " . $this -> printerName);
			}
		}
	}
	
	/**
	 * @return string Current platform. Separated out for testing purposes.
	 */
	protected function getCurrentPlatform() {
		if(PHP_OS == "WINNT") {
			return self::PLATFORM_WIN;
		}
		if(PHP_OS == "Darwin") {
			return self::PLATFORM_MAC;
		}
		return self::PLATFORM_LINUX;
	}
	
	/* (non-PHPdoc)
	 * @see PrintConnector::read()
	 */
	public function read($len) {
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
	protected function runCommand($command, &$outputStr, &$errorStr, $inputStr = null) {
		$descriptors = array(
				0 => array("pipe", "r"),
				1 => array("pipe", "w"),
				2 => array("pipe", "w"),
		);
		$process = proc_open($command, $descriptors, $fd);
		if (is_resource($process)) {
			/* Write to input */
			if($inputStr !== null) {
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
	protected function runCopy($from, $to) {
		return copy($from, $to);
	}
	
	/**
	 * Write data to a file. Separated out so that nothing is actually printed during test runs.
	 * 
	 * @param string $data Data to print
	 * @param string $to Destination file
         * @return boolean True if write was successful, false otherwise
	 */
	protected function runWrite($data, $to) {
		return file_put_contents($data, $to) !== false;
	}

	public function write($data) {
		$this -> buffer[] = $data;
	}
}
