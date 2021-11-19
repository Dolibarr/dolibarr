<?php
/* Copyright (C) 2001-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2012		Vinicius Nogueira	<viniciusvgn@gmail.com>
 * Copyright (C) 2019           Nicolas ZABOURI         <info@inovea-conseil.com>
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
 *	 \file	   htdocs/fourn/commande/index.php
 *	 \ingroup	commande fournisseur
 *	 \brief	  Home page of supplier's orders area
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

// Security check
$orderid = GETPOST('orderid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'fournisseur', $orderid, '', 'commande');

$hookmanager = new HookManager($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('orderssuppliersindex'));

// Load translation files required by the page
$langs->loadLangs(array("suppliers", "orders"));


/*
 * 	View
 */

llxHeader('', $langs->trans("SuppliersOrdersArea"));

$commandestatic = new CommandeFournisseur($db);
$userstatic = new User($db);
$formfile = new FormFile($db);

print load_fiche_titre($langs->trans("SuppliersOrdersArea"), '', 'supplier_order');

print '<div class="fichecenter"><div class="fichethirdleft">';

/*
 * Statistics
 */

$sql = "SELECT count(cf.rowid) as nb, fk_statut as status";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= ", ".MAIN_DB_PREFIX."commande_fournisseur as cf";
if (empty($user->rights->societe->client->voir) && !$socid) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE cf.fk_soc = s.rowid";
$sql .= " AND cf.entity IN (".getEntity('supplier_order').")";
if ($user->socid) {
	$sql .= ' AND cf.fk_soc = '.((int) $user->socid);
}
if (empty($user->rights->societe->client->voir) && !$socid) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
$sql .= " GROUP BY cf.fk_statut";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	$total = 0;
	$dataseries = array();
	$vals = array();
	//	0=Draft -> 1=Validated -> 2=Approved -> 3=Process runing -> 4=Received partially -> 5=Received totally -> (reopen) 4=Received partially
	//	-> 7=Canceled/Never received -> (reopen) 3=Process runing
	//	-> 6=Canceled -> (reopen) 2=Approved
	while ($i < $num) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$vals[($obj->status == CommandeFournisseur::STATUS_CANCELED_AFTER_ORDER ? CommandeFournisseur::STATUS_CANCELED : $obj->status)] = $obj->nb;

			$total += $obj->nb;
		}
		$i++;
	}
	$db->free($resql);

	include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder nohover centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("SuppliersOrders").'</th></tr>';
	print "</tr>\n";
	$listofstatus = array(0, 1, 2, 3, 4, 5, 6, 9);
	foreach ($listofstatus as $status) {
		$dataseries[] = array($commandestatic->LibStatut($status, 1), (isset($vals[$status]) ? (int) $vals[$status] : 0));
		if ($status == CommandeFournisseur::STATUS_DRAFT) {
			$colorseries[$status] = '-'.$badgeStatus0;
		}
		if ($status == CommandeFournisseur::STATUS_VALIDATED) {
			$colorseries[$status] = '-'.$badgeStatus1;
		}
		if ($status == CommandeFournisseur::STATUS_ACCEPTED) {
			$colorseries[$status] = $badgeStatus1;
		}
		if ($status == CommandeFournisseur::STATUS_REFUSED) {
			$colorseries[$status] = $badgeStatus9;
		}
		if ($status == CommandeFournisseur::STATUS_ORDERSENT) {
			$colorseries[$status] = $badgeStatus4;
		}
		if ($status == CommandeFournisseur::STATUS_RECEIVED_PARTIALLY) {
			$colorseries[$status] = '-'.$badgeStatus4;
		}
		if ($status == CommandeFournisseur::STATUS_RECEIVED_COMPLETELY) {
			$colorseries[$status] = $badgeStatus6;
		}
		if ($status == CommandeFournisseur::STATUS_CANCELED || $status == CommandeFournisseur::STATUS_CANCELED_AFTER_ORDER) {
			$colorseries[$status] = $badgeStatus9;
		}

		if (!$conf->use_javascript_ajax) {
			print '<tr class="oddeven">';
			print '<td>'.$commandestatic->LibStatut($status, 0).'</td>';
			print '<td class="right"><a href="list.php?statut='.$status.'">'.(isset($vals[$status]) ? $vals[$status] : 0).'</a></td>';
			print "</tr>\n";
		}
	}
	if ($conf->use_javascript_ajax) {
		print '<tr class="impair"><td class="center" colspan="2">';

		include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
		$dolgraph = new DolGraph();
		$dolgraph->SetData($dataseries);
		$dolgraph->SetDataColor(array_values($colorseries));
		$dolgraph->setShowLegend(2);
		$dolgraph->setShowPercent(1);
		$dolgraph->SetType(array('pie'));
		$dolgraph->setHeight('200');
		$dolgraph->draw('idgraphstatus');
		print $dolgraph->show($total ? 0 : 1);

		print '</td></tr>';
	}
	//if ($totalinprocess != $total)
	//print '<tr class="liste_total"><td>'.$langs->trans("Total").' ('.$langs->trans("SuppliersOrdersRunning").')</td><td class="right">'.$totalinprocess.'</td></tr>';
	print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td class="right">'.$total.'</td></tr>';

	print "</table></div><br>";
} else {
	dol_print_error($db);
}

