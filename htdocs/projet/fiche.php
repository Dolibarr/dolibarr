<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/projet/fiche.php
 *	\ingroup    projet
 *	\brief      Project card
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/task.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/modules/project/modules_project.php");

$langs->load("projects");
$langs->load('companies');

$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');
$backtopage=GETPOST('backtopage','alpha');

if ($id == '' && $ref == '' && ($action != "create" && $action != "add" && $action != "update" && ! $_POST["cancel"])) accessforbidden();

$mine = GETPOST('mode')=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$project = new Project($db);
if ($ref)
{
    $project->fetch(0,$ref);
    $id=$project->id;
}

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $id);




/*
 * Actions
 */

// Cancel
if (GETPOST("cancel") && ! empty($backtopage))
{
	if (GETPOST("comefromclone")==1)
	{
		$project = new Project($db);
	    $project->fetch($id);
	    $result=$project->delete($user);
	    if ($result > 0)
	    {
	        Header("Location: index.php");
	        exit;
	    }
	    else
	    {
	        dol_syslog($project->error,LOG_DEBUG);
	        $mesg='<div class="error">'.$langs->trans("CantRemoveProject").'</div>';
	    }
	}
    header("Location: ".$backtopage);
    exit;
}

//if cancel and come from clone then delete the cloned project
if (GETPOST("cancel") && (GETPOST("comefromclone")==1))
{
	$project = new Project($db);
    $project->fetch($id);
    $result=$project->delete($user);
    if ($result > 0)
    {
        Header("Location: index.php");
        exit;
    }
    else
    {
        dol_syslog($project->error,LOG_DEBUG);
        $mesg='<div class="error">'.$langs->trans("CantRemoveProject").'</div>';
    }
}

if ($action == 'add' && $user->rights->projet->creer)
{
    $error=0;
    if (empty($_POST["ref"]))
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Ref")).'</div>';
        $error++;
    }
    if (empty($_POST["title"]))
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")).'</div>';
        $error++;
    }

    if (! $error)
    {
        $error=0;

        $db->begin();

        $project->ref             = GETPOST('ref','alpha');
        $project->title           = GETPOST('title','alpha');
        $project->socid           = GETPOST('socid','int');
        $project->description     = GETPOST('description','alpha');
        $project->public          = GETPOST('public','alpha');
        $project->datec=dol_now();
        $project->date_start=dol_mktime(12,0,0,GETPOST('projectmonth','int'),GETPOST('projectday','int'),GETPOST('projectyear','int'));
        $project->date_end=dol_mktime(12,0,0,GETPOST('projectendmonth','int'),GETPOST('projectendday','int'),GETPOST('projectendyear','int'));

        $result = $project->create($user);
        if ($result > 0)
        {
            // Add myself as project leader
            $result = $project->add_contact($user->id, 'PROJECTLEADER', 'internal');
            if ($result < 0)
            {
                $langs->load("errors");
                $mesg='<div class="error">'.$langs->trans($project->error).'</div>';
                $error++;
            }
        }
        else
        {
            $langs->load("errors");
            $mesg='<div class="error">'.$langs->trans($project->error).'</div>';
            $error++;
        }

        if (! $error)
        {
            $db->commit();

            Header("Location:fiche.php?id=".$project->id);
            exit;
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
        //$_GET["id"]=$_POST["id"]; // On retourne sur la fiche projet
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Ref")).'</div>';
    }
    if (empty($_POST["title"]))
    {
        $error++;
        //$_GET["id"]=$_POST["id"]; // On retourne sur la fiche projet
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")).'</div>';
    }
    if (! $error)
    {
        $project->fetch($id);

		$old_start_date = $project->date_start;

        $project->ref             = GETPOST('ref','alpha');
        $project->title           = GETPOST('title','alpha');
        $project->socid           = GETPOST('socid','int');
        $project->description     = GETPOST('description','alpha');
        $project->public          = GETPOST('public','alpha');
        $project->date_start   = empty($_POST["project"])?'':dol_mktime(0,0,0,GETPOST('projectmonth'),GETPOST('projectday'),GETPOST('projectyear'));
        $project->date_end     = empty($_POST["projectend"])?'':dol_mktime(0,0,0,GETPOST('projectendmonth'),GETPOST('projectendday'),GETPOST('projectendyear'));

        $result=$project->update($user);

        $id=$project->id;  // On retourne sur la fiche projet

        if (GETPOST("reportdate") && ($project->date_start!=$old_start_date))
        {
        	$result=$project->shiftTaskDate($old_start_date);
        	if (!$result)
        	{
        		$error++;
        		$mesg='<div class="error">'.$langs->trans("ErrorShiftTaskDate").':'.$project->error.'</div>';
        	}
        }
    }
    else
    {
        $action='edit';
    }
}

