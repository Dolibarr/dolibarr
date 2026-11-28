<?php
/* Copyright (C) 2026       Laurent Magnin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/datapolicy/admin/dpo.php
 * \ingroup datapolicy
 * \brief   Datapolicy DPO management page
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/datapolicy/lib/datapolicy.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array('admin', 'companies', 'users', 'errors', 'datapolicy'));

$action = GETPOST('action', 'aZ09');
$id = GETPOSTINT('id');
$backtopage = GETPOST('backtopage', 'alpha');

if (!$user->admin) {
	accessforbidden();
}

$baseUploadDir = (!empty($conf->datapolicy->multidir_output[$conf->entity]) ? $conf->datapolicy->multidir_output[$conf->entity] : $conf->datapolicy->dir_output).'/dpo';
$form = new Form($db);
$formfile = new FormFile($db);

/*
 * Actions
 */

$pdfFileFields = array('gdpr_skills_certificate_file', 'gdpr_authority_declaration_file');

if ($action === 'download_file' && $id > 0) {
	$dlField = GETPOST('field', 'aZ09');
	if (!in_array($dlField, $pdfFileFields, true)) {
		$dlField = 'gdpr_skills_certificate_file';
	}
	$resFile = $db->query("SELECT ".$dlField." AS filename FROM ".$db->prefix()."datapolicy_dpo WHERE rowid = ".((int) $id)." AND entity = ".((int) $conf->entity));
	if ($resFile && ($objF = $db->fetch_object($resFile)) && !empty($objF->filename)) {
		$safeFile = dol_sanitizeFileName((string) $objF->filename);
		$fullPath = $baseUploadDir.'/'.((int) $id).'/'.$safeFile;
		if (dol_is_file($fullPath)) {
			top_httphead('application/octet-stream');
			header('Content-Disposition: attachment; filename="'.$safeFile.'"');
			header('Content-Length: '.dol_filesize($fullPath));
			readfile($fullPath);
			exit;
		}
	}
	accessforbidden();
}

if (GETPOST('cancel', 'alpha')) {
	$action = '';
	$id = 0;
}

