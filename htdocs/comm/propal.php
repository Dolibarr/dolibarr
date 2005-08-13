<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
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
        \file       htdocs/comm/propal.php
        \ingroup    propale
        \brief      Page liste des propales (vision commercial)
*/

require("./pre.inc.php");

$langs->load('companies');
$langs->load('propal');
$langs->load('compta');
$langs->load('bills');

$user->getrights('propale');

if (!$user->rights->propale->lire)
	accessforbidden();

if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/project.class.php');
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT.'/commande/commande.class.php');
require_once('./propal_model_pdf.class.php');
require_once('../propal.class.php');
require_once('../actioncomm.class.php');
require_once(DOL_DOCUMENT_ROOT.'/lib/CMailFile.class.php');

// Sécurité accés client
if ($user->societe_id > 0) 
{
	$action = '';
	$socidp = $user->societe_id;
}

if ($_GET["socidp"]) { $socidp=$_GET["socidp"]; }
if (isset($_GET["msg"])) { $msg=urldecode($_GET["msg"]); }
$year=isset($_GET["year"])?$_GET["year"]:"";
$month=isset($_GET["month"])?$_GET["month"]:"";


// Nombre de ligne pour choix de produit/service prédéfinis
$NBLINES=4;

$form=new Form($db);

/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST['action'] == 'confirm_delete' && $_POST['confirm'] == 'yes')
{
  if ($user->rights->propale->supprimer)
    {
      $propal = new Propal($db, 0, $_GET['propalid']);
      $propal->delete($user);
      $propalid = 0;
      $brouillon = 1;
    }
  Header('Location: propal.php');
}


