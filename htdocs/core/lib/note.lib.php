<?php
/* Copyright (C) 2026  Frédéric France  <frederic.france@free.fr>
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
 *  \file       htdocs/core/lib/note.lib.php
 *  \brief      Whitelist registry and per-module loader functions for
 *              htdocs/core/note.php, the single entry point for object
 *              "Notes" tabs. Each loader function fetches its object, runs
 *              its security check, and returns the $note* configuration
 *              array consumed by htdocs/core/note.inc.php.
 */

/**
 * Return the whitelist of 'element' values accepted by htdocs/core/note.php,
 * mapped to the loader function (defined further down in this same file)
 * each one calls. This is a closed whitelist: any 'element' value not
 * present as a key here must be rejected before use.
 *
 * @return array<string,string>
 */
function getNoteRegistry()
{
	return array(
		'commande' => 'noteLoadCommande',
		'resource' => 'noteLoadResource',
		'supplier_proposal' => 'noteLoadSupplierProposal',
		'fichinter' => 'noteLoadFichinter',
		'projet' => 'noteLoadProjet',
		'loan' => 'noteLoadLoan',
		'ticket' => 'noteLoadTicket',
		'workstation' => 'noteLoadWorkstation',
		'conferenceorboothattendee' => 'noteLoadConferenceorboothattendee',
		'expedition' => 'noteLoadExpedition',
		'contrat' => 'noteLoadContrat',
		'don' => 'noteLoadDon',
		'societe' => 'noteLoadSociete',
		'fourn_commande' => 'noteLoadFournCommande',
		'fourn_facture' => 'noteLoadFournFacture',
		'compta_sociales' => 'noteLoadComptaSociales',
		'compta_facture' => 'noteLoadComptaFacture',
		'comm_propal' => 'noteLoadCommPropal',
		'adherents' => 'noteLoadAdherents',
		'asset' => 'noteLoadAsset',
		'bom' => 'noteLoadBom',
		'mailing' => 'noteLoadMailing',
		'contact' => 'noteLoadContact',
		'product' => 'noteLoadProduct',
		'assetmodel' => 'noteLoadAssetModel',
		'availabilities' => 'noteLoadAvailabilities',
		'calendar' => 'noteLoadCalendar',
		'expensereport' => 'noteLoadExpenseReport',
		'evaluation' => 'noteLoadEvaluation',
		'job' => 'noteLoadJob',
		'position' => 'noteLoadPosition',
		'skill' => 'noteLoadSkill',
		'knowledgerecord' => 'noteLoadKnowledgeRecord',
		'mo' => 'noteLoadMo',
		'partnership' => 'noteLoadPartnership',
		'productlot' => 'noteLoadProductLot',
		'stocktransfer' => 'noteLoadStockTransfer',
		'recruitmentcandidature' => 'noteLoadRecruitmentCandidature',
		'recruitmentjobposition' => 'noteLoadRecruitmentJobPosition',
		'user' => 'noteLoadUser',
		'triggerhistory' => 'noteLoadTriggerHistory',
		'reception' => 'noteLoadReception',
	);
}

/**
 * Load context for the "Notes" tab of a customer order.
 *
 * @return array<string,mixed>
 */
function noteLoadCommande()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	if (isModEnabled('project')) {
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	}

	// Load translation files required by the page
	$langs->loadLangs(array('companies', 'bills', 'orders'));

	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$socid = GETPOSTINT('socid');
	$action = GETPOST('action', 'aZ09');

	// Security check
	$socid = 0;
	if ($user->socid) {
		$socid = $user->socid;
	}

	$result = restrictedArea($user, 'commande', $id, '');

	$permissionnote = $user->hasRight('commande', 'creer'); // Used by the include of actions_setnotes.inc.php

	$object = new Commande($db);
	if (!$object->fetch($id, $ref) > 0) {
		dol_print_error($db);
		exit;
	}
	$object->fetch_thirdparty();

	$notemorehtmlref = function (Commande $object, Form $form, string $action) use ($db, $langs) {
		$morehtmlref = '<div class="refidno">';
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
		$morehtmlref .= '</div>';
		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('ordernote'),
		'notepreparehead' => 'commande_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("CustomerOrder"),
		'notepicto' => $object->picto,
		'notepagetitle' => $object->ref." - ".$langs->trans('Notes'),
		'notehelpurl' => 'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes|DE:Modul_Kundenaufträge',
		'notebodyclass' => 'mod-order page-card_notes',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/commande/list.php', ['restore_lastsearch_values' => 1, 'socid' => (!empty($socid) ? $socid : '')]).'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a resource.
 *
 * @return array<string,mixed>
 */
function noteLoadResource()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/resource.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array('companies', 'interventions'));

	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');

	// Security check
	if ($user->socid) {
		$socid = $user->socid;
	}

	$object = new Dolresource($db);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

	$result = restrictedArea($user, 'resource', $object->id, 'resource');

	$permissionnote = $user->hasRight('resource', 'write'); // Used by the include of actions_setnotes.inc.php

	$noteextracontent = function (Dolresource $object, Form $form) use ($langs) {
		print '<table class="border tableforfield centpercent">';
		print '<tr>';
		print '<td class="titlefield">'.$langs->trans("ResourceType").'</td>';
		print '<td>';
		print $object->type_label;
		print '</td>';
		print '</tr>';
		print "</table>";
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('resourcenote'),
		'notepreparehead' => 'resource_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans('ResourceSingular'),
		'notepicto' => 'resource',
		'notepagetitle' => '',
		'notebodyclass' => 'mod-resource page-card_notes',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/resource/list.php'.(!empty($socid) ? '?id='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'noteextracontent' => $noteextracontent,
	);
}

/**
 * Load context for the "Notes" tab of a supplier proposal.
 *
 * @return array<string,mixed>
 */
function noteLoadSupplierProposal()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/supplier_proposal.lib.php';
	if (isModEnabled('project')) {
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	}

	// Load translation files required by the page
	$langs->loadLangs(array('supplier_proposal', 'compta', 'bills'));

	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');

	// Security check
	if ($user->socid) {
		$socid = $user->socid;
	}

	$result = restrictedArea($user, 'supplier_proposal', $id, 'supplier_proposal');

	$object = new SupplierProposal($db);
	$fetchok = ($id > 0 || !empty($ref)) && $object->fetch($id, $ref);
	if ($fetchok) {
		$object->fetch_thirdparty();
	}

	$usercancreate = $user->hasRight("supplier_propal", "write");

	$permissionnote = $user->hasRight('supplier_proposal', 'creer'); // Used by the include of actions_setnotes.inc.php

	// Preserves today's extra guard: a supplier proposal card is only shown once its linked thirdparty can be re-fetched independently.
	$noteviewguard = $fetchok && (new Societe($db))->fetch($object->socid) > 0;

	$notemorehtmlref = function (SupplierProposal $object, Form $form, string $action) use ($db, $langs, $usercancreate) {
		$morehtmlref = '<div class="refidno">';
		// Thirdparty
		$morehtmlref .= $object->thirdparty->getNomUrl(1);
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if ($usercancreate) {
				$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.dolBuildUrl($_SERVER['PHP_SELF'], ['action' => 'classify', 'id' => $object->id, 'element' => 'supplier_proposal'], true).'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
				}
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id.'&element=supplier_proposal', (getDolGlobalString('PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS') ? $object->socid : -1), $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= $proj->getNomUrl(1);
					if ($proj->title) {
						$morehtmlref .= ' - '.$proj->title;
					}
				}
			}
		}
		$morehtmlref .= '</div>';
		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('supplier_proposalnote'),
		'notepreparehead' => 'supplier_proposal_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans('CommRequest'),
		'notepicto' => 'supplier_proposal',
		'notepagetitle' => $fetchok ? $object->ref." - ".$langs->trans('Notes') : '',
		'notehelpurl' => 'EN:Ask_Price_Supplier|FR:Demande_de_prix_fournisseur',
		'notebodyclass' => 'mod-supplierproposal page-card_docuemnts',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/supplier_proposal/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'noteviewguard' => $noteviewguard,
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of an intervention.
 *
 * @return array<string,mixed>
 */
function noteLoadFichinter()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';
	if (isModEnabled('project')) {
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	}

	// Load translation files required by the page
	$langs->loadLangs(array('companies', 'interventions'));

	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');

	// Security check
	if ($user->socid) {
		$socid = $user->socid;
	}
	$result = restrictedArea($user, 'ficheinter', $id, 'fichinter');

	$object = new Fichinter($db);
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();

	$permissionnote = $user->hasRight('ficheinter', 'creer'); // Used by the include of actions_setnotes.inc.php

	$notemorehtmlref = function (Fichinter $object, Form $form, string $action) use ($db, $langs) {
		$morehtmlref = '<div class="refidno">';
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'customer');
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
		$morehtmlref .= '</div>';
		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('fichinternote'),
		'notepreparehead' => 'fichinter_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans('InterventionCard'),
		'notepicto' => $object->picto,
		'notepagetitle' => $object->ref." - ".$langs->trans('Notes'),
		'notehelpurl' => 'EN:Module_Interventions|FR:Module_Fiches_d\'interventions',
		'notebodyclass' => 'mod-fichinter page-card_note',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/fichinter/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a project.
 *
 * @return array<string,mixed>
 */
function noteLoadProjet()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';

	// Load translation files required by the page
	$langs->load('projects');

	$action = GETPOST('action', 'aZ09');
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');

	$object = new Project($db);

	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'
	if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_PROJECT') && method_exists($object, 'fetchComments') && empty($object->comments)) {
		$object->fetchComments();
	}

	// Security check
	$result = restrictedArea($user, 'projet', $object->id, 'projet&project');

	$permissionnote = $user->hasRight('project', 'creer'); // Used by the include of actions_setnotes.inc.php

	// Define a complementary filter for search of next/prev ref.
	if (!$user->hasRight('projet', 'all', 'lire')) {
		$objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
		$object->next_prev_filter = "rowid:IN:".$db->sanitize(count($objectsListId) ? implode(',', array_keys($objectsListId)) : '0');
	}

	$notepagetitle = $langs->trans("Notes").' - '.$object->ref.' '.$object->name;
	if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/projectnameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
		$notepagetitle = $object->ref.' '.$object->name.' - '.$langs->trans("Note");
	}

	if (!empty($_SESSION['pageforbacktolist']) && !empty($_SESSION['pageforbacktolist']['project'])) {
		$tmpurl = $_SESSION['pageforbacktolist']['project'];
		$tmpurl = preg_replace('/__SOCID__/', (string) $object->socid, $tmpurl);
		$notelinkback = '<a href="'.$tmpurl.(preg_match('/\?/', $tmpurl) ? '&' : '?'). 'restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	} else {
		$notelinkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	}

	$notemorehtmlref = function (Project $object, Form $form, string $action) {
		$morehtmlref = '<div class="refidno">';
		// Title
		$morehtmlref .= $object->title;
		// Thirdparty
		if (!empty($object->thirdparty->id) && $object->thirdparty->id > 0) {
			$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'project');
		}
		$morehtmlref .= '</div>';
		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('projetnote'),
		'notepreparehead' => 'project_prepare_head',
		'notetabid' => 'notes',
		'notetabtitle' => $langs->trans('Project'),
		'notepicto' => ($object->public ? 'projectpub' : 'project'),
		'notepagetitle' => $notepagetitle,
		'notehelpurl' => "EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos",
		'notebodyclass' => 'mod-project page-card_note',
		'noteparamid' => 'ref',
		'notelinkback' => $notelinkback,
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a loan.
 *
 * @return array<string,mixed>
 */
