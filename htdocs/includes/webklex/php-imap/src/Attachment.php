<?php
/*
* File:     Attachment.php
* Category: -
* Author:   M. Goldenbaum
* Created:  16.03.18 19:37
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;

use Illuminate\Support\Str;
use Webklex\PHPIMAP\Decoder\DecoderInterface;
use Webklex\PHPIMAP\Exceptions\DecoderNotFoundException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\MethodNotFoundException;
use Webklex\PHPIMAP\Support\Masks\AttachmentMask;

/**
 * Class Attachment
 *
 * @package Webklex\PHPIMAP
 *
 * @property integer $part_number
 * @property integer $size
 * @property string $content
 * @property string $type
 * @property string $content_type
 * @property string $id
 * @property string $hash
 * @property string $name
 * @property string $description
 * @property string $filename
 * @property ?string $disposition
 * @property string $img_src
 *
 * @method integer getPartNumber()
 * @method integer setPartNumber(integer $part_number)
 * @method string  getContent()
 * @method string  setContent(string $content)
 * @method string  getType()
 * @method string  setType(string $type)
 * @method string  getContentType()
 * @method string  setContentType(string $content_type)
 * @method string  getId()
 * @method string  setId(string $id)
 * @method string  getHash()
 * @method string  setHash(string $hash)
 * @method string  getSize()
 * @method string  setSize(integer $size)
 * @method string  getName()
 * @method string  getDisposition()
 * @method string  setDisposition(string $disposition)
 * @method string  setImgSrc(string $img_src)
 */
class Attachment {

    /**
     * @var Message $message
     */
    protected Message $message;

    /**
     * Used config
     *
     * @var Config $config
     */
    protected Config $config;

    /**
     * Attachment options
     *
     * @var array $options
     */
    protected array $options = [];

    /** @var Part $part */
    protected Part $part;

    /**
     * Decoder instance
     *
     * @var DecoderInterface $decoder
     */
    protected DecoderInterface $decoder;

    /**
     * Attribute holder
     *
     * @var array $attributes
     */
    protected array $attributes = [
        'content'      => null,
        'hash'         => null,
        'type'         => null,
        'part_number'  => 0,
        'content_type' => null,
        'id'           => null,
        'name'         => null,
        'filename'     => null,
        'description'  => null,
        'disposition'  => null,
        'img_src'      => null,
        'size'         => null,
    ];

    /**
     * Default mask
     *
     * @var string $mask
     */
    protected string $mask = AttachmentMask::class;

    /**
     * Attachment constructor.
     * @param Message $message
     * @param Part $part
     * @throws DecoderNotFoundException
     */
    public function __construct(Message $message, Part $part) {
        $this->message = $message;
        $this->config = $this->message->getConfig();
        $this->options = $this->config->get('options');
        $this->decoder = $this->config->getDecoder("attachment");

        $this->part = $part;
        $this->part_number = $part->part_number;

        if ($this->message->getClient()) {
            $default_mask = $this->message->getClient()?->getDefaultAttachmentMask();
            if ($default_mask != null) {
                $this->mask = $default_mask;
            }
        } else {
            $default_mask = $this->config->getMask("attachment");
            if ($default_mask != "") {
                $this->mask = $default_mask;
            }
        }

        $this->findType();
        $this->fetch();
    }

    /**
     * Call dynamic attribute setter and getter methods
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     * @throws MethodNotFoundException
     */
    public function __call(string $method, array $arguments) {
        if (strtolower(substr($method, 0, 3)) === 'get') {
            $name = Str::snake(substr($method, 3));

            if (isset($this->attributes[$name])) {
                return $this->attributes[$name];
            }

            return null;
        } elseif (strtolower(substr($method, 0, 3)) === 'set') {
            $name = Str::snake(substr($method, 3));

            $this->attributes[$name] = array_pop($arguments);

            return $this->attributes[$name];
        }

        throw new MethodNotFoundException("Method " . self::class . '::' . $method . '() is not supported');
    }

    /**
     * Magic setter
     * @param $name
     * @param $value
     *
     * @return mixed
     */
    public function __set($name, $value) {
        $this->attributes[$name] = $value;

        return $this->attributes[$name];
    }

