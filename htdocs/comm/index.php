<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2020      Pierre Ardoin        <mapiolca@me.com>
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
 *	\file       htdocs/comm/index.php
 *	\ingroup    commercial
 *	\brief      Home page of commercial area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
if (! empty($conf->contrat->enabled)) require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
if (! empty($conf->propal->enabled))  require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->supplier_proposal->enabled))  require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
if (! empty($conf->commande->enabled))  require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->fournisseur->enabled)) require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

if (! $user->rights->societe->lire) accessforbidden();

// Load translation files required by the page
$langs->loadLangs(array("commercial", "propal"));

$action=GETPOST('action', 'alpha');
$bid=GETPOST('bid', 'int');

// Securite acces client
$socid=GETPOST('socid', 'int');
if (isset($user->societe_id) && $user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}

$max=3;
$now=dol_now();

/*
 * Actions
 */


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$companystatic=new Societe($db);
if (! empty($conf->propal->enabled)) $propalstatic=new Propal($db);
if (! empty($conf->supplier_proposal->enabled)) $supplierproposalstatic=new SupplierProposal($db);
if (! empty($conf->commande->enabled)) $orderstatic=new Commande($db);
if (! empty($conf->fournisseur->enabled)) $supplierorderstatic=new CommandeFournisseur($db);

llxHeader("", $langs->trans("CommercialArea"));

print load_fiche_titre($langs->trans("CommercialArea"), '', 'title_commercial.png');

print '<div class="fichecenter"><div class="fichethirdleft">';

if (! empty($conf->global->MAIN_SEARCH_FORM_ON_HOME_AREAS))     // This is useless due to the global search combo
{
    // Search proposal
    if (! empty($conf->propal->enabled) && $user->rights->propal->lire)
    {
    	$listofsearchfields['search_proposal']=array('text'=>'Proposal');
    }
    // Search customer order
    if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
    {
    	$listofsearchfields['search_customer_order']=array('text'=>'CustomerOrder');
    }
    // Search supplier proposal
    if (! empty($conf->supplier_proposal->enabled) && $user->rights->supplier_proposal->lire)
    {
        $listofsearchfields['search_supplier_proposal']=array('text'=>'SupplierProposalShort');
    }
    // Search supplier order
    if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->lire)
    {
    	$listofsearchfields['search_supplier_order']=array('text'=>'SupplierOrder');
    }
    // Search intervention
    if (! empty($conf->ficheinter->enabled) && $user->rights->ficheinter->lire)
    {
    	$listofsearchfields['search_intervention']=array('text'=>'Intervention');
    }
    // Search contract
    if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire)
    {
        $listofsearchfields['search_contract']=array('text'=>'Contract');
    }

    if (count($listofsearchfields))
    {
    	print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';
    	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    	print '<table class="noborder nohover centpercent">';
    	$i=0;
    	foreach($listofsearchfields as $key => $value)
    	{
    		if ($i == 0) print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
    		print '<tr '.$bc[false].'>';
    		print '<td class="nowrap"><label for="'.$key.'">'.$langs->trans($value["text"]).'</label></td><td><input type="text" class="flat inputsearch" name="'.$key.'" id="'.$key.'" size="18"></td>';
    		if ($i == 0) print '<td class="noborderbottom" rowspan="'.count($listofsearchfields).'"><input type="submit" value="'.$langs->trans("Search").'" class="button "></td>';
    		print '</tr>';
    		$i++;
    	}
    	print '</table>';
    	print '</form>';
    	print '<br>';
    }
}


/*
 * Draft proposals
 */
