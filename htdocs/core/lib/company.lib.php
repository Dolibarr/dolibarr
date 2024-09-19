<?php
/* Copyright (C) 2006-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2006       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2007       Patrick Raguin          <patrick.raguin@gmail.com>
 * Copyright (C) 2010-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013-2014  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013       Christophe Battarel     <contact@altairis.fr>
 * Copyright (C) 2013-2018  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2015-2024	Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2017       Rui Strecht             <rui.strecht@aliartalentos.com>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/core/lib/company.lib.php
 *	\brief      Ensemble de functions de base pour le module societe
 *	\ingroup    societe
 */

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @param 	Societe	$object		Object company shown
 * @return 	array				Array of tabs
 */
function societe_prepare_head(Societe $object)
{
	global $db, $langs, $conf, $user;
	global $hookmanager;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/societe/card.php?socid='.$object->id;
	$head[$h][1] = $langs->trans("ThirdParty");
	$head[$h][2] = 'card';
	$h++;


	if (!getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB') && $user->hasRight('societe', 'contact', 'lire')) {
		//$nbContact = count($object->liste_contact(-1,'internal')) + count($object->liste_contact(-1,'external'));
		$nbContact = 0;
		// Enable caching of thirdrparty count Contacts
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_contacts_thirdparty_'.$object->id;
		$dataretrieved = dol_getcache($cachekey);

		if (!is_null($dataretrieved)) {
			$nbContact = $dataretrieved;
		} else {
			$sql = "SELECT COUNT(p.rowid) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
			// Add table from hooks
			$parameters = array('contacttab' => true);
			$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
			$sql .= $hookmanager->resPrint;
			$sql .= " WHERE p.fk_soc = ".((int) $object->id);
			$sql .= " AND p.entity IN (".getEntity($object->element).")";
			// Add where from hooks
			$parameters = array('contacttab' => true);
			$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
			$sql .= $hookmanager->resPrint;
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$nbContact = $obj->nb;
			}

			dol_setcache($cachekey, $nbContact, 120);	// If setting cache fails, this is not a problem, so we do not test result.
		}

		$head[$h][0] = DOL_URL_ROOT.'/societe/contact.php?socid='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		}
		$head[$h][2] = 'contact';
		$h++;
	}
	if (getDolGlobalString('MAIN_SUPPORT_SHARED_CONTACT_BETWEEN_THIRDPARTIES')) {
		// Some features may be unstable with this option, like permissions rules, import contact, ...
		$head[$h][0] = DOL_URL_ROOT.'/societe/societecontact.php?socid='.$object->id;
		$nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
		$head[$h][1] = $langs->trans("ContactsAddressesExt");
		if ($nbContact > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		}
		$head[$h][2] = 'contactext';
		$h++;
	}

	if ($object->client == 1 || $object->client == 2 || $object->client == 3) {
		$head[$h][0] = DOL_URL_ROOT.'/comm/card.php?socid='.$object->id;
		$head[$h][1] = '';
		if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS') && ($object->client == 2 || $object->client == 3)) {
			$head[$h][1] .= $langs->trans("Prospect");
		}
		if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS') && !getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS') && $object->client == 3) {
			$head[$h][1] .= ' | ';
		}
		if (!getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS') && ($object->client == 1 || $object->client == 3)) {
			$head[$h][1] .= $langs->trans("Customer");
		}
		$head[$h][2] = 'customer';
		$h++;

		if (getDolGlobalString('PRODUIT_CUSTOMER_PRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_AND_MULTIPRICES')) {
			$langs->load("products");
			// price
			$head[$h][0] = DOL_URL_ROOT.'/societe/price.php?socid='.$object->id;
			$head[$h][1] = $langs->trans("CustomerPrices");
			$head[$h][2] = 'price';
			$h++;
		}
	}
	$supplier_module_enabled = 0;
	if (isModEnabled('supplier_proposal') || isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) {
		$supplier_module_enabled = 1;
	}
	if ($supplier_module_enabled == 1 && $object->fournisseur && $user->hasRight('fournisseur', 'lire')) {
		$head[$h][0] = DOL_URL_ROOT.'/fourn/card.php?socid='.$object->id;
		$head[$h][1] = $langs->trans("Supplier");
		$head[$h][2] = 'supplier';
		$h++;
	}

	if (isModEnabled('project') && ($user->hasRight('projet', 'lire'))) {
		$nbProject = 0;
		// Enable caching of thirdrparty count projects
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_projects_thirdparty_'.$object->id;
		$dataretrieved = dol_getcache($cachekey);

		if (!is_null($dataretrieved)) {
			$nbProject = $dataretrieved;
		} else {
			$sql = "SELECT COUNT(n.rowid) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."projet as n";
			$sql .= " WHERE fk_soc = ".((int) $object->id);
			$sql .= " AND entity IN (".getEntity('project').")";
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$nbProject = $obj->nb;
			} else {
				dol_print_error($db);
			}
			dol_setcache($cachekey, $nbProject, 120);	// If setting cache fails, this is not a problem, so we do not test result.
		}
		$head[$h][0] = DOL_URL_ROOT.'/societe/project.php?socid='.$object->id;
		$head[$h][1] = $langs->trans("Projects");
		if ($nbProject > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbProject.'</span>';
		}
		$head[$h][2] = 'project';
		$h++;
	}

	// Tab to link resources
	if (isModEnabled('resource') && getDolGlobalString('RESOURCE_ON_THIRDPARTIES')) {
		$head[$h][0] = DOL_URL_ROOT.'/resource/element_resource.php?element=societe&element_id='.$object->id;
		$head[$h][1] = $langs->trans("Resources");
		$head[$h][2] = 'resources';
		$h++;
	}

	// Related items
	if ((isModEnabled('order') || isModEnabled('propal') || isModEnabled('invoice') || isModEnabled('intervention') || isModEnabled("supplier_proposal") || isModEnabled("supplier_order") || isModEnabled("supplier_invoice"))
		&& !getDolGlobalString('THIRDPARTIES_DISABLE_RELATED_OBJECT_TAB')) {
		$head[$h][0] = DOL_URL_ROOT.'/societe/consumption.php?socid='.$object->id;
		$head[$h][1] = $langs->trans("Referers");
		$head[$h][2] = 'consumption';
		$h++;
	}

	// Bank accounts
	if (!getDolGlobalInt('SOCIETE_DISABLE_BANKACCOUNT')) {
		$nbBankAccount = 0;
		$foundonexternalonlinesystem = 0;
		$langs->load("bills");

		$title = $langs->trans("PaymentModes");

		if (isModEnabled('stripe')) {
			$servicestatus = 0;
			if (getDolGlobalString('STRIPE_LIVE') && !GETPOST('forcesandbox', 'alpha')) {
				$servicestatus = 1;
			}

			include_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';
			$societeaccount = new SocieteAccount($db);
			$stripecu = $societeaccount->getCustomerAccount($object->id, 'stripe', $servicestatus); // Get thirdparty cu_...
			if ($stripecu) {
				$foundonexternalonlinesystem++;
			}
		}

		$sql = "SELECT COUNT(n.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_rib as n";
		$sql .= " WHERE n.fk_soc = ".((int) $object->id);
		if (!isModEnabled('stripe')) {
			$sql .= " AND n.stripe_card_ref IS NULL";
		} else {
			$sql .= " AND (n.stripe_card_ref IS NULL OR (n.stripe_card_ref IS NOT NULL AND n.status = ".((int) $servicestatus)."))";
		}

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			$nbBankAccount = $obj->nb;
		} else {
			dol_print_error($db);
		}

		//if (isModEnabled('stripe') && $nbBankAccount > 0) $nbBankAccount = '...';	// No way to know exact number

		$head[$h][0] = DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.urlencode((string) ($object->id));
		$head[$h][1] = $title;
		if ($foundonexternalonlinesystem) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">...</span>';
		} elseif ($nbBankAccount > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbBankAccount.'</span>';
		}
		$head[$h][2] = 'rib';
		$h++;
	}

	if ((isModEnabled('website') || isModEnabled('webportal')) && $user->hasRight('societe', 'lire')) {
		$site_filter_list = array();
		if (isModEnabled('website')) {
			$site_filter_list[] = 'dolibarr_website';
		}
		if (isModEnabled('webportal')) {
			$site_filter_list[] = 'dolibarr_portal';
		}

		$head[$h][0] = DOL_URL_ROOT.'/societe/website.php?id='.urlencode((string) ($object->id));
		$head[$h][1] = $langs->trans("WebSiteAccounts");
		$nbNote = 0;
		$sql = "SELECT COUNT(n.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_account as n";
		$sql .= " WHERE fk_soc = ".((int) $object->id);
		$sql .= " AND entity IN (".getEntity('thirdpartyaccount').")";
		if (!empty($site_filter_list)) {
			$sql .= " AND n.site IN (".$db->sanitize("'".implode("','", $site_filter_list)."'", 1).")";
		}
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			$nbNote = $obj->nb;
		} else {
			dol_print_error($db);
		}
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'website';
		$h++;
	}

	if (getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR', 'thirdparty') == 'thirdparty') {
		if ($user->hasRight('partnership', 'read')) {
			$langs->load("partnership");
			$nbPartnership = is_array($object->partnerships) ? count($object->partnerships) : 0;
			$head[$h][0] = DOL_URL_ROOT.'/partnership/partnership_list.php?socid='.$object->id;
			$head[$h][1] = $langs->trans("Partnerships");
			$nbNote = 0;
			$sql = "SELECT COUNT(n.rowid) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."partnership as n";
			$sql .= " WHERE fk_soc = ".((int) $object->id);
			$sql .= " AND entity IN (".getEntity('partnership').")";
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$nbNote = $obj->nb;
			} else {
				dol_print_error($db);
			}
			if ($nbNote > 0) {
				$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
			}
			$head[$h][2] = 'partnerships';
			if ($nbPartnership > 0) {
				$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbPartnership.'</span>';
			}
			$h++;
		}
	}

	// Notifications
	if (isModEnabled('ticket') && $user->hasRight("ticket", "read")) {
		//$langs->load('ticket');
		$nbTicket = 0;
		// Enable caching of thirdparty count notifications
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_ticket_thirdparty_'.$object->id;
		$nbticketretreived = dol_getcache($cachekey);
		if (!is_null($nbticketretreived)) {
			$nbTicket = $nbticketretreived;
		} else {
			// List of notifications enabled for contacts of the third party
			$sql = "SELECT COUNT(t.rowid) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."ticket as t";
			$sql .= " WHERE t.fk_soc = ".((int) $object->id);
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$nbTicket = $obj->nb;
			} else {
				dol_print_error($db);
			}
			dol_setcache($cachekey, $nbTicket, 120);		// If setting cache fails, this is not a problem, so we do not test result.
		}

		$head[$h][0] = DOL_URL_ROOT.'/ticket/list.php?socid='.urlencode((string) ($object->id));
		$head[$h][1] = $langs->trans("Tickets");
		if ($nbTicket > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbTicket.'</span>';
		}
		$head[$h][2] = 'ticket';
		$h++;
	}


	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'thirdparty', 'add', 'core');

	if ($user->socid == 0) {
		// Notifications
		if (isModEnabled('notification')) {
			$langs->load('mails');
			$nbNotif = 0;
			// Enable caching of thirdparty count notifications
			require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
			$cachekey = 'count_notifications_thirdparty_'.$object->id;
			$dataretrieved = dol_getcache($cachekey);
			if (!is_null($dataretrieved)) {
				$nbNotif = $dataretrieved;
			} else {
				// List of notifications enabled for contacts of the third party
				$sql = "SELECT COUNT(n.rowid) as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX."c_action_trigger as a,";
				$sql .= " ".MAIN_DB_PREFIX."notify_def as n,";
				$sql .= " ".MAIN_DB_PREFIX."socpeople as c";
				$sql .= " WHERE a.rowid = n.fk_action";
				$sql .= " AND c.rowid = n.fk_contact";
				$sql .= " AND c.fk_soc = ".((int) $object->id);
				$resql = $db->query($sql);
				if ($resql) {
					$obj = $db->fetch_object($resql);
					$nbNotif = $obj->nb;
				} else {
					dol_print_error($db);
				}
				dol_setcache($cachekey, $nbNotif, 120);		// If setting cache fails, this is not a problem, so we do not test result.
			}

			$head[$h][0] = DOL_URL_ROOT.'/societe/notify/card.php?socid='.urlencode((string) ($object->id));
			$head[$h][1] = $langs->trans("Notifications");
			if ($nbNotif > 0) {
				$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNotif.'</span>';
			}
			$head[$h][2] = 'notify';
			$h++;
		}

		// Notes
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT.'/societe/note.php?id='.urlencode((string) ($object->id));
		$head[$h][1] = $langs->trans("Notes");
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'note';
		$h++;

		// Attached files and Links
		$totalAttached = 0;
		// Enable caching of thirdrparty count attached files and links
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_attached_thirdparty_'.$object->id;
		$dataretrieved = dol_getcache($cachekey);
		if (!is_null($dataretrieved)) {
			$totalAttached = $dataretrieved;
		} else {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
			$upload_dir = $conf->societe->multidir_output[$object->entity]."/".$object->id;
			$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
			$nbLinks = Link::count($db, $object->element, $object->id);
			$totalAttached = $nbFiles + $nbLinks;
			dol_setcache($cachekey, $totalAttached, 120);		// If setting cache fails, this is not a problem, so we do not test result.
		}

		$head[$h][0] = DOL_URL_ROOT.'/societe/document.php?socid='.$object->id;
		$head[$h][1] = $langs->trans("Documents");
		if (($totalAttached) > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($totalAttached).'</span>';
		}
		$head[$h][2] = 'document';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/societe/messaging.php?socid='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
		$nbEvent = 0;
		// Enable caching of thirdparty count actioncomm
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_events_thirdparty_'.$object->id;
		$dataretrieved = dol_getcache($cachekey);
		if (!is_null($dataretrieved)) {
			$nbEvent = $dataretrieved;
		} else {
			$sql = "SELECT COUNT(id) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm";
			$sql .= " WHERE fk_soc = ".((int) $object->id);
			$sql .= " AND entity IN (".getEntity('agenda').")";
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

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'thirdparty', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'thirdparty', 'remove');

	return $head;
}


