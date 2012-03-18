<?php
/* Copyright (C) 2006-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/lib/project.lib.php
 *		\brief      Functions used by project module
 *      \ingroup    project
 */
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");


/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function project_prepare_head($object)
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/projet/fiche.php?id='.$object->id;
    $head[$h][1] = $langs->trans("Project");
    $head[$h][2] = 'project';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/projet/contact.php?id='.$object->id;
    $head[$h][1] = $langs->trans("ProjectContact");
    $head[$h][2] = 'contact';
    $h++;

    if ($conf->fournisseur->enabled || $conf->propal->enabled || $conf->commande->enabled || $conf->facture->enabled || $conf->contrat->enabled
    || $conf->ficheinter->enabled || $conf->agenda->enabled || $conf->deplacement->enabled)
    {
        $head[$h][0] = DOL_URL_ROOT.'/projet/element.php?id='.$object->id;
        $head[$h][1] = $langs->trans("Referers");
        $head[$h][2] = 'element';
        $h++;
    }

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'project');

    $head[$h][0] = DOL_URL_ROOT.'/projet/document.php?id='.$object->id;
    /*$filesdir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($object->ref);
     include_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');
    $listoffiles=dol_dir_list($filesdir,'files',1);
    $head[$h][1] = (count($listoffiles)?$langs->trans('DocumentsNb',count($listoffiles)):$langs->trans('Documents'));*/
    $head[$h][1] = $langs->trans('Documents');
    $head[$h][2] = 'document';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/projet/note.php?id='.$object->id;
    $head[$h][1] = $langs->trans('Notes');
    $head[$h][2] = 'notes';
    $h++;

    // Then tab for sub level of projet, i mean tasks
    $head[$h][0] = DOL_URL_ROOT.'/projet/tasks.php?id='.$object->id;
    $head[$h][1] = $langs->trans("Tasks");
    $head[$h][2] = 'tasks';
    $h++;

    /* Now this is a filter in the Task tab.
     $head[$h][0] = DOL_URL_ROOT.'/projet/tasks.php?id='.$object->id.'&mode=mine';
    $head[$h][1] = $langs->trans("MyTasks");
    $head[$h][2] = 'mytasks';
    $h++;
    */

    $head[$h][0] = DOL_URL_ROOT.'/projet/ganttview.php?id='.$object->id;
    $head[$h][1] = $langs->trans("Gantt");
   	$head[$h][2] = 'gantt';
   	$h++;

   	return $head;
}


/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function task_prepare_head($object)
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/projet/tasks/task.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'task_task';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/projet/tasks/contact.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');;
    $head[$h][1] = $langs->trans("TaskRessourceLinks");
    $head[$h][2] = 'task_contact';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/projet/tasks/time.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');;
    $head[$h][1] = $langs->trans("TimeSpent");
    $head[$h][2] = 'task_time';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'task');

    $head[$h][0] = DOL_URL_ROOT.'/projet/tasks/document.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');;
    /*$filesdir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($object->ref);
     include_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');
    $listoffiles=dol_dir_list($filesdir,'files',1);
    $head[$h][1] = (count($listoffiles)?$langs->trans('DocumentsNb',count($listoffiles)):$langs->trans('Documents'));*/
    $head[$h][1] = $langs->trans('Documents');
    $head[$h][2] = 'task_document';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/projet/tasks/note.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');;
    $head[$h][1] = $langs->trans('Notes');
    $head[$h][2] = 'task_notes';
    $h++;

    return $head;
}



/**
 *	Show a combo list with projects qualified for a third party
 *
 *	@param	int		$socid      Id third party (-1=all, 0=only projects not linked to a third party, id=projects not linked or linked to third party id)
 *	@param  int		$selected   Id project preselected
 *	@param  string	$htmlname   Nom de la zone html
 *	@param	int		$maxlength	Maximum length of label
 *	@return int         		Nbre of project if OK, <0 if KO
 */