$stagedFiles = array('gdpr_skills_certificate_file' => '', 'gdpr_authority_declaration_file' => '');
if (($action === 'add' || $action === 'update') && !GETPOST('cancel', 'alpha')) {
	$error = 0;
	$dpoType = GETPOST('dpo_type', 'aZ09');
	if (!in_array($dpoType, array('user', 'external', 'manual'), true)) {
		$dpoType = 'user';
	}

	$fkUser = GETPOSTINT('fk_user');
	$fkSoc = GETPOSTINT('fk_soc');
	$fkSocpeople = GETPOSTINT('fk_socpeople');
	$lastname = trim(GETPOST('lastname', 'alphanohtml'));
	$firstname = trim(GETPOST('firstname', 'alphanohtml'));
	$job = trim(GETPOST('job', 'alphanohtml'));
	$email = trim(GETPOST('email', 'alphanohtml'));
	$phone = trim(GETPOST('phone', 'alphanohtml'));
	$contactFormUrl = trim(GETPOST('contact_form_url', 'alphanohtml'));
	$gdprCommitment = GETPOSTINT('gdpr_prerequisites_validated') ? 1 : 0;

	if ($dpoType === 'user') {
		$fkSoc = 0;
		$fkSocpeople = 0;
		if ($fkUser <= 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('User')), null, 'errors');
			$error++;
		} else {
			$dpoUser = new User($db);
			if ($dpoUser->fetch($fkUser) > 0) {
				$lastname = trim((string) $dpoUser->lastname);
				$firstname = trim((string) $dpoUser->firstname);
				$job = trim((string) $dpoUser->job);
				$email = trim((string) $dpoUser->email);
				$phone = trim((string) ($dpoUser->office_phone ?: $dpoUser->user_mobile));
				if ($lastname === '') {
					setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Lastname')), null, 'errors');
					$error++;
				}
				if ($firstname === '') {
					setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Firstname')), null, 'errors');
					$error++;
				}
				if ($email === '') {
					setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Email')), null, 'errors');
					$error++;
				}
				if ($phone === '') {
					setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Phone')), null, 'errors');
					$error++;
				}
			} else {
				setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
				$error++;
			}
		}
	} elseif ($dpoType === 'external') {
		$fkUser = 0;
		if ($fkSoc <= 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ThirdParty')), null, 'errors');
			$error++;
		}
		if ($fkSocpeople <= 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Contact')), null, 'errors');
			$error++;
		} elseif (!$error) {
			$dpoContact = new Contact($db);
			if ($dpoContact->fetch($fkSocpeople) > 0) {
				$dpoSoc = new Societe($db);
				if ($fkSoc > 0) {
					$dpoSoc->fetch($fkSoc);
				}
				$lastname = trim((string) $dpoContact->lastname);
				$firstname = trim((string) $dpoContact->firstname);
				$job = trim((string) $dpoContact->poste);
				$email = trim((string) $dpoContact->email) ?: trim((string) $dpoSoc->email);
				$phone = trim((string) ($dpoContact->phone_pro ?: ($dpoContact->phone_mobile ?: $dpoContact->phone_perso))) ?: trim((string) $dpoSoc->phone);
				if ($lastname === '') {
					setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Lastname')), null, 'errors');
					$error++;
				}
				if ($firstname === '') {
					setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Firstname')), null, 'errors');
					$error++;
				}
				if ($email === '') {
					setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Email')), null, 'errors');
					$error++;
				}
				if ($phone === '') {
					setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Phone')), null, 'errors');
					$error++;
				}
			} else {
				setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
				$error++;
			}
		}
	} else {
		$fkUser = 0;
		$fkSoc = 0;
		$fkSocpeople = 0;
		if ($lastname === '') {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Lastname')), null, 'errors');
			$error++;
		}
		if ($firstname === '') {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Firstname')), null, 'errors');
			$error++;
		}
		if ($email === '' || !isValidEmail($email)) {
			setEventMessages($langs->trans('ErrorBadEMail', $email), null, 'errors');
			$error++;
		}
		if ($phone === '') {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Phone')), null, 'errors');
			$error++;
		} elseif (!preg_match('/^\+?[0-9][0-9\s\.\-\(\)]{5,24}$/', $phone) || dol_strlen((string) preg_replace('/\D/', '', $phone)) < 6) {
			setEventMessages($langs->trans('ErrorRequireValidPhone'), null, 'errors');
			$error++;
		}
	}

	if ($contactFormUrl !== '' && !isValidUrl($contactFormUrl)) {
		setEventMessages($langs->trans('ErrorBadUrl', $contactFormUrl), null, 'errors');
		$error++;
	}

	$dateStart = dol_mktime(0, 0, 0, GETPOSTINT('date_startmonth'), GETPOSTINT('date_startday'), GETPOSTINT('date_startyear'));
	if (empty($dateStart)) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('DateStart')), null, 'errors');
		$error++;
	}

	$dateEnd = '';
	if (GETPOSTINT('date_endyear') > 0 && GETPOSTINT('date_endmonth') > 0 && GETPOSTINT('date_endday') > 0) {
		$dateEnd = dol_mktime(0, 0, 0, GETPOSTINT('date_endmonth'), GETPOSTINT('date_endday'), GETPOSTINT('date_endyear'));
		if (!empty($dateStart) && $dateEnd < $dateStart) {
			setEventMessages($langs->trans('ErrorDateEndBeforeDateStart'), null, 'errors');
			$error++;
		}
	}

	if (!$error && !empty($dateStart)) {
		// Prevent overlapping DPO date ranges within the same entity
		$sqlOverlap = "SELECT rowid, firstname, lastname, date_start, date_end"
			." FROM ".$db->prefix()."datapolicy_dpo"
			." WHERE entity = ".((int) $conf->entity)
			.($action === 'update' && $id > 0 ? " AND rowid <> ".((int) $id) : "")
			." AND (date_end IS NULL OR date_end >= '".$db->idate($dateStart, 'date')."')";
		if (!empty($dateEnd)) {
			$sqlOverlap .= " AND date_start <= '".$db->idate($dateEnd, 'date')."'";
		}
		$sqlOverlap .= " LIMIT 1";
		$resOverlap = $db->query($sqlOverlap);
		if ($resOverlap && ($objOv = $db->fetch_object($resOverlap))) {
			$ovPeriod = trim($objOv->firstname.' '.$objOv->lastname).' ('.dol_print_date($db->jdate($objOv->date_start), 'day').' - '.(!empty($objOv->date_end) ? dol_print_date($db->jdate($objOv->date_end), 'day') : '...').')';
			setEventMessages($langs->trans('DatapolicyDpoDateOverlapError', $ovPeriod), null, 'errors');
			$error++;
		}
	}

	$tempDir = (!empty($conf->datapolicy->multidir_temp[$conf->entity]) ? $conf->datapolicy->multidir_temp[$conf->entity] : $conf->datapolicy->dir_temp);
	if (empty($tempDir)) {
		$tempDir = DOL_DATA_ROOT.'/datapolicy/temp';
	}

	// Validate and stage uploaded PDFs early so they are preserved if another field has a validation error
	foreach ($pdfFileFields as $pdfField) {
		$stagedFiles[$pdfField] = dol_sanitizeFileName(GETPOST('staged_'.$pdfField, 'alphanohtml'));
		if (!empty($_FILES[$pdfField]['name']) && empty($_FILES[$pdfField]['error'])) {
			$safeName = dol_sanitizeFileName((string) $_FILES[$pdfField]['name']);
			$ext = dol_strtolower((string) pathinfo($safeName, PATHINFO_EXTENSION));
			$tmpHead = @file_get_contents((string) $_FILES[$pdfField]['tmp_name'], false, null, 0, 5);
			if ($ext !== 'pdf' || dol_mimetype($safeName) !== 'application/pdf' || $tmpHead !== '%PDF-') {
				setEventMessages($langs->trans('DatapolicyDpoCommitmentFilePdfOnly'), null, 'errors');
				$error++;
			} elseif (dol_mkdir($tempDir) >= 0) {
				$newStaged = 'dpo_'.$pdfField.'_'.((int) $user->id).'_'.dol_now().'_'.$safeName;
				if (dol_move_uploaded_file((string) $_FILES[$pdfField]['tmp_name'], $tempDir.'/'.$newStaged, 1, 0, (int) $_FILES[$pdfField]['error']) > 0) {
					$stagedFiles[$pdfField] = $newStaged;
				} else {
					setEventMessages($langs->trans('ErrorFileNotUploaded'), null, 'errors');
					$error++;
				}
			}
		}
	}

	if (!$error) {
		$nowSql = $db->idate(dol_now());
		$savedId = $id;
		if ($action === 'update' && $id > 0) {
			$sql = "UPDATE ".$db->prefix()."datapolicy_dpo SET"
				." dpo_type = '".$db->escape($dpoType)."',"
				." fk_user = ".($fkUser > 0 ? ((int) $fkUser) : "NULL").","
				." fk_soc = ".($fkSoc > 0 ? ((int) $fkSoc) : "NULL").","
				." fk_socpeople = ".($fkSocpeople > 0 ? ((int) $fkSocpeople) : "NULL").","
				." lastname = '".$db->escape($lastname)."',"
				." firstname = '".$db->escape($firstname)."',"
				." job = ".($job !== '' ? "'".$db->escape($job)."'" : "NULL").","
				." email = '".$db->escape($email)."',"
				." phone = '".$db->escape($phone)."',"
				." contact_form_url = ".($contactFormUrl !== '' ? "'".$db->escape($contactFormUrl)."'" : "NULL").","
				." gdpr_prerequisites_validated = ".((int) $gdprCommitment).","
				." date_start = '".$db->idate($dateStart, 'date')."',"
				." date_end = ".(!empty($dateEnd) ? "'".$db->idate($dateEnd, 'date')."'" : "NULL").","
				." fk_user_modif = ".((int) $user->id)
				." WHERE rowid = ".((int) $id)
				." AND entity = ".((int) $conf->entity);
			$resSql = $db->query($sql);
		} else {
			$sql = "INSERT INTO ".$db->prefix()."datapolicy_dpo"
				." (entity, dpo_type, fk_user, fk_soc, fk_socpeople, lastname, firstname, job, email, phone, contact_form_url, gdpr_prerequisites_validated, date_start, date_end, datec, fk_user_creat)"
				." VALUES ("
				.((int) $conf->entity).", "
				."'".$db->escape($dpoType)."', "
				.($fkUser > 0 ? ((int) $fkUser) : "NULL").", "
				.($fkSoc > 0 ? ((int) $fkSoc) : "NULL").", "
				.($fkSocpeople > 0 ? ((int) $fkSocpeople) : "NULL").", "
				."'".$db->escape($lastname)."', "
				."'".$db->escape($firstname)."', "
				.($job !== '' ? "'".$db->escape($job)."'" : "NULL").", "
				."'".$db->escape($email)."', "
				."'".$db->escape($phone)."', "
				.($contactFormUrl !== '' ? "'".$db->escape($contactFormUrl)."'" : "NULL").", "
				.((int) $gdprCommitment).", "
				."'".$db->idate($dateStart, 'date')."', "
				.(!empty($dateEnd) ? "'".$db->idate($dateEnd, 'date')."'" : "NULL").", "
				."'".$nowSql."', "
				.((int) $user->id)
				.")";
			$resSql = $db->query($sql);
			if ($resSql) {
				$savedId = (int) $db->last_insert_id($db->prefix().'datapolicy_dpo');
			}
		}

		if ($resSql && $savedId > 0) {
			// Handle optional PDF file removals and staged uploads
			foreach ($pdfFileFields as $pdfField) {
				if (GETPOSTINT('remove_'.$pdfField) > 0) {
					$resOld = $db->query("SELECT ".$pdfField." AS filename FROM ".$db->prefix()."datapolicy_dpo WHERE rowid = ".((int) $savedId)." AND entity = ".((int) $conf->entity));
					if ($resOld && ($objOld = $db->fetch_object($resOld)) && !empty($objOld->filename)) {
						$oldPath = $baseUploadDir.'/'.((int) $savedId).'/'.dol_sanitizeFileName((string) $objOld->filename);
						if (dol_is_file($oldPath)) {
							dol_delete_file($oldPath);
						}
						$db->query("UPDATE ".$db->prefix()."datapolicy_dpo SET ".$pdfField." = NULL WHERE rowid = ".((int) $savedId)." AND entity = ".((int) $conf->entity));
					}
				}

				if (!empty($stagedFiles[$pdfField]) && dol_is_file($tempDir.'/'.$stagedFiles[$pdfField])) {
					$targetDir = $baseUploadDir.'/'.((int) $savedId);
					if (dol_mkdir($targetDir) >= 0) {
						$origName = dol_sanitizeFileName((string) preg_replace('/^dpo_'.preg_quote($pdfField, '/').'_\d+_\d+_/', '', $stagedFiles[$pdfField]));
						$destFile = $targetDir.'/'.$origName;
						if (dol_move($tempDir.'/'.$stagedFiles[$pdfField], $destFile, 0, 1) >= 0) {
							$db->query("UPDATE ".$db->prefix()."datapolicy_dpo SET ".$pdfField." = '".$db->escape($origName)."' WHERE rowid = ".((int) $savedId)." AND entity = ".((int) $conf->entity));
							$stagedFiles[$pdfField] = '';
						} else {
							setEventMessages($langs->trans('ErrorFileNotUploaded'), null, 'errors');
						}
					}
				}
			}

			// Keep MAIN_INFO_GDPR synchronized with the active DPO full name
			$activeDpo = datapolicyGetActiveDpo($db);
			$syncVal = $activeDpo !== null ? trim($activeDpo->firstname.' '.$activeDpo->lastname) : '';
			dolibarr_set_const($db, 'MAIN_INFO_GDPR', $syncVal, 'chaine', 0, '', $conf->entity);
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header('Location: '.$_SERVER['PHP_SELF']);
			exit;
		} else {
			setEventMessages($db->lasterror(), null, 'errors');
		}
	} else {
		$action = ($action === 'update') ? 'edit' : '';
	}
}

