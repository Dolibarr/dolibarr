<?PHP
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require (DOL_DOCUMENT_ROOT."/lib/ldap.lib.php");

class Contact 
{
  var $bs;
  var $db;

  var $id;
  var $fullname;
  var $nom;
  var $prenom;
  var $code;
  var $email;

  Function Contact($DB, $id=0) 
    {
      $this->db = $DB;
      $this->id = $id;
      
      return 1;
    }
  /*
   *
   *
   *
   */
  Function create($user)
  {
    if (!$this->socid)
      {
	$this->socid = 0;
      }

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."socpeople (datec, fk_soc, name, fk_user) ";
    $sql .= " VALUES (now(),$this->socid,'$this->name',$user->id)";

    if ($this->db->query($sql) )
      {
	$id = $this->db->last_insert_id();

	$this->update($id);

	return $id;
      }
    else
      {
	print $this->db->error() . '<br>' . $sql;
      }
  }
  /*
   * Mise à jour des infos
   *
   */
  Function update($id, $user=0)
    {
      $this->email = trim($this->email);

      $sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET name='$this->name', firstname='$this->firstname'";
      $sql .= ", poste='$this->poste'";
      $sql .= ", fax='$this->fax'";
      $sql .= ", email='$this->email'";
      $sql .= ", note='$this->note'";
      $sql .= ", phone = '$this->phone_pro'";
      $sql .= ", phone_perso = '$this->phone_perso'";
      $sql .= ", phone_mobile = '$this->phone_mobile'";
      $sql .= ", jabberid = '$this->jabberid'";
      $sql .= " WHERE idp=$id";

      $result = $this->db->query($sql);

      if (!$result) 
	{
	  print $this->db->error() . '<br>' . $sql;
	}

      if (defined('MAIN_MODULE_LDAP')  && MAIN_MODULE_LDAP)
	{
	  $ds = dolibarr_ldap_connect();

	  if ($ds)
	    {
	      ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
	      
	      $ldapbind = dolibarr_ldap_bind($ds);
	      
	      if ($ldapbind)
		{
		  $info["cn"] = utf8_encode($this->firstname." ".$this->name);
		  $info["sn"] = utf8_encode($this->name);
		  
		  if ($this->email)
		    $info["rfc822Mailbox"] = $this->email;
		  
		  if ($this->phone_pro)
		    $info["telephoneNumber"] = dolibarr_print_phone($this->phone_pro);
		  
		  if ($this->phone_mobile)
		    $info["mobile"] = dolibarr_print_phone($this->phone_mobile);
		  
		  if ($this->phone_perso)
		    $info["homePhone"] = dolibarr_print_phone($this->phone_perso);
		  
		  if ($this->poste)
		    $info["title"] = utf8_encode($this->poste);
		  
		  //$info["homePostalAddress"] = "AdressePersonnelle\nVIlle";
		  //$info["street"] = "street";
		  //$info["postalCode"] = "postalCode";
		  //$info["postalAddress"] = "postalAddress";
		  
		  $info["objectclass"] = "inetOrgPerson";
		  
		  // add data to directory
		  $dn = utf8_encode("cn=".$this->old_firstname." ".$this->old_name).", ".LDAP_SERVER_DN ;
		  
		  $r = @ldap_delete($ds, $dn);
		  
		  $dn = "cn=".$info["cn"].", ".LDAP_SERVER_DN ;
		  
		  if (! ldap_add($ds, $dn, $info))
		    {
		      print ldap_errno();
		    }
		}
	      else
		{
		  echo "LDAP bind failed...";
		}	 
	      ldap_close($ds);	    
	    }
	  else
	    {
	      echo "Unable to connect to LDAP server";
	    }
	}
      return $result;
    }

  /*
   * Mise à jour des infos persos
   *
   */
  Function update_perso($id, $user=0)
    {

      $sql = "UPDATE llx_socpeople SET ";
      $sql .= " birthday='".$this->db->idate($this->birthday)."'";
      $sql .= " WHERE idp=$id";

      $result = $this->db->query($sql);

      if (!$result) 
	{
	  print $this->db->error() . '<br>' . $sql;
	}
      
      return $result;
    }


