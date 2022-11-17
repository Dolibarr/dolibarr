<?php
/* Copyright (C)    2013      Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C)    2013-2014 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C)	2015	  Marcos García		  <marcosgdf@gmail.com>
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

// Following var must be set:
// $arrayofselected = array of id selected
// $object
// $objecttmp=new Propal($db);
// $topicmail="SendSupplierProposalRef";
// $modelmail="supplier_proposal_send";
// $trackid='ord'.$object->id;

if ($massaction == 'presend_attendees') {
	$langs->load("mails");
	require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorboothattendee.class.php';
	$attendee = new ConferenceOrBoothAttendee($db);
	$listofselectedid = array();
	$listofselectedref = array();

	if (!GETPOST('cancel', 'alpha')) {
		foreach ($arrayofselected as $toselectid) {
			$result = $objecttmp->fetch($toselectid);
			if ($result > 0) {
				$attendees = $attendee->fetchAll('', '', 0, 0, array('t.fk_actioncomm'=>$objecttmp->id));
				if (is_array($attendees) && count($attendees)>0) {
					foreach ($attendees as $attmail) {
						if (!empty($attmail->email)) {
							$listofselectedid[$attmail->email] = $attmail->id;
							$listofselectedref[$attmail->id][$toselectid] = $objecttmp->ref;
						}
					}
				}
			}
		}
	}

	print '<input type="hidden" name="massaction" value="confirm_presend_attendees">';
	print '<input type="hidden" name="projectid" value="'.GETPOST('projectid', 'int').'">';

	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	$formmail = new FormMail($db);

	print dol_get_fiche_head(null, '', '');

	// Cree l'objet formulaire mail
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	$formmail = new FormMail($db);
	$formmail->withform = -1;
	$formmail->fromtype = (GETPOST('fromtype') ? GETPOST('fromtype') : (!empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE) ? $conf->global->MAIN_MAIL_DEFAULT_FROMTYPE : 'user'));

	if ($formmail->fromtype === 'user') {
		$formmail->fromid = $user->id;
	}
	$formmail->trackid = $trackid;
	if (!empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2)) { // If bit 2 is set
		include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$formmail->frommail = dolAddEmailTrackId($formmail->frommail, $trackid);
	}
	$formmail->withfrom = 1;
	$liste = $langs->trans("AllRecipientSelected", count($listofselectedid));
	$formmail->withtoreadonly = 1;

	$formmail->withoptiononeemailperrecipient = ((count($listofselectedref) == 1 && count(reset($listofselectedref)) == 1) || empty($liste)) ? 0 : ((GETPOST('oneemailperrecipient') == 'on') ? 1 : -1);

	$formmail->withto = empty($liste) ? (GETPOST('sendto', 'alpha') ?GETPOST('sendto', 'alpha') : array()) : $liste;
	$formmail->withtofree = empty($liste) ? 1 : 0;
	$formmail->withtocc = 1;
	$formmail->withtoccc = $conf->global->MAIN_EMAIL_USECCC;
	$formmail->withtopic = $langs->transnoentities($topicmail, '__REF__', '__REF_CLIENT__');
	$formmail->withfile = 0;
	// $formmail->withfile = 2; Not yet supported in mass action
	$formmail->withmaindocfile = 0; // Add a checkbox "Attach also main document"
	$formmail->withbody = 1;
	$formmail->withdeliveryreceipt = 1;
	$formmail->withcancel = 1;

	// Make substitution in email content
	$substitutionarray = getCommonSubstitutionArray($langs, 0, null, $object);

	$substitutionarray['__EMAIL__'] = $sendto;
	$substitutionarray['__CHECK_READ__'] = (is_object($object) && is_object($object->thirdparty)) ? '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.urlencode($object->thirdparty->tag).'&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" width="1" height="1" style="width:1px;height:1px" border="0"/>' : '';
	$substitutionarray['__PERSONALIZED__'] = ''; // deprecated
	$substitutionarray['__CONTACTCIVNAME__'] = '';

	$parameters = array(
		'mode' => 'formemail'
	);
	complete_substitutions_array($substitutionarray, $langs, $object, $parameters);

	// Tableau des substitutions
	$formmail->substit = $substitutionarray;

	// Tableau des parametres complementaires du post
	$formmail->param['action'] = $action;
	$formmail->param['models'] = $modelmail;
	$formmail->param['models_id'] = empty(GETPOST('modelmailselected', 'int'))?$conf->global->EVENTORGANIZATION_TEMPLATE_EMAIL_BULK_ATTENDES:GETPOST('modelmailselected', 'int');
	$formmail->param['id'] = join(',', $arrayofselected);
	// $formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;
	if (!empty($conf->global->MAILING_LIMIT_SENDBYWEB) && count($listofselectedid) > $conf->global->MAILING_LIMIT_SENDBYWEB) {
		$langs->load("errors");
		print img_warning().' '.$langs->trans('WarningNumberOfRecipientIsRestrictedInMassAction', $conf->global->MAILING_LIMIT_SENDBYWEB);
		print ' - <a href="javascript: window.history.go(-1)">'.$langs->trans("GoBack").'</a>';
		$arrayofmassactions = array();
	} else {
		print $formmail->get_form();
	}

	print dol_get_fiche_end();
}
// Allow Pre-Mass-Action hook (eg for confirmation dialog)
$parameters = array(
	'toselect' => $toselect,
	'uploaddir' => isset($uploaddir) ? $uploaddir : null
);