if ($action === 'confirm_delete' && GETPOST('confirm', 'alpha') === 'yes' && $id > 0) {
	$oldDir = $baseUploadDir.'/'.((int) $id);
	if (dol_is_dir($oldDir)) {
		dol_delete_dir_recursive($oldDir);
	}
	$sqlDel = "DELETE FROM ".$db->prefix()."datapolicy_dpo WHERE rowid = ".((int) $id)." AND entity = ".((int) $conf->entity);
	if ($db->query($sqlDel)) {
		$activeDpo = datapolicyGetActiveDpo($db);
		$syncVal = $activeDpo !== null ? trim($activeDpo->firstname.' '.$activeDpo->lastname) : '';
		dolibarr_set_const($db, 'MAIN_INFO_GDPR', $syncVal, 'chaine', 0, '', $conf->entity);
		setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
	} else {
		setEventMessages($db->lasterror(), null, 'errors');
	}
	header('Location: '.$_SERVER['PHP_SELF']);
	exit;
}

/*
 * View
 */

$title = $langs->trans('datapolicySetup');
llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-datapolicy page-admin_dpo');

$linkback = '<a href="'.($backtopage ? dolPrintHTMLForAttributeUrl($backtopage) : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans('BackToModuleList').'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

$head = datapolicyAdminPrepareHead();
print dol_get_fiche_head($head, 'dpo', $title, -1, 'lock');

if ($action === 'delete' && $id > 0) {
	print $form->formconfirm(
		$_SERVER['PHP_SELF'].'?id='.((int) $id),
		$langs->trans('Delete'),
		$langs->trans('DatapolicyConfirmDeleteDpo'),
		'confirm_delete',
		'',
		0,
		1
	);
}

$editRow = null;
if ($action === 'edit' && $id > 0) {
	$resEdit = $db->query("SELECT rowid, dpo_type, fk_user, fk_soc, fk_socpeople, lastname, firstname, job, email, phone, contact_form_url, gdpr_prerequisites_validated, gdpr_skills_certificate_file, gdpr_authority_declaration_file, date_start, date_end FROM ".$db->prefix()."datapolicy_dpo WHERE rowid = ".((int) $id)." AND entity = ".((int) $conf->entity));
	if ($resEdit && $db->num_rows($resEdit) > 0) {
		$editRow = $db->fetch_object($resEdit);
	}
}

$formDpoType = GETPOSTISSET('dpo_type') ? GETPOST('dpo_type', 'aZ09') : ($editRow ? (string) $editRow->dpo_type : 'user');
if (!in_array($formDpoType, array('user', 'external', 'manual'), true)) {
	$formDpoType = 'user';
}
$formFkUser = GETPOSTISSET('fk_user') ? GETPOSTINT('fk_user') : ($editRow ? (int) $editRow->fk_user : 0);
$formFkSoc = GETPOSTISSET('fk_soc') ? GETPOSTINT('fk_soc') : ($editRow ? (int) $editRow->fk_soc : 0);
$formFkSocpeople = GETPOSTISSET('fk_socpeople') ? GETPOSTINT('fk_socpeople') : ($editRow ? (int) $editRow->fk_socpeople : 0);
$formLastname = GETPOSTISSET('lastname') ? GETPOST('lastname', 'alphanohtml') : ($editRow ? (string) $editRow->lastname : '');
$formFirstname = GETPOSTISSET('firstname') ? GETPOST('firstname', 'alphanohtml') : ($editRow ? (string) $editRow->firstname : '');
$formJob = GETPOSTISSET('job') ? GETPOST('job', 'alphanohtml') : ($editRow ? (string) $editRow->job : '');
$formEmail = GETPOSTISSET('email') ? GETPOST('email', 'alphanohtml') : ($editRow ? (string) $editRow->email : '');
$formPhone = GETPOSTISSET('phone') ? GETPOST('phone', 'alphanohtml') : ($editRow ? (string) $editRow->phone : '');
$formContactUrl = GETPOSTISSET('contact_form_url') ? GETPOST('contact_form_url', 'alphanohtml') : ($editRow ? (string) $editRow->contact_form_url : '');
$formCommitment = GETPOSTISSET('gdpr_prerequisites_validated') ? GETPOSTINT('gdpr_prerequisites_validated') : ($editRow ? (int) $editRow->gdpr_prerequisites_validated : 0);
$formCommitmentFile = ($editRow && !empty($editRow->gdpr_skills_certificate_file)) ? (string) $editRow->gdpr_skills_certificate_file : '';
$formCnilFile = ($editRow && !empty($editRow->gdpr_authority_declaration_file)) ? (string) $editRow->gdpr_authority_declaration_file : '';
$formDateStart = GETPOSTISSET('date_startyear')
	? dol_mktime(0, 0, 0, GETPOSTINT('date_startmonth'), GETPOSTINT('date_startday'), GETPOSTINT('date_startyear'))
	: ($editRow && !empty($editRow->date_start) ? $db->jdate($editRow->date_start) : dol_now());
$formDateEnd = GETPOSTISSET('date_endyear')
	? dol_mktime(0, 0, 0, GETPOSTINT('date_endmonth'), GETPOSTINT('date_endday'), GETPOSTINT('date_endyear'))
	: ($editRow && !empty($editRow->date_end) ? $db->jdate($editRow->date_end) : '');

$dpoTypeOptions = array(
	'user' => $langs->trans('DatapolicyDpoTypeUser'),
	'external' => $langs->trans('DatapolicyDpoTypeExternal'),
	'manual' => $langs->trans('DatapolicyDpoTypeManual'),
);

print '<form method="POST" enctype="multipart/form-data" action="'.dolPrintHTMLForAttributeUrl($_SERVER['PHP_SELF']).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="'.($editRow ? 'update' : 'add').'">';
if ($editRow) {
	print '<input type="hidden" name="id" value="'.((int) $editRow->rowid).'">';
}

print '<table class="border centpercent tableforfieldcreate">';
print '<tr class="oddeven"><td class="titlefieldcreate"><label for="dpo_type">'.$langs->trans('DatapolicyDpoType').'</label></td><td>';
print Form::selectarray('dpo_type', $dpoTypeOptions, $formDpoType, 0, 0, 0, '', 0, 0, 0, '', 'minwidth300');
print '</td></tr>';

print '<tr class="oddeven dpo-user-row"><td><span class="fieldrequired">'.$langs->trans('User').'</span></td><td>';
print img_picto('', 'user', 'class="pictofixedwidth"');
print $form->select_dolusers($formFkUser > 0 ? $formFkUser : '', 'fk_user', 1, null, 0, '', '', 'default', 0, 0, '', 0, '', 'minwidth300', 1);
print '</td></tr>';

print '<tr class="oddeven dpo-external-row"><td><span class="fieldrequired">'.$langs->trans('ThirdParty').'</span></td><td>';
print img_picto('', 'company', 'class="pictofixedwidth"');
print $form->select_company($formFkSoc > 0 ? $formFkSoc : '', 'fk_soc', '', 'SelectThirdParty', 1, 0, null, 0, 'minwidth300');
print '</td></tr>';

print '<tr class="oddeven dpo-external-row"><td><span class="fieldrequired">'.$langs->trans('Contact').'</span></td><td>';
print img_picto('', 'contact', 'class="pictofixedwidth"');
print $form->select_contact($formFkSoc > 0 ? $formFkSoc : 0, $formFkSocpeople > 0 ? $formFkSocpeople : '', 'fk_socpeople', 1, '', '', 1, 'minwidth300', true, 1);
print '</td></tr>';

print '<tr class="oddeven dpo-manual-row"><td><span class="fieldrequired"><label for="dpo_lastname">'.$langs->trans('Lastname').'</label></span></td><td>';
print '<input type="text" id="dpo_lastname" name="lastname" class="minwidth300" value="'.dolPrintHTMLForAttribute($formLastname).'">';
print '</td></tr>';

print '<tr class="oddeven dpo-manual-row"><td><span class="fieldrequired"><label for="dpo_firstname">'.$langs->trans('Firstname').'</label></span></td><td>';
print '<input type="text" id="dpo_firstname" name="firstname" class="minwidth300" value="'.dolPrintHTMLForAttribute($formFirstname).'">';
print '</td></tr>';

print '<tr class="oddeven dpo-manual-row"><td><label for="dpo_job">'.$langs->trans('DatapolicyDpoJob').'</label></td><td>';
print '<input type="text" id="dpo_job" name="job" class="minwidth300" value="'.dolPrintHTMLForAttribute($formJob).'">';
print '</td></tr>';

print '<tr class="oddeven dpo-manual-row"><td><span class="fieldrequired"><label for="dpo_email">'.$langs->trans('Email').'</label></span></td><td>';
print '<input type="email" id="dpo_email" name="email" class="minwidth300" value="'.dolPrintHTMLForAttribute($formEmail).'">';
print '</td></tr>';

print '<tr class="oddeven dpo-manual-row"><td><span class="fieldrequired"><label for="dpo_phone">'.$langs->trans('Phone').'</label></span></td><td>';
print $form->showPhoneInput($formPhone, 'phone', 0, 'object_phoning', 'maxwidth200 widthcentpercentminusx');
print '</td></tr>';

print '<tr class="oddeven"><td><label for="dpo_contact_form_url">'.$langs->trans('DatapolicyDpoContactFormUrl').'</label></td><td>';
print '<input type="url" id="dpo_contact_form_url" name="contact_form_url" class="minwidth400" placeholder="https://..." value="'.dolPrintHTMLForAttribute($formContactUrl).'">';
print '</td></tr>';

print '<tr class="oddeven"><td><span class="fieldrequired">'.$langs->trans('DateStart').'</span></td><td>';
print $form->selectDate($formDateStart, 'date_start', 0, 0, 0, '', 1, 1);
print '</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans('DateEnd').'</td><td>';
print $form->selectDate($formDateEnd, 'date_end', 0, 0, 1, '', 1, 0);
print '</td></tr>';

$renderPdfInputExtras = static function (string $fieldName, string $existingFile) use ($editRow, $stagedFiles, $langs): string {
	$html = ' &nbsp; <input type="file" name="'.dolPrintHTMLForAttribute($fieldName).'" accept=".pdf,application/pdf" class="flat">';
	$html .= ' <span class="opacitymedium small">('.dolPrintHTML($langs->trans('DatapolicyDpoCommitmentFilePdfHint')).')</span>';
	if (!empty($stagedFiles[$fieldName])) {
		$stagedDisplay = (string) preg_replace('/^dpo_'.preg_quote($fieldName, '/').'_\d+_\d+_/', '', $stagedFiles[$fieldName]);
		$html .= '<input type="hidden" name="staged_'.dolPrintHTMLForAttribute($fieldName).'" value="'.dolPrintHTMLForAttribute($stagedFiles[$fieldName]).'">';
		$html .= '<br><span class="small">'.img_mime($stagedDisplay, $stagedDisplay).' '.dolPrintHTML($stagedDisplay).'</span>';
	}
	if ($editRow && $existingFile !== '') {
		$dlUrl = $_SERVER['PHP_SELF'].'?action=download_file&field='.urlencode($fieldName).'&id='.((int) $editRow->rowid).'&token='.newToken();
		$html .= '<br><span class="small">';
		$html .= '<a href="'.dolPrintHTMLForAttributeUrl($dlUrl).'">'.img_mime($existingFile, $existingFile).' '.dolPrintHTML($existingFile).'</a>';
		$html .= ' &nbsp; <label><input type="checkbox" name="remove_'.dolPrintHTMLForAttribute($fieldName).'" value="1"> '.$langs->trans('Delete').'</label>';
		$html .= '</span>';
	}
	return $html;
};

print '<tr class="oddeven"><td>'.$langs->trans('DatapolicyDpoGdprCommitment').'</td><td>';
print '<label><input type="checkbox" id="gdpr_prerequisites_validated" name="gdpr_prerequisites_validated" value="1"'.($formCommitment ? ' checked' : '').'> ';
print dolPrintHTML($langs->trans('DatapolicyDpoGdprCommitmentHelp')).'</label>';
print $renderPdfInputExtras('gdpr_skills_certificate_file', $formCommitmentFile);
print '</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans('DatapolicyDpoAuthorityDeclarationFile').'</td><td>';
print $renderPdfInputExtras('gdpr_authority_declaration_file', $formCnilFile);
print '</td></tr>';
print '</table>';

print '<div class="center marginbottomonly">';
print '<input type="submit" class="button button-save" value="'.dolPrintHTMLForAttribute($editRow ? $langs->trans('Modify') : $langs->trans('Add')).'">';
if ($editRow) {
	print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="'.dolPrintHTMLForAttribute($langs->trans('Cancel')).'">';
}
print '</div>';
print '</form>';

print '<script nonce="'.getNonce().'">
jQuery(function($) {
	function toggleDpoFields() {
		var mode = $("#dpo_type").val() || "user";
		$(".dpo-user-row").toggle(mode === "user");
		$(".dpo-external-row").toggle(mode === "external");
		$(".dpo-manual-row").toggle(mode === "manual");
	}
	$("#dpo_type").on("change", toggleDpoFields);
	toggleDpoFields();
});
</script>';

// List of DPO records with native Dolibarr filters & sorting
$searchLastname = GETPOST('search_lastname', 'alpha');
$searchFirstname = GETPOST('search_firstname', 'alpha');
$searchJob = GETPOST('search_job', 'alpha');
$searchEmail = GETPOST('search_email', 'alpha');
$searchPhone = GETPOST('search_phone', 'alpha');
$searchCommitment = GETPOST('search_gdpr_prerequisites_validated', 'alpha');
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
if (empty($sortfield)) {
	$sortfield = 'date_start,rowid';
}
if (empty($sortorder)) {
	$sortorder = 'DESC,DESC';
}

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$searchLastname = '';
	$searchFirstname = '';
	$searchJob = '';
	$searchEmail = '';
	$searchPhone = '';
	$searchCommitment = '';
}

