<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005	   Eric	Seigne <eric.seigne@ryxeo.com>
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
 *
 * $Id$
 * $Source$
 */

/**
   \file		htdocs/fourn/commande/fiche.php
   \ingroup		commande
   \brief		Fiche commande
   \version		$Revision$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/commande/modules/modules_commandefournisseur.php');
require_once DOL_DOCUMENT_ROOT."/fourn/fournisseur.product.class.php";
if ($conf->projet->enabled)	require_once(DOL_DOCUMENT_ROOT.'/project.class.php');

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('propal');
$langs->load('deliveries');
$langs->load('products');
$langs->load('stocks');

$user->getrights("fournisseur");

if (!$user->rights->fournisseur->commande->lire) accessforbidden();

$comclientid = isset($_GET["comid"])?$_GET["comid"]:'';

// Sécurité	accés client
$socid=0;
if ($user->societe_id >	0)
{
  $action	= '';
  $socid = $user->societe_id;
}

// Récupération	de l'id	de projet
$projetid =	0;
if ($_GET["projetid"])
{
  $projetid =	$_GET["projetid"];
}

$mesg='';


/*
 * Actions
 */

// Categorisation dans projet
if ($_POST['action'] ==	'classin' && $user->rights->fournisseur->commande->creer)
{
  $commande	= new CommandeFournisseur($db);
  $commande->fetch($_GET["id"]);
  $commande->classin($_POST["projetid"]);
}

if ($_REQUEST['action'] ==	'setremisepercent' && $user->rights->fournisseur->commande->creer)
{
  $commande = new CommandeFournisseur($db);
  $commande->fetch($_REQUEST['id']);
  $result = $commande->set_remise($user, $_POST['remise_percent']);
  $_GET['id']=$_REQUEST['id'];
}

/*
 *	Ajout d'une	ligne produit dans la commande
 */
