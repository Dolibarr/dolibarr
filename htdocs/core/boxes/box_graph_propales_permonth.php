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
 *	\file       htdocs/core/boxes/box_graph_propales_permonth.php
 *	\ingroup    propales
 *	\brief      Box to show graph of proposals per month
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last propals
 */
class box_graph_propales_permonth extends ModeleBoxes
{
	var $boxcode="propalpermonth";
	var $boximg="object_propal";
	var $boxlabel="BoxProposalsPerMonth";
	var $depends = array("propal");

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

		$this->hidden=! ($user->rights->propale->lire);
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

		//include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
		//$propalstatic=new Propal($db);

		$langs->load("propal");

		$text = $langs->trans("BoxProposalsPerMonth",$max);
		$this->info_box_head = array(
				'text' => $text,
				'limit'=> dol_strlen($text),
				'graph'=> 1,		// Set to 1 if it's a box graph
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

		if ($user->rights->propale->lire)
		{
			$param_year='DOLUSERCOOKIE_box_'.$this->boxcode.'_year';
			$param_shownb='DOLUSERCOOKIE_box_'.$this->boxcode.'_shownb';
			$param_showtot='DOLUSERCOOKIE_box_'.$this->boxcode.'_showtot';

			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propalestats.class.php';
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
			if (empty($shownb) && empty($showtot))  { $shownb=1; $showtot=1; }
			$nowarray=dol_getdate(dol_now(),true);
			if (empty($endyear)) $endyear=$nowarray['year'];
			$startyear=$endyear-1;
			$WIDTH=(($shownb && $showtot) || ! empty($conf->dol_optimize_smallscreen))?'256':'320';
			$HEIGHT='192';

			$stats = new PropaleStats($this->db, $socid, 0);

			// Build graphic number of object. $data = array(array('Lib',val1,val2,val3),...)
			if ($shownb)
			{
				$data1 = $stats->getNbByMonthWithPrevYear($endyear,$startyear,(GETPOST('action','aZ09')==$refreshaction?-1:(3600*24)), ($WIDTH<300?2:0));
				$datatype1 = array_pad(array(), ($endyear-$startyear+1), 'bars');

				$filenamenb = $dir."/".$prefix."propalsnbinyear-".$endyear.".png";
				$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&amp;file=propalsnbinyear-'.$endyear.'.png';

				$px1 = new DolGraph();
				$mesg = $px1->isGraphKo();
				if (! $mesg)
				{
					$px1->SetType($datatype1);
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
					$px1->SetYLabel($langs->trans("NumberOfProposals"));
					$px1->SetShading(3);
					$px1->SetHorizTickIncrement(1);
					$px1->SetPrecisionY(0);
					$px1->SetCssPrefix("cssboxes");
					$px1->mode='depth';
					$px1->SetTitle($langs->trans("NumberOfProposalsByMonth"));

					$px1->draw($filenamenb,$fileurlnb);
				}
			}

			// Build graphic number of object. $data = array(array('Lib',val1,val2,val3),...)
			if ($showtot)
			{
				$data2 = $stats->getAmountByMonthWithPrevYear($endyear,$startyear,(GETPOST('action','aZ09')==$refreshaction?-1:(3600*24)), ($WIDTH<300?2:0));
				$datatype2 = array_pad(array(), ($endyear-$startyear+1), 'bars');
				//$datatype2 = array('lines','bars');

				$filenamenb = $dir."/".$prefix."propalsamountinyear-".$endyear.".png";
				if ($mode == 'customer') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&amp;file=propalsamountinyear-'.$endyear.'.png';
				if ($mode == 'supplier') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstatssupplier&amp;file=propalsamountinyear-'.$endyear.'.png';

				$px2 = new DolGraph();
				$mesg = $px2->isGraphKo();
				if (! $mesg)
				{
					$px2->SetType($datatype2);
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
					$px2->SetYLabel($langs->trans("AmountOfProposalsHT"));
					$px2->SetShading(3);
					$px2->SetHorizTickIncrement(1);
					$px2->SetPrecisionY(0);
					$px2->SetCssPrefix("cssboxes");
					$px2->mode='depth';
					$px2->SetTitle($langs->trans("AmountOfProposalsByMonthHT"));

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
				$stringtoshow.='<div class="center hideobject divboxfilter" id="idfilter'.$this->boxcode.'">';	// hideobject is to start hidden
				$stringtoshow.='<form class="flat formboxfilter" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
				$stringtoshow.='<input type="hidden" name="action" value="'.$refreshaction.'">';
				$stringtoshow.='<input type="hidden" name="page_y" value="">';
				$stringtoshow.='<input type="hidden" name="DOL_AUTOSET_COOKIE" value="DOLUSERCOOKIE_box_'.$this->boxcode.':year,shownb,showtot">';
				$stringtoshow.='<input type="checkbox" name="'.$param_shownb.'"'.($shownb?' checked':'').'> '.$langs->trans("NumberOfProposalsByMonth");
				$stringtoshow.=' &nbsp; ';
				$stringtoshow.='<input type="checkbox" name="'.$param_showtot.'"'.($showtot?' checked':'').'> '.$langs->trans("AmountOfProposalsByMonthHT");
				$stringtoshow.='<br>';
				$stringtoshow.=$langs->trans("Year").' <input class="flat" size="4" type="text" name="'.$param_year.'" value="'.$endyear.'">';
				$stringtoshow.='<input type="image" class="reposition inline-block valigntextbottom" alt="'.$langs->trans("Refresh").'" src="'.img_picto($langs->trans("Refresh"),'refresh.png','','',1).'">';
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
				$this->info_box_contents[0][0] = array('tr'=>'class="oddeven nohover"', 'td' => 'align="center" class="nohover"','textnoformat'=>$stringtoshow);
			}
			else
			{
				$this->info_box_contents[0][0] = array('tr'=>'class="oddeven nohover"',	'td' => 'align="left" class="nohover"',
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

