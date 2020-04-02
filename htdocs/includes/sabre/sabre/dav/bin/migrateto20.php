#!/usr/bin/env php
<?php

echo "SabreDAV migrate script for version 2.0\n";

if ($argc < 2) {

    echo <<<HELLO

This script help you migrate from a pre-2.0 database to 2.0 and later

The 'calendars', 'addressbooks' and 'cards' tables will be upgraded, and new
tables (calendarchanges, addressbookchanges, propertystorage) will be added.

If you don't use the default PDO CalDAV or CardDAV backend, it's pointless to
run this script.

Keep in mind that ALTER TABLE commands will be executed. If you have a large
dataset this may mean that this process takes a while.

Lastly: Make a back-up first. This script has been tested, but the amount of
potential variants are extremely high, so it's impossible to deal with every
possible situation.

In the worst case, you will lose all your data. This is not an overstatement.

Usage:

php {$argv[0]} [pdo-dsn] [username] [password]

For example:

php {$argv[0]} "mysql:host=localhost;dbname=sabredav" root password
php {$argv[0]} sqlite:data/sabredav.db

HELLO;

    exit();

}

// There's a bunch of places where the autoloader could be, so we'll try all of
// them.
$paths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

foreach ($paths as $path) {
    if (file_exists($path)) {
        include $path;
        break;
    }
}

$dsn = $argv[1];
$user = isset($argv[2]) ? $argv[2] : null;
$pass = isset($argv[3]) ? $argv[3] : null;

echo "Connecting to database: " . $dsn . "\n";

