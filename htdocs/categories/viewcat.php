<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin	  	  <patrick.raguin@gmail.com>
 * Copyright (C) 2005-2009 Regis Houssin	  	  <regis@dolibarr.fr>
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
 *       \file       htdocs/categories/viewcat.php
 *       \ingroup    category
 *       \brief      Page to show a category card
 *       \version    $Revision$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");

// Security check
if (! $user->rights->categorie->lire)
{
	accessforbidden();
}

$mesg = '';

if ($_REQUEST['id'] == "")
{
	dol_print_error('','Missing parameter id');
	exit();
}

$c = new Categorie($db);
$result=$c->fetch($_REQUEST['id']);
if ($result <= 0)
{
	dol_print_error($db,$c->error);
	exit;
}

$type=$c->type;


/*
 *	Actions
 */

if ($user->rights->categorie->supprimer && $_POST["action"] == 'confirm_delete' && $_POST['confirm'] == 'yes')
{
	if ($c->remove() >= 0)
	{
		header("Location: ".DOL_URL_ROOT.'/categories/index.php?type='.$type);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$c->error.'</div>';
	}
}



/*
 * View
 */

$html = new Form($db);

llxHeader ("","",$langs->trans("Categories"));


if ($mesg) print $mesg.'<br>';


$h = 0;
$head = array();

$head[$h][0] = DOL_URL_ROOT.'/categories/viewcat.php?id='.$c->id.'&amp;type='.$type;
$head[$h][1] = $langs->trans("Card");
$head[$h][2] = 'card';
$h++;

$head[$h][0] = DOL_URL_ROOT.'/categories/photos.php?id='.$c->id.'&amp;type='.$type;
$head[$h][1] = $langs->trans("Photos");
$head[$h][2] = 'photos';
$h++;

if ($type == 0) $title=$langs->trans("ProductsCategoryShort");
elseif ($type == 1) $title=$langs->trans("SuppliersCategoryShort");
elseif ($type == 2) $title=$langs->trans("CustomersCategoryShort");
elseif ($type == 3) $title=$langs->trans("MembersCategoryShort");
else $title=$langs->trans("Category");

dol_fiche_head($head, 'card', $title, 0, 'category');


/*
 * Confirmation suppression
 */
if ($_GET['action'] == 'delete')
{
	$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$c->id.'&amp;type='.$type,$langs->trans('DeleteCategory'),$langs->trans('ConfirmDeleteCategory'),'confirm_delete');
	if ($ret == 'html') print '<br>';
}

print '<table border="0" width="100%" class="border">';

// Path of category
print '<tr><td width="20%" class="notopnoleft">';
$ways = $c->print_all_ways ();
print $langs->trans("Ref").'</td><td>';
print '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
foreach ($ways as $way)
{
	print $way."<br>\n";
}
print '</td></tr>';

// Description
print '<tr><td width="20%" class="notopnoleft">';
print $langs->trans("Description").'</td><td>';
print nl2br($c->description);
print '</td></tr>';

print '</table>';

print '</div>';


/*
 * Boutons actions
 */
print "<div class='tabsAction'>\n";

if ($user->rights->categorie->creer)
{
	$socid = ($c->socid ? "&amp;socid=".$c->socid : "");
	print "<a class='butAction' href='edit.php?id=".$c->id.$socid."&amp;type=".$type."'>".$langs->trans("Modify")."</a>";
}

if ($user->rights->categorie->supprimer)
{
	print "<a class='butActionDelete' href='".DOL_URL_ROOT."/categories/viewcat.php?action=delete&amp;id=".$c->id."&amp;type=".$type."'>".$langs->trans("Delete")."</a>";
}

print "</div>";





$cats = $c->get_filles ();
if ($cats < 0)
{
	dol_print_error();
}
else
{
	print "<br>";
	print "<table class='noborder' width='100%'>\n";
	print "<tr class='liste_titre'><td colspan='2'>".$langs->trans("SubCats").'</td><td align="right">';
	if ($user->rights->categorie->creer)
	{
		print "<a href='".DOL_URL_ROOT."/categories/fiche.php?action=create&amp;catorigin=".$c->id."&amp;socid=".$c->socid."&amp;type=".$type."&amp;urlfrom=".urlencode($_SERVER["PHP_SELF"].'?id='.$c->id.'&type='.$type)."'>";
		print img_picto($langs->trans("Create"),'filenew');
		print "</a>";
	}
	print "</td>";
	print "</tr>\n";
	if (sizeof ($cats) > 0)
	{
		$var=true;
		foreach ($cats as $cat)
		{
			$i++;
			$var=!$var;
			print "\t<tr ".$bc[$var].">\n";
			print "\t\t<td nowrap=\"nowrap\">";
			print "<a href='viewcat.php?id=".$cat->id."&amp;type=".$type."'>".$cat->label."</a>";
			print "</td>\n";
			print "\t\t".'<td colspan="2">'.$cat->description."</td>\n";

			/*
			if ($cat->visible == 1)
			{
				print "\t\t<td>".$langs->trans("ContentsVisibleByAllShort")."</td>\n";
			}
			else
			{
				print "\t\t<td>".$langs->trans("ContentsNotVisibleByAllShort")."</td>\n";
			}
			*/

			print "\t</tr>\n";
		}
	}
	else
	{
		print "<tr><td>".$langs->trans("NoSubCat")."</td></tr>";
	}
	print "</table>\n";
}

