<?php
/* Copyright (C) 2005      Matthieu Valleton	<mv@seeschloss.org>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin		<patrick.raguin@gmail.com>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@capnetworks.com>
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
 *       \file       htdocs/categories/viewcat.php
 *       \ingroup    category
 *       \brief      Page to show a category card
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';

$langs->load("categories");

$mesg = '';
$id=GETPOST('id','int');
$ref=GETPOST('ref');
$type=GETPOST('type');
$action=GETPOST('action');
$confirm=GETPOST('confirm');
$removeelem = GETPOST('removeelem','int');

if ($id == "")
{
	dol_print_error('','Missing parameter id');
	exit();
}

// Security check
$result = restrictedArea($user, 'categorie', $id, '&category');

$object = new Categorie($db);
$result=$object->fetch($id);
if ($result <= 0)
{
	dol_print_error($db,$object->error);
	exit;
}

$type=$object->type;


/*
 *	Actions
 */

// Remove element from category
if ($id > 0 && $removeelem > 0)
{
	if ($type==0 && ($user->rights->produit->creer || $user->rights->service->creer))
	{
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		$tmpobject = new Product($db);
		$result = $tmpobject->fetch($removeelem);
		$elementtype = 'product';
	}
	if ($type==1 && $user->rights->societe->creer)
	{
		$tmpobject = new Societe($db);
		$result = $tmpobject->fetch($removeelem);
		$elementtype = 'fournisseur';
	}
	if ($type==2 && $user->rights->societe->creer)
	{
		$tmpobject = new Societe($db);
		$result = $tmpobject->fetch($removeelem);
		$elementtype = 'societe';
	}
	if ($type == 3 && $user->rights->adherent->creer)
	{
		require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
		$tmpobject = new Adherent($db);
		$result = $tmpobject->fetch($removeelem);
		$elementtype = 'member';
	}
	
	$result=$object->del_type($tmpobject,$elementtype);
	if ($result < 0) dol_print_error('',$object->error);
}

if ($user->rights->categorie->supprimer && $action == 'confirm_delete' && $confirm == 'yes')
{
	if ($object->delete($user) >= 0)
	{
		header("Location: ".DOL_URL_ROOT.'/categories/index.php?type='.$type);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$object->error.'</div>';
	}
}



/*
 * View
 */

$form = new Form($db);

llxHeader("","",$langs->trans("Categories"));

dol_htmloutput_mesg($mesg);

if ($type == 0) $title=$langs->trans("ProductsCategoryShort");
elseif ($type == 1) $title=$langs->trans("SuppliersCategoryShort");
elseif ($type == 2) $title=$langs->trans("CustomersCategoryShort");
elseif ($type == 3) $title=$langs->trans("MembersCategoryShort");
else $title=$langs->trans("Category");

$head = categories_prepare_head($object,$type);
dol_fiche_head($head, 'card', $title, 0, 'category');


/*
 * Confirmation suppression
 */
if ($action == 'delete')
{
	$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;type='.$type,$langs->trans('DeleteCategory'),$langs->trans('ConfirmDeleteCategory'),'confirm_delete');
	if ($ret == 'html') print '<br>';
}

print '<table border="0" width="100%" class="border">';

// Path of category
print '<tr><td width="20%" class="notopnoleft">';
$ways = $object->print_all_ways();
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
print nl2br($object->description);
print '</td></tr>';

print '</table>';

print '</div>';


/*
 * Boutons actions
 */
print "<div class='tabsAction'>\n";

if ($user->rights->categorie->creer)
{
	$socid = ($object->socid ? "&amp;socid=".$object->socid : "");
	print "<a class='butAction' href='edit.php?id=".$object->id.$socid."&amp;type=".$type."'>".$langs->trans("Modify")."</a>";
}

if ($user->rights->categorie->supprimer)
{
	print "<a class='butActionDelete' href='".DOL_URL_ROOT."/categories/viewcat.php?action=delete&amp;id=".$object->id."&amp;type=".$type."'>".$langs->trans("Delete")."</a>";
}

