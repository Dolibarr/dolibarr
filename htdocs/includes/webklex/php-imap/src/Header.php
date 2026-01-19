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
use Webklex\PHPIMAP\Decoder\DecoderInterface;
use Webklex\PHPIMAP\Exceptions\DecoderNotFoundException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MethodNotFoundException;
use Webklex\PHPIMAP\Exceptions\SpoofingAttemptDetectedException;

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
    public string $raw = "";

    /**
     * Attribute holder
     *
     * @var Attribute[]|array $attributes
     */
    protected array $attributes = [];

    /**
     * Config holder
     *
     * @var Config $config
     */
    protected Config $config;

    /**
     * Config holder
     *
     * @var array $options
     */
    protected array $options = [];

    /**
     * Decoder instance
     *
     * @var DecoderInterface $decoder
     */
    protected DecoderInterface $decoder;

    /**
     * Header constructor.
     * @param Config $config
     * @param string $raw_header
     *
     * @throws InvalidMessageDateException
     * @throws DecoderNotFoundException
     */
    public function __construct(string $raw_header, Config $config) {
        $this->decoder = $config->getDecoder("header");
        $this->raw = $raw_header;
        $this->config = $config;
        $this->options = $this->config->get('options');
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
    public function __call(string $method, array $arguments) {
        if (strtolower(substr($method, 0, 3)) === 'get') {
            $name = preg_replace('/(.)(?=[A-Z])/u', '$1_', substr(strtolower($method), 3));

            if (in_array($name, array_keys($this->attributes))) {
                return $this->attributes[$name];
            }

        }

        throw new MethodNotFoundException("Method " . self::class . '::' . $method . '() is not supported');
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
     * @return Attribute
     */
    public function get($name): Attribute {
        $name = str_replace(["-", " "], "_", strtolower($name));
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return new Attribute($name);
    }

    /**
     * Check if a specific attribute exists
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool {
        $name = str_replace(["-", " "], "_", strtolower($name));
        return isset($this->attributes[$name]);
    }

    /**
     * Set a specific attribute
     * @param string $name
     * @param array|mixed $value
     * @param boolean $strict
     *
     * @return Attribute|array
     */
    public function set(string $name, mixed $value, bool $strict = false): Attribute|array {
        if (isset($this->attributes[$name]) && $strict === false) {
            $this->attributes[$name]->add($value, true);
        } else {
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
    public function find($pattern): mixed {
        if (preg_match_all($pattern, $this->raw, $matches)) {
            if (isset($matches[1])) {
                if (count($matches[1]) > 0) {
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
    public function getBoundary(): ?string {
        $regex = $this->options["boundary"] ?? "/boundary=(.*?(?=;)|(.*))/i";
        $boundary = $this->find($regex);

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
    private function clearBoundaryString(string $str): string {
        return str_replace(['"', '\r', '\n', "\n", "\r", ";", "\s"], "", $str);
    }

    /**
     * Parse the raw headers
     *
     * @throws InvalidMessageDateException
     * @throws SpoofingAttemptDetectedException
     */
    protected function parse(): void {
        $header = $this->rfc822_parse_headers($this->raw);

        $this->extractAddresses($header);

        if (property_exists($header, 'subject')) {
            $this->set("subject", $this->decoder->decode($header->subject));
        }
        if (property_exists($header, 'references')) {
            $this->set("references", array_map(function($item) {
                return str_replace(['<', '>'], '', $item);
            }, explode(" ", $header->references)));
        }
        if (property_exists($header, 'message_id')) {
            $this->set("message_id", str_replace(['<', '>'], '', $header->message_id));
        }
        if (property_exists($header, 'in_reply_to')) {
            $this->set("in_reply_to", str_replace(['<', '>'], '', $header->in_reply_to));
        }

        $this->parseDate($header);
        foreach ($header as $key => $value) {
            $key = trim(rtrim(strtolower($key)));
            if (!isset($this->attributes[$key])) {
                $this->set($key, $value);
            }
        }

        $this->extractHeaderExtensions();
        $this->findPriority();

        if($this->config->get('security.detect_spoofing', true)) {
            // Detect spoofing
            $this->detectSpoofing();
        }
    }

    /**
     * Parse mail headers from a string
     * @link https://php.net/manual/en/function.imap-rfc822-parse-headers.php
     * @param $raw_headers
     *
     * @return object
     */
    public function rfc822_parse_headers($raw_headers): object {
        $headers = [];
        $imap_headers = [];
        if (extension_loaded('imap') && $this->options["rfc822"]) {
            $raw_imap_headers = (array)\imap_rfc822_parse_headers($raw_headers);
            foreach ($raw_imap_headers as $key => $values) {
                $key = strtolower(str_replace("-", "_", $key));
                $imap_headers[$key] = $values;
            }
        }
        $lines = explode("\r\n", preg_replace("/\r\n\s/", ' ', $raw_headers));
        $prev_header = null;
        foreach ($lines as $line) {
            if (str_starts_with($line, "\n")) {
                $line = substr($line, 1);
            }

            if (str_starts_with($line, "\t")) {
                $line = substr($line, 1);
                $line = trim(rtrim($line));
                if ($prev_header !== null) {
                    $headers[$prev_header][] = $line;
                }
            } elseif (str_starts_with($line, " ")) {
                $line = substr($line, 1);
                $line = trim(rtrim($line));
                if ($prev_header !== null) {
                    if (!isset($headers[$prev_header])) {
                        $headers[$prev_header] = "";
                    }
                    if (is_array($headers[$prev_header])) {
                        $headers[$prev_header][] = $line;
                    } else {
                        $headers[$prev_header] .= $line;
                    }
                }
            } else {
                if (($pos = strpos($line, ":")) > 0) {
                    $key = trim(rtrim(strtolower(substr($line, 0, $pos))));
                    $key = strtolower(str_replace("-", "_", $key));

                    $value = trim(rtrim(substr($line, $pos + 1)));
                    if (isset($headers[$key])) {
                        $headers[$key][] = $value;
                    } else {
                        $headers[$key] = [$value];
                    }
                    $prev_header = $key;
                }
            }
        }

        foreach ($headers as $key => $values) {
            if (isset($imap_headers[$key])) {
                continue;
            }
            $value = null;
            switch ((string)$key) {
                case 'from':
                case 'to':
                case 'cc':
                case 'bcc':
                case 'reply_to':
                case 'sender':
                    $value = $this->decodeAddresses($values);
                    $headers[$key . "address"] = implode(", ", $values);
                    break;
                case 'subject':
                    $value = implode(" ", $values);
                    break;
                default:
                    if (is_array($values)) {
                        foreach ($values as $k => $v) {
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

        return (object)array_merge($headers, $imap_headers);
    }

    /**
     * Try to extract the priority from a given raw header string
     */
    private function findPriority(): void {
        $priority = $this->get("x_priority");

        $priority = match ((int)"$priority") {
            IMAP::MESSAGE_PRIORITY_HIGHEST => IMAP::MESSAGE_PRIORITY_HIGHEST,
            IMAP::MESSAGE_PRIORITY_HIGH => IMAP::MESSAGE_PRIORITY_HIGH,
            IMAP::MESSAGE_PRIORITY_NORMAL => IMAP::MESSAGE_PRIORITY_NORMAL,
            IMAP::MESSAGE_PRIORITY_LOW => IMAP::MESSAGE_PRIORITY_LOW,
            IMAP::MESSAGE_PRIORITY_LOWEST => IMAP::MESSAGE_PRIORITY_LOWEST,
            default => IMAP::MESSAGE_PRIORITY_UNKNOWN,
        };

        $this->set("priority", $priority);
    }

    /**
     * Extract a given part as address array from a given header
     * @param $values
     *
     * @return array
     */
    private function decodeAddresses($values): array {
        $addresses = [];

        if (extension_loaded('mailparse') && $this->options["rfc822"]) {
            foreach ($values as $address) {
                foreach (\mailparse_rfc822_parse_addresses($address) as $parsed_address) {
                    if (isset($parsed_address['address'])) {
                        $mail_address = explode('@', $parsed_address['address']);
                        if (count($mail_address) == 2) {
                            $addresses[] = (object)[
                                "personal" => $parsed_address['display'] ?? '',
                                "mailbox"  => $mail_address[0],
                                "host"     => $mail_address[1],
                            ];
                        }
                    }
                }
            }

            return $addresses;
        }

        foreach ($values as $address) {
            foreach (preg_split('/, ?(?=(?:[^"]*"[^"]*")*[^"]*$)/', $address) as $split_address) {
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
                        "mailbox"  => $mailbox,
                        "host"     => $host,
                    ];
                }elseif (preg_match(
                    '/^((?P<name>.+)<)(?P<email>[^<]+?)>$/',
                    $split_address,
                    $matches
                )) {
                    $name = trim(rtrim($matches["name"]));
                    if(str_starts_with($name, "\"") && str_ends_with($name, "\"")) {
                        $name = substr($name, 1, -1);
                    }elseif(str_starts_with($name, "'") && str_ends_with($name, "'")) {
                        $name = substr($name, 1, -1);
                    }
                    $email = trim(rtrim($matches["email"]));
                    list($mailbox, $host) = array_pad(explode("@", $email), 2, null);
                    $addresses[] = (object)[
                        "personal" => $name,
                        "mailbox"  => $mailbox,
                        "host"     => $host,
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
    private function extractAddresses(object $header): void {
        foreach (['from', 'to', 'cc', 'bcc', 'reply_to', 'sender', 'return_path', 'envelope_from', 'envelope_to', 'delivered_to'] as $key) {
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
    private function parseAddresses($list): array {
        $addresses = [];

        if (is_array($list) === false) {
            if(is_string($list)) {
                if (preg_match(
                    '/^(?:(?P<name>.+)\s)?(?(name)<|<?)(?P<email>[^\s]+?)(?(name)>|>?)$/',
                    $list,
                    $matches
                )) {
                    $name = trim(rtrim($matches["name"]));
                    $email = trim(rtrim($matches["email"]));
                    list($mailbox, $host) = array_pad(explode("@", $email), 2, null);
                    if($mailbox === ">") { // Fix trailing ">" in malformed mailboxes
                        $mailbox = "";
                    }
                    if($name === "" && $mailbox === "" && $host === "") {
                        return $addresses;
                    }
                    $list = [
                        (object)[
                            "personal" => $name,
                            "mailbox"  => $mailbox,
                            "host"     => $host,
                        ]
                    ];
                }elseif (preg_match(
                    '/^((?P<name>.+)<)(?P<email>[^<]+?)>$/',
                    $list,
                    $matches
                )) {
                    $name = trim(rtrim($matches["name"]));
                    $email = trim(rtrim($matches["email"]));
                    if(str_starts_with($name, "\"") && str_ends_with($name, "\"")) {
                        $name = substr($name, 1, -1);
                    }elseif(str_starts_with($name, "'") && str_ends_with($name, "'")) {
                        $name = substr($name, 1, -1);
                    }
                    list($mailbox, $host) = array_pad(explode("@", $email), 2, null);
                    if($mailbox === ">") { // Fix trailing ">" in malformed mailboxes
                        $mailbox = "";
                    }
                    if($name === "" && $mailbox === "" && $host === "") {
                        return $addresses;
                    }
                    $list = [
                        (object)[
                            "personal" => $name,
                            "mailbox"  => $mailbox,
                            "host"     => $host,
                        ]
                    ];
                }else{
                    return $addresses;
                }
            }else{
                return $addresses;
            }
        }

        foreach ($list as $item) {
            $address = (object)$item;

            if (!property_exists($address, 'mailbox')) {
                $address->mailbox = false;
            }
            if (!property_exists($address, 'host')) {
                $address->host = false;
            }
            if (!property_exists($address, 'personal')) {
                $address->personal = false;
            } else {
                $personal_slices = explode(" ", $address->personal);
                $address->personal = "";
                foreach ($personal_slices as $slice) {
                    $personalParts = $this->decoder->mimeHeaderDecode($slice);

                    $personal = '';
                    foreach ($personalParts as $p) {
                        $personal .= $this->decoder->convertEncoding($p->text, $this->decoder->getEncoding($p));
                    }

                    if (str_starts_with($personal, "'")) {
                        $personal = str_replace("'", "", $personal);
                    }
                    $personal = $this->decoder->decode($personal);
                    $address->personal .= $personal . " ";
                }
                $address->personal = trim(rtrim($address->personal));
            }

            if ($address->host == ".SYNTAX-ERROR.") {
                $address->host = "";
            }elseif ($address->host == "UNKNOWN") {
                $address->host = "";
            }
            if ($address->mailbox == "UNEXPECTED_DATA_AFTER_ADDRESS") {
                $address->mailbox = "";
            }elseif ($address->mailbox == "MISSING_MAILBOX_TERMINATOR") {
                $address->mailbox = "";
            }

            $addresses[] = new Address($address);
        }

        return $addresses;
    }

    /**
     * Search and extract potential header extensions
     */
    private function extractHeaderExtensions(): void {
        foreach ($this->attributes as $key => $value) {
            if (is_array($value)) {
                $value = implode(", ", $value);
            } else {
                $value = (string)$value;
            }
            // Only parse strings and don't parse any attributes like the user-agent
            if (!in_array($key, ["user-agent", "subject", "received"])) {
                if (str_contains($value, ";") && str_contains($value, "=")) {
                    $_attributes = $this->read_attribute($value);
                    foreach($_attributes as $_key => $_value) {
                        if($_value === "") {
                            $this->set($key, $_key);
                        }
                        if (!isset($this->attributes[$_key])) {
                            $this->set($_key, $_value);
                        }
                    }
                }
            }
        }
    }

    /**
     * Read a given attribute string
     * - this isn't pretty, but it works - feel free to improve :)
     * @param string $raw_attribute
     * @return array
     */
    private function read_attribute(string $raw_attribute): array {
        $attributes = [];
        $key = '';
        $value = '';
        $inside_word = false;
        $inside_key = true;
        $escaped = false;
        foreach (str_split($raw_attribute) as $char) {
            if($escaped) {
                $escaped = false;
                continue;
            }
            if($inside_word) {
                if($char === '\\') {
                    $escaped = true;
                }elseif($char === "\"" && $value !== "") {
                    $inside_word = false;
                }else{
                    $value .= $char;
                }
            }else{
                if($inside_key) {
                    if($char === '"') {
                        $inside_word = true;
                    }elseif($char === ';'){
                        $attributes[$key] = $value;
                        $key = '';
                        $value = '';
                        $inside_key = true;
                    }elseif($char === '=') {
                        $inside_key = false;
                    }else{
                        $key .= $char;
                    }
                }else{
                    if($char === '"' && $value === "") {
                        $inside_word = true;
                    }elseif($char === ';'){
                        $attributes[$key] = $value;
                        $key = '';
                        $value = '';
                        $inside_key = true;
                    }else{
                        $value .= $char;
                    }
                }
            }
        }
        $attributes[$key] = $value;
        $result = [];

        foreach($attributes as $key => $value) {
            if (($pos = strpos($key, "*")) !== false) {
                $key = substr($key, 0, $pos);
            }
            $key = trim(rtrim(strtolower($key)));

            if(!isset($result[$key])) {
                $result[$key] = "";
            }
            $value = trim(rtrim(str_replace(["\r", "\n"], "", $value)));
            if(str_starts_with($value, "\"") && str_ends_with($value, "\"")) {
                $value = substr($value, 1, -1);
            }
            $result[$key] .= $value;
        }
        return $result;
    }

    /**
     * Exception handling for invalid dates
     *
     * Known bad and "invalid" formats:
     * ^ Datetime                                   ^ Problem                           ^ Cause
     * | Mon, 20 Nov 2017 20:31:31 +0800 (GMT+8:00) | Double timezone specification     | A Windows feature
     * | Thu, 8 Nov 2018 08:54:58 -0200 (-02)       |
     * |                                            | and invalid timezone (max 6 char) |
     * | 04 Jan 2018 10:12:47 UT                    | Missing letter "C"                | Unknown
     * | Thu, 31 May 2018 18:15:00 +0800 (added by) | Non-standard details added by the | Unknown
     * |                                            | mail server                       |
     * | Sat, 31 Aug 2013 20:08:23 +0580            | Invalid timezone                  | PHPMailer bug https://sourceforge.net/p/phpmailer/mailman/message/6132703/
     * | Mi., 23 Apr. 2025 09:48:37 +0200 (MESZ)    | Non-standard localized format     | Aqua Mail S/MIME implementation
     *
     * Please report any new invalid timestamps to [#45](https://github.com/Webklex/php-imap/issues)
     *
     * @param object $header
     *
     * @throws InvalidMessageDateException
     */
    private function parseDate(object $header): void {

        if (property_exists($header, 'date')) {
            $date = $header->date;

            if (preg_match('/\+0580/', $date)) {
                $date = str_replace('+0580', '+0530', $date);
            }

            $date = trim(rtrim($date));
            try {
                if (str_contains($date, '&nbsp;')) {
                    $date = str_replace('&nbsp;', ' ', $date);
                }
                if (str_contains($date, ' UT ')) {
                    $date = str_replace(' UT ', ' UTC ', $date);
                }
                $parsed_date = Carbon::parse($date);
            } catch (\Exception $e) {
                switch (true) {
                    case preg_match('/([0-9]{4}\.[0-9]{1,2}\.[0-9]{1,2}\-[0-9]{1,2}\.[0-9]{1,2}.[0-9]{1,2})+$/i', $date) > 0:
                        $date = Carbon::createFromFormat("Y.m.d-H.i.s", $date);
                        break;
                    case preg_match('/([0-9]{2} [A-Z]{3} [0-9]{4} [0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2} [+-][0-9]{1,4} [0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2} [+-][0-9]{1,4})+$/i', $date) > 0:
                        $parts = explode(' ', $date);
                        array_splice($parts, -2);
                        $date = implode(' ', $parts);
                        break;
                    case preg_match('/([A-Z]{2,4}\,\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ [\-|\+][0-9]{4})+$/i', $date) > 0:
                        $array = explode(',', $date);
                        array_shift($array);
                        $date = Carbon::createFromFormat("d M Y H:i:s O", trim(implode(',', $array)));
                        break;
                    case preg_match('/([0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ UT)+$/i', $date) > 0:
                    case preg_match('/([A-Z]{2,3}\,\ [0-9]{1,2}\ [A-Z]{2,3}\ ([0-9]{2}|[0-9]{4})\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ UT)+$/i', $date) > 0:
                        $date .= 'C';
                        break;
                    case preg_match('/([A-Z]{2,3}\,\ [0-9]{1,2}[\,]\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ [\-|\+][0-9]{4})+$/i', $date) > 0:
                        $date = str_replace(',', '', $date);
                        break;
                    // match case for: Di., 15 Feb. 2022 06:52:44 +0100 (MEZ)/Di., 15 Feb. 2022 06:52:44 +0100 (MEZ) and Mi., 23 Apr. 2025 09:48:37 +0200 (MESZ)
                    case preg_match('/([A-Z]{2,3}\.\,\ [0-9]{1,2}\ [A-Z]{2,3}\.\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ [\-|\+][0-9]{4}\ \([A-Z]{3,4}\))(\/([A-Z]{2,3}\.\,\ [0-9]{1,2}\ [A-Z]{2,3}\.\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ [\-|\+][0-9]{4}\ \([A-Z]{3,4}\))+)?$/i', $date) > 0:
                        $dates = explode('/', $date);
                        $date = array_shift($dates);
                        $array = explode(',', $date);
                        array_shift($array);
                        $date = trim(implode(',', $array));
                        $array = explode(' ', $date);
                        array_pop($array);
                        $date = trim(implode(' ', $array));
                        $date = Carbon::createFromFormat("d M. Y H:i:s O", $date);
                        break;
                    // match case for: fr., 25 nov. 2022 06:27:14 +0100/fr., 25 nov. 2022 06:27:14 +0100
                    case preg_match('/([A-Z]{2,3}\.\,\ [0-9]{1,2}\ [A-Z]{2,3}\.\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ [\-|\+][0-9]{4})\/([A-Z]{2,3}\.\,\ [0-9]{1,2}\ [A-Z]{2,3}\.\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ [\-|\+][0-9]{4})+$/i', $date) > 0:
                        $dates = explode('/', $date);
                        $date = array_shift($dates);
                        $array = explode(',', $date);
                        array_shift($array);
                        $date = trim(implode(',', $array));
                        $date = Carbon::createFromFormat("d M. Y H:i:s O", $date);
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
                try {
                    $parsed_date = Carbon::parse($date);
                } catch (\Exception $_e) {
                    if (!isset($this->options["fallback_date"])) {
                        throw new InvalidMessageDateException("Invalid message date. ID:" . $this->get("message_id") . " Date:" . $header->date . "/" . $date, 1100, $e);
                    } else {
                        $parsed_date = Carbon::parse($this->options["fallback_date"]);
                    }
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
    public function getAttributes(): array {
        return $this->attributes;
    }

    /**
     * Set all header attributes
     * @param array $attributes
     *
     * @return Header
     */
    public function setAttributes(array $attributes): Header {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Set the configuration used for parsing a raw header
     * @param array $config
     *
     * @return Header
     */
    public function setOptions(array $config): Header {
        $this->options = $config;
        return $this;
    }

    /**
     * Get the configuration used for parsing a raw header
     *
     * @return array
     */
    public function getOptions(): array {
        return $this->options;
    }

    /**
     * Set the configuration used for parsing a raw header
     * @param Config $config
     *
     * @return Header
     */
    public function setConfig(Config $config): Header {
        $this->config = $config;
        return $this;
    }

    /**
     * Get the configuration used for parsing a raw header
     *
     * @return Config
     */
    public function getConfig(): Config {
        return $this->config;
    }

    /**
     * Get the decoder instance
     *
     * @return DecoderInterface
     */
    public function getDecoder(): DecoderInterface {
        return $this->decoder;
    }

    /**
     * Set the decoder instance
     * @param DecoderInterface $decoder
     *
     * @return $this
     */
    public function setDecoder(DecoderInterface $decoder): static {
        $this->decoder = $decoder;
        return $this;
    }

    /**
     * Detect spoofing by checking the from, reply_to, return_path, sender and envelope_from headers
     * @throws SpoofingAttemptDetectedException
     */
    private function detectSpoofing(): void {
        $header_keys = ["from", "reply_to", "return_path", "sender", "envelope_from"];
        $potential_senders = [];
        foreach($header_keys as $key) {
            $header = $this->get($key);
            foreach ($header->toArray() as $address) {
                $potential_senders[] = $address->mailbox . "@" . $address->host;
            }
        }
        if(count($potential_senders) > 1) {
            $this->set("spoofed", true);
            if($this->config->get('security.detect_spoofing_exception', false)) {
                throw new SpoofingAttemptDetectedException("Potential spoofing detected. Message ID: " . $this->get("message_id") . " Senders: " . implode(", ", $potential_senders));
            }
        }
    }

}
