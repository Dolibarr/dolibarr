<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005      Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
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
        \file       htdocs/product/document.php
        \ingroup    product
        \brief      Page des documents joints sur les produits
        \version    $Revision$
*/

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/cactioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("other");
$langs->load("bills");

if (isset($_GET["error"])) $error=$_GET["error"];
$upload_dir = $conf->actions->dir_output.'/'.$_GET['id'];

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}


/*
 * Action envoie fichier
 */
if ( $_POST["sendit"] && $conf->upload )
{
    /*
     * Creation répertoire si n'existe pas
     */
    if (! is_dir($upload_dir)) create_exdir($upload_dir);

    if (is_dir($upload_dir))
    {
        if (doliMoveFileUpload($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name']))
        {
            $mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
            //print_r($_FILES);
        }
        else
        {
            // Echec transfert (fichier dépassant la limite ?)
            $mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
            // print_r($_FILES);
        }
    }
}

/*
 * Efface fichier
 */
if ($_GET["action"] == 'delete')
{
	$file = $upload_dir . '/' . urldecode($_GET['urlfile']);
	dol_delete_file($file);
}


/*
 * Affiche onglet
 */

llxHeader();


if ($_GET["id"] > 0)
{
	$act = new ActionComm($db);
	$act->fetch($_GET["id"]);
	$res=$act->societe->fetch($act->societe->id);
	$res=$act->author->fetch();     // Le paramètre est le login, hors seul l'id est chargé.
	$res=$act->contact->fetch($act->contact->id);

	$h=0;

	$head[$h][0] = DOL_URL_ROOT.'/comm/action/fiche.php?id='.$_GET["id"];
	$head[$h][1] = $langs->trans("CardAction");
	$hselected=$h;
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/action/document.php?id='.$_GET["id"];
	$head[$h][1] = $langs->trans('Documents');
	$hselected=$h;
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/action/info.php?id='.$_GET["id"];
	$head[$h][1] = $langs->trans('Info');
	$h++;
	
	dolibarr_fiche_head($head, $hselected, $langs->trans("Action"));

	// Affichage fiche action en mode visu
	print '<table class="border" width="100%"';

	// Ref
	print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">'.$act->id.'</td></tr>';

	// Type
	print '<tr><td>'.$langs->trans("Type").'</td><td colspan="3">'.$act->type.'</td></tr>';

	// Libelle
	print '<tr><td>'.$langs->trans("Title").'</td><td colspan="3">'.$act->label.'</td></tr>';

	// Societe - contact
	print '<tr><td>'.$langs->trans("Company").'</td><td>'.$act->societe->getNomUrl(1).'</td>';
	print '<td>'.$langs->trans("Contact").'</td>';
	print '<td>';
	if ($act->contact->id > 0)
	{
		print $act->contact->getNomUrl(1);
	}
	else
	{
		print $langs->trans("None");
	}
	
	print '</td></tr>';
	
	// Construit liste des fichiers
	clearstatcache();

	$totalsize=0;
	$filearray=array();

	if (is_dir($upload_dir))
	{
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
			print '<div class="error">'.$langs->trans("ErrorCanNotReadDir",$upload_dir).'</div>';
		}
	}
	
	print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
	print '</table>';

	print '</div>';

	if ($mesg) { print $mesg."<br>"; }

	// Affiche formulaire upload
	$html=new Form($db);
	$html->form_attach_new_file('document.php?id='.$act->id);
	
	// Affiche liste des documents existant
	print_titre($langs->trans("AttachedFiles"));

	print '<table width="100%" class="noborder">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Document').'</td>';
	print '<td align="right">'.$langs->trans('Size').'</td>';
	print '<td align="center">'.$langs->trans('Date').'</td>';
	print '<td>&nbsp;</td>';
	print '</tr>';

	if (is_dir($upload_dir))
	{
		$errorlevel=error_reporting();
		$handle=opendir($upload_dir);
		if ($handle)
		{
			$var=true;
			while (($file = readdir($handle))!==false)
			{
				if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
				{
					$var=!$var;
					print '<tr '.$bc[$var].'>';
					print '<td>';
					echo '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=actions&file='.$act->id.'/'.urlencode($file).'">'.$file.'</a>';
					print "</td>\n";
					print '<td align="right">'.filesize($upload_dir.'/'.$file). ' bytes</td>';
					print '<td align="center">'.dolibarr_print_date(filemtime($upload_dir.'/'.$file),'dayhour').'</td>';
					print '<td align="center">';
					print '<a href="'.DOL_URL_ROOT.'/comm/action/document.php?id='.$act->id.'&action=delete&urlfile='.urlencode($file).'">'.img_delete($langs->trans('Delete')).'</a>';
					print "</td></tr>\n";
				}
			}
			closedir($handle);
		}
		else
		{
			print '<div class="error">'.$langs->trans('ErrorCantOpenDir').'<b> '.$upload_dir.'</b></div>';
		}	  
	}
	print '</table>';
}
else
{
	print $langs->trans("UnkownError");
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
