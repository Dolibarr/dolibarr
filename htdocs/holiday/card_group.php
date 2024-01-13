<?php
/* Copyright (C) 2011		Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2012-2016	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2016	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2017		Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2014-2017  Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2020-2021  Udo Tamm            <dev@dolibit.de>
 * Copyright (C) 2022		Anthony Berton      <anthony.berton@bb2a.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, orwrite
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
 *   	\file       htdocs/holiday/card.php
 *		\ingroup    holiday
 *		\brief      Form and file creation of paid holiday.
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/holiday.lib.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Get parameters
$action 		= GETPOST('action', 'aZ09');
$cancel 		= GETPOST('cancel', 'alpha');
$confirm 		= GETPOST('confirm', 'alpha');
$id 			= GETPOST('id', 'int');
$ref 			= GETPOST('ref', 'alpha');
$fuserid 		= (GETPOST('fuserid', 'int') ? GETPOST('fuserid', 'int') : $user->id);
$users 			=  (GETPOST('users', 'array') ? GETPOST('users', 'array') : array($user->id));
$groups 		= GETPOST('groups', 'array');
$socid 			= GETPOST('socid', 'int');
$autoValidation 	= GETPOST('autoValidation', 'int');
$AutoSendMail   = GETPOST('AutoSendMail', 'int');
// Load translation files required by the page
$langs->loadLangs(array("other", "holiday", "mails", "trips"));

$error = 0;

$now = dol_now();

$childids = $user->getAllChildIds(1);

$morefilter = '';
if (getDolGlobalString('HOLIDAY_HIDE_FOR_NON_SALARIES')) {
	$morefilter = 'AND employee = 1';
}

$object = new Holiday($db);

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if (($id > 0) || $ref) {
	$object->fetch($id, $ref);

	// Check current user can read this leave request
	$canread = 0;
	if ($user->hasRight('holiday', 'readall')) {
		$canread = 1;
	}
	if ($user->hasRight('holiday', 'read') && in_array($object->fk_user, $childids)) {
		$canread = 1;
	}
	if (!$canread) {
		accessforbidden();
	}
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('holidaycard', 'globalcard'));

$cancreate = 0;
$cancreateall = 0;
if ($user->hasRight('holiday', 'write') && in_array($fuserid, $childids)) {
	$cancreate = 1;
}
if ($user->hasRight('holiday', 'writeall')) {
	$cancreate = 1;
	$cancreateall = 1;
}

$candelete = 0;
if ($user->hasRight('holiday', 'delete')) {
	$candelete = 1;
}
if ($object->statut == Holiday::STATUS_DRAFT && $user->hasRight('holiday', 'write') && in_array($object->fk_user, $childids)) {
	$candelete = 1;
}

// Protection if external user
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'holiday', $object->id, 'holiday', '', '', 'rowid', $object->statut);


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/holiday/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/holiday/card_group.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	// Add leave request
	if ($action == 'add') {
		// If no right to create a request
		if (!$cancreate) {
			$error++;
			setEventMessages($langs->trans('CantCreateCP'), null, 'errors');
			$action = 'create';
		}

		if (!$error) {
			$users 		=  GETPOST('users', 'array');
			$groups 	=  GETPOST('groups', 'array');

			$date_debut = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'));
			$date_fin = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'));
			$date_debut_gmt = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'), 1);
			$date_fin_gmt = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'), 1);
			$starthalfday = GETPOST('starthalfday');
			$endhalfday = GETPOST('endhalfday');
			$type = GETPOST('type');

			$halfday = 0;
			if ($starthalfday == 'afternoon' && $endhalfday == 'morning') {
				$halfday = 2;
			} elseif ($starthalfday == 'afternoon') {
				$halfday = -1;
			} elseif ($endhalfday == 'morning') {
				$halfday = 1;
			}

			$approverid = GETPOST('valideur', 'int');
			$description = trim(GETPOST('description', 'restricthtml'));

			// Check that leave is for a user inside the hierarchy or advanced permission for all is set
			if (!$cancreateall) {
				if (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
					if (!$user->hasRight('holiday', 'write')) {
						$error++;
						setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
					} elseif (!in_array($fuserid, $childids)) {
						$error++;
						setEventMessages($langs->trans("UserNotInHierachy"), null, 'errors');
						$action = 'create';
					}
				} else {
					if (!$user->hasRight('holiday', 'write') && !$user->hasRight('holiday', 'writeall_advance')) {
						$error++;
						setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
					} elseif (!$user->hasRight('holiday', 'writeall_advance') && !in_array($fuserid, $childids)) {
						$error++;
						setEventMessages($langs->trans("UserNotInHierachy"), null, 'errors');
						$action = 'create';
					}
				}
			}
			// If no groups and no users
			if (empty($groups) && empty($users)) {
				setEventMessages($langs->trans("ErrorFieldRequiredUserOrGroup"), null, 'errors');
				//setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UserOrGroup")), null, 'errors');
				//setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Group")), null, 'errors');
				$error++;
				$action = 'create';
			}
			// If no type
			if ($type <= 0) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
				$error++;
				$action = 'create';
			}

			// If no start date
			if (empty($date_debut)) {
				setEventMessages($langs->trans("NoDateDebut"), null, 'errors');
				$error++;
				$action = 'create';
			}
			// If no end date
			if (empty($date_fin)) {
				setEventMessages($langs->trans("NoDateFin"), null, 'errors');
				$error++;
				$action = 'create';
			}
			// If start date after end date
			if ($date_debut > $date_fin) {
				setEventMessages($langs->trans("ErrorEndDateCP"), null, 'errors');
				$error++;
				$action = 'create';
			}

			// If there is no Business Days within request
			$nbopenedday = num_open_day($date_debut_gmt, $date_fin_gmt, 0, 1, $halfday);
			if ($nbopenedday < 0.5) {
				setEventMessages($langs->trans("ErrorDureeCP"), null, 'errors'); // No working day
				$error++;
				$action = 'create';
			}

			// If no validator designated
			if ($approverid < 1) {
				setEventMessages($langs->transnoentitiesnoconv('InvalidValidatorCP'), null, 'errors');
				$error++;
			}

			$result = 0;


			if (!$error) {
				$TusersToProcess = array();
				// usergroup  select
				// better perf on single sql
				/** GROUPS */
				$sql = ' SELECT DISTINCT u.rowid,u.lastname,u.firstname from ' . MAIN_DB_PREFIX . 'user as  u';
				$sql .= ' LEFT JOIN  ' . MAIN_DB_PREFIX . 'usergroup_user as ug on ug.fk_user = u.rowid  ';
				$sql .= ' WHERE  fk_usergroup in (' .$db->sanitize(implode(',', $groups)) . ')';
				$resql = $db->query($sql);

				if ($resql) {
					while ($obj = $db->fetch_object($resql)) {
						$TusersToProcess[$obj->rowid] = $obj->rowid;
					}
				}
				/** USERS  */
				if (is_array($users) && count($users) > 0) {
					foreach ($users as $u) {
						$TusersToProcess[$u] = $u;
					}
				}
				foreach ($TusersToProcess as $u) {
					// Check if there is already holiday for this period pour chaque user
					$verifCP = $object->verifDateHolidayCP($u, $date_debut, $date_fin, $halfday);
					if (!$verifCP) {
						//setEventMessages($langs->trans("alreadyCPexist"), null, 'errors');

						$userError = new User($db);
						$result = $userError->fetch($u);

						if ($result) {
							setEventMessages($langs->trans("UseralreadyCPexist", $userError->firstname . ' '. $userError->lastname), null, 'errors');
						} else {
							setEventMessages($langs->trans("ErrorUserFetch", $u), null, 'errors');
						}

						$error++;
						$action = 'create';
					}
				}

				if (!$error) {
					$db->begin();
					// non errors we can insert all
					foreach ($TusersToProcess as $u) {
						$object = new Holiday($db);
						$object->fk_user = $u;
						$object->description = $description;
						$object->fk_validator = $approverid;
						$object->fk_type = $type;
						$object->date_debut = $date_debut;
						$object->date_fin = $date_fin;
						$object->halfday = $halfday;

						$result = $object->create($user);

						if ($result <= 0) {
							setEventMessages($object->error, $object->errors, 'errors');
							$error++;
						} else {
							//@TODO changer le nom si validated
							if ($autoValidation) {
								$htemp = new Holiday($db);
								$htemp->fetch($result);

								$htemp->statut = Holiday::STATUS_VALIDATED;
								$resultValidated = $htemp->update($approverid);

								if ($resultValidated < 0) {
									setEventMessages($object->error, $object->errors, 'errors');
									$error++;
								}
								// we can auto send mail if we are in auto validation behavior

								if ($AutoSendMail && !$error) {
									// send a mail to the user
									$returnSendMail = sendMail($result, $cancreate, $now, $autoValidation);
									if (!empty($returnSendMail->msg)) {
										setEventMessage($returnSendMail->msg, $returnSendMail->style);
									}
								}
							}
						}
					}
				}
				// If no SQL error we redirect to the request card
				if (!$error) {
					$db->commit();
					header('Location: '.DOL_URL_ROOT.'/holiday/list.php');
					exit;
				} else {
					$db->rollback();
				}
			}
		}
	}
}



