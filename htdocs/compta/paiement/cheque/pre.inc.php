<?php
/* Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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
require("../../../main.inc.php");

$langs->load("bills");
$langs->load("compta");

require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/remisecheque.class.php');

function llxHeader($head = "", $title="") 
{
  global $user, $langs;
  $langs->load("bills");
  
  top_menu($head, $title);

  $menu = new Menu();

  $menu->add("/compta/paiement/liste.php",$langs->trans("Payments"));

  $menu->add(DOL_URL_ROOT."/compta/paiement/cheque/index.php",$langs->trans("CheckReceipt"));
  $menu->add_submenu(DOL_URL_ROOT."/compta/paiement/cheque/fiche.php?action=new",$langs->trans("NewCheckDeposit"));
  $menu->add_submenu(DOL_URL_ROOT."/compta/paiement/cheque/liste.php",$langs->trans("List"));

  $menu->add(DOL_URL_ROOT."/compta/paiement/rapport.php",$langs->trans("Reportings"));

  $menu->add(DOL_URL_ROOT."/compta/facture.php",$langs->trans("Bills"));
  $menu->add_submenu(DOL_URL_ROOT."/compta/facture/impayees.php",$langs->trans("Unpayed"));

  left_menu($menu->liste);
}

?>
