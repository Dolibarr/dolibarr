<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/categories/categorie.php
 *  \ingroup    category
 *  \brief      Page to show category tab
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("categories");
$langs->load("products");

$socid	= GETPOST('socid','int');
$id		= GETPOST('id','int');
$ref	= GETPOST('ref');
$type	= GETPOST('type');
$mesg	= GETPOST('mesg');

$removecat = GETPOST('removecat','int');
$parent=GETPOST('parent','int');

$dbtablename = '';


// For categories on third parties
if (! empty($socid)) $id = $socid;
if (! isset($type)) $type = 0;
if ($type == 1 || $type == 2) $socid = $id;

if ($id || $ref)
{
	if ($type == 0) {
		$elementtype = 'product';
		$objecttype = 'produit|service&categorie';
		$objectid = isset($id)?$id:(isset($ref)?$ref:'');
		$dbtablename = 'product';
		$fieldid = isset($ref)?'ref':'rowid';
	}
	elseif ($type == 1) {
		$elementtype = 'fournisseur';
		$objecttype = 'societe&categorie';
		$objectid = isset($id)?$id:(isset($socid)?$socid:'');
		$dbtablename = '&societe';
		$fieldid = 'rowid';
	}
	elseif ($type == 2) {
		$elementtype = 'societe';
		$objecttype = 'societe&categorie';
		$objectid = isset($id)?$id:(isset($socid)?$socid:'');
		$dbtablename = '&societe';
		$fieldid = 'rowid';
	}
	elseif ($type == 3) {
		$elementtype = 'member';
		$objecttype = 'adherent&categorie';
		$objectid = isset($id)?$id:(isset($ref)?$ref:'');
		$dbtablename = 'adherent';
		$fieldid = ! empty($ref)?'ref':'rowid';
	}
}

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user,$objecttype,$objectid,$dbtablename,'','',$fieldid);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('categorycard'));


/*
 *	Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
$error=$hookmanager->error; $errors=array_merge($errors, (array) $hookmanager->errors);

if (empty($reshook))
{
	// Remove element from category
	if ($removecat > 0)
	{
		if ($type==0 && ($user->rights->produit->creer || $user->rights->service->creer))
		{
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			$object = new Product($db);
			$result = $object->fetch($id, $ref);
			$elementtype = 'product';
		}
		if ($type==1 && $user->rights->societe->creer)
		{
			$object = new Societe($db);
			$result = $object->fetch($objectid);
			$elementtype = 'fournisseur';
		}
		if ($type==2 && $user->rights->societe->creer)
		{
			$object = new Societe($db);
			$result = $object->fetch($objectid);
			$elementtype = 'societe';
		}
		if ($type == 3 && $user->rights->adherent->creer)
		{
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
			$object = new Adherent($db);
			$result = $object->fetch($objectid);
			$elementtype = 'member';
		}
		$cat = new Categorie($db);
		$result=$cat->fetch($removecat);

		$result=$cat->del_type($object,$elementtype);
	}

	// Add object into a category
	if ($parent > 0)
	{
		if ($type==0 && ($user->rights->produit->creer || $user->rights->service->creer))
		{
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			$object = new Product($db);
			$result = $object->fetch($id, $ref);
			$elementtype = 'product';
		}
		if ($type==1 && $user->rights->societe->creer)
		{
			$object = new Societe($db);
			$result = $object->fetch($objectid);
			$elementtype = 'fournisseur';
		}
		if ($type==2 && $user->rights->societe->creer)
		{
			$object = new Societe($db);
			$result = $object->fetch($objectid);
			$elementtype = 'societe';
		}
		if ($type==3 && $user->rights->adherent->creer)
		{
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
			$object = new Adherent($db);
			$result = $object->fetch($objectid);
			$elementtype = 'member';
		}
		$cat = new Categorie($db);
		$result=$cat->fetch($parent);

		$result=$cat->add_type($object,$elementtype);
		if ($result >= 0)
		{
			$mesg='<div class="ok">'.$langs->trans("WasAddedSuccessfully",$cat->label).'</div>';
		}
		else
		{
			if ($cat->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') $mesg='<div class="error">'.$langs->trans("ObjectAlreadyLinkedToCategory").'</div>';
			else $mesg=$langs->trans("Error").' '.$cat->error;
		}
	}
}


/*
 *	View
 */

$form = new Form($db);


/*
 * Fiche categorie de client et/ou fournisseur
 */
