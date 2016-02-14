<?php
/* Copyright (C) 2013-2014	Jean-François Ferry	<jfefe@aternatik.fr>
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
 */

/**
 *   	\file       product/dynamic_price/list_schedule.php
 *		\ingroup    product
 *		\brief      Page to list price schedules
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/dynamic_price/class/price_schedule.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

// Load traductions files required by page
$langs->load("companies");
$langs->load("other");

// Get parameters
$id                 = GETPOST('id','int');
$type               = GETPOST('type','int');
$product_supplier   = GETPOST('product_supplier','int');
$sortorder          = GETPOST('sortorder','alpha');
$sortfield          = GETPOST('sortfield','alpha');
$page               = GETPOST('page','int');

// Protection if external user
if ($user->societe_id > 0)
{
    accessforbidden();
}

if( ! $user->rights->service->lire || ! $user->rights->dynamicprices->schedule_read )
{
    accessforbidden();
}

$pagetitle = $langs->trans($type==PriceSchedule::TYPE_SUPPLIER_SERVICE?'SupplierPriceSchedule':'PriceSchedule');
llxHeader('',$pagetitle,'');

$form = new Form($db);
$product = new Product($db);

$params = '&type='.$type;
if (!empty($product_supplier)) $params.= "&product_supplier=".$product_supplier;

if ( $product->fetch($id) > 0 )
{

    /*
     * View object
     */
    $head=product_prepare_head($product);
    $tab=$type==PriceSchedule::TYPE_SUPPLIER_SERVICE?'supplierpriceschedule':'priceschedule';
    $titre=$langs->trans("CardProduct".$product->type);
    $picto=$product->type == Product::TYPE_SERVICE?'service':'product';
    dol_fiche_head($head, $tab, $titre, 0, $picto);
    dol_banner_tab($product, '', '', 0);
    dol_fiche_end();

    //Add the list to show any supplier price with only this product id
    if ($type == PriceSchedule::TYPE_SUPPLIER_SERVICE)
    {
        $ajaxoptions=array(
                'update' => array('product_supplier'=>'key'),	// html id tags that will be edited with which ajax json response key
                'warning' => $langs->trans("NoPriceDefinedForThisSupplier") // translation of an error saved into var 'error'
        );
        //print '<form name="supplier_form" method="post">';
        //print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print $langs->trans("Supplier").': '.$form->select_produits_fournisseurs_list(0, $product_supplier, 'product_supplier', '', ' AND fk_product='.$id,'',-1,0);
        //print '<input type="submit" class="button" name="refresh" value="'.$langs->trans('Refresh').'" />';
        //print '</form>';

        //JS script to run when list is changed, it sets new URL with pid number from list
        print '<script type="text/javascript">
            jQuery(document).ready(run);
            function run() {
                jQuery("#product_supplier").change(on_change);
            }
            function on_change() {
                var value = $("#product_supplier").val();
                console.log(value, $("#product_supplier"));
                if (value !== "")
                {
                    window.location = "'.$_SERVER["PHP_SELF"].'?id='.$id.'&type='.$type.'&product_supplier=" + value;
                }
            }
        </script>';
    }

    //List stuff
    if (empty($sortorder)) $sortorder="DESC";
    if (empty($sortfield)) $sortfield="schedule_year";
    if (empty($arch)) $arch = 0;
    if ($page == -1) {
        $page = 0 ;
    }

    $limit = $conf->liste_limit;
    $offset = $limit * $page ;
    $pageprev = $page - 1;
    $pagenext = $page + 1;

    // Load schedule list
    $object = new PriceSchedule($db);
    $ret = $object->fetchAll($id, $type, $product_supplier, $sortorder, $sortfield, $limit, $offset);
    if ($ret == -1)
    {
        dol_print_error($db,$object->error);
        exit;
    }
    if (!$ret)
    {
        $text = $type==PriceSchedule::TYPE_SUPPLIER_SERVICE?'SupplierPriceScheduleNone':'PriceScheduleNone';
        print '<div class="warning">'.$langs->trans($text).'</div>';
    }
    else
    {
        print '<table class="noborder" width="100%">'."\n";
        print '<tr class="liste_titre">';
        $param = 'id='.$id.$params;
        print_liste_field_titre($langs->trans('Year'),$_SERVER['PHP_SELF'],'schedule_year','',$param,'',$sortfield,$sortorder);
        if ($type == PriceSchedule::TYPE_SUPPLIER_SERVICE)
        {
            print_liste_field_titre($langs->trans("Suppliers"));
            print_liste_field_titre($langs->trans("SupplierRef"));
        }
        print_liste_field_titre($langs->trans('Action'),"","","","",'width="60" align="center"');
        print "</tr>\n";

        $var=true;
        foreach ($object->lines as $schedule)
        {
            $var=!$var;

            //Year
            print '<tr '.$bc[$var].'><td>';
            print '<a href="./schedule.php?id='.$schedule->id.'">'.$schedule->schedule_year.'</a>';
            print '</td>';

            if ($type == PriceSchedule::TYPE_SUPPLIER_SERVICE)
            {
                $prod = new ProductFournisseur($db);
                $prod->fetch_product_fournisseur_price($schedule->fk_product_supplier);

                //Supplier
                print '<td>';
                print $prod->getSocNomUrl(1,'supplier');
                print '</td>';

                //Supplier ref
                print '<td align="left">';
                print $prod->fourn_ref;
                print '</td>';
            }

            //Buttons
            print '<td align="center">';
            print '<a href="./schedule.php?id='.$schedule->id.'&action=edit">';
            print img_edit();
            print '</a>';
            print '&nbsp;';
            print '<a href="./schedule.php?id='.$schedule->id.'&action=delete">';
            print img_delete();
            print '</a>';
            print '</td>';

            print '</tr>';
        }

        print '</table>';
    }
}
else
{
    dol_print_error(0,$product->error);
}


/*
 * Add schedule button
 */
print '<div class="tabsAction">';
if($user->rights->dynamicprices->schedule_write)
{
    print '<div class="inline-block divButAction">';
    print '<a href="schedule.php?product_id='.$id.$params.'&action=create" class="butAction">'.$langs->trans('AddSchedule').'</a>';
    print '</div>';
}
print '</div>';

// End of page
llxFooter();
$db->close();