$param = '';
if ($searchLastname !== '') {
	$param .= '&search_lastname='.urlencode($searchLastname);
}
if ($searchFirstname !== '') {
	$param .= '&search_firstname='.urlencode($searchFirstname);
}
if ($searchJob !== '') {
	$param .= '&search_job='.urlencode($searchJob);
}
if ($searchEmail !== '') {
	$param .= '&search_email='.urlencode($searchEmail);
}
if ($searchPhone !== '') {
	$param .= '&search_phone='.urlencode($searchPhone);
}
if ($searchCommitment !== '' && $searchCommitment !== '-1') {
	$param .= '&search_gdpr_prerequisites_validated='.urlencode($searchCommitment);
}

print '<br>';
print '<form method="POST" action="'.dolPrintHTMLForAttributeUrl($_SERVER['PHP_SELF']).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="sortfield" value="'.dolPrintHTMLForAttribute($sortfield).'">';
print '<input type="hidden" name="sortorder" value="'.dolPrintHTMLForAttribute($sortorder).'">';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre_filter">';
print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_lastname" value="'.dolPrintHTMLForAttribute($searchLastname).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_firstname" value="'.dolPrintHTMLForAttribute($searchFirstname).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_job" value="'.dolPrintHTMLForAttribute($searchJob).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat maxwidth125" name="search_email" value="'.dolPrintHTMLForAttribute($searchEmail).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_phone" value="'.dolPrintHTMLForAttribute($searchPhone).'"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre center">'.$form->selectyesno('search_gdpr_prerequisites_validated', $searchCommitment, 1, false, 1).'</td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre center maxwidthsearch">'.$form->showFilterButtons().'</td>';
print '</tr>';

