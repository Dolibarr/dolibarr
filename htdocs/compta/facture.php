<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2005 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 */

/**
		\file       htdocs/compta/facture.php
		\ingroup    facture
		\brief      Page de création d'une facture
		\version    $Revision$
*/

require('./pre.inc.php');

$user->getrights('facture');
$user->getrights('banque');

if (!$user->rights->facture->lire)
accessforbidden();

$langs->load('bills');

require_once(DOL_DOCUMENT_ROOT.'/facture.class.php');
require_once(DOL_DOCUMENT_ROOT.'/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT.'/lib/CMailFile.class.php');
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/project.class.php');
if ($conf->propal->enabled)   require_once(DOL_DOCUMENT_ROOT.'/propal.class.php');
if ($conf->contrat->enabled)  require_once(DOL_DOCUMENT_ROOT.'/contrat/contrat.class.php');
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT.'/commande/commande.class.php');


$sall=isset($_GET['sall'])?$_GET['sall']:$_POST['sall'];
if (isset($_GET['msg'])) { $msg=urldecode($_GET['msg']); }
if ($_GET['socidp']) { $socidp=$_GET['socidp']; }

// Sécurité accés client
if ($user->societe_id > 0)
{
	$action = '';
	$socidp = $user->societe_id;
}

// Nombre de ligne pour choix de produit/service prédéfinis
$NBLINES=4;


/*
 *  Actions
 */

if ($_POST['action'] == 'classin')
{
	$facture = new Facture($db);
	$facture->fetch($_GET['facid']);
	$facture->classin($_POST['projetid']);
}

if ($_POST['action'] == 'setmode')
{
	$facture = new Facture($db);
	$facture->fetch($_GET['facid']);
	$result=$facture->mode_reglement($_POST['mode_reglement_id']);
	if ($result < 0) dolibarr_print_error($db,$facture->error);
}

if ($_POST['action'] == 'setconditions')
{
	$facture = new Facture($db);
	$facture->fetch($_GET['facid']);
	$result=$facture->cond_reglement($_POST['cond_reglement_id']);
	if ($result < 0) dolibarr_print_error($db,$facture->error);
}

if ($_POST['action'] == 'classin')
{
	$facture = new Facture($db);
	$facture->fetch($_GET['facid']);
	$facture->classin($_POST['projetid']);
}

if ($_POST['action'] == 'set_ref_client')
{
	$facture = new Facture($db);
	$facture->fetch($_GET['facid']);
	$facture->set_ref_client($_POST['ref_client']);
}

/*
 * Insertion facture
 */
if ($_POST['action'] == 'add')
{
	$datefacture = mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);

	$facture = new Facture($db, $_POST['socid']);

	$facture->number         = $_POST['facnumber'];
	$facture->date           = $datefacture;
	$facture->note           = $_POST['note'];

	if ($_POST['fac_rec'] > 0)
	{
		// Facture récurrente
		$facture->fac_rec = $_POST['fac_rec'];
		$facid = $facture->create($user);
	}
	else
	{
		$facture->projetid          = $_POST['projetid'];
		$facture->cond_reglement_id = $_POST['cond_reglement_id'];
		$facture->mode_reglement_id = $_POST['mode_reglement_id'];
		$facture->amount            = $_POST['amount'];
		$facture->remise            = $_POST['remise'];
		$facture->remise_percent    = $_POST['remise_percent'];
		$facture->ref_client        = $_POST['ref_client'];

		if (! $_POST['propalid'] && ! $_POST['commandeid'] && ! $_POST['contratid'])
		{
			for ($i = 1 ; $i <= $NBLINES ; $i++)
			{
				if ($_POST['idprod'.$i])
				{
					$startday='';
					$endday='';
					if ($_POST['date_start'.$i.'year'] && $_POST['date_start'.$i.'month'] && $_POST['date_start'.$i.'day']) {
						$startday=$_POST['date_start'.$i.'year'].'-'.$_POST['date_start'.$i.'month'].'-'.$_POST['date_start'.$i.'day'];
					}
					if ($_POST['date_end'.$i.'year'] && $_POST['date_end'.$i.'month'] && $_POST['date_end'.$i.'day']) {
						$endday=$_POST['date_end'.$i.'year'].'-'.$_POST['date_end'.$i.'month'].'-'.$_POST['date_end'.$i.'day'];
					}
					$facture->add_product($_POST['idprod'.$i],$_POST['qty'.$i],$_POST['remise_percent'.$i],$startday,$endday);
				}
			}
			$facid = $facture->create($user);

			if ($facid)
			{
				Header('Location: facture.php?facid='.$facid);
				exit;
			}
		}
		else
		{
			/*
			 * Si creation depuis propale
			 */
			if ($_POST['propalid'])
			{
				$facture->propalid = $_POST['propalid'];

				$facid = $facture->create($user);

				if ($facid)
				{
					$prop = New Propal($db);
					if ( $prop->fetch($_POST['propalid']) )
					{
						for ($i = 0 ; $i < sizeof($prop->lignes) ; $i++)
						{
							$liblignefac=($prop->lignes[$i]->desc?$prop->lignes[$i]->desc:$prop->lignes[$i]->libelle);

							$result = $facture->addline($facid,
							addslashes($liblignefac),
							$prop->lignes[$i]->subprice,
							$prop->lignes[$i]->qty,
							$prop->lignes[$i]->tva_tx,
							$prop->lignes[$i]->product_id,
							$prop->lignes[$i]->remise_percent);
						}
					}
					else
					{
						print $langs->trans('UnknownError');
					}
				}
			}

			/*
			 * Si création depuis commande
			 */
			if ($_POST['commandeid'])
			{
				$facture->commandeid = $_POST['commandeid'];
				$facid = $facture->create($user);
				if ($facid)
				{
					$comm = New Commande($db);
					if ( $comm->fetch($_POST['commandeid']) )
					{
						$lines = $comm->fetch_lignes();
						for ($i = 0 ; $i < sizeof($lines) ; $i++)
						{
							$result = $facture->addline($facid,
							addslashes($lines[$i]->description),
							$lines[$i]->subprice,
							$lines[$i]->qty,
							$lines[$i]->tva_tx,
							$lines[$i]->product_id,
							$lines[$i]->remise_percent);
						}
					}
					else
					{
						print $langs->trans('UnknownError');
					}
				}
				else
				{
					dolibarr_print_error($db);
				}
			}

			/*
			 * Si création depuis contrat
			 */
			if ($_POST['contratid'])
			{
				$facture->contratid = $_POST['contratid'];
				$facid = $facture->create($user);

				if ($facid)
				{
					$contrat = New Contrat($db);
					if ($contrat->fetch($_POST['contratid']) > 0)
					{
						$lines = $contrat->fetch_lignes();

						for ($i = 0 ; $i < sizeof($lines) ; $i++)
						{
							$liblignefac=($contrat->lignes[$i]->desc?$contrat->lignes[$i]->desc:$contrat->lignes[$i]->libelle);

							// Plage de dates
							$date_start=$contrat->lignes[$i]->date_debut_prevue;
							if ($contrat->lignes[$i]->date_debut_reel) $date_start=$contrat->lignes[$i]->date_debut_reel;
							$date_end=$contrat->lignes[$i]->date_fin_prevue;
							if ($contrat->lignes[$i]->date_fin_reel) $date_end=$contrat->lignes[$i]->date_fin_reel;

							$result = $facture->addline($facid,
							addslashes($liblignefac),
							$lines[$i]->subprice,
							$lines[$i]->qty,
							$lines[$i]->tva_tx,
							$lines[$i]->product_id,
							$lines[$i]->remise_percent,
							$date_start,
							$date_end);
						}
					}
					else
					{
						print $langs->trans('UnknownError');
					}
				}
				else
				{
					dolibarr_print_error($db);
				}
			}

			// Fin création facture, on l'affiche
			if ($facid)
			{
				Header('Location: facture.php?facid='.$facid);
				exit;
			}
		}
	}
}

// Classe à "validée"
if ($_POST['action'] == 'confirm_valid' && $_POST['confirm'] == 'yes' && $user->rights->facture->valider)
{
	$fac = new Facture($db);
	$fac->fetch($_GET['facid']);
	$soc = new Societe($db);
	$soc->fetch($fac->socidp);
	$result = $fac->set_valid($fac->id, $user, $soc);
	if ($result)
	{
		facture_pdf_create($db, $fac->id);
	}
}

// Classe à "payée"
if ($_POST['action'] == 'confirm_payed' && $_POST['confirm'] == 'yes' && $user->rights->facture->paiement)
{
	$fac = new Facture($db);
	$fac->fetch($_GET['facid']);
	$result = $fac->set_payed($user);
}

if ($_POST['action'] == 'setremise' && $user->rights->facture->creer)
{
	$fac = new Facture($db);
	$fac->fetch($_GET['facid']);
	$result = $fac->set_remise($user, $_POST['remise']);
}

if ($_POST['action'] == 'addligne' && $user->rights->facture->creer)
{
	if ($_POST['qty'] && (($_POST['pu']>=0 && $_POST['desc']) || $_POST['idprod']))
	{
		$fac = new Facture($db);
		$fac->fetch($_POST['facid']);
		$datestart='';
		$dateend='';
		if ($_POST['date_startyear'] && $_POST['date_startmonth'] && $_POST['date_startday'])
		{
			$datestart=$_POST['date_startyear'].'-'.$_POST['date_startmonth'].'-'.$_POST['date_startday'];
		}
		if ($_POST['date_endyear'] && $_POST['date_endmonth'] && $_POST['date_endday'])
		{
			$dateend=$_POST['date_endyear'].'-'.$_POST['date_endmonth'].'-'.$_POST['date_endday'];
		}
		$result = $fac->addline($_POST['facid'],
			$_POST['desc'],
			$_POST['pu'],
			$_POST['qty'],
			$_POST['tva_tx'],
			$_POST['idprod'],
			$_POST['remise_percent'],
			$datestart,
			$dateend
			);
	}

	$_GET['facid']=$_POST['facid'];   // Pour réaffichage de la fiche en cours d'édition
}

