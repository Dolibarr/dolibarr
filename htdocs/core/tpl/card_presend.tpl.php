<?php
/* Copyright (C)    2017 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/*
 * Code to ouput content when action is presend
 *
 * $trackid must be defined
 * $modelmail
 * $defaulttopic
 * $diroutput
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}


if ($action == 'presend')
{
	$langs->load("mails");

	$titreform='SendMail';

	$object->fetch_projet();

	if (! in_array($object->element, array('societe', 'user', 'member')))
	{
		// TODO get also the main_lastdoc field of $object. If not found, try to guess with following code

		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($diroutput . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
		//
		if ($object->element == 'invoice_supplier')
		{
			$fileparams = dol_most_recent_file($diroutput . '/' . get_exdir($object->id,2,0,0,$object,$object->element).$ref, preg_quote($ref,'/').'([^\-])+');
		}

		$file = $fileparams['fullname'];
	}

	// Define output language
	$outputlangs = $langs;
	$newlang = '';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
	{
		$newlang = $_REQUEST['lang_id'];
	}
	if ($conf->global->MAIN_MULTILANGS && empty($newlang))
	{
		$newlang = $object->thirdparty->default_lang;
	}

	if (!empty($newlang))
	{
		$outputlangs = new Translate('', $conf);
		$outputlangs->setDefaultLang($newlang);
		$outputlangs->loadLangs(array('commercial','bills','orders','contracts','members','propal','products','supplier_proposal','interventions'));
	}

	$topicmail='';
	if (empty($object->ref_client)) {
		$topicmail = $outputlangs->trans($defaulttopic, '__REF__');
	} else if (! empty($object->ref_client)) {
		$topicmail = $outputlangs->trans($defaulttopic, '__REF__ (__REFCLIENT__)');
	}

	// Build document if it not exists
	if (! in_array($object->element, array('societe', 'user', 'member')))
	{
		if ((! $file || ! is_readable($file)) && method_exists($object, 'generateDocument'))
		{
			$result = $object->generateDocument(GETPOST('model') ? GETPOST('model') : $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0) {
				dol_print_error($db, $object->error, $object->errors);
				exit();
			}
			$fileparams = dol_most_recent_file($diroutput . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
			$file = $fileparams['fullname'];
		}
	}

	print '<div id="formmailbeforetitle" name="formmailbeforetitle"></div>';
	print '<div class="clearboth"></div>';
	print '<br>';
	print load_fiche_titre($langs->trans($titreform));

	dol_fiche_head('');

	// Create form for email
	include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
	$formmail = new FormMail($db);

	$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
	$formmail->fromtype = (GETPOST('fromtype')?GETPOST('fromtype'):(!empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE)?$conf->global->MAIN_MAIL_DEFAULT_FROMTYPE:'user'));

	if ($formmail->fromtype === 'user')
	{
		$formmail->fromid = $user->id;
	}
	$formmail->trackid=$trackid;
	if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
	{
		include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$formmail->frommail=dolAddEmailTrackId($formmail->frommail, $trackid);
	}
	$formmail->withfrom = 1;

	// Fill list of recipient with email inside <>.
	$liste = array();
	if ($object->element == 'expensereport')
	{
		$fuser = new User($db);
		$fuser->fetch($object->fk_user_author);
		$liste['thirdparty'] = $fuser->getFullName($langs)." <".$fuser->email.">";
	}
	elseif ($object->element == 'societe')
	{
		foreach ($object->thirdparty_and_contact_email_array(1) as $key => $value) {
			$liste[$key] = $value;
		}
	}
	elseif ($object->element == 'user' || $object->element == 'member')
	{
		$liste['thirdparty'] = $object->getFullName($langs)." <".$object->email.">";
	}
	else
	{
		foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value) {
			$liste[$key] = $value;
		}
	}

	$formmail->withto = GETPOST('sendto') ? GETPOST('sendto') : $liste;
	$formmail->withtocc = $liste;
	$formmail->withtoccc = $conf->global->MAIN_EMAIL_USECCC;
	$formmail->withtopic = $topicmail;
	$formmail->withfile = 2;
	$formmail->withbody = 1;
	$formmail->withdeliveryreceipt = 1;
	$formmail->withcancel = 1;

	// Make substitution in email content
	$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
	$substitutionarray['__CHECK_READ__'] = (is_object($object) && is_object($object->thirdparty)) ? '<img src="' . DOL_MAIN_URL_ROOT . '/public/emailing/mailing-read.php?tag=' . $object->thirdparty->tag . '&securitykey=' . urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY) . '" width="1" height="1" style="width:1px;height:1px" border="0"/>' : '';
	$substitutionarray['__PERSONALIZED__'] = '';	// deprecated
	$substitutionarray['__CONTACTCIVNAME__'] = '';
	$parameters = array(
		'mode' => 'formemail'
	);
	complete_substitutions_array($substitutionarray, $outputlangs, $object, $parameters);

	// Tableau des substitutions
	$formmail->substit = $substitutionarray;

	// Tableau des parametres complementaires
	$formmail->param['action'] = 'send';
	$formmail->param['models'] = $modelmail;
	$formmail->param['models_id']=GETPOST('modelmailselected','int');
	$formmail->param['id'] = $object->id;
	$formmail->param['returnurl'] = $_SERVER["PHP_SELF"] . '?id=' . $object->id;
	$formmail->param['fileinit'] = array($file);

	$formmail->withsubstit = 1;

	// Show form
	print $formmail->get_form();

	dol_fiche_end();
}

