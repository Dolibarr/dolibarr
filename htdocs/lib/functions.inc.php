<?PHP
/* Copyright (C) 2000-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 *
 */

/*!	\file functions.inc.php
		\brief Ensemble de fonctions de base de dolibarr sous forme d'include
		\author Rodolphe Quiedeville
		\author	Jean-Louis Bergamo
		\author	Laurent Destailleur
		\version $Revision$

		Ensemble de fonctions de base de dolibarr sous forme d'include
*/

$yn[0] = "non";
$yn[1] = "oui";

/*!
		\brief envoi des messages dolibarr dans syslog
		\param	message		message a envoyer a syslog
*/

function dolibarr_syslog($message)
{
  define_syslog_variables();

  openlog("dolibarr", LOG_PID | LOG_PERROR, LOG_USER);	# LOG_USER au lieu de LOG_LOCAL0 car non accepté par tous les PHP

  syslog(LOG_WARNING, $message);

  closelog();
}

/*!
		\brief header d'une fiche
		\param	links		liens
		\param	active
		\remarks active = 0 par défaut
*/


function dolibarr_fiche_head($links, $active=0, $title='')
{
  print "<!-- fiche --><div class=\"tabs\">\n";

  if (strlen($title))
    {
      print '<a class="tabTitle">'.$title.'</a>';
    }

  for ($i = 0 ; $i < sizeof($links) ; $i++)
    {
      if ($links[$i][2] == 'image')
	{
	  print '<a class="tabimage" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
	}
      else
	{
	  if ($i == $active)
	    {
	      print '<a id="active" class="tab" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
	    }
	  else
	    {
	      print '<a class="tab" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
	    }
	}
    }

  print "</div>\n";
  print "<div class=\"tabBar\">\n<br>\n";
}

/*!
		\brief insertion d'une constantes dans la base de données
		\param	db			base de données
		\param	name		nom de la constante
		\param	value		valeur de la constante
		\param	type		type de constante
		\param	visible	la constante est t'elle visible
		\param	note		explication de la constante
		\remarks type = chaine par défaut
		\remarks visible = 0 par défaut
		\remarks retourne 0 pour raté, 1 pour réussi
*/

function dolibarr_set_const($db, $name, $value, $type='chaine', $visible=0, $note='')
{
  $sql = "REPLACE INTO llx_const SET name = '$name', value='$value', visible=$visible, type='$type', note='$note'";

  if ($db->query($sql))
    {
      return 1;
    }
  else
    {
      return 0;
    }
}

/*!
		\brief effacement d'une constante dans la base de données
		\param	db			base de données
		\param	name		nom ou rowid de la constante
		\remarks retourne 0 pour raté, 1 pour réussi
*/

function dolibarr_del_const($db, $name)
{
  $sql = "DELETE FROM llx_const WHERE name='$name' or rowid='$name'";

  if ($db->query($sql))
    {
      return 1;
    }
  else
    {
      return 0;
    }
}

/*!
		\brief formattage des nombres
		\param	ca			valeur a formater
		\return	cat			valeur formatée
*/

function dolibarr_print_ca($ca)
{
    if ($ca > 1000)
    {
      $cat = round(($ca / 1000),2);
      $cat = "$cat Keuros";
    }
    else
    {
      $cat = round($ca,2);
      $cat = "$cat euros";
    }

    if ($ca > 1000000)
    {
      $cat = round(($ca / 1000000),2);
      $cat = "$cat Meuros";
    }

    return $cat;
}

/*!
		\brief formattage de la date
		\param	time       date timestamp ou au format YYYY-MM-DD
		\param	format     format de la date "%d %b %Y"
		\remarks retourne la date formatée
*/

function dolibarr_print_date($time,$format="%d %b %Y")
{
  // Analyse de la date
  if (eregi('^([0-9]+)\-([0-9]+)\-([0-9]+)\s?([0-9]+)?:?([0-9]+)?',$time,$reg)) {
    // Date au format 'YYYY-MM-DD' ou 'YYYY-MM-DD HH:MM:SS'
    $syear = $reg[1];
    $smonth = $reg[2];
    $sday = $reg[3];
    $shour = $reg[4];
    $smin = $reg[5];
    if ($syear < 1970 && $_SERVER["WINDIR"])
      {
	// Le formatage ne peut etre appliqué car windows ne supporte pas la fonction
	// mktime si l'année est inférieur à 1970. On retourne un format fixe
	return "$syear-$smonth-$sday";
      }
    else
      {
	return strftime($format,mktime($shour,$smin,0,$smonth,$sday,$syear));
      }
  }
  else {
    // Date est un timestamps
    return strftime($format,$time);
  }
}


/*!
		\brief affiche les informations d'un objet
		\param	object			objet a afficher
*/

