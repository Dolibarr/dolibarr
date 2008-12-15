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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 \file       htdocs/install/etape4.php
 \ingroup	install
 \brief      Demande le login et mot de passe de l'administrateur Dolibarr
 \version    $Id$
 */


include_once("./inc.php");
require_once($dolibarr_main_document_root."/lib/databases/".$dolibarr_main_db_type.".lib.php");
require_once($dolibarr_main_document_root."/conf/conf.class.php");
require_once($dolibarr_main_document_root."/lib/admin.lib.php");


$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:'auto');
$langs->setDefaultLang($setuplang);

$langs->load("admin");
$langs->load("install");

// Init "forced values" to nothing. "forced values" are used after an doliwamp install wizard.
if (! isset($force_install_dolibarrlogin))     $force_install_dolibarrlogin='';
if (file_exists("./install.forced.php")) include_once("./install.forced.php");

dolibarr_install_syslog("etape4: Entering etape4.php page");

$err=0;
$ok = 0;



/*
 *	View
 */

pHeader($langs->trans("AdminAccountCreation"),"etape5");

print '<table cellspacing="0" cellpadding="2" width="100%">';

$db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);
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
		$err=1;
	}

	if (isset($_GET["error"]) && $_GET["error"] == 2)
	{
		print '<br>';
		print '<div class="error">';
		print $langs->trans("PleaseTypePassword");
		print '</div>';
		$err=1;
	}

	if (isset($_GET["error"]) && $_GET["error"] == 3)
	{
		print '<br>';
		print '<div class="error">'.$langs->trans("PleaseTypeALogin").'</div>';
		$err=1;
	}

}

$db->close();

pFooter($err,$setuplang);
?>
