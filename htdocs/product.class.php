<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
  var $label;
  var $description;
  var $price;
  var $tva_tx;
  var $type;
  var $duration_value;
  var $duration_unit;

  Function Product($DB, $id=0)
    {
      $this->db = $DB;
      $this->id   = $id ;
      $this->envente = 0;
    }  
  /*
   *
   *
   *
   */
  Function check()
    {
      $err = 0;
      if (strlen(trim($this->ref)) == 0)
	$err++;
 
      if (strlen(trim($this->label)) == 0)
	$err++;
      
      if ($err > 0)
	{
	  return 0;
	}
      else
	{
	  return 1;
	}      
    }
  /*
   *
   *
   */
  Function create($user) 
    {
      if (strlen($this->price)==0)
	{
	  $this->price = 0;
	}
      $sql = "INSERT INTO llx_product (datec, fk_user_author, fk_product_type, price)";
      $sql .= " VALUES (now(),".$user->id.",$this->type, " . ereg_replace(",",".",$this->price) . ")";

      if ($this->db->query($sql) )
	{
	  $id = $this->db->last_insert_id();
	  
	  if ($id > 0)
	    {
	      $this->id = $id;
	      $this->_log_price($user);
	      if ( $this->update($id, $user) )
		{
		  return $id;
		}
	    }
	}
      else
	{
	  print $this->db->error() . ' in ' . $sql;
	}
  }
  /*
   *
   */
  Function _log_price($user) 
    {

      $sql = "REPLACE INTO llx_product_price ";
      $sql .= " SET date_price= now()";
      $sql .= " ,fk_product = ".$this->id;
      $sql .= " ,fk_user_author = ".$user->id;
      $sql .= " ,price = ".$this->price;
      $sql .= " ,envente = ".$this->envente;
      $sql .= " ,tva_tx = ".$this->tva_tx;
      
      if ($this->db->query($sql) )
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
   */
  Function update_price($id, $user)
    {
      if (strlen(trim($this->price)) > 0 )
	{
	  $sql = "UPDATE llx_product ";
	  $sql .= " SET price = " . ereg_replace(",",".",$this->price);	  
	  $sql .= " WHERE rowid = " . $id;
	  
	  if ( $this->db->query($sql) )
	    {
	      $this->_log_price($user);
	      return 1;
	    }
	  else
	    {
	      print $this->db->error() . ' in ' . $sql;
	      return -1;
	    }
	}
      else
	{
	  $this->mesg_error = "Prix saisi invalide.";
	  return -2;
	}
    }

  /*
   *
   *
   *
   */
  Function update($id, $user)
    {
      if (strlen(trim($this->ref)))
	{
	  $sql = "UPDATE llx_product ";
	  $sql .= " SET label = '" . trim($this->label) ."'";
	  $sql .= ",ref = '" . trim($this->ref) ."'";
	  $sql .= ",tva_tx = " . $this->tva_tx ;
	  $sql .= ",envente = " . $this->envente ;
	  $sql .= ",description = '" . trim($this->description) ."'";
	  $sql .= ",duration = '" . $this->duration_value . $this->duration_unit ."'";
	  
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
      else
	{
	  $this->mesg_error = "Vous devez indiquer une référence";
	  return 0;
	}
    }
  /*
   *
   *
   *
   */
  Function fetch ($id)
    {
    
      $sql = "SELECT rowid, ref, label, description, price, tva_tx, envente, nbvente, fk_product_type, duration";
      $sql .= " FROM llx_product WHERE rowid = $id";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  $result = $this->db->fetch_array();

	  $this->id             = $result["rowid"];
	  $this->ref            = $result["ref"];
	  $this->label          = stripslashes($result["label"]);
	  $this->description    = stripslashes($result["description"]);
	  $this->price          = $result["price"];
	  $this->tva_tx         = $result["tva_tx"];
	  $this->type           = $result["fk_product_type"];
	  $this->nbvente        = $result["nbvente"];
	  $this->envente        = $result["envente"];
	  $this->duration       = $result["duration"];
	  $this->duration_value = substr($result["duration"],0,strlen($result["duration"])-1);
	  $this->duration_unit  = substr($result["duration"],-1);

	  $this->label_url = '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$this->id.'">'.$this->label.'</a>';

	  $this->db->free();
	  return 1;
	}
      else
	{
	  print $this->db->error();
	  return -1;
	}
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
  /*
   *
   *
   */
  Function get_nb_vente()
    {
      $sql = "SELECT sum(d.qty), date_format(f.datef, '%Y%m') ";
      $sql .= " FROM llx_facturedet as d, llx_facture as f";
      $sql .= " WHERE f.rowid = d.fk_facture and f.paye = 1 and d.fk_product =".$this->id;
      $sql .= " GROUP BY date_format(f.datef,'%Y%m') DESC ;";

      return $this->_get_stats($sql);
    }
  /*
   *
   *
   */
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
