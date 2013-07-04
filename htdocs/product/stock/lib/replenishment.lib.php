<?php
/*
 * Copyright (C) 2013   Cédric Salvador    <csalvador@gpcsolutions.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/product/stock/replenishment.lib.php
 *  \ingroup    produit
 *  \brief      Contains functions used in replenish.php and replenishorders.php
 */

function dispatched($order_id)
{
    global $db;
    $sql = 'SELECT fk_product, SUM(qty) from llx_commande_fournisseur_dispatch';
    $sql .= ' WHERE fk_commande = ' . $order_id . ' GROUP BY fk_product';
    $sql .= ' ORDER by fk_product';
    $resql = $db->query($sql);
    $dispatched = array();
    $ordered = array();
    if($resql && $db->num_rows($resql)) {
        while($res = $db->fetch_object($resql))
            $dispatched[] = $res;
    }
    $sql = 'SELECT fk_product, SUM(qty) from llx_commande_fournisseurdet';
    $sql .= ' WHERE fk_commande = ' . $order_id . ' GROUP BY fk_product';
    $sql .= ' ORDER by fk_product';
    $resql = $db->query($sql);
    if($resql && $db->num_rows($resql)) {
        while($res = $db->fetch_object($resql))
            $ordered[] = $res;
    }
    return $dispatched == $ordered;
}

function dispatchedOrders()
{
    global $db;
    $sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'commande_fournisseur';
    $resql = $db->query($sql);
    $res = array();
    if ($resql && $db->num_rows($resql) > 0) {
        while ($obj = $db->fetch_object($resql)) {
            if (dispatched($obj->rowid)) {
                $res[] = $obj->rowid;
            }
        }
    }
    if ($res) {
        $res = '(' . implode(',', $res) . ')';
    } else {
        //hack to make sure ordered SQL request won't syntax error
        $res = '(0)';
    }
    return $res;
}

function ordered($product_id)
{
    global $db, $langs, $conf;
    $sql = 'SELECT DISTINCT cfd.fk_product, SUM(cfd.qty) FROM';
    $sql .= ' ' . MAIN_DB_PREFIX . 'commande_fournisseurdet as cfd ';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur as cf';
    $sql .= ' ON cfd.fk_commande = cf.rowid WHERE';
    if ($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER) {
        $sql .= ' cf.fk_statut < 3';
    } else if ($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER) {
        $sql .= ' cf.fk_statut < 6 AND cf.rowid NOT IN ' . dispatchedOrders();
    } else {
        $sql .= ' cf.fk_statut < 5';
    } 
    $sql .= ' AND cfd.fk_product = ' . $product_id;
    $sql .= ' GROUP BY cfd.fk_product';

    $resql = $db->query($sql);
    if ($resql) {
        $exists = $db->num_rows($resql);
        if ($exists) {
            $obj = $db->fetch_array($resql);
            return $obj['SUM(cfd.qty)']; //. ' ' . img_picto('','tick');
        } else {
            return null;//img_picto('', 'stcomm-1');
        }
    } else {
        $error = $db->lasterror();
        dol_print_error($db);
        dol_syslog('replenish.php: ' . $error, LOG_ERROR);

        return $langs->trans('error');
    }
}

?>
