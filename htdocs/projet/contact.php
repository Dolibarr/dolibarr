<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 *       \file       htdocs/projet/contact.php
 *       \ingroup    project
 *       \brief      Onglet de gestion des contacts du projet
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');

$langs->load("projects");
$langs->load("companies");

$projectid = isset($_GET["id"])?$_GET["id"]:'';

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

// Security check
$socid=0;
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $projectid);


/*
 * Actions
 */

// Add new contact
if ($_POST["action"] == 'addcontact' && $user->rights->projet->creer)
{

	$result = 0;
	$project = new Project($db);
	$result = $project->fetch($projectid);

    if ($result > 0 && $projectid > 0)
    {
  		$result = $project->add_contact($_POST["contactid"], $_POST["type"], $_POST["source"]);
    }

	if ($result >= 0)
	{
		Header("Location: contact.php?id=".$project->id);
		exit;
	}
	else
	{
		if ($project->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			$langs->load("errors");
			$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
		}
		else
		{
			$mesg = '<div class="error">'.$project->error.'</div>';
		}
	}
}

// bascule du statut d'un contact
if ($_GET["action"] == 'swapstatut' && $user->rights->projet->creer)
{
	$project = new Project($db);
	if ($project->fetch($projectid))
	{
	    $result=$project->swapContactStatus(GETPOST('ligne'));
	}
	else
	{
		dol_print_error($db);
	}
}

