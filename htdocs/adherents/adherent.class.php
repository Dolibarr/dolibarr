<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!	\file adherent.class.php
		\brief Fichier de la classe permettant la gestion d'un adhérent
		\author Rodolphe Qiedeville
		\author	Jean-Louis Bergamo
		\author	Laurent Destailleur
		\version $Revision$
*/

/*! \class Adherent
		\brief Classe permettant la gestion d'un adhérent
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
  //  var $public;
  var $array_options;

  var $errorstr;

/*!
		\brief Adherent
		\param DB		base de données
		\param id		id de l'adhérent
*/

  function Adherent($DB, $id='')
  {
    $this->db = $DB ;
    $this->id = $id;
    $this->statut = -1;
    // l'adherent n'est pas public par defaut
    $this->public = 0;
    // les champs optionnels sont vides
    $this->array_options=array();
  }


/*!
		\brief	function envoyant un email au destinataire (recipient) avec le text fourni en parametre.
		\param	recipients		destinataires
		\param	text					contenu du message
		\param	subject				sujet du message
		\remarks	La particularite de cette fonction est de remplacer certains champs
		\remarks	par leur valeur pour l'adherent en l'occurrence :
		\remarks	%PRENOM% : est remplace par le prenom
		\remarks	%NOM% : est remplace par nom
		\remarks	%INFOS% : l'ensemble des attributs de cet adherent
		\remarks	%SERVEUR% : URL du serveur web
		\remarks	etc..
*/

  function send_an_email($recipients,$text,$subject="Vos coordonnees sur %SERVEUR%")
  {
    $patterns = array (
		       '/%PRENOM%/',
		       '/%NOM%/',
		       '/%INFOS%/',
		       '/%INFO%/',
		       '/%SERVEUR%/',
		       '/%SOCIETE%/',
		       '/%ADRESSE%/',
		       '/%CP%/',
		       '/%VILLE%/',
		       '/%PAYS%/',
		       '/%EMAIL%/',
		       '/%NAISS%/',
		       '/%PHOTO%/',
		       '/%LOGIN%/',
		       '/%PASS%/'
		       );
    $infos = "Prenom : $this->prenom\nNom : $this->nom\nSociete : $this->societe\nAdresse : $this->adresse\nCP : $this->cp\nVille : $this->ville\nPays : $this->pays\nEmail : $this->email\nLogin : $this->login\nPassword : $this->pass\nDate de naissance : $this->naiss\nPhoto : $this->photo\n";
    if ($this->public == 1)
      {
	$infos.="Fiche Publique : Oui\n";
      }
    else
      {
	$infos.="Fiche Publique : Non\n";
      }
    $replace = array (
		      $this->prenom,
		      $this->nom,
		      $infos,
		      $infos,
		      "http://".$_SERVER["SERVER_NAME"]."/",
		      $this->societe,
		      $this->adresse,
		      $this->cp,
		      $this->ville,
		      $this->pays,
		      $this->email,
		      $this->naiss,
		      $this->photo,
		      $this->login,
		      $this->pass
		      );
    $texttosend = preg_replace ($patterns, $replace, $text);
    $subjectosend = preg_replace ($patterns, $replace, $subject);
    if (defined('ADHERENT_MAIL_FROM') && ADHERENT_MAIL_FROM != ''){
	  return mail($recipients,$subjectosend,$texttosend,"From: ".ADHERENT_MAIL_FROM."\nReply-To: ".ADHERENT_MAIL_FROM."\nX-Mailer: PHP/" . phpversion());
    }else{
      return mail($recipients,$subjectosend,$texttosend);
    }
  }

/*!
		\brief	imprime une liste d'erreur.
*/

  function print_error_list()
  {
    $num = sizeof($this->errorstr);
    for ($i = 0 ; $i < $num ; $i++)
      {
	print "<li>" . $this->errorstr[$i];
      }
  }


/*!
		\brief fonction qui renvoie la nature physique ou morale d'un adherent
		\param	morphy		nature physique ou morale de l'adhérent
*/

  function getmorphylib($morphy='')
  {
    if (! $morphy) { $morphy=$this->morphy; }
    if ($morphy == 'phy') { return "Physique"; }
    if ($morphy == 'mor') { return "Morale"; }
    return $morphy;
  }

/*!
		\brief fonction qui vérifie les données entrées
		\param	minimum
*/

  function check($minimum=0)
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

