<?php
/* Copyright (C) 2026 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/versionmod.inc.php
 * 	\ingroup	core
 *  \brief      File included by main files of the Unalterable Log module
 */

// The version of the POS system
define('DOLCERT_VERSION', '2.0.0');

// DOL_VERSION is now a.b.c-alpha, a.b.c-beta, a.b.c-rcX or a.b.c

// Set to 1 if the beta version is the candidate for certification or if the stable version has been certified.
// Set to 2 to force LNE features for debug purposes, and flag version as candidate for certification. It just has one difference with 1: https is not required in this mode
// Set to 0 for standard version or if you don't want to use the certification because you chosen to comply the law by using an attestation of an IT provider that guarantee
//          the the software is compliant. WARNING: In this case, you must find an IT company that give you the attestation of conformity.
if (!defined('CERTIF_LNE')) {
	define('CERTIF_LNE', '2');
}