/*
 * Draft orders
 */

if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || !empty($conf->supplier_order->enabled)) {
	$sql = "SELECT c.rowid, c.ref, s.nom as name, s.rowid as socid";
	$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE c.fk_soc = s.rowid";
	$sql .= " AND c.entity IN (".getEntity("supplier_order").")"; // Thirdparty sharing is mandatory with supplier order sharing
	$sql .= " AND c.fk_statut = 0";
	if (!empty($socid)) {
		$sql .= " AND c.fk_soc = ".((int) $socid);
	}
	if (empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}

	$resql = $db->query($sql);
	if ($resql) {
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">'.$langs->trans("DraftOrders").'</th></tr>';
		$langs->load("orders");
		$num = $db->num_rows($resql);
		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven">';
				print '<td class="nowrap">';
				print "<a href=\"card.php?id=".$obj->rowid."\">".img_object($langs->trans("ShowOrder"), "order").' '.$obj->ref."</a></td>";
				print '<td><a href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"), "company").' '.dol_trunc($obj->name, 24).'</a></td></tr>';
				$i++;
			}
		}
		print "</table></div><br>";
	}
}


/*
 * List of users allowed
 */

$sql = "SELECT";
if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
	$sql .= " DISTINCT";
}
$sql .= " u.rowid, u.lastname, u.firstname, u.email, u.statut";
$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
	$sql .= ",".MAIN_DB_PREFIX."usergroup_user as ug";
	$sql .= " WHERE ((ug.fk_user = u.rowid";
	$sql .= " AND ug.entity IN (".getEntity('usergroup')."))";
	$sql .= " OR u.entity = 0)"; // Show always superadmin
} else {
	$sql .= " WHERE (u.entity IN (".getEntity('user')."))";
}
$sql .= " AND u.fk_soc IS NULL"; // An external user can not approved

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	print '<div class="div-table-responsive-no-min">';
	print '<table class="liste centpercent">';
	print '<tr class="liste_titre"><th>'.$langs->trans("UserWithApproveOrderGrant").'</th>';
	print "</tr>\n";

	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		$userstatic = new User($db);
		$userstatic->id = $obj->rowid;
		$userstatic->getrights('fournisseur');

		if (!empty($userstatic->rights->fournisseur->commande->approuver)) {
			print '<tr class="oddeven">';
			print '<td>';
			$userstatic->lastname = $obj->lastname;
			$userstatic->firstname = $obj->firstname;
			$userstatic->email = $obj->email;
			$userstatic->statut = $obj->statut;
			print $userstatic->getNomUrl(1);
			print '</td>';
			print "</tr>\n";
		}

		$i++;
	}
	print "</table></div><br>";
	$db->free($resql);
} else {
	dol_print_error($db);
}


