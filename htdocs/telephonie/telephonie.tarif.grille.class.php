<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
   \brief      Classe permettant la gestion des tarifs de telephonie
*/


class TelephonieTarifGrille {

  var $_DB;
  var $tableau_tarif;
  var $prefixes;
  var $prefixe_max;

  /*
   * Constructeur
   *
   */
  function TelephonieTarifGrille($_DB)
  {
    $this->db = $_DB;


  }


  function UpdateTarif($grille_id, $tarif_id, $temporel, $fixe, $user)
  {

    if ($temporel > 0)
      {

	$tarifs_linked = array();

	$this->_DBUpdateTarif($grille_id, $tarif_id, $temporel, $fixe, $user);
	
	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."telephonie_tarif";
	$sql .= " WHERE tlink = ".$tarif_id;
	
	$resql = $this->db->query($sql);
	
	if ($resql)
	  {
	    $num = $this->db->num_rows($resql);
	    $i = 0;
	    
	    while ($i < $num)
	      {
		$row = $this->db->fetch_row($resql);
		$tarifs_linked[$i] = $row[0];
		$i++;
	      }
	  }
	else
	  {
	    dolibarr_syslog($this->db->error());
	  }
	
	
	foreach($tarifs_linked as $tarif)
	  {
	    $this->_DBUpdateTarif($grille_id, $tarif, $temporel, $fixe, $user);
	  }
	
      }
    
    return $result;
  }

  /*
   *
   */


  function _DBUpdateTarif($grille_id, $tarif_id, $temporel, $fixe, $user)
  {

    $sql = "REPLACE INTO ".MAIN_DB_PREFIX."telephonie_tarif_montant";
    $sql .= "(fk_tarif_desc, fk_user, fk_tarif, temporel,fixe)";
    $sql .= " VALUES (".$grille_id.",".$user->id;
    $sql .= " ,".$tarif_id;
    $sql .= " ,".ereg_replace(",",".",$temporel);
    $sql .= " ,".ereg_replace(",",".",$fixe).");";
    
    if ( $this->db->query($sql) )
      {
	
      }
    else
      {
	dolibarr_syslog($this->db->error());
      }
    
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_tarif_montant_log";
    $sql .= "(fk_tarif_desc, fk_user, fk_tarif, temporel,fixe)";
    
    $sql .= " VALUES (".$grille_id.",".$user->id;
    $sql .= " ,".$tarif_id;
    $sql .= " ,".ereg_replace(",",".",$temporel);
    $sql .= " ,".ereg_replace(",",".",$fixe).");";
    
    if ( $this->db->query($sql) )
      {
	
      }
    else
      {
	dolibarr_syslog($this->db->error());
      }
           
    
    return $result;
  }

}

?>