/**
 * Return array of tabs to used on page
 *
 * @param	Object	$object		Object for tabs
 * @return	array				Array of tabs
 */
function societe_prepare_head2($object)
{
	global $langs;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/societe/card.php?socid='.$object->id;
	$head[$h][1] = $langs->trans("ThirdParty");
	$head[$h][2] = 'company';
	$h++;

	$head[$h][0] = 'commerciaux.php?socid='.$object->id;
	$head[$h][1] = $langs->trans("SalesRepresentative");
	$head[$h][2] = 'salesrepresentative';
	$h++;

	return $head;
}



/**
 *  Return array head with list of tabs to view object information.
 *
 *  @return	array   	        head array with tabs
 */
function societe_admin_prepare_head()
{
	global $langs, $conf, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('societe');
	$extrafields->fetch_name_optionals_label('socpeople');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/societe/admin/societe.php';
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'general';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'company_admin');

	$head[$h][0] = DOL_URL_ROOT.'/societe/admin/societe_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsThirdParties");
	$nbExtrafields = $extrafields->attributes['societe']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/societe/admin/contact_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsContacts");
	$nbExtrafields = $extrafields->attributes['socpeople']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes_contacts';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'company_admin', 'remove');

	return $head;
}



/**
 *    Return country label, code or id from an id, code or label
 *
 *    @param	int|string	$searchkey      Id or code of country to search
 *    @param    string		$withcode   	'' or '0' = Return label,
 *    										'1'=Return code + label,
 *    										'2'=Return code from id,
 *    										'3'=Return id from code,
 * 	   										'all'=Return array('id'=>,'code'=>,'label'=>)
 *    @param	DoliDB		$dbtouse       	Database handler (using in global way may fail because of conflicts with some autoload features)
 *    @param	Translate	$outputlangs	Langs object for output translation
 *    @param	int			$entconv       	0=Return value without entities and not converted to output charset, 1=Ready for html output
 *    @param	string		$searchlabel    Label of country to search (warning: searching on label is not reliable)
 *    @return	int|string|array{id:int,code:string,label:string}	Integer with country id or String with country code or translated country name or Array('id','code','label') or 'NotDefined'
 */
function getCountry($searchkey, $withcode = '', $dbtouse = null, $outputlangs = null, $entconv = 1, $searchlabel = '')
{
	global $db, $langs;

	$result = '';

	// Check parameters
	if (empty($searchkey) && empty($searchlabel)) {
		if ($withcode === 'all') {
			return array('id' => 0, 'code' => '', 'label' => '');
		} else {
			return '';
		}
	}
	if (!is_object($dbtouse)) {
		$dbtouse = $db;
	}
	if (!is_object($outputlangs)) {
		$outputlangs = $langs;
	}

	$sql = "SELECT rowid, code, label FROM ".MAIN_DB_PREFIX."c_country";
	if (is_numeric($searchkey)) {
		$sql .= " WHERE rowid = ".((int) $searchkey);
	} elseif (!empty($searchkey)) {
		$sql .= " WHERE code = '".$db->escape($searchkey)."'";
	} else {
		$sql .= " WHERE label = '".$db->escape($searchlabel)."'";
	}

	$resql = $dbtouse->query($sql);
	if ($resql) {
		$obj = $dbtouse->fetch_object($resql);
		if ($obj) {
			$label = ((!empty($obj->label) && $obj->label != '-') ? $obj->label : '');
			if (is_object($outputlangs)) {
				$outputlangs->load("dict");
				if ($entconv) {
					$label = ($obj->code && ($outputlangs->trans("Country".$obj->code) != "Country".$obj->code)) ? $outputlangs->trans("Country".$obj->code) : $label;
				} else {
					$label = ($obj->code && ($outputlangs->transnoentitiesnoconv("Country".$obj->code) != "Country".$obj->code)) ? $outputlangs->transnoentitiesnoconv("Country".$obj->code) : $label;
				}
			}
			if ($withcode == '1') {
				$result = $label ? "$obj->code - $label" : "$obj->code";
			} elseif ($withcode == '2') {
				$result = $obj->code;
			} elseif ($withcode == '3') {
				$result = $obj->rowid;
			} elseif ($withcode === 'all') {
				$result = array('id' => $obj->rowid, 'code' => $obj->code, 'label' => $label);
			} else {
				$result = $label;
			}
		} else {
			$result = 'NotDefined';
		}
		$dbtouse->free($resql);
		return $result;
	} else {
		dol_print_error($dbtouse, '');
	}
	return 'Error';
}

/**
 *    Return state translated from an id. Return value is always utf8 encoded and without entities.
 *
 *    @param    int			$id         	id of state (province/departement)
 *    @param    '0'|'1'|'2'|'all'	$withcode	'0'=Return label,
 *                                              '1'=Return string code + label,
 *                                              '2'=Return code,
 *                                              'all'=return array('id'=>,'code'=>,'label'=>)
 *    @param	?DoliDB		$dbtouse		Database handler (using in global way may fail because of conflicts with some autoload features)
 *    @param    int<0,1>	$withregion   	'0'=Ignores region,
 *    										'1'=Add region name/code/id as needed to output,
 *    @param    Translate	$outputlangs	Langs object for output translation, not fully implemented yet
 *    @param    int<0,1>    $entconv       	0=Return value without entities and not converted to output charset, 1=Ready for html output
 *    @return   string|array{id:int,code:string,label:string}|array{id:int,code:string,label:string,region_code:string,region:string}		String with state code or state name or Array('id','code','label')/Array('id','code','label','region_code','region')
 */
function getState($id, $withcode = '0', $dbtouse = null, $withregion = 0, $outputlangs = null, $entconv = 1)
{
	global $db, $langs;

	if (!is_object($dbtouse)) {
		$dbtouse = $db;
	}

	$sql = "SELECT d.rowid as id, d.code_departement as code, d.nom as name, d.active, c.label as country, c.code as country_code, r.code_region as region_code, r.nom as region_name FROM";
	$sql .= " ".MAIN_DB_PREFIX."c_departements as d, ".MAIN_DB_PREFIX."c_regions as r,".MAIN_DB_PREFIX."c_country as c";
	$sql .= " WHERE d.fk_region=r.code_region and r.fk_pays=c.rowid and d.rowid=".((int) $id);
	$sql .= " AND d.active = 1 AND r.active = 1 AND c.active = 1";
	$sql .= " ORDER BY c.code, d.code_departement";

	dol_syslog("Company.lib::getState", LOG_DEBUG);
	$resql = $dbtouse->query($sql);
	if ($resql) {
		$obj = $dbtouse->fetch_object($resql);
		if ($obj) {
			$label = ((!empty($obj->name) && $obj->name != '-') ? $obj->name : '');
			if (is_object($outputlangs)) {
				$outputlangs->load("dict");
				if ($entconv) {
					$label = ($obj->code && ($outputlangs->trans("State".$obj->code) != "State".$obj->code)) ? $outputlangs->trans("State".$obj->code) : $label;
				} else {
					$label = ($obj->code && ($outputlangs->transnoentitiesnoconv("State".$obj->code) != "State".$obj->code)) ? $outputlangs->transnoentitiesnoconv("State".$obj->code) : $label;
				}
			}

			if ($withcode == 1) {
				if ($withregion == 1) {
					return $label = $obj->region_name.' - '.$obj->code.' - '.($langs->trans($obj->code) != $obj->code ? $langs->trans($obj->code) : ($obj->name != '-' ? $obj->name : ''));
				} else {
					return $label = $obj->code.' - '.($langs->trans($obj->code) != $obj->code ? $langs->trans($obj->code) : ($obj->name != '-' ? $obj->name : ''));
				}
			} elseif ($withcode == 2) {
				if ($withregion == 1) {
					return $label = $obj->region_name.' - '.($langs->trans($obj->code) != $obj->code ? $langs->trans($obj->code) : ($obj->name != '-' ? $obj->name : ''));
				} else {
					return $label = ($langs->trans($obj->code) != $obj->code ? $langs->trans($obj->code) : ($obj->name != '-' ? $obj->name : ''));
				}
			} elseif ($withcode === 'all') {
				if ($withregion == 1) {
					return array('id' => $obj->id, 'code' => $obj->code, 'label' => $label, 'region_code' => $obj->region_code, 'region' => $obj->region_name);
				} else {
					return array('id' => $obj->id, 'code' => $obj->code, 'label' => $label);
				}
			} else {
				if ($withregion == 1) {
					return $label = $obj->region_name.' - '.$label;
				} else {
					return $label;
				}
			}
		} else {
			return $langs->transnoentitiesnoconv("NotDefined");
		}
	} else {
		dol_print_error($dbtouse, '');
	}

	return '';
}

/**
 *    Return label of currency or code+label
 *
 *    @param      string	$code_iso       Code iso of currency
 *    @param      int		$withcode       '1'=show code + label
 *    @param      Translate $outputlangs    Output language
 *    @return     string     			    Label translated of currency
 */
function currency_name($code_iso, $withcode = 0, $outputlangs = null)
{
	global $langs, $db;

	if (empty($outputlangs)) {
		$outputlangs = $langs;
	}

	$outputlangs->load("dict");

	// If there is a translation, we can send immediately the label
	if ($outputlangs->trans("Currency".$code_iso) != "Currency".$code_iso) {
		return ($withcode ? $code_iso.' - ' : '').$outputlangs->trans("Currency".$code_iso);
	}

	// If no translation, we read table to get label by default
	$sql = "SELECT label FROM ".MAIN_DB_PREFIX."c_currencies";
	$sql .= " WHERE code_iso='".$db->escape($code_iso)."'";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		if ($num) {
			$obj = $db->fetch_object($resql);
			$label = ($obj->label != '-' ? $obj->label : '');
			if ($withcode) {
				return ($label == $code_iso) ? "$code_iso" : "$code_iso - $label";
			} else {
				return $label;
			}
		} else {
			return $code_iso;
		}
	}
	return 'ErrorWhenReadingCurrencyLabel';
}

/**
 *    Return the name translated of juridical status.
 *    This method include a cache.
 *
 *    @param      string	$code       Code of juridical status
 *    @return     string     			Value of the juridical status
 */
function getFormeJuridiqueLabel($code)
{
	global $conf, $db, $langs;

	if (!$code) {
		return '';
	}

	if (!empty($conf->cache["legalform_".$langs->defaultlang.'_'.$code])) {
		return $conf->cache["legalform_".$langs->defaultlang.'_'.$code];
	}

	$sql = "SELECT libelle as label FROM ".MAIN_DB_PREFIX."c_forme_juridique";
	$sql .= " WHERE code = '".$db->escape($code)."'";

	dol_syslog("Company.lib::getFormeJuridiqueLabel", LOG_DEBUG);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		if ($num) {
			$obj = $db->fetch_object($resql);

			$label = ($obj->label != '-' ? $obj->label : '');

			$conf->cache["legalform_".$langs->defaultlang.'_'.$code] = $label;

			return $langs->trans($label);
		} else {
			return $langs->trans("NotDefined");
		}
	} else {
		return 'Error '.$db->lasterror();
	}
}


