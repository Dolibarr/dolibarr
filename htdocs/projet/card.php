<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/projet/card.php
 *	\ingroup    projet
 *	\brief      Project card
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$langs->load("projects");
$langs->load('companies');

$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');
$backtopage=GETPOST('backtopage','alpha');
$cancel=GETPOST('cancel','alpha');
$status=GETPOST('status','int');
$opp_status=GETPOST('opp_status','int');

if ($id == '' && $ref == '' && ($action != "create" && $action != "add" && $action != "update" && ! $_POST["cancel"])) accessforbidden();

$mine = GETPOST('mode')=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('projectcard','globalcard'));

$object = new Project($db);
$extrafields = new ExtraFields($db);

// Load object
//include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Can't use generic include because when creating a project, ref is defined and we dont want error if fetch fails from ref.
if ($id > 0 || ! empty($ref))
{
    $ret = $object->fetch($id,$ref);	// If we create project, ref may be defined into POST but record does not yet exists into database
    if ($ret > 0) {
        $object->fetch_thirdparty();
        $id=$object->id;
    }
}

// Security check
$socid=GETPOST('socid');
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $object->id);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

$date_start=dol_mktime(0,0,0,GETPOST('projectstartmonth','int'),GETPOST('projectstartday','int'),GETPOST('projectstartyear','int'));
$date_end=dol_mktime(0,0,0,GETPOST('projectendmonth','int'),GETPOST('projectendday','int'),GETPOST('projectendyear','int'));


/*
 * Actions
 */

