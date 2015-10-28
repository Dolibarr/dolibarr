<?php
/* Copyright (C) 2005       Matthieu Valleton	<mv@seeschloss.org>
 * Copyright (C) 2006-2010  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007       Patrick Raguin		<patrick.raguin@gmail.com>
 * Copyright (C) 2005-2012  Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';


$langs->load("categories");

$id=GETPOST('id','int');
$ref=GETPOST('ref');
$type=GETPOST('type');
$action=GETPOST('action');
$confirm=GETPOST('confirm');
$removeelem = GETPOST('removeelem','int');
$elemid=GETPOST('elemid');

if ($id == "")
{
	dol_print_error('','Missing parameter id');
	exit();
}

// Security check
$result = restrictedArea($user, 'categorie', $id, '&category');

$object = new Categorie($db);
$result=$object->fetch($id);
$object->fetch_optionals($id,$extralabels);
if ($result <= 0)
{
	dol_print_error($db,$object->error);
	exit;
}

$type=$object->type;

$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('categorycard'));

/*
 *	Actions
 */

// Remove element from category
if ($id > 0 && $removeelem > 0)
{
	if ($type == Categorie::TYPE_PRODUCT && ($user->rights->produit->creer || $user->rights->service->creer))
	{
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		$tmpobject = new Product($db);
		$result = $tmpobject->fetch($removeelem);
		$elementtype = 'product';
	}
	else if ($type == Categorie::TYPE_SUPPLIER && $user->rights->societe->creer)
	{
		$tmpobject = new Societe($db);
		$result = $tmpobject->fetch($removeelem);
		$elementtype = 'fournisseur';
	}
	else if ($type == Categorie::TYPE_CUSTOMER && $user->rights->societe->creer)
	{
		$tmpobject = new Societe($db);
		$result = $tmpobject->fetch($removeelem);
		$elementtype = 'societe';
	}
	else if ($type == Categorie::TYPE_MEMBER && $user->rights->adherent->creer)
	{
		require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
		$tmpobject = new Adherent($db);
		$result = $tmpobject->fetch($removeelem);
		$elementtype = 'member';
	}
	else if ($type == Categorie::TYPE_CONTACT && $user->rights->societe->creer) {

		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
		$tmpobject = new Contact($db);
		$result = $tmpobject->fetch($removeelem);
		$elementtype = 'contact';
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
		setEventMessage($object->error, 'errors');
	}
}

if ($type == Categorie::TYPE_PRODUCT && $elemid && $action == 'addintocategory' && ($user->rights->produit->creer || $user->rights->service->creer))
{
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	$newobject = new Product($db);
	$result = $newobject->fetch($elemid);
	$elementtype = 'product';

	// TODO Add into categ
	$result=$object->add_type($newobject,$elementtype);
	if ($result >= 0)
	{
		setEventMessage($langs->trans("WasAddedSuccessfully",$newobject->ref));
	}
	else
	{
		if ($cat->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			setEventMessage($langs->trans("ObjectAlreadyLinkedToCategory"),'warnings');
		}
		else
		{
			setEventMessages($object->error,$object->errors,'errors');
		}
	}

}



/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

llxHeader("","",$langs->trans("Categories"));

if ($type == Categorie::TYPE_PRODUCT)       $title=$langs->trans("ProductsCategoryShort");
elseif ($type == Categorie::TYPE_SUPPLIER)  $title=$langs->trans("SuppliersCategoryShort");
elseif ($type == Categorie::TYPE_CUSTOMER)  $title=$langs->trans("CustomersCategoryShort");
elseif ($type == Categorie::TYPE_MEMBER)    $title=$langs->trans("MembersCategoryShort");
elseif ($type == Categorie::TYPE_CONTACT)   $title=$langs->trans("ContactCategoriesShort");
else                                        $title=$langs->trans("Category");

$head = categories_prepare_head($object,$type);

dol_fiche_head($head, 'card', $title, 0, 'category');


/*
 * Confirmation suppression
 */