if (! empty($conf->propal->enabled) && $user->rights->propal->lire)
{
	$langs->load("propal");

	$sql = "SELECT p.rowid, p.ref, p.ref_client, p.total_ht, p.tva as total_tva, p.total as total_ttc, s.rowid as socid, s.nom as name, s.client, s.canvas";
    $sql.= ", s.code_client";
	$sql.= ", s.email";
    $sql.= ", s.entity";
    $sql.= ", s.code_compta";
	$sql.= " FROM ".MAIN_DB_PREFIX."propal as p";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE p.fk_statut = 0";
	$sql.= " AND p.fk_soc = s.rowid";
	$sql.= " AND p.entity IN (".getEntity('propal').")";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND s.rowid = ".$socid;

	$resql=$db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("ProposalsDraft").' <a href="'.DOL_URL_ROOT.'/comm/propal/list.php?viewstatut=0"><span class="badge">'.$num.'</span></a></th></tr>';

		if ($num > 0)
		{
			$i = 0;
			$nbofloop=min($num, (empty($conf->global->MAIN_MAXLIST_OVERLOAD)?500:$conf->global->MAIN_MAXLIST_OVERLOAD));
			while ($i < $nbofloop)
			{
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven"><td  class="nowrap">';
				$propalstatic->id=$obj->rowid;
				$propalstatic->ref=$obj->ref;
                $propalstatic->ref_client=$obj->ref_client;
                $propalstatic->total_ht = $obj->total_ht;
                $propalstatic->total_tva = $obj->total_tva;
                $propalstatic->total_ttc = $obj->total_ttc;
				print $propalstatic->getNomUrl(1);
				print '</td>';
				print '<td class="nowrap">';
				$companystatic->id=$obj->socid;
				$companystatic->name=$obj->name;
				$companystatic->client=$obj->client;
                $companystatic->code_client = $obj->code_client;
                $companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->entity = $obj->entity;
                $companystatic->email = $obj->email;
                $companystatic->code_compta = $obj->code_compta;
				$companystatic->canvas=$obj->canvas;
				print $companystatic->getNomUrl(1, 'customer', 16);
				print '</td>';
				print '<td class="nowrap right">'.price($obj->total_ht).'</td></tr>';
				$i++;
				$total += $obj->total_ht;
			}
			if ($num > $nbofloop)
			{
				print '<tr class="liste_total"><td colspan="3" class="right">'.$langs->trans("XMoreLines", ($num - $nbofloop))."</td></tr>";
			}
			elseif ($total>0)
			{
				print '<tr class="liste_total"><td colspan="2" class="right">'.$langs->trans("Total").'</td><td class="right">'.price($total)."</td></tr>";
			}
		}
		else
		{

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoProposal").'</td></tr>';
		}
		print "</table></div><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}



/*
 * Draft supplier proposals
 */
if (! empty($conf->supplier_proposal->enabled) && $user->rights->supplier_proposal->lire)
{
    $langs->load("supplier_proposal");

    $sql = "SELECT p.rowid, p.ref, p.total_ht, p.tva as total_tva, p.total as total_ttc, s.rowid as socid, s.nom as name, s.client, s.canvas";
    $sql.= ", s.code_client";
	$sql.= ", s.entity";
    $sql.= ", s.email";
    $sql.= " FROM ".MAIN_DB_PREFIX."supplier_proposal as p";
    $sql.= ", ".MAIN_DB_PREFIX."societe as s";
    if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= " WHERE p.fk_statut = 0";
    $sql.= " AND p.fk_soc = s.rowid";
    $sql.= " AND p.entity IN (".getEntity('supplier_proposal').")";
    if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($socid)	$sql.= " AND s.rowid = ".$socid;

    $resql=$db->query($sql);
    if ($resql)
    {
        $total = 0;
        $num = $db->num_rows($resql);

        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<th colspan="3">'.$langs->trans("SupplierProposalsDraft").($num?' <span class="badge">'.$num.'</span>':'').'</th></tr>';

        if ($num > 0)
        {
            $i = 0;
			$nbofloop=min($num, (empty($conf->global->MAIN_MAXLIST_OVERLOAD)?500:$conf->global->MAIN_MAXLIST_OVERLOAD));
			while ($i < $nbofloop)
            {
                $obj = $db->fetch_object($resql);

                print '<tr class="oddeven"><td  class="nowrap">';
                $supplierproposalstatic->id=$obj->rowid;
                $supplierproposalstatic->ref=$obj->ref;
                $supplierproposalstatic->total_ht = $obj->total_ht;
                $supplierproposalstatic->total_tva = $obj->total_tva;
                $supplierproposalstatic->total_ttc = $obj->total_ttc;
                print $supplierproposalstatic->getNomUrl(1);
                print '</td>';
                print '<td class="nowrap">';
                $companystatic->id=$obj->socid;
                $companystatic->name=$obj->name;
                $companystatic->client=$obj->client;
                $companystatic->code_client = $obj->code_client;
                $companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->entity = $obj->entity;
                $companystatic->email = $obj->email;
                $companystatic->canvas=$obj->canvas;
                print $companystatic->getNomUrl(1, 'supplier', 16);
                print '</td>';
                print '<td class="nowrap right">'.price($obj->total_ht).'</td></tr>';
                $i++;
                $total += $obj->total_ht;
            }
			if ($num > $nbofloop)
			{
				print '<tr class="liste_total"><td colspan="3" class="right">'.$langs->trans("XMoreLines", ($num - $nbofloop))."</td></tr>";
			}
			elseif ($total>0)
            {
                print '<tr class="liste_total"><td class="right">'.$langs->trans("Total").'</td><td colspan="2" class="right">'.price($total)."</td></tr>";
            }
        }
        else
        {
            print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoProposal").'</td></tr>';
        }
        print "</table></div><br>";

        $db->free($resql);
    }
    else
    {
        dol_print_error($db);
    }
}


/*
 * Draft orders
 */
if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
{
	$langs->load("orders");

	$sql = "SELECT c.rowid, c.ref, c.ref_client, c.total_ht, c.tva as total_tva, c.total_ttc, s.rowid as socid, s.nom as name, s.client, s.canvas";
    $sql.= ", s.code_client";
	$sql.= ", s.email";
    $sql.= ", s.entity";
    $sql.= ", s.code_compta";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.fk_statut = 0";
	$sql.= " AND c.entity IN (".getEntity('commande').")";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND c.fk_soc = ".$socid;

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("DraftOrders").($num?' <span class="badge">'.$num.'</span>':'').'</th></tr>';

		if ($num > 0)
		{
			$i = 0;
			$nbofloop=min($num, (empty($conf->global->MAIN_MAXLIST_OVERLOAD)?500:$conf->global->MAIN_MAXLIST_OVERLOAD));
			while ($i < $nbofloop)
			{
				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';
                $orderstatic->id=$obj->rowid;
                $orderstatic->ref=$obj->ref;
                $orderstatic->ref_client=$obj->ref_client;
                $orderstatic->total_ht = $obj->total_ht;
                $orderstatic->total_tva = $obj->total_tva;
                $orderstatic->total_ttc = $obj->total_ttc;
                print $orderstatic->getNomUrl(1);
                print '</td>';
				print '<td class="nowrap">';
				$companystatic->id=$obj->socid;
				$companystatic->name=$obj->name;
				$companystatic->client=$obj->client;
                $companystatic->code_client = $obj->code_client;
                $companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->canvas=$obj->canvas;
                $companystatic->email=$obj->email;
                $companystatic->entity=$obj->entity;
                $companystatic->canvas=$obj->canvas;
				print $companystatic->getNomUrl(1, 'customer', 16);
				print '</td>';
				if(! empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT)) {
					print '<td class="nowrap right">'.price($obj->total_ht).'</td></tr>';
				}
				else {
					print '<td class="nowrap right">'.price($obj->total_ttc).'</td></tr>';
				}
				$i++;
				$total += $obj->total_ttc;
			}
			if ($num > $nbofloop)
			{
				print '<tr class="liste_total"><td colspan="3" class="right">'.$langs->trans("XMoreLines", ($num - $nbofloop))."</td></tr>";
			}
			elseif ($total>0)
            {
                print '<tr class="liste_total"><td class="right">'.$langs->trans("Total").'</td><td colspan="2" class="right">'.price($total)."</td></tr>";
			}
		}
		else
		{

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
		print "</table>";
		print "</div><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}


/*
 * Draft suppliers orders
 */
if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->lire)
{
    $langs->load("orders");

    $sql = "SELECT cf.rowid, cf.ref, cf.ref_supplier, cf.total_ttc, s.rowid as socid, s.nom as name, s.client, s.canvas";
    $sql.= ", s.code_client";
    $sql.= ", s.code_fournisseur";
	$sql.= ", s.entity";
    $sql.= ", s.email";
    $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf";
    $sql.= ", ".MAIN_DB_PREFIX."societe as s";
    if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= " WHERE cf.fk_soc = s.rowid";
    $sql.= " AND cf.fk_statut = 0";
    $sql.= " AND cf.entity IN (".getEntity('supplier_order').")";
    if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($socid)	$sql.= " AND cf.fk_soc = ".$socid;

    $resql = $db->query($sql);
    if ($resql)
    {
        $total = 0;
        $num = $db->num_rows($resql);

        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<th colspan="3">'.$langs->trans("DraftSuppliersOrders").($num?' <span class="badge">'.$num.'</span>':'').'</th></tr>';

        if ($num > 0)
        {
            $i = 0;
			$nbofloop=min($num, (empty($conf->global->MAIN_MAXLIST_OVERLOAD)?500:$conf->global->MAIN_MAXLIST_OVERLOAD));
			while ($i < $nbofloop)
            {

                $obj = $db->fetch_object($resql);
                print '<tr class="oddeven"><td class="nowrap">';
                $supplierorderstatic->id=$obj->rowid;
                $supplierorderstatic->ref=$obj->ref;
                $supplierorderstatic->ref_supplier=$obj->ref_suppliert;
                $supplierorderstatic->total_ht = $obj->total_ht;
                $supplierorderstatic->total_tva = $obj->total_tva;
                $supplierorderstatic->total_ttc = $obj->total_ttc;
                print $supplierorderstatic->getNomUrl(1);
                print '</td>';
                print '<td class="nowrap">';
                $companystatic->id=$obj->socid;
                $companystatic->name=$obj->name;
                $companystatic->client=$obj->client;
                $companystatic->code_client = $obj->code_client;
                $companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->entity=$obj->entity;
                $companystatic->email=$obj->email;
                $companystatic->canvas=$obj->canvas;
                print $companystatic->getNomUrl(1, 'supplier', 16);
                print '</td>';
				if(! empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT)) {
					print '<td class="nowrap right">'.price($obj->total_ht).'</td></tr>';
				}
				else {
					print '<td class="nowrap right">'.price($obj->total_ttc).'</td></tr>';
				}
                $i++;
                $total += $obj->total_ttc;
            }
 			if ($num > $nbofloop)
			{
				print '<tr class="liste_total"><td colspan="3" class="right">'.$langs->trans("XMoreLines", ($num - $nbofloop))."</td></tr>";
			}
			elseif ($total>0)
            {
                print '<tr class="liste_total"><td class="right">'.$langs->trans("Total").'</td><td colspan="2" class="right">'.price($total)."</td></tr>";
            }
        }
        else
        {

            print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoSupplierOrder").'</td></tr>';
        }
        print "</table>";
        print "</div><br>";

        $db->free($resql);
    } else {
        dol_print_error($db);
    }
}


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


