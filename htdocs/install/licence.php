<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/install/licence.php
        \ingroup    install
		\brief      Page affichage license
		\version    $Id$
*/

include_once("./inc.php");

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:'auto');
$langs->setDefaultLang($setuplang);

$langs->load("install");

// Init "forced values" to nothing. "forced values" are used after an doliwamp install wizard.
if (file_exists("./install.forced.php")) include_once("./install.forced.php");

dolibarr_install_syslog("Licence: Entering licence.php page");


/*
*	View
*/

pHeader($langs->trans("License"),"fileconf");


//print '<pre style="align: center; font-size: 12px">';
$result=$langs->print_file("html/gpl.html",1);
if (! $result)
{
	print '<center>'."\n";
	print '<textarea readonly="1" rows="26" cols="80">';
	$result=$langs->print_file("html/gpl.txt",1);
	print '</textarea>';
	print '</center>'."\n";
}
//print '</pre>';

pFooter(0,$setuplang);
?>
