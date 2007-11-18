<?php
/* Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 */

/**
   \file       htdocs/admin/syslog.php
   \ingroup    syslog
   \brief      Page de configuration du module syslog
   \version    $Revision$
*/
require("./pre.inc.php");

if (!$user->admin)
  accessforbidden();

$langs->load("admin");
$langs->load("other");


/*
 * Actions 
 */
if ($_POST["action"] == 'setlevel')
{
    dolibarr_set_const($db,"SYSLOG_LEVEL",$_POST["level"]);
    dolibarr_syslog("admin/syslog: level ".$_POST["level"]);
}

if ($_POST["action"] == 'set')
{
	$optionlogoutput=$_POST["optionlogoutput"];
	if ($optionlogoutput == "syslog") 
	{
	  if (defined($_POST["facility"])) 
	    {
	      dolibarr_del_const($db,"SYSLOG_FILE");
	      dolibarr_set_const($db,"SYSLOG_FACILITY",$_POST["facility"]);
	      dolibarr_syslog("admin/syslog: facility ".$_POST["facility"]);
	      Header("Location: syslog.php");
	      exit;
	    } 
	  else
	    {
	      print '<div class="error">'.$langs->trans("ErrorUnknownSyslogConstant",$_POST["facility"]).'</div>';
	    }
	}
	if ($optionlogoutput == "file")
	{
	  $file=fopen($_POST["filename"],"a+");
	  if ($file)
	    {
	      fclose($file);
	      dolibarr_del_const($db,"SYSLOG_FACILITY");
	      dolibarr_set_const($db,"SYSLOG_FILE",$_POST["filename"]);
	      dolibarr_syslog("admin/syslog: file ".$_POST["filename"]);
	    }
	  else
	    {
	      print '<div class="error">'.$langs->trans("ErrorFailedToOpenFile",$_POST["filename"]).'</div>';
	    }
	}
}



llxHeader();

print_fiche_titre($langs->trans("SyslogSetup"),'','setup');
print '<br>';

$def = array();

$syslogfacility=$defaultsyslogfacility=dolibarr_get_const($db,"SYSLOG_FACILITY");
$syslogfile=$defaultsyslogfile=dolibarr_get_const($db,"SYSLOG_FILE");
if (! $defaultsyslogfacility) $defaultsyslogfacility='LOG_USER';
if (! $defaultsyslogfile) $defaultsyslogfile='dolibarr.log';

/*
 *  Mode de sortie
 */
print_titre($langs->trans("SyslogOutput"));

// Mode
print '<form action="syslog.php" method="post">';
print '<input type="hidden" name="action" value="set">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Type").'</td><td>'.$langs->trans("Parameter").'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";
$var=true;
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"140\"><input type=\"radio\" name=\"optionlogoutput\" value=\"syslog\" ".($syslogfacility?" checked":"")."> ".$langs->trans("SyslogSyslog")."</td>";
print '<td colspan="2">'.$langs->trans("SyslogFacility").': <input type="text" class="flat" name="facility" value="'.$defaultsyslogfacility.'"></td></tr>';

$var=!$var;
print "<tr ".$bc[$var]."><td width=\"140\"><input type=\"radio\" name=\"optionlogoutput\" value=\"file\"".($syslogfile?" checked":"")."> ".$langs->trans("SyslogSimpleFile")."</td>";
print '<td colspan="2">'.$langs->trans("SyslogFilename").': <input type="text" class="flat" name="filename" size="60" value="'.$defaultsyslogfile.'"></td></tr>';

print "</table>\n";
print "</form>\n";

// Level
print '<form action="syslog.php" method="post">';
print '<input type="hidden" name="action" value="setlevel">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Type").'</td><td>'.$langs->trans("Parameter").'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";
$var=true;
$var=!$var;
print '<tr '.$bc[$var].'><td width=\"140\">'.$langs->trans("SyslogLevel").'</td>';
print '<td colspan="2"><select class="flat" name="level">';
print '<option value="'.LOG_EMERG.'" '.($conf->global->SYSLOG_LEVEL==LOG_EMERG?'SELECTED':'').'>LOG_EMERG ('.LOG_EMERG.')</option>';
print '<option value="'.LOG_ALERT.'" '.($conf->global->SYSLOG_LEVEL==LOG_ALERT?'SELECTED':'').'>LOG_ALERT ('.LOG_ALERT.')</option>';
print '<option value="'.LOG_CRIT.'" '.($conf->global->SYSLOG_LEVEL==LOG_CRIT?'SELECTED':'').'>LOG_CRIT ('.LOG_CRIT.')</option>';
print '<option value="'.LOG_ERR.'" '.($conf->global->SYSLOG_LEVEL==LOG_ERR?'SELECTED':'').'>LOG_ERR ('.LOG_ERR.')</option>';
print '<option value="'.LOG_WARNING.'" '.($conf->global->SYSLOG_LEVEL==LOG_WARNING?'SELECTED':'').'>LOG_WARNING ('.LOG_WARNING.')</option>';
print '<option value="'.LOG_NOTICE.'" '.($conf->global->SYSLOG_LEVEL==LOG_NOTICE?'SELECTED':'').'>LOG_NOTICE ('.LOG_NOTICE.')</option>';
print '<option value="'.LOG_INFO.'" '.($conf->global->SYSLOG_LEVEL==LOG_INFO?'SELECTED':'').'>LOG_INFO ('.LOG_INFO.')</option>';
print '<option value="'.LOG_DEBUG.'" '.($conf->global->SYSLOG_LEVEL==LOG_DEBUG?'SELECTED':'').'>LOG_DEBUG ('.LOG_DEBUG.')</option>';
print '</select>';
print '</td></tr>';
print '</table>';
print "</form>\n";

llxFooter('$Date$ - $Revision$');
?>
