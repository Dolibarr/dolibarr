<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require_once(DOL_DOCUMENT_ROOT."/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/fichinter.lib.php");
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

if ($_GET["action"] == 'valid')
{
  $fichinter = new Fichinter($db);
  $fichinter->id = $_GET["id"];
  $result=$fichinter->valid($user, $conf->fichinter->outputdir);
  if ($result < 0) $mesg='<div class="error">'.$fichinter->error.'</div>';
}

if ($_POST["action"] == 'add')
{
	$fichinter = new Fichinter($db);

	$fichinter->date = $db->idate(dolibarr_mktime(12, 1 , 1, $_POST["pmonth"], $_POST["pday"], $_POST["pyear"]));
	$fichinter->socid = $_POST["socid"];
	$fichinter->duree = $_POST["duree"];
	$fichinter->projet_id = $_POST["projetidp"];
	$fichinter->author = $user->id;
	$fichinter->description = $_POST["description"];
	$fichinter->ref = $_POST["ref"];

	$result = $fichinter->create();
	if ($result > 0)
	{
		$_GET["id"]=$result;      // Force raffraichissement sur fiche venant d'etre créée
	}
	else
	{
		$mesg='<div class="error">'.$fichinter->error.'</div>';
	}
}

if ($_POST["action"] == 'update')
{
  $fichinter = new Fichinter($db);
  
  $fichinter->date = $db->idate(mktime(12, 1 , 1, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]));
  $fichinter->socid = $_POST["socid"];
  $fichinter->duree = $_POST["duree"];
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
	 
	if ($_GET["socid"])
	{
		$societe=new Societe($db); 	 
	  $societe->fetch($_GET["socid"]);
	}

	print_titre($langs->trans("AddIntervention"));

	if (! $conf->global->FICHEINTER_ADDON)
	{
		dolibarr_print_error($db,$langs->trans("Error")." ".$langs->trans("Error_FICHEINTER_ADDON_NotDefined"));
		exit;
	}

	$ficheinter = new Fichinter($db);
	$result=$ficheinter->fetch($_GET["id"]);

	$obj = $conf->global->FICHEINTER_ADDON;
	$file = $obj.".php";

	$modFicheinter = new $obj;
	$numpr = $modFicheinter->getNextValue($societe,$ficheinter);

	print "<form name='fichinter' action=\"fiche.php\" method=\"post\">";

	$smonth = 1;
	$syear = date("Y", time());
	print '<table class="border" width="100%">';

	if ($_GET["socid"])
	{
		print '<input type="hidden" name="socid" value='.$_GET["socid"].'>';
		print "<tr><td>".$langs->trans("Company")."</td><td>".$societe->getNomUrl(1)."</td></tr>";
	}
	else
	{
		print "<tr><td>".$langs->trans("Company")."</td><td>";
		$html->select_societes('','socid','');
		print "</td></tr>";
	}

	print "<tr><td>".$langs->trans("Date")."</td><td>";
	$html->select_date(time(),"p",'','','','fichinter');
	print "</td></tr>";

	print "<input type=\"hidden\" name=\"action\" value=\"add\">";

	print "<tr><td>".$langs->trans("Ref")."</td>";
	print "<td><input name=\"ref\" value=\"$numpr\"></td></tr>\n";

	print "<tr><td>".$langs->trans("Duration")." (".$langs->trans("days").")</td><td><input name=\"duree\"></td></tr>\n";

	if ($conf->projet->enabled)
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
		print '<textarea name="description" wrap="soft" cols="70" rows="15"></textarea>';
	}

	print '</td></tr>';

	print '<tr><td colspan="2" align="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("CreateDaftIntervention").'">';
	print '</td></tr>';

	print '</table>';
	print '</form>';
}
elseif ($_GET["action"] == 'edit' && $_GET["id"] > 0)
{
  /*
   *
   * Mode update
   * Mise a jour de la fiche d'intervention
   *
   */
  $fichinter = new Fichinter($db);
  $fichinter->fetch($_GET["id"]);
  $fichinter->fetch_client();
  
  $head = fichinter_prepare_head($fichinter);

  dolibarr_fiche_head($head, 'card', $langs->trans("EditIntervention"));
  
  
  print "<form name='update' action=\"fiche.php\" method=\"post\">";
  
  print "<input type=\"hidden\" name=\"action\" value=\"update\">";
  print "<input type=\"hidden\" name=\"id\" value=\"".$_GET["id"]."\">";
  
  print '<table class="border" width="100%">';
  
  // Ref
  print '<tr><td>'.$langs->trans("Ref").'</td><td>'.$fichinter->ref.'</td></tr>';
  
  // Tiers
  print "<tr><td>".$langs->trans("Company")."</td><td>".$fichinter->client->getNomUrl(1)."</td></tr>";
  
  // Date
  print "<tr><td>".$langs->trans("Date")."</td><td>";
  $html->select_date($fichinter->date,'','','','','update');
  print "</td></tr>";
  
  print '<tr><td>'.$langs->trans("Duration")." (".$langs->trans("days").')</td><td><input name="duree" value="'.$fichinter->duree.'"></td></tr>';
  
  if ($conf->projet->enabled)
  {
  	$societe=new Societe($db);
  	$societe->fetch($fichinter->societe_id);
  	$numprojet = $societe->has_projects();
  	
  	// Projet associé
    print '<tr><td valign="top">'.$langs->trans("Project").'</td><td>';
  	
  	if (!$numprojet)
  	{
  		print '<table class="nobordernopadding" width="100%">';
			print '<tr><td width="130">'.$langs->trans("NoProject").'</td>';

			$user->getrights("projet");

			if ($user->rights->projet->creer)
			{
				print '<td><a href='.DOL_URL_ROOT.'/projet/fiche.php?socid='.$fichinter->societe_id.'&action=create>'.$langs->trans("Add").'</a></td>';
			}
			print '</tr></table>';
		}
		else
		{
			$html->select_projects($fichinter->societe_id,$fichinter->projet_id,"projetidp");
		}
		print '</td></tr>';
  }

    // Description
    print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
    print '<td>';

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

    print '</td></tr>';

    print '<tr><td colspan="2" align="center">';
    print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
    print '</td></tr>';
    print "</table>\n";
    print '</form>';
    print '</div>';
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
      dolibarr_print_error($db);
      exit;
    }
  $fichinter->fetch_client();

  if ($mesg) print $mesg."<br>";

  $head = fichinter_prepare_head($fichinter);
  
  dolibarr_fiche_head($head, 'card', $langs->trans("InterventionCard"));
  
  
  print '<table class="border" width="100%">';
  
  // Ref
  print '<tr><td>'.$langs->trans("Ref").'</td><td>'.$fichinter->ref.'</td></tr>';
  
  // Societe
  print "<tr><td>".$langs->trans("Company")."</td><td>".$fichinter->client->getNomUrl(1)."</td></tr>";
  
  // Date
  print '<tr><td width="20%">'.$langs->trans("Date").'</td><td>'.dolibarr_print_date($fichinter->date,"daytext").'</td></tr>';

	// Durée
    print '<tr><td>'.$langs->trans("Duration").'</td><td>'.$fichinter->duree.'</td></tr>';

    if ($conf->projet->enabled)
    {
        $fichinter->fetch_projet();
        print '<tr><td valign="top">'.$langs->trans("Project").'</td><td>';
        print '<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$fichinter->projet->id.'" title="'.$langs->trans('ShowProject').'">';
				print $fichinter->projet->title;
				print '</a>';
				print '</td></tr>';
    }
    
    // Statut
    print '<tr><td>'.$langs->trans("Status").'</td><td>'.$fichinter->getLibStatut(4).'</td></tr>';

    // Description
    print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
    print '<td>';
    print nl2br($fichinter->description);
    print '</td></tr>';

    print "</table>";
    print '</div>';


    /**
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';

    if ($user->societe_id == 0)
    {

        if ($fichinter->statut == 0)
        {
            print '<a class="butAction" href="fiche.php?id='.$_GET["id"].'&action=edit">'.$langs->trans("Edit").'</a>';
        }

        if ($fichinter->statut == 0)
        {
            print '<a class="butAction" href="fiche.php?id='.$_GET["id"].'&action=valid">'.$langs->trans("Valid").'</a>';
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
    $genallowed=$user->rights->fichinter->creer;
    $delallowed=$user->rights->fichinter->supprimer;
    $genallowed=1;
    $delallowed=0;

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
