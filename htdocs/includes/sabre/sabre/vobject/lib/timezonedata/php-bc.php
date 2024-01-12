<?php

/**
 * A list of additional PHP timezones that are returned by
 * DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC)
 * valid for new DateTimeZone().
 *
 * This list does not include those timezone identifiers that we have to map to
 * a different identifier for some PHP versions (see php-workaround.php).
 *
 * Instead of using DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC)
 * directly, we use this file because DateTimeZone::ALL_WITH_BC is not properly
 * supported by all PHP version and HHVM.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
return [
    'Africa/Asmara',
    'America/Argentina/Buenos_Aires',
    'America/Argentina/Catamarca',
    'America/Argentina/Cordoba',
    'America/Indiana/Indianapolis',
    'America/Argentina/Jujuy',
    'America/Indiana/Knox',
    'America/Kentucky/Louisville',
    'America/Argentina/Mendoza',
    'Asia/Calcutta',
    'Asia/Katmandu',
    'Australia/Adelaide',
    'Australia/Brisbane',
    'Australia/Broken_Hill',
    'Australia/Darwin',
    'Australia/Eucla',
    'Australia/Hobart',
    'Australia/Lindeman',
    'Australia/Lord_Howe',
    'Australia/Melbourne',
    'Australia/Perth',
    'Australia/Sydney',
    'EET',
    'EST',
    'Etc/GMT',
    'Etc/GMT+0',
    'Etc/GMT+1',
    'Etc/GMT+10',
    'Etc/GMT+11',
    'Etc/GMT+12',
    'Etc/GMT+2',
    'Etc/GMT+3',
    'Etc/GMT+4',
    'Etc/GMT+5',
    'Etc/GMT+6',
    'Etc/GMT+7',
    'Etc/GMT+8',
    'Etc/GMT+9',
    'Etc/GMT-0',
    'Etc/GMT-1',
    'Etc/GMT-10',
    'Etc/GMT-11',
    'Etc/GMT-12',
    'Etc/GMT-13',
    'Etc/GMT-14',
    'Etc/GMT-2',
    'Etc/GMT-3',
    'Etc/GMT-4',
    'Etc/GMT-5',
    'Etc/GMT-6',
    'Etc/GMT-7',
    'Etc/GMT-8',
    'Etc/GMT-9',
    'Etc/GMT0',
    'Etc/Greenwich',
    'Etc/UCT',
    'Etc/Universal',
    'Etc/UTC',
    'Etc/Zulu',
    'GB',
    'GMT',
    'GMT+0',
    'GMT-0',
    'HST',
    'MET',
    'MST',
    'NZ',
    'PRC',
    'ROC',
    'ROK',
    'UCT',
    'WET',
];
