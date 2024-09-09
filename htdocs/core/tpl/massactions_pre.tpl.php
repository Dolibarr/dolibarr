<?php
/* Copyright (C)    2013      Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C)    2013-2014 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C)	2015	  Marcos García		  <marcosgdf@gmail.com>
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

// Following var must be set:
// $action
// $massaction
// $arrayofselected = array of id selected
// $objecttmp = new MyObject($db);
// $topicmail="SendSupplierProposalRef";
// $modelmail="supplier_proposal_send";
// $trackid='ord'.$objecttmp->id;
//
// Following var can be set
// $object = Object fetched;
// $sendto
// $withmaindocfilemail
'
@phan-var-force CommonObject $objecttmp
@phan-var-force int[] $toselect
';

if (!empty($sall) || !empty($search_all)) {
	$search_all = empty($sall) ? $search_all : $sall;

	print '<input type="hidden" name="search_all" value="'.$search_all.'">';
}

if ($massaction == 'predeletedraft') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassDraftDeletion"), $langs->trans("ConfirmMassDeletionQuestion", count($toselect)), "delete", null, '', 0, 200, 500, 1);
}

if ($massaction == 'predelete') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassDeletion"), $langs->trans("ConfirmMassDeletionQuestion", count($toselect)), "delete", null, '', 0, 200, 500, 1);
}

if ($massaction == 'preclonetasks') {
	$selected = '';
	foreach (GETPOST('toselect') as $tmpselected) {
		$selected .= '&selected[]=' . $tmpselected;
	}
	$formquestion = array(
		// TODO If list of project is long and project is not on a thirdparty, the combo may be very long.
		// Solution: Allow only sameproject for cloning tasks ?
		array('type' => 'other', 'name' => 'projectid', 'label' => $langs->trans('Project') .': ', 'value' => $form->selectProjects($object->id, 'projectid', '', 0, 1, '', 0, array(), $object->socid, '1', 1, '', null, 1)),
	);
	print $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . $selected, $langs->trans('ConfirmMassClone'), '', 'clonetasks', $formquestion, '', 1, 300, 590);
}

if ($massaction == 'preaffecttag' && isModEnabled('category')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$categ = new Categorie($db);
	$categ_types = array();
	$categ_type_array = $categ->getMapList();
	foreach ($categ_type_array as $categdef) {
		// Test on $object (should be useless, we already check on $objecttmp just after)
		if (isset($object) && $categdef['obj_table'] == $object->table_element) {
			if (!array_key_exists($categdef['code'], $categ_types)) {
				$categ_types[$categdef['code']] = array('code' => $categdef['code'], 'label' => $langs->trans($categdef['obj_class']));
			}
		}
		if (isset($objecttmp) && $categdef['obj_table'] == $objecttmp->table_element) {
			if (!array_key_exists($categdef['code'], $categ_types)) {
				$categ_types[$categdef['code']] = array('code' => $categdef['code'], 'label' => $langs->trans($categdef['obj_class']));
			}
		}
	}

	$formquestion = array();
	if (!empty($categ_types)) {
		foreach ($categ_types as $categ_type) {
			$categ_arbo_tmp = $form->select_all_categories($categ_type['code'], '', 'parent', 0, 0, 3);
			$formquestion[] = array(
				'type' => 'other',
				'name' => 'affecttag_'.$categ_type['code'],
				'label' => '',
				'value' => $form->multiselectarray('contcats_'.$categ_type['code'], $categ_arbo_tmp, GETPOST('contcats_'.$categ_type['code'], 'array'), null, null, '', 0, '60%', '', '', $langs->transnoentitiesnoconv("SelectTheTagsToAssign"))
			);
		}
		$formquestion[] = array(
			'type' => 'other',
			'name' => 'affecttag_type',
			'label' => '',
			'value' => '<input type="hidden" name="affecttag_type"  id="affecttag_type" value="'.implode(",", array_keys($categ_types)).'"/>'
		);
		print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmAffectTag"), $langs->trans("ConfirmAffectTagQuestion", count($toselect)), "affecttag", $formquestion, 1, 0, 200, 500, 1);
	} else {
		setEventMessage('CategTypeNotFound');
	}
}

if ($massaction == 'preupdateprice') {
	$formquestion = array();

	$valuefield = '<div style="display: flex; align-items: center; justify-content: flex-end; padding-right: 150px">';
	$valuefield .= '<input type="number" name="pricerate" id="pricerate" min="-100" value="0" style="width: 100px; text-align: right; margin-right: 10px" />%';
	$valuefield .= '</div>';

	$formquestion[] = array(
				'type' => 'other',
				'name' => 'pricerate',
				'label' => $langs->trans("Rate"),
				'value' => $valuefield
			);

	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmUpdatePrice"), $langs->trans("ConfirmUpdatePriceQuestion", count($toselect)), "updateprice", $formquestion, 1, 0, 200, 500, 1);
}

if ($massaction == 'presetsupervisor') {
	$formquestion = array();

	$valuefield = '<div style="display: flex; align-items: center; justify-content: flex-end; padding-right: 150px">';
	$valuefield .= img_picto('', 'user').' ';
	$valuefield .= $form->select_dolusers('', 'supervisortoset', 1, $arrayofselected, 0, '', 0, $object->entity, 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');
	$valuefield .= '</div>';

	$formquestion[] = array(
				'type' => 'other',
				'name' => 'supervisortoset',
				'label' => $langs->trans("Supervisor"),
				'value' => $valuefield
			);

	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmSetSupervisor"), $langs->trans("ConfirmSetSupervisorQuestion", count($toselect)), "setsupervisor", $formquestion, 1, 0, 200, 500, 1);
}

if ($massaction == 'preaffectuser') {
	$formquestion = array();

	$valuefielduser = '<div style="display: flex; align-items: center; justify-content: flex-end; padding-right: 165px; padding-bottom: 6px; gap: 5px">';
	$valuefielduser .= img_picto('', 'user').' ';
	$valuefielduser .= $form->select_dolusers('', 'usertoaffect', 1, $arrayofselected, 0, '', 0, $object->entity, 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');
	$valuefielduser .= '</div>';

	$valuefieldprojrole = '<div style="display: flex; align-items: center; justify-content: flex-end; padding-right: 150px; padding-bottom: 6px">';
	$valuefieldprojrole .= $formcompany->selectTypeContact($object, '', 'projectrole', 'internal', 'position', 0, 'widthcentpercentminusx maxwidth300', 0);
	$valuefieldprojrole .= '</div>';

	$valuefieldtasksrole = '<div style="display: flex; align-items: center; justify-content: flex-end; padding-right: 150px">';
	$valuefieldtasksrole .= $formcompany->selectTypeContact($taskstatic, '', 'tasksrole', 'internal', 'position', 0, 'widthcentpercentminusx maxwidth300', 0);
	$valuefieldtasksrole .= '</div>';

	$formquestion[] = array(
				'type' => 'other',
				'name' => 'usertoaffect',
				'label' => $langs->trans("User"),
				'value' => $valuefielduser
			);
	$formquestion[] = array(
		'type' => 'other',
		'name' => 'projectrole',
		'label' => $langs->trans("ProjectRole"),
		'value' => $valuefieldprojrole
	);

	$formquestion[] = array(
		'type' => 'other',
		'name' => 'tasksrole',
		'label' => $langs->trans("TasksRole"),
		'value' => $valuefieldtasksrole
	);

	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmAffectUser"), $langs->trans("ConfirmAffectUserQuestion", count($toselect)), "affectuser", $formquestion, 1, 0, 200, 500, 1);
}

if ($massaction == 'presend') {
	$langs->load("mails");

	$listofselectedid = array();
	$listofselectedrecipientobjid = array();
	$listofselectedref = array();

	if (!GETPOST('cancel', 'alpha')) {
		foreach ($arrayofselected as $toselectid) {
			$result = $objecttmp->fetch($toselectid);
			if ($result > 0) {
				$listofselectedid[$toselectid] = $toselectid;
				$thirdpartyid = ($objecttmp->fk_soc ? $objecttmp->fk_soc : $objecttmp->socid);	// For proposal, order, invoice, conferenceorbooth, ...
				if (in_array($objecttmp->element, array('societe', 'conferenceorboothattendee'))) {
					$thirdpartyid = $objecttmp->id;
				} elseif ($objecttmp->element == 'contact') {
					$thirdpartyid = $objecttmp->id;
				} elseif ($objecttmp->element == 'expensereport') {
					$thirdpartyid = $objecttmp->fk_user_author;
				}
				if (empty($thirdpartyid)) {
					$thirdpartyid = 0;
				}
				if ($thirdpartyid) {
					$listofselectedrecipientobjid[$thirdpartyid] = $thirdpartyid;
				}
				$listofselectedref[$thirdpartyid][$toselectid] = $objecttmp->ref;
			}
		}
	}

	print '<input type="hidden" name="massaction" value="confirm_presend">';

	print dol_get_fiche_head(null, '', '');

	// Create mail form
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	$formmail = new FormMail($db);
	$formmail->withform = -1;
	$formmail->fromtype = (GETPOST('fromtype') ? GETPOST('fromtype') : (getDolGlobalString('MAIN_MAIL_DEFAULT_FROMTYPE') ? $conf->global->MAIN_MAIL_DEFAULT_FROMTYPE : 'user'));

	if ($formmail->fromtype === 'user') {
		$formmail->fromid = $user->id;
	}
	$formmail->trackid = $trackid;
	$formmail->withfrom = 1;
	$liste = $langs->trans("AllRecipientSelected", count($arrayofselected));
	if (count($listofselectedrecipientobjid) == 1) { // Only 1 different recipient selected, we can suggest contacts
		$liste = array();
		$thirdpartyid = array_shift($listofselectedrecipientobjid);
		if ($objecttmp->element == 'expensereport') {
			$fuser = new User($db);
			$fuser->fetch($thirdpartyid);
			$liste['thirdparty'] = $fuser->getFullName($langs)." &lt;".$fuser->email."&gt;";
		} elseif ($objecttmp->element == 'contact') {
			$fcontact = new Contact($db);
			$fcontact->fetch($thirdpartyid);
			$liste['contact'] = $fcontact->getFullName($langs)." &lt;".$fcontact->email."&gt;";
		} elseif ($objecttmp->element == 'partnership' && getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR') == 'member') {
			$fadherent = new Adherent($db);
			$fadherent->fetch($objecttmp->fk_member);
			$liste['member'] = $fadherent->getFullName($langs)." &lt;".$fadherent->email."&gt;";
		} else {
			$soc = new Societe($db);
			$soc->fetch($thirdpartyid);
			foreach ($soc->thirdparty_and_contact_email_array(1) as $key => $value) {
				$liste[$key] = $value;
			}
		}
		$formmail->withtoreadonly = 0;
	} else {
		$formmail->withtoreadonly = 1;
	}


	$formmail->withoptiononeemailperrecipient = ((count($listofselectedref) == 1 && count(reset($listofselectedref)) == 1) || empty($liste)) ? 0 : (GETPOSTINT('oneemailperrecipient') ? 1 : -1);
	if (in_array($objecttmp->element, array('conferenceorboothattendee'))) {
		$formmail->withoptiononeemailperrecipient = 0;
	}

	$formmail->withto = empty($liste) ? (GETPOST('sendto', 'alpha') ? GETPOST('sendto', 'alpha') : array()) : $liste;
	$formmail->withtofree = empty($liste) ? 1 : 0;
	$formmail->withtocc = 1;
	$formmail->withtoccc = getDolGlobalString('MAIN_EMAIL_USECCC');
	if (!empty($topicmail)) {
		$formmail->withtopic = $langs->transnoentities($topicmail, '__REF__', '__REF_CLIENT__');
	} else {
		$formmail->withtopic = 1;
	}
	if ($objecttmp->element == 'contact') {
		$formmail->withfile = 0;
		$formmail->withmaindocfile = 0; // Add a checkbox "Attach also main document"
	} else {
		$formmail->withfile = 1;    // $formmail->withfile = 2 to allow to upload files is not yet supported in mass action
		// Add a checkbox "Attach also main document"
		if (isset($withmaindocfilemail)) {
			$formmail->withmaindocfile = $withmaindocfilemail;
		} else {    // Do an automatic definition of $formmail->withmaindocfile
			$formmail->withmaindocfile = 1;
			if ($objecttmp->element != 'societe') {
				$formmail->withfile = '<span class="hideonsmartphone opacitymedium">' . $langs->trans("OnlyPDFattachmentSupported") . '</span>';
				$formmail->withmaindocfile = -1; // Add a checkbox "Attach also main document" but not checked by default
			}
		}
	}

	$formmail->withbody = 1;
	$formmail->withdeliveryreceipt = 1;
	$formmail->withcancel = 1;

	// Make substitution in email content
	$substitutionarray = getCommonSubstitutionArray($langs, 0, null, $object);

	$substitutionarray['__EMAIL__'] = $sendto;
	$substitutionarray['__CHECK_READ__'] = '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag=undefined&securitykey='.dol_hash(getDolGlobalString('MAILING_EMAIL_UNSUBSCRIBE_KEY')."-undefined", 'md5').'" width="1" height="1" style="width:1px;height:1px" border="0"/>';
	$substitutionarray['__ONLINE_PAYMENT_URL__'] = 'UrlToPayOnlineIfApplicable';
	$substitutionarray['__ONLINE_PAYMENT_TEXT_AND_URL__'] = 'TextAndUrlToPayOnlineIfApplicable';
	$substitutionarray['__THIRDPARTY_NAME__'] = '__THIRDPARTY_NAME__';
	$substitutionarray['__PROJECT_NAME__'] = '__PROJECT_NAME__';

	$parameters = array(
		'mode' => 'formemail'
	);
	complete_substitutions_array($substitutionarray, $langs, $object, $parameters);

	// Array of substitutions
	$formmail->substit = $substitutionarray;

	// Tableau des parameters complementaires du post
	$formmail->param['action'] = $action;
	$formmail->param['models'] = $modelmail;	// the filter to know which kind of template emails to show. 'none' means no template suggested.
	$formmail->param['models_id'] = GETPOSTINT('modelmailselected') ? GETPOSTINT('modelmailselected') : '-1';
	$formmail->param['id'] = implode(',', $arrayofselected);
	// $formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;
	if (getDolGlobalString('MAILING_LIMIT_SENDBYWEB') && count($listofselectedrecipientobjid) > $conf->global->MAILING_LIMIT_SENDBYWEB) {
		// Note: MAILING_LIMIT_SENDBYWEB may be forced by conf.php file and variable $dolibarr_mailing_limit_sendbyweb
		$langs->load("errors");
		print img_warning().' '.$langs->trans('WarningNumberOfRecipientIsRestrictedInMassAction', getDolGlobalString('MAILING_LIMIT_SENDBYWEB'));
		print ' - <a href="javascript: window.history.go(-1)">'.$langs->trans("GoBack").'</a>';
		$arrayofmassactions = array();
	} else {
		print $formmail->get_form();
	}

	print dol_get_fiche_end();
}

if ($massaction == 'edit_extrafields') {
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	$elementtype = $objecttmp->element;
	/** @var CommonObject $objecttmp */
	$extrafields = new ExtraFields($db);
	$keysuffix = '';
	$extrafields->fetch_name_optionals_label($elementtype);
	$extrafields_list = $extrafields->attributes[$elementtype]['label'];

	$formquestion = array();
	if (!empty($extrafields_list)) {
		$myParamExtra = $object->showOptionals($extrafields, 'create');

		foreach ($extrafields_list as $extraKey => $extraLabel) {
			$extrafields_list[$extraKey] = $langs->trans($extraLabel);
		}

		$formquestion[] = array(
			'type' => 'other',
			'value' => $form->selectarray('extrafield-key-to-update', $extrafields_list, GETPOST('extrafield-key-to-update'), 1)
		);


		$outputShowOutputFields = '<div class="extrafields-inputs">';

		foreach ($extrafields_list as $extraKey => $extraLabel) {
			$outputShowOutputFields .= '<div class="mass-action-extrafield" data-extrafield="'.$extraKey.'" style="display:none;" >';
			$outputShowOutputFields .= '<br><span>'. $langs->trans('NewValue').'</span>';
			$outputShowOutputFields .= $extrafields->showInputField($extraKey, '', '', $keysuffix, '', 0, $objecttmp->id, $objecttmp->table_element);
			$outputShowOutputFields .= '</div>';
		}
		$outputShowOutputFields .= '<script>
		jQuery(function($) {
            $("#extrafield-key-to-update").on(\'change\',function(){
            	let selectedExtrtafield = $(this).val();
            	if($(".extrafields-inputs .product_extras_"+selectedExtrtafield) != undefined){
					$(".mass-action-extrafield").hide();
					$(".mass-action-extrafield[data-extrafield=" + selectedExtrtafield + "]").show();
                }
            });
		});
		</script>';
		$outputShowOutputFields .= '</div>';



		$formquestion[] = array(
			'type' => 'other',
			'value' => $outputShowOutputFields
			);

		print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmEditExtrafield"), $langs->trans("ConfirmEditExtrafieldQuestion", count($toselect)), "confirm_edit_value_extrafields", $formquestion, 1, 0, 200, 500, 1);
	} else {
		setEventMessage($langs->trans("noExtrafields"));
	}
}

