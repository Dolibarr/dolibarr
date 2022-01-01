<?php
/* Copyright (C) 2007-2018  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 * \file    htdocs/website/class/websitepage.class.php
 * \ingroup website
 * \brief   File for the CRUD class of websitepage (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
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
	public $picto = 'file-code';


	/**
     * @var int ID
     */
	public $fk_website;

	public $pageurl;
	public $aliasalt;
	public $type_container;

	/**
	 * @var string title
	 */
	public $title;
	/**
	 * @var string description
	 */
	public $description;
	/**
	 * @var string image
	 */
	public $image;
	/**
	 * @var string keywords
	 */
	public $keywords;
	/**
	 * @var string language code ('en', 'fr', 'en-gb', ..)
	 */
	public $lang;

	public $htmlheader;
	public $content;
	public $grabbed_from;

	/**
	 * @var int Status
	 */
	public $status;

	/**
	 * @var integer|string date_creation
	 */
	public $date_creation;

	/**
	 * @var integer|string date_modification
	 */
	public $date_modification;

	/**
	 * @var string author_alias
	 */
	public $author_alias;


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;


	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
	public $fields = array(
	    'rowid'          =>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'index'=>1, 'position'=>1, 'comment'=>'Id'),
		'pageurl'        =>array('type'=>'varchar(16)', 'label'=>'WEBSITE_PAGENAME', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1, 'comment'=>'Ref/alias of page'),
		'aliasalt'       =>array('type'=>'varchar(255)', 'label'=>'AliasAlt', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'index'=>0, 'position'=>11, 'searchall'=>0, 'comment'=>'Alias alternative of page'),
		'type_container' =>array('type'=>'varchar(16)', 'label'=>'Type', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'index'=>0, 'position'=>12, 'comment'=>'Type of container'),
		'title'          =>array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>1, 'visible'=>1, 'position'=>30, 'searchall'=>1, 'help'=>'UseTextBetween5And70Chars'),
	    'description'    =>array('type'=>'varchar(255)', 'label'=>'Description', 'enabled'=>1, 'visible'=>1, 'position'=>30, 'searchall'=>1),
		'image'          =>array('type'=>'varchar(255)', 'label'=>'Image', 'enabled'=>1, 'visible'=>1, 'position'=>32, 'searchall'=>0, 'help'=>'Relative path of media. Used if Type is "blogpost"'),
		'keywords'       =>array('type'=>'varchar(255)', 'label'=>'Keywords', 'enabled'=>1, 'visible'=>1, 'position'=>45, 'searchall'=>0),
		'lang'           =>array('type'=>'varchar(6)', 'label'=>'Lang', 'enabled'=>1, 'notnull'=>-1, 'visible'=>1, 'position'=>45, 'searchall'=>0),
		//'status'        =>array('type'=>'integer',      'label'=>'Status',           'enabled'=>1, 'visible'=>1,  'index'=>true,   'position'=>1000),
	    'fk_website'     =>array('type'=>'integer', 'label'=>'WebsiteId', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'position'=>40, 'searchall'=>0, 'foreignkey'=>'websitepage.rowid'),
	    'fk_page'        =>array('type'=>'integer', 'label'=>'ParentPageId', 'enabled'=>1, 'visible'=>1, 'notnull'=>-1, 'position'=>45, 'searchall'=>0, 'foreignkey'=>'website.rowid'),
	    'htmlheader'     =>array('type'=>'text', 'label'=>'HtmlHeader', 'enabled'=>1, 'visible'=>0, 'position'=>50, 'searchall'=>0),
	    'content'        =>array('type'=>'mediumtext', 'label'=>'Content', 'enabled'=>1, 'visible'=>0, 'position'=>51, 'searchall'=>0),
		'grabbed_from'   =>array('type'=>'varchar(255)', 'label'=>'GrabbedFrom', 'enabled'=>1, 'visible'=>1, 'index'=>1, 'position'=>400, 'comment'=>'URL page content was grabbed from'),
	    'date_creation'  =>array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>500),
		'tms'            =>array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>501),
		//'date_valid'    =>array('type'=>'datetime',     'label'=>'DateValidation',     'enabled'=>1, 'visible'=>-1, 'position'=>502),
		'fk_user_creat'  =>array('type'=>'integer', 'label'=>'UserAuthor', 'enabled'=>1, 'visible'=>-1, 'notnull'=>true, 'position'=>510),
		'author_alias'   =>array('type'=>'varchar(64)', 'label'=>'AuthorAlias', 'enabled'=>1, 'visible'=>-1, 'index'=>0, 'position'=>511, 'comment'=>'Author alias'),
		'fk_user_modif'  =>array('type'=>'integer', 'label'=>'UserModif', 'enabled'=>1, 'visible'=>-1, 'position'=>512),
		//'fk_user_valid' =>array('type'=>'integer',      'label'=>'UserValidation',        'enabled'=>1, 'visible'=>-1, 'position'=>512),
		'import_key'     =>array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-1, 'index'=>1, 'position'=>1000, 'notnull'=>-1),
	);
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	/**
	 * @var int    Name of subtable line
	 */
	//public $table_element_line = 'mymodule_myobjectline';

	/**
	 * @var int 	Field with ID of parent key if this field has a parent or for child tables
	 */
	public $fk_element = 'fk_website_page';

	/**
	 * @var int    Name of subtable class that manage subtable lines
	 */
	//public $class_element_line = 'MyObjectline';

	/**
	 * @var array	List of child tables. To test if we can delete object.
	 */
	//protected $childtables=array();

	/**
	 * @var array	List of child tables. To know object to delete on cascade.
	 */
	protected $childtablesoncascade = array('categorie_website_page');



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
		$this->description = dol_trunc($this->description, 255, 'right', 'utf-8', 1);
		$this->keywords = dol_trunc($this->keywords, 255, 'right', 'utf-8', 1);
		if ($this->aliasalt) $this->aliasalt = ','.preg_replace('/,+$/', '', preg_replace('/^,+/', '', $this->aliasalt)).','; // content in database must be ',xxx,...,yyy,'

		// Remove spaces and be sure we have main language only
		$this->lang = preg_replace('/[_-].*$/', '', trim($this->lang)); // en_US or en-US -> en

		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int       $id             Id object.
	 *                                  - If this is 0, the value into $page will be used. If not found or $page not defined, the default page of website_id will be used or the first page found if not set.
	 *                                  - If value is < 0, we must exclude this ID.
	 * @param string    $website_id     Web site id (page name must also be filled if this parameter is used)
	 * @param string    $page           Page name (website id must also be filled if this parameter is used). Exemple 'myaliaspage' or 'fr/myaliaspage'
	 * @param string    $aliasalt       Alternative alias to search page (slow)
	 *
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $website_id = null, $page = null, $aliasalt = null)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= " t.fk_website,";
		$sql .= ' t.type_container,';
		$sql .= " t.pageurl,";
		$sql .= " t.aliasalt,";
		$sql .= " t.title,";
		$sql .= " t.description,";
		$sql .= " t.image,";
		$sql .= " t.keywords,";
		$sql .= " t.htmlheader,";
		$sql .= " t.content,";
		$sql .= " t.lang,";
		$sql .= " t.fk_page,";
		$sql .= " t.status,";
		$sql .= " t.grabbed_from,";
		$sql .= " t.date_creation,";
		$sql .= " t.tms as date_modification,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.author_alias,";
		$sql .= " t.fk_user_modif,";
		$sql .= " t.import_key";
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		//$sql .= ' WHERE entity IN ('.getEntity('website').')';       // entity is on website level
		$sql .= ' WHERE 1 = 1';
		if ($id > 0)
		{
			$sql .= ' AND t.rowid = '.$id;
		}
		else
		{
			if ($id < 0) $sql .= ' AND t.rowid <> '.abs($id);
			if (null !== $website_id) {
			    $sql .= " AND t.fk_website = '".$this->db->escape($website_id)."'";
			    if ($page) {
			    	$pagetouse = $page;
			    	$langtouse = '';
			    	$tmppage = explode('/', $page);
			    	if (!empty($tmppage[1])) {
			    		$pagetouse = $tmppage[1];
			    		if (strlen($tmppage[0])) $langtouse = $tmppage[0];
			    	}
			    	$sql .= " AND t.pageurl = '".$this->db->escape($pagetouse)."'";
			    	if ($langtouse) $sql .= " AND t.lang = '".$this->db->escape($langtouse)."'";
			    }
			    if ($aliasalt)	$sql .= " AND (t.aliasalt LIKE '%,".$this->db->escape($aliasalt).",%' OR t.aliasalt LIKE '%, ".$this->db->escape($aliasalt).",%')";
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
				$this->ref = $obj->pageurl;
				$this->aliasalt = preg_replace('/,+$/', '', preg_replace('/^,+/', '', $obj->aliasalt));

				$this->title = $obj->title;
				$this->description = $obj->description;
				$this->image = $obj->image;
				$this->keywords = $obj->keywords;
				$this->htmlheader = $obj->htmlheader;
				$this->content = $obj->content;
				$this->lang = $obj->lang;
				$this->fk_page = $obj->fk_page;
				$this->status = $obj->status;
				$this->grabbed_from = $obj->grabbed_from;
				$this->date_creation = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->author_alias = $obj->author_alias;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->import_key = $obj->import_key;
			}
			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Return array of all web site pages.
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
	public function fetchAll($websiteid, $sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= " t.fk_website,";
		$sql .= " t.type_container,";
		$sql .= " t.pageurl,";
		$sql .= " t.aliasalt,";
		$sql .= " t.title,";
		$sql .= " t.description,";
		$sql .= " t.image,";
		$sql .= " t.keywords,";
		$sql .= " t.htmlheader,";
		$sql .= " t.content,";
		$sql .= " t.lang,";
		$sql .= " t.fk_page,";
		$sql .= " t.status,";
		$sql .= " t.grabbed_from,";
		$sql .= " t.date_creation,";
		$sql .= " t.tms as date_modification,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.author_alias,";
		$sql .= " t.fk_user_modif,";
		$sql .= " t.import_key";
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.fk_website = '.$websiteid;
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid' || $key == 't.fk_website' || $key == 'status') {
					$sqlwhere[] = $key.'='.$value;
				} elseif ($key == 'type_container') {
					$sqlwhere[] = $key."='".$value."'";
				} elseif ($key == 'lang' || $key == 't.lang') {
					$listoflang = array();
					$foundnull = 0;
					foreach(explode(',', $value) as $tmpvalue) {
						if ($tmpvalue == 'null') {
							$foundnull++;
							continue;
						}
						$listoflang[] = "'".$this->db->escape(substr(str_replace("'", '', $tmpvalue), 0, 2))."'";
					}
					$stringtouse = $key." IN (".join(',', $listoflang).")";
					if ($foundnull) $stringtouse = '('.$stringtouse.' OR '.$key.' IS NULL)';
					$sqlwhere[] = $stringtouse;
				} else {
					$sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' '.$this->db->plimit($limit, $offset);
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
				$record->aliasalt = preg_replace('/,+$/', '', preg_replace('/^,+/', '', $obj->aliasalt));
				$record->title = $obj->title;
				$record->description = $obj->description;
				$record->image = $obj->image;
				$record->keywords = $obj->keywords;
				$record->htmlheader = $obj->htmlheader;
				$record->content = $obj->content;
				$record->lang = $obj->lang;
				$record->fk_page = $obj->fk_page;
				$record->status = $obj->status;
				$record->grabbed_from = $obj->grabbed_from;
				$record->date_creation = $this->db->jdate($obj->date_creation);
				$record->date_modification = $this->db->jdate($obj->date_modification);
				$record->fk_user_creat = $obj->fk_user_creat;
				$record->author_alias = $obj->author_alias;
				$record->fk_user_modif = $obj->fk_user_modif;
				$record->import_key = $obj->import_key;
				//var_dump($record->id);
				$records[$record->id] = $record;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->error = 'Error '.$this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Count objects in the database.
	 *
	 * @param  string      $websiteid    Web site
	 * @param  array       $filter       Filter array
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return int         		         int <0 if KO, array of pages if OK
	 */
	public function countAll($websiteid, array $filter = array(), $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$result = 0;

		$sql = 'SELECT COUNT(t.rowid) as nb';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.fk_website = '.$websiteid;
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid' || $key == 't.fk_website') {
					$sqlwhere[] = $key.'='.$value;
				} elseif ($key == 'lang' || $key == 't.lang') {
					$sqlwhere[] = $key." = '".$this->db->escape(substr($value, 0, 2))."'";
				} else {
					$sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$result = $obj->nb;
			}

			$this->db->free($resql);

			return $result;
		} else {
			$this->error = 'Error '.$this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

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
		$this->description = dol_trunc($this->description, 255, 'right', 'utf-8', 1);
		$this->keywords = dol_trunc($this->keywords, 255, 'right', 'utf-8', 1);
		if ($this->aliasalt) $this->aliasalt = ','.preg_replace('/,+$/', '', preg_replace('/^,+/', '', $this->aliasalt)).','; // content in database must be ',xxx,...,yyy,'

		// Remove spaces and be sure we have main language only
		$this->lang = preg_replace('/[_-].*$/', '', trim($this->lang)); // en_US or en-US -> en

		if ($this->fk_page > 0) {
			if (empty($this->lang)) {
				$this->error = "ErrorLanguageMandatoryIfPageSetAsTranslationOfAnother";
				return -1;
			}
			$tmppage = new WebsitePage($this->db);
			$tmppage->fetch($this->fk_page);
			if ($tmppage->lang == $this->lang) {
				$this->error = "ErrorLanguageOfTranslatedPageIsSameThanThisPage";
				return -1;
			}
		}

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
		$error = 0;

		// Delete all child tables
		if (!$error) {
			foreach ($this->childtablesoncascade as $table)
			{
				$sql = "DELETE FROM ".MAIN_DB_PREFIX.$table;
				$sql .= " WHERE fk_website_page = ".(int) $this->id;

				$result = $this->db->query($sql);
				if (!$result) {
					$error++;
					$this->errors[] = $this->db->lasterror();
					break;
				}
			}
		}

		if (!$error) {
			$result = $this->deleteCommon($user, $trigger);
			if ($result <= 0)
			{
				$error++;
			}
		}

		if (!$error)
		{
			$websiteobj = new Website($this->db);
			$result = $websiteobj->fetch($this->fk_website);

			if ($result > 0)
			{
				global $dolibarr_main_data_root;
				$pathofwebsite = $dolibarr_main_data_root.'/website/'.$websiteobj->ref;

				$filealias = $pathofwebsite.'/'.$this->pageurl.'.php';
				$filetpl = $pathofwebsite.'/page'.$this->id.'.tpl.php';

				dol_delete_file($filealias);
				dol_delete_file($filetpl);
			} else {
				$this->error = $websiteobj->error;
				$this->errors = $websiteobj->errors;
			}
		}

		if (! $error) {
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param	User	$user				User making the clone
	 * @param 	int 	$fromid 			Id of object to clone
	 * @param	string	$newref				New ref/alias of page
	 * @param	string	$newlang			New language
	 * @param	int		$istranslation		1=New page is a translation of the cloned page.
	 * @param	int		$newwebsite			0=Same web site, >0=Id of new website
	 * @param	string	$newtitle			New title
	 * @return 	mixed 						New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid, $newref, $newlang = '', $istranslation = 0, $newwebsite = 0, $newtitle = '')
	{
		global $hookmanager, $langs;

		$now = dol_now();
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		// Clean parameters
		if (empty($newref) && !empty($newtitle)) {
			$newref = strtolower(dol_sanitizeFileName(preg_replace('/\s+/', '-', $newtitle), '-', 1));
		}

		// Check parameters
		if (empty($newref)) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WEBSITE_TITLE"));
			return -1;
		}

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		// Reset object
		$object->id = 0;

		// Clear fields
		$object->ref = $newref;
		$object->pageurl = $newref;
		$object->aliasalt = '';
		$object->fk_user_creat = $user->id;
		$object->author_alias = '';
		$object->date_creation = $now;
		$object->title = ($newtitle == '1' ? $object->title : ($newtitle ? $newtitle : $object->title));
		if (!empty($newlang)) $object->lang = $newlang;
		if ($istranslation) $object->fk_page = $fromid;
		else $object->fk_page = 0;
		if (!empty($newwebsite)) $object->fk_website = $newwebsite;
		$object->import_key = '';

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
		}

		unset($object->context['createfromclone']);

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
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $maxlen = 24, $morecss = '')
	{
		global $langs, $conf, $db;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;

        $result = '';

        $label = '<u>'.$langs->trans("Page").'</u>';
        $label .= '<br>';
        $label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref.'<br>';
        $label .= '<b>'.$langs->trans('ID').':</b> '.$this->id.'<br>';
        $label .= '<b>'.$langs->trans('Title').':</b> '.$this->title.'<br>';
        $label .= '<b>'.$langs->trans('Language').':</b> '.$this->lang;

        $url = DOL_URL_ROOT.'/website/index.php?websiteid='.$this->fk_website.'&pageid='.$this->id;

        $linkclose = '';
        if (empty($notooltip))
        {
        	if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
        	{
        		$label = $langs->trans("ShowMyObject");
        		$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
        	}
        	$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
        	$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
        }
        else $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');

        $linkstart = '<a href="'.$url.'"';
        $linkstart .= $linkclose.'>';
		$linkend = '</a>';

		//$linkstart = $linkend = '';

		$result .= $linkstart;
		if ($withpicto) $result .= img_picto(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		if ($withpicto != 2) $result .= $this->ref;
		$result .= $linkend;

		return $result;
	}

	/**
	 *  Retourne le libelle du status d'un user (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi le libelle d'un status donne
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string 			       	Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
        // phpcs:enable
		global $langs;

		if (empty($this->labelStatus) || empty($this->labelStatusShort))
		{
			global $langs;
			//$langs->load("mymodule");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->trans('Disabled');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->trans('Enabled');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->trans('Disabled');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->trans('Enabled');
		}

		$statusType = 'status5';
		if ($status == self::STATUS_VALIDATED) $statusType = 'status4';

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}


	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		global $user;

		$this->id = 0;

		$now = dol_now();

		$this->fk_website = '';
		$this->type_container = 'page';
		$this->pageurl = 'specimen';
		$this->aliasalt = 'specimenalt';
		$this->title = 'My Page';
		$this->description = 'This is my page';
		$this->image = '';
		$this->keywords = 'keyword1, keyword2';
		$this->htmlheader = '';
		$this->content = '<html><body>This is a html content</body></html>';
		$this->status = '';
		$this->grabbed_from = '';
		$this->date_creation = $now - (24 * 30 * 3600);
		$this->date_modification = $now - (24 * 7 * 3600);
		$this->fk_user_creat = $user->id;
		$this->author_alias = 'mypublicpseudo';
	}
}
