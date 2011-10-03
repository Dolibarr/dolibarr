<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/admin/syslog.php
 *	\ingroup    syslog
 *	\brief      Setup page for logs module
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

if (!$user->admin) accessforbidden();

$langs->load("admin");
$langs->load("other");

$error=0; $mesg='';
$action = GETPOST("action");
$syslog_file_on=(defined('SYSLOG_FILE_ON') && constant('SYSLOG_FILE_ON'))?1:0;
$syslog_syslog_on=(defined('SYSLOG_SYSLOG_ON') && constant('SYSLOG_SYSLOG_ON'))?1:0;


/*
 * Actions
 */

// Set modes
if ($action == 'set')
{
	$db->begin();

    $res = dolibarr_del_const($db,"SYSLOG_FILE_ON",0);
    $res = dolibarr_del_const($db,"SYSLOG_SYSLOG_ON",0);

	if (! $error && GETPOST("filename"))
	{
		$filename=GETPOST("filename");
		$filelog=GETPOST("filename");
		$filelog=preg_replace('/DOL_DATA_ROOT/i',DOL_DATA_ROOT,$filelog);
		$file=@fopen($filelog,"a+");
		if ($file)
		{
			fclose($file);

			dol_syslog("admin/syslog: file ".$filename);
			$res = dolibarr_set_const($db,"SYSLOG_FILE",$filename,'chaine',0,'',0);
			if (! $res > 0) $error++;
            $syslog_file_on=GETPOST('SYSLOG_FILE_ON');
			if (! $error) $res = dolibarr_set_const($db,"SYSLOG_FILE_ON",$syslog_file_on,'chaine',0,'',0);
		}
		else
		{
		    $error++;
		    $mesg = "<font class=\"error\">".$langs->trans("ErrorFailedToOpenFile",$filename)."</font>";
		}
	}

	if (! $error && GETPOST("facility"))
	{
	    $facility=GETPOST("facility");
	    if (defined($_POST["facility"]))
		{
			// Only LOG_USER supported on Windows
			if (! empty($_SERVER["WINDIR"])) $facility='LOG_USER';

			dol_syslog("admin/syslog: facility ".$facility);
			$res = dolibarr_set_const($db,"SYSLOG_FACILITY",$facility,'chaine',0,'',0);
			if (! $res > 0) $error++;
            $syslog_syslog_on=GETPOST('SYSLOG_SYSLOG_ON');
			if (! $error) $res = dolibarr_set_const($db,"SYSLOG_SYSLOG_ON",$syslog_syslog_on,'chaine',0,'',0);
		}
		else
		{
		    $error++;
		    $mesg = "<font class=\"error\">".$langs->trans("ErrorUnknownSyslogConstant",$facility)."</font>";
		}
	}

	if (! $error)
	{
		$db->commit();
		$mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
	}
	else
	{
		$db->rollback();
		if (empty($mesg)) $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
	}

}

// Set level
if ($action == 'setlevel')
{
	$level = GETPOST("level");
	$res = dolibarr_set_const($db,"SYSLOG_LEVEL",$level,'chaine',0,'',0);
	dol_syslog("admin/syslog: level ".$level);

	if (! $res > 0) $error++;
	if (! $error)
    {
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
	}
}

/*
 * View
 */

llxHeader();

$html=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("SyslogSetup"),$linkback,'setup');
print '<br>';

$def = array();

$syslogfacility=$defaultsyslogfacility=dolibarr_get_const($db,"SYSLOG_FACILITY",0);
$syslogfile=$defaultsyslogfile=dolibarr_get_const($db,"SYSLOG_FILE",0);

if (! $defaultsyslogfacility) $defaultsyslogfacility='LOG_USER';
if (! $defaultsyslogfile) $defaultsyslogfile='dolibarr.log';

if ($conf->global->MAIN_MODULE_MULTICOMPANY && $user->entity)
{
	print '<div class="error">'.$langs->trans("ContactSuperAdminForChange").'</div>';
	$option = 'disabled="disabled"';
}

