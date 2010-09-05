<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 */

/**
 *   \file		htdocs/core/class/canvas.class.php
 *   \ingroup		core
 *   \brief			Fichier de la classe de gestion des canvas
 *   \version		$Id$
 */


/**
 *    \class      Canvas
 *    \brief      Classe de la gestion des canvas
 */

class Canvas
{
	var $db;
	
	var $card;
	var $canvas;
	var $module;
	var $object;
	var $control;
	var $aliasmodule;		// for compatibility
	var $template_dir;		// Directory with all core and external templates files
	var $action;
	var $smarty;

	var $error;

   /**
	*   Constructor.
	*   @param     DB      Database handler
	*/
	function Canvas($DB)
	{
		$this->db = $DB;
	}
	
	/**
	 * 	Return the title of card
	 */
	function getTitle()
	{
		return $this->control->getTitle($this->action);
	}
	
	/**
     *    Assigne les valeurs POST dans l'objet
     */
	function assign_post()
	{
		return $this->control->assign_post();
	}
	
	/**
     *    Set action type
     */
	function setAction($action='view')
	{
		return $this->action = $action;
	}
	
	/**
	 * 	Load data control
	 */
	function loadControl($socid)
	{
		return $this->control->loadControl($socid);
	}
	
	/**
	 * 	Fetch object values
	 * 	@param		id			Element id
	 */
	function fetch($id)
	{
		return $this->control->object->fetch($id, $this->action);
	}

	/**
	 * 	Get card and canvas type
	 * 	@param		card	 	Type of card
	 * 	@param		canvas		Name of canvas (ex: default@mymodule)
	 */
	function getCanvas($card,$canvas)
	{
		global $langs;

		$error='';
		$this->card = $card;

		if (preg_match('/^([^@]+)@([^@]+)$/i',$canvas,$regs))
		{
			$this->aliasmodule = $this->module = $regs[2];
			$this->canvas = $regs[1];
			
			// For compatibility
			if ($this->module == 'thirdparty') $this->aliasmodule = 'societe';
		}
		else
		{
			$this->error = $langs->trans('CanvasIsInvalid');
			return 0;
		}

		if (file_exists(DOL_DOCUMENT_ROOT.'/'.$this->aliasmodule.'/canvas/'.$this->canvas.'/'.$this->module.'.'.$this->canvas.'.class.php') &&
			file_exists(DOL_DOCUMENT_ROOT.'/'.$this->aliasmodule.'/canvas/'.$this->canvas.'/'.$this->card.'.'.$this->canvas.'.class.php'))
		{
			// Include model class
			$modelclassfile = DOL_DOCUMENT_ROOT.'/'.$this->aliasmodule.'/canvas/'.$this->canvas.'/'.$this->module.'.'.$this->canvas.'.class.php';
			include_once($modelclassfile);
			
			// Include common controller class
			$controlclassfile = DOL_DOCUMENT_ROOT.'/'.$this->aliasmodule.'/canvas/'.$this->card.'.common.class.php';
			include_once($controlclassfile);
			
			// Include canvas controller class
			$controlclassfile = DOL_DOCUMENT_ROOT.'/'.$this->aliasmodule.'/canvas/'.$this->canvas.'/'.$this->card.'.'.$this->canvas.'.class.php';
			include_once($controlclassfile);
			
			// Instantiate canvas controller class
			$controlclassname = ucfirst($this->card).ucfirst($this->canvas);
			$this->control = new $controlclassname($this->db);
			
			// Instantiate model class
			$modelclassname = ucfirst($this->module).ucfirst($this->canvas);
			$this->control->object = new $modelclassname($this->db);
			
			// Canvas
			$this->control->canvas = $canvas;
			
			// Template dir
			$this->template_dir = DOL_DOCUMENT_ROOT.'/'.$this->aliasmodule.'/canvas/'.$this->canvas.'/tpl/';
			
			// Need smarty
			$this->smarty = $this->control->smarty;
		}
		else
		{
			$this->error = $langs->trans('CanvasIsInvalid');
			return 0;
		}
		
		return 1;
	}

	/**
	 * 	Assign templates values
	 * 	@param	action	Type of action
	 */
	function assign_values()
	{
		if (!empty($this->smarty))
		{
			global $smarty;

			$this->control->assign_smarty_values($smarty, $this->action);
			$smarty->template_dir = $this->template_dir;
		}
		else
		{
			$this->control->assign_values($this->action);
		}

	}

	/**
	 * 	Display canvas
	 */
	function display_canvas()
	{
		global $conf, $langs, $user, $canvas;

		if (!empty($this->smarty))
		{
			global $smarty;

			$smarty->display($this->action.'.tpl');
		}
		else
		{
			include($this->template_dir.$this->card.'_'.$this->action.'.tpl.php');
		}
	}

}
?>
