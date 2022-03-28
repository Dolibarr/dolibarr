<?php
/* Copyright (C) 2005 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2015 Marcos García       <marcosgdf@gmail.com>
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
 *      \file       htdocs/bookmarks/class/bookmark.class.php
 *      \ingroup    bookmark
 *      \brief      File of class to manage bookmarks
 */


/**
 *		Class to manage bookmarks
 */
class Bookmark extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'bookmark';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'bookmark';

	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'bookmark';

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var int User ID
	 */
	public $fk_user;

	/**
	 * Date creation record (datec)
	 *
	 * @var integer
	 */
	public $datec;

	/**
	 * @var string url
	 */
	public $url;

	public $target; // 0=replace, 1=new window

	/**
	 * @var string title
	 */
	public $title;

	/**
	 * @var int position of bookmark
	 */
	public $position;

	/**
	 * @var string favicon
	 */
	public $favicon;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *    Directs the bookmark
	 *
	 *    @param    int		$id		Bookmark Id Loader
	 *    @return	int				<0 if KO, >0 if OK
	 */
	public function fetch($id)
	{
		global $conf;

		$sql = "SELECT rowid, fk_user, dateb as datec, url, target,";
		$sql .= " title, position, favicon";
		$sql .= " FROM ".MAIN_DB_PREFIX."bookmark";
		$sql .= " WHERE rowid = ".((int) $id);
		$sql .= " AND entity = ".$conf->entity;

		dol_syslog("Bookmark::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);

			$this->id = $obj->rowid;
			$this->ref = $obj->rowid;

			$this->fk_user = $obj->fk_user;
			$this->datec   = $this->db->jdate($obj->datec);
			$this->url     = $obj->url;
			$this->target  = $obj->target;
			$this->title   = $obj->title;
			$this->position = $obj->position;
			$this->favicon = $obj->favicon;

			$this->db->free($resql);
			return $this->id;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *      Insert bookmark into database
	 *
	 *      @return     int     <0 si ko, rowid du bookmark cree si ok
	 */
	public function create()
	{
		global $conf;

		// Clean parameters
		$this->url = trim($this->url);
		$this->title = trim($this->title);
		if (empty($this->position)) {
			$this->position = 0;
		}

		$now = dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."bookmark (fk_user,dateb,url,target";
		$sql .= ",title,favicon,position";
		$sql .= ",entity";
		$sql .= ") VALUES (";
		$sql .= ($this->fk_user > 0 ? $this->fk_user : "0").",";
		$sql .= " '".$this->db->idate($now)."',";
		$sql .= " '".$this->db->escape($this->url)."', '".$this->db->escape($this->target)."',";
		$sql .= " '".$this->db->escape($this->title)."', '".$this->db->escape($this->favicon)."', ".(int) $this->position;
		$sql .= ", ".(int) $conf->entity;
		$sql .= ")";

		dol_syslog("Bookmark::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."bookmark");
			if ($id > 0) {
				$this->id = $id;
				$this->db->commit();
				return $id;
			} else {
				$this->error = $this->db->lasterror();
				$this->errno = $this->db->lasterrno();
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->errno = $this->db->lasterrno();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *      Update bookmark record
	 *
	 *      @return     int         <0 if KO, > if OK
	 */
	public function update()
	{
		// Clean parameters
		$this->url = trim($this->url);
		$this->title = trim($this->title);
		if (empty($this->position)) {
			$this->position = 0;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."bookmark";
		$sql .= " SET fk_user = ".($this->fk_user > 0 ? $this->fk_user : "0");
		$sql .= " ,dateb = '".$this->db->idate($this->datec)."'";
		$sql .= " ,url = '".$this->db->escape($this->url)."'";
		$sql .= " ,target = '".$this->db->escape($this->target)."'";
		$sql .= " ,title = '".$this->db->escape($this->title)."'";
		$sql .= " ,favicon = '".$this->db->escape($this->favicon)."'";
		$sql .= " ,position = ".(int) $this->position;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog("Bookmark::update", LOG_DEBUG);
		if ($this->db->query($sql)) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *      Removes the bookmark
	 *
	 *      @param      int		$id     Id removed bookmark
	 *      @return     int         	<0 si ko, >0 si ok
	 */
	public function remove($id)
	{
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."bookmark";
		$sql .= " WHERE rowid = ".((int) $id);

		dol_syslog("Bookmark::remove", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old thirdparty id
	 * @param int $dest_id New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'bookmark'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}

	/**
	 *	Return label of contact status
	 *
	 *	@param      int			$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * 	@return 	string					Label of contact status
	 */
	public function getLibStatut($mode)
	{
		return '';
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$label = '<u>'.$langs->trans("Bookmark").'</u>';
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = DOL_URL_ROOT.'/bookmarks/card.php?id='.$this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowBookmark");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('mybookmarkdao'));
		$parameters = array('id'=>$this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}
}
