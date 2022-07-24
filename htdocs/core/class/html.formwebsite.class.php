<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/class/html.formwebsite.class.php
 *  \ingroup    core
 *	\brief      File of class to manage component html for module website
 */


/**
 *	Class to manage component html for module website
 */
class FormWebsite
{
	private $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error;

	/**
	 * var int		A number of lines
	 */
	public $num;


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *    Return HTML select list of websites
	 *
	 *    @param    string	$selected          Id modele pre-selectionne
	 *    @param    string	$htmlname          Name of HTML select
	 *    @param    int		$useempty          Show empty value or not
	 *    @return	string					   Html component
	 */
	public function selectWebsite($selected = '', $htmlname = 'exportmodelid', $useempty = 0)
	{
		$out = '';

		$sql = "SELECT rowid, ref";
		$sql .= " FROM ".$this->db->prefix()."website";
		$sql .= " WHERE 1 = 1";
		$sql .= " ORDER BY rowid";
		$result = $this->db->query($sql);
		if ($result) {
			$out .= '<select class="flat minwidth100" name="'.$htmlname.'" id="'.$htmlname.'">';
			if ($useempty) {
				$out .= '<option value="-1">&nbsp;</option>';
			}

			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				if ($selected == $obj->rowid) {
					$out .= '<option value="'.$obj->rowid.'" selected>';
				} else {
					$out .= '<option value="'.$obj->rowid.'">';
				}
				$out .= $obj->ref;
				$out .= '</option>';
				$i++;
			}
			$out .= "</select>";
		} else {
			dol_print_error($this->db);
		}

