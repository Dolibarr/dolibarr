<?php
namespace mikehaertl\shellcommand;

/**
 * Command
 *
 * This class represents a shell command.
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @version 1.2.2
 * @license http://www.opensource.org/licenses/MIT
 */
class Command
{
    /**
     * @var bool whether to escape any argument passed through `addArg()`. Default is `true`.
     */
    public $escapeArgs = true;

    /**
     * @var bool whether to escape the command passed to `setCommand()` or the constructor.
     * This is only useful if `$escapeArgs` is `false`. Default is `false`.
     */
    public $escapeCommand = false;

    /**
     * @var bool whether to use `exec()` instead of `proc_open()`. This can be used on Windows system
     * to workaround some quirks there. Note, that any errors from your command will be output directly
     * to the PHP output stream. `getStdErr()` will also not work anymore and thus you also won't get
     * the error output from `getError()` in this case. You also can't pass any environment
     * variables to the command if this is enabled. Default is `false`.
     */
    public $useExec = false;

    /**
     * @var bool whether to capture stderr (2>&1) when `useExec` is true. This will try to redirect the
     * stderr to stdout and provide the complete output of both in `getStdErr()` and `getError()`.
     * Default is `true`.
     */
    public $captureStdErr = true;

    /**
     * @var string|null the initial working dir for `proc_open()`. Default is `null` for current PHP working dir.
     */
    public $procCwd;

    /**
     * @var array|null an array with environment variables to pass to `proc_open()`. Default is `null` for none.
     */
    public $procEnv;

    /**
     * @var array|null an array of other_options for `proc_open()`. Default is `null` for none.
     */
    public $procOptions;

    /**
     * @var null|string the locale to temporarily set before calling `escapeshellargs()`. Default is `null` for none.
     */
    public $locale;

    /**
     * @var string the command to execute
     */
    protected $_command;

    /**
     * @var array the list of command arguments
     */
    protected $_args = array();

    /**
     * @var string the full command string to execute
     */
    protected $_execCommand;

    /**
     * @var string the stdout output
     */
    protected $_stdOut = '';

    /**
     * @var string the stderr output
     */
    protected $_stdErr = '';

    /**
     * @var int the exit code
     */
    protected $_exitCode;

    /**
     * @var string the error message
     */
    protected $_error = '';

    /**
     * @var bool whether the command was successfully executed
     */
    protected $_executed = false;

