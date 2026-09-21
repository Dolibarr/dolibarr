<?php
/* Copyright (C) 2026		Quentin Vial-Gouteyron	<quentin.vial-gouteyron@atm-consulting.fr>
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
 *	\file       htdocs/ticket/time.php
 *	\ingroup    ticket
 *	\brief      Tab to record and list the time spent on a ticket
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var Societe $mysoc
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/timespent.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}
if (isModEnabled('product') || isModEnabled('service')) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
}
if (isModEnabled('invoice')) {
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
}
if (isModEnabled('intervention')) {
	require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formintervention.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'ticket', 'projects', 'bills', 'products', 'salaries', 'orders', 'interventions', 'errors'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$lineid = GETPOSTINT('lineid');
$massaction = GETPOST('massaction', 'alpha');
$toselect = GETPOST('toselect', 'array:int');
$optioncss = GETPOST('optioncss', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ09') ? GETPOST('contextpage', 'aZ09') : 'tickettimespentlist';

// Search criteria
$search_note = GETPOST('search_note', 'alphanohtml');
$search_user = GETPOSTINT('search_user');
$search_date_startday = GETPOSTINT('search_date_startday');
$search_date_startmonth = GETPOSTINT('search_date_startmonth');
$search_date_startyear = GETPOSTINT('search_date_startyear');
$search_date_endday = GETPOSTINT('search_date_endday');
$search_date_endmonth = GETPOSTINT('search_date_endmonth');
$search_date_endyear = GETPOSTINT('search_date_endyear');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);
$search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);

// Pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}
$offset = $limit * $page;
if (!$sortfield) {
	$sortfield = 't.element_date,t.element_datehour,t.rowid';
}
if (!$sortorder) {
	$sortorder = 'DESC,DESC,DESC';
}


$object = new Ticket($db);
$timespent = new TimeSpent($db);
$form = new Form($db);

$hookmanager->initHooks(array('tickettimespentlist', 'globalcard'));

$error = 0;

// Security check. Ticket::fetch($id) does not filter the entity: restrictedArea() MUST follow it.
$result = $object->fetch($id, $ref);
if ($result < 0) {
	dol_print_error($db, $object->error);
	exit;
}
if (!($object->id > 0)) {
	accessforbidden();
}

restrictedArea($user, 'ticket', $object->id);

// Internal only: element_time carries the cost rate of the contributors
if (!empty($user->socid)) {
	accessforbidden();
}

$permissiontoadd = $user->hasRight('ticket', 'write');
// Same expression as ticket/card.php, not the one of the other tabs of the module (wrong right)
$permissiontomanage = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('ticket', 'write')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('ticket', 'manage_advance')));

if (!$user->socid && (getDolGlobalString('TICKET_LIMIT_VIEW_ASSIGNED_ONLY') && $object->fk_user_assign != $user->id) && !$permissiontomanage) {
	accessforbidden('', 0, 1);
}

// Ids of the current user and of all its subordinates
$childids = $user->getAllChildIds(1);

// The confirmation forms instantiate classes only included when their module is enabled
if (!in_array($massaction, array('generateinvoice', 'generateinter'))
	|| !$permissiontoadd
	|| ($massaction == 'generateinvoice' && !(isModEnabled('invoice') && $user->hasRight('facture', 'creer')))
	|| ($massaction == 'generateinter' && !(isModEnabled('intervention') && $user->hasRight('ficheinter', 'creer')))) {
	$massaction = '';
}

$arrayfields = array(
	't.element_date' => array('label' => $langs->trans("Date"), 'checked' => '1', 'position' => 10),
	'author' => array('label' => $langs->trans("By"), 'checked' => '1', 'position' => 20),
	't.note' => array('label' => $langs->trans("Note"), 'checked' => '1', 'position' => 30),
	't.fk_product' => array('label' => $langs->trans("Service"), 'checked' => '0', 'position' => 40, 'enabled' => (string) (int) isModEnabled("service")),
	't.element_duration' => array('label' => $langs->trans("Duration"), 'checked' => '1', 'position' => 50),
	'value' => array('label' => $langs->trans("Value"), 'checked' => '1', 'position' => 60, 'enabled' => (string) (int) isModEnabled("salaries")),
	'valuebilled' => array('label' => $langs->trans("Billed"), 'checked' => '1', 'position' => 70, 'enabled' => (string) (int) isModEnabled("invoice")),
	't.tms' => array('label' => $langs->trans("DateModificationShort"), 'checked' => '0', 'position' => 500)
);
// The display tests 'checked' only, so a field of a disabled module must be removed
foreach ($arrayfields as $key => $val) {
	if (isset($val['enabled']) && empty($val['enabled'])) {
		unset($arrayfields[$key]);
	}
}
$arrayfields = dol_sort_array($arrayfields, 'position');


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = '';
	$massaction = '';
}
// The add form and the filter line share the same <form>: a search must not be taken for a save
if (GETPOST('button_search', 'alpha') || GETPOST('button_search_x', 'alpha')) {
	$action = 'list';
}

$parameters = array('id' => $object->id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
		$search_note = '';
		$search_user = 0;
		$search_date_startday = 0;
		$search_date_startmonth = 0;
		$search_date_startyear = 0;
		$search_date_endday = 0;
		$search_date_endmonth = 0;
		$search_date_endyear = 0;
		$search_date_start = '';
		$search_date_end = '';
		$action = '';
	}

	// Add a time spent record
	if ($action == 'addtimespent' && $permissiontoadd) {
		$newtimespent = new TimeSpent($db);

		$durationhour = GETPOSTINT('timespent_durationhour');
		$durationmin = GETPOSTINT('timespent_durationmin');
		if (empty($durationhour) && empty($durationmin)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Duration")), null, 'errors');
			$error++;
		}
		if (!GETPOSTINT('userid')) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("User")), null, 'errors');
			$error++;
		} elseif (!$permissiontomanage && !in_array(GETPOSTINT('userid'), $childids)) {
			accessforbidden('', 0, 1);
		}

		if (!$error) {
			$newtimespent->element_duration = $durationhour * 3600 + $durationmin * 60;
			if (GETPOST('timehour', 'int') != '' && GETPOSTINT('timehour') >= 0) {	// If hour was entered
				$newtimespent->element_date = dol_mktime(GETPOSTINT("timehour"), GETPOSTINT("timemin"), 0, GETPOSTINT("timemonth"), GETPOSTINT("timeday"), GETPOSTINT("timeyear"));
				$newtimespent->element_date_withhour = 1;
			} else {
				$newtimespent->element_date = dol_mktime(12, 0, 0, GETPOSTINT("timemonth"), GETPOSTINT("timeday"), GETPOSTINT("timeyear"));
				$newtimespent->element_date_withhour = 0;
			}
			$newtimespent->element_datehour = $newtimespent->element_date;
			$newtimespent->fk_user = GETPOSTINT('userid');
			$newtimespent->fk_product = ticketIsSellableService($db, GETPOSTINT('fk_product')) ? GETPOSTINT('fk_product') : 0;
			$newtimespent->note = GETPOST('timespent_note', 'alphanohtml');

			$result = $object->addTimeSpent($user, $newtimespent);
			if ($result > 0) {
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'createtime';
			}
		} else {
			$action = 'createtime';
		}
	}

	// Update a time spent record
	if ($action == 'updateline' && $permissiontoadd && !GETPOST('cancel', 'alpha')) {
		$linetoupdate = new TimeSpent($db);
		$result = $linetoupdate->fetch($lineid);

		if ($result < 0) {
			setEventMessages($linetoupdate->error, $linetoupdate->errors, 'errors');
			$error++;
		} elseif ($result == 0) {
			setEventMessages($langs->trans("TicketTimeSpentLineNotFound"), null, 'errors');
			$error++;
		} elseif (!in_array($linetoupdate->fk_user, $childids) && !$permissiontomanage) {
			accessforbidden('', 0, 1);
		}

		// A field of a hidden column is not posted: it keeps its stored value
		$durationhour = GETPOSTINT('new_durationhour');
		$durationmin = GETPOSTINT('new_durationmin');
		if (!$error && GETPOSTISSET('new_durationhour') && empty($durationhour) && empty($durationmin)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Duration")), null, 'errors');
			$error++;
		}

		if (!$error) {
			if (GETPOSTISSET('new_durationhour')) {
				$linetoupdate->element_duration = $durationhour * 3600 + $durationmin * 60;
			}
			if (GETPOSTISSET('timelinemonth')) {
				if (GETPOST('timelinehour', 'int') != '' && GETPOSTINT('timelinehour') >= 0) {
					$linetoupdate->element_date = dol_mktime(GETPOSTINT("timelinehour"), GETPOSTINT("timelinemin"), 0, GETPOSTINT("timelinemonth"), GETPOSTINT("timelineday"), GETPOSTINT("timelineyear"));
					$linetoupdate->element_date_withhour = 1;
				} else {
					$linetoupdate->element_date = dol_mktime(12, 0, 0, GETPOSTINT("timelinemonth"), GETPOSTINT("timelineday"), GETPOSTINT("timelineyear"));
					$linetoupdate->element_date_withhour = 0;
				}
				$linetoupdate->element_datehour = $linetoupdate->element_date;
			}
			// The check above bears on the previous owner, this one on the new owner
			if (GETPOSTINT('userid') > 0 && GETPOSTINT('userid') != $linetoupdate->fk_user) {
				if (!$permissiontomanage && !in_array(GETPOSTINT('userid'), $childids)) {
					accessforbidden('', 0, 1);
				}
				$linetoupdate->fk_user = GETPOSTINT('userid');
			}
			if (GETPOSTISSET('fk_product')) {
				$linetoupdate->fk_product = ticketIsSellableService($db, GETPOSTINT('fk_product')) ? GETPOSTINT('fk_product') : 0;
			}
			if (GETPOSTISSET('timespent_note')) {
				$linetoupdate->note = GETPOST('timespent_note', 'alphanohtml');
			}

			$result = $object->updateTimeSpent($user, $linetoupdate);
			if ($result > 0) {
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	// $toselect comes from the user: reduce it to the records of this ticket
	$selectedlines = array();
	if (in_array($action, array('confirm_generateinvoice', 'confirm_generateinter'))) {
		if (empty($toselect)) {
			setEventMessages($langs->trans("NoRecordSelected"), null, 'errors');
			$action = '';
		} else {
			$selectedlines = $timespent->filterAttachedIds($toselect, $object->element, (int) $object->id);

			if (count($selectedlines) != count($toselect)) {
				dol_syslog('ticket/time.php refused a selection holding records not attached to ticket '.$object->id, LOG_WARNING);
				setEventMessages($langs->trans("TicketTimeSpentSelectionNotAttached"), null, 'errors');
				$selectedlines = array();
				$toselect = array();
				$action = '';
			}
		}

		// A record already invoiced or turned into an intervention must not be processed again.
		// Joined on the target: nothing in the core clears intervention_id when an intervention
		// line is deleted, so a stale stamp must not lock the record.
		if (!empty($selectedlines)) {
			$sql = "SELECT COUNT(t.rowid) as nb";
			$sql .= " FROM ".$db->prefix()."element_time as t";
			if ($action == 'confirm_generateinvoice') {	// Test on permission already done
				$sql .= " INNER JOIN ".$db->prefix()."facture as target ON target.rowid = t.invoice_id";
			} else {
				$sql .= " INNER JOIN ".$db->prefix()."fichinterdet as target ON target.rowid = t.intervention_line_id";
			}
			$sql .= " WHERE t.rowid IN (".$db->sanitize(implode(',', array_map('intval', $selectedlines))).")";

			$resql = $db->query($sql);
			$objstamped = $resql ? $db->fetch_object($resql) : null;
			if ($resql) {
				$db->free($resql);
			}
			if (!$resql || ($objstamped && $objstamped->nb > 0)) {
				if (!$resql) {
					dol_syslog('ticket/time.php could not check the already processed records of ticket '.$object->id.' : '.$db->lasterror(), LOG_ERR);
					setEventMessages($db->lasterror(), null, 'errors');
				} else {
					dol_syslog('ticket/time.php refused '.$objstamped->nb.' already processed records of ticket '.$object->id, LOG_WARNING);
					$refusedkey = ($action == 'confirm_generateinvoice' ? "TicketTimeSpentAlreadyInvoiced" : "TicketTimeSpentAlreadyInter");
					setEventMessages($langs->trans($refusedkey, $objstamped->nb), null, 'errors');
				}
				$selectedlines = array();
				$toselect = array();
				$action = '';
			}
		}
	}

	// Generate an invoice from the selected time
	if ($action == 'confirm_generateinvoice' && $permissiontoadd && isModEnabled('invoice') && $user->hasRight('facture', 'creer')) {
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

		if (!($object->fk_soc > 0)) {
			setEventMessages($langs->trans("ThirdPartyRequiredToGenerateInvoice"), null, 'errors');
		} elseif (!restrictedArea($user, 'societe', $object->fk_soc, '', '', 'rowid', 'rowid', 0, 1)) {
			setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
		} else {
			$object->fetch_thirdparty();

			$db->begin();
			$error = 0;

			$idprod = GETPOSTINT('productid');
			if ($idprod > 0 && !ticketIsSellableService($db, $idprod)) {
				$idprod = 0;
			}
			$generateinvoicemode = GETPOST('generateinvoicemode', 'alphanohtml');
			$invoicetouse = GETPOSTINT('invoiceid');

			$prodDurationHours = 1.0;
			$pu_ht = 0;
			$txtva = get_default_tva($mysoc, $object->thirdparty);
			$localtax1 = get_default_localtax($mysoc, $object->thirdparty, 1);
			$localtax2 = get_default_localtax($mysoc, $object->thirdparty, 2);

			if ($idprod > 0) {
				$tmpproduct = new Product($db);
				if ($tmpproduct->fetch($idprod) <= 0) {
					$error++;
					setEventMessages($tmpproduct->error, $tmpproduct->errors, 'errors');
				} else {
					$prodDurationHours = $tmpproduct->getProductDurationHours();
					if ($prodDurationHours < 0) {
						$error++;
						setEventMessages($tmpproduct->error, $tmpproduct->errors, 'errors');
					} elseif (!($prodDurationHours > 0)) {
						$prodDurationHours = 1.0;
					}
					$dataforprice = $tmpproduct->getSellPrice($mysoc, $object->thirdparty, 0);
					$pu_ht = empty($dataforprice['pu_ht']) ? 0 : $dataforprice['pu_ht'];
					$txtva = $dataforprice['tva_tx'];
					$localtax1 = get_localtax($txtva, 1, $object->thirdparty);
					$localtax2 = get_localtax($txtva, 2, $object->thirdparty);
				}
			}

			$tmpinvoice = new Facture($db);
			if (!$error) {
				if ($invoicetouse > 0) {
					if ($tmpinvoice->fetch($invoicetouse) <= 0) {
						$error++;
						setEventMessages($langs->trans("ErrorRecordNotFound"), null, 'errors');
					} elseif ($tmpinvoice->socid != $object->fk_soc) {
						$error++;
						setEventMessages($langs->trans("TicketInvoiceThirdPartyMismatch"), null, 'errors');
					} elseif ($tmpinvoice->entity != $conf->entity) {
						$error++;
						setEventMessages($langs->trans("TicketInvoiceOtherEntity"), null, 'errors');
					} elseif ($tmpinvoice->type != Facture::TYPE_STANDARD || $tmpinvoice->statut != Facture::STATUS_DRAFT) {
						$error++;
						setEventMessages($langs->trans("TicketInvoiceNotStandardDraft"), null, 'errors');
					}
				} else {
					$tmpinvoice->socid = $object->fk_soc;
					$tmpinvoice->date = dol_mktime(12, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));
					$tmpinvoice->fk_project = $object->fk_project;
					$tmpinvoice->cond_reglement_id = $object->thirdparty->cond_reglement_id;
					$tmpinvoice->mode_reglement_id = $object->thirdparty->mode_reglement_id;
					$tmpinvoice->fk_account = $object->thirdparty->fk_account;
					if ($tmpinvoice->create($user) <= 0) {
						$error++;
						setEventMessages($tmpinvoice->error, $tmpinvoice->errors, 'errors');
					}
				}
			}

			// Each group keeps the exact rowids it aggregates: only those are stamped with its line
			$groups = array();
			if (!$error) {
				foreach ($selectedlines as $selectedline) {
					$line = new TimeSpent($db);
					$resfetchline = $line->fetch($selectedline);
					if ($resfetchline < 0) {
						$error++;
						setEventMessages($line->error, $line->errors, 'errors');
						break;
					} elseif ($resfetchline == 0) {
						$error++;
						setEventMessages($langs->trans("ErrorRecordNotFound"), null, 'errors');
						break;
					}
					if ($generateinvoicemode == 'onelineperuser') {
						$key = 'u'.$line->fk_user.'p'.((int) $line->fk_product);
					} elseif ($generateinvoicemode == 'onelineperperiod') {
						$key = 'l'.$line->id;
					} else {
						$key = 'p'.((int) $line->fk_product);
					}
					if (!isset($groups[$key])) {
						$groups[$key] = array('duration' => 0, 'amount' => 0, 'rowids' => array(), 'fk_user' => $line->fk_user, 'fk_product' => (int) $line->fk_product, 'note' => $line->note, 'date' => $line->element_datehour);
					}
					$groups[$key]['duration'] += (float) $line->element_duration;
					$groups[$key]['amount'] += (float) $line->element_duration * (float) $line->thm;
					$groups[$key]['rowids'][] = (int) $line->id;
				}
			}

			if (!$error) {
				$fuser = new User($db);
				$productdatacache = array();
				foreach ($groups as $group) {
					$qtyhour = $group['duration'] / 3600;
					$qtyhourtext = convertSecondToTime((int) $group['duration'], 'all', getDolGlobalInt('MAIN_DURATION_OF_WORKDAY'));

					// With no product chosen, the time is sold at the rate recorded on the lines
					$pu_htline = $pu_ht;
					if ($idprod <= 0) {
						$pu_htline = $group['duration'] ? price2num($group['amount'] / $group['duration'], 'MU') : 0;
					}
					$txtvaline = $txtva;
					$localtax1line = $localtax1;
					$localtax2line = $localtax2;
					$prodDurationHoursLine = $prodDurationHours;

					// A service recorded on the time lines drives its own price, VAT and duration
					$productknown = true;
					if ($group['fk_product'] > 0 && $group['fk_product'] != $idprod) {
						if (!array_key_exists($group['fk_product'], $productdatacache)) {
							$productdatacache[$group['fk_product']] = null;
							$tmpproductline = new Product($db);
							if ($tmpproductline->fetch($group['fk_product']) <= 0) {
								// Deleted service: fall back to the rate of the records
								dol_syslog('ticket/time.php ignored unknown service '.$group['fk_product'], LOG_WARNING);
							} else {
								$durationline = $tmpproductline->getProductDurationHours();
								if ($durationline < 0) {
									$error++;
									setEventMessages($tmpproductline->error, $tmpproductline->errors, 'errors');
									break;
								}
								if (!($durationline > 0)) {
									$durationline = 1.0;
								}
								$productdatacache[$group['fk_product']] = array(
									'duration' => $durationline,
									'dataforprice' => $tmpproductline->getSellPrice($mysoc, $object->thirdparty, 0)
								);
							}
						}
						if (is_null($productdatacache[$group['fk_product']])) {
							$productknown = false;
						} else {
							$dataforpriceline = $productdatacache[$group['fk_product']]['dataforprice'];
							$prodDurationHoursLine = $productdatacache[$group['fk_product']]['duration'];
							$pu_htline = empty($dataforpriceline['pu_ht']) ? 0 : $dataforpriceline['pu_ht'];
							$txtvaline = $dataforpriceline['tva_tx'];
							$localtax1line = get_localtax($txtvaline, 1, $object->thirdparty);
							$localtax2line = get_localtax($txtvaline, 2, $object->thirdparty);
						}
					}

					if ($generateinvoicemode == 'onelineperuser') {
						$contributorname = $fuser->fetch($group['fk_user']) > 0 ? $fuser->getFullName($langs) : $langs->trans("Unknown");
						$linedesc = $langs->trans("TimeSpentForInvoice").' - '.$contributorname.' : '.$qtyhourtext;
					} elseif ($generateinvoicemode == 'onelineperperiod') {
						$linedesc = dol_print_date($group['date'], 'day').' - '.($group['note'] ? $group['note'] : $qtyhourtext);
					} else {
						$linedesc = $object->ref.' - '.$object->subject;
					}

					$fk_productline = ($group['fk_product'] > 0 && $productknown) ? $group['fk_product'] : $idprod;

					// Facture::addline() does not apply the discount of the third party by itself
					$invoicelineid = $tmpinvoice->addline($linedesc, (float) $pu_htline, round($qtyhour / $prodDurationHoursLine, 2), (float) $txtvaline, (float) $localtax1line, (float) $localtax2line, ($fk_productline > 0 ? $fk_productline : 0), (float) $object->thirdparty->remise_percent, '', '', 0, 0, 0, 'HT', 0, Product::TYPE_SERVICE);
					if ($invoicelineid <= 0) {
						$error++;
						setEventMessages($tmpinvoice->error, $tmpinvoice->errors, 'errors');
						break;
					}

					// The condition on the stamp makes the check done before the generation atomic
					$sql = "UPDATE ".$db->prefix()."element_time";
					$sql .= " SET invoice_id = ".((int) $tmpinvoice->id).", invoice_line_id = ".((int) $invoicelineid);
					$sql .= " WHERE rowid IN (".$db->sanitize(implode(',', array_map('intval', $group['rowids']))).")";
					$sql .= " AND (invoice_id IS NULL OR invoice_id = 0 OR NOT EXISTS (SELECT f.rowid FROM ".$db->prefix()."facture as f WHERE ".$db->prefix()."element_time.invoice_id = f.rowid))";
					$resqlstamp = $db->query($sql);
					if (!$resqlstamp) {
						$error++;
						setEventMessages($db->lasterror(), null, 'errors');
						break;
					}
					$nbstamped = $db->affected_rows($resqlstamp);
					if ($nbstamped != count($group['rowids'])) {
						$error++;
						dol_syslog('ticket/time.php records of ticket '.$object->id.' were invoiced meanwhile', LOG_WARNING);
						setEventMessages($langs->trans("TicketTimeSpentAlreadyInvoiced", count($group['rowids']) - $nbstamped), null, 'errors');
						break;
					}
				}
			}

			if (!$error && $tmpinvoice->id > 0) {
				$sql = "SELECT COUNT(rowid) as nb FROM ".$db->prefix()."element_element";
				$sql .= " WHERE sourcetype = 'ticket' AND fk_source = ".((int) $object->id);
				$sql .= " AND targettype = 'facture' AND fk_target = ".((int) $tmpinvoice->id);
				$resql = $db->query($sql);
				if (!$resql) {
					$error++;
					setEventMessages($db->lasterror(), null, 'errors');
				} else {
					$objlink = $db->fetch_object($resql);
					$db->free($resql);
					if (!($objlink && $objlink->nb > 0) && $tmpinvoice->add_object_linked('ticket', $object->id) <= 0) {
						$error++;
						setEventMessages($tmpinvoice->error, $tmpinvoice->errors, 'errors');
					}
				}
			}

			if (!$error) {
				$db->commit();
				setEventMessages($langs->trans("InvoiceGeneratedFromTimeSpent", $tmpinvoice->getNomUrl(0)), null, 'mesgs');
				header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
				exit;
			} else {
				$db->rollback();
			}
		}
	}

	// Generate an intervention from the selected time
	if ($action == 'confirm_generateinter' && $permissiontoadd && isModEnabled('intervention') && $user->hasRight('ficheinter', 'creer')) {
		if (!($object->fk_soc > 0)) {
			setEventMessages($langs->trans("ThirdPartyRequiredToGenerateIntervention"), null, 'errors');
		} elseif (!restrictedArea($user, 'societe', $object->fk_soc, '', '', 'rowid', 'rowid', 0, 1)) {
			setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
		} else {
			$db->begin();
			$error = 0;

			$intertouse = GETPOSTINT('interid');
			$tmpinter = new Fichinter($db);

			$interdate = dol_mktime(12, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));

			if ($intertouse > 0) {
				if ($tmpinter->fetch($intertouse) <= 0) {
					$error++;
					setEventMessages($langs->trans("ErrorRecordNotFound"), null, 'errors');
				} elseif ($tmpinter->socid != $object->fk_soc) {
					$error++;
					setEventMessages($langs->trans("TicketInterThirdPartyMismatch"), null, 'errors');
				} elseif ($tmpinter->entity != $conf->entity) {
					$error++;
					setEventMessages($langs->trans("TicketInterOtherEntity"), null, 'errors');
				} elseif ($tmpinter->statut != Fichinter::STATUS_DRAFT) {
					// Fichinter::addline() fails with an empty error on a validated intervention
					$error++;
					setEventMessages($langs->trans("TicketInterNotDraft"), null, 'errors');
				} else {
					// element_element has a unique index: link only once
					$sql = "SELECT COUNT(rowid) as nb FROM ".$db->prefix()."element_element";
					$sql .= " WHERE sourcetype = 'ticket' AND fk_source = ".((int) $object->id);
					$sql .= " AND targettype = 'fichinter' AND fk_target = ".((int) $tmpinter->id);
					$resql = $db->query($sql);
					if (!$resql) {
						$error++;
						setEventMessages($db->lasterror(), null, 'errors');
					} else {
						$objlink = $db->fetch_object($resql);
						$db->free($resql);
						if (!($objlink && $objlink->nb > 0) && $tmpinter->add_object_linked('ticket', $object->id) <= 0) {
							$error++;
							setEventMessages($tmpinter->error, $tmpinter->errors, 'errors');
						}
					}
				}
			} else {
				$tmpinter->socid = $object->fk_soc;
				$tmpinter->fk_project = $object->fk_project;
				$tmpinter->description = $object->ref.' - '.$object->subject;
				if ($tmpinter->create($user) <= 0) {
					$error++;
					setEventMessages($tmpinter->error, $tmpinter->errors, 'errors');
				} elseif ($tmpinter->add_object_linked('ticket', $object->id) <= 0) {
					$error++;
					setEventMessages($tmpinter->error, $tmpinter->errors, 'errors');
				} elseif ($interdate > 0 && $tmpinter->setValueFrom('dateo', $interdate, '', null, 'date', '', $user) <= 0) {
					// Fichinter::create() does not write dateo
					$error++;
					setEventMessages($tmpinter->error, $tmpinter->errors, 'errors');
				}
			}

			if (!$error) {
				foreach ($selectedlines as $selectedline) {
					$line = new TimeSpent($db);
					$resfetchline = $line->fetch($selectedline);
					if ($resfetchline < 0) {
						$error++;
						setEventMessages($line->error, $line->errors, 'errors');
						break;
					} elseif ($resfetchline == 0) {
						$error++;
						setEventMessages($langs->trans("ErrorRecordNotFound"), null, 'errors');
						break;
					}
					// element_datehour is already a timestamp: no jdate() here
					$interlineid = $tmpinter->addline($user, $tmpinter->id, $object->subject.($line->note ? ' - '.$line->note : ''), (int) $line->element_datehour, (int) $line->element_duration);
					if ($interlineid <= 0) {
						$error++;
						setEventMessages($tmpinter->error, $tmpinter->errors, 'errors');
						break;
					}
					// The condition on the stamp makes the check done before the generation atomic
					$sql = "UPDATE ".$db->prefix()."element_time";
					$sql .= " SET intervention_id = ".((int) $tmpinter->id).", intervention_line_id = ".((int) $interlineid);
					$sql .= " WHERE rowid = ".((int) $line->id);
					$sql .= " AND (intervention_line_id IS NULL OR intervention_line_id = 0 OR NOT EXISTS (SELECT fd.rowid FROM ".$db->prefix()."fichinterdet as fd WHERE ".$db->prefix()."element_time.intervention_line_id = fd.rowid))";
					$resqlstamp = $db->query($sql);
					if (!$resqlstamp) {
						$error++;
						setEventMessages($db->lasterror(), null, 'errors');
						break;
					}
					if ($db->affected_rows($resqlstamp) != 1) {
						$error++;
						dol_syslog('ticket/time.php record '.$line->id.' was turned into an intervention meanwhile', LOG_WARNING);
						setEventMessages($langs->trans("TicketTimeSpentAlreadyInter", 1), null, 'errors');
						break;
					}
				}
			}

			if (!$error) {
				$db->commit();
				setEventMessages($langs->trans("InterventionGeneratedFromTimeSpent", $tmpinter->getNomUrl(0)), null, 'mesgs');
				header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
				exit;
			} else {
				$db->rollback();
			}
		}
	}

	// Delete a time spent record
	if ($action == 'confirm_deleteline' && $confirm == 'yes' && $permissiontoadd) {
		$linetodelete = new TimeSpent($db);
		$result = $linetodelete->fetch($lineid);

		if ($result < 0) {
			setEventMessages($linetodelete->error, $linetodelete->errors, 'errors');
			$error++;
		} elseif ($result == 0) {
			setEventMessages($langs->trans("TicketTimeSpentLineNotFound"), null, 'errors');
			$error++;
		} elseif (!in_array($linetodelete->fk_user, $childids) && !$permissiontomanage) {
			accessforbidden('', 0, 1);
		}

		if (!$error) {
			$result = $object->delTimeSpent($user, $linetodelete);
			if ($result > 0) {
				setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
				header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}
}


/*
 * View
 */