function noteLoadLoan()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

	$action = GETPOST('action', 'aZ09');
	$ref = null;

	// Load translation files required by the page
	$langs->loadLangs(array("loan"));

	// Security check
	$id = GETPOSTINT('id');

	$result = restrictedArea($user, 'loan', $id, '&loan');

	$object = new Loan($db);
	if ($id > 0) {
		$object->fetch($id);
	}

	$permissionnote = $user->hasRight('loan', 'write'); // Used by the include of actions_setnotes.inc.php

	if ($object->id > 0) {
		$object->totalpaid = $object->getSumPayment(); // To give a chance to dol_banner_tab to use already paid amount to show correct status
	}

	$notemorehtmlref = function (Loan $object, Form $form, string $action) use ($db, $langs, $user) {
		$morehtmlref = '<div class="refidno">';
		// Ref loan
		$morehtmlref .= $form->editfieldkey("Label", 'label', $object->label, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("Label", 'label', $object->label, $object, 0, 'string', '', null, null, '', 1);
		// Project
		if (isModEnabled('project')) {
			$formproject = new FormProjets($db);
			$langs->loadLangs(array("projects"));
			$morehtmlref .= '<br>'.$langs->trans('Project').' : ';
			if ($user->hasRight('loan', 'write')) {
				if ($action == 'classify') {
					$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&element=loan">';
					$morehtmlref .= '<input type="hidden" name="action" value="classin">';
					$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
					$morehtmlref .= $formproject->select_projects(-1, (string) $object->fk_project, 'projectid', 16, 0, 1, 0, 1, 0, 0, '', 1);
					$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
					$morehtmlref .= '</form>';
				} else {
					$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id.'&element=loan', -1, (string) $object->fk_project, 'none', 0, 0, 0, 1, '', 'maxwidth300');
				}
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= ' : '.$proj->getNomUrl(1);
					if ($proj->title) {
						$morehtmlref .= ' - '.$proj->title;
					}
				} else {
					$morehtmlref .= '';
				}
			}
		}
		$morehtmlref .= '</div>';
		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('loannote'),
		'notepreparehead' => 'loan_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("Loan"),
		'notepicto' => 'money-bill-alt',
		'notepagetitle' => $langs->trans("Loan").' - '.$langs->trans("Notes"),
		'notehelpurl' => 'EN:Module_Loan|FR:Module_Emprunt',
		'notebodyclass' => 'mod-loan page-card_note',
		'noteparamid' => 'id',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/loan/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'rowid',
		'notefieldref' => 'ref',
		'notemorehtmlstatus' => '',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a ticket.
 *
 * @return array<string,mixed>
 */
