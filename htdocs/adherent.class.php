<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo <jlb@j1b.org>
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

class Adherent
{
  var $id;
  var $db;
  var $prenom;
  var $nom;
  var $societe;
  var $adresse;
  var $cp;
  var $ville;
  var $pays;
  var $typeid;
  var $morphy;
  var $email;
  var $public;
  var $commentaire;
  var $statut;
  var $login;
  var $pass;
  var $naiss;
  var $photo;
  var $public;
  var $array_options;

  var $errorstr;
  /*
   *
   *
   */
  Function Adherent($DB, $id='') 
    {
      $this->db = $DB ;
      $this->id = $id;
      $this->statut = -1;
      // l'adherent n'est pas public par defaut
      $this->public = 0;
      // les champs optionnels sont vides
      $this->array_options=array();
    }
  /*
   *
   *
   *
   */
  Function print_error_list()
  {
    $num = sizeof($this->errorstr);
    for ($i = 0 ; $i < $num ; $i++)
      {
	print "<li>" . $this->errorstr[$i];
      }
  }
  /*
   *
   *
   */
  Function check($minimum=0) 
    {
      $err = 0;

      if (strlen(trim($this->societe)) == 0)
	{
	  if ((strlen(trim($this->nom)) + strlen(trim($this->prenom))) == 0)
	    {
	      $error_string[$err] = "Vous devez saisir vos nom et prénom ou le nom de votre société.";
	      $err++;
	    }
	}

      if (strlen(trim($this->adresse)) == 0)
	{
	  $error_string[$err] = "L'adresse saisie est invalide";
	  $err++;
	}

      if (strlen(trim($this->cp)) == 0)
	{
	  $error_string[$err] = "Le code postal saisi est invalide";
	  $err++;
	}

      if (strlen(trim($this->ville)) == 0)
	{
	  $error_string[$err] = "La ville saisie est invalide";
	  $err++;
	}

      if (strlen(trim($this->email)) == 0)
	{
	  $error_string[$err] = "L'email saisi est invalide";
	  $err++;
	}

      if (strlen(trim($this->login)) == 0)
	{
	  $error_string[$err] = "Le login saisi est invalide";
	  $err++;
	}

      if (strlen(trim($this->pass)) == 0)
	{
	  $error_string[$err] = "Le pass saisi est invalide";
	  $err++;
	}
      $this->amount = trim($this->amount);

      $map = range(0,9);
      for ($i = 0; $i < strlen($this->amount) ; $i++)
	{
	  if (!isset($map[substr($this->amount, $i, 1)] ))
	    {
	      $error_string[$err] = "Le montant du don contient un/des caractère(s) invalide(s)";
	      $err++;
	      $amount_invalid = 1;
	      break;
	    } 	      
	}

      if (! $amount_invalid)
	{
	  if ($this->amount == 0)
	    {
	      $error_string[$err] = "Le montant du don est null";
	      $err++;
	    }
	  else
	    {
	      if ($this->amount < $minimum && $minimum > 0)
		{
		  $error_string[$err] = "Le montant minimum du don est de $minimum";
		  $err++;
		}
	    }
	}
      
      /*
       * Return errors
       *
       */

      if ($err)
	{
	  $this->errorstr = $error_string;
	  return 0;
	}
      else
	{
	  return 1;
	}

    }
  /*
   * Création
   *
   *
   */
  Function create($userid) 
    {
      /*
       *  Insertion dans la base
       */

      $this->date = $this->db->idate($this->date);

      $sql = "INSERT INTO llx_adherent (datec)";
      $sql .= " VALUES (now())";
      
      $result = $this->db->query($sql);
      
      if ($result) 
	{
	  $this->id = $this->db->last_insert_id();
	  return $this->update();
	}
      else
	{
	  print $this->db->error();
	  print "<h2><br>$sql<br></h2>";
	  return 0;
	}  
    }

