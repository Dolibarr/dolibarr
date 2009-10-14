<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/admin/geoipmaxmind.php
 *	\ingroup    geoipmaxmind
 *	\brief      Setup page for geoipmaxmind module
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/dolgeoip.class.php");

// Security check
if (!$user->admin)
accessforbidden();

$langs->load("admin");
$langs->load("errors");

/*
 * Actions
 */
if ($_POST["action"] == 'set')
{
	$error=0;
	if (! empty($_POST["GEOIPMAXMIND_COUNTRY_DATAFILE"]) && ! file_exists($_POST["GEOIPMAXMIND_COUNTRY_DATAFILE"]))
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFileNotFound",$_POST["GEOIPMAXMIND_COUNTRY_DATAFILE"]).'</div>';
		$error++;
	}
	
	if (! $error)
	{
		dolibarr_set_const($db,"GEOIPMAXMIND_COUNTRY_DATAFILE",$_POST["GEOIPMAXMIND_COUNTRY_DATAFILE"],'chaine',0,'',$conf->entity);
	}
}



/*
 * View
 */

$form=new Form($db);

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("GeoIPMaxmindSetup"),$linkback,'setup');
print '<br>';

if ($mesg) print $mesg;

$version='';
$geoip='';
if (! empty($conf->global->GEOIPMAXMIND_COUNTRY_DATAFILE))
{
	$geoip=new DolGeoIP('country',$conf->global->GEOIPMAXMIND_COUNTRY_DATAFILE);
}

// Mode
$var=true;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td width=\"50%\">'.$langs->trans("PathToGeoIPMaxmindCountryDataFile").'</td>';
print '<td colspan="2">';
print '<input size="50" type="text" name="GEOIPMAXMIND_COUNTRY_DATAFILE" value="'.$conf->global->GEOIPMAXMIND_COUNTRY_DATAFILE.'">';
if ($geoip) $version=$geoip->getVersion();
if ($version)
{
	print '<br>'.$langs->trans("Version").': '.$version;
}
print '</td></tr>';

print '</table>';

print "</form>\n";

print '<br>';

print $langs->trans("NoteOnPathLocation").'<br>';

$url1='http://www.maxmind.com/app/perl?rId=awstats';
print $langs->trans("YouCanDownloadFreeDatFileTo",'<a href="'.$url1.'" target="_blank">'.$url1.'</a>');

print '<br>';

$url2='http://www.maxmind.com/app/perl?rId=awstats';
print $langs->trans("YouCanDownloadAdvancedDatFileTo",'<a href="'.$url2.'" target="_blank">'.$url2.'</a>');

if ($geoip)
{
	print '<br><br>';
	print '<br>';
	$ip='24.24.24.24';
	print $langs->trans("TestGeoIPResult",$ip).':<br>';
	print $ip.' -> ';
	$result=dol_print_ip($ip,1);
	if ($result) print $result;
	else print $langs->trans("Error");

	$geoip->close();
}

llxFooter('$Date$ - $Revision$');
?>
