<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
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
		\file       htdocs/categories/categorie.php
		\ingroup    category
		\brief      Page de l'onglet categories de produits
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");

$langs->load("categories");
$langs->load("companies");

$socid = isset($_GET["socid"])?$_GET["socid"]:'';

// Sécurité d'accès client et commerciaux
$socid = restrictedArea($user, 'societe', $socid);

$mesg = '';


/*
*	Actions
*/

//on veut supprimer une catégorie
if ($_REQUEST["removecat"] && $user->rights->societe->creer)
{
	$soc = new Societe($db);
	if ($_REQUEST["socid"])  $result = $soc->fetch($_REQUEST["socid"]);

	$cat = new Categorie($db,$_REQUEST["removecat"]);
	$result=$cat->del_type($soc,"societe");
}

//on veut ajouter une catégorie
if (isset($_REQUEST["catMere"]) && $_REQUEST["catMere"]>=0  && $user->rights->societe->creer)
{
	$soc = new Societe($db);
	if ($_REQUEST["socid"])  $result = $soc->fetch($_REQUEST["socid"]);

	$cat = new Categorie($db,$_REQUEST["catMere"]);
	$result=$cat->add_type($soc,"societe");
	if ($result >= 0)
	{
		$mesg='<div class="ok">'.$langs->trans("Added").'</div>';	
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("Error").' '.$cat->error.'</div>';	
	}
	
}



/*
* Creation de l'objet fournisseur correspondant à l'id
*/
if ($_GET["socid"] || $_GET["ref"])
{
	$soc = new Societe($db);
	if ($_GET["socid"]) $result = $soc->fetch($_GET["socid"]);
	
	llxHeader("","",$langs->trans("CardCompany".$soc->type));
}



$html = new Form($db);


/*
 * Fiche produit
 */
if ($_GET["socid"] || $_GET["ref"])
{
	/*
	* Affichage onglets
	*/
	$head = societe_prepare_head($soc);

	dolibarr_fiche_head($head, 'category', $soc->nom);


	print '<table class="border" width="100%">';

	print '<tr><td width="30%">'.$langs->trans("Name").'</td><td width="70%" colspan="3">';
	print $soc->nom;
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$soc->prefix_comm.'</td></tr>';

	if ($soc->client)
	{
		print '<tr><td>';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $soc->code_client;
		if ($soc->check_codeclient() <> 0) print ' '.$langs->trans("WrongCustomerCode");
		print '</td></tr>';
	}

	print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."</td></tr>";

	print '<tr><td>'.$langs->trans('Zip').'</td><td>'.$soc->cp."</td>";
	print '<td>'.$langs->trans('Town').'</td><td>'.$soc->ville."</td></tr>";
	if ($soc->pays) {
		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$soc->pays.'</td></tr>';
	}

	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dolibarr_print_phone($soc->tel,$soc->pays_code).'</td>';
	print '<td>'.$langs->trans('Fax').'</td><td>'.dolibarr_print_phone($soc->fax,$soc->pays_code).'</td></tr>';

	print '<tr><td>'.$langs->trans("Web")."</td><td colspan=\"3\"><a href=\"http://$soc->url\" target=\"_blank\">".$soc->url."</a>&nbsp;</td></tr>";

	// Assujeti à TVA ou pas
	print '<tr>';
	print '<td nowrap="nowrap">'.$langs->trans('VATIsUsed').'</td><td colspan="3">';
	print yn($soc->tva_assuj);
	print '</td>';
	print '</tr>';

	print '</table>';

	print '</div>';
	

	if ($mesg) print($mesg);

	
	/*
	* Barre d'actions
	*
	*/
	print '<div class="tabsAction">';
	if ($user->rights->categorie->creer)
	{
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/categories/fiche.php?action=create&amp;origin='.$soc->id.'&type=2">'.$langs->trans("NewCat").'</a>';
	}
	print '</div>';


	// Formulaire ajout dans une categorie
	if ($user->rights->societe->creer)
	{
		print '<br>';
		print '<form method="post" action="'.DOL_URL_ROOT.'/categories/categorie.php?socid='.$soc->id.'">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td>';
		print $langs->trans("ClassifyInCategory").' ';
		print $html->select_all_categories(2,$categorie->id_mere).' <input type="submit" class="button" value="'.$langs->trans("Classify").'"></td>';
		print '</tr>';
		print '</table>';
		print '</form>';
		print '<br/>';
	}


	$c = new Categorie($db);

	if ($_GET["socid"])
	{
		$cats = $c->containing($_REQUEST["socid"],"societe");
	}


	if (sizeof($cats) > 0)
	{
		print_fiche_titre($langs->trans("CompanyIsInCategories"));
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Categories").'</td></tr>';

		$var = true;
		foreach ($cats as $cat)
		{
			$ways = $cat->print_all_ways ();
			foreach ($ways as $way)
			{
				$var = ! $var;
				print "<tr ".$bc[$var].">";
				
				// Categorie
				print "<td>".$way."</td>";

				// Lien supprimer
				print '<td align="right">';
				if ($user->rights->societe->creer)
				{
					print "<a href= '".DOL_URL_ROOT."/categories/categorie.php?socid=".$soc->id."&amp;removecat=".$cat->id."'>";
					print img_delete($langs->trans("DeleteFromCat")).' ';
					print $langs->trans("DeleteFromCat")."</a>";
				}
				else
				{
					print '&nbsp;';
				}
				print "</td>";

				print "</tr>\n";
			}

		}
		print "</table><br/>\n";
	}
	else if($cats < 0)
	{
		print $langs->trans("ErrorUnknown");
	}

	else
	{
		print $langs->trans("CompanyHasNoCategory")."<br/>";
	}
	
}

$db->close();


llxFooter('$Date$ - $Revision$');
?>