$max=3;


/*
 * Last modified customers or prospects
 */
if (! empty($conf->societe->enabled) && $user->rights->societe->lire)
{
	$langs->load("boxes");

	$sql = "SELECT s.rowid, s.nom as name, s.client, s.datec, s.tms, s.canvas";
    $sql.= ", s.code_client";
	$sql.= ", s.code_compta";
    $sql.= ", s.entity";
    $sql.= ", s.email";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.client IN (1, 2, 3)";
	$sql.= " AND s.entity IN (".getEntity($companystatic->element).")";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND s.rowid = $socid";
	$sql .= " ORDER BY s.tms DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print $langs->trans("BoxTitleLastCustomersOrProspects", $max);
        elseif (! empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print $langs->trans("BoxTitleLastModifiedProspects", $max);
		else print $langs->trans("BoxTitleLastModifiedCustomers", $max);
		print '</th>';
		print '<th class="right"><a class="commonlink" href="'.DOL_URL_ROOT.'/societe/list.php?type=p,c">'.$langs->trans("FullList").'</a></th>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
				$companystatic->id=$objp->rowid;
				$companystatic->name=$objp->name;
				$companystatic->client=$objp->client;
                $companystatic->code_client = $objp->code_client;
                $companystatic->code_fournisseur = $objp->code_fournisseur;
				$companystatic->code_compta = $objp->code_compta;
                $companystatic->entity = $objp->entity;
                $companystatic->email = $objp->email;
                $companystatic->canvas=$objp->canvas;
				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$companystatic->getNomUrl(1, 'customer', 48).'</td>';
				print '<td class="right" nowrap>';
				print $companystatic->getLibCustProspStatut();
				print "</td>";
				print '<td class="right" nowrap>'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
				print '</tr>';
				$i++;
			}

			$db->free($resql);
		}
		else
		{
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table>";
		print "</div><br>";
	}
}

