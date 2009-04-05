<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/admin/security_other.php
 *      \ingroup    core
 *      \brief      Security options setup
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("users");
$langs->load("admin");
$langs->load("other");

if (!$user->admin) accessforbidden();


/*
 * Actions
 */
if ($_GET["action"] == 'set_main_upload_doc')
{
	if (! dolibarr_set_const($db, 'MAIN_UPLOAD_DOC',$_POST["MAIN_UPLOAD_DOC"]))
	{
		dol_print_error($db);
	}
	else
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

if ($_GET["action"] == 'activate_captcha')
{
	dolibarr_set_const($db, "MAIN_SECURITY_ENABLECAPTCHA", '1');
	Header("Location: security_other.php");
	exit;
}
else if ($_GET["action"] == 'disable_captcha')
{
	dolibarr_del_const($db, "MAIN_SECURITY_ENABLECAPTCHA");
	Header("Location: security_other.php");
	exit;
}

if ($_GET["action"] == 'MAIN_SESSION_TIMEOUT')
{
	dolibarr_set_const($db, "MAIN_SESSION_TIMEOUT", $_POST["MAIN_SESSION_TIMEOUT"]);
	Header("Location: security_other.php");
	exit;
}

if ($_GET["action"] == 'MAIN_UMASK')
{
	dolibarr_set_const($db, "MAIN_UMASK", $_POST["MAIN_UMASK"]);
	Header("Location: security_other.php");
	exit;
}

if ($_GET["action"] == 'activate_avscan')
{
	dolibarr_set_const($db, "MAIN_USE_AVSCAN", '1');
	Header("Location: security_other.php");
	exit;
}
else if ($_GET["action"] == 'disable_avscan')
{
	dolibarr_del_const($db, "MAIN_USE_AVSCAN");
	Header("Location: security_other.php");
	exit;
}


/*
 * Affichage onglet
 */

llxHeader('',$langs->trans("Miscellanous"));

print_fiche_titre($langs->trans("SecuritySetup"),'','setup');

print $langs->trans("MiscellanousDesc")."<br>\n";
print "<br>\n";


$head=security_prepare_head();

dol_fiche_head($head, 'misc', $langs->trans("Security"));


$var=false;
$form = new Form($db);


print '<table width="100%" class="noborder">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>&nbsp;</td>';
print "</tr>\n";


print '<form action="'.$_SERVER["PHP_SELF"].'?action=set_main_upload_doc" method="POST">';
print '<tr '.$bc[$var].'>';
print '<td colspan="2">'.$langs->trans("MaxSizeForUploadedFiles").'.';
$max=@ini_get('upload_max_filesize');
if ($max) print ' '.$langs->trans("MustBeLowerThanPHPLimit",$max*1024,$langs->trans("Kb")).'.';
else print ' '.$langs->trans("NoMaxSizeByPHPLimit").'.';
print '</td>';
print '<td nowrap="1">';
print '<input class="flat" name="MAIN_UPLOAD_DOC" type="text" size="6" value="'.$conf->global->MAIN_UPLOAD_DOC.'"> '.$langs->trans("Kb");
print '</td>';
print '<td align="center">';
print '<input type="submit" class="button" name="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr></form>';

$var=!$var;
print '<form action="'.$_SERVER["PHP_SELF"].'?action=MAIN_UMASK" method="POST">';
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("UMask").'</td><td align="right">';
print $form->textwithhelp('',$langs->trans("UMaskExplanation"));
print '</td>';
print '<td nowrap="1">';
print '<input class="flat" name="MAIN_UMASK" type="text" size="6" value="'.$conf->global->MAIN_UMASK.'">';
print '</td>';
print '<td align="center">';
print '<input type="submit" class="button" name="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr></form>';

$var=!$var;
if (empty($conf->global->MAIN_SESSION_TIMEOUT)) $conf->global->MAIN_SESSION_TIMEOUT=ini_get("session.gc_maxlifetime");
print '<form action="'.$_SERVER["PHP_SELF"].'?action=MAIN_SESSION_TIMEOUT" method="POST">';
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("SessionTimeOut").'</td><td align="right">';
print $form->textwithhelp('',$langs->trans("SessionExplanation",ini_get("session.gc_probability"),ini_get("session.gc_divisor")));
print '</td>';
print '<td nowrap="1">';
print '<input class="flat" name="MAIN_SESSION_TIMEOUT" type="text" size="6" value="'.$conf->global->MAIN_SESSION_TIMEOUT.'"> '.$langs->trans("seconds");
print '</td>';
print '<td align="center">';
print '<input type="submit" class="button" name="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr></form>';

print '</table>';

print '<br>';

// Autre Options

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Parameters").'</td>';
print '<td align="center" width="80">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="80">'.$langs->trans("Action").'</td>';
print '</tr>';

// Enable Captcha code
$var=!$var;
print "<tr ".$bc[$var].">";
print '<td colspan="3">'.$langs->trans("UseCaptchaCode").'</td>';
print '<td align="center" width="60">';
if($conf->global->MAIN_SECURITY_ENABLECAPTCHA == 1)
{
 print img_tick();
}
print '</td>';
print '<td align="center" width="100">';
if (function_exists("imagecreatefrompng"))
{
	if ($conf->global->MAIN_SECURITY_ENABLECAPTCHA == 0)
	{
		print '<a href="security_other.php?action=activate_captcha">'.$langs->trans("Activate").'</a>';
	}
	if($conf->global->MAIN_SECURITY_ENABLECAPTCHA == 1)
	{
		print '<a href="security_other.php?action=disable_captcha">'.$langs->trans("Disable").'</a>';
	}
}
else
{
	$html = new Form($db);
	$desc = $html->textwithwarning('',$langs->transnoentities("EnableGDLibraryDesc"),1);
	print $desc;
}
print "</td>";

print "</td>";
print '</tr>';

// Enable AV scanner
$var=!$var;
print "<tr ".$bc[$var].">";
print '<td colspan="3">'.$langs->trans("UseAvToScanUploadedFiles");
if($conf->global->MAIN_USE_AVSCAN == 1)
{
	print ' : ';
	// Clamav
	if (function_exists("cl_scanfile"))
	{
		print cl_info();
	}
}
print '</td>';
print '<td align="center" width="60">';
if($conf->global->MAIN_USE_AVSCAN == 1)
{
 print img_tick();
}
print '</td>';
print '<td align="center" width="100">';
if (function_exists("cl_scanfile")) // Clamav
{
	if ($conf->global->MAIN_USE_AVSCAN == 0)
	{
		print '<a href="security_other.php?action=activate_avscan">'.$langs->trans("Activate").'</a>';
	}
	if($conf->global->MAIN_USE_AVSCAN == 1)
	{
		print '<a href="security_other.php?action=disable_avscan">'.$langs->trans("Disable").'</a>';
	}
}
else
{
	$html = new Form($db);
	$desc = $html->textwithwarning('',$langs->transnoentities("EnablePhpAVModuleDesc"),1);
	print $desc;
}
print "</td>";

print "</td>";
print '</tr>';

print '</table>';

print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
