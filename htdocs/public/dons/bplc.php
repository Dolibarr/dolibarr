<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*
 * Gestion du retour du système de Cyberpaiement
 * Cette page est appellée par le serveur de la BPLC lors de l'utilisation
 * su système RSTS
 *
 */

require("../../conf/conf.class.php");
require("../../retourbplc.class.php");
require("../../don.class.php");

$conf = new Conf();
$conf->db->type = $dolibarr_main_db_type;
$conf->db->host = $dolibarr_main_db_host;
$conf->db->name = $dolibarr_main_db_name;
$conf->db->user = $dolibarr_main_db_user;
$conf->db->pass = $dolibarr_main_db_pass;

// Si type non défini (pour compatibilité avec ancienne install), on
// travail avec mysql
if (! $conf->db->type) { $conf->db->type = 'mysql'; }

require_once(DOL_DOCUMENT_ROOT ."/lib/".$dolibarr_main_db_type.".lib.php");

$db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name);

$retbplc = new Retourbplc($db);

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

/*
 * Insertion de la transaction dans la base
 */

$return =  $retbplc->insertdb();


$don_id = substr($retbplc->ref_commande, 0, strlen($retbplc->ref_commande) -2);
print $don_id;

if($return)
{
  if ($retbplc->check_key($retbplc->cle_acceptation))
    {


      /*
       * Validation de la commande
       *
       */
      
      $don = new Don($db);

      $don_id = strstr($retbplc->ref_commande, 0, strlen($retbplc->ref_commande) -2);

      // 5 correspond au paiement en ligne voir table llx_c_paiement

      $don->set_paye($don_id, 5); 

    }

}
