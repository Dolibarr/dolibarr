<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005      Regis Houssin         <regis@dolibarr.fr>
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
   \file       htdocs/commande/document.php
   \ingroup    facture
   \brief      Page de gestion des documents attachées à une facture
   \version    $Revision$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/lib/order.lib.php');

$user->getrights('commande');
if (!$user->rights->commande->lire)
  accessforbidden();

$langs->load('companies');
//$langs->load("bills");
$langs->load('other');

$id=empty($_GET['id']) ? 0 : intVal($_GET['id']);
$action=empty($_GET['action']) ? (empty($_POST['action']) ? '' : $_POST['action']) : $_GET['action'];

// Securite acces
if ($user->societe_id > 0) 
{
  unset($_GET["action"]); 
  $socid = $user->societe_id;
}

$commande = new Commande($db);
if (!$commande->fetch($id)) {
  dolibarr_print_error($db);
}

/*
 * Actions
 */
 
// Envoi fichier
if ($_POST["sendit"] && $conf->upload)
{
  $upload_dir = $conf->commande->dir_output . "/" . $commande->ref;
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

// Delete
if ($action=='delete')
{
  $upload_dir = $conf->commande->dir_output . "/" . $commande->ref;
  $file = $upload_dir . '/' . urldecode($_GET['urlfile']);
  dol_delete_file($file);
  $mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
}


/*
 * Affichage
 */
if ($id > 0)
{
  llxHeader();

  $commanderef = sanitize_string($commande->ref);

  $upload_dir = $conf->commande->dir_output.'/'.$commanderef;
  
  $societe = new Societe($db);
  $societe->fetch($commande->socid);
  
  $head = commande_prepare_head($commande);
  dolibarr_fiche_head($head, 'documents', $langs->trans('CustomerOrder'));
  
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
	  if (! is_dir($dir.$file)
	      && ! eregi('^\.',$file)
	      && ! eregi('^CVS',$file)
	      && ! eregi('\.meta$',$file))
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
  print '<tr><td width="30%">'.$langs->trans('Ref').'</td><td colspan="3">'.$commande->ref.'</td></tr>';
  
  print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">'.$societe->getNomUrl(1).'</td></tr>';  
  print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';
  print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';  
  print "</table>\n";  
  print "</div>\n";

  if ($mesg) { print $mesg."<br>"; }

  // Affiche formulaire upload
  $html=new Form($db);
  $html->form_attach_new_file('document.php?id='.$commande->id);
  
  // Affiche liste des documents existant
  print_titre($langs->trans("AttachedFiles"));
  
  print '<table width="100%" class="noborder">';
  print '<tr class="liste_titre">';
  print '<td>'.$langs->trans("Document").'</td>';
  print '<td align="right">'.$langs->trans("Size").'</td>';
  print '<td align="center">'.$langs->trans("Date").'</td>';
  print '<td>&nbsp;</td>';
  print '</tr>';
  
  if (is_dir($upload_dir))
    {
      $handle=opendir($upload_dir);
      if ($handle)
	{
	  $var=true;
	  while (($file = readdir($handle))!==false)
	    {
	      if (! is_dir($dir.$file)
		  && ! eregi('^\.',$file)
		  && ! eregi('^CVS',$file)
		  && ! eregi('\.meta$',$file))
		{
		  $var=!$var;
		  print '<tr '.$bc[$var].'>';
		  print '<td>';
		  echo '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=commande&file='.$commanderef.'/'.urlencode($file).'">'.$file.'</a>';
		  print "</td>\n";
		  print '<td align="right">'.filesize($upload_dir.'/'.$file). ' '.$langs->trans("bytes").'</td>';
		  print '<td align="center">'.dolibarr_print_date(filemtime($upload_dir.'/'.$file),'dayhour').'</td>';
		  print '<td align="center">';
		  if ($file == $facref . '.pdf')
		    {
		      echo '-';
		    }
		  else
		    {
		      echo '<a href="'.DOL_URL_ROOT.'/commande/document.php?id='.$commande->id.'&action=delete&urlfile='.urlencode($file).'">'.img_delete($langs->trans('Delete')).'</a>';
		    }
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
  Header('Location: index.php');
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
