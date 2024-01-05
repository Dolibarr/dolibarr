<?php
/* Copyright (C) 2002-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2012       Cedric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2016-2018  Charlie Benke           <charlie@patas-monkey.com>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
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
 *  \file        fichinter/card-rec.php
 *  \ingroup     intervention
 *  \brief       Page to show predefined fichinter
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinterrec.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
if (isModEnabled('contrat')) {
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcontract.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("interventions", "admin", "compta", "bills"));

// Security check
$id = (GETPOST('fichinterid', 'int') ? GETPOST('fichinterid', 'int') : GETPOST('id', 'int'));
$ref = GETPOST('ref', 'alpha');
$date_next_execution = GETPOST('date_next_execution', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$socid = GETPOST('socid', 'int');
if ($user->socid) {
	$socid = $user->socid;
}
$objecttype = 'fichinter_rec';
if ($action == "create" || $action == "add") {
	$objecttype = '';
}
$result = restrictedArea($user, 'ficheinter', $id, $objecttype);

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;


$sortorder = GETPOST('sortorder', 'aZ09comma');
$sortfield = GETPOST('sortfield', 'aZ09comma');
if ($sortorder == "") {
	$sortorder = "DESC";
}

if ($sortfield == "") {
	$sortfield = "f.datec";
}

$object = new FichinterRec($db);
$extrafields = new ExtraFields($db);


$arrayfields = array(
	'f.titre'=>array('label'=>"Ref", 'checked'=>1),
	's.nom'=>array('label'=>"ThirdParty", 'checked'=>1),
	'f.fk_contrat'=>array('label'=>"Contract", 'checked'=>1),
	'f.duree'=>array('label'=>"Duration", 'checked'=>1),
	'f.total_ttc'=>array('label'=>"AmountTTC", 'checked'=>1),
	'f.frequency'=>array('label'=>"RecurringInvoiceTemplate", 'checked'=>1),
	'f.nb_gen_done'=>array('label'=>"NbOfGenerationDoneShort", 'checked'=>1),
	'f.date_last_gen'=>array('label'=>"DateLastGeneration", 'checked'=>1),
	'f.date_when'=>array('label'=>"NextDateToExecution", 'checked'=>1),
	'f.datec'=>array('label'=>"DateCreation", 'checked'=>0, 'position'=>500),
	'f.tms'=>array('label'=>"DateModificationShort", 'checked'=>0, 'position'=>500),
);


/*
 * Actions
 */

if ($cancel) {
	/*var_dump($cancel);var_dump($backtopage);var_dump($backtopageforcancel);exit;*/
	if (!empty($backtopageforcancel)) {
		header("Location: ".$backtopageforcancel);
		exit;
	} elseif (!empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}
	$action = '';
}