if ($action == 'delete')
{
	print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;type='.$type,$langs->trans('DeleteCategory'),$langs->trans('ConfirmDeleteCategory'),'confirm_delete');
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
print '<tr><td class="notopnoleft">';
print $langs->trans("Description").'</td><td>';
print dol_htmlentitiesbr($object->description);
print '</td></tr>';

// Color
print '<tr><td class="notopnoleft">';
print $langs->trans("Color").'</td><td>';
print $formother->showColor($object->color);
print '</td></tr>';

$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
if (empty($reshook) && ! empty($extrafields->attribute_label))
{
	print $object->showOptionals($extrafields);
}

print '</table>';

dol_fiche_end();


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
	dol_print_error($db, $cats->error, $cats->errors);
}
else
{
	print "<br>";
	print "<table class='noborder' width='100%'>\n";
	print "<tr class='liste_titre'><td colspan='2'>".$langs->trans("SubCats").'</td><td align="right">';
	if ($user->rights->categorie->creer)
	{
		print "<a href='".DOL_URL_ROOT."/categories/card.php?action=create&amp;catorigin=".$object->id."&amp;socid=".$object->socid."&amp;type=".$type."&amp;urlfrom=".urlencode($_SERVER["PHP_SELF"].'?id='.$object->id.'&type='.$type)."'>";
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
			print "\t\t".'<td class="nowrap">';
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

// List of products or services (type is type of category)
if ($object->type == Categorie::TYPE_PRODUCT)
{
	$prods = $object->getObjectsInCateg("product");
	if ($prods < 0)
	{
		dol_print_error($db, $prods->error, $prods->errors);
	}
	else
	{
		$showclassifyform=1; $typeid=Categorie::TYPE_PRODUCT;

		// Form to add record into a category
		if ($showclassifyform)
		{
			print '<br>';
			print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="typeid" value="'.$typeid.'">';
			print '<input type="hidden" name="type" value="'.$typeid.'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="action" value="addintocategory">';
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre"><td width="40%">';
			print $langs->trans("AddProductServiceIntoCategory").' &nbsp;';
			print $form->select_produits('','elemid','',0,0,-1,2,'',1);
			print '</td><td>';
			print '<input type="submit" class="button" value="'.$langs->trans("ClassifyInCategory").'"></td>';
			print '</tr>';
			print '</table>';
			print '</form>';
		}

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
				print '<td valign="top">'.$prod->label."</td>\n";
				// Link to delete from category
				print '<td align="right">';
				$typeid=$object->type;
				$permission=0;
				if ($typeid == Categorie::TYPE_PRODUCT)     $permission=($user->rights->produit->creer || $user->rights->service->creer);
				if ($typeid == Categorie::TYPE_SUPPLIER)    $permission=$user->rights->societe->creer;
				if ($typeid == Categorie::TYPE_CUSTOMER)    $permission=$user->rights->societe->creer;
				if ($typeid == Categorie::TYPE_MEMBER)      $permission=$user->rights->adherent->creer;
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

if ($object->type == Categorie::TYPE_SUPPLIER)
{
	$socs = $object->getObjectsInCateg("supplier");
	if ($socs < 0)
	{
		dol_print_error($db, $socs->error, $socs->errors);
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
				if ($typeid == Categorie::TYPE_PRODUCT)     $permission=($user->rights->produit->creer || $user->rights->service->creer);
				if ($typeid == Categorie::TYPE_SUPPLIER)    $permission=$user->rights->societe->creer;
				if ($typeid == Categorie::TYPE_CUSTOMER)    $permission=$user->rights->societe->creer;
				if ($typeid == Categorie::TYPE_MEMBER)      $permission=$user->rights->adherent->creer;
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

if($object->type == Categorie::TYPE_CUSTOMER)
{
	$socs = $object->getObjectsInCateg("customer");
	if ($socs < 0)
	{
		dol_print_error($db, $socs->error, $socs->errors);
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
				if ($user->societe_id > 0 && $soc->id != $user->societe_id)	continue; 	// External user always see only themself

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
				if ($typeid == Categorie::TYPE_PRODUCT)     $permission=($user->rights->produit->creer || $user->rights->service->creer);
				if ($typeid == Categorie::TYPE_SUPPLIER)    $permission=$user->rights->societe->creer;
				if ($typeid == Categorie::TYPE_CUSTOMER)    $permission=$user->rights->societe->creer;
				if ($typeid == Categorie::TYPE_MEMBER)      $permission=$user->rights->adherent->creer;
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
if ($object->type == Categorie::TYPE_MEMBER)
{
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

	$prods = $object->getObjectsInCateg("member");
	if ($prods < 0)
	{
		dol_print_error($db, $prods->error, $prods->errors);
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
				if ($typeid == Categorie::TYPE_PRODUCT)     $permission=($user->rights->produit->creer || $user->rights->service->creer);
				if ($typeid == Categorie::TYPE_SUPPLIER)    $permission=$user->rights->societe->creer;
				if ($typeid == Categorie::TYPE_CUSTOMER)    $permission=$user->rights->societe->creer;
				if ($typeid == Categorie::TYPE_MEMBER)      $permission=$user->rights->adherent->creer;
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

//Categorie contact
if($object->type == Categorie::TYPE_CONTACT)
{
	$contacts = $object->getObjectsInCateg("contact");
	if ($contacts < 0)
	{
		dol_print_error($db, $contacts->error, $contacts->errors);
	}
	else
	{
		print "<br>";
		print '<table class="noborder" width="100%">'."\n";
		print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Contact")."</td></tr>\n";

		if (count($contacts) > 0)
		{
			$i = 0;
			$var=true;
			foreach ($contacts as $key => $contact)
			{
				$i++;
				$var=!$var;
				print "\t<tr ".$bc[$var].">\n";
				print '<td class="nowrap" valign="top">';
				print $contact->getNomUrl(1,'category');
				print "</td>\n";
				// Link to delete from category
				print '<td align="right">';
				$typeid=$object->type;
				$permission=0;
				if ($typeid == Categorie::TYPE_PRODUCT)     $permission=($user->rights->produit->creer || $user->rights->service->creer);
				if ($typeid == Categorie::TYPE_SUPPLIER)    $permission=$user->rights->societe->creer;
				if ($typeid == Categorie::TYPE_CUSTOMER)    $permission=$user->rights->societe->creer;
				if ($typeid == Categorie::TYPE_MEMBER)      $permission=$user->rights->adherent->creer;
				if ($typeid == Categorie::TYPE_CONTACT)     $permission=$user->rights->societe->creer;
				if ($permission)
				{
					print "<a href= '".$_SERVER['PHP_SELF']."?".(empty($socid)?'id':'socid')."=".$object->id."&amp;type=".$typeid."&amp;removeelem=".$contact->id."'>";
					print img_delete($langs->trans("DeleteFromCat")).' ';
					print $langs->trans("DeleteFromCat")."</a>";
				}
				print '</td>';
				print "</tr>\n";
			}
		}
		else
		{
			print "<tr ".$bc[false]."><td>".$langs->trans("ThisCategoryHasNoContact")."</td></tr>";
		}
		print "</table>\n";
	}
}


llxFooter();

$db->close();
