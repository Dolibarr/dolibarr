<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2011      Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2018      Andreu Bisquerra    <jove@bisquerra.com>
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

require '../main.inc.php';	// Load $user and permissions
include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->loadLangs(array("main", "cashdesk"));

/*
 * View
 */

top_httphead('text/html');

$facid=GETPOST('facid','int');
$place=GETPOST('place','int');
if ($place>0){
    $sql="SELECT rowid FROM ".MAIN_DB_PREFIX."facture where ref='(PROV-POS-".$place.")'";
    $resql = $db->query($sql);
    $row = $db->fetch_array ($resql);
    $facid=$row[0];
}
$object=new Facture($db);
$object->fetch($facid);

// IMPORTANT: This file is sended to 'Takepos Printing' application. Keep basic file. No external files as css, js... If you need images use absolut path.
?>
<html>
<body>
<center>
<font size="4">
<?php echo '<b>'.$mysoc->name.'</b>';?>
</font>
</center>
<br>
<p align="left">
<?php print dol_nl2br(dol_format_address($mysoc)).'<br>'.$langs->trans("Phone").': '.$mysoc->phone;
 ?>

</p>
<p align="right">
<?php
print $langs->trans('Date')." ".dol_print_date($object->date, 'day').'<br>';
if ($mysoc->country_code == 'ES') print "Factura simplificada ";
print $object->ref;
?>
</p>
<br>

<table width="100%" style="border-top-style: double;">
    <thead>
	<tr>
        <th align="center"><?php print $langs->trans("Label"); ?></th>
        <th align="right"><?php print $langs->trans("Qty"); ?></th>
        <th align="right"><?php print $langs->trans("Price"); ?></th>
        <th align="right"><?php print $langs->trans("TotalTTC"); ?></th>
	</tr>
    </thead>
    <tbody>
    <?php
    foreach ($object->lines as $line)
    {
    ?>
    <tr>
        <td><?php echo $line->product_label;?></td>
        <td align="right"><?php echo $line->qty;?></td>
        <td align="right"><?php echo $line->total_ttc/$line->qty;?></td>
        <td align="right"><?php echo price($line->total_ttc);?></td>
    </tr>
    <?php
    }
    ?>
    </tbody>
</table>
<br>
<table align="right">
<tr>
    <th align="right"><?php echo $langs->trans("TotalHT");?></th>
    <td align="right"><?php echo price($object->total_ht, 1, '', 1, - 1, - 1, $conf->currency)."\n";?></td>
</tr>
<tr>
    <th align="right"><?php echo $langs->trans("TotalVAT").'</th><td align="right">'.price($object->total_tva, 1, '', 1, - 1, - 1, $conf->currency)."\n";?></td>
</tr>
<tr>
    <th align="right"><?php echo ''.$langs->trans("TotalTTC").'</th><td align="right">'.price($object->total_ttc, 1, '', 1, - 1, - 1, $conf->currency)."\n";?></td>
</tr>
</table>
<div style="border-top-style: double;">
<br>
<br>
<br>
<?php
echo $langs->trans("Cashier: ");
echo $user->firstname.'<br>'.$mysoc->url.'<br>';
echo '<center>'.$langs->trans("Thanks for your coming !").'</center>';
?>

<script type="text/javascript">
    window.print();
</script>
</body>
</html>