if ($socid)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$langs->load("companies");
	if (! empty($conf->notification->enabled)) $langs->load("mails");

	$soc = new Societe($db);
	$result = $soc->fetch($socid);

	llxHeader("","",$langs->trans("Category"));

	// Show tabs
	$head = societe_prepare_head($soc);

	dol_fiche_head($head, 'category', $langs->trans("ThirdParty"),0,'company');

	print '<table class="border" width="100%">';

	print '<tr><td width="25%">'.$langs->trans("ThirdPartyName").'</td><td colspan="3">';
	print $form->showrefnav($soc,'socid','',($user->societe_id?0:1),'rowid','nom','','&type='.$type);
	print '</td></tr>';

    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
	{
	   print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$soc->prefix_comm.'</td></tr>';
	}

	if ($soc->client)
	{
		print '<tr><td>';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $soc->code_client;
		if ($soc->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
	}

	if ($soc->fournisseur)
	{
		print '<tr><td>';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $soc->code_fournisseur;
		if ($soc->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
	}

	if (! empty($conf->barcode->enabled))
	{
		print '<tr><td>'.$langs->trans('Gencod').'</td><td colspan="3">'.$soc->barcode.'</td></tr>';
	}

	// Address
	print '<tr><td valign="top">'.$langs->trans('Address').'</td><td colspan="3">';
    print dol_print_address($soc->address,'gmap','thirdparty',$object->id);
    print '</td></tr>';

	// Zip / Town
	print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$soc->zip."</td>";
	print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$soc->town."</td></tr>";

	// Country
	if ($soc->country)
	{
		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
		$img=picto_from_langcode($soc->country_code);
		print ($img?$img.' ':'');
		print $soc->country;
		print '</td></tr>';
	}

	// EMail
	print '<tr><td>'.$langs->trans('EMail').'</td><td colspan="3">';
	print dol_print_email($soc->email,0,$soc->id,'AC_EMAIL');
	print '</td></tr>';

	// Web
	print '<tr><td>'.$langs->trans('Web').'</td><td colspan="3">';
	print dol_print_url($soc->url);
	print '</td></tr>';

	// Phone
	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($soc->tel,$soc->country_code,0,$soc->id,'AC_TEL').'</td>';
	print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($soc->fax,$soc->country_code,0,$soc->id,'AC_FAX').'</td></tr>';

	// Assujeti a TVA ou pas
	print '<tr>';
	print '<td class="nowrap">'.$langs->trans('VATIsUsed').'</td><td colspan="3">';
	print yn($soc->tva_assuj);
	print '</td>';
	print '</tr>';

	print '</table>';

	dol_fiche_end();

	dol_htmloutput_mesg($mesg);

	if ($soc->client) formCategory($db,$soc,2,$socid);

	if ($soc->client && $soc->fournisseur) print '<br><br>';

	if ($soc->fournisseur) formCategory($db,$soc,1,$socid);
}
else if ($id || $ref)
{
	if ($type == 0)
	{
		$langs->load("products");

		/*
		 * Fiche categorie de produit
		 */
		require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

		// Produit
		$product = new Product($db);
		$result = $product->fetch($id, $ref);

		llxHeader("","",$langs->trans("CardProduct".$product->type));


		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'category', $titre,0,$picto);


		print '<table class="border" width="100%">';

		// Ref
		print "<tr>";
		print '<td width="15%">'.$langs->trans("Ref").'</td><td>';
		print $form->showrefnav($product,'ref','',1,'ref');
		print '</td>';
		print '</tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
		print '</tr>';

		// Status (to sell)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>';
		print $product->getLibStatut(2,0);
		print '</td></tr>';

		// Status (to buy)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td>';
		print $product->getLibStatut(2,1);
		print '</td></tr>';

		print '</table>';

		dol_fiche_end();

		dol_htmloutput_mesg($mesg);

		formCategory($db,$product,0);
	}

	if ($type == 3)
	{
		$langs->load("members");

		/*
		 * Fiche categorie d'adherent
		 */
		require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
		require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';

		// Produit
		$member = new Adherent($db);
		$result = $member->fetch($id, $ref);

		$membert = new AdherentType($db);
		$membert->fetch($member->typeid);

		llxHeader("","",$langs->trans("Member"));


		$head=member_prepare_head($member, $user);
		$titre=$langs->trans("Member");
		$picto='user';
		dol_fiche_head($head, 'category', $titre,0,$picto);

        $rowspan=5;
        if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) $rowspan+=1;
        if (! empty($conf->societe->enabled)) $rowspan++;

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td class="valeur">';
		print $form->showrefnav($member,'rowid');
		print '</td></tr>';

        // Login
        if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
        {
    		print '<tr><td>'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.$member->login.'&nbsp;</td></tr>';
        }

        // Morphy
        print '<tr><td>'.$langs->trans("Nature").'</td><td class="valeur" >'.$member->getmorphylib().'</td>';
        /*print '<td rowspan="'.$rowspan.'" align="center" valign="middle" width="25%">';
        print $form->showphoto('memberphoto',$member);
        print '</td>';*/
        print '</tr>';

        // Type
        print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$membert->getNomUrl(1)."</td></tr>\n";

        // Company
        print '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.$member->societe.'</td></tr>';

        // Civility
        print '<tr><td>'.$langs->trans("UserTitle").'</td><td class="valeur">'.$member->getCivilityLabel().'&nbsp;</td>';
        print '</tr>';

        // Lastname
		print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur">'.$member->lastname.'&nbsp;</td>';
		print '</tr>';

		// Firstname
		print '<tr><td>'.$langs->trans("Firstname").'</td><td class="valeur">'.$member->firstname.'&nbsp;</td>';
		print '</tr>';

		// Status
		print '<tr><td>'.$langs->trans("Status").'</td><td class="valeur">'.$member->getLibStatut(4).'</td></tr>';

		print '</table>';

		dol_fiche_end();

		dol_htmloutput_mesg($mesg);

		formCategory($db,$member,3);
	}
}