/**
 *  Return list of countries that are inside the EEC (European Economic Community)
 *  Note: Try to keep this function as a "memory only" function for performance reasons.
 *
 *  @return     array					Array of countries code in EEC
 */
function getCountriesInEEC()
{
	// List of all country codes that are in europe for european vat rules
	// List found on https://ec.europa.eu/taxation_customs/territorial-status-eu-countries-and-certain-territories_en
	global $conf, $db;
	$country_code_in_EEC = array();

	if (!empty($conf->cache['country_code_in_EEC'])) {
		// Use of cache to reduce number of database requests
		$country_code_in_EEC = $conf->cache['country_code_in_EEC'];
	} else {
		$sql = "SELECT cc.code FROM ".MAIN_DB_PREFIX."c_country as cc";
		$sql .= " WHERE cc.eec = 1";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$objp = $db->fetch_object($resql);
				$country_code_in_EEC[] = $objp->code;
				$i++;
			}
		} else {
			dol_print_error($db);
		}
		$conf->cache['country_code_in_EEC'] = $country_code_in_EEC;
	}
	return $country_code_in_EEC;
}

/**
 *  Return if a country of an object is inside the EEC (European Economic Community)
 *
 *  @param      Object      $object    Object
 *  @return     boolean		           true = country inside EEC, false = country outside EEC
 */
function isInEEC($object)
{
	if (empty($object->country_code)) {
		return false;
	}

	$country_code_in_EEC = getCountriesInEEC();		// This make a database call but there is a cache done into $conf->cache['country_code_in_EEC']

	//print "dd".$object->country_code;
	return in_array($object->country_code, $country_code_in_EEC);
}


/**
 * 		Show html area for list of projects
 *
 *		@param	Conf		$conf			Object conf
 * 		@param	Translate	$langs			Object langs
 * 		@param	DoliDB		$db				Database handler
 * 		@param	Object		$object			Third party object
 *      @param  string		$backtopage		Url to go once contact is created
 *      @param  int         $nocreatelink   1=Hide create project link
 *      @param	string		$morehtmlright	More html on right of title
 *      @return	int
 */
function show_projects($conf, $langs, $db, $object, $backtopage = '', $nocreatelink = 0, $morehtmlright = '')
{
	global $user, $action, $hookmanager, $form, $massactionbutton, $massaction, $arrayofselected, $arrayofmassactions;

	$i = -1;

	if (isModEnabled('project') && $user->hasRight('projet', 'lire')) {
		$langs->load("projects");

		$newcardbutton = '';
		if (isModEnabled('project') && $user->hasRight('projet', 'creer') && empty($nocreatelink)) {
			$newcardbutton .= dolGetButtonTitle($langs->trans('AddProject'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/projet/card.php?socid='.$object->id.'&amp;action=create&amp;backtopage='.urlencode($backtopage));
		}

		print "\n";
		print load_fiche_titre($langs->trans("ProjectsDedicatedToThisThirdParty"), $newcardbutton.$morehtmlright, '');

		print '<div class="div-table-responsive">'."\n";
		print '<table class="noborder centpercent">';

		$sql  = "SELECT p.rowid as id, p.entity, p.title, p.ref, p.public, p.dateo as do, p.datee as de, p.fk_statut as status, p.fk_opp_status, p.opp_amount, p.opp_percent, p.tms as date_modification, p.budget_amount";
		$sql .= ", cls.code as opp_status_code";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_lead_status as cls on p.fk_opp_status = cls.rowid";
		$sql .= " WHERE p.fk_soc = ".((int) $object->id);
		$sql .= " AND p.entity IN (".getEntity('project').")";
		$sql .= " ORDER BY p.dateo DESC";

		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);

			print '<tr class="liste_titre">';
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="center">';
				$selectedfields = (is_array($arrayofmassactions) && count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');
				print $selectedfields;
				print '</td>';
			}
			print '<td>'.$langs->trans("Ref").'</td>';
			print '<td>'.$langs->trans("Name").'</td>';
			print '<td class="center">'.$langs->trans("DateStart").'</td>';
			print '<td class="center">'.$langs->trans("DateEnd").'</td>';
			print '<td class="right">'.$langs->trans("OpportunityAmountShort").'</td>';
			print '<td class="center">'.$langs->trans("OpportunityStatusShort").'</td>';
			print '<td class="right">'.$langs->trans("OpportunityProbabilityShort").'</td>';
			print '<td class="right">'.$langs->trans("Status").'</td>';
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="center">';
				$selectedfields = (is_array($arrayofmassactions) && count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');
				print $selectedfields;
				print '</td>';
			}
			print '</tr>';

			if ($num > 0) {
				require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

				$projecttmp = new Project($db);

				$i = 0;

				while ($i < $num) {
					$obj = $db->fetch_object($result);
					$projecttmp->fetch($obj->id);

					// To verify role of users
					$userAccess = $projecttmp->restrictedProjectArea($user);

					if ($user->hasRight('projet', 'lire') && $userAccess > 0) {
						print '<tr class="oddeven">';

						if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
							print '<td class="nowrap center actioncolumn">';
							if ($massactionbutton || $massaction) {
								$selected = 0;
								if (in_array($obj->id, $arrayofselected)) {
									$selected = 1;
								}
								print '<input id="cb'.$obj->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->id.'"'.($selected ? ' checked="checked"' : '').'>';
							}
							print '</td>';
						}
						// Ref
						print '<td class="nowraponall">';
						print $projecttmp->getNomUrl(1, '', 0, '', '-', 0, 1, '', 'project:'.$_SERVER["PHP_SELF"].'?socid=__SOCID__');
						print '</td>';

						// Label
						print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->title).'">'.dol_escape_htmltag($obj->title).'</td>';
						// Date start
						print '<td class="center">'.dol_print_date($db->jdate($obj->do), "day").'</td>';
						// Date end
						print '<td class="center">'.dol_print_date($db->jdate($obj->de), "day").'</td>';
						// Opp amount
						print '<td class="right">';
						if ($obj->opp_status_code) {
							print '<span class="amount">'.price($obj->opp_amount, 1, '', 1, -1, -1, '').'</span>';
						}
						print '</td>';
						// Opp status
						print '<td class="center">';
						if ($obj->opp_status_code) {
							print $langs->trans("OppStatus".$obj->opp_status_code);
						}
						print '</td>';
						// Opp percent
						print '<td class="right">';
						if ($obj->opp_percent) {
							print price($obj->opp_percent, 1, '', 1, 0).'%';
						}
						print '</td>';
						// Status
						print '<td class="right">'.$projecttmp->getLibStatut(5).'</td>';

						// Action column
						if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
							print '<td class="nowrap center actioncolumn">';
							if ($massactionbutton || $massaction) {
								$selected = 0;
								if (in_array($obj->id, $arrayofselected)) {
									$selected = 1;
								}
								print '<input id="cb'.$obj->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->id.'"'.($selected ? ' checked="checked"' : '').'>';
							}
							print '</td>';
						}
						print '</tr>';
					}
					$i++;
				}
			} else {
				print '<tr class="oddeven"><td colspan="9"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
			}
			$db->free($result);
		} else {
			dol_print_error($db);
		}

		//projects linked to that thirdpart because of a people of that company is linked to a project
		if (getDolGlobalString('PROJECT_DISPLAY_LINKED_BY_CONTACT')) {
			print "\n";
			print load_fiche_titre($langs->trans("ProjectsLinkedToThisThirdParty"), '', '');


			print '<div class="div-table-responsive">'."\n";
			print '<table class="noborder centpercent">';

			$sql  = "SELECT p.rowid as id, p.entity, p.title, p.ref, p.public, p.dateo as do, p.datee as de, p.fk_statut as status, p.fk_opp_status, p.opp_amount, p.opp_percent, p.tms as date_update, p.budget_amount";
			$sql .= ", cls.code as opp_status_code";
			$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_lead_status as cls on p.fk_opp_status = cls.rowid";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec on p.rowid = ec.element_id";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sc on ec.fk_socpeople = sc.rowid";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc on ec.fk_c_type_contact = tc.rowid";
			$sql .= " WHERE sc.fk_soc = ".((int) $object->id);
			$sql .= " AND p.entity IN (".getEntity('project').")";
			$sql .= " AND tc.element = 'project'";
			$sql .= " ORDER BY p.dateo DESC";

			$result = $db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);

				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Ref").'</td>';
				print '<td>'.$langs->trans("Name").'</td>';
				print '<td class="center">'.$langs->trans("DateStart").'</td>';
				print '<td class="center">'.$langs->trans("DateEnd").'</td>';
				print '<td class="right">'.$langs->trans("OpportunityAmountShort").'</td>';
				print '<td class="center">'.$langs->trans("OpportunityStatusShort").'</td>';
				print '<td class="right">'.$langs->trans("OpportunityProbabilityShort").'</td>';
				print '<td class="right">'.$langs->trans("Status").'</td>';
				print '</tr>';

				if ($num > 0) {
					require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

					$projecttmp = new Project($db);

					$i = 0;

					while ($i < $num) {
						$obj = $db->fetch_object($result);
						$projecttmp->fetch($obj->id);

						// To verify role of users
						$userAccess = $projecttmp->restrictedProjectArea($user);

						if ($user->rights->projet->lire && $userAccess > 0) {
							print '<tr class="oddeven">';

							// Ref
							print '<td class="nowraponall">';
							print $projecttmp->getNomUrl(1, '', 0, '', '-', 0, 1, '', 'project:'.$_SERVER["PHP_SELF"].'?socid=__SOCID__');
							print '</td>';

							// Label
							print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->title).'">'.dol_escape_htmltag($obj->title).'</td>';
							// Date start
							print '<td class="center">'.dol_print_date($db->jdate($obj->do), "day").'</td>';
							// Date end
							print '<td class="center">'.dol_print_date($db->jdate($obj->de), "day").'</td>';
							// Opp amount
							print '<td class="right">';
							if ($obj->opp_status_code) {
								print '<span class="amount">'.price($obj->opp_amount, 1, '', 1, -1, -1, '').'</span>';
							}
							print '</td>';
							// Opp status
							print '<td class="center">';
							if ($obj->opp_status_code) {
								print $langs->trans("OppStatus".$obj->opp_status_code);
							}
							print '</td>';
							// Opp percent
							print '<td class="right">';
							if ($obj->opp_percent) {
								print price($obj->opp_percent, 1, '', 1, 0).'%';
							}
							print '</td>';
							// Status
							print '<td class="right">'.$projecttmp->getLibStatut(5).'</td>';

							print '</tr>';
						}
						$i++;
					}
				} else {
					print '<tr class="oddeven"><td colspan="8"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
				}
				$db->free($result);
			} else {
				dol_print_error($db);
			}
		}

		$parameters = array('sql' => $sql, 'function' => 'show_projects');
		$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		print "</table>";
		print '</div>';

		print "<br>\n";
	}

	return $i;
}


/**
 * 		Show html area for list of contacts
 *
 *		@param	Conf		$conf			Object conf
 * 		@param	Translate	$langs			Object langs
 * 		@param	DoliDB		$db				Database handler
 * 		@param	Societe		$object			Third party object
 *      @param  string		$backtopage		Url to go once contact is created
 *      @param	int			$showuserlogin 	1=Show also user login if it exists
 *      @return	int
 */
