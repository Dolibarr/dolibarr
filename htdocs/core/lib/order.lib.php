<?php
/* Copyright (C) 2006-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2012	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010		Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025-2026  Frédéric France         <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file       htdocs/core/lib/order.lib.php
 *  \brief      Ensemble de functions de base pour le module commande
 *  \ingroup    order
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Commande	$object		Object related to tabs
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function commande_prepare_head(Commande $object)
{
	global $db, $langs, $conf, $user;

	if (isModEnabled("shipping")) {
		$langs->load("sendings");
	}
	$langs->load("orders");

	$h = 0;
	$head = array();

	if (isModEnabled('order') && $user->hasRight('commande', 'lire')) {
		$head[$h][0] = dolBuildUrl(DOL_URL_ROOT . '/commande/card.php', ['id' => $object->id]);
		$head[$h][1] = $langs->trans("CustomerOrder");
		$head[$h][2] = 'order';
		$h++;
	}

	if (!getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB')) {
		$nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
		$head[$h][0] = dolBuildUrl(DOL_URL_ROOT . '/commande/contact.php', ['id' => $object->id]);
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		}
		$head[$h][2] = 'contact';
		$h++;
	}

	if ((getDolGlobalInt('MAIN_SUBMODULE_EXPEDITION') && $user->hasRight('expedition', 'lire'))
		|| (getDolGlobalInt('MAIN_SUBMODULE_DELIVERY') && $user->hasRight('expedition', 'delivery', 'lire'))) {
		$nbShipments = $object->getNbOfShipments();
		$nbReceiption = 0;
		$head[$h][0] = dolBuildUrl(DOL_URL_ROOT . '/expedition/shipment.php', ['id' => $object->id]);
		$text = '';
		if (getDolGlobalInt('MAIN_SUBMODULE_EXPEDITION')) {
			$text .= $langs->trans("Shipments");
		}
		if (getDolGlobalInt('MAIN_SUBMODULE_EXPEDITION') && getDolGlobalInt('MAIN_SUBMODULE_DELIVERY')) {
			$text .= ' - ';
		}
		if (getDolGlobalInt('MAIN_SUBMODULE_DELIVERY')) {
			$text .= $langs->trans("Receivings");
		}
		if ($nbShipments > 0 || $nbReceiption > 0) {
			$text .= '<span class="badge marginleftonlyshort">'.($nbShipments ? $nbShipments : 0);
		}
		if (getDolGlobalInt('MAIN_SUBMODULE_EXPEDITION') && getDolGlobalInt('MAIN_SUBMODULE_DELIVERY') && ($nbShipments > 0 || $nbReceiption > 0)) {
			$text .= ' - ';
		}
		if (getDolGlobalInt('MAIN_SUBMODULE_EXPEDITION') && getDolGlobalInt('MAIN_SUBMODULE_DELIVERY') && ($nbShipments > 0 || $nbReceiption > 0)) {
			$text .= ($nbReceiption ? $nbReceiption : 0);
		}
		if ($nbShipments > 0 || $nbReceiption > 0) {
			$text .= '</span>';
		}
		$head[$h][1] = $text;
		$head[$h][2] = 'shipping';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'order', 'add', 'core');

	if (!getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = dolBuildUrl(DOL_URL_ROOT . '/core/note.php', ['element' => 'commande', 'id' => $object->id]);
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->commande->multidir_output[$object->entity ?? $conf->entity]."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT . '/commande/document.php', ['id' => $object->id]);
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;


	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT . '/commande/agenda.php', ['id' => $object->id]);
	$head[$h][1] = $langs->trans("Events");
	if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
		$nbEvent = 0;
		// Enable caching of thirdparty count actioncomm
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_events_propal_'.$object->id;
		$dataretrieved = dol_getcache($cachekey);
		if (!is_null($dataretrieved)) {
			$nbEvent = $dataretrieved;
		} else {
			$sql = "SELECT COUNT(id) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm";
			$sql .= " WHERE fk_element = ".((int) $object->id);
			$sql .= " AND elementtype = 'order'";
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$nbEvent = $obj->nb;
			} else {
				dol_syslog('Failed to count actioncomm '.$db->lasterror(), LOG_ERR);
			}
			dol_setcache($cachekey, $nbEvent, 120);		// If setting cache fails, this is not a problem, so we do not test result.
		}

		$head[$h][1] .= '/';
		$head[$h][1] .= $langs->trans("Agenda");
		if ($nbEvent > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbEvent.'</span>';
		}
	}
	$head[$h][2] = 'agenda';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'order', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'order', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object information.
 *
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function order_admin_prepare_head()
{
	global $langs, $conf, $extrafields;

	$extrafields->fetch_name_optionals_label('commande');
	$extrafields->fetch_name_optionals_label('commandedet');

	$h = 0;
	$head = array();

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT . '/admin/order.php');
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'general';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'order_admin');

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/admin/extrafields.php', array('elementtype' => 'commande'));
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = $extrafields->attributes['commande']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/admin/extrafields.php', array('elementtype' => 'commandedet'));
	$head[$h][1] = $langs->trans("ExtraFieldsLines");
	$nbExtrafields = $extrafields->attributes['commandedet']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributeslines';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'order_admin', 'remove');

	return $head;
}



/**
 * Return a HTML table that contains a pie chart of sales orders
 *
 * @param	int		$socid		(Optional) Show only results from the customer with this id
 * @return	string				A HTML table that contains a pie chart of customer invoices
 */
