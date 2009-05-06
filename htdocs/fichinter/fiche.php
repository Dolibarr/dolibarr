<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/fichinter/fiche.php
 *	\brief      Fichier fiche intervention
 *	\ingroup    ficheinter
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/fichinter/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/fichinter/modules_fichinter.php");
require_once(DOL_DOCUMENT_ROOT."/lib/fichinter.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/date.lib.php");
if ($conf->projet->enabled)
{
	require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");
	require_once(DOL_DOCUMENT_ROOT."/project.class.php");
}
if (defined("FICHEINTER_ADDON") && is_readable(DOL_DOCUMENT_ROOT ."/includes/modules/fichinter/mod_".FICHEINTER_ADDON.".php"))
{
	require_once(DOL_DOCUMENT_ROOT ."/includes/modules/fichinter/mod_".FICHEINTER_ADDON.".php");
}

$langs->load("companies");
$langs->load("interventions");

// Get parameters
$fichinterid = isset($_GET["id"])?$_GET["id"]:'';

// If socid provided by ajax company selector
if (! empty($_POST['socid_id']))
{
	$_POST['socid'] = $_POST['socid_id'];
	$_REQUEST['socid'] = $_REQUEST['socid_id'];
}

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $fichinterid, 'fichinter');



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
	$fichinter->fetch($_GET["id"]);

	$result = $fichinter->setValid($user, $conf->fichinter->outputdir);
	if ($result >= 0)
	{
		$outputlangs = $langs;
		if (! empty($_REQUEST['lang_id']))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		$result=fichinter_create($db, $fichinter, $_REQUEST['model'], $outputlangs);
	}
	else
	{
		$mesg='<div class="error">'.$fichinter->error.'</div>';
	}
}

if ($_REQUEST['action'] == 'confirm_modify' && $_REQUEST['confirm'] == 'yes')
{
	$fichinter = new Fichinter($db);
	$fichinter->id = $_GET["id"];
	$fichinter->fetch($_GET["id"]);

	$result = $fichinter->setDraft($user);
	if ($result >= 0)
	{
		$outputlangs = $langs;
		if (! empty($_REQUEST['lang_id']))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		$result=fichinter_create($db, $fichinter, $_REQUEST['model'], $outputlangs);
	}
	else
	{
		$mesg='<div class="error">'.$fichinter->error.'</div>';
	}
}

if ($_POST["action"] == 'add')
{
	$fichinter = new Fichinter($db);

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
			$_GET["id"]=$result;      // Force raffraichissement sur fiche venant d'etre cree
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

	$fichinter->socid = $_POST["socid"];
	$fichinter->projet_id = $_POST["projetidp"];
	$fichinter->author = $user->id;
	$fichinter->description = $_POST["description"];
	$fichinter->ref = $_POST["ref"];

	$fichinter->update($_POST["id"]);
	$_GET["id"]=$_POST["id"];      // Force raffraichissement sur fiche venant d'etre creee
}

/*
 * Build doc
 */
if ($_REQUEST['action'] == 'builddoc')	// En get ou en post
{
	$fichinter = new Fichinter($db);
	$fichinter->fetch($_GET['id']);
	$fichinter->fetch_lines();

	if ($_REQUEST['model'])
	{
		$fichinter->setDocModel($user, $_REQUEST['model']);
	}

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	$result=fichinter_create($db, $fichinter, $_REQUEST['model'], $outputlangs);
	if ($result <= 0)
	{
		dol_print_error($db,$result);
		exit;
	}
}

// Set into a project
if ($_POST['action'] == 'classin')
{
	$fichinter = new Fichinter($db);
	$fichinter->fetch($_GET['id']);
	$result=$fichinter->setProject($_POST['projetid']);
	if ($result < 0) dol_print_error($db,$fichinter->error);
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

if ($_POST['action'] == 'setdescription')
{
	$fichinter = new Fichinter($db);
	$fichinter->fetch($_GET['id']);
	$result=$fichinter->set_description($user,$_POST['description']);
	if ($result < 0) dol_print_error($db,$fichinter->error);
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
		$date_intervention = dol_mktime($_POST["dihour"], $_POST["dimin"], 0, $_POST["dimonth"], $_POST["diday"], $_POST["diyear"]);
		$duration = ConvertTime2Seconds($_POST['durationhour'],$_POST['durationmin']);

		$fichinter->addline(
		$_POST['fichinterid'],
		$desc,
		$date_intervention,
		$duration
		);

		$outputlangs = $langs;
		if (! empty($_REQUEST['lang_id']))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		fichinter_create($db, $fichinter, $fichinter->modelpdf, $outputlangs);
	}
}

/*
 *  Mise a jour d'une ligne d'intervention
 */