$title = $langs->trans("TimeSpent").' - '.$object->ref;
$help_url = 'EN:Module_Ticket|FR:Module_Ticket_FR';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-ticket page-card_time');

$userstatic = new User($db);

$head = ticket_prepare_head($object);
print dol_get_fiche_head($head, 'ticket_time', $langs->trans('TicketCard'), -1, 'ticket');

$object->fetch_thirdparty();

$linkback = '<a href="'.DOL_URL_ROOT.'/ticket/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';
$morehtmlref .= dol_escape_htmltag($object->subject);
if (isModEnabled("societe") && $object->fk_soc > 0) {
	$morehtmlref .= '<br>';
	$morehtmlref .= img_picto($langs->trans("ThirdParty"), 'company', 'class="pictofixedwidth"');
	$morehtmlref .= $object->thirdparty->getNomUrl(1);
}
if (isModEnabled('project') && !empty($object->fk_project)) {
	$morehtmlref .= '<br>';
	$proj = new Project($db);
	if ($proj->fetch($object->fk_project) > 0) {
		$morehtmlref .= $proj->getNomUrl(1);
		if ($proj->title) {
			$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
		}
	}
}
$morehtmlref .= '</div>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

print '<div class="underbanner clearboth"></div>';

// Summary of the time spent
$summary = $object->getSummaryOfTimeSpent();

