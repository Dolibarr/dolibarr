<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require(DOL_DOCUMENT_ROOT."/telephonie/fournisseurtel.class.php");
$mesg = '';

llxHeader("","","Fiche Ligne");

if ($_GET["id"] or $_GET["numero"])
{
  if ($_GET["action"] <> 're-edit')
    {
      $ligne = new LigneTel($db);
      if ($_GET["id"])
	{
	  $result = $ligne->fetch_by_id($_GET["id"]);
	}
      if ($_GET["numero"])
	{
	  $result = $ligne->fetch($_GET["numero"]);
	}
    }
  
  if ( $result )
    { 
	  
      $h=0;
      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/fiche.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans("Ligne");
      $h++;
	  
      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/commande.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans('Commande');
      $hselected = $h;
      $h++;
	  
      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/infoc.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans('Infos');
      $h++;
	  
      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/history.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans('Historique');
      $h++;
	  
      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/conso.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans('Conso');
      $h++;
	  
      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/stat.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans('Stats');
      $h++;

      dol_fiche_head($head, $hselected, 'Ligne : '.$ligne->numero);

      print_fiche_titre('Factures Ligne', $mesg);
      
      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

      print '<tr><td width="25%">Num�ro</td><td>'.dol_print_phone($ligne->numero,0,0,true).'</td></tr>';
	      	     
      $client = new Societe($db, $ligne->client_id);
      $client->fetch($ligne->client_id);

      print '<tr><td width="25%">Client</td><td>';

      print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$ligne->client_id.'">';
      print $client->nom.'</a></td></tr>';

      $client_facture = new Societe($db);
      $client_facture->fetch($ligne->client_facture_id);

      print '<tr><td width="25%">Client Factur�</td><td>'.$client_facture->nom.'</td></tr>';
      print '</table>';

      $ftx = new FournisseurTelephonie($db, $ligne->fournisseur_id);
      $ftx->fetch($ligne->fournisseur_id);

      if (strlen($ligne->numero) <> 10)
	{
	  $ok_commande = 0;
	}
      else
	{
	  $ok_commande = 1;
	}

      print '<table>';
      print "<tr><td>Numero correct </td><td> ".$ok_commande .'</td></tr>';
      print "<tr><td>Commandes ouvertes aupres du fournisseur </td><td> ".$ftx->commande_enable .'</td></tr>';
      print "<tr><td>Permission pour l'utilisateur de commander des lignes </td><td> ".$user->rights->telephonie->ligne_commander.'</td></tr>';
      print "<tr><td>Statut de la ligne compatible </td><td> ".($ligne->statut == 1 or $ligne->statut == -1) .'</td></tr>';
      print "<tr><td>Rib ok ou mode de reglement par virement </td><td> ".($client_facture->verif_rib() or $ligne->mode_paiement == 'vir').'</td></tr>';
      print '</table>';

    }
}
else
{
  print "Error";
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