/*!
		\brief fonction qui crée l'adhérent
		\param	userid		userid de l'adhérent
*/

  function create($userid)
    {
      /*
       *  Insertion dans la base
       */

      $this->date = $this->db->idate($this->date);

      $sql = "INSERT INTO ".MAIN_DB_PREFIX."adherent (datec)";
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

/*!
		\brief fonction qui met à jour l'adhérent
*/

  function update() 
    {

      $sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET ";
      $sql .= "prenom = '".$this->prenom ."'";
      $sql .= ",nom='"    .$this->nom."'";
      $sql .= ",societe='".$this->societe."'";
      $sql .= ",adresse='".$this->adresse."'";
      $sql .= ",cp='"     .$this->cp."'";
      $sql .= ",ville='"  .$this->ville."'";
      $sql .= ",pays='"   .$this->pays."'";
      $sql .= ",note='"   .$this->commentaire."'";
      $sql .= ",email='"  .$this->email."'";
      $sql .= ",login='"  .$this->login."'";
      $sql .= ",pass='"   .$this->pass."'";
      $sql .= ",naiss='"  .$this->naiss."'";
      $sql .= ",photo='"  .$this->photo."'";
      $sql .= ",public='" .$this->public."'";
      $sql .= ",statut="  .$this->statut;
      $sql .= ",fk_adherent_type=".$this->typeid;
      $sql .= ",morphy='".$this->morphy."'";

      $sql .= " WHERE rowid = $this->id";
      
      $result = $this->db->query($sql);
    
      if (!$result)
	{
	  print $this->db->error();
	  print "<br>$sql<br>";
	  return 0;
	}

      if (sizeof($this->array_options) > 0 )
	{
	  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."adherent_options SET adhid = $this->id";
	  foreach($this->array_options as $key => $value)
	    {
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

/*!
		\brief fonction qui supprime l'adhérent et les données associées
		\param	rowid
*/

  function delete($rowid)

  {
    $result = 0;
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent WHERE rowid = $rowid";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {

	    $sql = "DELETE FROM ".MAIN_DB_PREFIX."cotisation WHERE fk_adherent = $rowid";
	    if ( $this->db->query( $sql) )
	      {
		if ( $this->db->affected_rows() )
		  {
		    $result = 1;
		  }
	      }
	    $sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent_options WHERE adhid = $rowid";
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

/*!
		\brief fonction qui récupére l'adhérent en donnant son login
		\param	login		login de l'adhérent
*/

	function fetch_login($login)
  {
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."adherent WHERE login='$login' LIMIT 1";
    if ( $this->db->query( $sql) )
      {
	if ($this->db->num_rows())
	  {
	    $obj = $this->db->fetch_object(0);
	    $this->fetch($obj->rowid);
	  }
      }
    else
      {
	print $this->db->error();
      }
  }

/*!
		\brief fonction qui récupére l'adhérent en donnant son rowid
		\param	rowid
*/


	function fetch($rowid)
  {
    $sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, d.statut, d.public, d.adresse, d.cp, d.ville, d.pays, d.note, d.email, d.login, d.pass, d.naiss, d.photo, d.fk_adherent_type, d.morphy, t.libelle as type";
    $sql .= ",".$this->db->pdate("d.datefin")." as datefin";
    $sql .= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
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
  
/*!
		\brief fonction qui récupére les données optionelles de l'adhérent
		\param	rowid
*/

	function fetch_optionals($rowid)
  {
    $tab=array();
    $sql = "SELECT *";
    $sql .= " FROM ".MAIN_DB_PREFIX."adherent_options";
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
  function fetch_name_optionals()
  {
    $array_name_options=array();
    $sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."adherent_options";

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
/*!
		\brief fonction qui insèe la cotisation dans la base de données
		\param	date
		\param	montant
*/

  function cotisation($date, $montant)

  {

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."cotisation (fk_adherent, dateadh, cotisation)";
    $sql .= " VALUES ($this->id, ".$this->db->idate($date).", $montant)";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {
	    $rowid=$this->db->last_insert_id();
	    $datefin = mktime(12, 0 , 0,
			      strftime("%m",$date),
			      strftime("%d",$date),
			      strftime("%Y",$date)+1) - (24 * 3600);

	    $sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET datefin = ".$this->db->idate($datefin)." WHERE rowid =". $this->id;

	    if ( $this->db->query( $sql) )
	      {
	      return $rowid;
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

/*!
		\brief fonction qui vérifie que l'utilisateur est valide
		\param	userid		userid de l'adhérent
*/

  function validate($userid)
    {

      $sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET ";
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

/*!
		\brief fonction qui résilie un adhérent
		\param	userid		userid de de l'adhérent
*/

  function resiliate($userid)
    {

      $sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET ";
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


/*!
		\brief fonction qui ajoute l'adhérent au abonnements automatiques
		\param	adht
		\remarks	mailing-list, spip, glasnost, etc...
*/

	function add_to_abo($adht)
    {
      $err=0;
      // mailman
      if (defined("ADHERENT_USE_MAILMAN") && ADHERENT_USE_MAILMAN == 1)
	{
	  if(!$this->add_to_mailman())
	    {
	      $err+=1;
	    }
	}

      if ($adht->vote == 'yes' &&
	  defined("ADHERENT_USE_GLASNOST") && ADHERENT_USE_GLASNOST ==1 &&
	  defined("ADHERENT_USE_GLASNOST_AUTO") && ADHERENT_USE_GLASNOST_AUTO ==1
	  )
	{
	  if(!$this->add_to_glasnost()){
	    $err+=1;
	  }
	}
      if (
	  defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
	  defined("ADHERENT_USE_SPIP_AUTO") && ADHERENT_USE_SPIP_AUTO ==1
	  )
	{
	  if(!$this->add_to_spip()){
	    $err+=1;
	  }
	}
      if ($err>0){
	// error
	return 0;
      }else{
	return 1;
      }
    }

/*!
		\brief fonction qui supprime l'adhérent des abonnements automatiques
		\param	adht
		\remarks	mailing-list, spip, glasnost, etc...
*/

  function del_to_abo($adht)
    {
      $err=0;
      // mailman
      if (defined("ADHERENT_USE_MAILMAN") && ADHERENT_USE_MAILMAN == 1)
	{
	  if(!$this->del_to_mailman()){
	    $err+=1;
	  }
	}
      if ($adht->vote == 'yes' &&
	  defined("ADHERENT_USE_GLASNOST") && ADHERENT_USE_GLASNOST ==1 &&
	  defined("ADHERENT_USE_GLASNOST_AUTO") && ADHERENT_USE_GLASNOST_AUTO ==1
	  )
	{
	  if(!$this->del_to_glasnost()){
	    $err+=1;
	  }
	}
      if (
	  defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
	  defined("ADHERENT_USE_SPIP_AUTO") && ADHERENT_USE_SPIP_AUTO ==1
	  )
	{
	  if(!$this->del_to_spip()){
	    $err+=1;
	  }
	}
      if ($err>0){
	// error
	return 0;
      }else{
	return 1;
      }
    }

/*!
		\brief fonction qui donne les droits rédacteurs dans spip
*/

	function add_to_spip()
    {
      if (defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
	  defined('ADHERENT_SPIP_SERVEUR') && ADHERENT_SPIP_SERVEUR != '' &&
	  defined('ADHERENT_SPIP_USER') && ADHERENT_SPIP_USER != '' &&
	  defined('ADHERENT_SPIP_PASS') && ADHERENT_SPIP_PASS != '' &&
	  defined('ADHERENT_SPIP_DB') && ADHERENT_SPIP_DB != ''
	  ){
	$mdpass=md5($this->pass);
	$htpass=crypt($this->pass,initialiser_sel());
	$query = "INSERT INTO spip_auteurs (nom, email, login, pass, htpass, alea_futur, statut) VALUES(\"".$this->prenom." ".$this->nom."\",\"".$this->email."\",\"".$this->login."\",\"$mdpass\",\"$htpass\",FLOOR(32000*RAND()),\"1comite\")";
	//      $mydb=new Db('mysql',ADHERENT_SPIP_SERVEUR,ADHERENT_SPIP_USER,ADHERENT_SPIP_PASS,ADHERENT_SPIP_DB);
      $mydb=new DoliDb('mysql',ADHERENT_SPIP_SERVEUR,ADHERENT_SPIP_USER,ADHERENT_SPIP_PASS,ADHERENT_SPIP_DB);
      $result = $mydb->query($query);

      if ($result)
	{
	  $mydb->close();
	  return 1;
	}
      else
	{
	  $this->errorstr=$mydb->error();
	  return 0;
	}
      }
    }

/*!
		\brief fonction qui enlève les droits rédacteurs dans spip
*/

	function del_to_spip()
    {
      if (defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
	  defined('ADHERENT_SPIP_SERVEUR') && ADHERENT_SPIP_SERVEUR != '' &&
	  defined('ADHERENT_SPIP_USER') && ADHERENT_SPIP_USER != '' &&
	  defined('ADHERENT_SPIP_PASS') && ADHERENT_SPIP_PASS != '' &&
	  defined('ADHERENT_SPIP_DB') && ADHERENT_SPIP_DB != ''
	  ){
	$query = "DELETE FROM spip_auteurs WHERE login='".$this->login."'";
	$mydb=new DoliDb('mysql',ADHERENT_SPIP_SERVEUR,ADHERENT_SPIP_USER,ADHERENT_SPIP_PASS,ADHERENT_SPIP_DB);
	$result = $mydb->query($query);

	if ($result)
	  {
	    $mydb->close();
	    return 1;
	  }
	else
	  {
	    $this->errorstr=$mydb->error();
	    return 0;
	  }
      }
    }

/*!
		\brief fonction qui dit si cet utilisateur est rédacteur dans spip
*/

	function is_in_spip()
    {
      if (defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
	  defined('ADHERENT_SPIP_SERVEUR') && ADHERENT_SPIP_SERVEUR != '' &&
	  defined('ADHERENT_SPIP_USER') && ADHERENT_SPIP_USER != '' &&
	  defined('ADHERENT_SPIP_PASS') && ADHERENT_SPIP_PASS != '' &&
	  defined('ADHERENT_SPIP_DB') && ADHERENT_SPIP_DB != ''
	  ){
	$query = "SELECT login FROM spip_auteurs WHERE login='".$this->login."'";
	$mydb=new DoliDb('mysql',ADHERENT_SPIP_SERVEUR,ADHERENT_SPIP_USER,ADHERENT_SPIP_PASS,ADHERENT_SPIP_DB);
	$result = $mydb->query($query);

	if ($result)
	  {
	    if ($mydb->num_rows()){
	      # nous avons au moins une reponse
	      $mydb->close();
	      return 1;
	    }else{
	      # nous n'avons pas de reponse => n'existe pas
	      $mydb->close();
	      return 0;
	    }
	  }
	else
	  {
	    # error
	    $this->errorstr=$mydb->error();
	    return -1;
	  }
      }
    }

/*!
		\brief fonction qui ajoute l'utilisateur dans glasnost
*/

	function add_to_glasnost()
    {
      if (defined("ADHERENT_USE_GLASNOST") && ADHERENT_USE_GLASNOST ==1 &&
	  defined('ADHERENT_GLASNOST_SERVEUR') && ADHERENT_GLASNOST_SERVEUR != '' &&
	  defined('ADHERENT_GLASNOST_USER') && ADHERENT_GLASNOST_USER != '' &&
	  defined('ADHERENT_GLASNOST_PASS') && ADHERENT_GLASNOST_PASS != ''
	  ){
	// application token is not useful here
	$applicationtoken='';
	list($success, $response) =
	  XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			 'callGateway',
			 array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/authentication"),
			       XMLRPC_prepare('getUserIdAndToken'),
			       XMLRPC_prepare(array("glasnost://".ADHERENT_GLASNOST_SERVEUR."/authentication","$applicationtoken",ADHERENT_GLASNOST_USER,ADHERENT_GLASNOST_PASS))
			       )
			 );
	if ($success){
	  $userid=$response[0];
	  $usertoken=$response[1];
	}else{
	  $this->errorstr=$response['faultString'];
	  return 0;
	}

	list($success,$response)=
	  XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			 'callGateway',
			 array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/people"),
			       XMLRPC_prepare('addObject'),
			       XMLRPC_prepare(array(
						    "glasnost://".ADHERENT_GLASNOST_SERVEUR."/people",
						    "$applicationtoken",
						    $usertoken,
						    array(
							  '__thingCategory__'=>'object',
							  '__thingName__'=>  'Person',
							  'firstName'=>$this->prenom,
							  'lastName'=>$this->nom,
							  'login'=>$this->login,
							  'email'=>$this->email
							  )
						    )
					      )
			       )
			 );
	if ($success){
	  $personid=$response[0];
	}else{
	  $this->errorstr=$response['faultString'];
	  return 0;
	}
	return 1;
      }else{
	$this->errorstr="Constantes de connection non definies";
	return 0;
      }
    }

/*!
		\brief fonction qui enlève l'utilisateur de glasnost
*/

	function del_to_glasnost()
    {
      if (defined("ADHERENT_USE_GLASNOST") && ADHERENT_USE_GLASNOST ==1 &&
	  defined('ADHERENT_GLASNOST_SERVEUR') && ADHERENT_GLASNOST_SERVEUR != '' &&
	  defined('ADHERENT_GLASNOST_USER') && ADHERENT_GLASNOST_USER != '' &&
	  defined('ADHERENT_GLASNOST_PASS') && ADHERENT_GLASNOST_PASS != ''
	  ){
	// application token is not useful here
	$applicationtoken='';
	list($success, $response) =
	  XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			 'callGateway',
			 array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/authentication"),
			       XMLRPC_prepare('getUserIdAndToken'),
			       XMLRPC_prepare(array("glasnost://".ADHERENT_GLASNOST_SERVEUR."/authentication","$applicationtoken",ADHERENT_GLASNOST_USER,ADHERENT_GLASNOST_PASS))
			       )
			 );
	if ($success){
	  $userid=$response[0];
	  $usertoken=$response[1];
	}else{
	  return 0;
	}
	// recuperation du personID
	list($success,$response)=
	  XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			 'callGateway',
			 array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/people"),
			       XMLRPC_prepare('getObjectByLogin'),
			       XMLRPC_prepare(array(
						    "glasnost://".ADHERENT_GLASNOST_SERVEUR."/people",
						    "$applicationtoken",
						    $usertoken,
						    $this->login
						    )
					      )
			       )
			 );
	if ($success){
	  $personid=$response['id'];
	}else{
	  $this->errorstr=$response['faultString'];
	  return 0;
	}
	if (defined('ADHERENT_GLASNOST_DEFAULT_GROUPID') && ADHERENT_GLASNOST_DEFAULT_GROUPID != ''){
	  // recuperation des personne de ce groupe
	  list($success,$response)=
	    XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			   'callGateway',
			   array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/groups"),
				 XMLRPC_prepare('getObject'),
				 XMLRPC_prepare(array(
						      "glasnost://".ADHERENT_GLASNOST_SERVEUR."/groups",
						      "$applicationtoken",
						      $usertoken,
						      ADHERENT_GLASNOST_DEFAULT_GROUPID
						      )
						)
				 )
			   );
	  if ($success){
	    $groupids=$response['membersSet'];
	  }else{
	    $this->errorstr=$response['faultString'];
	    return 0;
	  }
	  // TODO faire la verification que le user n'est pas dans ce
	  // groupe par defaut. si il y ai il faut l'effacer et
	  // modifier le groupe
	}
	// suppression du personID
	list($success,$response)=
	  XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			 'callGateway',
			 array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/people"),
			       XMLRPC_prepare('deleteObject'),
			       XMLRPC_prepare(array(
						    "glasnost://".ADHERENT_GLASNOST_SERVEUR."/people",
						    "$applicationtoken",
						    $usertoken,
						    $personid
						    )
					      )
			       )
			 );
	if ($success){
	  return 1;
	}else{
	  $this->errorstr=$response['faultString'];
	  return 0;
	}
      }else{
	$this->errorstr="Constantes de connection non definies";
	return 0;
      }
    }

/*!
		\brief fonction qui vérifie si l'utilisateur est dans glasnost
*/

  function is_in_glasnost()
    {
      if (defined("ADHERENT_USE_GLASNOST") && ADHERENT_USE_GLASNOST ==1 &&
	  defined('ADHERENT_GLASNOST_SERVEUR') && ADHERENT_GLASNOST_SERVEUR != '' &&
	  defined('ADHERENT_GLASNOST_USER') && ADHERENT_GLASNOST_USER != '' &&
	  defined('ADHERENT_GLASNOST_PASS') && ADHERENT_GLASNOST_PASS != ''
	  ){
	// application token is not useful here
	$applicationtoken='';
	list($success, $response) =
	  XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			 'callGateway',
			 array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/authentication"),
			       XMLRPC_prepare('getUserIdAndToken'),
			       XMLRPC_prepare(array("glasnost://".ADHERENT_GLASNOST_SERVEUR."/authentication","$applicationtoken",ADHERENT_GLASNOST_USER,ADHERENT_GLASNOST_PASS))
			       )
			 );
	if ($success){
	  $userid=$response[0];
	  $usertoken=$response[1];
	}else{
	  return 0;
	}
	// recuperation du personID
	list($success,$response)=
	  XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			 'callGateway',
			 array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/people"),
			       XMLRPC_prepare('getObjectByLogin'),
			       XMLRPC_prepare(array(
						    "glasnost://".ADHERENT_GLASNOST_SERVEUR."/people",
						    "$applicationtoken",
						    $usertoken,
						    $this->login
						    )
					      )
			       )
			 );
	if ($success){
	  $personid=$response['id'];
	  return 1;
	}else{
	  $this->errorstr=$response['faultString'];
	  return 0;
	}
      }else{
	$this->errorstr="Constantes de connection non definies";
	return 0;
      }
    }