// Build doc
if ($action == 'builddoc' && $user->rights->projet->creer)
{
    $project->fetch($id);
    if (GETPOST('model'))
    {
        $project->setDocModel($user, GETPOST('model'));
    }

    $outputlangs = $langs;
    if (GETPOST('lang_id'))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang(GETPOST('lang_id'));
    }
    $result=project_pdf_create($db, $project, $project->modelpdf, $outputlangs);
    if ($result <= 0)
    {
        dol_print_error($db,$result);
        exit;
    }
    else
    {
        Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$project->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
        exit;
    }
}


if ($action == 'confirm_validate' && GETPOST('confirm') == 'yes')
{
    $project->fetch($id);

    $result = $project->setValid($user);
    if ($result <= 0)
    {
        $mesg='<div class="error">'.$project->error.'</div>';
    }
}

if ($action == 'confirm_close' && GETPOST('confirm') == 'yes')
{
    $project->fetch($id);
    $result = $project->setClose($user);
    if ($result <= 0)
    {
        $mesg='<div class="error">'.$project->error.'</div>';
    }
}

if ($action == 'confirm_reopen' && GETPOST('confirm') == 'yes')
{
    $project->fetch($id);
    $result = $project->setValid($user);
    if ($result <= 0)
    {
        $mesg='<div class="error">'.$project->error.'</div>';
    }
}

if ($action == 'confirm_delete' && GETPOST("confirm") == "yes" && $user->rights->projet->supprimer)
{
    $project->fetch($id);
    $result=$project->delete($user);
    if ($result > 0)
    {
        Header("Location: index.php");
        exit;
    }
    else
    {
        dol_syslog($project->error,LOG_DEBUG);
        $mesg='<div class="error">'.$langs->trans("CantRemoveProject").'</div>';
    }
}

if ($action == 'confirm_clone' && $user->rights->projet->creer && GETPOST('confirm') == 'yes')
{
	$idtoclone=$id;
	$project = new Project($db);
    $project->fetch($idtoclone);
    $clone_contacts=GETPOST('clone_contacts')?1:0;
    $clone_tasks=GETPOST('clone_tasks')?1:0;
    $clone_files=GETPOST('clone_files')?1:0;
    $clone_notes=GETPOST('clone_notes')?1:0;
    $result=$project->createFromClone($idtoclone,$clone_contacts,$clone_tasks,$clone_files,$clone_notes);
    if ($result <= 0)
    {
        $mesg='<div class="error">'.$project->error.'</div>';
    }
    else
    {
    	$id=$result;
    	$action='edit';
    	$comefromclone=true;
    }
}


