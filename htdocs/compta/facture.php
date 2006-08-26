<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2006 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2006 Regis Houssin         <regis.houssin@cap-networks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
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
require_once(DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");
require_once(DOL_DOCUMENT_ROOT.'/facture.class.php');
require_once(DOL_DOCUMENT_ROOT.'/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT.'/lib/invoice.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/lib/CMailFile.class.php');
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/project.class.php');
if ($conf->propal->enabled)   require_once(DOL_DOCUMENT_ROOT.'/propal.class.php');
if ($conf->contrat->enabled)  require_once(DOL_DOCUMENT_ROOT.'/contrat/contrat.class.php');
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT.'/commande/commande.class.php');

$user->getrights('facture');
$user->getrights('banque');

if (! $user->rights->facture->lire)
    accessforbidden();

$langs->load('bills');
$langs->load('companies');
$langs->load('products');
$langs->load('main');

$sall=isset($_GET['sall'])?trim($_GET['sall']):trim($_POST['sall']);
$msg=isset($_GET['msg'])?urldecode($_GET['msg']):'';
$socidp=isset($_GET['socidp'])?$_GET['socidp']:$_POST['socidp'];

// Sécurité accés client
if ($user->societe_id > 0)
{
	$action = '';
	$socidp = $user->societe_id;
}

// Récupération de l'id de projet
$projetid = 0;
if ($_GET["projetid"])
{
	$projetid = $_GET["projetid"];
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
	if ($result < 0) dolibarr_print_error($facture->db,$facture->error);
}

if ($_POST['action'] == 'setconditions')
{
	$facture = new Facture($db);
	$facture->fetch($_GET['facid']);
	$result=$facture->cond_reglement($_POST['cond_reglement_id']);
	if ($result < 0) dolibarr_print_error($facture->db,$facture->error);
}

if ($_REQUEST['action'] == 'setremisepercent' && $user->rights->facture->creer)
{
	$fac = new Facture($db);
	$fac->fetch($_REQUEST['facid']);
	$result = $fac->set_remise($user, $_POST['remise_percent']);
	$_GET['facid']=$_REQUEST['facid'];
}

if ($_POST['action'] == "setabsolutediscount" && $user->rights->propale->creer)
{
	if ($_POST["remise_id"])
	{
	    $fac = new Facture($db);
	    $fac->id=$_GET['facid'];
	    $ret=$fac->fetch($_GET['facid']);
		if ($ret > 0)
		{
			$result=$fac->insert_discount($_POST["remise_id"]);
			if ($result < 0)
			{
				$mesg='<div class="error">'.$fac->error.'</div>';
			}
		}
		else
		{
			dolibarr_print_error($db,$fac->error);
		}
	}
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

// Classe à "validée"
if ($_POST['action'] == 'confirm_valid' && $_POST['confirm'] == 'yes' && $user->rights->facture->valider)
{
	$fac = new Facture($db);
	$fac->fetch($_GET['facid']);
	$soc = new Societe($db);
	$soc->fetch($fac->socidp);
	$result = $fac->set_valid($fac->id, $user, $soc);
	if ($result >= 0)
	{
		if ($_REQUEST['lang_id'])
		{
			$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs");
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
	}
	else
	{
		$msg='<div class="error">'.$fac->error.'</div>';
	}
}

// Repasse la facture en mode brouillon
if ($_GET['action'] == 'modif' && $user->rights->facture->modifier && $conf->global->FACTURE_ENABLE_EDITDELETE)
{
	$fac = new Facture($db);
  $fac->fetch($_GET['facid']);
  
  // On vérifie si la facture a des paiements
  $sql = 'SELECT pf.amount';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf';
	$sql.= ' WHERE pf.fk_facture = '.$fac->id;

	$result = $db->query($sql);
	
	if ($result)
	{
		$i = 0;
		$num = $db->num_rows($result);
		
		while ($i < $num)
		{
			$objp = $db->fetch_object($result);
			$totalpaye += $objp->amount;
		  $i++;
		}
	}
			
  $resteapayer = $fac->total_ttc - $totalpaye;
  
	// On vérifie si les lignes de factures ont été exportées en compta et/ou ventilées
	$ventilExportCompta = $fac->getVentilExportCompta();
	
	// On vérifie si aucun paiement n'a été effectué
	if ($resteapayer == $fac->total_ttc	&& $fac->paye == 0 && $ventilExportCompta == 0)
	{
		$fac->reopen($user);
  }
}

if ($_POST['action'] == 'confirm_deleteproductline' && $_POST['confirm'] == 'yes' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
{
    if ($user->rights->facture->creer)
    {
    	$fac = new Facture($db);
    	$fac->fetch($_GET['facid']);
    	$fac->deleteline($_GET['rowid']);
    	if ($_REQUEST['lang_id'])
    	{
    		$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs");
    		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
    	}
    	facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
    }
    Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$_GET['facid']);
    exit;
}

// Classe à "payée"
if ($_POST['action'] == 'confirm_payed' && $_POST['confirm'] == 'yes' && $user->rights->facture->paiement)
{
	$fac = new Facture($db);
	$fac->fetch($_GET['facid']);
	$result = $fac->set_payed($user);
}
if ($_POST['action'] == 'confirm_payed_partially' && $_POST['confirm'] == 'yes' && $user->rights->facture->paiement)
{
	$fac = new Facture($db);
	$fac->fetch($_GET['facid']);
	$close_code=$_POST["close_code"];
	$close_note=$_POST["close_note"];
	if ($close_code)
	{
/*
		if ($close_code == 'other' && ! $close_note)
		{
			$msg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Comment")).'</div>';
		}
		else
		{
*/
			if ($close_code == 'abandon')
			{
				$result = $fac->set_canceled($user,$close_code,$close_note);
			}
			else
			{
				$result = $fac->set_payed($user,$close_code,$close_note);
			}
//		}
	}
	else
	{
		$msg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Reason")).'</div>';
	}
}

/*
 * Insertion facture
 */
if ($_POST['action'] == 'add')
{
	$datefacture = mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);

	$facture = new Facture($db, $_POST['socid']);

	$facture->type           = $_POST['type'];
	if ($facture->type == 1) $facture->fk_facture_source = $_POST['replacement_ref'];
	$facture->number         = $_POST['facnumber'];
	$facture->date           = $datefacture;
	$facture->note_public    = trim($_POST['note_public']);
	$facture->note           = trim($_POST['note']);
	$facture->ref_client     = $_POST['ref_client'];
	$facture->modelpdf       = $_POST['model'];

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
		$facture->remise_absolue    = $_POST['remise_absolue'];
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

			if ($facid > 0)
			{
				Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$facid);
				exit;
			}
			else
			{
				$_GET["action"]='create';
				$msg='<div class="error">'.$facture->error.'</div>';
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
				if ($facid > 0)
				{
					$prop = New Propal($db);
					if ( $prop->fetch($_POST['propalid']) )
					{
						for ($i = 0 ; $i < sizeof($prop->lignes) ; $i++)
						{
							$desc=($prop->lignes[$i]->desc?$prop->lignes[$i]->desc:$prop->lignes[$i]->libelle);

							$result = $facture->addline(
								$facid,
								$desc,
								$prop->lignes[$i]->subprice,
								$prop->lignes[$i]->qty,
								$prop->lignes[$i]->tva_tx,
								$prop->lignes[$i]->fk_product,
								$prop->lignes[$i]->remise_percent,
								'',
								'',
								0,
								$prop->lignes[$i]->info_bits,
								$prop->lignes[$i]->fk_remise_except);
						}
					}
					else
					{
						print $langs->trans('UnknownError');
					}
				}
				else
				{
					dolibarr_print_error($facture->db,$facture->error);
				}
			}

			/*
			 * Si création depuis commande
			 */
			if ($_POST['commandeid'])
			{
				$facture->commandeid = $_POST['commandeid'];
				$facid = $facture->create($user);
				if ($facid > 0)
				{
					$comm = New Commande($db);
					if ( $comm->fetch($_POST['commandeid']) )
					{
						$lines = $comm->fetch_lignes();
						for ($i = 0 ; $i < sizeof($lines) ; $i++)
						{
							$desc=($lines[$i]->desc ? $lines[$i]->desc : $lines[$i]->libelle);

							$result = $facture->addline(
								$facid,
								$desc,
								$lines[$i]->subprice,
								$lines[$i]->qty,
								$lines[$i]->tva_tx,
								$lines[$i]->fk_product,
								$lines[$i]->remise_percent,
								'',
								'',
								0,
								$lines[$i]->info_bits,
								$lines[$i]->fk_remise_except);
						}
					}
					else
					{
						print $langs->trans('UnknownError');
					}
				}
				else
				{
					dolibarr_print_error($facture->db,$facture->error);
				}
			}

			/*
			 * Si création depuis contrat
			 */
			if ($_POST['contratid'])
			{
				$facture->contratid = $_POST['contratid'];
				$facid = $facture->create($user);
				if ($facid > 0)
				{
					$contrat = New Contrat($db);
					if ($contrat->fetch($_POST['contratid']) > 0)
					{
						$lines = $contrat->fetch_lignes();

						for ($i = 0 ; $i < sizeof($lines) ; $i++)
						{
							$desc=($contrat->lignes[$i]->desc?$contrat->lignes[$i]->desc:$contrat->lignes[$i]->libelle);

							// Plage de dates
							$date_start=$contrat->lignes[$i]->date_debut_prevue;
							if ($contrat->lignes[$i]->date_debut_reel) $date_start=$contrat->lignes[$i]->date_debut_reel;
							$date_end=$contrat->lignes[$i]->date_fin_prevue;
							if ($contrat->lignes[$i]->date_fin_reel) $date_end=$contrat->lignes[$i]->date_fin_reel;

							$result = $facture->addline(
								$facid,
								$desc,
								$lines[$i]->subprice,
								$lines[$i]->qty,
								$lines[$i]->tva_tx,
								$lines[$i]->fk_product,
								$lines[$i]->remise_percent,
								$date_start,
								$date_end,
								0,
								$lines[$i]->info_bits,
								$lines[$i]->fk_remise_except);
						}
					}
					else
					{
						print $langs->trans('UnknownError');
					}
				}
				else
				{
					dolibarr_print_error($facture->db,$facture->error);
				}
			}

			// Fin création facture, on l'affiche
			if ($facid > 0)
			{
				Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$facid);
				exit;
			}
		}
	}
}