print '</div><div class="fichetwothirdright">';


/*
 * Last modified orders
*/
$max = 5;

$sql = "SELECT c.rowid, c.ref, c.fk_statut as status, c.tms, c.billed, s.nom as name, s.rowid as socid";
$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
$sql .= ", ".MAIN_DB_PREFIX."societe as s";
if (empty($user->rights->societe->client->voir) && !$socid) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE c.fk_soc = s.rowid";
$sql .= " AND c.entity IN (".getEntity('supplier_order').")";
//$sql.= " AND c.fk_statut > 2";
if (!empty($socid)) {
	$sql .= " AND c.fk_soc = ".((int) $socid);
}
if (empty($user->rights->societe->client->voir) && !$socid) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
$sql .= " ORDER BY c.tms DESC";
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="4">'.$langs->trans("LastModifiedOrders", $max).'</th></tr>';

	$num = $db->num_rows($resql);
	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td width="20%" class="nowrap">';

			$commandestatic->id = $obj->rowid;
			$commandestatic->ref = $obj->ref;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td width="96" class="nobordernopadding nowrap">';
			print $commandestatic->getNomUrl(1);
			print '</td>';

			print '<td width="16" class="nobordernopadding nowrap">';
			print '&nbsp;';
			print '</td>';

			print '<td width="16" class="right nobordernopadding hideonsmartphone">';
			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->commande->dir_output.'/'.dol_sanitizeFileName($obj->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($commandestatic->element, $filename, $filedir);
			print '</td></tr></table>';

			print '</td>';

			print '<td><a href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"), "company").' '.$obj->name.'</a></td>';
			print '<td>'.dol_print_date($db->jdate($obj->tms), 'day').'</td>';
			print '<td class="right">'.$commandestatic->LibStatut($obj->status, 3, $obj->billed).'</td>';
			print '</tr>';
			$i++;
		}
	}
	print "</table></div><br>";
} else {
	dol_print_error($db);
}


/*
 * Orders to process
 */
/*
 $sql = "SELECT c.rowid, c.ref, c.fk_statut, s.nom as name, s.rowid as socid";
$sql.=" FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
if (empty($user->rights->societe->client->voir) && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE c.fk_soc = s.rowid";
$sql.= " AND c.entity IN (".getEntity("supplier_order").")";
$sql.= " AND c.fk_statut = 1";
if ($socid) $sql.= " AND c.fk_soc = ".((int) $socid);
if (empty($user->rights->societe->client->voir) && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .((int) $user->id);
$sql.= " ORDER BY c.rowid DESC";

$resql=$db->query($sql);
if ($resql)
{
$num = $db->num_rows($resql);

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="3">'.$langs->trans("OrdersToProcess").' <a href="'.DOL_URL_ROOT.'/commande/list.php?search_status=1">('.$num.')</a></th></tr>';

if ($num)
{
$i = 0;
while ($i < $num)
{
$obj = $db->fetch_object($resql);

print '<tr class="oddeven">';
print '<td class="nowrap">';

$commandestatic->id=$obj->rowid;
$commandestatic->ref=$obj->ref;

print '<table class="nobordernopadding"><tr class="nocellnopadd">';
print '<td width="96" class="nobordernopadding nowrap">';
print $commandestatic->getNomUrl(1);
print '</td>';

print '<td width="16" class="nobordernopadding nowrap">';
print '&nbsp;';
print '</td>';

print '<td width="16" class="right nobordernopadding hideonsmartphone">';
$filename=dol_sanitizeFileName($obj->ref);
$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
print $formfile->getDocumentsLink($commandestatic->element, $filename, $filedir);
print '</td></tr></table>';

print '</td>';

print '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($obj->name,24).'</a></td>';

print '<td class="right">'.$commandestatic->LibStatut($obj->fk_statut,$obj->facture,5).'</td>';

print '</tr>';
$i++;
}
}

print "</table></div><br>";
}
*/

print '</div></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardOrdersSuppliers', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