    /**
     * magic getter
     * @param $name
     *
     * @return mixed|null
     */
    public function __get($name) {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return null;
    }

    /**
     * Determine the structure type
     */
    protected function findType(): void {
        $this->type = match ($this->part->type) {
            IMAP::ATTACHMENT_TYPE_MESSAGE => 'message',
            IMAP::ATTACHMENT_TYPE_APPLICATION => 'application',
            IMAP::ATTACHMENT_TYPE_AUDIO => 'audio',
            IMAP::ATTACHMENT_TYPE_IMAGE => 'image',
            IMAP::ATTACHMENT_TYPE_VIDEO => 'video',
            IMAP::ATTACHMENT_TYPE_MODEL => 'model',
            IMAP::ATTACHMENT_TYPE_TEXT => 'text',
            IMAP::ATTACHMENT_TYPE_MULTIPART => 'multipart',
            default => 'other',
        };
    }

    /**
     * Fetch the given attachment
     */
    protected function fetch(): void {
        $content = $this->part->content;

        $this->content_type = $this->part->content_type;
        $this->content = $this->decoder->decode($content, $this->part->encoding);

        // Create a hash of the raw part - this can be used to identify the attachment in the message context. However,
        // it is not guaranteed to be unique and collisions are possible.
        // Some additional online resources:
        // - https://en.wikipedia.org/wiki/Hash_collision
        // - https://www.php.net/manual/en/function.hash.php
        // - https://php.watch/articles/php-hash-benchmark
        // Benchmark speeds:
        // -xxh3    ~15.19(GB/s) (requires php-xxhash extension or >= php8.1)
        // -crc32c  ~14.12(GB/s)
        // -sha256  ~0.25(GB/s)
        // xxh3 would be nice to use, because of its extra speed and 32 instead of 8 bytes, but it is not compatible with
        // php < 8.1. crc32c is the next fastest and is compatible with php >= 5.1. sha256 is the slowest, but is compatible
        // with php >= 5.1 and is the most likely to be unique. crc32c is the best compromise between speed and uniqueness.
        // Unique enough for our purposes, but not so slow that it could be a bottleneck.
        $this->hash = hash("crc32c", $this->part->getHeader()->raw."\r\n\r\n".$this->part->content);

        if (($id = $this->part->id) !== null) {
            $this->id = str_replace(['<', '>'], '', $id);
        }else {
            $this->id = $this->hash;
        }

        $this->size = $this->part->bytes;
        $this->disposition = $this->part->disposition;

        if (($filename = $this->part->filename) !== null) {
            $this->filename = $this->decodeName($filename);
        }

        if (($description = $this->part->description) !== null) {
            $this->description = $this->part->getHeader()->getDecoder()->decode($description);
        }

        if (($name = $this->part->name) !== null) {
            $this->name = $this->decodeName($name);
        }

        if (IMAP::ATTACHMENT_TYPE_MESSAGE == $this->part->type) {
            if ($this->part->ifdescription) {
                if (!$this->name) {
                    $this->name = $this->part->description;
                }
            } else if (!$this->name) {
                $this->name = $this->part->subtype;
            }
        }
        $this->attributes = array_merge($this->part->getHeader()->getAttributes(), $this->attributes);

        if (!$this->filename) {
            $this->filename = $this->hash;
        }

        if (!$this->name && $this->filename != "") {
            $this->name = $this->filename;
        }
    }

    /**
     * Save the attachment content to your filesystem
     * @param string $path
     * @param string|null $filename
     *
     * @return boolean
     */
    public function save(string $path, ?string $filename = null): bool {
        $filename = $filename ? $this->decodeName($filename) : $this->filename;

        return file_put_contents($path . DIRECTORY_SEPARATOR . $filename, $this->getContent()) !== false;
    }

