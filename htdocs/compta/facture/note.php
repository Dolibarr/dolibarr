<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
* Gestion d'une proposition commerciale
* @package propale
*/

require("./pre.inc.php");

$user->getrights('facture');
if (!$user->rights->facture->lire)
  accessforbidden();

/*
 *  Modules optionnels
 */

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  unset($_GET["action"]);
  $socidp = $user->societe_id;
}

/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST["action"] == 'update' && $user->rights->facture->creer)
{
  $facture = new Facture($db);
  $facture->fetch($_GET["facid"]);
  $facture->update_note($_POST["note"]);

}

llxHeader();
$html = new Form($db);
/******************************************************************************/
/*                   Fin des  Actions                                         */
/******************************************************************************/

if ($_GET["facid"])
{
  $facture = new Facture($db);
  if ( $facture->fetch($_GET["facid"]) ) 
    {

      $soc = new Societe($db, $facture->socidp);
      $soc->fetch($facture->socidp);

      $head[0][0] = DOL_URL_ROOT.'/compta/facture.php?facid='.$facture->id;
      $head[0][1] = "Facture : $facture->ref";
      $h = 1;
      $head[$h][0] = DOL_URL_ROOT.'/compta/facture/note.php?facid='.$facture->id;
      $head[$h][1] = "Note";
      $a = 1;
      $h++;      
      $head[$h][0] = DOL_URL_ROOT.'/compta/facture/info.php?facid='.$facture->id;
      $head[$h][1] = "Info";


      dolibarr_fiche_head($head, $a, $soc->nom);
                  
	  
      print '<table class="border" cellspacing="0" cellpadding="2" width="100%">';
      
      print '<tr><td>Société</td><td>';
      if ($societe->client == 1)
	{
	  $url ='fiche.php?socid='.$societe->id;
	}
      else
	{
	  $url = DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$societe->id;
	}
      print '<a href="'.$url.'">'.$societe->nom.'</a></td>';
      print '<td>Statut</td><td align="center"><b>'.$facture->statut_libelle.'</b></td></tr>';

	  print '<tr><td>Date</td><td>'.strftime("%A %d %B %Y",$facture->date);
	  if ($facture->fin_validite)
	    {
	      print " (".strftime("%d %B %Y",$facture->fin_validite).")";
	    }
	  print '</td>';

	  print '<td>Auteur</td><td>';
	  $author = new User($db, $facture->user_author);
	  $author->fetch('');
	  print $author->fullname.'</td></tr>';

	  print '<tr><td valign="top" colspan="4">Note :<br>'. nl2br($facture->note)."</td></tr>";
	  
	  if ($_GET["action"] == 'edit')
	    {
	      print '<form method="post" action="note.php?facid='.$facture->id.'">';
	      print '<input type="hidden" name="action" value="update">';
	      print '<tr><td valign="top" colspan="4"><textarea name="note" cols="80" rows="8">'.$facture->note."</textarea></td></tr>";
	      print '<tr><td align="center" colspan="4"><input type="submit" value="Enregistrer"></td></tr>';
	      print '</form>';
	    }

	  print "</table>";

      print "<br>";


	  /*
	   * Actions
	   */
	  print '</div>';
	  print '<div class="tabsAction">';
	  
	  if ($user->rights->facture->creer && $_GET["action"] <> 'edit')
	    {
	      print "<a class=\"tabAction\" href=\"note.php?facid=$facture->id&amp;action=edit\">Editer</a>";
	    }
	  
	  print "</div>";


    }

}
$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
