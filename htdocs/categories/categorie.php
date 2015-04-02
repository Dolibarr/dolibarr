<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2006-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
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
	elseif ($type == 4) {
		$elementtype = 'societe';
		$objecttype = 'contact';
		$objectid = isset($id)?$id:(isset($ref)?$ref:'');
		$dbtablename = 'socpeople&societe';
		$fieldid = ! empty($ref)?'ref':'rowid';
	}
}

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user,$objecttype,$objectid,$dbtablename,'','',$fieldid);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('categorycard','globalcard'));


/*
 *	Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

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
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
			$object = new Fournisseur($db);
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
		if ($type == 4 && $user->rights->societe->creer)
		{
			require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
			$object = new Contact($db);
			$result = $object->fetch($objectid);
			$elementtype = 'contact';
		}
		$cat = new Categorie($db);
		$result=$cat->fetch($removecat);

		$result=$cat->del_type($object,$elementtype);
		if ($result < 0)
		{
			setEventMessage($cat->error,'errors');
			setEventMessage($cat->errors,'errors');
		}
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
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
			$object = new Fournisseur($db);
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
		if ($type == 4 && $user->rights->societe->creer)
		{
			require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
			$object = new Contact($db);
			$result = $object->fetch($objectid);
			$elementtype = 'contact';
		}
		$cat = new Categorie($db);
		$result=$cat->fetch($parent);

		$result=$cat->add_type($object,$elementtype);
		if ($result >= 0)
		{
			setEventMessage($langs->trans("WasAddedSuccessfully",$cat->label));
		}
		else
		{
			if ($cat->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				setEventMessage($langs->trans("ObjectAlreadyLinkedToCategory"),'warnings');
			}
			else
			{
				setEventMessages($cat->error,$this->errors,'errors');
			}
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

	$title=$langs->trans("Category");
	if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$soc->name." - ".$title;
	llxHeader("",$title);

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
    dol_print_address($soc->address,'gmap','thirdparty',$soc->id);
    print '</td></tr>';

	// Zip / Town
	print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$soc->zip."</td>";
	print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$soc->town."</td></tr>";

	// Country
	if ($soc->country)
	{
		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
		//$img=picto_from_langcode($soc->country_code);
		$img='';
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
	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($soc->phone,$soc->country_code,0,$soc->id,'AC_TEL').'</td>';
	print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($soc->fax,$soc->country_code,0,$soc->id,'AC_FAX').'</td></tr>';

	print '</table>';

	dol_fiche_end();

	if ($soc->client) formCategory($db,$soc,2,$socid,$user->rights->societe->creer);

	if ($soc->client && $soc->fournisseur) print '<br><br>';

	if ($soc->fournisseur) formCategory($db,$soc,1,$socid,$user->rights->societe->creer);
}
else if ($id || $ref)
{
	if ($type == 0)
	{
		$langs->load("products");

		/*
		 *  Category card for product
		 */
		require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

		// Product
		$product = new Product($db);
		$result = $product->fetch($id, $ref);

		llxHeader("","",$langs->trans("CardProduct".$product->type));


		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type== Product::TYPE_SERVICE?'service':'product');
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

		formCategory($db,$product,0,$socid,($user->rights->produit->creer || $user->rights->service->creer));
	}

	if ($type == 3)
	{
		$langs->load("members");

		/*
		 *  Category card for member
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


		$head=member_prepare_head($member);
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
		print $form->showrefnav($member,'id');
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

		formCategory($db,$member,3,0,$user->rights->adherent->creer);
	}
	if ($type == 4)
	{
		$langs->load("contact");

		/*
		 * Category card for contact
		 */
		require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

		// Produit
		$object = new Contact($db);
		$result = $object->fetch($id, $ref);
		$object->fetch_thirdparty();

		llxHeader("","",$langs->trans("Contact"));


		$head=contact_prepare_head($object);
		$titre=$langs->trans("ContactsAddresses");
		$picto='contact';
		dol_fiche_head($head, 'category', $titre,0,$picto);

		$rowspan=5;
		if (! empty($conf->societe->enabled)) $rowspan++;

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td class="valeur">';
		print $form->showrefnav($object,'rowid');
		print '</td></tr>';

	  // Name
        print '<tr><td width="20%">'.$langs->trans("Lastname").' / '.$langs->trans("Label").'</td><td width="30%">'.$object->lastname.'</td>';
        print '<td width="20%">'.$langs->trans("Firstname").'</td><td width="30%">'.$object->firstname.'</td></tr>';

        // Company
        if (empty($conf->global->SOCIETE_DISABLE_CONTACTS))
        {
            print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
            if (!empty($object->thirdparty->id))
            {
                print $object->thirdparty->getNomUrl(1);
            }
            else
            {
                print $langs->trans("ContactNotLinkedToCompany");
            }
            print '</td></tr>';
        }

        // Civility
        print '<tr><td width="15%">'.$langs->trans("UserTitle").'</td><td colspan="3">';
        print $object->getCivilityLabel();
        print '</td></tr>';

        // Role
        print '<tr><td>'.$langs->trans("PostOrFunction").'</td><td colspan="3">'.$object->poste.'</td>';

        // Address
        print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3">';
        dol_print_address($object->address,'gmap','contact',$object->id);
        print '</td></tr>';

        // Zip/Town
        print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3">';
        print $object->zip;
        if ($object->zip) print '&nbsp;';
        print $object->town.'</td></tr>';

        // Country
        print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
        $img=picto_from_langcode($object->country_code);
        if ($img) print $img.' ';
        print $object->country;
        print '</td></tr>';

        // State
        if (empty($conf->global->SOCIETE_DISABLE_STATE))
        {
            print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">'.$object->state.'</td>';
        }

        // Phone
        print '<tr><td>'.$langs->trans("PhonePro").'</td><td>'.dol_print_phone($object->phone_pro,$object->country_code,$object->id,$object->socid,'AC_TEL').'</td>';
        print '<td>'.$langs->trans("PhonePerso").'</td><td>'.dol_print_phone($object->phone_perso,$object->country_code,$object->id,$object->socid,'AC_TEL').'</td></tr>';

        print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td>'.dol_print_phone($object->phone_mobile,$object->country_code,$object->id,$object->socid,'AC_TEL').'</td>';
        print '<td>'.$langs->trans("Fax").'</td><td>'.dol_print_phone($object->fax,$object->country_code,$object->id,$object->socid,'AC_FAX').'</td></tr>';

        // Email
        print '<tr><td>'.$langs->trans("EMail").'</td><td>'.dol_print_email($object->email,$object->id,$object->socid,'AC_EMAIL').'</td>';
        if (! empty($conf->mailing->enabled))
        {
            $langs->load("mails");
            print '<td class="nowrap">'.$langs->trans("NbOfEMailingsReceived").'</td>';
            print '<td><a href="'.DOL_URL_ROOT.'/comm/mailing/list.php?filteremail='.urlencode($object->email).'">'.$object->getNbOfEMailings().'</a></td>';
        }
        else
        {
            print '<td colspan="2">&nbsp;</td>';
        }
        print '</tr>';

        // Instant message and no email
        print '<tr><td>'.$langs->trans("IM").'</td><td>'.$object->jabberid.'</td>';
        if (!empty($conf->mailing->enabled))
        {
        	print '<td>'.$langs->trans("No_Email").'</td><td>'.yn($object->no_email).'</td>';
        }
        else
       {
	       	print '<td colspan="2">&nbsp;</td>';
        }
        print '</tr>';

        print '<tr><td>'.$langs->trans("ContactVisibility").'</td><td colspan="3">';
        print $object->LibPubPriv($object->priv);
        print '</td></tr>';

        // Note Public
        print '<tr><td valign="top">'.$langs->trans("NotePublic").'</td><td colspan="3">';
        print nl2br($object->note_public);
        print '</td></tr>';

        // Note Private
        print '<tr><td valign="top">'.$langs->trans("NotePrivate").'</td><td colspan="3">';
        print nl2br($object->note_private);
        print '</td></tr>';

        // Other attributes
        $parameters=array('socid'=>$socid, 'colspan' => ' colspan="3"');
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
        if (empty($reshook) && ! empty($extrafields->attribute_label))
        {
        	print $object->showOptionals($extrafields);
        }

		print '</table>';

		dol_fiche_end();

		formCategory($db,$object,4,$socid, $user->rights->societe->creer);
	}
}


