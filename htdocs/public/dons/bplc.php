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

/* /bplc.php?CHAMP000=500240&CHAMP001=5965&CHAMP002=5429999010&CHAMP003=I&CHAMP004=Serveur+de+test+FSF+France&CHAMP006=FSF+France+TEST&CHAMP008=rq@lolix.org&CHAMP100=Quiedeville&CHA
MP101=Rodolphe&CHAMP102=FSFFrance&CHAMP103=.&CHAMP104=rq@lolix.org&CHAMP105=80.15.137.93&CHAMP106=.&CHAMP107=.&CHAMP108=.&CHAMP109=.&CHAMP110=.&CHAMP200=15CB6D9703F8F46760846CEC4A3CADC8&CHAMP201=0,15&CHAMP202=EUR&CHAMP900=01&CHAMP901=0170610&CHAMP902=20021223&CHAMP903=170610&CHAMP904=950467&CHAMP905=2203G&CHAMP114=Quiedeville+Rodolphe&CHAMP906=0000&CHAMP907=0 HTTP/1.1" 200 5 "-" "Microsoft URL Control - 6.00.8169"

*/

require("../../lib/mysql.lib.php3");
require("../../conf/conf.class.php3");
require("../../retourbplc.class.php");

$conf = new Conf();

$db = new Db();

$retbplc = new Retourbplc($db, $conf);

$retbplc->ipclient          = $CHAMP105;
$retbplc->num_transaction   = $CHAMP901;
$retbplc->date_transaction  = $CHAMP902;
$retbplc->heure_transaction = $CHAMP903;
$retbplc->num_autorisation  = $CHAMP904;
$retbplc->cle_acceptation   = $CHAMP905;
$retbplc->code_retour       = $CHAMP906;

$retbplc->ref_commande      = $CHAMP200;

print $retbplc->insertdb();


