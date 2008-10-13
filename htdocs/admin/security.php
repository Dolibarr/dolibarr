<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 *		\file       htdocs/admin/security.php
 *      \ingroup    setup
 *      \brief      Page de configuration du module sécurité
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/security.lib.php');

$langs->load("users");
$langs->load("admin");
$langs->load("other");

if (!$user->admin) accessforbidden();

// Allow/Disallow change to clear passwords once passwords are crypted
$allow_disable_encryption=true;

$mesg = '';


/*
 * Actions
 */
if ($_GET["action"] == 'setgeneraterule')
{
	if (! dolibarr_set_const($db, 'USER_PASSWORD_GENERATED',$_GET["value"]))
	{
		dolibarr_print_error($db);
	}
	else
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

if ($_GET["action"] == 'activate_encrypt')
{
	$db->begin();
	
    dolibarr_set_const($db, "DATABASE_PWD_ENCRYPTED", "1");
    $sql = "UPDATE ".MAIN_DB_PREFIX."user as u";
	$sql.= " SET u.pass = NULL AND u.pass_crypted = MD5(u.pass)";
	$sql.= " WHERE u.pass IS NOT NULL AND LENGTH(u.pass) < 32";	// Not a MD5 value

	//print $sql;
	$result = $db->query($sql);
    if ($result)
	{
		$db->commit();
		Header("Location: security.php");
	    exit;
	}
	else
	{
		dolibarr_print_error($db,'');
	}
}
else if ($_GET["action"] == 'disable_encrypt')
{
	//On n'autorise pas l'annulation de l'encryption car les mots de passe ne peuvent pas être décodés
	//Do not allow "disable encryption" as passwords cannot be decrypted
	if ($allow_disable_encryption)
	{
		dolibarr_del_const($db, "DATABASE_PWD_ENCRYPTED");
    }
	Header("Location: security.php");
    exit;
}

if ($_GET["action"] == 'activate_encryptdbpassconf')
{
	$result = encodedecode_dbpassconf(1);
	if ($result > 0)
	{
		// database value not required
		//dolibarr_set_const($db, "MAIN_DATABASE_PWD_CONFIG_ENCRYPTED", "1");
		Header("Location: security.php");
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans('ConfigFileIsInReadOnly').'</div>';
	}
}
else if ($_GET["action"] == 'disable_encryptdbpassconf')
{
	$result = encodedecode_dbpassconf(0);
	if ($result > 0)
	{
		// database value not required
		//dolibarr_del_const($db, "MAIN_DATABASE_PWD_CONFIG_ENCRYPTED");
		Header("Location: security.php");
		exit;
	}
	else
	{
		$mesg = '<div class="error">'.$langs->trans('ConfigFileIsInReadOnly').'</div>';
	}
}

if ($_GET["action"] == 'activate_pdfsecurity')
{
	dolibarr_set_const($db, "PDF_SECURITY_ENCRYPTION", "1");
	Header("Location: security.php");
	exit;
}
else if ($_GET["action"] == 'disable_pdfsecurity')
{
	dolibarr_del_const($db, "PDF_SECURITY_ENCRYPTION");
	Header("Location: security.php");
	exit;
}

if ($_GET["action"] == 'activate_MAIN_SECURITY_DISABLEFORGETPASSLINK')
{
	dolibarr_set_const($db, "MAIN_SECURITY_DISABLEFORGETPASSLINK", '1');
	Header("Location: security.php");
	exit;
}
else if ($_GET["action"] == 'disable_MAIN_SECURITY_DISABLEFORGETPASSLINK')
{
	dolibarr_del_const($db, "MAIN_SECURITY_DISABLEFORGETPASSLINK");
	Header("Location: security.php");
	exit;
}




/*
 * Affichage onglet
 */

llxHeader();

if ($mesg) print "$mesg\n";

print_fiche_titre($langs->trans("SecuritySetup"),'','setup');

print $langs->trans("GeneratedPasswordDesc")."<br>\n";
print "<br>\n";


$head=security_prepare_head();

dolibarr_fiche_head($head, 'passwords', $langs->trans("Security"));


$var=false;
$html = new Form($db);


// Choix du gestionnaire du générateur de mot de passe
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="USER_PASSWORD_GENERATED">';
print '<input type="hidden" name="consttype" value="yesno">';

// Charge tableau des modules generation
$dir = "../includes/modules/security/generate";
clearstatcache();
$handle=opendir($dir);
$i=1;
while (($file = readdir($handle))!==false)
{
    if (eregi('(modGeneratePass[a-z]+).class.php',$file,$reg))
    {
        // Chargement de la classe de numérotation
        $classname = $reg[1];
        require_once($dir.'/'.$file);

        $obj = new $classname($db,$conf,$langs,$user);
        $arrayhandler[$obj->id]=$obj;
		$i++;
    }
}
closedir($handle);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("RuleForGeneratedPasswords").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center">'.$langs->trans("Activated").'</td>';
print '</tr>';

foreach ($arrayhandler as $key => $module)
{
	// Show modules according to features level
    if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
    if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

	if ($module->isEnabled())
	{
        $var = !$var;
        print '<tr '.$bc[$var].'><td width="100">';
        print ucfirst($key);
        print "</td><td>\n";
        print $module->getDescription();
        print '</td>';

        // Affiche example
        print '<td width="60">'.$module->getExample().'</td>';

        print '<td width="100" align="center">';
        if ($conf->global->USER_PASSWORD_GENERATED == $key)
        {
            $title='';
            print img_tick($title);
        }
        else
        {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=setgeneraterule&amp;value='.$key.'">'.$langs->trans("Activate").'</a>';
        }
        print "</td></tr>\n";
	}
}
print '</table>';
print '</form>';

// Cryptage mot de passe
print '<br>';

$var=true;
print "<form method=\"post\" action=\"security.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"encrypt\">";

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Parameters").'</td>';
print '<td align="center">'.$langs->trans("Activated").'</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
print '</tr>';

// Disable clear password in database
$var=!$var;
print "<tr ".$bc[$var].">";
print '<td colspan="3">'.$langs->trans("DoNotStoreClearPassword").'</td>';
print '<td align="center" width="60">';
if ($conf->global->DATABASE_PWD_ENCRYPTED)
{
	print img_tick();
}
print '</td>';
if (! $conf->global->DATABASE_PWD_ENCRYPTED)
{
	print '<td align="center" width="100">';
	print '<a href="security.php?action=activate_encrypt">'.$langs->trans("Activate").'</a>';
	print "</td>";
}
if($conf->global->DATABASE_PWD_ENCRYPTED)
{
	print '<td align="center" width="100">';
	if ($allow_disable_encryption)
	{
		//On n'autorise pas l'annulation de l'encryption car les mots de passe ne peuvent pas être décodés
	  	//Do not allow "disable encryption" as passwords cannot be decrypted
	  	print '<a href="security.php?action=disable_encrypt">'.$langs->trans("Disable").'</a>';
	}
	else
	{
		print '-';
	}
	print "</td>";
}
print "</td>";
print '</tr>';


// Disable link "Forget password" on logon
$var=!$var;
print "<tr ".$bc[$var].">";
print '<td colspan="3">'.$langs->trans("DisableForgetPasswordLinkOnLogonPage").'</td>';
print '<td align="center" width="60">';
if($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK == 1)
{
	print img_tick();
}
print '</td>';
if ($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK == 0)
{
	print '<td align="center" width="100">';
	print '<a href="security.php?action=activate_MAIN_SECURITY_DISABLEFORGETPASSLINK">'.$langs->trans("Activate").'</a>';
	print "</td>";
}
if($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK == 1)
{
	print '<td align="center" width="100">';
	print '<a href="security.php?action=disable_MAIN_SECURITY_DISABLEFORGETPASSLINK">'.$langs->trans("Disable").'</a>';
	print "</td>";
}
print "</td>";
print '</tr>';


// Cryptage du mot de base de la base dans conf.php
$var=!$var;
print "<tr ".$bc[$var].">";
print '<td colspan="3">'.$langs->trans("MainDbPasswordFileConfEncrypted").'</td>';
print '<td align="center" width="60">';
if (! empty($dolibarr_main_db_encrypted_pass))
{
	print img_tick();
}

print '</td>';

print '<td align="center" width="100">';
// TODO Impossibilité de crypter le mot de passe lorsqu'il y a en a un, il affiche qu'il n'y en a pas !!
if (empty($dolibarr_main_db_pass) && empty($dolibarr_main_db_encrypted_pass)) 
{
	$langs->load("errors");
	print img_warning($langs->trans("WarningPassIsEmpty"));
}
else
{
	if (empty($dolibarr_main_db_encrypted_pass))
	{
		print '<a href="security.php?action=activate_encryptdbpassconf">'.$langs->trans("Activate").'</a>';
	}
	if (! empty($dolibarr_main_db_encrypted_pass))
	{
		print '<a href="security.php?action=disable_encryptdbpassconf">'.$langs->trans("Disable").'</a>';
	}
}
print "</td>";

print "</td>";
print '</tr>';

// Encryption et protection des PDF
$var=!$var;
print "<tr ".$bc[$var].">";
print '<td colspan="3">';
$text = $langs->trans("ProtectAndEncryptPdfFiles");
$desc = $html->textwithhelp($text,$langs->transnoentities("ProtectAndEncryptPdfFilesDesc"),1);
print $desc;
print '</td>';
print '<td align="center" width="60">';
if($conf->global->PDF_SECURITY_ENCRYPTION == 1)
{
	print img_tick();
}

print '</td>';

print '<td align="center" width="100">';
if ($conf->global->PDF_SECURITY_ENCRYPTION == 0)
{
	print '<a href="security.php?action=activate_pdfsecurity">'.$langs->trans("Activate").'</a>';
}
if($conf->global->PDF_SECURITY_ENCRYPTION == 1)
{
	print '<a href="security.php?action=disable_pdfsecurity">'.$langs->trans("Disable").'</a>';
}
print "</td>";

print "</td>";
print '</tr>';

print '</table>';
print '</form>';


//print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';

print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
