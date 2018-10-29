<?php
/* Copyright (C) 2013-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/boxes/box_graph_product_distribution.php
 *	\ingroup    factures
 *	\brief      Box to show graph of product distribution
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
//include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/dolchartjs.class.php';

/**
 * Class to manage the box to show last invoices
 */
class box_graph_product_distribution extends ModeleBoxes
{
	var $boxcode = "productdistribution";
	var $boximg = "object_product";
	var $boxlabel= "BoxProductDistribution";
	var $depends = array("product|service", "facture|propal|commande");

    /**
     * @var DoliDB Database handler.
     */
    public $db;

	var $param;

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
		global $user, $conf;

		$this->db=$db;

		$this->hidden = ! (
		    (! empty($conf->facture->enabled) && ! empty($user->rights->facture->lire))
		 || (! empty($conf->commande->enabled) && ! empty($user->rights->commande->lire))
		 || (! empty($conf->propal->enabled) && ! empty($user->rights->propale->lire))
		);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
		global $conf, $user, $langs, $db;

		$this->max=$max;

		$refreshaction = 'refresh_'.$this->boxcode;

		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
		include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

		$param_year = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_year';
		$param_showinvoicenb = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_showinvoicenb';
		$param_showpropalnb = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_showpropalnb';
		$param_showordernb = 'DOLUSERCOOKIE_box_'.$this->boxcode.'_showordernb';
		$autosetarray = preg_split("/[,;:]+/", GETPOST('DOL_AUTOSET_COOKIE'));
		if (in_array('DOLUSERCOOKIE_box_'.$this->boxcode,$autosetarray)) {
			$year=GETPOST($param_year,'int');
			$showinvoicenb=GETPOST($param_showinvoicenb,'alpha');
			$showpropalnb=GETPOST($param_showpropalnb,'alpha');
			$showordernb=GETPOST($param_showordernb,'alpha');
		} else {
			$tmparray=json_decode($_COOKIE['DOLUSERCOOKIE_box_'.$this->boxcode],true);
			$year=$tmparray['year'];
			$showinvoicenb=$tmparray['showinvoicenb'];
			$showpropalnb=$tmparray['showpropalnb'];
			$showordernb=$tmparray['showordernb'];
		}
		if (empty($showinvoicenb) && empty($showpropalnb) && empty($showordernb)) {
            $showpropalnb=1;
            $showinvoicenb=1;
            $showordernb=1;
        }
		if (empty($conf->facture->enabled) || empty($user->rights->facture->lire)) $showinvoicenb=0;
		if (empty($conf->propal->enabled) || empty($user->rights->propale->lire)) $showpropalnb=0;
		if (empty($conf->commande->enabled) || empty($user->rights->commande->lire)) $showordernb=0;

        if (empty($nbyear) || $nbyear<1) {
            $nbyear = 1;
        }
        if ($nbyear>6) {
            $nbyear = 6;
        }
        $nowarray=dol_getdate(dol_now(),true);
        if (empty($year)) {
            $year = $nowarray['year'];
        }

		$nbofgraph=0;
		if ($showinvoicenb) $nbofgraph++;
		if ($showpropalnb) $nbofgraph++;
		if ($showordernb) $nbofgraph++;

		$text = $langs->trans("BoxProductDistribution",$max).' - '.$langs->trans("Year").': '.$year;
		$this->info_box_head = array(
			'text' => $text,
			'limit'=> dol_strlen($text),
			'graph'=> 1,
			'sublink'=>'',
			'subtext'=>$langs->trans("Filter"),
			'subpicto'=>'filter.png',
            'subclass'=>'linkobject boxfilter',
            // Set '' to get target="_blank"
			'target'=>'none'
		);


		$paramtitle=$langs->transnoentitiesnoconv("Products").'/'.$langs->transnoentitiesnoconv("Services");
		if (empty($conf->produit->enabled)) $paramtitle=$langs->transnoentitiesnoconv("Services");
		if (empty($conf->service->enabled)) $paramtitle=$langs->transnoentitiesnoconv("Products");

		$socid=empty($user->societe_id)?0:$user->societe_id;
		$userid=0;	// No filter on user creation

        $width = ($nbofgraph >= 2 || ! empty($conf->dol_optimize_smallscreen))?'40':'80';
        $height = '25';

