<?PHP
/* Copyright (C) 2000-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

require("pre.inc.php");

/*
 * Sécurité accés client
 */

$socid = $_GET["id"];

/*
 *
 *
 */
llxHeader();
$form = new Form($db);

$soc = new LolixSociete($db);
$soc->fetch($_GET["id"]);

fiche_header($soc->id);

/*
 *
 */

if ($_GET["action"] == 'edit')
{
  print_titre("Edition de la société");

  if ($socid)
    {
      $soc = new Societe($db);
      $soc->id = $socid;
      $soc->fetch($socid);

      print '<form action="fiche.php?id='.$socid.'" method="post">';
      print '<input type="hidden" name="action" value="update">';

      print '<table class="border" width="100%" cellpadding="3" cellspacing="0">';
      print '<tr><td>Nom</td><td colspan="3"><input type="text" size="40" name="nom" value="'.$soc->nom.'"></td></tr>';
      print '<tr><td valign="top">Adresse</td><td><textarea name="adresse" cols="30" rows="3" wrap="soft">';
      print $soc->adresse;
      print '</textarea></td>';
      
      print '<td>CP</td><td><input size="6" type="text" name="cp" value="'.$soc->cp.'">&nbsp;';
      print 'Ville&nbsp;<input type="text" name="ville" value="'.$soc->ville.'"></td></tr>';
      print '<tr><td>Département</td><td>';
      print $form->select_departement($soc->departement_id);
      print '</td>';      

      print '<td>Pays</td><td>';
      print $form->select_pays($soc->pays_id);
      print '</td></tr>';

      print '<tr><td>Téléphone</td><td><input type="text" name="tel" value="'.$soc->tel.'"></td>';
      print '<td>Fax</td><td><input type="text" name="fax" value="'.$soc->fax.'"></td></tr>';
      print '<tr><td>Web</td><td colspan="3">http://<input type="text" name="url" size="40" value="'.$soc->url.'"></td></tr>';
      
      print '<tr><td>Siren</td><td><input type="text" name="siren" size="10" maxlength="9" value="'.$soc->siren.'"></td>';
      print '<td>Siret</td><td><input type="text" name="siret" size="15" maxlength="14" value="'.$soc->siret.'"></td></tr>';

      print '<tr><td>Ape</td><td><input type="text" name="ape" size="5" maxlength="4" value="'.$soc->ape.'"></td>';
      print '<td>Capital</td><td><input type="text" name="capital" size="10" value="'.$soc->capital.'"> '.MAIN_MONNAIE.'</td></tr>';


      print '<tr><td>Forme juridique</td><td colspan="3">';
      $html = new Form($db);
      print $html->select_array("forme_juridique_id",$soc->forme_juridique_array(), $soc->forme_juridique_id,0,1);
      print '</td></tr>';

      print '<tr><td>Effectif</td><td colspan="3">';
      print $html->select_array("effectif_id",$soc->effectif_array(), $soc->effectif_id);
      print '</td></tr>';

      print '<input type="hidden" name="tva_intra_code" value="'.$soc->tva_intra_code.'">';
      print '<input type="hidden" name="tva_intra_num" value="'.$soc->tva_intra_num.'">';

      print '<input type="hidden" name="client" value="'.$soc->client.'">';
      print '<input type="hidden" name="fournisseur" value="'.$soc->fournisseur.'">';
      print '<tr><td align="center" colspan="4"><input type="submit" value="Mettre à jour"></td></tr>';
      print '</table>';
      print '</form>';

    }
}
else
{
  
  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr><td width="20%">Nom</td><td width="30%">'.$soc->nom.'</td>';
  print '<td>Mise à jour</td><td>'.strftime("%d %B %Y %H:%M",$socdet->date_fiche_update).' ';
  print '</td></tr>';


  print '<tr><td valign="top">Adresse</td><td colspan="3">'.nl2br($soc->adresse).'&nbsp;';
  print '<br>'.$soc->cp.'&nbsp;'.$soc->ville.'<br>'.$soc->pays.'</td></tr>';


  print '<tr><td>Téléphone</td><td>'.dolibarr_print_phone($soc->tel).'</td>';
  print '<td>Fax</td><td>'.dolibarr_print_phone($soc->fax).'</td></tr>';
  print '<tr><td>Web</td><td>';
  if ($soc->url) { print '<a href="http://'.$soc->url.'">http://'.$soc->url.'</a>'; }
  print '</td>';

  print '<td>Siren</td><td><a target="_blank" href="http://www.societe.com/cgi-bin/recherche?rncs='.$soc->siren.'">'.$soc->siren.'</a>&nbsp;</td></tr>';
  
  print '<tr><td>Forme juridique</td><td colspan="3">'.$soc->forme_juridique.'</td></tr>';
  print '<tr><td>Effectif</td><td>'.$soc->effectif.'</td>';
  print '<td>Création</td><td>'.strftime("%d %B %Y",$soc->date_creation).'</td></tr>';
  
  print '<tr><td>Contact</td><td>'.$socdet->contact_nom.' '.$socdet->contact_email.'</td>';
  print '<td>&nbsp;</td><td>&nbsp;</td></tr>';
  
  $file = DOL_DOCUMENT_ROOT . "/document/sl/catalogue-".$soc->id.".pdf";
  
  print '<tr><td><a href="fiche.php?action=pdf&amp;id='.$soc->id.'">'.img_file_new().'</a></td>';
  
  if (file_exists($file))
    {
      print '<td><a href="'.DOL_URL_ROOT.'/document/sl/catalogue-'.$soc->id.'.pdf">'.img_pdf().'</a></td>';
      print '<td>'.filesize($file). ' bytes</td>';
      print '<td>'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td>';
    }
  print '</tr>';
/*
 *
 */


print '</table>';
print '<br></div>';
/*
 *
 */  

}

print '<div class="tabsAction">';

if ($soc->active == 0)
{
  print '<a class="tabAction" href="fiche.php?id='.$_GET["id"].'&action=activer">Activer</a>';
}



print '</div>';
/*
 *
 */


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
