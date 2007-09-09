<?php
/* Copyright (C) 2006 Jean Heimburger     <jean@tiaris.info>
 * Copyright (C) 2007 Laurent Destailleur <eldy@users.sourceforge.net>
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
 */

require("./pre.inc.php");

$langs->load("companies");
$langs->load("orders");


llxHeader();


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Liste des commandes de la boutique web", $page, "index.php");

set_magic_quotes_runtime(0);

//WebService Client.
require_once(NUSOAP_PATH."nusoap.php");
require_once("../includes/configure.php");

// Set the parameters to send to the WebService
$parameters = array("orderid"=>"0");

// Set the WebService URL
$client = new soapclient_nusoap(OSCWS_DIR."ws_orders.php");

$result = $client->call("get_Order",$parameters );

//		echo '<h2>Result</h2><pre>'; print_r($result); echo '</pre>';

if ($client->fault) {
  		dolibarr_print_error('',"erreur de connexion ");
}
elseif (!($err = $client->getError()) )
{
	$num=0;
  	if ($result) $num = sizeof($result);
	$var=True;
  	$i=0;

// une commande osc
	$OscOrder = new Osc_Order($db);
	
  	if ($num > 0) {
		print "<TABLE width=\"100%\" class=\"noborder\">";
		print '<TR class="liste_titre">';
		print "<td>Id</td>";
		print '<TD align="center">'.$langs->trans("Order").'</TD>';
		print '<td>'.$langs->trans("Customer").'</td>';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("Amount").'</td>';
		print '<td align="center">Paiement</td>';
		print '<td align="center">Statut</td>';
		print '<TD align="center">Importer</TD>';
  		print "</TR>\n";
	   
		while ($i < $num) {
      		$var=!$var;

				$ordid = $OscOrder->get_orderid($result[$i][orders_id]);
		    print "<TR $bc[$var]>";
		    print '<TD><a href="fiche.php?orderid='.$result[$i][orders_id].'">'.$result[$i][orders_id]."</TD>\n";
    		print '<TD><a href="../../commande/fiche.php?id='.$ordid.'">'.$ordid."</a> </TD>\n";
    		print "<TD>".$result[$i][customers_name]."</TD>\n";
    		print "<TD>".$result[$i][date_purchased]."</TD>\n";
			$tot = convert_price($result[$i][total]) + convert_price($result[$i][port]);
    		print "<TD>".$tot."</TD>\n";
    		print '<TD align="center">'.' '.$result[$i][payment_method]."</TD>\n";
    		print '<TD align="center">'.$result[$i][statut]."</TD>\n";
    		if ($ordid) $lib = "modifier";
    		else $lib = "<u>importer</u>";
    		print '<TD align="center"><a href="fiche.php?action=import&orderid='.$result[$i][orders_id].'">'.$lib."</a></TD>\n";
    		print "</TR>\n";
    		$i++;
  		}
		print "</table></p>";
	}
	else {
  		dolibarr_print_error('',"Aucune commande trouvée");
	}
}
else {
	dolibarr_print_error('',"Erreur service web ".$err); 
}

print "</TABLE>";


llxFooter('$Date$ - $Revision$');
?>