/*
 * View
 */

$form = new Form($db);
$object = new Holiday($db);

$listhalfday = array('morning'=>$langs->trans("Morning"), "afternoon"=>$langs->trans("Afternoon"));

$title = $langs->trans('Leave');
$help_url = 'EN:Module_Holiday';

llxHeader('', $title, $help_url);

if ((empty($id) && empty($ref)) || $action == 'create' || $action == 'add') {
	// If user has no permission to create a leave
	if ((in_array($fuserid, $childids) && !$user->hasRight('holiday', 'writeall')) || (!in_array($fuserid, $childids) && (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || !$user->hasRight('holiday', 'writeall_advance')))) {
		$errors[] = $langs->trans('CantCreateCP');
	} else {
		// Form to add a leave request
		print load_fiche_titre($langs->trans('MenuCollectiveAddCP'), '', 'title_hrm.png');

		// Error management
		if (GETPOST('error')) {
			switch (GETPOST('error')) {
				case 'datefin':
					$errors[] = $langs->trans('ErrorEndDateCP');
					break;
				case 'SQL_Create':
					$errors[] = $langs->trans('ErrorSQLCreateCP');
					break;
				case 'CantCreate':
					$errors[] = $langs->trans('CantCreateCP');
					break;
				case 'Valideur':
					$errors[] = $langs->trans('InvalidValidatorCP');
					break;
				case 'nodatedebut':
					$errors[] = $langs->trans('NoDateDebut');
					break;
				case 'nodatefin':
					$errors[] = $langs->trans('NoDateFin');
					break;
				case 'DureeHoliday':
					$errors[] = $langs->trans('ErrorDureeCP');
					break;
				case 'alreadyCP':
					$errors[] = $langs->trans('alreadyCPexist');
					break;
			}

			setEventMessages($errors, null, 'errors');
		}


		print '<script type="text/javascript">
		$( document ).ready(function() {

            if( $("input[name=autoValidation]").is(":checked") ){
    			$("#AutoSendMail").prop("disabled", false);
                $("#AutoSendMail").prop("checked", true);

			} else {
				$("#AutoSendMail").prop("disabled", true);
                $("#AutoSendMail").prop("checked", false);
			}

            $("input[name=autoValidation]").click( function(e) {


                if( $("input[name=autoValidation]").is(":checked") ){
					$("#AutoSendMail").prop("disabled", false);
					$("#AutoSendMail").prop("checked", true);
				} else {
					$("#AutoSendMail").prop("disabled", true);
					$("#AutoSendMail").prop("checked", false);
				}
            });



			$("input.button-save").click("submit", function(e) {
				console.log("Call valider()");
	    	    if (document.demandeCP.date_debut_.value != "")
	    	    {
		           	if(document.demandeCP.date_fin_.value != "")
		           	{
		               if(document.demandeCP.valideur.value != "-1") {
		                 return true;
		               }
		               else {
		                 alert("'.dol_escape_js($langs->transnoentities('InvalidValidatorCP')).'");
		                 return false;
		               }
		            }
		            else
		            {
		              alert("'.dol_escape_js($langs->transnoentities('NoDateFin')).'");
		              return false;
		            }
		        }
		        else
		        {
		           alert("'.dol_escape_js($langs->transnoentities('NoDateDebut')).'");
		           return false;
		        }
	       	})

	       	$("#autoValidation").change(function(){
                  if( $("input[name=autoValidation]").is(":checked") ){
    					$("#AutoSendMail").prop("disabled", false);
				} else {
					$("#AutoSendMail").prop("disabled", true);
                    $("#AutoSendMail").prop("checked", false);
				}
	       	})
		});
       </script>'."\n";


		// Formulaire de demande
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" name="demandeCP">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'" />'."\n";
		print '<input type="hidden" name="action" value="add" />'."\n";

		print dol_get_fiche_head();

		print '<table class="border centpercent">';
		print '<tbody>';

		// Groups of users
		print '<tr>';
		print '<td class="titlefield fieldrequired">';
		print $form->textwithpicto($langs->trans("groups"), $langs->trans("fusionGroupsUsers"));
		print '</td>';
		print '<td>';
		print img_picto($langs->trans("groups"), 'group', 'class="pictofixedwidth"');

		$sql =' SELECT rowid, nom from '.MAIN_DB_PREFIX.'usergroup WHERE entity IN ('.getEntity('usergroup').')';
		$resql = $db->query($sql);
		$Tgroup = array();
		while ($obj = $db->fetch_object($resql)) {
			$Tgroup[$obj->rowid] = $obj->nom;
		}

		print $form->multiselectarray('groups', $Tgroup, GETPOST('groups', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);

		print '</td>';

		// Users
		print '<tr>';
		print '<td class="titlefield fieldrequired">';
		print $form->textwithpicto($langs->trans("users"), $langs->trans("fusionGroupsUsers"));
		print '<td>';
		print img_picto($langs->trans("users"), 'user', 'class="pictofixedwidth"');

		$sql = ' SELECT u.rowid, u.lastname, u.firstname from '.MAIN_DB_PREFIX.'user as  u';
		$sql .= ' WHERE 1=1';
		$sql .= !empty($morefilter) ? $morefilter : '';

		$resql = $db->query($sql);
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$userlist[$obj->rowid] = dolGetFirstLastname($obj->firstname, $obj->lastname);
			}
		}

		print img_picto('', 'users') . $form->multiselectarray('users', $userlist, GETPOST('users', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
		print '</td>';

		// Type
		print '<tr>';
		print '<td class="fieldrequired">'.$langs->trans("Type").'</td>';
		print '<td>';
		$typeleaves = $object->getTypes(1, -1);
		$arraytypeleaves = array();
		foreach ($typeleaves as $key => $val) {
			$labeltoshow = ($langs->trans($val['code']) != $val['code'] ? $langs->trans($val['code']) : $val['label']);
			$labeltoshow .= ($val['delay'] > 0 ? ' ('.$langs->trans("NoticePeriod").': '.$val['delay'].' '.$langs->trans("days").')' : '');
			$arraytypeleaves[$val['rowid']] = $labeltoshow;
		}
		print $form->selectarray('type', $arraytypeleaves, (GETPOST('type', 'alpha') ? GETPOST('type', 'alpha') : ''), 1, 0, 0, '', 0, 0, 0, '', '', true);
		if ($user->admin) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '</td>';
		print '</tr>';

		// Date start
		print '<tr>';
		print '<td class="fieldrequired">';
		print $form->textwithpicto($langs->trans("DateDebCP"), $langs->trans("FirstDayOfHoliday"));
		print '</td>';
		print '<td>';
		// Si la demande ne vient pas de l'agenda
		if (!GETPOST('date_debut_')) {
			print $form->selectDate(-1, 'date_debut_', 0, 0, 0, '', 1, 1);
		} else {
			$tmpdate = dol_mktime(0, 0, 0, GETPOST('date_debut_month', 'int'), GETPOST('date_debut_day', 'int'), GETPOST('date_debut_year', 'int'));
			print $form->selectDate($tmpdate, 'date_debut_', 0, 0, 0, '', 1, 1);
		}
		print ' &nbsp; &nbsp; ';
		print $form->selectarray('starthalfday', $listhalfday, (GETPOST('starthalfday', 'alpha') ? GETPOST('starthalfday', 'alpha') : 'morning'));
		print '</td>';
		print '</tr>';

		// Date end
		print '<tr>';
		print '<td class="fieldrequired">';
		print $form->textwithpicto($langs->trans("DateFinCP"), $langs->trans("LastDayOfHoliday"));
		print '</td>';
		print '<td>';
		if (!GETPOST('date_fin_')) {
			print $form->selectDate(-1, 'date_fin_', 0, 0, 0, '', 1, 1);
		} else {
			$tmpdate = dol_mktime(0, 0, 0, GETPOST('date_fin_month', 'int'), GETPOST('date_fin_day', 'int'), GETPOST('date_fin_year', 'int'));
			print $form->selectDate($tmpdate, 'date_fin_', 0, 0, 0, '', 1, 1);
		}
		print ' &nbsp; &nbsp; ';
		print $form->selectarray('endhalfday', $listhalfday, (GETPOST('endhalfday', 'alpha') ? GETPOST('endhalfday', 'alpha') : 'afternoon'));
		print '</td>';
		print '</tr>';

		// Approver
		print '<tr>';
		print '<td class="fieldrequired">'.$langs->trans("ReviewedByCP").'</td>';
		print '<td>';

		$object = new Holiday($db);
		$include_users = $object->fetch_users_approver_holiday();
		if (empty($include_users)) {
			print img_warning().' '.$langs->trans("NobodyHasPermissionToValidateHolidays");
		} else {
			// Defined default approver (the forced approved of user or the supervisor if no forced value defined)
			// Note: This use will be set only if the deinfed approvr has permission to approve so is inside include_users
			$defaultselectuser = (empty($user->fk_user_holiday_validator) ? $user->fk_user : $user->fk_user_holiday_validator);
			if (getDolGlobalString('HOLIDAY_DEFAULT_VALIDATOR')) {
				$defaultselectuser = $conf->global->HOLIDAY_DEFAULT_VALIDATOR; // Can force default approver
			}
			if (GETPOST('valideur', 'int') > 0) {
				$defaultselectuser = GETPOST('valideur', 'int');
			}
			$s = $form->select_dolusers($defaultselectuser, "valideur", 1, '', 0, $include_users, '', '0,'.$conf->entity, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
			print img_picto('', 'user').$form->textwithpicto($s, $langs->trans("AnyOtherInThisListCanValidate"));
		}


		print '</td>';
		print '</tr>';

		//auto validation ON CREATE
		print '<tr><td>'.$langs->trans("AutoValidationOnCreate").'</td><td>';
		print '<input type="checkbox" id="autoValidation" name="autoValidation" value="1"'.($autoValidation ? ' checked="checked"' : '').'>';
		print '</td></tr>'."\n";


		//no auto SEND MAIL
		print '<tr><td>'.$langs->trans("AutoSendMail").'</td><td>';
		print '<input type="checkbox"  id="AutoSendMail" name="AutoSendMail" value="1"'.($AutoSendMail ? ' checked="checked"' : '').'>';
		print '</td></tr>'."\n";

		// Description
		print '<tr>';
		print '<td>'.$langs->trans("DescCP").'</td>';
		print '<td class="tdtop">';
		$doleditor = new DolEditor('description', GETPOST('description', 'restricthtml'), '', 80, 'dolibarr_notes', 'In', 0, false, isModEnabled('fckeditor'), ROWS_3, '90%');
		print $doleditor->Create(1);
		print '</td></tr>';

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

		print '</tbody>';
		print '</table>';

		print dol_get_fiche_end();

		print $form->buttonsSaveCancel("SendRequestCollectiveCP");

		print '</from>'."\n";
	}
} else {
	if ($error) {
		print '<div class="tabBar">';
		print $error;
		print '<br><br><input type="button" value="'.$langs->trans("ReturnCP").'" class="button" onclick="history.go(-1)" />';
		print '</div>';
	}
}

// End of page
llxFooter();

if (is_object($db)) {
	$db->close();
}
/**
 * send email to validator for current leave represented by (id)
 *
 * @param int		$id validator for current leave represented by (id)
 * @param int 	$cancreate flag for user right
 * @param int 	$now date
 * @param int		$autoValidation boolean flag on autovalidation
 *
 * @return stdClass
 * @throws Exception
 */
function sendMail($id, $cancreate, $now, $autoValidation)
{
	$objStd = new stdClass();
	$objStd->msg = '';
	$objStd->status = 'success';
	$objStd->error = 0;
	$objStd->style = '';

	global $db, $user, $conf, $langs;

	$object = new Holiday($db);

	$result = $object->fetch($id);

	if ($result) {
		// If draft and owner of leave
		if ($object->statut == Holiday::STATUS_VALIDATED && $cancreate) {
			$object->oldcopy = dol_clone($object, 2);

			//if ($autoValidation) $object->statut = Holiday::STATUS_VALIDATED;

			$verif = $object->validate($user);

			if ($verif > 0) {
				// To
				$destinataire = new User($db);
				$destinataire->fetch($object->fk_validator);
				$emailTo = $destinataire->email;


				if (!$emailTo) {
					dol_syslog("Expected validator has no email, so we redirect directly to finished page without sending email");

					$objStd->error++;
					$objStd->msg = $langs->trans('ErroremailTo');
					$objStd->status = 'error';
					$objStd->style="warnings";
					return $objStd;
				}

				// From
				$expediteur = new User($db);
				$expediteur->fetch($object->fk_user);
				//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
				$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

				// Subject
				$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
				if (getDolGlobalString('MAIN_APPLICATION_TITLE')) {
					$societeName = $conf->global->MAIN_APPLICATION_TITLE;
				}

				$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysToValidate");

				// Content
				$message = "<p>".$langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",</p>\n";

				$message .= "<p>".$langs->transnoentities("HolidaysToValidateBody")."</p>\n";


				// option to warn the validator in case of too short delay
				if (!getDolGlobalString('HOLIDAY_HIDE_APPROVER_ABOUT_TOO_LOW_DELAY')) {
					$delayForRequest = 0;		// TODO Set delay depending of holiday leave type
					if ($delayForRequest) {
						$nowplusdelay = dol_time_plus_duree($now, $delayForRequest, 'd');

						if ($object->date_debut < $nowplusdelay) {
							$message = "<p>".$langs->transnoentities("HolidaysToValidateDelay", $delayForRequest)."</p>\n";
						}
					}
				}

				// option to notify the validator if the balance is less than the request
				if (!getDolGlobalString('HOLIDAY_HIDE_APPROVER_ABOUT_NEGATIVE_BALANCE')) {
					$nbopenedday = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);

					if ($nbopenedday > $object->getCPforUser($object->fk_user, $object->fk_type)) {
						$message .= "<p>".$langs->transnoentities("HolidaysToValidateAlertSolde")."</p>\n";
					}
				}

				$typeleaves = $object->getTypes(1, -1);
				$labeltoshow = (($typeleaves[$object->fk_type]['code'] && $langs->trans($typeleaves[$object->fk_type]['code']) != $typeleaves[$object->fk_type]['code']) ? $langs->trans($typeleaves[$object->fk_type]['code']) : $typeleaves[$object->fk_type]['label']);

				if ($object->halfday == 2) {
					$starthalfdaykey = "Afternoon";
					$endhalfdaykey = "Morning";
				} elseif ($object->halfday == -1) {
					$starthalfdaykey = "Afternoon";
					$endhalfdaykey = "Afternoon";
				} elseif ($object->halfday == 1) {
					$starthalfdaykey = "Morning";
					$endhalfdaykey = "Morning";
				} elseif ($object->halfday == 0 || $object->halfday == 2) {
					$starthalfdaykey = "Morning";
					$endhalfdaykey = "Afternoon";
				}

				$link = dol_buildpath("/holiday/card.php", 3) . '?id='.$object->id;

				$message .= "<ul>";
				$message .= "<li>".$langs->transnoentitiesnoconv("Name")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
				$message .= "<li>".$langs->transnoentitiesnoconv("Type")." : ".(empty($labeltoshow) ? $langs->trans("TypeWasDisabledOrRemoved", $object->fk_type) : $labeltoshow)."</li>\n";
				$message .= "<li>".$langs->transnoentitiesnoconv("Period")." : ".dol_print_date($object->date_debut, 'day')." ".$langs->transnoentitiesnoconv($starthalfdaykey)." ".$langs->transnoentitiesnoconv("To")." ".dol_print_date($object->date_fin, 'day')." ".$langs->transnoentitiesnoconv($endhalfdaykey)."</li>\n";
				$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
				$message .= "</ul>\n";

				$trackid = 'leav'.$object->id;

				$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);

				// Sending the email
				$result = $mail->sendfile();

				if (!$result) {
					$objStd->error++;
					$objStd->msg = $langs->trans('ErroreSendmail');
					$objStd->style="warnings";
					$objStd->status = 'error';
				} else {
					$objStd->msg = $langs->trans('mailSended');
				}

				return $objStd;
			} else {
				$objStd->error++;
				$objStd->msg = $langs->trans('ErroreVerif');
				$objStd->status = 'error';
				$objStd->style="errors";
				return $objStd;
			}
		}
	} else {
		$objStd->error++;
		$objStd->msg = $langs->trans('ErrorloadUserOnSendingMail');
		$objStd->status = 'error';
		$objStd->style="warnings";
		return $objStd;
	}

	return $objStd;
}