// Last suppliers
if (! empty($conf->fournisseur->enabled) && $user->rights->societe->lire)
{
	$langs->load("boxes");

	$sql = "SELECT s.nom as name, s.rowid, s.datec as dc, s.canvas, s.tms as dm";
    $sql.= ", s.code_fournisseur";
	$sql.= ", s.entity";
    $sql.= ", s.email";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.fournisseur = 1";
	$sql.= " AND s.entity IN (".getEntity($companystatic->element).")";
	if (! $user->rights->societe->client->voir && ! $user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND s.rowid = ".$socid;
	$sql.= " ORDER BY s.datec DESC";
	$sql.= $db->plimit($max, 0);

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$i = 0;

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("BoxTitleLastModifiedSuppliers", min($max, $num)).'</th>';
		print '<th class="right"><a class="commonlink" href="'.DOL_URL_ROOT.'/societe/list.php?type=f">'.$langs->trans("FullList").'</a></th>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num && $i < $max)
			{
				$objp = $db->fetch_object($result);
				$companystatic->id=$objp->rowid;
                $companystatic->name=$objp->name;
                $companystatic->code_client = $objp->code_client;
                $companystatic->code_fournisseur = $objp->code_fournisseur;
				$companystatic->entity = $objp->entity;
                $companystatic->email = $objp->email;
                $companystatic->canvas=$objp->canvas;
                print '<tr class="oddeven">';
				print '<td class="nowrap">'.$companystatic->getNomUrl(1, 'supplier', 44).'</td>';
				print '<td class="right">'.dol_print_date($db->jdate($objp->dm), 'day').'</td>';
				print '</tr>';

				$i++;
			}
		}
		else
		{
			print '<tr class="oddeven"><td colspan="2" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print '</table>';
		print '</div><br>';
	}
}


