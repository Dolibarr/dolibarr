<?php

/**
 * The Mail_Mime class provides an OO interface to create MIME
 * enabled email messages. This way you can create emails that
 * contain plain-text bodies, HTML bodies, attachments, inline
 * images and specific headers.
 *
 * @category  Mail
 * @package   Mail_Mime
 * @author    Richard Heyes  <richard@phpguru.org>
 * @author    Tomas V.V. Cox <cox@idecnet.com>
 * @author    Cipriano Groenendal <cipri@php.net>
 * @author    Sean Coates <sean@php.net>
 * @copyright 2003-2006 PEAR <pear-group@php.net>
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Mail_mime
 */
class Mail_mime
{
	/**
	 * Contains the plain text part of the email
	 *
	 * @var string
	 * @access private
	 */
	public $_txtbody;
	/**
	 * Contains the html part of the email
	 *
	 * @var string
	 * @access private
	 */
	public $_htmlbody;
	/**
	 * list of the attached images
	 *
	 * @var array
	 * @access private
	 */
	public $_html_images = array();
	/**
	 * list of the attachements
	 *
	 * @var array
	 * @access private
	 */
	public $_parts = array();
	/**
	 * Headers for the mail
	 *
	 * @var array
	 * @access private
	 */
	public $_headers = array();
	/**
	 * Build parameters
	 *
	 * @var array
	 * @access private
	 */
	public $_build_params = array(
		// What encoding to use for the headers
		// Options: quoted-printable or base64
		'head_encoding' => 'quoted-printable',
		// What encoding to use for plain text
		// Options: 7bit, 8bit, base64, or quoted-printable
		'text_encoding' => 'quoted-printable',
		// What encoding to use for html
		// Options: 7bit, 8bit, base64, or quoted-printable
		'html_encoding' => 'quoted-printable',
		// The character set to use for html
		'html_charset' => 'ISO-8859-1',
		// The character set to use for text
		'text_charset' => 'ISO-8859-1',
		// The character set to use for headers
		'head_charset' => 'ISO-8859-1',
		// End-of-line sequence
		'eol' => "\r\n",
		// Delay attachment files IO until building the message
		'delay_file_io' => \false,
	);
	/**
	 * Constructor function
	 *
	 * @param mixed $params Build parameters that change the way the email
	 *                      is built. Should be an associative array.
	 *                      See $_build_params.
	 *
	 * @return void
	 * @access public
	 */
	public function Mail_mime($params = array())
	{
	}
	/**
	 * Set build parameter value
	 *
	 * @param string $name  Parameter name
	 * @param string $value Parameter value
	 *
	 * @return void
	 * @access public
	 * @since 1.6.0
	 */
	public function setParam($name, $value)
	{
	}
	/**
	 * Get build parameter value
	 *
	 * @param string $name Parameter name
	 *
	 * @return mixed Parameter value
	 * @access public
	 * @since 1.6.0
	 */
	public function getParam($name)
	{
	}
	/**
	 * Accessor function to set the body text. Body text is used if
	 * it's not an html mail being sent or else is used to fill the
	 * text/plain part that emails clients who don't support
	 * html should show.
	 *
	 * @param string $data   Either a string or
	 *                       the file name with the contents
	 * @param bool   $isfile If true the first param should be treated
	 *                       as a file name, else as a string (default)
	 * @param bool   $append If true the text or file is appended to
	 *                       the existing body, else the old body is
	 *                       overwritten
	 *
	 * @return mixed         True on success or PEAR_Error object
	 * @access public
	 */
	public function setTXTBody($data, $isfile = \false, $append = \false)
	{
	}
	/**
	 * Get message text body
	 *
	 * @return string Text body
	 * @access public
	 * @since 1.6.0
	 */
	public function getTXTBody()
	{
	}
	/**
	 * Adds a html part to the mail.
	 *
	 * @param string $data   Either a string or the file name with the
	 *                       contents
	 * @param bool   $isfile A flag that determines whether $data is a
	 *                       filename, or a string(false, default)
	 *
	 * @return bool          True on success
	 * @access public
	 */
	public function setHTMLBody($data, $isfile = \false)
	{
	}
	/**
	 * Get message HTML body
	 *
	 * @return string HTML body
	 * @access public
	 * @since 1.6.0
	 */
	public function getHTMLBody()
	{
	}
	/**
	 * Adds an image to the list of embedded images.
	 *
	 * @param string $file       The image file name OR image data itself
	 * @param string $c_type     The content type
	 * @param string $name       The filename of the image.
	 *                           Only used if $file is the image data.
	 * @param bool   $isfile     Whether $file is a filename or not.
	 *                           Defaults to true
	 * @param string $content_id Desired Content-ID of MIME part
	 *                           Defaults to generated unique ID
	 *
	 * @return bool          True on success
	 * @access public
	 */
	public function addHTMLImage($file, $c_type = 'application/octet-stream', $name = '', $isfile = \true, $content_id = \null)
	{
	}
	/**
	 * Adds a file to the list of attachments.
	 *
	 * @param string $file        The file name of the file to attach
	 *                            or the file contents itself
	 * @param string $c_type      The content type
	 * @param string $name        The filename of the attachment
	 *                            Only use if $file is the contents
	 * @param bool   $isfile      Whether $file is a filename or not. Defaults to true
	 * @param string $encoding    The type of encoding to use. Defaults to base64.
	 *                            Possible values: 7bit, 8bit, base64 or quoted-printable.
	 * @param string $disposition The content-disposition of this file
	 *                            Defaults to attachment.
	 *                            Possible values: attachment, inline.
	 * @param string $charset     The character set of attachment's content.
	 * @param string $language    The language of the attachment
	 * @param string $location    The RFC 2557.4 location of the attachment
	 * @param string $n_encoding  Encoding of the attachment's name in Content-Type
	 *                            By default filenames are encoded using RFC2231 method
	 *                            Here you can set RFC2047 encoding (quoted-printable
	 *                            or base64) instead
	 * @param string $f_encoding  Encoding of the attachment's filename
	 *                            in Content-Disposition header.
	 * @param string $description Content-Description header
	 * @param string $h_charset   The character set of the headers e.g. filename
	 *                            If not specified, $charset will be used
	 * @param array  $add_headers Additional part headers. Array keys can be in form
	 *                            of <header_name>:<parameter_name>
	 *
	 * @return mixed              True on success or PEAR_Error object
	 * @access public
	 */
	public function addAttachment($file, $c_type = 'application/octet-stream', $name = '', $isfile = \true, $encoding = 'base64', $disposition = 'attachment', $charset = '', $language = '', $location = '', $n_encoding = \null, $f_encoding = \null, $description = '', $h_charset = \null, $add_headers = array())
	{
	}
	/**
	 * Get the contents of the given file name as string
	 *
	 * @param string $file_name Path of file to process
	 *
	 * @return string           Contents of $file_name
	 * @access private
	 */
	public function _file2str($file_name)
	{
	}
	/**
	 * Adds a text subpart to the mimePart object and
	 * returns it during the build process.
	 *
	 * @param mixed  &$obj The object to add the part to, or
	 *                     anything else if a new object is to be created.
	 * @param string $text The text to add.
	 *
	 * @return object      The text mimePart object
	 * @access private
	 */
	public function &_addTextPart(&$obj, $text = '')
	{
	}
	/**
	 * Adds a html subpart to the mimePart object and
	 * returns it during the build process.
	 *
	 * @param mixed &$obj The object to add the part to, or
	 *                    anything else if a new object is to be created.
	 *
	 * @return object     The html mimePart object
	 * @access private
	 */
	public function &_addHtmlPart(&$obj)
	{
	}
	/**
	 * Creates a new mimePart object, using multipart/mixed as
	 * the initial content-type and returns it during the
	 * build process.
	 *
	 * @return object The multipart/mixed mimePart object
	 * @access private
	 */
	public function &_addMixedPart()
	{
	}
	/**
	 * Adds a multipart/alternative part to a mimePart
	 * object (or creates one), and returns it during
	 * the build process.
	 *
	 * @param mixed &$obj The object to add the part to, or
	 *                    anything else if a new object is to be created.
	 *
	 * @return object     The multipart/mixed mimePart object
	 * @access private
	 */
	public function &_addAlternativePart(&$obj)
	{
	}
	/**
	 * Adds a multipart/related part to a mimePart
	 * object (or creates one), and returns it during
	 * the build process.
	 *
	 * @param mixed &$obj The object to add the part to, or
	 *                    anything else if a new object is to be created
	 *
	 * @return object     The multipart/mixed mimePart object
	 * @access private
	 */
	public function &_addRelatedPart(&$obj)
	{
	}
	/**
	 * Adds an html image subpart to a mimePart object
	 * and returns it during the build process.
	 *
	 * @param object &$obj  The mimePart to add the image to
	 * @param array  $value The image information
	 *
	 * @return object       The image mimePart object
	 * @access private
	 */
	public function &_addHtmlImagePart(&$obj, $value)
	{
	}
	/**
	 * Adds an attachment subpart to a mimePart object
	 * and returns it during the build process.
	 *
	 * @param object &$obj  The mimePart to add the image to
	 * @param array  $value The attachment information
	 *
	 * @return object       The image mimePart object
	 * @access private
	 */
	public function &_addAttachmentPart(&$obj, $value)
	{
	}
	/**
	 * Returns the complete e-mail, ready to send using an alternative
	 * mail delivery method. Note that only the mailpart that is made
	 * with Mail_Mime is created. This means that,
	 * YOU WILL HAVE NO TO: HEADERS UNLESS YOU SET IT YOURSELF
	 * using the $headers parameter!
	 *
	 * @param string $separation The separation between these two parts.
	 * @param array  $params     The Build parameters passed to the
	 *                           get() function. See get() for more info.
	 * @param array  $headers    The extra headers that should be passed
	 *                           to the headers() method.
	 *                           See that function for more info.
	 * @param bool   $overwrite  Overwrite the existing headers with new.
	 *
	 * @return mixed The complete e-mail or PEAR error object
	 * @access public
	 */
	public function getMessage($separation = \null, $params = \null, $headers = \null, $overwrite = \false)
	{
	}
	/**
	 * Returns the complete e-mail body, ready to send using an alternative
	 * mail delivery method.
	 *
	 * @param array $params The Build parameters passed to the
	 *                      get() method. See get() for more info.
	 *
	 * @return mixed The e-mail body or PEAR error object
	 * @access public
	 * @since 1.6.0
	 */
	public function getMessageBody($params = \null)
	{
	}
	/**
	 * Writes (appends) the complete e-mail into file.
	 *
	 * @param string $filename  Output file location
	 * @param array  $params    The Build parameters passed to the
	 *                          get() method. See get() for more info.
	 * @param array  $headers   The extra headers that should be passed
	 *                          to the headers() function.
	 *                          See that function for more info.
	 * @param bool   $overwrite Overwrite the existing headers with new.
	 *
	 * @return mixed True or PEAR error object
	 * @access public
	 * @since 1.6.0
	 */
	public function saveMessage($filename, $params = \null, $headers = \null, $overwrite = \false)
	{
	}
	/**
	 * Writes (appends) the complete e-mail body into file.
	 *
	 * @param string $filename Output file location
	 * @param array  $params   The Build parameters passed to the
	 *                         get() method. See get() for more info.
	 *
	 * @return mixed True or PEAR error object
	 * @access public
	 * @since 1.6.0
	 */
	public function saveMessageBody($filename, $params = \null)
	{
	}
	/**
	 * Builds the multipart message from the list ($this->_parts) and
	 * returns the mime content.
	 *
	 * @param array    $params    Build parameters that change the way the email
	 *                            is built. Should be associative. See $_build_params.
	 * @param resource $filename  Output file where to save the message instead of
	 *                            returning it
	 * @param boolean  $skip_head True if you want to return/save only the message
	 *                            without headers
	 *
	 * @return mixed The MIME message content string, null or PEAR error object
	 * @access public
	 */
	public function get($params = \null, $filename = \null, $skip_head = \false)
	{
	}
	/**
	 * Returns an array with the headers needed to prepend to the email
	 * (MIME-Version and Content-Type). Format of argument is:
	 * $array['header-name'] = 'header-value';
	 *
	 * @param array $xtra_headers Assoc array with any extra headers (optional)
	 *                            (Don't set Content-Type for multipart messages here!)
	 * @param bool  $overwrite    Overwrite already existing headers.
	 * @param bool  $skip_content Don't return content headers: Content-Type,
	 *                            Content-Disposition and Content-Transfer-Encoding
	 *
	 * @return array              Assoc array with the mime headers
	 * @access public
	 */
	public function headers($xtra_headers = \null, $overwrite = \false, $skip_content = \false)
	{
	}
	/**
	 * Get the text version of the headers
	 * (usefull if you want to use the PHP mail() function)
	 *
	 * @param array $xtra_headers Assoc array with any extra headers (optional)
	 *                            (Don't set Content-Type for multipart messages here!)
	 * @param bool  $overwrite    Overwrite the existing headers with new.
	 * @param bool  $skip_content Don't return content headers: Content-Type,
	 *                            Content-Disposition and Content-Transfer-Encoding
	 *
	 * @return string             Plain text headers
	 * @access public
	 */
	public function txtHeaders($xtra_headers = \null, $overwrite = \false, $skip_content = \false)
	{
	}
	/**
	 * Sets message Content-Type header.
	 * Use it to build messages with various content-types e.g. miltipart/raport
	 * not supported by _contentHeaders() function.
	 *
	 * @param string $type   Type name
	 * @param array  $params Hash array of header parameters
	 *
	 * @return void
	 * @access public
	 * @since 1.7.0
	 */
	public function setContentType($type, $params = array())
	{
	}
	/**
	 * Sets the Subject header
	 *
	 * @param string $subject String to set the subject to.
	 *
	 * @return void
	 * @access public
	 */
	public function setSubject($subject)
	{
	}
	/**
	 * Set an email to the From (the sender) header
	 *
	 * @param string $email The email address to use
	 *
	 * @return void
	 * @access public
	 */
	public function setFrom($email)
	{
	}
	/**
	 * Add an email to the To header
	 * (multiple calls to this method are allowed)
	 *
	 * @param string $email The email direction to add
	 *
	 * @return void
	 * @access public
	 */
	public function addTo($email)
	{
	}
	/**
	 * Add an email to the Cc (carbon copy) header
	 * (multiple calls to this method are allowed)
	 *
	 * @param string $email The email direction to add
	 *
	 * @return void
	 * @access public
	 */
	public function addCc($email)
	{
	}
	/**
	 * Add an email to the Bcc (blank carbon copy) header
	 * (multiple calls to this method are allowed)
	 *
	 * @param string $email The email direction to add
	 *
	 * @return void
	 * @access public
	 */
	public function addBcc($email)
	{
	}
	/**
	 * Since the PHP send function requires you to specify
	 * recipients (To: header) separately from the other
	 * headers, the To: header is not properly encoded.
	 * To fix this, you can use this public method to
	 * encode your recipients before sending to the send
	 * function
	 *
	 * @param string $recipients A comma-delimited list of recipients
	 *
	 * @return string            Encoded data
	 * @access public
	 */
	public function encodeRecipients($recipients)
	{
	}
	/**
	 * Encodes headers as per RFC2047
	 *
	 * @param array $input  The header data to encode
	 * @param array $params Extra build parameters
	 *
	 * @return array        Encoded data
	 * @access private
	 */
	public function _encodeHeaders($input, $params = array())
	{
	}
	/**
	 * Encodes a header as per RFC2047
	 *
	 * @param string $name     The header name
	 * @param string $value    The header data to encode
	 * @param string $charset  Character set name
	 * @param string $encoding Encoding name (base64 or quoted-printable)
	 *
	 * @return string          Encoded header data (without a name)
	 * @access public
	 * @since 1.5.3
	 */
	public function encodeHeader($name, $value, $charset, $encoding)
	{
	}
	/**
	 * Get file's basename (locale independent)
	 *
	 * @param string $filename Filename
	 *
	 * @return string          Basename
	 * @access private
	 */
	public function _basename($filename)
	{
	}
	/**
	 * Get Content-Type and Content-Transfer-Encoding headers of the message
	 *
	 * @return array Headers array
	 * @access private
	 */
	public function _contentHeaders()
	{
	}
	/**
	 * Validate and set build parameters
	 *
	 * @return void
	 * @access private
	 */
	public function _checkParams()
	{
	}
	/**
	 * PEAR::isError implementation
	 *
	 * @param mixed $data Object
	 *
	 * @return bool True if object is an instance of PEAR_Error
	 * @access private
	 */
	public function _isError($data)
	{
	}
	/**
	 * PEAR::raiseError implementation
	 *
	 * @param $message A text error message
	 *
	 * @return PEAR_Error Instance of PEAR_Error
	 * @access private
	 */
	public function _raiseError($message)
	{
	}
}
/**
 * The Mail_mimePart class is used to create MIME E-mail messages
 *
 * This class enables you to manipulate and build a mime email
 * from the ground up. The Mail_Mime class is a userfriendly api
 * to this class for people who aren't interested in the internals
 * of mime mail.
 * This class however allows full control over the email.
 *
 * Compatible with PHP versions 4 and 5
 *
 * LICENSE: This LICENSE is in the BSD license style.
 * Copyright (c) 2002-2003, Richard Heyes <richard@phpguru.org>
 * Copyright (c) 2003-2006, PEAR <pear-group@php.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or
 * without modification, are permitted provided that the following
 * conditions are met:
 *
 * - Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 * - Neither the name of the authors, nor the names of its contributors
 *   may be used to endorse or promote products derived from this
 *   software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF
 * THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Mail
 * @package   Mail_Mime
 * @author    Richard Heyes  <richard@phpguru.org>
 * @author    Cipriano Groenendal <cipri@php.net>
 * @author    Sean Coates <sean@php.net>
 * @author    Aleksander Machniak <alec@php.net>
 * @copyright 2003-2006 PEAR <pear-group@php.net>
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Mail_mime
 */
