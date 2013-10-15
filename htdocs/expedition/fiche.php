<?php
/* Copyright (C) 2003-2008	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Simon TOSSER			<simon@kornog-computing.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2013       Marcos García           <marcosgdf@gmail.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/expedition/fiche.php
 *	\ingroup    expedition
 *	\brief      Fiche descriptive d'une expedition
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))  require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
if (! empty($conf->propal->enabled))   require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->stock->enabled))    require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

$langs->load("sendings");
$langs->load("companies");
$langs->load("bills");
$langs->load('deliveries');
$langs->load('orders');
$langs->load('stocks');
$langs->load('other');
$langs->load('propal');

$origin		= GETPOST('origin','alpha')?GETPOST('origin','alpha'):'expedition';   // Example: commande, propal
$origin_id 	= GETPOST('id','int')?GETPOST('id','int'):'';
$id = $origin_id;
if (empty($origin_id)) $origin_id  = GETPOST('origin_id','int');    // Id of order or propal
if (empty($origin_id)) $origin_id  = GETPOST('object_id','int');    // Id of order or propal
$ref=GETPOST('ref','alpha');

// Security check
$socid='';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user, $origin, $origin_id);

$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');

//PDF
$hidedetails = (GETPOST('hidedetails','int') ? GETPOST('hidedetails','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc 	 = (GETPOST('hidedesc','int') ? GETPOST('hidedesc','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ?  1 : 0));
$hideref 	 = (GETPOST('hideref','int') ? GETPOST('hideref','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

$object = new Expedition($db);

// Load object
if ($id > 0 || ! empty($ref))
{
	$ret=$object->fetch($id, $ref);
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('expeditioncard'));

/*
 * Actions
 */
$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

if ($action == 'add')
{
    $error=0;

    $db->begin();

    $object->note				= GETPOST('note','alpha');
    $object->origin				= $origin;
    $object->origin_id			= $origin_id;
    $object->weight				= GETPOST('weight','int')==''?"NULL":GETPOST('weight','int');
    $object->sizeH				= GETPOST('sizeH','int')==''?"NULL":GETPOST('sizeH','int');
    $object->sizeW				= GETPOST('sizeW','int')==''?"NULL":GETPOST('sizeW','int');
    $object->sizeS				= GETPOST('sizeS','int')==''?"NULL":GETPOST('sizeS','int');
    $object->size_units			= GETPOST('size_units','int');
    $object->weight_units		= GETPOST('weight_units','int');

    $date_delivery = dol_mktime(GETPOST('date_deliveryhour','int'), GETPOST('date_deliverymin','int'), 0, GETPOST('date_deliverymonth','int'), GETPOST('date_deliveryday','int'), GETPOST('date_deliveryyear','int'));

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
    $object->shipping_method_id		= GETPOST('shipping_method_id','int');
    $object->tracking_number		= GETPOST('tracking_number','alpha');
    $object->ref_int				= GETPOST('ref_int','alpha');
    $object->note_private			= GETPOST('note_private');
    $object->note_public			= GETPOST('note_public');

    $num=count($objectsrc->lines);
    $totalqty=0;
    for ($i = 0; $i < $num; $i++)
    {
        $qty = "qtyl".$i;
        if (GETPOST($qty,'int') > 0) $totalqty+=GETPOST($qty,'int');
    }

    if ($totalqty > 0)
    {
        //var_dump($_POST);exit;
        for ($i = 0; $i < $num; $i++)
        {
            $qty = "qtyl".$i;
            if (GETPOST($qty,'int') > 0)
            {
                $ent = "entl".$i;
                $idl = "idl".$i;
                $entrepot_id = is_numeric(GETPOST($ent,'int'))?GETPOST($ent,'int'):GETPOST('entrepot_id','int');
				if ($entrepot_id < 0) $entrepot_id='';

                $ret=$object->addline($entrepot_id,GETPOST($idl,'int'),GETPOST($qty,'int'));
                if ($ret < 0)
                {
                    $mesg='<div class="error">'.$object->error.'</div>';
                    $error++;
                }
            }
        }

        if (! $error)
        {
            $ret=$object->create($user);
            if ($ret <= 0)
            {
                $mesg='<div class="error">'.$object->error.'</div>';
                $error++;
            }
        }
    }
    else
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Qty")).'</div>';
        $error++;
    }

    if (! $error)
    {
        $db->commit();
        header("Location: fiche.php?id=".$object->id);
        exit;
    }
    else
    {
        $db->rollback();
        $_GET["commande_id"]=GETPOST('commande_id','int');
        $action='create';
    }
}

/*
 * Build a receiving receipt
*/
else if ($action == 'create_delivery' && $conf->livraison_bon->enabled && $user->rights->expedition->livraison->creer)
{
    $result = $object->create_delivery($user);
    if ($result > 0)
    {
        header("Location: ".DOL_URL_ROOT.'/livraison/fiche.php?id='.$result);
        exit;
    }
    else
    {
        $mesg=$object->error;
    }
}

