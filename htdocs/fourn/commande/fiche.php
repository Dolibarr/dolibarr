<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric	Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 *
 * This	program	is free	software; you can redistribute it and/or modify
 * it under	the	terms of the GNU General Public	License	as published by
 * the Free	Software Foundation; either	version	2 of the License, or
 * (at your	option)	any	later version.
 *
 * This	program	is distributed in the hope that	it will	be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A	PARTICULAR PURPOSE.	 See the
 * GNU General Public License for more details.
 *
 * You should have received	a copy of the GNU General Public License
 * along with this program;	if not,	write to the Free Software
 * Foundation, Inc., 59	Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file		htdocs/fourn/commande/fiche.php
 *	\ingroup	commande
 *	\brief		Fiche commande
 *	\version	$Id$
 */

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/html.formorder.class.php");
require_once(DOL_DOCUMENT_ROOT.'/includes/modules/supplier_order/modules_commandefournisseur.php');
require_once DOL_DOCUMENT_ROOT."/fourn/fournisseur.commande.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/fournisseur.product.class.php";
require_once DOL_DOCUMENT_ROOT."/lib/fourn.lib.php";
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
if ($conf->projet->enabled)	require_once(DOL_DOCUMENT_ROOT.'/project.class.php');

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('propal');
$langs->load('deliveries');
$langs->load('products');
$langs->load('stocks');

$comclientid = isset($_GET["comid"])?$_GET["comid"]:'';
$socid = isset($_GET["socid"])?$_GET["socid"]:'';

// Security check
$id = isset($_GET["id"])?$_GET["id"]:$_POST["id"];
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'commande_fournisseur', $id,'');

// Recuperation	de l'id	de projet
$projetid =	0;
if ($_GET["projetid"]) $projetid =	$_GET["projetid"];

$mesg='';


/*
 * Actions
 */

// Set project
if ($_POST['action'] ==	'classin')
{
	$commande	= new CommandeFournisseur($db);
	$commande->fetch($id);
	$commande->setProject($_POST["projetid"]);
}

if ($_REQUEST['action'] ==	'setremisepercent' && $user->rights->fournisseur->commande->creer)
{
	$commande = new CommandeFournisseur($db);
	$commande->fetch($_REQUEST['id']);
	$result = $commande->set_remise($user, $_POST['remise_percent']);
	$id=$_REQUEST['id'];
}

/*
 *	Ajout d'une	ligne produit dans la commande
 */
if ($_POST['action'] ==	'addligne' && $user->rights->fournisseur->commande->creer)
{
	if ($_POST['qty'] && (($_POST['pu'] && ($_POST['np_desc'] || $_POST['dp_desc'])) || $_POST['idprodfournprice']))
	{
		$commande =	new	CommandeFournisseur($db);
		$ret=$commande->fetch($id);
		if ($ret < 0)
		{
			dol_print_error($db,$commande->error);
			exit;
		}

		// Ecrase $pu par celui	du produit
		// Ecrase $desc	par	celui du produit
		// Ecrase $txtva  par celui du produit
		if ($_POST["idprodfournprice"])	// >0 or -1
		{
			$product = new ProductFournisseur($db);
			$idprod=$product->get_buyprice($_POST['idprodfournprice'], $_POST['qty']);
			if ($idprod > 0)
			{
				$product->fetch($idprod);

				// cas special pour lequel on a les meme reference que le fournisseur
				// $label = '['.$nv_prod->ref.'] - '. $nv_prod->libelle;
				$label = $product->libelle;

				$societe='';
				if ($commande->socid)
				{
					$societe=new Societe($db);
					$societe->fetch($commande->socid);
				}

				$desc = $product->description;
				$desc.= $product->description && $_POST['np_desc'] ? "\n" : "";
				$desc.= $_POST['np_desc'];

				$tva_tx	= get_default_tva($societe,$mysoc,$product->tva_tx,$product->id);
				$type = $product->type;

				$result=$commande->addline(
				$desc,
				$pu,
				$_POST['qty'],
				$tva_tx,
				$product->id,
				$_POST['idprodfournprice'],
				$product->fourn_ref,
				$_POST['remise_percent'],
				'HT',
				$type
				);
			}
			if ($idprod == -1)
			{
				// Quantity too low
				$mesg='<div class="error">'.$langs->trans("ErrorQtyTooLowForThisSupplier").'</div>';
			}
		}
		else
		{
			$type=$_POST["type"];
			$desc=$_POST['dp_desc'];
			$tva_tx = price2num($_POST['tva_tx']);
			if (! $_POST['dp_desc'])
			{
				$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")).'</div>';
			}
			else
			{
				if (!empty($_POST['pu']))
				{
					$price_base_type = 'HT';
					$ht = price2num($_POST['pu']);
					$result=$commande->addline($desc, $ht, $_POST['qty'], $tva_tx, 0, 0, '', $_POST['remise_percent'], $price_base_type, 0, $type);
				}
				else
				{
					$ttc = price2num($_POST['amountttc']);
					$ht = $ttc / (1 + ($tauxtva / 100));
					$price_base_type = 'HT';
					$result=$commande->addline($desc, $ht, $_POST['qty'], $tva_tx, 0, 0, '', $_POST['remise_percent'], $price_base_type, $ttc, $type);
				}
			}
		}

		//print "xx".$tva_tx; exit;
		if ($result > 0)
		{
			$outputlangs = $langs;
			if (! empty($_REQUEST['lang_id']))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($_REQUEST['lang_id']);
			}
			supplier_order_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
		}
		else
		{
			$mesg='<div class="error">'.$commande->error.'</div>';
		}
	}
}

