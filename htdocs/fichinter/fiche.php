<?php
/* Copyright (C) 2002-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012  Juanjo Menent			<jmenent@2byte.es>
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
 */

/**
 *	\file       htdocs/fichinter/fiche.php
 *	\brief      Fichier fiche intervention
 *	\ingroup    ficheinter
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (! empty($conf->projet->enabled))
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}
if (! empty($conf->global->FICHEINTER_ADDON) && is_readable(DOL_DOCUMENT_ROOT ."/core/modules/fichinter/mod_".$conf->global->FICHEINTER_ADDON.".php"))
{
    require_once DOL_DOCUMENT_ROOT ."/core/modules/fichinter/mod_".$conf->global->FICHEINTER_ADDON.'.php';
}

$langs->load("companies");
$langs->load("interventions");

$id			= GETPOST('id','int');
$ref		= GETPOST('ref','alpha');
$socid		= GETPOST('socid','int');
$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$mesg		= GETPOST('msg','alpha');

//PDF
$hidedetails = (GETPOST('hidedetails','int') ? GETPOST('hidedetails','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc 	 = (GETPOST('hidedesc','int') ? GETPOST('hidedesc','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ?  1 : 0));
$hideref 	 = (GETPOST('hideref','int') ? GETPOST('hideref','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $id, 'fichinter');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('interventioncard'));

$object = new Fichinter($db);


/*
 * Actions
 */

if ($action == 'confirm_validate' && $confirm == 'yes' && $user->rights->ficheinter->creer)
{
    $object->fetch($id);
    $object->fetch_thirdparty();

    $result = $object->setValid($user);
    if ($result >= 0)
    {
        // Define output language
        $outputlangs = $langs;
        $newlang='';
        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','alpha')) $newlang=GETPOST('lang_id','alpha');
        if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
        if (! empty($newlang))
        {
            $outputlangs = new Translate("",$conf);
            $outputlangs->setDefaultLang($newlang);
        }
        $result=fichinter_create($db, $object, GETPOST('model','alpha'), $outputlangs);
        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
        exit;
    }
    else
    {
        $mesg='<div class="error">'.$object->error.'</div>';
    }
}

else if ($action == 'confirm_modify' && $confirm == 'yes' && $user->rights->ficheinter->creer)
{
    $object->fetch($id);
    $object->fetch_thirdparty();

    $result = $object->setDraft($user);
    if ($result >= 0)
    {
        // Define output language
        $outputlangs = $langs;
        $newlang='';
        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','alpha')) $newlang=GETPOST('lang_id','alpha');
        if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
        if (! empty($newlang))
        {
            $outputlangs = new Translate("",$conf);
            $outputlangs->setDefaultLang($newlang);
        }
        $result=fichinter_create($db, $object, (!GETPOST('model','alpha'))?$object->model:GETPOST('model','apha'), $outputlangs);
        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
        exit;
    }
    else
    {
        $mesg='<div class="error">'.$object->error.'</div>';
    }
}

