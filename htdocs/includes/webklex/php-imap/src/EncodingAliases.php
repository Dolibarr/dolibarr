<?php
/*
* File:     EncodingAliases.php
* Category: -
* Author:   S. Todorov (https://github.com/todorowww)
* Created:  23.04.18 14:16
* Updated:  -
*
* Description:
*  Contains email encoding aliases, thta can occur when fetching emails. These sometimes can break icvon()
*  This file attempts to correct this by using a list of aliases and their mappings to supported iconv() encodings
*/

namespace Webklex\PHPIMAP;

/**
 * Class EncodingAliases
 *
 * @package Webklex\PHPIMAP
 */
class EncodingAliases {
   
    /**
     * Contains email encoding mappings
     *
     * @var array
     */
    private static $aliases = [
        /*
        |--------------------------------------------------------------------------
        | Email encoding aliases
        |--------------------------------------------------------------------------
        |
        | Email encoding aliases used to convert to iconv supported charsets
        |
        |
        | This Source Code Form is subject to the terms of the Mozilla Public
        | License, v. 2.0. If a copy of the MPL was not distributed with this
        | file, You can obtain one at http://mozilla.org/MPL/2.0/.
        |
        | This Original Code has been modified by IBM Corporation.
        | Modifications made by IBM described herein are
        | Copyright (c) International Business Machines
        | Corporation, 1999
        |
        | Modifications to Mozilla code or documentation
        | identified per MPL Section 3.3
        |
        | Date         Modified by     Description of modification
        | 12/09/1999   IBM Corp.       Support for IBM codepages - 850,852,855,857,862,864
        |
        | Rule of this file:
        | 1. key should always be in lower case ascii so we can do case insensitive
        |    comparison in the code faster.
        | 2. value should be the one used in unicode converter
        |
        | 3. If the charset is not used for document charset, but font charset
        |    (e.g. XLFD charset- such as JIS x0201, JIS x0208), don't put here
        |
        */
        "ascii"                    => "us-ascii",
        "us-ascii"                 => "us-ascii",
        "ansi_x3.4-1968"           => "us-ascii",
        "646"                      => "us-ascii",
        "iso-8859-1"               => "ISO-8859-1",
        "iso-8859-2"               => "ISO-8859-2",
        "iso-8859-3"               => "ISO-8859-3",
        "iso-8859-4"               => "ISO-8859-4",
        "iso-8859-5"               => "ISO-8859-5",
        "iso-8859-6"               => "ISO-8859-6",
        "iso-8859-6-i"             => "ISO-8859-6-I",
        "iso-8859-6-e"             => "ISO-8859-6-E",
        "iso-8859-7"               => "ISO-8859-7",
        "iso-8859-8"               => "ISO-8859-8",
        "iso-8859-8-i"             => "ISO-8859-8-I",
        "iso-8859-8-e"             => "ISO-8859-8-E",
        "iso-8859-9"               => "ISO-8859-9",
        "iso-8859-10"              => "ISO-8859-10",
        "iso-8859-11"              => "ISO-8859-11",
        "iso-8859-13"              => "ISO-8859-13",
        "iso-8859-14"              => "ISO-8859-14",
        "iso-8859-15"              => "ISO-8859-15",
        "iso-8859-16"              => "ISO-8859-16",
        "iso-ir-111"               => "ISO-IR-111",
        "iso-2022-cn"              => "ISO-2022-CN",
        "iso-2022-cn-ext"          => "ISO-2022-CN",
        "iso-2022-kr"              => "ISO-2022-KR",
        "iso-2022-jp"              => "ISO-2022-JP",
        "utf-16be"                 => "UTF-16BE",
        "utf-16le"                 => "UTF-16LE",
        "utf-16"                   => "UTF-16",
        "windows-1250"             => "windows-1250",
        "windows-1251"             => "windows-1251",
        "windows-1252"             => "windows-1252",
        "windows-1253"             => "windows-1253",
        "windows-1254"             => "windows-1254",
        "windows-1255"             => "windows-1255",
        "windows-1256"             => "windows-1256",
        "windows-1257"             => "windows-1257",
        "windows-1258"             => "windows-1258",
        "ibm866"                   => "IBM866",
        "ibm850"                   => "IBM850",
        "ibm852"                   => "IBM852",
        "ibm855"                   => "IBM855",
        "ibm857"                   => "IBM857",
        "ibm862"                   => "IBM862",
        "ibm864"                   => "IBM864",
        "utf-8"                    => "UTF-8",
        "utf-7"                    => "UTF-7",
        "shift_jis"                => "Shift_JIS",
        "big5"                     => "Big5",
        "euc-jp"                   => "EUC-JP",
        "euc-kr"                   => "EUC-KR",
        "gb2312"                   => "GB2312",
        "gb18030"                  => "gb18030",
        "viscii"                   => "VISCII",
        "koi8-r"                   => "KOI8-R",
        "koi8_r"                   => "KOI8-R",
        "cskoi8r"                  => "KOI8-R",
        "koi"                      => "KOI8-R",
        "koi8"                     => "KOI8-R",
        "koi8-u"                   => "KOI8-U",
        "tis-620"                  => "TIS-620",
        "t.61-8bit"                => "T.61-8bit",
        "hz-gb-2312"               => "HZ-GB-2312",
        "big5-hkscs"               => "Big5-HKSCS",
        "gbk"                      => "gbk",
        "cns11643"                 => "x-euc-tw",
        //
        // Aliases for ISO-8859-1
        //
        "latin1"                   => "ISO-8859-1",
        "iso_8859-1"               => "ISO-8859-1",
        "iso8859-1"                => "ISO-8859-1",
        "iso8859-2"                => "ISO-8859-2",
        "iso8859-3"                => "ISO-8859-3",
        "iso8859-4"                => "ISO-8859-4",
        "iso8859-5"                => "ISO-8859-5",
        "iso8859-6"                => "ISO-8859-6",
        "iso8859-7"                => "ISO-8859-7",
        "iso8859-8"                => "ISO-8859-8",
        "iso8859-9"                => "ISO-8859-9",
        "iso8859-10"               => "ISO-8859-10",
        "iso8859-11"               => "ISO-8859-11",
        "iso8859-13"               => "ISO-8859-13",
        "iso8859-14"               => "ISO-8859-14",
        "iso8859-15"               => "ISO-8859-15",
        "iso_8859-1:1987"          => "ISO-8859-1",
        "iso-ir-100"               => "ISO-8859-1",
        "l1"                       => "ISO-8859-1",
        "ibm819"                   => "ISO-8859-1",
        "cp819"                    => "ISO-8859-1",
        "csisolatin1"              => "ISO-8859-1",
        //
        // Aliases for ISO-8859-2
        //
        "latin2"                   => "ISO-8859-2",
        "iso_8859-2"               => "ISO-8859-2",
        "iso_8859-2:1987"          => "ISO-8859-2",
        "iso-ir-101"               => "ISO-8859-2",
        "l2"                       => "ISO-8859-2",
        "csisolatin2"              => "ISO-8859-2",
        //
        // Aliases for ISO-8859-3
        //
        "latin3"                   => "ISO-8859-3",
        "iso_8859-3"               => "ISO-8859-3",
        "iso_8859-3:1988"          => "ISO-8859-3",
        "iso-ir-109"               => "ISO-8859-3",
        "l3"                       => "ISO-8859-3",
        "csisolatin3"              => "ISO-8859-3",
        //
        // Aliases for ISO-8859-4
        //
        "latin4"                   => "ISO-8859-4",
        "iso_8859-4"               => "ISO-8859-4",
        "iso_8859-4:1988"          => "ISO-8859-4",
        "iso-ir-110"               => "ISO-8859-4",
        "l4"                       => "ISO-8859-4",
        "csisolatin4"              => "ISO-8859-4",
        //
        // Aliases for ISO-8859-5
        //
        "cyrillic"                 => "ISO-8859-5",
        "iso_8859-5"               => "ISO-8859-5",
        "iso_8859-5:1988"          => "ISO-8859-5",
        "iso-ir-144"               => "ISO-8859-5",
        "csisolatincyrillic"       => "ISO-8859-5",
        //
        // Aliases for ISO-8859-6
        //
        "arabic"                   => "ISO-8859-6",
        "iso_8859-6"               => "ISO-8859-6",
        "iso_8859-6:1987"          => "ISO-8859-6",
        "iso-ir-127"               => "ISO-8859-6",
        "ecma-114"                 => "ISO-8859-6",
        "asmo-708"                 => "ISO-8859-6",
        "csisolatinarabic"         => "ISO-8859-6",
        //
        // Aliases for ISO-8859-6-I
        //
        "csiso88596i"              => "ISO-8859-6-I",
        //
        // Aliases for ISO-8859-6-E",
        //
        "csiso88596e"              => "ISO-8859-6-E",
        //
        // Aliases for ISO-8859-7",
        //
        "greek"                    => "ISO-8859-7",
        "greek8"                   => "ISO-8859-7",
        "sun_eu_greek"             => "ISO-8859-7",
        "iso_8859-7"               => "ISO-8859-7",
        "iso_8859-7:1987"          => "ISO-8859-7",
        "iso-ir-126"               => "ISO-8859-7",
        "elot_928"                 => "ISO-8859-7",
        "ecma-118"                 => "ISO-8859-7",
        "csisolatingreek"          => "ISO-8859-7",
        //
        // Aliases for ISO-8859-8",
        //
        "hebrew"                   => "ISO-8859-8",
        "iso_8859-8"               => "ISO-8859-8",
        "visual"                   => "ISO-8859-8",
        "iso_8859-8:1988"          => "ISO-8859-8",
        "iso-ir-138"               => "ISO-8859-8",
        "csisolatinhebrew"         => "ISO-8859-8",
        //
        // Aliases for ISO-8859-8-I",
        //
        "csiso88598i"              => "ISO-8859-8-I",
        "iso-8859-8i"              => "ISO-8859-8-I",
        "logical"                  => "ISO-8859-8-I",
        //
        // Aliases for ISO-8859-8-E",
        //
        "csiso88598e"              => "ISO-8859-8-E",
        //
        // Aliases for ISO-8859-9",
        //
        "latin5"                   => "ISO-8859-9",
        "iso_8859-9"               => "ISO-8859-9",
        "iso_8859-9:1989"          => "ISO-8859-9",
        "iso-ir-148"               => "ISO-8859-9",
        "l5"                       => "ISO-8859-9",
        "csisolatin5"              => "ISO-8859-9",
        //
        // Aliases for UTF-8",
        //
        "unicode-1-1-utf-8"        => "UTF-8",
        // nl_langinfo(CODESET) in HP/UX returns 'utf8' under UTF-8 locales",
        "utf8"                     => "UTF-8",
        //
        // Aliases for Shift_JIS",
        //
        "x-sjis"                   => "Shift_JIS",
        "shift-jis"                => "Shift_JIS",
        "ms_kanji"                 => "Shift_JIS",
        "csshiftjis"               => "Shift_JIS",
        "windows-31j"              => "Shift_JIS",
        "cp932"                    => "Shift_JIS",
        "sjis"                     => "Shift_JIS",
        //
        // Aliases for EUC_JP",
        //
        "cseucpkdfmtjapanese"      => "EUC-JP",
        "x-euc-jp"                 => "EUC-JP",
        //
        // Aliases for ISO-2022-JP",
        //
        "csiso2022jp"              => "ISO-2022-JP",
        // The following are really not aliases ISO-2022-JP, but sharing the same decoder",
        "iso-2022-jp-2"            => "ISO-2022-JP",
        "csiso2022jp2"             => "ISO-2022-JP",
        //
        // Aliases for Big5",
        //
        "csbig5"                   => "Big5",
        "cn-big5"                  => "Big5",
        // x-x-big5 is not really a alias for Big5, add it only for MS FrontPage",
        "x-x-big5"                 => "Big5",
        // Sun Solaris",
        "zh_tw-big5"               => "Big5",
        //
        // Aliases for EUC-KR",
        //
        "cseuckr"                  => "EUC-KR",
        "ks_c_5601-1987"           => "EUC-KR",
        "iso-ir-149"               => "EUC-KR",
        "ks_c_5601-1989"           => "EUC-KR",
        "ksc_5601"                 => "EUC-KR",
        "ksc5601"                  => "EUC-KR",
        "korean"                   => "EUC-KR",
        "csksc56011987"            => "EUC-KR",
        "5601"                     => "EUC-KR",
        "windows-949"              => "EUC-KR",
        //
        // Aliases for GB2312",
        //
        // The following are really not aliases GB2312, add them only for MS FrontPage",
        "gb_2312-80"               => "GB2312",
        "iso-ir-58"                => "GB2312",
        "chinese"                  => "GB2312",
        "csiso58gb231280"          => "GB2312",
        "csgb2312"                 => "GB2312",
        "zh_cn.euc"                => "GB2312",
        // Sun Solaris",
        "gb_2312"                  => "GB2312",
        //
        // Aliases for windows-125x ",
        //
        "x-cp1250"                 => "windows-1250",
        "x-cp1251"                 => "windows-1251",
        "x-cp1252"                 => "windows-1252",
        "x-cp1253"                 => "windows-1253",
        "x-cp1254"                 => "windows-1254",
        "x-cp1255"                 => "windows-1255",
        "x-cp1256"                 => "windows-1256",
        "x-cp1257"                 => "windows-1257",
        "x-cp1258"                 => "windows-1258",
        //
        // Aliases for windows-874 ",
        //
        "windows-874"              => "windows-874",
        "ibm874"                   => "windows-874",
        "dos-874"                  => "windows-874",
        //
        // Aliases for macintosh",
        //
        "macintosh"                => "macintosh",
        "x-mac-roman"              => "macintosh",
        "mac"                      => "macintosh",
        "csmacintosh"              => "macintosh",
        //
        // Aliases for IBM866",
        //
        "cp866"                    => "IBM866",
        "cp-866"                   => "IBM866",
        "866"                      => "IBM866",
        "csibm866"                 => "IBM866",
        //
        // Aliases for IBM850",
        //
        "cp850"                    => "IBM850",
        "850"                      => "IBM850",
        "csibm850"                 => "IBM850",
        //
        // Aliases for IBM852",
        //
        "cp852"                    => "IBM852",
        "852"                      => "IBM852",
        "csibm852"                 => "IBM852",
        //
        // Aliases for IBM855",
        //
        "cp855"                    => "IBM855",
        "855"                      => "IBM855",
        "csibm855"                 => "IBM855",
        //
        // Aliases for IBM857",
        //
        "cp857"                    => "IBM857",
        "857"                      => "IBM857",
        "csibm857"                 => "IBM857",
        //
        // Aliases for IBM862",
        //
        "cp862"                    => "IBM862",
        "862"                      => "IBM862",
        "csibm862"                 => "IBM862",
        //
        // Aliases for IBM864",
        //
        "cp864"                    => "IBM864",
        "864"                      => "IBM864",
        "csibm864"                 => "IBM864",
        "ibm-864"                  => "IBM864",
        //
        // Aliases for T.61-8bit",
        //
        "t.61"                     => "T.61-8bit",
        "iso-ir-103"               => "T.61-8bit",
        "csiso103t618bit"          => "T.61-8bit",
        //
        // Aliases for UTF-7",
        //
        "x-unicode-2-0-utf-7"      => "UTF-7",
        "unicode-2-0-utf-7"        => "UTF-7",
        "unicode-1-1-utf-7"        => "UTF-7",
        "csunicode11utf7"          => "UTF-7",
        //
        // Aliases for ISO-10646-UCS-2",
        //
        "csunicode"                => "UTF-16BE",
        "csunicode11"              => "UTF-16BE",
        "iso-10646-ucs-basic"      => "UTF-16BE",
        "csunicodeascii"           => "UTF-16BE",
        "iso-10646-unicode-latin1" => "UTF-16BE",
        "csunicodelatin1"          => "UTF-16BE",
        "iso-10646"                => "UTF-16BE",
        "iso-10646-j-1"            => "UTF-16BE",
        //
        // Aliases for ISO-8859-10",
        //
        "latin6"                   => "ISO-8859-10",
        "iso-ir-157"               => "ISO-8859-10",
        "l6"                       => "ISO-8859-10",
        // Currently .properties cannot handle : in key",
        //iso_8859-10:1992" => "ISO-8859-10",
        "csisolatin6"              => "ISO-8859-10",
        //
        // Aliases for ISO-8859-15",
        //
        "iso_8859-15"              => "ISO-8859-15",
        "csisolatin9"              => "ISO-8859-15",
        "l9"                       => "ISO-8859-15",
        //
        // Aliases for ISO-IR-111",
        //
        "ecma-cyrillic"            => "ISO-IR-111",
        "csiso111ecmacyrillic"     => "ISO-IR-111",
        //
        // Aliases for ISO-2022-KR",
        //
        "csiso2022kr"              => "ISO-2022-KR",
        //
        // Aliases for VISCII",
        //
        "csviscii"                 => "VISCII",
        //
        // Aliases for x-euc-tw",
        //
        "zh_tw-euc"                => "x-euc-tw",
        //
        // Following names appears in unix nl_langinfo(CODESET)",
        // They can be compiled as platform specific if necessary",
        // DONT put things here if it does not look generic enough (like hp15CN)",
        //
        "iso88591"                 => "ISO-8859-1",
        "iso88592"                 => "ISO-8859-2",
        "iso88593"                 => "ISO-8859-3",
        "iso88594"                 => "ISO-8859-4",
        "iso88595"                 => "ISO-8859-5",
        "iso88596"                 => "ISO-8859-6",
        "iso88597"                 => "ISO-8859-7",
        "iso88598"                 => "ISO-8859-8",
        "iso88599"                 => "ISO-8859-9",
        "iso885910"                => "ISO-8859-10",
        "iso885911"                => "ISO-8859-11",
        "iso885912"                => "ISO-8859-12",
        "iso885913"                => "ISO-8859-13",
        "iso885914"                => "ISO-8859-14",
        "iso885915"                => "ISO-8859-15",
        "cp1250"                   => "windows-1250",
        "cp1251"                   => "windows-1251",
        "cp1252"                   => "windows-1252",
        "cp1253"                   => "windows-1253",
        "cp1254"                   => "windows-1254",
        "cp1255"                   => "windows-1255",
        "cp1256"                   => "windows-1256",
        "cp1257"                   => "windows-1257",
        "cp1258"                   => "windows-1258",
        "x-gbk"                    => "gbk",
        "windows-936"              => "gbk",
        "ansi-1251"                => "windows-1251",
    ];        
    
    /**
     * Returns proper encoding mapping, if exsists. If it doesn't, return unchanged $encoding
     * @param string $encoding
     * @param string|null $fallback
     *
     * @return string
     */
    public static function get($encoding, $fallback = null) {
        if (isset(self::$aliases[strtolower($encoding)])) {
            return self::$aliases[strtolower($encoding)];
        }
        return $fallback !== null ? $fallback : $encoding;
    }
    
}
