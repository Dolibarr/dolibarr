<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Andre Cianfarani  <acianfa@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *	\file       htdocs/comm/multiprix.php
 *	\ingroup    societe
 *	\brief      Onglet choix du niveau de prix
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

$langs->load("orders");
$langs->load("companies");

$_socid = $_GET["id"];
// Security check
if ($user->societe_id > 0)
{
	$_socid = $user->societe_id;
}


/*
 * Actions
 */

if ($_POST["action"] == 'setpricelevel')
{
	$soc = New Societe($db);
	$soc->fetch($_GET["id"]);
	$soc->set_price_level($_POST["price_level"],$user);

	header("Location: multiprix.php?id=".$_GET["id"]);
	exit;
}


/*
 * View
 */

llxHeader();

$userstatic=new User($db);

if ($_socid > 0)
{
	// On recupere les donnees societes par l'objet
	$objsoc = new Societe($db);
	$objsoc->id=$_socid;
	$objsoc->fetch($_socid,$to);

	if ($errmesg)
	{
		print '<div class="error">'.$errmesg.'</div><br>';
	}


	/*
	 * Affichage onglets
	 */

	$head = societe_prepare_head($objsoc);

	$tabchoice='';
	if ($objsoc->client == 1) $tabchoice='customer';
	if ($objsoc->client == 2) $tabchoice='prospect';

	print '<form method="POST" action="multiprix.php?id='.$objsoc->id.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="setpricelevel">';
	
	dol_fiche_head($head, $tabchoice, $langs->trans("ThirdParty"), 0, 'company');

	print '<table width="100%" border="0">';
	print '<tr><td class="tdtop">';
	print '<table class="border" width="100%">';

	print '<tr><td colspan="2" width="25%">';
	print $langs->trans("PriceLevel").'</td><td colspan="2">'.$objsoc->price_level."</td></tr>";

	print '<tr><td colspan="2">';
	print $langs->trans("NewValue").'</td><td colspan="2">';
	print '<select name="price_level" class="flat">';
	for($i=1;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
	{
		print '<option value="'.$i.'"' ;
		if($i == $objsoc->price_level)
		print 'selected';
		print '>'.$i;
		$keyforlabel='PRODUIT_MULTIPRICES_LABEL'.$i;
		if (! empty($conf->global->$keyforlabel)) print ' - '.$langs->trans($conf->global->$keyforlabel);
		print '</option>';
	}
	print '</select>';
	print '</td></tr>';

	print "</table>";

	print "</td>\n";


	print "</td></tr>";
	print "</table>";

	dol_fiche_end();
	
	print '<div align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';

	print "</form>";

	
	print '<br><br>';


	/*
	 * List historic of multiprices
	 */
	$sql  = "SELECT rc.rowid,rc.price_level, rc.datec as dc, u.rowid as uid, u.login";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe_prices as rc, ".MAIN_DB_PREFIX."user as u";
	$sql .= " WHERE rc.fk_soc =". $objsoc->id;
	$sql .= " AND u.rowid = rc.fk_user_author";
	$sql .= " ORDER BY rc.datec DESC";

	$resql=$db->query($sql);
	if ($resql)
	{
		print '<table class="noborder" width="100%">';
		$tag = !$tag;
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("PriceLevel").'</td>';
		print '<td align="right">'.$langs->trans("User").'</td>';
		print '</tr>';
		$i = 0 ;
		$num = $db->num_rows($resql);

		while ($i < $num )
		{
			$obj = $db->fetch_object($resql);
			$tag = !$tag;
			print '<tr '.$bc[$tag].'>';
			print '<td>'.dol_print_date($db->jdate($obj->dc),"dayhour").'</td>';
			print '<td>'.$obj->price_level.' </td>';
			$userstatic->id=$obj->uid;
			$userstatic->lastname=$obj->login;
			print '<td align="right">'.$userstatic->getNomUrl(1).'</td>';
			print '</tr>';
			$i++;
		}
		$db->free($resql);
		print "</table>";
	}
	else
	{
		dol_print_error($db);
	}

}

llxFooter();
$db->close();
