<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier        <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien Di Cintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2014 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2006      Marc Barilley/Ocebo   <marc@ocebo.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2007      Patrick Raguin        <patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010-2014 Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2011      Herve Prot            <herve.prot@symeos.com>
 * Copyright (C) 2012-2014 Marcos García         <marcosgdf@gmail.com>
 * Copyright (C) 2012      Cedric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014 Raphaël Doursenaud    <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014      Alexandre Spangaro    <alexandre.spangaro@gmail.com>
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
 *	\file       htdocs/core/class/html.form.class.php
 *  \ingroup    core
 *	\brief      File of class with all html predefined components
 */


/**
 *	Class to manage generation of HTML components
 *	Only common components must be here.
 */
class Form
{
    var $db;
    var $error;
    var $num;

    // Cache arrays
    var $cache_types_paiements=array();
    var $cache_conditions_paiements=array();
    var $cache_availability=array();
    var $cache_demand_reason=array();
    var $cache_types_fees=array();
    var $cache_vatrates=array();

    var $tva_taux_value;
    var $tva_taux_libelle;


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
     * Output key field for an editable field
     *
     * @param   string	$text			Text of label or key to translate
     * @param   string	$htmlname		Name of select field
     * @param   string	$preselected	Name of Value to show/edit (not used in this function)
     * @param	object	$object			Object
     * @param	boolean	$perm			Permission to allow button to edit parameter
     * @param	string	$typeofdata		Type of data ('string' by default, 'email', 'numeric:99', 'text' or 'textarea:rows:cols', 'day' or 'datepicker', 'ckeditor:dolibarr_zzz:width:height:savemethod:1:rows:cols', 'select;xxx[:class]'...)
     * @param	string	$moreparam		More param to add on a href URL
     * @return	string					HTML edit field
     */
    function editfieldkey($text, $htmlname, $preselected, $object, $perm, $typeofdata='string', $moreparam='')
    {
        global $conf,$langs;

        $ret='';

        // TODO change for compatibility
        if (! empty($conf->global->MAIN_USE_JQUERY_JEDITABLE) && ! preg_match('/^select;/',$typeofdata))
        {
            if (! empty($perm))
            {
                $tmp=explode(':',$typeofdata);
                $ret.= '<div class="editkey_'.$tmp[0].(! empty($tmp[1]) ? ' '.$tmp[1] : '').'" id="'.$htmlname.'">';
                $ret.= $langs->trans($text);
                $ret.= '</div>'."\n";
            }
            else
            {
                $ret.= $langs->trans($text);
            }
        }
        else
        {
            $ret.='<table class="nobordernopadding" width="100%"><tr><td class="nowrap">';
            $ret.=$langs->trans($text);
            $ret.='</td>';
            if (GETPOST('action') != 'edit'.$htmlname && $perm) $ret.='<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=edit'.$htmlname.'&amp;id='.$object->id.$moreparam.'">'.img_edit($langs->trans('Edit'),1).'</a></td>';
            $ret.='</tr></table>';
        }

        return $ret;
    }

