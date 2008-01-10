<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

require("./pre.inc.php");
require("./pnarticle.class.php");


if ($action == 'update' && !$cancel)
{
  $article = new pnArticle($db);

  $article->titre = $_POST["titre"];
  $article->body = $_POST["body"];
  if ($article->update($id, $user))
    {

    }
  else
    {
      $action = 'edit';
    }
}

/*
 *
 *
 */

llxHeader();

if ($id)
{
  $article = new pnArticle($db);

  if ($id)
    {
      $result = $article->fetch($id, 0);
    }
  
  if ( $result )
    { 
      $htmls = new Form($db);



      if ($action == 'edit')
	{
	  print_titre ("Edition de la fiche article");
	  
	  print "<form action=\"$fiche.php?id=$id\" method=\"post\">\n";
	  print "<input type=\"hidden\" name=\"action\" value=\"update\">";
	  
	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	  print "<tr><td>Titre</td><td>$article->titre</td></tr>\n";
	  print "<tr>";
	  print '<td valign="top">'.$langs->trans("Description").'</td>';
	  
	  print '<td valign="top" width="80%"><textarea name="body" rows="14" cols="60">';
	  print str_replace("<br />","",$article->body);
	  print "</textarea></td></tr>";
	  
	  print '<tr><td align="center" colspan="2"><input type="submit" value="'.$langs->trans("Save").'">&nbsp;<input type="submit" value="'.$langs->trans("Cancel").'" name="cancel"></td></tr>';
	  print "</form>";
	  

	  print '</table><hr>';
	      
	    }    

	  /*
	   * Affichage
	   */
      print_fiche_titre('Fiche Article : '.$article->titre);

	  print '<table class="border" width="100%">';
	  
	  print "<tr><td>Titre</td><td>$article->titre</td></tr>\n";
	  print "<tr><td>Titre</td><td>$article->body</td></tr>\n";

	  
	  print "</table>";
	}
      else
	{
	  print "Fetch failed";
	}
    

    }
  else
    {
      print "Error";
    }


/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print '<div class="tabsAction">';
if ($action != 'create')
{
  print '<a class="butAction" href="fiche.php?action=edit&id='.$id.'">'.$langs->trans('Modify').'</a>';
}
print '</div>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
