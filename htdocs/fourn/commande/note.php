<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/fourn/commande/note.php
        \ingroup    commande
        \brief      Fiche commande
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("orders");
$langs->load("suppliers");
$langs->load("companies");

$user->getrights('fournisseur');

if (!$user->rights->fournisseur->commande->lire) accessforbidden();

/*
 *
 */	

if ($_POST["action"] == 'updatenote' && $user->rights->fournisseur->commande->creer)
{
  $commande = new CommandeFournisseur($db);
  $commande->fetch($_GET["id"]);

  $result = $commande->UpdateNote($user, $_POST["note"]);
  if ($result == 0)
    {
      Header("Location: note.php?id=".$_GET["id"]);
    }
}

llxHeader('',$langs->trans("OrderCard"),"CommandeFournisseur");

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
  
if ($_GET["id"] > 0)
{
  $commande = new CommandeFournisseur($db);
  if ( $commande->fetch($_GET["id"]) == 0)
    {	  
      $soc = new Societe($db);
      $soc->fetch($commande->soc_id);
      $author = new User($db);
      $author->id = $commande->user_author_id;
      $author->fetch();
      
      $h = 0;
      $head[$h][0] = DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$commande->id;
      $head[$h][1] = $langs->trans("OrderCard");
      $h++;

      $head[$h][0] = DOL_URL_ROOT.'/fourn/commande/note.php?id='.$commande->id;
      $head[$h][1] = $langs->trans("Note");
      $a = $h;
      $h++;

      $head[$h][0] = DOL_URL_ROOT.'/fourn/commande/history.php?id='.$commande->id;
      $head[$h][1] = $langs->trans("OrderFollow");
      $h++;

      $title=$langs->trans("Order").": $commande->ref";
      dolibarr_fiche_head($head, $a, $title);	  
      

      /*
       *   Commande
       */
      print '<form action="note.php?id='.$commande->id.'" method="post">';
      print '<input type="hidden" name="action" value="updatenote">';

      print '<table class="border" width="100%">';
      print '<tr><td width="20%">'.$langs->trans("Supplier").'</td>';
      print '<td colspan="3">';
      print '<b><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';
      print '</tr>';
	  
      print '<tr>';
      print '<td>'.$langs->trans("Status").'</td>';
      print '<td colspan="3">';
      print '<img src="statut'.$commande->statut.'.png">&nbsp;';
      print $commande->statuts[$commande->statut];
      print "</td></tr>";
	  
      if ($commande->methode_commande_id > 0)
	{
	  print '<tr><td>'.$langs->trans("Date").'</td>';
	  print '<td colspan="2">';
	  
	  if ($commande->date_commande)
	    {
	      print strftime("%A %d %B %Y",$commande->date_commande)."\n";
	    }
	  
	  print '&nbsp;</td><td width="50%">';
	  if ($commande->methode_commande)
	    {
	      print "Méthode : " .$commande->methode_commande;
	    }
	  print "</td></tr>";
	}
      
      // Auteur
      print '<tr><td>'.$langs->trans("Author").'</td><td colspan="2">'.$author->fullname.'</td>';	
      print '<td width="50%">';
      print "&nbsp;</td></tr>";
  
      // Ligne de 3 colonnes
      print '<tr><td>'.$langs->trans("AmountHT").'</td>';
      print '<td align="right"><b>'.price($commande->total_ht).'</b></td>';
      print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td>';
      print '<td valign="top">&nbsp;</td></tr>';

      print '<tr><td>'.$langs->trans("AmountVAT").'</td><td align="right">'.price($commande->total_tva).'</td>';
      print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td><td>&nbsp;</td></tr>';
      print '<tr><td>'.$langs->trans("AmountTTC").'</td><td align="right">'.price($commande->total_ttc).'</td>';
      print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td><td>&nbsp;</td></tr>';


      if ($user->rights->fournisseur->commande->creer)
	{
	  print '<tr><td valign="top">'.$langs->trans("Note").'</td><td colspan="3"><textarea cols="60" rows="10" name="note">'.nl2br($commande->note)."</textarea></td></tr>";
	  print '<tr><td colspan="4" align="center"><input type="submit" class="button"></td></tr>';
	}
      else
	{
	  print '<tr><td>'.$langs->trans("Note").'</td><td colspan="3">'.nl2br($commande->note)."</td></tr>";
	}
	  
      print "</table></form>";
    }
  else
    {
      /* Commande non trouvée */
      print "Commande inexistante";
    }
}  


$db->close();

llxFooter('$Date$ - $Revision$');
?>
