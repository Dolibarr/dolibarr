<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

/**
 *  \file       	htdocs/expensereport/card.php
 *  \brief      	Page for trip and expense card
 */

$res=0;
require '../main.inc.php';
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formmail.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formprojet.class.php");
require_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/trip.lib.php");
dol_include_once('/expensereport/core/modules/expensereport/modules_expensereport.php');
dol_include_once("/expensereport/class/expensereport.class.php");

$langs->load("trips");

$action=GETPOST('action');
$date_start = dol_mktime(0, 0, 0, GETPOST('date_debutmonth'), GETPOST('date_debutday'), GETPOST('date_debutyear'));
$date_end = dol_mktime(0, 0, 0, GETPOST('date_finmonth'), GETPOST('date_finday'), GETPOST('date_finyear'));


// If socid provided by ajax company selector
if (! empty($_REQUEST['socid_id']))
{
	$_GET['socid'] = $_GET['socid_id'];
	$_POST['socid'] = $_POST['socid_id'];
	$_REQUEST['socid'] = $_REQUEST['socid_id'];
}

// Security check
$id=GETPOST("id",'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expensereport', 0, 'expensereport');

$mesg="";


// Hack to use expensereport dir
$rootfordata = DOL_DATA_ROOT;
$rootforuser = DOL_DATA_ROOT;
// If multicompany module is enabled, we redefine the root of data
if (! empty($conf->multicompany->enabled) && ! empty($conf->entity) && $conf->entity > 1)
{
	$rootfordata.='/'.$conf->entity;
}
$conf->expensereport->dir_output = $rootfordata.'/expensereport';
$conf->expensereport->dir_output = $rootfordata.'/expensereport';



/*
 * Actions
 */

