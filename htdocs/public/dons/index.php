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

  $db = new Db();
  $don = new Don($db);
      
  $don->projetid    = $HTTP_POST_VARS["projetid"];
  $don->date        = time();
  $don->prenom      = $HTTP_POST_VARS["prenom"];
  $don->nom         = $HTTP_POST_VARS["nom"];
  $don->societe     = $HTTP_POST_VARS["societe"];
  $don->adresse     = $HTTP_POST_VARS["adresse"];
  $don->cp          = $HTTP_POST_VARS["cp"];
  $don->ville       = $HTTP_POST_VARS["ville"];
  $don->pays        = $HTTP_POST_VARS["pays"];
  $don->public      = $HTTP_POST_VARS["public"];
  $don->email       = $HTTP_POST_VARS["email"];
  $don->amount      = $HTTP_POST_VARS["montant"];
  $don->commentaire = $HTTP_POST_VARS["commentaire"];
  

  if ($HTTP_POST_VARS["action"] == 'add')
    {
      
      if ($don->check($conf->don->minimum))
	{
	  require("valid.php");
	}
      else
	{
	  require("erreur.php");
	}     
    }
  elseif ($HTTP_POST_VARS["action"] == 'valid' && $HTTP_POST_VARS["valid"] == 'Valider')
    {
      
      if ($don->check($conf->don->minimum))
	{
	  $return = $don->create(0);
	  
	  if ($return)
	    {
	      $a = setlocale("LC_TIME", "FRENCH");
	      $date_limite = strftime("%A %d %B %Y",time() + (3 * 7 * 24 * 3600));

	      include ("mail.php");

	      mail($don->email, $subject, $body, "From: contact@eucd.info");

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
