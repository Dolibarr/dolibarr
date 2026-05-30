<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2005		Brice Davoleau				<brice.davoleau@gmail.com>
 * Copyright (C) 2005-2009	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2006-2011	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2007		Patrick Raguin				<patrick.raguin@gmail.com>
 * Copyright (C) 2010		Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2024-2025  Frédéric France             <frederic.france@free.fr>
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
 *    \file       htdocs/adherents/related.php
 *    \ingroup    member
 *    \brief      Page of members events
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'members'));

$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : getDolDefaultContextPage(__FILE__);

if (GETPOSTISARRAY('actioncode')) {
	$actioncode = GETPOST('actioncode', 'array:alpha', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST("actioncode", "alpha", 3) ? GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : getDolGlobalString('related_DEFAULT_FILTER_TYPE_FOR_OBJECT'));
}

$search_rowid = GETPOST('search_rowid');
$search_related_label = GETPOST('search_related_label');
$search_complete = GETPOST('search_complete');
$search_filtert = GETPOSTINT('search_filtert');
$search_dateevent_start = GETPOSTDATE('dateevent_start');
$search_dateevent_end = GETPOSTDATE('dateevent_end');

// Get Parameters
$id = GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('rowid');

// Pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'a.datep,a.id';
}
if (!$sortorder) {
	$sortorder = 'DESC,DESC';
}

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$objcanvas = null;

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('memberrelated', 'globalcard'));

// Security check
$result = restrictedArea($user, 'adherent', $id);

// Initialize a technical objects
$object = new Adherent($db);
$result = $object->fetch($id);
if ($result > 0) {
	$object->fetch_thirdparty();

	$adht = new AdherentType($db);
	$result = $adht->fetch($object->typeid);
}

/*
 *	View
 */

$contactstatic = new Contact($db);

$form = new Form($db);


