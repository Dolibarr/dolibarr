<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file    website/websitepage.class.php
 * \ingroup website
 * \brief   File for the CRUD class of websitepage (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class Websitepage
 */
class WebsitePage extends CommonObject
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'websitepage';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'website_page';
	/**
	 * @var string String with name of icon for websitepage. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'label';

	/**
	 */

	public $fk_website;
	public $pageurl;
	public $type_container;
	public $title;
	public $description;
	public $keywords;
	public $htmlheader;
	public $content;
	public $grabbed_from;
	public $status;
	public $date_creation;
	public $date_modification;


	// BEGIN MODULEBUILDER PROPERTIES
	/**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
	public $fields=array(
	    'rowid'          =>array('type'=>'integer',      'label'=>'TechnicalID',      'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'index'=>1, 'position'=>1,  'comment'=>'Id'),
		'pageurl'        =>array('type'=>'varchar(16)',  'label'=>'WEBSITE_PAGENAME', 'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1, 'comment'=>'Alias of page'),
		'type_container' =>array('type'=>'varchar(16)',  'label'=>'Type',             'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'index'=>0, 'position'=>11, 'comment'=>'Type of container'),
		'title'          =>array('type'=>'varchar(255)', 'label'=>'Label',            'enabled'=>1, 'visible'=>1,  'position'=>30,  'searchall'=>1),
	    'description'    =>array('type'=>'varchar(255)', 'label'=>'Description',      'enabled'=>1, 'visible'=>1,  'position'=>30,  'searchall'=>1),
	    'keywords'       =>array('type'=>'varchar(255)', 'label'=>'Keywords',         'enabled'=>1, 'visible'=>1,  'position'=>45,  'searchall'=>0),
		'lang'           =>array('type'=>'varchar(6)',   'label'=>'Lang',             'enabled'=>1, 'visible'=>1,  'position'=>45,  'searchall'=>0),
		//'status'        =>array('type'=>'integer',      'label'=>'Status',           'enabled'=>1, 'visible'=>1,  'index'=>true,   'position'=>1000),
	    'fk_website'     =>array('type'=>'integer',      'label'=>'WebsiteId',        'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'position'=>40,  'searchall'=>0, 'foreignkey'=>'websitepage.rowid'),
	    'fk_page'        =>array('type'=>'integer',      'label'=>'ParentPageId',     'enabled'=>1, 'visible'=>1,  'notnull'=>-1, 'position'=>45,  'searchall'=>0, 'foreignkey'=>'website.rowid'),
	    'htmlheader'     =>array('type'=>'text',         'label'=>'HtmlHeader',       'enabled'=>1, 'visible'=>0,  'position'=>50,  'searchall'=>0),
	    'content'        =>array('type'=>'mediumtext',   'label'=>'Content',          'enabled'=>1, 'visible'=>0,  'position'=>51,  'searchall'=>0),
		'grabbed_from'   =>array('type'=>'varchar(255)', 'label'=>'GrabbedFrom',      'enabled'=>1, 'visible'=>1,  'index'=>1,   'position'=>400, 'comment'=>'URL page content was grabbed from'),
	    'date_creation'  =>array('type'=>'datetime',     'label'=>'DateCreation',     'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>500),
		'tms'            =>array('type'=>'timestamp',    'label'=>'DateModification', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>501),
		//'date_valid'    =>array('type'=>'datetime',     'label'=>'DateValidation',     'enabled'=>1, 'visible'=>-1, 'position'=>502),
		//'fk_user_creat' =>array('type'=>'integer',      'label'=>'UserAuthor',       'enabled'=>1, 'visible'=>-1, 'notnull'=>true, 'position'=>510),
		//'fk_user_modif' =>array('type'=>'integer',      'label'=>'UserModif',        'enabled'=>1, 'visible'=>-1, 'position'=>511),
		//'fk_user_valid' =>array('type'=>'integer',      'label'=>'UserValidation',        'enabled'=>1, 'visible'=>-1, 'position'=>512),
		'import_key'     =>array('type'=>'varchar(14)',  'label'=>'ImportId',         'enabled'=>1, 'visible'=>-1,  'index'=>1,  'position'=>1000, 'notnull'=>-1),
	);
	// END MODULEBUILDER PROPERTIES


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id           Id object. If this is 0, the default page of website_id will be used, if not defined, the first one found.
	 * @param string $website_id   Web site id (page name must also be filled if this parameter is used)
	 * @param string $page         Page name (website id must also be filled if this parameter is used)
	 *
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $website_id = null, $page = null)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= " t.fk_website,";
		$sql .= ' t.type_container,';
		$sql .= " t.pageurl,";
		$sql .= " t.title,";
		$sql .= " t.description,";
		$sql .= " t.keywords,";
		$sql .= " t.htmlheader,";
		$sql .= " t.content,";
		$sql .= " t.lang,";
		$sql .= " t.fk_page,";
		$sql .= " t.status,";
		$sql .= " t.grabbed_from,";
		$sql .= " t.date_creation,";
		$sql .= " t.tms as date_modification";
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		//$sql .= ' WHERE entity IN ('.getEntity('website').')';       // entity is on website level
		$sql .= ' WHERE 1 = 1';
		if ($id > 0)
		{
			$sql .= ' AND t.rowid = ' . $id;
		}
		else
		{
			if (null !== $website_id) {
			    $sql .= " AND t.fk_website = '" . $this->db->escape($website_id) . "'";
			    if ($page) $sql .= " AND t.pageurl = '" . $this->db->escape($page) . "'";
			}
		}
        $sql .= $this->db->plimit(1);

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->fk_website = $obj->fk_website;
				$this->type_container = $obj->type_container;
				$this->pageurl = $obj->pageurl;
				$this->title = $obj->title;
				$this->description = $obj->description;
				$this->keywords = $obj->keywords;
				$this->htmlheader = $obj->htmlheader;
				$this->content = $obj->content;
				$this->lang = $obj->lang;
				$this->fk_page = $obj->fk_page;
				$this->status = $obj->status;
				$this->grabbed_from = $obj->grabbed_from;
				$this->date_creation = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->date_modification);
			}
			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return - 1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param  string      $websiteid    Web site
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($websiteid, $sortorder='', $sortfield='', $limit=0, $offset=0, array $filter = array(), $filtermode='AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records=array();

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= " t.fk_website,";
		$sql .= " t.type_container,";
		$sql .= " t.pageurl,";
		$sql .= " t.title,";
		$sql .= " t.description,";
		$sql .= " t.keywords,";
		$sql .= " t.htmlheader,";
		$sql .= " t.content,";
		$sql .= " t.lang,";
		$sql .= " t.fk_page,";
		$sql .= " t.status,";
		$sql .= " t.grabbed_from,";
		$sql .= " t.date_creation,";
		$sql .= " t.tms as date_modification";
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element. ' as t';
		$sql .= ' WHERE t.fk_website = '.$websiteid;
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key=='t.rowid' || $key=='t.fk_website') {
					$sqlwhere[] = $key . '='. $value;
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ' . implode(' '.$filtermode.' ', $sqlwhere);
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield,$sortorder);
		}
		if (!empty($limit)) {
            $sql .=  ' ' . $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql))
			{
				$record = new self($this->db);

				$record->id = $obj->rowid;
				$record->fk_website = $obj->fk_website;
				$record->type_container = $obj->type_container;
				$record->pageurl = $obj->pageurl;
				$record->title = $obj->title;
				$record->description = $obj->description;
				$record->keywords = $obj->keywords;
				$record->htmlheader = $obj->htmlheader;
				$record->content = $obj->content;
				$record->lang = $obj->lang;
				$record->fk_page = $obj->fk_page;
				$record->status = $obj->status;
				$record->grabbed_from = $obj->grabbed_from;
				$record->date_creation = $this->db->jdate($obj->date_creation);
				$record->date_modification = $this->db->jdate($obj->date_modification);
				//var_dump($record->id);
				$records[$record->id] = $record;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		$result = $this->deleteCommon($user, $trigger);

		if ($result > 0)
		{
			$websiteobj=new Website($this->db);
			$result = $websiteobj->fetch($this->fk_website);

			if ($result > 0)
			{
				global $dolibarr_main_data_root;
				$pathofwebsite=$dolibarr_main_data_root.'/website/'.$websiteobj->ref;

				$filealias=$pathofwebsite.'/'.$this->pageurl.'.php';
				$filetpl=$pathofwebsite.'/page'.$this->id.'.tpl.php';

				dol_delete_file($filealias);
				dol_delete_file($filetpl);
			}
		}

		return $result;
	}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param	User	$user				User making the clone
	 * @param 	int 	$fromid 			Id of object to clone
	 * @param	string	$newref				New ref/alias of page
	 * @param	string	$newlang			New language
	 * @param	int		$istranslation		1=New page is a translation of the cloned page.
	 * @param	int		$newwebsite			0=Same web site, 1=New web site
	 * @return 	mixed 						New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid, $newref, $newlang='', $istranslation=0, $newwebsite=0)
	{
		global $hookmanager, $langs;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		// Reset object
		$object->id = 0;

		// Clear fields
		$object->ref = $newref;
		$object->pageurl = $newref;
		$object->title = $langs->trans("CopyOf").' '.$object->title;
		if (! empty($newlang)) $object->lang=$newlang;
		if ($istranslation) $object->fk_page = $fromid;
		else $object->fk_page = 0;
		if (! empty($newwebsite)) $object->fk_website=$newwebsite;

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}

		// End
		if (!$error) {
			$this->db->commit();

			return $object;
		} else {
			$this->db->rollback();

			return -1;
		}
	}

	/**
	 *  Return a link to the user card (with optionaly the picto)
	 * 	Use this->id,this->lastname, this->firstname
	 *
	 *	@param	int		$withpicto			Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option				On what the link point to
     *  @param	integer	$notooltip			1=Disable tooltip
     *  @param	int		$maxlen				Max length of visible user name
     *  @param  string  $morecss            Add more css on link
	 *	@return	string						String with URL
	 */
	function getNomUrl($withpicto=0, $option='', $notooltip=0, $maxlen=24, $morecss='')
	{
		global $langs, $conf, $db;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;

        $result = '';
        $companylink = '';

        $label = '<u>' . $langs->trans("Page") . '</u>';
        $label.= '<div width="100%">';
        $label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

        $linkstart = '<a href="'.DOL_URL_ROOT.'/website/card.php?id='.$this->id.'"';
        $linkstart.= ($notooltip?'':' title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip'.($morecss?' '.$morecss:'').'"');
        $linkstart.= '>';
		$linkend='</a>';

		$linkstart = $linkend = '';

        if ($withpicto)
        {
            $result.=($linkstart.img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?'':'class="classfortooltip"')).$linkend);
        	if ($withpicto != 2) $result.=' ';
		}
		$result.= $linkstart . $this->ref . $linkend;
		return $result;
	}

	/**
	 *  Retourne le libelle du status d'un user (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->status,$mode);
	}

	/**
	 *  Renvoi le libelle d'un status donne
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string 			       	Label of status
	 */
	function LibStatut($status,$mode=0)
	{
		global $langs;

		if ($mode == 0)
		{
			$prefix='';
			if ($status == 1) return $langs->trans('Enabled');
			if ($status == 0) return $langs->trans('Disabled');
		}
		if ($mode == 1)
		{
			if ($status == 1) return $langs->trans('Enabled');
			if ($status == 0) return $langs->trans('Disabled');
		}
		if ($mode == 2)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		if ($mode == 3)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5');
		}
		if ($mode == 4)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		if ($mode == 5)
		{
			if ($status == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'),'statut5');
		}
	}


	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;

		$now=dol_now();

		$this->fk_website = '';
		$this->type_container = 'page';
		$this->pageurl = 'specimen';
		$this->title = 'My Page';
		$this->description = 'This is my page';
		$this->keywords = 'keyword1, keyword2';
		$this->htmlheader = '';
		$this->content = '<html><body>This is a html content</body></html>';
		$this->status = '';
		$this->grabbed_from = '';
		$this->date_creation = $now - (24 * 30 * 3600);
		$this->date_modification = $now - (24 * 7 * 3600);
	}

}
