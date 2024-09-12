<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2015		Jean-François Ferry			<jfefe@aternatik.fr>
 * Copyright (C) 2019		Nicolas ZABOURI				<info@inovea-conseil.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *	    \file       htdocs/contrat/index.php
 *      \ingroup    contrat
 *		\brief      Home page of contract area
 */

require "../main.inc.php";
require_once DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php";
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";

$hookmanager = new HookManager($db);

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('contractindex'));

// Load translation files required by the page
$langs->loadLangs(array('products', 'companies', 'contracts'));

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");

$statut = GETPOST('statut') ? GETPOST('statut') : 1;

$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);

// Security check
$socid = 0;
$id = GETPOSTINT('id');
if (!empty($user->socid)) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'contrat', $id);

$staticcompany = new Societe($db);
$staticcontrat = new Contrat($db);
$staticcontratligne = new ContratLigne($db);
$productstatic = new Product($db);



/*
 * Action
 */

// None


/*
 * View
 */

$now = dol_now();

$title = $langs->trans("ContractsArea");
$help_url = 'EN:Module_Contracts|FR:Module_Contrat|ES:Contratos_de_servicio';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-contrat page-index');

print load_fiche_titre($langs->trans("ContractsArea"), '', 'contract');


print '<div class="fichecenter"><div class="fichethirdleft">';


/*
 * Statistics
 */

$nb = array();
$total = 0;
$totalinprocess = 0;
$dataseries = array();
$vals = array();

// Search by status (except expired)
$sql = "SELECT count(cd.rowid) as nb, cd.statut as status";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= ", ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE cd.fk_contrat = c.rowid AND c.fk_soc = s.rowid";
$sql .= " AND (cd.statut != 4 OR (cd.statut = 4 AND (cd.date_fin_validite is null or cd.date_fin_validite >= '".$db->idate($now)."')))";
$sql .= " AND c.entity IN (".getEntity('contract', 0).")";
if ($user->socid) {
	$sql .= ' AND c.fk_soc = '.((int) $user->socid);
}
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
$sql .= " GROUP BY cd.statut";
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$nb[$obj->status] = $obj->nb;
			if ($obj->status != 5) {
				$vals[$obj->status] = $obj->nb;
				$totalinprocess += $obj->nb;
			}
			$total += $obj->nb;
		}
		$i++;
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}
// Search by status (only expired)
$sql = "SELECT count(cd.rowid) as nb, cd.statut as status";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= ", ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE cd.fk_contrat = c.rowid AND c.fk_soc = s.rowid";
$sql .= " AND (cd.statut = 4 AND cd.date_fin_validite < '".$db->idate($now)."')";
$sql .= " AND c.entity IN (".getEntity('contract', 0).")";
if ($user->socid) {
	$sql .= ' AND c.fk_soc = '.((int) $user->socid);
}
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
$sql .= " GROUP BY cd.statut";
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	// 0 inactive, 4 active, 5 closed
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$nb[$obj->status.((string) true)] = $obj->nb;
			if ($obj->status != 5) {
				$vals[$obj->status.((string) true)] = $obj->nb;
				$totalinprocess += $obj->nb;
			}
			$total += $obj->nb;
		}
		$i++;
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}

$colorseries = array();