if ($_POST['action'] == 'add') 
{
  $propal = new Propal($db, $_GET['socidp']);
  $propal->datep = mktime(12, 1 , 1, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
  
  $propal->duree_validite = $_POST['duree_validite'];
  
  $propal->contactid = $_POST['contactidp'];
  $propal->projetidp = $_POST['projetidp'];
  $propal->modelpdf  = $_POST['modelpdf'];
  $propal->author    = $user->id;
  $propal->note      = $_POST['note'];
  
  $propal->ref = $_POST['ref'];
  
  for ($i = 1 ; $i <= PROPALE_NEW_FORM_NB_PRODUCT ; $i++)
    {
      $xid = 'idprod'.$i;
      $xqty = 'qty'.$i;
      $xremise = 'remise'.$i;
      $propal->add_product($_POST[$xid],$_POST[$xqty],$_POST[$xremise]);
    }
  
  $id = $propal->create();
  
  /*
   *   Generation
   */
  if ($id) 
    {
      propale_pdf_create($db, $id, $_POST['modelpdf']);
      Header ('Location: propal.php?propalid='.$id);
    }
}

if ($_GET['action'] == 'pdf')
{
  $propal = new Propal($db);
  $propal->fetch($_GET['propalid']);
  propale_pdf_create($db, $_GET['propalid'], $propal->modelpdf);
}

if ($_POST['action'] == 'setstatut' && $user->rights->propale->cloturer) 
{
  /*
   *  Cloture de la propale
   */
  $propal = new Propal($db);
  $propal->fetch($_GET['propalid']);
  $propal->cloture($user, $_POST['statut'], $_POST['note']);
}

/*
 * Envoi de la propale par mail
 *
 */
if ($_POST['action'] == 'send')
{
  $langs->load('mails');
  $propal= new Propal($db);
  if ( $propal->fetch($_POST['propalid']) )
    {
      $propalref = sanitize_string($propal->ref);
      $file = $conf->propal->dir_output . '/' . $propalref . '/' . $propalref . '.pdf';
      if (is_readable($file))
	{
	  $soc = new Societe($db, $propal->socidp);
	  if ($_POST['sendto'])
	    {
	      // Le destinataire a été fourni via le champ libre
	      $sendto = $_POST['sendto'];
	      $sendtoid = 0;
	    }
	  elseif ($_POST['receiver'])
	    {
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
		  $subject = $langs->trans('Propal').' '.$propal->ref;
		  $actiontypeid=3;
		  $actionmsg ='Mail envoyé par '.$from.' à '.$sendto.'.<br>';
		  if ($message)
		    {
		      $actionmsg.='Texte utilisé dans le corps du message:<br>';
		      $actionmsg.=$message;
		    }
		  $actionmsg2='Envoi Propal par mail';
		}
	      /*
		if ($_POST["action"] == 'relance')
		{
		$subject = "Relance facture $propal->ref";
		$actiontypeid=10;
		$actionmsg="Mail envoyé par $from à $sendto.<br>";
		if ($message)
		{
		$actionmsg.="Texte utilisé dans le corps du message:<br>";
		$actionmsg.=$message;
		}
		$actionmsg2="Relance Facture par mail";
		}
	      */
	      $filepath[0] = $file;
	      $filename[0] = $propal->ref.'.pdf';
	      $mimetype[0] = 'application/pdf';
	      if ($_FILES['addedfile']['tmp_name']) 
		{
		  $filepath[1] = $_FILES['addedfile']['tmp_name'];
		  $filename[1] = $_FILES['addedfile']['name'];
		  $mimetype[1] = $_FILES['addedfile']['type'];
                }
	      // Envoi de la facture
	      $mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc);
	      if ($mailfile->sendfile())
		{
		  $msg='<div class="ok">'.$langs->trans('MailSuccessfulySent',$from,$sendto).'.</div>';
		  // Insertion action
		  include_once('../contact.class.php');
		  $actioncomm = new ActionComm($db);
		  $actioncomm->type_id     = $actiontypeid;
		  $actioncomm->label       = $actionmsg2;
		  $actioncomm->note        = $actionmsg;
		  $actioncomm->date        = time();  // L'action est faite maintenant
		  $actioncomm->percent     = 100;
		  $actioncomm->contact     = new Contact($db,$sendtoid);
		  $actioncomm->societe     = new Societe($db,$propal->socidp);
		  $actioncomm->user        = $user;   // User qui a fait l'action
		  $actioncomm->propalrowid = $propal->id;
		  $ret=$actioncomm->add($user);       // User qui saisi l'action
		  if ($ret < 0)
		    {
		      dolibarr_print_error($db);
		    }
		  else
		    {
		      // Renvoie sur la fiche
		      Header('Location: propal.php?propalid='.$propal->id.'&msg='.urlencode($msg));
		      exit;
		    }
		}
	      else
		{
		  $msg='<div class="error">'.$langs->trans('ErrorFailedToSendMail',$from,$sendto).' - '.$actioncomm->error.'</div>';
		}
	    }
	  else
	    {
	      $msg='<div class="error">'.$langs->trans('ErrorMailRecipientIsEmpty').' !</div>';
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
      dolibarr_syslog('Impossible de lire les données de la propale. Le fichier propal n\'a peut-être pas été généré.');
    }
}

if ($_GET['action'] == 'commande')
{
  /*
   *  Cloture de la propale
   */
  $propal = new Propal($db);
  $propal->fetch($propalid);
  $propal->create_commande($user);
}

if ($_GET['action'] == 'modif' && $user->rights->propale->creer) 
{
  /*
   *  Repasse la propale en mode brouillon
   */
  $propal = new Propal($db);
  $propal->fetch($_GET['propalid']);
  $propal->reopen($user->id);
}

if ($_POST['action'] == "addligne" && $user->rights->propale->creer) 
{
    /*
     *  Ajout d'une ligne produit dans la propale
     */
    $propal = new Propal($db);
    $propal->fetch($_POST['propalid']);

    if (isset($_POST['np_tva_tx']))
    {
        $propal->insert_product_generic(
				    $_POST['np_desc'], 
				    $_POST['np_price'], 
				    $_POST['np_qty'],
				    $_POST['np_tva_tx'],
				    $_POST['np_remise']);
    }
    else 
    {
        $propal->insert_product(
                    $_POST['idprod'],
                    $_POST['qty'],
                    $_POST['remise'],
                    $_POST['np_desc']);
    }
    propale_pdf_create($db, $_POST['propalid'], $propal->modelpdf);
}

if ($_POST['action'] == 'updateligne' && $user->rights->propale->creer) 
{
  /*
   *  Mise à jour d'une ligne dans la propale
   */

  $propal = new Propal($db);
  $propal->fetch($_GET['propalid']);
  $propal->UpdateLigne($_POST['ligne'], $_POST['subprice'], $_POST['qty'], $_POST['remise']);

  propale_pdf_create($db, $_GET['propalid'], $propal->modelpdf);
}

if ($_POST['action'] == 'setpdfmodel' && $user->rights->propale->creer) 
{
  $propal = new Propal($db, 0, $_GET['propalid']);
  $propal->set_pdf_model($user, $_POST['modelpdf']);
  propale_pdf_create($db, $_GET['propalid'], $_POST['modelpdf']);
}


if ($_GET['action'] == 'del_ligne' && $user->rights->propale->creer) 
{
  /*
   *  Supprime une ligne produit dans la propale
   */
  $propal = new Propal($db);
  $propal->fetch($_GET['propalid']);
  $propal->delete_product($_GET['ligne']);
  propale_pdf_create($db, $_GET['propalid'], $propal->modelpdf);
}

if ($_GET['valid'] == 1 && $user->rights->propale->valider)
{
  $propal = new Propal($db);
  $propal->fetch($_GET['propalid']);
  $propal->update_price($_GET['propalid']);
  propale_pdf_create($db, $_GET['propalid'], $propal->modelpdf);
  $propal->valid($user);
}

if ($_POST['action'] == 'setremise' && $user->rights->propale->creer) 
{
  $propal = new Propal($db);
  $propal->fetch($_GET['propalid']);
  $propal->set_remise($user, $_POST['remise']);
  propale_pdf_create($db, $_GET['propalid'], $propal->modelpdf);
}

if ($_POST['action'] == 'set_project')
{
  $propal = new Propal($db);
  $propal->fetch($_GET['propalid']);
  $propal->set_project($user, $_POST['projetidp']);
}

if ($_POST['action'] == 'set_contact')
{
  $propal = new Propal($db);
  $propal->fetch($_GET['propalid']);
  $propal->set_contact($user, $_POST['contactidp']);
}


llxHeader();


/*
 * Affichage fiche propal en mode visu
 *
 */
if ($_GET['propalid'])
{
  if ($msg) print "$msg<br>";
  $html = new Form($db);

  $propal = new Propal($db);
  $propal->fetch($_GET['propalid']);

  $societe = new Societe($db);
  $societe->fetch($propal->soc_id);
  $h=0;

  $head[$h][0] = DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id;
  $head[$h][1] = $langs->trans('CommercialCard');
  $hselected=$h;
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/compta/propal.php?propalid='.$propal->id;
  $head[$h][1] = $langs->trans('AccountancyCard');
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/apercu.php?propalid='.$propal->id;
  $head[$h][1] = $langs->trans("Preview");
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/note.php?propalid='.$propal->id;
  $head[$h][1] = $langs->trans('Note');
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/info.php?propalid='.$propal->id;
  $head[$h][1] = $langs->trans('Info');
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/document.php?propalid='.$propal->id;
  $head[$h][1] = $langs->trans('Documents');
  $h++;

  dolibarr_fiche_head($head, $hselected, $langs->trans('Proposal').': '.$propal->ref);

  /*
   * Confirmation de la suppression de la propale
   *
   */
  if ($_GET['action'] == 'delete')
    {
      $html->form_confirm('propal.php?propalid='.$propal->id, $langs->trans('DeleteProp'), $langs->trans('ConfirmDeleteProp'), 'confirm_delete');
      print '<br>';
    }


  /*
   * Fiche propal
   *
   */
  $sql = 'SELECT s.nom, s.idp, p.price, p.fk_projet, p.remise, p.tva, p.total, p.ref, p.fk_statut, '.$db->pdate('p.datep').' as dp, p.note,';
  $sql.= ' x.firstname, x.name, x.fax, x.phone, x.email, p.fk_user_author, p.fk_user_valid, p.fk_user_cloture, p.datec, p.date_valid, p.date_cloture';
  $sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'propal as p, '.MAIN_DB_PREFIX.'socpeople as x';
  $sql.= ' WHERE p.fk_soc = s.idp AND p.fk_soc_contact = x.idp AND p.rowid = '.$propal->id;
  if ($socidp) $sql .= ' AND s.idp = '.$socidp;

  $resql = $db->query($sql);
  if ($resql)
    {
      if ($db->num_rows($resql)) 
	{
	  $obj = $db->fetch_object($resql);

	  $societe = new Societe($db);
	  $societe->fetch($obj->idp);

	  print '<table class="border" width="100%">';
	  $rowspan=6;
	  print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">';
	  if ($societe->client == 1)
	    {
	      $url ='fiche.php?socid='.$societe->id;
	    }
	  else
	    {
	      $url = DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$societe->id;
	    }
	  print '<a href="'.$url.'">'.$societe->nom.'</a></td>';
	  print '<td align="left">Conditions de réglement</td>';
	  print '<td>'.'&nbsp;'.'</td>';
	  print '</tr>';

	  print '<tr><td>'.$langs->trans('Date').'</td><td colspan="3">';
	  print dolibarr_print_date($propal->date,'%a %d %B %Y');
	  print '</td>';

	  print '<td>'.$langs->trans('DateEndPropal').'</td><td>';
	  if ($propal->fin_validite)
	    {
	      print dolibarr_print_date($propal->fin_validite,'%a %d %B %Y');
		  if ($propal->statut == 1 && $propal->fin_validite < (time() - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
	    }
	  else
	    {
	    print $langs->trans("Unknown");   
	  }
	  print '</td>';
	  print '</tr>';

	  // Destinataire
	  $langs->load('mails');
	  print '<tr>';
	  print '<td>'.$langs->trans('MailTo').'</td>';

	  $dests=$societe->contact_array($societe->id);
	  $numdest = count($dests);
	  if ($numdest==0)
	    {
	      print '<td colspan="3">';
	      print '<font class="error">Cette societe n\'a pas de contact, veuillez en créer un avant de faire votre proposition commerciale</font><br>';
	      print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$societe->id.'&amp;action=create&amp;backtoreferer=1">'.$langs->trans('AddContact').'</a>';
	      print '</td>';
	    }
	  else
	    {
	      if ($propal->statut == 0 && $user->rights->propale->creer)
		{
		  print '<td colspan="2">';
		  print '<form action="propal.php?propalid='.$propal->id.'" method="post">';
		  print '<input type="hidden" name="action" value="set_contact">';
		  $form->select_contacts($societe->id, $propal->contactid, 'contactidp');
		  print '</td><td>';
		  print '<input type="submit" value="'.$langs->trans('Modify').'">';
		  print '</form>';
		  print '</td>';
		}
	      else
		{
		  if (!empty($propal->contactid))
		    {
		      print '<td colspan="3">';
		      require_once(DOL_DOCUMENT_ROOT.'/contact.class.php');
		      $contact=new Contact($db);
		      $contact->fetch($propal->contactid);
		      print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$propal->contactid.'" title="'.$langs->trans('ShowContact').'">';
		      print $contact->firstname.' '.$contact->name;
		      print '</a>';
		      print '</td>';
		    }
		  else {
		    print '<td colspan="3">&nbsp;</td>';
		  }
		}
	    }
	  print '</td>';

	  if ($conf->projet->enabled) 
	    $rowspan++;

	  print '<td valign="top" colspan="2" width="50%" rowspan="'.$rowspan.'">'.$langs->trans('Note').' :<br>'. nl2br($propal->note).'</td></tr>';

	  if ($conf->projet->enabled)
	    {
	      $langs->load("projects");
	      print '<tr><td>'.$langs->trans('Project').'</td>';
	      $numprojet = $societe->has_projects();
	      if (! $numprojet)
		{
		  print '<td colspan="2">';
		  print $langs->trans("NoProject").'</td><td>';
		  print '<a href=../projet/fiche.php?socidp='.$societe->id.'&action=create>'.$langs->trans('AddProject').'</a>';
		  print '</td>';
		}
	      else
		{
		  if ($propal->statut == 0 && $user->rights->propale->creer)
		    {
		      print '<td colspan="2">';
		      print '<form action="propal.php?propalid='.$propal->id.'" method="post">';
		      print '<input type="hidden" name="action" value="set_project">';
		      $form->select_projects($societe->id, $propal->projetidp, 'projetidp');
		      print '</td><td>';
		      print '<input type="submit" value="'.$langs->trans('Modify').'">';
		      print '</form>';
		      print '</td>';
		    }
		  else
		    {
		      if (!empty($propal->projetidp))
			{
			  print '<td colspan="3">';
			  $proj = new Project($db);
			  $proj->fetch($propal->projetidp);
			  print '<a href="../projet/fiche.php?id='.$propal->projetidp.'" title="'.$langs->trans('ShowProject').'">';
			  print $proj->title;
			  print '</a>';
			  print '</td>';
			}
		      else {
			print '<td colspan="3">&nbsp;</td>';
		      }
		    }
		}
	      print '</tr>';
	    }

	  print '<tr><td height="10" nowrap>'.$langs->trans('GlobalDiscount').'</td>';
	  if ($propal->brouillon == 1 && $user->rights->propale->creer)
	    {
	      print '<form action="propal.php?propalid='.$propal->id.'" method="post">';
	      print '<input type="hidden" name="action" value="setremise">';
	      print '<td colspan="2"><input type="text" name="remise" size="3" value="'.$propal->remise_percent.'">% ';
	      print '</td><td>';
	      print '<input type="submit" value="'.$langs->trans('Modify').'">';
	      print ' <a href="propal/aideremise.php?propalid='.$propal->id.'">?</a>';
	      print '</td>';
	      print '</form>';
	    }
	  else
	    {
	      print '<td colspan="3">'.$propal->remise_percent.'%</td>';
	    }
	  print '</tr>';

	  print '<tr><td height="10">'.$langs->trans('AmountHT').'</td>';
	  print '<td align="right" colspan="2"><b>'.price($propal->price).'</b></td>';
	  print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

	  print '<tr><td height="10">'.$langs->trans('VAT').'</td><td align="right" colspan="2">'.price($propal->total_tva).'</td>';
	  print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
	  print '<tr><td height="10">'.$langs->trans('AmountTTC').'</td><td align="right" colspan="2">'.price($propal->total_ttc).'</td>';
	  print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

      // Statut
	  print '<tr><td height="10">'.$langs->trans('Status').'</td><td align="left" colspan="3">'.$propal->getLibStatut().'</td></tr>';
	  print '</table><br>';
	  if ($propal->brouillon == 1 && $user->rights->propale->creer)
	    {
	      print '</form>';
	    }

	  /*
	   * Lignes de propale
	   *
	   */
	  $sql = 'SELECT pt.rowid, pt.description, pt.price, pt.fk_product, pt.qty, pt.tva_tx, pt.remise_percent, pt.subprice, p.label as product, p.ref, p.fk_product_type, p.rowid as prodid';
	  $sql .= ' FROM '.MAIN_DB_PREFIX.'propaldet as pt LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pt.fk_product=p.rowid';
	  $sql .= ' WHERE pt.fk_propal = '.$propal->id;
	  $sql .= ' ORDER BY pt.rowid ASC';
	  $resql = $db->query($sql);
	  if ($resql) 
	    {
	      $num_lignes = $db->num_rows($resql);
	      $i = 0;
	      $total = 0;

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
		  print '<td>&nbsp;</td><td>&nbsp;</td>';
		  print "</tr>\n";
		}
	      $var=true;
	      while ($i < $num_lignes)
		{
		  $objp = $db->fetch_object($resql);
		  $var=!$var;
		  if ($_GET['action'] != 'editline' || $_GET['rowid'] != $objp->rowid)
		    {
		      print '<tr '.$bc[$var].'>';
		      if ($objp->fk_product > 0)
              {
                print '<td><a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
                if ($objp->fk_product_type)
                print img_object($langs->trans('ShowService'),'service');
                else
                print img_object($langs->trans('ShowProduct'),'product');
                print ' '.stripslashes(nl2br($objp->product)).'</a>';
                if ($objp->date_start && $objp->date_end)
                {
                    print ' (Du '.dolibarr_print_date($objp->date_start).' au '.dolibarr_print_date($objp->date_end).')';
                }
                if ($objp->date_start && ! $objp->date_end)
                {
                    print ' (A partir du '.dolibarr_print_date($objp->date_start).')';
                }
                if (! $objp->date_start && $objp->date_end)
                {
                    print " (Jusqu'au ".dolibarr_print_date($objp->date_end).')';
                }
                print $objp->description?'<br>'.$objp->description:'';
                print '</td>';
            }
		      else
			{
			  print '<td>'.stripslashes(nl2br($objp->description));
			  if ($objp->date_start && $objp->date_end) 
			    { 
			      print ' (Du '.dolibarr_print_date($objp->date_start).' au '.dolibarr_print_date($objp->date_end).')'; 
			    }
			  if ($objp->date_start && ! $objp->date_end)
			    {
			      print ' (A partir du '.dolibarr_print_date($objp->date_start).')';
			    }
			  if (! $objp->date_start && $objp->date_end)
			    {
			      print " (Jusqu'au ".dolibarr_print_date($objp->date_end).')';
			    }
			  print "</td>\n";
			}
		      print '<td align="right">'.$objp->tva_tx.'%</td>';
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
		      if ($propal->statut == 0  && $user->rights->propale->creer) 
			{
			  print '<td align="right"><a href="propal.php?propalid='.$propal->id.'&amp;action=editline&amp;ligne='.$objp->rowid.'">';
			  print img_edit();
			  print '</a></td>';
			  print '<td align="right"><a href="propal.php?propalid='.$propal->id.'&amp;action=del_ligne&amp;ligne='.$objp->rowid.'">';
			  print img_delete();
			  print '</a></td>';
			}
		      else
			{
			  print '<td>&nbsp;</td><td>&nbsp;</td>';
			}
		      print '</tr>';
		    }
		  // Update ligne de propal
		  if ($propal->statut == 0 && $user->rights->propale->creer && $_GET["action"] == 'editline' && $_GET["ligne"] == $objp->rowid)
		    {
		      print '<form action="propal.php?propalid='.$propal->id.'" method="post">';
		      print '<input type="hidden" name="action" value="updateligne">';
		      print '<input type="hidden" name="ligne" value="'.$_GET["ligne"].'">';
		      print '<tr '.$bc[$var].'>';
		      print '<td colspan="2">&nbsp;</td>';
		      print '<td align="right"><input name="subprice" type="text" size="6" value="'.$objp->subprice.'"></td>';
		      print '<td align="right"><input name="qty" type="text" size="2" value="'.$objp->qty.'"></td>';
		      print '<td align="right"><input name="remise" type="text" size="2" value="'.$objp->remise_percent.'"> %</td>';
		      print '<td align="center" colspan="3"><input type="submit" value="'.$langs->trans("Save").'"></td>';
		      print '</tr></form>';
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
	   *
	   */
	  if ($propal->statut == 0 && $user->rights->propale->creer && $_GET["action"] <> 'editline')
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
	      print "</tr>\n";

	      // Ajout produit produits/services personalisés
	      print '<form action="propal.php?propalid='.$propal->id.'" method="post">';
	      print '<input type="hidden" name="propalid" value="'.$propal->id.'">';
	      print '<input type="hidden" name="action" value="addligne">';

	      $var=true;
	      print '<tr '.$bc[$var].">\n";
	      print '  <td><textarea cols="50" name="np_desc"></textarea></td>';
	      print '  <td align="center">';
	      print $html->select_tva('np_tva_tx', $conf->defaulttx) . "</td>\n";
	      print '  <td align="right"><input type="text" size="5" name="np_price"></td>';
	      print '  <td align="right"><input type="text" size="2" value="1" name="np_qty"></td>';
	      print '  <td align="right" nowrap><input type="text" size="2" value="'.$societe->remise_client.'" name="np_remise">%</td>';
	      print '  <td align="center" colspan="3"><input type="submit" value="'.$langs->trans('Add').'" name="addligne"></td>';
	      print '</tr>';
	      
	      print '</form>';

	      // Ajout de produits/services prédéfinis
	      print '<form action="propal.php?propalid='.$propal->id.'" method="post">';
	      print '<input type="hidden" name="propalid" value="'.$propal->id.'">';
	      print '<input type="hidden" name="action" value="addligne">';

	      $var=!$var;
	      print '<tr '.$bc[$var].'>';
	      print '<td colspan="2">';
	      $html->select_produits('','idprod','',20);
	      print '<br>';
	      print '<textarea cols="50" name="np_desc" rows="1"></textarea>';
	      print '</td>';
	      print '<td>&nbsp;</td>';
	      print '<td align="right"><input type="text" size="2" name="qty" value="1"></td>';
	      print '<td align="right" nowrap><input type="text" size="2" name="remise" value="'.$societe->remise_client.'">%</td>';
	      print '<td align="center" colspan="3"><input type="submit" value="'.$langs->trans("Add").'" name="addligne"></td>';
	      print "</tr>\n";

	      print '</form>';
	    }
	  
	  print '</table>';
	  
	}
    }
  else
    {
      dolibarr_print_error($db);
    }
  
  print '</div>';

  /*
   * Formulaire cloture (signé ou non)
   */
  if ($_GET['action'] == 'statut') 
    {
      print '<form action="propal.php?propalid='.$propal->id.'" method="post">';
      print '<table class="border" width="100%">';
      print '<tr><td>'.$langs->trans('Note').'</td><td><textarea cols="60" rows="2" wrap="soft" name="note">';
      print $obj->note;
      print '</textarea></td></tr>';
      print '<tr><td>'.$langs->trans("CloseAs").'</td><td>';
      print '<input type="hidden" name="action" value="setstatut">';
      print '<select name="statut">';
      print '<option value="2">'.$propal->labelstatut[2].'</option>';
      print '<option value="3">'.$propal->labelstatut[3].'</option>';
      print '</select>';
      print '</td></tr>';
      print '<tr><td align="center" colspan="2"><input type="submit" value="'.$langs->trans('Valid').'"></td>';
      print '</tr></table></form>';
    }


  /*
   * Boutons Actions
   */
  if ($propal->statut < 2)
    {
      print '<div class="tabsAction">';

      // Valid
      if ($propal->statut == 0)
	{
	  if ($user->rights->propale->valider)
	    {
	      print '<a class="butAction" href="propal.php?propalid='.$propal->id.'&amp;valid=1">'.$langs->trans('Valid').'</a>';
	    }
	}

      // Save
      if ($propal->statut == 1)
	{
	  if ($user->rights->propale->creer)
	    {
	      print '<a class="butAction" href="propal.php?propalid='.$propal->id.'&amp;action=modif">'.$langs->trans('Edit').'</a>';
	    }
	}

      // Build PDF
      if ($propal->statut < 2 && $user->rights->propale->creer)
	{
	  print '<a class="butAction" href="propal.php?propalid='.$propal->id.'&amp;action=pdf">'.$langs->trans('BuildPDF').'</a>';
	}	   

      // Send
      if ($propal->statut == 1)
	{
	  if ($user->rights->propale->envoyer)
	    {
	      $propref = sanitize_string($obj->ref);
	      $file = $conf->propal->dir_output . '/'.$propref.'/'.$propref.'.pdf';
	      if (file_exists($file))
		{
		  print '<a class="butAction" href="propal.php?propalid='.$propal->id.'&amp;action=presend">'.$langs->trans('Send').'</a>';
		}
	    }
	}

      // Close
      if ($propal->statut != 0)
	{
	  if ($propal->statut == 1 && $user->rights->propale->cloturer)
	    {
	      print '<a class="butAction" href="propal.php?propalid='.$propal->id.'&amp;action=statut">'.$langs->trans('Close').'</a>';
	    }
	}

      // Delete
      if ($propal->statut == 0)
	{
	  if ($user->rights->propale->supprimer)
	    {
	      print '<a class="butActionDelete" href="propal.php?propalid='.$propal->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
	    }
	}

      print '</div>';
    }


  print '<table width="100%"><tr><td width="50%" valign="top">';

  /*
   * Documents
   */
  if ($propal->brouillon == 1)
    {
      print '<form action="propal.php?propalid='.$propal->id.'" method="post">';
      print '<input type="hidden" name="action" value="setpdfmodel">';
    }
  print_titre($langs->trans('Documents'));

  print '<table class="border" width="100%">';
  $propref = sanitize_string($propal->ref);
  $file = $conf->propal->dir_output . '/'.$propref.'/'.$propref.'.pdf';
  $relativepath = $propref.'/'.$propref.'.pdf';

  $var=true;

  if (file_exists($file))
    {
      print '<tr '.$bc[$var].'><td>'.$langs->trans('Propal').' PDF</td>';
      print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=propal&file='.urlencode($relativepath).'">'.$propal->ref.'.pdf</a></td>';
      print '<td align="right">'.filesize($file). ' bytes</td>';
      print '<td align="right">'.strftime('%d %B %Y %H:%M:%S',filemtime($file)).'</td></tr>';
    }

  if ($propal->brouillon == 1 && $user->rights->propale->creer)
    {
      print '<tr '.$bc[$var].'><td>Modèle</td><td align="right">';
      $html = new Form($db);
      $modelpdf = new Propal_Model_pdf($db);
      $html->select_array('modelpdf',$modelpdf->liste_array(),$propal->modelpdf);
      print '</td><td colspan="2"><input type="submit" value="'.$langs->trans('Save').'">';
      print '</td></tr>';
    }
  print "</table>\n";

  if ($propal->brouillon == 1)
    {
      print '</form>';
    }


  /*
   * Commandes rattachées
   */
  if($conf->commande->enabled)
    {
      $coms = $propal->associated_orders();
      if (sizeof($coms) > 0)
	{
	  print '<br>';
	  print_titre($langs->trans('RelatedOrders'));
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre">';
	  print '<td>'.$langs->trans("Ref").'</td>';
	  print '<td align="center">'.$langs->trans("Date").'</td>';
	  print '<td align="right">'.$langs->trans("Price").'</td>';
	  print '</tr>';
	  $var=true;
	  for ($i = 0 ; $i < sizeof($coms) ; $i++)
	    {
	      $var=!$var;
	      print '<tr '.$bc[$var].'><td>';
	      print '<a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$coms[$i]->id.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$coms[$i]->ref."</a></td>\n";
	      print '<td align="center">'.dolibarr_print_date($coms[$i]->date).'</td>';
	      print '<td align="right">'.$coms[$i]->total_ttc.'</td>';
	      print "</tr>\n";
	    }
	  print '</table>';
	}
    }

  print '</td><td valign="top" width="50%">';

  /*
   * Liste des actions propres à la propal
   */
  $sql = 'SELECT id, '.$db->pdate('a.datea'). ' as da, label, note, fk_user_author' ;
  $sql .= ' FROM '.MAIN_DB_PREFIX.'actioncomm as a';
  $sql .= ' WHERE a.fk_soc = '.$obj->idp.' AND a.propalrowid = '.$propal->id ;
  $resql = $db->query($sql);
  if ($resql)
    {
      $num = $db->num_rows($resql);
      if ($num)
	{
	  print_titre($langs->trans('ActionsOnPropal'));
	  $i = 0;
	  $total = 0;
	  $var=true;

	  print '<table class="border" width="100%">';
	  print '<tr '.$bc[$var].'><td>'.$langs->trans('Ref').'</td><td>'.$langs->trans('Date').'</td><td>'.$langs->trans('Action').'</td><td>'.$langs->trans('By').'</td></tr>';
	  print "\n";

	  while ($i < $num)
	    {
	      $objp = $db->fetch_object($resql);
	      $var=!$var;
	      print '<tr '.$bc[$var].'>';
	      print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$objp->id.'">'.img_object($langs->trans('ShowTask'),'task').' '.$objp->id.'</a></td>';
	      print '<td>'.dolibarr_print_date($objp->da)."</td>\n";
	      print '<td>'.stripslashes($objp->label).'</td>';
	      $authoract = new User($db);
	      $authoract->id = $objp->fk_user_author;
	      $authoract->fetch('');
	      print '<td>'.$authoract->code.'</td>';
	      print "</tr>\n";
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
   * Action presend
   *
   */
  if ($_GET['action'] == 'presend')
    {
      print '<br>';
      print_titre($langs->trans('SendPropalByMail'));

      $liste[0]="&nbsp;";
      foreach ($societe->contact_email_array() as $key=>$value) {
	$liste[$key]=$value;
      }

      // Créé l'objet formulaire mail
      include_once('../html.formmail.class.php');
      $formmail = new FormMail($db);
      $formmail->fromname = $user->fullname;
      $formmail->frommail = $user->email;
      $formmail->withfrom=1;
      $formmail->withto=$liste;
      $formmail->withcc=1;
      $formmail->withtopic=$langs->trans('SendPropalRef','__PROPREF__');
      $formmail->withfile=1;
      $formmail->withbody=1;
      // Tableau des substitutions
      $formmail->substit['__PROPREF__']=$propal->ref;
      // Tableau des paramètres complémentaires
      $formmail->param['action']='send';
      $formmail->param['models']='propal_send';
      $formmail->param['propalid']=$propal->id;
      $formmail->param['returnurl']=DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id;

      $formmail->show_form();
    }

}
else
{
  /****************************************************************************
   *                                                                          *
   *                         Mode Liste des propales                          *
   *                                                                          *
   ****************************************************************************/

  $sortorder=$_GET['sortorder'];
  $sortfield=$_GET['sortfield'];
  $page=$_GET['page'];
  $viewstatut=$_GET['viewstatut'];

  if (! $sortfield) $sortfield='p.datep';
  if (! $sortorder) $sortorder='DESC';
  $limit = $conf->liste_limit;
  $offset = $limit * $page ;
  $pageprev = $page - 1;
  $pagenext = $page + 1;

  $sql = 'SELECT s.nom, s.idp, s.client, p.rowid as propalid, p.price, p.ref, p.fk_statut, '.$db->pdate('p.datep').' as dp,'.$db->pdate('p.fin_validite').' as dfv';
  $sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'propal as p';
  $sql.= ' WHERE p.fk_soc = s.idp';

  if (!empty($_GET['search_ref']))
    {
      $sql .= " AND p.ref LIKE '%".$_GET['search_ref']."%'";
    }
  if (!empty($_GET['search_societe']))
    {
      $sql .= " AND s.nom LIKE '%".$_GET['search_societe']."%'";
    }
  if (!empty($_GET['search_montant_ht']))
    {
      $sql .= " AND p.price='".$_GET['search_montant_ht']."'";
    }
  if ($_GET['socidp'])
    { 
      $sql .= ' AND s.idp = '.$_GET['socidp']; 
    }
  if ($_GET['viewstatut'] <> '')
    {
      $sql .= ' AND p.fk_statut in ('.$_GET['viewstatut'].')'; 
    }
  if ($month > 0)
    {
      $sql .= " AND date_format(p.datep, '%Y-%m') = '$year-$month'";
    }
  if ($year > 0)
    {
      $sql .= " AND date_format(p.datep, '%Y') = $year";
    }
  if (strlen($_POST['sf_ref']) > 0)
    {
      $sql .= " AND p.ref like '%".$_POST["sf_ref"] . "%'";
    }
  $sql .= ' ORDER BY '.$sortfield.' '.$sortorder.', p.ref DESC';
  $sql .= $db->plimit($limit + 1,$offset);
  $result=$db->query($sql);

  if ($result)
    {
      $num = $db->num_rows($result);
      print_barre_liste($langs->trans('ListOfProposals'), $page,'propal.php','&amp;socidp='.$socidp,$sortfield,$sortorder,'',$num);
      $i = 0;
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print_liste_field_titre($langs->trans('Ref'),'propal.php','p.ref','','&amp;socidp='.$socidp.'&amp;viewstatut='.$viewstatut,'',$sortfield);
      print_liste_field_titre($langs->trans('Company'),'propal.php','s.nom','','&amp;socidp='.$socidp.'&amp;viewstatut='.$viewstatut,'',$sortfield);
      print_liste_field_titre($langs->trans('Date'),'propal.php','p.datep','','&amp;socidp='.$socidp.'&amp;viewstatut='.$viewstatut, 'align="center"',$sortfield);
      print_liste_field_titre($langs->trans('DateEndPropalShort'),'propal.php','dfv','','&amp;socidp='.$socidp.'&amp;viewstatut='.$viewstatut, 'align="center"',$sortfield);
      print_liste_field_titre($langs->trans('Price'),'propal.php','p.price','','&amp;socidp='.$socidp.'&amp;viewstatut='.$viewstatut, 'align="right"',$sortfield);
      print_liste_field_titre($langs->trans('Status'),'propal.php','p.fk_statut','','&amp;socidp='.$socidp.'&amp;viewstatut='.$viewstatut,'align="center"',$sortfield);
      print "</tr>\n";
      // Lignes des champs de filtre
      print '<form method="get" action="propal.php">';
      print '<tr class="liste_titre">';
      print '<td valign="right">';
      print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_GET['search_ref'].'">';
      print '</td>';
      print '<td align="left">';
      print '<input class="flat" type="text" size="40" name="search_societe" value="'.$_GET['search_societe'].'">';
      print '</td>';
      print '<td colspan="2">&nbsp;</td>';
      print '<td align="right">';
      print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$_GET['search_montant_ht'].'">';
      print '</td>';
      print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
      print '</td>';
      print "</tr>\n";
      print '</form>';

      $var=true;

      while ($i < min($num,$limit))
	{
	  $objp = $db->fetch_object($result);
	  $now = time();
	  $var=!$var;
	  print '<tr '.$bc[$var].'>';
	  print '<td><a href="propal.php?propalid='.$objp->propalid.'">'.img_object($langs->trans('ShowPropal'),'propal').' '.$objp->ref."</a></td>\n";

	  if ($objp->client == 1)
	    {
	      $url = DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->idp;
	    }
	  else
	    {
	      $url = DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$objp->idp;
	    }
	  print '<td><a href="'.$url.'">'.img_object($langs->trans('ShowCompany'),'company').' '.$objp->nom.'</a></td>';

	  // Date propale
	  print '<td align="center">';
	  $y = strftime('%Y',$objp->dp);
	  $m = strftime('%m',$objp->dp);

	  print strftime('%d',$objp->dp)."\n";
	  print ' <a href="propal.php?year='.$y.'&amp;month='.$m.'">';
	  print dolibarr_print_date($objp->dp,'%b')."</a>\n";
	  print ' <a href="propal.php?year='.$y.'">';
	  print strftime('%Y',$objp->dp)."</a></td>\n";      

	  // Date fin validite
	  if ( $now > $objp->dfv && $objp->dfv > 0 )
	    {
	      print '<td align="center">'.dolibarr_print_date($objp->dfv);
		  if ($objp->fk_statut == 1 && $objp->dfv < (time() - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
	      print '</td>';
	    }
	  else
	    {
	      print '<td>&nbsp;</td>';
	    }

	  print '<td align="right">'.price($objp->price)."</td>\n";
	  $propal=New Propal($db);
	  print '<td align="center">'.$propal->LibStatut($objp->fk_statut,0)."</td>\n";
	  print "</tr>\n";

	  $total = $total + $objp->price;
	  $subtotal = $subtotal + $objp->price;

	  $i++;
	}
      print '</table>';
      $db->free($result);
    }
  else
    {
      dolibarr_print_error($db);
    }
}
$db->close();

llxFooter('$Date$ - $Revision$');

?>
