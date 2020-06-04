<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2014      Charles-Fr Benke	<charles.fr@benke.fr>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2016      Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2019	   Nicolas ZABOURI	<info@inovea-conseil.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/societe/index.php
 *  \ingroup    societe
 *  \brief      Home page for third parties area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$hookmanager = new HookManager($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('thirdpartiesindex'));

$langs->load("companies");

$socid = GETPOST('socid', 'int');
if ($user->socid) $socid = $user->socid;

// Security check
$result = restrictedArea($user, 'societe', 0, '', '', '', '');

$thirdparty_static = new Societe($db);


/*
 * View
 */

$transAreaType = $langs->trans("ThirdPartiesArea");
$helpurl = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Terceros';

llxHeader("", $langs->trans("ThirdParties"), $helpurl);
$linkback = '';
print load_fiche_titre($transAreaType, $linkback, 'companies');


//print '<table border="0" width="100%" class="notopnoleftnoright">';
//print '<tr><td valign="top" width="30%" class="notopnoleft">';
print '<div class="fichecenter"><div class="fichethirdleft">';


/*
 * Statistics area
 */

$third = array(
		'customer' => 0,
		'prospect' => 0,
		'supplier' => 0,
		'other' =>0
);
$total = 0;

$sql = "SELECT s.rowid, s.client, s.fournisseur";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= ' WHERE s.entity IN ('.getEntity('societe').')';
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
if ($socid)	$sql .= " AND s.rowid = ".$socid;
if (!$user->rights->fournisseur->lire) $sql .= " AND (s.fournisseur <> 1 OR s.client <> 0)"; // client=0, fournisseur=0 must be visible
//print $sql;
$result = $db->query($sql);
if ($result)
{
    while ($objp = $db->fetch_object($result))
    {
        $found = 0;
        if (!empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS_STATS) && ($objp->client == 2 || $objp->client == 3)) { $found = 1; $third['prospect']++; }
        if (!empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS_STATS) && ($objp->client == 1 || $objp->client == 3)) { $found = 1; $third['customer']++; }
        if (!empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_STATS) && $objp->fournisseur) { $found = 1; $third['supplier']++; }
        if (!empty($conf->societe->enabled) && $objp->client == 0 && $objp->fournisseur == 0) { $found = 1; $third['other']++; }
        if ($found) $total++;
    }
}
else dol_print_error($db);

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder nohover centpercent">'."\n";
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").'</th></tr>';
if (!empty($conf->use_javascript_ajax) && ((round($third['prospect']) ? 1 : 0) + (round($third['customer']) ? 1 : 0) + (round($third['supplier']) ? 1 : 0) + (round($third['other']) ? 1 : 0) >= 2))
{
    print '<tr><td class="center" colspan="2">';
    $dataseries = array();
    if (!empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS_STATS))     $dataseries[] = array($langs->trans("Prospects"), round($third['prospect']));
    if (!empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS_STATS))     $dataseries[] = array($langs->trans("Customers"), round($third['customer']));
    if (!empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_STATS)) $dataseries[] = array($langs->trans("Suppliers"), round($third['supplier']));
    if (!empty($conf->societe->enabled)) $dataseries[] = array($langs->trans("Others"), round($third['other']));
    include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
    $dolgraph = new DolGraph();
	$dolgraph->SetData($dataseries);
	$dolgraph->setShowLegend(2);
	$dolgraph->setShowPercent(1);
	$dolgraph->SetType(array('pie'));
	$dolgraph->setHeight('200');
	$dolgraph->draw('idgraphthirdparties');
	print $dolgraph->show();
    print '</td></tr>'."\n";
}
else
{
    if (!empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS_STATS))
    {
        $statstring = "<tr>";
        $statstring .= '<td><a href="'.DOL_URL_ROOT.'/societe/list.php?type=p">'.$langs->trans("Prospects").'</a></td><td class="right">'.round($third['prospect']).'</td>';
        $statstring .= "</tr>";
    }
    if (!empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS_STATS))
    {
        $statstring .= "<tr>";
        $statstring .= '<td><a href="'.DOL_URL_ROOT.'/societe/list.php?type=c">'.$langs->trans("Customers").'</a></td><td class="right">'.round($third['customer']).'</td>';
        $statstring .= "</tr>";
    }
    if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_order->enabled) || !empty($conf->supplier_invoice->enabled)) && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_STATS) && $user->rights->fournisseur->lire)
    {
        $statstring2 = "<tr>";
        $statstring2 .= '<td><a href="'.DOL_URL_ROOT.'/societe/list.php?type=f">'.$langs->trans("Suppliers").'</a></td><td class="right">'.round($third['supplier']).'</td>';
        $statstring2 .= "</tr>";
    }
    print $statstring;
    print $statstring2;
}
print '<tr class="liste_total"><td>'.$langs->trans("UniqueThirdParties").'</td><td class="right">';
print $total;
print '</td></tr>';
print '</table>';
print '</div>';

