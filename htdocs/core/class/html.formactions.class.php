<?php
/* Copyright (c) 2008-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2011 Regis Houssin        <regis@dolibarr.fr>
* Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
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
	function FormActions($db)
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
	 *	@return	int						<0 if KO, >=0 if OK
	 */
	function showactions($object,$typeelement,$socid=0)
	{
		global $langs,$conf,$user;
		global $bc;

		require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");

		$actioncomm = new ActionComm($this->db);
		$actioncomm->getActions($socid, $object->id, $typeelement);

		$num = count($actioncomm->actions);
		if ($num)
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
			print '<tr class="liste_titre"><th class="liste_titre">'.$langs->trans('Ref').'</th><th class="liste_titre">'.$langs->trans('Date').'</th><th class="liste_titre">'.$langs->trans('Action').'</th><th class="liste_titre">'.$langs->trans('By').'</th></tr>';
			print "\n";

			foreach($actioncomm->actions as $action)
			{
				$var=!$var;
				print '<tr '.$bc[$var].'>';
				print '<td>'.$action->getNomUrl(1).'</td>';
				print '<td>'.dol_print_date($action->datep,'day').'</td>';
				print '<td title="'.dol_escape_htmltag($action->label).'">'.dol_trunc($action->label,32).'</td>';
				$userstatic = new User($this->db);
				$userstatic->id = $action->author->id;
				$userstatic->firstname = $action->author->firstname;
				$userstatic->lastname = $action->author->lastname;
				print '<td>'.$userstatic->getNomUrl(1).'</td>';
				print '</tr>';
			}
			print '</table>';
		}

		return $num;
	}


	/**
	 *  Output list of type of event
	 *
	 *  @param	string		$selected        Type pre-selectionne
	 *  @param  string		$htmlname        Nom champ formulaire
	 * 	@return	void
	 */
	function select_type_actions($selected='',$htmlname='actioncode')
	{
		global $langs,$user;

		require_once(DOL_DOCUMENT_ROOT."/comm/action/class/cactioncomm.class.php");
		require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
		$caction=new CActionComm($this->db);
		$form=new Form($this->db);

		$arraylist=$caction->liste_array(1,'code');
		array_unshift($arraylist,'&nbsp;');     // Add empty line at start
		//asort($arraylist);

		print $form->selectarray($htmlname, $arraylist, $selected);
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
	}

}