function show_contacts($conf, $langs, $db, $object, $backtopage = '', $showuserlogin = 0)
{
	global $user, $conf, $extrafields, $hookmanager;
	global $contextpage;

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	$formcompany = new FormCompany($db);
	$form = new Form($db);

	$optioncss = GETPOST('optioncss', 'alpha');
	$sortfield = GETPOST('sortfield', 'aZ09comma');
	$sortorder = GETPOST('sortorder', 'aZ09comma');
	$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");

	$search_status = GETPOST("search_status", "intcomma");
	if ($search_status == '') {
		$search_status = 1; // always display active customer first
	}

	$search_rowid   = GETPOST("search_rowid", "intcomma");
	$search_name    = GETPOST("search_name", 'alpha');
	$search_address = GETPOST("search_address", 'alpha');
	$search_poste   = GETPOST("search_poste", 'alpha');
	$search_note_private = GETPOST('search_note_private', 'alphanohtml');
	$search_roles = GETPOST("search_roles", 'array');
	$search_birthday_dtstart = GETPOST("search_birthday_dtstart", 'alpha');
	$search_birthday_dtend = GETPOST("search_birthday_dtend", 'alpha');

	if ($search_birthday_dtstart != '' || $search_birthday_dtend != '') {
		$search_birthday_dtstart = dol_mktime(0, 0, 0, GETPOSTINT('search_birthday_dtstartmonth'), GETPOSTINT('search_birthday_dtstartday'), GETPOSTINT('search_birthday_dtstartyear'));
		$search_birthday_dtend = dol_mktime(23, 59, 59, GETPOSTINT('search_birthday_dtendmonth'), GETPOSTINT('search_birthday_dtendday'), GETPOSTINT('search_birthday_dtendyear'));
	}
	$socialnetworks = getArrayOfSocialNetworks();

	$searchAddressPhoneDBFields = array(
		//Address
		't.address',
		't.zip',
		't.town',

		//Phone
		't.phone',
		't.phone_perso',
		't.phone_mobile',

		//Fax
		't.fax',

		//E-mail
		't.email',
	);
	//Social media
	//    foreach ($socialnetworks as $key => $value) {
	//        if ($value['active']) {
	//            $searchAddressPhoneDBFields['t.'.$key] = "t.socialnetworks->'$.".$key."'";
	//        }
	//    }

	if (!$sortorder) {
		$sortorder = "ASC";
	}
	if (!$sortfield) {
		$sortfield = "t.lastname";
	}

	if (isModEnabled('clicktodial')) {
		$user->fetch_clicktodial(); // lecture des infos de clicktodial du user
	}


	$contactstatic = new Contact($db);

	$extrafields->fetch_name_optionals_label($contactstatic->table_element);

	$contactstatic->fields = array(
		'rowid'     => array('type' => 'integer', 'label' => "TechnicalID", 'enabled' => (getDolGlobalString('MAIN_SHOW_TECHNICAL_ID') ? 1 : 0), 'visible' => (getDolGlobalString('MAIN_SHOW_TECHNICAL_ID') ? 1 : 0), 'position' => 1),
		'name'      => array('type' => 'varchar(128)', 'label' => 'Name', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'showoncombobox' => 1, 'index' => 1, 'position' => 10, 'searchall' => 1),
		'poste'     => array('type' => 'varchar(128)', 'label' => 'PostOrFunction', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'showoncombobox' => 2, 'index' => 1, 'position' => 20),
		'address'   => array('type' => 'varchar(128)', 'label' => 'Address', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'showoncombobox' => 3, 'index' => 1, 'position' => 30),
		'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => ((int) !getDolGlobalBool('MAIN_LIST_HIDE_PRIVATE_NOTES')), 'visible' => 3, 'position' => 35),
		'role'      => array('type' => 'checkbox', 'label' => 'Role', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'showoncombobox' => 4, 'index' => 1, 'position' => 40),
		'birthday' 	=> array('type' => 'date', 'label' => 'Birthday', 'enabled' => 1, 'visible' => -1, 'notnull' => 0, 'position' => 45),
		'statut'    => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'default' => '0', 'index' => 1, 'position' => 50, 'arrayofkeyval' => array(0 => $contactstatic->LibStatut(0, 1), 1 => $contactstatic->LibStatut(1, 1))),
	);

	// Definition of fields for list
	$arrayfields = array(
		't.rowid' => array('label' => "TechnicalID", 'checked' => (getDolGlobalString('MAIN_SHOW_TECHNICAL_ID') ? 1 : 0), 'enabled' => (getDolGlobalString('MAIN_SHOW_TECHNICAL_ID') ? 1 : 0), 'position' => 1),
		't.name' => array('label' => "Name", 'checked' => 1, 'position' => 10),
		't.poste' => array('label' => "PostOrFunction", 'checked' => 1, 'position' => 20),
		't.address' => array('label' => (empty($conf->dol_optimize_smallscreen) ? $langs->trans("Address").' / '.$langs->trans("Phone").' / '.$langs->trans("Email") : $langs->trans("Address")), 'checked' => 1, 'position' => 30),
		't.note_private' => array('label' => 'NotePrivate', 'checked' => 0, 'position' => 35),
		'sc.role' => array('label' => "ContactByDefaultFor", 'checked' => 1, 'position' => 40),
		't.birthday' => array('label' => "Birthday", 'checked' => 0, 'position' => 45),
		't.statut' => array('label' => "Status", 'checked' => 1, 'position' => 50, 'class' => 'center'),
		'u.user' => array('label' => "DolibarrLogin", 'checked' => 1, 'position' => 50, 'class' => 'center'),
	);
	// Extra fields
	if (!empty($extrafields->attributes[$contactstatic->table_element]['label']) && is_array($extrafields->attributes[$contactstatic->table_element]['label']) && count($extrafields->attributes[$contactstatic->table_element]['label'])) {
		foreach ($extrafields->attributes[$contactstatic->table_element]['label'] as $key => $val) {
			if (!empty($extrafields->attributes[$contactstatic->table_element]['list'][$key])) {
				$arrayfields["ef.".$key] = array(
					'label' => $extrafields->attributes[$contactstatic->table_element]['label'][$key],
					'checked' => (((int) dol_eval($extrafields->attributes[$contactstatic->table_element]['list'][$key], 1, 1, '1') < 0) ? 0 : 1),
					'position' => 1000 + $extrafields->attributes[$contactstatic->table_element]['pos'][$key],
					'enabled' => (abs((int) dol_eval($extrafields->attributes[$contactstatic->table_element]['list'][$key], 1)) != 3 && (int) dol_eval($extrafields->attributes[$contactstatic->table_element]['perms'][$key], 1, 1, '1'))
				);
			}
		}
	}

	// Initialize array of search criteria
	$search = array();
	foreach ($arrayfields as $key => $val) {
		$queryName = 'search_'.substr($key, 2);
		if (GETPOST($queryName, 'alpha')) {
			$search[substr($key, 2)] = GETPOST($queryName, 'alpha');
		}
	}
	$search_array_options = $extrafields->getOptionalsFromPost($contactstatic->table_element, '', 'search_');

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_rowid = '';
		$search_status = '';
		$search_name = '';
		$search_roles = array();
		$search_address = '';
		$search_poste = '';
		$search_note_private = '';
		$search_birthday_dtstart = '';
		$search_birthday_dtend = '';
		$search = array();
		$search_array_options = array();

		foreach ($contactstatic->fields as $key => $val) {
			$search[$key] = '';
		}
	}

	$contactstatic->fields = dol_sort_array($contactstatic->fields, 'position');
	$arrayfields = dol_sort_array($arrayfields, 'position');

	$newcardbutton = '';
	if ($user->hasRight('societe', 'contact', 'creer')) {
		$addcontact = (getDolGlobalString('SOCIETE_ADDRESSES_MANAGEMENT') ? $langs->trans("AddContact") : $langs->trans("AddContactAddress"));
		$newcardbutton .= dolGetButtonTitle($addcontact, '', 'fa fa-plus-circle', DOL_URL_ROOT.'/contact/card.php?socid='.$object->id.'&amp;action=create&amp;backtopage='.urlencode($backtopage));
	}

	print "\n";

	$title = (getDolGlobalString('SOCIETE_ADDRESSES_MANAGEMENT') ? $langs->trans("ContactsForCompany") : $langs->trans("ContactsAddressesForCompany"));
	print load_fiche_titre($title, $newcardbutton, '');

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="socid" value="'.$object->id.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	$arrayofmassactions = array();
	$mode = 'view';

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));  // This also change content of $arrayfields with user setup
	$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
	$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print "\n".'<table class="tagtable liste noborder">'."\n";

	$param = "socid=".urlencode((string) ($object->id));
	if ($search_rowid != '') {
		$param .= '&search_rowid='.urlencode((string) ($search_rowid));
	}
	if ($search_status != '') {
		$param .= '&search_status='.urlencode((string) ($search_status));
	}
	if (count($search_roles) > 0) {
		$param .= implode('&search_roles[]=', $search_roles);
	}
	if ($search_name != '') {
		$param .= '&search_name='.urlencode($search_name);
	}
	if ($search_poste != '') {
		$param .= '&search_poste='.urlencode($search_poste);
	}
	if ($search_address != '') {
		$param .= '&search_address='.urlencode($search_address);
	}
	if ($search_note_private != '') {
		$param .= '&search_note_private='.urlencode($search_note_private);
	}
	if ($search_birthday_dtstart != '') {
		$param .= '&search_birthday_dtstart='.urlencode($search_birthday_dtstart);
	}
	if ($search_birthday_dtend != '') {
		$param .= '&search_birthday_dtend='.urlencode($search_birthday_dtend);
	}
	if ($optioncss != '') {
		$param .= '&optioncss='.urlencode($optioncss);
	}

	// Add $param from extra fields
	$extrafieldsobjectkey = $contactstatic->table_element;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	$sql = "SELECT t.rowid, t.entity, t.lastname, t.firstname, t.fk_pays as country_id, t.civility, t.poste,";
	$sql .= " t.phone as phone_pro, t.phone_mobile, t.phone_perso, t.fax, t.email, t.socialnetworks, t.statut, t.photo, t.fk_soc,";
	$sql .= " t.civility as civility_id, t.address, t.zip, t.town, t.birthday,";
	$sql .= " t.note_private";
	$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as t";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople_extrafields as ef on (t.rowid = ef.fk_object)";
	$sql .= " WHERE t.fk_soc = ".((int) $object->id);
	$sql .= " AND t.entity IN (" .getEntity('socpeople') .")";
	$sql .= " AND ((t.fk_user_creat = ".((int) $user->id)." AND t.priv = 1) OR t.priv = 0)";
	if ($search_rowid) {
		$sql .= natural_search('t.rowid', $search_rowid);
	}
	if ($search_status != '' && $search_status != '-1') {
		$sql .= " AND t.statut = ".((int) $search_status);
	}
	if ($search_name) {
		$sql .= natural_search(array('t.lastname', 't.firstname'), $search_name);
	}
	if ($search_poste) {
		$sql .= natural_search('t.poste', $search_poste);
	}
	if ($search_address) {
		$sql .= natural_search($searchAddressPhoneDBFields, $search_address);
	}
	if ($search_note_private) {
		$sql .= natural_search('t.note_private', $search_note_private);
	}
	if ($search_birthday_dtstart != '') {
		$sql .= " AND t.birthday >= '".$db->idate($search_birthday_dtstart)."'";
	}
	if ($search_birthday_dtend != '') {
		$sql .= " AND t.birthday <= '".$db->idate($search_birthday_dtend)."'";
	}
	if (count($search_roles) > 0) {
		$sql .= " AND t.rowid IN (SELECT sc.fk_socpeople FROM ".MAIN_DB_PREFIX."societe_contacts as sc WHERE sc.fk_c_type_contact IN (".$db->sanitize(implode(',', $search_roles))."))";
	}
	// Add where from extra fields
	$extrafieldsobjectkey = $contactstatic->table_element;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
	// Add where from hooks
	$parameters = array('socid' => $object->id);
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;
	if ($sortfield == "t.name") {
		$sql .= " ORDER BY t.lastname $sortorder, t.firstname $sortorder";
	} else {
		$sql .= " ORDER BY $sortfield $sortorder";
	}

	dol_syslog('core/lib/company.lib.php :: show_contacts', LOG_DEBUG);
	$result = $db->query($sql);
	if (!$result) {
		dol_print_error($db);
	}

	$num = $db->num_rows($result);

	// Fields title search
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre" align="right">';
		print $form->showFilterButtons();
		print '</td>';
	}
	foreach ($contactstatic->fields as $key => $val) {
		$align = '';
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
			$align .= ($align ? ' ' : '').'center';
		}
		if (in_array($val['type'], array('timestamp'))) {
			$align .= ($align ? ' ' : '').'nowrap';
		}
		if ($key == 'status' || $key == 'statut') {
			$align .= ($align ? ' ' : '').'center';
		}
		if (!empty($arrayfields['t.'.$key]['checked']) || !empty($arrayfields['sc.'.$key]['checked'])) {
			print '<td class="liste_titre'.($align ? ' '.$align : '').'">';
			if (in_array($key, array('statut'))) {
				print $form->selectarray('search_status', array('-1' => '', '0' => $contactstatic->LibStatut(0, 1), '1' => $contactstatic->LibStatut(1, 1)), $search_status, 0, 0, 0, '', 0, 0, 0, '', 'onrightofpage');
			} elseif (in_array($key, array('role'))) {
				print $formcompany->showRoles("search_roles", $contactstatic, 'edit', $search_roles, 'minwidth200 maxwidth300');
			} elseif (in_array($key, array('birthday'))) {
				print '<div class="nowrap">';
				print $form->selectDate($search_birthday_dtstart ? $search_birthday_dtstart : '', "search_birthday_dtstart", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
				print '</div>';
				print '<div class="nowrap">';
				print $form->selectDate($search_birthday_dtend ? $search_birthday_dtend : '', "search_birthday_dtend", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
				print '</div>';
			} else {
				print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.(!empty($search[$key]) ? dol_escape_htmltag($search[$key]) : '').'">';
			}
			print '</td>';
		}
	}
	if ($showuserlogin) {
		print '<td class="liste_titre"></td>';
	}
	// Extra fields
	$extrafieldsobjectkey = $contactstatic->table_element;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $contactstatic); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre" align="right">';
		print $form->showFilterButtons();
		print '</td>';
	}
	print '</tr>'."\n";


	// Fields title label
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'maxwidthsearch center ')."\n";
	}
	foreach ($contactstatic->fields as $key => $val) {
		$align = '';
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
			$align .= ($align ? ' ' : '').'center';
		}
		if (in_array($val['type'], array('timestamp'))) {
			$align .= ($align ? ' ' : '').'nowrap';
		}
		if ($key == 'status' || $key == 'statut') {
			$align .= ($align ? ' ' : '').'center';
		}
		if (!empty($arrayfields['t.'.$key]['checked'])) {
			print getTitleFieldOfList($val['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($align ? 'class="'.$align.'"' : ''), $sortfield, $sortorder, $align.' ')."\n";
		}
		if ($key == 'role') {
			$align .= ($align ? ' ' : '').'left';
		}
		if (!empty($arrayfields['sc.'.$key]['checked'])) {
			print getTitleFieldOfList($arrayfields['sc.'.$key]['label'], 0, $_SERVER['PHP_SELF'], '', '', $param, ($align ? 'class="'.$align.'"' : ''), $sortfield, $sortorder, $align.' ')."\n";
		}
	}
	if ($showuserlogin) {
		print '<th class="wrapcolumntitle liste_titre">'.$langs->trans("DolibarrLogin").'</th>';
	}
	// Extra fields
	$extrafieldsobjectkey = $contactstatic->table_element;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'maxwidthsearch center ')."\n";
	}
	print '</tr>'."\n";

	$i = -1;

	if ($num || (GETPOST('button_search') || GETPOST('button_search.x') || GETPOST('button_search_x'))) {
		$i = 0;

		while ($i < $num) {
			$obj = $db->fetch_object($result);

			$contactstatic->id = $obj->rowid;
			$contactstatic->ref = $obj->rowid;
			$contactstatic->statut = $obj->statut;
			$contactstatic->lastname = $obj->lastname;
			$contactstatic->firstname = $obj->firstname;
			$contactstatic->civility_id = $obj->civility_id;
			$contactstatic->civility_code = $obj->civility_id;
			$contactstatic->poste = $obj->poste;
			$contactstatic->address = $obj->address;
			$contactstatic->zip = $obj->zip;
			$contactstatic->town = $obj->town;
			$contactstatic->phone_pro = $obj->phone_pro;
			$contactstatic->phone_mobile = $obj->phone_mobile;
			$contactstatic->phone_perso = $obj->phone_perso;
			$contactstatic->email = $obj->email;
			$contactstatic->socialnetworks = $obj->socialnetworks;
			$contactstatic->photo = $obj->photo;
			$contactstatic->fk_soc = $obj->fk_soc;
			$contactstatic->entity = $obj->entity;

			$country_code = getCountry($obj->country_id, '2');
			$contactstatic->country_code = $country_code;

			$contactstatic->setGenderFromCivility();
			$contactstatic->fetch_optionals();

			$resultRole = $contactstatic->fetchRoles();
			if ($resultRole < 0) {
				setEventMessages(null, $contactstatic->errors, 'errors');
			}

			if (is_array($contactstatic->array_options)) {
				foreach ($contactstatic->array_options as $key => $val) {
					$obj->$key = $val;
				}
			}

			print '<tr class="oddeven">';

			// Actions
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="nowrap center">';

				// Add to agenda
				if (isModEnabled('agenda') && $user->hasRight('agenda', 'myactions', 'create')) {
					print '<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&actioncode=&contactid='.$obj->rowid.'&socid='.$object->id.'&backtopage='.urlencode($backtopage).'">';
					print img_object($langs->trans("Event"), "action");
					print '</a> &nbsp; ';
				}

				// Edit
				if ($user->hasRight('societe', 'contact', 'creer')) {
					print '<a class="editfielda paddingleft" href="'.DOL_URL_ROOT.'/contact/card.php?action=edit&token='.newToken().'&id='.$obj->rowid.'&backtopage='.urlencode($backtopage).'">';
					print img_edit();
					print '</a>';
				}

				// Delete
				if ($user->hasRight('societe', 'contact', 'delete')) {
					print '<a class="marginleftonly right" href="'.DOL_URL_ROOT.'/societe/contact.php?action=delete&token='.newToken().'&id='.$obj->rowid.'&backtopage='.urlencode($backtopage).'">';
					print img_delete();
					print '</a>';
				}

				print '</td>';
			}

			// ID
			if (!empty($arrayfields['t.rowid']['checked'])) {
				print '<td>';
				print $contactstatic->id;
				print '</td>';
			}

			// Photo - Name
			if (!empty($arrayfields['t.name']['checked'])) {
				print '<td class="tdoverflowmax150">';
				print $form->showphoto('contact', $contactstatic, 0, 0, 0, 'photorefnoborder valignmiddle marginrightonly', 'small', 1, 0, 1);
				print $contactstatic->getNomUrl(0, '', 0, '&backtopage='.urlencode($backtopage));
				print '</td>';
			}

			// Job position
			if (!empty($arrayfields['t.poste']['checked'])) {
				print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($obj->poste).'">';
				if ($obj->poste) {
					print dol_escape_htmltag($obj->poste);
				}
				print '</td>';
			}

			// Address - Phone - Email
			if (!empty($arrayfields['t.address']['checked'])) {
				$addresstoshow = $contactstatic->getBannerAddress('contact', $object);
				print '<td class="tdoverflowmax150" title="'.dolPrintHTMLForAttribute($addresstoshow).'">';
				print $addresstoshow;
				print '</td>';
			}

			// Note private
			if (!empty($arrayfields['t.note_private']['checked'])) {
				print '<td class="center">';
				print dolPrintHTML($obj->note_private);
				print '</td>';
			}

			// Role
			if (!empty($arrayfields['sc.role']['checked'])) {
				print '<td class="tdoverflowmax150">';
				print $formcompany->showRoles("roles", $contactstatic, 'view');
				print '</td>';
			}

			// Birthday
			if (!empty($arrayfields['t.birthday']['checked'])) {
				print '<td class="nowraponall">';
				print dol_print_date($db->jdate($obj->birthday));
				print '</td>';
			}

			// Status
			if (!empty($arrayfields['t.statut']['checked'])) {
				print '<td class="center">'.$contactstatic->getLibStatut(5).'</td>';
			}

			if ($showuserlogin) {
				print '<td class="tdoverflowmax125">';
				$tmpuser = new User($db);
				// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
				$resfetch = $tmpuser->fetch(0, '', '', 0, -1, '', $contactstatic->id);
				if ($resfetch > 0) {
					print $tmpuser->getNomUrl(-1, '', 0, 0, 24, 1);
				}
				print '</td>';
			}

			// Extra fields
			$extrafieldsobjectkey = $contactstatic->table_element;
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

			// Actions
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="nowrap center">';

				// Add to agenda
				if (isModEnabled('agenda') && $user->hasRight('agenda', 'myactions', 'create')) {
					print '<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&actioncode=&contactid='.$obj->rowid.'&socid='.$object->id.'&backtopage='.urlencode($backtopage).'">';
					print img_object($langs->trans("Event"), "action");
					print '</a> &nbsp; ';
				}

				// Edit
				if ($user->hasRight('societe', 'contact', 'creer')) {
					print '<a class="editfielda paddingleft" href="'.DOL_URL_ROOT.'/contact/card.php?action=edit&token='.newToken().'&id='.$obj->rowid.'&backtopage='.urlencode($backtopage).'">';
					print img_edit();
					print '</a>';
				}

				// Delete
				if ($user->hasRight('societe', 'contact', 'delete')) {
					print '<a class="marginleftonly right" href="'.DOL_URL_ROOT.'/societe/contact.php?action=delete&token='.newToken().'&id='.$obj->rowid.'&socid='.urlencode($obj->fk_soc).'">';
					print img_delete();
					print '</a>';
				}

				print '</td>';
			}

			print "</tr>\n";
			$i++;
		}

		if ($num == 0) {
			$colspan = 1 + ($showuserlogin ? 1 : 0);
			foreach ($arrayfields as $key => $val) {
				if (!empty($val['checked'])) {
					$colspan++;
				}
			}
			print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
		}
	} else {
		$colspan = 1 + ($showuserlogin ? 1 : 0);
		foreach ($arrayfields as $key => $val) {
			if (!empty($val['checked'])) {
				$colspan++;
			}
		}
		print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}
	print "\n</table>\n";
	print '</div>';

	print '</form>'."\n";

	return $i;
}


