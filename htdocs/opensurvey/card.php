<?php
/* Copyright (C) 2013-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2018-2023  Frédéric France         <frederic.france@netlogic.fr>
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
 *    \file       htdocs/opensurvey/card.php
 *    \ingroup    opensurvey
 *    \brief      Page to edit survey
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT."/opensurvey/class/opensurveysondage.class.php";
require_once DOL_DOCUMENT_ROOT."/opensurvey/lib/opensurvey.lib.php";


// Security check
if (!$user->hasRight('opensurvey', 'read')) {
	accessforbidden();
}

// Initialize Variables
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');

$numsondage = '';

if (GETPOST('id')) {
	$numsondage = (string) GETPOST('id', 'alpha');
}

// Initialize objects
$object = new Opensurveysondage($db);

$result = $object->fetch(0, $numsondage);
if ($result <= 0) {
	dol_print_error($db, $object->error);
	exit;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('surveycard', 'globalcard'));

$expiredate = dol_mktime(0, 0, 0, GETPOST('expiremonth'), GETPOST('expireday'), GETPOST('expireyear'));

$permissiontoread = $user->hasRight('opensurvey', 'read');
$permissiontoadd = $user->hasRight('opensurvey', 'write');
// permission delete doesn't exists
$permissiontodelete = $user->hasRight('opensurvey', 'write');


/*
 * Actions
 */

$parameters = array('id' => $numsondage);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel) {
		$action = '';
	}

	// Delete
	if ($action == 'delete_confirm') {
		// Security check
		if (!$user->hasRight('opensurvey', 'write')) {
			accessforbidden();
		}

		$result = $object->delete($user, '', $numsondage);

		header('Location: '.dol_buildpath('/opensurvey/list.php', 1));
		exit();
	}

	// Close
	if ($action == 'close') {
		$object->status = Opensurveysondage::STATUS_CLOSED;
		$object->update($user);
	}

	// Valid or Reopend
	if ($action == 'reopen' || $action == 'validate') {
		$object->status = Opensurveysondage::STATUS_VALIDATED;
		$object->update($user);
	}

	// Update
	if ($action == 'update') {
		// Security check
		if (!$user->hasRight('opensurvey', 'write')) {
			accessforbidden();
		}

		$error = 0;

		if (!GETPOST('nouveautitre')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Title")), null, 'errors');
			$error++;
			$action = 'edit';
		}

		if (!$error) {
			$object->title = (string) GETPOST('nouveautitre', 'alphanohtml');
			$object->description = (string) GETPOST('nouveauxcommentaires', 'restricthtml');
			$object->mail_admin = (string) GETPOST('nouvelleadresse', 'alpha');
			$object->date_fin = $expiredate;
			$object->allow_comments = GETPOST('cancomment', 'aZ09') == 'on' ? 1 : 0;
			$object->allow_spy = GETPOST('canseeothersvote', 'aZ09') == 'on' ? 1 : 0;
			$object->mailsonde = GETPOST('mailsonde', 'aZ09') == 'on' ? 1 : 0;

			$res = $object->update($user);
			if ($res < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'edit';
			}
		}
	}

	// Add comment
	if (GETPOST('ajoutcomment')) {
		$error = 0;

		if (!GETPOST('comment', "alphanohtml")) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Comment")), null, 'errors');
		}
		if (!GETPOST('commentuser', "alphanohtml")) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("User")), null, 'errors');
		}

		if (!$error) {
			$comment = (string) GETPOST("comment", "alphanohtml");
			$comment_user = (string) GETPOST('commentuser', "alphanohtml");

			$resql = $object->addComment($comment, $comment_user);

			if (!$resql) {
				setEventMessages($langs->trans('ErrorInsertingComment'), null, 'errors');
			}
		}
	}

	// Delete comment
	if ($action == 'deletecomment') {
		$idcomment = GETPOSTINT('idcomment');
		if ($idcomment > 0) {
			// Security check
			if (!$user->hasRight('opensurvey', 'write')) {
				accessforbidden();
			}

			$resql = $object->deleteComment($idcomment);
		}
	}

	if ($action == 'edit') {
		// Security check
		if (!$user->hasRight('opensurvey', 'write')) {
			accessforbidden();
		}
	}
}


/*
 * View
 */

$form = new Form($db);

if ($object->fk_user_creat) {
	$userstatic = new User($db);
	$userstatic->fetch($object->fk_user_creat);
}

