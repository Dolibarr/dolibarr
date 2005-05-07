<?PHP
/* Copyright (C) 2000-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 *
 */

/**
	    \file       htdocs/lib/functions.inc.php
		\brief      Ensemble de fonctions de base de dolibarr sous forme d'include
		\author     Rodolphe Quiedeville
		\author	    Jean-Louis Bergamo
		\author	    Laurent Destailleur
		\author     Sebastien Di Cintio
		\author     Benoit Mortier
		\version    $Revision$

		Ensemble de fonctions de base de dolibarr sous forme d'include
*/


/**
		\brief      Renvoi vrai si l'email est syntaxiquement valide
		\param	    address     adresse email (Ex: "toto@titi.com", "John Do <johndo@titi.com>")
        \return     boolean     true si email valide, false sinon
*/
function ValidEmail($address)
{
  if (ereg( ".*<(.+)>", $address, $regs)) {
    $address = $regs[1];
  }
  if (ereg( "^[^@  ]+@([a-zA-Z0-9\-]+\.)+([a-zA-Z0-9\-]{2}|net|com|gov|mil|org|edu|int)\$",$address))
    {
      return true;
    }
  else
    {
      return false;
    }
}

/**
		\brief      Renvoi vrai si l'email a un nom de domaine qui résoud via dns
		\param	    mail        adresse email (Ex: "toto@titi.com", "John Do <johndo@titi.com>")
        \return     boolean     true si email valide, false sinon
*/
function check_mail ($mail)
{
  list($user, $domain) = split("@", $mail, 2);
  if (checkdnsrr($domain, "MX"))
    {
      return true;
    }
  else
    {
      return false;
    }
}


/**
   \brief       Envoi des messages dolibarr dans syslog ou dans un fichier
                Pour syslog:    facility défini par MAIN_SYSLOG_FACILITY
                Pour fichier:   fichier défini par SYSLOG_FILE
   \param       message		message a envoyer a syslog
   \param       level       Niveau de l'erreur
   \remarks     Cette fonction a un effet que si le module syslog est activé.
                Warning, les fonctions syslog sont buggués sous Windows et génèrent des
                fautes de protection mémoire. Pour résoudre, utiliser le loggage fichier,
                au lieu du loggage syslog, en positionnant la constante MAIN_SYSLOG_FILE.
*/
function dolibarr_syslog($message, $level=LOG_ERR)
{
    if (defined("MAIN_MODULE_SYSLOG") && MAIN_MODULE_SYSLOG)
    {
        if (defined("SYSLOG_FILE") && SYSLOG_FILE)
        {
            $file=fopen(SYSLOG_FILE,"a+");
            if ($file) {
                fwrite($file,time()." ".$level." ".$message."\n");
                fclose($file);
            }
            else {
                print "Error: Failed to open file ".SYSLOG_FILE;   
            }
        }
        else
        {
            define_syslog_variables();
        
            if (defined("MAIN_SYSLOG_FACILITY") && MAIN_SYSLOG_FACILITY)
            {
                $facility = MAIN_SYSLOG_FACILITY;
            }
            else
            {
                $facility = LOG_USER;
            }
        
            openlog("dolibarr", LOG_PID | LOG_PERROR, $facility);
        
            if (! $level)
            {
                syslog(LOG_ERR, $message);
            }
            else
            {
                syslog($level, $message);
            }
        
            closelog();
        }
    }
}


