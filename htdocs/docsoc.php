<?PHP
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./pre.inc.php");

llxHeader();

$mesg = "";

$upload_dir = SOCIETE_OUTPUTDIR . "/" . $socid ;

if (! is_dir($upload_dir))
{
  umask(0);
  if (! mkdir($upload_dir, 0755))
    {
      print "Impossible de créer $upload_dir";
    }
}

if ( $sendit && defined('MAIN_UPLOAD_DOC') && MAIN_UPLOAD_DOC == 1)
{
  if (is_dir($upload_dir))
    {
      if (doliMoveFileUpload($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name']))
	{
	  $mesg = "Le fichier est valide, et a &eacute;t&eacute; t&eacute;l&eacute;charg&eacute; avec succ&egrave;s.\n";
	  //print_r($_FILES);
	}
      else
	{
	  $mesg = "Le fichier n'a pas été téléchargé";
	  // print_r($_FILES);
	}
      
    }
}

if ( $error_msg )
{ 
  echo "<B>$error_msg</B><BR><BR>";
}
if ($action=='delete')
{
  $file = $upload_dir . "/" . urldecode($urlfile);
  dol_delete_file($file);
  $mesg = "Le fichier a été supprimé";
}

/*
 *
 * Mode fiche
 *
 *
 */  
if ($socid > 0)
{
  $societe = new Societe($db);
  if ($societe->fetch($socid))
    {

      $head[0][0] = DOL_URL_ROOT.'/soc.php?socid='.$_GET["socid"];
      $head[0][1] = "Fiche société";
      $h = 1;

      if ($societe->client==1)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$societe->id;
	  $head[$h][1] = 'Client';
	  $h++;
	}
      
      if ($societe->client==2)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$societe->id;
	  $head[$h][1] = 'Prospect';
	  $h++;
	}

      if ($societe->fournisseur)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$societe->id;
	  $head[$h][1] = 'Fournisseur';
	  $h++;
	}

      if ($conf->compta->enabled) {
          $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$societe->id;
          $head[$h][1] = 'Comptabilité';
          $h++;
      }

      $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$societe->id;
      $head[$h][1] = 'Note';      
      $h++;

      if ($user->societe_id == 0)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$societe->id;
	  $head[$h][1] = 'Documents';
	  $a = $h;
	  $h++;
	}
      
      $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$societe->id;
      $head[$h][1] = 'Notifications';
      
      dolibarr_fiche_head($head, $a);
      /*
       *
       */
      print_titre("Documents associés à l'entreprise : $societe->nom");
      /*
       *
       *
       */
      if (defined('MAIN_UPLOAD_DOC') && MAIN_UPLOAD_DOC == 1)
	{
	  echo '<FORM NAME="userfile" ACTION="docsoc.php?socid='.$socid.'" ENCTYPE="multipart/form-data" METHOD="POST">';      
	  print '<input type="hidden" name="max_file_size" value="2000000">';
	  print '<input type="file"   name="userfile" size="40" maxlength="80">';
	  print '<BR>';
	  print '<input type="submit" value="Upload File!" name="sendit">';
	  print '<input type="submit" value="Cancel" name="cancelit"><BR>';
	  print '</FORM>';
	}
      else
	{
	  print "La gestion des fichiers associés est désactivée sur ce serveur";
	}
      print '</div>';

      print $mesg;

      clearstatcache();

      $handle=opendir($upload_dir);

      if ($handle)
	{
	  print '<table width="100%" border="1" cellpadding="3" cellspacing="0">';
	  while (($file = readdir($handle))!==false)
	    {
	      if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
		{
		  print '<tr><td>';
		  echo '<a href="'.DOL_URL_ROOT.'/document/societe/'.$socid.'/'.$file.'">'.$file.'</a>';
		  print "</td>\n";
		  
		  print '<td align="right">'.filesize($upload_dir."/".$file). ' bytes</td>';
		  print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($upload_dir."/".$file)).'</td>';
		  
		  print '<td>';
		  echo '<a href="docsoc.php?socid='.$socid.'&action=delete&urlfile='.urlencode($file).'">Delete</a>';
		  print "</td></tr>\n";
		}
	    }

	  print "</table>";

	  closedir($handle);
	}
      else
	{
	  print "<p>Impossible d'ouvrir : <b>".$upload_dir."</b>";
	}
    }
  else
    {
      print $db->error() . "<br>" . $sql;
    }
}
else
{
  print "Erreur";
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
