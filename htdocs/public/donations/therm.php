<?php
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/public/donations/therm.php
 *      \ingroup    donation
 *		\brief      Screen with thermometer
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';

// Security check
if (empty($conf->don->enabled)) accessforbidden('',1,1,1);



/*
 * 	View (output an image)
 */

$dontherm = new Don($db);

$intentValue  = $dontherm->sum_donations(1);
$pendingValue = $dontherm->sum_donations(2);
$actualValue  = $dontherm->sum_donations(3);

$db->close();


/*
 * Graph thermometer
 */
print moneyMeter($actualValue, $pendingValue, $intentValue);

