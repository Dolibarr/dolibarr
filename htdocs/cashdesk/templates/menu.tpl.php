<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier      <jeremie.o@laposte.net>
 * Copyright (C) 2008      Laurent Destailleur   <eldy@uers.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin         <regis@dolibarr.fr>
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

include_once(DOL_DOCUMENT_ROOT.'/societe.class.php');
include_once(DOL_DOCUMENT_ROOT.'/compta/bank/account.class.php');
include_once(DOL_DOCUMENT_ROOT.'/product/stock/entrepot.class.php');

$company=new Societe($db);
$company->fetch($conf->global->CASHDESK_ID_THIRDPARTY);
$bank=new Account($db);
$bank->fetch($conf->global->CASHDESK_ID_BANKACCOUNT);
$warehouse=new Entrepot($db);
$warehouse->fetch($conf->global->CASHDESK_ID_WAREHOUSE);

$langs->load("@cashdesk");

$logout='<img class="login" border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png">';

print '<div class="menu_bloc">';
print '<ul class="menu">';
print '<li class="menu_choix1"><a href="affIndex.php?menu=facturation&id=NOUV"><span>Nouvelle vente</span></a></li>';

print '<li class="menu_choix2"><a href="'.eregi_replace('cashdesk','',$conf_url_racine).'"><span>Gestion commerciale</span></a></li>';

print '<li class="menu_choix0">'.$langs->trans("User").' : '.$_SESSION['prenom'].' '.$_SESSION['nom'].' <a href="deconnexion.php">'.$logout.'</a><br>';
print $langs->trans("CashDeskThirdParty").' : '.$company->getNomUrl(1).'<br>';
print $langs->trans("CashDeskBank").' : '.$bank->getNomUrl(1).'<br>';
print $langs->trans("CashDeskWarehouse").' : '.$warehouse->getNomUrl(1);
print '</li></ul>';
print '</div>';
?>