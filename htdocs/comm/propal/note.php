<?php
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

/*! \file htdocs/comm/propal/note.php
        \ingroup    propale
        \brief      Fiche d'information sur une proposition commerciale
		\version    $Revision$
*/

require("./pre.inc.php");

$user->getrights('propale');
if (!$user->rights->propale->lire)
  accessforbidden();


require("../../propal.class.php");
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

if ($_POST["action"] == 'update' && $user->rights->propale->creer)
{
  $propal = new Propal($db);
  $propal->fetch($_GET["propalid"]);
  $propal->update_note($_POST["note"]);

}

llxHeader();
$html = new Form($db);
/******************************************************************************/
/*                   Fin des  Actions                                         */
/******************************************************************************/

if ($_GET["propalid"])
{
  $propal = new Propal($db);
  if ( $propal->fetch($_GET["propalid"]) ) 
    {

      $societe = new Societe($db);
      if ( $societe->fetch($propal->soc_id) ) 
	{

	  $head[0][0] = DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id;
	  $head[0][1] = "Proposition commerciale : $propal->ref";
	  $h = 1;
	  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/note.php?propalid='.$propal->id;
	  $head[$h][1] = "Note";
	  $hselected = 1;
	  $h++;
	  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/info.php?propalid='.$propal->id;
	  $head[$h][1] = "Info";
	   
	  dolibarr_fiche_head($head, $hselected, $societe->nom);
                  	  
	  print '<table class="border" width="100%">';
	  
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
	  print '<td>'.$langs->trans("Status").'</td><td align="center"><b>'.$propal->statut_libelle.'</b></td></tr>';

	  print '<tr><td>Date</td><td>'.strftime("%A %d %B %Y",$propal->date);
	  if ($propal->fin_validite)
	    {
	      print " (".strftime("%d %B %Y",$propal->fin_validite).")";
	    }
	  print '</td>';

	  print '<td>'.$langs->trans("Author").'</td><td>';
	  $author = new User($db, $propal->user_author_id);
	  $author->fetch('');
	  print $author->fullname.'</td></tr>';

	  print '<tr><td valign="top" colspan="4">Note :<br>'. nl2br($propal->note)."</td></tr>";
	  
	  if ($_GET["action"] == 'edit')
	    {
	      print '<form method="post" action="note.php?propalid='.$propal->id.'">';
	      print '<input type="hidden" name="action" value="update">';
	      print '<tr><td valign="top" colspan="4"><textarea name="note" cols="80" rows="8">'.$propal->note."</textarea></td></tr>";
	      print '<tr><td align="center" colspan="4"><input type="submit" value="'.$langs->trans("Save").'"></td></tr>';
	      print '</form>';
	    }


	  print "</table>";
      print '<br>';

	  /*
	   * Actions
	   */
	  print '</div>';
	  print '<p><div class="tabsAction">';
	  
	  if ($user->rights->propale->creer && $_GET["action"] <> 'edit')
	    {
	      print "<a class=\"tabAction\" href=\"note.php?propalid=$propal->id&amp;action=edit\">".$langs->trans("Edit")."</a>";
	    }
	  
	  print "</div>";

	}     
    }

}
$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