/*
 *	Mise a jour	d'une ligne	dans la	commande
 */
if ($_POST['action'] ==	'updateligne' && $user->rights->fournisseur->commande->creer &&	$_POST['save'] == $langs->trans('Save'))
{
	$commande =	new	CommandeFournisseur($db,"",$id);
	if ($commande->fetch($id) < 0) dol_print_error($db);

	$result	= $commande->updateline($_POST['elrowid'],
	$_POST['eldesc'],
	$_POST['pu'],
	$_POST['qty'],
	$_POST['remise_percent'],
	$_POST['tva_tx'],
	'HT',
	0,
	$_POST["type"]
	);

	if ($result	>= 0)
	{
		$outputlangs = $langs;
		if (! empty($_REQUEST['lang_id']))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		supplier_order_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
	}
	else
	{
		dol_print_error($db,$commande->error);
		exit;
	}
}

if ($_REQUEST['action'] == 'confirm_deleteproductline' && $_REQUEST['confirm'] == 'yes')
{
	if ($user->rights->fournisseur->commande->creer)
	{
		$commande = new CommandeFournisseur($db);
		$commande->fetch($id);
		$result = $commande->delete_line($_GET['lineid']);

		$outputlangs = $langs;
		if (! empty($_REQUEST['lang_id']))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		supplier_order_pdf_create($db, $id, $commande->modelpdf, $outputlangs);
	}
}

if ($_REQUEST['action'] == 'confirm_valid' && $_REQUEST['confirm'] == 'yes' && $user->rights->fournisseur->commande->valider)
{
	$commande =	new	CommandeFournisseur($db);

	$commande->fetch($id);

	$commande->date_commande=time();
	$result = $commande->valid($user);
	if ($result	>= 0)
	{
		$outputlangs = $langs;
		if (! empty($_REQUEST['lang_id']))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		supplier_order_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
	}
}

if ($_REQUEST['action'] ==	'confirm_approve' && $_REQUEST["confirm"] == 'yes'	&& $user->rights->fournisseur->commande->approuver)
{
	$commande =	new	CommandeFournisseur($db);
	$commande->fetch($id);
	$result	= $commande->approve($user);
}

if ($_REQUEST['action'] ==	'confirm_refuse' &&	$_REQUEST['confirm'] == 'yes' && $user->rights->fournisseur->commande->approuver)
{
	$commande = new CommandeFournisseur($db);
	$commande->fetch($id);
	$result = $commande->refuse($user);
}

if ($_REQUEST['action'] ==	'confirm_commande' && $_REQUEST['confirm']	== 'yes' &&	$user->rights->fournisseur->commande->commander)
{
	$commande =	new	CommandeFournisseur($db);
	$commande->fetch($id);
	$result	= $commande->commande($user, $_GET["datecommande"],	$_GET["methode"], $_GET['comment']);
}