		return $out;
	}


	/**
	 *  Return a HTML select list of type of containers from the dictionary
	 *
	 *  @param  string	$htmlname          	Name of select zone
	 *  @param	string	$selected			Selected value
	 *  @param  int		$useempty          	1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
	 *  @param  string  $moreattrib         More attributes on HTML select tag
	 *  @param	int		$addjscombo			Add js combo
	 *  @param	string	$morecss			More CSS
	 * 	@return	void
	 */
	public function selectTypeOfContainer($htmlname, $selected = '', $useempty = 0, $moreattrib = '', $addjscombo = 0, $morecss = 'minwidth200')
	{
		global $langs, $conf, $user;

		$langs->load("admin");

		$sql = "SELECT rowid, code, label, entity";
		$sql .= " FROM ".$this->db->prefix().'c_type_container';
		$sql .= " WHERE active = 1 AND entity IN (".getEntity('c_type_container').")";
		$sql .= " ORDER BY label";

		dol_syslog(get_class($this)."::selectTypeOfContainer", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num) {
				print '<select id="select'.$htmlname.'" class="flat selectTypeOfContainer'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.'"'.($moreattrib ? ' '.$moreattrib : '').'>';
				if ($useempty == 1 || ($useempty == 2 && $num > 1)) {
					print '<option value="-1">&nbsp;</option>';
				}

				while ($i < $num) {
					$obj = $this->db->fetch_object($result);
					if ($selected == $obj->rowid || $selected == $obj->code) {
						print '<option value="'.$obj->code.'" selected>';
					} else {
						print '<option value="'.$obj->code.'">';
					}
					print $obj->label;
					print '</option>';
					$i++;
				}
				print "</select>";
				if ($user->admin) {
					print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
				}

				if ($addjscombo) {
					print ajax_combobox('select'.$htmlname);
				}
			} else {
				print $langs->trans("NoTypeOfPagePleaseEditDictionary");
			}
		} else {
			dol_print_error($this->db);
		}
	}


	/**
	 *  Return a HTML select list of samples of containers content
	 *
	 *  @param  string	$htmlname          	Name of select zone
	 *  @param	string	$selected			Selected value
	 *  @param  int		$useempty          	1=Add an empty value in list
	 *  @param  string  $moreattrib         More attributes on HTML select tag
	 *  @param	int		$addjscombo			Add js combo
	 *  @param	string	$morecss			More css
	 * 	@return	string						HTML select component with list of type of containers
	 */
	public function selectSampleOfContainer($htmlname, $selected = '', $useempty = 0, $moreattrib = '', $addjscombo = 0, $morecss = 'minwidth200')
	{
		global $langs, $conf, $user;

		$langs->load("admin");

		$listofsamples = dol_dir_list(DOL_DOCUMENT_ROOT.'/website/samples', 'files', 0, '^page-sample-.*\.html$');

		$arrayofsamples = array();
		$arrayofsamples['empty'] = 'EmptyPage'; // Always this one first
		foreach ($listofsamples as $sample) {
			$reg = array();
			if (preg_match('/^page-sample-(.*)\.html$/', $sample['name'], $reg)) {
				$key = $reg[1];
				$labelkey = ucfirst($key);
				if ($key == 'empty') {
					$labelkey = 'EmptyPage';
				}
				$arrayofsamples[$key] = $labelkey;
			}
		}

		$out = '';
		$out .= '<select id="select'.$htmlname.'" class="selectSampleOfContainer'.($morecss? ' '.$morecss : '').'" name="'.$htmlname.'"'.($moreattrib ? ' '.$moreattrib : '').'>';

		if ($useempty == 1 || $useempty == 2) {
			$out .= '<option value="-1">&nbsp;</option>';
		}

		foreach ($arrayofsamples as $key => $val) {
			if ($selected == $key) {
				$out .= '<option value="'.$key.'" selected>';
			} else {
				$out .= '<option value="'.$key.'">';
			}
			$out .= $langs->trans($val);
			$out .= '</option>';
		}
		$out .= "</select>";

		if ($addjscombo) {
			$out .= ajax_combobox('select'.$htmlname);
		}

		return $out;
	}


	/**
	 *  Return a HTML select list of containers of a website.
	 *  Note: $website->lines must have been loaded.
	 *
	 *  @param  Website		$website       	Object Website
	 *  @param	string		$htmlname		Name of select zone
	 *  @param	int			$pageid			Preselected container ID
	 *  @param	int			$showempty		Show empty record
	 *  @param	string		$action			Action on page that use this select list
	 *  @param	string		$morecss		More CSS
	 *  @param	array		$excludeids		Exclude some ID in list
	 * 	@return	string						HTML select component with list of block containers
	 */
	public function selectContainer($website, $htmlname = 'pageid', $pageid = 0, $showempty = 0, $action = '', $morecss = 'minwidth200', $excludeids = null)
	{
		$this->num = 0;

		$atleastonepage = (is_array($website->lines) && count($website->lines) > 0);

		$out = '';
		if ($atleastonepage && $action != 'editsource') {
			$out .= '<select name="'.$htmlname.'" id="'.$htmlname.'" class="maxwidth300'.($morecss ? ' '.$morecss : '').'">';
		} else {
			$out .= '<select name="pageidbis" id="pageid" class="maxwidth300'.($morecss ? ' '.$morecss : '').'" disabled="disabled">';
		}

		if ($showempty || !$atleastonepage) {
			$out .= '<option value="-1">&nbsp;</option>';
		}

		if ($atleastonepage) {
			if (empty($pageid) && $action != 'createcontainer') {      // Page id is not defined, we try to take one
				$firstpageid = 0;
				$homepageid = 0;
				foreach ($website->lines as $key => $valpage) {
					if (empty($firstpageid)) {
						$firstpageid = $valpage->id;
					}
					if ($website->fk_default_home && $key == $website->fk_default_home) {
						$homepageid = $valpage->id;
					}
				}
				$pageid = $homepageid ? $homepageid : $firstpageid; // We choose home page and if not defined yet, we take first page
			}

			foreach ($website->lines as $key => $valpage) {
				if (is_array($excludeids) && count($excludeids) && in_array($valpage->id, $excludeids)) {
					continue;
				}

				$valueforoption = '<span class="opacitymedium">['.$valpage->type_container.' '.sprintf("%03d", $valpage->id).']</span> ';
				$valueforoption .= $valpage->pageurl.' - '.$valpage->title;
				if ($website->otherlang) {	// If there is alternative lang for this web site, we show the language code
					if ($valpage->lang) {
						$valueforoption .= ' <span class="opacitymedium">('.$valpage->lang.')</span>';
					}
				}
				if ($website->fk_default_home && $key == $website->fk_default_home) {
					//$valueforoption .= ' <span class="opacitymedium">('.$langs->trans("HomePage").')</span>';
					$valueforoption .= ' <span class="opacitymedium fa fa-home"></span>';
				}

				$out .= '<option value="'.$key.'"';
				if ($pageid > 0 && $pageid == $key) {
					$out .= ' selected'; // To preselect a value
				}
				$out .= ' data-html="'.dol_escape_htmltag($valueforoption).'"';
				$out .= '>';
				$out .= $valueforoption;
				$out .= '</option>';

				++$this->num;
			}
		}
		$out .= '</select>';

		if ($atleastonepage && $action != 'editsource') {
			$out .= ajax_combobox($htmlname);
		} else {
			$out .= '<input type="hidden" name="'.$htmlname.'" value="'.$pageid.'">';
			$out .= ajax_combobox($htmlname);
		}
		return $out;
	}
}