/**
 * The Mail_mimePart class is used to create MIME E-mail messages
 *
 * This class enables you to manipulate and build a mime email
 * from the ground up. The Mail_Mime class is a userfriendly api
 * to this class for people who aren't interested in the internals
 * of mime mail.
 * This class however allows full control over the email.
 *
 * @category  Mail
 * @package   Mail_Mime
 * @author    Richard Heyes  <richard@phpguru.org>
 * @author    Cipriano Groenendal <cipri@php.net>
 * @author    Sean Coates <sean@php.net>
 * @author    Aleksander Machniak <alec@php.net>
 * @copyright 2003-2006 PEAR <pear-group@php.net>
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Mail_mime
 */
class Mail_mimePart
{
	/**
	 * The encoding type of this part
	 *
	 * @var string
	 * @access private
	 */
	public $_encoding;
	/**
	 * An array of subparts
	 *
	 * @var array
	 * @access private
	 */
	public $_subparts;
	/**
	 * The output of this part after being built
	 *
	 * @var string
	 * @access private
	 */
	public $_encoded;
	/**
	 * Headers for this part
	 *
	 * @var array
	 * @access private
	 */
	public $_headers;
	/**
	 * The body of this part (not encoded)
	 *
	 * @var string
	 * @access private
	 */
	public $_body;
	/**
	 * The location of file with body of this part (not encoded)
	 *
	 * @var string
	 * @access private
	 */
	public $_body_file;
	/**
	 * The end-of-line sequence
	 *
	 * @var string
	 * @access private
	 */
	public $_eol = "\r\n";
	/**
	 * Constructor.
	 *
	 * Sets up the object.
	 *
	 * @param string $body   The body of the mime part if any.
	 * @param array  $params An associative array of optional parameters:
	 *     content_type      - The content type for this part eg multipart/mixed
	 *     encoding          - The encoding to use, 7bit, 8bit,
	 *                         base64, or quoted-printable
	 *     charset           - Content character set
	 *     cid               - Content ID to apply
	 *     disposition       - Content disposition, inline or attachment
	 *     filename          - Filename parameter for content disposition
	 *     description       - Content description
	 *     name_encoding     - Encoding of the attachment name (Content-Type)
	 *                         By default filenames are encoded using RFC2231
	 *                         Here you can set RFC2047 encoding (quoted-printable
	 *                         or base64) instead
	 *     filename_encoding - Encoding of the attachment filename (Content-Disposition)
	 *                         See 'name_encoding'
	 *     headers_charset   - Charset of the headers e.g. filename, description.
	 *                         If not set, 'charset' will be used
	 *     eol               - End of line sequence. Default: "\r\n"
	 *     headers           - Hash array with additional part headers. Array keys can be
	 *                         in form of <header_name>:<parameter_name>
	 *     body_file         - Location of file with part's body (instead of $body)
	 *
	 * @access public
	 */
	public function Mail_mimePart($body = '', $params = array())
	{
	}
	/**
	 * Encodes and returns the email. Also stores
	 * it in the encoded member variable
	 *
	 * @param string $boundary Pre-defined boundary string
	 *
	 * @return An associative array containing two elements,
	 *         body and headers. The headers element is itself
	 *         an indexed array. On error returns PEAR error object.
	 * @access public
	 */
	public function encode($boundary = \null)
	{
	}
	/**
	 * Encodes and saves the email into file. File must exist.
	 * Data will be appended to the file.
	 *
	 * @param string  $filename  Output file location
	 * @param string  $boundary  Pre-defined boundary string
	 * @param boolean $skip_head True if you don't want to save headers
	 *
	 * @return array An associative array containing message headers
	 *               or PEAR error object
	 * @access public
	 * @since 1.6.0
	 */
	public function encodeToFile($filename, $boundary = \null, $skip_head = \false)
	{
	}
	/**
	 * Encodes given email part into file
	 *
	 * @param string  $fh        Output file handle
	 * @param string  $boundary  Pre-defined boundary string
	 * @param boolean $skip_head True if you don't want to save headers
	 *
	 * @return array True on sucess or PEAR error object
	 * @access private
	 */
	public function _encodePartToFile($fh, $boundary = \null, $skip_head = \false)
	{
	}
	/**
	 * Adds a subpart to current mime part and returns
	 * a reference to it
	 *
	 * @param string $body   The body of the subpart, if any.
	 * @param array  $params The parameters for the subpart, same
	 *                       as the $params argument for constructor.
	 *
	 * @return Mail_mimePart A reference to the part you just added. In PHP4, it is
	 *                       crucial if using multipart/* in your subparts that
	 *                       you use =& in your script when calling this function,
	 *                       otherwise you will not be able to add further subparts.
	 * @access public
	 */
	public function &addSubpart($body, $params)
	{
	}
	/**
	 * Returns encoded data based upon encoding passed to it
	 *
	 * @param string $data     The data to encode.
	 * @param string $encoding The encoding type to use, 7bit, base64,
	 *                         or quoted-printable.
	 *
	 * @return string
	 * @access private
	 */
	public function _getEncodedData($data, $encoding)
	{
	}
	/**
	 * Returns encoded data based upon encoding passed to it
	 *
	 * @param string   $filename Data file location
	 * @param string   $encoding The encoding type to use, 7bit, base64,
	 *                           or quoted-printable.
	 * @param resource $fh       Output file handle. If set, data will be
	 *                           stored into it instead of returning it
	 *
	 * @return string Encoded data or PEAR error object
	 * @access private
	 */
	public function _getEncodedDataFromFile($filename, $encoding, $fh = \null)
	{
	}
	/**
	 * Encodes data to quoted-printable standard.
	 *
	 * @param string $input    The data to encode
	 * @param int    $line_max Optional max line length. Should
	 *                         not be more than 76 chars
	 *
	 * @return string Encoded data
	 *
	 * @access private
	 */
	public function _quotedPrintableEncode($input, $line_max = 76)
	{
	}
	/**
	 * Encodes the parameter of a header.
	 *
	 * @param string $name      The name of the header-parameter
	 * @param string $value     The value of the paramter
	 * @param string $charset   The characterset of $value
	 * @param string $language  The language used in $value
	 * @param string $encoding  Parameter encoding. If not set, parameter value
	 *                          is encoded according to RFC2231
	 * @param int    $maxLength The maximum length of a line. Defauls to 75
	 *
	 * @return string
	 *
	 * @access private
	 */
	public function _buildHeaderParam($name, $value, $charset = \null, $language = \null, $encoding = \null, $maxLength = 75)
	{
	}
	/**
	 * Encodes header parameter as per RFC2047 if needed
	 *
	 * @param string $name      The parameter name
	 * @param string $value     The parameter value
	 * @param string $charset   The parameter charset
	 * @param string $encoding  Encoding type (quoted-printable or base64)
	 * @param int    $maxLength Encoded parameter max length. Default: 76
	 *
	 * @return string Parameter line
	 * @access private
	 */
	public function _buildRFC2047Param($name, $value, $charset, $encoding = 'quoted-printable', $maxLength = 76)
	{
	}
	/**
	 * Encodes a header as per RFC2047
	 *
	 * @param string $name     The header name
	 * @param string $value    The header data to encode
	 * @param string $charset  Character set name
	 * @param string $encoding Encoding name (base64 or quoted-printable)
	 * @param string $eol      End-of-line sequence. Default: "\r\n"
	 *
	 * @return string          Encoded header data (without a name)
	 * @access public
	 * @since 1.6.1
	 */
	public function encodeHeader($name, $value, $charset = 'ISO-8859-1', $encoding = 'quoted-printable', $eol = "\r\n")
	{
	}
	/**
	 * Explode quoted string
	 *
	 * @param string $delimiter Delimiter expression string for preg_match()
	 * @param string $string    Input string
	 *
	 * @return array            String tokens array
	 * @access private
	 */
	public function _explodeQuotedString($delimiter, $string)
	{
	}
	/**
	 * Encodes a header value as per RFC2047
	 *
	 * @param string $value      The header data to encode
	 * @param string $charset    Character set name
	 * @param string $encoding   Encoding name (base64 or quoted-printable)
	 * @param int    $prefix_len Prefix length. Default: 0
	 * @param string $eol        End-of-line sequence. Default: "\r\n"
	 *
	 * @return string            Encoded header data
	 * @access public
	 * @since 1.6.1
	 */
	public function encodeHeaderValue($value, $charset, $encoding, $prefix_len = 0, $eol = "\r\n")
	{
	}
	/**
	 * Encodes the given string using quoted-printable
	 *
	 * @param string $str String to encode
	 *
	 * @return string     Encoded string
	 * @access public
	 * @since 1.6.0
	 */
	public function encodeQP($str)
	{
	}
	/**
	 * Encodes the given string using base64 or quoted-printable.
	 * This method makes sure that encoded-word represents an integral
	 * number of characters as per RFC2047.
	 *
	 * @param string $str        String to encode
	 * @param string $charset    Character set name
	 * @param string $encoding   Encoding name (base64 or quoted-printable)
	 * @param int    $prefix_len Prefix length. Default: 0
	 * @param string $eol        End-of-line sequence. Default: "\r\n"
	 *
	 * @return string     Encoded string
	 * @access public
	 * @since 1.8.0
	 */
	public function encodeMB($str, $charset, $encoding, $prefix_len = 0, $eol = "\r\n")
	{
	}
	/**
	 * Callback function to replace extended characters (\x80-xFF) with their
	 * ASCII values (RFC2047: quoted-printable)
	 *
	 * @param array $matches Preg_replace's matches array
	 *
	 * @return string        Encoded character string
	 * @access private
	 */
	public function _qpReplaceCallback($matches)
	{
	}
	/**
	 * Callback function to replace extended characters (\x80-xFF) with their
	 * ASCII values (RFC2231)
	 *
	 * @param array $matches Preg_replace's matches array
	 *
	 * @return string        Encoded character string
	 * @access private
	 */
	public function _encodeReplaceCallback($matches)
	{
	}
	/**
	 * PEAR::isError implementation
	 *
	 * @param mixed $data Object
	 *
	 * @return bool True if object is an instance of PEAR_Error
	 * @access private
	 */
	public function _isError($data)
	{
	}
	/**
	 * PEAR::raiseError implementation
	 *
	 * @param $message A text error message
	 *
	 * @return PEAR_Error Instance of PEAR_Error
	 * @access private
	 */
	public function _raiseError($message)
	{
	}
}
/**
 *
 * nusoap_base
 *
 * @author   Dietrich Ayala <dietrich@ganx4.com>
 * @author   Scott Nichol <snichol@users.sourceforge.net>
 * @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
 * @access   public
 */
