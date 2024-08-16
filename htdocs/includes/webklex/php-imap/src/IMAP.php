<?php
/*
* File: IMAP.php
* Category: -
* Author: M.Goldenbaum
* Created: 14.03.19 18:22
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;

/**
 * Class IMAP
 *
 * Independent imap const holder
 */
class IMAP {

    /**
     * Message const
     *
     * @const integer   TYPE_TEXT
     * @const integer   TYPE_MULTIPART
     *
     * @const integer   ENC_7BIT
     * @const integer   ENC_8BIT
     * @const integer   ENC_BINARY
     * @const integer   ENC_BASE64
     * @const integer   ENC_QUOTED_PRINTABLE
     * @const integer   ENC_OTHER
     */
    const MESSAGE_TYPE_TEXT = 0;
    const MESSAGE_TYPE_MULTIPART = 1;

    const MESSAGE_ENC_7BIT = 0;
    const MESSAGE_ENC_8BIT = 1;
    const MESSAGE_ENC_BINARY = 2;
    const MESSAGE_ENC_BASE64 = 3;
    const MESSAGE_ENC_QUOTED_PRINTABLE = 4;
    const MESSAGE_ENC_OTHER = 5;

    const MESSAGE_PRIORITY_UNKNOWN = 0;
    const MESSAGE_PRIORITY_HIGHEST = 1;
    const MESSAGE_PRIORITY_HIGH = 2;
    const MESSAGE_PRIORITY_NORMAL = 3;
    const MESSAGE_PRIORITY_LOW = 4;
    const MESSAGE_PRIORITY_LOWEST = 5;

    /**
     * Attachment const
     *
     * @const integer   TYPE_TEXT
     * @const integer   TYPE_MULTIPART
     * @const integer   TYPE_MESSAGE
     * @const integer   TYPE_APPLICATION
     * @const integer   TYPE_AUDIO
     * @const integer   TYPE_IMAGE
     * @const integer   TYPE_VIDEO
     * @const integer   TYPE_MODEL
     * @const integer   TYPE_OTHER
     */
    const ATTACHMENT_TYPE_TEXT = 0;
    const ATTACHMENT_TYPE_MULTIPART = 1;
    const ATTACHMENT_TYPE_MESSAGE = 2;
    const ATTACHMENT_TYPE_APPLICATION = 3;
    const ATTACHMENT_TYPE_AUDIO = 4;
    const ATTACHMENT_TYPE_IMAGE = 5;
    const ATTACHMENT_TYPE_VIDEO = 6;
    const ATTACHMENT_TYPE_MODEL = 7;
    const ATTACHMENT_TYPE_OTHER = 8;

    /**
     * Client const
     *
     * @const integer   CLIENT_OPENTIMEOUT
     * @const integer   CLIENT_READTIMEOUT
     * @const integer   CLIENT_WRITETIMEOUT
     * @const integer   CLIENT_CLOSETIMEOUT
     */
    const CLIENT_OPENTIMEOUT = 1;
    const CLIENT_READTIMEOUT = 2;
    const CLIENT_WRITETIMEOUT = 3;
    const CLIENT_CLOSETIMEOUT = 4;

