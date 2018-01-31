<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/boxes/box_graph_orders_permonth.php
 *	\ingroup    commandes
 *	\brief      Box to show graph of orders per month
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last orders
 */
class box_graph_orders_permonth extends ModeleBoxes
{
	var $boxcode="orderspermonth";
	var $boximg="object_order";
	var $boxlabel="BoxCustomersOrdersPerMonth";
	var $depends = array("commande");

	var $db;

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

		$this->hidden = ! ($user->rights->commande->lire);
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

		$refreshaction='refresh_'.$this->boxcode;

		//include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		//$commandestatic=new Commande($db);

		$text = $langs->trans("BoxCustomersOrdersPerMonth",$max);
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

		$dir=''; 	// We don't need a path because image file will not be saved into disk
		$prefix='';
		$socid=0;
		if ($user->societe_id) $socid=$user->societe_id;
		if (! $user->rights->societe->client->voir || $socid) $prefix.='private-'.$user->id.'-';	// If user has no permission to see all, output dir is specific to user

		if ($user->rights->commande->lire)
		{
		    $langs->load("orders");

		    $param_year='DOLUSERCOOKIE_box_'.$this->boxcode.'_year';
			$param_shownb='DOLUSERCOOKIE_box_'.$this->boxcode.'_shownb';
			$param_showtot='DOLUSERCOOKIE_box_'.$this->boxcode.'_showtot';

			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			include_once DOL_DOCUMENT_ROOT.'/commande/class/commandestats.class.php';
			$autosetarray=preg_split("/[,;:]+/",GETPOST('DOL_AUTOSET_COOKIE'));
			if (in_array('DOLUSERCOOKIE_box_'.$this->boxcode,$autosetarray))
			{
				$endyear=GETPOST($param_year,'int');
				$shownb=GETPOST($param_shownb,'alpha');
				$showtot=GETPOST($param_showtot,'alpha');
			}
			else
			{
				$tmparray=json_decode($_COOKIE['DOLUSERCOOKIE_box_'.$this->boxcode],true);
				$endyear=$tmparray['year'];
				$shownb=$tmparray['shownb'];
				$showtot=$tmparray['showtot'];
			}
			if (empty($shownb) && empty($showtot)) { $shownb=1; $showtot=1; }
			$nowarray=dol_getdate(dol_now(),true);
			if (empty($endyear)) $endyear=$nowarray['year'];
			$startyear=$endyear-1;
			$mode='customer';
			$WIDTH=(($shownb && $showtot) || ! empty($conf->dol_optimize_smallscreen))?'256':'320';
			$HEIGHT='192';

			$stats = new CommandeStats($this->db, $socid, $mode, 0);

			// Build graphic number of object. $data = array(array('Lib',val1,val2,val3),...)
			if ($shownb)
			{
				$data1 = $stats->getNbByMonthWithPrevYear($endyear,$startyear,(GETPOST('action','aZ09')==$refreshaction?-1:(3600*24)), ($WIDTH<300?2:0));

				$filenamenb = $dir."/".$prefix."ordersnbinyear-".$endyear.".png";
				if ($mode == 'customer') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&amp;file=ordersnbinyear-'.$endyear.'.png';
				if ($mode == 'supplier') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&amp;file=ordersnbinyear-'.$endyear.'.png';

				$px1 = new DolGraph();
				$mesg = $px1->isGraphKo();
				if (! $mesg)
				{
					$px1->SetData($data1);
					unset($data1);
					$px1->SetPrecisionY(0);
					$i=$startyear;$legend=array();
					while ($i <= $endyear)
					{
						$legend[]=$i;
						$i++;
					}
					$px1->SetLegend($legend);
					$px1->SetMaxValue($px1->GetCeilMaxValue());
					$px1->SetWidth($WIDTH);
					$px1->SetHeight($HEIGHT);
					$px1->SetYLabel($langs->trans("NumberOfOrders"));
					$px1->SetShading(3);
					$px1->SetHorizTickIncrement(1);
					$px1->SetPrecisionY(0);
					$px1->SetCssPrefix("cssboxes");
					$px1->mode='depth';
					$px1->SetTitle($langs->trans("NumberOfOrdersByMonth"));

					$px1->draw($filenamenb,$fileurlnb);
				}
			}

			// Build graphic number of object. $data = array(array('Lib',val1,val2,val3),...)
			if ($showtot)
			{
				$data2 = $stats->getAmountByMonthWithPrevYear($endyear,$startyear,(GETPOST('action','aZ09')==$refreshaction?-1:(3600*24)), ($WIDTH<300?2:0));

				$filenamenb = $dir."/".$prefix."ordersamountinyear-".$endyear.".png";
				if ($mode == 'customer') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&amp;file=ordersamountinyear-'.$endyear.'.png';
				if ($mode == 'supplier') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&amp;file=ordersamountinyear-'.$endyear.'.png';

				$px2 = new DolGraph();
				$mesg = $px2->isGraphKo();
				if (! $mesg)
				{
					$px2->SetData($data2);
					unset($data2);
					$px2->SetPrecisionY(0);
					$i=$startyear;$legend=array();
					while ($i <= $endyear)
					{
						$legend[]=$i;
						$i++;
					}
					$px2->SetLegend($legend);
					$px2->SetMaxValue($px2->GetCeilMaxValue());
					$px2->SetWidth($WIDTH);
					$px2->SetHeight($HEIGHT);
					$px2->SetYLabel($langs->trans("AmountOfOrdersHT"));
					$px2->SetShading(3);
					$px2->SetHorizTickIncrement(1);
					$px2->SetPrecisionY(0);
					$px2->SetCssPrefix("cssboxes");
					$px2->mode='depth';
					$px2->SetTitle($langs->trans("AmountOfOrdersByMonthHT"));

					$px2->draw($filenamenb,$fileurlnb);
				}
			}

			if (empty($conf->use_javascript_ajax))
			{
				$langs->load("errors");
				$mesg=$langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
			}

			if (! $mesg)
			{
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
				$stringtoshow.='<input type="hidden" name="DOL_AUTOSET_COOKIE" value="DOLUSERCOOKIE_box_'.$this->boxcode.':year,shownb,showtot">';
				$stringtoshow.='<input type="checkbox" name="'.$param_shownb.'"'.($shownb?' checked':'').'> '.$langs->trans("NumberOfOrdersByMonth");
				$stringtoshow.=' &nbsp; ';
				$stringtoshow.='<input type="checkbox" name="'.$param_showtot.'"'.($showtot?' checked':'').'> '.$langs->trans("AmountOfOrdersByMonthHT");
				$stringtoshow.='<br>';
				$stringtoshow.=$langs->trans("Year").' <input class="flat" size="4" type="text" name="'.$param_year.'" value="'.$endyear.'">';
				$stringtoshow.='<input type="image" alt="'.$langs->trans("Refresh").'" src="'.img_picto($langs->trans("Refresh"),'refresh.png','','',1).'">';
				$stringtoshow.='</form>';
				$stringtoshow.='</div>';
				if ($shownb && $showtot)
				{
					$stringtoshow.='<div class="fichecenter">';
					$stringtoshow.='<div class="fichehalfleft">';
				}
				if ($shownb) $stringtoshow.=$px1->show();
				if ($shownb && $showtot)
				{
					$stringtoshow.='</div>';
					$stringtoshow.='<div class="fichehalfright">';
				}
				if ($showtot) $stringtoshow.=$px2->show();
				if ($shownb && $showtot)
				{
					$stringtoshow.='</div>';
					$stringtoshow.='</div>';
				}
				$this->info_box_contents[0][0] = array('td' => 'align="center" class="nohover"','textnoformat'=>$stringtoshow);
			}
			else
			{
				$this->info_box_contents[0][0] = array(	'td' => 'align="left" class="nohover"',
    	        										'maxlength'=>500,
	            										'text' => $mesg);
			}

		}
		else {
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