function select_projects($socid=-1, $selected='', $htmlname='projectid', $maxlength=16)
{
	global $db,$user,$conf,$langs;

	$hideunselectables = false;
	if (! empty($conf->global->PROJECT_HIDE_UNSELECTABLES)) $hideunselectables = true;

	$projectsListId = false;
	if (empty($user->rights->projet->all->lire))
	{
		$projectstatic=new Project($db);
		$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1);
	}

	// Search all projects
	$sql = 'SELECT p.rowid, p.ref, p.title, p.fk_soc, p.fk_statut, p.public';
	$sql.= ' FROM '.MAIN_DB_PREFIX .'projet as p';
	$sql.= " WHERE p.entity = ".$conf->entity;
	if ($projectsListId) $sql.= " AND p.rowid IN (".$projectsListId.")";
	if ($socid == 0) $sql.= " AND (p.fk_soc=0 OR p.fk_soc IS NULL)";
	$sql.= " ORDER BY p.title ASC";

	dol_syslog("project.lib::select_projects sql=".$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		print '<select class="flat" name="'.$htmlname.'">';
		print '<option value="0">&nbsp;</option>';
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				// If we ask to filter on a company and user has no permission to see all companies and project is linked to another company, we hide project.
				if ($socid > 0 && (empty($obj->fk_soc) || $obj->fk_soc == $socid) && ! $user->rights->societe->lire)
				{
					// Do nothing
				}
				else
				{
					$labeltoshow=dol_trunc($obj->ref,18);
					//if ($obj->public) $labeltoshow.=' ('.$langs->trans("SharedProject").')';
					//else $labeltoshow.=' ('.$langs->trans("Private").')';
					if (!empty($selected) && $selected == $obj->rowid && $obj->fk_statut > 0)
					{
						print '<option value="'.$obj->rowid.'" selected="selected">'.$labeltoshow.'</option>';
					}
					else
					{
						$disabled=0;
						if (! $obj->fk_statut > 0)
						{
							$disabled=1;
							$labeltoshow.=' - '.$langs->trans("Draft");
						}
						if ($socid > 0 && (! empty($obj->fk_soc) && $obj->fk_soc != $socid))
						{
							$disabled=1;
							$labeltoshow.=' - '.$langs->trans("LinkedToAnotherCompany");
						}

						if ($hideunselectables && $disabled)
						{
							$resultat='';
						}
						else
						{
							$resultat='<option value="'.$obj->rowid.'"';
							if ($disabled) $resultat.=' disabled="disabled"';
							//if ($obj->public) $labeltoshow.=' ('.$langs->trans("Public").')';
							//else $labeltoshow.=' ('.$langs->trans("Private").')';
							$resultat.='>'.$labeltoshow;
							if (! $disabled) $resultat.=' - '.dol_trunc($obj->title,$maxlength);
							$resultat.='</option>';
						}
						print $resultat;
					}
				}
				$i++;
			}
		}
		print '</select>';
		$db->free($resql);
		return $num;
	}
	else
	{
		dol_print_error($db);
		return -1;
	}
}


/**
 * Show task lines with a particular parent
 *
 * @param	string	 	&$inc				Counter that count number of lines legitimate to show (for return)
 * @param 	int			$parent				Id of parent task to start
 * @param 	array		&$lines				Array of all tasks
 * @param 	int			&$level				Level of task
 * @param 	string		$var				Color
 * @param 	int			$showproject		Show project columns
 * @param	int			&$taskrole			Array of roles of user for each tasks
 * @param	int			$projectsListId		List of id of project allowed to user (separated with comma)
 * @return	void
 */
