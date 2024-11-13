<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2019      Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2019-2024  Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/commande/index.php
 *	\ingroup    order
 *	\brief      Home page of sales order module
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('orders', 'bills'));


if (!$user->hasRight('commande', 'lire')) {
	accessforbidden();
}

$hookmanager = new HookManager($db);

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('ordersindex'));


// Security check
$socid = GETPOSTINT('socid');
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

// Maximum elements of the tables
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);
$maxDraftCount = !getDolGlobalString('MAIN_MAXLIST_OVERLOAD') ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD;
$maxLatestEditCount = 5;
$maxOpenCount = !getDolGlobalString('MAIN_MAXLIST_OVERLOAD') ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD;


/*
 * View
 */

$commandestatic = new Commande($db);
$companystatic = new Societe($db);
$form = new Form($db);
$formfile = new FormFile($db);
$help_url = "EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";

llxHeader('', $langs->trans("Orders"), $help_url, '', 0, 0, '', '', '', 'mod-commande page-index');


print load_fiche_titre($langs->trans("OrdersArea"), '', 'order');


print '<div class="fichecenter"><div class="fichethirdleft">';

$tmp = getCustomerOrderPieChart($socid);
if ($tmp) {
	print $tmp;
	print '<br>';
}


/*
 * Draft orders
 */
if (isModEnabled('order')) {
	$sql = "SELECT c.rowid, c.ref, s.nom as name, s.rowid as socid";
	$sql .= ", s.client";
	$sql .= ", s.code_client";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE c.fk_soc = s.rowid";
	$sql .= " AND c.entity IN (".getEntity('commande').")";
	$sql .= " AND c.fk_statut = 0";
	if ($socid) {
		$sql .= " AND c.fk_soc = ".((int) $socid);
	}
	if (!$user->hasRight('societe', 'client', 'voir')) {
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

				$commandestatic->id = $obj->rowid;
				$commandestatic->ref = $obj->ref;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->client = $obj->client;
				$companystatic->code_client = $obj->code_client;
				$companystatic->canvas = $obj->canvas;

				print '<tr class="oddeven">';
				print '<td class="nowrap">';
				print $commandestatic->getNomUrl(1);
				print "</td>";
				print '<td class="nowrap">';
				print $companystatic->getNomUrl(1, 'company', 16);
				print '</td></tr>';
				$i++;
			}
		} else {
			print '<tr class="oddeven"><td colspan="3">'.$langs->trans("NoOrder").'</td></tr>';
		}
		print "</table></div><br>";
	}
}


print '</div><div class="fichetwothirdright">';


/*
 * Latest modified orders
 */

$sql = "SELECT c.rowid, c.entity, c.ref, c.fk_statut as status, c.facture, c.date_cloture as datec, c.tms as datem,";
$sql .= " s.nom as name, s.rowid as socid";
$sql .= ", s.client";
$sql .= ", s.code_client";
$sql .= ", s.canvas";
$sql .= " FROM ".MAIN_DB_PREFIX."commande as c,";
$sql .= " ".MAIN_DB_PREFIX."societe as s";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE c.fk_soc = s.rowid";
$sql .= " AND c.entity IN (".getEntity('commande').")";
//$sql.= " AND c.fk_statut > 2";
if ($socid) {
	$sql .= " AND c.fk_soc = ".((int) $socid);
}
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
$sql .= " ORDER BY c.tms DESC";
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	startSimpleTable($langs->trans("LastModifiedOrders", $max), "commande/list.php", "sortfield=c.tms&sortorder=DESC", 2, -1, 'order');

	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td width="20%" class="nowrap">';

			$commandestatic->id = $obj->rowid;
			$commandestatic->ref = $obj->ref;

			$companystatic->id = $obj->socid;
			$companystatic->name = $obj->name;
			$companystatic->client = $obj->client;
			$companystatic->code_client = $obj->code_client;
			$companystatic->canvas = $obj->canvas;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td width="96" class="nobordernopadding nowrap">';
			print $commandestatic->getNomUrl(1);
			print '</td>';

			print '<td width="16" class="nobordernopadding nowrap">';
			print '&nbsp;';
			print '</td>';

			print '<td width="16" class="nobordernopadding hideonsmartphone right">';
			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->commande->multidir_output[$obj->entity].'/'.dol_sanitizeFileName($obj->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($commandestatic->element, $filename, $filedir);
			print '</td></tr></table>';

			print '</td>';

			print '<td class="nowrap">';
			print $companystatic->getNomUrl(1, 'company', 16);
			print '</td>';

			$datem = $db->jdate($obj->datem);
			print '<td class="center" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'">';
			print dol_print_date($datem, 'day', 'tzuserrel');
			print '</td>';

			print '<td class="right">'.$commandestatic->LibStatut($obj->status, $obj->facture, 3).'</td>';
			print '</tr>';
			$i++;
		}
	}
	finishSimpleTable(true);
} else {
	dol_print_error($db);
}


