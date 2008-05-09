<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005	     Eric	Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
   \file		htdocs/fourn/commande/fiche.php
   \ingroup		commande
   \brief		Fiche commande
   \version		$Id$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
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

if (!$user->rights->fournisseur->commande->lire) accessforbidden();

$comclientid = isset($_GET["comid"])?$_GET["comid"]:'';
$id = isset($_GET["id"])?$_GET["id"]:$_POST["id"];

// Securite	acces client
$socid=0;
if ($user->societe_id >	0)
{
  $action	= '';
  $socid = $user->societe_id;
}

// Recuperation	de l'id	de projet
$projetid =	0;
if ($_GET["projetid"])
{
  $projetid =	$_GET["projetid"];
}

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
    	dolibarr_print_error($db,$commande->error);
    	exit;
    }

    $soc = new Societe($db,	$commande->socid);
    $result=$soc->fetch($commande->socid);
    //print $result;
	
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
    	
    	$desc = $prod->description;
    	$desc.= $prod->description && $_POST['np_desc'] ? "\n" : "";
		$desc.= $_POST['np_desc'];

    	$tva_tx	= get_default_tva($soc,$mysoc,$prod->tva_tx,$prodfournprice->product_id);
	}
	else
	{
		$pu=$_POST['pu'];
		$tva_tx=$_POST['tva_tx'];
		$desc=$_POST['dp_desc'];
	}
	//print "xx".$tva_tx; exit;
	
    $result=$commande->addline(
				 $desc,
				 $pu,
				 $_POST['qty'],
				 $tva_tx,
				 $prodfournprice->product_id,
				 $_POST['idprodfournprice'],
				 $prodfournprice->fourn_ref,
				 $_POST['remise_percent'],
				 'HT'
				 );

    if ($result > 0)
	  {
	    if ($_REQUEST['lang_id'])
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
  if ($commande->fetch($id) < 0) dolibarr_print_error($db);

  $result	= $commande->updateline($_POST['elrowid'],
					$_POST['eldesc'],
					$_POST['pu'],
					$_POST['qty'],
					$_POST['remise_percent'],
					$_POST['tva_tx']
					);

  if ($result	>= 0)
    {
      if ($_REQUEST['lang_id'])
	{
	  $outputlangs = new Translate("",$conf);
	  $outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
      supplier_order_pdf_create($db, $commande->id,	$commande->modelpdf, $outputlangs);
    }
  else
    {
      dolibarr_print_error($db,$commande->error);
      exit;
    }
}

if ($_POST['action'] ==	'updateligne' && $user->rights->fournisseur->commande->creer &&	$_POST['cancel'] ==	$langs->trans('Cancel'))
{
  Header('Location: fiche.php?id='.$id);	  // Pour reaffichage de la	fiche en cours d'edition
  exit;
}

if ($_POST['action'] == 'confirm_deleteproductline' && $_POST['confirm'] == 'yes' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
{
	if ($user->rights->fournisseur->commande->creer)
	{
		$commande = new CommandeFournisseur($db);
		$commande->fetch($id);
    $result = $commande->delete_line($_GET['lineid']);
    if ($_REQUEST['lang_id'])
    {
      $outputlangs = new Translate("",$conf);
      $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    supplier_order_pdf_create($db, $id, $commande->modelpdf, $outputlangs);
  }
  Header('Location: fiche.php?id='.$id);
  exit;
}

if ($_POST['action'] ==	'confirm_valid'	&& $_POST['confirm'] ==	'yes' && $user->rights->fournisseur->commande->valider)
{
	$commande =	new	CommandeFournisseur($db);
	$commande->fetch($id);

	$result = $commande->valid($user);
	if ($result	>= 0)
	{
		if ($_REQUEST['lang_id'])
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		supplier_order_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
	}
}

if ($_POST['action'] ==	'confirm_approve' && $_POST["confirm"] == 'yes'	&& $user->rights->fournisseur->commande->approuver)
{
  $commande =	new	CommandeFournisseur($db);
  $commande->fetch($id);
  $result	= $commande->approve($user);
}

if ($_POST['action'] ==	'confirm_refuse' &&	$_POST['confirm'] == 'yes' && $user->rights->fournisseur->commande->approuver)
{
  $commande = new CommandeFournisseur($db);
  $commande->fetch($id);
  $result = $commande->refuse($user);
}

if ($_POST['action'] ==	'confirm_commande' && $_POST['confirm']	== 'yes' &&	$user->rights->fournisseur->commande->commander)
{
  $commande =	new	CommandeFournisseur($db);
  $commande->fetch($id);
  $result	= $commande->commande($user, $_GET["datecommande"],	$_GET["methode"]);
}


if ($_POST['action'] ==	'confirm_delete' && $_POST['confirm'] == 'yes' && $user->rights->fournisseur->commande->creer)
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
		$date_liv = dolibarr_mktime(0,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);

		$result	= $commande->Livraison($user, $date_liv, $_POST["type"]);
		if ($result > 0)
		{
			Header("Location: fiche.php?id=".$id);
			exit;
		}
		else
		{
			dolibarr_print_error($db,$commande->error);
			exit;
		}
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Delivery")).'</div>';
	}
}


if ($_POST["action"] ==	'confirm_cancel' &&	$_POST["confirm"] == yes &&	$user->rights->fournisseur->commande->annuler)
{
  $commande =	new	CommandeFournisseur($db);
  $commande->fetch($id);
  $result	= $commande->cancel($user);
  Header("Location: fiche.php?id=".$id);
  exit;
}

/*
 * Ordonnancement des lignes
 */

if ($_GET['action']	== 'up'	&& $user->rights->fournisseur->commande->creer)
{
  $commande =	new	CommandeFournisseur($db,'',$id);
  $commande->fetch($id);
  $commande->line_up($_GET['rowid']);
  if ($_REQUEST['lang_id'])
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
  if ($_REQUEST['lang_id'])
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

  if ($_REQUEST['lang_id'])
  {
    $outputlangs = new Translate("",$conf);
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
 * Cree	une	commande
 */
if ($_GET["action"]	== 'create')
{
	$fourn = new Fournisseur($db);
	$fourn->fetch($_GET["socid"]);

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
		$_GET['action']='edit';
		$db->commit();
	}
	else
	{
		$db->rollback();
		$mesg=$fourn->error;
	}
}


llxHeader('',$langs->trans("OrderCard"),"CommandeFournisseur");


$html =	new	Form($db);
$formfile = new FormFile($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0)
{
	//if ($mesg) print $mesg.'<br>';
	
	$commande =	new	CommandeFournisseur($db);
  if ( $commande->fetch($id) >= 0)
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
	      // on verifie si la	commande est en numerotation provisoire
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
	      $html->form_confirm("fiche.php?id=$commande->id",$langs->trans("ApproveThisOrder"),$langs->trans("ConfirmApproveThisOrder"),"confirm_approve");
	      print '<br />';
	    }
	  /*
	   * Confirmation de la desapprobation
	   *
	   */
	  if ($_GET['action']	== 'refuse')
	    {
	      $html->form_confirm("fiche.php?id=$commande->id",$langs->trans("DenyingThisOrder"),$langs->trans("ConfirmDenyingThisOrder"),"confirm_refuse");
	      print '<br />';
	    }
	  /*
	   * Confirmation de l'annulation
	   */
	  if ($_GET['action']	== 'cancel')
	    {
	      $html->form_confirm("fiche.php?id=$commande->id",$langs->trans("Cancel"),$langs->trans("ConfirmCancelThisOrder"),"confirm_cancel");
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
		  print $langs->trans("Method")." : " .$commande->methode_commande;
		}
	      print "</td></tr>";
	    }
	
	  // Auteur
	  print '<tr><td>'.$langs->trans("AuthorRequest").'</td>';
	  print '<td colspan="2">'.$author->getNomUrl(1).'</td>';
	  print '<td colspan="3" width="50%">';
	  print "&nbsp;</td></tr>";
	
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
			print '</td></tr>';
		}

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
	
	  $sql = "SELECT l.ref as ref_fourn, l.fk_product, l.description, l.qty";
	  $sql.= ", l.rowid, l.tva_tx, l.remise_percent, l.subprice";
	  $sql.= ", l.total_ht, l.total_tva, l.total_ttc";
	  $sql.= ", p.rowid as product_id, p.label as product, p.ref";
	  $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet	as l";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
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
	    	print '<td>'.$langs->trans('Description').'</td>';
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
	    	$objp =	$db->fetch_object($resql);
	    	$var=!$var;
	    	
	    	// Ligne en mode visu
			  if ($_GET['action'] != 'editline' || $_GET['rowid'] != $objp->rowid)
				{
					print '<tr '.$bc[$var].'>';
					if ($objp->fk_product > 0)
					{
						print '<td>';
						print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
						
						// Affiche ligne produit
						$text = '<a href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$objp->product_id.'">';
						$text.= img_object($langs->trans('ShowProduct'),'product');
						$text.= ' '.$objp->ref_fourn.'</a>';
						$text.= ' ('.$objp->ref.')';
						$text.= ' - '.$objp->product;
						$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($objp->description));
						print $html->textwithtooltip($text,$description,3,'','',$i);
						if ($conf->global->PRODUIT_DESC_IN_FORM)
						{
							print ($objp->description && $objp->description!=$objp->product)?'<br>'.dol_htmlentitiesbr($objp->description):'';
						}
						
						print "</td>";
					}
					else
					{
						print '<td>';
						print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
						print nl2br($objp->description);
						print '</td>';
					}
					print '<td align="right">'.vatrate($objp->tva_tx).'%</td>';
					print '<td align="right">'.price($objp->subprice)."</td>\n";
					print '<td align="right">'.$objp->qty.'</td>';
					if ($objp->remise_percent >	0)
					{
						print '<td align="right">'.dolibarr_print_reduction($objp->remise_percent)."</td>\n";
					}
					else
					{
						print '<td>&nbsp;</td>';
					}
					//Todo: Modifier la classe pour utiliser le champ total_ttc
					print '<td align="right">'.price($objp->subprice*$objp->qty*(100-$objp->remise_percent)/100).'</td>';
					if ($commande->statut == 0	&& $user->rights->fournisseur->commande->creer)
					{
						print '<td align="center"><a	href="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;action=editline&amp;rowid='.$objp->rowid.'#'.$objp->rowid.'">';
						print img_edit();
						print '</a></td>';
						
						print '<td align="center"><a	href="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;action=delete_product_line&amp;lineid='.$objp->rowid.'">';
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
				if ($_GET["action"]	== 'editline' && $user->rights->fournisseur->commande->creer && $_GET["rowid"]	== $objp->rowid)
		    {
		      print '<form action="'.$_SERVER["PHP_SELF"].'#'.$objp->rowid.'" method="post">';
		      print '<input type="hidden" name="action" value="updateligne">';
					print '<input type="hidden" name="id" value="'.$_REQUEST["id"].'">';
					print '<input type="hidden" name="elrowid" value="'.$_GET['rowid'].'">';
		      print '<tr '.$bc[$var].'>';
		      print '<td>';
		      print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
		      if ($objp->fk_product > 0)
					{
						print '<a href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$objp->product_id.'">';
						print img_object($langs->trans('ShowProduct'),'product');
						print ' '.$objp->ref_fourn.'</a>';
						print ' ('.$objp->ref.')';
						print ' - '.nl2br($objp->product);
						print '<br>';
					}
					// editeur wysiwyg
					if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
					{
						require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
						$doleditor=new DolEditor('eldesc',$objp->description,200,'dolibarr_details');
						$doleditor->Create();
					}
					else
					{
						print '<textarea name="eldesc" class="flat" cols="70" rows="1">'.dol_htmlentitiesbr_decode($objp->description).'</textarea>';
					}
		      print '</td>';
		      print '<td>';
		      $html->select_tva('tva_tx',$objp->tva_tx);
		      print '</td>';
		      print '<td align="right"><input	size="5" type="text" name="pu"	value="'.price($objp->subprice).'"></td>';
		      print '<td align="right"><input size="2" type="text" name="qty" value="'.$objp->qty.'"></td>';
		      print '<td align="right" nowrap="nowrap"><input size="1" type="text" name="remise_percent" value="'.$objp->remise_percent.'">%</td>';
		      print '<td align="center" colspan="2"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
		      print '<br /><input	type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></td>';
		      print '</tr>' .	"\n";
		      print "</form>\n";
		    }
		  $i++;
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
	  if ($commande->statut == 0 && $user->rights->fournisseur->commande->creer && $_GET["action"] <> 'editline')
	    {
	      print '<tr class="liste_titre">';
	      print '<td>';
	      print '<a name="add"></a>'; // ancre
	      print $langs->trans('Description').'</td>';
		    print '<td align="right">'.$langs->trans('VAT').'</td>';
			  print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
			  print '<td align="right">'.$langs->trans('Qty').'</td>';
			  print '<td align="right">'.$langs->trans('ReductionShort').'</td>';
			  print '<td colspan="4">&nbsp;</td>';
	      print '</tr>';
	      
	      // Ajout produit produits/services personnalises
	      print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'#add" method="post">';
	      print '<input type="hidden"	name="action" value="addligne">';
	      print '<input type="hidden"	name="id" value="'.$comid.'">';
	      
	      $var=true;
				print '<tr '.$bc[$var].'>';
				print '<td>';
				// editeur wysiwyg
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
				if($soc->tva_assuj == "0")
				print '<input type="hidden" name="tva_tx" value="0">0';
				else
				print $html->select_tva('tva_tx',$conf->defaulttx,$mysoc,$soc);
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
					print '<input type="hidden" name="id" value="'.$id.'">';
					print '<input type="hidden" name="action" value="addligne">';

				  $var=!$var;
				  print '<tr '.$bc[$var].'>';
				  print '<td colspan="3">';
				  $html->select_produits_fournisseurs($commande->fourn_id,'','idprodfournprice');
				
				  if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) print '<br>';

			  	// editeur wysiwyg
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
	  if ($user->societe_id == 0 && $commande->statut < 3 && $_GET['action'] <> 'editline')
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
	
	      print "</div>";
	    }
	
	  print '<table width="100%"><tr><td width="50%" valign="top">';
	  print '<a name="builddoc"></a>'; // ancre

	  /*
	   * Documents	generes
	   *
	   */
	  $comfournref = sanitize_string($commande->ref);
	  $file =	$conf->fournisseur->commande->dir_output . '/' . $comfournref .	'/'	. $comfournref . '.pdf';
	  $relativepath =	$comfournref.'/'.$comfournref.'.pdf';
	  $filedir = $conf->fournisseur->commande->dir_output	. '/' .	$comfournref;
	  $urlsource=$_SERVER["PHP_SELF"]."?id=".$commande->id;
	  $genallowed=($commande->statut == 0 ? $user->rights->fournisseur->commande->creer : 0);
	  $delallowed=$user->rights->fournisseur->commande->supprimer;
	
	  $somethingshown=$formfile->show_documents('commande_fournisseur',$comfournref,$filedir,$urlsource,$genallowed,$delallowed,$commande->modelpdf);
	
	
	  print '</td><td	width="50%"	valign="top">';
	
	
	  /*
	   *
	   *
	   */
	  if ( $user->rights->fournisseur->commande->commander && $commande->statut == 2)
	    {
	      /**
	       * Commander
	       */
	      print '<br>';
	      print '<form name="commande" action="fiche.php?id='.$commande->id.'&amp;action=commande" method="post">';
	      print '<table class="border" width="100%">';
	      print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("ToOrder").'</td></tr>';
	      print '<tr><td>'.$langs->trans("OrderDate").'</td><td>';
	      print $html->select_date('','','','','',"commande");
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
	       * Receptionner
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
	      $liv['']	    = '&nbsp;';
	      $liv['tot']	= $langs->trans("TotalWoman");
	      $liv['par']	= $langs->trans("PartialWoman");
	      $liv['nev']	= $langs->trans("NeverReceived");
	      $liv['can']	= $langs->trans("Canceled");
	
	      print $html->select_array("type",$liv);
	
	
	      print '</td></tr>';
	      print '<tr><td>'.$langs->trans("Comment").'</td><td><input size="30"	type="text"	name="commentaire"></td></tr>';
	      print '<tr><td align="center" colspan="2"><input type="submit" class="button" name="'.$langs->trans("Activate").'"></td></tr>';
	      print "</table>\n";
	      print "</form>\n";
	    }
	  print '</td></tr></table>';
	}
      else
	{
	  // Commande	non	trouvee
	  dolibarr_print_error($db);
	}
}

$db->close();

llxFooter('$Date$	- $Revision$');
?>
