<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003-2005 Éric Seigne <eric.seigne@ryxeo.com>
 * Copyright (C) 2006      Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/boutique/client/fiche.php
		\ingroup    boutique
		\brief      Page fiche client OSCommerce
		\version    $Revision$
*/

require("./pre.inc.php");

llxHeader();

if ($action == 'update' && !$cancel) {
  $client = new Client($dbosc);
  $client->nom = $nom;
  $client->update($id, $user);
}

/*
 *
 *
 */
if ($_GET['id'])
{

  $client = new Client($dbosc);
  $result = $client->fetch($_GET['id']);
  if ( $result )
    {
      print '<div class="titre">Fiche Client : '.$client->name.'</div><br>';

      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
      print "<tr>";
      print '<td width="20%">Nom</td><td width="80%">'.$client->name.'</td></tr>';
      print "</table>";


      /*
       * Commandes
       *
       */
      $sql = "SELECT o.orders_id, o.customers_id,".$dbosc->pdate("date_purchased")." as date_purchased, t.value as total";
      $sql .= " FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."orders as o, ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."orders_total as t";
      $sql .= " WHERE o.customers_id = " . $client->id;
      $sql .= " AND o.orders_id = t.orders_id AND t.class = 'ot_total'";
      if ( $dbosc->query($sql) )
	{
	  $num = $dbosc->num_rows();
	  $i = 0;
	  print '<table class="noborder" width="50%">';
	  print "<tr class=\"liste_titre\"><td>Commandes</td>";
	  print "</tr>\n";
	  $var=True;
	  while ($i < $num) {
	    $objp = $dbosc->fetch_object();
	    $var=!$var;
	    print "<tr $bc[$var]>";

	    print '<td><a href="'.DOL_URL_ROOT.'/boutique/commande/fiche.php?id='.$objp->orders_id.'"><img src="/theme/'.$conf->theme.'/img/filenew.png" border="0" alt="Fiche">&nbsp;';

	    print dol_print_date($objp->date_purchased,'dayhour')."</a>\n";
	    print $objp->total . "</a></TD>\n";
	    print "</tr>\n";
	    $i++;
	  }
	  print "</table>";
	  $dbosc->free();
	}
      else
	{
	  print "<p>ERROR 1</p>\n";
	  dol_print_error($dbosc);
	}

    }
  else
    {
      print "<p>ERROR 1</p>\n";
      dol_print_error($dbosc);
    }


}
else
{
  print "<p>ERROR 1</p>\n";
  print "Error";
}


/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

// Pas d'action


$dbosc->close();

llxFooter('$Date$ - $Revision$');
?>
