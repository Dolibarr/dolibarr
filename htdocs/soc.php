<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Brian Fraval         <brian@fraval.org>
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
 *
 */

require("pre.inc.php");


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

llxHeader();

if ($action == 'add')
{
  $soc = new Societe($db);
  $soc->nom = $nom;

  $soc->adresse = $adresse;
  $soc->cp      = $cp;
  $soc->ville   = $ville;

  $soc->tel     = $tel;
  $soc->fax     = $fax;
  $soc->url     = ereg_replace( "http://", "", $url );
  $soc->siren   = $siren;

  $soc->client   = $client;
  $soc->fournisseur = $fournisseur;

  $socid = $soc->create();
}

if ($action == 'update')
{
  $soc = new Societe($db);

  $soc->nom = $nom;

  $soc->adresse = $adresse;
  $soc->cp = $cp;
  $soc->ville = $ville;

  $soc->tel = $tel;
  $soc->fax = $fax;
  $soc->url = ereg_replace( "http://", "", $url );
  $soc->siren = $siren;
  $soc->client = $client;
  $soc->fournisseur = $fournisseur;

  $soc->update($socid);
}

/*
 *
 *
 */

if ($action == 'create') 
{
  print '<div class="titre">Nouvelle société (prospect, client, fournisseur, partenaire...)</div><br>';
  print '<form action="soc.php" method="post">';
  print '<input type="hidden" name="action" value="add">';
  print '<input type="hidden" name="fournisseur" value="0">';

  print '<table class="border" cellpadding="3" cellspacing="0">';
  print '<tr><td>Nom</td><td><input type="text" name="nom"></td></tr>';
  print '<tr><td>Adresse</td><td><textarea name="adresse" cols="30" rows="3" wrap="soft"></textarea></td></tr>';
  print '<tr><td>CP</td><td><input size="6" type="text" name="cp">&nbsp;';
  print 'Ville&nbsp;<input type="text" name="ville"></td></tr>';

  print '<tr><td>Tel</td><td><input type="text" name="tel"></td></tr>';
  print '<tr><td>Fax</td><td><input type="text" name="fax"></td></tr>';
  print '<tr><td>Web</td><td>http://<input type="text" name="url"></td></tr>';

  print '<tr><td>Siren</td><td><input type="text" name="siren"></td></tr>';

  print '<tr><td>Client</td><td><select name="client">';
  print_oui_non($soc->client);
  print '</select>';

  print '<tr><td>Fournisseur</td><td><select name="fournisseur">';
  print_oui_non($soc->fournisseur);
  print '</select>';

  print '<tr><td colspan="2" align="center"><input type="submit" value="Ajouter"></td></tr>';
  print '</table>';
  print '</form>';
}
elseif ($action == 'edit')
{
  print_titre("Edition de la société");

  if ($socid)
    {

      $soc = new Societe($db);
      $soc->id = $socid;
      $soc->fetch($socid);

      print '<form action="soc.php?socid='.$socid.'" method="post">';
      print '<input type="hidden" name="action" value="update">';

      print '<table class="border" cellpadding="3" cellspacing="0">';
      print '<tr><td>Nom</td><td><input type="text" name="nom" value="'.$soc->nom.'"></td></tr>';
      print '<tr><td valign="top">Adresse</td><td><textarea name="adresse" cols="30" rows="3" wrap="soft">';
      print $soc->adresse;
      print '</textarea></td></tr>';
      
      print '<tr><td>CP</td><td><input size="6" type="text" name="cp" value="'.$soc->cp.'">&nbsp;';
      print 'Ville&nbsp;<input type="text" name="ville" value="'.$soc->ville.'"></td></tr>';
      
      print '<tr><td>Tel</td><td><input type="text" name="tel" value="'.$soc->tel.'"></td></tr>';
      print '<tr><td>Fax</td><td><input type="text" name="fax" value="'.$soc->fax.'"></td></tr>';
      print '<tr><td>Web</td><td>http://<input type="text" name="url" value="'.$soc->url.'"></td></tr>';
      
      print '<tr><td>Siren</td><td><input type="text" name="siren" value="'.$soc->siren.'"></td></tr>';
      
      print '<tr><td>Client</td><td><select name="client">';
      print_oui_non($soc->client);
      print '</select>';
      
      print '<tr><td>Fournisseur</td><td><select name="fournisseur">';
      print_oui_non($soc->fournisseur);
      print '</select>';
      
      print '</td></tr>';
      
      print '<tr><td align="center" colspan="2"><input type="submit" value="Mettre à jour"></td></tr>';
      print '</table>';
      print '</form>';
    }
}
else
{
  print_titre("Fiche société");
  
  $soc = new Societe($db);
  $soc->id = $socid;
  $soc->fetch($socid);
  
  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr><td width="20%">Nom</td><td width="80%" colspan="3">'.$soc->nom.'</td></tr>';
  print '<tr><td valign="top">Adresse</td><td colspan="3">'.nl2br($soc->adresse).'&nbsp;</td></tr>';
  print '<tr><td>CP</td><td colspan="3">'.$soc->cp.'&nbsp;'.$soc->ville.'</td></tr>';
  
  print '<tr><td>Tel</td><td>'.$soc->tel.'</td>';
  print '<td>Fax</td><td>'.$soc->fax.'</td></tr>';
  print '<tr><td>Web</td><td colspan="3"><a href="http://'.$soc->url.'">http://'.$soc->url.'</a></td></tr>';
  
  print '<tr><td>Siren</td><td colspan="3"><a target="_blank" href="http://www.societe.com/cgi-bin/recherche?rncs='.$soc->siren.'">'.$soc->siren.'</a>&nbsp;</td></tr>';

  if ($soc->client)
    {
      print '<tr><td>Client</td><td colspan="3">oui <a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$socid.'">'.img_file().'</a></td></tr>';
    }
  else
    {
      print '<tr><td>Client</td><td colspan="3">non</td></tr>';
    }
  
  if ($soc->fournisseur)
    {
      print '<tr><td>Fournisseur</td><td colspan="3">oui <a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$socid.'">'.img_file().'</a></td></tr>';
    }
  else
    {
      print '<tr><td>Fournisseur</td><td colspan="3">non</td></tr>';
    }

  print '</table>';

  /*
   *
   */
  
  print '<br><table id="actions" width="100%" cellspacing="0" cellpadding="3">';

  print '<td width="20%" align="center">[<a href="soc.php?socid='.$socid.'&action=edit">Editer</a>]</td>';
  print '<td width="20%" align="center">-</td>';
  print '<td width="20%" align="center">-</td>';
  print '<td width="20%" align="center">-</td>';
  print '<td width="20%" align="center">[<a href="societe/notify/fiche.php?socid='.$socid.'">Notifications</a>]</td>';
  print '</table><br>';
/*
 *
 */
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
