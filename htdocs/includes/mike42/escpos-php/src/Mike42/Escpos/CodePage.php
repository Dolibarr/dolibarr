<?php
/**
 * This file is part of escpos-php: PHP receipt printer library for use with
 * ESC/POS-compatible thermal and impact printers.
 *
 * Copyright (c) 2014-16 Michael Billington < michael.billington@gmail.com >,
 * incorporating modifications by others. See CONTRIBUTORS.md for a full list.
 *
 * This software is distributed under the terms of the MIT license. See LICENSE.md
 * for details.
 */

namespace Mike42\Escpos;

/**
 * Code page constants, all of which appear in either iconv or one or more printer
 * vendor docs. These are referenced in a printer's CapabilityProfile.
 *
 * - The iconv names were exported from iconv -l
 * - Supported printer pages were mapped to standard pages where they appear in ivonv
 * - Code pages that aren't supported are listed here as 'false'.
 * - Many of these code page names are not used.
 */
abstract class CodePage
{
    /**
     * CP037
     */
    const CP037 = "CP037";

    /**
     * CP038
     */
    const CP038 = "CP038";

    /**
     * CP273
     */
    const CP273 = "CP273";

    /**
     * CP274
     */
    const CP274 = "CP274";

    /**
     * CP275
     */
    const CP275 = "CP275";

    /**
     * CP278
     */
    const CP278 = "CP278";

    /**
     * CP280
     */
    const CP280 = "CP280";

    /**
     * CP281
     */
    const CP281 = "CP281";

    /**
     * CP282
     */
    const CP282 = "CP282";

    /**
     * CP284
     */
    const CP284 = "CP284";

    /**
     * CP285
     */
    const CP285 = "CP285";

    /**
     * CP290
     */
    const CP290 = "CP290";

    /**
     * CP297
     */
    const CP297 = "CP297";

    /**
     * CP367
     */
    const CP367 = "CP367";

    /**
     * CP420
     */
    const CP420 = "CP420";

    /**
     * CP423
     */
    const CP423 = "CP423";

    /**
     * CP424
     */
    const CP424 = "CP424";

    /**
     * CP437
     */
    const CP437 = "CP437";

    /**
     * CP500
     */
    const CP500 = "CP500";

    /**
     * CP737
     */
    const CP737 = "CP737";

    /**
     * CP770
     */
    const CP770 = "CP770";

    /**
     * CP771
     */
    const CP771 = "CP771";

    /**
     * CP772
     */
    const CP772 = "CP772";

    /**
     * CP773
     */
    const CP773 = "CP773";

    /**
     * CP774
     */
    const CP774 = "CP774";

    /**
     * CP775
     */
    const CP775 = "CP775";

    /**
     * CP803
     */
    const CP803 = "CP803";

    /**
     * CP813
     */
    const CP813 = "CP813";

    /**
     * CP819
     */
    const CP819 = "CP819";

    /**
     * CP850
     */
    const CP850 = "CP850";

    /**
     * CP851 - not used due to differences between the tables in iconv and tested printers.
     */
    const CP851 = false;

    /**
     * CP852
     */
    const CP852 = "CP852";

    /**
     * CP855
     */
    const CP855 = "CP855";

    /**
     * CP856
     */
    const CP856 = "CP856";

    /**
     * CP857
     */
    const CP857 = "CP857";

    /**
     * CP860
     */
    const CP860 = "CP860";

    /**
     * CP861
     */
    const CP861 = "CP861";

    /**
     * CP862
     */
    const CP862 = "CP862";

    /**
     * CP863
     */
    const CP863 = "CP863";

    /**
     * CP864
     */
    const CP864 = "CP864";

    /**
     * CP865
     */
    const CP865 = "CP865";

    /**
     * CP866
     */
    const CP866 = "CP866";

    /**
     * CP866NAV
     */
    const CP866NAV = "CP866NAV";

    /**
     * CP868
     */
    const CP868 = "CP868";

    /**
     * CP869
     */
    const CP869 = "CP869";

    /**
     * CP870
     */
    const CP870 = "CP870";

    /**
     * CP871
     */
    const CP871 = "CP871";

    /**
     * CP874
     */
    const CP874 = "CP874";

    /**
     * CP875
     */
    const CP875 = "CP875";