// List of products
if ($c->type == 0)
{

	$prods = $c->get_type("product","Product");
	if ($prods < 0)
	{
		dol_print_error();
	}
	else
	{
		print "<br>";
		print "<table class='noborder' width='100%'>\n";
		print "<tr class='liste_titre'><td colspan='2'>".$langs->trans("ProductsAndServices")."</td></tr>\n";

		if (sizeof ($prods) > 0)
		{
			$i = 0;
			$var=true;
			foreach ($prods as $prod)
			{
				$i++;
				$var=!$var;
				print "\t<tr ".$bc[$var].">\n";
				print '<td nowrap="nowrap" valign="top">';
				if ($prod->type == 1) print img_object($langs->trans("ShowService"),"service");
				else print img_object($langs->trans("ShowProduct"),"product");
				print " <a href='".DOL_URL_ROOT."/product/fiche.php?id=".$prod->id."'>".$prod->ref."</a></td>\n";
				print '<td valign="top">'.$prod->libelle."</td>\n";
				print "</tr>\n";
			}
		}
		else
		{
			print "<tr><td>".$langs->trans("ThisCategoryHasNoProduct")."</td></tr>";
		}
		print "</table>\n";
	}
}

if ($c->type == 1)
{
	$socs = $c->get_type("societe","Fournisseur","fournisseur");
	if ($socs < 0)
	{
		dol_print_error();
	}
	else
	{
		print "<br>";
		print "<table class='noborder' width='100%'>\n";
		print "<tr class='liste_titre'><td>".$langs->trans("Suppliers")."</td></tr>\n";

		if (sizeof ($socs) > 0)
		{
			$i = 0;
			$var=true;
			foreach ($socs as $soc)
			{
				$i++;
				$var=!$var;
				print "\t<tr ".$bc[$var].">\n";

				print '<td nowrap="nowrap" valign="top">';
				print $soc->getNomUrl(1);
				print "</td>\n";

				print "</tr>\n";
			}
		}
		else
		{
			print "<tr><td>".$langs->trans ("ThisCategoryHasNoSupplier")."</td></tr>";
		}
		print "</table>\n";
	}
}

if($c->type == 2)
{
	$socs = $c->get_type("societe","Societe");
	if ($socs < 0)
	{
		dol_print_error();
	}
	else
	{
		print "<br>";
		print "<table class='noborder' width='100%'>\n";
		print "<tr class='liste_titre'><td>".$langs->trans("Customers")."</td></tr>\n";

		if (sizeof ($socs) > 0)
		{
			$i = 0;
			$var=true;
			foreach ($socs as $key => $soc)
			{
				$i++;
				$var=!$var;
				print "\t<tr ".$bc[$var].">\n";
				print '<td nowrap="nowrap" valign="top">';
				print $soc->getNomUrl(1);
				print "</td>\n";

				print "</tr>\n";
			}
		}
		else
		{
			print "<tr><td>".$langs->trans("ThisCategoryHasNoCustomer")."</td></tr>";
		}
		print "</table>\n";
	}
}

// List of members
if ($c->type == 3)
{
	require_once(DOL_DOCUMENT_ROOT."/adherents/class/Adherent.class.php");

	$prods = $c->get_type("member","Adherent");
	if ($prods < 0)
	{
		dol_print_error($db,$c->error);
	}
	else
	{
		print "<br>";
		print "<table class='noborder' width='100%'>\n";
		print "<tr class='liste_titre'><td colspan='3'>".$langs->trans("Member")."</td></tr>\n";

		if (sizeof ($prods) > 0)
		{
			$i = 0;
			$var=true;
			foreach ($prods as $key => $member)
			{
				$i++;
				$var=!$var;
				print "\t<tr ".$bc[$var].">\n";
				print '<td nowrap="nowrap" valign="top">';
				print $member->getNomUrl(1);
				print "</td>\n";
				print '<td valign="top">'.$member->nom."</td>\n";
				print '<td valign="top">'.$member->prenom."</td>\n";
				print "</tr>\n";
			}
		}
		else
		{
			print "<tr><td>".$langs->trans("ThisCategoryHasNoMember")."</td></tr>";
		}
		print "</table>\n";
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>