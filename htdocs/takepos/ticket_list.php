<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2016 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2015 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2012 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Florian Henry		 <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2015-2016 Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2019		JC Prieto			<jcprieto@virtual20.com>
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
 */

/**
 *	\file       htdocs/takepos/ticket_list.php
 *	\ingroup    facture
 *	\brief      List of customer invoices
 */

$res=@include("../main.inc.php");                    // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");        // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->projet->enabled))   require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';


$langs->load('bills');
$langs->load('companies');
$langs->load('products');


$langs->load('takepos@takepos');

$sall=trim(GETPOST('sall'));
$projectid=(GETPOST('projectid')?GETPOST('projectid','int'):0);


$id=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$lineid=GETPOST('lineid','int');
$userid=GETPOST('userid','int');
$search_product_category=GETPOST('search_product_category','int');
//V20
$search_ref=GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
//$search_ref='POS';

$search_refcustomer=GETPOST('search_refcustomer','alpha');
$search_societe=GETPOST('search_societe','alpha');
$search_montant_ht=GETPOST('search_montant_ht','alpha');
$search_montant_vat=GETPOST('search_montant_vat','alpha');
$search_montant_ttc=GETPOST('search_montant_ttc','alpha');

$search_status=GETPOST('search_status');

if (strval($search_status)=='')		$search_status='5'; //V20: By default= All


$search_paymentmode=GETPOST('search_paymentmode','int');
$search_town=GETPOST('search_town','alpha');
$search_zip=GETPOST('search_zip','alpha');
$search_state=trim(GETPOST("search_state"));
$search_country=GETPOST("search_country",'int');
$search_type_thirdparty=GETPOST("search_type_thirdparty",'int');
$search_user = GETPOST('search_user','int');
$search_sale = GETPOST('search_sale','int');
$day	= GETPOST('day','int');
$month	= GETPOST('month','int');
$year	= GETPOST('year','int');
$day_lim	= GETPOST('day_lim','int');
$month_lim	= GETPOST('month_lim','int');
$year_lim	= GETPOST('year_lim','int');
$toselect = GETPOST('toselect', 'array');

$option = GETPOST('option');
if ($option == 'late') $filter = 'paye:0';
$filtre	= GETPOST('filtre');

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
if (! $sortorder && ! empty($conf->global->INVOICE_DEFAULT_UNPAYED_SORT_ORDER) && $search_status == 1) $sortorder=$conf->global->INVOICE_DEFAULT_UNPAYED_SORT_ORDER;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='f.datef';	
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$contextpage='invoicelist';

//V20
$orderid=GETPOST('orderid','int');
// Security check
$fieldid = (! empty($ref)?'facnumber':'rowid');
if (! empty($user->societe_id)) $socid=$user->societe_id;


$diroutputmassaction=$conf->facture->dir_output . '/temp/massgeneration/'.$user->id;

$object=new Facture($db);

$now=dol_now();

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('invoicelist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('facture');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'f.facnumber'=>'Ref',
    'f.ref_client'=>'RefCustomer',
    'pd.description'=>'Description',
    's.nom'=>"ThirdParty",
    'f.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["f.note_private"]="NotePrivate";

$checkedtypetiers=0;
$arrayfields=array(
    'f.facnumber'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
    'f.ref_client'=>array('label'=>$langs->trans("RefCustomer"), 'checked'=>1),
    'f.date'=>array('label'=>$langs->trans("DateInvoice"), 'checked'=>0),
    'f.date_lim_reglement'=>array('label'=>$langs->trans("DateDue"), 'checked'=>0),
    's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
    's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>0),
    's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>0),
    'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
    'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
    'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers),
    'f.fk_mode_reglement'=>array('label'=>$langs->trans("PaymentMode"), 'checked'=>1),
    'f.total_ht'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>0),
    'f.total_vat'=>array('label'=>$langs->trans("AmountVAT"), 'checked'=>0),
    'f.total_ttc'=>array('label'=>$langs->trans("AmountTTC"), 'checked'=>1),
    'dynamount_payed'=>array('label'=>$langs->trans("Received"), 'checked'=>0),
    'rtp'=>array('label'=>$langs->trans("Rest"), 'checked'=>0),
    'f.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'f.date_valid'=>array('label'=>$langs->trans("DatePayment"), 'checked'=>0, 'position'=>500),
	'f.fk_user_valid'=>array('label'=>$langs->trans("User"), 'checked'=>1, 'position'=>500),		
    'f.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>1, 'position'=>500),
    'f.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val)
    {
        $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>$extrafields->attribute_perms[$key]);
    }
}


/*
 * Actions
 */

if ($action=="select") {
	
    ?>
    <script>
	parent.$.colorbox.close();
    parent.$("#takepos").load("takepos.php?facid="+<?php print $id;?>);
    
    </script>
    <?php
    exit;
}