    /**
     * CP880
     */
    const CP880 = "CP880";

    /**
     * CP891
     */
    const CP891 = "CP891";

    /**
     * CP901
     */
    const CP901 = "CP901";

    /**
     * CP902
     */
    const CP902 = "CP902";

    /**
     * CP903
     */
    const CP903 = "CP903";

    /**
     * CP904
     */
    const CP904 = "CP904";

    /**
     * CP905
     */
    const CP905 = "CP905";

    /**
     * CP912
     */
    const CP912 = "CP912";

    /**
     * CP915
     */
    const CP915 = "CP915";

    /**
     * CP916
     */
    const CP916 = "CP916";

    /**
     * CP918
     */
    const CP918 = "CP918";

    /**
     * CP920
     */
    const CP920 = "CP920";

    /**
     * CP921
     */
    const CP921 = "CP921";

    /**
     * CP922
     */
    const CP922 = "CP922";

    /**
     * CP930
     */
    const CP930 = "CP930";

    /**
     * CP932
     */
    const CP932 = "CP932";

    /**
     * CP933
     */
    const CP933 = "CP933";

    /**
     * CP935
     */
    const CP935 = "CP935";

    /**
     * CP936
     */
    const CP936 = "CP936";

    /**
     * CP937
     */
    const CP937 = "CP937";

    /**
     * CP939
     */
    const CP939 = "CP939";

    /**
     * CP949
     */
    const CP949 = "CP949";

    /**
     * CP950
     */
    const CP950 = "CP950";

    /**
     * CP1004
     */
    const CP1004 = "CP1004";

    /**
     * CP1008
     */
    const CP1008 = "CP1008";

    /**
     * CP1025
     */
    const CP1025 = "CP1025";

    /**
     * CP1026
     */
    const CP1026 = "CP1026";

    /**
     * CP1046
     */
    const CP1046 = "CP1046";

    /**
     * CP1047
     */
    const CP1047 = "CP1047";

    /**
     * CP1070
     */
    const CP1070 = "CP1070";

    /**
     * CP1079
     */
    const CP1079 = "CP1079";

    /**
     * CP1081
     */
    const CP1081 = "CP1081";

    /**
     * CP1084
     */
    const CP1084 = "CP1084";

    /**
     * CP1089
     */
    const CP1089 = "CP1089";

    /**
     * CP1097
     */
    const CP1097 = "CP1097";

    /**
     * CP1112
     */
    const CP1112 = "CP1112";

    /**
     * CP1122
     */
    const CP1122 = "CP1122";

    /**
     * CP1123
     */
    const CP1123 = "CP1123";

    /**
     * CP1124
     */
    const CP1124 = "CP1124";

    /**
     * CP1125
     */
    const CP1125 = "CP1125";

    /**
     * CP1129
     */
    const CP1129 = "CP1129";

    /**
     * CP1130
     */
    const CP1130 = "CP1130";

    /**
     * CP1132
     */
    const CP1132 = "CP1132";

    /**
     * CP1133
     */
    const CP1133 = "CP1133";

    /**
     * CP1137
     */
    const CP1137 = "CP1137";

    /**
     * CP1140
     */
    const CP1140 = "CP1140";

    /**
     * CP1141
     */
    const CP1141 = "CP1141";

    /**
     * CP1142
     */
    const CP1142 = "CP1142";

    /**
     * CP1143
     */
    const CP1143 = "CP1143";

    /**
     * CP1144
     */
    const CP1144 = "CP1144";

    /**
     * CP1145
     */
    const CP1145 = "CP1145";

    /**
     * CP1146
     */
    const CP1146 = "CP1146";

    /**
     * CP1147
     */
    const CP1147 = "CP1147";

    /**
     * CP1148
     */
    const CP1148 = "CP1148";

    /**
     * CP1149
     */
    const CP1149 = "CP1149";

    /**
     * CP1153
     */
    const CP1153 = "CP1153";

    /**
     * CP1154
     */
    const CP1154 = "CP1154";

    /**
     * CP1155
     */
    const CP1155 = "CP1155";

    /**
     * CP1156
     */
    const CP1156 = "CP1156";

    /**
     * CP1157
     */
    const CP1157 = "CP1157";

    /**
     * CP1158
     */
    const CP1158 = "CP1158";

