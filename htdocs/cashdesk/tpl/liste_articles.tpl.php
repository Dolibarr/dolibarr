<?php
/* Copyright (C) 2007-2008	Jeremie Ollivier	<jeremie.o@laposte.net>
 * Copyright (C) 2011		Juanjo Menent		<jmenent@2byte.es>
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
 */

// Protection to avoid direct call of template
if (empty($langs) || ! is_object($langs))
{
	print "Error, template page can't be called as URL";
	exit;
}


require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// Load translation files required by the page
$langs->loadLangs(array("main","bills","cashdesk"));

?>

<div class="liste_articles_haut">
<div class="liste_articles_bas">

<p class="titre"><?php echo $langs->trans("ShoppingCart"); ?></p>

<?php
/** add Ditto for MultiPrix*/
$thirdpartyid = $_SESSION['CASHDESK_ID_THIRDPARTY'];
$societe = new Societe($db);
$societe->fetch($thirdpartyid);
/** end add Ditto */

$tab = (! empty($_SESSION['poscart'])?$_SESSION['poscart']:array());

$tab_size=count($tab);
if ($tab_size <= 0) print '<div class="center">'.$langs->trans("NoArticle").'</div><br>';
else
{
    for ($i=0;$i < $tab_size;$i++)
    {
        echo ('<div class="cadre_article">'."\n");
        echo ('<p><a href="facturation_verif.php?action=suppr_article&suppr_id='.$tab[$i]['id'].'" title="'.$langs->trans("DeleteArticle").'">'.$tab[$i]['ref'].' - '.$tab[$i]['label'].'</a></p>'."\n");

        if ( $tab[$i]['remise_percent'] > 0 ) {

            $remise_percent = ' -'.$tab[$i]['remise_percent'].'%';
        } else {

            $remise_percent = '';
        }

        $remise = $tab[$i]['remise'];

        echo ('<p>'.$tab[$i]['qte'].' x '.price2num($tab[$i]['price'], 'MT').$remise_percent.' = '.price(price2num($tab[$i]['total_ht'], 'MT'), 0, $langs, 0, 0, -1, $conf->currency).' '.$langs->trans("HT").' ('.price(price2num($tab[$i]['total_ttc'], 'MT'), 0, $langs, 0, 0, -1, $conf->currency).' '.$langs->trans("TTC").')</p>'."\n");
        echo ('</div>'."\n");
    }
}

echo ('<p class="cadre_prix_total">'.$langs->trans("Total").' : '.price(price2num($total_ttc, 'MT'), 0, $langs, 0, 0, -1, $conf->currency).'<br></p>'."\n");

?></div>
</div>
