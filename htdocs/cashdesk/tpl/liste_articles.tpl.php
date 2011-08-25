<?php
$langs->load("main");
$langs->load("bills");
$langs->load("cashdesk");
?>
<?php
/** add Ditto */
require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');
require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
/** end add Ditto */
?>

<!--Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
	Copyright (C) 2011		Juanjo Menent 	 <jmenent@2byte.es>
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
-->
<div class="liste_articles_haut">
<div class="liste_articles_bas">

<p class="titre"><?php echo $langs->trans("ShoppingCart"); ?></p>

<?php
	// Recuperation du contenu de la vente
	$request='SELECT id, ref, label, qte, price, remise_percent, remise, total_ht, total_ttc FROM '.MAIN_DB_PREFIX.'pos_tmp as c
			LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON c.fk_article = p.rowid
			ORDER BY id';
	dol_syslog($request);
	$res = $db->query ($request);
	if (! $res)
	{
	    dol_print_error($db);
	    exit;
	}

	if ( $db->num_rows($res) )
	{

		$ret=array(); $i=0;

		/** add Ditto for MultiPrix*/
		$thirdpartyid = $_SESSION['CASHDESK_ID_THIRDPARTY'];
		$societe = new Societe($db);
		$societe->fetch($thirdpartyid);
		/** end add Ditto */

		while ( $tab = $db->fetch_array($res) )
		{
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$i][$cle] = $valeur;
			}

			/** add Ditto for MultiPrix*/
			if($conf->global->PRODUIT_MULTIPRICES)
			{
				$product = new Product($db, $ret[$i]['id']);

				if(isset($product->multiprices[$societe->price_level]))
				{
					$ret[$i]['price'] = $product->multiprices_ttc[$societe->price_level];
				}
			}
			/** end add Ditto */


			$i++;
		}
		$tab = $ret;

		$tab_size=count($tab);
		for($i=0;$i < $tab_size;$i++) {

			echo ('<div class="cadre_article">'."\n");
				echo ('<p><a href="facturation_verif.php?action=suppr_article&suppr_id='.$tab[$i]['id'].'" title="'.$langs->trans("DeleteArticle").'">'.$tab[$i]['ref'].' - '.$tab[$i]['label'].'</a></p>'."\n");

				if ( $tab[$i]['remise_percent'] > 0 ) {

					$remise_percent = ' -'.$tab[$i]['remise_percent'].'%';

				} else {

					$remise_percent = '';

				}

				$remise = $tab[$i]['remise'];

				echo ('<p>'.$tab[$i]['qte'].' x '.price2num( $tab[$i]['price'], 'MT').$remise_percent.' = '.price2num($tab[$i]['total_ht'], 'MT').' '.$conf->monnaie.' '.$langs->trans("HT").' ('.price2num($tab[$i]['total_ttc'], 'MT').' '.$conf->monnaie.' '.$langs->trans("TTC").')</p>'."\n");
			echo ('</div>'."\n");

		}

		$obj_facturation->calculTotaux();
		$total_ttc = $obj_facturation->prix_total_ttc();
		echo ('<p class="cadre_prix_total">'.$langs->trans("Total").' : '.price2num($total_ttc, 'MT').' '.$conf->monnaie.'<br></p>'."\n");

	} else {

		echo ('<p class="cadre_aucun_article">'.$langs->trans("NoArticle").'</p>'."\n");

	}

?>
</div>
</div>