/**
 *    	Show html area with actions to do
 *
 * 		@param	Conf				$conf		    Object conf
 * 		@param	Translate			$langs		    Object langs
 * 		@param	DoliDB				$db			    Object db
 * 		@param	Adherent|Societe    $filterobj  	Object thirdparty or member
 * 		@param	Contact				$objcon	        Object contact
 *      @param  int					$noprint	    Return string but does not output it
 *      @param  string|string[]		$actioncode 	Filter on actioncode
 *      @return	?string							   	Return html part or null if noprint is 1
 */
function show_actions_todo($conf, $langs, $db, $filterobj, $objcon = null, $noprint = 0, $actioncode = '')
{
	global $user, $conf;

	$out = show_actions_done($conf, $langs, $db, $filterobj, $objcon, 1, $actioncode, 'todo');

	if ($noprint) {
		return $out;
	} else {
		print $out;
		return null;
	}
}

/**
 *    	Show html area with actions (done or not, ignore the name of function).
 *      Note: Global parameter $param must be defined.
 *
 * 		@param	Conf		       $conf		   Object conf
 * 		@param	Translate	       $langs		   Object langs
 * 		@param	DoliDB		       $db			   Object db
 * 		@param	mixed			   $filterobj	   Filter on object Adherent|Societe|Project|Product|CommandeFournisseur|Dolresource|Ticket... to list events linked to an object
 * 		@param	?Contact		       $objcon		   Filter on object contact to filter events on a contact
 *      @param  int			       $noprint        Return string but does not output it
 *      @param  string|string[]    $actioncode     Filter on actioncode
 *      @param  string             $donetodo       Filter on event 'done' or 'todo' or ''=nofilter (all).
 *      @param  array              $filters        Filter on other fields
 *      @param  string             $sortfield      Sort field
 *      @param  string             $sortorder      Sort order
 *      @param	string			   $module		   You can add module name here if elementtype in table llx_actioncomm is objectkey@module
 *      @return	?string							   Return html part or void if noprint is 1
 */
