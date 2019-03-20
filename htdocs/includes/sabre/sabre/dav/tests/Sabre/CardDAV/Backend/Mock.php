<?php

namespace Sabre\CardDAV\Backend;

class Mock extends AbstractBackend {

    public $addressBooks;
    public $cards;

    function __construct($addressBooks = null, $cards = null) {

        $this->addressBooks = $addressBooks;
        $this->cards = $cards;

        if (is_null($this->addressBooks)) {
            $this->addressBooks = [
                [
                    'id'                => 'foo',
                    'uri'               => 'book1',
                    'principaluri'      => 'principals/user1',
                    '{DAV:}displayname' => 'd-name',
                ],
                [
                    'id'                => 'bar',
                    'uri'               => 'book3',
                    'principaluri'      => 'principals/user1',
                    '{DAV:}displayname' => 'd-name',
                ],
            ];

            $card2 = fopen('php://memory', 'r+');
            fwrite($card2, "BEGIN:VCARD\nVERSION:3.0\nUID:45678\nEND:VCARD");
            rewind($card2);
            $this->cards = [
                'foo' => [
                    'card1' => "BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD",
                    'card2' => $card2,
                ],
                'bar' => [
                    'card3' => "BEGIN:VCARD\nVERSION:3.0\nUID:12345\nFN:Test-Card\nEMAIL;TYPE=home:bar@example.org\nEND:VCARD",
                ],
            ];
        }

    }


    function getAddressBooksForUser($principalUri) {

        $books = [];
        foreach ($this->addressBooks as $book) {
            if ($book['principaluri'] === $principalUri) {
                $books[] = $book;
            }
        }
        return $books;

    }

    /**
     * Updates properties for an address book.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param string $addressBookId
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void
     */
    function updateAddressBook($addressBookId, \Sabre\DAV\PropPatch $propPatch) {

        foreach ($this->addressBooks as &$book) {
            if ($book['id'] !== $addressBookId)
                continue;

            $propPatch->handleRemaining(function($mutations) use (&$book) {
                foreach ($mutations as $key => $value) {
                    $book[$key] = $value;
                }
                return true;
            });

        }

    }

    function createAddressBook($principalUri, $url, array $properties) {

        $this->addressBooks[] = array_merge($properties, [
            'id'           => $url,
            'uri'          => $url,
            'principaluri' => $principalUri,
        ]);

    }

    function deleteAddressBook($addressBookId) {

        foreach ($this->addressBooks as $key => $value) {
            if ($value['id'] === $addressBookId)
                unset($this->addressBooks[$key]);
        }
        unset($this->cards[$addressBookId]);

    }

    /**
     * Returns all cards for a specific addressbook id.
     *
     * This method should return the following properties for each card:
     *   * carddata - raw vcard data
     *   * uri - Some unique url
     *   * lastmodified - A unix timestamp
     *
     * It's recommended to also return the following properties:
     *   * etag - A unique etag. This must change every time the card changes.
     *   * size - The size of the card in bytes.
     *
     * If these last two properties are provided, less time will be spent
     * calculating them. If they are specified, you can also ommit carddata.
     * This may speed up certain requests, especially with large cards.
     *
     * @param mixed $addressBookId
     * @return array
     */
    function getCards($addressBookId) {

        $cards = [];
        foreach ($this->cards[$addressBookId] as $uri => $data) {
            if (is_resource($data)) {
                $cards[] = [
                    'uri'      => $uri,
                    'carddata' => $data,
                ];
            } else {
                $cards[] = [
                    'uri'      => $uri,
                    'carddata' => $data,
                    'etag'     => '"' . md5($data) . '"',
                    'size'     => strlen($data)
                ];
            }
        }
        return $cards;

    }

    /**
     * Returns a specfic card.
     *
     * The same set of properties must be returned as with getCards. The only
     * exception is that 'carddata' is absolutely required.
     *
     * If the card does not exist, you must return false.
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @return array
     */
    function getCard($addressBookId, $cardUri) {

        if (!isset($this->cards[$addressBookId][$cardUri])) {
            return false;
        }

        $data = $this->cards[$addressBookId][$cardUri];
        return [
            'uri'      => $cardUri,
            'carddata' => $data,
            'etag'     => '"' . md5($data) . '"',
            'size'     => strlen($data)
        ];

    }

    /**
     * Creates a new card.
     *
     * The addressbook id will be passed as the first argument. This is the
     * same id as it is returned from the getAddressBooksForUser method.
     *
     * The cardUri is a base uri, and doesn't include the full path. The
     * cardData argument is the vcard body, and is passed as a string.
     *
     * It is possible to return an ETag from this method. This ETag is for the
     * newly created resource, and must be enclosed with double quotes (that
     * is, the string itself must contain the double quotes).
     *
     * You should only return the ETag if you store the carddata as-is. If a
     * subsequent GET request on the same card does not have the same body,
     * byte-by-byte and you did return an ETag here, clients tend to get
     * confused.
     *
     * If you don't return an ETag, you can just return null.
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @param string $cardData
     * @return string|null
     */
    function createCard($addressBookId, $cardUri, $cardData) {

        if (is_resource($cardData)) {
            $cardData = stream_get_contents($cardData);
        }
        $this->cards[$addressBookId][$cardUri] = $cardData;
        return '"' . md5($cardData) . '"';

    }

    /**
     * Updates a card.
     *
     * The addressbook id will be passed as the first argument. This is the
     * same id as it is returned from the getAddressBooksForUser method.
     *
     * The cardUri is a base uri, and doesn't include the full path. The
     * cardData argument is the vcard body, and is passed as a string.
     *
     * It is possible to return an ETag from this method. This ETag should
     * match that of the updated resource, and must be enclosed with double
     * quotes (that is: the string itself must contain the actual quotes).
     *
     * You should only return the ETag if you store the carddata as-is. If a
     * subsequent GET request on the same card does not have the same body,
     * byte-by-byte and you did return an ETag here, clients tend to get
     * confused.
     *
     * If you don't return an ETag, you can just return null.
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @param string $cardData
     * @return string|null
     */
    function updateCard($addressBookId, $cardUri, $cardData) {

        if (is_resource($cardData)) {
            $cardData = stream_get_contents($cardData);
        }
        $this->cards[$addressBookId][$cardUri] = $cardData;
        return '"' . md5($cardData) . '"';

    }

    function deleteCard($addressBookId, $cardUri) {

        unset($this->cards[$addressBookId][$cardUri]);

    }

}
