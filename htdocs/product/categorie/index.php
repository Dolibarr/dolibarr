<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Éric Seigne <erics@rycks.com>
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

llxHeader();

if ($id)
{
  $title = title_url($id, $db);

  print_barre_liste($title, $page, $PHP_SELF);

  $sql = "SELECT products_id FROM ".DB_NAME_OSC.".products_to_categories WHERE categories_id = $id";
  
  if ( $db->query($sql) )
    {
      $numprod = $db->num_rows();
      $i = 0;
      $wc = "(";
      while ($i < $numprod)
	{
	  $objp = $db->fetch_object( $i);
	  $wc .= $objp->products_id;
	  if ($i < $numprod -1)
	    {
	      $wc .= ",";
	    }
	  $i++;
	}
      $wc .=")";
      $db->free();
    }
  else
    {
      print $db->error();
    }
  //  print $wc ;

  if ($numprod)
    {

      $sql = "SELECT l.rowid, l.title, l.oscid, l.ref, l.status FROM llx_livre as l";
      $sql .= " WHERE l.oscid in $wc";

      if ( $db->query($sql) )
	{
	  $num = $db->num_rows();
	  $i = 0;
	  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
	  print "<TR class=\"liste_titre\"><td>Réf.</td><td>";
	  print_liste_field_titre("Titre",$PHP_SELF, "l.title");
	  print "</td>";
	  print '<td colspan="3">&nbsp;</td>';
	  print "</TR>\n";
	  $var=True;
	  while ($i < $num)
	    {
	      $objp = $db->fetch_object( $i);
	      $var=!$var;
	      print "<TR $bc[$var]>";
	      print '<TD><a href="'.DOL_URL_ROOT.'/boutique/livre/fiche.php?id='.$objp->rowid.'">'.$objp->ref.'</a></TD>';
	      print '<TD width="70%"><a href="'.DOL_URL_ROOT.'/boutique/livre/fiche.php?id='.$objp->rowid.'">'.$objp->title.'</a></TD>';
	      
	      
	      if ($objp->status == 1)
		{
		  print '<td align="center">';
		  print '<img src="/theme/'.$conf->theme.'/img/icon_status_green.png" border="0"></a></td>';
		  print '<td align="center">';
		  print '<img src="/theme/'.$conf->theme.'/img/icon_status_red_light.png" border="0"></a></td>';
		}
	      else
		{
		  print '<td align="center">';
		  print '<img src="/theme/'.$conf->theme.'/img/icon_status_green_light.png" border="0"></a></td>';
		  print '<td align="center">';
		  print '<img src="/theme/'.$conf->theme.'/img/icon_status_red.png" border="0"></a></td>';
		}
	      
	      print '<TD align="right">';
	      print '<a href="'.OSC_CATALOG_URL.'product_info.php?products_id='.$objp->oscid.'">Fiche en ligne</a></TD>';
	      print "</TR>\n";
	      $i++;
	    }
	  print "</TABLE>";
	  $db->free();
	}
      else
	{
	  print $db->error();
	}
    }
  else
    {
      print "Aucun produits dans cette catégorie";
    }
}
else
{

  print_barre_liste("Liste des catégories", $page, $PHP_SELF);

  $sql = "SELECT c.categories_id, cd.categories_name ";
  $sql .= " FROM ".DB_NAME_OSC.".categories as c,".DB_NAME_OSC.".categories_description as cd";
  $sql .= " WHERE c.categories_id = cd.categories_id AND cd.language_id = ".OSC_LANGUAGE_ID;
  $sql .= " AND c.parent_id = 0";
  $sql .= " ORDER BY cd.categories_name ASC ";
  
  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      $i = 0;
      print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
      print "<TR class=\"liste_titre\"><td>";
      print_liste_field_titre("Titre",$PHP_SELF, "a.title");
      print "</td>";
      print "<td></td>";
      print "</TR>\n";
      $var=True;
      while ($i < $num)
	{
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  
	  printc($objp->categories_id,$db, 0);
	  
	  $i++;
	}
      print "</TABLE>";
      $db->free();
    }
  else
    {
      print $db->error();
    }
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");


/*
 *
 *
 */
Function printc($id, $db, $level)
{

  $cat = new Categorie($db);
  $cat->fetch($id);

  print "<TR $bc[$var]><td>";

  for ($i = 0 ; $i < $level ; $i++)
    {
      print "&nbsp;&nbsp;|--";
    }

  print '<a href="index.php?id='.$cat->id.'">'.$cat->name."</a></TD>\n";
  print "</TR>\n";

  $childs = array();
  $childs = $cat->liste_childs_array();
  if (sizeof($childs))
  {
    foreach($childs as $key => $value)
      {
	printc($key,$db, $level+1);
      }
  }
}

Function title_url($id, $db)
{

  $cat = new Categorie($db);
  $cat->fetch($id);

  $title = $title . '<a href="index.php?id='.$cat->id.'">'.  $cat->name ."</a>";


  if (sizeof($cat->parent_id))
  {
    $title = title_url($cat->parent_id, $db) . " / ".$title;
  }

  return $title;
}

?>
