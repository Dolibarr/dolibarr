<?php
/* Copyright (C) 2010 Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2012 Laurent Destailleur <eldy@users.sourceforge.net>
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
 *       \file       htdocs/projet/contact.php
 *       \ingroup    project
 *       \brief      Onglet de gestion des contacts du projet
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$langs->load("projects");
$langs->load("companies");

$id     = GETPOST('id','int');
$ref    = GETPOST('ref','alpha');
$lineid = GETPOST('lineid','int');
$socid  = GETPOST('socid','int');
$action = GETPOST('action','alpha');

$mine   = GETPOST('mode')=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$object = new Project($db);
if ($ref)
{
    $object->fetch(0,$ref);
    $id=$object->id;
}

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $id);


/*
 * Actions
 */

// Add new contact
if ($action == 'addcontact' && $user->rights->projet->creer)
{
	$result = 0;
	$result = $object->fetch($id);

    if ($result > 0 && $id > 0)
    {
  		$contactid = (GETPOST('userid') ? GETPOST('userid','int') : GETPOST('contactid','int'));
  		$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
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
if ($action == 'swapstatut' && $user->rights->projet->creer)
{
	if ($object->fetch($id))
	{
	    $result=$object->swapContactStatus(GETPOST('ligne'));
	}
	else
	{
		dol_print_error($db);
	}
}

// Efface un contact
if (($action == 'deleteline' || $action == 'deletecontact') && $user->rights->projet->creer)
{
	$object->fetch($id);
	$result = $object->delete_contact($_GET["lineid"]);

	if ($result >= 0)
	{
		header("Location: contact.php?id=".$object->id);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}


/*
 * View
 */

$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader('', $langs->trans("Project"), $help_url);

$form = new Form($db);
$formcompany= new FormCompany($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
dol_htmloutput_mesg($mesg);

if ($id > 0 || ! empty($ref))
{
	if ( $object->fetch($id,$ref) > 0)
	{
		if ($object->societe->id > 0)  $result=$object->societe->fetch($object->societe->id);

		// To verify role of users
		//$userAccess = $object->restrictedProjectArea($user,'read');
		$userWrite  = $object->restrictedProjectArea($user,'write');
		//$userDelete = $object->restrictedProjectArea($user,'delete');
		//print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;

		$head = project_prepare_head($object);
		dol_fiche_head($head, 'contact', $langs->trans("Project"), 0, ($object->public?'projectpub':'project'));


		/*
		 *   Projet synthese pour rappel
		 */
		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/projet/liste.php">'.$langs->trans("BackToList").'</a>';

		// Ref
		print '<tr><td width="30%">'.$langs->trans('Ref').'</td><td colspan="3">';
		// Define a complementary filter for search of next/prev ref.
        if (! $user->rights->projet->all->lire)
        {
            $objectsListId = $object->getProjectsAuthorizedForUser($user,$mine,0);
            $object->next_prev_filter=" rowid in (".(count($objectsListId)?join(',',array_keys($objectsListId)):'0').")";
        }
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '');
		print '</td></tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->title.'</td></tr>';

		// Customer
		print "<tr><td>".$langs->trans("Company")."</td>";
		print '<td colspan="3">';
		if ($object->societe->id > 0) print $object->societe->getNomUrl(1);
		else print '&nbsp;';
		print '</td></tr>';

		// Visibility
		print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
		if ($object->public) print $langs->trans('SharedProject');
		else print $langs->trans('PrivateProject');
		print '</td></tr>';

		// Statut
		print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

		print "</table>";

		print '</div>';

		// Contacts lines (modules that overwrite templates must declare this into descriptor)
		$dirtpls=array_merge($conf->modules_parts['tpl'],array('/core/tpl'));
		foreach($dirtpls as $reldir)
		{
			$res=@include dol_buildpath($reldir.'/contacts.tpl.php');
			if ($res) break;
		}		
	}
	else
	{
		print "ErrorRecordNotFound";
	}
}

llxFooter();

$db->close();
?>