function show_actions_done($conf, $langs, $db, $filterobj, $objcon = null, $noprint = 0, $actioncode = '', $donetodo = 'done', $filters = array(), $sortfield = 'a.datep,a.id', $sortorder = 'DESC', $module = '')
{
	global $user, $conf, $hookmanager;
	global $form;
	global $param, $massactionbutton;

	$start_year = GETPOSTINT('dateevent_startyear');
	$start_month = GETPOSTINT('dateevent_startmonth');
	$start_day = GETPOSTINT('dateevent_startday');
	$end_year = GETPOSTINT('dateevent_endyear');
	$end_month = GETPOSTINT('dateevent_endmonth');
	$end_day = GETPOSTINT('dateevent_endday');
	$tms_start = '';
	$tms_end = '';

	if (!empty($start_year) && !empty($start_month) && !empty($start_day)) {
		$tms_start = dol_mktime(0, 0, 0, $start_month, $start_day, $start_year, 'tzuserrel');
	}
	if (!empty($end_year) && !empty($end_month) && !empty($end_day)) {
		$tms_end = dol_mktime(23, 59, 59, $end_month, $end_day, $end_year, 'tzuserrel');
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
		$tms_start = '';
		$tms_end = '';
	}
	require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

	// Check parameters
	if (!is_object($filterobj) && !is_object($objcon)) {
		dol_print_error(null, 'BadParameter');
	}

	$out = '';
	$histo = array();
	$numaction = 0;
	$now = dol_now('tzuser');

	// Open DSI -- Fix order by -- Begin
	$sortfield_list = explode(',', $sortfield);
	$sortfield_label_list = array('a.id' => 'id', 'a.datep' => 'dp', 'a.percent' => 'percent');
	$sortfield_new_list = array();
	foreach ($sortfield_list as $sortfield_value) {
		$sortfield_new_list[] = $sortfield_label_list[trim($sortfield_value)];
	}
	$sortfield_new = implode(',', $sortfield_new_list);

	$sql = '';

	if (isModEnabled('agenda')) {
		// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
		$hookmanager->initHooks(array('agendadao'));

		$sql = "SELECT a.id, a.label as label,";
		$sql .= " a.datep as dp,";
		$sql .= " a.datep2 as dp2,";
		$sql .= " a.percent as percent, 'action' as type,";
		$sql .= " a.fk_element, a.elementtype,";
		$sql .= " a.fk_contact,";
		$sql .= " c.code as acode, c.libelle as alabel, c.picto as apicto,";
		$sql .= " u.rowid as user_id, u.login as user_login, u.photo as user_photo, u.firstname as user_firstname, u.lastname as user_lastname";
		if (is_object($filterobj) && in_array(get_class($filterobj), array('Societe', 'Client', 'Fournisseur'))) {
			$sql .= ", sp.lastname, sp.firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Dolresource') {
			/* Nothing */
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Project') {
			/* Nothing */
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
			$sql .= ", m.lastname, m.firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'BOM') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Contrat') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && is_array($filterobj->fields) && is_array($filterobj->fields['rowid']) && $filterobj->table_element && $filterobj->element) {
			if (!empty($filterobj->fields['ref'])) {
				$sql .= ", o.ref";
			} elseif (!empty($filterobj->fields['label'])) {
				$sql .= ", o.label";
			}
		}

		// Fields from hook
		$parameters = array('sql' => &$sql, 'filterobj' => $filterobj, 'objcon' => $objcon);
		$reshook = $hookmanager->executeHooks('showActionsDoneListSelect', $parameters);    // Note that $action and $object may have been modified by hook
		if (!empty($hookmanager->resPrint)) {
			$sql .= $hookmanager->resPrint;
		}

		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
		// Link to the owner of action
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on u.rowid = a.fk_user_action";
		// Link to action types
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON a.fk_action = c.id";

		// Set $force_filter_contact:
		// - true for a filter on a user or a contact, so a link on table llx_actioncomm_resources or llx_actioncomm.fk_user_action
		// - false for a link on table llx_element_resources
		$force_filter_contact = false;
		if (is_object($filterobj) && $filterobj->id > 0 && get_class($filterobj) == 'User') {
			$force_filter_contact = true;
		}
		if (is_object($objcon) && $objcon->id > 0) {
			$force_filter_contact = true;
		}

		// Fields from hook
		$parameters = array('sql' => &$sql, 'filterobj' => $filterobj, 'objcon' => $objcon);
		$reshook = $hookmanager->executeHooks('showActionsDoneListFrom', $parameters);    // Note that $action and $object may have been modified by hook
		if (!empty($hookmanager->resPrint)) {
			$sql .= $hookmanager->resPrint;
		}
		if (is_object($filterobj) && in_array(get_class($filterobj), array('Societe', 'Client', 'Fournisseur'))) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Dolresource') {
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."element_resources as er";
			$sql .= " ON er.resource_type = 'dolresource'";
			$sql .= " AND er.element_id = a.id";
			$sql .= " AND er.resource_id = ".((int) $filterobj->id);
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Project') {
			/* Nothing */
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
			$sql .= ", ".MAIN_DB_PREFIX."adherent as m";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
			$sql .= ", ".MAIN_DB_PREFIX."commande_fournisseur as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
			$sql .= ", ".MAIN_DB_PREFIX."product as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
			$sql .= ", ".MAIN_DB_PREFIX."ticket as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'BOM') {
			$sql .= ", ".MAIN_DB_PREFIX."bom_bom as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Contrat') {
			$sql .= ", ".MAIN_DB_PREFIX."contrat as o";
		} elseif (is_object($filterobj) && is_array($filterobj->fields) && is_array($filterobj->fields['rowid'])
		&& ((!empty($filterobj->fields['ref']) && is_array($filterobj->fields['ref'])) || (!empty($filterobj->fields['label']) && is_array($filterobj->fields['label'])) || (!empty($filterobj->fields['titre']) && is_array($filterobj->fields['titre'])))
		&& $filterobj->table_element && $filterobj->element) {
			$sql .= ", ".MAIN_DB_PREFIX.$filterobj->table_element." as o";
		}

		$sql .= " WHERE a.entity IN (".getEntity('agenda').")";
		if (!$force_filter_contact) {
			if (is_object($filterobj) && in_array(get_class($filterobj), array('Societe', 'Client', 'Fournisseur')) && $filterobj->id) {
				$sql .= " AND a.fk_soc = ".((int) $filterobj->id);
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Dolresource') {
				/* Nothing */
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Project' && $filterobj->id) {
				$sql .= " AND a.fk_project = ".((int) $filterobj->id);
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
				$sql .= " AND a.fk_element = m.rowid AND a.elementtype = 'member'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Commande') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'order'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'order_supplier'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Facture') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'invoice'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'product'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'ticket'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'BOM') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'bom'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Contrat') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'contract'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && is_array($filterobj->fields) && is_array($filterobj->fields['rowid'])
				&& ((!empty($filterobj->fields['ref']) && is_array($filterobj->fields['ref'])) || (!empty($filterobj->fields['label']) && is_array($filterobj->fields['label'])) || (!empty($filterobj->fields['titre']) && is_array($filterobj->fields['titre'])))
				&& $filterobj->table_element && $filterobj->element) {
				// Generic case (if there is a $filterobj and a field rowid and (ref or label) exists.
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = '".$db->escape($filterobj->element).($module ? "@".$module : "")."'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = ".((int) $filterobj->id);
				}
			} elseif (is_object($filterobj)) {
				return 'Bad value for $filterobj';
			}
		} else {
			if (is_object($filterobj) && $filterobj->id > 0 && get_class($filterobj) == 'User') {
				$sql .= " AND (u.rowid = ". ((int) $filterobj->id).' OR ';
				$sql .= " EXISTS (SELECT r.rowid FROM ".MAIN_DB_PREFIX."actioncomm_resources as r WHERE a.id = r.fk_actioncomm";
				$sql .= " AND r.element_type = '".$db->escape($filterobj->table_element)."' AND r.fk_element = ".((int) $filterobj->id).')';
				$sql .= ")";
			}
			if (is_object($objcon) && $objcon->id > 0) {
				$sql .= " AND EXISTS (SELECT r.rowid FROM ".MAIN_DB_PREFIX."actioncomm_resources as r WHERE a.id = r.fk_actioncomm";
				$sql .= " AND r.element_type = '".$db->escape($objcon->table_element)."' AND r.fk_element = ".((int) $objcon->id).')';
			}
		}

		if (!empty($tms_start) && !empty($tms_end)) {
			$sql .= " AND ((a.datep BETWEEN '".$db->idate($tms_start)."' AND '".$db->idate($tms_end)."') OR (a.datep2 BETWEEN '".$db->idate($tms_start)."' AND '".$db->idate($tms_end)."'))";
		} elseif (empty($tms_start) && !empty($tms_end)) {
			$sql .= " AND ((a.datep <= '".$db->idate($tms_end)."') OR (a.datep2 <= '".$db->idate($tms_end)."'))";
		} elseif (!empty($tms_start) && empty($tms_end)) {
			$sql .= " AND ((a.datep >= '".$db->idate($tms_start)."') OR (a.datep2 >= '".$db->idate($tms_start)."'))";
		}

		if (is_array($actioncode) && !empty($actioncode)) {
			$sql .= ' AND (';
			foreach ($actioncode as $key => $code) {
				if ($key != 0) {
					$sql .= " OR ";
				}
				if (!empty($code)) {
					addEventTypeSQL($sql, $code, "");
				}
			}
			$sql .= ')';
		} elseif (!empty($actioncode)) {
			addEventTypeSQL($sql, $actioncode);
		}

		addOtherFilterSQL($sql, $donetodo, $now, $filters);

		// Fields from hook
		$parameters = array('sql' => &$sql, 'filterobj' => $filterobj, 'objcon' => $objcon, 'module' => $module);
		$reshook = $hookmanager->executeHooks('showActionsDoneListWhere', $parameters);    // Note that $action and $object may have been modified by hook
		if (!empty($hookmanager->resPrint)) {
			$sql .= $hookmanager->resPrint;
		}

		if (is_array($actioncode)) {
			foreach ($actioncode as $code) {
				$sql2 = addMailingEventTypeSQL($code, $objcon, $filterobj);
				if (!empty($sql2)) {
					if (!empty($sql)) {
						$sql = $sql." UNION ".$sql2;
					} elseif (empty($sql)) {
						$sql = $sql2;
					}
					break;
				}
			}
		} else {
			$sql2 = addMailingEventTypeSQL($actioncode, $objcon, $filterobj);
			if (!empty($sql) && !empty($sql2)) {
				$sql = $sql." UNION ".$sql2;
			} elseif (empty($sql) && !empty($sql2)) {
				$sql = $sql2;
			}
		}
	}

	if ($sql) {
		//TODO Add navigation with this limits...
		$offset = 0;
		$limit = 1000;

		// Complete request and execute it with limit
		$sql .= $db->order($sortfield_new, $sortorder);
		if ($limit) {
			$sql .= $db->plimit($limit + 1, $offset);
		}

		dol_syslog("company.lib::show_actions_done", LOG_DEBUG);

		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);

			$imaxinloop = ($limit ? min($num, $limit) : $num);
			while ($i < $imaxinloop) {
				$obj = $db->fetch_object($resql);

				if ($obj->type == 'action') {
					$contactaction = new ActionComm($db);
					$contactaction->id = $obj->id;
					$result = $contactaction->fetchResources();
					if ($result < 0) {
						dol_print_error($db);
						setEventMessage("company.lib::show_actions_done Error fetch resource", 'errors');
					}

					//if ($donetodo == 'todo') $sql.= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > '".$db->idate($now)."'))";
					//elseif ($donetodo == 'done') $sql.= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep <= '".$db->idate($now)."'))";
					$tododone = '';
					if (($obj->percent >= 0 and $obj->percent < 100) || ($obj->percent == -1 && (!empty($obj->datep) && $obj->datep > $now))) {
						$tododone = 'todo';
					}

					$histo[$numaction] = array(
						'type' => $obj->type,
						'tododone' => $tododone,
						'id' => $obj->id,
						'datestart' => $db->jdate($obj->dp),
						'dateend' => $db->jdate($obj->dp2),
						'note' => $obj->label,
						'percent' => $obj->percent,

						'userid' => $obj->user_id,
						'login' => $obj->user_login,
						'userfirstname' => $obj->user_firstname,
						'userlastname' => $obj->user_lastname,
						'userphoto' => $obj->user_photo,

						'contact_id' => $obj->fk_contact,
						'socpeopleassigned' => $contactaction->socpeopleassigned,
						'lastname' => empty($obj->lastname) ? '' : $obj->lastname,
						'firstname' => empty($obj->firstname) ? '' : $obj->firstname,
						'fk_element' => $obj->fk_element,
						'elementtype' => $obj->elementtype,
						// Type of event
						'acode' => $obj->acode,
						'alabel' => $obj->alabel,
						'libelle' => $obj->alabel, // deprecated
						'apicto' => $obj->apicto
					);
				} else {
					$histo[$numaction] = array(
						'type' => $obj->type,
						'tododone' => 'done',
						'id' => $obj->id,
						'datestart' => $db->jdate($obj->dp),
						'dateend' => $db->jdate($obj->dp2),
						'note' => $obj->label,
						'percent' => $obj->percent,
						'acode' => $obj->acode,

						'userid' => $obj->user_id,
						'login' => $obj->user_login,
						'userfirstname' => $obj->user_firstname,
						'userlastname' => $obj->user_lastname,
						'userphoto' => $obj->user_photo
					);
				}

				$numaction++;
				$i++;
			}
		} else {
			dol_print_error($db);
		}
	}

	'@phan-var-force array<int,array{userid:int,type:string,tododone:string,apicto:string,acode:string,alabel:string,note:string,id:int,percent:int<0,100>,datestart:int,dateend:int,fk_element:string,elementtype:string,contact_id:string,lastname:string,firstname:string,contact_photo:string,socpeaopleassigned:int[],login:string,userfirstname:string,userlastname:string,userphoto:string}> $histo';

	if (isModEnabled('agenda') || (isModEnabled('mailing') && !empty($objcon->email))) {
		$delay_warning = getDolGlobalInt('MAIN_DELAY_ACTIONS_TODO') * 24 * 60 * 60;

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

		$formactions = new FormActions($db);

		$actionstatic = new ActionComm($db);
		$userstatic = new User($db);
		$userlinkcache = array();
		$contactstatic = new Contact($db);
		$elementlinkcache = array();

		$out .= '<form name="listactionsfilter" class="listactionsfilter" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$out .= '<input type="hidden" name="token" value="'.newToken().'">';
		if ($objcon && get_class($objcon) == 'Contact' &&
			(is_null($filterobj) || get_class($filterobj) == 'Societe')) {
			$out .= '<input type="hidden" name="id" value="'.$objcon->id.'" />';
		} else {
			$out .= '<input type="hidden" name="id" value="'.$filterobj->id.'" />';
		}
		if ($filterobj && get_class($filterobj) == 'Societe') {
			$out .= '<input type="hidden" name="socid" value="'.$filterobj->id.'" />';
		} else {
			$out .= '<input type="hidden" name="userid" value="'.$filterobj->id.'" />';
		}

		$out .= "\n";

		$out .= '<div class="div-table-responsive-no-min">';
		$out .= '<table class="noborder centpercent">';

		$out .= '<tr class="liste_titre">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= '<th class="liste_titre width50 middle">';
			$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
			$out .= $searchpicto;
			$out .= '</th>';
		}

		if ($donetodo) {
			$out .= '<td class="liste_titre"></td>';
		}

		// ID
		$out .= '<td class="liste_titre"><input type="text" class="width50" name="search_rowid" value="'.(isset($filters['search_rowid']) ? $filters['search_rowid'] : '').'"></td>';
		// Date
		$out .= '<td class="liste_titre center">';
		$out .= $form->selectDateToDate($tms_start, $tms_end, 'dateevent', 1);
		$out .= '</td>';
		// Owner
		$out .= '<td class="liste_titre"></td>';
		// Type
		$out .= '<td class="liste_titre">';
		$out .= $formactions->select_type_actions($actioncode, "actioncode", '', getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? -1 : 1, 0, (getDolGlobalString('AGENDA_USE_MULTISELECT_TYPE') ? 1 : 0), 1, 'combolargeelem minwidth100 maxwidth150');
		$out .= '</td>';
		$out .= '<td class="liste_titre maxwidth100onsmartphone"><input type="text" class="maxwidth100onsmartphone" name="search_agenda_label" value="'.$filters['search_agenda_label'].'"></td>';
		$out .= '<td class="liste_titre"></td>';
		$out .= '<td class="liste_titre"></td>';
		$out .= '<td class="liste_titre"></td>';
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= '<td class="liste_titre" align="middle">';
			$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
			$out .= $searchpicto;
			$out .= '</td>';
		}
		$out .= '</tr>';

		$out .= '<tr class="liste_titre">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'maxwidthsearch ');
		}
		if ($donetodo) {
			$tmp = '';
			if (get_class($filterobj) == 'Societe') {
				$tmp .= '<a href="'.DOL_URL_ROOT.'/comm/action/list.php?mode=show_list&socid='.$filterobj->id.'&status=done">';
			}
			if (get_class($filterobj) == 'User') {
				$tmp .= '<a href="'.DOL_URL_ROOT.'/comm/action/list.php?mode=show_list&userid='.$filterobj->id.'&status=done">';
			}
			$tmp .= ($donetodo != 'done' ? $langs->trans("ActionsToDoShort") : '');
			$tmp .= ($donetodo != 'done' && $donetodo != 'todo' ? ' / ' : '');
			$tmp .= ($donetodo != 'todo' ? $langs->trans("ActionsDoneShort") : '');
			//$out.=$langs->trans("ActionsToDoShort").' / '.$langs->trans("ActionsDoneShort");
			if (get_class($filterobj) == 'Societe') {
				$tmp .= '</a>';
			}
			if (get_class($filterobj) == 'User') {
				$tmp .= '</a>';
			}
			$out .= getTitleFieldOfList($tmp);
		}
		$out .= getTitleFieldOfList("Ref", 0, $_SERVER["PHP_SELF"], 'a.id', '', $param, '', $sortfield, $sortorder);
		$out .= getTitleFieldOfList("Date", 0, $_SERVER["PHP_SELF"], 'a.datep,a.id', '', $param, '', $sortfield, $sortorder, 'center ');
		$out .= getTitleFieldOfList("Owner");
		$out .= getTitleFieldOfList("Type");
		$out .= getTitleFieldOfList("Label", 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
		$out .= getTitleFieldOfList("RelatedObjects", 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
		$out .= getTitleFieldOfList("ActionOnContact", 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'tdoverflowmax125 ', 0, '', 0);
		$out .= getTitleFieldOfList("Status", 0, $_SERVER["PHP_SELF"], 'a.percent', '', $param, '', $sortfield, $sortorder, 'center ');
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'maxwidthsearch ');
		}
		$out .= '</tr>';

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
		$caction = new CActionComm($db);
		$arraylist = $caction->liste_array(1, 'code', '', (!getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? 1 : 0), '', 1);

		foreach ($histo as $key => $value) {
			$actionstatic->fetch($histo[$key]['id']); // TODO Do we need this, we already have a lot of data of line into $histo

			$actionstatic->type_picto = $histo[$key]['apicto'];
			$actionstatic->type_code = $histo[$key]['acode'];

			$out .= '<tr class="oddeven">';

			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				$out .= '<td></td>';
			}

			// Done or todo
			if ($donetodo) {
				$out .= '<td class="nowrap">';
				$out .= '</td>';
			}

			// Ref
			$out .= '<td class="nowraponall">';
			if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'mailing') {
				$out .= '<a href="'.DOL_URL_ROOT.'/comm/mailing/card.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"), "email").' ';
				$out .= $histo[$key]['id'];
				$out .= '</a>';
			} else {
				$out .= $actionstatic->getNomUrl(1, -1);
			}
			$out .= '</td>';

			// Date
			$out .= '<td class="center nowraponall">';
			$out .= dol_print_date($histo[$key]['datestart'], 'dayhour', 'tzuserrel');
			if ($histo[$key]['dateend'] && $histo[$key]['dateend'] != $histo[$key]['datestart']) {
				$tmpa = dol_getdate($histo[$key]['datestart'], true);
				$tmpb = dol_getdate($histo[$key]['dateend'], true);
				if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
					$out .= '-'.dol_print_date($histo[$key]['dateend'], 'hour', 'tzuserrel');
				} else {
					$out .= '-'.dol_print_date($histo[$key]['dateend'], 'dayhour', 'tzuserrel');
				}
			}
			$late = 0;
			if ($histo[$key]['percent'] == 0 && $histo[$key]['datestart'] && $histo[$key]['datestart'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($histo[$key]['percent'] == 0 && !$histo[$key]['datestart'] && $histo[$key]['dateend'] && $histo[$key]['datestart'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($histo[$key]['percent'] > 0 && $histo[$key]['percent'] < 100 && $histo[$key]['dateend'] && $histo[$key]['dateend'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($histo[$key]['percent'] > 0 && $histo[$key]['percent'] < 100 && !$histo[$key]['dateend'] && $histo[$key]['datestart'] && $histo[$key]['datestart'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($late) {
				$out .= img_warning($langs->trans("Late")).' ';
			}
			$out .= "</td>\n";

			// Author of event
			$out .= '<td class="tdoverflowmax125">';
			if ($histo[$key]['userid'] > 0) {
				if (isset($userlinkcache[$histo[$key]['userid']])) {
					$link = $userlinkcache[$histo[$key]['userid']];
				} else {
					$userstatic->fetch($histo[$key]['userid']);
					$link = $userstatic->getNomUrl(-1, '', 0, 0, 16, 0, 'firstelselast', '');
					$userlinkcache[$histo[$key]['userid']] = $link;
				}
				$out .= $link;
			}
			$out .= '</td>';

			// Type
			$labeltype = $actionstatic->type_code;
			if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE') && empty($arraylist[$labeltype])) {
				$labeltype = 'AC_OTH';
			}
			if (!empty($actionstatic->code) && preg_match('/^TICKET_MSG/', $actionstatic->code)) {
				$labeltype = $langs->trans("Message");
			} else {
				if (!empty($arraylist[$labeltype])) {
					$labeltype = $arraylist[$labeltype];
				}
				if ($actionstatic->type_code == 'AC_OTH_AUTO' && ($actionstatic->type_code != $actionstatic->code) && $labeltype && !empty($arraylist[$actionstatic->code])) {
					$labeltype .= ' - '.$arraylist[$actionstatic->code]; // Use code in priority on type_code
				}
			}
			$out .= '<td class="tdoverflowmax125" title="'.$labeltype.'">';
			$out .= $actionstatic->getTypePicto();
			//if (empty($conf->dol_optimize_smallscreen)) {
			$out .= $labeltype;
			//}
			$out .= '</td>';

			// Title/Label of event
			$out .= '<td class="tdoverflowmax300"';
			if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'action') {
				$transcode = $langs->trans("Action".$histo[$key]['acode']);
				//$libelle = ($transcode != "Action".$histo[$key]['acode'] ? $transcode : $histo[$key]['alabel']);
				$libelle = $histo[$key]['note'];
				$actionstatic->id = $histo[$key]['id'];
				$out .= ' title="'.dol_escape_htmltag($libelle).'">';
				$out .= dol_trunc($libelle, 120);
			}
			if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'mailing') {
				$out .= '<a href="'.DOL_URL_ROOT.'/comm/mailing/card.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"), "email").' ';
				$transcode = $langs->trans("Action".$histo[$key]['acode']);
				$libelle = ($transcode != "Action".$histo[$key]['acode'] ? $transcode : 'Send mass mailing');
				$out .= ' title="'.dol_escape_htmltag($libelle).'">';
				$out .= dol_trunc($libelle, 120);
			}
			$out .= '</td>';

			// Linked object
			$out .= '<td class="tdoverflowmax200 nowraponall">';
			if (isset($histo[$key]['elementtype']) && !empty($histo[$key]['fk_element'])) {
				if (isset($elementlinkcache[$histo[$key]['elementtype']]) && isset($elementlinkcache[$histo[$key]['elementtype']][$histo[$key]['fk_element']])) {
					$link = $elementlinkcache[$histo[$key]['elementtype']][$histo[$key]['fk_element']];
				} else {
					if (!isset($elementlinkcache[$histo[$key]['elementtype']])) {
						$elementlinkcache[$histo[$key]['elementtype']] = array();
					}
					$link = dolGetElementUrl($histo[$key]['fk_element'], $histo[$key]['elementtype'], 1);
					$elementlinkcache[$histo[$key]['elementtype']][$histo[$key]['fk_element']] = $link;
				}
				$out .= $link;
			}
			$out .= '</td>';

			// Contact(s) for action
			if (isset($histo[$key]['socpeopleassigned']) && is_array($histo[$key]['socpeopleassigned']) && count($histo[$key]['socpeopleassigned']) > 0) {
				$out .= '<td class="valignmiddle">';
				$contact = new Contact($db);
				foreach ($histo[$key]['socpeopleassigned'] as $cid => $cvalue) {
					$result = $contact->fetch($cid);

					if ($result < 0) {
						dol_print_error($db, $contact->error);
					}

					if ($result > 0) {
						$out .= $contact->getNomUrl(-3, '', 10, '', -1, 0, 'paddingright');
						if (isset($histo[$key]['acode']) && $histo[$key]['acode'] == 'AC_TEL') {
							if (!empty($contact->phone_pro)) {
								$out .= '('.dol_print_phone($contact->phone_pro).')';
							}
						}
						$out .= '<div class="paddingright"></div>';
					}
				}
				$out .= '</td>';
			} elseif (empty($objcon->id) && isset($histo[$key]['contact_id']) && $histo[$key]['contact_id'] > 0) {
				$contactstatic->lastname = $histo[$key]['lastname'];
				$contactstatic->firstname = $histo[$key]['firstname'];
				$contactstatic->id = $histo[$key]['contact_id'];
				$contactstatic->photo = $histo[$key]['contact_photo'];
				$out .= '<td width="120">'.$contactstatic->getNomUrl(-1, '', 10).'</td>';
			} else {
				$out .= '<td>&nbsp;</td>';
			}

			// Status
			$out .= '<td class="nowrap center">'.$actionstatic->LibStatut($histo[$key]['percent'], 2, 0, $histo[$key]['datestart']).'</td>';

			// Action column
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				$out .= '<td></td>';
			}

			$out .= "</tr>\n";
		}
		if (empty($histo)) {
			$colspan = 9;
			$out .= '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
		}

		$out .= "</table>\n";
		$out .= "</div>\n";

		$out .= '</form>';
	}

	if ($noprint) {
		return $out;
	} else {
		print $out;
		return  null;
	}
}

/**
 * 		Show html area for list of subsidiaries
 *
 *		@param	Conf		$conf		Object conf
 * 		@param	Translate	$langs		Object langs
 * 		@param	DoliDB		$db			Database handler
 * 		@param	Societe		$object		Third party object
 * 		@return	int
 */
function show_subsidiaries($conf, $langs, $db, $object)
{
	global $user;

	$i = -1;

	$sql = "SELECT s.rowid, s.client, s.fournisseur, s.nom as name, s.name_alias, s.email, s.address, s.zip, s.town, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur, s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql .= " WHERE s.parent = ".((int) $object->id);
	$sql .= " AND s.entity IN (".getEntity('societe').")";
	$sql .= " ORDER BY s.nom";

	$result = $db->query($sql);
	$num = $db->num_rows($result);

	if ($num) {
		$socstatic = new Societe($db);

		print load_fiche_titre($langs->trans("Subsidiaries"), '', '');

		print "\n".'<div class="div-table-responsive-no-min">'."\n";
		print '<table class="noborder centpercent">'."\n";

		print '<tr class="liste_titre"><td>'.$langs->trans("Company").'</td>';
		print '<td>'.$langs->trans("Address").'</td><td>'.$langs->trans("Zip").'</td>';
		print '<td>'.$langs->trans("Town").'</td><td>'.$langs->trans("CustomerCode").'</td>';
		print "<td>&nbsp;</td>";
		print "</tr>";

		$i = 0;

		while ($i < $num) {
			$obj = $db->fetch_object($result);

			$socstatic->id = $obj->rowid;
			$socstatic->name = $obj->name;
			$socstatic->name_alias = $obj->name_alias;
			$socstatic->email = $obj->email;
			$socstatic->code_client = $obj->code_client;
			$socstatic->code_fournisseur = $obj->code_fournisseur;
			$socstatic->code_compta = $obj->code_compta;
			$socstatic->code_compta_client = $obj->code_compta;
			$socstatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
			$socstatic->email = $obj->email;
			$socstatic->canvas = $obj->canvas;
			$socstatic->client = $obj->client;
			$socstatic->fournisseur = $obj->fournisseur;

			print '<tr class="oddeven">';

			print '<td class="tdoverflowmax150">';
			print $socstatic->getNomUrl(1);
			print '</td>';

			print '<td class="tdoverflowmax400" title="'.dol_escape_htmltag($obj->address).'">'.dol_escape_htmltag($obj->address).'</td>';
			print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($obj->zip).'">'.$obj->zip.'</td>';
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->town).'">'.$obj->town.'</td>';
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->code_client).'">'.$obj->code_client.'</td>';

			print '<td class="center">';
			print '<a class="editfielda" href="'.DOL_URL_ROOT.'/societe/card.php?socid='.((int) $obj->rowid).'&action=edit&token='.newToken().'">';
			print img_edit();
			print '</a></td>';

			print "</tr>\n";
			$i++;
		}
		print "\n</table>\n";
		print '</div>'."\n";
	}

	print "<br>\n";

	return $i;
}
/**
 * 		Add Event Type SQL
 *
 *		@param	string		$sql		    $sql modified
 * 		@param	string	    $actioncode		Action code
 * 		@param	string		$sqlANDOR		"AND", "OR" or "" sql condition
 * 		@return	string      sql request
 */