function getCustomerOrderPieChart($socid = 0)
{
	global $conf, $db, $langs, $user, $hookmanager;

	$result = '';

	if (!isModEnabled('order') || !$user->hasRight('commande', 'lire')) {
		return '';
	}

	$commandestatic = new Commande($db);

	/*
	 * Statistics
	 */
	if ($user->socid > 0) $socid = $user->socid;
	$sql = "SELECT count(c.rowid) as nb, c.fk_statut as status";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql .= ", ".MAIN_DB_PREFIX."commande as c";
	if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE c.fk_soc = s.rowid";
	$sql .= " AND c.entity IN (".getEntity($commandestatic->element).")";
	if ($user->socid) {
		$sql .= ' AND c.fk_soc = '.((int) $user->socid);
	}
	if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	// Add where from hooks
	$parameters = array('socid' => $user->socid);
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $commandestatic); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;
	$sql .= " GROUP BY c.fk_statut";

	$resql = $db->query($sql);
	if ($resql) {
		$vals = array();
		while ($row = $db->fetch_row($resql)) {
			if (!isset($vals[$row[1]])) {
				$vals[$row[1]] = 0;
			}
			$vals[$row[1]] += $row[0];
		}
		$db->free($resql);

		require_once DOL_DOCUMENT_ROOT.'/core/lib/dashboard.lib.php';
		$colors = getThemeBadgeStatusColors();
		$colorofstatus = array(
			Commande::STATUS_DRAFT => '-'.$colors[0],
			Commande::STATUS_VALIDATED => $colors[1],
			Commande::STATUS_SHIPMENTONPROCESS => $colors[4],
			Commande::STATUS_CLOSED => $colors[6],
			Commande::STATUS_CANCELED => $colors[9],
		);

		$series = array();
		foreach (array(0, 1, 2, 3, -1) as $status) {
			$series[] = array(
				'label' => $commandestatic->LibStatut($status, 0, 1, 1),
				'labelnojs' => $commandestatic->LibStatut($status, 0, 0, 1),
				'nb' => (isset($vals[$status]) ? (int) $vals[$status] : 0),
				'color' => $colorofstatus[$status],
				'url' => DOL_URL_ROOT.'/commande/list.php?search_status='.$status,
			);
		}

		$result = getStatusPieChart($langs->trans("Statistics").' - '.$langs->trans("CustomersOrders"), $series, array('height' => 150, 'width' => 300));
	} else {
		dol_print_error($db);
	}

	return $result;
}