    /**
     * CP1160
     */
    const CP1160 = "CP1160";

    /**
     * CP1161
     */
    const CP1161 = "CP1161";

    /**
     * CP1162
     */
    const CP1162 = "CP1162";

    /**
     * CP1163
     */
    const CP1163 = "CP1163";

    /**
     * CP1164
     */
    const CP1164 = "CP1164";

    /**
     * CP1166
     */
    const CP1166 = "CP1166";

    /**
     * CP1167
     */
    const CP1167 = "CP1167";

    /**
     * CP1250
     */
    const CP1250 = "CP1250";

    /**
     * CP1251
     */
    const CP1251 = "CP1251";

    /**
     * CP1252
     */
    const CP1252 = "CP1252";

    /**
     * CP1253
     */
    const CP1253 = "CP1253";

    /**
     * CP1254
     */
    const CP1254 = "CP1254";

    /**
     * CP1255
     */
    const CP1255 = "CP1255";

    /**
     * CP1256
     */
    const CP1256 = "CP1256";

    /**
     * CP1257
     */
    const CP1257 = "CP1257";

    /**
     * CP1258
     */
    const CP1258 = "CP1258";

    /**
     * CP1282
     */
    const CP1282 = "CP1282";

    /**
     * CP1361
     */
    const CP1361 = "CP1361";

    /**
     * CP1364
     */
    const CP1364 = "CP1364";

    /**
     * CP1371
     */
    const CP1371 = "CP1371";

    /**
     * CP1388
     */
    const CP1388 = "CP1388";

    /**
     * CP1390
     */
    const CP1390 = "CP1390";

    /**
     * CP1399
     */
    const CP1399 = "CP1399";

    /**
     * CP4517
     */
    const CP4517 = "CP4517";

    /**
     * CP4899
     */
    const CP4899 = "CP4899";

    /**
     * CP4909
     */
    const CP4909 = "CP4909";

    /**
     * CP4971
     */
    const CP4971 = "CP4971";

    /**
     * CP5347
     */
    const CP5347 = "CP5347";

    /**
     * CP9030
     */
    const CP9030 = "CP9030";

    /**
     * CP9066
     */
    const CP9066 = "CP9066";

    /**
     * CP9448
     */
    const CP9448 = "CP9448";

    /**
     * CP10007
     */
    const CP10007 = "CP10007";

    /**
     * CP12712
     */
    const CP12712 = "CP12712";

    /**
     * CP16804
     */
    const CP16804 = "CP16804";

    /**
     * ISO8859_7
     */
    const ISO8859_7 = "ISO_8859-7";

    /**
     * ISO8859_2
     */
    const ISO8859_2 = "ISO_8859-2";

    /**
     * ISO8859_15
     */
    const ISO8859_15 = "ISO_8859-15";

    /**
     * RK1048
     */
    const RK1048 = "RK1048";

    /**
     * CP720 - not built in to default iconv in Debian
     */
    const CP720 = false;

    /**
     * CP853 - not built in to default iconv in Debian
     */
    const CP853 = false;

    /**
     * CP858 - not built in to default iconv in Debian
     */
    const CP858 = false;

    /**
     * CP928 - not built in to default iconv in Debian
     */
    const CP928 = false;

    /**
     * CP1098 - not built in to default iconv in Debian
     */
    const CP1098 = false;

    /**
     * CP747 - not built in to default iconv in Debian
     */
    const CP747 = false;

    /**
     * CP3840 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP3840 = false;

    /**
     * CP3841 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP3841 = false;

    /**
     * CP3843 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP3843 = false;

    /**
     * CP3844 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP3844 = false;

    /**
     * CP3845 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP3845 = false;

    /**
     * CP3847 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP3847 = false;

    /**
     * CP3846 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP3846 = false;

    /**
     * CP3848 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP3848 = false;

    /**
     * CP1001 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP1001 = false;

    /**
     * CP2001 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP2001 = false;

    /**
     * CP3001 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP3001 = false;

    /**
     * CP3002 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP3002 = false;

    /**
     * CP3011 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP3011 = false;

    /**
     * CP3012 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP3012 = false;

    /**
     * CP3021 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP3021 = false;

    /**
     * CP3041 - May be Star vendor-specific, see StarCapabilityProfile
     */
    const CP3041 = false;
}