// Create predefined intervention
if ($action == 'add') {
	if (!GETPOST('titre')) {
		setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->trans("Title")), null, 'errors');
		$action = "create";
		$error++;
	}

	if (!GETPOST('socid')) {
		setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->trans("Customer")), null, 'errors');
		$action = "create";
		$error++;
	}

	// gestion des fréquences et des échéances
	$frequency = GETPOST('frequency', 'int');
	$reyear = GETPOST('reyear');
	$remonth = GETPOST('remonth');
	$reday = GETPOST('reday');
	$rehour = GETPOST('rehour');
	$remin = GETPOST('remin');
	$nb_gen_max = (GETPOST('nb_gen_max', 'int') ? GETPOST('nb_gen_max', 'int') : 0);
	if (GETPOST('frequency')) {
		if (empty($reyear) || empty($remonth) || empty($reday)) {
			setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->trans("Date")), null, 'errors');
			$action = "create";
			$error++;
		} else {
			$date_next_execution = dol_mktime($rehour, $remin, 0, $remonth, $reday, $reyear);
		}
		if ($nb_gen_max === '') {
			setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->trans("MaxPeriodNumber")), null, 'errors');
			$action = "create";
			$error++;
		}
	}

	if (!$error) {
		$object->id_origin = $id;
		$object->title			= GETPOST('titre', 'alpha');
		$object->description	= GETPOST('description', 'restricthtml');
		$object->socid			= GETPOST('socid', 'alpha');
		$object->fk_project		= GETPOST('projectid', 'int');
		$object->fk_contract	= GETPOST('contractid', 'int');

		$object->frequency = $frequency;
		$object->unit_frequency = GETPOST('unit_frequency', 'alpha');
		$object->nb_gen_max = $nb_gen_max;
		$object->auto_validate = GETPOST('auto_validate', 'int');

		$object->date_when = $date_next_execution;

		if ($object->create($user) > 0) {
			$id = $object->id;
			$action = '';
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = "create";
		}
	}
} elseif ($action == 'createfrommodel') {
	$newinter = new Fichinter($db);

	// on récupère les enregistrements
	$object->fetch($id);
	$res = $object->fetch_lines();
	// on transfert les données de l'un vers l'autre
	if ($object->socid > 0) {
		$newinter->socid = $object->socid;
		$newinter->fk_project = $object->fk_project;
		$newinter->fk_contrat = $object->fk_contrat;
	} else {
		$newinter->socid = GETPOST("socid");
	}

	$newinter->entity = $object->entity;
	$newinter->duree = $object->duree;

	$newinter->description = $object->description;
	$newinter->note_private = $object->note_private;
	$newinter->note_public = $object->note_public;

	// on créer un nouvelle intervention
	$extrafields->fetch_name_optionals_label($newinter->table_element);

	$array_options = $extrafields->getOptionalsFromPost($newinter->table_element);
	$newinter->array_options = $array_options;

	$newfichinterid = $newinter->create($user);

	if ($newfichinterid > 0) {
		// Now we add line of details
		foreach ($object->lines as $line) {
			$newinter->addline($user, $newfichinterid, $line->desc, $line->datei, $line->duree, '');
		}

		// on update le nombre d'inter crée à partir du modèle
		$object->updateNbGenDone();
		//on redirige vers la fiche d'intervention nouvellement crée
		header('Location: '.DOL_URL_ROOT.'/fichinter/card.php?id='.$newfichinterid);
		exit;
	} else {
		setEventMessages($newinter->error, $newinter->errors, 'errors');
		$action = '';
	}
} elseif ($action == 'delete' && $user->hasRight('ficheinter', 'supprimer')) {
	// delete modele
	$object->fetch($id);
	$object->delete($user);
	$id = 0;
	header('Location: '.$_SERVER["PHP_SELF"]);
	exit;
} elseif ($action == 'setfrequency' && $user->hasRight('ficheinter', 'creer')) {
	// Set frequency and unit frequency
	$object->fetch($id);
	$object->setFrequencyAndUnit(GETPOST('frequency', 'int'), GETPOST('unit_frequency', 'alpha'));
} elseif ($action == 'setdate_when' && $user->hasRight('ficheinter', 'creer')) {
	// Set next date of execution
	$object->fetch($id);
	$date = dol_mktime(GETPOST('date_whenhour'), GETPOST('date_whenmin'), 0, GETPOST('date_whenmonth'), GETPOST('date_whenday'), GETPOST('date_whenyear'));
	if (!empty($date)) {
		$object->setNextDate($date);
	}
} elseif ($action == 'setnb_gen_max' && $user->hasRight('ficheinter', 'creer')) {
	// Set max period
	$object->fetch($id);
	$object->setMaxPeriod(GETPOST('nb_gen_max', 'int'));
}


/*
 *	View
 */

$help_url = '';

llxHeader('', $langs->trans("RepeatableIntervention"), $help_url);

$form = new Form($db);
$companystatic = new Societe($db);
if (isModEnabled('contrat')) {
	$contratstatic = new Contrat($db);
}
if (isModEnabled('project')) {
	$projectstatic = new Project($db);
}

$now = dol_now();
$tmparray = dol_getdate($now);
$today = dol_mktime(23, 59, 59, $tmparray['mon'], $tmparray['mday'], $tmparray['year']); // Today is last second of current day



/*
 * Create mode
 */