function noteLoadTicket()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
	if (isModEnabled('project')) {
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	}

	// Load translation files required by the page
	$langs->loadLangs(array('companies', 'ticket'));

	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');

	// Security check
	if ($user->socid) {
		$socid = $user->socid;
	}
	$result = restrictedArea($user, 'ticket', $id, 'ticket');

	$object = new Ticket($db);
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();

	$permissiontoadd = $user->hasRight('ticket', 'write');
	$permissionnote = $user->hasRight('ticket', 'write'); // Used by the include of actions_setnotes.inc.php

	// Store current page url
	$url_page_current = DOL_URL_ROOT.'/ticket/document.php';

	$notemorehtmlref = function (Ticket $object, Form $form, string $action) use ($db, $langs, $permissiontoadd, $url_page_current) {
		$morehtmlref = '<div class="refidno">';
		$morehtmlref .= $object->subject;
		// Author
		if ($object->fk_user_create > 0) {
			$morehtmlref .= '<br>';
			$fuser = new User($db);
			$fuser->fetch($object->fk_user_create);
			$morehtmlref .= $fuser->getNomUrl(-1);
		} elseif (!empty($object->email_msgid)) {
			$morehtmlref .= '<br>';
			$morehtmlref .= img_picto('', 'email', 'class="paddingrightonly"');
			$morehtmlref .= dol_escape_htmltag($object->origin_email).' <small class="hideonsmartphone opacitymedium">('.$form->textwithpicto($langs->trans("CreatedByEmailCollector"), $langs->trans("EmailMsgID").': '.$object->email_msgid).')</small>';
		} elseif (!empty($object->origin_email)) {
			$morehtmlref .= '<br>';
			$morehtmlref .= img_picto('', 'email', 'class="paddingrightonly"');
			$morehtmlref .= dol_escape_htmltag($object->origin_email).' <small class="hideonsmartphone opacitymedium">('.$langs->trans("CreatedByPublicPortal").')</small>';
		}

		// Thirdparty
		if (isModEnabled("societe")) {
			$morehtmlref .= '<br>';
			$morehtmlref .= img_picto($langs->trans("ThirdParty"), 'company', 'class="pictofixedwidth"');
			if ($action != 'editcustomer' && $permissiontoadd) {
				$morehtmlref .= '<a class="editfielda" href="'.$url_page_current.'?action=editcustomer&token='.newToken().'&track_id='.$object->track_id.'">'.img_edit($langs->transnoentitiesnoconv('SetThirdParty'), 0).'</a> ';
			}
			$morehtmlref .= $form->form_thirdparty($url_page_current.'?track_id='.$object->track_id, (string) $object->socid, $action == 'editcustomer' ? 'editcustomer' : 'none', '', 1, 0, 0, array(), 1);
		}

		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			if (!empty($object->fk_project)) {
				$morehtmlref .= '<br>';
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
		$morehtmlref .= '</div>';
		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('ticketnote'),
		'notepreparehead' => 'ticket_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans('TicketCard'),
		'notepicto' => 'ticket',
		'notepagetitle' => $langs->trans("Ticket"),
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/ticket/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a workstation.
 *
 * @return array<string,mixed>
 */
function noteLoadWorkstation()
{
	global $db, $langs, $user, $conf, $extrafields;

	require_once DOL_DOCUMENT_ROOT.'/workstation/class/workstation.class.php';
	require_once DOL_DOCUMENT_ROOT.'/workstation/lib/workstation_workstation.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array('mrp', 'companies'));

	// Get parameters
	$id         = GETPOSTINT('id');
	$ref        = GETPOST('ref', 'alpha');
	$action     = GETPOST('action', 'aZ09');
	$cancel     = GETPOST('cancel');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new Workstation($db);

	$diroutputmassaction = $conf->workstation->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = rtrim(getMultidirOutput($object, '', 1), '/');
	}

	$permissionnote = $user->hasRight('workstation', 'workstation', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiontoadd = $user->hasRight('workstation', 'workstation', 'write'); // Used by the include of actions_addupdatedelete.inc.php

	// Security check
	$isdraft = 0;
	restrictedArea($user, $object->element, $object->id, $object->table_element, 'workstation', 'fk_soc', 'rowid', $isdraft);

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('workstationnote', 'globalcard'),
		'notepreparehead' => 'workstationPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("Workstation"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('Workstation'),
		'notehelpurl' => 'EN:Module_Workstation',
		'notebodyclass' => 'mod-workstation page-card_workstation_note',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/workstation/workstation_list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
	);
}

/**
 * Load context for the "Notes" tab of an event organization conference/booth attendee.
 *
 * @return array<string,mixed>
 */
function noteLoadConferenceorboothattendee()
{
	global $db, $langs, $user, $conf;

	require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorboothattendee.class.php';
	require_once DOL_DOCUMENT_ROOT.'/eventorganization/lib/eventorganization_conferenceorbooth.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array('eventorganization', 'companies'));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref        = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel     = GETPOST('cancel');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new ConferenceOrBoothAttendee($db);
	$extrafields = new ExtraFields($db);
	$diroutputmassaction = $conf->eventorganization->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->eventorganization->multidir_output[$object->entity ?? $conf->entity]."/".$object->id;
	}

	// Permissions
	$permissionnote = $user->hasRight('project', 'conferenceorboothattendee', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiontoadd = $user->hasRight('project', 'conferenceorboothattendee', 'write'); // Used by the include of actions_addupdatedelete.inc.php

	restrictedArea($user, 'projet', $object->fk_project, 'projet&project');

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('conferenceorboothattendeenote', 'globalcard'),
		'notepreparehead' => 'conferenceorboothAttendeePrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans('ConferenceOrBoothAttendee'),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('ConferenceOrBoothAttendee'),
		'notehelpurl' => "EN:Module_Event_Organization",
		'notebodyclass' => 'mod-eventorganization page-attendee-card_note',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.dol_buildpath('/eventorganization/conferenceorboothattendee_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
	);
}

/**
 * Load context for the "Notes" tab of a shipment.
 *
 * @return array<string,mixed>
 */
function noteLoadExpedition()
{
	global $db, $langs, $user, $conf;

	require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
	if (isModEnabled('project')) {
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	}

	// Load translation files required by the page
	$langs->loadLangs(array('sendings', 'companies', 'bills', 'orders', 'stocks', 'other', 'propal'));

	$id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('facid')); // For backward compatibility
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');

	$objectsrc = null;
	$object = new Expedition($db);
	if ($id > 0 || !empty($ref)) {
		$object->fetch($id, $ref);
		$object->fetch_thirdparty();

		$typeobject = null;
		if (!empty($object->origin)) {
			$typeobject = $object->origin;
			$origin = $object->origin;
			$object->fetch_origin();
		}

		// Linked documents
		if ($typeobject == 'commande' && $object->origin_object->id && isModEnabled('order')) {
			$objectsrc = new Commande($db);
			$objectsrc->fetch($object->origin_object->id);
		}
		if ($typeobject == 'propal' && $object->origin_object->id && isModEnabled("propal")) {
			$objectsrc = new Propal($db);
			$objectsrc->fetch($object->origin_object->id);
		}

		$upload_dir = $conf->expedition->dir_output."/sending/".dol_sanitizeFileName($object->ref);
	}

	$permissionnote = $user->hasRight('expedition', 'creer'); // Used by the include of actions_setnotes.inc.php

	// Security check
	if ($user->socid) {
		$socid = $user->socid;
	}
	$result = restrictedArea($user, 'expedition', $object->id, '');

	$notemorehtmlref = function (Expedition $object, Form $form, string $action) use ($db, $langs, $user, $objectsrc) {
		$morehtmlref = '<div class="refidno">';
		// Ref customer shipment
		$morehtmlref .= $form->editfieldkey("RefCustomer", '', $object->ref_customer, $object, $user->hasRight('expedition', 'creer'), 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", '', $object->ref_customer, $object, $user->hasRight('expedition', 'creer'), 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
		// Project
		if (isModEnabled('project') && $objectsrc !== null) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if (!empty($objectsrc) && !empty($objectsrc->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($objectsrc->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
		$morehtmlref .= '</div>';
		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('expeditionnote'),
		'notepreparehead' => 'shipping_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("Shipment"),
		'notepicto' => $object->picto,
		'notepagetitle' => '',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/expedition/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a contract.
 *
 * @return array<string,mixed>
 */
function noteLoadContrat()
{
	global $db, $langs, $user, $conf;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
	if (isModEnabled('project')) {
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	}

	// Load translation files required by the page
	$langs->loadLangs(array('companies', 'contracts'));

	$action = GETPOST('action', 'aZ09');
	$confirm = GETPOST('confirm', 'alpha');
	$socid = GETPOSTINT('socid');
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');

	// Security check
	if ($user->socid) {
		$socid = $user->socid;
	}

	$object = new Contrat($db);
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();

	$permissiontoadd   = $user->hasRight('contrat', 'creer');     //  Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissionnote = $user->hasRight('contrat', 'creer'); // Used by the include of actions_setnotes.inc.php

	$result = restrictedArea($user, 'contrat', $object->id);

	$notemorehtmlref = function (Contrat $object, Form $form, string $action) use ($db, $langs) {
		$morehtmlref = '';
		$morehtmlref .= $object->ref;

		$morehtmlref .= '<div class="refidno">';
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_customer', $object->ref_customer, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_customer', $object->ref_customer, $object, 0, 'string', '', null, null, '', 1, 'getFormatedCustomerRef');
		// Ref supplier
		$morehtmlref .= '<br>';
		$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1, 'getFormatedSupplierRef');
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
		if (!getDolGlobalString('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
			$morehtmlref .= ' <span class="otherlink valignmiddle">(<a href="'.DOL_URL_ROOT.'/contrat/list.php?socid='.$object->thirdparty->id.'&search_name='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherContracts").'</a>)</span>';
		}
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
		$morehtmlref .= '</div>';
		return $morehtmlref;
	};
	$noteextracontent = function (Contrat $object, Form $form) use ($langs, $conf) {
		print '<table class="border centpercent tableforfield">';

		// Third-party discount info
		print '<tr><td class="titlefield">'.$langs->trans('Discount').'</td><td colspan="3">';
		if ($object->thirdparty->remise_percent) {
			print $langs->trans("CompanyHasRelativeDiscount", $object->thirdparty->remise_percent);
		} else {
			print '<span class="hideonsmartphone opacitymedium">'.$langs->trans("CompanyHasNoRelativeDiscount").'</span>';
		}
		$absolute_discount = $object->thirdparty->getAvailableDiscounts();
		print '. ';
		if ($absolute_discount) {
			print $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->trans("Currency".$conf->currency));
		} else {
			print '<span class="hideonsmartphone opacitymedium">'.$langs->trans("CompanyHasNoAbsoluteDiscount").'</span>';
		}
		print '.';
		print '</td></tr>';

		// Date
		print '<tr>';
		print '<td class="titlefield">';
		print $form->editfieldkey("Date", 'date_contrat', $object->date_contrat, $object, 0);
		print '</td><td>';
		print $form->editfieldval("Date", 'date_contrat', $object->date_contrat, $object, 0, 'datehourpicker');
		print '</td>';
		print '</tr>';

		print "</table>";
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('contractnote'),
		'notepreparehead' => 'contract_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("Contract"),
		'notepicto' => 'contract',
		'notepagetitle' => $langs->trans("Contract"),
		'notehelpurl' => 'EN:Module_Contracts|FR:Module_Contrat|ES:Contratos_de_servicio',
		'notebodyclass' => 'mod-contrat page-card_note',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/contrat/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'none',
		'notemorehtmlref' => $notemorehtmlref,
		'noteextracontent' => $noteextracontent,
	);
}

/**
 * Load context for the "Notes" tab of a donation.
 *
 * @return array<string,mixed>
 */
function noteLoadDon()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/donation.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	if (isModEnabled('project')) {
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	}

	// Load translation files required by the page
	$langs->loadLangs(array('companies', 'bills', 'donations'));

	$id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('facid')); // For backward compatibility
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$projectid = (GETPOST('projectid') ? GETPOSTINT('projectid') : 0);

	$object = new Don($db);
	if ($id > 0 || $ref) {
		$object->fetch($id, $ref);
	}

	// Security check
	$socid = 0;
	if ($user->socid) {
		$socid = $user->socid;
	}
	$result = restrictedArea($user, 'don', $object->id, '');

	$permissiontoadd = $user->hasRight('don', 'creer');
	$permissionnote = $user->hasRight('don', 'creer'); // Used by the include of actions_setnotes.inc.php

	$noteextraaction = function () use ($object, $permissiontoadd, $projectid) {
		global $action;
		if ($action == 'classin' && $permissiontoadd) {
			$object->fetch($object->id);
			$object->setProject($projectid);
		}
	};
	$notemorehtmlref = function (Don $object, Form $form, string $action) use ($db, $langs, $permissiontoadd) {
		$morehtmlref = '<div class="refidno">';
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			if ($permissiontoadd) {
				$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'&element=don">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
				}
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id.'&element=don', $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= $proj->getNomUrl(1);
					if ($proj->title) {
						$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
					}
				}
			}
		}
		$morehtmlref .= '</div>';
		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('donnote'),
		'notepreparehead' => 'donation_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("Donation"),
		'notepicto' => 'donation',
		'notepagetitle' => $langs->trans('Donation')." - ".$langs->trans('Notes'),
		'notehelpurl' => 'EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Donaciones|DE:Modul_Spenden',
		'notebodyclass' => 'mod-donation page-card_notes',
		'noteparamid' => 'rowid',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/don/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'rowid',
		'notefieldref' => 'ref',
		'noteextraaction' => $noteextraaction,
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a thirdparty.
 *
 * @return array<string,mixed>
 */
function noteLoadSociete()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

	// Load translation files required by the page
	$langs->load("companies");

	// Get parameters
	$id = GETPOST('id') ? GETPOSTINT('id') : GETPOSTINT('socid');
	$action = GETPOST('action', 'aZ09');

	// Initialize objects
	$object = new Societe($db);
	if ($id > 0) {
		$object->fetch($id);
	}

	// Permissions
	$permissionnote = $user->hasRight('societe', 'creer'); // Used by the include of actions_setnotes.inc.php

	// Security check
	if ($user->socid > 0) {
		unset($action);
		$socid = $user->socid;
	}

	$result = restrictedArea($user, 'societe', $object->id, '&societe');

	$notepagetitle = $langs->trans("ThirdParty").' - '.$langs->trans("Notes");
	if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
		$notepagetitle = $object->name.' - '.$langs->trans("Notes");
	}

	$noteextracontent = function (Societe $object, Form $form) {
		global $langs;
		print '<table class="border centpercent tableforfield">';

		// Type Prospect/Customer/Supplier
		print '<tr><td class="titlefield">'.$langs->trans('NatureOfThirdParty').'</td><td>';
		print $object->getTypeUrl(1);
		print '</td></tr>';

		if ($object->client) {
			print '<tr><td class="titlefield">';
			print $langs->trans('CustomerCode').'</td><td colspan="3">';
			print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_client));
			$tmpcheck = $object->check_codeclient();
			if ($tmpcheck != 0 && $tmpcheck != -5) {
				print ' <span class="error">('.$langs->trans("WrongCustomerCode").')</span>';
			}
			print '</td></tr>';
		}

		if ($object->fournisseur) {
			print '<tr><td class="titlefield">';
			print $langs->trans('SupplierCode').'</td><td colspan="3">';
			print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_fournisseur));
			$tmpcheck = $object->check_codefournisseur();
			if ($tmpcheck != 0 && $tmpcheck != -5) {
				print ' <span class="error">('.$langs->trans("WrongSupplierCode").')</span>';
			}
			print '</td></tr>';
		}

		print "</table>";
	};
	$notenotfoundcontent = function () {
		global $langs;
		$langs->load("errors");
		print $langs->trans("ErrorRecordNotFound");
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => null,
		'action' => isset($action) ? $action : null,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('thirdpartynote', 'globalcard'),
		'notepreparehead' => 'societe_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("ThirdParty"),
		'notepicto' => 'company',
		'notepagetitle' => $notepagetitle,
		'notehelpurl' => 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas',
		'noteparamid' => 'socid',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>',
		'noteshownav' => ($user->socid ? 0 : 1),
		'notefieldid' => 'rowid',
		'notefieldref' => 'nom',
		'noteextracontent' => $noteextracontent,
		'notenotfoundcontent' => $notenotfoundcontent,
	);
}

/**
 * Load context for the "Notes" tab of a supplier order.
 *
 * @return array<string,mixed>
 */
