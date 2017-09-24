<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@capnetworks.com>
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
 *	\file       htdocs/projet/tasks/task.php
 *	\ingroup    project
 *	\brief      Page of a project task
 */

require ("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/modules_task.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

$langs->load("projects");
$langs->load("companies");

$id=GETPOST('id','int');
$idcomment=GETPOST('idcomment','int');
$ref=GETPOST("ref",'alpha',1);          // task ref
$taskref=GETPOST("taskref",'alpha');    // task ref
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$withproject=GETPOST('withproject','int');
$project_ref=GETPOST('project_ref','alpha');
$planned_workload=((GETPOST('planned_workloadhour','int')!='' || GETPOST('planned_workloadmin','int')!='') ? (GETPOST('planned_workloadhour','int')>0?GETPOST('planned_workloadhour','int')*3600:0) + (GETPOST('planned_workloadmin','int')>0?GETPOST('planned_workloadmin','int')*60:0) : '');

// Security check
$socid=0;
//if ($user->societe_id > 0) $socid = $user->societe_id;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
if (! $user->rights->projet->lire) accessforbidden();

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('projecttaskcard','globalcard'));

$task = new Task($db);
$object = new TaskComment($db);
$extrafields = new ExtraFields($db);
$projectstatic = new Project($db);
$userstatic = new User($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($task->table_element);


/*
 * Actions
 */

if ($action == 'addcomment')
{
	if (!empty($_POST['comment_description']))
	{
		$object->description = GETPOST('comment_description');
		$object->datec = time();
		$object->fk_task = $id;
		$object->fk_user = $user->id;
		$object->entity = $conf->entity;
		if ($object->create($user) > 0)
		{
			setEventMessages($langs->trans("CommentAdded"), null, 'mesgs');
			header('Location: '.DOL_URL_ROOT.'/projet/tasks/comment.php?id='.$id.($withproject?'&withproject=1':''));
			exit;
		}
		else
		{
			setEventMessages($task->error,$task->errors,'errors');
			$action='';
		}
	}
}
if ($action == 'deletecomment')
{
	if ($object->fetch($idcomment) >= 0)
	{
		if ($object->delete($user) > 0)
		{
			setEventMessages($langs->trans("CommentDeleted"), null, 'mesgs');
			header('Location: '.DOL_URL_ROOT.'/projet/tasks/comment.php?id='.$id.($withproject?'&withproject=1':''));
			exit;
		}
		else
		{
			setEventMessages($task->error,$task->errors,'errors');
			$action='';
		}
	}
}

// Retreive First Task ID of Project if withprojet is on to allow project prev next to work
if (! empty($project_ref) && ! empty($withproject))
{
	if ($projectstatic->fetch('',$project_ref) > 0)
	{
		$tasksarray=$task->getTasksArray(0, 0, $projectstatic->id, $socid, 0);
		if (count($tasksarray) > 0)
		{
			$id=$tasksarray[0]->id;
		}
		else
		{
			header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.(empty($mode)?'':'&mode='.$mode));
		}
	}
}

/*
 * View
*/


llxHeader('', $langs->trans("TaskComment"));

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