print "</div>";




$cats = $object->get_filles();
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
		print "<a href='".DOL_URL_ROOT."/categories/fiche.php?action=create&amp;catorigin=".$object->id."&amp;socid=".$object->socid."&amp;type=".$type."&amp;urlfrom=".urlencode($_SERVER["PHP_SELF"].'?id='.$object->id.'&type='.$type)."'>";
		print img_picto($langs->trans("Create"),'filenew');
		print "</a>";
	}
	print "</td>";
	print "</tr>\n";
	if (count($cats) > 0)
	{
		$var=true;
		foreach ($cats as $cat)
		{
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
		print "<tr ".$bc[false].'><td colspan="3">'.$langs->trans("NoSubCat")."</td></tr>";
	}
	print "</table>\n";
}

// List of products
if ($object->type == 0)
{

	$prods = $object->get_type("product","Product");
	if ($prods < 0)
	{
		dol_print_error();
	}
	else
	{
		print "<br>";
		print "<table class='noborder' width='100%'>\n";
		print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("ProductsAndServices")."</td></tr>\n";

		if (count($prods) > 0)
		{
			$var=true;
			foreach ($prods as $prod)
			{
				$var=!$var;
				print "\t<tr ".$bc[$var].">\n";
				print '<td class="nowrap" valign="top">';
				print $prod->getNomUrl(1,'category');
				print "</td>\n";
				print '<td valign="top">'.$prod->libelle."</td>\n";
				// Link to delete from category
				print '<td align="right">';
				$typeid=$object->type;
				$permission=0;
				if ($typeid == 0) $permission=($user->rights->produit->creer || $user->rights->service->creer);
				if ($typeid == 1) $permission=$user->rights->societe->creer;
				if ($typeid == 2) $permission=$user->rights->societe->creer;
				if ($typeid == 3) $permission=$user->rights->adherent->creer;
				if ($permission)
				{
					print "<a href= '".$_SERVER['PHP_SELF']."?".(empty($socid)?'id':'socid')."=".$object->id."&amp;type=".$typeid."&amp;removeelem=".$prod->id."'>";
					print img_delete($langs->trans("DeleteFromCat")).' ';
					print $langs->trans("DeleteFromCat")."</a>";
				}
				print '</td>';
				print "</tr>\n";
			}
		}
		else
		{
			print "<tr ".$bc[false].'><td colspan="2">'.$langs->trans("ThisCategoryHasNoProduct")."</td></tr>";
		}
		print "</table>\n";
	}
}

if ($object->type == 1)
{
	$socs = $object->get_type("societe","Fournisseur","fournisseur");
	if ($socs < 0)
	{
		dol_print_error();
	}
	else
	{
		print "<br>";
		print '<table class="noborder" width="100%">'."\n";
		print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Suppliers")."</td></tr>\n";

		if (count($socs) > 0)
		{
			$var=true;
			foreach ($socs as $soc)
			{
				$var=!$var;
				print "\t<tr ".$bc[$var].">\n";

				print '<td class="nowrap" valign="top">';
				print $soc->getNomUrl(1,'category_supplier');
				print "</td>\n";
				// Link to delete from category
				print '<td align="right">';
				$typeid=$object->type;
				$permission=0;
				if ($typeid == 0) $permission=($user->rights->produit->creer || $user->rights->service->creer);
				if ($typeid == 1) $permission=$user->rights->societe->creer;
				if ($typeid == 2) $permission=$user->rights->societe->creer;
				if ($typeid == 3) $permission=$user->rights->adherent->creer;
				if ($permission)
				{
					print "<a href= '".$_SERVER['PHP_SELF']."?".(empty($socid)?'id':'socid')."=".$object->id."&amp;type=".$typeid."&amp;removeelem=".$soc->id."'>";
					print img_delete($langs->trans("DeleteFromCat")).' ';
					print $langs->trans("DeleteFromCat")."</a>";
				}
				print '</td>';
				
				print "</tr>\n";
			}
		}
		else
		{
			print "<tr ".$bc[false]."><td>".$langs->trans("ThisCategoryHasNoSupplier")."</td></tr>";
		}
		print "</table>\n";
	}
}

