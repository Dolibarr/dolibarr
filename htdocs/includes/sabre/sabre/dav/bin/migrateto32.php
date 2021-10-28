#!/usr/bin/env php
<?php

echo "SabreDAV migrate script for version 3.2\n";

if ($argc < 2) {

    echo <<<HELLO

This script help you migrate from a 3.1 database to 3.2 and later

Changes:
* Created a new calendarinstances table to support calendar sharing.
* Remove a lot of columns from calendars.

Keep in mind that ALTER TABLE commands will be executed. If you have a large
dataset this may mean that this process takes a while.

Make a back-up first. This script has been tested, but the amount of
potential variants are extremely high, so it's impossible to deal with every
possible situation.

In the worst case, you will lose all your data. This is not an overstatement.

Lastly, if you are upgrading from an older version than 3.1, make sure you run
the earlier migration script first. Migration scripts must be ran in order.

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

$backupPostfix = time();

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

echo "Creating 'calendarinstances'\n";
$addValueType = false;
try {
    $result = $pdo->query('SELECT * FROM calendarinstances LIMIT 1');
    $result->fetch(\PDO::FETCH_ASSOC);
    echo "calendarinstances exists. Assuming this part of the migration has already been done.\n";
} catch (Exception $e) {
    echo "calendarinstances does not yet exist. Creating table and migrating data.\n";

    switch ($driver) {
        case 'mysql' :
            $pdo->exec(<<<SQL
CREATE TABLE calendarinstances (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    calendarid INTEGER UNSIGNED NOT NULL,
    principaluri VARBINARY(100),
    access TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1 = owner, 2 = read, 3 = readwrite',
    displayname VARCHAR(100),
    uri VARBINARY(200),
    description TEXT,
    calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
    calendarcolor VARBINARY(10),
    timezone TEXT,
    transparent TINYINT(1) NOT NULL DEFAULT '0',
    share_href VARBINARY(100),
    share_displayname VARCHAR(100),
    share_invitestatus TINYINT(1) NOT NULL DEFAULT '2' COMMENT '1 = noresponse, 2 = accepted, 3 = declined, 4 = invalid',
    UNIQUE(principaluri, uri),
    UNIQUE(calendarid, principaluri),
    UNIQUE(calendarid, share_href)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL
        );
            $pdo->exec("
INSERT INTO calendarinstances
    (
        calendarid,
        principaluri,
        access,
        displayname,
        uri,
        description,
        calendarorder,
        calendarcolor,
        transparent
    )
SELECT
    id,
    principaluri,
    1,
    displayname,
    uri,
    description,
    calendarorder,
    calendarcolor,
    transparent
FROM calendars
");
            break;
        case 'sqlite' :
            $pdo->exec(<<<SQL
CREATE TABLE calendarinstances (
    id integer primary key asc NOT NULL,
    calendarid integer,
    principaluri text,
    access integer COMMENT '1 = owner, 2 = read, 3 = readwrite' NOT NULL DEFAULT '1',
    displayname text,
    uri text NOT NULL,
    description text,
    calendarorder integer,
    calendarcolor text,
    timezone text,
    transparent bool,
    share_href text,
    share_displayname text,
    share_invitestatus integer DEFAULT '2',
    UNIQUE (principaluri, uri),
    UNIQUE (calendarid, principaluri),
    UNIQUE (calendarid, share_href)
);
SQL
        );
            $pdo->exec("
INSERT INTO calendarinstances
    (
        calendarid,
        principaluri,
        access,
        displayname,
        uri,
        description,
        calendarorder,
        calendarcolor,
        transparent
    )
SELECT
    id,
    principaluri,
    1,
    displayname,
    uri,
    description,
    calendarorder,
    calendarcolor,
    transparent
FROM calendars
");
            break;
    }

}
try {
    $result = $pdo->query('SELECT * FROM calendars LIMIT 1');
    $row = $result->fetch(\PDO::FETCH_ASSOC);

    if (!$row) {
        echo "Source table is empty.\n";
        $migrateCalendars = true;
    }

    $columnCount = count($row);
    if ($columnCount === 3) {
        echo "The calendars table has 3 columns already. Assuming this part of the migration was already done.\n";
        $migrateCalendars = false;
    } else {
        echo "The calendars table has " . $columnCount . " columns.\n";
        $migrateCalendars = true;
    }

} catch (Exception $e) {
    echo "calendars table does not exist. This is a major problem. Exiting.\n";
    exit(-1);
}

if ($migrateCalendars) {

    $calendarBackup = 'calendars_3_1_' . $backupPostfix;
    echo "Backing up 'calendars' to '", $calendarBackup, "'\n";

    switch ($driver) {
        case 'mysql' :
            $pdo->exec('RENAME TABLE calendars TO ' . $calendarBackup);
            break;
        case 'sqlite' :
            $pdo->exec('ALTER TABLE calendars RENAME TO ' . $calendarBackup);
            break;

    }

    echo "Creating new calendars table.\n";
    switch ($driver) {
        case 'mysql' :
            $pdo->exec(<<<SQL
CREATE TABLE calendars (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    synctoken INTEGER UNSIGNED NOT NULL DEFAULT '1',
    components VARBINARY(21)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL
);
            break;
        case 'sqlite' :
            $pdo->exec(<<<SQL
CREATE TABLE calendars (
    id integer primary key asc NOT NULL,
    synctoken integer DEFAULT 1 NOT NULL,
    components text NOT NULL
);
SQL
        );
            break;

    }

    echo "Migrating data from old to new table\n";

    $pdo->exec(<<<SQL
INSERT INTO calendars (id, synctoken, components) SELECT id, synctoken, COALESCE(components,"VEVENT,VTODO,VJOURNAL") as components FROM $calendarBackup
SQL
    );

}


echo "Upgrade to 3.2 schema completed.\n";