/*
 * Last actions
 */
if ($user->rights->agenda->myactions->read)
{
	show_array_last_actions_done($max);
}


/*
 * Actions to do
 */
if ($user->rights->agenda->myactions->read)
{
	show_array_actions_to_do(10);
}


/*
 * Last contracts
 */
if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire && 0) // TODO A REFAIRE DEPUIS NOUVEAU CONTRAT
{
	$langs->load("contracts");

	$sql = "SELECT s.nom as name, s.rowid, s.canvas, ";
    $sql.= ", s.code_client";
	$sql.= ", s.entity";
    $sql.= ", s.email";
	$sql.= " c.statut, c.rowid as contratid, p.ref, c.mise_en_service as datemes, c.fin_validite as datefin, c.date_cloture as dateclo";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= ", ".MAIN_DB_PREFIX."contrat as c";
	$sql.= ", ".MAIN_DB_PREFIX."product as p";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.entity IN (".getEntity('contract').")";
	$sql.= " AND c.fk_product = p.rowid";
	if (! $user->rights->societe->client->voir && ! $socid)	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND s.rowid = ".$socid;
	$sql.= " ORDER BY c.tms DESC";
	$sql.= $db->plimit(5, 0);

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		if ($num > 0)
		{
			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre"><th colspan="3">'.$langs->trans("LastContracts", 5).'</th></tr>';
			$i = 0;

			$staticcontrat=new Contrat($db);

			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td><a href=\"../contrat/card.php?id=".$obj->contratid."\">".img_object($langs->trans("ShowContract","contract"), "contract")." ".$obj->ref."</a></td>';
				print '<td>';
                $companystatic->id=$objp->rowid;
                $companystatic->name=$objp->name;
                $companystatic->code_client = $objp->code_client;
                $companystatic->code_fournisseur = $objp->code_fournisseur;
				$companystatic->entity = $objp->entity;
                $companystatic->email = $objp->email;
                $companystatic->canvas=$objp->canvas;
                print $companystatic->getNomUrl(1, 'customer', 44);
				print '</td>'."\n";
				print "<td class=\"right\">".$staticcontrat->LibStatut($obj->statut, 3)."</td></tr>\n";

				$i++;
			}
			print "</table>";
			print "</div><br>";
		}
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * Opened proposals
 */