/*
 *  Ajout d'une ligne produit dans la facture
 */
if (($_POST['action'] == 'addligne' || $_POST['action'] == 'addligne_predef') && $user->rights->facture->creer)
{
	if ($_POST['qty'] && (($_POST['pu']!=0 && $_POST['desc']) || $_POST['idprod']))
	{
		$fac = new Facture($db);
		$ret=$fac->fetch($_POST['facid']);
		$soc = new Societe($db);
		$ret=$soc->fetch($fac->socidp);
		
		$date_start='';
		$date_end='';
		// Si ajout champ produit libre
		if ($_POST['action'] == 'addligne')
		{
			if ($_POST['date_startyear'] && $_POST['date_startmonth'] && $_POST['date_startday'])
			{
				$date_start=$_POST['date_startyear'].'-'.$_POST['date_startmonth'].'-'.$_POST['date_startday'];
			}
			if ($_POST['date_endyear'] && $_POST['date_endmonth'] && $_POST['date_endday'])
			{
				$date_end=$_POST['date_endyear'].'-'.$_POST['date_endmonth'].'-'.$_POST['date_endday'];
			}
		}
		// Si ajout champ produit prédéfini
		if ($_POST['action'] == 'addligne_predef')
		{
			if ($_POST['date_start_predefyear'] && $_POST['date_start_predefmonth'] && $_POST['date_start_predefday'])
			{
				$date_start=$_POST['date_start_predefyear'].'-'.$_POST['date_start_predefmonth'].'-'.$_POST['date_start_predefday'];
			}
			if ($_POST['date_end_predefyear'] && $_POST['date_end_predefmonth'] && $_POST['date_end_predefday'])
			{
				$date_end=$_POST['date_end_predefyear'].'-'.$_POST['date_end_predefmonth'].'-'.$_POST['date_end_predefday'];
			}
		}

		// Ecrase $pu par celui du produit
		// Ecrase $desc par celui du produit
		// Ecrase $txtva par celui du produit
        if ($_POST['idprod'])
        {
            $prod = new Product($db, $_POST['idprod']);
            $prod->fetch($_POST['idprod']);
            
            // multiprix
            if ($conf->global->PRODUIT_MULTIPRICES == 1)
            {
            	$pu = $prod->multiprices[$soc->price_level];
            }
            else
            {
            	$pu=$prod->price;
            }
            
            // La description de la ligne est celle saisie ou
            // celle du produit si (non saisi + PRODUIT_CHANGE_PROD_DESC défini)
            $desc=$_POST['desc'];
            if (! $desc && $conf->global->PRODUIT_CHANGE_PROD_DESC)
            {
            	$desc = $prod->description;
            }
            
            $tva_tx = get_default_tva($mysoc,$soc,$prod->tva_tx);
        }
        else
        {
        	$pu=$_POST['pu'];
        	$tva_tx=$_POST['tva_tx'];
        	$desc=$_POST['desc'];
        }

		// Insere ligne
		$result = $fac->addline(
			$_POST['facid'],
			$desc,
			$pu,
			$_POST['qty'],
			$tva_tx,
			$_POST['idprod'],
			$_POST['remise_percent'],
			$date_start,
			$date_end
			);
	}

	$_GET['facid']=$_POST['facid'];   // Pour réaffichage de la fiche en cours d'édition
}

if ($_POST['action'] == 'updateligne' && $user->rights->facture->creer && $_POST['save'] == $langs->trans('Save'))
{
	$fac = new Facture($db,'',$_POST['facid']);
	if (! $fac->fetch($_POST['facid']) > 0) dolibarr_print_error($db);

	$date_start='';
	$date_end='';
	if ($_POST['date_startyear'] && $_POST['date_startmonth'] && $_POST['date_startday']) {
		$date_start=$_POST['date_startyear'].'-'.$_POST['date_startmonth'].'-'.$_POST['date_startday'];
	}
	if ($_POST['date_endyear'] && $_POST['date_endmonth'] && $_POST['date_endday']) {
		$date_end=$_POST['date_endyear'].'-'.$_POST['date_endmonth'].'-'.$_POST['date_endday'];
	}

	$result = $fac->updateline($_POST['rowid'],
		$_POST['desc'],
		$_POST['price'],
		$_POST['qty'],
		$_POST['remise_percent'],
		$date_start,
		$date_end,
		$_POST['tva_tx']
		);

	if ($_REQUEST['lang_id'])
	{
		$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs");
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
    facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);

	$_GET['facid']=$_POST['facid'];   // Pour réaffichage de la fiche en cours d'édition
}

if ($_POST['action'] == 'updateligne' && $user->rights->facture->creer && $_POST['cancel'] == $langs->trans('Cancel'))
{
	Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$_POST['facid']);   // Pour réaffichage de la fiche en cours d'édition
	exit;
}

if ($_GET['action'] == 'deleteline' && $user->rights->facture->creer && ! $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
{
	$fac = new Facture($db,'',$_GET['facid']);
	$fac->fetch($_GET['facid']);
	$result = $fac->deleteline($_GET['rowid']);
	if ($result > 0)
	{
		if ($_REQUEST['lang_id'])
		{
			$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs");
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
	//	facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
	}
	else
	{
		print $fac->error;	
	}
}

if ($_POST['action'] == 'confirm_delete' && $_POST['confirm'] == 'yes')
{
	if ($user->rights->facture->supprimer)
	{
		$fac = new Facture($db);
		$result = $fac->delete($_GET['facid']);
		if ($result > 0)
		{
			Header('Location: '.$_SERVER["PHP_SELF"]);
			exit;
		}
		else
		{
			$mesg='<div class="error">'.$fac->error.'</div>';
		}	
	}
}

if ($_POST['action'] == 'confirm_canceled' && $_POST['confirm'] == 'yes')
{
	if ($user->rights->facture->supprimer)
	{
		$fac = new Facture($db);
		$fac->fetch($_GET['facid']);
		$result = $fac->set_canceled($user);
	}
}

/*
 * Ordonnancement des lignes
 */

if ($_GET['action'] == 'up' && $user->rights->facture->creer)
{
	$fac = new Facture($db,'',$_GET['facid']);
	$fac->fetch($_GET['facid']);
	$fac->line_up($_GET['rowid']);
	if ($_REQUEST['lang_id'])
	{
		$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs");
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
  facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
	Header ('Location: '.$_SERVER["PHP_SELF"].'?facid='.$_GET["facid"].'#'.$_GET['rowid']);
}

if ($_GET['action'] == 'down' && $user->rights->facture->creer)
{
	$fac = new Facture($db,'',$_GET['facid']);
	$fac->fetch($_GET['facid']);
	$fac->line_down($_GET['rowid']);
	if ($_REQUEST['lang_id'])
	{
		$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs");
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
  facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
	Header ('Location: '.$_SERVER["PHP_SELF"].'?facid='.$_GET["facid"].'#'.$_GET['rowid']);
}

/*
 * Action envoi de mail
 */
if (($_POST['action'] == 'send' || $_POST['action'] == 'relance') && ! $_POST['cancel'])
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
				$sendtocc = $_POST['sendtocc'];
				$deliveryreceipt = $_POST['deliveryreceipt'];
				
				if ($_POST['action'] == 'send')
				{
					if(strlen($_POST['subject']))
					{
						$subject = $_POST['subject'];
					}
					else
					{
						$subject = $langs->trans('Bill').' '.$fac->ref;
					}
					
					$actiontypeid=9;
					$actionmsg ='Mail envoyé par '.$from.' à '.$sendto.'.<br>';
					
					if ($message)
					{
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
				$mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt);
				if ($mailfile->error)
				{
					$msg='<div class="error">'.$mailfile->error.'</div>';
				}
				else
				{
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
							Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&msg='.urlencode($msg));
							exit;
						}
					}
					else
					{
						$langs->load("other");
						$msg='<div class="error">';
						$msg.=$langs->trans('ErrorFailedToSendMail',$from,$sendto);
						if ($mailfile->error) $msg.='<br>'.$mailfile->error;
						$msg.='</div>';
					}
				}
			}
			else
			{
				$langs->load("other");
				$msg='<div class="error">'.$langs->trans('ErrorMailRecipientIsEmpty').'</div>';
				dolibarr_syslog('Recipient email is empty');
			}

		}
		else
		{
			$langs->load("other");
			$msg='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
			dolibarr_syslog('Failed to read file: '.$file);
		}
	}
	else
	{
		$langs->load("other");
		$msg='<div class="error">'.$langs->trans('ErrorFailedToReadEntity',$langs->trans("Invoice")).'</div>';
		dolibarr_syslog('Impossible de lire les données de la facture. Le fichier facture n\'a peut-être pas été généré.');
	}
}

/*
 * Générer ou regénérer le document PDF
 */
