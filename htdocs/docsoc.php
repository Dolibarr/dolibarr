<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@cap-networks.com>
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
   \file       htdocs/docsoc.php
   \brief      Fichier onglet documents liés à la société
   \ingroup    societe
   \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

$langs->load("companies");
$langs->load('other');

$mesg = "";

$socid = isset($_GET["socid"])?$_GET["socid"]:'';

// Sécurité d'accès client et commerciaux
$socid = restrictedArea($user, 'societe', $socid);

/*
 * Actions
 */
$upload_dir = $conf->societe->dir_output . "/" . $socid ;
$courrier_dir = $conf->societe->dir_output . "/courrier/" . get_exdir($socid) ;

// Envoie fichier
if ( $_POST["sendit"] && $conf->upload != 0)
{
  if (! is_dir($upload_dir)) create_exdir($upload_dir);
  
  if (is_dir($upload_dir))
  {
  	$result = doliMoveFileUpload($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name']);
  	if ($result == true)
    {
    	$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
    	//print_r($_FILES);
    }
    else if ($result == false)
    {
    	// Echec transfert (fichier dépassant la limite ?)
    	$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
    	// print_r($_FILES);
    }
    else
    {
    	$mesg = '<div class="error">'.$langs->trans("FileIsInfectedWith",$result).'</div>';
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
 * Affichage liste
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
		
		dolibarr_fiche_head($head, 'document', $societe->nom);
		
		/*
	   * Confirmation de la suppression d'une ligne produit
	   */
	  if ($_GET['action'] == 'delete_file')
	  {
	    $html->form_confirm($_SERVER["PHP_SELF"].'?socid='.$socid.'&amp;urlfile='.urldecode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile');
	    print '<br>';
	  }
		
		// Construit liste des fichiers
		clearstatcache();
		
		$totalsize=0;
		$filearray=array();
		
		$errorlevel=error_reporting();
		error_reporting(0);
		$handle=opendir($upload_dir);
		error_reporting($errorlevel);
		if ($handle)
		{
			$i=0;
			while (($file = readdir($handle))!==false)
			{
				if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
				{
					$filearray[$i]=$file;
					$totalsize+=filesize($upload_dir."/".$file);
					$i++;
				}
			}
			closedir($handle);
		}
		else
		{
			//            print '<div class="error">'.$langs->trans("ErrorCanNotReadDir",$upload_dir).'</div>';
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
		
		// Affiche formulaire upload
		$html->form_attach_new_file('docsoc.php?socid='.$socid);
		
		// Affiche liste des documents existant
		print_titre($langs->trans("AttachedFiles"));

		print '<table width="100%" class="noborder">';
		print '<tr class="liste_titre"><td>'.$langs->trans("Document").'</td><td align="right">'.$langs->trans("Size").'</td><td align="center">'.$langs->trans("Date").'</td><td>&nbsp;</td></tr>';

		$var=true;
		foreach($filearray as $key => $file)
		{
			if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
			{
				$var=!$var;
				print "<tr $bc[$var]><td>";
				echo '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=societe&type=application/binary&file='.urlencode($socid.'/'.$file).'">'.$file.'</a>';
				print "</td>\n";
				print '<td align="right">'.filesize($upload_dir."/".$file). ' '.$langs->trans("bytes").'</td>';
				print '<td align="center">'.dolibarr_print_date(filemtime($upload_dir."/".$file),"dayhour").'</td>';
				print '<td align="center">';
				echo '<a href="docsoc.php?socid='.$socid.'&amp;action=delete_file&urlfile='.urlencode($file).'">'.img_delete().'</a>';
				print "</td></tr>\n";
			}
		}
		print "</table><br><br>";

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
					print '<td align="center">'.dolibarr_print_date(filemtime($courrier_dir."/".$file),"dayhour").'</td>';
					print "</tr>\n";
				}
			}
			print "</table>";
		}
	}
	else
	{
		dolibarr_print_error($db);
	}
}
else
{
	dolibarr_print_error();
}

$db->close();


llxFooter('$Date$ - $Revision$');

?>