if (GETPOST('cancel')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction') && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter") || GETPOST("button_removefilter.x")) // Both test are required to be compatible with all browsers
{
    $search_user='';
    $search_sale='';
    $search_product_category='';
    $search_ref='';
    $search_refcustomer='';
    $search_project='';
    $search_societe='';
    $search_montant_ht='';
    $search_montant_vat='';
    $search_montant_ttc='';
    $search_status='';
    $search_paymentmode='';
    $search_town='';
    $search_zip="";
    $search_state="";
    $search_type='';
    $search_country='';
    $search_type_thirdparty='';    
    $day='';
    $year='';
    $month='';
    $toselect='';
    $option='';
    $filter='';
    $day_lim='';
    $year_lim='';
    $month_lim='';
    $search_array_options=array();
}

if (empty($reshook))
{
	// Mass actions. Controls on number of lines checked
    $maxformassaction=1000;
	if (! empty($massaction) && count($toselect) < 1)
	{
		$error++;
		setEventMessages($langs->trans("NoLineChecked"), null, "warnings");
	}
	if (! $error && count($toselect) > $maxformassaction)
	{
	    setEventMessages($langs->trans('TooManyRecordForMassAction',$maxformassaction), null, 'errors');
	    $error++;
	}
	
	if (! $error && $massaction == 'confirm_presend' && GETPOST('modelselected'))  // If we change the template, we must not send email, but keep on send email form
	{
	    $massaction='presend';
	}
	if (! $error && $massaction == 'confirm_presend')
	{
		$resaction = '';
		$nbsent = 0;
		$nbignored = 0;
		$langs->load("mails");
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		
		if (!$error && !isset($user->email))
		{
			$error++;
			setEventMessages($langs->trans("NoSenderEmailDefined"), null, 'warnings');
		}

		if (! $error)
		{
			$thirdparty=new Societe($db);
			$objecttmp=new Facture($db);
			$listofobjectid=array();
			$listofobjectthirdparties=array();
			$listofobjectref=array();
			foreach($toselect as $toselectid)
			{
				$objecttmp=new Facture($db);	// must create new instance because instance is saved into $listofobjectref array for future use
				$result=$objecttmp->fetch($toselectid);
				if ($result > 0) 
				{
					$listoinvoicesid[$toselectid]=$toselectid;
					$thirdpartyid=$objecttmp->fk_soc?$objecttmp->fk_soc:$objecttmp->socid;
					$listofobjectthirdparties[$thirdpartyid]=$thirdpartyid;
					$listofobjectref[$thirdpartyid][$toselectid]=$objecttmp;
				}
			}
			//var_dump($listofobjectthirdparties);exit;
			
			foreach ($listofobjectthirdparties as $thirdpartyid)
			{
				$result = $thirdparty->fetch($thirdpartyid);
				if ($result < 0) 
				{
					dol_print_error($db);
					exit;
				}
				
				// Define recipient $sendto and $sendtocc
				if (trim($_POST['sendto']))
				{
					// Recipient is provided into free text
					$sendto = trim($_POST['sendto']);
					$sendtoid = 0;
				}
				elseif ($_POST['receiver'] != '-1')
				{
					// Recipient was provided from combo list
					if ($_POST['receiver'] == 'thirdparty') // Id of third party
					{
						$sendto = $thirdparty->email;
						$sendtoid = 0;
					}
					else	// Id du contact
					{
						$sendto = $thirdparty->contact_get_property((int) $_POST['receiver'],'email');
						$sendtoid = $_POST['receiver'];
					}
				}
				if (trim($_POST['sendtocc']))
				{
					$sendtocc = trim($_POST['sendtocc']);
				}
				elseif ($_POST['receivercc'] != '-1')
				{
					// Recipient was provided from combo list
					if ($_POST['receivercc'] == 'thirdparty')	// Id of third party
					{
						$sendtocc = $thirdparty->email;
					}
					else	// Id du contact
					{
						$sendtocc = $thirdparty->contact_get_property((int) $_POST['receivercc'],'email');
					}
				}
				
				//var_dump($listofobjectref[$thirdpartyid]);	// Array of invoice for this thirdparty
				
				$attachedfiles=array('paths'=>array(), 'names'=>array(), 'mimes'=>array());
				$listofqualifiedinvoice=array();
				$listofqualifiedref=array();
				foreach($listofobjectref[$thirdpartyid] as $objectid => $object)
				{
					//var_dump($object);
					//var_dump($thirdpartyid.' - '.$objectid.' - '.$object->statut);
					
					if ($object->statut != Facture::STATUS_VALIDATED)
					{
						$nbignored++;
						$resaction.='<div class="error">'.$langs->trans('ErrorOnlyInvoiceValidatedCanBeSentInMassAction',$object->ref).'</div><br>';
						continue; // Payment done or started or canceled
					}
	
					// Read document
					// TODO Use future field $object->fullpathdoc to know where is stored default file
					// TODO If not defined, use $object->modelpdf (or defaut invoice config) to know what is template to use to regenerate doc.
					$filename=dol_sanitizeFileName($object->ref).'.pdf';
					$filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);
					$file = $filedir . '/' . $filename;
					$mime = dol_mimetype($file);

					if (dol_is_file($file))
					{
						if (empty($sendto)) 	// For the case, no recipient were set (multi thirdparties send)
						{
							$object->fetch_thirdparty();
							$sendto = $object->thirdparty->email;
						}
	
						if (empty($sendto)) 
						{
							//print "No recipient for thirdparty ".$object->thirdparty->name;
							$nbignored++;
							continue;
						}
	
						if (dol_strlen($sendto))
						{
							// Create form object
							$attachedfiles=array(
									'paths'=>array_merge($attachedfiles['paths'],array($file)), 
									'names'=>array_merge($attachedfiles['names'],array($filename)), 
									'mimes'=>array_merge($attachedfiles['mimes'],array($mime))
							);
						}
	
						$listofqualifiedinvoice[$objectid]=$object;
						$listofqualifiedref[$objectid]=$object->ref;
					}
					else
					{  
						$nbignored++;
						$langs->load("errors");
						$resaction.='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div><br>';
						dol_syslog('Failed to read file: '.$file, LOG_WARNING);
						continue;
					}
					
					//var_dump($listofqualifiedref);
				}
	
				if (count($listofqualifiedinvoice) > 0)
				{
					$langs->load("commercial");
					$from = $user->getFullName($langs) . ' <' . $user->email .'>';
					$replyto = $from;
					$subject = GETPOST('subject');
					$message = GETPOST('message');
					$sendtocc = GETPOST('sentocc');
					$sendtobcc = (empty($conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO)?'':$conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO);
		
					$substitutionarray=array(
						'__ID__' => join(', ',array_keys($listofqualifiedinvoice)),
						'__EMAIL__' => $thirdparty->email,
						'__CHECK_READ__' => '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$thirdparty->tag.'&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" width="1" height="1" style="width:1px;height:1px" border="0"/>',
						//'__LASTNAME__' => $obj2->lastname,
						//'__FIRSTNAME__' => $obj2->firstname,
						'__FACREF__' => join(', ',$listofqualifiedref),            // For backward compatibility
					    '__REF__' => join(', ',$listofqualifiedref),
						'__REFCLIENT__' => $thirdparty->name
					);
	
					$subject=make_substitutions($subject, $substitutionarray);
					$message=make_substitutions($message, $substitutionarray);
	
					$filepath = $attachedfiles['paths'];
					$filename = $attachedfiles['names'];
					$mimetype = $attachedfiles['mimes'];
					
					//var_dump($filepath);
					
					// Send mail
					require_once(DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php');
					$mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,$sendtobcc,$deliveryreceipt,-1);
					if ($mailfile->error)
					{
						$resaction.='<div class="error">'.$mailfile->error.'</div>';
					}
					else
					{
						$result=$mailfile->sendfile();
						if ($result)
						{
							$resaction.=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2)).'<br>';		// Must not contain "
	
							$error=0;
	
							// Insert logs into agenda
							foreach($listofqualifiedinvoice as $invid => $object)
							{
								$actiontypecode='AC_FAC';
								$actionmsg=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto;
								if ($message)
								{
									if ($sendtocc) $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('Bcc') . ": " . $sendtocc);
									$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
									$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
									$actionmsg = dol_concatdesc($actionmsg, $message);
								}
								
								// Initialisation donnees
								$object->sendtoid		= 0;
								$object->actiontypecode	= $actiontypecode;
								$object->actionmsg		= $actionmsg;  // Long text
								$object->actionmsg2		= $actionmsg2; // Short text
								$object->fk_element		= $invid;
								$object->elementtype	= $object->element;
		
								// Appel des triggers
								include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
								$interface=new Interfaces($db);
								$result=$interface->run_triggers('BILL_SENTBYMAIL',$object,$user,$langs,$conf);
								if ($result < 0) { $error++; $errors=$interface->errors; }
								// Fin appel triggers
		
								if ($error)
								{
									setEventMessages($db->lasterror(), $errors, 'errors');
									dol_syslog("Error in trigger BILL_SENTBYMAIL ".$db->lasterror(), LOG_ERR);
								}
								$nbsent++;
							}
						}
						else
						{
							$langs->load("other");
							if ($mailfile->error)
							{
								$resaction.=$langs->trans('ErrorFailedToSendMail',$from,$sendto);
								$resaction.='<br><div class="error">'.$mailfile->error.'</div>';
							}
							else
							{
								$resaction.='<div class="warning">No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS</div>';
							}
						}
					}
				}
			}

			$resaction.=($resaction?'<br>':$resaction);
			$resaction.='<strong>'.$langs->trans("ResultOfMailSending").':</strong><br>'."\n";
			$resaction.=$langs->trans("NbSelected").': '.count($toselect)."\n<br>";
			$resaction.=$langs->trans("NbIgnored").': '.($nbignored?$nbignored:0)."\n<br>";
			$resaction.=$langs->trans("NbSent").': '.($nbsent?$nbsent:0)."\n<br>";
			
			if ($nbsent)
			{
				$action='';	// Do not show form post if there was at least one successfull sent
				setEventMessages($langs->trans("EMailSentToNRecipients", $nbsent.'/'.count($toselect)), null, 'mesgs');
				setEventMessages($resaction, null, 'mesgs');
			}
			else
			{
				//setEventMessages($langs->trans("EMailSentToNRecipients", 0), null, 'warnings');  // May be object has no generated PDF file
				setEventMessages($resaction, null, 'warnings');
			}
		}
		
		$action='list';
		$massaction='';
	}

	if (! $error && $massaction == "builddoc"  && ! GETPOST('button_search'))
	{
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
        require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
         
        $objecttmp=new Facture($db);
        $listofobjectid=array();
        $listofobjectthirdparties=array();
        $listofobjectref=array();
        foreach($toselect as $toselectid)
        {
            $objecttmp=new Facture($db);	// must create new instance because instance is saved into $listofobjectref array for future use
            $result=$objecttmp->fetch($toselectid);
            if ($result > 0)
            {
                $listoinvoicesid[$toselectid]=$toselectid;
                $thirdpartyid=$objecttmp->fk_soc?$objecttmp->fk_soc:$objecttmp->socid;
                $listofobjectthirdparties[$thirdpartyid]=$thirdpartyid;
                $listofobjectref[$toselectid]=$objecttmp->ref;
            }
        }

        $arrayofinclusion=array();
        foreach($listofobjectref as $tmppdf) $arrayofinclusion[]=preg_quote($tmppdf.'.pdf','/');
        $factures = dol_dir_list($conf->facture->dir_output,'all',1,implode('|',$arrayofinclusion),'\.meta$|\.png','date',SORT_DESC,0,true);

        // liste les fichiers
        $files = array();
        foreach($listofobjectref as $basename)
        {
            foreach($factures as $facture)
            {
                if (strstr($facture["name"],$basename))
                {
                    $files[] = $conf->facture->dir_output.'/'.$basename.'/'.$facture["name"];
                    break;
                }
            }
        }
        
        // Define output language (Here it is not used because we do only merging existing PDF)
        $outputlangs = $langs;
        $newlang='';
        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
        if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->thirdparty->default_lang;
        if (! empty($newlang))
        {
            $outputlangs = new Translate("",$conf);
            $outputlangs->setDefaultLang($newlang);
        }

        // Create empty PDF
        $pdf=pdf_getInstance();
        if (class_exists('TCPDF'))
        {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($outputlangs));

        if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

        // Add all others
        foreach($files as $file)
        {
            // Charge un document PDF depuis un fichier.
            $pagecount = $pdf->setSourceFile($file);
            for ($i = 1; $i <= $pagecount; $i++)
            {
                $tplidx = $pdf->importPage($i);
                $s = $pdf->getTemplatesize($tplidx);
                $pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
                $pdf->useTemplate($tplidx);
            }
        }

        // Create output dir if not exists
        dol_mkdir($diroutputmassaction);

        // Save merged file
        $filename=strtolower(dol_sanitizeFileName($langs->transnoentities("Invoices")));
        if ($filter=='paye:0')
        {
            if ($option=='late') $filename.='_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Unpaid"))).'_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Late")));
            else $filename.='_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Unpaid")));
        }
        if ($year) $filename.='_'.$year;
        if ($month) $filename.='_'.$month;
        if ($pagecount)
        {
            $now=dol_now();
            $file=$diroutputmassaction.'/'.$filename.'_'.dol_print_date($now,'dayhourlog').'.pdf';
            $pdf->Output($file,'F');
            if (! empty($conf->global->MAIN_UMASK))
                @chmod($file, octdec($conf->global->MAIN_UMASK));

                $langs->load("exports");
                setEventMessages($langs->trans('FileSuccessfullyBuilt',$filename.'_'.dol_print_date($now,'dayhourlog')), null, 'mesgs');
        }
        else
        {
            setEventMessages($langs->trans('NoPDFAvailableForDocGenAmongChecked'), null, 'errors');
        }
	}
	
	// Remove file
	if ($action == 'remove_file')
	{
	    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	
	    $langs->load("other");
	    $upload_dir = $diroutputmassaction;
	    $file = $upload_dir . '/' . GETPOST('file');
	    $ret=dol_delete_file($file);
	    if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('file')), null, 'mesgs');
	    else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), null, 'errors');
	    $action='';
	}
	
}

    

