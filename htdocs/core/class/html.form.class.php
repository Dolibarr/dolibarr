<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier        <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien Di Cintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2006      Marc Barilley/Ocebo   <marc@ocebo.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2007      Patrick Raguin        <patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010      Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2011      Herve Prot            <herve.prot@symeos.com>
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

    // Cache arrays
    var $cache_types_paiements=array();
    var $cache_conditions_paiements=array();
    var $cache_availability=array();
    var $cache_demand_reason=array();
    var $cache_type_fees=array();
    var $cache_currencies=array();
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
     * @param	string	$typeofdata		Type of data ('string' by default, 'email', 'numeric:99', 'text' or 'textarea:rows:cols', 'day' or 'datepicker', 'ckeditor:dolibarr_zzz:width:height:?:1:rows:cols', 'select:xxx'...)
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
            if ($perm)
            {
                $tmp=explode(':',$typeofdata);
                $ret.= '<div class="editkey_'.$tmp[0].' '.$tmp[1].'" id="'.$htmlname.'">';
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
            $ret.='<table class="nobordernopadding" width="100%"><tr><td nowrap="nowrap">';
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
     * @param	string	$typeofdata		Type of data ('string' by default, 'email', 'numeric:99', 'text' or 'textarea:rows:cols', 'day' or 'datepicker', 'ckeditor:dolibarr_zzz:width:height:?:1:rows:cols', 'select:xxx'...)
     * @param	string	$editvalue		When in edit mode, use this value as $value instead of value
     * @param	object	$extObject		External object
     * @param	string	$success		Success message
     * @param	string	$moreparam		More param to add on a href URL
     * @return  string					HTML edit field
     */
    function editfieldval($text, $htmlname, $value, $object, $perm, $typeofdata='string', $editvalue='', $extObject=null, $success=null, $moreparam='')
    {
        global $conf,$langs,$db;

        $ret='';

        // When option to edit inline is activated
        // TODO change for compatibility
        if (! empty($conf->global->MAIN_USE_JQUERY_JEDITABLE) && ! preg_match('/^select;/',$typeofdata))
        {
            $ret.=$this->editInPlace($object, $value, $htmlname, $perm, $typeofdata, $editvalue, $extObject, $success);
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
                if (preg_match('/^(string|email|numeric)/',$typeofdata))
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
                    require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
                    $doleditor=new DolEditor($htmlname, ($editvalue?$editvalue:$value), ($tmp[2]?$tmp[2]:''), ($tmp[3]?$tmp[3]:'100'), ($tmp[1]?$tmp[1]:'dolibarr_notes'), 'In', ($tmp[5]?$tmp[5]:0), true, true, ($tmp[6]?$tmp[6]:'20'), ($tmp[7]?$tmp[7]:'100'));
                    $ret.=$doleditor->Create(1);
                }
                $ret.='</td>';
                if ($typeofdata != 'day' && $typeofdata != 'datepicker') $ret.='<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
                $ret.='</tr></table>'."\n";
                $ret.='</form>'."\n";
            }
            else
            {
                if ($typeofdata == 'email')   $ret.=dol_print_email($value,0,0,0,0,1);
                elseif ($typeofdata == 'day' || $typeofdata == 'datepicker') $ret.=dol_print_date($value,'day');
                elseif ($typeofdata == 'text' || $typeofdata == 'textarea')  $ret.=dol_htmlentitiesbr($value);
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
                    $firstline=preg_replace('/<br>.*/','',$tmpcontent);
                    $firstline=preg_replace('/[\n\r].*/','',$firstline);
                    $ret.=$firstline.((strlen($firstline) != strlen($tmpcontent))?'...':'');
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
     * @param	string	$success		Success message
     * @return	string   		      	HTML edit in place
     */
    private function editInPlace($object, $value, $htmlname, $condition, $inputType='textarea', $editvalue=null, $extObject=null, $success=null)
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
                $inputType=$tmp[0]; $inputOption=$tmp[1];
                if (! empty($tmp[2])) $savemethod=$tmp[2];
            }
            else if (preg_match('/^datepicker/',$inputType))
            {
                $tmp=explode(':',$inputType);
                $inputType=$tmp[0]; $inputOption=$tmp[1];
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
            	if (! empty($tmp[1])) $rows=$tmp[1];
            	if (! empty($tmp[2])) $cols=$tmp[2];
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
            if (! empty($success))		$out.= '<input id="success_'.$htmlname.'" value="'.$success.'" type="hidden"/>'."\n";

            $out.= '<div id="viewval_'.$htmlname.'" class="viewval_'.$inputType.($button_only ? ' inactive' : ' active').'">'.$value.'</div>'."\n";
            $out.= '<div id="editval_'.$htmlname.'" class="editval_'.$inputType.($button_only ? ' inactive' : ' active').' hideobject">'.(! empty($editvalue) ? $editvalue : $value).'</div>'."\n";
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
     *	@param	string		$htmltext			Content html of tooltip. Must be HTML/UTF8 encoded.
     *	@param	int			$tooltipon			1=tooltip sur texte, 2=tooltip sur picto, 3=tooltip sur les 2
     *	@param	int			$direction			-1=Le picto est avant, 0=pas de picto, 1=le picto est apres
     *	@param	string		$img				Code img du picto (use img_xxx() function to get it)
     *	@param	string		$extracss			Add a CSS style to td tags
     *	@param	int			$notabs				Do not include table and tr tags
     *	@param	string		$incbefore			Include code before the text
     *	@param	int			$noencodehtmltext	Do not encode into html entity the htmltext
     *	@return	string							Code html du tooltip (texte+picto)
     *	@see	Use function textwithpicto if you can.
     */
    function textwithtooltip($text,$htmltext,$tooltipon=1,$direction=0,$img='',$extracss='',$notabs=0,$incbefore='',$noencodehtmltext=0)
    {
        global $conf;

        if ($incbefore) $text = $incbefore.$text;
        if (! $htmltext) return $text;

        // Sanitize tooltip
        $htmltext=str_replace("\\","\\\\",$htmltext);
        $htmltext=str_replace("\r","",$htmltext);
        $htmltext=str_replace("\n","",$htmltext);

        $htmltext=str_replace('"',"&quot;",$htmltext);
        if ($tooltipon == 2 || $tooltipon == 3) $paramfortooltipimg=' class="classfortooltip'.($extracss?' '.$extracss:'').'" title="'.($noencodehtmltext?$htmltext:dol_escape_htmltag($htmltext,1)).'"'; // Attribut to put on td img tag to store tooltip
        else $paramfortooltipimg =($extracss?' class="'.$extracss.'"':''); // Attribut to put on td text tag
        if ($tooltipon == 1 || $tooltipon == 3) $paramfortooltiptd=' class="classfortooltip'.($extracss?' '.$extracss:'').'" title="'.($noencodehtmltext?$htmltext:dol_escape_htmltag($htmltext,1)).'"'; // Attribut to put on td tag to store tooltip
        else $paramfortooltiptd =($extracss?' class="'.$extracss.'"':''); // Attribut to put on td text tag

        $s="";
        if (empty($notabs)) $s.='<table class="nobordernopadding" summary=""><tr>';
        if ($direction > 0)
        {
            if ($text != '')
            {
                $s.='<td'.$paramfortooltiptd.'>'.$text;
                if ($direction) $s.='&nbsp;';
                $s.='</td>';
            }
            if ($direction) $s.='<td'.$paramfortooltipimg.' valign="top" width="14">'.$img.'</td>';
        }
        else
        {
            if ($direction) $s.='<td'.$paramfortooltipimg.' valign="top" width="14">'.$img.'</td>';
            if ($text != '')
            {
                $s.='<td'.$paramfortooltiptd.'>';
                if ($direction) $s.='&nbsp;';
                $s.=$text.'</td>';
            }
        }
        if (empty($notabs)) $s.='</tr></table>';

        return $s;
    }

    /**
     *	Show a text with a picto and a tooltip on picto
     *
     *	@param	string	$text				Text to show
     *	@param  string	$htmltooltip     	Content of tooltip
     *	@param	int		$direction			1=Icon is after text, -1=Icon is before text, 0=no icon
     * 	@param	string	$type				Type of picto (info, help, warning, superadmin...)
     *  @param  string	$extracss           Add a CSS style to td tags
     *  @param  int		$noencodehtmltext   Do not encode into html entity the htmltext
     * 	@return	string						HTML code of text, picto, tooltip
     */
    function textwithpicto($text,$htmltext,$direction=1,$type='help',$extracss='',$noencodehtmltext=0)
    {
        global $conf;

        if ("$type" == "0") $type='info';	// For backward compatibility

        $alt='';
        // If info or help with no javascript, show only text
        if (empty($conf->use_javascript_ajax))
        {
            if ($type == 'info' || $type == 'help')	return $text;
            else { $alt=$htmltext; $htmltext='';
            }
        }
        // If info or help with smartphone, show only text
        if (! empty($conf->browser->phone))
        {
            if ($type == 'info' || $type == 'help') return $text;
        }
        // Info or help
        if ($type == 'info') 				$img=img_help(0,$alt);
        if ($type == 'help' || $type ==1)	$img=img_help(1,$alt);
        if ($type == 'superadmin') 			$img=img_picto($alt,"redstar");
        if ($type == 'admin')				$img=img_picto($alt,"star");
        // Warnings
        if ($type == 'warning') 			$img=img_warning($alt);

        return $this->textwithtooltip($text,$htmltext,2,$direction,$img,$extracss,0,'',$noencodehtmltext);
    }

    /**
     *  Return combo list of activated countries, into language of user
     *
     *  @param	string	$selected       Id or Code or Label of preselected country
     *  @param  string	$htmlname       Name of html select object
     *  @param  string	$htmloption     Options html on select object
     *  @return	void
     */
    function select_pays($selected='',$htmlname='pays_id',$htmloption='')
    {
        print $this->select_country($selected,$htmlname,$htmloption);
    }

    /**
     *  Return combo list of activated countries, into language of user
     *
     *  @param	string	$selected       Id or Code or Label of preselected country
     *  @param  string	$htmlname       Name of html select object
     *  @param  string	$htmloption     Options html on select object
     *  @return string           		HTML string with select
     */
    function select_country($selected='',$htmlname='pays_id',$htmloption='')
    {
        global $conf,$langs;

        $langs->load("dict");

        $out='';
        $countryArray=array();
        $label=array();

        $sql = "SELECT rowid, code as code_iso, libelle as label";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_pays";
        $sql.= " WHERE active = 1";
        $sql.= " ORDER BY code ASC";

        dol_syslog(get_class($this)."::select_country sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $out.= '<select id="select'.$htmlname.'" class="flat selectpays" name="'.$htmlname.'" '.$htmloption.'>';
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
                    $countryArray[$i]['label']		= ($obj->code_iso && $langs->transnoentitiesnoconv("Country".$obj->code_iso)!="Country".$obj->code_iso?$langs->transnoentitiesnoconv("Country".$obj->code_iso):($obj->label!='-'?$obj->label:''));
                    $label[$i] 	= $countryArray[$i]['label'];
                    $i++;
                }

                array_multisort($label, SORT_ASC, $countryArray);

                foreach ($countryArray as $row)
                {
                    //print 'rr'.$selected.'-'.$row['label'].'-'.$row['code_iso'].'<br>';
                    if ($selected && $selected != '-1' && ($selected == $row['rowid'] || $selected == $row['code_iso'] || $selected == $row['label']) )
                    {
                        $foundselected=true;
                        $out.= '<option value="'.$row['rowid'].'" selected="selected">';
                    }
                    else
                    {
                        $out.= '<option value="'.$row['rowid'].'">';
                    }
                    $out.= $row['label'];
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
     *	Return list of types of lines (product or service)
     * 	Example: 0=product, 1=service, 9=other (for external module)
     *
     *	@param  string	$selected       Preselected type
     *	@param  string	$htmlname       Name of field in html form
     * 	@param	int		$showempty		Add an empty field
     * 	@param	int		$hidetext		Do not show label before combo box
     * 	@param	string	$forceall		Force to show products and services in combo list, whatever are activated modules
     *  @return	void
     */
    function select_type_of_lines($selected='',$htmlname='type',$showempty=0,$hidetext=0,$forceall=0)
    {
        global $db,$langs,$user,$conf;

        // If product & services are enabled or both disabled.
        if ($forceall || ($conf->product->enabled && $conf->service->enabled)
        || (empty($conf->product->enabled) && empty($conf->service->enabled)))
        {
            if (empty($hidetext)) print $langs->trans("Type").': ';
            print '<select class="flat" name="'.$htmlname.'">';
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
            //if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
        }
        if (! $forceall && empty($conf->product->enabled) && $conf->service->enabled)
        {
            print '<input type="hidden" name="'.$htmlname.'" value="1">';
        }
        if (! $forceall && $conf->product->enabled && empty($conf->service->enabled))
        {
            print '<input type="hidden" name="'.$htmlname.'" value="0">';
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

        $sql = "SELECT c.code, c.libelle as label";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_type_fees as c";
        $sql.= " ORDER BY lower(c.libelle) ASC";

        dol_syslog(get_class($this).'::load_cache_types_fees sql='.$sql, LOG_DEBUG);
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
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
    }

    /**
     *  Output html form to select a third party
     *
     *	@param	string	$selected       Preselected type
     *	@param  string	$htmlname       Name of field in form
     *  @param  string	$filter         Optionnal filters criteras
     *	@param	int		$showempty		Add an empty field
     * 	@param	int		$showtype		Show third party type in combolist (customer, prospect or supplier)
     * 	@param	int		$forcecombo		Force to use combo box
     	 @param	array	$event			Event options
     * 	@return	string					HTML string with
     */
    function select_company($selected='',$htmlname='socid',$filter='',$showempty=0, $showtype=0, $forcecombo=0, $event=array())
    {
        global $conf,$user,$langs;

        $out='';

        // On recherche les societes
        $sql = "SELECT s.rowid, s.nom, s.client, s.fournisseur, s.code_client, s.code_fournisseur";
        $sql.= " FROM ".MAIN_DB_PREFIX ."societe as s";
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE s.entity IN (".getEntity('societe', 1).")";
        if ($filter) $sql.= " AND ".$filter;
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        $sql.= " ORDER BY nom ASC";

        dol_syslog(get_class($this)."::select_company sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT && ! $forcecombo)
            {
                //$minLength = (is_numeric($conf->global->COMPANY_USE_SEARCH_TO_SELECT)?$conf->global->COMPANY_USE_SEARCH_TO_SELECT:2);

                $out.= ajax_combobox($htmlname, $event);
            }

            $out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">';
            if ($showempty) $out.= '<option value="-1"></option>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    $label=$obj->nom;
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
                    $i++;
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

        dol_syslog(get_class($this)."::select_remises sql=".$sql);
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
     *    	Return list of all contacts (for a third party or all)
     *
     *    	@param	int		$socid      	Id ot third party or 0 for all
     *    	@param  string	$selected   	Id contact pre-selectionne
     *    	@param  string	$htmlname  	    Name of HTML field ('none' for a not editable field)
     *      @param  int		$show_empty     0=no empty value, 1=add an empty value
     *      @param  string	$exclude        List of contacts id to exclude
     * 		@param	string	$limitto		Disable answers that are not id in this array list
     * 	    @param	string	$showfunction   Add function into label
     * 		@param	string	$moreclass		Add more class to class style
     *		@return	int						<0 if KO, Nb of contact in list if OK
     */
    function select_contacts($socid,$selected='',$htmlname='contactid',$showempty=0,$exclude='',$limitto='',$showfunction=0, $moreclass='')
    {
        global $conf,$langs;

        // On recherche les societes
        $sql = "SELECT sp.rowid, sp.name as name, sp.firstname, sp.poste";
        $sql.= " FROM ".MAIN_DB_PREFIX ."socpeople as sp";
        $sql.= " WHERE sp.entity IN (".getEntity('societe', 1).")";
        if ($socid > 0) $sql.= " AND sp.fk_soc=".$socid;
        $sql.= " ORDER BY sp.name ASC";

        dol_syslog(get_class($this)."::select_contacts sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num=$this->db->num_rows($resql);
            if ($num == 0) return 0;

            if ($htmlname != 'none') print '<select class="flat'.($moreclass?' '.$moreclass:'').'" id="'.$htmlname.'" name="'.$htmlname.'">';
            if ($showempty) print '<option value="0"></option>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                include_once(DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php');
                $contactstatic=new Contact($this->db);

                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);

                    $contactstatic->id=$obj->rowid;
                    $contactstatic->name=$obj->name;
                    $contactstatic->lastname=$obj->name;
                    $contactstatic->firstname=$obj->firstname;

                    if ($htmlname != 'none')
                    {
                        $disabled=0;
                        if (is_array($exclude) && count($exclude) && in_array($obj->rowid,$exclude)) $disabled=1;
                        if (is_array($limitto) && count($limitto) && ! in_array($obj->rowid,$limitto)) $disabled=1;
                        if ($selected && $selected == $obj->rowid)
                        {
                            print '<option value="'.$obj->rowid.'"';
                            if ($disabled) print ' disabled="disabled"';
                            print ' selected="selected">';
                            print $contactstatic->getFullName($langs);
                            if ($showfunction && $obj->poste) print ' ('.$obj->poste.')';
                            print '</option>';
                        }
                        else
                        {
                            print '<option value="'.$obj->rowid.'"';
                            if ($disabled) print ' disabled="disabled"';
                            print '>';
                            print $contactstatic->getFullName($langs);
                            if ($showfunction && $obj->poste) print ' ('.$obj->poste.')';
                            print '</option>';
                        }
                    }
                    else
                    {
                        if ($selected == $obj->rowid)
                        {
                            print $contactstatic->getFullName($langs);
                            if ($showfunction && $obj->poste) print ' ('.$obj->poste.')';
                        }
                    }
                    $i++;
                }
            }
            if ($htmlname != 'none')
            {
                print '</select>';
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
     *	Return select list of users
     *
     *  @param	string	$selected       Id user preselected
     *  @param  string	$htmlname       Field name in form
     *  @param  int		$show_empty     0=liste sans valeur nulle, 1=ajoute valeur inconnue
     *  @param  array	$exclude        Array list of users id to exclude
     * 	@param	int		$disabled		If select list must be disabled
     *  @param  array	$include        Array list of users id to include
     * 	@param	int		$enableonly		Array list of users id to be enabled. All other must be disabled
     *  @param	int		$force_entity	Possibility to force entity
     * 	@return	void
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
     *  @param  array	$include        Array list of users id to include
     * 	@param	int		$enableonly		Array list of users id to be enabled. All other must be disabled
     *  @param	int		$force_entity	Possibility to force entity
     * 	@return	string					HTML select string
     */
    function select_dolusers($selected='',$htmlname='userid',$show_empty=0,$exclude='',$disabled=0,$include='',$enableonly='',$force_entity=0)
    {
        global $conf,$user,$langs;

        // If no preselected user defined, we take current user
        if ($selected < -1 && empty($conf->global->SOCIETE_DISABLE_DEFAULT_SALESREPRESENTATIVE)) $selected=$user->id;

        // Permettre l'exclusion d'utilisateurs
        if (is_array($exclude))	$excludeUsers = implode("','",$exclude);
        // Permettre l'inclusion d'utilisateurs
        if (is_array($include))	$includeUsers = implode("','",$include);

        $out='';

        // On recherche les utilisateurs
        $sql = "SELECT u.rowid, u.name as lastname, u.firstname, u.login, u.admin, u.entity";
        if(! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
        {
            $sql.= ", e.label";
        }
        $sql.= " FROM ".MAIN_DB_PREFIX ."user as u";
        if(! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX ."entity as e on e.rowid=u.entity";
            if ($force_entity) $sql.= " WHERE u.entity IN (0,".$force_entity.")";
            else $sql.= " WHERE u.entity IS NOT NULL";
        }
        else
        {
            $sql.= " WHERE u.entity IN (0,".$conf->entity.")";
        }
        if (is_array($exclude) && $excludeUsers) $sql.= " AND u.rowid NOT IN ('".$excludeUsers."')";
        if (is_array($include) && $includeUsers) $sql.= " AND u.rowid IN ('".$includeUsers."')";
        $sql.= " ORDER BY u.name ASC";

        dol_syslog(get_class($this)."::select_dolusers sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $out.= '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled="disabled"':'').'>';
                if ($show_empty) $out.= '<option value="-1"'.($selected==-1?' selected="selected"':'').'>&nbsp;</option>'."\n";

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
                    $out.= $userstatic->getFullName($langs);

                    if(! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
                    {
                        if ($obj->admin && ! $obj->entity) $out.=" (".$langs->trans("AllEntities").")";
                        else $out.=" (".$obj->label.")";
                    }

                    //if ($obj->admin) $out.= ' *';
                    if ($conf->global->MAIN_SHOW_LOGIN) $out.= ' ('.$obj->login.')';
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


    /**
     *  Return list of products for customer in Ajax if Ajax activated or go to select_produits_do
     *
     *  @param		int			$selected				Preselected products
     *  @param		string		$htmlname				Name of HTML seletc field (must be unique in page)
     *  @param		int			$filtertype				Filter on product type (''=nofilter, 0=product, 1=service)
     *  @param		int			$limit					Limit on number of returned lines
     *  @param		int			$price_level			Level of price to show
     *  @param		int			$status					-1=Return all products, 0=Products not on sell, 1=Products on sell
     *  @param		int			$finished				2=all, 1=finished, 0=raw material
     *  @param		string		$selected_input_value	Value of preselected input text (with ajax)
     *  @return		void
     */
    function select_produits($selected='',$htmlname='productid',$filtertype='',$limit=20,$price_level=0,$status=1,$finished=2,$selected_input_value='',$hidelabel=0)
    {
        global $langs,$conf;

        $price_level = (! empty($price_level) ? $price_level : 0);

        if ($conf->global->PRODUIT_USE_SEARCH_TO_SELECT)
        {
            if ($selected && empty($selected_input_value))
            {
                require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
                $product = new Product($this->db);
                $product->fetch($selected);
                $selected_input_value=$product->ref;
            }
            // mode=1 means customers products
            print ajax_autocompleter($selected, $htmlname, DOL_URL_ROOT.'/product/ajaxproducts.php', 'htmlname='.$htmlname.'&outjson=1&price_level='.$price_level.'&type='.$filtertype.'&mode=1&status='.$status.'&finished='.$finished, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT);
            if (! $hidelabel) print $langs->trans("RefOrLabel").' : ';
            print '<input type="text" size="20" name="search_'.$htmlname.'" id="search_'.$htmlname.'" value="'.$selected_input_value.'" />';
        }
        else
        {
            $this->select_produits_do($selected,$htmlname,$filtertype,$limit,$price_level,'',$status,$finished,0);
        }

        print '<br>';
    }

    /**
     *	Return list of products for a customer
     *
     *	@param      int		$selected       Preselected product
     *	@param      string	$htmlname       Name of select html
     *  @param		string	$filtertype     Filter on product type (''=nofilter, 0=product, 1=service)
     *	@param      int		$limit          Limite sur le nombre de lignes retournees
     *	@param      int		$price_level    Level of price to show
     * 	@param      string	$filterkey      Filter on product
     *	@param		int		$status         -1=Return all products, 0=Products not on sell, 1=Products on sell
     *  @param      int		$finished       Filter on finished field: 2=No filter
     *  @param      int		$disableout     Disable print output
     *  @return     array    				Array of keys for json
     */
    function select_produits_do($selected='',$htmlname='productid',$filtertype='',$limit=20,$price_level=0,$filterkey='',$status=1,$finished=2,$disableout=0)
    {
        global $langs,$conf,$user,$db;

        $sql = "SELECT ";
        $sql.= " p.rowid, p.label, p.ref, p.fk_product_type, p.price, p.price_ttc, p.price_base_type, p.duration, p.stock";
        // Multilang : we add translation
        if ($conf->global->MAIN_MULTILANGS)
        {
            $sql.= ", pl.label as label_translated";
        }
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        // Multilang : we add translation
        if ($conf->global->MAIN_MULTILANGS)
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
        if ($filterkey && $filterkey != '')
        {
            if (! empty($conf->global->PRODUCT_DONOTSEARCH_ANYWHERE))   // Can use index
            {
                $sql.=" AND (p.ref LIKE '".$filterkey."%' OR p.label LIKE '".$filterkey."%'";
                if ($conf->global->MAIN_MULTILANGS) $sql.=" OR pl.label LIKE '".$filterkey."%'";
                $sql.=")";
            }
            else
            {
                $sql.=" AND (p.ref LIKE '%".$filterkey."%' OR p.label LIKE '%".$filterkey."%'";
                if ($conf->global->MAIN_MULTILANGS) $sql.=" OR pl.label LIKE '%".$filterkey."%'";
                $sql.=")";
            }
        }
        $sql.= $db->order("p.ref");
        $sql.= $db->plimit($limit);

        // Build output string
        $outselect='';
        $outjson=array();

        dol_syslog(get_class($this)."::select_produits_do search product sql=".$sql, LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);

            $outselect.='<select class="flat" name="'.$htmlname.'" id="'.$htmlname.'">';
            $outselect.='<option value="0" selected="selected">&nbsp;</option>';

            $i = 0;
            while ($num && $i < $num)
            {
                $outkey='';
                $outval='';
                $outref='';

                $objp = $this->db->fetch_object($result);

                $label=$objp->label;
                if (! empty($objp->label_translated)) $label=$objp->label_translated;
                if ($filterkey && $filterkey != '') $label=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$label,1);

                $outkey=$objp->rowid;
                $outref=$objp->ref;

                $opt = '<option value="'.$objp->rowid.'"';
                $opt.= ($objp->rowid == $selected)?' selected="selected"':'';
                if ($conf->stock->enabled && $objp->fk_product_type == 0 && isset($objp->stock))
                {
                    if ($objp->stock > 0)
                    {
                        $opt.= ' style="background-color:#32CD32; color:#F5F5F5;"';
                    }
                    else if ($objp->stock <= 0)
                    {
                        $opt.= ' style="background-color:#FF0000; color:#F5F5F5;"';
                    }
                }
                $opt.= '>';
                $opt.= $objp->ref.' - '.dol_trunc($label,32).' - ';

                $objRef = $objp->ref;
                if ($filterkey && $filterkey != '') $objRef=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$objRef,1);
                $outval.=$objRef.' - '.dol_trunc($label,32).' - ';

                $found=0;
                $currencytext=$langs->trans("Currency".$conf->currency);
                $currencytextnoent=$langs->transnoentities("Currency".$conf->currency);
                if (dol_strlen($currencytext) > 10) $currencytext=$conf->currency;	// If text is too long, we use the short code
                if (dol_strlen($currencytextnoent) > 10) $currencytextnoent=$conf->currency;   // If text is too long, we use the short code

                // Multiprice
                if ($price_level >= 1)		// If we need a particular price level (from 1 to 6)
                {
                    $sql= "SELECT price, price_ttc, price_base_type ";
                    $sql.= "FROM ".MAIN_DB_PREFIX."product_price ";
                    $sql.= "WHERE fk_product='".$objp->rowid."'";
                    $sql.= " AND price_level=".$price_level;
                    $sql.= " ORDER BY date_price";
                    $sql.= " DESC LIMIT 1";

                    dol_syslog(get_class($this)."::select_produits_do search price for level '.$price_level.' sql=".$sql);
                    $result2 = $this->db->query($sql);
                    if ($result2)
                    {
                        $objp2 = $this->db->fetch_object($result2);
                        if ($objp2)
                        {
                            $found=1;
                            if ($objp2->price_base_type == 'HT')
                            {
                                $opt.= price($objp2->price,1).' '.$currencytext.' '.$langs->trans("HT");
                                $outval.= price($objp2->price,1).' '.$currencytextnoent.' '.$langs->transnoentities("HT");
                            }
                            else
                            {
                                $opt.= price($objp2->price_ttc,1).' '.$currencytext.' '.$langs->trans("TTC");
                                $outval.= price($objp2->price_ttc,1).' '.$currencytextnoent.' '.$langs->transnoentities("TTC");
                            }
                        }
                    }
                    else
                    {
                        dol_print_error($this->db);
                    }
                }

                // If level no defined or multiprice not found, we used the default price
                if (! $found)
                {
                    if ($objp->price_base_type == 'HT')
                    {
                        $opt.= price($objp->price,1).' '.$currencytext.' '.$langs->trans("HT");
                        $outval.= price($objp->price,1).' '.$currencytextnoent.' '.$langs->transnoentities("HT");
                    }
                    else
                    {
                        $opt.= price($objp->price_ttc,1).' '.$currencytext.' '.$langs->trans("TTC");
                        $outval.= price($objp->price_ttc,1).' '.$currencytextnoent.' '.$langs->transnoentities("TTC");
                    }
                }

                if ($conf->stock->enabled && isset($objp->stock) && $objp->fk_product_type == 0)
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

                // Add new entry
                // "key" value of json key array is used by jQuery automatically as selected value
                // "label" value of json key array is used by jQuery automatically as text for combo box
                $outselect.=$opt;
                array_push($outjson,array('key'=>$outkey,'value'=>$outref,'label'=>$outval));

                $i++;
            }

            $outselect.='</select>';

            $this->db->free($result);

            if (empty($disableout)) print $outselect;
            return $outjson;
        }
        else
        {
            dol_print_error($db);
        }
    }

    /**
     *	Return list of products for customer (in Ajax if Ajax activated or go to select_produits_fournisseurs_do)
     *
     *	@param	int		$socid			Id third party
     *	@param  string	$selected        Preselected product
     *	@param  string	$htmlname        Name of HTML Select
     *  @param	string	$filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
     *	@param  string	$filtre          For a SQL filter
     *	@return	void
     */
    function select_produits_fournisseurs($socid,$selected='',$htmlname='productid',$filtertype='',$filtre)
    {
        global $langs,$conf;
        global $price_level, $status, $finished;

        if ($conf->global->PRODUIT_USE_SEARCH_TO_SELECT)
        {
            // mode=2 means suppliers products
            print ajax_autocompleter('', $htmlname, DOL_URL_ROOT.'/product/ajaxproducts.php', ($socid > 0?'socid='.$socid.'&':'').'htmlname='.$htmlname.'&outjson=1&price_level='.$price_level.'&type='.$filtertype.'&mode=2&status='.$status.'&finished='.$finished, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT);
            print $langs->trans("RefOrLabel").' : <input type="text" size="16" name="search_'.$htmlname.'" id="search_'.$htmlname.'">';
            print '<br>';
        }
        else
        {
            $this->select_produits_fournisseurs_do($socid,$selected,$htmlname,$filtertype,$filtre,'',-1,0);
        }
    }

    /**
     *	Return list of suppliers products
     *
     *	@param		socid   		Id societe fournisseur (0 pour aucun filtre)
     *	@param      selected        Produit pre-selectionne
     *	@param      htmlname        Nom de la zone select
     *  @param		filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
     *	@param      filtre          Pour filtre sql
     *	@param      filterkey       Filtre des produits
     *  @param      status          -1=Return all products, 0=Products not on sell, 1=Products on sell
     *  @param      disableout      Disable print output
     *  @return     array           Array of keys for json
     */
    function select_produits_fournisseurs_do($socid,$selected='',$htmlname='productid',$filtertype='',$filtre='',$filterkey='',$statut=-1,$disableout=0)
    {
        global $langs,$conf;

        $langs->load('stocks');

        $sql = "SELECT p.rowid, p.label, p.ref, p.price, p.duration,";
        $sql.= " pfp.ref_fourn, pfp.rowid as idprodfournprice, pfp.price as fprice, pfp.quantity, pfp.unitprice,";
        $sql.= " s.nom";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON p.rowid = pfp.fk_product";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON pfp.fk_soc = s.rowid";
        $sql.= " WHERE p.entity IN (".getEntity('product', 1).")";
        $sql.= " AND p.tobuy = 1";
        if ($socid) $sql.= " AND pfp.fk_soc = ".$socid;
        if (strval($filtertype) != '') $sql.=" AND p.fk_product_type=".$filtertype;
        if (! empty($filtre)) $sql.=" ".$filtre;
        // Add criteria on ref/label
        if ($filterkey && $filterkey != '')
        {
            if (! empty($conf->global->PRODUCT_DONOTSEARCH_ANYWHERE))
            {
                $sql.=" AND (pfp.ref_fourn LIKE '".$filterkey."%' OR p.ref LIKE '".$filterkey."%' OR p.label LIKE '".$filterkey."%')";
            }
            else
            {
                $sql.=" AND (pfp.ref_fourn LIKE '%".$filterkey."%' OR p.ref LIKE '%".$filterkey."%' OR p.label LIKE '%".$filterkey."%')";
            }
        }
        $sql.= " ORDER BY pfp.ref_fourn DESC";

        // Build output string
        $outselect='';
        $outjson=array();

        dol_syslog(get_class($this)."::select_produits_fournisseurs_do sql=".$sql,LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {

            $num = $this->db->num_rows($result);

            $outselect.='<select class="flat" id="select'.$htmlname.'" name="'.$htmlname.'">';
            if (! $selected) $outselect.='<option value="0" selected="selected">&nbsp;</option>';
            else $outselect.='<option value="0">&nbsp;</option>';

            $i = 0;
            while ($i < $num)
            {
                $objp = $this->db->fetch_object($result);

                $outkey=$objp->idprodfournprice;
                $outref=$objp->ref;
                $outval='';

                $opt = '<option value="'.$objp->idprodfournprice.'"';
                if ($selected == $objp->idprodfournprice) $opt.= ' selected="selected"';
                if ($objp->fprice == '') $opt.=' disabled="disabled"';
                $opt.= '>';

                $objRef = $objp->ref;
                if ($filterkey && $filterkey != '') $objRef=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$objRef,1);
                $objRefFourn = $objp->ref_fourn;
                if ($filterkey && $filterkey != '') $objRefFourn=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$objRefFourn,1);
                $label = $objp->label;
                if ($filterkey && $filterkey != '') $label=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$label,1);

                $opt.=$objp->ref.' ('.$objp->ref_fourn.') - ';
                $outval.=$objRef.' ('.$objRefFourn.') - ';
                $opt.=dol_trunc($objp->label,18).' - ';
                $outval.=dol_trunc($label,18).' - ';

                if ($objp->fprice != '') 	// Keep != ''
                {
                    $currencytext=$langs->trans("Currency".$conf->currency);
                    $currencytextnoent=$langs->transnoentities("Currency".$conf->currency);
                    if (dol_strlen($currencytext) > 10) $currencytext=$conf->currency;   // If text is too long, we use the short code
                    if (dol_strlen($currencytextnoent) > 10) $currencytextnoent=$conf->currency;   // If text is too long, we use the short code

                    $opt.= price($objp->fprice).' '.$currencytext."/".$objp->quantity;
                    $outval.= price($objp->fprice).' '.$currencytextnoent."/".$objp->quantity;
                    if ($objp->quantity == 1)
                    {
                        $opt.= strtolower($langs->trans("Unit"));
                        $outval.=strtolower($langs->transnoentities("Unit"));
                    }
                    else
                    {
                        $opt.= strtolower($langs->trans("Units"));
                        $outval.=strtolower($langs->transnoentities("Units"));
                    }
                    if ($objp->quantity >= 1)
                    {
                        $opt.=" (".price($objp->unitprice).' '.$currencytext."/".strtolower($langs->trans("Unit")).")";
                        $outval.=" (".price($objp->unitprice).' '.$currencytextnoent."/".strtolower($langs->transnoentities("Unit")).")";
                    }
                    if ($objp->duration)
                    {
                        $opt .= " - ".$objp->duration;
                        $outval.=" - ".$objp->duration;
                    }
                    if (! $socid)
                    {
                        $opt .= " - ".dol_trunc($objp->nom,8);
                        $outval.=" - ".dol_trunc($objp->nom,8);
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
                $outselect.=$opt;
                array_push($outjson,array('key'=>$outkey,'value'=>$outref,'label'=>$outval));

                $i++;
            }
            $outselect.='</select>';

            $this->db->free($result);

            if (empty($disableout)) print $outselect;
            return $outjson;
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
        $sql.= " s.nom";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON p.rowid = pfp.fk_product";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON pfp.fk_soc = s.rowid";
        $sql.= " WHERE p.entity IN (".getEntity('product', 1).")";
        $sql.= " AND p.tobuy = 1";
        $sql.= " AND s.fournisseur = 1";
        $sql.= " AND p.rowid = ".$productid;
        $sql.= " ORDER BY s.nom, pfp.ref_fourn DESC";

        dol_syslog(get_class($this)."::select_product_fourn_price sql=".$sql,LOG_DEBUG);
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
                $form.= '<option value="0">&nbsp;</option>';

                $i = 0;
                while ($i < $num)
                {
                    $objp = $this->db->fetch_object($result);

                    $opt = '<option value="'.$objp->idprodfournprice.'"';
                    $opt.= '>'.$objp->nom.' - '.$objp->ref_fourn.' - ';

                    if ($objp->quantity == 1)
                    {
                        $opt.= price($objp->fprice);
                        $opt.= $langs->trans("Currency".$conf->currency)."/";
                    }

                    $opt.= $objp->quantity.' ';

                    if ($objp->quantity == 1)
                    {
                        $opt.= strtolower($langs->trans("Unit"));
                    }
                    else
                    {
                        $opt.= strtolower($langs->trans("Units"));
                    }
                    if ($objp->quantity > 1)
                    {
                        $opt.=" - ";
                        $opt.= price($objp->unitprice).$langs->trans("Currency".$conf->currency)."/".strtolower($langs->trans("Unit"));
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
    function select_address($selected='', $socid, $htmlname='address_id',$showempty=0)
    {
        // On recherche les utilisateurs
        $sql = "SELECT a.rowid, a.label";
        $sql .= " FROM ".MAIN_DB_PREFIX ."societe_address as a";
        $sql .= " WHERE a.fk_soc = ".$socid;
        $sql .= " ORDER BY a.label ASC";

        dol_syslog(get_class($this)."::select_address sql=".$sql);
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
        dol_syslog(get_class($this).'::load_cache_conditions_paiements sql='.$sql,LOG_DEBUG);
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
        dol_syslog(get_class($this).'::load_cache_availability sql='.$sql,LOG_DEBUG);
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
    function select_availability($selected='',$htmlname='availid',$filtertype='',$addempty=0)
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
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
    }

    /**
     *      Load into cache cache_demand_reason, array of input reasons
     *
     *      @return     int             Nb of lines loaded, 0 if already loaded, <0 if ko
     */
    function load_cache_demand_reason()
    {
        global $langs;

        if (count($this->cache_demand_reason)) return 0;    // Cache already loaded

        $sql = "SELECT rowid, code, label";
        $sql.= " FROM ".MAIN_DB_PREFIX.'c_input_reason';
        $sql.= " WHERE active=1";
        $sql.= " ORDER BY rowid";
        dol_syslog(get_class($this)."::load_cache_demand_reason sql=".$sql,LOG_DEBUG);
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
            $this->cache_demand_reason=dol_sort_array($tmparray,'label', $order='asc');

            unset($tmparray);
            return 1;
        }
        else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *      Return list of events that triggered an object creation
     *
     *      @param	int		$selected        Id du type d'origine pre-selectionne
     *      @param  string	$htmlname        Nom de la zone select
     *      @param  string	$exclude         To exclude a code value (Example: SRC_PROP)
     *		@param	int		$addempty		Add an empty entry
     *		@return	void
     */
    function select_demand_reason($selected='',$htmlname='demandreasonid',$exclude='',$addempty=0)
    {
        global $langs,$user;

        $this->load_cache_demand_reason();

        print '<select class="flat" name="'.$htmlname.'">';
        if ($addempty) print '<option value="0"'.(empty($selected)?' selected="selected"':'').'>&nbsp;</option>';
        foreach($this->cache_demand_reason as $id => $arraydemandreason)
        {
            if ($arraydemandreason['code']==$exclude) continue;

            if ($selected == $arraydemandreason['id'])
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
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
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
        dol_syslog(get_class($this)."::load_cache_types_paiements sql=".$sql,LOG_DEBUG);
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
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
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
        if ($filtertype == 'CRDT')  	$filterarray=array(0,2);
        elseif ($filtertype == 'DBIT') 	$filterarray=array(1,2);
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
        if ($user->admin && ! $noadmininfo) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
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
     *  Return a HTML select list of bank accounts
     *
     *  @param	string	$selected          Id account pre-selected
     *  @param  string	$htmlname          Name of select zone
     *  @param  int		$statut            Status of searched accounts (0=open, 1=closed)
     *  @param  string	$filtre            To filter list
     *  @param  int		$useempty          1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
     *  @param  string	$moreattrib        To add more attribute on select
     * 	@return	void
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

        dol_syslog(get_class($this)."::select_comptes sql=".$sql);
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
     *    @param	int		$type				Type de categories (0=product, 1=supplier, 2=customer, 3=member)
     *    @param    string	$selected    		Id of category preselected
     *    @param    string	$select_name		HTML field name
     *    @param    int		$maxlength      	Maximum length for labels
     *    @param    int		$excludeafterid 	Exclude all categories after this leaf in category tree.
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
     *     @param	string		$page        	   	Url of page to call if confirmation is OK
     *     @param	string		$title       	   	Title
     *     @param	string		$question    	   	Question
     *     @param 	string		$action      	   	Action
     *	   @param	array		$formquestion	   	An array with forms complementary inputs
     * 	   @param	string		$selectedchoice		"" or "no" or "yes"
     * 	   @param	int			$useajax		   	0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No
     *     @param	int			$height          	Force height of box
     *     @return 	void
     */
    function form_confirm($page, $title, $question, $action, $formquestion='', $selectedchoice="", $useajax=0, $height=170, $width=500)
    {
        print $this->formconfirm($page, $title, $question, $action, $formquestion, $selectedchoice, $useajax, $height, $width);
    }

    /**
     *     Show a confirmation HTML form or AJAX popup
     *
     *     @param  	string		$page        	   	Url of page to call if confirmation is OK
     *     @param	string		$title       	   	Title
     *     @param	string		$question    	   	Question
     *     @param 	string		$action      	   	Action
     *	   @param  	array		$formquestion	   	An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , ))
     * 	   @param  	string		$selectedchoice  	"" or "no" or "yes"
     * 	   @param  	int			$useajax		   	0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=preoutput confirm box with div id=dialog-confirm-xxx
     *     @param  	int			$height          	Force height of box
     *     @return 	string      	    			'ajax' if a confirm ajax popup is shown, 'html' if it's an html form
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
     *	Show a form to select payment conditions
     *
     *  @param	int		$page        	Page
     *  @param  string	$selected    	Id condition pre-selectionne
     *  @param  string	$htmlname    	Name of select html field
     *	@param	int		$addempty		Ajoute entree vide
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
     *  Show a select form to select origin
     *
     *  @param  string	$page        	Page
     *  @param  string	$selected    	Id condition pre-selectionne
     *  @param  string	$htmlname    	Name of select html field
     *	@param	int		$addempty		Add empty entry
     *  @return	void
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
     *    @param	string		$page        Page
     *    @param	string		$selected    Date preselected
     *    @param    string		$htmlname    Name of input html field
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
     *    @param	string	$page        Page
     *    @param    int		$selected    Id mode pre-selectionne
     *    @param    string	$htmlname    Name of select html field
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
    function form_remise_dispo($page, $selected='', $htmlname='remise_id', $socid, $amount, $filter='', $maxvalue=0, $more='')
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
     *    @param	string	$page        Page
     *    @param    int		$selected    Id contact pre-selectionne
     *    @param    string	$htmlname    Nom du formulaire select
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
            	$addcontact = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("AddContact") : $langs->trans("AddContactAddress"));
                print '<font class="error">Cette societe n\'a pas de contact, veuillez en cr�er un avant de faire votre proposition commerciale</font><br>';
                print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$societe->id.'&amp;action=create&amp;backtoreferer=1">'.$addcontact.'</a>';
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
     *    Output html select to select thirdparty
     *
     *    @param	string	$page       Page
     *    @param    string	$selected   Id preselected
     *    @param    string	$htmlname	Name of HTML select
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
            print $this->select_company($selected , $htmlname);
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
     *      Load into the cache all currencies
     *
     *      @return     int             Nb of loaded lines, 0 if already loaded, <0 if KO
     */
    function load_cache_currencies()
    {
    	global $langs;

    	$langs->load("dict");

    	if (count($this->cache_currencies)) return 0;    // Cache deja charge

    	$sql = "SELECT code_iso, label, unicode";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_currencies";
        $sql.= " WHERE active = 1";
        $sql.= " ORDER BY code_iso ASC";

    	dol_syslog(get_class($this).'::load_cache_currencies sql='.$sql, LOG_DEBUG);
    	$resql = $this->db->query($sql);
    	if ($resql)
    	{
    		$num = $this->db->num_rows($resql);
    		$i = 0;
    		while ($i < $num)
    		{
    			$obj = $this->db->fetch_object($resql);

    			// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
    			$this->cache_currencies[$obj->code_iso]['label'] = ($obj->code_iso && $langs->trans("Currency".$obj->code_iso)!="Currency".$obj->code_iso?$langs->trans("Currency".$obj->code_iso):($obj->label!='-'?$obj->label:''));
    			$this->cache_currencies[$obj->code_iso]['unicode'] = (array) dol_json_decode($obj->unicode, true);
    			$label[$obj->code_iso] = $this->cache_currencies[$obj->code_iso]['label'];
    			$i++;
    		}

    		array_multisort($label, SORT_ASC, $this->cache_currencies);

    		return $num;
    	}
    	else
    	{
    		dol_print_error($this->db);
    		return -1;
    	}
    }

    /**
     *  Retourne la liste des devises, dans la langue de l'utilisateur
     *
     *  @param	string	$selected    preselected currency code
     *  @param  string	$htmlname    name of HTML select list
     * 	@return	void
     */
    function selectcurrency($selected='',$htmlname='currency_id')
    {
        global $conf,$langs,$user;

        $langs->load("dict");

        $this->load_cache_currencies();

        $out='';

        if ($selected=='euro' || $selected=='euros') $selected='EUR';   // Pour compatibilite

        $out.= '<select class="flat" name="'.$htmlname.'">';
        foreach ($this->cache_currencies as $code_iso => $currency)
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
        	$out.= ' ('.getCurrencySymbol($code_iso).')';
        	$out.= '</option>';
        }
        $out.= '</select>';
        if ($user->admin) $out.= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
        return $out;
    }

    /**
     *  Output an HTML select vat rate
     *
     *  @param	string	$htmlname           Nom champ html
     *  @param  float	$selectedrate       Forcage du taux tva pre-selectionne. Mettre '' pour aucun forcage.
     *  @param  Societe	$societe_vendeuse   Object societe vendeuse
     *  @param  Societe	$societe_acheteuse  Object societe acheteuse
     *  @param  int		$idprod             Id product
     *  @param  int		$info_bits          Miscellaneous information on line
     *  @param  type               			''=Unknown, 0=Product, 1=Service (Used if idprod not defined)
     *  						            Si vendeur non assujeti a TVA, TVA par defaut=0. Fin de regle.
     *              						Si le (pays vendeur = pays acheteur) alors la TVA par defaut=TVA du produit vendu. Fin de regle.
     *              						Si (vendeur et acheteur dans Communaute europeenne) et bien vendu = moyen de transports neuf (auto, bateau, avion), TVA par defaut=0 (La TVA doit etre paye par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
     *              						Si (vendeur et acheteur dans Communaute europeenne) et bien vendu autre que transport neuf alors la TVA par defaut=TVA du produit vendu. Fin de regle.
     *              						Sinon la TVA proposee par defaut=0. Fin de regle.
     *  @return	void
     *  @deprecated
     */
    function select_tva($htmlname='tauxtva', $selectedrate='', $societe_vendeuse='', $societe_acheteuse='', $idprod=0, $info_bits=0, $type='')
    {
        print $this->load_tva($htmlname, $selectedrate, $societe_vendeuse, $societe_acheteuse, $idprod, $info_bits, $type);
    }

    /**
     *	Load into the cache vat rates of a country
     *
     *	@param		string		Country code
     *	@return		int			Nb of loaded lines, 0 if already loaded, <0 if KO
     */
    function load_cache_vatrates($country_code)
    {
    	if (count($this->cache_vatrates)) return 0;    // Cache deja charge

    	$sql  = "SELECT DISTINCT t.taux, t.recuperableonly";
    	$sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_pays as p";
    	$sql.= " WHERE t.fk_pays = p.rowid";
    	$sql.= " AND t.active = 1";
    	$sql.= " AND p.code IN (".$country_code.")";
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
    			$this->error = '<font class="error">'.$langs->trans("ErrorNoVATRateDefinedForSellerCountry",$code_pays).'</font>';
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
     *                  					Si (vendeur et acheteur dans Communaute europeenne) et bien vendu autre que transport neuf alors la TVA par defaut=TVA du produit vendu. Fin de regle.
     *                  					Sinon la TVA proposee par defaut=0. Fin de regle.
     *  @param	bool	$options_only		Return options only (for ajax treatment)
     *  @return	void
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
        $num = $this->load_cache_vatrates($code_pays);

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
        		$defaulttx = $this->cache_vatrates[$num-1]['txtva'];
        	}

        	if (! $options_only) $return.= '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'">';

        	foreach ($this->cache_vatrates as $rate)
        	{
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

        return $return;
    }


    /**
     *	Show a HTML widget to input a date or combo list for day, month, years and optionnaly hours and minutes
     *  Fields are preselected with :
     *            	- set_time date (Local PHP server timestamps or date format YYYY-MM-DD or YYYY-MM-DD HH:MM)
     *            	- local date of PHP server if set_time is ''
     *            	- Empty (fields empty) if set_time is -1 (in this case, parameter empty must also have value 1)
     *
     *	@param	timestamp	$set_time 		Pre-selected date (must be a local PHP server timestamp)
     *	@param	string		$prefix			Prefix for fields name
     *	@param	int			$h				1=Show also hours
     *	@param	int			$m				1=Show also minutes
     *	@param	int			$empty			0=Fields required, 1=Empty input is allowed
     *	@param	string		$form_name 		Form name. Used by popup dates.
     *	@param	int			$d				1=Show days, month, years
     * 	@param	int			$addnowbutton	Add a button "Now"
     * 	@param	int			$nooutput		Do not output html string but return it
     * 	@param 	int			$disabled		Disable input fields
     *  @param  int			$fullday        When a checkbox with this html name is on, hour and day are set with 00:00 or 23:59
     * 	@return	mixed						Nothing or string if nooutput is 1
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
     *	@param	string	$htmlname       Name of html select area
     *	@param	array	$array          Array with key+value
     *	@param	int		$id             Preselected key
     *	@param	int		$show_empty     1 si il faut ajouter une valeur vide dans la liste, 0 sinon
     *	@param	int		$key_in_label   1 pour afficher la key dans la valeur "[key] value"
     *	@param	int		$value_as_key   1 to use value as key
     *	@param  string	$option         Valeur de l'option en fonction du type choisi
     *	@param  int		$translate		Translate and encode value
     * 	@param	int		$maxlen			Length maximum for labels
     * 	@param	int		$disabled		Html select box is disabled
     * 	@return	string					HTML select string
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
                    if ($value == '' || $value == '-') {
                        $selectOptionValue='&nbsp;';
                    }
                    $out.=$selectOptionValue;
                }
                $out.="</option>\n";
            }
        }

        $out.="</select>";
        return $out;
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

        dol_syslog(get_class($this)."::select_dolgroups sql=".$sql);
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