print '<tr class="liste_titre">';
print_liste_field_titre('Lastname', $_SERVER['PHP_SELF'], 'lastname', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('Firstname', $_SERVER['PHP_SELF'], 'firstname', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('DatapolicyDpoJob', $_SERVER['PHP_SELF'], 'job', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('Email', $_SERVER['PHP_SELF'], 'email', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('Phone', $_SERVER['PHP_SELF'], 'phone', '', $param, '', $sortfield, $sortorder);
print '<td>'.$langs->trans('ThirdParty').' / '.$langs->trans('User').'</td>';
print '<td>'.$langs->trans('DatapolicyDpoContactFormUrl').'</td>';
print_liste_field_titre('DatapolicyDpoGdprCommitment', $_SERVER['PHP_SELF'], 'gdpr_prerequisites_validated', '', $param, 'class="center"', $sortfield, $sortorder);
print '<td class="center">'.$langs->trans('DatapolicyDpoCommitmentFile').'</td>';
print '<td class="center">'.$langs->trans('DatapolicyDpoAuthorityDeclarationFile').'</td>';
print_liste_field_titre('DateStart', $_SERVER['PHP_SELF'], 'date_start', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('DateEnd', $_SERVER['PHP_SELF'], 'date_end', '', $param, 'class="center"', $sortfield, $sortorder);
print '<td class="right">'.$langs->trans('Action').'</td>';
print '</tr>';

$sqlList = "SELECT rowid, dpo_type, fk_user, fk_soc, fk_socpeople, lastname, firstname, job, email, phone, contact_form_url, gdpr_prerequisites_validated, gdpr_skills_certificate_file, gdpr_authority_declaration_file, date_start, date_end"
	." FROM ".$db->prefix()."datapolicy_dpo"
	." WHERE entity = ".((int) $conf->entity);
if ($searchLastname !== '') {
	$sqlList .= natural_search('lastname', $searchLastname);
}
if ($searchFirstname !== '') {
	$sqlList .= natural_search('firstname', $searchFirstname);
}
if ($searchJob !== '') {
	$sqlList .= natural_search('job', $searchJob);
}
if ($searchEmail !== '') {
	$sqlList .= natural_search('email', $searchEmail);
}
if ($searchPhone !== '') {
	$sqlList .= natural_search('phone', $searchPhone);
}
if ($searchCommitment !== '' && $searchCommitment !== '-1') {
	$sqlList .= " AND gdpr_prerequisites_validated = ".((int) $searchCommitment);
}
$sqlList .= $db->order($sortfield, $sortorder);
$resList = $db->query($sqlList);
if ($resList && $db->num_rows($resList) > 0) {
	while ($obj = $db->fetch_object($resList)) {
		$linkCell = '<span class="opacitymedium">-</span>';
		if ((int) $obj->fk_user > 0) {
			$tmpUser = new User($db);
			if ($tmpUser->fetch((int) $obj->fk_user) > 0) {
				$linkCell = $tmpUser->getNomUrl(1);
			}
		} elseif ((int) $obj->fk_soc > 0 || (int) $obj->fk_socpeople > 0) {
			$parts = array();
			if ((int) $obj->fk_soc > 0) {
				$tmpSoc = new Societe($db);
				if ($tmpSoc->fetch((int) $obj->fk_soc) > 0) {
					$parts[] = $tmpSoc->getNomUrl(1);
				}
			}
			if ((int) $obj->fk_socpeople > 0) {
				$tmpContact = new Contact($db);
				if ($tmpContact->fetch((int) $obj->fk_socpeople) > 0) {
					$parts[] = $tmpContact->getNomUrl(1);
				}
			}
			if (!empty($parts)) {
				$linkCell = implode(' / ', $parts);
			}
		}

		print '<tr class="oddeven">';
		print '<td>'.dolPrintHTML((string) $obj->lastname).'</td>';
		print '<td>'.dolPrintHTML((string) $obj->firstname).'</td>';
		print '<td>'.dolPrintHTML((string) ($obj->job ?: '-')).'</td>';
		print '<td>'.dol_print_email((string) $obj->email).'</td>';
		print '<td>'.dol_print_phone((string) $obj->phone).'</td>';
		print '<td class="nowraponall">'.$linkCell.'</td>';
		print '<td>'.(!empty($obj->contact_form_url) ? '<a href="'.dolPrintHTMLForAttributeUrl((string) $obj->contact_form_url).'" target="_blank" rel="noopener noreferrer">'.dolPrintHTML((string) $obj->contact_form_url).'</a>' : '<span class="opacitymedium">-</span>').'</td>';
		$renderPdfLinks = static function (int $rowId, string $fieldName, string $rawFile) use ($formfile, $baseUploadDir): string {
			if ($rawFile === '') {
				return '';
			}
			$safeF = dol_sanitizeFileName($rawFile);
			$dlUrl = $_SERVER['PHP_SELF'].'?action=download_file&field='.urlencode($fieldName).'&id='.$rowId.'&token='.newToken();
			$out = '<a href="'.dolPrintHTMLForAttributeUrl($dlUrl).'" title="'.dolPrintHTMLForAttribute($safeF).'">'.img_mime($safeF, $safeF).'</a>';
			$out .= ' '.$formfile->showPreview(array('name' => $safeF, 'fullname' => $baseUploadDir.'/'.$rowId.'/'.$safeF), 'datapolicy', 'dpo/'.$rowId.'/'.$safeF);
			return $out;
		};

		print '<td class="center nowraponall">'.yn((int) $obj->gdpr_prerequisites_validated).'</td>';
		print '<td class="center nowraponall">'.(!empty($obj->gdpr_skills_certificate_file) ? $renderPdfLinks((int) $obj->rowid, 'gdpr_skills_certificate_file', (string) $obj->gdpr_skills_certificate_file) : '<span class="opacitymedium">-</span>').'</td>';
		print '<td class="center nowraponall">'.(!empty($obj->gdpr_authority_declaration_file) ? $renderPdfLinks((int) $obj->rowid, 'gdpr_authority_declaration_file', (string) $obj->gdpr_authority_declaration_file) : '<span class="opacitymedium">-</span>').'</td>';
		print '<td class="center">'.dol_print_date($db->jdate($obj->date_start), 'day').'</td>';
		print '<td class="center">'.(!empty($obj->date_end) ? dol_print_date($db->jdate($obj->date_end), 'day') : '<span class="opacitymedium">-</span>').'</td>';
		print '<td class="right nowraponall">';
		print '<a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?action=edit&id='.((int) $obj->rowid).'&token='.newToken().'" title="'.dolPrintHTMLForAttribute($langs->trans('Modify')).'">'.img_edit().'</a>';
		print '<a class="paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?action=delete&id='.((int) $obj->rowid).'&token='.newToken().'" title="'.dolPrintHTMLForAttribute($langs->trans('Delete')).'">'.img_delete().'</a>';
		print '</td>';
		print '</tr>';
	}
} else {
	print '<tr class="oddeven"><td colspan="13" class="opacitymedium">'.$langs->trans('None').'</td></tr>';
}
print '</table>';
print '</div>';
print '</form>';

print dol_get_fiche_end();

llxFooter();
$db->close();