function projectLinesa(&$inc, $parent, &$lines, &$level, $var, $showproject, &$taskrole, $projectsListId='')
{
    global $user, $bc, $langs;
	global $projectstatic, $taskstatic;

    $lastprojectid=0;

    $projectsArrayId=explode(',',$projectsListId);

    $numlines=count($lines);

    $total=0;

    for ($i = 0 ; $i < $numlines ; $i++)
    {
        if ($parent == 0) $level = 0;

        // Process line
        // print "i:".$i."-".$lines[$i]->fk_project.'<br>';

        if ($lines[$i]->fk_parent == $parent)
        {
            // Show task line.
            $showline=1;
            $showlineingray=0;

            // If there is filters to use
            if (is_array($taskrole))
            {
                // If task not legitimate to show, search if a legitimate task exists later in tree
                if (! isset($taskrole[$lines[$i]->id]) && $lines[$i]->id != $lines[$i]->fk_parent)
                {
                    // So search if task has a subtask legitimate to show
                    $foundtaskforuserdeeper=0;
                    searchTaskInChild($foundtaskforuserdeeper,$lines[$i]->id,$lines,$taskrole);
                    //print '$foundtaskforuserpeeper='.$foundtaskforuserdeeper.'<br>';
                    if ($foundtaskforuserdeeper > 0)
                    {
                        $showlineingray=1;		// We will show line but in gray
                    }
                    else
                    {
                        $showline=0;			// No reason to show line
                    }
                }
            }

            if ($showline)
            {
                // Break on a new project
                if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid)
                {
                    $var = !$var;
                    $lastprojectid=$lines[$i]->fk_project;
                }

                print "<tr ".$bc[$var].">\n";

                // Project
                if ($showproject)
                {
                    print "<td>";
                    //var_dump($taskrole);
                    if ($showlineingray) print '<i>';
                    $projectstatic->id=$lines[$i]->fk_project;
                    $projectstatic->ref=$lines[$i]->projectref;
                    $projectstatic->public=$lines[$i]->public;
                    if ($lines[$i]->public || in_array($lines[$i]->fk_project,$projectsArrayId)) print $projectstatic->getNomUrl(1);
                    else print $projectstatic->getNomUrl(1,'nolink');
                    if ($showlineingray) print '</i>';
                    print "</td>";
                }

                // Ref of task
                print '<td>';
                if ($showlineingray)
                {
                    print '<i>'.img_object('','projecttask').' '.$lines[$i]->id.'</i>';
                }
                else
                {
                    $taskstatic->id=$lines[$i]->id;
                    $taskstatic->ref=$lines[$i]->id;
                    $taskstatic->label=($taskrole[$lines[$i]->id]?$langs->trans("YourRole").': '.$taskrole[$lines[$i]->id]:'');
                    print $taskstatic->getNomUrl(1,($showproject?'':'withproject'));
                }
                print '</td>';

                // Title of task
                print "<td>";
                if ($showlineingray) print '<i>';
                else print '<a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?id='.$lines[$i]->id.($showproject?'':'&withproject=1').'">';
                for ($k = 0 ; $k < $level ; $k++)
                {
                    print "&nbsp; &nbsp; &nbsp;";
                }
                print $lines[$i]->label;
                if ($showlineingray) print '</i>';
                else print '</a>';
                print "</td>\n";

                // Date start
                print '<td align="center">';
                print dol_print_date($lines[$i]->date_start,'day');
                print '</td>';

                // Date end
                print '<td align="center">';
                print dol_print_date($lines[$i]->date_end,'day');
                print '</td>';

                // Progress
                print '<td align="right">';
                print $lines[$i]->progress.' %';
                print '</td>';

                // Time spent
                print '<td align="right">';
                if ($showlineingray) print '<i>';
                else print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$lines[$i]->id.($showproject?'':'&withproject=1').'">';
                if ($lines[$i]->duration) print convertSecondToTime($lines[$i]->duration,'all');
                else print '--:--';
                if ($showlineingray) print '</i>';
                else print '</a>';
                print '</td>';

                print "</tr>\n";

                if (! $showlineingray) $inc++;

                $level++;
                if ($lines[$i]->id) projectLinesa($inc, $lines[$i]->id, $lines, $level, $var, $showproject, $taskrole, $projectsListId);
                $level--;
                $total += $lines[$i]->duration;
            }
        }
        else
        {
            //$level--;
        }
    }

    if ($total>0)
    {
        print '<tr class="liste_total"><td class="liste_total">'.$langs->trans("Total").'</td>';
        if ($showproject) print '<td></td>';
        print '<td></td>';
        print '<td></td>';
        print '<td></td>';
        print '<td></td>';
        print '<td align="right" nowrap="nowrap" class="liste_total">'.convertSecondToTime($total).'</td></tr>';
    }

    return $inc;
}


/**
 * Output a task line
 *
 * @param	string	   	&$inc			?
 * @param   string		$parent			?
 * @param   Object		$lines			?
 * @param   int			&$level			?
 * @param   string		&$projectsrole	?
 * @param   string		&$tasksrole		?
 * @param   int			$mytask			0 or 1 to enable only if task is a task i am affected to
 * @return  $inc
 */
