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

$db = new Db();

$retbplc = new Retourbplc($db, $conf);

$retbplc->num_compte        = $conf->bplc->num_compte;

$retbplc->montant           = $CHAMP201;
$retbplc->num_contrat       = $CHAMP002;
$retbplc->ref_commande      = $CHAMP200;
$retbplc->ipclient          = $CHAMP105;
$retbplc->num_transaction   = $CHAMP901;
$retbplc->date_transaction  = $CHAMP902;
$retbplc->heure_transaction = $CHAMP903;
$retbplc->num_autorisation  = $CHAMP904;
$retbplc->cle_acceptation   = $CHAMP905;
$retbplc->code_retour       = $CHAMP906;

$retbplc->ref_commande      = $CHAMP200;

print $retbplc->insertdb();


