<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Jean-Louis Bergamo <jlb@j1b.org>
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
require("../../main.inc.php");
require("./account.class.php");

function llxHeader($head = "")
{
  global $db, $user, $conf, $account;

  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add("index.php","Comptes");
  $menu->add_submenu("search.php","Recherche");


  $sql = "SELECT rowid, label FROM llx_bank_account where clos = 0 AND courant = 1";
  $result = $db->query($sql);
  if ($result)
    {

      $numr = $db->num_rows();
      $i = 0; 

      while ($i < $numr) 
	{
	  $objp = $db->fetch_object($i);
	  $menu->add("account.php?account=" . $objp->rowid,  $objp->label);
	  $menu->add_submenu("releve.php?account=" . $objp->rowid ,"Relevés");
	  $i++;
	}
    }
  $db->close;

  $menu->add("index.php","Banque");

  $menu->add_submenu("budget.php","Budgets");
  $menu->add_submenu("bilan.php","Bilan");
  $menu->add_submenu("virement.php","Virement");

  $menu->add_submenu("config.php","Config");

  if (defined("COMPTA_ONLINE_PAYMENT_BPLC") && COMPTA_ONLINE_PAYMENT_BPLC)
    {
      $menu->add(DOL_URL_ROOT."/compta/bank/bplc.php","Transactions BPLC");
    }

  left_menu($menu->liste);

}


?>