  /*
   *
   *
   */
  Function fetch($id, $user=0) 
    {
      $sql = "SELECT c.idp, c.fk_soc, c.name, c.firstname, c.email, phone, phone_perso, phone_mobile, jabberid, ".$this->db->pdate('c.birthday') ." as birthday, c.note, poste";
      $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
      $sql .= " WHERE c.idp = $id";
      
      $result = $this->db->query($sql);
      
      if ($result) 
	{
	  if ($this->db->num_rows()) 
	    {
	      $obj = $this->db->fetch_object($result , 0);

	      $this->id             = $obj->idp;
	      $this->name           = $obj->name;
	      $this->firstname      = $obj->firstname;
	      $this->nom            = $obj->name;
	      $this->prenom         = $obj->firstname;
	      $this->societeid      = $obj->fk_soc;
	      $this->socid          = $obj->fk_soc;
	      $this->poste          = $obj->poste;
	      $this->fullname       = $this->firstname . ' ' . $this->name;
	      
	      $this->phone_pro      = dolibarr_print_phone($obj->phone);
	      $this->phone_perso    = dolibarr_print_phone($obj->phone_perso);
	      $this->phone_mobile   = dolibarr_print_phone($obj->phone_mobile);

	      $this->code           = $obj->code;
	      $this->email          = $obj->email;
	      $this->jabberid       = $obj->jabberid;
	      $this->mail           = $obj->email;

	      $this->birthday       = $obj->birthday;
	      $this->birthday_alert = "";
	      $this->note           = $obj->note;
	    }
	  $this->db->free();

	  if ($user)
	    {
	      $sql = "SELECT fk_user";
	      $sql .= " FROM ".MAIN_DB_PREFIX."birthday_alert";
	      $sql .= " WHERE fk_user = $user->id AND fk_contact = $id";
	      
	      if ($this->db->query($sql)) 
		{
		  if ($this->db->num_rows())
		    {
		      $obj = $this->db->fetch_object($result , 0);
		      
		      $this->birthday_alert = 1;
		    } 
		  else 
		    {
		      print $this->db->error();
		    }
		}
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
  Function delete($id)
    {
      $sql = "DELETE FROM ".MAIN_DB_PREFIX."socpeople";
      $sql .= " WHERE idp=$id";

      $result = $this->db->query($sql);

      if (!$result) 
	{
	  print $this->db->error() . '<br>' . $sql;
	}

      if (defined('MAIN_MODULE_LDAP')  && MAIN_MODULE_LDAP)
	{
	  $ds = dolibarr_ldap_connect();
      
	  if ($ds)
	    {
	      ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
	      
	      $ldapbind = dolibarr_ldap_bind($ds);
	      
	      if ($ldapbind)
		{	      
		  // delete from ldap directory
		  $dn = utf8_encode("cn=".$this->old_firstname." ".$this->old_name.", ".LDAP_SERVER_DN) ;
		  
		  $r = @ldap_delete($ds, $dn);
		}
	      else
		{
		  echo "LDAP bind failed...";
		}	      	      
	      ldap_close($ds);
	    
	    }
	  else
	    {
	      echo "Unable to connect to LDAP server";
	    }
	}
      
      return $result;
    }
  /*
   * Information sur l'objet
   *
   */
  Function info($id) 
    {
      $sql = "SELECT c.idp, ".$this->db->pdate("datec")." as datec, fk_user";
      $sql .= ", ".$this->db->pdate("tms")." as tms";
      $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
      $sql .= " WHERE c.idp = $id";
      
      if ($this->db->query($sql)) 
	{
	  if ($this->db->num_rows()) 
	    {
	      $obj = $this->db->fetch_object($result , 0);

	      $this->id                = $obj->idp;

	      $cuser = new User($this->db, $obj->fk_user);
	      $cuser->fetch();

	      $this->user_creation     = $cuser;

	      $this->date_creation     = $obj->datec;
	      $this->date_modification = $obj->tms;

	    }
	  $this->db->free();

	}
      else
	{
	  print $this->db->error();
	}
    }
}
?>
