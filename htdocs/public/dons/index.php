<?php
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
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT ."/don.class.php");

if ($conf->don->enabled)
{
  $don = new Don($db);
      
  $don->projetid    = $_POST["projetid"];
  $don->date        = time();
  $don->prenom      = $_POST["prenom"];
  $don->nom         = $_POST["nom"];
  $don->societe     = $_POST["societe"];
  $don->adresse     = $_POST["adresse"];
  $don->cp          = $_POST["cp"];
  $don->ville       = $_POST["ville"];
  $don->pays        = $_POST["pays"];
  $don->public      = 1;
  if ($_POST["public"] == "FALSE")
    {
      $don->public      = 0;
    }
  $don->email       = $_POST["email"];
  $don->amount      = $_POST["montant"];
  $don->commentaire = $_POST["commentaire"];
  

  if ($_POST["action"] == 'add')
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
  elseif ($_POST["action"] == 'valid' && $_POST["valid"] == 'Valider')
    {
      
      if ($don->check($conf->don->minimum))
	{
	  $ref_commande = $don->create(0);
	  
	  if ($ref_commande)
	    {
	      $date_limite = dolibarr_print_date(time() + (3 * 7 * 24 * 3600), 'dayhourtext');

	      include ("mail.php");

	      include ("mail_moderator.php");

	      mail($don->email, $subject, $body, "From: contact@eucd.info");

	      mail($conf->don->email_moderator, $subject_moderator, $body_moderator);

	      require("merci.php");
	    }
	  else
	    {
	      print "Erreur : can't insert value in db";
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
  print "Cette fonctionnalit� n'est pas activ� sur ce site";
}


?>