include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder nohover centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("Services").'</th></tr>'."\n";
$listofstatus = array(0, 4, 4, 5);
$bool = false;
foreach ($listofstatus as $status) {
	$bool_str = (string) $bool;
	$dataseries[] = array($staticcontratligne->LibStatut($status, 1, ($bool ? 1 : 0)), (isset($nb[$status.$bool_str]) ? (int) $nb[$status.$bool_str] : 0));
	if ($status == ContratLigne::STATUS_INITIAL) {
		$colorseries[$status.$bool_str] = '-'.$badgeStatus0;
	}
	if ($status == ContratLigne::STATUS_OPEN && !$bool) {
		$colorseries[$status.$bool_str] = $badgeStatus4;
	}
	if ($status == ContratLigne::STATUS_OPEN && $bool) {
		$colorseries[$status.$bool_str] = $badgeStatus1;
	}
	if ($status == ContratLigne::STATUS_CLOSED) {
		$colorseries[$status.$bool_str] = $badgeStatus6;
	}

	if (empty($conf->use_javascript_ajax)) {
		print '<tr class="oddeven">';
		print '<td>'.$staticcontratligne->LibStatut($status, 0, ($bool ? 1 : 0)).'</td>';
		print '<td class="right"><a href="services_list.php?search_status='.((int) $status).($bool ? '&filter=expired' : '').'">'.($nb[$status.$bool_str] ? $nb[$status.$bool_str] : 0).' '.$staticcontratligne->LibStatut($status, 3, ($bool ? 1 : 0)).'</a></td>';
		print "</tr>\n";
	}
	if ($status == 4 && !$bool) {
		$bool = true;
	} else {
		$bool = false;
	}
}
if (!empty($conf->use_javascript_ajax)) {
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
$listofstatus = array(0, 4, 4, 5);
$bool = false;
foreach ($listofstatus as $status) {
	$bool_str = (string) $bool;
	if (empty($conf->use_javascript_ajax)) {
		print '<tr class="oddeven">';
		print '<td>'.$staticcontratligne->LibStatut($status, 0, ($bool ? 1 : 0)).'</td>';
		print '<td class="right"><a href="services_list.php?search_status='.((int) $status).($bool ? '&filter=expired' : '').'">'.($nb[$status.$bool_str] ? $nb[$status.$bool_str] : 0).' '.$staticcontratligne->LibStatut($status, 3, ($bool ? 1 : 0)).'</a></td>';
		if ($status == 4 && !$bool) {
			$bool = true;
		} else {
			$bool = false;
		}
		print "</tr>\n";
	}
}
print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td class="right">'.$total.'</td></tr>';
print "</table></div><br>";


// Draft contracts

if (isModEnabled('contract') && $user->hasRight('contrat', 'lire')) {
	$sql = "SELECT c.rowid, c.ref,";
	$sql .= " s.nom as name, s.name_alias, s.logo, s.rowid as socid, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client, s.code_compta_fournisseur";
	$sql .= " FROM ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE s.rowid = c.fk_soc";
	$sql .= " AND c.entity IN (".getEntity('contract', 0).")";
	$sql .= " AND c.statut = 0";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND c.fk_soc = ".((int) $socid);
	}

	$resql = $db->query($sql);

	if ($resql) {
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("DraftContracts").($num ? '<span class="badge marginleftonlyshort">'.$num.'</span>' : '').'</th></tr>';
		if ($num) {
			$i = 0;
			//$tot_ttc = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$staticcontrat->ref = $obj->ref;
				$staticcontrat->id = $obj->rowid;

				$staticcompany->id = $obj->socid;
				$staticcompany->name = $obj->name;
				$staticcompany->name_alias = $obj->name_alias;
				$staticcompany->logo = $obj->logo;
				$staticcompany->code_client = $obj->code_client;
				$staticcompany->code_fournisseur = $obj->code_fournisseur;
				$staticcompany->code_compta = $obj->code_compta_client;
				$staticcompany->code_compta_client = $obj->code_compta_client;
				$staticcompany->code_compta_fournisseur = $obj->code_compta_fournisseur;
				$staticcompany->client = $obj->client;
				$staticcompany->fournisseur = $obj->fournisseur;

				print '<tr class="oddeven"><td class="nowrap">';
				print $staticcontrat->getNomUrl(1, 0);
				print '</td>';
				print '<td>';
				print $staticcompany->getNomUrl(1, '', 16);
				print '</td>';
				print '</tr>';
				//$tot_ttc+=$obj->total_ttc;
				$i++;
			}
		} else {
			print '<tr class="oddeven"><td colspan="3"><span class="opacitymedium">'.$langs->trans("NoContracts").'</span></td></tr>';
		}
		print "</table></div><br>";
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


print '</div><div class="fichetwothirdright">';


// Last modified contracts
$sql = 'SELECT ';
$sql .= " sum(".$db->ifsql("cd.statut=0", 1, 0).') as nb_initial,';
$sql .= " sum(".$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite >= '".$db->idate($now)."')", 1, 0).') as nb_running,';
$sql .= " sum(".$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < '".$db->idate($now)."')", 1, 0).') as nb_expired,';
$sql .= " sum(".$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < '".$db->idate($now - $conf->contrat->services->expires->warning_delay)."')", 1, 0).') as nb_late,';
$sql .= " sum(".$db->ifsql("cd.statut=5", 1, 0).') as nb_closed,';
$sql .= " c.rowid as cid, c.ref, c.datec, c.tms, c.statut,";
$sql .= " s.nom as name, s.name_alias, s.logo, s.rowid as socid, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s,";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
}
$sql .= " ".MAIN_DB_PREFIX."contrat as c";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
$sql .= " WHERE c.fk_soc = s.rowid";
$sql .= " AND c.entity IN (".getEntity('contract', 0).")";
$sql .= " AND c.statut > 0";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
if ($socid) {
	$sql .= " AND s.rowid = ".((int) $socid);
}
$sql .= " GROUP BY c.rowid, c.ref, c.datec, c.tms, c.statut,";
$sql .= " s.nom, s.name_alias, s.logo, s.rowid, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client, s.code_compta_fournisseur";
$sql .= " ORDER BY c.tms DESC";
$sql .= $db->plimit($max);

