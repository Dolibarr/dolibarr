<?php
/* Copyright (C) 2013-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018       Frédéric France     <frederic.france@netlogic.fr>
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
 *	\file       htdocs/core/boxes/box_graph_product_distribution.php
 *	\ingroup    factures
 *	\brief      Box to show graph of invoices per month
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

/**
 * Class to manage the box to show last invoices
 */
class box_graph_product_distribution extends ModeleBoxes
{
	public $boxcode = "productdistribution";
	public $boximg = "object_product";
	public $boxlabel = "BoxProductDistribution";
	public $depends = array("product|service", "facture|propal|commande");

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $param;

	public $info_box_head = array();
	public $info_box_contents = array();

	public $widgettype = 'graph';


	/**
	 *  Constructor
	 *
	 * 	@param	DoliDB	$db			Database handler
	 *  @param	string	$param		More parameters
	 */
	public function __construct($db, $param)
	{
		global $user, $conf;

		$this->db = $db;

		$this->hidden = !(
			(isModEnabled('facture') && $user->hasRight('facture', 'lire'))
			|| (isModEnabled('commande') && $user->hasRight('commande', 'lire'))
			|| (isModEnabled('propal') && $user->hasRight('propal', 'lire'))
		);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs;

		$this->max = $max;
		$dir = $conf->user->dir_temp;

		$refreshaction = 'refresh_'.$this->boxcode;

		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
		include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

		$param_year = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_year';
		$param_showinvoicenb = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_showinvoicenb';
		$param_showpropalnb = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_showpropalnb';
		$param_showordernb = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_showordernb';
		$autosetarray = preg_split("/[,;:]+/", GETPOST('DOL_AUTOSET_COOKIE'));
		if (in_array('DOLUSERCOOKIE_box_'.$this->boxcode, $autosetarray)) {
			$year = GETPOST($param_year, 'int');
			$showinvoicenb = GETPOST($param_showinvoicenb, 'alpha');
			$showpropalnb = GETPOST($param_showpropalnb, 'alpha');
			$showordernb = GETPOST($param_showordernb, 'alpha');
		} else {
			$tmparray = (!empty($_COOKIE['DOLUSERCOOKIE_box_'.$this->boxcode]) ? json_decode($_COOKIE['DOLUSERCOOKIE_box_'.$this->boxcode], true) : array());
			$year = (!empty($tmparray['year']) ? $tmparray['year'] : '');
			$showinvoicenb = (!empty($tmparray['showinvoicenb']) ? $tmparray['showinvoicenb'] : '');
			$showpropalnb = (!empty($tmparray['showpropalnb']) ? $tmparray['showpropalnb'] : '');
			$showordernb = (!empty($tmparray['showordernb']) ? $tmparray['showordernb'] : '');
		}
		if (empty($showinvoicenb) && empty($showpropalnb) && empty($showordernb)) {
			$showpropalnb = 1;
			$showinvoicenb = 1;
			$showordernb = 1;
		}
		if (!isModEnabled('facture') || !$user->hasRight('facture', 'lire')) {
			$showinvoicenb = 0;
		}
		if (isModEnabled('propal') || !$user->hasRight('propal', 'lire')) {
			$showpropalnb = 0;
		}
		if (!isModEnabled('commande') || !$user->hasRight('commande', 'lire')) {
			$showordernb = 0;
		}

		$nowarray = dol_getdate(dol_now(), true);
		if (empty($year)) {
			$year = $nowarray['year'];
		}

		$nbofgraph = 0;
		if ($showinvoicenb) {
			$nbofgraph++;
		}
		if ($showpropalnb) {
			$nbofgraph++;
		}
		if ($showordernb) {
			$nbofgraph++;
		}

		$text = $langs->trans("BoxProductDistribution", $max).' - '.$langs->trans("Year").': '.$year;
		$this->info_box_head = array(
				'text' => $text,
				'limit'=> dol_strlen($text),
				'graph'=> 1,
				'sublink'=>'',
				'subtext'=>$langs->trans("Filter"),
				'subpicto'=>'filter.png',
				'subclass'=>'linkobject boxfilter',
				'target'=>'none'	// Set '' to get target="_blank"
		);


		$socid = empty($user->socid) ? 0 : $user->socid;
		$userid = 0; // No filter on user creation

		$WIDTH = ($nbofgraph >= 2 || !empty($conf->dol_optimize_smallscreen)) ? '300' : '320';
		$HEIGHT = '150';	// Height require to have 5+1 entries into legend visible.

		if (isModEnabled("propal") && $user->hasRight('propal', 'lire')) {
			// Build graphic number of object. $data = array(array('Lib',val1,val2,val3),...)
			if ($showpropalnb) {
				$langs->load("propal");
				include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propalestats.class.php';

				$showpointvalue = 1;
				$nocolor = 0;
				$stats_proposal = new PropaleStats($this->db, $socid, ($userid > 0 ? $userid : 0));
				$data2 = $stats_proposal->getAllByProductEntry($year, (GETPOST('action', 'aZ09') == $refreshaction ? -1 : (3600 * 24)), $max);
				if (empty($data2)) {
					$showpointvalue = 0;
					$nocolor = 1;
					$data2 = array(array(0=>$langs->trans("None"), 1=>1));
				}

				$filenamenb = $dir."/prodserforpropal-".$year.".png";
				$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=proposalstats&amp;file=prodserforpropal-'.$year.'.png';

				$px2 = new DolGraph();
				$mesg = $px2->isGraphKo();
				if (!$mesg) {
					$i = 0;
					$legend = array();

					// Truncate length of legend
					foreach ($data2 as $key => $val) {
						$data2[$key][0] = dol_trunc($data2[$key][0], 32);
						$legend[] = $data2[$key][0];
						$i++;
					}

					$px2->SetData($data2);
					unset($data2);

					if ($nocolor) {
						$px2->SetDataColor(array(array(220, 220, 220)));
					}
					$px2->SetLegend($legend);
					$px2->setShowLegend(2);
					if (!empty($conf->dol_optimize_smallscreen)) {
						$px2->SetWidth(320);
					}
					$px2->setShowPointValue($showpointvalue);
					$px2->setShowPercent(0);
					$px2->SetMaxValue($px2->GetCeilMaxValue());
					$px2->SetWidth($WIDTH);
					$px2->SetHeight($HEIGHT);
					//$px2->SetYLabel($langs->trans("AmountOfBillsHT"));
					$px2->SetShading(3);
					$px2->SetHorizTickIncrement(1);
					$px2->SetCssPrefix("cssboxes");
					//$px2->mode='depth';
					$px2->SetType(array('pie'));
					$px2->SetTitle($langs->trans("ForObject", $langs->transnoentitiesnoconv("Proposals")));
					$px2->combine = 0.05;

					$px2->draw($filenamenb, $fileurlnb);
				}
			}
		}

		if (isModEnabled('commande') && $user->hasRight('commande', 'lire')) {
			// Build graphic number of object. $data = array(array('Lib',val1,val2,val3),...)
			if ($showordernb) {
				$langs->load("orders");
				include_once DOL_DOCUMENT_ROOT.'/commande/class/commandestats.class.php';

				$showpointvalue = 1;
				$nocolor = 0;
				$mode = 'customer';
				$stats_order = new CommandeStats($this->db, $socid, $mode, ($userid > 0 ? $userid : 0));
				$data3 = $stats_order->getAllByProductEntry($year, (GETPOST('action', 'aZ09') == $refreshaction ? -1 : (3600 * 24)), $max);
				if (empty($data3)) {
					$showpointvalue = 0;
					$nocolor = 1;
					$data3 = array(array(0=>$langs->trans("None"), 1=>1));
				}

				$filenamenb = $dir."/prodserfororder-".$year.".png";
				$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&amp;file=prodserfororder-'.$year.'.png';

				$px3 = new DolGraph();
				$mesg = $px3->isGraphKo();
				if (!$mesg) {
					$i = 0;
					$legend = array();

					// Truncate length of legend
					foreach ($data3 as $key => $val) {
						$data3[$key][0] = dol_trunc($data3[$key][0], 32);
						$legend[] = $data3[$key][0];
						$i++;
					}

					$px3->SetData($data3);
					unset($data3);

					if ($nocolor) {
						$px3->SetDataColor(array(array(220, 220, 220)));
					}
					$px3->SetLegend($legend);
					$px3->setShowLegend(2);
					if (!empty($conf->dol_optimize_smallscreen)) {
						$px3->SetWidth(320);
					}
					$px3->setShowPointValue($showpointvalue);
					$px3->setShowPercent(0);
					$px3->SetMaxValue($px3->GetCeilMaxValue());
					$px3->SetWidth($WIDTH);
					$px3->SetHeight($HEIGHT);
					//$px3->SetYLabel($langs->trans("AmountOfBillsHT"));
					$px3->SetShading(3);
					$px3->SetHorizTickIncrement(1);
					$px3->SetCssPrefix("cssboxes");
					//$px3->mode='depth';
					$px3->SetType(array('pie'));
					$px3->SetTitle($langs->trans("ForObject", $langs->transnoentitiesnoconv("Orders")));
					$px3->combine = 0.05;

					$px3->draw($filenamenb, $fileurlnb);
				}
			}
		}


		if (isModEnabled('facture') && $user->hasRight('facture', 'lire')) {
			// Build graphic number of object. $data = array(array('Lib',val1,val2,val3),...)
			if ($showinvoicenb) {
				$langs->load("bills");
				include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';

				$showpointvalue = 1;
				$nocolor = 0;
				$mode = 'customer';
				$stats_invoice = new FactureStats($this->db, $socid, $mode, ($userid > 0 ? $userid : 0));
				$data1 = $stats_invoice->getAllByProductEntry($year, (GETPOST('action', 'aZ09') == $refreshaction ? -1 : (3600 * 24)), $max);

				if (empty($data1)) {
					$showpointvalue = 0;
					$nocolor = 1;
					$data1 = array(array(0=>$langs->trans("None"), 1=>1));
				}
				$filenamenb = $dir."/prodserforinvoice-".$year.".png";
				$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=productstats&amp;file=prodserforinvoice-'.$year.'.png';

				$px1 = new DolGraph();
				$mesg = $px1->isGraphKo();
				if (!$mesg) {
					$i = 0;
					$legend = array();

					// Truncate length of legend
					foreach ($data1 as $key => $val) {
						$data1[$key][0] = dol_trunc($data1[$key][0], 32);
						$legend[] = $data1[$key][0];
						$i++;
					}

					$px1->SetData($data1);
					unset($data1);

					if ($nocolor) {
						$px1->SetDataColor(array(array(220, 220, 220)));
					}
					$px1->SetLegend($legend);
					$px1->setShowLegend(2);
					if (!empty($conf->dol_optimize_smallscreen)) {
						$px1->SetWidth(320);
					}
					$px1->setShowPointValue($showpointvalue);
					$px1->setShowPercent(0);
					$px1->SetMaxValue($px1->GetCeilMaxValue());
					$px1->SetWidth($WIDTH);
					$px1->SetHeight($HEIGHT);
					//$px1->SetYLabel($langs->trans("NumberOfBills"));
					$px1->SetShading(3);
					$px1->SetHorizTickIncrement(1);
					$px1->SetCssPrefix("cssboxes");
					//$px1->mode='depth';
					$px1->SetType(array('pie'));
					$px1->SetTitle($langs->trans("ForObject", $langs->transnoentitiesnoconv("Invoices")));
					$px1->combine = 0.05;

					$px1->draw($filenamenb, $fileurlnb);
				}
			}
		}

		if (empty($nbofgraph)) {
			$langs->load("errors");
			$mesg = $langs->trans("ReadPermissionNotAllowed");
		}
		if (empty($conf->use_javascript_ajax)) {
			$langs->load("errors");
			$mesg = $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
		}

		if (!$mesg) {
			$stringtoshow = '';
			$stringtoshow .= '<script nonce="'.getNonce().'" type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#idsubimg'.$this->boxcode.'").click(function() {
						jQuery("#idfilter'.$this->boxcode.'").toggle();
					});
				});
			</script>';
			$stringtoshow .= '<div class="center hideobject" id="idfilter'.$this->boxcode.'">'; // hideobject is to start hidden
			$stringtoshow .= '<form class="flat formboxfilter" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			$stringtoshow .= '<input type="hidden" name="token" value="'.newToken().'">';
			$stringtoshow .= '<input type="hidden" name="action" value="'.$refreshaction.'">';
			$stringtoshow .= '<input type="hidden" name="page_y" value="">';
			$stringtoshow .= '<input type="hidden" name="DOL_AUTOSET_COOKIE" value="DOLUSERCOOKIE_box_'.$this->boxcode.':year,showinvoicenb,showpropalnb,showordernb">';
			if (isModEnabled("propal") || $user->hasRight('propal', 'lire')) {
				$stringtoshow .= '<input type="checkbox" name="'.$param_showpropalnb.'"'.($showpropalnb ? ' checked' : '').'> '.$langs->trans("ForProposals");
				$stringtoshow .= '&nbsp;';
			}
			if (isModEnabled('commande') || $user->hasRight('commande', 'lire')) {
				$stringtoshow .= '<input type="checkbox" name="'.$param_showordernb.'"'.($showordernb ? ' checked' : '').'> '.$langs->trans("ForCustomersOrders");
			}
			if (isModEnabled('facture') || $user->hasRight('facture', 'lire')) {
				$stringtoshow .= '<input type="checkbox" name="'.$param_showinvoicenb.'"'.($showinvoicenb ? ' checked' : '').'> '.$langs->trans("ForCustomersInvoices");
				$stringtoshow .= ' &nbsp; ';
			}
			$stringtoshow .= '<br>';
			$stringtoshow .= $langs->trans("Year").' <input class="flat" size="4" type="text" name="'.$param_year.'" value="'.$year.'">';
			$stringtoshow .= '<input type="image" class="reposition inline-block valigntextbottom" alt="'.$langs->trans("Refresh").'" src="'.img_picto('', 'refresh.png', '', '', 1).'">';
			$stringtoshow .= '</form>';
			$stringtoshow .= '</div>';

			if ($nbofgraph == 1) {
				if ($showpropalnb) {
					$stringtoshow .= $px2->show();
				} elseif ($showordernb) {
					$stringtoshow .= $px3->show();
				} else {
					$stringtoshow .= $px1->show();
				}
			}
			if ($nbofgraph == 2) {
				$stringtoshow .= '<div class="fichecenter"><div class="containercenter"><div class="fichehalfleft">';
				if (isModEnabled('propal') && $showpropalnb) {
					$stringtoshow .= $px2->show();
				} elseif (isModEnabled('commande') && $showordernb) {
					$stringtoshow .= $px3->show();
				}
				$stringtoshow .= '</div><div class="fichehalfright">';
				if (isModEnabled('facture') && $showinvoicenb) {
					$stringtoshow .= $px1->show();
				} elseif (isModEnabled('commande') && $showordernb) {
					$stringtoshow .= $px3->show();
				}
				$stringtoshow .= '</div></div></div>';
			}
			if ($nbofgraph == 3) {
				$stringtoshow .= '<div class="fichecenter"><div class="containercenter"><div class="fichehalfleft">';
				$stringtoshow .= $px2->show();
				$stringtoshow .= '</div><div class="fichehalfright">';
				$stringtoshow .= $px3->show();
				$stringtoshow .= '</div></div></div>';
				$stringtoshow .= '<div class="fichecenter"><div class="containercenter">';
				$stringtoshow .= $px1->show();
				$stringtoshow .= '</div></div>';
			}
			$this->info_box_contents[0][0] = array(
				'tr' => 'class="oddeven nohover"',
				'td' => 'class="nohover center"',
				'textnoformat'=>$stringtoshow,
			);
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover left"',
				'maxlength'=>500,
				'text' => '<span class="opacitymedium">'.$mesg.'</span>'
			);
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
