<?php
/* Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Source$
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

llxHeader();

print_titre($langs->trans("SyslogSetup"));
print '<br>';

$def = array();

/*
 * Actions 
 */
$optionlogoutput=$_POST["optionlogoutput"];
if ($optionlogoutput == "syslog") {
    if (defined($_POST["facility"])) {
        dolibarr_del_const($db,"SYSLOG_FILE");
        dolibarr_set_const($db,"SYSLOG_FACILITY",$_POST["facility"]);
    } else {
        print '<div class="error">'.$langs->trans("ErrorUnknownSyslogConstant",$_POST["facility"]).'</div>';
    }
}
if ($optionlogoutput == "file") {
    $file=fopen($_POST["filename"],"a+");
    if ($file) {
        fclose($file);
        dolibarr_del_const($db,"SYSLOG_FACILITY");
        dolibarr_set_const($db,"SYSLOG_FILE",$_POST["filename"]);
    }
    else {
        print '<div class="error">'.$langs->trans("ErrorFailedToOpenFile",$_POST["filename"]).'</div>';
    }
}


$syslogfacility=$defaultsyslogfacility=dolibarr_get_const($db,"SYSLOG_FACILITY");
$syslogfile=$defaultsyslogfile=dolibarr_get_const($db,"SYSLOG_FILE");
if (! $defaultsyslogfacility) $defaultsyslogfacility='LOG_USER';
if (! $defaultsyslogfile) $defaultsyslogfile='dolibarr.log';

/*
 *  Mode de sortie
 */
print_titre($langs->trans("SyslogOutput"));

print '<table class="noborder" width=\"100%\">';
print '<form action="syslog.php" method="post">';
print '<input type="hidden" name="action" value="set">';
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
print "</form>";
print "</table>";


llxFooter('$Date$ - $Revision$');
?>