dol_syslog("contrat/index.php", LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("LastContracts", $max).'</th>';
	print '<th class="center">'.$langs->trans("DateModification").'</th>';
	//print '<th class="left">'.$langs->trans("Status").'</th>';
	print '<th class="center" width="80" colspan="4">'.$langs->trans("Services").'</th>';
	print "</tr>\n";

	while ($i < $num) {
		$obj = $db->fetch_object($result);
		$datem = $db->jdate($obj->tms);

		$staticcontrat->ref = ($obj->ref ? $obj->ref : $obj->cid);
		$staticcontrat->id = $obj->cid;

		$staticcompany->id = $obj->socid;
		$staticcompany->name = $obj->name;
		$staticcompany->name_alias = $obj->name_alias;
		$staticcompany->photo = 1;
		$staticcompany->code_client = $obj->code_client;
		$staticcompany->code_fournisseur = $obj->code_fournisseur;
		$staticcompany->code_compta = $obj->code_compta_client;
		$staticcompany->code_compta_client = $obj->code_compta_client;
		$staticcompany->code_compta_fournisseur = $obj->code_compta_fournisseur;
		$staticcompany->client = $obj->client;
		$staticcompany->fournisseur = $obj->fournisseur;

		print '<tr class="oddeven">';
		print '<td class="nowraponall">';
		print $staticcontrat->getNomUrl(1, 16);
		if ($obj->nb_late) {
			print img_warning($langs->trans("Late"));
		}
		print '</td>';

		print '<td class="tdoverflowmax150">';
		print $staticcompany->getNomUrl(1, '', 20);
		print '</td>';
		print '<td class="center nowraponall" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'">';
		print dol_print_date($datem, 'dayhour');
		print '</td>';
		//print '<td class="left">'.$staticcontrat->LibStatut($obj->statut,2).'</td>';
		print '<td class="right nowraponall" width="32">'.($obj->nb_initial > 0 ? '<span class="paddingright">'.$obj->nb_initial.'</span>'.$staticcontratligne->LibStatut(0, 3, -1, 'class="paddingleft"') : '').'</td>';
		print '<td class="right nowraponall" width="32">'.($obj->nb_running > 0 ? '<span class="paddingright">'.$obj->nb_running.'</span>'.$staticcontratligne->LibStatut(4, 3, 0, 'class="marginleft"') : '').'</td>';
		print '<td class="right nowraponall" width="32">'.($obj->nb_expired > 0 ? '<span class="paddingright">'.$obj->nb_expired.'</span>'.$staticcontratligne->LibStatut(4, 3, 1, 'class="paddingleft"') : '').'</td>';
		print '<td class="right nowraponall" width="32">'.($obj->nb_closed > 0 ? '<span class="paddingright">'.$obj->nb_closed.'</span>'.$staticcontratligne->LibStatut(5, 3, -1, 'class="paddingleft"') : '').'</td>';
		print "</tr>\n";
		$i++;
	}
	$db->free($result);

	print "</table></div>";
} else {
	dol_print_error($db);
}

print '<br>';