/**
		\brief      Affiche le header d'une fiche
		\param	    links		liens
		\param	    active      0 par défaut
		\param      title       titre ("" par defaut)
*/
function dolibarr_fiche_head($links, $active=0, $title='')
{
  print "<!-- fiche --><div class=\"tabs\">\n";

  if (strlen($title))
    {
        $limittitle=30;
        if (strlen($title) > $limittitle) print '<a class="tabTitle">'.substr($title,0,$limittitle).'...</a>';
        else print '<a class="tabTitle">'.$title.'</a>';
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

/**
		\brief      Insertion d'une constante dans la base de données.
		\see        dolibarr_del_const
		\param	    db          handler d'accès base
		\param	    name		nom de la constante
		\param	    value		valeur de la constante
		\param	    type		type de constante (chaine par défaut)
		\param	    visible	    la constante est t'elle visible (0 par défaut)
		\param	    note		explication de la constante
		\return     int         0 si KO, 1 si OK
*/
function dolibarr_set_const($db, $name, $value, $type='chaine', $visible=0, $note='')
{
        $db->begin();
        
        $sql = "DELETE FROM llx_const WHERE name = '$name';"; 		
        $resql=$db->query($sql);	
        
        $sql = "INSERT INTO llx_const(name,value,type,visible,note)";
        $sql.= " VALUES ('$name','".addslashes($value)."','$type',$visible,'$note');";
        $resql=$db->query($sql);	

        if ($resql)
        {
            $db->commit();
            return 1;
        }
        else
        {
            $db->rollback();
            return 0;
        }
}

/**
		\brief      Effacement d'une constante dans la base de données
        \see        dolibarr_set_const
		\param	    db          handler d'accès base
		\param	    name		nom ou rowid de la constante
		\return     int         0 si KO, 1 si OK
*/
function dolibarr_del_const($db, $name)
{
    $sql = "DELETE FROM llx_const WHERE name='$name' or rowid='$name'";
    $resql=$db->query($sql);

    if ($resql)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}

/**
		\brief  Formattage des nombres
		\param	ca			valeur a formater
		\return	int			valeur formatée
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

/**
		\brief  Formattage de la date
		\param	time        date timestamp ou au format YYYY-MM-DD
		\param	format      format de la date "%d %b %Y"
		\return string      date formatée
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


/**
		\brief  Affiche les informations d'un objet
		\param	object			objet a afficher
*/
function dolibarr_print_object_info($object)
{
    global $langs;
        
    if (isset($object->user_creation))
        print $langs->trans("CreatedBy")." : " . $object->user_creation->fullname . '<br>';

    if (isset($object->date_creation))
        print $langs->trans("DateCreation")." : " . dolibarr_print_date($object->date_creation,"%A %d %B %Y %H:%M:%S") . '<br>';
    
    if (isset($object->user_modification))
        print $langs->trans("ModifiedBy")." : " . $object->user_modification->fullname . '<br>';
        
    if (isset($object->date_modification))
        print $langs->trans("DateModification")." : " . dolibarr_print_date($object->date_modification,"%A %d %B %Y %H:%M:%S") . '<br>';
    
    if (isset($object->user_validation))
        print $langs->trans("ValidatedBy")." : " . $object->user_validation->fullname . '<br>';
    
    if (isset($object->date_validation))
        print $langs->trans("DateValidation")." : " . dolibarr_print_date($object->date_modification,"%A %d %B %Y %H:%M:%S") . '<br>';

    if (isset($object->user_cloture))
        print $langs->trans("ClosedBy")." : " . $object->user_cloture->fullname . '<br>';

    if (isset($object->date_cloture))
        print $langs->trans("DateClosing")." : " . dolibarr_print_date($object->date_modification,"%A %d %B %Y %H:%M:%S") . '<br>';
}

/**
        \brief      Formatage des numéros de telephone en fonction du format d'un pays
        \param	    phone			Numéro de telephone à formater
        \param	    country			Pays selon lequel formatter
        \return     string			Numéro de téléphone formaté
*/
function dolibarr_print_phone($phone,$country="FR")
{
    $phone=trim($phone);
    if (strstr($phone, ' ')) { return $phone; }
    if (strtoupper($country) == "FR") {
        // France
        if (strlen($phone) == 10) {
            return substr($phone,0,2)."&nbsp;".substr($phone,2,2)."&nbsp;".substr($phone,4,2)."&nbsp;".substr($phone,6,2)."&nbsp;".substr($phone,8,2);
        }
        elseif (strlen($phone) == 7)
        {
            return substr($phone,0,3)."&nbsp;".substr($phone,3,2)."&nbsp;".substr($phone,5,2);
        }
        elseif (strlen($phone) == 9)
        {
            return substr($phone,0,2)."&nbsp;".substr($phone,2,3)."&nbsp;".substr($phone,5,2)."&nbsp;".substr($phone,7,2);
        }
        elseif (strlen($phone) == 11)
        {
            return substr($phone,0,3)."&nbsp;".substr($phone,3,2)."&nbsp;".substr($phone,5,2)."&nbsp;".substr($phone,7,2)."&nbsp;".substr($phone,9,2);
        }
        elseif (strlen($phone) == 12)
        {
            return substr($phone,0,4)."&nbsp;".substr($phone,4,2)."&nbsp;".substr($phone,6,2)."&nbsp;".substr($phone,8,2)."&nbsp;".substr($phone,10,2);
        }
    }
    return $phone;
}

/**
        \brief      Tronque une chaine à une taille donnée en ajoutant les points de suspension si cela dépasse
        \param      string			Chaine à tronquer
        \param      size			Longueur max de la chaine
        \return     string			Chaine tronquée
*/
function dolibarr_trunc($string,$size=40)
{
    if (sizeof($string) > $size) return substr($string,0,$size).'...';
    else return $string;  
}

/**
        \brief  Affiche logo propre à une notion (fonction générique)
        \param  alt     Texte sur le alt de l'image
        \param  object  Objet pour lequel il faut afficher le logo (exemple: user, group, action, bill, contract, propal, product, ...)
*/
function img_object($alt, $object)
{
  global $conf,$langs;
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/object_'.$object.'.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo action
        \param  alt         Texte sur le alt de l'image
        \param  numaction   Determine image action
*/
function img_action($alt = "default", $numaction)
{
  global $conf,$langs;
  if ($alt=="default") {
    if ($numaction == -1) $alt=$langs->trans("ChangeDoNotContact");
    if ($numaction == 0)  $alt=$langs->trans("ChangeNeverContacted");
    if ($numaction == 1)  $alt=$langs->trans("ChangeToContact");
    if ($numaction == 2)  $alt=$langs->trans("ChangeContactInProcess");
    if ($numaction == 3)  $alt=$langs->trans("ChangeContactDone");
  }
  return '<img align="absmiddle" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/stcomm'.$numaction.'.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo fichier
        \param  alt     Texte sur le alt de l'image
*/
function img_file($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Show");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/file.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo dossier
        \param  alt     Texte sur le alt de l'image
*/
function img_folder($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Dossier");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/folder.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo nouveau fichier
        \param  alt     Texte sur le alt de l'image
*/
function img_file_new($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Show");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo pdf
        \param  alt     Texte sur le alt de l'image
*/
function img_pdf($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Show");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/pdf.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo +
        \param  alt     Texte sur le alt de l'image
*/
function img_edit_add($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Add");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit_add.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}
/**
        \brief  Affiche logo -
        \param  alt     Texte sur le alt de l'image
*/
function img_edit_remove($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Remove");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit_remove.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo editer/modifier fiche
        \param  alt     Texte sur le alt de l'image
*/
function img_edit($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Modify");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo effacer
        \param  alt     Texte sur le alt de l'image
*/
function img_delete($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Delete");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo désactiver
        \param  alt     Texte sur le alt de l'image
*/
function img_disable($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Disable");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/disable.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}


/**
        \brief  Affiche logo info
        \param  alt     Texte sur le alt de l'image
*/
function img_info($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Informations");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/info.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo warning
        \param  alt     Texte sur le alt de l'image
*/
function img_warning($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Warning");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/warning.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo warning
        \param  alt     Texte sur le alt de l'image
*/
function img_error($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Error");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/error.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo alerte
        \param  alt     Texte sur le alt de l'image
*/
function img_alerte($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Alert");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/alerte.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo téléphone in
        \param  alt     Texte sur le alt de l'image
*/
function img_phone_in($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Modify");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/call.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo téléphone out
        \param  alt     Texte sur le alt de l'image
*/
function img_phone_out($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Modify");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/call.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo suivant
        \param  alt     Texte sur le alt de l'image
*/
function img_next($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") {
    $alt=$langs->trans("Next");
  }
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/next.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo précédent
        \param  alt     Texte sur le alt de l'image
*/
function img_previous($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Previous");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/previous.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo bas
        \param  alt     Texte sur le alt de l'image
*/
function img_down($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Down");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1downarrow.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
        \brief  Affiche logo haut
        \param  alt     Texte sur le alt de l'image
*/
function img_up($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Up");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1uparrow.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}


/**
        \brief  Affiche logo tick
        \param  alt     Texte sur le alt de l'image
*/
function img_tick($alt = "default")
{
  global $conf,$langs;
  if ($alt=="default") $alt=$langs->trans("Active");
  return '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

/**
		\brief      fonction de login
		\remarks    il faut changer le code html dans la fonction pour changer le design
*/
function loginfunction()
{
  global $langs;
  
  print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
  print "\n<html><head><title>Dolibarr Authentification</title>";
  print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/lib/login.css">
  </head>
  <body onload="donnefocus();">
  <div class="main">
  <div class="header">';
  print 'Dolibarr '.DOL_VERSION;
  print '
  </div>
  <div class="main-inside">
  ';

  print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="identification">';
  print '<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">';
  print '<table width="100%">';
  print '<tr><td align="left">'.$langs->trans("Login").': </td><td><input type="text" name="username"></td></tr>';;
  print '<tr><td align="left">'.$langs->trans("Password").': </td><td><input type="password" name="password"></td></tr>';
  print '</table>';
  print '</td></tr></table>';
  print '<br><center><input value="'.$langs->trans("Connexion").'" type="submit" class="button"></center>';
  print '</form>';
}


/**
		\brief      Affiche message erreur de type acces interdit et arrete le programme
		\remarks    L'appel a cette fonction termine le code.
*/
function accessforbidden()
{
  global $user, $langs;
  $langs->load("other");
  
  llxHeader();
  print '<div class="error">'.$langs->trans("ErrorForbidden").'</div>';
  print '<br>';
  if ($user->login)
  {
    print $langs->trans("CurrentLogin").': <font class="error">'.$user->login.'</font><br>';
    print $langs->trans("ErrorForbidden2",$langs->trans("Home"),$langs->trans("Users"));
  }
  elseif (! empty($_SERVER["REMOTE_USER"]))
  {
    print $langs->trans("CurrentLogin").': <font class="error">'.$_SERVER["REMOTE_USER"]."</font><br>";
    print $langs->trans("ErrorForbidden2",$langs->trans("Home"),$langs->trans("Users"));
  }
  else
  {
    print $langs->trans("ErrorForbidden3");
  }
  llxFooter();
  exit(0);
}


/**
		\brief      Affiche message erreur system avec toutes les informations pour faciliter le diagnostique et la remontée des bugs.
                    On doit appeler cette fonction quand une erreur technique bloquante est rencontrée.
                    Toutefois, il faut essayer de ne l'appeler qu'au sein de page php, les classes devant
                    renvoyer leur erreur par l'intermédiaire de leur propriété "error".
        \param      db      Handler de base utilisé
        \param      msg     Message complémentaire à afficher
*/
function dolibarr_print_error($db='',$msg='')
{
    global $langs;
    $syslog = '';
    
    // Si erreur intervenu avant chargement langue
    if (! $langs) {
        require_once(DOL_DOCUMENT_ROOT ."/translate.class.php");
        $langs = new Translate(DOL_DOCUMENT_ROOT ."/langs", "en_US");
        $langs->load("main");
    }
    
    if ($_SERVER['DOCUMENT_ROOT'])    // Mode web
    {
        print $langs->trans("DolibarrHasDetectedError").".<br>\n";
        print $langs->trans("InformationToHelpDiagnose").":<br><br>\n";
    
        print "<b>".$langs->trans("Server").":</b> ".$_SERVER["SERVER_SOFTWARE"]."<br>\n";;
        print "<b>".$langs->trans("RequestedUrl").":</b> ".$_SERVER["REQUEST_URI"]."<br>\n";;
        print "<b>QUERY_STRING:</b> ".$_SERVER["QUERY_STRING"]."<br>\n";;
        print "<b>".$langs->trans("Referer").":</b> ".$_SERVER["HTTP_REFERER"]."<br>\n";;
        $syslog.="url=".$_SERVER["REQUEST_URI"];
        $syslog.=", query_string=".$_SERVER["QUERY_STRING"];
    }
    else                              // Mode CLI
    {
    
        print $langs->trans("ErrorInternalErrorDetected")."...\n";
        $syslog.="pid=".getmypid();
    }
    
    if ($db) {
        if ($_SERVER['DOCUMENT_ROOT'])  // Mode web
        {
            print "<br>\n";
            print "<b>".$langs->trans("DatabaseTypeManager").":</b> ".$db->type."<br>\n";
            print "<b>".$langs->trans("RequestLastAccess").":</b> ".($db->lastquery()?$db->lastquery():$langs->trans("ErrorNoRequestRan"))."<br>\n";
            print "<b>".$langs->trans("ReturnCodeLastAccess").":</b> ".$db->errno()."<br>\n";
            print "<b>".$langs->trans("InformationLastAccess").":</b> ".$db->error()."<br>\n";
        }
        else                            // Mode CLI
        {
            print $langs->trans("DatabaseTypeManager").":\n".$db->type."\n";
            print $langs->trans("RequestLastAccess").":\n".($db->lastquery()?$db->lastquery():$langs->trans("ErrorNoRequestRan"))."\n";
            print $langs->trans("ReturnCodeLastAccess").":\n".$db->errno()."\n";
            print $langs->trans("InformationLastAccess").":\n".$db->error()."\n";
    
        }
        $syslog.=", sql=".$db->lastquery();
        $syslog.=", db_error=".$db->error();
    }
    
    if ($msg) {
        if ($_SERVER['DOCUMENT_ROOT'])  // Mode web
        {
            print "<b>".$langs->trans("Message").":</b> ".$msg."<br>\n" ;
        }
        else                            // Mode CLI
        {
            print $langs->trans("Message").":\n".$msg."\n" ;
        }
        $syslog.=", msg=".$msg;
    }
    
    dolibarr_syslog("Error $syslog");
}


/**
		\brief  Deplacer les fichiers telechargés
		\param	src_file	fichier source
		\param	dest_file	fichier de destination
		\return int         le resultat du move_uploaded_file
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


/**
		\brief  
		\param	db      handler d'accès base
		\param	user    object utilisateur
*/
function dolibarr_user_page_param($db, &$user)
{
  foreach ($GLOBALS["_GET"] as $key=>$value)
    {
      if ($key == "sortfield")
	{
	
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_param WHERE fk_user = $user->id ;"; 
	  
		$db->query($sql);
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_param(fk_user,page,param,value) VALUES
		($user->id,'".$GLOBALS["SCRIPT_URL"]."','sortfield','".urlencode($value)."');";

	  $db->query($sql);
	  
		$user->page_param["sortfield"] = $value;
	}

      if ($key == "sortorder")
	{    
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_param WHERE fk_user = $user->id ;"; 
	  
		$db->query($sql);
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_param(fk_user,page,param,value) VALUES
		($user->id,'".$GLOBALS["SCRIPT_URL"]."','sortfield','".urlencode($value)."');";
		
	  $db->query($sql);
		
	  $user->page_param["sortorder"] = $value;
	}
      if ($key == "begin")
	{
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_param WHERE fk_user = $user->id ;"; 
	  
		$db->query($sql);
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_param(fk_user,page,param,value) VALUES
		($user->id,'".$GLOBALS["SCRIPT_URL"]."','sortfield','".urlencode($value)."');	";
	  
		$db->query($sql);
		
	  $user->page_param["begin"] = $value;
	}
      if ($key == "page")
	{
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_param WHERE fk_user = $user->id ;"; 
	  
		$db->query($sql);
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_param(fk_user,page,param,value) VALUES
		($user->id,'".$GLOBALS["SCRIPT_URL"]."','sortfield','".urlencode($value)."');	";
	  
		$db->query($sql);
		
	  $user->page_param["page"] = $value;
	}
    }
}

/**
		\brief  Transcodage de francs en euros
		\param	zonein		zone de depart
		\param	devise		type de devise
		\return	r           resultat transcodé
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


/**
		\brief      Affichage de la ligne de titre d'un tabelau
		\param	    name
		\param	    file
		\param	    field
		\param	    begin       ("" par defaut)
		\param	    options     ("" par defaut)
		\param      td          paramètres de l'attribut td ("" par defaut)
		\param      sortfield   nom du champ sur lequel est effectué le tri du tableau
*/
function print_liste_field_titre($name, $file, $field, $begin="", $options="", $td="", $sortfield="")
{
    global $conf;
    // Le champ de tri est mis en évidence.
    // Exemple si (sortfield,field)=("nom","xxx.nom") ou (sortfield,field)=("nom","nom")
    if ($sortfield == $field || $sortfield == ereg_replace("^[^\.]+\.","",$field))
    {
        print '<td class="liste_titre_sel" '. $td.'>';
    }
    else
    {
        print '<td class="liste_titre" '. $td.'>';
    }
    print $name."&nbsp;";
    print '<a href="'.$file.'?sortfield='.$field.'&amp;sortorder=asc&amp;begin='.$begin.$options.'">'.img_down("A-Z").'</a>';
    print '<a href="'.$file.'?sortfield='.$field.'&amp;sortorder=desc&amp;begin='.$begin.$options.'">'.img_up("Z-A").'</a>';
    print "</td>";
}

/**
		\brief  Affichage d'un titre
		\param	titre			Le titre a afficher
*/
function print_titre($titre)
{
  print '<div class="titre">'.$titre.'</div>';
}

/**
		\brief  Affichage d'un titre d'une fiche, aligné a gauche
		\param	titre			Le titre a afficher
		\param	mesg			Message suplémentaire à afficher à droite
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

/**
		\brief  Effacement d'un fichier
		\param	file			fichier a effacer
*/
function dol_delete_file($file)
{
  return unlink($file);
}


/**
		\brief  Fonction print_barre_liste
		\param	titre			titre de la page
		\param	page			numéro de la page
		\param	file			lien
		\param	options
		\param	sortfield
		\param	sortorder
		\param	form
		\param	num             nombre d'élément total
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

  print '<table width="100%" class="noborder">';

 if ($page > 0 || $num > $conf->liste_limit)
   {
     print '<tr><td><div class="titre">'.$titre.' - page '.($page+1);
     print '</div></td>';
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

  // Affichage des fleches de navigation

  print_fleche_navigation($page,$file,$options,$nextpage);

  print '</td></tr></table><br>';
}

/**
		\brief  Fonction servant a afficher les fleches de navigation dans les pages de listes
		\param	page			numéro de la page
		\param	file			lien
		\param	options         autres parametres d'url a propager dans les liens ("" par defaut)
		\param	nextpage	    faut-il une page suivante
*/
function print_fleche_navigation($page,$file,$options='',$nextpage)
{
  global $conf, $langs;
  if ($page > 0)
    {
      print '<a href="'.$file.'?page='.($page-1).$options.'">'.img_previous($langs->trans("Previous")).'</a>';
    }

  if ($nextpage > 0)
    {
      print '<a href="'.$file.'?page='.($page+1).$options.'">'.img_next($langs->trans("Next")).'</a>';
    }
}


/**
		\brief      Fonction servant a afficher un menu déroulant sur le type de paiement
		\param	    db          handler d'accès base
		\param	    nomselect   Nom de la zone select html
		\param	    value       Critere de filtrage sur les type de paiement
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
            $obj = $db->fetch_object();
            print "<option value=\"$obj->code\">$obj->libelle</option>";
            $i++;
        }

    }
  print "</select>\n";
}


/**
		\brief  Fonction servant a afficher les heures/minutes dans un liste déroulante
		\param	prefix
		\param	begin (1 par defaut)
		\param	end (23 par defaut)
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

/**
		\brief  Fonction servant a afficher une durée dans une liste déroulante
		\param	prefix  prefix
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


/**
		\brief  Fonction qui retourne un montant monétaire formaté
		\param	amount		montant a formater
		\param	html		formatage html ou pas (0 par defaut)
		\remarks fonction utilisée dans les pdf et les pages html

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

/**
		\brief  Fonction qui convertit des euros en francs
		\param	euros   	somme en euro à convertir
		\return price       prix converti et formaté    
*/
function francs($euros)
{
  return price($euros * 6.55957);
}

/**
		\brief  Fonction qui calcule la tva
		\param	euros			somme en euro
		\param	taux			taux de tva
*/
function tva($euros, $taux=19.6)
{
  $taux = $taux / 100 ;

  return sprintf("%01.2f",($euros * $taux));
}

/**
		\brief  Fonction qui calcule le montant tva incluse
		\param	euros			somme en euro
		\param	taux			taux de tva
*/
function inctva($euros, $taux=1.196)
{
  return sprintf("%01.2f",($euros * $taux));
}

/**
		\brief  Renvoie oui ou non dans la langue choisie
		\param	yesno			variable pour test si oui ou non
		\param	case			Oui/Non ou oui/non
*/
function yn($yesno, $case=1) {
    global $langs;
    if ($yesno == 0 || $yesno == 'no' || $yesno == 'false') 
        return $case?$langs->trans("No"):$langs->trans("no");
    if ($yesno == 1 || $yesno == 'yes' || $yesno == 'true') 
        return $case?$langs->trans("Yes"):$langs->trans("yes");
    return "unknown";
}


/**
		\brief      Fonction pour créer un mot de passe aléatoire
		\param	    longueur    longueur du mot de passe (8 par defaut)
		\param	    sel			donnée aléatoire
		\remarks    la fonction a été prise sur http://www.uzine.net/spip
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

/**
		\brief      Fonction pour initialiser sel
		\remarks    la fonction a été prise sur http://www.uzine.net/spip
*/
function initialiser_sel() {
  global $htsalt;

  $htsalt = '$1$'.creer_pass_aleatoire();
}

/**
		\brief  Fonction pour qui retourne le rowid d'un departement par son code
		\param  db          handler d'accès base
		\param	code		Code région
		\param	pays_id		Id du pays
*/
function departement_rowid($db,$code, $pays_id)
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
	  $obj = $db->fetch_object();
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

/**
 *      \brief      Renvoi un chemin de classement répertoire en fonction d'un id
 *                  Examples: 1->"0/0/1/", 15->"0/1/5/"
 *      \param      $num        id à décomposer
 */
function get_exdir($num)
{
    $num = substr("000".$num, -3);
    return substr($num, 0,1).'/'.substr($num, 1,1).'/'.substr($num, 2,1).'/';
}

/**
 *      \brief      Création de répertoire recursive
 *      \param      $dir        Répertoire à créer
 *      \return     int         < 0 si erreur, >= 0 si succès
 */
function create_exdir($dir)
{
    $nberr=0;
    $nbcreated=0;

    $ccdir = '';
    $cdir = explode("/",$dir);
    for ($i = 0 ; $i < sizeof($cdir) ; $i++)
    {
        if ($i > 0) $ccdir .= '/'.$cdir[$i];
        else $ccdir = $cdir[$i];
        if (eregi("^.:$",$ccdir,$regs)) continue;     // Si chemin Windows incomplet, on poursuit par rep suivant

        //print "${ccdir}<br>\n";
        if ($ccdir && ! file_exists($ccdir))
        {
            umask(0);
            if (! @mkdir($ccdir, 0755))
            {
                dolibarr_syslog("functions.inc.php::create_exdir Erreur: Le répertoire '$ccdir' n'existe pas et Dolibarr n'a pu le créer.");
                $nberr++;
            }
            else
            {
                dolibarr_syslog("functions.inc.php::create_exdir Répertoire '$ccdir' created");
                $nbcreated++;
            }
        }
    }
    return ($nberr ? -$nberr : $nbcreated);
}

?>
