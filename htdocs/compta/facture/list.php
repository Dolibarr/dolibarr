<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2015 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2012 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2015-2016 Ferran Marcet		<fmarcet@2byte.es>
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
 *	\file       htdocs/compta/facture/list.php
 *	\ingroup    facture
 *	\brief      Page to create/see an invoice
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->projet->enabled))
{
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

$langs->load('bills');
$langs->load('companies');
$langs->load('products');
$langs->load('main');

$sall=trim(GETPOST('sall'));
$projectid=(GETPOST('projectid')?GETPOST('projectid','int'):0);

$id=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$confirm=GETPOST('confirm','alpha');
$lineid=GETPOST('lineid','int');
$userid=GETPOST('userid','int');
$search_product_category=GETPOST('search_product_category','int');
$search_ref=GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
$search_refcustomer=GETPOST('search_refcustomer','alpha');
$search_societe=GETPOST('search_societe','alpha');
$search_montant_ht=GETPOST('search_montant_ht','alpha');
$search_montant_ttc=GETPOST('search_montant_ttc','alpha');
$search_status=GETPOST('search_status','int');
$search_paymentmode=GETPOST('search_paymentmode','int');
$option = GETPOST('option');
if ($option == 'late') $filter = 'paye:0';

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$page = GETPOST("page",'int');
if ($page == -1) {
    $page = 0;
}
$offset = $limit * $page;
if (! $sortorder && ! empty($conf->global->INVOICE_DEFAULT_UNPAYED_SORT_ORDER) && $search_status == 1) $sortorder=$conf->global->INVOICE_DEFAULT_UNPAYED_SORT_ORDER;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='f.datef';

$pageprev = $page - 1;
$pagenext = $page + 1;

$search_user = GETPOST('search_user','int');
$search_sale = GETPOST('search_sale','int');
$day	= GETPOST('day','int');
$month	= GETPOST('month','int');
$year	= GETPOST('year','int');
$day_lim	= GETPOST('day_lim','int');
$month_lim	= GETPOST('month_lim','int');
$year_lim	= GETPOST('year_lim','int');
$filtre	= GETPOST('filtre');
$toselect = GETPOST('toselect', 'array');

// Security check
$fieldid = (! empty($ref)?'facnumber':'rowid');
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture', $id,'','','fk_soc',$fieldid);

$object=new Facture($db);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('invoicelist'));

$now=dol_now();

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'f.facnumber'=>'Ref',
    'f.ref_client'=>'RefCustomer',
    'fd.description'=>'Description',
    's.nom'=>"ThirdParty",
    'f.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["f.note_private"]="NotePrivate";


/*
 * Actions
 */

