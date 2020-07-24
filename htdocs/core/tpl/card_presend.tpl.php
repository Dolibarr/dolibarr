<?php
/* Copyright (C)    2017-2018 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * $defaulttopic
 * $diroutput
 * $arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf)) {
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
		// Special case
		if ($object->element == 'invoice_supplier')
		{
			$fileparams = dol_most_recent_file($diroutput . '/' . get_exdir($object->id, 2, 0, 0, $object, $object->element).$ref, preg_quote($ref, '/').'([^\-])+');
		}
		else
		{
		    $fileparams = dol_most_recent_file($diroutput . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
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
		// Load traductions files required by page
		$outputlangs->loadLangs(array('commercial','bills','orders','contracts','members','propal','products','supplier_proposal','interventions'));
	}

	$topicmail='';
	if (empty($object->ref_client)) {
		$topicmail = $outputlangs->trans($defaulttopic, '__REF__');
	} elseif (! empty($object->ref_client)) {
		$topicmail = $outputlangs->trans($defaulttopic, '__REF__ (__REFCLIENT__)');
	}

	// Build document if it not exists
	$forcebuilddoc=true;
	if (in_array($object->element, array('societe', 'user', 'member'))) $forcebuilddoc=false;
	if ($object->element == 'invoice_supplier' && empty($conf->global->INVOICE_SUPPLIER_ADDON_PDF)) $forcebuilddoc=false;
	if ($forcebuilddoc)    // If there is no default value for supplier invoice, we do not generate file, even if modelpdf was set by a manual generation
	{
		if ((! $file || ! is_readable($file)) && method_exists($object, 'generateDocument'))
		{
			$result = $object->generateDocument(GETPOST('model') ? GETPOST('model') : $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result < 0) {
				dol_print_error($db, $object->error, $object->errors);
				exit();
			}
			if ($object->element == 'invoice_supplier')
			{
			    $fileparams = dol_most_recent_file($diroutput . '/' . get_exdir($object->id, 2, 0, 0, $object, $object->element).$ref, preg_quote($ref, '/').'([^\-])+');
			}
			else
			{
			    $fileparams = dol_most_recent_file($diroutput . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
			}

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
	if ($object->element === 'facture' && !empty($conf->global->INVOICE_EMAIL_SENDER)) {
		$formmail->frommail = $conf->global->INVOICE_EMAIL_SENDER;
		$formmail->fromname = '';
		$formmail->fromtype = 'special';
	}
	if ($object->element === 'shipping' && !empty($conf->global->SHIPPING_EMAIL_SENDER)) {
		$formmail->frommail = $conf->global->SHIPPING_EMAIL_SENDER;
		$formmail->fromname = '';
		$formmail->fromtype = 'special';
	}
	if ($object->element === 'commande' && !empty($conf->global->COMMANDE_EMAIL_SENDER)) {
		$formmail->frommail = $conf->global->COMMANDE_EMAIL_SENDER;
		$formmail->fromname = '';
		$formmail->fromtype = 'special';
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
		$liste['thirdparty'] = $fuser->getFullName($outputlangs)." <".$fuser->email.">";
	}
	elseif ($object->element == 'societe')
	{
		foreach ($object->thirdparty_and_contact_email_array(1) as $key => $value) {
			$liste[$key] = $value;
		}
	}
	elseif ($object->element == 'contact')
	{
		$liste['contact'] = $object->getFullName($outputlangs)." <".$object->email.">";
	}
	elseif ($object->element == 'user' || $object->element == 'member')
	{
		$liste['thirdparty'] = $object->getFullName($outputlangs)." <".$object->email.">";
	}
	else
	{
		if (is_object($object->thirdparty))
		{
			foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value) {
				$liste[$key] = $value;
			}
		}
	}
	if (!empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT)) {
		$listeuser=array();
		$fuserdest = new User($db);

		$result= $fuserdest->fetchAll('ASC', 't.lastname', 0, 0, array('customsql'=>'t.statut=1 AND t.employee=1 AND t.email IS NOT NULL AND t.email<>\'\''), 'AND', true);
		if ($result>0 && is_array($fuserdest->users) && count($fuserdest->users)>0) {
			foreach($fuserdest->users as $uuserdest) {
				$listeuser[$uuserdest->id] = $uuserdest->user_get_property($uuserdest->id, 'email');
			}
		} elseif ($result<0) {
			setEventMessages(null, $fuserdest->errors, 'errors');
		}
		if (count($listeuser)>0) {
			$formmail->withtouser = $listeuser;
			$formmail->withtoccuser = $listeuser;
		}
	}

	$formmail->withto = $liste;
	$formmail->withtofree = (GETPOSTISSET('sendto') ? (GETPOST('sendto') ? GETPOST('sendto') : '1') : '1');
	$formmail->withtocc = $liste;
	$formmail->withtoccc = $conf->global->MAIN_EMAIL_USECCC;
	$formmail->withtopic = $topicmail;
	$formmail->withfile = 2;
	$formmail->withbody = 1;
	$formmail->withdeliveryreceipt = 1;
	$formmail->withcancel = 1;

	//$arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
	if (! isset($arrayoffamiliestoexclude)) $arrayoffamiliestoexclude=null;

	// Make substitution in email content
	$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, $arrayoffamiliestoexclude, $object);
	$substitutionarray['__CHECK_READ__'] = (is_object($object) && is_object($object->thirdparty)) ? '<img src="' . DOL_MAIN_URL_ROOT . '/public/emailing/mailing-read.php?tag=' . $object->thirdparty->tag . '&securitykey=' . urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY) . '" width="1" height="1" style="width:1px;height:1px" border="0"/>' : '';
	$substitutionarray['__PERSONALIZED__'] = '';	// deprecated
	$substitutionarray['__CONTACTCIVNAME__'] = '';
	$parameters = array(
		'mode' => 'formemail'
	);
	complete_substitutions_array($substitutionarray, $outputlangs, $object, $parameters);

	// Find the good contact address
    $tmpobject = $object;
    if (($object->element == 'shipping'|| $object->element == 'reception')) {
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
            if ($element == 'order')    {
                $element = $subelement = 'commande';
            }
            if ($element == 'propal')   {
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

            dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');
            $classname = ucfirst($origin);
            $objectsrc = new $classname($db);
            $objectsrc->fetch($origin_id);

            $tmpobject = $objectsrc;
        }
    }

	$custcontact = '';
	$contactarr = array();
	$contactarr = $tmpobject->liste_contact(- 1, 'external');

	if (is_array($contactarr) && count($contactarr) > 0) {
		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        $contactstatic = new Contact($db);

		foreach ($contactarr as $contact) {
            $contactstatic->fetch($contact['id']);
            $substitutionarray['__CONTACT_NAME_'.$contact['code'].'__'] = $contactstatic->getFullName($outputlangs, 1);
		}
	}

	// Tableau des substitutions
	$formmail->substit = $substitutionarray;

	// Tableau des parametres complementaires
	$formmail->param['action'] = 'send';
	$formmail->param['models'] = $modelmail;
	$formmail->param['models_id']=GETPOST('modelmailselected', 'int');
	$formmail->param['id'] = $object->id;
	$formmail->param['returnurl'] = $_SERVER["PHP_SELF"] . '?id=' . $object->id;
	$formmail->param['fileinit'] = array($file);

	// Show form
	print $formmail->get_form();

	dol_fiche_end();
}