function projectLinesb(&$inc, $parent, $lines, &$level, &$projectsrole, &$tasksrole, $mytask=0)
{
    global $user, $bc, $langs;
    global $form, $projectstatic, $taskstatic;

    $lastprojectid=0;

    $var=true;

    $numlines=count($lines);
    for ($i = 0 ; $i < $numlines ; $i++)
    {
        if ($parent == 0) $level = 0;

        if ($lines[$i]->fk_parent == $parent)
        {
            // Break on a new project
            if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid)
            {
                $var = !$var;
                $lastprojectid=$lines[$i]->fk_project;
            }

            print "<tr ".$bc[$var].">\n";

            // Project
            print "<td>";
            $projectstatic->id=$lines[$i]->fk_project;
            $projectstatic->ref=$lines[$i]->projectref;
            $projectstatic->public=$lines[$i]->public;
            $projectstatic->label=$langs->transnoentitiesnoconv("YourRole").': '.$projectsrole[$lines[$i]->fk_project];
            print $projectstatic->getNomUrl(1);
            print "</td>";

            // Ref
            print '<td>';
            $taskstatic->id=$lines[$i]->id;
            $taskstatic->ref=$lines[$i]->id;
            print $taskstatic->getNomUrl(1);
            print '</td>';

            // Label task
            print "<td>";
            for ($k = 0 ; $k < $level ; $k++)
            {
                print "&nbsp;&nbsp;&nbsp;";
            }
            print $lines[$i]->label;
            print "</td>\n";

            // Date start
            print '<td align="center">';
            print dol_print_date($lines[$i]->date_start,'day');
            print '</td>';

            // Date end
            print '<td align="center">';
            print dol_print_date($lines[$i]->date_end,'day');
            print '</td>';

            // Progress
            print '<td align="right">';
            print $lines[$i]->progress.' %';
            print '</td>';

            // Time spent
            print '<td align="right">';
            if ($lines[$i]->duration) print convertSecondToTime($lines[$i]->duration,'all');
            else print '--:--';
            print "</td>\n";

            $disabledproject=1;$disabledtask=1;
            //print "x".$lines[$i]->fk_project;
            //var_dump($lines[$i]);
            //var_dump($projectsrole[$lines[$i]->fk_project]);
            // If at least one role for project
            if ($lines[$i]->public || ! empty($projectsrole[$lines[$i]->fk_project]) || $user->rights->projet->all->creer)
            {
                $disabledproject=0;
                $disabledtask=0;
            }
            // If mytask and no role on task
            if ($mytask && empty($tasksrole[$lines[$i]->id]))
            {
                $disabledtask=1;
            }

            print '<td nowrap="nowrap">';
            $s =$form->select_date('',$lines[$i]->id,'','','',"addtime",1,0,1,$disabledtask);
            $s.='&nbsp;&nbsp;&nbsp;';
            $s.=$form->select_duration($lines[$i]->id,'',$disabledtask);
            $s.='&nbsp;<input type="submit" class="button"'.($disabledtask?' disabled="disabled"':'').' value="'.$langs->trans("Add").'">';
            print $s;
            print '</td>';
            print '<td align="right">';
            if ((! $lines[$i]->public) && $disabledproject) print $form->textwithpicto('',$langs->trans("YouAreNotContactOfProject"));
            else if ($disabledtask) print $form->textwithpicto('',$langs->trans("TaskIsNotAffectedToYou"));
            print '</td>';

            print "</tr>\n";
            $inc++;
            $level++;
            if ($lines[$i]->id) projectLinesb($inc, $lines[$i]->id, $lines, $level, $projectsrole, $tasksrole, $mytask);
            $level--;
        }
        else
        {
            //$level--;
        }
    }

    return $inc;
}


/**
 * Search in task lines with a particular parent if there is a task for a particular user (in taskrole)
 *
 * @param 	string	&$inc				Counter that count number of lines legitimate to show (for return)
 * @param 	int		$parent				Id of parent task to start
 * @param 	array	&$lines				Array of all tasks
 * @param	string	&$taskrole			Array of task filtered on a particular user
 * @return	int							1 if there is
 */
function searchTaskInChild(&$inc, $parent, &$lines, &$taskrole)
{
    //print 'Search in line with parent id = '.$parent.'<br>';
    $numlines=count($lines);
    for ($i = 0 ; $i < $numlines ; $i++)
    {
        // Process line $lines[$i]
        if ($lines[$i]->fk_parent == $parent && $lines[$i]->id != $lines[$i]->fk_parent)
        {
            // If task is legitimate to show, no more need to search deeper
            if (isset($taskrole[$lines[$i]->id]))
            {
                //print 'Found a legitimate task id='.$lines[$i]->id.'<br>';
                $inc++;
                return $inc;
            }

            searchTaskInChild($inc, $lines[$i]->id, $lines, $taskrole);
            //print 'Found inc='.$inc.'<br>';

            if ($inc > 0) return $inc;
        }
    }

    return $inc;
}


