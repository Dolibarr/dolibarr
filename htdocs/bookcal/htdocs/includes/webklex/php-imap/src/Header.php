<?php
/*
* File: Header.php
* Category: -
* Author: M.Goldenbaum
* Created: 17.09.20 20:38
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;


use Carbon\Carbon;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MethodNotFoundException;

/**
 * Class Header
 *
 * @package Webklex\PHPIMAP
 */
class Header {

    /**
     * Raw header
     *
     * @var string $raw
     */
    public $raw = "";

    /**
     * Attribute holder
     *
     * @var Attribute[]|array $attributes
     */
    protected $attributes = [];

    /**
     * Config holder
     *
     * @var array $config
     */
    protected $config = [];

    /**
     * Fallback Encoding
     *
     * @var string
     */
    public $fallback_encoding = 'UTF-8';

    /**
     * Convert parsed values to attributes
     *
     * @var bool
     */
    protected $attributize = false;

    /**
     * Header constructor.
     * @param string $raw_header
     * @param boolean $attributize
     *
     * @throws InvalidMessageDateException
     */
    public function __construct($raw_header, $attributize = true) {
        $this->raw = $raw_header;
        $this->config = ClientManager::get('options');
        $this->attributize = $attributize;
        $this->parse();
    }

    /**
     * Call dynamic attribute setter and getter methods
     * @param string $method
     * @param array $arguments
     *
     * @return Attribute|mixed
     * @throws MethodNotFoundException
     */
    public function __call($method, $arguments) {
        if(strtolower(substr($method, 0, 3)) === 'get') {
            $name = preg_replace('/(.)(?=[A-Z])/u', '$1_', substr(strtolower($method), 3));

            if(in_array($name, array_keys($this->attributes))) {
                return $this->attributes[$name];
            }

        }

        throw new MethodNotFoundException("Method ".self::class.'::'.$method.'() is not supported');
    }

    /**
     * Magic getter
     * @param $name
     *
     * @return Attribute|null
     */
    public function __get($name) {
        return $this->get($name);
    }

