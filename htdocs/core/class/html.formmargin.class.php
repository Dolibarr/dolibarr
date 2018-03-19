<?php
/* Copyright (c) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/class/html.formmargin.class.php
 *  \ingroup    core
 *	\brief      Fichier de la classe des fonctions predefinie de composants html autre
 */


/**
 *	Classe permettant la generation de composants html autre
 *	Only common components are here.
 */
class FormMargin
{
    var $db;
    var $error;


    /**
     *	Constructor
     *
     *	@param	DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;

        return 1;
    }



	/**
	 *	get array with margin information from lines of object
	 *  TODO Move this in common class.
	 *
	 * 	@param	CommonObject	$object			Object we want to get margin information for
	 * 	@param 	boolean			$force_price	True of not
	 * 	@return array							Array with info
	 */
	function getMarginInfosArray($object, $force_price=false)
	{
		global $conf, $db;

		// Default returned array
		$marginInfos = array(
				'pa_products' => 0,
				'pv_products' => 0,
				'margin_on_products' => 0,
				'margin_rate_products' => '',
				'mark_rate_products' => '',
				'pa_services' => 0,
				'pv_services' => 0,
				'margin_on_services' => 0,
				'margin_rate_services' => '',
				'mark_rate_services' => '',
				'pa_total' => 0,
				'pv_total' => 0,
				'total_margin' => 0,
				'total_margin_rate' => '',
				'total_mark_rate' => ''
		);

		foreach($object->lines as $line)
		{
			if (empty($line->pa_ht) && isset($line->fk_fournprice) && !$force_price)
			{
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
				$product = new ProductFournisseur($db);
				if ($product->fetch_product_fournisseur_price($line->fk_fournprice))
					$line->pa_ht = $product->fourn_unitprice * (1 - $product->fourn_remise_percent / 100);
			}
			// si prix d'achat non renseigné et devrait l'être, alors prix achat = prix vente
			if ((!isset($line->pa_ht) || $line->pa_ht == 0) && $line->subprice > 0 && (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1)) {
				$line->pa_ht = $line->subprice * (1 - ($line->remise_percent / 100));
			}

			$pv = $line->qty * $line->subprice * (1 - $line->remise_percent / 100);
			$pa_ht = ($pv < 0 ? - $line->pa_ht : $line->pa_ht);      // We choosed to have line->pa_ht always positive in database, so we guess the correct sign
			$pa = $line->qty * $pa_ht;

			// calcul des marges
			if (isset($line->fk_remise_except) && isset($conf->global->MARGIN_METHODE_FOR_DISCOUNT)) {    // remise
				if ($conf->global->MARGIN_METHODE_FOR_DISCOUNT == '1') { // remise globale considérée comme produit
					$marginInfos['pa_products'] += $pa;
					$marginInfos['pv_products'] += $pv;
					$marginInfos['pa_total'] +=  $pa;
					$marginInfos['pv_total'] +=  $pv;
					// if credit note, margin = -1 * (abs(selling_price) - buying_price)
					//if ($pv < 0)
					//{
					//	$marginInfos['margin_on_products'] += -1 * (abs($pv) - $pa);
					//}
					//else
						$marginInfos['margin_on_products'] += $pv - $pa;
				}
				elseif ($conf->global->MARGIN_METHODE_FOR_DISCOUNT == '2') { // remise globale considérée comme service
					$marginInfos['pa_services'] += $pa;
					$marginInfos['pv_services'] += $pv;
					$marginInfos['pa_total'] +=  $pa;
					$marginInfos['pv_total'] +=  $pv;
					// if credit note, margin = -1 * (abs(selling_price) - buying_price)
					//if ($pv < 0)
					//	$marginInfos['margin_on_services'] += -1 * (abs($pv) - $pa);
					//else
						$marginInfos['margin_on_services'] += $pv - $pa;
				}
				elseif ($conf->global->MARGIN_METHODE_FOR_DISCOUNT == '3') { // remise globale prise en compte uniqt sur total
					$marginInfos['pa_total'] += $pa;
					$marginInfos['pv_total'] += $pv;
				}
			}
			else {
				$type=$line->product_type?$line->product_type:$line->fk_product_type;
				if ($type == 0) {  // product
					$marginInfos['pa_products'] += $pa;
					$marginInfos['pv_products'] += $pv;
					$marginInfos['pa_total'] +=  $pa;
					$marginInfos['pv_total'] +=  $pv;
					// if credit note, margin = -1 * (abs(selling_price) - buying_price)
					//if ($pv < 0)
					//{
					//    $marginInfos['margin_on_products'] += -1 * (abs($pv) - $pa);
					//}
					//else
					//{
					    $marginInfos['margin_on_products'] += $pv - $pa;
					//}
				}
				elseif ($type == 1) {  // service
					$marginInfos['pa_services'] += $pa;
					$marginInfos['pv_services'] += $pv;
					$marginInfos['pa_total'] +=  $pa;
					$marginInfos['pv_total'] +=  $pv;
					// if credit note, margin = -1 * (abs(selling_price) - buying_price)
					//if ($pv < 0)
					//	$marginInfos['margin_on_services'] += -1 * (abs($pv) - $pa);
					//else
						$marginInfos['margin_on_services'] += $pv - $pa;
				}
			}
		}
		if ($marginInfos['pa_products'] > 0)
			$marginInfos['margin_rate_products'] = 100 * $marginInfos['margin_on_products'] / $marginInfos['pa_products'];
		if ($marginInfos['pv_products'] > 0)
			$marginInfos['mark_rate_products'] = 100 * $marginInfos['margin_on_products'] / $marginInfos['pv_products'];

		if ($marginInfos['pa_services'] > 0)
			$marginInfos['margin_rate_services'] = 100 * $marginInfos['margin_on_services'] / $marginInfos['pa_services'];
		if ($marginInfos['pv_services'] > 0)
			$marginInfos['mark_rate_services'] = 100 * $marginInfos['margin_on_services'] / $marginInfos['pv_services'];

		// if credit note, margin = -1 * (abs(selling_price) - buying_price)
		//if ($marginInfos['pv_total'] < 0)
		//	$marginInfos['total_margin'] = -1 * (abs($marginInfos['pv_total']) - $marginInfos['pa_total']);
		//else
			$marginInfos['total_margin'] = $marginInfos['pv_total'] - $marginInfos['pa_total'];
		if ($marginInfos['pa_total'] > 0)
			$marginInfos['total_margin_rate'] = 100 * $marginInfos['total_margin'] / $marginInfos['pa_total'];
		if ($marginInfos['pv_total'] > 0)
			$marginInfos['total_mark_rate'] = 100 * $marginInfos['total_margin'] / $marginInfos['pv_total'];

		return $marginInfos;
	}

