<?PHP
/* Copyright (C) 2000-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 *
 */

$yn[0] = "non";
$yn[1] = "oui";

function loginFunction()
{
  /**
   * Change the HTML output so that it fits to your
   * application.     */
  print '<html><head><title>Dolibarr Authentification</title></head><body>';
  print '<p>Dolibarr Authentification';
  echo "<form method=\"post\" action=\"" . $_SERVER['PHP_SELF'] . "\">";
  print '<table><tr>';
  print '<td>Login:</td><td><input type="text" name="username"></td></tr>';;
  print '<tr><td>Password:</td><td><input type="password" name="password"></td></tr>';
  echo '<tr><td colspan="2" align="center"><input value="Login" type="submit"></td></tr>';
  echo "</table></form></p></body></html>";
}


function accessforbidden()
{
  llxHeader();
  print "Accés interdit";
  llxFooter();
  exit(0);
}

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



function print_liste_field_titre($name, $file, $field, $begin="", $options="")
 {
  global $conf;

  print $name."&nbsp;";
  print '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">';
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1downarrow.png" border="0"></a>';
  print '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">';
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1uparrow.png" border="0"></a>';
}

function print_liste_field_titre_new($name, $file, $field, $begin="", $options="", $td="", $sortfield="")
 {
   /*
    * idem à la fonction ci dessus mais ajoute des fonctionnalités
    *
    *
    */
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
  print '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">';
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1downarrow.png" border="0"></a>';
  print '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">';
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1uparrow.png" border="0"></a>';
  print "</td>";
}

function print_titre($titre)
{
  print '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
  print '<tr><td><div class="titre">'.$titre.'</div></td>';
  print '</tr></table>';
}
/*
 *
 *
 */
function print_fiche_titre($titre, $mesg='')
{
  print '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
  print '<tr><td><div class="titre">'.$titre.'</div></td>';
  if (strlen($mesg))
    {
      print '<td align="right"><b>'.$mesg.'</b></td>';
    }
  print '</tr></table>';
}
/*
 *
 *
 */

function dol_delete_file($file)
{
  return unlink($file);
}
/*
 *
 *
 */
function block_access()
{
  llxHeader();
  print "Accés refusé";
  llxFooter();
} 

/*
 *
 *
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
      $options .= "&sortfield=$sortfield";
    }

  if (strlen($sortorder))
    {
      $options .= "&sortorder=$sortorder";
    }

  // affichage des fleches de navigation

  print_fleche_navigation($page,$file,$options, $nextpage);

  print '</td></tr></table><p>';
}

/*
 * fonction servant a afficher les fleches de navigation dans les
 * pages de liste
 */
function print_fleche_navigation($page,$file,$options='', $nextpage)
{
  global $conf;
  if ($page > 0) 
    {
      print '<a href="'.$file.'?page='.($page-1).$options.'"><img alt="Page précédente" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1leftarrow.png" border="0"></a>';
    }

  if ($nextpage > 0) 
    {
      print '<a href="'.$file.'?page='.($page+1).$options.'"><img alt="Page suivante" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1rightarrow.png" border="0"></a>';
    }
}
/*
 *
 *
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
/*
 *
 *
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
    
  $smonth = 1;
  $sday = 1;

  $cday = date("d", $set_time);
  $cmonth = date("n", $set_time);
  $syear = date("Y", $set_time);

  print "<select name=\"reday\">";    

  for ($day = 1 ; $day < $sday + 32 ; $day++) 
    {
      if ($day == $cday)
	{
	  print "<option value=\"$day\" SELECTED>$day";
	}
      else 
	{
	  print "<option value=\"$day\">$day";
	}
    }

  print "</select>";


  print "<select name=\"remonth\">";    
  for ($month = $smonth ; $month < $smonth + 12 ; $month++)
    {
      if ($month == $cmonth)
	{
	  print "<option value=\"$month\" SELECTED>" . $strmonth[$month];
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
	  print "<option value=\"$year\" SELECTED>$year";
	}
      else
	{
	  print "<option value=\"$year\">$year";
	}
    }
  print "</select>\n";
  
}
/*
 *
 *
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
/*
 *
 *
 */
function print_duree_select($prefix)
{  
  print '<select name="'.$prefix.'hour">';
  print "<option value=\"0\">0";
  print "<option value=\"1\" SELECTED>1";

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

function price($amount)
{
  return number_format($amount, 2, '.', ' ');
  //return sprintf("%.2f", $amount);
}


function francs($euros)
{
  return price($euros * 6.55957);
}

function tva($euros, $taux=19.6)
{
  $taux = $taux / 100 ;

  return sprintf("%01.2f",($euros * $taux));
}
function inctva($euros, $taux=1.196)
{
  return sprintf("%01.2f",($euros * $taux));
}


/*
 *
 *
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

/*
 * logfile : permet de logguer dans un fichier
 * cette fonction ne fonctionenra que si et seulement si le fichier de
 * la constante globale MAIN_DEBUG existe et vaut 1
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

/*
 * Fonctions reprise sur spip
 * http://www.uzine.net/spip/
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

/*
 * Fonctions reprise sur spip
 * http://www.uzine.net/spip/
 */

function initialiser_sel() {
  global $htsalt;
  
  $htsalt = '$1$'.creer_pass_aleatoire();
}

?>