if ($_REQUEST['action'] == 'builddoc')	// En get ou en post
{
	$fac = new Facture($db, 0, $_GET['facid']);
	$fac->fetch($_GET['facid']);
	
	if ($_REQUEST['model'])
	{
		$fac->set_pdf_model($user, $_REQUEST['model']);
	}
	
	if ($_REQUEST['lang_id'])
	{
		$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs");
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	
	$result=facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
    if ($result <= 0)
    {
    	dolibarr_print_error($db,$result);
      exit;
    }
    else
    {
    	Header ('Location: '.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'#builddoc');
    }    
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
	$facturestatic=new Facture($db);
	
	print_titre($langs->trans('NewBill'));

	if ($msg) print $msg;
	
	$soc = new Societe($db);

	if ($_GET['propalid'])
	{
		$propal = New Propal($db);
		$propal->fetch($_GET['propalid']);
		$societe_id = $propal->socidp;
		$projetid=$propal->projetidp;
		$ref_client=$propal->ref_client;

		$soc->fetch($societe_id);
		$cond_reglement_id = $propal->cond_reglement_id;
		$mode_reglement_id = $propal->mode_reglement_id;
		$remise_percent = $propal->remise_percent;
		$remise_absolue = $propal->remise_absolue;
	}
	elseif ($_GET['commandeid'])
	{
		$commande = New Commande($db);
		$commande->fetch($_GET['commandeid']);
		$societe_id = $commande->socidp;
		$projetid=$commande-> projet_id;
		$ref_client=$commande->ref_client;

		$soc->fetch($societe_id);
		$cond_reglement_id = $commande->cond_reglement_id;
		$mode_reglement_id = $commande->mode_reglement_id;
		$remise_percent = $commande->remise_percent;
		$remise_absolue = $commande->remise_absolue;
	}
	elseif ($_GET['contratid'])
	{
		$contrat = New Contrat($db);
		$contrat->fetch($_GET['contratid']);
		$societe_id = $contrat->societe->id;
		$projetid=$contrat->fk_projet;

		$soc=$contrat->societe;
		$cond_reglement_id = $soc->cond_reglement;
		$mode_reglement_id = $soc->mod_reglement;
		$remise_percent = $soc->remise_client;
		$remise_absolue = 0;
	}
	else
	{
		$societe_id=$socidp;

		$soc->fetch($societe_id);
		$cond_reglement_id = $soc->cond_reglement;
		$mode_reglement_id = $soc->mode_reglement;
		$remise_percent = $soc->remise_client;
		$remise_absolue = 0;
	}
	$absolute_discount=$soc->getCurrentDiscount();


	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="socid" value="'.$soc->id.'">' ."\n";
	print '<input name="facnumber" type="hidden" value="provisoire">';

	print '<table class="border" width="100%">';

    // Ref
	print '<tr><td>'.$langs->trans('Ref').'</td><td colspan="2">'.$langs->trans('Draft').'</td></tr>';
	
    // Type de facture
	print '<tr><td valign="top">'.$langs->trans('Type').'</td><td colspan="2">';
	print '<table class="nobordernopadding"><tr>';
	print '<td width="16px">';
	print '<input type="radio" name="type" value="0"'.($_POST['type']==0?' checked=true':'').'>';
	print '</td><td>';
	$desc=$html->textwithhelp($langs->trans("InvoiceStandardAsk"),$langs->transnoentities("InvoiceStandardDesc"),1);
	print $desc;
	print '</td></tr>';
	print '<tr><td>';
	print '<input type="radio" name="type" value="1"'.($_POST['type']==1?' checked=true':'').'>';
	print '</td><td>';
	$facids=$facturestatic->list_replacable_invoices($soc->id);
	$options="";
	foreach ($facids as $key => $value)
	{
		$options.='<option value="'.$key.'">'.$value.'</option>';
	}
	$text=$langs->trans("InvoiceReplacementAsk").' ';
	$text.='<select name="replacement_ref">';
	$text.='<option value="-1">&nbsp;</option>';
	$text.=$options;
	$text.='</select>';
	$desc=$html->textwithhelp($text,$langs->transnoentities("InvoiceReplacementDesc"),1);
	print $desc;	
	print '</td></tr>';
	print '<tr><td>';
	print '<input type="radio" name="type" value="2"'.($_POST['type']==2?' checked=true':'').' disabled>';
	print '</td><td>';
	$desc=$html->textwithhelp($langs->trans("InvoiceAvoirAsk").' ('.$langs->trans("FeatureNotYetAvailable").')',$langs->transnoentities("InvoiceAvoirDesc"),1);
	print $desc;	
	print '</td></tr>';
	print '</table>';
	print '</td></tr>';
    
	// Societe
	print '<tr><td>'.$langs->trans('Company').'</td><td colspan="2">';
	print $soc->getNomUrl(1);
	print '<input type="hidden" name="socidp" value="'.$soc->id.'">';
	print '</td>';
	print '</tr>';

	// Ligne info remises tiers
    print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="2">';
	if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
	else print $langs->trans("CompanyHasNoRelativeDiscount");
	print '. ';
	if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->monnaie));
	else print $langs->trans("CompanyHasNoAbsoluteDiscount");
	print '.';
	print '</td></tr>';

	// Date facture
	print '<tr><td>'.$langs->trans('Date').'</td><td colspan="2">';
	$html->select_date('','','','','',"add");
	print '</td></tr>';

	// Conditions de réglement
	print '<tr><td nowrap>'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
	$html->select_conditions_paiements($cond_reglement_id,'cond_reglement_id');
	print '</td></tr>';

	// Mode de réglement
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
	$html->select_types_paiements($mode_reglement_id,'mode_reglement_id');
	print '</td></tr>';

    // Réductions relatives (Remises-Ristournes-Rabbais)
/* Une réduction doit s'appliquer obligatoirement sur des lignes de factures
   et non globalement
	print '<tr><td>'.$langs->trans("CustomerRelativeDiscount").'</td>';
	print '<td>';
	if (! $_GET['propalid'] && ! $_GET['commandeid'] && ! $_GET['contratid']) print '<input type="text" name="remise_percent" size="1" value="';
	print $remise_percent;
	if (! $_GET['propalid'] && ! $_GET['commandeid'] && ! $_GET['contratid']) print '">';
	print ' %';
	print '</td><td>'.img_info().' ';
	$relative_discount=$soc->remise_client;
	if ($relative_discount)
	{
		print $langs->trans("CompanyHasRelativeDiscount",$relative_discount);
	}	
	else
	{
		print $langs->trans("CompanyHasNoRelativeDiscount");
	}
	print '</td></tr>';
*/

    // Réductions absolues (Remises-Ristournes-Rabbais)
