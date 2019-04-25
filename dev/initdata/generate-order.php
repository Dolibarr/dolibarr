#!/usr/bin/env php
<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * ATTENTION DE PAS EXECUTER CE SCRIPT SUR UNE INSTALLATION DE PRODUCTION
 */

/**
 *      \file       dev/initdata/generate-order.php
 *      \brief      Script example to inject random orders (for load tests)
 */

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer mailing-send.php en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}

// Recupere root dolibarr
//$path=preg_replace('/generate-commande.php/i','',$_SERVER["PHP_SELF"]);
require __DIR__. '/../../htdocs/master.inc.php';
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT."/commande/class/commande.class.php";


/*
 * Parametre
 */

define(GEN_NUMBER_COMMANDE, 10);
$year = 2016;
$dates = array (mktime(12, 0, 0, 1, 3, $year),
    mktime(12, 0, 0, 1, 9, $year),
    mktime(12, 0, 0, 2, 13, $year),
    mktime(12, 0, 0, 2, 23, $year),
    mktime(12, 0, 0, 3, 30, $year),
    mktime(12, 0, 0, 4, 3, $year),
    mktime(12, 0, 0, 4, 3, $year),
    mktime(12, 0, 0, 5, 9, $year),
    mktime(12, 0, 0, 5, 1, $year),
    mktime(12, 0, 0, 5, 13, $year),
    mktime(12, 0, 0, 5, 19, $year),
    mktime(12, 0, 0, 5, 23, $year),
    mktime(12, 0, 0, 6, 3, $year),
    mktime(12, 0, 0, 6, 19, $year),
    mktime(12, 0, 0, 6, 24, $year),
    mktime(12, 0, 0, 7, 3, $year),
    mktime(12, 0, 0, 7, 9, $year),
    mktime(12, 0, 0, 7, 23, $year),
    mktime(12, 0, 0, 7, 30, $year),
    mktime(12, 0, 0, 8, 9, $year),
    mktime(12, 0, 0, 9, 23, $year),
    mktime(12, 0, 0, 10, 3, $year),
    mktime(12, 0, 0, 11, 12, $year),
    mktime(12, 0, 0, 11, 13, $year),
    mktime(12, 0, 0, 1, 3, ($year - 1)),
    mktime(12, 0, 0, 1, 9, ($year - 1)),
    mktime(12, 0, 0, 2, 13, ($year - 1)),
    mktime(12, 0, 0, 2, 23, ($year - 1)),
    mktime(12, 0, 0, 3, 30, ($year - 1)),
    mktime(12, 0, 0, 4, 3, ($year - 1)),
    mktime(12, 0, 0, 4, 3, ($year - 1)),
    mktime(12, 0, 0, 5, 9, ($year - 1)),
    mktime(12, 0, 0, 5, 1, ($year - 1)),
    mktime(12, 0, 0, 5, 13, ($year - 1)),
    mktime(12, 0, 0, 5, 19, ($year - 1)),
    mktime(12, 0, 0, 5, 23, ($year - 1)),
    mktime(12, 0, 0, 6, 3, ($year - 1)),
    mktime(12, 0, 0, 6, 19, ($year - 1)),
    mktime(12, 0, 0, 6, 24, ($year - 1)),
    mktime(12, 0, 0, 7, 3, ($year - 1)),
    mktime(12, 0, 0, 7, 9, ($year - 1)),
    mktime(12, 0, 0, 7, 23, ($year - 1)),
    mktime(12, 0, 0, 7, 30, ($year - 1)),
    mktime(12, 0, 0, 8, 9, ($year - 1)),
    mktime(12, 0, 0, 9, 23, ($year - 1)),
    mktime(12, 0, 0, 10, 3, ($year - 1)),
    mktime(12, 0, 0, 11, 12, $year),
    mktime(12, 0, 0, 11, 13, $year),
    mktime(12, 0, 0, 12, 12, $year),
    mktime(12, 0, 0, 12, 13, $year),
);

$ret=$user->fetch('', 'admin');
if ($ret <= 0)
{
    print 'A user with login "admin" and all permissions must be created to use this script.'."\n";
    exit;
}
$user->getrights();

$societesid = array();
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe where client in (1, 3)";
$resql=$db->query($sql);
if ($resql) {
    $num_thirdparties = $db->num_rows($resql);
    $i = 0;
    while ($i < $num_thirdparties) {
        $i++;
        $row = $db->fetch_row($resql);
        $societesid[$i] = $row[0];
    }
}
else { print "err"; }

$commandesid = array();
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commande";
$resql=$db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < $num) {
        $i++;
        $row = $db->fetch_row($resql);
        $commandesid[$i] = $row[0];
    }
}
else { print "err"; }

$prodids = array();
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE tosell=1";
$resql = $db->query($sql);
if ($resql) {
    $num_prods = $db->num_rows($resql);
    $i = 0;
    while ($i < $num_prods) {
        $i++;

        $row = $db->fetch_row($resql);
        $prodids[$i] = $row[0];
    }
}



print "Build ".GEN_NUMBER_COMMANDE." orders\n";
for ($s = 0 ; $s < GEN_NUMBER_COMMANDE ; $s++)
{
    print "Process order ".$s."\n";

    $object = new Commande($db);

    $object->socid          = $societesid[mt_rand(1, $num_thirdparties)];
    $object->date_commande  = $dates[mt_rand(1, count($dates)-1)];
    $object->note           = 'My small comment about this order. Hum. Nothing.';
    $object->source         = 1;
    $object->fk_project     = 0;
    $object->remise_percent = 0;
    $object->shipping_method_id = mt_rand(1, 2);
    $object->cond_reglement_id = mt_rand(0, 2);
    $object->more_reglement_id = mt_rand(0, 7);
    $object->availability_id = mt_rand(0, 1);

    $listofuserid=array(12,13,16);

    $fuser = new User($db);
    $fuser->fetch($listofuserid[mt_rand(0, 2)]);
    $fuser->getRights();

    $db->begin();

    $result=$object->create($fuser);
    if ($result >= 0)
    {
        $nbp = mt_rand(2, 5);
        $xnbp = 0;
        while ($xnbp < $nbp)
        {
            $prodid = mt_rand(1, $num_prods);
            $product=new Product($db);
            $result=$product->fetch($prodids[$prodid]);
            $result=$object->addline($product->description, $product->price, mt_rand(1, 5), 0, 0, 0, $prodids[$prodid], 0, 0, 0, $product->price_base_type, $product->price_ttc, '', '', $product->type);
            if ($result <= 0)
            {
                dol_print_error($db, $object->error);
            }
            $xnbp++;
        }

        $result=$object->valid($fuser);
        if ($result > 0)
        {
            $db->commit();
            print " OK with ref ".$object->ref."\n";
        }
        else
        {
            print " KO\n";
            $db->rollback();
            dol_print_error($db, $object->error);
        }
    }
    else
    {
        print " KO\n";
        $db->rollback();
        dol_print_error($db, $object->error);
    }
}
