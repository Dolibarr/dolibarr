<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

require("../../lib/mysql.lib.php3");
require("../../conf/conf.class.php3");
require("../../retourbplc.class.php");
$conf = new Conf();

if ($conf->don->onlinepayment)
{

  $db = new Db();

  $retbplc = new Retourbplc($db, $conf);

  $retbplc->ipclient          = $HTTP_POST_VARS["CHAMP105"];
  $retbplc->num_transaction   = $HTTP_POST_VARS["CHAMP901"];
  $retbplc->date_transaction  = $HTTP_POST_VARS["CHAMP902"];
  $retbplc->heure_transaction = $HTTP_POST_VARS["CHAMP903"];
  $retbplc->num_autorisation  = $HTTP_POST_VARS["CHAMP904"];
  $retbplc->cle_acceptation   = $HTTP_POST_VARS["CHAMP905"];
  $retbplc->code_retour       = $HTTP_POST_VARS["CHAMP906"];

  $retbplc->ref_commande      = $HTTP_POST_VARS["CHAMP200"];

  $retbplc->insertdb();

}
