<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	 \file       htdocs/install/licence.php
 *   \ingroup    install
 *	 \brief      Page to show licence (Removed from install process to save time)
 *	 \version    $Id: licence.php,v 1.21 2011/07/31 23:26:22 eldy Exp $
 */

include_once("./inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:'auto');
$langs->setDefaultLang($setuplang);

$langs->load("install");

// Init "forced values" to nothing. "forced values" are used after an doliwamp install wizard.
$useforcedwizard=false;
if (file_exists("./install.forced.php")) { $useforcedwizard=true; include_once("./install.forced.php"); }
else if (file_exists("/etc/dolibarr/install.forced.php")) { $useforcedwizard=include_once("/etc/dolibarr/install.forced.php"); }

dolibarr_install_syslog("Licence: Entering licence.php page");


/*
 *	View
 */

pHeader($langs->trans("License"),"fileconf");

// Test if we can run a first install process
if (! is_writable($conffile))
{
    print $langs->trans("ConfFileIsNotWritable",$conffiletoshow);
    pFooter(1,$setuplang,'jscheckparam');
    exit;
}

//print '<pre style="align: center; font-size: 12px">';
$result=dol_print_file($langs,"html/gpl.html",1);
if (! $result)
{
    print '<center>'."\n";
    print '<textarea readonly="1" rows="26" cols="80">';
    dol_print_file($langs,"html/gpl.txt",1);
    print '</textarea>';
    print '</center>'."\n";
}
//print '</pre>';

pFooter(0,$setuplang);
?>
