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
if ($user->rights->commande->lire)
{

  $info_box_head = array();
  $info_box_head[] = array('text' => "Les 5 dernières commandes");

  $info_box_contents = array();

  $sql = "SELECT s.nom,s.idp,p.ref,".$db->pdate("p.date_commande")." as dp,p.rowid";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as p WHERE p.fk_soc = s.idp";  
  if($user->societe_id)
    {
      $sql .= " AND s.idp = $user->societe_id";
    }
  $sql .= " ORDER BY p.date_commande DESC, p.ref DESC ";
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
					    'text' => img_file()." ".$objp->ref,
					    'url' => DOL_URL_ROOT."/commande/fiche.php?id=".$objp->rowid);
	  
	  $info_box_contents[$i][1] = array('align' => 'left',
					    'text' => $objp->nom,
					    'url' => DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->idp);
	  $i++;
	}
    } 
  new infoBox($info_box_head, $info_box_contents);
}
?>
