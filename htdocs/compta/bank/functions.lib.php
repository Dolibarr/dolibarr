<?php
/* Copyright (C) 2000,2001 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * or see http://www.gnu.org/
 */

function gljDbConnect() {
  return pg_Connect($GLOBALS["DB_HOST"],
		    $GLOBALS["DB_PORT"],
		    $GLOBALS["DB_OPTIONS"],
		    $GLOBALS["DB_TTY"],
		    $GLOBALS["DB_NAME"]);
}

function gljPrintSelect($db, $refid) {
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num) {
    $obj = $db->fetch_object( $i);
    print "<OPTION VALUE=$obj->id";
    if ($refid==$obj->id) { 
      print " SELECTED"; 
    }
    print ">$obj->libelle</OPTION>\n";
    $i++;
  }
}
/*
 * Copy company def from one base to another
 */

function gljCopy_Soc($idsoc, $dbfrom, $dbto) {
  global $dbhost, $dbport, $dboptions, $dbtty, $DB_LOGIN_NAME, $dbname, $DB_NAME;

  // Add login info into login table

  $connfrom = pg_Connect($dbhost, $dbport, $dboptions, $dbtty, $DB_NAME["$dbfrom"]);
  $connto = pg_Connect($dbhost, $dbport, $dboptions, $dbtty, $DB_NAME["$dbto"]);
  $connlogin = pg_Connect($DB_LOGIN_HOST, $DB_LOGIN_PORT , $dboptions, $dbtty ,$DB_LOGIN_NAME);

  $madate = time();

  if ($connfrom) {
    if ($connto) {
      if ($connlogin) {
	$sql = "SELECT nom, fk_effectif FROM societe WHERE id = '$idsoc'";
	$result = pg_Exec($connfrom, $sql);
	if ( $result ) {
	  if (pg_NumRows($result) ) {
	    $obj = pg_Fetch_Object($result, 0);
	    
	    $sql = "INSERT INTO societe (id, nom, datec, fk_effectif) ";
	    $sql .= "VALUES ('$idsoc', '$obj->nom', $madate, $obj->fk_effectif)";
	    
	    $result = pg_Exec($connto, $sql);
	    if ( $result ) {
	      $sql = "UPDATE login SET pays = pays || ':$dbto' WHERE id = '$idsoc'";
	      $result = pg_Exec($connlogin, $sql);
	      if ( $result ) {
		// ALL success
		return 0;
	      }
	    }	    
	  } else {
	    // this login exists
	    return 2;
	  }
	}
	pg_close($connlogin);
      }
      pg_close($connto);
    }
    pg_close($connfrom);
  }  
}
/*
 * Envoie le login lors de la premiere connexion au compte
 */
function gljMailLogin ($db, $address, $id, $dbname) {

  $sql = "SELECT login, clearpass FROM login where id='$id' ";
  
  if ($db->query($sql)) {
    if ($db->num_rows() > 0) {
      $obj = $db->fetch_object(0);
      $db->free();

      $subject = "Confirmation";
      $mess  = "Vous venez de déposer votre CV sur http://".$GLOBALS["GLJ_NORMAL_HOST"]."\n";
      $mess .= "\n\n";
      $mess .= "login : $obj->login\n";
      $mess .= "pass  : $obj->clearpass\n";
      $mess .= "\n-----------------------------\n";
      $mess .= "contact : " . $GLOBALS["WEBMASTER"];
      $mess .= "\n-----------------------------\n";
    
      $return = mail("$address","$subject","$mess","From: " . $GLOBALS["WEBMASTER"]);
    }
  }
}
//
//
//
function gljFooter_Cursor ($file, $page, $limit, $i, $parm="") {
  $page_prev = $page - 1;
  $page_next = $page + 1;

  print "<TABLE width=\"100%\" border=\"0\" cellspacing=\"2\"><TR><TD>";

  if ( $page ) {
    print "<A href=\"$file?page=$page_prev$parm\" class=\"T3\">" . $GLOBALS["_PAGE_PREV"] . "</A>"; 
  }
 
  print "</TD><TD align=\"right\">";

  if ( $i > ( $limit - 1 ) ) {
    print "<A href=\"$file?page=$page_next$parm\" class=\"T3\">" . $GLOBALS["_PAGE_NEXT"] . "</A>"; 
  }

  print "</TD></TR></TABLE>\n";
}
//
//
//

/*
 *
 * Verif info
 *
 */