if ($_POST['action'] == 'updateligne' && $user->rights->ficheinter->creer && $_POST["save"] == $langs->trans("Save"))
{
	$fichinterline = new FichinterLigne($db);
	if ($fichinterline->fetch($_POST['ligne']) <= 0)
	{
		dol_print_error($db);
		exit;
	}
	$fichinter = new Fichinter($db);
	if ($fichinter->fetch($fichinterline->fk_fichinter) <= 0)
	{
		dol_print_error($db);
		exit;
	}
	$desc=$_POST['desc'];
	$date_intervention = dol_mktime($_POST["dihour"], $_POST["dimin"], 0, $_POST["dimonth"], $_POST["diday"], $_POST["diyear"]);
	$duration = ConvertTime2Seconds($_POST['durationhour'],$_POST['durationmin']);

	$fichinterline->datei=$date_intervention;
	$fichinterline->desc=$desc;
	$fichinterline->duration=$duration;
	$result = $fichinterline->update();

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	fichinter_create($db, $fichinter, $fichinter->modelpdf, $outputlangs);

}

/*
 *  Supprime une ligne d'intervention SANS confirmation
 */
if ($_GET['action'] == 'deleteline' && $user->rights->ficheinter->creer && !$conf->global->PRODUIT_CONFIRM_DELETE_LINE)
{
	$fichinterline = new FichinterLigne($db);
	if ($fichinterline->fetch($_GET['ligne']) <= 0)
	{
		dol_print_error($db);
		exit;
	}
	$result=$fichinterline->delete_line();
	$fichinter = new Fichinter($db);
	if ($fichinter->fetch($fichinterline->fk_fichinter) <= 0)
	{
		dol_print_error($db);
		exit;
	}

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	fichinter_create($db, $fichinter, $fichinter->modelpdf, $outputlangs);
}

/*
 *  Supprime une ligne d'intervention AVEC confirmation
 */
if ($_REQUEST['action'] == 'confirm_deleteline' && $_REQUEST['confirm'] == 'yes' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
{
	if ($user->rights->ficheinter->creer)
	{
		$fichinterline = new FichinterLigne($db);
		if ($fichinterline->fetch($_GET['ligne']) <= 0)
		{
			dol_print_error($db);
			exit;
		}
		$result=$fichinterline->delete_line();
		$fichinter = new Fichinter($db);
		if ($fichinter->fetch($fichinterline->fk_fichinter) <= 0)
		{
			dol_print_error($db);
			exit;
		}

		$outputlangs = $langs;
		if (! empty($_REQUEST['lang_id']))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		fichinter_create($db, $fichinter, $fichinter->modelpdf, $outputlangs);
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

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	fichinter_create($db, $fichinter, $fichinter->modelpdf, $outputlangs);
	Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'#'.$_GET['rowid']);
	exit;
}

if ($_GET['action'] == 'down' && $user->rights->ficheinter->creer)
{
	$fichinter = new Fichinter($db);
	$fichinter->fetch($_GET['id']);
	$fichinter->line_down($_GET['rowid']);

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	fichinter_create($db, $fichinter, $fichinter->modelpdf, $outputlangs);
	Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'#'.$_GET['rowid']);
	exit;
}


/*
 * View
 */

$html = new Form($db);
$formfile = new FormFile($db);

llxHeader();

