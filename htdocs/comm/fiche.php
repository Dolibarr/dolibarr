<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne                 <eric.seigne@ryxeo.com>
 * Copyright (C) 2006      Andre Cianfarani            <acianfa@free.fr>
 * Copyright (C) 2005-2012 Regis Houssin               <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2012 Juanjo Menent               <jmenent@2byte.es>
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
 *       \file       htdocs/comm/fiche.php
 *       \ingroup    commercial compta
 *       \brief      Page to show customer card of a third party
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
if (! empty($conf->facture->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
if (! empty($conf->propal->enabled)) require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->contrat->enabled)) require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
if (! empty($conf->ficheinter->enabled)) require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';

$langs->load("companies");
if (! empty($conf->contrat->enabled))  $langs->load("contracts");
if (! empty($conf->commande->enabled)) $langs->load("orders");
if (! empty($conf->facture->enabled)) $langs->load("bills");
if (! empty($conf->projet->enabled))  $langs->load("projects");
if (! empty($conf->ficheinter->enabled)) $langs->load("interventions");
if (! empty($conf->notification->enabled)) $langs->load("mails");

// Security check
$id = (GETPOST('socid','int') ? GETPOST('socid','int') : GETPOST('id','int'));
if ($user->societe_id > 0) $id=$user->societe_id;
$result = restrictedArea($user,'societe',$id,'&societe');

$action		= GETPOST('action');
$mode		= GETPOST("mode");
$modesearch	= GETPOST("mode_search");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";

$object = new Societe($db);

/*
 * Actions
 */

if ($action == 'setcustomeraccountancycode')
{
	$result=$object->fetch($id);
	$object->code_compta=$_POST["customeraccountancycode"];
	$result=$object->update($object->id,$user,1,1,0);
	if ($result < 0)
	{
		$mesgs[]=join(',',$object->errors);
	}
	$action="";
}

// conditions de reglement
if ($action == 'setconditions' && $user->rights->societe->creer)
{
	$object->fetch($id);
	$result=$object->setPaymentTerms(GETPOST('cond_reglement_id','int'));
	if ($result < 0) dol_print_error($db,$object->error);
}
// mode de reglement
if ($action == 'setmode' && $user->rights->societe->creer)
{
	$object->fetch($id);
	$result=$object->setPaymentMethods(GETPOST('mode_reglement_id','int'));
	if ($result < 0) dol_print_error($db,$object->error);
}
// assujetissement a la TVA
if ($action == 'setassujtva' && $user->rights->societe->creer)
{
	$object->fetch($id);
	$object->tva_assuj=$_POST['assujtva_value'];

	// TODO move to DAO class
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET tva_assuj='".$_POST['assujtva_value']."' WHERE rowid='".$id."'";
	$result = $db->query($sql);
	if (! $result) dol_print_error($result);
}

// set prospect level
if ($action == 'setprospectlevel' && $user->rights->societe->creer)
{
	$object->fetch($id);
	$object->fk_prospectlevel=GETPOST('prospect_level_id','alpha');
	$result=$object->set_prospect_level($user);
	if ($result < 0) setEventMessage($object->error,'errors');
}

// Update communication level
if ($action == 'cstc')
{
	$object->fetch($id);
	$object->stcomm_id=GETPOST('stcomm','int');
	$result=$object->set_commnucation_level($user);
	if ($result < 0) setEventMessage($object->error,'errors');
}

// Update communication level
if ($action == 'setOutstandingBill')
{
	$object->fetch($id);
	$object->outstanding_limit=GETPOST('OutstandingBill');
	$result=$object->set_OutstandingBill($user);
	if ($result < 0) setEventMessage($object->error,'errors');
}


/*
 * View
 */

llxHeader('',$langs->trans('CustomerCard'));


$contactstatic = new Contact($db);
$userstatic=new User($db);
$form = new Form($db);
$formcompany=new FormCompany($db);


if ($mode == 'search')
{
	if ($modesearch == 'soc')
	{
		// TODO move to DAO class
		$sql = "SELECT s.rowid";
		if (!$user->rights->societe->client->voir && !$id) $sql .= ", sc.fk_soc, sc.fk_user ";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
		if (!$user->rights->societe->client->voir && !$id) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	}

	$resql=$db->query($sql);
	if ($resql)
	{
		if ( $db->num_rows($resql) == 1)
		{
			$obj = $db->fetch_object($resql);
			$id = $obj->rowid;
		}
		$db->free($resql);
	}
}


