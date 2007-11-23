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
 * $Source$
 *
 */

if ($conf->don->onlinepayment)
{

  require("../../cyberpaiement.class.php");

  $cyberp = new Cyberpaiement($conf);

  print "<form action=\"".$conf->bplc->url."\" method=\"post\">\n";

  $cyberp->set_client($_POST["nom"],
		      $_POST["prenom"],
		      $_POST["email"],
		      $_POST["societe"]);

  $cyberp->set_commande($ref_commande ."10",
			$_POST["montant"]);

  $cyberp->print_hidden();



  print "<input type=\"submit\" value=\"Payer en ligne avec Cyberpaiement\">";
  print "</form>";
}
