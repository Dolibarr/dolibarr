<?php
/* Copyright (C) 2003-2008 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Code identique a /expedition/shipment.php

/**
 *	\file       htdocs/expedition/fiche.php
 *	\ingroup    expedition
 *	\brief      Fiche descriptive d'une expedition
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/modules/expedition/pdf/ModelePdfExpedition.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/sendings.lib.php");
if ($conf->product->enabled || $conf->service->enabled)  require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
if ($conf->propal->enabled)   require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
if ($conf->stock->enabled)    require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");

$langs->load("sendings");
$langs->load("companies");
$langs->load("bills");
$langs->load('deliveries');
$langs->load('orders');
$langs->load('stocks');
$langs->load('other');
$langs->load('propal');

$origin		= GETPOST("origin")?GETPOST("origin"):'expedition';   // Example: commande, propal
$origin_id 	= GETPOST("id")?GETPOST("id"):'';
if (empty($origin_id)) $origin_id  = GETPOST("origin_id");    // Id of order or propal
if (empty($origin_id)) $origin_id  = GETPOST("object_id");    // Id of order or propal
$id = $origin_id;

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,$origin,$origin_id);

$action		= GETPOST("action");
$confirm	= GETPOST("confirm");

$object = new Expedition($db);

/*
 * Actions
 */

if ($action == 'add')
{
	$db->begin();

	$object->note				= $_POST["note"];
	$object->origin				= $origin;
	$object->origin_id			= $origin_id;
	$object->weight				= $_POST["weight"]==""?"NULL":$_POST["weight"];
	$object->sizeH				= $_POST["sizeH"]==""?"NULL":$_POST["sizeH"];
	$object->sizeW				= $_POST["sizeW"]==""?"NULL":$_POST["sizeW"];
	$object->sizeS				= $_POST["sizeS"]==""?"NULL":$_POST["sizeS"];
	$object->size_units			= $_POST["size_units"];
	$object->weight_units		= $_POST["weight_units"];

	$date_delivery = dol_mktime($_POST["date_deliveryhour"], $_POST["date_deliverymin"], 0, $_POST["date_deliverymonth"], $_POST["date_deliveryday"], $_POST["date_deliveryyear"]);

	// On va boucler sur chaque ligne du document d'origine pour completer objet expedition
	// avec info diverses + qte a livrer
	$classname = ucfirst($object->origin);
	$objectsrc = new $classname($db);
	$objectsrc->fetch($object->origin_id);
	//$object->fetch_lines();

	$object->socid					= $objectsrc->socid;
	$object->ref_customer			= $objectsrc->ref_client;
	$object->date_delivery			= $date_delivery;	// Date delivery planed
	$object->fk_delivery_address	= $objectsrc->fk_delivery_address;
	$object->expedition_method_id	= $_POST["expedition_method_id"];
	$object->tracking_number		= $_POST["tracking_number"];
	$object->ref_int				= $_POST["ref_int"];

	//var_dump($_POST);exit;
	$num=count($objectsrc->lines);
	for ($i = 0; $i < $num; $i++)
	{
		$qty = "qtyl".$i;
		if ($_POST[$qty] > 0)
		{
			$ent = "entl".$i;
			$idl = "idl".$i;
			$entrepot_id = isset($_POST[$ent])?$_POST[$ent]:$_POST["entrepot_id"];

			$object->addline($entrepot_id,$_POST[$idl],$_POST[$qty]);
		}
	}

	$ret=$object->create($user);
	if ($ret > 0)
	{
		$db->commit();
		Header("Location: fiche.php?id=".$object->id);
		exit;
	}
	else
	{
		$db->rollback();
		$mesg='<div class="error">'.$object->error.'</div>';
		$_GET["commande_id"]=$_POST["commande_id"];
		$action='create';
	}
}

/*
 * Build a receiving receipt
 */
if ($action == 'create_delivery' && $conf->livraison_bon->enabled && $user->rights->expedition->livraison->creer)
{
	$object->fetch($id);
	$result = $object->create_delivery($user);
	if ($result > 0)
	{
		Header("Location: ".DOL_URL_ROOT.'/livraison/fiche.php?id='.$result);
		exit;
	}
	else
	{
		$mesg=$object->error;
	}
}

if ($action == 'confirm_valid' && $confirm == 'yes' && $user->rights->expedition->valider)
{
	$object->fetch($id);
	$object->fetch_thirdparty();

	$result = $object->valid($user);

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) $result=expedition_pdf_create($db,$object,$object->modelpdf,$outputlangs);
	if ($result < 0)
	{
		dol_print_error($db,$result);
		exit;
	}
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->expedition->supprimer)
{
	$object->fetch($id);
	$result = $object->delete();
	if ($result > 0)
	{
		Header("Location: ".DOL_URL_ROOT.'/expedition/index.php');
		exit;
	}
	else
	{
		$mesg = $object->error;
	}
}

if ($action == 'reopen' && $user->rights->expedition->valider)
{
	$object->fetch($id);
	$result = $object->setStatut(0);
	if ($result < 0)
	{
		$mesg = $object->error;
	}
}

