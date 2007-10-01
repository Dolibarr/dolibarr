<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/oscommerce_ws/index.php
        \ingroup    oscommerce2
		\brief      Page accueil zone boutique
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("shop");
$langs->load("orders");


llxHeader("",$langs->trans("OSCOmmerceShop"));

print_fiche_titre($langs->trans("OSCommerceShop"));

print '<table width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="40%" class="notopnoleft">';

// initialisation des webservices
set_magic_quotes_runtime(0);

//WebService Client.
require_once(NUSOAP_PATH."nusoap.php");
require_once("./includes/configure.php");

// Set the parameters to send to the WebService
$parameters = array();

// Set the WebService URL
$client = new soapclient_nusoap(OSCWS_DIR."ws_orders.php");

/* 
/* Chiffre d'affaire 
*/

print_titre($langs->trans('SalesTurnover'));

print '<table class="noborder" cellspacing="0" cellpadding="3" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Year").'</td>';
print '<td>'.$langs->trans("Month").'</td>';
print '<td align="right">'.$langs->trans("Total").'</td></tr>';

// Call the WebService and store its result in $result.
$result = $client->call("get_CAmensuel",$parameters );
if ($client->fault) {
  dolibarr_print_error('',"Erreur de connexion ");
  print_r($client->faultstring);
}
elseif (!($err = $client->getError()) )
{
	$num=0;
  	if ($result) $num = sizeof($result);
	$var=True;
  	$i=0;

	if ($num > 0) {
	   while ($i < $num)
		{
      	$var=!$var;
      	print "<tr $bc[$var]>";
      	print '<td align="left">'.$result[$i][an].'</td>';
		print '<td align="left">'.$result[$i][mois].'</td>';
      	print '<td align="right">'.convert_price($result[$i][value]).'</td>';

      	print "</tr>\n";
      	$i++;
    	}
	}
}
else
{
  dolibarr_print_error('',"Erreur du service web ".$err);
}


print "</table>";
print '</td><td valign="top" width="60%" class="notopnoleftnoright">';

// partie commandes
print_titre($langs->trans("Orders"));

/*
 * 5 dernières commandes reçues
 */

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("LastOrders").'</td></tr>';
	
// Call the WebService and store its result in $result.
$parameters = array("limit"=>OSC_MAXNBCOM);
$result = $client->call("get_orders",$parameters );

if ($client->fault) {
  dolibarr_print_error('',"Erreur de connexion ");
}
elseif (!($err = $client->getError()) ) {
	$num=0;
  	if ($result) $num = sizeof($result);
	$var=True;
  	$i=0;

    if ($num > 0) {
	
 	  $num = min($num,OSC_MAXNBCOM);
      while ($i < $num) {
      	$var=!$var;
      	print "<tr $bc[$var]>";
 	    print '<td>'.$result[$i][orders_id].'</td><td>'.$result[$i][customers_name].'</td><td>'.convert_price($result[$i][value]).'</td><td>'.$result[$i][payment_method].'</td></tr>';
	  	$i++;
	  }
    }
}
else {
  dolibarr_print_error('',"Erreur du service web ".$err);
}

print "</table><br>";

/*
 * 5 dernières commandes en attente
*/

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("OnStandBy").'</td></tr>';

$parameters = array("limit"=>OSC_MAXNBCOM, "status"=>OSC_ORDWAIT);
$result = $client->call("get_orders",$parameters );

if ($client->fault) {
  dolibarr_print_error('',"Erreur webservice ".$client->faultstring);
}
elseif (!($err = $client->getError()) ) {
  $var=True;
  $i=0;
  $num=0;
  if ($result) $num = sizeof($result);
  $langs->load("orders");

  if ($num > 0) {
 	  $num = min($num,OSC_MAXNBCOM);

      while ($i < $num) {
		  $var=!$var;
		  print "<tr $bc[$var]>";
		  print '<td>'.$result[$i][orders_id].'</td><td>'.$result[$i][customers_name].'</td><td>'.convert_price($result[$i][value]).'</td><td>'.$result[$i][payment_method].'</td></tr>';
		  $i++;
		}
  }
}
else {
  dolibarr_print_error('',"Erreur du service web ".$err);
}

print "</table><br>";
/*
 * Commandes à traiter
 */

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("TreatmentInProgress").'</td></tr>';
		
$parameters = array("limit"=>OSC_MAXNBCOM, "status"=>OSC_ORDPROCESS);
$result = $client->call("get_orders",$parameters );

if ($client->fault) {
  dolibarr_print_error('',"Erreur webservice ".$client->faultstring);
}
elseif (!($err = $client->getError()) ) {
  $var=True;
  $i=0;
  $num=0;
  if ($result) $num = sizeof($result);
  $langs->load("orders");

  if ($num > 0)	{
 	  $num = min($num,OSC_MAXNBCOM);

      while ($i < $num)	{
		print "<tr $bc[$var]>";
		print '<td>'.$result[$i][orders_id].'</td><td>'.$result[$i][customers_name].'</td><td>'.convert_price($result[$i][value]).'</td><td>'.$result[$i][payment_method].'</td></tr>';
	  	$i++;
		$var=!$var;
		}
    }
}
else {
  dolibarr_print_error('',"Erreur du service web ".$err);
}

print "</table><br>";
print '</td></tr><tr>';

/*
* Derniers clients qui ont commandé
*/

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="7">'.$langs->trans("LastCustomers").'</td></tr>';

$parameters = array("limit"=>OSC_MAXNBCOM);
$result = $client->call("get_lastOrderClients",$parameters );

if ($client->fault) {
  dolibarr_print_error('',"Erreur webservice ".$client->faultstring);
}
elseif (!($err = $client->getError()) ) {
  $var=True;
  $i=0;
  $num=0;
  if ($result) $num = sizeof($result);
  $langs->load("orders");

  if ($num > 0)	{
 	  $num = min($num,OSC_MAXNBCOM);

      while ($i < $num)	{
		print "<tr $bc[$var]>";
	  	print "<td>".$result[$i][date_purchased]."</td><td>".$result[$i][customers_name]."</td><td>".$result[$i][delivery_country]."</td><td>".convert_price($result[$i][value])."</td><td>".$result[$i][payment_method]."</td><td>".$result[$i][orders_id]."</td><td>".$result[$i][statut]."</td></tr>";
	  	$i++;
		$var=!$var;
		}
	  print "</table><br>";
    }
}
else {
  dolibarr_print_error('',"Erreur du service web ".$err);
}


print '</tr></table>';


llxFooter('$Date$ - $Revision$');
?>
