<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien DiCintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/install/etape4.php
 *	\ingroup	install
 *	\brief      Ask login and password of Dolibarr admin user
 */


include_once("./inc.php");
require_once($dolibarr_main_document_root."/core/class/conf.class.php");
require_once($dolibarr_main_document_root."/core/lib/admin.lib.php");


$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:'auto');
$langs->setDefaultLang($setuplang);

$langs->load("admin");
$langs->load("install");

// Init "forced values" to nothing. "forced values" are used after an doliwamp install wizard.
if (! isset($force_install_dolibarrlogin))     $force_install_dolibarrlogin='';
$useforcedwizard=false;
if (file_exists("./install.forced.php")) { $useforcedwizard=true; include_once("./install.forced.php"); }
else if (file_exists("/etc/dolibarr/install.forced.php")) { $useforcedwizard=include_once("/etc/dolibarr/install.forced.php"); }

dolibarr_install_syslog("--- etape4: Entering etape4.php page");

$err=0;
$ok = 0;



/*
 *	View
 */

pHeader($langs->trans("AdminAccountCreation"),"etape5");

// Test if we can run a first install process
if (! is_writable($conffile))
{
    print $langs->trans("ConfFileIsNotWritable",$conffiletoshow);
    pFooter(1,$setuplang,'jscheckparam');
    exit;
}


print '<br>'.$langs->trans("LastStepDesc").'<br><br>';


print '<table cellspacing="0" cellpadding="2" width="100%">';

$db=getDoliDBInstance($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);

if ($db->ok == 1)
{
    print '<tr><td>'.$langs->trans("DolibarrAdminLogin").' :</td><td>';
    print '<input name="login" value="'.(! empty($_GET["login"])?$_GET["login"]:$force_install_dolibarrlogin).'"></td></tr>';
    print '<tr><td>'.$langs->trans("Password").' :</td><td>';
    print '<input type="password" name="pass"></td></tr>';
    print '<tr><td>'.$langs->trans("PasswordAgain").' :</td><td>';
    print '<input type="password" name="pass_verif"></td></tr>';
    print '</table>';

    if (isset($_GET["error"]) && $_GET["error"] == 1)
    {
        print '<br>';
        print '<div class="error">'.$langs->trans("PasswordsMismatch").'</div>';
        $err=0;	// We show button
    }

    if (isset($_GET["error"]) && $_GET["error"] == 2)
    {
        print '<br>';
        print '<div class="error">';
        print $langs->trans("PleaseTypePassword");
        print '</div>';
        $err=0;	// We show button
    }

    if (isset($_GET["error"]) && $_GET["error"] == 3)
    {
        print '<br>';
        print '<div class="error">'.$langs->trans("PleaseTypeALogin").'</div>';
        $err=0;	// We show button
    }

}

$db->close();

dolibarr_install_syslog("--- install/etape4.php end", LOG_INFO);

pFooter($err,$setuplang);
?>