    /**
     * Output val field for an editable field
     *
     * @param	string	$text			Text of label (not used in this function)
     * @param	string	$htmlname		Name of select field
     * @param	string	$value			Value to show/edit
     * @param	object	$object			Object
     * @param	boolean	$perm			Permission to allow button to edit parameter
     * @param	string	$typeofdata		Type of data ('string' by default, 'amount', 'email', 'numeric:99', 'text' or 'textarea:rows:cols', 'day' or 'datepicker', 'ckeditor:dolibarr_zzz:width:height:savemethod:toolbarstartexpanded:rows:cols', 'select:xxx'...)
     * @param	string	$editvalue		When in edit mode, use this value as $value instead of value (for example, you can provide here a formated price instead of value). Use '' to use same than $value
     * @param	object	$extObject		External object
     * @param	mixed	$custommsg		String or Array of custom messages : eg array('success' => 'MyMessage', 'error' => 'MyMessage')
     * @param	string	$moreparam		More param to add on a href URL
     * @return  string					HTML edit field
     */
    function editfieldval($text, $htmlname, $value, $object, $perm, $typeofdata='string', $editvalue='', $extObject=null, $custommsg=null, $moreparam='')
    {
        global $conf,$langs,$db;

        $ret='';

        // Check parameters
        if (empty($typeofdata)) return 'ErrorBadParameter';

        // When option to edit inline is activated
        if (! empty($conf->global->MAIN_USE_JQUERY_JEDITABLE) && ! preg_match('/^select;|datehourpicker/',$typeofdata)) // TODO add jquery timepicker
        {
            $ret.=$this->editInPlace($object, $value, $htmlname, $perm, $typeofdata, $editvalue, $extObject, $custommsg);
        }
        else
        {
            if (GETPOST('action') == 'edit'.$htmlname)
            {
                $ret.="\n";
                $ret.='<form method="post" action="'.$_SERVER["PHP_SELF"].($moreparam?'?'.$moreparam:'').'">';
                $ret.='<input type="hidden" name="action" value="set'.$htmlname.'">';
                $ret.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                $ret.='<input type="hidden" name="id" value="'.$object->id.'">';
                $ret.='<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
                $ret.='<tr><td>';
                if (preg_match('/^(string|email|numeric|amount)/',$typeofdata))
                {
                    $tmp=explode(':',$typeofdata);
                    $ret.='<input type="text" id="'.$htmlname.'" name="'.$htmlname.'" value="'.($editvalue?$editvalue:$value).'"'.($tmp[1]?' size="'.$tmp[1].'"':'').'>';
                }
                else if (preg_match('/^text/',$typeofdata) || preg_match('/^note/',$typeofdata))
                {
                    $tmp=explode(':',$typeofdata);
                    $ret.='<textarea id="'.$htmlname.'" name="'.$htmlname.'" wrap="soft" rows="'.($tmp[1]?$tmp[1]:'20').'" cols="'.($tmp[2]?$tmp[2]:'100').'">'.($editvalue?$editvalue:$value).'</textarea>';
                }
                else if ($typeofdata == 'day' || $typeofdata == 'datepicker')
                {
                    $ret.=$this->form_date($_SERVER['PHP_SELF'].'?id='.$object->id,$value,$htmlname);
                }
                else if ($typeofdata == 'datehourpicker')
                {
                	$ret.=$this->form_date($_SERVER['PHP_SELF'].'?id='.$object->id,$value,$htmlname,1,1);
                }
                else if (preg_match('/^select;/',$typeofdata))
                {
                     $arraydata=explode(',',preg_replace('/^select;/','',$typeofdata));
                     foreach($arraydata as $val)
                     {
                         $tmp=explode(':',$val);
                         $arraylist[$tmp[0]]=$tmp[1];
                     }
                     $ret.=$this->selectarray($htmlname,$arraylist,$value);
                }
                else if (preg_match('/^ckeditor/',$typeofdata))
                {
                    $tmp=explode(':',$typeofdata);
                    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
                    $doleditor=new DolEditor($htmlname, ($editvalue?$editvalue:$value), ($tmp[2]?$tmp[2]:''), ($tmp[3]?$tmp[3]:'100'), ($tmp[1]?$tmp[1]:'dolibarr_notes'), 'In', ($tmp[5]?$tmp[5]:0), true, true, ($tmp[6]?$tmp[6]:'20'), ($tmp[7]?$tmp[7]:'100'));
                    $ret.=$doleditor->Create(1);
                }
                $ret.='</td>';
                if ($typeofdata != 'day' && $typeofdata != 'datepicker' && $typeofdata != 'datehourpicker')
                {
                	$ret.='<td align="left">';
                	$ret.='<input type="submit" class="button" name="modify" value="'.$langs->trans("Modify").'">';
                	if (preg_match('/ckeditor|textarea/',$typeofdata)) $ret.='<br>'."\n";
                	$ret.='<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
                	$ret.='</td>';
                }
                $ret.='</tr></table>'."\n";
                $ret.='</form>'."\n";
            }
            else
			{
				if ($typeofdata == 'email')   $ret.=dol_print_email($value,0,0,0,0,1);
                elseif ($typeofdata == 'amount')   $ret.=($value != '' ? price($value,'',$langs,0,0,-1,$conf->currency) : '');
                elseif (preg_match('/^text/',$typeofdata) || preg_match('/^note/',$typeofdata))  $ret.=dol_htmlentitiesbr($value);
                elseif ($typeofdata == 'day' || $typeofdata == 'datepicker') $ret.=dol_print_date($value,'day');
                elseif ($typeofdata == 'datehourpicker') $ret.=dol_print_date($value,'dayhour');
                else if (preg_match('/^select;/',$typeofdata))
                {
                    $arraydata=explode(',',preg_replace('/^select;/','',$typeofdata));
                    foreach($arraydata as $val)
                    {
                        $tmp=explode(':',$val);
                        $arraylist[$tmp[0]]=$tmp[1];
                    }
                    $ret.=$arraylist[$value];
                }
                else if (preg_match('/^ckeditor/',$typeofdata))
                {
                    $tmpcontent=dol_htmlentitiesbr($value);
                    if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB))
                    {
                        $firstline=preg_replace('/<br>.*/','',$tmpcontent);
                        $firstline=preg_replace('/[\n\r].*/','',$firstline);
                        $tmpcontent=$firstline.((strlen($firstline) != strlen($tmpcontent))?'...':'');
                    }
                    $ret.=$tmpcontent;
                }
                else $ret.=$value;
            }
        }
        return $ret;
    }

    /**
     * Output edit in place form
     *
     * @param	object	$object			Object
     * @param	string	$value			Value to show/edit
     * @param	string	$htmlname		DIV ID (field name)
     * @param	int		$condition		Condition to edit
     * @param	string	$inputType		Type of input ('numeric', 'datepicker', 'textarea:rows:cols', 'ckeditor:dolibarr_zzz:width:height:?:1:rows:cols', 'select:xxx')
     * @param	string	$editvalue		When in edit mode, use this value as $value instead of value
     * @param	object	$extObject		External object
     * @param	mixed	$custommsg		String or Array of custom messages : eg array('success' => 'MyMessage', 'error' => 'MyMessage')
     * @return	string   		      	HTML edit in place
     */
    private function editInPlace($object, $value, $htmlname, $condition, $inputType='textarea', $editvalue=null, $extObject=null, $custommsg=null)
    {
        global $conf;

        $out='';

        // Check parameters
        if ($inputType == 'textarea') $value = dol_nl2br($value);
        else if (preg_match('/^numeric/',$inputType)) $value = price($value);
        else if ($inputType == 'datepicker') $value = dol_print_date($value, 'day');

        if ($condition)
        {
            $element		= false;
            $table_element	= false;
            $fk_element		= false;
            $loadmethod		= false;
            $savemethod		= false;
            $ext_element	= false;
            $button_only	= false;

            if (is_object($object))
            {
                $element = $object->element;
                $table_element = $object->table_element;
                $fk_element = $object->id;
            }

            if (is_object($extObject))
            {
                $ext_element = $extObject->element;
            }

            if (preg_match('/^(string|email|numeric)/',$inputType))
            {
                $tmp=explode(':',$inputType);
                $inputType=$tmp[0];
                if (! empty($tmp[1])) $inputOption=$tmp[1];
                if (! empty($tmp[2])) $savemethod=$tmp[2];
            }
            else if ((preg_match('/^datepicker/',$inputType)) || (preg_match('/^datehourpicker/',$inputType)))
            {
                $tmp=explode(':',$inputType);
                $inputType=$tmp[0];
                if (! empty($tmp[1])) $inputOption=$tmp[1];
                if (! empty($tmp[2])) $savemethod=$tmp[2];

                $out.= '<input id="timestamp" type="hidden"/>'."\n"; // Use for timestamp format
            }
            else if (preg_match('/^(select|autocomplete)/',$inputType))
            {
                $tmp=explode(':',$inputType);
                $inputType=$tmp[0]; $loadmethod=$tmp[1];
                if (! empty($tmp[2])) $savemethod=$tmp[2];
                if (! empty($tmp[3])) $button_only=true;
            }
            else if (preg_match('/^textarea/',$inputType))
            {
            	$tmp=explode(':',$inputType);
            	$inputType=$tmp[0];
            	$rows=(empty($tmp[1])?'8':$tmp[1]);
            	$cols=(empty($tmp[2])?'80':$tmp[2]);
            }
            else if (preg_match('/^ckeditor/',$inputType))
            {
                $tmp=explode(':',$inputType);
                $inputType=$tmp[0]; $toolbar=$tmp[1];
                if (! empty($tmp[2])) $width=$tmp[2];
                if (! empty($tmp[3])) $heigth=$tmp[3];
                if (! empty($tmp[4])) $savemethod=$tmp[4];

                if (! empty($conf->fckeditor->enabled))
                {
                    $out.= '<input id="ckeditor_toolbar" value="'.$toolbar.'" type="hidden"/>'."\n";
                }
                else
                {
                    $inputType = 'textarea';
                }
            }

            $out.= '<input id="element_'.$htmlname.'" value="'.$element.'" type="hidden"/>'."\n";
            $out.= '<input id="table_element_'.$htmlname.'" value="'.$table_element.'" type="hidden"/>'."\n";
            $out.= '<input id="fk_element_'.$htmlname.'" value="'.$fk_element.'" type="hidden"/>'."\n";
            $out.= '<input id="loadmethod_'.$htmlname.'" value="'.$loadmethod.'" type="hidden"/>'."\n";
            if (! empty($savemethod))	$out.= '<input id="savemethod_'.$htmlname.'" value="'.$savemethod.'" type="hidden"/>'."\n";
            if (! empty($ext_element))	$out.= '<input id="ext_element_'.$htmlname.'" value="'.$ext_element.'" type="hidden"/>'."\n";
            if (! empty($custommsg))
            {
            	if (is_array($custommsg))
            	{
            		if (!empty($custommsg['success']))
            			$out.= '<input id="successmsg_'.$htmlname.'" value="'.$custommsg['success'].'" type="hidden"/>'."\n";
            		if (!empty($custommsg['error']))
            			$out.= '<input id="errormsg_'.$htmlname.'" value="'.$custommsg['error'].'" type="hidden"/>'."\n";
            	}
            	else
            		$out.= '<input id="successmsg_'.$htmlname.'" value="'.$custommsg.'" type="hidden"/>'."\n";
            }
            if ($inputType == 'textarea') {
            	$out.= '<input id="textarea_'.$htmlname.'_rows" value="'.$rows.'" type="hidden"/>'."\n";
            	$out.= '<input id="textarea_'.$htmlname.'_cols" value="'.$cols.'" type="hidden"/>'."\n";
            }

            $out.= '<span id="viewval_'.$htmlname.'" class="viewval_'.$inputType.($button_only ? ' inactive' : ' active').'">'.$value.'</span>'."\n";
            $out.= '<span id="editval_'.$htmlname.'" class="editval_'.$inputType.($button_only ? ' inactive' : ' active').' hideobject">'.(! empty($editvalue) ? $editvalue : $value).'</span>'."\n";
        }
        else
        {
            $out = $value;
        }

        return $out;
    }

    /**
     *	Show a text and picto with tooltip on text or picto
     *
     *	@param	string		$text				Text to show
     *	@param	string		$htmltext			HTML content of tooltip. Must be HTML/UTF8 encoded.
     *	@param	int			$tooltipon			1=tooltip on text, 2=tooltip on image, 3=tooltip sur les 2
     *	@param	int			$direction			-1=image is before, 0=no image, 1=image is after
     *	@param	string		$img				Html code for image (use img_xxx() function to get it)
     *	@param	string		$extracss			Add a CSS style to td tags
     *	@param	int			$notabs				0=Include table and tr tags, 1=Do not include table and tr tags, 2=use div, 3=use span
     *	@param	string		$incbefore			Include code before the text
     *	@param	int			$noencodehtmltext	Do not encode into html entity the htmltext
     *	@return	string							Code html du tooltip (texte+picto)
     *	@see	Use function textwithpicto if you can.
     */
    function textwithtooltip($text, $htmltext, $tooltipon = 1, $direction = 0, $img = '', $extracss = '', $notabs = 0, $incbefore = '', $noencodehtmltext = 0)
    {
        global $conf;

        if ($incbefore) $text = $incbefore.$text;
        if (! $htmltext) return $text;

        $tag='td';
        if ($notabs == 2) $tag='div';
        if ($notabs == 3) $tag='span';
        // Sanitize tooltip
        $htmltext=str_replace("\\","\\\\",$htmltext);
        $htmltext=str_replace("\r","",$htmltext);
        $htmltext=str_replace("\n","",$htmltext);

        $htmltext=str_replace('"',"&quot;",$htmltext);
        if ($tooltipon == 2 || $tooltipon == 3) $paramfortooltipimg=' class="classfortooltip inline-block'.($extracss?' '.$extracss:'').'" title="'.($noencodehtmltext?$htmltext:dol_escape_htmltag($htmltext,1)).'"'; // Attribut to put on td img tag to store tooltip
        else $paramfortooltipimg =($extracss?' class="'.$extracss.'"':''); // Attribut to put on td text tag
        if ($tooltipon == 1 || $tooltipon == 3) $paramfortooltiptd=' class="classfortooltip inline-block'.($extracss?' '.$extracss:'').'" title="'.($noencodehtmltext?$htmltext:dol_escape_htmltag($htmltext,1)).'"'; // Attribut to put on td tag to store tooltip
        else $paramfortooltiptd =($extracss?' class="'.$extracss.'"':''); // Attribut to put on td text tag
        $s="";
        if (empty($notabs)) $s.='<table class="nobordernopadding" summary=""><tr>';
        elseif ($notabs == 2) $s.='<div class="inline-block nowrap">';
        if ($direction < 0) {
            $s.='<'.$tag.$paramfortooltipimg;
            if ($tag == 'td') {
                $s .= ' valign="top" width="14"';
            }
            $s.= '>'.$img.'</'.$tag.'>';
        }
        // Use another method to help avoid having a space in value in order to use this value with jquery
        // TODO add this in css
        //if ($text != '') $s.='<'.$tag.$paramfortooltiptd.'>'.(($direction < 0)?'&nbsp;':'').$text.(($direction > 0)?'&nbsp;':'').'</'.$tag.'>';
        $paramfortooltiptd.= (($direction < 0)?' class="inline-block" style="padding-left: 3px !important;"':'');
        $paramfortooltiptd.= (($direction > 0)?' class="inline-block" style="padding-right: 3px !important;"':'');
        if ((string) $text != '') $s.='<'.$tag.$paramfortooltiptd.'>'.$text.'</'.$tag.'>';
        if ($direction > 0) {
            $s.='<'.$tag.$paramfortooltipimg;
            if ($tag == 'td') {
                $s .= ' valign="top" width="14"';
            }
            $s.= '>'.$img.'</'.$tag.'>';
        }
        if (empty($notabs)) $s.='</tr></table>';
		elseif ($notabs == 2) $s.='</div>';

        return $s;
    }

    /**
     *	Show a text with a picto and a tooltip on picto
     *
     *	@param	string	$text				Text to show
     *	@param  string	$htmltext	     	Content of tooltip
     *	@param	int		$direction			1=Icon is after text, -1=Icon is before text, 0=no icon
     * 	@param	string	$type				Type of picto (info, help, warning, superadmin...)
     *  @param  string	$extracss           Add a CSS style to td tags
     *  @param  int		$noencodehtmltext   Do not encode into html entity the htmltext
     *  @param	int		$notabs				0=Include table and tr tags, 1=Do not include table and tr tags, 2=use div, 3=use span
     * 	@return	string						HTML code of text, picto, tooltip
     */
    function textwithpicto($text, $htmltext, $direction = 1, $type = 'help', $extracss = '', $noencodehtmltext = 0, $notabs = 0)
    {
        global $conf;

        $alt = '';

        //For backwards compatibility
        if ($type == '0') $type = 'info';
        elseif ($type == '1') $type = 'help';

        // If info or help with no javascript, show only text
        if (empty($conf->use_javascript_ajax))
        {
            if ($type == 'info' || $type == 'help')	return $text;
            else
            {
                $alt = $htmltext;
                $htmltext = '';
            }
        }

        // If info or help with smartphone, show only text (tooltip can't works)
        if (! empty($conf->dol_no_mouse_hover))
        {
            if ($type == 'info' || $type == 'help') return $text;
        }

        if ($type == 'info') $img = img_help(0, $alt);
        elseif ($type == 'help') $img = img_help(1, $alt);
        elseif ($type == 'superadmin') $img = img_picto($alt, 'redstar');
        elseif ($type == 'admin') $img = img_picto($alt, 'star');
        elseif ($type == 'warning') $img = img_warning($alt);

        return $this->textwithtooltip($text, $htmltext, 2, $direction, $img, $extracss, $notabs, '', $noencodehtmltext);
    }

    /**
     *  Return combo list of activated countries, into language of user
     *
     *  @param	string	$selected       Id or Code or Label of preselected country
     *  @param  string	$htmlname       Name of html select object
     *  @param  string	$htmloption     Options html on select object
     *  @param	integer	$maxlength		Max length for labels (0=no limit)
     *  @return string           		HTML string with select
     */
    function select_country($selected='',$htmlname='country_id',$htmloption='',$maxlength=0)
    {
        global $conf,$langs;

        $langs->load("dict");

        $out='';
        $countryArray=array();
		$favorite=array();
        $label=array();
		$atleastonefavorite=0;

        $sql = "SELECT rowid, code as code_iso, code_iso as code_iso3, label, favorite";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_country";
        $sql.= " WHERE active = 1";
        //$sql.= " ORDER BY code ASC";

        dol_syslog(get_class($this)."::select_country", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $out.= '<select id="select'.$htmlname.'" class="flat selectcountry" name="'.$htmlname.'" '.$htmloption.'>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;

                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    $countryArray[$i]['rowid'] 		= $obj->rowid;
                    $countryArray[$i]['code_iso'] 	= $obj->code_iso;
                    $countryArray[$i]['code_iso3'] 	= $obj->code_iso3;
                    $countryArray[$i]['label']		= ($obj->code_iso && $langs->transnoentitiesnoconv("Country".$obj->code_iso)!="Country".$obj->code_iso?$langs->transnoentitiesnoconv("Country".$obj->code_iso):($obj->label!='-'?$obj->label:''));
                    $countryArray[$i]['favorite']   = $obj->favorite;
                    $favorite[$i]					= $obj->favorite;
					$label[$i] = dol_string_unaccent($countryArray[$i]['label']);
                    $i++;
                }

                array_multisort($favorite, SORT_DESC, $label, SORT_ASC, $countryArray);

                foreach ($countryArray as $row)
                {
                	if ($row['favorite'] && $row['code_iso']) $atleastonefavorite++;
					if (empty($row['favorite']) && $atleastonefavorite)
					{
						$atleastonefavorite=0;
						$out.= '<option value="" disabled="disabled">----------------------</option>';
					}
                    if ($selected && $selected != '-1' && ($selected == $row['rowid'] || $selected == $row['code_iso'] || $selected == $row['code_iso3'] || $selected == $row['label']) )
                    {
                        $foundselected=true;
                        $out.= '<option value="'.$row['rowid'].'" selected="selected">';
                    }
                    else
					{
                        $out.= '<option value="'.$row['rowid'].'">';
                    }
                    $out.= dol_trunc($row['label'],$maxlength,'middle');
                    if ($row['code_iso']) $out.= ' ('.$row['code_iso'] . ')';
                    $out.= '</option>';
                }
            }
            $out.= '</select>';
        }
        else
		{
            dol_print_error($this->db);
        }

        return $out;
    }

	/**
     *  Return select list of incoterms
     *
     *  @param	string	$selected       		Id or Code of preselected incoterm
     *  @param	string	$location_incoterms     Value of input location
     *  @param	string	$page       			Defined the form action
     *  @param  string	$htmlname       		Name of html select object
     *  @param  string	$htmloption     		Options html on select object
     * 	@param	int		$forcecombo				Force to use combo box
     *  @param	array	$events					Event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     *  @return string           				HTML string with select and input
     */
    function select_incoterms($selected='', $location_incoterms='', $page='',$htmlname='incoterm_id',$htmloption='', $forcecombo=0, $events=array())
    {
        global $conf,$langs;

        $langs->load("dict");

        $out='';
        $incotermArray=array();

        $sql = "SELECT rowid, code";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_incoterms";
        $sql.= " WHERE active = 1";
        $sql.= " ORDER BY code ASC";

        dol_syslog(get_class($this)."::select_incoterm", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
        	if (!$forcecombo)
			{
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname, $events);
			}

			if (!empty($page))
			{
				$out .= '<form method="post" action="'.$page.'">';
	            $out .= '<input type="hidden" name="action" value="set_incoterms">';
	            $out .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			}

            $out.= '<select id="'.$htmlname.'" class="flat selectincoterm" name="'.$htmlname.'" '.$htmloption.'>';
			$out.= '<option value=""></option>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;

                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    $incotermArray[$i]['rowid'] = $obj->rowid;
                    $incotermArray[$i]['code'] = $obj->code;
                    $i++;
                }

                foreach ($incotermArray as $row)
                {
                    if ($selected && ($selected == $row['rowid'] || $selected == $row['code']))
                    {
                        $out.= '<option value="'.$row['rowid'].'" selected="selected">';
                    }
                    else
					{
                        $out.= '<option value="'.$row['rowid'].'">';
                    }

                    if ($row['code']) $out.= $row['code'];

					$out.= '</option>';
                }
            }
            $out.= '</select>';

			$out .= '<input id="location_incoterms" name="location_incoterms" size="14" value="'.$location_incoterms.'">';

			if (!empty($page))
			{
	            $out .= '<input type="submit" class="button" value="'.$langs->trans("Modify").'"></form>';
			}
        }
        else
		{
            dol_print_error($this->db);
        }

        return $out;
    }

    /**
     *	Return list of types of lines (product or service)
     * 	Example: 0=product, 1=service, 9=other (for external module)
     *
     *	@param  string	$selected       Preselected type
     *	@param  string	$htmlname       Name of field in html form
     * 	@param	int		$showempty		Add an empty field
     * 	@param	int		$hidetext		Do not show label 'Type' before combo box (used only if there is at least 2 choices to select)
     * 	@param	integer	$forceall		1=Force to show products and services in combo list, whatever are activated modules, 0=No force, -1=Force none (and set hidden field to 'service')
     *  @return	void
     */
    function select_type_of_lines($selected='',$htmlname='type',$showempty=0,$hidetext=0,$forceall=0)
    {
        global $db,$langs,$user,$conf;

        // If product & services are enabled or both disabled.
        if ($forceall > 0 || (empty($forceall) && ! empty($conf->product->enabled) && ! empty($conf->service->enabled))
        || (empty($forceall) && empty($conf->product->enabled) && empty($conf->service->enabled)) )
        {
            if (empty($hidetext)) print $langs->trans("Type").': ';
            print '<select class="flat" id="select_'.$htmlname.'" name="'.$htmlname.'">';
            if ($showempty)
            {
                print '<option value="-1"';
                if ($selected == -1) print ' selected="selected"';
                print '>&nbsp;</option>';
            }

            print '<option value="0"';
            if (0 == $selected) print ' selected="selected"';
            print '>'.$langs->trans("Product");

            print '<option value="1"';
            if (1 == $selected) print ' selected="selected"';
            print '>'.$langs->trans("Service");

            print '</select>';
            //if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
        }
        if (empty($forceall) && empty($conf->product->enabled) && ! empty($conf->service->enabled))
        {
        	print $langs->trans("Service");
            print '<input type="hidden" name="'.$htmlname.'" value="1">';
        }
        if (empty($forceall) && ! empty($conf->product->enabled) && empty($conf->service->enabled))
        {
        	print $langs->trans("Product");
            print '<input type="hidden" name="'.$htmlname.'" value="0">';
        }
		if ($forceall < 0)	// This should happened only for contracts when both predefined product and service are disabled.
		{
            print '<input type="hidden" name="'.$htmlname.'" value="1">';	// By default we set on service for contract. If CONTRACT_SUPPORT_PRODUCTS is set, forceall should be 1 not -1
		}
    }

    /**
     *	Load into cache cache_types_fees, array of types of fees
     *
     *	@return     int             Nb of lines loaded, 0 if already loaded, <0 if ko
     *	TODO move in DAO class
     */
    function load_cache_types_fees()
    {
        global $langs;

        $langs->load("trips");

        if (count($this->cache_types_fees)) return 0;    // Cache already load

        $sql = "SELECT c.code, c.label";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_type_fees as c";
        //$sql.= " ORDER BY c.label ASC";				  // No sort here, sort must be done after translation

        dol_syslog(get_class($this).'::load_cache_types_fees', LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;

            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $label=($obj->code != $langs->trans($obj->code) ? $langs->trans($obj->code) : $langs->trans($obj->label));
                $this->cache_types_fees[$obj->code] = $label;
                $i++;
            }

			asort($this->cache_types_fees);

            return $num;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *	Return list of types of notes
     *
     *	@param	string		$selected		Preselected type
     *	@param  string		$htmlname		Name of field in form
     * 	@param	int			$showempty		Add an empty field
     * 	@return	void
     */
    function select_type_fees($selected='',$htmlname='type',$showempty=0)
    {
        global $user, $langs;

        dol_syslog(get_class($this)."::select_type_fees ".$selected.", ".$htmlname, LOG_DEBUG);

        $this->load_cache_types_fees();

        print '<select class="flat" name="'.$htmlname.'">';
        if ($showempty)
        {
            print '<option value="-1"';
            if ($selected == -1) print ' selected="selected"';
            print '>&nbsp;</option>';
        }

        foreach($this->cache_types_fees as $key => $value)
        {
            print '<option value="'.$key.'"';
            if ($key == $selected) print ' selected="selected"';
            print '>';
            print $value;
            print '</option>';
        }

        print '</select>';
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
    }


    /**
     *  Return HTML code to select a company.
     *
     *  @param		int			$selected				Preselected products
     *  @param		string		$htmlname				Name of HTML select field (must be unique in page)
     *  @param		int			$filter					Filter on thirdparty
     *  @param		int			$limit					Limit on number of returned lines
     *  @param		array		$ajaxoptions			Options for ajax_autocompleter
     * 	@param		int			$forcecombo				Force to use combo box
     *  @return		string								Return select box for thirdparty.
     */
    function select_thirdparty($selected='', $htmlname='socid', $filter='', $limit=20, $ajaxoptions=array(), $forcecombo=0)
    {
    	global $langs,$conf;

    	$out='';

    	/* TODO Use ajax_autocompleter like for products (not finished)
    	if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT) && ! $forcecombo)
    	{
    		$placeholder='';

    		if ($selected && empty($selected_input_value))
    		{
    			require_once DOL_DOCUMENT_ROOT.'/societe/ajaxcompanies.php';
    			$societe = new Societe($this->db);
    			$societe->fetch($selected);
    			$selected_input_value=$societe->ref;
    		}
    		// mode=1 means customers products
    		$urloption='htmlname='.$htmlname.'&outjson=1&price_level='.$price_level.'&type='.$filtertype.'&mode=1&status='.$status.'&finished='.$finished;
    		print ajax_autocompleter($selected, $htmlname, DOL_URL_ROOT.'/societe/ajax/company.php', $urloption, $conf->global->COMPANY_USE_SEARCH_TO_SELECT, 0, $ajaxoptions);
    		if (empty($hidelabel)) print $langs->trans("RefOrLabel").' : ';
    		else if ($hidelabel > 1) {
    			if (! empty($conf->global->MAIN_HTML5_PLACEHOLDER)) $placeholder=' placeholder="'.$langs->trans("RefOrLabel").'"';
    			else $placeholder=' title="'.$langs->trans("RefOrLabel").'"';
    			if ($hidelabel == 2) {
    				print img_picto($langs->trans("Search"), 'search');
    			}
    		}
    		print '<input type="text" size="20" name="search_'.$htmlname.'" id="search_'.$htmlname.'" value="'.$selected_input_value.'"'.$placeholder.' />';
    		if ($hidelabel == 3) {
    			print img_picto($langs->trans("Search"), 'search');
    		}
    	}
    	else
    	{*/
    		$out.=$this->select_thirdparty_list($selected,$htmlname,$filter,1,0,$forcecombo,array(),'',0,$limit);
    	//}

    	return $out;
    }

    /**
     *  Output html form to select a third party
     *
     *	@param	string	$selected       Preselected type
     *	@param  string	$htmlname       Name of field in form
     *  @param  string	$filter         optional filters criteras (example: 's.rowid <> x')
     *	@param	int		$showempty		Add an empty field
     * 	@param	int		$showtype		Show third party type in combolist (customer, prospect or supplier)
     * 	@param	int		$forcecombo		Force to use combo box
     *  @param	array	$events			Event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     *	@param	int		$limit			Maximum number of elements
     * 	@return	string					HTML string with
	 *  @deprecated						Use select_thirdparty instead
     */
    function select_company($selected='', $htmlname='socid', $filter='', $showempty=0, $showtype=0, $forcecombo=0, $events=array(), $limit=0)
    {
		return $this->select_thirdparty_list($selected, $htmlname, $filter, $showempty, $showtype, $forcecombo, $events, '', 0, $limit);
    }

    /**
     *  Output html form to select a third party
     *
     *	@param	string	$selected       Preselected type
     *	@param  string	$htmlname       Name of field in form
     *  @param  string	$filter         optional filters criteras (example: 's.rowid <> x')
     *	@param	int		$showempty		Add an empty field
     * 	@param	int		$showtype		Show third party type in combolist (customer, prospect or supplier)
     * 	@param	int		$forcecombo		Force to use combo box
     *  @param	array	$events			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     *  @param	string	$filterkey		Filter on key value
     *  @param	int		$outputmode		0=HTML select string, 1=Array
     *  @param	int		$limit			Limit number of answers
     * 	@return	string					HTML string with
     */
    function select_thirdparty_list($selected='',$htmlname='socid',$filter='',$showempty=0, $showtype=0, $forcecombo=0, $events=array(), $filterkey='', $outputmode=0, $limit=0)
    {
        global $conf,$user,$langs;

        $out=''; $num=0;
        $outarray=array();

        // On recherche les societes
        $sql = "SELECT s.rowid, s.nom as name, s.client, s.fournisseur, s.code_client, s.code_fournisseur";
        $sql.= " FROM ".MAIN_DB_PREFIX ."societe as s";
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE s.entity IN (".getEntity('societe', 1).")";
        if (! empty($user->societe_id)) $sql.= " AND s.rowid = ".$user->societe_id;
        if ($filter) $sql.= " AND (".$filter.")";
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        if (! empty($conf->global->COMPANY_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND s.status<>0 ";
        // Add criteria
        if ($filterkey && $filterkey != '')
        {
			$sql.=" AND (";
        	if (! empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE))   // Can use index
        	{
        		$sql.="(s.name LIKE '".$this->db->escape($filterkey)."%')";
        	}
        	else
        	{
        		// For natural search
        		$scrit = explode(' ', $filterkey);
        		foreach ($scrit as $crit) {
        			$sql.=" AND (s.name LIKE '%".$this->db->escape($crit)."%')";
        		}
        	}
        	if (! empty($conf->barcode->enabled))
        	{
        		$sql .= " OR s.barcode LIKE '".$this->db->escape($filterkey)."%'";
        	}
        	$sql.=")";
        }
        $sql.=$this->db->order("nom","ASC");
		if ($limit > 0) $sql.=$this->db->plimit($limit);

        dol_syslog(get_class($this)."::select_thirdparty_list", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
           	if ($conf->use_javascript_ajax && ! $forcecombo)
            {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
            	$comboenhancement =ajax_combobox($htmlname, $events, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
            	$out.= $comboenhancement;
            	$nodatarole=($comboenhancement?' data-role="none"':'');
            }

            // Construct $out and $outarray
            $out.= '<select id="'.$htmlname.'" class="flat minwidth100" name="'.$htmlname.'"'.$nodatarole.'>'."\n";

            $textifempty='';
            // Do not use textempty = ' ' or '&nbsp;' here, or search on key will search on ' key'.
            //$textifempty=' ';
            //if (! empty($conf->use_javascript_ajax) || $forcecombo) $textifempty='';
            if ($showempty) $out.= '<option value="-1">'.$textifempty.'</option>'."\n";

            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    $label='';
                    if ($conf->global->SOCIETE_ADD_REF_IN_LIST) {
                    	if (($obj->client) && (!empty($obj->code_client))) {
                    		$label = $obj->code_client. ' - ';
                    	}
                    	if (($obj->fournisseur) && (!empty($obj->code_fournisseur))) {
                    		$label .= $obj->code_fournisseur. ' - ';
                    	}
                    	$label.=' '.$obj->name;
                    }
                    else
                    {
                    	$label=$obj->name;
                    }

                    if ($showtype)
                    {
                        if ($obj->client || $obj->fournisseur) $label.=' (';
                        if ($obj->client == 1 || $obj->client == 3) $label.=$langs->trans("Customer");
                        if ($obj->client == 2 || $obj->client == 3) $label.=($obj->client==3?', ':'').$langs->trans("Prospect");
                        if ($obj->fournisseur) $label.=($obj->client?', ':'').$langs->trans("Supplier");
                        if ($obj->client || $obj->fournisseur) $label.=')';
                    }
                    if ($selected > 0 && $selected == $obj->rowid)
                    {
                        $out.= '<option value="'.$obj->rowid.'" selected="selected">'.$label.'</option>';
                    }
                    else
					{
                        $out.= '<option value="'.$obj->rowid.'">'.$label.'</option>';
                    }

                    array_push($outarray, array('key'=>$obj->rowid, 'value'=>$obj->name, 'label'=>$obj->name));

                    $i++;
                    if (($i % 10) == 0) $out.="\n";
                }
            }
            $out.= '</select>'."\n";
        }
        else
        {
            dol_print_error($this->db);
        }

        $this->result=array('nbofthirdparties'=>$num);

        if ($outputmode) return $outarray;
        return $out;
    }


    /**
     *    	Return HTML combo list of absolute discounts
     *
     *    	@param	string	$selected       Id remise fixe pre-selectionnee
     *    	@param  string	$htmlname       Nom champ formulaire
     *    	@param  string	$filter         Criteres optionnels de filtre
     * 		@param	int		$socid			Id of thirdparty
     * 		@param	int		$maxvalue		Max value for lines that can be selected
     * 		@return	int						Return number of qualifed lines in list
     */
    function select_remises($selected, $htmlname, $filter, $socid, $maxvalue=0)
    {
        global $langs,$conf;

        // On recherche les remises
        $sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
        $sql.= " re.description, re.fk_facture_source";
        $sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re";
        $sql.= " WHERE fk_soc = ".$socid;
        if ($filter) $sql.= " AND ".$filter;
        $sql.= " ORDER BY re.description ASC";

        dol_syslog(get_class($this)."::select_remises", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            print '<select class="flat" name="'.$htmlname.'">';
            $num = $this->db->num_rows($resql);

            $qualifiedlines=$num;

            $i = 0;
            if ($num)
            {
                print '<option value="0">&nbsp;</option>';
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    $desc=dol_trunc($obj->description,40);
                    if ($desc=='(CREDIT_NOTE)') $desc=$langs->trans("CreditNote");
                    if ($desc=='(DEPOSIT)')     $desc=$langs->trans("Deposit");

                    $selectstring='';
                    if ($selected > 0 && $selected == $obj->rowid) $selectstring=' selected="selected"';

                    $disabled='';
                    if ($maxvalue > 0 && $obj->amount_ttc > $maxvalue)
                    {
                        $qualifiedlines--;
                        $disabled=' disabled="disabled"';
                    }

                    print '<option value="'.$obj->rowid.'"'.$selectstring.$disabled.'>'.$desc.' ('.price($obj->amount_ht).' '.$langs->trans("HT").' - '.price($obj->amount_ttc).' '.$langs->trans("TTC").')</option>';
                    $i++;
                }
            }
            print '</select>';
            return $qualifiedlines;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *	Return list of all contacts (for a third party or all)
     *
     *	@param	int		$socid      	Id ot third party or 0 for all
     *	@param  string	$selected   	Id contact pre-selectionne
     *	@param  string	$htmlname  	    Name of HTML field ('none' for a not editable field)
     *	@param  int		$showempty      0=no empty value, 1=add an empty value
     *	@param  string	$exclude        List of contacts id to exclude
     *	@param	string	$limitto		Disable answers that are not id in this array list
     *	@param	integer	$showfunction   Add function into label
     *	@param	string	$moreclass		Add more class to class style
     *	@param	integer	$showsoc	    Add company into label
     * 	@param	int		$forcecombo		Force to use combo box
     *  @param	array	$events			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     *  @param	bool	$options_only	Return options only (for ajax treatment)
     *	@return	int						<0 if KO, Nb of contact in list if OK
     */
    function select_contacts($socid,$selected='',$htmlname='contactid',$showempty=0,$exclude='',$limitto='',$showfunction=0, $moreclass='', $showsoc=0, $forcecombo=0, $events=array(), $options_only=false)
    {
    	print $this->selectcontacts($socid,$selected,$htmlname,$showempty,$exclude,$limitto,$showfunction, $moreclass, $options_only, $showsoc, $forcecombo, $events);
    	return $this->num;
    }

    /**
     *	Return list of all contacts (for a third party or all)
     *
     *	@param	int		$socid      	Id ot third party or 0 for all
     *	@param  string	$selected   	Id contact pre-selectionne
     *	@param  string	$htmlname  	    Name of HTML field ('none' for a not editable field)
     *	@param  int		$showempty     	0=no empty value, 1=add an empty value, 2=add line 'Internal' (used by user edit)
     *	@param  string	$exclude        List of contacts id to exclude
     *	@param	string	$limitto		Disable answers that are not id in this array list
     *	@param	integer	$showfunction   Add function into label
     *	@param	string	$moreclass		Add more class to class style
     *	@param	bool	$options_only	Return options only (for ajax treatment)
     *	@param	integer	$showsoc	    Add company into label
     * 	@param	int		$forcecombo		Force to use combo box
     *  @param	array	$events			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     *	@return	 int					<0 if KO, Nb of contact in list if OK
     */
    function selectcontacts($socid,$selected='',$htmlname='contactid',$showempty=0,$exclude='',$limitto='',$showfunction=0, $moreclass='', $options_only=false, $showsoc=0, $forcecombo=0, $events=array())
    {
        global $conf,$langs;

        $langs->load('companies');

        $out='';

        // On recherche les societes
        $sql = "SELECT sp.rowid, sp.lastname, sp.statut, sp.firstname, sp.poste";
        if ($showsoc > 0) $sql.= " , s.nom as company";
        $sql.= " FROM ".MAIN_DB_PREFIX ."socpeople as sp";
        if ($showsoc > 0) $sql.= " LEFT OUTER JOIN  ".MAIN_DB_PREFIX ."societe as s ON s.rowid=sp.fk_soc";
        $sql.= " WHERE sp.entity IN (".getEntity('societe', 1).")";
        if ($socid > 0) $sql.= " AND sp.fk_soc=".$socid;
        if (! empty($conf->global->CONTACT_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND sp.statut<>0";
        $sql.= " ORDER BY sp.lastname ASC";

        dol_syslog(get_class($this)."::select_contacts", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num=$this->db->num_rows($resql);

            if ($conf->use_javascript_ajax && ! $forcecombo && ! $options_only)
            {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
            	$comboenhancement = ajax_combobox($htmlname, $events, $conf->global->CONTACT_USE_SEARCH_TO_SELECT);
            	$out.= $comboenhancement;
            	$nodatarole=($comboenhancement?' data-role="none"':'');
            }

            if ($htmlname != 'none' || $options_only) $out.= '<select class="flat'.($moreclass?' '.$moreclass:'').'" id="'.$htmlname.'" name="'.$htmlname.'"'.$nodatarole.'>';
            if ($showempty == 1) $out.= '<option value="0"'.($selected=='0'?' selected="selected"':'').'></option>';
            if ($showempty == 2) $out.= '<option value="0"'.($selected=='0'?' selected="selected"':'').'>'.$langs->trans("Internal").'</option>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
                $contactstatic=new Contact($this->db);

                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);

                    $contactstatic->id=$obj->rowid;
                    $contactstatic->lastname=$obj->lastname;
                    $contactstatic->firstname=$obj->firstname;
					if ($obj->statut == 1){
                    if ($htmlname != 'none')
                    {
                        $disabled=0;
                        if (is_array($exclude) && count($exclude) && in_array($obj->rowid,$exclude)) $disabled=1;
                        if (is_array($limitto) && count($limitto) && ! in_array($obj->rowid,$limitto)) $disabled=1;
                        if ($selected && $selected == $obj->rowid)
                        {
                            $out.= '<option value="'.$obj->rowid.'"';
                            if ($disabled) $out.= ' disabled="disabled"';
                            $out.= ' selected="selected">';
                            $out.= $contactstatic->getFullName($langs);
                            if ($showfunction && $obj->poste) $out.= ' ('.$obj->poste.')';
                            if (($showsoc > 0) && $obj->company) $out.= ' - ('.$obj->company.')';
                            $out.= '</option>';
                        }
                        else
                        {
                            $out.= '<option value="'.$obj->rowid.'"';
                            if ($disabled) $out.= ' disabled="disabled"';
                            $out.= '>';
                            $out.= $contactstatic->getFullName($langs);
                            if ($showfunction && $obj->poste) $out.= ' ('.$obj->poste.')';
                            if (($showsoc > 0) && $obj->company) $out.= ' - ('.$obj->company.')';
                            $out.= '</option>';
                        }
                    }
                    else
					{
                        if ($selected == $obj->rowid)
                        {
                            $out.= $contactstatic->getFullName($langs);
                            if ($showfunction && $obj->poste) $out.= ' ('.$obj->poste.')';
                            if (($showsoc > 0) && $obj->company) $out.= ' - ('.$obj->company.')';
                        }
                    }
				}
                    $i++;
                }
            }
            else
			{
            	$out.= '<option value="-1"'.($showempty==2?'':' selected="selected"').' disabled="disabled">'.$langs->trans($socid?"NoContactDefinedForThirdParty":"NoContactDefined").'</option>';
            }
            if ($htmlname != 'none' || $options_only)
            {
                $out.= '</select>';
            }

            $this->num = $num;
            return $out;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *	Return select list of users
     *
     *  @param	string	$selected       Id user preselected
     *  @param  string	$htmlname       Field name in form
     *  @param  int		$show_empty     0=liste sans valeur nulle, 1=ajoute valeur inconnue
     *  @param  array	$exclude        Array list of users id to exclude
     * 	@param	int		$disabled		If select list must be disabled
     *  @param  array	$include        Array list of users id to include
     * 	@param	int		$enableonly		Array list of users id to be enabled. All other must be disabled
     *  @param	int		$force_entity	0 or Id of environment to force
     * 	@return	void
     *  @deprecated
     */
    function select_users($selected='',$htmlname='userid',$show_empty=0,$exclude='',$disabled=0,$include='',$enableonly='',$force_entity=0)
    {
        print $this->select_dolusers($selected,$htmlname,$show_empty,$exclude,$disabled,$include,$enableonly,$force_entity);
    }

    /**
     *	Return select list of users
     *
     *  @param	string	$selected       User id or user object of user preselected. If -1, we use id of current user.
     *  @param  string	$htmlname       Field name in form
     *  @param  int		$show_empty     0=liste sans valeur nulle, 1=ajoute valeur inconnue
     *  @param  array	$exclude        Array list of users id to exclude
     * 	@param	int		$disabled		If select list must be disabled
     *  @param  array	$include        Array list of users id to include or 'hierarchy' to have only supervised users
     * 	@param	array	$enableonly		Array list of users id to be enabled. All other must be disabled
     *  @param	int		$force_entity	0 or Id of environment to force
     *  @param	int		$maxlength		Maximum length of string into list (0=no limit)
     *  @param	int		$showstatus		0=show user status only if status is disabled, 1=always show user status into label, -1=never show user status
     *  @param	string	$morefilter		Add more filters into sql request
     * 	@return	string					HTML select string
     *  @see select_dolgroups
     */
    function select_dolusers($selected='', $htmlname='userid', $show_empty=0, $exclude='', $disabled=0, $include='', $enableonly='', $force_entity=0, $maxlength=0, $showstatus=0, $morefilter='')
    {
        global $conf,$user,$langs;

        // If no preselected user defined, we take current user
        if ((is_numeric($selected) && ($selected < -1 || empty($selected))) && empty($conf->global->SOCIETE_DISABLE_DEFAULT_SALESREPRESENTATIVE)) $selected=$user->id;

        $excludeUsers=null;
        $includeUsers=null;

        // Permettre l'exclusion d'utilisateurs
        if (is_array($exclude))	$excludeUsers = implode("','",$exclude);
        // Permettre l'inclusion d'utilisateurs
        if (is_array($include))	$includeUsers = implode("','",$include);
		else if ($include == 'hierarchy')
		{
			// Build list includeUsers to have only hierarchy
			$userid=$user->id;
			$include=array();
			if (empty($user->users) || ! is_array($user->users)) $user->get_full_tree();
			foreach($user->users as $key => $val)
			{
				if (preg_match('/_'.$userid.'/',$val['fullpath'])) $include[]=$val['id'];
			}
			$includeUsers = implode("','",$include);
			//var_dump($includeUsers);exit;
			//var_dump($user->users);exit;
		}

        $out='';

        // On recherche les utilisateurs
        $sql = "SELECT DISTINCT u.rowid, u.lastname as lastname, u.firstname, u.statut, u.login, u.admin, u.entity";
        if (! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
        {
            $sql.= ", e.label";
        }
        $sql.= " FROM ".MAIN_DB_PREFIX ."user as u";
        if (! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX ."entity as e ON e.rowid=u.entity";
            if ($force_entity) $sql.= " WHERE u.entity IN (0,".$force_entity.")";
            else $sql.= " WHERE u.entity IS NOT NULL";
        }
        else
       {
        	if (! empty($conf->multicompany->transverse_mode))
        	{
        		$sql.= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
        		$sql.= " WHERE ug.fk_user = u.rowid";
        		$sql.= " AND ug.entity = ".$conf->entity;
        	}
        	else
        	{
        		$sql.= " WHERE u.entity IN (0,".$conf->entity.")";
        	}
        }
        if (! empty($user->societe_id)) $sql.= " AND u.fk_societe = ".$user->societe_id;
        if (is_array($exclude) && $excludeUsers) $sql.= " AND u.rowid NOT IN ('".$excludeUsers."')";
        if (is_array($include) && $includeUsers) $sql.= " AND u.rowid IN ('".$includeUsers."')";
        if (! empty($conf->global->USER_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND u.statut <> 0";
        if (! empty($morefilter)) $sql.=" ".$morefilter;
        $sql.= " ORDER BY u.lastname ASC";

        dol_syslog(get_class($this)."::select_dolusers", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
           		// Enhance with select2
           		$nodatarole='';
		        if ($conf->use_javascript_ajax)
		        {
		            include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		            $comboenhancement = ajax_combobox($htmlname);
		            $out.=$comboenhancement;
		            $nodatarole=($comboenhancement?' data-role="none"':'');
		        }

                $out.= '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled="disabled"':'').$nodatarole.'>';
                if ($show_empty) $out.= '<option value="-1"'.((empty($selected) || $selected==-1)?' selected="selected"':'').'>&nbsp;</option>'."\n";

                $userstatic=new User($this->db);

                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);

                    $userstatic->id=$obj->rowid;
                    $userstatic->lastname=$obj->lastname;
                    $userstatic->firstname=$obj->firstname;

                    $disableline=0;
                    if (is_array($enableonly) && count($enableonly) && ! in_array($obj->rowid,$enableonly)) $disableline=1;

                    if ((is_object($selected) && $selected->id == $obj->rowid) || (! is_object($selected) && $selected == $obj->rowid))
                    {
                        $out.= '<option value="'.$obj->rowid.'"';
                        if ($disableline) $out.= ' disabled="disabled"';
                        $out.= ' selected="selected">';
                    }
                    else
                    {
                        $out.= '<option value="'.$obj->rowid.'"';
                        if ($disableline) $out.= ' disabled="disabled"';
                        $out.= '>';
                    }

                    $out.= $userstatic->getFullName($langs, 0, 0, $maxlength);
                    // Complete name with more info
                    $moreinfo=0;
                    if (! empty($conf->global->MAIN_SHOW_LOGIN))
                    {
                    	$out.= ($moreinfo?' - ':' (').$obj->login;
                    	$moreinfo++;
                    }
                    if ($showstatus >= 0)
                    {
                    	if ($obj->statut == 1 && $showstatus == 1)
                    	{
                    		$out.=($moreinfo?' - ':' (').$langs->trans('Enabled');
                    		$moreinfo++;
                    	}
						if ($obj->statut == 0)
						{
							$out.=($moreinfo?' - ':' (').$langs->trans('Disabled');
							$moreinfo++;
						}
					}
                    if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
                    {
                        if ($obj->admin && ! $obj->entity)
                        {
                        	$out.=($moreinfo?' - ':' (').$langs->trans("AllEntities");
                        	$moreinfo++;
                        }
                        else
                     {
                        	$out.=($moreinfo?' - ':' (').($obj->label?$obj->label:$langs->trans("EntityNameNotDefined"));
                        	$moreinfo++;
                     	}
                    }
					$out.=($moreinfo?')':'');
                    $out.= '</option>';

                    $i++;
                }
            }
            else
            {
                $out.= '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'" disabled="disabled">';
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


    /**
     *	Return select list of users. Selected users are stored into session.
     *  List of users are provided into $_SESSION['assignedtouser'].
     *
     *  @param  string	$action         Value for $action
     *  @param  string	$htmlname       Field name in form
     *  @param  int		$show_empty     0=liste sans valeur nulle, 1=ajoute valeur inconnue
     *  @param  array	$exclude        Array list of users id to exclude
     * 	@param	int		$disabled		If select list must be disabled
     *  @param  array	$include        Array list of users id to include or 'hierarchy' to have only supervised users
     * 	@param	array	$enableonly		Array list of users id to be enabled. All other must be disabled
     *  @param	int		$force_entity	0 or Id of environment to force
     *  @param	int		$maxlength		Maximum length of string into list (0=no limit)
     *  @param	int		$showstatus		0=show user status only if status is disabled, 1=always show user status into label, -1=never show user status
     *  @param	string	$morefilter		Add more filters into sql request
     * 	@return	string					HTML select string
     *  @see select_dolgroups
     */
    function select_dolusers_forevent($action='', $htmlname='userid', $show_empty=0, $exclude='', $disabled=0, $include='', $enableonly='', $force_entity=0, $maxlength=0, $showstatus=0, $morefilter='')
    {
        global $conf,$user,$langs;

        $userstatic=new User($this->db);
		$out='';

        // Method with no ajax
        //$out.='<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
        if ($action == 'view')
        {
			$out.='';
        }
		else
		{
			$out.='<input type="hidden" class="removedassignedhidden" name="removedassigned" value="">';
			$out.='<script type="text/javascript" language="javascript">jQuery(document).ready(function () {    jQuery(".removedassigned").click(function() {        jQuery(".removedassignedhidden").val(jQuery(this).val());    });})</script>';
			$out.=$this->select_dolusers('', $htmlname, $show_empty, $exclude, $disabled, $include, $enableonly, $force_entity, $maxlength, $showstatus, $morefilter);
			$out.='<input type="submit" class="button" name="'.$action.'assignedtouser" value="'.dol_escape_htmltag($langs->trans("Add")).'">';
		}
		$assignedtouser=array();
		if (!empty($_SESSION['assignedtouser']))
		{
			$assignedtouser=dol_json_decode($_SESSION['assignedtouser'], true);
		}
		$nbassignetouser=count($assignedtouser);

		if ($nbassignetouser && $action != 'view') $out.='<br>';
		$i=0; $ownerid=0;
		foreach($assignedtouser as $key => $value)
		{
			if ($value['id'] == $ownerid) continue;
			$userstatic->fetch($value['id']);
			$out.=$userstatic->getNomUrl(1);
			if ($i == 0) { $ownerid = $value['id']; $out.=' ('.$langs->trans("Owner").')'; }
			if ($nbassignetouser > 1 && $action != 'view') $out.=' <input type="image" style="border: 0px;" src="'.img_picto($langs->trans("Remove"), 'delete', '', 0, 1).'" value="'.$userstatic->id.'" class="removedassigned" id="removedassigned_'.$userstatic->id.'" name="removedassigned_'.$userstatic->id.'">';
			//$out.=' '.($value['mandatory']?$langs->trans("Mandatory"):$langs->trans("Optional"));
			//$out.=' '.($value['transparency']?$langs->trans("Busy"):$langs->trans("NotBusy"));
			$out.='<br>';
			$i++;
		}

		//$out.='</form>';
        return $out;
    }


    /**
     *  Return list of products for customer in Ajax if Ajax activated or go to select_produits_list
     *
     *  @param		int			$selected				Preselected products
     *  @param		string		$htmlname				Name of HTML select field (must be unique in page)
     *  @param		int			$filtertype				Filter on product type (''=nofilter, 0=product, 1=service)
     *  @param		int			$limit					Limit on number of returned lines
     *  @param		int			$price_level			Level of price to show
     *  @param		int			$status					-1=Return all products, 0=Products not on sell, 1=Products on sell
     *  @param		int			$finished				2=all, 1=finished, 0=raw material
     *  @param		string		$selected_input_value	Value of preselected input text (with ajax)
     *  @param		int			$hidelabel				Hide label (0=no, 1=yes, 2=show search icon (before) and placeholder, 3 search icon after)
     *  @param		array		$ajaxoptions			Options for ajax_autocompleter
     *  @param      int			$socid					Thirdparty Id
     *  @return		void
     */
    function select_produits($selected='', $htmlname='productid', $filtertype='', $limit=20, $price_level=0, $status=1, $finished=2, $selected_input_value='', $hidelabel=0, $ajaxoptions=array(),$socid=0)
    {
        global $langs,$conf;

        $price_level = (! empty($price_level) ? $price_level : 0);

        if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT))
        {
        	$placeholder='';

            if ($selected && empty($selected_input_value))
            {
                require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
                $product = new Product($this->db);
                $product->fetch($selected);
                $selected_input_value=$product->ref;
            }
            // mode=1 means customers products
            $urloption='htmlname='.$htmlname.'&outjson=1&price_level='.$price_level.'&type='.$filtertype.'&mode=1&status='.$status.'&finished='.$finished;
            //Price by customer
            if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES) && !empty($socid)) {
            	$urloption.='&socid='.$socid;
            }
            print ajax_autocompleter($selected, $htmlname, DOL_URL_ROOT.'/product/ajax/products.php', $urloption, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT, 0, $ajaxoptions);
            if (empty($hidelabel)) print $langs->trans("RefOrLabel").' : ';
            else if ($hidelabel > 1) {
            	if (! empty($conf->global->MAIN_HTML5_PLACEHOLDER)) $placeholder=' placeholder="'.$langs->trans("RefOrLabel").'"';
            	else $placeholder=' title="'.$langs->trans("RefOrLabel").'"';
            	if ($hidelabel == 2) {
            		print img_picto($langs->trans("Search"), 'search');
            	}
            }
            print '<input type="text" size="20" name="search_'.$htmlname.'" id="search_'.$htmlname.'" value="'.$selected_input_value.'"'.$placeholder.' />';
            if ($hidelabel == 3) {
            	print img_picto($langs->trans("Search"), 'search');
            }
        }
        else
		{
            print $this->select_produits_list($selected,$htmlname,$filtertype,$limit,$price_level,'',$status,$finished,0,$socid);
        }
    }

    /**
     *	Return list of products for a customer
     *
     *	@param      int		$selected       Preselected product
     *	@param      string	$htmlname       Name of select html
     *  @param		string	$filtertype     Filter on product type (''=nofilter, 0=product, 1=service)
     *	@param      int		$limit          Limit on number of returned lines
     *	@param      int		$price_level    Level of price to show
     * 	@param      string	$filterkey      Filter on product
     *	@param		int		$status         -1=Return all products, 0=Products not on sell, 1=Products on sell
     *  @param      int		$finished       Filter on finished field: 2=No filter
     *  @param      int		$outputmode     0=HTML select string, 1=Array
     *  @param      int		$socid     		Thirdparty Id
     *  @return     array    				Array of keys for json
     */
    function select_produits_list($selected='',$htmlname='productid',$filtertype='',$limit=20,$price_level=0,$filterkey='',$status=1,$finished=2,$outputmode=0,$socid=0)
    {
        global $langs,$conf,$user,$db;

        $out='';
        $outarray=array();

        $sql = "SELECT ";
        $sql.= " p.rowid, p.label, p.ref, p.description, p.fk_product_type, p.price, p.price_ttc, p.price_base_type, p.tva_tx, p.duration, p.stock, p.fk_price_expression";

        //Price by customer
        if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES) && !empty($socid)) {
        	$sql.=' ,pcp.rowid as idprodcustprice, pcp.price as custprice, pcp.price_ttc as custprice_ttc,';
        	$sql.=' pcp.price_base_type as custprice_base_type, pcp.tva_tx as custtva_tx';
        }

        // Multilang : we add translation
        if (! empty($conf->global->MAIN_MULTILANGS))
        {
            $sql.= ", pl.label as label_translated";
        }
		// Price by quantity
		if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))
		{
			$sql.= ", (SELECT pp.rowid FROM ".MAIN_DB_PREFIX."product_price as pp WHERE pp.fk_product = p.rowid";
			if ($price_level >= 1 && !empty($conf->global->PRODUIT_MULTIPRICES)) $sql.= " AND price_level=".$price_level;
			$sql.= " ORDER BY date_price";
			$sql.= " DESC LIMIT 1) as price_rowid";
			$sql.= ", (SELECT pp.price_by_qty FROM ".MAIN_DB_PREFIX."product_price as pp WHERE pp.fk_product = p.rowid";
			if ($price_level >= 1 && !empty($conf->global->PRODUIT_MULTIPRICES)) $sql.= " AND price_level=".$price_level;
			$sql.= " ORDER BY date_price";
			$sql.= " DESC LIMIT 1) as price_by_qty";
		}
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        //Price by customer
        if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES) && !empty($socid)) {
        	$sql.=" LEFT JOIN  ".MAIN_DB_PREFIX."product_customer_price as pcp ON pcp.fk_soc=".$socid." AND pcp.fk_product=p.rowid";
        }
        // Multilang : we add translation
        if (! empty($conf->global->MAIN_MULTILANGS))
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON pl.fk_product = p.rowid AND pl.lang='". $langs->getDefaultLang() ."'";
        }
        $sql.= ' WHERE p.entity IN ('.getEntity('product', 1).')';
        if ($finished == 0)
        {
            $sql.= " AND p.finished = ".$finished;
        }
        elseif ($finished == 1)
        {
            $sql.= " AND p.finished = ".$finished;
            if ($status >= 0)  $sql.= " AND p.tosell = ".$status;
        }
        elseif ($status >= 0)
        {
            $sql.= " AND p.tosell = ".$status;
        }
        if (strval($filtertype) != '') $sql.=" AND p.fk_product_type=".$filtertype;
        // Add criteria on ref/label
        if ($filterkey != '')
        {
        	$sql.=' AND (';
        	$prefix=empty($conf->global->PRODUCT_DONOTSEARCH_ANYWHERE)?'%':'';	// Can use index if PRODUCT_DONOTSEARCH_ANYWHERE is on
            // For natural search
            $scrit = explode(' ', $filterkey);
            $i=0;
            if (count($scrit) > 1) $sql.="(";
            foreach ($scrit as $crit)
            {
            	if ($i > 0) $sql.=" AND ";
                $sql.="(p.ref LIKE '".$prefix.$crit."%' OR p.label LIKE '".$prefix.$crit."%'";
                if (! empty($conf->global->MAIN_MULTILANGS)) $sql.=" OR pl.label LIKE '".$prefix.$crit."%'";
                $sql.=")";
                $i++;
            }
            if (count($scrit) > 1) $sql.=")";
          	if (! empty($conf->barcode->enabled)) $sql.= " OR p.barcode LIKE '".$prefix.$filterkey."%'";
        	$sql.=')';
        }
        $sql.= $db->order("p.ref");
        $sql.= $db->plimit($limit);

        // Build output string
        dol_syslog(get_class($this)."::select_produits_list search product", LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {
            require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
            require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
            $num = $this->db->num_rows($result);

            $out.='<select class="flat" name="'.$htmlname.'" id="'.$htmlname.'">';
            $out.='<option value="0" selected="selected">&nbsp;</option>';

            $i = 0;
            while ($num && $i < $num)
            {
            	$opt = '';
				$optJson = array();
				$objp = $this->db->fetch_object($result);

				if (!empty($objp->price_by_qty) && $objp->price_by_qty == 1 && !empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))
				{ // Price by quantity will return many prices for the same product
					$sql = "SELECT rowid, quantity, price, unitprice, remise_percent, remise";
					$sql.= " FROM ".MAIN_DB_PREFIX."product_price_by_qty";
					$sql.= " WHERE fk_product_price=".$objp->price_rowid;
					$sql.= " ORDER BY quantity ASC";

					dol_syslog(get_class($this)."::select_produits_list search price by qty", LOG_DEBUG);
					$result2 = $this->db->query($sql);
					if ($result2)
					{
						$nb_prices = $this->db->num_rows($result2);
						$j = 0;
						while ($nb_prices && $j < $nb_prices) {
							$objp2 = $this->db->fetch_object($result2);

							$objp->quantity = $objp2->quantity;
							$objp->price = $objp2->price;
							$objp->unitprice = $objp2->unitprice;
							$objp->remise_percent = $objp2->remise_percent;
							$objp->remise = $objp2->remise;
							$objp->price_by_qty_rowid = $objp2->rowid;

							$this->constructProductListOption($objp, $opt, $optJson, 0, $selected);

							$j++;

							// Add new entry
							// "key" value of json key array is used by jQuery automatically as selected value
							// "label" value of json key array is used by jQuery automatically as text for combo box
							$out.=$opt;
							array_push($outarray, $optJson);
						}
					}
				}
				else
				{
                    if (!empty($objp->fk_price_expression)) {
                        $price_product = new Product($this->db);
                        $price_product->fetch($objp->rowid, '', '', 1);
                        $priceparser = new PriceParser($this->db);
                        $price_result = $priceparser->parseProduct($price_product);
                        if ($price_result >= 0) {
                            $objp->price = $price_result;
                            $objp->unitprice = $price_result;
                            //Calculate the VAT
                            $objp->price_ttc = price2num($objp->price) * (1 + ($objp->tva_tx / 100));
                            $objp->price_ttc = price2num($objp->price_ttc,'MU');
                        }
                    }
					$this->constructProductListOption($objp, $opt, $optJson, $price_level, $selected);
					// Add new entry
					// "key" value of json key array is used by jQuery automatically as selected value
					// "label" value of json key array is used by jQuery automatically as text for combo box
					$out.=$opt;
					array_push($outarray, $optJson);
				}

                $i++;
            }

            $out.='</select>';

            $this->db->free($result);

            if (empty($outputmode)) return $out;
            return $outarray;
        }
        else
		{
            dol_print_error($db);
        }
    }

    /**
     * constructProductListOption
     *
     * @param 	resultset	$objp			Resultset of fetch
     * @param 	string		$opt			Option
     * @param 	string		$optJson		Option
     * @param 	int			$price_level	Price level
     * @param 	string		$selected		Preselected value
     * @return	void
     */
	private function constructProductListOption(&$objp, &$opt, &$optJson, $price_level, $selected)
	{
		global $langs,$conf,$user,$db;

        $outkey='';
        $outval='';
        $outref='';
        $outlabel='';
        $outdesc='';
        $outtype='';
        $outprice_ht='';
        $outprice_ttc='';
        $outpricebasetype='';
        $outtva_tx='';
		$outqty=1;
		$outdiscount=0;

		$maxlengtharticle=(empty($conf->global->PRODUCT_MAX_LENGTH_COMBO)?48:$conf->global->PRODUCT_MAX_LENGTH_COMBO);

        $label=$objp->label;
        if (! empty($objp->label_translated)) $label=$objp->label_translated;
        if (! empty($filterkey) && $filterkey != '') $label=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$label,1);

        $outkey=$objp->rowid;
        $outref=$objp->ref;
        $outlabel=$objp->label;
        $outdesc=$objp->description;
        $outtype=$objp->fk_product_type;

        $opt = '<option value="'.$objp->rowid.'"';
        $opt.= ($objp->rowid == $selected)?' selected="selected"':'';
		$opt.= (!empty($objp->price_by_qty_rowid) && $objp->price_by_qty_rowid > 0)?' pbq="'.$objp->price_by_qty_rowid.'"':'';
        if (! empty($conf->stock->enabled) && $objp->fk_product_type == 0 && isset($objp->stock))
        {
			if ($objp->stock > 0) $opt.= ' class="product_line_stock_ok"';
			else if ($objp->stock <= 0) $opt.= ' class="product_line_stock_too_low"';
        }
        $opt.= '>';
        $opt.= $objp->ref.' - '.dol_trunc($label,$maxlengtharticle).' - ';

        $objRef = $objp->ref;
        if (! empty($filterkey) && $filterkey != '') $objRef=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$objRef,1);
        $outval.=$objRef.' - '.dol_trunc($label,$maxlengtharticle).' - ';

        $found=0;

        // Multiprice
        if ($price_level >= 1 && $conf->global->PRODUIT_MULTIPRICES)		// If we need a particular price level (from 1 to 6)
        {
            $sql = "SELECT price, price_ttc, price_base_type, tva_tx";
            $sql.= " FROM ".MAIN_DB_PREFIX."product_price";
            $sql.= " WHERE fk_product='".$objp->rowid."'";
            $sql.= " AND entity IN (".getEntity('productprice', 1).")";
            $sql.= " AND price_level=".$price_level;
            $sql.= " ORDER BY date_price";
            $sql.= " DESC LIMIT 1";

            dol_syslog(get_class($this).'::constructProductListOption search price for level '.$price_level.'', LOG_DEBUG);
            $result2 = $this->db->query($sql);
            if ($result2)
            {
                $objp2 = $this->db->fetch_object($result2);
                if ($objp2)
                {
                    $found=1;
                    if ($objp2->price_base_type == 'HT')
                    {
                        $opt.= price($objp2->price,1,$langs,0,0,-1,$conf->currency).' '.$langs->trans("HT");
                        $outval.= price($objp2->price,0,$langs,0,0,-1,$conf->currency).' '.$langs->transnoentities("HT");
                    }
                    else
                    {
                        $opt.= price($objp2->price_ttc,1,$langs,0,0,-1,$conf->currency).' '.$langs->trans("TTC");
                        $outval.= price($objp2->price_ttc,0,$langs,0,0,-1,$conf->currency).' '.$langs->transnoentities("TTC");
                    }
                    $outprice_ht=price($objp2->price);
                    $outprice_ttc=price($objp2->price_ttc);
                    $outpricebasetype=$objp2->price_base_type;
                    $outtva_tx=$objp2->tva_tx;
                }
            }
            else
            {
                dol_print_error($this->db);
            }
        }

		// Price by quantity
		if (!empty($objp->quantity) && $objp->quantity >= 1 && ! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))
		{
			$found = 1;
			$outqty=$objp->quantity;
			$outdiscount=$objp->remise_percent;
			if ($objp->quantity == 1)
			{
				$opt.= price($objp->unitprice,1,$langs,0,0,-1,$conf->currency)."/";
				$outval.= price($objp->unitprice,0,$langs,0,0,-1,$conf->currency)."/";
				$opt.= $langs->trans("Unit");	// Do not use strtolower because it breaks utf8 encoding
				$outval.=$langs->transnoentities("Unit");
			}
			else
			{
				$opt.= price($objp->price,1,$langs,0,0,-1,$conf->currency)."/".$objp->quantity;
				$outval.= price($objp->price,0,$langs,0,0,-1,$conf->currency)."/".$objp->quantity;
				$opt.= $langs->trans("Units");	// Do not use strtolower because it breaks utf8 encoding
				$outval.=$langs->transnoentities("Units");
			}

			$outprice_ht=price($objp->unitprice);
            $outprice_ttc=price($objp->unitprice * (1 + ($objp->tva_tx / 100)));
            $outpricebasetype=$objp->price_base_type;
            $outtva_tx=$objp->tva_tx;
		}
		if (!empty($objp->quantity) && $objp->quantity >= 1)
		{
			$opt.=" (".price($objp->unitprice,1,$langs,0,0,-1,$conf->currency)."/".$langs->trans("Unit").")";	// Do not use strtolower because it breaks utf8 encoding
			$outval.=" (".price($objp->unitprice,0,$langs,0,0,-1,$conf->currency)."/".$langs->transnoentities("Unit").")";	// Do not use strtolower because it breaks utf8 encoding
		}
		if (!empty($objp->remise_percent) && $objp->remise_percent >= 1)
		{
			$opt.=" - ".$langs->trans("Discount")." : ".vatrate($objp->remise_percent).' %';
			$outval.=" - ".$langs->transnoentities("Discount")." : ".vatrate($objp->remise_percent).' %';
		}

		//Price by customer
		if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
			if (!empty($objp->idprodcustprice)) {
				$found = 1;

				if ($objp->custprice_base_type == 'HT')
				{
					$opt.= price($objp->custprice,1,$langs,0,0,-1,$conf->currency).' '.$langs->trans("HT");
					$outval.= price($objp->custprice,0,$langs,0,0,-1,$conf->currency).' '.$langs->transnoentities("HT");
				}
				else
				{
					$opt.= price($objp->custprice_ttc,1,$langs,0,0,-1,$conf->currency).' '.$langs->trans("TTC");
					$outval.= price($objp->custprice_ttc,0,$langs,0,0,-1,$conf->currency).' '.$langs->transnoentities("TTC");
				}

				$outprice_ht=price($objp->custprice);
				$outprice_ttc=price($objp->custprice_ttc);
				$outpricebasetype=$objp->custprice_base_type;
				$outtva_tx=$objp->custtva_tx;
			}
		}

        // If level no defined or multiprice not found, we used the default price
        if (! $found)
        {
            if ($objp->price_base_type == 'HT')
            {
                $opt.= price($objp->price,1,$langs,0,0,-1,$conf->currency).' '.$langs->trans("HT");
                $outval.= price($objp->price,0,$langs,0,0,-1,$conf->currency).' '.$langs->transnoentities("HT");
            }
            else
            {
                $opt.= price($objp->price_ttc,1,$langs,0,0,-1,$conf->currency).' '.$langs->trans("TTC");
                $outval.= price($objp->price_ttc,0,$langs,0,0,-1,$conf->currency).' '.$langs->transnoentities("TTC");
            }
            $outprice_ht=price($objp->price);
            $outprice_ttc=price($objp->price_ttc);
            $outpricebasetype=$objp->price_base_type;
            $outtva_tx=$objp->tva_tx;
        }

        if (! empty($conf->stock->enabled) && isset($objp->stock) && $objp->fk_product_type == 0)
        {
            $opt.= ' - '.$langs->trans("Stock").':'.$objp->stock;
            $outval.=' - '.$langs->transnoentities("Stock").':'.$objp->stock;
        }

        if ($objp->duration)
        {
            $duration_value = substr($objp->duration,0,dol_strlen($objp->duration)-1);
            $duration_unit = substr($objp->duration,-1);
            if ($duration_value > 1)
            {
                $dur=array("h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
            }
            else
            {
                $dur=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
            }
            $opt.= ' - '.$duration_value.' '.$langs->trans($dur[$duration_unit]);
            $outval.=' - '.$duration_value.' '.$langs->transnoentities($dur[$duration_unit]);
        }

        $opt.= "</option>\n";
		$optJson = array('key'=>$outkey, 'value'=>$outref, 'label'=>$outval, 'label2'=>$outlabel, 'desc'=>$outdesc, 'type'=>$outtype, 'price_ht'=>$outprice_ht, 'price_ttc'=>$outprice_ttc, 'pricebasetype'=>$outpricebasetype, 'tva_tx'=>$outtva_tx, 'qty'=>$outqty, 'discount'=>$outdiscount);
	}

    /**
     *	Return list of products for customer (in Ajax if Ajax activated or go to select_produits_fournisseurs_list)
     *
     *	@param	int		$socid			Id third party
     *	@param  string	$selected       Preselected product
     *	@param  string	$htmlname       Name of HTML Select
     *  @param	string	$filtertype     Filter on product type (''=nofilter, 0=product, 1=service)
     *	@param  string	$filtre			For a SQL filter
     *	@param	array	$ajaxoptions	Options for ajax_autocompleter
	 *  @param	int		$hidelabel		Hide label (0=no, 1=yes)
     *	@return	void
     */
    function select_produits_fournisseurs($socid, $selected='', $htmlname='productid', $filtertype='', $filtre='', $ajaxoptions=array(), $hidelabel=0)
    {
        global $langs,$conf;
        global $price_level, $status, $finished;

        if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT))
        {
            // mode=2 means suppliers products
            $urloption=($socid > 0?'socid='.$socid.'&':'').'htmlname='.$htmlname.'&outjson=1&price_level='.$price_level.'&type='.$filtertype.'&mode=2&status='.$status.'&finished='.$finished;
            print ajax_autocompleter('', $htmlname, DOL_URL_ROOT.'/product/ajax/products.php', $urloption, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT, 0, $ajaxoptions);
            print ($hidelabel?'':$langs->trans("RefOrLabel").' : ').'<input type="text" size="20" name="search_'.$htmlname.'" id="search_'.$htmlname.'">';
        }
        else
        {
            print $this->select_produits_fournisseurs_list($socid,$selected,$htmlname,$filtertype,$filtre,'',-1,0);
        }
    }

    /**
     *	Return list of suppliers products
     *
     *	@param	int		$socid   		Id societe fournisseur (0 pour aucun filtre)
     *	@param  int		$selected       Produit pre-selectionne
     *	@param  string	$htmlname       Nom de la zone select
     *  @param	string	$filtertype     Filter on product type (''=nofilter, 0=product, 1=service)
     *	@param  string	$filtre         Pour filtre sql
     *	@param  string	$filterkey      Filtre des produits
     *  @param  int		$statut         -1=Return all products, 0=Products not on sell, 1=Products on sell
     *  @param  int		$outputmode     0=HTML select string, 1=Array
     *  @param  int     $limit          Limit of line number
     *  @return array           		Array of keys for json
     */
    function select_produits_fournisseurs_list($socid,$selected='',$htmlname='productid',$filtertype='',$filtre='',$filterkey='',$statut=-1,$outputmode=0,$limit=100)
    {
        global $langs,$conf,$db;

        $out='';
        $outarray=array();

        $langs->load('stocks');

        $sql = "SELECT p.rowid, p.label, p.ref, p.price, p.duration,";
        $sql.= " pfp.ref_fourn, pfp.rowid as idprodfournprice, pfp.price as fprice, pfp.quantity, pfp.remise_percent, pfp.remise, pfp.unitprice,";
        $sql.= " pfp.fk_supplier_price_expression, pfp.fk_product, pfp.tva_tx, s.nom as name";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON p.rowid = pfp.fk_product";
        if ($socid) $sql.= " AND pfp.fk_soc = ".$socid;
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON pfp.fk_soc = s.rowid";
        $sql.= " WHERE p.entity IN (".getEntity('product', 1).")";
        $sql.= " AND p.tobuy = 1";
        if (strval($filtertype) != '') $sql.=" AND p.fk_product_type=".$filtertype;
        if (! empty($filtre)) $sql.=" ".$filtre;
        // Add criteria on ref/label
        if ($filterkey != '')
        {
        	$sql.=' AND (';
        	$prefix=empty($conf->global->PRODUCT_DONOTSEARCH_ANYWHERE)?'%':'';	// Can use index if PRODUCT_DONOTSEARCH_ANYWHERE is on
        	// For natural search
        	$scrit = explode(' ', $filterkey);
        	$i=0;
        	if (count($scrit) > 1) $sql.="(";
        	foreach ($scrit as $crit)
        	{
        		if ($i > 0) $sql.=" AND ";
        		$sql.="(pfp.ref_fourn LIKE '".$prefix.$crit."%' OR p.ref LIKE '".$prefix.$crit."%' OR p.label LIKE '".$prefix.$crit."%')";
        		$i++;
        	}
        	if (count($scrit) > 1) $sql.=")";
        	if (! empty($conf->barcode->enabled)) $sql.= " OR p.barcode LIKE '".$prefix.$filterkey."%'";
        	$sql.=')';
        }
        $sql.= " ORDER BY pfp.ref_fourn DESC, pfp.quantity ASC";
        $sql.= $db->plimit($limit);

        // Build output string

        dol_syslog(get_class($this)."::select_produits_fournisseurs_list", LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {
            require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';

            $num = $this->db->num_rows($result);

            //$out.='<select class="flat" id="select'.$htmlname.'" name="'.$htmlname.'">';	// remove select to have id same with combo and ajax
            $out.='<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'">';
            if (! $selected) $out.='<option value="0" selected="selected">&nbsp;</option>';
            else $out.='<option value="0">&nbsp;</option>';

            $i = 0;
            while ($i < $num)
            {
                $objp = $this->db->fetch_object($result);

                $outkey=$objp->idprodfournprice;
                $outref=$objp->ref;
                $outval='';
                $outqty=1;
				$outdiscount=0;

                $opt = '<option value="'.$objp->idprodfournprice.'"';
                if ($selected && $selected == $objp->idprodfournprice) $opt.= ' selected="selected"';
                if (empty($objp->idprodfournprice)) $opt.=' disabled="disabled"';
                $opt.= '>';

                $objRef = $objp->ref;
                if ($filterkey && $filterkey != '') $objRef=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$objRef,1);
                $objRefFourn = $objp->ref_fourn;
                if ($filterkey && $filterkey != '') $objRefFourn=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$objRefFourn,1);
                $label = $objp->label;
                if ($filterkey && $filterkey != '') $label=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$label,1);

                $opt.=$objp->ref;
                if (! empty($objp->idprodfournprice)) $opt.=' ('.$objp->ref_fourn.')';
                $opt.=' - ';
                $outval.=$objRef;
                if (! empty($objp->idprodfournprice)) $outval.=' ('.$objRefFourn.')';
                $outval.=' - ';
                $opt.=dol_trunc($label, 72).' - ';
                $outval.=dol_trunc($label, 72).' - ';

                if (! empty($objp->idprodfournprice))
                {
                    $outqty=$objp->quantity;
					$outdiscount=$objp->remise_percent;
                    if (!empty($objp->fk_supplier_price_expression)) {
                        $priceparser = new PriceParser($this->db);
                        $price_result = $priceparser->parseProductSupplier($objp->fk_product, $objp->fk_supplier_price_expression, $objp->quantity, $objp->tva_tx);
                        if ($price_result >= 0) {
                            $objp->fprice = $price_result;
                            if ($objp->quantity >= 1)
                            {
                                $objp->unitprice = $objp->fprice / $objp->quantity;
                            }
                        }
                    }
                    if ($objp->quantity == 1)
                    {
	                    $opt.= price($objp->fprice,1,$langs,0,0,-1,$conf->currency)."/";
                    	$outval.= price($objp->fprice,0,$langs,0,0,-1,$conf->currency)."/";
                    	$opt.= $langs->trans("Unit");	// Do not use strtolower because it breaks utf8 encoding
                        $outval.=$langs->transnoentities("Unit");
                    }
                    else
                    {
    	                $opt.= price($objp->fprice,1,$langs,0,0,-1,$conf->currency)."/".$objp->quantity;
	                    $outval.= price($objp->fprice,0,$langs,0,0,-1,$conf->currency)."/".$objp->quantity;
                    	$opt.= ' '.$langs->trans("Units");	// Do not use strtolower because it breaks utf8 encoding
                        $outval.= ' '.$langs->transnoentities("Units");
                    }

                    if ($objp->quantity >= 1)
                    {
                        $opt.=" (".price($objp->unitprice,1,$langs,0,0,-1,$conf->currency)."/".$langs->trans("Unit").")";	// Do not use strtolower because it breaks utf8 encoding
                        $outval.=" (".price($objp->unitprice,0,$langs,0,0,-1,$conf->currency)."/".$langs->transnoentities("Unit").")";	// Do not use strtolower because it breaks utf8 encoding
                    }
					if ($objp->remise_percent >= 1)
                    {
                        $opt.=" - ".$langs->trans("Discount")." : ".vatrate($objp->remise_percent).' %';
                        $outval.=" - ".$langs->transnoentities("Discount")." : ".vatrate($objp->remise_percent).' %';
                    }
                    if ($objp->duration)
                    {
                        $opt .= " - ".$objp->duration;
                        $outval.=" - ".$objp->duration;
                    }
                    if (! $socid)
                    {
                        $opt .= " - ".dol_trunc($objp->name,8);
                        $outval.=" - ".dol_trunc($objp->name,8);
                    }
                }
                else
                {
                    $opt.= $langs->trans("NoPriceDefinedForThisSupplier");
                    $outval.=$langs->transnoentities("NoPriceDefinedForThisSupplier");
                }
                $opt .= "</option>\n";


                // Add new entry
                // "key" value of json key array is used by jQuery automatically as selected value
                // "label" value of json key array is used by jQuery automatically as text for combo box
                $out.=$opt;
                array_push($outarray, array('key'=>$outkey, 'value'=>$outref, 'label'=>$outval, 'qty'=>$outqty, 'discount'=>$outdiscount, 'disabled'=>(empty($objp->idprodfournprice)?true:false)));
				// Exemple of var_dump $outarray
				// array(1) {[0]=>array(6) {[key"]=>string(1) "2" ["value"]=>string(3) "ppp"
				//           ["label"]=>string(76) "ppp (<strong>f</strong>ff2) - ppp - 20,00 Euros/1unité (20,00 Euros/unité)"
				//      	 ["qty"]=>string(1) "1" ["discount"]=>string(1) "0" ["disabled"]=>bool(false)
                //}
                //var_dump($outval); var_dump(utf8_check($outval)); var_dump(json_encode($outval));
                //$outval=array('label'=>'ppp (<strong>f</strong>ff2) - ppp - 20,00 Euros/ Unité (20,00 Euros/unité)');
                //var_dump($outval); var_dump(utf8_check($outval)); var_dump(json_encode($outval));

                $i++;
            }
            $out.='</select>';

            $this->db->free($result);

            if (empty($outputmode)) return $out;
            return $outarray;
        }
        else
        {
            dol_print_error($this->db);
        }
    }

    /**
     *	Return list of suppliers prices for a product
     *
     *  @param		int		$productid       Id of product
     *  @param      string	$htmlname        Name of HTML field
     *  @return		void
     */
    function select_product_fourn_price($productid,$htmlname='productfournpriceid')
    {
        global $langs,$conf;

        $langs->load('stocks');

        $sql = "SELECT p.rowid, p.label, p.ref, p.price, p.duration,";
        $sql.= " pfp.ref_fourn, pfp.rowid as idprodfournprice, pfp.price as fprice, pfp.quantity, pfp.unitprice,";
        $sql.= " pfp.fk_supplier_price_expression, pfp.fk_product, pfp.tva_tx, s.nom as name";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON p.rowid = pfp.fk_product";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON pfp.fk_soc = s.rowid";
        $sql.= " WHERE p.entity IN (".getEntity('product', 1).")";
        $sql.= " AND p.tobuy = 1";
        $sql.= " AND s.fournisseur = 1";
        $sql.= " AND p.rowid = ".$productid;
        $sql.= " ORDER BY s.nom, pfp.ref_fourn DESC";

        dol_syslog(get_class($this)."::select_product_fourn_price", LOG_DEBUG);
        $result=$this->db->query($sql);

        if ($result)
        {
            $num = $this->db->num_rows($result);

            $form = '<select class="flat" name="'.$htmlname.'">';

            if (! $num)
            {
                $form.= '<option value="0">-- '.$langs->trans("NoSupplierPriceDefinedForThisProduct").' --</option>';
            }
            else
            {
                require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
                $form.= '<option value="0">&nbsp;</option>';

                $i = 0;
                while ($i < $num)
                {
                    $objp = $this->db->fetch_object($result);

                    $opt = '<option value="'.$objp->idprodfournprice.'"';
                    //if there is only one supplier, preselect it
                    if($num == 1) {
                        $opt .= ' selected="selected"';
                    }
                    $opt.= '>'.$objp->name.' - '.$objp->ref_fourn.' - ';

                    if (!empty($objp->fk_supplier_price_expression)) {
                        $priceparser = new PriceParser($this->db);
                        $price_result = $priceparser->parseProductSupplier($objp->fk_product, $objp->fk_supplier_price_expression, $objp->quantity, $objp->tva_tx);
                        if ($price_result >= 0) {
                            $objp->fprice = $price_result;
                            if ($objp->quantity >= 1)
                            {
                                $objp->unitprice = $objp->fprice / $objp->quantity;
                            }
                        }
                    }
                    if ($objp->quantity == 1)
                    {
                        $opt.= price($objp->fprice,1,$langs,0,0,-1,$conf->currency)."/";
                    }

                    $opt.= $objp->quantity.' ';

                    if ($objp->quantity == 1)
                    {
                        $opt.= $langs->trans("Unit");
                    }
                    else
                    {
                        $opt.= $langs->trans("Units");
                    }
                    if ($objp->quantity > 1)
                    {
                        $opt.=" - ";
                        $opt.= price($objp->unitprice,1,$langs,0,0,-1,$conf->currency)."/".$langs->trans("Unit");
                    }
                    if ($objp->duration) $opt .= " - ".$objp->duration;
                    $opt .= "</option>\n";

                    $form.= $opt;
                    $i++;
                }
                $form.= '</select>';

                $this->db->free($result);
            }
            return $form;
        }
        else
        {
            dol_print_error($this->db);
        }
    }

    /**
     *    Return list of delivery address
     *
     *    @param    string	$selected          	Id contact pre-selectionn
     *    @param    int		$socid				Id of company
     *    @param    string	$htmlname          	Name of HTML field
     *    @param    int		$showempty         	Add an empty field
     *    @return	void
     */
    function select_address($selected, $socid, $htmlname='address_id',$showempty=0)
    {
        // On recherche les utilisateurs
        $sql = "SELECT a.rowid, a.label";
        $sql .= " FROM ".MAIN_DB_PREFIX ."societe_address as a";
        $sql .= " WHERE a.fk_soc = ".$socid;
        $sql .= " ORDER BY a.label ASC";

        dol_syslog(get_class($this)."::select_address", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            print '<select class="flat" name="'.$htmlname.'">';
            if ($showempty) print '<option value="0">&nbsp;</option>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);

                    if ($selected && $selected == $obj->rowid)
                    {
                        print '<option value="'.$obj->rowid.'" selected="selected">'.$obj->label.'</option>';
                    }
                    else
                    {
                        print '<option value="'.$obj->rowid.'">'.$obj->label.'</option>';
                    }
                    $i++;
                }
            }
            print '</select>';
            return $num;
        }
        else
        {
            dol_print_error($this->db);
        }
    }


    /**
     *      Charge dans cache la liste des conditions de paiements possibles
     *
     *      @return     int             Nb lignes chargees, 0 si deja chargees, <0 si ko
     */
    function load_cache_conditions_paiements()
    {
        global $langs;

        if (count($this->cache_conditions_paiements)) return 0;    // Cache deja charge

        $sql = "SELECT rowid, code, libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX.'c_payment_term';
        $sql.= " WHERE active=1";
        $sql.= " ORDER BY sortorder";
        dol_syslog(get_class($this).'::load_cache_conditions_paiements', LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $libelle=($langs->trans("PaymentConditionShort".$obj->code)!=("PaymentConditionShort".$obj->code)?$langs->trans("PaymentConditionShort".$obj->code):($obj->libelle!='-'?$obj->libelle:''));
                $this->cache_conditions_paiements[$obj->rowid]['code'] =$obj->code;
                $this->cache_conditions_paiements[$obj->rowid]['label']=$libelle;
                $i++;
            }
            return 1;
        }
        else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *      Charge dans cache la liste des délais de livraison possibles
     *
     *      @return     int             Nb lignes chargees, 0 si deja chargees, <0 si ko
     */
    function load_cache_availability()
    {
        global $langs;

        if (count($this->cache_availability)) return 0;    // Cache deja charge

        $sql = "SELECT rowid, code, label";
        $sql.= " FROM ".MAIN_DB_PREFIX.'c_availability';
        $sql.= " WHERE active=1";
        $sql.= " ORDER BY rowid";
        dol_syslog(get_class($this).'::load_cache_availability', LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $label=($langs->trans("AvailabilityType".$obj->code)!=("AvailabilityType".$obj->code)?$langs->trans("AvailabilityType".$obj->code):($obj->label!='-'?$obj->label:''));
                $this->cache_availability[$obj->rowid]['code'] =$obj->code;
                $this->cache_availability[$obj->rowid]['label']=$label;
                $i++;
            }
            return 1;
        }
        else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *      Retourne la liste des types de delais de livraison possibles
     *
     *      @param	int		$selected        Id du type de delais pre-selectionne
     *      @param  string	$htmlname        Nom de la zone select
     *      @param  string	$filtertype      To add a filter
     *		@param	int		$addempty		Add empty entry
     *		@return	void
     */
    function selectAvailabilityDelay($selected='',$htmlname='availid',$filtertype='',$addempty=0)
    {
        global $langs,$user;

        $this->load_cache_availability();

        print '<select class="flat" name="'.$htmlname.'">';
        if ($addempty) print '<option value="0">&nbsp;</option>';
        foreach($this->cache_availability as $id => $arrayavailability)
        {
            if ($selected == $id)
            {
                print '<option value="'.$id.'" selected="selected">';
            }
            else
            {
                print '<option value="'.$id.'">';
            }
            print $arrayavailability['label'];
            print '</option>';
        }
        print '</select>';
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
    }

    /**
     *      Load into cache cache_demand_reason, array of input reasons
     *
     *      @return     int             Nb of lines loaded, 0 if already loaded, <0 if ko
     */
    function loadCacheInputReason()
    {
        global $langs;

        if (count($this->cache_demand_reason)) return 0;    // Cache already loaded

        $sql = "SELECT rowid, code, label";
        $sql.= " FROM ".MAIN_DB_PREFIX.'c_input_reason';
        $sql.= " WHERE active=1";
        $sql.= " ORDER BY rowid";
        dol_syslog(get_class($this)."::loadCacheInputReason", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            $tmparray=array();
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $label=($langs->trans("DemandReasonType".$obj->code)!=("DemandReasonType".$obj->code)?$langs->trans("DemandReasonType".$obj->code):($obj->label!='-'?$obj->label:''));
                $tmparray[$obj->rowid]['id']   =$obj->rowid;
                $tmparray[$obj->rowid]['code'] =$obj->code;
                $tmparray[$obj->rowid]['label']=$label;
                $i++;
            }
            $this->cache_demand_reason=dol_sort_array($tmparray, 'label', 'asc');

            unset($tmparray);
            return 1;
        }
        else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
	 *	Return list of input reason (events that triggered an object creation, like after sending an emailing, making an advert, ...)
	 *  List found into table c_input_reason loaded by loadCacheInputReason
     *
     *  @param	int		$selected        Id or code of type origin to select by default
     *  @param  string	$htmlname        Nom de la zone select
     *  @param  string	$exclude         To exclude a code value (Example: SRC_PROP)
     *	@param	int		$addempty		 Add an empty entry
     *	@return	void
     */
    function selectInputReason($selected='',$htmlname='demandreasonid',$exclude='',$addempty=0)
    {
        global $langs,$user;

        $this->loadCacheInputReason();

        print '<select class="flat" name="'.$htmlname.'">';
        if ($addempty) print '<option value="0"'.(empty($selected)?' selected="selected"':'').'>&nbsp;</option>';
        foreach($this->cache_demand_reason as $id => $arraydemandreason)
        {
            if ($arraydemandreason['code']==$exclude) continue;

            if ($selected && ($selected == $arraydemandreason['id'] || $selected == $arraydemandreason['code']))
            {
                print '<option value="'.$arraydemandreason['id'].'" selected="selected">';
            }
            else
            {
                print '<option value="'.$arraydemandreason['id'].'">';
            }
            print $arraydemandreason['label'];
            print '</option>';
        }
        print '</select>';
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
    }

    /**
     *      Charge dans cache la liste des types de paiements possibles
     *
     *      @return     int             Nb lignes chargees, 0 si deja chargees, <0 si ko
     */
    function load_cache_types_paiements()
    {
        global $langs;

        if (count($this->cache_types_paiements)) return 0;    // Cache deja charge

        $sql = "SELECT id, code, libelle, type";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_paiement";
        $sql.= " WHERE active > 0";
        $sql.= " ORDER BY id";
        dol_syslog(get_class($this)."::load_cache_types_paiements", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $libelle=($langs->trans("PaymentTypeShort".$obj->code)!=("PaymentTypeShort".$obj->code)?$langs->trans("PaymentTypeShort".$obj->code):($obj->libelle!='-'?$obj->libelle:''));
                $this->cache_types_paiements[$obj->id]['code'] =$obj->code;
                $this->cache_types_paiements[$obj->id]['label']=$libelle;
                $this->cache_types_paiements[$obj->id]['type'] =$obj->type;
                $i++;
            }
            return $num;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     *      Retourne la liste des types de paiements possibles
     *
     *      @param	string	$selected        Id du type de paiement pre-selectionne
     *      @param  string	$htmlname        Nom de la zone select
     *      @param  string	$filtertype      Pour filtre
     *		@param	int		$addempty		Ajoute entree vide
     *		@return	void
     */
    function select_conditions_paiements($selected='',$htmlname='condid',$filtertype=-1,$addempty=0)
    {
        global $langs,$user;

        $this->load_cache_conditions_paiements();

        print '<select class="flat" name="'.$htmlname.'">';
        if ($addempty) print '<option value="0">&nbsp;</option>';
        foreach($this->cache_conditions_paiements as $id => $arrayconditions)
        {
            if ($selected == $id)
            {
                print '<option value="'.$id.'" selected="selected">';
            }
            else
            {
                print '<option value="'.$id.'">';
            }
            print $arrayconditions['label'];
            print '</option>';
        }
        print '</select>';
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
    }


    /**
     *      Return list of payment methods
     *
     *      @param	string	$selected       Id du mode de paiement pre-selectionne
     *      @param  string	$htmlname       Nom de la zone select
     *      @param  string	$filtertype     To filter on field type in llx_c_paiement (array('code'=>xx,'label'=>zz))
     *      @param  int		$format         0=id+libelle, 1=code+code, 2=code+libelle, 3=id+code
     *      @param  int		$empty			1=peut etre vide, 0 sinon
     * 		@param	int		$noadmininfo	0=Add admin info, 1=Disable admin info
     *      @param  int		$maxlength      Max length of label
     * 		@return	void
     */
    function select_types_paiements($selected='',$htmlname='paiementtype',$filtertype='',$format=0, $empty=0, $noadmininfo=0,$maxlength=0)
    {
        global $langs,$user;

        dol_syslog(get_class($this)."::select_type_paiements ".$selected.", ".$htmlname.", ".$filtertype.", ".$format,LOG_DEBUG);

        $filterarray=array();
        if ($filtertype == 'CRDT')  	$filterarray=array(0,2,3);
        elseif ($filtertype == 'DBIT') 	$filterarray=array(1,2,3);
        elseif ($filtertype != '' && $filtertype != '-1') $filterarray=explode(',',$filtertype);

        $this->load_cache_types_paiements();

        print '<select id="select'.$htmlname.'" class="flat selectpaymenttypes" name="'.$htmlname.'">';
        if ($empty) print '<option value="">&nbsp;</option>';
        foreach($this->cache_types_paiements as $id => $arraytypes)
        {
            // On passe si on a demande de filtrer sur des modes de paiments particuliers
            if (count($filterarray) && ! in_array($arraytypes['type'],$filterarray)) continue;

            // We discard empty line if showempty is on because an empty line has already been output.
            if ($empty && empty($arraytypes['code'])) continue;

            if ($format == 0) print '<option value="'.$id.'"';
            if ($format == 1) print '<option value="'.$arraytypes['code'].'"';
            if ($format == 2) print '<option value="'.$arraytypes['code'].'"';
            if ($format == 3) print '<option value="'.$id.'"';
            // Si selected est text, on compare avec code, sinon avec id
            if (preg_match('/[a-z]/i', $selected) && $selected == $arraytypes['code']) print ' selected="selected"';
            elseif ($selected == $id) print ' selected="selected"';
            print '>';
            if ($format == 0) $value=($maxlength?dol_trunc($arraytypes['label'],$maxlength):$arraytypes['label']);
            if ($format == 1) $value=$arraytypes['code'];
            if ($format == 2) $value=($maxlength?dol_trunc($arraytypes['label'],$maxlength):$arraytypes['label']);
            if ($format == 3) $value=$arraytypes['code'];
            print $value?$value:'&nbsp;';
            print '</option>';
        }
        print '</select>';
        if ($user->admin && ! $noadmininfo) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
    }


    /**
     *      Selection HT or TTC
     *
     *      @param	string	$selected        Id pre-selectionne
     *      @param  string	$htmlname        Nom de la zone select
     * 		@return	void
     */
    function select_PriceBaseType($selected='',$htmlname='price_base_type')
    {
        print $this->load_PriceBaseType($selected,$htmlname);
    }


    /**
     *      Selection HT or TTC
     *
     *      @param	string	$selected        Id pre-selectionne
     *      @param  string	$htmlname        Nom de la zone select
     * 		@return	void
     */
    function load_PriceBaseType($selected='',$htmlname='price_base_type')
    {
        global $langs;

        $return='';

        $return.= '<select class="flat" name="'.$htmlname.'">';
        $options = array(
			'HT'=>$langs->trans("HT"),
			'TTC'=>$langs->trans("TTC")
        );
        foreach($options as $id => $value)
        {
            if ($selected == $id)
            {
                $return.= '<option value="'.$id.'" selected="selected">'.$value;
            }
            else
            {
                $return.= '<option value="'.$id.'">'.$value;
            }
            $return.= '</option>';
        }
        $return.= '</select>';

        return $return;
    }

    /**
     *  Return a HTML select list of shipping mode
     *
     *  @param	string	$selected          Id shipping mode pre-selected
     *  @param  string	$htmlname          Name of select zone
     *  @param  string	$filtre            To filter list
     *  @param  int		$useempty          1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
     *  @param  string	$moreattrib        To add more attribute on select
     * 	@return	void
     */
    function selectShippingMethod($selected='',$htmlname='shipping_method_id',$filtre='',$useempty=0,$moreattrib='')
    {
        global $langs, $conf, $user;

        $langs->load("admin");
        $langs->load("deliveries");

        $sql = "SELECT rowid, code, libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode";
        $sql.= " WHERE active = 1";
        if ($filtre) $sql.=" AND ".$filtre;
        $sql.= " ORDER BY libelle ASC";

        dol_syslog(get_class($this)."::selectShippingMode", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);
            $i = 0;
            if ($num) {
                print '<select id="select'.$htmlname.'" class="flat selectshippingmethod" name="'.$htmlname.'"'.($moreattrib?' '.$moreattrib:'').'>';
                if ($useempty == 1 || ($useempty == 2 && $num > 1)) {
                    print '<option value="-1">&nbsp;</option>';
                }
                while ($i < $num) {
                    $obj = $this->db->fetch_object($result);
                    if ($selected == $obj->rowid) {
                        print '<option value="'.$obj->rowid.'" selected="selected">';
                    } else {
                        print '<option value="'.$obj->rowid.'">';
                    }
                    print $langs->trans("SendingMethod".strtoupper($obj->code));
                    print '</option>';
                    $i++;
                }
                print "</select>";
                if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
            } else {
                print $langs->trans("NoShippingMethodDefined");
            }
        } else {
            dol_print_error($this->db);
        }
    }

    /**
     *    Display form to select shipping mode
     *
     *    @param	string	$page        Page
     *    @param    int		$selected    Id of shipping mode
     *    @param    string	$htmlname    Name of select html field
     *    @param    int		$addempty    1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
     *    @return	void
     */
    function formSelectShippingMethod($page, $selected='', $htmlname='shipping_method_id', $addempty=0)
    {
        global $langs, $db;

        $langs->load("deliveries");

        if ($htmlname != "none") {
            print '<form method="POST" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setshippingmethod">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->selectShippingMethod($selected, $htmlname, '', $addempty);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        } else {
            if ($selected) {
                $code=$langs->getLabelFromKey($db, $selected, 'c_shipment_mode', 'rowid', 'code');
                print $langs->trans("SendingMethod".strtoupper($code));
            } else {
                print "&nbsp;";
            }
        }
    }

	/**
	 * Creates HTML last in cycle situation invoices selector
	 *
	 * @param       string  $selected   Preselected ID
	 * @param       int     $socid      Company ID
	 *
	 * @return    string                     HTML select
	 */
	function load_situation_invoices($selected = '', $socid = '')
	{
		global $langs;

		$langs->load('bills');

		$opt = '<option value ="" selected="selected"></option>';
		$sql = 'SELECT rowid, facnumber, situation_cycle_ref, situation_counter, situation_final, fk_soc FROM ' . MAIN_DB_PREFIX . 'facture WHERE situation_counter>=1';
		$sql .= ' order by situation_cycle_ref, situation_counter desc';
		$resql = $this->db->query($sql);
		if ($resql && $this->db->num_rows($resql) > 0) {
			// Last seen cycle
			$ref = 0;
			while ($res = $this->db->fetch_array($resql, MYSQL_NUM)) {
				//Same company ?
				if ($socid == $res[5]) {
					//Same cycle ?
					if ($res[2] != $ref) {
						// Just seen this cycle
						$ref = $res[2];
						//not final ?
						if ($res[4] != 1) {
							//Not prov?
							if (substr($res[1], 1, 4) != 'PROV') {
								if ($selected == $res[0]) {
									$opt .= '<option value="' . $res[0] . '" selected="selected">' . $res[1] . '</option>';
								} else {
									$opt .= '<option value="' . $res[0] . '">' . $res[1] . '</option>';
								}
							}
						}
					}
				}
			}
		} else {
				dol_syslog("Error sql=" . $sql . ", error=" . $this->error, LOG_ERR);
		}
		if ($opt == '<option value ="" selected="selected"></option>') {
			$opt = '<option value ="0" selected="selected">' . $langs->trans('NoSituations') . '</option>';
		}
		return $opt;
	}

    /**
     *  Return a HTML select list of bank accounts
     *
     *  @param	string	$selected          Id account pre-selected
     *  @param  string	$htmlname          Name of select zone
     *  @param  int		$statut            Status of searched accounts (0=open, 1=closed, 2=both)
     *  @param  string	$filtre            To filter list
     *  @param  int		$useempty          1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
     *  @param  string	$moreattrib        To add more attribute on select
     * 	@return	void
     */
    function select_comptes($selected='',$htmlname='accountid',$statut=0,$filtre='',$useempty=0,$moreattrib='')
    {
        global $langs, $conf;

        $langs->load("admin");

        $sql = "SELECT rowid, label, bank, clos as status";
        $sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
        $sql.= " WHERE entity IN (".getEntity('bank_account', 1).")";
        if ($statut != 2) $sql.= " AND clos = '".$statut."'";
        if ($filtre) $sql.=" AND ".$filtre;
        $sql.= " ORDER BY label";

        dol_syslog(get_class($this)."::select_comptes", LOG_DEBUG);
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
                    print '<option value="-1">&nbsp;</option>';
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
                    if ($statut == 2 && $obj->status == 1) print ' ('.$langs->trans("Closed").')';
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
     *    Display form to select bank account
     *
     *    @param	string	$page        Page
     *    @param    int		$selected    Id of bank account
     *    @param    string	$htmlname    Name of select html field
     *    @param    int		$addempty    1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
     *    @return	void
     */
    function formSelectAccount($page, $selected='', $htmlname='fk_account', $addempty=0)
    {
        global $langs;
        if ($htmlname != "none") {
            print '<form method="POST" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setbankaccount">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->select_comptes($selected, $htmlname, 0, '', $addempty);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        } else {
            if ($selected) {
                require_once DOL_DOCUMENT_ROOT .'/compta/bank/class/account.class.php';
                $bankstatic=new Account($this->db);
                $bankstatic->fetch($selected);
                print $this->textwithpicto($bankstatic->label,$langs->trans("AccountCurrency").'&nbsp;'.$bankstatic->currency_code);
            } else {
                print "&nbsp;";
            }
        }
    }

    /**
     *    Return list of categories having choosed type
     *
     *    @param	int		$type				Type de categories (0=product, 1=supplier, 2=customer, 3=member)
     *    @param    string	$selected    		Id of category preselected or 'auto' (autoselect category if there is only one element)
     *    @param    string	$htmlname			HTML field name
     *    @param    int		$maxlength      	Maximum length for labels
     *    @param    int		$excludeafterid 	Exclude all categories after this leaf in category tree.
     *    @return	string
     *    @see select_categories
     */
    function select_all_categories($type, $selected='', $htmlname="parent", $maxlength=64, $excludeafterid=0)
    {
        global $langs;
        $langs->load("categories");

        $cat = new Categorie($this->db);
        $cate_arbo = $cat->get_full_arbo($type,$excludeafterid);

        $output = '<select class="flat" name="'.$htmlname.'">';
        if (is_array($cate_arbo))
        {
            if (! count($cate_arbo)) $output.= '<option value="-1" disabled="disabled">'.$langs->trans("NoCategoriesDefined").'</option>';
            else
            {
                $output.= '<option value="-1">&nbsp;</option>';
                foreach($cate_arbo as $key => $value)
                {
                    if ($cate_arbo[$key]['id'] == $selected || ($selected == 'auto' && count($cate_arbo) == 1))
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
     *     @param	string		$page        	   	Url of page to call if confirmation is OK
     *     @param	string		$title       	   	Title
     *     @param	string		$question    	   	Question
     *     @param 	string		$action      	   	Action
     *	   @param	array		$formquestion	   	An array with forms complementary inputs
     * 	   @param	string		$selectedchoice		"" or "no" or "yes"
     * 	   @param	int			$useajax		   	0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=preoutput confirm box with div id=dialog-confirm-xxx
     *     @param	int			$height          	Force height of box
     *     @param	int			$width				Force width of box
     *     @return 	void
     *     @deprecated
     */
    function form_confirm($page, $title, $question, $action, $formquestion='', $selectedchoice="", $useajax=0, $height=170, $width=500)
    {
        print $this->formconfirm($page, $title, $question, $action, $formquestion, $selectedchoice, $useajax, $height, $width);
    }

    /**
     *     Show a confirmation HTML form or AJAX popup.
     *     Easiest way to use this is with useajax=1.
     *     If you use useajax='xxx', you must also add jquery code to trigger opening of box (with correct parameters)
     *     just after calling this method. For example:
     *       print '<script type="text/javascript">'."\n";
     *       print 'jQuery(document).ready(function() {'."\n";
     *       print 'jQuery(".xxxlink").click(function(e) { jQuery("#aparamid").val(jQuery(this).attr("rel")); jQuery("#dialog-confirm-xxx").dialog("open"); return false; });'."\n";
     *       print '});'."\n";
     *       print '</script>'."\n";
     *
     *     @param  	string		$page        	   	Url of page to call if confirmation is OK
     *     @param	string		$title       	   	Title
     *     @param	string		$question    	   	Question
     *     @param 	string		$action      	   	Action
     *	   @param  	array		$formquestion	   	An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , ))
     * 	   @param  	string		$selectedchoice  	"" or "no" or "yes"
     * 	   @param  	int			$useajax		   	0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=Yes and preoutput confirm box with div id=dialog-confirm-xxx
     *     @param  	int			$height          	Force height of box
     *     @param	int			$width				Force width of bow
     *     @return 	string      	    			HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
     */
    function formconfirm($page, $title, $question, $action, $formquestion='', $selectedchoice="", $useajax=0, $height=170, $width=500)
    {
        global $langs,$conf;
        global $useglobalvars;

        $more='';
        $formconfirm='';
        $inputok=array();
        $inputko=array();

        // Clean parameters
        $newselectedchoice=empty($selectedchoice)?"no":$selectedchoice;

        if (is_array($formquestion) && ! empty($formquestion))
        {
        	// First add hidden fields and value
        	foreach ($formquestion as $key => $input)
            {
                if (is_array($input) && ! empty($input))
                {
                	if ($input['type'] == 'hidden')
                    {
                        $more.='<input type="hidden" id="'.$input['name'].'" name="'.$input['name'].'" value="'.dol_escape_htmltag($input['value']).'">'."\n";
                    }
                }
            }

        	// Now add questions
            $more.='<table class="paddingrightonly" width="100%">'."\n";
            $more.='<tr><td colspan="3" valign="top">'.(! empty($formquestion['text'])?$formquestion['text']:'').'</td></tr>'."\n";
            foreach ($formquestion as $key => $input)
            {
                if (is_array($input) && ! empty($input))
                {
                	$size=(! empty($input['size'])?' size="'.$input['size'].'"':'');

                    if ($input['type'] == 'text')
                    {
                        $more.='<tr><td valign="top">'.$input['label'].'</td><td valign="top" colspan="2" align="left"><input type="text" class="flat" id="'.$input['name'].'" name="'.$input['name'].'"'.$size.' value="'.$input['value'].'" /></td></tr>'."\n";
                    }
                    else if ($input['type'] == 'password')
                    {
                        $more.='<tr><td valign="top">'.$input['label'].'</td><td valign="top" colspan="2" align="left"><input type="password" class="flat" id="'.$input['name'].'" name="'.$input['name'].'"'.$size.' value="'.$input['value'].'" /></td></tr>'."\n";
                    }
                    else if ($input['type'] == 'select')
                    {
                        $more.='<tr><td valign="top">';
                        if (! empty($input['label'])) $more.=$input['label'].'</td><td valign="top" colspan="2" align="left">';
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
                        if (isset($input['disabled'])) $more.=' disabled="disabled"';
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
                }
            }
            $more.='</table>'."\n";
        }

		// JQUI method dialog is broken with jmobile, we use standard HTML.
		// Note: When using dol_use_jmobile or no js, you must also check code for button use a GET url with action=xxx and check that you also output the confirm code when action=xxx
		// See page product/card.php for example
        if (! empty($conf->dol_use_jmobile)) $useajax=0;
		if (empty($conf->use_javascript_ajax)) $useajax=0;

        if ($useajax)
        {
            $autoOpen=true;
            $dialogconfirm='dialog-confirm';
            $button='';
            if (! is_numeric($useajax))
            {
                $button=$useajax;
                $useajax=1;
                $autoOpen=false;
                $dialogconfirm.='-'.$button;
            }
            $pageyes=$page.(preg_match('/\?/',$page)?'&':'?').'action='.$action.'&confirm=yes';
            $pageno=($useajax == 2 ? $page.(preg_match('/\?/',$page)?'&':'?').'confirm=no':'');
            // Add input fields into list of fields to read during submit (inputok and inputko)
            if (is_array($formquestion))
            {
                foreach ($formquestion as $key => $input)
                {
                    if (isset($input['name'])) array_push($inputok,$input['name']);
                    if (isset($input['inputko']) && $input['inputko'] == 1) array_push($inputko,$input['name']);
                }
            }

			// Show JQuery confirm box. Note that global var $useglobalvars is used inside this template
            $formconfirm.= '<div id="'.$dialogconfirm.'" title="'.dol_escape_htmltag($title).'" style="display: none;">';
            if (! empty($more)) {
            	$formconfirm.= '<div>'.$more.'</div>';
            }
            $formconfirm.= ($question ? img_help('','').' '.$question : '');
            $formconfirm.= '</div>'."\n";

            $formconfirm.= "\n<!-- begin ajax form_confirm page=".$page." -->\n";
            $formconfirm.= '<script type="text/javascript">'."\n";
            $formconfirm.='
            $(function() {
            	$( "#'.$dialogconfirm.'" ).dialog({
                    autoOpen: '.($autoOpen ? "true" : "false").',';
            		if ($newselectedchoice == 'no')
            		{
						$formconfirm.='
						open: function() {
            				$(this).parent().find("button.ui-button:eq(1)").focus();
						},';
            		}
        			$formconfirm.='
                    resizable: false,
                    height: "'.$height.'",
                    width: "'.$width.'",
                    modal: true,
                    closeOnEscape: false,
                    buttons: {
                        "'.dol_escape_js($langs->transnoentities("Yes")).'": function() {
                        	var options="";
                        	var inputok = '.json_encode($inputok).';
                         	var pageyes = "'.dol_escape_js(! empty($pageyes)?$pageyes:'').'";
                         	if (inputok.length>0) {
                         		$.each(inputok, function(i, inputname) {
                         			var more = "";
                         			if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
                         		    if ($("#" + inputname).attr("type") == "radio") { more = ":checked"; }
                         			var inputvalue = $("#" + inputname + more).val();
                         			if (typeof inputvalue == "undefined") { inputvalue=""; }
                         			options += "&" + inputname + "=" + inputvalue;
                         		});
                         	}
                         	var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "") + options;
                         	//alert(urljump);
            				if (pageyes.length > 0) { location.href = urljump; }
                            $(this).dialog("close");
                        },
                        "'.dol_escape_js($langs->transnoentities("No")).'": function() {
                        	var options = "";
                         	var inputko = '.json_encode($inputko).';
                         	var pageno="'.dol_escape_js(! empty($pageno)?$pageno:'').'";
                         	if (inputko.length>0) {
                         		$.each(inputko, function(i, inputname) {
                         			var more = "";
                         			if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
                         			var inputvalue = $("#" + inputname + more).val();
                         			if (typeof inputvalue == "undefined") { inputvalue=""; }
                         			options += "&" + inputname + "=" + inputvalue;
                         		});
                         	}
                         	var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "") + options;
                         	//alert(urljump);
            				if (pageno.length > 0) { location.href = urljump; }
                            $(this).dialog("close");
                        }
                    }
                });

            	var button = "'.$button.'";
            	if (button.length > 0) {
                	$( "#" + button ).click(function() {
                		$("#'.$dialogconfirm.'").dialog("open");
        			});
                }
            });
            </script>';
            $formconfirm.= "<!-- end ajax form_confirm -->\n";
        }
        else
        {
        	$formconfirm.= "\n<!-- begin form_confirm page=".$page." -->\n";

            $formconfirm.= '<form method="POST" action="'.$page.'" class="notoptoleftroright">'."\n";
            $formconfirm.= '<input type="hidden" name="action" value="'.$action.'">'."\n";
            $formconfirm.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";

            $formconfirm.= '<table width="100%" class="valid">'."\n";

            // Line title
            $formconfirm.= '<tr class="validtitre"><td class="validtitre" colspan="3">'.img_picto('','recent').' '.$title.'</td></tr>'."\n";

            // Line form fields
            if ($more)
            {
                $formconfirm.='<tr class="valid"><td class="valid" colspan="3">'."\n";
                $formconfirm.=$more;
                $formconfirm.='</td></tr>'."\n";
            }

            // Line with question
            $formconfirm.= '<tr class="valid">';
            $formconfirm.= '<td class="valid">'.$question.'</td>';
            $formconfirm.= '<td class="valid">';
            $formconfirm.= $this->selectyesno("confirm",$newselectedchoice);
            $formconfirm.= '</td>';
            $formconfirm.= '<td class="valid" align="center"><input class="button" type="submit" value="'.$langs->trans("Validate").'"></td>';
            $formconfirm.= '</tr>'."\n";

            $formconfirm.= '</table>'."\n";

            $formconfirm.= "</form>\n";
            $formconfirm.= '<br>';

            $formconfirm.= "<!-- end form_confirm -->\n";
        }

        return $formconfirm;
    }


    /**
     *    Show a form to select a project
     *
     *    @param	int		$page        		Page
     *    @param	int		$socid       		Id third party (-1=all, 0=only projects not linked to a third party, id=projects not linked or linked to third party id)
     *    @param    int		$selected    		Id pre-selected project
     *    @param    string	$htmlname    		Name of select field
     *    @param	int		$discard_closed		Discard closed projects (0=Keep,1=hide completely,2=Disable)
     *    @param	int		$maxlength			Max length
     *    @param	int		$forcefocus			Force focus on field (works with javascript only)
     *    @return	void
     */
    function form_project($page, $socid, $selected='', $htmlname='projectid', $discard_closed=0, $maxlength=20, $forcefocus=0)
    {
        global $langs;

        require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
        require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

        $formproject=new FormProjets($this->db);

        $langs->load("project");
        if ($htmlname != "none")
        {
            print "\n";
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="classin">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $formproject->select_projects($socid,$selected,$htmlname,$maxlength,0,1,$discard_closed, $forcefocus);
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
                //print '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$selected.'">'.$projet->title.'</a>';
                print $projet->getNomUrl(0,'',1);
            }
            else
            {
                print "&nbsp;";
            }
        }
    }

    /**
     *	Show a form to select payment conditions
     *
     *  @param	int		$page        	Page
     *  @param  string	$selected    	Id condition pre-selectionne
     *  @param  string	$htmlname    	Name of select html field
     *	@param	int		$addempty		Add empty entry
     *  @return	void
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
     *  Show a form to select a delivery delay
     *
     *  @param  int		$page        	Page
     *  @param  string	$selected    	Id condition pre-selectionne
     *  @param  string	$htmlname    	Name of select html field
     *	@param	int		$addempty		Ajoute entree vide
     *  @return	void
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
            $this->selectAvailabilityDelay($selected,$htmlname,-1,$addempty);
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
	 *	Output HTML form to select list of input reason (events that triggered an object creation, like after sending an emailing, making an advert, ...)
	 *  List found into table c_input_reason loaded by loadCacheInputReason
     *
     *  @param  string	$page        	Page
     *  @param  string	$selected    	Id condition pre-selectionne
     *  @param  string	$htmlname    	Name of select html field
     *	@param	int		$addempty		Add empty entry
     *  @return	void
     */
    function formInputReason($page, $selected='', $htmlname='demandreason', $addempty=0)
    {
        global $langs;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setdemandreason">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->selectInputReason($selected,$htmlname,-1,$addempty);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                $this->loadCacheInputReason();
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
     *    Show a form + html select a date
     *
     *    @param	string		$page        Page
     *    @param	string		$selected    Date preselected
     *    @param    string		$htmlname    Html name of date input fields or 'none'
     *    @param    int			$displayhour Display hour selector
     *    @param    int			$displaymin	 Display minutes selector
     *    @return	void
     *    @see		select_date
     */
    function form_date($page, $selected, $htmlname, $displayhour=0, $displaymin=0)
    {
        global $langs;

        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'" name="form'.$htmlname.'">';
            print '<input type="hidden" name="action" value="set'.$htmlname.'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            print $this->select_date($selected,$htmlname,$displayhour,$displaymin,1,'form'.$htmlname);
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
     *  Show a select form to choose a user
     *
     *  @param	string	$page        	Page
     *  @param  string	$selected    	Id of user preselected
     *  @param  string	$htmlname    	Name of input html field
     *  @param  array	$exclude         List of users id to exclude
     *  @param  array	$include         List of users id to include
     *  @return	void
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
                require_once DOL_DOCUMENT_ROOT .'/user/class/user.class.php';
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
     *    @param	string	$page        	Page
     *    @param    int		$selected    	Id mode pre-selectionne
     *    @param    string	$htmlname    	Name of select html field
     *    @param  	string	$filtertype		To filter on field type in llx_c_paiement (array('code'=>xx,'label'=>zz))
     *    @return	void
     */
    function form_modes_reglement($page, $selected='', $htmlname='mode_reglement_id', $filtertype='')
    {
        global $langs;
        if ($htmlname != "none")
        {
            print '<form method="POST" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setmode">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->select_types_paiements($selected,$htmlname,$filtertype);
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
     *	@param  string	$htmlname    	Nom du formulaire select. Si 'none', non modifiable. Example 'remise_id'.
     *	@param	int		$socid			Third party id
     * 	@param	float	$amount			Total amount available
     * 	@param	string	$filter			SQL filter on discounts
     * 	@param	int		$maxvalue		Max value for lines that can be selected
     *  @param  string	$more           More string to add
     *  @return	void
     */
    function form_remise_dispo($page, $selected, $htmlname, $socid, $amount, $filter='', $maxvalue=0, $more='')
    {
        global $conf,$langs;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setabsolutediscount">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td class="nowrap">';
            if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
            {
                if (! $filter || $filter=="fk_facture_source IS NULL") print $langs->trans("CompanyHasAbsoluteDiscount",price($amount,0,$langs,0,0,-1,$conf->currency)).': ';    // If we want deposit to be substracted to payments only and not to total of final invoice
                else print $langs->trans("CompanyHasCreditNote",price($amount,0,$langs,0,0,-1,$conf->currency)).': ';
            }
            else
            {
                if (! $filter || $filter=="fk_facture_source IS NULL OR (fk_facture_source IS NOT NULL AND description='(DEPOSIT)')") print $langs->trans("CompanyHasAbsoluteDiscount",price($amount,0,$langs,0,0,-1,$conf->currency)).': ';
                else print $langs->trans("CompanyHasCreditNote",price($amount,0,$langs,0,0,-1,$conf->currency)).': ';
            }
            $newfilter='fk_facture IS NULL AND fk_facture_line IS NULL';	// Remises disponibles
            if ($filter) $newfilter.=' AND ('.$filter.')';
            $nbqualifiedlines=$this->select_remises($selected,$htmlname,$newfilter,$socid,$maxvalue);
            print '</td>';
            print '<td class="nowrap">';
            if ($nbqualifiedlines > 0)
            {
                print ' &nbsp; <input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("UseLine")).'"';
                if ($filter && $filter != "fk_facture_source IS NULL OR (fk_facture_source IS NOT NULL AND description='(DEPOSIT)')") print ' title="'.$langs->trans("UseCreditNoteInInvoicePayment").'"';
                print '>';
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
     *    @param	string	$page        	Page
     *    @param	Societe	$societe		Third party
     *    @param    int		$selected    	Id contact pre-selectionne
     *    @param    string	$htmlname    	Nom du formulaire select
     *    @return	void
     */
    function form_contacts($page, $societe, $selected='', $htmlname='contactid')
    {
        global $langs, $conf;

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
            	$addcontact = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("AddContact") : $langs->trans("AddContactAddress"));
                print '<font class="error">Cette societe n\'a pas de contact, veuillez en cr�er un avant de faire votre proposition commerciale</font><br>';
                print '<a href="'.DOL_URL_ROOT.'/contact/card.php?socid='.$societe->id.'&amp;action=create&amp;backtoreferer=1">'.$addcontact.'</a>';
            }
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                require_once DOL_DOCUMENT_ROOT .'/contact/class/contact.class.php';
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
     *  Output html select to select thirdparty
     *
     *  @param	string	$page       	Page
     *  @param  string	$selected   	Id preselected
     *  @param  string	$htmlname		Name of HTML select
     *  @param  string	$filter         optional filters criteras
     *	@param	int		$showempty		Add an empty field
     * 	@param	int		$showtype		Show third party type in combolist (customer, prospect or supplier)
     * 	@param	int		$forcecombo		Force to use combo box
     *  @param	array	$events			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     *  @return	void
     */
    function form_thirdparty($page, $selected='', $htmlname='socid', $filter='',$showempty=0, $showtype=0, $forcecombo=0, $events=array())
    {
        global $langs;

        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="set_thirdparty">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            print $this->select_company($selected, $htmlname, $filter, $showempty, $showtype, $forcecombo, $events);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                require_once DOL_DOCUMENT_ROOT .'/societe/class/societe.class.php';
                $soc = new Societe($this->db);
                $soc->fetch($selected);
                print $soc->getNomUrl($langs);
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
     *    @param	string	$selected    preselected currency code
     *    @param    string	$htmlname    name of HTML select list
     *    @return	void
     */
    function select_currency($selected='',$htmlname='currency_id')
    {
        print $this->selectcurrency($selected,$htmlname);
    }

    /**
     *  Retourne la liste des devises, dans la langue de l'utilisateur
     *
     *  @param	string	$selected    preselected currency code
     *  @param  string	$htmlname    name of HTML select list
     * 	@return	string
     */
    function selectCurrency($selected='',$htmlname='currency_id')
    {
        global $conf,$langs,$user;

        $langs->loadCacheCurrencies('');

        $out='';

        if ($selected=='euro' || $selected=='euros') $selected='EUR';   // Pour compatibilite

        $out.= '<select class="flat" name="'.$htmlname.'" id="'.$htmlname.'">';
        foreach ($langs->cache_currencies as $code_iso => $currency)
        {
        	if ($selected && $selected == $code_iso)
        	{
        		$out.= '<option value="'.$code_iso.'" selected="selected">';
        	}
        	else
        	{
        		$out.= '<option value="'.$code_iso.'">';
        	}
        	$out.= $currency['label'];
        	$out.= ' ('.$langs->getCurrencySymbol($code_iso).')';
        	$out.= '</option>';
        }
        $out.= '</select>';
        if ($user->admin) $out.= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
        return $out;
    }


    /**
     *	Load into the cache vat rates of a country
     *
     *	@param	string	$country_code		Country code
     *	@return	int							Nb of loaded lines, 0 if already loaded, <0 if KO
     */
    function load_cache_vatrates($country_code)
    {
    	global $langs;

    	$num = count($this->cache_vatrates);
    	if ($num > 0) return $num;    // Cache deja charge

    	$sql  = "SELECT DISTINCT t.taux, t.recuperableonly";
    	$sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
    	$sql.= " WHERE t.fk_pays = c.rowid";
    	$sql.= " AND t.active = 1";
    	$sql.= " AND c.code IN (".$country_code.")";
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
    				$this->cache_vatrates[$i]['txtva']	= $obj->taux;
    				$this->cache_vatrates[$i]['libtva']	= $obj->taux.'%';
    				$this->cache_vatrates[$i]['nprtva']	= $obj->recuperableonly;
    			}

    			return $num;
    		}
    		else
    		{
    			$this->error = '<font class="error">'.$langs->trans("ErrorNoVATRateDefinedForSellerCountry",$country_code).'</font>';
    			return -1;
    		}
    	}
    	else
    	{
    		$this->error = '<font class="error">'.$this->db->error().'</font>';
    		return -2;
    	}
    }

    /**
     *  Output an HTML select vat rate
     *
     *  @param	string	$htmlname           Nom champ html
     *  @param  float	$selectedrate       Forcage du taux tva pre-selectionne. Mettre '' pour aucun forcage.
     *  @param  Societe	$societe_vendeuse   Objet societe vendeuse
     *  @param  Societe	$societe_acheteuse  Objet societe acheteuse
     *  @param  int		$idprod             Id product
     *  @param  int		$info_bits          Miscellaneous information on line (1 for NPR)
     *  @param  int		$type               ''=Unknown, 0=Product, 1=Service (Used if idprod not defined)
     *                  					Si vendeur non assujeti a TVA, TVA par defaut=0. Fin de regle.
     *                  					Si le (pays vendeur = pays acheteur) alors la TVA par defaut=TVA du produit vendu. Fin de regle.
     *                  					Si (vendeur et acheteur dans Communaute europeenne) et bien vendu = moyen de transports neuf (auto, bateau, avion), TVA par defaut=0 (La TVA doit etre paye par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
	 *                                      Si vendeur et acheteur dans Communauté européenne et acheteur= particulier alors TVA par défaut=TVA du produit vendu. Fin de règle.
	 *                                      Si vendeur et acheteur dans Communauté européenne et acheteur= entreprise alors TVA par défaut=0. Fin de règle.
     *                  					Sinon la TVA proposee par defaut=0. Fin de regle.
     *  @param	bool	$options_only		Return options only (for ajax treatment)
     *  @return	string
     */
    function load_tva($htmlname='tauxtva', $selectedrate='', $societe_vendeuse='', $societe_acheteuse='', $idprod=0, $info_bits=0, $type='', $options_only=false)
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

        // Define list of countries to use to search VAT rates to show
        // First we defined code_country to use to find list
        if (is_object($societe_vendeuse))
        {
            $code_country="'".$societe_vendeuse->country_code."'";
        }
        else
       {
            $code_country="'".$mysoc->country_code."'";   // Pour compatibilite ascendente
        }
        if (! empty($conf->global->SERVICE_ARE_ECOMMERCE_200238EC))    // If option to have vat for end customer for services is on
        {
            if (! $societe_vendeuse->isInEEC() && (! is_object($societe_acheteuse) || ($societe_acheteuse->isInEEC() && ! $societe_acheteuse->isACompany())))
            {
                // We also add the buyer
                if (is_numeric($type))
                {
                    if ($type == 1) // We know product is a service
                    {
                        $code_country.=",'".$societe_acheteuse->country_code."'";
                    }
                }
                else if (! $idprod)  // We don't know type of product
                {
                    $code_country.=",'".$societe_acheteuse->country_code."'";
                }
                else
                {
                    $prodstatic=new Product($this->db);
                    $prodstatic->fetch($idprod);
                    if ($prodstatic->type == Product::TYPE_SERVICE)   // We know product is a service
                    {
                        $code_country.=",'".$societe_acheteuse->country_code."'";
                    }
                }
            }
        }

        // Now we get list
        $num = $this->load_cache_vatrates($code_country);
        if ($num > 0)
        {
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
        		if (empty($conf->global->MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS)) $defaulttx = $this->cache_vatrates[$num-1]['txtva'];
        		else $defaulttx=($conf->global->MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS == 'none' ? '' : $conf->global->MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS);
        	}

        	// Disabled if seller is not subject to VAT
        	$disabled=false; $title='';
        	if (is_object($societe_vendeuse) && $societe_vendeuse->id == $mysoc->id && $societe_vendeuse->tva_assuj == "0")
        	{
        		$title=' title="'.$langs->trans('VATIsNotUsed').'"';
        		$disabled=true;
        	}

        	if (! $options_only) $return.= '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled="disabled"':'').$title.'>';

        	foreach ($this->cache_vatrates as $rate)
        	{
        		// Keep only 0 if seller is not subject to VAT
        		if ($disabled && $rate['txtva'] != 0) continue;

        		$return.= '<option value="'.$rate['txtva'];
        		$return.= $rate['nprtva'] ? '*': '';
        		$return.= '"';
        		if ($rate['txtva'] == $defaulttx && $rate['nprtva'] == $defaultnpr)
        		{
        			$return.= ' selected="selected"';
        		}
        		$return.= '>'.vatrate($rate['libtva']);
        		$return.= $rate['nprtva'] ? ' *': '';
        		$return.= '</option>';

        		$this->tva_taux_value[]		= $rate['txtva'];
        		$this->tva_taux_libelle[]	= $rate['libtva'];
        		$this->tva_taux_npr[]		= $rate['nprtva'];
        	}

        	if (! $options_only) $return.= '</select>';
        }
        else
        {
            $return.= $this->error;
        }

        $this->num = $num;
        return $return;
    }


    /**
     *	Show a HTML widget to input a date or combo list for day, month, years and optionaly hours and minutes.
     *  Fields are preselected with :
     *            	- set_time date (must be a local PHP server timestamp or string date with format 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM')
     *            	- local date in user area, if set_time is '' (so if set_time is '', output may differs when done from two different location)
     *            	- Empty (fields empty), if set_time is -1 (in this case, parameter empty must also have value 1)
     *
     *	@param	timestamp	$set_time 		Pre-selected date (must be a local PHP server timestamp), -1 to keep date not preselected, '' to use current date.
     *	@param	string		$prefix			Prefix for fields name
     *	@param	int			$h				1=Show also hours
     *	@param	int			$m				1=Show also minutes
     *	@param	int			$empty			0=Fields required, 1=Empty inputs are allowed, 2=Empty inputs are allowed for hours only
     *	@param	string		$form_name 		Not used
     *	@param	int			$d				1=Show days, month, years
     * 	@param	int			$addnowbutton	Add a button "Now"
     * 	@param	int			$nooutput		Do not output html string but return it
     * 	@param 	int			$disabled		Disable input fields
     *  @param  int			$fullday        When a checkbox with this html name is on, hour and day are set with 00:00 or 23:59
     * 	@return	mixed						Nothing or string if nooutput is 1
     *  @see	form_date
     */
    function select_date($set_time='', $prefix='re', $h=0, $m=0, $empty=0, $form_name="", $d=1, $addnowbutton=0, $nooutput=0, $disabled=0, $fullday='')
    {
        global $conf,$langs;

        $retstring='';

        if($prefix=='') $prefix='re';
        if($h == '') $h=0;
        if($m == '') $m=0;
        $emptydate=0;
        $emptyhours=0;
    	if ($empty == 1) { $emptydate=1; $emptyhours=1; }
    	if ($empty == 2) { $emptydate=0; $emptyhours=1; }
		$orig_set_time=$set_time;

        if ($set_time === '' && $emptydate == 0)
        {
        	include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
        	$set_time = dol_now('tzuser')-(getServerTimeZoneInt('now')*3600); // set_time must be relative to PHP server timezone
        }

        // Analysis of the pre-selection date
        if (preg_match('/^([0-9]+)\-([0-9]+)\-([0-9]+)\s?([0-9]+)?:?([0-9]+)?/',$set_time,$reg))
        {
            // Date format 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS'
            $syear	= (! empty($reg[1])?$reg[1]:'');
            $smonth	= (! empty($reg[2])?$reg[2]:'');
            $sday	= (! empty($reg[3])?$reg[3]:'');
            $shour	= (! empty($reg[4])?$reg[4]:'');
            $smin	= (! empty($reg[5])?$reg[5]:'');
        }
        elseif (strval($set_time) != '' && $set_time != -1)
        {
            // set_time est un timestamps (0 possible)
            $syear = dol_print_date($set_time, "%Y");
            $smonth = dol_print_date($set_time, "%m");
            $sday = dol_print_date($set_time, "%d");
            if ($orig_set_time != '')
            {
            	$shour = dol_print_date($set_time, "%H");
            	$smin = dol_print_date($set_time, "%M");
            }
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

        $usecalendar='combo';
        if (! empty($conf->use_javascript_ajax) && (empty($conf->global->MAIN_POPUP_CALENDAR) || $conf->global->MAIN_POPUP_CALENDAR != "none")) $usecalendar=empty($conf->global->MAIN_POPUP_CALENDAR)?'eldy':$conf->global->MAIN_POPUP_CALENDAR;
		if ($conf->browser->phone) $usecalendar='combo';

        if ($d)
        {
            // Show date with popup
            if ($usecalendar != 'combo')
            {
            	$formated_date='';
                //print "e".$set_time." t ".$conf->format_date_short;
                if (strval($set_time) != '' && $set_time != -1)
                {
                    //$formated_date=dol_print_date($set_time,$conf->format_date_short);
                    $formated_date=dol_print_date($set_time,$langs->trans("FormatDateShortInput"));  // FormatDateShortInput for dol_print_date / FormatDateShortJavaInput that is same for javascript
                }

                // Calendrier popup version eldy
                if ($usecalendar == "eldy")
                {
                    // Zone de saisie manuelle de la date
                    $retstring.='<input id="'.$prefix.'" name="'.$prefix.'" type="text" size="9" maxlength="11" value="'.$formated_date.'"';
                    $retstring.=($disabled?' disabled="disabled"':'');
                    $retstring.=' onChange="dpChangeDay(\''.$prefix.'\',\''.$langs->trans("FormatDateShortJavaInput").'\'); "';  // FormatDateShortInput for dol_print_date / FormatDateShortJavaInput that is same for javascript
                    $retstring.='>';

                    // Icone calendrier
                    if (! $disabled)
                    {
                        $retstring.='<button id="'.$prefix.'Button" type="button" class="dpInvisibleButtons"';
                        $base=DOL_URL_ROOT.'/core/';
                        $retstring.=' onClick="showDP(\''.$base.'\',\''.$prefix.'\',\''.$langs->trans("FormatDateShortJavaInput").'\',\''.$langs->defaultlang.'\');">'.img_object($langs->trans("SelectDate"),'calendarday','class="datecallink"').'</button>';
                    }
                    else $retstring.='<button id="'.$prefix.'Button" type="button" class="dpInvisibleButtons">'.img_object($langs->trans("Disabled"),'calendarday','class="datecallink"').'</button>';

                    $retstring.='<input type="hidden" id="'.$prefix.'day"   name="'.$prefix.'day"   value="'.$sday.'">'."\n";
                    $retstring.='<input type="hidden" id="'.$prefix.'month" name="'.$prefix.'month" value="'.$smonth.'">'."\n";
                    $retstring.='<input type="hidden" id="'.$prefix.'year"  name="'.$prefix.'year"  value="'.$syear.'">'."\n";
                }
                else
                {
                    print "Bad value of MAIN_POPUP_CALENDAR";
                }
            }
            // Show date with combo selects
            else
			{
                // Day
                $retstring.='<select'.($disabled?' disabled="disabled"':'').' class="flat" name="'.$prefix.'day">';

                if ($emptydate || $set_time == -1)
                {
                    $retstring.='<option value="0" selected="selected">&nbsp;</option>';
                }

                for ($day = 1 ; $day <= 31; $day++)
                {
                    $retstring.='<option value="'.$day.'"'.($day == $sday ? ' selected="selected"':'').'>'.$day.'</option>';
                }

                $retstring.="</select>";

                $retstring.='<select'.($disabled?' disabled="disabled"':'').' class="flat" name="'.$prefix.'month">';
                if ($emptydate || $set_time == -1)
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
                if ($emptydate || $set_time == -1)
                {
                    $retstring.='<input'.($disabled?' disabled="disabled"':'').' placeholder="'.dol_escape_htmltag($langs->trans("Year")).'" class="flat" type="text" size="3" maxlength="4" name="'.$prefix.'year" value="'.$syear.'">';
                }
                else
                {
                    $retstring.='<select'.($disabled?' disabled="disabled"':'').' class="flat" name="'.$prefix.'year">';

                    for ($year = $syear - 5; $year < $syear + 10 ; $year++)
                    {
                        $retstring.='<option value="'.$year.'"'.($year == $syear ? ' selected="true"':'').'>'.$year.'</option>';
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
            if ($emptyhours) $retstring.='<option value="-1">&nbsp;</option>';
            for ($hour = 0; $hour < 24; $hour++)
            {
                if (strlen($hour) < 2) $hour = "0" . $hour;
                $retstring.='<option value="'.$hour.'"'.(($hour == $shour)?' selected="true"':'').'>'.$hour.(empty($conf->dol_optimize_smallscreen)?'':'H').'</option>';
            }
            $retstring.='</select>';
            if (empty($conf->dol_optimize_smallscreen)) $retstring.=":";
        }

        if ($m)
        {
            // Show minutes
            $retstring.='<select'.($disabled?' disabled="disabled"':'').' class="flat '.($fullday?$fullday.'min':'').'" name="'.$prefix.'min">';
            if ($emptyhours) $retstring.='<option value="-1">&nbsp;</option>';
            for ($min = 0; $min < 60 ; $min++)
            {
                if (strlen($min) < 2) $min = "0" . $min;
                $retstring.='<option value="'.$min.'"'.(($min == $smin)?' selected="true"':'').'>'.$min.(empty($conf->dol_optimize_smallscreen)?'':'').'</option>';
            }
            $retstring.='</select>';
        }

        // Add a "Now" button
        if ($conf->use_javascript_ajax && $addnowbutton)
        {
            // Script which will be inserted in the OnClick of the "Now" button
            $reset_scripts = "";

            // Generate the date part, depending on the use or not of the javascript calendar
            if ($usecalendar == "eldy")
            {
                $base=DOL_URL_ROOT.'/core/';
                $reset_scripts .= 'resetDP(\''.$base.'\',\''.$prefix.'\',\''.$langs->trans("FormatDateShortJavaInput").'\',\''.$langs->defaultlang.'\');';
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
            if ($reset_scripts && empty($conf->dol_optimize_smallscreen))
            {
                $retstring.=' <button class="dpInvisibleButtons datenowlink" id="'.$prefix.'ButtonNow" type="button" name="_useless" value="Now" onClick="'.$reset_scripts.'">';
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
     *	@param	string	$prefix   		Prefix for input fields
     *	@param  int		$iSecond  		Default preselected duration (number of seconds or '')
     * 	@param	int		$disabled		Disable the combo box
     * 	@param	string	$typehour		If 'select' then input hour and input min is a combo, if 'text' input hour is in text and input min is a combo
     *  @param	string	$minunderhours	If 1, show minutes selection under the hours
     * 	@param	int		$nooutput		Do not output html string but return it
     *  @return	void
     */
    function select_duration($prefix, $iSecond='', $disabled=0, $typehour='select', $minunderhours=0, $nooutput=0)
    {
    	global $langs;

    	$retstring='';

    	$hourSelected=0; $minSelected=0;

        if ($iSecond != '')
        {
            require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

            $hourSelected = convertSecondToTime($iSecond,'allhour');
            $minSelected = convertSecondToTime($iSecond,'min');
        }

        if ($typehour=='select')
        {
	        $retstring.='<select class="flat" name="'.$prefix.'hour"'.($disabled?' disabled="disabled"':'').'>';
	        for ($hour = 0; $hour < 25; $hour++)	// For a duration, we allow 24 hours
	        {
	            $retstring.='<option value="'.$hour.'"';
	            if ($hourSelected == $hour)
	            {
	                $retstring.=" selected=\"true\"";
	            }
	            $retstring.=">".$hour."</option>";
	        }
	        $retstring.="</select>";
        }
        elseif ($typehour=='text')
        {
        	$retstring.='<input type="text" size="2" name="'.$prefix.'hour"'.($disabled?' disabled="disabled"':'').' class="flat" value="'.($hourSelected?((int) $hourSelected):'').'">';
        }
        else return 'BadValueForParameterTypeHour';

        $retstring.=' '.$langs->trans('HourShort');

        if ($minunderhours) $retstring.='<br>';
        else $retstring.="&nbsp;";

        if ($typehour=='select')
        {
	        $retstring.='<select class="flat" name="'.$prefix.'min"'.($disabled?' disabled="disabled"':'').'>';
	        for ($min = 0; $min <= 55; $min=$min+5)
	        {
	            $retstring.='<option value="'.$min.'"';
	            if ($minSelected == $min) $retstring.=' selected="selected"';
	            $retstring.='>'.$min.'</option>';
	        }
	        $retstring.="</select>";
        }
        elseif ($typehour=='text')
        {
        	$retstring.='<input type="text" size="2" name="'.$prefix.'min"'.($disabled?' disabled="disabled"':'').' class="flat" value="'.($minSelected?((int) $minSelected):'').'">';
        }
        $retstring.=' '.$langs->trans('MinuteShort');
        $retstring.="&nbsp;";

        if (! empty($nooutput)) return $retstring;

        print $retstring;
        return;
    }


    /**
     *	Return a HTML select string, built from an array of key+value.
     *  Note: Do not use returned string into a langs->trans function, content may be entity encoded twice.
     *
     *	@param	string	$htmlname       Name of html select area
     *	@param	array	$array          Array with key+value
     *	@param	string	$id             Preselected key
     *	@param	int		$show_empty     0 no empty value allowed, 1 to add an empty value into list (value is '' or '&nbsp;').
     *	@param	int		$key_in_label   1 pour afficher la key dans la valeur "[key] value"
     *	@param	int		$value_as_key   1 to use value as key
     *	@param  string	$moreparam      Add more parameters onto the select tag
     *	@param  int		$translate		Translate and encode value
     * 	@param	int		$maxlen			Length maximum for labels
     * 	@param	int		$disabled		Html select box is disabled
     *  @param	int		$sort			'ASC' or 'DESC' = Sort on label, '' or 'NONE' = Do not sort
     *  @param	string	$morecss		Add more class to css styles
     *  @param	int		$addjscombo		Add js combo
     * 	@return	string					HTML select string.
     *  @see multiselectarray
     */
    static function selectarray($htmlname, $array, $id='', $show_empty=0, $key_in_label=0, $value_as_key=0, $moreparam='', $translate=0, $maxlen=0, $disabled=0, $sort='', $morecss='', $addjscombo=0)
    {
        global $conf, $langs;

        if ($value_as_key) $array=array_combine($array, $array);

        $out='';

        // Add code for jquery to use multiselect
        if ($addjscombo && empty($conf->dol_use_jmobile) && (! empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT')))
        {
        	$tmpplugin=empty($conf->global->MAIN_USE_JQUERY_MULTISELECT)?constant('REQUIRE_JQUERY_MULTISELECT'):$conf->global->MAIN_USE_JQUERY_MULTISELECT;
        	$out.='<!-- JS CODE TO ENABLE '.$tmpplugin.' for id '.$htmlname.' -->
        			<script type="text/javascript">
        				$(document).ready(function () {
        					$(\'#'.$htmlname.'\').'.$tmpplugin.'({
        					width: \'off\',
        					minimumInputLength: 0
        				});
        			});
        		   </script>';
        }

        $out.='<select id="'.$htmlname.'" '.($disabled?'disabled="disabled" ':'').'class="flat'.($morecss?' '.$morecss:'').'" name="'.$htmlname.'" '.($moreparam?$moreparam:'').'>';

        if ($show_empty)
        {
        	$textforempty=' ';
        	if (! empty($conf->use_javascript_ajax)) $textforempty='&nbsp;';	// If we use ajaxcombo, we need &nbsp; here to avoid to have an empty element that is too small.
            $out.='<option value="-1"'.($id==-1?' selected="selected"':'').'>'.$textforempty.'</option>'."\n";
        }

        if (is_array($array))
        {
        	// Translate
        	if ($translate)
        	{
	        	foreach($array as $key => $value) $array[$key]=$langs->trans($value);
        	}

        	// Sort
			if ($sort == 'ASC') asort($array);
			elseif ($sort == 'DESC') arsort($array);

            foreach($array as $key => $value)
            {
                $out.='<option value="'.$key.'"';
                if ($id != '' && $id == $key) $out.=' selected="selected"';		// To preselect a value
                $out.='>';

                if ($key_in_label)
                {
                    $selectOptionValue = dol_escape_htmltag($key.' - '.($maxlen?dol_trunc($value,$maxlen):$value));
                }
                else
                {
                    $selectOptionValue = dol_escape_htmltag($maxlen?dol_trunc($value,$maxlen):$value);
                    if ($value == '' || $value == '-') $selectOptionValue='&nbsp;';
                }
                $out.=$selectOptionValue;
                $out.="</option>\n";
            }
        }

        $out.="</select>";
        return $out;
    }

    /**
     *	Show a multiselect form from an array.
     *
     *	@param	string	$htmlname		Name of select
     *	@param	array	$array			Array with key+value
     *	@param	array	$selected		Array with key+value preselected
     *	@param	int		$key_in_label   1 pour afficher la key dans la valeur "[key] value"
     *	@param	int		$value_as_key   1 to use value as key
     *	@param  string	$option         Valeur de l'option en fonction du type choisi
     *	@param  int		$translate		Translate and encode value
     *  @param	int		$width			Force width of select box. May be used only when using jquery couch.
     *	@return	string					HTML multiselect string
     *  @see selectarray
     */
    static function multiselectarray($htmlname, $array, $selected=array(), $key_in_label=0, $value_as_key=0, $option='', $translate=0, $width=0)
    {
    	global $conf, $langs;

    	// Add code for jquery to use multiselect
    	if (! empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT'))
    	{
    		$tmpplugin=empty($conf->global->MAIN_USE_JQUERY_MULTISELECT)?constant('REQUIRE_JQUERY_MULTISELECT'):$conf->global->MAIN_USE_JQUERY_MULTISELECT;
   			print '<!-- JS CODE TO ENABLE '.$tmpplugin.' for id '.$htmlname.' -->
    			<script type="text/javascript">
	    			$(document).ready(function () {
    					$(\'#'.$htmlname.'\').'.$tmpplugin.'({
    					});
    				});
    			</script>';
    	}

    	// Try also magic suggest

    	// Add data-role="none" to disable jmobile decoration
    	$out = '<select data-role="none" id="'.$htmlname.'" class="multiselect" multiple="multiple" name="'.$htmlname.'[]"'.$option.($width?' style="width: '.$width.'px"':'').'>'."\n";
    	if (is_array($array) && ! empty($array))
    	{
    		if ($value_as_key) $array=array_combine($array, $array);

    		if (! empty($array))
    		{
    			foreach ($array as $key => $value)
    			{
    				$out.= '<option value="'.$key.'"';
    				if (is_array($selected) && ! empty($selected) && in_array($key, $selected))
    				{
    					$out.= ' selected="selected"';
    				}
    				$out.= '>';

    				$newval = ($translate ? $langs->trans($value) : $value);
    				$newval = ($key_in_label ? $key.' - '.$newval : $newval);
    				$out.= dol_htmlentitiesbr($newval);
    				$out.= '</option>'."\n";
    			}
    		}
    	}
    	$out.= '</select>'."\n";

    	return $out;
    }


    /**
     *	Return an html string with a select combo box to choose yes or no
     *
     *	@param	string		$htmlname		Name of html select field
     *	@param	string		$value			Pre-selected value
     *	@param	int			$option			0 return yes/no, 1 return 1/0
     *	@param	bool		$disabled		true or false
     *  @param	useempty	$useempty		1=Add empty line
     *	@return	mixed						See option
     */
    function selectyesno($htmlname,$value='',$option=0,$disabled=false,$useempty='')
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
        if ($useempty) $resultyesno .= '<option value="-1"'.(($value < 0)?' selected="selected"':'').'></option>'."\n";
        if (("$value" == 'yes') || ($value == 1))
        {
            $resultyesno .= '<option value="'.$yes.'" selected="selected">'.$langs->trans("Yes").'</option>'."\n";
            $resultyesno .= '<option value="'.$no.'">'.$langs->trans("No").'</option>'."\n";
        }
        else
       {
       		$selected=(($useempty && $value != '0' && $value != 'no')?'':' selected="selected"');
            $resultyesno .= '<option value="'.$yes.'">'.$langs->trans("Yes").'</option>'."\n";
            $resultyesno .= '<option value="'.$no.'"'.$selected.'>'.$langs->trans("No").'</option>'."\n";
        }
        $resultyesno .= '</select>'."\n";
        return $resultyesno;
    }



    /**
     *  Return list of export templates
     *
     *  @param	string	$selected          Id modele pre-selectionne
     *  @param  string	$htmlname          Name of HTML select
     *  @param  string	$type              Type of searched templates
     *  @param  int		$useempty          Affiche valeur vide dans liste
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
     *    @param	object	$object			Object to show
     *    @param   string	$paramid   		Name of parameter to use to name the id into the URL link
     *    @param   string	$morehtml  		More html content to output just before the nav bar
     *    @param	int		$shownav	  	Show Condition (navigation is shown if value is 1)
     *    @param   string	$fieldid   		Nom du champ en base a utiliser pour select next et previous
     *    @param   string	$fieldref   	Nom du champ objet ref (object->ref) a utiliser pour select next et previous
     *    @param   string	$morehtmlref  	Code html supplementaire a afficher apres ref
     *    @param   string	$moreparam  	More param to add in nav link url.
     *	  @param	int		$nodbprefix		Do not include DB prefix to forge table name
     * 	  @return  string    				Portion HTML avec ref + boutons nav
     */
    function showrefnav($object,$paramid,$morehtml='',$shownav=1,$fieldid='rowid',$fieldref='ref',$morehtmlref='',$moreparam='',$nodbprefix=0)
    {
    	global $langs,$conf;

        $ret='';
        if (empty($fieldid))  $fieldid='rowid';
        if (empty($fieldref)) $fieldref='ref';

        //print "paramid=$paramid,morehtml=$morehtml,shownav=$shownav,$fieldid,$fieldref,$morehtmlref,$moreparam";
        $object->load_previous_next_ref((isset($object->next_prev_filter)?$object->next_prev_filter:''),$fieldid,$nodbprefix);

        $previous_ref = $object->ref_previous?'<a data-role="button" data-icon="arrow-l" data-iconpos="left" href="'.$_SERVER["PHP_SELF"].'?'.$paramid.'='.urlencode($object->ref_previous).$moreparam.'">'.(empty($conf->dol_use_jmobile)?img_picto($langs->trans("Previous"),'previous.png'):'&nbsp;').'</a>':'';
        $next_ref     = $object->ref_next?'<a data-role="button" data-icon="arrow-r" data-iconpos="right" href="'.$_SERVER["PHP_SELF"].'?'.$paramid.'='.urlencode($object->ref_next).$moreparam.'">'.(empty($conf->dol_use_jmobile)?img_picto($langs->trans("Next"),'next.png'):'&nbsp;').'</a>':'';

        //print "xx".$previous_ref."x".$next_ref;
        if ($previous_ref || $next_ref || $morehtml) {
            $ret.='<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
        }

        $ret.=dol_htmlentities($object->$fieldref);
        if ($morehtmlref)
        {
            $ret.=' '.$morehtmlref;
        }

        if ($morehtml)
        {
            $ret.='</td><td class="paddingrightonly" align="right">'.$morehtml;
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
     *     	@param	Object	$object		Object containing data to retrieve file name
     * 		@param	int		$width			Width of photo
     * 	  	@return string    				HTML code to output barcode
     */
    function showbarcode(&$object,$width=100)
    {
        global $conf;

        //Check if barcode is filled in the card
        if (empty($object->barcode)) return '';

        // Complete object if not complete
        if (empty($object->barcode_type_code) || empty($object->barcode_type_coder))
        {
        	$result = $object->fetch_barcode();
            //Check if fetch_barcode() failed
        	if ($result < 1) return '<!-- ErrorFetchBarcode -->';
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
     *     	@param  object		$object			Object containing data to retrieve file name
     * 		@param	int			$width			Width of photo
     * 	  	@return string    					HTML code to output photo
     */
    static function showphoto($modulepart,$object,$width=100)
    {
        global $conf;

        $entity = (! empty($object->entity) ? $object->entity : $conf->entity);
        $id = (! empty($object->id) ? $object->id : $object->rowid);

        $ret='';$dir='';$file='';$altfile='';$email='';

        if ($modulepart=='societe')
        {
            $dir=$conf->societe->multidir_output[$entity];
            $smallfile=$object->logo;
            $smallfile=preg_replace('/(\.png|\.gif|\.jpg|\.jpeg|\.bmp)/i','_small\\1',$smallfile);
            if ($object->logo) $file=$id.'/logos/thumbs/'.$smallfile;
        }
        else if ($modulepart=='userphoto')
        {
            $dir=$conf->user->dir_output;
            if ($object->photo) $file=get_exdir($id, 2).$object->photo;
            if (! empty($conf->global->MAIN_OLD_IMAGE_LINKS)) $altfile=$object->id.".jpg";	// For backward compatibility
            $email=$object->email;
        }
        else if ($modulepart=='memberphoto')
        {
            $dir=$conf->adherent->dir_output;
            if ($object->photo) $file=get_exdir($id, 2).'photos/'.$object->photo;
            if (! empty($conf->global->MAIN_OLD_IMAGE_LINKS)) $altfile=$object->id.".jpg";	// For backward compatibility
            $email=$object->email;
        }else {
        	$dir=$conf->$modulepart->dir_output;
        	if ($object->photo) $file=get_exdir($id, 2).'photos/'.$object->photo;
        	if (! empty($conf->global->MAIN_OLD_IMAGE_LINKS)) $altfile=$object->id.".jpg";	// For backward compatibility
        	$email=$object->email;
        }

        if ($dir)
        {
            $cache='0';
            if ($file && file_exists($dir."/".$file))
            {
                // TODO Link to large image
                $ret.='<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$entity.'&file='.urlencode($file).'&cache='.$cache.'">';
                $ret.='<img alt="Photo" id="photologo'.(preg_replace('/[^a-z]/i','_',$file)).'" class="photologo" border="0" width="'.$width.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$entity.'&file='.urlencode($file).'&cache='.$cache.'">';
                $ret.='</a>';
            }
            else if ($altfile && file_exists($dir."/".$altfile))
            {
                $ret.='<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$entity.'&file='.urlencode($file).'&cache='.$cache.'">';
                $ret.='<img alt="Photo alt" id="photologo'.(preg_replace('/[^a-z]/i','_',$file)).'" class="photologo" border="0" width="'.$width.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$entity.'&file='.urlencode($altfile).'&cache='.$cache.'">';
                $ret.='</a>';
            }
            else
            {
                if (! empty($conf->gravatar->enabled) && $email)
                {
                    global $dolibarr_main_url_root;
                    $ret.='<!-- Put link to gravatar -->';
                    $ret.='<img alt="Photo found on Gravatar" title="Photo Gravatar.com - email '.$email.'" border="0" width="'.$width.'" src="http://www.gravatar.com/avatar/'.dol_hash($email,3).'?s='.$width.'&d='.urlencode(dol_buildpath('/theme/common/nophoto.jpg',2)).'">';	// gravatar need md5 hash
                }
                else
                {
                    $ret.='<img alt="No photo" border="0" width="'.$width.'" src="'.DOL_URL_ROOT.'/theme/common/nophoto.jpg">';
                }
            }
        }
        else dol_print_error('','Call of showphoto with wrong parameters');

        return $ret;
    }

    /**
     *	Return select list of groups
     *
     *  @param	string	$selected       Id group preselected
     *  @param  string	$htmlname       Field name in form
     *  @param  int		$show_empty     0=liste sans valeur nulle, 1=ajoute valeur inconnue
     *  @param  string	$exclude        Array list of groups id to exclude
     * 	@param	int		$disabled		If select list must be disabled
     *  @param  string	$include        Array list of groups id to include
     * 	@param	int		$enableonly		Array list of groups id to be enabled. All other must be disabled
     * 	@param	int		$force_entity	0 or Id of environment to force
     *  @return	void
     *  @see select_dolusers
     */
    function select_dolgroups($selected='', $htmlname='groupid', $show_empty=0, $exclude='', $disabled=0, $include='', $enableonly='', $force_entity=0)
    {
        global $conf,$user,$langs;

        // Permettre l'exclusion de groupes
        if (is_array($exclude))	$excludeGroups = implode("','",$exclude);
        // Permettre l'inclusion de groupes
        if (is_array($include))	$includeGroups = implode("','",$include);

        $out='';

        // On recherche les groupes
        $sql = "SELECT ug.rowid, ug.nom as name";
        if (! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
        {
            $sql.= ", e.label";
        }
        $sql.= " FROM ".MAIN_DB_PREFIX."usergroup as ug ";
        if (! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entity as e ON e.rowid=ug.entity";
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

        dol_syslog(get_class($this)."::select_dolgroups", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
    		// Enhance with select2
	        if ($conf->use_javascript_ajax)
	        {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
	           	$comboenhancement = ajax_combobox($htmlname);
                $out.= $comboenhancement;
                $nodatarole=($comboenhancement?' data-role="none"':'');
            }

            $out.= '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled="disabled"':'').$nodatarole.'>';

        	$num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
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

                    $out.= $obj->name;
                    if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1)
                    {
                        $out.= " (".$obj->label.")";
                    }

                    $out.= '</option>';
                    $i++;
                }
            }
            else
            {
                if ($show_empty) $out.= '<option value="-1"'.($selected==-1?' selected="selected"':'').'></option>'."\n";
                $out.= '<option value="" disabled="disabled">'.$langs->trans("NoUserGroupDefined").'</option>';
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

