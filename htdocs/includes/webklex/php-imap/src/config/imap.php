<?php
/*
* File:     imap.php
* Category: config
* Author:   M. Goldenbaum
* Created:  24.09.16 22:36
* Updated:  -
*
* Description:
*  -
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Default date format
    |--------------------------------------------------------------------------
    |
    | The default date format is used to convert any given Carbon::class object into a valid date string.
    | These are currently known working formats: "d-M-Y", "d-M-y", "d M y"
    |
    */
    'date_format' => 'd-M-Y',

    /*
    |--------------------------------------------------------------------------
    | Default account
    |--------------------------------------------------------------------------
    |
    | The default account identifier. It will be used as default for any missing account parameters.
    | If however the default account is missing a parameter the package default will be used.
    | Set to 'false' [boolean] to disable this functionality.
    |
    */
    'default' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Available accounts
    |--------------------------------------------------------------------------
    |
    | Please list all IMAP accounts which you are planning to use within the
    | array below.
    |
    */
    'accounts' => [

        'default' => [// account identifier
            'host'  => 'localhost',
            'port'  => 993,
            'protocol'  => 'imap', //might also use imap, [pop3 or nntp (untested)]
            'encryption'    => 'ssl', // Supported: false, 'ssl', 'tls'
            'validate_cert' => true,
            'username' => 'root@example.com',
            'password' => '',
            'authentication' => null,
            'proxy' => [
                'socket' => null,
                'request_fulluri' => false,
                'username' => null,
                'password' => null,
            ],
            "timeout" => 30
        ],

        /*
        'gmail' => [ // account identifier
            'host' => 'imap.gmail.com',
            'port' => 993,
            'encryption' => 'ssl',
            'validate_cert' => true,
            'username' => 'example@gmail.com',
            'password' => 'PASSWORD',
            'authentication' => 'oauth',
        ],

        'another' => [ // account identifier
            'host' => '',
            'port' => 993,
            'encryption' => false,
            'validate_cert' => true,
            'username' => '',
            'password' => '',
            'authentication' => null,
        ]
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Available IMAP options
    |--------------------------------------------------------------------------
    |
    | Available php imap config parameters are listed below
    |   -Delimiter (optional):
    |       This option is only used when calling $oClient->
    |       You can use any supported char such as ".", "/", (...)
    |   -Fetch option:
    |       IMAP::FT_UID  - Message marked as read by fetching the body message
    |       IMAP::FT_PEEK - Fetch the message without setting the "seen" flag
    |   -Fetch sequence id:
    |       IMAP::ST_UID  - Fetch message components using the message uid
    |       IMAP::ST_MSGN - Fetch message components using the message number
    |   -Body download option
    |       Default TRUE
    |   -Flag download option
    |       Default TRUE
    |   -Soft fail
    |       Default FALSE - Set to TRUE if you want to ignore certain exception while fetching bulk messages
    |   -RFC822
    |       Default TRUE - Set to FALSE to prevent the usage of \imap_rfc822_parse_headers().
    |                      See https://github.com/Webklex/php-imap/issues/115 for more information.
    |   -Message key identifier option
    |       You can choose between the following:
    |       'id'     - Use the MessageID as array key (default, might cause hickups with yahoo mail)
    |       'number' - Use the message number as array key (isn't always unique and can cause some interesting behavior)
    |       'list'   - Use the message list number as array key (incrementing integer (does not always start at 0 or 1)
    |       'uid'    - Use the message uid as array key (isn't always unique and can cause some interesting behavior)
    |   -Fetch order
    |       'asc'  - Order all messages ascending (probably results in oldest first)
    |       'desc' - Order all messages descending (probably results in newest first)
    |   -Disposition types potentially considered an attachment
    |       Default ['attachment', 'inline']
    |   -Common folders
    |       Default folder locations and paths assumed if none is provided
    |   -Open IMAP options:
    |       DISABLE_AUTHENTICATOR - Disable authentication properties.
    |                               Use 'GSSAPI' if you encounter the following
    |                               error: "Kerberos error: No credentials cache
    |                               file found (try running kinit) (...)"
    |                               or ['GSSAPI','PLAIN'] if you are using outlook mail
    |   -Decoder options (currently only the message subject and attachment name decoder can be set)
    |       'utf-8' - Uses imap_utf8($string) to decode a string
    |       'mimeheader' - Uses mb_decode_mimeheader($string) to decode a string
    |
    */
    'options' => [
        'delimiter' => '/',
        'fetch' => \Webklex\PHPIMAP\IMAP::FT_PEEK,
        'sequence' => \Webklex\PHPIMAP\IMAP::ST_MSGN,
        'fetch_body' => true,
        'fetch_flags' => true,
        'soft_fail' => false,
        'rfc822' => true,
        'message_key' => 'list',
        'fetch_order' => 'asc',
        'dispositions' => ['attachment', 'inline'],
        'common_folders' => [
            "root" => "INBOX",
            "junk" => "INBOX/Junk",
            "draft" => "INBOX/Drafts",
            "sent" => "INBOX/Sent",
            "trash" => "INBOX/Trash",
        ],
        'decoder' => [
            'message' => 'utf-8', // mimeheader
            'attachment' => 'utf-8' // mimeheader
        ],
        'open' => [
            // 'DISABLE_AUTHENTICATOR' => 'GSSAPI'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Available flags
    |--------------------------------------------------------------------------
    |
    | List all available / supported flags. Set to null to accept all given flags.
     */
    'flags' => ['recent', 'flagged', 'answered', 'deleted', 'seen', 'draft'],

    /*
    |--------------------------------------------------------------------------
    | Available events
    |--------------------------------------------------------------------------
    |
     */
    'events' => [
        "message" => [
            'new' => \Webklex\PHPIMAP\Events\MessageNewEvent::class,
            'moved' => \Webklex\PHPIMAP\Events\MessageMovedEvent::class,
            'copied' => \Webklex\PHPIMAP\Events\MessageCopiedEvent::class,
            'deleted' => \Webklex\PHPIMAP\Events\MessageDeletedEvent::class,
            'restored' => \Webklex\PHPIMAP\Events\MessageRestoredEvent::class,
        ],
        "folder" => [
            'new' => \Webklex\PHPIMAP\Events\FolderNewEvent::class,
            'moved' => \Webklex\PHPIMAP\Events\FolderMovedEvent::class,
            'deleted' => \Webklex\PHPIMAP\Events\FolderDeletedEvent::class,
        ],
        "flag" => [
            'new' => \Webklex\PHPIMAP\Events\FlagNewEvent::class,
            'deleted' => \Webklex\PHPIMAP\Events\FlagDeletedEvent::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available masking options
    |--------------------------------------------------------------------------
    |
    | By using your own custom masks you can implement your own methods for
    | a better and faster access and less code to write.
    |
    | Checkout the two examples custom_attachment_mask and custom_message_mask
    | for a quick start.
    |
    | The provided masks below are used as the default masks.
     */
    'masks' => [
        'message' => \Webklex\PHPIMAP\Support\Masks\MessageMask::class,
        'attachment' => \Webklex\PHPIMAP\Support\Masks\AttachmentMask::class
    ]
];
