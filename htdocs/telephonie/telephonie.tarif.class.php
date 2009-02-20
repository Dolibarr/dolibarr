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


class TelephonieTarif {

  var $_DB;
  var $tableau_tarif;
  var $prefixes;
  var $prefixe_max;
  var $messages;

  /*
   * Constructeur
   *
   */
  function TelephonieTarif($_DB, $grille_id, $type, $fournisseur_id = 0 , $client_id = 0)
  {
    $this->db = $_DB;

    $this->tableau_tarif = array();

    $this->prefixes = array();

    $this->fournisseur_id = $fournisseur_id;
    $this->client_id = $client_id;

    $this->tarif_spec = $fournisseur_id;

    $this->messages = array();

    for ($j = 0 ; $j++ ; $j < 10)
      {
	$this->prefixes[$j] = array();
	$this->prefixe_max = array();
      }

    $this->_load_tarif($grille_id, $type);
  }

  function CreateTarif($name, $type)
  {

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_tarif";
    $sql .= "(libelle, type)";
    $sql .= " VALUES ('".$name."','".$type."');";
    
    if ( $this->db->query($sql) )
      {
	
      }
    else
      {
	dol_syslog($this->db->error());
      }
                  
    return $result;
  }

  /*
   *
   *
   */
  function Fetch($id)
  {
    $this->id = 0;

    $sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."telephonie_tarif";
    $sql .= " WHERE rowid = '".$id."';";
    
    $resql = $this->db->query($sql);

    if ( $resql )
      {
	if ($row = $this->db->fetch_row($resql) )
	  {
	    $this->id = $row[0];
	    $this->libelle = stripslashes($row[1]);
	  }
	$this->db->free($resql);

	return 0;
      }
    else
      {
	return -1;
      }                 
  }
  /*
   *
   *
   */
  function _load_tarif($grille_id, $type)
  {
    if ($type == 'achat')
      {
	$sql = "SELECT p.prefix, m.temporel, m.fixe";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_montant as m ";
	$sql .= " , ".MAIN_DB_PREFIX."telephonie_prefix as p ";
	$sql .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f ";

	$sql .= " WHERE p.fk_tarif = m.fk_tarif ";

	$sql .= " AND f.fk_tarif_grille = m.fk_tarif_desc";

	$sql .= " AND f.rowid =  " . $this->fournisseur_id;
	
      }
    elseif ($type == 'vente')
      {
	$sql = "SELECT p.prefix, m.temporel, m.fixe, t.libelle";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_montant as m";
	$sql .= " ,  ".MAIN_DB_PREFIX."telephonie_tarif as t";
	$sql .= " ,  ".MAIN_DB_PREFIX."telephonie_prefix as p";

	$sql .= " WHERE t.rowid = m.fk_tarif";
	$sql .= " AND t.rowid = p.fk_tarif";
	$sql .= " AND m.fk_tarif_desc = ". $grille_id;
      }
        
    if ( $resql = $this->db->query($sql) )
      {
	$num = $this->db->num_rows($resql);
	
	$i = 0;
	
	while ( $row = $this->db->fetch_row($resql) )
	  {	    
	    $l = $row[0];

	    $this->tableau_tarif[$l] = $row;
	    	    
	    // Tableaux des prefixes découpés en 10 tableaux
	    
	    $pref = substr($row[0],0,1);
	    
	    $i_pref = sizeof($this->prefixes[$pref]) + 1;
	    
	    $this->prefixes[$pref][$i_pref] = $row[0];
	    
	    // Taille maximale du prefixe
	    $this->prefixe_max[$pref] = max(strlen($row[0]), $this->prefixe_max[$pref]);
	    
	    $i++;
	  }
	$this->num_tarifs = $num;
	$this->db->free($resql);
      }
    else
      {
	dol_syslog("TelephonieTarif::_load_tarif Erreur SQL 1 (type=$type)", LOG_ERR);
	dol_syslog($sql, LOG_DEBUG);
      }
    /*
     * Tarif Spécifique
     *
     */
    if ($this->tarif_spec <> 1)
      {
	$sql = "SELECT p.prefix, m.temporel, m.fixe, t.libelle";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_montant as m";
	$sql .= " ,  ".MAIN_DB_PREFIX."telephonie_tarif as t";
	$sql .= " ,  ".MAIN_DB_PREFIX."telephonie_prefix as p";
	$sql .= " WHERE t.rowid = m.fk_tarif";
	$sql .= " AND t.rowid = p.fk_tarif";
	$sql .= " AND m.fk_tarif_desc = ".$this->tarif_spec;

	$resql = $this->db->query($sql);
        
	if ($resql)
	  {
	    $num = $this->db->num_rows($resql);
	    $i = 0;
	
	    while ($i < $num)
	      {
		$row = $this->db->fetch_row($resql);
		
		$l = $row[0];		
		$this->tableau_tarif[$l] = $row;
		
		$i++;
	      }
	    
	    $this->db->free($resql);	
	  }
	else
	  {
	    dol_syslog("TelephonieTarif::_load_tarif Erreur 59");
	    dol_syslog($this->db->error());
	  }
      }
    /*
     * Tarifs client
     *
     *
     */

    if ($type == 'vente' && ($this->client_id > 0))
      {
	$sql = "SELECT p.prefix, tc.temporel, tc.fixe, t.libelle";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_client as tc"; 
	$sql .= " , ".MAIN_DB_PREFIX."telephonie_prefix as p ";
	$sql .= " , ".MAIN_DB_PREFIX."telephonie_tarif as t";
	$sql .= " WHERE tc.fk_tarif = t.rowid AND p.fk_tarif = t.rowid";
	$sql .= " AND tc.fk_client = ".$this->client_id;

	if ( $this->db->query($sql) )
	  {
	    $num = $this->db->num_rows();		
	    $i = 0;
	    
	    while ($i < $num)
	      {
		$row = $this->db->fetch_row($i);
		
		$l = $row[0];

		$this->tableau_tarif[$l] = $row;
		
		$i++;
	      }
	  }
	else
	  {
	    print $this->db->error();
	  }
      }        
  }
  /*
   *
   *
   *
   */
  function cout($number, &$cout_tempo, &$cout_fixe, &$tarif_libelle)
  {
    $result = 0;
    $first_char_in_prefix = substr($number,2,1);

    $k = $this->prefixe_max[$first_char_in_prefix];

    $goon = 1;
    while ($goon == 1 && $k > 0)
      {
	
	$prefix_to_find = substr($number, 2, $k);
	
	if (in_array($prefix_to_find, $this->prefixes[$first_char_in_prefix]))
	  {
	    //print "\t$prefix_to_find\n";
	    $cout_tempo    = $this->tableau_tarif[$prefix_to_find][1];
	    $cout_fixe     = $this->tableau_tarif[$prefix_to_find][2];
	    $tarif_libelle = $this->tableau_tarif[$prefix_to_find][3];

	    $goon = 0;
	    $result = 1;
	  }		
	$k = $k - 1;
      }

    return $result;
  }
}

?>