if ($_GET["action"] == 'create')
{
	/*
	 * Mode creation
	 * Creation d'une nouvelle fiche d'intervention
	 */

	$societe=new Societe($db);
	if ($_GET["socid"] > 0)
	{
		$societe->fetch($_GET["socid"]);
	}

	print_fiche_titre($langs->trans("AddIntervention"));

	if ($mesg) print $mesg.'<br>';

	if (! $conf->global->FICHEINTER_ADDON)
	{
		dol_print_error($db,$langs->trans("Error")." ".$langs->trans("Error_FICHEINTER_ADDON_NotDefined"));
		exit;
	}

	$ficheinter = new Fichinter($db);
	$ficheinter->date = time();
	if ($fichinterid) $result=$ficheinter->fetch($fichinterid);

	$obj = $conf->global->FICHEINTER_ADDON;
	$obj = "mod_".$obj;

	$modFicheinter = new $obj;
	$numpr = $modFicheinter->getNextValue($societe,$ficheinter);

	if ($_GET["socid"] > 0)
	{
		print "<form name='fichinter' action=\"fiche.php\" method=\"post\">";

		print '<table class="border" width="100%">';

		print '<input type="hidden" name="socid" value='.$_GET["socid"].'>';
		print "<tr><td>".$langs->trans("Company")."</td><td>".$societe->getNomUrl(1)."</td></tr>";

		print "<input type=\"hidden\" name=\"action\" value=\"add\">";

		print "<tr><td>".$langs->trans("Ref")."</td>";
		print "<td><input name=\"ref\" value=\"$numpr\"></td></tr>\n";

		if ($conf->projet->enabled)
		{
			// Projet associe
			$langs->load("project");

			print '<tr><td valign="top">'.$langs->trans("Project").'</td><td>';
			$numprojet=select_projects($societe->id,$projetid,'projetidp');
			if ($numprojet==0)
			{
				print ' &nbsp; <a href="../projet/fiche.php?socid='.$societe->id.'&action=create">'.$langs->trans("AddProject").'</a>';
			}
			print '</td></tr>';
		}

		// Model
		print '<tr>';
		print '<td>'.$langs->trans("DefaultModel").'</td>';
		print '<td colspan="2">';
		$model=new ModelePDFFicheinter();
		$liste=$model->liste_modeles($db);
		$html->select_array('model',$liste,$conf->global->FICHEINTER_ADDON_PDF);
		print "</td></tr>";

		// Description (must be a textarea and not html must be allowed (used in list view)
		print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
		print "<td>";
		print '<textarea name="description" wrap="soft" cols="80" rows="'.ROWS_3.'"></textarea>';
		print '</td></tr>';

		print '<tr><td colspan="2" align="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("CreateDraftIntervention").'">';
		print '</td></tr>';

		print '</table>';
		print '</form>';
	}
	else
	{
		print "<form name='fichinter' action=\"fiche.php\" method=\"get\">";
		print '<table class="border" width="100%">';
		print "<tr><td>".$langs->trans("Company")."</td><td>";
		$html->select_societes('','socid','s.client = 1',1);
		print "</td></tr>";
		print '<tr><td colspan="2" align="center">';
		print "<input type=\"hidden\" name=\"action\" value=\"create\">";
		print '<input type="submit" class="button" value="'.$langs->trans("CreateDraftIntervention").'">';
		print '</td></tr>';
		print '</table>';
		print '</form>';
	}

}
elseif ($_GET["id"] > 0)
{
	/*
	 * Affichage en mode visu
	 */
	$fichinter = new Fichinter($db);
	$result=$fichinter->fetch($_GET["id"]);
	if (! $result > 0)
	{
		dol_print_error($db);
		exit;
	}
	$fichinter->fetch_client();

	$societe=new Societe($db);
	$societe->fetch($fichinter->socid);

	if ($mesg) print $mesg."<br>";

	$head = fichinter_prepare_head($fichinter);

	dol_fiche_head($head, 'card', $langs->trans("InterventionCard"));

	// Confirmation de la suppression de la fiche d'intervention
	if ($_GET['action'] == 'delete')
	{
		$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$fichinter->id, $langs->trans('DeleteIntervention'), $langs->trans('ConfirmDeleteIntervention'), 'confirm_delete');
		if ($ret == 'html') print '<br>';
	}

	// Confirmation de la validation de la fiche d'intervention
	if ($_GET['action'] == 'validate')
	{
		$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$fichinter->id, $langs->trans('ValidateIntervention'), $langs->trans('ConfirmValidateIntervention'), 'confirm_validate');
		if ($ret == 'html') print '<br>';
	}

	// Confirmation de la validation de la fiche d'intervention
	if ($_GET['action'] == 'modify')
	{
		$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$fichinter->id, $langs->trans('ModifyIntervention'), $langs->trans('ConfirmModifyIntervention'), 'confirm_modify');
		if ($ret == 'html') print '<br>';
	}

	// Confirmation de la suppression d'une ligne d'intervention
	if ($_GET['action'] == 'ask_deleteline' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
	{
		$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$fichinter->id.'&amp;ligne='.$_GET["ligne"], $langs->trans('DeleteInterventionLine'), $langs->trans('ConfirmDeleteInterventionLine'), 'confirm_deleteline');
		if ($ret == 'html') print '<br>';
	}

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>'.$fichinter->ref.'</td></tr>';

	// Societe
	print "<tr><td>".$langs->trans("Company")."</td><td>".$fichinter->client->getNomUrl(1)."</td></tr>";

	// Project
	if ($conf->projet->enabled)
	{
		$langs->load('projects');
		print '<tr>';
		print '<td>';

		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Project');
		print '</td>';
		if ($_GET['action'] != 'classin')
		{
			print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classin&amp;id='.$fichinter->id.'">';
			print img_edit($langs->trans('SetProject'),1);
			print '</a></td>';
		}
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($_GET['action'] == 'classin')
		{
			$html->form_project($_SERVER['PHP_SELF'].'?id='.$fichinter->id, $fichinter->socid, $fichinter->projetidp,'projetid');
		}
		else
		{
			$html->form_project($_SERVER['PHP_SELF'].'?id='.$fichinter->id, $fichinter->socid, $fichinter->projetidp,'none');
		}
		print '</td>';
		print '</tr>';
	}

	// Duration
	print '<tr><td>'.$langs->trans("TotalDuration").'</td><td>'.ConvertSecondToTime($fichinter->duree).'</td></tr>';

	// Description (must be a textarea and not html must be allowed (used in list view)
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
		print '<textarea name="description" wrap="soft" cols="70" rows="'.ROWS_3.'">'.dol_htmlentitiesbr_decode($fichinter->description).'</textarea><br>';
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

	print "</table>";

	/*
	 * Lignes d'intervention
	 */

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
			if ($_GET['action'] != 'editline' || $_GET['ligne'] != $objp->rowid)
			{
				print '<tr '.$bc[$var].'>';
				print '<td>';
				print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
				print nl2br($objp->description);

				// Date
				print '<td align="center" width="150">'.dol_print_date($objp->date_intervention,'dayhour').'</td>';

				// Duration
				print '<td align="right" width="150">'.ConvertSecondToTime($objp->duree).'</td>';

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
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$fichinter->id.'&amp;action=ask_deleteline&amp;ligne='.$objp->rowid.'">';
						print img_delete();
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

				// �diteur wysiwyg
				if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
				{
					require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
					$doleditor=new DolEditor('desc',$objp->description,164,'dolibarr_details');
					$doleditor->Create();
				}
				else
				{
					print '<textarea name="desc" cols="70" class="flat" rows="'.ROWS_2.'">'.dol_htmlentitiesbr_decode($objp->description).'</textarea>';
				}
				print '</td>';

				// Date d'intervention
				print '<td align="center" nowrap="nowrap">';
				$html->select_date($objp->date_intervention,'di',1,1,0,"date_intervention");
				print '</td>';

				// Duration
				print '<td align="right">';
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

		/*
		 * Ajouter une ligne
		 */
		if ($fichinter->statut == 0 && $user->rights->ficheinter->creer && $_GET["action"] <> 'editline')
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
			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$fichinter->id.'#add" name="addinter" method="post">';
			print '<input type="hidden" name="fichinterid" value="'.$fichinter->id.'">';
			print '<input type="hidden" name="action" value="addligne">';

			$var=false;

			print '<tr '.$bc[$var].">\n";
			print '<td>';
			// editeur wysiwyg
			if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
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

			// Date intervention
			print '<td align="center" nowrap="nowrap">';
			$timearray=dol_getdate(mktime());
			$timewithnohour=dol_mktime(0,0,0,$timearray['mon'],$timearray['mday'],$timearray['year']);
			$html->select_date($timewithnohour,'di',1,1,0,"addinter");
			print '</td>';

			// Duration
			print '<td align="right">';
			$html->select_duree('duration');
			print '</td>';

			print '<td align="center" valign="middle" colspan="4"><input type="submit" class="button" value="'.$langs->trans('Add').'" name="addligne"></td>';
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


	/**
	 * Barre d'actions
	 *
	 */
	print '<div class="tabsAction">';

	if ($user->societe_id == 0)
	{
		if ($_GET['action'] != 'editdescription')
		{
			// Validate
			if ($fichinter->statut == 0 && $user->rights->ficheinter->creer)
			{
				print '<a class="butAction" href="fiche.php?id='.$_GET["id"].'&action=validate"';
				print '>'.$langs->trans("Valid").'</a>';
			}

			// Modify
			if ($fichinter->statut == 1 && $user->rights->ficheinter->creer)
			{
				print '<a class="butAction" href="fiche.php?id='.$_GET["id"].'&action=modify"';
				print '>'.$langs->trans("Modify").'</a>';
			}

			// Delete
			if (($fichinter->statut == 0 && $user->rights->ficheinter->creer) || $user->rights->ficheinter->supprimer)
			{
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$fichinter->id.'&amp;action=delete"';
				print '>'.$langs->trans('Delete').'</a>';
			}
		}
	}

	print '</div>';

	print '<table width="100%"><tr><td width="50%" valign="top">';
	/*
	 * Built documents
	 */
	$filename=dol_sanitizeFileName($fichinter->ref);
	$filedir=$conf->ficheinter->dir_output . "/".$fichinter->ref;
	$urlsource=$_SERVER["PHP_SELF"]."?id=".$fichinter->id;
	$genallowed=$user->rights->ficheinter->creer;
	$delallowed=$user->rights->ficheinter->supprimer;
	$genallowed=1;
	$delallowed=1;

	$var=true;

	print "<br>\n";
	$somethingshown=$formfile->show_documents('ficheinter',$filename,$filedir,$urlsource,$genallowed,$delallowed,$fichinter->modelpdf);

	print "</td><td>";
	print "&nbsp;</td>";
	print "</tr></table>\n";

}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
