<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
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

/*!
	    \file       htdocs/compta/bank/account.class.php
        \ingroup    banque
		\brief      Fichier de la classe des comptes bancaires
		\version    $Revision$
*/


/*! \class Account
		\brief      Classe permettant la gestion des comptes bancaires
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
  var $proprio;
  var $adresse_proprio;

  function Account($DB, $rowid=0)
  {
    global $config;
    
    $this->db = $DB;
    $this->rowid = $rowid;

    $this->clos = 0;
    $this->solde = 0;    

    return 1;
  }

  /*
   * Efface une entree dans la table ".MAIN_DB_PREFIX."bank
   */

  function deleteline($rowid)
  {
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid=$rowid";
    $result = $this->db->query($sql);

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank WHERE rowid=$rowid";
    $result = $this->db->query($sql);

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_url WHERE fk_bank=$rowid";
    $result = $this->db->query($sql);
  }
  /*
   *
   *
   */
  function add_url_line($line_id, $url_id, $url, $label)
  {
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_url (fk_bank, url_id, url, label)";
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
  function get_url($line_id)
  {
    $lines = array();
    $sql = "SELECT fk_bank, url_id, url, label FROM ".MAIN_DB_PREFIX."bank_url WHERE fk_bank = $line_id";
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
   * Ajoute une entree dans la table ".MAIN_DB_PREFIX."bank
   *
   */
  function addline($date, $oper, $label, $amount, $num_chq='', $categorie='',$user)
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
	    $oper = 'VAD';
	    break;
	  case 6:
	    $oper = 'CB';
	    break;
	  case 7:
	    $oper = 'CHQ';
	    break;
	  }
	
	$datev = $date;

	$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank (datec, dateo, datev, label, amount, fk_user_author, num_chq,fk_account, fk_type)";
	$sql .= " VALUES (now(), '$date', '$datev', '$label', '" . ereg_replace(",",".",$amount) . "', '$user->id' ,'$num_chq', '$this->rowid', '$oper')";


	if ($this->db->query($sql))
	  {
	    $rowid = $this->db->last_insert_id();
	    if ($categorie)
	      {
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_class (lineid, fk_categ) VALUES ('$rowid', '$categorie')";
		$result = $this->db->query($sql);
		if ($result)
		  {
		    return $rowid;
		  }
		else
		  {
		  //return '';	On ne quitte pas avec erreur car insertion dans bank_class peut echouer alors que insertion dans bank ok
		}
	      }
	    return $rowid;
	  }
	else
	  {
	    dolibarr_print_error($this->db);
	    return '';
	  }
      }
  }

  /*
   * Creation du compte bancaire
   *
   */
  function create()
    {
      // Chargement librairie pour acces fonction controle RIB
	  require_once DOL_DOCUMENT_ROOT . '/compta/bank/bank.lib.php';

      if (! verif_rib($this->code_banque,$this->code_guichet,$this->number,$this->cle_rib,$this->iban_prefix)) {
            $this->error="Le contrôle de la clé indique que les informations de votre compte bancaire sont incorrectes.";
            return 0;
      }

      if (! $pcgnumber) {
          // TODO
          // Prendre comme de numero compte comptable pour le compte bancaire, le numero par defaut pour plan de compte actif
          $pcgnumber="51";
      }

      $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_account (datec, label, account_number) values (now(),'$this->label','$pcgnumber');";
      if ($this->db->query($sql))
	{
	  if ($this->db->affected_rows()) 
	    {
	      $this->id = $this->db->last_insert_id();
	      if ( $this->update() )
		{
		  $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank (datec, label, amount, fk_account,datev,dateo,fk_type,rappro) ";
		  $sql .= " VALUES (now(),'Solde','" . ereg_replace(",",".",$this->solde) . "','$this->id','".$this->db->idate($this->date_solde)."','".$this->db->idate($this->date_solde)."','SOLD',1);";

		  $this->db->query($sql);
		}
	      return $this->id;      
	    }
	}
      else
	{
	  dolibarr_print_error($this->db);
	  return 0;
	}
    }

  /*
   *
   *
   */
  function update($user='')
    {      
      // Chargement librairie pour acces fonction controle RIB
	  require_once DOL_DOCUMENT_ROOT . '/compta/bank/bank.lib.php';

      if (! verif_rib($this->code_banque,$this->code_guichet,$this->number,$this->cle_rib,$this->iban_prefix)) {
            $this->error="Le contrôle de la clé indique que les informations de votre compte bancaire sont incorrectes.";
            return 0;
      }

      if (! $this->label) $this->label = "???";

      $sql = "UPDATE ".MAIN_DB_PREFIX."bank_account SET ";

      $sql .= " bank = '" .$this->bank ."'";
      $sql .= ",label = '".$this->label ."'";

      $sql .= ",code_banque='".$this->code_banque."'";
      $sql .= ",code_guichet='".$this->code_guichet."'";
      $sql .= ",number='".$this->number."'";
      $sql .= ",cle_rib='".$this->cle_rib."'";
      $sql .= ",bic='".$this->bic."'";
      $sql .= ",iban_prefix = '".$this->iban_prefix."'";
      $sql .= ",domiciliation='".$this->domiciliation."'";
      $sql .= ",proprio = '".$this->proprio."'";
      $sql .= ",adresse_proprio = '".$this->adresse_proprio."'";
      $sql .= ",courant = ".$this->courant;
      $sql .= ",clos = ".$this->clos;

      $sql .= " WHERE rowid = ".$this->id;
      
      $result = $this->db->query($sql);
	      
      if ($result) 
	{
      return 1;		      
	}
      else
	{
	  dolibarr_print_error($this->db);
	  return 0;
	}
    }

  /*
   *
   *
   */
  function fetch($id)
  {
    $this->id = $id; 
    $sql = "SELECT rowid, label, bank, number, courant, clos, code_banque, code_guichet, cle_rib, bic, iban_prefix, domiciliation, proprio, adresse_proprio FROM ".MAIN_DB_PREFIX."bank_account";
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
	    $this->iban_prefix   = $obj->iban_prefix;
	    $this->domiciliation = $obj->domiciliation;
	    $this->proprio       = $obj->proprio;
	    $this->adresse_proprio = $obj->adresse_proprio;
	  }
	$this->db->free();
      }
    else
      {
	    dolibarr_print_error($this->db);
      }
  }

  /*
   *
   *
   */
  function error()
    {      
        return $this->error;
    }
    
  /*
   *
   *
   */
  function solde()
  {
    $sql = "SELECT sum(amount) FROM ".MAIN_DB_PREFIX."bank WHERE fk_account=$this->id AND dateo <=" . $this->db->idate(time() );

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
  /*
   *
   *
   */
  function datev_next($rowid)
    {      
      $sql = "UPDATE ".MAIN_DB_PREFIX."bank SET ";

      $sql .= " datev = adddate(datev, interval 1 day)";

      $sql .= " WHERE rowid = $rowid";
      
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
  function datev_previous($rowid)
    {      
      $sql = "UPDATE ".MAIN_DB_PREFIX."bank SET ";

      $sql .= " datev = adddate(datev, interval -1 day)";

      $sql .= " WHERE rowid = $rowid";
      
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


}

?>