$parameters=array('id'=>$socid, 'objcanvas'=>$objcanvas);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Cancel
	if ($cancel)
	{
		if (GETPOST("comefromclone")==1)
		{
		    $result=$object->delete($user);
		    if ($result > 0)
		    {
		        header("Location: index.php");
		        exit;
		    }
		    else
		    {
		        dol_syslog($object->error,LOG_DEBUG);
			    setEventMessages($langs->trans("CantRemoveProject"), null, 'errors');
		    }
		}
		if ($backtopage)
		{
	    	header("Location: ".$backtopage);
	    	exit;
		}

		$action = '';
	}

	if ($action == 'add' && $user->rights->projet->creer)
	{
	    $error=0;
	    if (empty($_POST["ref"]))
	    {
		    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
	        $error++;
	    }
	    if (empty($_POST["title"]))
	    {
		    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
	        $error++;
	    }

	    if (! $error)
	    {
	        $error=0;

	        $db->begin();

	        $object->ref             = GETPOST('ref','alpha');
	        $object->title           = GETPOST('title'); // Do not use 'alpha' here, we want field as it is
	        $object->socid           = GETPOST('socid','int');
	        $object->description     = GETPOST('description'); // Do not use 'alpha' here, we want field as it is
	        $object->public          = GETPOST('public','alpha');
	        $object->opp_amount      = price2num(GETPOST('opp_amount'));
	        $object->budget_amount   = price2num(GETPOST('budget_amount'));
	        $object->datec=dol_now();
	        $object->date_start=$date_start;
	        $object->date_end=$date_end;
	        $object->statuts         = $status;
	        $object->opp_status      = $opp_status;

	        // Fill array 'array_options' with data from add form
	        $ret = $extrafields->setOptionalsFromPost($extralabels,$object);
			if ($ret < 0) $error++;

	        $result = $object->create($user);
	        if (! $error && $result > 0)
	        {
	            // Add myself as project leader
	            $result = $object->add_contact($user->id, 'PROJECTLEADER', 'internal');
	            if ($result < 0)
	            {
	                $langs->load("errors");
		            setEventMessages($langs->trans($object->error), null, 'errors');
	                $error++;
	            }
	        }
	        else
	        {
	            $langs->load("errors");
		        setEventMessages($langs->trans($object->error), null, 'errors');
	            $error++;
	        }

	        if (! $error)
	        {
	            $db->commit();

        		if ($backtopage)
				{
			    	header("Location: ".$backtopage.'&projectid='.$object->id);
			    	exit;
				}
				else
				{
	            	header("Location:card.php?id=".$object->id);
	            	exit;
				}
	        }
	        else
	        {
	            $db->rollback();

	            $action = 'create';
	        }
	    }
	    else
	    {
	        $action = 'create';
	    }
	}

	if ($action == 'update' && ! $_POST["cancel"] && $user->rights->projet->creer)
	{
	    $error=0;

	    if (empty($ref))
	    {
	        $error++;
	        //$_GET["id"]=$_POST["id"]; // We return on the project card
		    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
	    }
	    if (empty($_POST["title"]))
	    {
	        $error++;
	        //$_GET["id"]=$_POST["id"]; // We return on the project card
		    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
	    }

	    $db->begin();

	    if (! $error)
	    {
			$object->oldcopy = clone $object;

			$old_start_date = $object->date_start;

	        $object->ref          = GETPOST('ref','alpha');
	        $object->title        = GETPOST('title'); // Do not use 'alpha' here, we want field as it is
	        $object->socid        = GETPOST('socid','int');
	        $object->description  = GETPOST('description');	// Do not use 'alpha' here, we want field as it is
	        $object->public       = GETPOST('public','alpha');
	        $object->date_start   = empty($_POST["projectstart"])?'':$date_start;
	        $object->date_end     = empty($_POST["projectend"])?'':$date_end;
	        if (isset($_POST['opp_amount']))    $object->opp_amount   = price2num(GETPOST('opp_amount'));
	        if (isset($_POST['budget_amount'])) $object->budget_amount= price2num(GETPOST('budget_amount'));
	        if (isset($_POST['opp_status']))    $object->opp_status   = $opp_status;

	        // Fill array 'array_options' with data from add form
	        $ret = $extrafields->setOptionalsFromPost($extralabels,$object);
			if ($ret < 0) $error++;
	    }

		if ($object->opp_amount && ($object->opp_status <= 0))
	    {
	       	$error++;
	    	setEventMessages($langs->trans("ErrorOppStatusRequiredIfAmount"), null, 'errors');
	    }
	    
	    if (! $error)
	    {
	    	$result=$object->update($user);
	    	if ($result < 0)
	    	{
	    		$error++;
		        setEventMessages($object->error, $object->errors,'errors');
	    	}
	    }

	    if (! $error)
	    {
	    	if (GETPOST("reportdate") && ($object->date_start!=$old_start_date))
	    	{
	    		$result=$object->shiftTaskDate($old_start_date);
	    		if ($result < 0)
	    		{
	    			$error++;
				    setEventMessages($langs->trans("ErrorShiftTaskDate").':'.$object->error, $langs->trans("ErrorShiftTaskDate").':'.$object->errors, 'errors');
	    		}
	    	}
	    }

	    if ($error)
	    {
			$db->rollback();
	    	$action='edit';
	    }
	    else
		{
	    	$db->commit();

			if (GETPOST('socid','int') > 0) $object->fetch_thirdparty(GETPOST('socid','int'));
			else unset($object->thirdparty);
	    }
	}

	// Build doc
	if ($action == 'builddoc' && $user->rights->projet->creer)
	{
		// Save last template used to generate document
		if (GETPOST('model')) $object->setDocModel($user, GETPOST('model','alpha'));

	    $outputlangs = $langs;
	    if (GETPOST('lang_id'))
	    {
	        $outputlangs = new Translate("",$conf);
	        $outputlangs->setDefaultLang(GETPOST('lang_id'));
	    }
	    $result= $object->generateDocument($object->modelpdf, $outputlangs);
	    if ($result <= 0)
	    {
	        setEventMessages($object->error, $object->errors, 'errors');
	        $action='';
	    }
	}

	// Delete file in doc form
	if ($action == 'remove_file' && $user->rights->projet->creer)
	{
	    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	    if ($object->id > 0)
	    {
	        $langs->load("other");
	        $upload_dir =	$conf->projet->dir_output . "/";
	        $urlfile=GETPOST('urlfile','alpha');
	        $file =	$upload_dir	. '/' .	$filetodelete;
	        $ret=dol_delete_file($file);
	        if ($ret) setEventMessages($langs->trans("FileWasRemoved", $urlfile), null, 'mesgs');
	        else setEventMessages($langs->trans("ErrorFailToDeleteFile", $urlfile), null, 'errors');
	    }
	}


	if ($action == 'confirm_validate' && GETPOST('confirm') == 'yes')
	{
	    $result = $object->setValid($user);
	    if ($result <= 0)
	    {
	        setEventMessages($object->error, $object->errors, 'errors');
	    }
	}

	if ($action == 'confirm_close' && GETPOST('confirm') == 'yes')
	{
	    $result = $object->setClose($user);
	    if ($result <= 0)
	    {
	        setEventMessages($object->error, $object->errors, 'errors');
	    }
	}

	if ($action == 'confirm_reopen' && GETPOST('confirm') == 'yes')
	{
	    $result = $object->setValid($user);
	    if ($result <= 0)
	    {
	        setEventMessages($object->error, $object->errors, 'errors');
	    }
	}

	if ($action == 'confirm_delete' && GETPOST("confirm") == "yes" && $user->rights->projet->supprimer)
	{
	    $object->fetch($id);
	    $result=$object->delete($user);
	    if ($result > 0)
	    {
	        setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
	    	header("Location: index.php");
	        exit;
	    }
	    else
	    {
	        dol_syslog($object->error,LOG_DEBUG);
	        setEventMessages($object->error, $object->errors, 'errors');
	    }
	}

	if ($action == 'confirm_clone' && $user->rights->projet->creer && GETPOST('confirm') == 'yes')
	{
	    $clone_contacts=GETPOST('clone_contacts')?1:0;
	    $clone_tasks=GETPOST('clone_tasks')?1:0;
		$clone_project_files = GETPOST('clone_project_files') ? 1 : 0;
		$clone_task_files = GETPOST('clone_task_files') ? 1 : 0;
	    $clone_notes=GETPOST('clone_notes')?1:0;
	    $move_date=GETPOST('move_date')?1:0;
	    $result=$object->createFromClone($object->id,$clone_contacts,$clone_tasks,$clone_project_files,$clone_task_files,$clone_notes,$move_date);
	    if ($result <= 0)
	    {
	        setEventMessages($object->error, $object->errors, 'errors');
	    }
	    else
	    {
	    	$object->fetch($result);	// Load new object
	    	$action='edit';
	    	$comefromclone=true;
	    }
	}
}


