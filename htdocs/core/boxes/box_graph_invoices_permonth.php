<?php
/* Copyright (C) 2013       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *  \file       htdocs/core/boxes/box_graph_invoices_permonth.php
 *  \ingroup    factures
 *  \brief      Box to show graph of invoices per month
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last invoices
 */
class box_graph_invoices_permonth extends ModeleBoxes
{
	var $boxcode = "invoicespermonth";
	var $boximg = "object_bill";
	var $boxlabel = "BoxCustomersInvoicesPerMonth";
	var $depends = array("facture");

	/**
     * @var DoliDB Database handler.
     */
    public $db;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 * 	@param	DoliDB	$db			Database handler
	 *  @param	string	$param		More parameters
	 */
	function __construct($db,$param)
	{
		global $user;

		$this->db=$db;

		$this->hidden = ! ($user->rights->facture->lire);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param  int     $max        Maximum number of records to load
     *  @return void
	 */
	function loadBox($max=5)
	{
		global $conf, $user, $langs, $db;

		$this->max=$max;

		$refreshaction = 'refresh_'.$this->boxcode;

		//include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		//$facturestatic=new Facture($db);
        $langs->load("bills");

		$text = $langs->trans("BoxCustomersInvoicesPerMonth",$max);
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

		$socid=0;
		if ($user->societe_id) {
            $socid=$user->societe_id;
        }
		//if (! $user->rights->societe->client->voir || $socid) {
        //    // If user has no permission to see all, output dir is specific to user
        //    $prefix.='private-'.$user->id.'-';
        //}

		if ($user->rights->facture->lire) {
			$mesg = '';

			$param_year = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_year';
			$param_nbyear = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_nbyear';
			$param_shownb = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_shownb';
			$param_showtot = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_showtot';

			include_once DOL_DOCUMENT_ROOT.'/core/class/dolchartjs.class.php';
			include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';
			$autosetarray = preg_split("/[,;:]+/", GETPOST('DOL_AUTOSET_COOKIE'));
			if (in_array('DOLUSERCOOKIE_box_'.$this->boxcode, $autosetarray)) {
				$endyear = GETPOST($param_year, 'int');
				$nbyear = GETPOST($param_nbyear, 'int');
				$shownb = GETPOST($param_shownb, 'alpha');
				$showtot = GETPOST($param_showtot, 'alpha');
			} else {
				$tmparray = json_decode($_COOKIE['DOLUSERCOOKIE_box_'.$this->boxcode],true);
				$endyear = $tmparray['year'];
				$nbyear = $tmparray['nbyear'];
				$shownb = $tmparray['shownb'];
				$showtot = $tmparray['showtot'];
			}
			if (empty($shownb) && empty($showtot)) {
                $shownb = 1;
				$showtot = 1;
			}
			if (empty($nbyear) || $nbyear<1) {
				$nbyear = 1;
			}
			if ($nbyear>8) {
				$nbyear = 8;
			}
			$nowarray = dol_getdate(dol_now(), true);
			if (empty($endyear)) {
                $endyear = $nowarray['year'];
			}
			$startyear = $endyear - $nbyear + 1;
			$mode = 'customer';
			$width = (($shownb && $showtot) || ! empty($conf->dol_optimize_smallscreen))?35:70;
			$height = 25;

			$stats = new FactureStats($this->db, $socid, $mode, 0);

            // Build graphic number of object. $data = array(array('Lib',val1,val2,val3),...)
            if ($shownb) {
                $graph_datas = $stats->getNbByMonthWithPrevYear($endyear, $startyear, (GETPOST('action','aZ09')==$refreshaction?-1:(3600*24)), ($width<80?2:0));

                $labels = array();
                $datas = array();
                $datacolor = array();
                $bgdatacolor = array();
                $dataset = array();

                $px1 = new DolChartJs();
                foreach ($graph_datas as $data) {
                    $labels[] = $data[0];
                    for ($i=0; $i<$nbyear; $i++) {
                        $datacolor[$i][] = $px1->datacolor[$i];
                        $bgdatacolor[$i][] = $px1->bgdatacolor[$i];
                        $datas[$i][] = $data[$i+1];
                    }
                }
                for ($i=0; $i<$nbyear; $i++) {
                    $dataset[] = array(
                        //'label' => $langs->trans("NumberOfBills").' '.($startyear+$i),
                        'label' => $startyear + $i,
                        'backgroundColor' => $datacolor[$i],
                        'borderColor' => $bgdatacolor[$i],
                        'data' => $datas[$i],
                    );
                }
                $px1->element('idboxgraphboxnb'.$this->boxcode)
                    ->setType('bar')
                    ->setLabels($labels)
                    ->setDatasets($dataset)
                    ->setSize(array('width' => $width, 'height' => $height))
                    ->setOptions(array(
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'legend' => array(
                            'display' => true,
                            'position' => 'bottom',
                        ),
                        'title' => array(
                            'display' => true,
                            'text' => $langs->transnoentitiesnoconv("NumberOfBillsByMonth"),
                        )
                    )
                );
			}

			// Build graphic number of object. $data = array(array('Lib',val1,val2,val3),...)
			if ($showtot) {
				$data2 = $stats->getAmountByMonthWithPrevYear($endyear, $startyear, (GETPOST('action','aZ09')==$refreshaction?-1:(3600*24)), ($width<80?2:0));

                $labels2 = array();
                $datas2 = array();
                $datacolor=array();
                $bgdatacolor=array();
                $dataset = array();

                $px2 = new DolChartJs();

                foreach ($data2 as $data) {
                    $labels2[] = $data[0];
                    for ($i=0; $i<$nbyear; $i++) {
                        $datacolor[$i][] = $px2->datacolor[$i];
                        $bgdatacolor[$i][] = $px2->bgdatacolor[$i];
                        $datas2[$i][] = $data[$i+1];
                    }
                }
                for ($i=0; $i<$nbyear; $i++) {
                    $dataset[] = array(
                        //'label' => $langs->trans("AmountOfBillsHT").' '.($startyear+$i),
                        'label' => $startyear + $i,
                        'backgroundColor' => $datacolor[$i],
                        'borderColor' => $bgdatacolor[$i],
                        'data' => $datas2[$i],
                    );
                }
                $px2->element('idboxgraphboxamount'.$this->boxcode)
                    ->setType('bar')
                    ->setLabels($labels2)
                    ->setDatasets($dataset)
                    ->setSize(array('width' => $width, 'height' => $height))
                    ->setOptions(array(
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'legend' => array(
                            'display' => true,
                            'position' => 'bottom',
                        ),
                        'title' => array(
                            'display' => true,
                            'text' => $langs->transnoentitiesnoconv("AmountOfBillsByMonthHT"),
                        )
                    )
                );
			}

			if (empty($conf->use_javascript_ajax)) {
				$langs->load("errors");
				$mesg = $langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
			}

			if (! $mesg) {
				$stringtoshow='';
				$stringtoshow.='<script type="text/javascript" language="javascript">
					jQuery(document).ready(function() {
						jQuery("#idsubimg'.$this->boxcode.'").click(function() {
							jQuery("#idfilter'.$this->boxcode.'").toggle();
						});
					});
					</script>';
				$stringtoshow .= '<div class="center hideobject" id="idfilter'.$this->boxcode.'">';	// hideobject is to start hidden
				$stringtoshow .= '<form class="flat formboxfilter" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
				$stringtoshow .= '<input type="hidden" name="action" value="'.$refreshaction.'">';
				$stringtoshow .= '<input type="hidden" name="page_y" value="">';
				$stringtoshow .= '<input type="hidden" name="DOL_AUTOSET_COOKIE" value="DOLUSERCOOKIE_box_'.$this->boxcode.':year,nbyear,shownb,showtot">';
				$stringtoshow .= '<input type="checkbox" name="'.$param_shownb.'"'.($shownb?' checked':'').'> '.$langs->trans("NumberOfBillsByMonth");
				$stringtoshow .= '&nbsp;&nbsp;';
				$stringtoshow .= '<input type="checkbox" name="'.$param_showtot.'"'.($showtot?' checked':'').'> '.$langs->trans("AmountOfBillsByMonthHT");
				$stringtoshow .= '<br>';
				$stringtoshow .= $langs->trans("NbYear").' <input class="flat" size="4" type="text" name="'.$param_nbyear.'" value="'.$nbyear.'">';
				$stringtoshow .= $langs->trans("Year").' <input class="flat" size="4" type="text" name="'.$param_year.'" value="'.$endyear.'">';
				$stringtoshow .= '<input class="reposition inline-block valigntextbottom" type="image" alt="'.$langs->trans("Refresh").'" src="'.img_picto($langs->trans("Refresh"),'refresh.png','','',1).'">';
				$stringtoshow .= '</form>';
				$stringtoshow .= '</div>';
				if ($shownb && $showtot) {
					$stringtoshow.='<div class="fichecenter">';
					$stringtoshow.='<div class="fichehalfleft">';
				}
				if ($shownb) $stringtoshow.=$px1->renderChart();
				if ($shownb && $showtot) {
					$stringtoshow.='</div>';
					$stringtoshow.='<div class="fichehalfright">';
				}
				if ($showtot) $stringtoshow.=$px2->renderChart();
				if ($shownb && $showtot) {
					$stringtoshow.='</div>';
					$stringtoshow.='</div>';
				}
				$this->info_box_contents[0][0] = array(
                    'tr'=>'class="oddeven nohover"',
                    'td' => 'align="center" class="nohover"',
                    'textnoformat'=>$stringtoshow,
                );
            } else {
                $this->info_box_contents[0][0] = array(
                    'tr'=>'class="oddeven nohover"',
                    'td' => 'align="left" class="nohover"',
                    'maxlength' => 500,
                    'text' => $mesg,
                );
			}
		} else {
			$this->info_box_contents[0][0] = array(
			    'td' => 'align="left" class="nohover opacitymedium"',
                'text' => $langs->trans("ReadPermissionNotAllowed")
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
    function showBox($head = null, $contents = null, $nooutput=0)
    {
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