    /**
     * Get a specific header attribute
     * @param $name
     *
     * @return Attribute|mixed
     */
    public function get($name) {
        if(isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return null;
    }

    /**
     * Set a specific attribute
     * @param string $name
     * @param array|mixed $value
     * @param boolean $strict
     *
     * @return Attribute
     */
    public function set($name, $value, $strict = false) {
        if(isset($this->attributes[$name]) && $strict === false) {
            if ($this->attributize) {
                $this->attributes[$name]->add($value, true);
            }else{
                if(isset($this->attributes[$name])) {
                    if (is_array($this->attributes[$name]) == false) {
                        $this->attributes[$name] = [$this->attributes[$name], $value];
                    }else{
                        $this->attributes[$name][] = $value;
                    }
                }else{
                    $this->attributes[$name] = $value;
                }
            }
        }elseif($this->attributize == false){
            $this->attributes[$name] = $value;
        }else{
            $this->attributes[$name] = new Attribute($name, $value);
        }

        return $this->attributes[$name];
    }

    /**
     * Perform a regex match all on the raw header and return the first result
     * @param $pattern
     *
     * @return mixed|null
     */
    public function find($pattern) {
        if (preg_match_all($pattern, $this->raw, $matches)) {
            if (isset($matches[1])) {
                if(count($matches[1]) > 0) {
                    return $matches[1][0];
                }
            }
        }
        return null;
    }

    /**
     * Try to find a boundary if possible
     *
     * @return string|null
     */
    public function getBoundary(){
        $boundary = $this->find("/boundary\=(.*)/i");

        if ($boundary === null) {
            return null;
        }

        return $this->clearBoundaryString($boundary);
    }

    /**
     * Remove all unwanted chars from a given boundary
     * @param string $str
     *
     * @return string
     */
    private function clearBoundaryString($str) {
        return str_replace(['"', '\r', '\n', "\n", "\r", ";", "\s"], "", $str);
    }

    /**
     * Parse the raw headers
     *
     * @throws InvalidMessageDateException
     */
    protected function parse(){
        $header = $this->rfc822_parse_headers($this->raw);

        $this->extractAddresses($header);

        if (property_exists($header, 'subject')) {
            $this->set("subject", $this->decode($header->subject));
        }
        if (property_exists($header, 'references')) {
            $this->set("references", $this->decode($header->references));
        }
        if (property_exists($header, 'message_id')) {
            $this->set("message_id", str_replace(['<', '>'], '', $header->message_id));
        }

        $this->parseDate($header);
        foreach ($header as $key => $value) {
            $key = trim(rtrim(strtolower($key)));
            if(!isset($this->attributes[$key])){
                $this->set($key, $value);
            }
        }

        $this->extractHeaderExtensions();
        $this->findPriority();
    }

    /**
     * Parse mail headers from a string
     * @link https://php.net/manual/en/function.imap-rfc822-parse-headers.php
     * @param $raw_headers
     *
     * @return object
     */
    public function rfc822_parse_headers($raw_headers){
        $headers = [];
        $imap_headers = [];
        if (extension_loaded('imap') && $this->config["rfc822"]) {
            $raw_imap_headers = (array) \imap_rfc822_parse_headers($this->raw);
            foreach($raw_imap_headers as $key => $values) {
                $key = str_replace("-", "_", $key);
                $imap_headers[$key] = $values;
            }
        }
        $lines = explode("\r\n", str_replace("\r\n\t", ' ', $raw_headers));
        $prev_header = null;
        foreach($lines as $line) {
            if (substr($line, 0, 1) === "\n") {
                $line = substr($line, 1);
            }

            if (substr($line, 0, 1) === "\t") {
                $line = substr($line, 1);
                $line = trim(rtrim($line));
                if ($prev_header !== null) {
                    $headers[$prev_header][] = $line;
                }
            }elseif (substr($line, 0, 1) === " ") {
                $line = substr($line, 1);
                $line = trim(rtrim($line));
                if ($prev_header !== null) {
                    if (!isset($headers[$prev_header])) {
                        $headers[$prev_header] = "";
                    }
                    if (is_array($headers[$prev_header])) {
                        $headers[$prev_header][] = $line;
                    }else{
                        $headers[$prev_header] .= $line;
                    }
                }
            }else{
                if (($pos = strpos($line, ":")) > 0) {
                    $key = trim(rtrim(strtolower(substr($line, 0, $pos))));
                    $key = str_replace("-", "_", $key);

                    $value = trim(rtrim(substr($line, $pos + 1)));
                    if (isset($headers[$key])) {
                        $headers[$key][]  = $value;
                    }else{
                        $headers[$key] = [$value];
                    }
                    $prev_header = $key;
                }
            }
        }

        foreach($headers as $key => $values) {
            if (isset($imap_headers[$key])) continue;
            $value = null;
            switch($key){
                case 'from':
                case 'to':
                case 'cc':
                case 'bcc':
                case 'reply_to':
                case 'sender':
                    $value = $this->decodeAddresses($values);
                    $headers[$key."address"] = implode(", ", $values);
                    break;
                case 'subject':
                    $value = implode(" ", $values);
                    break;
                default:
                    if (is_array($values)) {
                        foreach($values as $k => $v) {
                            if ($v == "") {
                                unset($values[$k]);
                            }
                        }
                        $available_values = count($values);
                        if ($available_values === 1) {
                            $value = array_pop($values);
                        } elseif ($available_values === 2) {
                            $value = implode(" ", $values);
                        } elseif ($available_values > 2) {
                            $value = array_values($values);
                        } else {
                            $value = "";
                        }
                    }
                    break;
            }
            $headers[$key] = $value;
        }

        return (object) array_merge($headers, $imap_headers);
    }

    /**
     * Decode MIME header elements
     * @link https://php.net/manual/en/function.imap-mime-header-decode.php
     * @param string $text The MIME text
     *
     * @return array The decoded elements are returned in an array of objects, where each
     * object has two properties, charset and text.
     */
    public function mime_header_decode($text){
        if (extension_loaded('imap')) {
            return \imap_mime_header_decode($text);
        }
        $charset = $this->getEncoding($text);
        return [(object)[
            "charset" => $charset,
            "text" => $this->convertEncoding($text, $charset)
        ]];
    }

    /**
     * Check if a given pair of strings has ben decoded
     * @param $encoded
     * @param $decoded
     *
     * @return bool
     */
    private function notDecoded($encoded, $decoded) {
        return 0 === strpos($decoded, '=?')
            && strlen($decoded) - 2 === strpos($decoded, '?=')
            && false !== strpos($encoded, $decoded);
    }

    /**
     * Convert the encoding
     * @param $str
     * @param string $from
     * @param string $to
     *
     * @return mixed|string
     */
    public function convertEncoding($str, $from = "ISO-8859-2", $to = "UTF-8") {

        $from = EncodingAliases::get($from, $this->fallback_encoding);
        $to = EncodingAliases::get($to, $this->fallback_encoding);

        if ($from === $to) {
            return $str;
        }

        // We don't need to do convertEncoding() if charset is ASCII (us-ascii):
        //     ASCII is a subset of UTF-8, so all ASCII files are already UTF-8 encoded
        //     https://stackoverflow.com/a/11303410
        //
        // us-ascii is the same as ASCII:
        //     ASCII is the traditional name for the encoding system; the Internet Assigned Numbers Authority (IANA)
        //     prefers the updated name US-ASCII, which clarifies that this system was developed in the US and
        //     based on the typographical symbols predominantly in use there.
        //     https://en.wikipedia.org/wiki/ASCII
        //
        // convertEncoding() function basically means convertToUtf8(), so when we convert ASCII string into UTF-8 it gets broken.
        if (strtolower($from) == 'us-ascii' && $to == 'UTF-8') {
            return $str;
        }

        try {
            if (function_exists('iconv') && $from != 'UTF-7' && $to != 'UTF-7') {
                return iconv($from, $to, $str);
            } else {
                if (!$from) {
                    return mb_convert_encoding($str, $to);
                }
                return mb_convert_encoding($str, $to, $from);
            }
        } catch (\Exception $e) {
            if (strstr($from, '-')) {
                $from = str_replace('-', '', $from);
                return $this->convertEncoding($str, $from, $to);
            } else {
                return $str;
            }
        }
    }

    /**
     * Get the encoding of a given abject
     * @param object|string $structure
     *
     * @return string
     */
    public function getEncoding($structure) {
        if (property_exists($structure, 'parameters')) {
            foreach ($structure->parameters as $parameter) {
                if (strtolower($parameter->attribute) == "charset") {
                    return EncodingAliases::get($parameter->value, $this->fallback_encoding);
                }
            }
        }elseif (property_exists($structure, 'charset')) {
            return EncodingAliases::get($structure->charset, $this->fallback_encoding);
        }elseif (is_string($structure) === true){
            return mb_detect_encoding($structure);
        }

        return $this->fallback_encoding;
    }

    /**
     * Test if a given value is utf-8 encoded
     * @param $value
     *
     * @return bool
     */
    private function is_uft8($value) {
        return strpos(strtolower($value), '=?utf-8?') === 0;
    }

    /**
     * Try to decode a specific header
     * @param mixed $value
     *
     * @return mixed
     */
    private function decode($value) {
        if (is_array($value)) {
            return $this->decodeArray($value);
        }
        $original_value = $value;
        $decoder = $this->config['decoder']['message'];

        if ($value !== null) {
            $is_utf8_base = $this->is_uft8($value);

            if($decoder === 'utf-8' && extension_loaded('imap')) {
                $value = \imap_utf8($value);
                $is_utf8_base = $this->is_uft8($value);
                if ($is_utf8_base) {
                    $value = mb_decode_mimeheader($value);
                }
                if ($this->notDecoded($original_value, $value)) {
                    $decoded_value = $this->mime_header_decode($value);
                    if (count($decoded_value) > 0) {
                        if(property_exists($decoded_value[0], "text")) {
                            $value = $decoded_value[0]->text;
                        }
                    }
                }
            }elseif($decoder === 'iconv' && $is_utf8_base) {
                $value = iconv_mime_decode($value);
            }elseif($is_utf8_base){
                $value = mb_decode_mimeheader($value);
            }

            if ($this->is_uft8($value)) {
                $value = mb_decode_mimeheader($value);
            }

            if ($this->notDecoded($original_value, $value)) {
                $value = $this->convertEncoding($original_value, $this->getEncoding($original_value));
            }
        }

        return $value;
    }

    /**
     * Decode a given array
     * @param array $values
     *
     * @return array
     */
    private function decodeArray($values) {
        foreach($values as $key => $value) {
            $values[$key] = $this->decode($value);
        }
        return $values;
    }

    /**
     * Try to extract the priority from a given raw header string
     */
    private function findPriority() {
        if(($priority = $this->get("x_priority")) === null) return;
        switch((int)"$priority"){
            case IMAP::MESSAGE_PRIORITY_HIGHEST;
                $priority = IMAP::MESSAGE_PRIORITY_HIGHEST;
                break;
            case IMAP::MESSAGE_PRIORITY_HIGH;
                $priority = IMAP::MESSAGE_PRIORITY_HIGH;
                break;
            case IMAP::MESSAGE_PRIORITY_NORMAL;
                $priority = IMAP::MESSAGE_PRIORITY_NORMAL;
                break;
            case IMAP::MESSAGE_PRIORITY_LOW;
                $priority = IMAP::MESSAGE_PRIORITY_LOW;
                break;
            case IMAP::MESSAGE_PRIORITY_LOWEST;
                $priority = IMAP::MESSAGE_PRIORITY_LOWEST;
                break;
            default:
                $priority = IMAP::MESSAGE_PRIORITY_UNKNOWN;
                break;
        }

        $this->set("priority", $priority);
    }

    /**
     * Extract a given part as address array from a given header
     * @param $values
     *
     * @return array
     */
    private function decodeAddresses($values) {
        $addresses = [];

        if (extension_loaded('mailparse') && $this->config["rfc822"]) {
            foreach ($values as $address) {
                foreach (\mailparse_rfc822_parse_addresses($address) as $parsed_address) {
                    if (isset($parsed_address['address'])) {
                        $mail_address = explode('@', $parsed_address['address']);
                        if (count($mail_address) == 2) {
                            $addresses[] = (object)[
                                "personal" => isset($parsed_address['display']) ? $parsed_address['display'] : '',
                                "mailbox" => $mail_address[0],
                                "host" => $mail_address[1],
                            ];
                        }
                    }
                }
            }

            return $addresses;
        }

        foreach($values as $address) {
            foreach (preg_split('/, (?=(?:[^"]*"[^"]*")*[^"]*$)/', $address) as $split_address) {
                $split_address = trim(rtrim($split_address));

                if (strpos($split_address, ",") == strlen($split_address) - 1) {
                    $split_address = substr($split_address, 0, -1);
                }
                if (preg_match(
                    '/^(?:(?P<name>.+)\s)?(?(name)<|<?)(?P<email>[^\s]+?)(?(name)>|>?)$/',
                    $split_address,
                    $matches
                )) {
                    $name = trim(rtrim($matches["name"]));
                    $email = trim(rtrim($matches["email"]));
                    list($mailbox, $host) = array_pad(explode("@", $email), 2, null);
                    $addresses[] = (object)[
                        "personal" => $name,
                        "mailbox" => $mailbox,
                        "host" => $host,
                    ];
                }
            }
        }

        return $addresses;
    }

    /**
     * Extract a given part as address array from a given header
     * @param object $header
     */
    private function extractAddresses($header) {
        foreach(['from', 'to', 'cc', 'bcc', 'reply_to', 'sender'] as $key){
            if (property_exists($header, $key)) {
                $this->set($key, $this->parseAddresses($header->$key));
            }
        }
    }

    /**
     * Parse Addresses
     * @param $list
     *
     * @return array
     */
    private function parseAddresses($list) {
        $addresses = [];

        if (is_array($list) === false) {
            return $addresses;
        }

        foreach ($list as $item) {
            $address = (object) $item;

            if (!property_exists($address, 'mailbox')) {
                $address->mailbox = false;
            }
            if (!property_exists($address, 'host')) {
                $address->host = false;
            }
            if (!property_exists($address, 'personal')) {
                $address->personal = false;
            } else {
                $personalParts = $this->mime_header_decode($address->personal);

                if(is_array($personalParts)) {
                    $address->personal = '';
                    foreach ($personalParts as $p) {
                        $address->personal .= $this->convertEncoding($p->text, $this->getEncoding($p));
                    }
                }

                if (strpos($address->personal, "'") === 0) {
                    $address->personal = str_replace("'", "", $address->personal);
                }
            }

            $address->mail = ($address->mailbox && $address->host) ? $address->mailbox.'@'.$address->host : false;
            $address->full = ($address->personal) ? $address->personal.' <'.$address->mail.'>' : $address->mail;

            $addresses[] = new Address($address);
        }

        return $addresses;
    }

    /**
     * Search and extract potential header extensions
     */
    private function extractHeaderExtensions(){
        foreach ($this->attributes as $key => $value) {
            if (is_array($value)) {
                $value = implode(", ", $value);
            }else{
                $value = (string)$value;
            }
            // Only parse strings and don't parse any attributes like the user-agent
            if (in_array($key, ["user_agent"]) === false) {
                if (($pos = strpos($value, ";")) !== false){
                    $original = substr($value, 0, $pos);
                    $this->set($key, trim(rtrim($original)), true);

                    // Get all potential extensions
                    $extensions = explode(";", substr($value, $pos + 1));
                    foreach($extensions as $extension) {
                        if (($pos = strpos($extension, "=")) !== false){
                            $key = substr($extension, 0, $pos);
                            $key = trim(rtrim(strtolower($key)));

                            if (isset($this->attributes[$key]) === false) {
                                $value = substr($extension, $pos + 1);
                                $value = str_replace('"', "", $value);
                                $value = trim(rtrim($value));

                                $this->set($key, $value);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Exception handling for invalid dates
     *
     * Currently known invalid formats:
     * ^ Datetime                                   ^ Problem                           ^ Cause
     * | Mon, 20 Nov 2017 20:31:31 +0800 (GMT+8:00) | Double timezone specification     | A Windows feature
     * | Thu, 8 Nov 2018 08:54:58 -0200 (-02)       |
     * |                                            | and invalid timezone (max 6 char) |
     * | 04 Jan 2018 10:12:47 UT                    | Missing letter "C"                | Unknown
     * | Thu, 31 May 2018 18:15:00 +0800 (added by) | Non-standard details added by the | Unknown
     * |                                            | mail server                       |
     * | Sat, 31 Aug 2013 20:08:23 +0580            | Invalid timezone                  | PHPMailer bug https://sourceforge.net/p/phpmailer/mailman/message/6132703/
     *
     * Please report any new invalid timestamps to [#45](https://github.com/Webklex/php-imap/issues)
     *
     * @param object $header
     *
     * @throws InvalidMessageDateException
     */
    private function parseDate($header) {

        if (property_exists($header, 'date')) {
            $parsed_date = null;
            $date = $header->date;

            if(preg_match('/\+0580/', $date)) {
                $date = str_replace('+0580', '+0530', $date);
            }

            $date = trim(rtrim($date));
            try {
                $parsed_date = Carbon::parse($date);
            } catch (\Exception $e) {
                switch (true) {
                    case preg_match('/([0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ UT)+$/i', $date) > 0:
                    case preg_match('/([A-Z]{2,3}\,\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ UT)+$/i', $date) > 0:
                        $date .= 'C';
                        break;
                    case preg_match('/([A-Z]{2,3}\,\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ \+[0-9]{2,4}\ \(\+[0-9]{1,2}\))+$/i', $date) > 0:
                    case preg_match('/([A-Z]{2,3}[\,|\ \,]\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}.*)+$/i', $date) > 0:
                    case preg_match('/([A-Z]{2,3}\,\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ [\-|\+][0-9]{4}\ \(.*)\)+$/i', $date) > 0:
                    case preg_match('/([A-Z]{2,3}\, \ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ [\-|\+][0-9]{4}\ \(.*)\)+$/i', $date) > 0:
                    case preg_match('/([0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{2,4}\ [0-9]{2}\:[0-9]{2}\:[0-9]{2}\ [A-Z]{2}\ \-[0-9]{2}\:[0-9]{2}\ \([A-Z]{2,3}\ \-[0-9]{2}:[0-9]{2}\))+$/i', $date) > 0:
                        $array = explode('(', $date);
                        $array = array_reverse($array);
                        $date = trim(array_pop($array));
                        break;
                }
                try{
                    $parsed_date = Carbon::parse($date);
                } catch (\Exception $_e) {
                    throw new InvalidMessageDateException("Invalid message date. ID:".$this->get("message_id"), 1100, $e);
                }
            }

            $this->set("date", $parsed_date);
        }
    }

    /**
     * Get all available attributes
     *
     * @return array
     */
    public function getAttributes() {
        return $this->attributes;
    }

}