$title = $object->title." - ".$langs->trans('Card');
$helpurl = '';
$arrayofjs = array();
$arrayofcss = array('/opensurvey/css/style.css');
llxHeader('', $title, $helpurl, 0, 0, 0, $arrayofjs, $arrayofcss);


// Define format of choices
$toutsujet = explode(",", $object->sujet);
$listofanswers = array();
foreach ($toutsujet as $value) {
	$tmp = explode('@', $value);
	$listofanswers[] = array('label'=>$tmp[0], 'format'=>(!empty($tmp[1]) ? $tmp[1] : 'checkbox'));
}
$toutsujet = str_replace("@", "<br>", $toutsujet);
$toutsujet = str_replace("°", "'", $toutsujet);

print '<form name="updatesurvey" action="'.$_SERVER["PHP_SELF"].'?id='.$numsondage.'" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

$head = opensurvey_prepare_head($object);


print dol_get_fiche_head($head, 'general', $langs->trans("Survey"), -1, 'poll');

$morehtmlref = '';

$linkback = '<a href="'.DOL_URL_ROOT.'/opensurvey/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'id', $linkback, 1, 'id_sondage', 'id_sondage', $morehtmlref);


print '<div class="fichecenter">';

print '<div class="fichehalfleft">';
print '<div class="underbanner clearboth"></div>';
print '<table class="border tableforfield centpercent">';

// Type
$type = ($object->format == "A") ? 'classic' : 'date';
print '<tr><td class="titlefieldmax45">'.$langs->trans("Type").'</td><td>';
print img_picto('', dol_buildpath('/opensurvey/img/'.($type == 'classic' ? 'chart-32.png' : 'calendar-32.png'), 1), 'width="16"', 1);
print ' '.$langs->trans($type == 'classic' ? "TypeClassic" : "TypeDate").'</td></tr>';

// Title
print '<tr><td>';
$adresseadmin = $object->mail_admin;
print $langs->trans("Title").'</td><td>';
if ($action == 'edit') {
	print '<input type="text" name="nouveautitre" style="width: 95%" value="'.dol_escape_htmltag(dol_htmlentities($object->title)).'">';
} else {
	print dol_htmlentities($object->title);
}
print '</td></tr>';

// Description
print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td class="wordbreak">';
if ($action == 'edit') {
	$doleditor = new DolEditor('nouveauxcommentaires', $object->description, '', 120, 'dolibarr_notes', 'In', 1, 1, 1, ROWS_7, '90%');
	$doleditor->Create(0, '');
} else {
	print(dol_textishtml($object->description) ? $object->description : dol_nl2br($object->description, 1, true));
}
print '</td></tr>';

// Receive an email with each vote
print '<tr><td>'.$langs->trans('ToReceiveEMailForEachVote').'</td><td>';
if ($action == 'edit') {
	print '<input type="checkbox" name="mailsonde" '.($object->mailsonde ? 'checked="checked"' : '').'">';
} else {
	print yn($object->mailsonde);

	//If option is active and linked user does not have an email, we show a warning
	if ($object->fk_user_creat && $object->mailsonde) {
		if (!$userstatic->email) {
			print ' '.img_warning($langs->trans('NoEMail'));
		}
	}
}
print '</td></tr>';

// Users can comment
print '<tr><td>'.$langs->trans('CanComment').'</td><td>';
if ($action == 'edit') {
	print '<input type="checkbox" name="cancomment" '.($object->allow_comments ? 'checked="checked"' : '').'">';
} else {
	print yn($object->allow_comments);
}
print '</td></tr>';

// Users can see others vote
print '<tr><td>'.$langs->trans('CanSeeOthersVote').'</td><td>';
if ($action == 'edit') {
	print '<input type="checkbox" name="canseeothersvote" '.($object->allow_spy ? 'checked="checked"' : '').'">';
} else {
	print yn($object->allow_spy);
}
print '</td></tr>';

print '</table>';

print '</div>';
print '<div class="fichehalfright">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border tableforfield centpercent">';

// Expire date
print '<tr><td>'.$langs->trans('ExpireDate').'</td><td>';
if ($action == 'edit') {
	print $form->selectDate($expiredate ? $expiredate : $object->date_fin, 'expire', 0, 0, 0, '', 1, 0);
} else {
	print dol_print_date($object->date_fin, 'day');
	if ($object->date_fin && $object->date_fin < dol_now() && $object->status == Opensurveysondage::STATUS_VALIDATED) {
		print img_warning($langs->trans("Expired"));
	}
}
print '</td></tr>';