    /**
     * @param string|array $options either a command string or an options array (see setOptions())
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        } elseif (is_string($options)) {
            $this->setCommand($options);
        }
    }

    /**
     * @param array $options array of name => value options that should be applied to the object
     * You can also pass options that use a setter, e.g. you can pass a `fileName` option which
     * will be passed to `setFileName()`.
     * @throws \Exception
     * @return static for method chaining
     */
    public function setOptions($options)
    {
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            } else {
                $method = 'set'.ucfirst($key);
                if (method_exists($this, $method)) {
                    call_user_func(array($this,$method), $value);
                } else {
                    throw new \Exception("Unknown configuration option '$key'");
                }
            }
        }
        return $this;
    }

    /**
     * @param string $command the command or full command string to execute, like 'gzip' or 'gzip -d'.
     * You can still call addArg() to add more arguments to the command. If $escapeCommand was set to true,
     * the command gets escaped through escapeshellcmd().
     * @return static for method chaining
     */
    public function setCommand($command)
    {
        if ($this->escapeCommand) {
            $command = escapeshellcmd($command);
        }
        if ($this->getIsWindows()) {
            // Make sure to switch to correct drive like "E:" first if we have a full path in command
            if (isset($command[1]) && $command[1]===':') {
                $position = 1;
                // Could be a quoted absolute path because of spaces. i.e. "C:\Program Files (x86)\file.exe"
            } elseif (isset($command[2]) && $command[2]===':') {
                $position = 2;
            } else {
                $position = false;    
            }

            // Absolute path. If it's a relative path, let it slide.
            if ($position) {
                $command = sprintf($command[$position - 1].': && cd %s && %s', escapeshellarg(dirname($command)), basename($command));
            }
        }
        $this->_command = $command;
        return $this;
    }

    /**
     * @return string|null the command that was set through setCommand() or passed to the constructor. Null if none.
     */
    public function getCommand()
    {
        return $this->_command;
    }

    /**
     * @return string|bool the full command string to execute. If no command was set with setCommand()
     * or passed to the constructor it will return false.
     */
    public function getExecCommand()
    {
        if ($this->_execCommand===null) {
            $command = $this->getCommand();
            if (!$command) {
                $this->_error = 'Could not locate any executable command';
                return false;
            }
            $args = $this->getArgs();
            $this->_execCommand = $args ? $command.' '.$args : $command;
        }
        return $this->_execCommand;
    }

    /**
     * @param string $args the command arguments as string. Note that these will not get escaped!
     * @return static for method chaining
     */
    public function setArgs($args)
    {
        $this->_args = array($args);
        return $this;
    }

    /**
     * @return string the command args that where set through setArgs() or added with addArg() separated by spaces
     */
    public function getArgs()
    {
        return implode(' ', $this->_args);
    }

    /**
     * @param string $key the argument key to add e.g. `--feature` or `--name=`. If the key does not end with
     * and `=`, the $value will be separated by a space, if any. Keys are not escaped unless $value is null
     * and $escape is `true`.
     * @param string|array|null $value the optional argument value which will get escaped if $escapeArgs is true.
     * An array can be passed to add more than one value for a key, e.g. `addArg('--exclude', array('val1','val2'))`
     * which will create the option `--exclude 'val1' 'val2'`.
     * @param bool|null $escape if set, this overrides the $escapeArgs setting and enforces escaping/no escaping
     * @return static for method chaining
     */
    public function addArg($key, $value = null, $escape = null)
    {
        $doEscape = $escape!==null ? $escape : $this->escapeArgs;
        $useLocale = $doEscape && $this->locale!==null;

        if ($useLocale) {
            $locale = setlocale(LC_CTYPE, 0);   // Returns current locale setting
            setlocale(LC_CTYPE, $this->locale);
        }
        if ($value===null) {
            // Only escape single arguments if explicitely requested
            $this->_args[] = $escape ? escapeshellarg($key) : $key;
        } else {
            $separator = substr($key, -1)==='=' ? '' : ' ';
            if (is_array($value)) {
                $params = array();
                foreach ($value as $v) {
                    $params[] = $doEscape ? escapeshellarg($v) : $v;
                }
                $this->_args[] = $key.$separator.implode(' ',$params);
            } else {
                $this->_args[] = $key.$separator.($doEscape ? escapeshellarg($value) : $value);
            }
        }
        if ($useLocale) {
            setlocale(LC_CTYPE, $locale);
        }

        return $this;
    }

    /**
     * @param bool $trim whether to `trim()` the return value. The default is `true`.
     * @return string the command output (stdout). Empty if none.
     */
    public function getOutput($trim = true)
    {
        return $trim ? trim($this->_stdOut) : $this->_stdOut;
    }

    /**
     * @param bool $trim whether to `trim()` the return value. The default is `true`.
     * @return string the error message, either stderr or internal message. Empty if none.
     */
    public function getError($trim = true)
    {
        return $trim ? trim($this->_error) : $this->_error;
    }

    /**
     * @param bool $trim whether to `trim()` the return value. The default is `true`.
     * @return string the stderr output. Empty if none.
     */
    public function getStdErr($trim = true)
    {
        return $trim ? trim($this->_stdErr) : $this->_stdErr;
    }

    /**
     * @return int|null the exit code or null if command was not executed yet
     */
    public function getExitCode()
    {
        return $this->_exitCode;
    }

    /**
     * @return string whether the command was successfully executed
     */
    public function getExecuted()
    {
        return $this->_executed;
    }

    /**
     * Execute the command
     *
     * @return bool whether execution was successful. If false, error details can be obtained through
     * getError(), getStdErr() and getExitCode().
     */
    public function execute()
    {
        $command = $this->getExecCommand();

        if (!$command) {
            return false;
        }

        if ($this->useExec) {
            $execCommand = $this->captureStdErr ? "$command 2>&1" : $command;
            exec($execCommand, $output, $this->_exitCode);
            $this->_stdOut = implode("\n", $output);
            if ($this->_exitCode!==0) {
                $this->_stdErr = $this->_stdOut;
                $this->_error = empty($this->_stdErr) ? 'Command failed' : $this->_stdErr;
                return false;
            }
        } else {
            $descriptors = array(
                1   => array('pipe','w'),
                2   => array('pipe', $this->getIsWindows() ? 'a' : 'w'),
            );
            $process = proc_open($command, $descriptors, $pipes, $this->procCwd, $this->procEnv, $this->procOptions);

            if (is_resource($process)) {

                $this->_stdOut = stream_get_contents($pipes[1]);
                $this->_stdErr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);

                $this->_exitCode = proc_close($process);

                if ($this->_exitCode!==0) {
                    $this->_error = $this->_stdErr ? $this->_stdErr : "Failed without error message: $command";
                    return false;
                }
            } else {
                $this->_error = "Could not run command $command";
                return false;
            }
        }

        $this->_executed = true;

        return true;
    }

    /**
     * @return bool whether we are on a Windows OS
     */
    public function getIsWindows()
    {
        return strncasecmp(PHP_OS, 'WIN', 3)===0;
    }

    /**
     * @return string the current command string to execute
     */
    public function __toString()
    {
        return (string)$this->getExecCommand();
    }
}
