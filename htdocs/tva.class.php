<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/*
 * La tva collectée n'est calculée que sur les factures payées.
 *
 *
 */
class Tva
{
  var $db;
  var $note;
  /*
   * Initialistation automatique de la classe
   */
  Function Tva($DB)
    {
      $this->db = $DB;
    
      return 1;
  }
  /*
   * Hum la fonction s'appelle 'Solde' elle doit a mon avis
   * calcluer le solde de TVA, non ?
   *
   */
  Function solde($year = 0)
    {
    
      $reglee = $this->tva_sum_reglee($year);

      $payee = $this->tva_sum_payee($year);
      $collectee = $this->tva_sum_collectee($year);

      $solde = $reglee - ($collectee - $payee) ;

      return $solde;
    }
  /*
   * Tva collectée
   * Total de la TVA des factures emises par la societe.
   *
   */
  Function tva_sum_collectee($year = 0)
    {

      $sql = "SELECT sum(f.tva) as amount";
      $sql .= " FROM ".MAIN_DB_PREFIX."facture as f WHERE f.paye = 1";

      if ($year)
	{
	  $sql .= " AND f.datef >= '$year-01-01' AND f.datef <= '$year-12-31' ";
	}

      $result = $this->db->query($sql);

      if ($result)
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);
	      return $obj->amount;
	    }
	  else
	    {
	      return 0;
	    }

	  $this->db->free();
	  
	}
      else
	{
	  print $this->db->error();
	  return -1;
	} 
    }
  /*
   * Tva payée
   * 
   *
   */
  Function tva_sum_payee($year = 0)
    {
      
      $sql = "SELECT sum(f.amount) as amount";
      $sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
      
      if ($year)
	{
	  $sql .= " WHERE f.datef >= '$year-01-01' AND f.datef <= '$year-12-31' ";
	}
      
      $result = $this->db->query($sql);
      
      if ($result)
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);
	      return $obj->amount;
	    }
	  else
	    {
	      return 0;
	    }
	  
	  $this->db->free();

	}
      else
	{
	  print $this->db->error();
	  return -1;
	} 
    }
  /*
   * Tva réglée
   * Total de la TVA réglee aupres de qui de droit
   *
   */
  Function tva_sum_reglee($year = 0)
    {

      $sql = "SELECT sum(f.amount) as amount";
      $sql .= " FROM ".MAIN_DB_PREFIX."tva as f";
      
      if ($year)
	{
	  $sql .= " WHERE f.datev >= '$year-01-01' AND f.datev <= '$year-12-31' ";
	}

      $result = $this->db->query($sql);

      if ($result)
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);
	      return $obj->amount;
	    }
	  else
	    {
	      return 0;
	    }
	  
	  $this->db->free();

	}
      else
	{
	  print $this->db->error();
	  return -1;
	} 
    }

  /*
   *
   */
  Function add_payement($datep, $datev, $amount)
    {
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."tva (datep, datev, amount) ";
      $sql .= " VALUES ('".$this->db->idate($datep)."',";
      $sql .= "'".$this->db->idate($datev)."'," . ereg_replace(",",".",$amount) .")";

      $result = $this->db->query($sql);

      if ($result)
	{
	  return 1;
	}
      else
	{
	  print $this->db->error()."<br>".$sql;
	  return -1;
	} 


    }
}

?>