if (! empty($conf->propal->enabled) && $user->rights->propal->lire)
{
	$langs->load("propal");

	$sql = "SELECT s.nom as name, s.rowid, s.code_client";
	$sql.= ", s.entity";
    $sql.= ", s.email";
	$sql.= ", p.rowid as propalid, p.entity, p.total as total_ttc, p.total_ht, p.tva as total_tva, p.ref, p.ref_client, p.fk_statut, p.datep as dp, p.fin_validite as dfv";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= ", ".MAIN_DB_PREFIX."propal as p";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE p.fk_soc = s.rowid";
	$sql.= " AND p.entity IN (".getEntity('propal').")";
	$sql.= " AND p.fk_statut = 1";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND s.rowid = ".$socid;
	$sql.= " ORDER BY p.rowid DESC";

	$result=$db->query($sql);
	if ($result)
	{
		$total = 0;
		$num = $db->num_rows($result);
		$i = 0;
		if ($num > 0)
		{
			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre"><th colspan="5">'.$langs->trans("ProposalsOpened").' <a href="'.DOL_URL_ROOT.'/comm/propal/list.php?viewstatut=1"><span class="badge">'.$num.'</span></th></tr>';

			$nbofloop=min($num, (empty($conf->global->MAIN_MAXLIST_OVERLOAD)?500:$conf->global->MAIN_MAXLIST_OVERLOAD));
			while ($i < $nbofloop)
			{
				$obj = $db->fetch_object($result);

				print '<tr class="oddeven">';

				// Ref
				print '<td class="nowrap" width="140">';

				$propalstatic->id=$obj->propalid;
				$propalstatic->ref=$obj->ref;
                $propalstatic->ref_client=$obj->ref_client;
                $propalstatic->total_ht = $obj->total_ht;
                $propalstatic->total_tva = $obj->total_tva;
                $propalstatic->total_ttc = $obj->total_ttc;

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td class="nobordernopadding nowrap">';
				print $propalstatic->getNomUrl(1);
				print '</td>';
				print '<td width="18" class="nobordernopadding nowrap">';
				if ($db->jdate($obj->dfv) < ($now - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
				print '</td>';
				print '<td width="16" align="center" class="nobordernopadding">';
				$filename=dol_sanitizeFileName($obj->ref);
				$filedir=$conf->propal->multidir_output[$obj->entity] . '/' . dol_sanitizeFileName($obj->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->propalid;
				print $formfile->getDocumentsLink($propalstatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print "</td>";

                print '<td class="nowrap">';
                $companystatic->id=$obj->rowid;
                $companystatic->name=$obj->name;
                $companystatic->client=$obj->client;
                $companystatic->code_client = $obj->code_client;
                $companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->entity = $obj->entity;
                $companystatic->email = $obj->email;
                $companystatic->canvas=$obj->canvas;
                print $companystatic->getNomUrl(1, 'customer', 44);
                print '</td>';
				print '<td class="right">';
				print dol_print_date($db->jdate($obj->dp), 'day').'</td>'."\n";
				if(! empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT)) {
					print '<td class="right">'.price($obj->total_ht).'</td>';
				}
				else {
					print '<td class="right">'.price($obj->total_ttc).'</td>';
				}
				print '<td align="center" width="14">'.$propalstatic->LibStatut($obj->fk_statut, 3).'</td>'."\n";
				print '</tr>'."\n";
				$i++;
				$total += $obj->total_ttc;
			}
			if ($num > $nbofloop)
			{
				print '<tr class="liste_total"><td colspan="5" class="right">'.$langs->trans("XMoreLines", ($num - $nbofloop))."</td></tr>";
			}
			elseif ($total>0)
			{
				print '<tr class="liste_total"><td colspan="3" class="right">'.$langs->trans("Total")."</td><td class=\"right\">".price($total)."</td><td>&nbsp;</td></tr>";
			}
			print "</table>";
			print "</div><br>";
		}
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * Opened Order
 */
if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
{
	$langs->load("orders");

	$sql = "SELECT s.nom as name, s.rowid, c.rowid as commandeid, c.total_ttc, c.total_ht, c.tva as total_tva, c.ref, c.ref_client, c.fk_statut, c.date_valid as dv, c.facture as billed";
    $sql.= ", s.code_client";
	$sql.= ", s.entity";
    $sql.= ", s.email";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= ", ".MAIN_DB_PREFIX."commande as c";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.entity IN (".getEntity('commande').")";
	$sql.= " AND (c.fk_statut = ".Commande::STATUS_VALIDATED." or c.fk_statut = ".Commande::STATUS_SHIPMENTONPROCESS.")";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND s.rowid = ".$socid;
	$sql.= " ORDER BY c.rowid DESC";

	$result=$db->query($sql);
	if ($result)
	{
		$total = 0;
		$num = $db->num_rows($result);
		$i = 0;
		if ($num > 0)
		{
			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre"><th class="liste_titre" colspan="5">'.$langs->trans("OrdersOpened").' <a href="'.DOL_URL_ROOT.'/commande/list.php?viewstatut=1"><span class="badge">'.$num.'</span></th></tr>';

			$nbofloop=min($num, (empty($conf->global->MAIN_MAXLIST_OVERLOAD)?500:$conf->global->MAIN_MAXLIST_OVERLOAD));
			while ($i < $nbofloop)
			{
				$obj = $db->fetch_object($result);

				print '<tr class="oddeven">';

				// Ref
				print '<td class="nowrap" width="140">';

				$orderstatic->id=$obj->commandeid;
				$orderstatic->ref=$obj->ref;
                $orderstatic->ref_client=$obj->ref_client;
                $orderstatic->total_ht = $obj->total_ht;
                $orderstatic->total_tva = $obj->total_tva;
                $orderstatic->total_ttc = $obj->total_ttc;

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td class="nobordernopadding nowrap">';
				print $orderstatic->getNomUrl(1);
				print '</td>';
				print '<td width="18" class="nobordernopadding nowrap">';
				//if ($db->jdate($obj->dfv) < ($now - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
				print '</td>';
				print '<td width="16" align="center" class="nobordernopadding">';
				$filename=dol_sanitizeFileName($obj->ref);
				$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->propalid;
				print $formfile->getDocumentsLink($orderstatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print "</td>";

                print '<td class="nowrap">';
                $companystatic->id=$obj->rowid;
                $companystatic->name=$obj->name;
                $companystatic->client=$obj->client;
                $companystatic->code_client = $obj->code_client;
                $companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->entity = $obj->entity;
                $companystatic->email = $obj->email;
                $companystatic->canvas=$obj->canvas;
                print $companystatic->getNomUrl(1, 'customer', 44);
                print '</td>';
				print '<td class="right">';
				print dol_print_date($db->jdate($obj->dp), 'day').'</td>'."\n";
				if(! empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT)) {
					print '<td class="right">'.price($obj->total_ht).'</td>';
				}
				else {
					print '<td class="right">'.price($obj->total_ttc).'</td>';
				}
				print '<td align="center" width="14">'.$orderstatic->LibStatut($obj->fk_statut, $obj->billed, 3).'</td>'."\n";
				print '</tr>'."\n";
				$i++;
				$total += $obj->total_ttc;
			}
			if ($num > $nbofloop)
			{
				print '<tr class="liste_total"><td colspan="5" class="right">'.$langs->trans("XMoreLines", ($num - $nbofloop))."</td></tr>";
			}
			elseif ($total>0)
			{
				print '<tr class="liste_total"><td colspan="3" class="right">'.$langs->trans("Total")."</td><td class=\"right\">".price($total)."</td><td>&nbsp;</td></tr>";
			}
			print "</table>";
			print "</div><br>";
		}
	}
	else
	{
		dol_print_error($db);
	}
}



print '</div></div></div>';

// End of page
llxFooter();
$db->close();
