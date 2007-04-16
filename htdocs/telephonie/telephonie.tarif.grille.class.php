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
   \brief      Classe permettant la gestion des tarifs de telephonie
*/


class TelephonieTarifGrille {
  //! Identifiant de la grille
  var $id;
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
  /*
    \brief Creation d'une nouvelle grille
  */
  function CreateGrille($user, $name, $type, $copy=0)
  {    
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_tarif_grille";
    $sql .= "(libelle, type_tarif)";
    $sql .= " VALUES ('".addslashes($name)."','".$type."');";
    
    if ( $this->db->query($sql) )
      {
	$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'telephonie_tarif_grille');

	$this->Perms($user, 2, $user->id);
      }
    else
      {
	dolibarr_syslog($this->db->error());
      }
                  
    if ($copy > 0 && $type == 'vente')
      {
	$this->CopieGrille($user,$copy);
      }

    return $result;
  }

  function CopieGrille($user, $ori)
  {
    $sql = "SELECT fk_tarif,temporel,fixe FROM ".MAIN_DB_PREFIX."telephonie_tarif_montant";
    $sql .= " WHERE fk_tarif_desc= '".$ori."'";
	
    $resql = $this->db->query($sql);
	
    if ($resql)
      {
	$i = 0;
	while ($row = $this->db->fetch_row($resql) )
	  {
	    $tarifs[$i] = $row;
	    $i++;
	  }
	$this->db->free($resql);
      }
    else
      {
	dolibarr_syslog($this->db->error());
      }
    
	
    foreach($tarifs as $tarif)
      {
	$this->_DBUpdateTarif($this->id, $tarif[0], $tarif[1], $tarif[2], $user);
      }
    
  }


  function Perms($user, $perms, $user_grille)
  {

    if ($perms == 0)
      {
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille_rights";
	$sql .= " WHERE fk_user = '".$user_grille."'";
	$sql .= " AND fk_grille = '".$this->id."';";
	$this->db->query($sql);
      }
    
    if ($perms == 1)
      {
	$sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_tarif_grille_rights";
	$sql .= " SET pread= 1, pwrite = 0, fk_user_creat ='".$user->id."' WHERE fk_user = '".$user_grille."'";
	$sql .= " AND fk_grille = '".$this->id."';";
	if ( $this->db->query($sql) )
	  {
	    if ($this->db->affected_rows($resql) == 0)
	      {
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_tarif_grille_rights";
		$sql .= " (pread,pwrite,  fk_user, fk_grille, fk_user_creat) VALUES ";
		$sql .= " (1,0,'".$user_grille."','".$this->id."','".$user->id."');";
		if ( $this->db->query($sql) )
		  {
		    
		  }
	      }
	  }
      }
    
    if ($perms == 2)
      {
	$sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_tarif_grille_rights";
	$sql .= " SET pread= 1, pwrite = 1, fk_user_creat ='".$user->id."' WHERE fk_user = '".$user_grille."'";
	$sql .= " AND fk_grille = '".$this->id."';";
	if ( $this->db->query($sql) )
	  {
	    
	    if ($this->db->affected_rows($resql) == 0)
	      {
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_tarif_grille_rights";
		$sql .= " (pread,pwrite, fk_user, fk_grille, fk_user_creat) VALUES ";
		$sql .= " (1,1,'".$user_grille."','".$this->id."','".$user->id."');";
		if ( $this->db->query($sql) )
		  {
		    
		  }
		else
		  {
		    print $sql;
		  }
	      }
	    
	  }
      }
       
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
