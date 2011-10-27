<?php
/* Copyright (C) 2002-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011	Regis Houssin			<regis@dolibarr.fr>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
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
 */

/**
 *	\file       htdocs/fichinter/fiche.php
 *	\brief      Fichier fiche intervention
 *	\ingroup    ficheinter
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/modules/fichinter/modules_fichinter.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/fichinter.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
if ($conf->projet->enabled)
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
}
if (! empty($conf->global->FICHEINTER_ADDON) && is_readable(DOL_DOCUMENT_ROOT ."/core/modules/fichinter/mod_".$conf->global->FICHEINTER_ADDON.".php"))
{
    require_once(DOL_DOCUMENT_ROOT ."/core/modules/fichinter/mod_".$conf->global->FICHEINTER_ADDON.".php");
}

$langs->load("companies");
$langs->load("interventions");

$id			= GETPOST('id');
$ref		= GETPOST('ref');
$socid		= GETPOST('socid');
$action		= GETPOST("action");
$confirm	= GETPOST("confirm");
$mesg		= GETPOST("msg");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $id, 'fichinter');

$object = new Fichinter($db);

/*
 * Actions
 */

if ($action == 'confirm_validate' && $confirm == 'yes')
{
    $object->fetch($id);
    $object->fetch_thirdparty();

    $result = $object->setValid($user, $conf->fichinter->outputdir);
    if ($result >= 0)
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
        $result=fichinter_create($db, $object, $_REQUEST['model'], $outputlangs);
        Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
        exit;
    }
    else
    {
        $mesg='<div class="error">'.$object->error.'</div>';
    }
}

if ($action == 'confirm_modify' && $confirm == 'yes')
{
    $object->fetch($id);
    $object->fetch_thirdparty();

    $result = $object->setDraft($user);
    if ($result >= 0)
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
        $result=fichinter_create($db, $object, (empty($_REQUEST['model'])?$object->model:$_REQUEST['model']), $outputlangs);
        Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
        exit;
    }
    else
    {
        $mesg='<div class="error">'.$object->error.'</div>';
    }
}

if ($action == 'add')
{
    $object->socid			= $socid;
    $object->duree			= $_POST["duree"];
    $object->fk_project		= $_POST["projectid"];
    $object->author			= $user->id;
    $object->description	= $_POST["description"];
    $object->ref			= $ref;
    $object->modelpdf		= $_POST["model"];
    $object->note_private	= $_POST["note_private"];
    $object->note_public	= $_POST["note_public"];

    if ($object->socid > 0)
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
    else
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("ThirdParty")).'</div>';
        $action = 'create';
    }
}

if ($action == 'update')
{
    $object->fetch($id);

    $object->socid			= $socid;
    $object->fk_project		= $_POST["projectid"];
    $object->author			= $user->id;
    $object->description	= $_POST["description"];
    $object->ref			= $ref;

    $object->update();
}

/*
 * Build doc
 */
if ($action == 'builddoc')	// En get ou en post
{
    $object->fetch($id);
    $object->fetch_thirdparty();
    $object->fetch_lines();

    if ($_REQUEST['model'])
    {
        $object->setDocModel($user, $_REQUEST['model']);
    }

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
    $result=fichinter_create($db, $object, $_REQUEST['model'], $outputlangs);
    if ($result <= 0)
    {
        dol_print_error($db,$result);
        exit;
    }
}

// Set into a project
if ($action == 'classin')
{
    $object->fetch($id);
    $result=$object->setProject($_POST['projectid']);
    if ($result < 0) dol_print_error($db,$object->error);
}

if ($action == 'confirm_delete' && $confirm == 'yes')
{
    if ($user->rights->ficheinter->supprimer)
    {
        $object->fetch($id);
        $object->delete($user);
    }
    Header('Location: '.DOL_URL_ROOT.'/fichinter/list.php?leftmenu=ficheinter');
    exit;
}

if ($action == 'setdescription')
{
    $object->fetch($id);
    $result=$object->set_description($user,$_POST['description']);
    if ($result < 0) dol_print_error($db,$object->error);
}

