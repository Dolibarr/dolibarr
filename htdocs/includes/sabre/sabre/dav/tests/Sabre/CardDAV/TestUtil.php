<?php

namespace Sabre\CardDAV;

class TestUtil {

    static function getBackend() {

        $backend = new Backend\PDO(self::getSQLiteDB());
        return $backend;

    }

    static function getSQLiteDB() {

        $pdo = Backend\PDOSqliteTest::getSQLite();

        // Inserting events through a backend class.
        $backend = new Backend\PDO($pdo);
        $addressbookId = $backend->createAddressBook(
            'principals/user1',
            'UUID-123467',
            [
                '{DAV:}displayname'                                       => 'user1 addressbook',
                '{urn:ietf:params:xml:ns:carddav}addressbook-description' => 'AddressBook description',
            ]
        );
        $backend->createAddressBook(
            'principals/user1',
            'UUID-123468',
            [
                '{DAV:}displayname'                                       => 'user1 addressbook2',
                '{urn:ietf:params:xml:ns:carddav}addressbook-description' => 'AddressBook description',
            ]
        );
        $backend->createCard($addressbookId, 'UUID-2345', self::getTestCardData());
        return $pdo;

    }

    static function deleteSQLiteDB() {
        $sqliteTest = new Backend\PDOSqliteTest();
        $pdo = $sqliteTest->tearDown();
    }

    static function getTestCardData() {

        $addressbookData = 'BEGIN:VCARD
VERSION:3.0
PRODID:-//Acme Inc.//RoadRunner 1.0//EN
FN:Wile E. Coyote
N:Coyote;Wile;Erroll;;
ORG:Acme Inc.
UID:39A6B5ED-DD51-4AFE-A683-C35EE3749627
REV:2012-06-20T07:00:39+00:00
END:VCARD';

        return $addressbookData;

    }

}