if ($_POST['action'] ==	'addligne' && $user->rights->fournisseur->commande->creer)
{
	if ($_POST['qty'] && (($_POST['pu'] && $_POST['desc']) || $_POST['idprodfournprice']))
  {
  	$commande =	new	CommandeFournisseur($db);
    $ret=$commande->fetch($_POST["id"]);

    $soc = new Societe($db,	$commande->socid);
    $soc->fetch($commande->socid);
    if ($ret < 0)
    {
    	dolibarr_print_error($db,$commande->error);
    	exit;
    }
    
    // Ecrase $pu par celui	du produit
    // Ecrase $desc	par	celui du produit
    // Ecrase $txtva  par celui du produit
    if ($_POST["idprodfournprice"] > 0)
    {
    	$prodfournprice = new ProductFournisseur($db);
    	$prodfournprice->fetch_product_fournisseur_price($_POST["idprodfournprice"]);
    	
    	$prod =	new	Product($db, $prodfournprice->product_id);
    	$prod->fetch($prodfournprice->product_id);
    	
    	$libelle = $prod->libelle;
    	
    	// La description de la ligne est celle saisie ou
	    // celle du	produit	si (non	saisi +	PRODUIT_CHANGE_PROD_DESC défini)
	    // \todo Ne	faut-il	pas	rendre $conf->global->PRODUIT_CHANGE_PROD_DESC toujours	a on
	    $desc=$_POST['np_desc'];
	    if (! $desc	&& $conf->global->PRODUIT_CHANGE_PROD_DESC)
	    {
	      $desc =	$prod->description;
	    }

	    $tva_tx	= get_default_tva($soc,$mysoc,$prod->tva_tx);
	  }
	  else
	  {
	  	$pu=$_POST['pu'];
	    $tva_tx=$_POST['tva_tx'];
	    $desc=$_POST['desc'];
	  }

    $result=$commande->addline(
				 $desc,
				 $pu,
				 $_POST['qty'],
				 $tva_tx,
				 $prodfournprice->product_id,
				 $_POST['idprodfournprice'],
				 $_POST['remise_percent'],
				 'HT'
				 );

    if ($result > 0)
	  {
	    if ($_REQUEST['lang_id'])
	    {
	      $outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
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
 *	Mise à jour	d'une ligne	dans la	propale
 */
if ($_POST['action'] ==	'updateligne' && $user->rights->fournisseur->commande->creer &&	$_POST['save'] == $langs->trans('Save'))
{
  $commande =	new	CommandeFournisseur($db,"",$_POST["id"]);
  if ($commande->fetch($_POST['id']) < 0) dolibarr_print_error($db);

  $result	= $commande->updateline($_POST['elrowid'],
					$_POST['desc'],
					$_POST['pu'],
					$_POST['qty'],
					$_POST['remise_percent'],
					$_POST['tva_tx']
					);

  if ($result	>= 0)
    {
      if ($_REQUEST['lang_id'])
	{
	  $outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
	  $outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
      supplier_order_pdf_create($db, $commande->id,	$commande->modelpdf, $outputlangs);
    }
  else
    {
      dolibarr_print_error($db,$commande->error);
      exit;
    }

  $_GET['id']=$_POST['id'];	// Pour	réaffichage	de la fiche	en cours d'édition
}

if ($_POST['action'] ==	'updateligne' && $user->rights->fournisseur->commande->creer &&	$_POST['cancel'] ==	$langs->trans('Cancel'))
{
  Header('Location: fiche.php?id='.$_POST['id']);	  // Pour réaffichage de la	fiche en cours d'édition
  exit;
}

if ($_POST['action'] == 'confirm_deleteproductline' && $_POST['confirm'] == 'yes' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
{
	if ($user->rights->fournisseur->commande->creer)
	{
		$commande = new CommandeFournisseur($db);
		$commande->fetch($_GET['id']);
    $result = $commande->delete_line($_GET['lineid']);
    if ($_REQUEST['lang_id'])
    {
      $outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
      $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    supplier_order_pdf_create($db, $_GET['id'], $commande->modelpdf, $outputlangs);
  }
  Header('Location: fiche.php?id='.$_GET['id']);
  exit;
}

if ($_POST['action'] ==	'confirm_valid'	&& $_POST['confirm'] ==	'yes' && $user->rights->fournisseur->commande->valider)
{
  $commande =	new	CommandeFournisseur($db);
  $commande->fetch($_GET['id']);
  $soc = new Societe($db);
  $soc->fetch($commande->socid);
  $result = $commande->valid($user);
  if ($result	>= 0)
    {
      supplier_order_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
      Header("Location: fiche.php?id=".$_GET["id"]);
      exit;
    }
}

if ($_POST['action'] ==	'confirm_approve' && $_POST["confirm"] == 'yes'	&& $user->rights->fournisseur->commande->approuver)
{
  $commande =	new	CommandeFournisseur($db);
  $commande->fetch($_GET['id']);
  $result	= $commande->approve($user);
  if ($result	>= 0)
    {
      Header("Location: fiche.php?id=".$_GET["id"]);
      exit;
    }
}

if ($_POST['action'] ==	'confirm_refuse' &&	$_POST['confirm'] == 'yes' && $user->rights->fournisseur->commande->approuver)
{
  $commande = new CommandeFournisseur($db);
  $commande->fetch($_GET['id']);
  $result = $commande->refuse($user);
  if ($result	>= 0)
    {
      Header("Location: fiche.php?id=".$_GET["id"]);
      exit;
    }
}

if ($_POST['action'] ==	'confirm_commande' && $_POST['confirm']	== 'yes' &&	$user->rights->fournisseur->commande->commander)
{
  $commande =	new	CommandeFournisseur($db);
  $commande->fetch($_GET["id"]);
  $result	= $commande->commande($user, $_GET["datecommande"],	$_GET["methode"]);
  Header("Location: fiche.php?id=".$_GET["id"]);
  exit;
}


if ($_POST['action'] ==	'confirm_delete' && $_POST['confirm'] == 'yes' && $user->rights->fournisseur->commande->creer)
{
  $commande = new CommandeFournisseur($db);
  $commande->id = $_GET['id'];
  $commande->delete();
  Header('Location: index.php');
  exit;
}

if ($_POST["action"] ==	'livraison'	&& $user->rights->fournisseur->commande->receptionner)
{
  $commande =	new	CommandeFournisseur($db);
  $commande->fetch($_GET["id"]);

  $date_liv =	mktime(0,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);

  $result	= $commande->Livraison($user, $date_liv, $_POST["type"]);
  Header("Location: fiche.php?id=".$_GET["id"]);
  exit;
}


if ($_POST["action"] ==	'confirm_cancel' &&	$_POST["confirm"] == yes &&	$user->rights->fournisseur->commande->annuler)
{
  $commande =	new	CommandeFournisseur($db);
  $commande->fetch($_GET["id"]);
  $result	= $commande->cancel($user);
  Header("Location: fiche.php?id=".$_GET["id"]);
  exit;
}

/*
 * Ordonnancement des lignes
 */

if ($_GET['action']	== 'up'	&& $user->rights->fournisseur->commande->creer)
{
  $commande =	new	CommandeFournisseur($db,'',$_GET['id']);
  $commande->fetch($_GET['id']);
  $commande->line_up($_GET['rowid']);
  if ($_REQUEST['lang_id'])
    {
      $outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
      $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
  supplier_order_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
  Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'#'.$_GET['rowid']);
  exit;
}

if ($_GET['action']	== 'down' && $user->rights->fournisseur->commande->creer)
{
  $commande =	new	CommandeFournisseur($db,'',$_GET['id']);
  $commande->fetch($_GET['id']);
  $commande->line_down($_GET['rowid']);
  if ($_REQUEST['lang_id'])
    {
      $outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
      $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
  supplier_order_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
  Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'#'.$_GET['rowid']);
  exit;
}


if ($_REQUEST['action']	== 'builddoc')	// En get ou en	post
{
  /*
   * Generation de la	commande
   * définit dans	/includes/modules/commande/modules_commande.php
   */

  // Sauvegarde le dernier modèle	choisi pour	générer	un document
  $commande =	new	CommandeFournisseur($db, 0, $_REQUEST['id']);
  $commande->fetch($_REQUEST['id']);
  if ($_REQUEST['model'])
    {
      $commande->set_pdf_model($user,	$_REQUEST['model']);
    }

  if ($_REQUEST['lang_id'])
    {
      $outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
      $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
  $result=supplier_order_pdf_create($db, $commande->id,$commande->modelpdf,$outputlangs);
  if ($result	<= 0)
    {
      dolibarr_print_error($db,$result);
      exit;
    }
  else
    {
      Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$commande->id.'#builddoc');
      exit;
    }
}

// Efface les fichiers
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
 * Créé	une	commande
 */
if ($_GET["action"]	== 'create')
{

  $fourn = new Fournisseur($db);
  $fourn->fetch($_GET["socid"]);
  $commande->modelpdf			 = 'muscadet'; //test

  if ($fourn->create_commande($user) > 0)
    {
      $idc = $fourn->single_open_commande;

      if ($comclientid !=	'')
	{
	  $fourn->updateFromCommandeClient($user,$idc,$comclientid);
	}

      Header("Location:fiche.php?id=".$idc);
      exit;
    }
  else
    {
      $mesg=$fourn->error;
    }
}


llxHeader('',$langs->trans("OrderCard"),"CommandeFournisseur");


$html =	new	Form($db);

/*********************************************************************
 *
 * Mode	creation
 *
 *********************************************************************/
if ($_GET['action']	== 'create'	&& $user->rights->fournisseur->commande->creer)
{
  // Gerer par autre page
}
else
{
	
  /* *************************************************************************** */
  /*																			   */
  /* Mode vue et edition														   */
  /*																			   */
  /* *************************************************************************** */
	
  $id = $_GET['id'];
  if ($id > 0)
    {
      //if ($mesg) print $mesg.'<br>';
	
      $commande =	new	CommandeFournisseur($db);
      if ( $commande->fetch($_GET['id']) >= 0)
	{
	  $soc = new Societe($db);
	  $soc->fetch($commande->socid);

	  $author	= new User($db);
	  $author->id	= $commande->user_author_id;
	  $author->fetch();

	  $h = 0;
	  $head[$h][0] = DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$commande->id;
	  $head[$h][1] = $langs->trans("OrderCard");
	  $a = $h;
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT.'/fourn/commande/dispatch.php?id='.$commande->id;
	  $head[$h][1] = $langs->trans("OrderDispatch");
	  $h++;
	
	  $head[$h][0] = DOL_URL_ROOT.'/fourn/commande/note.php?id='.$commande->id;
	  $head[$h][1] = $langs->trans("Note");
	  $h++;
	
	  $head[$h][0] = DOL_URL_ROOT.'/fourn/commande/history.php?id='.$commande->id;
	  $head[$h][1] = $langs->trans("OrderFollow");
	  $h++;
	
	
	  $title=$langs->trans("SupplierOrder");
	  dolibarr_fiche_head($head, $a, $title);
	
	  /*
	   * Confirmation de la suppression de	la commande
	   */
	  if ($_GET['action']	== 'delete')
	    {
	      $html->form_confirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('DeleteOrder'), $langs->trans('ConfirmDeleteOrder'),	'confirm_delete');
	      print '<br />';
	    }

	  /*
	   * Confirmation de la validation
	   */
	  if ($_GET['action']	== 'valid')
	    {
	      // on vérifie si la	commande est en numérotation provisoire
	      $ref = substr($commande->ref, 1, 4);
	      if ($ref ==	'PROV')
		{
		  $newref	= $commande->getNextNumRef($soc);
		}
	      else
		{
		  $newref	= $commande->ref;
		}
		
	      $text=$langs->trans('ConfirmValidateOrder',$newref);
	      if ($conf->notification->enabled)
		{
		  require_once(DOL_DOCUMENT_ROOT ."/notify.class.php");
		  $notify=new	Notify($db);
		  $text.='<br>';
		  $text.=$notify->confirmMessage(3,$commande->socid);
		}
	
	      $html->form_confirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('ValidateOrder'), $text,	'confirm_valid');
	      print '<br />';
	    }
	  /*
	   * Confirmation de l'approbation
	   *
	   */
	  if ($_GET['action']	== 'approve')
	    {
	      $html->form_confirm("fiche.php?id=$commande->id","Approuver	la commande","Etes-vous	sûr	de vouloir approuver cette commande	?","confirm_approve");
	      print '<br />';
	    }
	  /*
	   * Confirmation de la desapprobation
	   *
	   */
	  if ($_GET['action']	== 'refuse')
	    {
	      $html->form_confirm("fiche.php?id=$commande->id","Refuser la commande","Etes-vous sûr de vouloir refuser cette commande	?","confirm_refuse");
	      print '<br />';
	    }
	  /*
	   * Confirmation de l'annulation
	   */
	  if ($_GET['action']	== 'cancel')
	    {
	      $html->form_confirm("fiche.php?id=$commande->id",$langs->trans("Cancel"),"Etes-vous	sûr	de vouloir annuler cette commande ?","confirm_cancel");
	      print '<br />';
	    }
	
	  /*
	   * Confirmation de l'envoi de la	commande
	   */
	  if ($_GET["action"]	== 'commande')
	    {
	      $date_com = dolibarr_mktime(0,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);
	      $html->form_confirm("fiche.php?id=".$commande->id."&amp;datecommande=".$date_com."&amp;methode=".$_POST["methodecommande"],
				  $langs->trans("MakeOrder"),$langs->trans("ConfirmMakeOrder",dolibarr_print_date($date_com,'day')),"confirm_commande");
	      print '<br />';
	    }
	    
	  /*
     * Confirmation de la suppression d'une ligne produit
     */
    if ($_GET['action'] == 'delete_product_line' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
    {
      $html->form_confirm($_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;lineid='.$_GET["lineid"], $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteproductline');
      print '<br>';
    }
	
	  /*
	   *	Commande
	   */
	  $nbrow=8;
	  if ($conf->projet->enabled)	$nbrow++;
	  print '<table class="border" width="100%">';
	
	  // Ref
	  print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
	  print '<td colspan="5">'.$commande->ref.'</td>';
	  print '</tr>';
	
	  // Fournisseur
	  print '<tr><td width="20%">'.$langs->trans("Supplier")."</td>";
	  print '<td colspan="5">';
	  print '<b><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$soc->id.'">';
	  print img_object($langs->trans("ShowSupplier"),'company').'	'.$soc->nom.'</a></b></td>';
	  print '</tr>';
	
	  // Statut
	  print '<tr>';
	  print '<td>'.$langs->trans("Status").'</td>';
	  print '<td width="50%" colspan="5">';
	  print $commande->getLibStatut(4);
	  print "</td></tr>";

	  if ($commande->methode_commande_id > 0)
	    {
	      print '<tr><td>'.$langs->trans("Date").'</td><td colspan="2">';
	
	      if ($commande->date_commande)
		{
		  print dolibarr_print_date($commande->date_commande,"dayhour")."\n";
		}
	
	      print '</td><td	width="50%"	colspan="3">';
	      if ($commande->methode_commande)
		{
		  print "Méthode : " .$commande->methode_commande;
		}
	      print "</td></tr>";
	    }
	
	  // Auteur
	  print '<tr><td>'.$langs->trans("AuthorRequest").'</td>';
	  print '<td colspan="2">'.$author->getNomUrl(1).'</td>';
	  print '<td colspan="3" width="50%">';
	  print "&nbsp;</td></tr>";
	
	  // Ligne de	3 colonnes
	  print '<tr><td>'.$langs->trans("AmountHT").'</td>';
	  print '<td align="right"><b>'.price($commande->total_ht).'</b></td>';
	  print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td><td	colspan="3">&nbsp;</td></tr>';
	  print '<tr><td>'.$langs->trans("AmountVAT").'</td><td align="right">'.price($commande->total_tva).'</td>';
	  print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td><td	colspan="3">&nbsp;</td></tr>';
	
	  print '<tr><td>'.$langs->trans("AmountTTC").'</td><td align="right">'.price($commande->total_ttc).'</td>';
	  print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td><td	colspan="3">&nbsp;</td></tr>';
	
	  print "</table>";
	
	  if ($mesg) print $mesg;
	  else print '<br>';
	
	  /*
	   * Lignes de	commandes
	   */
	  print '<table class="noborder" width="100%">';
	
	  $sql = "SELECT l.ref as ref_fourn, l.fk_prod_fourn_price,	l.description, l.price,	l.qty";
	  $sql.= ", l.rowid, l.tva_tx, l.remise_percent, l.subprice";
	  $sql.= ", p.rowid as product_id, p.label, p.ref";
	  $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet	as l";
	  $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_fournisseur_price as pfp ON l.fk_prod_fourn_price = pfp.rowid';
	  $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_fournisseur as pf ON pfp.fk_product_fournisseur = pf.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pf.fk_product = p.rowid';
	  $sql.= " WHERE l.fk_commande = ".$commande->id;
	  $sql.= " ORDER BY l.rowid";

	  $resql = $db->query($sql);
	  if ($resql)
	    {
	      $num = $db->num_rows($resql);
	      $i = 0;	$total = 0;
	
	      if ($num)
		{
		  print '<tr class="liste_titre">';
		  print '<td>'.$langs->trans("Description").'</td>';
		  print '<td align="center">'.$langs->trans("VAT").'</td>';
		  print '<td align="center">'.$langs->trans("Qty").'</td>';
		  print '<td align="right">'.$langs->trans("ReductionShort").'</td>';
		  print '<td align="right">'.$langs->trans("PriceU").'</td>';
		  print '<td width="18">&nbsp;</td><td width="18">&nbsp;</td>';
		  print "</tr>\n";
		}
	      $var=false;
	      while ($i <	$num)
		{
		  $objp =	$db->fetch_object($resql);
		  print "<tr $bc[$var]>";
		  print '<td>';
		  print '<a href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$objp->product_id.'">'.img_object($langs->trans("ShowProduct"),'product').' '.$objp->ref_fourn.'</a>';
		  print ' ('.$objp->ref.')';
		  print ' - '.$objp->label;
		  if ($objp->description) print '<br>'.nl2br($objp->description);
		  print "</td>";
		  print '<td align="center">'.vatrate($objp->tva_tx).'%</td>';
		  print '<td align="center">'.$objp->qty.'</td>';
		  if ($objp->remise_percent >	0)
		  {
		    print '<td align="right">'.$objp->remise_percent."%</td>\n";
		  }
		  else
		  {
		    print '<td>&nbsp;</td>';
		  }
		  print '<td align="right">'.price($objp->subprice)."</td>\n";
		  if ($commande->statut == 0	&& $user->rights->fournisseur->commande->creer && $_GET["action"] <> 'valid' &&	$_GET["action"]	!= 'editline')
		    {
		      print '<td align="right"><a	href="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;action=editline&amp;rowid='.$objp->rowid.'#'.$objp->rowid.'">';
		      print img_edit();
		      print '</a></td>';
	
		      print '<td align="right"><a	href="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;action=delete_product_line&amp;lineid='.$objp->rowid.'">';
		      print img_delete();
		      print '</a></td>';
		    }
		  else
		    {
		      print '<td>&nbsp;</td><td>&nbsp;</td>';
		    }
		  print "</tr>";
	
		  if ($_GET["action"]	== 'editline' && $_GET["rowid"]	== $objp->rowid)
		    {
		      print "<form action=\"fiche.php?id=$commande->id\" method=\"post\">";
		      print "<tr $bc[$var]>";
		      print '<td><textarea name="desc" cols="60" rows="'.ROWS_2.'">'.$objp->description.'</textarea>';
		      print '<input type="hidden"	name="action" value="updateligne">';
		      print '<input type="hidden"	name="elrowid" value="'.$_GET["rowid"].'">';
		      print '<input type="hidden"	name="id" value="'.$_REQUEST["id"].'">';
		      print '</td>';
		      print '<td>';
		      $html->select_tva('tva_tx',$objp->tva_tx);
		      print '</td>';
		      print '<td align="center"><input size="3" type="text" name="qty" value="'.$objp->qty.'"></td>';
		      print '<td align="right"><input	size="2" type="text" name="remise_percent" value="'.$objp->remise_percent.'">%</td>';
		      print '<td align="right"><input	size="6" type="text" name="pu"	value="'.price($objp->subprice).'"></td>';
		      print '<td align="center" colspan="2"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
		      print '<br /><input	type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></td>';
		      print '</tr>' .	"\n";
		      print "</form>\n";
		    }
		  $i++;
		  $var=!$var;
		}
	      $db->free();
	    }
	  else
	    {
	      dolibarr_print_error($db);
	    }
	
	  /*
	   * Ajouter une ligne
	   */
	  if ($commande->statut == 0 && $user->rights->fournisseur->commande->creer && ($_GET["action"] <> 'valid' ||	$_GET['action']	== 'builddoc'))
	    {
	      print '<form action="fiche.php?id='.$commande->id.'" method="post">';
	      print '<input type="hidden"	name="action" value="addligne">';
	      print '<input type="hidden"	name="id" value="'.$_REQUEST["id"].'">';

	      print '<tr class="liste_titre">';
	      print '<td>'.$langs->trans("Description").'</td>';
	      print '<td align="center">'.$langs->trans("VAT").'</td>';
	      print '<td align="center">'.$langs->trans("Qty").'</td>';
	      print '<td align="right">'.$langs->trans("ReductionShort").'</td>';
	      print '<td align="right">'.$langs->trans("PriceU").'</td>';
	      print '<td>&nbsp;</td><td>&nbsp;</td>'."</tr>\n";
	
	      $var=false;
	      print "<tr $bc[$var]>".'<td colspan="2">';
	      $html->select_produits_fournisseurs($commande->fourn_id,'','idprodfournprice',$filtre);
	      print '</td>';
	      print '<td align="center"><input type="text" size="2" name="qty" value="1"></td>';
	      print '<td align="right"><input	type="text"	size="3" name="remise_percent"	value="0">%</td>';
	      print '<td>&nbsp;</td>';
	      print '<td align="center" colspan="3"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td></tr>';
	      print "</tr>\n";
	
	      print "</form>";
	    }
	
	  print "</table>";
	  print '</div>';
	
	
	  /**
	   * Boutons actions
	   */
	  if ($user->societe_id == 0 && $commande->statut	< 3	&& ($_GET["action"]	<> 'valid' || $_GET['action'] == 'builddoc'))
	    {
	      print '<div	class="tabsAction">';
	
	      if ($commande->statut == 0 && $num > 0)
		{
		  if ($user->rights->fournisseur->commande->valider)
		    {
		      print '<a class="butAction"	href="fiche.php?id='.$commande->id.'&amp;action=valid">'.$langs->trans("Valid").'</a>';
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
	
	      if ($commande->statut == 0)
		{
		  if ($user->rights->fournisseur->commande->creer)
		    {
		      print '<a class="butActionDelete" href="fiche.php?id='.$commande->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
		    }
		}
	
	
	      // Build PDF
	      if ($commande->statut >	0)
		{
		  if ($user->rights->fournisseur->commande->creer)
		    {
		      print '<a class="butAction"	href="fiche.php?id='.$commande->id.'&amp;action=builddoc">'.$langs->trans("BuildPDF").'</a>';
		    }
		}
	      print "</div>";
	    }
	
	  print '<table width="100%"><tr><td width="50%" valign="top">';
	
	  /*
	   * Documents	générés
	   *
	   */
	  $comfournref = sanitize_string($commande->ref);
	  $file =	$conf->fournisseur->commande->dir_output . '/' . $comfournref .	'/'	. $comfournref . '.pdf';
	  $relativepath =	$comfournref.'/'.$comfournref.'.pdf';
	  $filedir = $conf->fournisseur->commande->dir_output	. '/' .	$comfournref;
	  $urlsource=$_SERVER["PHP_SELF"]."?id=".$commande->id;
	  $genallowed=$user->rights->fournisseur->commande->creer;
	  $delallowed=$user->rights->fournisseur->commande->supprimer;
	
	  $somethingshown=$html->show_documents('commande_fournisseur',$comfournref,$filedir,$urlsource,$commande->statut>0?1:0,$delallowed,$commande->modelpdf);
	
	
	  print '</td><td	width="50%"	valign="top">';
	
	  /*
	   *
	   *
	   */
	  if ($_GET["action"]	== 'classer')
	    {
	      print '<form method="post" action="fiche.php?id='.$commande->id.'">';
	      print '<input type="hidden"	name="action" value="classin">';
	      print '<table class="border">';
	      print '<tr><td>'.$langs->trans("Project").'</td><td>';
	
	      $proj =	new	Project($db);
	      $html->select_array("projetid",$proj->liste_array($commande->socid));

	      print "</td></tr>";
	      print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Send").'"></td></tr></table></form>';
	    }
	
	  /*
	   *
	   *
	   */
	  if ( $user->rights->fournisseur->commande->commander &&	$commande->statut == 2)
	    {
	      /**
	       * Commander
	       */
	      $form =	new	Form($db);

	      print '<br>';
	      print '<form name="commande" action="fiche.php?id='.$commande->id.'&amp;action=commande" method="post">';
	      print '<table class="border" width="100%">';
	      print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("ToOrder").'</td></tr>';
	      print '<tr><td>'.$langs->trans("OrderDate").'</td><td>';
	      print $form->select_date('','','','','',"commande");
	      print '</td></tr>';
	
	      print '<tr><td>'.$langs->trans("OrderMode").'</td><td>';
	      $html->select_methodes_commande('',"methodecommande",1);
	      print '</td></tr>';
	
	      print '<tr><td>'.$langs->trans("Comment").'</td><td><input size="30" type="text" name="commentaire"></td></tr>';
	      print '<tr><td align="center" colspan="2"><input type="submit" class="button" name="'.$langs->trans("Activate").'"></td></tr>';
	      print '</table>';
	      print '</form>';
	    }
	
	  /*
	   *
	   *
	   */
	  if ( $user->rights->fournisseur->commande->receptionner	&& ($commande->statut == 3 ||$commande->statut == 4	))
	    {
	      /**
	       * Réceptionner
	       */
	      $form =	new	Form($db);

	      print '<br>';
	      print '<form action="fiche.php?id='.$commande->id.'" method="post">';
	      print '<input type="hidden"	name="action" value="livraison">';
	      print '<table class="border" width="100%">';
	      print '<tr class="liste_titre"><td colspan="2">Réceptionner</td></tr>';
	      print '<tr><td>Date	de livraison</td><td>';
	      print $form->select_date('','','','','',"commande");
	      print "</td></tr>\n";

	      print "<tr><td>Livraison</td><td>\n";
	      $liv = array();
	      $liv['par']	= "Partielle";
	      $liv['tot']	= "Totale";
	
	      print $form->select_array("type",$liv);
	
	
	      print '</td></tr>';
	      print '<tr><td>Commentaire</td><td><input size="30"	type="text"	name="commentaire"></td></tr>';
	      print '<tr><td align="center" colspan="2"><input type="submit" class="button" name="'.$langs->trans("Activate").'"></td></tr>';
	      print "</table>\n";
	      print "</form>\n";
	    }
	  print '</td></tr></table>';
	}
      else
	{
	  // Commande	non	trouvée
	  dolibarr_print_error($db);
	}
    }
}

$db->close();

llxFooter('$Date$	- $Revision$');
?>