class nusoap_base
{
	/**
	 * Identification for HTTP headers.
	 *
	 * @var string
	 * @access private
	 */
	public $title = 'NuSOAP';
	/**
	 * Version for HTTP headers.
	 *
	 * @var string
	 * @access private
	 */
	public $version = '0.9.11';
	/**
	 * CVS revision for HTTP headers.
	 *
	 * @var string
	 * @access private
	 */
	public $revision = '$Revision: 1.123 $';
	/**
	 * Current error string (manipulated by getError/setError)
	 *
	 * @var string
	 * @access private
	 */
	public $error_str = '';
	/**
	 * Current debug string (manipulated by debug/appendDebug/clearDebug/getDebug/getDebugAsXMLComment)
	 *
	 * @var string
	 * @access private
	 */
	public $debug_str = '';
	/**
	 * toggles automatic encoding of special characters as entities
	 * (should always be true, I think)
	 *
	 * @var boolean
	 * @access private
	 */
	public $charencoding = \true;
	/**
	 * the debug level for this instance
	 *
	 * @var    integer
	 * @access private
	 */
	public $debugLevel;
	/**
	 * set schema version
	 *
	 * @var      string
	 * @access   public
	 */
	public $XMLSchemaVersion = 'http://www.w3.org/2001/XMLSchema';
	/**
	 * charset encoding for outgoing messages
	 *
	 * @var      string
	 * @access   public
	 */
	public $soap_defencoding = 'ISO-8859-1';
	//var $soap_defencoding = 'UTF-8';
	/**
	 * namespaces in an array of prefix => uri
	 *
	 * this is "seeded" by a set of constants, but it may be altered by code
	 *
	 * @var      array
	 * @access   public
	 */
	public $namespaces = array('SOAP-ENV' => 'http://schemas.xmlsoap.org/soap/envelope/', 'xsd' => 'http://www.w3.org/2001/XMLSchema', 'xsi' => 'http://www.w3.org/2001/XMLSchema-instance', 'SOAP-ENC' => 'http://schemas.xmlsoap.org/soap/encoding/');
	/**
	 * namespaces used in the current context, e.g. during serialization
	 *
	 * @var      array
	 * @access   private
	 */
	public $usedNamespaces = array();
	/**
	 * XML Schema types in an array of uri => (array of xml type => php type)
	 * is this legacy yet?
	 * no, this is used by the nusoap_xmlschema class to verify type => namespace mappings.
	 *
	 * @var      array
	 * @access   public
	 */
	public $typemap = array('http://www.w3.org/2001/XMLSchema' => array(
		'string' => 'string',
		'boolean' => 'boolean',
		'float' => 'double',
		'double' => 'double',
		'decimal' => 'double',
		'duration' => '',
		'dateTime' => 'string',
		'time' => 'string',
		'date' => 'string',
		'gYearMonth' => '',
		'gYear' => '',
		'gMonthDay' => '',
		'gDay' => '',
		'gMonth' => '',
		'hexBinary' => 'string',
		'base64Binary' => 'string',
		// abstract "any" types
		'anyType' => 'string',
		'anySimpleType' => 'string',
		// derived datatypes
		'normalizedString' => 'string',
		'token' => 'string',
		'language' => '',
		'NMTOKEN' => '',
		'NMTOKENS' => '',
		'Name' => '',
		'NCName' => '',
		'ID' => '',
		'IDREF' => '',
		'IDREFS' => '',
		'ENTITY' => '',
		'ENTITIES' => '',
		'integer' => 'integer',
		'nonPositiveInteger' => 'integer',
		'negativeInteger' => 'integer',
		'long' => 'integer',
		'int' => 'integer',
		'short' => 'integer',
		'byte' => 'integer',
		'nonNegativeInteger' => 'integer',
		'unsignedLong' => '',
		'unsignedInt' => '',
		'unsignedShort' => '',
		'unsignedByte' => '',
		'positiveInteger' => '',
	), 'http://www.w3.org/2000/10/XMLSchema' => array('i4' => '', 'int' => 'integer', 'boolean' => 'boolean', 'string' => 'string', 'double' => 'double', 'float' => 'double', 'dateTime' => 'string', 'timeInstant' => 'string', 'base64Binary' => 'string', 'base64' => 'string', 'ur-type' => 'array'), 'http://www.w3.org/1999/XMLSchema' => array('i4' => '', 'int' => 'integer', 'boolean' => 'boolean', 'string' => 'string', 'double' => 'double', 'float' => 'double', 'dateTime' => 'string', 'timeInstant' => 'string', 'base64Binary' => 'string', 'base64' => 'string', 'ur-type' => 'array'), 'http://soapinterop.org/xsd' => array('SOAPStruct' => 'struct'), 'http://schemas.xmlsoap.org/soap/encoding/' => array('base64' => 'string', 'array' => 'array', 'Array' => 'array'), 'http://xml.apache.org/xml-soap' => array('Map'));
	/**
	 * XML entities to convert
	 *
	 * @var      array
	 * @access   public
	 * @deprecated
	 * @see    expandEntities
	 */
	public $xmlEntities = array('quot' => '"', 'amp' => '&', 'lt' => '<', 'gt' => '>', 'apos' => "'");
	/**
	 * HTTP Content-type to be used for SOAP calls and responses
	 *
	 * @var string
	 */
	public $contentType = "text/xml";
	/**
	 * constructor
	 *
	 * @access    public
	 */
	public function __construct()
	{
	}
	/**
	 * gets the global debug level, which applies to future instances
	 *
	 * @return    integer    Debug level 0-9, where 0 turns off
	 * @access    public
	 */
	public function getGlobalDebugLevel()
	{
	}
	/**
	 * sets the global debug level, which applies to future instances
	 *
	 * @param    int $level Debug level 0-9, where 0 turns off
	 * @access    public
	 */
	public function setGlobalDebugLevel($level)
	{
	}
	/**
	 * gets the debug level for this instance
	 *
	 * @return    int    Debug level 0-9, where 0 turns off
	 * @access    public
	 */
	public function getDebugLevel()
	{
	}
	/**
	 * sets the debug level for this instance
	 *
	 * @param    int $level Debug level 0-9, where 0 turns off
	 * @access    public
	 */
	public function setDebugLevel($level)
	{
	}
	/**
	 * adds debug data to the instance debug string with formatting
	 *
	 * @param    string $string debug data
	 * @access   private
	 */
	public function debug($string)
	{
	}
	/**
	 * adds debug data to the instance debug string without formatting
	 *
	 * @param    string $string debug data
	 * @access   public
	 */
	public function appendDebug($string)
	{
	}
	/**
	 * clears the current debug data for this instance
	 *
	 * @access   public
	 */
	public function clearDebug()
	{
	}
	/**
	 * gets the current debug data for this instance
	 *
	 * @return   string data
	 * @access   public
	 */
	public function &getDebug()
	{
	}
	/**
	 * gets the current debug data for this instance as an XML comment
	 * this may change the contents of the debug data
	 *
	 * @return   string data as an XML comment
	 * @access   public
	 */
	public function &getDebugAsXMLComment()
	{
	}
	/**
	 * expands entities, e.g. changes '<' to '&lt;'.
	 *
	 * @param    string $val The string in which to expand entities.
	 * @access    private
	 */
	public function expandEntities($val)
	{
	}
	/**
	 * returns error string if present
	 *
	 * @return   false|string error string or false
	 * @access   public
	 */
	public function getError()
	{
	}
	/**
	 * sets error string
	 *
	 * @return   void
	 * @access   private
	 */
	public function setError($str)
	{
	}
	/**
	 * detect if array is a simple array or a struct (associative array)
	 *
	 * @param    mixed $val The PHP array
	 * @return    string    (arraySimple|arrayStruct)
	 * @access    private
	 */
	public function isArraySimpleOrStruct($val)
	{
	}
	/**
	 * serializes PHP values in accordance w/ section 5. Type information is
	 * not serialized if $use == 'literal'.
	 *
	 * @param    mixed $val The value to serialize
	 * @param    string $name The name (local part) of the XML element
	 * @param    string $type The XML schema type (local part) for the element
	 * @param    string $name_ns The namespace for the name of the XML element
	 * @param    string $type_ns The namespace for the type of the element
	 * @param    array $attributes The attributes to serialize as name=>value pairs
	 * @param    string $use The WSDL "use" (encoded|literal)
	 * @param    boolean $soapval Whether this is called from soapval.
	 * @return    string    The serialized element, possibly with child elements
	 * @access    public
	 */
	public function serialize_val($val, $name = \false, $type = \false, $name_ns = \false, $type_ns = \false, $attributes = \false, $use = 'encoded', $soapval = \false)
	{
	}
	/**
	 * serializes a message
	 *
	 * @param string $body the XML of the SOAP body
	 * @param mixed $headers optional string of XML with SOAP header content, or array of soapval objects for SOAP headers, or associative array
	 * @param array $namespaces optional the namespaces used in generating the body and headers
	 * @param string $style optional (rpc|document)
	 * @param string $use optional (encoded|literal)
	 * @param string $encodingStyle optional (usually 'http://schemas.xmlsoap.org/soap/encoding/' for encoded)
	 * @return string the message
	 * @access public
	 */
	public function serializeEnvelope($body, $headers = \false, $namespaces = array(), $style = 'rpc', $use = 'encoded', $encodingStyle = 'http://schemas.xmlsoap.org/soap/encoding/')
	{
	}
	/**
	 * formats a string to be inserted into an HTML stream
	 *
	 * @param string $str The string to format
	 * @return string The formatted string
	 * @access public
	 * @deprecated
	 */
	public function formatDump($str)
	{
	}
	/**
	 * contracts (changes namespace to prefix) a qualified name
	 *
	 * @param    string $qname qname
	 * @return    string contracted qname
	 * @access   private
	 */
	public function contractQname($qname)
	{
	}
	/**
	 * expands (changes prefix to namespace) a qualified name
	 *
	 * @param    string $qname qname
	 * @return    string expanded qname
	 * @access   private
	 */
	public function expandQname($qname)
	{
	}
	/**
	 * returns the local part of a prefixed string
	 * returns the original string, if not prefixed
	 *
	 * @param string $str The prefixed string
	 * @return string The local part
	 * @access public
	 */
	public function getLocalPart($str)
	{
	}
	/**
	 * returns the prefix part of a prefixed string
	 * returns false, if not prefixed
	 *
	 * @param string $str The prefixed string
	 * @return false|string The prefix or false if there is no prefix
	 * @access public
	 */
	public function getPrefix($str)
	{
	}
	/**
	 * pass it a prefix, it returns a namespace
	 *
	 * @param string $prefix The prefix
	 * @return mixed The namespace, false if no namespace has the specified prefix
	 * @access public
	 */
	public function getNamespaceFromPrefix($prefix)
	{
	}
	/**
	 * returns the prefix for a given namespace (or prefix)
	 * or false if no prefixes registered for the given namespace
	 *
	 * @param string $ns The namespace
	 * @return false|string The prefix, false if the namespace has no prefixes
	 * @access public
	 */
	public function getPrefixFromNamespace($ns)
	{
	}
	/**
	 * returns the time in ODBC canonical form with microseconds
	 *
	 * @return string The time in ODBC canonical form with microseconds
	 * @access public
	 */
	public function getmicrotime()
	{
	}
	/**
	 * Returns a string with the output of var_dump
	 *
	 * @param mixed $data The variable to var_dump
	 * @return string The output of var_dump
	 * @access public
	 */
	public function varDump($data)
	{
	}
	/**
	 * represents the object as a string
	 *
	 * @return    string
	 * @access   public
	 */
	public function __toString()
	{
	}
}
/**
 * Contains information for a SOAP fault.
 * Mainly used for returning faults from deployed functions
 * in a server instance.
 *
 * @author   Dietrich Ayala <dietrich@ganx4.com>
 * @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
 * @access public
 */