else if ($action == 'add' && $user->rights->ficheinter->creer)
{
    $object->socid			= $socid;
    $object->duree			= GETPOST('duree','int');
    $object->fk_project		= GETPOST('projectid','int');
    $object->author			= $user->id;
    $object->description	= GETPOST('description');
    $object->ref			= $ref;
    $object->modelpdf		= GETPOST('model','alpha');
    $object->note_private	= GETPOST('note_private');
    $object->note_public	= GETPOST('note_public');

    if ($object->socid > 0)
    {
	    // If creation from another object of another module (Example: origin=propal, originid=1)
	    if ($_POST['origin'] && $_POST['originid'])
	    {
	        // Parse element/subelement (ex: project_task)
	        $element = $subelement = $_POST['origin'];
	        if (preg_match('/^([^_]+)_([^_]+)/i',$_POST['origin'],$regs))
	        {
	            $element = $regs[1];
	            $subelement = $regs[2];
	        }

	        // For compatibility
	        if ($element == 'order')    { $element = $subelement = 'commande'; }
	        if ($element == 'propal')   { $element = 'comm/propal'; $subelement = 'propal'; }
	        if ($element == 'contract') { $element = $subelement = 'contrat'; }

	        $object->origin    = $_POST['origin'];
	        $object->origin_id = $_POST['originid'];

	        // Possibility to add external linked objects with hooks
	        $object->linked_objects[$object->origin] = $object->origin_id;
	        if (is_array($_POST['other_linked_objects']) && ! empty($_POST['other_linked_objects']))
	        {
	        	$object->linked_objects = array_merge($object->linked_objects, $_POST['other_linked_objects']);
	        }

	        $object_id = $object->create($user);

	        if ($object_id > 0)
	        {
	            dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

	            $classname = ucfirst($subelement);
	            $srcobject = new $classname($db);

	            dol_syslog("Try to find source object origin=".$object->origin." originid=".$object->origin_id." to add lines");
	            $result=$srcobject->fetch($object->origin_id);
	            if ($result > 0)
	            {
	                $srcobject->fetch_thirdparty();
					$lines = $srcobject->lines;
	                if (empty($lines) && method_exists($srcobject,'fetch_lines'))  $lines = $srcobject->fetch_lines();

	                $fk_parent_line=0;
	                $num=count($lines);

	                for ($i=0;$i<$num;$i++)
	                {
	                    $product_type=($lines[$i]->product_type?$lines[$i]->product_type:0);

						if ($product_type == 1) { //only services
							// service prédéfini
							if ($lines[$i]->fk_product > 0)
							{
								// Define output language
								if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
								{
									$prod = new Product($db, $lines[$i]->fk_product);

									$outputlangs = $langs;
									$newlang='';
									if (empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
									if (empty($newlang)) $newlang=$srcobject->client->default_lang;
									if (! empty($newlang))
									{
										$outputlangs = new Translate("",$conf);
										$outputlangs->setDefaultLang($newlang);
									}

									$label = (! empty($prod->multilangs[$outputlangs->defaultlang]["libelle"])) ? $prod->multilangs[$outputlangs->defaultlang]["libelle"] : $lines[$i]->product_label;
								}
								else
								{
									$label = $lines[$i]->product_label;
								}

								$desc = $label;
								$desc .= ' ('.$langs->trans('Quantity').': '.$lines[$i]->qty.')';
							}
							else {
							    $desc = dol_htmlentitiesbr($lines[$i]->desc);
						        $desc .= ' ('.$langs->trans('Quantity').': '.$lines[$i]->qty.')';
					        }
							$timearray=dol_getdate(mktime());
            				$date_intervention=dol_mktime(0,0,0,$timearray['mon'],$timearray['mday'],$timearray['year']);
							$duration = 3600;

		                    $result = $object->addline(
		                        $object_id,
		                        $desc,
					            $date_intervention,
                 				$duration
		                    );

		                    if ($result < 0)
		                    {
		                        $error++;
		                        break;
		                    }

						}
	                }

	            }
	            else
	            {
	                $mesg=$srcobject->error;
	                $error++;
	            }
	        }
	        else
	        {
	            $mesg=$object->error;
	            $error++;
	        }
	    }
	    else
	    {
	        $result = $object->create();
	        if ($result > 0)
	        {
	            $id=$result;      // Force raffraichissement sur fiche venant d'etre cree
	        }
	        else
	        {
	            $langs->load("errors");
	            $mesg='<div class="error">'.$langs->trans($object->error).'</div>';
	            $action = 'create';
	        }
        }
    }
    else
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("ThirdParty")).'</div>';
        $action = 'create';
    }
}

else if ($action == 'update' && $user->rights->ficheinter->creer)
{
    $object->fetch($id);

    $object->socid			= $socid;
    $object->fk_project		= GETPOST('projectid','int');
    $object->author			= $user->id;
    $object->description	= GETPOST('description','alpha');
    $object->ref			= $ref;

    $object->update();
}

/*
 * Build doc
 */
else if ($action == 'builddoc' && $user->rights->ficheinter->creer)	// En get ou en post
{
    $object->fetch($id);
    $object->fetch_thirdparty();
    $object->fetch_lines();

    if (GETPOST('model','alpha'))
    {
        $object->setDocModel($user, GETPOST('model','alpha'));
    }

    // Define output language
    $outputlangs = $langs;
    $newlang='';
    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','alpha')) $newlang=GETPOST('lang_id','alpha');
    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
    if (! empty($newlang))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($newlang);
    }
    $result=fichinter_create($db, $object, GETPOST('model','alpha'), $outputlangs);
    if ($result <= 0)
    {
        dol_print_error($db,$result);
        exit;
    }
}

// Remove file in doc form
else if ($action == 'remove_file')
{
	if ($object->fetch($id))
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$object->fetch_thirdparty();

		$langs->load("other");
		$upload_dir = $conf->ficheinter->dir_output;
		$file = $upload_dir . '/' . GETPOST('file');
		$ret=dol_delete_file($file,0,0,0,$object);
		if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
		else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
	}
}

// Set into a project
else if ($action == 'classin' && $user->rights->ficheinter->creer)
{
    $object->fetch($id);
    $result=$object->setProject(GETPOST('projectid','int'));
    if ($result < 0) dol_print_error($db,$object->error);
}

else if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->ficheinter->supprimer)
{
	$object->fetch($id);
	$object->fetch_thirdparty();
	$object->delete($user);

    header('Location: '.DOL_URL_ROOT.'/fichinter/list.php?leftmenu=ficheinter');
    exit;
}

else if ($action == 'setdescription' && $user->rights->ficheinter->creer)
{
    $object->fetch($id);
    $result=$object->set_description($user,GETPOST('description'));
    if ($result < 0) dol_print_error($db,$object->error);
}
else if ($action == 'setnote_public' && $user->rights->ficheinter->creer)
{
    $object->fetch($id);
    $result=$object->update_note_public(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES));
    if ($result < 0) dol_print_error($db,$object->error);
}
else if ($action == 'setnote_private' && $user->rights->ficheinter->creer)
{
    $object->fetch($id);
    $result=$object->update_note(dol_html_entity_decode(GETPOST('note_private'), ENT_QUOTES));
    if ($result < 0) dol_print_error($db,$object->error);
}