function gljVerif_NewLogin($login, $pass, $pass2) {
  if (! strlen($login) ) {
    return 3;
    exit;
  }
 if ( $pass <> $pass2 ) {
    return 1;
    exit;
  }
  if (! strlen($pass) ) {
    return 5;
    exit;
  }
  return 0;
}
//
// Create new candidat login
//
function gljCreate_Login_Cand($db, $login, $pass, $pass2) {
  global $dbhost, $dbport, $dboptions, $dbtty, $DB_LOGIN_NAME, $dbname;

  // Check validity
  $return = gljVerif_NewLogin($login, $pass, $pass2);
  if ( $return ) {
    return $return;
    exit;
  }
  // Add login info into login table

  $madate = $db->idate(time());
  $passmd5 = md5($pass);
  $token = uniqid("CAN");

  $sql = "SELECT login FROM login WHERE login = '$login'";

  if ( $db->query($sql) ) {
    if (! $db->num_rows() ) {
      $sql = "INSERT INTO login VALUES ('$token','$login', '$passmd5','$pass', $madate, 'c','" . $GLOBALS["PREFIX"] . "')";
      if ( $db->query($sql) ) {
	//
	// Create data base in candidat
	//
	
	$sql = "INSERT INTO candidat (id, datec,datel, sent, fk_anexpe, reminder, intern, cjn) VALUES ('$token',$madate,$madate, -1, 0, 1, 1, 1)";
	if ( $db->query($sql) ) {
	  return 0;
	}
      }
    } else {
      // this login exists
      return 2;
    }
  }
}
/*
 *
 *
 * Create new company login
 *
 *
 */
function gljCreateCompany($db, $company_name, $address, $cp, $ville, $fkpays, $phone, $fax, $url, 
			  $fksecteur, $fkeffectif, $fktypent, $c_nom, $c_prenom, $c_phone, $c_mail, $siren, 
			  $parentidp=0, $setid=0, &$numerror) {
  /*
   * Create a new company
   *  - insert data in table societe 
   *  - return company's idp
   */

  // Check validity
  $return = gljVerifCompany($company_name, $address, $cp, $ville, $fkpays, $phone, $fax, $url, 
			    $fksecteur,$fkeffectif, $c_nom, $c_prenom, $c_phone, $c_mail, $numerror);
  if ( $return ) {
    $sql = "INSERT INTO societe (datec,nom,address,cp,ville,tel,fax,url,fk_secteur,fk_effectif,fk_typent";
    $sql .= ",c_nom,c_prenom,c_tel,c_mail,karma,view_res_coord,siren,parent";
    if ($setid > 0 ) {
      $sql .= ",id";
    }
    $sql .= ")";
  
    $sql .= "VALUES (now(),'$company_name',$address,$cp,$ville,$phone,$fax,$url,$fksecteur,$fkeffectif,$fktypent";
    $sql .= ",$c_nom,$c_prenom,$c_phone,'$c_mail', 0, 0,'$siren',$parentidp";
  
    if ($setid > 0 ) {
      if ($setid == 2 ) {
	$token = uniqid("-OC");
      } else {
	$token = uniqid("SOC");
      }
      $sql .= ",'$token'";
    }
    $sql .= ");";
    
    if ( $db->query($sql) ) {
      $sql = "SELECT idp FROM societe WHERE id= '$token';";
      if ( $db->query($sql) ) {
	if ( $db->num_rows() ) {
	  $obj = $db->fetch_object(0);
	  return $obj->idp;
	  $db->free();
	  
	  $sql = "INSERT INTO socpeople (datec, name, firstname, fk_soc, phone, fax, email)";
	  $sql .= "VALUES (now(),'$c_nom','$c_prenom', $obj->idp, $phone, $fax,$url, '$c_mail')";
	  if ( $db->query($sql) ) {
	    
	  }
	}
      } else {
	print $db->error();
      }
    } else {
      print $db->error();
      return 0;
    }
    return 1;
  } else {
    /*
     * Verification Failed
     */
    return 0;
  }
}
/*
 *
 *
 */
