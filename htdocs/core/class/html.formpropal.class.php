<?php
/* Copyright (C) 2012 Laurent Destailleur   <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/class/html.formpropal.class.php
 *  \ingroup    core
 *	\brief      File of class with all html predefined components
 */


/**
 *	Class to manage generation of HTML components for proposal management
 */
class FormPropal
{
	var $db;
	var $error;


	/**
	 * Constructor
	 *
	 * @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

    /**
     *    Return combo list of differents status of a proposal
     *    Values are id of table c_propalst
     *
     *    @param	string	$selected   Preselected value
     *    @param	int		$short		Use short labels
     *    @return	void
     */
    function select_propal_statut($selected='',$short=0)
    {
        global $langs;

        $sql = "SELECT id, code, label, active FROM ".MAIN_DB_PREFIX."c_propalst";
        $sql .= " WHERE active = 1";

        dol_syslog(get_class($this)."::select_propal_statut sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            print '<select class="flat" name="propal_statut">';
            print '<option value="">&nbsp;</option>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    if ($selected == $obj->id)
                    {
                        print '<option value="'.$obj->id.'" selected="selected">';
                    }
                    else
                    {
                        print '<option value="'.$obj->id.'">';
                    }
                    $key=$obj->code;
                    if ($langs->trans("PropalStatus".$key.($short?'Short':'')) != "PropalStatus".$key.($short?'Short':''))
                    {
                        print $langs->trans("PropalStatus".$key.($short?'Short':''));
                    }
                    else
                    {
                        $conv_to_new_code=array('PR_DRAFT'=>'Draft','PR_OPEN'=>'Opened','PR_CLOSED'=>'Closed','PR_SIGNED'=>'Signed','PR_NOTSIGNED'=>'NotSigned','PR_FAC'=>'Billed');
                        if (! empty($conv_to_new_code[$obj->code])) $key=$conv_to_new_code[$obj->code];
                        print ($langs->trans("PropalStatus".$key.($short?'Short':''))!="PropalStatus".$key.($short?'Short':''))?$langs->trans("PropalStatus".$key.($short?'Short':'')):$obj->label;
                    }
                    print '</option>';
                    $i++;
                }
            }
            print '</select>';
        }
        else
        {
            dol_print_error($this->db);
        }
    }


    /**
     *    Return a HTML select list of bank accounts
     *
     *    @param      selected          Id account pre-selected
     *    @param      htmlname          Name of select zone
     *    @param      statut            Status of searched accounts (0=open, 1=closed)
     *    @param      filtre            To filter list
     *    @param      useempty          1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
     *    @param      moreattrib        To add more attribute on select
     */
    function select_comptes($selected='',$htmlname='accountid',$statut=0,$filtre='',$useempty=0,$moreattrib='')
    {
        global $langs, $conf;

        $langs->load("admin");

        $sql = "SELECT rowid, label, bank";
        $sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
        $sql.= " WHERE clos = '".$statut."'";
        $sql.= " AND entity = ".$conf->entity;
        if ($filtre) $sql.=" AND ".$filtre;
        $sql.= " ORDER BY label";

        dol_syslog("Form::select_comptes sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            if ($num)
            {
                print '<select id="select'.$htmlname.'" class="flat selectbankaccount" name="'.$htmlname.'"'.($moreattrib?' '.$moreattrib:'').'>';
                if ($useempty == 1 || ($useempty == 2 && $num > 1))
                {
                    print '<option value="'.$obj->rowid.'">&nbsp;</option>';
                }

                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($result);
                    if ($selected == $obj->rowid)
                    {
                        print '<option value="'.$obj->rowid.'" selected="selected">';
                    }
                    else
                    {
                        print '<option value="'.$obj->rowid.'">';
                    }
                    print $obj->label;
                    print '</option>';
                    $i++;
                }
                print "</select>";
            }
            else
            {
                print $langs->trans("NoActiveBankAccountDefined");
            }
        }
        else {
            dol_print_error($this->db);
        }
    }

    /**
     *    Return list of categories having choosed type
     *
     *    @param    type			Type de categories (0=product, 1=supplier, 2=customer, 3=member)
     *    @param    selected    	Id of category preselected
     *    @param    select_name		HTML field name
     *    @param    maxlength       Maximum length for labels
     *    @param    excludeafterid  Exclude all categories after this leaf in category tree.
     */
    function select_all_categories($type, $selected='', $select_name="", $maxlength=64, $excludeafterid=0)
    {
        global $langs;
        $langs->load("categories");

        if ($select_name=="") $select_name="catMere";

        $cat = new Categorie($this->db);
        $cate_arbo = $cat->get_full_arbo($type,$excludeafterid);

        $output = '<select class="flat" name="'.$select_name.'">';
        if (is_array($cate_arbo))
        {
            if (! count($cate_arbo)) $output.= '<option value="-1" disabled="disabled">'.$langs->trans("NoCategoriesDefined").'</option>';
            else
            {
                $output.= '<option value="-1">&nbsp;</option>';
                foreach($cate_arbo as $key => $value)
                {
                    if ($cate_arbo[$key]['id'] == $selected)
                    {
                        $add = 'selected="selected" ';
                    }
                    else
                    {
                        $add = '';
                    }
                    $output.= '<option '.$add.'value="'.$cate_arbo[$key]['id'].'">'.dol_trunc($cate_arbo[$key]['fulllabel'],$maxlength,'middle').'</option>';
                }
            }
        }
        $output.= '</select>';
        $output.= "\n";
        return $output;
    }

	/**
     *     Show a confirmation HTML form or AJAX popup
     *
     *     @param  page        	   Url of page to call if confirmation is OK
     *     @param  title       	   title
     *     @param  question    	   question
     *     @param  action      	   action
     *	   @param  formquestion	   an array with forms complementary inputs
     * 	   @param  selectedchoice  "" or "no" or "yes"
     * 	   @param  useajax		   0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No
     *     @param  height          Force height of box
     *     @return string          'ajax' if a confirm ajax popup is shown, 'html' if it's an html form
     */
    function form_confirm($page, $title, $question, $action, $formquestion='', $selectedchoice="", $useajax=0, $height=170, $width=500)
    {
    	print $this->formconfirm($page, $title, $question, $action, $formquestion, $selectedchoice, $useajax, $height, $width);
    }

    /**
     *     Show a confirmation HTML form or AJAX popup
     *
     *     @param  	string	$page        	   	Url of page to call if confirmation is OK
     *     @param	string	$title       	   	Title
     *     @param	string	$question    	   	Question
     *     @param 	string	$action      	   	Action
     *	   @param  	array	$formquestion	   	An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , ))
     * 	   @param  	string	$selectedchoice  	"" or "no" or "yes"
     * 	   @param  	int		$useajax		   	0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=preoutput confirm box with div id=dialog-confirm-xxx
     *     @param  	int		$height          	Force height of box
     *     @return 	string          			'ajax' if a confirm ajax popup is shown, 'html' if it's an html form
     */
    function formconfirm($page, $title, $question, $action, $formquestion='', $selectedchoice="", $useajax=0, $height=170, $width=500)
    {
        global $langs,$conf;

        $more='';
        $formconfirm='';
        $inputarray=array();

        if (is_array($formquestion) && count($formquestion) > 0)
        {
        	$more.='<table class="paddingrightonly" width="100%">'."\n";
            $more.='<tr><td colspan="3" valign="top">'.$formquestion['text'].'</td></tr>'."\n";
            foreach ($formquestion as $key => $input)
            {
            	if (is_array($input))
            	{
            		if ($input['type'] == 'text')
	                {
	                    $more.='<tr><td valign="top">'.$input['label'].'</td><td valign="top" colspan="2" align="left"><input type="text" class="flat" id="'.$input['name'].'" name="'.$input['name'].'" size="'.$input['size'].'" value="'.$input['value'].'" /></td></tr>'."\n";
	                }
	                else if ($input['type'] == 'password')
	                {
	                    $more.='<tr><td valign="top">'.$input['label'].'</td><td valign="top" colspan="2" align="left"><input type="password" class="flat" id="'.$input['name'].'" name="'.$input['name'].'" size="'.$input['size'].'" value="'.$input['value'].'" /></td></tr>'."\n";
	                }
	                else if ($input['type'] == 'select')
	                {
	                	$more.='<tr><td valign="top" style="padding: 4px !important;">';
	                	if (! empty($input['label'])) $more.=$input['label'].'</td><td valign="top" colspan="2" align="left" style="padding: 4px !important;">';
	                    $more.=$this->selectarray($input['name'],$input['values'],$input['default'],1);
	                    $more.='</td></tr>'."\n";
	                }
	                else if ($input['type'] == 'checkbox')
	                {
	                    $more.='<tr>';
	                    $more.='<td valign="top">'.$input['label'].' </td><td valign="top" align="left">';
	                    $more.='<input type="checkbox" class="flat" id="'.$input['name'].'" name="'.$input['name'].'"';
	                    if (! is_bool($input['value']) && $input['value'] != 'false') $more.=' checked="checked"';
	                    if (is_bool($input['value']) && $input['value']) $more.=' checked="checked"';
	                    if ($input['disabled']) $more.=' disabled="disabled"';
	                    $more.=' /></td>';
	                    $more.='<td valign="top" align="left">&nbsp;</td>';
	                    $more.='</tr>'."\n";
	                }
	                else if ($input['type'] == 'radio')
	                {
	                    $i=0;
	                    foreach($input['values'] as $selkey => $selval)
	                    {
	                        $more.='<tr>';
	                        if ($i==0) $more.='<td valign="top">'.$input['label'].'</td>';
	                        else $more.='<td>&nbsp;</td>';
	                        $more.='<td valign="top" width="20"><input type="radio" class="flat" id="'.$input['name'].'" name="'.$input['name'].'" value="'.$selkey.'"';
	                        if ($input['disabled']) $more.=' disabled="disabled"';
	                        $more.=' /></td>';
	                        $more.='<td valign="top" align="left">';
	                        $more.=$selval;
	                        $more.='</td></tr>'."\n";
	                        $i++;
	                    }
	                }
	            	else if ($input['type'] == 'other')
	                {
	                	$more.='<tr><td valign="top">';
	                	if (! empty($input['label'])) $more.=$input['label'].'</td><td valign="top" colspan="2" align="left">';
	                    $more.=$input['value'];
	                    $more.='</td></tr>'."\n";
	                }
	                array_push($inputarray,$input['name']);
            	}
            }
            $more.='</table>'."\n";
        }

        $formconfirm.= "\n<!-- begin form_confirm -->\n";

        if ($useajax && $conf->use_javascript_ajax)
        {
        	$autoOpen=true;
        	$dialogconfirm='dialog-confirm';
        	if (! is_int($useajax))
        	{
        		$button=$useajax;
        		$useajax=1;
        		$autoOpen=false;
        		$dialogconfirm.='-'.$button;
        	}
            $pageyes=$page.'&action='.$action.'&confirm=yes';
            $pageno=($useajax == 2?$page.'&confirm=no':'');

            // New code using jQuery only
            $formconfirm.= '<div id="'.$dialogconfirm.'" title="'.dol_escape_htmltag($title).'" style="display: none;">';
            if (! empty($more)) $formconfirm.= '<p>'.$more.'</p>';
            $formconfirm.= img_help('','').' '.$question;
            $formconfirm.= '</div>'."\n";
            $formconfirm.= '<script type="text/javascript">
            $(function() {
                var choice=\'ko\';
                var	$inputarray='.json_encode($inputarray).';
                var button=\''.$button.'\';
            	var dialogconfirm=\''.$dialogconfirm.'\';

			    $( "#" + dialogconfirm ).dialog({
			        autoOpen: '.($autoOpen?'true':'false').',
			        resizable: false,
			        height:'.$height.',
			        width:'.$width.',
			        modal: true,
			        closeOnEscape: false,
			        close: function(event, ui) {
			             if (choice == \'ok\') {
			             	var options="";
			             	if ($inputarray.length>0) {
			             		$.each($inputarray, function() {
			             			var inputname = this;
			             			var inputvalue = $("#" + this).val();
			             			options += \'&\' + inputname + \'=\' + inputvalue;
			             		});
			             		//alert(options);
			             	}
			             	location.href=\''.$pageyes.'\' + options;
			             }
                         '.($pageno?'if (choice == \'ko\') location.href=\''.$pageno.'\';':'').'
		              },
			        buttons: {
			            \''.dol_escape_js($langs->transnoentities("Yes")).'\': function() {
			                choice=\'ok\';
			                $(this).dialog(\'close\');
			            },
			            \''.dol_escape_js($langs->transnoentities("No")).'\': function() {
			            	choice=\'ko\';
			                $(this).dialog(\'close\');
			            }
			        }
			    });

			    if (button.length > 0) {
			    	$( "#" + button ).click(function() {
			    		$("#" + dialogconfirm ).dialog(\'open\');
			    	});
			    }
			});
			</script>';

            $formconfirm.= "\n";
        }
        else
        {
            $formconfirm.= '<form method="POST" action="'.$page.'" class="notoptoleftroright">'."\n";
            $formconfirm.= '<input type="hidden" name="action" value="'.$action.'">';
            $formconfirm.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";

            $formconfirm.= '<table width="100%" class="valid">'."\n";

            // Ligne titre
            $formconfirm.= '<tr class="validtitre"><td class="validtitre" colspan="3">'.img_picto('','recent').' '.$title.'</td></tr>'."\n";

            // Ligne formulaire
            if ($more)
            {
                $formconfirm.='<tr class="valid"><td class="valid" colspan="3">'."\n";
                $formconfirm.=$more;
                $formconfirm.='</td></tr>'."\n";
            }

            // Ligne message
            $formconfirm.= '<tr class="valid">';
            $formconfirm.= '<td class="valid">'.$question.'</td>';
            $formconfirm.= '<td class="valid">';
            $newselectedchoice=empty($selectedchoice)?"no":$selectedchoice;
            $formconfirm.= $this->selectyesno("confirm",$newselectedchoice);
            $formconfirm.= '</td>';
            $formconfirm.= '<td class="valid" align="center"><input class="button" type="submit" value="'.$langs->trans("Validate").'"></td>';
            $formconfirm.= '</tr>'."\n";

            $formconfirm.= '</table>'."\n";

            if (is_array($formquestion))
            {
                foreach ($formquestion as $key => $input)
                {
                    if ($input['type'] == 'hidden') $formconfirm.= '<input type="hidden" name="'.$input['name'].'" value="'.$input['value'].'">';
                }
            }

            $formconfirm.= "</form>\n";
            $formconfirm.= '<br>';
        }

        $formconfirm.= "<!-- end form_confirm -->\n";
        return $formconfirm;
    }


    /**
     *    Show a form to select a project
     *
     *    @param	int		$page        Page
     *    @param	int		$socid       Id third party
     *    @param    int		$selected    Id pre-selected project
     *    @param    string	$htmlname    Name of select field
     *    @return	void
     */
    function form_project($page, $socid, $selected='', $htmlname='projectid')
    {
        global $langs;

        require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");

        $langs->load("project");
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="classin">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            //print "$socid,$selected,$htmlname";
            select_projects($socid,$selected,$htmlname);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                $projet = new Project($this->db);
                $projet->fetch($selected);
                //print '<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$selected.'">'.$projet->title.'</a>';
                print $projet->getNomUrl(0,'',1);
            }
            else
            {
                print "&nbsp;";
            }
        }
    }

    /**
     *    	Show a form to select payment conditions
     *
     *    	@param      page        	Page
     *    	@param      selected    	Id condition pre-selectionne
     *    	@param      htmlname    	Name of select html field
     *		@param		addempty		Ajoute entree vide
     *    @return	void
     */
    function form_conditions_reglement($page, $selected='', $htmlname='cond_reglement_id', $addempty=0)
    {
        global $langs;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setconditions">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->select_conditions_paiements($selected,$htmlname,-1,$addempty);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                $this->load_cache_conditions_paiements();
                print $this->cache_conditions_paiements[$selected]['label'];
            } else {
                print "&nbsp;";
            }
        }
    }

	 /**
     *    	Show a form to select a delivery delay
     *
     *    	@param      page        	Page
     *    	@param      selected    	Id condition pre-selectionne
     *    	@param      htmlname    	Name of select html field
     *		@param		addempty		Ajoute entree vide
     *    @return	void
     */
    function form_availability($page, $selected='', $htmlname='availability', $addempty=0)
    {
        global $langs;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setavailability">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->select_availability($selected,$htmlname,-1,$addempty);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                $this->load_cache_availability();
                print $this->cache_availability[$selected]['label'];
            } else {
                print "&nbsp;";
            }
        }
    }

	/**
     *    	Show a select form to select origin
     *
     *    	@param      page        	Page
     *    	@param      selected    	Id condition pre-selectionne
     *    	@param      htmlname    	Name of select html field
     *		@param		addempty		Add empty entry
     *    @return	void
     */
    function form_demand_reason($page, $selected='', $htmlname='demandreason', $addempty=0)
    {
        global $langs;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setdemandreason">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->select_demand_reason($selected,$htmlname,-1,$addempty);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                $this->load_cache_demand_reason();
                foreach ($this->cache_demand_reason as $key => $val)
                {
                    if ($val['id'] == $selected)
                    {
                        print $val['label'];
                        break;
                    }
                }
            } else {
                print "&nbsp;";
            }
        }
    }

    /**
     *    Show a form to select a date
     *
     *    @param      page        Page
     *    @param      selected    Date preselected
     *    @param      htmlname    Name of input html field
     *    @return	void
     */
    function form_date($page, $selected='', $htmlname)
    {
        global $langs;

        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'" name="form'.$htmlname.'">';
            print '<input type="hidden" name="action" value="set'.$htmlname.'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            print $this->select_date($selected,$htmlname,0,0,1,'form'.$htmlname);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                $this->load_cache_types_paiements();
                print $this->cache_types_paiements[$selected]['label'];
            } else {
                print "&nbsp;";
            }
        }
    }


    /**
     *    	Show a select form to choose a user
     *
     *    	@param      page        	Page
     *   	@param      selected    	Id of user preselected
     *    	@param      htmlname    	Name of input html field
     *  	@param      exclude         List of users id to exclude
     *  	@param      include         List of users id to include
     *    @return	void
     */
    function form_users($page, $selected='', $htmlname='userid', $exclude='', $include='')
    {
        global $langs;

        if ($htmlname != "none")
        {
            print '<form method="POST" action="'.$page.'" name="form'.$htmlname.'">';
            print '<input type="hidden" name="action" value="set'.$htmlname.'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            print $this->select_users($selected,$htmlname,1,$exclude,0,$include);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                require_once(DOL_DOCUMENT_ROOT ."/user/class/user.class.php");
                //$this->load_cache_contacts();
                //print $this->cache_contacts[$selected];
                $theuser=new User($this->db);
                $theuser->fetch($selected);
                print $theuser->getNomUrl(1);
            } else {
                print "&nbsp;";
            }
        }
    }


    /**
     *    Affiche formulaire de selection des modes de reglement
     *
     *    @param      page        Page
     *    @param      selected    Id mode pre-selectionne
     *    @param      htmlname    Name of select html field
     *    @return	void
     */
    function form_modes_reglement($page, $selected='', $htmlname='mode_reglement_id')
    {
        global $langs;
        if ($htmlname != "none")
        {
            print '<form method="POST" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setmode">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->select_types_paiements($selected,$htmlname);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                $this->load_cache_types_paiements();
                print $this->cache_types_paiements[$selected]['label'];
            } else {
                print "&nbsp;";
            }
        }
    }


    /**
     *	Show a select box with available absolute discounts
     *
     *  @param  string	$page        	Page URL where form is shown
     *  @param  int		$selected    	Value pre-selected
     *	@param  string	$htmlname    	Nom du formulaire select. Si none, non modifiable
     *	@param	int		$socid			Third party id
     * 	@param	float	$amount			Total amount available
     * 	@param	string	$filter			SQL filter on discounts
     * 	@param	int		$maxvalue		Max value for lines that can be selected
     *  @param  string	$more           More string to add
     *  @return	void
     */
    function form_remise_dispo($page, $selected='', $htmlname='remise_id',$socid, $amount, $filter='', $maxvalue=0, $more='')
    {
        global $conf,$langs;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setabsolutediscount">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td nowrap="nowrap">';
            if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
            {
                if (! $filter || $filter=="fk_facture_source IS NULL") print $langs->trans("CompanyHasAbsoluteDiscount",price($amount),$langs->transnoentities("Currency".$conf->currency)).': ';    // If we want deposit to be substracted to payments only and not to total of final invoice
                else print $langs->trans("CompanyHasCreditNote",price($amount),$langs->transnoentities("Currency".$conf->currency)).': ';
            }
            else
            {
                if (! $filter || $filter=="fk_facture_source IS NULL OR (fk_facture_source IS NOT NULL AND description='(DEPOSIT)')") print $langs->trans("CompanyHasAbsoluteDiscount",price($amount),$langs->transnoentities("Currency".$conf->currency)).': ';
                else print $langs->trans("CompanyHasCreditNote",price($amount),$langs->transnoentities("Currency".$conf->currency)).': ';
            }
            $newfilter='fk_facture IS NULL AND fk_facture_line IS NULL';	// Remises disponibles
            if ($filter) $newfilter.=' AND ('.$filter.')';
            $nbqualifiedlines=$this->select_remises($selected,$htmlname,$newfilter,$socid,$maxvalue);
            print '</td>';
            print '<td>';
            if ($nbqualifiedlines > 0)
            {
                print ' &nbsp; <input type="submit" class="button" value="'.$langs->trans("UseLine").'"';
                if ($filter && $filter != "fk_facture_source IS NULL OR (fk_facture_source IS NOT NULL AND description='(DEPOSIT)')") print '" title="'.$langs->trans("UseCreditNoteInInvoicePayment");
                print '">';
            }
            if ($more) print $more;
            print '</td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                print $selected;
            }
            else
            {
                print "0";
            }
        }
    }


    /**
     *    Affiche formulaire de selection des contacts
     *
     *    @param      page        Page
     *    @param      selected    Id contact pre-selectionne
     *    @param      htmlname    Nom du formulaire select
     *    @return	void
     */
    function form_contacts($page, $societe, $selected='', $htmlname='contactidp')
    {
        global $langs;

        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="set_contact">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $num=$this->select_contacts($societe->id, $selected, $htmlname);
            if ($num==0)
            {
                print '<font class="error">Cette societe n\'a pas de contact, veuillez en cr�er un avant de faire votre proposition commerciale</font><br>';
                print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$societe->id.'&amp;action=create&amp;backtoreferer=1">'.$langs->trans('AddContact').'</a>';
            }
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                require_once(DOL_DOCUMENT_ROOT ."/contact/class/contact.class.php");
                //$this->load_cache_contacts();
                //print $this->cache_contacts[$selected];
                $contact=new Contact($this->db);
                $contact->fetch($selected);
                print $contact->getFullName($langs);
            } else {
                print "&nbsp;";
            }
        }
    }

