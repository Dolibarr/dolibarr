<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne                 <eric.seigne@ryxeo.com>
 * Copyright (C) 2006      Andre Cianfarani            <acianfa@free.fr>
 * Copyright (C) 2005-2012 Regis Houssin               <regis@dolibarr.fr>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2011 Juanjo Menent               <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/comm/fiche.php
 *       \ingroup    commercial compta
 *       \brief      Page to show customer card of a third party
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
if ($conf->adherent->enabled) require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
if ($conf->ficheinter->enabled) require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");

$langs->load("companies");
if ($conf->contrat->enabled)  $langs->load("contracts");
if ($conf->commande->enabled) $langs->load("orders");
if ($conf->facture->enabled) $langs->load("bills");
if ($conf->projet->enabled)  $langs->load("projects");
if ($conf->ficheinter->enabled) $langs->load("interventions");
if ($conf->notification->enabled) $langs->load("mails");

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
		$mesg=join(',',$object->errors);
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



/*
 * View
 */

llxHeader('',$langs->trans('CustomerCard'));


$contactstatic = new Contact($db);
$userstatic=new User($db);
$form = new Form($db);


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

	if ($errmesg)
	{
		print "<b>".$errmesg."</b><br>";
	}

	/*
	 * Affichage onglets
	 */

	$head = societe_prepare_head($object);

	dol_fiche_head($head, 'customer', $langs->trans("ThirdParty"),0,'company');


	print '<table width="100%" class="notopnoleftnoright">';
	print '<tr><td valign="top" class="notopnoleft">';

	print '<table class="border" width="100%">';

	print '<tr><td width="30%">'.$langs->trans("ThirdPartyName").'</td><td width="70%" colspan="3">';
	$object->next_prev_filter="te.client in (1,3)";
	print $form->showrefnav($object,'socid','',($user->societe_id?0:1),'rowid','nom','','');
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

		print '<tr><td nowrap>';
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
	print '<tr><td nowrap="nowrap">'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td>';
	print '<td colspan="3">'.$object->zip.(($object->zip && $object->town)?' / ':'').$object->town."</td>";
	print '</tr>';

	// Country
	print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
	$img=picto_from_langcode($object->country_code);
	if ($object->isInEEC()) print $form->textwithpicto(($img?$img.' ':'').$object->country,$langs->trans("CountryIsInEEC"),1,0);
	else print ($img?$img.' ':'').$object->country;
	print '</td></tr>';

	// Phone
	print '<tr><td>'.$langs->trans('Phone').'</td><td style="min-width: 25%;">'.dol_print_phone($object->tel,$object->country_code,0,$object->id,'AC_TEL').'</td>';

	// Fax
	print '<td>'.$langs->trans('Fax').'</td><td style="min-width: 25%;">'.dol_print_phone($object->fax,$object->country_code,0,$object->id,'AC_FAX').'</td></tr>';

	// EMail
	print '<td>'.$langs->trans('EMail').'</td><td colspan="3">'.dol_print_email($object->email,0,$object->id,'AC_EMAIL').'</td></tr>';

	// Web
	print '<tr><td>'.$langs->trans("Web").'</td><td colspan="3">'.dol_print_url($object->url,'_blank').'</td></tr>';

	// Assujeti a TVA ou pas
	print '<tr>';
	print '<td nowrap="nowrap">'.$langs->trans('VATIsUsed').'</td><td colspan="3">';
	print yn($object->tva_assuj);
	print '</td>';
	print '</tr>';

	// Local Taxes
	if($mysoc->country_code=='ES')
	{
		if($mysoc->localtax1_assuj=="1" && $mysoc->localtax2_assuj=="1")
		{
			print '<tr><td nowrap="nowrap">'.$langs->trans('LocalTax1IsUsedES').'</td><td colspan="3">';
			print yn($object->localtax1_assuj);
			print '</td></tr>';
			print '<tr><td nowrap="nowrap">'.$langs->trans('LocalTax2IsUsedES').'</td><td colspan="3">';
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
	print '<tr><td nowrap>'.$langs->trans('VATIntraVeryShort').'</td><td colspan="3">';
	print $object->tva_intra;
	print '</td></tr>';

	// Conditions de reglement par defaut
	$langs->load('bills');
	$form = new Form($db);
	print '<tr><td nowrap>';
	print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
	print $langs->trans('PaymentConditions');
	print '<td>';
	if (($action != 'editconditions') && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;socid='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editconditions')
	{
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->cond_reglement,'cond_reglement_id',-1,1);
	}
	else
	{
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->cond_reglement,'none');
	}
	print "</td>";
	print '</tr>';

	// Mode de reglement par defaut
	print '<tr><td nowrap>';
	print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
	print $langs->trans('PaymentMode');
	print '<td>';
	if (($action != 'editmode') && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;socid='.$object->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editmode')
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->mode_reglement,'mode_reglement_id');
	}
	else
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->mode_reglement,'none');
	}
	print "</td>";
	print '</tr>';

	// Relative discounts (Discounts-Drawbacks-Rebates)
	print '<tr><td nowrap>';
	print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
	print $langs->trans("CustomerRelativeDiscountShort");
	print '<td><td align="right">';
	if ($user->rights->societe->creer && !$user->societe_id > 0)
	{
		print '<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$object->id.'">'.img_edit($langs->trans("Modify")).'</a>';
	}
	print '</td></tr></table>';
	print '</td><td colspan="3">'.($object->remise_client?'<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$object->id.'">'.$object->remise_client.'%</a>':$langs->trans("DiscountNone")).'</td>';
	print '</tr>';

	// Absolute discounts (Discounts-Drawbacks-Rebates)
	print '<tr><td nowrap>';
	print '<table width="100%" class="nobordernopadding">';
	print '<tr><td nowrap>';
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

	// Multiprice level
	if ($conf->global->PRODUIT_MULTIPRICES)
	{
		print '<tr><td nowrap>';
		print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
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

    // Module Adherent
    if ($conf->adherent->enabled)
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

	print "</td>\n";


	print '<td valign="top" width="50%" class="notopnoleftnoright">';

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
	if ($conf->propal->enabled && $user->rights->propale->lire)
	{
		$propal_static = new Propal($db);

		$proposals = $propal_static->liste_array(0, 0, 0, $object->id, $MAXLIST);

		//var_dump($proposals);

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
    			print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastPropals",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/comm/propal.php?socid='.$object->id.'">'.$langs->trans("AllPropals").' ('.$num.')</a></td>';
                print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/comm/propal/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
    			print '</tr></table></td>';
    			print '</tr>';
            }

			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);
				print "<tr $bc[$var]>";
				print '<td nowrap><a href="propal.php?id='.$objp->propalid.'">'.img_object($langs->trans("ShowPropal"),"propal").' '.$objp->ref.'</a>'."\n";
				if ( ($db->jdate($objp->dp) < ($now - $conf->propal->cloture->warning_delay)) && $objp->fk_statut == 1 )
				{
					print " ".img_warning();
				}
				print '</td><td align="right" width="80">'.dol_print_date($db->jdate($objp->dp),'day')."</td>\n";
				print '<td align="right" width="120">'.price($objp->total_ht).'</td>';
				print '<td align="right" nowrap="nowrap">'.$propal_static->LibStatut($objp->fk_statut,5).'</td></tr>';
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
	if ($conf->commande->enabled && $user->rights->commande->lire)
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
        		print '<table class="noborder" width="100%">';

			    print '<tr class="liste_titre">';
    			print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastOrders",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/commande/liste.php?socid='.$object->id.'">'.$langs->trans("AllOrders").' ('.$num.')</a></td>';
                print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/commande/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
    			print '</tr></table></td>';
    			print '</tr>';
			}

			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr $bc[$var]>";
				print '<td nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$objp->cid.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$objp->ref."</a>\n";
				print '</td><td align="right" width="80">'.dol_print_date($db->jdate($objp->dc),'day')."</td>\n";
				print '<td align="right" width="120">'.price($objp->total_ht).'</td>';
				print '<td align="right" width="100">'.$commande_static->LibStatut($objp->fk_statut,$objp->facture,5).'</td></tr>';
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
	if ($conf->contrat->enabled && $user->rights->contrat->lire)
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
				print "<tr $bc[$var]>";
				print '<td nowrap="nowrap">';
				$contrat->id=$objp->id;
				$contrat->ref=$objp->ref?$objp->ref:$objp->id;
				print $contrat->getNomUrl(1,12);
				print "</td>\n";
				print '<td align="right" width="80">'.dol_print_date($db->jdate($objp->dc),'day')."</td>\n";
				print '<td width="20">&nbsp;</td>';
				print '<td align="right" nowrap="nowrap">';
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
	if ($conf->ficheinter->enabled && $user->rights->ficheinter->lire)
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
				print '<td nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/fichinter/fiche.php?id='.$objp->id.'">'.img_object($langs->trans("ShowPropal"),"propal").' '.$objp->ref.'</a></td>'."\n";
                //print '<td align="right">'.dol_print_date($db->jdate($objp->startdate)).'</td>'."\n";
				print '<td align="right">'.convertSecondToTime($objp->duration).'</td>'."\n";
				print '<td align="right">'.$fichinter_static->getLibStatut(3).'</td>'."\n";
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
	if ($conf->facture->enabled && $user->rights->facture->lire)
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
				print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastCustomersBills",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$object->id.'">'.$langs->trans("AllBills").' ('.$num.')</a></td>';
                print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr $bc[$var]>";
				print '<td>';
				$facturestatic->id=$objp->facid;
				$facturestatic->ref=$objp->facnumber;
				$facturestatic->type=$objp->type;
				print $facturestatic->getNomUrl(1);
				print '</td>';
				if ($objp->df > 0)
				{
					print "<td align=\"right\">".dol_print_date($db->jdate($objp->df),'day')."</td>\n";
				}
				else
				{
					print "<td align=\"right\"><b>!!!</b></td>\n";
				}
				print "<td align=\"right\">".price($objp->total_ttc)."</td>\n";

				print '<td align="right" nowrap="nowrap">'.($facturestatic->LibStatut($objp->paye,$objp->statut,5,$objp->am))."</td>\n";
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

	print "</td></tr>";
	print "</table>";

	print "\n</div>\n";


	/*
	 * Barre d'actions
	 */
	print '<div class="tabsAction">';

	if ($conf->propal->enabled && $user->rights->propale->creer)
	{
		$langs->load("propal");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/addpropal.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddProp").'</a>';
	}

	if ($conf->commande->enabled && $user->rights->commande->creer)
	{
		$langs->load("orders");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/commande/fiche.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddOrder").'</a>';
	}

	if ($user->rights->contrat->creer)
	{
		$langs->load("contracts");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/contrat/fiche.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddContract").'</a>';
	}

	if ($conf->ficheinter->enabled && $user->rights->ficheinter->creer)
	{
		$langs->load("fichinter");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/fichinter/fiche.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddIntervention").'</a>';
	}

	// Add invoice
	if ($user->societe_id == 0)
	{
		if ($conf->deplacement->enabled)
		{
			$langs->load("trips");
			print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/deplacement/fiche.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddTrip").'</a>';
		}

		if ($conf->facture->enabled)
		{
			if ($user->rights->facture->creer)
			{
				$langs->load("bills");
				if ($object->client != 0) print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&socid='.$object->id.'">'.$langs->trans("AddBill").'</a>';
				else print '<a class="butActionRefused" title="'.dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")).'" href="#">'.$langs->trans("AddBill").'</a>';
			}
			else
			{
				print '<a class="butActionRefused" title="'.dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")).'" href="#">'.$langs->trans("AddBill").'</a>';
			}
		}
	}

	// Add action
	if ($conf->agenda->enabled && ! empty($conf->global->MAIN_REPEATTASKONEACHTAB))
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
	print "<br>\n";

	if (! empty($conf->global->MAIN_REPEATCONTACTONEACHTAB))
	{
	    print '<br>';
		// List of contacts
		show_contacts($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
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


llxFooter();

$db->close();
?>
