<?php
/*
 * Copyright (C) 2017		Gustavo Novaro
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
?>
<form action="'.$_SERVER["PHP_SELF"].'" method="POST">
    <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken'];?>">
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="type" value="<?php echo $type;?>">
    <?php
    if (! empty($modCodeProduct->code_auto)):
    ?>
    <input type="hidden" name="code_auto" value="1">
    <?php endif;?>

    <?php
    if (! empty($modBarCodeProduct->code_auto)):
    ?>
    <input type="hidden" name="barcode_auto" value="1">
    <?php endif;?>

    <?php
    if ($type==1)
        $title = $langs->trans("NewService");
    else
        $title = $langs->trans("NewProduct");

    $linkback="";
    print load_fiche_titre($title,$linkback,'title_products.png');

    dol_fiche_head('');
    ?>
    <table class="border centpercent">
    <tr>
        <?php
        $tmpcode='';
        if (! empty($modCodeProduct->code_auto))
            $tmpcode=$modCodeProduct->getNextValue($object,$type);
        ?>
        <td class="titlefieldcreate fieldrequired">
            <?php echo $langs->trans("Ref");?>
        </td>
        <td colspan="3">
            <input name="ref" class="maxwidth200" maxlength="128" value="<?php echo dol_escape_htmltag(GETPOST('ref') ? GETPOST('ref') :$tmpcode);?>">
            <?php
            if ($refalreadyexists)
            {
                print $langs->trans("RefAlreadyExists");
            }
            ?>
        </td>
    </tr>
    <?php
    // Label
    ?>
    <tr>
        <td class="fieldrequired"><?php echo $langs->trans("Label");?></td>
        <td colspan="3">
            <input name="label" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="<?php dol_escape_htmltag(GETPOST('label'));?>">
        </td>
    </tr>
    <tr>
        <td class="fieldrequired">
        <?php
        // On sell
            print $langs->trans("Status").' ('.$langs->trans("Sell").')';
        ?>
        </td>
        <td colspan="3">
        <?php
            $statutarray = array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
            print $form->selectarray('statut',$statutarray,GETPOST('statut'));
        ?>
        </td>
    </tr>
    <tr>
        <td class="fieldrequired">
        <?php
        // To buy
            print $langs->trans("Status").' ('.$langs->trans("Buy").')';
        ?>
        </td>
        <td colspan="3">
        <?php
            $statutarray=array('1' => $langs->trans("ProductStatusOnBuy"), '0' => $langs->trans("ProductStatusNotOnBuy"));
            print $form->selectarray('statut_buy',$statutarray,GETPOST('statut_buy'));
        ?>
        </td>
    </tr>

    <?php
    // Batch number management
    if (! empty($conf->productbatch->enabled)):
    ?>
    <tr>
        <td><?php print $langs->trans("ManageLotSerial");?>
        </td>
        <td colspan="3">
        <?php
            $statutarray=array('0' => $langs->trans("ProductStatusNotOnBatch"), '1' => $langs->trans("ProductStatusOnBatch"));
            print $form->selectarray('status_batch',$statutarray,GETPOST('status_batch'));
        ?>
        </td>
    </tr>
    <?php
    endif;
    ?>

    <?php
    //Barcode
    $showbarcode = empty($conf->barcode->enabled) ? 0:1;

    if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance))
        $showbarcode=0;

    if ($showbarcode):
    ?>
    <tr>
        <td><?php echo $langs->trans('BarcodeType');?></td>
        <td>
        <?php
        if (isset($_POST['fk_barcode_type']))
        {
            $fk_barcode_type=GETPOST('fk_barcode_type');
        }
        else
        {
            if (empty($fk_barcode_type) && ! empty($conf->global->PRODUIT_DEFAULT_BARCODE_TYPE)) $fk_barcode_type = $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE;
        }
        require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';
        $formbarcode = new FormBarCode($db);
        print $formbarcode->select_barcode_type($fk_barcode_type, 'fk_barcode_type', 1);
        ?>
        </td>
        <td>
            <?php echo $langs->trans("BarcodeValue");?></td>
        <td>
        <?php
        $tmpcode = isset($_POST['barcode']) ? GETPOST('barcode') : $object->barcode;

        if (empty($tmpcode) && ! empty($modBarCodeProduct->code_auto))
            $tmpcode=$modBarCodeProduct->getNextValue($object,$type);
        ?>
        <input class="maxwidth100" type="text" name="barcode" value="<?php echo dol_escape_htmltag($tmpcode);?>">
        </td>
    </tr>
    <?php
    endif;
    ?>

    <tr>
        <td class="tdtop">
        <?php
            // Description (used in invoice, propal...)
            print $langs->trans("Description");
        ?>
        </td>
        <td colspan="3">
        <?php
            $doleditor = new DolEditor('desc', GETPOST('desc'), '', 160, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_4, '90%');
            $doleditor->Create();
        ?>
        </td>
    </tr>
    <tr>
        <td>
        <?php
        // Public URL
        print $langs->trans("PublicUrl");
        ?>
        </td>
        <td colspan="3">
            <input type="text" name="url" class="quatrevingtpercent" value="<?php echo GETPOST('url');?>">
        </td>
    </tr>

    <?php
    // Stock min level
    if ($type != 1 && ! empty($conf->stock->enabled))
    {
    ?>
        <tr>
            <td><?php echo $langs->trans('StockLimit');?></td>
            <td>
                <input name="seuil_stock_alerte" class="maxwidth50" value="<?php echo GETPOST('seuil_stock_alerte');?>">
            </td>
            <?php
            // Stock desired level
            ?>
            <td><?php echo $langs->trans('DesiredStock');?></td>
            <td>
                <input name="desiredstock" class="maxwidth50" value="<?php echo GETPOST('desiredstock');?>">
            </td>
        </tr>
    <?php
    }
    else
    {
    ?>
            <input name="seuil_stock_alerte" type="hidden" value="0">
            <input name="desiredstock" type="hidden" value="0">
    <?php
    }
    ?>

    <?php
    // Nature
    if ($type != 1):
    ?>
    <tr>
        <td><?php echo $langs->trans("Nature");?></td>
        <td colspan="3">
        <?php
            $statutarray = array('1' => $langs->trans("Finished"), '0' => $langs->trans("RowMaterial"));
            print $form->selectarray('finished',$statutarray,GETPOST('finished'),1);
        ?>
        </td>
    </tr>
    <?php
    endif;
    ?>

    <?php
    // Duration
    if ($type == 1):
    ?>
    <tr>
        <td><?php echo $langs->trans("Duration");?></td>
        <td colspan="3">
            <input name="duration_value" size="6" maxlength="5" value="<?php echo $duration_value;?>">&nbsp;
            <input name="duration_unit" type="radio" value="h"><?php echo $langs->trans("Hour");?>&nbsp;
            <input name="duration_unit" type="radio" value="d"><?php echo $langs->trans("Day");?>&nbsp;
            <input name="duration_unit" type="radio" value="w"><?php echo $langs->trans("Week");?>&nbsp;
            <input name="duration_unit" type="radio" value="m"><?php echo $langs->trans("Month");?>&nbsp;
            <input name="duration_unit" type="radio" value="y"><?php echo $langs->trans("Year");?>&nbsp;
        </td>
    </tr>
    <?php
    endif;
    ?>

    <?php
    // Weight and volume apply only to products and not to services
    if ($type != 1):
    ?>
        <tr>
            <td>
                <?php
                // Weight
                print $langs->trans("Weight");
                ?>
            </td>
            <td colspan="3">
                <input name="weight" size="4" value="<?php echo GETPOST('weight');?>">
                <?php
                print $formproduct->select_measuring_units("weight_units","weight");
                ?>
            </td>
        </tr>

        <?php
        // Length
        if (empty($conf->global->PRODUCT_DISABLE_SIZE)):
        ?>
        <tr>
            <td><?php echo $langs->trans("Length").' x '.$langs->trans("Width").' x '.$langs->trans("Height");?></td>
            <td colspan="3">
                <input name="size" size="4" value="<?php echo GETPOST('size');?>"> x
                <input name="sizewidth" size="4" value="<?php echo GETPOST('sizewidth');?>"> x
                <input name="sizeheight" size="4" value="<?php echo GETPOST('sizeheight');?>">
                <?php echo $formproduct->select_measuring_units("size_units","size");?>
            </td>
        </tr>
        <?php
        endif;
        ?>

        <?php
        if (empty($conf->global->PRODUCT_DISABLE_SURFACE))
        {
            // Surface
        ?>
            <tr>
                <td><?php echo $langs->trans("Surface");?></td>
                <td colspan="3">
                    <input name="surface" size="4" value="<?php echo GETPOST('surface');?>">
                    <?php echo $formproduct->select_measuring_units("surface_units","surface");?>
                </td>
            </tr>
        <?php
        }
        ?>
        <tr>
        <?php
        // Volume
        ?>
            <td><?php echo $langs->trans("Volume");?></td>
            <td colspan="3">
                <input name="volume" size="4" value="<?php  echo GETPOST('volume');?>">
                <?php echo $formproduct->select_measuring_units("volume_units","volume");?>
            </td>
        </tr>
    <?php
    endif;
    ?>

    <?php
    // Units
    if($conf->global->PRODUCT_USE_UNITS):
    ?>
        <tr>
            <td><?php echo $langs->trans('DefaultUnitToShow');?></td>
            <td colspan="3"><?php print $form->selectUnits('','units');?></td>
        </tr>
    <?php
    endif;
    ?>

    <?php
    // Custom code
    if (empty($conf->global->PRODUCT_DISABLE_CUSTOM_INFO) && empty($type))
    {
    ?>
        <tr>
            <td><?php echo $langs->trans("CustomCode");?></td>
            <td>
                <input name="customcode" class="maxwidth100onsmartphone" value="<?php echo GETPOST('customcode');?>"></td>
            <?php
            // Origin country
            ?>
            <td><?php echo $langs->trans("CountryOrigin");?></td>
            <td>
            <?php print $form->select_country(GETPOST('country_id','int'),'country_id');?>
            <?php
            if ($user->admin)
                print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
            ?>
            </td></tr>
    <?php
    }
    ?>

    <?php
    // Other attributes
    $parameters=array('colspan' => 3);
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
    if (empty($reshook) && ! empty($extrafields->attribute_label))
    {
        print $object->showOptionals($extrafields,'edit',$parameters);
    }

// Note (private, no output on invoices, propales...)
//if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB))       available in create mode
//{
    ?>
    <tr>
        <td class="tdtop"><?php echo $langs->trans("NoteNotVisibleOnBill");?></td>
        <td colspan="3">
        <?php
            // We use dolibarr_details as type of DolEditor here, because we must not accept images as description is included into PDF and not accepted by TCPDF.
            $doleditor = new DolEditor('note_private', GETPOST('note_private'), '', 140, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_8, '90%');
            $doleditor->Create();
        ?>
        </td>
    </tr>
    <?php
//}

    // Categories
    if($conf->categorie->enabled):
    ?>
    <tr>
        <td><?php echo $langs->trans("Categories");?></td>
        <td colspan="3">
        <?php
        $cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', 'parent', 64, 0, 1);
        print $form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, '', 0, '100%');
        ?>
        </td>
    </tr>
    <?php
    endif;
    ?>
</table>

<br>

<?php
if (! empty($conf->global->PRODUIT_MULTIPRICES))
{
    // We do no show price array on create when multiprices enabled.
    // We must set them on prices tab.
}
else
{
?>
    <table class="border" width="100%">
    <?php
    // Price
    ?>
    <tr>
        <td class="titlefieldcreate"><?php echo $langs->trans("SellingPrice");?></td>
        <td>
            <input name="price" class="maxwidth50" value="<?php echo $object->price;?>">
            <?php print $form->selectPriceBaseType($object->price_base_type, "price_base_type");?>
        </td>
    </tr>
    <?php
    // Min price
    ?>
    <tr>
        <td><?php echo $langs->trans("MinPrice");?></td>
        <td>
            <input name="price_min" class="maxwidth50" value="<?php echo $object->price_min;?>">
        </td>
    </tr>
    <?php
    // VAT
    ?>
    <tr>
        <td><?php echo $langs->trans("VATRate");?></td>
        <td><?php print $form->load_tva("tva_tx",-1,$mysoc,'');?>
    </td>
    </tr>

    </table>

    <br>
<?php
}
?>

<?php
// Accountancy codes
if (! empty($conf->accounting->enabled)):
?>
<table class="border" width="100%">
    <tr>
        <td class="titlefieldcreate">
        <?php
        // Accountancy_code_sell
        print $langs->trans("ProductAccountancySellCode");
        ?>
        </td>
        <td>
        <?php print $formaccountancy->select_account(GETPOST('accountancy_code_sell'), 'accountancy_code_sell', 1, null, 1, 1, '');?>
        </td>
    </tr>
    <tr>
        <td>
        <?php
        // Accountancy_code_buy
        print $langs->trans("ProductAccountancyBuyCode");
        ?>
        </td>
        <td>
        <?php
        print $formaccountancy->select_account(GETPOST('accountancy_code_buy'), 'accountancy_code_buy', 1, null, 1, 1, '');
        ?>
        </td>
    </tr>
<?php
else: // For external software
?>
    <tr>
    <?php
    // Accountancy_code_sell
    ?>
    <td class="titlefieldcreate"><?php print $langs->trans("ProductAccountancySellCode");?></td>
    <td class="maxwidthonsmartphone">
        <input class="minwidth100" name="accountancy_code_sell" value="<?php echo $object->accountancy_code_sell;?>">
    </td>
    </tr>
    <tr>
    <?php
    // Accountancy_code_buy
    ?>
    <td><?php print $langs->trans("ProductAccountancyBuyCode");?></td>
    <td class="maxwidthonsmartphone">
        <input class="minwidth100" name="accountancy_code_buy" value="<?php echo $object->accountancy_code_buy;?>">
    </td>
    </tr>
</table>
<?php
endif;
?>

<?php
dol_fiche_end();
?>
<div class="center">
    <input type="submit" class="button" value="<?php echo $langs->trans('Create');?>">
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <input type="button" class="button" value="<?php echo $langs->trans('Cancel');?>" onclick="javascript:history.go(-1)">
</div>

</form>