/**
     *    Affiche formulaire de selection des tiers
     *
     *    @param      page        Page
     *    @param      selected    Id contact pre-selectionne
     *    @param      htmlname    Nom du formulaire select
     *    @return	void
     */
    function form_thirdparty($page, $selected='', $htmlname='socid')
    {
        global $langs;

        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="set_thirdparty">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $num=$this->select_societes($selected , $htmlname);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                require_once(DOL_DOCUMENT_ROOT ."/societe/class/societe.class.php");
                $soc = new Societe($this->db);
                $soc->fetch($selected);
                print $soc->getNomUrl($langs);
            } else {
                print "&nbsp;";
            }
        }
    }

    /**
     *  Show form to select addresse
     *
     *  @param  page        	Page
     *  @param  selected    	Id condition pre-selectionne
     *  @param  htmlname    	Nom du formulaire select
     *	@param	origin        	Origine de l'appel pour pouvoir creer un retour
     *  @param  originid      	Id de l'origine
     *  @return	void
     *  @deprecated
     */
    function form_address($page, $selected='', $socid, $htmlname='address_id', $origin='', $originid='')
    {
        global $langs,$conf;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setaddress">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->select_address($selected, $socid, $htmlname, 1);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'">';
            $langs->load("companies");
            print ' &nbsp; <a href='.DOL_URL_ROOT.'/comm/address.php?socid='.$socid.'&action=create&origin='.$origin.'&originid='.$originid.'>'.$langs->trans("AddAddress").'</a>';
            print '</td></tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                require_once(DOL_DOCUMENT_ROOT ."/societe/class/address.class.php");
                $address=new Address($this->db);
                $result=$address->fetch_address($selected);
                print '<a href='.DOL_URL_ROOT.'/comm/address.php?socid='.$address->socid.'&id='.$address->id.'&action=edit&origin='.$origin.'&originid='.$originid.'>'.$address->label.'</a>';
            }
            else
            {
                print "&nbsp;";
            }
        }
    }

    /**
     *    Retourne la liste des devises, dans la langue de l'utilisateur
     *
     *    @param     selected    code devise pre-selectionne
     *    @param     htmlname    nom de la liste deroulante
     *    @return	void
     */
    function select_currency($selected='',$htmlname='currency_id')
    {
    	print $this->selectcurrency($selected,$htmlname);
    }

    /**
     *    Retourne la liste des devises, dans la langue de l'utilisateur
     *
     *    @param     selected    code devise pre-selectionne
     *    @param     htmlname    nom de la liste deroulante
     */
    function selectcurrency($selected='',$htmlname='currency_id')
    {
        global $conf,$langs,$user;

        $langs->load("dict");

        $out='';
        $currencyArray=array();
        $label=array();

        if ($selected=='euro' || $selected=='euros') $selected='EUR';   // Pour compatibilite

        $sql = "SELECT code_iso, label";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_currencies";
        $sql.= " WHERE active = 1";
        $sql.= " ORDER BY code_iso ASC";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $out.= '<select class="flat" name="'.$htmlname.'">';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;

                while ($i < $num) {
                    $obj = $this->db->fetch_object($resql);
                    $currencyArray[$i]['code_iso'] 	= $obj->code_iso;
                    $currencyArray[$i]['label']		= ($obj->code_iso && $langs->trans("Currency".$obj->code_iso)!="Currency".$obj->code_iso?$langs->trans("Currency".$obj->code_iso):($obj->label!='-'?$obj->label:''));
                	$label[$i] 	= $currencyArray[$i]['label'];
                    $i++;
                }

                array_multisort($label, SORT_ASC, $currencyArray);

                foreach ($currencyArray as $row) {
                	if ($selected && $selected == $row['code_iso']) {
                        $foundselected=true;
                        $out.= '<option value="'.$row['code_iso'].'" selected="selected">';
                    } else {
                        $out.= '<option value="'.$row['code_iso'].'">';
                    }
                    $out.= $row['label'];
                    if ($row['code_iso']) $out.= ' ('.$row['code_iso'] . ')';
                    $out.= '</option>';
                }
            }
            $out.= '</select>';
            if ($user->admin) $out.= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
            return $out;
        }
        else
        {
            dol_print_error($this->db);
        }
    }

    /**
     *      Output an HTML select vat rate
     *
     *      @param      htmlname            Nom champ html
     *      @param      selectedrate        Forcage du taux tva pre-selectionne. Mettre '' pour aucun forcage.
     *      @param      societe_vendeuse    Objet societe vendeuse
     *      @param      societe_acheteuse   Objet societe acheteuse
     *      @param      idprod              Id product
     *      @param      info_bits           Miscellaneous information on line
     *      @param      type               ''=Unknown, 0=Product, 1=Service (Used if idprod not defined)
     *                  Si vendeur non assujeti a TVA, TVA par defaut=0. Fin de regle.
     *                  Si le (pays vendeur = pays acheteur) alors la TVA par defaut=TVA du produit vendu. Fin de regle.
     *                  Si (vendeur et acheteur dans Communaute europeenne) et bien vendu = moyen de transports neuf (auto, bateau, avion), TVA par defaut=0 (La TVA doit etre paye par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
     *                  Si (vendeur et acheteur dans Communaute europeenne) et bien vendu autre que transport neuf alors la TVA par defaut=TVA du produit vendu. Fin de regle.
     *                  Sinon la TVA proposee par defaut=0. Fin de regle.
     *      @deprecated
     *    @return	void
     */
    function select_tva($htmlname='tauxtva', $selectedrate='', $societe_vendeuse='', $societe_acheteuse='', $idprod=0, $info_bits=0, $type='')
    {
    	print $this->load_tva($htmlname, $selectedrate, $societe_vendeuse, $societe_acheteuse, $idprod, $info_bits, $type);
    }


    /**
     *      Output an HTML select vat rate
     *
     *      @param      htmlname           Nom champ html
     *      @param      selectedrate       Forcage du taux tva pre-selectionne. Mettre '' pour aucun forcage.
     *      @param      societe_vendeuse   Objet societe vendeuse
     *      @param      societe_acheteuse  Objet societe acheteuse
     *      @param      idprod             Id product
     *      @param      info_bits          Miscellaneous information on line (1 for NPR)
     *      @param      type               ''=Unknown, 0=Product, 1=Service (Used if idprod not defined)
     *                  Si vendeur non assujeti a TVA, TVA par defaut=0. Fin de regle.
     *                  Si le (pays vendeur = pays acheteur) alors la TVA par defaut=TVA du produit vendu. Fin de regle.
     *                  Si (vendeur et acheteur dans Communaute europeenne) et bien vendu = moyen de transports neuf (auto, bateau, avion), TVA par defaut=0 (La TVA doit etre paye par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
     *                  Si (vendeur et acheteur dans Communaute europeenne) et bien vendu autre que transport neuf alors la TVA par defaut=TVA du produit vendu. Fin de regle.
     *                  Sinon la TVA proposee par defaut=0. Fin de regle.
     *    @return	void
     */
    function load_tva($htmlname='tauxtva', $selectedrate='', $societe_vendeuse='', $societe_acheteuse='', $idprod=0, $info_bits=0, $type='')
    {
        global $langs,$conf,$mysoc;

        $return='';
        $txtva=array();
        $libtva=array();
        $nprtva=array();

        // Define defaultnpr and defaultttx
        $defaultnpr=($info_bits & 0x01);
        $defaultnpr=(preg_match('/\*/',$selectedrate) ? 1 : $defaultnpr);
        $defaulttx=str_replace('*','',$selectedrate);

        // Check parameters
        if (is_object($societe_vendeuse) && ! $societe_vendeuse->country_code)
        {
            if ($societe_vendeuse->id == $mysoc->id)
            {
                $return.= '<font class="error">'.$langs->trans("ErrorYourCountryIsNotDefined").'</div>';
            }
            else
            {
                $return.= '<font class="error">'.$langs->trans("ErrorSupplierCountryIsNotDefined").'</div>';
            }
            return $return;
        }

        //var_dump($societe_acheteuse);
        //print "name=$name, selectedrate=$selectedrate, seller=".$societe_vendeuse->country_code." buyer=".$societe_acheteuse->country_code." buyer is company=".$societe_acheteuse->isACompany()." idprod=$idprod, info_bits=$info_bits type=$type";
        //exit;

        // Get list of all VAT rates to show
        // First we defined code_pays to use to find list
        if (is_object($societe_vendeuse))
        {
            $code_pays="'".$societe_vendeuse->country_code."'";
        }
        else
        {
            $code_pays="'".$mysoc->country_code."'";   // Pour compatibilite ascendente
        }
        if (! empty($conf->global->SERVICE_ARE_ECOMMERCE_200238EC))    // If option to have vat for end customer for services is on
        {
            if (! $societe_vendeuse->isInEEC() && $societe_acheteuse->isInEEC() && ! $societe_acheteuse->isACompany())
            {
                // We also add the buyer
                if (is_numeric($type))
                {
                    if ($type == 1) // We know product is a service
                    {
                        $code_pays.=",'".$societe_acheteuse->country_code."'";
                    }
                }
                else if (! $idprod)  // We don't know type of product
                {
                    $code_pays.=",'".$societe_acheteuse->country_code."'";
                }
                else
                {
                    $prodstatic=new Product($this->db);
                    $prodstatic->fetch($idprod);
                    if ($prodstatic->type == 1)   // We know product is a service
                    {
                        $code_pays.=",'".$societe_acheteuse->country_code."'";
                    }
                }
            }
        }
        // Now we get list
        $sql  = "SELECT DISTINCT t.taux, t.recuperableonly";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_pays as p";
        $sql.= " WHERE t.fk_pays = p.rowid";
        $sql.= " AND t.active = 1";
        $sql.= " AND p.code in (".$code_pays.")";
        $sql.= " ORDER BY t.taux ASC, t.recuperableonly ASC";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            if ($num)
            {
                for ($i = 0; $i < $num; $i++)
                {
                    $obj = $this->db->fetch_object($resql);
                    $txtva[$i]  = $obj->taux;
                    $libtva[$i] = $obj->taux.'%';
                    $nprtva[$i] = $obj->recuperableonly;
                }
            }
            else
            {
                $return.= '<font class="error">'.$langs->trans("ErrorNoVATRateDefinedForSellerCountry",$code_pays).'</font>';
            }
        }
        else
        {
            $return.= '<font class="error">'.$this->db->error().'</font>';
        }

        // Definition du taux a pre-selectionner (si defaulttx non force et donc vaut -1 ou '')
        if ($defaulttx < 0 || dol_strlen($defaulttx) == 0)
        {
            $defaulttx=get_default_tva($societe_vendeuse,$societe_acheteuse,$idprod);
            $defaultnpr=get_default_npr($societe_vendeuse,$societe_acheteuse,$idprod);
        }

        // Si taux par defaut n'a pu etre determine, on prend dernier de la liste.
        // Comme ils sont tries par ordre croissant, dernier = plus eleve = taux courant
        if ($defaulttx < 0 || dol_strlen($defaulttx) == 0)
        {
            $defaulttx = $txtva[count($txtva)-1];
        }

        $nbdetaux = count($txtva);
        if ($nbdetaux > 0)
        {
            $return.= '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'">';

            for ($i = 0 ; $i < $nbdetaux ; $i++)
            {
                //print "xxxxx".$txtva[$i]."-".$nprtva[$i];
                $return.= '<option value="'.$txtva[$i];
                $return.= $nprtva[$i] ? '*': '';
                $return.= '"';
                if ($txtva[$i] == $defaulttx && $nprtva[$i] == $defaultnpr)
                {
                    $return.= ' selected="selected"';
                }
                $return.= '>'.vatrate($libtva[$i]);
                $return.= $nprtva[$i] ? ' *': '';
                $return.= '</option>';

                $this->tva_taux_value[$i] = $txtva[$i];
                $this->tva_taux_libelle[$i] = $libtva[$i];
                $this->tva_taux_npr[$i] = $nprtva[$i];
            }
            $return.= '</select>';
        }

        return $return;
    }


    /**
     *		Show a HTML widget to input a date or combo list for day, month, years and optionnaly hours and minutes
     *      Fields are preselected with :
     *            	- set_time date (Local PHP server timestamps or date format YYYY-MM-DD or YYYY-MM-DD HH:MM)
     *            	- local date of PHP server if set_time is ''
     *            	- Empty (fields empty) if set_time is -1 (in this case, parameter empty must also have value 1)
     *
     *		@param	set_time 		Pre-selected date (must be a local PHP server timestamp)
     *		@param	prefix			Prefix for fields name
     *		@param	h				1=Show also hours
     *		@param	m				1=Show also minutes
     *		@param	empty			0=Fields required, 1=Empty input is allowed
     *		@param	form_name 		Form name. Used by popup dates.
     *		@param	d				1=Show days, month, years
     * 		@param	addnowbutton	Add a button "Now"
     * 		@param	nooutput		Do not output html string but return it
     * 		@param 	disabled		Disable input fields
     *      @param  fullday         When a checkbox with this html name is on, hour and day are set with 00:00 or 23:59
     * 		@return	nothing or string if nooutput is 1
     */
    function select_date($set_time='', $prefix='re', $h=0, $m=0, $empty=0, $form_name="", $d=1, $addnowbutton=0, $nooutput=0, $disabled=0, $fullday='')
    {
        global $conf,$langs;

        $retstring='';

        if($prefix=='') $prefix='re';
        if($h == '') $h=0;
        if($m == '') $m=0;
        if($empty == '') $empty=0;

        if (! $set_time && $empty == 0) $set_time = dol_now('tzuser');

        // Analysis of the pre-selection date
        if (preg_match('/^([0-9]+)\-([0-9]+)\-([0-9]+)\s?([0-9]+)?:?([0-9]+)?/',$set_time,$reg))
        {
            // Date format 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS'
            $syear = $reg[1];
            $smonth = $reg[2];
            $sday = $reg[3];
            $shour = $reg[4];
            $smin = $reg[5];
        }
        elseif (strval($set_time) != '' && $set_time != -1)
        {
            // set_time est un timestamps (0 possible)
            $syear = dol_print_date($set_time, "%Y");
            $smonth = dol_print_date($set_time, "%m");
            $sday = dol_print_date($set_time, "%d");
            $shour = dol_print_date($set_time, "%H");
            $smin = dol_print_date($set_time, "%M");
        }
        else
        {
            // Date est '' ou vaut -1
            $syear = '';
            $smonth = '';
            $sday = '';
            $shour = '';
            $smin = '';
        }

        if ($d)
        {
            // Show date with popup
            if ($conf->use_javascript_ajax && (empty($conf->global->MAIN_POPUP_CALENDAR) || $conf->global->MAIN_POPUP_CALENDAR != "none"))
            {
                //print "e".$set_time." t ".$conf->format_date_short;
                if (strval($set_time) != '' && $set_time != -1)
                {
                    //$formated_date=dol_print_date($set_time,$conf->format_date_short);
                    $formated_date=dol_print_date($set_time,$langs->trans("FormatDateShort"));  // FormatDateShort for dol_print_date/FormatDateShortJava that is same for javascript
                }

                // Calendrier popup version eldy
                if (empty($conf->global->MAIN_POPUP_CALENDAR) || $conf->global->MAIN_POPUP_CALENDAR == "eldy")
                {
                    // Zone de saisie manuelle de la date
                    $retstring.='<input id="'.$prefix.'" name="'.$prefix.'" type="text" size="9" maxlength="11" value="'.$formated_date.'"';
                    $retstring.=($disabled?' disabled="disabled"':'');
                    $retstring.=' onChange="dpChangeDay(\''.$prefix.'\',\''.$langs->trans("FormatDateShortJava").'\'); "';  // FormatDateShort for dol_print_date/FormatDateShortJava that is same for javascript
                    $retstring.='>';

                    // Icone calendrier
                    if (! $disabled)
                    {
                        $retstring.='<button id="'.$prefix.'Button" type="button" class="dpInvisibleButtons"';
                        $base=DOL_URL_ROOT.'/core/';
                        $retstring.=' onClick="showDP(\''.$base.'\',\''.$prefix.'\',\''.$langs->trans("FormatDateShortJava").'\',\''.$langs->defaultlang.'\');">'.img_object($langs->trans("SelectDate"),'calendarday','class="datecallink"').'</button>';
                    }
                    else $retstring.='<button id="'.$prefix.'Button" type="button" class="dpInvisibleButtons">'.img_object($langs->trans("Disabled"),'calendarday','class="datecallink"').'</button>';

                    $retstring.='<input type="hidden" id="'.$prefix.'day"   name="'.$prefix.'day"   value="'.$sday.'">'."\n";
                    $retstring.='<input type="hidden" id="'.$prefix.'month" name="'.$prefix.'month" value="'.$smonth.'">'."\n";
                    $retstring.='<input type="hidden" id="'.$prefix.'year"  name="'.$prefix.'year"  value="'.$syear.'">'."\n";
                }
                else
                {
                    print "Bad value of calendar";
                }
            }

            // Show date with combo selects
            if (empty($conf->use_javascript_ajax) || $conf->global->MAIN_POPUP_CALENDAR == "none")
            {
                // Day
                $retstring.='<select'.($disabled?' disabled="disabled"':'').' class="flat" name="'.$prefix.'day">';

                if ($empty || $set_time == -1)
                {
                    $retstring.='<option value="0" selected="selected">&nbsp;</option>';
                }

                for ($day = 1 ; $day <= 31; $day++)
                {
                    if ($day == $sday)
                    {
                        $retstring.="<option value=\"$day\" selected=\"selected\">$day";
                    }
                    else
                    {
                        $retstring.="<option value=\"$day\">$day";
                    }
                    $retstring.="</option>";
                }

                $retstring.="</select>";

                $retstring.='<select'.($disabled?' disabled="disabled"':'').' class="flat" name="'.$prefix.'month">';
                if ($empty || $set_time == -1)
                {
                    $retstring.='<option value="0" selected="selected">&nbsp;</option>';
                }

                // Month
                for ($month = 1 ; $month <= 12 ; $month++)
                {
                    $retstring.='<option value="'.$month.'"'.($month == $smonth?' selected="selected"':'').'>';
                    $retstring.=dol_print_date(mktime(12,0,0,$month,1,2000),"%b");
                    $retstring.="</option>";
                }
                $retstring.="</select>";

                // Year
                if ($empty || $set_time == -1)
                {
                    $retstring.='<input'.($disabled?' disabled="disabled"':'').' class="flat" type="text" size="3" maxlength="4" name="'.$prefix.'year" value="'.$syear.'">';
                }
                else
                {
                    $retstring.='<select'.($disabled?' disabled="disabled"':'').' class="flat" name="'.$prefix.'year">';

                    for ($year = $syear - 5; $year < $syear + 10 ; $year++)
                    {
                        if ($year == $syear)
                        {
                            $retstring.="<option value=\"$year\" selected=\"true\">".$year;
                        }
                        else
                        {
                            $retstring.="<option value=\"$year\">".$year;
                        }
                        $retstring.="</option>";
                    }
                    $retstring.="</select>\n";
                }
            }
        }

        if ($d && $h) $retstring.='&nbsp;';

        if ($h)
        {
            // Show hour
        	$retstring.='<select'.($disabled?' disabled="disabled"':'').' class="flat '.($fullday?$fullday.'hour':'').'" name="'.$prefix.'hour">';
            if ($empty) $retstring.='<option value="-1">&nbsp;</option>';
            for ($hour = 0; $hour < 24; $hour++)
            {
                if (dol_strlen($hour) < 2)
                {
                    $hour = "0" . $hour;
                }
                if ($hour == $shour)
                {
                    $retstring.="<option value=\"$hour\" selected=\"true\">$hour</option>";
                }
                else
                {
                    $retstring.="<option value=\"$hour\">$hour</option>";
                }
            }
            $retstring.="</select>";
            $retstring.="H\n";
        }

        if ($m)
        {
            // Show minutes
            $retstring.='<select'.($disabled?' disabled="disabled"':'').' class="flat '.($fullday?$fullday.'min':'').'" name="'.$prefix.'min">';
            if ($empty) $retstring.='<option value="-1">&nbsp;</option>';
            for ($min = 0; $min < 60 ; $min++)
            {
                if (dol_strlen($min) < 2)
                {
                    $min = "0" . $min;
                }
                if ($min == $smin)
                {
                    $retstring.="<option value=\"$min\" selected=\"true\">$min</option>";
                }
                else
                {
                    $retstring.="<option value=\"$min\">$min</option>";
                }
            }
            $retstring.="</select>";
            $retstring.="M\n";
        }

        // Add a "Now" button
        if ($conf->use_javascript_ajax && $addnowbutton)
        {
            // Script which will be inserted in the OnClick of the "Now" button
            $reset_scripts = "";

            // Generate the date part, depending on the use or not of the javascript calendar
            if (empty($conf->global->MAIN_POPUP_CALENDAR) || $conf->global->MAIN_POPUP_CALENDAR == "eldy")
            {
                $base=DOL_URL_ROOT.'/core/lib/';
                $reset_scripts .= 'resetDP(\''.$base.'\',\''.$prefix.'\',\''.$langs->trans("FormatDateShortJava").'\',\''.$langs->defaultlang.'\');';
            }
            else
            {
                $reset_scripts .= 'this.form.elements[\''.$prefix.'day\'].value=formatDate(new Date(), \'d\'); ';
                $reset_scripts .= 'this.form.elements[\''.$prefix.'month\'].value=formatDate(new Date(), \'M\'); ';
                $reset_scripts .= 'this.form.elements[\''.$prefix.'year\'].value=formatDate(new Date(), \'yyyy\'); ';
            }
            // Generate the hour part
            if ($h)
            {
                if ($fullday) $reset_scripts .= " if (jQuery('#fullday:checked').val() == null) {";
                $reset_scripts .= 'this.form.elements[\''.$prefix.'hour\'].value=formatDate(new Date(), \'HH\'); ';
                if ($fullday) $reset_scripts .= ' } ';
            }
            // Generate the minute part
            if ($m)
            {
                if ($fullday) $reset_scripts .= " if (jQuery('#fullday:checked').val() == null) {";
                $reset_scripts .= 'this.form.elements[\''.$prefix.'min\'].value=formatDate(new Date(), \'mm\'); ';
                if ($fullday) $reset_scripts .= ' } ';
            }
            // If reset_scripts is not empty, print the button with the reset_scripts in OnClick
            if ($reset_scripts)
            {
                $retstring.='<button class="dpInvisibleButtons datenowlink" id="'.$prefix.'ButtonNow" type="button" name="_useless" value="Now" onClick="'.$reset_scripts.'">';
                $retstring.=$langs->trans("Now");
                $retstring.='</button> ';
            }
        }

        if (! empty($nooutput)) return $retstring;

        print $retstring;
        return;
    }

    /**
     *	Function to show a form to select a duration on a page
     *
     *	@param	string	$prefix   	prefix
     *	@param  int		$iSecond  	Default preselected duration (number of seconds)
     * 	@param	int		$disabled	Disable the combo box
     *  @return	void
     */
    function select_duration($prefix,$iSecond='',$disabled=0)
    {
        if ($iSecond)
        {
            require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");

            $hourSelected = convertSecondToTime($iSecond,'hour');
            $minSelected = convertSecondToTime($iSecond,'min');
        }

        print '<select class="flat" name="'.$prefix.'hour"'.($disabled?' disabled="disabled"':'').'>';
        for ($hour = 0; $hour < 24; $hour++)
        {
            print '<option value="'.$hour.'"';
            if ($hourSelected == $hour)
            {
                print " selected=\"true\"";
            }
            print ">".$hour."</option>";
        }
        print "</select>";
        print "H &nbsp;";
        print '<select class="flat" name="'.$prefix.'min"'.($disabled?' disabled="disabled"':'').'>';
        for ($min = 0; $min <= 55; $min=$min+5)
        {
            print '<option value="'.$min.'"';
            if ($minSelected == $min) print ' selected="selected"';
            print '>'.$min.'</option>';
        }
        print "</select>";
        print "M&nbsp;";
    }


    /**
     *	Show a select form from an array
     *
     *	@param	htmlname        Name of html select area
     *	@param	array           Array with key+value
     *	@param	id              Preselected key
     *	@param	show_empty      1 si il faut ajouter une valeur vide dans la liste, 0 sinon
     *	@param	key_in_label    1 pour afficher la key dans la valeur "[key] value"
     *	@param	value_as_key    1 to use value as key
     *	@param  option          Valeur de l'option en fonction du type choisi
     *	@param  translate       Translate and encode value
     * 	@param	maxlen			Length maximum for labels
     * 	@param	disabled		Html select box is disabled
     * 	@return	string			HTML select string
     */
    function selectarray($htmlname, $array, $id='', $show_empty=0, $key_in_label=0, $value_as_key=0, $option='', $translate=0, $maxlen=0, $disabled=0)
    {
        global $langs;

        $out='<select id="'.$htmlname.'" '.($disabled?'disabled="disabled" ':'').'class="flat" name="'.$htmlname.'" '.($option != ''?$option:'').'>';

        if ($show_empty)
        {
            $out.='<option value="-1"'.($id==-1?' selected="selected"':'').'>&nbsp;</option>'."\n";
        }

        if (is_array($array))
        {
            foreach($array as $key => $value)
            {
                $out.='<option value="'.($value_as_key?$value:$key).'"';
                // Si il faut pre-selectionner une valeur
                if ($id != '' && ($id == $key || $id == $value))
                {
                    $out.=' selected="selected"';
                }

                $out.='>';

                if ($key_in_label)
                {
                    $newval=($translate?$langs->trans($value):$value);
                    $selectOptionValue = dol_htmlentitiesbr($key.' - '.($maxlen?dol_trunc($newval,$maxlen):$newval));
                    $out.=$selectOptionValue;
                }
                else
                {
                    $newval=($translate?$langs->trans($value):$value);
                    $selectOptionValue = dol_htmlentitiesbr($maxlen?dol_trunc($newval,$maxlen):$newval);
                    if ($value == '' || $value == '-') { $selectOptionValue='&nbsp;'; }
                    $out.=$selectOptionValue;
                }
                $out.="</option>\n";
            }
        }

        $out.="</select>";
        return $out;
    }

    /**
     *	Show a select form from an array
     *
     * 	@deprecated				Use selectarray instead
     *  @return	void
     */
    function select_array($htmlname, $array, $id='', $show_empty=0, $key_in_label=0, $value_as_key=0, $option='', $translate=0, $maxlen=0)
    {
        print $this->selectarray($htmlname, $array, $id, $show_empty, $key_in_label, $value_as_key, $option, $translate, $maxlen);
    }


    /**
     *	Return an html string with a select combo box to choose yes or no
     *
     *	@param	string	$name			Name of html select field
     *	@param	string	$value			Pre-selected value
     *	@param	int		$option			0 return yes/no, 1 return 1/0
     *	@param	bool	$disabled		true or false
     *	@return	mixed					See option
     */
    function selectyesno($htmlname,$value='',$option=0,$disabled=false)
    {
        global $langs;

        $yes="yes"; $no="no";

        if ($option)
        {
            $yes="1";
            $no="0";
        }

        $disabled = ($disabled ? ' disabled="disabled"' : '');

        $resultyesno = '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'"'.$disabled.'>'."\n";
        if (("$value" == 'yes') || ($value == 1))
        {
            $resultyesno .= '<option value="'.$yes.'" selected="selected">'.$langs->trans("Yes").'</option>'."\n";
            $resultyesno .= '<option value="'.$no.'">'.$langs->trans("No").'</option>'."\n";
        }
        else
        {
            $resultyesno .= '<option value="'.$yes.'">'.$langs->trans("Yes").'</option>'."\n";
            $resultyesno .= '<option value="'.$no.'" selected="selected">'.$langs->trans("No").'</option>'."\n";
        }
        $resultyesno .= '</select>'."\n";
        return $resultyesno;
    }



    /**
     *    Return list of export templates
     *
     *    @param      selected          Id modele pre-selectionne
     *    @param      htmlname          Nom de la zone select
     *    @param      type              Type des modeles recherches
     *    @param      useempty          Affiche valeur vide dans liste
     *  @return	void
     */
    function select_export_model($selected='',$htmlname='exportmodelid',$type='',$useempty=0)
    {

        $sql = "SELECT rowid, label";
        $sql.= " FROM ".MAIN_DB_PREFIX."export_model";
        $sql.= " WHERE type = '".$type."'";
        $sql.= " ORDER BY rowid";
        $result = $this->db->query($sql);
        if ($result)
        {
            print '<select class="flat" name="'.$htmlname.'">';
            if ($useempty)
            {
                print '<option value="-1">&nbsp;</option>';
            }

            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                if ($selected == $obj->rowid)
                {
                    print '<option value="'.$obj->rowid.'" selected="selected">';
                }
                else
                {
                    print '<option value="'.$obj->rowid.'">';
                }
                print $obj->label;
                print '</option>';
                $i++;
            }
            print "</select>";
        }
        else {
            dol_print_error($this->db);
        }
    }

    /**
     *    Return a HTML area with the reference of object and a navigation bar for a business object
     *    To add a particular filter on select, you must set $object->next_prev_filter to SQL criteria.
     *
     *    @param	Object	$object			Object to show
     *    @param    int		$paramid   		Name of parameter to use to name the id into the URL link
     *    @param    string	$morehtml  		More html content to output just before the nav bar
     *    @param	int		$shownav	  	Show Condition (navigation is shown if value is 1)
     *    @param    int		$fieldid   		Nom du champ en base a utiliser pour select next et previous
     *    @param    string	$fieldref   	Nom du champ objet ref (object->ref) a utiliser pour select next et previous
     *    @param    string	$morehtmlref  	Code html supplementaire a afficher apres ref
     *    @param    string	$moreparam  	More param to add in nav link url.
     * 	  @return   tring    				Portion HTML avec ref + boutons nav
     */
    function showrefnav($object,$paramid,$morehtml='',$shownav=1,$fieldid='rowid',$fieldref='ref',$morehtmlref='',$moreparam='')
    {
        $ret='';

        //print "paramid=$paramid,morehtml=$morehtml,shownav=$shownav,$fieldid,$fieldref,$morehtmlref,$moreparam";
        $object->load_previous_next_ref((isset($object->next_prev_filter)?$object->next_prev_filter:''),$fieldid);
        $previous_ref = $object->ref_previous?'<a href="'.$_SERVER["PHP_SELF"].'?'.$paramid.'='.urlencode($object->ref_previous).$moreparam.'">'.img_previous().'</a>':'';
        $next_ref     = $object->ref_next?'<a href="'.$_SERVER["PHP_SELF"].'?'.$paramid.'='.urlencode($object->ref_next).$moreparam.'">'.img_next().'</a>':'';

        //print "xx".$previous_ref."x".$next_ref;
        if ($previous_ref || $next_ref || $morehtml) {
            $ret.='<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
        }

        $ret.=$object->$fieldref;
        if ($morehtmlref)
        {
            $ret.=' '.$morehtmlref;
        }

        if ($morehtml)
        {
            $ret.='</td><td class="nobordernopadding" align="right">'.$morehtml;
        }
        if ($shownav && ($previous_ref || $next_ref))
        {
            $ret.='</td><td class="nobordernopadding" align="center" width="20">'.$previous_ref.'</td>';
            $ret.='<td class="nobordernopadding" align="center" width="20">'.$next_ref;
        }
        if ($previous_ref || $next_ref || $morehtml)
        {
            $ret.='</td></tr></table>';
        }
        return $ret;
    }


    /**
    *    	Return HTML code to output a barcode
    *
    *     	@param	Object	&$object		Object containing data to retrieve file name
    * 		@param	int		$width			Width of photo
    * 	  	@return string    				HTML code to output barcode
    */
    function showbarcode(&$object,$width=100)
    {
        global $conf;

        if (empty($object->barcode)) return '';

        // Complete object if not complete
        if (empty($object->barcode_type_code) || empty($object->barcode_type_coder))
        {
            $object->fetch_barcode();
        }

        // Barcode image
        $url=DOL_URL_ROOT.'/viewimage.php?modulepart=barcode&generator='.urlencode($object->barcode_type_coder).'&code='.urlencode($object->barcode).'&encoding='.urlencode($object->barcode_type_code);
        $out ='<!-- url barcode = '.$url.' -->';
        $out.='<img src="'.$url.'">';
        return $out;
    }

    /**
     *    	Return HTML code to output a photo
     *
     *    	@param	string		$modulepart		Key to define module concerned ('societe', 'userphoto', 'memberphoto')
     *     	@param  Object		$object			Object containing data to retrieve file name
     * 		@param	int			$width			Width of photo
     * 	  	@return string    					HTML code to output photo
     */
    function showphoto($modulepart,$object,$width=100)
    {
        global $conf;

        $ret='';$dir='';$file='';$altfile='';$email='';

        if ($modulepart=='societe')
        {
            $dir=$conf->societe->dir_output;
            $smallfile=$object->logo;
            $smallfile=preg_replace('/(\.png|\.gif|\.jpg|\.jpeg|\.bmp)/i','_small\\1',$smallfile);
            if ($object->logo) $file=$object->id.'/logos/thumbs/'.$smallfile;
        }
        else if ($modulepart=='userphoto')
        {
            $dir=$conf->user->dir_output;
            if ($object->photo) $file=get_exdir($object->id,2).$object->photo;
            if (! empty($conf->global->MAIN_OLD_IMAGE_LINKS)) $altfile=$object->id.".jpg";	// For backward compatibility
            $email=$object->email;
        }
        else if ($modulepart=='memberphoto')
        {
            $dir=$conf->adherent->dir_output;
            if ($object->photo) $file=get_exdir($object->id,2).'photos/'.$object->photo;
            if (! empty($conf->global->MAIN_OLD_IMAGE_LINKS)) $altfile=$object->id.".jpg";	// For backward compatibility
            $email=$object->email;
        }

        if ($dir)
        {
            $cache='0';
            if ($file && file_exists($dir."/".$file))
            {
                // TODO Link to large image
                $ret.='<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&file='.urlencode($file).'&cache='.$cache.'">';
                $ret.='<img alt="Photo" id="photologo'.(preg_replace('/[^a-z]/i','_',$file)).'" class="photologo" border="0" width="'.$width.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&file='.urlencode($file).'&cache='.$cache.'">';
                $ret.='</a>';
            }
            else if ($altfile && file_exists($dir."/".$altfile))
            {
                $ret.='<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&file='.urlencode($file).'&cache='.$cache.'">';
                $ret.='<img alt="Photo alt" id="photologo'.(preg_replace('/[^a-z]/i','_',$file)).'" class="photologo" border="0" width="'.$width.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&file='.urlencode($altfile).'&cache='.$cache.'">';
                $ret.='</a>';
            }
            else
            {
                if ($conf->gravatar->enabled && $email)
                {
                    global $dolibarr_main_url_root;
                    $ret.='<!-- Put link to gravatar -->';
                    $ret.='<img alt="Photo found on Gravatar" title="Photo Gravatar.com - email '.$email.'" border="0" width="'.$width.'" src="http://www.gravatar.com/avatar/'.dol_hash($email).'?s='.$width.'&d='.urlencode( dol_buildpath('/theme/common/nophoto.jpg',2) ).'">';
                }
                else
                {
                    $ret.='<img alt="No photo" border="0" width="'.$width.'" src="'.DOL_URL_ROOT.'/theme/common/nophoto.jpg">';
                }
            }
        }
        else dol_print_error('','Call of showphoto with wrong parameters');

        /* Disabled. lightbox seems to not work. I don't know why.
        $ret.="\n<script type=\"text/javascript\">
              jQuery(function() {
                     jQuery('.photologo').lightBox();
              });
              </script>\n";

        $ret.="\n<script type=\"text/javascript\">
              jQuery(function() {
                     jQuery('.photologo').lightBox({
                        overlayBgColor: '#FFF',
                        overlayOpacity: 0.6,
                        imageLoading: '".DOL_URL_ROOT."/includes/jquery/plugins/lightbox/images/lightbox-ico-loading.gif',
                        imageBtnClose: '".DOL_URL_ROOT."/includes/jquery/plugins/lightbox/images/lightbox-btn-close.gif',
                        imageBtnPrev: '".DOL_URL_ROOT."/includes/jquery/plugins/lightbox/images/lightbox-btn-prev.gif',
                        imageBtnNext: '".DOL_URL_ROOT."/includes/jquery/plugins/lightbox/images/lightbox-btn-next.gif',
                        containerResizeSpeed: 350,
                        txtImage: 'Imagem',
                        txtOf: 'de'
                     });
              });
              </script>\n";
        */

        return $ret;
    }

    /**
     *	Return select list of groups
     *
     *  @param	string	$selected        Id group preselected
     *  @param  string	$htmlname        Field name in form
     *  @param  int		$show_empty      0=liste sans valeur nulle, 1=ajoute valeur inconnue
     *  @param  string	$exclude         Array list of groups id to exclude
     * 	@param	int		$disabled		If select list must be disabled
     *  @param  string	$include         Array list of groups id to include
     * 	@param	int		$enableonly		Array list of groups id to be enabled. All other must be disabled
     * 	@param	int		$force_entity	Possibility to force entity
     *  @return	void
     */
    function select_dolgroups($selected='',$htmlname='groupid',$show_empty=0,$exclude='',$disabled=0,$include='',$enableonly='',$force_entity='')
    {
        global $conf,$user,$langs;

        // Permettre l'exclusion de groupes
        if (is_array($exclude))	$excludeGroups = implode("','",$exclude);
        // Permettre l'inclusion de groupes
        if (is_array($include))	$includeGroups = implode("','",$include);

        $out='';

        // On recherche les groupes
        $sql = "SELECT ug.rowid, ug.nom ";
        if(! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
        {
        	$sql.= ", e.label";
        }
        $sql.= " FROM ".MAIN_DB_PREFIX."usergroup as ug ";
        if(! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entity as e on e.rowid=ug.entity";
            if ($force_entity) $sql.= " WHERE ug.entity IN (0,".$force_entity.")";
            else $sql.= " WHERE ug.entity IS NOT NULL";
        }
        else
        {
        	$sql.= " WHERE ug.entity IN (0,".$conf->entity.")";
        }
        if (is_array($exclude) && $excludeGroups) $sql.= " AND ug.rowid NOT IN ('".$excludeGroups."')";
        if (is_array($include) && $includeGroups) $sql.= " AND ug.rowid IN ('".$includeGroups."')";
		$sql.= " ORDER BY ug.nom ASC";

        dol_syslog("Form::select_dolgroups sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
            	$out.= '<select class="flat" name="'.$htmlname.'"'.($disabled?' disabled="disabled"':'').'>';
            	if ($show_empty) $out.= '<option value="-1"'.($selected==-1?' selected="selected"':'').'>&nbsp;</option>'."\n";

                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    $disableline=0;
                    if (is_array($enableonly) && count($enableonly) && ! in_array($obj->rowid,$enableonly)) $disableline=1;

                    $out.= '<option value="'.$obj->rowid.'"';
                    if ($disableline) $out.= ' disabled="disabled"';
                    if ((is_object($selected) && $selected->id == $obj->rowid) || (! is_object($selected) && $selected == $obj->rowid))
                    {
                        $out.= ' selected="selected"';
                    }
                    $out.= '>';

                    $out.= $obj->nom;
                    if(! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1)
                    {
                    	$out.= " (".$obj->label.")";
                    }

                    $out.= '</option>';
                    $i++;
                }
            }
            else
            {
            	$out.= '<select class="flat" name="'.$htmlname.'" disabled="disabled">';
            	$out.= '<option value="">'.$langs->trans("None").'</option>';
            }
            $out.= '</select>';
        }
        else
        {
            dol_print_error($this->db);
        }

        return $out;
    }

}

?>