        if (! empty($conf->facture->enabled) && ! empty($user->rights->facture->lire) && $showinvoicenb) {
            // Build graphic number of object. $data = array(array('Lib',val1,val2,val3),...)
            $langs->load("bills");
            include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';

            $mode = 'customer';
            $stats_invoice = new FactureStats($this->db, $socid, $mode, ($userid>0?$userid:0));
            $data1 = $stats_invoice->getAllByProductEntry($year,(GETPOST('action','aZ09')==$refreshaction?-1:(3600*24)));
            if (empty($data1)) {
                $data1 = array(
                    array(
                        0 => $langs->trans("None"),
                        1 => 1
                    )
                );
            }

            $labels1 = array();
            $datas1 = array();
            foreach ($data1 as $data) {
                $labels1[] = $data[0];
                $datas1[] = $data[1];
            }

            $px1 = new DolChartJs();
            $px1->element('idboxgraphboxbycustomer')
                ->setType('pie')
                ->setLabels($labels1)
                ->setDatasets(
                    array(
                        array(
                            'backgroundColor' => $px1->datacolor,
                            'borderColor' => $px1->bgdatacolor,
                            'data' => $datas1,
                        ),
                    )
                )
                ->setSize(array('width' => $width, 'height' => $height))
                ->setOptions(array(
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                    'legend' => array(
                        'display' => true,
                        'position' => 'right',
                    ),
                    'title' => array(
                        'display' => true,
                        'text' => $langs->transnoentitiesnoconv("BoxProductDistributionFor", $paramtitle, $langs->transnoentitiesnoconv("Invoices")),
                    )
                )
            );
        }

        if (! empty($conf->propal->enabled) && ! empty($user->rights->propale->lire) && $showpropalnb) {
            // Build graphic number of object. $data = array(array('Lib',val1,val2,val3),...)
            $langs->load("propal");
            include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propalestats.class.php';

            $stats_proposal = new PropaleStats($this->db, $socid, ($userid>0?$userid:0));
            $data2 = $stats_proposal->getAllByProductEntry($year, (GETPOST('action','aZ09')==$refreshaction?-1:(3600*24)));
            if (empty($data2)) {
                $data2 = array(
                    array(
                        0 => $langs->trans("None"),
                        1 => 1,
                    ),
                );
            }

            $labels2 = array();
            $datas2 = array();
            foreach ($data2 as $data) {
                $labels2[] = $data[0];
                $datas2[] = $data[1];
            }

            $px2 = new DolChartJs();
            $px2->element('idboxgraphprodbyproposal')
                ->setType('pie')
                ->setLabels($labels2)
                ->setDatasets(
                    array(
                        array(
                            'backgroundColor' => $px2->datacolor,
                            'borderColor' => $px2->bgdatacolor,
                            'data' => $datas2,
                        ),
                    )
                )
                ->setSize(
                    array(
                        'width' => $width,
                        'height' => $height
                    )
                )
                ->setOptions(
                    array(
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'legend' => array(
                            'display' => true,
                            'position' => 'right',
                        ),
                        'title' => array(
                            'display' => true,
                            'text' => $langs->transnoentitiesnoconv("BoxProductDistributionFor", $paramtitle, $langs->transnoentitiesnoconv("Proposals")),
                        )
                    )
                );
        }

        if (! empty($conf->commande->enabled) && ! empty($user->rights->commande->lire) && $showordernb) {
            // Build graphic number of object. $data = array(array('Lib',val1,val2,val3),...)
            $langs->load("orders");

            include_once DOL_DOCUMENT_ROOT.'/commande/class/commandestats.class.php';

            $mode='customer';
            $stats_order = new CommandeStats($this->db, $socid, $mode, ($userid>0?$userid:0));
            $data3 = $stats_order->getAllByProductEntry($year, (GETPOST('action','aZ09')==$refreshaction?-1:(3600*24)));
            if (empty($data3)) {
                $data3=array(
                    array(
                        0 => $langs->trans("None"),
                        1 => 1
                    )
                );
            }

            $labels3 = array();
            $datas3 = array();
            foreach ($data3 as $data) {
                $labels3[] = $data[0];
                $datas3[] = $data[1];
            }

            $px3 = new DolChartJs();
            $px3->element('idboxgraphboxbyorder')
                ->setType('pie')
                ->setLabels($labels3)
                ->setDatasets(
                    array(
                        array(
                            'backgroundColor' => $px3->datacolor,
                            'borderColor' => $px3->bgdatacolor,
                            'data' => $datas3,
                        ),
                    )
                )
                ->setSize(
                    array(
                        'width' => $width,
                        'height' => $height
                    )
                )
                ->setOptions(
                    array(
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'legend' => array(
                            'display' => true,
                            'position' => 'right',
                        ),
                        'title' => array(
                            'display' => true,
                            'text' => $langs->transnoentitiesnoconv("BoxProductDistributionFor", $paramtitle, $langs->transnoentitiesnoconv("Orders")
                        ),
                    )
                )
            );
        }

