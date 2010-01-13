<?php
/* Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *	\file       htdocs/admin/numberwords.php
 *	\ingroup    numberwords
 *	\brief      Setup page for numberwords module
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/html.formadmin.class.php");

if (!$user->admin)
accessforbidden();

$langs->load("admin");
$langs->load("other");

$newvaltest='';
$outputlangs=new Translate('',$conf);
$outputlangs->setDefaultLang($langs->defaultlang);


/*
 * Actions
 */
if (! empty($_POST["action"]) && $_POST["action"] == 'setlevel')
{
	dolibarr_set_const($db,"SYSLOG_LEVEL",$_POST["level"],'chaine',0,'',0);
	dol_syslog("admin/syslog: level ".$_POST["level"]);
}

if (! empty($_POST["action"]) && $_POST["action"] == 'set')
{
	$optionlogoutput=$_POST["optionlogoutput"];
	if ($optionlogoutput == "syslog")
	{
		if (defined($_POST["facility"]))
		{
			// Only LOG_USER supported on Windows
			if (! empty($_SERVER["WINDIR"])) $_POST["facility"]='LOG_USER';

			dolibarr_del_const($db,"SYSLOG_FILE",0);
			dolibarr_set_const($db,"SYSLOG_FACILITY",$_POST["facility"],'chaine',0,'',0);
			dol_syslog("admin/syslog: facility ".$_POST["facility"]);
		}
		else
		{
			print '<div class="error">'.$langs->trans("ErrorUnknownSyslogConstant",$_POST["facility"]).'</div>';
		}
	}

	if ($optionlogoutput == "file")
	{
		$filelog=$_POST["filename"];
		$filelog=preg_replace('/DOL_DATA_ROOT/i',DOL_DATA_ROOT,$filelog);
		$file=fopen($filelog,"a+");
		if ($file)
		{
			fclose($file);
			dolibarr_del_const($db,"SYSLOG_FACILITY",0);
			dolibarr_set_const($db,"SYSLOG_FILE",$_POST["filename"],'chaine',0,'',0);
			dol_syslog("admin/syslog: file ".$_POST["filename"]);
		}
		else
		{
			print '<div class="error">'.$langs->trans("ErrorFailedToOpenFile",$_POST["filename"]).'</div>';
		}
	}
}

if ($_POST["action"] == 'test' && trim($_POST["value"]) != '')
{
	if ($_POST["lang_id"]) $outputlangs->setDefaultLang($_POST["lang_id"]);

	if ($_POST["level"])
	{
		$object->total_ttc=$_POST["value"];
		$source='__TOTAL_TTC_WORDS__';
	}
	else
	{
		$object->number=$_POST["value"];
		$source='__NUMBER_WORDS__';
	}
	$newvaltest=make_substitutions($source,array(),$outputlangs,$object);
}



/*
 * View
 */

llxHeader();

$html=new Form($db);
$htmlother=new FormAdmin($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("NumberWordsSetup"),$linkback,'setup');

print $langs->trans("DescNumberWords").'<br>';
print '<br>';

// Mode
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="test">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Example").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Language").'</td>';
print '<td>&nbsp;</td>';
print '<td>'.$langs->trans("Result").'</td>';
print "</tr>\n";

$var=true;

$var=!$var;
print '<tr '.$bc[$var].'><td width="140">'.$langs->trans("Number").'</td>';
$val='989';
print '<td>'.$val.'</td>';
print '<td>'.$outputlangs->defaultlang.'</td>';
print '<td>&nbsp;</td>';
$object->number=$val;
$newval=make_substitutions('__NUMBER_WORDS__',array(),$outputlangs,$object);
print '<td>'.$newval.'</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td width="140">'.$langs->trans("Amount").'</td>';
$val='989.99';
print '<td>'.$val.'</td>';
print '<td>'.$outputlangs->defaultlang.'</td>';
print '<td>&nbsp;</td>';
$object->total_ttc=$val;
$newval=make_substitutions('__TOTAL_TTC_WORDS__',array(),$outputlangs,$object);
print '<td>'.$newval.'</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
$val=$_POST["level"];
print '<td><select class="flat" name="level" '.$option.'>';
print '<option value="0" '.($_POST["level"]=='0'?'SELECTED':'').'>'.$langs->trans("Number").'</option>';
print '<option value="1" '.($_POST["level"]=='1'?'SELECTED':'').'>'.$langs->trans("Amount").'</option>';
print '</select>';
print '</td>';
print '<td><input type="text" name="value" class="flat" value="'.$_POST["value"].'"></td>';
print '<td>';
$htmlother->select_lang($_POST["lang_id"]?$_POST["lang_id"]:$langs->defaultlang,'lang_id');
print '</td>';
print '<td><input type="submit" class="button" '.$option.' value="'.$langs->trans("ToTest").'"></td>';
print '<td>'.$newvaltest.'</td>';
print '</tr>';

print '</table>';

print "</form>\n";

llxFooter('$Date$ - $Revision$');
?>
