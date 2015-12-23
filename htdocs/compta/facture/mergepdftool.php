<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
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
 *
 * Note: Page can be call with param mode=sendremind to bring feature to send
 * remind by emails.
 */

/**
 *		\file       htdocs/compta/facture/mergepdftool.php
 *		\ingroup    facture
 *		\brief      Page to list and build doc of selected invoices
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->load("mails");
$langs->load("bills");

$id = (GETPOST('facid','int') ? GETPOST('facid','int') : GETPOST('id','int'));
$action = GETPOST('action','alpha');
$option = GETPOST('option');
$mode=GETPOST('mode');
$builddoc_generatebutton=GETPOST('builddoc_generatebutton');
$month = GETPOST("month","int");
$year = GETPOST("year","int");
$filter = GETPOST("filtre");
if (GETPOST('button_search') || GETPOST('button_search.x') || GETPOST('button_search_x'))
{
	$filter=GETPOST('filtre',2);
	//if ($filter != 'payed:0') $option='';
}
if ($option == 'late') $filter = 'paye:0';
if ($option == 'unpaidall') $filter = 'paye:0';
if ($mode == 'sendmassremind' && $filter == '') $filter = 'paye:0';
if ($filter == '') $filter = 'paye:0';

$search_user = GETPOST('search_user','int');
$search_sale = GETPOST('search_sale','int');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user,'facture',$id,'');

$diroutputpdf=$conf->facture->dir_output . '/unpaid/temp';
if (! $user->rights->societe->client->voir || $socid) $diroutputpdf.='/private/'.$user->id;	// If user has no permission to see all, output dir is specific to user

$resultmasssend='';
if (GETPOST('buttonsendremind')) 
{
	$action='presend';
	$mode='sendmassremind';
}

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter"))		// Both test must be present to be compatible with all browsers
{
	$search_ref="";
	$search_ref_supplier="";
	$search_user = "";
	$search_sale = "";
	$search_label="";
	$search_company="";
	$search_amount_no_tax="";
	$search_amount_all_tax="";
	$year="";
	$month="";
	$filter="";
	$option="";
}



/*
 * Action
 */

// Send remind email
if ($action == 'presend' && GETPOST('cancel'))
{
	$action='';
	if (GETPOST('models')=='facture_relance') $mode='sendmassremind';	// If we made a cancel from submit email form, this means we must be into mode=sendmassremind
}

