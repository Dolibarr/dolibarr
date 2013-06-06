<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2013 Juanjo Menent        <jmenent@2byte.es>
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
 *	\file       htdocs/fourn/fiche.php
 *	\ingroup    fournisseur, facture
 *	\brief      Page for supplier third party card (view, edit)
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

$langs->load('suppliers');
$langs->load('products');
$langs->load('bills');
$langs->load('orders');
$langs->load('companies');
$langs->load('commercial');

$action	= GETPOST('action');

// Security check
$id = (GETPOST('socid','int') ? GETPOST('socid','int') : GETPOST('id','int'));
if ($user->societe_id) $id=$user->societe_id;
$result = restrictedArea($user, 'societe&fournisseur', $id, '&societe');

$object = new Fournisseur($db);

/*
 * Action
 */

if ($action == 'setsupplieraccountancycode')
{
    $result=$object->fetch($id);
    $object->code_compta_fournisseur=$_POST["supplieraccountancycode"];
    $result=$object->update($object->id,$user,1,0,1);
    if ($result < 0)
    {
        $mesg=join(',',$object->errors);
    }
    $action="";
}



/*
 * View
 */

$contactstatic = new Contact($db);
$form = new Form($db);

if ($object->fetch($id))
{
	llxHeader('',$langs->trans('SupplierCard'));

	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($object);

	dol_fiche_head($head, 'supplier', $langs->trans("ThirdParty"),0,'company');


	print '<div class="fichecenter"><div class="fichehalfleft">';
	//print '<table width="100%" class="notopnoleftnoright">';
	//print '<tr><td valign="top" width="50%" class="notopnoleft">';

	print '<table width="100%" class="border">';
	print '<tr><td width="20%">'.$langs->trans("ThirdPartyName").'</td><td width="80%" colspan="3">';
	$object->next_prev_filter="te.fournisseur = 1";
	print $form->showrefnav($object,'socid','',($user->societe_id?0:1),'rowid','nom','','');
	print '</td></tr>';

    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
    }

	if ($object->fournisseur)
	{
		print '<tr>';
        print '<td class="nowrap">'.$langs->trans("SupplierCode"). '</td><td colspan="3">';
        print $object->code_fournisseur;
        if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
        print '</td>';
        print '</tr>';

		$langs->load('compta');
        print '<tr>';
        print '<td>';
        print $form->editfieldkey("SupplierAccountancyCode",'supplieraccountancycode',$object->code_compta_fournisseur,$object,$user->rights->societe->creer);
        print '</td><td colspan="3">';
        print $form->editfieldval("SupplierAccountancyCode",'supplieraccountancycode',$object->code_compta_fournisseur,$object,$user->rights->societe->creer);
        print '</td>';
        print '</tr>';
	}

	// Address
	print '<tr><td valign="top">'.$langs->trans("Address").'</td><td colspan="3">';
	dol_print_address($object->address,'gmap','thirdparty',$object->id);
	print '</td></tr>';

	// Zip / Town
	print '<tr><td class="nowrap">'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3">'.$object->zip.(($object->zip && $object->town)?' / ':'').$object->town.'</td>';
	print '</tr>';

	// Country
	print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
	$img=picto_from_langcode($object->country_code);
	if ($object->isInEEC()) print $form->textwithpicto(($img?$img.' ':'').$object->country,$langs->trans("CountryIsInEEC"),1,0);
	else print ($img?$img.' ':'').$object->country;
	print '</td></tr>';

    // EMail
	print '<td>'.$langs->trans('EMail').'</td><td colspan="3">'.dol_print_email($object->email,0,$object->id,'AC_EMAIL').'</td></tr>';

	// Web
	print '<tr><td>'.$langs->trans("Web").'</td><td colspan="3">'.dol_print_url($object->url).'</td></tr>';

	// Phone
	print '<tr><td>'.$langs->trans("Phone").'</td><td style="min-width: 25%;">'.dol_print_phone($object->tel,$object->country_code,0,$object->id,'AC_TEL').'</td>';

	// Fax
	print '<td>'.$langs->trans("Fax").'</td><td style="min-width: 25%;">'.dol_print_phone($object->fax,$object->country_code,0,$object->id,'AC_FAX').'</td></tr>';

	// Assujetti a TVA ou pas
	print '<tr>';
	print '<td class="nowrap">'.$langs->trans('VATIsUsed').'</td><td colspan="3">';
	print yn($object->tva_assuj);
	print '</td>';
	print '</tr>';

	// Local Taxes
	//TODO: Place into a function to control showing by country or study better option
	if($mysoc->country_code=='ES')
	{
		if($mysoc->localtax1_assuj=="1" && $mysoc->localtax2_assuj=="1")
		{
			print '<tr><td class="nowrap">'.$langs->trans('LocalTax1IsUsedES').'</td><td colspan="3">';
			print yn($object->localtax1_assuj);
			print '</td></tr>';
			print '<tr><td class="nowrap">'.$langs->trans('LocalTax2IsUsedES').'</td><td colspan="3">';
			print yn($object->localtax2_assuj);
			print '</td></tr>';
		}
		elseif($mysoc->localtax1_assuj=="1")
		{
			print '<tr><td>'.$langs->trans("LocalTax1IsUsedES").'</td><td colspan="3">';
			print yn($object->localtax1_assuj);
			print '</td></tr>';
		}
		elseif($mysoc->localtax2_assuj=="1")
		{
			print '<tr><td>'.$langs->trans("LocalTax2IsUsedES").'</td><td colspan="3">';
			print yn($object->localtax2_assuj);
			print '</td></tr>';
		}

		if ($mysoc->localtax2_assuj!="1")
		{
			print '<tr><td>'.$langs->transcountry("LocalTax2IsUsed",$mysoc->country_code).'</td><td colspan="3">';
			print yn($object->localtax2_assuj);
			print '</td><tr>';
		}

	}

    // TVA Intra
    print '<tr><td nowrap>'.$langs->trans('VATIntra').'</td><td colspan="3">';
    print $object->tva_intra;
    print '</td></tr>';

    // Module Adherent
    if (! empty($conf->adherent->enabled))
    {
        $langs->load("members");
        $langs->load("users");
        print '<tr><td width="25%" valign="top">'.$langs->trans("LinkedToDolibarrMember").'</td>';
        print '<td colspan="3">';
        $adh=new Adherent($db);
        $result=$adh->fetch('','',$object->id);
        if ($result > 0)
        {
            $adh->ref=$adh->getFullName($langs);
            print $adh->getNomUrl(1);
        }
        else
        {
            print $langs->trans("UserNotLinkedToMember");
        }
        print '</td>';
        print "</tr>\n";
    }

	print '</table>';


	print '</div><div class="fichehalfright"><div class="ficheaddleft">';
	//print '</td><td valign="top" width="50%" class="notopnoleftnoright">';


	$var=true;

	$MAXLIST=5;

	// Lien recap
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("Summary").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/fourn/recap-fourn.php?socid='.$object->id.'">'.$langs->trans("ShowSupplierPreview").'</a></td></tr></table></td>';
	print '</tr>';
	print '</table>';

	/*
	 * List of products
	 */
	if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
	{
		$langs->load("products");
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("ProductsAndServices").'</td><td align="right">';
		print '<a href="'.DOL_URL_ROOT.'/fourn/product/liste.php?fourn_id='.$object->id.'">'.$langs->trans("All").' ('.$object->nbOfProductRefs().')';
		print '</a></td></tr></table>';
	}


	print '<br>';

	/*
	 * Last orders
	 */
	$orderstatic = new CommandeFournisseur($db);

	if ($user->rights->fournisseur->commande->lire)
	{
		// TODO move to DAO class
		$sql  = "SELECT p.rowid,p.ref, p.date_commande as dc, p.fk_statut";
		$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as p ";
		$sql.= " WHERE p.fk_soc =".$object->id;
		$sql.= " ORDER BY p.date_commande DESC";
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
    			print '<table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("LastOrders",($num<$MAXLIST?"":$MAXLIST)).'</td>';
    			print '<td align="right"><a href="commande/liste.php?socid='.$object->id.'">'.$langs->trans("AllOrders").' ('.$num.')</td>';
                print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/commande/stats/index.php?mode=supplier&socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
    			print '</tr></table>';
    			print '</td></tr>';
			}

			while ($i < $num && $i <= $MAXLIST)
			{
				$obj = $db->fetch_object($resql);
				$var=!$var;

				print "<tr $bc[$var]>";
				print '<td><a href="commande/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowOrder"),"order")." ".$obj->ref.'</a></td>';
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
	 * Last invoices
	 */
	$MAXLIST=5;

	$langs->load('bills');
	$facturestatic = new FactureFournisseur($db);

	if ($user->rights->fournisseur->facture->lire)
	{
		// TODO move to DAO class
		$sql = 'SELECT f.rowid,f.libelle,f.ref_supplier,f.fk_statut,f.datef as df,f.total_ttc as amount,f.paye,';
		$sql.= ' SUM(pf.amount) as am';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON f.rowid=pf.fk_facturefourn';
		$sql.= ' WHERE f.fk_soc = '.$object->id;
		$sql.= ' GROUP BY f.rowid,f.libelle,f.ref_supplier,f.fk_statut,f.datef,f.total_ttc,f.paye';
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
    			print '<table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans('LastSuppliersBills',($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="facture/index.php?socid='.$object->id.'">'.$langs->trans('AllBills').' ('.$num.')</td>';
                print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?mode=supplier&socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
    			print '</tr></table>';
    			print '</td></tr>';
			}
			while ($i < min($num,$MAXLIST))
			{
				$obj = $db->fetch_object($resql);
				$var=!$var;
				print '<tr '.$bc[$var].'>';
				print '<td>';
				print '<a href="facture/fiche.php?facid='.$obj->rowid.'">';
				print img_object($langs->trans('ShowBill'),'bill').' '.$obj->ref_supplier.'</a> '.dol_trunc($obj->libelle,14).'</td>';
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
	//print '</td></tr>';
	//print '</table>' . "\n";

	dol_fiche_end();


	/*
	 * Barre d'actions
	 */

	print '<div class="tabsAction">';

	if ($user->rights->fournisseur->commande->creer)
	{
		$langs->load("orders");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?action=create&socid='.$object->id.'">'.$langs->trans("AddOrder").'</a>';
	}

	if ($user->rights->fournisseur->facture->creer)
	{
		$langs->load("bills");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?action=create&socid='.$object->id.'">'.$langs->trans("AddBill").'</a>';
	}

    // Add action
    if (! empty($conf->agenda->enabled) && ! empty($conf->global->MAIN_REPEATTASKONEACHTAB))
    {
        if ($user->rights->agenda->myactions->create)
        {
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$object->id.'">'.$langs->trans("AddAction").'</a>';
        }
        else
        {
            print '<a class="butAction" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddAction").'</a>';
        }
    }

	print '</div>';
	print '<br>';

    if (! empty($conf->global->MAIN_REPEATCONTACTONEACHTAB))
    {
        print '<br>';
        // List of contacts
        show_contacts($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?id='.$object->id);
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
?>
