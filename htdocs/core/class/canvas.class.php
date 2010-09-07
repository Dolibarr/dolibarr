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
 *   \file			htdocs/core/class/canvas.class.php
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
	var $object;
	var $control;
	var $module;
	var $targetmodule;
	var $aliasmodule;		// for compatibility
	var $aliastargetmodule;	// for compatibility
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
	 * 	Execute actions
	 * 	@param 		Id of object (may be empty for creation)
	 */
	function doActions($socid)
	{
		return $this->control->doActions($socid);
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
	 * 	@param		module		Name of target module (product, thirdparty, ...)
	 * 	@param		card	 	Type of card (ex: card, info, ...)
	 * 	@param		canvas		Name of canvas (ex: mycanvas@mymodule)
	 */
	function getCanvas($module,$card,$canvas)
	{
		global $conf, $langs;

		$error='';
		$this->card = $card;
		$this->canvas = $canvas;
		$childmodule = $this->aliasmodule = $this->module = $module;
		$targetmodule = $this->aliastargetmodule = $this->targetmodule = $module;

		if (preg_match('/^([^@]+)@([^@]+)$/i',$canvas,$regs))
		{
			$childmodule = $this->aliasmodule = $this->module = $regs[2];
			$this->canvas = $regs[1];

			// For compatibility
			if ($childmodule == 'thirdparty') $childmodule = $this->aliasmodule = 'societe';
			if ($targetmodule == 'thirdparty') $targetmodule = 'societe';
		}

		//print 'childmodule='.$childmodule.' targetmodule='.$targetmodule.'<br>';
		//print 'childmodule='.$conf->$childmodule->enabled.' targetmodule='.$conf->$targetmodule->enabled.'<br>';

		if (! $conf->$childmodule->enabled || ! $conf->$targetmodule->enabled) accessforbidden();

		if (file_exists(DOL_DOCUMENT_ROOT.'/'.$this->aliasmodule.'/canvas/'.$this->canvas.'/dao_'.$this->targetmodule.'_'.$this->canvas.'.class.php') &&
			file_exists(DOL_DOCUMENT_ROOT.'/'.$this->aliasmodule.'/canvas/'.$this->canvas.'/actions_'.$this->card.'_'.$this->canvas.'.class.php'))
		{
			// Include dataservice class (model)
			$modelclassfile = DOL_DOCUMENT_ROOT.'/'.$this->aliasmodule.'/canvas/'.$this->canvas.'/dao_'.$this->targetmodule.'_'.$this->canvas.'.class.php';
			include_once($modelclassfile);

			// Include actions class (controller)
			$controlclassfile = DOL_DOCUMENT_ROOT.'/'.$this->aliasmodule.'/canvas/'.$this->canvas.'/actions_'.$this->card.'_'.$this->canvas.'.class.php';
			include_once($controlclassfile);

			// Instantiate actions class (controller)
			$controlclassname = 'Actions'.ucfirst($this->card).ucfirst($this->canvas);
			$this->control = new $controlclassname($this->db);

			// Instantiate dataservice class (model)
			$modelclassname = 'Dao'.ucfirst($this->targetmodule).ucfirst($this->canvas);
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
			accessforbidden();
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
