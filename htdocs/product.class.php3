<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Product
{
  var $db ;

  var $id ;
  var $ref;
  var $libelle;
  var $description;
  var $price;
  var $tva_tx;

  Function Product($DB, $id=0)
    {
      $this->db = $DB;
      $this->id   = $id ;
    }  
  /*
   *
   *
   *
   */
  Function create($user) 
    {

      $sql = "INSERT INTO llx_product (fk_user_author) VALUES (".$user->id.")";
      
      if ($this->db->query($sql) )
	{
	  $id = $this->db->last_insert_id();
	  
	  if ( $this->update($id, $user) )
	    {
	      return $id;
	    }
	}
      else
	{
	  print $this->db->error() . ' in ' . $sql;
	}
  }

  /*
   *
   *
   *
   */
  Function update($id, $user)
    {

      $sql = "UPDATE llx_product ";
      $sql .= " SET label = '" . trim($this->libelle) ."'";
      $sql .= ",ref = '" . trim($this->ref) ."'";
      $sql .= ",price = " . $this->price ;
      $sql .= ",tva_tx = " . $this->tva_tx ;
      $sql .= ",description = '" . trim($this->description) ."'";
      
      $sql .= " WHERE rowid = " . $id;
      
      if ( $this->db->query($sql) )
	{
	  return 1;
	}
      else
	{
	  print $this->db->error() . ' in ' . $sql;
	}
    }
  /*
   *
   *
   *
   */
  Function fetch ($id)
    {
    
      $sql = "SELECT rowid, ref, label, description, price, tva_tx";
      $sql .= " FROM llx_product WHERE rowid = $id";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  $result = $this->db->fetch_array();

	  $this->id          = $result["rowid"];
	  $this->ref         = $result["ref"];
	  $this->label       = $result["label"];
	  $this->description = $result["description"];
	  $this->price       = $result["price"];
	  $this->tva_tx      = $result["tva_tx"];
	}
      $this->db->free();
      return $result;
  }
  /*
   *
   *
   */
  Function count_propale()
    {
      $sql = "SELECT pd.fk_propal";
      $sql .= " FROM llx_propaldet as pd, llx_product as p";
      $sql .= " WHERE p.rowid = pd.fk_product AND p.rowid = ".$this->id;
      $sql .= " GROUP BY pd.fk_propal";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  return $this->db->num_rows();
	}
      else
	{
	  return 0;
	}
    }
  /*
   *
   *
   */
  Function count_propale_client()
    {
      $sql = "SELECT pr.fk_soc";
      $sql .= " FROM llx_propaldet as pd, llx_product as p, llx_propal as pr";
      $sql .= " WHERE p.rowid = pd.fk_product AND pd.fk_propal = pr.rowid AND p.rowid = ".$this->id;
      $sql .= " GROUP BY pr.fk_soc";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  return $this->db->num_rows();
	}
      else
	{
	  return 0;
	}
    }
  /*
   *
   *
   */
  Function count_facture()
    {
      $sql = "SELECT pd.fk_facture";
      $sql .= " FROM llx_facturedet as pd, llx_product as p";
      $sql .= " WHERE p.rowid = pd.fk_product AND p.rowid = ".$this->id;
      $sql .= " GROUP BY pd.fk_facture";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  return $this->db->num_rows();
	}
      else
	{
	  return 0;
	}
    }
  /*
   *
   *
   */

  Function _get_stats($sql)
    {
      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  while ($i < $num)
	    {
	      $arr = $this->db->fetch_array($i);
	      $ventes[$arr[1]] = $arr[0];
	      $i++;
	    }
	}

      $year = strftime('%Y',time());
      $month = strftime('%m',time());
      $result = array();

      for ($j = 0 ; $j < 12 ; $j++)
	{
	  $idx=ucfirst(strftime("%b",mktime(12,0,0,$month,1,$year)));
	  if (isset($ventes[$year . $month]))
	    {
	      $result[$j] = array($idx, $ventes[$year . $month]);
	    }
	  else
	    {
	      $result[$j] = array($idx,0);
	    }

	  $month = "0".($month - 1);
	  if (strlen($month) == 3)
	    {
	      $month = substr($month,1);
	    }
	  if ($month == 0)
	    {
	      $month = 12;
	      $year = $year - 1;
	    }
	}
      return array_reverse($result);

    }

  Function get_nb_vente()
    {
      $sql = "SELECT sum(d.qty), date_format(f.datef, '%Y%m') ";
      $sql .= " FROM llx_facturedet as d, llx_facture as f";
      $sql .= " WHERE f.rowid = d.fk_facture and f.paye = 1 and d.fk_product =".$this->id;
      $sql .= " GROUP BY date_format(f.datef,'%Y%m') DESC ;";

      return $this->_get_stats($sql);
    }

  Function get_num_vente()
    {
      $sql = "SELECT count(*), date_format(f.datef, '%Y%m') ";
      $sql .= " FROM llx_facturedet as d, llx_facture as f";
      $sql .= " WHERE f.rowid = d.fk_facture and f.paye = 1 and d.fk_product =".$this->id;
      $sql .= " GROUP BY date_format(f.datef,'%Y%m') DESC ;";

      return $this->_get_stats($sql);
    }
}
?>