print '<div class="fichecenter">';
print '<table class="border centpercent tableforfield">';
print '<tr><td class="titlefield">'.$langs->trans("TimeSpent").'</td><td>';
if (!empty($summary['total_duration'])) {
	print convertSecondToTime((int) $summary['total_duration'], 'all', getDolGlobalInt('MAIN_DURATION_OF_WORKDAY'));
} else {
	print '<span class="opacitymedium">'.$langs->trans("TicketNoTimeSpent").'</span>';
}
print '</td></tr>';
if (isModEnabled('salaries') && !empty($summary['total_amount'])) {
	print '<tr><td>'.$langs->trans("Value").'</td><td>';
	print price($summary['total_amount']);
	if (!empty($summary['nblinesnull'])) {
		print ' <span class="opacitymedium">('.$langs->trans("TicketTimeSpentWithoutRate", $summary['nblinesnull']).')</span>';
	}
	print '</td></tr>';
}
print '</table>';
print '</div>';

print dol_get_fiche_end();

// Confirmation of the deletion of a line
if ($action == 'deleteline') {
	// Token in the url: the ajax formconfirm() confirms in GET without adding one
	print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id.'&lineid='.$lineid.'&token='.newToken(), $langs->trans("TicketDeleteTimeSpent"), $langs->trans("TicketConfirmDeleteTimeSpent"), 'confirm_deleteline', '', '', 1);
}

