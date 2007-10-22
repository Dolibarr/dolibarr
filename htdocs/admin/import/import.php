<?php
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
   \file       htdocs/admin/index.php
   \brief      Page d'import de donnee
   \version    $Revision$
*/

require("./pre.inc.php");

require(DOL_DOCUMENT_ROOT.'/admin/import/dolibarrimport.class.php');

$langs->load("admin");
$langs->load("companies");

if (!$user->admin)
  accessforbidden();

/*
 * Affichage page
 */
llxHeader();

$form = new Form($db);

print_fiche_titre($langs->trans("ImportArea"),'','setup');

print "<br>";

print '<form name="userfile" action="index.php" enctype="multipart/form-data" METHOD="POST">';      
print '<input type="hidden" name="max_file_size" value="'.$conf->maxfilesize.'">';

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Importer un fichier clients</td></tr>';

print "<tr $bc[1]><td>";
print '<input type="file"   name="userfile" size="20" maxlength="80"><br />';
print '<input type="submit" value="'.$langs->trans("Upload").'" name="sendit"> &nbsp; ';
print '<input type="submit" value="'.$langs->trans("Cancel").'" name="cancelit"><br>';

print "</tr>\n";
print '</table></form>';


if ( $_POST["sendit"] && $conf->upload != 0)
{
  $imp = new DolibarrImport($db);
  $imp->CreateBackupDir();  
  if (doliMoveFileUpload($_FILES['userfile']['tmp_name'], $imp->upload_dir . "/" . $_FILES['userfile']['name']))
    {
      
      $imp->ImportClients($imp->upload_dir . "/" . $_FILES['userfile']['name']);
      
      print "Imports : ".$imp->nb_import."<br>";
      print "Imports corrects : ".$imp->nb_import_ok."<br>";
      print "Imports erreurs : ".$imp->nb_import_ko."<br>";
      
    }
  else
    {
      $mesg = "Le fichier n'a pas été téléchargé";
    }
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
