<?php
/* Copyright (C) 2006-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2012	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010		Juanjo Menent        <jmenent@2byte.es>
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
 *  \brief      Ensemble de fonctions de base pour le module commande
 *  \ingroup    commande
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Commande	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function commande_prepare_head(Commande $object)
{
	global $db, $langs, $conf, $user;
	if (isModEnabled("expedition")) {
		$langs->load("sendings");
	}
	$langs->load("orders");

	$h = 0;
	$head = array();

	if (isModEnabled('commande') && $user->hasRight('commande', 'lire')) {
		$head[$h][0] = DOL_URL_ROOT.'/commande/card.php?id='.$object->id;
		$head[$h][1] = $langs->trans("CustomerOrder");
		$head[$h][2] = 'order';
		$h++;
	}

	if (!getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB')) {
		$nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
		$head[$h][0] = DOL_URL_ROOT.'/commande/contact.php?id='.$object->id;
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
		$head[$h][0] = DOL_URL_ROOT.'/expedition/shipment.php?id='.$object->id;
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
		$head[$h][0] = DOL_URL_ROOT.'/commande/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->commande->multidir_output[$object->entity]."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/commande/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;


	$head[$h][0] = DOL_URL_ROOT.'/commande/agenda.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	if (isModEnabled('agenda')&& ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
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
 *  Return array head with list of tabs to view object informations.
 *
 *  @return	array   	    		    head array with tabs
 */
function order_admin_prepare_head()
{
	global $langs, $conf, $user, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('commande');
	$extrafields->fetch_name_optionals_label('commandedet');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/commande.php';
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'general';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'order_admin');

	$head[$h][0] = DOL_URL_ROOT.'/admin/order_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = $extrafields->attributes['commande']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/orderdet_extrafields.php';
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
	global $conf, $db, $langs, $user;

	$result = '';

	if (!isModEnabled('commande') || !$user->hasRight('commande', 'lire')) {
		return '';
	}

	$commandestatic = new Commande($db);

	/*
	 * Statistics
	 */

	$sql = "SELECT count(c.rowid) as nb, c.fk_statut as status";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql .= ", ".MAIN_DB_PREFIX."commande as c";
	if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE c.fk_soc = s.rowid";
	$sql .= " AND c.entity IN (".getEntity($commandestatic->element).")";
	if ($user->socid) {
		$sql .= ' AND c.fk_soc = '.((int) $user->socid);
	}
	if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	$sql .= " GROUP BY c.fk_statut";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		$total = 0;
		$totalinprocess = 0;
		$dataseries = array();
		$colorseries = array();
		$vals = array();
		// -1=Canceled, 0=Draft, 1=Validated, 2=Accepted/On process, 3=Closed (Sent/Received, billed or not)
		while ($i < $num) {
			$row = $db->fetch_row($resql);
			if ($row) {
				if (!isset($vals[$row[1]])) {
					$vals[$row[1]] = 0;
				}
				$vals[$row[1]] += $row[0];
				$totalinprocess += $row[0];
				$total += $row[0];
			}
			$i++;
		}
		$db->free($resql);

		global $badgeStatus0, $badgeStatus1, $badgeStatus4, $badgeStatus6, $badgeStatus9;
		include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';

		$result = '<div class="div-table-responsive-no-min">';
		$result .= '<table class="noborder nohover centpercent">';
		$result .= '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("CustomersOrders").'</th></tr>'."\n";
		$listofstatus = array(0, 1, 2, 3, -1);
		foreach ($listofstatus as $status) {
			$dataseries[] = array($commandestatic->LibStatut($status, 0, 1, 1), (isset($vals[$status]) ? (int) $vals[$status] : 0));
			if ($status == Commande::STATUS_DRAFT) {
				$colorseries[$status] = '-'.$badgeStatus0;
			}
			if ($status == Commande::STATUS_VALIDATED) {
				$colorseries[$status] = $badgeStatus1;
			}
			if ($status == Commande::STATUS_SHIPMENTONPROCESS) {
				$colorseries[$status] = $badgeStatus4;
			}
			if ($status == Commande::STATUS_CLOSED) {
				$colorseries[$status] = $badgeStatus6;
			}
			if ($status == Commande::STATUS_CANCELED) {
				$colorseries[$status] = $badgeStatus9;
			}

			if (empty($conf->use_javascript_ajax)) {
				$result .= '<tr class="oddeven">';
				$result .= '<td>'.$commandestatic->LibStatut($status, 0, 0, 1).'</td>';
				$result .= '<td class="right"><a href="list.php?statut='.$status.'">'.(isset($vals[$status]) ? $vals[$status] : 0).' ';
				$result .= $commandestatic->LibStatut($status, 0, 3, 1);
				$result .= '</a></td>';
				$result .= "</tr>\n";
			}
		}
		if (!empty($conf->use_javascript_ajax)) {
			$result .= '<tr class="impair"><td align="center" colspan="2">';

			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			$dolgraph = new DolGraph();
			$dolgraph->SetData($dataseries);
			$dolgraph->SetDataColor(array_values($colorseries));
			$dolgraph->setShowLegend(2);
			$dolgraph->setShowPercent(1);
			$dolgraph->SetType(array('pie'));
			$dolgraph->setHeight('150');
			$dolgraph->setWidth('300');
			$dolgraph->draw('idgraphstatus');
			$result .= $dolgraph->show($total ? 0 : 1);

			$result .= '</td></tr>';
		}

		//if ($totalinprocess != $total)
		$result .= '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td class="right">'.$total.'</td></tr>';
		$result .= "</table></div><br>";
	} else {
		dol_print_error($db);
	}

	return $result;
}
