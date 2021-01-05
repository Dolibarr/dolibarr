<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2011      Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Marcos García       <marcosgdf@gmail.com>
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

// Protection to avoid direct call of template
if (empty($langs) || !is_object($langs))
{
	print "Error, template page can't be called as URL";
	exit;
}


include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

// Load translation files required by the page
$langs->loadLangs(array("main", "cashdesk"));

top_httphead('text/html');

$facid = GETPOST('facid', 'int');
$object = new Facture($db);
$object->fetch($facid);

?>
<html>
    <head>
    <title><?php echo $langs->trans('PrintTicket') ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo DOL_URL_ROOT; ?>/cashdesk/css/ticket.css">
</head>

<body>

<div class="entete">
    <div class="logo">
        <?php print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_small).'">'; ?>
    </div>
    <div class="infos">
        <p class="address"><?php echo $mysoc->name; ?><br>
        <?php print dol_nl2br(dol_format_address($mysoc)); ?><br>
        </p>

        <p class="date_heure"><?php
		// Recuperation et affichage de la date et de l'heure
		$now = dol_now();
		print dol_print_date($now, 'dayhourtext').'<br>';
		print $object->ref;
		?></p>
    </div>
</div>

<br>

<table class="liste_articles">
    <thead>
	<tr class="titres">
            <th><?php print $langs->trans("Code"); ?></th>
            <th><?php print $langs->trans("Label"); ?></th>
            <th><?php print $langs->trans("Qty"); ?></th>
            <th><?php print $langs->trans("Discount").' (%)'; ?></th>
            <th><?php print $langs->trans("TotalHT"); ?></th>
	</tr>
    </thead>
    <tbody>
    <?php

	$tab = array();
	$tab = $_SESSION['poscart'];

	$tab_size = count($tab);
	for ($i = 0; $i < $tab_size; $i++)
	{
		$remise = $tab[$i]['remise'];
		?>
    <tr>
        <td><?php echo $tab[$i]['ref']; ?></td>
        <td><?php echo $tab[$i]['label']; ?></td>
        <td><?php echo $tab[$i]['qte']; ?></td>
        <td><?php echo $tab[$i]['remise_percent']; ?></td>
        <td class="total"><?php echo price(price2num($tab[$i]['total_ht'], 'MT'), 0, $langs, 0, 0, -1, $conf->currency); ?></td>
    </tr>
        <?php
	}
	?>
    </tbody>
</table>

<table class="totaux">
<tr>
    <th class="nowrap"><?php echo $langs->trans("TotalHT"); ?></th>
    <td class="nowrap"><?php echo price(price2num($obj_facturation->amountWithoutTax(), 'MT'), '', $langs, 0, -1, -1, $conf->currency)."\n"; ?></td>
</tr>
<tr>
    <th class="nowrap"><?php echo $langs->trans("TotalVAT").'</th><td class="nowrap">'.price(price2num($obj_facturation->amountVat(), 'MT'), '', $langs, 0, -1, -1, $conf->currency)."\n"; ?></td>
</tr>
<tr>
    <th class="nowrap"><?php echo ''.$langs->trans("TotalTTC").'</th><td class="nowrap">'.price(price2num($obj_facturation->amountWithTax(), 'MT'), '', $langs, 0, -1, -1, $conf->currency)."\n"; ?></td>
</tr>
</table>

<script type="text/javascript">
    window.print();
</script>

<a class="lien" href="#" onclick="javascript: window.close(); return(false);"><?php echo $langs->trans("Close"); ?></a>
</body>
</html>
