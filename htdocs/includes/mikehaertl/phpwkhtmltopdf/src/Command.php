<?php
namespace mikehaertl\wkhtmlto;

use mikehaertl\shellcommand\Command as BaseCommand;

/**
 * Command
 *
 * This class is an extension of mikehaertl\shellcommand\Command and adds `wk*`
 * specific features like xvfb support and proper argument handling.
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @license http://www.opensource.org/licenses/MIT
 */
class Command extends BaseCommand
{
    /**
     * @var bool whether to enable the built in Xvfb support (uses xvfb-run)
     */
    public $enableXvfb = false;

    /**
     * @var string the name of the xvfb-run comand. Default is `xvfb-run`.  You
     * can also configure a full path here.
     */
    public $xvfbRunBinary = 'xvfb-run';

    /**
     * @var string options to pass to the xfvb-run command. Default is
     * `--server-args="-screen 0, 1024x768x24"`.
     */
    public $xvfbRunOptions = '-a --server-args="-screen 0, 1024x768x24"';

    /**
     * @param array $args args to add to the command. These can be:
     * ```
     * [
     *   // Special argument 'input' will not get prepended with '--'.
     *   'input' => 'cover',
     *
     *   // Special argument 'inputArg' is treated like 'input' but will get escaped
     *   // Both 'input' and 'inputArg' can be used in combination
     *   'inputArg' => '/tmp/tmpFileName.html',
     *
     *   'no-outline',           // option without argument
     *   'encoding' => 'UTF-8',  // option with argument
     *
     *   // Option with 2 arguments
     *   'cookie' => array('name'=>'value'),
     *
     *   // Repeatable options with single argument
     *   'run-script' => array(
     *       'local1.js',
     *       'local2.js',
     *   ),
     *
     *   // Repeatable options with 2 arguments
     *   'replace' => array(
     *       '{page}' => $page++,
     *       '{title}' => $pageTitle,
     *   ),
     * ]
     * ```
     */
    public function addArgs($args)
    {
        if (isset($args['input'])) {
            // Typecasts TmpFile to filename
            $this->addArg((string) $args['input']);
            unset($args['input']);
        }
        if (isset($args['inputArg'])) {
            // Typecasts TmpFile to filename and escapes argument
            $this->addArg((string) $args['inputArg'], null, true);
            unset($args['inputArg']);
        }
        foreach($args as $key => $val) {
            if (is_numeric($key)) {
                $this->addArg("--$val");
            } elseif (is_array($val)) {
                foreach($val as $vkey => $vval) {
                    if (is_int($vkey)) {
                        $this->addArg("--$key", $vval);
                    } else {
                        $this->addArg("--$key", array($vkey, $vval));
                    }
                }
            } else {
                $this->addArg("--$key", $val);
            }
        }
    }

    /**
     * @return string|bool the command to execute with optional Xfvb wrapper
     * applied. Null if none set.
     */
    public function getExecCommand()
    {
        $command = parent::getExecCommand();
        if ($this->enableXvfb) {
            return $this->xvfbRunBinary.' '.$this->xvfbRunOptions.' '.$command;
        }
        return $command;
    }
}
