<?php
/* Copyright (C) 2023	Solution Libre SAS	<contact@solution-libre.fr>
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
 *	\file		htdocs/core/lib/notify.lib.php
 *	\brief		Set of functions used for notifications
 *	\ingroup	core
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

/**
 * Send the notification email
 *
 * @param	string			$notifCode		Notification code
 * @param	string			$targetType		Target type (tocontactid or touserid)
 * @param	string			$to				Recipients
 * @param	string			$from			Sender
 * @param	CommonObject	$object			Related object
 * @param	string			$newRef			Related object new reference
 * @param	string			$companyName	Company name
 * @param	string			$urlWithRoot	URL with root
 * @param	Translate		$outputLangs	Object langs for output
 * @param	int				$actionId		Action Trigger ID
 * @param	?int			$contactId		Contact ID
 * @param	string			$type			Type
 * @param	string			$email			Email
 *
 * @return string The error message
 */
function notify_sendMail(
	string			$notifCode,
	string			$targetType,
	string			$to,
	string			$from,
	CommonObject	$object,
	string			$newRef,
	string			$companyName,
	string			$urlWithRoot,
	Translate		$outputLangs,
	int				$actionId,
	?int			$contactId,
	string			$type,
	string			$email
): string {
	global $conf, $db, $user;
	global $hookmanager;

	$result = '';
	$projectTitle = '';
	if (!empty($object->fk_project) && !is_object($object->project)) {
		$object->fetch_projet();
		$projectTitle = '('.$object->project->title.')';
	}

	switch ($notifCode) {
		case 'BILL_VALIDATE':
		case 'BILL_PAYED':
			$bodyKeys = [
				'BILL_VALIDATE' => 'Validated',
				'BILL_PAYED'    => 'Payed',
			];
			$link = '<a href="'.$urlWithRoot.'/compta/facture/card.php?facid='.$object->id.'&entity='.$object->entity.'">'.$newRef.'</a>';
			$dir_output = $conf->facture->dir_output."/".get_exdir(0, 0, 0, 1, $object, 'invoice');
			$objectType = 'facture';
			$mesg = $outputLangs->transnoentitiesnoconv('EMailTextInvoice' . $bodyKeys[$notifCode], $link);
			break;
		case 'ORDER_VALIDATE':
			$link = '<a href="'.$urlWithRoot.'/commande/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newRef.'</a>';
			$dir_output = $conf->commande->dir_output."/".get_exdir(0, 0, 0, 1, $object, 'commande');
			$objectType = 'order';
			$mesg = $outputLangs->transnoentitiesnoconv("EMailTextOrderValidated", $link);
			break;
		case 'PROPAL_VALIDATE':
		case 'PROPAL_CLOSE_SIGNED':
			$bodyKeys = [
				'PROPAL_VALIDATE'     => 'Validated',
				'PROPAL_CLOSE_SIGNED' => 'ClosedSigned',
			];
			$link = '<a href="'.$urlWithRoot.'/comm/propal/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newRef.'</a>';
			$dir_output = $conf->propal->multidir_output[$object->entity]."/".get_exdir(0, 0, 0, 1, $object, 'propal');
			$objectType = 'propal';
			$mesg = $outputLangs->transnoentitiesnoconv('EMailTextProposal' . $bodyKeys[$notifCode], $link);
			break;
		case 'FICHINTER_ADD_CONTACT':
		case 'FICHINTER_VALIDATE':
			$bodyKeys = [
				'FICHINTER_ADD_CONTACT' => 'AddedContact',
				'FICHINTER_VALIDATE'    => 'Validated',
			];
			$link = '<a href="'.$urlWithRoot.'/fichinter/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newRef.'</a>';
			$dir_output = $conf->ficheinter->dir_output;
			$objectType = 'ficheinter';
			$mesg = $outputLangs->transnoentitiesnoconv('EMailTextIntervention' . $bodyKeys[$notifCode], $link);
			break;
		case 'ORDER_SUPPLIER_APPROVE':
		case 'ORDER_SUPPLIER_REFUSE':
		case 'ORDER_SUPPLIER_SUBMIT':
		case 'ORDER_SUPPLIER_VALIDATE':
			$bodyKeys = [
				'ORDER_SUPPLIER_APPROVE'  => 'EMailTextOrderApproved',
				'ORDER_SUPPLIER_REFUSE'   => 'EMailTextOrderRefused',
				'ORDER_SUPPLIER_SUBMIT'   => 'EMailTextSupplierOrderSubmit',
				'ORDER_SUPPLIER_VALIDATE' => 'EMailTextOrderValidated',
			];
			$link = '<a href="'.$urlWithRoot.'/fourn/commande/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newRef.'</a>';
			$dir_output = $conf->fournisseur->commande->multidir_output[$object->entity]."/".get_exdir(0, 0, 0, 1, $object);
			$objectType = 'order_supplier';
			$mesg = $outputLangs->transnoentitiesnoconv("Hello").",\n\n";
			$mesg .= $outputLangs->transnoentitiesnoconv($bodyKeys[$notifCode] . 'By', $link, $user->getFullName($outputLangs));
			$mesg .= "\n\n".$outputLangs->transnoentitiesnoconv("Sincerely").".\n\n";
			break;
		case 'SHIPPING_VALIDATE':
			$link = '<a href="'.$urlWithRoot.'/expedition/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newRef.'</a>';
			$dir_output = $conf->expedition->dir_output."/sending/".get_exdir(0, 0, 0, 1, $object, 'shipment');
			$objectType = 'shipping';
			$mesg = $outputLangs->transnoentitiesnoconv('EMailTextExpeditionValidated', $link);
			break;
		case 'EXPENSE_REPORT_VALIDATE':
		case 'EXPENSE_REPORT_APPROVE':
			$bodyKeys = [
				'EXPENSE_REPORT_VALIDATE' => 'Validated',
				'EXPENSE_REPORT_APPROVE'  => 'Approved',
			];
			$link = '<a href="'.$urlWithRoot.'/expensereport/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newRef.'</a>';
			$dir_output = $conf->expensereport->dir_output;
			$objectType = 'expensereport';
			$mesg = $outputLangs->transnoentitiesnoconv('EMailTextExpenseReport' . $bodyKeys[$notifCode], $link);
			break;
		case 'HOLIDAY_VALIDATE':
		case 'HOLIDAY_APPROVE':
			$bodyKeys = [
				'HOLIDAY_VALIDATE' => 'Validated',
				'HOLIDAY_APPROVE'  => 'Approved',
			];
			$link = '<a href="'.$urlWithRoot.'/holiday/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newRef.'</a>';
			$dir_output = $conf->holiday->dir_output;
			$objectType = 'holiday';
			$mesg = $outputLangs->transnoentitiesnoconv('EMailTextHoliday' . $bodyKeys[$notifCode], $link);
			break;
		case 'ACTION_CREATE':
			$link = '<a href="'.$urlWithRoot.'/comm/action/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newRef.'</a>';
			$dir_output = $conf->agenda->dir_output;
			$objectType = 'action';
			$mesg = $outputLangs->transnoentitiesnoconv("EMailTextActionAdded", $link);
			break;
		default:
			$objectType = $object->element;
			$dir_output = $conf->$objectType->multidir_output[$object->entity ? $object->entity : $conf->entity]."/".get_exdir(0, 0, 0, 1, $object, $objectType);
			$mesg = $outputLangs->transnoentitiesnoconv('Notify_'.$notifCode).' '.$newRef.' '.$dir_output;
			break;
	}
	$template = $notifCode.'_TEMPLATE';
	$labelToUse = $conf->global->$template;

	$defaultMessage = null;
	if (!empty($labelToUse)) {
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);

		$defaultMessage = $formmail->getEMailTemplate($db, $objectType.'_send', $user, $outputLangs, 0, 1, $labelToUse);
	}

	if (!empty($labelToUse) && is_object($defaultMessage) && $defaultMessage->id > 0) {
		$substitutionarray = getCommonSubstitutionArray($outputLangs, 0, null, $object);
		complete_substitutions_array($substitutionarray, $outputLangs, $object);
		$subject = make_substitutions($defaultMessage->topic, $substitutionarray, $outputLangs);
		$message = make_substitutions($defaultMessage->content, $substitutionarray, $outputLangs);
	} else {
		$subject = '['.$companyName.'] '.$outputLangs->transnoentitiesnoconv("DolibarrNotification");
		$subject .= ($projectTitle ? ' '.$projectTitle : '');
		$message = $outputLangs->transnoentities("YouReceiveMailBecauseOfNotification", $application, $companyName)."\n";
		$message .= $outputLangs->transnoentities("YouReceiveMailBecauseOfNotification2", $application, $companyName)."\n";
		$message .= "\n";
		$message .= $mesg;
	}

	$ref = dol_sanitizeFileName($newRef);
	$pdfPath = $dir_output."/".$ref.".pdf";
	if (!dol_is_file($pdfPath)||(is_object($defaultMessage) && $defaultMessage->id > 0 && !$defaultMessage->joinfiles)) {
		// We can't add PDF as it is not generated yet.
		$pdfFile = '';
	} else {
		$pdfFile = $pdfPath;
		$filename_list[] = $pdfFile;
		$mimetype_list[] = mime_content_type($pdfFile);
		$mimefilename_list[] = $ref.".pdf";
	}

	$parameters = array('notifCode'=>$notifCode, 'to'=>$to, 'from'=>$from, 'file'=>$filename_list, 'mimefile'=>$mimetype_list, 'filename'=>$mimefilename_list, 'outputLangs'=>$outputLangs, 'labelToUse'=>$labelToUse);
	if (!isset($action)) {
		$action = '';
	}

	$reshook = $hookmanager->executeHooks('formatNotificationMessage', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
	if (empty($reshook)) {
		if (!empty($hookmanager->resArray['subject'])) {
			$subject .= $hookmanager->resArray['subject'];
		}
		if (!empty($hookmanager->resArray['message'])) {
			$message .= $hookmanager->resArray['message'];
		}
	}

	switch ($targetType) {
		case 'tocontactid':
			$trackid = 'ctc'.$contactId;
			$fkContact = 'fk_contact, ';
			break;
		case 'touserid':
			$trackid = 'use'.$contactId;
			$fkContact = 'fk_user, ';
			break;
		default:
			$trackid = '';
			$fkContact = '';
			break;
	}

	$mailfile = new CMailFile(
		$subject,
		$to,
		$from,
		$message.' '.$filename_list[0],
		$filename_list,
		$mimetype_list,
		$mimefilename_list,
		'',
		'',
		0,
		-1,
		'',
		'',
		$trackid,
		'',
		'notification'
	);

	if ($mailfile->sendfile()) {
		$sql = "INSERT INTO ".$db->prefix()."notify (daten, fk_action, fk_soc, ".$fkContact."type, objet_type, type_target, objet_id, email)";
		$sql .= " VALUES ('".$db->idate(dol_now())."', ".(int) $actionId.", ".($object->socid > 0 ? ((int) $object->socid) : 'null').", ".(($contactId) ? (int) $contactId.', ' : '')."'".$db->escape($type)."', '".$db->escape($objectType)."', '".$db->escape($targetType)."', ".((int) $object->id).", '".$db->escape($email)."')";

		if (!$db->query($sql)) {
			dol_print_error($db);
		}
	} else {
		$result = $mailfile->error;
	}

	return $result;
}
