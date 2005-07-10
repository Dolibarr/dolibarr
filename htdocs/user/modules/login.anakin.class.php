<?php
/* Copyright (c) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (c) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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

/**
	    \file       htdocs/user/modules/login.anakin.class.php
  	    \brief      Fichier de la classe de génération de login anakin
  	    \author     Rodolphe Qiedeville
  	    \version    $Revision$
*/


/**
        \class      LoginAnakin
		\brief      Classe permettant la génération de login anakin
*/

class LoginAnakin
{
  var $db;
  var $user;

  /**
   *    \brief      Constructeur de la classe
   *    \param      $DB         Handler accès base de données
   *    \param      $user       Objet de l'utilisateur
   */
  function LoginAnakin($DB, $user)
    {
      $this->db = $DB;
      $this->user = $user;

      return 0;
  }


  function generate_login()
  {
    $ok = 0;
    $step = 1;
    $this->else_step = 1;

    while ($ok == 0)
      {
	$func = 'generate_login_'.$step;

	if (method_exists($this, $func))
	  {
	    $this->$func();
	  }
	else
	  {
	    $this->generate_login_else();
	    $this->else_step++;
	  }

	$ok = $this->dispo();
	$step++;
      }
    
    return 0;

  }

  /**
   *    \brief      Vérifie la disponibilité du login
   *    \return     1 si le login n'existe pas dans la base, 1 sinon
   */
  function dispo()
  {
    $sql = "SELECT login FROM ".MAIN_DB_PREFIX."user WHERE login ='".$this->login."';";

    $resql=$this->db->query($sql);

    if ($resql)
      {
	$num = $this->db->num_rows($resql);
      }

    if ($num == 0)
      {
	return 1;
      }
    else
      {
	return 0;
      }

  }


  // Règle primaire
  // 8 premières lettre désaccentuées du nom en minuscule
  function generate_login_1()
  {
    $nom = unaccent(strtolower($this->user->nom));
    
    $this->login = substr($nom, 0, 8);
  }


  // Règle de défaut
  function generate_login_else()
  {
    $login = unaccent(strtolower($this->user->nom));

    $le = strlen($this->else_step);

    $this->login = substr($login, 0, (8-$le)) . $this->else_step;
  }


  // Règles annexes
  // première lettre du prénom + 7 premières lettres du nom, désaccentuées en minuscule
  function generate_login_2()
  {
    $nom = unaccent(strtolower($this->user->nom));
    $prenom = unaccent(strtolower($this->user->prenom));
    
    $this->login = substr($prenom, 0, 1) . substr($nom, 0, 7);
  }


  // 2 premières lettres du prénom + 6 premières lettres du nom, désaccentuées en minuscule
  function generate_login_3()
  {
    $nom = unaccent(strtolower($this->user->nom));
    $prenom = unaccent(strtolower($this->user->prenom));
    
    $this->login = substr($prenom, 0, 2) . substr($nom, 0, 6);
  }

}
?>
