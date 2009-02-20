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
 *
 * $Id$
 * $Source$
 *
 * Script d'verif des CDR
 */
require_once(DOL_DOCUMENT_ROOT."/telephonie/fournisseurtel.class.php");

class FacturationVerifCdr {

  function FacturationVerifCdr($dbh)
  {
    $this->db = $dbh;
    $this->messages = array();
    $this->message_bad_file_format = array();
  }

  function Verif()
  {
    $error = 0;

    $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
    
    if ( $this->db->query($sql) )
      {
	$row = $this->db->fetch_row();
	dol_syslog("facturation-verif.php ".$row[0]." lignes de communications a verifier");
      }

    /*******************************************************************************
     *
     * Verifie la présence des tarifs adequat
     *
     */
    $grille_vente = TELEPHONIE_GRILLE_VENTE_DEFAUT_ID;
    
    $tarif_vente = new TelephonieTarif($this->db, $grille_vente, "vente");
    
    dol_syslog("facturation-verif.php Grille : $grille contient ".$tarif_vente->num_tarifs." tarifs");
    
    $sql = "SELECT distinct(num) FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
    
    $resql = $this->db->query($sql);
    
    if ( $resql )
      {
	$nums = $this->db->num_rows($resql);
	
	while($row = $this->db->fetch_row($resql) )
	  {
	    $numero = $row[0];
	    
	    /* Reformatage du numéro */
	    
	    if (substr($numero,0,2) == '00') /* International */
	      {
	      }     
	    elseif (substr($numero,0,2) == '06') /* Telephones Mobiles */
	      {	
		$numero = "0033".substr($numero,1);
	      }
	    else
	      {
		$numero = "0033".substr($numero, 1);
	      }	  
	    
	    /* Numéros spéciaux */
	    if (substr($numero,4,1) == 8)
	      {
		
	      }
	    else
	      {	  
		if ( $tarif_vente->cout($numero, $x, $y, $z) == 0)
		  {
		    print "Tarif vente manquant pour $numero ($row[0]) $x $y dans la grille $grille\n";
		  }
	      }
	    
	  }
	$this->db->free($resql);
	
      }
	}
}
