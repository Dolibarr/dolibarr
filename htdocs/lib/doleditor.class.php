<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *       \file       htdocs/lib/doleditor.class.php
 *       \brief      Class to manage a WYSIWYG editor
 *       \version    $Id$
 */

/**
 * 		\class      DolEditor
 *      \brief      Classe de gestion de FCKEditor
 *      \remarks    Usage:
 *		\remarks	$doleditor=new DolEditor('body',$message,320,'toolbar_mailing');
 *		\remarks	$doleditor->Create();
 */
class DolEditor
{
    var $tool;      // Store the selected tool

	// If using fckeditor
	var $editor;

	// If not using fckeditor
	var $content;
	var $htmlname;
	var $rows;
	var $cols;
	var $height;
	var $width;


    /**
     *      DolEditor                       Create an object to build an HTML area to edit a large string content
     *      @param 	htmlname		        Nom formulaire html WYSIWIG
     *      @param 	content			        Contenu edition WYSIWIG
     *      @param 	height			        Hauteur en pixel de la zone edition
     *      @param 	toolbarname		        Nom barre de menu editeur
     *      @param  toolbarlocation       	Emplacement de la barre de menu :
     *                                    	'In' chaque fenetre d'edition a la propre barre d'outils
     *                                    	'Out:nom' partage de la barre d'outils ou 'nom' est le nom du DIV qui affiche la barre
     *      @param  toolbarstartexpanded  	visible ou non au demarrage
	 *		@param	uselocalbrowser			Enabled to add links to local object with local browsers. If false, only external images can be added in content.
	 *      @param  okforextandededitor     True=Allow usage of extended editor tool (like fckeditor)
     *      @param  rows                    Size of rows for textarea tool
	 *      @param  cols                    Size of cols for textarea tool
	 */
    function DolEditor($htmlname,$content,$height=200,$toolbarname='Basic',$toolbarlocation='In',$toolbarstartexpanded=false,$uselocalbrowser=true,$okforextendededitor=true,$rows=0,$cols=0)
    {
    	global $conf,$langs;

    	dol_syslog("DolEditor::DolEditor htmlname=".$htmlname." tool=".$tool);

        // Name of extended editor to use
    	$this->tool=empty($conf->global->MAIN_EXTENDED_EDITOR_NAME)?'fckeditor':$conf->global->MAIN_EXTENDED_EDITOR_NAME;

        // Check if extended editor is ok. If not we force textarea
        if (empty($conf->fckeditor->enabled) || ! $okforextendededitor)
        {
            $this->tool = 'textarea';
        }

    	if ($this->tool == 'fckeditor')
    	{
        	require_once(DOL_DOCUMENT_ROOT."/includes/fckeditor/fckeditor.php");

    		$content=dol_htmlentitiesbr($content);	// If content is not HTML, we convert to HTML.

        	$this->editor = new FCKeditor($htmlname);
        	$this->editor->BasePath = DOL_URL_ROOT.'/includes/fckeditor/' ;
        	$this->editor->Value	= $content;
        	$this->editor->Height   = $height;
        	$this->editor->ToolbarSet = $toolbarname;
        	$this->editor->Config['AutoDetectLanguage'] = 'true';
        	$this->editor->Config['ToolbarLocation'] = $toolbarlocation ? $toolbarlocation : 'In';
        	$this->editor->Config['ToolbarStartExpanded'] = $toolbarstartexpanded;

    		// Rem: Le forcage de ces 2 parametres ne semble pas fonctionner.
    		// Dolibarr utilise toujours liens avec modulepart='fckeditor' quelque soit modulepart.
    		// Ou se trouve donc cette valeur /viewimage.php?modulepart=fckeditor&file=' ?
        	$modulepart='fckeditor';
    		$this->editor->Config['UserFilesPath'] = '/viewimage.php?modulepart='.$modulepart.'&file=';
    		$this->editor->Config['UserFilesAbsolutePath'] = DOL_DATA_ROOT.'/'.$modulepart.'/' ;

        	$this->editor->Config['LinkBrowser']=($uselocalbrowser?'true':'false');
        	$this->editor->Config['ImageBrowser']=($uselocalbrowser?'true':'false');

        	if (file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/fckeditor/fckconfig.js'))
        	{
        		$this->editor->Config['CustomConfigurationsPath'] = DOL_URL_ROOT.'/theme/'.$conf->theme.'/fckeditor/fckconfig.js';
        		$this->editor->Config['SkinPath'] = DOL_URL_ROOT.'/theme/'.$conf->theme.'/fckeditor/';
    		}
    	}

        if (in_array($this->tool,array('textarea','ckeditor')))
        {
    	    $this->content	= $content;
    	    $this->htmlname = $htmlname;
            $this->rows		= max(ROWS_3,$rows);
            $this->cols		= max(40,$cols);
            $this->height	= $height;
            $this->width	= 600;
    	}

    }

    /**
     *		Output edit area inside the HTML stream
     */
    function Create()
    {
    	global $conf;

        $found=0;

        if ($this->tool == 'fckeditor')
        {
            $found=1;
            $this->editor->Create();
        }
        if (in_array($this->tool,array('textarea','ckeditor')))
        {
            $found=1;
            print '<textarea id="'.$this->htmlname.'" name="'.$this->htmlname.'" rows="'.$this->rows.'" cols="'.$this->cols.'" class="flat">';
            print $this->content;
            print '</textarea>';

            if ($this->tool == 'ckeditor')
            {
            	if (! defined('REQUIRE_CKEDITOR')) define('REQUIRE_CKEDITOR','1');
            	
            	print '<script type="text/javascript">';
            	print 'jQuery(document).ready(function () {';
            	print 'CKEDITOR.replace(\''.$this->htmlname.'\', { toolbar: \'Basic\', width: '.$this->width.', height: '.$this->height.', toolbarStartupExpanded: false });';
            	print '});';
            	print '</script>';
            }
        }

        if (empty($found))
        {
            print 'Error, unknown value for tool '.$this->tool.' in DolEditor Create function.';
        }
    }

}

?>