	/**
	 * 	Show the array with all margin infos
	 *
	 *	@param	CommonObject	$object			Object we want to get margin information for
	 * 	@param 	boolean			$force_price	Force price
	 * 	@return	void
	 */
	function displayMarginInfos($object, $force_price=false)
	{
		global $langs, $conf, $user;

    	if (! empty($user->societe_id)) return;

    	if (! $user->rights->margins->liretous) return;

        $rounding = min($conf->global->MAIN_MAX_DECIMALS_UNIT, $conf->global->MAIN_MAX_DECIMALS_TOT);

		$marginInfo = $this->getMarginInfosArray($object, $force_price);

		if (! empty($conf->global->MARGIN_ADD_SHOWHIDE_BUTTON))	// TODO Warning this feature rely on an external js file that may be removed. Using native js function document.cookie should be better
		{
			print $langs->trans('ShowMarginInfos').' : ';
	        $hidemargininfos = $_COOKIE['DOLUSER_MARGININFO_HIDE_SHOW'];
	    	print '<span id="showMarginInfos" class="linkobject '.(!empty($hidemargininfos)?'':'hideobject').'">'.img_picto($langs->trans("Disabled"),'switch_off').'</span>';
	    	print '<span id="hideMarginInfos" class="linkobject '.(!empty($hidemargininfos)?'hideobject':'').'">'.img_picto($langs->trans("Enabled"),'switch_on').'</span>';

    	    print '<script>$(document).ready(function() {
        	    $("span#showMarginInfos").click(function() { $.getScript( "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'", function( data, textStatus, jqxhr ) { $.cookie("DOLUSER_MARGININFO_HIDE_SHOW", 0); $(".margininfos").show(); $("span#showMarginInfos").addClass("hideobject"); $("span#hideMarginInfos").removeClass("hideobject");})});
        	    $("span#hideMarginInfos").click(function() { $.getScript( "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'", function( data, textStatus, jqxhr ) { $.cookie("DOLUSER_MARGININFO_HIDE_SHOW", 1); $(".margininfos").hide(); $("span#hideMarginInfos").addClass("hideobject"); $("span#showMarginInfos").removeClass("hideobject");})});
      	        });</script>';
    	    if (!empty($hidemargininfos)) print '<script>$(document).ready(function() {$(".margininfos").hide();});</script>';
		}

		print '<div class="div-table-responsive-no-min">';
		print '<!-- Margin table -->'."\n";

		print '<table class="noborder margintable centpercent">';
		print '<tr class="liste_titre">';
		print '<td class="liste_titre">'.$langs->trans('Margins').'</td>';
		print '<td class="liste_titre" align="right">'.$langs->trans('SellingPrice').'</td>';
		if ($conf->global->MARGIN_TYPE == "1")
			print '<td class="liste_titre" align="right">'.$langs->trans('BuyingPrice').'</td>';
		else
			print '<td class="liste_titre" align="right">'.$langs->trans('CostPrice').'</td>';
		print '<td class="liste_titre" align="right">'.$langs->trans('Margin').'</td>';
		if (! empty($conf->global->DISPLAY_MARGIN_RATES))
			print '<td class="liste_titre" align="right">'.$langs->trans('MarginRate').'</td>';
		if (! empty($conf->global->DISPLAY_MARK_RATES))
			print '<td class="liste_titre" align="right">'.$langs->trans('MarkRate').'</td>';
		print '</tr>';

		if (! empty($conf->product->enabled))
		{
			//if ($marginInfo['margin_on_products'] != 0 && $marginInfo['margin_on_services'] != 0) {
			print '<tr class="oddeven">';
			print '<td>'.$langs->trans('MarginOnProducts').'</td>';
			print '<td align="right">'.price($marginInfo['pv_products'], null, null, null, null, $rounding).'</td>';
			print '<td align="right">'.price($marginInfo['pa_products'], null, null, null, null, $rounding).'</td>';
			print '<td align="right">'.price($marginInfo['margin_on_products'], null, null, null, null, $rounding).'</td>';
			if (! empty($conf->global->DISPLAY_MARGIN_RATES))
				print '<td align="right">'.(($marginInfo['margin_rate_products'] == '')?'':price($marginInfo['margin_rate_products'], null, null, null, null, $rounding).'%').'</td>';
			if (! empty($conf->global->DISPLAY_MARK_RATES))
				print '<td align="right">'.(($marginInfo['mark_rate_products'] == '')?'':price($marginInfo['mark_rate_products'], null, null, null, null, $rounding).'%').'</td>';
			print '</tr>';
		}

		if (! empty($conf->service->enabled))
		{
			print '<tr class="oddeven">';
			print '<td>'.$langs->trans('MarginOnServices').'</td>';
			print '<td align="right">'.price($marginInfo['pv_services'], null, null, null, null, $rounding).'</td>';
			print '<td align="right">'.price($marginInfo['pa_services'], null, null, null, null, $rounding).'</td>';
			print '<td align="right">'.price($marginInfo['margin_on_services'], null, null, null, null, $rounding).'</td>';
			if (! empty($conf->global->DISPLAY_MARGIN_RATES))
				print '<td align="right">'.(($marginInfo['margin_rate_services'] == '')?'':price($marginInfo['margin_rate_services'], null, null, null, null, $rounding).'%').'</td>';
			if (! empty($conf->global->DISPLAY_MARK_RATES))
				print '<td align="right">'.(($marginInfo['mark_rate_services'] == '')?'':price($marginInfo['mark_rate_services'], null, null, null, null, $rounding).'%').'</td>';
			print '</tr>';
		}

		if (! empty($conf->product->enabled) && ! empty($conf->service->enabled))
		{
			print '<tr class="liste_total">';
			print '<td>'.$langs->trans('TotalMargin').'</td>';
			print '<td align="right">'.price($marginInfo['pv_total'], null, null, null, null, $rounding).'</td>';
			print '<td align="right">'.price($marginInfo['pa_total'], null, null, null, null, $rounding).'</td>';
			print '<td align="right">'.price($marginInfo['total_margin'], null, null, null, null, $rounding).'</td>';
			if (! empty($conf->global->DISPLAY_MARGIN_RATES))
				print '<td align="right">'.(($marginInfo['total_margin_rate'] == '')?'':price($marginInfo['total_margin_rate'], null, null, null, null, $rounding).'%').'</td>';
			if (! empty($conf->global->DISPLAY_MARK_RATES))
				print '<td align="right">'.(($marginInfo['total_mark_rate'] == '')?'':price($marginInfo['total_mark_rate'], null, null, null, null, $rounding).'%').'</td>';
			print '</tr>';
		}
		print '</table>';
		print '</div>';
	}

}

