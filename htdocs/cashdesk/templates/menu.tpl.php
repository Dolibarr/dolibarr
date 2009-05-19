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

$langs->load("@cashdesk");

$logout='<img class="login" border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png">';

print '<div class="menu_bloc">';
print '<ul class="menu">';
print '<li class="menu_choix1"><a href="affIndex.php?menu=facturation&id=NOUV"><span>Nouvelle vente</span></a></li>';
print '<li class="menu_choix2"><a href="'.eregi_replace('cashdesk','',$conf_url_racine).'"><span>Gestion commerciale</span></a></li>';
print '<li class="menu_choix0"><a href="deconnexion.php"><span title="Cliquez pour quitter la session">Utilisateur : '.utf8_decode($_SESSION['prenom']).' '.$_SESSION['nom'].' '.$logout.'</span></a>';
print '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$conf->global->CASHDESK_ID_THIRDPARTY.'">'.$langs->trans("CashDeskThirdParty").' : '.$conf->global->CASHDESK_ID_THIRDPARTY.'</a>';
print '<a href="'.DOL_URL_ROOT.'/compta/bank/fiche.php?id='.$conf->global->CASHDESK_ID_BANKACCOUNT.'">'.$langs->trans("CashDeskBank").' : '.$conf->global->CASHDESK_ID_BANKACCOUNT.'</a>';
print '<a href="'.DOL_URL_ROOT.'/product/stock/fiche.php?id='.$conf->global->CASHDESK_ID_WAREHOUSE.'">'.$langs->trans("CashDeskWarehouse").' : '.$conf->global->CASHDESK_ID_WAREHOUSE.'</a>';
print '</li></ul>';
print '</div>';
?>