/*
 * List of the time spent records
 */

$sql = "SELECT t.rowid, t.element_date, t.element_datehour, t.element_date_withhour, t.element_duration,";
$sql .= " t.fk_user, t.thm, t.note, t.fk_product, t.tms,";
$sql .= " u.lastname, u.firstname, u.login, u.photo, u.statut as user_status, u.admin, u.email,";
$sql .= " prod.ref as product_ref, prod.label as product_label, prod.fk_product_type,";
$sql .= " il.total_ht as invoice_total_ht,";
$sql .= " inv.rowid as invoice_rowid, inv.ref as invoice_ref, inv.fk_statut as invoice_status, inv.type as invoice_type, inv.total_ht as invoice_amount, inv.paye as invoice_paye,";
$sql .= " inter.rowid as intervention_rowid, inter.ref as intervention_ref";
$sql .= " FROM ".$db->prefix()."element_time as t";
// Left joins: nothing purges element_time when a user is deleted
$sql .= " LEFT JOIN ".$db->prefix()."user as u ON u.rowid = t.fk_user";
$sql .= " LEFT JOIN ".$db->prefix()."product as prod ON prod.rowid = t.fk_product";
$sql .= " LEFT JOIN ".$db->prefix()."facturedet as il ON il.rowid = t.invoice_line_id";
$sql .= " LEFT JOIN ".$db->prefix()."facture as inv ON inv.rowid = il.fk_facture";
// Joined on the line, like the refusal of the generation
$sql .= " LEFT JOIN ".$db->prefix()."fichinterdet as interdet ON interdet.rowid = t.intervention_line_id";
$sql .= " LEFT JOIN ".$db->prefix()."fichinter as inter ON inter.rowid = interdet.fk_fichinter";
$sql .= " WHERE t.elementtype = '".$db->escape($object->element)."'";
$sql .= " AND t.fk_element = ".((int) $object->id);
if ($search_note) {
	$sql .= natural_search('t.note', $search_note);
}
if ($search_user > 0) {
	$sql .= " AND t.fk_user = ".((int) $search_user);
}
if ($search_date_start) {
	$sql .= " AND t.element_date >= '".$db->idate($search_date_start)."'";
}
if ($search_date_end) {
	$sql .= " AND t.element_date <= '".$db->idate($search_date_end)."'";
}