class nusoap_fault extends \nusoap_base
{
	/**
	 * The fault code (client|server)
	 *
	 * @var string
	 * @access private
	 */
	public $faultcode;
	/**
	 * The fault actor
	 *
	 * @var string
	 * @access private
	 */
	public $faultactor;
	/**
	 * The fault string, a description of the fault
	 *
	 * @var string
	 * @access private
	 */
	public $faultstring;
	/**
	 * The fault detail, typically a string or array of string
	 *
	 * @var mixed
	 * @access private
	 */
	public $faultdetail;
	/**
	 * constructor
	 *
	 * @param string $faultcode (SOAP-ENV:Client | SOAP-ENV:Server)
	 * @param string $faultactor only used when msg routed between multiple actors
	 * @param string $faultstring human readable error message
	 * @param mixed $faultdetail detail, typically a string or array of string
	 */
	public function __construct($faultcode, $faultactor = '', $faultstring = '', $faultdetail = '')
	{
	}
	/**
	 * serialize a fault
	 *
	 * @return    string    The serialization of the fault instance.
	 * @access   public
	 */
	public function serialize()
	{
	}
}
/**
 * Backward compatibility
 */
class soap_fault extends \nusoap_fault
{
}
/**
 * parses an XML Schema, allows access to it's data, other utility methods.
 * imperfect, no validation... yet, but quite functional.
 *
 * @author   Dietrich Ayala <dietrich@ganx4.com>
 * @author   Scott Nichol <snichol@users.sourceforge.net>
 * @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
 * @access   public
 */
class nusoap_xmlschema extends \nusoap_base
{
	// files
	public $schema = '';
	public $xml = '';
	// namespaces
	public $enclosingNamespaces;
	// schema info
	public $schemaInfo = array();
	public $schemaTargetNamespace = '';
	// types, elements, attributes defined by the schema
	public $attributes = array();
	public $complexTypes = array();
	public $complexTypeStack = array();
	public $currentComplexType = \null;
	public $elements = array();
	public $elementStack = array();
	public $currentElement = \null;
	public $simpleTypes = array();
	public $simpleTypeStack = array();
	public $currentSimpleType = \null;
	// imports
	public $imports = array();
	// parser vars
	public $parser;
	public $position = 0;
	public $depth = 0;
	public $depth_array = array();
	public $message = array();
	public $defaultNamespace = array();
	/**
	 * constructor
	 *
	 * @param    string $schema schema document URI
	 * @param    string $xml xml document URI
	 * @param    string $namespaces namespaces defined in enclosing XML
	 * @access   public
	 */
	public function __construct($schema = '', $xml = '', $namespaces = array())
	{
	}
	/**
	 * parse an XML file
	 *
	 * @param string $xml path/URL to XML file
	 * @param string $type (schema | xml)
	 * @return boolean
	 * @access public
	 */
	public function parseFile($xml, $type)
	{
	}
	/**
	 * parse an XML string
	 *
	 * @param    string $xml path or URL
	 * @param    string $type (schema|xml)
	 * @access   private
	 */
	public function parseString($xml, $type)
	{
	}
	/**
	 * gets a type name for an unnamed type
	 *
	 * @param    string $ename Element name
	 * @return    string    A type name for an unnamed type
	 * @access    private
	 */
	public function CreateTypeName($ename)
	{
	}
	/**
	 * start-element handler
	 *
	 * @param    string $parser XML parser object
	 * @param    string $name element name
	 * @param    array $attrs associative array of attributes
	 * @access   private
	 */
	public function schemaStartElement($parser, $name, $attrs)
	{
	}
	/**
	 * end-element handler
	 *
	 * @param    string $parser XML parser object
	 * @param    string $name element name
	 * @access   private
	 */
	public function schemaEndElement($parser, $name)
	{
	}
	/**
	 * element content handler
	 *
	 * @param    string $parser XML parser object
	 * @param    string $data element content
	 * @access   private
	 */
	public function schemaCharacterData($parser, $data)
	{
	}
	/**
	 * serialize the schema
	 *
	 * @access   public
	 */
	public function serializeSchema()
	{
	}
	/**
	 * adds debug data to the clas level debug string
	 *
	 * @param    string $string debug data
	 * @access   private
	 */
	public function xdebug($string)
	{
	}
	/**
	 * get the PHP type of a user defined type in the schema
	 * PHP type is kind of a misnomer since it actually returns 'struct' for assoc. arrays
	 * returns false if no type exists, or not w/ the given namespace
	 * else returns a string that is either a native php type, or 'struct'
	 *
	 * @param string $type name of defined type
	 * @param string $ns namespace of type
	 * @return mixed
	 * @access public
	 * @deprecated
	 */
	public function getPHPType($type, $ns)
	{
	}
	/**
	 * returns an associative array of information about a given type
	 * returns false if no type exists by the given name
	 *
	 *    For a complexType typeDef = array(
	 *    'restrictionBase' => '',
	 *    'phpType' => '',
	 *    'compositor' => '(sequence|all)',
	 *    'elements' => array(), // refs to elements array
	 *    'attrs' => array() // refs to attributes array
	 *    ... and so on (see addComplexType)
	 *    )
	 *
	 *   For simpleType or element, the array has different keys.
	 *
	 * @param string $type
	 * @return mixed
	 * @access public
	 * @see addComplexType
	 * @see addSimpleType
	 * @see addElement
	 */
	public function getTypeDef($type)
	{
	}
	/**
	 * returns a sample serialization of a given type, or false if no type by the given name
	 *
	 * @param string $type name of type
	 * @return false|string
	 * @access public
	 */
	public function serializeTypeDef($type)
	{
	}
	/**
	 * returns HTML form elements that allow a user
	 * to enter values for creating an instance of the given type.
	 *
	 * @param string $name name for type instance
	 * @param string $type name of type
	 * @return string
	 * @access public
	 * @deprecated
	 */
	public function typeToForm($name, $type)
	{
	}
	/**
	 * adds a complex type to the schema
	 * example: array
	 * addType(
	 *    'ArrayOfstring',
	 *    'complexType',
	 *    'array',
	 *    '',
	 *    'SOAP-ENC:Array',
	 *    array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'string[]'),
	 *    'xsd:string'
	 * );
	 * example: PHP associative array ( SOAP Struct )
	 * addType(
	 *    'SOAPStruct',
	 *    'complexType',
	 *    'struct',
	 *    'all',
	 *    array('myVar'=> array('name'=>'myVar','type'=>'string')
	 * );
	 *
	 * @param string $name
	 * @param string $typeClass (complexType|simpleType|attribute)
	 * @param string $phpType : currently supported are array and struct (php assoc array)
	 * @param string $compositor (all|sequence|choice)
	 * @param string $restrictionBase namespace:name (http://schemas.xmlsoap.org/soap/encoding/:Array)
	 * @param array $elements = array ( name = array(name=>'',type=>'') )
	 * @param array $attrs = array(
	 *    array(
	 *        'ref' => "http://schemas.xmlsoap.org/soap/encoding/:arrayType",
	 *        "http://schemas.xmlsoap.org/wsdl/:arrayType" => "string[]"
	 *    )
	 * )
	 * @param array $arrayType : namespace:name (http://www.w3.org/2001/XMLSchema:string)
	 *
	 * @access public
	 * @see getTypeDef
	 */
	public function addComplexType($name, $typeClass = 'complexType', $phpType = 'array', $compositor = '', $restrictionBase = '', $elements = array(), $attrs = array(), $arrayType = '')
	{
	}
	/**
	 * adds a simple type to the schema
	 *
	 * @param string $name
	 * @param string $restrictionBase namespace:name (http://schemas.xmlsoap.org/soap/encoding/:Array)
	 * @param string $typeClass (should always be simpleType)
	 * @param string $phpType (should always be scalar)
	 * @param array $enumeration array of values
	 * @access public
	 * @see nusoap_xmlschema
	 * @see getTypeDef
	 */
	public function addSimpleType($name, $restrictionBase = '', $typeClass = 'simpleType', $phpType = 'scalar', $enumeration = array())
	{
	}
	/**
	 * adds an element to the schema
	 *
	 * @param array $attrs attributes that must include name and type
	 * @see nusoap_xmlschema
	 * @access public
	 */
	public function addElement($attrs)
	{
	}
}
/**
 * Backward compatibility
 */
class XMLSchema extends \nusoap_xmlschema
{
}
/**
 * For creating serializable abstractions of native PHP types.  This class
 * allows element name/namespace, XSD type, and XML attributes to be
 * associated with a value.  This is extremely useful when WSDL is not
 * used, but is also useful when WSDL is used with polymorphic types, including
 * xsd:anyType and user-defined types.
 *
 * @author   Dietrich Ayala <dietrich@ganx4.com>
 * @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
 * @access   public
 */
class soapval extends \nusoap_base
{
	/**
	 * The XML element name
	 *
	 * @var string
	 * @access private
	 */
	public $name;
	/**
	 * The XML type name (string or false)
	 *
	 * @var mixed
	 * @access private
	 */
	public $type;
	/**
	 * The PHP value
	 *
	 * @var mixed
	 * @access private
	 */
	public $value;
	/**
	 * The XML element namespace (string or false)
	 *
	 * @var mixed
	 * @access private
	 */
	public $element_ns;
	/**
	 * The XML type namespace (string or false)
	 *
	 * @var mixed
	 * @access private
	 */
	public $type_ns;
	/**
	 * The XML element attributes (array or false)
	 *
	 * @var mixed
	 * @access private
	 */
	public $attributes;
	/** @var false|resource */
	public $fp;
	/**
	 * constructor
	 *
	 * @param    string $name optional name
	 * @param    mixed $type optional type name
	 * @param    mixed $value optional value
	 * @param    mixed $element_ns optional namespace of value
	 * @param    mixed $type_ns optional namespace of type
	 * @param    mixed $attributes associative array of attributes to add to element serialization
	 * @access   public
	 */
	public function __construct($name = 'soapval', $type = \false, $value = -1, $element_ns = \false, $type_ns = \false, $attributes = \false)
	{
	}
	/**
	 * return serialized value
	 *
	 * @param    string $use The WSDL use value (encoded|literal)
	 * @return    string XML data
	 * @access   public
	 */
	public function serialize($use = 'encoded')
	{
	}
	/**
	 * decodes a soapval object into a PHP native type
	 *
	 * @return    mixed
	 * @access   public
	 */
	public function decode()
	{
	}
}
/**
 * transport class for sending/receiving data via HTTP and HTTPS
 * NOTE: PHP must be compiled with the CURL extension for HTTPS support
 *
 * @author   Dietrich Ayala <dietrich@ganx4.com>
 * @author   Scott Nichol <snichol@users.sourceforge.net>
 * @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
 * @access public
 */