/* Les remises absolues doivent s'appliquer par ajout de lignes spécialisées
	print '<tr><td>'.$langs->trans("CustomerAbsoluteDiscount").'</td>';
	print '<td>';
	if (! $_GET['propalid'] && ! $_GET['commandeid'] && ! $_GET['contratid']) print '<input type="text" name="remise_absolue" size="1" value="';
	print $remise_absolue;
	if (! $_GET['propalid'] && ! $_GET['commandeid'] && ! $_GET['contratid']) print '">';
	print ' '.$langs->trans("Currency".$conf->monnaie);
	print '</td><td>'.img_info().' ';
	if ($absolute_discount)
	{
		print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->monnaie));
	}	
	else
	{
		print $langs->trans("CompanyHasNoAbsoluteDiscount");
	}
	print '</td></tr>';
*/
	
	// Projet
	if ($conf->projet->enabled)
	{
		$langs->load('projects');
		print '<tr><td>'.$langs->trans('Project').'</td><td colspan="2">';
		$html->select_projects($societe_id, $projetid, 'projetid');
		print '</td></tr>';
	}
	
	// Modele PDF
	print '<tr><td>'.$langs->trans('Model').'</td>';
	print '<td>';
	include_once(DOL_DOCUMENT_ROOT.'/includes/modules/facture/modules_facture.php');
	$model=new ModelePDFFactures();
	$liste=$model->liste_modeles($db);
	$html->select_array('model',$liste,$conf->global->FACTURE_ADDON_PDF);
	print "</td></tr>";

    /*
      \todo
      L'info "Reference commande client" est une carac de la commande et non de la facture.
      Elle devrait donc etre stockée sur l'objet commande lié à la facture et non sur la facture.
      Pour ceux qui veulent l'utiliser au niveau de la facture, positionner la
      constante FAC_USE_CUSTOMER_ORDER_REF à 1.
    */
    if ($conf->global->FAC_USE_CUSTOMER_ORDER_REF)
    {
    	print '<tr><td>'.$langs->trans('RefCustomerOrder').'</td><td>';
    	print '<input type="text" name="ref_client" value="'.$ref_client.'">';
    	print '</td></tr>';
    }
    
	// Note publique
	print '<tr>';
	print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
	print '<td valign="top" colspan="2">';
	print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'">';
	if (is_object($propal))
	{
		print $propal->note_public;
	}
	if (is_object($commande))
	{
		print $commande->note_public;
	}
	if (is_object($contrat))
	{
		print $contrat->note_public;
	}
	print '</textarea></td></tr>';

	// Note privée
	if (! $user->societe_id)
	{
		print '<tr>';
		print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
		print '<td valign="top" colspan="2">';
		print '<textarea name="note" wrap="soft" cols="70" rows="'.ROWS_3.'">';
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
	}
	
	if ($_GET['propalid'] > 0)
	{
		print '<input type="hidden" name="amount"         value="'.$propal->price.'">'."\n";
		print '<input type="hidden" name="total"          value="'.$propal->total.'">'."\n";
		print '<input type="hidden" name="tva"            value="'.$propal->tva.'">'."\n";
//		print '<input type="hidden" name="remise_absolue" value="'.$propal->remise_absolue.'">'."\n";
//		print '<input type="hidden" name="remise_percent" value="'.$propal->remise_percent.'">'."\n";
		print '<input type="hidden" name="propalid"       value="'.$propal->id.'">';

		print '<tr><td>'.$langs->trans('Proposal').'</td><td colspan="2"><a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id.'">'.img_object($langs->trans("ShowPropal"),'propal').' '.$propal->ref.'</a></td></tr>';
		print '<tr><td>'.$langs->trans('TotalHT').'</td><td colspan="2">'.price($propal->price).'</td></tr>';
		print '<tr><td>'.$langs->trans('TotalVAT').'</td><td colspan="2">'.price($propal->total_tva)."</td></tr>";
		print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($propal->total)."</td></tr>";
	}
	elseif ($_GET['commandeid'] > 0)
	{
		print '<input type="hidden" name="amount"         value="'.$commande->total_ht.'">'."\n";
		print '<input type="hidden" name="total"          value="'.$commande->total_ttc.'">'."\n";
		print '<input type="hidden" name="tva"            value="'.$commande->tva.'">'."\n";
//		print '<input type="hidden" name="remise_absolue" value="'.$commande->remise_absolue.'">'."\n";
//		print '<input type="hidden" name="remise_percent" value="'.$commande->remise_percent.'">'."\n";
		print '<input type="hidden" name="commandeid"     value="'.$commande->id.'">';

		print '<tr><td>'.$langs->trans('Order').'</td><td colspan="2"><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$commande->id.'">'.img_object($langs->trans("ShowOrder"),'order').' '.$commande->ref.'</a></td></tr>';
		print '<tr><td>'.$langs->trans('TotalHT').'</td><td colspan="2">'.price($commande->total_ht).'</td></tr>';
		print '<tr><td>'.$langs->trans('TotalVAT').'</td><td colspan="2">'.price($commande->total_tva)."</td></tr>";
		print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($commande->total_ttc)."</td></tr>";
	}
	elseif ($_GET['contratid'] > 0)
	{
		// Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
		$contrat->remise_absolue=$remise_absolue;
		$contrat->remise_percent=$remise_percent;
		$contrat->update_price();

		print '<input type="hidden" name="amount"         value="'.$contrat->total_ht.'">'."\n";
		print '<input type="hidden" name="total"          value="'.$contrat->total_ttc.'">'."\n";
		print '<input type="hidden" name="tva"            value="'.$contrat->total_tva.'">'."\n";
//		print '<input type="hidden" name="remise_absolue" value="'.$contrat->remise_absolue.'">'."\n";
//		print '<input type="hidden" name="remise_percent" value="'.$contrat->remise_percent.'">'."\n";
		print '<input type="hidden" name="contratid"      value="'.$contrat->id.'">';

		print '<tr><td>'.$langs->trans('Contract').'</td><td colspan="2"><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$contrat->id.'">'.img_object($langs->trans("ShowContract"),'contract').' '.$contrat->ref.'</a></td></tr>';
		print '<tr><td>'.$langs->trans('TotalHT').'</td><td colspan="2">'.price($contrat->total_ht).'</td></tr>';
		print '<tr><td>'.$langs->trans('TotalVAT').'</td><td colspan="2">'.price($contrat->total_tva)."</td></tr>";
		print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($contrat->total_ttc)."</td></tr>";
	}
	else
	{
		if ($conf->global->PRODUCT_SHOW_WHEN_CREATE)
		{
			print '<tr><td colspan="3">';
	
			// Zone de choix des produits prédéfinis à la création
			print '<table class="noborder">';
			print '<tr><td>'.$langs->trans('ProductsAndServices').'</td>';
			print '<td>'.$langs->trans('Qty').'</td>';
			print '<td>'.$langs->trans('ReductionShort').'</td>';
			print '<td> &nbsp; &nbsp; </td>';
			if ($conf->service->enabled)
			{
				print '<td>'.$langs->trans('ServiceLimitedDuration').'</td>';
			}
			print '</tr>';
			for ($i = 1 ; $i <= $NBLINES ; $i++)
			{
				print '<tr>';
				print '<td>';
				// multiprix
				if($conf->global->PRODUIT_MULTIPRICES == 1)
					$html->select_produits('','idprod'.$i,'',$conf->produit->limit_size,$soc->price_level);
				else
					$html->select_produits('','idprod'.$i,'',$conf->produit->limit_size);
				print '</td>';
				print '<td><input type="text" size="2" name="qty'.$i.'" value="1"></td>';
				print '<td nowrap="nowrap"><input type="text" size="1" name="remise_percent'.$i.'" value="'.$soc->remise_client.'">%</td>';
				print '<td>&nbsp;</td>';
				// Si le module service est actif, on propose des dates de début et fin à la ligne
				if ($conf->service->enabled)
				{
					print '<td nowrap="nowrap">';
					print $langs->trans('From').' ';
					print $html->select_date('','date_start'.$i,0,0,1,"add");
					print '<br>'.$langs->trans('to').' ';
					print $html->select_date('','date_end'.$i,0,0,1,"add");
					print '</td>';
				}
				print "</tr>\n";
			}
	
			print '</table>';
			print '</td></tr>';
		}
	}

	/*
	 * Factures récurrentes
	 */
	if (! $conf->global->FACTURE_DISABLE_RECUR)
	{
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
					print '<tr><td colspan="3">'.$langs->trans('CreateFromRepeatableInvoice').' : <select class="flat" name="fac_rec">';
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
	}
	
	// Bouton "Create Draft"
	print '<tr><td colspan="3" align="center"><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'"></td></tr>';
	print "</table>\n";

	print "</form>\n";

	// Si creation depuis un propal
	if ($_GET['propalid'])
	{
		$title=$langs->trans('ProductsAndServices');
		
		$sql = 'SELECT pt.rowid, pt.description, pt.price, pt.fk_product, pt.fk_remise_except,';
		$sql.= ' pt.qty, pt.tva_tx, pt.remise_percent, pt.subprice, pt.info_bits,';
		$sql.= ' p.label as product, p.ref, p.fk_product_type, p.rowid as prodid,';
		$sql.= ' p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'propaldet as pt';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pt.fk_product=p.rowid';
		$sql.= ' WHERE pt.fk_propal = '.$_GET['propalid'];
		$sql.= ' ORDER BY pt.rang ASC, pt.rowid';
	}
	if ($_GET['commandeid'])
	{
		$title=$langs->trans('Products');

		$sql = 'SELECT pt.rowid, pt.subprice, pt.tva_tx, pt.qty, pt.remise_percent, pt.description, pt.info_bits,';
		$sql.= ' p.label as product, p.ref, p.rowid as prodid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'commandedet as pt';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pt.fk_product = p.rowid';
		$sql.= ' WHERE pt.fk_commande = '.$commande->id;
		$sql.= ' ORDER BY pt.rowid ASC';
	}
	if ($_GET['contratid'])
	{
		$title=$langs->trans('Services');
		
		$sql = 'SELECT pt.rowid, pt.subprice, pt.tva_tx, pt.qty, pt.remise_percent, pt.description, pt.info_bits,';
		$sql.= ' pt.date_ouverture_prevue as date_debut_prevue, pt.date_ouverture as date_debut_reel,';
		$sql.= ' pt.date_fin_validite as date_fin_prevue, pt.date_cloture as date_fin_reel,';
		$sql.= ' p.label as product, p.ref, p.rowid as prodid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'contratdet as pt';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pt.fk_product = p.rowid';
		$sql.= ' WHERE pt.fk_contrat = '.$contrat->id;
		$sql.= ' ORDER BY pt.rowid ASC';
	}
	if ($_GET['propalid'] || $_GET['commandeid'] || $_GET['contratid'])
	{
		print '<br>';
		print_titre($title);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Ref').'</td>';
		print '<td>'.$langs->trans('Description').'</td>';
		print '<td align="right">'.$langs->trans('VAT').'</td>';
		print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
		print '<td align="right">'.$langs->trans('Qty').'</td>';
		print '<td align="right">'.$langs->trans('ReductionShort').'</td></tr>';

		// Lignes
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			$var=True;
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print '<tr '.$bc[$var].'><td>';
				if (($objp->info_bits & 2) == 2)
				{
					print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$propal->socidp.'">';
					print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
					print '</a>';
				}
				else if ($objp->prodid)
				{
					print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->prodid.'">'.img_object($langs->trans(''),'service').' '.$objp->ref.'</a>';
					print $objp->product?' - '.$objp->product:'';
					// Plage de dates si contrat
					if ($_GET['contratid'])
					{
						$date_start=$objp->date_debut_prevue;
						if ($objp->date_debut_reel) $date_start=$objp->date_debut_reel;
						$date_end=$objp->date_fin_prevue;
						if ($objp->date_fin_reel) $date_end=$objp->date_fin_reel;
						print_date_range($date_start,$date_end);
					}
				}
				else
				{
					print '&nbsp;';
				}
				print "</td>\n";
				print '<td>';
				print dolibarr_trunc($objp->description,60);
				print '</td>';
				print '<td align="right">'.$objp->tva_tx.'%</td>';
				print '<td align="right">'.price($objp->subprice).'</td>';
				print '<td align="right">';
				print (($objp->info_bits & 2) != 2) ? $objp->qty : '&nbsp;';
				print '</td>';
				print '<td align="right">';
				print (($objp->info_bits & 2) != 2) ? $objp->remise_percent.'%' : '&nbsp;';
				print '</td>';
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
	$id = $_GET['facid'];
	if ($id > 0)
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
			$absolute_discount=$soc->getCurrentDiscount();

 			$totalpaye = $fac->getSommePaiement();
			$resteapayer = $fac->total_ttc - $totalpaye;
			if ($fac->paye) $resteapayer=0;
			
			$author = new User($db);
			if ($fac->user_author)
			{
			    $author->id = $fac->user_author;
			    $author->fetch();
			}

			$head = facture_prepare_head($fac);
 
			dolibarr_fiche_head($head, 'compta', $langs->trans('InvoiceCustomer'));

			/*
			 * Confirmation de la suppression de la facture
 			 */
			if ($_GET['action'] == 'delete')
			{
				$text=$langs->trans('ConfirmDeleteBill');
				$html->form_confirm($_SERVER['PHP_SELF'].'?facid='.$fac->id,$langs->trans('DeleteBill'),$text,'confirm_delete');
				print '<br />';
			}

			/*
 			 * Confirmation de la validation
 			 */
			if ($_GET['action'] == 'valid')
			{
				// on vérifie si la facture est en numérotation provisoire
				$facref = substr($fac->ref, 1, 4);
				if ($facref == 'PROV')
				{
					$numfa = $fac->getNextNumRef($soc);
				}
				else
				{
					$numfa = $fac->ref;
				}

				$text=$langs->trans('ConfirmValidateBill',$numfa);
				if ($conf->notification->enabled)
				{
					require_once(DOL_DOCUMENT_ROOT ."/notify.class.php");
					$notify=new Notify($db);
					$text.='<br>';
					$text.=$notify->confirmMessage(2,$fac->socidp);
				}

				$html->form_confirm($_SERVER["PHP_SELF"].'?facid='.$fac->id,$langs->trans('ValidateBill'),$text,'confirm_valid');
				print '<br />';
			}

			/*
 			 * Confirmation du classement payé
 			 */
			if ($_GET['action'] == 'payed' && $resteapayer <= 0)
			{
				$html->form_confirm($_SERVER["PHP_SELF"].'?facid='.$fac->id,$langs->trans('ClassifyPayed'),$langs->trans('ConfirmClassifyPayedBill',$fac->ref),'confirm_payed');
				print '<br />';
			}
			if ($_GET['action'] == 'payed' && $resteapayer > 0)
			{
				// Crée un tableau formulaire
				//$helpescompte_avoir=$langs->trans("ConfirmClassifyPayedPartiallyAvoir");
				$helpescompte_vat    =$langs->trans("HelpEscompte").'<br><br>'.$langs->trans("ConfirmClassifyPayedPartiallyVat");
				$helpescompte_abandon=$langs->trans("ConfirmClassifyPayedPartiallyAbandon");
				//$reason_avoir  =$html->textwithhelp($langs->transnoentities("ConfirmClassifyPayedPartiallyReasonAvoir",$resteapayer,$langs->trans("Currency".$conf->monnaie)),$helpescompte_avoir,1);
				$reason_vat    =$html->textwithhelp($langs->transnoentities("ConfirmClassifyPayedPartiallyReasonDiscountVat",$resteapayer,$langs->trans("Currency".$conf->monnaie)),$helpescompte_vat,1);
				$reason_abandon=$html->textwithhelp($langs->transnoentities("ConfirmClassifyPayedPartiallyReasonAbandon",$resteapayer,$langs->trans("Currency".$conf->monnaie)),$helpescompte_abandon,1);
				//$arrayreasons['avoir']       =$reason_avoir;
				$arrayreasons['discount_vat']=$reason_vat;
				$arrayreasons['abandon']     =$reason_abandon;
				$formquestion=array(
					'text' => $langs->trans("ConfirmClassifyPayedPartiallyQuestion"),
					array('type' => 'radio', 'name' => 'close_code', 'label' => $langs->trans("Reason"),  'values' => $arrayreasons),
					array('type' => 'text',  'name' => 'close_note', 'label' => $langs->trans("Comment"), 'value' => '', 'size' => '100')
				);
				// Paiement incomplet. On demande si motif = escompte ou autre
				$html->form_confirm($_SERVER["PHP_SELF"].'?facid='.$fac->id,$langs->trans('ClassifyPayed'),$langs->trans('ConfirmClassifyPayedPartially',$fac->ref),'confirm_payed_partially',$formquestion);
				print '<br />';
			}

			/*
			 * Confirmation du classement abandonne
			 */
			if ($_GET['action'] == 'canceled')
			{
				$html->form_confirm($_SERVER['PHP_SELF'].'?facid='.$fac->id,$langs->trans('CancelBill'),$langs->trans('ConfirmCancelBill',$fac->ref),'confirm_canceled');
				print '<br />';
			}
			
			/*
			 * Confirmation de la suppression d'une ligne produit
			 */
			if ($_GET['action'] == 'delete_product_line' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
			{
				$html->form_confirm($_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;rowid='.$_GET["rowid"], $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteproductline');
				print '<br />';
			}

			/*
			 *   Facture
			 */
			print '<table class="border" width="100%">';
			
			// Reference
			print '<tr><td width="20%">'.$langs->trans('Ref').'</td><td colspan="5">'.$fac->ref.'</td></tr>';
			
			// Type
			print '<tr><td width="20%">'.$langs->trans('Type').'</td><td colspan="5">';
			print $fac->getLibType();
			if ($fac->type == 1)
			{
				$facreplaced=new Facture($db);
				$facreplaced->fetch($fac->fk_facture_source);
				print ' ('.$langs->transnoentities("ReplaceInvoice",$facreplaced->getNomUrl(1)).')';
			}
			$facidnext=$fac->getIdNextInvoice();
			if ($facidnext > 0)
			{
				$facthatreplace=new Facture($db);
				$facthatreplace->fetch($facidnext);
				print ' ('.$langs->transnoentities("ReplacedByInvoice",$facthatreplace->getNomUrl(1)).')';
			}
			print '</td></tr>';
			
			// Société
			print '<tr><td>'.$langs->trans('Company').'</td>';
			print '<td colspan="5">'.$soc->getNomUrl(1,'compta').'</td>';
			print '</tr>';

			// Ligne info remises tiers
			print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="5">';
			if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
			else print $langs->trans("CompanyHasNoRelativeDiscount");
			print '. ';
			if ($absolute_discount)
			{
				if ($fac->statut > 0)
				{
					print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->monnaie));
				}
				else
				{
					print '<br>';
					print $html->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$fac->id,0,'remise_id',$soc->id,$absolute_discount);
				}
			}
			else print $langs->trans("CompanyHasNoAbsoluteDiscount").'.';
			print '</td></tr>';
    
			// Dates
			print '<tr><td>'.$langs->trans('Date').'</td>';
			print '<td colspan="3">'.dolibarr_print_date($fac->date,'%A %d %B %Y').'</td>';
			
            $nbrows=8;
            if ($conf->global->FAC_USE_CUSTOMER_ORDER_REF) $nbrows++;
			if ($conf->projet->enabled) $nbrows++;

			print '<td rowspan="'.$nbrows.'" colspan="2" valign="top">';

			/*
			 * Liste des paiements
			 */
			$sql = 'SELECT '.$db->pdate('datep').' as dp, pf.amount,';
			$sql.= ' c.libelle as paiement_type, p.num_paiement, p.rowid';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement as p, '.MAIN_DB_PREFIX.'c_paiement as c, '.MAIN_DB_PREFIX.'paiement_facture as pf';
			$sql.= ' WHERE pf.fk_facture = '.$fac->id.' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';
			$sql.= ' ORDER BY dp DESC';

			$result = $db->query($sql);

			if ($result)
			{
				$num = $db->num_rows($result);
				$i = 0;
				print '<table class="noborder" width="100%">';
				print '<tr class="liste_titre"><td>'.$langs->trans('Payments').'</td><td>'.$langs->trans('Type').'</td>';
				print '<td align="right">'.$langs->trans('Amount').'</td><td>&nbsp;</td></tr>';

				$var=True;
				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$var=!$var;
					print '<tr '.$bc[$var].'><td>';
					print '<a href="'.DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans('ShowPayment'),'payment').' ';
					print dolibarr_print_date($objp->dp).'</a></td>';
					print '<td>'.$objp->paiement_type.' '.$objp->num_paiement.'</td>';
					print '<td align="right">'.price($objp->amount).'</td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td>';
					print '</tr>';
					$i++;
				}

				print '<tr><td colspan="2" align="right">'.$langs->trans('AlreadyPayed').' :</td><td align="right"><b>'.price($totalpaye).'</b></td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
				print '<tr><td colspan="2" align="right">'.$langs->trans("Billed").' :</td><td align="right" style="border: 1px solid;">'.price($fac->total_ttc).'</td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
				if ($fac->close_code == 'escompte')
				{
					print '<tr><td colspan="2" align="right" nowrap="1">';
					print $html->textwithhelp($langs->trans("Escompte").':',$langs->trans("HelpEscompte"),-1);
					print '</td><td align="right">'.price($fac->total_ttc - $totalpaye).'</td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
				}
				print '<tr><td colspan="2" align="right">'.$langs->trans('RemainderToPay').' :</td>';
				print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($resteapayer).'</b></td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

				print '</table>';
				$db->free($result);
			}
			else
			{
				dolibarr_print_error($db);
			}

			print '</td></tr>';					
						
			// Date limite reglement
			print '<tr>';
			print '<td>'.$langs->trans('DateMaxPayment').'</td>';
			print '<td colspan="3">' . dolibarr_print_date($fac->date_lim_reglement,'%A %d %B %Y');
			if ($fac->date_lim_reglement < (time() - $conf->facture->client->warning_delay) && ! $fac->paye && $fac->statut == 1 && ! $fac->am) print img_warning($langs->trans('Late'));
			print '</td></tr>';

			// Conditions de réglement
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('PaymentConditionsShort');
			print '</td>';
			if ($_GET['action'] != 'editconditions' && $fac->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;facid='.$fac->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
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
			print '</td></tr>';
			
			// Mode de reglement
			print '<tr><td width="25%">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('PaymentMode');
			print '</td>';
			if ($_GET['action'] != 'editmode' && $fac->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;facid='.$fac->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($_GET['action'] == 'editmode')
			{
				$html->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->mode_reglement_id,'mode_reglement_id');
			}
			else
			{
				$html->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->mode_reglement_id,'none');
			}
			print '</td></tr>';


            /*
              \todo
              L'info "Reference commande client" est une carac de la commande et non de la facture.
              Elle devrait donc etre stockée sur l'objet commande lié à la facture et non sur la facture.
              Pour ceux qui utilisent ainsi, positionner la constante FAC_USE_CUSTOMER_ORDER_REF à 1.
            */
            if ($conf->global->FAC_USE_CUSTOMER_ORDER_REF)
            {
			    print '<tr><td>'.$langs->trans('RefCustomerOrder').'</td>';

    			if ($fac->brouillon == 1 && $user->rights->facture->creer)
    			{
    					print '<form action="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'" method="post">';
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

			// Lit lignes de facture pour déterminer montant
			// On s'en sert pas mais ca sert pour debuggage
			$sql  = 'SELECT l.price as price, l.qty, l.rowid, l.tva_taux,';
			$sql .= ' l.remise_percent, l.subprice';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'facturedet as l ';
			$sql .= ' WHERE l.fk_facture = '.$fac->id;
			$resql = $db->query($sql);
			if ($resql)
			{
				$num_lignes = $db->num_rows($resql);
				$i=0;
				$total_lignes_ht=0;
				$total_lignes_vat=0;
				$total_lignes_ttc=0;
				while ($i < $num_lignes)
				{
					$obj=$db->fetch_object($resql);
					$ligne_ht=($obj->price*$obj->qty);
					$ligne_vat=($ligne_ht*$obj->tva_taux/100);
					$ligne_ttc=($ligne_ht+$ligne_vat);
					$total_lignes_ht+=$ligne_ht;
					$total_lignes_vat+=$ligne_vat;
					$total_lignes_ttc+=$ligne_ttc;
					$i++;			
				}
			}
			
			// Montants
			print '<tr><td>'.$langs->trans('AmountHT').'</td>';
			print '<td align="right" colspan="2" nowrap>'.price($fac->total_ht).'</td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right" colspan="2" nowrap>'.price($fac->total_tva).'</td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right" colspan="2" nowrap>'.price($fac->total_ttc).'</td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

			// Statut
			print '<tr><td>'.$langs->trans('Status').'</td>';
			print '<td align="left" colspan="3">'.($fac->getLibStatut(4,$totalpaye)).'</td></tr>';

			// Projet
			if ($conf->projet->enabled)
			{
				$langs->load('projects');
				print '<tr>';
				print '<td>';
				
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('Project');
				print '</td>';
				if ($_GET['action'] != 'classer')
				{
				    print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classer&amp;facid='.$fac->id.'">';
				    print img_edit($langs->trans('SetProject'),1);
				    print '</a></td>';
				}
				print '</tr></table>';
				
				print '</td><td colspan="3">';
				if ($_GET['action'] == 'classer')
				{
					$html->form_project($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->socidp,$fac->projetid,'projetid');
				}
				else
				{
					$html->form_project($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->socidp,$fac->projetid,'none');
				}
				print '</td>';
				print '</tr>';
			}
						
			print '</table><br>';


			/*
			 * Lignes de factures
			 */
			$sql = 'SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_taux,';
			$sql.= ' l.remise_percent, l.subprice, l.info_bits,';
			$sql.= ' '.$db->pdate('l.date_start').' as date_start,';
			$sql.= ' '.$db->pdate('l.date_end').' as date_end,';
			$sql.= ' p.ref, p.fk_product_type, p.label as product,';
          	$sql.= ' p.description as product_desc';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet as l';
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product p ON l.fk_product=p.rowid';
			$sql.= ' WHERE l.fk_facture = '.$fac->id;
			$sql.= ' ORDER BY l.rang ASC, l.rowid';

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
					print '<td align="right" width="50">'.$langs->trans('ReductionShort').'</td>';
					print '<td align="right" width="50">'.$langs->trans('AmountHT').'</td>';
					print '<td width="16">&nbsp;</td>';
					print '<td width="16">&nbsp;</td>';
					print '<td width="12">&nbsp;</td>';
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
							print '<td>';
							print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
							print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
							if ($objp->fk_product_type) print img_object($langs->trans('ShowService'),'service');
							else print img_object($langs->trans('ShowProduct'),'product');
							print ' '.$objp->ref.'</a>';
							print ' - '.nl2br(stripslashes($objp->product));
							print_date_range($objp->date_start,$objp->date_end);
							print ($objp->description && $objp->description!=$objp->product)?'<br>'.stripslashes(nl2br($objp->description)):'';
							
							if ($conf->global->FORM_ADD_PROD_DESC && !$conf->global->PRODUIT_CHANGE_PROD_DESC)
                            {
                            	print '<br>'.nl2br(stripslashes($objp->product_desc));
                            }
							
							print '</td>';
						}
						else
						{
							print '<td>';
							print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
							if (($objp->info_bits & 2) == 2)
							{
								print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$fac->socidp.'">';
								print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
								print '</a>';
								if ($objp->description) print ' - '.nl2br($objp->description);
							}
							else
							{
								print nl2br($objp->description);
								print_date_range($objp->date_start,$objp->date_end);
							}
							print "</td>\n";
						}
						print '<td align="right">'.$objp->tva_taux.'%</td>';
						print '<td align="right">'.price($objp->subprice)."</td>\n";
						print '<td align="right">';
						if (($objp->info_bits & 2) != 2)
						{
							print $objp->qty;
						}
						else print '&nbsp;';
						print '</td>';
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
							print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=editline&amp;rowid='.$objp->rowid.'#'.$objp->rowid.'">';
							print img_edit();
							print '</a></td>';
							if ($conf->global->PRODUIT_CONFIRM_DELETE_LINE)
							{
								print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=delete_product_line&amp;rowid='.$objp->rowid.'">';
							}
							else
							{
								print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=deleteline&amp;rowid='.$objp->rowid.'">';
							}
							print img_delete();
							print '</a></td>';
							print '<td align="right">';
							if ($i > 0)
							{
								print '<a href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=up&amp;rowid='.$objp->rowid.'">';
								print img_up();
								print '</a>';
							}
							if ($i < $num_lignes-1)
							{
								print '<a href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=down&amp;rowid='.$objp->rowid.'">';
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
						print '<form name="updateligne" action="'.$_SERVER["PHP_SELF"].'#'.$objp->rowid.'" method="post">';
						print '<input type="hidden" name="action" value="updateligne">';
						print '<input type="hidden" name="facid" value="'.$fac->id.'">';
						print '<input type="hidden" name="rowid" value="'.$_GET['rowid'].'">';
						print '<tr '.$bc[$var].'>';
						print '<td>';
						print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
						if ($objp->fk_product > 0)
						{
							print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
							if ($objp->fk_product_type) print img_object($langs->trans('ShowService'),'service');
							else print img_object($langs->trans('ShowProduct'),'product');
							print ' '.$objp->ref.'</a>';
							print ' - '.nl2br($objp->product);
							print '<br>';
						}
						// éditeur wysiwyg
						if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
						{
					    	require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
							$doleditor=new DolEditor('desc',$objp->description,164,'dolibarr_details');
							$doleditor->Create();
						}
						else
						{
							print '<textarea name="desc" class="flat" cols="70" rows="'.ROWS_2.'">'.$objp->description.'</textarea>';
						}
						print '</td>';
						print '<td align="right">';
						if(! $soc->tva_assuj)
							print '<input type="hidden" name="tva_tx" value="0">0';
						else
							print $html->select_tva('tva_tx',$objp->tva_taux,$mysoc,$soc);
						print '</td>';
						print '<td align="right"><input size="6" type="text" class="flat" name="price" value="'.price($objp->subprice).'"></td>';
						print '<td align="right">';
						if (($objp->info_bits & 2) != 2)
						{
							print '<input size="2" type="text" class="flat" name="qty" value="'.$objp->qty.'">';
						}
						else print '&nbsp;';
						print '</td>';
						print '<td align="right" nowrap>';
						if (($objp->info_bits & 2) != 2)
						{
							print '<input size="2" type="text" class="flat" name="remise_percent" value="'.$objp->remise_percent.'">%';
						}
						else print '&nbsp;';
						print '</td>';
						print '<td align="center" rowspan="1" colspan="5" valign="center"><input type="submit" class="button" name="save" value="'.$langs->trans('Save').'">';
						print '<br /><input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></td>';
						print '</tr>' . "\n";
						if ($conf->service->enabled)
						{
							print '<tr '.$bc[$var].'>';
							print '<td colspan="9">'.$langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
							print $html->select_date($objp->date_start,'date_start',0,0,$objp->date_start?0:1,"updateligne");
							print ' '.$langs->trans('to').' ';
							print $html->select_date($objp->date_end,'date_end',0,0,$objp->date_end?0:1,"updateligne");
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
			 * Lignes de remise
			 */
			
    // Réductions relatives (Remises-Ristournes-Rabbais)
/* Une réduction doit s'appliquer obligatoirement sur des lignes de factures
			$var=!$var;
			print '<form name="updateligne" action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<input type="hidden" name="action" value="setremisepercent">';
			print '<input type="hidden" name="facid" value="'.$fac->id.'">';
			print '<tr class="liste_total"><td>';
			print $langs->trans('CustomerRelativeDiscount');
			if ($fac->brouillon) print ' <font style="font-weight: normal">('.($soc->remise_client?$langs->trans("CompanyHasRelativeDiscount",$soc->remise_client):$langs->trans("CompanyHasNoRelativeDiscount")).')</font>';
			print '</td>';
			print '<td>&nbsp;</td>';
			print '<td>&nbsp;</td>';
			print '<td>&nbsp;</td>';
			print '<td align="right"><font style="font-weight: normal">';
			if ($_GET['action'] == 'editrelativediscount')
			{
				print '<input type="text" name="remise_percent" size="2" value="'.$fac->remise_percent.'">%';
			}
			else
			{
				print $fac->remise_percent?$fac->remise_percent.'%':'&nbsp;';
			}
			print '</font></td>';
			print '<td align="right"><font style="font-weight: normal">';
			if ($_GET['action'] != 'editrelativediscount') print $fac->remise_percent?'-'.price($fac->remise_percent*$total/100):$langs->trans("DiscountNone");
			else print '&nbsp;';
			print '</font></td>';
			if ($_GET['action'] != 'editrelativediscount')
			{
				if ($fac->brouillon && $user->rights->facture->creer)
				{
					print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editrelativediscount&amp;facid='.$fac->id.'">'.img_edit($langs->trans('SetRelativeDiscount'),1).'</a></td>';
				}
				else
				{
					print '<td>&nbsp;</td>';
				}
				if ($fac->brouillon && $user->rights->facture->creer && $fac->remise_percent)
				{
					print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=setremisepercent&amp;rowid='.$objp->rowid.'">';
					print img_delete();
					print '</a></td>';
				}
				else
				{
					print '<td>&nbsp;</td>';
				}
				print '<td>&nbsp;</td>';
			}
			else
			{
				print '<td colspan="3"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td>';
			}
			print '</tr>';
			print '</form>';
*/

		    // Réductions absolues (Remises-Ristournes-Rabbais)
/* Les remises absolues doivent s'appliquer par ajout de lignes spécialisées
			$var=!$var;
			print '<form name="updateligne" action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<input type="hidden" name="action" value="setremiseabsolue">';
			print '<input type="hidden" name="facid" value="'.$fac->id.'">';
			print '<tr class="liste_total"><td>';
			print $langs->trans('CustomerAbsoluteDiscount');
			if ($fac->brouillon) print ' <font style="font-weight: normal">('.($absolute_discount?$langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->monnaie)):$langs->trans("CompanyHasNoAbsoluteDiscount")).')</font>';
			print '</td>';
			print '<td>&nbsp;</td>';
			print '<td>&nbsp;</td>';
			print '<td>&nbsp;</td>';
			print '<td>&nbsp;</td>';
			print '<td align="right"><font style="font-weight: normal">';
			if ($_GET['action'] == 'editabsolutediscount')
			{
				print '-<input type="text" name="remise_absolue" size="2" value="'.$fac->remise_absolue.'">';
			}
			else
			{
				print $fac->remise_absolue?'-'.price($fac->remise_absolue):$langs->trans("DiscountNone");
			}
			print '</font></td>';
			if ($_GET['action'] != 'editabsolutediscount')
			{
				if ($fac->brouillon && $user->rights->facture->creer)
				{
					print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editabsolutediscount&amp;facid='.$fac->id.'">'.img_edit($langs->trans('SetAbsoluteDiscount'),1).'</a></td>';
				}
				else
				{
					print '<td>&nbsp;</td>';
				}
				if ($fac->brouillon && $user->rights->facture->creer && $fac->remise_absolue)
				{
					print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=setremiseabsolue&amp;rowid='.$objp->rowid.'">';
					print img_delete();
					print '</a></td>';
				}
				else
				{
					print '<td>&nbsp;</td>';
				}
				print '<td>&nbsp;</td>';
			}
			else
			{
				print '<td colspan="3"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td>';
			}
			print '</tr>';
			print '</form>';
*/

			/*
			 * Ajouter une ligne
			 */
			if ($fac->statut == 0 && $user->rights->facture->creer && $_GET['action'] <> 'valid')
			{
				print '<tr class="liste_titre">';
				print '<td>';
				print '<a name="add"></a>'; // ancre
				print $langs->trans('Description').'</td>';
				print '<td align="right">'.$langs->trans('VAT').'</td>';
				print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
				print '<td align="right">'.$langs->trans('Qty').'</td>';
				print '<td align="right">'.$langs->trans('ReductionShort').'</td>';
				print '<td>&nbsp;</td>';
				print '<td>&nbsp;</td>';
				print '<td>&nbsp;</td>';
				print '<td>&nbsp;</td>';
				print "</tr>\n";

                // Ajout produit produits/services personalisés
				print '<form name="addligne" action="'.$_SERVER['PHP_SELF'].'#add" method="post">';
				print '<input type="hidden" name="facid" value="'.$fac->id.'">';
				print '<input type="hidden" name="action" value="addligne">';

                $var=true;
				print '<tr '.$bc[$var].'>';
				print '<td colspan="1">';
				print '<textarea name="desc" cols="70" rows="'.ROWS_2.'"></textarea></td>';
				print '<td align="right">';
				if($soc->tva_assuj == "0")
					print '<input type="hidden" name="tva_tx" value="0">0';
				else
					$html->select_tva('tva_tx',$conf->defaulttx,$mysoc,$soc);
				print '</td>';
				print '<td align="right"><input type="text" name="pu" size="6"></td>';
				print '<td align="right"><input type="text" name="qty" value="1" size="2"></td>';
				print '<td align="right" nowrap><input type="text" name="remise_percent" size="1" value="'.$soc->remise_client.'">%</td>';
				print '<td align="center" valign="middle" colspan="4"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
				print '</tr>';
				if ($conf->service->enabled)
				{
					print '<tr '.$bc[$var].'>';
					print '<td colspan="9">'.$langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
					print $html->select_date('','date_start',0,0,1,"addligne");
					print ' '.$langs->trans('to').' ';
					print $html->select_date('','date_end',0,0,1,"addligne");
					print '</td>';
					print '</tr>';
				}
				print '</form>';

                // Ajout de produits/services prédéfinis
                if ($conf->produit->enabled)
                {
    				print '<form name="addligne_predef" action="'.$_SERVER['PHP_SELF'].'#add" method="post">';
    				print '<input type="hidden" name="facid" value="'.$fac->id.'">';
    				print '<input type="hidden" name="action" value="addligne_predef">';
    
                    $var=! $var;
    				print '<tr '.$bc[$var].'>';
    				print '<td colspan="2">';
					// multiprix
					if($conf->global->PRODUIT_MULTIPRICES == 1)
						$html->select_produits('','idprod','',$conf->produit->limit_size,$soc->price_level);
					else
                    	$html->select_produits('','idprod','',$conf->produit->limit_size);
                    if (! $conf->use_ajax) print '<br>';
                    print '<textarea name="desc" cols="70" rows="'.ROWS_2.'"></textarea></td>';
                    print '<td>&nbsp;</td>';
    				print '<td align="right"><input type="text" name="qty" value="1" size="2"></td>';
    				print '<td align="right" nowrap><input type="text" name="remise_percent" size="1" value="'.$soc->remise_client.'">%</td>';
    				print '<td align="center" valign="middle" rowspan="2" colspan="5"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
    				print '</tr>';
    				if ($conf->service->enabled)
    				{
    					print '<tr '.$bc[$var].'>';
    					print '<td colspan="5">'.$langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
    					print $html->select_date('','date_start_predef',0,0,1,"addligne_predef");
    					print ' '.$langs->trans('to').' ';
    					print $html->select_date('','date_end_predef',0,0,1,"addligne_predef");
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
			
				// Editer une facture déjà validée, sans paiement effectué et pas exporté en compta
				if ($fac->statut == 1)
				{
					// On vérifie si les lignes de factures ont été exportées en compta et/ou ventilées
					$ventilExportCompta = $fac->getVentilExportCompta();
			
					if ($conf->global->FACTURE_ENABLE_EDITDELETE && $user->rights->facture->modifier
					&& ($resteapayer == $fac->total_ttc	&& $fac->paye == 0 && $ventilExportCompta == 0))
					{
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=modif">'.$langs->trans('Edit').'</a>';
					}
				}

				// Récurrente
				if (! $conf->global->FACTURE_DISABLE_RECUR)
				{
					print '  <a class="butAction" href="facture/fiche-rec.php?facid='.$fac->id.'&amp;action=create">'.$langs->trans("ChangeIntoRepeatableInvoice").'</a>';
				}
			
				// Valider
				if ($fac->statut == 0 && $num_lignes > 0)
				{
					if ($user->rights->facture->valider)
					{
						print '  <a class="butAction" href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=valid">'.$langs->trans('Validate').'</a>';
					}
				}
				else
				{
					// Générer
					if ($fac->statut >= 1 && $user->rights->facture->creer)
					{
						if ($fac->paye == 0)
						{
							print '  <a class="butAction" href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=builddoc">'.$langs->trans('BuildPDF').'</a>';
						}
						else
						{
							print '  <a class="butAction" href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=builddoc">'.$langs->trans('RebuildPDF').'</a>';
						}
					}
				}
			
				// Envoyer
				if ($fac->statut == 1 && $user->rights->facture->envoyer)
				{
					print '  <a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=presend">'.$langs->trans('SendByMail').'</a>';
				}
			
				// Envoyer une relance
				if ($fac->statut == 1 && $resteapayer > 0 && $user->rights->facture->envoyer)
				{
					print '  <a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=prerelance">'.$langs->trans('SendRemindByMail').'</a>';
				}
			
				// Emettre paiement
				if ($fac->statut == 1 && $fac->paye == 0 && $user->rights->facture->paiement)
				{
					print '  <a class="butAction" href="paiement.php?facid='.$fac->id.'&amp;action=create">'.$langs->trans('DoPaiement').'</a>';
				}
			
				// Classer 'payé'
				if ($fac->statut == 1 && $fac->paye == 0 && $user->rights->facture->paiement
						&& $resteapayer <= 0)
				{
					print '  <a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=payed">'.$langs->trans('ClassifyPayed').'</a>';
				}
			
				// Classer 'fermée' (possible si validée et pas encore classée payée)
				if ($fac->statut == 1 && $fac->paye == 0 && $resteapayer > 0
						&& $user->rights->facture->paiement)
				{
					if ($totalpaye > 0)
					{
						print '  <a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=payed">'.$langs->trans('ClassifyPayedPartially').'</a>';
					}
					else
					{
						print '  <a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$fac->id.'&amp;action=canceled">'.$langs->trans('ClassifyCanceled').'</a>';

						// \todo
						// Ajouter bouton "Annuler et Créer facture remplacement" 
					}
				}

				// Supprimer
				if ($fac->is_erasable() && $user->rights->facture->supprimer && $_GET['action'] != 'delete')
				{
					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?facid='.$fac->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
				}
			
				print '</div>';
			}

			print '<table width="100%"><tr><td width="50%" valign="top">';
			print '<a name="builddoc"></a>'; // ancre

			/*
			 * Documents générés
			 */
			$filename=sanitize_string($fac->ref);
			$filedir=$conf->facture->dir_output . '/' . sanitize_string($fac->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?facid='.$fac->id;
			$genallowed=($fac->statut >= 1 && $user->rights->facture->creer);
			$delallowed=$user->rights->facture->supprimer;

			$var=true;

			print '<br>';
			$somethingshown=$html->show_documents('facture',$filename,$filedir,$urlsource,$genallowed,$delallowed,$fac->modelpdf);

			/*
			 *   Propales rattachées
			 */
			$sql = 'SELECT '.$db->pdate('p.datep').' as dp, p.price, p.ref, p.rowid as propalid';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'propal as p';
			if (!$conf->commande->enabled)
			{
				$sql .= ", ".MAIN_DB_PREFIX."fa_pr as fp";
				$sql .= " WHERE fp.fk_propal = p.rowid AND fp.fk_facture = ".$fac->id;
			}
			else
			{
				$sql .= ", ".MAIN_DB_PREFIX."co_pr as cp, ".MAIN_DB_PREFIX."co_fa as cf";
				$sql .= " WHERE cf.fk_facture = ".$fac->id." AND cf.fk_commande = cp.fk_commande AND cp.fk_propale = p.rowid";
			}

			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				if ($num)
				{
					$i = 0; $total = 0;
					if ($somethingshown) print '<br>';
					$somethingshown=1;
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
						if ($somethingshown) print '<br>';
						$somethingshown=1;
						print_titre($langs->trans('RelatedOrders'));
						print '<table class="noborder" width="100%">';
						print '<tr class="liste_titre">';
						print '<td>'.$langs->trans('Ref').'</td>';
           				print '<td>'.$langs->trans('RefCustomerOrderShort').'</td>';
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
				$facref = sanitize_string($fac->ref);
				$file = $conf->facture->dir_output . '/' . $facref . '/' . $facref . '.pdf';

				// Construit PDF si non existant
				if (! is_readable($file))
				{
					if ($_REQUEST['lang_id'])
					{
						$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs");
						$outputlangs->setDefaultLang($_REQUEST['lang_id']);
					}
					$result=facture_pdf_create($db, $fac->id, '', $_REQUEST['model'], $outputlangs);
				    if ($result <= 0)
				    {
				    	dolibarr_print_error($db,$result);
				        exit;
				    }    
				}			

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
				$formmail->withdeliveryreceipt=1;
				$formmail->withcancel=1;
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
				$facref = sanitize_string($fac->ref);
				$file = $conf->facture->dir_output . '/' . $facref . '/' . $facref . '.pdf';

				// Construit PDF si non existant
				if (! is_readable($file))
				{
					if ($_REQUEST['lang_id'])
					{
						$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs");
						$outputlangs->setDefaultLang($_REQUEST['lang_id']);
					}
					$result=facture_pdf_create($db, $fac->id, '', $_REQUEST['model'], $outputlangs);
				    if ($result <= 0)
				    {
				    	dolibarr_print_error($db,$result);
				        exit;
				    }    
				}	
				
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
				$formmail->withdeliveryreceipt=1;
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
		$page     =$_GET['page'];
		$sortorder=$_GET['sortorder'];
		$sortfield=$_GET['sortfield'];
		$month    =$_GET['month'];
		$year     =$_GET['year'];
		$limit = $conf->liste_limit;
		$offset = $limit * $page ;

		if (! $sortorder) $sortorder='DESC';
		if (! $sortfield) $sortfield='f.datef';

		$facturestatic=new Facture($db);

		if ($page == -1) $page = 0 ;

		$sql = 'SELECT s.nom, s.idp,';
		$sql.= ' f.rowid as facid, f.facnumber, f.increment, f.total, f.total_ttc,';
		$sql.= $db->pdate('f.datef').' as df, '.$db->pdate('f.date_lim_reglement').' as datelimite, ';
		$sql.= ' f.paye as paye, f.fk_statut';
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
				$sql .= ' AND ' . trim($filt[0]) . ' = ' . trim($filt[1]);
			}
		}
		if ($_GET['search_ref'])
		{
			$sql .= ' AND f.facnumber like \'%'.addslashes(trim($_GET['search_ref'])).'%\'';
		}
		if ($_GET['search_societe'])
		{
			$sql .= ' AND s.nom like \'%'.addslashes(trim($_GET['search_societe'])).'%\'';
		}
		if ($_GET['search_montant_ht'])
		{
			$sql .= ' AND f.total = \''.addslashes(trim($_GET['search_montant_ht'])).'\'';
		}
		if ($_GET['search_montant_ttc'])
		{
			$sql .= ' AND f.total_ttc = \''.addslashes(trim($_GET['search_montant_ttc'])).'\'';
		}
		if ($year > 0)
		{
			$sql .= ' AND date_format(f.datef, \'%Y\') = '.$year;
		}
		if ($_POST['sf_ref'])
		{
			$sql .= ' AND f.facnumber like \'%'.addslashes(trim($_POST['sf_ref'])) . '%\'';
		}
		if ($sall)
		{
			$sql .= ' AND (s.nom like \'%'.addslashes($sall).'%\' OR f.facnumber like \'%'.addslashes($sall).'%\' OR f.note like \'%'.addslashes($sall).'%\' OR fd.description like \'%'.addslashes($sall).'%\')';
		}

		$sql .= ' GROUP BY f.rowid';

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
			print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'fk_statut,paye,am','','&amp;socidp='.$socidp,'align="right"',$sortfield);
			print '</tr>';

			// Lignes des champs de filtre
			print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';
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
					print '<td nowrap="nowrap"><a href="'.$_SERVER["PHP_SELF"].'?facid='.$objp->facid.'">'.img_object($langs->trans('ShowBill'),'bill').'</a> ';
					print '<a href="'.$_SERVER["PHP_SELF"].'?facid='.$objp->facid.'">'.$objp->facnumber.'</a>'.$objp->increment;
					if ($objp->datelimite < (time() - $conf->facture->client->warning_delay) && ! $objp->paye && $objp->fk_statut == 1 && ! $objp->am) print img_warning($langs->trans('Late'));
					print '</td>';

					if ($objp->df > 0 )
					{
						print '<td align="center" nowrap>';
						$y = strftime('%Y',$objp->df);
						$m = strftime('%m',$objp->df);
						print strftime('%d',$objp->df);
						print ' <a href="'.$_SERVER["PHP_SELF"].'?year='.$y.'&amp;month='.$m.'">';
						print substr(strftime('%B',$objp->df),0,3).'</a>';
						print ' <a href="'.$_SERVER["PHP_SELF"].'?year='.$y.'">';
						print strftime('%Y',$objp->df).'</a></td>';
					}
					else
					{
						print '<td align="center"><b>!!!</b></td>';
					}
					print '<td><a href="fiche.php?socid='.$objp->idp.'">'.img_object($langs->trans('ShowCompany'),'company').' '.dolibarr_trunc($objp->nom,48).'</a></td>';
					print '<td align="right">'.price($objp->total).'</td>';
					print '<td align="right">'.price($objp->total_ttc).'</td>';
					print '<td align="right">'.price($objp->am).'</td>';

					// Affiche statut de la facture
					print '<td align="right" nowrap="nowrap">';
					print $facturestatic->LibStatut($objp->paye,$objp->fk_statut,5,$objp->am);
					print '</td>';
					
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

$db->close();

llxFooter('$Date$ - $Revision$');
?>
