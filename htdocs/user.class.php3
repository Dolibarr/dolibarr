<?PHP
/* Copyright (c) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * 
 * $Id$
 * $Source$
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
 */

class User {
  var $db;

  var $id;
  var $fullname;
  var $nom;
  var $prenom;
  var $code;
  var $email;
  var $admin;
  var $login;
  var $comm;
  var $compta;
  var $webcal_login;
  var $errorstr;
  var $limite_liste;

  Function User($DB, $id=0)
    {

      $this->db = $DB;
      $this->id = $id;
    
      $this->limite_liste = 20;

      return 1;
  }
  /*
   *
   *
   *
   */

  Function fetch($login='')
    {

    $sql = "SELECT u.rowid, u.name, u.firstname, u.email, u.code, u.admin, u.module_comm, u.module_compta, u.login, u.webcal_login";
    $sql .= " FROM llx_user as u";

    if ($this->id) {
      $sql .= " WHERE u.rowid = $this->id";
    } else {
      $sql .= " WHERE u.login = '$login'";
    }
  
    $result = $this->db->query($sql);

    if ($result) 
      {
	if ($this->db->num_rows()) 
	  {
	    $obj = $this->db->fetch_object($result , 0);

	    $this->id = $obj->rowid;
	    $this->nom = $obj->name;
	    $this->prenom = $obj->firstname;
	    
	    $this->fullname = $this->prenom . ' ' . $this->nom;
	    $this->admin = $obj->admin;
	    $this->webcal_login = $obj->webcal_login;
	    $this->code = $obj->code;
	    $this->email = $obj->email;
	    
	    $this->comm = $obj->module_comm;
	    $this->compta = $obj->module_compta;
	    
	    $this->login = $obj->login;
	    $this->pass  = $obj->pass;
	    $this->webcal_login = $obj->webcal_login;
	    
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
   *
   */
  Function update()
    {
      $sql = "SELECT login FROM llx_user WHERE login ='$this->login'";

      if ($this->db->query($sql)) 
	{
	  $num = $this->db->num_rows();
	  $this->db->free();

	  if ($num) 
	    {
	      $this->errorstr = "Ce login existe déjà";
	      return 0;
	    }
	  else
	    {            

	      $sql = "UPDATE llx_user SET ";
	      $sql .= " name = '$this->nom'";
	      $sql .= ", firstname = '$this->prenom'";
	      $sql .= ", login = '$this->login'";
	      $sql .= ", email = '$this->email'";
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
		}
	    }
	}
    }
  /*
   *
   *
   */
  Function error()
    {
      return $this->errorstr;
    }
  /*
   * Change le mot de passe et l'envoie par mail
   *
   *
   */
  Function password()
    {

      $password =  substr(crypt(uniqid("")),0,8);

      $sql = "UPDATE llx_user SET pass = '".crypt($password, "CRYPT_STD_DES")."'";
      $sql .= " WHERE rowid = $this->id";
      
      $result = $this->db->query($sql);

      if ($result) 
	{
	  if ($this->db->affected_rows()) 
	    {
	      $mesg = "Login : $this->login\nMot de passe : $password";
	      
	      if (mail($this->email, "Mot de passe Dolibarr", $mesg))
		{
		  return 1;
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
   *
   */
  

}
/*
 * $Id$
 * $Source$
 */
?>