if ($action == 'confirm_delete' && $_GET["confirm"] == "yes" && $id > 0 && $user->rights->expensereport->supprimer)
{
	$object = new ExpenseReport($db);
	$result=$object->delete($id);
	if ($result >= 0)
	{
		header("Location: index.php");
		exit;
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == 'add' && $user->rights->expensereport->creer)
{
	$object = new ExpenseReport($db);

	$object->date_debut = $date_start;
	$object->date_fin = $date_end;

	$object->fk_user_validator			= GETPOST('fk_user_validator','int');
	$object->fk_c_expensereport_statuts = 1;
	$object->fk_c_paiement				= GETPOST('fk_c_paiement','int');
	$object->note						= GETPOST('note');

	if ($object->periode_existe($user,dol_print_date($object->date_debut, 'dayrfc'),dol_print_date($object->date_fin, 'dayrfc')))
	{
		setEventMessage($langs->trans("ErrorDoubleDeclaration"),'errors');
		$action='create';
	}
	else
	{
		$db->begin();

		$id = $object->create($user);

		if ($id > 0)
		{
			$db->commit();
			Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
			$db->rollback();
			$action='create';
		}
	}
}

if ($action == 'update' && $user->rights->expensereport->creer)
{
	$object = new ExpenseReport($db);
	$object->fetch($_POST['id'],$user);

	$object->date_debut = $date_start;
	$object->date_fin = $date_end;

	if($object->fk_c_expensereport_statuts < 3)
	{
		$object->fk_user_validator = GETPOST('fk_user_validator','int');
	}

	$object->fk_c_paiement = GETPOST('fk_c_paiement','int');
	$object->note = GETPOST('note');

	$result = $object->update($user);
	if ($result > 0)
	{
		header("Location: ".$_SEVER["PHP_SELF"]."?id=".$_POST['id']);
		exit;
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == "confirm_save" && GETPOST("confirm") == "yes" && $id > 0 && $user->rights->expensereport->creer)
{
	$object = new ExpenseReport($db);
	$object->fetch($id,$user);
	$result = $object->set_save($user);
	if ($result > 0)
	{
		// Send mail
		if (! empty($conf->global->DEPLACEMENT_TO_CLEAN))
		{
			// Send mail

			// TO
			$destinataire = new User($db);
			$destinataire->fetch($object->fk_user_validator);
			$emailTo = $destinataire->email;

			// FROM
			$expediteur = new User($db);
			$expediteur->fetch($object->fk_user_author);
			$emailFrom = $expediteur->email;

			// SUBJECT
			$subject = "' ERP - Note de frais à valider";

			// CONTENT
			$message = "Bonjour {$destinataire->firstname},\n\n";
			$message.= "Veuillez trouver en pièce jointe une nouvelle note de frais à valider.\n";
			$message.= "- Déclarant : {$expediteur->firstname} {$expediteur->lastname}\n";
			$message.= "- Période : du {$object->date_debut} au {$object->date_fin}\n";
			$message.= "- Lien : {$dolibarr_main_url_root}/expensereport/card.php?id={$object->id}\n\n";
			$message.= "Bien cordialement,\n' SI";

			// Génération du pdf avant attachement
			$object->setDocModel($user,"");
			$resultPDF = expensereport_pdf_create($db,$id,'',"",$langs);

			if($resultPDF):
			// ATTACHMENT
			$filename=array(); $filedir=array(); $mimetype=array();
			array_push($filename,dol_sanitizeFileName($object->ref_number).".pdf");
			array_push($filedir,$conf->expensereport->dir_output . "/" . dol_sanitizeFileName($object->ref_number) . "/" . dol_sanitizeFileName($object->ref_number).".pdf");
			array_push($mimetype,"application/pdf");

			// PREPARE SEND
			$mailfile = new CMailFile($subject,$emailTo,$emailFrom,$message,$filedir,$mimetype,$filename);

			if(!$mailfile->error):

			// SEND
			$result=$mailfile->sendfile();
			if ($result):
			Header("Location: ".$_SEVER["PHP_SELF"]."?id=".$id);
			exit;
			endif;

			else:

			$mesg="Impossible d'envoyer l'email.";

			endif;
			// END - Send mail
			else:
			dol_print_error($db,$resultPDF);
			exit;
			endif;
		}
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == "confirm_save_from_refuse" && $_GET["confirm"] == "yes" && $id > 0 && $user->rights->expensereport->creer)
{
	$object = new ExpenseReport($db);
	$object->fetch($id,$user);
	$result = $object->set_save_from_refuse($user);
	if ($result > 0)
	{
		// Send mail
		if (! empty($conf->global->DEPLACEMENT_TO_CLEAN))
		{
			// TO
			$destinataire = new User($db);
			$destinataire->fetch($object->fk_user_validator);
			$emailTo = $destinataire->email;

			// FROM
			$expediteur = new User($db);
			$expediteur->fetch($object->fk_user_author);
			$emailFrom = $expediteur->email;

			// SUBJECT
			$subject = "' ERP - Note de frais à re-approuver";

			// CONTENT
			$dateRefusEx = explode(" ",$object->date_refuse);

			$message = "Bonjour {$destinataire->firstname},\n\n";
			$message.= "Le {$dateRefusEx[0]} à {$dateRefusEx[1]} vous avez refusé d'approuver la note de frais \"{$object->ref_number}\". Vous aviez émis le motif suivant : {$object->detail_refuse}\n\n";
			$message.= "L'auteur vient de modifier la note de frais, veuillez trouver la nouvelle version en pièce jointe.\n";
			$message.= "- Déclarant : {$expediteur->firstname} {$expediteur->lastname}\n";
			$message.= "- Période : du {$object->date_debut} au {$object->date_fin}\n";
			$message.= "- Lien : {$dolibarr_main_url_root}/expensereport/card.php?id={$object->id}\n\n";
			$message.= "Bien cordialement,\n' SI";

			// Génération du pdf avant attachement
			$object->setDocModel($user,"");
			$resultPDF = expensereport_pdf_create($db,$id,'',"",$langs);

			if($resultPDF):
			// ATTACHMENT
			$filename=array(); $filedir=array(); $mimetype=array();
			array_push($filename,dol_sanitizeFileName($object->ref_number).".pdf");
			array_push($filedir,$conf->expensereport->dir_output . "/" . dol_sanitizeFileName($object->ref_number) . "/" . dol_sanitizeFileName($object->ref_number).".pdf");
			array_push($mimetype,"application/pdf");

			// PREPARE SEND
			$mailfile = new CMailFile($subject,$emailTo,$emailFrom,$message,$filedir,$mimetype,$filename);

			if(!$mailfile->error):

			// SEND
			$result=$mailfile->sendfile();
			if ($result):
			Header("Location: ".$_SEVER["PHP_SELF"]."?id=".$id);
			exit;
			endif;

			else:

			$mesg="Impossible d'envoyer l'email.";

			endif;
			// END - Send mail
			else:
			dol_print_error($db,$resultPDF);
			exit;
			endif;
		}
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Approve
if ($action == "confirm_validate" && GETPOST("confirm") == "yes" && $id > 0 && $user->rights->expensereport->to_validate)
{
	$object = new ExpenseReport($db);
	$object->fetch($id,$user);

	$result = $object->set_valide($user);
	if ($result > 0)
	{
		if (! empty($conf->global->DEPLACEMENT_TO_CLEAN))
		{
			$object = new ExpenseReport($db);
			$object->fetch($id,$user);

			// Send mail

			// TO
			$destinataire = new User($db);
			$destinataire->fetch($object->fk_user_author);
			$emailTo = $destinataire->email;

			// CC
			$emailCC = $conf->global->NDF_CC_EMAILS;

			// FROM
			$expediteur = new User($db);
			$expediteur->fetch($object->fk_user_valid);
			$emailFrom = $expediteur->email;

			// SUBJECT
			$subject = "' ERP - Note de frais validée";

			// CONTENT
			$message = "Bonjour {$destinataire->firstname},\n\n";
			$message.= "Votre note de frais \"{$object->ref_number}\" vient d'être approuvé!\n";
			$message.= "- Approbateur : {$expediteur->firstname} {$expediteur->lastname}\n";
			$message.= "- Lien : {$dolibarr_main_url_root}/expensereport/card.php?id={$object->id}\n\n";
			$message.= "Bien cordialement,\n' SI";

			// Génération du pdf avant attachement
			$object->setDocModel($user,"");
			$resultPDF = expensereport_pdf_create($db,$id,'',"",$langs);

			if($resultPDF):
				// ATTACHMENT
				$filename=array(); $filedir=array(); $mimetype=array();
				array_push($filename,dol_sanitizeFileName($object->ref_number).".pdf");
				array_push($filedir, $conf->expensereport->dir_output.
					"/".
					dol_sanitizeFileName($object->ref_number) .
					"/".
					dol_sanitizeFileName($object->ref_number).
					".pdf"
					);
				array_push($mimetype,"application/pdf");

				// PREPARE SEND
				$mailfile = new CMailFile($subject,$emailTo,$emailFrom,$message,$filedir,$mimetype,$filename,$emailCC);

				if(!$mailfile->error):

					// SEND
					$result=$mailfile->sendfile();
					if ($result):
						setEventMessage($langs->trans("MailSuccessfulySent",$emailFrom,$emailTo));
						Header("Location: ".$_SEVER["PHP_SELF"]."?id=".$id);
						exit;
					else:
						setEventMessage($langs->trans("ErrorFailedToSendMail",$emailFrom,$emailTo),'errors');
					endif;

				else:
					setEventMessage($langs->trans("ErrorFailedToSendMail",$emailFrom,$emailTo),'errors');
				endif;
				// END - Send mail
			else : // if ($resultPDF)
				dol_print_error($db,$resultPDF);
				exit;
			endif;
		}
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == "confirm_refuse" && $_POST['confirm']=="yes" && !empty($_POST['detail_refuse']) && $id > 0 && $user->rights->expensereport->to_validate)
{
	$object = new ExpenseReport($db);
	$object->fetch($id,$user);

	$result = $object->set_refuse($user,$_POST['detail_refuse']);
	if ($result > 0)
	{
		if (! empty($conf->global->DEPLACEMENT_TO_CLEAN))
		{
			$object = new ExpenseReport($db);
			$object->fetch($id,$user);

			// Send mail

			// TO
			$destinataire = new User($db);
			$destinataire->fetch($object->fk_user_author);
			$emailTo = $destinataire->email;

			// FROM
			$expediteur = new User($db);
			$expediteur->fetch($object->fk_user_refuse);
			$emailFrom = $expediteur->email;

			// SUBJECT
			$subject = "' ERP - Note de frais refusée";

			// CONTENT
			$message = "Bonjour {$destinataire->firstname},\n\n";
			$message.= "Votre note de frais \"{$object->ref_number}\" vient d'être refusée.\n";
			$message.= "- Refuseur : {$expediteur->firstname} {$expediteur->lastname}\n";
			$message.= "- Motif de refus : {$_POST['detail_refuse']}\n";
			$message.= "- Lien : {$dolibarr_main_url_root}/expensereport/card.php?id={$object->id}\n\n";
			$message.= "Bien cordialement,\n' SI";

			// PREPARE SEND
			$mailfile = new CMailFile($subject,$emailTo,$emailFrom,$message);

			if(!$mailfile->error)
			{
				// SEND
				$result=$mailfile->sendfile();
				if ($result)
				{
					setEventMessage($langs->trans("MailSuccessfulySent",$emailFrom,$emailTo));
					Header("Location: ".$_SEVER["PHP_SELF"]."?id=".$id);
					exit;
				}
				else
				{
					setEventMessage($langs->trans("ErrorFailedToSendMail",$emailFrom,$emailTo),'errors');
					$mesg="Impossible d'envoyer l'email.";
				}
				// END - Send mail
			}
		}
	}
	else
	{
		setEventMessage($object->error, $object->errors);
	}
}

if ($action == "confirm_cancel" && GETPOST('confirm')=="yes" && !empty($_POST['detail_cancel']) && $id > 0 && $user->rights->expensereport->to_validate)
{
	$object = new ExpenseReport($db);
	$object->fetch($id,$user);
	if($user->id == $object->fk_user_validator)
	{
		$result = $object->set_cancel($user,$_POST['detail_cancel']);

		if ($result > 0)
		{
			if (! empty($conf->global->DEPLACEMENT_TO_CLEAN))
			{
				$object = new ExpenseReport($db);
				$object->fetch($id,$user);

				// Send mail

				// TO
				$destinataire = new User($db);
				$destinataire->fetch($object->fk_user_author);
				$emailTo = $destinataire->email;

				// FROM
				$expediteur = new User($db);
				$expediteur->fetch($object->fk_user_cancel);
				$emailFrom = $expediteur->email;

				// SUBJECT
				$subject = "' ERP - Note de frais annulée";

				// CONTENT
				$message = "Bonjour {$destinataire->firstname},\n\n";
				$message.= "Votre note de frais \"{$object->ref_number}\" vient d'être annulée.\n";
				$message.= "- Annuleur : {$expediteur->firstname} {$expediteur->lastname}\n";
				$message.= "- Motif d'annulation : {$_POST['detail_cancel']}\n";
				$message.= "- Lien : {$dolibarr_main_url_root}/expensereport/card.php?id={$object->id}\n\n";
				$message.= "Bien cordialement,\n' SI";

				// PREPARE SEND
				$mailfile = new CMailFile($subject,$emailTo,$emailFrom,$message);

				if(!$mailfile->error)
				{
					// SEND
					$result=$mailfile->sendfile();
					if ($result)
					{
						header("Location: ".$_SEVER["PHP_SELF"]."?id=".$id);
						exit;
					}
					else
					{
						$mesg="Impossible d'envoyer l'email.";
					}
					// END - Send mail
				}
				else
				{
					setEventMessages($mail->error, $mail->errors, 'errors');
				}
			}
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	else
	{
		setEventMessages($langs->transnoentitiesnoconv("NOT_VALIDATOR"), '', 'errors');
	}
}

if ($action == "confirm_paid" && $_GET['confirm']=="yes" && $id > 0 && $user->rights->expensereport->to_paid)
{
	$object = new ExpenseReport($db);
	$object->fetch($id,$user);
	$result = $object->set_paid($user);
	if ($result > 0)
	{
		if (! empty($conf->global->DEPLACEMENT_TO_CLEAN))
		{
			$object = new ExpenseReport($db);
			$object->fetch($id,$user);

			// Send mail

			// TO
			$destinataire = new User($db);
			$destinataire->fetch($object->fk_user_author);
			$emailTo = $destinataire->email;

			// FROM
			$expediteur = new User($db);
			$expediteur->fetch($object->fk_user_paid);
			$emailFrom = $expediteur->email;

			// SUBJECT
			$subject = "' ERP - Note de frais payée";

			// CONTENT
			$message = "Bonjour {$destinataire->firstname},\n\n";
			$message.= "Votre note de frais \"{$object->ref_number}\" vient d'être payée.\n";
			$message.= "- Payeur : {$expediteur->firstname} {$expediteur->lastname}\n";
			$message.= "- Lien : {$dolibarr_main_url_root}/expensereport/card.php?id={$object->id}\n\n";
			$message.= "Bien cordialement,\n' SI";

			// Génération du pdf avant attachement
			$object->setDocModel($user,"");
			$resultPDF = expensereport_pdf_create($db,$id,'',"",$langs);

			// PREPARE SEND
			$mailfile = new CMailFile($subject,$emailTo,$emailFrom,$message);

			if(!$mailfile->error):

			// SEND
			$result=$mailfile->sendfile();
			if ($result):
			// Insert écriture dans le compte courant
			$idTrip 	= $id;
			$idAccount 	= 1;

			$object = new ExpenseReport($db);
			$object->fetch($idTrip,$user);

			$datePaiement = explode("-",$object->date_paiement);

			$dateop 	= dol_mktime(12,0,0,$datePaiement[1],$datePaiement[2],$datePaiement[0]);
			$operation	= $object->code_paiement;
			$label		= "Règlement ".$object->ref_number;
			$amount 	= - price2num($object->total_ttc);
			$num_chq	= '';
			$cat1		= '';

			$user = new User($db);
			$user->fetch($object->fk_user_paid);

			$acct=new Account($db,$idAccount);
			$insertid = $acct->addline($dateop, $operation, $label, $amount, $num_chq, $cat1, $user);

			if ($insertid > 0):
			$sql = " UPDATE ".MAIN_DB_PREFIX."expensereport d";
			$sql.= " SET integration_compta = 1, fk_bank_account = $idAccount";
			$sql.= " WHERE rowid = $idTrip";
			$resql=$db->query($sql);
			if($result):
			Header("Location: ".$_SEVER["PHP_SELF"]."?id=".$id);
			exit;
			else:
			dol_print_error($db);
			endif;
			else:
			dol_print_error($db,$acct->error);
			endif;
			endif;

			else:

			$mesg="Impossible d'envoyer l'email.";

			endif;
			// END - Send mail
		}
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == "confirm_brouillonner" && $_GET['confirm']=="yes" && $id > 0 && $user->rights->expensereport->creer)
{
	$object = new ExpenseReport($db);
	$object->fetch($id,$user);
	if($user->id == $object->fk_user_author OR $user->id == $object->fk_user_validator)
	{
		$result = $object->set_draft($user);
		if ($result > 0)
		{
			header("Location: ".$_SEVER["PHP_SELF"]."?id=".$id);
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	else
	{
		setEventMessages($langs->transnoentitiesnoconv("NOT_AUTHOR"), '', 'errors');
	}
}

if ($action == "addline")
{
	$db->begin();

	$object_ligne = new ExpenseReportLigne($db);

	$object_ligne->comments = empty($_POST['comments'])?"Aucun commentaire.":$_POST['comments'];
	$object_ligne->qty  = empty($_POST['qty'])?1:$_POST['qty'];

	// Convertion de "," en "." dans le nombre entré dans le champ
	if(preg_match("#,#",$_POST['value_unit']))
	{
		$object_ligne->value_unit = preg_replace("#,#",".",$_POST['value_unit']);
	}
	else
	{
		$object_ligne->value_unit = $_POST['value_unit'];
	}

	$object_ligne->value_unit = number_format($object_ligne->value_unit,3,'.','');

	$date = explode("/",$_POST['date']);
	$object_ligne->date = $date[2]."-".$date[1]."-".$date[0];

	$object_ligne->fk_c_type_fees = empty($_POST['fk_c_type_fees'])?1:$_POST['fk_c_type_fees'];

	// Get tax id from rate
	$tva_tx=GETPOST('fk_c_tva');
	$sql  = "SELECT t.rowid, t.localtax1, t.localtax2, t.localtax1_type, t.localtax2_type";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as p";
	$sql .= " WHERE t.fk_pays = p.rowid AND p.code = '".$mysoc->country_code."'";
	$sql .= " AND t.taux = ".$db->escape($tva_tx)." AND t.active = 1";

	dol_syslog("get_localtax sql=".$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		$tva=$obj->rowid;
	}
	else dol_print_error($db);

	// Force la TVA à 0% lorsque c'est du transport
	if (! empty($conf->global->DEPLACEMENT_TO_CLEAN))
	{
		if ($object_ligne->fk_c_type_fees==10)
		{
			$tva = 15;		// TODO A virer le hardcoding
		}
	}

	$object_ligne->fk_c_tva = $tva;

	$object_ligne->fk_projet = $_POST['fk_projet'];

	// Tests des données rentrées
	$error = false;

	// Si aucun projet n'est défini
	if (empty($object_ligne->fk_projet) || $object_ligne->fk_projet==-1)
	{
		$error = true;
		$text_error[] = "NO_PROJECT";
	}

	// Si aucune date n'est rentrée
	if($object_ligne->date=="--"):
	$error = true;
	$text_error[] = "NO_DATE";
	endif;

	// Si aucun prix n'est rentré
	if($object_ligne->value_unit==0):
	$error = true;
	$text_error[] = "NO_PRICE";
	endif;

	// S'il y'a eu au moins une erreur
	if($error)
	{
		$mesg = implode(",",$text_error);
		Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$_POST['fk_expensereport']."&mesg=$mesg");
		exit;
	}
	else
	{
		$object_ligne->fk_expensereport = $_POST['fk_expensereport'];

		$object_ligne->fetch_taux($object_ligne->fk_c_tva);

		// Calculs des totos
		$object_ligne->total_ttc = $object_ligne->value_unit * $object_ligne->qty;
		$object_ligne->total_ttc = number_format($object_ligne->total_ttc,2,'.','');

		$tx_tva = $object_ligne->tva_taux/100;
		$tx_tva	= $tx_tva + 1;

		$object_ligne->total_ht = $object_ligne->total_ttc / $tx_tva;
		$object_ligne->total_ht = price2num($object_ligne->total_ht,'MT');

		$object_ligne->total_tva = $object_ligne->total_ttc - $object_ligne->total_ht;
		// Fin calculs des totos

		$result = $object_ligne->insert();
		if ($result > 0)
		{
			$object = new ExpenseReport($db);
			$object->fetch($_POST['fk_expensereport'],$user);
			$object->update_totaux_add($object_ligne->total_ht,$object_ligne->total_tva);

			$db->commit();
			Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$_POST['fk_expensereport']);
			exit;
		}
		else
		{
			dol_print_error($db,$object->error);
			$db->rollback();
			exit;
		}
	}
}

if ($action == 'confirm_delete_line' && $_POST["confirm"] == "yes")
{
	$object = new ExpenseReport($db);
	$object->fetch($_GET["id"],$user);

	$object_ligne = new ExpenseReportLigne($db);
	$object_ligne->fetch($_GET["rowid"]);
	$total_ht = $object_ligne->total_ht;
	$total_tva = $object_ligne->total_tva;

	$result=$object->deleteline($_GET["rowid"]);
	if ($result >= 0)
	{
		$object->update_totaux_del($object_ligne->total_ht,$object_ligne->total_tva);
		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$_GET['id']);
		exit;
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == "updateligne" )
{
	$object = new ExpenseReport($db);

	$object_id = GETPOST('id','int');
	$object->fetch($object_id,$user);

	$rowid = $_POST['rowid'];
	$type_fees_id = empty($_POST['fk_c_type_fees'])?1:$_POST['fk_c_type_fees'];

	// Get tax id from rate
	$tva_tx=GETPOST('fk_c_tva');
	$sql  = "SELECT t.rowid, t.localtax1, t.localtax2, t.localtax1_type, t.localtax2_type";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as p";
	$sql .= " WHERE t.fk_pays = p.rowid AND p.code = '".$mysoc->country_code."'";
	$sql .= " AND t.taux = ".$db->escape($tva_tx)." AND t.active = 1";

	dol_syslog("get_localtax sql=".$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		$c_tva=$obj->rowid;
	}
	else dol_print_error($db);

	// Force la TVA à 0% lorsque c'est du transport
	if ($type_fees_id==10)
	{
		$c_tva = 15;
	}

	$object_ligne->fk_c_tva = $c_tva;
	$projet_id = $_POST['fk_projet'];
	$comments = $_POST['comments'];
	$qty = $_POST['qty'];

	// Convertion de "," en "." dans le nombre entré dans le champ
	if(preg_match("#,#",$_POST['value_unit'])):
	$value_unit = preg_replace("#,#",".",$_POST['value_unit']);
	else:
	$value_unit = $_POST['value_unit'];
	endif;

	$date = explode("/",$_POST['date']);
	$date = $date[2]."-".$date[1]."-".$date[0];

	$result = $object->updateline($rowid, $type_fees_id, $projet_id, $c_tva, $comments, $qty, $value_unit, $date, $object_id);
	if ($result >= 0)
	{
		$object->recalculer($object_id);
		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object_id);
		exit;
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == "recalc" && $id > 0)
{
	$object = new ExpenseReport($db);
	$object->fetch($id);
	if($object->recalculer($id) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$_GET['id']);
		exit;
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

/*
 * Generer ou regenerer le document PDF
 */
if ($action == 'builddoc')	// En get ou en post
{
	$depl = new ExpenseReport($db, 0, $_GET['id']);
	$depl->fetch($_GET['id'],$user);

	if ($_REQUEST['model'])
	{
		$depl->setDocModel($user, $_REQUEST['model']);
	}

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	$result=expensereport_pdf_create($db, $depl->id, '', $depl->modelpdf, $outputlangs);
	if ($result <= 0)
	{
		dol_print_error($db,$result);
		exit;
	}
	else
	{
		Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$depl->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
		exit;
	}
}

// Remove file in doc form
else if ($action == 'remove_file')
{
	$object = new ExpenseReport($db, 0, $_GET['id']);
	if ($object->fetch($id))
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$object->fetch_thirdparty();

		$langs->load("other");
		$upload_dir = $conf->expensereport->dir_output;
		$file = $upload_dir . '/' . GETPOST('file');
		$ret=dol_delete_file($file,0,0,0,$object);
		if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
		else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
		$action='';
	}
}


/*
 * View
 */

llxHeader('', $langs->trans("ExpenseReport"));

$html = new Form($db);
$formfile = new FormFile($db);
$form = new Form($db);
$formproject = new FormProjets($db);

if (! empty($conf->global->DEPLACEMENT_TO_CLEAN))
{
	if(!empty($_GET['mesg']))
	{
		$text_mesg = explode(",",$_GET['mesg']);

		foreach($text_mesg as $text)
		{
			$mesg.= "- ".$langs->trans($text)."<br />";
		}

		print "<div class=\"error\" style=\"font-size:15px;background-color:#FFB3B3;\">";
		print $langs->trans("LINE_NOT_ADDED")."<br />";
		print $mesg;
		print "</div>";
	}
	else
	{
		if ($mesg) print "<div class=\"error\" style=\"font-size:16px;background-color:red;\">".$mesg."</div>";
	}
}


/*
 * Create
 */

if ($action == 'create')
{
	print print_fiche_titre($langs->trans("NewTrip"));

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="post" name="create">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	dol_fiche_head('');

	print '<table class="border" width="100%">';
	print '<tbody>';
	print '<tr>';
	print '<td>'.$langs->trans("DateStart").'</td>';
	print '<td>';
	$html->select_date($date_start?$date_start:-1,'date_debut',0,0,0,'',1,1);
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td>'.$langs->trans("DateEnd").'</td>';
	print '<td>';
	$html->select_date($date_end?$date_end:-1,'date_fin',0,0,0,'',1,1);
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td>'.$langs->trans("VALIDATOR").'</td>';
	print '<td>';
	$object = new ExpenseReport($db);
	$include_users = $object->fetch_users_approver_expensereport();
	$s=$html->select_dolusers((GETPOST('fk_user_validator')?GETPOST('fk_user_validator'):$conf->global->EXPENSEREPORT_DEFAULT_VALIDATOR), "fk_user_validator", 1, "", 0, $include_users);
	print $html->textwithpicto($s, $langs->trans("AnyOtherInThisListCanValidate"));
	print '</td>';
	print '</tr>';
	if (! empty($conf->global->EXPENSEREPORT_ASK_PAYMENTMODE_ON_CREATION))
	{
		print '<tr>';
		print '<td>'.$langs->trans("ModePaiement").'</td>';
		print '<td>';
		$html->select_types_paiements(2,'fk_c_paiement');
		print '</td>';
		print '</tr>';
	}
	print '<tr>';
	print '<td>'.$langs->trans("Note").'</td>';
	print '<td>';
	print '<textarea name="note" class="flat" rows="'.ROWS_3.'" cols="100">'.GETPOST('note').'</textarea>';
	print '</td>';
	print '</tr>';
	print '<tbody>';
	print '</table>';

	dol_fiche_end();

	print '<center>';
	print '<input type="submit" value="'.$langs->trans("AddTrip").'" name="bouton" class="button" />';
	print ' &nbsp; &nbsp; <input type="button" value="'.$langs->trans("Cancel").'" class="button" onclick="history.go(-1)" />';
	print '</center>';

	print '</form>';
}
else
{
	if($id > 0)
	{
		$object = new ExpenseReport($db);
		$result = $object->fetch($id,$user);

		if ($result)
		{
			if ($object->fk_user_author != $user->id)
			{
				if (empty($user->rights->expensereport->readall) && empty($user->rights->expensereport->lire_tous))
				{
					print_fiche_titre($langs->trans('TripCard'));

					print '<div class="tabBar">';
					print $langs->trans('NotUserRightToView');
					print '</div>';

					$db->close();

					llxFooter();

					exit;
				}
			}

			//$head = trip_prepare_head($object);

			$head[0][0] = $_SERVER['PHP_SELF'].'?id='.$object->id;
			$head[0][1] = $langs->trans('Card');
			$head[0][2] = 'card';
			$h++;

			dol_fiche_head($head, 'card', $langs->trans("TripCard"), 0, 'trip');

			if ($action == 'edit' && ($object->fk_c_expensereport_statuts < 3 || $object->fk_c_expensereport_statuts==99))
			{
				print "<form name='update' action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

				if($object->fk_c_expensereport_statuts==99)
				{
					print '<input type="hidden" name="action" value="updateFromRefuse">';
				}
				else
				{
					print '<input type="hidden" name="action" value="update">';
				}

				print '<input type="hidden" name="id" value="'.$id.'">';


				print '<table class="border" style="width:100%;">';

				$linkback = '<a href="'.DOL_URL_ROOT.'/expensereport/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

            	// Ref
            	print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>';
            	print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'ref', '');
            	print '</td></tr>';

				print '<tr>';
				print '<td>'.$langs->trans("DateStart").'</td>';
				print '<td>';
				$html->select_date($object->date_debut,'date_debut');
				print '</td>';
				print '</tr>';
				print '<tr>';
				print '<td>'.$langs->trans("DateEnd").'</td>';
				print '<td>';
				$html->select_date($object->date_fin,'date_fin');
				print '</td>';
				print '</tr>';

				if (! empty($conf->global->EXPENSEREPORT_ASK_PAYMENTMODE_ON_CREATION))
				{
					print '<tr>';
					print '<td>'.$langs->trans("ModePaiement").'</td>';
					print '<td>';
					$html->select_types_paiements($object->fk_c_paiement,'fk_c_paiement');
					print '</td>';
					print '</tr>';
				}

				if($object->fk_c_expensereport_statuts<3)
				{
					print '<tr>';
					print '<td>'.$langs->trans("VALIDATOR").'</td>';
					print '<td>';
					$include_users = $object->fetch_users_approver_expensereport();
					$s=$html->select_dolusers($object->fk_user_validator,"fk_user_validator",0,"",0,$include_users);
					print $html->textwithpicto($s, $langs->trans("AnyOtherInThisListCanValidate"));
					print '</td>';
					print '</tr>';
				}
				else
				{
					print '<tr>';
					print '<td>'.$langs->trans("VALIDOR").'</td>';
					print '<td>';
					$userfee=new User($db);
					$userfee->fetch($object->fk_user_valid);
					print $userfee->getNomUrl(1);
					print '</td></tr>';
				}

				print '<tr>';
				print '<td>'.$langs->trans("AUTHOR").'</td>';
				print '<td>';
				$userfee=new User($db);
				$userfee->fetch($object->fk_user_author);
				print $userfee->getNomUrl(1);
				print '</td></tr>';
				if ($object->fk_c_expensereport_statuts==6)
				{
					print '<tr>';
					print '<td>'.$langs->trans("AUTHORPAIEMENT").'</td>';
					print '<td>';
					$userfee=new User($db);
					$userfee->fetch($object->fk_user_paid);
					print $userfee->getNomUrl(1);
					print '</td></tr>';

				}
				print '<tr>';
				print '<td>'.$langs->trans("Note").'</td>';
				print '<td>';
				print '<textarea name="note" class="flat" rows="1" cols="70">'.$object->note.'</textarea>';
				print '</td>';
				print '</tr>';
				print '</table>';

				print '<br><div class="center">';
				print '<input type="submit" value="'.$langs->trans("Modify").'" name="bouton" class="button"> &nbsp; &nbsp; ';
				print '<input type="button" value="'.$langs->trans("CancelAddTrip").'" class="button" onclick="history.go(-1)" />';
				print '</div>';

				print '</form>';

			}
			else
			{

				if ($action == 'save'):
				$ret=$html->form_confirm($_SEVER["PHP_SELF"]."?id=".$id,$langs->trans("SaveTrip"),$langs->trans("ConfirmSaveTrip"),"confirm_save","","",1);
				if ($ret == 'html') print '<br>';
				endif;

				if ($action == 'save_from_refuse'):
				$ret=$html->form_confirm($_SEVER["PHP_SELF"]."?id=".$id,$langs->trans("SaveTrip"),$langs->trans("ConfirmSaveTrip"),"confirm_save_from_refuse","","",1);
				if ($ret == 'html') print '<br>';
				endif;

				if ($action == 'delete'):
				$ret=$html->form_confirm($_SEVER["PHP_SELF"]."?id=".$id,$langs->trans("DeleteTrip"),$langs->trans("ConfirmDeleteTrip"),"confirm_delete","","",1);
				if ($ret == 'html') print '<br>';
				endif;

				if ($action == 'validate'):
				$ret=$html->form_confirm($_SEVER["PHP_SELF"]."?id=".$id,$langs->trans("ValideTrip"),$langs->trans("ConfirmValideTrip"),"confirm_validate","","",1);
				if ($ret == 'html') print '<br>';
				endif;

				if ($action == 'paid'):
				$ret=$html->form_confirm($_SEVER["PHP_SELF"]."?id=".$id,$langs->trans("PaidTrip"),$langs->trans("ConfirmPaidTrip"),"confirm_paid","","",1);
				if ($ret == 'html') print '<br>';
				endif;

				if ($action == 'cancel'):
				$array_input = array(array('type'=>"text",'label'=>"Entrez ci-dessous un motif d'annulation :",'name'=>"detail_cancel",'size'=>"50",'value'=>""));
				$ret=$html->form_confirm($_SEVER["PHP_SELF"]."?id=".$id,$langs->trans("ConfirmCancelTrip"),"","confirm_cancel",$array_input,"",0);
				if ($ret == 'html') print '<br>';
				endif;

				if ($action == 'brouillonner'):
				$ret=$html->form_confirm($_SEVER["PHP_SELF"]."?id=".$id,$langs->trans("BrouillonnerTrip"),$langs->trans("ConfirmBrouillonnerTrip"),"confirm_brouillonner","","",1);
				if ($ret == 'html') print '<br>';
				endif;

				if ($action == 'refuse'):
				$array_input = array(array('type'=>"text",'label'=>"Entrez ci-dessous un motif de refus :",'name'=>"detail_refuse",'size'=>"50",'value'=>""));
				$ret=$html->form_confirm($_SEVER["PHP_SELF"]."?id=".$id,$langs->trans("ConfirmRefuseTrip"),"","confirm_refuse",$array_input,"yes",0);
				if ($ret == 'html') print '<br>';
				endif;

				if ($action == 'delete_line')
				{
					$ret=$html->form_confirm($_SEVER["PHP_SELF"]."?id=".$_GET['id']."&amp;rowid=".$_GET['rowid'],$langs->trans("DeleteLine"),$langs->trans("ConfirmDeleteLine"),"confirm_delete_line");
					if ($ret == 'html') print '<br>';
				}

				print '<table class="border centpercent">';

				$linkback = '<a href="'.DOL_URL_ROOT.'/expensereport/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

            	// Ref
            	print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>';
            	print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'ref', '');
            	print '</td></tr>';

				print '<tr>';
				print '<td>'.$langs->trans("Periode").'</td>';
				print '<td>';
				print get_date_range($object->date_debut,$object->date_fin,'',$langs,0);
				print '</td>';
				print '</tr>';
				if (! empty($conf->global->EXPENSEREPORT_ASK_PAYMENTMODE_ON_CREATION))
				{
					print '<tr>';
					print '<td>'.$langs->trans("ModePaiement").'</td>';
					print '<td>'.$object->libelle_paiement.'</td>';
					print '</tr>';
				}
				print '<tr>';
				print '<td>'.$langs->trans("Statut").'</td>';
				print '<td style="font-weight:bold;">'.$object->getLibStatut(4).'</td>';
				print '</tr>';
				print '<tr>';
				print '<td>'.$langs->trans("Note").'</td>';
				print '<td>'.$object->note.'</td>';
				print '</tr>';
				print '<tr>';
				print '<td>'.$langs->trans("AmountHT").'</td>';
				print '<td>'.price($object->total_ht).'</td>';
				print '</tr>';
				print '<tr>';
				print '<td>'.$langs->trans("AmountVAT").'</td>';
				print '<td>'.price($object->total_tva).'</td>';
				print '</tr>';
				print '<tr>';
				print '<td>'.$langs->trans("AmountTTC").'</td>';
				print '<td>'.price($object->total_ttc).'</td>';
				print '</tr>';

				if($object->fk_c_expensereport_statuts<3)
				{
					print '<tr>';
					print '<td>'.$langs->trans("VALIDATOR").'</td>';
					print '<td>';
					if ($object->fk_user_validator > 0)
					{
						$userfee=new User($db);
						$userfee->fetch($object->fk_user_validator);
						print $userfee->getNomUrl(1);
					}
					print '</td></tr>';
				}
				elseif($object->fk_c_expensereport_statuts==4)
				{
					print '<tr>';
					print '<td><span style="font-weight:bold;color:red;">'.$langs->trans("CANCEL_USER").'</span></td>';
					print '<td><span style="font-weight:bold;color:red;">';
					if ($object->fk_user_cancel > 0)
					{
						$userfee=new User($db);
						$userfee->fetch($object->fk_user_cancel);
						print $userfee->getNomUrl(1);
					}
					print '</span></td></tr>';
					print '<tr>';
					print '<td><span style="font-weight:bold;color:red;">'.$langs->trans("MOTIF_CANCEL").'</span></td>';
					print '<td><span style="font-weight:bold;color:red;">'.$object->detail_cancel.'</span></td></tr>';
					print '</tr>';
					print '<tr>';
					print '<td><span style="font-weight:bold;color:red;">'.$langs->trans("DATE_CANCEL").'</span></td>';
					print '<td><span style="font-weight:bold;color:red;">'.$object->date_cancel.'</span></td></tr>';
					print '</tr>';
				}
				else
				{
					print '<tr>';
					print '<td>'.$langs->trans("VALIDOR").'</td>';
					print '<td>';
					if ($object->fk_user_valid > 0)
					{
						$userfee=new User($db);
						$userfee->fetch($object->fk_user_valid);
						print $userfee->getNomUrl(1);
					}
					print '</td></tr>';
					print '<tr>';
					print '<td>'.$langs->trans("DATE_VALIDE").'</td>';
					print '<td>'.$object->date_valide.'</td></tr>';
					print '</tr>';
				}

				print '<tr>';
				print '<td>'.$langs->trans("AUTHOR").'</td>';
				print '<td>';
				if ($object->fk_user_author > 0)
				{
					$userfee=new User($db);
					$userfee->fetch($object->fk_user_author);
					print $userfee->getNomUrl(1);
				}
				print '</td></tr>';

				print '<tr>';
				print '<td>'.$langs->trans("DATE_SAVE").'</td>';
				print '<td>'.$object->date_create.'</td></tr>';
				print '</tr>';
				if($object->fk_c_expensereport_statuts==6)
				{
					print '<tr>';
					print '<td>'.$langs->trans("AUTHORPAIEMENT").'</td>';
					print '<td>';
					$userfee=new User($db);
					$userfee->fetch($object->fk_user_paid);
					print $userfee->getNomUrl(1);
					print '</td></tr>';
					print '<tr>';
					print '<td>'.$langs->trans("DATE_PAIEMENT").'</td>';
					print '<td>'.$object->date_paiement.'</td></tr>';
					print '</tr>';
				}
				if($object->fk_c_expensereport_statuts==99 || !empty($object->detail_refuse))
				{
					print '<tr>';
					print '<td><span style="font-weight:bold;color:red;">'.$langs->trans("REFUSEUR").'</span></td>';
					print '<td><span style="font-weight:bold;color:red;">';
					$userfee=new User($db);
					$userfee->fetch($object->fk_user_refuse);
					print $userfee->getNomUrl(1);
					print '</span></td></tr>';
					print '<tr>';
					print '<td><span style="font-weight:bold;color:red;">'.$langs->trans("MOTIF_REFUS").'</span></td>';
					print '<td><span style="font-weight:bold;color:red;">'.$object->detail_refuse.'</span></td></tr>';
					print '</tr>';
					print '<tr>';
					print '<td><span style="font-weight:bold;color:red;">'.$langs->trans("DATE_REFUS").'</span></td>';
					print '<td><span style="font-weight:bold;color:red;">'.$object->date_refuse.'</span></td></tr>';
					print '</tr>';
				}
				print '</table>';

				print '<br>';

				// Fetch Lines of current ndf
				$sql = 'SELECT fde.rowid, fde.fk_expensereport, fde.fk_c_type_fees, fde.fk_projet, fde.date,';
				$sql.= ' fde.fk_c_tva, fde.comments, fde.qty, fde.value_unit, fde.total_ht, fde.total_tva, fde.total_ttc,';
				$sql.= ' ctf.code as type_fees_code, ctf.label as type_fees_libelle,';
				$sql.= ' pjt.rowid as projet_id, pjt.title as projet_title, pjt.ref as projet_ref';
				$sql.= ' FROM '.MAIN_DB_PREFIX.'expensereport_det fde';
				$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'c_type_fees ctf ON fde.fk_c_type_fees=ctf.id';
				$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet pjt ON fde.fk_projet=pjt.rowid';
				$sql.= ' WHERE fde.fk_expensereport = '.$id;

				$resql = $db->query($sql);
				if ($resql)
				{
					$num_lignes = $db->num_rows($resql);
					$i = 0;$total = 0;

					if ($num_lignes)
					{
						print '<div style="clear: both;">';

						print '<form name="updateligne" action="'.$_SERVER["PHP_SELF"].'" method="post">';
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
						print '<input type="hidden" name="action" value="updateligne">';
						print '<input type="hidden" name="id" value="'.$object->id.'">';

						print '<table class="noborder" width="100%">';

						print '<tr class="liste_titre">';
						if ($action != 'editline') print '<td style="text-align:center;width:9%;">'.$langs->trans('Piece').'</td>';
						print '<td style="text-align:center;">'.$langs->trans('Date').'</td>';
						print '<td style="text-align:center;">'.$langs->trans('Project').'</td>';
						print '<td style="text-align:center;">'.$langs->trans('Type').'</td>';
						print '<td style="text-align:left;">'.$langs->trans('Description').'</td>';
						print '<td style="text-align:right;">'.$langs->trans('VAT').'</td>';
						print '<td style="text-align:right;">'.$langs->trans('UnitPriceTTC').'</td>';
						print '<td style="text-align:right;">'.$langs->trans('Qty').'</td>';
						if ($action != 'editline')
						{
							print '<td style="text-align:right;">'.$langs->trans('AmountHT').'</td>';
							print '<td style="text-align:right;">'.$langs->trans('AmountTTC').'</td>';
						}
						// Ajout des boutons de modification/suppression
						if ($object->fk_c_expensereport_statuts<2 OR $object->fk_c_expensereport_statuts==99)
						{
							print '<td style="text-align:right;"></td>';
						}

						print '</tr>';

						$var=true;
						while ($i < $num_lignes)
						{
							$piece_comptable = $i + 1;
							$objp = $db->fetch_object($resql);
							$var=!$var;
							if ($action != 'editline')
							{
								print '<tr '.$bc[$var].'>';
								print '<td style="text-align:center;width:9%;">';
								print img_picto("Document", "generic");
								print ' <span style="color:red;font-weight:bold;font-size:14px;">'.$piece_comptable.'</span></td>';
								print '<td style="text-align:center;">'.$objp->date.'</td>';
								print '<td style="text-align:center;">'.$objp->projet_ref.'</td>';
								print '<td style="text-align:center;">'.$langs->trans("TF_".strtoupper($objp->type_fees_libelle)).'</td>';
								print '<td style="text-align:left;">'.$objp->comments.'</td>';
								print '<td style="text-align:right;">'.vatrate($objp->tva_taux,true).'</td>';
								print '<td style="text-align:right;">'.price($objp->value_unit).'</td>';
								print '<td style="text-align:right;">'.$objp->qty.'</td>';
								print '<td style="text-align:right;">'.$objp->total_ht.'</td>';
								print '<td style="text-align:right;">'.$objp->total_ttc.'</td>';

								// Ajout des boutons de modification/suppression
								if($object->fk_c_expensereport_statuts<2 OR $object->fk_c_expensereport_statuts==99)
								{
									print '<td style="text-align:right;" class="nowrap">';
									print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=editline&amp;rowid='.$objp->rowid.'#'.$objp->rowid.'">';
									print img_edit();
									print '</a> &nbsp; ';
									print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete_line&amp;rowid='.$objp->rowid.'">';
									print img_delete();
									print '</a>';
									print '</td>';
								}
								print '</tr>';
							}
							else
							{
								if($objp->rowid==$_GET['rowid'])
								{
									//modif ligne!!!!!
									print '<tr '.$bc[$var].'>';
									// Sélection date
									print '<td style="text-align:center;width:10%;">';
									$html->select_date($objp->date,'date');
									print '</td>';

									// Sélection projet
									print '<td style="text-align:center;width:10%;">';
									print select_projet($objp->fk_projet,'','fk_projet');
									print '</td>';

									// Sélection type
									print '<td style="text-align:center;width:10%;">';
									select_type_fees_id($objp->type_fees_code,'fk_c_type_fees');
									print '</td>';

									// Add comments
									print '<td style="text-align:left;width:35%;">';
									print '<textarea class="flat_ndf" name="comments" class="centpercent">'.$objp->comments.'</textarea>';
									print '</td>';

									// Sélection TVA
									print '<td style="text-align:right;width:10%;">';
									print $form->load_tva('fk_c_tva', (isset($_POST["fk_c_tva"])?$_POST["fk_c_tva"]:$objp->tva_taux), $mysoc, '');
									print '</td>';

									// Prix unitaire
									print '<td style="text-align:right;width:10%;">';
									print '<input type="text" size="10" name="value_unit" value="'.$objp->value_unit.'" />';
									print '</td>';

									// Quantité
									print '<td style="text-align:right;width:10%;">';
									print '<input type="text" size="10" name="qty" value="'.$objp->qty.'" />';
									print '</td>';

									print '<td style="text-align:center;">';
									print '<input type="hidden" name="rowid" value="'.$objp->rowid.'">';
									print '<input type="submit" class="button" name="save" value="'.$langs->trans('Save').'">';
									print '<br /><input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
									print '</td>';
								}
							}

							$i++;
						}

						$db->free($resql);

						print '</table>';

						print '</form>';

						print '</div>';
					}
					else
					{
					/*	print '<table width="100%">';
						print '<tr><td><div class="error" style="display:block;">'.$langs->trans("AucuneLigne").'</div></td></tr>';
						print '</table>';*/
					}
					//print '</div>';

					// Ajouter une ligne
					if (($object->fk_c_expensereport_statuts==0 || $object->fk_c_expensereport_statuts==99) && $action != 'editline')
					{
						print_fiche_titre($langs->trans("AddLine"),'','');

						print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" name="addline">';
						print '<input type="hidden" name="fk_expensereport" value="'.$id.'" />';
						print '<input type="hidden" name="action" value="addline" />';

						print '<table class="noborder" width="100%">';
						print '<tr class="liste_titre">';
						print '<td style="text-align:center;">'.$langs->trans('Date').'</td>';
						print '<td>'.$langs->trans('Project').'</td>';
						print '<td>'.$langs->trans('Type').'</td>';
						print '<td>'.$langs->trans('Description').'</td>';
						print '<td style="text-align:right;">'.$langs->trans('VAT').'</td>';
						print '<td style="text-align:right;">'.$langs->trans('UnitPriceTTC').'</td>';
						print '<td style="text-align:right;">'.$langs->trans('Qty').'</td>';
						print '<td style="text-align:center;"></td>';
						print '</tr>';

						print '<tr>';

						// Sélection date
						print '<td style="text-align:center;">';
						$html->select_date(-1,'date');
						print '</td>';

						// Sélection projet
						print '<td>';
						print select_projet('','','fk_projet');
						//$formproject->select_projects('','','fk_projet');
						print '</td>';

						// Sélection type
						print '<td>';
						select_type_fees_id('TF_TRAIN','fk_c_type_fees');
						print '</td>';

						// Add comments
						print '<td style="text-align:left;">';
						print '<textarea class="flat_ndf centpercent" name="comments"></textarea>';
						print '</td>';

						// Sélection TVA
						print '<td style="text-align:right;">';
						$defaultvat=-1;
						if (! empty($conf->global->DEPLACEMENT_NO_DEFAULT_VAT)) $conf->global->MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS = 'none';
						print '<select class="flat" name="fk_c_tva">';
						print '<option name="none" value="" selected="selected">';
						print $form->load_tva('fk_c_tva', (isset($_POST["fk_c_tva"])?$_POST["fk_c_tva"]:$defaultvat), $mysoc, '', 0, 0, '', true);
						print '</select>';
						print '</td>';

						// Prix unitaire
						print '<td style="text-align:right;">';
						print '<input type="text" size="10" name="value_unit" />';
						print '</td>';

						// Quantité
						print '<td style="text-align:right;">';
						print '<input type="text" size="4" name="qty" />';
						print '</td>';

						print '<td style="text-align:center;"><input type="submit" value="'.$langs->trans("Add").'" name="bouton" class="button"></td>';
						print '</tr>';

						print '</table>';

						print '</form>';
					} // Fin si c'est payé/validé

				}
				else
				{
					dol_print_error($db);
				}
			} // end edit or not edit

			dol_fiche_end();

		}	// end of if result
		else
		{
			dol_print_error($db);
		}

	} //fin si id > 0

}



/*
 * Barre d'actions
 */

print '<div class="tabsAction">';

if ($action != 'create' && $action != 'edit')
{
	$object = new ExpenseReport($db);
	$object->fetch($id,$user);

	/* Si l'état est "Brouillon"
	 *	ET user à droit "creer/supprimer"
	*	ET fk_user_author == user courant
	* 	Afficher : "Enregistrer" / "Modifier" / "Supprimer"
	*/
	if ($user->rights->expensereport->creer AND $object->fk_c_expensereport_statuts==0)
	{
		if ($object->fk_user_author == $user->id)
		{
			// Modifier
			print '<a class="butAction" href="'.$_SEVER["PHP_SELF"].'?action=edit&id='.$id.'">'.$langs->trans('ModifyInfoGen').'</a>';

			// Enregistrer
			print '<a class="butAction" href="'.$_SEVER["PHP_SELF"].'?action=save&id='.$id.'">'.$langs->trans('Validate').'</a>';

			if ($user->rights->expensereport->supprimer)
			{
				// Supprimer
				print '<a class="butActionDelete" href="'.$_SEVER["PHP_SELF"].'?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
			}
		}
	}

	/* Si l'état est "Refusée"
	 *	ET user à droit "creer/supprimer"
	 *	ET fk_user_author == user courant
	 * 	Afficher : "Enregistrer" / "Modifier" / "Supprimer"
	 */
	if($user->rights->expensereport->creer && $object->fk_c_expensereport_statuts==99)
	{
		if ($object->fk_user_author == $user->id)
		{
			// Modifier
			print '<a class="butAction" href="'.$_SEVER["PHP_SELF"].'?action=edit&id='.$id.'">'.$langs->trans('ModifyInfoGen').'</a>';

			// Brouillonner (le statut refusée est identique à brouillon)
			//print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=brouillonner&id='.$id.'">'.$langs->trans('BROUILLONNER').'</a>';
			// Enregistrer depuis le statut "Refusée"
			print '<a class="butAction" href="'.$_SEVER["PHP_SELF"].'?action=save_from_refuse&id='.$id.'">'.$langs->trans('Validate').'</a>';

			if ($user->rights->expensereport->supprimer)
			{
				// Supprimer
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
			}
		}
	}

	if ($user->rights->expensereport->to_paid && $object->fk_c_expensereport_statuts==5)
	{
		if ($object->fk_user_author == $user->id || $object->fk_user_valid == $user->id)
		{
			// Brouillonner
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=brouillonner&id='.$id.'">'.$langs->trans('BROUILLONNER').'</a>';
		}
	}

	/* Si l'état est "En attente d'approbation"
	 *	ET user à droit de "to_validate"
	*	ET fk_user_validator == user courant
	*	Afficher : "Valider" / "Refuser" / "Supprimer"
	*/
	if ($object->fk_c_expensereport_statuts == 2)
	{
		if ($object->fk_user_author == $user->id)
		{
			// Brouillonner
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=brouillonner&id='.$id.'">'.$langs->trans('BROUILLONNER').'</a>';
		}
	}

	if ($user->rights->expensereport->to_validate && $object->fk_c_expensereport_statuts == 2)
	{
		//if($object->fk_user_validator==$user->id)
		//{
			// Valider
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=validate&id='.$id.'">'.$langs->trans('Approve').'</a>';
			// Refuser
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=refuse&id='.$id.'">'.$langs->trans('REFUSE').'</a>';
		//}

		if ($object->fk_user_author==$user->id)
		{
			// Annuler
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=cancel&id='.$id.'">'.$langs->trans('CANCEL').'</a>';
		}

		if($user->rights->expensereport->supprimer)
		{
			// Supprimer
			print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
		}
	}

	/* Si l'état est "A payer"
	 *	ET user à droit de "to_paid"
	*	Afficher : "Annuler" / "Payer" / "Supprimer"
	*/
	if ($user->rights->expensereport->to_paid && $object->fk_c_expensereport_statuts==5)
	{
		// Payer
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=paid&id='.$id.'">'.$langs->trans('TO_PAID').'</a>';

		// Annuler
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=cancel&id='.$id.'">'.$langs->trans('CANCEL').'</a>';

		if($user->rights->expensereport->supprimer)
		{
			// Supprimer
			print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
		}
	}

	/* Si l'état est "Payée"
	 *	ET user à droit "to_validate"
	*	ET user à droit "to_paid"
	*	Afficher : "Annuler"
	*/
	if ($user->rights->expensereport->to_validate && $user->rights->expensereport->to_paid && $object->fk_c_expensereport_statuts==6)
	{
		// Annuler
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=cancel&id='.$id.'">'.$langs->trans('Cancel').'</a>';
		if($user->rights->expensereport->supprimer)
		{
			// Supprimer
			print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
		}
	}

	/* Si l'état est "Annulée"
	 * 	ET user à droit "supprimer"
	 *	Afficher : "Supprimer"
	 */
	if ($user->rights->expensereport->supprimer && $object->fk_c_expensereport_statuts==4)
	{

		if ($object->fk_user_validator==$user->id)
		{
			// Brouillonner
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=brouillonner&id='.$id.'">'.$langs->trans('BROUILLONNER').'</a>';
		}

		// Supprimer
		print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';

	}
}

print '</div>';


$conf->global->DOL_URL_ROOT_DOCUMENT_PHP=dol_buildpath('/expensereport/documentwrapper.php',1);


print '<div style="width:50%">';

/*
 * Documents generes
 */
if($user->rights->expensereport->export && $object->fk_c_expensereport_statuts>0 && $action != 'edit')
{
	$filename	=	dol_sanitizeFileName($object->ref_number);
	$filedir	=	$conf->expensereport->dir_output . "/" . dol_sanitizeFileName($object->ref_number);
	$urlsource	=	$_SERVER["PHP_SELF"]."?id=".$object->id;
	$genallowed	=	1;
	$delallowed	=	1;
	$var 		= 	true;
	print $formfile->showdocuments('expensereport',$filename,$filedir,$urlsource,$genallowed,$delallowed);
	$somethingshown = $formfile->numoffiles;
}

print '</div>';


llxFooter();

$db->close();
