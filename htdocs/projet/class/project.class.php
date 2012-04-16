<?php

/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
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
 * 		\file       htdocs/projet/class/project.class.php
 * 		\ingroup    projet
 * 		\brief      Fichier de la classe de gestion des projets
 */
require_once(DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
 * 		\class      Project
 * 		\brief      Class to manage projects
 */
class Project extends CommonObject
{

    public $element = 'project';    //!< Id that identify managed objects
    public $table_element = 'projet';  //!< Name of table without prefix where object is stored
    var $id;
    var $ref;
    var $description;
    var $statut;
    var $title;
    var $date_c;
    var $date_m;
    var $date_start;
    var $date_end;
    var $socid;
    var $user_author_id;    //!< Id of project creator. Not defined if shared project.
    var $public;      //!< Tell if this is a public or private project
    var $note_private;
    var $note_public;
    var $statuts_short;
    var $statuts;

    /**
     *  Constructor
     *
     *  @param      DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        $this->societe = new Societe($db);

        $this->statuts_short = array(0 => 'Draft', 1 => 'Validated', 2 => 'Closed');
        $this->statuts = array(0 => 'Draft', 1 => 'Validated', 2 => 'Closed');
    }

    /**
     *    Create a project into database
     *
     *    @param    User	$user       	User making creation
     *    @param	int		$notrigger		Disable triggers
     *    @return   int         			<0 if KO, id of created project if OK
     */
    function create($user, $notrigger=0)
    {
        global $conf, $langs;

        $error = 0;
        $ret = 0;

        // Check parameters
        if (!trim($this->ref))
        {
            $this->error = 'ErrorFieldsRequired';
            dol_syslog("Project::Create error -1 ref null", LOG_ERR);
            return -1;
        }

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "projet (";
        $sql.= "ref";
        $sql.= ", title";
        $sql.= ", description";
        $sql.= ", fk_soc";
        $sql.= ", fk_user_creat";
        $sql.= ", fk_statut";
        $sql.= ", public";
        $sql.= ", datec";
        $sql.= ", dateo";
        $sql.= ", datee";
        $sql.= ", entity";
        $sql.= ") VALUES (";
        $sql.= "'" . $this->db->escape($this->ref) . "'";
        $sql.= ", '" . $this->db->escape($this->title) . "'";
        $sql.= ", '" . $this->db->escape($this->description) . "'";
        $sql.= ", " . ($this->socid > 0 ? $this->socid : "null");
        $sql.= ", " . $user->id;
        $sql.= ", 0";
        $sql.= ", " . ($this->public ? 1 : 0);
        $sql.= ", " . ($this->datec != '' ? $this->db->idate($this->datec) : 'null');
        $sql.= ", " . ($this->dateo != '' ? $this->db->idate($this->dateo) : 'null');
        $sql.= ", " . ($this->datee != '' ? $this->db->idate($this->datee) : 'null');
        $sql.= ", ".$conf->entity;
        $sql.= ")";

        dol_syslog("Project::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "projet");
            $ret = $this->id;

            if (!$notrigger)
            {
                // Call triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_CREATE', $this, $user, $langs, $conf);
                if ($result < 0)
                {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // End call triggers
            }
        }
        else
        {
            $this->error = $this->db->lasterror();
            $this->errno = $this->db->lasterrno();
            dol_syslog("Project::Create error -2 " . $this->error, LOG_ERR);
            $error++;
        }

        if (!$error && !empty($conf->global->MAIN_DISABLEDRAFTSTATUS))
        {
            $res = $this->setValid($user);
            if ($res < 0) $error++;
        }

        if (!$error)
        {
            $this->db->commit();
            return $ret;
        }
        else
        {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Update a project
     *
     * @param  User		$user       User object of making update
     * @param  int		$notrigger  1=Disable all triggers
     * @return int
     */
    function update($user, $notrigger=0)
    {
        global $langs, $conf;

		$error=0;

        // Clean parameters
        $this->title = trim($this->title);
        $this->description = trim($this->description);

        if (dol_strlen(trim($this->ref)) > 0)
        {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "projet SET";
            $sql.= " ref='" . $this->ref . "'";
            $sql.= ", title = '" . $this->db->escape($this->title) . "'";
            $sql.= ", description = '" . $this->db->escape($this->description) . "'";
            $sql.= ", fk_soc = " . ($this->socid > 0 ? $this->socid : "null");
            $sql.= ", fk_statut = " . $this->statut;
            $sql.= ", public = " . ($this->public ? 1 : 0);
            $sql.= ", datec=" . ($this->date_c != '' ? $this->db->idate($this->date_c) : 'null');
            $sql.= ", dateo=" . ($this->date_start != '' ? $this->db->idate($this->date_start) : 'null');
            $sql.= ", datee=" . ($this->date_end != '' ? $this->db->idate($this->date_end) : 'null');
            $sql.= " WHERE rowid = " . $this->id;

            dol_syslog("Project::Update sql=" . $sql, LOG_DEBUG);
            if ($this->db->query($sql))
            {
                if (!$notrigger)
                {
                    // Call triggers
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface = new Interfaces($this->db);
                    $result = $interface->run_triggers('PROJECT_MODIFY', $this, $user, $langs, $conf);
                    if ($result < 0)
                    {
                        $error++;
                        $this->errors = $interface->errors;
                    }
                    // End call triggers
                }

                $result = 1;
            }
            else
            {
                $this->error = $this->db->lasterror();
                dol_syslog("Project::Update error -2 " . $this->error, LOG_ERR);
                $result = -2;
            }
        }
        else
        {
            dol_syslog("Project::Update ref null");
            $result = -1;
        }

        return $result;
    }

    /**
     * 	Get object and lines from database
     *
     * 	@param      int		$id       	Id of object to load
     * 	@param		string	$ref		Ref of project
     * 	@return     int      		   	>0 if OK, 0 if not found, <0 if KO
     */
    function fetch($id, $ref='')
    {
        if (empty($id) && empty($ref)) return -1;

        $sql = "SELECT rowid, ref, title, description, public, datec";
        $sql.= ", tms, dateo, datee, fk_soc, fk_user_creat, fk_statut, note_private, note_public";
        $sql.= " FROM " . MAIN_DB_PREFIX . "projet";
        if ($ref) $sql.= " WHERE ref='" . $ref . "'";
        else $sql.= " WHERE rowid=" . $id;

        dol_syslog("Project::fetch sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->title = $obj->title;
                $this->titre = $obj->title; // TODO deprecated
                $this->description = $obj->description;
                $this->date_c = $this->db->jdate($obj->datec);
                $this->datec = $this->db->jdate($obj->datec); // TODO deprecated
                $this->date_m = $this->db->jdate($obj->tms);
                $this->datem = $this->db->jdate($obj->tms);  // TODO deprecated
                $this->date_start = $this->db->jdate($obj->dateo);
                $this->date_end = $this->db->jdate($obj->datee);
                $this->note_private = $obj->note_private;
                $this->note_public = $obj->note_public;
                $this->socid = $obj->fk_soc;
                $this->societe->id = $obj->fk_soc; // TODO For backward compatibility
                $this->user_author_id = $obj->fk_user_creat;
                $this->public = $obj->public;
                $this->statut = $obj->fk_statut;

                $this->db->free($resql);

                return 1;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            $this->error = $this->db->lasterror();
            dol_syslog("Project::fetch " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     * 	Return list of projects
     *
     * 	@param		int		$socid		To filter on a particular third party
     * 	@return		array				List of projects
     */
    function liste_array($socid='')
    {
        global $conf;

        $projects = array();

        $sql = "SELECT rowid, title";
        $sql.= " FROM " . MAIN_DB_PREFIX . "projet";
        $sql.= " WHERE entity = " . $conf->entity;
        if (! empty($socid)) $sql.= " AND fk_soc = " . $socid;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $nump = $this->db->num_rows($resql);

            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {
                    $obj = $this->db->fetch_object($resql);

                    $projects[$obj->rowid] = $obj->title;
                    $i++;
                }
            }
            return $projects;
        }
        else
        {
            print $this->db->lasterror();
        }
    }

    /**
     * 	Return list of elements for type linked to project
     *
     * 	@param		string		$type		'propal','order','invoice','order_supplier','invoice_supplier'
     * 	@return		array					List of orders linked to project, <0 if error
     */
    function get_element_list($type)
    {
        $elements = array();

        $sql = '';
        if ($type == 'propal')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "propal WHERE fk_projet=" . $this->id;
        if ($type == 'order')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "commande WHERE fk_projet=" . $this->id;
        if ($type == 'invoice')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "facture WHERE fk_projet=" . $this->id;
        if ($type == 'invoice_predefined')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "facture_rec WHERE fk_projet=" . $this->id;
        if ($type == 'order_supplier')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "commande_fournisseur WHERE fk_projet=" . $this->id;
        if ($type == 'invoice_supplier')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "facture_fourn WHERE fk_projet=" . $this->id;
        if ($type == 'contract')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "contrat WHERE fk_projet=" . $this->id;
        if ($type == 'intervention')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "fichinter WHERE fk_projet=" . $this->id;
        if ($type == 'trip')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "deplacement WHERE fk_projet=" . $this->id;
        if ($type == 'agenda')
            $sql = "SELECT id as rowid FROM " . MAIN_DB_PREFIX . "actioncomm WHERE fk_project=" . $this->id;
        if (! $sql) return -1;

        //print $sql;
        dol_syslog("Project::get_element_list sql=" . $sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $nump = $this->db->num_rows($result);
            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {
                    $obj = $this->db->fetch_object($result);

                    $elements[$i] = $obj->rowid;

                    $i++;
                }
                $this->db->free($result);

                /* Return array */
                return $elements;
            }
        }
        else
        {
            dol_print_error($this->db);
        }
    }

    /**
     *    Delete a project from database
     *
     *    @param       User		$user            User
     *    @param       int		$notrigger       Disable triggers
     *    @return      int       			      <0 if KO, 0 if not possible, >0 if OK
     */
    function delete($user, $notrigger=0)
    {
        global $langs, $conf;
        require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");

        $error = 0;

        $this->db->begin();

        if (!$error)
        {
            // Delete linked contacts
            $res = $this->delete_linked_contact();
            if ($res < 0)
            {
                $this->error = 'ErrorFailToDeleteLinkedContact';
                //$error++;
                $this->db->rollback();
                return 0;
            }
        }

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "projet_task";
        $sql.= " WHERE fk_projet=" . $this->id;

        dol_syslog(get_class($this) . "::delete sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "projet";
        $sql.= " WHERE rowid=" . $this->id;

        dol_syslog(get_class($this) . "::delete sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            // We remove directory
            $projectref = dol_sanitizeFileName($this->ref);
            if ($conf->projet->dir_output)
            {
                $dir = $conf->projet->dir_output . "/" . $projectref;
                if (file_exists($dir))
                {
                    $res = @dol_delete_dir_recursive($dir);
                    if (!$res)
                    {
                        $this->error = 'ErrorFailToDeleteDir';
                        $this->db->rollback();
                        return 0;
                    }
                }
            }

            if (!$notrigger)
            {
                // Call triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_DELETE', $this, $user, $langs, $conf);
                if ($result < 0)
                {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // End call triggers
            }

            dol_syslog("Project::delete sql=" . $sql, LOG_DEBUG);
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error = $this->db->lasterror();
            dol_syslog("Project::delete " . $this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * 		Validate a project
     *
     * 		@param		User	$user		User that validate
     * 		@return		int					<0 if KO, >0 if OK
     */
    function setValid($user)
    {
        global $langs, $conf;

		$error=0;

        if ($this->statut != 1)
        {
            $this->db->begin();

            $sql = "UPDATE " . MAIN_DB_PREFIX . "projet";
            $sql.= " SET fk_statut = 1";
            $sql.= " WHERE rowid = " . $this->id;
            $sql.= " AND entity = " . $conf->entity;

            dol_syslog("Project::setValid sql=" . $sql);
            $resql = $this->db->query($sql);
            if ($resql)
            {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_VALIDATE', $this, $user, $langs, $conf);
                if ($result < 0)
                {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers

                if (!$error)
                {
                    $this->db->commit();
                    return 1;
                }
                else
                {
                    $this->db->rollback();
                    $this->error = join(',', $this->errors);
                    dol_syslog("Project::setValid " . $this->error, LOG_ERR);
                    return -1;
                }
            }
            else
            {
                $this->db->rollback();
                $this->error = $this->db->lasterror();
                dol_syslog("Project::setValid " . $this->error, LOG_ERR);
                return -1;
            }
        }
    }

    /**
     * 		Close a project
     *
     * 		@param		User	$user		User that validate
     * 		@return		int					<0 if KO, >0 if OK
     */
    function setClose($user)
    {
        global $langs, $conf;

		$error=0;

        if ($this->statut != 2)
        {
            $this->db->begin();

            $sql = "UPDATE " . MAIN_DB_PREFIX . "projet";
            $sql.= " SET fk_statut = 2";
            $sql.= " WHERE rowid = " . $this->id;
            $sql.= " AND entity = " . $conf->entity;
            $sql.= " AND fk_statut = 1";

            dol_syslog("Project::setClose sql=" . $sql);
            $resql = $this->db->query($sql);
            if ($resql)
            {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_CLOSE', $this, $user, $langs, $conf);
                if ($result < 0)
                {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers

                if (!$error)
                {
                    $this->db->commit();
                    return 1;
                }
                else
                {
                    $this->db->rollback();
                    $this->error = join(',', $this->errors);
                    dol_syslog("Project::setClose " . $this->error, LOG_ERR);
                    return -1;
                }
            }
            else
            {
                $this->db->rollback();
                $this->error = $this->db->lasterror();
                dol_syslog("Project::setClose " . $this->error, LOG_ERR);
                return -1;
            }
        }
    }

    /**
     *  Return status label of object
     *
     *  @param  int			$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     * 	@return string      			Label
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut, $mode);
    }

    /**
     *  Renvoi status label for a status
     *
     *  @param	int		$statut     id statut
     *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     * 	@return string				Label
     */
    function LibStatut($statut, $mode=0)
    {
        global $langs;

        if ($mode == 0)
        {
            return $langs->trans($this->statuts[$statut]);
        }
        if ($mode == 1)
        {
            return $langs->trans($this->statuts_short[$statut]);
        }
        if ($mode == 2)
        {
            if ($statut == 0)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut0') . ' ' . $langs->trans($this->statuts_short[$statut]);
            if ($statut == 1)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut4') . ' ' . $langs->trans($this->statuts_short[$statut]);
            if ($statut == 2)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut6') . ' ' . $langs->trans($this->statuts_short[$statut]);
        }
        if ($mode == 3)
        {
            if ($statut == 0)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut0');
            if ($statut == 1)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut4');
            if ($statut == 2)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut6');
        }
        if ($mode == 4)
        {
            if ($statut == 0)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut0') . ' ' . $langs->trans($this->statuts_short[$statut]);
            if ($statut == 1)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut4') . ' ' . $langs->trans($this->statuts_short[$statut]);
            if ($statut == 2)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut6') . ' ' . $langs->trans($this->statuts_short[$statut]);
        }
        if ($mode == 5)
        {
            if ($statut == 0)
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_short[$statut]), 'statut0');
            if ($statut == 1)
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_short[$statut]), 'statut1');
            if ($statut == 2)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut6') . ' ' . $langs->trans($this->statuts_short[$statut]);
        }
    }

    /**
     * 	Renvoie nom clicable (avec eventuellement le picto)
     *
     * 	@param	int		$withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
     * 	@param	string	$option			Variant ('', 'nolink')
     * 	@param	int		$addlabel		0=Default, 1=Add label into string
     * 	@return	string					Chaine avec URL
     */
    function getNomUrl($withpicto=0, $option='', $addlabel=0)
    {
        global $langs;

        $result = '';
        $lien = '';
        $lienfin = '';

        if ($option != 'nolink')
        {
            $lien = '<a href="' . DOL_URL_ROOT . '/projet/fiche.php?id=' . $this->id . '">';
            $lienfin = '</a>';
        }

        $picto = 'projectpub';
        if (!$this->public) $picto = 'project';

        $label = $langs->trans("ShowProject") . ': ' . $this->ref . ($this->title ? ' - ' . $this->title : '');

        if ($withpicto) $result.=($lien . img_object($label, $picto) . $lienfin);
        if ($withpicto && $withpicto != 2) $result.=' ';
        if ($withpicto != 2) $result.=$lien . $this->ref . $lienfin . (($addlabel && $this->title) ? ' - ' . $this->title : '');
        return $result;
    }

    /**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     * 	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    function initAsSpecimen()
    {
        global $user, $langs, $conf;

        $now = dol_now();

        // Charge tableau des produits prodids
        $prodids = array();

        $sql = "SELECT rowid";
        $sql.= " FROM " . MAIN_DB_PREFIX . "product";
        $sql.= " WHERE tosell = 1";
        $sql.= " AND entity = " . $conf->entity;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num_prods = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num_prods)
            {
                $i++;
                $row = $this->db->fetch_row($resql);
                $prodids[$i] = $row[0];
            }
        }

        // Initialise parametres
        $this->id = 0;
        $this->ref = 'SPECIMEN';
        $this->specimen = 1;
        $socid = rand(1, $num_socs);
        $this->socid = 1;
        $this->date_c = $now;
        $this->date_m = $now;
        $this->date_start = $now;
        $this->note_public = 'SPECIMEN';
        $nbp = rand(1, 9);
        $xnbp = 0;
        while ($xnbp < $nbp)
        {
            $line = new Task($this->db);
            $line->desc = $langs->trans("Description") . " " . $xnbp;
            $line->qty = 1;
            $prodid = rand(1, $num_prods);
            $line->fk_product = $prodids[$prodid];
            $xnbp++;
        }
    }

    /**
     * 	Check if user has permission on current project
     *
     * 	@param	User	$user		Object user to evaluate
     * 	@param  string	$mode		Type of permission we want to know: 'read', 'write'
     * 	@return	int					>0 if user has permission, <0 if user has no permission
     */
    function restrictedProjectArea($user, $mode='read')
    {
        // To verify role of users
        $userAccess = 0;
        if (($mode == 'read' && $user->rights->projet->all->lire) || ($mode == 'write' && $user->rights->projet->all->creer) || ($mode == 'delete' && $user->rights->projet->all->supprimer))
        {
            $userAccess = 1;
        }
        else if ($this->public && (($mode == 'read' && $user->rights->projet->lire) || ($mode == 'write' && $user->rights->projet->creer) || ($mode == 'delete' && $user->rights->projet->supprimer)))
        {
            $userAccess = 1;
        }
        else
        {
            foreach (array('internal', 'external') as $source)
            {
                $userRole = $this->liste_contact(4, $source);
                $num = count($userRole);

                $nblinks = 0;
                while ($nblinks < $num)
                {
                    if (preg_match('/PROJECT/', $userRole[$nblinks]['code']) && $user->id == $userRole[$nblinks]['id'])
                    {
                        if ($mode == 'read'   && $user->rights->projet->lire)      $userAccess++;
                        if ($mode == 'write'  && $user->rights->projet->creer)     $userAccess++;
                        if ($mode == 'delete' && $user->rights->projet->supprimer) $userAccess++;
                    }
                    $nblinks++;
                }
            }
            //if (empty($nblinks))	// If nobody has permission, we grant creator
            //{
            //	if ((!empty($this->user_author_id) && $this->user_author_id == $user->id))
            //	{
            //		$userAccess = 1;
            //	}
            //}
        }

        return ($userAccess?$userAccess:-1);
    }

    /**
     * Return array of projects a user has permission on, is affected to, or all projects
     *
     * @param 	User	$user			User object
     * @param 	int		$mode			0=All project I have permission on, 1=Projects affected to me only, 2=Will return list of all projects with no test on contacts
     * @param 	int		$list			0=Return array,1=Return string list
     * @param	int		$socid			0=No filter on third party, id of third party
     * @return 	array 					Array of projects
     */
    function getProjectsAuthorizedForUser($user, $mode=0, $list=0, $socid=0)
    {
        global $conf;

        $projects = array();
        $temp = array();

        $sql = "SELECT DISTINCT p.rowid, p.ref";
        $sql.= " FROM " . MAIN_DB_PREFIX . "projet as p";
        if ($mode == 0 || $mode == 1)
        {
            $sql.= ", " . MAIN_DB_PREFIX . "element_contact as ec";
            $sql.= ", " . MAIN_DB_PREFIX . "c_type_contact as ctc";
        }
        $sql.= " WHERE p.entity = " . $conf->entity;
        // Internal users must see project he is contact to even if project linked to a third party he can't see.
        //if ($socid || ! $user->rights->societe->client->voir)	$sql.= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
        if ($socid > 0) $sql.= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = " . $socid . ")";

        if ($mode == 0)
        {
            $sql.= " AND ec.element_id = p.rowid AND ( p.public = 1";
            //$sql.= " OR p.fk_user_creat = ".$user->id;
            $sql.= " OR ( ctc.rowid = ec.fk_c_type_contact";
            $sql.= " AND ctc.element = '" . $this->element . "'";
            $sql.= " AND ec.fk_socpeople = " . $user->id . " ) )";
        }
        if ($mode == 1)
        {
            $sql.= " AND ec.element_id = p.rowid";
            $sql.= " AND ctc.rowid = ec.fk_c_type_contact";
            $sql.= " AND ctc.element = '" . $this->element . "'";
            $sql.= " AND ec.fk_socpeople = " . $user->id;
        }
        if ($mode == 2)
        {
            // No filter. Use this if user has permission to see all project
        }
        //print $sql;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $row = $this->db->fetch_row($resql);
                $projects[$row[0]] = $row[1];
                $temp[] = $row[0];
                $i++;
            }

            $this->db->free($resql);

            if ($list)
            {
                if (empty($temp)) return 0;
                $result = implode(',', $temp);
                return $result;
            }
        }
        else
        {
            dol_print_error($this->db);
        }

        return $projects;
    }

}

?>
