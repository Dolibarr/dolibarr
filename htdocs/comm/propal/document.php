<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*! \file htdocs/comm/propal/document.php
        \ingroup    propale
		\brief      Page de gestion des documents attachées à une proposition commerciale
		\version    $Revision$
*/

require("./pre.inc.php");

$user->getrights('propale');
if (!$user->rights->propale->lire)
  accessforbidden();


require_once("../../propal.class.php");
llxHeader();

function do_upload ($upload_dir)
{
  global $local_file, $error_msg;

  if (! is_dir($upload_dir))
    {
      umask(0);
      mkdir($upload_dir, 0755);
    }

  if (doliMoveFileUpload($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name']))
    {
      print "Le fichier est valide, et a &eacute;t&eacute; t&eacute;l&eacute;charg&eacute; avec succ&egrave;s.\n";
      //print_r($_FILES);
    }
  else
    {
      echo "Le fichier n'a pas été téléchargé";
      // print_r($_FILES);
    }

}

if ($id > 0)
{
  $propal = new Propal($db);
  if ( $propal->fetch($id) )
    {

      $upload_dir = PROPALE_OUTPUTDIR . "/" . $propal->ref ;

      if ( $error_msg )
	{ 
	  echo "<B>$error_msg</B><BR><BR>";
	}

      if ($action=='delete')
	{
	  $file = $upload_dir . "/" . urldecode($urlfile);
	  dol_delete_file($file);
	}
      
      if ( $_POST["sendit"] )
	{
	  do_upload ($upload_dir);
	}
            
      /*
       *
       */
      print "<table width=\"100%\" border=\"0\" cellspacing=\"1\">\n";
      
      print "<tr><td><div class=\"titre\">Documents associés à la proposition : ".$propal->ref_url."</div></td>";
      print "</tr></table>";

      echo '<FORM NAME="userfile" ACTION="document.php?id='.$propal->id.'" ENCTYPE="multipart/form-data" METHOD="POST">';
      print '<input type="hidden" name="max_file_size" value="2000000">';
      print '<input type="file"   name="userfile" size="40" maxlength="80"><br>';
      print '<input type="submit" value="Upload File!" name="sendit">';
      print '<input type="submit" value="'.$langs->trans("Cancel").'" name="cancelit"><BR>';
      print '</FORM>';

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
		  echo '<a target="_blank" href="'.DOL_URL_ROOT.'/document/propale/'.$propal->ref.'/'.$file.'">'.$file.'</a>';
		  print "</td>\n";
		  
		  print '<td align="right">'.filesize($upload_dir."/".$file). ' bytes</td>';
		  print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($upload_dir."/".$file)).'</td>';
		  
		  print '<td align="center">';
		  if ($file == $propal->ref . '.pdf')
		    {
		      echo '-';
		    }
		  else
		    {
		      echo '<a href="document.php?id='.$propal->id.'&action=delete&urlfile='.urlencode($file).'">Supprimer</a>';
		    }
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