/**
 * Clean task not linked to a parent
 *
 * @param	DoliDB	$db     Database handler
 * @return	int				Nb of records deleted
 */
function clean_orphelins($db)
{
    $nb=0;

    // There is orphelins. We clean that
    $listofid=array();

    // Get list of id in array listofid
    $sql='SELECT rowid FROM '.MAIN_DB_PREFIX.'projet_task';
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        while ($i < $num && $i < 100)
        {
            $obj = $db->fetch_object($resql);
            $listofid[]=$obj->rowid;
            $i++;
        }
    }
    else
    {
        dol_print_error($db);
    }

    if (count($listofid))
    {
        // Removed orphelins records
        print 'Some orphelins were found and restored to be parents so records are visible again: ';
        print join(',',$listofid);

        $sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
        $sql.= " SET fk_task_parent = 0";
        $sql.= " WHERE fk_task_parent NOT IN (".join(',',$listofid).")";

        $resql = $db->query($sql);
        if ($resql)
        {
            $nb=$db->affected_rows($sql);

            return $nb;
        }
        else
        {
            return -1;
        }
    }
}


/**
 * Return HTML table with list of projects and number of opened tasks
 *
 * @param	DoliDB	$db					Database handler
 * @param   int		$socid				Id thirdparty
 * @param   int		$projectsListId     Id of project i have permission on
 * @param   int		$mytasks            Limited to task i am contact to
 * @return	void
 */
function print_projecttasks_array($db, $socid, $projectsListId, $mytasks=0)
{
    global $langs,$conf,$user,$bc;

    require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");

    $projectstatic=new Project($db);

    $sortfield='';
    $sortorder='';

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Project"),"index.php","","","","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("NbOpenTasks"),"","","","",'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Status"),"","","","",'align="right"',$sortfield,$sortorder);
    print "</tr>\n";

    $sql = "SELECT p.rowid as projectid, p.ref, p.title, p.fk_user_creat, p.public, p.fk_statut, COUNT(t.rowid) as nb";
    $sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
    if ($mytasks)
    {
        $sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
        $sql.= ", ".MAIN_DB_PREFIX."element_contact as ec";
        $sql.= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
    }
    else
    {
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t ON p.rowid = t.fk_projet";
    }
    $sql.= " WHERE p.entity = ".$conf->entity;
    $sql.= " AND p.rowid IN (".$projectsListId.")";
    if ($socid) $sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
    if ($mytasks)
    {
        $sql.= " AND p.rowid = t.fk_projet";
        $sql.= " AND ec.element_id = t.rowid";
        $sql.= " AND ctc.rowid = ec.fk_c_type_contact";
        $sql.= " AND ctc.element = 'project_task'";
        $sql.= " AND ec.fk_socpeople = ".$user->id;
    }
    $sql.= " GROUP BY p.rowid, p.ref, p.title, p.fk_user_creat, p.public, p.fk_statut";
    $sql.= " ORDER BY p.title, p.ref";

    $var=true;
    $resql = $db->query($sql);
    if ( $resql )
    {
        $num = $db->num_rows($resql);
        $i = 0;

        while ($i < $num)
        {
            $objp = $db->fetch_object($resql);

            $projectstatic->id = $objp->projectid;
            $projectstatic->user_author_id = $objp->fk_user_creat;
            $projectstatic->public = $objp->public;

            // Check is user has read permission on project
            $userAccess = $projectstatic->restrictedProjectArea($user);
            if ($userAccess >= 0)
            {
                $var=!$var;
                print "<tr ".$bc[$var].">";
                print '<td nowrap="nowrap">';
                $projectstatic->ref=$objp->ref;
                print $projectstatic->getNomUrl(1);
                print ' - '.$objp->title.'</td>';
                print '<td align="right">'.$objp->nb.'</td>';
                $projectstatic->statut = $objp->fk_statut;
                print '<td align="right">'.$projectstatic->getLibStatut(3).'</td>';
                print "</tr>\n";
            }

            $i++;
        }

        $db->free($resql);
    }
    else
    {
        dol_print_error($db);
    }
    print "</table>";
}

?>