  /*
   * Mise à jour
   *
   *
   */
  Function update() 
    {
      

      $sql = "UPDATE llx_adherent SET ";
      $sql .= "prenom = '".$this->prenom ."'";
      $sql .= ",nom='".$this->nom."'";
      $sql .= ",societe='".$this->societe."'";
      $sql .= ",adresse='".$this->adresse."'";
      $sql .= ",cp='".$this->cp."'";
      $sql .= ",ville='".$this->ville."'";
      $sql .= ",pays='".$this->pays."'";
      $sql .= ",note='".$this->commentaire."'";
      $sql .= ",email='".$this->email."'";
      $sql .= ",login='".$this->login."'";
      $sql .= ",pass='".$this->pass."'";
      $sql .= ",naiss='".$this->naiss."'";
      $sql .= ",photo='".$this->photo."'";
      $sql .= ",public='".$this->public."'";
      $sql .= ",statut=".$this->statut;
      $sql .= ",fk_adherent_type=".$this->typeid;
      $sql .= ",morphy='".$this->morphy."'";

      $sql .= " WHERE rowid = $this->id";
      
      $result = $this->db->query($sql);
    
      if (!$result)
	{
	  print $this->db->error();
	  print "<h2><br>$sql<br></h2>";
	  return 0;
	}
      if(sizeof($this->array_options) > 0 ){
	$sql = "REPLACE INTO llx_adherent_options SET adhid = $this->id";
	foreach($this->array_options as $key => $value){
	  // recupere le nom de l'attribut
	  $attr=substr($key,8);
	  $sql.=",$attr = '".$this->array_options[$key]."'";
	}
	$result = $this->db->query($sql);
      }
	
      if ($result) 
	{
	  return 1;
	}
      else
	{
	  print $this->db->error();
	  print "<h2><br>$sql<br></h2>";
	  return 0;
	}  
    }

  /*
   * Suppression de l'adhérent
   *
   */
  Function delete($rowid)

  {
    $result = 0;
    $sql = "DELETE FROM llx_adherent WHERE rowid = $rowid";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {

	    $sql = "DELETE FROM llx_cotisation WHERE fk_adherent = $rowid";
	    if ( $this->db->query( $sql) )
	      {
		if ( $this->db->affected_rows() )
		  {
		    $result = 1;
		  }
	      }
	    $sql = "DELETE FROM llx_adherent_options WHERE adhid = $rowid";
	    if ( $this->db->query( $sql) )
	      {
		if ( $this->db->affected_rows() )
		  {
		    $result = 1;
		  }
	      }
	  }
      }
    else
      {
	print "Err : ".$this->db->error();
      }

