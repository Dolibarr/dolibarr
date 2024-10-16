<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/comm/action/class/cactioncomm.class.php
 *       \ingroup    agenda
 *       \brief      File of class to manage type of agenda events
 */


/**
 *      Class to manage different types of events
 */
class CActionComm
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var string code
	 */
	public $code;

	/**
	 * @var string type
	 */
	public $type;

	/**
	 * @var string label
	 * @deprecated
	 * @see $label
	 */
	public $libelle;

	/**
	 * @var string Type of agenda event label
	 */
	public $label;

	/**
	 * @var int active
	 */
	public $active;

	/**
	 * @var string color hex
	 */
	public $color;

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto;

	/**
	 * @var array array of type_actions
	 */
	public $type_actions = array();


	/**
	 * @var array	Used to return value by some methods
	 */
	public $liste_array;


	/**
	 *  Constructor
	 *
	 *  @param	DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *  Load action type from database
	 *
	 *  @param  int|string	$id     id or code of action type to read
	 *  @return int             	1=ok, 0=not found, -1=error
	 */
	public function fetch($id)
	{
		$sql = "SELECT id, code, type, libelle as label, color, active, picto";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_actioncomm";
		if (is_numeric($id)) {
			$sql .= " WHERE id=".(int) $id;
		} else {
			$sql .= " WHERE code='".$this->db->escape($id)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id      = $obj->id;
				$this->code    = $obj->code;
				$this->type    = $obj->type;
				$this->libelle = $obj->label; // deprecated
				$this->label   = $obj->label;
				$this->active  = $obj->active;
				$this->color   = $obj->color;

				$this->db->free($resql);
				return 1;
			} else {
				$this->db->free($resql);
				return 0;
			}
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of event types: array(id=>label) or array(code=>label)
	 *
	 *  @param  string|int  $active         1 or 0 to filter on event state active or not ('' by default = no filter)
	 *  @param  string      $idorcode       'id' or 'code' or 'all'
	 *  @param  string      $excludetype    Type to exclude ('system' or 'systemauto')
	 *  @param  int         $onlyautoornot  1=Group all type AC_XXX into 1 line AC_MANUAL. 0=Keep details of type, -1 or -2=Keep details and add a combined line per calendar (Default, Auto, BoothConf, ...)
	 *  @param  string      $morefilter     Add more SQL filter
	 *  @param  int         $shortlabel     1=Get short label instead of long label
	 *  @return array|int                   Array of all event types if OK, <0 if KO. Key of array is id or code depending on parameter $idorcode.
	 */
	public function liste_array($active = '', $idorcode = 'id', $excludetype = '', $onlyautoornot = 0, $morefilter = '', $shortlabel = 0)
	{
		// phpcs:enable
		global $langs, $conf, $user;
		$langs->load("commercial");

		$rep_id = array();
		$rep_code = array();
		$rep_all = array();

		$sql = "SELECT id, code, libelle as label, module, type, color, picto";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_actioncomm";
		$sql .= " WHERE 1=1";
		if ($active != '') {
			$sql .= " AND active=".(int) $active;
		}
		if (!empty($excludetype)) {
			$sql .= " AND type <> '".$this->db->escape($excludetype)."'";
		}
		if ($morefilter) {
			$sql .= " AND ".$morefilter;
		}
		// If AGENDA_SORT_EVENT_TYPE_BY_POSITION_FIRST is defined, we use position as main sort criterion
		// otherwise we use type as main sort criterion
		if (getDolGlobalString('AGENDA_SORT_EVENT_TYPE_BY_POSITION_FIRST')) {
			$sql .= " ORDER BY position, type, module";
		} else {
			$sql .= " ORDER BY type, position, module";
		}

		dol_syslog(get_class($this)."::liste_array", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$nump = $this->db->num_rows($resql);
			if ($nump) {
				$idforallfornewmodule = 96;
				$TSystem = array(
					'id' => [],
					'code' => [],
					'all' => []
				);
				$TSystemAuto = array(
					'id' => [],
					'code' => [],
					'all' => []
				);
				$TModule = array(
					'id' => [],
					'code' => [],
					'all' => []
				);
				$i = 0;
				while ($i < $nump) {
					$obj = $this->db->fetch_object($resql);

					$qualified = 1;

					// $obj->type into c_actioncomm can be 'system', 'systemauto', 'module', 'moduleauto', 'xxx', 'xxxauto'
					// Note: type = system... than type of event is added among other standard events.
					//       type = module... then type of event is grouped into module defined into module = myobject@mymodule. Example: Event organization or external modules
					//       type = xxx... then type of event is added into list as a new flat value (not grouped). Example: Agefod external module
					if ($qualified && $onlyautoornot > 0 && preg_match('/^system/', $obj->type) && !preg_match('/^AC_OTH/', $obj->code)) {
						$qualified = 0; // We discard detailed system events. We keep only the 2 generic lines (AC_OTH and AC_OTH_AUTO)
					}

					if ($qualified && !empty($obj->module)) {
						//var_dump($obj->type.' '.$obj->module.' '); var_dump($user->hasRight('facture', 'lire'));
						$qualified = 0;
						// Special cases
						if ($obj->module == 'invoice' && isModEnabled('invoice') && $user->hasRight('facture', 'lire')) {
							$qualified = 1;
						}
						if ($obj->module == 'order' && isModEnabled('order') && !$user->hasRight('commande', 'lire')) {
							$qualified = 1;
						}
						if ($obj->module == 'propal' && isModEnabled("propal") && $user->hasRight('propal', 'lire')) {
							$qualified = 1;
						}
						if ($obj->module == 'invoice_supplier' && ((isModEnabled("fournisseur") && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD') && $user->hasRight('fournisseur', 'facture', 'lire')) || (isModEnabled('supplier_invoice') && $user->hasRight('supplier_invoice', 'lire')))) {
							$qualified = 1;
						}
						if ($obj->module == 'order_supplier' && ((isModEnabled("fournisseur") && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD') && $user->hasRight('fournisseur', 'commande', 'lire')) || (!isModEnabled('supplier_order') && $user->hasRight('supplier_order', 'lire')))) {
							$qualified = 1;
						}
						if ($obj->module == 'shipping' && isModEnabled("shipping") && $user->hasRight('expedition', 'lire')) {
							$qualified = 1;
						}
						// For case module = 'myobject@eventorganization'
						$tmparray = explode("@", $obj->module);
						if (count($tmparray) > 1 && $tmparray[1] == 'eventorganization' && isModEnabled('eventorganization')) {
							$qualified = 1;
						}
						// For the generic case with type = 'module...' and module = 'myobject@mymodule'
						$regs = array();
						if (preg_match('/^module/', $obj->type)) {
							if (preg_match('/^(.+)@(.+)$/', $obj->module, $regs)) {
								$tmpobject = $regs[1];
								$tmpmodule = $regs[2];
								//var_dump($user->$tmpmodule);
								if ($tmpmodule && isset($conf->$tmpmodule) && isModEnabled($tmpmodule) && ($user->hasRight($tmpmodule, 'read') || $user->hasRight($tmpmodule, 'lire') || $user->hasRight($tmpmodule, $tmpobject, 'read') || $user->hasRight($tmpmodule, $tmpobject, 'lire'))) {
									$qualified = 1;
								}
							}
						}
						// For the case type is not 'system...' neither 'module', we just check module is on
						if (! in_array($obj->type, array('system', 'systemauto', 'module', 'moduleauto'))) {
							$tmpmodule = $obj->module;
							//var_dump($tmpmodule);
							if ($tmpmodule && isset($conf->$tmpmodule) && isModEnabled($tmpmodule)) {
								$qualified = 1;
							}
						}
					}

					if ($qualified) {
						$keyfortrans = '';
						$transcode = '';
						$code = $obj->code;
						$typecalendar = $obj->type;

						if ($onlyautoornot > 0 && $typecalendar == 'system') {
							$code = 'AC_MANUAL';
						} elseif ($onlyautoornot > 0 && $typecalendar == 'systemauto') {
							$code = 'AC_AUTO';
						} elseif ($onlyautoornot > 0) {
							$code = 'AC_'.strtoupper($obj->module);
						}

						if ($shortlabel) {
							$keyfortrans = "Action".$code.'Short';
							$transcode = $langs->trans($keyfortrans);
						}
						if (empty($keyfortrans) || $keyfortrans == $transcode) {
							$keyfortrans = "Action".$code;
							$transcode = $langs->trans($keyfortrans);
						}

						$label = (($transcode != $keyfortrans) ? $transcode : $langs->trans($obj->label));
						if (($onlyautoornot == -1 || $onlyautoornot == -2) && getDolGlobalString('AGENDA_USE_EVENT_TYPE')) {
							// Add a group of elements
							if ($typecalendar == 'system' || $typecalendar == 'user') {
								$label = '&nbsp;&nbsp; '.$label;
								$TSystem['id'][-99] = $langs->trans("ActionAC_MANUAL");
								$TSystem['code']['AC_NON_AUTO'] = '-- '.$langs->trans("ActionAC_MANUAL");
							}
							if ($typecalendar == 'systemauto') {
								$label = '&nbsp;&nbsp; '.$label;
								$TSystemAuto['id'][-98] = $langs->trans("ActionAC_AUTO");
								$TSystemAuto['code']['AC_ALL_AUTO'] = '-- '.$langs->trans("ActionAC_AUTO");
							}

							if ($typecalendar == 'module') {
								$module = preg_replace('/^[^@]+@/', '', $obj->module);
								$label = '&nbsp;&nbsp; '.$label;
								if (!isset($TModule['id'][-1 * $idforallfornewmodule])) {	// If first time for this module
									$idforallfornewmodule--;
								}
								$TModule['id'][-1 * $idforallfornewmodule] = $langs->trans("ActionAC_ALL_".strtoupper($module));
								$TModule['code']['AC_ALL_'.strtoupper($module)] = '-- '.$langs->trans("Module").' '.ucfirst($module);
							}
						}
						// Add element
						if ($typecalendar == 'system' || $typecalendar == 'user') {
							$TSystem['id'][$obj->id] = $label;
							$TSystem['code'][$obj->code] = $label;
							$TSystem['all'][$obj->code] = array('id' => $label, 'label' => $label, 'type' => $typecalendar, 'color' => $obj->color, 'picto' => $obj->picto);
						} elseif ($typecalendar == 'systemauto') {
							$TSystemAuto['id'][$obj->id] = $label;
							$TSystemAuto['code'][$obj->code] = $label;
							$TSystemAuto['all'][$obj->code] = array('id' => $label, 'label' => $label, 'type' => $typecalendar, 'color' => $obj->color, 'picto' => $obj->picto);
						} elseif ($typecalendar == 'module') {	// Can be automatic or manual
							$module = preg_replace('/^[^@]+@/', '', $obj->module);
							$TModule['id'][$obj->id] = $label;
							$TModule['code'][$obj->code] = $label;
							$TModule['all'][$obj->code] = array('id' => $label, 'label' => $langs->trans("Module").' '.ucfirst($module).' - '.$label, 'type' => $typecalendar, 'color' => $obj->color, 'picto' => $obj->picto);
						}

						if ($onlyautoornot > 0 && preg_match('/^module/', $obj->type) && $obj->module) {
							$moduletoshow = ucfirst(preg_replace('/^[^@]+@/', '', $obj->module));
							//array_key_exists($obj->code, $TModule['code']) ? ($TModule['code'][$obj->code] .= $langs->trans("Module").': '.$moduletoshow.' - '.$label) : ($TModule['code'][$obj->code] = $langs->trans("Module").': '.$moduletoshow.' - '.$label);
							//array_key_exists($obj->code, $TModule['all']) ? ($TModule['all'][$obj->code]['label'] .= $langs->trans("Module").': '.$moduletoshow.' - '.$label) : ($TModule['all'][$obj->code]['label'] = $langs->trans("Module").': '.$moduletoshow.' - '.$label);
							$TModule['code'][$obj->code] = $moduletoshow.' - '.$label;
							$TModule['all'][$obj->code]['label'] = $moduletoshow.' - '.$label;
						}
					}
					$i++;
				}
			}

			if (empty($idorcode)) {
				$idorcode = 'all';
			}
			$TType = $TSystem[$idorcode];
			if (! empty($TSystemAuto[$idorcode])) {
				$TType = array_merge($TType, $TSystemAuto[$idorcode]);
			}
			if (! empty($TModule[$idorcode])) {
				$TType = array_merge($TType, $TModule[$idorcode]);
			}
			$this->liste_array = $TType;

			return $this->liste_array;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Return name of action type as a label translated
	 *
	 *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Picto only
	 *  @return string|-1		      	Label of action type, or -1 if error
	 */
	public function getNomUrl($withpicto = 0)
	{
		global $langs;

		// Check if translation available
		$transcode = $langs->trans("Action".$this->code);
		if ($transcode != "Action".$this->code) {
			return $transcode;
		}
		return -1;
	}
}
