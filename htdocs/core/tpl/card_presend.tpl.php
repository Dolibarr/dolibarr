<?php
/* Copyright (C) 2017-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2022	    Charlene Benke          <charlene@patas-monkey.com>
 * Copyright (C) 2023       Maxime Nicolas          <maxime@oarces.com>
 * Copyright (C) 2023       Benjamin GREMBI         <benjamin@oarces.com>
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

/*
 * Code to ouput content when action is presend
 *
 * $trackid must be defined
 * $modelmail
 * $defaulttopic and $defaulttopiclang
 * $diroutput
 * $arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}


if ($action == 'presend') {
	$langs->load("mails");

	$titreform = 'SendMail';

	$object->fetch_projet();
	if (!isset($file)) {
		$file = null;
	}
	$ref = dol_sanitizeFileName($object->ref);
	if (!in_array($object->element, array('user', 'member'))) {
		//$fileparams['fullname'] can be filled from the card
		//Get also the main_lastdoc field of $object. If not found, try to guess with following code
		if (!empty($object->last_main_doc) && is_readable(DOL_DATA_ROOT.'/'.$object->last_main_doc) && is_file(DOL_DATA_ROOT.'/'.$object->last_main_doc)) {
			$fileparams['fullname'] = DOL_DATA_ROOT.'/'.$object->last_main_doc;
		} else {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			// Special case
			if ($object->element == 'invoice_supplier') {
				$fileparams = dol_most_recent_file($diroutput.'/'.get_exdir($object->id, 2, 0, 0, $object, $object->element).$ref, preg_quote($ref, '/').'([^\-])+');
			} else {
				$fileparams = dol_most_recent_file($diroutput.'/'.$ref, preg_quote($ref, '/').'[^\-]+');
			}
		}

		$file = isset($fileparams['fullname']) ? $fileparams['fullname'] : null;
	}

	// Define output language
	$outputlangs = $langs;
	$newlang = '';
	if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
		$newlang = $object->thirdparty->default_lang;
		if (GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
	}

	if (!empty($newlang)) {
		$outputlangs = new Translate('', $conf);
		$outputlangs->setDefaultLang($newlang);
		// Load traductions files required by page
		$outputlangs->loadLangs(array('commercial', 'bills', 'orders', 'contracts', 'members', 'propal', 'products', 'supplier_proposal', 'interventions', 'receptions', 'sendings'));
		if (!empty($defaulttopiclang)) {
			$outputlangs->loadLangs(array($defaulttopiclang));
		}
	}

	$topicmail = '';
	if (empty($object->ref_client)) {
		$topicmail = $outputlangs->trans($defaulttopic, '__REF__');
	} elseif (!empty($object->ref_client)) {
		$topicmail = $outputlangs->trans($defaulttopic, '__REF__'. getDolGlobalString('MAIN_MAIL_NO_DISPLAY_OBJECT_REF_CLIENT', ' (__REF_CLIENT__)'));
	}
	// Build document if it not exists
	$forcebuilddoc = true;
	if (in_array($object->element, array('user', 'member'))) {
		$forcebuilddoc = false;
	}
	if ($object->element == 'invoice_supplier' && !getDolGlobalString('INVOICE_SUPPLIER_ADDON_PDF')) {
		$forcebuilddoc = false;
	}
	if ($object->element == 'societe' && !getDolGlobalString('COMPANY_ADDON_PDF')) {
		$forcebuilddoc = false;
	}
	if ($forcebuilddoc) {    // If there is no default value for supplier invoice, we do not generate file, even if modelpdf was set by a manual generation
		if ((!$file || !is_readable($file)) && method_exists($object, 'generateDocument')) {
			$result = $object->generateDocument(GETPOST('model') ? GETPOST('model') : $object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result < 0) {
				dol_print_error($db, $object->error, $object->errors);
				exit();
			}
			if ($object->element == 'invoice_supplier') {
				$fileparams = dol_most_recent_file($diroutput.'/'.get_exdir($object->id, 2, 0, 0, $object, $object->element).$ref, preg_quote($ref, '/').'([^\-])+');
			} else {
				$fileparams = dol_most_recent_file($diroutput.'/'.$ref, preg_quote($ref, '/').'[^\-]+');
			}

			$file = isset($fileparams['fullname']) ? $fileparams['fullname'] : null;
		}
	}

	print '<div id="formmailbeforetitle" name="formmailbeforetitle"></div>';
	print '<div class="clearboth"></div>';
	print '<br>';
	print load_fiche_titre($langs->trans($titreform));

	print dol_get_fiche_head('', '', '', -1);

	// Create form for email
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	$formmail = new FormMail($db);

	$formmail->param['langsmodels'] = (empty($newlang) ? $langs->defaultlang : $newlang);
	$formmail->fromtype = (GETPOST('fromtype') ? GETPOST('fromtype') : (getDolGlobalString('MAIN_MAIL_DEFAULT_FROMTYPE') ? $conf->global->MAIN_MAIL_DEFAULT_FROMTYPE : 'user'));

	if ($formmail->fromtype === 'user') {
		$formmail->fromid = $user->id;
	}
	if ($object->element == 'salary' && getDolGlobalString('INVOICE_EMAIL_SENDER')) {
		$formmail->frommail = $conf->global->SINVOICE_EMAIL_SENDER;
		$formmail->fromname = getDolGlobalString('INVOICE_EMAIL_SENDER_NAME', '');
		$formmail->fromtype = 'special';
	}
	if ($object->element === 'facture' && getDolGlobalString('INVOICE_EMAIL_SENDER')) {
		$formmail->frommail = $conf->global->INVOICE_EMAIL_SENDER;
		$formmail->fromname = getDolGlobalString('INVOICE_EMAIL_SENDER_NAME', '');
		$formmail->fromtype = 'special';
	}
	if ($object->element === 'shipping' && getDolGlobalString('SHIPPING_EMAIL_SENDER')) {
		$formmail->frommail = $conf->global->SHIPPING_EMAIL_SENDER;
		$formmail->fromname = getDolGlobalString('SHIPPING_EMAIL_SENDER_NAME', '');
		$formmail->fromtype = 'special';
	}
	if ($object->element === 'commande' && getDolGlobalString('COMMANDE_EMAIL_SENDER')) {
		$formmail->frommail = $conf->global->COMMANDE_EMAIL_SENDER;
		$formmail->fromname = getDolGlobalString('COMMANDE_EMAIL_SENDER_NAME', '');
		$formmail->fromtype = 'special';
	}
	if ($object->element === 'order_supplier' && getDolGlobalString('ORDER_SUPPLIER_EMAIL_SENDER')) {
		$formmail->frommail = $conf->global->ORDER_SUPPLIER_EMAIL_SENDER;
		$formmail->fromname = getDolGlobalString('ORDER_SUPPLIER_EMAIL_SENDER_NAME', '');
		$formmail->fromtype = 'special';
	}
	if ($object->element === 'recruitmentcandidature') {
		$formmail->frommail = getDolGlobalString('RECRUITMENT_EMAIL_SENDER', (!empty($recruitermail) ? $recruitermail : ''));
		$formmail->fromname = getDolGlobalString('RECRUITMENT_EMAIL_SENDER_NAME', (!empty($recruitername) ? $recruitername : ''));
		$formmail->fromtype = 'special';
	}

	// Set the default "From"
	$defaultfrom = '';
	if (GETPOSTISSET('fromtype')) {
		$defaultfrom = GETPOST('fromtype');
	} else {
		$parameters = array();
		$reshook = $hookmanager->executeHooks('getDefaultFromEmail', $parameters, $formmail);
		if (empty($reshook)) {
			$defaultfrom = $formmail->fromtype;
		}
		if (!empty($hookmanager->resArray['defaultfrom'])) {
			$defaultfrom = $hookmanager->resArray['defaultfrom'];
		}
	}
	$formmail->fromtype = $defaultfrom;

	$formmail->trackid = empty($trackid) ? '' : $trackid;
	$formmail->inreplyto = empty($inreplyto) ? '' : $inreplyto;
	$formmail->withfrom = 1;

	// Define $liste, a list of recipients with email inside <>.
	$liste = array();
	if ($object->element == 'expensereport') {
		$fuser = new User($db);
		$fuser->fetch($object->fk_user_author);
		$liste['thirdparty'] = $fuser->getFullName($outputlangs)." <".$fuser->email.">";
	} elseif ($object->element == 'partnership' && getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR') == 'member') {
		$fadherent = new Adherent($db);
		$fadherent->fetch($object->fk_member);
		$liste['member'] = $fadherent->getFullName($outputlangs)." <".$fadherent->email.">";
	} elseif ($object->element == 'societe') {
		foreach ($object->thirdparty_and_contact_email_array(1) as $key => $value) {
			$liste[$key] = $value;
		}
	} elseif ($object->element == 'contact') {
		$liste['contact'] = $object->getFullName($outputlangs)." <".$object->email.">";
	} elseif ($object->element == 'user' || $object->element == 'member') {
		$liste['thirdparty'] = $object->getFullName($outputlangs)." <".$object->email.">";
	} elseif ($object->element == 'salary') {
		$fuser = new User($db);
		$fuser->fetch($object->fk_user);
		$liste['thirdparty'] = $fuser->getFullName($outputlangs)." <".$fuser->email.">";
	} else {
		// For exemple if element is project
		if (!empty($object->socid) && $object->socid > 0 && !is_object($object->thirdparty) && method_exists($object, 'fetch_thirdparty')) {
			$object->fetch_thirdparty();
		}
		if (is_object($object->thirdparty)) {
			foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value) {
				$liste[$key] = $value;
			}
		}
	}
	if (getDolGlobalString('MAIN_MAIL_ENABLED_USER_DEST_SELECT')) {
		$listeuser = array();
		$fuserdest = new User($db);

		$result = $fuserdest->fetchAll('ASC', 't.lastname', 0, 0, array('customsql'=>"t.statut=1 AND t.employee=1 AND t.email IS NOT NULL AND t.email <> ''"), 'AND', true);
		if ($result > 0 && is_array($fuserdest->users) && count($fuserdest->users) > 0) {
			foreach ($fuserdest->users as $uuserdest) {
				$listeuser[$uuserdest->id] = $uuserdest->user_get_property($uuserdest->id, 'email');
			}
		} elseif ($result < 0) {
			setEventMessages(null, $fuserdest->errors, 'errors');
		}
		if (count($listeuser) > 0) {
			$formmail->withtouser = $listeuser;
			$formmail->withtoccuser = $listeuser;
		}
	}

	//$arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
	if (!isset($arrayoffamiliestoexclude)) {
		$arrayoffamiliestoexclude = null;
	}

	// Make substitution in email content
	if (!empty($object)) {
		// First we set ->substit (useless, it will be erased later) and ->substit_lines
		$formmail->setSubstitFromObject($object, $langs);
	}
	$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, $arrayoffamiliestoexclude, $object);

	// Overwrite __SENDEREMAIL_SIGNATURE__ with value select into form
	if ($formmail->fromtype) {
		$reg = array();
		if (preg_match('/user/', $formmail->fromtype, $reg)) {
			$emailsendersignature = $user->signature;
		} elseif (preg_match('/company/', $formmail->fromtype, $reg)) {
			$emailsendersignature = '';
		} elseif (preg_match('/senderprofile_(\d+)/', $formmail->fromtype, $reg)) {
			$sql = "SELECT rowid, label, email, signature FROM ".$db->prefix()."c_email_senderprofile";
			$sql .= " WHERE rowid = ".((int) $reg[1]);
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$emailsendersignature = $obj->signature;
				}
			}
		}
	}
	$substitutionarray['__SENDEREMAIL_SIGNATURE__'] = $emailsendersignature;

	$substitutionarray['__CHECK_READ__'] = "";
	if (is_object($object) && is_object($object->thirdparty)) {
		$checkRead= '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php';
		$checkRead.='?tag='.(!empty($object->thirdparty->tag) ? urlencode($object->thirdparty->tag) : "");
		$checkRead.='&securitykey='.(getDolGlobalString('MAILING_EMAIL_UNSUBSCRIBE_KEY') ? urlencode(getDolGlobalString('MAILING_EMAIL_UNSUBSCRIBE_KEY')) : "");
		$checkRead.='" width="1" height="1" style="width:1px;height:1px" border="0"/>';
		$substitutionarray['__CHECK_READ__'] = $checkRead;
	}
	$substitutionarray['__CONTACTCIVNAME__'] = '';
	$parameters = array(
		'mode' => 'formemail'
	);
	complete_substitutions_array($substitutionarray, $outputlangs, $object, $parameters);

	// Find all external contact addresses
	$tmpobject = $object;
	if (($object->element == 'shipping' || $object->element == 'reception')) {
		$origin = $object->origin;
		$origin_id = $object->origin_id;

		if (!empty($origin) && !empty($origin_id)) {
			$element = $subelement = $origin;
			$regs = array();
			if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
				$element = $regs[1];
				$subelement = $regs[2];
			}
			// For compatibility
			if ($element == 'order') {
				$element = $subelement = 'commande';
			}
			if ($element == 'propal') {
				$element = 'comm/propal';
				$subelement = 'propal';
			}
			if ($element == 'contract') {
				$element = $subelement = 'contrat';
			}
			if ($element == 'inter') {
				$element = $subelement = 'ficheinter';
			}
			if ($element == 'shipping') {
				$element = $subelement = 'expedition';
			}
			if ($element == 'order_supplier') {
				$element = 'fourn';
				$subelement = 'fournisseur.commande';
			}
			if ($element == 'project') {
				$element = 'projet';
			}

			dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');
			$classname = ucfirst($origin);
			$objectsrc = new $classname($db);
			$objectsrc->fetch($origin_id);

			$tmpobject = $objectsrc;
		}
	}

	$contactarr = array();
	$contactarr = $tmpobject->liste_contact(-1, 'external', 0, '', 1);

	if (is_array($contactarr) && count($contactarr) > 0) {
		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		$contactstatic = new Contact($db);
		$tmpcompany = new Societe($db);

		foreach ($contactarr as $contact) {
			$contactstatic->fetch($contact['id']);
			// Complete substitution array
			$substitutionarray['__CONTACT_NAME_'.$contact['code'].'__'] = $contactstatic->getFullName($outputlangs, 1);
			$substitutionarray['__CONTACT_LASTNAME_'.$contact['code'].'__'] = $contactstatic->lastname;
			$substitutionarray['__CONTACT_FIRSTNAME_'.$contact['code'].'__'] = $contactstatic->firstname;
			$substitutionarray['__CONTACT_TITLE_'.$contact['code'].'__'] = $contactstatic->getCivilityLabel();

			// Complete $liste with the $contact
			if (empty($liste[$contact['id']])) {	// If this contact id not already into the $liste
				$contacttoshow = '';
				if (isset($object->thirdparty) && is_object($object->thirdparty)) {
					if ($contactstatic->fk_soc != $object->thirdparty->id) {
						$tmpcompany->fetch($contactstatic->fk_soc);
						if ($tmpcompany->id > 0) {
							$contacttoshow .= $tmpcompany->name.': ';
						}
					}
				}
				$contacttoshow .= $contactstatic->getFullName($outputlangs, 1);
				$contacttoshow .= " <".($contactstatic->email ? $contactstatic->email : $langs->transnoentitiesnoconv("NoEMail")) .">";
				$liste[$contact['id']] = $contacttoshow;
			}
		}
	}

	$formmail->withto = $liste;
	$formmail->withtofree = (GETPOST('sendto', 'alphawithlgt') ? GETPOST('sendto', 'alphawithlgt') : '1');
	$formmail->withtocc = $liste;
	$formmail->withtoccc = getDolGlobalString('MAIN_EMAIL_USECCC');
	$formmail->withtopic = $topicmail;
	$formmail->withfile = 2;
	$formmail->withbody = 1;
	$formmail->withdeliveryreceipt = 1;
	$formmail->withcancel = 1;

	// Array of substitutions
	$formmail->substit = $substitutionarray;

	// Array of other parameters
	$formmail->param['action'] = 'send';
	$formmail->param['models'] = $modelmail;
	$formmail->param['models_id'] = GETPOST('modelmailselected', 'int');
	$formmail->param['id'] = $object->id;
	$formmail->param['returnurl'] = $_SERVER["PHP_SELF"].'?id='.$object->id;
	$formmail->param['fileinit'] = array($file);
	$formmail->param['object_entity'] = $object->entity;

	// Show form
	print $formmail->get_form();

	print dol_get_fiche_end();
}
