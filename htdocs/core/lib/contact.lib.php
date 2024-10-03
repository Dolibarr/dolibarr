<?php
/* Copyright (C) 2006-2010  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2017  Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2021  Frederic France     <frederic.france@netlogic.fr>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2024		MDW					<mdeweerd@users.noreply.github.com>
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
 *	    \file       htdocs/core/lib/contact.lib.php
 *		\brief      Ensemble de functions de base pour les contacts
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Contact	$object		Object related to tabs
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function contact_prepare_head(Contact $object)
{
	global $db, $langs, $conf, $user;

	$tab = 0;
	$head = array();

	$head[$tab][0] = DOL_URL_ROOT.'/contact/card.php?id='.$object->id;
	$head[$tab][1] = $langs->trans("Contact");
	$head[$tab][2] = 'card';
	$tab++;

	if ((!empty($conf->ldap->enabled) && getDolGlobalString('LDAP_CONTACT_ACTIVE'))
		&& (!getDolGlobalString('MAIN_DISABLE_LDAP_TAB') || !empty($user->admin))) {
		$langs->load("ldap");

		$head[$tab][0] = DOL_URL_ROOT.'/contact/ldap.php?id='.$object->id;
		$head[$tab][1] = $langs->trans("LDAPCard");
		$head[$tab][2] = 'ldap';
		$tab++;
	}

	$head[$tab][0] = DOL_URL_ROOT.'/contact/perso.php?id='.$object->id;
	$head[$tab][1] = $langs->trans("PersonalInformations");
	$head[$tab][2] = 'perso';
	$tab++;

	if (isModEnabled('project') && $user->hasRight('project', 'lire')) {
		$nbProject = 0;
		// Enable caching of thirdrparty count projects
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_projects_contact_'.$object->id;
		$dataretrieved = dol_getcache($cachekey);

		if (!is_null($dataretrieved)) {
			$nbProject = $dataretrieved;
		} else {
			$sql = 'SELECT COUNT(n.rowid) as nb';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'projet as n';
			$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'element_contact as cc ON (n.rowid = cc.element_id)';
			$sql .= " WHERE cc.fk_socpeople = ".((int) $object->id);
			$sql .= " AND cc.fk_c_type_contact IN (SELECT rowid FROM ".MAIN_DB_PREFIX."c_type_contact WHERE element='project' AND source='external')";
			$sql .= " AND n.entity IN (".getEntity('project').")";

			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$nbProject = $obj->nb;
			} else {
				dol_print_error($db);
			}
			dol_setcache($cachekey, $nbProject, 120);	// If setting cache fails, this is not a problem, so we do not test result.
		}
		$head[$tab][0] = DOL_URL_ROOT.'/contact/project.php?id='.$object->id;
		$head[$tab][1] = $langs->trans("Projects");
		if ($nbProject > 0) {
			$head[$tab][1] .= '<span class="badge marginleftonlyshort">'.$nbProject.'</span>';
		}
		$head[$tab][2] = 'project';
		$tab++;
	}

	// Related items
	if (isModEnabled('order') || isModEnabled("propal") || isModEnabled('invoice') || isModEnabled('intervention') || isModEnabled("supplier_proposal") || isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) {
		$head[$tab][0] = DOL_URL_ROOT.'/contact/consumption.php?id='.$object->id;
		$head[$tab][1] = $langs->trans("Referers");
		$head[$tab][2] = 'consumption';
		$tab++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $tab, 'contact', 'add', 'core');

	// Notes
	if (!getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
		$nbNote = (empty($object->note_private) ? 0 : 1) + (empty($object->note_public) ? 0 : 1);
		$head[$tab][0] = DOL_URL_ROOT.'/contact/note.php?id='.$object->id;
		$head[$tab][1] = $langs->trans("Note");
		if ($nbNote > 0) {
			$head[$tab][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$tab][2] = 'note';
		$tab++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->societe->dir_output."/contact/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$tab][0] = DOL_URL_ROOT.'/contact/document.php?id='.$object->id;
	$head[$tab][1] = $langs->trans("Documents");
	if (($nbFiles + $nbLinks) > 0) {
		$head[$tab][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$tab][2] = 'documents';
	$tab++;

	// Agenda / Events
	$head[$tab][0] = DOL_URL_ROOT.'/contact/agenda.php?id='.$object->id;
	$head[$tab][1] = $langs->trans("Events");
	if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
		$head[$tab][1] .= '/';
		$head[$tab][1] .= $langs->trans("Agenda");
	}
	$head[$tab][2] = 'agenda';
	$tab++;

	// Log
	/*
	$head[$tab][0] = DOL_URL_ROOT.'/contact/info.php?id='.$object->id;
	$head[$tab][1] = $langs->trans("Info");
	$head[$tab][2] = 'info';
	$tab++;*/

	complete_head_from_modules($conf, $langs, $object, $head, $tab, 'contact', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $tab, 'contact', 'remove');

	return $head;
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
function show_contacts_projects($conf, $langs, $db, $object, $backtopage = '', $nocreatelink = 0, $morehtmlright = '')
{
	global $user;

	$i = -1;

	if (isModEnabled('project') && $user->hasRight('projet', 'lire')) {
		$langs->load("projects");

		$newcardbutton = '';
		if (isModEnabled('project') && $user->hasRight('projet', 'creer') && empty($nocreatelink)) {
			$newcardbutton .= dolGetButtonTitle($langs->trans('AddProject'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/projet/card.php?socid='.$object->id.'&amp;action=create&amp;backtopage='.urlencode($backtopage));
		}

		print "\n";
		print load_fiche_titre($langs->trans("ProjectsHavingThisContact"), $newcardbutton.$morehtmlright, '');
		print '<div class="div-table-responsive">';
		print "\n".'<table class="noborder" width=100%>';

		$sql  = 'SELECT p.rowid as id, p.entity, p.title, p.ref, p.public, p.dateo as do, p.datee as de, p.fk_statut as status, p.fk_opp_status, p.opp_amount, p.opp_percent, p.tms as date_modification, p.budget_amount';
		$sql .= ', cls.code as opp_status_code, ctc.libelle as type_label';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'projet as p';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_lead_status as cls on p.fk_opp_status = cls.rowid';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'element_contact as cc ON (p.rowid = cc.element_id)';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'c_type_contact as ctc ON (ctc.rowid = cc.fk_c_type_contact)';
		$sql .= " WHERE cc.fk_socpeople = ".((int) $object->id);
		$sql .= " AND ctc.element='project' AND ctc.source='external'";
		$sql .= " AND p.entity IN (".getEntity('project').")";
		$sql .= " ORDER BY p.dateo DESC";

		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);

			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Ref").'</td>';
			print '<td>'.$langs->trans("Name").'</td>';
			print '<td>'.$langs->trans("ContactType").'</td>';
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

					if ($user->hasRight('projet', 'lire') && $userAccess > 0) {
						print '<tr class="oddeven">';

						// Ref
						print '<td>';
						print $projecttmp->getNomUrl(1);
						print '</td>';

						// Label
						print '<td>'.dol_escape_htmltag($obj->title).'</td>';
						print '<td>'.dol_escape_htmltag($obj->type_label).'</td>';
						// Date start
						print '<td class="center">'.dol_print_date($db->jdate($obj->do), "day").'</td>';
						// Date end
						print '<td class="center">'.dol_print_date($db->jdate($obj->de), "day").'</td>';
						// Opp amount
						print '<td class="right">';
						if ($obj->opp_status_code) {
							print price($obj->opp_amount, 1, '', 1, -1, -1, '');
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
		print "</table>";
		print '</div>';

		print "<br>\n";
	}

	return $i;
}
