<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

if (!$user->rights->telephonie->lire) accessforbidden();

$upload_dir = DOL_DATA_ROOT."/telephonie/ligne/commande/retour";

if (! is_dir($upload_dir))
{
  umask(0);
  if (! mkdir($upload_dir, 0755))
    {
      print "Impossible de créer $upload_dir";
    }
}

if ( $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
  if (is_dir($upload_dir))
    {

      $nextname = $upload_dir .'/backup';


      if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name'],1) > 0)
	{
	  $mesg = "Le fichier est valide, et a &eacute;t&eacute; t&eacute;l&eacute;charg&eacute; avec succ&egrave;s.\n";
	}
      else
	{
	  $mesg = "Le fichier n'a pas été téléchargé";
	}
      
    }
}

llxHeader('','Telephonie - Ligne - Commande - Retour');

/*
 *
 *
 *
 */
print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td width="30%" valign="top">';

$sql = "SELECT distinct statut, count(*) as cc";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " GROUP BY statut";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  $ligne = new LigneTel($db);
  print_titre("Retour Commandes");

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Lignes Statuts</td><td valign="center">Nb</td>';
  print "</tr>\n";
  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object();	
      $var=!$var;

      print "<tr $bc[$var]>";
      print "<td>".$ligne->statuts[$obj->statut]."</td>\n";
      print "<td>".$obj->cc."</td>\n";
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}

print '<br>';

print '<form name="userfile" action="index.php" enctype="multipart/form-data" METHOD="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';    
print '<input type="hidden" name="max_file_size" value="'.$conf->maxfilesize.'">';

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Charger un fichier de retour</td></tr>';

print "<tr $bc[1]><td>";
print '<input type="file"   name="userfile" size="20" maxlength="80"><br />';
print '<input type="submit" value="'.$langs->trans("Upload").'" name="sendit"> &nbsp; ';
print '<input type="submit" value="'.$langs->trans("Cancel").'" name="cancelit"><br>';

print "</tr>\n";
print '</table></form>';
print '</td><td valign="top">';

/*
 * Seconde colonne
 *
 */

$sql = "SELECT ";
$sql .= " cli,mode,situation,date_mise_service,date_resiliation,motif_resiliation,commentaire,fichier, traite ";

$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commande_retour";
$sql .= " WHERE traite = 0 AND mode = 'PRESELECTION'";
$sql .= " LIMIT 10";

if ($db->query($sql))
{
  $num = $db->num_rows();

  if ($num)
    {

      $i = 0;

      print_titre("Retour");
      
      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr class="liste_titre"><td>Lignes Statuts</td><td align="center">Resultat</td>';
      print '<td align="center">Date</td><td>Commentaire</td>';
      print "</tr>\n";
      $var=True;
      
      while ($i < $num)
	{
	  $obj = $db->fetch_object();
	  $var=!$var;
	  
	  $ligne = new LigneTel($db);
	  
	  if ( $ligne->fetch($obj->cli) == 1);
	  {
	    print "<tr $bc[$var]><td>";
	    print '<img src="'.DOL_URL_ROOT.'/telephonie/ligne/graph'.$ligne->statut.'.png">&nbsp;';
	    print '<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?numero='.$obj->cli.'">';
	    print $obj->cli."</a></td>\n";
	    print '<td align="center">'.$obj->situation."</td>\n";
	    print '<td align="center">'.$obj->date_mise_service."</td>\n";
	    print '<td>'.$obj->commentaire."</td>\n";
	    print "</tr>\n";
	  }
	  $i++;
	}
      print "</table><br />";
    }
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}

print_titre("Fichiers retour en attente de traitement");

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Fichier</td><td>Taille</td><td>Date</td>';
print "</tr>\n";
$var=True;

$upload_dir = $upload_dir."/";

$handle=opendir($upload_dir);

while (($file = readdir($handle))!==false)
{
  if (is_readable($upload_dir.$file) && is_file($upload_dir.$file))
    {
      $var=!$var;
      
      print "<tr $bc[$var]>";
      print '<td><a href="'.DOL_URL_ROOT.'/document.php?file='.$upload_dir.$file.'&amp;type=text/plain">';
      print $file.'</a></td>';                       

      print '<td>'.filesize($upload_dir.$file). ' bytes</td>';
      print '<td>'.strftime("%A %d %b %Y %H:%M:%S",filemtime($upload_dir.$file)).'</td>';

      print '</tr>';
    }
}

print "</table>";

print '</td></tr>';
print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
