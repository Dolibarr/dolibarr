<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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
 */

require("../../main.inc.php3");
require("./account.class.php");

function llxHeader($head = "")
{
  global $user, $conf, $account;

  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add("index.php3","Comptes");
  $menu->add_submenu("search.php3","Recherche");

  $db = new Db();
  $sql = "SELECT rowid, label FROM llx_bank_account";
  $result = $db->query($sql);
  if ($result)
    {

      $num = $db->num_rows();
      $i = 0; 

      while ($i < $num) 
	{
	  $objp = $db->fetch_object($i);
	  $menu->add("account.php3?account=" . $objp->rowid,  $objp->label);
	  $menu->add_submenu("releve.php3?account=" . $objp->rowid ,"Relevés");
	  $menu->add_submenu("rappro.php3?account=".$objp->rowid,"Rappro");
	  $i++;
	}
    }
  $db->close;

  $menu->add("index.php3","Bank");


  $menu->add_submenu("budget.php3","Budgets");
  $menu->add_submenu("bilan.php3","Bilan");
  $menu->add_submenu("virement.php3","Virement");


  $menu->add_submenu("config.php3","Config");

  $menu->add("/compta/facture.php3","Factures");

  $menu->add("/compta/ca.php3","Chiffres d'affaires");

  left_menu($menu->liste);

}


?>
