<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	\file       htdocs/core/boxes/box_graph_invoices_peryear.php
 *	\ingroup    invoices
 *	\brief      Box to show graph of invoices per year
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show invoices per year graph
 */
class box_graph_invoices_peryear extends ModeleBoxes
{
	public $boxcode  = "invoicesperyear";
	public $boximg   = "object_bill";
	public $boxlabel = "BoxCustomersInvoicesPerYear";
	public $depends  = array("facture");

	/**
	 *  Constructor
	 *
	 * 	@param	DoliDB	$db			Database handler
	 *  @param	string	$param		More parameters
	 */
	public function __construct($db, $param)
	{
		global $user;

		$this->db = $db;

		$this->hidden = !$user->hasRight('facture', 'lire');
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

		$refreshaction = 'refresh_'.$this->boxcode;

		//include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		//$facturestatic=new Facture($this->db);

		$startmonth = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
		if (!getDolGlobalString('GRAPH_USE_FISCAL_YEAR')) {
			$startmonth = 1;
		}

		$text = $langs->trans("Turnover", $max);
		$this->info_box_head = array(
			'text' => $text,
			'limit' => dol_strlen($text),
			'graph' => 1,
			'sublink' => '',
			'subtext' => $langs->trans("Filter"),
			'subpicto' => 'filter.png',
			'subclass' => 'linkobject boxfilter',
			'target' => 'none'	// Set '' to get target="_blank"
		);

		$dir = ''; // We don't need a path because image file will not be saved into disk
		$prefix = '';
		$socid = 0;
		if ($user->socid) {
			$socid = $user->socid;
		}
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$prefix .= 'private-'.$user->id.'-';
		} // If user has no permission to see all, output dir is specific to user

		if ($user->hasRight('facture', 'lire')) {
			$mesg = '';

			$param_year = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_year';
			$param_showtot = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_showtot';

			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';
			$autosetarray = preg_split("/[,;:]+/", GETPOST('DOL_AUTOSET_COOKIE'));
			if (in_array('DOLUSERCOOKIE_box_'.$this->boxcode, $autosetarray)) {
				$endyear = GETPOSTINT($param_year);
				$showtot = GETPOST($param_showtot, 'alpha');
			} else {
				$tmparray = json_decode($_COOKIE['DOLUSERCOOKIE_box_'.$this->boxcode], true);
				$endyear = $tmparray['year'];
				$showtot = $tmparray['showtot'];
			}
			if (empty($showtot)) {
				$showtot = 1;
			}
			$nowarray = dol_getdate(dol_now(), true);
			if (empty($endyear)) {
				$endyear = $nowarray['year'];
			}
			$numberyears = getDolGlobalInt('MAIN_NB_OF_YEAR_IN_WIDGET_GRAPH', 5);
			$startyear = $endyear - $numberyears;

			$mode = 'customer';
			$WIDTH = (($showtot) || !empty($conf->dol_optimize_smallscreen)) ? '256' : '320';
			$HEIGHT = '192';

			$stats = new FactureStats($this->db, $socid, $mode, 0);
			$stats->where = "f.fk_statut > 0";

			// Build graphic amount of object. $data = array(array('Lib',val1,val2,val3),...)
			$data2 = $stats->getAmountByYear($numberyears);

			$filenamenb = $dir."/".$prefix."invoicesamountyears-".$endyear.".png";
			// default value for customer mode
			$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=billstats&file=invoicesamountyears-'.$endyear.'.png';
			if ($mode == 'supplier') {
				$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=billstatssupplier&file=invoicessupplieramountyears-'.$endyear.'.png';
			}

			$px2 = new DolGraph();
			$mesg = $px2->isGraphKo();
			if (!$mesg) {
				$langs->load("bills");

				$px2->SetData($data2);
				unset($data2);
				$i = $startyear;
				/*$legend = array();
				while ($i <= $endyear) {
					if ($startmonth != 1) {
						$legend[] = sprintf("%d/%d", $i - 2001, $i - 2000);
					} else {
						$legend[] = $i;
					}
					$i++;
				}*/
				$px2->SetLegend([$langs->trans("AmountOfBillsHT")]);
				$px2->SetMaxValue($px2->GetCeilMaxValue());
				$px2->SetWidth($WIDTH);
				$px2->SetHeight($HEIGHT);
				$px2->SetYLabel($langs->trans("AmountOfBillsHT"));
				$px2->SetShading(3);
				$px2->SetHorizTickIncrement(1);
				$px2->SetCssPrefix("cssboxes");
				$px2->mode = 'depth';
				$px2->SetTitle($langs->trans("Turnover"));

				$px2->draw($filenamenb, $fileurlnb);
			}

			if (empty($conf->use_javascript_ajax)) {
				$langs->load("errors");
				$mesg = $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
			}

			if (!$mesg) {
				$stringtoshow = '';
				$stringtoshow .= '<script nonce="'.getNonce().'" type="text/javascript" language="javascript">
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
				$stringtoshow .= '<input type="hidden" name="DOL_AUTOSET_COOKIE" value="DOLUSERCOOKIE_box_'.$this->boxcode.':year,showtot">';
				$stringtoshow .= $langs->trans("Year").' <input class="flat" size="4" type="text" name="'.$param_year.'" value="'.$endyear.'">';
				$stringtoshow .= '<input class="reposition inline-block valigntextbottom" type="image" alt="'.$langs->trans("Refresh").'" src="'.img_picto($langs->trans("Refresh"), 'refresh.png', '', 0, 1).'">';
				$stringtoshow .= '</form>';
				$stringtoshow .= '</div>';
				$stringtoshow .= $px2->show();
				$this->info_box_contents[0][0] = array('tr' => 'class="oddeven nohover"', 'td' => 'class="nohover center"', 'textnoformat' => $stringtoshow);
			} else {
				$this->info_box_contents[0][0] = array('tr' => 'class="oddeven nohover"', 'td' => 'class="nohover left"', 'maxlength' => 500, 'text' => $mesg);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover left"',
				'text' => '<span class="opacitymedium">'.$langs->trans("ReadPermissionNotAllowed").'</span>'
			);
		}
	}



	/**
	 *	Method to show box.  Called when the box needs to be displayed.
	 *
	 *	@param	?array<array{text?:string,sublink?:string,subtext?:string,subpicto?:?string,picto?:string,nbcol?:int,limit?:int,subclass?:string,graph?:int<0,1>,target?:string}>   $head       Array with properties of box title
	 *	@param	?array<array{tr?:string,td?:string,target?:string,text?:string,text2?:string,textnoformat?:string,tooltip?:string,logo?:string,url?:string,maxlength?:int,asis?:int<0,1>}>   $contents   Array with properties of box lines
	 *	@param	int<0,1>	$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