/*
 *	View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);
$userstatic = new User($db);

$title=$langs->trans("Project").' - '.$object->ref.($object->thirdparty->name?' - '.$object->thirdparty->name:'').($object->title?' - '.$object->title:'');
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/',$conf->global->MAIN_HTML_TITLE)) $title=$object->ref.($object->thirdparty->name?' - '.$object->thirdparty->name:'').($object->title?' - '.$object->title:'');
$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";

llxHeader("",$title,$help_url);


if ($action == 'create' && $user->rights->projet->creer)
{
    /*
     * Create
     */

	$thirdparty=new Societe($db);
	if ($socid > 0) $thirdparty->fetch($socid);

    print load_fiche_titre($langs->trans("NewProject"), '', 'title_project');

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

    dol_fiche_head();

    print '<table class="border" width="100%">';

    $defaultref='';
    $modele = empty($conf->global->PROJECT_ADDON)?'mod_project_simple':$conf->global->PROJECT_ADDON;

    // Search template files
    $file=''; $classname=''; $filefound=0;
    $dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
    foreach($dirmodels as $reldir)
    {
    	$file=dol_buildpath($reldir."core/modules/project/".$modele.'.php',0);
    	if (file_exists($file))
    	{
    		$filefound=1;
    		$classname = $modele;
    		break;
    	}
    }

    if ($filefound)
    {
	    $result=dol_include_once($reldir."core/modules/project/".$modele.'.php');
	    $modProject = new $classname;

	    $defaultref = $modProject->getNextValue($thirdparty,$object);
    }

    if (is_numeric($defaultref) && $defaultref <= 0) $defaultref='';

    // Ref
    $suggestedref=($_POST["ref"]?$_POST["ref"]:$defaultref);
    print '<tr><td><span class="fieldrequired">'.$langs->trans("Ref").'</span></td><td><input size="12" type="text" name="ref" value="'.$suggestedref.'">';
    print ' '.$form->textwithpicto('', $langs->trans("YouCanCompleteRef", $suggestedref));
    print '</td></tr>';

    // Label
    print '<tr><td><span class="fieldrequired">'.$langs->trans("Label").'</span></td><td><input size="80" type="text" name="title" value="'.GETPOST("title").'"></td></tr>';

    // Thirdparty
    print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
    $filteronlist='';
    if (! empty($conf->global->PROJECT_FILTER_FOR_THIRDPARTY_LIST)) $filteronlist=$conf->global->PROJECT_FILTER_FOR_THIRDPARTY_LIST;
   	$text=$form->select_thirdparty_list(GETPOST('socid','int'),'socid',$filteronlist,1,1);
    if (empty($conf->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS) && empty($conf->dol_use_jmobile))
    {
    	$texthelp=$langs->trans("IfNeedToUseOhterObjectKeepEmpty");
    	print $form->textwithtooltip($text.' '.img_help(),$texthelp,1);
    }
    else print $text;
    print ' <a href="'.DOL_URL_ROOT.'/societe/soc.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'">'.$langs->trans("AddThirdParty").'</a>';
    print '</td></tr>';

    // Status
    if ($status != '')
    {
    	print '<tr><td>'.$langs->trans("Status").'</td><td>';
    	print '<input type="hidden" name="status" value="'.$status.'">';
    	print $object->LibStatut($status, 4);
	    print '</td></tr>';
    }

    // Public
    print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
    $array=array(0 => $langs->trans("PrivateProject"),1 => $langs->trans("SharedProject"));
    print $form->selectarray('public',$array,$object->public);
    print '</td></tr>';

    // Date start
    print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
    print $form->select_date(($date_start?$date_start:''),'projectstart',0,0,0,'',1,0,1);
    print '</td></tr>';

    // Date end
    print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
    print $form->select_date(($date_end?$date_end:-1),'projectend',0,0,0,'',1,0,1);
    print '</td></tr>';

    if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES))
    {
	    // Opportunity status
	    print '<tr><td>'.$langs->trans("OpportunityStatus").'</td>';
	    print '<td>';
	    print $formproject->selectOpportunityStatus('opp_status',$object->opp_status);
	    print '</tr>';

	    // Opportunity amount
	    print '<tr><td>'.$langs->trans("OpportunityAmount").'</td>';
	    print '<td><input size="5" type="text" name="opp_amount" value="'.(GETPOST('opp_amount')!=''?price(GETPOST('opp_amount')):'').'"></td>';
	    print '</tr>';
    }

	// Budget
	print '<tr><td>'.$langs->trans("Budget").'</td>';
	print '<td><input size="5" type="text" name="budget_amount" value="'.(GETPOST('budget_amount')!=''?price(GETPOST('budget_amount')):'').'"></td>';
	print '</tr>';

    // Description
    print '<tr><td class="tdtop">'.$langs->trans("Description").'</td>';
    print '<td>';
    print '<textarea name="description" wrap="soft" cols="80" rows="'.ROWS_3.'">'.$_POST["description"].'</textarea>';
    print '</td></tr>';

    // Other options
    $parameters=array();
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook
    if (empty($reshook) && ! empty($extrafields->attribute_label))
    {
    	print $object->showOptionals($extrafields,'edit');
    }

    print '</table>';

    dol_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button" value="'.$langs->trans("CreateDraft").'">';
    if (! empty($backtopage))
    {
        print ' &nbsp; &nbsp; ';
	    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
    }
    print '</div>';

    print '</form>';

}
else
{
    /*
     * Show or edit
     */

    $res=$object->fetch_optionals($object->id,$extralabels);

    // To verify role of users
    $userAccess = $object->restrictedProjectArea($user,'read');
    $userWrite  = $object->restrictedProjectArea($user,'write');
    $userDelete = $object->restrictedProjectArea($user,'delete');
    //print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;


    // Confirmation validation
    if ($action == 'validate')
    {
        print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateProject'), $langs->trans('ConfirmValidateProject'), 'confirm_validate','',0,1);
    }
    // Confirmation close
    if ($action == 'close')
    {
        print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans("CloseAProject"),$langs->trans("ConfirmCloseAProject"),"confirm_close",'','',1);
    }
    // Confirmation reopen
    if ($action == 'reopen')
    {
        print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans("ReOpenAProject"),$langs->trans("ConfirmReOpenAProject"),"confirm_reopen",'','',1);
    }
    // Confirmation delete
    if ($action == 'delete')
    {
        $text=$langs->trans("ConfirmDeleteAProject");
        $task=new Task($db);
        $taskarray=$task->getTasksArray(0,0,$object->id,0,0);
        $nboftask=count($taskarray);
        if ($nboftask) $text.='<br>'.img_warning().' '.$langs->trans("ThisWillAlsoRemoveTasks",$nboftask);
        print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans("DeleteAProject"),$text,"confirm_delete",'','',1);
    }

    // Clone confirmation
    if ($action == 'clone')
    {
        $formquestion=array(
    		'text' => $langs->trans("ConfirmClone"),
            array('type' => 'checkbox', 'name' => 'clone_contacts',		'label' => $langs->trans("CloneContacts"), 			'value' => true),
            array('type' => 'checkbox', 'name' => 'clone_tasks',   		'label' => $langs->trans("CloneTasks"), 			'value' => true),
        	array('type' => 'checkbox', 'name' => 'move_date',   		'label' => $langs->trans("CloneMoveDate"), 			'value' => true),
            array('type' => 'checkbox', 'name' => 'clone_notes',   		'label' => $langs->trans("CloneNotes"), 			'value' => true),
        	array('type' => 'checkbox', 'name' => 'clone_project_files','label' => $langs->trans("CloneProjectFiles"),	    'value' => false),
        	array('type' => 'checkbox', 'name' => 'clone_task_files',	'label' => $langs->trans("CloneTaskFiles"),         'value' => false)
        );

        print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("CloneProject"), $langs->trans("ConfirmCloneProject"), "confirm_clone", $formquestion, '', 1, 240);
    }



    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="'.$object->id.'">';
    print '<input type="hidden" name="comefromclone" value="'.$comefromclone.'">';


    $head=project_prepare_head($object);
    dol_fiche_head($head, 'project', $langs->trans("Project"),0,($object->public?'projectpub':'project'));

    if ($action == 'edit' && $userWrite > 0)
    {
        print '<table class="border" width="100%">';

        // Ref
        $suggestedref=$object->ref;
        print '<tr><td class="fieldrequired" width="30%">'.$langs->trans("Ref").'</td>';
        print '<td><input size="12" name="ref" value="'.$suggestedref.'">';
        print ' '.$form->textwithpicto('', $langs->trans("YouCanCompleteRef", $suggestedref));
        print '</td></tr>';

        // Label
        print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td>';
        print '<td><input size="80" name="title" value="'.$object->title.'"></td></tr>';

        // Thirdparty
        print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
	    $filteronlist='';
	    if (! empty($conf->global->PROJECT_FILTER_FOR_THIRDPARTY_LIST)) $filteronlist=$conf->global->PROJECT_FILTER_FOR_THIRDPARTY_LIST;
        $text=$form->select_thirdparty_list($object->thirdparty->id, 'socid', $filteronlist, 1, 1);
        $texthelp=$langs->trans("IfNeedToUseOhterObjectKeepEmpty");
        print $form->textwithtooltip($text.' '.img_help(), $texthelp, 1, 0, '', '', 2);
        print '</td></tr>';

        // Visibility
        print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
        $array=array(0 => $langs->trans("PrivateProject"),1 => $langs->trans("SharedProject"));
        print $form->selectarray('public',$array,$object->public);
        print '</td></tr>';

        // Status
        print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

        // Date start
        print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
        print $form->select_date($object->date_start?$object->date_start:-1,'projectstart',0,0,0,'',1,0,1);
        print ' &nbsp; &nbsp; <input type="checkbox" name="reportdate" value="yes" ';
        if ($comefromclone){print ' checked ';}
		print '/> '. $langs->trans("ProjectReportDate");
        print '</td></tr>';

        // Date end
        print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
        print $form->select_date($object->date_end?$object->date_end:-1,'projectend',0,0,0,'',1,0,1);
        print '</td></tr>';

    	if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES))
	    {
	        // Opportunity status
		    print '<tr><td>'.$langs->trans("OpportunityStatus").'</td>';
	    	print '<td>';
		    print $formproject->selectOpportunityStatus('opp_status', $object->opp_status, 1);
		    print '</td>';
		    print '</tr>';

		    // Opportunity amount
		    print '<tr><td>'.$langs->trans("OpportunityAmount").'</td>';
		    print '<td><input size="5" type="text" name="opp_amount" value="'.(isset($_POST['opp_amount'])?GETPOST('opp_amount'):(strcmp($object->opp_amount,'')?price($object->opp_amount):'')).'"></td>';
		    print '</tr>';
	    }

	    // Budget
	    print '<tr><td>'.$langs->trans("Budget").'</td>';
	    print '<td><input size="5" type="text" name="budget_amount" value="'.(isset($_POST['budget_amount'])?GETPOST('budget_amount'):(strcmp($object->budget_amount,'')?price($object->budget_amount):'')).'"></td>';
	    print '</tr>';

	    // Description
        print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
        print '<td>';
        print '<textarea name="description" wrap="soft" cols="80" rows="'.ROWS_3.'">'.$object->description.'</textarea>';
        print '</td></tr>';

        // Other options
        $parameters=array();
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook
        if (empty($reshook) && ! empty($extrafields->attribute_label))
        {
        	print $object->showOptionals($extrafields,'edit');
        }

        print '</table>';
    }
    else
    {
        print '<table class="border" width="100%">';

        $linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php">'.$langs->trans("BackToList").'</a>';

        // Ref
        print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
        // Define a complementary filter for search of next/prev ref.
        if (! $user->rights->projet->all->lire)
        {
            $objectsListId = $object->getProjectsAuthorizedForUser($user,0,0);
            $object->next_prev_filter=" rowid in (".(count($objectsListId)?join(',',array_keys($objectsListId)):'0').")";
        }
        print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
        print '</td></tr>';

        // Label
        print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->title.'</td></tr>';

        // Third party
        print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
        if ($object->thirdparty->id > 0) print $object->thirdparty->getNomUrl(1, 'project');
        else print'&nbsp;';
        print '</td></tr>';

        // Visibility
        print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
        if ($object->public) print $langs->trans('SharedProject');
        else print $langs->trans('PrivateProject');
        print '</td></tr>';

        // Statut
        print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

        // Date start
        print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
        print dol_print_date($object->date_start,'day');
        print '</td></tr>';

        // Date end
        print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
        print dol_print_date($object->date_end,'day');
        print '</td></tr>';

    	if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES))
	    {
	        // Opportunity status
	        print '<tr><td>'.$langs->trans("OpportunityStatus").'</td><td>';
	        $code = dol_getIdFromCode($db, $object->opp_status, 'c_lead_status', 'rowid', 'code');
	        if ($code) print $langs->trans("OppStatus".$code);
	        print '</td></tr>';

	        // Opportunity Amount
	        print '<tr><td>'.$langs->trans("OpportunityAmount").'</td><td>';
	        if (strcmp($object->opp_amount,'')) print price($object->opp_amount,'',$langs,0,0,0,$conf->currency);
	        print '</td></tr>';
	    }

        // Budget
        print '<tr><td>'.$langs->trans("Budget").'</td><td>';
        if (strcmp($object->budget_amount, '')) print price($object->budget_amount,'',$langs,0,0,0,$conf->currency);
        print '</td></tr>';

        // Description
        print '<td class="tdtop">'.$langs->trans("Description").'</td><td>';
        print nl2br($object->description);
        print '</td></tr>';

        // Other options
        $parameters=array();
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook
        if (empty($reshook) && ! empty($extrafields->attribute_label))
        {
        	print $object->showOptionals($extrafields);
        }
        print '</table>';
    }

    dol_fiche_end();

	if ($action == 'edit' && $userWrite > 0)
	{
	    print '<div align="center">';
    	print '<input name="update" class="button" type="submit" value="'.$langs->trans("Modify").'">&nbsp; &nbsp; &nbsp;';
    	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
    	print '</div>';
	}

    print '</form>';


    /*
     * Boutons actions
     */
    print '<div class="tabsAction">';

    if ($action != "edit" )
    {
        // Modify
        if ($object->statut != 2 && $user->rights->projet->creer)
        {
            if ($userWrite > 0)
            {
                print '<div class="inline-block divButAction"><a class="butAction" href="card.php?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>';
            }
            else
            {
                print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('Modify').'</a></div>';
            }
        }

    	// Validate
        if ($object->statut == 0 && $user->rights->projet->creer)
        {
            if ($userWrite > 0)
            {
                print '<div class="inline-block divButAction"><a class="butAction" href="card.php?id='.$object->id.'&action=validate">'.$langs->trans("Validate").'</a></div>';
            }
            else
            {
                print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('Validate').'</a></div>';
            }
        }

        // Close
        if (($object->statut == 0 || $object->statut == 1) && $user->rights->projet->creer)
        {
            if ($userWrite > 0)
            {
                print '<div class="inline-block divButAction"><a class="butAction" href="card.php?id='.$object->id.'&amp;action=close">'.$langs->trans("Close").'</a></div>';
            }
            else
            {
                print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('Close').'</a></div>';
            }
        }

        // Reopen
        if ($object->statut == 2 && $user->rights->projet->creer)
        {
            if ($userWrite > 0)
            {
                print '<div class="inline-block divButAction"><a class="butAction" href="card.php?id='.$object->id.'&amp;action=reopen">'.$langs->trans("ReOpen").'</a></div>';
            }
            else
            {
                print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('ReOpen').'</a></div>';
            }
        }

        // Clone
        if ($user->rights->projet->creer)
        {
            if ($userWrite > 0)
            {
                print '<div class="inline-block divButAction"><a class="butAction" href="card.php?id='.$object->id.'&action=clone">'.$langs->trans('ToClone').'</a></div>';
            }
            else
            {
                print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('ToClone').'</a></div>';
            }
        }

        // Delete
        if ($user->rights->projet->supprimer)
        {
            if ($userDelete > 0)
            {
                print '<div class="inline-block divButAction"><a class="butAction" href="card.php?id='.$object->id.'&amp;action=delete">'.$langs->trans("Delete").'</a></div>';
            }
            else
            {
                print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('Delete').'</a></div>';
            }
        }
    }

    print "</div>";
    print "<br>\n";

    if ($action != 'presend')
    {
        print '<div class="fichecenter"><div class="fichehalfleft">';
        print '<a name="builddoc"></a>'; // ancre

        /*
         * Documents generes
         */
        $filename=dol_sanitizeFileName($object->ref);
        $filedir=$conf->projet->dir_output . "/" . dol_sanitizeFileName($object->ref);
        $urlsource=$_SERVER["PHP_SELF"]."?id=".$object->id;
        $genallowed=($user->rights->projet->lire && $userAccess > 0);
        $delallowed=($user->rights->projet->creer && $userWrite > 0);

        $var=true;

        $somethingshown=$formfile->show_documents('project',$filename,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf);

        print '</div></div class="fichehalfright">';

        if (!empty($object->id))
        {
	        // List of actions on element
	        include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	        $formactions=new FormActions($db);
	        $somethingshown=$formactions->showactions($object,'project',$socid);
        }

        print '</div>';
    }

    // Hook to add more things on page
    $parameters=array();
    $reshook=$hookmanager->executeHooks('mainCardTabAddMore',$parameters,$object,$action); // Note that $action and $object may have been modified by hook
}

llxFooter();

$db->close();