if ($id > 0)
{
	// Load data of third party
	$object->fetch($id);
	if ($object->id <= 0)
	{
		dol_print_error($db,$object->error);
	}

	/*
	 * Affichage onglets
	 */

	$head = societe_prepare_head($object);

	dol_fiche_head($head, 'customer', $langs->trans("ThirdParty"),0,'company');


	print '<div class="fichecenter"><div class="fichehalfleft">';
	//print '<table width="100%" class="notopnoleftnoright">';
	//print '<tr><td valign="top" class="notopnoleft">';

	print '<table class="border" width="100%">';

	print '<tr><td width="30%">'.$langs->trans("ThirdPartyName").'</td><td width="70%" colspan="3">';
	$object->next_prev_filter="te.client in (1,3)";
	print $form->showrefnav($object,'socid','',($user->societe_id?0:1),'rowid','nom','','');
	print '</td></tr>';

	// Prospect/Customer
	print '<tr><td width="30%">'.$langs->trans('ProspectCustomer').'</td><td width="70%" colspan="3">';
	print $object->getLibCustProspStatut();
	print '</td></tr>';

	// Prefix
    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans("Prefix").'</td><td colspan="3">';
	   print ($object->prefix_comm?$object->prefix_comm:'&nbsp;');
	   print '</td></tr>';
    }

	if ($object->client)
	{
        $langs->load("compta");

		print '<tr><td>';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $object->code_client;
		if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';

		print '<tr>';
		print '<td>';
		print $form->editfieldkey("CustomerAccountancyCode",'customeraccountancycode',$object->code_compta,$object,$user->rights->societe->creer);
		print '</td><td colspan="3">';
		print $form->editfieldval("CustomerAccountancyCode",'customeraccountancycode',$object->code_compta,$object,$user->rights->societe->creer);
		print '</td>';
		print '</tr>';
	}

	// Address
	print '<tr><td valign="top">'.$langs->trans('Address').'</td><td colspan="3">';
	dol_print_address($object->address,'gmap','thirdparty',$object->id);
	print "</td></tr>";

	// Zip / Town
	print '<tr><td class="nowrap">'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td>';
	print '<td colspan="3">'.$object->zip.(($object->zip && $object->town)?' / ':'').$object->town."</td>";
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
	print '<tr><td>'.$langs->trans("Web").'</td><td colspan="3">'.dol_print_url($object->url,'_blank').'</td></tr>';

	// Phone
	print '<tr><td>'.$langs->trans('Phone').'</td><td style="min-width: 25%;">'.dol_print_phone($object->phone,$object->country_code,0,$object->id,'AC_TEL').'</td>';

	// Fax
	print '<td>'.$langs->trans('Fax').'</td><td style="min-width: 25%;">'.dol_print_phone($object->fax,$object->country_code,0,$object->id,'AC_FAX').'</td></tr>';

	// Assujeti a TVA ou pas
	print '<tr>';
	print '<td class="nowrap">'.$langs->trans('VATIsUsed').'</td><td colspan="3">';
	print yn($object->tva_assuj);
	print '</td>';
	print '</tr>';

	// Local Taxes
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
	}

	// TVA Intra
	print '<tr><td class="nowrap">'.$langs->trans('VATIntra').'</td><td colspan="3">';
	print $object->tva_intra;
	print '</td></tr>';

	// Conditions de reglement par defaut
	$langs->load('bills');
	print '<tr><td>';
	print '<table width="100%" class="nobordernopadding"><tr><td>';
	print $langs->trans('PaymentConditions');
	print '<td>';
	if (($action != 'editconditions') && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;socid='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editconditions')
	{
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->cond_reglement_id,'cond_reglement_id',-1,1);
	}
	else
	{
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->cond_reglement_id,'none');
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
	print '</td><td colspan="3">';
	if ($action == 'editmode')
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->mode_reglement_id,'mode_reglement_id');
	}
	else
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->mode_reglement_id,'none');
	}
	print "</td>";
	print '</tr>';

	// Relative discounts (Discounts-Drawbacks-Rebates)
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans("CustomerRelativeDiscountShort");
	print '<td><td align="right">';
	if ($user->rights->societe->creer && !$user->societe_id > 0)
	{
		print '<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$object->id.'">'.img_edit($langs->trans("Modify")).'</a>';
	}
	print '</td></tr></table>';
	print '</td><td colspan="3">'.($object->remise_percent?'<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$object->id.'">'.$object->remise_percent.'%</a>':$langs->trans("DiscountNone")).'</td>';
	print '</tr>';

	// Absolute discounts (Discounts-Drawbacks-Rebates)
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding">';
	print '<tr><td class="nowrap">';
	print $langs->trans("CustomerAbsoluteDiscountShort");
	print '<td><td align="right">';
	if ($user->rights->societe->creer && !$user->societe_id > 0)
	{
		print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$object->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$object->id).'">'.img_edit($langs->trans("Modify")).'</a>';
	}
	print '</td></tr></table>';
	print '</td>';
	print '<td colspan="3">';
	$amount_discount=$object->getAvailableDiscounts();
	if ($amount_discount < 0) dol_print_error($db,$object->error);
	if ($amount_discount > 0) print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$object->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$object->id).'">'.price($amount_discount).'</a>&nbsp;'.$langs->trans("Currency".$conf->currency);
	else print $langs->trans("DiscountNone");
	print '</td>';
	print '</tr>';

	if ($object->client)
	{
		print '<tr>';
		print '<td>';
		print $form->editfieldkey("OutstandingBill",'OutstandingBill',$object->outstanding_limit,$object,$user->rights->societe->creer);
		print '</td><td colspan="3">';
		print $form->editfieldval("OutstandingBill",'OutstandingBill',$object->outstanding_limit,$object,$user->rights->societe->creer);
		print '</td>';
		print '</tr>';
	}


	// Multiprice level
	if (! empty($conf->global->PRODUIT_MULTIPRICES))
	{
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans("PriceLevel");
		print '<td><td align="right">';
		if ($user->rights->societe->creer)
		{
			print '<a href="'.DOL_URL_ROOT.'/comm/multiprix.php?id='.$object->id.'">'.img_edit($langs->trans("Modify")).'</a>';
		}
		print '</td></tr></table>';
		print '</td><td colspan="3">'.$object->price_level."</td>";
		print '</tr>';
	}

	// Level of prospect
	if ($object->client == 2 || $object->client == 3)
	{
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans('ProspectLevel');
		print '<td>';
		if ($action != 'editlevel' && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editlevel&amp;socid='.$object->id.'">'.img_edit($langs->trans('Modify'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($action == 'editlevel')
			$formcompany->form_prospect_level($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->fk_prospectlevel,'prospect_level_id',1);
		else
			print $object->getLibProspLevel();
		print "</td>";
		print '</tr>';

		// Status
		print '<tr><td>'.$langs->trans("StatusProsp").'</td><td colspan="2">'.$object->getLibProspCommStatut(4).'</td>';
		print '<td>';
		if ($object->stcomm_id != -1) print '<a href="fiche.php?socid='.$object->id.'&amp;stcomm=-1&amp;action=cstc">'.img_action(0,-1).'</a>';
		if ($object->stcomm_id !=  0) print '<a href="fiche.php?socid='.$object->id.'&amp;stcomm=0&amp;action=cstc">'.img_action(0,0).'</a>';
		if ($object->stcomm_id !=  1) print '<a href="fiche.php?socid='.$object->id.'&amp;stcomm=1&amp;action=cstc">'.img_action(0,1).'</a>';
		if ($object->stcomm_id !=  2) print '<a href="fiche.php?socid='.$object->id.'&amp;stcomm=2&amp;action=cstc">'.img_action(0,2).'</a>';
		if ($object->stcomm_id !=  3) print '<a href="fiche.php?socid='.$object->id.'&amp;stcomm=3&amp;action=cstc">'.img_action(0,3).'</a>';
		print '</td></tr>';
	}

	// Sales representative
	include DOL_DOCUMENT_ROOT.'/societe/tpl/linesalesrepresentative.tpl.php';

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

	print "</table>";


	print '</div><div class="fichehalfright"><div class="ficheaddleft">';
	//print "</td>\n";
	//print '<td valign="top" width="50%" class="notopnoleftnoright">';


	// Nbre max d'elements des petites listes
	$MAXLIST=4;
	$tableaushown=1;

	// Lien recap
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("Summary").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/compta/recap-compta.php?socid='.$object->id.'">'.$langs->trans("ShowCustomerPreview").'</a></td></tr></table></td>';
	print '</tr>';
	print '</table>';
	print '<br>';

	$now=dol_now();

	/*
	 * Last proposals
	 */
	if (! empty($conf->propal->enabled) && $user->rights->propal->lire)
	{
		$propal_static = new Propal($db);

		$sql = "SELECT s.nom, s.rowid, p.rowid as propalid, p.fk_statut, p.total_ht, p.ref, p.remise, ";
		$sql.= " p.datep as dp, p.fin_validite as datelimite";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c";
		$sql.= " WHERE p.fk_soc = s.rowid AND p.fk_statut = c.id";
		$sql.= " AND s.rowid = ".$object->id;
		$sql.= " AND p.entity = ".$conf->entity;
		$sql.= " ORDER BY p.datep DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);

            if ($num > 0)
            {
		        print '<table class="noborder" width="100%">';

                print '<tr class="liste_titre">';
    			print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastPropals",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/comm/propal/list.php?socid='.$object->id.'">'.$langs->trans("AllPropals").' ('.$num.')</a></td>';
                print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/comm/propal/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
    			print '</tr></table></td>';
    			print '</tr>';
            }

			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);
				print "<tr ".$bc[$var].">";
				print '<td class="nowrap"><a href="propal.php?id='.$objp->propalid.'">'.img_object($langs->trans("ShowPropal"),"propal").' '.$objp->ref.'</a>'."\n";
				if ( ($db->jdate($objp->dp) < ($now - $conf->propal->cloture->warning_delay)) && $objp->fk_statut == 1 )
				{
					print " ".img_warning();
				}
				print '</td><td align="right" width="80">'.dol_print_date($db->jdate($objp->dp),'day')."</td>\n";
				print '<td align="right" style="min-width: 60px">'.price($objp->total_ht).'</td>';
				print '<td align="right" style="min-width: 60px" class="nowrap">'.$propal_static->LibStatut($objp->fk_statut,5).'</td></tr>';
				$var=!$var;
				$i++;
			}
			$db->free($resql);

			if ($num > 0) print "</table>";
		}
		else
		{
			dol_print_error($db);
		}
	}

	/*
	 * Last orders
	 */
	if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
	{
		$commande_static=new Commande($db);

		$sql = "SELECT s.nom, s.rowid,";
		$sql.= " c.rowid as cid, c.total_ht, c.ref, c.fk_statut, c.facture,";
		$sql.= " c.date_commande as dc";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
		$sql.= " WHERE c.fk_soc = s.rowid ";
		$sql.= " AND s.rowid = ".$object->id;
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " ORDER BY c.date_commande DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);

			if ($num > 0)
			{
				// Check if there are orders billable
				$sql2 = 'SELECT s.nom, s.rowid as socid, s.client, c.rowid, c.ref, c.total_ht, c.ref_client,';
				$sql2.= ' c.date_valid, c.date_commande, c.date_livraison, c.fk_statut, c.facture as facturee';
				$sql2.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
				$sql2.= ', '.MAIN_DB_PREFIX.'commande as c';
				$sql2.= ' WHERE c.fk_soc = s.rowid';
				$sql2.= ' AND s.rowid = '.$object->id;
				// Show orders with status validated, shipping started and delivered (well any order we can bill)
				$sql2.= " AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))";

				$resql2=$db->query($sql2);
				$orders2invoice = $db->num_rows($resql2);
				$db->free($resql2);

				print '<table class="noborder" width="100%">';

				print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastOrders",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/commande/liste.php?socid='.$object->id.'">'.$langs->trans("AllOrders").' ('.$num.')</a></td>';
				print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/commande/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
				//if($num2 > 0) print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/commande/orderstoinvoice.php?socid='.$object->id.'">'.img_picto($langs->trans("CreateInvoiceForThisCustomer"),'object_bill').'</a></td>';
				//else print '<td width="20px" align="right"><a href="#">'.img_picto($langs->trans("NoOrdersToInvoice"),'object_bill').'</a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr ".$bc[$var].">";
				print '<td class="nowrap"><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$objp->cid.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$objp->ref."</a>\n";
				print '</td><td align="right" width="80">'.dol_print_date($db->jdate($objp->dc),'day')."</td>\n";
				print '<td align="right" style="min-width: 60px">'.price($objp->total_ht).'</td>';
				print '<td align="right" style="min-width: 60px" class="nowrap">'.$commande_static->LibStatut($objp->fk_statut,$objp->facture,5).'</td></tr>';
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
	 * Last linked contracts
	 */
	if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire)
	{
		$contratstatic=new Contrat($db);

		$sql = "SELECT s.nom, s.rowid, c.rowid as id, c.ref as ref, c.statut, c.datec as dc";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as c";
		$sql.= " WHERE c.fk_soc = s.rowid ";
		$sql.= " AND s.rowid = ".$object->id;
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " ORDER BY c.datec DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);
			if ($num >0 )
			{
		        print '<table class="noborder" width="100%">';

			    print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastContracts",($num<=$MAXLIST?"":$MAXLIST)).'</td>';
				print '<td align="right"><a href="'.DOL_URL_ROOT.'/contrat/liste.php?socid='.$object->id.'">'.$langs->trans("AllContracts").' ('.$num.')</a></td></tr></table></td>';
				print '</tr>';
			}
			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$contrat=new Contrat($db);

				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr ".$bc[$var].">";
				print '<td class="nowrap">';
				$contrat->id=$objp->id;
				$contrat->ref=$objp->ref?$objp->ref:$objp->id;
				print $contrat->getNomUrl(1,12);
				print "</td>\n";
				print '<td align="right" width="80">'.dol_print_date($db->jdate($objp->dc),'day')."</td>\n";
				print '<td width="20">&nbsp;</td>';
				print '<td align="right" class="nowrap">';
				$contrat->fetch_lines();
				print $contrat->getLibStatut(4);
				print "</td>\n";
				print '</tr>';
				$i++;
			}
			$db->free($resql);

			if ($num > 0) print "</table>";
		}
		else
		{
			dol_print_error($db);
		}
	}

	/*
	 * Last interventions
	 */
	if (! empty($conf->ficheinter->enabled) && $user->rights->ficheinter->lire)
	{
		$sql = "SELECT s.nom, s.rowid, f.rowid as id, f.ref, f.fk_statut, f.duree as duration, f.datei as startdate";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."fichinter as f";
		$sql.= " WHERE f.fk_soc = s.rowid";
		$sql.= " AND s.rowid = ".$object->id;
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " ORDER BY f.tms DESC";

		$fichinter_static=new Fichinter($db);

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);
			if ($num > 0)
			{
		        print '<table class="noborder" width="100%">';

			    print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastInterventions",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/fichinter/list.php?socid='.$object->id.'">'.$langs->trans("AllInterventions").' ('.$num.')</td></tr></table></td>';
				print '</tr>';
				$var=!$var;
			}
			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);

				$fichinter_static->id=$objp->id;
                $fichinter_static->statut=$objp->fk_statut;

				print "<tr ".$bc[$var].">";
				print '<td class="nowrap"><a href="'.DOL_URL_ROOT.'/fichinter/fiche.php?id='.$objp->id.'">'.img_object($langs->trans("ShowPropal"),"propal").' '.$objp->ref.'</a></td>'."\n";
                //print '<td align="right" width="80">'.dol_print_date($db->jdate($objp->startdate)).'</td>'."\n";
				print '<td align="right" width="120">'.convertSecondToTime($objp->duration).'</td>'."\n";
				print '<td align="right" width="100">'.$fichinter_static->getLibStatut(5).'</td>'."\n";
				print '</tr>';
				$var=!$var;
				$i++;
			}
			$db->free($resql);

			if ($num > 0) print "</table>";
		}
		else
		{
			dol_print_error($db);
		}
	}

	/*
	 *   Last invoices
	 */
	if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
	{
		$facturestatic = new Facture($db);

		$sql = 'SELECT f.rowid as facid, f.facnumber, f.type, f.amount, f.total, f.total_ttc,';
		$sql.= ' f.datef as df, f.datec as dc, f.paye as paye, f.fk_statut as statut,';
		$sql.= ' s.nom, s.rowid as socid,';
		$sql.= ' SUM(pf.amount) as am';
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON f.rowid=pf.fk_facture';
		$sql.= " WHERE f.fk_soc = s.rowid AND s.rowid = ".$object->id;
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= ' GROUP BY f.rowid, f.facnumber, f.type, f.amount, f.total, f.total_ttc,';
		$sql.= ' f.datef, f.datec, f.paye, f.fk_statut,';
		$sql.= ' s.nom, s.rowid';
		$sql.= " ORDER BY f.datef DESC, f.datec DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);
			$i = 0;
			if ($num > 0)
			{
		        print '<table class="noborder" width="100%">';

			    $tableaushown=1;
				print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastCustomersBills",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->id.'">'.$langs->trans("AllBills").' ('.$num.')</a></td>';
                print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr ".$bc[$var].">";
				print '<td class="nowrap">';
				$facturestatic->id=$objp->facid;
				$facturestatic->ref=$objp->facnumber;
				$facturestatic->type=$objp->type;
				print $facturestatic->getNomUrl(1);
				print '</td>';
				if ($objp->df > 0)
				{
					print '<td align="right" width="80">'.dol_print_date($db->jdate($objp->df),'day').'</td>';
				}
				else
				{
					print '<td align="right"><b>!!!</b></td>';
				}
				print '<td align="right" width="120">'.price($objp->total_ttc).'</td>';

				print '<td align="right" class="nowrap" width="100" >'.($facturestatic->LibStatut($objp->paye,$objp->statut,5,$objp->am)).'</td>';
				print "</tr>\n";
				$i++;
			}
			$db->free($resql);

			if ($num > 0) print "</table>";
		}
		else
		{
			dol_print_error($db);
		}
	}

	print '</div></div></div>';
	print '<div style="clear:both"></div>';
	//print "</td></tr>";
	//print "</table>";

	dol_fiche_end();


	/*
	 * Barre d'actions
	 */
	print '<div class="tabsAction">';

	if (! empty($conf->propal->enabled) && $user->rights->propal->creer)
	{
		$langs->load("propal");
		print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/comm/propal.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddProp").'</a></div>';
	}

	if (! empty($conf->commande->enabled) && $user->rights->commande->creer)
	{
		$langs->load("orders");
		print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/commande/fiche.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddOrder").'</a></div>';
	}

	if ($user->rights->contrat->creer)
	{
		$langs->load("contracts");
		print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/contrat/fiche.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddContract").'</a></div>';
	}

	if (! empty($conf->ficheinter->enabled) && $user->rights->ficheinter->creer)
	{
		$langs->load("fichinter");
		print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/fichinter/fiche.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddIntervention").'</a></div>';
	}

	// Add invoice
	if ($user->societe_id == 0)
	{
		if (! empty($conf->deplacement->enabled))
		{
			$langs->load("trips");
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/deplacement/fiche.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddTrip").'</a></div>';
		}

		if (! empty($conf->facture->enabled))
		{
			if ($user->rights->facture->creer)
			{
				$langs->load("bills");
				$langs->load("orders");

				if (! empty($conf->commande->enabled))
				{
					if (! empty($orders2invoice) && $orders2invoice > 0) print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/commande/orderstoinvoice.php?socid='.$object->id.'">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
					else print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.dol_escape_js($langs->trans("NoOrdersToInvoice")).'" href="#">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
				}

				if ($object->client != 0) print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&socid='.$object->id.'">'.$langs->trans("AddBill").'</a></div>';
				else print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")).'" href="#">'.$langs->trans("AddBill").'</a></div>';

			}
			else
			{
				print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddBill").'</a></div>';
			}
		}
	}

	// Add action
	if (! empty($conf->agenda->enabled) && ! empty($conf->global->MAIN_REPEATTASKONEACHTAB))
	{
		if ($user->rights->agenda->myactions->create)
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$object->id.'">'.$langs->trans("AddAction").'</a></div>';
		}
		else
		{
			print '<div class="inline-block divButAction"><a class="butAction" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddAction").'</a></div>';
		}
	}

	print '</div>';
	print "<br>\n";

	if (! empty($conf->global->MAIN_REPEATCONTACTONEACHTAB))
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
	dol_print_error($db,'Bad value for socid parameter');
}

dol_htmloutput_mesg('',$mesgs);

// End of page
llxFooter();
$db->close();
?>