if ($_POST['action'] == 'updateligne' && $user->rights->facture->creer && $_POST['save'] == $langs->trans('Save'))
{
	$fac = new Facture($db,'',$_POST['facid']);
	$fac->fetch($_POST['facid']);

	$datestart='';
	$dateend='';
	if ($_POST['date_startyear'] && $_POST['date_startmonth'] && $_POST['date_startday']) {
		$datestart=$_POST['date_startyear'].'-'.$_POST['date_startmonth'].'-'.$_POST['date_startday'];
	}
	if ($_POST['date_endyear'] && $_POST['date_endmonth'] && $_POST['date_endday']) {
		$dateend=$_POST['date_endyear'].'-'.$_POST['date_endmonth'].'-'.$_POST['date_endday'];
	}

	$result = $fac->updateline($_POST['rowid'],
		$_POST['desc'],
		$_POST['price'],
		$_POST['qty'],
		$_POST['remise_percent'],
		$datestart,
		$dateend,
		$_POST['tva_tx']
		);

	$_GET['facid']=$_POST['facid'];   // Pour réaffichage de la fiche en cours d'édition
}

if ($_POST['action'] == 'updateligne' && $user->rights->facture->creer && $_POST['cancel'] == $langs->trans('Cancel'))
{
	Header('Location: facture.php?facid='.$_POST['facid']);   // Pour réaffichage de la fiche en cours d'édition
	exit;
}

if ($_GET['action'] == 'deleteline' && $user->rights->facture->creer)
{
	$fac = new Facture($db,'',$_GET['facid']);
	$fac->fetch($_GET['facid']);
	$result = $fac->deleteline($_GET['rowid']);
}

if ($_POST['action'] == 'confirm_delete' && $_POST['confirm'] == 'yes')
{
	if ($user->rights->facture->supprimer )
	{
		$fac = new Facture($db);
		$result = $fac->delete($_GET['facid']);
		$_GET['facid'] = 0 ;
		Header('Location: facture.php');
		exit;
	}
}

if ($_POST['action'] == 'confirm_canceled' && $_POST['confirm'] == 'yes')
{
	if ($user->rights->facture->supprimer )
	{
		$fac = new Facture($db);
    	$fac->fetch($_GET['facid']);
		$result = $fac->set_canceled();
		$_GET['facid'] = 0 ;
		Header('Location: facture.php');
		exit;
	}
}

/*
 * Ordonnancement des lignes
 */

if ($_GET['action'] == 'up' && $user->rights->facture->creer)
{
	$fac = new Facture($db,'',$_GET['facid']);
	$fac->line_up($_GET['rowid']);
}

if ($_GET['action'] == 'down' && $user->rights->facture->creer)
{
	$fac = new Facture($db,'',$_GET['facid']);
	$fac->line_down($_GET['rowid']);
}

/*
 * Action envoi de mail
 */
if ($_POST['action'] == 'send' || $_POST['action'] == 'relance')
{
	$langs->load('mails');

	$fac = new Facture($db,'',$_POST['facid']);
	if ( $fac->fetch($_POST['facid']) )
	{
		$facref = sanitize_string($fac->ref);
		$file = $conf->facture->dir_output . '/' . $facref . '/' . $facref . '.pdf';

		if (is_readable($file))
		{
			$soc = new Societe($db, $fac->socidp);

			if ($_POST['sendto']) {
				// Le destinataire a été fourni via le champ libre
				$sendto = $_POST['sendto'];
				$sendtoid = 0;
			}
			elseif ($_POST['receiver']) {
				// Le destinataire a été fourni via la liste déroulante
				$sendto = $soc->contact_get_email($_POST['receiver']);
				$sendtoid = $_POST['receiver'];
			}

			if (strlen($sendto))
			{
				$from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
				$replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
				$message = $_POST['message'];
				if ($_POST['action'] == 'send')
				{
					$subject = $langs->trans('Bill').' '.$fac->ref;
					$actiontypeid=9;
					$actionmsg ='Mail envoyé par '.$from.' à '.$sendto.'.<br>';
					if ($message) {
						$actionmsg.='Texte utilisé dans le corps du message:<br>';
						$actionmsg.=$message;
					}
					$actionmsg2='Envoi facture par mail';
				}
				if ($_POST['action'] == 'relance')
				{
					$subject = 'Relance facture '.$fac->ref;
					$actiontypeid=10;
					$actionmsg='Mail envoyé par '.$from.' à '.$sendto.'.<br>';
					if ($message) {
						$actionmsg.='Texte utilisé dans le corps du message:<br>';
						$actionmsg.=$message;
					}
					$actionmsg2='Relance facture par mail';
				}

				$filepath[0] = $file;
				$filename[0] = $fac->ref.'.pdf';
				$mimetype[0] = 'application/pdf';
				$filepath[1] = $_FILES['addedfile']['tmp_name'];
				$filename[1] = $_FILES['addedfile']['name'];
				$mimetype[1] = $_FILES['addedfile']['type'];

				// Envoi de la facture
				$mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc);

				if ($mailfile->sendfile())
				{
					$msg='<div class="ok">'.$langs->trans('MailSuccessfulySent',$from,$sendto).'.</div>';

					// Insertion action
					require_once(DOL_DOCUMENT_ROOT.'/contact.class.php');
					require_once(DOL_DOCUMENT_ROOT.'/actioncomm.class.php');
					$actioncomm = new ActionComm($db);
					$actioncomm->type_id     = $actiontypeid;
					$actioncomm->label       = $actionmsg2;
					$actioncomm->note        = $actionmsg;
					$actioncomm->date        = time();
					$actioncomm->percent     = 100;
					$actioncomm->contact     = new Contact($db,$sendtoid);
					$actioncomm->societe     = new Societe($db,$fac->socidp);
					$actioncomm->user        = $user;   // User qui a fait l'action
					$actioncomm->facid       = $fac->id;

					$ret=$actioncomm->add($user);       // User qui saisit l'action

					if ($ret < 0)
					{
						dolibarr_print_error($db);
					}
					else
					{
						// Renvoie sur la fiche
						Header('Location: facture.php?facid='.$fac->id.'&msg='.urlencode($msg));
						exit;
					}
				}
				else
				{
					$msg='<div class="error">'.$langs->trans('ErrorFailedToSendMail',$from,$sendto).' !</div>';
				}
			}
			else
			{
				$msg='<div class="error">'.$langs->trans('ErrorMailRecipientIsEmpty').'</div>';
				dolibarr_syslog('Le mail du destinataire est vide');
			}

		}
		else
		{
			dolibarr_syslog('Impossible de lire :'.$file);
		}
	}
	else
	{
		dolibarr_syslog('Impossible de lire les données de la facture. Le fichier facture n\'a peut-être pas été généré.');
	}
}

/*
 * Générer ou regénérer le PDF
 */
if ($_GET['action'] == 'pdf')
{
	// Generation de la facture définie dans /includes/modules/facture/modules_facture.php
	// Génère également le fichier meta dans le meme répertoire (pour faciliter les recherches et indexation)
	facture_pdf_create($db, $_GET['facid']);
}




/*********************************************************************
*
* Fonctions internes
*
**********************************************************************/
function print_date_range($date_start,$date_end)
{
	global $langs;

	if ($date_start && $date_end)
	{
		print ' ('.$langs->trans('DateFromTo',dolibarr_print_date($date_start),dolibarr_print_date($date_end)).')';
	}
	if ($date_start && ! $date_end)
	{
		print ' ('.$langs->trans('DateFrom',dolibarr_print_date($date_start)).')';
	}
	if (! $date_start && $date_end)
	{
		print ' ('.$langs->trans('DateUntil',dolibarr_print_date($date_end)).')';
	}
}



llxHeader('',$langs->trans('Bill'),'Facture');

$html = new Form($db);


