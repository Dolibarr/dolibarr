<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class mod_codeclient_zebre
{

  function mod_codeclient_zebre()
  {
    $this->nom = "Zèbre";
  }
  /*!     \brief      Renvoi la description du module
   *      \return     string      Texte descripif
   */
  function info()
    {
      return "Vérifie si le code client est de la forme ABCD5600. Les quatres premières lettres étant une représentation mnémotechnique, suivi du code postal en 2 chiffres et un numéro d'ordre pour la prise en compte des doublons.";
    }

  function verif($db, $code)
    { 
      $res = 0;


      $code = strtoupper(trim($code));

      if (strlen($code) <> 8)
	{
	  $res = -1;
	}
      else
	{
	  if ($this->is_alpha(substr($code,0,4)) == 0 && $this->is_num(substr($code,4,4)) == 0 )
	    {
	      $res = 0;	      
	    }
	  else
	    {
	      $res = -2; 
	    }

	}
      return $res;
    }

  function is_alpha($str)
  {
    $ok = 0;
    // Je n'ai pas trouvé de fonction pour tester une chaine alpha sans les caractère accentués
    // dommage
    $alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';      

    for ($i = 0 ; $i < 4 ; $i++)
      {
	if (strpos($alpha, substr($str,$i, 1)) === false)
	{
	  $ok++;
	}
      }
    
    return $ok;
  }

  function is_num($str)
  {
    $ok = 0;

    $alpha = '0123456789';

    for ($i = 0 ; $i < 4 ; $i++)
      {
	if (strpos($alpha, substr($str,$i, 1)) === false)
	{
	  $ok++;
	}
      }
    
    return $ok;
  }

}

?>