/*!
		\brief fonction qui rajoute l'utilisateur dans mailman
*/

	function add_to_mailman($listes='')
  {
    if (defined("ADHERENT_MAILMAN_URL") && ADHERENT_MAILMAN_URL != '' && defined("ADHERENT_MAILMAN_LISTS") && ADHERENT_MAILMAN_LISTS != '')
      {
	if ($listes ==''){
	  $lists=explode(',',ADHERENT_MAILMAN_LISTS);
	}else{
	  $lists=explode(',',$listes);
	}
	foreach ($lists as $list)
	  {
	    // on remplace dans l'url le nom de la liste ainsi
	    // que l'email et le mot de passe
	    $patterns = array (
			       '/%LISTE%/',
			       '/%EMAIL%/',
			       '/%PASS%/',
			       '/%ADMINPW%/',
			       '/%SERVER%/'
			       );
	    $replace = array (
			      $list,
			      $this->email,
			      $this->pass,
			      ADHERENT_MAILMAN_ADMINPW,
			      ADHERENT_MAILMAN_SERVER
			      );
	    $curl_url = preg_replace ($patterns, $replace, ADHERENT_MAILMAN_URL);

	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL,"$curl_url");
	    //curl_setopt($ch, CURLOPT_URL,"http://www.j1b.org/");
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	    //curl_setopt($ch, CURLOPT_POST, 0);
	    //curl_setopt($ch, CURLOPT_POSTFIELDS, "a=3&b=5");
	    //--- Start buffering
	    //ob_start();
	    $result=curl_exec ($ch);
	    logfile($result);
	    //--- End buffering and clean output
	    //ob_end_clean();
	    if (curl_error($ch) > 0)
	      {
		// error
		return 0;
	      }
	    curl_close ($ch);

	  }
	return 1;
      }
    else
      {
	$this->errorstr="Constantes de connection non definies";
	return 0;
      }
  }

