<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/compta/paiement/rapport.php
 *	\ingroup    facture
 *	\brief      Payment reports page
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/rapport/pdf_paiement.class.php");

// Security check
if (! $user->rights->facture->lire)
accessforbidden();

$dir = $conf->compta->dir_output.'/payments';

$socid=0;
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
	$dir = DOL_DATA_ROOT.'/private/'.$user->id.'/compta';
}

$year = $_GET["year"];
if (! $year) { $year=date("Y"); }


/*
 * Actions
 */

if ($_POST["action"] == 'gen')
{
	$rap = new pdf_paiement($db);

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}

	// We save charset_output to restore it because write_file can change it if needed for
	// output format that does not support UTF8.
	$sav_charset_output=$outputlangs->charset_output;
	if ($rap->write_file($dir, $_POST["remonth"], $_POST["reyear"], $outputlangs) > 0)
	{
		$outputlangs->charset_output=$sav_charset_output;
	}
	else
	{
		$outputlangs->charset_output=$sav_charset_output;
		dolibarr_syslog("Erreur dans commande_pdf_create");
		dolibarr_print_error($db,$obj->pdferror());
	}

	$year = $_POST["reyear"];
}


/*
 * View
 */

llxHeader();

$titre=($year?$langs->trans("PaymentsReportsForYear",$year):$langs->trans("PaymentsReports"));
print_fiche_titre($titre);

// Formulaire de génération
print '<form method="post" action="rapport.php?year='.$year.'">';
print '<input type="hidden" name="action" value="gen">';
$cmonth = date("n", time());
$syear = date("Y", time());

print '<select name="remonth">';
for ($month = 1 ; $month < 13 ; $month++)
{
	if ($month == $cmonth)
	{
		print "<option value=\"$month\" selected=\"true\">" . dolibarr_print_date(mktime(0,0,0,$month),"%B");
	}
	else
	{
		print "<option value=\"$month\">" . dolibarr_print_date(mktime(0,0,0,$month),"%B");
	}
}
print "</select>";
print '<select name="reyear">';

for ($formyear = $syear - 2; $formyear < $syear +1 ; $formyear++)
{
	if ($formyear == $syear)
	{
		print "<option value=\"$formyear\" selected=\"true\">".$formyear."</option>";
	}
	else
	{
		print "<option value=\"$formyear\">".$formyear."</option>";
	}
}
print "</select>\n";
print '<input type="submit" class="button" value="'.$langs->trans("Create").'">';
print '</form>';
print '<br>';

clearstatcache();

// Affiche lien sur autres années
$linkforyear=array();
$found=0;
if (is_dir($dir))
{
	$handle=opendir($dir);
	while (($file = readdir($handle))!==false)
	{
		if (is_dir($dir.'/'.$file) && ! eregi('^\.',$file))
		{
			$found=1;
			$linkforyear[]=$file;
		}
	}
}
asort($linkforyear);
foreach($linkforyear as $cursoryear)
{
	print '<a href="rapport.php?year='.$cursoryear.'">'.$cursoryear.'</a> &nbsp;';
}

if ($year)
{
	if (is_dir($dir.'/'.$year))
	{
		$handle=opendir($dir.'/'.$year);

		if ($found) print '<br>';
		print '<br>';
		print '<table width="100%" class="noborder">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Reporting").'</td>';
		print '<td align="right">'.$langs->trans("Size").'</td>';
		print '<td align="right">'.$langs->trans("Date").'</td>';
		print '</tr>';
		$var=true;
		while (($file = readdir($handle))!==false)
		{
	  if (eregi('^payment',$file))
	  {
	  	$var=!$var;
	  	$tfile = $dir . '/'.$year.'/'.$file;
	  	$relativepath = $year.'/'.$file;
	  	print "<tr $bc[$var]>".'<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=facture_paiement&amp;file='.urlencode($relativepath).'">'.img_pdf().' '.$file.'</a></td>';
	  	print '<td align="right">'.filesize($tfile). ' '.$langs->trans("Bytes").'</td>';
	  	print '<td align="right">'.dolibarr_print_date(filemtime($tfile),"dayhour").'</td></tr>';
	  }
		}
		print '</table>';
	}
}
$db->close();

llxFooter('$Date$ - $Revision$');
?>