/*********************************************************************
*
* Mode creation
*
**********************************************************************/
if ($_GET['action'] == 'create')
{
	print_titre($langs->trans('NewBill'));

	$soc = new Societe($db);

	if ($_GET['propalid'])
	{
		$propal = New Propal($db);
		$propal->fetch($_GET['propalid']);
		$societe_id = $propal->soc_id;
		$projet=$propal->projetidp;
		$soc->fetch($societe_id);
	}
	elseif ($_GET['commandeid'])
	{
		$commande = New Commande($db);
		$commande->fetch($_GET['commandeid']);
		$societe_id = $commande->soc_id;
		$projet=$commande-> projet_id;
		$ref_client=$commande->ref_client;
		$soc->fetch($societe_id);
	}
	elseif ($_GET['contratid'])
	{
		$contrat = New Contrat($db);
		$contrat->fetch($_GET['contratid']);
		$societe_id = $contrat->societe->id;
		$projet=$contrat->fk_projet;
		$soc=$contrat->societe;
	}
	else
	{
		$societe_id=$socidp;
		$soc->fetch($societe_id);
	}


	print '<form action="facture.php" method="post">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="socid" value="'.$soc->id.'">' ."\n";

	print '<table class="border" width="100%">';
	print '<tr><td>'.$langs->trans('Ref').'</td><td colspan="2">'.$langs->trans('Draft').'</td></tr>';
	print '<input name="facnumber" type="hidden" value="provisoire">';

	print '<tr><td>'.$langs->trans('Company').'</td><td colspan="2">'.$soc->nom.'</td>';
	print '</tr>';

	print '<tr><td>'.$langs->trans('Author').'</td><td>'.$user->fullname.'</td>';
	print '<td class="border">'.$langs->trans('Comments').'</td>';
	print '</tr>';

	print '<tr><td>'.$langs->trans('Date').'</td><td>';
	$html->select_date();
	print '</td>';

	// Notes
	$nbrows=4;
    if ($conf->global->FAC_USE_CUSTOMER_ORDER_REF) $nbrows++;
	print '<td rowspan="'.$nbrows.'" valign="top">';
	print '<textarea name="note" wrap="soft" cols="70" rows="'.ROWS_5.'">';
	if (is_object($propal))
	{
		print $propal->note;
	}
	if (is_object($commande))
	{
		print $commande->note;
	}
	if (is_object($contrat))
	{
		print $contrat->note;
	}
	print '</textarea></td></tr>';

	// Conditions de réglement
	$cond_reglement_id_defaut=1;
	print '<tr><td nowrap>'.$langs->trans('PaymentConditions').'</td><td>';
	$html->select_conditions_paiements($cond_reglement_id_defaut,'cond_reglement_id');
	print '</td></tr>';

	// Mode de réglement
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td>';
	$html->select_types_paiements('','mode_reglement_id');
	print '</td></tr>';

	// Projet
	if ($conf->projet->enabled)
	{
		$langs->load('projects');
		print '<tr><td>'.$langs->trans('Project').'</td><td>';
		$html->select_projects($societe_id, $projet, 'projetid');
		print '</td></tr>';
	}
	else
	{
		print '<tr><td colspan="2">&nbsp;</td></tr>';
	}

    /*
      \todo
      L'info "Reference commande client" est une carac de la commande et non de la facture.
      Elle devrait donc etre stockée sur l'objet commande lié à la facture et non sur la facture.
      Pour ceux qui utilisent ainsi, positionner la constante FAC_USE_CUSTOMER_ORDER_REF à 1.
    */
    if ($conf->global->FAC_USE_CUSTOMER_ORDER_REF)
    {
    	print '<tr><td>'.$langs->trans('RefCdeClient').'</td><td>';
    	print '<input type="text" name="ref_client" value="'.$ref_client.'">';
    	print '</td></tr>';
    }
    
	if ($_GET['propalid'] > 0)
	{
		print '<input type="hidden" name="amount"         value="'.$propal->price.'">'."\n";
		print '<input type="hidden" name="total"          value="'.$propal->total.'">'."\n";
		print '<input type="hidden" name="remise"         value="'.$propal->remise.'">'."\n";
		print '<input type="hidden" name="remise_percent" value="'.$propal->remise_percent.'">'."\n";
		print '<input type="hidden" name="tva"            value="'.$propal->tva.'">'."\n";
		print '<input type="hidden" name="propalid"       value="'.$_GET['propalid'].'">';

		print '<tr><td>'.$langs->trans('Proposal').'</td><td colspan="2">'.$propal->ref.'</td></tr>';
		print '<tr><td>'.$langs->trans('GlobalDiscount').'</td><td colspan="2">'.$propal->remise_percent.'%</td></tr>';
		print '<tr><td>'.$langs->trans('TotalHT').'</td><td colspan="2">'.price($propal->price).'</td></tr>';
		print '<tr><td>'.$langs->trans('TotalVAT').'</td><td colspan="2">'.price($propal->tva)."</td></tr>";
		print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($propal->total)."</td></tr>";
	}
	elseif ($_GET['commandeid'] > 0)
	{
		$commande->remise_percent=$soc->remise_client;
		print '<input type="hidden" name="commandeid" value="'.$commande->id.'">';
		print '<input type="hidden" name="remise_percent" value="'.$commande->remise_percent.'">'."\n";

		print '<tr><td>'.$langs->trans('Order').'</td><td colspan="2">'.$commande->ref.'</td></tr>';
		print '<tr><td>'.$langs->trans('TotalHT').'</td><td colspan="2">'.price($commande->total_ht).'</td></tr>';
		print '<tr><td>'.$langs->trans('TotalVAT').'</td><td colspan="2">'.price($commande->total_tva)."</td></tr>";
		print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($commande->total_ttc)."</td></tr>";
	}
	elseif ($_GET['contratid'] > 0)
	{
		// Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
		$contrat->remise=0;
		$contrat->remise_percent=$soc->remise_client;
		$contrat->update_price();

		print '<input type="hidden" name="amount"    value="'.$contrat->total_ht.'">'."\n";
		print '<input type="hidden" name="total"     value="'.$contrat->total_ttc.'">'."\n";
		print '<input type="hidden" name="remise"    value="'.$contrat->remise.'">'."\n";
		print '<input type="hidden" name="remise_percent"   value="'.$contrat->remise_percent.'">'."\n";
		print '<input type="hidden" name="tva"       value="'.$contrat->total_tva.'">'."\n";
		print '<input type="hidden" name="contratid" value="'.$_GET["contratid"].'">';

		print '<tr><td>'.$langs->trans('Contract').'</td><td colspan="2">'.$contrat->ref.'</td></tr>';
		print '<tr><td>'.$langs->trans('TotalHT').'</td><td colspan="2">'.price($contrat->total_ht).'</td></tr>';
		print '<tr><td>'.$langs->trans('TotalVAT').'</td><td colspan="2">'.price($contrat->total_tva)."</td></tr>";
		print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($contrat->total_ttc)."</td></tr>";
	}
	else
	{
		print '<tr><td colspan="3">&nbsp;</td></tr>';
		print '<tr><td colspan="3">';

		print '<table class="noborder">';
		print '<tr><td>'.$langs->trans('ProductsAndServices').'</td><td>'.$langs->trans('Qty').'</td><td>'.$langs->trans('Discount').'</td><td> &nbsp; &nbsp; </td>';
		if ($conf->service->enabled)
		{
			print '<td>Si produit de type service à durée limitée</td></tr>';
		}
		for ($i = 1 ; $i <= $NBLINES ; $i++)
		{
			print '<tr><td>';
			$html->select_produits('','idprod'.$i,'',$conf->produit->limit_size);
			print '</td>';
			print '<td><input type="text" size="3" name="qty'.$i.'" value="1"></td>';
			print '<td nowrap="nowrap"><input type="text" size="4" name="remise_percent'.$i.'" value="0">%</td>';
			print '<td>&nbsp;</td>';
			// Si le module service est actif, on propose des dates de début et fin à la ligne
			if ($conf->service->enabled)
			{
				print '<td nowrap="nowrap">';
				print 'Du ';
				print $html->select_date('','date_start'.$i,0,0,1);
				print '<br>au ';
				print $html->select_date('','date_end'.$i,0,0,1);
				print '</td>';
			}
			print "</tr>\n";
		}

		print '</table>';
		print '</td></tr>';
	}

	/*
	 * Factures récurrentes
	 */
	if ($_GET['propalid'] == 0 && $_GET['commandeid'] == 0 && $_GET['contratid'] == 0)
	{
		$sql = 'SELECT r.rowid, r.titre, r.amount FROM '.MAIN_DB_PREFIX.'facture_rec as r';
		$sql .= ' WHERE r.fk_soc = '.$soc->id;
		if ( $db->query($sql) )
		{
			$num = $db->num_rows();
			$i = 0;

			if ($num > 0)
			{
				print '<tr><td colspan="3">Factures récurrentes : <select class="flat" name="fac_rec">';
				print '<option value="0" selected="true"></option>';
				while ($i < $num)
				{
					$objp = $db->fetch_object();
					print '<option value="'.$objp->rowid.'">'.$objp->titre.' : '.$objp->amount.'</option>';
					$i++;
				}
				print '</select></td></tr>';
			}
			$db->free();
		}
		else
		{
			dolibarr_print_error($db);
		}
	}

	// Bouton "Create Draft"
	print '<tr><td colspan="3" align="center"><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'"></td></tr>';
	print "</form>\n";
	print "</table>\n";

	// Si creation depuis un propal
	if ($_GET['propalid'])
	{
		print '<br>';
		print_titre($langs->trans('ProductsAndServices'));

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Ref').'</td>';
		print '<td>'.$langs->trans('Description').'</td>';
		print '<td align="right">'.$langs->trans('VAT').'</td>';
		print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
		print '<td align="right">'.$langs->trans('Qty').'</td>';
		print '<td align="right">'.$langs->trans('Discount').'</td></tr>';

		// Lignes de propal produits prédéfinis
		$sql = 'SELECT pt.rowid, p.label as product, p.ref, pt.tva_tx, pt.price, pt.qty, p.rowid as prodid, pt.remise_percent, pt.description';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'propaldet as pt, '.MAIN_DB_PREFIX.'product as p WHERE pt.fk_product = p.rowid AND pt.fk_propal = '.$_GET['propalid'];
		$sql .= ' ORDER BY pt.rowid ASC';
		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$i = 0;
			$var=True;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);
				$var=!$var;
				print '<tr '.$bc[$var].'><td><a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->prodid.'">'.img_object($langs->trans(''),'product').' '.$objp->ref.'</a>';
				print $objp->product?' - '.$objp->product:'';
				print "</td>\n";
				print '<td>';
				print dolibarr_trunc($objp->description,60);
				print '</td>';
				print '<td align="right">'.$objp->tva_tx.'%</td>';
				print '<td align="right">'.price($objp->price).'</td>';
				print '<td align="right">'.$objp->qty.'</td>';
				print '<td align="right">'.$objp->remise_percent.'%</td>';
				print '</tr>';
				$i++;
			}
		}
		else
		{
			dolibarr_print_error($db);
		}
		// Lignes de propal non produits prédéfinis
		$sql = 'SELECT pt.rowid, pt.description as product, pt.tva_tx, pt.price, pt.qty, pt.remise_percent';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'propaldet as pt ';
		$sql .= ' WHERE  pt.fk_propal = '.$_GET['propalid'];
		$sql .= ' AND (pt.fk_product = 0 or pt.fk_product is null)';
		$sql .= ' ORDER BY pt.rowid ASC';
		$result=$db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);
				$var=!$var;
				print '<tr '.$bc[$var].'><td>&nbsp;</td>';
				print '<td>'.dolibarr_trunc($objp->product,60).'</td>';
				print '<td align="right">'.$objp->tva_tx.'%</td>';
				print '<td align="right">'.price($objp->price).'</td>';
				print '<td align="right">'.$objp->qty.'</td>';
				print '<td align="right">'.$objp->remise_percent.'%</td>';
				print '</tr>';
				$i++;
			}
		}
		else
		{
			dolibarr_print_error($db);
		}

		print '</table>';
	}

	// Si creation depuis une commande
	if ($_GET['commandeid'])
	{
		print '<br>';
		print_titre($langs->trans('Products'));

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Ref').'</td>';
		print '<td>'.$langs->trans('Description').'</td>';
		print '<td align="right">'.$langs->trans('VAT').'</td>';
		print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
		print '<td align="right">'.$langs->trans('Qty').'</td>';
		print '<td align="right">'.$langs->trans('Discount').'</td></tr>';

		$sql = 'SELECT pt.rowid, pt.subprice, pt.tva_tx, pt.qty, pt.remise_percent, pt.description,';
		$sql.= ' p.label as product, p.ref, p.rowid as prodid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'commandedet as pt, '.MAIN_DB_PREFIX.'product as p';
		$sql.= ' WHERE pt.fk_product = p.rowid AND pt.fk_commande = '.$commande->id;
		$sql.= ' ORDER BY pt.rowid ASC';

		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$i = 0;
			$var=True;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);
				$var=!$var;
				print '<tr '.$bc[$var].'><td><a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->prodid.'">'.img_object($langs->trans(''),'product').' '.$objp->ref.'</a>';
				print $objp->product?' - '.$objp->product:'';
				print "</td>\n";
				print '<td>';
				print dolibarr_trunc($objp->description,60);
				print '</td>';
				print '<td align="right">'.$objp->tva_tx.'%</td>';
				print '<td align="right">'.price($objp->subprice).'</td>';
				print '<td align="right">'.$objp->qty.'</td>';
				print '<td align="right">'.$objp->remise_percent.'%</td>';
				print '</tr>';
				$i++;
			}
		}
		else
		{
			dolibarr_print_error($db);
		}
		// Lignes de commande non produits prédéfinis
		$sql  = 'SELECT pt.rowid, pt.description as product, pt.tva_tx, pt.subprice, pt.qty, pt.remise_percent';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'commandedet as pt';
		$sql .= ' WHERE  pt.fk_commande = '.$commande->id;
		$sql .= ' AND (pt.fk_product = 0 or pt.fk_product is null)';
		$sql .= ' ORDER BY pt.rowid ASC';

		$result=$db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);
				$var=!$var;
				print '<tr '.$bc[$var].'><td>&nbsp;</td>';
				print '<td>'.dolibarr_trunc($objp->product,60).'</td>';
				print '<td align="right">'.$objp->tva_tx.'%</td>';
				print '<td align="right">'.price($objp->subprice).'</td>';
				print '<td align="right">'.$objp->qty.'</td>';
				print '<td align="right">'.$objp->remise_percent.'%</td>';
				print '</tr>';
				$i++;
			}
		}
		else
		{
			dolibarr_print_error($db);
		}

		print '</table>';
	}

	// Si creation depuis un contrat
	if ($_GET['contratid'])
	{
		print '<br>';
		print_titre($langs->trans('Services'));

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Ref').'</td>';
		print '<td>'.$langs->trans('Description').'</td>';
		print '<td align="right">'.$langs->trans('VAT').'</td>';
		print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
		print '<td align="right">'.$langs->trans('Qty').'</td>';
		print '<td align="right">'.$langs->trans('Discount').'</td></tr>';

		// Lignes de contrat produits prédéfinis
		$sql = 'SELECT pt.rowid, pt.subprice, pt.tva_tx, pt.qty, pt.remise_percent, pt.description,';
		$sql.= ' pt.date_ouverture_prevue as date_debut_prevue, pt.date_ouverture as date_debut_reel,';
		$sql.= ' pt.date_fin_validite as date_fin_prevue, pt.date_cloture as date_fin_reel,';
		$sql.= ' p.label as product, p.ref, p.rowid as prodid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'contratdet as pt, '.MAIN_DB_PREFIX.'product as p';
		$sql.= ' WHERE pt.fk_product = p.rowid AND pt.fk_contrat = '.$contrat->id;
		$sql.= ' ORDER BY pt.rowid ASC';

		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$i = 0;
			$var=True;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);
				$var=!$var;
				print '<tr '.$bc[$var].'><td><a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->prodid.'">'.img_object($langs->trans(''),'service').' '.$objp->ref.'</a>';
				print $objp->product?' - '.$objp->product:'';
				// Plage de dates
				$date_start=$objp->date_debut_prevue;
				if ($objp->date_debut_reel) $date_start=$objp->date_debut_reel;
				$date_end=$objp->date_fin_prevue;
				if ($objp->date_fin_reel) $date_end=$objp->date_fin_reel;
				print_date_range($date_start,$date_end);

				print "</td>\n";
				print '<td>';
				print dolibarr_trunc($objp->description,60);
				print '</td>';
				print '<td align="right">'.$objp->tva_tx.'%</td>';
				print '<td align="right">'.price($objp->subprice).'</td>';
				print '<td align="right">'.$objp->qty.'</td>';
				print '<td align="right">'.$objp->remise_percent.'%</td>';
				print '</tr>';
				$i++;
			}
		}
		else
		{
			dolibarr_print_error($db);
		}
		// Lignes de contrat non produits prédéfinis
		$sql  = 'SELECT pt.rowid, pt.description as product, pt.tva_tx, pt.subprice, pt.qty, pt.remise_percent,';
		$sql.= ' pt.date_ouverture_prevue as date_debut_prevue, pt.date_ouverture as date_debut_reel,';
		$sql.= ' pt.date_fin_validite as date_fin_prevue, pt.date_cloture as date_fin_reel';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'contratdet as pt';
		$sql.= ' WHERE  pt.fk_contrat = '.$contrat->id;
		$sql.= ' AND (pt.fk_product = 0 or pt.fk_product is null)';
		$sql.= ' ORDER BY pt.rowid ASC';

		$result=$db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);
				$var=!$var;
				print '<tr '.$bc[$var].'><td>&nbsp;</td>';
				print '<td>'.dolibarr_trunc($objp->product,60).'</td>';
				print '<td align="right">'.$objp->tva_tx.'%</td>';
				print '<td align="right">'.price($objp->subprice).'</td>';
				print '<td align="right">'.$objp->qty.'</td>';
				print '<td align="right">'.$objp->remise_percent.'%</td>';
				print '</tr>';
				$i++;
			}
		}
		else
		{
			dolibarr_print_error($db);
		}

		print '</table>';
	}

}
else
{
	if ($_GET['facid'] > 0)
	{
		/* *************************************************************************** */
		/*                                                                             */
		/* Fiche en mode visu                                                          */
		/*                                                                             */
		/* *************************************************************************** */

		if ($msg) print $msg.'<br>';

		$fac = New Facture($db);
		if ( $fac->fetch($_GET['facid'], $user->societe_id) > 0)
		{
			$soc = new Societe($db, $fac->socidp);
			$soc->fetch($fac->socidp);

			$author = new User($db);
			$author->id = $fac->user_author;
			$author->fetch();

			$h = 0;

			$head[$h][0] = DOL_URL_ROOT.'/compta/facture.php?facid='.$fac->id;
			$head[$h][1] = $langs->trans('CardBill');
			$hselected = $h;
			$h++;

			if ($conf->use_preview_tabs)
			{
				$head[$h][0] = DOL_URL_ROOT.'/compta/facture/apercu.php?facid='.$fac->id;
				$head[$h][1] = $langs->trans('Preview');
				$h++;
			}

			if ($fac->mode_reglement_code == 'PRE')
			{
				$head[$h][0] = DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$fac->id;
				$head[$h][1] = $langs->trans('StandingOrders');
				$h++;
			}

			$head[$h][0] = DOL_URL_ROOT.'/compta/facture/note.php?facid='.$fac->id;
			$head[$h][1] = $langs->trans('Note');
			$h++;
			$head[$h][0] = DOL_URL_ROOT.'/compta/facture/info.php?facid='.$fac->id;
			$head[$h][1] = $langs->trans('Info');
			$h++;

			dolibarr_fiche_head($head, $hselected, $langs->trans('Bill').' : '.$fac->ref);


			/*
			* Confirmation de la suppression de la facture
			*
			*/
			if ($_GET['action'] == 'delete')
			{
				$html->form_confirm($_SERVER['PHP_SELF'].'?facid='.$fac->id,$langs->trans('DeleteBill'),$langs->trans('ConfirmDeleteBill'),'confirm_delete');
				print '<br />';
			}

			/*
 			 * Confirmation de la validation
 			 */
			if ($_GET['action'] == 'valid')
			{
				$numfa = facture_get_num($soc);
				$html->form_confirm($_SERVER["PHP_SELF"].'?facid='.$fac->id,$langs->trans('ValidateBill'),$langs->trans('ConfirmValidateBill',$numfa),'confirm_valid');
				print '<br />';
			}

			/*
 			 * Confirmation du classement payé
 			 */
			if ($_GET['action'] == 'payed')
			{
				$html->form_confirm($_SERVER["PHP_SELF"].'?facid='.$fac->id,$langs->trans('ClassifyPayed'),$langs->trans('ConfirmClassifyPayedBill',$fac->ref),'confirm_payed');
				print '<br />';
			}

			/*
			 * Confirmation du classement abandonn
			 */
			if ($_GET['action'] == 'canceled')
			{
				$html->form_confirm($_SERVER['PHP_SELF'].'?facid='.$fac->id,$langs->trans('CancelBill'),$langs->trans('ConfirmCancelBill',$fac->ref),'confirm_canceled');
				print '<br />';
			}

			/*
			*   Facture
			*/
			print '<table class="border" width="100%">';
			
			// Société
			print '<tr><td>'.$langs->trans('Company').'</td>';
			print '<td colspan="5">';
			print '<a href="fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></td>';
			print '</tr>';

			// Dates
			print '<tr><td>'.$langs->trans('Date').'</td>';
			print '<td colspan="3">'.dolibarr_print_date($fac->date,'%A %d %B %Y').'</td>';
			print '<td>'.$langs->trans('DateClosing').'</td><td>' . dolibarr_print_date($fac->date_lim_reglement,'%A %d %B %Y');
			if ($fac->date_lim_reglement < (time() - $conf->facture->client->warning_delay) && ! $fac->paye && $fac->statut == 1 && ! $fac->am) print img_warning($langs->trans('Late'));
			print '</td></tr>';

			// Conditions et modes de réglement
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('PaymentConditions');
			print '</td>';
			if ($_GET['action'] != 'editconditions' && $fac->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;facid='.$fac->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($_GET['action'] == 'editconditions')
			{
				$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->cond_reglement_id,'cond_reglement_id');
			}
			else
			{
				$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->cond_reglement_id,'none');
			}
			print '</td>';
			print '<td width="25%">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('PaymentMode');
			print '</td>';
			if ($_GET['action'] != 'editmode' && $fac->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;facid='.$fac->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td width="25%">';
			if ($_GET['action'] == 'editmode')
			{
				$html->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->mode_reglement_id,'mode_reglement_id');
			}
			else
			{
				$html->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->mode_reglement_id,'none');
			}
			print '</td></tr>';

			// Projet
			print '<tr>';
			if ($conf->projet->enabled)
			{
				$langs->load('projects');
				print '<td>';
				
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('Project');
				print '</td>';
				if ($_GET['action'] != 'classer')
				{
				    print '<td align="right"><a href="facture.php?action=classer&amp;facid='.$fac->id.'">';
				    print img_edit($langs->trans('SetProject'),1);
				    print '</a></td>';
				}
				print '</tr></table>';
				
				print '</td><td colspan="3">';
				if ($_GET['action'] == 'classer')
				{
					$html->form_project($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->fk_soc,$fac->projetid,'projetid');
				}
				else
				{
					$html->form_project($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->fk_soc,$fac->projetid,'none');
				}
				print '</td>';
			}
			else
			{
				print '<td>&nbsp;</td><td colspan="3">&nbsp;</td>';
			}

            $nbrows=8;
            if ($conf->global->FAC_USE_CUSTOMER_ORDER_REF) $nbrows++;
			print '<td rowspan="'.$nbrows.'" colspan="2" valign="top">';

			/*
			 * Liste des paiements
			 */
			print $langs->trans('Payments').' :<br>';
			$sql = 'SELECT '.$db->pdate('datep').' as dp, pf.amount,';
			$sql.= ' c.libelle as paiement_type, p.num_paiement, p.rowid';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement as p, '.MAIN_DB_PREFIX.'c_paiement as c, '.MAIN_DB_PREFIX.'paiement_facture as pf';
			$sql.= ' WHERE pf.fk_facture = '.$fac->id.' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';
			$sql.= ' ORDER BY dp DESC';

			$result = $db->query($sql);

			if ($result)
			{
				$num = $db->num_rows($result);
				$i = 0; $totalpaye = 0;
				print '<table class="noborder" width="100%">';
				print '<tr class="liste_titre"><td>'.$langs->trans('Date').'</td><td>'.$langs->trans('Type').'</td>';
				print '<td align="right">'.$langs->trans('Amount').'</td><td>&nbsp;</td></tr>';

				$var=True;
				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$var=!$var;
					print '<tr '.$bc[$var].'><td>';
					print '<a href="'.DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans('ShowPayment'),'payment').'</a>';
					print '&nbsp;'.strftime('%d %B %Y',$objp->dp).'</td>';
					print '<td>'.$objp->paiement_type.' '.$objp->num_paiement.'</td>';
					print '<td align="right">'.price($objp->amount).'</td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td>';
					print '</tr>';
					$totalpaye += $objp->amount;
					$i++;
				}

				if ($fac->paye == 0)
				{
					print '<tr><td colspan="2" align="right">'.$langs->trans('AlreadyPayed').' :</td><td align="right"><b>'.price($totalpaye).'</b></td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
					print '<tr><td colspan="2" align="right">'.$langs->trans("Billed").' :</td><td align="right" style="border: 1px solid;">'.price($fac->total_ttc).'</td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

					$resteapayer = $fac->total_ttc - $totalpaye;

					print '<tr><td colspan="2" align="right">'.$langs->trans('RemainderToPay').' :</td>';
					print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($resteapayer).'</b></td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
				}
				print '</table>';
				$db->free($result);
			}
			else
			{
				dolibarr_print_error($db);
			}

			print '</td></tr>';

			print '<tr><td>'.$langs->trans('Author').'</td><td colspan="3">'.$author->fullname.'</td></tr>';

			print '<tr><td>'.$langs->trans('GlobalDiscount').'</td>';
			if ($fac->brouillon == 1 && $user->rights->facture->creer)
			{
				print '<form action="facture.php?facid='.$fac->id.'" method="post">';
				print '<input type="hidden" name="action" value="setremise">';
				print '<td colspan="3"><input type="text" name="remise" size="3" value="'.$fac->remise_percent.'">% ';
				print '<input type="submit" class="button" value="'.$langs->trans('Modify').'"></td>';
				print '</form>';
			}
			else
			{
				print '<td colspan="3">'.$fac->remise_percent.'%</td>';
			}
			print '</tr>';

            /*
              \todo
              L'info "Reference commande client" est une carac de la commande et non de la facture.
              Elle devrait donc etre stockée sur l'objet commande lié à la facture et non sur la facture.
              Pour ceux qui utilisent ainsi, positionner la constante FAC_USE_CUSTOMER_ORDER_REF à 1.
            */
            if ($conf->global->FAC_USE_CUSTOMER_ORDER_REF)
            {
			    print '<tr><td>'.$langs->trans('RefCdeClient').'</td>';

    			if ($fac->brouillon == 1 && $user->rights->facture->creer)
    			{
    					print '<form action="facture.php?facid='.$fac->id.'" method="post">';
    					print '<input type="hidden" name="action" value="set_ref_client">';
    					print '<td colspan="3"><input type="text" name="ref_client" size="20" value="'.$fac->ref_client.'">';
    					print '<input type="submit" class="button" value="'.$langs->trans('Modify').'"></td>';
    					print '</form>';
    			}
    			else
    			{
    				print '<td colspan="3">'.$fac->ref_client.'</td>';
    			}
            }

			print '<tr><td>'.$langs->trans('AmountHT').'</td>';
			print '<td align="right" colspan="2" nowrap><b>'.price($fac->total_ht).'</b></td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

			print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right" colspan="2" nowrap>'.price($fac->total_tva).'</td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right" colspan="2" nowrap>'.price($fac->total_ttc).'</td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

			// Statut
			print '<tr><td>'.$langs->trans('Status').'</td><td align="left" colspan="3">'.($fac->getLibStatut()).'</td></tr>';

			if ($fac->note)
			{
				print '<tr><td colspan="4" valign="top">'.$langs->trans('Note').' : '.nl2br($fac->note).'</td></tr>';
			}
			else
			{
				print '<tr><td colspan="4">&nbsp;</td></tr>';
			}

			print '</table><br>';

			/*
			 * Lignes de factures
			 */
			$sql  = 'SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_taux,';
			$sql .= ' l.remise_percent, l.subprice,';
			$sql .= ' '.$db->pdate('l.date_start').' as date_start,';
			$sql .= ' '.$db->pdate('l.date_end').' as date_end, ';
			$sql .= ' p.ref, p.fk_product_type, p.label as product';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'facturedet as l ';
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product p ON l.fk_product=p.rowid';
			$sql .= ' WHERE l.fk_facture = '.$fac->id;
			$sql .= ' ORDER BY l.rang ASC, l.rowid';

			$resql = $db->query($sql);
			if ($resql)
			{
				$num_lignes = $db->num_rows($resql);
				$i = 0; $total = 0;

				print '<table class="noborder" width="100%">';
				if ($num_lignes)
				{
					print '<tr class="liste_titre">';
					print '<td>'.$langs->trans('Description').'</td>';
					print '<td align="right" width="50">'.$langs->trans('VAT').'</td>';
					print '<td align="right" width="80">'.$langs->trans('PriceUHT').'</td>';
					print '<td align="right" width="50">'.$langs->trans('Qty').'</td>';
					print '<td align="right" width="50">'.$langs->trans('Discount').'</td>';
					print '<td align="right" width="50">'.$langs->trans('AmountHT').'</td>';
					print '<td colspan="3">&nbsp;</td>';
					print "</tr>\n";
				}
				$var=true;
				while ($i < $num_lignes)
				{
					$objp = $db->fetch_object($resql);
					$var=!$var;

					// Ligne en mode visu
					if ($_GET['action'] != 'editline' || $_GET['rowid'] != $objp->rowid)
					{
						print '<tr '.$bc[$var].'>';
						if ($objp->fk_product > 0)
						{
							print '<td><a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
							if ($objp->fk_product_type) print img_object($langs->trans('ShowService'),'service');
							else print img_object($langs->trans('ShowProduct'),'product');
							print ' '.$objp->ref.'</a>';
							print ' - '.nl2br(stripslashes($objp->product));
							print_date_range($objp->date_start,$objp->date_end);
							print ($objp->description && $objp->description!=$objp->product)?'<br>'.$objp->description:'';
							print '</td>';
						}
						else
						{
							print '<td>'.stripslashes(nl2br($objp->description));
							print_date_range($objp->date_start,$objp->date_end);
							print "</td>\n";
						}
						print '<td align="right">'.$objp->tva_taux.'%</td>';
						print '<td align="right">'.price($objp->subprice)."</td>\n";
						print '<td align="right">'.$objp->qty.'</td>';
						if ($objp->remise_percent > 0)
						{
							print '<td align="right">'.$objp->remise_percent."%</td>\n";
						}
						else
						{
							print '<td>&nbsp;</td>';
						}
						print '<td align="right">'.price($objp->subprice*$objp->qty*(100-$objp->remise_percent)/100)."</td>\n";

						// Icone d'edition et suppression
						if ($fac->statut == 0  && $user->rights->facture->creer)
						{
							print '<td align="right"><a href="facture.php?facid='.$fac->id.'&amp;action=editline&amp;rowid='.$objp->rowid.'">';
							print img_edit();
							print '</a></td>';
							print '<td align="right"><a href="facture.php?facid='.$fac->id.'&amp;action=deleteline&amp;rowid='.$objp->rowid.'">';
							print img_delete();
							print '</a></td>';
							print '<td align="right">';
							if ($i > 0)
							{
								print '<a href="facture.php?facid='.$fac->id.'&amp;action=up&amp;rowid='.$objp->rowid.'">';
								print img_up();
								print '</a>';
							}
							if ($i < $num_lignes-1)
							{
								print '<a href="facture.php?facid='.$fac->id.'&amp;action=down&amp;rowid='.$objp->rowid.'">';
								print img_down();
								print '</a>';
							}
							print '</td>';
						}
						else
						{
							print '<td colspan="3">&nbsp;</td>';
						}
						print '</tr>';
					}

					// Ligne en mode update
					if ($_GET['action'] == 'editline' && $user->rights->facture->creer && $_GET['rowid'] == $objp->rowid)
					{
						print '<form action="facture.php" method="post">';
						print '<input type="hidden" name="action" value="updateligne">';
						print '<input type="hidden" name="facid" value="'.$fac->id.'">';
						print '<input type="hidden" name="rowid" value="'.$_GET['rowid'].'">';
						print '<tr '.$bc[$var].'>';
						print '<td>';
						if ($objp->fk_product > 0)
						{
							print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
							if ($objp->fk_product_type) print img_object($langs->trans('ShowService'),'service');
							else print img_object($langs->trans('ShowProduct'),'product');
							print ' '.$objp->ref.'</a>';
							print ' - '.stripslashes(nl2br($objp->product));
							print '<br>';
						}
						print '<textarea name="desc" cols="70" rows="'.ROWS_2.'">'.stripslashes($objp->description).'</textarea></td>';
						print '<td align="right">';
						print $html->select_tva('tva_tx',$objp->tva_taux);
						print '</td>';
						print '<td align="right"><input size="6" type="text" name="price" value="'.price($objp->subprice).'"></td>';
						print '<td align="right"><input size="2" type="text" name="qty" value="'.$objp->qty.'"></td>';
						print '<td align="right" nowrap><input size="2" type="text" name="remise_percent" value="'.$objp->remise_percent.'">%</td>';
						print '<td align="center" rowspan="2" colspan="4" valign="center"><input type="submit" class="button" name="save" value="'.$langs->trans('Save').'">';
						print '<br /><input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></td>';
						print '</tr>' . "\n";
						if ($conf->service->enabled)
						{
							print '<tr '.$bc[$var].'>';
							print '<td colspan="5">Si produit de type service à durée limitée: Du ';
							print $html->select_date($objp->date_start,'date_start',0,0,$objp->date_start?0:1);
							print ' au ';
							print $html->select_date($objp->date_end,'date_end',0,0,$objp->date_end?0:1);
							print '</td>';
							print '</tr>';
						}
						print "</form>\n";
					}

					$total = $total + ($objp->qty * $objp->price);
					$i++;
				}

				$db->free($resql);
			}
			else
			{
				dolibarr_print_error($db);
			}

			/*
			 * Ajouter une ligne
			 */
			if ($fac->statut == 0 && $user->rights->facture->creer && $_GET['action'] <> 'valid')
			{
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans('Description').'</td>';
				print '<td align="right">'.$langs->trans('VAT').'</td>';
				print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
				print '<td align="right">'.$langs->trans('Qty').'</td>';
				print '<td align="right">'.$langs->trans('Discount').'</td>';
				print '<td>&nbsp;</td>';
				print '<td>&nbsp;</td>';
				print '<td>&nbsp;</td>';
				print '<td>&nbsp;</td>';
				print "</tr>\n";

                // Ajout produit produits/services personalisés
				print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
				print '<input type="hidden" name="facid" value="'.$fac->id.'">';
				print '<input type="hidden" name="action" value="addligne">';

                $var=true;
				print '<tr '.$bc[$var].'>';
				print '<td><textarea name="desc" cols="70" rows="'.ROWS_2.'"></textarea></td>';
				print '<td align="right">';
				$html->select_tva('tva_tx',$conf->defaulttx);
				print '</td>';
				print '<td align="right"><input type="text" name="pu" size="6"></td>';
				print '<td align="right"><input type="text" name="qty" value="1" size="2"></td>';
				print '<td align="right" nowrap><input type="text" name="remise_percent" size="2" value="0">%</td>';
				print '<td align="center" valign="middle" rowspan="2" colspan="4"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
				print '</tr>';
				if ($conf->service->enabled)
				{
					print '<tr '.$bc[$var].'>';
					print '<td colspan="5">Si produit de type service à durée limitée: Du ';
					print $html->select_date('','date_start',0,0,1);
					print ' au ';
					print $html->select_date('','date_end',0,0,1);
					print '</td>';
					print '</tr>';
				}
				print '</form>';

                // Ajout de produits/services prédéfinis
                if ($conf->produit->enabled)
                {
    				print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
    				print '<input type="hidden" name="facid" value="'.$fac->id.'">';
    				print '<input type="hidden" name="action" value="addligne">';
    
                    $var=! $var;
    				print '<tr '.$bc[$var].'>';
    				print '<td colspan="2">';
                    $html->select_produits('','idprod','',$conf->produit->limit_size);
                    print '<br>';
                    print '<textarea name="desc" cols="70" rows="'.ROWS_2.'"></textarea></td>';
                    print '<td>&nbsp;</td>';
    				print '<td align="right"><input type="text" name="qty" value="1" size="2"></td>';
    				print '<td align="right" nowrap><input type="text" name="remise_percent" size="2" value="0">%</td>';
    				print '<td align="center" valign="middle" rowspan="2" colspan="4"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
    				print '</tr>';
    				if ($conf->service->enabled)
    				{
    					print '<tr '.$bc[$var].'>';
    					print '<td colspan="5">Si produit de type service à durée limitée: Du ';
    					print $html->select_date('','date_start',0,0,1);
    					print ' au ';
    					print $html->select_date('','date_end',0,0,1);
    					print '</td>';
    					print '</tr>';
    				}
    				print '</form>';
                }
			}
			print "</table>\n";

			print "</div>\n";


			/*
			 * Boutons actions
			 */

			if ($user->societe_id == 0 && $_GET['action'] <> 'valid' && $_GET['action'] <> 'editline')
			{
				print '<div class="tabsAction">';
				// Valider
				if ($fac->statut == 0 && $num_lignes > 0)
				{
					if ($user->rights->facture->valider)
					{
						print '  <a class="butAction" href="facture.php?facid='.$fac->id.'&amp;action=valid">'.$langs->trans('Validate').'</a>';
					}
				}
				else
				{
					// Générer
					if ($fac->statut == 1 && ($fac->paye == 0 || $user->admin) && $user->rights->facture->creer)
					{
						if ($fac->paye == 0)
						{
							print '  <a class="butAction" href="facture.php?facid='.$fac->id.'&amp;action=pdf">'.$langs->trans('BuildPDF').'</a>';
						}
						else
						{
							print '  <a class="butAction" href="facture.php?facid='.$fac->id.'&amp;action=pdf">'.$langs->trans('RebuildPDF').'</a>';
						}
					}
				}

				// Supprimer
				if ($fac->statut == 0 && $user->rights->facture->supprimer && $_GET['action'] != 'delete')
				{
					print '<a class="butActionDelete" href="facture.php?facid='.$fac->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
				}

				// Envoyer
				if ($fac->statut == 1 && $user->rights->facture->envoyer)
				{
					print '  <a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=presend">'.$langs->trans('Send').'</a>';
				}

				// Envoyer une relance
				if ($fac->statut == 1 && price($resteapayer) > 0 && $user->rights->facture->envoyer)
				{
					print '  <a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=prerelance">'.$langs->trans('SendRemind').'</a>';
				}

				// Emettre paiement
				if ($fac->statut == 1 && $fac->paye == 0 && $user->rights->facture->paiement)
				{
					print '  <a class="butAction" href="paiement.php?facid='.$fac->id.'&amp;action=create">'.$langs->trans('DoPaiement').'</a>';
				}

				// Classer 'payé'
				if ($fac->statut == 1 && price($resteapayer) <= 0
				&& $fac->paye == 0 && $user->rights->facture->paiement)
				{
					print '  <a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=payed">'.$langs->trans('ClassifyPayed').'</a>';
				}

				// Classer 'abandonnée' (possible si validée et pas encore classer payée)
				if ($fac->statut == 1 && $fac->paye == 0 && $user->rights->facture->paiement)
				{
					print '  <a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=canceled">'.$langs->trans('ClassifyCanceled').'</a>';
				}

				// Récurrente
				if (! defined('FACTURE_DISABLE_RECUR') || FACTURE_DISABLE_RECUR == 0) 	// Possibilité de désactiver les factures récurrentes
				{
					if ($fac->statut > 0)
					{
						print '  <a class="butAction" href="facture/fiche-rec.php?facid='.$fac->id.'&amp;action=create">Récurrente</a>';
					}
				}

				print '</div>';
			}

			print '<table width="100%"><tr><td width="50%" valign="top">';

			/*
			 * Documents générés
			 */
			$filename=sanitize_string($fac->ref);
			$filedir=$conf->facture->dir_output . '/' . sanitize_string($fac->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?facid='.$fac->id;
//            $genallowed=($fac->statut == 1 && ($fac->paye == 0 || $user->admin) && $user->rights->facture->creer);
//            $delallowed=$user->rights->facture->supprimer;
			$genallowed=0;
			$delallowed=0;

			$var=true;

			print '<br>';
			$html->show_documents('facture',$filename,$filedir,$urlsource,$genallowed,$delallowed);

			/*
			 *   Propales rattachées
			 */
			$sql = 'SELECT '.$db->pdate('p.datep').' as dp, p.price, p.ref, p.rowid as propalid';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'propal as p, '.MAIN_DB_PREFIX.'fa_pr as fp WHERE fp.fk_propal = p.rowid AND fp.fk_facture = '.$fac->id;

			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				if ($num)
				{
					$i = 0; $total = 0;
					print '<br>';
					print_titre($langs->trans('RelatedCommercialProposals'));
					print '<table class="noborder" width="100%">';
					print '<tr class="liste_titre">';
					print '<td>'.$langs->trans('Ref').'</td>';
					print '<td>'.$langs->trans('Date').'</td>';
					print '<td align="right">'.$langs->trans('Price').'</td>';
					print '</tr>';

					$var=True;
					while ($i < $num)
					{
						$objp = $db->fetch_object($resql);
						$var=!$var;
						print '<tr '.$bc[$var].'>';
						print '<td><a href="propal.php?propalid='.$objp->propalid.'">'.img_object($langs->trans('ShowPropal'),'propal').' '.$objp->ref.'</a></td>';
						print '<td>'.dolibarr_print_date($objp->dp).'</td>';
						print '<td align="right">'.price($objp->price).'</td>';
						print '</tr>';
						$total = $total + $objp->price;
						$i++;
					}
					print '<tr class="liste_total"><td>&nbsp;</td><td align="left">'.$langs->trans('TotalHT').'</td><td align="right">'.price($total).'</td></tr>';
					print '</table>';
				}
			}
			else
			{
				dolibarr_print_error($db);
			}

			/*
			 * Commandes rattachées
			 */
			if($conf->commande->enabled)
			{
				$sql = 'SELECT '.$db->pdate('c.date_commande').' as date_commande, c.total_ht, c.ref, c.ref_client, c.rowid as id';
				$sql .= ' FROM '.MAIN_DB_PREFIX.'commande as c, '.MAIN_DB_PREFIX.'co_fa as co_fa WHERE co_fa.fk_commande = c.rowid AND co_fa.fk_facture = '.$fac->id;
				$resql = $db->query($sql);
				if ($resql)
				{
					$num = $db->num_rows($resql);
					if ($num)
					{
						$i = 0; $total = 0;
						print '<br>';
						print_titre($langs->trans('RelatedOrders'));
						print '<table class="noborder" width="100%">';
						print '<tr class="liste_titre">';
						print '<td>'.$langs->trans('Ref').'</td>';
           			    print '<td>'.$langs->trans('RefCdeClientShort').'</td>';
						print '<td align="center">'.$langs->trans('Date').'</td>';
						print '<td align="right">'.$langs->trans('AmountHT').'</td>';
						print '</tr>';
						$var=true;
						while ($i < $num)
						{
							$objp = $db->fetch_object($resql);
							$var=!$var;
							print '<tr '.$bc[$var].'><td>';
							print '<a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$objp->id.'">'.img_object($langs->trans('ShowOrder'), 'order').' '.$objp->ref."</a></td>\n";
   							print '<td>'.$objp->ref_client.'</td>';
							print '<td align="center">'.dolibarr_print_date($objp->date_commande).'</td>';
							print '<td align="right">'.price($objp->total_ht).'</td>';
							print "</tr>\n";
							$total = $total + $objp->total_ht;
							$i++;
						}
						print '<tr class="liste_total">';
						print '<td align="left">'.$langs->trans('TotalHT').'</td>';
   						print '<td>&nbsp;</td>';
   						print '<td>&nbsp;</td>';
						print '<td align="right">'.price($total).'</td></tr>';
						print '</table>';
					}
				}
				else
				{
					dolibarr_print_error($db);
				}
			}

			print '</td><td valign="top" width="50%">';

			/*
			 * Liste des actions propres à la facture
			 */
			$sql = 'SELECT id, '.$db->pdate('a.datea').' as da, a.label, a.note, code';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'actioncomm as a, '.MAIN_DB_PREFIX.'user as u ';
			$sql .= ' WHERE a.fk_user_author = u.rowid ';
			$sql .= ' AND a.fk_action in (9,10) ';
			$sql .= ' AND a.fk_soc = '.$fac->socidp ;
			$sql .= ' AND a.fk_facture = '.$fac->id;

			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				if ($num)
				{
					print '<br>';
					print_titre($langs->trans('ActionsOnBill'));

					$i = 0; $total = 0;
					print '<table class="border" width="100%">';
					print '<tr '.$bc[$var].'><td>'.$langs->trans('Ref').'</td><td>'.$langs->trans('Date').'</td><td>'.$langs->trans('Action').'</td><td>'.$langs->trans('By').'</td></tr>';
					print "\n";

					$var=True;
					while ($i < $num)
					{
						$objp = $db->fetch_object($resql);
						$var=!$var;
						print '<tr '.$bc[$var].'>';
						print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$objp->id.'">'.img_object($langs->trans('ShowTask'),'task').' '.$objp->id.'</a></td>';
						print '<td>'.dolibarr_print_date($objp->da).'</td>';
						print '<td>'.stripslashes($objp->label).'</td>';
						print '<td>'.$objp->code.'</td>';
						print '</tr>';
						$i++;
					}
					print '</table>';
				}
			}
			else
			{
				dolibarr_print_error($db);
			}

			print '</td></tr></table>';


			/*
			 * Affiche formulaire mail
			 */
			if ($_GET['action'] == 'presend')
			{
				print '<br>';
				print_titre($langs->trans('SendBillByMail'));

				$liste[0]='&nbsp;';
				foreach ($soc->contact_email_array() as $key=>$value)
				{
					$liste[$key]=$value;
				}

				// Créé l'objet formulaire mail
				include_once(DOL_DOCUMENT_ROOT.'/html.formmail.class.php');
				$formmail = new FormMail($db);
				$formmail->fromname = $user->fullname;
				$formmail->frommail = $user->email;
				$formmail->withfrom=1;
				$formmail->withto=$liste;
				$formmail->withcc=1;
				$formmail->withtopic=$langs->trans('SendBillRef','__FACREF__');
				$formmail->withfile=1;
				$formmail->withbody=1;
				// Tableau des substitutions
				$formmail->substit['__FACREF__']=$fac->ref;
				// Tableau des paramètres complémentaires du post
				$formmail->param['action']='send';
				$formmail->param['models']='facture_send';
				$formmail->param['facid']=$fac->id;
				$formmail->param['returnurl']=DOL_URL_ROOT.'/compta/facture.php?facid='.$fac->id;

				$formmail->show_form();

				print '<br>';
			}

			if ($_GET['action'] == 'prerelance')
			{
				print '<br>';
				print_titre($langs->trans('SendReminderBillByMail'));

				$liste[0]='&nbsp;';
				foreach ($soc->contact_email_array() as $key=>$value)
				{
					$liste[$key]=$value;
				}

				// Créé l'objet formulaire mail
				include_once(DOL_DOCUMENT_ROOT.'/html.formmail.class.php');
				$formmail = new FormMail($db);
				$formmail->fromname = $user->fullname;
				$formmail->frommail = $user->email;
				$formmail->withfrom=1;
				$formmail->withto=$liste;
				$formmail->withcc=1;
				$formmail->withtopic=$langs->trans('SendReminderBillRef','__FACREF__');
				$formmail->withfile=1;
				$formmail->withbody=1;
				// Tableau des substitutions
				$formmail->substit['__FACREF__']=$fac->ref;
				// Tableau des paramètres complémentaires
				$formmail->param['action']='relance';
				$formmail->param['models']='facture_relance';
				$formmail->param['facid']=$fac->id;
				$formmail->param['returnurl']=DOL_URL_ROOT.'/compta/facture.php?facid='.$fac->id;

				$formmail->show_form();

				print '<br>';
			}

		}
		else
		{
			dolibarr_print_error($db,$fac->error);
		}
	}
	else
	{
		/***************************************************************************
		 *                                                                         *
		 *                      Mode Liste                                         *
		 *                                                                         *
		 ***************************************************************************/
		$page = $_GET['page'];
		$sortorder=$_GET['sortorder'];
		$sortfield=$_GET['sortfield'];
		$month=$_GET['month'];
		$year=$_GET['year'];

		$fac=new Facture($db);

		if ($page == -1) $page = 0 ;

		if ($user->rights->facture->lire)
		{
			$limit = $conf->liste_limit;
			$offset = $limit * $page ;

			if (! $sortorder) $sortorder='DESC';
			if (! $sortfield) $sortfield='f.datef';

			$sql = 'SELECT s.nom,s.idp,f.facnumber,f.increment,f.total,f.total_ttc,';
			$sql.= $db->pdate('f.datef').' as df, '.$db->pdate('f.date_lim_reglement').' as datelimite, ';
			$sql.= ' f.paye as paye, f.rowid as facid, f.fk_statut';
			if (! $sall) $sql.= ' ,sum(pf.amount) as am';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
			$sql.= ','.MAIN_DB_PREFIX.'facture as f';
			if (! $sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON f.rowid=pf.fk_facture ';
			if ($sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as fd ON f.rowid=fd.fk_facture ';
			$sql.= ' WHERE f.fk_soc = s.idp';
			if ($socidp) $sql .= ' AND s.idp = '.$socidp;
			if ($month > 0) $sql .= ' AND date_format(f.datef, \'%m\') = '.$month;
			if ($_GET['filtre'])
			{
				$filtrearr = split(',', $_GET['filtre']);
				foreach ($filtrearr as $fil)
				{
					$filt = split(':', $fil);
					$sql .= ' AND ' . $filt[0] . ' = ' . $filt[1];
				}
			}
			if ($_GET['search_ref'])
			{
				$sql .= ' AND f.facnumber like \'%'.$_GET['search_ref'].'%\'';
			}
			if ($_GET['search_societe'])
			{
				$sql .= ' AND s.nom like \'%'.$_GET['search_societe'].'%\'';
			}
			if ($_GET['search_montant_ht'])
			{
				$sql .= ' AND f.total = \''.$_GET['search_montant_ht'].'\'';
			}
			if ($_GET['search_montant_ttc'])
			{
				$sql .= ' AND f.total_ttc = \''.$_GET['search_montant_ttc'].'\'';
			}
			if ($year > 0)
			{
				$sql .= ' AND date_format(f.datef, \'%Y\') = '.$year;
			}
			if ($_POST['sf_ref'])
			{
				$sql .= ' AND f.facnumber like \'%'.$_POST['sf_ref'] . '%\'';
			}
			if ($sall)
			{
				$sql .= ' AND (s.nom like \'%'.$sall.'%\' OR f.facnumber like \'%'.$sall.'%\' OR f.note like \'%'.$sall.'%\' OR fd.description like \'%'.$sall.'%\')';
			}

			$sql .= ' GROUP BY f.facnumber';

			$sql .= ' ORDER BY ';
			$listfield=split(',',$sortfield);
			foreach ($listfield as $key => $value)
				$sql.= $listfield[$key].' '.$sortorder.',';
			$sql .= ' f.rowid DESC ';

			$sql .= $db->plimit($limit+1,$offset);

			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);

				if ($socidp)
				{
					$soc = new Societe($db);
					$soc->fetch($socidp);
				}

				print_barre_liste($langs->trans('BillsCustomers').' '.($socidp?' '.$soc->nom:''),$page,'facture.php','&amp;socidp='.$socidp,$sortfield,$sortorder,'',$num);

				$i = 0;
				print '<table class="liste" width="100%">';
				print '<tr class="liste_titre">';
				print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'f.facnumber','','&amp;socidp='.$socidp,'',$sortfield);
				print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'f.datef','','&amp;socidp='.$socidp,'align="center"',$sortfield);
				print_liste_field_titre($langs->trans('Company'),$_SERVER['PHP_SELF'],'s.nom','','&amp;socidp='.$socidp,'',$sortfield);
				print_liste_field_titre($langs->trans('AmountHT'),$_SERVER['PHP_SELF'],'f.total','','&amp;socidp='.$socidp,'align="right"',$sortfield);
				print_liste_field_titre($langs->trans('AmountTTC'),$_SERVER['PHP_SELF'],'f.total_ttc','','&amp;socidp='.$socidp,'align="right"',$sortfield);
				print_liste_field_titre($langs->trans('Received'),$_SERVER['PHP_SELF'],'am','','&amp;socidp='.$socidp,'align="right"',$sortfield);
				print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'fk_statut,paye','','&amp;socidp='.$socidp,'align="right"',$sortfield);
				print '</tr>';

				// Lignes des champs de filtre
				print '<form method="get" action="facture.php">';
				print '<tr class="liste_titre">';
				print '<td class="liste_titre" valign="right">';
				print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_GET['search_ref'].'">';
				print '</td><td class="liste_titre">&nbsp;</td>';
				print '<td class="liste_titre" align="left">';
				print '<input class="flat" type="text" name="search_societe" value="'.$_GET['search_societe'].'">';
				print '</td><td class="liste_titre" align="right">';
				print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$_GET['search_montant_ht'].'">';
				print '</td><td class="liste_titre" align="right">';
				print '<input class="flat" type="text" size="10" name="search_montant_ttc" value="'.$_GET['search_montant_ttc'].'">';
				print '</td><td class="liste_titre" colspan="2" align="right">';
				print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans('Search').'">';
				print '</td>';
				print '</tr>';
				print '</form>';

				if ($num > 0)
				{
					$var=True;
					$total=0;
					$totalrecu=0;

					while ($i < min($num,$limit))
					{
						$objp = $db->fetch_object($resql);
						$var=!$var;

						print '<tr '.$bc[$var].'>';
						if ($objp->paye)
						{
							$class = 'normal';
						}
						else
						{
							if ($objp->fk_statut == 0)
							{
								$class = 'normal';
							}
							else
							{
								$class = 'impayee';
							}
						}

						print '<td><a href="facture.php?facid='.$objp->facid.'">'.img_object($langs->trans('ShowBill'),'bill').'</a> ';
						print '<a href="facture.php?facid='.$objp->facid.'">'.$objp->facnumber.'</a>'.$objp->increment;
						if ($objp->datelimite < (time() - $conf->facture->client->warning_delay) && ! $objp->paye && $objp->fk_statut == 1 && ! $objp->am) print img_warning($langs->trans('Late'));
						print '</td>';

						if ($objp->df > 0 )
						{
							print '<td align="center" nowrap>';
							$y = strftime('%Y',$objp->df);
							$m = strftime('%m',$objp->df);
							print strftime('%d',$objp->df);
							print ' <a href="facture.php?year='.$y.'&amp;month='.$m.'">';
							print substr(strftime('%B',$objp->df),0,3).'</a>';
							print ' <a href="facture.php?year='.$y.'">';
							print strftime('%Y',$objp->df).'</a></td>';
						}
						else
						{
							print '<td align="center"><b>!!!</b></td>';
						}
						print '<td><a href="fiche.php?socid='.$objp->idp.'">'.img_object($langs->trans('ShowCompany'),'company').' '.$objp->nom.'</a></td>';
						print '<td align="right">'.price($objp->total).'</td>';
						print '<td align="right">'.price($objp->total_ttc).'</td>';
						print '<td align="right">'.price($objp->am).'</td>';

						// Affiche statut de la facture
						if (! $objp->paye)
						{
							if ($objp->fk_statut == 0)
							{
								print '<td align="center">'.$langs->trans('BillShortStatusDraft').'</td>';
							}
							elseif ($objp->fk_statut == 3)
							{
								print '<td align="center">'.$langs->trans('BillShortStatusCanceled').'</td>';
							}
							else
							{
								print '<td align="center"><a class="'.$class.'" href="facture.php?filtre=paye:0,fk_statut:1">'.($objp->am?$langs->trans('BillShortStatusStarted'):$langs->trans('BillShortStatusNotPayed')).'</a></td>';
							}
						}
						else
						{
							print '<td align="center">'.$langs->trans('BillShortStatusPayed').'</td>';
						}

						print '</tr>';
						$total+=$objp->total;
						$total_ttc+=$objp->total_ttc;
						$totalrecu+=$objp->am;
						$i++;
					}

					if (($offset + $num) <= $limit)
					{
						// Print total
						print '<tr class="liste_total">';
						print '<td class="liste_total" colspan="3" align="left">'.$langs->trans('Total').'</td>';
						print '<td class="liste_total" align="right">'.price($total).'</td>';
						print '<td class="liste_total" align="right">'.price($total_ttc).'</td>';
						print '<td class="liste_total" align="right">'.price($totalrecu).'</td>';
						print '<td class="liste_total" align="center">&nbsp;</td>';
						print '</tr>';
					}
				}

				print '</table>';
				$db->free($resql);
			}
			else
			{
				dolibarr_print_error($db);
			}
		}
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