/*
 *	View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$userstatic = new User($db);


$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("",$langs->trans("Projects"),$help_url);


if ($action == 'create' && $user->rights->projet->creer)
{
    /*
     * Create
     */
    print_fiche_titre($langs->trans("NewProject"));

    dol_htmloutput_mesg($mesg);

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

    print '<table class="border" width="100%">';

    $project = new Project($db);

    $defaultref='';
    $obj = empty($conf->global->PROJECT_ADDON)?'mod_project_simple':$conf->global->PROJECT_ADDON;
    if (! empty($conf->global->PROJECT_ADDON) && is_readable(DOL_DOCUMENT_ROOT ."/core/modules/project/".$conf->global->PROJECT_ADDON.".php"))
    {
        require_once(DOL_DOCUMENT_ROOT ."/core/modules/project/".$conf->global->PROJECT_ADDON.".php");
        $modProject = new $obj;
        $defaultref = $modProject->getNextValue($soc,$project);
    }

    if (is_numeric($defaultref) && $defaultref <= 0) $defaultref='';

    // Ref
    print '<tr><td><span class="fieldrequired">'.$langs->trans("Ref").'</span></td><td><input size="12" type="text" name="ref" value="'.($_POST["ref"]?$_POST["ref"]:$defaultref).'"></td></tr>';

    // Label
    print '<tr><td><span class="fieldrequired">'.$langs->trans("Label").'</span></td><td><input size="30" type="text" name="title" value="'.$_POST["title"].'"></td></tr>';

    // Customer
    print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
    $text=$form->select_company(GETPOST('socid','int'),'socid','',1,1);
    $texthelp=$langs->trans("IfNeedToUseOhterObjectKeepEmpty");
    print $form->textwithtooltip($text.' '.img_help(),$texthelp,1);
    print '</td></tr>';

    // Public
    print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
    $array=array(0 => $langs->trans("PrivateProject"),1 => $langs->trans("SharedProject"));
    print $form->selectarray('public',$array,$project->public);
    print '</td></tr>';

    // Date start
    print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
    print $form->select_date('','project');
    print '</td></tr>';

    // Date end
    print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
    print $form->select_date(-1,'projectend');
    print '</td></tr>';

    // Description
    print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
    print '<td>';
    print '<textarea name="description" wrap="soft" cols="80" rows="'.ROWS_3.'">'.$_POST["description"].'</textarea>';
    print '</td></tr>';

    print '</table>';

    print '<br><center>';
    print '<input type="submit" class="button" value="'.$langs->trans("Create").'">';
    if (! empty($backtopage))
    {
        print ' &nbsp; &nbsp; ';
	    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
    }
    print '</center>';

    print '</form>';

}
else
{
    /*
     * Show or edit
     */

    dol_htmloutput_mesg($mesg);

    $project = new Project($db);
    $project->fetch($id,$ref);

    if ($project->societe->id > 0)  $result=$project->societe->fetch($project->societe->id);

    // To verify role of users
    $userAccess = $project->restrictedProjectArea($user,'read');
    $userWrite  = $project->restrictedProjectArea($user,'write');
    $userDelete = $project->restrictedProjectArea($user,'delete');
    //print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;


    $head=project_prepare_head($project);
    dol_fiche_head($head, 'project', $langs->trans("Project"),0,($project->public?'projectpub':'project'));

    // Confirmation validation
    if ($action == 'validate')
    {
        $ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$project->id, $langs->trans('ValidateProject'), $langs->trans('ConfirmValidateProject'), 'confirm_validate','',0,1);
        if ($ret == 'html') print '<br>';
    }
    // Confirmation close
    if ($action == 'close')
    {
        $ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$project->id,$langs->trans("CloseAProject"),$langs->trans("ConfirmCloseAProject"),"confirm_close",'','',1);
        if ($ret == 'html') print '<br>';
    }
    // Confirmation reopen
    if ($action == 'reopen')
    {
        $ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$project->id,$langs->trans("ReOpenAProject"),$langs->trans("ConfirmReOpenAProject"),"confirm_reopen",'','',1);
        if ($ret == 'html') print '<br>';
    }
    // Confirmation delete
    if ($action == 'delete')
    {
        $text=$langs->trans("ConfirmDeleteAProject");
        $task=new Task($db);
        $taskarray=$task->getTasksArray(0,0,$project->id,0,0);
        $nboftask=count($taskarray);
        if ($nboftask) $text.='<br>'.img_warning().' '.$langs->trans("ThisWillAlsoRemoveTasks",$nboftask);
        $ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$project->id,$langs->trans("DeleteAProject"),$text,"confirm_delete",'','',1);
        if ($ret == 'html') print '<br>';
    }

    // Clone confirmation
    if ($action == 'clone')
    {
        $formquestion=array(
    		'text' => $langs->trans("ConfirmClone"),
            array('type' => 'checkbox', 'name' => 'clone_contacts','label' => $langs->trans("CloneContacts"), 'value' => true),
            array('type' => 'checkbox', 'name' => 'clone_tasks',   'label' => $langs->trans("CloneTasks"), 'value' => true),
            array('type' => 'checkbox', 'name' => 'clone_notes',   'label' => $langs->trans("CloneNotes"), 'value' => true),
            array('type' => 'checkbox', 'name' => 'clone_files',   'label' => $langs->trans("CloneFiles"), 'value' => false)
        );

        print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$project->id, $langs->trans("CloneProject"), $langs->trans("ConfirmCloneProject"), "confirm_clone", $formquestion, '', 1, 240);
    }

    if ($action == 'edit' && $userWrite > 0)
    {
        print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="id" value="'.$project->id.'">';
        print '<input type="hidden" name="comefromclone" value="'.$comefromclone.'">';

        print '<table class="border" width="100%">';

        // Ref
        print '<tr><td width="30%">'.$langs->trans("Ref").'</td>';
        print '<td><input size="12" name="ref" value="'.$project->ref.'"></td></tr>';

        // Label
        print '<tr><td>'.$langs->trans("Label").'</td>';
        print '<td><input size="30" name="title" value="'.$project->title.'"></td></tr>';

        // Customer
        print '<tr><td>'.$langs->trans("Company").'</td><td>';
        print $form->select_company($project->societe->id,'socid','',1,1);
        print '</td></tr>';

        // Visibility
        print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
        $array=array(0 => $langs->trans("PrivateProject"),1 => $langs->trans("SharedProject"));
        print $form->selectarray('public',$array,$project->public);
        print '</td></tr>';

        // Statut
        print '<tr><td>'.$langs->trans("Status").'</td><td>'.$project->getLibStatut(4).'</td></tr>';

        // Date start
        print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
        print $form->select_date($project->date_start,'project');
        print '<input type="checkbox" name="reportdate" value="yes" ';
        if ($comefromclone){print ' checked="checked" ';}
		print '/>'. $langs->trans("ProjectReportDate");
        print '</td></tr>';

        // Date end
        print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
        print $form->select_date($project->date_end?$project->date_end:-1,'projectend');
        print '</td></tr>';

        // Description
        print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
        print '<td>';
        print '<textarea name="description" wrap="soft" cols="80" rows="'.ROWS_3.'">'.$project->description.'</textarea>';
        print '</td></tr>';

        print '</table>';

        print '<div align="center"><br>';
        print '<input name="update" class="button" type="submit" value="'.$langs->trans("Modify").'"> &nbsp; ';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></div>';

        print '</form>';
    }
    else
    {
        print '<table class="border" width="100%">';

        // Ref
        print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
        // Define a complementary filter for search of next/prev ref.
        if (! $user->rights->projet->all->lire)
        {
            $projectsListId = $project->getProjectsAuthorizedForUser($user,$mine,0);
            $project->next_prev_filter=" rowid in (".(count($projectsListId)?join(',',array_keys($projectsListId)):'0').")";
        }
        print $form->showrefnav($project,'ref','',1,'ref','ref');
        print '</td></tr>';

        // Label
        print '<tr><td>'.$langs->trans("Label").'</td><td>'.$project->title.'</td></tr>';

        // Third party
        print '<tr><td>'.$langs->trans("Company").'</td><td>';
        if ($project->societe->id > 0) print $project->societe->getNomUrl(1);
        else print'&nbsp;';
        print '</td></tr>';

        // Visibility
        print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
        if ($project->public) print $langs->trans('SharedProject');
        else print $langs->trans('PrivateProject');
        print '</td></tr>';

        // Statut
        print '<tr><td>'.$langs->trans("Status").'</td><td>'.$project->getLibStatut(4).'</td></tr>';

        // Date start
        print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
        print dol_print_date($project->date_start,'day');
        print '</td></tr>';

        // Date end
        print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
        print dol_print_date($project->date_end,'day');
        print '</td></tr>';

        // Description
        print '<td valign="top">'.$langs->trans("Description").'</td><td>';
        print nl2br($project->description);
        print '</td></tr>';

        print '</table>';
    }

    dol_fiche_end();

    /*
     * Boutons actions
     */
    print '<div class="tabsAction">';

    if ($action != "edit" )
    {
    	// Validate
        if ($project->statut == 0 && $user->rights->projet->creer)
        {
            if ($userWrite > 0)
            {
                print '<a class="butAction" href="fiche.php?id='.$project->id.'&action=validate">'.$langs->trans("Valid").'</a>';
            }
            else
            {
                print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('Valid').'</a>';
            }
        }

        // Modify
        if ($project->statut != 2 && $user->rights->projet->creer)
        {
            if ($userWrite > 0)
            {
                print '<a class="butAction" href="fiche.php?id='.$project->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
            }
            else
            {
                print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('Modify').'</a>';
            }
        }

        // Close
        if ($project->statut == 1 && $user->rights->projet->creer)
        {
            if ($userWrite > 0)
            {
                print '<a class="butAction" href="fiche.php?id='.$project->id.'&amp;action=close">'.$langs->trans("Close").'</a>';
            }
            else
            {
                print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('Close').'</a>';
            }
        }

        // Reopen
        if ($project->statut == 2 && $user->rights->projet->creer)
        {
            if ($userWrite > 0)
            {
                print '<a class="butAction" href="fiche.php?id='.$project->id.'&amp;action=reopen">'.$langs->trans("ReOpen").'</a>';
            }
            else
            {
                print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('ReOpen').'</a>';
            }
        }

        // Clone
        if ($user->rights->projet->creer)
        {
            if ($userWrite > 0)
            {
                print '<a class="butAction" href="fiche.php?id='.$project->id.'&action=clone">'.$langs->trans('ToClone').'</a>';
            }
            else
            {
                print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('ToClone').'</a>';
            }
        }

        // Delete
        if ($user->rights->projet->supprimer)
        {
            if ($userDelete > 0)
            {
                print '<a class="butActionDelete" href="fiche.php?id='.$project->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
            }
            else
            {
                print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('Delete').'</a>';
            }
        }
    }

    print "</div>";
    print "<br>\n";

    if ($action != 'presend')
    {
        print '<table width="100%"><tr><td width="50%" valign="top">';
        print '<a name="builddoc"></a>'; // ancre


        /*
         * Documents generes
         */
        $filename=dol_sanitizeFileName($project->ref);
        $filedir=$conf->projet->dir_output . "/" . dol_sanitizeFileName($project->ref);
        $urlsource=$_SERVER["PHP_SELF"]."?id=".$project->id;
        $genallowed=($user->rights->projet->lire && $userAccess > 0);
        $delallowed=($user->rights->projet->creer && $userWrite > 0);

        $var=true;

        $somethingshown=$formfile->show_documents('project',$filename,$filedir,$urlsource,$genallowed,$delallowed,$project->modelpdf);

        print '</td><td valign="top" width="50%">';

        // List of actions on element
        include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php');
        $formactions=new FormActions($db);
        $somethingshown=$formactions->showactions($project,'project',$socid);

        print '</td></tr></table>';
    }
}

llxFooter();

$db->close();
?>
