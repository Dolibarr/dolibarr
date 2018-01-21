<?php
namespace mikehaertl\wkhtmlto;

use mikehaertl\tmp\File;

/**
 * Pdf
 *
 * This class is a slim wrapper around `wkhtmltoimage`.
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @license http://www.opensource.org/licenses/MIT
 */
class Image
{
    // Regular expression to detect HTML strings
    const REGEX_HTML = '/<html/i';

    // prefix for tmp files
    const TMP_PREFIX = 'tmp_wkhtmlto_pdf_';

    /**
     * @var string the name of the `wkhtmltoimage` binary. Default is
     * `wkhtmltoimage`. You can also configure a full path here.
     */
    public $binary = 'wkhtmltoimage';

    /**
     * @var string the image type. Default is 'png'. Other options are 'jpg'
     * and 'bmp'.
     */
    public $type = 'png';

    /**
     * @var array options to pass to the Command constructor. Default is none.
     */
    public $commandOptions = array();

    /**
     * @var string|null the directory to use for temporary files. If `null`
     * (default) the dir is autodetected.
     */
    public $tmpDir;

    /**
     * @var bool whether to ignore any errors if some PDF file was still
     * created. Default is `false`.
     */
    public $ignoreWarnings = false;

    /**
     * @var bool whether the PDF was created
     */
    protected $_isCreated = false;

    /**
     * @var \mikehaertl\tmp\File|string the page input or a `File` instance for
     * HTML string inputs
     */
    protected $_page;

    /**
     * @var array options for `wkhtmltoimage` as `['--opt1', '--opt2' => 'val',
     * ...]`
     */
    protected $_options = array();

    /**
     * @var \mikehaertl\tmp\File the temporary image file
     */
    protected $_tmpImageFile;

    /**
     * @var Command the command instance that executes wkhtmltopdf
     */
    protected $_command;

    /**
     * @var string the detailed error message. Empty string if none.
     */
    protected $_error = '';

    /**
     * @param array|string $options global options for wkhtmltoimage, a page
     * URL, a HTML string or a filename
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        } elseif (is_string($options)) {
            $this->setPage($options);
        }
    }

    /**
     * Add a page object to the output
     *
     * @param string $page either a URL, a HTML string or a filename
     * @return static the Image instance for method chaining
     */
    public function setPage($page)
    {
        $this->_page = preg_match(self::REGEX_HTML, $page) ? new File($page, '.html') : $page;
        return $this;
    }

    /**
     * Save the image to given filename (triggers image creation)
     *
     * @param string $filename to save image as
     * @return bool whether image was created successfully
     */
    public function saveAs($filename)
    {
        if (!$this->_isCreated && !$this->createImage()) {
            return false;
        }
        if (!$this->_tmpImageFile->saveAs($filename)) {
            $tmpFile = $this->_tmpImageFile->getFileName();
            $this->_error = "Could not copy image from tmp location '$tmpFile' to '$filename'";
            return false;
        }
        return true;
    }

    /**
     * Send image to client, either inline or as download (triggers image
     * creation)
     *
     * @param string|null $filename the filename to send. If empty, the PDF is
     * streamed inline. Note, that the file extension must match what you
     * configured as $type (png, jpg, ...).
     * @param bool $inline whether to force inline display of the image, even
     * if filename is present.
     * @return bool whether image was created successfully
     */
    public function send($filename = null,$inline = false)
    {
        if (!$this->_isCreated && !$this->createImage()) {
            return false;
        }
        $this->_tmpImageFile->send($filename, $this->getMimeType(), $inline);
        return true;
    }

    /**
     * Set options
     *
     * @param array $options list of image options to set as name/value pairs
     * @return static the Image instance for method chaining
     */
    public function setOptions($options = array())
    {
        foreach ($options as $key => $val) {
            if (is_int($key)) {
                $this->_options[] = $val;
            } elseif ($key[0]!=='_' && property_exists($this, $key)) {
                $this->$key = $val;
            } else {
                $this->_options[$key] = $val;
            }
        }
        return $this;
    }

    /**
     * @return Command the command instance that executes wkhtmltopdf
     */
    public function getCommand()
    {
        if ($this->_command === null) {
            $options = $this->commandOptions;
            if (!isset($options['command'])) {
                $options['command'] = $this->binary;
            }
            $this->_command = new Command($options);
        }
        return $this->_command;
    }

    /**
     * @return string the detailed error message. Empty string if none.
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * @return string the filename of the temporary image file
     */
    public function getImageFilename()
    {
        if ($this->_tmpImageFile === null) {
            $this->_tmpImageFile = new File('', '.'.$this->type, self::TMP_PREFIX);
        }
        return $this->_tmpImageFile->getFileName();
    }

    /**
     * @return string the mime type for the current image
     * @throws \Exception
     */
    public function getMimeType()
    {
        if ($this->type === 'jpg') {
            return 'image/jpeg';
        } elseif ($this->type === 'png') {
            return 'image/png';
        } elseif ($this->type === 'bmp') {
            return 'image/bmp';
        } else {
            throw new \Exception('Invalid image type');
        }
    }

    /**
     * Run the Command to create the tmp image file
     *
     * @return bool whether creation was successful
     */
    protected function createImage()
    {
        if ($this->_isCreated) {
            return false;
        }
        $command = $this->getCommand();
        $fileName = $this->getImageFilename();

        $command->addArgs($this->_options);
        // Always escape input and output filename
        $command->addArg((string) $this->_page, null, true);
        $command->addArg($fileName, null, true);
        if (!$command->execute()) {
            $this->_error = $command->getError();
            if (!(file_exists($fileName) && filesize($fileName)!==0 && $this->ignoreWarnings)) {
                return false;
            }
        }
        $this->_isCreated = true;
        return true;
    }
}