/**
 * 	Function to output a form to add object into a category
 *
 * 	@param		DoliDb		$db					Database handler
 * 	@param		Object		$object				Object we want to see categories it can be classified into
 * 	@param		int			$typeid				Type of category (0, 1, 2, 3)
 *  @param		int			$socid				Id thirdparty
 *  @param		int		$showclassifyform	1=Add form to 'Classify', 0=Do not show form to 'Classify'
 *  @return		int			0
 */
function formCategory($db,$object,$typeid,$socid=0,$showclassifyform=1)
{
	global $user,$langs,$form,$bc;

	if ($typeid == 0) $title = $langs->trans("ProductsCategoriesShort");
	if ($typeid == 1) $title = $langs->trans("SuppliersCategoriesShort");
	if ($typeid == 2) $title = $langs->trans("CustomersProspectsCategoriesShort");
	if ($typeid == 3) $title = $langs->trans("MembersCategoriesShort");
	if ($typeid == 4) $title = $langs->trans("ContactCategoriesShort");

	$linktocreate='';
	if ($showclassifyform && $user->rights->categorie->creer)
	{
		$linktocreate='<a href="'.DOL_URL_ROOT.'/categories/card.php?action=create&amp;origin='.$object->id.'&type='.$typeid.'&urlfrom='.urlencode($_SERVER["PHP_SELF"].'?'.(($typeid==1||$typeid==2)?'socid':'id').'='.$object->id.'&type='.$typeid).'">';
		$linktocreate.=$langs->trans("CreateCat").' ';
		$linktocreate.=img_picto($langs->trans("Create"),'filenew');
		$linktocreate.="</a>";
	}

	print '<br>';
	print_fiche_titre($title,$linktocreate,'');

	// Form to add record into a category
	if ($showclassifyform)
	{
		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="typeid" value="'.$typeid.'">';
		print '<input type="hidden" name="type" value="'.$typeid.'">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td width="40%">';
		print '<span class="hideonsmartphone">'.$langs->trans("ClassifyInCategory").' &nbsp;</span>';
		print $form->select_all_categories($typeid,'auto');
		print '</td>';
		print '<td><input type="submit" class="button" value="'.$langs->trans("Classify").'"></td>';
		print '</tr>';
		print '</table>';
		print '</form>';
		print '<br>';
	}


	$c = new Categorie($db);
	$cats = $c->containing($object->id,$typeid);

	if (count($cats) > 0)
	{
		if ($typeid == 0) $title=$langs->trans("ProductIsInCategories");
		if ($typeid == 1) $title=$langs->trans("CompanyIsInSuppliersCategories");
		if ($typeid == 2) $title=$langs->trans("CompanyIsInCustomersCategories");
		if ($typeid == 3) $title=$langs->trans("MemberIsInCategories");
		if ($typeid == 4) $title=$langs->trans("ContactIsInCategories");
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
				print "<td>".img_object('','category').' '.$way."</td>";

				// Link to delete from category
				print '<td align="right">';
				$permission=0;
				if ($typeid == 0) $permission=($user->rights->produit->creer || $user->rights->service->creer);
				if ($typeid == 1) $permission=$user->rights->societe->creer;
				if ($typeid == 2) $permission=$user->rights->societe->creer;
				if ($typeid == 3) $permission=$user->rights->adherent->creer;
				if ($typeid == 4) $permission=$user->rights->societe->creer;
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
		if ($typeid == 4) $title=$langs->trans("ContactHasNoCategory");
		print $title;
		print "<br/>";
	}
	return 0;
}


llxFooter();

$db->close();
