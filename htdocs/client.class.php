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
 *
 * $Id$
 * $Source$
 *
 */

/*!	\file       htdocs/client.class.php
		\ingroup    societe
		\brief      Fichier de la classe des clients
		\version    $Revision$
*/


/*! \class Client
		\brief Classe permettant la gestion des clients
*/

include_once DOL_DOCUMENT_ROOT."/societe.class.php";

class Client extends Societe {
  var $db;

  /**
   *    \brief  Constructeur de la classe
   *    \param  DB     handler accès base de données
   *    \param  id     id societe (0 par defaut)
   */
	 
  function Client($DB, $id=0)
  {
    global $config;

    $this->db = $DB;
    $this->id = $id;
    $this->factures = array();

    return 0;
  }

  function read_factures()
  {
    $sql = "SELECT rowid, facnumber";
    $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
    $sql .= " WHERE f.fk_soc = ".$this->id;
    $sql .= " ORDER BY datef DESC";
    
    $result = $this->db->query($sql) ;
    $i = 0;
    if ( $result )
      {
	$num = $this->db->num_rows();
	
	while ($i < $num )
	  {
	    $row = $this->db->fetch_row();

	    $this->factures[$i][0] = $row[0];
	    $this->factures[$i][1] = $row[1];

	    $i++;
	  }
      }
    return $result;
  }
}
?>