$pdo = new PDO($dsn, $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

switch ($driver) {

    case 'mysql' :
        echo "Detected MySQL.\n";
        break;
    case 'sqlite' :
        echo "Detected SQLite.\n";
        break;
    default :
        echo "Error: unsupported driver: " . $driver . "\n";
        die(-1);
}

foreach (['calendar', 'addressbook'] as $itemType) {

    $tableName = $itemType . 's';
    $tableNameOld = $tableName . '_old';
    $changesTable = $itemType . 'changes';

    echo "Upgrading '$tableName'\n";

    // The only cross-db way to do this, is to just fetch a single record.
    $row = $pdo->query("SELECT * FROM $tableName LIMIT 1")->fetch();

    if (!$row) {

        echo "No records were found in the '$tableName' table.\n";
        echo "\n";
        echo "We're going to rename the old table to $tableNameOld (just in case).\n";
        echo "and re-create the new table.\n";

        switch ($driver) {

            case 'mysql' :
                $pdo->exec("RENAME TABLE $tableName TO $tableNameOld");
                switch ($itemType) {
                    case 'calendar' :
                        $pdo->exec("
            CREATE TABLE calendars (
                id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                principaluri VARCHAR(100),
                displayname VARCHAR(100),
                uri VARCHAR(200),
                synctoken INT(11) UNSIGNED NOT NULL DEFAULT '1',
                description TEXT,
                calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
                calendarcolor VARCHAR(10),
                timezone TEXT,
                components VARCHAR(20),
                transparent TINYINT(1) NOT NULL DEFAULT '0',
                UNIQUE(principaluri, uri)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
                        ");
                        break;
                    case 'addressbook' :
                        $pdo->exec("
            CREATE TABLE addressbooks (
                id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                principaluri VARCHAR(255),
                displayname VARCHAR(255),
                uri VARCHAR(200),
                description TEXT,
                synctoken INT(11) UNSIGNED NOT NULL DEFAULT '1',
                UNIQUE(principaluri, uri)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
                        ");
                        break;
                }
                break;

            case 'sqlite' :

                $pdo->exec("ALTER TABLE $tableName RENAME TO $tableNameOld");

                switch ($itemType) {
                    case 'calendar' :
                        $pdo->exec("
            CREATE TABLE calendars (
                id integer primary key asc,
                principaluri text,
                displayname text,
                uri text,
                synctoken integer,
                description text,
                calendarorder integer,
                calendarcolor text,
                timezone text,
                components text,
                transparent bool
            );
                        ");
                        break;
                    case 'addressbook' :
                        $pdo->exec("
            CREATE TABLE addressbooks (
                id integer primary key asc,
                principaluri text,
                displayname text,
                uri text,
                description text,
                synctoken integer
            );
                        ");

                        break;
                }
                break;

        }
        echo "Creation of 2.0 $tableName table is complete\n";

    } else {

        // Checking if there's a synctoken field already.
        if (array_key_exists('synctoken', $row)) {
            echo "The 'synctoken' field already exists in the $tableName table.\n";
            echo "It's likely you already upgraded, so we're simply leaving\n";
            echo "the $tableName table alone\n";
        } else {

            echo "1.8 table schema detected\n";
            switch ($driver) {

                case 'mysql' :
                    $pdo->exec("ALTER TABLE $tableName ADD synctoken INT(11) UNSIGNED NOT NULL DEFAULT '1'");
                    $pdo->exec("ALTER TABLE $tableName DROP ctag");
                    $pdo->exec("UPDATE $tableName SET synctoken = '1'");
                    break;
                case 'sqlite' :
                    $pdo->exec("ALTER TABLE $tableName ADD synctoken integer");
                    $pdo->exec("UPDATE $tableName SET synctoken = '1'");
                    echo "Note: there's no easy way to remove fields in sqlite.\n";
                    echo "The ctag field is no longer used, but it's kept in place\n";
                    break;

            }

            echo "Upgraded '$tableName' to 2.0 schema.\n";

        }

    }

    try {
        $pdo->query("SELECT * FROM $changesTable LIMIT 1");

        echo "'$changesTable' already exists. Assuming that this part of the\n";
        echo "upgrade was already completed.\n";

    } catch (Exception $e) {
        echo "Creating '$changesTable' table.\n";

        switch ($driver) {

            case 'mysql' :
                $pdo->exec("
    CREATE TABLE $changesTable (
        id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
        uri VARCHAR(200) NOT NULL,
        synctoken INT(11) UNSIGNED NOT NULL,
        {$itemType}id INT(11) UNSIGNED NOT NULL,
        operation TINYINT(1) NOT NULL,
        INDEX {$itemType}id_synctoken ({$itemType}id, synctoken)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

                ");
                break;
            case 'sqlite' :
                $pdo->exec("

    CREATE TABLE $changesTable (
        id integer primary key asc,
        uri text,
        synctoken integer,
        {$itemType}id integer,
        operation bool
    );

                ");
                $pdo->exec("CREATE INDEX {$itemType}id_synctoken ON $changesTable ({$itemType}id, synctoken);");
                break;

        }

    }

}

try {
    $pdo->query("SELECT * FROM calendarsubscriptions LIMIT 1");

    echo "'calendarsubscriptions' already exists. Assuming that this part of the\n";
    echo "upgrade was already completed.\n";

} catch (Exception $e) {
    echo "Creating calendarsubscriptions table.\n";

    switch ($driver) {

        case 'mysql' :
            $pdo->exec("
CREATE TABLE calendarsubscriptions (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    uri VARCHAR(200) NOT NULL,
    principaluri VARCHAR(100) NOT NULL,
    source TEXT,
    displayname VARCHAR(100),
    refreshrate VARCHAR(10),
    calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
    calendarcolor VARCHAR(10),
    striptodos TINYINT(1) NULL,
    stripalarms TINYINT(1) NULL,
    stripattachments TINYINT(1) NULL,
    lastmodified INT(11) UNSIGNED,
    UNIQUE(principaluri, uri)
);
            ");
            break;
        case 'sqlite' :
            $pdo->exec("

CREATE TABLE calendarsubscriptions (
    id integer primary key asc,
    uri text,
    principaluri text,
    source text,
    displayname text,
    refreshrate text,
    calendarorder integer,
    calendarcolor text,
    striptodos bool,
    stripalarms bool,
    stripattachments bool,
    lastmodified int
);
            ");

            $pdo->exec("CREATE INDEX principaluri_uri ON calendarsubscriptions (principaluri, uri);");
            break;

    }

}

try {
    $pdo->query("SELECT * FROM propertystorage LIMIT 1");

    echo "'propertystorage' already exists. Assuming that this part of the\n";
    echo "upgrade was already completed.\n";

} catch (Exception $e) {
    echo "Creating propertystorage table.\n";

    switch ($driver) {

        case 'mysql' :
            $pdo->exec("
CREATE TABLE propertystorage (
    id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    path VARBINARY(1024) NOT NULL,
    name VARBINARY(100) NOT NULL,
    value MEDIUMBLOB
);
            ");
            $pdo->exec("
CREATE UNIQUE INDEX path_property ON propertystorage (path(600), name(100));
            ");
            break;
        case 'sqlite' :
            $pdo->exec("
CREATE TABLE propertystorage (
    id integer primary key asc,
    path TEXT,
    name TEXT,
    value TEXT
);
            ");
            $pdo->exec("
CREATE UNIQUE INDEX path_property ON propertystorage (path, name);
            ");

            break;

    }

}

echo "Upgrading cards table to 2.0 schema\n";

try {

    $create = false;
    $row = $pdo->query("SELECT * FROM cards LIMIT 1")->fetch();
    if (!$row) {
        $random = mt_rand(1000, 9999);
        echo "There was no data in the cards table, so we're re-creating it\n";
        echo "The old table will be renamed to cards_old$random, just in case.\n";

        $create = true;

        switch ($driver) {
            case 'mysql' :
                $pdo->exec("RENAME TABLE cards TO cards_old$random");
                break;
            case 'sqlite' :
                $pdo->exec("ALTER TABLE cards RENAME TO cards_old$random");
                break;

        }
    }

} catch (Exception $e) {

    echo "Exception while checking cards table. Assuming that the table does not yet exist.\n";
    echo "Debug: ", $e->getMessage(), "\n";
    $create = true;

}

if ($create) {
    switch ($driver) {
        case 'mysql' :
            $pdo->exec("
CREATE TABLE cards (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    addressbookid INT(11) UNSIGNED NOT NULL,
    carddata MEDIUMBLOB,
    uri VARCHAR(200),
    lastmodified INT(11) UNSIGNED,
    etag VARBINARY(32),
    size INT(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

            ");
            break;

        case 'sqlite' :

            $pdo->exec("
CREATE TABLE cards (
    id integer primary key asc,
    addressbookid integer,
    carddata blob,
    uri text,
    lastmodified integer,
    etag text,
    size integer
);
            ");
            break;

    }
} else {
    switch ($driver) {
        case 'mysql' :
            $pdo->exec("
                ALTER TABLE cards
                ADD etag VARBINARY(32),
                ADD size INT(11) UNSIGNED NOT NULL;
            ");
            break;

        case 'sqlite' :

            $pdo->exec("
                ALTER TABLE cards ADD etag text;
                ALTER TABLE cards ADD size integer;
            ");
            break;

    }
    echo "Reading all old vcards and populating etag and size fields.\n";
    $result = $pdo->query('SELECT id, carddata FROM cards');
    $stmt = $pdo->prepare('UPDATE cards SET etag = ?, size = ? WHERE id = ?');
    while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
        $stmt->execute([
            md5($row['carddata']),
            strlen($row['carddata']),
            $row['id']
        ]);
    }


}

echo "Upgrade to 2.0 schema completed.\n";
