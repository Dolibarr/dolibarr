<?php

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\DAV\MkCol;
use Sabre\DAVACL;
use Sabre\Uri;

/**
 * AddressBook Home class
 *
 * This collection contains a list of addressbooks associated with one user.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class AddressBookHome extends DAV\Collection implements DAV\IExtendedCollection, DAVACL\IACL {

    use DAVACL\ACLTrait;

    /**
     * Principal uri
     *
     * @var array
     */
    protected $principalUri;

    /**
     * carddavBackend
     *
     * @var Backend\BackendInterface
     */
    protected $carddavBackend;

    /**
     * Constructor
     *
     * @param Backend\BackendInterface $carddavBackend
     * @param string $principalUri
     */
    function __construct(Backend\BackendInterface $carddavBackend, $principalUri) {

        $this->carddavBackend = $carddavBackend;
        $this->principalUri = $principalUri;

    }

    /**
     * Returns the name of this object
     *
     * @return string
     */
    function getName() {

        list(, $name) = Uri\split($this->principalUri);
        return $name;

    }

    /**
     * Updates the name of this object
     *
     * @param string $name
     * @return void
     */
    function setName($name) {

        throw new DAV\Exception\MethodNotAllowed();

    }

    /**
     * Deletes this object
     *
     * @return void
     */
    function delete() {

        throw new DAV\Exception\MethodNotAllowed();

    }

    /**
     * Returns the last modification date
     *
     * @return int
     */
    function getLastModified() {

        return null;

    }

    /**
     * Creates a new file under this object.
     *
     * This is currently not allowed
     *
     * @param string $filename
     * @param resource $data
     * @return void
     */
    function createFile($filename, $data = null) {

        throw new DAV\Exception\MethodNotAllowed('Creating new files in this collection is not supported');

    }

    /**
     * Creates a new directory under this object.
     *
     * This is currently not allowed.
     *
     * @param string $filename
     * @return void
     */
    function createDirectory($filename) {

        throw new DAV\Exception\MethodNotAllowed('Creating new collections in this collection is not supported');

    }

    /**
     * Returns a single addressbook, by name
     *
     * @param string $name
     * @todo needs optimizing
     * @return AddressBook
     */
    function getChild($name) {

        foreach ($this->getChildren() as $child) {
            if ($name == $child->getName())
                return $child;

        }
        throw new DAV\Exception\NotFound('Addressbook with name \'' . $name . '\' could not be found');

    }

    /**
     * Returns a list of addressbooks
     *
     * @return array
     */
    function getChildren() {

        $addressbooks = $this->carddavBackend->getAddressBooksForUser($this->principalUri);
        $objs = [];
        foreach ($addressbooks as $addressbook) {
            $objs[] = new AddressBook($this->carddavBackend, $addressbook);
        }
        return $objs;

    }

    /**
     * Creates a new address book.
     *
     * @param string $name
     * @param MkCol $mkCol
     * @throws DAV\Exception\InvalidResourceType
     * @return void
     */
    function createExtendedCollection($name, MkCol $mkCol) {

        if (!$mkCol->hasResourceType('{' . Plugin::NS_CARDDAV . '}addressbook')) {
            throw new DAV\Exception\InvalidResourceType('Unknown resourceType for this collection');
        }
        $properties = $mkCol->getRemainingValues();
        $mkCol->setRemainingResultCode(201);
        $this->carddavBackend->createAddressBook($this->principalUri, $name, $properties);

    }

    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    function getOwner() {

        return $this->principalUri;

    }

}