function addEventTypeSQL(&$sql, $actioncode, $sqlANDOR = "AND")
{
	global $conf, $db;
	// Condition on actioncode

	if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE')) {
		if ($actioncode == 'AC_NON_AUTO') {
			$sql .= " $sqlANDOR c.type <> 'systemauto'";
		} elseif ($actioncode == 'AC_ALL_AUTO') {
			$sql .= " $sqlANDOR c.type = 'systemauto'";
		} else {
			if ($actioncode == 'AC_OTH') {
				$sql .= " $sqlANDOR c.type <> 'systemauto'";
			} elseif ($actioncode == 'AC_OTH_AUTO') {
				$sql .= " $sqlANDOR c.type = 'systemauto'";
			}
		}
	} else {
		if ($actioncode == 'AC_NON_AUTO') {
			$sql .= " $sqlANDOR c.type <> 'systemauto'";
		} elseif ($actioncode == 'AC_ALL_AUTO') {
			$sql .= " $sqlANDOR c.type = 'systemauto'";
		} else {
			$sql .= " $sqlANDOR c.code = '".$db->escape($actioncode)."'";
		}
	}

	return $sql;
}

/**
 * 		Add Event Type SQL
 *
 *		@param	string		$sql		    $sql modified
 * 		@param	string		$donetodo		donetodo
 * 		@param	int 		$now		    now
 * 		@param	array		$filters		array
 * 		@return	string      sql request
 */