if ($_REQUEST['action'] ==	'confirm_delete' && $_REQUEST['confirm'] == 'yes' && $user->rights->fournisseur->commande->creer)
{
	$commande = new CommandeFournisseur($db);
	$commande->id = $id;
	$commande->delete();
	Header('Location: index.php');
	exit;
}

if ($_POST["action"] ==	'livraison'	&& $user->rights->fournisseur->commande->receptionner)
{
	$commande =	new	CommandeFournisseur($db);
	$commande->fetch($id);

	if ($_POST["type"])
	{
		$date_liv = dol_mktime(0,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);

		$result	= $commande->Livraison($user, $date_liv, $_POST["type"], $_POST["comment"]);
		if ($result > 0)
		{
			Header("Location: fiche.php?id=".$id);
			exit;
		}
		else
		{
			dol_print_error($db,$commande->error);
			exit;
		}
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Delivery")).'</div>';
	}
}

if ($_REQUEST["action"] == 'confirm_cancel' && $_REQUEST["confirm"] == 'yes' &&	$user->rights->fournisseur->commande->annuler)
{
	$commande =	new	CommandeFournisseur($db);
	$commande->fetch($id);
	$result	= $commande->cancel($user);
	if ($result > 0)
	{
		Header("Location: fiche.php?id=".$id);
		exit;
	}
	else
	{
		$mesg=$commande->error;
	}
}

/*
 * Ordonnancement des lignes
 */

if ($_GET['action']	== 'up'	&& $user->rights->fournisseur->commande->creer)
{
	$commande =	new	CommandeFournisseur($db,'',$id);
	$commande->fetch($id);
	$commande->line_up($_GET['rowid']);

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	supplier_order_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
	Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$id.'#'.$_GET['rowid']);
	exit;
}

if ($_GET['action']	== 'down' && $user->rights->fournisseur->commande->creer)
{
	$commande =	new	CommandeFournisseur($db,'',$id);
	$commande->fetch($id);
	$commande->line_down($_GET['rowid']);

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	supplier_order_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
	Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$id.'#'.$_GET['rowid']);
	exit;
}


if ($_REQUEST['action']	== 'builddoc')	// En get ou en	post
{
	/*
	 * Generation de la	commande
	 * definit dans	/includes/modules/commande/modules_commande.php
	 */

	// Sauvegarde le dernier module	choisi pour	generer	un document
	$commande =	new	CommandeFournisseur($db, 0, $_REQUEST['id']);
	$commande->fetch($_REQUEST['id']);
	if ($_REQUEST['model'])
	{
		$commande->setDocModel($user, $_REQUEST['model']);
	}

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	$result=supplier_order_pdf_create($db, $commande->id,$commande->modelpdf,$outputlangs);
	if ($result	<= 0)
	{
		dol_print_error($db,$result);
		exit;
	}
	else
	{
		Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$commande->id.'#builddoc');
		exit;
	}
}

// Delete file
if ($action=='remove_file')
{
	$commande = new CommandeFournisseur($db);

	if ($commande->fetch($id))
	{
		$upload_dir =	$conf->commande->dir_output	. "/";
		$file =	$upload_dir	. '/' .	urldecode($_GET['file']);
		dol_delete_file($file);
		$mesg	= '<div	class="ok">'.$langs->trans("FileWasRemoved").'</div>';
	}
}


/*
 * Create an order
 */
if ($_GET["action"]	== 'create')
{
	$fourn = new Fournisseur($db);
	$result=$fourn->fetch($_GET["socid"]);

	$db->begin();

	$orderid=$fourn->create_commande($user);

	if ($orderid > 0)
	{
		$idc = $fourn->single_open_commande;

		if ($comclientid !=	'')
		{
			$fourn->updateFromCommandeClient($user,$idc,$comclientid);

		}

		$id=$orderid;
		$_GET['id']=$id;
		$_REQUEST['id']=$id;
		$db->commit();
	}
	else
	{
		$db->rollback();
		$mesg=$fourn->error;
	}
}


/*
 * View
 */

llxHeader('',$langs->trans("OrderCard"),"CommandeFournisseur");


