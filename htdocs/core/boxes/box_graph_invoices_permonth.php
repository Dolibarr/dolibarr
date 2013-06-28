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
 *	\file       htdocs/core/boxes/box_invoice_permonth.php
 *	\ingroup    factures
 *	\brief      Box to show graph of invoices per month
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last invoices
 */
class box_graph_invoices_permonth extends ModeleBoxes
{
	var $boxcode="invoicespermonth";
	var $boximg="object_bill";
	var $boxlabel="BoxInvoicesPerMonth";
	var $depends = array("facture");

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
		$this->enabled=$conf->global->MAIN_FEATURES_LEVEL;
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
		
		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		$facturestatic=new Facture($db);

		$text = $langs->trans("BoxInvoicesPerMonth",$max);
		$this->info_box_head = array(
				'text' => $text,
				'limit'=> dol_strlen($text),
				'graph'=> 1,
				'sublink'=>$_SERVER["PHP_SELF"].'?action='.$refreshaction,
				'subtext'=>$langs->trans("Refresh"),
				'subpicto'=>'refresh.png',
				'target'=>'none'
		);

		if ($user->rights->facture->lire)
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			include_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facturestats.class.php';
				
			$nowarray=dol_getdate(dol_now(),true);
			$endyear=$nowarray['year'];
			$startyear=$endyear-1;
			$mode='customer';
			$userid=0;
			$WIDTH='256';
			$HEIGHT='192';
				
			$stats = new FactureStats($this->db, 0, $mode, ($userid>0?$userid:0));
			
			// Build graphic number of object
			// $data = array(array('Lib',val1,val2,val3),...)
			$data = $stats->getNbByMonthWithPrevYear($endyear,$startyear,(GETPOST('action')==$refreshaction?-1:(3600*24)));
			//var_dump($data);
			
			$filenamenb = $dir."/invoicesnbinyear-".$year.".png";
			if ($mode == 'customer') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=billstats&amp;file=invoicesnbinyear-'.$year.'.png';
			if ($mode == 'supplier') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=billstatssupplier&amp;file=invoicesnbinyear-'.$year.'.png';
			
			$px1 = new DolGraph();
			$mesg = $px1->isGraphKo();
			if (! $mesg)
			{
				$px1->SetData($data);
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
				$px1->SetYLabel($langs->trans("NumberOfBills"));
				$px1->SetShading(3);
				$px1->SetHorizTickIncrement(1);
				$px1->SetPrecisionY(0);
				$px1->mode='depth';
				//$px1->SetTitle($langs->trans("NumberOfBillsByMonth"));
			
				$px1->draw($filenamenb,$fileurlnb);
			}

			
			if (! $mesg)
			{
				$this->info_box_contents[0][0] = array('td' => 'align="center"','textnoformat'=>$px1->show());
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