/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$bankaccountstatic=new Account($db);
$facturestatic=new Facture($db);
$formcompany=new FormCompany($db);

$arrayofcss=array('/takepos/css/style.css');	//V20
top_htmlhead($head,$langs->trans("takepos"),0,0,$arrayofjs,$arrayofcss);

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql.= ' f.rowid as facid, f.facnumber, f.ref_client, f.type, f.note_private, f.note_public, f.increment, f.fk_mode_reglement, f.total as total_ht, f.tva as total_vat, f.total_ttc,';
$sql.= ' f.datef as df, f.date_lim_reglement as datelimite, f.date_valid, f.fk_user_author, f.fk_user_valid, ';
$sql.= ' f.paye as paye, f.fk_statut,';
$sql.= ' f.datec as date_creation, f.tms as date_update,';
$sql.= ' s.rowid as socid, s.nom as name, s.town, s.zip, s.fk_pays, s.client, s.code_client, s.price_level, ';
$sql.= " typent.code as typent_code,";
$sql.= " state.code_departement as state_code, state.nom as state_name";
// We need dynamount_payed to be able to sort on status (value is surely wrong because we can count several lines several times due to other left join or link with contacts. But what we need is just 0 or > 0)
// TODO Better solution to be able to sort on already payed or remain to pay is to store amount_payed in a denormalized field.
if (! $sall) $sql.= ', SUM(pf.amount) as dynamount_payed';   
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country ON (country.rowid = s.fk_pays)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent ON (typent.id = s.fk_typent)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state ON (state.rowid = s.fk_departement)";
$sql.= ', '.MAIN_DB_PREFIX.'facture as f';
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_extrafields as ef on (f.rowid = ef.fk_object)";
if (! $sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
if ($sall || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as pd ON f.rowid=pd.fk_facture';
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
    $sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
    $sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= ' WHERE f.fk_soc = s.rowid';
$sql.= ' AND f.module_source="takepos"';	//V20
$sql.= " AND f.entity = ".$conf->entity;

if ($search_product_category > 0) $sql.=" AND cp.fk_categorie = ".$search_product_category;
if ($socid > 0) $sql.= ' AND s.rowid = '.$socid;
if ($userid)
{
    if ($userid == -1) $sql.=' AND f.fk_user_author IS NULL';
    else $sql.=' AND f.fk_user_author = '.$userid;
}
if ($filtre)
{
    $aFilter = explode(',', $filtre);
    foreach ($aFilter as $filter)
    {
        $filt = explode(':', $filter);
        $sql .= ' AND ' . trim($filt[0]) . ' = ' . trim($filt[1]);
    }
}
if ($search_ref) $sql .= natural_search('f.facnumber', $search_ref);
if ($search_refcustomer) $sql .= natural_search('f.ref_client', $search_refcustomer);
if ($search_project) $sql .= natural_search('p.ref', $search_project);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($search_town)  $sql.= natural_search('s.town', $search_town);
if ($search_zip)   $sql.= natural_search("s.zip",$search_zip);
if ($search_state) $sql.= natural_search("state.nom",$search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$search_country.')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$search_type_thirdparty.')';
if ($search_company) $sql .= natural_search('s.nom', $search_company);
if ($search_montant_ht != '') $sql.= natural_search('f.total', $search_montant_ht, 1);
if ($search_montant_vat != '') $sql.= natural_search('f.total_vat', $search_montant_vat, 1);
if ($search_montant_ttc != '') $sql.= natural_search('f.total_ttc', $search_montant_ttc, 1);
if ($search_status != '' && $search_status >= 0)
{
    if ($search_status == '0') $sql.=" AND f.fk_statut = 0";  // draft
    if ($search_status == '1') $sql.=" AND f.fk_statut = 1 AND f.facnumber NOT LIKE '%ALB%'";  // unpayed
    if ($search_status == '2') $sql.=" AND f.fk_statut = 2";  // payed     Not that some correupted data may contains f.fk_statut = 1 AND f.paye = 1 (it means payed too but should not happend. If yes, reopen and reclassify billed)
    if ($search_status == '3') $sql.=" AND f.fk_statut = 3";  // abandonned
    if ($search_status == '8') $sql.=" AND (f.fk_statut = 1 OR f.fk_statut = 9)";  // V20: Pendientes
    if ($search_status == '9') $sql.=" AND f.fk_statut = 9";  // V20: Albaranes pendientes.  
}    
if ($search_paymentmode > 0) $sql .= " AND f.fk_mode_reglement = ".$search_paymentmode."";
if ($month > 0)
{
    if ($year > 0 && empty($day))
    $sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
    else if ($year > 0 && ! empty($day))
    $sql.= " AND f.datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
    else
    $sql.= " AND date_format(f.datef, '%m') = '".$month."'";
}
else if ($year > 0)
{
    $sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($month_lim > 0)
{
	if ($year_lim > 0 && empty($day_lim))
		$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,$month_lim,false))."' AND '".$db->idate(dol_get_last_day($year_lim,$month_lim,false))."'";
	else if ($year_lim > 0 && ! empty($day_lim))
		$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_lim, $day_lim, $year_lim))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month_lim, $day_lim, $year_lim))."'";
	else
		$sql.= " AND date_format(f.date_lim_reglement, '%m') = '".$month_lim."'";
}
else if ($year_lim > 0)
{
	$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,1,false))."' AND '".$db->idate(dol_get_last_day($year_lim,12,false))."'";
}
if ($option == 'late') $sql.=" AND f.date_lim_reglement < '".$db->idate(dol_now() - $conf->facture->client->warning_delay)."'";
if ($filter == 'paye:0') $sql.= " AND f.fk_statut = 1";
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0)
{
    $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='facture' AND tc.source='internal' AND ec.element_id = f.rowid AND ec.fk_socpeople = ".$search_user;
}
// Add where from extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    $typ=$extrafields->attribute_type[$tmpkey];
    $mode=0;
    if (in_array($typ, array('int','double'))) $mode=1;    // Search on a numeric
    if ($val && ( ($crit != '' && ! in_array($typ, array('select'))) || ! empty($crit)))
    {
        $sql .= natural_search('ef.'.$tmpkey, $crit, $mode);
    }
}
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