if($object->type == 2)
{
	$socs = $object->get_type("societe","Societe");
	if ($socs < 0)
	{
		dol_print_error();
	}
	else
	{
		print "<br>";
		print '<table class="noborder" width="100%">'."\n";
		print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Customers")."</td></tr>\n";

		if (count($socs) > 0)
		{
			$i = 0;
			$var=true;
			foreach ($socs as $key => $soc)
			{
				$i++;
				$var=!$var;
				print "\t<tr ".$bc[$var].">\n";
				print '<td class="nowrap" valign="top">';
				print $soc->getNomUrl(1,'category');
				print "</td>\n";
				// Link to delete from category
				print '<td align="right">';
				$typeid=$object->type;
				$permission=0;
				if ($typeid == 0) $permission=($user->rights->produit->creer || $user->rights->service->creer);
				if ($typeid == 1) $permission=$user->rights->societe->creer;
				if ($typeid == 2) $permission=$user->rights->societe->creer;
				if ($typeid == 3) $permission=$user->rights->adherent->creer;
				if ($permission)
				{
					print "<a href= '".$_SERVER['PHP_SELF']."?".(empty($socid)?'id':'socid')."=".$object->id."&amp;type=".$typeid."&amp;removeelem=".$soc->id."'>";
					print img_delete($langs->trans("DeleteFromCat")).' ';
					print $langs->trans("DeleteFromCat")."</a>";
				}
				print '</td>';
				print "</tr>\n";
			}
		}
		else
		{
			print "<tr ".$bc[false]."><td>".$langs->trans("ThisCategoryHasNoCustomer")."</td></tr>";
		}
		print "</table>\n";
	}
}

// List of members
if ($object->type == 3)
{
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

	$prods = $object->get_type("member","Adherent","","adherent");
	if ($prods < 0)
	{
		dol_print_error($db,$object->error);
	}
	else
	{
		print "<br>";
		print "<table class='noborder' width='100%'>\n";
		print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("Member")."</td></tr>\n";

		if (count($prods) > 0)
		{
			$var=true;
			foreach ($prods as $key => $member)
			{
				$var=!$var;
				print "\t<tr ".$bc[$var].">\n";
				print '<td class="nowrap" valign="top">';
				$member->ref=$member->login;
				print $member->getNomUrl(1,0,'category');
				print "</td>\n";
				print '<td valign="top">'.$member->lastname."</td>\n";
				print '<td valign="top">'.$member->firstname."</td>\n";
				// Link to delete from category
				print '<td align="right">';
				$typeid=$object->type;
				$permission=0;
				if ($typeid == 0) $permission=($user->rights->produit->creer || $user->rights->service->creer);
				if ($typeid == 1) $permission=$user->rights->societe->creer;
				if ($typeid == 2) $permission=$user->rights->societe->creer;
				if ($typeid == 3) $permission=$user->rights->adherent->creer;
				if ($permission)
				{
					print "<a href= '".$_SERVER['PHP_SELF']."?".(empty($socid)?'id':'socid')."=".$object->id."&amp;type=".$typeid."&amp;removeelem=".$member->id."'>";
					print img_delete($langs->trans("DeleteFromCat")).' ';
					print $langs->trans("DeleteFromCat")."</a>";
				}
				print "</tr>\n";
			}
		}
		else
		{
			print "<tr ".$bc[false].'><td colspan="3">'.$langs->trans("ThisCategoryHasNoMember")."</td></tr>";
		}
		print "</table>\n";
	}
}


llxFooter();
$db->close();
?>