if ($massaction == 'preenable') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassEnabling"), $langs->trans("ConfirmMassEnablingQuestion", count($toselect)), "enable", null, 'yes', 0, 200, 500, 1);
}
if ($massaction == 'predisable') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassDisabling"), $langs->trans("ConfirmMassDisablingQuestion", count($toselect)), "disable", null, '', 0, 200, 500, 1);
}
if ($massaction == 'presetcommercial') {
	$formquestion = array();
	$userlist = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
	$formquestion[] = array('type' => 'other',
			'name' => 'affectedcommercial',
			'label' => $form->editfieldkey('AllocateCommercial', 'commercial_id', '', $object, 0),
			'value' => $form->multiselectarray('commercial', $userlist, null, 0, 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0, '', '', '', 1));
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmAllocateCommercial"), $langs->trans("ConfirmAllocateCommercialQuestion", count($toselect)), "affectcommercial", $formquestion, 1, 0, 200, 500, 1);
}
if ($massaction == 'unsetcommercial') {
	$formquestion = array();
	$userlist = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
	$formquestion[] = array('type' => 'other',
		'name' => 'unassigncommercial',
		'label' => $form->editfieldkey('UnallocateCommercial', 'commercial_id', '', $object, 0),
		'value' => $form->multiselectarray('commercial', $userlist, null, 0, 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0, '', '', '', 1));
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmUnallocateCommercial"), $langs->trans("ConfirmUnallocateCommercialQuestion", count($toselect)), "unassigncommercial", $formquestion, 1, 0, 200, 500, 1);
}

if ($massaction == 'preapproveleave') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassLeaveApproval"), $langs->trans("ConfirmMassLeaveApprovalQuestion", count($toselect)), "approveleave", null, 'yes', 0, 200, 500, 1);
}

// Allow Pre-Mass-Action hook (eg for confirmation dialog)
$parameters = array(
	'toselect' => isset($toselect) ? $toselect : array(),
	'uploaddir' => isset($uploaddir) ? $uploaddir : null,
	'massaction' => $massaction
);

$reshook = $hookmanager->executeHooks('doPreMassActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
} else {
	print $hookmanager->resPrint;
}
