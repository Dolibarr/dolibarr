<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
if ($user->rights->produit->lire)
{
  $info_box_head = array();
  $info_box_head[] = array('text' => "Les 5 derniers produits enregistrés");

  $info_box_contents = array();

  $sql = "SELECT p.label, p.rowid, p.price";
  $sql .= " FROM llx_product as p";
  $sql .= " ORDER BY p.datec DESC";
  $sql .= $db->plimit(5, 0);
  
  $result = $db->query($sql);

  if ($result) 
    {
      $num = $db->num_rows();      
      $i = 0;      
      while ($i < $num)
	{
	  $objp = $db->fetch_object( $i);
	  
	  $info_box_contents[$i][0] = array('align' => 'left',
					    'text' => $objp->label,
					    'url' => DOL_URL_ROOT."/product/fiche.php?id=".$objp->rowid);
	  
	  $info_box_contents[$i][1] = array('align' => 'right',
					    'text' => price($objp->price),
					    'url' => DOL_URL_ROOT."/product/fiche.php?id=".$objp->rowid);
	  $i++;
	}
    } 
  new infoBox($info_box_head, $info_box_contents);
}
?>
