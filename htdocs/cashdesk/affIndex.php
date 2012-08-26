<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier      <jeremie.o@laposte.net>
 * Copyright (C) 2008-2010 Laurent Destailleur   <eldy@uers.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2011      Juanjo Menent         <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/cashdesk/affIndex.php
 *	\ingroup    cashdesk
 *	\brief      First page of point of sale module
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/cashdesk/include/environnement.php';

// Test if already logged
if ( $_SESSION['uid'] <= 0 )
{
	header('Location: index.php');
	exit;
}

$langs->load("cashdesk");


/*
 * View
 */

//header("Content-type: text/html; charset=UTF-8");
header("Content-type: text/html; charset=".$conf->file->character_set_client);

$arrayofjs=array();
$arrayofcss=array('/cashdesk/css/style.css');

top_htmlhead($head,$langs->trans("CashDesk"),0,0,$arrayofjs,$arrayofcss);

print '<body>'."\n";

if (!empty($error))
{
	print $error;
	print '</body></html>';
	exit;
}

print '<div class="conteneur">'."\n";
print '<div class="conteneur_img_gauche">'."\n";
print '<div class="conteneur_img_droite">'."\n";

print '<h1 class="entete"><span>POINT OF SALE</span></h1>'."\n";

print '<div class="menu_principal">'."\n";
include_once 'tpl/menu.tpl.php';
print '</div>'."\n";

print '<div class="contenu">'."\n";
include_once 'affContenu.php';
print '</div>'."\n";

include_once 'affPied.php';

print '</div></div></div>'."\n";
print '</body></html>'."\n";
?>