    /**
     * Generic imap const
     *
     * @const integer NIL
     * @const integer IMAP_OPENTIMEOUT
     * @const integer IMAP_READTIMEOUT
     * @const integer IMAP_WRITETIMEOUT
     * @const integer IMAP_CLOSETIMEOUT
     * @const integer OP_DEBUG
     * @const integer OP_READONLY
     * @const integer OP_ANONYMOUS
     * @const integer OP_SHORTCACHE
     * @const integer OP_SILENT
     * @const integer OP_PROTOTYPE
     * @const integer OP_HALFOPEN
     * @const integer OP_EXPUNGE
     * @const integer OP_SECURE
     * @const integer CL_EXPUNGE
     * @const integer FT_UID
     * @const integer FT_PEEK
     * @const integer FT_NOT
     * @const integer FT_INTERNAL
     * @const integer FT_PREFETCHTEXT
     * @const integer ST_UID
     * @const integer ST_SILENT
     * @const integer ST_SET
     * @const integer CP_UID
     * @const integer CP_MOVE
     * @const integer SE_UID
     * @const integer SE_FREE
     * @const integer SE_NOPREFETCH
     * @const integer SO_FREE
     * @const integer SO_NOSERVER
     * @const integer SA_MESSAGES
     * @const integer SA_RECENT
     * @const integer SA_UNSEEN
     * @const integer SA_UIDNEXT
     * @const integer SA_UIDVALIDITY
     * @const integer SA_ALL
     * @const integer LATT_NOINFERIORS
     * @const integer LATT_NOSELECT
     * @const integer LATT_MARKED
     * @const integer LATT_UNMARKED
     * @const integer LATT_REFERRAL
     * @const integer LATT_HASCHILDREN
     * @const integer LATT_HASNOCHILDREN
     * @const integer SORTDATE
     * @const integer SORTARRIVAL
     * @const integer SORTFROM
     * @const integer SORTSUBJECT
     * @const integer SORTTO
     * @const integer SORTCC
     * @const integer SORTSIZE
     * @const integer TYPETEXT
     * @const integer TYPEMULTIPART
     * @const integer TYPEMESSAGE
     * @const integer TYPEAPPLICATION
     * @const integer TYPEAUDIO
     * @const integer TYPEIMAGE
     * @const integer TYPEVIDEO
     * @const integer TYPEMODEL
     * @const integer TYPEOTHER
     * @const integer ENC7BIT
     * @const integer ENC8BIT
     * @const integer ENCBINARY
     * @const integer ENCBASE64
     * @const integer ENCQUOTEDPRINTABLE
     * @const integer ENCOTHER
     * @const integer IMAP_GC_ELT
     * @const integer IMAP_GC_ENV
     * @const integer IMAP_GC_TEXTS
     */
    
    const NIL = 0;
    const IMAP_OPENTIMEOUT = 1;
    const IMAP_READTIMEOUT = 2;
    const IMAP_WRITETIMEOUT = 3;
    const IMAP_CLOSETIMEOUT = 4;
    const OP_DEBUG = 1;

    /**
     * Open mailbox read-only
     * @link http://php.net/manual/en/imap.constants.php
     */
    const OP_READONLY = 2;

    /**
     * Don't use or update a .newsrc for news
     * (NNTP only)
     * @link http://php.net/manual/en/imap.constants.php
     */
    const OP_ANONYMOUS = 4;
    const OP_SHORTCACHE = 8;
    const OP_SILENT = 16;
    const OP_PROTOTYPE = 32;

    /**
     * For IMAP and NNTP
     * names, open a connection but don't open a mailbox.
     * @link http://php.net/manual/en/imap.constants.php
     */
    const OP_HALFOPEN = 64;
    const OP_EXPUNGE = 128;
    const OP_SECURE = 256;

    /**
     * silently expunge the mailbox before closing when
     * calling <b>imap_close</b>
     * @link http://php.net/manual/en/imap.constants.php
     */
    const CL_EXPUNGE = 32768;

    /**
     * The parameter is a UID
     * @link http://php.net/manual/en/imap.constants.php
     */
    const FT_UID = 1;

    /**
     * Do not set the \Seen flag if not already set
     * @link http://php.net/manual/en/imap.constants.php
     */
    const FT_PEEK = 2;
    const FT_NOT = 4;

    /**
     * The return string is in internal format, will not canonicalize to CRLF.
     * @link http://php.net/manual/en/imap.constants.php
     */
    const FT_INTERNAL = 8;
    const FT_PREFETCHTEXT = 32;

    /**
     * The sequence argument contains UIDs instead of sequence numbers
     * @link http://php.net/manual/en/imap.constants.php
     */
    const ST_UID = 1;
    const ST_SILENT = 2;
    const ST_MSGN = 3;
    const ST_SET = 4;

    /**
     * the sequence numbers contain UIDS
     * @link http://php.net/manual/en/imap.constants.php
     */
    const CP_UID = 1;