// Add line
if ($action == "addline" && $user->rights->ficheinter->creer)
{
    if (empty($_POST['np_desc']))
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Description")).'</div>';
        $error++;
    }
    if (empty($_POST['durationhour']) && empty($_POST['durationmin']))
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Duration")).'</div>';
        $error++;
    }
    if (! $error)
    {
		$db->begin();

        $ret=$object->fetch($id);
        $object->fetch_thirdparty();

        $desc=$_POST['np_desc'];
        $date_intervention = dol_mktime($_POST["dihour"], $_POST["dimin"], 0, $_POST["dimonth"], $_POST["diday"], $_POST["diyear"]);
        $duration = ConvertTime2Seconds($_POST['durationhour'],$_POST['durationmin']);

        $result=$object->addline(
        	$id,
        	$desc,
        	$date_intervention,
        	$duration
        );

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

		if ($result >= 0)
		{
			$db->commit();

        	fichinter_create($db, $object, $object->modelpdf, $outputlangs);
        	Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
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
if ($action == 'classifybilled')
{
    $object->fetch($id);
	$result=$object->setBilled();
	if ($result > 0)
	{
        Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
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
if ($action == 'updateline' && $user->rights->ficheinter->creer && $_POST["save"] == $langs->trans("Save"))
{
    $objectline = new FichinterLigne($db);
    if ($objectline->fetch($_POST['line_id']) <= 0)
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

    $desc		= $_POST['np_desc'];
    $date_inter	= dol_mktime($_POST["dihour"], $_POST["dimin"], 0, $_POST["dimonth"], $_POST["diday"], $_POST["diyear"]);
    $duration	= ConvertTime2Seconds($_POST['durationhour'],$_POST['durationmin']);

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
    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
    if (! empty($newlang))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($newlang);
    }
    fichinter_create($db, $object, $object->modelpdf, $outputlangs);

    Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
    exit;
}

/*
 *  Supprime une ligne d'intervention AVEC confirmation
 */
if ($action == 'confirm_deleteline' && $confirm == 'yes')
{
    if ($user->rights->ficheinter->creer)
    {
        $objectline = new FichinterLigne($db);
        if ($objectline->fetch($_GET['line_id']) <= 0)
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
        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
        if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
        if (! empty($newlang))
        {
            $outputlangs = new Translate("",$conf);
            $outputlangs->setDefaultLang($newlang);
        }
        fichinter_create($db, $object, $object->modelpdf, $outputlangs);
    }
    Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
    exit;
}

/*
 * Ordonnancement des lignes
 */

if ($action == 'up' && $user->rights->ficheinter->creer)
{
    $object->fetch($id);
    $object->fetch_thirdparty();
    $object->line_up($_GET['line_id']);

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
    fichinter_create($db, $object, $object->modelpdf, $outputlangs);
    Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'#'.$_GET['line_id']);
    exit;
}

if ($action == 'down' && $user->rights->ficheinter->creer)
{
    $object->fetch($id);
    $object->fetch_thirdparty();
    $object->line_down($_GET['line_id']);

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
    fichinter_create($db, $object, $object->modelpdf, $outputlangs);
    Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'#'.$_GET['line_id']);
    exit;
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

    $action='presend';
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

    $action='presend';
}

/*
 * Send mail
 */
if ($action == 'send' && ! $_POST['cancel'] && (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->ficheinter->ficheinter_advance->send))
{
    $langs->load('mails');

    if ($object->fetch($id) > 0)
    {
        $objectref = dol_sanitizeFileName($object->ref);
        $file = $conf->ficheinter->dir_output . '/' . $objectref . '/' . $objectref . '.pdf';

        if (is_readable($file))
        {
            $object->fetch_thirdparty();

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
                else    // Id du contact
                {
                    $sendto = $object->client->contact_get_email($_POST['receiver']);
                    $sendtoid = $_POST['receiver'];
                }
            }

            if (dol_strlen($sendto))
            {
                $langs->load("commercial");

                $from				= $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
                $replyto			= $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
                $message			= $_POST['message'];
                $sendtocc			= $_POST['sendtocc'];
                $deliveryreceipt	= $_POST['deliveryreceipt'];

                if ($action == 'send')
                {
                    if (strlen($_POST['subject'])) $subject = $_POST['subject'];
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
                include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
                $formmail = new FormMail($db);

                $attachedfiles=$formmail->get_attached_files();
                $filepath = $attachedfiles['paths'];
                $filename = $attachedfiles['names'];
                $mimetype = $attachedfiles['mimes'];

                // Envoi de la propal
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
                        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                        $interface=new Interfaces($db);
                        $result=$interface->run_triggers('FICHEINTER_SENTBYMAIL',$object,$user,$langs,$conf);
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
                            Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&msg='.urlencode($mesg));
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
        }
        else
        {
            $langs->load("other");
            $mesg='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
            dol_syslog('Failed to read file: '.$file);
        }
    }
    else
    {
        $langs->load("other");
        $mesg='<div class="error">'.$langs->trans('ErrorFailedToReadEntity',$langs->trans("Intervention")).'</div>';
        dol_syslog('Impossible de lire les donnees de l\'intervention. Le fichier intervention n\'a peut-etre pas ete genere.');
    }

    $action='presend';
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
        if ($conf->projet->enabled)
        {
            $langs->load("project");

            print '<tr><td valign="top">'.$langs->trans("Project").'</td><td>';
            $numprojet=select_projects($soc->id,$_POST["projectid"],'projectid');
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
        print '<textarea name="note_public" cols="80" rows="'.ROWS_3.'"></textarea>';
        print '</td></tr>';
        
        // Private note
        if (! $user->societe_id)
        {
        	print '<tr>';
        	print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
        	print '<td valign="top" colspan="2">';
        	print '<textarea name="note_private" cols="80" rows="'.ROWS_3.'"></textarea>';
        	print '</td></tr>';
        }

        print '</table>';

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
        $form->select_societes('','socid','',1,1);
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
        $ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&line_id='.$_GET["line_id"], $langs->trans('DeleteInterventionLine'), $langs->trans('ConfirmDeleteInterventionLine'), 'confirm_deleteline','',0,1);
        if ($ret == 'html') print '<br>';
    }

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>';
    print $form->showrefnav($object,'ref','',1,'ref','ref');
    print '</td></tr>';

    // Third party
    print "<tr><td>".$langs->trans("Company")."</td><td>".$object->client->getNomUrl(1)."</td></tr>";

    // Duration
    print '<tr><td>'.$langs->trans("TotalDuration").'</td>';
    print '<td>'.ConvertSecondToTime($object->duree,'all',$conf->global->MAIN_DURATION_OF_WORKDAY).'</td>';
    print '</tr>';

    // Description (must be a textarea and not html must be allowed (used in list view)
    print '<tr><td>';
    if (! empty($conf->global->MAIN_USE_JQUERY_JEDITABLE))
    {
    	print $langs->trans('Description');
    	print '</td><td colspan="3">';
		// FIXME parameter note_private must not be denatured with a format function to be propagated. dol_nl2br must be used
		// by editInPlace if necessary according to type (4rd parameter)
    	print $form->editInPlace(dol_nl2br($object->description), 'description', $user->rights->ficheinter->creer && $object->statut == 0, 'area');
    }
    else
    {
    	print '<table class="nobordernopadding" width="100%"><tr><td>';
    	print $langs->trans('Description');
    	print '</td>';
    	if ($action != 'editdescription' && $object->statut == 0) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdescription&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify'),1).'</a></td>';
    	print '</tr></table>';
    	print '</td><td colspan="3">';
    	if ($action == 'editdescription')
    	{
    		print '<form name="editdescription" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
    		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    		print '<input type="hidden" name="action" value="setdescription">';
    		print '<textarea name="description" wrap="soft" cols="70" rows="'.ROWS_3.'">'.dol_htmlentitiesbr_decode($object->description).'</textarea><br>';
    		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
    		print '</form>';
    	}
    	else
    	{
    		print dol_nl2br($object->description);
    	}
    }
    print '</td>';
    print '</tr>';

    // Project
    if ($conf->projet->enabled)
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
    
    // Public note
    print '<tr><td valign="top">'.$langs->trans("NotePublic").'</td>';
    print '<td valign="top" colspan="3">';
	// FIXME parameter note_public must not be denatured with a format function to be propagated. dol_nl2br must be used
	// by editInPlace if necessary according to type (4rd parameter)
    print $form->editInPlace(dol_nl2br($object->note_public), 'note_public', $user->rights->ficheinter->creer, 'area');
    print "</td></tr>";
    	
    // Private note
    if (! $user->societe_id)
    {
    	print '<tr><td valign="top">'.$langs->trans("NotePrivate").'</td>';
    	print '<td valign="top" colspan="3">';
    	print $form->editInPlace(dol_nl2br($object->note_private), 'note_private', $user->rights->ficheinter->creer);
    	print "</td></tr>";
    }

    print "</table>";

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
            print '<br><table class="noborder" width="100%">';

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
            if ($action != 'editline' || $_GET['line_id'] != $objp->rowid)
            {
                print '<tr '.$bc[$var].'>';
                print '<td>';
                print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
                print nl2br($objp->description);

                // Date
                print '<td align="center" width="150">'.dol_print_date($db->jdate($objp->date_intervention),'dayhour').'</td>';

                // Duration
                print '<td align="right" width="150">'.ConvertSecondToTime($objp->duree).'</td>';

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
            if ($object->statut == 0 && $action == 'editline' && $user->rights->ficheinter->creer && $_GET["line_id"] == $objp->rowid)
            {
                print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'#'.$objp->rowid.'" method="post">';
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                print '<input type="hidden" name="action" value="updateline">';
                print '<input type="hidden" name="id" value="'.$object->id.'">';
                print '<input type="hidden" name="line_id" value="'.$_GET["line_id"].'">';
                print '<tr '.$bc[$var].'>';
                print '<td>';
                print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

                // Editeur wysiwyg
                require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
                $doleditor=new DolEditor('np_desc',$objp->description,'',164,'dolibarr_details','',false,true,$conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS,ROWS_2,70);
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
            require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
            $doleditor=new DolEditor('np_desc',$_POST["np_desc"],'',100,'dolibarr_details','',false,true,$conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS,ROWS_2,70);
            $doleditor->Create();
            print '</td>';

            // Date intervention
            print '<td align="center" nowrap="nowrap">';
            $timearray=dol_getdate(mktime());
            if (empty($_POST['diday'])) $timewithnohour=dol_mktime(0,0,0,$timearray['mon'],$timearray['mday'],$timearray['year']);
            else $timewithnohour=dol_mktime($_POST['dihour'],$_POST['dimin'],$_POST['disec'],$_POST['dimonth'],$_POST['diday'],$_POST['diyear']);
            $form->select_date($timewithnohour,'di',1,1,0,"addinter");
            print '</td>';

            // Duration
            print '<td align="right">';
            $form->select_duration('duration',(empty($_POST["durationhour"]) && empty($_POST["durationmin"]))?3600:(60*60*$_POST["durationhour"]+60*$_POST["durationmin"]));
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
                $objectref = dol_sanitizeFileName($object->ref);
                $file = $conf->ficheinter->dir_output . '/'.$objectref.'/'.$objectref.'.pdf';
                if (file_exists($file))
                {
                    if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->ficheinter->ficheinter_advance->send)
                    {
                        print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
                    }
                    else print '<a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a>';
                }
            }

        	// Invoicing
			if ($conf->facture->enabled && $object->statut > 0)
            {
				$langs->load("bills");
                if ($object->statut < 2)
                {
					if ($user->rights->facture->creer) print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("CreateBill").'</a>';
					else print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("CreateBill").'</a>';
                }

                if (! empty($conf->global->FICHEINTER_CLASSIFY_BILLED))
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
    	include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php');
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
        $file = $conf->ficheinter->dir_output . '/' . $ref . '/' . $ref . '.pdf';

        print '<br>';
        print_titre($langs->trans('SendInterventionByMail'));

        // Create form object
        include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
        $formmail = new FormMail($db);
        $formmail->fromtype = 'user';
        $formmail->fromid   = $user->id;
        $formmail->fromname = $user->getFullName($langs);
        $formmail->frommail = $user->email;
        $formmail->withfrom=1;
        $formmail->withto=empty($_POST["sendto"])?1:$_POST["sendto"];
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
        // Tableau des parametres complementaires
        $formmail->param['action']='send';
        $formmail->param['models']='fichinter_send';
        $formmail->param['fichinter_id']=$object->id;
        $formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;

        // Init list of files
        if (! empty($_REQUEST["mode"]) && $_REQUEST["mode"]=='init')
        {
            $formmail->clear_attached_files();
            $formmail->add_attached_files($file,$object->ref.'.pdf','application/pdf');
        }

        $formmail->show_form();

        print '<br>';
    }
}

$db->close();

llxFooter();
?>