$parameters = array('id' => $object->id);
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action);
$sql .= $hookmanager->resPrint;

$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$sqlforcount = preg_replace('/^SELECT[a-zA-Z0-9\._\s\(\),=<>\:\-\']+\sFROM/Ui', 'SELECT COUNT(t.rowid) as nbtotalofrecords FROM', $sql);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
	if (($page * $limit) > $nbtotalofrecords) {
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	llxFooter();
	$db->close();
	exit;
}

$num = $db->num_rows($resql);

$param = '&id='.$object->id;
if (!empty($limit) && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($search_note) {
	$param .= '&search_note='.urlencode($search_note);
}
if ($search_user > 0) {
	$param .= '&search_user='.((int) $search_user);
}
if ($search_date_startday) {
	$param .= '&search_date_startday='.((int) $search_date_startday);
}
if ($search_date_startmonth) {
	$param .= '&search_date_startmonth='.((int) $search_date_startmonth);
}
if ($search_date_startyear) {
	$param .= '&search_date_startyear='.((int) $search_date_startyear);
}
if ($search_date_endday) {
	$param .= '&search_date_endday='.((int) $search_date_endday);
}
if ($search_date_endmonth) {
	$param .= '&search_date_endmonth='.((int) $search_date_endmonth);
}
if ($search_date_endyear) {
	$param .= '&search_date_endyear='.((int) $search_date_endyear);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.dol_escape_htmltag($optioncss).'">';
}
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
$formaction = 'list';
if ($action == 'editline') {
	$formaction = 'updateline';
} elseif ($action == 'createtime') {
	$formaction = 'addtimespent';
} elseif ($massaction == 'generateinvoice') {
	$formaction = 'confirm_generateinvoice';
} elseif ($massaction == 'generateinter') {
	$formaction = 'confirm_generateinter';
}
print '<input type="hidden" name="action" value="'.$formaction.'">';
if (in_array($massaction, array('generateinvoice', 'generateinter'))) {
	print '<input type="hidden" name="confirmmassaction" value="1">';
	print '<input type="hidden" name="massaction" value="'.$massaction.'">';
}
print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';
print '<input type="hidden" name="id" value="'.$object->id.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
if ($action == 'editline') {
	print '<input type="hidden" name="lineid" value="'.((int) $lineid).'">';
}

$newcardbutton = '';
if ($action != 'createtime' && $action != 'editline') {
	$newcardbutton = dolGetButtonTitle($langs->trans('AddTimeSpent'), '', 'fa fa-plus-circle', $_SERVER['PHP_SELF'].'?action=createtime&token='.newToken().'&id='.$object->id, '', $permissiontoadd);
}

$arrayofmassactions = array();
if ($permissiontoadd && $action != 'createtime' && $action != 'editline') {
	if (isModEnabled('invoice') && $user->hasRight('facture', 'creer')) {
		$arrayofmassactions['generateinvoice'] = img_picto('', 'bill', 'class="pictofixedwidth"').$langs->trans("GenerateBill");
	}
	if (isModEnabled('intervention') && $user->hasRight('ficheinter', 'creer')) {
		$arrayofmassactions['generateinter'] = img_picto('', 'intervention', 'class="pictofixedwidth"').$langs->trans("GenerateInter");
	}
}
if (in_array($massaction, array('generateinvoice', 'generateinter'))) {
	$arrayofmassactions = array();
}
$massactionbutton = count($arrayofmassactions) ? $form->selectMassAction('', $arrayofmassactions) : '';

$listtitle = $langs->trans("TicketTimeSpentList");
print_barre_liste($listtitle, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'clock', 0, $newcardbutton, '', $limit, 0, 0, 1);

// Confirmation form of the mass actions. llx_ticket.fk_soc is 0, not NULL, without third party.
if ($massaction == 'generateinvoice') {
	if ($object->fk_soc > 0) {
		print '<table class="noborder centpercent">';
		print '<tr><td class="titlefield">'.$langs->trans('DateInvoice').'</td><td>';
		print $form->selectDate('', '', 0, 0, 0, '', 1, 1);
		print '</td></tr>';
		print '<tr><td>'.$langs->trans('TicketTimeSpentInvoiceMode').'</td><td>';
		print $form->selectarray('generateinvoicemode', array(
			'onelineperticket' => $langs->trans('TicketTimeSpentOneLinePerTicket'),
			'onelineperuser' => $langs->trans('OneLinePerUser'),
			'onelineperperiod' => $langs->trans('OneLinePerTimeSpentLine')
		), 'onelineperticket', 0, 0, 0, '', 1);
		print '</td></tr>';
		if (isModEnabled("service")) {
			print '<tr><td>'.$langs->trans('Service').'</td><td>';
			print $form->select_produits(0, 'productid', 1, 0, 0, 1, 2, '', 1, array(), 0, '1', 0, 'maxwidth500');
			print '</td></tr>';
		}
		print '<tr><td>'.$langs->trans('InvoiceToUse').'</td><td>';
		print ticketSelectDraftInvoiceOfThirdparty($db, $langs, (int) $object->fk_soc);
		print '</td></tr>';
		print '</table>';
		print '<div class="center">';
		print '<input type="submit" class="button" name="createbill" value="'.$langs->trans('GenerateBill').'">&nbsp;';
		print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans('Cancel').'">';
		print '</div><br>';
	} else {
		print '<div class="warning">'.$langs->trans("ThirdPartyRequiredToGenerateInvoice").'</div>';
		print '<div class="center"><input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans('Cancel').'"></div>';
	}
} elseif ($massaction == 'generateinter') {
	if ($object->fk_soc > 0) {
		$forminter = new FormIntervention($db);
		print '<table class="noborder centpercent">';
		print '<tr><td class="titlefield">'.$langs->trans('Date').'</td><td>';
		print $form->selectDate('', '', 0, 0, 0, '', 1, 1);
		print '</td></tr>';
		print '<tr><td>'.img_picto('', 'intervention', 'class="pictofixedwidth"').$langs->trans('InterToUse').'</td><td>';
		print $forminter->select_interventions((int) $object->fk_soc, 0, 'interid', 24, $langs->trans('NewInter'), true);
		print '</td></tr>';
		print '</table>';
		print '<div class="center">';
		print '<input type="submit" class="button" name="createinter" value="'.$langs->trans('GenerateInter').'">&nbsp;';
		print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans('Cancel').'">';
		print '</div><br>';
	} else {
		print '<div class="warning">'.$langs->trans("ThirdPartyRequiredToGenerateIntervention").'</div>';
		print '<div class="center"><input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans('Cancel').'"></div>';
	}
}

$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, '', '');
$selectedfields .= (($massactionbutton || $massaction) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive-no-min">';
print '<table class="tagtable nobottomiftotal liste">';

// Form to add a new record
if ($action == 'createtime' && $permissiontoadd) {
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Date").'</td>';
	print '<td>'.$langs->trans("By").'</td>';
	print '<td>'.$langs->trans("Note").'</td>';
	if (isModEnabled("service")) {
		print '<td>'.$langs->trans("Service").'</td>';
	}
	print '<td class="center">'.$langs->trans("Duration").'</td>';
	print '<td></td>';
	print '</tr>';

	print '<tr class="oddeven nohover">';
	print '<td class="maxwidthonsmartphone nowraponall">';
	print $form->selectDate((int) dol_now(), 'time', 1, 1, 0, 'timespent', 1, 0);
	print '</td>';
	print '<td class="maxwidthonsmartphone">';
	print img_picto('', 'user', 'class="pictofixedwidth"');
	print $form->select_dolusers($user->id, 'userid', 0, null, 0, '', '', (string) $object->entity, 0, 0, '', 0, '', 'maxwidth200');
	print '</td>';
	print '<td>';
	print '<input type="text" class="minwidth200 maxwidth300" name="timespent_note" value="'.dol_escape_htmltag(GETPOST('timespent_note', 'alphanohtml')).'">';
	print '</td>';
	if (isModEnabled("service")) {
		print '<td>';
		print $form->select_produits(GETPOSTINT('fk_product'), 'fk_product', 1, 0, 0, 1, 2, '', 1, array(), 0, '1', 0, 'maxwidth200');
		print '</td>';
	}
	print '<td class="center nowraponall">';
	print $form->select_duration('timespent_duration', GETPOSTINT('timespent_durationhour') * 3600 + GETPOSTINT('timespent_durationmin') * 60, 0, 'text');
	print '</td>';
	print '<td class="center">';
	print '<input type="submit" class="button buttongen small" name="save" value="'.$langs->trans("Add").'">';
	print '<br><input type="submit" class="button buttongen small button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</td>';
	print '</tr>';

	print '<tr><td colspan="'.(isModEnabled("service") ? 6 : 5).'"></td></tr>';
}

// Filter line
print '<tr class="liste_titre_filter">';
if (!empty($arrayfields['t.element_date']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowraponall">'.$langs->trans('From').' ';
	print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0);
	print '</div>';
	print '<div class="nowraponall">'.$langs->trans('to').' ';
	print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0);
	print '</div>';
	print '</td>';
}
if (!empty($arrayfields['author']['checked'])) {
	print '<td class="liste_titre">';
	print $form->select_dolusers($search_user, 'search_user', 1, null, 0, '', '', (string) $object->entity, 0, 0, '', 0, '', 'maxwidth150');
	print '</td>';
}
if (!empty($arrayfields['t.note']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat maxwidth100" name="search_note" value="'.dol_escape_htmltag($search_note).'">';
	print '</td>';
}
if (!empty($arrayfields['t.fk_product']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['t.element_duration']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['value']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['valuebilled']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['t.tms']['checked'])) {
	print '<td class="liste_titre"></td>';
}
print '<td class="liste_titre center maxwidthsearch">';
print $form->showFilterButtons();
print '</td>';
print '</tr>';

// Title line
print '<tr class="liste_titre">';
if (!empty($arrayfields['t.element_date']['checked'])) {
	print_liste_field_titre($arrayfields['t.element_date']['label'], $_SERVER['PHP_SELF'], 't.element_date,t.element_datehour,t.rowid', '', $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['author']['checked'])) {
	print_liste_field_titre($arrayfields['author']['label'], $_SERVER['PHP_SELF'], 'u.lastname', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['t.note']['checked'])) {
	print_liste_field_titre($arrayfields['t.note']['label'], $_SERVER['PHP_SELF'], 't.note', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['t.fk_product']['checked'])) {
	print_liste_field_titre($arrayfields['t.fk_product']['label'], $_SERVER['PHP_SELF'], 'prod.ref', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['t.element_duration']['checked'])) {
	print_liste_field_titre($arrayfields['t.element_duration']['label'], $_SERVER['PHP_SELF'], 't.element_duration', '', $param, '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['value']['checked'])) {
	print_liste_field_titre($arrayfields['value']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['valuebilled']['checked'])) {
	print_liste_field_titre($arrayfields['valuebilled']['label'], $_SERVER['PHP_SELF'], 't.invoice_id', '', $param, '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['t.tms']['checked'])) {
	print_liste_field_titre($arrayfields['t.tms']['label'], $_SERVER['PHP_SELF'], 't.tms', '', $param, '', $sortfield, $sortorder, 'center ');
}
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
print '</tr>';

$totalduration = 0;
$totalvalue = 0;
$i = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);

while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (!$obj) {
		break;
	}

	$caneditline = ($permissiontoadd && (in_array($obj->fk_user, $childids) || $permissiontomanage));
	$iseditedline = ($action == 'editline' && $lineid == $obj->rowid);

	print '<tr class="oddeven">';

	// Date
	if (!empty($arrayfields['t.element_date']['checked'])) {
		print '<td class="center nowraponall">';
		if ($iseditedline) {
			print $form->selectDate($db->jdate($obj->element_datehour), 'timeline', (int) $obj->element_date_withhour, (int) $obj->element_date_withhour, 0, 'timespent', 1, 0);
		} else {
			print dol_print_date($db->jdate($obj->element_datehour), ($obj->element_date_withhour ? 'dayhour' : 'day'));
		}
		print '</td>';
	}

	// Author
	if (!empty($arrayfields['author']['checked'])) {
		print '<td class="tdoverflowmax125">';
		if ($iseditedline && $permissiontomanage) {
			print $form->select_dolusers($obj->fk_user, 'userid', 0, null, 0, '', '', (string) $object->entity, 0, 0, '', 0, '', 'maxwidth150');
		} elseif (empty($obj->login)) {
			print '<span class="opacitymedium">'.dol_escape_htmltag($langs->trans("Unknown")).'</span>';
		} else {
			$userstatic->id = $obj->fk_user;
			$userstatic->lastname = $obj->lastname;
			$userstatic->firstname = $obj->firstname;
			$userstatic->login = $obj->login;
			$userstatic->photo = $obj->photo;
			$userstatic->status = $obj->user_status;
			$userstatic->admin = $obj->admin;
			$userstatic->email = $obj->email;
			print $userstatic->getNomUrl(-1);
		}
		print '</td>';
	}

	// Note
	if (!empty($arrayfields['t.note']['checked'])) {
		print '<td class="tdoverflowmax200">';
		if ($iseditedline) {
			print '<input type="text" class="minwidth100" name="timespent_note" value="'.dol_escape_htmltag($obj->note).'">';
		} else {
			print dol_escape_htmltag($obj->note);
		}
		print '</td>';
	}

	// Service
	if (!empty($arrayfields['t.fk_product']['checked'])) {
		print '<td class="tdoverflowmax125">';
		if ($iseditedline) {
			print $form->select_produits((int) $obj->fk_product, 'fk_product', 1, 0, 0, 1, 2, '', 1, array(), 0, '1', 0, 'maxwidth150');
		} elseif ($obj->fk_product > 0) {
			$productstatic = new Product($db);
			$productstatic->id = $obj->fk_product;
			$productstatic->ref = $obj->product_ref;
			$productstatic->label = $obj->product_label;
			$productstatic->type = $obj->fk_product_type;
			print $productstatic->getNomUrl(1);
		}
		print '</td>';
	}

	// Duration
	if (!empty($arrayfields['t.element_duration']['checked'])) {
		print '<td class="right nowraponall">';
		if ($iseditedline) {
			print $form->select_duration('new_duration', (int) $obj->element_duration, 0, 'text');
		} else {
			print convertSecondToTime((int) $obj->element_duration, 'allhourmin', getDolGlobalInt('MAIN_DURATION_OF_WORKDAY'));
		}
		print '</td>';
		$totalduration += (float) $obj->element_duration;
	}

	// Value
	if (!empty($arrayfields['value']['checked'])) {
		$value = (float) $obj->thm * (float) $obj->element_duration / 3600;
		print '<td class="right nowraponall">';
		if (!empty($obj->thm)) {
			print $form->textwithpicto(price($value), $langs->trans("THM").': '.price($obj->thm));
		}
		print '</td>';
		$totalvalue += $value;
	}

	// Billed
	if (!empty($arrayfields['valuebilled']['checked'])) {
		print '<td class="right nowraponall">';
		if ($obj->invoice_rowid > 0) {
			$invoicestatic = new Facture($db);
			$invoicestatic->id = $obj->invoice_rowid;
			$invoicestatic->ref = $obj->invoice_ref;
			$invoicestatic->statut = $obj->invoice_status;
			$invoicestatic->status = $obj->invoice_status;
			$invoicestatic->type = $obj->invoice_type;
			$invoicestatic->total_ht = $obj->invoice_amount;
			$invoicestatic->paye = $obj->invoice_paye;
			print $invoicestatic->getNomUrl(1);
			if ($obj->invoice_total_ht != '') {
				print '<br><span class="opacitymedium">'.price($obj->invoice_total_ht).'</span>';
			}
		} else {
			print '<span class="opacitymedium">'.$langs->trans("No").'</span>';
		}
		if ($obj->intervention_rowid > 0) {
			print '<br>'.img_picto('', 'intervention', 'class="pictofixedwidth"').dol_escape_htmltag($obj->intervention_ref);
		}
		print '</td>';
	}

	// Modification date
	if (!empty($arrayfields['t.tms']['checked'])) {
		print '<td class="center nowraponall">'.dol_print_date($db->jdate($obj->tms), 'dayhour').'</td>';
	}

	// Actions
	print '<td class="center nowraponall">';
	if ($iseditedline) {
		print '<input type="submit" class="button buttongen small" name="save" value="'.$langs->trans("Save").'">';
		print '<input type="submit" class="button buttongen small button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	} elseif ($caneditline && $action != 'createtime' && $action != 'editline') {
		print '<a class="editfielda paddingrightonly" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=editline&token='.newToken().'&lineid='.$obj->rowid.'">'.img_edit().'</a>';
		print '<a class="paddingleftonly" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=deleteline&token='.newToken().'&lineid='.$obj->rowid.'">'.img_delete().'</a>';
		// Also rendered once the mass action is chosen, so its confirmation reposts toselect[]
		if ($massactionbutton || $massaction) {
			$selected = in_array($obj->rowid, $toselect) ? 1 : 0;
			// Never disabled: a disabled checkbox is not posted, so the server refusal would not see it
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect marginleftonly" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
	}
	print '</td>';

	print '</tr>';

	$i++;
}

// Total line
if ($num > 0) {
	print '<tr class="liste_total">';
	$colspanbefore = 0;
	foreach (array('t.element_date', 'author', 't.note', 't.fk_product') as $key) {
		if (!empty($arrayfields[$key]['checked'])) {
			$colspanbefore++;
		}
	}
	print '<td colspan="'.($colspanbefore > 0 ? $colspanbefore : 1).'">'.$langs->trans("Total").'</td>';
	if (!empty($arrayfields['t.element_duration']['checked'])) {
		print '<td class="right nowraponall">'.convertSecondToTime((int) $totalduration, 'allhourmin', getDolGlobalInt('MAIN_DURATION_OF_WORKDAY')).'</td>';
	}
	if (!empty($arrayfields['value']['checked'])) {
		print '<td class="right nowraponall">'.price($totalvalue).'</td>';
	}
	if (!empty($arrayfields['valuebilled']['checked'])) {
		print '<td></td>';
	}
	if (!empty($arrayfields['t.tms']['checked'])) {
		print '<td></td>';
	}
	print '<td></td>';
	print '</tr>';
} elseif ($action != 'createtime') {
	$colspan = 1;
	foreach ($arrayfields as $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr class="oddeven"><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("TicketNoTimeSpent").'</span></td></tr>';
}

print '</table>';
print '</div>';
print '</form>';

$db->free($resql);

llxFooter();
$db->close();

/**
 * Tell whether a posted product id really is a service that can be sold
 *
 * @param	DoliDB	$db			Database handler
 * @param	int		$idprod		Id of the product to check
 * @return	bool				True if it is a sellable service of a reachable entity
 */
function ticketIsSellableService($db, int $idprod): bool
{
	if (!($idprod > 0)) {
		return false;
	}

	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

	$sql = "SELECT COUNT(p.rowid) as nb";
	$sql .= " FROM ".$db->prefix()."product as p";
	$sql .= " WHERE p.rowid = ".((int) $idprod);
	$sql .= " AND p.fk_product_type = ".((int) Product::TYPE_SERVICE);
	$sql .= " AND p.tosell = 1";
	$sql .= " AND p.entity IN (".getEntity('product').")";

	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog(__FUNCTION__.' '.$db->lasterror(), LOG_ERR);
		return false;
	}
	$obj = $db->fetch_object($resql);
	$db->free($resql);

	return (bool) ($obj && $obj->nb > 0);
}

/**
 * Build a select of the draft invoices of a third party, limited to the current entity
 *
 * @param	DoliDB		$db			Database handler
 * @param	Translate	$langs		Language object
 * @param	int			$socid		Id of the third party
 * @return	string					HTML select, empty string on SQL error
 */
function ticketSelectDraftInvoiceOfThirdparty($db, $langs, int $socid): string
{
	global $conf;

	if (!($socid > 0)) {
		return '';
	}

	$sql = "SELECT f.rowid, f.ref";
	$sql .= " FROM ".$db->prefix()."facture as f";
	$sql .= " WHERE f.fk_soc = ".((int) $socid);
	$sql .= " AND f.fk_statut = ".((int) Facture::STATUS_DRAFT);
	$sql .= " AND f.type = ".((int) Facture::TYPE_STANDARD);
	// Current entity only: the action refuses the invoices of another one
	$sql .= " AND f.entity = ".((int) $conf->entity);
	$sql .= " ORDER BY f.ref";

	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog(__FUNCTION__.' '.$db->lasterror(), LOG_ERR);
		return '';
	}

	$out = '<select class="flat maxwidth300" id="invoiceid" name="invoiceid">';
	$out .= '<option value="0">'.dol_escape_htmltag($langs->trans("NewInvoice")).'</option>';
	while ($obj = $db->fetch_object($resql)) {
		$out .= '<option value="'.((int) $obj->rowid).'">'.dol_escape_htmltag($obj->ref).'</option>';
	}
	$out .= '</select>';
	$db->free($resql);

	return $out;
}
