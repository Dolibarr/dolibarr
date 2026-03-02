<?php
/*
* File: Structure.php
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
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;

/**
 * Class Structure
 *
 * @package Webklex\PHPIMAP
 */
class Structure {

    /**
     * Raw structure
     *
     * @var string $raw
     */
    public string $raw = "";

    /**
     * @var Header $header
     */
    private Header $header;

    /**
     * Message type (if multipart or not)
     *
     * @var int $type
     */
    public int $type = IMAP::MESSAGE_TYPE_TEXT;

    /**
     * All available parts
     *
     * @var Part[] $parts
     */
    public array $parts = [];

    /**
     * Options holder
     *
     * @var array $options
     */
    protected array $options = [];

    /**
     * Structure constructor.
     * @param $raw_structure
     * @param Header $header
     *
     * @throws MessageContentFetchingException
     * @throws InvalidMessageDateException
     */
    public function __construct($raw_structure, Header $header) {
        $this->raw = $raw_structure;
        $this->header = $header;
        $this->options = $header->getConfig()->get('options');
        $this->parse();
    }

    /**
     * Parse the given raw structure
     *
     * @throws MessageContentFetchingException
     * @throws InvalidMessageDateException
     */
    protected function parse(): void {
        $this->findContentType();
        $this->parts = $this->find_parts();
    }

    /**
     * Determine the message content type
     */
    public function findContentType(): void {
        $content_type = $this->header->get("content_type")->first();
        if($content_type && stripos($content_type, 'multipart') === 0) {
            $this->type = IMAP::MESSAGE_TYPE_MULTIPART;
        }else{
            $this->type = IMAP::MESSAGE_TYPE_TEXT;
        }
    }

    /**
     * Find all available headers and return the leftover body segment
     * @var string $context
     * @var integer $part_number
     *
     * @return Part[]
     * @throws InvalidMessageDateException
     */
    private function parsePart(string $context, int $part_number = 0): array {
        $body = $context;
        while (($pos = strpos($body, "\r\n")) > 0) {
            $body = substr($body, $pos + 2);
        }
        $headers = substr($context, 0, strlen($body) * -1);
        $body = substr($body, 0, -2);

        $config = $this->header->getConfig();
        $headers = new Header($headers, $config);
        if (($boundary = $headers->getBoundary()) !== null) {
            $parts = $this->detectParts($boundary, $body, $part_number);

            if(count($parts) > 1) {
                return $parts;
            }
        }

        return [new Part($body, $this->header->getConfig(), $headers, $part_number)];
    }

    /**
     * @param string $boundary
     * @param string $context
     * @param int $part_number
     *
     * @return array
     * @throws InvalidMessageDateException
     */
    private function detectParts(string $boundary, string $context, int $part_number = 0): array {
        $base_parts = explode( "--".$boundary, $context);
        if(count($base_parts) == 0) {
            $base_parts = explode($boundary, $context);
        }
        $final_parts = [];
        foreach($base_parts as $ctx) {
            $ctx = substr($ctx, 2);
            if ($ctx !== "--" && $ctx != "" && $ctx != "\r\n") {
                $parts = $this->parsePart($ctx, $part_number);
                foreach ($parts as $part) {
                    $final_parts[] = $part;
                    $part_number = $part->part_number;
                }
                $part_number++;
            }
        }
        return $final_parts;
    }

    /**
     * Find all available parts
     *
     * @return array
     * @throws MessageContentFetchingException
     * @throws InvalidMessageDateException
     */
    public function find_parts(): array {
        if($this->type === IMAP::MESSAGE_TYPE_MULTIPART) {
            if (($boundary = $this->header->getBoundary()) === null)  {
                throw new MessageContentFetchingException("no content found", 0);
            }

            return $this->detectParts($boundary, $this->raw);
        }

        return [new Part($this->raw, $this->header->getConfig(), $this->header)];
    }
}