function noteLoadFournCommande()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
	if (isModEnabled('project')) {
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	}

	// Load translation files required by the page
	$langs->loadLangs(array("suppliers", "orders", "companies", "stocks"));

	// Get Parameters
	$id = GETPOSTINT('facid') ? GETPOSTINT('facid') : GETPOSTINT('id');
	$ref = GETPOST('ref');
	$action = GETPOST('action', 'aZ09');

	// Security check
	if ($user->isExternalUser()) {
		$socid = $user->isExternalUser();
	}

	// Init Objects
	$result = restrictedArea($user, 'fournisseur', $id, 'commande_fournisseur', 'commande');

	$object = new CommandeFournisseur($db);
	$object->fetch($id, $ref);
	if ($result >= 0) {
		$object->fetch_thirdparty();
	}

	// Permissions
	$permissionnote = ($user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer")); // Used by the include of actions_setnotes.inc.php

	$noteviewguard = ($result >= 0);
	$notenotfoundcontent = function () {
		recordNotFound('', 0);
	};
	$notemorehtmlref = function (CommandeFournisseur $object, Form $form, string $action) use ($db, $langs) {
		$morehtmlref = '<div class="refidno">';
		// Ref supplier
		$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
		$morehtmlref .= '</div>';
		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('ordersuppliercardnote'),
		'notepreparehead' => 'ordersupplier_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("SupplierOrder"),
		'notepicto' => 'order',
		'notepagetitle' => $object->ref." - ".$langs->trans('Notes'),
		'notehelpurl' => 'EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores',
		'notebodyclass' => 'mod-supplier-order page-notes',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'noteviewguard' => $noteviewguard,
		'notenotfoundcontent' => $notenotfoundcontent,
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a supplier invoice.
 *
 * @return array<string,mixed>
 */
function noteLoadFournFacture()
{
	global $db, $langs, $mysoc, $user;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	if (isModEnabled('project')) {
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	}

	$langs->loadLangs(array("bills", "companies"));

	$id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('facid'));
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');

	// Security check
	if ($user->socid) {
		$socid = $user->socid;
	}
	$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');

	$object = new FactureFournisseur($db);
	$object->fetch($id, $ref);

	$usercancreate = ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer"));
	$permissiontoadd = $usercancreate;
	$permissionnote = ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer")); // Used by the include of actions_setnotes.inc.php

	$noteextraaction = function () use ($object, $db) {
		global $action, $user;
		if ($action == 'setlabel' && ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer"))) {
			$object->label = GETPOST('label');
			$result = $object->update($user);
			if ($result < 0) {
				dol_print_error($db);
			}
		}
	};
	if ($object->id > 0) {
		$object->fetch_thirdparty();
		$object->totalpaid = $object->getSommePaiement(); // To give a chance to dol_banner_tab to use already paid amount to show correct status
	}
	$notemorehtmlref = function (FactureFournisseur $object, Form $form, string $action) use ($db, $langs) {
		$morehtmlref = '<div class="refidno">';
		// Ref supplier
		$morehtmlref .= $form->editfieldkey("RefSupplierBill", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefSupplierBill", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
		if (!getDolGlobalString('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
			$morehtmlref .= ' <div class="inline-block valignmiddle">(<a class="valignmiddle" href="'.DOL_URL_ROOT.'/fourn/facture/list.php?socid='.$object->thirdparty->id.'">'.$langs->trans("OtherBills").'</a>)</div>';
		}
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
		$morehtmlref .= '</div>';
		return $morehtmlref;
	};
	$noteextracontent = function (FactureFournisseur $object, Form $form) use ($db, $mysoc) {
		global $langs, $conf;
		print '<table class="border centpercent tableforfield">';

		// Type
		print '<tr><td class="titlefield">'.$langs->trans('Type').'</td><td>';
		print '<span class="badgeneutral">';
		print $object->getLibType();
		print '</span>';
		if ($object->type == FactureFournisseur::TYPE_REPLACEMENT) {
			$facreplaced = new FactureFournisseur($db);
			$facreplaced->fetch($object->fk_facture_source);
			print ' ('.$langs->transnoentities("ReplaceInvoice", $facreplaced->getNomUrl(1)).')';
		}
		if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
			$facusing = new FactureFournisseur($db);
			$facusing->fetch($object->fk_facture_source);
			print ' ('.$langs->transnoentities("CorrectInvoice", $facusing->getNomUrl(1)).')';
		}

		// Retrieve credit note ids
		$object->getListIdAvoirFromInvoice();

		if (!empty($object->creditnote_ids)) {
			$invoicecredits = array();
			foreach ($object->creditnote_ids as $invoiceid) {
				$creditnote = new FactureFournisseur($db);
				$creditnote->fetch($invoiceid);
				$invoicecredits[] = $creditnote->getNomUrl(1);
			}
			print ' ('.$langs->transnoentities("InvoiceHasAvoir") . implode(',', $invoicecredits) . ')';
		}
		print '</td></tr>';

		// Label
		print '<tr><td>'.$form->editfieldkey("Label", 'label', $object->label, $object, 0).'</td><td>';
		print $form->editfieldval("Label", 'label', $object->label, $object, 0);
		print '</td></tr>';

		// Amount
		print '<tr><td>'.$langs->trans('AmountHT').'</td><td>'.price($object->total_ht, 1, $langs, 0, -1, -1, $conf->currency).'</td></tr>';
		print '<tr><td>'.$langs->trans('AmountVAT').'</td><td>'.price($object->total_tva, 1, $langs, 0, -1, -1, $conf->currency).'</td></tr>';

		// Amount Local Taxes
		if ($mysoc->localtax1_assuj == "1") { //Localtax1
			print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td>';
			print '<td>'.price($object->total_localtax1, 1, $langs, 0, -1, -1, $conf->currency).'</td>';
			print '</tr>';
		}
		if ($mysoc->localtax2_assuj == "1") { //Localtax2
			print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td>';
			print '<td>'.price($object->total_localtax2, 1, $langs, 0, -1, -1, $conf->currency).'</td>';
			print '</tr>';
		}
		print '<tr><td>'.$langs->trans('AmountTTC').'</td><td>'.price($object->total_ttc, 1, $langs, 0, -1, -1, $conf->currency).'</td></tr>';

		print "</table>";

		print '<br>';
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('invoicesuppliernote'),
		'notepreparehead' => 'facturefourn_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans('SupplierInvoice'),
		'notepicto' => $object->picto,
		'notepagetitle' => $object->ref." - ".$langs->trans('Notes'),
		'notehelpurl' => "EN:Module_Suppliers_Invoices|FR:Module_Fournisseurs_Factures|ES:Módulo_Facturas_de_proveedores",
		'notebodyclass' => 'mod-fourn-facture page-card_note',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'noteextraaction' => $noteextraaction,
		'notemorehtmlref' => $notemorehtmlref,
		'noteextracontent' => $noteextracontent,
	);
}

/**
 * Load context for the "Notes" tab of a social contribution.
 *
 * @return array<string,mixed>
 */
function noteLoadComptaSociales()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	if (isModEnabled('project')) {
		include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	}

	// Load translation files required by the page
	$langs->loadLangs(array('compta', 'bills'));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel', 'alpha');
	$backtopage = GETPOST('backtopage', 'alpha');

	$object = new ChargeSociales($db);
	if ($id > 0) {
		$object->fetch($id);
	}
	if ($object->id > 0) {
		$object->fetch_thirdparty();
	}

	// Security check
	$socid = GETPOSTINT('socid');
	if ($user->socid) {
		$socid = $user->socid;
	}
	$result = restrictedArea($user, 'tax', $object->id, 'chargesociales', 'charges');

	$permissiontoread = $user->hasRight('tax', 'charges', 'lire');
	$permissiontoadd = $user->hasRight('tax', 'charges', 'creer');
	$permissionnote = $user->hasRight('tax', 'charges', 'creer'); // Used by the include of actions_setnotes.inc.php

	$notemorehtmlref = function (ChargeSociales $object, Form $form, string $action) use ($db, $langs, $user) {
		$morehtmlref = '<div class="refidno">';
		// Label of social contribution
		$morehtmlref .= $form->editfieldkey("Label", 'lib', $object->label, $object, $user->hasRight('tax', 'charges', 'creer'), 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("Label", 'lib', $object->label, $object, $user->hasRight('tax', 'charges', 'creer'), 'string', '', null, null, '', 1);
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			if (!empty($object->fk_project)) {
				$morehtmlref .= '<br>';
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
		$morehtmlref .= '</div>';
		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array(),
		'notepreparehead' => 'tax_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("SocialContribution"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans("SocialContribution").' - '.$langs->trans("Note"),
		'notehelpurl' => 'EN:Module_Taxes_and_social_contributions|FR:Module Taxes et dividendes|ES:M&oacute;dulo Impuestos y cargas sociales (IVA, impuestos)',
		'noteparamid' => 'id',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/compta/sociales/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'rowid',
		'notefieldref' => 'ref',
		'notemorehtmlstatus' => '',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a customer invoice.
 *
 * @return array<string,mixed>
 */
function noteLoadComptaFacture()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
	if (isModEnabled('project')) {
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	}

	// Load translation files required by the page
	$langs->loadLangs(array('companies', 'bills'));

	$id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('facid')); // For backward compatibility
	$ref = GETPOST('ref', 'alpha');
	$socid = GETPOSTINT('socid');
	$action = GETPOST('action', 'aZ09');

	$object = new Facture($db);
	// Load object
	if ($id > 0 || !empty($ref)) {
		$object->fetch($id, $ref, '', 0, getDolGlobalBool('INVOICE_USE_SITUATION'));
		$object->fetch_thirdparty();
	}

	$permissionnote = $user->hasRight('facture', 'creer'); // Used by the include of actions_setnotes.inc.php

	// Security check
	$socid = 0;
	if ($user->socid) {
		$socid = $user->socid;
	}
	$result = restrictedArea($user, 'facture', $id, '');

	$notenotfoundcontent = function () use ($langs) {
		$langs->load('errors');
		echo '<div class="error">'.$langs->trans("ErrorRecordNotFound").'</div>';
	};
	if ($object->id > 0) {
		$object->totalpaid = $object->getSommePaiement(); // To give a chance to dol_banner_tab to use already paid amount to show correct status
	}
	$notemorehtmlref = function (Facture $object, Form $form, string $action) use ($db, $langs) {
		$morehtmlref = '<div class="refidno">';
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_customer, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_customer, $object, 0, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'customer');
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
		$morehtmlref .= '</div>';
		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('invoicenote'),
		'notepreparehead' => 'facture_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("InvoiceCustomer"),
		'notepicto' => $object->picto,
		'notepagetitle' => empty($object->id) ? ($object->ref." - ".$langs->trans('Notes')) : $langs->trans('Notes'),
		'notenotfoundcontent' => $notenotfoundcontent,
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a commercial proposal.
 *
 * @return array<string,mixed>
 */
function noteLoadCommPropal()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';
	if (isModEnabled('project')) {
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	}

	// Load translation files required by the page
	$langs->loadLangs(array('propal', 'compta', 'bills', 'companies'));

	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');

	$object = new Propal($db);
	if ($id > 0 || !empty($ref)) {
		$object->fetch($id, $ref);
	}

	// Security check
	$socid = '';
	if ($user->socid > 0) {
		$socid = $user->socid;
	}

	$result = restrictedArea($user, 'propal', $object->id, 'propal');

	$permissionnote = $user->hasRight('propal', 'creer'); // Used by the include of actions_setnotes.inc.php

	// Preserves today's extra guard: a proposal card is only shown once its linked thirdparty can be re-fetched.
	$noteviewguard = ($object->id > 0) && ($object->fetch_thirdparty() > 0);
	$query = ['restore_lastsearch_values' => 1];
	if (!empty($socid) && $socid > 0) {
		$query += ['socid' => $socid];
	}

	$notemorehtmlref = function (Propal $object, Form $form, string $action) use ($db, $langs) {
		$morehtmlref = '<div class="refidno">';
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_customer, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_customer, $object, 0, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
		$morehtmlref .= '</div>';
		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('propalnote'),
		'notepreparehead' => 'propal_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans('Proposal'),
		'notepicto' => $object->picto,
		'notepagetitle' => $object->ref." - ".$langs->trans('Notes'),
		'notehelpurl' => 'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos',
		'noteviewguard' => $noteviewguard,
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/comm/propal/list.php', $query).'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a member.
 *
 * @return array<string,mixed>
 */
function noteLoadAdherents()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';

	// Load translation files required by the page
	$langs->loadLangs(array("companies", "members", "bills"));

	// Get parameters
	$action = GETPOST('action', 'aZ09');
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alphanohtml');

	// Initialize objects
	$object = new Adherent($db);

	$result = $object->fetch($id, $ref);
	if ($result > 0) {
		$adht = new AdherentType($db);
		$adht->fetch($object->typeid);
	} else {
		$adht = null;
	}

	$permissionnote = $user->hasRight('adherent', 'creer'); // Used by the include of actions_setnotes.inc.php

	$result = restrictedArea($user, 'adherent', $object->id, '', '', 'socid', 'rowid', 0);

	$notelinkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/adherents/list.php', ['restore_lastsearch_values' => 1]).'">'.$langs->trans("BackToList").'</a>';

	$notemorehtmlref = function (Adherent $object, Form $form, string $action) use ($langs) {
		$morehtmlref = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/adherents/vcard.php', ['id' => $object->id]).'" class="refid">';
		$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard', 'class="valignmiddle marginleftonly paddingrightonly"');
		$morehtmlref .= '</a>';
		return $morehtmlref;
	};

	$noteextracontent = function (Adherent $object, Form $form) use ($adht) {
		global $langs;
		print '<table class="border centpercent tableforfield">';

		// Login
		if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
			print '<tr><td class="titlefield">'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.dol_escape_htmltag($object->login).'</td></tr>';
		}

		// Type
		print '<tr><td class="titlefield">'.$langs->trans("Type").'</td>';
		print '<td class="valeur">'.$adht->getNomUrl(1)."</td></tr>\n";

		// Morphy
		print '<tr><td>'.$langs->trans("MemberNature").'</td>';
		print '<td class="valeur" >'.$object->getmorphylib('', 1).'</td>';
		print '</tr>';

		// Company
		print '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.dol_escape_htmltag($object->company).'</td></tr>';

		// Civility
		print '<tr><td>'.$langs->trans("UserTitle").'</td><td class="valeur">'.$object->getCivilityLabel().'</td>';
		print '</tr>';

		print "</table>";
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('membernote'),
		'notepreparehead' => 'member_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("Member"),
		'notepicto' => 'user',
		'notepagetitle' => $langs->trans("Member")." - ".$langs->trans("Note"),
		'notehelpurl' => "EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder",
		'notebodyclass' => 'mod-member page-card_note',
		'noteparamid' => 'id',
		'notelinkback' => $notelinkback,
		'notefieldid' => 'rowid',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
		'noteextracontent' => $noteextracontent,
	);
}

/**
 * Load context for the "Notes" tab of an asset.
 *
 * @return array<string,mixed>
 */
function noteLoadAsset()
{
	global $db, $langs, $user, $conf, $extrafields;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/asset.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/asset/class/asset.class.php';

	// Load translation files required by the page
	$langs->loadLangs(array("assets", "companies"));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel     = GETPOST('cancel', 'alpha');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new Asset($db);
	$diroutputmassaction = $conf->asset->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->asset->multidir_output[$object->entity ?? $conf->entity]."/".$object->id;
	}

	$permissionnote = $user->hasRight('asset', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiontoadd = $user->hasRight('asset', 'write'); // Used by the include of actions_addupdatedelete.inc.php

	// Security check (enable the most restrictive one)
	if ($user->socid > 0) {
		accessforbidden();
	}
	$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
	restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
	if (!isModEnabled('asset')) {
		accessforbidden();
	}

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('assetnote', 'globalcard'),
		'notepreparehead' => 'assetPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("Asset"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('Asset'),
		'notebodyclass' => 'mod-asset page-card_notes',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/asset/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
	);
}

/**
 * Load context for the "Notes" tab of a bill of materials.
 *
 * @return array<string,mixed>
 */
function noteLoadBom()
{
	global $db, $langs, $user, $conf, $extrafields;

	require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
	require_once DOL_DOCUMENT_ROOT.'/bom/lib/bom.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array("mrp", "companies"));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel', 'alpha');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new BOM($db);
	$diroutputmassaction = getMultidirOutput($object, '', 0, 'temp').'massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = (!empty($conf->bom->multidir_output[$object->entity ?? $conf->entity]) ? $conf->bom->multidir_output[$object->entity ?? $conf->entity] : $conf->bom->dir_output)."/".$object->id;
	}

	$permissionnote = $user->hasRight('bom', 'write'); // Used by the include of actions_setnotes.inc.php

	// Security check
	$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
	restrictedArea($user, 'bom', $object->id, $object->table_element, '', '', 'rowid', $isdraft);

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('bomnote', 'globalcard'),
		'notepreparehead' => 'bomPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("BillOfMaterials"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('BillOfMaterials'),
		'notehelpurl' => 'EN:Module_BOM',
		'notebodyclass' => 'mod-bom page-card_notes',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/bom/bom_list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
	);
}