// Efface un contact
if ($_GET["action"] == 'deleteline' && $user->rights->projet->creer)
{
	$project = new Project($db);
	$project->fetch($projectid);
	$result = $project->delete_contact($_GET["lineid"]);

	if ($result >= 0)
	{
		Header("Location: contact.php?id=".$project->id);
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

$html = new Form($db);
$formcompany= new FormCompany($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
dol_htmloutput_mesg($mesg);

$id = $_GET['id'];
$ref= $_GET['ref'];
if ($id > 0 || ! empty($ref))
{
	$project = new Project($db);

	if ( $project->fetch($id,$ref) > 0)
	{
		if ($project->societe->id > 0)  $result=$project->societe->fetch($project->societe->id);

		// To verify role of users
		$userAccess = $project->restrictedProjectArea($user);

		$head = project_prepare_head($project);
		dol_fiche_head($head, 'contact', $langs->trans("Project"), 0, ($project->public?'projectpub':'project'));


		/*
		 *   Projet synthese pour rappel
		 */
		print '<table class="border" width="100%">';

		//$linkback="<a href=\"".$_SERVER["PHP_SELF"]."?page=$page&socid=$socid&viewstatut=$viewstatut&sortfield=$sortfield&$sortorder\">".$langs->trans("BackToList")."</a>";

		// Ref
		print '<tr><td width="30%">'.$langs->trans('Ref').'</td><td colspan="3">';
		// Define a complementary filter for search of next/prev ref.
		$projectsListId = $project->getProjectsAuthorizedForUser($user,$mine,1);
		$project->next_prev_filter=" rowid in (".$projectsListId.")";
		print $html->showrefnav($project,'ref',$linkback,1,'ref','ref','');
		print '</td></tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$project->title.'</td></tr>';

		// Customer
		print "<tr><td>".$langs->trans("Company")."</td>";
		print '<td colspan="3">';
		if ($project->societe->id > 0) print $project->societe->getNomUrl(1);
		else print '&nbsp;';
		print '</td></tr>';

		// Visibility
		print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
		if ($project->public) print $langs->trans('SharedProject');
		else print $langs->trans('PrivateProject');
		print '</td></tr>';

		// Statut
		print '<tr><td>'.$langs->trans("Status").'</td><td>'.$project->getLibStatut(4).'</td></tr>';

		print "</table>";

		print '</div>';

		/*
		 * Lignes de contacts
		 */
		print '<br><table class="noborder" width="100%">';

		/*
		 * Ajouter une ligne de contact
		 * Non affiche en mode modification de ligne
		 */
		if ($_GET["action"] != 'editline' && $user->rights->projet->creer && $userAccess)
		{
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Source").'</td>';
			print '<td>'.$langs->trans("Company").'</td>';
			print '<td>'.$langs->trans("Contacts").'</td>';
			print '<td>'.$langs->trans("ContactType").'</td>';
			print '<td colspan="3">&nbsp;</td>';
			print "</tr>\n";

			$var = false;

			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="addcontact">';
			print '<input type="hidden" name="source" value="internal">';
			print '<input type="hidden" name="id" value="'.$id.'">';

			// Ligne ajout pour contact interne
			print "<tr $bc[$var]>";

			print '<td nowrap="nowrap">';
			print img_object('','user').' '.$langs->trans("Users");
			print '</td>';

			print '<td colspan="1">';
			print $conf->global->MAIN_INFO_SOCIETE_NOM;
			print '</td>';

			print '<td colspan="1">';
			// On recupere les id des users deja selectionnes
			$html->select_users($user->id,'contactid',0);
			print '</td>';
			print '<td>';
			$formcompany->selectTypeContact($project, '', 'type','internal','rowid');
			print '</td>';
			print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
			print '</tr>';

			print '</form>';

			// Line to add external contact. Only if project is linked to a third party.
			if ($project->societe->id)
			{
				print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="POST">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="addcontact">';
				print '<input type="hidden" name="source" value="external">';
				print '<input type="hidden" name="id" value="'.$id.'">';

				$var=!$var;
				print "<tr $bc[$var]>";

				print '<td nowrap="nowrap">';
				print img_object('','contact').' '.$langs->trans("ThirdPartyContacts");
				print '</td>';

				print '<td colspan="1">';
				$selectedCompany = isset($_GET["newcompany"])?$_GET["newcompany"]:$project->societe->id;
				$selectedCompany = $formcompany->selectCompaniesForNewContact($project, 'id', $selectedCompany, 'newcompany');
				print '</td>';

				print '<td colspan="1">';
				$nbofcontacts=$html->select_contacts($selectedCompany, '', 'contactid');
				if ($nbofcontacts == 0) print $langs->trans("NoContactDefined");
				print '</td>';
				print '<td>';
				$formcompany->selectTypeContact($project, '', 'type','external','rowid');
				print '</td>';
				print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"';
				if (! $nbofcontacts) print ' disabled="disabled"';
				print '></td>';
				print '</tr>';

				print "</form>";
			}

			print '<tr><td colspan="6">&nbsp;</td></tr>';
		}

		// Liste des contacts lies
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Source").'</td>';
		print '<td>'.$langs->trans("Company").'</td>';
		print '<td>'.$langs->trans("Contacts").'</td>';
		print '<td>'.$langs->trans("ContactType").'</td>';
		print '<td align="center">'.$langs->trans("Status").'</td>';
		print '<td colspan="2">&nbsp;</td>';
		print "</tr>\n";

		$companystatic = new Societe($db);
		$var = true;

		foreach(array('internal','external') as $source)
		{
			$tab = $project->liste_contact(-1,$source);
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
                    $userstatic->nom=$tab[$i]['nom'];
                    $userstatic->prenom=$tab[$i]['firstname'];
                    print $userstatic->getNomUrl(1);
				}
				if ($tab[$i]['source']=='external')
				{
                    $contactstatic->id=$tab[$i]['id'];
                    $contactstatic->name=$tab[$i]['nom'];
                    $contactstatic->firstname=$tab[$i]['firstname'];
                    print $contactstatic->getNomUrl(1);
				}
				print '</td>';

				// Type de contact
				print '<td>'.$tab[$i]['libelle'].'</td>';

				// Statut
				print '<td align="center">';
				// Activation desativation du contact
				if ($project->statut >= 0 && $userAccess) print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$project->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'">';
				print $contactstatic->LibStatut($tab[$i]['status'],3);
				if ($project->statut >= 0 && $userAccess) print '</a>';
				print '</td>';

				// Icon update et delete
				print '<td align="center" nowrap>';
				if ($user->rights->projet->creer && $userAccess)
				{
					print '&nbsp;';
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$project->id.'&amp;action=deleteline&amp;lineid='.$tab[$i]['rowid'].'">';
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

$db->close();

llxFooter();
?>