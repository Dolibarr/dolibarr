#!/usr/bin/env php
<?php

echo "SabreDAV migrate script for version 2.1\n";

if ($argc < 2) {

    echo <<<HELLO

This script help you migrate from a pre-2.1 database to 2.1.

Changes:
  The 'calendarobjects' table will be upgraded.
  'schedulingobjects' will be created.

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

echo "Upgrading 'calendarobjects'\n";
$addUid = false;
try {
    $result = $pdo->query('SELECT * FROM calendarobjects LIMIT 1');
    $row = $result->fetch(\PDO::FETCH_ASSOC);

    if (!$row) {
        echo "No data in table. Going to try to add the uid field anyway.\n";
        $addUid = true;
    } elseif (array_key_exists('uid', $row)) {
        echo "uid field exists. Assuming that this part of the migration has\n";
        echo "Already been completed.\n";
    } else {
        echo "2.0 schema detected.\n";
        $addUid = true;
    }

} catch (Exception $e) {
    echo "Could not find a calendarobjects table. Skipping this part of the\n";
    echo "upgrade.\n";
}

if ($addUid) {

    switch ($driver) {
        case 'mysql' :
            $pdo->exec('ALTER TABLE calendarobjects ADD uid VARCHAR(200)');
            break;
        case 'sqlite' :
            $pdo->exec('ALTER TABLE calendarobjects ADD uid TEXT');
            break;
    }

    $result = $pdo->query('SELECT id, calendardata FROM calendarobjects');
    $stmt = $pdo->prepare('UPDATE calendarobjects SET uid = ? WHERE id = ?');
    $counter = 0;

    while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {

        try {
            $vobj = \Sabre\VObject\Reader::read($row['calendardata']);
        } catch (\Exception $e) {
            echo "Warning! Item with id $row[id] could not be parsed!\n";
            continue;
        }
        $uid = null;
        $item = $vobj->getBaseComponent();
        if (!isset($item->UID)) {
            echo "Warning! Item with id $item[id] does NOT have a UID property and this is required.\n";
            continue;
        }
        $uid = (string)$item->UID;
        $stmt->execute([$uid, $row['id']]);
        $counter++;

    }

}

echo "Creating 'schedulingobjects'\n";

switch ($driver) {

    case 'mysql' :
        $pdo->exec('CREATE TABLE IF NOT EXISTS schedulingobjects
(
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    principaluri VARCHAR(255),
    calendardata MEDIUMBLOB,
    uri VARCHAR(200),
    lastmodified INT(11) UNSIGNED,
    etag VARCHAR(32),
    size INT(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ');
        break;


    case 'sqlite' :
        $pdo->exec('CREATE TABLE IF NOT EXISTS schedulingobjects (
    id integer primary key asc,
    principaluri text,
    calendardata blob,
    uri text,
    lastmodified integer,
    etag text,
    size integer
)
');
        break;
}

echo "Done.\n";

echo "Upgrade to 2.1 schema completed.\n";