// Add line
else if ($action == "addline" && $user->rights->ficheinter->creer)
{
    if (!GETPOST('np_desc'))
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Description")).'</div>';
        $error++;
    }
    if (!GETPOST('durationhour','int') && !GETPOST('durationmin','int'))
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Duration")).'</div>';
        $error++;
    }
    if (! $error)
    {
		$db->begin();

        $ret=$object->fetch($id);
        $object->fetch_thirdparty();

        $desc=GETPOST('np_desc');
        $date_intervention = dol_mktime(GETPOST('dihour','int'), GETPOST('dimin','int'), 0, GETPOST('dimonth','int'), GETPOST('diday','int'), GETPOST('diyear','int'));
        $duration = convertTime2Seconds(GETPOST('durationhour','int'), GETPOST('durationmin','int'));

        $result=$object->addline(
            $id,
            $desc,
            $date_intervention,
            $duration
        );

        // Define output language
        $outputlangs = $langs;
        $newlang='';
        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','alpha')) $newlang=GETPOST('lang_id','alpha');
        if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
        if (! empty($newlang))
        {
            $outputlangs = new Translate("",$conf);
            $outputlangs->setDefaultLang($newlang);
        }

		if ($result >= 0)
		{
			$db->commit();

        	fichinter_create($db, $object, $object->modelpdf, $outputlangs);
        	header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
        	exit;
		}
		else
		{
			$mesg=$object->error;
			$db->rollback();
		}
    }
}

// Classify Billed
else if ($action == 'classifybilled' && $user->rights->ficheinter->creer)
{
    $object->fetch($id);
	$result=$object->setBilled();
	if ($result > 0)
	{
        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
        exit;
	}
	else
	{
        $mesg='<div class="error">'.$object->error.'</div>';
	}
}

/*
 *  Mise a jour d'une ligne d'intervention
 */
else if ($action == 'updateline' && $user->rights->ficheinter->creer && GETPOST('save','alpha') == $langs->trans("Save"))
{
    $objectline = new FichinterLigne($db);
    if ($objectline->fetch(GETPOST('line_id','int')) <= 0)
    {
        dol_print_error($db);
        exit;
    }

    if ($object->fetch($objectline->fk_fichinter) <= 0)
    {
        dol_print_error($db);
        exit;
    }
    $object->fetch_thirdparty();

    $desc		= GETPOST('np_desc');
    $date_inter	= dol_mktime(GETPOST('dihour','int'), GETPOST('dimin','int'), 0, GETPOST('dimonth','int'), GETPOST('diday','int'), GETPOST('diyear','int'));
    $duration	= convertTime2Seconds(GETPOST('durationhour','int'),GETPOST('durationmin','int'));

    $objectline->datei		= $date_inter;
    $objectline->desc		= $desc;
    $objectline->duration	= $duration;
    $result = $objectline->update();
    if ($result < 0)
    {
        dol_print_error($db);
        exit;
    }

    // Define output language
    $outputlangs = $langs;
    $newlang='';
    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','alpha')) $newlang=GETPOST('lang_id','alpha');
    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
    if (! empty($newlang))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($newlang);
    }
    fichinter_create($db, $object, $object->modelpdf, $outputlangs);

    header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
    exit;
}

/*
 *  Supprime une ligne d'intervention AVEC confirmation
 */
else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->ficheinter->creer)
{
	$objectline = new FichinterLigne($db);
	if ($objectline->fetch(GETPOST('line_id','int')) <= 0)
	{
		dol_print_error($db);
		exit;
	}
	$result=$objectline->deleteline();

	if ($object->fetch($objectline->fk_fichinter) <= 0)
	{
		dol_print_error($db);
		exit;
	}

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','alpha')) $newlang=GETPOST('lang_id','alpha');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	fichinter_create($db, $object, $object->modelpdf, $outputlangs);
}

/*
 * Ordonnancement des lignes
 */

else if ($action == 'up' && $user->rights->ficheinter->creer)
{
    $object->fetch($id);
    $object->fetch_thirdparty();
    $object->line_up(GETPOST('line_id','int'));

    // Define output language
    $outputlangs = $langs;
    $newlang='';
    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','alpha')) $newlang=GETPOST('lang_id','alpha');
    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
    if (! empty($newlang))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($newlang);
    }
    fichinter_create($db, $object, $object->modelpdf, $outputlangs);
    header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'#'.GETPOST('line_id','int'));
    exit;
}