if ($action == 'create') {
	print load_fiche_titre($langs->trans("CreateRepeatableIntervention"), '', 'intervention');

	$object = new Fichinter($db); // Source invoice
	//$object = new Managementfichinter($db);   // Source invoice

	if ($object->fetch($id, $ref) > 0) {
		print '<form action="card-rec.php" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		print '<input type="hidden" name="fichinterid" value="'.$object->id.'">';

		print dol_get_fiche_head();

		$rowspan = 4;
		if (isModEnabled('project') && $object->fk_project > 0) {
			$rowspan++;
		}
		if (isModEnabled('contrat') && $object->fk_contrat > 0) {
			$rowspan++;
		}

		print '<table class="border centpercent">';

		$object->fetch_thirdparty();

		// Third party
		print '<tr><td>'.$langs->trans("Customer").'</td><td>';
		print $form->select_company($object->thirdparty->id, 'socid', '', 0, 1);

		//.$object->thirdparty->getNomUrl(1,'customer').
		print '</td><td>';
		print $langs->trans("Comment");
		print '</td></tr>';

		// Title
		print '<tr><td class="fieldrequired">'.$langs->trans("Title").'</td><td>';
		print '<input class="flat quatrevingtpercent" type="text" name="titre" value="'.dol_escape_htmltag(GETPOST("titre", "alphanohtml")).'">';
		print '</td>';

		// Note
		print '<td rowspan="'.$rowspan.'" valign="top">';
		print '<textarea class="flat" name="description" wrap="soft" cols="60" rows="'.ROWS_4.'">';
		print $object->description.'</textarea>';
		print '</td></tr>';

		// Author
		print "<tr><td>".$langs->trans("Author")."</td><td>".$user->getFullName($langs)."</td></tr>";

		if (!getDolGlobalString('FICHINTER_DISABLE_DETAILS')) {
			// Duration
			print '<tr><td>'.$langs->trans("TotalDuration").'</td>';
			print '<td colspan="3">'.convertSecondToTime($object->duration, 'all', $conf->global->MAIN_DURATION_OF_WORKDAY).'</td>';
			print '</tr>';
		}

		// Project
		if (isModEnabled('project')) {
			$formproject = new FormProjets($db);
			print "<tr><td>".$langs->trans("Project")."</td><td>";
			$projectid = GETPOST('projectid') ? GETPOST('projectid') : $object->fk_project;

			$numprojet = $formproject->select_projects($object->thirdparty->id, $projectid, 'projectid', 0, 0, 1, 0, 0, 0, 0, '', 0, 0, '');
			print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$object->thirdparty->id;
			print '&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?action=create';
			print '&socid='.$object->thirdparty->id.(!empty($id) ? '&id='.$id : '').'">';
			print $langs->trans("AddProject").'</a>';
			print "</td></tr>";
		}

		// Contrat
		if (isModEnabled('contrat')) {
			$formcontract = new FormContract($db);
			print "<tr><td>".$langs->trans("Contract")."</td><td>";
			$contractid = GETPOST('contractid') ? GETPOST('contractid') : (!empty($object->fk_contract) ? $object->fk_contract : 0) ;
			$numcontract = $formcontract->select_contract($object->thirdparty->id, $contractid, 'contracttid');
			print "</td></tr>";
		}
		print "</table>";

		print '<br><br>';

		/// frequency & duration
		// Autogeneration
		$title = $langs->trans("Recurrence");
		print load_fiche_titre($title, '', 'calendar');

		print '<table class="border centpercent">';

		// Frequency
		print '<tr><td class="titlefieldcreate">';
		print $form->textwithpicto($langs->trans("Frequency"), $langs->transnoentitiesnoconv('toolTipFrequency'));
		print "</td><td>";
		print '<input type="text" name="frequency" value="'.GETPOST('frequency', 'int').'" size="4">&nbsp;';
		print $form->selectarray('unit_frequency', array('d'=>$langs->trans('Day'), 'm'=>$langs->trans('Month'), 'y'=>$langs->trans('Year')), (GETPOST('unit_frequency') ? GETPOST('unit_frequency') : 'm'));
		print "</td></tr>";

		// First date of execution for cron
		print "<tr><td>".$langs->trans('NextDateToExecution')."</td><td>";
		if (empty($date_next_execution)) {
			$date_next_execution = (GETPOST('remonth') ? dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear')) : -1);
		}
		print $form->selectDate($date_next_execution, '', 1, 1, '', "add", 1, 1);
		print "</td></tr>";

		// Number max of generation
		print "<tr><td>".$langs->trans("MaxPeriodNumber")."</td><td>";
		print '<input type="text" name="nb_gen_max" value="'.GETPOST('nb_gen_max', 'int').'" size="5">';
		print "</td></tr>";

		print "</table>";

		print '<br>';

		$title = $langs->trans("ProductsAndServices");
		if (!isModEnabled("service")) {
			$title = $langs->trans("Products");
		} elseif (!isModEnabled("product")) {
			$title = $langs->trans("Services");
		}

		print load_fiche_titre($title, '', '');

		/*
		 * Fichinter lines
		 */
		print '<table class="notopnoleftnoright" width="100%">';
		print '<tr><td colspan="3">';

		$sql = 'SELECT l.rowid, l.description, l.duree';
		$sql .= " FROM ".MAIN_DB_PREFIX."fichinterdet as l";
		$sql .= " WHERE l.fk_fichinter= ".((int) $object->id);
		//$sql.= " AND l.fk_product is null ";
		$sql .= " ORDER BY l.rang";

		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
			$i = 0;
			$total = 0;

			echo '<table class="noborder centpercent">';
			if ($num) {
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Description").'</td>';
				print '<td class="center">'.$langs->trans("Duration").'</td>';
				print "</tr>\n";
			}
			while ($i < $num) {
				$objp = $db->fetch_object($result);
				print '<tr class="oddeven">';

				// Show product and description

				print '<td>';
				print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

				$text = img_object($langs->trans('Service'), 'service');

				print $text.' '.nl2br($objp->description);

				// Duration
				print '<td class="center">'.convertSecondToTime($objp->duree).'</td>';
				print "</tr>";

				$i++;
			}
			$db->free($result);
		} else {
			print $db->error();
		}
		print "</table>";

		print '</td></tr>';

		print "</table>\n";

		print dol_get_fiche_end();

		print $form->buttonsSaveCancel("Create");

		print "</form>\n";
	} else {
		dol_print_error('', "Error, no fichinter ".$object->id);
	}
} elseif ($action == 'selsocforcreatefrommodel') {
	print load_fiche_titre($langs->trans("CreateRepeatableIntervention"), '', 'intervention');
	print dol_get_fiche_head('');

	print '<form name="fichinter" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<table class="border centpercent">';
	print '<tr><td class="fieldrequired">'.$langs->trans("ThirdParty").'</td><td>';
	print $form->select_company('', 'socid', '', 1, 1);
	print '</td></tr>';
	print '</table>';

	print dol_get_fiche_end();

	print '<input type="hidden" name="action" value="createfrommodel">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print $form->buttonsSaveCancel("CreateDraftIntervention", '');

	print '</form>';
} else {
	/*
	 * View mode
	 *
	 */
	if ($id > 0) {
		if ($object->fetch($id) > 0) {
			$object->fetch_thirdparty();

			$author = new User($db);
			$author->fetch($object->user_author);

			$head = fichinter_rec_prepare_head($object);

			print dol_get_fiche_head($head, 'card', $langs->trans("PredefinedInterventional"), 0, 'intervention');

			// Intervention card
			$linkback = '<a href="card-rec.php">'.$langs->trans("BackToList").'</a>';

			$morehtmlref = '<div class="refidno">';
			// Thirdparty

			$morehtmlref .= $langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
			// Project
			if (isModEnabled('project')) {
				$formproject = new FormProjets($db);
				$langs->load("projects");
				$morehtmlref .= '<br>'.$langs->trans('Project').' ';
				if ($user->hasRight('ficheinter', 'creer')) {
					if ($action != 'classify') {
						$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">';
						$morehtmlref .= img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
					}
					if ($action == 'classify') {
						$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
						$morehtmlref .= '<input type="hidden" name="action" value="classin">';
						$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
						$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
						$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
						$morehtmlref .= '</form>';
					} else {
						$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1, '', 'maxwidth300');
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

			dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border centpercent">';

			print "<tr><td>".$langs->trans("Author").'</td><td colspan="3">'.$author->getFullName($langs)."</td></tr>";

			if (!getDolGlobalString('FICHINTER_DISABLE_DETAILS')) {
				// Duration
				print '<tr><td class="titlefield">'.$langs->trans("TotalDuration").'</td>';
				print '<td colspan="3">';
				print convertSecondToTime($object->duration, 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);
				print '</td></tr>';
			}

			print '<tr><td>'.$langs->trans("Description").'</td><td colspan="3">'.nl2br($object->description)."</td></tr>";

			// Contract
			if (isModEnabled('contrat')) {
				$langs->load('contracts');
				print '<tr>';
				print '<td>';

				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('Contract');
				print '</td>';
				if ($action != 'contrat') {
					print '<td class="right"><a href="'.$_SERVER["PHP_SELF"].'?action=contrat&amp;id='.$object->id.'">';
					print img_edit($langs->trans('SetContract'), 1);
					print '</a></td>';
				}
				print '</tr></table>';
				print '</td><td>';
				if ($action == 'contrat') {
					$formcontract = new FormContract($db);
					$formcontract->formSelectContract($_SERVER["PHP_SELF"].'?id='.$object->id, $object->socid, $object->fk_contrat, 'contratid', 0, 1);
				} else {
					if ($object->fk_contrat) {
						$contratstatic = new Contrat($db);
						$contratstatic->fetch($object->fk_contrat);
						print $contratstatic->getNomUrl(0, '', 1);
					} else {
						print "&nbsp;";
					}
				}
				print '</td>';
				print '</tr>';
			}
			print "</table>";
			print '</div>';

			print '<div class="fichehalfright">';
			print '<div class="underbanner clearboth"></div>';

			$title = $langs->trans("Recurrence");
			print load_fiche_titre($title, '', 'calendar');

			print '<table class="border centpercent">';

			// if "frequency" is empty or = 0, the reccurence is disabled
			print '<tr><td class="titlefield">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('Frequency');
			print '</td>';
			if ($action != 'editfrequency' && $user->hasRight('ficheinter', 'creer')) {
				print '<td class="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editfrequency&token='.newToken().'&id='.$id.'">';
				print img_edit($langs->trans('Edit'), 1).'</a></td>';
			}
			print '</tr></table>';
			print '</td><td>';
			if ($action == 'editfrequency') {
				print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
				print '<input type="hidden" name="action" value="setfrequency">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<table class="nobordernopadding">';
				print '<tr><td>';
				print '<input type="text" name="frequency" value="'.$object->frequency.'" size="5">&nbsp;';
				print $form->selectarray('unit_frequency', array('d'=>$langs->trans('Day'), 'm'=>$langs->trans('Month'), 'y'=>$langs->trans('Year')), ($object->unit_frequency ? $object->unit_frequency : 'm'));
				print '</td>';
				print '<td class="left"><input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'"></td>';
				print '</tr></table></form>';
			} else {
				if ($object->frequency > 0) {
					print $langs->trans('FrequencyPer_'.$object->unit_frequency, $object->frequency);
				} else {
					print $langs->trans("NotARecurringInterventionalTemplate");
				}
			}
			print '</td></tr>';

			// Date when
			print '<tr><td>';
			if ($user->hasRight('ficheinter', 'creer') && ($action == 'date_when' || $object->frequency > 0)) {
				print $form->editfieldkey($langs->trans("NextDateToExecution"), 'date_when', $object->date_when, $object, $user->hasRight('facture', 'creer'), 'day');
			} else {
				print $langs->trans("NextDateToExecution");
			}
			print '</td><td>';
			if ($action == 'date_when' || $object->frequency > 0) {
				print $form->editfieldval($langs->trans("NextDateToExecution"), 'date_when', $object->date_when, $object, $user->hasRight('facture', 'creer'), 'day');
			}
			print '</td>';
			print '</tr>';

			// Max period / Rest period
			print '<tr><td>';
			if ($user->hasRight('ficheinter', 'creer') && ($action == 'nb_gen_max' || $object->frequency > 0)) {
				print $form->editfieldkey($langs->trans("MaxPeriodNumber"), 'nb_gen_max', $object->nb_gen_max, $object, $user->hasRight('facture', 'creer'));
			} else {
				print $langs->trans("MaxPeriodNumber");
			}

			print '</td><td>';
			if ($action == 'nb_gen_max' || $object->frequency > 0) {
				print $form->editfieldval($langs->trans("MaxPeriodNumber"), 'nb_gen_max', $object->nb_gen_max ? $object->nb_gen_max : '', $object, $user->hasRight('facture', 'creer'));
			} else {
				print '';
			}

			print '</td>';
			print '</tr>';

			print '</table>';

			// Frequencry/Recurring section
			if ($object->frequency > 0) {
				print '<br>';
				if (empty($conf->cron->enabled)) {
					$txtinfoadmin = $langs->trans("EnableAndSetupModuleCron", $langs->transnoentitiesnoconv("Module2300Name"));
					print info_admin($txtinfoadmin);
				}
				print '<div class="underbanner clearboth"></div>';
				print '<table class="border centpercent">';

				// Nb of generation already done
				print '<tr><td class="titlefield">'.$langs->trans("NbOfGenerationOfRecordDone").'</td>';
				print '<td>';
				print $object->nb_gen_done ? $object->nb_gen_done : '0';
				print '</td>';
				print '</tr>';

				// Date last
				print '<tr><td>';
				print $langs->trans("DateLastGeneration");
				print '</td><td>';
				print dol_print_date($object->date_last_gen, 'dayhour');
				print '</td>';
				print '</tr>';
				print '</table>';
				print '<br>';
			}

			print '</div>';
			print '</div>';

			print '<div class="clearboth"></div><br>';

			/*
			 * Lines
			 */

			$title = $langs->trans("ProductsAndServices");
			if (!isModEnabled("service")) {
				$title = $langs->trans("Products");
			} elseif (!isModEnabled("product")) {
				$title = $langs->trans("Services");
			}

			print load_fiche_titre($title);

			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Description").'</td>';
			print '<td class="center">'.$langs->trans("Duration").'</td>';
			print '</tr>';

			$num = count($object->lines);
			$i = 0;
			while ($i < $num) {
				// Show product and description
				if (isset($object->lines[$i]->product_type)) {
					$type = $object->lines[$i]->product_type;
				} else {
					$object->lines[$i]->fk_product_type;
				}
				// Try to enhance type detection using date_start and date_end for free lines when type
				// was not saved.
				if (!empty($objp->date_start)) {
					$type = 1;
				}
				if (!empty($objp->date_end)) {
					$type = 1;
				}

				// Show line
				print '<tr class="oddeven">';
				print '<td>';
				$text = img_object($langs->trans('Service'), 'service');
				print $text.' '.nl2br($object->lines[$i]->desc);
				print '</td>';

				print '<td class="center">'.convertSecondToTime($object->lines[$i]->duree).'</td>';
				print "</tr>\n";
				$i++;
			}
			print '</table>';

			/*
			 * Action bar
			 */
			print '<div class="tabsAction">';

			if ($user->hasRight('ficheinter', 'creer')) {
				print '<div class="inline-block divButAction">';
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=createfrommodel&token='.newToken();
				print '&socid='.$object->thirdparty->id.'&id='.$object->id.'">';
				print $langs->trans("AddIntervention").'</a></div>';
			}

			// Delete
			print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', $user->hasRight('ficheinter', 'supprimer'));

			print '</div>';
		} else {
			print $langs->trans("ErrorRecordNotFound");
		}
	} else {
		/*
		 *  List mode
		 */
		$sql = "SELECT f.rowid as fich_rec, s.nom as name, s.rowid as socid, f.rowid as facid, f.titre as title,";
		$sql .= " f.duree, f.fk_contrat, f.fk_projet as fk_project, f.frequency, f.nb_gen_done, f.nb_gen_max,";
		$sql .= " f.date_last_gen, f.date_when, f.datec";

		$sql .= " FROM ".MAIN_DB_PREFIX."fichinter_rec as f";
		$sql .= " , ".MAIN_DB_PREFIX."societe as s ";
		if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= " , ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql .= " WHERE f.fk_soc = s.rowid";
		$sql .= " AND f.entity = ".$conf->entity;
		if (!empty($socid)) {
			$sql .= " AND s.rowid = ".((int) $socid);
		}
		if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		if (!empty($search_ref)) {
			$sql .= natural_search('f.titre', $search_ref);
		}
		if (!empty($search_societe)) {
			$sql .= natural_search('s.nom', $search_societe);
		}
		if (!empty($search_frequency) && $search_frequency == '1') {
			$sql .= ' AND f.frequency > 0';
		}
		if (isset($search_frequency) && (string) $search_frequency == '0') {
			$sql .= ' AND (f.frequency IS NULL or f.frequency = 0)';
		}


		//$sql .= " ORDER BY $sortfield $sortorder, rowid DESC ";
		//	$sql .= $db->plimit($limit + 1, $offset);

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);

			print_barre_liste($langs->trans("RepeatableIntervention"), $page, $_SERVER['PHP_SELF'], "&socid=$socid", $sortfield, $sortorder, '', $num, '', 'intervention');

			print '<span class="opacitymedium">'.$langs->trans("ToCreateAPredefinedIntervention").'</span><br><br>';

			$i = 0;
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print_liste_field_titre("Ref", $_SERVER['PHP_SELF'], "f.titre", "", "", 'width="200px"', $sortfield, $sortorder, 'left ');
			print_liste_field_titre("Company", $_SERVER['PHP_SELF'], "s.nom", "", "", 'width="200px"', $sortfield, $sortorder, 'left ');
			if (isModEnabled('contrat')) {
				print_liste_field_titre("Contract", $_SERVER['PHP_SELF'], "f.fk_contrat", "", "", 'width="100px"', $sortfield, $sortorder, 'left ');
			}
			if (isModEnabled('project')) {
				print_liste_field_titre("Project", $_SERVER['PHP_SELF'], "f.fk_project", "", "", 'width="100px"', $sortfield, $sortorder, 'left ');
			}
			print_liste_field_titre("Duration", $_SERVER['PHP_SELF'], 'f.duree', '', '', 'width="50px"', $sortfield, $sortorder, 'right ');
			// Recurring or not
			print_liste_field_titre("Frequency", $_SERVER['PHP_SELF'], "f.frequency", "", "", 'width="100px"', $sortfield, $sortorder, 'center ');
			print_liste_field_titre("NbOfGenerationDoneShort", $_SERVER['PHP_SELF'], "f.nb_gen_done", "", "", 'width="100px"', $sortfield, $sortorder, 'center ');
			print_liste_field_titre("DateLastGeneration", $_SERVER['PHP_SELF'], "f.date_last_gen", "", "", 'width="100px"', $sortfield, $sortorder, 'center ');
			print_liste_field_titre("NextDateToIntervention", $_SERVER['PHP_SELF'], "f.date_when", "", "", 'width="100px"', $sortfield, $sortorder, 'center ');
			print '<th width="100px"></th>';
			print "</tr>\n";


			// les filtres à faire ensuite

			if ($num > 0) {
				while ($i < min($num, $limit)) {
					$objp = $db->fetch_object($resql);

					print '<tr class="oddeven">';
					print '<td><a href="'.$_SERVER['PHP_SELF'].'?id='.$objp->fich_rec.'">';
					print img_object($langs->trans("ShowIntervention"), "intervention").' '.$objp->title;
					print "</a></td>\n";
					if ($objp->socid) {
						$companystatic->id = $objp->socid;
						$companystatic->name = $objp->name;
						print '<td>'.$companystatic->getNomUrl(1, 'customer').'</td>';
					} else {
						print '<td>'.$langs->trans("None").'</td>';
					}

					if (isModEnabled('contrat')) {
						print '<td>';
						if ($objp->fk_contrat > 0) {
							$contratstatic->fetch($objp->fk_contrat);
							print $contratstatic->getNomUrl(1);
						}
						print '</td>';
					}
					if (isModEnabled('project')) {
						print '<td>';
						if ($objp->fk_project > 0) {
							$projectstatic->fetch($objp->fk_project);
							print $projectstatic->getNomUrl(1);
						}
						print '</td>';
					}

					print '<td class=right>'.convertSecondToTime($objp->duree).'</td>';

					print '<td class="center">'.yn($objp->frequency ? 1 : 0).'</td>';

					print '<td class="center">';
					if ($objp->frequency) {
						print $objp->nb_gen_done.($objp->nb_gen_max > 0 ? ' / '.$objp->nb_gen_max : '');
						print '</td>';

						print '<td class="center">';
						print dol_print_date($db->jdate($objp->date_last_gen), 'day');
						print '</td>';

						print '<td class="center">';
						print dol_print_date($db->jdate($objp->date_when), 'day');
						print '</td>';
					} else {
						print '<span class="opacitymedium">'.$langs->trans('NA').'</span>';
						print '</td>';
						print '<td class="center">';
						print '<span class="opacitymedium">'.$langs->trans('NA').'</span>';
						print '</td>';
						print '<td class="center">';
						print '<span class="opacitymedium">'.$langs->trans('NA').'</span>';
						print '</td>';
					}

					if ($user->hasRight('ficheinter', 'creer')) {
						// Action column
						print '<td class="center">';
						if ($user->hasRight('ficheinter', 'creer')) {
							if (empty($objp->frequency) || $db->jdate($objp->date_when) <= $today) {
								print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=createfrommodel';
								print '&socid='.$objp->socid.'&id='.$objp->fich_rec.'">';
								print $langs->trans("CreateFichInter").'</a>';
							} else {
								print $langs->trans("DateIsNotEnough");
							}
						} else {
							print "&nbsp;";
						}

						print "</td>";

						print "</tr>\n";
						$i++;
					}
				}
			} else {
				print '<tr class="oddeven"><td colspan="10"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
			}

			print "</table>";
			$db->free($resql);
		} else {
			dol_print_error($db);
		}
	}
}
llxFooter();
$db->close();
