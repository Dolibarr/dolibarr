<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 *
 * $Id$
 * $Source$
 */

/**
   \file       htdocs/fichinter/fiche.php
   \brief      Fichier fiche intervention
   \ingroup    ficheinter
   \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/fichinter/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/fichinter/modules_fichinter.php");
require_once(DOL_DOCUMENT_ROOT."/lib/fichinter.lib.php");
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT."/project.class.php");
if (defined("FICHEINTER_ADDON") && is_readable(DOL_DOCUMENT_ROOT ."/includes/modules/fichinter/".FICHEINTER_ADDON.".php"))
{
	require_once(DOL_DOCUMENT_ROOT ."/includes/modules/fichinter/".FICHEINTER_ADDON.".php");
}

$langs->load("companies");
$langs->load("interventions");


$fichinterid = isset($_GET["id"])?$_GET["id"]:'';

// Sécurité d'accès client et commerciaux
$socid = restrictedArea($user, 'ficheinter', $fichinterid, 'fichinter');

//Récupère le résultat de la recherche Ajax
//Todo: voir pour le supprimer par la suite
if ($conf->use_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT && $_POST['socid_id'])
{
	$_POST['socid'] = $_POST['socid_id'];
}

/*
 * Traitements des actions
 */
if ($_REQUEST["action"] != 'create' && $_REQUEST["action"] != 'add' && ! $_REQUEST["id"] > 0)
{
	Header("Location: index.php");
	return;
}

if ($_REQUEST['action'] == 'confirm_validate' && $_REQUEST['confirm'] == 'yes')
{
  $fichinter = new Fichinter($db);
  $fichinter->id = $_GET["id"];
  $result=$fichinter->valid($user, $conf->fichinter->outputdir);
  if ($result < 0) $mesg='<div class="error">'.$fichinter->error.'</div>';
}

if ($_POST["action"] == 'add')
{
	$fichinter = new Fichinter($db);
	
	$fichinter->date = dolibarr_mktime(12, 0 , 0, $_POST["pmonth"], $_POST["pday"], $_POST["pyear"]);
	$fichinter->socid = $_POST["socid"];
	$fichinter->duree = $_POST["duree"];
	$fichinter->projet_id = $_POST["projetidp"];
	$fichinter->author = $user->id;
	$fichinter->description = $_POST["description"];
	$fichinter->ref = $_POST["ref"];
	$fichinter->modelpdf = $_POST["model"];

	if ($fichinter->socid > 0)
	{
		$result = $fichinter->create();
		if ($result > 0)
		{
			$_GET["id"]=$result;      // Force raffraichissement sur fiche venant d'etre créée
			$fichinterid=$result;
		}
		else
		{
			$mesg='<div class="error">'.$fichinter->error.'</div>';
			$_GET["action"] = 'create';
		}
	}
	else
	{
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("ThirdParty")).'</div>';
			$_GET["action"] = 'create';
	}
}

if ($_POST["action"] == 'update')
{
  $fichinter = new Fichinter($db);
  
  $fichinter->date = $db->idate(mktime(12, 1 , 1, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]));
  $fichinter->socid = $_POST["socid"];
  $fichinter->projet_id = $_POST["projetidp"];
  $fichinter->author = $user->id;
  $fichinter->description = $_POST["description"];
  $fichinter->ref = $_POST["ref"];
  
  $fichinter->update($_POST["id"]);
  $_GET["id"]=$_POST["id"];      // Force raffraichissement sur fiche venant d'etre créée
}

/*
 * Générer ou regénérer le document PDF
 */
if ($_REQUEST['action'] == 'builddoc')	// En get ou en post
{
  if ($_REQUEST['lang_id'])
  {
  	$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
    $outputlangs->setDefaultLang($_REQUEST['lang_id']);
  }
  
  $result=fichinter_pdf_create($db, $_REQUEST['id'], $_REQUEST['model'], $outputlangs);
  if ($result <= 0)
    {
      dolibarr_print_error($db,$result);
      exit;
    }
}

