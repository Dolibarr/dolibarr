<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
 *	\file       htdocs/projet/fiche.php
 *	\ingroup    projet
 *	\brief      Project card
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/project/modules_project.php");
require_once(DOL_DOCUMENT_ROOT."/projet/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

$langs->load("projects");
$langs->load('companies');

$projectid = (isset($_GET["id"])?$_GET["id"]:(isset($_POST["id"])?$_POST["id"]:''));
$projectref = (isset($_GET["ref"])?$_GET["ref"]:'');

// If socid provided by ajax company selector
if (! empty($_REQUEST['socid_id']))
{
	$_GET['socid'] = $_GET['socid_id'];
	$_POST['socid'] = $_POST['socid_id'];
	$_REQUEST['socid'] = $_REQUEST['socid_id'];
}

if ($projectid == '' && $projectref == '' && ($_GET['action'] != "create" && $_POST['action'] != "add" && $_POST["action"] != "update" && !$_POST["cancel"])) accessforbidden();

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $projectid);


/*
 * Actions
 */

if ($_POST["action"] == 'add' && $user->rights->projet->creer)
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
		$project = new Project($db);

		$project->ref             = $_POST["ref"];
		$project->title           = $_POST["title"];
		$project->socid           = $_POST["socid"];
		$project->description     = $_POST["description"];
		$project->public          = $_POST["public"];
		$project->datec=dol_now('tzserver');
		$project->dateo=dol_mktime(12,0,0,$_POST['projectmonth'],$_POST['projectday'],$_POST['projectyear']);
		$project->datee=dol_mktime(12,0,0,$_POST['projectendmonth'],$_POST['projectendday'],$_POST['projectendyear']);

		$result = $project->create($user);
		if ($result > 0)
		{
			// Add myself as project leader
			$result = $project->add_contact($user->id, 'PROJECTLEADER', 'internal');

			Header("Location:fiche.php?id=".$project->id);
			exit;
		}
		else
		{
			$langs->load("errors");
			$mesg='<div class="error">'.$langs->trans($project->error).'</div>';
			$_GET["action"] = 'create';
		}
	}
	else
	{
		$_GET["action"] = 'create';
	}
}

if ($_POST["action"] == 'update' && ! $_POST["cancel"] && $user->rights->projet->creer)
{
	$error=0;

	if (empty($_POST["ref"]))
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
		$project = new Project($db);
		$project->fetch($_POST["id"]);

		$project->ref          = $_POST["ref"];
		$project->title        = $_POST["title"];
		$project->socid        = $_POST["socid"];
		$project->description  = $_POST["description"];
		$project->public       = $_POST["public"];
		$project->date_start   = empty($_POST["project"])?'':dol_mktime(12,0,0,$_POST['projectmonth'],$_POST['projectday'],$_POST['projectyear']);
		$project->date_end     = empty($_POST["projectend"])?'':dol_mktime(12,0,0,$_POST['projectendmonth'],$_POST['projectendday'],$_POST['projectendyear']);

		$result=$project->update($user);

		$_GET["id"]=$project->id;  // On retourne sur la fiche projet
	}
	else
	{
		$_GET["id"]=$_POST["id"];
		$_GET['action']='edit';
	}
}

// Build doc
if ($_REQUEST['action'] == 'builddoc' && $user->rights->projet->creer)
{
	$project = new Project($db);
	$project->fetch($_GET['id']);
	if ($_REQUEST['model'])
	{
		$project->setDocModel($user, $_REQUEST['model']);
	}

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	$result=project_pdf_create($db, $project->id, $project->modelpdf, $outputlangs);
	if ($result <= 0)
	{
		dol_print_error($db,$result);
		exit;
	}
	else
	{
		Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$project->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
		exit;
	}
}

if ($_REQUEST['action'] == 'confirm_validate' && $_REQUEST['confirm'] == 'yes')
{
	$project = new Project($db);
	$project->fetch($_GET["id"]);

	$result = $project->setValid($user, $conf->projet->outputdir);
	if ($result <= 0)
	{
		$mesg='<div class="error">'.$project->error.'</div>';
	}
}

if ($_REQUEST['action'] == 'confirm_close' && $_REQUEST['confirm'] == 'yes')
{
	$project = new Project($db);
	$project->fetch($_GET["id"]);
	$result = $project->setClose($user, $conf->projet->outputdir);
	if ($result <= 0)
	{
		$mesg='<div class="error">'.$project->error.'</div>';
	}
}

