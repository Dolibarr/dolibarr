<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *      \file       htdocs/adherents/info_subscription.php
 *      \ingroup    member
 *		\brief      Page with information of subscriptions of a member
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/cotisation.class.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("members");
$langs->load("users");

if (!$user->rights->adherent->lire)
	accessforbidden();

$rowid=isset($_GET["rowid"])?$_GET["rowid"]:$_POST["rowid"];



/*
 * Visualisation de la fiche
 *
 */

llxHeader();

$form = new Form($db);

$subscription = new Cotisation($db);
$result=$subscription->fetch($rowid);

$h = 0;
$head = array();

$head[$h][0] = DOL_URL_ROOT.'/adherents/fiche_subscription.php?rowid='.$subscription->id;
$head[$h][1] = $langs->trans("SubscriptionCard");
$head[$h][2] = 'general';
$h++;

$head[$h][0] = DOL_URL_ROOT.'/adherents/info_subscription.php?rowid='.$subscription->id;
$head[$h][1] = $langs->trans("Info");
$head[$h][2] = 'info';
$h++;


dol_fiche_head($head, 'info', $langs->trans("Subscription"), '', 'payment');

$subscription->info($rowid);

print '<table width="100%"><tr><td>';
dol_print_object_info($subscription);
print '</td></tr></table>';

print '</div>';


llxFooter();
$db->close();