class soap_transport_http extends \nusoap_base
{
	public $query = '';
	public $tryagain = \false;
	public $url = '';
	public $uri = '';
	public $digest_uri = '';
	public $scheme = '';
	public $host = '';
	public $port = '';
	public $path = '';
	public $request_method = 'POST';
	public $protocol_version = '1.0';
	public $encoding = '';
	public $outgoing_headers = array();
	public $incoming_headers = array();
	public $incoming_cookies = array();
	public $outgoing_payload = '';
	public $incoming_payload = '';
	public $response_status_line;
	// HTTP response status line
	public $useSOAPAction = \true;
	public $persistentConnection = \false;
	public $ch = \false;
	// cURL handle
	public $ch_options = array();
	// cURL custom options
	public $use_curl = \false;
	// force cURL use
	public $proxy = \null;
	// proxy information (associative array)
	public $username = '';
	public $password = '';
	public $authtype = '';
	public $digestRequest = array();
	public $certRequest = array();
	// keys must be cainfofile (optional), sslcertfile, sslkeyfile, passphrase, certpassword (optional), verifypeer (optional), verifyhost (optional)
	// cainfofile: certificate authority file, e.g. '$pathToPemFiles/rootca.pem'
	// sslcertfile: SSL certificate file, e.g. '$pathToPemFiles/mycert.pem'
	// sslkeyfile: SSL key file, e.g. '$pathToPemFiles/mykey.pem'
	// passphrase: SSL key password/passphrase
	// certpassword: SSL certificate password
	// verifypeer: default is 1
	// verifyhost: default is 1
	/** @var false|resource */
	public $fp;
	public $errno;
	/**
	 * constructor
	 *
	 * @param string $url The URL to which to connect
	 * @param array $curl_options User-specified cURL options
	 * @param boolean $use_curl Whether to try to force cURL use
	 * @access public
	 */
	public function __construct($url, $curl_options = \null, $use_curl = \false)
	{
	}
	/**
	 * sets a cURL option
	 *
	 * @param    mixed $option The cURL option (always integer?)
	 * @param    mixed $value The cURL option value
	 * @access   private
	 */
	public function setCurlOption($option, $value)
	{
	}
	/**
	 * sets an HTTP header
	 *
	 * @param string $name The name of the header
	 * @param string $value The value of the header
	 * @access private
	 */
	public function setHeader($name, $value)
	{
	}
	/**
	 * unsets an HTTP header
	 *
	 * @param string $name The name of the header
	 * @access private
	 */
	public function unsetHeader($name)
	{
	}
	/**
	 * sets the URL to which to connect
	 *
	 * @param string $url The URL to which to connect
	 * @access private
	 */
	public function setURL($url)
	{
	}
	/**
	 * gets the I/O method to use
	 *
	 * @return    string    I/O method to use (socket|curl|unknown)
	 * @access    private
	 */
	public function io_method()
	{
	}
	/**
	 * establish an HTTP connection
	 *
	 * @param    integer $connection_timeout set connection timeout in seconds
	 * @param    integer $response_timeout set response timeout in seconds
	 * @return    boolean true if connected, false if not
	 * @access   private
	 */
	public function connect($connection_timeout = 0, $response_timeout = 30)
	{
	}
	/**
	 * sends the SOAP request and gets the SOAP response via HTTP[S]
	 *
	 * @param    string $data message data
	 * @param    integer $timeout set connection timeout in seconds
	 * @param    integer $response_timeout set response timeout in seconds
	 * @param    array $cookies cookies to send
	 * @return    string data
	 * @access   public
	 */
	public function send($data, $timeout = 0, $response_timeout = 30, $cookies = \null)
	{
	}
	/**
	 * sends the SOAP request and gets the SOAP response via HTTPS using CURL
	 *
	 * @param    string $data message data
	 * @param    integer $timeout set connection timeout in seconds
	 * @param    integer $response_timeout set response timeout in seconds
	 * @param    array $cookies cookies to send
	 * @return    string data
	 * @access   public
	 */
	public function sendHTTPS($data, $timeout = 0, $response_timeout = 30, $cookies = \NULL)
	{
	}
	/**
	 * if authenticating, set user credentials here
	 *
	 * @param    string $username
	 * @param    string $password
	 * @param    string $authtype (basic|digest|certificate|ntlm)
	 * @param    array $digestRequest (keys must be nonce, nc, realm, qop)
	 * @param    array $certRequest (keys must be cainfofile (optional), sslcertfile, sslkeyfile, passphrase, certpassword (optional), verifypeer (optional), verifyhost (optional): see corresponding options in cURL docs)
	 * @access   public
	 */
	public function setCredentials($username, $password, $authtype = 'basic', $digestRequest = array(), $certRequest = array())
	{
	}
	/**
	 * set the soapaction value
	 *
	 * @param    string $soapaction
	 * @access   public
	 */
	public function setSOAPAction($soapaction)
	{
	}
	/**
	 * use http encoding
	 *
	 * @param    string $enc encoding style. supported values: gzip, deflate, or both
	 * @access   public
	 */
	public function setEncoding($enc = 'gzip, deflate')
	{
	}
	/**
	 * set proxy info here
	 *
	 * @param    string $proxyhost use an empty string to remove proxy
	 * @param    string $proxyport
	 * @param    string $proxyusername
	 * @param    string $proxypassword
	 * @param    string $proxyauthtype (basic|ntlm)
	 * @access   public
	 */
	public function setProxy($proxyhost, $proxyport, $proxyusername = '', $proxypassword = '', $proxyauthtype = 'basic')
	{
	}
	/**
	 * Test if the given string starts with a header that is to be skipped.
	 * Skippable headers result from chunked transfer and proxy requests.
	 *
	 * @param    string $data The string to check.
	 * @returns    boolean    Whether a skippable header was found.
	 * @access    private
	 */
	public function isSkippableCurlHeader($data)
	{
	}
	/**
	 * decode a string that is encoded w/ "chunked' transfer encoding
	 * as defined in RFC2068 19.4.6
	 *
	 * @param    string $buffer
	 * @param    string $lb
	 * @returns    string
	 * @access   public
	 * @deprecated
	 */
	public function decodeChunked($buffer, $lb)
	{
	}
	/**
	 * Writes the payload, including HTTP headers, to $this->outgoing_payload.
	 *
	 * @param    string $data HTTP body
	 * @param    string $cookie_str data for HTTP Cookie header
	 * @return    void
	 * @access    private
	 */
	public function buildPayload($data, $cookie_str = '')
	{
	}
	/**
	 * sends the SOAP request via HTTP[S]
	 *
	 * @param    string $data message data
	 * @param    array $cookies cookies to send
	 * @return    boolean    true if OK, false if problem
	 * @access   private
	 */
	public function sendRequest($data, $cookies = \null)
	{
	}
	/**
	 * gets the SOAP response via HTTP[S]
	 *
	 * @return    string the response (also sets member variables like incoming_payload)
	 * @access   private
	 */
	public function getResponse()
	{
	}
	/**
	 * sets the content-type for the SOAP message to be sent
	 *
	 * @param    string $type the content type, MIME style
	 * @param    mixed $charset character set used for encoding (or false)
	 * @access    public
	 */
	public function setContentType($type, $charset = \false)
	{
	}
	/**
	 * specifies that an HTTP persistent connection should be used
	 *
	 * @return    boolean whether the request was honored by this method.
	 * @access    public
	 */
	public function usePersistentConnection()
	{
	}
	/**
	 * parse an incoming Cookie into it's parts
	 *
	 * @param    string $cookie_str content of cookie
	 * @return    array with data of that cookie
	 * @access    private
	 */
	/*
	 * TODO: allow a Set-Cookie string to be parsed into multiple cookies
	 */
	public function parseCookie($cookie_str)
	{
	}
	/**
	 * sort out cookies for the current request
	 *
	 * @param    array $cookies array with all cookies
	 * @param    boolean $secure is the send-content secure or not?
	 * @return    string for Cookie-HTTP-Header
	 * @access    private
	 */
	public function getCookiesForRequest($cookies, $secure = \false)
	{
	}
}
/**
 *
 * nusoap_server allows the user to create a SOAP server
 * that is capable of receiving messages and returning responses
 *
 * @author   Dietrich Ayala <dietrich@ganx4.com>
 * @author   Scott Nichol <snichol@users.sourceforge.net>
 * @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
 * @access   public
 */
class nusoap_server extends \nusoap_base
{
	/**
	 * HTTP headers of request
	 *
	 * @var array
	 * @access private
	 */
	public $headers = array();
	/**
	 * HTTP request
	 *
	 * @var string
	 * @access private
	 */
	public $request = '';
	/**
	 * SOAP headers from request (incomplete namespace resolution; special characters not escaped) (text)
	 *
	 * @var string
	 * @access public
	 */
	public $requestHeaders = '';
	/**
	 * SOAP Headers from request (parsed)
	 *
	 * @var mixed
	 * @access public
	 */
	public $requestHeader = \null;
	/**
	 * SOAP body request portion (incomplete namespace resolution; special characters not escaped) (text)
	 *
	 * @var string
	 * @access public
	 */
	public $document = '';
	/**
	 * SOAP payload for request (text)
	 *
	 * @var string
	 * @access public
	 */
	public $requestSOAP = '';
	/**
	 * requested method namespace URI
	 *
	 * @var string
	 * @access private
	 */
	public $methodURI = '';
	/**
	 * name of method requested
	 *
	 * @var string
	 * @access private
	 */
	public $methodname = '';
	/**
	 * name of the response tag name
	 *
	 * @var string
	 * @access private
	 */
	public $responseTagName = '';
	/**
	 * method parameters from request
	 *
	 * @var array
	 * @access private
	 */
	public $methodparams = array();
	/**
	 * SOAP Action from request
	 *
	 * @var string
	 * @access private
	 */
	public $SOAPAction = '';
	/**
	 * character set encoding of incoming (request) messages
	 *
	 * @var string
	 * @access public
	 */
	public $xml_encoding = '';
	/**
	 * toggles whether the parser decodes element content w/ utf8_decode()
	 *
	 * @var boolean
	 * @access public
	 */
	public $decode_utf8 = \true;
	/**
	 * HTTP headers of response
	 *
	 * @var array
	 * @access public
	 */
	public $outgoing_headers = array();
	/**
	 * HTTP response
	 *
	 * @var string
	 * @access private
	 */
	public $response = '';
	/**
	 * SOAP headers for response (text or array of soapval or associative array)
	 *
	 * @var mixed
	 * @access public
	 */
	public $responseHeaders = '';
	/**
	 * SOAP payload for response (text)
	 *
	 * @var string
	 * @access private
	 */
	public $responseSOAP = '';
	/**
	 * SOAP attachments in response
	 *
	 * @var string
	 * @access private
	 */
	public $attachments = '';
	/**
	 * method return value to place in response
	 *
	 * @var mixed
	 * @access private
	 */
	public $methodreturn = \false;
	/**
	 * whether $methodreturn is a string of literal XML
	 *
	 * @var boolean
	 * @access public
	 */
	public $methodreturnisliteralxml = \false;
	/**
	 * SOAP fault for response (or false)
	 *
	 * @var mixed
	 * @access private
	 */
	public $fault = \false;
	/**
	 * text indication of result (for debugging)
	 *
	 * @var string
	 * @access private
	 */
	public $result = 'successful';
	/**
	 * assoc array of operations => opData; operations are added by the register()
	 * method or by parsing an external WSDL definition
	 *
	 * @var array
	 * @access private
	 */
	public $operations = array();
	/**
	 * wsdl instance (if one)
	 *
	 * @var false|wsdl
	 * @access private
	 */
	public $wsdl = \false;
	/**
	 * URL for WSDL (if one)
	 *
	 * @var false|string
	 * @access private
	 */
	public $externalWSDLURL = \false;
	/**
	 * whether to append debug to response as XML comment
	 *
	 * @var boolean
	 * @access public
	 */
	public $debug_flag = \false;
	/** @var array */
	public $opData;
	/**
	 * constructor
	 * the optional parameter is a path to a WSDL file that you'd like to bind the server instance to.
	 *
	 * @param false|string|wsdl $wsdl file path or URL (string), or wsdl instance (object)
	 * @access   public
	 */
	public function __construct($wsdl = \false)
	{
	}
	/**
	 * processes request and returns response
	 *
	 * @param    string $data usually is the value of $HTTP_RAW_POST_DATA
	 * @access   public
	 */
	public function service($data)
	{
	}
	/**
	 * parses HTTP request headers.
	 *
	 * The following fields are set by this function (when successful)
	 *
	 * headers
	 * request
	 * xml_encoding
	 * SOAPAction
	 *
	 * @access   private
	 */
	public function parse_http_headers()
	{
	}
	/**
	 * parses a request
	 *
	 * The following fields are set by this function (when successful)
	 *
	 * headers
	 * request
	 * xml_encoding
	 * SOAPAction
	 * request
	 * requestSOAP
	 * methodURI
	 * methodname
	 * methodparams
	 * requestHeaders
	 * document
	 *
	 * This sets the fault field on error
	 *
	 * @param    string $data XML string
	 * @access   private
	 */
	public function parse_request($data = '')
	{
	}
	/**
	 * invokes a PHP function for the requested SOAP method
	 *
	 * The following fields are set by this function (when successful)
	 *
	 * methodreturn
	 *
	 * Note that the PHP function that is called may also set the following
	 * fields to affect the response sent to the client
	 *
	 * responseHeaders
	 * outgoing_headers
	 *
	 * This sets the fault field on error
	 *
	 * @access   private
	 */
	public function invoke_method()
	{
	}
	/**
	 * serializes the return value from a PHP function into a full SOAP Envelope
	 *
	 * The following fields are set by this function (when successful)
	 *
	 * responseSOAP
	 *
	 * This sets the fault field on error
	 *
	 * @access   private
	 */
	public function serialize_return()
	{
	}
	/**
	 * sends an HTTP response
	 *
	 * The following fields are set by this function (when successful)
	 *
	 * outgoing_headers
	 * response
	 *
	 * @access   private
	 */
	public function send_response()
	{
	}
	/**
	 * takes the value that was created by parsing the request
	 * and compares to the method's signature, if available.
	 *
	 * @param    string $operation The operation to be invoked
	 * @param    array $request The array of parameter values
	 * @return    boolean    Whether the operation was found
	 * @access   private
	 */
	public function verify_method($operation, $request)
	{
	}
	/**
	 * processes SOAP message received from client
	 *
	 * @param    array $headers The HTTP headers
	 * @param    string $data unprocessed request data from client
	 * @return   false|void void or false on error
	 * @access   private
	 */
	public function parseRequest($headers, $data)
	{
	}
	/**
	 * gets the HTTP body for the current response.
	 *
	 * @param string $soapmsg The SOAP payload
	 * @return string The HTTP body, which includes the SOAP payload
	 * @access private
	 */
	public function getHTTPBody($soapmsg)
	{
	}
	/**
	 * gets the HTTP content type for the current response.
	 *
	 * Note: getHTTPBody must be called before this.
	 *
	 * @return string the HTTP content type for the current response.
	 * @access private
	 */
	public function getHTTPContentType()
	{
	}
	/**
	 * gets the HTTP content type charset for the current response.
	 * returns false for non-text content types.
	 *
	 * Note: getHTTPBody must be called before this.
	 *
	 * @return string the HTTP content type charset for the current response.
	 * @access private
	 */
	public function getHTTPContentTypeCharset()
	{
	}
	/**
	 * add a method to the dispatch map (this has been replaced by the register method)
	 *
	 * @param    string $methodname
	 * @param    string $in array of input values
	 * @param    string $out array of output values
	 * @access   public
	 * @deprecated
	 */
	public function add_to_map($methodname, $in, $out)
	{
	}
	/**
	 * register a service function with the server
	 *
	 * @param    string $name the name of the PHP function, class.method or class..method
	 * @param    array $in assoc array of input values: key = param name, value = param type
	 * @param    array $out assoc array of output values: key = param name, value = param type
	 * @param    mixed $namespace the element namespace for the method or false
	 * @param    mixed $soapaction the soapaction for the method or false
	 * @param    mixed $style optional (rpc|document) or false Note: when 'document' is specified, parameter and return wrappers are created for you automatically
	 * @param    mixed $use optional (encoded|literal) or false
	 * @param    string $documentation optional Description to include in WSDL
	 * @param    string $encodingStyle optional (usually 'http://schemas.xmlsoap.org/soap/encoding/' for encoded)
	 * @param    string $customResponseTagName optional Name of the outgoing response, default $name . 'Response'
	 * @access   public
	 */
	public function register($name, $in = array(), $out = array(), $namespace = \false, $soapaction = \false, $style = \false, $use = \false, $documentation = '', $encodingStyle = '', $customResponseTagName = '')
	{
	}
	/**
	 * Specify a fault to be returned to the client.
	 * This also acts as a flag to the server that a fault has occured.
	 *
	 * @param    string $faultcode
	 * @param    string $faultstring
	 * @param    string $faultactor
	 * @param    string $faultdetail
	 * @access   public
	 */
	public function fault($faultcode, $faultstring, $faultactor = '', $faultdetail = '')
	{
	}
	/**
	 * Sets up wsdl object.
	 * Acts as a flag to enable internal WSDL generation
	 *
	 * @param string $serviceName , name of the service
	 * @param mixed $namespace optional 'tns' service namespace or false
	 * @param mixed $endpoint optional URL of service endpoint or false
	 * @param string $style optional (rpc|document) WSDL style (also specified by operation)
	 * @param string $transport optional SOAP transport
	 * @param mixed $schemaTargetNamespace optional 'types' targetNamespace for service schema or false
	 */
	public function configureWSDL($serviceName, $namespace = \false, $endpoint = \false, $style = 'rpc', $transport = 'http://schemas.xmlsoap.org/soap/http', $schemaTargetNamespace = \false)
	{
	}
}
/**
 * Backward compatibility
 */
