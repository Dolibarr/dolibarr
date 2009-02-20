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

class TelephonieTarif {

  var $_DB;
  var $tarif_fournisseur;
  var $prefixes;
  var $prefixe_max;

  /*
   * Constructeur
   *
   */
  function TelephonieTarif($_DB, $fournisseur_id, $type, $client_id = 0)
  {
    $this->db = $_DB;

    $this->tarif_fournisseur = array();

    $this->prefixes = array();

    $this->client_id = $client_id;


    for ($j = 0 ; $j++ ; $j < 10)
      {
	$this->prefixes[$j] = array();
	$this->prefixe_max = array();
      }

    $this->_load_tarif($fournisseur_id, $type);

  }


  function _load_tarif($fournisseur_id, $type)
  {
    
    if ($type == 'achat')
      {
	$sql = "SELECT prefix, temporel, fixe";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_achat ";
	$sql .= " WHERE fk_fournisseur = " . $fournisseur_id;
	
      }
    elseif ($type == 'vente')
      {
	$sql = "SELECT prefix, temporel, fixe, libelle";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_vente";
      }
        
    if ( $this->db->query($sql) )
      {
	$num = $this->db->num_rows();
	
	//print "$num tarif_fournisseur trouvés\n";
	
	$i = 0;
	
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($i);
	    
	    $l = $row[0];

	    $this->tarif_fournisseur[$l] = $row;
	    	    
	    // Tableaux des prefixes découpés en 10 tableaux
	    
	    $pref = substr($row[0],0,1);
	    
	    $i_pref = sizeof($this->prefixes[$pref]) + 1;
	    
	    $this->prefixes[$pref][$i_pref] = $row[0];
	    
	    // Taille maximale du prefixe
	    $this->prefixe_max[$pref] = max(strlen($row[0]), $this->prefixe_max[$pref]);
	    
	    $i++;
	  }

	$this->db->free();	
      }
    else
      {
	dol_syslog("TelephonieTarif::_load_tarif Erreur 1");
	dol_syslog($this->db->error());
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

		$this->tarif_fournisseur[$l] = $row;
		
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

    //print "$first_char_in_prefix\t";

    $goon = 1;
    while ($goon == 1 && $k > 0)
      {
	
	$prefix_to_find = substr($number, 2, $k);
	
	//print "($k, $prefix_to_find)";
	
	if (in_array($prefix_to_find, $this->prefixes[$first_char_in_prefix]))
	  {
	    //	    print "\t$prefix_to_find\n";
	    $cout_tempo    = $this->tarif_fournisseur[$prefix_to_find][1];
	    $cout_fixe     = $this->tarif_fournisseur[$prefix_to_find][2];
	    $tarif_libelle = $this->tarif_fournisseur[$prefix_to_find][3];

	    $goon = 0;
	    $result = 1;
	  }		
	$k = $k - 1;
      }

    return $result;
  }
}

?>