/*
 * Classer dans un projet
 */
if ($_POST['action'] == 'classin')
{
  $fichinter = new Fichinter($db);
  $fichinter->fetch($_GET['id']);
  $fichinter->set_project($user, $_POST['projetidp']);
}

if ($_REQUEST['action'] == 'confirm_delete' && $_REQUEST['confirm'] == 'yes')
{
  if ($user->rights->ficheinter->supprimer)
    {
      $fichinter = new Fichinter($db);
      $fichinter->fetch($_GET['id']);
      $fichinter->delete($user);
    }
  Header('Location: index.php?leftmenu=ficheinter');
  exit;
}

if ($_POST['action'] == 'setdate_delivery')
{
	$fichinter = new Fichinter($db);
  $fichinter->fetch($_GET['id']);
	$result=$fichinter->set_date_delivery($user,dolibarr_mktime(12, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']));
	if ($result < 0) dolibarr_print_error($db,$fichinter->error);
}

if ($_POST['action'] == 'setdescription')
{
	$fichinter = new Fichinter($db);
  $fichinter->fetch($_GET['id']);
	$result=$fichinter->set_description($user,$_POST['description']);
	if ($result < 0) dolibarr_print_error($db,$fichinter->error);
}

/*
 *  Ajout d'une ligne d'intervention
 */
if ($_POST['action'] == "addligne" && $user->rights->ficheinter->creer)
{
	if ($_POST['np_desc'] && ($_POST['durationhour'] || $_POST['durationmin']))
	{
		$fichinter = new Fichinter($db);
	  $ret=$fichinter->fetch($_POST['fichinterid']);
		
		$desc=$_POST['np_desc'];
		$date_intervention = $db->idate(mktime(12, 1 , 1, $_POST["dimonth"], $_POST["diday"], $_POST["diyear"]));
		$duration = ConvertTime2Seconds($_POST['durationhour'],$_POST['durationmin']);

		$fichinter->addline(
			$_POST['fichinterid'],
			$desc,
			$date_intervention,
			$duration
		);

		if ($_REQUEST['lang_id'])
		{
			$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
  	fichinter_pdf_create($db, $fichinter->id, $fichinter->modelpdf, $outputlangs);
	}
}

/*
 *  Mise à jour d'une ligne d'intervention
 */
if ($_POST['action'] == 'updateligne' && $user->rights->ficheinter->creer && $_POST["save"] == $langs->trans("Save"))
{
  $fichinter = new Fichinter($db);
	if (! $fichinter->fetch($_POST['fichinterid']) > 0) dolibarr_print_error($db);
	
	$desc=$_POST['desc'];
  $date_intervention = $db->idate(mktime(12, 1 , 1, $_POST["dimonth"], $_POST["diday"], $_POST["diyear"]));
	$duration = ConvertTime2Seconds($_POST['durationhour'],$_POST['durationmin']);

  $result = $fichinter->updateline($_POST['ligne'],
  	$desc,
  	$date_intervention,
  	$duration
	 );

	if ($_REQUEST['lang_id'])
	{
		$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
    fichinter_pdf_create($db, $fichinter->id, $fichinter->modelpdf, $outputlangs);
}

/*
 *  Supprime une ligne d'intervention SANS confirmation
 */
if ($_GET['action'] == 'deleteline' && $user->rights->ficheinter->creer && !$conf->global->PRODUIT_CONFIRM_DELETE_LINE)
{
	$fichinter = new Fichinter($db);
	$fichinter->fetch($_GET['id']);
	$result=$fichinter->delete_line($_GET['ligne']);
	if ($_REQUEST['lang_id'])
	{
		$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	fichinter_pdf_create($db, $fichinter->id, $fichinter->modelpdf, $outputlangs);
}

/*
 *  Supprime une ligne d'intervention AVEC confirmation
 */
if ($_REQUEST['action'] == 'confirm_deleteline' && $_REQUEST['confirm'] == 'yes' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
{
  if ($user->rights->ficheinter->creer)
  {
  	$fichinter = new Fichinter($db);
    $fichinter->fetch($_GET['id']);
    $result=$fichinter->delete_line($_GET['ligne']);
    if ($_REQUEST['lang_id'])
    {
    	$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
    	$outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    fichinter_pdf_create($db, $fichinter->id, $fichinter->modelpdf, $outputlangs);
  }
  Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$_GET['id']);
  exit;
}

/*
 * Ordonnancement des lignes
 */

if ($_GET['action'] == 'up' && $user->rights->ficheinter->creer)
{
	$fichinter = new Fichinter($db);
	$fichinter->fetch($_GET['id']);
	$fichinter->line_up($_GET['rowid']);
	if ($_REQUEST['lang_id'])
	{
		$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
  fichinter_pdf_create($db, $fichinter->id, $fichinter->modelpdf, $outputlangs);
	Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'#'.$_GET['rowid']);
}

if ($_GET['action'] == 'down' && $user->rights->ficheinter->creer)
{
	$fichinter = new Fichinter($db);
	$fichinter->fetch($_GET['id']);
	$fichinter->line_down($_GET['rowid']);
	if ($_REQUEST['lang_id'])
	{
		$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
  	fichinter_pdf_create($db, $fichinter->id, $fichinter->modelpdf, $outputlangs);
	Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'#'.$_GET['rowid']);
	exit;
}


/*
 * Affichage page
 */
$html = new Form($db);

llxHeader();

if ($_GET["action"] == 'create')
{
	/*
	 * Mode creation
	 * Creation d'une nouvelle fiche d'intervention
	 */
	if ($_GET["socid"] > 0)
	{
		$societe=new Societe($db); 	 
		$societe->fetch($_GET["socid"]);
	}

	print_titre($langs->trans("AddIntervention"));

	if ($mesg) print $mesg.'<br>';
	
	if (! $conf->global->FICHEINTER_ADDON)
	{
		dolibarr_print_error($db,$langs->trans("Error")." ".$langs->trans("Error_FICHEINTER_ADDON_NotDefined"));
		exit;
	}

	$ficheinter = new Fichinter($db);
	$result=$ficheinter->fetch($fichinterid);

	$obj = $conf->global->FICHEINTER_ADDON;
	$file = $obj.".php";

	$modFicheinter = new $obj;
	$numpr = $modFicheinter->getNextValue($societe,$ficheinter);

	print "<form name='fichinter' action=\"fiche.php\" method=\"post\">";

	print '<table class="border" width="100%">';

	if ($_GET["socid"])
	{
		print '<input type="hidden" name="socid" value='.$_GET["socid"].'>';
		print "<tr><td>".$langs->trans("Company")."</td><td>".$societe->getNomUrl(1)."</td></tr>";
	}
	else
	{
		print "<tr><td>".$langs->trans("Company")."</td><td>";
		$html->select_societes('','socid','',1);
		print "</td></tr>";
	}

	print "<tr><td>".$langs->trans("Date")."</td><td>";
	$html->select_date(time(),"p",'','','','fichinter');
	print "</td></tr>";

	print "<input type=\"hidden\" name=\"action\" value=\"add\">";

	print "<tr><td>".$langs->trans("Ref")."</td>";
	print "<td><input name=\"ref\" value=\"$numpr\"></td></tr>\n";

	if ($conf->projet->enabled && $_GET["socid"])
	{
		// Projet associe
		$langs->load("project");

		print '<tr><td valign="top">'.$langs->trans("Project").'</td><td>';
		
    if ($_GET["socid"]) $numprojet = $societe->has_projects();
    
		if (!$numprojet)
		{
			print '<table class="nobordernopadding" width="100%">';
			print '<tr><td width="130">'.$langs->trans("NoProject").'</td>';

			$user->getrights("projet");

			if ($user->rights->projet->creer)
			{
				print '<td><a href='.DOL_URL_ROOT.'/projet/fiche.php?socid='.$societe->id.'&action=create>'.$langs->trans("Add").'</a></td>';
			}
			print '</tr></table>';
		}
		else
		{
			$html->select_projects($societe->id,'','projetidp');
		}
		print '</td></tr>';
	}
	
	// Model
  print '<tr>';
  print '<td>'.$langs->trans("DefaultModel").'</td>';
  print '<td colspan="2">';
  $model=new ModelePDFFicheinter();
  $liste=$model->liste_modeles($db);
  $html->select_array('model',$liste,$conf->global->FICHINTER_ADDON_PDF);
  print "</td></tr>";

	print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
	print "<td>";

	if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_SOCIETE)
	{
		// Editeur wysiwyg
		require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
		$doleditor=new DolEditor('description','',280,'dolibarr_notes','In',true);
		$doleditor->Create();
	}
	else
	{
		print '<textarea name="description" wrap="soft" cols="70" rows="12"></textarea>';
	}

	print '</td></tr>';

	print '<tr><td colspan="2" align="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("CreateDaftIntervention").'">';
	print '</td></tr>';

	print '</table>';
	print '</form>';
}
elseif ($_GET["id"] > 0)
{
	/*
* Affichage en mode visu
*/

	$html = new Form($db); 
	$fichinter = new Fichinter($db);
	$result=$fichinter->fetch($_GET["id"]);
	if (! $result > 0)
	{
		dolibarr_print_error($db);
		exit;
	}
	$fichinter->fetch_client();

	// Debug mode
	// TODO: créer une fonction debug générique
	if ($conf->use_debug_mode)
	{
		$debug = '<div class="error">';
		$debug.= 'Mode Debugage<br>';
		$debug.= 'Module intervention: lire='.yn($user->rights->ficheinter->lire).', creer='.yn($user->rights->ficheinter->creer);
		$debug.= ', supprimer='.yn($user->rights->ficheinter->supprimer);
		$debug.= '</div>';
		print $debug;
	}

	if ($mesg) print $mesg."<br>";

	$head = fichinter_prepare_head($fichinter);

	dolibarr_fiche_head($head, 'card', $langs->trans("InterventionCard"));

	/*
* Confirmation de la suppression de la fiche d'intervention
*/
	if ($_GET['action'] == 'delete')
	{
		$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$fichinter->id, $langs->trans('DeleteIntervention'), $langs->trans('ConfirmDeleteIntervention'), 'confirm_delete');
		print '<br>';
	}

	/*
* Confirmation de la validation de la fiche d'intervention
*/
	if ($_GET['action'] == 'validate')
	{
		$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$fichinter->id, $langs->trans('ValidateIntervention'), $langs->trans('ConfirmValidateIntervention'), 'confirm_validate');
		print '<br>';
	}

	/*
* Confirmation de la suppression d'une ligne d'intervention
*/
	if ($_GET['action'] == 'ask_deleteline' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
	{
		$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$fichinter->id.'&amp;ligne='.$_GET["ligne"], $langs->trans('DeleteInterventionLine'), $langs->trans('ConfirmDeleteInterventionLine'), 'confirm_deleteline');
		print '<br>';
	}

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>'.$fichinter->ref.'</td></tr>';

	// Societe
	print "<tr><td>".$langs->trans("Company")."</td><td>".$fichinter->client->getNomUrl(1)."</td></tr>";

	// Date
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('Date');
	print '</td>';
	if ($_GET['action'] != 'editdate_delivery' && $fichinter->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_delivery&amp;id='.$fichinter->id.'">'.img_edit($langs->trans('SetDateCreate'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editdate_delivery')
	{
		print '<form name="editdate_delivery" action="'.$_SERVER["PHP_SELF"].'?id='.$fichinter->id.'" method="post">';
		print '<input type="hidden" name="action" value="setdate_delivery">';
		$html->select_date($fichinter->date,'liv_','','','',"editdate_delivery");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
	{
		print dolibarr_print_date($fichinter->date,'%a %d %B %Y');
	}
	print '</td>';
	print '</tr>';

	// Projet
	if ($conf->projet->enabled)
	{
		$langs->load("projects");
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Project').'</td>';
		$societe=new Societe($db);
		$societe->fetch($fichinter->socid);
		$numprojet = $societe->has_projects();
		if (! $numprojet)
		{
			print '</td></tr></table>';
			print '<td>';
			print $langs->trans("NoProject").'&nbsp;&nbsp;';
			if ($fichinter->brouillon) print '<a href=../projet/fiche.php?socid='.$societe->id.'&action=create>'.$langs->trans('AddProject').'</a>';
			print '</td>';
		}
		else
		{
			if ($fichinter->statut == 0 && $user->rights->ficheinter->creer)
			{
				if ($_GET['action'] != 'classer' && $fichinter->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=classer&amp;id='.$fichinter->id.'">'.img_edit($langs->trans('SetProject')).'</a></td>';
				print '</tr></table>';
				print '</td><td colspan="3">';
				if ($_GET['action'] == 'classer')
				{
					$html->form_project($_SERVER['PHP_SELF'].'?id='.$fichinter->id, $fichinter->socid, $fichinter->projetidp, 'projetidp');
				}
				else
				{
					$html->form_project($_SERVER['PHP_SELF'].'?id='.$fichinter->id, $fichinter->socid, $fichinter->projetidp, 'none');
				}
				print '</td></tr>';
			}
			else
			{
				if (!empty($fichinter->projetidp))
				{
					print '</td></tr></table>';
					print '<td colspan="3">';
					$proj = new Project($db);
					$proj->fetch($fichinter->projetidp);
					print '<a href="../projet/fiche.php?id='.$fichinter->projetidp.'" title="'.$langs->trans('ShowProject').'">';
					print $proj->title;
					print '</a>';
					print '</td>';
				}
				else {
					print '</td></tr></table>';
					print '<td colspan="3">&nbsp;</td>';
				}
			}
		}
		print '</tr>';
	}
	
	// Durée
	print '<tr><td>'.$langs->trans("TotalDuration").'</td><td>'.$fichinter->duree.'</td></tr>';
	
	// Description
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('Description');
	print '</td>';
	if ($_GET['action'] != 'editdescription' && $fichinter->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdescription&amp;id='.$fichinter->id.'">'.img_edit($langs->trans('SetDescription'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editdescription')
	{
		print '<form name="editdescription" action="'.$_SERVER["PHP_SELF"].'?id='.$fichinter->id.'" method="post">';
		print '<input type="hidden" name="action" value="setdescription">';
		if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_SOCIETE)
		{
			// Editeur wysiwyg
			require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$doleditor=new DolEditor('description',$fichinter->description,280,'dolibarr_notes','In',true);
			$doleditor->Create();
		}
		else
		{
			print '<textarea name="description" wrap="soft" cols="70" rows="12">'.$fichinter->description.'</textarea>';
		}
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
	{
		print nl2br($fichinter->description);
	}
	print '</td>';
	print '</tr>';
	
	// Statut
	print '<tr><td>'.$langs->trans("Status").'</td><td>'.$fichinter->getLibStatut(4).'</td></tr>';

	print "</table><br>";
	
	/*
	* Lignes d'intervention
	*/
	print '<table class="noborder" width="100%">';

	$sql = 'SELECT ft.rowid, ft.description, ft.fk_fichinter, ft.duree, ft.rang';
	$sql.= ', '.$db->pdate('ft.date').' as date_intervention';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'fichinterdet as ft';
	$sql.= ' WHERE ft.fk_fichinter = '.$fichinterid;
	$sql.= ' ORDER BY ft.rang ASC, ft.rowid';
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		if ($num)
		{
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans('Description').'</td>';
			print '<td>'.$langs->trans('Date').'</td>';
			print '<td>'.$langs->trans('Duration').'</td>';
			print '<td width="48" colspan="3">&nbsp;</td>';
			print "</tr>\n";
		}
		$var=true;
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);
			$var=!$var;

			// Ligne en mode visu
			if ($_GET['action'] != 'editline' || $_GET['ligne'] != $objp->rowid)
			{
				print '<tr '.$bc[$var].'>';
				print '<td>';
				print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
				print nl2br($objp->description);
				
				print '<td width="150">'.dolibarr_print_date($objp->date_intervention,'%a %d %B %Y').'</td>';
				print '<td width="150">'.ConvertSecondToTime($objp->duree).'</td>';

				print "</td>\n";


				// Icone d'edition et suppression
				if ($fichinter->statut == 0  && $user->rights->ficheinter->creer)
				{
					print '<td align="center">';
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$fichinter->id.'&amp;action=editline&amp;ligne='.$objp->rowid.'#'.$objp->rowid.'">';
					print img_edit();
					print '</a>';
					print '</td>';
					print '<td align="center">';
					if ($conf->global->PRODUIT_CONFIRM_DELETE_LINE)
					{
						if ($conf->use_ajax && $conf->global->MAIN_CONFIRM_AJAX)
						{
							$url = $_SERVER["PHP_SELF"].'?id='.$fichinter->id.'&ligne='.$objp->rowid.'&action=confirm_deleteline&confirm=yes';
							print '<a href="#" onClick="dialogConfirm(\''.$url.'\',\''.$langs->trans('ConfirmDeleteInterventionLine').'\',\''.$langs->trans("Yes").'\',\''.$langs->trans("No").'\',\'deleteline'.$i.'\')">';
							print img_delete();
						}
						else
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$fichinter->id.'&amp;action=ask_deleteline&amp;ligne='.$objp->rowid.'">';
							print img_delete();
						}
					}
					else
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$fichinter->id.'&amp;action=deleteline&amp;ligne='.$objp->rowid.'">';
						print img_delete();
					}
					print '</a></td>';
					if ($num > 1)
					{
						print '<td align="center">';
						if ($i > 0)
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$fichinter->id.'&amp;action=up&amp;rowid='.$objp->rowid.'">';
							print img_up();
							print '</a>';
						}
						if ($i < $num-1)
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$fichinter->id.'&amp;action=down&amp;rowid='.$objp->rowid.'">';
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
			if ($fichinter->statut == 0 && $_GET["action"] == 'editline' && $user->rights->ficheinter->creer && $_GET["ligne"] == $objp->rowid)
			{
				print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$fichinter->id.'#'.$objp->rowid.'" method="post">';
				print '<input type="hidden" name="action" value="updateligne">';
				print '<input type="hidden" name="fichinterid" value="'.$fichinter->id.'">';
				print '<input type="hidden" name="ligne" value="'.$_GET["ligne"].'">';
				print '<tr '.$bc[$var].'>';
				print '<td>';
				print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

				// éditeur wysiwyg
				if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
				{
					require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
					$doleditor=new DolEditor('desc',$objp->description,164,'dolibarr_details');
					$doleditor->Create();
				}
				else
				{
					print '<textarea name="desc" cols="70" class="flat" rows="'.ROWS_2.'">'.$objp->description.'</textarea>';
				}
				print '</td>';
				
				// Date d'intervention
				print '<td>';
				$html->select_date($objp->date_intervention,'di',0,0,0,"date_intervention");
				print '</td>';
				
				// Durée
				print '<td>';
				$html->select_duree('duration',$objp->duree);
				print '</td>';

				print '<td align="center" colspan="5" valign="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
				print '<br /><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td>';
				print '</tr>' . "\n";

				print "</form>\n";
			}

			$i++;
		}

		$db->free($resql);
	}
	else
	{
		dolibarr_print_error($db);
	}

	/*
	* Ajouter une ligne
	*/
	if ($fichinter->statut == 0 && $user->rights->ficheinter->creer && $_GET["action"] <> 'editline')
	{
		print '<tr class="liste_titre">';
		print '<td>';
		print '<a name="add"></a>'; // ancre
		print $langs->trans('Description').'</td>';
		print '<td>'.$langs->trans('Date').'</td>';
		print '<td>'.$langs->trans('Duration').'</td>';

		print '<td colspan="4">&nbsp;</td>';
		print "</tr>\n";

		// Ajout ligne d'intervention
		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$fichinter->id.'#add" name="addinter" method="post">';
		print '<input type="hidden" name="fichinterid" value="'.$fichinter->id.'">';
		print '<input type="hidden" name="action" value="addligne">';

		$var=true;

		print '<tr '.$bc[$var].">\n";
		print '<td>';
		// éditeur wysiwyg
		if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS_PERSO)
		{
			require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$doleditor=new DolEditor('np_desc','',100,'dolibarr_details');
			$doleditor->Create();
		}
		else
		{
			print '<textarea class="flat" cols="70" name="np_desc" rows="'.ROWS_2.'"></textarea>';
		}
		print '</td>';
		
		// Date d'intervention
		print '<td>';
		$html->select_date(time(),'di',0,0,0,"addinter");
		print '</td>';
		
		// Durée
		print '<td>';
		$html->select_duree('duration');
		print '</td>';

		print '<td align="center" valign="middle" colspan="4"><input type="submit" class="button" value="'.$langs->trans('Add').'" name="addligne"></td>';
		print '</tr>';

		print '</form>';
	}

	print '</table>';

	print '</div>';
	print "\n";


	/**
	* Barre d'actions
	*
	*/
	print '<div class="tabsAction">';

	if ($user->societe_id == 0)
	{
		// Validate
		if ($fichinter->statut == 0 && $user->rights->ficheinter->creer)
		{
			print '<a class="butAction" ';
			if ($conf->use_ajax && $conf->global->MAIN_CONFIRM_AJAX)
			{
				$url = $_SERVER["PHP_SELF"].'?id='.$fichinter->id.'&action=confirm_validate&confirm=yes';
				print 'href="#" onClick="dialogConfirm(\''.$url.'\',\''.$langs->trans('ConfirmValidateIntervention').'\',\''.$langs->trans("Yes").'\',\''.$langs->trans("No").'\',\'validate\')"';
			}
			else
			{
				print 'href="fiche.php?id='.$_GET["id"].'&action=validate"';
			}
			print '>'.$langs->trans("Valid").'</a>';
		}
		
		// Delete
		if ($fichinter->statut == 0 && $user->rights->ficheinter->supprimer)
		{
			print '<a class="butActionDelete" ';
			if ($conf->use_ajax && $conf->global->MAIN_CONFIRM_AJAX)
			{
				$url = $_SERVER["PHP_SELF"].'?id='.$fichinter->id.'&action=confirm_delete&confirm=yes';
				print 'href="#" onClick="dialogConfirm(\''.$url.'\',\''.$langs->trans("ConfirmDeleteIntervention").'\',\''.$langs->trans("Yes").'\',\''.$langs->trans("No").'\',\'delete\')"';
			}
			else
			{
				print 'href="'.$_SERVER["PHP_SELF"].'?id='.$fichinter->id.'&amp;action=delete"';
			}
			print '>'.$langs->trans('Delete').'</a>';
		}
	}
	
	print '</div>';

	print '<table width="100%"><tr><td width="50%" valign="top">';
	/*
	* Documents générés
	*/
	$filename=sanitize_string($fichinter->ref);
	$filedir=$conf->fichinter->dir_output . "/".$fichinter->ref;
	$urlsource=$_SERVER["PHP_SELF"]."?id=".$fichinter->id;
	$genallowed=$user->rights->ficheinter->creer;
	$delallowed=$user->rights->ficheinter->supprimer;
	$genallowed=1;
	$delallowed=1;

	$var=true;

	print "<br>\n";
	$somethingshown=$html->show_documents('ficheinter',$filename,$filedir,$urlsource,$genallowed,$delallowed,$ficheinter->modelpdf);

	print "</td><td>";
	print "&nbsp;</td>";
	print "</tr></table>\n";

}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
