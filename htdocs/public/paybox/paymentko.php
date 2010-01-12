<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *     	\file       htdocs/public/paybox/paymentok.php
 *		\ingroup    paybox
 *		\brief      File to offer a way to make a payment for a particular Dolibarr entity
 *		\author	    Laurent Destailleur
 *		\version    $Id$
 */

define("NOLOGIN",1);	// This means this output page does not require to be logged.

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/paybox/paybox.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

// Security check
if (empty($conf->paybox->enabled)) accessforbidden('',1,1,1);

$langs->load("main");
$langs->load("other");
$langs->load("paybox");
$langs->load("dict");
$langs->load("bills");
$langs->load("companies");




/*
 * Actions
 */





/*
 * View
 */

llxHeaderPayBox($langs->trans("PaymentForm"));



html_print_footer($mysoc,$langs);


$db->close();

llxFooterPayBox('$Date$ - $Revision$');
?>