else if ($action == 'confirm_valid' && $confirm == 'yes' && $user->rights->expedition->valider)
{
    $object->fetch_thirdparty();

    $result = $object->valid($user);

    // Define output language
    $outputlangs = $langs;
    $newlang='';
    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id','alpha');
    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
    if (! empty($newlang))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($newlang);
    }
    if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
    {
        $ret=$object->fetch($id);    // Reload to get new records
        $result=expedition_pdf_create($db,$object,$object->modelpdf,$outputlangs);
    }
    if ($result < 0)
    {
        dol_print_error($db,$result);
        exit;
    }
}

else if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->expedition->supprimer)
{
    $result = $object->delete();
    if ($result > 0)
    {
        header("Location: ".DOL_URL_ROOT.'/expedition/index.php');
        exit;
    }
    else
    {
        $mesg = $object->error;
    }
}

else if ($action == 'reopen' && $user->rights->expedition->valider)
{
    $result = $object->setStatut(0);
    if ($result < 0)
    {
        $mesg = $object->error;
    }
}

else if ($action == 'setdate_livraison' && $user->rights->expedition->creer)
{
    //print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
    $datedelivery=dol_mktime(GETPOST('liv_hour','int'), GETPOST('liv_min','int'), 0, GETPOST('liv_month','int'), GETPOST('liv_day','int'), GETPOST('liv_year','int'));

    $object->fetch($id);
    $result=$object->set_date_livraison($user,$datedelivery);
    if ($result < 0)
    {
        $mesg='<div class="error">'.$object->error.'</div>';
    }
}

// Action update description of emailing
else if ($action == 'settrackingnumber' || $action == 'settrackingurl'
|| $action == 'settrueWeight'
|| $action == 'settrueWidth'
|| $action == 'settrueHeight'
|| $action == 'settrueDepth'
|| $action == 'setshipping_method_id')
{
    $error=0;

    if ($action == 'settrackingnumber')		$object->tracking_number = trim(GETPOST('trackingnumber','alpha'));
    if ($action == 'settrackingurl')		$object->tracking_url = trim(GETPOST('trackingurl','int'));
    if ($action == 'settrueWeight')			$object->trueWeight = trim(GETPOST('trueWeight','int'));
    if ($action == 'settrueWidth')			$object->trueWidth = trim(GETPOST('trueWidth','int'));
    if ($action == 'settrueHeight')			$object->trueHeight = trim(GETPOST('trueHeight','int'));
    if ($action == 'settrueDepth')			$object->trueDepth = trim(GETPOST('trueDepth','int'));
    if ($action == 'setshipping_method_id')	$object->shipping_method_id = trim(GETPOST('shipping_method_id','int'));

    if (! $error)
    {
        if ($object->update($user) >= 0)
        {
            header("Location: fiche.php?id=".$object->id);
            exit;
        }
        setEventMessage($object->error,'errors');
    }

    $action="";
}

// Build document
else if ($action == 'builddoc')	// En get ou en post
{

	// Save last template used to generate document
	if (GETPOST('model')) $object->setDocModel($user, GETPOST('model','alpha'));

    // Define output language
    $outputlangs = $langs;
    $newlang='';
    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id','alpha');
    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$shipment->client->default_lang;
    if (! empty($newlang))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($newlang);
    }
    $result=expedition_pdf_create($db,$object,$object->modelpdf,$outputlangs);
    if ($result <= 0)
    {
        dol_print_error($db,$result);
        exit;
    }
}

// Delete file in doc form
elseif ($action == 'remove_file')
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$upload_dir =	$conf->expedition->dir_output . "/sending";
	$file =	$upload_dir	. '/' .	GETPOST('file');
	$ret=dol_delete_file($file,0,0,0,$object);
	if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
	else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
}

/*
 * Add file in email form
*/
if (GETPOST('addfile','alpha'))
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    // Set tmp user directory TODO Use a dedicated directory for temp mails files
    $vardir=$conf->user->dir_output."/".$user->id;
    $upload_dir_tmp = $vardir.'/temp';

    dol_add_file_process($upload_dir_tmp,0,0);
    $action ='presend';
}

/*
 * Remove file in email form
*/
if (GETPOST('removedfile','alpha'))
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    // Set tmp user directory
    $vardir=$conf->user->dir_output."/".$user->id;
    $upload_dir_tmp = $vardir.'/temp';

    // TODO Delete only files that was uploaded from email form
    dol_remove_file_process(GETPOST('removedfile','int'),0);
    $action ='presend';
}