    return $result;

  }
  /*
   * Fetch
   *
   *
   */
  /* Fetch adherent corresponding to login passed in argument */
  Function fetch_login($login){
    $sql = "SELECT rowid FROM llx_adherent WHERE login='$login' LIMIT 1";
    if ( $this->db->query( $sql) ){
      if ($this->db->num_rows()){
	$obj = $this->db->fetch_object(0);
	$this->fetch($obj->rowid);
      }
    }else{
      print $this->db->error();
    }
  }
  /* Fetch adherent corresponding to rowid passed in argument */
  Function fetch($rowid)
  {
    $sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, d.statut, d.public, d.adresse, d.cp, d.ville, d.pays, d.note, d.email, d.login, d.pass, d.naiss, d.photo, d.fk_adherent_type, d.morphy, t.libelle as type";
    $sql .= ",".$this->db->pdate("d.datefin")." as datefin";
    $sql .= " FROM llx_adherent as d, llx_adherent_type as t";
    $sql .= " WHERE d.rowid = $rowid AND d.fk_adherent_type = t.rowid";

    if ( $this->db->query( $sql) )
      {
	if ($this->db->num_rows())
	  {

	    $obj = $this->db->fetch_object(0);

	    $this->id             = $obj->rowid;
	    $this->typeid         = $obj->fk_adherent_type;
	    $this->type           = $obj->type;
	    $this->statut         = $obj->statut;
	    $this->public         = $obj->public;
	    $this->date           = $obj->datedon;
	    $this->prenom         = stripslashes($obj->prenom);
	    $this->nom            = stripslashes($obj->nom);
	    $this->societe        = stripslashes($obj->societe);
	    $this->adresse        = stripslashes($obj->adresse);
	    $this->cp             = stripslashes($obj->cp);
	    $this->ville          = stripslashes($obj->ville);
	    $this->email          = stripslashes($obj->email);
	    $this->login          = stripslashes($obj->login);
	    $this->pass           = stripslashes($obj->pass);
	    $this->naiss          = stripslashes($obj->naiss);
	    $this->photo          = stripslashes($obj->photo);
	    $this->pays           = stripslashes($obj->pays);
	    $this->datefin        = $obj->datefin;
	    $this->commentaire    = stripslashes($obj->note);
	    $this->morphy         = $obj->morphy;
	  }
      }
    else
      {
	print $this->db->error();
      }
    
  }
  
  /*
   * fetch optional attribute
   */
  Function fetch_optionals($rowid)
  {
    $tab=array();
    $sql = "SELECT *";
    $sql .= " FROM llx_adherent_options";
    $sql .= " WHERE adhid=$rowid";
    
    if ( $this->db->query( $sql) ){
	if ($this->db->num_rows()){
	  
	  //$obj = $this->db->fetch_object(0);
	  $tab = $this->db->fetch_array();
	  
	  foreach ($tab as $key => $value){
	    if ($key != 'optid' && $key != 'tms' && $key != 'adhid'){
	      // we can add this attribute to adherent object
	      $this->array_options["options_$key"]=$value;
	    }
	  }
	}
    }else{
      print $this->db->error();
    }
    
  }

  /*
   * fetch optional attribute name
   */
  Function fetch_name_optionals()
  {
    $array_name_options=array();
    $sql = "SHOW COLUMNS FROM llx_adherent_options";

    if ( $this->db->query( $sql) ){
      if ($this->db->num_rows()){
	//$tab = $this->db->fetch_object();
	//$array_name_options[]=$tab->Field;
	while ($tab = $this->db->fetch_object()){
	  if ($tab->Field != 'optid' && $tab->Field != 'tms' && $tab->Field != 'adhid'){
	    // we can add this attribute to adherent object
	    $array_name_options[]=$tab->Field;
	  }
	}
	return $array_name_options;
      }else{
	return array();
      }
    }else{
      print $this->db->error();
      return array() ;
    }
    
  }
  /*
   * Cotisation
   *
   */
  Function cotisation($date, $montant)

  {
    
    $sql = "INSERT INTO llx_cotisation (fk_adherent, dateadh, cotisation)";
    $sql .= " VALUES ($this->id, ".$this->db->idate($date).", $montant)";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {

	    $datefin = mktime(12, 0 , 0, 
			      strftime("%m",$date), 
			      strftime("%d",$date),
			      strftime("%Y",$date)+1) - (24 * 3600);

	    $sql = "UPDATE llx_adherent SET datefin = ".$this->db->idate($datefin)." WHERE rowid =". $this->id;

	    if ( $this->db->query( $sql) )
	      {
	      return 1;
	      }
	  }
	else
	  {
	    return 0;
	  }
      }
    else
      {
	print "Err : ".$this->db->error();
	return 0;
      }    
  }



  /*
   * Validation
   *
   *
   */
  Function validate($userid) 
    {
      
      $sql = "UPDATE llx_adherent SET ";
      $sql .= "statut=1";
      $sql .= ",fk_user_valid=".$userid;

      $sql .= " WHERE rowid = $this->id";
      
      $result = $this->db->query($sql);
      
      if ($result) 
	{
	  return 1;
	}
      else
	{
	  print $this->db->error();
	  print "<h2><br>$sql<br></h2>";
	  return 0;
	}  
    }
  /*
   * Résiliation
   *
   *
   */
  Function resiliate($userid) 
    {
      
      $sql = "UPDATE llx_adherent SET ";
      $sql .= "statut=0";
      $sql .= ",fk_user_valid=".$userid;

      $sql .= " WHERE rowid = $this->id";
      
      $result = $this->db->query($sql);
      
      if ($result) 
	{
	  return 1;
	}
      else
	{
	  print $this->db->error();
	  print "<h2><br>$sql<br></h2>";
	  return 0;
	}  
    }


}
?>