    /**
     * Delete the messages from the current mailbox after copying
     * with <b>imap_mail_copy</b>
     * @link http://php.net/manual/en/imap.constants.php
     */
    const CP_MOVE = 2;

    /**
     * Return UIDs instead of sequence numbers
     * @link http://php.net/manual/en/imap.constants.php
     */
    const SE_UID = 1;
    const SE_FREE = 2;

    /**
     * Don't prefetch searched messages
     * @link http://php.net/manual/en/imap.constants.php
     */
    const SE_NOPREFETCH = 4;
    const SO_FREE = 8;
    const SO_NOSERVER = 16;
    const SA_MESSAGES = 1;
    const SA_RECENT = 2;
    const SA_UNSEEN = 4;
    const SA_UIDNEXT = 8;
    const SA_UIDVALIDITY = 16;
    const SA_ALL = 31;

    /**
     * This mailbox has no "children" (there are no
     * mailboxes below this one).
     * @link http://php.net/manual/en/imap.constants.php
     */
    const LATT_NOINFERIORS = 1;

    /**
     * This is only a container, not a mailbox - you
     * cannot open it.
     * @link http://php.net/manual/en/imap.constants.php
     */
    const LATT_NOSELECT = 2;

    /**
     * This mailbox is marked. Only used by UW-IMAPD.
     * @link http://php.net/manual/en/imap.constants.php
     */
    const LATT_MARKED = 4;

    /**
     * This mailbox is not marked. Only used by
     * UW-IMAPD.
     * @link http://php.net/manual/en/imap.constants.php
     */
    const LATT_UNMARKED = 8;
    const LATT_REFERRAL = 16;
    const LATT_HASCHILDREN = 32;
    const LATT_HASNOCHILDREN = 64;

    /**
     * Sort criteria for <b>imap_sort</b>:
     * message Date
     * @link http://php.net/manual/en/imap.constants.php
     */
    const SORTDATE = 0;

    /**
     * Sort criteria for <b>imap_sort</b>:
     * arrival date
     * @link http://php.net/manual/en/imap.constants.php
     */
    const SORTARRIVAL = 1;

    /**
     * Sort criteria for <b>imap_sort</b>:
     * mailbox in first From address
     * @link http://php.net/manual/en/imap.constants.php
     */
    const SORTFROM = 2;

    /**
     * Sort criteria for <b>imap_sort</b>:
     * message subject
     * @link http://php.net/manual/en/imap.constants.php
     */
    const SORTSUBJECT = 3;

    /**
     * Sort criteria for <b>imap_sort</b>:
     * mailbox in first To address
     * @link http://php.net/manual/en/imap.constants.php
     */
    const SORTTO = 4;

    /**
     * Sort criteria for <b>imap_sort</b>:
     * mailbox in first cc address
     * @link http://php.net/manual/en/imap.constants.php
     */
    const SORTCC = 5;

    /**
     * Sort criteria for <b>imap_sort</b>:
     * size of message in octets
     * @link http://php.net/manual/en/imap.constants.php
     */
    const SORTSIZE = 6;
    const TYPETEXT = 0;
    const TYPEMULTIPART = 1;
    const TYPEMESSAGE = 2;
    const TYPEAPPLICATION = 3;
    const TYPEAUDIO = 4;
    const TYPEIMAGE = 5;
    const TYPEVIDEO = 6;
    const TYPEMODEL = 7;
    const TYPEOTHER = 8;
    const ENC7BIT = 0;
    const ENC8BIT = 1;
    const ENCBINARY = 2;
    const ENCBASE64 = 3;
    const ENCQUOTEDPRINTABLE = 4;
    const ENCOTHER = 5;

    /**
     * Garbage collector, clear message cache elements.
     * @link http://php.net/manual/en/imap.constants.php
     */
    const IMAP_GC_ELT = 1;

    /**
     * Garbage collector, clear envelopes and bodies.
     * @link http://php.net/manual/en/imap.constants.php
     */
    const IMAP_GC_ENV = 2;

    /**
     * Garbage collector, clear texts.
     * @link http://php.net/manual/en/imap.constants.php
     */
    const IMAP_GC_TEXTS = 4;
    
}