else if ($action == 'down' && $user->rights->ficheinter->creer)
{
    $object->fetch($id);
    $object->fetch_thirdparty();
    $object->line_down(GETPOST('line_id','int'));

    // Define output language
    $outputlangs = $langs;
    $newlang='';
    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','alpha')) $newlang=GETPOST('lang_id','alpha');
    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
    if (! empty($newlang))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($newlang);
    }
    fichinter_create($db, $object, $object->modelpdf, $outputlangs);
    header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'#'.GETPOST('line_id','int'));
    exit;
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
    $action='presend';
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
    dol_remove_file_process(GETPOST('removedfile','alpha'),0);
    $action='presend';
}

/*
 * Send mail
 */
if ($action == 'send' && ! GETPOST('cancel','alpha') && (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->ficheinter->ficheinter_advance->send))
{
    $langs->load('mails');

    if ($object->fetch($id) > 0)
    {
//        $objectref = dol_sanitizeFileName($object->ref);
//        $file = $conf->ficheinter->dir_output . '/' . $objectref . '/' . $objectref . '.pdf';

//        if (is_readable($file))
//        {
            $object->fetch_thirdparty();

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
                else    // Id du contact
                {
                    $sendto = $object->client->contact_get_property(GETPOST('receiver'),'email');
                    $sendtoid = GETPOST('receiver','alpha');
                }
            }

            if (dol_strlen($sendto))
            {
                $langs->load("commercial");

                $from				= GETPOST('fromname','alpha') . ' <' . GETPOST('frommail','alpha') .'>';
                $replyto			= GETPOST('replytoname','alpha'). ' <' . GETPOST('replytomail','alpha').'>';
                $message			= GETPOST('message','alpha');
                $sendtocc			= GETPOST('sendtocc','alpha');
                $deliveryreceipt	= GETPOST('deliveryreceipt','alpha');

                if ($action == 'send')
                {
                    if (strlen(GETPOST('subject','alphs'))) $subject = GETPOST('subject','alpha');
                    else $subject = $langs->transnoentities('Intervention').' '.$object->ref;
                    $actiontypecode='AC_FICH';
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

                // Envoi de la propal
                require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
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
                        $mesg='<div class="ok">'.$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2)).'.</div>';

                        $error=0;

                        // Initialisation donnees
                        $object->sendtoid		= $sendtoid;
                        $object->actiontypecode	= $actiontypecode;
                        $object->actionmsg 		= $actionmsg;
                        $object->actionmsg2		= $actionmsg2;
                        $object->fk_element		= $object->id;
						$object->elementtype	= $object->element;

                        // Appel des triggers
                        include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                        $interface=new Interfaces($db);
                        $result=$interface->run_triggers('FICHINTER_SENTBYMAIL',$object,$user,$langs,$conf);
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
                            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&msg='.urlencode($mesg));
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
                dol_syslog('Recipient email is empty');
            }
		/*}
        else
        {
            $langs->load("errors");
            $mesg='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
            dol_syslog('Failed to read file: '.$file);
        }*/
    }
    else
    {
        $langs->load("other");
        $mesg='<div class="error">'.$langs->trans('ErrorFailedToReadEntity',$langs->trans("Intervention")).'</div>';
        dol_syslog('Impossible de lire les donnees de l\'intervention. Le fichier intervention n\'a peut-etre pas ete genere.');
    }

    $action='presend';
}