/*!
		\brief fonction qui désinscrit l'utilisateur de toutes les mailing list mailman
		\ remarks	utilie lors de la résiliation d'adhésion
*/

  function del_to_mailman($listes='')
  {
    if (defined("ADHERENT_MAILMAN_UNSUB_URL") && ADHERENT_MAILMAN_UNSUB_URL != '' && defined("ADHERENT_MAILMAN_LISTS") && ADHERENT_MAILMAN_LISTS != '')
      {
	if ($listes==''){
	  $lists=explode(',',ADHERENT_MAILMAN_LISTS);
	  if (defined("ADHERENT_MAILMAN_LISTS_COTISANT") && ADHERENT_MAILMAN_LISTS_COTISANT !=''){
	    $lists=array_merge ($lists,explode(',',ADHERENT_MAILMAN_LISTS_COTISANT));
	  }
	}else{
	  $lists=explode(',',$listes);
	}
	foreach ($lists as $list)
	  {
	    // on remplace dans l'url le nom de la liste ainsi
	    // que l'email et le mot de passe
	    $patterns = array (
			       '/%LISTE%/',
			       '/%EMAIL%/',
			       '/%PASS%/',
			       '/%ADMINPW%/',
			       '/%SERVER%/'
			       );
	    $replace = array (
			      $list,
			      $this->email,
			      $this->pass,
			      ADHERENT_MAILMAN_ADMINPW,
			      ADHERENT_MAILMAN_SERVER
			      );
	    $curl_url = preg_replace ($patterns, $replace, ADHERENT_MAILMAN_UNSUB_URL);

	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL,"$curl_url");
	    //curl_setopt($ch, CURLOPT_URL,"http://www.j1b.org/");
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	    //curl_setopt($ch, CURLOPT_POST, 0);
	    //curl_setopt($ch, CURLOPT_POSTFIELDS, "a=3&b=5");
	    //--- Start buffering
	    //ob_start();
	    $result=curl_exec ($ch);
	    logfile($result);
	    //--- End buffering and clean output
	    //ob_end_clean();
	    if (curl_error($ch) > 0)
	      {
		// error
		return 0;
	      }
	    curl_close ($ch);

	  }
	return 1;
      }
    else
      {
	$this->errorstr="Constantes de connection non definies";
	return 0;
      }
  }

}
?>
