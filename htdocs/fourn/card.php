<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Jean Heimburger      <jean@tiaris.info>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
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
 *	\file       htdocs/fourn/card.php
 *	\ingroup    fournisseur, facture
 *	\brief      Page for supplier third party card (view, edit)
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

$langs->load('companies');
$langs->load('suppliers');
$langs->load('products');
$langs->load('bills');
$langs->load('orders');
$langs->load('commercial');

$action	= GETPOST('action','aZ09');
$cancelbutton = GETPOST('cancel','alpha');

// Security check
$id = (GETPOST('socid','int') ? GETPOST('socid','int') : GETPOST('id','int'));
if ($user->societe_id) $id=$user->societe_id;
$result = restrictedArea($user, 'societe&fournisseur', $id, '&societe', '', 'rowid');

$object = new Fournisseur($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('suppliercard','globalcard'));


/*
 * Action
 */

$parameters=array('id'=>$id);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($cancelbutton)
	{
		$action = "";
	}

	if ($action == 'setsupplieraccountancycode')
	{
		$result=$object->fetch($id);
   		$object->code_compta_fournisseur=$_POST["supplieraccountancycode"];
	    $result=$object->update($object->id,$user,1,0,1);
	    if ($result < 0)
	    {
	        $mesg=join(',',$object->errors);
	    }
	}
	// conditions de reglement
	if ($action == 'setconditions' && $user->rights->societe->creer)
	{
		$object->fetch($id);
		$result=$object->setPaymentTerms(GETPOST('cond_reglement_supplier_id','int'));
		if ($result < 0) dol_print_error($db,$object->error);
	}
	// mode de reglement
	if ($action == 'setmode' && $user->rights->societe->creer)
	{
		$object->fetch($id);
		$result=$object->setPaymentMethods(GETPOST('mode_reglement_supplier_id','int'));
		if ($result < 0) dol_print_error($db,$object->error);
	}
	if ($action == 'update_extras') {
        $object->fetch($id);

        // Fill array 'array_options' with data from update form
        $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
        $ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));

        if ($ret < 0) $error++;
        if (! $error)
        {
            $result = $object->insertExtraFields();
            if ($result < 0) $error++;
        }
        if ($error) $action = 'edit_extras';
    }
}


/*
 * View
 */

$contactstatic = new Contact($db);
$form = new Form($db);

if ($id > 0 && empty($object->id))
{
	// Load data of third party
	$res=$object->fetch($id);
	if ($object->id <= 0) dol_print_error($db,$object->error);
}

