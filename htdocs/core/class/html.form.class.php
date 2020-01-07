<?php
/* Copyright (c) 2002-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2017  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2006       Marc Barilley/Ocebo     <marc@ocebo.com>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2007       Patrick Raguin          <patrick.raguin@gmail.com>
 * Copyright (C) 2010       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2010-2014  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2011       Herve Prot              <herve.prot@symeos.com>
 * Copyright (C) 2012-2016  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2012       Cedric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014       Alexandre Spangaro      <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018       Nicolas ZABOURI	        <info@inovea-conseil.com>
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
 *
 *  TODO Merge all function load_cache_* and loadCache* (except load_cache_vatrates) into one generic function loadCacheTable
 */
class Form
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
     * @var string[]    Array of error strings
     */
    public $errors = array();

	public $num;

	// Cache arrays
	public $cache_types_paiements=array();
	public $cache_conditions_paiements=array();
	public $cache_availability=array();
	public $cache_demand_reason=array();
	public $cache_types_fees=array();
	public $cache_vatrates=array();


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
	 * @param   string	$htmlname		Name of select field ('edit' prefix will be added)
	 * @param   string	$preselected    Value to show/edit (not used in this function)
	 * @param	object	$object			Object
	 * @param	boolean	$perm			Permission to allow button to edit parameter. Set it to 0 to have a not edited field.
	 * @param	string	$typeofdata		Type of data ('string' by default, 'email', 'amount:99', 'numeric:99', 'text' or 'textarea:rows:cols', 'datepicker' ('day' do not work, don't know why), 'ckeditor:dolibarr_zzz:width:height:savemethod:1:rows:cols', 'select;xxx[:class]'...)
	 * @param	string	$moreparam		More param to add on a href URL.
	 * @param   int     $fieldrequired  1 if we want to show field as mandatory using the "fieldrequired" CSS.
	 * @param   int     $notabletag     1=Do not output table tags but output a ':', 2=Do not output table tags and no ':', 3=Do not output table tags but output a ' '
	 * @param	string	$paramid		Key of parameter for id ('id', 'socid')
	 * @return	string					HTML edit field
	 */
	function editfieldkey($text, $htmlname, $preselected, $object, $perm, $typeofdata='string', $moreparam='', $fieldrequired=0, $notabletag=0, $paramid='id')
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
				if ($fieldrequired) $ret.='<span class="fieldrequired">';
				$ret.= $langs->trans($text);
				if ($fieldrequired) $ret.='</span>';
				$ret.= '</div>'."\n";
			}
			else
			{
				if ($fieldrequired) $ret.='<span class="fieldrequired">';
				$ret.= $langs->trans($text);
				if ($fieldrequired) $ret.='</span>';
			}
		}
		else
		{
			if (empty($notabletag) && GETPOST('action','aZ09') != 'edit'.$htmlname && $perm) $ret.='<table class="nobordernopadding" width="100%"><tr><td class="nowrap">';
			if ($fieldrequired) $ret.='<span class="fieldrequired">';
			$ret.=$langs->trans($text);
			if ($fieldrequired) $ret.='</span>';
			if (! empty($notabletag)) $ret.=' ';
			if (empty($notabletag) && GETPOST('action','aZ09') != 'edit'.$htmlname && $perm) $ret.='</td>';
			if (empty($notabletag) && GETPOST('action','aZ09') != 'edit'.$htmlname && $perm) $ret.='<td align="right">';
			if ($htmlname && GETPOST('action','aZ09') != 'edit'.$htmlname && $perm) $ret.='<a href="'.$_SERVER["PHP_SELF"].'?action=edit'.$htmlname.'&amp;'.$paramid.'='.$object->id.$moreparam.'">'.img_edit($langs->trans('Edit'), ($notabletag ? 0 : 1)).'</a>';
			if (! empty($notabletag) && $notabletag == 1) $ret.=' : ';
			if (! empty($notabletag) && $notabletag == 3) $ret.=' ';
			if (empty($notabletag) && GETPOST('action','aZ09') != 'edit'.$htmlname && $perm) $ret.='</td>';
			if (empty($notabletag) && GETPOST('action','aZ09') != 'edit'.$htmlname && $perm) $ret.='</tr></table>';
		}

		return $ret;
	}

	/**
	 * Output value of a field for an editable field
	 *
	 * @param	string	$text			Text of label (not used in this function)
	 * @param	string	$htmlname		Name of select field
	 * @param	string	$value			Value to show/edit
	 * @param	object	$object			Object
	 * @param	boolean	$perm			Permission to allow button to edit parameter
	 * @param	string	$typeofdata		Type of data ('string' by default, 'email', 'amount:99', 'numeric:99', 'text' or 'textarea:rows:cols%', 'datepicker' ('day' do not work, don't know why), 'dayhour' or 'datepickerhour', 'ckeditor:dolibarr_zzz:width:height:savemethod:toolbarstartexpanded:rows:cols', 'select;xkey:xval,ykey:yval,...')
	 * @param	string	$editvalue		When in edit mode, use this value as $value instead of value (for example, you can provide here a formated price instead of value). Use '' to use same than $value
	 * @param	object	$extObject		External object
	 * @param	mixed	$custommsg		String or Array of custom messages : eg array('success' => 'MyMessage', 'error' => 'MyMessage')
	 * @param	string	$moreparam		More param to add on the form action href URL
	 * @param   int     $notabletag     Do no output table tags
	 * @param	string	$formatfunc		Call a specific function to output field
	 * @param	string	$paramid		Key of parameter for id ('id', 'socid')
	 * @return  string					HTML edit field
	 */
	function editfieldval($text, $htmlname, $value, $object, $perm, $typeofdata='string', $editvalue='', $extObject=null, $custommsg=null, $moreparam='', $notabletag=0, $formatfunc='', $paramid='id')
	{
		global $conf,$langs,$db;

		$ret='';

		// Check parameters
		if (empty($typeofdata)) return 'ErrorBadParameter';

		// When option to edit inline is activated
		if (! empty($conf->global->MAIN_USE_JQUERY_JEDITABLE) && ! preg_match('/^select;|datehourpicker/',$typeofdata)) // TODO add jquery timepicker and support select
		{
			$ret.=$this->editInPlace($object, $value, $htmlname, $perm, $typeofdata, $editvalue, $extObject, $custommsg);
		}
		else
		{
			if (GETPOST('action','aZ09') == 'edit'.$htmlname)
			{
				$ret.="\n";
				$ret.='<form method="post" action="'.$_SERVER["PHP_SELF"].($moreparam?'?'.$moreparam:'').'">';
				$ret.='<input type="hidden" name="action" value="set'.$htmlname.'">';
				$ret.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				$ret.='<input type="hidden" name="'.$paramid.'" value="'.$object->id.'">';
				if (empty($notabletag)) $ret.='<table class="nobordernopadding centpercent" cellpadding="0" cellspacing="0">';
				if (empty($notabletag)) $ret.='<tr><td>';
				if (preg_match('/^(string|email)/',$typeofdata))
				{
					$tmp=explode(':',$typeofdata);
					$ret.='<input type="text" id="'.$htmlname.'" name="'.$htmlname.'" value="'.($editvalue?$editvalue:$value).'"'.($tmp[1]?' size="'.$tmp[1].'"':'').'>';
				}
				else if (preg_match('/^(numeric|amount)/',$typeofdata))
				{
					$tmp=explode(':',$typeofdata);
					$valuetoshow=price2num($editvalue?$editvalue:$value);
					$ret.='<input type="text" id="'.$htmlname.'" name="'.$htmlname.'" value="'.($valuetoshow!=''?price($valuetoshow):'').'"'.($tmp[1]?' size="'.$tmp[1].'"':'').'>';
				}
				else if (preg_match('/^text/',$typeofdata) || preg_match('/^note/',$typeofdata))
				{
					$tmp=explode(':',$typeofdata);
					$cols=$tmp[2];
					$morealt='';
					if (preg_match('/%/',$cols))
					{
						$morealt=' style="width: '.$cols.'"';
						$cols='';
					}

					$valuetoshow = ($editvalue?$editvalue:$value);

					$ret.='<textarea id="'.$htmlname.'" name="'.$htmlname.'" wrap="soft" rows="'.($tmp[1]?$tmp[1]:'20').'"'.($cols?' cols="'.$cols.'"':'class="quatrevingtpercent"').$morealt.'">';
					$ret.=dol_string_neverthesehtmltags($valuetoshow, array('textarea'));
					$ret.='</textarea>';
				}
				else if ($typeofdata == 'day' || $typeofdata == 'datepicker')
				{
					$ret.=$this->selectDate($value,$htmlname,0,0,1,'form'.$htmlname,1,0);
				}
				else if ($typeofdata == 'dayhour' || $typeofdata == 'datehourpicker')
				{
					$ret.=$this->selectDate($value,$htmlname,1,1,1,'form'.$htmlname,1,0);
				}
				else if (preg_match('/^select;/',$typeofdata))
				{
					 $arraydata=explode(',',preg_replace('/^select;/','',$typeofdata));
					 foreach($arraydata as $val)
					 {
						 $tmp=explode(':',$val);
						 $tmpkey=str_replace('|', ':', $tmp[0]);
						 $arraylist[$tmpkey]=$tmp[1];
					 }
					 $ret.=$this->selectarray($htmlname,$arraylist,$value);
				}
				else if (preg_match('/^ckeditor/',$typeofdata))
				{
					$tmp=explode(':',$typeofdata);		// Example: ckeditor:dolibarr_zzz:width:height:savemethod:toolbarstartexpanded:rows:cols:uselocalbrowser
					require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
					$doleditor=new DolEditor($htmlname, ($editvalue?$editvalue:$value), ($tmp[2]?$tmp[2]:''), ($tmp[3]?$tmp[3]:'100'), ($tmp[1]?$tmp[1]:'dolibarr_notes'), 'In', ($tmp[5]?$tmp[5]:0), (isset($tmp[8])?($tmp[8]?true:false):true), true, ($tmp[6]?$tmp[6]:'20'), ($tmp[7]?$tmp[7]:'100'));
					$ret.=$doleditor->Create(1);
				}
				if (empty($notabletag)) $ret.='</td>';

				if (empty($notabletag)) $ret.='<td align="left">';
				//else $ret.='<div class="clearboth"></div>';
			   	$ret.='<input type="submit" class="button'.(empty($notabletag)?'':' ').'" name="modify" value="'.$langs->trans("Modify").'">';
			   	if (preg_match('/ckeditor|textarea/',$typeofdata) && empty($notabletag)) $ret.='<br>'."\n";
			   	$ret.='<input type="submit" class="button'.(empty($notabletag)?'':' ').'" name="cancel" value="'.$langs->trans("Cancel").'">';
			   	if (empty($notabletag)) $ret.='</td>';

			   	if (empty($notabletag)) $ret.='</tr></table>'."\n";
				$ret.='</form>'."\n";
			}
			else
			{
				if (preg_match('/^(email)/',$typeofdata))              $ret.=dol_print_email($value,0,0,0,0,1);
				elseif (preg_match('/^(amount|numeric)/',$typeofdata)) $ret.=($value != '' ? price($value,'',$langs,0,-1,-1,$conf->currency) : '');
				elseif (preg_match('/^text/',$typeofdata) || preg_match('/^note/',$typeofdata))  $ret.=dol_htmlentitiesbr($value);
				elseif ($typeofdata == 'day' || $typeofdata == 'datepicker') $ret.=dol_print_date($value,'day');
				elseif ($typeofdata == 'dayhour' || $typeofdata == 'datehourpicker') $ret.=dol_print_date($value,'dayhour');
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

				if ($formatfunc && method_exists($object, $formatfunc))
				{
					$ret=$object->$formatfunc($ret);
				}
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
	 * @param	string	$inputType		Type of input ('string', 'numeric', 'datepicker' ('day' do not work, don't know why), 'textarea:rows:cols', 'ckeditor:dolibarr_zzz:width:height:?:1:rows:cols', 'select:loadmethod:savemethod:buttononly')
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
		if (preg_match('/^text/',$inputType)) $value = dol_nl2br($value);
		else if (preg_match('/^numeric/',$inputType)) $value = price($value);
		else if ($inputType == 'day' || $inputType == 'datepicker') $value = dol_print_date($value, 'day');

		if ($condition)
		{
			$element		= false;
			$table_element	= false;
			$fk_element		= false;
			$loadmethod		= false;
			$savemethod		= false;
			$ext_element	= false;
			$button_only	= false;
			$inputOption    = '';

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
				$out.= '<input id="width_'.$htmlname.'" value="'.$inputOption.'" type="hidden"/>'."\n";
			}
			else if ((preg_match('/^day$/',$inputType)) || (preg_match('/^datepicker/',$inputType)) || (preg_match('/^datehourpicker/',$inputType)))
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
	 *	Show a text and picto with tooltip on text or picto.
	 *  Can be called by an instancied $form->textwithtooltip or by a static call Form::textwithtooltip
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
	 *  @param  string      $tooltiptrigger		''=Tooltip on hover, 'abc'=Tooltip on click (abc is a unique key)
	 *  @param	int			$forcenowrap		Force no wrap between text and picto (works with notabs=2 only)
	 *	@return	string							Code html du tooltip (texte+picto)
	 *	@see	Use function textwithpicto if you can.
	 *  TODO Move this as static as soon as everybody use textwithpicto or @Form::textwithtooltip
	 */
	function textwithtooltip($text, $htmltext, $tooltipon = 1, $direction = 0, $img = '', $extracss = '', $notabs = 2, $incbefore = '', $noencodehtmltext = 0, $tooltiptrigger='', $forcenowrap=0)
	{
		global $conf;

		if ($incbefore) $text = $incbefore.$text;
		if (! $htmltext) return $text;

		$tag='td';
		if ($notabs == 2) $tag='div';
		if ($notabs == 3) $tag='span';
		// Sanitize tooltip
		//$htmltext=str_replace("\\","\\\\",$htmltext);
		$htmltext=str_replace("\r","",$htmltext);
		$htmltext=str_replace("\n","",$htmltext);

		$extrastyle='';
		if ($direction < 0) { $extracss=($extracss?$extracss.' ':'').'inline-block'; $extrastyle='padding: 0px; padding-left: 3px !important;'; }
		if ($direction > 0) { $extracss=($extracss?$extracss.' ':'').'inline-block'; $extrastyle='padding: 0px; padding-right: 3px !important;'; }

		$classfortooltip='classfortooltip';

		$s='';$textfordialog='';

		if ($tooltiptrigger == '')
		{
			$htmltext=str_replace('"',"&quot;",$htmltext);
		}
		else
		{
			$classfortooltip='classfortooltiponclick';
			$textfordialog.='<div style="display: none;" id="idfortooltiponclick_'.$tooltiptrigger.'" class="classfortooltiponclicktext">'.$htmltext.'</div>';
		}
		if ($tooltipon == 2 || $tooltipon == 3)
		{
			$paramfortooltipimg=' class="'.$classfortooltip.' inline-block'.($extracss?' '.$extracss:'').'" style="padding: 0px;'.($extrastyle?' '.$extrastyle:'').'"';
			if ($tooltiptrigger == '') $paramfortooltipimg.=' title="'.($noencodehtmltext?$htmltext:dol_escape_htmltag($htmltext,1)).'"'; // Attribut to put on img tag to store tooltip
			else $paramfortooltipimg.=' dolid="'.$tooltiptrigger.'"';
		}
		else $paramfortooltipimg =($extracss?' class="'.$extracss.'"':'').($extrastyle?' style="'.$extrastyle.'"':''); // Attribut to put on td text tag
		if ($tooltipon == 1 || $tooltipon == 3)
		{
			$paramfortooltiptd=' class="'.($tooltipon == 3 ? 'cursorpointer ' : '').$classfortooltip.' inline-block'.($extracss?' '.$extracss:'').'" style="padding: 0px;'.($extrastyle?' '.$extrastyle:'').'" ';
			if ($tooltiptrigger == '') $paramfortooltiptd.=' title="'.($noencodehtmltext?$htmltext:dol_escape_htmltag($htmltext,1)).'"'; // Attribut to put on td tag to store tooltip
			else $paramfortooltiptd.=' dolid="'.$tooltiptrigger.'"';
		}
		else $paramfortooltiptd =($extracss?' class="'.$extracss.'"':'').($extrastyle?' style="'.$extrastyle.'"':''); // Attribut to put on td text tag
		if (empty($notabs)) $s.='<table class="nobordernopadding" summary=""><tr style="height: auto;">';
		elseif ($notabs == 2) $s.='<div class="inline-block'.($forcenowrap?' nowrap':'').'">';
		// Define value if value is before
		if ($direction < 0) {
			$s.='<'.$tag.$paramfortooltipimg;
			if ($tag == 'td') {
				$s .= ' valign="top" width="14"';
			}
			$s.= '>'.$textfordialog.$img.'</'.$tag.'>';
		}
		// Use another method to help avoid having a space in value in order to use this value with jquery
		// Define label
		if ((string) $text != '') $s.='<'.$tag.$paramfortooltiptd.'>'.$text.'</'.$tag.'>';
		// Define value if value is after
		if ($direction > 0) {
			$s.='<'.$tag.$paramfortooltipimg;
			if ($tag == 'td') $s .= ' valign="middle" width="14"';
			$s.= '>'.$textfordialog.$img.'</'.$tag.'>';
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
	 * 	@param	string	$type				Type of picto ('info', 'help', 'warning', 'superadmin', 'mypicto@mymodule', ...) or image filepath or 'none'
	 *  @param  string	$extracss           Add a CSS style to td, div or span tag
	 *  @param  int		$noencodehtmltext   Do not encode into html entity the htmltext
	 *  @param	int		$notabs				0=Include table and tr tags, 1=Do not include table and tr tags, 2=use div, 3=use span
	 *  @param  string  $tooltiptrigger     ''=Tooltip on hover, 'abc'=Tooltip on click (abc is a unique key)
	 *  @param	int		$forcenowrap		Force no wrap between text and picto (works with notabs=2 only)
	 * 	@return	string						HTML code of text, picto, tooltip
	 */
	function textwithpicto($text, $htmltext, $direction = 1, $type = 'help', $extracss = '', $noencodehtmltext = 0, $notabs = 2, $tooltiptrigger='', $forcenowrap=0)
	{
		global $conf, $langs;

		$alt = '';
		if ($tooltiptrigger) $alt=$langs->transnoentitiesnoconv("ClickToShowHelp");

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

		// If info or help with smartphone, show only text (tooltip hover can't works)
		if (! empty($conf->dol_no_mouse_hover) && empty($tooltiptrigger))
		{
			if ($type == 'info' || $type == 'help') return $text;
		}
		// If info or help with smartphone, show only text (tooltip on click does not works with dialog on smaprtphone)
		//if (! empty($conf->dol_no_mouse_hover) && ! empty($tooltiptrigger))
		//{
			//if ($type == 'info' || $type == 'help') return '<a href="'..'">'.$text.''</a>';
		//}

		$img='';
		if ($type == 'info') $img = img_help(0, $alt);
		elseif ($type == 'help') $img = img_help(($tooltiptrigger != '' ? 2 : 1), $alt);
		elseif ($type == 'superadmin') $img = img_picto($alt, 'redstar');
		elseif ($type == 'admin') $img = img_picto($alt, 'star');
		elseif ($type == 'warning') $img = img_warning($alt);
		elseif ($type != 'none') $img = img_picto($alt, $type);   // $type can be an image path

		return $this->textwithtooltip($text, $htmltext, (($tooltiptrigger && ! $img)?3:2), $direction, $img, $extracss, $notabs, '', $noencodehtmltext, $tooltiptrigger, $forcenowrap);
	}

	/**
	 * Generate select HTML to choose massaction
	 *
	 * @param	string	$selected		Value auto selected when at least one record is selected. Not a preselected value. Use '0' by default.
	 * @param	array		$arrayofaction	array('code'=>'label', ...). The code is the key stored into the GETPOST('massaction') when submitting action.
	 * @param   int     $alwaysvisible  1=select button always visible
	 * @return	string|void					Select list
	 */
	function selectMassAction($selected, $arrayofaction, $alwaysvisible=0)
	{
		global $conf,$langs,$hookmanager;


		$disabled=0;
		$ret='<div class="centpercent center">';
		$ret.='<select class="flat'.(empty($conf->use_javascript_ajax)?'':' hideobject').' massaction massactionselect" name="massaction"'.($disabled?' disabled="disabled"':'').'>';

		// Complete list with data from external modules. THe module can use $_SERVER['PHP_SELF'] to know on which page we are, or use the $parameters['currentcontext'] completed by executeHooks.
		$parameters=array();
		$reshook=$hookmanager->executeHooks('addMoreMassActions',$parameters);    // Note that $action and $object may have been modified by hook
		// check if there is a mass action
		if (count($arrayofaction) == 0 && empty($hookmanager->resPrint)) return;
		if (empty($reshook))
		{
			$ret.='<option value="0"'.($disabled?' disabled="disabled"':'').'>-- '.$langs->trans("SelectAction").' --</option>';
			foreach($arrayofaction as $code => $label)
			{
				$ret.='<option value="'.$code.'"'.($disabled?' disabled="disabled"':'').'>'.$label.'</option>';
			}
		}
		$ret.=$hookmanager->resPrint;

		$ret.='</select>';
		// Warning: if you set submit button to disabled, post using 'Enter' will no more work if there is no another input submit. So we add a hidden button
		$ret.='<input type="submit" name="confirmmassactioninvisible" style="display: none" tabindex="-1">';	// Hidden button BEFORE so it is the one used when we submit with ENTER.
		$ret.='<input type="submit" disabled name="confirmmassaction" class="button'.(empty($conf->use_javascript_ajax)?'':' hideobject').' massaction massactionconfirmed" value="'.dol_escape_htmltag($langs->trans("Confirm")).'">';
		$ret.='</div>';

		if (! empty($conf->use_javascript_ajax))
		{
			$ret.='<!-- JS CODE TO ENABLE mass action select -->
    		<script type="text/javascript">
        		function initCheckForSelect(mode)	/* mode is 0 during init of page or click all, 1 when we click on 1 checkbox */
        		{
        			atleastoneselected=0;
    	    		jQuery(".checkforselect").each(function( index ) {
    	  				/* console.log( index + ": " + $( this ).text() ); */
    	  				if ($(this).is(\':checked\')) atleastoneselected++;
    	  			});
					console.log("initCheckForSelect mode="+mode+" atleastoneselected="+atleastoneselected);
    	  			if (atleastoneselected || '.$alwaysvisible.')
    	  			{
    	  				jQuery(".massaction").show();
        			    '.($selected ? 'if (atleastoneselected) { jQuery(".massactionselect").val("'.$selected.'"); jQuery(".massactionconfirmed").prop(\'disabled\', false); }' : '').'
        			    '.($selected ? 'if (! atleastoneselected) { jQuery(".massactionselect").val("0"); jQuery(".massactionconfirmed").prop(\'disabled\', true); } ' : '').'
    	  			}
    	  			else
    	  			{
    	  				jQuery(".massaction").hide();
    	            }
        		}

        	jQuery(document).ready(function () {
        		initCheckForSelect(0);
        		jQuery(".checkforselect").click(function() {
        			initCheckForSelect(1);
    	  		});
    	  		jQuery(".massactionselect").change(function() {
        			var massaction = $( this ).val();
        			var urlform = $( this ).closest("form").attr("action").replace("#show_files","");
        			if (massaction == "builddoc")
                    {
                        urlform = urlform + "#show_files";
    	            }
        			$( this ).closest("form").attr("action", urlform);
                    console.log("we select a mass action "+massaction+" - "+urlform);
        	        /* Warning: if you set submit button to disabled, post using Enter will no more work if there is no other button */
        			if ($(this).val() != \'0\')
    	  			{
    	  				jQuery(".massactionconfirmed").prop(\'disabled\', false);
    	  			}
    	  			else
    	  			{
    	  				jQuery(".massactionconfirmed").prop(\'disabled\', true);
    	  			}
    	        });
        	});
    		</script>
        	';
		}

		return $ret;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Return combo list of activated countries, into language of user
	 *
	 *  @param	string	$selected       	Id or Code or Label of preselected country
	 *  @param  string	$htmlname       	Name of html select object
	 *  @param  string	$htmloption     	Options html on select object
	 *  @param	integer	$maxlength			Max length for labels (0=no limit)
	 *  @param	string	$morecss			More css class
	 *  @param	string	$usecodeaskey		''=Use id as key (default), 'code3'=Use code on 3 alpha as key, 'code2"=Use code on 2 alpha as key
	 *  @param	int		$showempty			Show empty choice
	 *  @param	int		$disablefavorites	1=Disable favorites,
	 *  @param	int		$addspecialentries	1=Add dedicated entries for group of countries (like 'European Economic Community', ...)
	 *  @return string           			HTML string with select
	 */
	function select_country($selected='', $htmlname='country_id', $htmloption='', $maxlength=0, $morecss='minwidth300', $usecodeaskey='', $showempty=1, $disablefavorites=0, $addspecialentries=0)
	{
        // phpcs:enable
		global $conf,$langs,$mysoc;

		$langs->load("dict");

		$out='';
		$countryArray=array();
		$favorite=array();
		$label=array();
		$atleastonefavorite=0;

		$sql = "SELECT rowid, code as code_iso, code_iso as code_iso3, label, favorite";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_country";
		$sql.= " WHERE active > 0";
		//$sql.= " ORDER BY code ASC";

		dol_syslog(get_class($this)."::select_country", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$out.= '<select id="select'.$htmlname.'" class="flat maxwidth200onsmartphone selectcountry'.($morecss?' '.$morecss:'').'" name="'.$htmlname.'" '.$htmloption.'>';
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

				if (empty($disablefavorites)) array_multisort($favorite, SORT_DESC, $label, SORT_ASC, $countryArray);
				else $countryArray = dol_sort_array($countryArray, 'label');

				if ($showempty)
				{
					$out.='<option value="">&nbsp;</option>'."\n";
				}

				if ($addspecialentries)	// Add dedicated entries for groups of countries
				{
					//if ($showempty) $out.= '<option value="" disabled class="selectoptiondisabledwhite">--------------</option>';
					$out.= '<option value="special_allnotme"'.($selected == 'special_allnotme' ? ' selected' : '').'>'.$langs->trans("CountriesExceptMe", $langs->transnoentitiesnoconv("Country".$mysoc->country_code)).'</option>';
					$out.= '<option value="special_eec"'.($selected == 'special_eec' ? ' selected' : '').'>'.$langs->trans("CountriesInEEC").'</option>';
					if ($mysoc->isInEEC()) $out.= '<option value="special_eecnotme"'.($selected == 'special_eecnotme' ? ' selected' : '').'>'.$langs->trans("CountriesInEECExceptMe", $langs->transnoentitiesnoconv("Country".$mysoc->country_code)).'</option>';
					$out.= '<option value="special_noteec"'.($selected == 'special_noteec' ? ' selected' : '').'>'.$langs->trans("CountriesNotInEEC").'</option>';
					$out.= '<option value="" disabled class="selectoptiondisabledwhite">--------------</option>';
				}

				foreach ($countryArray as $row)
				{
					//if (empty($showempty) && empty($row['rowid'])) continue;
					if (empty($row['rowid'])) continue;

					if (empty($disablefavorites) && $row['favorite'] && $row['code_iso']) $atleastonefavorite++;
					if (empty($row['favorite']) && $atleastonefavorite)
					{
						$atleastonefavorite=0;
						$out.= '<option value="" disabled class="selectoptiondisabledwhite">--------------</option>';
					}
					if ($selected && $selected != '-1' && ($selected == $row['rowid'] || $selected == $row['code_iso'] || $selected == $row['code_iso3'] || $selected == $row['label']) )
					{
						$foundselected=true;
						$out.= '<option value="'.($usecodeaskey?($usecodeaskey=='code2'?$row['code_iso']:$row['code_iso3']):$row['rowid']).'" selected>';
					}
					else
					{
						$out.= '<option value="'.($usecodeaskey?($usecodeaskey=='code2'?$row['code_iso']:$row['code_iso3']):$row['rowid']).'">';
					}
					if ($row['label']) $out.= dol_trunc($row['label'],$maxlength,'middle');
					else $out.= '&nbsp;';
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

		// Make select dynamic
		include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		$out .= ajax_combobox('select'.$htmlname);

		return $out;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Return select list of incoterms
	 *
	 *  @param	string	$selected       		Id or Code of preselected incoterm
	 *  @param	string	$location_incoterms     Value of input location
	 *  @param	string	$page       			Defined the form action
	 *  @param  string	$htmlname       		Name of html select object
	 *  @param  string	$htmloption     		Options html on select object
	 * 	@param	int		$forcecombo				Force to load all values and output a standard combobox (with no beautification)
	 *  @param	array	$events					Event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
	 *  @return string           				HTML string with select and input
	 */
	function select_incoterms($selected='', $location_incoterms='', $page='', $htmlname='incoterm_id', $htmloption='', $forcecombo=1, $events=array())
	{
        // phpcs:enable
		global $conf,$langs;

		$langs->load("dict");

		$out='';
		$incotermArray=array();

		$sql = "SELECT rowid, code";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_incoterms";
		$sql.= " WHERE active > 0";
		$sql.= " ORDER BY code ASC";

		dol_syslog(get_class($this)."::select_incoterm", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($conf->use_javascript_ajax && ! $forcecombo)
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

			$out.= '<select id="'.$htmlname.'" class="flat selectincoterm minwidth100imp noenlargeonsmartphone" name="'.$htmlname.'" '.$htmloption.'>';
			$out.= '<option value="0">&nbsp;</option>';
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
						$out.= '<option value="'.$row['rowid'].'" selected>';
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

			$out .= '<input id="location_incoterms" class="maxwidth100onsmartphone" name="location_incoterms" value="'.$location_incoterms.'">';

			if (!empty($page))
			{
				$out .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'"></form>';
			}
		}
		else
		{
			dol_print_error($this->db);
		}

		return $out;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
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
        // phpcs:enable
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
				if ($selected == -1) print ' selected';
				print '>&nbsp;</option>';
			}

			print '<option value="0"';
			if (0 == $selected) print ' selected';
			print '>'.$langs->trans("Product");

			print '<option value="1"';
			if (1 == $selected) print ' selected';
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Load into cache cache_types_fees, array of types of fees
	 *
	 *	@return     int             Nb of lines loaded, <0 if KO
	 */
	function load_cache_types_fees()
	{
        // phpcs:enable
		global $langs;

		$num = count($this->cache_types_fees);
		if ($num > 0) return 0;    // Cache already loaded

		dol_syslog(__METHOD__, LOG_DEBUG);

		$langs->load("trips");

		$sql = "SELECT c.code, c.label";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_type_fees as c";
		$sql.= " WHERE active > 0";

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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
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
        // phpcs:enable
		global $user, $langs;

		dol_syslog(__METHOD__." selected=".$selected.", htmlname=".$htmlname, LOG_DEBUG);

		$this->load_cache_types_fees();

		print '<select class="flat" name="'.$htmlname.'">';
		if ($showempty)
		{
			print '<option value="-1"';
			if ($selected == -1) print ' selected';
			print '>&nbsp;</option>';
		}

		foreach($this->cache_types_fees as $key => $value)
		{
			print '<option value="'.$key.'"';
			if ($key == $selected) print ' selected';
			print '>';
			print $value;
			print '</option>';
		}

		print '</select>';
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Return HTML code to select a company.
	 *
	 *  @param		int			$selected				Preselected products
	 *  @param		string		$htmlname				Name of HTML select field (must be unique in page)
	 *  @param		int			$filter					Filter on thirdparty
	 *  @param		int			$limit					Limit on number of returned lines
	 *  @param		array		$ajaxoptions			Options for ajax_autocompleter
	 * 	@param		int			$forcecombo				Force to load all values and output a standard combobox (with no beautification)
	 *  @return		string								Return select box for thirdparty.
	 *  @deprecated	3.8 Use select_company instead. For exemple $form->select_thirdparty(GETPOST('socid'),'socid','',0) => $form->select_company(GETPOST('socid'),'socid','',1,0,0,array(),0)
	 */
	function select_thirdparty($selected='', $htmlname='socid', $filter='', $limit=20, $ajaxoptions=array(), $forcecombo=0)
	{
        // phpcs:enable
   		return $this->select_thirdparty_list($selected,$htmlname,$filter,1,0,$forcecombo,array(),'',0, $limit);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Output html form to select a third party
	 *
	 *	@param	string	$selected       		Preselected type
	 *	@param  string	$htmlname       		Name of field in form
	 *  @param  string	$filter         		optional filters criteras (example: 's.rowid <> x', 's.client IN (1,3)')
	 *	@param	string	$showempty				Add an empty field (Can be '1' or text key to use on empty line like 'SelectThirdParty')
	 * 	@param	int		$showtype				Show third party type in combolist (customer, prospect or supplier)
	 * 	@param	int		$forcecombo				Force to load all values and output a standard combobox (with no beautification)
	 *  @param	array	$events					Ajax event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
	 *	@param	int		$limit					Maximum number of elements
	 *  @param	string	$morecss				Add more css styles to the SELECT component
	 *	@param  string	$moreparam      		Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
	 *	@param	string	$selected_input_value	Value of preselected input text (for use with ajax)
	 *  @param	int		$hidelabel				Hide label (0=no, 1=yes, 2=show search icon (before) and placeholder, 3 search icon after)
	 *  @param	array	$ajaxoptions			Options for ajax_autocompleter
	 * 	@param  bool	$multiple				add [] in the name of element and add 'multiple' attribut (not working with ajax_autocompleter)
	 * 	@return	string							HTML string with select box for thirdparty.
	 */
	function select_company($selected='', $htmlname='socid', $filter='', $showempty='', $showtype=0, $forcecombo=0, $events=array(), $limit=0, $morecss='minwidth100', $moreparam='', $selected_input_value='', $hidelabel=1, $ajaxoptions=array(), $multiple=false)
	{
        // phpcs:enable
		global $conf,$user,$langs;

		$out='';

		if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT) && ! $forcecombo)
		{
			// No immediate load of all database
			$placeholder='';
			if ($selected && empty($selected_input_value))
			{
				require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
				$societetmp = new Societe($this->db);
				$societetmp->fetch($selected);
				$selected_input_value=$societetmp->name;
				unset($societetmp);
			}
			// mode 1
			$urloption='htmlname='.$htmlname.'&outjson=1&filter='.$filter.($showtype?'&showtype='.$showtype:'');
			$out.=  ajax_autocompleter($selected, $htmlname, DOL_URL_ROOT.'/societe/ajax/company.php', $urloption, $conf->global->COMPANY_USE_SEARCH_TO_SELECT, 0, $ajaxoptions);
			$out.='<style type="text/css">.ui-autocomplete { z-index: 250; }</style>';
			if (empty($hidelabel)) print $langs->trans("RefOrLabel").' : ';
			else if ($hidelabel > 1) {
				$placeholder=' placeholder="'.$langs->trans("RefOrLabel").'"';
				if ($hidelabel == 2) {
					$out.=  img_picto($langs->trans("Search"), 'search');
				}
			}
			$out.= '<input type="text" class="'.$morecss.'" name="search_'.$htmlname.'" id="search_'.$htmlname.'" value="'.$selected_input_value.'"'.$placeholder.' '.(!empty($conf->global->THIRDPARTY_SEARCH_AUTOFOCUS) ? 'autofocus' : '').' />';
			if ($hidelabel == 3) {
				$out.=  img_picto($langs->trans("Search"), 'search');
			}
		}
		else
		{
			// Immediate load of all database
			$out.=$this->select_thirdparty_list($selected, $htmlname, $filter, $showempty, $showtype, $forcecombo, $events, '', 0, $limit, $morecss, $moreparam, $multiple);
		}

		return $out;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Output html form to select a third party.
	 *  Note, you must use the select_company to get the component to select a third party. This function must only be called by select_company.
	 *
	 *	@param	string	$selected       Preselected type
	 *	@param  string	$htmlname       Name of field in form
	 *  @param  string	$filter         Optional filters criteras (example: 's.rowid <> x', 's.client in (1,3)')
	 *	@param	string	$showempty		Add an empty field (Can be '1' or text to use on empty line like 'SelectThirdParty')
	 * 	@param	int		$showtype		Show third party type in combolist (customer, prospect or supplier)
	 * 	@param	int		$forcecombo		Force to use standard HTML select component without beautification
	 *  @param	array	$events			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
	 *  @param	string	$filterkey		Filter on key value
	 *  @param	int		$outputmode		0=HTML select string, 1=Array
	 *  @param	int		$limit			Limit number of answers
	 *  @param	string	$morecss		Add more css styles to the SELECT component
	 *	@param  string	$moreparam      Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
	 *	@param  bool	$multiple       add [] in the name of element and add 'multiple' attribut
	 * 	@return	string					HTML string with
	 */
	function select_thirdparty_list($selected='',$htmlname='socid',$filter='',$showempty='', $showtype=0, $forcecombo=0, $events=array(), $filterkey='', $outputmode=0, $limit=0, $morecss='minwidth100', $moreparam='', $multiple=false)
	{
        // phpcs:enable
		global $conf,$user,$langs;

		$out='';
		$num=0;
		$outarray=array();

		if ($selected === '') $selected = array();
		else if (!is_array($selected)) $selected = array($selected);

		// Clean $filter that may contains sql conditions so sql code
		if (function_exists('testSqlAndScriptInject')) {
			if (testSqlAndScriptInject($filter, 3)>0) {
				$filter ='';
			}
		}

		// On recherche les societes
		$sql = "SELECT s.rowid, s.nom as name, s.name_alias, s.client, s.fournisseur, s.code_client, s.code_fournisseur";

		if ($conf->global->COMPANY_SHOW_ADDRESS_SELECTLIST) {
			$sql .= ", s.address, s.zip, s.town";
		 	$sql .= ", dictp.code as country_code";
		}

		$sql.= " FROM (".MAIN_DB_PREFIX ."societe as s";
		if (!$user->rights->societe->client->voir && !$user->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " )";
		if ($conf->global->COMPANY_SHOW_ADDRESS_SELECTLIST) {
			$sql.= " LEFT OUTER JOIN ".MAIN_DB_PREFIX."c_country as dictp ON dictp.rowid=s.fk_pays";
		}
		$sql.= " WHERE s.entity IN (".getEntity('societe').")";
		if (! empty($user->socid)) $sql.= " AND s.rowid = ".$user->socid;
		if ($filter) $sql.= " AND (".$filter.")";
		if (!$user->rights->societe->client->voir && !$user->socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
		if (! empty($conf->global->COMPANY_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND s.status <> 0";
		// Add criteria
		if ($filterkey && $filterkey != '')
		{
			$sql.=" AND (";
			$prefix=empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE)?'%':'';	// Can use index if COMPANY_DONOTSEARCH_ANYWHERE is on
			// For natural search
			$scrit = explode(' ', $filterkey);
			$i=0;
			if (count($scrit) > 1) $sql.="(";
			foreach ($scrit as $crit) {
				if ($i > 0) $sql.=" AND ";
				$sql.="(s.nom LIKE '".$this->db->escape($prefix.$crit)."%')";
				$i++;
			}
			if (count($scrit) > 1) $sql.=")";
			if (! empty($conf->barcode->enabled))
			{
				$sql .= " OR s.barcode LIKE '".$this->db->escape($prefix.$filterkey)."%'";
			}
			$sql.= " OR s.code_client LIKE '".$this->db->escape($prefix.$filterkey)."%' OR s.code_fournisseur LIKE '".$this->db->escape($prefix.$filterkey)."%'";
			$sql.=")";
		}
		$sql.=$this->db->order("nom","ASC");
		$sql.=$this->db->plimit($limit, 0);

		// Build output string
		dol_syslog(get_class($this)."::select_thirdparty_list", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
		   	if (! $forcecombo)
			{
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname, $events, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
			}

			// Construct $out and $outarray
			$out.= '<select id="'.$htmlname.'" class="flat'.($morecss?' '.$morecss:'').'"'.($moreparam?' '.$moreparam:'').' name="'.$htmlname.($multiple ? '[]' : '').'" '.($multiple ? 'multiple' : '').'>'."\n";

			$textifempty='';
			// Do not use textifempty = ' ' or '&nbsp;' here, or search on key will search on ' key'.
			//if (! empty($conf->use_javascript_ajax) || $forcecombo) $textifempty='';
			if (! empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT))
			{
				if ($showempty && ! is_numeric($showempty)) $textifempty=$langs->trans($showempty);
				else $textifempty.=$langs->trans("All");
			}
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

					if(!empty($obj->name_alias)) {
						$label.=' ('.$obj->name_alias.')';
					}

					if ($showtype)
					{
						if ($obj->client || $obj->fournisseur) $label.=' (';
						if ($obj->client == 1 || $obj->client == 3) $label.=$langs->trans("Customer");
						if ($obj->client == 2 || $obj->client == 3) $label.=($obj->client==3?', ':'').$langs->trans("Prospect");
						if ($obj->fournisseur) $label.=($obj->client?', ':'').$langs->trans("Supplier");
						if ($obj->client || $obj->fournisseur) $label.=')';
					}

					if ($conf->global->COMPANY_SHOW_ADDRESS_SELECTLIST) {
						$label.='-'.$obj->address.'-'. $obj->zip.' '. $obj->town;
						if (!empty($obj->country_code)) {
							$label.= ' '. $langs->trans('Country'.$obj->country_code);
						}
					}

					if (empty($outputmode))
					{
						if (in_array($obj->rowid,$selected))
						{
							$out.= '<option value="'.$obj->rowid.'" selected>'.$label.'</option>';
						}
						else
						{
							$out.= '<option value="'.$obj->rowid.'">'.$label.'</option>';
						}
					}
					else
					{
						array_push($outarray, array('key'=>$obj->rowid, 'value'=>$label, 'label'=>$label));
					}

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


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
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
        // phpcs:enable
		global $langs,$conf;

		// On recherche les remises
		$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
		$sql.= " re.description, re.fk_facture_source";
		$sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re";
		$sql.= " WHERE re.fk_soc = ".(int) $socid;
		$sql.= " AND re.entity = " . $conf->entity;
		if ($filter) $sql.= " AND ".$filter;
		$sql.= " ORDER BY re.description ASC";

		dol_syslog(get_class($this)."::select_remises", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			print '<select class="flat maxwidthonsmartphone" name="'.$htmlname.'">';
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
					if (preg_match('/\(CREDIT_NOTE\)/', $desc)) $desc=preg_replace('/\(CREDIT_NOTE\)/', $langs->trans("CreditNote"), $desc);
					if (preg_match('/\(DEPOSIT\)/', $desc)) $desc=preg_replace('/\(DEPOSIT\)/', $langs->trans("Deposit"), $desc);
					if (preg_match('/\(EXCESS RECEIVED\)/', $desc)) $desc=preg_replace('/\(EXCESS RECEIVED\)/', $langs->trans("ExcessReceived"), $desc);
					if (preg_match('/\(EXCESS PAID\)/', $desc)) $desc=preg_replace('/\(EXCESS PAID\)/', $langs->trans("ExcessPaid"), $desc);

					$selectstring='';
					if ($selected > 0 && $selected == $obj->rowid) $selectstring=' selected';

					$disabled='';
					if ($maxvalue > 0 && $obj->amount_ttc > $maxvalue)
					{
						$qualifiedlines--;
						$disabled=' disabled';
					}

					if (!empty($conf->global->MAIN_SHOW_FACNUMBER_IN_DISCOUNT_LIST) && !empty($obj->fk_facture_source))
					{
						$tmpfac = new Facture($this->db);
						if ($tmpfac->fetch($obj->fk_facture_source) > 0) $desc=$desc.' - '.$tmpfac->ref;
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Return list of all contacts (for a third party or all)
	 *
	 *	@param	int		$socid      	Id ot third party or 0 for all
	 *	@param  string	$selected   	Id contact pre-selectionne
	 *	@param  string	$htmlname  	    Name of HTML field ('none' for a not editable field)
	 *	@param  int		$showempty      0=no empty value, 1=add an empty value, 2=add line 'Internal' (used by user edit), 3=add an empty value only if more than one record into list
	 *	@param  string	$exclude        List of contacts id to exclude
	 *	@param	string	$limitto		Disable answers that are not id in this array list
	 *	@param	integer	$showfunction   Add function into label
	 *	@param	string	$moreclass		Add more class to class style
	 *	@param	integer	$showsoc	    Add company into label
	 * 	@param	int		$forcecombo		Force to use combo box
	 *  @param	array	$events			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
	 *  @param	bool	$options_only	Return options only (for ajax treatment)
	 *  @param	string	$moreparam		Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
	 *  @param	string	$htmlid			Html id to use instead of htmlname
	 *	@return	int						<0 if KO, Nb of contact in list if OK
	 *  @deprected						You can use selectcontacts directly (warning order of param was changed)
	 */
	function select_contacts($socid,$selected='',$htmlname='contactid',$showempty=0,$exclude='',$limitto='',$showfunction=0, $moreclass='', $showsoc=0, $forcecombo=0, $events=array(), $options_only=false, $moreparam='', $htmlid='')
	{
        // phpcs:enable
		print $this->selectcontacts($socid,$selected,$htmlname,$showempty,$exclude,$limitto,$showfunction, $moreclass, $options_only, $showsoc, $forcecombo, $events, $moreparam, $htmlid);
		return $this->num;
	}

	/**
	 *	Return HTML code of the SELECT of list of all contacts (for a third party or all).
	 *  This also set the number of contacts found into $this->num
	 *
	 *	@param	int			$socid      	Id ot third party or 0 for all or -1 for empty list
	 *	@param  array|int	$selected   	Array of ID of pre-selected contact id
	 *	@param  string		$htmlname  	    Name of HTML field ('none' for a not editable field)
	 *	@param  int			$showempty     	0=no empty value, 1=add an empty value, 2=add line 'Internal' (used by user edit), 3=add an empty value only if more than one record into list
	 *	@param  string		$exclude        List of contacts id to exclude
	 *	@param	string		$limitto		Disable answers that are not id in this array list
	 *	@param	integer		$showfunction   Add function into label
	 *	@param	string		$moreclass		Add more class to class style
	 *	@param	bool		$options_only	Return options only (for ajax treatment)
	 *	@param	integer		$showsoc	    Add company into label
	 * 	@param	int			$forcecombo		Force to use combo box (so no ajax beautify effect)
	 *  @param	array		$events			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
	 *  @param	string		$moreparam		Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
	 *  @param	string		$htmlid			Html id to use instead of htmlname
	 *  @param	bool		$multiple		add [] in the name of element and add 'multiple' attribut
	 *	@return	 int						<0 if KO, Nb of contact in list if OK
	 */
	function selectcontacts($socid, $selected='', $htmlname='contactid', $showempty=0, $exclude='', $limitto='', $showfunction=0, $moreclass='', $options_only=false, $showsoc=0, $forcecombo=0, $events=array(), $moreparam='', $htmlid='', $multiple=false)
	{
		global $conf,$langs;

		$langs->load('companies');

		if (empty($htmlid)) $htmlid = $htmlname;

		if ($selected === '') $selected = array();
		else if (!is_array($selected)) $selected = array($selected);
        $out='';

		// On recherche les societes
		$sql = "SELECT sp.rowid, sp.lastname, sp.statut, sp.firstname, sp.poste";
		if ($showsoc > 0) $sql.= " , s.nom as company";
		$sql.= " FROM ".MAIN_DB_PREFIX ."socpeople as sp";
		if ($showsoc > 0) $sql.= " LEFT OUTER JOIN  ".MAIN_DB_PREFIX ."societe as s ON s.rowid=sp.fk_soc";
		$sql.= " WHERE sp.entity IN (".getEntity('socpeople').")";
		if ($socid > 0 || $socid == -1) $sql.= " AND sp.fk_soc=".$socid;
		if (! empty($conf->global->CONTACT_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND sp.statut <> 0";
		$sql.= " ORDER BY sp.lastname ASC";

		dol_syslog(get_class($this)."::select_contacts", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);

			if ($conf->use_javascript_ajax && ! $forcecombo && ! $options_only)
			{
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlid, $events, $conf->global->CONTACT_USE_SEARCH_TO_SELECT);
			}

			if ($htmlname != 'none' && ! $options_only) $out.= '<select class="flat'.($moreclass?' '.$moreclass:'').'" id="'.$htmlid.'" name="'.$htmlname.($multiple ? '[]' : '').'" '.($multiple ? 'multiple' : '').' '.(!empty($moreparam) ? $moreparam : '').'>';
			if (($showempty == 1 || ($showempty == 3 && $num > 1)) && !$multiple) $out.= '<option value="0"'.(in_array(0,$selected)?' selected':'').'>&nbsp;</option>';
			if ($showempty == 2) $out.= '<option value="0"'.(in_array(0,$selected)?' selected':'').'>'.$langs->trans("Internal").'</option>';

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
						if (!empty($selected) && in_array($obj->rowid, $selected))
						{
							$out.= '<option value="'.$obj->rowid.'"';
							if ($disabled) $out.= ' disabled';
							$out.= ' selected>';
							$out.= $contactstatic->getFullName($langs);
							if ($showfunction && $obj->poste) $out.= ' ('.$obj->poste.')';
							if (($showsoc > 0) && $obj->company) $out.= ' - ('.$obj->company.')';
							$out.= '</option>';
						}
						else
						{
							$out.= '<option value="'.$obj->rowid.'"';
							if ($disabled) $out.= ' disabled';
							$out.= '>';
							$out.= $contactstatic->getFullName($langs);
							if ($showfunction && $obj->poste) $out.= ' ('.$obj->poste.')';
							if (($showsoc > 0) && $obj->company) $out.= ' - ('.$obj->company.')';
							$out.= '</option>';
						}
					}
					else
					{
						if (in_array($obj->rowid, $selected))
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
				$out.= '<option value="-1"'.(($showempty==2 || $multiple) ? '' : ' selected').' disabled>';
				$out.= ($socid != -1) ? ($langs->trans($socid?"NoContactDefinedForThirdParty":"NoContactDefined")) : $langs->trans('SelectAThirdPartyFirst');
				$out.= '</option>';
			}
			if ($htmlname != 'none' && ! $options_only)
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
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
	 *  @param	string	$force_entity	'0' or Ids of environment to force
	 * 	@return	void
	 *  @deprecated		Use select_dolusers instead
	 *  @see select_dolusers()
	 */
	function select_users($selected='',$htmlname='userid',$show_empty=0,$exclude=null,$disabled=0,$include='',$enableonly='',$force_entity='0')
	{
        // phpcs:enable
		print $this->select_dolusers($selected,$htmlname,$show_empty,$exclude,$disabled,$include,$enableonly,$force_entity);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Return select list of users
	 *
	 *  @param	string	$selected       User id or user object of user preselected. If 0 or < -2, we use id of current user. If -1, keep unselected (if empty is allowed)
	 *  @param  string	$htmlname       Field name in form
	 *  @param  int		$show_empty     0=list with no empty value, 1=add also an empty value into list
	 *  @param  array	$exclude        Array list of users id to exclude
	 * 	@param	int		$disabled		If select list must be disabled
	 *  @param  array|string	$include        Array list of users id to include or 'hierarchy' to have only supervised users or 'hierarchyme' to have supervised + me
	 * 	@param	array	$enableonly		Array list of users id to be enabled. If defined, it means that others will be disabled
	 *  @param	string	$force_entity	'0' or Ids of environment to force
	 *  @param	int		$maxlength		Maximum length of string into list (0=no limit)
	 *  @param	int		$showstatus		0=show user status only if status is disabled, 1=always show user status into label, -1=never show user status
	 *  @param	string	$morefilter		Add more filters into sql request (Example: 'employee = 1')
	 *  @param	integer	$show_every		0=default list, 1=add also a value "Everybody" at beginning of list
	 *  @param	string	$enableonlytext	If option $enableonlytext is set, we use this text to explain into label why record is disabled. Not used if enableonly is empty.
	 *  @param	string	$morecss		More css
	 *  @param  int     $noactive       Show only active users (this will also happened whatever is this option if USER_HIDE_INACTIVE_IN_COMBOBOX is on).
	 *  @param  int		$outputmode     0=HTML select string, 1=Array
	 *  @param  bool	$multiple       add [] in the name of element and add 'multiple' attribut
	 * 	@return	string					HTML select string
	 *  @see select_dolgroups
	 */
	function select_dolusers($selected='', $htmlname='userid', $show_empty=0, $exclude=null, $disabled=0, $include='', $enableonly='', $force_entity='0', $maxlength=0, $showstatus=0, $morefilter='', $show_every=0, $enableonlytext='', $morecss='', $noactive=0, $outputmode=0, $multiple=false)
	{
        // phpcs:enable
		global $conf,$user,$langs;

		// If no preselected user defined, we take current user
		if ((is_numeric($selected) && ($selected < -2 || empty($selected))) && empty($conf->global->SOCIETE_DISABLE_DEFAULT_SALESREPRESENTATIVE)) $selected=$user->id;

		if ($selected === '') $selected = array();
		else if (!is_array($selected)) $selected = array($selected);

		$excludeUsers=null;
		$includeUsers=null;

		// Permettre l'exclusion d'utilisateurs
		if (is_array($exclude))	$excludeUsers = implode(",",$exclude);
		// Permettre l'inclusion d'utilisateurs
		if (is_array($include))	$includeUsers = implode(",",$include);
		else if ($include == 'hierarchy')
		{
			// Build list includeUsers to have only hierarchy
			$includeUsers = implode(",",$user->getAllChildIds(0));
		}
		else if ($include == 'hierarchyme')
		{
			// Build list includeUsers to have only hierarchy and current user
			$includeUsers = implode(",",$user->getAllChildIds(1));
		}

		$out='';
		$outarray = array();

		// Forge request to select users
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
			if (! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
			{
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ug";
				$sql.= " ON ug.fk_user = u.rowid";
				$sql.= " WHERE ug.entity = ".$conf->entity;
			}
			else
			{
				$sql.= " WHERE u.entity IN (0,".$conf->entity.")";
			}
		}
		if (! empty($user->societe_id)) $sql.= " AND u.fk_soc = ".$user->societe_id;
		if (is_array($exclude) && $excludeUsers) $sql.= " AND u.rowid NOT IN (".$excludeUsers.")";
		if ($includeUsers) $sql.= " AND u.rowid IN (".$includeUsers.")";
		if (! empty($conf->global->USER_HIDE_INACTIVE_IN_COMBOBOX) || $noactive) $sql.= " AND u.statut <> 0";
		if (! empty($morefilter)) $sql.=" ".$morefilter;

		if (empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION))	// MAIN_FIRSTNAME_NAME_POSITION is 0 means firstname+lastname
		{
			$sql.= " ORDER BY u.firstname ASC";
		}
		else
		{
			$sql.= " ORDER BY u.lastname ASC";
		}

		dol_syslog(get_class($this)."::select_dolusers", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
		   		// Enhance with select2
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname);

				// do not use maxwidthonsmartphone by default. Set it by caller so auto size to 100% will work when not defined
				$out.= '<select class="flat'.($morecss?' minwidth100 '.$morecss:' minwidth200').'" id="'.$htmlname.'" name="'.$htmlname.($multiple ? '[]' : '').'" '.($multiple ? 'multiple' : '').' '.($disabled?' disabled':'').'>';
				if ($show_empty && !$multiple) $out.= '<option value="-1"'.((empty($selected) || in_array(-1,$selected))?' selected':'').'>&nbsp;</option>'."\n";
				if ($show_every) $out.= '<option value="-2"'.((in_array(-2,$selected))?' selected':'').'>-- '.$langs->trans("Everybody").' --</option>'."\n";

				$userstatic=new User($this->db);

				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);

					$userstatic->id=$obj->rowid;
					$userstatic->lastname=$obj->lastname;
					$userstatic->firstname=$obj->firstname;

					$disableline='';
					if (is_array($enableonly) && count($enableonly) && ! in_array($obj->rowid,$enableonly)) $disableline=($enableonlytext?$enableonlytext:'1');

					if ((is_object($selected) && $selected->id == $obj->rowid) || (! is_object($selected) && in_array($obj->rowid,$selected) ))
					{
						$out.= '<option value="'.$obj->rowid.'"';
						if ($disableline) $out.= ' disabled';
						$out.= ' selected>';
					}
					else
					{
						$out.= '<option value="'.$obj->rowid.'"';
						if ($disableline) $out.= ' disabled';
						$out.= '>';
					}

					// $fullNameMode is 0=Lastname+Firstname (MAIN_FIRSTNAME_NAME_POSITION=1), 1=Firstname+Lastname (MAIN_FIRSTNAME_NAME_POSITION=0)
					$fullNameMode = 0;
					if (empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION))
					{
						$fullNameMode = 1; //Firstname+lastname
					}
					$out.= $userstatic->getFullName($langs, $fullNameMode, -1, $maxlength);

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
					if (! empty($conf->multicompany->enabled) && empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && $user->admin && ! $user->entity)
					{
						if (! $obj->entity)
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
					if ($disableline && $disableline != '1')
					{
						$out.=' - '.$disableline;	// This is text from $enableonlytext parameter
					}
					$out.= '</option>';
					$outarray[$userstatic->id] = $userstatic->getFullName($langs, $fullNameMode, -1, $maxlength);

					$i++;
				}
			}
			else
			{
				$out.= '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'" disabled>';
				$out.= '<option value="">'.$langs->trans("None").'</option>';
			}
			$out.= '</select>';
		}
		else
		{
			dol_print_error($this->db);
		}

		if ($outputmode) return $outarray;
		return $out;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Return select list of users. Selected users are stored into session.
	 *  List of users are provided into $_SESSION['assignedtouser'].
	 *
	 *  @param  string	$action         Value for $action
	 *  @param  string	$htmlname       Field name in form
	 *  @param  int		$show_empty     0=list without the empty value, 1=add empty value
	 *  @param  array	$exclude        Array list of users id to exclude
	 * 	@param	int		$disabled		If select list must be disabled
	 *  @param  array	$include        Array list of users id to include or 'hierarchy' to have only supervised users
	 * 	@param	array	$enableonly		Array list of users id to be enabled. All other must be disabled
	 *  @param	int		$force_entity	'0' or Ids of environment to force
	 *  @param	int		$maxlength		Maximum length of string into list (0=no limit)
	 *  @param	int		$showstatus		0=show user status only if status is disabled, 1=always show user status into label, -1=never show user status
	 *  @param	string	$morefilter		Add more filters into sql request
	 *  @param	int		$showproperties		Show properties of each attendees
	 *  @param	array	$listofuserid		Array with properties of each user
	 *  @param	array	$listofcontactid	Array with properties of each contact
	 *  @param	array	$listofotherid		Array with properties of each other contact
	 * 	@return	string					HTML select string
	 *  @see select_dolgroups
	 */
	function select_dolusers_forevent($action='', $htmlname='userid', $show_empty=0, $exclude=null, $disabled=0, $include='', $enableonly='', $force_entity='0', $maxlength=0, $showstatus=0, $morefilter='', $showproperties=0, $listofuserid=array(), $listofcontactid=array(), $listofotherid=array())
	{
        // phpcs:enable
		global $conf, $user, $langs;

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
			$out.=' <input type="submit" class="button valignmiddle" name="'.$action.'assignedtouser" value="'.dol_escape_htmltag($langs->trans("Add")).'">';
			$out.='<br>';
		}
		$assignedtouser=array();
		if (!empty($_SESSION['assignedtouser']))
		{
			$assignedtouser=json_decode($_SESSION['assignedtouser'], true);
		}
		$nbassignetouser=count($assignedtouser);

		if ($nbassignetouser && $action != 'view') $out.='<br>';
		if ($nbassignetouser) $out.='<ul class="attendees">';
		$i=0; $ownerid=0;
		foreach($assignedtouser as $key => $value)
		{
			if ($value['id'] == $ownerid) continue;

			$out.='<li>';
			$userstatic->fetch($value['id']);
			$out.= $userstatic->getNomUrl(-1);
			if ($i == 0) { $ownerid = $value['id']; $out.=' ('.$langs->trans("Owner").')'; }
			if ($nbassignetouser > 1 && $action != 'view')
			{
				$out.=' <input type="image" style="border: 0px;" src="'.img_picto($langs->trans("Remove"), 'delete', '', 0, 1).'" value="'.$userstatic->id.'" class="removedassigned" id="removedassigned_'.$userstatic->id.'" name="removedassigned_'.$userstatic->id.'">';
			}
			// Show my availability
			if ($showproperties)
			{
				if ($ownerid == $value['id'] && is_array($listofuserid) && count($listofuserid) && in_array($ownerid, array_keys($listofuserid)))
				{
					$out.='<div class="myavailability inline-block">';
					$out.='&nbsp;-&nbsp;<span class="opacitymedium">'.$langs->trans("Availability").':</span>  <input id="transparency" class="marginleftonly marginrightonly" '.($action == 'view'?'disabled':'').' type="checkbox" name="transparency"'.($listofuserid[$ownerid]['transparency']?' checked':'').'>'.$langs->trans("Busy");
					$out.='</div>';
				}
			}
			//$out.=' '.($value['mandatory']?$langs->trans("Mandatory"):$langs->trans("Optional"));
			//$out.=' '.($value['transparency']?$langs->trans("Busy"):$langs->trans("NotBusy"));

			$out.='</li>';
			$i++;
		}
		if ($nbassignetouser) $out.='</ul>';

		//$out.='</form>';
		return $out;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Return list of products for customer in Ajax if Ajax activated or go to select_produits_list
	 *
	 *  @param		int			$selected				Preselected products
	 *  @param		string		$htmlname				Name of HTML select field (must be unique in page)
	 *  @param		int			$filtertype				Filter on product type (''=nofilter, 0=product, 1=service)
	 *  @param		int			$limit					Limit on number of returned lines
	 *  @param		int			$price_level			Level of price to show
	 *  @param		int			$status					Sell status -1=Return all products, 0=Products not on sell, 1=Products on sell
	 *  @param		int			$finished				2=all, 1=finished, 0=raw material
	 *  @param		string		$selected_input_value	Value of preselected input text (for use with ajax)
	 *  @param		int			$hidelabel				Hide label (0=no, 1=yes, 2=show search icon (before) and placeholder, 3 search icon after)
	 *  @param		array		$ajaxoptions			Options for ajax_autocompleter
	 *  @param      int			$socid					Thirdparty Id (to get also price dedicated to this customer)
	 *  @param		string		$showempty				'' to not show empty line. Translation key to show an empty line. '1' show empty line with no text.
	 * 	@param		int			$forcecombo				Force to use combo box
	 *  @param      string      $morecss                Add more css on select
	 *  @param      int         $hidepriceinlabel       1=Hide prices in label
	 *  @param      string      $warehouseStatus        Warehouse status filter to count the quantity in stock. Following comma separated filter options can be used
	 *										            'warehouseopen' = count products from open warehouses,
	 *										            'warehouseclosed' = count products from closed warehouses,
	 *										            'warehouseinternal' = count products from warehouses for internal correct/transfer only
	 *  @param 		array 		$selected_combinations 	Selected combinations. Format: array([attrid] => attrval, [...])
	 *  @return		void
	 */
	function select_produits($selected='', $htmlname='productid', $filtertype='', $limit=20, $price_level=0, $status=1, $finished=2, $selected_input_value='', $hidelabel=0, $ajaxoptions=array(), $socid=0, $showempty='1', $forcecombo=0, $morecss='', $hidepriceinlabel=0, $warehouseStatus='', $selected_combinations = array())
	{
        // phpcs:enable
		global $langs,$conf;

		// check parameters
		$price_level = (! empty($price_level) ? $price_level : 0);
		if (is_null($ajaxoptions)) $ajaxoptions=array();

		if(strval($filtertype) === '' && (!empty($conf->product->enabled) || !empty($conf->service->enabled))){
			if(!empty($conf->product->enabled) && empty($conf->service->enabled)){
				$filtertype = '0';
			}
			elseif(empty($conf->product->enabled) && !empty($conf->service->enabled)){
				$filtertype = '1';
			}
		}

		if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT))
		{
			$placeholder='';

			if ($selected && empty($selected_input_value))
			{
				require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
				$producttmpselect = new Product($this->db);
				$producttmpselect->fetch($selected);
				$selected_input_value=$producttmpselect->ref;
				unset($producttmpselect);
			}
			// mode=1 means customers products
			$urloption='htmlname='.$htmlname.'&outjson=1&price_level='.$price_level.'&type='.$filtertype.'&mode=1&status='.$status.'&finished='.$finished.'&hidepriceinlabel='.$hidepriceinlabel.'&warehousestatus='.$warehouseStatus;
			//Price by customer
			if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES) && !empty($socid)) {
				$urloption.='&socid='.$socid;
			}
			print ajax_autocompleter($selected, $htmlname, DOL_URL_ROOT.'/product/ajax/products.php', $urloption, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT, 0, $ajaxoptions);

			if (!empty($conf->variants->enabled)) {
				?>
				<script>

					selected = <?php echo json_encode($selected_combinations) ?>;
					combvalues = {};

					jQuery(document).ready(function () {

						jQuery("input[name='prod_entry_mode']").change(function () {
							if (jQuery(this).val() == 'free') {
								jQuery('div#attributes_box').empty();
							}
						});

						jQuery("input#<?php echo $htmlname ?>").change(function () {

							if (!jQuery(this).val()) {
								jQuery('div#attributes_box').empty();
								return;
							}

							jQuery.getJSON("<?php echo dol_buildpath('/variants/ajax/getCombinations.php', 2) ?>", {
								id: jQuery(this).val()
							}, function (data) {
								jQuery('div#attributes_box').empty();

								jQuery.each(data, function (key, val) {

									combvalues[val.id] = val.values;

									var span = jQuery(document.createElement('div')).css({
										'display': 'table-row'
									});

									span.append(
										jQuery(document.createElement('div')).text(val.label).css({
											'font-weight': 'bold',
											'display': 'table-cell',
											'text-align': 'right'
										})
									);

									var html = jQuery(document.createElement('select')).attr('name', 'combinations[' + val.id + ']').css({
										'margin-left': '15px',
										'white-space': 'pre'
									}).append(
										jQuery(document.createElement('option')).val('')
									);

									jQuery.each(combvalues[val.id], function (key, val) {
										var tag = jQuery(document.createElement('option')).val(val.id).html(val.value);

										if (selected[val.fk_product_attribute] == val.id) {
											tag.attr('selected', 'selected');
										}

										html.append(tag);
									});

									span.append(html);
									jQuery('div#attributes_box').append(span);
								});
							})
						});

						<?php if ($selected): ?>
						jQuery("input#<?php echo $htmlname ?>").change();
						<?php endif ?>
					});
				</script>
                <?php
			}
			if (empty($hidelabel)) print $langs->trans("RefOrLabel").' : ';
			else if ($hidelabel > 1) {
				$placeholder=' placeholder="'.$langs->trans("RefOrLabel").'"';
				if ($hidelabel == 2) {
					print img_picto($langs->trans("Search"), 'search');
				}
			}
			print '<input type="text" class="minwidth100" name="search_'.$htmlname.'" id="search_'.$htmlname.'" value="'.$selected_input_value.'"'.$placeholder.' '.(!empty($conf->global->PRODUCT_SEARCH_AUTOFOCUS) ? 'autofocus' : '').' />';
			if ($hidelabel == 3) {
				print img_picto($langs->trans("Search"), 'search');
			}
		}
		else
		{
			print $this->select_produits_list($selected, $htmlname, $filtertype, $limit, $price_level, '', $status, $finished, 0, $socid, $showempty, $forcecombo, $morecss, $hidepriceinlabel, $warehouseStatus);
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Return list of products for a customer
	 *
	 *	@param      int		$selected           Preselected product
	 *	@param      string	$htmlname           Name of select html
	 *  @param		string	$filtertype         Filter on product type (''=nofilter, 0=product, 1=service)
	 *	@param      int		$limit              Limit on number of returned lines
	 *	@param      int		$price_level        Level of price to show
	 * 	@param      string	$filterkey          Filter on product
	 *	@param		int		$status             -1=Return all products, 0=Products not on sell, 1=Products on sell
	 *  @param      int		$finished           Filter on finished field: 2=No filter
	 *  @param      int		$outputmode         0=HTML select string, 1=Array
	 *  @param      int		$socid     		    Thirdparty Id (to get also price dedicated to this customer)
	 *  @param		string	$showempty		    '' to not show empty line. Translation key to show an empty line. '1' show empty line with no text.
	 * 	@param		int		$forcecombo		    Force to use combo box
	 *  @param      string  $morecss            Add more css on select
	 *  @param      int     $hidepriceinlabel   1=Hide prices in label
	 *  @param      string  $warehouseStatus    Warehouse status filter to group/count stock. Following comma separated filter options can be used.
	 *										    'warehouseopen' = count products from open warehouses,
	 *										    'warehouseclosed' = count products from closed warehouses,
	 *										    'warehouseinternal' = count products from warehouses for internal correct/transfer only
	 *  @return     array    				    Array of keys for json
	 */
	function select_produits_list($selected='',$htmlname='productid',$filtertype='',$limit=20,$price_level=0,$filterkey='',$status=1,$finished=2,$outputmode=0,$socid=0,$showempty='1',$forcecombo=0,$morecss='',$hidepriceinlabel=0, $warehouseStatus='')
	{
        // phpcs:enable
		global $langs,$conf,$user,$db;

		$out='';
		$outarray=array();

		$warehouseStatusArray = array();
		if (! empty($warehouseStatus))
		{
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
			if (preg_match('/warehouseclosed/', $warehouseStatus))
			{
				$warehouseStatusArray[] = Entrepot::STATUS_CLOSED;
			}
			if (preg_match('/warehouseopen/', $warehouseStatus))
			{
				$warehouseStatusArray[] = Entrepot::STATUS_OPEN_ALL;
			}
			if (preg_match('/warehouseinternal/', $warehouseStatus))
			{
				$warehouseStatusArray[] = Entrepot::STATUS_OPEN_INTERNAL;
			}
		}

		$selectFields = " p.rowid, p.label, p.ref, p.description, p.barcode, p.fk_product_type, p.price, p.price_ttc, p.price_base_type, p.tva_tx, p.duration, p.fk_price_expression";
		if (count($warehouseStatusArray))
		{
		    $selectFieldsGrouped = ", sum(".$db->ifsql("e.statut IS NULL", "0", "ps.reel").") as stock";           // e.statut is null if there is no record in stock
		}
		else
		{
		    $selectFieldsGrouped = ", p.stock";
		}

		$sql = "SELECT ";
		$sql.= $selectFields . $selectFieldsGrouped;
		//Price by customer
		if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES) && !empty($socid))
		{
			$sql.=', pcp.rowid as idprodcustprice, pcp.price as custprice, pcp.price_ttc as custprice_ttc,';
			$sql.=' pcp.price_base_type as custprice_base_type, pcp.tva_tx as custtva_tx';
			$selectFields.= ", idprodcustprice, custprice, custprice_ttc, custprice_base_type, custtva_tx";
		}

		// Multilang : we add translation
		if (! empty($conf->global->MAIN_MULTILANGS))
		{
			$sql.= ", pl.label as label_translated";
			$selectFields.= ", label_translated";
		}
		// Price by quantity
		if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY) || !empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES))
		{
			$sql.= ", (SELECT pp.rowid FROM ".MAIN_DB_PREFIX."product_price as pp WHERE pp.fk_product = p.rowid";
			if ($price_level >= 1 && !empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES)) $sql.= " AND price_level=".$price_level;
			$sql.= " ORDER BY date_price";
			$sql.= " DESC LIMIT 1) as price_rowid";
			$sql.= ", (SELECT pp.price_by_qty FROM ".MAIN_DB_PREFIX."product_price as pp WHERE pp.fk_product = p.rowid";	// price_by_qty is 1 if some prices by qty exists in subtable
			if ($price_level >= 1 && !empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES)) $sql.= " AND price_level=".$price_level;
			$sql.= " ORDER BY date_price";
			$sql.= " DESC LIMIT 1) as price_by_qty";
			$selectFields.= ", price_rowid, price_by_qty";
		}
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		if (count($warehouseStatusArray))
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps on ps.fk_product = p.rowid";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as e on ps.fk_entrepot = e.rowid AND e.entity IN (".getEntity('stock').")";
			$sql.= ' AND e.statut IN ('.$this->db->escape(implode(',',$warehouseStatusArray)).')';    // Return line if product is inside the selected stock. If not, an empty line will be returned so we will count 0.
		}

		// include search in supplier ref
		if(!empty($conf->global->MAIN_SEARCH_PRODUCT_BY_FOURN_REF))
		{
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON p.rowid = pfp.fk_product";
		}

		//Price by customer
		if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES) && !empty($socid)) {
			$sql.=" LEFT JOIN  ".MAIN_DB_PREFIX."product_customer_price as pcp ON pcp.fk_soc=".$socid." AND pcp.fk_product=p.rowid";
		}
		// Multilang : we add translation
		if (! empty($conf->global->MAIN_MULTILANGS))
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON pl.fk_product = p.rowid AND pl.lang='". $langs->getDefaultLang() ."'";
		}

		if (!empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD)) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_attribute_combination pac ON pac.fk_product_child = p.rowid";
		}

		$sql.= ' WHERE p.entity IN ('.getEntity('product').')';

		if (!empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD)) {
			$sql .= " AND pac.rowid IS NULL";
		}

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
				$sql.="(p.ref LIKE '".$db->escape($prefix.$crit)."%' OR p.label LIKE '".$db->escape($prefix.$crit)."%'";
				if (! empty($conf->global->MAIN_MULTILANGS)) $sql.=" OR pl.label LIKE '".$db->escape($prefix.$crit)."%'";
				if (! empty($conf->global->MAIN_SEARCH_PRODUCT_BY_FOURN_REF)) $sql.=" OR pfp.ref_fourn LIKE '".$db->escape($prefix.$crit)."%'";
				$sql.=")";
				$i++;
			}
			if (count($scrit) > 1) $sql.=")";
		  	if (! empty($conf->barcode->enabled)) $sql.= " OR p.barcode LIKE '".$db->escape($prefix.$filterkey)."%'";
			$sql.=')';
		}
		if (count($warehouseStatusArray))
		{
			$sql.= ' GROUP BY'.$selectFields;
		}
		$sql.= $db->order("p.ref");
		$sql.= $db->plimit($limit, 0);

		// Build output string
		dol_syslog(get_class($this)."::select_produits_list search product", LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
			$num = $this->db->num_rows($result);

			$events=null;

			if (! $forcecombo)
			{
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname, $events, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT);
			}

			$out.='<select class="flat'.($morecss?' '.$morecss:'').'" name="'.$htmlname.'" id="'.$htmlname.'">';

			$textifempty='';
			// Do not use textifempty = ' ' or '&nbsp;' here, or search on key will search on ' key'.
			//if (! empty($conf->use_javascript_ajax) || $forcecombo) $textifempty='';
			if (! empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT))
			{
				if ($showempty && ! is_numeric($showempty)) $textifempty=$langs->trans($showempty);
				else $textifempty.=$langs->trans("All");
			}
			else
			{
			    if ($showempty && ! is_numeric($showempty)) $textifempty=$langs->trans($showempty);
			}
			if ($showempty) $out.='<option value="0" selected>'.$textifempty.'</option>';

			$i = 0;
			while ($num && $i < $num)
			{
				$opt = '';
				$optJson = array();
				$objp = $this->db->fetch_object($result);

				if ((!empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY) || !empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES)) && !empty($objp->price_by_qty) && $objp->price_by_qty == 1)
				{ // Price by quantity will return many prices for the same product
					$sql = "SELECT rowid, quantity, price, unitprice, remise_percent, remise, price_base_type";
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

							$objp->price_by_qty_rowid = $objp2->rowid;
							$objp->price_by_qty_price_base_type = $objp2->price_base_type;
							$objp->price_by_qty_quantity = $objp2->quantity;
							$objp->price_by_qty_unitprice = $objp2->unitprice;
							$objp->price_by_qty_remise_percent = $objp2->remise_percent;
							// For backward compatibility
							$objp->quantity = $objp2->quantity;
							$objp->price = $objp2->price;
							$objp->unitprice = $objp2->unitprice;
							$objp->remise_percent = $objp2->remise_percent;
							$objp->remise = $objp2->remise;

							$this->constructProductListOption($objp, $opt, $optJson, 0, $selected, $hidepriceinlabel);

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
					if (!empty($conf->dynamicprices->enabled) && !empty($objp->fk_price_expression)) {
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
					$this->constructProductListOption($objp, $opt, $optJson, $price_level, $selected, $hidepriceinlabel);
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
	 * @param 	resource	$objp			    Resultset of fetch
	 * @param 	string		$opt			    Option (var used for returned value in string option format)
	 * @param 	string		$optJson		    Option (var used for returned value in json format)
	 * @param 	int			$price_level	    Price level
	 * @param 	string		$selected		    Preselected value
	 * @param   int         $hidepriceinlabel   Hide price in label
	 * @return	void
	 */
	private function constructProductListOption(&$objp, &$opt, &$optJson, $price_level, $selected, $hidepriceinlabel=0)
	{
		global $langs,$conf,$user,$db;

		$outkey='';
		$outval='';
		$outref='';
		$outlabel='';
		$outdesc='';
		$outbarcode='';
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
		$outbarcode=$objp->barcode;

		$outtype=$objp->fk_product_type;
		$outdurationvalue=$outtype == Product::TYPE_SERVICE?substr($objp->duration,0,dol_strlen($objp->duration)-1):'';
		$outdurationunit=$outtype == Product::TYPE_SERVICE?substr($objp->duration,-1):'';

		$opt = '<option value="'.$objp->rowid.'"';
		$opt.= ($objp->rowid == $selected)?' selected':'';
		if (!empty($objp->price_by_qty_rowid) && $objp->price_by_qty_rowid > 0)
		{
			$opt.= ' pbq="'.$objp->price_by_qty_rowid.'" data-pbq="'.$objp->price_by_qty_rowid.'" data-pbqqty="'.$objp->price_by_qty_quantity.'" data-pbqpercent="'.$objp->price_by_qty_remise_percent.'"';
		}
		if (! empty($conf->stock->enabled) && $objp->fk_product_type == 0 && isset($objp->stock))
		{
			if ($objp->stock > 0) $opt.= ' class="product_line_stock_ok"';
			else if ($objp->stock <= 0) $opt.= ' class="product_line_stock_too_low"';
		}
		$opt.= '>';
		$opt.= $objp->ref;
		if ($outbarcode) $opt.=' ('.$outbarcode.')';
		$opt.=' - '.dol_trunc($label,$maxlengtharticle);

		$objRef = $objp->ref;
		if (! empty($filterkey) && $filterkey != '') $objRef=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$objRef,1);
		$outval.=$objRef;
		if ($outbarcode) $outval.=' ('.$outbarcode.')';
		$outval.=' - '.dol_trunc($label,$maxlengtharticle);

		$found=0;

		// Multiprice
		// If we need a particular price level (from 1 to 6)
		if (empty($hidepriceinlabel) && $price_level >= 1 && (! empty($conf->global->PRODUIT_MULTIPRICES) || ! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES)))
		{
			$sql = "SELECT price, price_ttc, price_base_type, tva_tx";
			$sql.= " FROM ".MAIN_DB_PREFIX."product_price";
			$sql.= " WHERE fk_product='".$objp->rowid."'";
			$sql.= " AND entity IN (".getEntity('productprice').")";
			$sql.= " AND price_level=".$price_level;
			$sql.= " ORDER BY date_price DESC, rowid DESC"; // Warning DESC must be both on date_price and rowid.
			$sql.= " LIMIT 1";

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
						$opt.= ' - '.price($objp2->price,1,$langs,0,0,-1,$conf->currency).' '.$langs->trans("HT");
						$outval.= ' - '.price($objp2->price,0,$langs,0,0,-1,$conf->currency).' '.$langs->transnoentities("HT");
					}
					else
					{
						$opt.= ' - '.price($objp2->price_ttc,1,$langs,0,0,-1,$conf->currency).' '.$langs->trans("TTC");
						$outval.= ' - '.price($objp2->price_ttc,0,$langs,0,0,-1,$conf->currency).' '.$langs->transnoentities("TTC");
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
		if (empty($hidepriceinlabel) && !empty($objp->quantity) && $objp->quantity >= 1 && (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY) || !empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES)))
		{
			$found = 1;
			$outqty=$objp->quantity;
			$outdiscount=$objp->remise_percent;
			if ($objp->quantity == 1)
			{
				$opt.= ' - '.price($objp->unitprice,1,$langs,0,0,-1,$conf->currency)."/";
				$outval.= ' - '.price($objp->unitprice,0,$langs,0,0,-1,$conf->currency)."/";
				$opt.= $langs->trans("Unit");	// Do not use strtolower because it breaks utf8 encoding
				$outval.=$langs->transnoentities("Unit");
			}
			else
			{
				$opt.= ' - '.price($objp->price,1,$langs,0,0,-1,$conf->currency)."/".$objp->quantity;
				$outval.= ' - '.price($objp->price,0,$langs,0,0,-1,$conf->currency)."/".$objp->quantity;
				$opt.= $langs->trans("Units");	// Do not use strtolower because it breaks utf8 encoding
				$outval.=$langs->transnoentities("Units");
			}

			$outprice_ht=price($objp->unitprice);
			$outprice_ttc=price($objp->unitprice * (1 + ($objp->tva_tx / 100)));
			$outpricebasetype=$objp->price_base_type;
			$outtva_tx=$objp->tva_tx;
		}
		if (empty($hidepriceinlabel) && !empty($objp->quantity) && $objp->quantity >= 1)
		{
			$opt.=" (".price($objp->unitprice,1,$langs,0,0,-1,$conf->currency)."/".$langs->trans("Unit").")";	// Do not use strtolower because it breaks utf8 encoding
			$outval.=" (".price($objp->unitprice,0,$langs,0,0,-1,$conf->currency)."/".$langs->transnoentities("Unit").")";	// Do not use strtolower because it breaks utf8 encoding
		}
		if (empty($hidepriceinlabel) && !empty($objp->remise_percent) && $objp->remise_percent >= 1)
		{
			$opt.=" - ".$langs->trans("Discount")." : ".vatrate($objp->remise_percent).' %';
			$outval.=" - ".$langs->transnoentities("Discount")." : ".vatrate($objp->remise_percent).' %';
		}

		// Price by customer
		if (empty($hidepriceinlabel) && !empty($conf->global->PRODUIT_CUSTOMER_PRICES))
		{
			if (!empty($objp->idprodcustprice))
			{
				$found = 1;

				if ($objp->custprice_base_type == 'HT')
				{
					$opt.= ' - '.price($objp->custprice,1,$langs,0,0,-1,$conf->currency).' '.$langs->trans("HT");
					$outval.= ' - '.price($objp->custprice,0,$langs,0,0,-1,$conf->currency).' '.$langs->transnoentities("HT");
				}
				else
				{
					$opt.= ' - '.price($objp->custprice_ttc,1,$langs,0,0,-1,$conf->currency).' '.$langs->trans("TTC");
					$outval.= ' - '.price($objp->custprice_ttc,0,$langs,0,0,-1,$conf->currency).' '.$langs->transnoentities("TTC");
				}

				$outprice_ht=price($objp->custprice);
				$outprice_ttc=price($objp->custprice_ttc);
				$outpricebasetype=$objp->custprice_base_type;
				$outtva_tx=$objp->custtva_tx;
			}
		}

		// If level no defined or multiprice not found, we used the default price
		if (empty($hidepriceinlabel) && ! $found)
		{
			if ($objp->price_base_type == 'HT')
			{
				$opt.= ' - '.price($objp->price,1,$langs,0,0,-1,$conf->currency).' '.$langs->trans("HT");
				$outval.= ' - '.price($objp->price,0,$langs,0,0,-1,$conf->currency).' '.$langs->transnoentities("HT");
			}
			else
			{
				$opt.= ' - '.price($objp->price_ttc,1,$langs,0,0,-1,$conf->currency).' '.$langs->trans("TTC");
				$outval.= ' - '.price($objp->price_ttc,0,$langs,0,0,-1,$conf->currency).' '.$langs->transnoentities("TTC");
			}
			$outprice_ht=price($objp->price);
			$outprice_ttc=price($objp->price_ttc);
			$outpricebasetype=$objp->price_base_type;
			$outtva_tx=$objp->tva_tx;
		}

		if (! empty($conf->stock->enabled) && isset($objp->stock) && $objp->fk_product_type == 0)
		{
			$opt.= ' - '.$langs->trans("Stock").':'.$objp->stock;

			if ($objp->stock > 0) {
				$outval.= ' - <span class="product_line_stock_ok">'.$langs->transnoentities("Stock").':'.$objp->stock.'</span>';
			}elseif ($objp->stock <= 0) {
				$outval.= ' - <span class="product_line_stock_too_low">'.$langs->transnoentities("Stock").':'.$objp->stock.'</span>';
			}
		}

		if ($outdurationvalue && $outdurationunit)
		{
			$da=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
			if (isset($da[$outdurationunit]))
			{
				$key = $da[$outdurationunit].($outdurationvalue > 1?'s':'');
				$opt.= ' - '.$outdurationvalue.' '.$langs->trans($key);
				$outval.=' - '.$outdurationvalue.' '.$langs->transnoentities($key);
			}
		}

		$opt.= "</option>\n";
		$optJson = array('key'=>$outkey, 'value'=>$outref, 'label'=>$outval, 'label2'=>$outlabel, 'desc'=>$outdesc, 'type'=>$outtype, 'price_ht'=>$outprice_ht, 'price_ttc'=>$outprice_ttc, 'pricebasetype'=>$outpricebasetype, 'tva_tx'=>$outtva_tx, 'qty'=>$outqty, 'discount'=>$outdiscount, 'duration_value'=>$outdurationvalue, 'duration_unit'=>$outdurationunit);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
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
	 *  @param  int     $alsoproductwithnosupplierprice    1=Add also product without supplier prices
	 *  @param	string	$morecss		More CSS
	 *	@return	void
	 */
	function select_produits_fournisseurs($socid, $selected='', $htmlname='productid', $filtertype='', $filtre='', $ajaxoptions=array(), $hidelabel=0, $alsoproductwithnosupplierprice=0, $morecss='')
	{
        // phpcs:enable
		global $langs,$conf;
		global $price_level, $status, $finished;

		$selected_input_value='';
		if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT))
		{
			if ($selected > 0)
			{
				require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
				$producttmpselect = new Product($this->db);
				$producttmpselect->fetch($selected);
				$selected_input_value=$producttmpselect->ref;
				unset($producttmpselect);
			}

			// mode=2 means suppliers products
			$urloption=($socid > 0?'socid='.$socid.'&':'').'htmlname='.$htmlname.'&outjson=1&price_level='.$price_level.'&type='.$filtertype.'&mode=2&status='.$status.'&finished='.$finished.'&alsoproductwithnosupplierprice='.$alsoproductwithnosupplierprice;
			print ajax_autocompleter($selected, $htmlname, DOL_URL_ROOT.'/product/ajax/products.php', $urloption, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT, 0, $ajaxoptions);
			print ($hidelabel?'':$langs->trans("RefOrLabel").' : ').'<input type="text" size="20" name="search_'.$htmlname.'" id="search_'.$htmlname.'" value="'.$selected_input_value.'">';
		}
		else
		{
			print $this->select_produits_fournisseurs_list($socid, $selected, $htmlname, $filtertype, $filtre, '', -1, 0, 0, $alsoproductwithnosupplierprice, $morecss);
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Return list of suppliers products
	 *
	 *	@param	int		$socid   		Id societe fournisseur (0 pour aucun filtre)
	 *	@param  int		$selected       Product price pre-selected (must be 'id' in product_fournisseur_price or 'idprod_IDPROD')
	 *	@param  string	$htmlname       Nom de la zone select
	 *  @param	string	$filtertype     Filter on product type (''=nofilter, 0=product, 1=service)
	 *	@param  string	$filtre         Pour filtre sql
	 *	@param  string	$filterkey      Filtre des produits
	 *  @param  int		$statut         -1=Return all products, 0=Products not on sell, 1=Products on sell (not used here, a filter on tobuy is already hard coded in request)
	 *  @param  int		$outputmode     0=HTML select string, 1=Array
	 *  @param  int     $limit          Limit of line number
	 *  @param  int     $alsoproductwithnosupplierprice    1=Add also product without supplier prices
	 *  @param	string	$morecss		Add more CSS
	 *  @return array           		Array of keys for json
	 */
	function select_produits_fournisseurs_list($socid,$selected='',$htmlname='productid',$filtertype='',$filtre='',$filterkey='',$statut=-1,$outputmode=0,$limit=100,$alsoproductwithnosupplierprice=0,$morecss='')
	{
        // phpcs:enable
		global $langs,$conf,$db;

		$out='';
		$outarray=array();

		$langs->load('stocks');

		$sql = "SELECT p.rowid, p.label, p.ref, p.price, p.duration, p.fk_product_type,";
		$sql.= " pfp.ref_fourn, pfp.rowid as idprodfournprice, pfp.price as fprice, pfp.quantity, pfp.remise_percent, pfp.remise, pfp.unitprice,";
		$sql.= " pfp.fk_supplier_price_expression, pfp.fk_product, pfp.tva_tx, pfp.fk_soc, s.nom as name,";
		$sql.= " pfp.supplier_reputation";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON p.rowid = pfp.fk_product";
		if ($socid) $sql.= " AND pfp.fk_soc = ".$socid;
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON pfp.fk_soc = s.rowid";
		$sql.= " WHERE p.entity IN (".getEntity('product').")";
		$sql.= " AND p.tobuy = 1";
		if (strval($filtertype) != '') $sql.=" AND p.fk_product_type=".$this->db->escape($filtertype);
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
				$sql.="(pfp.ref_fourn LIKE '".$this->db->escape($prefix.$crit)."%' OR p.ref LIKE '".$this->db->escape($prefix.$crit)."%' OR p.label LIKE '".$this->db->escape($prefix.$crit)."%')";
				$i++;
			}
			if (count($scrit) > 1) $sql.=")";
			if (! empty($conf->barcode->enabled)) $sql.= " OR p.barcode LIKE '".$this->db->escape($prefix.$filterkey)."%'";
			$sql.=')';
		}
		$sql.= " ORDER BY pfp.ref_fourn DESC, pfp.quantity ASC";
		$sql.= $db->plimit($limit, 0);

		// Build output string

		dol_syslog(get_class($this)."::select_produits_fournisseurs_list", LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{
			require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';

			$num = $this->db->num_rows($result);

			//$out.='<select class="flat" id="select'.$htmlname.'" name="'.$htmlname.'">';	// remove select to have id same with combo and ajax
			$out.='<select class="flat maxwidthonsmartphone'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'">';
			if (! $selected) $out.='<option value="0" selected>&nbsp;</option>';
			else $out.='<option value="0">&nbsp;</option>';

			$i = 0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);

				$outkey=$objp->idprodfournprice;                                                    // id in table of price
				if (! $outkey && $alsoproductwithnosupplierprice) $outkey='idprod_'.$objp->rowid;   // id of product

				$outref=$objp->ref;
				$outval='';
				$outqty=1;
				$outdiscount=0;
				$outtype=$objp->fk_product_type;
				$outdurationvalue=$outtype == Product::TYPE_SERVICE?substr($objp->duration,0,dol_strlen($objp->duration)-1):'';
				$outdurationunit=$outtype == Product::TYPE_SERVICE?substr($objp->duration,-1):'';

				$opt = '<option value="'.$outkey.'"';
				if ($selected && $selected == $objp->idprodfournprice) $opt.= ' selected';
				if (empty($objp->idprodfournprice) && empty($alsoproductwithnosupplierprice)) $opt.=' disabled';
				if (!empty($objp->idprodfournprice) && $objp->idprodfournprice > 0)
				{
					$opt.= ' pbq="'.$objp->idprodfournprice.'" data-pbq="'.$objp->idprodfournprice.'" data-pbqqty="'.$objp->quantity.'" data-pbqpercent="'.$objp->remise_percent.'"';
				}
				$opt.= '>';

				$objRef = $objp->ref;
				if ($filterkey && $filterkey != '') $objRef=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$objRef,1);
				$objRefFourn = $objp->ref_fourn;
				if ($filterkey && $filterkey != '') $objRefFourn=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$objRefFourn,1);
				$label = $objp->label;
				if ($filterkey && $filterkey != '') $label=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$label,1);

				$opt.=$objp->ref;
				if (! empty($objp->idprodfournprice) && ($objp->ref != $objp->ref_fourn))
					$opt.=' ('.$objp->ref_fourn.')';
				$opt.=' - ';
				$outval.=$objRef;
				if (! empty($objp->idprodfournprice) && ($objp->ref != $objp->ref_fourn))
					$outval.=' ('.$objRefFourn.')';
				$outval.=' - ';
				$opt.=dol_trunc($label, 72).' - ';
				$outval.=dol_trunc($label, 72).' - ';

				if (! empty($objp->idprodfournprice))
				{
					$outqty=$objp->quantity;
					$outdiscount=$objp->remise_percent;
					if (!empty($conf->dynamicprices->enabled) && !empty($objp->fk_supplier_price_expression)) {
						$prod_supplier = new ProductFournisseur($this->db);
						$prod_supplier->product_fourn_price_id = $objp->idprodfournprice;
						$prod_supplier->id = $objp->fk_product;
						$prod_supplier->fourn_qty = $objp->quantity;
						$prod_supplier->fourn_tva_tx = $objp->tva_tx;
						$prod_supplier->fk_supplier_price_expression = $objp->fk_supplier_price_expression;
						$priceparser = new PriceParser($this->db);
						$price_result = $priceparser->parseProductSupplier($prod_supplier);
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
					if ($objp->supplier_reputation)
					{
						//TODO dictionary
						$reputations=array(''=>$langs->trans('Standard'),'FAVORITE'=>$langs->trans('Favorite'),'NOTTHGOOD'=>$langs->trans('NotTheGoodQualitySupplier'), 'DONOTORDER'=>$langs->trans('DoNotOrderThisProductToThisSupplier'));

						$opt .= " - ".$reputations[$objp->supplier_reputation];
						$outval.=" - ".$reputations[$objp->supplier_reputation];
					}
				}
				else
				{
					if (empty($alsoproductwithnosupplierprice))     // No supplier price defined for couple product/supplier
					{
						$opt.= $langs->trans("NoPriceDefinedForThisSupplier");
						$outval.=$langs->transnoentities("NoPriceDefinedForThisSupplier");
					}
					else                                            // No supplier price defined for product, even on other suppliers
					{
						$opt.= $langs->trans("NoPriceDefinedForThisSupplier");
						$outval.=$langs->transnoentities("NoPriceDefinedForThisSupplier");
					}
				}
				$opt .= "</option>\n";


				// Add new entry
				// "key" value of json key array is used by jQuery automatically as selected value
				// "label" value of json key array is used by jQuery automatically as text for combo box
				$out.=$opt;
				array_push($outarray, array('key'=>$outkey, 'value'=>$outref, 'label'=>$outval, 'qty'=>$outqty, 'discount'=>$outdiscount, 'type'=>$outtype, 'duration_value'=>$outdurationvalue, 'duration_unit'=>$outdurationunit, 'disabled'=>(empty($objp->idprodfournprice)?true:false)));
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

			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			$out.=ajax_combobox($htmlname);

			if (empty($outputmode)) return $out;
			return $outarray;
		}
		else
		{
			dol_print_error($this->db);
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Return list of suppliers prices for a product
	 *
	 *  @param	    int		$productid       	Id of product
	 *  @param      string	$htmlname        	Name of HTML field
	 *  @param      int		$selected_supplier  Pre-selected supplier if more than 1 result
	 *  @return	    void
	 */
	function select_product_fourn_price($productid, $htmlname='productfournpriceid', $selected_supplier='')
	{
        // phpcs:enable
		global $langs,$conf;

		$langs->load('stocks');

		$sql = "SELECT p.rowid, p.label, p.ref, p.price, p.duration, pfp.fk_soc,";
		$sql.= " pfp.ref_fourn, pfp.rowid as idprodfournprice, pfp.price as fprice, pfp.quantity, pfp.unitprice,";
		$sql.= " pfp.fk_supplier_price_expression, pfp.fk_product, pfp.tva_tx, s.nom as name";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON p.rowid = pfp.fk_product";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON pfp.fk_soc = s.rowid";
		$sql.= " WHERE pfp.entity IN (".getEntity('productsupplierprice').")";
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
					if($num == 1 || ($selected_supplier > 0 && $objp->fk_soc == $selected_supplier)) {
						$opt .= ' selected';
					}
					$opt.= '>'.$objp->name.' - '.$objp->ref_fourn.' - ';

					if (!empty($conf->dynamicprices->enabled) && !empty($objp->fk_supplier_price_expression)) {
						$prod_supplier = new ProductFournisseur($this->db);
						$prod_supplier->product_fourn_price_id = $objp->idprodfournprice;
						$prod_supplier->id = $productid;
						$prod_supplier->fourn_qty = $objp->quantity;
						$prod_supplier->fourn_tva_tx = $objp->tva_tx;
						$prod_supplier->fk_supplier_price_expression = $objp->fk_supplier_price_expression;
						$priceparser = new PriceParser($this->db);
						$price_result = $priceparser->parseProductSupplier($prod_supplier);
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
			}

			$form.= '</select>';
			$this->db->free($result);
			return $form;
		}
		else
		{
			dol_print_error($this->db);
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *    Return list of delivery address
	 *
	 *    @param    string	$selected          	Id contact pre-selectionn
	 *    @param    int		$socid				Id of company
	 *    @param    string	$htmlname          	Name of HTML field
	 *    @param    int		$showempty         	Add an empty field
	 *    @return	integer|null
	 */
	function select_address($selected, $socid, $htmlname='address_id',$showempty=0)
	{
        // phpcs:enable
		// looking for users
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
						print '<option value="'.$obj->rowid.'" selected>'.$obj->label.'</option>';
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


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *      Load into cache list of payment terms
	 *
	 *      @return     int             Nb of lines loaded, <0 if KO
	 */
	function load_cache_conditions_paiements()
	{
        // phpcs:enable
		global $langs;

		$num = count($this->cache_conditions_paiements);
		if ($num > 0) return 0;    // Cache already loaded

		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = "SELECT rowid, code, libelle as label";
		$sql.= " FROM ".MAIN_DB_PREFIX.'c_payment_term';
		$sql.= " WHERE entity IN (".getEntity('c_payment_term').")";
		$sql.= " AND active > 0";
		$sql.= " ORDER BY sortorder";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
				$label=($langs->trans("PaymentConditionShort".$obj->code)!=("PaymentConditionShort".$obj->code)?$langs->trans("PaymentConditionShort".$obj->code):($obj->label!='-'?$obj->label:''));
				$this->cache_conditions_paiements[$obj->rowid]['code'] =$obj->code;
				$this->cache_conditions_paiements[$obj->rowid]['label']=$label;
				$i++;
			}

			//$this->cache_conditions_paiements=dol_sort_array($this->cache_conditions_paiements, 'label', 'asc', 0, 0, 1);		// We use the field sortorder of table

			return $num;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *      Charge dans cache la liste des délais de livraison possibles
	 *
	 *      @return     int             Nb of lines loaded, <0 if KO
	 */
	function load_cache_availability()
	{
        // phpcs:enable
		global $langs;

		$num = count($this->cache_availability);
		if ($num > 0) return 0;    // Cache already loaded

		dol_syslog(__METHOD__, LOG_DEBUG);

		$langs->load('propal');

		$sql = "SELECT rowid, code, label";
		$sql.= " FROM ".MAIN_DB_PREFIX.'c_availability';
		$sql.= " WHERE active > 0";

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

			$this->cache_availability = dol_sort_array($this->cache_availability, 'label', 'asc', 0, 0, 1);

			return $num;
		}
		else
		{
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

		dol_syslog(__METHOD__." selected=".$selected.", htmlname=".$htmlname, LOG_DEBUG);

		print '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">';
		if ($addempty) print '<option value="0">&nbsp;</option>';
		foreach($this->cache_availability as $id => $arrayavailability)
		{
			if ($selected == $id)
			{
				print '<option value="'.$id.'" selected>';
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
	 *      @return     int             Nb of lines loaded, <0 if KO
	 */
	function loadCacheInputReason()
	{
		global $langs;

		$num = count($this->cache_demand_reason);
		if ($num > 0) return 0;    // Cache already loaded

		$sql = "SELECT rowid, code, label";
		$sql.= " FROM ".MAIN_DB_PREFIX.'c_input_reason';
		$sql.= " WHERE active > 0";

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
				$label=($obj->label!='-'?$obj->label:'');
				if ($langs->trans("DemandReasonType".$obj->code) != ("DemandReasonType".$obj->code)) $label = $langs->trans("DemandReasonType".$obj->code); // So translation key DemandReasonTypeSRC_XXX will work
				if ($langs->trans($obj->code) != $obj->code) $label=$langs->trans($obj->code);																// So translation key SRC_XXX will work

				$tmparray[$obj->rowid]['id']   =$obj->rowid;
				$tmparray[$obj->rowid]['code'] =$obj->code;
				$tmparray[$obj->rowid]['label']=$label;
				$i++;
			}

			$this->cache_demand_reason=dol_sort_array($tmparray, 'label', 'asc', 0, 0, 1);

			unset($tmparray);
			return $num;
		}
		else
		{
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
		if ($addempty) print '<option value="0"'.(empty($selected)?' selected':'').'>&nbsp;</option>';
		foreach($this->cache_demand_reason as $id => $arraydemandreason)
		{
			if ($arraydemandreason['code']==$exclude) continue;

			if ($selected && ($selected == $arraydemandreason['id'] || $selected == $arraydemandreason['code']))
			{
				print '<option value="'.$arraydemandreason['id'].'" selected>';
			}
			else
			{
				print '<option value="'.$arraydemandreason['id'].'">';
			}
			$label=$arraydemandreason['label'];	// Translation of label was already done into the ->loadCacheInputReason
			print $langs->trans($label);
			print '</option>';
		}
		print '</select>';
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *      Charge dans cache la liste des types de paiements possibles
	 *
	 *      @return     int                 Nb of lines loaded, <0 if KO
	 */
	function load_cache_types_paiements()
	{
        // phpcs:enable
		global $langs;

		$num=count($this->cache_types_paiements);
		if ($num > 0) return $num;    // Cache already loaded

		dol_syslog(__METHOD__, LOG_DEBUG);

		$this->cache_types_paiements = array();

		$sql = "SELECT id, code, libelle as label, type, active";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_paiement";
		$sql.= " WHERE entity IN (".getEntity('c_paiement').")";
		//if ($active >= 0) $sql.= " AND active = ".$active;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
				$label=($langs->transnoentitiesnoconv("PaymentTypeShort".$obj->code)!=("PaymentTypeShort".$obj->code)?$langs->transnoentitiesnoconv("PaymentTypeShort".$obj->code):($obj->label!='-'?$obj->label:''));
				$this->cache_types_paiements[$obj->id]['id'] =$obj->id;
				$this->cache_types_paiements[$obj->id]['code'] =$obj->code;
				$this->cache_types_paiements[$obj->id]['label']=$label;
				$this->cache_types_paiements[$obj->id]['type'] =$obj->type;
				$this->cache_types_paiements[$obj->id]['active'] =$obj->active;
				$i++;
			}

			$this->cache_types_paiements = dol_sort_array($this->cache_types_paiements, 'label', 'asc', 0, 0, 1);

			return $num;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *      Return list of payment modes.
	 *      Constant MAIN_DEFAULT_PAYMENT_TERM_ID can used to set default value but scope is all application, probably not what you want.
	 *      See instead to force the default value by the caller.
	 *
	 *      @param	int		$selected		Id of payment term to preselect by default
	 *      @param	string	$htmlname		Nom de la zone select
	 *      @param	int		$filtertype		Not used
	 *		@param	int		$addempty		Add an empty entry
	 * 		@param	int		$noinfoadmin		0=Add admin info, 1=Disable admin info
	 * 		@param	string	$morecss			Add more CSS on select tag
	 *		@return	void
	 */
	function select_conditions_paiements($selected=0, $htmlname='condid', $filtertype=-1, $addempty=0, $noinfoadmin=0, $morecss='')
	{
        // phpcs:enable
		global $langs, $user, $conf;

		dol_syslog(__METHOD__." selected=".$selected.", htmlname=".$htmlname, LOG_DEBUG);

		$this->load_cache_conditions_paiements();

		// Set default value if not already set by caller
		if (empty($selected) && ! empty($conf->global->MAIN_DEFAULT_PAYMENT_TERM_ID)) $selected = $conf->global->MAIN_DEFAULT_PAYMENT_TERM_ID;

		print '<select id="'.$htmlname.'" class="flat selectpaymentterms'.($morecss?' '.$morecss:'').'" name="'.$htmlname.'">';
		if ($addempty) print '<option value="0">&nbsp;</option>';
		foreach($this->cache_conditions_paiements as $id => $arrayconditions)
		{
			if ($selected == $id)
			{
				print '<option value="'.$id.'" selected>';
			}
			else
			{
				print '<option value="'.$id.'">';
			}
			print $arrayconditions['label'];
			print '</option>';
		}
		print '</select>';
		if ($user->admin && empty($noinfoadmin)) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *      Return list of payment methods
	 *
	 *      @param	string	$selected       Id du mode de paiement pre-selectionne
	 *      @param  string	$htmlname       Nom de la zone select
	 *      @param  string	$filtertype     To filter on field type in llx_c_paiement ('CRDT' or 'DBIT' or array('code'=>xx,'label'=>zz))
	 *      @param  int		$format         0=id+libelle, 1=code+code, 2=code+libelle, 3=id+code
	 *      @param  int		$empty			1=peut etre vide, 0 sinon
	 * 		@param	int		$noadmininfo	0=Add admin info, 1=Disable admin info
	 *      @param  int		$maxlength      Max length of label
	 *      @param  int     $active         Active or not, -1 = all
	 *      @param  string  $morecss        Add more CSS on select tag
	 * 		@return	void
	 */
	function select_types_paiements($selected='', $htmlname='paiementtype', $filtertype='', $format=0, $empty=1, $noadmininfo=0, $maxlength=0, $active=1, $morecss='')
	{
        // phpcs:enable
		global $langs,$user;

		dol_syslog(__METHOD__." ".$selected.", ".$htmlname.", ".$filtertype.", ".$format, LOG_DEBUG);

		$filterarray=array();
		if ($filtertype == 'CRDT')  	$filterarray=array(0,2,3);
		elseif ($filtertype == 'DBIT') 	$filterarray=array(1,2,3);
		elseif ($filtertype != '' && $filtertype != '-1') $filterarray=explode(',',$filtertype);

		$this->load_cache_types_paiements();

		print '<select id="select'.$htmlname.'" class="flat selectpaymenttypes'.($morecss?' '.$morecss:'').'" name="'.$htmlname.'">';
		if ($empty) print '<option value="">&nbsp;</option>';
		foreach($this->cache_types_paiements as $id => $arraytypes)
		{
			// If not good status
			if ($active >= 0 && $arraytypes['active'] != $active) continue;

			// On passe si on a demande de filtrer sur des modes de paiments particuliers
			if (count($filterarray) && ! in_array($arraytypes['type'],$filterarray)) continue;

			// We discard empty line if showempty is on because an empty line has already been output.
			if ($empty && empty($arraytypes['code'])) continue;

			if ($format == 0) print '<option value="'.$id.'"';
			elseif ($format == 1) print '<option value="'.$arraytypes['code'].'"';
			elseif ($format == 2) print '<option value="'.$arraytypes['code'].'"';
			elseif ($format == 3) print '<option value="'.$id.'"';
			// Si selected est text, on compare avec code, sinon avec id
			if (preg_match('/[a-z]/i', $selected) && $selected == $arraytypes['code']) print ' selected';
			elseif ($selected == $id) print ' selected';
			print '>';
			if ($format == 0) $value=($maxlength?dol_trunc($arraytypes['label'],$maxlength):$arraytypes['label']);
			elseif ($format == 1) $value=$arraytypes['code'];
			elseif ($format == 2) $value=($maxlength?dol_trunc($arraytypes['label'],$maxlength):$arraytypes['label']);
			elseif ($format == 3) $value=$arraytypes['code'];
			print $value?$value:'&nbsp;';
			print '</option>';
		}
		print '</select>';
		if ($user->admin && ! $noadmininfo) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
	}


	/**
	 *  Selection HT or TTC
	 *
	 *  @param	string	$selected       Id pre-selectionne
	 *  @param  string	$htmlname       Nom de la zone select
	 * 	@return	string					Code of HTML select to chose tax or not
	 */
	function selectPriceBaseType($selected='',$htmlname='price_base_type')
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
				$return.= '<option value="'.$id.'" selected>'.$value;
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

		$sql = "SELECT rowid, code, libelle as label";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode";
		$sql.= " WHERE active > 0";
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
						print '<option value="'.$obj->rowid.'" selected>';
					} else {
						print '<option value="'.$obj->rowid.'">';
					}
					print ($langs->trans("SendingMethod".strtoupper($obj->code)) != "SendingMethod".strtoupper($obj->code)) ? $langs->trans("SendingMethod".strtoupper($obj->code)) : $obj->label;
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
			$this->selectShippingMethod($selected, $htmlname, '', $addempty);
			print '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			print '</form>';
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
	 * @param     string  $selected   		Preselected ID
	 * @param     int     $socid      		Company ID
	 *
	 * @return    string                     HTML select
	 */
	function selectSituationInvoices($selected = '', $socid = 0)
	{
		global $langs;

		$langs->load('bills');

		$opt = '<option value ="" selected></option>';
		$sql = 'SELECT rowid, facnumber, situation_cycle_ref, situation_counter, situation_final, fk_soc';
		$sql.= ' FROM ' . MAIN_DB_PREFIX . 'facture';
		$sql.= ' WHERE entity IN ('.getEntity('facture').')';
		$sql.= ' AND situation_counter>=1';
		$sql.= ' ORDER by situation_cycle_ref, situation_counter desc';
		$resql = $this->db->query($sql);
		if ($resql && $this->db->num_rows($resql) > 0) {
			// Last seen cycle
			$ref = 0;
			while ($obj = $this->db->fetch_object($resql)){
				//Same company ?
			    if ($socid == $obj->fk_soc) {
					//Same cycle ?
			        if ($obj->situation_cycle_ref != $ref) {
						// Just seen this cycle
			            $ref = $obj->situation_cycle_ref;
						//not final ?
			            if ($obj->situation_final != 1) {
							//Not prov?
			                if (substr($obj->facnumber, 1, 4) != 'PROV') {
			                    if ($selected == $obj->rowid) {
			                        $opt .= '<option value="' . $obj->rowid . '" selected>' . $obj->facnumber . '</option>';
								} else {
								    $opt .= '<option value="' . $obj->rowid . '">' . $obj->facnumber . '</option>';
								}
							}
						}
					}
				}
			}
		}
		else
		{
				dol_syslog("Error sql=" . $sql . ", error=" . $this->error, LOG_ERR);
		}
		if ($opt == '<option value ="" selected></option>')
		{
			$opt = '<option value ="0" selected>' . $langs->trans('NoSituations') . '</option>';
		}
		return $opt;
	}

	/**
	 *      Creates HTML units selector (code => label)
	 *
	 *      @param	string	$selected       Preselected Unit ID
	 *      @param  string	$htmlname       Select name
	 *      @param	int		$showempty		Add a nempty line
	 * 		@return	string                  HTML select
	 */
	function selectUnits($selected = '', $htmlname = 'units', $showempty=0)
	{
		global $langs;

		$langs->load('products');

		$return= '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'">';

		$sql = 'SELECT rowid, label, code from '.MAIN_DB_PREFIX.'c_units';
		$sql.= ' WHERE active > 0';

		$resql = $this->db->query($sql);
		if($resql && $this->db->num_rows($resql) > 0)
		{
			if ($showempty) $return .= '<option value="none"></option>';

			while($res = $this->db->fetch_object($resql))
			{
			    $unitLabel = $res->label;
			    if (! empty($langs->tab_translate['unit'.$res->code]))	// check if Translation is available before
			    {
			        $unitLabel = $langs->trans('unit'.$res->code)!=$res->label?$langs->trans('unit'.$res->code):$res->label;
			    }

				if ($selected == $res->rowid)
				{
				    $return.='<option value="'.$res->rowid.'" selected>'.$unitLabel.'</option>';
				}
				else
				{
				    $return.='<option value="'.$res->rowid.'">'.$unitLabel.'</option>';
				}
			}
			$return.='</select>';
		}
		return $return;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Return a HTML select list of bank accounts
	 *
	 *  @param	string	$selected           Id account pre-selected
	 *  @param  string	$htmlname           Name of select zone
	 *  @param  int		$statut             Status of searched accounts (0=open, 1=closed, 2=both)
	 *  @param  string	$filtre             To filter list
	 *  @param  int		$useempty           1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
	 *  @param  string	$moreattrib         To add more attribute on select
	 *  @param	int		$showcurrency		Show currency in label
	 * 	@return	void
	 */
	function select_comptes($selected='',$htmlname='accountid',$statut=0,$filtre='',$useempty=0,$moreattrib='',$showcurrency=0)
	{
        // phpcs:enable
		global $langs, $conf;

		$langs->load("admin");

		$sql = "SELECT rowid, label, bank, clos as status, currency_code";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
		$sql.= " WHERE entity IN (".getEntity('bank_account').")";
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
						print '<option value="'.$obj->rowid.'" selected>';
					}
					else
					{
						print '<option value="'.$obj->rowid.'">';
					}
					print trim($obj->label);
					if ($showcurrency) print ' ('.$obj->currency_code.')';
					if ($statut == 2 && $obj->status == 1) print ' ('.$langs->trans("Closed").')';
					print '</option>';
					$i++;
				}
				print "</select>";
			}
			else
			{
				if ($statut == 0) print $langs->trans("NoActiveBankAccountDefined");
				else print $langs->trans("NoBankAccountFound");
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
			$this->select_comptes($selected, $htmlname, 0, '', $addempty);
			print '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			print '</form>';
		} else {

			$langs->load('banks');

			if ($selected) {
				require_once DOL_DOCUMENT_ROOT .'/compta/bank/class/account.class.php';
				$bankstatic=new Account($this->db);
				$result = $bankstatic->fetch($selected);
				if ($result) print $bankstatic->getNomUrl(1);
			} else {
				print "&nbsp;";
			}
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *    Return list of categories having choosed type
	 *
	 *    @param	string|int	$type				Type of category ('customer', 'supplier', 'contact', 'product', 'member'). Old mode (0, 1, 2, ...) is deprecated.
	 *    @param    string		$selected    		Id of category preselected or 'auto' (autoselect category if there is only one element)
	 *    @param    string		$htmlname			HTML field name
	 *    @param    int			$maxlength      	Maximum length for labels
	 *    @param    int			$excludeafterid 	Exclude all categories after this leaf in category tree.
	 *    @param	int			$outputmode			0=HTML select string, 1=Array
	 *    @return	string
	 *    @see select_categories
	 */
	function select_all_categories($type, $selected='', $htmlname="parent", $maxlength=64, $excludeafterid=0, $outputmode=0)
	{
        // phpcs:enable
		global $conf, $langs;
		$langs->load("categories");

		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

		// For backward compatibility
		if (is_numeric($type))
		{
			dol_syslog(__METHOD__ . ': using numeric value for parameter type is deprecated. Use string code instead.', LOG_WARNING);
		}

		if ($type === Categorie::TYPE_BANK_LINE)
		{
			// TODO Move this into common category feature
			$categids=array();
			$sql = "SELECT c.label, c.rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."bank_categ as c";
			$sql.= " WHERE entity = ".$conf->entity;
			$sql.= " ORDER BY c.label";
			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
					$objp = $this->db->fetch_object($result);
					if ($objp) $cate_arbo[$objp->rowid]=array('id'=>$objp->rowid, 'fulllabel'=>$objp->label);
					$i++;
				}
				$this->db->free($result);
			}
			else dol_print_error($this->db);
		}
		else
		{
			$cat = new Categorie($this->db);
			$cate_arbo = $cat->get_full_arbo($type, $excludeafterid);
		}

		$output = '<select class="flat" name="'.$htmlname.'" id="'.$htmlname.'">';
		$outarray=array();
		if (is_array($cate_arbo))
		{
			if (! count($cate_arbo)) $output.= '<option value="-1" disabled>'.$langs->trans("NoCategoriesDefined").'</option>';
			else
			{
				$output.= '<option value="-1">&nbsp;</option>';
				foreach($cate_arbo as $key => $value)
				{
					if ($cate_arbo[$key]['id'] == $selected || ($selected == 'auto' && count($cate_arbo) == 1))
					{
						$add = 'selected ';
					}
					else
					{
						$add = '';
					}
					$output.= '<option '.$add.'value="'.$cate_arbo[$key]['id'].'">'.dol_trunc($cate_arbo[$key]['fulllabel'],$maxlength,'middle').'</option>';

					$outarray[$cate_arbo[$key]['id']] = $cate_arbo[$key]['fulllabel'];
				}
			}
		}
		$output.= '</select>';
		$output.= "\n";

		if ($outputmode) return $outarray;
		return $output;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
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
	 *     @see formconfirm()
	 */
	function form_confirm($page, $title, $question, $action, $formquestion='', $selectedchoice="", $useajax=0, $height=170, $width=500)
	{
        // phpcs:enable
        dol_syslog(__METHOD__ . ': using form_confirm is deprecated. Use formconfim instead.', LOG_WARNING);
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
	 *     @param  	string		$page        	   	Url of page to call if confirmation is OK. Can contains paramaters (param 'action' and 'confirm' will be reformated)
	 *     @param	string		$title       	   	Title
	 *     @param	string		$question    	   	Question
	 *     @param 	string		$action      	   	Action
	 *	   @param  	array		$formquestion	   	An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , ))
	 *												type can be 'hidden', 'text', 'password', 'checkbox', 'radio', 'date', 'morecss', ...
	 * 	   @param  	string		$selectedchoice  	'' or 'no', or 'yes' or '1' or '0'
	 * 	   @param  	int			$useajax		   	0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=Yes and preoutput confirm box with div id=dialog-confirm-xxx
	 *     @param  	int			$height          	Force height of box
	 *     @param	int			$width				Force width of box ('999' or '90%'). Ignored and forced to 90% on smartphones.
	 *     @param	int			$disableformtag		1=Disable form tag. Can be used if we are already inside a <form> section.
	 *     @return 	string      	    			HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
	 */
	function formconfirm($page, $title, $question, $action, $formquestion='', $selectedchoice='', $useajax=0, $height=210, $width=500, $disableformtag=0)
	{
		global $langs,$conf;
		global $useglobalvars;

		$more='';
		$formconfirm='';
		$inputok=array();
		$inputko=array();

		// Clean parameters
		$newselectedchoice=empty($selectedchoice)?"no":$selectedchoice;
		if ($conf->browser->layout == 'phone') $width='95%';

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
			$more.='<table class="paddingtopbottomonly" width="100%">'."\n";
			if (! empty($formquestion['text'])) $more.='<tr><td colspan="2">'.$formquestion['text'].'</td></tr>'."\n";
			foreach ($formquestion as $key => $input)
			{
				if (is_array($input) && ! empty($input))
				{
					$size=(! empty($input['size'])?' size="'.$input['size'].'"':'');
					$moreattr=(! empty($input['moreattr'])?' '.$input['moreattr']:'');
					$morecss=(! empty($input['morecss'])?' '.$input['morecss']:'');

					if ($input['type'] == 'text')
					{
						$more.='<tr><td'.(empty($input['tdclass'])?'':(' class="'.$input['tdclass'].'"')).'>'.$input['label'].'</td><td align="left"><input type="text" class="flat'.$morecss.'" id="'.$input['name'].'" name="'.$input['name'].'"'.$size.' value="'.$input['value'].'"'.$moreattr.' /></td></tr>'."\n";
					}
					elseif ($input['type'] == 'password')
					{
						$more.='<tr><td'.(empty($input['tdclass'])?'':(' class="'.$input['tdclass'].'"')).'>'.$input['label'].'</td><td align="left"><input type="password" class="flat'.$morecss.'" id="'.$input['name'].'" name="'.$input['name'].'"'.$size.' value="'.$input['value'].'"'.$moreattr.' /></td></tr>'."\n";
					}
					elseif ($input['type'] == 'select')
					{
						$more.='<tr><td'.(empty($input['tdclass'])?'':(' class="'.$input['tdclass'].'"')).'>';
						if (! empty($input['label'])) $more.=$input['label'].'</td><td class="tdtop" align="left">';
						$more.=$this->selectarray($input['name'],$input['values'],$input['default'],1,0,0,$moreattr,0,0,0,'',$morecss);
						$more.='</td></tr>'."\n";
					}
					elseif ($input['type'] == 'checkbox')
					{
						$more.='<tr>';
						$more.='<td'.(empty($input['tdclass'])?'':(' class="'.$input['tdclass'].'"')).'>'.$input['label'].' </td><td align="left">';
						$more.='<input type="checkbox" class="flat'.$morecss.'" id="'.$input['name'].'" name="'.$input['name'].'"'.$moreattr;
						if (! is_bool($input['value']) && $input['value'] != 'false' && $input['value'] != '0') $more.=' checked';
						if (is_bool($input['value']) && $input['value']) $more.=' checked';
						if (isset($input['disabled'])) $more.=' disabled';
						$more.=' /></td>';
						$more.='</tr>'."\n";
					}
					elseif ($input['type'] == 'radio')
					{
						$i=0;
						foreach($input['values'] as $selkey => $selval)
						{
							$more.='<tr>';
							if ($i==0) $more.='<td'.(empty($input['tdclass'])?' class="tdtop"':(' class="tdtop '.$input['tdclass'].'"')).'>'.$input['label'].'</td>';
							else $more.='<td'.(empty($input['tdclass'])?'':(' class="'.$input['tdclass'].'"')).'>&nbsp;</td>';
							$more.='<td><input type="radio" class="flat'.$morecss.'" id="'.$input['name'].'" name="'.$input['name'].'" value="'.$selkey.'"'.$moreattr;
							if ($input['disabled']) $more.=' disabled';
							$more.=' /> ';
							$more.=$selval;
							$more.='</td></tr>'."\n";
							$i++;
						}
					}
					elseif ($input['type'] == 'date')
					{
						$more.='<tr><td'.(empty($input['tdclass'])?'':(' class="'.$input['tdclass'].'"')).'>'.$input['label'].'</td>';
						$more.='<td align="left">';
						$more.=$this->selectDate($input['value'],$input['name'],0,0,0,'',1,0);
						$more.='</td></tr>'."\n";
						$formquestion[] = array('name'=>$input['name'].'day');
						$formquestion[] = array('name'=>$input['name'].'month');
						$formquestion[] = array('name'=>$input['name'].'year');
						$formquestion[] = array('name'=>$input['name'].'hour');
						$formquestion[] = array('name'=>$input['name'].'min');
					}
					elseif ($input['type'] == 'other')
					{
						$more.='<tr><td'.(empty($input['tdclass'])?'':(' class="'.$input['tdclass'].'"')).'>';
						if (! empty($input['label'])) $more.=$input['label'].'</td><td align="left">';
						$more.=$input['value'];
						$more.='</td></tr>'."\n";
					}

					elseif ($input['type'] == 'onecolumn')
					{
						$more.='<tr><td colspan="2" align="left">';
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
					//print "xx ".$key." rr ".is_array($input)."<br>\n";
					if (is_array($input) && isset($input['name'])) array_push($inputok,$input['name']);
					if (isset($input['inputko']) && $input['inputko'] == 1) array_push($inputko,$input['name']);
				}
			}
			// Show JQuery confirm box. Note that global var $useglobalvars is used inside this template
			$formconfirm.= '<div id="'.$dialogconfirm.'" title="'.dol_escape_htmltag($title).'" style="display: none;">';
			if (! empty($more)) {
				$formconfirm.= '<div class="confirmquestions">'.$more.'</div>';
			}
			$formconfirm.= ($question ? '<div class="confirmmessage">'.img_help('','').' '.$question . '</div>': '');
			$formconfirm.= '</div>'."\n";

			$formconfirm.= "\n<!-- begin ajax formconfirm page=".$page." -->\n";
			$formconfirm.= '<script type="text/javascript">'."\n";
			$formconfirm.= 'jQuery(document).ready(function() {
            $(function() {
            	$( "#'.$dialogconfirm.'" ).dialog(
            	{
                    autoOpen: '.($autoOpen ? "true" : "false").',';
			if ($newselectedchoice == 'no')
			{
				$formconfirm.='
						open: function() {
            				$(this).parent().find("button.ui-button:eq(2)").focus();
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
                         			options += "&" + inputname + "=" + encodeURIComponent(inputvalue);
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
                         			options += "&" + inputname + "=" + encodeURIComponent(inputvalue);
                         		});
                         	}
                         	var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "") + options;
                         	//alert(urljump);
            				if (pageno.length > 0) { location.href = urljump; }
                            $(this).dialog("close");
                        }
                    }
                }
                );

            	var button = "'.$button.'";
            	if (button.length > 0) {
                	$( "#" + button ).click(function() {
                		$("#'.$dialogconfirm.'").dialog("open");
        			});
                }
            });
            });
            </script>';
			$formconfirm.= "<!-- end ajax formconfirm -->\n";
		}
		else
		{
			$formconfirm.= "\n<!-- begin formconfirm page=".$page." -->\n";

			if (empty($disableformtag)) $formconfirm.= '<form method="POST" action="'.$page.'" class="notoptoleftroright">'."\n";

			$formconfirm.= '<input type="hidden" name="action" value="'.$action.'">'."\n";
			if (empty($disableformtag)) $formconfirm.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";

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
			$formconfirm.= '<td class="valid" align="center"><input class="button valignmiddle" type="submit" value="'.$langs->trans("Validate").'"></td>';
			$formconfirm.= '</tr>'."\n";

			$formconfirm.= '</table>'."\n";

			if (empty($disableformtag)) $formconfirm.= "</form>\n";
			$formconfirm.= '<br>';

			$formconfirm.= "<!-- end formconfirm -->\n";
		}

		return $formconfirm;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *    Show a form to select a project
	 *
	 *    @param	int		$page        		Page
	 *    @param	int		$socid       		Id third party (-1=all, 0=only projects not linked to a third party, id=projects not linked or linked to third party id)
	 *    @param    int		$selected    		Id pre-selected project
	 *    @param    string	$htmlname    		Name of select field
	 *    @param	int		$discard_closed		Discard closed projects (0=Keep,1=hide completely except $selected,2=Disable)
	 *    @param	int		$maxlength			Max length
	 *    @param	int		$forcefocus			Force focus on field (works with javascript only)
	 *    @param    int     $nooutput           No print is done. String is returned.
	 *    @return	string                      Return html content
	 */
	function form_project($page, $socid, $selected='', $htmlname='projectid', $discard_closed=0, $maxlength=20, $forcefocus=0, $nooutput=0)
	{
        // phpcs:enable
		global $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

		$out='';

		$formproject=new FormProjets($this->db);

		$langs->load("project");
		if ($htmlname != "none")
		{
			$out.="\n";
			$out.='<form method="post" action="'.$page.'">';
			$out.='<input type="hidden" name="action" value="classin">';
			$out.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			$out.=$formproject->select_projects($socid, $selected, $htmlname, $maxlength, 0, 1, $discard_closed, $forcefocus, 0, 0, '', 1);
			$out.='<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
			$out.='</form>';
		}
		else
		{
			if ($selected)
			{
				$projet = new Project($this->db);
				$projet->fetch($selected);
				//print '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$selected.'">'.$projet->title.'</a>';
				$out.=$projet->getNomUrl(0,'',1);
			}
			else
			{
				$out.="&nbsp;";
			}
		}

		if (empty($nooutput))
		{
			print $out;
			return '';
		}
		return $out;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
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
        // phpcs:enable
		global $langs;
		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setconditions">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			$this->select_conditions_paiements($selected,$htmlname,-1,$addempty);
			print '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			print '</form>';
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
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
        // phpcs:enable
		global $langs;
		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setavailability">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			$this->selectAvailabilityDelay($selected,$htmlname,-1,$addempty);
			print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
			print '</form>';
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
			$this->selectInputReason($selected,$htmlname,-1,$addempty);
			print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
			print '</form>';
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *    Show a form + html select a date
	 *
	 *    @param	string		$page        	Page
	 *    @param	string		$selected    	Date preselected
	 *    @param    string		$htmlname    	Html name of date input fields or 'none'
	 *    @param    int			$displayhour 	Display hour selector
	 *    @param    int			$displaymin		Display minutes selector
	 *    @param	int			$nooutput		1=No print output, return string
	 *    @return	string
	 *    @see		selectDate
	 */
	function form_date($page, $selected, $htmlname, $displayhour=0, $displaymin=0, $nooutput=0)
	{
        // phpcs:enable
		global $langs;

		$ret='';

		if ($htmlname != "none")
		{
			$ret.='<form method="post" action="'.$page.'" name="form'.$htmlname.'">';
			$ret.='<input type="hidden" name="action" value="set'.$htmlname.'">';
			$ret.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			$ret.='<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			$ret.='<tr><td>';
			$ret.=$this->selectDate($selected,$htmlname,$displayhour,$displaymin,1,'form'.$htmlname,1,0);
			$ret.='</td>';
			$ret.='<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			$ret.='</tr></table></form>';
		}
		else
		{
			if ($displayhour) $ret.=dol_print_date($selected,'dayhour');
			else $ret.=dol_print_date($selected,'day');
		}

		if (empty($nooutput)) print $ret;
		return $ret;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Show a select form to choose a user
	 *
	 *  @param	string	$page        	Page
	 *  @param  string	$selected    	Id of user preselected
	 *  @param  string	$htmlname    	Name of input html field. If 'none', we just output the user link.
	 *  @param  array	$exclude		List of users id to exclude
	 *  @param  array	$include        List of users id to include
	 *  @return	void
	 */
	function form_users($page, $selected='', $htmlname='userid', $exclude='', $include='')
	{
        // phpcs:enable
		global $langs;

		if ($htmlname != "none")
		{
			print '<form method="POST" action="'.$page.'" name="form'.$htmlname.'">';
			print '<input type="hidden" name="action" value="set'.$htmlname.'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print $this->select_dolusers($selected,$htmlname,1,$exclude,0,$include);
			print '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			print '</form>';
		}
		else
		{
			if ($selected)
			{
				require_once DOL_DOCUMENT_ROOT .'/user/class/user.class.php';
				$theuser=new User($this->db);
				$theuser->fetch($selected);
				print $theuser->getNomUrl(1);
			} else {
				print "&nbsp;";
			}
		}
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *    Show form with payment mode
	 *
	 *    @param	string	$page        	Page
	 *    @param    int		$selected    	Id mode pre-selectionne
	 *    @param    string	$htmlname    	Name of select html field
	 *    @param  	string	$filtertype		To filter on field type in llx_c_paiement (array('code'=>xx,'label'=>zz))
	 *    @param    int     $active         Active or not, -1 = all
	 *    @param   int     $addempty       1=Add empty entry
	 *    @return	void
	 */
	function form_modes_reglement($page, $selected='', $htmlname='mode_reglement_id', $filtertype='', $active=1, $addempty=0)
	{
        // phpcs:enable
		global $langs;
		if ($htmlname != "none")
		{
			print '<form method="POST" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setmode">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			$this->select_types_paiements($selected, $htmlname, $filtertype, 0, $addempty, 0, 0, $active);
			print '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			print '</form>';
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *    Show form with multicurrency code
	 *
	 *    @param	string	$page        	Page
	 *    @param    string	$selected    	code pre-selectionne
	 *    @param    string	$htmlname    	Name of select html field
	 *    @return	void
	 */
	function form_multicurrency_code($page, $selected='', $htmlname='multicurrency_code')
	{
        // phpcs:enable
		global $langs;
		if ($htmlname != "none")
		{
			print '<form method="POST" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setmulticurrencycode">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print $this->selectMultiCurrency($selected, $htmlname, 0);
			print '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			print '</form>';
		}
		else
		{
			dol_include_once('/core/lib/company.lib.php');
			print !empty($selected) ? currency_name($selected,1) : '&nbsp;';
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *    Show form with multicurrency rate
	 *
	 *    @param	string	$page        	Page
	 *    @param    double	$rate	    	Current rate
	 *    @param    string	$htmlname    	Name of select html field
	 *    @param    string  $currency       Currency code to explain the rate
	 *    @return	void
	 */
	function form_multicurrency_rate($page, $rate='', $htmlname='multicurrency_tx', $currency='')
	{
        // phpcs:enable
		global $langs, $mysoc, $conf;

		if ($htmlname != "none")
		{
			print '<form method="POST" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setmulticurrencyrate">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="text" name="'.$htmlname.'" value="'.(!empty($rate) ? price($rate) : 1).'" size="10" /> ';
			print '<select name="calculation_mode">';
			print '<option value="1">'.$currency.' > '.$conf->currency.'</option>';
			print '<option value="2">'.$conf->currency.' > '.$currency.'</option>';
			print '</select> ';
			print '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			print '</form>';
		}
		else
		{
			if (! empty($rate))
			{
				print price($rate, 1, $langs, 1, 0);
				if ($currency && $rate != 1) print ' &nbsp; ('.price($rate, 1, $langs, 1, 0).' '.$currency.' = 1 '.$conf->currency.')';
			}
			else
			{
				print 1;
			}
		}
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Show a select box with available absolute discounts
	 *
	 *  @param  string	$page        	Page URL where form is shown
	 *  @param  int		$selected    	Value pre-selected
	 *	@param  string	$htmlname    	Name of SELECT component. If 'none', not changeable. Example 'remise_id'.
	 *	@param	int		$socid			Third party id
	 * 	@param	float	$amount			Total amount available
	 * 	@param	string	$filter			SQL filter on discounts
	 * 	@param	int		$maxvalue		Max value for lines that can be selected
	 *  @param  string	$more           More string to add
	 *  @param  int     $hidelist       1=Hide list
	 *  @param	int		$discount_type	0 => customer discount, 1 => supplier discount
	 *  @return	void
	 */
	function form_remise_dispo($page, $selected, $htmlname, $socid, $amount, $filter='', $maxvalue=0, $more='', $hidelist=0, $discount_type=0)
	{
        // phpcs:enable
		global $conf,$langs;
		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setabsolutediscount">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<div class="inline-block">';
			if(! empty($discount_type)) {
				if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
				{
					if (! $filter || $filter=="fk_invoice_supplier_source IS NULL") $translationKey = 'HasAbsoluteDiscountFromSupplier';    // If we want deposit to be substracted to payments only and not to total of final invoice
					else $translationKey = 'HasCreditNoteFromSupplier';
				}
				else
				{
					if (! $filter || $filter=="fk_invoice_supplier_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS PAID)%')") $translationKey = 'HasAbsoluteDiscountFromSupplier';
					else $translationKey = 'HasCreditNoteFromSupplier';
				}
			} else {
				if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
				{
					if (! $filter || $filter=="fk_facture_source IS NULL") $translationKey = 'CompanyHasAbsoluteDiscount';    // If we want deposit to be substracted to payments only and not to total of final invoice
					else $translationKey = 'CompanyHasCreditNote';
				}
				else
				{
					if (! $filter || $filter=="fk_facture_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS RECEIVED)%')") $translationKey = 'CompanyHasAbsoluteDiscount';
					else $translationKey = 'CompanyHasCreditNote';
				}
			}
			print $langs->trans($translationKey,price($amount,0,$langs,0,0,-1,$conf->currency));
			if (empty($hidelist)) print ': ';
			print '</div>';
			if (empty($hidelist))
			{
				print '<div class="inline-block" style="padding-right: 10px">';
				$newfilter = 'discount_type='.intval($discount_type);
				if(! empty($discount_type)) {
					$newfilter.= ' AND fk_invoice_supplier IS NULL AND fk_invoice_supplier_line IS NULL'; // Supplier discounts available
				} else {
					$newfilter.= ' AND fk_facture IS NULL AND fk_facture_line IS NULL'; // Customer discounts available
				}
				if ($filter) $newfilter.=' AND ('.$filter.')';
				$nbqualifiedlines=$this->select_remises($selected,$htmlname,$newfilter,$socid,$maxvalue);
				if ($nbqualifiedlines > 0)
				{
					print ' &nbsp; <input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("UseLine")).'"';
					if(! empty($discount_type) && $filter && $filter != "fk_invoice_supplier_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS PAID)%')")
						print ' title="'.$langs->trans("UseCreditNoteInInvoicePayment").'"';
					if(empty($discount_type) && $filter && $filter != "fk_facture_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS RECEIVED)%')")
						print ' title="'.$langs->trans("UseCreditNoteInInvoicePayment").'"';

					print '>';
				}
				print '</div>';
			}
			if ($more)
			{
				print '<div class="inline-block">';
				print $more;
				print '</div>';
			}
			print '</form>';
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


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *    Show forms to select a contact
	 *
	 *    @param	string		$page        	Page
	 *    @param	Societe		$societe		Filter on third party
	 *    @param    int			$selected    	Id contact pre-selectionne
	 *    @param    string		$htmlname    	Name of HTML select. If 'none', we just show contact link.
	 *    @return	void
	 */
	function form_contacts($page, $societe, $selected='', $htmlname='contactid')
	{
        // phpcs:enable
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
				$contact=new Contact($this->db);
				$contact->fetch($selected);
				print $contact->getFullName($langs);
			} else {
				print "&nbsp;";
			}
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
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
	 *  @param  int     $nooutput       No print output. Return it only.
	 *  @return	void
	 */
	function form_thirdparty($page, $selected='', $htmlname='socid', $filter='',$showempty=0, $showtype=0, $forcecombo=0, $events=array(), $nooutput=0)
	{
        // phpcs:enable
		global $langs;

		$out = '';
		if ($htmlname != "none")
		{
			$out.='<form method="post" action="'.$page.'">';
			$out.= '<input type="hidden" name="action" value="set_thirdparty">';
			$out.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			$out.= $this->select_company($selected, $htmlname, $filter, $showempty, $showtype, $forcecombo, $events);
			$out.= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			$out.= '</form>';
		}
		else
		{
			if ($selected)
			{
				require_once DOL_DOCUMENT_ROOT .'/societe/class/societe.class.php';
				$soc = new Societe($this->db);
				$soc->fetch($selected);
				$out.= $soc->getNomUrl($langs);
			}
			else
			{
				$out.= "&nbsp;";
			}
		}

		if ($nooutput) return $out;
		else print $out;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *    Retourne la liste des devises, dans la langue de l'utilisateur
	 *
	 *    @param	string	$selected    preselected currency code
	 *    @param    string	$htmlname    name of HTML select list
     *    @deprecated
	 *    @return	void
	 */
	function select_currency($selected='',$htmlname='currency_id')
	{
        // phpcs:enable
		print $this->selectCurrency($selected,$htmlname);
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

		$out.= '<select class="flat maxwidth200onsmartphone minwidth300" name="'.$htmlname.'" id="'.$htmlname.'">';
		foreach ($langs->cache_currencies as $code_iso => $currency)
		{
			if ($selected && $selected == $code_iso)
			{
				$out.= '<option value="'.$code_iso.'" selected>';
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

		// Make select dynamic
		include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		$out .= ajax_combobox($htmlname);

		return $out;
	}

	/**
	 *	Return array of currencies in user language
	 *
	 *  @param	string	$selected    preselected currency code
	 *  @param  string	$htmlname    name of HTML select list
	 *  @param  integer	$useempty    1=Add empty line
	 * 	@return	string
	 */
	function selectMultiCurrency($selected='', $htmlname='multicurrency_code', $useempty=0)
	{
		global $db,$conf,$langs,$user;

		$langs->loadCacheCurrencies('');        // Load ->cache_currencies

		$TCurrency = array();

		$sql = 'SELECT code FROM '.MAIN_DB_PREFIX.'multicurrency';
		$sql.= " WHERE entity IN ('".getEntity('mutlicurrency')."')";
		$resql = $db->query($sql);
		if ($resql)
		{
			while ($obj = $db->fetch_object($resql)) $TCurrency[$obj->code] = $obj->code;
		}

		$out='';
		$out.= '<select class="flat" name="'.$htmlname.'" id="'.$htmlname.'">';
		if ($useempty) $out .= '<option value="">&nbsp;</option>';
		// If company current currency not in table, we add it into list. Should always be available.
		if (! in_array($conf->currency, $TCurrency))
		{
			$TCurrency[$conf->currency] = $conf->currency;
		}
		if (count($TCurrency) > 0)
		{
			foreach ($langs->cache_currencies as $code_iso => $currency)
			{
				if (isset($TCurrency[$code_iso]))
				{
					if (!empty($selected) && $selected == $code_iso) $out.= '<option value="'.$code_iso.'" selected="selected">';
					else $out.= '<option value="'.$code_iso.'">';

					$out.= $currency['label'];
					$out.= ' ('.$langs->getCurrencySymbol($code_iso).')';
					$out.= '</option>';
				}
			}
		}

		$out.= '</select>';
		// Make select dynamic
		include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		$out.= ajax_combobox($htmlname);

		return $out;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Load into the cache vat rates of a country
	 *
	 *	@param	string	$country_code		Country code with quotes ("'CA'", or "'CA,IN,...'")
	 *	@return	int							Nb of loaded lines, 0 if already loaded, <0 if KO
	 */
	function load_cache_vatrates($country_code)
	{
        // phpcs:enable
		global $langs;

		$num = count($this->cache_vatrates);
		if ($num > 0) return $num;    // Cache already loaded

		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql  = "SELECT DISTINCT t.rowid, t.code, t.taux, t.localtax1, t.localtax1_type, t.localtax2, t.localtax2_type, t.recuperableonly";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
		$sql.= " WHERE t.fk_pays = c.rowid";
		$sql.= " AND t.active > 0";
		$sql.= " AND c.code IN (".$country_code.")";
		$sql.= " ORDER BY t.code ASC, t.taux ASC, t.recuperableonly ASC";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				for ($i = 0; $i < $num; $i++)
				{
					$obj = $this->db->fetch_object($resql);
					$this->cache_vatrates[$i]['rowid']	= $obj->rowid;
					$this->cache_vatrates[$i]['code']	= $obj->code;
					$this->cache_vatrates[$i]['txtva']	= $obj->taux;
					$this->cache_vatrates[$i]['nprtva']	= $obj->recuperableonly;
					$this->cache_vatrates[$i]['localtax1']	    = $obj->localtax1;
					$this->cache_vatrates[$i]['localtax1_type']	= $obj->localtax1_type;
					$this->cache_vatrates[$i]['localtax2']	    = $obj->localtax2;
					$this->cache_vatrates[$i]['localtax2_type']	= $obj->localtax1_type;

					$this->cache_vatrates[$i]['label']	= $obj->taux.'%'.($obj->code?' ('.$obj->code.')':'');   // Label must contains only 0-9 , . % or *
					$this->cache_vatrates[$i]['labelallrates'] = $obj->taux.'/'.($obj->localtax1?$obj->localtax1:'0').'/'.($obj->localtax2?$obj->localtax2:'0').($obj->code?' ('.$obj->code.')':'');	// Must never be used as key, only label
					$positiverates='';
					if ($obj->taux) $positiverates.=($positiverates?'/':'').$obj->taux;
					if ($obj->localtax1) $positiverates.=($positiverates?'/':'').$obj->localtax1;
					if ($obj->localtax2) $positiverates.=($positiverates?'/':'').$obj->localtax2;
					if (empty($positiverates)) $positiverates='0';
					$this->cache_vatrates[$i]['labelpositiverates'] = $positiverates.($obj->code?' ('.$obj->code.')':'');	// Must never be used as key, only label
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Output an HTML select vat rate.
	 *  The name of this function should be selectVat. We keep bad name for compatibility purpose.
	 *
	 *  @param	string	      $htmlname           Name of HTML select field
	 *  @param  float|string  $selectedrate       Force preselected vat rate. Can be '8.5' or '8.5 (NOO)' for example. Use '' for no forcing.
	 *  @param  Societe	      $societe_vendeuse   Thirdparty seller
	 *  @param  Societe	      $societe_acheteuse  Thirdparty buyer
	 *  @param  int		      $idprod             Id product. O if unknown of NA.
	 *  @param  int		      $info_bits          Miscellaneous information on line (1 for NPR)
	 *  @param  int|string    $type               ''=Unknown, 0=Product, 1=Service (Used if idprod not defined)
	 *                  		                  Si vendeur non assujeti a TVA, TVA par defaut=0. Fin de regle.
	 *                  					      Si le (pays vendeur = pays acheteur) alors la TVA par defaut=TVA du produit vendu. Fin de regle.
	 *                  					      Si (vendeur et acheteur dans Communaute europeenne) et bien vendu = moyen de transports neuf (auto, bateau, avion), TVA par defaut=0 (La TVA doit etre paye par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
	 *                                            Si vendeur et acheteur dans Communauté européenne et acheteur= particulier alors TVA par défaut=TVA du produit vendu. Fin de règle.
	 *                                            Si vendeur et acheteur dans Communauté européenne et acheteur= entreprise alors TVA par défaut=0. Fin de règle.
	 *                  					      Sinon la TVA proposee par defaut=0. Fin de regle.
	 *  @param	bool	     $options_only		  Return HTML options lines only (for ajax treatment)
	 *  @param  int          $mode                0=Use vat rate as key in combo list, 1=Add VAT code after vat rate into key, -1=Use id of vat line as key
	 *  @return	string
	 */
	function load_tva($htmlname='tauxtva', $selectedrate='', $societe_vendeuse='', $societe_acheteuse='', $idprod=0, $info_bits=0, $type='', $options_only=false, $mode=0)
	{
        // phpcs:enable
		global $langs,$conf,$mysoc;

		$langs->load('errors');

		$return='';

		// Define defaultnpr, defaultttx and defaultcode
		$defaultnpr=($info_bits & 0x01);
		$defaultnpr=(preg_match('/\*/',$selectedrate) ? 1 : $defaultnpr);
		$defaulttx=str_replace('*','',$selectedrate);
		$defaultcode='';
		if (preg_match('/\((.*)\)/', $defaulttx, $reg))
		{
			$defaultcode=$reg[1];
			$defaulttx=preg_replace('/\s*\(.*\)/','',$defaulttx);
		}
		//var_dump($selectedrate.'-'.$defaulttx.'-'.$defaultnpr.'-'.$defaultcode);

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
			require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
			if (! isInEEC($societe_vendeuse) && (! is_object($societe_acheteuse) || (isInEEC($societe_acheteuse) && ! $societe_acheteuse->isACompany())))
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
		$num = $this->load_cache_vatrates($code_country);   // If no vat defined, return -1 with message into this->error

		if ($num > 0)
		{
			// Definition du taux a pre-selectionner (si defaulttx non force et donc vaut -1 ou '')
			if ($defaulttx < 0 || dol_strlen($defaulttx) == 0)
			{
				$tmpthirdparty=new Societe($this->db);
				$defaulttx=get_default_tva($societe_vendeuse, (is_object($societe_acheteuse)?$societe_acheteuse:$tmpthirdparty), $idprod);
				$defaultnpr=get_default_npr($societe_vendeuse, (is_object($societe_acheteuse)?$societe_acheteuse:$tmpthirdparty), $idprod);
		        if (preg_match('/\((.*)\)/', $defaulttx, $reg)) {
			        $defaultcode=$reg[1];
			        $defaulttx=preg_replace('/\s*\(.*\)/','',$defaulttx);
		        }
				if (empty($defaulttx)) $defaultnpr=0;
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

			if (! $options_only) $return.= '<select class="flat minwidth75imp" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled':'').$title.'>';

			$selectedfound=false;
			foreach ($this->cache_vatrates as $rate)
			{
				// Keep only 0 if seller is not subject to VAT
				if ($disabled && $rate['txtva'] != 0) continue;

				// Define key to use into select list
				$key = $rate['txtva'];
				$key.= $rate['nprtva'] ? '*': '';
				if ($mode > 0 && $rate['code']) $key.=' ('.$rate['code'].')';
				if ($mode < 0) $key = $rate['rowid'];

				$return.= '<option value="'.$key.'"';
				if (! $selectedfound)
				{
					if ($defaultcode) // If defaultcode is defined, we used it in priority to select combo option instead of using rate+npr flag
					{
						if ($defaultcode == $rate['code'])
						{
							$return.= ' selected';
							$selectedfound=true;
						}
					}
					elseif ($rate['txtva'] == $defaulttx && $rate['nprtva'] == $defaultnpr)
			   		{
			   			$return.= ' selected';
			   			$selectedfound=true;
					}
				}
				$return.= '>';
				//if (! empty($conf->global->MAIN_VAT_SHOW_POSITIVE_RATES))
				if ($mysoc->country_code == 'IN' || ! empty($conf->global->MAIN_VAT_LABEL_IS_POSITIVE_RATES))
				{
					$return.= $rate['labelpositiverates'];
				}
				else
				{
					$return.= vatrate($rate['label']);
				}
				//$return.=($rate['code']?' '.$rate['code']:'');
				$return.= (empty($rate['code']) && $rate['nprtva']) ? ' *': '';         // We show the *  (old behaviour only if new vat code is not used)

				$return.= '</option>';
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


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Show a HTML widget to input a date or combo list for day, month, years and optionaly hours and minutes.
	 *  Fields are preselected with :
	 *            	- set_time date (must be a local PHP server timestamp or string date with format 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM')
	 *            	- local date in user area, if set_time is '' (so if set_time is '', output may differs when done from two different location)
	 *            	- Empty (fields empty), if set_time is -1 (in this case, parameter empty must also have value 1)
	 *
	 *	@param	timestamp	$set_time 		Pre-selected date (must be a local PHP server timestamp), -1 to keep date not preselected, '' to use current date with 00:00 hour (Parameter 'empty' must be 0 or 2).
	 *	@param	string		$prefix			Prefix for fields name
	 *	@param	int			$h				1 or 2=Show also hours (2=hours on a new line), -1 has same effect but hour and minutes are prefilled with 23:59 if date is empty, 3 show hour always empty
	 *	@param	int			$m				1=Show also minutes, -1 has same effect but hour and minutes are prefilled with 23:59 if date is empty, 3 show minutes always empty
	 *	@param	int			$empty			0=Fields required, 1=Empty inputs are allowed, 2=Empty inputs are allowed for hours only
	 *	@param	string		$form_name 		Not used
	 *	@param	int			$d				1=Show days, month, years
	 * 	@param	int			$addnowlink		Add a link "Now"
	 * 	@param	int			$nooutput		Do not output html string but return it
	 * 	@param 	int			$disabled		Disable input fields
	 *  @param  int			$fullday        When a checkbox with this html name is on, hour and day are set with 00:00 or 23:59
	 *  @param	string		$addplusone		Add a link "+1 hour". Value must be name of another select_date field.
	 *  @param  datetime    $adddateof      Add a link "Date of invoice" using the following date.
	 *  @return	string|void					Nothing or string if nooutput is 1
     *  @deprecated
	 *  @see    form_date, select_month, select_year, select_dayofweek
	 */
    function select_date($set_time='', $prefix='re', $h=0, $m=0, $empty=0, $form_name="", $d=1, $addnowlink=0, $nooutput=0, $disabled=0, $fullday='', $addplusone='', $adddateof='')
    {
        // phpcs:enable
        $retstring = $this->selectDate($set_time, $prefix, $h, $m, $empty, $form_name, $d, $addnowlink, $disabled, $fullday, $addplusone, $adddateof);
        if (! empty($nooutput)) {
            return $retstring;
        }
        print $retstring;
        return;
    }

    /**
     *  Show a HTML widget to input a date or combo list for day, month, years and optionaly hours and minutes.
	 *  Fields are preselected with :
	 *              - set_time date (must be a local PHP server timestamp or string date with format 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM')
	 *              - local date in user area, if set_time is '' (so if set_time is '', output may differs when done from two different location)
	 *              - Empty (fields empty), if set_time is -1 (in this case, parameter empty must also have value 1)
	 *
	 *  @param  timestamp   $set_time       Pre-selected date (must be a local PHP server timestamp), -1 to keep date not preselected, '' to use current date with 00:00 hour (Parameter 'empty' must be 0 or 2).
	 *  @param	string		$prefix			Prefix for fields name
	 *  @param	int			$h				1 or 2=Show also hours (2=hours on a new line), -1 has same effect but hour and minutes are prefilled with 23:59 if date is empty, 3 show hour always empty
	 *	@param	int			$m				1=Show also minutes, -1 has same effect but hour and minutes are prefilled with 23:59 if date is empty, 3 show minutes always empty
	 *	@param	int			$empty			0=Fields required, 1=Empty inputs are allowed, 2=Empty inputs are allowed for hours only
	 *	@param	string		$form_name 		Not used
	 *	@param	int			$d				1=Show days, month, years
	 * 	@param	int			$addnowlink		Add a link "Now"
	 * 	@param 	int			$disabled		Disable input fields
	 *  @param  int			$fullday        When a checkbox with this html name is on, hour and day are set with 00:00 or 23:59
	 *  @param	string		$addplusone		Add a link "+1 hour". Value must be name of another selectDate field.
	 *  @param  datetime    $adddateof      Add a link "Date of invoice" using the following date.
	 * 	@return string                      Html for selectDate
	 *  @see    form_date, select_month, select_year, select_dayofweek
	 */
	function selectDate($set_time='', $prefix='re', $h=0, $m=0, $empty=0, $form_name="", $d=1, $addnowlink=0, $disabled=0, $fullday='', $addplusone='', $adddateof='')
	{
		global $conf,$langs;

		$retstring='';

		if ($prefix=='') $prefix='re';
		if ($h == '') $h=0;
		if ($m == '') $m=0;
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
		if (preg_match('/^([0-9]+)\-([0-9]+)\-([0-9]+)\s?([0-9]+)?:?([0-9]+)?/',$set_time,$reg))	// deprecated usage
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
				$ssec = dol_print_date($set_time, "%S");
			}
			else
			{
				$shour = '';
				$smin = '';
				$ssec = '';
			}
		}
		else
		{
			// Date est '' ou vaut -1
			$syear = '';
			$smonth = '';
			$sday = '';
			$shour = !isset($conf->global->MAIN_DEFAULT_DATE_HOUR) ? ($h == -1 ? '23' : '') : $conf->global->MAIN_DEFAULT_DATE_HOUR;
			$smin = !isset($conf->global->MAIN_DEFAULT_DATE_MIN) ? ($h == -1 ? '59' : '') : $conf->global->MAIN_DEFAULT_DATE_MIN;
			$ssec = !isset($conf->global->MAIN_DEFAULT_DATE_SEC) ? ($h == -1 ? '59' : '') : $conf->global->MAIN_DEFAULT_DATE_SEC;
		}
		if ($h == 3) $shour = '';
		if ($m == 3) $smin = '';

		// You can set MAIN_POPUP_CALENDAR to 'eldy' or 'jquery'
		$usecalendar='combo';
		if (! empty($conf->use_javascript_ajax) && (empty($conf->global->MAIN_POPUP_CALENDAR) || $conf->global->MAIN_POPUP_CALENDAR != "none")) {
			$usecalendar = ((empty($conf->global->MAIN_POPUP_CALENDAR) || $conf->global->MAIN_POPUP_CALENDAR == 'eldy')?'jquery':$conf->global->MAIN_POPUP_CALENDAR);
		}

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
					$retstring.='<input id="'.$prefix.'" name="'.$prefix.'" type="text" class="maxwidth75" maxlength="11" value="'.$formated_date.'"';
					$retstring.=($disabled?' disabled':'');
					$retstring.=' onChange="dpChangeDay(\''.$prefix.'\',\''.$langs->trans("FormatDateShortJavaInput").'\'); "';  // FormatDateShortInput for dol_print_date / FormatDateShortJavaInput that is same for javascript
					$retstring.='>';

					// Icone calendrier
					if (! $disabled)
					{
						$retstring.='<button id="'.$prefix.'Button" type="button" class="dpInvisibleButtons"';
						$base=DOL_URL_ROOT.'/core/';
						$retstring.=' onClick="showDP(\''.$base.'\',\''.$prefix.'\',\''.$langs->trans("FormatDateShortJavaInput").'\',\''.$langs->defaultlang.'\');"';
						$retstring.='>'.img_object($langs->trans("SelectDate"),'calendarday','class="datecallink"').'</button>';
					}
					else $retstring.='<button id="'.$prefix.'Button" type="button" class="dpInvisibleButtons">'.img_object($langs->trans("Disabled"),'calendarday','class="datecallink"').'</button>';

					$retstring.='<input type="hidden" id="'.$prefix.'day"   name="'.$prefix.'day"   value="'.$sday.'">'."\n";
					$retstring.='<input type="hidden" id="'.$prefix.'month" name="'.$prefix.'month" value="'.$smonth.'">'."\n";
					$retstring.='<input type="hidden" id="'.$prefix.'year"  name="'.$prefix.'year"  value="'.$syear.'">'."\n";
				}
				elseif ($usecalendar == 'jquery')
				{
					if (! $disabled)
					{
						// Output javascript for datepicker
						$retstring.="<script type='text/javascript'>";
						$retstring.="$(function(){ $('#".$prefix."').datepicker({
							dateFormat: '".$langs->trans("FormatDateShortJQueryInput")."',
							autoclose: true,
							todayHighlight: true,";
							if (! empty($conf->dol_use_jmobile))
							{
								$retstring.="
								beforeShow: function (input, datePicker) {
									input.disabled = true;
								},
								onClose: function (dateText, datePicker) {
									this.disabled = false;
								},
								";
							}
							// Note: We don't need monthNames, monthNamesShort, dayNames, dayNamesShort, dayNamesMin, they are set globally on datepicker component in lib_head.js.php
							if (empty($conf->global->MAIN_POPUP_CALENDAR_ON_FOCUS))
							{
							$retstring.="
								showOn: 'button',
								buttonImage: '".DOL_URL_ROOT."/theme/".$conf->theme."/img/object_calendarday.png',
								buttonImageOnly: true";
							}
							$retstring.="
							}) });";
						$retstring.="</script>";
					}

					// Zone de saisie manuelle de la date
					$retstring.='<div class="nowrap inline-block">';
					$retstring.='<input id="'.$prefix.'" name="'.$prefix.'" type="text" class="maxwidth75" maxlength="11" value="'.$formated_date.'"';
					$retstring.=($disabled?' disabled':'');
					$retstring.=' onChange="dpChangeDay(\''.$prefix.'\',\''.$langs->trans("FormatDateShortJavaInput").'\'); "';  // FormatDateShortInput for dol_print_date / FormatDateShortJavaInput that is same for javascript
					$retstring.='>';

					// Icone calendrier
					if (! $disabled)
					{
						/* Not required. Managed by option buttonImage of jquery
                		$retstring.=img_object($langs->trans("SelectDate"),'calendarday','id="'.$prefix.'id" class="datecallink"');
                		$retstring.="<script type='text/javascript'>";
                		$retstring.="jQuery(document).ready(function() {";
                		$retstring.='	jQuery("#'.$prefix.'id").click(function() {';
                		$retstring.="    	jQuery('#".$prefix."').focus();";
                		$retstring.='    });';
                		$retstring.='});';
                		$retstring.="</script>";*/
					}
					else
					{
						$retstring.='<button id="'.$prefix.'Button" type="button" class="dpInvisibleButtons">'.img_object($langs->trans("Disabled"),'calendarday','class="datecallink"').'</button>';
					}

					$retstring.='</div>';
					$retstring.='<input type="hidden" id="'.$prefix.'day"   name="'.$prefix.'day"   value="'.$sday.'">'."\n";
					$retstring.='<input type="hidden" id="'.$prefix.'month" name="'.$prefix.'month" value="'.$smonth.'">'."\n";
					$retstring.='<input type="hidden" id="'.$prefix.'year"  name="'.$prefix.'year"  value="'.$syear.'">'."\n";
				}
				else
				{
					$retstring.="Bad value of MAIN_POPUP_CALENDAR";
				}
			}
			// Show date with combo selects
			else
			{
				//$retstring.='<div class="inline-block">';
				// Day
				$retstring.='<select'.($disabled?' disabled':'').' class="flat valignmiddle maxwidth50imp" id="'.$prefix.'day" name="'.$prefix.'day">';

				if ($emptydate || $set_time == -1)
				{
					$retstring.='<option value="0" selected>&nbsp;</option>';
				}

				for ($day = 1 ; $day <= 31; $day++)
				{
					$retstring.='<option value="'.$day.'"'.($day == $sday ? ' selected':'').'>'.$day.'</option>';
				}

				$retstring.="</select>";

				$retstring.='<select'.($disabled?' disabled':'').' class="flat valignmiddle maxwidth75imp" id="'.$prefix.'month" name="'.$prefix.'month">';
				if ($emptydate || $set_time == -1)
				{
					$retstring.='<option value="0" selected>&nbsp;</option>';
				}

				// Month
				for ($month = 1 ; $month <= 12 ; $month++)
				{
					$retstring.='<option value="'.$month.'"'.($month == $smonth?' selected':'').'>';
					$retstring.=dol_print_date(mktime(12,0,0,$month,1,2000),"%b");
					$retstring.="</option>";
				}
				$retstring.="</select>";

				// Year
				if ($emptydate || $set_time == -1)
				{
					$retstring.='<input'.($disabled?' disabled':'').' placeholder="'.dol_escape_htmltag($langs->trans("Year")).'" class="flat maxwidth50imp valignmiddle" type="number" min="0" max="3000" maxlength="4" id="'.$prefix.'year" name="'.$prefix.'year" value="'.$syear.'">';
				}
				else
				{
					$retstring.='<select'.($disabled?' disabled':'').' class="flat valignmiddle maxwidth75imp" id="'.$prefix.'year" name="'.$prefix.'year">';

					for ($year = $syear - 10; $year < $syear + 10 ; $year++)
					{
						$retstring.='<option value="'.$year.'"'.($year == $syear ? ' selected':'').'>'.$year.'</option>';
					}
					$retstring.="</select>\n";
				}
				//$retstring.='</div>';
			}
		}

		if ($d && $h) $retstring.=($h==2?'<br>':' ');

		if ($h)
		{
			// Show hour
			$retstring.='<select'.($disabled?' disabled':'').' class="flat valignmiddle maxwidth50 '.($fullday?$fullday.'hour':'').'" id="'.$prefix.'hour" name="'.$prefix.'hour">';
			if ($emptyhours) $retstring.='<option value="-1">&nbsp;</option>';
			for ($hour = 0; $hour < 24; $hour++)
			{
				if (strlen($hour) < 2) $hour = "0" . $hour;
				$retstring.='<option value="'.$hour.'"'.(($hour == $shour)?' selected':'').'>'.$hour.(empty($conf->dol_optimize_smallscreen)?'':'H').'</option>';
			}
			$retstring.='</select>';
			if ($m && empty($conf->dol_optimize_smallscreen)) $retstring.=":";
		}

		if ($m)
		{
			// Show minutes
			$retstring.='<select'.($disabled?' disabled':'').' class="flat valignmiddle maxwidth50 '.($fullday?$fullday.'min':'').'" id="'.$prefix.'min" name="'.$prefix.'min">';
			if ($emptyhours) $retstring.='<option value="-1">&nbsp;</option>';
			for ($min = 0; $min < 60 ; $min++)
			{
				if (strlen($min) < 2) $min = "0" . $min;
				$retstring.='<option value="'.$min.'"'.(($min == $smin)?' selected':'').'>'.$min.(empty($conf->dol_optimize_smallscreen)?'':'').'</option>';
			}
			$retstring.='</select>';

			$retstring.='<input type="hidden" name="'.$prefix.'sec" value="'.$ssec.'">';
		}

		// Add a "Now" link
		if ($conf->use_javascript_ajax && $addnowlink)
		{
			// Script which will be inserted in the onClick of the "Now" link
			$reset_scripts = "";

			// Generate the date part, depending on the use or not of the javascript calendar
			$reset_scripts .= 'jQuery(\'#'.$prefix.'\').val(\''.dol_print_date(dol_now(),'day').'\');';
			$reset_scripts .= 'jQuery(\'#'.$prefix.'day\').val(\''.dol_print_date(dol_now(),'%d').'\');';
			$reset_scripts .= 'jQuery(\'#'.$prefix.'month\').val(\''.dol_print_date(dol_now(),'%m').'\');';
			$reset_scripts .= 'jQuery(\'#'.$prefix.'year\').val(\''.dol_print_date(dol_now(),'%Y').'\');';
			/*if ($usecalendar == "eldy")
            {
                $base=DOL_URL_ROOT.'/core/';
                $reset_scripts .= 'resetDP(\''.$base.'\',\''.$prefix.'\',\''.$langs->trans("FormatDateShortJavaInput").'\',\''.$langs->defaultlang.'\');';
            }
            else
            {
                $reset_scripts .= 'this.form.elements[\''.$prefix.'day\'].value=formatDate(new Date(), \'d\'); ';
                $reset_scripts .= 'this.form.elements[\''.$prefix.'month\'].value=formatDate(new Date(), \'M\'); ';
                $reset_scripts .= 'this.form.elements[\''.$prefix.'year\'].value=formatDate(new Date(), \'yyyy\'); ';
            }*/
			// Update the hour part
			if ($h)
			{
				if ($fullday) $reset_scripts .= " if (jQuery('#fullday:checked').val() == null) {";
				//$reset_scripts .= 'this.form.elements[\''.$prefix.'hour\'].value=formatDate(new Date(), \'HH\'); ';
				$reset_scripts .= 'jQuery(\'#'.$prefix.'hour\').val(\''.dol_print_date(dol_now(),'%H').'\');';
				if ($fullday) $reset_scripts .= ' } ';
			}
			// Update the minute part
			if ($m)
			{
				if ($fullday) $reset_scripts .= " if (jQuery('#fullday:checked').val() == null) {";
				//$reset_scripts .= 'this.form.elements[\''.$prefix.'min\'].value=formatDate(new Date(), \'mm\'); ';
				$reset_scripts .= 'jQuery(\'#'.$prefix.'min\').val(\''.dol_print_date(dol_now(),'%M').'\');';
				if ($fullday) $reset_scripts .= ' } ';
			}
			// If reset_scripts is not empty, print the link with the reset_scripts in the onClick
			if ($reset_scripts && empty($conf->dol_optimize_smallscreen))
			{
				$retstring.=' <button class="dpInvisibleButtons datenowlink" id="'.$prefix.'ButtonNow" type="button" name="_useless" value="now" onClick="'.$reset_scripts.'">';
				$retstring.=$langs->trans("Now");
				$retstring.='</button> ';
			}
		}

		// Add a "Plus one hour" link
		if ($conf->use_javascript_ajax && $addplusone)
		{
			// Script which will be inserted in the onClick of the "Add plusone" link
			$reset_scripts = "";

			// Generate the date part, depending on the use or not of the javascript calendar
			$reset_scripts .= 'jQuery(\'#'.$prefix.'\').val(\''.dol_print_date(dol_now(),'day').'\');';
			$reset_scripts .= 'jQuery(\'#'.$prefix.'day\').val(\''.dol_print_date(dol_now(),'%d').'\');';
			$reset_scripts .= 'jQuery(\'#'.$prefix.'month\').val(\''.dol_print_date(dol_now(),'%m').'\');';
			$reset_scripts .= 'jQuery(\'#'.$prefix.'year\').val(\''.dol_print_date(dol_now(),'%Y').'\');';
			// Update the hour part
			if ($h)
			{
				if ($fullday) $reset_scripts .= " if (jQuery('#fullday:checked').val() == null) {";
				$reset_scripts .= 'jQuery(\'#'.$prefix.'hour\').val(\''.dol_print_date(dol_now(),'%H').'\');';
				if ($fullday) $reset_scripts .= ' } ';
			}
			// Update the minute part
			if ($m)
			{
				if ($fullday) $reset_scripts .= " if (jQuery('#fullday:checked').val() == null) {";
				$reset_scripts .= 'jQuery(\'#'.$prefix.'min\').val(\''.dol_print_date(dol_now(),'%M').'\');';
				if ($fullday) $reset_scripts .= ' } ';
			}
			// If reset_scripts is not empty, print the link with the reset_scripts in the onClick
			if ($reset_scripts && empty($conf->dol_optimize_smallscreen))
			{
				$retstring.=' <button class="dpInvisibleButtons datenowlink" id="'.$prefix.'ButtonPlusOne" type="button" name="_useless2" value="plusone" onClick="'.$reset_scripts.'">';
				$retstring.=$langs->trans("DateStartPlusOne");
				$retstring.='</button> ';
			}
		}

		// Add a "Plus one hour" link
		if ($conf->use_javascript_ajax && $adddateof)
		{
			$tmparray=dol_getdate($adddateof);
			$retstring.=' - <button class="dpInvisibleButtons datenowlink" id="dateofinvoice" type="button" name="_dateofinvoice" value="now" onclick="jQuery(\'#re\').val(\''.dol_print_date($adddateof,'day').'\');jQuery(\'#reday\').val(\''.$tmparray['mday'].'\');jQuery(\'#remonth\').val(\''.$tmparray['mon'].'\');jQuery(\'#reyear\').val(\''.$tmparray['year'].'\');">'.$langs->trans("DateInvoice").'</a>';
		}

		return $retstring;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Function to show a form to select a duration on a page
	 *
	 *	@param	string	$prefix   		Prefix for input fields
	 *	@param  int	$iSecond  		    Default preselected duration (number of seconds or '')
	 * 	@param	int	$disabled           Disable the combo box
	 * 	@param	string	$typehour		If 'select' then input hour and input min is a combo,
	 *						            if 'text' input hour is in text and input min is a text,
	 *						            if 'textselect' input hour is in text and input min is a combo
	 *  @param	integer	$minunderhours	If 1, show minutes selection under the hours
	 * 	@param	int	$nooutput		    Do not output html string but return it
	 *  @return	string|void
	 */
	function select_duration($prefix, $iSecond='', $disabled=0, $typehour='select', $minunderhours=0, $nooutput=0)
	{
        // phpcs:enable
		global $langs;

		$retstring='';

		$hourSelected=0; $minSelected=0;

		// Hours
		if ($iSecond != '')
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

			$hourSelected = convertSecondToTime($iSecond,'allhour');
			$minSelected = convertSecondToTime($iSecond,'min');
		}

		if ($typehour=='select' )
		{
			$retstring.='<select class="flat" name="'.$prefix.'hour"'.($disabled?' disabled':'').'>';
			for ($hour = 0; $hour < 25; $hour++)	// For a duration, we allow 24 hours
			{
				$retstring.='<option value="'.$hour.'"';
				if ($hourSelected == $hour)
				{
					$retstring.=" selected";
				}
				$retstring.=">".$hour."</option>";
			}
			$retstring.="</select>";
		}
		elseif ($typehour=='text' || $typehour=='textselect')
		{
			$retstring.='<input placeholder="'.$langs->trans('HourShort').'" type="number" min="0" size="1" name="'.$prefix.'hour"'.($disabled?' disabled':'').' class="flat maxwidth50 inputhour" value="'.(($hourSelected != '')?((int) $hourSelected):'').'">';
		}
		else return 'BadValueForParameterTypeHour';

		if ($typehour!='text') $retstring.=' '.$langs->trans('HourShort');
		else $retstring.='<span class="hideonsmartphone">:</span>';

		// Minutes
		if ($minunderhours) $retstring.='<br>';
		else $retstring.='<span class="hideonsmartphone">&nbsp;</span>';

		if ($typehour=='select' || $typehour=='textselect')
		{
			$retstring.='<select class="flat" name="'.$prefix.'min"'.($disabled?' disabled':'').'>';
			for ($min = 0; $min <= 55; $min=$min+5)
			{
				$retstring.='<option value="'.$min.'"';
				if ($minSelected == $min) $retstring.=' selected';
				$retstring.='>'.$min.'</option>';
			}
			$retstring.="</select>";
		}
		elseif ($typehour=='text' )
		{
			$retstring.='<input placeholder="'.$langs->trans('MinuteShort').'" type="number" min="0" size="1" name="'.$prefix.'min"'.($disabled?' disabled':'').' class="flat maxwidth50 inputminute" value="'.(($minSelected != '')?((int) $minSelected):'').'">';
		}

		if ($typehour!='text') $retstring.=' '.$langs->trans('MinuteShort');

		//$retstring.="&nbsp;";

		if (! empty($nooutput)) return $retstring;

		print $retstring;
		return;
	}


	/**
	 * Generic method to select a component from a combo list.
	 * This is the generic method that will replace all specific existing methods.
	 *
	 * @param 	string			$objectdesc			Objectclassname:Objectclasspath
	 * @param	string			$htmlname			Name of HTML select component
	 * @param	int				$preselectedvalue	Preselected value (ID of element)
	 * @param	string			$showempty			''=empty values not allowed, 'string'=value show if we allow empty values (for example 'All', ...)
	 * @param	string			$searchkey			Search criteria
	 * @param	string			$placeholder		Place holder
	 * @param	string			$morecss			More CSS
	 * @param	string			$moreparams			More params provided to ajax call
	 * @param	int				$forcecombo			Force to load all values and output a standard combobox (with no beautification)
	 * @return	string								Return HTML string
	 * @see selectForFormsList select_thirdparty
	 */
	function selectForForms($objectdesc, $htmlname, $preselectedvalue, $showempty='', $searchkey='', $placeholder='', $morecss='', $moreparams='', $forcecombo=0)
	{
		global $conf, $user;

		$objecttmp = null;

		$InfoFieldList = explode(":", $objectdesc);
		$classname=$InfoFieldList[0];
		$classpath=$InfoFieldList[1];
		if (! empty($classpath))
		{
			dol_include_once($classpath);
			if ($classname && class_exists($classname))
			{
				$objecttmp = new $classname($this->db);
			}
		}
		if (! is_object($objecttmp))
		{
			dol_syslog('Error bad setup of type for field '.$InfoFieldList, LOG_WARNING);
			return 'Error bad setup of type for field '.join(',', $InfoFieldList);
		}

		$prefixforautocompletemode=$objecttmp->element;
		if ($prefixforautocompletemode == 'societe') $prefixforautocompletemode='company';
		$confkeyforautocompletemode=strtoupper($prefixforautocompletemode).'_USE_SEARCH_TO_SELECT';	// For example COMPANY_USE_SEARCH_TO_SELECT

		dol_syslog(get_class($this)."::selectForForms", LOG_DEBUG);

		$out='';
		if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->$confkeyforautocompletemode) && ! $forcecombo)
		{
			$objectdesc=$classname.':'.$classpath;
			$urlforajaxcall = DOL_URL_ROOT.'/core/ajax/selectobject.php';
			//if ($objecttmp->element == 'societe') $urlforajaxcall = DOL_URL_ROOT.'/societe/ajax/company.php';

			// No immediate load of all database
			$urloption='htmlname='.$htmlname.'&outjson=1&objectdesc='.$objectdesc.($moreparams?$moreparams:'');
			// Activate the auto complete using ajax call.
			$out.=  ajax_autocompleter($preselectedvalue, $htmlname, $urlforajaxcall, $urloption, $conf->global->$confkeyforautocompletemode, 0, array());
			$out.= '<style type="text/css">.ui-autocomplete { z-index: 250; }</style>';
			if ($placeholder) $placeholder=' placeholder="'.$placeholder.'"';
			$out.= '<input type="text" class="'.$morecss.'" name="search_'.$htmlname.'" id="search_'.$htmlname.'" value="'.$preselectedvalue.'"'.$placeholder.' />';
		}
		else
		{
			// Immediate load of all database
			$out.=$this->selectForFormsList($objecttmp, $htmlname, $preselectedvalue, $showempty, $searchkey, $placeholder, $morecss, $moreparams, $forcecombo);
		}

		return $out;
	}

	/**
	 * Output html form to select an object.
	 * Note, this function is called by selectForForms or by ajax selectobject.php
	 *
	 * @param 	Object			$objecttmp			Object
	 * @param	string			$htmlname			Name of HTML select component
	 * @param	int				$preselectedvalue	Preselected value (ID of element)
	 * @param	string			$showempty			''=empty values not allowed, 'string'=value show if we allow empty values (for example 'All', ...)
	 * @param	string			$searchkey			Search value
	 * @param	string			$placeholder		Place holder
	 * @param	string			$morecss			More CSS
	 * @param	string			$moreparams			More params provided to ajax call
	 * @param	int				$forcecombo			Force to load all values and output a standard combobox (with no beautification)
	 * @param	int				$outputmode			0=HTML select string, 1=Array
	 * @return	string								Return HTML string
	 * @see selectForForms
	 */
	function selectForFormsList($objecttmp, $htmlname, $preselectedvalue, $showempty='', $searchkey='', $placeholder='', $morecss='', $moreparams='', $forcecombo=0, $outputmode=0)
	{
		global $conf, $langs, $user;

		$prefixforautocompletemode=$objecttmp->element;
		if ($prefixforautocompletemode == 'societe') $prefixforautocompletemode='company';
		$confkeyforautocompletemode=strtoupper($prefixforautocompletemode).'_USE_SEARCH_TO_SELECT';	// For example COMPANY_USE_SEARCH_TO_SELECT

		if (((float) DOL_VERSION) < 10 && $objecttmp->element == 'facture') $fieldstoshow = 't.facnumber';
		else $fieldstoshow='t.ref';
		if (! empty($objecttmp->fields))	// For object that declare it, it is better to use declared fields ( like societe, contact, ...)
		{
			$tmpfieldstoshow='';
			foreach($objecttmp->fields as $key => $val)
			{
				if ($val['showoncombobox']) $tmpfieldstoshow.=($tmpfieldstoshow?',':'').'t.'.$key;
			}
			if ($tmpfieldstoshow) $fieldstoshow = $tmpfieldstoshow;
		}

		$out='';
		$outarray=array();

		$num=0;

		// Search data
		$sql = "SELECT t.rowid, ".$fieldstoshow." FROM ".MAIN_DB_PREFIX .$objecttmp->table_element." as t";
		if ($objecttmp->ismultientitymanaged == 2)
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE 1=1";
		if(! empty($objecttmp->ismultientitymanaged)) $sql.= " AND t.entity IN (".getEntity($objecttmp->table_element).")";
		if ($objecttmp->ismultientitymanaged == 1 && ! empty($user->societe_id))
		{
			if ($objecttmp->element == 'societe') $sql.= " AND t.rowid = ".$user->societe_id;
				else $sql.= " AND t.fk_soc = ".$user->societe_id;
		}
		if ($searchkey != '') $sql.=natural_search(explode(',',$fieldstoshow), $searchkey);
		if ($objecttmp->ismultientitymanaged == 2)
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND t.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
		$sql.=$this->db->order($fieldstoshow,"ASC");
		//$sql.=$this->db->plimit($limit, 0);

		// Build output string
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if (! $forcecombo)
			{
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname, null, $conf->global->$confkeyforautocompletemode);
			}

			// Construct $out and $outarray
			$out.= '<select id="'.$htmlname.'" class="flat'.($morecss?' '.$morecss:'').'"'.($moreparams?' '.$moreparams:'').' name="'.$htmlname.'">'."\n";

			// Warning: Do not use textifempty = ' ' or '&nbsp;' here, or search on key will search on ' key'. Seems it is no more true with selec2 v4
			$textifempty='&nbsp;';

			//if (! empty($conf->use_javascript_ajax) || $forcecombo) $textifempty='';
			if (! empty($conf->global->$confkeyforautocompletemode))
			{
				if ($showempty && ! is_numeric($showempty)) $textifempty=$langs->trans($showempty);
				else $textifempty.=$langs->trans("All");
			}
			if ($showempty) $out.= '<option value="-1">'.$textifempty.'</option>'."\n";

			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					$label='';
					$tmparray=explode(',', $fieldstoshow);
					foreach($tmparray as $key => $val)
					{
						$val = preg_replace('/t\./','',$val);
						$label .= (($label && $obj->$val)?' - ':'').$obj->$val;
					}
					if (empty($outputmode))
					{
						if ($preselectedvalue > 0 && $preselectedvalue == $obj->rowid)
						{
							$out.= '<option value="'.$obj->rowid.'" selected>'.$label.'</option>';
						}
						else
						{
							$out.= '<option value="'.$obj->rowid.'">'.$label.'</option>';
						}
					}
					else
					{
						array_push($outarray, array('key'=>$obj->rowid, 'value'=>$label, 'label'=>$label));
					}

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

		$this->result=array('nbofelement'=>$num);

		if ($outputmode) return $outarray;
		return $out;
	}


	/**
	 *	Return a HTML select string, built from an array of key+value.
	 *  Note: Do not apply langs->trans function on returned content, content may be entity encoded twice.
	 *
	 *	@param	string			$htmlname			Name of html select area. Must start with "multi" if this is a multiselect
	 *	@param	array			$array				Array (key => value)
	 *	@param	string|string[]	$id					Preselected key or preselected keys for multiselect
	 *	@param	int|string		$show_empty			0 no empty value allowed, 1 or string to add an empty value into list (key is -1 and value is '' or '&nbsp;' if 1, key is -1 and value is text if string), <0 to add an empty value with key that is this value.
	 *	@param	int				$key_in_label		1 to show key into label with format "[key] value"
	 *	@param	int				$value_as_key		1 to use value as key
	 *	@param  string			$moreparam			Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
	 *	@param  int				$translate			1=Translate and encode value
	 * 	@param	int				$maxlen				Length maximum for labels
	 * 	@param	int				$disabled			Html select box is disabled
	 *  @param	string			$sort				'ASC' or 'DESC' = Sort on label, '' or 'NONE' or 'POS' = Do not sort, we keep original order
	 *  @param	string			$morecss			Add more class to css styles
	 *  @param	int				$addjscombo			Add js combo
	 *  @param  string          $moreparamonempty	Add more param on the empty option line. Not used if show_empty not set
	 *  @param  int             $disablebademail	Check if an email is found into value and if not disable and colorize entry
	 *  @param  int             $nohtmlescape		No html escaping.
	 * 	@return	string								HTML select string.
	 *  @see multiselectarray, selectArrayAjax, selectArrayFilter
	 */
	static function selectarray($htmlname, $array, $id='', $show_empty=0, $key_in_label=0, $value_as_key=0, $moreparam='', $translate=0, $maxlen=0, $disabled=0, $sort='', $morecss='', $addjscombo=0, $moreparamonempty='',$disablebademail=0, $nohtmlescape=0)
	{
		global $conf, $langs;

		// Do we want a multiselect ?
		//$jsbeautify = 0;
		//if (preg_match('/^multi/',$htmlname)) $jsbeautify = 1;
		$jsbeautify = 1;

		if ($value_as_key) $array=array_combine($array, $array);

		$out='';

		// Add code for jquery to use multiselect
		if ($addjscombo && $jsbeautify)
		{
			$minLengthToAutocomplete=0;
			$tmpplugin=empty($conf->global->MAIN_USE_JQUERY_MULTISELECT)?(constant('REQUIRE_JQUERY_MULTISELECT')?constant('REQUIRE_JQUERY_MULTISELECT'):'select2'):$conf->global->MAIN_USE_JQUERY_MULTISELECT;

			// Enhance with select2
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			$out .= ajax_combobox($htmlname);
		}

		$out.='<select id="'.preg_replace('/^\./','',$htmlname).'" '.($disabled?'disabled ':'').'class="flat '.(preg_replace('/^\./','',$htmlname)).($morecss?' '.$morecss:'').'"';
		$out.=' name="'.preg_replace('/^\./','',$htmlname).'" '.($moreparam?$moreparam:'');
		$out.='>';

		if ($show_empty)
		{
			$textforempty=' ';
			if (! empty($conf->use_javascript_ajax)) $textforempty='&nbsp;';	// If we use ajaxcombo, we need &nbsp; here to avoid to have an empty element that is too small.
			if (! is_numeric($show_empty)) $textforempty=$show_empty;
			$out.='<option class="optiongrey" '.($moreparamonempty?$moreparamonempty.' ':'').'value="'.($show_empty < 0 ? $show_empty : -1).'"'.($id == $show_empty ?' selected':'').'>'.$textforempty.'</option>'."\n";
		}

		if (is_array($array))
		{
			// Translate
			if ($translate)
			{
				foreach($array as $key => $value)
				{
					$array[$key]=$langs->trans($value);
				}
			}

			// Sort
			if ($sort == 'ASC') asort($array);
			elseif ($sort == 'DESC') arsort($array);

			foreach($array as $key => $value)
			{
				$disabled=''; $style='';
				if (! empty($disablebademail))
				{
					if (! preg_match('/&lt;.+@.+&gt;/', $value))
					{
						//$value=preg_replace('/'.preg_quote($a,'/').'/', $b, $value);
						$disabled=' disabled';
						$style=' class="warning"';
					}
				}

				if ($key_in_label)
				{
					if (empty($nohtmlescape)) $selectOptionValue = dol_escape_htmltag($key.' - '.($maxlen?dol_trunc($value,$maxlen):$value));
					else $selectOptionValue = $key.' - '.($maxlen?dol_trunc($value,$maxlen):$value);
				}
				else
				{
					if (empty($nohtmlescape)) $selectOptionValue = dol_escape_htmltag($maxlen?dol_trunc($value,$maxlen):$value);
					else $selectOptionValue = $maxlen?dol_trunc($value,$maxlen):$value;
					if ($value == '' || $value == '-') $selectOptionValue='&nbsp;';
				}

				$out.='<option value="'.$key.'"';
				$out.=$style.$disabled;
				if ($id != '' && $id == $key && ! $disabled) $out.=' selected';		// To preselect a value
				if ($nohtmlescape) $out.=' data-html="'.dol_escape_htmltag($selectOptionValue).'"';
				$out.='>';
				//var_dump($selectOptionValue);
				$out.=$selectOptionValue;
				$out.="</option>\n";
			}
		}

		$out.="</select>";
		return $out;
	}


	/**
	 *	Return a HTML select string, built from an array of key+value, but content returned into select come from an Ajax call of an URL.
	 *  Note: Do not apply langs->trans function on returned content of Ajax service, content may be entity encoded twice.
	 *
	 *	@param	string	$htmlname       		Name of html select area
	 *	@param	string	$url					Url. Must return a json_encode of array(key=>array('text'=>'A text', 'url'=>'An url'), ...)
	 *	@param	string	$id             		Preselected key
	 *	@param  string	$moreparam      		Add more parameters onto the select tag
	 *	@param  string	$moreparamtourl 		Add more parameters onto the Ajax called URL
	 * 	@param	int		$disabled				Html select box is disabled
	 *  @param	int		$minimumInputLength		Minimum Input Length
	 *  @param	string	$morecss				Add more class to css styles
	 *  @param  int     $callurlonselect        If set to 1, some code is added so an url return by the ajax is called when value is selected.
	 *  @param  string  $placeholder            String to use as placeholder
	 *  @param  integer $acceptdelayedhtml      1 = caller is requesting to have html js content not returned but saved into global $delayedhtmlcontent (so caller can show it at end of page to avoid flash FOUC effect)
	 * 	@return	string   						HTML select string
	 *  @see selectArrayFilter, ajax_combobox in ajax.lib.php
	 */
	static function selectArrayAjax($htmlname, $url, $id='', $moreparam='', $moreparamtourl='', $disabled=0, $minimumInputLength=1, $morecss='', $callurlonselect=0, $placeholder='', $acceptdelayedhtml=0)
	{
		global $conf, $langs;
		global $delayedhtmlcontent;

		// TODO Use an internal dolibarr component instead of select2
		if (empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) && ! defined('REQUIRE_JQUERY_MULTISELECT')) return '';

		$out='<select type="text" class="'.$htmlname.($morecss?' '.$morecss:'').'" '.($moreparam?$moreparam.' ':'').'name="'.$htmlname.'"></select>';

		$tmpplugin='select2';
		$outdelayed="\n".'<!-- JS CODE TO ENABLE '.$tmpplugin.' for id '.$htmlname.' -->
	    	<script type="text/javascript">
	    	$(document).ready(function () {

    	        '.($callurlonselect ? 'var saveRemoteData = [];':'').'

                $(".'.$htmlname.'").select2({
			    	ajax: {
				    	dir: "ltr",
				    	url: "'.$url.'",
				    	dataType: \'json\',
				    	delay: 250,
				    	data: function (params) {
				    		return {
						    	q: params.term, 	// search term
				    			page: params.page
				    		};
			    		},
			    		processResults: function (data) {
			    			// parse the results into the format expected by Select2.
			    			// since we are using custom formatting functions we do not need to alter the remote JSON data
			    			//console.log(data);
							saveRemoteData = data;
				    	    /* format json result for select2 */
				    	    result = []
				    	    $.each( data, function( key, value ) {
				    	       result.push({id: key, text: value.text});
                            });
			    			//return {results:[{id:\'none\', text:\'aa\'}, {id:\'rrr\', text:\'Red\'},{id:\'bbb\', text:\'Search a into projects\'}], more:false}
			    			//console.log(result);
			    			return {results: result, more: false}
			    		},
			    		cache: true
			    	},
	 				language: select2arrayoflanguage,
					containerCssClass: \':all:\',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
				    placeholder: "'.dol_escape_js($placeholder).'",
			    	escapeMarkup: function (markup) { return markup; }, 	// let our custom formatter work
			    	minimumInputLength: '.$minimumInputLength.',
			        formatResult: function(result, container, query, escapeMarkup) {
                        return escapeMarkup(result.text);
                    },
			    });

                '.($callurlonselect ? '
                /* Code to execute a GET when we select a value */
                $(".'.$htmlname.'").change(function() {
			    	var selected = $(".'.$htmlname.'").val();
                	console.log("We select in selectArrayAjax the entry "+selected)
			        $(".'.$htmlname.'").val("");  /* reset visible combo value */
    			    $.each( saveRemoteData, function( key, value ) {
    				        if (key == selected)
    			            {
    			                 console.log("selectArrayAjax - Do a redirect to "+value.url)
    			                 location.assign(value.url);
    			            }
                    });
    			});' : '' ) . '

    	   });
	       </script>';

		if ($acceptdelayedhtml)
		{
			$delayedhtmlcontent.=$outdelayed;
		}
		else
		{
			$out.=$outdelayed;
		}
		return $out;
	}

	/**
	 *	Return a HTML select string, built from an array of key+value, but content returned into select is defined into $array parameter.
	 *  Note: Do not apply langs->trans function on returned content of Ajax service, content may be entity encoded twice.
	 *
	 *	@param	string	$htmlname       		Name of html select area
	 *	@param	string	$array					Array (key=>array('text'=>'A text', 'url'=>'An url'), ...)
	 *	@param	string	$id             		Preselected key
	 *	@param  string	$moreparam      		Add more parameters onto the select tag
	 *	@param	int		$disableFiltering		If set to 1, results are not filtered with searched string
	 * 	@param	int		$disabled				Html select box is disabled
	 *  @param	int		$minimumInputLength		Minimum Input Length
	 *  @param	string	$morecss				Add more class to css styles
	 *  @param  int     $callurlonselect        If set to 1, some code is added so an url return by the ajax is called when value is selected.
	 *  @param  string  $placeholder            String to use as placeholder
	 *  @param  integer $acceptdelayedhtml      1 = caller is requesting to have html js content not returned but saved into global $delayedhtmlcontent (so caller can show it at end of page to avoid flash FOUC effect)
	 * 	@return	string   						HTML select string
	 *  @see selectArrayAjax, ajax_combobox in ajax.lib.php
	 */
	static function selectArrayFilter($htmlname, $array, $id='', $moreparam='', $disableFiltering=0, $disabled=0, $minimumInputLength=1, $morecss='', $callurlonselect=0, $placeholder='', $acceptdelayedhtml=0)
	{
		global $conf, $langs;
		global $delayedhtmlcontent;

		// TODO Use an internal dolibarr component instead of select2
		if (empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) && ! defined('REQUIRE_JQUERY_MULTISELECT')) return '';

		$out='<select type="text" class="'.$htmlname.($morecss?' '.$morecss:'').'" '.($moreparam?$moreparam.' ':'').'name="'.$htmlname.'"><option></option></select>';

		$formattedarrayresult = array();

		foreach($array as $key => $value) {
			$o = new stdClass();
			$o->id = $key;
			$o->text = $value['text'];
			$o->url = $value['url'];
			$formattedarrayresult[] = $o;
		}

		$tmpplugin='select2';
		$outdelayed="\n".'<!-- JS CODE TO ENABLE '.$tmpplugin.' for id '.$htmlname.' -->
			<script type="text/javascript">
			$(document).ready(function () {
				var data = '.json_encode($formattedarrayresult).';

				'.($callurlonselect ? 'var saveRemoteData = '.json_encode($array).';':'').'

				$(".'.$htmlname.'").select2({
					data: data,
					language: select2arrayoflanguage,
					containerCssClass: \':all:\',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
					placeholder: "'.dol_escape_js($placeholder).'",
					escapeMarkup: function (markup) { return markup; }, 	// let our custom formatter work
					minimumInputLength: '.$minimumInputLength.',
					formatResult: function(result, container, query, escapeMarkup) {
						return escapeMarkup(result.text);
					},
					matcher: function (params, data) {

						if(! data.id) return null;';

		if($callurlonselect) {
			$outdelayed.='

						var urlBase = data.url;
						var separ = urlBase.indexOf("?") >= 0 ? "&" : "?";
						/* console.log("params.term="+params.term); */
						/* console.log("params.term encoded="+encodeURIComponent(params.term)); */
						saveRemoteData[data.id].url = urlBase + separ + "sall=" + encodeURIComponent(params.term);';
		}

		if(! $disableFiltering) {
			$outdelayed.='

						if(data.text.match(new RegExp(params.term))) {
							return data;
						}

						return null;';
		} else {
			$outdelayed.='

						return data;';
		}

		$outdelayed.='
					}
				});

				'.($callurlonselect ? '
				/* Code to execute a GET when we select a value */
				$(".'.$htmlname.'").change(function() {
					var selected = $(".'.$htmlname.'").val();
					console.log("We select "+selected)

					$(".'.$htmlname.'").val("");  /* reset visible combo value */
					$.each( saveRemoteData, function( key, value ) {
						if (key == selected)
						{
							console.log("selectArrayAjax - Do a redirect to "+value.url)
							location.assign(value.url);
						}
					});
				});' : '' ) . '

			});
			</script>';

		if ($acceptdelayedhtml)
		{
			$delayedhtmlcontent.=$outdelayed;
		}
		else
		{
			$out.=$outdelayed;
		}
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
	 *	@param  string	$morecss        Add more css style
	 *	@param  int		$translate		Translate and encode value
	 *  @param	int		$width			Force width of select box. May be used only when using jquery couch. Example: 250, 95%
	 *  @param	string	$moreattrib		Add more options on select component. Example: 'disabled'
	 *  @param	string	$elemtype		Type of element we show ('category', ...)
	 *  @param	string	$placeholder	String to use as placeholder
	 *  @param	int		$addjscombo		Add js combo
	 *	@return	string					HTML multiselect string
	 *  @see selectarray, selectArrayAjax, selectArrayFilter
	 */
	static function multiselectarray($htmlname, $array, $selected=array(), $key_in_label=0, $value_as_key=0, $morecss='', $translate=0, $width=0, $moreattrib='', $elemtype='', $placeholder='', $addjscombo=-1)
	{
		global $conf, $langs;

		$out = '';

		if ($addjscombo < 0) {
		    if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $addjscombo = 1;
		    else $addjscombo = 0;
		}

		// Add code for jquery to use multiselect
		if (! empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT'))
		{
			$out.="\n".'<!-- JS CODE TO ENABLE '.$tmpplugin.' for id '.$htmlname.' -->
						<script type="text/javascript">'."\n";
			if ($addjscombo == 1)
			{
				$tmpplugin=empty($conf->global->MAIN_USE_JQUERY_MULTISELECT)?constant('REQUIRE_JQUERY_MULTISELECT'):$conf->global->MAIN_USE_JQUERY_MULTISELECT;
				$out.=	'function formatResult(record) {'."\n";
				if ($elemtype == 'category')
				{
					$out.='	//return \'<span><img src="'.DOL_URL_ROOT.'/theme/eldy/img/object_category.png'.'"> <a href="'.DOL_URL_ROOT.'/categories/viewcat.php?type=0&id=\'+record.id+\'">\'+record.text+\'</a></span>\';
									  	return \'<span><img src="'.DOL_URL_ROOT.'/theme/eldy/img/object_category.png'.'"> \'+record.text+\'</span>\';';
				}
				else
				{
					$out.='return record.text;';
				}
				$out.=	'};'."\n";
				$out.=	'function formatSelection(record) {'."\n";
				if ($elemtype == 'category')
				{
					$out.='	//return \'<span><img src="'.DOL_URL_ROOT.'/theme/eldy/img/object_category.png'.'"> <a href="'.DOL_URL_ROOT.'/categories/viewcat.php?type=0&id=\'+record.id+\'">\'+record.text+\'</a></span>\';
									  	return \'<span><img src="'.DOL_URL_ROOT.'/theme/eldy/img/object_category.png'.'"> \'+record.text+\'</span>\';';
				}
				else
				{
					$out.='return record.text;';
				}
				$out.=	'};'."\n";
				$out.=	'$(document).ready(function () {
							$(\'#'.$htmlname.'\').'.$tmpplugin.'({
								dir: \'ltr\',
								// Specify format function for dropdown item
								formatResult: formatResult,
							 	templateResult: formatResult,		/* For 4.0 */
								// Specify format function for selected item
								formatSelection: formatSelection,
							 	templateResult: formatSelection		/* For 4.0 */
							});
						});'."\n";
			}
			elseif ($addjscombo == 2)
			{
				// Add other js lib
				// ...
				$out.= '$(document).ready(function () {
							$(\'#'.$htmlname.'\').multiSelect({
								containerHTML: \'<div class="multi-select-container">\',
								menuHTML: \'<div class="multi-select-menu">\',
								buttonHTML: \'<span class="multi-select-button '.$morecss.'">\',
								menuItemHTML: \'<label class="multi-select-menuitem">\',
								activeClass: \'multi-select-container--open\',
								noneText: \''.$placeholder.'\'
							});
						})';
			}
			$out.=	'</script>';
		}

		// Try also magic suggest

		$out .= '<select id="'.$htmlname.'" class="multiselect'.($morecss?' '.$morecss:'').'" multiple name="'.$htmlname.'[]"'.($moreattrib?' '.$moreattrib:'').($width?' style="width: '.(preg_match('/%/',$width)?$width:$width.'px').'"':'').'>'."\n";
		if (is_array($array) && ! empty($array))
		{
			if ($value_as_key) $array=array_combine($array, $array);

			if (! empty($array))
			{
				foreach ($array as $key => $value)
				{
					$out.= '<option value="'.$key.'"';
					if (is_array($selected) && ! empty($selected) && in_array($key, $selected) && ((string) $key != ''))
					{
						$out.= ' selected';
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
	 *	Show a multiselect dropbox from an array.
	 *
	 *	@param	string	$htmlname		Name of HTML field
	 *	@param	array	$array			Array with array of fields we could show. This array may be modified according to setup of user.
	 *  @param  string  $varpage        Id of context for page. Can be set by caller with $varpage=(empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage);
	 *	@return	string					HTML multiselect string
	 *  @see selectarray
	 */
	static function multiSelectArrayWithCheckbox($htmlname, &$array, $varpage)
	{
		global $conf,$langs,$user;

		if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) return '';

		$tmpvar="MAIN_SELECTEDFIELDS_".$varpage;
		if (! empty($user->conf->$tmpvar))
		{
			$tmparray=explode(',', $user->conf->$tmpvar);
			foreach($array as $key => $val)
			{
				//var_dump($key);
				//var_dump($tmparray);
				if (in_array($key, $tmparray)) $array[$key]['checked']=1;
				else $array[$key]['checked']=0;
			}
		}
		//var_dump($array);

		$lis='';
		$listcheckedstring='';

		foreach($array as $key => $val)
		{
		   /* var_dump($val);
            var_dump(array_key_exists('enabled', $val));
            var_dump(!$val['enabled']);*/
		   if (array_key_exists('enabled', $val) && isset($val['enabled']) && ! $val['enabled'])
		   {
			   unset($array[$key]);     // We don't want this field
			   continue;
		   }
		   if ($val['label'])
		   {
		   	$lis.='<li><input type="checkbox" id="checkbox'.$key.'" value="'.$key.'"'.(empty($val['checked'])?'':' checked="checked"').'/><label for="checkbox'.$key.'">'.dol_escape_htmltag($langs->trans($val['label'])).'</label></li>';
			   $listcheckedstring.=(empty($val['checked'])?'':$key.',');
		   }
		}

		$out ='<!-- Component multiSelectArrayWithCheckbox '.$htmlname.' -->

        <dl class="dropdown">
            <dt>
            <a href="#">
              '.img_picto('','list').'
            </a>
            <input type="hidden" class="'.$htmlname.'" name="'.$htmlname.'" value="'.$listcheckedstring.'">
            </dt>
            <dd class="dropdowndd">
                <div class="multiselectcheckbox'.$htmlname.'">
                    <ul class="ul'.$htmlname.'">
                    '.$lis.'
                    </ul>
                </div>
            </dd>
        </dl>

        <script type="text/javascript">
          jQuery(document).ready(function () {
              $(\'.multiselectcheckbox'.$htmlname.' input[type="checkbox"]\').on(\'click\', function () {
                  console.log("A new field was added/removed")
                  $("input:hidden[name=formfilteraction]").val(\'listafterchangingselectedfields\')
                  var title = $(this).val() + ",";
                  if ($(this).is(\':checked\')) {
                      $(\'.'.$htmlname.'\').val(title + $(\'.'.$htmlname.'\').val());
                  }
                  else {
                      $(\'.'.$htmlname.'\').val( $(\'.'.$htmlname.'\').val().replace(title, \'\') )
                  }
                  // Now, we submit page
                  $(this).parents(\'form:first\').submit();
              });
           });
        </script>

        ';
		return $out;
	}

	/**
	 * 	Render list of categories linked to object with id $id and type $type
	 *
	 * 	@param		int		$id				Id of object
	 * 	@param		string	$type			Type of category ('member', 'customer', 'supplier', 'product', 'contact'). Old mode (0, 1, 2, ...) is deprecated.
	 *  @param		int		$rendermode		0=Default, use multiselect. 1=Emulate multiselect (recommended)
	 * 	@return		string					String with categories
	 */
	function showCategories($id, $type, $rendermode=0)
	{
		global $db;

		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

		$cat = new Categorie($db);
		$categories = $cat->containing($id, $type);

		if ($rendermode == 1)
		{
			$toprint = array();
			foreach($categories as $c)
			{
				$ways = $c->print_all_ways();       // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formated text
				foreach($ways as $way)
				{
					$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"'.($c->color?' style="background: #'.$c->color.';"':' style="background: #aaa"').'>'.img_object('','category').' '.$way.'</li>';
				}
			}
			return '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
		}

		if ($rendermode == 0)
		{
			$cate_arbo = $this->select_all_categories($type, '', 'parent', 64, 0, 1);
			foreach($categories as $c) {
				$arrayselected[] = $c->id;
			}

			return $this->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, '', 0, '100%', 'disabled', 'category');
		}

		return 'ErrorBadValueForParameterRenderMode';	// Should not happened
	}


	/**
	 *  Show linked object block.
	 *
	 *  @param	CommonObject	$object		      Object we want to show links to
	 *  @param  string          $morehtmlright    More html to show on right of title
	 *  @param  array           $compatibleImportElementsList  Array of compatibles elements object for "import from" action
	 *  @return	int							      <0 if KO, >=0 if OK
	 */
	function showLinkedObjectBlock($object, $morehtmlright='',$compatibleImportElementsList=false)
	{
		global $conf,$langs,$hookmanager;
		global $bc;

		$object->fetchObjectLinked();

		// Bypass the default method
		$hookmanager->initHooks(array('commonobject'));
		$parameters=array(
			'morehtmlright' => $morehtmlright,
		    'compatibleImportElementsList' => &$compatibleImportElementsList,
		);
		$reshook=$hookmanager->executeHooks('showLinkedObjectBlock',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

		if (empty($reshook))
		{
			$nbofdifferenttypes = count($object->linkedObjects);

			print '<!-- showLinkedObjectBlock -->';
			print load_fiche_titre($langs->trans('RelatedObjects'), $morehtmlright, '', 0, 0, 'showlinkedobjectblock');


			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder allwidth" data-block="showLinkedObject" data-element="'.$object->element.'"  data-elementid="'.$object->id.'"   >';

			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Type").'</td>';
			print '<td>'.$langs->trans("Ref").'</td>';
			print '<td align="center"></td>';
			print '<td align="center">'.$langs->trans("Date").'</td>';
			print '<td align="right">'.$langs->trans("AmountHTShort").'</td>';
			print '<td align="right">'.$langs->trans("Status").'</td>';
			print '<td></td>';
			print '</tr>';

			$nboftypesoutput=0;

			foreach($object->linkedObjects as $objecttype => $objects)
			{
				$tplpath = $element = $subelement = $objecttype;

				// to display inport button on tpl
				$showImportButton=false;
				if(!empty($compatibleImportElementsList) && in_array($element,$compatibleImportElementsList)){
				    $showImportButton=true;
				}

				if ($objecttype != 'supplier_proposal' && preg_match('/^([^_]+)_([^_]+)/i',$objecttype,$regs))
				{
					$element = $regs[1];
					$subelement = $regs[2];
					$tplpath = $element.'/'.$subelement;
				}
				$tplname='linkedobjectblock';

				// To work with non standard path
				if ($objecttype == 'facture')          {
					$tplpath = 'compta/'.$element;
					if (empty($conf->facture->enabled)) continue;	// Do not show if module disabled
				}
				else if ($objecttype == 'facturerec')          {
					$tplpath = 'compta/facture';
					$tplname = 'linkedobjectblockForRec';
					if (empty($conf->facture->enabled)) continue;	// Do not show if module disabled
				}
				else if ($objecttype == 'propal')           {
					$tplpath = 'comm/'.$element;
					if (empty($conf->propal->enabled)) continue;	// Do not show if module disabled
				}
				else if ($objecttype == 'supplier_proposal')           {
					if (empty($conf->supplier_proposal->enabled)) continue;	// Do not show if module disabled
				}
				else if ($objecttype == 'shipping' || $objecttype == 'shipment') {
					$tplpath = 'expedition';
					if (empty($conf->expedition->enabled)) continue;	// Do not show if module disabled
				}
				else if ($objecttype == 'delivery')         {
					$tplpath = 'livraison';
					if (empty($conf->expedition->enabled)) continue;	// Do not show if module disabled
				}
				else if ($objecttype == 'invoice_supplier') {
					$tplpath = 'fourn/facture';
				}
				else if ($objecttype == 'order_supplier')   {
					$tplpath = 'fourn/commande';
				}
				else if ($objecttype == 'expensereport')   {
					$tplpath = 'expensereport';
				}
				else if ($objecttype == 'subscription')   {
					$tplpath = 'adherents';
				}

				global $linkedObjectBlock;
				$linkedObjectBlock = $objects;


				// Output template part (modules that overwrite templates must declare this into descriptor)
				$dirtpls=array_merge($conf->modules_parts['tpl'],array('/'.$tplpath.'/tpl'));
				foreach($dirtpls as $reldir)
				{
					if ($nboftypesoutput == ($nbofdifferenttypes - 1))    // No more type to show after
					{
						global $noMoreLinkedObjectBlockAfter;
						$noMoreLinkedObjectBlockAfter=1;
					}

					$res=@include dol_buildpath($reldir.'/'.$tplname.'.tpl.php');
					if ($res)
					{
						$nboftypesoutput++;
						break;
					}
				}
			}

			if (! $nboftypesoutput)
			{
				print '<tr><td class="impair opacitymedium" colspan="7">'.$langs->trans("None").'</td></tr>';
			}

			print '</table>';

			if(!empty($compatibleImportElementsList))
			{
			    $res=@include dol_buildpath('core/tpl/ajax/objectlinked_lineimport.tpl.php');
			}


			print '</div>';

			return $nbofdifferenttypes;
		}
	}

	/**
	 *  Show block with links to link to other objects.
	 *
	 *  @param	CommonObject	$object				Object we want to show links to
	 *  @param	array			$restrictlinksto	Restrict links to some elements, for exemple array('order') or array('supplier_order'). null or array() if no restriction.
	 *  @param	array			$excludelinksto		Do not show links of this type, for exemple array('order') or array('supplier_order'). null or array() if no exclusion.
	 *  @return	string								<0 if KO, >0 if OK
	 */
	function showLinkToObjectBlock($object, $restrictlinksto=array(), $excludelinksto=array())
	{
		global $conf, $langs, $hookmanager;
		global $bc;

		$linktoelem='';
		$linktoelemlist='';
		$listofidcompanytoscan='';

		if (! is_object($object->thirdparty)) $object->fetch_thirdparty();

		$possiblelinks=array();
		if (is_object($object->thirdparty) && ! empty($object->thirdparty->id) && $object->thirdparty->id > 0)
		{
			$listofidcompanytoscan=$object->thirdparty->id;
			if (($object->thirdparty->parent > 0) && ! empty($conf->global->THIRDPARTY_INCLUDE_PARENT_IN_LINKTO)) $listofidcompanytoscan.=','.$object->thirdparty->parent;
			if (($object->fk_project > 0) && ! empty($conf->global->THIRDPARTY_INCLUDE_PROJECT_THIRDPARY_IN_LINKTO))
			{
				include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				$tmpproject=new Project($this->db);
				$tmpproject->fetch($object->fk_project);
				if ($tmpproject->socid > 0 && ($tmpproject->socid != $object->thirdparty->id)) $listofidcompanytoscan.=','.$tmpproject->socid;
				unset($tmpproject);
			}

			$possiblelinks=array(
				'propal'=>array('enabled'=>$conf->propal->enabled, 'perms'=>1, 'label'=>'LinkToProposal', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_client, t.total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('propal').')'),
				'order'=>array('enabled'=>$conf->commande->enabled, 'perms'=>1, 'label'=>'LinkToOrder', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_client, t.total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('commande').')'),
				'invoice'=>array('enabled'=>$conf->facture->enabled, 'perms'=>1, 'label'=>'LinkToInvoice', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.facnumber as ref, t.ref_client, t.total as total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('facture').')'),
				'invoice_template'=>array('enabled'=>$conf->facture->enabled, 'perms'=>1, 'label'=>'LinkToTemplateInvoice', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.titre as ref, t.total as total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_rec as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('facture').')'),
				'contrat'=>array('enabled'=>$conf->contrat->enabled , 'perms'=>1, 'label'=>'LinkToContract', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_supplier, '' as total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('contract').')'),
				'fichinter'=>array('enabled'=>$conf->ficheinter->enabled, 'perms'=>1, 'label'=>'LinkToIntervention', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."fichinter as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('intervention').')'),
				'supplier_proposal'=>array('enabled'=>$conf->supplier_proposal->enabled , 'perms'=>1, 'label'=>'LinkToSupplierProposal', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, '' as ref_supplier, t.total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."supplier_proposal as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('supplier_proposal').')'),
				'order_supplier'=>array('enabled'=>$conf->supplier_order->enabled , 'perms'=>1, 'label'=>'LinkToSupplierOrder', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_supplier, t.total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande_fournisseur as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('commande_fournisseur').')'),
				'invoice_supplier'=>array('enabled'=>$conf->supplier_invoice->enabled , 'perms'=>1, 'label'=>'LinkToSupplierInvoice', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_supplier, t.total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('facture_fourn').')')
			);
		}

		global $action;

		// Can complete the possiblelink array
		$hookmanager->initHooks(array('commonobject'));
		$parameters=array('listofidcompanytoscan' => $listofidcompanytoscan);
		$reshook=$hookmanager->executeHooks('showLinkToObjectBlock',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

		if (empty($reshook))
		{
			if (is_array($hookmanager->resArray) && count($hookmanager->resArray))
			{
				$possiblelinks=array_merge($possiblelinks, $hookmanager->resArray);
			}
		}
		else if ($reshook > 0)
		{
			if (is_array($hookmanager->resArray) && count($hookmanager->resArray))
			{
				$possiblelinks=$hookmanager->resArray;
			}
		}

		foreach($possiblelinks as $key => $possiblelink)
		{
			$num = 0;

			if (empty($possiblelink['enabled'])) continue;

			if (! empty($possiblelink['perms']) && (empty($restrictlinksto) || in_array($key, $restrictlinksto)) && (empty($excludelinksto) || ! in_array($key, $excludelinksto)))
			{
				print '<div id="'.$key.'list"'.(empty($conf->use_javascript_ajax)?'':' style="display:none"').'>';
				$sql = $possiblelink['sql'];

				$resqllist = $this->db->query($sql);
				if ($resqllist)
				{
					$num = $this->db->num_rows($resqllist);
					$i = 0;

					print '<br>';
					print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formlinked'.$key.'">';
					print '<input type="hidden" name="action" value="addlink">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="id" value="'.$object->id.'">';
					print '<input type="hidden" name="addlink" value="'.$key.'">';
					print '<table class="noborder">';
					print '<tr class="liste_titre">';
					print '<td class="nowrap"></td>';
					print '<td align="center">' . $langs->trans("Ref") . '</td>';
					print '<td align="left">' . $langs->trans("RefCustomer") . '</td>';
					print '<td align="right">' . $langs->trans("AmountHTShort") . '</td>';
					print '<td align="left">' . $langs->trans("Company") . '</td>';
					print '</tr>';
					while ($i < $num)
					{
						$objp = $this->db->fetch_object($resqllist);

						print '<tr class="oddeven">';
						print '<td aling="left">';
						print '<input type="radio" name="idtolinkto" value=' . $objp->rowid . '>';
						print '</td>';
						print '<td align="center">' . $objp->ref . '</td>';
						print '<td>' . $objp->ref_client . '</td>';
						print '<td align="right">' . price($objp->total_ht) . '</td>';
						print '<td>' . $objp->name . '</td>';
						print '</tr>';
						$i++;
					}
					print '</table>';
					print '<div class="center"><input type="submit" class="button valignmiddle" value="' . $langs->trans('ToLink') . '">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" class="button" name="cancel" value="' . $langs->trans('Cancel') . '"></div>';

					print '</form>';
					$this->db->free($resqllist);
				} else {
					dol_print_error($this->db);
				}
				print '</div>';
				if ($num > 0)
				{
				}

				//$linktoelem.=($linktoelem?' &nbsp; ':'');
				if ($num > 0) $linktoelemlist.='<li><a href="#linkto'.$key.'" class="linkto dropdowncloseonclick" rel="'.$key.'">' . $langs->trans($possiblelink['label']) .' ('.$num.')</a></li>';
				//else $linktoelem.=$langs->trans($possiblelink['label']);
				else $linktoelemlist.='<li><span class="linktodisabled">' . $langs->trans($possiblelink['label']) . ' (0)</span></li>';
			}
		}

		if ($linktoelemlist)
		{
			$linktoelem='
    		<dl class="dropdown" id="linktoobjectname">
    		';
			if (! empty($conf->use_javascript_ajax)) $linktoelem.='<dt><a href="#linktoobjectname">'.$langs->trans("LinkTo").'...</a></dt>';
			$linktoelem.='<dd>
    		<div class="multiselectlinkto">
    		<ul class="ulselectedfields">'.$linktoelemlist.'
    		</ul>
    		</div>
    		</dd>
    		</dl>';
		}
		else
		{
			$linktoelem='';
		}

		if (! empty($conf->use_javascript_ajax))
		{
		  print '<!-- Add js to show linkto box -->
				<script type="text/javascript" language="javascript">
				jQuery(document).ready(function() {
					jQuery(".linkto").click(function() {
						console.log("We choose to show/hide link for rel="+jQuery(this).attr(\'rel\'));
					    jQuery("#"+jQuery(this).attr(\'rel\')+"list").toggle();
						jQuery(this).toggle();
					});
				});
				</script>
		  ';
		}

		return $linktoelem;
	}

	/**
	 *	Return an html string with a select combo box to choose yes or no
	 *
	 *	@param	string		$htmlname		Name of html select field
	 *	@param	string		$value			Pre-selected value
	 *	@param	int			$option			0 return yes/no, 1 return 1/0
	 *	@param	bool		$disabled		true or false
	 *  @param	int      	$useempty		1=Add empty line
	 *	@return	string						See option
	 */
	function selectyesno($htmlname, $value='', $option=0, $disabled=false, $useempty=0)
	{
		global $langs;

		$yes="yes"; $no="no";
		if ($option)
		{
			$yes="1";
			$no="0";
		}

		$disabled = ($disabled ? ' disabled' : '');

		$resultyesno = '<select class="flat width75" id="'.$htmlname.'" name="'.$htmlname.'"'.$disabled.'>'."\n";
		if ($useempty) $resultyesno .= '<option value="-1"'.(($value < 0)?' selected':'').'>&nbsp;</option>'."\n";
		if (("$value" == 'yes') || ($value == 1))
		{
			$resultyesno .= '<option value="'.$yes.'" selected>'.$langs->trans("Yes").'</option>'."\n";
			$resultyesno .= '<option value="'.$no.'">'.$langs->trans("No").'</option>'."\n";
		}
		else
	   {
	   		$selected=(($useempty && $value != '0' && $value != 'no')?'':' selected');
			$resultyesno .= '<option value="'.$yes.'">'.$langs->trans("Yes").'</option>'."\n";
			$resultyesno .= '<option value="'.$no.'"'.$selected.'>'.$langs->trans("No").'</option>'."\n";
		}
		$resultyesno .= '</select>'."\n";
		return $resultyesno;
	}



    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
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
        // phpcs:enable
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
					print '<option value="'.$obj->rowid.'" selected>';
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
	 *    Note: To complete search with a particular filter on select, you can set $object->next_prev_filter set to define SQL criterias.
	 *
	 *    @param	object	$object			Object to show.
	 *    @param	string	$paramid   		Name of parameter to use to name the id into the URL next/previous link.
	 *    @param	string	$morehtml  		More html content to output just before the nav bar.
	 *    @param	int		$shownav	  	Show Condition (navigation is shown if value is 1).
	 *    @param	string	$fieldid   		Name of field id into database to use for select next and previous (we make the select max and min on this field compared to $object->ref). Use 'none' to disable next/prev.
	 *    @param	string	$fieldref   	Name of field ref of object (object->ref) to show or 'none' to not show ref.
	 *    @param	string	$morehtmlref  	More html to show after ref.
	 *    @param	string	$moreparam  	More param to add in nav link url. Must start with '&...'.
	 *	  @param	int		$nodbprefix		Do not include DB prefix to forge table name.
	 *	  @param	string	$morehtmlleft	More html code to show before ref.
	 *	  @param	string	$morehtmlstatus	More html code to show under navigation arrows (status place).
	 *	  @param	string	$morehtmlright	More html code to show after ref.
	 * 	  @return	string    				Portion HTML with ref + navigation buttons
	 */
	function showrefnav($object,$paramid,$morehtml='',$shownav=1,$fieldid='rowid',$fieldref='ref',$morehtmlref='',$moreparam='',$nodbprefix=0,$morehtmlleft='',$morehtmlstatus='',$morehtmlright='')
	{
		global $langs,$conf,$hookmanager;

		$ret='';
		if (empty($fieldid))  $fieldid='rowid';
		if (empty($fieldref)) $fieldref='ref';

		// Add where from hooks
		if (is_object($hookmanager))
		{
			$parameters=array();
			$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters, $object);    // Note that $action and $object may have been modified by hook
			$object->next_prev_filter.=$hookmanager->resPrint;
		}
		$previous_ref = $next_ref = '';
		if ($shownav)
		{
			//print "paramid=$paramid,morehtml=$morehtml,shownav=$shownav,$fieldid,$fieldref,$morehtmlref,$moreparam";
			$object->load_previous_next_ref((isset($object->next_prev_filter)?$object->next_prev_filter:''), $fieldid, $nodbprefix);

			$navurl = $_SERVER["PHP_SELF"];
			// Special case for project/task page
			if ($paramid == 'project_ref')
			{
				$navurl = preg_replace('/\/tasks\/(task|contact|time|note|document)\.php/','/tasks.php',$navurl);
				$paramid='ref';
			}

			// accesskey is for Windows or Linux:  ALT + key for chrome, ALT + SHIFT + KEY for firefox
			// accesskey is for Mac:               CTRL + key for all browsers
			$previous_ref = $object->ref_previous?'<a accesskey="p" title="'.$langs->trans("KeyboardShortcut").' ALT+p|ALT+SHIFT+p|CTRL+p" href="'.$navurl.'?'.$paramid.'='.urlencode($object->ref_previous).$moreparam.'"><i class="fa fa-chevron-left"></i></a>':'<span class="inactive"><i class="fa fa-chevron-left opacitymedium"></i></span>';
			$next_ref     = $object->ref_next?'<a accesskey="n" title="'.$langs->trans("KeyboardShortcut").' ALT+n|ALT+SHIFT+n|CTRL+n" href="'.$navurl.'?'.$paramid.'='.urlencode($object->ref_next).$moreparam.'"><i class="fa fa-chevron-right"></i></a>':'<span class="inactive"><i class="fa fa-chevron-right opacitymedium"></i></span>';
		}

		//print "xx".$previous_ref."x".$next_ref;
		$ret.='<!-- Start banner content --><div style="vertical-align: middle">';

		// Right part of banner
		if ($morehtmlright) $ret.='<div class="inline-block floatleft">'.$morehtmlright.'</div>';

		if ($previous_ref || $next_ref || $morehtml)
		{
			$ret.='<div class="pagination paginationref"><ul class="right">';
		}
		if ($morehtml)
		{
			$ret.='<li class="noborder litext">'.$morehtml.'</li>';
		}
		if ($shownav && ($previous_ref || $next_ref))
		{
			$ret.='<li class="pagination">'.$previous_ref.'</li>';
			$ret.='<li class="pagination">'.$next_ref.'</li>';
		}
		if ($previous_ref || $next_ref || $morehtml)
		{
			$ret.='</ul></div>';
		}

		$parameters=array();
		$reshook=$hookmanager->executeHooks('moreHtmlStatus',$parameters, $object);    // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $morehtmlstatus.=$hookmanager->resPrint;
		else $morehtmlstatus=$hookmanager->resPrint;
		if ($morehtmlstatus) $ret.='<div class="statusref">'.$morehtmlstatus.'</div>';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('moreHtmlRef', $parameters, $object); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $morehtmlref.=$hookmanager->resPrint;
		elseif ($reshook > 0) $morehtmlref=$hookmanager->resPrint;

		// Left part of banner
		if ($morehtmlleft)
		{
			if ($conf->browser->layout == 'phone') $ret.='<div class="floatleft">'.$morehtmlleft.'</div>';    // class="center" to have photo in middle
			else $ret.='<div class="inline-block floatleft">'.$morehtmlleft.'</div>';
		}

		//if ($conf->browser->layout == 'phone') $ret.='<div class="clearboth"></div>';
		$ret.='<div class="inline-block floatleft valignmiddle refid'.(($shownav && ($previous_ref || $next_ref))?' refidpadding':'').'">';

		// For thirdparty, contact, user, member, the ref is the id, so we show something else
		if ($object->element == 'societe')
		{
			$ret.=dol_htmlentities($object->name);
		}
		else if ($object->element == 'member')
		{
			$ret.=$object->ref.'<br>';
			$fullname=$object->getFullName($langs);
			if ($object->morphy == 'mor' && $object->societe) {
				$ret.= dol_htmlentities($object->societe) . ((! empty($fullname) && $object->societe != $fullname)?' ('.dol_htmlentities($fullname).')':'');
			} else {
				$ret.= dol_htmlentities($fullname) . ((! empty($object->societe) && $object->societe != $fullname)?' ('.dol_htmlentities($object->societe).')':'');
			}
		}
		else if (in_array($object->element, array('contact', 'user', 'usergroup')))
		{
			$ret.=dol_htmlentities($object->getFullName($langs));
		}
		else if (in_array($object->element, array('action', 'agenda')))
		{
			$ret.=$object->ref.'<br>'.$object->label;
		}
		else if (in_array($object->element, array('adherent_type')))
		{
			$ret.=$object->label;
		}
		else if ($object->element == 'ecm_directories')
		{
			$ret.='';
		}
		else if ($fieldref != 'none') $ret.=dol_htmlentities($object->$fieldref);


		if ($morehtmlref)
		{
			$ret.=' '.$morehtmlref;
		}
		$ret.='</div>';

		$ret.='</div><!-- End banner content -->';

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
	 *    	@param	string		$modulepart			Key to define module concerned ('societe', 'userphoto', 'memberphoto')
	 *     	@param  object		$object				Object containing data to retrieve file name
	 * 		@param	int			$width				Width of photo
	 * 		@param	int			$height				Height of photo (auto if 0)
	 * 		@param	int			$caneditfield		Add edit fields
	 * 		@param	string		$cssclass			CSS name to use on img for photo
	 * 		@param	string		$imagesize		    'mini', 'small' or '' (original)
	 *      @param  int         $addlinktofullsize  Add link to fullsize image
	 *      @param  int         $cache              1=Accept to use image in cache
	 *      @param	string		$forcecapture		Force parameter capture on HTML input file element to ask a smartphone to allow to open camera to take photo. Auto if empty.
	 * 	  	@return string    						HTML code to output photo
	 */
	static function showphoto($modulepart, $object, $width=100, $height=0, $caneditfield=0, $cssclass='photowithmargin', $imagesize='', $addlinktofullsize=1, $cache=0, $forcecapture='')
	{
		global $conf,$langs;

		$entity = (! empty($object->entity) ? $object->entity : $conf->entity);
		$id = (! empty($object->id) ? $object->id : $object->rowid);

		$ret='';$dir='';$file='';$originalfile='';$altfile='';$email='';$capture='';
		if ($modulepart=='societe')
		{
			$dir=$conf->societe->multidir_output[$entity];
			if (! empty($object->logo))
			{
				if ((string) $imagesize == 'mini') $file=get_exdir(0, 0, 0, 0, $object, 'thirdparty').'/logos/'.getImageFileNameForSize($object->logo, '_mini');             // getImageFileNameForSize include the thumbs
				else if ((string) $imagesize == 'small') $file=get_exdir(0, 0, 0, 0, $object, 'thirdparty').'/logos/'.getImageFileNameForSize($object->logo, '_small');
				else $file=get_exdir(0, 0, 0, 0, $object, 'thirdparty').'/logos/'.$object->logo;
				$originalfile=get_exdir(0, 0, 0, 0, $object, 'thirdparty').'/logos/'.$object->logo;
			}
			$email=$object->email;
		}
		else if ($modulepart=='contact')
		{
			$dir=$conf->societe->multidir_output[$entity].'/contact';
			if (! empty($object->photo))
			{
				if ((string) $imagesize == 'mini') $file=get_exdir(0, 0, 0, 0, $object, 'contact').'/photos/'.getImageFileNameForSize($object->photo, '_mini');
				else if ((string) $imagesize == 'small') $file=get_exdir(0, 0, 0, 0, $object, 'contact').'/photos/'.getImageFileNameForSize($object->photo, '_small');
				else $file=get_exdir(0, 0, 0, 0, $object, 'contact').'/photos/'.$object->photo;
				$originalfile=get_exdir(0, 0, 0, 0, $object, 'contact').'/photos/'.$object->photo;
			}
			$email=$object->email;
			$capture='user';
		}
		else if ($modulepart=='userphoto')
		{
			$dir=$conf->user->dir_output;
			if (! empty($object->photo))
			{
				if ((string) $imagesize == 'mini') $file=get_exdir(0, 0, 0, 0, $object, 'user').$object->id.'/'.getImageFileNameForSize($object->photo, '_mini');
				else if ((string) $imagesize == 'small') $file=get_exdir(0, 0, 0, 0, $object, 'user').$object->id.'/'.getImageFileNameForSize($object->photo, '_small');
				else $file=get_exdir(0, 0, 0, 0, $object, 'user').'/'.$object->id.'/'.$object->photo;
				$originalfile=get_exdir(0, 0, 0, 0, $object, 'user').'/'.$object->id.'/'.$object->photo;
			}
			if (! empty($conf->global->MAIN_OLD_IMAGE_LINKS)) $altfile=$object->id.".jpg";	// For backward compatibility
			$email=$object->email;
			$capture='user';
		}
		else if ($modulepart=='memberphoto')
		{
			$dir=$conf->adherent->dir_output;
			if (! empty($object->photo))
			{
				if ((string) $imagesize == 'mini') $file=get_exdir(0, 0, 0, 0, $object, 'member').'photos/'.getImageFileNameForSize($object->photo, '_mini');
				else if ((string) $imagesize == 'small') $file=get_exdir(0, 0, 0, 0, $object, 'member').'photos/'.getImageFileNameForSize($object->photo, '_small');
				else $file=get_exdir(0, 0, 0, 0, $object, 'member').'photos/'.$object->photo;
				$originalfile=get_exdir(0, 0, 0, 0, $object, 'member').'photos/'.$object->photo;
			}
			if (! empty($conf->global->MAIN_OLD_IMAGE_LINKS)) $altfile=$object->id.".jpg";	// For backward compatibility
			$email=$object->email;
			$capture='user';
		}
		else
		{
			// Generic case to show photos
			$dir=$conf->$modulepart->dir_output;
			if (! empty($object->photo))
			{
				if ((string) $imagesize == 'mini') $file=get_exdir($id, 2, 0, 0, $object, $modulepart).'photos/'.getImageFileNameForSize($object->photo, '_mini');
				else if ((string) $imagesize == 'small') $file=get_exdir($id, 2, 0, 0, $object, $modulepart).'photos/'.getImageFileNameForSize($object->photo, '_small');
				else $file=get_exdir($id, 2, 0, 0, $object, $modulepart).'photos/'.$object->photo;
				$originalfile=get_exdir($id, 2, 0, 0, $object, $modulepart).'photos/'.$object->photo;
			}
			if (! empty($conf->global->MAIN_OLD_IMAGE_LINKS)) $altfile=$object->id.".jpg";	// For backward compatibility
			$email=$object->email;
		}

		if ($forcecapture) $capture = $forcecapture;

		if ($dir)
		{
			if ($file && file_exists($dir."/".$file))
			{
				if ($addlinktofullsize)
				{
					$urladvanced=getAdvancedPreviewUrl($modulepart, $originalfile, 0, '&entity='.$entity);
					if ($urladvanced) $ret.='<a href="'.$urladvanced.'">';
					else $ret.='<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$entity.'&file='.urlencode($originalfile).'&cache='.$cache.'">';
				}
				$ret.='<img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="Photo" id="photologo'.(preg_replace('/[^a-z]/i','_',$file)).'" '.($width?' width="'.$width.'"':'').($height?' height="'.$height.'"':'').' src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$entity.'&file='.urlencode($file).'&cache='.$cache.'">';
				if ($addlinktofullsize) $ret.='</a>';
			}
			else if ($altfile && file_exists($dir."/".$altfile))
			{
				if ($addlinktofullsize)
				{
					$urladvanced=getAdvancedPreviewUrl($modulepart, $originalfile, 0, '&entity='.$entity);
					if ($urladvanced) $ret.='<a href="'.$urladvanced.'">';
					else $ret.='<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$entity.'&file='.urlencode($originalfile).'&cache='.$cache.'">';
				}
				$ret.='<img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="Photo alt" id="photologo'.(preg_replace('/[^a-z]/i','_',$file)).'" class="'.$cssclass.'" '.($width?' width="'.$width.'"':'').($height?' height="'.$height.'"':'').' src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$entity.'&file='.urlencode($altfile).'&cache='.$cache.'">';
				if ($addlinktofullsize) $ret.='</a>';
			}
			else
			{
				$nophoto='/public/theme/common/nophoto.png';
				if (in_array($modulepart,array('userphoto','contact')))	// For module that are "physical" users
				{
					$nophoto='/public/theme/common/user_anonymous.png';
					if ($object->gender == 'man') $nophoto='/public/theme/common/user_man.png';
					if ($object->gender == 'woman') $nophoto='/public/theme/common/user_woman.png';
				}

				if (! empty($conf->gravatar->enabled) && $email)
				{
					/**
					 * @see https://gravatar.com/site/implement/images/php/
					 */
					global $dolibarr_main_url_root;
					$ret.='<!-- Put link to gravatar -->';
					//$defaultimg=urlencode(dol_buildpath($nophoto,3));
					$defaultimg='mm';
					$ret.='<img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="Gravatar avatar" title="'.$email.' Gravatar avatar" '.($width?' width="'.$width.'"':'').($height?' height="'.$height.'"':'').' src="https://www.gravatar.com/avatar/'.dol_hash(strtolower(trim($email)),3).'?s='.$width.'&d='.$defaultimg.'">';	// gravatar need md5 hash
				}
				else
				{
					$ret.='<img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="No photo" '.($width?' width="'.$width.'"':'').($height?' height="'.$height.'"':'').' src="'.DOL_URL_ROOT.$nophoto.'">';
				}
			}

			if ($caneditfield)
			{
				if ($object->photo) $ret.="<br>\n";
				$ret.='<table class="nobordernopadding centpercent">';
				if ($object->photo) $ret.='<tr><td><input type="checkbox" class="flat photodelete" name="deletephoto" id="photodelete"> '.$langs->trans("Delete").'<br><br></td></tr>';
				$ret.='<tr><td class="tdoverflow"><input type="file" class="flat maxwidth200onsmartphone" name="photo" id="photoinput"'.($capture?' capture="'.$capture.'"':'').'></td></tr>';
				$ret.='</table>';
			}
		}
		else dol_print_error('','Call of showphoto with wrong parameters modulepart='.$modulepart);

		return $ret;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
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
	 * 	@param	string	$force_entity	'0' or Ids of environment to force
	 * 	@param	bool	$multiple		add [] in the name of element and add 'multiple' attribut (not working with ajax_autocompleter)
	 *  @return	string
	 *  @see select_dolusers
	 */
	function select_dolgroups($selected='', $htmlname='groupid', $show_empty=0, $exclude='', $disabled=0, $include='', $enableonly='', $force_entity='0', $multiple=false)
	{
        // phpcs:enable
		global $conf,$user,$langs;

		// Permettre l'exclusion de groupes
		if (is_array($exclude))	$excludeGroups = implode("','",$exclude);
		// Permettre l'inclusion de groupes
		if (is_array($include))	$includeGroups = implode("','",$include);

		if (!is_array($selected)) $selected = array($selected);

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
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		   	$out .= ajax_combobox($htmlname);

			$out.= '<select class="flat minwidth200" id="'.$htmlname.'" name="'.$htmlname.($multiple ? '[]' : '').'" '.($multiple ? 'multiple' : '').' '.($disabled?' disabled':'').'>';

			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				if ($show_empty && !$multiple) $out.= '<option value="-1"'.(in_array(-1,$selected)?' selected':'').'>&nbsp;</option>'."\n";

				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					$disableline=0;
					if (is_array($enableonly) && count($enableonly) && ! in_array($obj->rowid,$enableonly)) $disableline=1;

					$out.= '<option value="'.$obj->rowid.'"';
					if ($disableline) $out.= ' disabled';
					if ((is_object($selected[0]) && $selected[0]->id == $obj->rowid) || (! is_object($selected[0]) && in_array($obj->rowid,$selected) ))
					{
						$out.= ' selected';
					}
					$out.= '>';

					$out.= $obj->name;
					if (! empty($conf->multicompany->enabled) && empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1)
					{
						$out.= " (".$obj->label.")";
					}

					$out.= '</option>';
					$i++;
				}
			}
			else
			{
				if ($show_empty) $out.= '<option value="-1"'.(in_array(-1,$selected)?' selected':'').'></option>'."\n";
				$out.= '<option value="" disabled>'.$langs->trans("NoUserGroupDefined").'</option>';
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
	 *	Return HTML to show the search and clear seach button
	 *
	 *  @return	string
	 */
	function showFilterButtons()
	{
		global $conf, $langs;

		$out='<div class="nowrap">';
		$out.='<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
		$out.='<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
		$out.='</div>';

		return $out;
	}

	/**
	 *	Return HTML to show the search and clear seach button
	 *
	 *  @param  string  $cssclass                  CSS class
	 *  @param  int     $calljsfunction            0=default. 1=call function initCheckForSelect() after changing status of checkboxes
	 *  @return	string
	 */
	function showCheckAddButtons($cssclass='checkforaction', $calljsfunction=0)
	{
		global $conf, $langs;

		$out='';
		if (! empty($conf->use_javascript_ajax)) $out.='<div class="inline-block checkallactions"><input type="checkbox" id="checkallactions" name="checkallactions" class="checkallactions"></div>';
		$out.='<script type="text/javascript">
            $(document).ready(function() {
            	$("#checkallactions").click(function() {
                    if($(this).is(\':checked\')){
                        console.log("We check all");
                		$(".'.$cssclass.'").prop(\'checked\', true).trigger(\'change\');
                    }
                    else
                    {
                        console.log("We uncheck all");
                		$(".'.$cssclass.'").prop(\'checked\', false).trigger(\'change\');
                    }'."\n";
		if ($calljsfunction) $out.='if (typeof initCheckForSelect == \'function\') { initCheckForSelect(0); } else { console.log("No function initCheckForSelect found. Call won\'t be done."); }';
		$out.='         });

        	$(".checkforselect").change(function() {
				$(this).closest("tr").toggleClass("highlight", this.checked);
			});

 	});
    </script>';

		return $out;
	}

	/**
	 *	Return HTML to show the search and clear seach button
	 *
	 *  @param	int  	$addcheckuncheckall        Add the check all/uncheck all checkbox (use javascript) and code to manage this
	 *  @param  string  $cssclass                  CSS class
	 *  @param  int     $calljsfunction            0=default. 1=call function initCheckForSelect() after changing status of checkboxes
	 *  @return	string
	 */
	function showFilterAndCheckAddButtons($addcheckuncheckall=0, $cssclass='checkforaction', $calljsfunction=0)
	{
		$out.=$this->showFilterButtons();
		if ($addcheckuncheckall)
		{
			$out.=$this->showCheckAddButtons($cssclass, $calljsfunction);
		}
		return $out;
	}

	/**
	 * Return HTML to show the select of expense categories
	 *
	 * @param	string	$selected              preselected category
	 * @param	string	$htmlname              name of HTML select list
	 * @param	integer	$useempty              1=Add empty line
	 * @param	array	$excludeid             id to exclude
	 * @param	string	$target                htmlname of target select to bind event
	 * @param	int		$default_selected      default category to select if fk_c_type_fees change = EX_KME
	 * @param	array	$params                param to give
	 * @return	string
	 */
	function selectExpenseCategories($selected='', $htmlname='fk_c_exp_tax_cat', $useempty=0, $excludeid=array(), $target='', $default_selected=0, $params=array())
	{
		global $db, $conf, $langs, $user;

		$sql = 'SELECT rowid, label FROM '.MAIN_DB_PREFIX.'c_exp_tax_cat WHERE active = 1';
		$sql.= ' AND entity IN (0,'.getEntity('exp_tax_cat').')';
		if (!empty($excludeid)) $sql.= ' AND rowid NOT IN ('.implode(',', $excludeid).')';
		$sql.= ' ORDER BY label';

		$resql = $db->query($sql);
		if ($resql)
		{
			$out = '<select name="'.$htmlname.'" class="'.$htmlname.' flat minwidth75imp">';
			if ($useempty) $out.= '<option value="0">&nbsp;</option>';

			while ($obj = $db->fetch_object($resql))
			{
				$out.= '<option '.($selected == $obj->rowid ? 'selected="selected"' : '').' value="'.$obj->rowid.'">'.$langs->trans($obj->label).'</option>';
			}
			$out.= '</select>';
			if (! empty($htmlname) && $user->admin) $out .= ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);

			if (!empty($target))
			{
				$sql = "SELECT c.id FROM ".MAIN_DB_PREFIX."c_type_fees as c WHERE c.code = 'EX_KME' AND c.active = 1";
				$resql = $db->query($sql);
				if ($resql)
				{
					if ($db->num_rows($resql) > 0)
					{
						$obj = $db->fetch_object($resql);
						$out.= '<script type="text/javascript">
							$(function() {
								$("select[name='.$target.']").on("change", function() {
									var current_val = $(this).val();
									if (current_val == '.$obj->id.') {';
						if (!empty($default_selected) || !empty($selected)) $out.= '$("select[name='.$htmlname.']").val("'.($default_selected > 0 ? $default_selected : $selected).'");';

						$out.= '
										$("select[name='.$htmlname.']").change();
									}
								});

								$("select[name='.$htmlname.']").change(function() {

									if ($("select[name='.$target.']").val() == '.$obj->id.') {
										// get price of kilometer to fill the unit price
										var data = '.json_encode($params).';
										data.fk_c_exp_tax_cat = $(this).val();

										$.ajax({
											method: "POST",
											dataType: "json",
											data: data,
											url: "'.(DOL_URL_ROOT.'/expensereport/ajax/ajaxik.php').'",
										}).done(function( data, textStatus, jqXHR ) {
											console.log(data);
											if (typeof data.up != "undefined") {
												$("input[name=value_unit]").val(data.up);
												$("select[name='.$htmlname.']").attr("title", data.title);
											} else {
												$("input[name=value_unit]").val("");
												$("select[name='.$htmlname.']").attr("title", "");
											}
										});
									}
								});
							});
						</script>';
					}
				}
			}
		}
		else
		{
			dol_print_error($db);
		}

		return $out;
	}

	/**
	 * Return HTML to show the select ranges of expense range
	 *
	 * @param	string	$selected    preselected category
	 * @param	string	$htmlname    name of HTML select list
	 * @param	integer	$useempty    1=Add empty line
	 * @return	string
	 */
	function selectExpenseRanges($selected='', $htmlname='fk_range', $useempty=0)
	{
		global $db,$conf,$langs;

		$sql = 'SELECT rowid, range_ik FROM '.MAIN_DB_PREFIX.'c_exp_tax_range';
		$sql.= ' WHERE entity = '.$conf->entity.' AND active = 1';

		$resql = $db->query($sql);
		if ($resql)
		{
			$out = '<select name="'.$htmlname.'" class="'.$htmlname.' flat minwidth75imp">';
			if ($useempty) $out.= '<option value="0"></option>';

			while ($obj = $db->fetch_object($resql))
			{
				$out.= '<option '.($selected == $obj->rowid ? 'selected="selected"' : '').' value="'.$obj->rowid.'">'.price($obj->range_ik, 0, $langs, 1, 0).'</option>';
			}
			$out.= '</select>';
		}
		else
		{
			dol_print_error($db);
		}

		return $out;
	}

	/**
	 * Return HTML to show a select of expense
	 *
	 * @param	string	$selected    preselected category
	 * @param	string	$htmlname    name of HTML select list
	 * @param	integer	$useempty    1=Add empty choice
	 * @param	integer	$allchoice   1=Add all choice
	 * @param	integer	$useid       0=use 'code' as key, 1=use 'id' as key
	 * @return	string
	 */
	function selectExpense($selected='', $htmlname='fk_c_type_fees', $useempty=0, $allchoice=1, $useid=0)
	{
		global $db,$langs;

		$sql = 'SELECT id, code, label FROM '.MAIN_DB_PREFIX.'c_type_fees';
		$sql.= ' WHERE active = 1';

		$resql = $db->query($sql);
		if ($resql)
		{
			$out = '<select name="'.$htmlname.'" class="'.$htmlname.' flat minwidth75imp">';
			if ($useempty) $out.= '<option value="0"></option>';
			if ($allchoice) $out.= '<option value="-1">'.$langs->trans('AllExpenseReport').'</option>';

			$field = 'code';
			if ($useid) $field = 'id';

			while ($obj = $db->fetch_object($resql))
			{
				$key = $langs->trans($obj->code);
				$out.= '<option '.($selected == $obj->{$field} ? 'selected="selected"' : '').' value="'.$obj->{$field}.'">'.($key != $obj->code ? $key : $obj->label).'</option>';
			}
			$out.= '</select>';
		}
		else
		{
			dol_print_error($db);
		}

		return $out;
	}
}