if (!empty($conf->categorie->enabled) && !empty($conf->global->CATEGORY_GRAPHSTATS_ON_THIRDPARTIES))
{
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$elementtype = 'societe';

	print '<br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder nohover centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Categories").'</th></tr>';
	print '<tr><td class="center" colspan="2">';
	$sql = "SELECT c.label, count(*) as nb";
	$sql .= " FROM ".MAIN_DB_PREFIX."categorie_societe as cs";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cs.fk_categorie = c.rowid";
	$sql .= " WHERE c.type = 2";
	if (!is_numeric($conf->global->CATEGORY_GRAPHSTATS_ON_THIRDPARTIES)) $sql .= " AND c.label like '".$db->escape($conf->global->CATEGORY_GRAPHSTATS_ON_THIRDPARTIES)."'";
	$sql .= " AND c.entity IN (".getEntity('category').")";
	$sql .= " GROUP BY c.label";
	$total = 0;
	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$i = 0;
		if (!empty($conf->use_javascript_ajax))
		{
			$dataseries = array();
			$rest = 0;
			$nbmax = 10;

			while ($i < $num)
			{
				$obj = $db->fetch_object($result);
				if ($i < $nbmax)
				{
					$dataseries[] = array($obj->label, round($obj->nb));
				}
				else
				{
					$rest += $obj->nb;
				}
				$total += $obj->nb;
				$i++;
			}
			if ($i > $nbmax)
			{
				$dataseries[] = array($langs->trans("Other"), round($rest));
			}
			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			$dolgraph = new DolGraph();
			$dolgraph->SetData($dataseries);
			$dolgraph->setShowLegend(2);
			$dolgraph->setShowPercent(1);
			$dolgraph->SetType(array('pie'));
			$dolgraph->setHeight('200');
			$dolgraph->draw('idgraphcateg');
			print $dolgraph->show();
		}
		else
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($result);

				print '<tr class="oddeven"><td>'.$obj->label.'</td><td>'.$obj->nb.'</td></tr>';
				$total += $obj->nb;
				$i++;
			}
		}
	}
	print '</td></tr>';
	print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td class="right">';
	print $total;
	print '</td></tr>';
	print '</table>';
	print '</div>';
}

//print '</td><td valign="top" width="70%" class="notopnoleftnoright">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Latest modified third parties
 */
$max = 15;
$sql = "SELECT s.rowid, s.nom as name, s.email, s.client, s.fournisseur";
$sql .= ", s.code_client";
$sql .= ", s.code_fournisseur";
$sql .= ", s.code_compta_fournisseur";
$sql .= ", s.code_compta";
$sql .= ", s.logo";
$sql .= ", s.entity";
$sql .= ", s.canvas, s.tms as date_modification, s.status as status";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= ' WHERE s.entity IN ('.getEntity('societe').')';
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
if ($socid)	$sql .= " AND s.rowid = ".$socid;
if (!$user->rights->fournisseur->lire) $sql .= " AND (s.fournisseur != 1 OR s.client != 0)";
$sql .= $db->order("s.tms", "DESC");
$sql .= $db->plimit($max, 0);

//print $sql;
$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);

    $i = 0;

    if ($num > 0)
    {
        $transRecordedType = $langs->trans("LastModifiedThirdParties", $max);

        print "\n<!-- last thirdparties modified -->\n";
        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';

        print '<tr class="liste_titre"><th colspan="2">'.$transRecordedType.'</th>';
        print '<th>&nbsp;</th>';
        print '<th class="right"><a href="'.DOL_URL_ROOT.'/societe/list.php?sortfield=s.tms&sortorder=DESC">'.$langs->trans("FullList").'</th>';
        print '</tr>'."\n";

        while ($i < $num)
        {
            $objp = $db->fetch_object($result);

            $thirdparty_static->id = $objp->rowid;
            $thirdparty_static->name = $objp->name;
            $thirdparty_static->client = $objp->client;
            $thirdparty_static->fournisseur = $objp->fournisseur;
            $thirdparty_static->logo = $objp->logo;
            $thirdparty_static->date_modification = $db->jdate($objp->date_modification);
            $thirdparty_static->status = $objp->status;
            $thirdparty_static->code_client = $objp->code_client;
            $thirdparty_static->code_fournisseur = $objp->code_fournisseur;
            $thirdparty_static->canvas = $objp->canvas;
            $thirdparty_static->email = $objp->email;
			$thirdparty_static->entity = $objp->entity;
            $thirdparty_static->code_compta_fournisseur = $objp->code_compta_fournisseur;
            $thirdparty_static->code_compta = $objp->code_compta;

            print '<tr class="oddeven">';
            // Name
            print '<td class="nowrap tdoverflowmax200">';
            print $thirdparty_static->getNomUrl(1);
            print "</td>\n";
            // Type
            print '<td class="center">';
            if ($thirdparty_static->client == 1 || $thirdparty_static->client == 3)
            {
            	$thirdparty_static->name = $langs->trans("Customer");
            	print $thirdparty_static->getNomUrl(0, 'customer', 0, 1);
            }
            if ($thirdparty_static->client == 3 && empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print " / ";
            if (($thirdparty_static->client == 2 || $thirdparty_static->client == 3) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
            {
            	$thirdparty_static->name = $langs->trans("Prospect");
            	print $thirdparty_static->getNomUrl(0, 'prospect', 0, 1);
            }
            if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_order->enabled) || !empty($conf->supplier_invoice->enabled)) && $thirdparty_static->fournisseur)
            {
                if ($thirdparty_static->client) print " / ";
            	$thirdparty_static->name = $langs->trans("Supplier");
            	print $thirdparty_static->getNomUrl(0, 'supplier', 0, 1);
            }
            print '</td>';
            // Last modified date
            print '<td class="right">';
            print dol_print_date($thirdparty_static->date_modification, 'day');
            print "</td>";
            print '<td class="right nowrap">';
            print $thirdparty_static->getLibStatut(3);
            print "</td>";
            print "</tr>\n";
            $i++;
        }

        $db->free($result);

        print "</table>\n";
        print '</div>';
        print "<!-- End last thirdparties modified -->\n";
    }
}
else
{
    dol_print_error($db);
}

//print '</td></tr></table>';
print '</div></div></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardThirdparties', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
