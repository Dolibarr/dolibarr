<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

$user->getrights('banque');

function llxHeader($head = "")
{
  global $db, $user, $conf, $account;

  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();


  $sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_account where clos = 0 AND courant = 1";
  $result = $db->query($sql);
  if ($result)
    {

      $numr = $db->num_rows();
      $i = 0; 

      while ($i < $numr) 
	{
	  $objp = $db->fetch_object($i);
	  $menu->add(DOL_URL_ROOT."/compta/bank/account.php?account=" . $objp->rowid,  $objp->label);
	  $menu->add_submenu(DOL_URL_ROOT."/compta/bank/releve.php?account=" . $objp->rowid ,"Relevés");
      $menu->add_submenu(DOL_URL_ROOT."/compta/bank/bilanmens.php?account=" . $objp->rowid ,"Bilan mensuel E/S");
	  $i++;
	}
    }
  $db->close;

  $menu->add(DOL_URL_ROOT."/compta/bank/index.php","Banque");

  $menu->add_submenu(DOL_URL_ROOT."/compta/bank/search.php","Recherche écriture");
  $menu->add_submenu(DOL_URL_ROOT."/compta/bank/budget.php","Budgets");
  $menu->add_submenu(DOL_URL_ROOT."/compta/bank/bilan.php","Bilan");

  if ($user->rights->banque->modifier)
    {
      $menu->add_submenu(DOL_URL_ROOT."/compta/bank/virement.php","Virement");
    }

  if ($user->rights->banque->configurer)
    {
      $menu->add_submenu(DOL_URL_ROOT."/compta/bank/config.php","Configuration");
    }

  if (defined("COMPTA_ONLINE_PAYMENT_BPLC") && COMPTA_ONLINE_PAYMENT_BPLC)
    {
      $menu->add(DOL_URL_ROOT."/compta/bank/bplc.php","Transactions BPLC");
    }

  left_menu($menu->liste);

}


?>
