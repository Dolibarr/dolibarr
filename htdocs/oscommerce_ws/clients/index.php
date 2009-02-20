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
 *
 * $Id$
 */

require("./pre.inc.php");

$langs->load("companies");


llxHeader();


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Liste des clients de la boutique web", $page, "index.php");

set_magic_quotes_runtime(0);

//WebService Client.
require_once(NUSOAP_PATH."nusoap.php");
require_once("../includes/configure.php");

// Set the parameters to send to the WebService
$parameters = array("custid"=>"0");

// Set the WebService URL
$client = new soapclient_nusoap(OSCWS_DIR."ws_customers.php");

$result = $client->call("get_Client",$parameters );

//		echo '<h2>Result</h2><pre>'; print_r($result); echo '</pre>';

if ($client->fault) {
  		dol_print_error('',"erreur de connexion ");
}
elseif (!($err = $client->getError()) )
{
	$num=0;
  	if ($result) $num = sizeof($result);
	$var=True;
  	$i=0;

// un client osc
	$OscClient = new Osc_Customer($db);
	
  	if ($num > 0) {
		print "<TABLE width=\"100%\" class=\"noborder\">";
		print '<TR class="liste_titre">';
		print "<td>OscId</td>";
		print "<td>".$langs->trans("IdCustomer")."</td>";
		print '<TD align="left">'.$langs->trans("ThirdParty").'</TD>';
		print '<td>'.$langs->trans("Name").'</td>';
		print '<td>'.$langs->trans("Town").'</td>';
		print '<td>'.$langs->trans("Country").'</td>';
		print '<td align="center">'.$langs->trans("Phone").'</td>';
		print '<TD align="center">Importer</TD>';
  		print "</TR>\n";
	   
		while ($i < $num) {
      		$var=!$var;
      		$custid = $OscClient->get_clientid($result[$i][customers_id]);

		    print "<TR $bc[$var]>";
		    print '<TD><a href="fiche.php?custid='.$result[$i][customers_id].'">'.$result[$i][customers_id]."</TD>\n";
    		print "<td>".$custid."</td>";
    		print "<TD>".$result[$i][entry_company]."</TD>\n";
    		print "<TD>".$result[$i][entry_lastname]."</TD>\n";
    		print "<TD>".$result[$i][entry_city]."</TD>\n";
    		print '<TD align="left">'.$result[$i][countries_name]."</TD>\n";
    		print '<TD align="center">'.$result[$i][customers_telephone]."</TD>\n";
    		if ($custid) $lib = "modifier";
    		else $lib = "<u>importer</u>";
    		 
    		print '<TD align="center"><a href="fiche.php?action=import&custid='.$result[$i][customers_id].'"'.">".$lib."</a></TD>\n";
    		print "</TR>\n";
    		$i++;
  		}
		print "</table></p>";
	}
	else {
  		dol_print_error('',"Aucun client trouvé");
	}
}
else {
	dol_print_error('',"Erreur service web ".$err); 
}

print "</TABLE>";


llxFooter('$Date$ - $Revision$');
?>
