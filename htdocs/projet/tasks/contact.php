<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *	\file       htdocs/projet/tasks/contact.php
 *	\ingroup    project
 *	\brief      Actors of a task
 */

require ("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Load translation files required by the page
$langs->loadLangs(array('projects', 'companies'));

$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$withproject=GETPOST('withproject','int');
$project_ref=GETPOST('project_ref','alpha');

// Security check
$socid=0;
//if ($user->societe_id > 0) $socid = $user->societe_id;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
//$result = restrictedArea($user, 'projet', $id, 'projet_task');
if (! $user->rights->projet->lire) accessforbidden();

$object = new Task($db);
$projectstatic = new Project($db);


/*
 * Actions
 */

// Add new contact
if ($action == 'addcontact' && $user->rights->projet->creer)
{
	$result = $object->fetch($id, $ref);

    if ($result > 0 && $id > 0)
    {
    	$idfortaskuser=(GETPOST("contactid")!=0)?GETPOST("contactid"):GETPOST("userid");	// GETPOST('contactid') may val -1 to mean empty or -2 to means "everybody"
    	if ($idfortaskuser == -2)
    	{
    		$result=$projectstatic->fetch($object->fk_project);
    		if ($result <= 0)
    		{
    			dol_print_error($db,$projectstatic->error,$projectstatic->errors);
    		}
    		else
    		{
    			$contactsofproject=$projectstatic->getListContactId('internal');
    			foreach($contactsofproject as $key => $val)
    			{
    				$result = $object->add_contact($val, GETPOST("type"), GETPOST("source"));
    			}
    		}
    	}
    	else
    	{
  			$result = $object->add_contact($idfortaskuser, GETPOST("type"), GETPOST("source"));
    	}
    }

	if ($result >= 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id.($withproject?'&withproject=1':''));
		exit;
	}
	else
	{
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

// bascule du statut d'un contact
if ($action == 'swapstatut' && $user->rights->projet->creer)
{
	if ($object->fetch($id, $ref))
	{
	    $result=$object->swapContactStatus(GETPOST('ligne'));
	}
	else
	{
		dol_print_error($db);
	}
}

// Efface un contact
if ($action == 'deleteline' && $user->rights->projet->creer)
{
	$object->fetch($id, $ref);
	$result = $object->delete_contact($_GET["lineid"]);

	if ($result >= 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id.($withproject?'&withproject=1':''));
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

// Retreive First Task ID of Project if withprojet is on to allow project prev next to work
if (! empty($project_ref) && ! empty($withproject))
{
	if ($projectstatic->fetch(0,$project_ref) > 0)
	{
		$tasksarray=$object->getTasksArray(0, 0, $projectstatic->id, $socid, 0);
		if (count($tasksarray) > 0)
		{
			$id=$tasksarray[0]->id;
		}
		else
		{
			header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.($withproject?'&withproject=1':'').(empty($mode)?'':'&mode='.$mode));
			exit;
		}
	}
}

/*
 * View
 */

llxHeader('', $langs->trans("Task"));

$form = new Form($db);
$formcompany   = new FormCompany($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0 || ! empty($ref))
{
	if ($object->fetch($id, $ref) > 0)
	{
		if(! empty($conf->global->PROJECT_ALLOW_COMMENT_ON_TASK) && method_exists($object, 'fetchComments') && empty($object->comments)) $object->fetchComments();
	    $id = $object->id;     // So when doing a search from ref, id is also set correctly.

		$result=$projectstatic->fetch($object->fk_project);
		if(! empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($projectstatic, 'fetchComments') && empty($projectstatic->comments)) $projectstatic->fetchComments();
		if (! empty($projectstatic->socid)) $projectstatic->fetch_thirdparty();

		$object->project = clone $projectstatic;

		$userWrite  = $projectstatic->restrictedProjectArea($user,'write');

		if ($withproject)
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
                $objectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,0);
                $projectstatic->next_prev_filter=" rowid in (".(count($objectsListId)?join(',',array_keys($objectsListId)):'0').")";
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
            $start = dol_print_date($projectstatic->date_start,'day');
            print ($start?$start:'?');
            $end = dol_print_date($projectstatic->date_end,'day');
            print ' - ';
            print ($end?$end:'?');
            if ($projectstatic->hasDelay()) print img_warning("Late");
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


		// To verify role of users
		//$userAccess = $projectstatic->restrictedProjectArea($user); // We allow task affected to user even if a not allowed project
		//$arrayofuseridoftask=$object->getListContactId('internal');

		$head = task_prepare_head($object);
		dol_fiche_head($head, 'task_contact', $langs->trans("Task"), -1, 'projecttask', 0, '', 'reposition');


		$param=(GETPOST('withproject')?'&withproject=1':'');
		$linkback=GETPOST('withproject')?'<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("BackToList").'</a>':'';

		if (! GETPOST('withproject') || empty($projectstatic->id))
		{
		    $projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1);
		    $object->next_prev_filter=" fk_projet in (".$projectsListId.")";
		}
		else $object->next_prev_filter=" fk_projet = ".$projectstatic->id;

		$morehtmlref='';

		// Project
		if (empty($withproject))
		{
		    $result=$projectstatic->fetch($object->fk_project);
		    $morehtmlref.='<div class="refidno">';
		    $morehtmlref.=$langs->trans("Project").': ';
		    $morehtmlref.=$projectstatic->getNomUrl(1);
		    $morehtmlref.='<br>';

		    // Third party
		    $morehtmlref.=$langs->trans("ThirdParty").': ';
		    if($projectstatic->socid>0) {
		        $projectstatic->fetch_thirdparty();
		        $morehtmlref.=$projectstatic->thirdparty->getNomUrl(1);
		    }

		    $morehtmlref.='</div>';
		}

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $param, 0, '', '', 1);

		dol_fiche_end();

		/*
		 * Lignes de contacts
		 */
/*
		// Contacts lines (modules that overwrite templates must declare this into descriptor)
		$dirtpls=array_merge($conf->modules_parts['tpl'],array('/core/tpl'));
		foreach($dirtpls as $reldir)
		{
		    $res=@include dol_buildpath($reldir.'/contacts.tpl.php');
		    if ($res) break;
		}
*/

		/*
		 * Add a new contact line
		 * Non affiche en mode modification de ligne
		 */
		print '<table class="noborder" width="100%">';

		if ($action != 'editline' && $user->rights->projet->creer)
		{
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Source").'</td>';
			print '<td>'.$langs->trans("ThirdParty").'</td>';
			print '<td>'.$langs->trans("TaskContact").'</td>';
			print '<td>'.$langs->trans("ContactType").'</td>';
			print '<td colspan="3">&nbsp;</td>';
			print "</tr>\n";

			$var = false;

			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="addcontact">';
			print '<input type="hidden" name="source" value="internal">';
			print '<input type="hidden" name="id" value="'.$id.'">';
			if ($withproject) print '<input type="hidden" name="withproject" value="'.$withproject.'">';

			// Ligne ajout pour contact interne
			print '<tr class="oddeven">';

			print '<td class="nowrap">';
			print img_object('','user').' '.$langs->trans("Users");
			print '</td>';

			print '<td colspan="1">';
			print $conf->global->MAIN_INFO_SOCIETE_NOM;
			print '</td>';

			print '<td colspan="1">';
			// On recupere les id des users deja selectionnes
			if ($object->project->public) $contactsofproject='';	// Everybody
			else $contactsofproject=$projectstatic->getListContactId('internal');
			print $form->select_dolusers((GETPOST('contactid')?GETPOST('contactid'):$user->id), 'contactid', 0, '', 0, '', $contactsofproject, 0, 0, 0, '', 1, $langs->trans("ResourceNotAssignedToProject"));
			print '</td>';
			print '<td>';
			$formcompany->selectTypeContact($object, '', 'type','internal','rowid');
			print '</td>';
			print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
			print '</tr>';

			print '</form>';

			// Line to add an external contact. Only if project linked to a third party.
			if ($projectstatic->socid)
			{
				print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="addcontact">';
				print '<input type="hidden" name="source" value="external">';
				print '<input type="hidden" name="id" value="'.$object->id.'">';
				if ($withproject) print '<input type="hidden" name="withproject" value="'.$withproject.'">';


				print '<tr class="oddeven">';

				print '<td class="nowrap">';
				print img_object('','contact').' '.$langs->trans("ThirdPartyContacts");
				print '</td>';

				print '<td colspan="1">';
				$thirdpartyofproject=$projectstatic->getListContactId('thirdparty');
				$selectedCompany = isset($_GET["newcompany"])?$_GET["newcompany"]:$projectstatic->socid;
				$selectedCompany = $formcompany->selectCompaniesForNewContact($object, 'id', $selectedCompany, 'newcompany', $thirdpartyofproject, 0, '&withproject='.$withproject);
				print '</td>';

				print '<td colspan="1">';
				$contactofproject=$projectstatic->getListContactId('external');
				$nbofcontacts=$form->select_contacts($selectedCompany,'','contactid',0,'',$contactofproject);
				print '</td>';
				print '<td>';
				$formcompany->selectTypeContact($object, '', 'type','external','rowid');
				print '</td>';
				print '<td align="right" colspan="3" ><input type="submit" class="button" id="add-customer-contact" value="'.$langs->trans("Add").'"';
				if (! $nbofcontacts) print ' disabled';
				print '></td>';
				print '</tr>';

				print "</form>";
			}
		}

		// Liste des contacts lies
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Source").'</td>';
		print '<td>'.$langs->trans("ThirdParty").'</td>';
		print '<td>'.$langs->trans("TaskContact").'</td>';
		print '<td>'.$langs->trans("ContactType").'</td>';
		print '<td align="center">'.$langs->trans("Status").'</td>';
		print '<td colspan="2">&nbsp;</td>';
		print "</tr>\n";

		$companystatic = new Societe($db);
		$var = true;

		foreach(array('internal','external') as $source)
		{
			$tab = $object->liste_contact(-1,$source);
			$num=count($tab);

			$i = 0;
			while ($i < $num)
			{
				$var = !$var;

				print '<tr '.$bc[$var].' valign="top">';

				// Source
				print '<td align="left">';
				if ($tab[$i]['source']=='internal') print $langs->trans("User");
				if ($tab[$i]['source']=='external') print $langs->trans("ThirdPartyContact");
				print '</td>';

				// Societe
				print '<td align="left">';
				if ($tab[$i]['socid'] > 0)
				{
					$companystatic->fetch($tab[$i]['socid']);
					print $companystatic->getNomUrl(1);
				}
				if ($tab[$i]['socid'] < 0)
				{
					print $conf->global->MAIN_INFO_SOCIETE_NOM;
				}
				if (! $tab[$i]['socid'])
				{
					print '&nbsp;';
				}
				print '</td>';

				// Contact
				print '<td>';
                if ($tab[$i]['source']=='internal')
                {
                    $userstatic->id=$tab[$i]['id'];
                    $userstatic->lastname=$tab[$i]['lastname'];
                    $userstatic->firstname=$tab[$i]['firstname'];
    				$userstatic->photo=$tab[$i]['photo'];
    				$userstatic->login=$tab[$i]['login'];
                    print $userstatic->getNomUrl(-1);
                }
                if ($tab[$i]['source']=='external')
                {
                    $contactstatic->id=$tab[$i]['id'];
                    $contactstatic->lastname=$tab[$i]['lastname'];
                    $contactstatic->firstname=$tab[$i]['firstname'];
                    print $contactstatic->getNomUrl(1);
                }
				print '</td>';

				// Type de contact
				print '<td>'.$tab[$i]['libelle'].'</td>';

				// Statut
				print '<td align="center">';
				// Activation desativation du contact
				if ($object->statut >= 0) print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=swapstatut&ligne='.$tab[$i]['rowid'].($withproject?'&withproject=1':'').'">';
				print $contactstatic->LibStatut($tab[$i]['status'],3);
				if ($object->statut >= 0) print '</a>';
				print '</td>';

				// Icon update et delete
				print '<td align="center" class="nowrap">';
				if ($user->rights->projet->creer)
				{
					print '&nbsp;';
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=deleteline&lineid='.$tab[$i]['rowid'].($withproject?'&withproject=1':'').'">';
					print img_delete();
					print '</a>';
				}
				print '</td>';

				print "</tr>\n";

				$i ++;
			}
		}
		print "</table>";

	}
	else
	{
		print "ErrorRecordNotFound";
	}
}

if (is_object($hookmanager))
{
	$hookmanager->initHooks(array('contacttpl'));
	$parameters=array();
	$reshook=$hookmanager->executeHooks('formContactTpl',$parameters,$object,$action);
}


llxFooter();

$db->close();
