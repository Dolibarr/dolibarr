<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/compta/dons/index.php
		\ingroup    don
		\brief      Page accueil espace don
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT ."/product/droitpret/droitpret.class.php");
require_once(DOL_DOCUMENT_ROOT ."/product/droitpret/modules_droitpret.php");


global $conf;

$html = new Form($db);

if($_GET['action'] && $_GET['action'] == 'create')
{
	$dated = mktime($_POST['dhour'],$_POST['dmin'],0,$_POST['dmonth'],$_POST['dday'],$_POST['dyear']);
	$datef = mktime($_POST['fhour'],$_POST['fmin'],0,$_POST['fmonth'],$_POST['fday'],$_POST['fyear']);

	if($dated < $datef)
	{
		$droitpret = new DroitPret($db,$dated,$datef);
		$droitpret->CreateNewRapport();	
		$mesg = $droitpret->EnvoiMail();	
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorDate").'</div>';
	}



	
	
}



/*
 * Affichage
 */
 
llxHeader();

print_fiche_titre($langs->trans("DroitPretArea"));

if ($mesg) print "$mesg\n";




$sql = "SELECT MAX(date_fin) as lastRapport FROM ".MAIN_DB_PREFIX."droitpret_rapport";
$result = $db->query($sql);
$obj = $db->fetch_object($result);
$lastRapport = $obj->lastRapport;


print '<form action="index.php?action=create" method="post">';


print '<table class="border" width="100%">';
print '<tr><td>Date de début de période</td><td>';


$html->select_date($lastRapport,'d',1,1,'',"dated");
print '</td></tr>';
print '<tr><td>Date de fin de période</td><td>';
$html->select_date('','f',1,1,'',"datef");
print '</td></tr>';
print '</table>';

print '<br><center><input type="submit" class="button" value="Générer"></center>';

print '</form>';


print '<table width="100%" class="noborder">';
print '<tr class="liste_titre"><td>Document</td>';
print '<td>Date du rapport</td>';
print '<td>Début période</td>';
print '<td>Fin période</td>';
print '<td>Nb factures</td>';
print '</tr>';

$sql ="SELECT rowid, date_envoie, date_debut, date_fin, fichier, nbfact";
$sql.=" FROM ".MAIN_DB_PREFIX."droitpret_rapport";
$sql.=" ORDER BY rowid";

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$var = true;
	while ($i < $num)
	{
		$var = !$var;
		$obj = $db->fetch_object($resql);
		print '<tr '.$bc[$var].'><td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=droitpret&amp;file='.$obj->fichier.'">'.$obj->fichier.'</a></td>';
		print '<td>'.$obj->date_envoie.'</td>';
		print '<td>'.$obj->date_debut.'</td>';
		print '<td>'.$obj->date_fin.'</td>';
		print '<td>'.$obj->nbfact.'</td></tr>';
		$i++;
	}
}



print '</table>';

?>
