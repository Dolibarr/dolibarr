<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *  \file       htdocs/societe/document.php
 *  \brief      Tab for documents linked to third party
 *  \ingroup    societe
 *  \version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");

$langs->load("companies");
$langs->load('other');

$mesg = "";

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:(! empty($_GET["id"])?$_GET["id"]:'');
if ($user->societe_id > 0) 
{
	unset($_GET["action"]);
	$action=''; 
	$socid = $user->societe_id;
}
$result = restrictedArea($user, 'societe', $socid);

// Get parameters
$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";

$upload_dir = $conf->societe->dir_output . "/" . $socid ;
$courrier_dir = $conf->societe->dir_output . "/courrier/" . get_exdir($socid) ;


/*
 * Actions
 */

// Envoie fichier
if ( $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
  if (! is_dir($upload_dir)) create_exdir($upload_dir);
  
  if (is_dir($upload_dir))
  {
  	$result = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name'],0);
  	if ($result > 0)
    {
    	$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
    	//print_r($_FILES);
    }
    else if ($result < 0)
    {
    	// Echec transfert (fichier depassant la limite ?)
    	$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
    	// print_r($_FILES);
    }
    else
    {
    	// Fichier infecte par un virus
    	$mesg = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWith",$result).'</div>';
    }
  }
}

// Suppression fichier
if ($_POST['action'] == 'confirm_deletefile' && $_POST['confirm'] == 'yes')
{
  $file = $upload_dir . "/" . urldecode($_GET["urlfile"]);
  dol_delete_file($file);
  $mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
}


/*
* View
*/

llxHeader();

if ($socid > 0)
{
	$societe = new Societe($db);
	if ($societe->fetch($socid))
	{
		/*
		* Affichage onglets
		*/
		$head = societe_prepare_head($societe);
		
		$html=new Form($db);
		
		dol_fiche_head($head, 'document', $langs->trans("ThirdParty"));
		

		// Construit liste des fichiers
		$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
		$totalsize=0;
		foreach($filearray as $key => $file)
		{
			$totalsize+=$file['size'];
		}
		  
		
		print '<table class="border"width="100%">';
		
		// Ref
		print '<tr><td width="30%">'.$langs->trans("Name").'</td><td colspan="3">'.$societe->nom.'</td></tr>';

		// Prefix
		print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$societe->prefix_comm.'</td></tr>';
		
		// Nbre fichiers
		print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';
		
		//Total taille
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
		
		print '</table>';
		
		print '</div>';
		
		if ($mesg) { print "$mesg<br>"; }
		
		  /*
		   * Confirmation de la suppression d'une ligne produit
		   */
		  if ($_GET['action'] == 'delete')
		  {
		    $html->form_confirm($_SERVER["PHP_SELF"].'?socid='.$_GET["id"].'&amp;urlfile='.urldecode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile');
		    print '<br>';
		  }
		
		
		// Affiche formulaire upload
       	$formfile=new FormFile($db);
		$formfile->form_attach_new_file(DOL_URL_ROOT.'/societe/document.php?socid='.$socid);
		

		// List of document
		$param='&socid='.$societe->id;
		$formfile->list_of_documents($filearray,$societe,'societe',$param);
		
		
		print "<br><br>";
		
		// Courriers
		// Les courriers sont des documents speciaux generes par des scripts
		// situes dans scripts/courrier.
		// Voir Rodo
		if ($conf->global->MAIN_MODULE_EDITEUR)
		{
			$filearray=array();	
			$errorlevel=error_reporting();
			error_reporting(0);
			$handle=opendir($courrier_dir);
			error_reporting($errorlevel);
			if ($handle)
			{
				$i=0;
				while (($file = readdir($handle))!==false)
				{
					if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
					{
						$filearray[$i]=$file;
						$i++;
					}
				}
				closedir($handle);
			}       	       

			print '<table width="100%" class="noborder">';
			print '<tr class="liste_titre"><td>'.$langs->trans("Courriers").'</td><td align="right">'.$langs->trans("Size").'</td><td align="center">'.$langs->trans("Date").'</td></tr>';

			$var=true;
			foreach($filearray as $key => $file)
			{
				if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
				{
					$var=!$var;
					print "<tr $bc[$var]><td>";
					$loc = "courrier/".get_exdir($socid);
					echo '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=societe&type=application/binary&file='.urlencode($loc.'/'.$file).'">'.$file.'</a>';
					print "</td>\n";

					print '<td align="right">'.filesize($courrier_dir."/".$file). ' '.$langs->trans("bytes").'</td>';
					print '<td align="center">'.dol_print_date(filemtime($courrier_dir."/".$file),"dayhour").'</td>';
					print "</tr>\n";
				}
			}
			print "</table>";
		}
	}
	else
	{
		dol_print_error($db);
	}
}
else
{
	dol_print_error();
}

$db->close();


llxFooter('$Date$ - $Revision$');

?>
