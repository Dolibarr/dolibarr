<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

require("../../don.class.php");
require("../../lib/mysql.lib.php3");
require("../../conf/conf.class.php3");

$conf = new Conf();

if ($conf->don->enabled)
{

  if ($HTTP_POST_VARS["action"] == 'add')
    {

      $db = new Db();
      $don = new Don($db);
      
      $don->projetid    = $HTTP_POST_VARS["projetid"];
      $don->date        = time();
      $don->nom         = $HTTP_POST_VARS["nom"];
      $don->adresse     = $HTTP_POST_VARS["adresse"];
      $don->cp          = $HTTP_POST_VARS["cp"];
      $don->ville       = $HTTP_POST_VARS["ville"];
      $don->public      = $HTTP_POST_VARS["public"];
      $don->email       = $HTTP_POST_VARS["email"];
      $don->amount      = $HTTP_POST_VARS["montant"];
      $don->commentaire = $HTTP_POST_VARS["commentaire"];
      
      
      if ($don->check())
	{
	  require("valid.php");
	}
      else
	{
	  require("erreur.php");
	}     
    }
  elseif ($HTTP_POST_VARS["action"] == 'valid')
    {

      $db = new Db();
      $don = new Don($db);
  
      $don->projetid = $HTTP_POST_VARS["projetid"];
      $don->date     = time();
      $don->nom      = $HTTP_POST_VARS["nom"];
      $don->adresse  = $HTTP_POST_VARS["adresse"];
      $don->cp       = $HTTP_POST_VARS["cp"];
      $don->ville    = $HTTP_POST_VARS["ville"];
      $don->public   = $HTTP_POST_VARS["public"];
      $don->email    = $HTTP_POST_VARS["email"];
      $don->amount   = $HTTP_POST_VARS["montant"];
      $don->commentaire = $HTTP_POST_VARS["commentaire"];      
      
      if ($don->check())
	{
	  $return = $don->create(0);
	  
	  if ($return)
	    {
	      require("merci.php");
	    }
	}
      else
	{
	  require("erreur.php");
	}
    }
  else
    {
      require("don.php");
    }
}
else
{
  print "Cette fonctionnalité n'est pas activé sur ce site";
}


?>