// Output mode
print_titre($langs->trans("SyslogOutput"));

// Mode
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Type").'</td><td>'.$langs->trans("Value").'</td>';
print '<td align="right" colspan="2"><input type="submit" class="button" '.$option.' value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";
$var=true;

$var=!$var;
print '<tr '.$bc[$var].'><td width="140"><input '.$bc[$var].' type="checkbox" name="SYSLOG_FILE_ON" '.$option.' value="1" '.($syslog_file_on?" checked":"").'> '.$langs->trans("SyslogSimpleFile").'</td>';
print '<td width="250" nowrap="nowrap">'.$langs->trans("SyslogFilename").': <input type="text" class="flat" name="filename" '.$option.' size="60" value="'.$defaultsyslogfile.'">';
print '</td>';
print "<td align=\"left\">".$html->textwithpicto('',$langs->trans("YouCanUseDOL_DATA_ROOT"));
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td width="140"><input '.$bc[$var].' type="checkbox" name="SYSLOG_SYSLOG_ON" '.$option.' value="1" '.($syslog_syslog_on?" checked":"").'> '.$langs->trans("SyslogSyslog").'</td>';
print '<td width="250" nowrap="nowrap">'.$langs->trans("SyslogFacility").': <input type="text" class="flat" name="facility" '.$option.' value="'.$defaultsyslogfacility.'">';
print '</td>';
print "<td align=\"left\">".$html->textwithpicto('','Only LOG_USER supported on Windows');
print '</td></tr>';

print "</table>\n";
print "</form>\n";

print '<br>';

print_titre($langs->trans("SyslogLevel"));

// Level
print '<form action="syslog.php" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setlevel">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td>';
print '<td align="right"><input type="submit" class="button" '.$option.' value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";
$var=true;
$var=!$var;
print '<tr '.$bc[$var].'><td width=\"140\">'.$langs->trans("SyslogLevel").'</td>';
print '<td colspan="2"><select class="flat" name="level" '.$option.'>';
print '<option value="'.LOG_EMERG.'" '.($conf->global->SYSLOG_LEVEL==LOG_EMERG?'SELECTED':'').'>LOG_EMERG ('.LOG_EMERG.')</option>';
print '<option value="'.LOG_ALERT.'" '.($conf->global->SYSLOG_LEVEL==LOG_ALERT?'SELECTED':'').'>LOG_ALERT ('.LOG_ALERT.')</option>';
print '<option value="'.LOG_CRIT.'" '.($conf->global->SYSLOG_LEVEL==LOG_CRIT?'SELECTED':'').'>LOG_CRIT ('.LOG_CRIT.')</option>';
print '<option value="'.LOG_ERR.'" '.($conf->global->SYSLOG_LEVEL==LOG_ERR?'SELECTED':'').'>LOG_ERR ('.LOG_ERR.')</option>';
print '<option value="'.LOG_WARNING.'" '.($conf->global->SYSLOG_LEVEL==LOG_WARNING?'SELECTED':'').'>LOG_WARNING ('.LOG_WARNING.')</option>';
print '<option value="'.LOG_NOTICE.'" '.($conf->global->SYSLOG_LEVEL==LOG_NOTICE?'SELECTED':'').'>LOG_NOTICE ('.LOG_NOTICE.')</option>';
print '<option value="'.LOG_INFO.'" '.($conf->global->SYSLOG_LEVEL==LOG_INFO?'SELECTED':'').'>LOG_INFO ('.LOG_INFO.')</option>';
print '<option value="'.LOG_DEBUG.'" '.($conf->global->SYSLOG_LEVEL>=LOG_DEBUG?'SELECTED':'').'>LOG_DEBUG ('.LOG_DEBUG.')</option>';
print '</select>';
print '</td></tr>';
print '</table>';
print "</form>\n";

dol_htmloutput_mesg($mesg);

$db->close();

llxFooter();
?>
