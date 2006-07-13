<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

require("./pre.inc.php");

$langs->load("companies");

llxHeader();


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Liste des articles de la boutique web", $page, "index.php");

set_magic_quotes_runtime(0);

//WebService Client.
require_once(NUSOAP_PATH."nusoap.php");
require_once("../includes/configure.php");

// Set the parameters to send to the WebService
$parameters = array();

// Set the WebService URL
$client = new soapclient(OSCWS_DIR."ws_articles.php");

$result = $client->call("get_listearticles",$parameters );
if ($client->fault) {
  		dolibarr_print_error("erreur de connection ");
}
elseif (!($err = $client->getError()) )
{
	$num=0;
  	if ($result) $num = sizeof($result);
	$var=True;
  	$i=0;

  	if ($num > 0) {
		print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
		print '<TR class="liste_titre">';
		print "<td>id</td>";
		print "<td>Ref</td>";
		print "<td>Titre</td>";
		print "<td>Groupe</td>";
		print '<td align="center">Stock</td>';
		print '<TD align="center">Status</TD>';
  		print "</TR>\n";
	   
		while ($i < $num) {
      		$var=!$var;

		    print "<TR $bc[$var]>";
		    print '<TD><a href="fiche.php?id='.$result[$i][OSC_id].'">'.$result[$i][OSC_id]."</TD>\n";
    		print "<TD>".$result[$i][model]."</TD>\n";
    		print "<TD>".$result[$i][name]."</TD>\n";
    		print "<TD>".$result[$i][manufacturer]."</TD>\n";
    		print '<TD align="center">'.$result[$i][quantity]."</TD>\n";
    		print '<TD align="center">'.$result[$i][status]."</TD>\n";
    		print "</TR>\n";
    		$i++;
  		}
		print "</table></p>";
	}
	else {
  		dolibarr_print_error("Aucun article trouvé");
	}
}
else {
	dolibarr_print_error("Erreur service web ".$err); 
}

print "</TABLE>";


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