// Last modified services
$sql = "SELECT c.ref, c.fk_soc as socid,";
$sql .= " cd.rowid as cid, cd.statut, cd.label, cd.fk_product, cd.description as note, cd.fk_contrat, cd.date_fin_validite,";
$sql .= " s.nom as name, s.name_alias, s.logo, s.rowid as socid, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client, s.code_compta_fournisseur,";
$sql .= " p.rowid as pid, p.ref as pref, p.label as plabel, p.fk_product_type as ptype, p.entity as pentity";
$sql .= " FROM (".MAIN_DB_PREFIX."contrat as c";
$sql .= ", ".MAIN_DB_PREFIX."societe as s";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= ", ".MAIN_DB_PREFIX."contratdet as cd";
$sql .= ") LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
$sql .= " WHERE c.entity IN (".getEntity('contract', 0).")";
$sql .= " AND cd.fk_contrat = c.rowid";
$sql .= " AND c.fk_soc = s.rowid";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
if ($socid) {
	$sql .= " AND s.rowid = ".((int) $socid);
}
$sql .= " ORDER BY cd.tms DESC";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre"><th colspan="4">'.$langs->trans("LastModifiedServices", $max).'</th>';
	print "</tr>\n";

	while ($i < min($num, $max)) {
		$obj = $db->fetch_object($resql);

		print '<tr class="oddeven">';
		print '<td class="nowraponall">';

		$staticcontrat->ref = ($obj->ref ? $obj->ref : $obj->fk_contrat);
		$staticcontrat->id = $obj->fk_contrat;

		$staticcompany->id = $obj->socid;
		$staticcompany->name = $obj->name;
		$staticcompany->name_alias = $obj->name_alias;
		$staticcompany->photo = 1;
		$staticcompany->code_client = $obj->code_client;
		$staticcompany->code_fournisseur = $obj->code_fournisseur;
		$staticcompany->code_compta = $obj->code_compta_client;
		$staticcompany->code_compta_client = $obj->code_compta_client;
		$staticcompany->code_compta_fournisseur = $obj->code_compta_fournisseur;
		$staticcompany->client = $obj->client;
		$staticcompany->fournisseur = $obj->fournisseur;

		print $staticcontrat->getNomUrl(1, 16);

		//if (1 == 1) print img_warning($langs->trans("Late"));
		print '</td>';
		print '<td>';
		if ($obj->fk_product > 0) {
			$productstatic->id = $obj->fk_product;
			$productstatic->type = $obj->ptype;
			$productstatic->ref = $obj->pref;
			$productstatic->entity = $obj->pentity;
			print $productstatic->getNomUrl(1, '', 20);
		} else {
			print '<a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"), "service");
			if ($obj->label) {
				print ' '.dol_trunc($obj->label, 20).'</a>';
			} else {
				print '</a> '.dol_trunc($obj->note, 20);
			}
		}
		print '</td>';
		print '<td class="tdoverflowmax125">';
		print $staticcompany->getNomUrl(1, '', 20);
		print '</td>';
		print '<td class="nowrap right"><a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'">';
		$dateend = $db->jdate($obj->date_fin_validite);
		print $staticcontratligne->LibStatut($obj->statut, 3, ($dateend && $dateend < $now) ? 1 : 0);
		print '</a></td>';
		print "</tr>\n";
		$i++;
	}
	$db->free($resql);

	print "</table></div>";
} else {
	dol_print_error($db);
}

print '<br>';

// Not activated services
$sql = "SELECT c.ref, c.fk_soc as thirdpartyid, cd.rowid as cid, cd.statut, cd.label, cd.fk_product, cd.description as note, cd.fk_contrat,";
$sql .= " s.nom as name, s.name_alias, s.logo, s.rowid as socid, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client, s.code_compta_fournisseur,";
$sql .= " p.rowid as pid, p.ref as pref, p.label as plabel, p.fk_product_type as ptype, p.entity as pentity";
$sql .= " FROM (".MAIN_DB_PREFIX."contrat as c";
$sql .= ", ".MAIN_DB_PREFIX."societe as s";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= ", ".MAIN_DB_PREFIX."contratdet as cd";
$sql .= " ) LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
$sql .= " WHERE c.entity IN (".getEntity('contract', 0).")";
$sql .= " AND c.statut = 1";
$sql .= " AND cd.statut = 0";
$sql .= " AND cd.fk_contrat = c.rowid";
$sql .= " AND c.fk_soc = s.rowid";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
if ($socid) {
	$sql .= " AND s.rowid = ".((int) $socid);
}
$sql .= " ORDER BY cd.tms DESC";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre"><th colspan="4">'.$langs->trans("NotActivatedServices").' <a href="'.DOL_URL_ROOT.'/contrat/services_list.php?mode=0"><span class="badge">'.$num.'</span></a></th>';
	print "</tr>\n";

	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		$staticcompany->id = $obj->thirdpartyid;
		$staticcompany->name = $obj->name;
		$staticcompany->name_alias = $obj->name_alias;
		$staticcompany->photo = 1;
		$staticcompany->code_client = $obj->code_client;
		$staticcompany->code_fournisseur = $obj->code_fournisseur;
		$staticcompany->code_compta = $obj->code_compta_client;
		$staticcompany->code_compta_client = $obj->code_compta_client;
		$staticcompany->code_compta_fournisseur = $obj->code_compta_fournisseur;
		$staticcompany->client = $obj->client;
		$staticcompany->fournisseur = $obj->fournisseur;

		$staticcontrat->ref = ($obj->ref ? $obj->ref : $obj->fk_contrat);
		$staticcontrat->id = $obj->fk_contrat;

		$productstatic->id = $obj->fk_product;
		$productstatic->type = $obj->ptype;
		$productstatic->ref = $obj->pref;
		$productstatic->entity = $obj->pentity;

		print '<tr class="oddeven">';

		print '<td class="nowraponall">';
		print $staticcontrat->getNomUrl(1, 16);
		print '</td>';
		print '<td class="nowrap">';
		if ($obj->fk_product > 0) {
			print $productstatic->getNomUrl(1, '', 20);
		} else {
			print '<a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"), "service");
			if ($obj->label) {
				print ' '.dol_trunc($obj->label, 20).'</a>';
			} else {
				print '</a> '.dol_trunc($obj->note, 20);
			}
		}
		print '</td>';
		print '<td class="tdoverflowmax125">';
		print $staticcompany->getNomUrl(1, '', 20);
		print '</td>';
		print '<td width="16" class="right"><a href="line.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'">';
		print $staticcontratligne->LibStatut($obj->statut, 3);
		print '</a></td>';
		print "</tr>\n";
		$i++;
	}

	$db->free($resql);

	print "</table></div>";
} else {
	dol_print_error($db);
}