if ($action == 'presend' && GETPOST('sendmail'))
{
	if (GETPOST('models')=='facture_relance') $mode='sendmassremind';	// If we made a cancel from submit email form, this means we must be into mode=sendmassremind

	if (!isset($user->email))
	{
		$error++;
		setEventMessages($langs->trans("NoSenderEmailDefined"), null, 'warnings');
	}

	$countToSend = count($_POST['toSend']);
	if (empty($countToSend))
	{
		$error++;
		setEventMessages($langs->trans("InvoiceNotChecked"), null, 'warnings');
	}

	if (! $error)
	{
		$nbsent = 0;
		$nbignored = 0;

		$arrayofinvoices=GETPOST('toSend','array');
		
		$thirdparty=new Societe($db);
		$invoicetmp=new Facture($db);
		$listofinvoicesid=array();
		$listofinvoicesthirdparties=array();
		$listofinvoicesref=array();
		foreach($arrayofinvoices as $invoiceid)
		{
			$invoicetmp=new Facture($db);	// must create new instance because instance is saved into $listofinvoicesref array for future use
			$result=$invoicetmp->fetch($invoiceid);
			if ($result > 0) 
			{
				$listoinvoicesid[$invoiceid]=$invoiceid;
				$thirdpartyid=$invoicetmp->fk_soc?$invoicetmp->fk_soc:$invoicetmp->socid;
				$listofinvoicesthirdparties[$thirdpartyid]=$thirdpartyid;
				$listofinvoicesref[$thirdpartyid][$invoiceid]=$invoicetmp;
			}
		}
		//var_dump($listofinvoicesref);exit;
		
		foreach ($listofinvoicesthirdparties as $thirdpartyid)
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
			
			//var_dump($listofinvoicesref[$thirdpartyid]);
			
			$attachedfiles=array('paths'=>array(), 'names'=>array(), 'mimes'=>array());
			$listofqualifiedinvoice=array();
			$listofqualifiedref=array();
			foreach($listofinvoicesref[$thirdpartyid] as $invoiceid => $invoice)
			{
				//var_dump($invoice);
				$object = $invoice;
				//$object = new Facture($db);
				//$result = $object->fetch();
				//var_dump($thirdpartyid.' - '.$invoiceid.' - '.$object->statut);
				
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
				$mime = 'application/pdf';

				if (dol_is_file($file))
				{
					if (empty($sendto)) 	// For the case, no recipient were set (multi thirdparties send)
					{
						$object->fetch_thirdparty();
						$sendto = $object->thirdparty->email;
					}

					if (empty($sendto)) 
					{
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

					$listofqualifiedinvoice[$invoiceid]=$invoice;
					$listofqualifiedref[$invoiceid]=$invoice->ref;
				}
				else
				{  
					$nbignored++;
					$langs->load("other");
					$resultmasssend.='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
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
					$resultmasssend.='<div class="error">'.$mailfile->error.'</div>';
				}
				else
				{
					$result=$mailfile->sendfile();
					if ($result)
					{
						$resultmasssend.=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));		// Must not contain "

						$error=0;

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
							$object->elementtype	= $invoice->element;
	
							// Appel des triggers
							include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
							$interface=new Interfaces($db);
							$result=$interface->run_triggers('BILL_SENTBYMAIL',$object,$user,$langs,$conf);
							if ($result < 0) { $error++; $this->errors=$interface->errors; }
							// Fin appel triggers
	
							if (! $error)
							{
								$resultmasssend.=$langs->trans("MailSent").': '.$sendto."<br>\n";
							}
							else
							{
								dol_print_error($db);
							}
							$nbsent++;
						}
					}
					else
					{
						$langs->load("other");
						if ($mailfile->error)
						{
							$resultmasssend.=$langs->trans('ErrorFailedToSendMail',$from,$sendto);
							$resultmasssend.='<br><div class="error">'.$mailfile->error.'</div>';
						}
						else
						{
							$resultmasssend.='<div class="warning">No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS</div>';
						}
					}
				}
			}
		}

		if ($nbsent)
		{
			$action='';	// Do not show form post if there was at least one successfull sent
			setEventMessages($nbsent. '/'.$countToSend.' '.$langs->trans("RemindSent"), null, 'mesgs');
		}
		else
		{
			setEventMessages($langs->trans("NoRemindSent"), null, 'warnings');  // May be object has no generated PDF file
		}
	}
}


if ($action == "builddoc" && $user->rights->facture->lire && ! GETPOST('button_search') && !empty($builddoc_generatebutton))
{
	if (is_array($_POST['toGenerate']))
	{
	    $arrayofinclusion=array();
	    foreach($_POST['toGenerate'] as $tmppdf) $arrayofinclusion[]=preg_quote($tmppdf.'.pdf','/');
		$factures = dol_dir_list($conf->facture->dir_output,'all',1,implode('|',$arrayofinclusion),'\.meta$|\.png','date',SORT_DESC,0,true);

		// liste les fichiers
		$files = array();
		$factures_bak = $factures ;
		foreach($_POST['toGenerate'] as $basename)
		{
			foreach($factures as $facture)
			{
				if (strstr($facture["name"],$basename))
				{
					$files[] = $conf->facture->dir_output.'/'.$basename.'/'.$facture["name"];
				}
			}
		}

        // Define output language (Here it is not used because we do only merging existing PDF)
        $outputlangs = $langs;
        $newlang='';
        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
        if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
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
		dol_mkdir($diroutputpdf);

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
			$file=$diroutputpdf.'/'.$filename.'_'.dol_print_date($now,'dayhourlog').'.pdf';
			$pdf->Output($file,'F');
			if (! empty($conf->global->MAIN_UMASK))
			@chmod($file, octdec($conf->global->MAIN_UMASK));

			$langs->load("exports");
			setEventMessages($langs->trans('FileSuccessfullyBuilt',$filename.'_'.dol_print_date($now,'dayhourlog')), null, 'mesgs');
		}
		else
		{
			setEventMessages($langs->trans('NoPDFAvailableForChecked'), null, 'errors');
		}
	}
	else
	{
		setEventMessages($langs->trans('InvoiceNotChecked'), null, 'warnings');
	}
}

