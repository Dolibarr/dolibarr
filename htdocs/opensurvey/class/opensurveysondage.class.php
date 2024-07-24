<?php
/* Copyright (C) 2013-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014      Marcos García	    <marcosgdf@gmail.com>
 * Copyright (C) 2020		Frédéric France		<frederic.france@netlogic.fr>
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
 *  \file       htdocs/opensurvey/class/opensurveysondage.class.php
 *  \ingroup    opensurvey
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *				Initialy built by build_class_from_table on 2013-03-10 00:32
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";


/**
 *	Put here description of your class
 */
class Opensurveysondage extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'opensurvey_sondage';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'opensurvey_sondage';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'poll';


	/**
	 * @var string Description
	 */
	public $description;

	/**
	 * @var string Date last modification (same as tms)
	 */
	public $date_m;


	/**
	 *  'type' field format:
	 *  	'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *  	'select' (list of values are in 'options'),
	 *  	'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]',
	 *  	'chkbxlst:...',
	 *  	'varchar(x)',
	 *  	'text', 'text:none', 'html',
	 *   	'double(24,8)', 'real', 'price',
	 *  	'date', 'datetime', 'timestamp', 'duration',
	 *  	'boolean', 'checkbox', 'radio', 'array',
	 *  	'mail', 'phone', 'url', 'password', 'ip'
	 *		Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalInt("MY_SETUP_PARAM")' or 'isModEnabled("multicurrency")' ...)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'alwayseditable' says if field can be modified also when status is not draft ('1' or '0')
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' and 'helplist' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if need to validate with $this->validateField()
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'id_sondage' => array('type'=>'varchar(16)', 'label'=>'Idsondage', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>-1,),
		'commentaires' => array('type'=>'mediumtext', 'label'=>'Commentaires', 'enabled'=>'1', 'position'=>15, 'notnull'=>0, 'visible'=>-1,),
		'mail_admin' => array('type'=>'varchar(128)', 'label'=>'Mailadmin', 'enabled'=>'1', 'position'=>20, 'notnull'=>0, 'visible'=>-1,),
		'nom_admin' => array('type'=>'varchar(64)', 'label'=>'Nomadmin', 'enabled'=>'1', 'position'=>25, 'notnull'=>0, 'visible'=>-1,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>-2, 'css'=>'maxwidth500 widthcentpercentminusxx', 'csslist'=>'tdoverflowmax150',),
		'title' => array('type'=>'mediumtext', 'label'=>'Titre', 'enabled'=>'1', 'position'=>35, 'notnull'=>1, 'visible'=>-1,),
		'date_fin' => array('type'=>'datetime', 'label'=>'Datefin', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>-1,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>500, 'notnull'=>0, 'visible'=>-1,),
		'format' => array('type'=>'varchar(2)', 'label'=>'Format', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>-1,),
		'mailsonde' => array('type'=>'integer', 'label'=>'Mailsonde', 'enabled'=>'1', 'position'=>55, 'notnull'=>1, 'visible'=>-1,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>60, 'notnull'=>1, 'visible'=>-1,),
		'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>65, 'notnull'=>1, 'visible'=>-2, 'default'=>'1', 'index'=>1,),
		'allow_comments' => array('type'=>'integer', 'label'=>'Allowcomments', 'enabled'=>'1', 'position'=>70, 'notnull'=>1, 'visible'=>-1,),
		'allow_spy' => array('type'=>'integer', 'label'=>'Allowspy', 'enabled'=>'1', 'position'=>75, 'notnull'=>1, 'visible'=>-1,),
		'sujet' => array('type'=>'mediumtext', 'label'=>'Sujet', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>-1,),
		'id_sondage_admin' => array('type'=>'char(24)', 'label'=>'Idsondageadmin', 'enabled'=>'1', 'position'=>85, 'notnull'=>0, 'visible'=>-1,),
	);
	public $id_sondage;
	/**
	 * @var string		Description
	 * @deprecated 		Use $description instead
	 */
	public $commentaires;
	public $mail_admin;
	public $nom_admin;
	public $fk_user_creat;
	public $title;
	public $date_fin = '';
	public $status;
	public $format;
	public $mailsonde;
	public $tms;
	public $entity;
	/**
	 * @var int		Allow comments on this poll
	 */
	public $allow_comments;

	/**
	 * @var int		Allow users see others vote
	 */
	public $allow_spy;
	public $sujet;
	public $id_sondage_admin;
	// END MODULEBUILDER PROPERTIES


	/**
	 * Draft status (not used)
	 */
	const STATUS_DRAFT = 0;
	/**
	 * Validated/Opened status
	 */
	const STATUS_VALIDATED = 1;
	/**
	 * Closed
	 */
	const STATUS_CLOSED = 2;


	/**
	 *  Constructor
	 *
	 *  @param	DoliDb		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *  Create object into database
	 *
	 *  @param	User    $user        User that creates
	 *  @param  int     $notrigger   0=launch triggers after, 1=disable triggers
	 *  @return int                  Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		// Clean parameters
		$this->cleanParameters();

		// Check parameters
		if (!$this->date_fin > 0) {
			$this->error = 'BadValueForEndDate';
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -1;
		}

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."opensurvey_sondage(";
		$sql .= "id_sondage,";
		$sql .= "commentaires,";
		$sql .= "fk_user_creat,";
		$sql .= "titre,";
		$sql .= "date_fin,";
		$sql .= "status,";
		$sql .= "format,";
		$sql .= "mailsonde,";
		$sql .= "allow_comments,";
		$sql .= "allow_spy,";
		$sql .= "sujet,";
		$sql .= "entity";
		$sql .= ") VALUES (";
		$sql .= "'".$this->db->escape($this->id_sondage)."',";
		$sql .= " ".(empty($this->description) ? 'NULL' : "'".$this->db->escape($this->description)."'").",";
		$sql .= " ".(int) $user->id.",";
		$sql .= " '".$this->db->escape($this->title)."',";
		$sql .= " '".$this->db->idate($this->date_fin)."',";
		$sql .= " ".(int) $this->status.",";
		$sql .= " '".$this->db->escape($this->format)."',";
		$sql .= " ".((int) $this->mailsonde).",";
		$sql .= " ".((int) $this->allow_comments).",";
		$sql .= " ".((int) $this->allow_spy).",";
		$sql .= " '".$this->db->escape($this->sujet)."',";
		$sql .= " ".((int) $conf->entity);
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('OPENSURVEY_CREATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}


	/**
	 *  Load object in memory from the database
	 *
	 *  @param	int		$id    				Id object
	 *  @param	string	$numsurvey			Ref of survey (admin or not)
	 *  @return int          				Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id, $numsurvey = '')
	{
		$sql = "SELECT";
		$sql .= " t.id_sondage,";
		$sql .= " t.titre as title,";
		$sql .= " t.commentaires as description,";
		$sql .= " t.mail_admin,";
		$sql .= " t.nom_admin,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.date_fin,";
		$sql .= " t.status,";
		$sql .= " t.format,";
		$sql .= " t.mailsonde,";
		$sql .= " t.allow_comments,";
		$sql .= " t.allow_spy,";
		$sql .= " t.sujet,";
		$sql .= " t.tms";
		$sql .= " FROM ".MAIN_DB_PREFIX."opensurvey_sondage as t";
		$sql .= " WHERE t.id_sondage = '".$this->db->escape($id ? $id : $numsurvey)."'";

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id_sondage = $obj->id_sondage;
				$this->ref = $this->id_sondage; //For compatibility

				$this->description = $obj->description;
				$this->mail_admin = $obj->mail_admin;
				$this->nom_admin = $obj->nom_admin;
				$this->title = $obj->title;
				$this->date_fin = $this->db->jdate($obj->date_fin);
				$this->status = $obj->status;
				$this->format = $obj->format;
				$this->mailsonde = $obj->mailsonde;
				$this->allow_comments = $obj->allow_comments;
				$this->allow_spy = $obj->allow_spy;
				$this->sujet = $obj->sujet;
				$this->fk_user_creat = $obj->fk_user_creat;

				$this->date_m = $this->db->jdate(!empty($obj->tms) ? $obj->tms : "");
				$ret = 1;
			} else {
				$sondage = ($id ? 'id='.$id : 'sondageid='.$numsurvey);
				$this->error = 'Fetch no poll found for '.$sondage;
				dol_syslog($this->error, LOG_ERR);
				$ret = 0;
			}

			$this->db->free($resql);
		} else {
			$this->error = "Error ".$this->db->lasterror();
			$ret = -1;
		}

		return $ret;
	}


	/**
	 *  Update object into database
	 *
	 *  @param	User    $user        User that modifies
	 *  @param  int     $notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return int     		   	 Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		$error = 0;

		// Clean parameters
		$this->cleanParameters();

		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."opensurvey_sondage SET";
		$sql .= " id_sondage=".(isset($this->id_sondage) ? "'".$this->db->escape($this->id_sondage)."'" : "null").",";
		$sql .= " commentaires=".(isset($this->description) ? "'".$this->db->escape($this->description)."'" : "null").",";
		$sql .= " mail_admin=".(isset($this->mail_admin) ? "'".$this->db->escape($this->mail_admin)."'" : "null").",";
		$sql .= " nom_admin=".(isset($this->nom_admin) ? "'".$this->db->escape($this->nom_admin)."'" : "null").",";
		$sql .= " titre=".(isset($this->title) ? "'".$this->db->escape($this->title)."'" : "null").",";
		$sql .= " date_fin=".(dol_strlen($this->date_fin) != 0 ? "'".$this->db->idate($this->date_fin)."'" : 'null').",";
		$sql .= " status=".(isset($this->status) ? "'".$this->db->escape($this->status)."'" : "null").",";
		$sql .= " format=".(isset($this->format) ? "'".$this->db->escape($this->format)."'" : "null").",";
		$sql .= " mailsonde=".(isset($this->mailsonde) ? ((int) $this->mailsonde) : "null").",";
		$sql .= " allow_comments=".((int) $this->allow_comments).",";
		$sql .= " allow_spy=".((int) $this->allow_spy);
		$sql .= " WHERE id_sondage='".$this->db->escape($this->id_sondage)."'";

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('OPENSURVEY_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *  Delete object in database
	 *
	 *	@param  User	$user        		User that deletes
	 *  @param  int		$notrigger	 		0=launch triggers after, 1=disable triggers
	 *  @param	string	$numsondage			Num sondage admin to delete
	 *  @return	int					 		Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0, $numsondage = '')
	{
		global $conf, $langs;
		$error = 0;

		if (empty($numsondage)) {
			$numsondage = $this->id_sondage;
		}

		$this->db->begin();

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('OPENSURVEY_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX."opensurvey_comments WHERE id_sondage = '".$this->db->escape($numsondage)."'";
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX."opensurvey_user_studs WHERE id_sondage = '".$this->db->escape($numsondage)."'";
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."opensurvey_sondage";
			$sql .= " WHERE id_sondage = '".$this->db->escape($numsondage)."'";

			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = "Error ".$this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 * getTooltipContentArray
	 *
	 * @param array $params ex option, infologin
	 * @since v18
	 * @return array
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs;

		$langs->load('opensurvey');

		$datas = [];
		$datas['picto'] = img_picto('', $this->picto).' <u>'.$langs->trans("ShowSurvey").'</u>';
		$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		$datas['title'] = '<br><b>'.$langs->trans('Title').':</b> '.$this->title;

		return $datas;
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param	int  	$notooltip					1=Disable tooltip
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$url = DOL_URL_ROOT.'/opensurvey/card.php?id='.$this->id;

		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
			$add_save_lastsearch_values = 1;
		}
		if ($add_save_lastsearch_values) {
			$url .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowMyObject");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;

		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return array of lines
	 *
	 * @return 	int		Return integer <0 if KO, >0 if OK
	 */
	public function fetch_lines()
	{
		// phpcs:enable
		$this->lines = array();

		$sql = "SELECT id_users, nom as name, reponses";
		$sql .= " FROM ".MAIN_DB_PREFIX."opensurvey_user_studs";
		$sql .= " WHERE id_sondage = '".$this->db->escape($this->id_sondage)."'";

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$tmp = array('id_users'=>$obj->id_users, 'nom'=>$obj->name, 'reponses'=>$obj->reponses);

				$this->lines[] = $tmp;
				$i++;
			}
		} else {
			dol_print_error($this->db);
		}

		return count($this->lines);
	}

	/**
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;

		$this->id_sondage = 'a12d5g';
		$this->description = 'Description of the specimen survey';
		$this->mail_admin = 'email@email.com';
		$this->nom_admin = 'surveyadmin';
		$this->title = 'This is a specimen survey';
		$this->date_fin = dol_now() + 3600 * 24 * 10;
		$this->status = 1;
		$this->format = 'classic';
		$this->mailsonde = 0;
	}

	/**
	 * Returns all comments for the current opensurvey poll
	 *
	 * @return Object[]
	 */
	public function getComments()
	{
		$comments = array();

		$sql = 'SELECT id_comment, usercomment, comment';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'opensurvey_comments';
		$sql .= " WHERE id_sondage='".$this->db->escape($this->id_sondage)."'";
		$sql .= " ORDER BY id_comment";
		$resql = $this->db->query($sql);

		if ($resql) {
			$num_rows = $this->db->num_rows($resql);

			if ($num_rows > 0) {
				while ($obj = $this->db->fetch_object($resql)) {
					$comments[] = $obj;
				}
			}
		}

		return $comments;
	}

	/**
	 * Adds a comment to the poll
	 *
	 * @param string $comment Comment content
	 * @param string $comment_user Comment author
	 * @param string $user_ip Comment author IP
	 * @return boolean False in case of the query fails, true if it was successful
	 */
	public function addComment($comment, $comment_user, $user_ip = '')
	{
		$now = dol_now();
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."opensurvey_comments (id_sondage, comment, usercomment, date_creation, ip)";
		$sql .= " VALUES ('".$this->db->escape($this->id_sondage)."','".$this->db->escape($comment)."','".$this->db->escape($comment_user)."','".$this->db->idate($now)."'".($user_ip ? ",'".$this->db->escape($user_ip)."'" : '').")";
		$resql = $this->db->query($sql);

		if (!$resql) {
			return false;
		}

		return true;
	}

	/**
	 * Deletes a comment of the poll
	 *
	 * @param int $id_comment Id of the comment
	 * @return boolean False in case of the query fails, true if it was successful
	 */
	public function deleteComment($id_comment)
	{
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'opensurvey_comments WHERE id_comment = '.((int) $id_comment).' AND id_sondage = "'.$this->db->escape($this->id_sondage).'"';
		$resql = $this->db->query($sql);

		if (!$resql) {
			return false;
		}

		return true;
	}

	/**
	 * Cleans all the class variables before doing an update or an insert
	 *
	 * @return void
	 */
	private function cleanParameters()
	{
		$this->id_sondage = trim($this->id_sondage);
		$this->description = trim($this->description);
		$this->mail_admin = trim($this->mail_admin);
		$this->nom_admin = trim($this->nom_admin);
		$this->title = trim($this->title);
		$this->status = (int) $this->status;
		$this->format = trim($this->format);
		$this->mailsonde = ($this->mailsonde ? 1 : 0);
		$this->allow_comments = ($this->allow_comments ? 1 : 0);
		$this->allow_spy = ($this->allow_spy ? 1 : 0);
		$this->sujet = trim($this->sujet);
	}


	/**
	 *	Return status label of Order
	 *
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *	@return string              	Label if status
	 */
	public function getLibStatut($mode)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return label of status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string					Label of status
	 */
	public function LibStatut($status, $mode)
	{
		// phpcs:enable
		global $langs, $conf;

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("mymodule");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Opened');
			$this->labelStatus[self::STATUS_CLOSED] = $langs->transnoentitiesnoconv('Closed');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Opened');
			$this->labelStatusShort[self::STATUS_CLOSED] = $langs->transnoentitiesnoconv('Closed');
		}

		$statusType = 'status'.$status;
		if ($status == self::STATUS_VALIDATED) {
			if (0) {
				$statusType = 'status1';
			} else {
				$statusType = 'status4';
			}
		}
		if ($status == self::STATUS_CLOSED) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}


	/**
	 *	Return number of votes done for this survey.
	 *
	 *	@return     int			Number of votes
	 */
	public function countVotes()
	{
		$result = 0;

		$sql = " SELECT COUNT(id_users) as nb FROM ".MAIN_DB_PREFIX."opensurvey_user_studs";
		$sql .= " WHERE id_sondage = '".$this->db->escape($this->ref)."'";

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$result = $obj->nb;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
		}

		return $result;
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= ' WHERE t.entity IN ('.getEntity($this->element).')';
		} else {
			$sql .= ' WHERE 1 = 1';
		}
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key." = ".((int) $value);
				} elseif (array_key_exists($key, $this->fields) && in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
					$sqlwhere[] = $key." = '".$this->db->idate($value)."'";
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} elseif (strpos($value, '%') === false) {
					$sqlwhere[] = $key." IN (".$this->db->sanitize($this->db->escape($value)).")";
				} else {
					$sqlwhere[] = $key." LIKE '%".$this->db->escape($value)."%'";
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$this->db->escape($filtermode).' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id_sondage] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}
}
