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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 *
 */

Class mod_commande_ivoire
{
  Function mod_commande_ivoire()
    {
      $this->nom = "Ivoire";
    }

  Function info()
    {
      return "Renvoie le numéro sous la forme numérique C0M1, COM2, COM3, ...";      
    }

  function commande_get_num($obj_soc=0)
    { 
      global $db;
      
      $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."commande WHERE fk_statut <> 0";
      
      if ( $db->query($sql) ) 
	{
	  $row = $db->fetch_row(0);
	  
	  $num = $row[0];
	}
      
      $y = strftime("%y",time());
      
      return 'COM'.($num+1);
    }
}

?>