// Remove file
if ($action == 'remove_file')
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$langs->load("other");
	$upload_dir = $diroutputpdf;
	$file = $upload_dir . '/' . GETPOST('file');
	$ret=dol_delete_file($file);
	if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
	else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
	$action='';
}



/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formother = new FormOther($db);

$title=$langs->trans("MergingPDFTool");

llxHeader('',$title);

?>
<script type="text/javascript">
$(document).ready(function() {
	$("#checkall").click(function() {
		$(".checkformerge").prop('checked', true);
	});
	$("#checknone").click(function() {
		$(".checkformerge").prop('checked', false);
	});
	$("#checkallsend").click(function() {
		$(".checkforsend").prop('checked', true);
	});
	$("#checknonesend").click(function() {
		$(".checkforsend").prop('checked', false);
	});
});
</script>
<?php

$now=dol_now();

$search_ref = GETPOST("search_ref");
$search_refcustomer=GETPOST('search_refcustomer');
$search_societe = GETPOST("search_societe");
$search_paymentmode = GETPOST("search_paymentmode");
$search_montant_ht = GETPOST("search_montant_ht");
$search_montant_ttc = GETPOST("search_montant_ttc");
$late = GETPOST("late");

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_ref='';
    $search_refcustomer='';
    $search_societe='';
    $search_paymentmode='';
    $search_montant_ht='';
    $search_montant_ttc='';
}

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="f.date_lim_reglement";
if (! $sortorder) $sortorder="ASC";

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;

$sql = "SELECT s.nom as name, s.rowid as socid, s.email";
$sql.= ", f.rowid as facid, f.facnumber, f.ref_client, f.increment, f.total as total_ht, f.tva as total_tva, f.total_ttc, f.localtax1, f.localtax2, f.revenuestamp";
$sql.= ", f.datef as df, f.date_lim_reglement as datelimite";
$sql.= ", f.paye as paye, f.fk_statut, f.type, f.fk_mode_reglement";
$sql.= ", sum(pf.amount) as am";
if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", sc.fk_soc, sc.fk_user ";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ",".MAIN_DB_PREFIX."facture as f";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid=pf.fk_facture ";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
    $sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
    $sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= " WHERE f.fk_soc = s.rowid";
