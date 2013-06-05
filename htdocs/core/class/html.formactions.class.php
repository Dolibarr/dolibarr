<?php
/* Copyright (c) 2008-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
    var $db;
    var $error;


    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }


    /**
     *  Show list of action status
     *
     * 	@param	string	$formname	Name of form where select in included
     * 	@param	string	$selected	Preselected value (-1..100)
     * 	@param	int		$canedit	1=can edit, 0=read only
     *  @param  string	$htmlname   Name of html prefix for html fields (selectX and valX)
     * 	@return	void
     */
    function form_select_status_action($formname,$selected,$canedit=1,$htmlname='complete')
    {
        global $langs,$conf;

        $listofstatus = array(
            '-1' => $langs->trans("ActionNotApplicable"),
            '0' => $langs->trans("ActionRunningNotStarted"),
            '50' => $langs->trans("ActionRunningShort"),
            '100' => $langs->trans("ActionDoneShort")
        );

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
                        percentage.attr('disabled', 'disabled');
                        $('.hideifna').hide();
                    }
                    else if (defaultvalue == 0) {
						percentage.val(0);
                    	percentage.attr('disabled', 'disabled');
                        $('.hideifna').show();
                    }
                    else if (defaultvalue == 100) {
						percentage.val(100);
                        percentage.attr('disabled', 'disabled');
                        $('.hideifna').show();
                    }
                    else {
                    	if (defaultvalue == 50 && (percentage.val() == 0 || percentage.val() == 100)) { percentage.val(50) };
                    	percentage.removeAttr('disabled');
                        $('.hideifna').show();
                    }
                }
                </script>\n";
            print '<select '.($canedit?'':'disabled="disabled" ').'name="status" id="select'.$htmlname.'" class="flat">';
            foreach($listofstatus as $key => $val)
            {
                print '<option value="'.$key.'"'.(($selected == $key) || (($selected > 0 && $selected < 100) && $key == '50') ? ' selected="selected"' : '').'>'.$val.'</option>';
            }
            print '</select>';
            if ($selected == 0 || $selected == 100) $canedit=0;
            print ' <input type="text" id="val'.$htmlname.'" name="percentage" class="flat hideifna" value="'.($selected>=0?$selected:'').'" size="2"'.($canedit&&($selected>=0)?'':' disabled="disabled"').'>';
            print '<span class="hideifna">%</span>';
        }
        else
        {
            print ' <input type="text" id="val'.$htmlname.'" name="percentage" class="flat" value="'.($selected>=0?$selected:'').'" size="2"'.($canedit?'':' disabled="disabled"').'>%';
        }
    }


    /**
     *  Show list of actions for element
     *
     *  @param	Object	$object			Object
     *  @param  string	$typeelement	'invoice','propal','order','invoice_supplier','order_supplier','fichinter'
     *	@param	int		$socid			socid of user
     *  @param	int		$forceshowtitle	Show title even if there is no actions to show
     *	@return	int						<0 if KO, >=0 if OK
     */
    function showactions($object,$typeelement,$socid=0,$forceshowtitle=0)
    {
        global $langs,$conf,$user;
        global $bc;

        require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

        $listofactions=ActionComm::getActions($this->db, $socid, $object->id, $typeelement);
		if (! is_array($listofactions)) dol_print_error($this->db,'FailedToGetActions');

        $num = count($listofactions);
        if ($num || $forceshowtitle)
        {
        	if ($typeelement == 'invoice')   $title=$langs->trans('ActionsOnBill');
        	elseif ($typeelement == 'invoice_supplier' || $typeelement == 'supplier_invoice') $title=$langs->trans('ActionsOnBill');
        	elseif ($typeelement == 'propal')    $title=$langs->trans('ActionsOnPropal');
        	elseif ($typeelement == 'order')     $title=$langs->trans('ActionsOnOrder');
        	elseif ($typeelement == 'order_supplier' || $typeelement == 'supplier_order')   $title=$langs->trans('ActionsOnOrder');
        	elseif ($typeelement == 'project')   $title=$langs->trans('ActionsOnProject');
        	elseif ($typeelement == 'shipping')  $title=$langs->trans('ActionsOnShipping');
            elseif ($typeelement == 'fichinter') $title=$langs->trans('ActionsOnFicheInter');
        	else $title=$langs->trans("Actions");

        	print_titre($title);

        	$total = 0;	$var=true;
        	print '<table class="noborder" width="100%">';
        	print '<tr class="liste_titre">';
        	print '<th class="liste_titre">'.$langs->trans('Ref').'</th>';
        	print '<th class="liste_titre">'.$langs->trans('Action').'</th>';
        	print '<th class="liste_titre">'.$langs->trans('Date').'</th>';
        	print '<th class="liste_titre">'.$langs->trans('By').'</th>';
        	print '</tr>';
        	print "\n";

        	$userstatic = new User($this->db);

        	foreach($listofactions as $action)
        	{
        		$savlabel=$action->label;
        		$action->label=$action->ref;
        		$ref=$action->getNomUrl(1);
        		$action->label=$savlabel;
        		$label=$action->getNomUrl(0,38);

        		$var=!$var;
        		print '<tr '.$bc[$var].'>';
				print '<td>'.$ref.'</td>';
        		print '<td>'.$label.'</td>';
        		print '<td>'.dol_print_date($action->datep,'day').'</td>';
        		print '<td>';
        		if (! empty($action->author->id))
        		{
        			$userstatic->id = $action->author->id;
        			$userstatic->firstname = $action->author->firstname;
        			$userstatic->lastname = $action->author->lastname;
        			print $userstatic->getNomUrl(1);
        		}
        		print '</td>';
        		print '</tr>';
        	}
        	print '</table>';
        }

        return $num;
    }


    /**
     *  Output list of type of event
     *
     *  @param	string		$selected       Type pre-selected (can be 'manual', 'auto' or 'AC_xxx'
     *  @param  string		$htmlname       Nom champ formulaire
     *  @param	string		$excludetype	Type to exclude
     *  @param	string		$onlyautoornot	Group list by auto events or not: We keep only the 2 generic lines (AC_OTH and AC_OTH_AUTO)
     * 	@return	void
     */
    function select_type_actions($selected='',$htmlname='actioncode',$excludetype='',$onlyautoornot=0)
    {
        global $langs,$user;

        require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
        require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
        $caction=new CActionComm($this->db);
        $form=new Form($this->db);

       	// Suggest a list with manual events or all auto events
       	$arraylist=$caction->liste_array(1, 'code', $excludetype, $onlyautoornot);
       	array_unshift($arraylist,'&nbsp;');     // Add empty line at start
       	//asort($arraylist);

       	if ($selected == 'manual') $selected='AC_OTH';
       	if ($selected == 'auto')   $selected='AC_OTH_AUTO';
       	
        print $form->selectarray($htmlname, $arraylist, $selected);
        if ($user->admin && empty($onlyautoornot)) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
    }

}
