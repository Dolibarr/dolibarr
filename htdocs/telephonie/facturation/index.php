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
require_once DOL_DOCUMENT_ROOT.'/telephonie/facturation/FacturationImportCdr.class.php';
require_once DOL_DOCUMENT_ROOT.'/telephonie/facturation/FacturationEmission.class.php';
require_once DOL_DOCUMENT_ROOT.'/telephonie/fournisseurtel.class.php';

if (!$user->rights->telephonie->facture->ecrire) accessforbidden();

if ( $_POST["sendit"] && $conf->upload != 0)
{
  if ($_POST['fournisseur'] > 0)
    {

      $upload_dir = DOL_DATA_ROOT."/telephonie/cdr/atraiter/".$_POST['fournisseur'];
      
      if (is_dir($upload_dir))
	{
	  if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name'],1))
	    {
	      $mesg = "Le fichier est valide, et a &eacute;t&eacute; t&eacute;l&eacute;charg&eacute; avec succ&egrave;s.\n";
	    }
	  else
	    {
	      $mesg = "Le fichier n'a pas été téléchargé";
	    }
	  
	}
    }
}

llxHeader('','Telephonie - Facturation');

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

/*
 *
 */
$fourn = new FournisseurTelephonie($db);
$fourns = $fourn->getActives();

clearstatcache();

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr><td width="50%" valign="top">';
$var = true;
print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td colspan="3">Facturation</td></tr>';
foreach ($fourns as $key => $value)
{
  $dir = $conf->telephonie->dir_output."/cdr/atraiter/$key" ;
  if (is_dir($dir))
    {
      $handle=opendir($dir);
      $files = array();
      
      while (($file = readdir($handle))!==false)
	{
	  if (is_file($dir.'/'.$file))
	    array_push($files, $file);
	}
      closedir($handle);
      $nb = sizeof($files);
    }
  else
    {
      $nb = 'Rep Inexistant';
    }

  print "<tr $bc[$var]>";
  print "<td>Fichiers a traiter pour $value</td>";
  print '<td align="right"><a title="Nombre de fichiers a importer" href="files.php">'.$nb.'</a></td><td align="center">';
  if (sizeof($files) > 0)
    {
      print '<a href="cdr-import.php?id='.$key.'">Importer</a>';
    }
  print '&nbsp;</td></tr>';
  $var =!$var;
}

$obj = new FacturationImportCdr($db);
$nbcdr = $obj->CountDataImport();

print "<tr $bc[$var]>";
print '<td>Donnees a traiter</td><td align="right"><a href="cdr.php">'.$nbcdr.'</a></td>';
print '<td align="center"><a href="calcul.php">Traiter</a></td></tr>';

$obj = new FacturationEmission($db,$user);
$act = $obj->NbFactureToGenerate();
$nb = $obj->nbfac;
$var =!$var;
print "<tr $bc[$var]>";
print '<td>Lignes de facture</td><td align="right"><a href="facture.php">'.$nb.'</a></td>';
print '<td align="center"><a href="emission.php">Emettre</a></td></tr>';
print '</table><br />';

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td colspan="2">Statistiques</td></tr>';
$var =!$var;
print "<tr $bc[$var]>";
print '<td>Generation</td><td align="right"><a href="stats.php">Generer</a></td></tr>';
print '</table>';


print '</td><td valign="top" width="50%" rowspan="3">';

print '<form name="userfile" action="index.php" enctype="multipart/form-data" METHOD="POST">';      
print '<input type="hidden" name="max_file_size" value="'.$conf->maxfilesize.'">';

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Charger un fichier CDR</td></tr>';

print "<tr $bc[$var]><td>";

$form = new Form($db);
$form->select_array("fournisseur",$fourns);

print '<input type="file"   name="userfile" size="40" maxlength="80"><br />';
print '<input type="submit" value="'.$langs->trans("Upload").'" name="sendit"> &nbsp; ';
print '<input type="submit" value="'.$langs->trans("Cancel").'" name="cancelit"><br>';

print "</tr>\n";
print '</table></form><br />';

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td colspan="2">Autres actions</td></tr>';

if ($nbcdr > 0)
{
  print "<tr $bc[$var]>";
  print '<td>Vider la table des CDR a traiter</td><td align="right"><a href="cdr.php?action=empty_request">Vider</a></td></tr>';
}
print '</table>';

print '</td></tr>';


print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