function dolibarr_print_object_info($object)
{
  print "Créé par  : " . $object->user_creation->fullname . '<br>';
  print "Date de création : " . strftime("%A %d %B %Y %H:%M:%S",$object->date_creation) . '<br>';

  if (isset($object->user_modification))
    print "Modifié par  : " . $object->user_modification->fullname . '<br>';


  if (isset($object->date_modification))
    print "Date de modification : " . strftime("%A %d %B %Y %H:%M:%S",$object->date_modification) . '<br>';

  if (isset($object->user_validation))
    print "Validé par  : " . $object->user_validation->fullname . '<br>';

  if (isset($object->user_cloture))
    print "Cloturé par  : " . $object->user_cloture->fullname . '<br>';

}

/*!
  \brief formattage du telephone
  \param	phone			numéro de telephone à formater
  \return phone			numéro de téléphone formaté
  \remarks net tient pas en compte le format belge 02/211 34 83
*/

function dolibarr_print_phone($phone)
{
    if (strlen(trim($phone)) == 10)
    {
      return substr($phone,0,2)." ".substr($phone,2,2)." ".substr($phone,4,2)." ".substr($phone,6,2)." ".substr($phone,8,2);
    }
    else
    {
      return $phone;
    }
}

function img_file($alt = "default")
{
  if ($alt="default") {
    global $langs;
    $alt=$langs->trans("Show");
  }
  return '<img src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/file.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

function img_file_new($alt = "default")
{
  if ($alt="default") {
    global $langs;
    $alt=$langs->trans("Show");
  }
  return '<img src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/filenew.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}


function img_pdf($alt = "default")
{
  if ($alt="default") {
    global $langs;
    $alt=$langs->trans("Show");
  }
  return '<img src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/pdf.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}


function img_edit($alt = "default")
{
  if ($alt="default") {
    global $langs;
    $alt=$langs->trans("Modify");
  }
  return '<img src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/edit.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

function img_delete($alt = "default")
{
  if ($alt="default") {
    global $langs;
    $alt=$langs->trans("Delete");
  }
  return '<img src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/delete.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

function img_disable($alt = "default")
{
  if ($alt="default") {
    global $langs;
    $alt=$langs->trans("Disable");
  }
  return '<img src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/disable.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}


function img_warning($alt = "default")
{
  if ($alt="default") {
    global $langs;
    $alt=$langs->trans("Show");
  }
  return '<img src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/warning.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

function img_info($alt = "Informations")
{
  return '<img src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/info.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

function img_alerte($alt = "default")
{
  if ($alt="default") {
    global $langs;
    $alt=$langs->trans("Alert");
  }
  return '<img src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/alerte.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}


function img_phone_in($alt = "default")
{
  if ($alt="default") {
    global $langs;
    $alt=$langs->trans("Modify");
  }
  return '<img src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/call.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

function img_phone_out($alt = "default")
{
  if ($alt="default") {
    global $langs;
    $alt=$langs->trans("Modify");
  }
  return '<img src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/call_out.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}


function img_next($alt = "default")
{
  if ($alt="default") {
    global $langs;
    $alt=$langs->trans("Next");
  }
  return '<img src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/next.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

function img_previous($alt = "default")
{
  if ($alt="default") {
    global $langs;
    $alt=$langs->trans("Previous");
  }
  return '<img src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/previous.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/*!
		\brief fonction de login
		\remarks if faut changer le code html dans la fonction pour changer le design
		\remarks	le css devrait etre pris dans le repetoire de dolibarr et ne pas etre en dur !
*/

function loginfunction()
{
  print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
  print "\n<html><head><title>Dolibarr Authentification</title>";
  print '<style type="text/css">
  body {
    font-size:14px;
    font-family: Verdana, Tahoma, Arial, Helvetica, sans-serif;
    background-color: #cac8c0;
    margin-left: 30%;
    margin-right: 30%;
    margin-top: 10%;
    margin-bottom: 1%;
  }
  div.main {
    background-color: white;
    text-align: left;
    border: solid black 1px;
  }
  div.main-inside {
    background-color: white;
    padding-left: 20px;
    padding-right: 50px;
    text-align: center;
    margin-bottom: 50px;
    margin-top: 30px;
  }
  div.footer {
	background-color: #dcdff4;
	font-size: 10px;
	border-top: solid black 1px;
	padding-left: 5px;
        text-align: center;
  }
  div.header {
	background-color: #dcdff4;
	border-bottom: solid black 1px;
	padding-left: 5px;
        text-align: center;
  }
  div.footer p {
	margin: 0px;
  }
  a:link,a:visited,a:active {
	text-decoration:none;
	color:blue;
  }
  a:hover {
	text-decoration:underline;
	color:blue;
  }
  </style>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15">
  <script language="javascript">
  function donnefocus(){
   document.identification.username.focus();
  }
  </script>
  </head>
  <body onload="donnefocus();">
  <div class="main">
  <div class="header">';
  print 'Dolibarr '.DOL_VERSION;
  print '
  </div>
  <div class="main-inside">
  ';

  echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="identification">';
  print '<table><tr>';
  print '<td>Login : </td><td><input type="text" name="username"></td></tr>';;
  print '<tr><td>Password : </td><td><input type="password" name="password"></td></tr>';

  echo '</table>
  <p align="center"><input value="Login" type="submit">
  </form>';
}

/*!
		\brief Affiche message erreur de type acces interdit
*/

function accessforbidden()
{
  global $langs;
  
  llxHeader();
  print $langs->trans("ErrorForbidden");
  llxFooter();
  exit(0);
}

/*!
		\brief Affiche message erreur system avec toutes les informations pour faciliter le diagnostique et la remontée des bugs
*/

function dolibarr_print_error($db='',$msg='')
{
  print "Dolibarr a détectée une erreur technique.<br>\n";
  print "Voici les informations qui pourront aider au diagnostique:<br><br>\n";

  print "<b>Serveur:</b>".$_SERVER["SERVER_SOFTWARE"]."<br>\n";;
  print "<b>URL sollicitée:</b> ".$_SERVER["REQUEST_URI"]."<br>\n";;
  print "<b>Paramètres:</b> ".$_SERVER["QUERY_STRING"]."<br>\n";;
  print "<b>URL d'origine:</b> ".$_SERVER["HTTP_REFERER"]."<br>\n";;
  
  if ($db) {
    print "<br>\n";
    print "<b>Requete dernier acces en base:</b> ".$db->lastquery()."<br>\n";
    print "<b>Code retour dernier acces en base:</b> ".$db->errno()."<br>\n";
    print "<b>Information sur le dernier accès en base:</b> ".$db->error()."<br>\n";
  }
  if ($msg) {
    print "Message: $msg<br>\n" ;
  }
  exit;
}

/*!
		\brief deplacer les fichiers telechargés
		\param	src_file	fichier source
		\param	dest_file	fichier de destination
*/

function doliMoveFileUpload($src_file, $dest_file)
{
  $file_name = $dest_file;

  if (substr($file_name, strlen($file_name) -3 , 3) == 'php')
    {
      $file_name = $dest_file . ".txt";
    }

  return move_uploaded_file($src_file, $file_name);
}


function dolibarr_user_page_param($db, &$user)
{
  foreach ($GLOBALS["_GET"] as $key=>$value)
    {
      if ($key == "sortfield")
	{
	  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."user_param ";
	  $sql .= " SET fk_user =".$user->id;
	  $sql .= " ,page='".$GLOBALS["SCRIPT_URL"] . "'";
	  $sql .= " ,param='sortfield'";
	  $sql .= " ,value='".urlencode($value)."'";

	  $db->query($sql);
	  $user->page_param["sortfield"] = $value;
	}

      //      print $key . "=".$value . "<br>";

      if ($key == "sortorder")
	{
	  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."user_param ";
	  $sql .= " SET fk_user =".$user->id;
	  $sql .= " ,page='".$GLOBALS["SCRIPT_URL"] . "'";
	  $sql .= " ,param='sortorder'";
	  $sql .= " ,value='".urlencode($value)."'";

	  $db->query($sql);
	  $user->page_param["sortorder"] = $value;
	}
      if ($key == "begin")
	{
	  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."user_param ";
	  $sql .= " SET fk_user =".$user->id;
	  $sql .= " ,page='".$GLOBALS["SCRIPT_URL"] . "'";
	  $sql .= " ,param='begin'";
	  $sql .= " ,value='".$value."'";

	  $db->query($sql);
	  $user->page_param["begin"] = $value;
	}
      if ($key == "page")
	{
	  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."user_param ";
	  $sql .= " SET fk_user =".$user->id;
	  $sql .= " ,page='".$GLOBALS["SCRIPT_URL"] . "'";
	  $sql .= " ,param='page'";
	  $sql .= " ,value='".$value."'";

	  $db->query($sql);
	  $user->page_param["page"] = $value;
	}
    }
}

/*!
		\brief transcodage de francs en euros
		\param	zonein		zone de depart
		\param	devise		type de devise
		\return	r
*/

function transcoS2L($zonein,$devise)
{
  // Open source offert par <A HREF="mailto:alainfloch@free.fr?subject=chif2let">alainfloch@free.fr</A> 28/10/2001, sans garantie.
  // début de la fonction de transcodification de somme en toutes lettres

  /*  $zonein = "123,56";
   *  $devise = "E"; // préciser F si francs , sinon ce sera de l'euro
   *  $r = transcoS2L($zonein,$devise); // appeler la fonction
   *  echo "résultat   vaut $r<br>";
   *  $zonelettresM =  strtoupper($r); // si vous voulez la même zone mais tout en majuscules
   *  echo "résultat en Majuscules  vaut $zonelettresM<br>";
   *  $zonein = "1,01";
   *  $r = transcoS2L($zonein,$devise);
   *  echo "résultat   vaut $r<br>";
   */


  if ($devise == "F")
    {
      $unite_singulier = " franc ";
      $unite_pluriel = " francs ";
      $cent_singulier = " centime";
    }
  else
    {
      $unite_singulier = " euro ";
      $unite_pluriel = " euros ";
      $cent_singulier = " centime";
    }

  $arr1_99 = array("zéro","un","deux","trois",
		   "quatre","cinq","six","sept",
		   "huit","neuf","dix","onze","douze",
		   "treize","quatorze","quinze","seize",
		   "dix-sept","dix-huit","dix-neuf","vingt ");

  $arr1_99[30] = "trente ";
  $arr1_99[40] = "quarante ";
  $arr1_99[50] = "cinquante ";
  $arr1_99[60] = "soixante ";
  $arr1_99[70] = "soixante-dix ";
  $arr1_99[71] = "soixante et onze";
  $arr1_99[80] = "quatre-vingts ";
  $i = 22;
  while ($i < 63) {// initialise la  table
    $arr1_99[$i - 1] = $arr1_99[$i - 2]." et un";
    $j = 0;
    while ($j < 8) {
      $k = $i + $j;
      $arr1_99[$k] = $arr1_99[$i - 2].$arr1_99[$j + 2];
      $j++;
    }
    $i = $i + 10;
  } // fin initialise la table

  $i = 12;
  while ($i < 20) {// initialise la  table (suite)
    $j = 60 + $i;
    $arr1_99[$j] = "soixante-".$arr1_99[$i];
    $i++;
  } // fin initialise la  table (suite)

  $i = 1;
  while ($i < 20) {// initialise la  table (fin)
    $j = 80 + $i;
    $arr1_99[$j] = "quatre-vingt-".$arr1_99[$i];
    $i++;
  } // fin initialise la  table (fin)
  // echo "Pour une valeur en entrée = $zonein<br>"; //pour ceux qui ne croient que ce qu'ils voient !
  // quelques petits controles s'imposent !! 
  $valid = "[a-zA-Z\&\é\"\'\(\-\è\_\ç\à\)\=\;\:\!\*\$\^\<\>]";
  if (ereg($valid,$zonein))
    {
      $r = "<b>la chaîne ".$zonein." n'est pas valide</b>";
      return($r);
    }
  $zone = explode(" ",$zonein); // supprimer les blancs séparateurs
  $zonein = implode("",$zone); // reconcatène la zone input
  $zone = explode(".",$zonein); // supprimer les points séparateurs
  $zonein = implode("",$zone); // reconcatène la zone input, ça c'est fort ! merci PHP
  $virg = strpos($zonein,",",1); // à la poursuite de la virgule
  $i = strlen($zonein); // et de la longueur de la zone input
  if ($virg == 0) { // ya pas de virgule
    if ($i > 7)
      {
	$r = "<b>la chaîne ".$zonein." est trop longue (maxi = 9 millions)</b>";
	return($r);
      }
    $deb = 7 - $i;
    $zoneanaly = substr($zonechiffres,0,$deb).$zonein.",00";
  }
  else
    { //ya une virgule
      $ti = explode(",",$zonein); // mettre de côté ce qu'il y a devant la virgule
      $i = strlen($ti[0]); // en controler la longueur
      $zonechiffres = "0000000,00";
      if ($i > 7)
	{
	  $r = "<b>la chaîne ".$zonein." est trop longue (maxi = 9 millions,00)</b>";
	  return($r);
	}
      $deb = 7 - $i;
      $zoneanaly = substr($zonechiffres,0,$deb).$zonein;
    }
  $M= substr($zoneanaly,0,1);
  if ($M != 0)
    { // qui veut gagner des millions
      $r =   $arr1_99[$M]." million";
      if ($M ==1) $r =  $r." ";
      else $r = $r."s ";
      if (substr($zoneanaly,1,6)==0)
	{
	  if ($devise == 'F') $r = $r." de ";
	  else $r = $r."d'";
	}
    }
  $CM= substr($zoneanaly,1,1);
  if ($CM == 1)
    { // qui veut gagner des centaines de mille
      $r = $r." cent ";
    }
 else
   { // ya des centaines de mille
	if ($CM > 1)
	  {
	    $r = $r. $arr1_99[$CM]." cent ";
		}
   } // fin du else ya des centaines de mille
  $MM= substr($zoneanaly,2,2);
  if (substr($zoneanaly,2,1)==0){ $MM = substr($zoneanaly,3,1);} // enlever le zéro des milliers cause indexation
  if ($MM ==0 && $CM > 0)
    {
      $r = $r."mille ";
    }
  if ($MM != 0)
    {
      if ($MM == 80)
	{
	  $r = $r."quatre-vingt mille ";
	}
      else
	{
	  if ($MM > 1 )
	    {
	      $r = $r.$arr1_99[$MM]." mille ";
	    }
	  else
	    {
	      if ($CM == 0)	$r = $r." mille ";
	      else
		{
		  $r = $r.$arr1_99[$MM]." mille ";
		}
	    }
	}
    }
  $C2= substr($zoneanaly,5,2);
  if (substr($zoneanaly,5,1)==0){ $C2 = substr($zoneanaly,6,1);} // enlever le zéro des centaines cause indexation
  $C1= substr($zoneanaly,4,1);
  if ($C2 ==0 && $C1 > 1)
    {
      $r = $r.$arr1_99[$C1]." cents ";
    }
  else
    {
      if ($C1 == 1) $r = $r." cent ";
      else
	{
	  if ($C1 > 1) $r = $r.$arr1_99[$C1]." cent ";
	}
    }
  if ($C2 != 0)
    {
      $r = $r.$arr1_99[$C2];
    }
  if ($virg !=0)
    {
      if ($ti[0] > 1) $r = $r. $unite_pluriel; else $r = "un ".$unite_singulier;
    }
  else
    {
      if ($zonein > 1) $r = $r.$unite_pluriel; else $r = "un ".$unite_singulier;
    }
  $UN= substr($zoneanaly,8,2);
  if ($UN != "00")
    {
      $cts = $UN;
      if (substr($UN,0,1)==0){ $cts = substr($UN,1,1);} // enlever le zéro des centimes cause indexation
      $r = $r." et ". $arr1_99[$cts].$cent_singulier;
      if ($UN > 1) $r =$r."s"; // accorde au pluriel
    }
  $r1 = ltrim($r); // enleve quelques blancs possibles en début de zone
  $r = ucfirst($r1); // met le 1er caractère en Majuscule, c'est + zoli
  return($r); // retourne le résultat
} // fin fonction transcoS2L


/*!
		\brief affichage du titre d'une liste
		\param	name
		\param	file
		\param	field
		\param	begin
		\param	options
		\remarks begin = "" par défaut
		\remarks options = "" par défaut
*/

function print_liste_field_titre($name, $file, $field, $begin="", $options="")
 {
  global $conf;

  print $name."&nbsp;";
  print '<a href="'.$file.'?sortfield='.$field.'&amp;sortorder=asc&amp;begin='.$begin.$options.'">';
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1downarrow.png" border="0" alt="A-Z"></a>';
  print '<a href="'.$file.'?sortfield='.$field.'&amp;sortorder=desc&amp;begin='.$begin.$options.'">';
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1uparrow.png" border="0" alt="Z-A"></a>';
}

/*!
		\brief affichage du titre d'une liste avec possibilité de tri et de choix du type de la balise td
		\param	name
		\param	file
		\param	field
		\param	begin
		\param	options
		\param	td
		\param	sortfield
		\remarks begin = "" par défaut
		\remarks options = "" par défaut
		\remarks td = "" par défaut
		\remarks sortfield = "" par défaut
*/

function print_liste_field_titre_new($name, $file, $field, $begin="", $options="", $td="", $sortfield="")
 {
  global $conf;
  if ($sortfield == $field)
    {
  print '<td class="menusel" '. $td.'>';
    }
  else
    {
  print '<td '. $td.'>';
    }
  print $name."&nbsp;";
  print '<a href="'.$file.'?sortfield='.$field.'&amp;sortorder=asc&amp;begin='.$begin.$options.'">';
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1downarrow.png" border="0" alt="A-Z"></a>';
  print '<a href="'.$file.'?sortfield='.$field.'&amp;sortorder=desc&amp;begin='.$begin.$options.'">';
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1uparrow.png" border="0" alt="Z-A"></a>';
  print "</td>";
}

/*!
		\brief affichage d'un titre
		\param	titre			le titre a afficher
*/

function print_titre($titre)
{
  print '<div class="titre">'.$titre.'</div>';
}

/*!
		\brief affichage d'un titre d'une fiche aligné a droite
		\param	titre			le titre a afficher
		\param	mesg			message afficher
*/

function print_fiche_titre($titre, $mesg='')
{
  print "\n".'<table width="100%" border="0" cellpadding="3" cellspacing="0">';
  print '<tr><td><div class="titre" valign="middle">'.$titre.'</div></td>';
  if (strlen($mesg))
    {
      print '<td align="right" valign="middle"><b>'.$mesg.'</b></td>';
    }
  print '</tr></table>'."\n";
}

/*!
		\brief effacement d'un fichier
		\param	file			fichier a effacer
*/

function dol_delete_file($file)
{
  return unlink($file);
}


/*!
		\brief fonction print_barre_liste
		\param	titre			titre de la page
		\param	page			numéro de la page
		\param	file			lien
		\param	options
		\param	sortfield
		\param	sortorder
		\param	form
*/

function print_barre_liste($titre, $page, $file, $options='', $sortfield='', $sortorder='', $form='', $num=-1)
 {
  global $conf;

  if ($num > $conf->liste_limit or $num == -1)
    {
      $nextpage = 1;
    }
  else
    {
      $nextpage = 0;
    }

  print '<table width="100%" border="0" cellpadding="3" cellspacing="0">';

 if ($page > 0)
   {
     print '<tr><td><div class="titre">'.$titre.' - page '.($page+1).'</div></td>';
   }
 else
   {
     print '<tr><td><div class="titre">'.$titre.'</div></td>';
   }

 if ($form)
   {
     print '<td align="left">'.$form.'</td>';
   }

  print '<td align="right">';

  if (strlen($sortfield))
    {
      $options .= "&amp;sortfield=$sortfield";
    }

  if (strlen($sortorder))
    {
      $options .= "&amp;sortorder=$sortorder";
    }

  // affichage des fleches de navigation

  print_fleche_navigation($page,$file,$options, $nextpage);

  print '</td></tr></table>';
}

/*!
		\brief fonction servant a afficher les fleches de navigation dans les pages de listes
		\param	page			numéro de la page
		\param	file			lien
		\param	options
		\param	nextpage	page suivante
*/

function print_fleche_navigation($page,$file,$options='', $nextpage)
{
  global $conf, $langs;
  if ($page > 0)
    {
      print '<a href="'.$file.'?page='.($page-1).$options.'"><img alt="'.$langs->trans("Previous").'" title="'.$langs->trans("Previous").'" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1leftarrow.png" border="0"></a>';
    }

  if ($nextpage > 0)
    {
      print '<a href="'.$file.'?page='.($page+1).$options.'"><img alt="'.$langs->trans("Next").'" title="'.$langs->trans("Next").'" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1rightarrow.png" border="0"></a>';
    }
}


/*!
		\brief fonction servant a afficher un menu déroulant avec oui ou non
		\param	value
		\remarks value peut avoir la valeur 0 ou 1
*/

function print_oui_non($value)
{
  if ($value)
    {
      print '<option value="0">non';
      print '<option value="1" selected>oui';
    }
  else
    {
      print '<option value="0" selected>non';
      print '<option value="1">oui';
    }
}

/*!
		\brief fonction servant a afficher un menu déroulant sur le type de paiement
		\param	value
		\remarks value peut avoir la valeur 0 ou 1
*/

function print_type_paiement_select($db,$nomselect,$value=-1)
{
  print "<select name=\"$nomselect\">";

  $sql  = "SELECT tp.code, tp.libelle";
  $sql .= " FROM ".MAIN_DB_PREFIX."c_paiement as tp";
  if ($value >= 0)
    {
    $sql.="WHERE type = $value";
    }
  $sql.=" ORDER by tp.libelle";
  if ( $db->query($sql) ) {
        $i=0;
        $num = $db->num_rows();
        while ($i < $num)
    	  {
            $obj = $db->fetch_object( $i);
            print "<option value=\"$obj->code\">$obj->libelle</option>";
            $i++;
        }

    }
  print "</select>\n";
}

/*!
		\brief fonction servant a afficher les mois dans un liste déroulante
		\param	set_time
		\remarks set_time = '' par défaut
*/

function print_date_select($set_time='')
{
  if (! $set_time)
    {
      $set_time = time();
    }

  $strmonth[1] = "Janvier";
  $strmonth[2] = "F&eacute;vrier";
  $strmonth[3] = "Mars";
  $strmonth[4] = "Avril";
  $strmonth[5] = "Mai";
  $strmonth[6] = "Juin";
  $strmonth[7] = "Juillet";
  $strmonth[8] = "Ao&ucirc;t";
  $strmonth[9] = "Septembre";
  $strmonth[10] = "Octobre";
  $strmonth[11] = "Novembre";
  $strmonth[12] = "D&eacute;cembre";
    
  $smonth = 1; $endmonth = 12;
  $sday = 1; $endday = 31;

  $cday = date("d", $set_time);
  $cmonth = date("n", $set_time);
  $syear = date("Y", $set_time);

  print "<select name=\"reday\">";    

  for ($day = 1 ; $day <= $endday ; $day++) 
    {
      if ($day == $cday)
	{
	  print "<option value=\"$day\" selected>$day";
	}
      else 
	{
	  print "<option value=\"$day\">$day";
	}
    }

  print "</select>";


  print "<select name=\"remonth\">";    
  for ($month = $smonth ; $month <= $endmonth ; $month++)
    {
      if ($month == $cmonth)
	{
	  print "<option value=\"$month\" selected>" . $strmonth[$month];
	}
      else
	{
	  print "<option value=\"$month\">" . $strmonth[$month];
	}
    }
  print "</select>";
  
  print "<select name=\"reyear\">";
  
  for ($year = $syear - 2; $year < $syear + 5 ; $year++)
    {
      if ($year == $syear)
	{
	  print "<option value=\"$year\" selected>$year";
	}
      else
	{
	  print "<option value=\"$year\">$year";
	}
    }
  print "</select>\n";
  
}
/*!
		\brief fonction servant a afficher les heures/minutes dans un liste déroulante
		\param	prefix
		\param	begin
		\param	end
		\remarks begin = 1 par défaut
		\remarks end = 23 par défaut
*/

function print_heure_select($prefix,$begin=1,$end=23) {
  
  print '<select name="'.$prefix.'hour">';
  for ($hour = $begin ; $hour <= $end ; $hour++) {
    print "<option value=\"$hour\">$hour";
  }
  print "</select>&nbsp;H&nbsp;";
  print '<select name="'.$prefix.'min">';
  for ($min = 0 ; $min < 60 ; $min=$min+5) {
    if ($min < 10) {
      $min = "0" . $min;
    }
    print "<option value=\"$min\">$min";
  }
  print "</select>\n";  
}

/*!
		\brief fonction servant a afficher une durée dans une liste déroulante
		\param	prefix
*/

function print_duree_select($prefix)
{  
  print '<select name="'.$prefix.'hour">';
  print "<option value=\"0\">0";
  print "<option value=\"1\" selected>1";

  for ($hour = 2 ; $hour < 13 ; $hour++)
    {
      print "<option value=\"$hour\">$hour";
    }
  print "</select>&nbsp;H&nbsp;";
  print '<select name="'.$prefix.'min">';
  for ($min = 0 ; $min < 55 ; $min=$min+5)
    {
      print "<option value=\"$min\">$min";
    }
  print "</select>\n";  
}


/*!
		\brief fonction qui retourne un montant monétaire formaté
		\param	amount		montant a formater
		\param	html			formatage html ou pas
		\remarks html = 0 par défaut
		\remarks fnction utilisée dans les pdf et les pages html

*/

function price($amount, $html=0)
{
  if ($html)
    {

      $dec='.'; $thousand=' ';
      return ereg_replace(' ','&nbsp;',number_format($amount, 2, $dec, $thousand));

    }
  else
    {
      return number_format($amount, 2, '.', ' ');
    }

}

/*!
		\brief fonction qui convertit des euros en francs
		\param	euros			somme en euro à convertir
		\return price
*/

function francs($euros)
{
  return price($euros * 6.55957);
}

/*!
		\brief fonction qui calcule la tva
		\param	euros			somme en euro
		\param	taux			taux de tva
*/

function tva($euros, $taux=19.6)
{
  $taux = $taux / 100 ;

  return sprintf("%01.2f",($euros * $taux));
}

/*!
		\brief fonction qui calcule le montant tva incluse
		\param	euros			somme en euro
		\param	taux			taux de tva
*/

function inctva($euros, $taux=1.196)
{
  return sprintf("%01.2f",($euros * $taux));
}

/*!
		\brief fonction qui affiche des statistiques
		\param	basename
		\param	bc1
		\param	bc2
		\param	ftc
		\param	jour
*/


function stat_print($basename,$bc1,$bc2,$ftc, $jour) {

  $db = pg_Connect("","","","","$basename");
  if (!$db) {
    echo "Pas de connexion a la base\n";
    exit ;
  }

  $offset = $jour * 9;

  $sql="SELECT s.date, s.nb, l.libelle FROM stat_base as s, stat_cat as l WHERE s.cat = l.id ORDER by s.date DESC, s.cat ASC LIMIT 9 OFFSET $offset";

  $result = $db->query($sql);
  if (!$result) {
    print "Erreur SELECT<br><h1>$sql</h1><br>";
    return 1;
  }

  print "<table border=1 cellspacing=0 cellpadding=2>";
  print "<tr><td><font color=\"white\">base <b>$basename</b></font></td>";
  print "<td><font color=\"white\">libelle</font></td>";
  print "</tr>";

  $num = $db->num_rows();
  $i = 0;

  $tag = 1;
  while ( $i < $num) {
    $obj = $db->fetch_object( $i);

    $tag = !$tag;

    print "<TR><TD>$obj->date</TD><TD>$obj->libelle</TD>\n";
    print "<TD align=\"center\">$obj->nb</TD></TR>\n";
    $i++;
  }
  print "</TABLE>";
  $db->free();

  $db->close();

}


function tab_count($basename,$bc1,$bc2,$ftc) {

  $db = pg_Connect("","","","","$basename");
  if (!$db) {
    echo "Pas de connexion a la base\n";
    exit ;
  }

  $sql="SELECT count(*) AS nbcv from candidat WHERE active=1";
  $result = $db->query($sql);
  if (!$result) {
    print "Erreur SELECT<br><h1>$sql</h1><br>";
    return 1;
  }
  print "<table border=0 bgcolor=black cellspacing=0 cellpadding=0><tr><td>";

  print "<table border=0 cellspacing=1 cellpadding=1>";
  print "<tr><td><font color=\"white\">base <b>$basename</b></font></td>";
  print "<td><font color=\"white\">libelle</font></td>";
  print "</tr>";
  $nbcv = $db->result( $i, "nbcv");

  print "<tr $bc1><td><b>$ftc Nombre de CV</font></b></td>\n";
  print "<td  align=\"center\">$ftc $nbcv</td>\n";
  print "</tr>\n";
  $db->free();

  $sql="SELECT count(*) AS nbcv from offre WHERE active=1";

  $result = $db->query($sql);
  if (!$result) {
    print "Erreur SELECT<br><h1>$sql</h1><br>";
  }
  $nbcv = $db->result( $i, "nbcv");

  print "<tr $bc2><td><b>$ftc Nombre d'offre</font></b></td>";
  print "<td align=\"center\">$ftc $nbcv</td>";
  print "</tr>";

  $db->free();


  $sql="SELECT count(*) AS nbcv from candidat WHERE active=0";

  $result = $db->query($sql);
  if (!$result) {
    print "Erreur SELECT<br><h1>$sql</h1><br>";
  }

  $nbcv = $db->result( $i, "nbcv");

  print "<tr $bc1><td><b>$ftc Nombre de CV inactifs</font></b></td>\n";
  print "<td align=\"center\">$ftc $nbcv</td>";
  print "</tr>";

  $db->free();


  $sql="SELECT count(*) AS nbcv from offre WHERE active=0";

  $result = $db->query($sql);
  if (!$result) {
    print "Erreur SELECT<br><h1>$sql</h1><br>";
  }

  $nbcv = $db->result( $i, "nbcv");

  print "<tr $bc2><td><b>$ftc Nombre d'offres inactives</font></b></td>\n";
  print "<td  align=\"center\">$ftc $nbcv</td>\n";
  print "</tr>\n";

  $db->free();

  $sql="SELECT count(*) AS nbsoc from logsoc";

  $result = $db->query($sql);
  if (!$result) {
    print "Erreur SELECT<br><h1>$sql</h1><br>";
  }

  $nbsoc = $db->result( $i, "nbsoc");

  print "<tr $bc1><td><b>$ftc Nombre de logins societes</font></b></td>\n";
  print "<td align=\"center\">$ftc $nbsoc</td>";
  print "</tr>";

  print "</td></tr></table></td></tr></table>";

  $db->close();

}

/*!
		\brief fonction qui permet d'envoyer les infos dans un fichier de log
		\param	str				chaine a mettre dans le fichier
		\param	log				nom du fichier de log
		\remarks cette fonction ne marchera qui si la constante MAIN_DEBUG = 1
*/

function logfile($str,$log="/var/log/dolibarr/dolibarr.log")
{
  if (defined("MAIN_DEBUG") && MAIN_DEBUG ==1)
    {
      if (!file_exists($log))
	{
	  if (!$file=fopen($log,"w"))
	    {
	      return 0;
	    }
	}
      else
	{
	  if (!$file=fopen($log,"a+"))
	    {
	      return 0;
	    }
	}
      $logentry=date("[d/M/Y:H:i:s] ").$str."\n";
      if(!fwrite($file,$logentry)) {
	fclose($file);
	return 0;
      }
      fclose($file);
      return 1;
    }
}

/*!
		\brief fonction pour créer un mot de passe aléatoire
		\param	longueur	longueur du mot de passe
		\param	sel				donnée aléatoire
		\remarks la longueur est fixée a 8 par défaut
		\remarks la fonction a été prise sur http://www.uzine.net/spip
*/

function creer_pass_aleatoire($longueur = 8, $sel = "") {
  $seed = (double) (microtime() + 1) * time();
  srand($seed);

  for ($i = 0; $i < $longueur; $i++) {
    if (!$s) {
      if (!$s) $s = rand();
      $s = substr(md5(uniqid($s).$sel), 0, 16);
    }
    $r = unpack("Cr", pack("H2", $s.$s));
    $x = $r['r'] & 63;
    if ($x < 10) $x = chr($x + 48);
    else if ($x < 36) $x = chr($x + 55);
    else if ($x < 62) $x = chr($x + 61);
    else if ($x == 63) $x = '/';
    else $x = '.';
    $pass .= $x;
    $s = substr($s, 2);
  }
  return $pass;
}

/*!
		\brief fonction pour initialiser sel
		\remarks la fonction a été prise sur http://www.uzine.net/spip
*/

function initialiser_sel() {
  global $htsalt;

  $htsalt = '$1$'.creer_pass_aleatoire();
}


/*
 * Retourne le rowid d'un departement pas son code
 *
 */
Function departement_rowid($db,$code, $pays_id)
{
  $sql = "SELECT c.rowid FROM ".MAIN_DB_PREFIX."c_departements as c,".MAIN_DB_PREFIX."c_regions as r";
  $sql .= " WHERE c.code_departement=". $code;
  $sql .= " AND c.fk_region = r.code_region";
  $sql .= " AND r.fk_pays =".$pays_id;

  if ($db->query($sql))
    {
      $num = $db->num_rows();
      if ($num)
	{
	  $obj = $db->fetch_object(0);
	  return  $obj->rowid;
	}
      else
	{
	  return 0;
	}
      $db->free();
    }
  else
    {
      return 0;
    }
}


?>