if ($object->id > 0) {
	$langs->load("members");

	$title = $langs->trans("Member")." - ".$langs->trans("NbOfObjectReferers");

	$help_url = "EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder";

	llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-member page-card_related');

	if (isModEnabled('notification')) {
		$langs->load("mails");
	}
	$head = member_prepare_head($object);

	print dol_get_fiche_head($head, 'consumption', $langs->trans("Member"), -1, 'user');

	$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/adherents/list.php', ['restore_lastsearch_values' => 1]).'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/adherents/vcard.php', ['id' => $object->id]).'" class="refid">';
	$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard', 'class="valignmiddle marginleftonly paddingrightonly"');
	$morehtmlref .= '</a>';

	dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '</div>';
	print dol_get_fiche_end();


	// for a badge with how many items
	$nbAsContact = 0;
	require_once DOL_DOCUMENT_ROOT . '/core/lib/memory.lib.php';
	$cachekey = 'count_consumption_member_' . $object->id;
	$nbAsContactretreived = dol_getcache($cachekey);
	if (!is_null($nbAsContactretreived)) {
		$nbAsContact = $nbAsContactretreived;
	} else {
		$sql = "SELECT COUNT(ec.rowid) as nb";
		$sql .= " FROM " . MAIN_DB_PREFIX . "element_contact as ec";
		$sql .= " WHERE ec.fk_member = " . ((int) $object->id);
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			$nbAsContact = $obj->nb;
		} else {
			dol_print_error($db);
		}
		dol_setcache($cachekey, $nbAsContact, 120);		// If setting cache fails, this is not a problem, so we do not test result.
	}

	$titlelist = $langs->trans("NbOfObjectReferers").(is_numeric($nbAsContact) ? '<span class="opacitymedium colorblack paddingleft">('.$nbAsContact.')</span>' : '');
	if (!empty($conf->dol_optimize_smallscreen)) {
		$titlelist = $langs->trans("NbOfObjectReferers").(is_numeric($nbAsContact) ? '<span class="opacitymedium colorblack paddingleft">('.$nbAsContact.')</span>' : '');
	}
	print_barre_liste($titlelist, 0, $_SERVER["PHP_SELF"], '', '', '', '', 0, -1, '', 0, '', '', 0, 1, 0);


	$sql = "SELECT ec.rowid, ec.datecreate, tc.source, tc.element, ec.element_id, tc.code, ec.fk_c_type_contact, tc.libelle";
	$sql .= " FROM ".MAIN_DB_PREFIX."element_contact as ec";
	$sql .= " LEFT JOIN llx_c_type_contact tc ON ec.fk_c_type_contact = tc.rowid";
	$sql .= " WHERE ec.fk_member = ".((int) $id);

	$resql = $db->query($sql);

	if ($resql) {
		$contactsArray = [];

		while ($obj = $db->fetch_object($resql)) {
			$contactsArray[] = [
				'rowid'         => $obj->rowid,
				'datecreate'    => $obj->datecreate,
				'source'        => $obj->source,
				'element'       => $obj->element,
				'element_id'    => $obj->element_id,
				'code'          => $obj->code,
				'fk_c_type_contact' => $obj->fk_c_type_contact,
				'libelle'       => $obj->libelle
			];
		}

		echo '<div class="div-table-responsive">';
		echo '<table class="liste">';

		echo '<thead>';
		echo '<tr class="liste_titre">';
		$headers = [
			'ID' => 'rowid',
			'Date' => 'datecreate',
			'Element' => 'element',
			'Ref' => 'element_id',
			'Code' => 'code',
			'Label' => 'libelle'
		];

		foreach ($headers as $label => $key) {
			echo '<th>'.$label.'</th>';
		}
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if (count($contactsArray) > 0) {
			$i = 0;
			foreach ($contactsArray as $row) {
				$cssClass = ($i % 2) ? 'oddeven' : '';
				echo '<tr class="'.$cssClass.'">';
				echo '<td class="right">'.$row['rowid'].'</td>';
				echo '<td>'.$row['datecreate'].'</td>';

				if ($row['element'] == 'commande') {
					$element_url = dolBuildUrl(DOL_URL_ROOT.'/commande/contact.php', ['id' => $row['element_id']]);
				} elseif ($row['element'] == 'ticket') {
					$element_url = dolBuildUrl(DOL_URL_ROOT.'/ticket/contact.php', ['id' => $row['element_id']]);
				} elseif ($row['element'] == 'fichinter') {
					$element_url = dolBuildUrl(DOL_URL_ROOT.'/fichinter/contact.php', ['id' => $row['element_id']]);
				} elseif ($row['element'] == 'order_supplier') {
					$element_url = dolBuildUrl(DOL_URL_ROOT.'/fourn/commande/contact.php', ['id' => $row['element_id']]);
				} elseif ($row['element'] == 'supplier_proposal') {
					$element_url = dolBuildUrl(DOL_URL_ROOT.'/supplier_proposal/contact.php', ['id' => $row['element_id']]);
				} elseif ($row['element'] == 'project') {
					$element_url = dolBuildUrl(DOL_URL_ROOT.'/projet/contact.php', ['id' => $row['element_id']]);
				} elseif ($row['element'] == 'dolresource') {
					$element_url = dolBuildUrl(DOL_URL_ROOT.'/resource/contact.php', ['id' => $row['element_id']]);
				} elseif ($row['element'] == 'facture') {
					$element_url = dolBuildUrl(DOL_URL_ROOT.'/compta/facture/contact.php', ['id' => $row['element_id']]);
				} elseif ($row['element'] == 'invoice_supplier') {
					$element_url = dolBuildUrl(DOL_URL_ROOT.'/fourn/facture/contact.php', ['id' => $row['element_id']]);
				} elseif ($row['element'] == 'propal') {
					$element_url = dolBuildUrl(DOL_URL_ROOT.'/comm/propal/contact.php', ['id' => $row['element_id']]);
				} elseif ($row['element'] == 'contrat') {
					$element_url = dolBuildUrl(DOL_URL_ROOT.'/contrat/contact.php', ['id' => $row['element_id']]);
				} else {
					$element_url = dolBuildUrl(DOL_URL_ROOT.'/unknown_element/contact.php', ['id' => $row['element_id']]);
				}
				echo '<td><a href="'.$element_url.'">'.$row['element'].'</a></td>';
				echo '<td><a href="'.$element_url.'">'.$row['element_id'].'</a></td>';
				echo '<td>'.$row['code'].'</td>';
				echo '<td>'.$row['libelle'].'</td>';

				echo '</tr>';
				$i++;
			}
		} else {
			// Empty state row
			echo '<tr class="oddeven"><td colspan="8" class="opacitymedium">This member is not listed as a contact for any Dolibarr object.</td></tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';
	} else {
		dol_print_error($db);
	}
}
// End of page
llxFooter();
$db->close();