print '<br>';

// Expired services
$sql = "SELECT c.ref, c.fk_soc as thirdpartyid, cd.rowid as cid, cd.statut, cd.label, cd.fk_product, cd.description as note, cd.fk_contrat,";
$sql .= " s.nom as name, s.name_alias, s.logo, s.rowid as socid, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client, s.code_compta_fournisseur,";
$sql .= " p.rowid as pid, p.ref as pref, p.label as plabel, p.fk_product_type as ptype, p.entity as pentity";
$sql .= " FROM (".MAIN_DB_PREFIX."contrat as c";
$sql .= ", ".MAIN_DB_PREFIX."societe as s";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= ", ".MAIN_DB_PREFIX."contratdet as cd";
$sql .= " ) LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
$sql .= " WHERE c.entity IN (".getEntity('contract', 0).")";
$sql .= " AND c.statut = 1";
$sql .= " AND cd.statut = 4";
$sql .= " AND cd.date_fin_validite < '".$db->idate($now)."'";
$sql .= " AND cd.fk_contrat = c.rowid";
$sql .= " AND c.fk_soc = s.rowid";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
if ($socid) {
	$sql .= " AND s.rowid = ".((int) $socid);
}
$sql .= " ORDER BY cd.tms DESC";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre"><th colspan="4">'.$langs->trans("ListOfExpiredServices").' <a href="'.DOL_URL_ROOT.'/contrat/services_list.php?search_status=4&amp;filter=expired"><span class="badge">'.$num.'</span></a></th>';
	print "</tr>\n";

	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		$staticcompany->id = $obj->thirdpartyid;
		$staticcompany->name = $obj->name;
		$staticcompany->name_alias = $obj->name_alias;
		$staticcompany->photo = 1;
		$staticcompany->code_client = $obj->code_client;
		$staticcompany->code_fournisseur = $obj->code_fournisseur;
		$staticcompany->code_compta = $obj->code_compta_client;
		$staticcompany->code_compta_client = $obj->code_compta_client;
		$staticcompany->code_compta_fournisseur = $obj->code_compta_fournisseur;
		$staticcompany->client = $obj->client;
		$staticcompany->fournisseur = $obj->fournisseur;

		$staticcontrat->ref = ($obj->ref ? $obj->ref : $obj->fk_contrat);
		$staticcontrat->id = $obj->fk_contrat;

		$productstatic->id = $obj->fk_product;
		$productstatic->type = $obj->ptype;
		$productstatic->ref = $obj->pref;
		$productstatic->entity = $obj->pentity;

		print '<tr class="oddeven">';

		print '<td class="nowraponall">';
		print $staticcontrat->getNomUrl(1, 16);
		print '</td>';
		print '<td class="nowrap">';
		if ($obj->fk_product > 0) {
			print $productstatic->getNomUrl(1, '', 20);
		} else {
			print '<a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"), "service");
			if ($obj->label) {
				print ' '.dol_trunc($obj->label, 20).'</a>';
			} else {
				print '</a> '.dol_trunc($obj->note, 20);
			}
		}
		print '</td>';
		print '<td class="tdoverflowmax125">';
		print $staticcompany->getNomUrl(1, '', 20);
		print '</td>';
		print '<td width="16" class="right"><a href="line.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'">';
		print $staticcontratligne->LibStatut($obj->statut, 3, 1);
		print '</a></td>';
		print "</tr>\n";
		$i++;
	}
	$db->free($resql);

	print "</table></div>";
} else {
	dol_print_error($db);
}


print '</div></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardContracts', $parameters, $object); // Note that $action and $object may have been modified by hook

llxFooter();

$db->close();
