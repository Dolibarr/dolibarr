<?php
/*
 * Copyright (C) 2019 ATM Consulting <support@atm-consulting.fr>
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
 * This script is meant to be run when upgrading from a dolibarr version < 3.8
 * to a newer version.
 *
 * Version 3.8 introduces a new column in llx_commande_fournisseur_dispatch, which
 * matches the dispatch to a specific supplier order line (so that if there are
 * several with the same product, the user can specifically tell which products of
 * which line were dispatched where).
 *
 * However when migrating, the new column has a default value of 0, which means that
 * old supplier orders whose lines were dispatched using the old dolibarr version
 * have unspecific dispatch lines, which are not taken into account by the new version,
 * thus making the order look like it was never dispatched at all.
 *
 * This scripts sets this foreign key to the first matching supplier order line whose
 * product (and supplier order of course) are the same as the dispatch’s.
 *
 * If the dispatched quantity is more than indicated on the order line (this happens if
 * there are several order lines for the same product), it creates new dispatch lines
 * pointing to the other order lines accordingly, until all the dispatched quantity is
 * accounted for.
 */

// Dolibarr environment
$path_dir = '../../';
$main_inc_file = 'main.inc.php';
while ((false == (@include $path_dir . $main_inc_file)) && 3*10 > strlen($path_dir)) {
    $path_dir = '../' . $path_dir;
    if (strlen($path_dir) > 20) {
        echo 'Error: unable to include "' . $main_inc_file . '" from any of the parent directories.';
        exit;
    }
}

// Access control
if (!$user->admin) {
    accessforbidden();
}

echo '<h3>Repair llx_commande_fournisseur_dispatch.fk_commandefourndet</h3>';
echo '<p>Repair in progress. This may take a while.</p>';

echo '<pre>';

$sql_dispatch = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'commande_fournisseur_dispatch WHERE COALESCE(fk_commandefourndet, 0) = 0';
$db->begin();
$resql_dispatch = $db->query($sql_dispatch);
$n_processed_rows = 0;
$errors = array();
if ($resql_dispatch) {
    if ($db->num_rows($resql_dispatch) == 0) {
        echo 'Nothing to do.'; exit;
    };
    while ($obj_dispatch = $db->fetch_object($resql_dispatch)) {
        $sql_line = 'SELECT line.rowid, line.qty FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet AS line'
            .  ' WHERE line.fk_commande = ' . $obj_dispatch->fk_commande
            .  ' AND line.fk_product = ' . $obj_dispatch->fk_product;
        $resql_line = $db->query($sql_line);

        // s’il y a plusieurs lignes avec le même produit sur cette commande fournisseur,
        // on divise la ligne de dispatch en autant de lignes qu’on en a sur la commande pour le produit
        // et on met la quantité de la ligne dans la limite du "budget" indiqué par dispatch.qty

        $remaining_qty = $obj_dispatch->qty;
        $first_iteration = true;
        if (!$resql_line) {
            echo 'Unable to find a matching supplier order line for dispatch #' . $obj_dispatch->rowid . "\n";
            $errors[] = $sql_line;
            $n_processed_rows++;
            continue;
        }
        if ($db->num_rows($resql_line) == 0) continue;
        while ($obj_line = $db->fetch_object($resql_line)) {
            if (!$remaining_qty) break;
            if (!$obj_line->rowid) {
                continue;
            }
            $qty_for_line = min($remaining_qty, $obj_line->qty);
            if ($first_iteration) {
                $sql_attach = 'UPDATE ' . MAIN_DB_PREFIX . 'commande_fournisseur_dispatch'
                    . ' SET fk_commandefourndet = ' . $obj_line->rowid . ', qty = ' . $qty_for_line
                    . ' WHERE rowid = ' . $obj_dispatch->rowid;
                $first_iteration = false;
            } else {
                $sql_attach_values = array(
                    $obj_dispatch->fk_commande,
                    $obj_dispatch->fk_product,
                    $obj_line->rowid,
                    $qty_for_line,
                    $obj_dispatch->fk_entrepot,
                    $obj_dispatch->fk_user,
                    $obj_dispatch->datec ? '"' . $db->escape($obj_dispatch->datec) . '"' : 'NULL',
                    $obj_dispatch->comment ? '"' . $db->escape($obj_dispatch->comment) . '"' : 'NULL',
                    $obj_dispatch->status ?: 'NULL',
                    $obj_dispatch->tms ? '"' . $db->escape($obj_dispatch->tms) . '"': 'NULL',
                    $obj_dispatch->batch ?: 'NULL',
                    $obj_dispatch->eatby ? '"' . $db->escape($obj_dispatch->eatby) . '"': 'NULL',
                    $obj_dispatch->sellby ? '"' . $db->escape($obj_dispatch->sellby) . '"': 'NULL'
                );
                $sql_attach_values = join(', ', $sql_attach_values);

                $sql_attach = 'INSERT INTO ' . MAIN_DB_PREFIX . 'commande_fournisseur_dispatch'
                    . ' (fk_commande, fk_product, fk_commandefourndet, qty, fk_entrepot, fk_user, datec, comment, status, tms, batch, eatby, sellby)'
                    . ' VALUES (' . $sql_attach_values . ')';
            }
            $resql_attach = $db->query($sql_attach);
            if ($resql_attach) {
                $remaining_qty -= $qty_for_line;
            } else {
                $errors[] = $sql_attach;
            }
            $first_iteration = false;
        }
        $n_processed_rows++;

        // report progress every 256th row
        if (!($n_processed_rows & 0xff)) {
            echo 'Processed ' . $n_processed_rows . ' rows with ' . count($errors) . ' errors…' . "\n";
            flush();
            ob_flush();
        }
    }
} else {
    echo 'Unable to find any dispatch without an fk_commandefourndet.' . "\n";
    echo $sql_dispatch . "\n";
}
echo 'Fixed ' . $n_processed_rows . ' rows with ' . count($errors) . ' errors…' . "\n";
echo 'DONE.' . "\n";
echo '</pre>';

if (count($errors)) {
    $db->rollback();
    echo 'The transaction was rolled back due to errors: nothing was changed by the script.';
} else {
    $db->commit();
}
$db->close();


echo '<h3>SQL queries with errors:</h3>';
echo '<ul><li>' . join('</li><li>', $errors) . '</li></ul>';