function addOtherFilterSQL(&$sql, $donetodo, $now, $filters)
{
	global $db;

	if ($donetodo == 'todo') {
		$sql .= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > '".$db->idate($now)."'))";
	} elseif ($donetodo == 'done') {
		$sql .= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep <= '".$db->idate($now)."'))";
	}
	if (is_array($filters) && !empty($filters['search_agenda_label'])) {
		$sql .= natural_search('a.label', $filters['search_agenda_label']);
	}
	if (is_array($filters) && !empty($filters['search_rowid'])) {
		$sql .= natural_search('a.id', $filters['search_rowid'], 1);
	}

	return $sql;
}

/**
 *  Add Mailing Event Type SQL
 *
 *  @param	string	    $actioncode		Action code
 *  @param	Object		$objcon		    objcon
 *  @param	Object		$filterobj      filterobj
 *  @return	string
 */
function addMailingEventTypeSQL($actioncode, $objcon, $filterobj)
{
	global $db;

	// Add also event from emailings. TODO This should be replaced by an automatic event ? May be it's too much for very large emailing.
	if (isModEnabled('mailing') && !empty($objcon->email) && (empty($actioncode) || $actioncode == 'AC_OTH_AUTO' || $actioncode == 'AC_EMAILING')) {
		$sql2 = "SELECT m.rowid as id, m.titre as label, mc.date_envoi as dp, mc.date_envoi as dp2, '100' as percent, 'mailing' as type";
		$sql2 .= ", null as fk_element, '' as elementtype, null as contact_id";
		$sql2 .= ", 'AC_EMAILING' as acode, '' as alabel, '' as apicto";
		$sql2 .= ", u.rowid as user_id, u.login as user_login, u.photo as user_photo, u.firstname as user_firstname, u.lastname as user_lastname"; // User that valid action
		if (is_object($filterobj) && get_class($filterobj) == 'Societe') {
			$sql2 .= ", '' as lastname, '' as firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
			$sql2 .= ", '' as lastname, '' as firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
			$sql2 .= ", '' as ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
			$sql2 .= ", '' as ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
			$sql2 .= ", '' as ref";
		}
		$sql2 .= " FROM ".MAIN_DB_PREFIX."mailing as m, ".MAIN_DB_PREFIX."mailing_cibles as mc, ".MAIN_DB_PREFIX."user as u";
		$sql2 .= " WHERE mc.email = '".$db->escape($objcon->email)."'"; // Search is done on email.
		$sql2 .= " AND mc.statut = 1";
		$sql2 .= " AND u.rowid = m.fk_user_valid";
		$sql2 .= " AND mc.fk_mailing=m.rowid";

		return $sql2;
	} else {
		return '';
	}
}



/**
 * Show footer of company in HTML pages
 *
 * @param   Societe		$fromcompany	Third party
 * @param   Translate	$langs			Output language
 * @param	int			$addformmessage	Add the payment form message
 * @param	string		$suffix			Suffix to use on constants
 * @param	Object		$object			Object related to payment
 * @return	void
 */
function htmlPrintOnlineFooter($fromcompany, $langs, $addformmessage = 0, $suffix = '', $object = null)
{
	global $conf;

	$reg = array();

	// Juridical status
	$line1 = "";
	if ($fromcompany->forme_juridique_code) {
		$line1 .= ($line1 ? " - " : "").getFormeJuridiqueLabel((string) $fromcompany->forme_juridique_code);
	}
	// Capital
	if ($fromcompany->capital) {
		$line1 .= ($line1 ? " - " : "").$langs->transnoentities("CapitalOf", $fromcompany->capital)." ".$langs->transnoentities("Currency".$conf->currency);
	}
	// Prof Id 1
	if ($fromcompany->idprof1 && ($fromcompany->country_code != 'FR' || !$fromcompany->idprof2)) {
		$field = $langs->transcountrynoentities("ProfId1", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line1 .= ($line1 ? " - " : "").$field.": ".$fromcompany->idprof1;
	}
	// Prof Id 2
	if ($fromcompany->idprof2) {
		$field = $langs->transcountrynoentities("ProfId2", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line1 .= ($line1 ? " - " : "").$field.": ".$fromcompany->idprof2;
	}

	// Second line of company infos
	$line2 = "";
	// Prof Id 3
	if ($fromcompany->idprof3) {
		$field = $langs->transcountrynoentities("ProfId3", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line2 .= ($line2 ? " - " : "").$field.": ".$fromcompany->idprof3;
	}
	// Prof Id 4
	if ($fromcompany->idprof4) {
		$field = $langs->transcountrynoentities("ProfId4", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line2 .= ($line2 ? " - " : "").$field.": ".$fromcompany->idprof4;
	}
	// IntraCommunautary VAT
	if ($fromcompany->tva_intra != '') {
		$line2 .= ($line2 ? " - " : "").$langs->transnoentities("VATIntraShort").": ".$fromcompany->tva_intra;
	}

	print '<!-- htmlPrintOnlineFooter -->'."\n";

	// css centpercent has been removed from class="..." because not compatible with paddingleft/right and there is an horizontal scroll appearring on payment page for example.
	print '<footer class="center centpercent opacitymedium">'."\n";
	print '<br>';
	if ($addformmessage) {
		print '<!-- object = '.(empty($object) ? 'undefined' : $object->element).' -->';
		print '<br>';

		$parammessageform = 'ONLINE_PAYMENT_MESSAGE_FORM_'.$suffix;
		if (getDolGlobalString($parammessageform) !== '') {
			print $langs->transnoentities(getDolGlobalString($parammessageform));
		} elseif (getDolGlobalString('ONLINE_PAYMENT_MESSAGE_FORM')  !== '') {
			print $langs->transnoentities(getDolGlobalString('ONLINE_PAYMENT_MESSAGE_FORM'));
		}

		// Add other message if VAT exists
		if (!empty($object->total_vat) || !empty($object->total_tva)) {
			$parammessageform = 'ONLINE_PAYMENT_MESSAGE_FORMIFVAT_'.$suffix;
			if (getDolGlobalString($parammessageform)  !== '') {
				print $langs->transnoentities(getDolGlobalString($parammessageform));
			} elseif (getDolGlobalString('ONLINE_PAYMENT_MESSAGE_FORMIFVAT') !== '') {
				print $langs->transnoentities(getDolGlobalString('ONLINE_PAYMENT_MESSAGE_FORMIFVAT'));
			}
		}
	}

	print '<span style="font-size: 10px;"><br><hr>'."\n";
	print $fromcompany->name.'<br>';
	print $line1;
	if (strlen($line1.$line2) > 50) {
		print '<br>';
	} else {
		print ' - ';
	}
	print $line2;
	print '</span>';
	print '</footer>'."\n";
}
