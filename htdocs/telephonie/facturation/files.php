<?PHP
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
 *
 */
require("./pre.inc.php");
require_once DOL_DOCUMENT_ROOT.'/telephonie/fournisseurtel.class.php';

if (!$user->rights->telephonie->facture->ecrire) accessforbidden();

if ($_GET["action"] == 'archive' && $user->rights->telephonie->facture->ecrire)
{
  $srcdir = $conf->telephonie->dir_output."/cdr/atraiter/" ;


  $file = urldecode ($_GET["file"]);

  $destdir = $conf->telephonie->dir_output."/cdr/archive/".dirname($file).'/';

  if (!is_dir($destdir))
    {
      @mkdir($destdir);
    }

  if (is_dir($destdir) && is_file($srcdir.$file))
    {
      rename($srcdir.$file,$destdir.basename($file));
    }

}

$dir = $conf->telephonie->dir_output."/cdr/atraiter/" ;

$files = array();
$fourn_files = array();

$obj = new FournisseurTelephonie($db,$user);
$fourns = $obj->GetActives();

foreach ($fourns as $id => $nom)
{
  $fdir = $dir . $id.'/';
  if (is_dir($fdir))
  {
    $handle=opendir($fdir);
    while (($file = readdir($handle))!==false)
      {
	if (is_file($fdir.'/'.$file))
	  {
	    array_push($files, $id.'/'.$file);
	    $fourn_files[$id.'/'.$file] = $nom;
	  }
      }
    closedir($handle);
  }
}

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}


/*
 * Mode Liste
 */
print_barre_liste("Fichiers CDR a traiter", $page, "files.php", "", $sortfield, $sortorder, '', $num);

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Fournisseur</td>';
print '<td>Fichier</td><td>Date</td>';
print '<td align="right">Taille</td>';
print "<td>&nbsp;</td></tr>\n";

$var=True;

foreach ($files as $file)
{
  $var=!$var;
  
  print "<tr $bc[$var]>";

  print '<td>'.$fourn_files[$file].'</td>';
  
  print '<td>';
  print img_file();      
  print '&nbsp;';
  print basename($file)."</td>\n";
  
  print '<td>'.date("d F Y H:i:s", filemtime($dir.$file)).'</td>';
  print '<td align="right">'.filesize($dir.$file).' octets</td>';
  print '<td align="right"><a href="files.php?action=archive&amp;file='.urlencode($file).'">archiver</a></td>';
}
print "</table>";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
