<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2011      Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2018      Andreu Bisquerra    <jove@bisquerra.com>
 * Copyright (C) 2019      Josep Lluís Amador  <joseplluis@lliuretic.cat>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/takepos/floors.php
 *	\ingroup    takepos
 *	\brief      Page to show a receipt.
 */

require '../main.inc.php'; // Load $user and permissions
include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->loadLangs(array("main", "cashdesk", "companies"));

$place = (GETPOST('place', 'int') > 0 ? GETPOST('place', 'int') : 0); // $place is id of table for Ba or Restaurant

$facid = GETPOST('facid', 'int');


/*
 * View
 */

top_httphead('text/html');

if ($place > 0)
{
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture where ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")'";
    $resql = $db->query($sql);
    $obj = $db->fetch_object($resql);
    if ($obj)
    {
        $facid = $obj->rowid;
    }
}
$object = new Facture($db);
$object->fetch($facid);

// Call to external receipt modules if exist
$hookmanager->initHooks(array('takeposfrontend'), $facid);
$reshook = $hookmanager->executeHooks('TakeposReceipt', $parameters, $object);
if (!empty($hookmanager->resPrint)) {
    print $hookmanager->resPrint;
	exit;
}

// IMPORTANT: This file is sended to 'Takepos Printing' application. Keep basic file. No external files as css, js... If you need images use absolute path.
?>
<html>
<body>
<style>
.right {
    text-align: right;
}
.center {
    text-align: center;
}
.left {
    text-align: left;
}
</style>
<center>
<font size="4">
<?php echo '<b>'.$mysoc->name.'</b>'; ?>
</font>
</center>
<br>
<p class="left">
<?php
if ($conf->global->TAKEPOS_CUSTOM_RECEIPT)
{
	$substitutionarray = getCommonSubstitutionArray($langs);
	if (!empty($conf->global->TAKEPOS_HEADER))
	{
		$newfreetext = make_substitutions($conf->global->TAKEPOS_HEADER, $substitutionarray);
		echo $newfreetext;
	}
}
?>
</p>
<p class="right">
<?php
print $langs->trans('Date')." ".dol_print_date($object->date, 'day').'<br>';
if ($conf->global->TAKEPOS_CUSTOM_RECEIPT) print $conf->global->TAKEPOS_RECEIPT_NAME." ";
if ($object->statut == Facture::STATUS_DRAFT) print str_replace(")", "", str_replace("-", " ".$langs->trans('Place')." ", str_replace("(PROV-POS", $langs->trans("Terminal")." ", $object->ref)));
else print $object->ref;
if ($conf->global->TAKEPOS_CUSTOM_RECEIPT && $conf->global->TAKEPOS_SHOW_CUSTOMER)
{
	$soc = new Societe($db);
	$soc->fetch($invoice->socid);
	if ($invoice->socid != $conf->global->{'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"]})
	{
		$soc = new Societe($db);
		if ($invoice->socid > 0) $soc->fetch($invoice->socid);
		else $soc->fetch($conf->global->{'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"]});
		print "<br>".$langs->trans("Customer").': '.$soc->name;
	}
}
?>
</p>
<br>

<table width="100%" style="border-top-style: double;">
    <thead>
	<tr>
        <th class="center"><?php print $langs->trans("Label"); ?></th>
        <th class="right"><?php print $langs->trans("Qty"); ?></th>
        <th class="right"><?php print $langs->trans("Price"); ?></th>
        <th class="right"><?php print $langs->trans("TotalTTC"); ?></th>
	</tr>
    </thead>
    <tbody>
    <?php
    foreach ($object->lines as $line)
    {
        ?>
    <tr>
        <td>
		<?php if (!empty($line->product_label)) echo $line->product_label;
        else echo $line->description;?>
        </td>
        <td class="right"><?php echo $line->qty;?></td>
        <td class="right"><?php echo price(price2num($line->total_ttc / $line->qty, 'MT'), 1);?></td>
        <td class="right"><?php echo price($line->total_ttc, 1);?></td>
    </tr>
        <?php
    }
    ?>
    </tbody>
</table>
<br>
<table class="right">
<tr>
    <th class="right"><?php echo $langs->trans("TotalHT");?></th>
    <td class="right"><?php echo price($object->total_ht, 1, '', 1, - 1, - 1, $conf->currency)."\n";?></td>
</tr>
<?php if($conf->global->TAKEPOS_TICKET_VAT_GROUPPED) {
	$vat_groups = array();
	foreach ($object->lines as $line)
	{
		if(!array_key_exists($line->tva_tx, $vat_groups)) {
			$vat_groups[$line->tva_tx] = 0;
		}
		$vat_groups[$line->tva_tx] += $line->total_tva;
	}
	foreach($vat_groups as $key => $val) {
	    ?>
	<tr>
		<th align="right"><?php echo $langs->trans("VAT").' '.vatrate($key, 1);?></th>
		<td align="right"><?php echo price($val, 1, '', 1, - 1, - 1, $conf->currency)."\n";?></td>
	</tr>
        <?php
	}
} else { ?>
<tr>
	<th class="right"><?php echo $langs->trans("TotalVAT").'</th><td class="right">'.price($object->total_tva, 1, '', 1, - 1, - 1, $conf->currency)."\n";?></td>
</tr>
<?php } ?>
<tr>
	<th class="right"><?php echo ''.$langs->trans("TotalTTC").'</th><td class="right">'.price($object->total_ttc, 1, '', 1, - 1, - 1, $conf->currency)."\n";?></td>
</tr>
</table>
<div style="border-top-style: double;">
<br>
<br>
<br>
<?php
if ($conf->global->TAKEPOS_CUSTOM_RECEIPT)
{
	$substitutionarray = getCommonSubstitutionArray($langs);
	if (!empty($conf->global->TAKEPOS_FOOTER)) {
		$newfreetext = make_substitutions($conf->global->TAKEPOS_FOOTER, $substitutionarray);
		echo $newfreetext;
	}
}
?>

<script type="text/javascript">
    window.print();
</script>
</body>
</html>