$sql.= " AND f.entity = ".$conf->entity;
$sql.= " AND f.type IN (0,1,3,5)";
if ($filter == 'paye:0') $sql.= " AND f.fk_statut = 1";
//$sql.= " AND f.paye = 0";
if ($option == 'late') $sql.=" AND f.date_lim_reglement < '".$db->idate(dol_now() - $conf->facture->client->warning_delay)."'";
if (! $user->rights->societe->client->voir && ! $socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if (! empty($socid)) $sql .= " AND s.rowid = ".$socid;
if ($filter && $filter != -1)		// GETPOST('filtre') may be a string
{
	$filtrearr = explode(",", $filter);
	foreach ($filtrearr as $fil)
	{
		$filt = explode(":", $fil);
		$sql .= " AND " . $filt[0] . " = " . $filt[1];
	}
}
if ($search_ref)         $sql .= " AND f.facnumber LIKE '%".$db->escape($search_ref)."%'";
if ($search_refcustomer) $sql .= " AND f.ref_client LIKE '%".$db->escape($search_refcustomer)."%'";
if ($search_societe)     $sql .= " AND s.nom LIKE '%".$db->escape($search_societe)."%'";
if ($search_paymentmode) $sql .= " AND f.fk_mode_reglement = ".$search_paymentmode."";
if ($search_montant_ht)  $sql .= " AND f.total = '".$db->escape($search_montant_ht)."'";
if ($search_montant_ttc) $sql .= " AND f.total_ttc = '".$db->escape($search_montant_ttc)."'";
if (GETPOST('sf_ref'))   $sql .= " AND f.facnumber LIKE '%".$db->escape(GETPOST('sf_ref'))."%'";
if ($month > 0)
{
	if ($year > 0)
	$sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
	else
	$sql.= " AND date_format(f.datef, '%m') = '$month'";
}
else if ($year > 0)
{
	$sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0)
{
    $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='facture' AND tc.source='internal' AND ec.element_id = f.rowid AND ec.fk_socpeople = ".$search_user;
}
$sql.= " GROUP BY s.nom, s.rowid, s.email, f.rowid, f.facnumber, f.ref_client, f.increment, f.total, f.tva, f.total_ttc, f.localtax1, f.localtax2, f.revenuestamp,";
$sql.= " f.datef, f.date_lim_reglement, f.paye, f.fk_statut, f.type, f.fk_mode_reglement";
if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", sc.fk_soc, sc.fk_user ";
$sql.= " ORDER BY ";
$listfield=explode(',',$sortfield);
foreach ($listfield as $key => $value) $sql.=$listfield[$key]." ".$sortorder.",";
$sql.= " f.facnumber DESC";
//print $sql;
//$sql .= $db->plimit($limit+1,$offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	if (! empty($socid))
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
	}

	$param="";
	$param.=(! empty($socid)?"&amp;socid=".$socid:"");
	if ($search_ref)         $param.='&amp;search_ref='.urlencode($search_ref);
   	if ($search_refcustomer) $param.='&amp;search_ref='.urlencode($search_refcustomer);
	if ($search_societe)     $param.='&amp;search_societe='.urlencode($search_societe);
	if ($search_societe)     $param.='&amp;search_paymentmode='.urlencode($search_paymentmode);
	if ($search_montant_ht)  $param.='&amp;search_montant_ht='.urlencode($search_montant_ht);
	if ($search_montant_ttc) $param.='&amp;search_montant_ttc='.urlencode($search_montant_ttc);
	if ($late)               $param.='&amp;late='.urlencode($late);
	if ($mode)               $param.='&amp;mode='.urlencode($mode);
	$urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource.=str_replace('&amp;','&',$param);

	//$titre=(! empty($socid)?$langs->trans("BillsCustomersUnpaidForCompany",$soc->name):$langs->trans("BillsCustomersUnpaid"));
	$titre=(! empty($socid)?$langs->trans("BillsCustomersForCompany",$soc->name):$langs->trans("BillsCustomers"));
	if ($option == 'late') $titre.=' ('.$langs->trans("Late").')';
	//else $titre.=' ('.$langs->trans("All").')';

	$link='';
	//if (empty($option) || $option == 'late') $link.=($link?' - ':'').'<a href="'.$_SERVER["PHP_SELF"].'?option=unpaidall'.$param.'">'.$langs->trans("ShowUnpaidAll").'</a>';
	//if (empty($option) || $option == 'unpaidall') $link.=($link?' - ':'').'<a href="'.$_SERVER["PHP_SELF"].'?option=late'.$param.'">'.$langs->trans("ShowUnpaidLateOnly").'</a>';

	$param.=(! empty($option)?"&amp;option=".$option:"");

	print load_fiche_titre($titre,$link);
	//print_barre_liste($titre,$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',0);	// We don't want pagination on this page

	$arrayofinvoices=GETPOST('toSend','array');
	if ($action == 'presend' && count($arrayofinvoices) == 0 && ! GETPOST('cancel')) 
	{
		setEventMessages($langs->trans("InvoiceNotChecked"), null, 'errors');
		$action='list';
		$mode='sendmassremind';
	}
	else 
	{
		$invoicetmp=new Facture($db);
		$listofinvoicesid=array();
		$listofinvoicesthirdparties=array();
		$listofinvoicesref=array();
		foreach($arrayofinvoices as $invoiceid)
		{
			$result=$invoicetmp->fetch($invoiceid);
			if ($result > 0) 
			{
				$listofinvoicesid[$invoiceid]=$invoiceid;
				$thirdpartyid=$invoicetmp->fk_soc?$invoicetmp->fk_soc:$invoicetmp->socid;
				$listofinvoicesthirdparties[$thirdpartyid]=$thirdpartyid;
				$listofinvoicesref[$thirdpartyid][$invoiceid]=$invoicetmp->ref;
			}
		}
	}
	print '<form id="form_unpaid" method="POST" action="'.$_SERVER["PHP_SELF"].'?sortfield='. $sortfield .'&sortorder='. $sortorder .'">';

	if (GETPOST('modelselected')) {
		$action = 'presend';
	}
	if (! empty($mode) && $action == 'presend')
	{
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);

		dol_fiche_head(null, '', $langs->trans("SendRemind"));

		$topicmail="MailTopicSendRemindUnpaidInvoices";
		$modelmail="facture_relance";

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
		$liste=$langs->trans("AllRecipientSelectedForRemind");
		if (count($listofinvoicesthirdparties) == 1)
		{
			$liste=array();
			$thirdpartyid=array_shift($listofinvoicesthirdparties);
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
		//$formmail->substit['__REF__']='';
		$formmail->substit['__SIGNATURE__']=$user->signature;
		//$formmail->substit['__REFCLIENT__']='';
		$formmail->substit['__PERSONALIZED__']='';
		$formmail->substit['__CONTACTCIVNAME__']='';

		// Tableau des parametres complementaires du post
		$formmail->param['action']=$action;
		$formmail->param['models']=$modelmail;
		$formmail->param['models_id']=GETPOST('modelmailselected','int');
		$formmail->param['facid']=join(',',$arrayofinvoices);
		$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;

		print $formmail->get_form();
        
        dol_fiche_end();
	}

	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="mode" value="'.$mode.'">';
	if ($late) print '<input type="hidden" name="late" value="'.dol_escape_htmltag($late).'">';

	if ($resultmasssend)
	{
		print '<br><strong>'.$langs->trans("ResultOfMassSending").':</strong><br>'."\n";
		print $langs->trans("Selected").': '.$countToSend."\n<br>";
		print $langs->trans("Ignored").': '.$nbignored."\n<br>";
		print $langs->trans("Sent").': '.$nbsent."\n<br>";
		//print $resultmasssend;
		print '<br>';
	}

	$i = 0;

 	// If the user can view prospects other than his'
    $moreforfilter='';
 	if ($user->rights->societe->client->voir || $socid)
 	{
 		$langs->load("commercial");
 		$moreforfilter.='<div class="divsearchfield">';
 		$moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale,'search_sale',$user, 0, 1, 'maxwidth300');
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
    if (! empty($moreforfilter))
    {
        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        $parameters=array();
        $reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        print '</div>';
    }

    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">';
    
    print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"f.facnumber","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('RefCustomer'),$_SERVER["PHP_SELF"],'f.ref_client','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"f.datef","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateDue"),$_SERVER["PHP_SELF"],"f.date_lim_reglement","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ThirdParty"),$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("PaymentMode"),$_SERVER["PHP_SELF"],"f.fk_mode_reglement","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"f.total","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Taxes"),$_SERVER["PHP_SELF"],"f.tva","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"f.total_ttc","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Received"),$_SERVER["PHP_SELF"],"am","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Rest"),$_SERVER["PHP_SELF"],"","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"fk_statut,paye,am","",$param,'align="right"',$sortfield,$sortorder);

	$searchpitco='<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	$searchpitco.='<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Reset"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	if (empty($mode))
	{
		print_liste_field_titre($searchpitco,$_SERVER["PHP_SELF"],"","",$param,'align="center"',$sortfield,$sortorder);
	}
	else
	{
		print_liste_field_titre($searchpitco,$_SERVER["PHP_SELF"],"","",$param,'align="center"',$sortfield,$sortorder);
	}
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';
	// Ref
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_ref" value="'.$search_ref.'"></td>';
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_refcustomer" value="'.$search_refcustomer.'">';
    print '</td>';
	print '<td class="liste_titre" align="center">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
	$syear = $year;
	$formother->select_year($syear?$syear:-1,'year',1, 20, 5);
	print '</td>';
	// Late
	print '<td class="liste_titre" align="center">';
	print '<input type="checkbox" name="option" value="late"'.($option == 'late'?' checked':'').'> '.$langs->trans("Late");
	print '</td>';
	print '<td class="liste_titre" align="left"><input class="flat" type="text" size="10" name="search_societe" value="'.dol_escape_htmltag($search_societe).'"></td>';
	print '<td class="liste_titre" align="left">';
	$form->select_types_paiements($search_paymentmode, 'search_paymentmode', '', 0, 0, 1);
	print '</td>';
	print '<td class="liste_titre" align="right"><input class="flat" type="text" size="8" name="search_montant_ht" value="'.dol_escape_htmltag($search_montant_ht).'"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right"><input class="flat" type="text" size="8" name="search_montant_ttc" value="'.dol_escape_htmltag($search_montant_ttc).'"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right">';
	$liststatus=array('paye:0'=>$langs->trans("Unpaid"), 'paye:1'=>$langs->trans("Paid"));
	print $form->selectarray('filtre', $liststatus, $filter, 1);
	print '</td>';
	print '<td class="liste_titre" align="center">';
	if (empty($mode))
	{
		if ($conf->use_javascript_ajax) print '<a href="#" id="checkall">'.$langs->trans("All").'</a> / <a href="#" id="checknone">'.$langs->trans("None").'</a>';
	}
	else
	{
		if ($conf->use_javascript_ajax) print '<a href="#" id="checkallsend">'.$langs->trans("All").'</a> / <a href="#" id="checknonesend">'.$langs->trans("None").'</a>';
	}
	print '</td>';
	print "</tr>\n";

	if ($num > 0)
	{
		$var=true;
		$total_ht=0;
		$total_tva=0;
		$total_ttc=0;
		$total_paid=0;

		$facturestatic=new Facture($db);

		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);
			$date_limit=$db->jdate($objp->datelimite);

			$var=!$var;

			print "<tr ".$bc[$var].">";
			$classname = "impayee";

			print '<td class="nowrap">';

			$facturestatic->id=$objp->facid;
			$facturestatic->ref=$objp->facnumber;
			$facturestatic->type=$objp->type;
			$facturestatic->statut=$objp->fk_statut;
			$facturestatic->date_lim_reglement= $db->jdate($objp->datelimite);

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';

			// Ref
			print '<td class="nobordernopadding nowrap">';
			print $facturestatic->getNomUrl(1);
			print '</td>';

			// Warning picto
			print '<td width="20" class="nobordernopadding nowrap">';
			if ($facturestatic->hasDelay()) {
				print img_warning($langs->trans("Late"));
			}
			print '</td>';

			// PDF Picto
			print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
            $filename=dol_sanitizeFileName($objp->facnumber);
			$filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($objp->facnumber);
			print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
            print '</td>';

			print '</tr></table>';

			print "</td>\n";

			// Customer ref
			print '<td class="nowrap">';
			print $objp->ref_client;
			print '</td>';

			print '<td class="nowrap" align="center">'.dol_print_date($db->jdate($objp->df),'day').'</td>'."\n";
			print '<td class="nowrap" align="center">'.dol_print_date($db->jdate($objp->datelimite),'day').'</td>'."\n";

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


			print '<td align="right">'.price($objp->total_ht).'</td>';
			print '<td align="right">'.price($objp->total_tva);
			$tx1=price2num($objp->localtax1);
			$tx2=price2num($objp->localtax2);
			$revenuestamp=price2num($objp->revenuestamp);
			if (! empty($tx1) || ! empty($tx2) || ! empty($revenuestamp)) print '+'.price($tx1 + $tx2 + $revenuestamp);
			print '</td>';
			print '<td align="right">'.price($objp->total_ttc).'</td>';
			print '<td align="right">';
			$cn=$facturestatic->getSumCreditNotesUsed();
			$dep=$facturestatic->getSumDepositsUsed();
			if (! empty($objp->am)) print price($objp->am);
			if (! empty($objp->am) && ! empty($cn)) print '+';
			if (! empty($cn)) print price($cn);
			if (! empty($dep)) print price(-$dep);
			print '</td>';

			// Remain to receive
			print '<td align="right">'.(((! empty($objp->total_ttc) || ! empty($objp->am) || ! empty($cn) || ! empty($dep)) && ($objp->total_ttc - $objp->am - $cn - $dep)) ? price($objp->total_ttc - $objp->am - $cn - $dep):'&nbsp;').'</td>';

			// Status of invoice
			print '<td align="right" class="nowrap">';
			print $facturestatic->LibStatut($objp->paye,$objp->fk_statut,5,$objp->am);
			print '</td>';

			if (empty($mode))
			{
				// Checkbox to merge
				print '<td align="center">';
				if (! empty($formfile->infofiles['extensions']['pdf']))
					print '<input id="cb'.$objp->facid.'" class="flat checkformerge" type="checkbox" name="toGenerate[]" value="'.$objp->facnumber.'">';
				print '</td>' ;
			}
			else
			{
				// Checkbox to send remind
				print '<td class="nowrap" align="center">';
				$selected=0;
				if (in_array($objp->facid, $arrayofinvoices)) $selected=1;
				if ($objp->email) print '<input class="flat checkforsend" type="checkbox" name="toSend[]" value="'.$objp->facid.'"'.($selected?' checked="checked"':'').'>';
				else print img_picto($langs->trans("NoEMail"), 'warning.png');
				print '</td>' ;
			}

			print "</tr>\n";
			$total_ht+=$objp->total_ht;
			$total_tva+=($objp->total_tva + $tx1 + $tx2 + $revenuestamp);
			$total_ttc+=$objp->total_ttc;
			$total_paid+=$objp->am + $cn + $dep;

			$i++;
		}

		print '<tr class="liste_total">';
		print '<td colspan="6" align="left">'.$langs->trans("Total").'</td>';
		print '<td align="right"><b>'.price($total_ht).'</b></td>';
		print '<td align="right"><b>'.price($total_tva).'</b></td>';
		print '<td align="right"><b>'.price($total_ttc).'</b></td>';
		print '<td align="right"><b>'.price($total_paid).'</b></td>';
		print '<td align="right"><b>'.price($total_ttc - $total_paid).'</b></td>';
		print '<td align="center">&nbsp;</td>';
		print '<td align="center">&nbsp;</td>';
		print "</tr>\n";
	}

	print "</table>";


	if (empty($mode))
	{
		/*
		 * Show list of available documents
		 */
		$filedir=$diroutputpdf;
		$genallowed=$user->rights->facture->lire;
		$delallowed=$user->rights->facture->lire;

		print '<br>';
		// We disable multilang because we concat already existing pdf.
		$formfile->show_documents('unpaid','',$filedir,$urlsource,$genallowed,$delallowed,'',1,1,0,48,1,$param,$langs->trans("PDFMerge"),$langs->trans("PDFMerge"));
	}
	else
	{
		if ($action != 'presend')
		{
			print '<div class="tabsAction">';
			print '<input type="submit" class="butAction" name="buttonsendremind" value="'.dol_escape_htmltag($langs->trans("SendRemind")).'">';
			print '</div>';
			print '<br>';
		}
	}

	print '</form>';

	$db->free($resql);
}
else dol_print_error($db,'');


llxFooter();
$db->close();