/**
 * Load context for the "Notes" tab of an emailing.
 *
 * @return array<string,mixed>
 */
function noteLoadMailing()
{
	global $db, $langs, $user, $conf, $extrafields;

	require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/emailing.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array("mails", "mailing", "companies"));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel', 'alpha');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new Mailing($db);
	$diroutputmassaction = $conf->mailing->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->mailing->multidir_output[$object->entity ?? $conf->entity]."/".$object->id;
	}

	$permissionnote = $user->hasRight('mailing', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiontoadd = $user->hasRight('mailing', 'write'); // Used by the include of actions_addupdatedelete.inc.php

	// Security check
	$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
	restrictedArea($user, $object->module, $object->id, $object->table_element, '', '', 'rowid', $isdraft);

	$notemorehtmlref = function (Mailing $object, Form $form, string $action) use ($user) {
		$morehtmlref = '<div class="refidno">';
		$morehtmlref .= $form->editfieldval("", 'title', $object->title, $object, $user->hasRight('mailing', 'creer'), 'string', '', null, null, '', 1);
		$morehtmlref .= '</div>';
		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('mailingnote', 'globalcard'),
		'notepreparehead' => 'emailing_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("Mailing"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('Mailing'),
		'noteparamid' => 'id',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/comm/mailing/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'rowid',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a contact.
 *
 * @return array<string,mixed>
 */
function noteLoadContact()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

	// Load translation files required by the page
	$langs->load("companies");

	$action = GETPOST('action', 'aZ09');
	$id = GETPOSTINT('id');

	$object = new Contact($db);
	if ($id > 0) {
		$object->fetch($id);
	}

	// Security check
	if ($user->socid > 0) {
		if ($object->fk_soc > 0 && $object->fk_soc != $user->socid) {
			accessforbidden();
		}
	}

	restrictedArea($user, 'contact', $id, 'socpeople&societe');

	$permissionnote = $user->hasRight('societe', 'creer'); // Used by the include of actions_setnotes.inc.php

	if (isModEnabled('notification')) {
		$langs->load("mails");
	}

	$notemorehtmlref = function (Contact $object, Form $form, string $action) use ($db, $langs) {
		$morehtmlref = '<a href="'.DOL_URL_ROOT.'/contact/vcard.php?id='.$object->id.'" class="refid">';
		$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard', 'class="valignmiddle marginleftonly paddingrightonly"');
		$morehtmlref .= '</a>';

		$morehtmlref .= '<div class="refidno">';
		if (!getDolGlobalString('SOCIETE_DISABLE_CONTACTS')) {
			$objsoc = new Societe($db);
			$objsoc->fetch($object->socid);
			if ($objsoc->id > 0) {
				$morehtmlref .= $objsoc->getNomUrl(1);
			} else {
				$morehtmlref .= '<span class="opacitymedium">'.$langs->trans("ContactNotLinkedToCompany").'</span>';
			}
		}
		$morehtmlref .= '</div>';

		return $morehtmlref;
	};

	$noteextracontent = function (Contact $object, Form $form) use ($langs) {
		print '<table class="border centpercent tableforfield">';

		// Civility
		if (getDolGlobalString('MAIN_USE_TITLE_FOR_CONTACT')) {
			print '<tr><td class="titlefield">'.$langs->trans("UserTitle").'</td><td>';
			print $object->getCivilityLabel();
			print '</td></tr>';
		}

		print "</table>";
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => '',
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('contactnote'),
		'notepreparehead' => 'contact_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("ContactNotes"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans("ContactNotes"),
		'notehelpurl' => 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas',
		'notebodyclass' => 'mod-societe page-contact-card_note',
		'noteparamid' => 'id',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/contact/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'rowid',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
		'noteextracontent' => $noteextracontent,
	);
}

/**
 * Load context for the "Notes" tab of a product or service.
 *
 * @return array<string,mixed>
 */
function noteLoadProduct()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

	// Load translation files required by the page
	$langs->load("companies");

	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');

	// Security check
	$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
	$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
	if ($user->socid) {
		$socid = $user->socid;
	}

	$object = new Product($db);
	if ($id > 0 || !empty($ref)) {
		$object->fetch($id, $ref);
	}

	$permissionnote = ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer')); // Used by the include of actions_setnotes.inc.php

	if ($object->id > 0) {
		if ($object->type == $object::TYPE_PRODUCT) {
			restrictedArea($user, 'product', $object->id, 'product&product', '', '');
		}
		if ($object->type == $object::TYPE_SERVICE) {
			restrictedArea($user, 'service', $object->id, 'product&product', '', '');
		}
	} else {
		restrictedArea($user, 'product|service', $fieldvalue, 'product&product', '', '', $fieldtype);
	}

	if (isModEnabled('notification')) {
		$langs->load("mails");
	}

	$help_url = '';
	if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
		$help_url = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos|DE:Modul_Produkte';
	}
	if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
		$help_url = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios|DE:Modul_Leistungen';
	}

	$shortlabel = dol_trunc($object->label, 16);
	$title = $langs->trans('ProductServiceCard');
	if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
		$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('Notes');
	}
	if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
		$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('Notes');
	}

	$titre = $langs->trans("CardProduct".$object->type);
	$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

	$object->next_prev_filter = "(te.fk_product_type:=:".((int) $object->type).")";

	$noteshownav = 1;
	if ($user->socid && !in_array('product', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) {
		$noteshownav = 0;
	}

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('productnote'),
		'notepreparehead' => 'product_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $titre,
		'notepicto' => $picto,
		'notepagetitle' => $title,
		'notehelpurl' => $help_url,
		'notebodyclass' => 'mod-product page-card_note',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1&type='.$object->type.'">'.$langs->trans("BackToList").'</a>',
		'noteshownav' => $noteshownav,
		'notefieldid' => 'ref',
	);
}

/**
 * Load context for the "Notes" tab of an asset model.
 *
 * @return array<string,mixed>
 */