$html =	new	Form($db);
$formfile = new FormFile($db);
$formorder = new FormOrder($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

$now=gmmktime();

$productstatic = new Product($db);

$id = $_REQUEST['id'];
$ref= $_REQUEST['ref'];
if ($id > 0 || ! empty($ref))
{
	//if ($mesg) print $mesg.'<br>';

	$commande =	new	CommandeFournisseur($db);

	$result=$commande->fetch($_REQUEST['id'],$_REQUEST['ref']);
	if ($result >= 0)
	{
		$soc = new Societe($db);
		$soc->fetch($commande->socid);

		$author	= new User($db);
		$author->id	= $commande->user_author_id;
		$author->fetch();

		$head = ordersupplier_prepare_head($commande);

		$title=$langs->trans("SupplierOrder");
		dol_fiche_head($head, 'card', $title);

		/*
		 * Confirmation de la suppression de	la commande
		 */
		if ($_GET['action']	== 'delete')
		{
			$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('DeleteOrder'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 2);
			if ($ret == 'html') print '<br>';
		}

		/*
		 * Confirmation de la validation
		 */
		if ($_GET['action']	== 'valid')
		{
			$commande->date_commande=gmmktime();

			// We check if number is temporary number
			if (eregi('^\(PROV',$commande->ref)) $newref = $commande->getNextNumRef($soc);
			else $newref = $commande->ref;

			$text=$langs->trans('ConfirmValidateOrder',$newref);
			if ($conf->notification->enabled)
			{
				require_once(DOL_DOCUMENT_ROOT ."/notify.class.php");
				$notify=new	Notify($db);
				$text.='<br>';
				$text.=$notify->confirmMessage(3,$commande->socid);
			}

			$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('ValidateOrder'), $text, 'confirm_valid', '', 0, ($conf->notification->enabled?0:1));
			if ($ret == 'html') print '<br>';
		}
		/*
		 * Confirmation de l'approbation
		 *
		 */
		if ($_GET['action']	== 'approve')
		{
			$ret=$html->form_confirm("fiche.php?id=$commande->id",$langs->trans("ApproveThisOrder"),$langs->trans("ConfirmApproveThisOrder"),"confirm_approve", '', 1, 1);
			if ($ret == 'html') print '<br>';
		}
		/*
		 * Confirmation de la desapprobation
		 *
		 */
		if ($_GET['action']	== 'refuse')
		{
			$ret=$html->form_confirm("fiche.php?id=$commande->id",$langs->trans("DenyingThisOrder"),$langs->trans("ConfirmDenyingThisOrder"),"confirm_refuse", '', 0, 1);
			if ($ret == 'html') print '<br>';
		}
		/*
		 * Confirmation de l'annulation
		 */
		if ($_GET['action']	== 'cancel')
		{
			$ret=$html->form_confirm("fiche.php?id=$commande->id",$langs->trans("Cancel"),$langs->trans("ConfirmCancelThisOrder"),"confirm_cancel", '', 0, 1);
			if ($ret == 'html') print '<br>';
		}

		/*
		 * Confirmation de l'envoi de la commande
		 */
		if ($_GET["action"]	== 'commande')
		{
			$date_com = dol_mktime(0,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);
			$ret=$html->form_confirm("fiche.php?id=".$commande->id."&amp;datecommande=".$date_com."&amp;methode=".$_POST["methodecommande"]."&amp;comment=".urlencode($_POST["comment"]),
			$langs->trans("MakeOrder"),$langs->trans("ConfirmMakeOrder",dol_print_date($date_com,'day')),"confirm_commande");
			if ($ret == 'html') print '<br>';
		}

		/*
		 * Confirmation de la suppression d'une ligne produit
		 */
		if ($_GET['action'] == 'delete_product_line')
		{
			$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$commande->id.'&lineid='.$_GET["lineid"], $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteproductline','',0,2);
			if ($ret == 'html') print '<br>';
		}

		/*
		 *	Commande
		 */
		$nbrow=8;
		if ($conf->projet->enabled)	$nbrow++;
		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td colspan="2">';
		print $html->showrefnav($commande,'ref','',1,'ref','ref');
		print '</td>';
		print '</tr>';

		// Fournisseur
		print '<tr><td>'.$langs->trans("Supplier")."</td>";
		print '<td colspan="2">'.$soc->getNomUrl(1,'supplier').'</td>';
		print '</tr>';

		// Statut
		print '<tr>';
		print '<td>'.$langs->trans("Status").'</td>';
		print '<td colspan="2">';
		print $commande->getLibStatut(4);
		print "</td></tr>";

		// Date
		if ($commande->methode_commande_id > 0)
		{
			print '<tr><td>'.$langs->trans("Date").'</td><td colspan="2">';
			if ($commande->date_commande)
			{
				print dol_print_date($commande->date_commande,"dayhourtext")."\n";
			}
			print "</td></tr>";

			if ($commande->methode_commande)
			{
				print '<tr><td>'.$langs->trans("Method").'</td><td colspan="2">'.$commande->methode_commande.'</td></tr>';
			}
		}

		// Auteur
		print '<tr><td>'.$langs->trans("AuthorRequest").'</td>';
		print '<td colspan="2">'.$author->getNomUrl(1).'</td>';
		print '</tr>';

		// Projet
		if ($conf->projet->enabled)
		{
			$langs->load('projects');
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('Project');
			print '</td>';
			if ($_GET['action'] != 'classer' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=classer&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetProject')).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($_GET['action'] == 'classer')
			{
				$html->form_project($_SERVER['PHP_SELF'].'?id='.$commande->id, $commande->socid, $commande->projet_id, 'projetid');
			}
			else
			{
				$html->form_project($_SERVER['PHP_SELF'].'?id='.$commande->id, $commande->socid, $commande->projet_id, 'none');
			}
			print '</td>';
			print '</tr>';
		}

		// Ligne de	3 colonnes
		print '<tr><td>'.$langs->trans("AmountHT").'</td>';
		print '<td align="right"><b>'.price($commande->total_ht).'</b></td>';
		print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

		print '<tr><td>'.$langs->trans("AmountVAT").'</td><td align="right">'.price($commande->total_tva).'</td>';
		print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

		print '<tr><td>'.$langs->trans("AmountTTC").'</td><td align="right">'.price($commande->total_ttc).'</td>';
		print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

		print "</table>";

		if ($mesg) print $mesg;
		else print '<br>';

		/*
		 * Lines
		 */
		print '<table class="noborder" width="100%">';

		$num = sizeof($commande->lignes);
		$i = 0;	$total = 0;

		if ($num)
		{
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans('Label').'</td>';
			print '<td align="right" width="50">'.$langs->trans('VAT').'</td>';
			print '<td align="right" width="80">'.$langs->trans('PriceUHT').'</td>';
			print '<td align="right" width="50">'.$langs->trans('Qty').'</td>';
			print '<td align="right" width="50">'.$langs->trans('ReductionShort').'</td>';
			print '<td align="right" width="50">'.$langs->trans('AmountHT').'</td>';
			print '<td width="48" colspan="3">&nbsp;</td>';
			print "</tr>\n";
		}
		$var=true;
		while ($i <	$num)
		{
			$commandline =	$commande->lignes[$i];
			$var=!$var;

			// Show product and description
			$type=$commandline->product_type?$commandline->product_type:$commandline->fk_product_type;
			// Try to enhance type detection using date_start and date_end for free lines where type
			// was not saved.
			if (! empty($commandline->date_start)) $type=1;
			if (! empty($commandline->date_end)) $type=1;

			// Ligne en mode visu
			if ($_GET['action'] != 'editline' || $_GET['rowid'] != $commandline->id)
			{
				print '<tr '.$bc[$var].'>';

				// Show product and description
				print '<td>';
				if ($commandline->fk_product > 0)
				{
					print '<a name="'.$commandline->id.'"></a>'; // ancre pour retourner sur la ligne

					$product_static=new ProductFournisseur($db);
					$product_static->fetch($commandline->fk_product);
					$text=$product_static->getNomUrl(1);
					$text.= ' - '.$product_static->libelle;
					$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($commandline->description));
					print $html->textwithtooltip($text,$description,3,'','',$i);

					// Show range
					print_date_range($commandline->date_start,$commandline->date_end);

					// Add description in form
					if ($conf->global->PRODUIT_DESC_IN_FORM) print ($objp->description && $objp->description!=$objp->product)?'<br>'.dol_htmlentitiesbr($objp->description):'';
				}

				// Description - Editor wysiwyg
				if (! $commandline->fk_product)
				{
					if ($type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');
					print $text.' '.nl2br($commandline->description);

					// Show range
					print_date_range($commandline->date_start,$commandline->date_end);
				}

				print '</td>';

				print '<td align="right" nowrap="nowrap">'.vatrate($commandline->tva_tx).'%</td>';

				print '<td align="right" nowrap="nowrap">'.price($commandline->subprice)."</td>\n";

				print '<td align="right" nowrap="nowrap">'.$commandline->qty.'</td>';

				if ($commandline->remise_percent >	0)
				{
					print '<td align="right" nowrap="nowrap">'.dol_print_reduction($commandline->remise_percent,$langs)."</td>\n";
				}
				else
				{
					print '<td>&nbsp;</td>';
				}

				print '<td align="right" nowrap="nowrap">'.price($commandline->total_ht).'</td>';
				if ($commande->statut == 0	&& $user->rights->fournisseur->commande->creer)
				{
					print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;action=editline&amp;rowid='.$commandline->id.'#'.$commandline->id.'">';
					print img_edit();
					print '</a></td>';

					$actiondelete='delete_product_line';
					print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;action='.$actiondelete.'&amp;lineid='.$commandline->id.'">';
					print img_delete();
					print '</a></td>';
				}
				else
				{
					print '<td>&nbsp;</td><td>&nbsp;</td>';
				}
				print "</tr>";
			}

			// Ligne en mode update
			if ($_GET["action"]	== 'editline' && $user->rights->fournisseur->commande->creer && ($_GET["rowid"] == $commandline->id))
			{
				print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;etat=1&amp;ligne_id='.$commandline->id.'" method="post">';
				print '<input type="hidden" name="action" value="updateligne">';
				print '<input type="hidden" name="id" value="'.$_REQUEST["id"].'">';
				print '<input type="hidden" name="elrowid" value="'.$_GET['rowid'].'">';
				print '<tr '.$bc[$var].'>';
				print '<td>';
				print '<a name="'.$commandline->id.'"></a>'; // ancre pour retourner sur la ligne
				if ($conf->produit->enabled && $commandline->fk_product > 0)
				{
					print '<a href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$commandline->product_id.'">';
					print img_object($langs->trans('ShowProduct'),'product');
					print ' '.$commandline->ref_fourn.'</a>';
					print ' ('.$commandline->ref.')';
					print ' - '.nl2br($commandline->product);
					print '<br>';
				}
				else
				{
					print $html->select_type_of_lines($commandline->product_type,'type',1);
					if ($conf->produit->enabled && $conf->service->enabled) print '<br>';
				}

				// Description - Editor wysiwyg
				if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
				{
					require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
					$doleditor=new DolEditor('eldesc',$commandline->description,200,'dolibarr_details');
					$doleditor->Create();
				}
				else
				{
					print '<textarea name="eldesc" class="flat" cols="70" rows="'.ROWS_2.'">'.dol_htmlentitiesbr_decode($commandline->description).'</textarea>';
				}
				print '</td>';
				print '<td>';
				$html->select_tva('tva_tx',$commandline->tva_tx);
				print '</td>';
				print '<td align="right"><input	size="5" type="text" name="pu"	value="'.price($commandline->subprice).'"></td>';
				print '<td align="right"><input size="2" type="text" name="qty" value="'.$commandline->qty.'"></td>';
				print '<td align="right" nowrap="nowrap"><input size="1" type="text" name="remise_percent" value="'.$commandline->remise_percent.'">%</td>';
				print '<td align="center" colspan="3"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
				print '<br /><input	type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></td>';
				print '</tr>' .	"\n";
				print "</form>\n";
			}
			$i++;
		}

		/*
		 * Form to add new line
		 */
		if ($commande->statut == 0 && $user->rights->fournisseur->commande->creer && $_GET["action"] <> 'editline')
		{
			print '<tr class="liste_titre">';
			print '<td>';
			print '<a name="add"></a>'; // ancre
			print $langs->trans('AddNewLine').' - '.$langs->trans("FreeZone").'</td>';
			print '<td align="right">'.$langs->trans('VAT').'</td>';
			print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
			print '<td align="right">'.$langs->trans('Qty').'</td>';
			print '<td align="right">'.$langs->trans('ReductionShort').'</td>';
			print '<td colspan="4">&nbsp;</td>';
			print '</tr>';

			// Add free products/services form
			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'#add" method="post">';
			print '<input type="hidden"	name="action" value="addligne">';
			print '<input type="hidden"	name="id" value="'.$commande->id.'">';

			$var=true;
			print '<tr '.$bc[$var].'>';
			print '<td>';

			print $html->select_type_of_lines(-1,'type',1);
			if ($conf->produit->enabled && $conf->service->enabled) print '<br>';

			// Editor wysiwyg
			if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
			{
				require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
				$doleditor=new DolEditor('dp_desc','',100,'dolibarr_details');
				$doleditor->Create();
			}
			else
			{
				print '<textarea class="flat" cols="70" name="dp_desc" rows="'.ROWS_2.'"></textarea>';
			}
			print '</td>';
			print '<td align="center">';
			//if($soc->tva_assuj == "0")
			//print '<input type="hidden" name="tva_tx" value="0">0';
			//else
			print $html->select_tva('tva_tx',$conf->defaulttx,$soc,$mysoc);
			print '</td>';
			print '<td align="right"><input type="text" name="pu" size="5"></td>';
			print '<td align="right"><input type="text" name="qty" value="1" size="2"></td>';
			print '<td align="right" nowrap="nowrap"><input type="text" name="remise_percent" size="1" value="'.$soc->remise_client.'">%</td>';
			print '<td align="center" colspan="4"><input type="submit" class="button" value="'.$langs->trans('Add').'"></td>';
			print '</tr>';

			print '</form>';

			// Ajout de produits/services predefinis
			if ($conf->produit->enabled)
			{
				print '<tr class="liste_titre">';
				print '<td colspan="3">';
				print $langs->trans("AddNewLine").' - ';
				if ($conf->service->enabled)
				{
					print $langs->trans('RecordedProductsAndServices');
				}
				else
				{
					print $langs->trans('RecordedProducts');
				}
				print '</td>';
				print '<td align="right">'.$langs->trans('Qty').'</td>';
				print '<td align="right">'.$langs->trans('ReductionShort').'</td>';
				print '<td colspan="4">&nbsp;</td>';
				print '</tr>';

				print '<form id="addpredefinedproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'#add" method="post">';
				print '<input type="hidden" name="action" value="addligne">';
				print '<input type="hidden" name="id" value="'.$commande->id.'">';

				$var=!$var;
				print '<tr '.$bc[$var].'>';
				print '<td colspan="3">';
				$html->select_produits_fournisseurs($commande->fourn_id,'','idprodfournprice','',$filtre);

				if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) print '<br>';

				// Editor wysiwyg
				if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
				{
					require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
					$doleditor=new DolEditor('np_desc','',100,'dolibarr_details');
					$doleditor->Create();
				}
				else
				{
					print '<textarea cols="70" name="np_desc" rows="'.ROWS_2.'" class="flat"></textarea>';
				}

				print '</td>';
				print '<td align="right"><input type="text" size="2" name="qty" value="1"></td>';
				print '<td align="right" nowrap="nowrap"><input type="text" size="1" name="remise_percent" value="0">%</td>';
				print '<td align="center" colspan="4"><input type="submit" class="button" value="'.$langs->trans('Add').'"></td>';
				print '</tr>';

				print '</form>';
			}
		}
		print '</table>';
		print '</div>';


		/**
		 * Boutons actions
		 */
		if ($user->societe_id == 0 && $_GET['action'] != 'editline' && $_GET['action'] != 'delete')
		{
			print '<div	class="tabsAction">';

			if ($commande->statut == 0 && $num > 0)
			{
				if ($user->rights->fournisseur->commande->valider)
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;action=valid"';
					print '>'.$langs->trans('Validate').'</a>';
				}
			}

			if ($commande->statut == 1)
			{
				if ($user->rights->fournisseur->commande->approuver)
				{
					print '<a class="butAction"	href="fiche.php?id='.$commande->id.'&amp;action=approve">'.$langs->trans("ApproveOrder").'</a>';

					print '<a class="butAction"	href="fiche.php?id='.$commande->id.'&amp;action=refuse">'.$langs->trans("RefuseOrder").'</a>';
				}

				if ($user->rights->fournisseur->commande->annuler)
				{
					print '<a class="butActionDelete" href="fiche.php?id='.$commande->id.'&amp;action=cancel">'.$langs->trans("CancelOrder").'</a>';
				}

			}

			if ($commande->statut == 2)
			{
				if ($user->rights->fournisseur->commande->annuler)
				{
					print '<a class="butActionDelete" href="fiche.php?id='.$commande->id.'&amp;action=cancel">'.$langs->trans("CancelOrder").'</a>';
				}
			}

			if ($user->rights->fournisseur->commande->annuler)
			{
				print '<a class="butActionDelete" href="fiche.php?id='.$commande->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
			}

			print "</div>";
		}

		print '<table width="100%"><tr><td width="50%" valign="top">';
		print '<a name="builddoc"></a>'; // ancre


		/*
		 * Documents	generes
		 */
		$comfournref = dol_sanitizeFileName($commande->ref);
		$file =	$conf->fournisseur->commande->dir_output . '/' . $comfournref .	'/'	. $comfournref . '.pdf';
		$relativepath =	$comfournref.'/'.$comfournref.'.pdf';
		$filedir = $conf->fournisseur->commande->dir_output	. '/' .	$comfournref;
		$urlsource=$_SERVER["PHP_SELF"]."?id=".$commande->id;
		$genallowed=$user->rights->fournisseur->commande->creer;
		$delallowed=$user->rights->fournisseur->commande->supprimer;

		$somethingshown=$formfile->show_documents('commande_fournisseur',$comfournref,$filedir,$urlsource,$genallowed,$delallowed,$commande->modelpdf);


		print '</td><td	width="50%"	valign="top">';


		if ( $user->rights->fournisseur->commande->commander && ($commande->statut == 2 || $commande->statut == 6))
		{
			/**
			 * Commander (action=commande)
			 */
			print '<br>';
			print '<form name="commande" action="fiche.php?id='.$commande->id.'&amp;action=commande" method="post">';
			print '<input type="hidden"	name="action" value="commande">';
			print '<table class="border" width="100%">';
			print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("ToOrder").'</td></tr>';
			print '<tr><td>'.$langs->trans("OrderDate").'</td><td>';
			$date_com = dol_mktime(0,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);
			print $html->select_date($date_com,'','','','',"commande");
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("OrderMode").'</td><td>';
			$formorder->select_methodes_commande($_POST["methodecommande"],"methodecommande",1);
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("Comment").'</td><td><input size="30" type="text" name="comment" value="'.$_POST["comment"].'"></td></tr>';
			print '<tr><td align="center" colspan="2"><input type="submit" class="button" name="'.$langs->trans("Activate").'"></td></tr>';
			print '</table>';
			print '</form>';
		}

		if ( $user->rights->fournisseur->commande->receptionner	&& ($commande->statut == 3 ||$commande->statut == 4	))
		{
			/**
			 * Receptionner (action=livraison)
			 */
			print '<br>';
			print '<form action="fiche.php?id='.$commande->id.'" method="post">';
			print '<input type="hidden"	name="action" value="livraison">';
			print '<table class="border" width="100%">';
			print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Receive").'</td></tr>';
			print '<tr><td>'.$langs->trans("DeliveryDate").'</td><td>';
			print $html->select_date('','','','','',"commande");
			print "</td></tr>\n";

			print "<tr><td>".$langs->trans("Delivery")."</td><td>\n";
			$liv = array();
			$liv[''] = '&nbsp;';
			$liv['tot']	= $langs->trans("TotalWoman");
			$liv['par']	= $langs->trans("PartialWoman");
			$liv['nev']	= $langs->trans("NeverReceived");
			$liv['can']	= $langs->trans("Canceled");

			print $html->select_array("type",$liv);

			print '</td></tr>';
			print '<tr><td>'.$langs->trans("Comment").'</td><td><input size="30" type="text" name="comment"></td></tr>';
			print '<tr><td align="center" colspan="2"><input type="submit" class="button" name="'.$langs->trans("Activate").'"></td></tr>';
			print "</table>\n";
			print "</form>\n";
		}
		print '</td></tr></table>';
	}
	else
	{
		// Commande	non	trouvee
		dol_print_error($db);
	}
}

$db->close();

llxFooter('$Date$	- $Revision$');
?>
