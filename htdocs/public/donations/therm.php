<?php
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	    \file       htdocs/public/donations/therm.php
 *      \ingroup    donation
 *		\brief      Screen with thermometer
 *		\version    $Id$
 */

require("../../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/images.lib.php");
require_once(DOL_DOCUMENT_ROOT."/don.class.php");

// Define lang object automatically using browser language
$langs->setDefaultLang('auto');

// Security check
if (empty($conf->don->enabled)) accessforbidden('',1,1,1);



/*
 * 	View
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

?>
