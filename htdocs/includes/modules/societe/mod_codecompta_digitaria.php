<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class mod_codecompta_digitaria
{

  function mod_codecompta_digitaria()
  {
    $this->nom = "Digitaria";
  }

  function info()
  {
    return "Renvoie un code compta composé suivant le code client. Le code est composé du caractère 'C' en première position suivi des 5 premiers caractères du code client.";
  }

  /**
   *    \brief      Renvoi code
   *    \param      DB              Handler d'accès base
   *    \param      societe         Objet societe
   */
  function get_code($DB, $societe)
  {    
    $i = 0;
    $this->db = $DB;

    $this->code = "C".substr($societe->code_client,0,5);

    $is_dispo = $this->verif($DB, $this->code);

    while ( $is_dispo <> 0 && $i < 37)
      {
	$arr = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

	$this->code = substr($this->code,0,5) . substr($arr, $i, 1);

	$is_dispo = $this->verif($DB, $this->code);

	$i++;
      }


    if ($is_dispo == 0)
      {
	return 0;
      }
    else
      {
	return -1;
      }

  }
  
  function verif($db, $code)
  {
    $sql = "SELECT code_compta FROM ".MAIN_DB_PREFIX."societe";
    $sql .= " WHERE code_compta = '".$code."'";

    if ($db->query($sql))
      {
	if ($db->num_rows() == 0)
	  {
	    return 0;
	  }
	else
	  {
	    return -1;
	  }
      }
    else
      {
	return -2;
      }

  }
}

?>
