<?php
/* Copyright (c) 2008-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2018 Juanjo Menent        <jmenent@2byte.es>
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
 *      \file       htdocs/core/class/html.formactions.class.php
 *      \ingroup    core
 *      \brief      Fichier de la classe des fonctions predefinie de composants html actions
 */


/**
 *      Class to manage building of HTML components
 */
class FormActions
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
	 * @var string Error code (or message)
	 */
	public $error='';


    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Show list of action status
     *
     * 	@param	string	$formname		Name of form where select is included
     * 	@param	string	$selected		Preselected value (-1..100)
     * 	@param	int		$canedit		1=can edit, 0=read only
     *  @param  string	$htmlname   	Name of html prefix for html fields (selectX and valX)
     *  @param	integer	$showempty		Show an empty line if select is used
     *  @param	integer	$onlyselect		0=Standard, 1=Hide percent of completion and force usage of a select list, 2=Same than 1 and add "Incomplete (Todo+Running)
     *  @param  string  $morecss        More css on select field
     * 	@return	void
     */
    function form_select_status_action($formname, $selected, $canedit=1, $htmlname='complete', $showempty=0, $onlyselect=0, $morecss='maxwidth100')
    {
        // phpcs:enable
        global $langs,$conf;

        $listofstatus = array(
            '-1' => $langs->trans("ActionNotApplicable"),
            '0' => $langs->trans("ActionsToDoShort"),
            '50' => $langs->trans("ActionRunningShort"),
            '100' => $langs->trans("ActionDoneShort")
        );
		// +ActionUncomplete

        if (! empty($conf->use_javascript_ajax))
        {
            print "\n";
            print "<script type=\"text/javascript\">
                var htmlname = '".$htmlname."';

                $(document).ready(function () {
                	select_status();

                    $('#select' + htmlname).change(function() {
                        select_status();
                    });
                    // FIXME use another method for update combobox
                    //$('#val' + htmlname).change(function() {
                        //select_status();
                    //});
                });

                function select_status() {
                    var defaultvalue = $('#select' + htmlname).val();
                    var percentage = $('input[name=percentage]');
                    var selected = '".(isset($selected)?$selected:'')."';
                    var value = (selected>0?selected:(defaultvalue>=0?defaultvalue:''));

                    percentage.val(value);

                    if (defaultvalue == -1) {
						percentage.prop('disabled', true);
                        $('.hideifna').hide();
                    }
                    else if (defaultvalue == 0) {
						percentage.val(0);
						percentage.removeAttr('disabled'); /* Not disabled, we want to change it to higher value */
                        $('.hideifna').show();
                    }
                    else if (defaultvalue == 100) {
						percentage.val(100);
						percentage.prop('disabled', true);
                        $('.hideifna').show();
                    }
                    else {
                    	if (defaultvalue == 50 && (percentage.val() == 0 || percentage.val() == 100)) { percentage.val(50) };
                    	percentage.removeAttr('disabled');
                        $('.hideifna').show();
                    }
                }
                </script>\n";
        }
        if (! empty($conf->use_javascript_ajax) || $onlyselect)
        {
        	//var_dump($selected);
        	if ($selected == 'done') $selected='100';
            print '<select '.($canedit?'':'disabled ').'name="'.$htmlname.'" id="select'.$htmlname.'" class="flat'.($morecss?' '.$morecss:'').'">';
            if ($showempty) print '<option value=""'.($selected == ''?' selected':'').'></option>';
            foreach($listofstatus as $key => $val)
            {
                print '<option value="'.$key.'"'.(($selected == $key && strlen($selected) == strlen($key)) || (($selected > 0 && $selected < 100) && $key == '50') ? ' selected' : '').'>'.$val.'</option>';
                if ($key == '50' && $onlyselect == 2)
                {
                	print '<option value="todo"'.($selected == 'todo' ? ' selected' : '').'>'.$langs->trans("ActionUncomplete").' ('.$langs->trans("ActionsToDoShort")."+".$langs->trans("ActionRunningShort").')</option>';
                }
            }
            print '</select>';
            if ($selected == 0 || $selected == 100) $canedit=0;

            if (empty($onlyselect))
            {
	            print ' <input type="text" id="val'.$htmlname.'" name="percentage" class="flat hideifna" value="'.($selected>=0?$selected:'').'" size="2"'.($canedit&&($selected>=0)?'':' disabled').'>';
    	        print '<span class="hideonsmartphone hideifna">%</span>';
            }
        }
        else
		{
            print ' <input type="text" id="val'.$htmlname.'" name="percentage" class="flat" value="'.($selected>=0?$selected:'').'" size="2"'.($canedit?'':' disabled').'>%';
        }
    }


    /**
     *  Show list of actions for element
     *
     *  @param	Object	$object					Object
     *  @param  string	$typeelement			'invoice','propal','order','invoice_supplier','order_supplier','fichinter'
     *	@param	int		$socid					Socid of user
     *  @param	int		$forceshowtitle			Show title even if there is no actions to show
     *  @param  string  $morecss        		More css on table
     *  @param	int		$max					Max number of record
     *  @param	string	$moreparambacktopage	More param for the backtopage
     *  @param	string	$morehtmlright			More html text on right of title line
     *	@return	int								<0 if KO, >=0 if OK
     */
    function showactions($object, $typeelement, $socid=0, $forceshowtitle=0, $morecss='listactions', $max=0, $moreparambacktopage='', $morehtmlright='')
    {
        global $langs,$conf,$user;
        global $bc;

        require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

        $sortfield='a.datep,a.id';
        $sortorder='DESC,DESC';

        $listofactions=ActionComm::getActions($this->db, $socid, $object->id, $typeelement, '', $sortfield, $sortorder, ($max?($max+1):0));
		if (! is_array($listofactions)) dol_print_error($this->db,'FailedToGetActions');

        $num = count($listofactions);
        if ($num || $forceshowtitle)
        {
        	if ($typeelement == 'invoice')               $title=$langs->trans('ActionsOnBill');
        	elseif ($typeelement == 'invoice_supplier' || $typeelement == 'supplier_invoice') $title=$langs->trans('ActionsOnBill');
        	elseif ($typeelement == 'propal')            $title=$langs->trans('ActionsOnPropal');
        	elseif ($typeelement == 'supplier_proposal') $title=$langs->trans('ActionsOnSupplierProposal');
        	elseif ($typeelement == 'order')             $title=$langs->trans('ActionsOnOrder');
        	elseif ($typeelement == 'order_supplier' || $typeelement == 'supplier_order')   $title=$langs->trans('ActionsOnOrder');
        	elseif ($typeelement == 'shipping')          $title=$langs->trans('ActionsOnShipping');
            elseif ($typeelement == 'fichinter')         $title=$langs->trans('ActionsOnFicheInter');
            elseif ($typeelement == 'project')           $title=$langs->trans('LatestLinkedEvents', $max?$max:'');
            elseif ($typeelement == 'task')              $title=$langs->trans('LatestLinkedEvents', $max?$max:'');
            elseif ($typeelement == 'member')            $title=$langs->trans('LatestLinkedEvents', $max?$max:'');
            else $title=$langs->trans("LatestLinkedEvents", $max?$max:'');

            $urlbacktopage=$_SERVER['PHP_SELF'].'?id='.$object->id.($moreparambacktopage?'&'.$moreparambacktopage:'');

            $projectid = $object->fk_project;
            if ($typeelement == 'project') $projectid = $object->id;

            $newcardbutton='';
			if (! empty($conf->agenda->enabled))
			{
				$newcardbutton = '<a class="butActionNew" href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&datep='.dol_print_date(dol_now(),'dayhourlog').'&origin='.$typeelement.'&originid='.$object->id.($object->socid>0?'&socid='.$object->socid:($socid>0?'&socid='.$socid:'')).($projectid>0?'&projectid='.$projectid:'').'&backtopage='.urlencode($urlbacktopage).'"><span class="valignmiddle">'.$langs->trans("AddEvent").'</span>';
				$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
				$newcardbutton.= '</a>';
			}

        	print '<!-- formactions->showactions -->'."\n";
        	print load_fiche_titre($title, $newcardbutton, '', 0, 0, '', $morehtmlright);

        	$page=0; $param='';

        	$total = 0;

        	print '<div class="div-table-responsive-no-min">';
        	print '<table class="noborder'.($morecss?' '.$morecss:'').'" width="100%">';
        	print '<tr class="liste_titre">';
        	print getTitleFieldOfList('Ref',   0, $_SERVER["PHP_SELF"], '', $page, $param, '', $sortfield, $sortorder, '', 1);
        	print getTitleFieldOfList('By',    0, $_SERVER["PHP_SELF"], '', $page, $param, '', $sortfield, $sortorder, '', 1);
        	print getTitleFieldOfList('Type',  0, $_SERVER["PHP_SELF"], '', $page, $param, '', $sortfield, $sortorder, '', 1);
        	print getTitleFieldOfList('Title', 0, $_SERVER["PHP_SELF"], '', $page, $param, '', $sortfield, $sortorder, '', 1);
        	print getTitleFieldOfList('Date',  0, $_SERVER["PHP_SELF"], 'a.datep', $page, $param, 'align="center"', $sortfield, $sortorder, '', 1);
        	print getTitleFieldOfList('',      0, $_SERVER["PHP_SELF"], '', $page, $param, 'align="right"', $sortfield, $sortorder, '', 1);
        	print '</tr>';
        	print "\n";

        	$userstatic = new User($this->db);

        	if (count($listofactions))
        	{
	        	$cursorevent = 0;
	        	foreach($listofactions as $action)
	        	{
	        		if ($max && $cursorevent >= $max) break;

	        		$ref=$action->getNomUrl(1,-1);
	        		$label=$action->getNomUrl(0,38);

	        		print '<tr class="oddeven">';
	        		// Ref
					print '<td class="nowraponall">'.$ref.'</td>';
					// Onwer
	        		print '<td>';
	        		if (! empty($action->userownerid))
	        		{
	        			$userstatic->fetch($action->userownerid);	// TODO Introduce a cache on users fetched
	        			print $userstatic->getNomUrl(-1, '', 0, 0, 16, 0, '', '');
	        		}
	        		print '</td>';
					// Type
	        		print '<td>';
					$imgpicto='';
					if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
					{
						if ($action->type_picto) $imgpicto=img_picto('', $action->type_picto);
						else {
							if ($action->type_code == 'AC_RDV')       $imgpicto=img_picto('', 'object_group', '', false, 0, 0, '', 'paddingright').' ';
							elseif ($action->type_code == 'AC_TEL')   $imgpicto=img_picto('', 'object_phoning', '', false, 0, 0, '', 'paddingright').' ';
							elseif ($action->type_code == 'AC_FAX')   $imgpicto=img_picto('', 'object_phoning_fax', '', false, 0, 0, '', 'paddingright').' ';
							elseif ($action->type_code == 'AC_EMAIL') $imgpicto=img_picto('', 'object_email', '', false, 0, 0, '', 'paddingright').' ';
							elseif ($action->type_code == 'AC_INT')   $imgpicto=img_picto('', 'object_intervention', '', false, 0, 0, '', 'paddingright').' ';
							elseif (! preg_match('/_AUTO/', $action->type_code)) $imgpicto=img_picto('', 'object_action', '', false, 0, 0, '', 'paddingright').' ';
						}
					}
					print $imgpicto;
	        		print $action->type_short ? $action->type_short : $action->type;
	        		print '</td>';
	        		// Label
	        		print '<td>'.$label.'</td>';
	        		// Date
	        		print '<td align="center">'.dol_print_date($action->datep, 'dayhour', 'tzuserrel');
	        		if ($action->datef)
	        		{
		        		$tmpa=dol_getdate($action->datep);
		        		$tmpb=dol_getdate($action->datef);
		        		if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year'])
		        		{
		        			if ($tmpa['hours'] != $tmpb['hours'] || $tmpa['minutes'] != $tmpb['minutes'] && $tmpa['seconds'] != $tmpb['seconds']) print '-'.dol_print_date($action->datef, 'hour', 'tzuserrel');
		        		}
		        		else print '-'.dol_print_date($action->datef, 'dayhour', 'tzuserrel');
	        		}
	        		print '</td>';
	        		print '<td align="right">';
	        		if (! empty($action->author->id))
	        		{
	        			print $action->getLibStatut(3);
	        		}
	        		print '</td>';
	        		print '</tr>';

	        		$cursorevent++;
	        	}
        	}
        	else
        	{
        		print '<tr class="oddeven"><td colspan="6" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
        	}

        	if ($max && $num > $max)
        	{
        		print '<tr class="oddeven"><td colspan="6" class="opacitymedium">'.$langs->trans("More").'...</td></tr>';
        	}

        	print '</table>';
        	print '</div>';
        }

        return $num;
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Output html select list of type of event
     *
     *  @param	array|string	$selected       Type pre-selected (can be 'manual', 'auto' or 'AC_xxx'). Can be an array too.
     *  @param  string		    $htmlname       Name of select field
     *  @param	string		    $excludetype	A type to exclude ('systemauto', 'system', '')
     *  @param	integer		    $onlyautoornot	1=Group all type AC_XXX into 1 line AC_MANUAL. 0=Keep details of type, -1=Keep details and add a combined line "All manual"
     *  @param	int		        $hideinfohelp	1=Do not show info help, 0=Show, -1=Show+Add info to tell how to set default value
     *  @param  int		        $multiselect    1=Allow multiselect of action type
     *  @param  int             $nooutput       1=No output
     * 	@return	string
     */
    function select_type_actions($selected='', $htmlname='actioncode', $excludetype='', $onlyautoornot=0, $hideinfohelp=0, $multiselect=0, $nooutput=0)
    {
        // phpcs:enable
        global $langs,$user,$form,$conf;

        if (! is_object($form)) $form=new Form($this->db);

        require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
        require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
        $caction=new CActionComm($this->db);

       	// Suggest a list with manual events or all auto events
       	$arraylist=$caction->liste_array(1, 'code', $excludetype, $onlyautoornot);
       	array_unshift($arraylist,'&nbsp;');     // Add empty line at start
       	//asort($arraylist);

       	if ($selected == 'manual') $selected='AC_OTH';
       	if ($selected == 'auto')   $selected='AC_OTH_AUTO';

       	if (! empty($conf->global->AGENDA_ALWAYS_HIDE_AUTO)) unset($arraylist['AC_OTH_AUTO']);

       	$out='';

		if (! empty($multiselect))
		{
	        if (!is_array($selected) && !empty($selected)) $selected = explode(',', $selected);
			$out.=$form->multiselectarray($htmlname, $arraylist, $selected, 0, 0, 'centpercent', 0, 0);
		}
		else
		{
			$out.=$form->selectarray($htmlname, $arraylist, $selected, 0, 0, 0, '', 0, 0, 0, '', 'minwidth200', 1);
		}

        if ($user->admin && empty($onlyautoornot) && $hideinfohelp <= 0)
        {
            $out.=info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup").($hideinfohelp == -1 ? ". ".$langs->trans("YouCanSetDefaultValueInModuleSetup") : ''),1);
        }

        if ($nooutput) return $out;
        else print $out;
        return '';
    }
}