function noteLoadAssetModel()
{
	global $db, $langs, $user, $conf, $extrafields;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/asset.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/asset/class/assetmodel.class.php';

	// Load translation files required by the page
	$langs->loadLangs(array("assets", "companies"));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel', 'alpha');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new AssetModel($db);
	$diroutputmassaction = $conf->asset->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->asset->multidir_output[isset($object->entity) ? $object->entity : 1]."/".$object->id;
	}

	$permissiontoread = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'read')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'model_advance', 'read')));
	$permissiontoadd = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'write')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'model_advance', 'write'))); // Used by the include of actions_addupdatedelete.inc.php
	$permissionnote = $permissiontoadd; // Used by the include of actions_setnotes.inc.php

	// Security check (enable the most restrictive one)
	if ($user->socid > 0) {
		accessforbidden();
	}
	$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
	restrictedArea($user, 'asset', $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
	if (empty($conf->asset->enabled)) {
		accessforbidden();
	}
	if (!$permissiontoread) {
		accessforbidden();
	}

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('assetmodelnote', 'globalcard'),
		'notepreparehead' => 'assetModelPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("AssetModel"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('AssetModel'),
		'notebodyclass' => 'mod-asset page-model-card_notes',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/asset/model/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
	);
}

/**
 * Load context for the "Notes" tab of a bookcal availability.
 *
 * @return array<string,mixed>
 */
function noteLoadAvailabilities()
{
	global $db, $langs, $user, $conf, $extrafields;

	require_once DOL_DOCUMENT_ROOT.'/bookcal/class/availabilities.class.php';
	require_once DOL_DOCUMENT_ROOT.'/bookcal/lib/bookcal_availabilities.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array("agenda", "companies"));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel', 'alpha');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new Availabilities($db);
	$diroutputmassaction = $conf->bookcal->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->bookcal->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity]."/".$object->id;
	}

	$permissiontoread = $user->hasRight('bookcal', 'availabilities', 'read');
	$permissiontoadd = $user->hasRight('bookcal', 'availabilities', 'write');
	$permissionnote = $user->hasRight('bookcal', 'availabilities', 'write'); // Used by the include of actions_setnotes.inc.php

	// Security check (enable the most restrictive one)
	if (!isModEnabled('bookcal')) {
		accessforbidden();
	}
	if (!$permissiontoread) {
		accessforbidden();
	}

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('availabilitiesnote', 'globalcard'),
		'notepreparehead' => 'availabilitiesPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("Availabilities"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('Availabilities').' - '.$langs->trans("Notes"),
		'notebodyclass' => 'mod-bookcal page-card_availabilities_note',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.dol_buildpath('/bookcal/availabilities_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
	);
}

/**
 * Load context for the "Notes" tab of a bookcal calendar.
 *
 * @return array<string,mixed>
 */
function noteLoadCalendar()
{
	global $db, $langs, $user, $conf, $extrafields;

	require_once DOL_DOCUMENT_ROOT.'/bookcal/class/calendar.class.php';
	require_once DOL_DOCUMENT_ROOT.'/bookcal/lib/bookcal_calendar.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array("agenda", "companies"));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel', 'alpha');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new Calendar($db);
	$diroutputmassaction = $conf->bookcal->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->bookcal->multidir_output[empty($object->entity) ? $conf->entity : $object->entity]."/".$object->id;
	}

	// There is several ways to check permission.
	$permissiontoread = $user->hasRight('bookcal', 'calendar', 'read');
	$permissiontoadd = $user->hasRight('bookcal', 'calendar', 'write');
	$permissionnote = $user->hasRight('bookcal', 'calendar', 'write'); // Used by the include of actions_setnotes.inc.php

	// Security check (enable the most restrictive one)
	if (!isModEnabled("bookcal")) {
		accessforbidden();
	}
	if (!$permissiontoread) {
		accessforbidden();
	}

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('calendarnote', 'globalcard'),
		'notepreparehead' => 'calendarPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("Calendar"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('Calendar').' - '.$langs->trans("Notes"),
		'notebodyclass' => 'mod-bookcal page-card_calendar_note',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.dol_buildpath('/bookcal/calendar_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
	);
}

/**
 * Load context for the "Notes" tab of an expense report.
 *
 * @return array<string,mixed>
 */
function noteLoadExpenseReport()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/expensereport.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

	// Load translation files required by the page
	$langs->loadLangs(array('trips', 'companies', 'bills', 'orders'));

	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');

	$childids = $user->getAllChildIds(1);

	// Security check
	$socid = 0;
	if ($user->isExternalUser()) {
		$socid = $user->isExternalUser();
	}

	restrictedArea($user, 'expensereport', $id, 'expensereport');

	$object = new ExpenseReport($db);
	if (!$object->fetch($id, $ref) > 0) {
		dol_print_error($db);
	}
	$object->info($object->id);

	$permissionnote = $user->hasRight('expensereport', 'creer'); // Used by the include of actions_setnotes.inc.php

	if ($object->id > 0) {
		// Check current user can read this expense report
		$canread = 0;
		if ($user->hasRight('expensereport', 'readall')) {
			$canread = 1;
		}
		if ($user->hasRight('expensereport', 'lire') && in_array($object->fk_user_author, $childids)) {
			$canread = 1;
		}
		if (!$canread) {
			accessforbidden();
		}
	}

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('expensereportnote'),
		'notepreparehead' => 'expensereport_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("ExpenseReport"),
		'notepicto' => 'trip',
		'notepagetitle' => $langs->trans("ExpenseReport")." - ".$langs->trans("Note"),
		'notehelpurl' => 'EN:Module_Expense_Reports',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/expensereport/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
	);
}

/**
 * Load context for the "Notes" tab of an HRM evaluation.
 *
 * @return array<string,mixed>
 */