if ($action == 'setdate_livraison' && $user->rights->expedition->creer)
{
	//print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
	$datedelivery=dol_mktime($_POST['liv_hour'], $_POST['liv_min'], 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']);

	$object->fetch($id);
	$result=$object->set_date_livraison($user,$datedelivery);
	if ($result < 0)
	{
		$mesg='<div class="error">'.$object->error.'</div>';
	}
}

// Action update description of emailing
if ($action == 'settrackingnumber' || $action == 'settrackingurl'
|| $action == 'settrueWeight'
|| $action == 'settrueWidth'
|| $action == 'settrueHeight'
|| $action == 'settrueDepth'
|| $action == 'setexpedition_method_id')
{
	$error=0;

	$shipping = new Expedition($db);
	$result=$shipping->fetch($id);
	if ($result < 0) dol_print_error($db,$shipping->error);

	if ($action == 'settrackingnumber')			$shipping->tracking_number = trim($_REQUEST["trackingnumber"]);
	if ($action == 'settrackingurl')			$shipping->tracking_url = trim($_REQUEST["trackingurl"]);
	if ($action == 'settrueWeight')				$shipping->trueWeight = trim($_REQUEST["trueWeight"]);
	if ($action == 'settrueWidth')				$shipping->trueWidth = trim($_REQUEST["trueWidth"]);
	if ($action == 'settrueHeight')				$shipping->trueHeight = trim($_REQUEST["trueHeight"]);
	if ($action == 'settrueDepth')				$shipping->trueDepth = trim($_REQUEST["trueDepth"]);
	if ($action == 'setexpedition_method_id')	$shipping->expedition_method_id = trim($_REQUEST["expedition_method_id"]);

	if (! $error)
	{
		if ($shipping->update($user) >= 0)
		{
			Header("Location: fiche.php?id=".$shipping->id);
			exit;
		}
		$mesg=$shipping->error;
	}

	$mesg='<div class="error">'.$mesg.'</div>';
	$action="";
}


/*
 * Build doc
 */
if ($action == 'builddoc')	// En get ou en post
{
	require_once(DOL_DOCUMENT_ROOT."/core/modules/expedition/pdf/ModelePdfExpedition.class.php");

	// Sauvegarde le dernier modele choisi pour generer un document
	$shipment = new Expedition($db);
	$shipment->fetch($id);
	$shipment->fetch_thirdparty();

	if ($_REQUEST['model'])
	{
		$shipment->setDocModel($user, $_REQUEST['model']);
	}

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$shipment->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	$result=expedition_pdf_create($db,$shipment,$_REQUEST['model'],$outputlangs);
	if ($result <= 0)
	{
		dol_print_error($db,$result);
		exit;
	}
}

/*
 * Add file in email form
 */
if ($_POST['addfile'])
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

    // Set tmp user directory TODO Use a dedicated directory for temp mails files
    $vardir=$conf->user->dir_output."/".$user->id;
    $upload_dir_tmp = $vardir.'/temp';

    $mesg=dol_add_file_process($upload_dir_tmp,0,0);

    $action ='presend';
}

/*
 * Remove file in email form
 */
if (! empty($_POST['removedfile']))
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

    // Set tmp user directory
    $vardir=$conf->user->dir_output."/".$user->id;
    $upload_dir_tmp = $vardir.'/temp';

    $mesg=dol_remove_file_process($_POST['removedfile'],0);

    $action ='presend';
}

/*
 * Send mail
 */
if ($action == 'send' && ! $_POST['addfile'] && ! $_POST['removedfile'] && ! $_POST['cancel'])
{
    $langs->load('mails');

    $result=$object->fetch($id);
    $result=$object->fetch_thirdparty();

    if ($result > 0)
    {
        $ref = dol_sanitizeFileName($object->ref);
        $file = $conf->expedition->dir_output . '/sending/' . $ref . '/' . $ref . '.pdf';

        if (is_readable($file))
        {
            if ($_POST['sendto'])
            {
                // Le destinataire a ete fourni via le champ libre
                $sendto = $_POST['sendto'];
                $sendtoid = 0;
            }
            elseif ($_POST['receiver'] != '-1')
            {
                // Recipient was provided from combo list
                if ($_POST['receiver'] == 'thirdparty') // Id of third party
                {
                    $sendto = $object->client->email;
                    $sendtoid = 0;
                }
                else	// Id du contact
                {
                    $sendto = $object->client->contact_get_property($_POST['receiver'],'email');
                    $sendtoid = $_POST['receiver'];
                }
            }

            if (dol_strlen($sendto))
            {
                $langs->load("commercial");

                $from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
                $replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
                $message = $_POST['message'];
                $sendtocc = $_POST['sendtocc'];
                $deliveryreceipt = $_POST['deliveryreceipt'];

                if ($_POST['action'] == 'send')
                {
                    if (dol_strlen($_POST['subject'])) $subject=$_POST['subject'];
                    else $subject = $langs->transnoentities('Shipping').' '.$object->ref;
                    $actiontypecode='AC_SHIP';
                    $actionmsg = $langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto.".\n";
                    if ($message)
                    {
                        $actionmsg.=$langs->transnoentities('MailTopic').": ".$subject."\n";
                        $actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody').":\n";
                        $actionmsg.=$message;
                    }
                    $actionmsg2=$langs->transnoentities('Action'.$actiontypecode);
                }

                // Create form object
                include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
                $formmail = new FormMail($db);

                $attachedfiles=$formmail->get_attached_files();
                $filepath = $attachedfiles['paths'];
                $filename = $attachedfiles['names'];
                $mimetype = $attachedfiles['mimes'];

                // Send mail
                require_once(DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php');
                $mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt);
                if ($mailfile->error)
                {
                    $mesg='<div class="error">'.$mailfile->error.'</div>';
                }
                else
                {
                    $result=$mailfile->sendfile();
                    if ($result)
                    {
                        $_SESSION['mesg']=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));

                        $error=0;

                        // Initialisation donnees
                        $object->sendtoid		= $sendtoid;
                        $object->actiontypecode	= $actiontypecode;
                        $object->actionmsg		= $actionmsg;
                        $object->actionmsg2		= $actionmsg2;
                        $object->fk_element		= $object->id;
						$object->elementtype	= $object->element;

                        // Appel des triggers
                        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                        $interface=new Interfaces($db);
                        $result=$interface->run_triggers('SHIPPING_SENTBYMAIL',$object,$user,$langs,$conf);
                        if ($result < 0) { $error++; $this->errors=$interface->errors; }
                        // Fin appel triggers

                        if ($error)
                        {
                            dol_print_error($db);
                        }
                        else
                        {
                            // Redirect here
                            // This avoid sending mail twice if going out and then back to page
                            Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
                            exit;
                        }
                    }
                    else
                    {
                        $langs->load("other");
                        $mesg='<div class="error">';
                        if ($mailfile->error)
                        {
                            $mesg.=$langs->trans('ErrorFailedToSendMail',$from,$sendto);
                            $mesg.='<br>'.$mailfile->error;
                        }
                        else
                        {
                            $mesg.='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
                        }
                        $mesg.='</div>';
                    }
                }
            }
            else
            {
                $langs->load("other");
                $mesg='<div class="error">'.$langs->trans('ErrorMailRecipientIsEmpty').' !</div>';
                $action='presend';
                dol_syslog('Recipient email is empty');
            }
        }
        else
        {
            $langs->load("errors");
            $mesg='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
            dol_syslog('Failed to read file: '.$file);
        }
    }
    else
    {
        $langs->load("other");
        $mesg='<div class="error">'.$langs->trans('ErrorFailedToReadEntity',$langs->trans("Shipping")).'</div>';
        dol_syslog($langs->trans('ErrorFailedToReadEntity',$langs->trans("Shipping")));
    }
}


