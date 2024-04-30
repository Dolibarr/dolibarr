<?php
/* Copyright (C) 2015 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014 Juanjo Menent	      <jmenent@2byte.es>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024  Christophe Battarel	<christophe@altairis.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/fourn/class/fournisseur.commande.dispatch.class.php
 *  \ingroup    fournisseur stock
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *              Initially built by build_class_from_table on 2015-02-24 10:38
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT."/reception/class/receptionlinebatch.class.php";


/**
 *  Class to manage table ReceptionLineBatch.
 *  Old name was CommandeFournisseurDispatch. This is a transition class.
 */
class CommandeFournisseurDispatch extends ReceptionLineBatch
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'commandefournisseurdispatch';
	/**
	 * @var int ID
	 */
	public $fk_commande;
	/**
	 * @var int ID
	 */
	public $fk_commandefourndet;


	/**
	 *  Create object into database
	 *
	 *  @param	User	$user        User that creates
	 *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
	 *  @return int      		   	 Return integer <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0)
	{
		if (empty($this->fk_element) && !empty($this->fk_commande)) {
			$this->fk_element = $this->fk_commande;
		}
		if (empty($this->fk_elementdet) && !empty($this->fk_commandefourndet)) {
			$this->fk_elementdet = $this->fk_commandefourndet;
		}

		return parent::create($user, $notrigger);
	}

	/**
	 *  Load object in memory from the database
	 *
	 *  @param	int		$id    	Id object
	 *  @param	string	$ref	Ref
	 *  @return int          	Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id, $ref = '')
	{
		$ret = parent::fetch($id, $ref);
		if ($ret > 0) {
			$this->fk_commande = $this->fk_element;
			$this->fk_commandefourndet = $this->fk_elementdet;
		}
		return $ret;
	}

	/**
	 *  Update object into database
	 *
	 *  @param	User	$user        User that modifies
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return int     		   	 Return integer <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0)
	{
		$this->fk_element = $this->fk_commande;
		$this->fk_elementdet = $this->fk_commandefourndet;

		return parent::update($user, $notrigger);
	}
}
