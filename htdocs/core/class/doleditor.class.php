<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *       \file       htdocs/core/class/doleditor.class.php
 *       \brief      Class to manage a WYSIWYG editor
 */

/**
 *      Class to manage a WYSIWYG editor.
 *		Usage: $doleditor=new DolEditor('body',$message,320,'toolbar_mailing');
 *		       $doleditor->Create();
 */
class DolEditor
{
    var $tool;      // Store the selected tool

	// If using fckeditor
	var $editor;

	// If not using fckeditor
	var $content;
	var $htmlname;
	var $toolbarname;
	var $toolbarstartexpanded;
	var $rows;
	var $cols;
	var $height;
	var $width;
	var $readonly;


    /**
     *      Create an object to build an HTML area to edit a large string content
     *
     *      @param 	string	$htmlname		        HTML name of WYSIWIG field
     *      @param 	string	$content		        Content of WYSIWIG field
     *      @param	int		$width					Width in pixel of edit area (auto by default)
     *      @param 	int		$height			        Height in pixel of edit area (200px by default)
     *      @param 	string	$toolbarname	        Name of bar set to use ('Full', 'dolibarr_notes[_encoded]', 'dolibarr_details[_encoded]'=the less featured, 'dolibarr_mailings[_encoded]', 'dolibarr_readonly')
     *      @param  string	$toolbarlocation       	Where bar is stored :
     *                       		             	'In' each window has its own toolbar
     *                              		      	'Out:name' share toolbar into the div called 'name'
     *      @param  boolean	$toolbarstartexpanded  	Bar is visible or not at start
	 *		@param	int		$uselocalbrowser		Enabled to add links to local object with local browser. If false, only external images can be added in content.
	 *      @param  int		$okforextendededitor    True=Allow usage of extended editor tool (like fckeditor)
     *      @param  int		$rows                   Size of rows for textarea tool
	 *      @param  string	$cols                   Size of cols for textarea tool (textarea number of cols '70' or percent 'x%')
	 *      @param	int		$readonly				0=Read/Edit, 1=Read only
	 */
    function __construct($htmlname,$content,$width='',$height=200,$toolbarname='Basic',$toolbarlocation='In',$toolbarstartexpanded=false,$uselocalbrowser=true,$okforextendededitor=true,$rows=0,$cols=0,$readonly=0)
    {
    	global $conf,$langs;

    	dol_syslog(get_class($this)."::DolEditor htmlname=".$htmlname." width=".$width." height=".$height." toolbarname=".$toolbarname);

    	if (! $rows) $rows=round($height/20);
    	if (! $cols) $cols=($width?round($width/6):80);
		$shorttoolbarname=preg_replace('/_encoded$/','',$toolbarname);

        // Name of extended editor to use (FCKEDITOR_EDITORNAME can be 'ckeditor' or 'fckeditor')
        $defaulteditor='ckeditor';
        $this->tool=empty($conf->global->FCKEDITOR_EDITORNAME)?$defaulteditor:$conf->global->FCKEDITOR_EDITORNAME;
        $this->uselocalbrowser=$uselocalbrowser;
        $this->readonly=$readonly;

        // Check if extended editor is ok. If not we force textarea
        if (empty($conf->fckeditor->enabled) || ! $okforextendededitor) $this->tool = 'textarea';
        if ($conf->dol_use_jmobile) $this->tool = 'textarea';       // TODO ckeditor ko with jmobile

        // Define content and some properties
        if ($this->tool == 'ckeditor')
        {
            $content=dol_htmlentitiesbr($content);  // If content is not HTML, we convert to HTML.
        }
        if ($this->tool == 'fckeditor')
    	{
        	require_once DOL_DOCUMENT_ROOT.'/includes/fckeditor/fckeditor.php';

    		$content=dol_htmlentitiesbr($content);	// If content is not HTML, we convert to HTML.

        	$this->editor = new FCKeditor($htmlname);
        	$this->editor->BasePath = DOL_URL_ROOT.'/includes/fckeditor/' ;
        	$this->editor->Value	= $content;
        	$this->editor->Height   = $height;
        	if (! empty($width)) $this->editor->Width = $width;
        	$this->editor->ToolbarSet = $shorttoolbarname;         // Profile of this toolbar set is deinfed into theme/mytheme/ckeditor/config.js
        	$this->editor->Config['AutoDetectLanguage'] = 'true';
        	$this->editor->Config['ToolbarLocation'] = $toolbarlocation ? $toolbarlocation : 'In';
        	$this->editor->Config['ToolbarStartExpanded'] = $toolbarstartexpanded;

    		// Rem: Le forcage de ces 2 parametres ne semble pas fonctionner.
    		// Dolibarr utilise toujours liens avec modulepart='fckeditor' quelque soit modulepart.
    		// Ou se trouve donc cette valeur /viewimage.php?modulepart=fckeditor&file=' ?
        	$modulepart='fckeditor';
    		$this->editor->Config['UserFilesPath'] = '/viewimage.php?modulepart='.$modulepart.'&entity='.$conf->entity.'&file=';
    		$this->editor->Config['UserFilesAbsolutePath'] = DOL_DATA_ROOT.'/'.$modulepart.'/' ;

        	$this->editor->Config['LinkBrowser']=($uselocalbrowser?'true':'false');
        	$this->editor->Config['ImageBrowser']=($uselocalbrowser?'true':'false');

        	if (file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/fckeditor/fckconfig.js'))
        	{
        		$this->editor->Config['CustomConfigurationsPath'] = DOL_URL_ROOT.'/theme/'.$conf->theme.'/fckeditor/fckconfig.js';
        		$this->editor->Config['SkinPath'] = DOL_URL_ROOT.'/theme/'.$conf->theme.'/fckeditor/';
    		}
    	}

    	// Define some properties
        if (in_array($this->tool,array('textarea','ckeditor')))
        {
    	    $this->content				= $content;
    	    $this->htmlname 			= $htmlname;
    	    $this->toolbarname			= $shorttoolbarname;
    	    $this->toolbarstartexpanded = $toolbarstartexpanded;
            $this->rows					= max(ROWS_3,$rows);
            $this->cols					= (preg_match('/%/',$cols)?$cols:max(40,$cols));	// If $cols is a percent, we keep it, otherwise, we take max
            $this->height				= $height;
            $this->width				= $width;
    	}

    }

    /**
     *	Output edit area inside the HTML stream.
     *	Output depends on this->tool (fckeditor, ckeditor, textarea, ...)
     *
     *  @param	int		$noprint             1=Return HTML string instead of printing it to output
     *  @param	string	$morejs		         Add more js. For example: ".on( \'saveSnapshot\', function(e) { alert(\'ee\'); });"
     *  @param  boolean $disallowAnyContent  Disallow to use any content. true=restrict to a predefined list of allowed elements.
     *  @return	void|string
     */
    function Create($noprint=0,$morejs='',$disallowAnyContent=true)
    {
    	global $conf,$langs;

    	$fullpage=False;
    	if (isset($conf->global->FCKEDITOR_ALLOW_ANY_CONTENT))
    	{
    	   $disallowAnyContent=empty($conf->global->FCKEDITOR_ALLOW_ANY_CONTENT);      // Only predefined list of html tags are allowed or all
    	}

    	$found=0;
		$out='';

        if ($this->tool == 'fckeditor') // not used anymore
        {
            $found=1;
            $this->editor->Create();
        }
        if (in_array($this->tool,array('textarea','ckeditor')))
        {
            $found=1;
            //$out.= '<textarea id="'.$this->htmlname.'" name="'.$this->htmlname.'" rows="'.$this->rows.'" cols="'.$this->cols.'"'.($this->readonly?' disabled':'').' class="flat">';
            $out.= '<textarea id="'.$this->htmlname.'" name="'.$this->htmlname.'" rows="'.$this->rows.'"'.(preg_match('/%/',$this->cols)?' style="margin-top: 2px; width: '.$this->cols.'"':' cols="'.$this->cols.'"').' class="flat">';
            $out.= $this->content;
            $out.= '</textarea>';

            if ($this->tool == 'ckeditor' && ! empty($conf->use_javascript_ajax))
            {
            	if (! defined('REQUIRE_CKEDITOR')) define('REQUIRE_CKEDITOR','1');

            	if (! empty($conf->global->FCKEDITOR_SKIN)) {
					$skin = $conf->global->FCKEDITOR_SKIN;
				} else {
					$skin = 'moono'; // default with ckeditor 4 : moono
				}

            	$htmlencode_force=preg_match('/_encoded$/',$this->toolbarname)?'true':'false';

            	$out.= '<!-- Output ckeditor $disallowAnyContent='.$disallowAnyContent.' toolbarname='.$this->toolbarname.' -->'."\n";
            	$out.= '<script type="text/javascript">
            			$(document).ready(function () {
                            /* if (CKEDITOR.loadFullCore) CKEDITOR.loadFullCore(); */
                            /* should be editor=CKEDITOR.replace but what if serveral editors ? */
                            CKEDITOR.replace(\''.$this->htmlname.'\',
            					{
            						/* property:xxx is same than CKEDITOR.config.property = xxx */
            						customConfig : ckeditorConfig,
            						readOnly : '.($this->readonly?'true':'false').',
                            		htmlEncodeOutput :'.$htmlencode_force.',
            						allowedContent :'.($disallowAnyContent?'false':'true').',
            						extraAllowedContent : \'\',
            						fullPage : '.($fullpage?'true':'false').', 
                            		toolbar: \''.$this->toolbarname.'\',
            						toolbarStartupExpanded: '.($this->toolbarstartexpanded ? 'true' : 'false').',
            						width: '.($this->width ? '\''.$this->width.'\'' : '\'\'').',
            						height: '.$this->height.',
                                    skin: \''.$skin.'\',
                                    language: \''.$langs->defaultlang.'\',
                                    textDirection: \''.$langs->trans("DIRECTION").'\',
                                    on :
                                            {
                                                instanceReady : function( ev )
                                                {
                                                    // Output paragraphs as <p>Text</p>.
                                                    this.dataProcessor.writer.setRules( \'p\',
                                                        {
                                                            indent : false,
                                                            breakBeforeOpen : true,
                                                            breakAfterOpen : false,
                                                            breakBeforeClose : false,
                                                            breakAfterClose : true
                                                        });
                                                }
                                            }';
            	if ($this->uselocalbrowser)
            	{
                    $out.= ','."\n";
                    // To use filemanager with old fckeditor (GPL)
                    $out.= '    filebrowserBrowseUrl : ckeditorFilebrowserBrowseUrl,';
                    $out.= '    filebrowserImageBrowseUrl : ckeditorFilebrowserImageBrowseUrl,';
                    //$out.= '    filebrowserUploadUrl : \''.DOL_URL_ROOT.'/includes/fckeditor/editor/filemanagerdol/connectors/php/upload.php?Type=File\',';
                    //$out.= '    filebrowserImageUploadUrl : \''.DOL_URL_ROOT.'/includes/fckeditor/editor/filemanagerdol/connectors/php/upload.php?Type=Image\',';
                    $out.= "\n";
                    // To use filemanager with ckfinder (Non free) and ckfinder directory is inside htdocs/includes
					/* $out.= '    filebrowserBrowseUrl : \''.DOL_URL_ROOT.'/includes/ckfinder/ckfinder.html\',
                               filebrowserImageBrowseUrl : \''.DOL_URL_ROOT.'/includes/ckfinder/ckfinder.html?Type=Images\',
                               filebrowserFlashBrowseUrl : \''.DOL_URL_ROOT.'/includes/ckfinder/ckfinder.html?Type=Flash\',
                               filebrowserUploadUrl : \''.DOL_URL_ROOT.'/includes/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files\',
                               filebrowserImageUploadUrl : \''.DOL_URL_ROOT.'/includes/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images\',
                               filebrowserFlashUploadUrl : \''.DOL_URL_ROOT.'/includes/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash\','."\n";
					*/
                    $out.= '    filebrowserWindowWidth : \'900\',
                               filebrowserWindowHeight : \'500\',
                               filebrowserImageWindowWidth : \'900\',
                               filebrowserImageWindowHeight : \'500\'';
            	}
            	$out.= '	})'.$morejs;
            	$out.= '});
            			</script>';
            }
        }

        if (empty($found))
        {
            $out.= 'Error, unknown value for tool '.$this->tool.' in DolEditor Create function.';
        }

        if ($noprint) return $out;
        else print $out;
    }

}