/**
 * 	Function to output a HTML select for a category
 *
 * 	@param		DoliDb		$db			Database handler
 * 	@param		Object		$object		Object we want to see categories it can be classified into
 * 	@param		int			$typeid		Type of category (0, 1, 2, 3)
 *  @param		int			$socid		Id thirdparty
 *  @return		int			0
 */
function formCategory($db,$object,$typeid,$socid=0)
{
	global $user,$langs,$form,$bc;

	if ($typeid == 0) $title = $langs->trans("ProductsCategoriesShort");
	if ($typeid == 1) $title = $langs->trans("SuppliersCategoriesShort");
	if ($typeid == 2) $title = $langs->trans("CustomersProspectsCategoriesShort");
	if ($typeid == 3) $title = $langs->trans("MembersCategoriesShort");

	// Formulaire ajout dans une categorie
	print '<br>';
	print_fiche_titre($title,'','');
	print '<form method="post" action="'.DOL_URL_ROOT.'/categories/categorie.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="typeid" value="'.$typeid.'">';
	print '<input type="hidden" name="type" value="'.$typeid.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td width="40%">';
	print $langs->trans("ClassifyInCategory").' &nbsp;';
	print $form->select_all_categories($typeid);
	print '</td><td>';
	print '<input type="submit" class="button" value="'.$langs->trans("Classify").'"></td>';
	if ($user->rights->categorie->creer)
	{
		print '<td align="right">';
		print '<a href="'.DOL_URL_ROOT.'/categories/fiche.php?action=create&amp;origin='.$object->id.'&type='.$typeid.'&urlfrom='.urlencode($_SERVER["PHP_SELF"].'?'.(($typeid==1||$typeid==2)?'socid':'id').'='.$object->id.'&type='.$typeid).'">';
		print $langs->trans("CreateCat").' ';
		print img_picto($langs->trans("Create"),'filenew');
		print "</a>";
		print '</td>';
	}
	print '</tr>';
	print '</table>';
	print '</form>';
	print '<br/>';


	$c = new Categorie($db);
	$cats = $c->containing($object->id,$typeid);

	if (count($cats) > 0)
	{
		if ($typeid == 0) $title=$langs->trans("ProductIsInCategories");
		if ($typeid == 1) $title=$langs->trans("CompanyIsInSuppliersCategories");
		if ($typeid == 2) $title=$langs->trans("CompanyIsInCustomersCategories");
		if ($typeid == 3) $title=$langs->trans("MemberIsInCategories");
		print "\n";
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="2">'.$title.':</td></tr>';

		$var = true;
		foreach ($cats as $cat)
		{
			$ways = $cat->print_all_ways();

			foreach ($ways as $way)
			{
				$var = ! $var;
				print "<tr ".$bc[$var].">";

				// Categorie
				print "<td>";
				//$c->id=;
				//print $c->getNomUrl(1);
				print img_object('','category').' '.$way."</td>";

				// Link to delete from category
				print '<td align="right">';
				$permission=0;
				if ($typeid == 0) $permission=($user->rights->produit->creer || $user->rights->service->creer);
				if ($typeid == 1) $permission=$user->rights->societe->creer;
				if ($typeid == 2) $permission=$user->rights->societe->creer;
				if ($typeid == 3) $permission=$user->rights->adherent->creer;
				if ($permission)
				{
					print "<a href= '".$_SERVER['PHP_SELF']."?".(empty($socid)?'id':'socid')."=".$object->id."&amp;type=".$typeid."&amp;removecat=".$cat->id."'>";
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
		print "</table>\n";
	}
	else if ($cats < 0)
	{
		print $langs->trans("ErrorUnknown");
	}
	else
	{
		if ($typeid == 0) $title=$langs->trans("ProductHasNoCategory");
		if ($typeid == 1) $title=$langs->trans("CompanyHasNoCategory");
		if ($typeid == 2) $title=$langs->trans("CompanyHasNoCategory");
		if ($typeid == 3) $title=$langs->trans("MemberHasNoCategory");
		print $title;
		print "<br/>";
	}
	return 0;
}


llxFooter();
$db->close();
?>