class soap_server extends \nusoap_server
{
}
/**
 * parses a WSDL file, allows access to it's data, other utility methods.
 * also builds WSDL structures programmatically.
 *
 * @author   Dietrich Ayala <dietrich@ganx4.com>
 * @author   Scott Nichol <snichol@users.sourceforge.net>
 * @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
 * @access public
 */
class wsdl extends \nusoap_base
{
	// URL or filename of the root of this WSDL
	public $wsdl;
	// define internal arrays of bindings, ports, operations, messages, etc.
	public $schemas = array();
	public $currentSchema;
	public $message = array();
	public $complexTypes = array();
	public $messages = array();
	public $currentMessage;
	public $currentOperation;
	public $portTypes = array();
	public $currentPortType;
	public $bindings = array();
	public $currentBinding;
	public $ports = array();
	public $currentPort;
	public $opData = array();
	public $status = '';
	public $documentation = \false;
	public $endpoint = '';
	// array of wsdl docs to import
	public $import = array();
	// parser vars
	public $parser;
	public $position = 0;
	public $depth = 0;
	public $depth_array = array();
	// for getting wsdl
	public $proxyhost = '';
	public $proxyport = '';
	public $proxyusername = '';
	public $proxypassword = '';
	public $timeout = 0;
	public $response_timeout = 30;
	public $curl_options = array();
	// User-specified cURL options
	public $use_curl = \false;
	// whether to always try to use cURL
	// for HTTP authentication
	public $username = '';
	// Username for HTTP authentication
	public $password = '';
	// Password for HTTP authentication
	public $authtype = '';
	// Type of HTTP authentication
	public $certRequest = array();
	// Certificate for HTTP SSL authentication
	/** @var mixed */
	public $currentPortOperation;
	/** @var string */
	public $opStatus;
	/** @var mixed */
	public $serviceName;
	public $wsdl_info;
	/**
	 * constructor
	 *
	 * @param string $wsdl WSDL document URL
	 * @param string $proxyhost
	 * @param string $proxyport
	 * @param string $proxyusername
	 * @param string $proxypassword
	 * @param integer $timeout set the connection timeout
	 * @param integer $response_timeout set the response timeout
	 * @param array $curl_options user-specified cURL options
	 * @param boolean $use_curl try to use cURL
	 * @access public
	 */
	public function __construct($wsdl = '', $proxyhost = \false, $proxyport = \false, $proxyusername = \false, $proxypassword = \false, $timeout = 0, $response_timeout = 30, $curl_options = \null, $use_curl = \false)
	{
	}
	/**
	 * fetches the WSDL document and parses it
	 *
	 * @access public
	 */
	public function fetchWSDL($wsdl)
	{
	}
	/**
	 * parses the wsdl document
	 *
	 * @param string $wsdl path or URL
	 * @access private
	 */
	public function parseWSDL($wsdl = '')
	{
	}
	/**
	 * start-element handler
	 *
	 * @param string $parser XML parser object
	 * @param string $name element name
	 * @param array $attrs associative array of attributes
	 * @access private
	 */
	public function start_element($parser, $name, $attrs)
	{
	}
	/**
	 * end-element handler
	 *
	 * @param string $parser XML parser object
	 * @param string $name element name
	 * @access private
	 */
	public function end_element($parser, $name)
	{
	}
	/**
	 * element content handler
	 *
	 * @param string $parser XML parser object
	 * @param string $data element content
	 * @access private
	 */
	public function character_data($parser, $data)
	{
	}
	/**
	 * if authenticating, set user credentials here
	 *
	 * @param    string $username
	 * @param    string $password
	 * @param    string $authtype (basic|digest|certificate|ntlm)
	 * @param    array $certRequest (keys must be cainfofile (optional), sslcertfile, sslkeyfile, passphrase, certpassword (optional), verifypeer (optional), verifyhost (optional): see corresponding options in cURL docs)
	 * @access   public
	 */
	public function setCredentials($username, $password, $authtype = 'basic', $certRequest = array())
	{
	}
	public function getBindingData($binding)
	{
	}
	/**
	 * returns an assoc array of operation names => operation data
	 *
	 * @param string $portName WSDL port name
	 * @param string $bindingType eg: soap, smtp, dime (only soap and soap12 are currently supported)
	 * @return array
	 * @access public
	 */
	public function getOperations($portName = '', $bindingType = 'soap')
	{
	}
	/**
	 * returns an associative array of data necessary for calling an operation
	 *
	 * @param string $operation name of operation
	 * @param string $bindingType type of binding eg: soap, soap12
	 * @return array
	 * @access public
	 */
	public function getOperationData($operation, $bindingType = 'soap')
	{
	}
	/**
	 * returns an associative array of data necessary for calling an operation
	 *
	 * @param string $soapAction soapAction for operation
	 * @param string $bindingType type of binding eg: soap, soap12
	 * @return array
	 * @access public
	 */
	public function getOperationDataForSoapAction($soapAction, $bindingType = 'soap')
	{
	}
	/**
	 * returns an array of information about a given type
	 * returns false if no type exists by the given name
	 *     typeDef = array(
	 *     'elements' => array(), // refs to elements array
	 *    'restrictionBase' => '',
	 *    'phpType' => '',
	 *    'order' => '(sequence|all)',
	 *    'attrs' => array() // refs to attributes array
	 *    )
	 *
	 * @param string $type the type
	 * @param string $ns namespace (not prefix) of the type
	 * @return false
	 * @access public
	 * @see nusoap_xmlschema
	 */
	public function getTypeDef($type, $ns)
	{
	}
	/**
	 * prints html description of services
	 *
	 * @access private
	 */
	public function webDescription()
	{
	}
	/**
	 * serialize the parsed wsdl
	 *
	 * @param mixed $debug whether to put debug=1 in endpoint URL
	 * @return string serialization of WSDL
	 * @access public
	 */
	public function serialize($debug = 0)
	{
	}
	/**
	 * determine whether a set of parameters are unwrapped
	 * when they are expect to be wrapped, Microsoft-style.
	 *
	 * @param string $type the type (element name) of the wrapper
	 * @param array $parameters the parameter values for the SOAP call
	 * @return boolean whether they parameters are unwrapped (and should be wrapped)
	 * @access private
	 */
	public function parametersMatchWrapped($type, $parameters)
	{
	}
	/**
	 * serialize PHP values according to a WSDL message definition
	 * contrary to the method name, this is not limited to RPC
	 *
	 * TODO
	 * - multi-ref serialization
	 * - validate PHP values against type definitions, return errors if invalid
	 *
	 * @param string $operation operation name
	 * @param string $direction (input|output)
	 * @param mixed $parameters parameter value(s)
	 * @param string $bindingType (soap|soap12)
	 * @return false|string parameters serialized as XML or false on error (e.g. operation not found)
	 * @access public
	 */
	public function serializeRPCParameters($operation, $direction, $parameters, $bindingType = 'soap')
	{
	}
	/**
	 * serialize a PHP value according to a WSDL message definition
	 *
	 * TODO
	 * - multi-ref serialization
	 * - validate PHP values against type definitions, return errors if invalid
	 *
	 * @param string $operation operation name
	 * @param string $direction (input|output)
	 * @param mixed $parameters parameter value(s)
	 * @return false|string parameters serialized as XML or false on error (e.g. operation not found)
	 * @access public
	 * @deprecated
	 */
	public function serializeParameters($operation, $direction, $parameters)
	{
	}
	/**
	 * serializes a PHP value according a given type definition
	 *
	 * @param string $name name of value (part or element)
	 * @param string $type XML schema type of value (type or element)
	 * @param mixed $value a native PHP value (parameter value)
	 * @param string $use use for part (encoded|literal)
	 * @param string $encodingStyle SOAP encoding style for the value (if different than the enclosing style)
	 * @param boolean $unqualified a kludge for what should be XML namespace form handling
	 * @return string value serialized as an XML string
	 * @access private
	 */
	public function serializeType($name, $type, $value, $use = 'encoded', $encodingStyle = \false, $unqualified = \false)
	{
	}
	/**
	 * serializes the attributes for a complexType
	 *
	 * @param array $typeDef our internal representation of an XML schema type (or element)
	 * @param mixed $value a native PHP value (parameter value)
	 * @param string $ns the namespace of the type
	 * @param string $uqType the local part of the type
	 * @return string value serialized as an XML string
	 * @access private
	 */
	public function serializeComplexTypeAttributes($typeDef, $value, $ns, $uqType)
	{
	}
	/**
	 * serializes the elements for a complexType
	 *
	 * @param array $typeDef our internal representation of an XML schema type (or element)
	 * @param mixed $value a native PHP value (parameter value)
	 * @param string $ns the namespace of the type
	 * @param string $uqType the local part of the type
	 * @param string $use use for part (encoded|literal)
	 * @param string $encodingStyle SOAP encoding style for the value (if different than the enclosing style)
	 * @return string value serialized as an XML string
	 * @access private
	 */
	public function serializeComplexTypeElements($typeDef, $value, $ns, $uqType, $use = 'encoded', $encodingStyle = \false)
	{
	}
	/**
	 * adds an XML Schema complex type to the WSDL types
	 *
	 * @param string $name
	 * @param string $typeClass (complexType|simpleType|attribute)
	 * @param string $phpType currently supported are array and struct (php assoc array)
	 * @param string $compositor (all|sequence|choice)
	 * @param string $restrictionBase namespace:name (http://schemas.xmlsoap.org/soap/encoding/:Array)
	 * @param array $elements e.g. array ( name => array(name=>'',type=>'') )
	 * @param array $attrs e.g. array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'xsd:string[]'))
	 * @param string $arrayType as namespace:name (xsd:string)
	 * @see nusoap_xmlschema
	 * @access public
	 */
	public function addComplexType($name, $typeClass = 'complexType', $phpType = 'array', $compositor = '', $restrictionBase = '', $elements = array(), $attrs = array(), $arrayType = '')
	{
	}
	/**
	 * adds an XML Schema simple type to the WSDL types
	 *
	 * @param string $name
	 * @param string $restrictionBase namespace:name (http://schemas.xmlsoap.org/soap/encoding/:Array)
	 * @param string $typeClass (should always be simpleType)
	 * @param string $phpType (should always be scalar)
	 * @param array $enumeration array of values
	 * @see nusoap_xmlschema
	 * @access public
	 */
	public function addSimpleType($name, $restrictionBase = '', $typeClass = 'simpleType', $phpType = 'scalar', $enumeration = array())
	{
	}
	/**
	 * adds an element to the WSDL types
	 *
	 * @param array $attrs attributes that must include name and type
	 * @see nusoap_xmlschema
	 * @access public
	 */
	public function addElement($attrs)
	{
	}
	/**
	 * register an operation with the server
	 *
	 * @param string $name operation (method) name
	 * @param array $in assoc array of input values: key = param name, value = param type
	 * @param array $out assoc array of output values: key = param name, value = param type
	 * @param string $namespace optional The namespace for the operation
	 * @param string $soapaction optional The soapaction for the operation
	 * @param string $style (rpc|document) optional The style for the operation Note: when 'document' is specified, parameter and return wrappers are created for you automatically
	 * @param string $use (encoded|literal) optional The use for the parameters (cannot mix right now)
	 * @param string $documentation optional The description to include in the WSDL
	 * @param string $encodingStyle optional (usually 'http://schemas.xmlsoap.org/soap/encoding/' for encoded)
	 * @param string $customResponseTagName optional Name of the outgoing response
	 * @access public
	 */
	public function addOperation($name, $in = \false, $out = \false, $namespace = \false, $soapaction = \false, $style = 'rpc', $use = 'encoded', $documentation = '', $encodingStyle = '', $customResponseTagName = '')
	{
	}
}
/**
 *
 * nusoap_parser class parses SOAP XML messages into native PHP values
 *
 * @author   Dietrich Ayala <dietrich@ganx4.com>
 * @author   Scott Nichol <snichol@users.sourceforge.net>
 * @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
 * @access   public
 */
