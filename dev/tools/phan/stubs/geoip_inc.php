<?php
// phpcs:disable Generic.Files.LineLength,PEAR.Commenting,PEAR.NamingConventions
class GeoIP
{
	public $flags;
	public $filehandle;
	public $memory_buffer;
	public $databaseType;
	public $databaseSegments;
	public $record_length;
	public $shmid;
	public $GEOIP_COUNTRY_CODE_TO_NUMBER = array("" => 0, "AP" => 1, "EU" => 2, "AD" => 3, "AE" => 4, "AF" => 5, "AG" => 6, "AI" => 7, "AL" => 8, "AM" => 9, "CW" => 10, "AO" => 11, "AQ" => 12, "AR" => 13, "AS" => 14, "AT" => 15, "AU" => 16, "AW" => 17, "AZ" => 18, "BA" => 19, "BB" => 20, "BD" => 21, "BE" => 22, "BF" => 23, "BG" => 24, "BH" => 25, "BI" => 26, "BJ" => 27, "BM" => 28, "BN" => 29, "BO" => 30, "BR" => 31, "BS" => 32, "BT" => 33, "BV" => 34, "BW" => 35, "BY" => 36, "BZ" => 37, "CA" => 38, "CC" => 39, "CD" => 40, "CF" => 41, "CG" => 42, "CH" => 43, "CI" => 44, "CK" => 45, "CL" => 46, "CM" => 47, "CN" => 48, "CO" => 49, "CR" => 50, "CU" => 51, "CV" => 52, "CX" => 53, "CY" => 54, "CZ" => 55, "DE" => 56, "DJ" => 57, "DK" => 58, "DM" => 59, "DO" => 60, "DZ" => 61, "EC" => 62, "EE" => 63, "EG" => 64, "EH" => 65, "ER" => 66, "ES" => 67, "ET" => 68, "FI" => 69, "FJ" => 70, "FK" => 71, "FM" => 72, "FO" => 73, "FR" => 74, "SX" => 75, "GA" => 76, "GB" => 77, "GD" => 78, "GE" => 79, "GF" => 80, "GH" => 81, "GI" => 82, "GL" => 83, "GM" => 84, "GN" => 85, "GP" => 86, "GQ" => 87, "GR" => 88, "GS" => 89, "GT" => 90, "GU" => 91, "GW" => 92, "GY" => 93, "HK" => 94, "HM" => 95, "HN" => 96, "HR" => 97, "HT" => 98, "HU" => 99, "ID" => 100, "IE" => 101, "IL" => 102, "IN" => 103, "IO" => 104, "IQ" => 105, "IR" => 106, "IS" => 107, "IT" => 108, "JM" => 109, "JO" => 110, "JP" => 111, "KE" => 112, "KG" => 113, "KH" => 114, "KI" => 115, "KM" => 116, "KN" => 117, "KP" => 118, "KR" => 119, "KW" => 120, "KY" => 121, "KZ" => 122, "LA" => 123, "LB" => 124, "LC" => 125, "LI" => 126, "LK" => 127, "LR" => 128, "LS" => 129, "LT" => 130, "LU" => 131, "LV" => 132, "LY" => 133, "MA" => 134, "MC" => 135, "MD" => 136, "MG" => 137, "MH" => 138, "MK" => 139, "ML" => 140, "MM" => 141, "MN" => 142, "MO" => 143, "MP" => 144, "MQ" => 145, "MR" => 146, "MS" => 147, "MT" => 148, "MU" => 149, "MV" => 150, "MW" => 151, "MX" => 152, "MY" => 153, "MZ" => 154, "NA" => 155, "NC" => 156, "NE" => 157, "NF" => 158, "NG" => 159, "NI" => 160, "NL" => 161, "NO" => 162, "NP" => 163, "NR" => 164, "NU" => 165, "NZ" => 166, "OM" => 167, "PA" => 168, "PE" => 169, "PF" => 170, "PG" => 171, "PH" => 172, "PK" => 173, "PL" => 174, "PM" => 175, "PN" => 176, "PR" => 177, "PS" => 178, "PT" => 179, "PW" => 180, "PY" => 181, "QA" => 182, "RE" => 183, "RO" => 184, "RU" => 185, "RW" => 186, "SA" => 187, "SB" => 188, "SC" => 189, "SD" => 190, "SE" => 191, "SG" => 192, "SH" => 193, "SI" => 194, "SJ" => 195, "SK" => 196, "SL" => 197, "SM" => 198, "SN" => 199, "SO" => 200, "SR" => 201, "ST" => 202, "SV" => 203, "SY" => 204, "SZ" => 205, "TC" => 206, "TD" => 207, "TF" => 208, "TG" => 209, "TH" => 210, "TJ" => 211, "TK" => 212, "TM" => 213, "TN" => 214, "TO" => 215, "TL" => 216, "TR" => 217, "TT" => 218, "TV" => 219, "TW" => 220, "TZ" => 221, "UA" => 222, "UG" => 223, "UM" => 224, "US" => 225, "UY" => 226, "UZ" => 227, "VA" => 228, "VC" => 229, "VE" => 230, "VG" => 231, "VI" => 232, "VN" => 233, "VU" => 234, "WF" => 235, "WS" => 236, "YE" => 237, "YT" => 238, "RS" => 239, "ZA" => 240, "ZM" => 241, "ME" => 242, "ZW" => 243, "A1" => 244, "A2" => 245, "O1" => 246, "AX" => 247, "GG" => 248, "IM" => 249, "JE" => 250, "BL" => 251, "MF" => 252, "BQ" => 253, "SS" => 254);
	public $GEOIP_COUNTRY_CODES = array("", "AP", "EU", "AD", "AE", "AF", "AG", "AI", "AL", "AM", "CW", "AO", "AQ", "AR", "AS", "AT", "AU", "AW", "AZ", "BA", "BB", "BD", "BE", "BF", "BG", "BH", "BI", "BJ", "BM", "BN", "BO", "BR", "BS", "BT", "BV", "BW", "BY", "BZ", "CA", "CC", "CD", "CF", "CG", "CH", "CI", "CK", "CL", "CM", "CN", "CO", "CR", "CU", "CV", "CX", "CY", "CZ", "DE", "DJ", "DK", "DM", "DO", "DZ", "EC", "EE", "EG", "EH", "ER", "ES", "ET", "FI", "FJ", "FK", "FM", "FO", "FR", "SX", "GA", "GB", "GD", "GE", "GF", "GH", "GI", "GL", "GM", "GN", "GP", "GQ", "GR", "GS", "GT", "GU", "GW", "GY", "HK", "HM", "HN", "HR", "HT", "HU", "ID", "IE", "IL", "IN", "IO", "IQ", "IR", "IS", "IT", "JM", "JO", "JP", "KE", "KG", "KH", "KI", "KM", "KN", "KP", "KR", "KW", "KY", "KZ", "LA", "LB", "LC", "LI", "LK", "LR", "LS", "LT", "LU", "LV", "LY", "MA", "MC", "MD", "MG", "MH", "MK", "ML", "MM", "MN", "MO", "MP", "MQ", "MR", "MS", "MT", "MU", "MV", "MW", "MX", "MY", "MZ", "NA", "NC", "NE", "NF", "NG", "NI", "NL", "NO", "NP", "NR", "NU", "NZ", "OM", "PA", "PE", "PF", "PG", "PH", "PK", "PL", "PM", "PN", "PR", "PS", "PT", "PW", "PY", "QA", "RE", "RO", "RU", "RW", "SA", "SB", "SC", "SD", "SE", "SG", "SH", "SI", "SJ", "SK", "SL", "SM", "SN", "SO", "SR", "ST", "SV", "SY", "SZ", "TC", "TD", "TF", "TG", "TH", "TJ", "TK", "TM", "TN", "TO", "TL", "TR", "TT", "TV", "TW", "TZ", "UA", "UG", "UM", "US", "UY", "UZ", "VA", "VC", "VE", "VG", "VI", "VN", "VU", "WF", "WS", "YE", "YT", "RS", "ZA", "ZM", "ME", "ZW", "A1", "A2", "O1", "AX", "GG", "IM", "JE", "BL", "MF", "BQ", "SS", "O1");
	public $GEOIP_COUNTRY_CODES3 = array("", "AP", "EU", "AND", "ARE", "AFG", "ATG", "AIA", "ALB", "ARM", "CUW", "AGO", "ATA", "ARG", "ASM", "AUT", "AUS", "ABW", "AZE", "BIH", "BRB", "BGD", "BEL", "BFA", "BGR", "BHR", "BDI", "BEN", "BMU", "BRN", "BOL", "BRA", "BHS", "BTN", "BVT", "BWA", "BLR", "BLZ", "CAN", "CCK", "COD", "CAF", "COG", "CHE", "CIV", "COK", "CHL", "CMR", "CHN", "COL", "CRI", "CUB", "CPV", "CXR", "CYP", "CZE", "DEU", "DJI", "DNK", "DMA", "DOM", "DZA", "ECU", "EST", "EGY", "ESH", "ERI", "ESP", "ETH", "FIN", "FJI", "FLK", "FSM", "FRO", "FRA", "SXM", "GAB", "GBR", "GRD", "GEO", "GUF", "GHA", "GIB", "GRL", "GMB", "GIN", "GLP", "GNQ", "GRC", "SGS", "GTM", "GUM", "GNB", "GUY", "HKG", "HMD", "HND", "HRV", "HTI", "HUN", "IDN", "IRL", "ISR", "IND", "IOT", "IRQ", "IRN", "ISL", "ITA", "JAM", "JOR", "JPN", "KEN", "KGZ", "KHM", "KIR", "COM", "KNA", "PRK", "KOR", "KWT", "CYM", "KAZ", "LAO", "LBN", "LCA", "LIE", "LKA", "LBR", "LSO", "LTU", "LUX", "LVA", "LBY", "MAR", "MCO", "MDA", "MDG", "MHL", "MKD", "MLI", "MMR", "MNG", "MAC", "MNP", "MTQ", "MRT", "MSR", "MLT", "MUS", "MDV", "MWI", "MEX", "MYS", "MOZ", "NAM", "NCL", "NER", "NFK", "NGA", "NIC", "NLD", "NOR", "NPL", "NRU", "NIU", "NZL", "OMN", "PAN", "PER", "PYF", "PNG", "PHL", "PAK", "POL", "SPM", "PCN", "PRI", "PSE", "PRT", "PLW", "PRY", "QAT", "REU", "ROU", "RUS", "RWA", "SAU", "SLB", "SYC", "SDN", "SWE", "SGP", "SHN", "SVN", "SJM", "SVK", "SLE", "SMR", "SEN", "SOM", "SUR", "STP", "SLV", "SYR", "SWZ", "TCA", "TCD", "ATF", "TGO", "THA", "TJK", "TKL", "TKM", "TUN", "TON", "TLS", "TUR", "TTO", "TUV", "TWN", "TZA", "UKR", "UGA", "UMI", "USA", "URY", "UZB", "VAT", "VCT", "VEN", "VGB", "VIR", "VNM", "VUT", "WLF", "WSM", "YEM", "MYT", "SRB", "ZAF", "ZMB", "MNE", "ZWE", "A1", "A2", "O1", "ALA", "GGY", "IMN", "JEY", "BLM", "MAF", "BES", "SSD", "O1");
	public $GEOIP_COUNTRY_NAMES = array("", "Asia/Pacific Region", "Europe", "Andorra", "United Arab Emirates", "Afghanistan", "Antigua and Barbuda", "Anguilla", "Albania", "Armenia", "Curacao", "Angola", "Antarctica", "Argentina", "American Samoa", "Austria", "Australia", "Aruba", "Azerbaijan", "Bosnia and Herzegovina", "Barbados", "Bangladesh", "Belgium", "Burkina Faso", "Bulgaria", "Bahrain", "Burundi", "Benin", "Bermuda", "Brunei Darussalam", "Bolivia", "Brazil", "Bahamas", "Bhutan", "Bouvet Island", "Botswana", "Belarus", "Belize", "Canada", "Cocos (Keeling) Islands", "Congo, The Democratic Republic of the", "Central African Republic", "Congo", "Switzerland", "Cote D'Ivoire", "Cook Islands", "Chile", "Cameroon", "China", "Colombia", "Costa Rica", "Cuba", "Cape Verde", "Christmas Island", "Cyprus", "Czech Republic", "Germany", "Djibouti", "Denmark", "Dominica", "Dominican Republic", "Algeria", "Ecuador", "Estonia", "Egypt", "Western Sahara", "Eritrea", "Spain", "Ethiopia", "Finland", "Fiji", "Falkland Islands (Malvinas)", "Micronesia, Federated States of", "Faroe Islands", "France", "Sint Maarten (Dutch part)", "Gabon", "United Kingdom", "Grenada", "Georgia", "French Guiana", "Ghana", "Gibraltar", "Greenland", "Gambia", "Guinea", "Guadeloupe", "Equatorial Guinea", "Greece", "South Georgia and the South Sandwich Islands", "Guatemala", "Guam", "Guinea-Bissau", "Guyana", "Hong Kong", "Heard Island and McDonald Islands", "Honduras", "Croatia", "Haiti", "Hungary", "Indonesia", "Ireland", "Israel", "India", "British Indian Ocean Territory", "Iraq", "Iran, Islamic Republic of", "Iceland", "Italy", "Jamaica", "Jordan", "Japan", "Kenya", "Kyrgyzstan", "Cambodia", "Kiribati", "Comoros", "Saint Kitts and Nevis", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Cayman Islands", "Kazakhstan", "Lao People's Democratic Republic", "Lebanon", "Saint Lucia", "Liechtenstein", "Sri Lanka", "Liberia", "Lesotho", "Lithuania", "Luxembourg", "Latvia", "Libya", "Morocco", "Monaco", "Moldova, Republic of", "Madagascar", "Marshall Islands", "Macedonia", "Mali", "Myanmar", "Mongolia", "Macau", "Northern Mariana Islands", "Martinique", "Mauritania", "Montserrat", "Malta", "Mauritius", "Maldives", "Malawi", "Mexico", "Malaysia", "Mozambique", "Namibia", "New Caledonia", "Niger", "Norfolk Island", "Nigeria", "Nicaragua", "Netherlands", "Norway", "Nepal", "Nauru", "Niue", "New Zealand", "Oman", "Panama", "Peru", "French Polynesia", "Papua New Guinea", "Philippines", "Pakistan", "Poland", "Saint Pierre and Miquelon", "Pitcairn Islands", "Puerto Rico", "Palestinian Territory", "Portugal", "Palau", "Paraguay", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saudi Arabia", "Solomon Islands", "Seychelles", "Sudan", "Sweden", "Singapore", "Saint Helena", "Slovenia", "Svalbard and Jan Mayen", "Slovakia", "Sierra Leone", "San Marino", "Senegal", "Somalia", "Suriname", "Sao Tome and Principe", "El Salvador", "Syrian Arab Republic", "Swaziland", "Turks and Caicos Islands", "Chad", "French Southern Territories", "Togo", "Thailand", "Tajikistan", "Tokelau", "Turkmenistan", "Tunisia", "Tonga", "Timor-Leste", "Turkey", "Trinidad and Tobago", "Tuvalu", "Taiwan", "Tanzania, United Republic of", "Ukraine", "Uganda", "United States Minor Outlying Islands", "United States", "Uruguay", "Uzbekistan", "Holy See (Vatican City State)", "Saint Vincent and the Grenadines", "Venezuela", "Virgin Islands, British", "Virgin Islands, U.S.", "Vietnam", "Vanuatu", "Wallis and Futuna", "Samoa", "Yemen", "Mayotte", "Serbia", "South Africa", "Zambia", "Montenegro", "Zimbabwe", "Anonymous Proxy", "Satellite Provider", "Other", "Aland Islands", "Guernsey", "Isle of Man", "Jersey", "Saint Barthelemy", "Saint Martin", "Bonaire, Saint Eustatius and Saba", "South Sudan", "Other");
	public $GEOIP_CONTINENT_CODES = array("--", "AS", "EU", "EU", "AS", "AS", "NA", "NA", "EU", "AS", "NA", "AF", "AN", "SA", "OC", "EU", "OC", "NA", "AS", "EU", "NA", "AS", "EU", "AF", "EU", "AS", "AF", "AF", "NA", "AS", "SA", "SA", "NA", "AS", "AN", "AF", "EU", "NA", "NA", "AS", "AF", "AF", "AF", "EU", "AF", "OC", "SA", "AF", "AS", "SA", "NA", "NA", "AF", "AS", "AS", "EU", "EU", "AF", "EU", "NA", "NA", "AF", "SA", "EU", "AF", "AF", "AF", "EU", "AF", "EU", "OC", "SA", "OC", "EU", "EU", "NA", "AF", "EU", "NA", "AS", "SA", "AF", "EU", "NA", "AF", "AF", "NA", "AF", "EU", "AN", "NA", "OC", "AF", "SA", "AS", "AN", "NA", "EU", "NA", "EU", "AS", "EU", "AS", "AS", "AS", "AS", "AS", "EU", "EU", "NA", "AS", "AS", "AF", "AS", "AS", "OC", "AF", "NA", "AS", "AS", "AS", "NA", "AS", "AS", "AS", "NA", "EU", "AS", "AF", "AF", "EU", "EU", "EU", "AF", "AF", "EU", "EU", "AF", "OC", "EU", "AF", "AS", "AS", "AS", "OC", "NA", "AF", "NA", "EU", "AF", "AS", "AF", "NA", "AS", "AF", "AF", "OC", "AF", "OC", "AF", "NA", "EU", "EU", "AS", "OC", "OC", "OC", "AS", "NA", "SA", "OC", "OC", "AS", "AS", "EU", "NA", "OC", "NA", "AS", "EU", "OC", "SA", "AS", "AF", "EU", "EU", "AF", "AS", "OC", "AF", "AF", "EU", "AS", "AF", "EU", "EU", "EU", "AF", "EU", "AF", "AF", "SA", "AF", "NA", "AS", "AF", "NA", "AF", "AN", "AF", "AS", "AS", "OC", "AS", "AF", "OC", "AS", "EU", "NA", "OC", "AS", "AF", "EU", "AF", "OC", "NA", "SA", "AS", "EU", "NA", "SA", "NA", "NA", "AS", "OC", "OC", "OC", "AS", "AF", "EU", "AF", "AF", "EU", "AF", "--", "--", "--", "EU", "EU", "EU", "EU", "NA", "NA", "NA", "AF", "--");
}
/* -*- Mode: C; indent-tabs-mode: t; c-basic-offset: 2; tab-width: 2 -*- */
/* geoip.inc
 *
 * Copyright (C) 2007 MaxMind LLC
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
\define("GEOIP_COUNTRY_BEGIN", 16776960);
\define("GEOIP_STATE_BEGIN_REV0", 16700000);
\define("GEOIP_STATE_BEGIN_REV1", 16000000);
\define("GEOIP_STANDARD", 0);
\define("GEOIP_MEMORY_CACHE", 1);
\define("GEOIP_SHARED_MEMORY", 2);
\define("STRUCTURE_INFO_MAX_SIZE", 20);
\define("DATABASE_INFO_MAX_SIZE", 100);
\define("GEOIP_COUNTRY_EDITION", 1);
\define("GEOIP_PROXY_EDITION", 8);
\define("GEOIP_ASNUM_EDITION", 9);
\define("GEOIP_NETSPEED_EDITION", 10);
\define("GEOIP_REGION_EDITION_REV0", 7);
\define("GEOIP_REGION_EDITION_REV1", 3);
\define("GEOIP_CITY_EDITION_REV0", 6);
\define("GEOIP_CITY_EDITION_REV1", 2);
\define("GEOIP_ORG_EDITION", 5);
\define("GEOIP_ISP_EDITION", 4);
\define("SEGMENT_RECORD_LENGTH", 3);
\define("STANDARD_RECORD_LENGTH", 3);
\define("ORG_RECORD_LENGTH", 4);
\define("MAX_RECORD_LENGTH", 4);
\define("MAX_ORG_RECORD_LENGTH", 300);
\define("GEOIP_SHM_KEY", 0x4f415401);
\define("US_OFFSET", 1);
\define("CANADA_OFFSET", 677);
\define("WORLD_OFFSET", 1353);
\define("FIPS_RANGE", 360);
\define("GEOIP_UNKNOWN_SPEED", 0);
\define("GEOIP_DIALUP_SPEED", 1);
\define("GEOIP_CABLEDSL_SPEED", 2);
\define("GEOIP_CORPORATE_SPEED", 3);
\define("GEOIP_DOMAIN_EDITION", 11);
\define("GEOIP_COUNTRY_EDITION_V6", 12);
\define("GEOIP_LOCATIONA_EDITION", 13);
\define("GEOIP_ACCURACYRADIUS_EDITION", 14);
\define("GEOIP_CITYCOMBINED_EDITION", 15);
\define("GEOIP_CITY_EDITION_REV1_V6", 30);
\define("GEOIP_CITY_EDITION_REV0_V6", 31);
\define("GEOIP_NETSPEED_EDITION_REV1", 32);
\define("GEOIP_NETSPEED_EDITION_REV1_V6", 33);
\define("GEOIP_USERTYPE_EDITION", 28);
\define("GEOIP_USERTYPE_EDITION_V6", 29);
\define("GEOIP_ASNUM_EDITION_V6", 21);
\define("GEOIP_ISP_EDITION_V6", 22);
\define("GEOIP_ORG_EDITION_V6", 23);
\define("GEOIP_DOMAIN_EDITION_V6", 24);
\define("CITYCOMBINED_FIXED_RECORD", 7);
function geoip_load_shared_mem($file)
{
}
function _setup_segments($gi)
{
}
// This should be only used for variable-length records where
// $start + $maxLength may be greater than the shared mem size
function _sharedMemRead($gi, $start, $maxLength)
{
}
/**
 * @param string $filename
 * @param int    $flags
 * @return GeoIP
 */
function geoip_open($filename, $flags)
{
}
/**
 * @param GeoIP $gi
 * @return bool
 */
function geoip_close($gi)
{
}
function geoip_country_id_by_name_v6($gi, $name)
{
}
function geoip_country_id_by_name($gi, $name)
{
}
function geoip_country_code_by_name_v6($gi, $name)
{
}
function geoip_country_code_by_name($gi, $name)
{
}
function geoip_country_name_by_name_v6($gi, $name)
{
}
function geoip_country_name_by_name($gi, $name)
{
}
function geoip_country_id_by_addr_v6($gi, $addr)
{
}
function geoip_country_id_by_addr($gi, $addr)
{
}
/**
 * @return string|false
 */
function geoip_country_code_by_addr_v6($gi, $addr)
{
}
/**
 * @param GeoIP $gi
 * @return string|false
 */
function geoip_country_code_by_addr($gi, $addr)
{
}
/**
 * @param GeoIP $gi
 * @return string|false
 */
function geoip_country_name_by_addr_v6($gi, $addr)
{
}
/**
 * @param GeoIP $gi
 * @return string|false
 */
function geoip_country_name_by_addr($gi, $addr)
{
}
function _geoip_seek_country_v6($gi, $ipnum)
{
}
function _geoip_seek_country($gi, $ipnum)
{
}
function _common_get_org($gi, $seek_org)
{
}
function _get_org_v6($gi, $ipnum)
{
}
function _get_org($gi, $ipnum)
{
}
function geoip_name_by_addr_v6($gi, $addr)
{
}
function geoip_name_by_addr($gi, $addr)
{
}
function geoip_org_by_addr($gi, $addr)
{
}
function _get_region($gi, $ipnum)
{
}
function geoip_region_by_addr($gi, $addr)
{
}
function _safe_substr($string, $start, $length)
{
}
