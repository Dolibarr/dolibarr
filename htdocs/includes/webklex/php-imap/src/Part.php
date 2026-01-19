<?php
/*
* File: Part.php
* Category: -
* Author: M.Goldenbaum
* Created: 17.09.20 20:38
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;


use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;

/**
 * Class Part
 *
 * @package Webklex\PHPIMAP
 */
class Part {

    /**
     * Raw part
     *
     * @var string $raw
     */
    public string $raw = "";

    /**
     * Part type
     *
     * @var int $type
     */
    public int $type = IMAP::MESSAGE_TYPE_TEXT;

    /**
     * Part content
     *
     * @var string $content
     */
    public string $content = "";

    /**
     * Part subtype
     *
     * @var ?string $subtype
     */
    public ?string $subtype = null;

    /**
     * Part charset - if available
     *
     * @var string $charset
     */
    public string $charset = "utf-8";

    /**
     * Part encoding method
     *
     * @var int $encoding
     */
    public int $encoding = IMAP::MESSAGE_ENC_OTHER;

    /**
     * Alias to check if the part is an attachment
     *
     * @var boolean $ifdisposition
     */
    public bool $ifdisposition = false;

    /**
     * Indicates if the part is an attachment
     *
     * @var ?string $disposition
     */
    public ?string $disposition = null;

    /**
     * Alias to check if the part has a description
     *
     * @var boolean $ifdescription
     */
    public bool $ifdescription = false;

    /**
     * Part description if available
     *
     * @var ?string $description
     */
    public ?string $description = null;

    /**
     * Part filename if available
     *
     * @var ?string $filename
     */
    public ?string $filename = null;

    /**
     * Part name if available
     *
     * @var ?string $name
     */
    public ?string $name = null;

    /**
     * Part id if available
     *
     * @var ?string $id
     */
    public ?string $id = null;

    /**
     * The part number of the current part
     *
     * @var integer $part_number
     */
    public int $part_number = 0;

    /**
     * Part length in bytes
     *
     * @var integer $bytes
     */
    public int $bytes;

    /**
     * Part content type
     *
     * @var string|null $content_type
     */
    public ?string $content_type = null;

    /**
     * @var ?Header $header
     */
    private ?Header $header;

    /**
     * @var Config $config
     */
    protected Config $config;

    /**
     * Part constructor.
     * @param string $raw_part
     * @param Config $config
     * @param Header|null $header
     * @param integer $part_number
     *
     * @throws InvalidMessageDateException
     */
    public function __construct(string $raw_part, Config $config, ?Header $header = null, int $part_number = 0) {
        $this->raw = $raw_part;
        $this->config = $config;
        $this->header = $header;
        $this->part_number = $part_number;
        $this->parse();
    }

    /**
     * Parse the raw parts
     *
     * @throws InvalidMessageDateException
     */
    protected function parse(): void {
        if ($this->header === null) {
            $body = $this->findHeaders();
        }else{
            $body = $this->raw;
        }

        $this->parseDisposition();
        $this->parseDescription();
        $this->parseEncoding();

        $this->charset = $this->header->get("charset")->first();
        $this->name = $this->header->get("name");
        $this->filename = $this->header->get("filename");

        if($this->header->get("id")->exist()) {
            $this->id = $this->header->get("id");
        } else if($this->header->get("x_attachment_id")->exist()){
            $this->id = $this->header->get("x_attachment_id");
        } else if($this->header->get("content_id")->exist()){
            $this->id = strtr($this->header->get("content_id"), [
                '<' => '',
                '>' => ''
            ]);
        }

        $content_types = $this->header->get("content_type")->all();
        if(!empty($content_types)){
            $this->subtype = $this->parseSubtype($content_types);
            $content_type = $content_types[0];
            $parts = explode(';', $content_type);
            $this->content_type = trim($parts[0]);
        }

        $this->content = trim(rtrim($body));
        $this->bytes = strlen($this->content);
    }

    /**
     * Find all available headers and return the leftover body segment
     *
     * @return string
     * @throws InvalidMessageDateException
     */
    private function findHeaders(): string {
        $body = $this->raw;
        while (($pos = strpos($body, "\r\n")) > 0) {
            $body = substr($body, $pos + 2);
        }
        $headers = substr($this->raw, 0, strlen($body) * -1);
        $body = substr($body, 0, -2);

        $this->header = new Header($headers, $this->config);

        return $body;
    }

    /**
     * Try to parse the subtype if any is present
     * @param $content_type
     *
     * @return ?string
     */
    private function parseSubtype($content_type): ?string {
        if (is_array($content_type)) {
            foreach ($content_type as $part){
                if ((strpos($part, "/")) !== false){
                    return $this->parseSubtype($part);
                }
            }
            return null;
        }
        if (($pos = strpos($content_type, "/")) !== false){
            return substr(explode(";", $content_type)[0], $pos + 1);
        }
        return null;
    }

    /**
     * Try to parse the disposition if any is present
     */
    private function parseDisposition(): void {
        $content_disposition = $this->header->get("content_disposition")->first();
        if($content_disposition) {
            $this->ifdisposition = true;
            $this->disposition = (is_array($content_disposition)) ? implode(' ', $content_disposition) : explode(";", $content_disposition)[0];
        }
    }

    /**
     * Try to parse the description if any is present
     */
    private function parseDescription(): void {
        $content_description = $this->header->get("content_description")->first();
        if($content_description) {
            $this->ifdescription = true;
            $this->description = $content_description;
        }
    }

    /**
     * Try to parse the encoding if any is present
     */
    private function parseEncoding(): void {
        $encoding = $this->header->get("content_transfer_encoding")->first();
        if($encoding) {
            $this->encoding = match (strtolower($encoding)) {
                "quoted-printable" => IMAP::MESSAGE_ENC_QUOTED_PRINTABLE,
                "base64" => IMAP::MESSAGE_ENC_BASE64,
                "7bit" => IMAP::MESSAGE_ENC_7BIT,
                "8bit" => IMAP::MESSAGE_ENC_8BIT,
                "binary" => IMAP::MESSAGE_ENC_BINARY,
                default => IMAP::MESSAGE_ENC_OTHER,
            };
        }
    }

    /**
     * Check if the current part represents an attachment
     *
     * @return bool
     */
    public function isAttachment(): bool {
        $valid_disposition = in_array(strtolower($this->disposition ?? ''), $this->config->get('options.dispositions'));

        if ($this->type == IMAP::MESSAGE_TYPE_TEXT && ($this->ifdisposition == 0 || empty($this->disposition) || !$valid_disposition)) {
            if (($this->subtype == null || in_array((strtolower($this->subtype)), ["plain", "html"])) && $this->filename == null && $this->name == null) {
                return false;
            }
        }

        if ($this->disposition === "inline" && $this->filename == null && $this->name == null && !$this->header->has("content_id")) {
            return false;
        }
        return true;
    }

    /**
     * Get the part header
     *
     * @return Header|null
     */
    public function getHeader(): ?Header {
        return $this->header;
    }

    /**
     * Get the Config instance
     *
     * @return Config
     */
    public function getConfig(): Config {
        return $this->config;
    }

}