/*
 * Orders to process
 */
if (isModEnabled('order')) {
	$sql = "SELECT c.rowid, c.entity, c.ref, c.fk_statut as status, c.facture, c.date_commande as date, s.nom as name, s.rowid as socid";
	$sql .= ", s.client";
	$sql .= ", s.code_client";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE c.fk_soc = s.rowid";
	$sql .= " AND c.entity IN (".getEntity('commande').")";
	$sql .= " AND c.fk_statut = ".Commande::STATUS_VALIDATED;
	if ($socid) {
		$sql .= " AND c.fk_soc = ".((int) $socid);
	}
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	$sql .= " ORDER BY c.rowid DESC";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="4">'.$langs->trans("OrdersToProcess").' <a href="'.DOL_URL_ROOT.'/commande/list.php?search_status='.Commande::STATUS_VALIDATED.'"><span class="badge">'.$num.'</span></a></th></tr>';

		if ($num) {
			$i = 0;
			while ($i < $num && $i < $max) {
				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';
				print '<td class="nowrap" width="20%">';

				$commandestatic->id = $obj->rowid;
				$commandestatic->ref = $obj->ref;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->client = $obj->client;
				$companystatic->code_client = $obj->code_client;
				$companystatic->canvas = $obj->canvas;

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="96" class="nobordernopadding nowrap">';
				print $commandestatic->getNomUrl(1);
				print '</td>';

				print '<td width="16" class="nobordernopadding nowrap">';
				print '&nbsp;';
				print '</td>';

				print '<td width="16" class="nobordernopadding hideonsmartphone right">';
				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->commande->multidir_output[$obj->entity].'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				print $formfile->getDocumentsLink($commandestatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';

				print '<td class="nowrap">';
				print $companystatic->getNomUrl(1, 'company', 24);
				print '</td>';

				print '<td class="right">'.dol_print_date($db->jdate($obj->date), 'day').'</td>'."\n";

				print '<td class="right">'.$commandestatic->LibStatut($obj->status, $obj->facture, 3).'</td>';

				print '</tr>';
				$i++;
			}
			if ($i < $num) {
				print '<tr><td><span class="opacitymedium">'.$langs->trans("More").'...</span></td><td></td><td></td><td></td></tr>';
			}
		}

		print "</table></div><br>";
	} else {
		dol_print_error($db);
	}
}

/*
 * Orders that are in process
 */
if (isModEnabled('order')) {
	$sql = "SELECT c.rowid, c.entity, c.ref, c.fk_statut as status, c.facture, c.date_commande as date, s.nom as name, s.rowid as socid";
	$sql .= ", s.client";
	$sql .= ", s.code_client";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE c.fk_soc = s.rowid";
	$sql .= " AND c.entity IN (".getEntity('commande').")";
	$sql .= " AND c.fk_statut = ".((int) Commande::STATUS_SHIPMENTONPROCESS);
	if ($socid) {
		$sql .= " AND c.fk_soc = ".((int) $socid);
	}
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	$sql .= " ORDER BY c.rowid DESC";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="4">'.$langs->trans("OnProcessOrders").' <a href="'.DOL_URL_ROOT.'/commande/list.php?search_status='.Commande::STATUS_SHIPMENTONPROCESS.'"><span class="badge">'.$num.'</span></a></th></tr>';

		if ($num) {
			$i = 0;
			while ($i < $num && $i < $max) {
				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';
				print '<td width="20%" class="nowrap">';

				$commandestatic->id = $obj->rowid;
				$commandestatic->ref = $obj->ref;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->client = $obj->client;
				$companystatic->code_client = $obj->code_client;
				$companystatic->canvas = $obj->canvas;

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="96" class="nobordernopadding nowrap">';
				print $commandestatic->getNomUrl(1);
				print '</td>';

				print '<td width="16" class="nobordernopadding nowrap">';
				print '&nbsp;';
				print '</td>';

				print '<td width="16" class="nobordernopadding hideonsmartphone right">';
				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->commande->multidir_output[$obj->entity].'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				print $formfile->getDocumentsLink($commandestatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';

				print '<td>';
				print $companystatic->getNomUrl(1, 'company');
				print '</td>';

				print '<td class="right">'.dol_print_date($db->jdate($obj->date), 'day').'</td>'."\n";

				print '<td class="right">'.$commandestatic->LibStatut($obj->status, $obj->facture, 3).'</td>';

				print '</tr>';
				$i++;
			}
			if ($i < $num) {
				print '<tr><td><span class="opacitymedium">'.$langs->trans("More").'...</span></td><td></td><td></td><td></td></tr>';
			}
		}
		print "</table></div><br>";
	} else {
		dol_print_error($db);
	}
}


print '</div></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardOrders', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
