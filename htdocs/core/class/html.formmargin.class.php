<?php
/* Copyright (c) 2015-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/core/class/html.formmargin.class.php
 *  \ingroup    core
 *	\brief      Fichier de la class des functions predefinie de composants html autre
 */


/**
 *	Class permettant la generation de composants html autre
 *	Only common components are here.
 */
class FormMargin
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}



	/**
	 *	get array with margin information from lines of object
	 *  TODO Move this in common class.
	 *
	 * 	@param	CommonObject	$object			Object we want to get margin information for
	 * 	@param 	boolean			$force_price	True of not
	 * 	@return array							Array with info
	 */
	public function getMarginInfosArray($object, $force_price = false)
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

		foreach ($object->lines as $line) {
			if (empty($line->pa_ht) && isset($line->fk_fournprice) && !$force_price) {
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
				$product = new ProductFournisseur($this->db);
				if ($product->fetch_product_fournisseur_price($line->fk_fournprice)) {
					$line->pa_ht = $product->fourn_unitprice * (1 - $product->fourn_remise_percent / 100);
				}
			}

			// If buy price is not defined (null), we will use the sell price. If defined to 0 (it means it was forced to 0 during insert, for example for a free to get product), we must still use 0.
			//if ((!isset($line->pa_ht) || $line->pa_ht == 0) && $line->subprice > 0 && (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull > 0)) {
			if ((!isset($line->pa_ht)) && $line->subprice > 0 && (isset($conf->global->ForceBuyingPriceIfNull) && getDolGlobalInt('ForceBuyingPriceIfNull') > 0)) {
				$line->pa_ht = $line->subprice * (1 - ($line->remise_percent / 100));
			}

			$pv = $line->total_ht;
			// We chose to have line->pa_ht always positive in database, so we guess the correct sign
			// @phan-suppress-next-line PhanUndeclaredConstantOfClass
			$pa_ht = (($pv < 0 || ($pv == 0 && in_array($object->element, array('facture', 'facture_fourn')) && $object->type == $object::TYPE_CREDIT_NOTE)) ? -$line->pa_ht : $line->pa_ht);
			if (getDolGlobalInt('INVOICE_USE_SITUATION') == 1) {	// Special case for old situation mode
				// @phan-suppress-next-line PhanUndeclaredConstantOfClass
				if (($object->element == 'facture' && $object->type == $object::TYPE_SITUATION)
					// @phan-suppress-next-line PhanUndeclaredConstantOfClass
					|| ($object->element == 'facture' && $object->type == $object::TYPE_CREDIT_NOTE && getDolGlobalInt('INVOICE_USE_SITUATION_CREDIT_NOTE') && $object->situation_counter > 0)) {
					// We need a compensation relative to $line->situation_percent
					$pa = $line->qty * $pa_ht * ($line->situation_percent / 100);
				} else {
					$pa = $line->qty * $pa_ht;
				}
			} else {
				$pa = $line->qty * $pa_ht;
			}

			// calcul des marges
			if (isset($line->fk_remise_except) && isset($conf->global->MARGIN_METHODE_FOR_DISCOUNT)) {    // remise
				if (getDolGlobalString('MARGIN_METHODE_FOR_DISCOUNT') == '1') { // remise globale considérée comme produit
					$marginInfos['pa_products'] += $pa;
					$marginInfos['pv_products'] += $pv;
					$marginInfos['pa_total'] += $pa;
					$marginInfos['pv_total'] += $pv;
					// if credit note, margin = -1 * (abs(selling_price) - buying_price)
					//if ($pv < 0)
					//{
					//	$marginInfos['margin_on_products'] += -1 * (abs($pv) - $pa);
					//}
					//else
					$marginInfos['margin_on_products'] += $pv - $pa;
				} elseif (getDolGlobalString('MARGIN_METHODE_FOR_DISCOUNT') == '2') { // remise globale considérée comme service
					$marginInfos['pa_services'] += $pa;
					$marginInfos['pv_services'] += $pv;
					$marginInfos['pa_total'] += $pa;
					$marginInfos['pv_total'] += $pv;
					// if credit note, margin = -1 * (abs(selling_price) - buying_price)
					//if ($pv < 0)
					//	$marginInfos['margin_on_services'] += -1 * (abs($pv) - $pa);
					//else
					$marginInfos['margin_on_services'] += $pv - $pa;
				} elseif (getDolGlobalString('MARGIN_METHODE_FOR_DISCOUNT') == '3') { // remise globale prise en compte uniqt sur total
					$marginInfos['pa_total'] += $pa;
					$marginInfos['pv_total'] += $pv;
				}
			} else {
				$type = $line->product_type ? $line->product_type : $line->fk_product_type;
				if ($type == 0) {  // product
					$marginInfos['pa_products'] += $pa;
					$marginInfos['pv_products'] += $pv;
					$marginInfos['pa_total'] += $pa;
					$marginInfos['pv_total'] += $pv;
					// if credit note, margin = -1 * (abs(selling_price) - buying_price)
					//if ($pv < 0)
					//{
					//    $marginInfos['margin_on_products'] += -1 * (abs($pv) - $pa);
					//}
					//else
					//{
					$marginInfos['margin_on_products'] += $pv - $pa;
					//}
				} elseif ($type == 1) {  // service
					$marginInfos['pa_services'] += $pa;
					$marginInfos['pv_services'] += $pv;
					$marginInfos['pa_total'] += $pa;
					$marginInfos['pv_total'] += $pv;
					// if credit note, margin = -1 * (abs(selling_price) - buying_price)
					//if ($pv < 0)
					//	$marginInfos['margin_on_services'] += -1 * (abs($pv) - $pa);
					//else
					$marginInfos['margin_on_services'] += $pv - $pa;
				}
			}
		}
		if ($marginInfos['pa_products'] > 0) {
			$marginInfos['margin_rate_products'] = 100 * $marginInfos['margin_on_products'] / $marginInfos['pa_products'];
		}
		if ($marginInfos['pv_products'] > 0) {
			$marginInfos['mark_rate_products'] = 100 * $marginInfos['margin_on_products'] / $marginInfos['pv_products'];
		}

		if ($marginInfos['pa_services'] > 0) {
			$marginInfos['margin_rate_services'] = 100 * $marginInfos['margin_on_services'] / $marginInfos['pa_services'];
		}
		if ($marginInfos['pv_services'] > 0) {
			$marginInfos['mark_rate_services'] = 100 * $marginInfos['margin_on_services'] / $marginInfos['pv_services'];
		}

		// if credit note, margin = -1 * (abs(selling_price) - buying_price)
		//if ($marginInfos['pv_total'] < 0)
		//	$marginInfos['total_margin'] = -1 * (abs($marginInfos['pv_total']) - $marginInfos['pa_total']);
		//else
		$marginInfos['total_margin'] = $marginInfos['pv_total'] - $marginInfos['pa_total'];
		if ($marginInfos['pa_total'] > 0) {
			$marginInfos['total_margin_rate'] = 100 * $marginInfos['total_margin'] / $marginInfos['pa_total'];
		}
		if ($marginInfos['pv_total'] > 0) {
			$marginInfos['total_mark_rate'] = 100 * $marginInfos['total_margin'] / $marginInfos['pv_total'];
		}

		return $marginInfos;
	}

	/**
	 * 	Show the array with all margin infos
	 *
	 *	@param	CommonObject	$object			Object we want to get margin information for
	 * 	@param 	boolean			$force_price	Force price
	 * 	@return	void
	 */
	public function displayMarginInfos($object, $force_price = false)
	{
		global $langs, $user, $hookmanager;
		global $action;

		if (!empty($user->socid)) {
			return;
		}

		if (!$user->hasRight('margins', 'liretous')) {
			return;
		}

		$marginInfo = $this->getMarginInfosArray($object, $force_price);

		print '<!-- displayMarginInfos() - Show margin table -->' . "\n";

		$parameters = array('marginInfo' => &$marginInfo);
		$reshook = $hookmanager->executeHooks('displayMarginInfos', $parameters, $object, $action);
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		} elseif (empty($reshook)) {
			$hidemargininfos = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_COOKIE['DOLUSER_MARGININFO_HIDE_SHOW']) ?? ''; // Clean cookie

			$buttonToShowHideMargin = '<span id="showMarginInfos" class="linkobject valignmiddle ' . (!empty($hidemargininfos) ? '' : 'hideobject') . '">';
			$buttonToShowHideMargin .= img_picto($langs->trans("ShowMarginInfos"), 'switch_off', '', 0, 0, 0, '', 'size15x');
			$buttonToShowHideMargin .= '</span>';
			$buttonToShowHideMargin .= '<span id="hideMarginInfos" class="linkobject valignmiddle ' . (!empty($hidemargininfos) ? 'hideobject' : '') . '">';
			$buttonToShowHideMargin .= img_picto($langs->trans("Hide"), 'switch_on_grey', '', 0, 0, 0, '', 'size15x opacitymedium');
			$buttonToShowHideMargin .= '</span>';

			$buttonToShowHideMargin .= '<script nonce="'.getNonce().'">$(document).ready(function() {';
			$buttonToShowHideMargin .= '$("span#showMarginInfos").click(function() { console.log("click on showMargininfos"); date = new Date(); date.setTime(date.getTime()+(30*86400000)); document.cookie = "DOLUSER_MARGININFO_HIDE_SHOW=0; expires=" + date.toGMTString() + "; path=/ "; $(".margininfo").show(); $("span#showMarginInfos").addClass("hideobject"); $("span#hideMarginInfos").removeClass("hideobject"); });';
			$buttonToShowHideMargin .= '$("span#hideMarginInfos").click(function() { console.log("click on hideMarginInfos"); date = new Date(); date.setTime(date.getTime()+(30*86400000)); document.cookie = "DOLUSER_MARGININFO_HIDE_SHOW=1; expires=" + date.toGMTString() + "; path=/ "; $(".margininfo").hide(); $("span#hideMarginInfos").addClass("hideobject"); $("span#showMarginInfos").removeClass("hideobject"); });';
			if (!empty($hidemargininfos)) {
				$buttonToShowHideMargin .= 'console.log("hide the margin info"); $(".margininfo").hide();';
			}
			$buttonToShowHideMargin .= '});</script>';

			print '<div class="div-table-responsive-no-min">';

			print '<table class="noborder margintable centpercent" id="margintable">';
			print '<tr class="liste_titre">';
			print '<td class="liste_titre">' . $langs->trans('Margins') . ' ' . $buttonToShowHideMargin . '</td>';
			print '<td class="liste_titre right margininfo'.(empty($_COOKIE['DOLUSER_MARGININFO_HIDE_SHOW']) ? '' : ' hideobject').'">' . $langs->trans('SellingPrice') . '</td>';
			if (getDolGlobalString('MARGIN_TYPE') == "1") {
				print '<td class="liste_titre right margininfo'.(empty($_COOKIE['DOLUSER_MARGININFO_HIDE_SHOW']) ? '' : ' hideobject').'">' . $langs->trans('BuyingPrice') . '</td>';
			} else {
				print '<td class="liste_titre right margininfo'.(empty($_COOKIE['DOLUSER_MARGININFO_HIDE_SHOW']) ? '' : ' hideobject').'">' . $langs->trans('CostPrice') . '</td>';
			}
			print '<td class="liste_titre right margininfo'.(empty($_COOKIE['DOLUSER_MARGININFO_HIDE_SHOW']) ? '' : ' hideobject').'">' . $langs->trans('Margin') . '</td>';
			if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
				print '<td class="liste_titre right margininfo'.(empty($_COOKIE['DOLUSER_MARGININFO_HIDE_SHOW']) ? '' : ' hideobject').'">' . $langs->trans('MarginRate') . '</td>';
			}
			if (getDolGlobalString('DISPLAY_MARK_RATES')) {
				print '<td class="liste_titre right margininfo'.(empty($_COOKIE['DOLUSER_MARGININFO_HIDE_SHOW']) ? '' : ' hideobject').'">' . $langs->trans('MarkRate') . '</td>';
			}
			print '</tr>';

			if (isModEnabled("product")) {
				//if ($marginInfo['margin_on_products'] != 0 && $marginInfo['margin_on_services'] != 0) {
				print '<tr class="oddeven margininfo'.(empty($_COOKIE['DOLUSER_MARGININFO_HIDE_SHOW']) ? '' : ' hideobject').'">';
				print '<td>' . $langs->trans('MarginOnProducts') . '</td>';
				print '<td class="right">' . price($marginInfo['pv_products']) . '</td>';
				print '<td class="right">' . price($marginInfo['pa_products']) . '</td>';
				print '<td class="right">' . price($marginInfo['margin_on_products']) . '</td>';
				if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
					print '<td class="right">' . (($marginInfo['margin_rate_products'] == '') ? '' : price($marginInfo['margin_rate_products'], 0, '', 0, 0, 2) . '%') . '</td>';
				}
				if (getDolGlobalString('DISPLAY_MARK_RATES')) {
					print '<td class="right">' . (($marginInfo['mark_rate_products'] == '') ? '' : price($marginInfo['mark_rate_products'], 0, '', 0, 0, 2) . '%') . '</td>';
				}
				print '</tr>';
			}

			if (isModEnabled("service")) {
				print '<tr class="oddeven margininfo'.(empty($_COOKIE['DOLUSER_MARGININFO_HIDE_SHOW']) ? '' : ' hideobject').'">';
				print '<td>' . $langs->trans('MarginOnServices') . '</td>';
				print '<td class="right">' . price($marginInfo['pv_services']) . '</td>';
				print '<td class="right">' . price($marginInfo['pa_services']) . '</td>';
				print '<td class="right">' . price($marginInfo['margin_on_services']) . '</td>';
				if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
					print '<td class="right">' . (($marginInfo['margin_rate_services'] == '') ? '' : price($marginInfo['margin_rate_services'], 0, '', 0, 0, 2) . '%') . '</td>';
				}
				if (getDolGlobalString('DISPLAY_MARK_RATES')) {
					print '<td class="right">' . (($marginInfo['mark_rate_services'] == '') ? '' : price($marginInfo['mark_rate_services'], 0, '', 0, 0, 2) . '%') . '</td>';
				}
				print '</tr>';
			}

			if (isModEnabled("product") && isModEnabled("service")) {
				print '<tr class="liste_total margininfo'.(empty($_COOKIE['DOLUSER_MARGININFO_HIDE_SHOW']) ? '' : ' hideobject').'">';
				print '<td>' . $langs->trans('TotalMargin') . '</td>';
				print '<td class="right">' . price($marginInfo['pv_total']) . '</td>';
				print '<td class="right">' . price($marginInfo['pa_total']) . '</td>';
				print '<td class="right">' . price($marginInfo['total_margin']) . '</td>';
				if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
					print '<td class="right">' . (($marginInfo['total_margin_rate'] == '') ? '' : price($marginInfo['total_margin_rate'], 0, '', 0, 0, 2) . '%') . '</td>';
				}
				if (getDolGlobalString('DISPLAY_MARK_RATES')) {
					print '<td class="right">' . (($marginInfo['total_mark_rate'] == '') ? '' : price($marginInfo['total_mark_rate'], 0, '', 0, 0, 2) . '%') . '</td>';
				}
				print '</tr>';
			}
			print $hookmanager->resPrint;
			print '</table>';
			print '</div>';
		} elseif ($reshook > 0) {
			print $hookmanager->resPrint;
		}
	}
}