/*
 * Send mail
*/
if ($action == 'send' && ! GETPOST('addfile','alpha') && ! GETPOST('removedfile','alpha') && ! GETPOST('cancel','alpha'))
{
    $langs->load('mails');

//        $ref = dol_sanitizeFileName($object->ref);
//        $file = $conf->expedition->dir_output . '/sending/' . $ref . '/' . $ref . '.pdf';

//        if (is_readable($file))
//        {
            if (GETPOST('sendto','alpha'))
            {
                // Le destinataire a ete fourni via le champ libre
                $sendto = GETPOST('sendto','alpha');
                $sendtoid = 0;
            }
            elseif (GETPOST('receiver','alpha') != '-1')
            {
                // Recipient was provided from combo list
                if (GETPOST('receiver','alpha') == 'thirdparty') // Id of third party
                {
                    $sendto = $object->client->email;
                    $sendtoid = 0;
                }
                else	// Id du contact
                {
                    $sendto = $object->client->contact_get_property(GETPOST('receiver','alpha'),'email');
                    $sendtoid = GETPOST('receiver','alpha');
                }
            }

            if (dol_strlen($sendto))
            {
                $langs->load("commercial");

                $from = GETPOST('fromname','alpha') . ' <' . GETPOST('frommail','alpha') .'>';
                $replyto = GETPOST('replytoname','alpha'). ' <' . GETPOST('replytomail','alpha').'>';
                $message = GETPOST('message');
                $sendtocc = GETPOST('sendtocc','alpha');
                $deliveryreceipt = GETPOST('deliveryreceipt','alpha');

                if ($action == 'send')
                {
                    if (dol_strlen(GETPOST('subject','alpha'))) $subject=GETPOST('subject','alpha');
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
                include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
                $formmail = new FormMail($db);

                $attachedfiles=$formmail->get_attached_files();
                $filepath = $attachedfiles['paths'];
                $filename = $attachedfiles['names'];
                $mimetype = $attachedfiles['mimes'];

                // Send mail
                require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
                $mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt,-1);
                if ($mailfile->error)
                {
                    $mesg='<div class="error">'.$mailfile->error.'</div>';
                }
                else
                {
                    $result=$mailfile->sendfile();
                    if ($result)
                    {
                        $error=0;

                        // Initialisation donnees
                        $object->sendtoid		= $sendtoid;
                        $object->actiontypecode	= $actiontypecode;
                        $object->actionmsg		= $actionmsg;
                        $object->actionmsg2		= $actionmsg2;
                        $object->fk_element		= $object->id;
                        $object->elementtype	= $object->element;

                        // Appel des triggers
                        include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                        $interface=new Interfaces($db);
                        $result=$interface->run_triggers('SHIPPING_SENTBYMAIL',$object,$user,$langs,$conf);
                        if ($result < 0) {
                            $error++; $this->errors=$interface->errors;
                        }
                        // Fin appel triggers

                        if ($error)
                        {
                            dol_print_error($db);
                        }
                        else
                        {
                            // Redirect here
                            // This avoid sending mail twice if going out and then back to page
                        	$mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));
                            setEventMessage($mesg);
                            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
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
/*        }
        else
        {
            $langs->load("errors");
            $mesg='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
            dol_syslog('Failed to read file: '.$file);
        }*/
}

else if ($action == 'classifybilled')
{
    $object->fetch($id);
    $object->set_billed();
}


/*
 * View
 */

llxHeader('',$langs->trans('Shipment'),'Expedition');

$form = new Form($db);
$formfile = new FormFile($db);
$formproduct = new FormProduct($db);
$product_static = new Product($db);

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

            if (! empty($conf->stock->enabled)) $entrepot = new Entrepot($db);

            /*
             *   Document source
            */
            print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="add">';
            print '<input type="hidden" name="origin" value="'.$origin.'">';
            print '<input type="hidden" name="origin_id" value="'.$object->id.'">';
            print '<input type="hidden" name="ref_int" value="'.$object->ref_int.'">';
            if (GETPOST('entrepot_id','int'))
            {
                print '<input type="hidden" name="entrepot_id" value="'.GETPOST('entrepot_id','int').'">';
            }

            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="30%" class="fieldrequired">';
            if ($origin == 'commande' && ! empty($conf->commande->enabled))
            {
                print $langs->trans("RefOrder").'</td><td colspan="3"><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$object->id.'">'.img_object($langs->trans("ShowOrder"),'order').' '.$object->ref;
            }
            if ($origin == 'propal' && ! empty($conf->propal->enabled))
            {
                print $langs->trans("RefProposal").'</td><td colspan="3"><a href="'.DOL_URL_ROOT.'/comm/fiche.php?id='.$object->id.'">'.img_object($langs->trans("ShowProposal"),'propal').' '.$object->ref;
            }
            print '</a></td>';
            print "</tr>\n";

            // Ref client
            print '<tr><td>';
            if ($origin == 'commande') print $langs->trans('RefCustomerOrder');
            else if ($origin == 'propal') print $langs->trans('RefCustomerOrder');
            else print $langs->trans('RefCustomer');
            print '</td><td colspan="3">';
            print $object->ref_client;
            print '</td>';
            print '</tr>';

            // Tiers
            print '<tr><td class="fieldrequired">'.$langs->trans('Company').'</td>';
            print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
            print '</tr>';

            // Date delivery planned
            print '<tr><td>'.$langs->trans("DateDeliveryPlanned").'</td>';
            print '<td colspan="3">';
            //print dol_print_date($object->date_livraison,"day");	// date_livraison come from order and will be stored into date_delivery planed.
            print $form->select_date($object->date_livraison?$object->date_livraison:-1,'date_delivery',1,1);
            print "</td>\n";
            print '</tr>';

            // Note Public
            print '<tr><td>'.$langs->trans("NotePublic").'</td>';
            print '<td colspan="3">';
            $doleditor = new DolEditor('note_public', $object->note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
            print $doleditor->Create(1);
            print "</td></tr>";

            // Note Private
            if ($object->note_private && ! $user->societe_id)
            {
                print '<tr><td>'.$langs->trans("NotePrivate").'</td>';
                print '<td colspan="3">';
                $doleditor = new DolEditor('note_private', $object->note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
        		print $doleditor->Create(1);
                print "</td></tr>";
            }

            // Weight
            print '<tr><td>';
            print $langs->trans("Weight");
            print '</td><td width="90px"><input name="weight" size="5" value="'.GETPOST('weight','int').'"></td><td>';
            print $formproduct->select_measuring_units("weight_units","weight",GETPOST('weight_units','int'));
            print '</td></tr><tr><td>';
            print $langs->trans("Width");
            print ' </td><td><input name="sizeW" size="5" value="'.GETPOST('sizeW','int').'"></td><td rowspan="3">';
            print $formproduct->select_measuring_units("size_units","size");
            print '</td></tr><tr><td>';
            print $langs->trans("Height");
            print '</td><td><input name="sizeH" size="5" value="'.GETPOST('sizeH','int').'"></td>';
            print '</tr><tr><td>';
            print $langs->trans("Depth");
            print '</td><td><input name="sizeS" size="5" value="'.GETPOST('sizeS','int').'"></td>';
            print '</tr>';

            // Delivery method
            print "<tr><td>".$langs->trans("DeliveryMethod")."</td>";
            print '<td colspan="3">';
            $expe->fetch_delivery_methods();
            print $form->selectarray("shipping_method_id",$expe->meths,GETPOST('shipping_method_id','int'),1,0,0,"",1);
            if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
            print "</td></tr>\n";

            // Tracking number
            print "<tr><td>".$langs->trans("TrackingNumber")."</td>";
            print '<td colspan="3">';
            print '<input name="tracking_number" size="20" value="'.GETPOST('tracking_number','alpha').'">';
            print "</td></tr>\n";

            // Other attributes
            $parameters=array('colspan' => ' colspan="3"');
            $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$expe,$action);    // Note that $action and $object may have been modified by hook

            print "</table>";

            /*
             * Lignes de commandes
             */

            //$lines = $object->fetch_lines(1);
            $numAsked = count($object->lines);

            print '<script type="text/javascript" language="javascript">
            jQuery(document).ready(function() {
	            jQuery("#autofill").click(function() {';
    	    	$i=0;
    	    	while($i < $numAsked)
    	    	{
    	    		print 'jQuery("#qtyl'.$i.'").val(jQuery("#qtyasked'.$i.'").val() - jQuery("#qtydelivered'.$i.'").val());'."\n";
    	    		$i++;
    	    	}
        		print '});
	            jQuery("#autoreset").click(function() {';
    	    	$i=0;
    	    	while($i < $numAsked)
    	    	{
    	    		print 'jQuery("#qtyl'.$i.'").val(0);'."\n";
    	    		$i++;
    	    	}
        		print '});
        	});
            </script>';


            print '<br>';
            print '<table class="noborder" width="100%">';


            /* Lecture des expeditions deja effectuees */
            $object->loadExpeditions();

            if ($numAsked)
            {
                print '<tr class="liste_titre">';
                print '<td>'.$langs->trans("Description").'</td>';
                print '<td align="center">'.$langs->trans("QtyOrdered").'</td>';
                print '<td align="center">'.$langs->trans("QtyShipped").'</td>';
                print '<td align="center">'.$langs->trans("QtyToShip");
                print ' <br>(<a href="#" id="autofill">'.$langs->trans("Fill").'</a>';
                print ' / <a href="#" id="autoreset">'.$langs->trans("Reset").'</a>)';
                print '</td>';
                if (! empty($conf->stock->enabled))
                {
                    print '<td align="left">'.$langs->trans("Warehouse").' / '.$langs->trans("Stock").'</td>';
                }
                print "</tr>\n";
            }

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
                    $text=$product_static->getNomUrl(1);
                    $text.= ' - '.(! empty($line->label)?$line->label:$line->product_label);
                    $description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($line->desc));
                    print $form->textwithtooltip($text,$description,3,'','',$i);

                    // Show range
                    print_date_range($db->jdate($line->date_start),$db->jdate($line->date_end));

                    // Add description in form
                    if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
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

                    if (! empty($line->label)) {
                    	$text.= ' <strong>'.$line->label.'</strong>';
                    	print $form->textwithtooltip($text,$line->desc,3,'','',$i);
                    } else {
                    	print $text.' '.nl2br($line->desc);
                    }

                    // Show range
                    print_date_range($db->jdate($line->date_start),$db->jdate($line->date_end));
                    print "</td>\n";
                }

                // Qty
                print '<td align="center">'.$line->qty;
                print '<input name="qtyasked'.$indiceAsked.'" id="qtyasked'.$indiceAsked.'" type="hidden" value="'.$line->qty.'">';
                print '</td>';
                $qtyProdCom=$line->qty;

                // Qty already sent
                print '<td align="center">';
                $quantityDelivered = $object->expeditions[$line->id];
                print $quantityDelivered;
                print '<input name="qtydelivered'.$indiceAsked.'" id="qtydelivered'.$indiceAsked.'" type="hidden" value="'.$quantityDelivered.'">';
                print '</td>';

                $quantityAsked = $line->qty;
                $quantityToBeDelivered = $quantityAsked - $quantityDelivered;

                $defaultqty=0;
                if (GETPOST('entrepot_id','int') > 0)
                {
                    //var_dump($product);
                    $stock = $product->stock_warehouse[GETPOST('entrepot_id','int')]->real;
                    $stock+=0;  // Convertit en numerique
                    $defaultqty=min($quantityToBeDelivered, $stock);
                    if (($line->product_type == 1 && empty($conf->global->STOCK_SUPPORTS_SERVICES)) || $defaultqty < 0) $defaultqty=0;
                }

                // Quantity to send
                print '<td align="center">';
                if ($line->product_type == 0 || ! empty($conf->global->STOCK_SUPPORTS_SERVICES))
                {
                    print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';
                    print '<input name="qtyl'.$indiceAsked.'" id="qtyl'.$indiceAsked.'" type="text" size="4" value="'.$defaultqty.'">';
                }
                else print $langs->trans("NA");
                print '</td>';

                // Stock
                if (! empty($conf->stock->enabled))
                {
                    print '<td align="left">';
                    if ($line->product_type == 0 || ! empty($conf->global->STOCK_SUPPORTS_SERVICES))
                    {
                        // Show warehouse combo list
                    	$ent = "entl".$indiceAsked;
                    	$idl = "idl".$indiceAsked;
                    	$tmpentrepot_id = is_numeric(GETPOST($ent,'int'))?GETPOST($ent,'int'):GETPOST('entrepot_id','int');
                        print $formproduct->selectWarehouses($tmpentrepot_id,'entl'.$indiceAsked,'',1,0,$line->fk_product);
                    	if ($tmpentrepot_id > 0 && $tmpentrepot_id == GETPOST('entrepot_id','int'))
                        {
                            //print $stock.' '.$quantityToBeDelivered;
                            if ($stock < $quantityToBeDelivered)
                            {
                                print ' '.img_warning($langs->trans("StockTooLow"));	// Stock too low for entrepot_id but we may have change warehouse
                            }
                        }
                    }
                    else
                    {
                        print $langs->trans("Service");
                    }
                    print '</td>';
                }

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
                            print "<tr ".$bc[$var]."><td>&nbsp; &nbsp; &nbsp; ->
                                <a href=\"".DOL_URL_ROOT."/product/fiche.php?id=".$value['id']."\">".$value['fullpath']."
                                </a> (".$value['nb'].")</td><td align=\"center\"> ".$value['nb_total']."</td><td>&nbsp</td><td>&nbsp</td>
                                <td align=\"center\">".$value['stock']." ".$img."</td></tr>";
                        }
                    }
                }

                $indiceAsked++;
            }

            print "</table>";

            print '<br><center><input type="submit" class="button" value="'.$langs->trans("Create").'"></center>';

            print '</form>';

            print '<br>';
        }
        else
        {
            dol_print_error($db);
        }
    }
}
else if ($id || $ref)
/* *************************************************************************** */
/*                                                                             */
/* Edit and view mode                                                          */
/*                                                                             */
/* *************************************************************************** */
{
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

		$head=shipping_prepare_head($object);
		dol_fiche_head($head, 'shipping', $langs->trans("Shipment"), 0, 'sending');

		dol_htmloutput_mesg($mesg);

		/*
		 * Confirmation de la suppression
		*/
		if ($action == 'delete')
		{
			print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id,$langs->trans('DeleteSending'),$langs->trans("ConfirmDeleteSending",$object->ref),'confirm_delete','',0,1);

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

			$text = $langs->trans("ConfirmValidateSending",$numref);

			if (! empty($conf->notification->enabled))
			{
				require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
				$notify=new Notify($db);
				$text.='<br>';
				$text.=$notify->confirmMessage('SHIPPING_VALIDATE',$object->socid);
			}

			print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id,$langs->trans('ValidateSending'),$text,'confirm_valid','',0,1);

		}
		/*
		 * Confirmation de l'annulation
		*/
		if ($action == 'annuler')
		{
			print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id,$langs->trans('CancelSending'),$langs->trans("ConfirmCancelSending",$object->ref),'confirm_cancel','',0,1);

		}

		// Calculate true totalWeight and totalVolume for all products
		// by adding weight and volume of each product line.
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

		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/expedition/liste.php">'.$langs->trans("BackToList").'</a>';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td colspan="3">';
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
		print '</td></tr>';

		// Customer
		print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
		print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
		print "</tr>";

		// Linked documents
		if ($typeobject == 'commande' && $object->$typeobject->id && ! empty($conf->commande->enabled))
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
		if ($typeobject == 'propal' && $object->$typeobject->id && ! empty($conf->propal->enabled))
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
		print '<td colspan="3">'.dol_print_date($object->date_creation,"day")."</td>\n";
		print '</tr>';

		// Delivery date planned
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('DateDeliveryPlanned');
		print '</td>';

		if ($action != 'editdate_livraison') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryDate'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="2">';
		if ($action == 'editdate_livraison')
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

		// Weight
		print '<tr><td>'.$form->editfieldkey("Weight",'trueWeight',$object->trueWeight,$object,$user->rights->expedition->creer).'</td><td colspan="3">';
		print $form->editfieldval("Weight",'trueWeight',$object->trueWeight,$object,$user->rights->expedition->creer);
		print ($object->trueWeight && $object->weight_units!='')?' '.measuring_units_string($object->weight_units,"weight"):'';
		if ($totalWeight > 0)
		{
			if (!empty($object->trueWeight)) print ' ('.$langs->trans("SumOfProductWeights").': ';
			print $totalWeight.' '.measuring_units_string(0,"weight");
			if (!empty($object->trueWeight)) print ')';
		}
		print '</td></tr>';

		// Width
		print '<tr><td>'.$form->editfieldkey("Width",'trueWidth',$object->trueWidth,$object,$user->rights->expedition->creer).'</td><td colspan="3">';
		print $form->editfieldval("Width",'trueWidth',$object->trueWidth,$object,$user->rights->expedition->creer);
		print ($object->trueWidth && $object->width_units!='')?' '.measuring_units_string($object->width_units,"size"):'';
		print '</td></tr>';

		// Height
		print '<tr><td>'.$form->editfieldkey("Height",'trueHeight',$object->trueHeight,$object,$user->rights->expedition->creer).'</td><td colspan="3">';
		print $form->editfieldval("Height",'trueHeight',$object->trueHeight,$object,$user->rights->expedition->creer);
		print ($object->trueHeight && $object->height_units!='')?' '.measuring_units_string($object->height_units,"size"):'';
		print '</td></tr>';

		// Depth
		print '<tr><td>'.$form->editfieldkey("Depth",'trueDepth',$object->trueDepth,$object,$user->rights->expedition->creer).'</td><td colspan="3">';
		print $form->editfieldval("Depth",'trueDepth',$object->trueDepth,$object,$user->rights->expedition->creer);
		print ($object->trueDepth && $object->depth_units!='')?' '.measuring_units_string($object->depth_units,"size"):'';
		print '</td></tr>';

		// Volume
		print '<tr><td>';
		print $langs->trans("Volume");
		print '</td>';
		print '<td colspan="3">';
		$calculatedVolume=0;
		if ($object->trueWidth && $object->trueHeight && $object->trueDepth) $calculatedVolume=($object->trueWidth * $object->trueHeight * $object->trueDepth);
		// If sending volume not defined we use sum of products
		if ($calculatedVolume > 0)
		{
			print $calculatedVolume.' ';
			if ($volumeUnit < 50) print measuring_units_string(0,"volume");
			else print measuring_units_string($volumeUnit,"volume");
		}
		if ($totalVolume > 0)
		{
			if ($calculatedVolume) print ' ('.$langs->trans("SumOfProductVolumes").': ';
			print $totalVolume.' '.measuring_units_string(0,"volume");
			if ($calculatedVolume) print ')';
		}
		print "</td>\n";
		print '</tr>';

		// Status
		print '<tr><td>'.$langs->trans("Status").'</td>';
		print '<td colspan="3">'.$object->getLibStatut(4)."</td>\n";
		print '</tr>';

		// Sending method
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('SendingMethod');
		print '</td>';

		if ($action != 'editshipping_method_id') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editshipping_method_id&amp;id='.$object->id.'">'.img_edit($langs->trans('SetSendingMethod'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="2">';
		if ($action == 'editshipping_method_id')
		{
			print '<form name="setshipping_method_id" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="setshipping_method_id">';
			$object->fetch_delivery_methods();
			print $form->selectarray("shipping_method_id",$object->meths,$object->shipping_method_id,1,0,0,"",1);
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		}
		else
		{
			if ($object->shipping_method_id > 0)
			{
				// Get code using getLabelFromKey
				$code=$langs->getLabelFromKey($db,$object->shipping_method_id,'c_shipment_mode','rowid','code');
				print $langs->trans("SendingMethod".strtoupper($code));
			}
		}
		print '</td>';
		print '</tr>';

		// Tracking Number
		print '<tr><td>'.$form->editfieldkey("TrackingNumber",'trackingnumber',$object->tracking_number,$object,$user->rights->expedition->creer).'</td><td colspan="3">';
		print $form->editfieldval("TrackingNumber",'trackingnumber',$object->tracking_url,$object,$user->rights->expedition->creer,'string',$object->tracking_number);
		print '</td></tr>';

		// Other attributes
		$parameters=array('colspan' => ' colspan="3"');
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

		print "</table>\n";

		/*
		 * Lignes produits
		*/
		print '<br><table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER))
		{
			print '<td width="5" align="center">&nbsp;</td>';
		}
		print '<td>'.$langs->trans("Products").'</td>';
		print '<td align="center">'.$langs->trans("QtyOrdered").'</td>';
		if ($object->statut <= 1)
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

		if (! empty($conf->stock->enabled))
		{
			print '<td align="left">'.$langs->trans("WarehouseSource").'</td>';
		}

		print "</tr>\n";

		$var=false;

		if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
		{
			$object->fetch_thirdparty();
			$outputlangs = $langs;
			$newlang='';
			if (empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id','alpha');
			if (empty($newlang)) $newlang=$object->client->default_lang;
			if (! empty($newlang))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($newlang);
			}
		}

		for ($i = 0 ; $i < $num_prod ; $i++)
		{
			print "<tr ".$bc[$var].">";

			if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER))
			{
				print '<td align="center">'.($i+1).'</td>';
			}

			// Predefined product or service
			if ($lines[$i]->fk_product > 0)
			{
				// Define output language
				if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
				{
					$prod = new Product($db);
					$prod->fetch($lines[$i]->fk_product);
					$label = ( ! empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $lines[$i]->product_label;
				}
				else
					$label = (! empty($lines[$i]->label)?$lines[$i]->label:$lines[$i]->product_label);

				print '<td>';

				// Show product and description
				$product_static->type=$lines[$i]->fk_product_type;
				$product_static->id=$lines[$i]->fk_product;
				$product_static->ref=$lines[$i]->ref;
				$text=$product_static->getNomUrl(1);
				$text.= ' - '.$label;
				$description=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($lines[$i]->description));
				print $form->textwithtooltip($text,$description,3,'','',$i);
				print_date_range($lines[$i]->date_start,$lines[$i]->date_end);
				if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
				{
					print (! empty($lines[$i]->description) && $lines[$i]->description!=$lines[$i]->product)?'<br>'.dol_htmlentitiesbr($lines[$i]->description):'';
				}
			}
			else
			{
				print "<td>";
				if ($lines[$i]->fk_product_type==1) $text = img_object($langs->trans('Service'),'service');
				else $text = img_object($langs->trans('Product'),'product');

				if (! empty($lines[$i]->label)) {
					$text.= ' <strong>'.$lines[$i]->label.'</strong>';
					print $form->textwithtooltip($text,$lines[$i]->description,3,'','',$i);
				} else {
					print $text.' '.nl2br($lines[$i]->description);
				}

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
			if (! empty($conf->stock->enabled))
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


	$object->fetchObjectLinked($object->id,$object->element);

	/*
	 *    Boutons actions
	*/

	if (($user->societe_id == 0) && ($action!='presend'))
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
		if ($object->statut > 0)
		{
			if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->expedition->shipping_advance->send)
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
			}
			else print '<a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a>';
		}

		// Create bill and Close shipment
		if (! empty($conf->facture->enabled) && $object->statut > 0)
		{
			if ($user->rights->facture->creer)
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("CreateBill").'</a>';
			}
		}

		// This is just to generate a delivery receipt
		if ($conf->livraison_bon->enabled && ($object->statut == 1 || $object->statut == 2) && $user->rights->expedition->livraison->creer && empty($object->linkedObjectsIds['delivery'][0]))
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=create_delivery">'.$langs->trans("CreateDeliveryOrder").'</a>';
		}

		// Close
		if (! empty($conf->facture->enabled) && $object->statut > 0)
		{
			if ($user->rights->expedition->creer && $object->statut > 0 && ! $object->billed)
			{
				$label="Close";
				// Label here should be "Close" or "ClassifyBilled" if we decided to make bill on shipments instead of orders
				if (! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)) $label="ClassifyBilled";
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=classifybilled">'.$langs->trans($label).'</a>';
			}
		}

		if ($user->rights->expedition->supprimer)
		{
			print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
		}

		print '</div>';
		print "<br>\n";
	}


	/*
	 * Documents generated
	*/
	if ($action != 'presend')
	{
		print '<table width="100%"><tr><td width="50%" valign="top">';

		$objectref = dol_sanitizeFileName($object->ref);
		$filedir = $conf->expedition->dir_output . "/sending/" .$objectref;

		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;

		$genallowed=$user->rights->expedition->lire;
		$delallowed=$user->rights->expedition->supprimer;

		$somethingshown=$formfile->show_documents('expedition',$objectref,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'','','',$soc->default_lang);

		/*
		 * Linked object block
		*/
		$somethingshown=$object->showLinkedObjectBlock();

		if ($genallowed && ! $somethingshown) $somethingshown=1;

		print '</td><td valign="top" width="50%">';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions=new FormActions($db);
		$somethingshown=$formactions->showactions($object,'shipping',$socid);

		print '</td></tr></table>';
	}

	/*
	 * Action presend
	*/
	if ($action == 'presend')
	{
		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->expedition->dir_output . '/sending/' . $ref, preg_quote($ref,'/'));
		$file=$fileparams['fullname'];

		// Build document if it not exists
		if (! $file || ! is_readable($file))
		{
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

			$result=expedition_pdf_create($db, $object, GETPOST('model')?GETPOST('model'):$object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0)
			{
				dol_print_error($db,$result);
				exit;
			}
			$fileparams = dol_most_recent_file($conf->expedition->dir_output . '/sending/' . $ref, preg_quote($ref,'/'));
			$file=$fileparams['fullname'];
		}

		print '<br>';
		print_titre($langs->trans('SendShippingByEMail'));

		// Cree l'objet formulaire mail
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->fromtype = 'user';
		$formmail->fromid   = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		$formmail->withfrom=1;
		$liste=array();
		foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key=>$value)	$liste[$key]=$value;
		$formmail->withto=GETPOST("sendto")?GETPOST("sendto"):$liste;
		$formmail->withtocc=$liste;
		$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
		$formmail->withtopic=$langs->trans('SendShippingRef','__SHIPPINGREF__');
		$formmail->withfile=2;
		$formmail->withbody=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withcancel=1;
		// Tableau des substitutions
		$formmail->substit['__SHIPPINGREF__']=$object->ref;
		$formmail->substit['__SIGNATURE__']=$user->signature;
		$formmail->substit['__PERSONALIZED__']='';
		$formmail->substit['__CONTACTCIVNAME__']='';

		//Find the good contact adress
		//Find the good contact adress
		if ($typeobject == 'commande' && $object->$typeobject->id && ! empty($conf->commande->enabled))	{
			$objectsrc=new Commande($db);
			$objectsrc->fetch($object->$typeobject->id);
		}
		if ($typeobject == 'propal' && $object->$typeobject->id && ! empty($conf->propal->enabled))	{
			$objectsrc=new Propal($db);
			$objectsrc->fetch($object->$typeobject->id);
		}
		$custcontact='';
		$contactarr=array();
		$contactarr=$objectsrc->liste_contact(-1,'external');

		if (is_array($contactarr) && count($contactarr)>0) {
			foreach($contactarr as $contact) {

				if ($contact['libelle']==$langs->trans('TypeContact_commande_external_CUSTOMER')) {

					require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

					$contactstatic=new Contact($db);
					$contactstatic->fetch($contact['id']);
					$custcontact=$contactstatic->getFullName($langs,1);
				}
			}

			if (!empty($custcontact)) {
				$formmail->substit['__CONTACTCIVNAME__']=$custcontact;
			}
		}

		// Tableau des parametres complementaires
		$formmail->param['action']='send';
		$formmail->param['models']='shipping_send';
		$formmail->param['shippingid']=$object->id;
		$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;

		// Init list of files
		if (GETPOST("mode")=='init')
		{
			$formmail->clear_attached_files();
			$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
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


llxFooter();

$db->close();
?>
