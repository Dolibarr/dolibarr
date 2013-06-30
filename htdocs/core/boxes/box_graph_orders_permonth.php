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
 *	\file       htdocs/core/boxes/box_order_permonth.php
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
	var $boximg="object_bill";
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
		global $conf;

		$this->db=$db;
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

		include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		$commandestatic=new Commande($db);

		$text = $langs->trans("BoxCustomersOrdersPerMonth",$max);
		$this->info_box_head = array(
				'text' => $text,
				'limit'=> dol_strlen($text),
				'graph'=> 1,
				'sublink'=>$_SERVER["PHP_SELF"].'?action='.$refreshaction,
				'subtext'=>$langs->trans("Refresh"),
				'subpicto'=>'refresh.png',
				'target'=>'none'
		);

		if ($user->rights->commande->lire)
		{
			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			include_once DOL_DOCUMENT_ROOT.'/commande/class/commandestats.class.php';

			$shownb=(! empty($conf->global->COMMANDE_BOX_GRAPH_SHOW_NB));
			$showtot=(! isset($conf->global->COMMANDE_BOX_GRAPH_SHOW_TOT) || ! empty($conf->global->COMMANDE_BOX_GRAPH_SHOW_TOT));
			$nowarray=dol_getdate(dol_now(),true);
			$endyear=$nowarray['year'];
			$startyear=$endyear-1;
			$mode='customer';
			$userid=0;
			$WIDTH='256';
			$HEIGHT='192';

			$stats = new CommandeStats($this->db, 0, $mode, ($userid>0?$userid:0));

			// Build graphic number of object. $data = array(array('Lib',val1,val2,val3),...)
			if ($shownb)
			{
				$data1 = $stats->getNbByMonthWithPrevYear($endyear,$startyear,(GETPOST('action')==$refreshaction?-1:(3600*24)));

				$filenamenb = $dir."/ordersnbinyear-".$year.".png";
				if ($mode == 'customer') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&amp;file=ordersnbinyear-'.$year.'.png';
				if ($mode == 'supplier') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&amp;file=ordersnbinyear-'.$year.'.png';

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
				$data2 = $stats->getAmountByMonthWithPrevYear($endyear,$startyear,(GETPOST('action')==$refreshaction?-1:(3600*24)));

				$filenamenb = $dir."/ordersamountinyear-".$year.".png";
				if ($mode == 'customer') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&amp;file=ordersamountinyear-'.$year.'.png';
				if ($mode == 'supplier') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&amp;file=ordersamountinyear-'.$year.'.png';

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

			if (! $mesg)
			{
				if ($shownb && $showtot)
				{
					$stringtoshow ='<div class="fichecenter">';
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
				$this->info_box_contents[0][0] = array('td' => 'align="center"','textnoformat'=>$stringtoshow);
			}
			else
			{
				$this->info_box_contents[0][0] = array(	'td' => 'align="left"',
    	        										'maxlength'=>500,
	            										'text' => $mesg);
			}

		}
		else {
			$this->info_box_contents[0][0] = array('td' => 'align="left"',
            'text' => $langs->trans("ReadPermissionNotAllowed"));
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *	@return	void
	 */
	function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}

?>