if (! $sall)
{
    $sql.= ' GROUP BY f.rowid, f.facnumber, ref_client, f.type, f.note_private, f.note_public, f.increment, f.fk_mode_reglement, f.total, f.tva, f.total_ttc,';
    $sql.= ' f.datef, f.date_lim_reglement,';
    $sql.= ' f.paye, f.fk_statut,';
    $sql.= ' f.datec, f.tms,';
    $sql.= ' s.rowid, s.nom, s.town, s.zip, s.fk_pays, s.code_client, s.client, typent.code,';
    $sql.= ' state.code_departement, state.nom';

    foreach ($extrafields->attribute_label as $key => $val) //prevent error with sql_mode=only_full_group_by
    {
        $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key : '');
    }
}
else
{
    $sql .= natural_search(array_keys($fieldstosearchall), $sall);
}

$sql.= ' ORDER BY ';
$listfield=explode(',',$sortfield);
foreach ($listfield as $key => $value) $sql.= $listfield[$key].' '.$sortorder.',';
$sql.= ' f.rowid DESC ';

$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit+1,$offset);
//print $sql;

$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);

	$arrayofselected=is_array($toselect)?$toselect:array();
    
    if ($socid)
    {
        $soc = new Societe($db);
        $soc->fetch($socid);
    }

    $param='&socid='.$socid;
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
    if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($sall)				 $param.='&sall='.$sall;
    if ($day)                $param.='&day='.urlencode($day);
    if ($month)              $param.='&month='.urlencode($month);
    if ($year)               $param.='&year=' .urlencode($year);
    if ($day_lim)            $param.='&day_lim='.urlencode($day_lim);
    if ($month_lim)          $param.='&month_lim='.urlencode($month_lim);
    if ($year_lim)           $param.='&year_lim=' .urlencode($year_lim);
    if ($search_ref)         $param.='&search_ref=' .urlencode($search_ref);
    if ($search_refcustomer) $param.='&search_refcustomer=' .urlencode($search_refcustomer);
    if ($search_societe)     $param.='&search_societe=' .urlencode($search_societe);
    if ($search_sale > 0)    $param.='&search_sale=' .urlencode($search_sale);
    if ($search_user > 0)    $param.='&search_user=' .urlencode($search_user);
    if ($search_product_category > 0)   $param.='$search_product_category=' .urlencode($search_product_category);
    if ($search_montant_ht != '')  $param.='&search_montant_ht='.urlencode($search_montant_ht);
    if ($search_montant_vat != '')  $param.='&search_montant_vat='.urlencode($search_montant_vat);
    if ($search_montant_ttc != '') $param.='&search_montant_ttc='.urlencode($search_montant_ttc);
	if ($search_status != '') $param.='&search_status='.urlencode($search_status);
	if ($search_paymentmode > 0) $param.='search_paymentmode='.urlencode($search_paymentmode);
    if ($show_files)         $param.='&show_files=' .$show_files;
	if ($option)             $param.="&option=".$option;
	if ($optioncss != '')    $param.='&optioncss='.$optioncss;
	// Add $param from extra fields
	foreach ($search_array_options as $key => $val)
	{
	    $crit=$val;
	    $tmpkey=preg_replace('/search_options_/','',$key);
	    if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
	}
	
	//V20:
	//$massactionbutton=$form->selectMassAction('', $massaction == 'presend' ? array() : array('presend'=>$langs->trans("SendByMail"), 'builddoc'=>$langs->trans("PDFMerge")));
    
    $i = 0;
    print '<form method="POST" name="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="action" value="list">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="search_status" value="'.$search_status.'">';
    print '<input type="hidden" name="id" value="'.$id.'">';
    
    //V20: Filters:
    //Status of tickets
    $limit=$num;	
    $moreforfilter='<td class="filtros">'.$langs->trans('Status'). ': ';
    
	$liststatus=array('0'=>$langs->trans("Opened"),
	    			'2'=>$langs->trans("BillShortStatusPaid"),
	    			'5'=>$langs->trans("All"));
    
    $but_name="'searchFormList'";
	$moreforfilter.= $form->selectarray('search_status', $liststatus, $search_status, 0,0,0,'onchange="auto_ok('.$but_name.')"');
    $moreforfilter.= '</td>';
    //Customer
    $moreforfilter.='<td class="filtros">'.$langs->trans('Customer'). ': ';
    $moreforfilter.=  $form->select_thirdparty_list('', 'socid', '(s.client = 1 OR s.client = 3)', 'SelectThirdParty',0,0,'','',0,0,'maxwidth300');
    $moreforfilter.='<button name="OK" type="submit">OK</button></td>';
    $moreforfilter.= '<td>'.$user->login.' <br> '.$zona_comercial['name'].'</td>';
    //print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_commercial.png', 0, $moreforfilter, '', $limit,1);
	print_barre_liste($langs->trans('TicketList').' '.($socid?' '.$soc->name:''),$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'title_accountancy.png',0,$moreforfilter,'',$limit,1);

	
    $varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
    //$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
	
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";
    		
    print '<tr class="liste_titre">';
    if (! empty($arrayfields['f.facnumber']['checked']))          print_liste_field_titre($arrayfields['f.facnumber']['label'],$_SERVER['PHP_SELF'],'f.facnumber','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['f.ref_client']['checked']))         print_liste_field_titre($arrayfields['f.ref_client']['label'],$_SERVER["PHP_SELF"],'f.ref_client','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['f.date']['checked']))               print_liste_field_titre($arrayfields['f.date']['label'],$_SERVER['PHP_SELF'],'f.datef','',$param,'align="center"',$sortfield,$sortorder);
    if (! empty($arrayfields['f.date_lim_reglement']['checked'])) print_liste_field_titre($arrayfields['f.date_lim_reglement']['label'],$_SERVER['PHP_SELF'],"f.date_lim_reglement",'',$param,'align="center"',$sortfield,$sortorder);
    if (! empty($arrayfields['s.nom']['checked']))                print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER['PHP_SELF'],'s.nom','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.town']['checked']))               print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],'s.town','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.zip']['checked']))                print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],'s.zip','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['state.nom']['checked']))            print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['country.code_iso']['checked']))     print_liste_field_titre($arrayfields['country.code_iso']['label'],$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['typent.code']['checked']))          print_liste_field_titre($arrayfields['typent.code']['label'],$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
    if (! empty($arrayfields['f.fk_mode_reglement']['checked']))  print_liste_field_titre($arrayfields['f.fk_mode_reglement']['label'],$_SERVER["PHP_SELF"],"f.fk_mode_reglement","",$param,"",$sortfield,$sortorder);
    if (! empty($arrayfields['f.total_ht']['checked']))           print_liste_field_titre($arrayfields['f.total_ht']['label'],$_SERVER['PHP_SELF'],'f.total','',$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['f.total_vat']['checked']))          print_liste_field_titre($arrayfields['f.total_vat']['label'],$_SERVER['PHP_SELF'],'f.tva','',$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['f.total_ttc']['checked']))          print_liste_field_titre($arrayfields['f.total_ttc']['label'],$_SERVER['PHP_SELF'],'f.total_ttc','',$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['dynamount_payed']['checked']))      print_liste_field_titre($arrayfields['dynamount_payed']['label'],$_SERVER['PHP_SELF'],'','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['rtp']['checked']))                  print_liste_field_titre($arrayfields['rtp']['label'],$_SERVER['PHP_SELF'],'','',$param,'align="right"',$sortfield,$sortorder);
    // Extra fields
    if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
    {
        foreach($extrafields->attribute_label as $key => $val)
        {
            if (! empty($arrayfields["ef.".$key]['checked']))
            {
                $align=$extrafields->getAlignFlag($key);
                print_liste_field_titre($extralabels[$key],$_SERVER["PHP_SELF"],"ef.".$key,"",$param,($align?'align="'.$align.'"':''),$sortfield,$sortorder);
            }
        }
    }
    // Hook fields
    $parameters=array('arrayfields'=>$arrayfields);
    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if (! empty($arrayfields['f.datec']['checked']))     print_liste_field_titre($arrayfields['f.datec']['label'],$_SERVER["PHP_SELF"],"f.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
    if (! empty($arrayfields['f.date_valid']['checked']))     print_liste_field_titre($arrayfields['f.date_valid']['label'],$_SERVER["PHP_SELF"],"f.date_valid","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);	//V20
    if (! empty($arrayfields['f.fk_user_valid']['checked']))     print_liste_field_titre($arrayfields['f.fk_user_valid']['label'],$_SERVER["PHP_SELF"],"f.fk_user_valid","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);	//V20
    if (! empty($arrayfields['f.tms']['checked']))       print_liste_field_titre($arrayfields['f.tms']['label'],$_SERVER["PHP_SELF"],"f.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
    if (! empty($arrayfields['f.fk_statut']['checked'])) print_liste_field_titre($arrayfields['f.fk_statut']['label'],$_SERVER["PHP_SELF"],"fk_statut,paye,type,dynamount_payed","",$param,'align="right"',$sortfield,$sortorder);
    //print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
    print "</tr>\n";

   
	
    if ($num > 0)
    {
        $i=0;
        $var=true;
        $totalarray=array();
        while ($i < min($num,$limit))
        {
            $obj = $db->fetch_object($resql);
            $var=!$var;

            $datelimit=$db->jdate($obj->datelimite);
            $facturestatic->id=$obj->facid;
            $facturestatic->ref=$obj->facnumber;
            $facturestatic->type=$obj->type;
            $facturestatic->statut=$obj->fk_statut;
            $facturestatic->date_lim_reglement=$db->jdate($obj->datelimite);

            $paiement = $facturestatic->getSommePaiement();
            $totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
            $totaldeposits = $facturestatic->getSumDepositsUsed();
            $totalpay = $paiement + $totalcreditnotes + $totaldeposits;
            $remaintopay = $obj->total_ttc - $totalpay;
            
            //Ref
            //print '<tr '.$bc[$var].'>';
            print '<tr class="oddeven" onclick="location.href=\'ticket_list.php?action=select&id='.$obj->facid.'\'">';
    		if (! empty($arrayfields['f.facnumber']['checked']))
    		{
                print '<td class="nowrap">';
    
                $notetoshow=dol_string_nohtmltag(($user->societe_id>0?$obj->note_public:$obj->note_private),1);

                print '<table class="nobordernopadding"><tr class="nocellnopadd">';
    
                print '<td class="nobordernopadding nowrap">';
                print $obj->facnumber;
                print $obj->increment;
                print '</td>';
    
                print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
                if (! empty($obj->note_private))
                {
    				print ' <span class="note">';
    				print '<a href="'.DOL_URL_ROOT.'/compta/facture/note.php?id='.$obj->facid.'">'.img_picto($langs->trans("ViewPrivateNote"),'object_generic').'</a>';
    				print '</span>';
    			}
                $filename=dol_sanitizeFileName($obj->facnumber);
                $filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($obj->facnumber);
                $urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->facid;
                print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
    			print '</td>';
                print '</tr>';
                print '</table>';
    
                print "</td>\n";
    		    if (! $i) $totalarray['nbfield']++;
    		}
    		
			// Customer ref
    		if (! empty($arrayfields['f.ref_client']['checked']))
    		{
        		print '<td class="nowrap">';
    			print $obj->ref_client;
    			print '</td>';
    		    if (! $i) $totalarray['nbfield']++;
    		}
    		
	       
    		
			// Date
    		if (! empty($arrayfields['f.date']['checked']))
    		{
        		print '<td align="center" class="nowrap">';
                print dol_print_date($db->jdate($obj->df),'%d/%m/%Y');	//V20
                print '</td>';
    		    if (! $i) $totalarray['nbfield']++;
    		}
	       
    		
            // Date limit
    		if (! empty($arrayfields['f.date_lim_reglement']['checked']))
    		{
        		print '<td align="center" class="nowrap">'.dol_print_date($datelimit,'day');
                if ($facturestatic->hasDelay())
                {
                    print img_warning($langs->trans('Late'));
                }
                print '</td>';
    		    if (! $i) $totalarray['nbfield']++;
    		}
    		
    		// Third party
    		if (! empty($arrayfields['s.nom']['checked']))
    		{
                print '<td>';
                $thirdparty=new Societe($db);
                $thirdparty->id=$obj->socid;
                $thirdparty->name=$obj->name;
                $thirdparty->client=$obj->client;
                $thirdparty->code_client=$obj->code_client;

                $thirdparty->fetch($obj->socid);
                
                print $obj->name.' ('.$thirdparty->name_alias.')';
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
    		}
    		// Town
    		if (! empty($arrayfields['s.town']['checked']))
    		{
    		    print '<td class="nocellnopadd">';
    		    print $obj->town;
    		    print '</td>';
    		    if (! $i) $totalarray['nbfield']++;
    		}
    		// Zip
    		if (! empty($arrayfields['s.zip']['checked']))
    		{
    		    print '<td class="nocellnopadd">';
    		    print $obj->zip;
    		    print '</td>';
    		    if (! $i) $totalarray['nbfield']++;
    		}
    		// State
    		if (! empty($arrayfields['state.nom']['checked']))
    		{
    		    print "<td>".$obj->state_name."</td>\n";
    		    if (! $i) $totalarray['nbfield']++;
    		}
    		// Country
    		if (! empty($arrayfields['country.code_iso']['checked']))
    		{
    		    print '<td align="center">';
    		    $tmparray=getCountry($obj->fk_pays,'all');
    		    print $tmparray['label'];
    		    print '</td>';
    		    if (! $i) $totalarray['nbfield']++;
    		}
    		// Type ent
    		if (! empty($arrayfields['typent.code']['checked']))
    		{
    		    print '<td align="center">';
    		    if (count($typenArray)==0) $typenArray = $formcompany->typent_array(1);
    		    print $typenArray[$obj->typent_code];
    		    print '</td>';
    		    if (! $i) $totalarray['nbfield']++;
    		}
    		
            // Payment mode
    		if (! empty($arrayfields['f.fk_mode_reglement']['checked']))
    		{
        		print '<td>';
                $form->form_modes_reglement($_SERVER['PHP_SELF'], $obj->fk_mode_reglement, 'none', '', -1);
                print '</td>';
    		    if (! $i) $totalarray['nbfield']++;
    		}
    		
            // Amount HT
            if (! empty($arrayfields['f.total_ht']['checked']))
            {
    		      print '<td align="right">'.price($obj->total_ht)."</td>\n";
    		      if (! $i) $totalarray['nbfield']++;
    		      if (! $i) $totalarray['totalhtfield']=$totalarray['nbfield'];
    		      $totalarray['totalht'] += $obj->total_ht;
            }
            // Amount VAT
            if (! empty($arrayfields['f.total_vat']['checked']))
            {
                print '<td align="right">'.price($obj->total_vat)."</td>\n";
                if (! $i) $totalarray['nbfield']++;
    		    if (! $i) $totalarray['totalvatfield']=$totalarray['nbfield'];
    		    $totalarray['totalvat'] += $obj->total_vat;
            }
            // Amount TTC
            if (! empty($arrayfields['f.total_ttc']['checked']))
            {
                print '<td align="right">'.price($obj->total_ttc)."</td>\n";
                if (! $i) $totalarray['nbfield']++;
    		    if (! $i) $totalarray['totalttcfield']=$totalarray['nbfield'];
    		    $totalarray['totalttc'] += $obj->total_ttc;
            }

            if (! empty($arrayfields['dynamount_payed']['checked']))
            {
                print '<td align="right">'.(! empty($totalpay)?price($totalpay,0,$langs):'&nbsp;').'</td>'; // TODO Use a denormalized field
                if (! $i) $totalarray['nbfield']++;
    		    if (! $i) $totalarray['totalamfield']=$totalarray['nbfield'];
    		    $totalarray['totalam'] += $totalpay;
            }

            if (! empty($arrayfields['rtp']['checked']))
            {
                print '<td align="right">'.(! empty($remaintopay)?price($remaintopay,0,$langs):'&nbsp;').'</td>'; // TODO Use a denormalized field
                if (! $i) $totalarray['nbfield']++;
    		    if (! $i) $totalarray['totalrtpfield']=$totalarray['nbfield'];
    		    $totalarray['totalrtp'] += $remaintopay;
            }
            
            // Extra fields
            if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
            {
                foreach($extrafields->attribute_label as $key => $val)
                {
                    if (! empty($arrayfields["ef.".$key]['checked']))
                    {
                        print '<td';
                        $align=$extrafields->getAlignFlag($key);
                        if ($align) print ' align="'.$align.'"';
                        print '>';
                        $tmpkey='options_'.$key;
                        //print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
                        print $obj->$tmpkey;
                        print '</td>';
                        if (! $i) $totalarray['nbfield']++;
                    }
                }
            }
            // Fields from hook
            $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
            $reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            // Date creation
            if (! empty($arrayfields['f.datec']['checked']))
            {
                print '<td align="center" class="nowrap">';
                print dol_print_date($db->jdate($obj->date_creation), '%d/%m/%Y %H:%M');
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }
         	// Date validated
    		if (! empty($arrayfields['f.date_valid']['checked']))
    		{
        		print '<td align="center" class="nowrap">';
                print dol_print_date($db->jdate($obj->date_valid),'%d/%m/%Y');	//V20
                print '</td>';
    		    if (! $i) $totalarray['nbfield']++;
    		}
	        //User validated
    		if (! empty($arrayfields['f.fk_user_valid']['checked']))
    		{
        		print '<td align="center" class="nowrap">';
        		if($obj->fk_user_valid>0 || $obj->fk_user_author>0){
	        		require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	        		$user_valid=new user($db);
	        		$user_valid->fetch(($obj->fk_user_valid>0 ? $obj->fk_user_valid : $obj->fk_user_author));	//V20: To see always an user. 
	                print $user_valid->login;	//V20
        		}
                print '</td>';
    		    if (! $i) $totalarray['nbfield']++;
    		}
            // Date modification
            if (! empty($arrayfields['f.tms']['checked']))
            {
                print '<td align="center" class="nowrap">';
                print dol_print_date($db->jdate($obj->date_update), 'dayhour');
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Status
            if (! empty($arrayfields['f.fk_statut']['checked']))
            {
                print '<td align="right" class="nowrap">';
                print $facturestatic->LibStatut($obj->paye,$obj->fk_statut,5,$paiement,$obj->type);
                print "</td>";
                if (! $i) $totalarray['nbfield']++;
            }
            
    		// Action column
            /*V20
            print '<td class="nowrap" align="center">';
            $selected=0;
    		if (in_array($obj->facid, $arrayofselected)) $selected=1;
    		print '<input id="cb'.$obj->facid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->facid.'"'.($selected?' checked="checked"':'').'>';
    		print '</td>' ;
    		if (! $i) $totalarray['nbfield']++;*/
				
            print "</tr>\n";

            $i++;
        }

    	// Show total line
    	if (isset($totalarray))
    	{
    		print '<tr class="liste_total">';
    		$i=0;
    		while ($i < $totalarray['nbfield'])
    		{
    		   $i++;
    		   if ($i == 1)
    	       {
            		if ($num < $limit) print '<td align="left">'.$langs->trans("Total").'</td>';
            		else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
    	       }
    		   elseif ($totalarray['totalhtfield'] == $i)  print '<td align="right">'.price($totalarray['totalht']).'</td>';
    		   elseif ($totalarray['totalvatfield'] == $i) print '<td align="right">'.price($totalarray['totalvat']).'</td>';
    		   elseif ($totalarray['totalttcfield'] == $i) print '<td align="right">'.price($totalarray['totalttc']).'</td>';
    		   elseif ($totalarray['totalamfield'] == $i)  print '<td align="right">'.price($totalarray['totalam']).'</td>';
			   elseif ($totalarray['totalrtpfield'] == $i)  print '<td align="right">'.price($totalarray['totalrtp']).'</td>';
    		   else print '<td></td>';
    		}
    		print '</tr>';
    		
    	}
    }

    $db->free($resql);
	
	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
    
	print "</table>\n";
    
    print "</form>\n";

}
else
{
    dol_print_error($db);
}

llxFooter();
$db->close();
?>