/*
 * View
 */

llxHeader('',$langs->trans('Sending'),'Expedition');

$form = new Form($db);
$formfile = new FormFile($db);
$formproduct = new FormProduct($db);

if ($action == 'create2')
{
	print_fiche_titre($langs->trans("CreateASending")).'<br>';
    print $langs->trans("ShipmentCreationIsDoneFromOrder");
    $action=''; $id=''; $ref='';
}

// Mode creation
if ($action == 'create')
{
	$expe = new Expedition($db);

	print_fiche_titre($langs->trans("CreateASending"));
	if (! $origin)
	{
		$mesg='<div class="error">'.$langs->trans("ErrorBadParameters").'</div>';
	}

	dol_htmloutput_mesg($mesg);

	if ($origin)
	{
		$classname = ucfirst($origin);

		$object = new $classname($db);

		if ($object->fetch($origin_id))	// This include the fetch_lines
		{
			//var_dump($object);

			$soc = new Societe($db);
			$soc->fetch($object->socid);

			$author = new User($db);
			$author->fetch($object->user_author_id);

			if ($conf->stock->enabled) $entrepot = new Entrepot($db);

			/*
			 *   Document source
			 */
			print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="add">';
			print '<input type="hidden" name="origin" value="'.$origin.'">';
			print '<input type="hidden" name="origin_id" value="'.$object->id.'">';
			print '<input type="hidden" name="ref_int" value="'.$object->ref_int.'">';
			if ($_GET["entrepot_id"])
			{
				print '<input type="hidden" name="entrepot_id" value="'.$_GET["entrepot_id"].'">';
			}

			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="30%" class="fieldrequired">';
			if ($origin == 'commande' && $conf->commande->enabled)
			{
				print $langs->trans("RefOrder").'</td><td colspan="3"><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$object->id.'">'.img_object($langs->trans("ShowOrder"),'order').' '.$object->ref;
			}
			if ($origin == 'propal' && $conf->propal->enabled)
			{
				print $langs->trans("RefProposal").'</td><td colspan="3"><a href="'.DOL_URL_ROOT.'/comm/fiche.php?id='.$object->id.'">'.img_object($langs->trans("ShowProposal"),'propal').' '.$object->ref;
			}
			print '</a></td>';
			print "</tr>\n";

			// Ref client
			print '<tr><td>';
			print $langs->trans('RefCustomer').'</td><td colspan="3">';
			print $object->ref_client;
			print '</td>';
			print '</tr>';

			// Tiers
			print '<tr><td class="fieldrequired">'.$langs->trans('Company').'</td>';
			print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
			print '</tr>';

			// Date delivery planned
			print '<tr><td class="fieldrequired">'.$langs->trans("DateDeliveryPlanned").'</td>';
			print '<td colspan="3">';
			//print dol_print_date($object->date_livraison,"day");	// date_livraison come from order and will be stored into date_delivery planed.
			print $form->select_date($object->date_livraison?$object->date_livraison:-1,'date_delivery',1,1);
			print "</td>\n";
			print '</tr>';

			// Delivery address
			if (($origin == 'commande' && $conf->global->COMMANDE_ADD_DELIVERY_ADDRESS)
				|| ($origin == 'propal' && $conf->global->PROPAL_ADD_DELIVERY_ADDRESS))
			{
				print '<tr><td>'.$langs->trans('DeliveryAddress').'</td>';
				print '<td colspan="3">';
				if (!empty($object->fk_delivery_address))
				{
					$form->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,$_GET['socid'],'none','commande',$object->id);
				}
				print '</td></tr>'."\n";
			}

			// Note
			if ($object->note && ! $user->societe_id)
			{
				print '<tr><td>'.$langs->trans("NotePrivate").'</td>';
				print '<td colspan="3">'.nl2br($object->note)."</td></tr>";
			}

			// Weight
			print '<tr><td>';
			print $langs->trans("Weight");
			print '</td><td><input name="weight" size="4" value="'.$_POST["weight"].'"></td><td>';
			print $formproduct->select_measuring_units("weight_units","weight",$_POST["weight_units"]);
			print '</td></tr><tr><td>';
			print $langs->trans("Width");
			print ' </td><td><input name="sizeW" size="4" value="'.$_POST["sizeW"].'"></td><td rowspan="3">';
			print $formproduct->select_measuring_units("size_units","size");
			print '</td></tr><tr><td>';
			print $langs->trans("Height");
			print '</td><td><input name="sizeH" size="4" value="'.$_POST["sizeH"].'"></td>';
			print '</tr><tr><td>';
			print $langs->trans("Depth");
			print '</td><td><input name="sizeS" size="4" value="'.$_POST["sizeS"].'"></td>';
			print '</tr>';

			// Delivery method
			print "<tr><td>".$langs->trans("DeliveryMethod")."</td>";
			print '<td colspan="3">';
			$expe->fetch_delivery_methods();
			print $form->selectarray("expedition_method_id",$expe->meths,$_POST["expedition_method_id"],1,0,0,"",1);
			print "</td></tr>\n";

			// Tracking number
			print "<tr><td>".$langs->trans("TrackingNumber")."</td>";
			print '<td colspan="3">';
			print '<input name="tracking_number" size="20" value="'.$_POST["tracking_number"].'">';
			print "</td></tr>\n";

			print "</table>";

			/*
			 * Lignes de commandes
			 *
			 */
			print '<br><table class="nobordernopadding" width="100%">';

			//$lines = $object->fetch_lines(1);
			$numAsked = count($object->lines);

			/* Lecture des expeditions deja effectuees */
			$object->loadExpeditions();

			if ($numAsked)
			{
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Description").'</td>';
				print '<td align="center">'.$langs->trans("QtyOrdered").'</td>';
				print '<td align="center">'.$langs->trans("QtyShipped").'</td>';
				print '<td align="left">'.$langs->trans("QtyToShip").'</td>';
				if ($conf->stock->enabled)
				{
					print '<td align="left">'.$langs->trans("Warehouse").' / '.$langs->trans("Stock").'</td>';
				}
				print "</tr>\n";
			}

			$product_static = new Product($db);

			$var=true;
			$indiceAsked = 0;
			while ($indiceAsked < $numAsked)
			{
				$product = new Product($db);

				$line = $object->lines[$indiceAsked];
				$var=!$var;

				// Show product and description
				$type=$line->product_type?$line->product_type:$line->fk_product_type;
				// Try to enhance type detection using date_start and date_end for free lines where type
				// was not saved.
				if (! empty($line->date_start)) $type=1;
				if (! empty($line->date_end)) $type=1;

				print "<tr ".$bc[$var].">\n";

				// Product label
				if ($line->fk_product > 0)
				{
					$product->fetch($line->fk_product);
					$product->load_stock();

					print '<td>';
					print '<a name="'.$line->rowid.'"></a>'; // ancre pour retourner sur la ligne

					// Show product and description
					$product_static->type=$line->fk_product_type;
					$product_static->id=$line->fk_product;
					$product_static->ref=$line->ref;
					$product_static->libelle=$line->product_label;
					$text=$product_static->getNomUrl(1);
					$text.= ' - '.$line->product_label;
					$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($line->desc));
					print $form->textwithtooltip($text,$description,3,'','',$i);

					// Show range
					print_date_range($db->jdate($line->date_start),$db->jdate($line->date_end));

					// Add description in form
					if ($conf->global->PRODUIT_DESC_IN_FORM)
					{
						print ($line->desc && $line->desc!=$line->product_label)?'<br>'.dol_htmlentitiesbr($line->desc):'';
					}

					print '</td>';
				}
				else
				{
					print "<td>";
					if ($type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');
					print $text.' '.nl2br($line->desc);

					// Show range
					print_date_range($db->jdate($line->date_start),$db->jdate($line->date_end));
					print "</td>\n";
				}

				// Qty
				print '<td align="center">'.$line->qty.'</td>';
				$qtyProdCom=$line->qty;

				// Qty already sent
				print '<td align="center">';
				$quantityDelivered = $object->expeditions[$line->id];
				print $quantityDelivered;
				print '</td>';

				$quantityAsked = $line->qty;
				$quantityToBeDelivered = $quantityAsked - $quantityDelivered;

				$defaultqty=0;
				if ($_REQUEST["entrepot_id"])
				{
					//var_dump($product);
					$stock = $product->stock_warehouse[$_REQUEST["entrepot_id"]]->real;
					$stock+=0;  // Convertit en numerique
					$defaultqty=min($quantityToBeDelivered, $stock);
					if (($line->product_type == 1 && empty($conf->global->STOCK_SUPPORTS_SERVICES)) || $defaultqty < 0) $defaultqty=0;
				}

				// Quantity to send
				print '<td align="left">';
				if ($line->product_type == 0 || ! empty($conf->global->STOCK_SUPPORTS_SERVICES))
				{
					print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';
					print '<input name="qtyl'.$indiceAsked.'" type="text" size="4" value="'.$defaultqty.'">';
				}
				else print '0';
				print '</td>';

				// Stock
				if ($conf->stock->enabled)
				{
					print '<td align="left">';
					if ($line->product_type == 0 || ! empty($conf->global->STOCK_SUPPORTS_SERVICES))
					{
						// Show warehous
						if ($_REQUEST["entrepot_id"])
						{
							print $formproduct->selectWarehouses($_REQUEST["entrepot_id"],'entl'.$indiceAsked,'',1,0,$line->fk_product);
							//print $stock.' '.$quantityToBeDelivered;
							//if ($stock >= 0 && $stock < $quantityToBeDelivered)
							if ($stock < $quantityToBeDelivered)
							{
								print ' '.img_warning($langs->trans("StockTooLow"));
							}
						}
						else
						{
							print $formproduct->selectWarehouses('','entl'.$indiceAsked,'',1,0,$line->fk_product);
						}
					}
					else
					{
						print $langs->trans("Service");
					}
					print '</td>';
				}
				/*else
				{
					// Quantity
					print '<td align="center" '.$colspan.'>';
					print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';
					print '<input name="qtyl'.$indiceAsked.'" type="text" size="4" value="'.$quantityToBeDelivered.'">';
					print '</td>';
					if ($line->product_type == 1) print '<td>&nbsp;</td>';
				}*/

				print "</tr>\n";

				// Show subproducts of product
				if (! empty($conf->global->PRODUIT_SOUSPRODUITS) && $line->fk_product > 0)
				{
					$product->get_sousproduits_arbo();
					$prods_arbo = $product->get_arbo_each_prod($qtyProdCom);
					if(count($prods_arbo) > 0)
					{
						foreach($prods_arbo as $key => $value)
						{
							//print $value[0];
							$img='';
							if ($value['stock'] < $value['stock_alert'])
							{
								$img=img_warning($langs->trans("StockTooLow"));
							}
							print "<tr><td>&nbsp; &nbsp; &nbsp; ->
                                <a href=\"".DOL_URL_ROOT."/product/fiche.php?id=".$value['id']."\">".$value['fullpath']."
                                </a> (".$value['nb'].")</td><td align=\"center\"> ".$value['nb_total']."</td><td>&nbsp</td><td>&nbsp</td>
                                <td align=\"center\">".$value['stock']." ".$img."</td></tr>";
						}
					}
				}

				$indiceAsked++;
			}

			print '<tr><td align="center" colspan="5"><br><input type="submit" class="button" value="'.$langs->trans("Create").'"></td></tr>';
			print "</table>";
			print '</form>';
		}
		else
		{
			dol_print_error($db);
		}
	}
}
else
/* *************************************************************************** */
/*                                                                             */
/* Edit and view mode                                                          */
/*                                                                             */
/* *************************************************************************** */
{
	if (! empty($id) || ! empty($ref))
	{
		$result = $object->fetch($id,$ref);
		if ($result < 0)
		{
			dol_print_error($db,$object->error);
			exit -1;
		}
		$lines = $object->lines;
		$num_prod = count($lines);

		if ($object->id > 0)
		{
			dol_htmloutput_mesg($mesg);

			if (!empty($object->origin))
			{
				$typeobject = $object->origin;
				$origin = $object->origin;
				$object->fetch_origin();
			}

			$soc = new Societe($db);
			$soc->fetch($object->socid);

			// delivery link
			$object->fetchObjectLinked($object->id,$object->element,-1,-1);

			$head=shipping_prepare_head($object);
			dol_fiche_head($head, 'shipping', $langs->trans("Sending"), 0, 'sending');

			if ($mesg) print $mesg;

			/*
			 * Confirmation de la suppression
			 */
			if ($action == 'delete')
			{
				$ret=$form->form_confirm($_SERVER['PHP_SELF'].'?id='.$object->id,$langs->trans('DeleteSending'),$langs->trans("ConfirmDeleteSending",$object->ref),'confirm_delete','',0,1);
				if ($ret == 'html') print '<br>';
			}

			/*
			 * Confirmation de la validation
			 */
			if ($action == 'valid')
			{
				$objectref = substr($object->ref, 1, 4);
				if ($objectref == 'PROV')
				{
					$numref = $object->getNextNumRef($soc);
				}
				else
				{
					$numref = $object->ref;
				}
				$ret=$form->form_confirm($_SERVER['PHP_SELF'].'?id='.$object->id,$langs->trans('ValidateSending'),$langs->trans("ConfirmValidateSending",$numref),'confirm_valid','',0,1);
				if ($ret == 'html') print '<br>';
			}
			/*
			 * Confirmation de l'annulation
			 */
			if ($action == 'annuler')
			{
				$ret=$form->form_confirm($_SERVER['PHP_SELF'].'?id='.$object->id,$langs->trans('CancelSending'),$langs->trans("ConfirmCancelSending",$object->ref),'confirm_cancel','',0,1);
				if ($ret == 'html') print '<br>';
			}

			// Calculate ture totalVeight and totalVolume for all products
			// by adding weight and volume of each line.
			$totalWeight = '';
			$totalVolume = '';
			$weightUnit=0;
			$volumeUnit=0;
			for ($i = 0 ; $i < $num_prod ; $i++)
			{
				$weightUnit=0;
				$volumeUnit=0;
				if (! empty($lines[$i]->weight_units)) $weightUnit = $lines[$i]->weight_units;
				if (! empty($lines[$i]->volume_units)) $volumeUnit = $lines[$i]->volume_units;
				// TODO Use a function addvalueunits(val1,unit1,val2,unit2)=>(val,unit)
				if ($lines[$i]->weight_units < 50)
				{
					$trueWeightUnit=pow(10,$weightUnit);
					$totalWeight += $lines[$i]->weight*$lines[$i]->qty_shipped*$trueWeightUnit;
				}
				else
				{
					$trueWeightUnit=$weightUnit;
					$totalWeight += $lines[$i]->weight*$lines[$i]->qty_shipped;
				}
				if ($lines[$i]->volume_units < 50)
				{
					//print $lines[$i]->volume."x".$lines[$i]->volume_units."x".($lines[$i]->volume_units < 50)."x".$volumeUnit;
					$trueVolumeUnit=pow(10,$volumeUnit);
					//print $lines[$i]->volume;
					$totalVolume += $lines[$i]->volume*$lines[$i]->qty_shipped*$trueVolumeUnit;
				}
				else
				{
					$trueVolumeUnit=$volumeUnit;
					$totalVolume += $lines[$i]->volume*$lines[$i]->qty_shipped;
				}
			}
			$totalVolume=$totalVolume;
			//print "totalVolume=".$totalVolume." volumeUnit=".$volumeUnit;

			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
			print '<td colspan="3">';
			print $form->showrefnav($object,'ref','',1,'ref','ref');
			print '</td></tr>';

			// Customer
			print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
			print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
			print "</tr>";

			// Linked documents
			if ($typeobject == 'commande' && $object->$typeobject->id && $conf->commande->enabled)
			{
				print '<tr><td>';
				$objectsrc=new Commande($db);
				$objectsrc->fetch($object->$typeobject->id);
				print $langs->trans("RefOrder").'</td>';
				print '<td colspan="3">';
				print $objectsrc->getNomUrl(1,'commande');
				print "</td>\n";
				print '</tr>';
			}
			if ($typeobject == 'propal' && $object->$typeobject->id && $conf->propal->enabled)
			{
				print '<tr><td>';
				$objectsrc=new Propal($db);
				$objectsrc->fetch($object->$typeobject->id);
				print $langs->trans("RefProposal").'</td>';
				print '<td colspan="3">';
				print $objectsrc->getNomUrl(1,'expedition');
				print "</td>\n";
				print '</tr>';
			}

			// Ref customer
			print '<tr><td>'.$langs->trans("RefCustomer").'</td>';
			print '<td colspan="3">'.$object->ref_customer."</a></td>\n";
			print '</tr>';

			// Date creation
			print '<tr><td>'.$langs->trans("DateCreation").'</td>';
			print '<td colspan="3">'.dol_print_date($object->date_creation,"daytext")."</td>\n";
			print '</tr>';

			// Delivery date planed
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('DateDeliveryPlanned');
			print '</td>';

			if ($_GET['action'] != 'editdate_livraison') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryDate'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($_GET['action'] == 'editdate_livraison')
			{
				print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="setdate_livraison">';
				$form->select_date($object->date_delivery?$object->date_delivery:-1,'liv_',1,1,'',"setdate_livraison");
				print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
			}
			else
			{
				print $object->date_delivery ? dol_print_date($object->date_delivery,'dayhourtext') : '&nbsp;';
			}
			print '</td>';
			print '</tr>';

			// Delivery address
			if (($origin == 'commande' && $conf->global->COMMANDE_ADD_DELIVERY_ADDRESS)
				|| ($origin == 'propal' && $conf->global->PROPAL_ADD_DELIVERY_ADDRESS))
			{
				print '<tr><td>'.$langs->trans('DeliveryAddress').'</td>';
				print '<td colspan="3">';
				if (!empty($object->fk_delivery_address))
				{
					$form->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,$object->deliveryaddress->socid,'none','shipment',$object->id);
				}
				print '</td></tr>'."\n";
			}

			// Weight
			print '<tr><td>'.$form->editfieldkey("Weight",'trueWeight',$object->trueWeight,'id',$object->id,$user->rights->expedition->creer).'</td><td colspan="3">';
			print $form->editfieldval("Weight",'trueWeight',$object->trueWeight,'id',$object->id,$user->rights->expedition->creer);
			print $object->weight_units?measuring_units_string($object->weight_units,"weight"):'';
			print '</td></tr>';

			// Volume Total
			print '<tr><td>'.$langs->trans("Volume").'</td>';
			print '<td colspan="3">';
			if ($object->trueVolume)
			{
				// If sending volume defined
				print $object->trueVolume.' '.measuring_units_string($object->volumeUnit,"volume");
			}
			else
			{
				// If sending volume not defined we use sum of products
				if ($totalVolume > 0)
				{
					print $totalVolume.' ';
					if ($volumeUnit < 50) print measuring_units_string(0,"volume");
					else print measuring_units_string($volumeUnit,"volume");
				}
				else print '&nbsp;';
			}
			print "</td>\n";
			print '</tr>';

			// Width
			print '<tr><td>'.$form->editfieldkey("Width",'trueWidth',$object->trueWidth,'id',$object->id,$user->rights->expedition->creer).'</td><td colspan="3">';
			print $form->editfieldval("Width",'trueWidth',$object->trueWidth,'id',$object->id,$user->rights->expedition->creer);
			print $object->trueWidth?measuring_units_string($object->width_units,"size"):'';
			print '</td></tr>';

			// Height
			print '<tr><td>'.$form->editfieldkey("Height",'trueHeight',$object->trueHeight,'id',$object->id,$user->rights->expedition->creer).'</td><td colspan="3">';
			print $form->editfieldval("Height",'trueHeight',$object->trueHeight,'id',$object->id,$user->rights->expedition->creer);
			print $object->trueHeight?measuring_units_string($object->height_units,"size"):'';
			print '</td></tr>';

			// Depth
			print '<tr><td>'.$form->editfieldkey("Depth",'trueDepth',$object->trueDepth,'id',$object->id,$user->rights->expedition->creer).'</td><td colspan="3">';
			print $form->editfieldval("Depth",'trueDepth',$object->trueDepth,'id',$object->id,$user->rights->expedition->creer);
			print $object->trueDepth?measuring_units_string($object->depth_units,"size"):'';
			print '</td></tr>';

			// Status
			print '<tr><td>'.$langs->trans("Status").'</td>';
			print '<td colspan="3">'.$object->getLibStatut(4)."</td>\n";
			print '</tr>';

			// Sending method
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('SendingMethod');
			print '</td>';

			if ($_GET['action'] != 'editexpedition_method_id') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editexpedition_method_id&amp;id='.$object->id.'">'.img_edit($langs->trans('SetSendingMethod'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($_GET['action'] == 'editexpedition_method_id')
			{
				print '<form name="setexpedition_method_id" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="setexpedition_method_id">';
				$object->fetch_delivery_methods();
				print $form->selectarray("expedition_method_id",$object->meths,$object->expedition_method_id,1,0,0,"",1);
				print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
			}
			else
			{
				if ($object->expedition_method_id > 0)
				{
					// Get code using getLabelFromKey
					$code=$langs->getLabelFromKey($db,$object->expedition_method_id,'c_shipment_mode','rowid','code');
					print $langs->trans("SendingMethod".strtoupper($code));
				}
			}
			print '</td>';
			print '</tr>';

			// Tracking Number
			print '<tr><td>'.$form->editfieldkey("TrackingNumber",'trackingnumber',$object->tracking_number,'id',$object->id,$user->rights->expedition->creer).'</td><td colspan="3">';
			print $form->editfieldval("TrackingNumber",'trackingnumber',$object->tracking_url,'id',$object->id,$user->rights->expedition->creer,'string',$object->tracking_number);
			print '</td></tr>';

			print "</table>\n";

			/*
			 * Lignes produits
			 */
			print '<br><table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Products").'</td>';
			print '<td align="center">'.$langs->trans("QtyOrdered").'</td>';
			if ($object->fk_statut <= 1)
			{
				print '<td align="center">'.$langs->trans("QtyToShip").'</td>';
			}
			else
			{
				print '<td align="center">'.$langs->trans("QtyShipped").'</td>';
			}

			print '<td align="center">'.$langs->trans("CalculatedWeight").'</td>';
			print '<td align="center">'.$langs->trans("CalculatedVolume").'</td>';
			//print '<td align="center">'.$langs->trans("Size").'</td>';

			if ($conf->stock->enabled)
			{
				print '<td align="left">'.$langs->trans("WarehouseSource").'</td>';
			}
			print "</tr>\n";

			$var=false;

			for ($i = 0 ; $i < $num_prod ; $i++)
			{
				print "<tr ".$bc[$var].">";

				// Predefined product or service
				if ($lines[$i]->fk_product > 0)
				{
					print '<td>';

					// Affiche ligne produit
					$text = '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$lines[$i]->fk_product.'">';
					if ($lines[$i]->fk_product_type==1) $text.= img_object($langs->trans('ShowService'),'service');
					else $text.= img_object($langs->trans('ShowProduct'),'product');
					$text.= ' '.$lines[$i]->ref.'</a>';
					$text.= ' - '.$lines[$i]->label;
					$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($lines[$i]->description));
					//print $description;
					print $form->textwithtooltip($text,$description,3,'','',$i);
					print_date_range($lines[$i]->date_start,$lines[$i]->date_end);
					if ($conf->global->PRODUIT_DESC_IN_FORM)
					{
						print ($lines[$i]->description && $lines[$i]->description!=$lines[$i]->product)?'<br>'.dol_htmlentitiesbr($lines[$i]->description):'';
					}
				}
				else
				{
					print "<td>";
					if ($lines[$i]->fk_product_type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');
					print $text.' '.nl2br($lines[$i]->description);
					print_date_range($lines[$i]->date_start,$lines[$i]->date_end);
					print "</td>\n";
				}

				// Qte commande
				print '<td align="center">'.$lines[$i]->qty_asked.'</td>';

				// Qte a expedier ou expedier
				print '<td align="center">'.$lines[$i]->qty_shipped.'</td>';

				// Weight
				print '<td align="center">';
				if ($lines[$i]->fk_product_type == 0) print $lines[$i]->weight*$lines[$i]->qty_shipped.' '.measuring_units_string($lines[$i]->weight_units,"weight");
				else print '&nbsp;';
				print '</td>';

				// Volume
				print '<td align="center">';
				if ($lines[$i]->fk_product_type == 0) print $lines[$i]->volume*$lines[$i]->qty_shipped.' '.measuring_units_string($lines[$i]->volume_units,"volume");
				else print '&nbsp;';
				print '</td>';

				// Size
				//print '<td align="center">'.$lines[$i]->volume*$lines[$i]->qty_shipped.' '.measuring_units_string($lines[$i]->volume_units,"volume").'</td>';

				// Entrepot source
				if ($conf->stock->enabled)
				{
					print '<td align="left">';
					if ($lines[$i]->entrepot_id > 0)
					{
						$entrepot = new Entrepot($db);
						$entrepot->fetch($lines[$i]->entrepot_id);
						print $entrepot->getNomUrl(1);
					}
					print '</td>';
				}


				print "</tr>";

				$var=!$var;
			}
		}

		print "</table>\n";

		print "\n</div>\n";


		/*
		 *    Boutons actions
		 */

		if ($user->societe_id == 0)
		{
			print '<div class="tabsAction">';

			if ($object->statut == 0 && $num_prod > 0)
			{
				if ($user->rights->expedition->valider)
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valid">'.$langs->trans("Validate").'</a>';
				}
				else
				{
					print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("Validate").'</a>';
				}
			}

			// TODO add alternative status
			/* if ($object->statut == 1 && $user->rights->expedition->valider)
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("ReOpen").'</a>';
			}*/

			// Send
			if ($object->statut == 1)
			{
				$ref = dol_sanitizeFileName($object->ref);
				$file = $conf->expedition->dir_output . '/sending/'.$ref.'/'.$ref.'.pdf';
				if (file_exists($file))
				{
                    if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->expedition->shipping_advance->send)
                    {
					   print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
                    }
                    else print '<a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a>';
				}
			}

			// Create bill and Classify billed
			if ($conf->facture->enabled && $object->statut > 0  && ! $object->billed)
			{
				if ($user->rights->facture->creer)
				{
					// TODO until the module is autonomous
					//print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("CreateBill").'</a>';
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;origin='.$object->origin.'&amp;originid='.$object->origin_id.'&amp;socid='.$object->socid.'">'.$langs->trans("CreateBill").'</a>';
				}

				// TODO add alternative status
				if ($user->rights->expedition->creer && $object->statut > 2)
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=classifybilled">'.$langs->trans("ClassifyBilled").'</a>';
				}
			}

			if ($conf->livraison_bon->enabled && $object->statut == 1 && $user->rights->expedition->livraison->creer && empty($object->linkedObjectsIds))
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=create_delivery">'.$langs->trans("DeliveryOrder").'</a>';
			}

			if ($user->rights->expedition->supprimer)
			{
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
			}

			print '</div>';
			print "<br>\n";
		}

		print '<table width="100%"><tr><td width="50%" valign="top">';


		/*
		 * Documents generated
		 */
		if ($action != 'presend')
		{
			$objectref = dol_sanitizeFileName($object->ref);
			$filedir = $conf->expedition->dir_output . "/sending/" .$objectref;

			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;

			$genallowed=$user->rights->expedition->lire;
			$delallowed=$user->rights->expedition->supprimer;
			//$genallowed=1;
			//$delallowed=0;

			$somethingshown=$formfile->show_documents('expedition',$objectref,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'','','',$soc->default_lang);
			if ($genallowed && ! $somethingshown) $somethingshown=1;

			print '</td><td valign="top" width="50%">';

			// List of actions on element
			include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php');
			$formactions=new FormActions($db);
			$somethingshown=$formactions->showactions($object,'shipping',$socid);

			print '</td></tr></table>';
		}

		/*
		 * Action presend
		 *
		 */
		if ($action == 'presend')
		{
			$ref = dol_sanitizeFileName($object->ref);
			$file = $conf->expedition->dir_output . '/sending/' . $ref . '/' . $ref . '.pdf';

			print '<br>';
			print_titre($langs->trans('SendShippingByEMail'));

			// Cree l'objet formulaire mail
			include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
			$formmail = new FormMail($db);
			$formmail->fromtype = 'user';
			$formmail->fromid   = $user->id;
			$formmail->fromname = $user->getFullName($langs);
			$formmail->frommail = $user->email;
			$formmail->withfrom=1;
			$formmail->withto=empty($_POST["sendto"])?1:$_POST["sendto"];
			$formmail->withtosocid=$soc->id;
			$formmail->withtocc=1;
			$formmail->withtoccsocid=0;
			$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
			$formmail->withtocccsocid=0;
			$formmail->withtopic=$langs->trans('SendShippingRef','__SHIPPINGREF__');
			$formmail->withfile=2;
			$formmail->withbody=1;
			$formmail->withdeliveryreceipt=1;
			$formmail->withcancel=1;
			// Tableau des substitutions
			$formmail->substit['__SHIPPINGREF__']=$object->ref;
			// Tableau des parametres complementaires
			$formmail->param['action']='send';
			$formmail->param['models']='shipping_send';
			$formmail->param['shippingid']=$object->id;
			$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;

			// Init list of files
			if (! empty($_REQUEST["mode"]) && $_REQUEST["mode"]=='init')
			{
				$formmail->clear_attached_files();
				$formmail->add_attached_files($file,dol_sanitizeFilename($ref.'.pdf'),'application/pdf');
			}

			// Show form
			$formmail->show_form();

			print '<br>';
		}

		if ($action != 'presend' && ! empty($origin) && $object->$origin->id)
		{
			print '<br>';
			//show_list_sending_receive($object->origin,$object->origin_id," AND e.rowid <> ".$object->id);
			show_list_sending_receive($object->origin,$object->origin_id);
		}
	}
}

$db->close();

llxFooter();
?>
