<?php
/*
* File:     Address.php
* Category: -
* Author:   M. Goldenbaum
* Created:  01.01.21 21:17
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;

/**
 * Class Address
 *
 * @package Webklex\PHPIMAP
 */
class Address {

    /**
     * Address attributes
     * @var string $personal
     * @var string $mailbox
     * @var string $host
     * @var string $mail
     * @var string $full
     */
    public string $personal = "";
    public string $mailbox = "";
    public string $host = "";
    public string $mail = "";
    public string $full = "";

    /**
     * Address constructor.
     * @param object $object
     */
    public function __construct(object $object) {
        if (property_exists($object, "personal")){ $this->personal = $object->personal ?? ''; }
        if (property_exists($object, "mailbox")){ $this->mailbox = $object->mailbox ?? ''; }
        if (property_exists($object, "host")){ $this->host = $object->host ?? ''; }
        if (property_exists($object, "mail")){ $this->mail = $object->mail ?? ''; }
        if (property_exists($object, "full")){ $this->full = $object->full ?? ''; }
        $this->boot();
    }

    /**
     * Boot the address
     */
    private function boot(): void {
        if($this->mail === "" && $this->mailbox !== "" && $this->host !== ""){
            $this->mail = $this->mailbox . "@" . $this->host;
        }elseif($this->mail === "" && $this->mailbox !== ""){
            $this->mail = $this->mailbox;
        }

        if($this->full === "" && $this->mail !== "" && $this->personal !== ""){
            $this->full = $this->personal . " <" . $this->mail . ">";
        }elseif($this->full === "" && $this->mail !== ""){
            $this->full = $this->mail;
        }
    }


    /**
     * Return the stringified address
     *
     * @return string
     */
    public function __toString() {
        return $this->full ?: "";
    }

    /**
     * Return the serialized address
     *
     * @return array
     */
    public function __serialize(){
        return [
            "personal" => $this->personal,
            "mailbox" => $this->mailbox,
            "host" => $this->host,
            "mail" => $this->mail,
            "full" => $this->full,
        ];
    }

    /**
     * Convert instance to array
     *
     * @return array
     */
    public function toArray(): array {
        return $this->__serialize();
    }

    /**
     * Return the stringified attribute
     *
     * @return string
     */
    public function toString(): string {
        return $this->__toString();
    }
}