if (GETPOST('cancel')) { $action='list'; $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
if (empty($reshook))
{
	// Mass actions
	if (! empty($massaction) && count($toselect) < 1)
	{
		$error++;
		setEventMessages($langs->trans("NoLineChecked"), null, "warnings");
	}

	if (! $error && $massaction == 'confirm_presend')
	{
		$resaction = '';
		$nbsent = 0;
		$nbignored = 0;
		$langs->load("mails");
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		
		if (!isset($user->email))
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
						$langs->load("other");
						$resaction.='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
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
}

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_user='';
    $search_sale='';
    $search_product_category='';
    $search_ref='';
    $search_refcustomer='';
    $search_societe='';
    $search_montant_ht='';
    $search_montant_ttc='';
    $search_status='';
    $search_paymentmode='';
    $day='';
    $year='';
    $month='';
    $toselect='';
    $option='';
    $filter='';
    $day_lim='';
    $year_lim='';
    $month_lim='';
}

    

/*
 * View
 */

llxHeader('',$langs->trans('Bill'),'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes');

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$bankaccountstatic=new Account($db);
$facturestatic=new Facture($db);

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql.= ' f.rowid as facid, f.facnumber, f.ref_client, f.type, f.note_private, f.note_public, f.increment, f.fk_mode_reglement, f.total as total_ht, f.tva as total_tva, f.total_ttc,';
$sql.= ' f.datef as df, f.date_lim_reglement as datelimite,';
$sql.= ' f.paye as paye, f.fk_statut,';
$sql.= ' s.nom as name, s.rowid as socid, s.code_client, s.client ';
if (! $sall) $sql.= ', SUM(pf.amount) as am';   // To be able to sort on status
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql.= ', '.MAIN_DB_PREFIX.'facture as f';
if (! $sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
else $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as fd ON fd.fk_facture = f.rowid';
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
$sql.= " AND f.entity = ".$conf->entity;
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
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
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($search_montant_ht != '') $sql.= natural_search('f.total', $search_montant_ht, 1);
if ($search_montant_ttc != '') $sql.= natural_search('f.total_ttc', $search_montant_ttc, 1);
if ($search_status != '' && $search_status >= 0) $sql.= " AND f.fk_statut = ".$db->escape($search_status);
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
if (! $sall)
{
    $sql.= ' GROUP BY f.rowid, f.facnumber, ref_client, f.type, f.note_private, f.note_public, f.increment, f.total, f.tva, f.total_ttc,';
    $sql.= ' f.datef, f.date_lim_reglement,';
    $sql.= ' f.paye, f.fk_statut,';
    $sql.= ' s.nom, s.rowid, s.code_client, s.client';
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
    if ($day)                $param.='&day='.$day;
    if ($month)              $param.='&month='.$month;
    if ($year)               $param.='&year=' .$year;
    if ($day_lim)            $param.='&day_lim='.$day_lim;
    if ($month_lim)          $param.='&month_lim='.$month_lim;
    if ($year_lim)           $param.='&year_lim=' .$year_lim;
    if ($search_ref)         $param.='&search_ref=' .$search_ref;
    if ($search_refcustomer) $param.='&search_refcustomer=' .$search_refcustomer;
    if ($search_societe)     $param.='&search_societe=' .$search_societe;
    if ($search_sale > 0)    $param.='&search_sale=' .$search_sale;
    if ($search_user > 0)    $param.='&search_user=' .$search_user;
    if ($search_product_category > 0)   $param.='$search_product_category=' .$search_product_category;
    if ($search_montant_ht != '')  $param.='&search_montant_ht='.$search_montant_ht;
    if ($search_montant_ttc != '') $param.='&search_montant_ttc='.$search_montant_ttc;
	if ($search_status != '') $param.='&search_status='.$search_status;
	if ($search_paymentmode > 0) $param.='search_paymentmode='.$search_paymentmode;
	$param.=(! empty($option)?"&amp;option=".$option:"");
	
	$massactionbutton=$form->selectMassAction('', $massaction ? array() : array('presend'=>$langs->trans("SendByMail")));
    
    $i = 0;
    print '<form method="POST" name="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    
	print_barre_liste($langs->trans('BillsCustomers').' '.($socid?' '.$soc->name:''),$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,$massactionbutton,$num,$nbtotalofrecords,'title_accountancy.png');

	if ($massaction == 'presend')
	{
		$langs->load("mails");
		
		if (! GETPOST('cancel')) 
		{
			$objecttmp=new Facture($db);
			$listofselectedid=array();
			$listofselectedthirdparties=array();
			$listofselectedref=array();
			foreach($arrayofselected as $toselectid)
			{
				$result=$objecttmp->fetch($toselectid);
				if ($result > 0) 
				{
					$listofselectedid[$toselectid]=$toselectid;
					$thirdpartyid=$objecttmp->fk_soc?$objecttmp->fk_soc:$objecttmp->socid;
					$listofselectedthirdparties[$thirdpartyid]=$thirdpartyid;
					$listofselectedref[$thirdpartyid][$toselectid]=$objecttmp->ref;
				}
			}
		}

		print '<input type="hidden" name="massaction" value="confirm_presend">';
		
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);		
		
		dol_fiche_head(null, '', '');

		$topicmail="SendBillRef";
		$modelmail="facture_send";

		// Cree l'objet formulaire mail
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->withform=-1;
		$formmail->fromtype = 'user';
		$formmail->fromid   = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 1))	// If bit 1 is set
		{
			$formmail->trackid='inv'.$object->id;
		}
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
		{
			include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'inv'.$object->id);
		}
		$formmail->withfrom=1;
		$liste=$langs->trans("AllRecipientSelected");
		if (count($listofselectedthirdparties) == 1)
		{
			$liste=array();
			$thirdpartyid=array_shift($listofselectedthirdparties);
   			$soc=new Societe($db);
    		$soc->fetch($thirdpartyid);
        	foreach ($soc->thirdparty_and_contact_email_array(1) as $key=>$value)
        	{
        		$liste[$key]=$value;
        	}
			$formmail->withtoreadonly=0;
		}
		else
		{
			$formmail->withtoreadonly=1;
		}
		$formmail->withto=$liste;
		$formmail->withtofree=0;
		$formmail->withtocc=1;
		$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
		$formmail->withtopic=$langs->transnoentities($topicmail, '__REF__', '__REFCLIENT__');
		$formmail->withfile=$langs->trans("OnlyPDFattachmentSupported");
		$formmail->withbody=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withcancel=1;
		// Tableau des substitutions
		$formmail->substit['__REF__']='__REF__';	// We want to keep the tag
		$formmail->substit['__SIGNATURE__']=$user->signature;
		$formmail->substit['__REFCLIENT__']='__REFCLIENT__';	// We want to keep the tag
		$formmail->substit['__PERSONALIZED__']='';
		$formmail->substit['__CONTACTCIVNAME__']='';

		// Tableau des parametres complementaires du post
		$formmail->param['action']=$action;
		$formmail->param['models']=$modelmail;
		$formmail->param['models_id']=GETPOST('modelmailselected','int');
		$formmail->param['facid']=join(',',$arrayofselected);
		//$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;

		print $formmail->get_form();
        
        dol_fiche_end();
	}
	
	
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';

    if ($sall)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
    }
    
 	// If the user can view prospects other than his'
    $moreforfilter='';
 	if ($user->rights->societe->client->voir || $socid)
 	{
 		$langs->load("commercial");
 		$moreforfilter.='<div class="divsearchfield">';
 		$moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth300');
	 	$moreforfilter.='</div>';
 	}
    // If the user can view prospects other than his'
    if ($user->rights->societe->client->voir || $socid)
    {
		$moreforfilter.='<div class="divsearchfield">';
    	$moreforfilter.=$langs->trans('LinkedToSpecificUsers'). ': ';
        $moreforfilter.=$form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
	 	$moreforfilter.='</div>';
    }
	// If the user can view prospects other than his'
	if ($conf->categorie->enabled && ($user->rights->produit->lire || $user->rights->service->lire))
	{
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('IncludingProductWithTag'). ': ';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
		$moreforfilter.=$form->selectarray('search_product_category', $cate_arbo, $search_product_category, 1, 0, 0, '', 0, 0, 0, 0, '', 1);
		$moreforfilter.='</div>';
	}
    $parameters=array();
    $reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

    if ($moreforfilter)
    {
   		print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        print '</div>';
    }

    print '<table class="liste '.($moreforfilter?"listwithfilterbefore":"").'">';
    		
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'f.facnumber','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('RefCustomer'),$_SERVER["PHP_SELF"],'f.ref_client','',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'f.datef','',$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DateDue"),$_SERVER['PHP_SELF'],"f.date_lim_reglement",'',$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('ThirdParty'),$_SERVER['PHP_SELF'],'s.nom','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("PaymentModeShort"),$_SERVER["PHP_SELF"],"f.fk_mode_reglement","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('AmountHT'),$_SERVER['PHP_SELF'],'f.total','',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Taxes'),$_SERVER['PHP_SELF'],'f.tva','',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('AmountTTC'),$_SERVER['PHP_SELF'],'f.total_ttc','',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Received'),$_SERVER['PHP_SELF'],'am','',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'fk_statut,paye,am','',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
    print "</tr>\n";

    // Filters lines
    print '<tr class="liste_titre">';
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
    print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" size="6" type="text" name="search_refcustomer" value="'.$search_refcustomer.'">';
	print '</td>';
    print '<td class="liste_titre" align="center">';
    if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
    print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
    $formother->select_year($year?$year:-1,'year',1, 20, 5);
    print '</td>';
 	print '<td class="liste_titre" align="center">';
    if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day_lim" value="'.$day_lim.'">';
    print '<input class="flat" type="text" size="1" maxlength="2" name="month_lim" value="'.$month_lim.'">';
    $formother->select_year($year_lim?$year_lim:-1,'year_lim',1, 20, 5);
	print '<br><input type="checkbox" name="option" value="late"'.($option == 'late'?' checked':'').'> '.$langs->trans("Late");
    print '</td>';
    print '<td class="liste_titre" align="left"><input class="flat" type="text" size="8" name="search_societe" value="'.$search_societe.'"></td>';
	print '<td class="liste_titre" align="left">';
	$form->select_types_paiements($search_paymentmode, 'search_paymentmode', '', 0, 0, 1, 10);
	print '</td>';
    print '<td class="liste_titre" align="right"><input class="flat" type="text" size="6" name="search_montant_ht" value="'.$search_montant_ht.'"></td>';
    print '<td class="liste_titre"></td>';
    print '<td class="liste_titre" align="right"><input class="flat" type="text" size="6" name="search_montant_ttc" value="'.$search_montant_ttc.'"></td>';
    print '<td class="liste_titre"></td>';
    print '<td class="liste_titre" align="right">';
	$liststatus=array('0'=>$langs->trans("BillShortStatusDraft"), '1'=>$langs->trans("BillShortStatusNotPaid"), '2'=>$langs->trans("BillShortStatusPaid"), '3'=>$langs->trans("BillShortStatusCanceled"));
	print $form->selectarray('search_status', $liststatus, $search_status, 1);
    print '</td>';
    print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print "</td></tr>\n";

    if ($num > 0)
    {
        $var=true;
        $total_ht=0;
        $total_tva=0;
        $total_ttc=0;
        $totalrecu=0;

        while ($i < min($num,$limit))
        {
            $objp = $db->fetch_object($resql);
            $var=!$var;

            $datelimit=$db->jdate($objp->datelimite);

            print '<tr '.$bc[$var].'>';
            print '<td class="nowrap">';

            $facturestatic->id=$objp->facid;
            $facturestatic->ref=$objp->facnumber;
            $facturestatic->type=$objp->type;
            $facturestatic->statut=$objp->fk_statut;
            $facturestatic->date_lim_reglement=$db->jdate($objp->datelimite);
            $notetoshow=dol_string_nohtmltag(($user->societe_id>0?$objp->note_public:$objp->note_private),1);
            $paiement = $facturestatic->getSommePaiement();

            print '<table class="nobordernopadding"><tr class="nocellnopadd">';

            print '<td class="nobordernopadding nowrap">';
            print $facturestatic->getNomUrl(1,'',200,0,$notetoshow);
            print $objp->increment;
            print '</td>';

            print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
            if (! empty($objp->note_private))
            {
				print ' <span class="note">';
				print '<a href="'.DOL_URL_ROOT.'/compta/facture/note.php?id='.$objp->facid.'">'.img_picto($langs->trans("ViewPrivateNote"),'object_generic').'</a>';
				print '</span>';
			}
            $filename=dol_sanitizeFileName($objp->facnumber);
            $filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($objp->facnumber);
            $urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->facid;
            print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
			print '</td>';
            print '</tr>';
            print '</table>';

            print "</td>\n";

			// Customer ref
			print '<td class="nowrap">';
			print $objp->ref_client;
			print '</td>';

			// Date
            print '<td align="center" class="nowrap">';
            print dol_print_date($db->jdate($objp->df),'day');
            print '</td>';

            // Date limit
            print '<td align="center" class="nowrap">'.dol_print_date($datelimit,'day');
            if ($facturestatic->hasDelay())
            {
                print img_warning($langs->trans('Late'));
            }
            print '</td>';

            print '<td>';
            $thirdparty=new Societe($db);
            $thirdparty->id=$objp->socid;
            $thirdparty->name=$objp->name;
            $thirdparty->client=$objp->client;
            $thirdparty->code_client=$objp->code_client;
            print $thirdparty->getNomUrl(1,'customer');
            print '</td>';

            // Payment mode
            print '<td>';
            $form->form_modes_reglement($_SERVER['PHP_SELF'], $objp->fk_mode_reglement, 'none', '', -1);
            print '</td>';
            
            print '<td align="right">'.price($objp->total_ht,0,$langs).'</td>';

            print '<td align="right">'.price($objp->total_tva,0,$langs).'</td>';

            print '<td align="right">'.price($objp->total_ttc,0,$langs).'</td>';

            print '<td align="right">'.(! empty($paiement)?price($paiement,0,$langs):'&nbsp;').'</td>';

            // Status
            print '<td align="right" class="nowrap">';
            print $facturestatic->LibStatut($objp->paye,$objp->fk_statut,5,$paiement,$objp->type);
            print "</td>";

			// Checkbox
            print '<td class="nowrap" align="center">';
            $selected=0;
			if (in_array($objp->facid, $arrayofselected)) $selected=1;
			print '<input id="cb'.$objp->facid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$objp->facid.'"'.($selected?' checked="checked"':'').'>';
			print '</td>' ;

            print "</tr>\n";
            $total_ht+=$objp->total_ht;
            $total_tva+=$objp->total_tva;
            $total_ttc+=$objp->total_ttc;
            $totalrecu+=$paiement;
            $i++;
        }

        if (($offset + $num) <= $limit)
        {
            // Print total
            print '<tr class="liste_total">';
            print '<td class="liste_total" colspan="6" align="left">'.$langs->trans('Total').'</td>';
            print '<td class="liste_total" align="right">'.price($total_ht,0,$langs).'</td>';
            print '<td class="liste_total" align="right">'.price($total_tva,0,$langs).'</td>';
            print '<td class="liste_total" align="right">'.price($total_ttc,0,$langs).'</td>';
            print '<td class="liste_total" align="right">'.price($totalrecu,0,$langs).'</td>';
            print '<td class="liste_total"></td>';
            print '<td class="liste_total"></td>';
            print '</tr>';
        }
    }

    print "</table>\n";
    print "</form>\n";
    $db->free($resql);
}
else
{
    dol_print_error($db);
}

llxFooter();
$db->close();
