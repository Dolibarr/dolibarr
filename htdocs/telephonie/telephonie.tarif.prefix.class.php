<?PHP
/* Copyright (C) 2005-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/**
   \file       htdocs/telephonie.tarif.class.php
   \ingroup    facture
   \brief      Fichier de la classe des tarifs telephonies
   \version    $Revision$
*/

/**
   \class      TelephonieTarif
   \brief      Classe permettant la gestion des prefix de tarifs de telephonie
*/

class TelephonieTarifPrefix {
  //! Identifiant de la prefix
  var $id;
  var $_DB;

  /*
   * Constructeur
   *
   */
  function TelephonieTarifPrefix($_DB)
  {
    $this->db = $_DB;
  }

  function Create($user, $prefix, $tarif_id, $force='off')
  {
    if ($force == 'on')
      {
	$sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_prefix";
	$sql .= " SET fk_tarif='".$tarif_id."'";
	$sql .= " WHERE prefix = '".$prefix."';";
	
	if ( $this->db->query($sql) )
	  {
	    $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'telephonie_prefix');
	  }
	else
	  {
	    dol_syslog($this->db->error());
	  }
      }
    else
      {
	$this->fetch($prefix);

	if ($this->tarif_id == 0 && strlen(trim($prefix)) > 0)
	  {
	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_prefix";
	    $sql .= "(prefix, fk_tarif)";
	    $sql .= " VALUES ('".$prefix."','".$tarif_id."');";
	    
	    if ( $this->db->query($sql) )
	      {
		$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'telephonie_prefix');
	      }
	    else
	      {
		dol_syslog($this->db->error());
	      }
	  }
      }
                  
    return $result;
  }

  function Fetch($prefix)
  {
    $this->tarif_id = 0;

    $sql = "SELECT fk_tarif FROM ".MAIN_DB_PREFIX."telephonie_prefix";
    $sql .= " WHERE prefix = '".$prefix."';";
    
    $resql = $this->db->query($sql);

    if ( $resql )
      {
	if ($row = $this->db->fetch_row($resql) )
	  {
	    $this->tarif_id = $row[0];
	  }
	$this->db->free($resql);

	return 0;
      }
    else
      {
	return -1;
      }                 
  }
}

?>
