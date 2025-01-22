<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 * \file       htdocs/core/lib/propal.lib.php
 * \brief      Ensemble de functions de base pour le module propal
 * \ingroup    propal
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Propal	$object		Object related to tabs
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function propal_prepare_head($object)
{
	global $db, $langs, $conf, $user;
	$langs->loadLangs(array('propal', 'compta', 'companies'));

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Proposal');
	$head[$h][2] = 'comm';
	$h++;

	if ((!isModEnabled('order') && ((isModEnabled("shipping") && getDolGlobalInt('MAIN_SUBMODULE_EXPEDITION') && $user->hasRight('expedition', 'lire'))
		|| (getDolGlobalInt('MAIN_SUBMODULE_DELIVERY') && $user->hasRight('expedition', 'delivery', 'lire'))))) {
		$langs->load("sendings");
		$text = '';
		$head[$h][0] = DOL_URL_ROOT.'/expedition/propal.php?id='.$object->id;
		if (getDolGlobalInt('MAIN_SUBMODULE_EXPEDITION')) {
			$text = $langs->trans("Shipment");
		}
		if (getDolGlobalInt('MAIN_SUBMODULE_EXPEDITION') && getDolGlobalInt('MAIN_SUBMODULE_DELIVERY')) {
			$text .= '/';
		}
		if (getDolGlobalInt('MAIN_SUBMODULE_DELIVERY')) {
			$text .= $langs->trans("Receivings");
		}
		$head[$h][1] = $text;
		$head[$h][2] = 'shipping';
		$h++;
	}

	if (!getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB')) {
		$nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
		$head[$h][0] = DOL_URL_ROOT.'/comm/propal/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		}
		$head[$h][2] = 'contact';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'propal', 'add', 'core');

	if (!getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT.'/comm/propal/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->propal->multidir_output[$object->entity]."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'document';
	$h++;


	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/agenda.php?id='.$object->id;
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
			$sql .= " AND elementtype = 'propal'";
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

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'propal', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'propal', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object information.
 *
 *  @return	array<array{0:string,1:string,2:string}>	head array with tabs
 */
function propal_admin_prepare_head()
{
	global $langs, $conf, $user, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('propal');
	$extrafields->fetch_name_optionals_label('propaldet');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/propal.php';
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'general';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'propal_admin');

	$head[$h][0] = DOL_URL_ROOT.'/comm/admin/propal_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = $extrafields->attributes['propal']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/admin/propaldet_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsLines");
	$nbExtrafields = $extrafields->attributes['propaldet']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributeslines';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'propal_admin', 'remove');

	return $head;
}



/**
 * Return a HTML table that contains a pie chart of customer proposals
 *
 * @param	int		$socid		(Optional) Show only results from the customer with this id
 * @return	string				A HTML table that contains a pie chart of customer invoices
 */
function getCustomerProposalPieChart($socid = 0)
{
	global $conf, $db, $langs, $user;

	$result = '';

	if (!isModEnabled('propal') || !$user->hasRight('propal', 'lire')) {
		return '';
	}

	$listofstatus = array(Propal::STATUS_DRAFT, Propal::STATUS_VALIDATED, Propal::STATUS_SIGNED, Propal::STATUS_NOTSIGNED, Propal::STATUS_BILLED);

	$propalstatic = new Propal($db);

	$sql = "SELECT count(p.rowid) as nb, p.fk_statut as status";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql .= ", ".MAIN_DB_PREFIX."propal as p";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE p.entity IN (".getEntity($propalstatic->element).")";
	$sql .= " AND p.fk_soc = s.rowid";
	if ($user->socid) {
		$sql .= ' AND p.fk_soc = '.((int) $user->socid);
	}
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	$sql .= " AND p.fk_statut IN (".$db->sanitize(implode(" ,", $listofstatus)).")";
	$sql .= " GROUP BY p.fk_statut";
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		$total = 0;
		$totalinprocess = 0;
		$dataseries = array();
		$colorseries = array();
		$vals = array();

		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$vals[$obj->status] = $obj->nb;
				$totalinprocess += $obj->nb;

				$total += $obj->nb;
			}
			$i++;
		}
		$db->free($resql);

		global $badgeStatus0, $badgeStatus1, $badgeStatus4, $badgeStatus6, $badgeStatus9;
		include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';

		$result = '<div class="div-table-responsive-no-min">';
		$result .= '<table class="noborder nohover centpercent">';

		$result .=  '<tr class="liste_titre">';
		$result .=  '<td colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("Proposals").'</td>';
		$result .=  '</tr>';

		foreach ($listofstatus as $status) {
			$dataseries[] = array($propalstatic->LibStatut($status, 1), (isset($vals[$status]) ? (int) $vals[$status] : 0));
			if ($status == Propal::STATUS_DRAFT) {
				$colorseries[$status] = '-'.$badgeStatus0;
			}
			if ($status == Propal::STATUS_VALIDATED) {
				$colorseries[$status] = $badgeStatus1;
			}
			if ($status == Propal::STATUS_SIGNED) {
				$colorseries[$status] = $badgeStatus4;
			}
			if ($status == Propal::STATUS_NOTSIGNED) {
				$colorseries[$status] = $badgeStatus9;
			}
			if ($status == Propal::STATUS_BILLED) {
				$colorseries[$status] = $badgeStatus6;
			}

			if (empty($conf->use_javascript_ajax)) {
				$result .=  '<tr class="oddeven">';
				$result .=  '<td>'.$propalstatic->LibStatut($status, 0).'</td>';
				$result .=  '<td class="right"><a href="list.php?statut='.$status.'">'.(isset($vals[$status]) ? $vals[$status] : 0).'</a></td>';
				$result .=  "</tr>\n";
			}
		}

		if ($conf->use_javascript_ajax) {
			$result .=  '<tr>';
			$result .=  '<td align="center" colspan="2">';

			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			$dolgraph = new DolGraph();
			$dolgraph->SetData($dataseries);
			$dolgraph->SetDataColor(array_values($colorseries));
			$dolgraph->setShowLegend(2);
			$dolgraph->setShowPercent(1);
			$dolgraph->SetType(array('pie'));
			$dolgraph->setHeight('150');
			$dolgraph->setWidth('300');
			$dolgraph->draw('idgraphthirdparties');
			$result .=  $dolgraph->show($total ? 0 : 1);

			$result .=  '</td>';
			$result .=  '</tr>';
		}

		//if ($totalinprocess != $total)
		//{
		//	print '<tr class="liste_total">';
		//	print '<td>'.$langs->trans("Total").' ('.$langs->trans("CustomersOrdersRunning").')</td>';
		//	print '<td class="right">'.$totalinprocess.'</td>';
		//	print '</tr>';
		//}

		$result .=  '<tr class="liste_total">';
		$result .=  '<td>'.$langs->trans("Total").'</td>';
		$result .=  '<td class="right">'.$total.'</td>';
		$result .=  '</tr>';

		$result .=  '</table>';
		$result .=  '</div>';
		$result .=  '<br>';
	} else {
		dol_print_error($db);
	}

	return $result;
}