if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $user->rights->ficheinter->creer)
{
	if ($action == 'addcontact')
	{
		$result = $object->fetch($id);

		if ($result > 0 && $id > 0)
		{
			$contactid = (GETPOST('userid','int') ? GETPOST('userid','int') : GETPOST('contactid','int'));
			$result = $object->add_contact($contactid, GETPOST('type','int'), GETPOST('source','alpha'));
		}

		if ($result >= 0)
		{
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
		else
		{
			if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$langs->load("errors");
				$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
			}
			else
			{
				$mesg = '<div class="error">'.$object->error.'</div>';
			}
		}
	}

	// bascule du statut d'un contact
	else if ($action == 'swapstatut')
	{
		if ($object->fetch($id))
		{
			$result=$object->swapContactStatus(GETPOST('ligne','int'));
		}
		else
		{
			dol_print_error($db);
		}
	}

	// Efface un contact
	else if ($action == 'deletecontact')
	{
		$object->fetch($id);
		$result = $object->delete_contact(GETPOST('lineid','int'));

		if ($result >= 0)
		{
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
		else {
			dol_print_error($db);
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader();

if ($action == 'create')
{
    /*
     * Mode creation
     * Creation d'une nouvelle fiche d'intervention
     */

    $soc=new Societe($db);

    print_fiche_titre($langs->trans("AddIntervention"));

    dol_htmloutput_mesg($mesg);

    if ($socid) $res=$soc->fetch($socid);

    if (GETPOST('origin') && GETPOST('originid'))
    {
        // Parse element/subelement (ex: project_task)
        $element = $subelement = GETPOST('origin');
        if (preg_match('/^([^_]+)_([^_]+)/i',GETPOST('origin'),$regs))
        {
            $element = $regs[1];
            $subelement = $regs[2];
        }

        if ($element == 'project')
        {
            $projectid=GETPOST('originid');
        }
        else
        {
            // For compatibility
            if ($element == 'order' || $element == 'commande')    { $element = $subelement = 'commande'; }
            if ($element == 'propal')   { $element = 'comm/propal'; $subelement = 'propal'; }
            if ($element == 'contract') { $element = $subelement = 'contrat'; }

            dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

            $classname = ucfirst($subelement);
            $objectsrc = new $classname($db);
            $objectsrc->fetch(GETPOST('originid'));
            if (empty($objectsrc->lines) && method_exists($objectsrc,'fetch_lines'))  $objectsrc->fetch_lines();
            $objectsrc->fetch_thirdparty();

            $projectid          = (!empty($objectsrc->fk_project)?$objectsrc->fk_project:'');

            $soc = $objectsrc->client;

            $note_private		= (! empty($objectsrc->note) ? $objectsrc->note : (! empty($objectsrc->note_private) ? $objectsrc->note_private : ''));
            $note_public		= (! empty($objectsrc->note_public) ? $objectsrc->note_public : '');

            // Object source contacts list
            $srccontactslist = $objectsrc->liste_contact(-1,'external',1);
        }
    }
    else {
		$projectid = GETPOST('projectid','int');
		$note_private = '';
		$note_public = '';
	}

    if (! $conf->global->FICHEINTER_ADDON)
    {
        dol_print_error($db,$langs->trans("Error")." ".$langs->trans("Error_FICHEINTER_ADDON_NotDefined"));
        exit;
    }

    $object->date = dol_now();

    $obj = $conf->global->FICHEINTER_ADDON;
    $obj = "mod_".$obj;

    $modFicheinter = new $obj;
    $numpr = $modFicheinter->getNextValue($soc, $object);

    if ($socid > 0)
    {
    	$soc->fetch($socid);

        print '<form name="fichinter" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

        print '<table class="border" width="100%">';

        print '<input type="hidden" name="socid" value='.$soc->id.'>';
        print '<tr><td class="fieldrequired">'.$langs->trans("ThirdParty").'</td><td>'.$soc->getNomUrl(1).'</td></tr>';

        print '<input type="hidden" name="action" value="add">';

        // Ref
        print '<tr><td class="fieldrequired">'.$langs->trans("Ref").'</td>';
        print '<td><input name="ref" value="'.$numpr.'"></td></tr>'."\n";

        // Description (must be a textarea and not html must be allowed (used in list view)
        print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
        print '<td>';
        print '<textarea name="description" cols="80" rows="'.ROWS_3.'"></textarea>';
        print '</td></tr>';

        // Project
        if (! empty($conf->projet->enabled))
        {
            $langs->load("project");

            print '<tr><td valign="top">'.$langs->trans("Project").'</td><td>';
            /* Fix: If a project must be linked to any companies (suppliers or not), project must be not be set as limited to customer but must be not linked to any particular thirdparty
            if ($societe->fournisseur==1)
            	$numprojet=select_projects(-1,$_POST["projectid"],'projectid');
            else
            	$numprojet=select_projects($societe->id,$_POST["projectid"],'projectid');
            	*/
            $numprojet=select_projects($soc->id,GETPOST('projectid','int'),'projectid');
            if ($numprojet==0)
            {
                print ' &nbsp; <a href="'.DOL_DOCUMENT_ROOT.'/projet/fiche.php?socid='.$soc->id.'&action=create">'.$langs->trans("AddProject").'</a>';
            }
            print '</td></tr>';
        }

        // Model
        print '<tr>';
        print '<td>'.$langs->trans("DefaultModel").'</td>';
        print '<td colspan="2">';
        $liste=ModelePDFFicheinter::liste_modeles($db);
        print $form->selectarray('model',$liste,$conf->global->FICHEINTER_ADDON_PDF);
        print "</td></tr>";

        // Public note
        print '<tr>';
        print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
        print '<td valign="top" colspan="2">';
        print '<textarea name="note_public" cols="80" rows="'.ROWS_3.'">'.$note_public.'</textarea>';
        print '</td></tr>';

        // Private note
        if (! $user->societe_id)
        {
        	print '<tr>';
        	print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
        	print '<td valign="top" colspan="2">';
        	print '<textarea name="note_private" cols="80" rows="'.ROWS_3.'">'.$note_private.'</textarea>';
        	print '</td></tr>';
        }

        // Other attributes
        $parameters=array('colspan' => ' colspan="2"');
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

        print '</table>';

	    if (is_object($objectsrc))
	    {
	        print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
	        print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';
		}

        print '<center><br>';
        print '<input type="submit" class="button" value="'.$langs->trans("CreateDraftIntervention").'">';
        print '</center>';

        print '</form>';
    }
    else
    {
        print '<form name="fichinter" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
        print '<table class="border" width="100%">';
        print '<tr><td class="fieldrequired">'.$langs->trans("ThirdParty").'</td><td>';
        print $form->select_company('','socid','',1,1);
        print '</td></tr>';
        print '</table>';

        print '<br><center>';
        print '<input type="hidden" name="action" value="create">';
        print '<input type="submit" class="button" value="'.$langs->trans("CreateDraftIntervention").'">';
        print '</center>';

        print '</form>';
    }

}
else if ($id > 0 || ! empty($ref))
{
    /*
     * Affichage en mode visu
     */

	$object->fetch($id, $ref);
    $object->fetch_thirdparty();

    $soc=new Societe($db);
    $soc->fetch($object->socid);

    dol_htmloutput_mesg($mesg);

    $head = fichinter_prepare_head($object);

    dol_fiche_head($head, 'card', $langs->trans("InterventionCard"), 0, 'intervention');

    // Confirmation de la suppression de la fiche d'intervention
    if ($action == 'delete')
    {
        $ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteIntervention'), $langs->trans('ConfirmDeleteIntervention'), 'confirm_delete','',0,1);
        if ($ret == 'html') print '<br>';
    }

    // Confirmation validation
    if ($action == 'validate')
    {
        $ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateIntervention'), $langs->trans('ConfirmValidateIntervention'), 'confirm_validate','',0,1);
        if ($ret == 'html') print '<br>';
    }

    // Confirmation de la validation de la fiche d'intervention
    if ($action == 'modify')
    {
        $ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ModifyIntervention'), $langs->trans('ConfirmModifyIntervention'), 'confirm_modify','',0,1);
        if ($ret == 'html') print '<br>';
    }

    // Confirmation de la suppression d'une ligne d'intervention
    if ($action == 'ask_deleteline')
    {
        $ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&line_id='.GETPOST('line_id','int'), $langs->trans('DeleteInterventionLine'), $langs->trans('ConfirmDeleteInterventionLine'), 'confirm_deleteline','',0,1);
        if ($ret == 'html') print '<br>';
    }

    print '<table class="border" width="100%">';

    $linkback = '<a href="'.DOL_URL_ROOT.'/fichinter/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

    // Ref
    print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>';
    print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
    print '</td></tr>';

    // Third party
    print "<tr><td>".$langs->trans("Company")."</td><td>".$object->client->getNomUrl(1)."</td></tr>";

    // Duration
    print '<tr><td>'.$langs->trans("TotalDuration").'</td>';
    print '<td>'.convertSecondToTime($object->duree, 'all', $conf->global->MAIN_DURATION_OF_WORKDAY).'</td>';
    print '</tr>';

    // Description (must be a textarea and not html must be allowed (used in list view)
    print '<tr><td valign="top">';
    print $form->editfieldkey("Description",'description',$object->description,$object,$user->rights->ficheinter->creer,'textarea');
    print '</td><td colspan="3">';
    print $form->editfieldval("Description",'description',$object->description,$object,$user->rights->ficheinter->creer,'textarea:8:80');
    print '</td>';
    print '</tr>';

    // Project
    if (! empty($conf->projet->enabled))
    {
        $langs->load('projects');
        print '<tr>';
        print '<td>';

        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('Project');
        print '</td>';
        if ($action != 'classify')
        {
            print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classify&amp;id='.$object->id.'">';
            print img_edit($langs->trans('SetProject'),1);
            print '</a></td>';
        }
        print '</tr></table>';
        print '</td><td colspan="3">';
        if ($action == 'classify')
        {
            $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project,'projectid');
        }
        else
        {
            $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project,'none');
        }
        print '</td>';
        print '</tr>';
    }

    // Statut
    print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

    // Other attributes
    $parameters=array('colspan' => ' colspan="3"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

    print "</table><br>";

    if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
    {
    	$blocname = 'contacts';
    	$title = $langs->trans('ContactsAddresses');
    	include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
    }

	if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    {
    	$blocname = 'notes';
    	$title = $langs->trans('Notes');
    	include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
    }

    /*
     * Lignes d'intervention
     */
    $sql = 'SELECT ft.rowid, ft.description, ft.fk_fichinter, ft.duree, ft.rang,';
    $sql.= ' ft.date as date_intervention';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'fichinterdet as ft';
    $sql.= ' WHERE ft.fk_fichinter = '.$object->id;
    $sql.= ' ORDER BY ft.rang ASC, ft.rowid';

    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;

        if ($num)
        {
            print '<table class="noborder" width="100%">';

            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans('Description').'</td>';
            print '<td align="center">'.$langs->trans('Date').'</td>';
            print '<td align="right">'.$langs->trans('Duration').'</td>';
            print '<td width="48" colspan="3">&nbsp;</td>';
            print "</tr>\n";
        }
        $var=true;
        while ($i < $num)
        {
            $objp = $db->fetch_object($resql);
            $var=!$var;

            // Ligne en mode visu
            if ($action != 'editline' || GETPOST('line_id','int') != $objp->rowid)
            {
                print '<tr '.$bc[$var].'>';
                print '<td>';
                print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
                print dol_htmlentitiesbr($objp->description);

                // Date
                print '<td align="center" width="150">'.dol_print_date($db->jdate($objp->date_intervention),'dayhour').'</td>';

                // Duration
                print '<td align="right" width="150">'.convertSecondToTime($objp->duree).'</td>';

                print "</td>\n";


                // Icone d'edition et suppression
                if ($object->statut == 0  && $user->rights->ficheinter->creer)
                {
                    print '<td align="center">';
                    print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=editline&amp;line_id='.$objp->rowid.'#'.$objp->rowid.'">';
                    print img_edit();
                    print '</a>';
                    print '</td>';
                    print '<td align="center">';
                    print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=ask_deleteline&amp;line_id='.$objp->rowid.'">';
                    print img_delete();
                    print '</a></td>';
                    if ($num > 1)
                    {
                        print '<td align="center">';
                        if ($i > 0)
                        {
                            print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=up&amp;line_id='.$objp->rowid.'">';
                            print img_up();
                            print '</a>';
                        }
                        if ($i < $num-1)
                        {
                            print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=down&amp;line_id='.$objp->rowid.'">';
                            print img_down();
                            print '</a>';
                        }
                        print '</td>';
                    }
                }
                else
                {
                    print '<td colspan="3">&nbsp;</td>';
                }

                print '</tr>';
            }

            // Ligne en mode update
            if ($object->statut == 0 && $action == 'editline' && $user->rights->ficheinter->creer && GETPOST('line_id','int') == $objp->rowid)
            {
                print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'#'.$objp->rowid.'" method="post">';
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                print '<input type="hidden" name="action" value="updateline">';
                print '<input type="hidden" name="id" value="'.$object->id.'">';
                print '<input type="hidden" name="line_id" value="'.GETPOST('line_id','int').'">';
                print '<tr '.$bc[$var].'>';
                print '<td>';
                print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

                // Editeur wysiwyg
                require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
                $doleditor=new DolEditor('np_desc',$objp->description,'',164,'dolibarr_details','',false,true,$conf->global->FCKEDITOR_ENABLE_DETAILS,ROWS_2,70);
                $doleditor->Create();
                print '</td>';

                // Date d'intervention
                print '<td align="center" nowrap="nowrap">';
                $form->select_date($db->jdate($objp->date_intervention),'di',1,1,0,"date_intervention");
                print '</td>';

                // Duration
                print '<td align="right">';
                $form->select_duration('duration',$objp->duree);
                print '</td>';

                print '<td align="center" colspan="5" valign="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
                print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td>';
                print '</tr>' . "\n";

                print "</form>\n";
            }

            $i++;
        }

        $db->free($resql);

        /*
         * Add line
         */
        if ($object->statut == 0 && $user->rights->ficheinter->creer && $action <> 'editline')
        {
            if (! $num) print '<br><table class="noborder" width="100%">';

            print '<tr class="liste_titre">';
            print '<td>';
            print '<a name="add"></a>'; // ancre
            print $langs->trans('Description').'</td>';
            print '<td align="center">'.$langs->trans('Date').'</td>';
            print '<td align="right">'.$langs->trans('Duration').'</td>';

            print '<td colspan="4">&nbsp;</td>';
            print "</tr>\n";

            // Ajout ligne d'intervention
            print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'#add" name="addinter" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="id" value="'.$object->id.'">';
            print '<input type="hidden" name="action" value="addline">';

            $var=false;

            print '<tr '.$bc[$var].">\n";
            print '<td>';
            // editeur wysiwyg
            require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
            $doleditor=new DolEditor('np_desc',GETPOST('np_desc','alpha'),'',100,'dolibarr_details','',false,true,$conf->global->FCKEDITOR_ENABLE_DETAILS,ROWS_2,70);
            $doleditor->Create();
            print '</td>';

            // Date intervention
            print '<td align="center" nowrap="nowrap">';
            $now=dol_now();
            $timearray=dol_getdate($now);
            if (!GETPOST('diday','int')) $timewithnohour=dol_mktime(0,0,0,$timearray['mon'],$timearray['mday'],$timearray['year']);
            else $timewithnohour=dol_mktime(GETPOST('dihour','int'),GETPOST('dimin','int'), 0,GETPOST('dimonth','int'),GETPOST('diday','int'),GETPOST('diyear','int'));
            $form->select_date($timewithnohour,'di',1,1,0,"addinter");
            print '</td>';

            // Duration
            print '<td align="right">';
            $form->select_duration('duration',(!GETPOST('durationhour','int') && !GETPOST('durationmin','int'))?3600:(60*60*GETPOST('durationhour','int')+60*GETPOST('durationmin','int')));
            print '</td>';

            print '<td align="center" valign="middle" colspan="4"><input type="submit" class="button" value="'.$langs->trans('Add').'" name="addline"></td>';
            print '</tr>';

            print '</form>';

            if (! $num) print '</table>';
        }

        if ($num) print '</table>';
    }
    else
    {
        dol_print_error($db);
    }

    print '</div>';
    print "\n";


    /*
     * Barre d'actions
     */
    print '<div class="tabsAction">';

    if ($user->societe_id == 0)
    {
        if ($action != 'editdescription')
        {
            // Validate
            if ($object->statut == 0 && $user->rights->ficheinter->creer && count($object->lines) > 0)
            {
                print '<a class="butAction" href="fiche.php?id='.$object->id.'&action=validate"';
                print '>'.$langs->trans("Valid").'</a>';
            }

            // Modify
            if ($object->statut == 1 && $user->rights->ficheinter->creer)
            {
                print '<a class="butAction" href="fiche.php?id='.$object->id.'&action=modify"';
                print '>'.$langs->trans("Modify").'</a>';
            }

            // Send
            if ($object->statut > 0)
            {
                if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->ficheinter->ficheinter_advance->send)
                {
                    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
                }
                else print '<a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a>';
            }

        	// Invoicing
			if (! empty($conf->facture->enabled) && $object->statut > 0)
            {
				$langs->load("bills");
                if ($object->statut < 2)
                {
					if ($user->rights->facture->creer) print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("CreateBill").'</a>';
					else print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("CreateBill").'</a>';
                }

                if (! empty($conf->global->FICHINTER_CLASSIFY_BILLED))
                {
	                if ($object->statut != 2)
					{
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=classifybilled">'.$langs->trans("ClassifyBilled").'</a>';
					}
	            }
            }

            // Delete
            if (($object->statut == 0 && $user->rights->ficheinter->creer) || $user->rights->ficheinter->supprimer)
            {
                print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete"';
                print '>'.$langs->trans('Delete').'</a>';
            }

        }
    }

    print '</div>';
    print '<br>';

    if ($action != 'presend')
    {
        print '<table width="100%"><tr><td width="50%" valign="top">';
        /*
         * Built documents
         */
        $filename=dol_sanitizeFileName($object->ref);
        $filedir=$conf->ficheinter->dir_output . "/".$object->ref;
        $urlsource=$_SERVER["PHP_SELF"]."?id=".$object->id;
        $genallowed=$user->rights->ficheinter->creer;
        $delallowed=$user->rights->ficheinter->supprimer;
        $genallowed=1;
        $delallowed=1;

        $var=true;

        //print "<br>\n";
        $somethingshown=$formfile->show_documents('ficheinter',$filename,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'','','',$soc->default_lang);

    	/*
    	 * Linked object block
    	 */
    	$somethingshown=$object->showLinkedObjectBlock();

    	print '</td><td valign="top" width="50%">';
    	// List of actions on element
    	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
    	$formactions=new FormActions($db);
    	$somethingshown=$formactions->showactions($object,'fichinter',$socid);
        print "</td><td>";
        print "&nbsp;</td>";
        print "</tr></table>\n";
    }


    /*
     * Action presend
     */
    if ($action == 'presend')
    {
        $ref = dol_sanitizeFileName($object->ref);
        include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        $fileparams = dol_most_recent_file($conf->ficheinter->dir_output . '/' . $ref, preg_quote($ref,'/'));
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

            $result=fichinter_create($db, $object, GETPOST('model')?GETPOST('model'):$object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);
            if ($result <= 0)
            {
                dol_print_error($db,$result);
                exit;
            }
            $fileparams = dol_most_recent_file($conf->ficheinter->dir_output . '/' . $ref, preg_quote($ref,'/'));
            $file=$fileparams['fullname'];
        }

        print '<br>';
        print_titre($langs->trans('SendInterventionByMail'));

        // Create form object
        include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
        $formmail = new FormMail($db);
        $formmail->fromtype = 'user';
        $formmail->fromid   = $user->id;
        $formmail->fromname = $user->getFullName($langs);
        $formmail->frommail = $user->email;
        $formmail->withfrom=1;
        $formmail->withto=(!GETPOST('sendto','alpha'))?1:GETPOST('sendto','alpha');
        $formmail->withtosocid=$societe->id;
        $formmail->withtocc=1;
        $formmail->withtoccsocid=0;
        $formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
        $formmail->withtocccsocid=0;
        $formmail->withtopic=$langs->trans('SendInterventionRef','__FICHINTERREF__');
        $formmail->withfile=2;
        $formmail->withbody=1;
        $formmail->withdeliveryreceipt=1;
        $formmail->withcancel=1;

        // Tableau des substitutions
        $formmail->substit['__FICHINTERREF__']=$object->ref;
        $formmail->substit['__SIGNATURE__']=$user->signature;
        $formmail->substit['__PERSONALIZED__']='';
        // Tableau des parametres complementaires
        $formmail->param['action']='send';
        $formmail->param['models']='fichinter_send';
        $formmail->param['fichinter_id']=$object->id;
        $formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;

        // Init list of files
        if (GETPOST("mode")=='init')
        {
            $formmail->clear_attached_files();
            $formmail->add_attached_files($file,basename($file),dol_mimetype($file));
        }

        $formmail->show_form();

        print '<br>';
    }
}


llxFooter();

$db->close();
?>