function gljVerifCompany(&$company_name, &$address, &$cp, &$ville, &$fkpays, &$phone, &$fax, &$url, &$fksecteur,&$fkeffectif, &$c_nom, &$c_prenom, &$c_phone, &$c_mail, &$numerror) {
  $numerror = 0;

  if (!strlen(trim($company_name))) { $numerror = 4; } 
  if (!gljValidEmail($c_mail))      { $numerror = 8; }
  if (!strlen(trim($c_mail)))       { $numerror = 7; }

  if (strlen(trim($address)))  { $address = "'$address'";       } else { $address = "NULL"; }
  if (strlen(trim($cp)))       { $cp    = "'".trim($cp)   ."'"; } else { $cp = "NULL";  }
  if (strlen(trim($ville)))    { $ville = "'".trim($ville)."'"; } else { $ville = "NULL"; }
  if (strlen(trim($phone)))    { $phone = "'".trim($phone)."'"; } else { $phone = "NULL";  }
  if (strlen(trim($fax)))      { $fax   = "'".trim($fax)  ."'"; } else { $fax = "NULL";  }
  if (strlen(trim($url)))      { $url   = "'".trim($url)  ."'"; } else { $url = "NULL";  }
  if (strlen(trim($c_nom)))    { $c_nom = "'".trim($c_nom)."'"; } else { $c_nom = "NULL";  }
  if (strlen(trim($c_prenom))) { $c_prenom = "'$c_prenom'";     } else { $c_prenom = "NULL";  }
  if (strlen(trim($c_phone)))  { $c_phone = "'$c_phone'";       } else { $c_phone = "NULL";  }

  if ($numerror) {
    return 0;
  } else {
    return 1;
  }
}
function gljCreateCompanyMail($to, $subject, $message) {
  $return = mail($to, $subject, $message, "From: " . $GLOBALS["WEBMASTER"]);
}
//
//
function gljValidEmail($email) {
  if (eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@([0-9a-z][0-9a-z-]*[0-9a-z]\.)+[a-z]{2}[mtgvu]?$", $email) ) {
    return 1;
  } else {
    return 0;
  }
}
//
// Create new company login REPLACE by function gljCreate_Company
//
function gljCreate_Login_Soc($login, $pass, $pass2, $company_name) {
  global $dbhost, $dbport, $dboptions, $dbtty, $DB_LOGIN_NAME, $dbname;

  // Check validity
  $return = gljVerif_NewLogin($login, $pass, $pass2);
  if ( $return ) {
    return $return;
    exit;
  }
  if (! strlen(trim($company_name))) {
    return 4;
    exit;
  }
  // Add login info into login table
  $dbconn = pg_Connect("$dbhost","$dbport","$dboptions","$dbtty",$DB_LOGIN_NAME);
  $dbconn2 = gljDbConnect();

  $madate = time();
  $passmd5 = md5($pass);
  $token = uniqid("");

  if ($dbconn) {
    if ($dbconn2) {
      $sql = "SELECT login FROM login WHERE login = '$login'";

      $result = pg_Exec($dbconn, $sql);
      if ( $result ) {
	if (! pg_NumRows($result) ) {
	  $sql = "SELECT nom FROM societe WHERE nom = '$company_name'";

	  $result = pg_Exec($dbconn2, $sql);
	  if ( $result ) {
	    if (! pg_NumRows($result) ) {

	      $sql = "INSERT INTO login VALUES ('$token','$login', '$passmd5','$pass', $madate, 's','" . $GLOBALS["PREFIX"] . "')";
	      $result = pg_Exec($dbconn, $sql);
	      if( $result ) {	
		$sql2 = "INSERT INTO societe (id, datec, nom, fk_effectif, tchoozeid, viewed, cjn, intern) VALUES ('$token', $madate, '$company_name',0,0,0, 1, 1)";
		  
		$result = pg_Exec($dbconn2, $sql2);
		return 0;
	      }
	    } else {
	      return 6;
	    }
	  }
	} else {
	  // this login exists
	  return 2;
	}
      }
    }
  }
}
//
// Ajoute un outil
//
function ins_outil ($db, $idp, $outil, $contrib, $niveau, $table, $champ, $fkanexpe=0) {

  $sql = "DELETE FROM $table WHERE $champ=$idp AND fk_outil=$outil;";
  if ( $db->query( $sql ) ) {
    $sql = "INSERT INTO $table ($champ, fk_outil, fk_contrib, fk_niveau, fk_anexpe)";
    $sql .= " VALUES ($idp, $outil, $contrib, $niveau, $fkanexpe)" ;

    $result = $db->query( $sql );
    if (!$result) { 
      print "Erreur INSERT\n<BR>$sql";
    }
  }
}
//
//
//
function ins_lang ($db, $idp, $lang, $niveau, $table='lang', $champ='fk_cand') {

  $sql = "DELETE FROM $table WHERE $champ=$idp AND fk_lang=$lang;";
  if ( $db->query( $sql ) ) {
    $sql = "INSERT INTO $table ($champ, fk_lang, fk_niv) VALUES ($idp, $lang, $niveau)" ;

    $result = $db->query($sql);
    if (!$result) {	
      echo "Erreur INSERT\n$sql";	
    }
  }
}
//
//
//
function get_ofid_by_idp ($db, $ofidp) {
  $sql = "SELECT id from OFFRE where idp = $ofidp";

  $result = $db->query( $sql );

  if (!$result) { 
    return 0;
  } else {
    if ($db->num_rows() > 0) {
      $row = 0;
      while($data = $db->fetch_object( $row)) {
	$id = $data->id ;
      }
      return $id;
    }
  }
}
?>