// Author
print '<tr><td>';
print $langs->trans("Author").'</td><td>';
if ($object->fk_user_creat > 0) {
	print $userstatic->getLoginUrl(-1);
} else {
	if ($action == 'edit') {
		print '<input type="text" name="nouvelleadresse" class="minwith200" value="'.$object->mail_admin.'">';
	} else {
		print dol_print_email($object->mail_admin, 0, 0, 1, 0, 1, 1);
	}
}
print '</td></tr>';

// Link
print '<tr><td>'.$langs->trans("UrlForSurvey", '').'</td><td>';

// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

$url = $urlwithroot.'/public/opensurvey/studs.php?sondage='.$object->id_sondage;
print '<input type="text" class="quatrevingtpercent" '.($action == 'edit' ? 'disabled' : '').' id="opensurveyurl" name="opensurveyurl" value="'.$url.'">';
//if ($action != 'edit') {
	print ajax_autoselect("opensurveyurl", $url, 'image');
//}

print '</td></tr>';

// Other attributes
$parameters = array();
$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>';
print '</div>';

print '</div>';
print '<div class="clearboth"></div>';

print dol_get_fiche_end();

if ($action == 'edit') {
	print $form->buttonsSaveCancel();
}

print '</form>'."\n";



// Action bar

print '<div class="tabsAction">';

if ($action != 'edit' && $user->hasRight('opensurvey', 'write')) {
	// Modify button
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&id='.urlencode($numsondage).'">'.$langs->trans("Modify").'</a>';

	if ($object->status == Opensurveysondage::STATUS_DRAFT) {
		// Validate button
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=validate&token='.newToken().'&id='.urlencode($numsondage).'">'.$langs->trans("Valid").'</a>';
	}

	if ($object->status == Opensurveysondage::STATUS_VALIDATED) {
		// Close button
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=close&token='.newToken().'&id='.urlencode($numsondage).'">'.$langs->trans("Close").'</a>';
	}
	if ($object->status == Opensurveysondage::STATUS_CLOSED) {
		// Re-Open
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=reopen&token='.newToken().'&id='.urlencode($numsondage).'">'.$langs->trans("ReOpen").'</a>';
	}

	// Delete
	print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER["PHP_SELF"].'?suppressionsondage=1&id='.urlencode($numsondage).'&action=delete&token='.newToken(), 'delete', $permissiontodelete);
}

print '</div>';

if ($action == 'delete') {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?&id='.urlencode($numsondage), $langs->trans("RemovePoll"), $langs->trans("ConfirmRemovalOfPoll", $id), 'delete_confirm', '', '', 1);
}




print '<form name="formulaire5" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="addcomment">';
print '<input type="hidden" name="id" value="'.urlencode($numsondage).'">';
print '<input type="hidden" name="page_y" value="">';

print load_fiche_titre($langs->trans("CommentsOfVoters"), '', '');

// Comment list
$comments = $object->getComments();

if (!empty($comments)) {
	foreach ($comments as $comment) {
		if ($user->hasRight('opensurvey', 'write')) {
			print '<a class="reposition" href="'.DOL_URL_ROOT.'/opensurvey/card.php?action=deletecomment&token='.newToken().'&idcomment='.((int) $comment->id_comment).'&id='.urlencode($numsondage).'"> '.img_picto('', 'delete.png', '', false, 0, 0, '', '', 0).'</a> ';
		}

		print dol_htmlentities($comment->usercomment).': '.dol_nl2br(dol_htmlentities($comment->comment))." <br>";
	}
} else {
	print '<span class="opacitymedium">'.$langs->trans("NoCommentYet").'</span><br>';
}

print '<br>';

// Add comment
if ($object->allow_comments) {
	print $langs->trans("AddACommentForPoll").'<br>';
	print '<textarea name="comment" rows="2" class="quatrevingtpercent"></textarea><br>'."\n";
	print $langs->trans("Name").': <input type="text" class="minwidth300" name="commentuser" value="'.dol_escape_htmltag($user->getFullName($langs)).'"> '."\n";
	print '<input type="submit" class="button reposition smallpaddingimp" name="ajoutcomment" value="'.dol_escape_htmltag($langs->trans("AddComment")).'"><br>'."\n";
}

print '</form>';

// End of page
llxFooter();
$db->close();
