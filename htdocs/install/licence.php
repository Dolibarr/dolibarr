<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net> 
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
		\version    $Source$
*/

include_once("./inc.php");

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:'auto');
$langs->setDefaultLang($setuplang);

$langs->load("install");

dolibarr_install_syslog("licence: Entering licence.php page");


pHeader($langs->trans("License"),"fileconf");

print '<center>'."\n";
//print '<pre style="align: center; font-size: 12px">';
print '<textarea readonly="1" rows="26" cols="80">';
$langs->print_file("html/gpl.txt",1);
//print '</pre>';
print '</textarea>';
print '</center>'."\n";

pFooter(0,$setuplang);

?>