if ($object->id > 0)
{
	$title=$langs->trans("ThirdParty")." - ".$langs->trans('Supplier');
	if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$langs->trans('Supplier');
	$help_url='';
	llxHeader('',$title, $help_url);

	/*
	 * Show tabs
	 */
	$head = societe_prepare_head($object);

	dol_fiche_head($head, 'supplier', $langs->trans("ThirdParty"), -1, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

	print '<div class="fichecenter"><div class="fichehalfleft">';

    print '<div class="underbanner clearboth"></div>';
	print '<table width="100%" class="border">';

    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
    }

	if ($object->fournisseur)
	{
		print '<tr>';
        print '<td class="titlefield">'.$langs->trans("SupplierCode"). '</td><td>';
        print $object->code_fournisseur;
        if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
        print '</td>';
        print '</tr>';

		$langs->load('compta');
        print '<tr>';
        print '<td>';
        print $form->editfieldkey("SupplierAccountancyCode",'supplieraccountancycode',$object->code_compta_fournisseur,$object,$user->rights->societe->creer);
        print '</td><td>';
        print $form->editfieldval("SupplierAccountancyCode",'supplieraccountancycode',$object->code_compta_fournisseur,$object,$user->rights->societe->creer);
        print '</td>';
        print '</tr>';
	}

	// Assujetti a TVA ou pas
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans('VATIsUsed').'</td><td>';
	print yn($object->tva_assuj);
	print '</td>';
	print '</tr>';

	// Local Taxes
	if ($mysoc->useLocalTax(1))
	{
		print '<tr><td>'.$langs->transcountry("LocalTax1IsUsed", $mysoc->country_code).'</td><td>';
		print yn($object->localtax1_assuj);
		print '</td></tr>';
	}
	if ($mysoc->useLocalTax(2))
	{
		print '<tr><td>'.$langs->transcountry("LocalTax2IsUsed", $mysoc->country_code).'</td><td>';
		print yn($object->localtax2_assuj);
		print '</td></tr>';
	}

    // TVA Intra
    print '<tr><td class="nowrap">'.$langs->trans('VATIntra').'</td><td>';
    print $object->tva_intra;
    print '</td></tr>';

	// Conditions de reglement par defaut
	$langs->load('bills');
	$form = new Form($db);
	print '<tr><td>';
	print '<table width="100%" class="nobordernopadding"><tr><td>';
	print $langs->trans('PaymentConditions');
	print '<td>';
	if (($action != 'editconditions') && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;socid='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'editconditions')
	{
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->cond_reglement_supplier_id,'cond_reglement_supplier_id',-1,1);
	}
	else
	{
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->cond_reglement_supplier_id,'none');
	}
	print "</td>";
	print '</tr>';

	// Mode de reglement par defaut
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans('PaymentMode');
	print '<td>';
	if (($action != 'editmode') && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;socid='.$object->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'editmode')
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->mode_reglement_supplier_id,'mode_reglement_supplier_id');
	}
	else
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->mode_reglement_supplier_id,'none');
	}
	print "</td>";
	print '</tr>';

	// Categories
	if (! empty($conf->categorie->enabled))
	{
	    $langs->load("categories");
    	print '<tr><td>' . $langs->trans("SuppliersCategoriesShort") . '</td>';
    	print '<td>';
    	print $form->showCategories($object->id, 'supplier', 1);
    	print "</td></tr>";
	}

	// Other attributes
	$parameters=array('socid'=>$object->id, 'colspan' => ' colspan="3"', 'colspanvalue' => '3');
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	// Module Adherent
    if (! empty($conf->adherent->enabled))
    {
        $langs->load("members");
        $langs->load("users");
        print '<tr><td>'.$langs->trans("LinkedToDolibarrMember").'</td>';
        print '<td>';
        $adh=new Adherent($db);
        $result=$adh->fetch('','',$object->id);
        if ($result > 0)
        {
            $adh->ref=$adh->getFullName($langs);
            print $adh->getNomUrl(1);
        }
        else
        {
            print $langs->trans("ThirdpartyNotLinkedToMember");
        }
        print '</td>';
        print "</tr>\n";
    }

	print '</table>';


	print '</div><div class="fichehalfright"><div class="ficheaddleft">';

	$boxstat = '';

	// Nbre max d'elements des petites listes
	$MAXLIST=$conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

	// Lien recap
	$boxstat.='<div class="box">';
	$boxstat.='<table summary="'.dol_escape_htmltag($langs->trans("DolibarrStateBoard")).'" class="noborder boxtable boxtablenobottom" width="100%">';
	$boxstat.='<tr class="impair"><td colspan="2" class="tdboxstats nohover">';

	if ($conf->supplier_proposal->enabled)
	{
	    // Box proposals
	    $tmp = $object->getOutstandingProposals('supplier');
	    $outstandingOpened=$tmp['opened'];
	    $outstandingTotal=$tmp['total_ht'];
	    $outstandingTotalIncTax=$tmp['total_ttc'];
	    $text=$langs->trans("OverAllSupplierProposals");
	    $link=DOL_URL_ROOT.'/supplier_proposal/list.php?socid='.$object->id;
	    $icon='bill';
	    if ($link) $boxstat.='<a href="'.$link.'" class="boxstatsindicator thumbstat nobold nounderline">';
	    $boxstat.='<div class="boxstats">';
	    $boxstat.='<span class="boxstatstext">'.img_object("",$icon).' '.$text.'</span><br>';
	    $boxstat.='<span class="boxstatsindicator">'.price($outstandingTotal, 1, $langs, 1, -1, -1, $conf->currency).'</span>';
	    $boxstat.='</div>';
	    if ($link) $boxstat.='</a>';
	}

	if ($conf->fournisseur->enabled)
	{
	    // Box proposals
	    $tmp = $object->getOutstandingOrders('supplier');
	    $outstandingOpened=$tmp['opened'];
	    $outstandingTotal=$tmp['total_ht'];
	    $outstandingTotalIncTax=$tmp['total_ttc'];
	    $text=$langs->trans("OverAllOrders");
	    $link=DOL_URL_ROOT.'/fourn/commande/list.php?socid='.$object->id;
	    $icon='bill';
	    if ($link) $boxstat.='<a href="'.$link.'" class="boxstatsindicator thumbstat nobold nounderline">';
	    $boxstat.='<div class="boxstats">';
	    $boxstat.='<span class="boxstatstext">'.img_object("",$icon).' '.$text.'</span><br>';
	    $boxstat.='<span class="boxstatsindicator">'.price($outstandingTotal, 1, $langs, 1, -1, -1, $conf->currency).'</span>';
	    $boxstat.='</div>';
	    if ($link) $boxstat.='</a>';
	}

	if ($conf->fournisseur->enabled)
	{
	    $tmp = $object->getOutstandingBills('supplier');
	    $outstandingOpened=$tmp['opened'];
	    $outstandingTotal=$tmp['total_ht'];
	    $outstandingTotalIncTax=$tmp['total_ttc'];

	    $text=$langs->trans("OverAllInvoices");
	    $link=DOL_URL_ROOT.'/fourn/facture/list.php?socid='.$object->id;
	    $icon='bill';
	    if ($link) $boxstat.='<a href="'.$link.'" class="boxstatsindicator thumbstat nobold nounderline">';
	    $boxstat.='<div class="boxstats">';
	    $boxstat.='<span class="boxstatstext">'.img_object("",$icon).' '.$text.'</span><br>';
	    $boxstat.='<span class="boxstatsindicator">'.price($outstandingTotal, 1, $langs, 1, -1, -1, $conf->currency).'</span>';
	    $boxstat.='</div>';
	    if ($link) $boxstat.='</a>';

	    // Box outstanding bill
	    $text=$langs->trans("CurrentOutstandingBill");
	    $link=DOL_URL_ROOT.'/fourn/recap-fourn.php?socid='.$object->id;
	    $icon='bill';
	    if ($link) $boxstat.='<a href="'.$link.'" class="boxstatsindicator thumbstat nobold nounderline">';
	    $boxstat.='<div class="boxstats">';
	    $boxstat.='<span class="boxstatstext">'.img_object("",$icon).' '.$text.'</span><br>';
	    $boxstat.='<span class="boxstatsindicator'.($outstandingOpened>0?' amountremaintopay':'').'">'.price($outstandingOpened, 1, $langs, 1, -1, -1, $conf->currency).$warn.'</span>';
	    $boxstat.='</div>';
	    if ($link) $boxstat.='</a>';
	}

	$boxstat.='</td></tr>';
	$boxstat.='</table>';
	$boxstat.='</div>';

	print $boxstat;


	$var=true;

	$MAXLIST=$conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

	// Lien recap
	/*
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("Summary").'</td>';
	print '<td align="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/fourn/recap-fourn.php?socid='.$object->id.'">'.$langs->trans("ShowSupplierPreview").'</a></td></tr></table></td>';
	print '</tr>';
	print '</table>';
	print '<br>';
    */

	/*
	 * List of products
	 */
	if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
	{
		$langs->load("products");
		//Query from product/liste.php
		$sql = 'SELECT p.rowid, p.ref, p.label, p.fk_product_type, p.entity,';
		$sql.= ' pfp.tms, pfp.ref_fourn as supplier_ref, pfp.price, pfp.quantity, pfp.unitprice';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'product_fournisseur_price as pfp';
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = pfp.fk_product";
		$sql.= ' WHERE p.entity IN ('.getEntity('product').')';
		$sql.= ' AND pfp.fk_soc = '.$object->id;
		$sql .= $db->order('pfp.tms', 'desc');
		$sql.= $db->plimit($MAXLIST);

		$query = $db->query($sql);
        if (! $query) dol_print_error($db);

        $num = $db->num_rows($query);

        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre'.(($num == 0) ? ' nobottom':'').'">';
        print '<td colspan="3">'.$langs->trans("ProductsAndServices").'</td><td align="right">';
        print '<a class="notasortlink" href="'.DOL_URL_ROOT.'/fourn/product/list.php?fourn_id='.$object->id.'">'.$langs->trans("AllProductReferencesOfSupplier").' <span class="badge">'.$object->nbOfProductRefs().'</span>';
        print '</a></td></tr>';

		$return = array();
		if ($num > 0)
		{
			$productstatic = new Product($db);

			while ($objp = $db->fetch_object($query))
			{
				$productstatic->id = $objp->rowid;
				$productstatic->ref = $objp->ref;
				$productstatic->label = $objp->label;
				$productstatic->type = $objp->fk_product_type;
				$productstatic->entity = $objp->entity;

				print '<tr class="oddeven">';
				print '<td class="nowrap">';
				print $productstatic->getNomUrl(1);
				print '</td>';
				print '<td>';
				print $objp->supplier_ref;
				print '</td>';
				print '<td class="maxwidthonsmartphone">';
				print dol_trunc(dol_htmlentities($objp->label), 30);
				print '</td>';
				//print '<td align="right" class="nowrap">'.dol_print_date($objp->tms, 'day').'</td>';
				print '<td align="right">';
				//print (isset($objp->unitprice) ? price($objp->unitprice) : '');
				if (isset($objp->price))
				{
    				print price($objp->price);
				    if ($objp->quantity > 1)
				    {
    				    print ' / ';
    				    print $objp->quantity;
				    }
				}
				print '</td>';
				print '</tr>';
			}
		}

		print '</table>';
	}


	/*
	 * Latest supplier proposal
	 */
	$proposalstatic = new SupplierProposal($db);

	if ($user->rights->supplier_proposal->lire)
	{
	    $sql  = "SELECT p.rowid, p.ref, p.date_valid as dc, p.fk_statut, p.total_ht, p.tva as total_tva, p.total as total_ttc";
	    $sql.= " FROM ".MAIN_DB_PREFIX."supplier_proposal as p ";
	    $sql.= " WHERE p.fk_soc =".$object->id;
	    $sql.= " AND p.entity IN (".getEntity('supplier_proposal').")";
	    $sql.= " ORDER BY p.date_valid DESC";
	    $sql.= " ".$db->plimit($MAXLIST);

	    $resql=$db->query($sql);
	    if ($resql)
	    {
	        $i = 0 ;
	        $num = $db->num_rows($resql);

	        if ($num > 0)
	        {
	            print '<table class="noborder" width="100%">';

	            print '<tr class="liste_titre">';
	            print '<td colspan="3">';
	            print '<table class="nobordernopadding centpercent"><tr><td>'.$langs->trans("LastSupplierProposals",($num<$MAXLIST?"":$MAXLIST)).'</td>';
	            print '<td align="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/supplier_proposal/list.php?socid='.$object->id.'">'.$langs->trans("AllPriceRequests").' <span class="badge">'.$num.'</span></td>';
	            print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/supplier_proposal/stats/index.php?mode=supplier&socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
	            print '</tr></table>';
	            print '</td></tr>';
	        }

	        $var = True;
	        while ($i < $num && $i <= $MAXLIST)
	        {
	            $obj = $db->fetch_object($resql);


	            print '<tr class="oddeven">';
	            print '<td class="nowrap">';
	            $proposalstatic->id = $obj->rowid;
	            $proposalstatic->ref = $obj->ref;
	            $proposalstatic->total_ht = $obj->total_ht;
	            $proposalstatic->total_tva = $obj->total_tva;
	            $proposalstatic->total_ttc = $obj->total_ttc;
	            print $proposalstatic->getNomUrl(1);
	            print '</td>';
	            print '<td align="center" width="80">';
	            if ($obj->dc)
	            {
	                print dol_print_date($db->jdate($obj->dc),'day');
	            }
	            else
	            {
	                print "-";
	            }
	            print '</td>';
	            print '<td align="right" class="nowrap">'.$proposalstatic->LibStatut($obj->fk_statut,5).'</td>';
	            print '</tr>';
	            $i++;
	        }
	        $db->free($resql);

	        if ($num >0) print "</table>";
	    }
	    else
	    {
	        dol_print_error($db);
	    }
	}

	/*
	 * Latest supplier orders
	 */
	$orderstatic = new CommandeFournisseur($db);

	if ($user->rights->fournisseur->commande->lire)
	{
		// TODO move to DAO class
		// Check if there are supplier orders billable
		$sql2 = 'SELECT s.nom, s.rowid as socid, s.client, c.rowid, c.ref, c.total_ht, c.ref_supplier,';
		$sql2.= ' c.date_valid, c.date_commande, c.date_livraison, c.fk_statut';
		$sql2.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
		$sql2.= ', '.MAIN_DB_PREFIX.'commande_fournisseur as c';
		$sql2.= ' WHERE c.fk_soc = s.rowid';
		$sql2.= " AND c.entity IN (".getEntity('commande_fournisseur').")";
		$sql2.= ' AND s.rowid = '.$object->id;
		// Show orders with status validated, shipping started and delivered (well any order we can bill)
		$sql2.= " AND c.fk_statut IN (5)";
		$sql2.= " AND c.billed = 0";
		// Find order that are not already invoiced
		// just need to check received status because we have the billed status now
		//$sql2 .= " AND c.rowid NOT IN (SELECT fk_source FROM " . MAIN_DB_PREFIX . "element_element WHERE targettype='invoice_supplier')";
		$resql2=$db->query($sql2);
		if ($resql2) {
			$orders2invoice = $db->num_rows($resql2);
			$db->free($resql2);
		} else {
			setEventMessages($db->lasterror(), null, 'errors');
		}

		// TODO move to DAO class
		$sql  = "SELECT count(p.rowid) as total";
		$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as p";
		$sql.= " WHERE p.fk_soc =".$object->id;
		$sql.= " AND p.entity IN (".getEntity('commande_fournisseur').")";
		$resql=$db->query($sql);
		if ($resql)
		{
			$object_count = $db->fetch_object($resql);
			$num = $object_count->total;
		}

		$sql  = "SELECT p.rowid,p.ref, p.date_commande as dc, p.fk_statut, p.total_ht, p.tva as total_tva, p.total_ttc";
		$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as p";
		$sql.= " WHERE p.fk_soc =".$object->id;
		$sql.= " AND p.entity IN (".getEntity('commande_fournisseur').")";
		$sql.= " ORDER BY p.date_commande DESC";
		$sql.= " ".$db->plimit($MAXLIST);
		$resql=$db->query($sql);
		if ($resql)
		{
			$i = 0 ;

			if ($num > 0)
			{
			    print '<table class="noborder" width="100%">';

			    print '<tr class="liste_titre">';
    			print '<td colspan="3">';
    			print '<table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("LastSupplierOrders",($num<$MAXLIST?"":$MAXLIST)).'</td>';
    			print '<td align="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/fourn/commande/list.php?socid='.$object->id.'">'.$langs->trans("AllOrders").' <span class="badge">'.$num.'</span></td>';
                print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/commande/stats/index.php?mode=supplier&socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
    			print '</tr></table>';
    			print '</td></tr>';
			}

			$var = True;
			while ($i < $num && $i < $MAXLIST)
			{
				$obj = $db->fetch_object($resql);


				print '<tr class="oddeven">';
                print '<td class="nowrap">';
                $orderstatic->id = $obj->rowid;
                $orderstatic->ref = $obj->ref;
                $orderstatic->total_ht = $obj->total_ht;
                $orderstatic->total_tva = $obj->total_tva;
                $orderstatic->total_ttc = $obj->total_ttc;
                print $orderstatic->getNomUrl(1);
                print '</td>';
				print '<td align="center" width="80">';
				if ($obj->dc)
				{
					print dol_print_date($db->jdate($obj->dc),'day');
				}
				else
				{
					print "-";
				}
				print '</td>';
				print '<td align="right" class="nowrap">'.$orderstatic->LibStatut($obj->fk_statut,5).'</td>';
				print '</tr>';
				$i++;
			}
			$db->free($resql);

			if ($num >0) print "</table>";
		}
		else
		{
			dol_print_error($db);
		}
	}

	/*
	 * Latest supplier invoices
	 */

	$langs->load('bills');
	$facturestatic = new FactureFournisseur($db);

	if ($user->rights->fournisseur->facture->lire)
	{
		// TODO move to DAO class
		$sql = 'SELECT f.rowid,f.libelle,f.ref,f.ref_supplier,f.fk_statut,f.datef as df, f.total_ht, f.total_tva, f.total_ttc as amount,f.paye,';
		$sql.= ' SUM(pf.amount) as am';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON f.rowid=pf.fk_facturefourn';
		$sql.= ' WHERE f.fk_soc = '.$object->id;
		$sql.= " AND f.entity IN (".getEntity('facture_fourn').")";
		$sql.= ' GROUP BY f.rowid,f.libelle,f.ref,f.ref_supplier,f.fk_statut,f.datef,f.total_ht,f.total_tva,f.total_ttc,f.paye';
		$sql.= ' ORDER BY f.datef DESC';
		$resql=$db->query($sql);
		if ($resql)
		{
			$i = 0 ;
			$num = $db->num_rows($resql);
			if ($num > 0)
			{
			    print '<table class="noborder" width="100%">';

			    print '<tr class="liste_titre">';
    			print '<td colspan="4">';
    			print '<table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans('LastSuppliersBills',($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/fourn/facture/list.php?socid='.$object->id.'">'.$langs->trans('AllBills').' <span class="badge">'.$num.'</span></td>';
                print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?mode=supplier&socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
    			print '</tr></table>';
    			print '</td></tr>';
			}
			$var=True;
			while ($i < min($num,$MAXLIST))
			{
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven">';
				print '<td>';
				print '<a href="facture/card.php?facid='.$obj->rowid.'">';
				$facturestatic->id=$obj->rowid;
				$facturestatic->ref=($obj->ref?$obj->ref:$obj->rowid);
				$facturestatic->ref_supplier = $obj->ref_supplier;
				$facturestatic->libelle = $obj->libelle;
				$facturestatic->total_ht = $obj->total_ht;
                $facturestatic->total_tva = $obj->total_tva;
                $facturestatic->total_ttc = $obj->total_ttc;
				print $facturestatic->getNomUrl(1);
				print $obj->ref_supplier?' - '.$obj->ref_supplier:'';
				print ($obj->libelle?' - ':'').dol_trunc($obj->libelle,14);
				print '</td>';
				print '<td align="center" class="nowrap">'.dol_print_date($db->jdate($obj->df),'day').'</td>';
				print '<td align="right" class="nowrap">'.price($obj->amount).'</td>';
				print '<td align="right" class="nowrap">';
				print $facturestatic->LibStatut($obj->paye,$obj->fk_statut,5,$obj->am);
				print '</td>';
				print '</tr>';
				$i++;
			}
			$db->free($resql);
			if ($num > 0) print '</table>';
		}
		else
		{
			dol_print_error($db);
		}
	}

	print '</div></div></div>';
	print '<div style="clear:both"></div>';

	dol_fiche_end();


	/*
	 * Barre d'actions
	 */
	print '<div class="tabsAction">';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
	// modified by hook
	if (empty($reshook))
	{
	    if ($object->status != 1)
        {
            print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.dol_escape_js($langs->trans("ThirdPartyIsClosed")).'" href="#">'.$langs->trans("ThirdPartyIsClosed").'</a></div>';
        }

		if ($conf->supplier_proposal->enabled && $user->rights->supplier_proposal->creer)
		{
			$langs->load("supplier_proposal");
	      if ($object->status == 1)
	      {
	        print '<a class="butAction" href="'.DOL_URL_ROOT.'/supplier_proposal/card.php?action=create&socid='.$object->id.'">'.$langs->trans("AddSupplierProposal").'</a>';
	      }
	      else
	      {
	        print '<a class="butActionRefused" title="'.dol_escape_js($langs->trans("ThirdPartyIsClosed")).'" href="#">'.$langs->trans("AddSupplierProposal").'</a>';
	      }
		}

	    if ($user->rights->fournisseur->commande->creer)
		{
			$langs->load("orders");
	      if ($object->status == 1)
	      {
	        print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/card.php?action=create&socid='.$object->id.'">'.$langs->trans("AddOrder").'</a>';
	      }
	      else
	      {
	        print '<a class="butActionRefused" title="'.dol_escape_js($langs->trans("ThirdPartyIsClosed")).'" href="#">'.$langs->trans("AddOrder").'</a>';
	      }
		}

		if ($user->rights->fournisseur->facture->creer)
		{
			$langs->load("bills");
	      if ($object->status == 1)
	      {
	        print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/card.php?action=create&socid='.$object->id.'">'.$langs->trans("AddBill").'</a>';
	      }
	      else
	      {
	        print '<a class="butActionRefused" title="'.dol_escape_js($langs->trans("ThirdPartyIsClosed")).'" href="#">'.$langs->trans("AddBill").'</a>';
	      }
		}

		if ($user->rights->fournisseur->facture->creer)
		{
			if (! empty($orders2invoice) && $orders2invoice > 0)
			{
				if ($object->status == 1)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/orderstoinvoice.php?socid='.$object->id.'">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
				}
				else
				{
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
				}
			}
			else print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.dol_escape_js($langs->trans("NoOrdersToInvoice")).'" href="#">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
		}

    	// Add action
    	if (! empty($conf->agenda->enabled) && ! empty($conf->global->MAIN_REPEATTASKONEACHTAB) && $object->status==1)
    	{
        	if ($user->rights->agenda->myactions->create)
        	{
            	print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&socid='.$object->id.'">'.$langs->trans("AddAction").'</a>';
        	}
        	else
        	{
            	print '<a class="butAction" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddAction").'</a>';
        	}
    	}
	}

	print '</div>';


	if (! empty($conf->global->MAIN_DUPLICATE_CONTACTS_TAB_ON_MAIN_CARD))
	{
    	print '<br>';
    	// List of contacts
    	show_contacts($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
	}

	// Addresses list
	if (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) && ! empty($conf->global->MAIN_REPEATADDRESSONEACHTAB))
	{
		$result=show_addresses($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
	}

	if (! empty($conf->global->MAIN_REPEATTASKONEACHTAB))
	{
    	print load_fiche_titre($langs->trans("ActionsOnCompany"),'','');

    	// List of todo actions
    	show_actions_todo($conf,$langs,$db,$object);

    	// List of done actions
    	show_actions_done($conf,$langs,$db,$object);
	}
}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();