function noteLoadEvaluation()
{
	global $db, $langs, $user, $conf;

	require_once DOL_DOCUMENT_ROOT.'/hrm/class/evaluation.class.php';
	require_once DOL_DOCUMENT_ROOT.'/hrm/class/job.class.php';
	require_once DOL_DOCUMENT_ROOT.'/hrm/lib/hrm_evaluation.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array('hrm', 'companies'));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new Evaluation($db);
	$extrafields = new ExtraFields($db);
	$diroutputmassaction = $conf->hrm->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->hrm->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity]."/".$object->id;
	}

	// Permissions
	$permissionnote   = $user->hasRight('hrm', 'evaluation', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiontoread = $user->hasRight('hrm', 'evaluation', 'read');  // Used by the include of actions_addupdatedelete.inc.php

	// Security check (enable the most restrictive one)
	$isdraft = (($object->status == Evaluation::STATUS_DRAFT) ? 1 : 0);
	restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
	if (empty($conf->hrm->enabled)) {
		accessforbidden();
	}
	if (!$permissiontoread) {
		accessforbidden();
	}

	$notemorehtmlref = function (Evaluation $object, Form $form, string $action) use ($db, $langs) {
		$morehtmlref = '<div class="refidno">';
		$morehtmlref .= $langs->trans('Label').' : '.$object->label;
		$u_position = new User($db);
		$u_position->fetch($object->fk_user);
		$morehtmlref .= '<br>'.$u_position->getNomUrl(1);
		$job = new Job($db);
		$job->fetch($object->fk_job);
		$morehtmlref .= '<br>'.$langs->trans('JobProfile').' : '.$job->getNomUrl(1);
		$morehtmlref .= '</div>';

		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('evaluationnote', 'globalcard'),
		'notepreparehead' => 'evaluationPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans('Notes'),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('Evaluation'),
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.dol_buildpath('/hrm/evaluation_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of an HRM job.
 *
 * @return array<string,mixed>
 */
function noteLoadJob()
{
	global $db, $langs, $user, $conf;

	require_once DOL_DOCUMENT_ROOT.'/hrm/class/job.class.php';
	require_once DOL_DOCUMENT_ROOT.'/hrm/lib/hrm_job.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array('hrm', 'companies'));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new Job($db);
	$extrafields = new ExtraFields($db);
	$diroutputmassaction = $conf->hrm->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->hrm->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity]."/".$object->id;
	}

	// Permissions
	$permissiontoread = $user->hasRight('hrm', 'all', 'read');
	$permissionnote = $user->hasRight('hrm', 'all', 'write'); // Used by the include of actions_addupdatedelete.inc.php

	// Security check (enable the most restrictive one)
	if (empty($conf->hrm->enabled)) {
		accessforbidden();
	}
	if (!$permissiontoread) {
		accessforbidden();
	}

	$notemorehtmlref = function (Job $object) {
		$morehtmlref = '<div class="refid">';
		$morehtmlref .= $object->label;
		$morehtmlref .= '</div>';

		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('jobnote', 'globalcard'),
		'notepreparehead' => 'jobPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("Notes"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('Job'),
		'noteparamid' => 'id',
		'notelinkback' => '<a href="'.dol_buildpath('/hrm/job_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'rowid',
		'notefieldref' => 'rowid',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of an HRM position.
 *
 * @return array<string,mixed>
 */
function noteLoadPosition()
{
	global $db, $langs, $user, $conf;

	require_once DOL_DOCUMENT_ROOT.'/hrm/class/job.class.php';
	require_once DOL_DOCUMENT_ROOT.'/hrm/class/position.class.php';
	require_once DOL_DOCUMENT_ROOT.'/hrm/lib/hrm_position.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array('hrm', 'companies'));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new Position($db);
	$extrafields = new ExtraFields($db);
	$diroutputmassaction = $conf->hrm->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->hrm->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity]."/".$object->id;
	}

	// Permissions
	$permissionnote   = $user->hasRight('hrm', 'all', 'write');
	$permissiontoread = $user->hasRight('hrm', 'all', 'read'); // Used by the include of actions_addupdatedelete.inc.php

	// Security check (enable the most restrictive one)
	if (empty($conf->hrm->enabled)) {
		accessforbidden();
	}
	if (!$permissiontoread) {
		accessforbidden();
	}

	$notemorehtmlref = function (Position $object) use ($db, $langs) {
		$morehtmlref = '<div class="refidno">';
		$u_position = new User($db);
		$u_position->fetch($object->fk_user);
		$morehtmlref .= ($u_position->id > 0 ? $u_position->getNomUrl(1) : $langs->trans('Employee').' : ');
		$job = new Job($db);
		$job->fetch($object->fk_job);
		$morehtmlref .= '<br>'.$langs->trans('JobProfile').' : '.$job->getNomUrl(1);
		$morehtmlref .= '</div>';

		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('positionnote', 'globalcard'),
		'notepreparehead' => 'positionCardPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("Notes"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('Position'),
		'noteparamid' => 'id',
		'notelinkback' => '<a href="'.dol_buildpath('/hrm/position_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'rowid',
		'notefieldref' => 'rowid',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of an HRM skill.
 *
 * @return array<string,mixed>
 */
function noteLoadSkill()
{
	global $db, $langs, $user, $conf;

	require_once DOL_DOCUMENT_ROOT.'/hrm/class/skill.class.php';
	require_once DOL_DOCUMENT_ROOT.'/hrm/lib/hrm_skill.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array('hrm', 'companies'));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new Skill($db);
	$extrafields = new ExtraFields($db);
	$diroutputmassaction = $conf->hrm->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->hrm->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity]."/".$object->id;
	}

	// Permissions
	$permissionnote   = $user->hasRight('hrm', 'all', 'write');
	$permissiontoread = $user->hasRight('hrm', 'all', 'read'); // Used by the include of actions_addupdatedelete.inc.php

	// Security check (enable the most restrictive one)
	if (empty($conf->hrm->enabled)) {
		accessforbidden();
	}
	if (!$permissiontoread) {
		accessforbidden();
	}

	$notemorehtmlref = function (Skill $object) {
		$morehtmlref = '<div class="refid">';
		$morehtmlref .= $object->label;
		$morehtmlref .= '</div>';

		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('skillnote', 'globalcard'),
		'notepreparehead' => 'skillPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("Notes"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('Skill'),
		'noteparamid' => 'id',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/hrm/skill_list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'rowid',
		'notefieldref' => 'rowid',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a knowledge record.
 *
 * @return array<string,mixed>
 */
function noteLoadKnowledgeRecord()
{
	global $db, $langs, $user, $conf;

	require_once DOL_DOCUMENT_ROOT.'/knowledgemanagement/class/knowledgerecord.class.php';
	require_once DOL_DOCUMENT_ROOT.'/knowledgemanagement/lib/knowledgemanagement_knowledgerecord.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array("knowledgemanagement", "companies"));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new KnowledgeRecord($db);
	$extrafields = new ExtraFields($db);
	$diroutputmassaction = $conf->knowledgemanagement->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->knowledgemanagement->multidir_output[$object->entity ?? $conf->entity]."/".$object->id;
	}

	$permissionnote = $user->hasRight('knowledgemanagement', 'knowledgerecord', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiontoadd = $user->hasRight('knowledgemanagement', 'knowledgerecord', 'write'); // Used by the include of actions_addupdatedelete.inc.php

	// Security check - Protection if external user
	$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
	restrictedArea($user, $object->module, $object->id, $object->table_element.'&'.$object->element, $object->element, '', 'rowid', $isdraft);

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('knowledgerecordnote', 'globalcard'),
		'notepreparehead' => 'knowledgerecordPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("KnowledgeRecord"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('KnowledgeRecord'),
		'notebodyclass' => 'mod-knowledgemanagement page-card_notes',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.dol_buildpath('/knowledgemanagement/knowledgerecord_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
	);
}

/**
 * Load context for the "Notes" tab of a manufacturing order.
 *
 * @return array<string,mixed>
 */
function noteLoadMo()
{
	global $db, $langs, $user, $conf, $extrafields;

	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
	require_once DOL_DOCUMENT_ROOT.'/mrp/lib/mrp_mo.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array("mrp", "companies"));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new Mo($db);
	$diroutputmassaction = $conf->mrp->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->mrp->multidir_output[empty($object->entity) ? $conf->entity : $object->entity]."/".$object->id;
	}

	// Security check - Protection if external user
	$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
	restrictedArea($user, 'mrp', $object->id, 'mrp_mo', '', 'fk_soc', 'rowid', $isdraft);

	$permissionnote = $user->hasRight('mrp', 'write'); // Used by the include of actions_setnotes.inc.php

	$notemorehtmlref = function (Mo $object, Form $form) use ($db, $langs) {
		$morehtmlref = '<div class="refidno">';
		// Thirdparty
		if (is_object($object->thirdparty)) {
			$morehtmlref .= $object->thirdparty->getNomUrl(1, 'customer');
			if (!getDolGlobalString('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
				$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->thirdparty->id.'">'.$langs->trans("OtherOrders").'</a>)';
			}
		}
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			if (is_object($object->thirdparty)) {
				$morehtmlref .= '<br>';
			}
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
		$morehtmlref .= '</div>';

		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('monote', 'globalcard'),
		'notepreparehead' => 'moPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("ManufacturingOrder"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('Mo'),
		'notehelpurl' => 'EN:Module_Manufacturing_Orders|FR:Module_Ordres_de_Fabrication|DE:Modul_Fertigungsauftrag',
		'notebodyclass' => 'mod-mrp page-card_note',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.dol_buildpath('/mrp/mo_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a partnership.
 *
 * @return array<string,mixed>
 */
function noteLoadPartnership()
{
	global $db, $langs, $user, $conf;

	require_once DOL_DOCUMENT_ROOT.'/partnership/class/partnership.class.php';
	require_once DOL_DOCUMENT_ROOT.'/partnership/lib/partnership.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array("partnership", "companies"));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new Partnership($db);
	$extrafields = new ExtraFields($db);
	$diroutputmassaction = $conf->partnership->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->partnership->multidir_output[$object->entity ?? $conf->entity]."/".$object->id;
	}

	$permissiontoread = $user->hasRight('partnership', 'read');
	$permissionnote = $user->hasRight('partnership', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiontoadd = $user->hasRight('partnership', 'write'); // Used by the include of actions_addupdatedelete.inc.php
	$managedfor = getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR', 'thirdparty');

	// Security check - Protection if external user
	if (empty($conf->partnership->enabled)) {
		accessforbidden();
	}
	if (empty($permissiontoread)) {
		accessforbidden();
	}
	if ($object->id > 0 && !($object->fk_member > 0) && $managedfor == 'member') {
		accessforbidden();
	}
	if ($object->id > 0 && !($object->fk_soc > 0) && $managedfor == 'thirdparty') {
		accessforbidden();
	}

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('partnershipnote', 'globalcard'),
		'notepreparehead' => 'partnershipPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("Partnership"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('Partnership'),
		'notebodyclass' => 'mod-partnership page-card_notes',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.dol_buildpath('/partnership/partnership_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
	);
}

/**
 * Load context for the "Notes" tab of a product lot / batch.
 *
 * @return array<string,mixed>
 */
function noteLoadProductLot()
{
	global $db, $langs, $user, $conf;

	require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array('other', 'products', 'productbatch'));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');

	// Initialize a technical objects
	$object = new Productlot($db);
	$extrafields = new ExtraFields($db);
	$diroutputmassaction = $conf->productbatch->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->productbatch->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity]."/".$object->id;
	}

	$permissiontoread = $user->hasRight('product', 'read');
	$permissionnote = $user->hasRight('product', 'write'); // Used by the include of actions_setnotes.inc.php

	// Security check (enable the most restrictive one)
	if (!$permissiontoread) {
		accessforbidden();
	}

	$shortlabel = dol_trunc($object->batch, 16);

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('productlotnote'),
		'notepreparehead' => 'productlot_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => '',
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('Batch')." ".$shortlabel." - ".$langs->trans('Notes'),
		'notehelpurl' => 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos',
		'notebodyclass' => 'mod-product page-stock_productlot_note',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/product/stock/productlot_list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'batch',
	);
}

/**
 * Load context for the "Notes" tab of a stock transfer.
 *
 * @return array<string,mixed>
 */
function noteLoadStockTransfer()
{
	global $db, $langs, $user, $conf;

	require_once DOL_DOCUMENT_ROOT.'/product/stock/stocktransfer/class/stocktransfer.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/stock/stocktransfer/lib/stocktransfer_stocktransfer.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array("stocks", "companies"));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'alpha');
	$cancel = GETPOST('cancel');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new StockTransfer($db);
	$extrafields = new ExtraFields($db);
	$diroutputmassaction = $conf->stocktransfer->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->stocktransfer->multidir_output[$object->entity ?? $conf->entity]."/".$object->id;
	}

	$permissionnote = $user->hasRight('stocktransfer', 'stocktransfer', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiontoadd = $user->hasRight('stocktransfer', 'stocktransfer', 'write'); // Used by the include of actions_addupdatedelete.inc.php

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('stocktransfernote', 'globalcard'),
		'notepreparehead' => 'stocktransferPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("StockTransfer"),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('ModuleStockTransferName'),
		'notebodyclass' => 'mod-product page-stock-stocktransfer_stocktransfer_note',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/product/stock/stocktransfer/stocktransfer_list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
	);
}

/**
 * Load context for the "Notes" tab of a recruitment candidature.
 *
 * @return array<string,mixed>
 */
function noteLoadRecruitmentCandidature()
{
	global $db, $langs, $user, $conf;

	require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentcandidature.class.php';
	require_once DOL_DOCUMENT_ROOT.'/recruitment/lib/recruitment_recruitmentcandidature.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array("recruitment", "companies"));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new RecruitmentCandidature($db);
	$extrafields = new ExtraFields($db);
	$diroutputmassaction = $conf->recruitment->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->recruitment->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity]."/".$object->id;
	}

	$permissionnote = $user->hasRight('recruitment', 'recruitmentjobposition', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiontoadd = $user->hasRight('recruitment', 'recruitmentjobposition', 'write'); // Used by the include of actions_addupdatedelete.inc.php

	// Security check - Protection if external user
	$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
	restrictedArea($user, 'recruitment', $object->id, 'recruitment_recruitmentcandidature', 'recruitmentjobposition', '', 'rowid', $isdraft);

	$notemorehtmlref = function (RecruitmentCandidature $object) {
		$morehtmlref = '<div class="refidno">';
		$morehtmlref .= $object->getFullName(null, 1);
		$morehtmlref .= '</div>';

		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('recruitmentjobpositionnote', 'globalcard'),
		'notepreparehead' => 'recruitmentCandidaturePrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("RecruitmentCandidature"),
		'notepicto' => $object->picto,
		'notepagetitle' => $object->ref." - ".$langs->trans('Notes'),
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.dol_buildpath('/recruitment/recruitmentcandidature_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a recruitment job position.
 *
 * @return array<string,mixed>
 */
function noteLoadRecruitmentJobPosition()
{
	global $db, $langs, $user, $conf;

	require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentjobposition.class.php';
	require_once DOL_DOCUMENT_ROOT.'/recruitment/lib/recruitment_recruitmentjobposition.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array("recruitment", "companies"));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new RecruitmentJobPosition($db);
	$extrafields = new ExtraFields($db);
	$diroutputmassaction = $conf->recruitment->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->recruitment->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity]."/".$object->id;
	}

	$permissionnote = $user->hasRight('recruitment', 'recruitmentjobposition', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiontoadd = $user->hasRight('recruitment', 'recruitmentjobposition', 'write'); // Used by the include of actions_addupdatedelete.inc.php

	// Security check - Protection if external user
	$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
	restrictedArea($user, 'recruitment', $object->id, 'recruitment_recruitmentjobposition', 'recruitmentjobposition', '', 'rowid', $isdraft);

	$notemorehtmlref = function (RecruitmentJobPosition $object, Form $form, string $action) use ($db, $langs, $permissiontoadd) {
		$morehtmlref = '<div class="refidno">';
		// Project
		if (isModEnabled('project')) {
			require_once DOL_DOCUMENT_ROOT."/core/class/html.formprojet.class.php";
			$formproject = new FormProjets($db);
			$langs->load("projects");
			$morehtmlref .= $langs->trans('Project').' ';
			if ($permissiontoadd) {
				if ($action != 'classify') {
					$morehtmlref .= ' : ';
				}
				if ($action == 'classify') {
					$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&element=recruitmentjobposition">';
					$morehtmlref .= '<input type="hidden" name="action" value="classin">';
					$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
					$morehtmlref .= $formproject->select_projects($object->fk_soc, (string) $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1);
					$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
					$morehtmlref .= '</form>';
				} else {
					$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id.'&element=recruitmentjobposition', !empty($object->fk_soc) ? $object->fk_soc : 0, (string) $object->fk_project, 'none', 0, 0, 0, 1, '', 'maxwidth300');
				}
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= ': '.$proj->getNomUrl();
				}
			}
		}
		$morehtmlref .= '</div>';

		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('recruitmentjobpositionnote', 'globalcard'),
		'notepreparehead' => 'recruitmentjobpositionPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("RecruitmentJobPosition"),
		'notepicto' => $object->picto,
		'notepagetitle' => $object->ref." - ".$langs->trans('Notes'),
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.dol_buildpath('/recruitment/recruitmentjobposition_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
	);
}

/**
 * Load context for the "Notes" tab of a Dolibarr user.
 *
 * @return array<string,mixed>
 */
function noteLoadUser()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

	// Get parameters
	$id = GETPOSTINT('id');
	$action = GETPOST('action', 'aZ09');

	if (empty($id)) {
		accessforbidden();
	}

	// Load translation files required by page
	$langs->loadLangs(array('companies', 'members', 'bills', 'users'));

	$object = new User($db);
	$object->fetch($id, '', '', 1);
	$object->loadRights();

	// If user is not user read and no permission to read other users, we stop
	if (($object->id != $user->id) && (!$user->hasRight("user", "user", "read"))) {
		accessforbidden();
	}

	// Permissions
	if ($object->id == $user->id) {
		$permissionnote = $user->hasRight("user", "self", "write"); // Used by the include of actions_setnotes.inc.php
	} else {
		$permissionnote = $user->hasRight("user", "user", "write"); // Used by the include of actions_setnotes.inc.php
	}

	// Security check
	$socid = 0;
	if ($user->socid > 0) {
		$socid = $user->socid;
	}
	$feature2 = (($socid && $user->hasRight("user", "self", "write")) ? '' : 'user');

	restrictedArea($user, 'user', $id, 'user&user', $feature2);

	$noteshownav = ($user->hasRight("user", "user", "read") || $user->admin);

	$notemorehtmlref = function (User $object) use ($langs) {
		$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'&output=file&file='.urlencode(dol_sanitizeFileName($object->getFullName($langs).'.vcf')).'" class="refid valignmiddle" rel="noopener">';
		$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard', 'class="valignmiddle marginleftonly paddingrightonly"');
		$morehtmlref .= '</a>';

		$urltovirtualcard = '/user/virtualcard.php?id='.((int) $object->id);
		$morehtmlref .= dolButtonToOpenUrlInDialogPopup('publicvirtualcard', $langs->transnoentitiesnoconv("PublicVirtualCardUrl").' - '.$object->getFullName($langs), img_picto($langs->trans("PublicVirtualCardUrl"), 'card', 'class="valignmiddle marginleftonly paddingrightonly"'), $urltovirtualcard, '', 'refid valignmiddle nohover');

		return $morehtmlref;
	};

	$noteextracontent = function (User $object) use ($langs) {
		print '<table class="border centpercent tableforfield">';

		// Login
		print '<tr><td class="titlefield">'.$langs->trans("Login").'</td>';
		if (!empty($object->ldap_sid) && $object->statut == 0) {
			print '<td class="error">';
			print $langs->trans("LoginAccountDisableInDolibarr");
			print '</td>';
		} else {
			print '<td>';
			$addadmin = '';
			if (isModEnabled('multicompany') && !empty($object->admin) && empty($object->entity)) {
				$addadmin .= img_picto($langs->trans("SuperAdministratorDesc"), "superadmin", 'class="paddingleft valignmiddle"');
			} elseif (!empty($object->admin)) {
				$addadmin .= img_picto($langs->trans("AdministratorDesc"), "admin", 'class="paddingleft valignmiddle"');
			}
			print showValueWithClipboardCPButton($object->login).$addadmin;
			print '</td>';
		}
		print '</tr>';

		print "</table>";
	};

	$notenotfoundcontent = function () use ($langs) {
		$langs->load("errors");
		print $langs->trans("ErrorRecordNotFound");
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => '',
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('usercard', 'usernote', 'globalcard'),
		'notepreparehead' => 'user_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("User"),
		'notepicto' => 'user',
		'notepagetitle' => '',
		'notebodyclass' => 'mod-user page-card_note',
		'noteparamid' => 'id',
		'notelinkback' => (($user->hasRight("user", "user", "read") || $user->admin) ? '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>' : ''),
		'noteshownav' => $noteshownav,
		'notefieldid' => 'rowid',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
		'noteextracontent' => $noteextracontent,
		'notenotfoundcontent' => $notenotfoundcontent,
	);
}

/**
 * Load context for the "Notes" tab of a webhook trigger history entry.
 *
 * @return array<string,mixed>
 */
function noteLoadTriggerHistory()
{
	global $db, $langs, $user, $conf, $extrafields;

	require_once DOL_DOCUMENT_ROOT.'/webhook/class/triggerhistory.class.php';
	require_once DOL_DOCUMENT_ROOT.'/webhook/lib/webhook_triggerhistory.lib.php';

	// Load translation files required by the page
	$langs->loadLangs(array("webhook@webhook", "companies", "admin"));

	// Get parameters
	$id = GETPOSTINT('id');
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');
	$cancel = GETPOST('cancel', 'alpha');
	$backtopage = GETPOST('backtopage', 'alpha');

	// Initialize a technical objects
	$object = new TriggerHistory($db);
	$diroutputmassaction = $conf->webhook->dir_output.'/temp/massgeneration/'.$user->id;

	// Fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	// Load object
	include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
	if ($id > 0 || !empty($ref)) {
		$upload_dir = $conf->webhook->multidir_output[empty($object->entity) ? $conf->entity : $object->entity]."/".$object->id;
	}

	// There is several ways to check permission.
	$permissiontoread = $permissiontoadd = $permissiontodelete = (!empty($user->admin) ? 1 : 0);
	$permissionnote = $permissiontoadd; // Used by the include of actions_setnotes.inc.php

	// Security check (enable the most restrictive one)
	if (!isModEnabled("webhook")) {
		accessforbidden();
	}
	if (!$permissiontoread) {
		accessforbidden();
	}

	$query = array('restore_lastsearch_values' => 1);
	if (!empty($socid) && $socid > 0) {
		$query += array('socid' => $socid);
	}

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array($object->element.'note', 'globalcard'),
		'notepreparehead' => 'triggerhistoryPrepareHead',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans(""),
		'notepicto' => $object->picto,
		'notepagetitle' => $langs->trans('').' - '.$langs->trans("Notes"),
		'notebodyclass' => 'mod-webhook page-card_notes',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.dolBuildUrl('/webhook/triggerhistory_list.php', $query).'">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
	);
}

/**
 * Load context for the "Notes" tab of a reception.
 *
 * @return array<string,mixed>
 */
function noteLoadReception()
{
	global $db, $langs, $user;

	require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/reception.lib.php';
	if (isModEnabled('project')) {
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	}
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';

	$langs->loadLangs(array("receptions", "companies", "bills", 'orders', 'stocks', 'other', 'propal'));

	$id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('facid')); // For backward compatibility
	$ref = GETPOST('ref', 'alpha');
	$action = GETPOST('action', 'aZ09');

	$origin = '';
	$objectsrc = null;
	$object = new Reception($db);
	if ($id > 0 || !empty($ref)) {
		$object->fetch($id, $ref);
		$object->fetch_thirdparty();

		if (!empty($object->origin)) {
			$origin = (string) $object->origin;

			$object->fetch_origin();
		}

		// Linked documents
		if ($origin == 'order_supplier' && $object->origin_object->id && isModEnabled("supplier_order")) {
			$objectsrc = new CommandeFournisseur($db);
			$objectsrc->fetch($object->origin_object->id);
		}
	}

	// Security check
	if ($user->socid > 0) {
		$socid = $user->socid;
	}

	if (isModEnabled("reception")) {
		$permissiontoread = $user->hasRight('reception', 'lire');
	} else {
		$permissiontoread = $user->hasRight('fournisseur', 'commande', 'receptionner');
	}
	$permissionnote = $user->hasRight('reception', 'creer'); // Used by the include of actions_setnotes.inc.php

	if (isModEnabled("reception") || $origin == 'reception' || empty($origin)) {
		restrictedArea($user, 'reception', $object->id);
	} else {
		if ($origin == 'supplierorder' || $origin == 'order_supplier') {
			restrictedArea($user, 'fournisseur', $object->origin_id, 'commande_fournisseur', 'commande');
		} elseif (!$user->hasRight($origin, 'lire') && !$user->hasRight($origin, 'read')) {
			accessforbidden();
		}
	}

	$notemorehtmlref = function (Reception $object, Form $form) use ($db, $langs, $user, $objectsrc) {
		$morehtmlref = '<div class="refidno">';
		// Ref supplier reception
		$morehtmlref .= $form->editfieldkey("RefSupplier", '', $object->ref_supplier, $object, $user->hasRight('reception', 'creer'), 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefSupplier", '', $object->ref_supplier, $object, $user->hasRight('reception', 'creer'), 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if (!empty($objectsrc) && !empty($objectsrc->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($objectsrc->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
		$morehtmlref .= '</div>';

		return $morehtmlref;
	};

	return array(
		'object' => $object,
		'id' => $id,
		'ref' => $ref,
		'action' => $action,
		'permissionnote' => $permissionnote,
		'notehookcontext' => array('receptionnote'),
		'notepreparehead' => 'reception_prepare_head',
		'notetabid' => 'note',
		'notetabtitle' => $langs->trans("Reception"),
		'notepicto' => 'dollyrevert',
		'notepagetitle' => $langs->trans('Reception'),
		'notebodyclass' => 'mod-reception page-card_notes',
		'noteparamid' => 'ref',
		'notelinkback' => '<a href="'.DOL_URL_ROOT.'/reception/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>',
		'notefieldid' => 'ref',
		'notefieldref' => 'ref',
		'notemorehtmlref' => $notemorehtmlref,
	);
}
