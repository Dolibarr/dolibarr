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
 *       \brief      Classe permettant de gérer FCKEditor
 *       \version    $Id$
*/

/**
 *       \class      DolEditor
 *       \brief      Classe de gestion de FCKEditor
 *       \remarks    Usage:
 *		\remarks	$doleditor=new DolEditor('body',$message,320,'toolbar_mailing');
 *		\remarks	$doleditor->Create();
 */
class DolEditor
{
	var $editor;


    /**
            \brief 	DolEditor
            \param 	htmlname		        Nom formulaire html WYSIWIG
            \param 	content			        Contenu édition WYSIWIG
            \param 	height			        Hauteur en pixel de la zone édition
            \param 	toolbarname		        Nom barre de menu éditeur
            \param  toolbarlocation       	Emplacement de la barre de menu :
                                          	'In' chaque fenêtre d'édition a ça propre barre d'outils
                                          	'Out:nom' partage de la barre d'outils où 'nom' est le nom du DIV qui affiche la barre
            \param  toolbarstartexpanded  	visible ou non au démarrage
			\param	modulepart				modulepart pour protection wrapper download viewimage
	*/
    function DolEditor($htmlname,$content,$height=200,$toolbarname='Basic',$toolbarlocation='In',$toolbarstartexpanded=false,$uselocalbrowser=true)
    {
    	global $conf,$langs;

    	dol_syslog("DolEditor::DolEditor modulepart=".$modulepart);

    	require_once(DOL_DOCUMENT_ROOT."/includes/fckeditor/fckeditor.php");


		$content=dol_htmlentitiesbr($content);	// If content is not HTML, we convert to HTML.
		//print "\n".$conf->global->MAIN_MOTD."\n".$content;exit;

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


    /**
     *		\brief 	Show edit area
     */
    function Create()
    {
    	$this->editor->Create();
    }

}


?>