if ($_REQUEST['action'] == 'confirm_reopen' && $_REQUEST['confirm'] == 'yes')
{
	$project = new Project($db);
	$project->fetch($_GET["id"]);
	$result = $project->setValid($user, $conf->projet->outputdir);
	if ($result <= 0)
	{
		$mesg='<div class="error">'.$project->error.'</div>';
	}
}

if ($_REQUEST["action"] == 'confirm_delete' && $_REQUEST["confirm"] == "yes" && $user->rights->projet->supprimer)
{
	$project = new Project($db);
	$project->id = $_GET["id"];
	$result=$project->delete($user);
	if ($result >= 0)
	{
		Header("Location: index.php");
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("CantRemoveProject").'</div>';
	}
}


/*
 *	View
 */

$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("",$langs->trans("Projects"),$help_url);

$html = new Form($db);
$formfile = new FormFile($db);

$userstatic=new User($db);

if ($_GET["action"] == 'create' && $user->rights->projet->creer)
{
	/*
	 * Create
	 */
	print_fiche_titre($langs->trans("NewProject"));

	if ($mesg) print $mesg.'<br>';

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="border" width="100%">';
	print '<input type="hidden" name="action" value="add">';

	$project = new Project($db);

	$defaultref='';
	$obj = empty($conf->global->PROJECT_ADDON)?'mod_project_simple':$conf->global->PROJECT_ADDON;
	if (! empty($conf->global->PROJECT_ADDON) && is_readable(DOL_DOCUMENT_ROOT ."/includes/modules/project/".$conf->global->PROJECT_ADDON.".php"))
	{
		require_once(DOL_DOCUMENT_ROOT ."/includes/modules/project/".$conf->global->PROJECT_ADDON.".php");
		$modProject = new $obj;
		$defaultref = $modProject->getNextValue($soc,$project);
	}

	if (empty($defaultref)) $defaultref='';

	// Ref
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Ref").'</span></td><td><input size="12" type="text" name="ref" value="'.($_POST["ref"]?$_POST["ref"]:$defaultref).'"></td></tr>';

	// Label
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Label").'</span></td><td><input size="30" type="text" name="title" value="'.$_POST["title"].'"></td></tr>';

	// Customer
	print '<tr><td>'.$langs->trans("Company").'</td><td>';
	print $html->select_societes($_REQUEST["socid"],'socid','',1,1);
	print '</td></tr>';

	// Public
	print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
	$array=array(0 => $langs->trans("PrivateProject"),1 => $langs->trans("SharedProject"));
	$html->select_array('public',$array,$project->public);
	print '</td></tr>';

	// Date start
	print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
	print $html->select_date('','project');
	print '</td></tr>';

	// Date end
	print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
	print $html->select_date(-1,'projectend');
	print '</td></tr>';

	// Description
	print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
	print '<td>';
	print '<textarea name="description" wrap="soft" cols="80" rows="'.ROWS_3.'">'.$_POST["description"].'</textarea>';
	print '</td></tr>';

	print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Create").'"></td></tr>';
	print '</table>';
	print '</form>';

}
else
{
	/*
	 * Show or edit
	 */

	if ($mesg) print $mesg;

	$project = new Project($db);
	$project->fetch($projectid,$projectref);

	if ($project->societe->id > 0)  $result=$project->societe->fetch($project->societe->id);

	// To verify role of users
	$userAccess = $project->restrictedProjectArea($user);

	$head=project_prepare_head($project);
	dol_fiche_head($head, 'project', $langs->trans("Project"),0,'project');

	// Confirmation validation
	if ($_GET['action'] == 'validate')
	{
		$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$project->id, $langs->trans('ValidateProject'), $langs->trans('ConfirmValidateProject'), 'confirm_validate','',0,1);
		if ($ret == 'html') print '<br>';
	}
	// Confirmation close
	if ($_GET["action"] == 'close')
	{
		$ret=$html->form_confirm($_SERVER["PHP_SELF"]."?id=".$project->id,$langs->trans("CloseAProject"),$langs->trans("ConfirmCloseAProject"),"confirm_close",'','',1);
		if ($ret == 'html') print '<br>';
	}
	// Confirmation reopen
	if ($_GET["action"] == 'reopen')
	{
		$ret=$html->form_confirm($_SERVER["PHP_SELF"]."?id=".$project->id,$langs->trans("ReOpenAProject"),$langs->trans("ConfirmReOpenAProject"),"confirm_reopen",'','',1);
		if ($ret == 'html') print '<br>';
	}
	// Confirmation delete
	if ($_GET["action"] == 'delete')
	{
		$ret=$html->form_confirm($_SERVER["PHP_SELF"]."?id=".$project->id,$langs->trans("DeleteAProject"),$langs->trans("ConfirmDeleteAProject"),"confirm_delete",'','',1);
		if ($ret == 'html') print '<br>';
	}


	if ($_GET["action"] == 'edit' && $userAccess)
	{
		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.$project->id.'">';

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="30%">'.$langs->trans("Ref").'</td>';
		print '<td><input size="12" name="ref" value="'.$project->ref.'"></td></tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td>';
		print '<td><input size="30" name="title" value="'.$project->title.'"></td></tr>';

		// Customer
		print '<tr><td>'.$langs->trans("Company").'</td><td>';
		print $html->select_societes($project->societe->id,'socid','',1,1);
		print '</td></tr>';

		// Visibility
		print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
		$array=array(0 => $langs->trans("PrivateProject"),1 => $langs->trans("SharedProject"));
		$html->select_array('public',$array,$project->public);
		print '</td></tr>';

		// Statut
		print '<tr><td>'.$langs->trans("Status").'</td><td>'.$project->getLibStatut(4).'</td></tr>';

		// Date start
		print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
		print $html->select_date($project->date_start,'project');
		print '</td></tr>';

		// Date end
		print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
		print $html->select_date($project->date_end?$project->date_end:-1,'projectend');
		print '</td></tr>';

		// Description
		print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
		print '<td>';
		print '<textarea name="description" wrap="soft" cols="80" rows="'.ROWS_3.'">'.$project->description.'</textarea>';
		print '</td></tr>';

		print '<tr><td align="center" colspan="2">';
		print '<input name="update" class="button" type="submit" value="'.$langs->trans("Modify").'"> &nbsp; ';
		print '<input type="submit" class="button" name="cancel" Value="'.$langs->trans("Cancel").'"></td></tr>';
		print '</table>';
		print '</form>';
	}
	else
	{

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
		print $html->showrefnav($project,'ref','',1,'ref','ref');
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

	print '</div>';

	/*
	 * Boutons actions
	 */
	print '<div class="tabsAction">';

	if ($_GET["action"] != "edit" )
	{
		// Validate
		if ($project->statut == 0 && $user->rights->projet->creer)
		{
			if ($userAccess)
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
			if ($userAccess)
			{
				print '<a class="butAction" href="fiche.php?id='.$project->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
			}
			else
			{
				print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('Modify').'</a>';
			}
		}

		// Close
		if ($project->statut != 2 && $user->rights->projet->creer)
		{
			if ($userAccess)
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
			if ($userAccess)
			{
				print '<a class="butAction" href="fiche.php?id='.$project->id.'&amp;action=reopen">'.$langs->trans("ReOpen").'</a>';
			}
			else
			{
				print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('ReOpen').'</a>';
			}
		}

		// Delete
		if ($user->rights->projet->supprimer)
		{
			if ($userAccess)
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

	if ($_GET['action'] != 'presend')
	{
		print '<table width="100%"><tr><td width="50%" valign="top">';
		print '<a name="builddoc"></a>'; // ancre


		/*
		 * Documents generes
		 */
		$filename=dol_sanitizeFileName($project->ref);
		$filedir=$conf->projet->dir_output . "/" . dol_sanitizeFileName($project->ref);
		$urlsource=$_SERVER["PHP_SELF"]."?id=".$project->id;
		$genallowed=($user->rights->projet->creer && $userAccess);
		$delallowed=($user->rights->projet->supprimer && $userAccess);

		$var=true;

		$somethingshown=$formfile->show_documents('project',$filename,$filedir,$urlsource,$genallowed,$delallowed,$project->modelpdf);

		print '</td><td valign="top" width="50%">';

		// List of actions on element
		include_once(DOL_DOCUMENT_ROOT.'/html.formactions.class.php');
		$formactions=new FormActions($db);
		$somethingshown=$formactions->showactions($project,'project',$socid);

		print '</td></tr></table>';
	}

}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