class nusoap_parser extends \nusoap_base
{
	public $parser = \null;
	public $methodNamespace = '';
	public $xml = '';
	public $xml_encoding = '';
	public $method = '';
	public $root_struct = '';
	public $root_struct_name = '';
	public $root_struct_namespace = '';
	public $root_header = '';
	public $document = '';
	// incoming SOAP body (text)
	// determines where in the message we are (envelope,header,body,method)
	public $status = '';
	public $position = 0;
	public $depth = 0;
	public $default_namespace = '';
	public $namespaces = array();
	public $message = array();
	public $parent = '';
	public $fault = \false;
	public $fault_code = '';
	public $fault_str = '';
	public $fault_detail = '';
	public $depth_array = array();
	public $debug_flag = \true;
	public $soapresponse = \null;
	// parsed SOAP Body
	public $soapheader = \null;
	// parsed SOAP Header
	public $responseHeaders = '';
	// incoming SOAP headers (text)
	public $body_position = 0;
	// for multiref parsing:
	// array of id => pos
	public $ids = array();
	// array of id => hrefs => pos
	public $multirefs = array();
	// toggle for auto-decoding element content
	public $decode_utf8 = \true;
	public $attachments = array();
	/**
	 * constructor that actually does the parsing
	 *
	 * @param    string $xml SOAP message
	 * @param    string $encoding character encoding scheme of message
	 * @param    string $method method for which XML is parsed (unused?)
	 * @param    string $decode_utf8 whether to decode UTF-8 to ISO-8859-1
	 * @access   public
	 */
	public function __construct($xml, $encoding = 'UTF-8', $method = '', $decode_utf8 = \true)
	{
	}
	/**
	 * start-element handler
	 *
	 * @param    resource $parser XML parser object
	 * @param    string $name element name
	 * @param    array $attrs associative array of attributes
	 * @access   private
	 */
	public function start_element($parser, $name, $attrs)
	{
	}
	/**
	 * end-element handler
	 *
	 * @param    resource $parser XML parser object
	 * @param    string $name element name
	 * @access   private
	 */
	public function end_element($parser, $name)
	{
	}
	/**
	 * element content handler
	 *
	 * @param    resource $parser XML parser object
	 * @param    string $data element content
	 * @access   private
	 */
	public function character_data($parser, $data)
	{
	}
	/**
	 * get the parsed message (SOAP Body)
	 *
	 * @return    mixed
	 * @access   public
	 * @deprecated    use get_soapbody instead
	 */
	public function get_response()
	{
	}
	/**
	 * get the parsed SOAP Body (null if there was none)
	 *
	 * @return    mixed
	 * @access   public
	 */
	public function get_soapbody()
	{
	}
	/**
	 * get the parsed SOAP Header (null if there was none)
	 *
	 * @return    mixed
	 * @access   public
	 */
	public function get_soapheader()
	{
	}
	/**
	 * get the unparsed SOAP Header
	 *
	 * @return    string XML or empty if no Header
	 * @access   public
	 */
	public function getHeaders()
	{
	}
	/**
	 * decodes simple types into PHP variables
	 *
	 * @param    string $value value to decode
	 * @param    string $type XML type to decode
	 * @param    string $typens XML type namespace to decode
	 * @return    mixed PHP value
	 * @access   private
	 */
	public function decodeSimple($value, $type, $typens)
	{
	}
	/**
	 * builds response structures for compound values (arrays/structs)
	 * and scalars
	 *
	 * @param    integer $pos position in node tree
	 * @return    mixed    PHP value
	 * @access   private
	 */
	public function buildVal($pos)
	{
	}
}
/**
 * Backward compatibility
 */
class soap_parser extends \nusoap_parser
{
}
/**
 *
 * [nu]soapclient higher level class for easy usage.
 *
 * usage:
 *
 * // instantiate client with server info
 * $soapclient = new nusoap_client( string path [ ,mixed wsdl] );
 *
 * // call method, get results
 * echo $soapclient->call( string methodname [ ,array parameters] );
 *
 * // bye bye client
 * unset($soapclient);
 *
 * @author   Dietrich Ayala <dietrich@ganx4.com>
 * @author   Scott Nichol <snichol@users.sourceforge.net>
 * @version  $Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $
 * @access   public
 */
class nusoap_client extends \nusoap_base
{
	public $attachments = '';
	public $return = \null;
	public $operation = '';
	public $opData = array();
	public $username = '';
	// Username for HTTP authentication
	public $password = '';
	// Password for HTTP authentication
	public $authtype = '';
	// Type of HTTP authentication
	public $certRequest = array();
	// Certificate for HTTP SSL authentication
	public $requestHeaders = \false;
	// SOAP headers in request (text)
	public $responseHeaders = '';
	// SOAP headers from response (incomplete namespace resolution) (text)
	public $responseHeader = \null;
	// SOAP Header from response (parsed)
	public $document = '';
	// SOAP body response portion (incomplete namespace resolution) (text)
	public $endpoint;
	public $forceEndpoint = '';
	// overrides WSDL endpoint
	public $proxyhost = '';
	public $proxyport = '';
	public $proxyusername = '';
	public $proxypassword = '';
	public $portName = '';
	// port name to use in WSDL
	public $xml_encoding = '';
	// character set encoding of incoming (response) messages
	public $http_encoding = \false;
	public $timeout = 0;
	// HTTP connection timeout
	public $response_timeout = 30;
	// HTTP response timeout
	public $endpointType = '';
	// soap|wsdl, empty for WSDL initialization error
	public $persistentConnection = \false;
	public $defaultRpcParams = \false;
	// This is no longer used
	public $request = '';
	// HTTP request
	public $response = '';
	// HTTP response
	public $responseData = '';
	// SOAP payload of response
	public $cookies = array();
	// Cookies from response or for request
	public $decode_utf8 = \true;
	// toggles whether the parser decodes element content w/ utf8_decode()
	public $operations = array();
	// WSDL operations, empty for WSDL initialization error
	public $curl_options = array();
	// User-specified cURL options
	public $bindingType = '';
	// WSDL operation binding type
	public $use_curl = \false;
	// whether to always try to use cURL
	/*
	 * fault related variables
	 */
	/**
	 * @var      bool
	 * @access   public
	 */
	public $fault;
	/**
	 * @var      string
	 * @access   public
	 */
	public $faultcode;
	/**
	 * @var      string
	 * @access   public
	 */
	public $faultstring;
	/**
	 * @var      string
	 * @access   public
	 */
	public $faultdetail;
	/** @var wsdl|null */
	public $wsdl;
	/** @var mixed */
	public $wsdlFile;
	/**
	 * constructor
	 *
	 * @param    mixed $endpoint SOAP server or WSDL URL (string), or wsdl instance (object)
	 * @param    mixed $wsdl optional, set to 'wsdl' or true if using WSDL
	 * @param    string $proxyhost optional
	 * @param    string $proxyport optional
	 * @param    string $proxyusername optional
	 * @param    string $proxypassword optional
	 * @param    integer $timeout set the connection timeout
	 * @param    integer $response_timeout set the response timeout
	 * @param    string $portName optional portName in WSDL document
	 * @access   public
	 */
	public function __construct($endpoint, $wsdl = \false, $proxyhost = \false, $proxyport = \false, $proxyusername = \false, $proxypassword = \false, $timeout = 0, $response_timeout = 30, $portName = '')
	{
	}
	/**
	 * calls method, returns PHP native type
	 *
	 * @param    string $operation SOAP server URL or path
	 * @param    mixed $params An array, associative or simple, of the parameters
	 *                          for the method call, or a string that is the XML
	 *                          for the call.  For rpc style, this call will
	 *                          wrap the XML in a tag named after the method, as
	 *                          well as the SOAP Envelope and Body.  For document
	 *                          style, this will only wrap with the Envelope and Body.
	 *                          IMPORTANT: when using an array with document style,
	 *                          in which case there
	 *                         is really one parameter, the root of the fragment
	 *                         used in the call, which encloses what programmers
	 *                         normally think of parameters.  A parameter array
	 *                         *must* include the wrapper.
	 * @param    string $namespace optional method namespace (WSDL can override)
	 * @param    string $soapAction optional SOAPAction value (WSDL can override)
	 * @param    mixed $headers optional string of XML with SOAP header content, or array of soapval objects for SOAP headers, or associative array
	 * @param    boolean $rpcParams optional (no longer used)
	 * @param    string $style optional (rpc|document) the style to use when serializing parameters (WSDL can override)
	 * @param    string $use optional (encoded|literal|literal wrapped) the use when serializing parameters (WSDL can override)
	 * @return    mixed    response from SOAP call, normally an associative array mirroring the structure of the XML response, false for certain fatal errors
	 * @access   public
	 */
	public function call($operation, $params = array(), $namespace = 'http://tempuri.org', $soapAction = '', $headers = \false, $rpcParams = \null, $style = 'rpc', $use = 'encoded')
	{
	}
	/**
	 * check WSDL passed as an instance or pulled from an endpoint
	 *
	 * @access   private
	 */
	public function checkWSDL()
	{
	}
	/**
	 * instantiate wsdl object and parse wsdl file
	 *
	 * @access    public
	 */
	public function loadWSDL()
	{
	}
	/**
	 * get available data pertaining to an operation
	 *
	 * @param    string $operation operation name
	 * @return   array|false array of data pertaining to the operation, false on error or no data
	 * @access   public
	 */
	public function getOperationData($operation)
	{
	}
	/**
	 * send the SOAP message
	 *
	 * Note: if the operation has multiple return values
	 * the return value of this method will be an array
	 * of those values.
	 *
	 * @param    string $msg a SOAPx4 soapmsg object
	 * @param    string $soapaction SOAPAction value
	 * @param    integer $timeout set connection timeout in seconds
	 * @param    integer $response_timeout set response timeout in seconds
	 * @return    mixed native PHP types.
	 * @access   private
	 */
	public function send($msg, $soapaction = '', $timeout = 0, $response_timeout = 30)
	{
	}
	/**
	 * processes SOAP message returned from server
	 *
	 * @param    array $headers The HTTP headers
	 * @param    string $data unprocessed response data from server
	 * @return    mixed    value of the message, decoded into a PHP type
	 * @access   private
	 */
	public function parseResponse($headers, $data)
	{
	}
	/**
	 * sets user-specified cURL options
	 *
	 * @param    mixed $option The cURL option (always integer?)
	 * @param    mixed $value The cURL option value
	 * @access   public
	 */
	public function setCurlOption($option, $value)
	{
	}
	/**
	 * sets the SOAP endpoint, which can override WSDL
	 *
	 * @param    string $endpoint The endpoint URL to use, or empty string or false to prevent override
	 * @access   public
	 */
	public function setEndpoint($endpoint)
	{
	}
	/**
	 * set the SOAP headers
	 *
	 * @param    mixed $headers String of XML with SOAP header content, or array of soapval objects for SOAP headers
	 * @access   public
	 */
	public function setHeaders($headers)
	{
	}
	/**
	 * get the SOAP response headers (namespace resolution incomplete)
	 *
	 * @return    string
	 * @access   public
	 */
	public function getHeaders()
	{
	}
	/**
	 * get the SOAP response Header (parsed)
	 *
	 * @return    mixed
	 * @access   public
	 */
	public function getHeader()
	{
	}
	/**
	 * set proxy info here
	 *
	 * @param    string $proxyhost
	 * @param    string $proxyport
	 * @param    string $proxyusername
	 * @param    string $proxypassword
	 * @access   public
	 */
	public function setHTTPProxy($proxyhost, $proxyport, $proxyusername = '', $proxypassword = '')
	{
	}
	/**
	 * if authenticating, set user credentials here
	 *
	 * @param    string $username
	 * @param    string $password
	 * @param    string $authtype (basic|digest|certificate|ntlm)
	 * @param    array $certRequest (keys must be cainfofile (optional), sslcertfile, sslkeyfile, passphrase, verifypeer (optional), verifyhost (optional): see corresponding options in cURL docs)
	 * @access   public
	 */
	public function setCredentials($username, $password, $authtype = 'basic', $certRequest = array())
	{
	}
	/**
	 * use HTTP encoding
	 *
	 * @param    string $enc HTTP encoding
	 * @access   public
	 */
	public function setHTTPEncoding($enc = 'gzip, deflate')
	{
	}
	/**
	 * Set whether to try to use cURL connections if possible
	 *
	 * @param    boolean $use Whether to try to use cURL
	 * @access   public
	 */
	public function setUseCURL($use)
	{
	}
	/**
	 * use HTTP persistent connections if possible
	 *
	 * @access   public
	 */
	public function useHTTPPersistentConnection()
	{
	}
	/**
	 * gets the default RPC parameter setting.
	 * If true, default is that call params are like RPC even for document style.
	 * Each call() can override this value.
	 *
	 * This is no longer used.
	 *
	 * @return boolean
	 * @access public
	 * @deprecated
	 */
	public function getDefaultRpcParams()
	{
	}
	/**
	 * sets the default RPC parameter setting.
	 * If true, default is that call params are like RPC even for document style
	 * Each call() can override this value.
	 *
	 * This is no longer used.
	 *
	 * @param    boolean $rpcParams
	 * @access public
	 * @deprecated
	 */
	public function setDefaultRpcParams($rpcParams)
	{
	}
	/**
	 * dynamically creates an instance of a proxy class,
	 * allowing user to directly call methods from wsdl
	 *
	 * @return   object soap_proxy object
	 * @access   public
	 */
	public function getProxy()
	{
	}
	/**
	 * dynamically creates proxy class code
	 *
	 * @return   string PHP/NuSOAP code for the proxy class
	 * @access   private
	 */
	public function _getProxyClassCode($r)
	{
	}
	/**
	 * dynamically creates proxy class code
	 *
	 * @return   string PHP/NuSOAP code for the proxy class
	 * @access   public
	 */
	public function getProxyClassCode()
	{
	}
	/**
	 * gets the HTTP body for the current request.
	 *
	 * @param string $soapmsg The SOAP payload
	 * @return string The HTTP body, which includes the SOAP payload
	 * @access private
	 */
	public function getHTTPBody($soapmsg)
	{
	}
	/**
	 * gets the HTTP content type for the current request.
	 *
	 * Note: getHTTPBody must be called before this.
	 *
	 * @return string the HTTP content type for the current request.
	 * @access private
	 */
	public function getHTTPContentType()
	{
	}
	/**
	 * allows you to change the HTTP ContentType of the request.
	 *
	 * @param   string $contentTypeNew
	 * @return  void
	 */
	public function setHTTPContentType($contentTypeNew = "text/xml")
	{
	}
	/**
	 * gets the HTTP content type charset for the current request.
	 * returns false for non-text content types.
	 *
	 * Note: getHTTPBody must be called before this.
	 *
	 * @return string the HTTP content type charset for the current request.
	 * @access private
	 */
	public function getHTTPContentTypeCharset()
	{
	}
	/*
	 * whether or not parser should decode utf8 element content
	 *
	 * @return   always returns true
	 * @access   public
	 */
	public function decodeUTF8($bool)
	{
	}
	/**
	 * adds a new Cookie into $this->cookies array
	 *
	 * @param    string $name Cookie Name
	 * @param    string $value Cookie Value
	 * @return    boolean if cookie-set was successful returns true, else false
	 * @access    public
	 */
	public function setCookie($name, $value)
	{
	}
	/**
	 * gets all Cookies
	 *
	 * @return   array with all internal cookies
	 * @access   public
	 */
	public function getCookies()
	{
	}
	/**
	 * checks all Cookies and delete those which are expired
	 *
	 * @return   boolean always return true
	 * @access   private
	 */
	public function checkCookies()
	{
	}
	/**
	 * updates the current cookies with a new set
	 *
	 * @param    array $cookies new cookies with which to update current ones
	 * @return    boolean always return true
	 * @access    private
	 */
	public function UpdateCookies($cookies)
	{
	}
}
/**
 * caches instances of the wsdl class
 *
 * @author   Scott Nichol <snichol@users.sourceforge.net>
 * @author	Ingo Fischer <ingo@apollon.de>
 * @version  $Id: class.wsdlcache.php,v 1.7 2007/04/17 16:34:03 snichol Exp $
 * @access public
 */
