<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Jean-Louis Bergamo <jlb@j1b.org>
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

class Account
{
  var $rowid;

  var $bank;
  var $label;
  var $courant;
  var $clos;
  var $code_banque;
  var $code_guichet;
  var $number;
  var $cle_rib;
  var $bic;
  var $iban_prefix;

  Function Account($DB, $rowid=0)
  {
    global $config;
    
    $this->clos = 0;
    $this->db = $DB;
    $this->rowid = $rowid;
    $this->solde = 0;    
    return 1;
  }

  /*
   * Efface une entree dans la table llx_bank
   */

  Function deleteline($rowid)
  {
    $sql = "DELETE FROM llx_bank_class WHERE lineid=$rowid";
    $result = $this->db->query($sql);

    $sql = "DELETE FROM llx_bank WHERE rowid=$rowid";
    $result = $this->db->query($sql);

    $sql = "DELETE FROM llx_bank_url WHERE fk_bank=$rowid";
    $result = $this->db->query($sql);
  }
  /*
   *
   *
   */
  Function add_url_line($line_id, $url_id, $url, $label)
  {
    $sql = "INSERT INTO llx_bank_url (fk_bank, url_id, url, label)";
    $sql .= " VALUES ('$line_id', '$url_id', '$url', '$label')";

    if ($this->db->query($sql))
      {
	$rowid = $this->db->last_insert_id();
	
	return $rowid;
      }
    else
      {
	return '';
	print $this->db->error();
	print "<br>$sql";
      }
  }
  /*
   *
   */
  Function get_url($line_id)
  {
    $lines = array();
    $sql = "SELECT fk_bank, url_id, url, label FROM llx_bank_url WHERE fk_bank = $line_id";
    $result = $this->db->query($sql);

    if ($result)
      {
	$i = 0;
	$num = $this->db->num_rows();
	while ($i < $num)
	  {
	    $obj = $this->db->fetch_object($result, $i);
	    $lines[$i][0] = $obj->url;
	    $lines[$i][1] = $obj->url_id;
	    $lines[$i][2] = $obj->label;
	    $i++;
	  }
	return $lines;
      }
  }
  /*
   * Ajoute une entree dans la table llx_bank
   *
   */
  Function addline($date, $oper, $label, $amount, $num_chq="",$categorie='')
  {
    if ($this->rowid)
      {
	switch ($oper)
	  {
	  case 1:
	    $oper = 'TIP';
	    break;
	  case 2:
	    $oper = 'VIR';
	    break;
	  case 3:
	    $oper = 'PRE';
	    break;
	  case 4:
	    $oper = 'LIQ';
	    break;
	  case 5:
	    $oper = 'WWW';
	    break;
	  case 6:
	    $oper = 'CB';
	    break;
	  case 7:
	    $oper = 'CHQ';
	    break;
	  }
	
	$sql = "INSERT INTO llx_bank (datec, dateo, label, amount, author, num_chq,fk_account, fk_type)";
	$sql .= " VALUES (now(), '$date', '$label', '" . ereg_replace(",",".",$amount) . "','$author','$num_chq', '$this->rowid', '$oper')";


	if ($this->db->query($sql))
	  {
	    $rowid = $this->db->last_insert_id();
	    if ($categorie)
	      {
		$sql = "INSERT INTO llx_bank_class (lineid, fk_categ) VALUES ('$rowid', '$categorie')";
		$result = $this->db->query($sql);
		if ($result){
		  return $rowid;
		}else{
		  //return '';	On ne quitte pas avec erreur car insertion dans bank_class peut echouer alors que insertion dans bank ok
		}
	      }
	    return $rowid;
	  }
	else
	  {
	    print $this->db->error().' in '.$sql;
	    return '';
	  }
      }
  }
  /*
   *
   *
   */
  Function create()
    {
      $sql = "INSERT INTO llx_bank_account (datec, label) values (now(),'$this->label');";
      if ($this->db->query($sql))
	{
	  if ($this->db->affected_rows()) 
	    {
	      $this->id = $this->db->last_insert_id();
	      if ( $this->update() )
		{
		  $sql = "INSERT INTO llx_bank (datec, label, amount, fk_account,datev,dateo,fk_type,rappro) ";
		  $sql .= " VALUES (now(),'Solde','" . ereg_replace(",",".",$this->solde) . "','$this->id','".$this->db->idate($this->date_solde)."','".$this->db->idate($this->date_solde)."','SOLD',1);";

		  $this->db->query($sql);
		}
	      return $this->id;      
	    }
	}
      else
	{
	  print $this->db->error();
	}
    }
  /*
   *
   *
   */

  Function update()
    {      
      if (strlen($this->label)==0)
	$this->label = "???";

      $sql = "UPDATE llx_bank_account SET ";

      $sql .= " bank = '" .$this->bank ."'";
      $sql .= ",label = '".$this->label ."'";

      $sql .= ",code_banque='".$this->code_banque."'";
      $sql .= ",code_guichet='".$this->code_guichet."'";
      $sql .= ",number='".$this->number."'";
      $sql .= ",cle_rib='".$this->cle_rib."'";
      $sql .= ",bic='".$this->bic."'";
      $sql .= ",domiciliation='".$this->domiciliation."'";
      $sql .= ",courant = ".$this->courant;
      $sql .= ",clos = ".$this->clos;
      $sql .= ",iban_prefix = '".$this->iban_prefix."'";

      $sql .= " WHERE rowid = $this->id";
      
      $result = $this->db->query($sql);
	      
      if ($result) 
	{
	  if ($this->db->affected_rows()) 
	    {
	      return 1;		      
	    }		  
	}
      else
	{
	  print $this->db->error();
	  print "<p>$sql</p>";
	  return 0;
	}
    }
  /*
   *
   *
   */
  Function fetch($id)
  {
    $this->id = $id; 
    $sql = "SELECT rowid, label, bank, number, courant, clos, code_banque,code_guichet,cle_rib,bic,iban_prefix,domiciliation FROM llx_bank_account";
    $sql .= " WHERE rowid  = ".$id;

    $result = $this->db->query($sql);

    if ($result)
      {
	if ($this->db->num_rows())
	  {
	    $obj = $this->db->fetch_object($result , 0);
	    
	    $this->bank          = $obj->bank;
	    $this->label         = $obj->label;
	    $this->courant       = $obj->courant;
	    $this->clos          = $obj->clos;
	    $this->code_banque   = $obj->code_banque;
	    $this->code_guichet  = $obj->code_guichet;
	    $this->number        = $obj->number;
	    $this->cle_rib       = $obj->cle_rib;
	    $this->bic           = $obj->bic;
	    $this->domiciliation = $obj->domiciliation;
	    $this->iban_prefix   = $obj->iban_prefix;
	  }
	$this->db->free();
      }
    else
      {
	print $this->db->error();
      }
  }
  /*
   *
   *
   */
  Function solde()
  {
    $sql = "SELECT sum(amount) FROM llx_bank WHERE fk_account=$this->id AND dateo <=" . $this->db->idate(time() );

    $result = $this->db->query($sql);

    if ($result)
      {
	if ($this->db->num_rows())
	  {
	    $solde = $this->db->result(0,0);

	    return $solde;
	  }
	$this->db->free();
      }
  }


}

?>