        if (empty($nbofgraph)) {
            $langs->load("errors");
            $mesg=$langs->trans("ReadPermissionNotAllowed");
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
			$stringtoshow.='<div class="center hideobject" id="idfilter'.$this->boxcode.'">';	// hideobject is to start hidden
			$stringtoshow.='<form class="flat formboxfilter" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			$stringtoshow.='<input type="hidden" name="action" value="'.$refreshaction.'">';
			$stringtoshow.='<input type="hidden" name="page_y" value="">';
			$stringtoshow.='<input type="hidden" name="DOL_AUTOSET_COOKIE" value="DOLUSERCOOKIE_box_'.$this->boxcode.':year,showinvoicenb,showpropalnb,showordernb">';
			if (! empty($conf->facture->enabled) || ! empty($user->rights->facture->lire)) {
				$stringtoshow.='<input type="checkbox" name="'.$param_showinvoicenb.'"'.($showinvoicenb?' checked':'').'> '.$langs->trans("ForCustomersInvoices");
				$stringtoshow.=' &nbsp; ';
			}
			if (! empty($conf->propal->enabled) || ! empty($user->rights->propale->lire)) {
				$stringtoshow.='<input type="checkbox" name="'.$param_showpropalnb.'"'.($showpropalnb?' checked':'').'> '.$langs->trans("ForProposals");
				$stringtoshow.='&nbsp;';
			}
			if (! empty($conf->commande->enabled) || ! empty($user->rights->commande->lire)) {
				$stringtoshow.='<input type="checkbox" name="'.$param_showordernb.'"'.($showordernb?' checked':'').'> '.$langs->trans("ForCustomersOrders");
			}
			$stringtoshow.='<br>';
			$stringtoshow.=$langs->trans("Year").' <input class="flat" size="4" type="text" name="'.$param_year.'" value="'.$year.'">';
			$stringtoshow.='<input type="image" class="reposition inline-block valigntextbottom" alt="'.$langs->trans("Refresh").'" src="'.img_picto('','refresh.png','','',1).'">';
			$stringtoshow.='</form>';
			$stringtoshow.='</div>';

			if ($nbofgraph == 1) {
				if ($showinvoicenb) $stringtoshow.= $px1->renderchart();
				elseif ($showpropalnb) $stringtoshow.= $px2->renderchart();
				else $stringtoshow.= $px3->renderchart();
			} elseif ($nbofgraph == 2) {
				$stringtoshow.='<div class="fichecenter"><div class="containercenter"><div class="fichehalfleft">';
				if ($showinvoicenb) $stringtoshow.= $px1->renderchart();
				elseif ($showpropalnb) $stringtoshow.= $px2->renderchart();
				$stringtoshow.='</div><div class="fichehalfright">';
				if ($showordernb) $stringtoshow.= $px3->renderchart();
				elseif ($showpropalnb) $stringtoshow.= $px2->renderchart();
				$stringtoshow.='</div></div></div>';
			} elseif ($nbofgraph == 3) {
				$stringtoshow.='<div class="fichecenter"><div class="containercenter"><div class="fichehalfleft">';
				$stringtoshow .= $px1->renderchart();
				$stringtoshow .= '</div><div class="fichehalfright">';
				$stringtoshow .= $px2->renderchart();
				$stringtoshow .= '</div></div></div>';
				$stringtoshow .= '<div class="fichecenter"><div class="containercenter">';
				$stringtoshow .= $px3->renderchart();
				$stringtoshow .= '</div></div>';
			}
            $this->info_box_contents[0][0] = array(
                'tr'=>'class="oddeven nohover"',
                'td' => 'align="center" class="nohover"',
                'textnoformat'=>$stringtoshow,
            );
		} else {
			$this->info_box_contents[0][0] = array(
			    'td' => 'align="left" class="nohover opacitymedium"',
				'maxlength'=>500,
				'text' => $mesg
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