    /**
     * Decode a given name
     * @param string|null $name
     *
     * @return string
     */
    public function decodeName(?string $name): string {
        if ($name !== null) {
            if (str_contains($name, "''")) {
                $parts = explode("''", $name);
                if (EncodingAliases::has($parts[0])) {
                    $encoding = $parts[0];
                    $name = implode("''", array_slice($parts, 1));
                }
            }

            $decoder = $this->decoder->getOptions()['message'];
            if (preg_match('/=\?([^?]+)\?(Q|B)\?(.+)\?=/i', $name, $matches)) {
                $name = $this->part->getHeader()->getDecoder()->decode($name);
            } elseif ($decoder === 'utf-8' && extension_loaded('imap')) {
                $name = \imap_utf8($name);
            }

            // check if $name is url encoded
            if (preg_match('/%[0-9A-F]{2}/i', $name)) {
                $name = urldecode($name);
            }

            if (isset($encoding)) {
                $name = EncodingAliases::convert($name, $encoding);
            }

            if($this->config->get('security.sanitize_filenames', true)) {
                $name = $this->sanitizeName($name);
            }

            return $name;
        }
        return "";
    }

    /**
     * Get the attachment mime type
     *
     * @return string|null
     */
    public function getMimeType(): ?string {
        return (new \finfo())->buffer($this->getContent(), FILEINFO_MIME_TYPE);
    }

    /**
     * Try to guess the attachment file extension
     *
     * @return string|null
     */
    public function getExtension(): ?string {
        $extension = null;
        $guesser = "\Symfony\Component\Mime\MimeTypes";
        if (class_exists($guesser) !== false) {
            /** @var Symfony\Component\Mime\MimeTypes $guesser */
            $extensions = $guesser::getDefault()->getExtensions($this->getMimeType());
            $extension = $extensions[0] ?? null;
        }
        if ($extension === null) {
            $deprecated_guesser = "\Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser";
            if (class_exists($deprecated_guesser) !== false) {
                /** @var \Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser $deprecated_guesser */
                $extension = $deprecated_guesser::getInstance()->guess($this->getMimeType());
            }
        }
        if ($extension === null) {
            $parts = explode(".", $this->filename);
            $extension = count($parts) > 1 ? end($parts) : null;
        }
        if ($extension === null) {
            $parts = explode(".", $this->name);
            $extension = count($parts) > 1 ? end($parts) : null;
        }
        return $extension;
    }

    /**
     * Get all attributes
     *
     * @return array
     */
    public function getAttributes(): array {
        return $this->attributes;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message {
        return $this->message;
    }

    /**
     * Set the default mask
     * @param $mask
     *
     * @return $this
     */
    public function setMask($mask): Attachment {
        if (class_exists($mask)) {
            $this->mask = $mask;
        }

        return $this;
    }

    /**
     * Get the used default mask
     *
     * @return string
     */
    public function getMask(): string {
        return $this->mask;
    }

    /**
     * Get the attachment options
     * @return array
     */
    public function getOptions(): array {
        return $this->options;
    }

    /**
     * Set the attachment options
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options): Attachment {
        $this->options = $options;
        return $this;
    }

    /**
     * Get the used config
     *
     * @return Config
     */
    public function getConfig(): Config {
        return $this->config;
    }

    /**
     * Set the used config
     * @param Config $config
     *
     * @return $this
     */
    public function setConfig(Config $config): Attachment {
        $this->config = $config;
        return $this;
    }

    /**
     * Get a masked instance by providing a mask name
     * @param string|null $mask
     *
     * @return mixed
     * @throws MaskNotFoundException
     */
    public function mask(?string $mask = null): mixed {
        $mask = $mask !== null ? $mask : $this->mask;
        if (class_exists($mask)) {
            return new $mask($this);
        }

        throw new MaskNotFoundException("Unknown mask provided: " . $mask);
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
     * Sanitize a given name to prevent common attacks
     * !!IMPORTANT!! Do not rely on this method alone - this is just the bare minimum. Additional measures should be taken
     * to ensure that the file is safe to use.
     * @param string $name
     *
     * @return string
     */
    private function sanitizeName(string $name): string {
        $replaces = [
            '/\\\\/' => '',
            '/[\/\0:]+/' => '',
            '/\.+/' => '.',
        ];
        $name_starts_with_dots = str_starts_with($name, '..');
        $name = preg_replace(array_keys($replaces), array_values($replaces), $name);
        if($name_starts_with_dots) {
            return substr($name, 1);
        }
        return $name;
    }
}