if ($id > 0 || ! empty($ref))
{
	if ($task->fetch($id,$ref) > 0)
	{
		$res=$task->fetch_optionals($task->id,$extralabels);

		$result=$projectstatic->fetch($task->fk_project);
		if (! empty($projectstatic->socid)) $projectstatic->fetch_thirdparty();

		$task->project = clone $projectstatic;

		$userWrite  = $projectstatic->restrictedProjectArea($user,'write');

		if (! empty($withproject))
		{
			// Tabs for project
			$tab='tasks';
			$head=project_prepare_head($projectstatic);
			dol_fiche_head($head, $tab, $langs->trans("Project"), -1, ($projectstatic->public?'projectpub':'project'));

			$param=($mode=='mine'?'&mode=mine':'');

			// Project card

            $linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

            $morehtmlref='<div class="refidno">';
            // Title
            $morehtmlref.=$projectstatic->title;
            // Thirdparty
            if ($projectstatic->thirdparty->id > 0)
            {
                $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $projectstatic->thirdparty->getNomUrl(1, 'project');
            }
            $morehtmlref.='</div>';

            // Define a complementary filter for search of next/prev ref.
            if (! $user->rights->projet->all->lire)
            {
                $tasksListId = $projectstatic->getProjectsAuthorizedForUser($user,0,0);
                $projectstatic->next_prev_filter=" rowid in (".(count($tasksListId)?join(',',array_keys($tasksListId)):'0').")";
            }

            dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

            print '<div class="fichecenter">';
            print '<div class="fichehalfleft">';
            print '<div class="underbanner clearboth"></div>';

            print '<table class="border" width="100%">';

            // Visibility
            print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
            if ($projectstatic->public) print $langs->trans('SharedProject');
            else print $langs->trans('PrivateProject');
            print '</td></tr>';

            // Date start - end
            print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
            print dol_print_date($projectstatic->date_start,'day');
            $end=dol_print_date($projectstatic->date_end,'day');
            if ($end) print ' - '.$end;
            print '</td></tr>';

            // Budget
            print '<tr><td>'.$langs->trans("Budget").'</td><td>';
            if (strcmp($projectstatic->budget_amount, '')) print price($projectstatic->budget_amount,'',$langs,1,0,0,$conf->currency);
            print '</td></tr>';

            // Other attributes
            $cols = 2;
            //include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

            print '</table>';

            print '</div>';
            print '<div class="fichehalfright">';
            print '<div class="ficheaddleft">';
            print '<div class="underbanner clearboth"></div>';

            print '<table class="border" width="100%">';

            // Description
            print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
            print nl2br($projectstatic->description);
            print '</td></tr>';

            // Categories
            if($conf->categorie->enabled) {
                print '<tr><td valign="middle">'.$langs->trans("Categories").'</td><td>';
                print $form->showCategories($projectstatic->id,'project',1);
                print "</td></tr>";
            }

            print '</table>';

            print '</div>';
            print '</div>';
            print '</div>';

            print '<div class="clearboth"></div>';

			dol_fiche_end();

			print '<br>';
		}

		$head=task_prepare_head($task);

		/*
		 * Fiche tache en mode visu
		 */
		$param=($withproject?'&withproject=1':'');
		$linkback=$withproject?'<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'&restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>':'';

		dol_fiche_head($head, 'task_comment', $langs->trans("Task"), -1, 'projecttask');

		if ($action == 'delete')
		{
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$_GET["id"].'&withproject='.$withproject,$langs->trans("DeleteATask"),$langs->trans("ConfirmDeleteATask"),"confirm_delete");
		}

		if (! GETPOST('withproject') || empty($projectstatic->id))
		{
		    $projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1);
		    $task->next_prev_filter=" fk_projet in (".$projectsListId.")";
		}
		else $task->next_prev_filter=" fk_projet = ".$projectstatic->id;

		$morehtmlref='';

		// Project
		if (empty($withproject))
		{
		    $morehtmlref.='<div class="refidno">';
		    $morehtmlref.=$langs->trans("Project").': ';
		    $morehtmlref.=$projectstatic->getNomUrl(1);
		    $morehtmlref.='<br>';

		    // Third party
		    $morehtmlref.=$langs->trans("ThirdParty").': ';
		    if (!empty($projectstatic->thirdparty)) {
                   $morehtmlref.=$projectstatic->thirdparty->getNomUrl(1);
		    }
		    $morehtmlref.='</div>';
		}

		dol_banner_tab($task, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $param);

		print '<div class="fichecenter">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border" width="100%">';

		// Nb comments
		print '<td class="titlefield">'.$langs->trans("TaskNbComments").'</td><td>';
		print $task->getNbComments();
		print '</td></tr>';

		// Other attributes
		$cols = 3;
		$parameyers=array('socid'=>$socid);
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';

		dol_fiche_end();


		print '<br>';
		print '<div id="comment">';

		// Add comment

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$task->id.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="addcomment">';
		print '<input type="hidden" name="id" value="'.$task->id.'">';
		print '<input type="hidden" name="withproject" value="'.$withproject.'">';

		print '<table class="noborder nohover" width="100%">';

		print '<tr class="liste_titre">';
		print '<td width="25%">'.$langs->trans("Comments").'</td>';
		print '<td width="25%"></td>';
		print '<td width="25%"></td>';
		print '<td width="25%"></td>';
		print "</tr>\n";

		print '<tr class="oddeven">';
		print '<td></td>';

		// Description
		print '<td colspan="2">';

		$desc = ($_POST['comment_description']?$_POST['comment_description']:'');

		$doleditor = new DolEditor('comment_description', $desc, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '100%');
		print $doleditor->Create(1);

		print '</td>';

		print '<td align="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
		print '</td></tr>';
		print '</table></form>';

		// List of comments
		if(!empty($task->comments)) {
			// Default color for current user
			$TColors = array($user->id => array('bgcolor'=>'efefef','color'=>'555'));
			$first = true;
			foreach($task->comments as $comment) {
				$fk_user = $comment->fk_user;
				$userstatic->fetch($fk_user);
				if(empty($TColors[$fk_user])) {
					$bgcolor = random_color(180,240);
					if(!empty($userstatic->color)) {
						$bgcolor = $userstatic->color;
					}
					$color = (colorIsLight($bgcolor))?'555':'fff';
					$TColors[$fk_user] = array('bgcolor'=>$bgcolor,'color'=>$color);
				}
				print '<div class="width100p" style="color:#'.$TColors[$fk_user]['color'].'">';
				if($comment->fk_user == $user->id) {
					print '<div class="width25p float">&nbsp;</div>';
				}

				print '<div class="width75p float comment comment-table" style="background-color:#'.$TColors[$fk_user]['bgcolor'].'">';
				print '<div class="comment-info comment-cell">';
				if (! empty($user->photo))
				{
					print Form::showphoto('userphoto', $userstatic, 80, 0, 0, '', 'small', 0, 1).'<br/>';
				}
				print $langs->trans('User').' : '.$userstatic->getNomUrl().'<br/>';
				print $langs->trans('Date').' : '.dol_print_date($comment->datec,'dayhoursec');
				print '</div>'; // End comment-info

				print '<div class="comment-cell comment-right">';
				print '<div class="comment-table width100p">';
				print '<div class="comment-description comment-cell">';
				print $comment->description;
				print '</div>'; // End comment-description
				if(($first && $fk_user == $user->id) || $user->admin == 1) {
					print '<a class="comment-delete comment-cell" href="'.DOL_URL_ROOT.'/projet/tasks/comment.php?action=deletecomment&id='.$id.'&withproject=1&idcomment='.$comment->id.'" title="'.$langs->trans('Delete').'">';
					print img_picto('', 'delete.png');
					print '</a>';
				}
				print '</div>'; // End comment-table
				print '</div>'; // End comment-right
				print '</div>'; // End comment

				if($comment->fk_user != $user->id) {
					print '<div class="width25p float">&nbsp;</div>';
				}
				print '<div class="clearboth"></div>';
				print '</div>'; // end 100p

				$first = false;
			}
		}

		print '<br>';
		print '</div>';

	}
}


llxFooter();
$db->close();