class nusoap_wsdlcache
{
	/**
	 *	@var resource
	 *	@access private
	 */
	public $fplock;
	/**
	 *	@var integer
	 *	@access private
	 */
	public $cache_lifetime;
	/**
	 *	@var string
	 *	@access private
	 */
	public $cache_dir;
	/**
	 *	@var string
	 *	@access public
	 */
	public $debug_str = '';
	/**
	 * constructor
	 *
	 * @param string $cache_dir directory for cache-files
	 * @param integer $cache_lifetime lifetime for caching-files in seconds or 0 for unlimited
	 * @access public
	 */
	public function __construct($cache_dir = '.', $cache_lifetime = 0)
	{
	}
	/**
	 * creates the filename used to cache a wsdl instance
	 *
	 * @param string $wsdl The URL of the wsdl instance
	 * @return string The filename used to cache the instance
	 * @access private
	 */
	public function createFilename($wsdl)
	{
	}
	/**
	 * adds debug data to the class level debug string
	 *
	 * @param    string $string debug data
	 * @access   private
	 */
	public function debug($string)
	{
	}
	/**
	 * gets a wsdl instance from the cache
	 *
	 * @param string $wsdl The URL of the wsdl instance
	 * @return object wsdl The cached wsdl instance, null if the instance is not in the cache
	 * @access public
	 */
	public function get($wsdl)
	{
	}
	/**
	 * obtains the local mutex
	 *
	 * @param string $filename The Filename of the Cache to lock
	 * @param string $mode The open-mode ("r" or "w") or the file - affects lock-mode
	 * @return boolean Lock successfully obtained ?!
	 * @access private
	 */
	public function obtainMutex($filename, $mode)
	{
	}
	/**
	 * adds a wsdl instance to the cache
	 *
	 * @param wsdl $wsdl_instance The wsdl instance to add
	 * @return boolean WSDL successfully cached
	 * @access public
	 */
	public function put($wsdl_instance)
	{
	}
	/**
	 * releases the local mutex
	 *
	 * @param string $filename The Filename of the Cache to lock
	 * @return boolean Lock successfully released
	 * @access private
	 */
	public function releaseMutex($filename)
	{
	}
	/**
	 * removes a wsdl instance from the cache
	 *
	 * @param string $wsdl The URL of the wsdl instance
	 * @return boolean Whether there was an instance to remove
	 * @access public
	 */
	public function remove($wsdl)
	{
	}
}
/**
 * For backward compatibility
 */
class wsdlcache extends \nusoap_wsdlcache
{
}
/**
 * nusoap_client_mime client supporting MIME attachments defined at
 * http://www.w3.org/TR/SOAP-attachments.  It depends on the PEAR Mail_MIME library.
 *
 * @author   Scott Nichol <snichol@users.sourceforge.net>
 * @author	Thanks to Guillaume and Henning Reich for posting great attachment code to the mail list
 * @version  $Id: nusoapmime.php,v 1.13 2010/04/26 20:15:08 snichol Exp $
 * @access   public
 */
class nusoap_client_mime extends \nusoap_client
{
	/**
	 * @var array Each array element in the return is an associative array with keys
	 * data, filename, contenttype, cid
	 * @access private
	 */
	public $requestAttachments = array();
	/**
	 * @var array Each array element in the return is an associative array with keys
	 * data, filename, contenttype, cid
	 * @access private
	 */
	public $responseAttachments;
	/**
	 * @var string
	 * @access private
	 */
	public $mimeContentType;
	/**
	 * adds a MIME attachment to the current request.
	 *
	 * If the $data parameter contains an empty string, this method will read
	 * the contents of the file named by the $filename parameter.
	 *
	 * If the $cid parameter is false, this method will generate the cid.
	 *
	 * @param string $data The data of the attachment
	 * @param string $filename The filename of the attachment (default is empty string)
	 * @param string $contenttype The MIME Content-Type of the attachment (default is application/octet-stream)
	 * @param string $cid The content-id (cid) of the attachment (default is false)
	 * @return string The content-id (cid) of the attachment
	 * @access public
	 */
	public function addAttachment($data, $filename = '', $contenttype = 'application/octet-stream', $cid = \false)
	{
	}
	/**
	 * clears the MIME attachments for the current request.
	 *
	 * @access public
	 */
	public function clearAttachments()
	{
	}
	/**
	 * gets the MIME attachments from the current response.
	 *
	 * Each array element in the return is an associative array with keys
	 * data, filename, contenttype, cid.  These keys correspond to the parameters
	 * for addAttachment.
	 *
	 * @return array The attachments.
	 * @access public
	 */
	public function getAttachments()
	{
	}
	/**
	 * gets the HTTP body for the current request.
	 *
	 * @param string $soapmsg The SOAP payload
	 * @return string The HTTP body, which includes the SOAP payload
	 * @access private
	 */
	public function getHTTPBody($soapmsg)
	{
	}
	/**
	 * gets the HTTP content type for the current request.
	 *
	 * Note: getHTTPBody must be called before this.
	 *
	 * @return string the HTTP content type for the current request.
	 * @access private
	 */
	public function getHTTPContentType()
	{
	}
	/**
	 * gets the HTTP content type charset for the current request.
	 * returns false for non-text content types.
	 *
	 * Note: getHTTPBody must be called before this.
	 *
	 * @return string the HTTP content type charset for the current request.
	 * @access private
	 */
	public function getHTTPContentTypeCharset()
	{
	}
	/**
	 * processes SOAP message returned from server
	 *
	 * @param	array	$headers	The HTTP headers
	 * @param	string	$data		unprocessed response data from server
	 * @return	mixed	value of the message, decoded into a PHP type
	 * @access   private
	 */
	public function parseResponse($headers, $data)
	{
	}
}
class soapclientmime extends \nusoap_client_mime
{
}
/**
 * nusoap_server_mime server supporting MIME attachments defined at
 * http://www.w3.org/TR/SOAP-attachments.  It depends on the PEAR Mail_MIME library.
 *
 * @author   Scott Nichol <snichol@users.sourceforge.net>
 * @author	Thanks to Guillaume and Henning Reich for posting great attachment code to the mail list
 * @version  $Id: nusoapmime.php,v 1.13 2010/04/26 20:15:08 snichol Exp $
 * @access   public
 */
class nusoap_server_mime extends \nusoap_server
{
	/**
	 * @var array Each array element in the return is an associative array with keys
	 * data, filename, contenttype, cid
	 * @access private
	 */
	public $requestAttachments = array();
	/**
	 * @var array Each array element in the return is an associative array with keys
	 * data, filename, contenttype, cid
	 * @access private
	 */
	public $responseAttachments;
	/**
	 * @var string
	 * @access private
	 */
	public $mimeContentType;
	/**
	 * adds a MIME attachment to the current response.
	 *
	 * If the $data parameter contains an empty string, this method will read
	 * the contents of the file named by the $filename parameter.
	 *
	 * If the $cid parameter is false, this method will generate the cid.
	 *
	 * @param string $data The data of the attachment
	 * @param string $filename The filename of the attachment (default is empty string)
	 * @param string $contenttype The MIME Content-Type of the attachment (default is application/octet-stream)
	 * @param string $cid The content-id (cid) of the attachment (default is false)
	 * @return string The content-id (cid) of the attachment
	 * @access public
	 */
	public function addAttachment($data, $filename = '', $contenttype = 'application/octet-stream', $cid = \false)
	{
	}
	/**
	 * clears the MIME attachments for the current response.
	 *
	 * @access public
	 */
	public function clearAttachments()
	{
	}
	/**
	 * gets the MIME attachments from the current request.
	 *
	 * Each array element in the return is an associative array with keys
	 * data, filename, contenttype, cid.  These keys correspond to the parameters
	 * for addAttachment.
	 *
	 * @return array The attachments.
	 * @access public
	 */
	public function getAttachments()
	{
	}
	/**
	 * gets the HTTP body for the current response.
	 *
	 * @param string $soapmsg The SOAP payload
	 * @return string The HTTP body, which includes the SOAP payload
	 * @access private
	 */
	public function getHTTPBody($soapmsg)
	{
	}
	/**
	 * gets the HTTP content type for the current response.
	 *
	 * Note: getHTTPBody must be called before this.
	 *
	 * @return string the HTTP content type for the current response.
	 * @access private
	 */
	public function getHTTPContentType()
	{
	}
	/**
	 * gets the HTTP content type charset for the current response.
	 * returns false for non-text content types.
	 *
	 * Note: getHTTPBody must be called before this.
	 *
	 * @return string the HTTP content type charset for the current response.
	 * @access private
	 */
	public function getHTTPContentTypeCharset()
	{
	}
	/**
	 * processes SOAP message received from client
	 *
	 * @param	array	$headers	The HTTP headers
	 * @param	string	$data		unprocessed request data from client
	 * @return	mixed	value of the message, decoded into a PHP type
	 * @access   private
	 */
	public function parseRequest($headers, $data)
	{
	}
}
/*
 *	For backwards compatiblity
 */
class nusoapservermime extends \nusoap_server_mime
{
}
// XML Schema Datatype Helper Functions
//xsd:dateTime helpers
/**
 * convert unix timestamp to ISO 8601 compliant date string
 *
 * @param    int $timestamp Unix time stamp
 * @param    boolean $utc Whether the time stamp is UTC or local
 * @return   false|string ISO 8601 date string or false
 * @access   public
 */
function timestamp_to_iso8601($timestamp, $utc = \true)
{
}
/**
 * convert ISO 8601 compliant date string to unix timestamp
 *
 * @param    string $datestr ISO 8601 compliant date string
 * @return   false|int Unix timestamp (int) or false
 * @access   public
 */
function iso8601_to_timestamp($datestr)
{
}
/**
 * sleeps some number of microseconds
 *
 * @param    string $usec the number of microseconds to sleep
 * @access   public
 * @deprecated
 */
function usleepWindows($usec)
{
}
